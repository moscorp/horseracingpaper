<?php
// HKJCHorseHistoryBatchManagerV2.php - 分离队列版本

require_once 'HKJCHorseHistoryManager.php';

class HKJCHorseHistoryBatchManagerV2 {
    private $db;
    private $horseHistoryManager;
    private $batchSize = 5;
    private $maxExecutionTime = 8;
    
    public function __construct($dbHost, $dbName, $dbUser, $dbPass) {
        try {
            $this->db = new PDO(
                "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
                $dbUser,
                $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $this->db->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            throw new Exception("数据库连接失败: " . $e->getMessage());
        }
        $this->horseHistoryManager = new HKJCHorseHistoryManager($dbHost, $dbName, $dbUser, $dbPass);
    }
    
    /**
     * 检查是否为香港马匹
     */
    private function isHongKongHorse($horseCode) {
        return preg_match('/^[A-Z]\d{3}$/', $horseCode);
    }
    
    /**
     * 添加到历史抓取队列（一次性全量抓取）
     */
    public function addToHistoryQueue($horseCode, $horseNameCh = '', $priority = 0) {
        if (!$this->isHongKongHorse($horseCode)) {
            return false;
        }
        
        // 检查是否已在队列中
        $stmt = $this->db->prepare("
            SELECT status FROM hkracing_history_queue 
            WHERE horse_code = ? AND status IN ('pending', 'processing')
        ");
        $stmt->execute([$horseCode]);
        if ($stmt->fetch()) {
            return false;
        }
        
        // 检查是否已完成历史抓取
        $stmt = $this->db->prepare("
            SELECT last_sync_date FROM hkracing_horses WHERE horse_code = ?
        ");
        $stmt->execute([$horseCode]);
        $horse = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($horse && $horse['last_sync_date']) {
            // 已经有历史数据，不需要再添加到历史队列
            return false;
        }
        
        $hkTime = new DateTime('now', new DateTimeZone('Asia/Hong_Kong'));
        $createdAt = $hkTime->format('Y-m-d H:i:s');
        
        $stmt = $this->db->prepare("
            INSERT INTO hkracing_history_queue (horse_code, horse_name_ch, priority, status, created_at)
            VALUES (?, ?, ?, 'pending', ?)
            ON DUPLICATE KEY UPDATE
            horse_name_ch = COALESCE(?, horse_name_ch),
            priority = GREATEST(priority, VALUES(priority)),
            status = IF(status = 'completed', 'pending', status)
        ");
        return $stmt->execute([$horseCode, $horseNameCh, $priority, $createdAt, $horseNameCh]);
    }
    
    /**
     * 添加到增量更新队列（日常检查新比赛）
     */
    public function addToIncrementalQueue($horseCode, $horseNameCh = '', $reason = 'new_race') {
        if (!$this->isHongKongHorse($horseCode)) {
            return false;
        }
        
        // 检查是否已在队列中
        $stmt = $this->db->prepare("
            SELECT status FROM hkracing_incremental_queue 
            WHERE horse_code = ? AND status IN ('pending', 'processing')
        ");
        $stmt->execute([$horseCode]);
        if ($stmt->fetch()) {
            return false;
        }
        
        // 检查历史数据是否已抓取
        $stmt = $this->db->prepare("
            SELECT last_sync_date FROM hkracing_horses WHERE horse_code = ?
        ");
        $stmt->execute([$horseCode]);
        $horse = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$horse || !$horse['last_sync_date']) {
            // 历史数据未抓取，不应该添加到增量队列
            return false;
        }
        
        $hkTime = new DateTime('now', new DateTimeZone('Asia/Hong_Kong'));
        $createdAt = $hkTime->format('Y-m-d H:i:s');
        
        $stmt = $this->db->prepare("
            INSERT INTO hkracing_incremental_queue (horse_code, horse_name_ch, reason, status, created_at)
            VALUES (?, ?, ?, 'pending', ?)
        ");
        return $stmt->execute([$horseCode, $horseNameCh, $reason, $createdAt]);
    }
    
    /**
     * 处理历史抓取队列
     */
    public function processHistoryBatch() {
        $startTime = microtime(true);
        $results = ['success' => 0, 'failed' => 0, 'skipped' => 0];
        
        $limit = (int)$this->batchSize;
        $stmt = $this->db->query("
            SELECT horse_code, horse_name_ch
            FROM hkracing_history_queue 
            WHERE status = 'pending'
            ORDER BY priority DESC, retry_count ASC, created_at ASC
            LIMIT {$limit}
        ");
        
        $horses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($horses)) {
            return ['message' => '历史队列为空', 'results' => $results];
        }
        
        foreach ($horses as $horse) {
            if ((microtime(true) - $startTime) > $this->maxExecutionTime) {
                echo "  执行时间超限，剩余任务下次处理\n";
                break;
            }
            
            $horseCode = $horse['horse_code'];
            $this->updateHistoryQueueStatus($horseCode, 'processing');
            
            $fetchStart = microtime(true);
            echo "  [历史] 抓取: {$horseCode} ... ";
            
            $data = $this->horseHistoryManager->fetchHorseHistory($horseCode);
            $duration = round((microtime(true) - $fetchStart) * 1000);
            
            if (!isset($data['error']) && !empty($data['info']['name_ch'])) {
                $this->horseHistoryManager->saveHorseHistory($horseCode, $data);
                $this->updateHistoryQueueStatus($horseCode, 'completed');
                $this->logFetchResult($horseCode, 'success', null, count($data['performances']), $duration);
                $results['success']++;
                echo "成功 ({$duration}ms, " . count($data['performances']) . "场)\n";
            } else {
                $errorMsg = $data['error'] ?? '无数据返回';
                $this->updateHistoryQueueStatus($horseCode, 'failed', true);
                $this->logFetchResult($horseCode, 'failed', $errorMsg, 0, $duration);
                $results['failed']++;
                echo "失败: {$errorMsg}\n";
            }
        }
        
        return [
            'message' => '历史批次处理完成',
            'results' => $results,
            'duration_ms' => round((microtime(true) - $startTime) * 1000)
        ];
    }
    
    /**
     * 处理增量更新队列
     */
    public function processIncrementalBatch() {
        $startTime = microtime(true);
        $results = ['success' => 0, 'failed' => 0, 'skipped' => 0];
        
        $limit = (int)$this->batchSize;
        $stmt = $this->db->query("
            SELECT horse_code, horse_name_ch, reason
            FROM hkracing_incremental_queue 
            WHERE status = 'pending'
            ORDER BY created_at ASC
            LIMIT {$limit}
        ");
        
        $horses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($horses)) {
            return ['message' => '增量队列为空', 'results' => $results];
        }
        
        foreach ($horses as $horse) {
            if ((microtime(true) - $startTime) > $this->maxExecutionTime) {
                echo "  执行时间超限，剩余任务下次处理\n";
                break;
            }
            
            $horseCode = $horse['horse_code'];
            $this->updateIncrementalQueueStatus($horseCode, 'processing');
            
            $fetchStart = microtime(true);
            echo "  [增量] 更新: {$horseCode} ({$horse['reason']}) ... ";
            
            $data = $this->horseHistoryManager->fetchHorseHistory($horseCode);
            $duration = round((microtime(true) - $fetchStart) * 1000);
            
            if (!isset($data['error']) && !empty($data['info']['name_ch'])) {
                $this->horseHistoryManager->saveHorseHistory($horseCode, $data);
                $this->updateIncrementalQueueStatus($horseCode, 'completed');
                $this->logFetchResult($horseCode, 'incremental_success', null, count($data['performances']), $duration);
                $results['success']++;
                echo "成功 ({$duration}ms, 新增 " . count($data['performances']) . "场)\n";
            } else {
                $errorMsg = $data['error'] ?? '无数据返回';
                $this->updateIncrementalQueueStatus($horseCode, 'failed', true);
                $this->logFetchResult($horseCode, 'incremental_failed', $errorMsg, 0, $duration);
                $results['failed']++;
                echo "失败: {$errorMsg}\n";
            }
        }
        
        return [
            'message' => '增量批次处理完成',
            'results' => $results,
            'duration_ms' => round((microtime(true) - $startTime) * 1000)
        ];
    }
    
    /**
     * 更新历史队列状态
     */
    private function updateHistoryQueueStatus($horseCode, $status, $incrementRetry = false) {
        $hkTime = new DateTime('now', new DateTimeZone('Asia/Hong_Kong'));
        $now = $hkTime->format('Y-m-d H:i:s');
        
        if ($incrementRetry) {
            $stmt = $this->db->prepare("
                UPDATE hkracing_history_queue 
                SET status = ?, retry_count = retry_count + 1, last_attempt = ?
                WHERE horse_code = ?
            ");
            $stmt->execute([$status, $now, $horseCode]);
        } else {
            $stmt = $this->db->prepare("
                UPDATE hkracing_history_queue 
                SET status = ?, last_attempt = ?
                WHERE horse_code = ?
            ");
            $stmt->execute([$status, $now, $horseCode]);
        }
    }
    
    /**
     * 更新增量队列状态
     */
    private function updateIncrementalQueueStatus($horseCode, $status, $incrementRetry = false) {
        $hkTime = new DateTime('now', new DateTimeZone('Asia/Hong_Kong'));
        $now = $hkTime->format('Y-m-d H:i:s');
        
        if ($incrementRetry) {
            $stmt = $this->db->prepare("
                UPDATE hkracing_incremental_queue 
                SET status = ?, retry_count = retry_count + 1, last_attempt = ?
                WHERE horse_code = ? AND status != 'completed'
            ");
            $stmt->execute([$status, $now, $horseCode]);
        } else {
            $stmt = $this->db->prepare("
                UPDATE hkracing_incremental_queue 
                SET status = ?, last_attempt = ?
                WHERE horse_code = ? AND status != 'completed'
            ");
            $stmt->execute([$status, $now, $horseCode]);
        }
    }
    
    /**
     * 记录抓取日志
     */
    private function logFetchResult($horseCode, $status, $errorMsg = null, $perfCount = 0, $durationMs = 0) {
        $hkTime = new DateTime('now', new DateTimeZone('Asia/Hong_Kong'));
        $createdAt = $hkTime->format('Y-m-d H:i:s');
        
        $stmt = $this->db->prepare("
            INSERT INTO hkracing_horse_fetch_log 
            (horse_code, status, error_message, performances_count, duration_ms, created_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$horseCode, $status, $errorMsg, $perfCount, $durationMs, $createdAt]);
    }
    
    /**
     * 获取队列统计信息
     */
    public function getQueueStats() {
        // 历史队列统计
        $stmt = $this->db->query("
            SELECT 
                status,
                COUNT(*) as count
            FROM hkracing_history_queue
            GROUP BY status
        ");
        $historyStats = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $historyStats[$row['status']] = $row['count'];
        }
        
        // 增量队列统计
        $stmt = $this->db->query("
            SELECT 
                status,
                COUNT(*) as count
            FROM hkracing_incremental_queue
            GROUP BY status
        ");
        $incrementalStats = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $incrementalStats[$row['status']] = $row['count'];
        }
        
        // 马匹总数
        $stmt = $this->db->query("
            SELECT COUNT(DISTINCT horse_code) as total
            FROM hkracing_runners 
            WHERE horse_code REGEXP '^[A-Z][0-9]{3}$'
        ");
        $totalHorses = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $this->db->query("
            SELECT COUNT(*) as synced
            FROM hkracing_horses 
            WHERE horse_code REGEXP '^[A-Z][0-9]{3}$'
              AND last_sync_date IS NOT NULL
        ");
        $syncedHorses = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'history_queue' => $historyStats,
            'incremental_queue' => $incrementalStats,
            'total_hk_horses' => (int)($totalHorses['total'] ?? 0),
            'synced_horses' => (int)($syncedHorses['synced'] ?? 0),
            'history_pending' => (int)($historyStats['pending'] ?? 0),
            'incremental_pending' => (int)($incrementalStats['pending'] ?? 0)
        ];
    }
    
    /**
     * 重置卡住的任务
     */
    public function resetStuckTasks() {
        $count = 0;
        
        // 重置历史队列中卡住的任务（超过30分钟）
        $stmt = $this->db->prepare("
            UPDATE hkracing_history_queue 
            SET status = 'pending', last_attempt = NULL
            WHERE status = 'processing' 
              AND last_attempt < DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ");
        $stmt->execute();
        $count += $stmt->rowCount();
        
        // 重置增量队列中卡住的任务
        $stmt = $this->db->prepare("
            UPDATE hkracing_incremental_queue 
            SET status = 'pending', last_attempt = NULL
            WHERE status = 'processing' 
              AND last_attempt < DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ");
        $stmt->execute();
        $count += $stmt->rowCount();
        
        return $count;
    }
}
?>