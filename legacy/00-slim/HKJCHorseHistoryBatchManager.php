<?php
// HKJCHorseHistoryBatchManager.php - 完整版（无重复方法）

require_once 'HKJCHorseHistoryManager.php';

class HKJCHorseHistoryBatchManager {
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
     * 从 runners 表中收集所有马匹到队列
     */
    public function collectHorsesFromRunners() {
        $stmt = $this->db->query("
            SELECT DISTINCT 
                ru.horse_code,
                ru.name_ch as horse_name_ch
            FROM hkracing_runners ru
            LEFT JOIN hkracing_pending_horses p ON ru.horse_code = p.horse_code
            WHERE ru.horse_code IS NOT NULL 
              AND ru.horse_code != ''
              AND ru.horse_code REGEXP '^[A-Z][0-9]{3}$'
              AND p.horse_code IS NULL
            ORDER BY ru.created_at DESC
            LIMIT 500
        ");
        
        $count = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->addToQueue($row['horse_code'], $row['horse_name_ch']);
            $count++;
        }
        return $count;
    }
    
    /**
     * 添加马匹到队列（支持同步原因）
     * 这是唯一的一个 addToQueue 方法
     */
    // 在 addToQueue 方法中，使用 PHP 生成香港时间
    public function addToQueue($horseCode, $horseNameCh = '', $priority = 0, $reason = 'new') {
        // 只处理香港马匹格式
        if (!$this->isHongKongHorse($horseCode)) {
            return false;
        }
        
        // 使用 PHP 生成香港时间
        $hkTime = new DateTime('now', new DateTimeZone('Asia/Hong_Kong'));
        $updatedAt = $hkTime->format('Y-m-d H:i:s');
        
        // 检查是否已经在队列中
        $stmt = $this->db->prepare("
            SELECT status FROM hkracing_pending_horses 
            WHERE horse_code = ? AND status IN ('pending', 'processing')
        ");
        $stmt->execute([$horseCode]);
        if ($stmt->fetch()) {
            return false;
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO hkracing_pending_horses (horse_code, horse_name_ch, priority, status, sync_reason, updated_at)
            VALUES (?, ?, ?, 'pending', ?, ?)
            ON DUPLICATE KEY UPDATE
            horse_name_ch = COALESCE(?, horse_name_ch),
            priority = GREATEST(priority, VALUES(priority)),
            status = IF(status = 'completed', 'pending', status),
            sync_reason = VALUES(sync_reason),
            updated_at = ?
        ");
        return $stmt->execute([
            $horseCode, 
            $horseNameCh, 
            $priority, 
            $reason, 
            $updatedAt,
            $horseNameCh,
            $updatedAt
        ]);
    }
    
    /**
     * 检查马匹是否需要更新
     */
    private function needsUpdate($horseCode) {
        $stmt = $this->db->prepare("
            SELECT last_sync_date, total_starts 
            FROM hkracing_horses 
            WHERE horse_code = ?
        ");
        $stmt->execute([$horseCode]);
        $horse = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$horse) {
            return true;
        }
        
        if (!$horse['last_sync_date'] || strtotime($horse['last_sync_date']) < strtotime('-7 days')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 批量处理队列中的马匹
     */
    public function processBatch() {
        $startTime = microtime(true);
        $results = ['success' => 0, 'failed' => 0, 'skipped' => 0];
        
        $limit = (int)$this->batchSize;
        $stmt = $this->db->query("
            SELECT horse_code, horse_name_ch
            FROM hkracing_pending_horses 
            WHERE status = 'pending'
              AND horse_code REGEXP '^[A-Z][0-9]{3}$'
            ORDER BY priority DESC, retry_count ASC, created_at ASC
            LIMIT {$limit}
        ");
        
        $horses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($horses)) {
            return ['message' => '队列为空', 'results' => $results];
        }
        
        foreach ($horses as $horse) {
            if ((microtime(true) - $startTime) > $this->maxExecutionTime) {
                echo "  执行时间超限，剩余任务下次处理\n";
                break;
            }
            
            $horseCode = $horse['horse_code'];
            
            if (!$this->isHongKongHorse($horseCode)) {
                echo "  跳过: {$horseCode} (海外马，无香港历史数据)\n";
                $this->updateQueueStatus($horseCode, 'completed');
                $results['skipped']++;
                continue;
            }
            
            $this->updateQueueStatus($horseCode, 'processing');
            
            if (!$this->needsUpdate($horseCode)) {
                $this->updateQueueStatus($horseCode, 'completed');
                $results['skipped']++;
                echo "  跳过: {$horseCode} (已是最新)\n";
                continue;
            }
            
            $fetchStart = microtime(true);
            echo "  抓取: {$horseCode} ... ";
            
            $data = $this->horseHistoryManager->fetchHorseHistory($horseCode);
            $duration = round((microtime(true) - $fetchStart) * 1000);
            
            if (!isset($data['error']) && !empty($data['info']['name_ch'])) {
                $this->horseHistoryManager->saveHorseHistory($horseCode, $data);
                $this->updateQueueStatus($horseCode, 'completed');
                $this->logFetchResult($horseCode, 'success', null, count($data['performances']), $duration);
                $results['success']++;
                echo "成功 ({$duration}ms, " . count($data['performances']) . "场)\n";
            } else {
                $errorMsg = $data['error'] ?? '无数据返回';
                $this->updateQueueStatus($horseCode, 'failed', true);
                $this->logFetchResult($horseCode, 'failed', $errorMsg, 0, $duration);
                $results['failed']++;
                echo "失败: {$errorMsg}\n";
            }
        }
        
        return [
            'message' => '批次处理完成',
            'results' => $results,
            'duration_ms' => round((microtime(true) - $startTime) * 1000)
        ];
    }
    
    /**
     * 更新队列状态
     */
    private function updateQueueStatus($horseCode, $status, $incrementRetry = false) {
        $hkTime = new DateTime('now', new DateTimeZone('Asia/Hong_Kong'));
        $now = $hkTime->format('Y-m-d H:i:s');
        
        if ($incrementRetry) {
            $stmt = $this->db->prepare("
                UPDATE hkracing_pending_horses 
                SET status = ?, retry_count = retry_count + 1, last_attempt = ?
                WHERE horse_code = ?
            ");
            $stmt->execute([$status, $now, $horseCode]);
        } else {
            $stmt = $this->db->prepare("
                UPDATE hkracing_pending_horses 
                SET status = ?, last_attempt = ?
                WHERE horse_code = ?
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
        $stmt = $this->db->query("
            SELECT 
                status,
                COUNT(*) as count,
                SUM(retry_count) as total_retries
            FROM hkracing_pending_horses 
            WHERE horse_code REGEXP '^[A-Z][0-9]{3}$'
            GROUP BY status
        ");
        $stats = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats[$row['status']] = $row;
        }
        
        $stmt = $this->db->query("
            SELECT COUNT(DISTINCT horse_code) as hk_horses
            FROM hkracing_runners 
            WHERE horse_code REGEXP '^[A-Z][0-9]{3}$'
        ");
        $hkHorses = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $this->db->query("
            SELECT COUNT(*) as synced_horses
            FROM hkracing_horses 
            WHERE horse_code REGEXP '^[A-Z][0-9]{3}$'
              AND last_sync_date IS NOT NULL
        ");
        $syncedHorses = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'queue' => $stats,
            'total_hk_horses' => (int)($hkHorses['hk_horses'] ?? 0),
            'synced_horses' => (int)($syncedHorses['synced_horses'] ?? 0),
            'pending_count' => (int)($stats['pending']['count'] ?? 0),
            'processing_count' => (int)($stats['processing']['count'] ?? 0),
            'completed_count' => (int)($stats['completed']['count'] ?? 0),
            'failed_count' => (int)($stats['failed']['count'] ?? 0)
        ];
    }
    
    /**
     * 清空失败的任务
     */
    public function clearFailedTasks() {
        $stmt = $this->db->prepare("
            DELETE FROM hkracing_pending_horses 
            WHERE status = 'failed' AND retry_count >= 3
        ");
        $stmt->execute();
        return $stmt->rowCount();
    }
    
    /**
     * 重置卡住的任务（超过30分钟还在 processing）
     */
    public function resetStuckTasks() {
        $stmt = $this->db->prepare("
            UPDATE hkracing_pending_horses 
            SET status = 'pending', last_attempt = NULL
            WHERE status = 'processing' 
              AND last_attempt < DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ");
        $stmt->execute();
        return $stmt->rowCount();
    }
    
    /**
     * 手动添加单匹马到队列
     */
    public function addHorseManually($horseCode, $horseNameCh = '') {
        if (!$this->isHongKongHorse($horseCode)) {
            return ['success' => false, 'message' => '无效的马匹代码格式，应为字母+3位数字如 J071'];
        }
        $this->addToQueue($horseCode, $horseNameCh, 10);
        return ['success' => true, 'message' => "已添加 {$horseCode} 到队列"];
    }
    
    /**
     * 强制重新抓取某匹马
     */
    public function forceRefetch($horseCode) {
        if (!$this->isHongKongHorse($horseCode)) {
            return ['success' => false, 'message' => '无效的马匹代码格式'];
        }
        
        $stmt = $this->db->prepare("DELETE FROM hkracing_horses WHERE horse_code = ?");
        $stmt->execute([$horseCode]);
        $stmt = $this->db->prepare("DELETE FROM hkracing_horse_performances WHERE horse_code = ?");
        $stmt->execute([$horseCode]);
        $this->addToQueue($horseCode, '', 10);
        
        return ['success' => true, 'message' => "已重置并重新加入队列 {$horseCode}"];
    }
}
?>