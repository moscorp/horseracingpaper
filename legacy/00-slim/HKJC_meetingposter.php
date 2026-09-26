<?php
// HKJC_meetingposter.php - 带数据库管理的截图生成器

require_once 'lib/constants.php';

class HKJCRacingPoster {
    private $pdo;
    private $imageDir = 'image/horseracing/';
    private $baseUrl;
    
    public function __construct() {
        global $dbConfig;
        $this->pdo = new PDO(
            "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
            $dbConfig['user'],
            $dbConfig['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $this->baseUrl = "https://" . $_SERVER['HTTP_HOST'] . "/00";
        
        if (!file_exists($this->imageDir)) {
            mkdir($this->imageDir, 0777, true);
        }
    }
    
    /**
     * 创建截图任务
     */
    private function createTask($date, $venue, $raceNo) {
        $shareUrl = $this->baseUrl . "/HKJC_race_share.php?date={$date}&venue={$venue}&race_no={$raceNo}";
        
        // 检查是否已有任务
        $stmt = $this->pdo->prepare("
            SELECT id, status, retry_count FROM hkracing_screenshot_tasks 
            WHERE race_date = ? AND venue_code = ? AND race_no = ?
        ");
        $stmt->execute([$date, $venue, $raceNo]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // 只重试失败或 pending 的任务
            if (in_array($existing['status'], ['failed', 'pending', 'retry'])) {
                $stmt = $this->pdo->prepare("
                    UPDATE hkracing_screenshot_tasks 
                    SET status = 'pending', error_message = NULL, started_at = NULL, completed_at = NULL
                    WHERE id = ?
                ");
                $stmt->execute([$existing['id']]);
                return $existing['id'];
            }
            return $existing['id'];
        }
        
        // 创建新任务
        $stmt = $this->pdo->prepare("
            INSERT INTO hkracing_screenshot_tasks (race_date, venue_code, race_no, share_url, status)
            VALUES (?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$date, $venue, $raceNo, $shareUrl]);
        return $this->pdo->lastInsertId();
    }
    
    /**
     * 更新任务状态
     */
    private function updateTask($taskId, $status, $errorMsg = null, $imagePath = null, $apiResponse = null) {
        $fields = ['status = ?', 'completed_at = NOW()'];
        $params = [$status];
        
        if ($errorMsg) {
            $fields[] = 'error_message = ?';
            $params[] = $errorMsg;
        }
        if ($imagePath) {
            $fields[] = 'image_path = ?';
            $params[] = $imagePath;
            
            // 获取文件大小
            if (file_exists($imagePath)) {
                $fields[] = 'image_size = ?';
                $params[] = filesize($imagePath);
            }
        }
        if ($apiResponse) {
            $fields[] = 'api_response = ?';
            $params[] = $apiResponse;
        }
        
        $params[] = $taskId;
        $sql = "UPDATE hkracing_screenshot_tasks SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }
    
    /**
     * 开始处理任务
     */
    private function startTask($taskId) {
        $stmt = $this->pdo->prepare("
            UPDATE hkracing_screenshot_tasks 
            SET status = 'processing', started_at = NOW(), retry_count = retry_count + 1
            WHERE id = ?
        ");
        $stmt->execute([$taskId]);
    }
    
    /**
     * 使用 Google PageSpeed API 截图
     */
    private function captureWithGooglePagespeed($url, $outputPath) {
        $apiUrl = "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=" . urlencode($url) . "&screenshot=true";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode != 200 || !$response) {
            return ['success' => false, 'error' => "HTTP {$httpCode}: " . substr($response, 0, 200)];
        }
        
        $data = json_decode($response, true);
        
        // 提取截图
        $screenshotData = null;
        if (isset($data['lighthouseResult']['audits']['final-screenshot']['details']['data'])) {
            $screenshotData = $data['lighthouseResult']['audits']['final-screenshot']['details']['data'];
        } elseif (isset($data['screenshot']['data'])) {
            $screenshotData = $data['screenshot']['data'];
        }
        
        if (!$screenshotData) {
            return ['success' => false, 'error' => 'No screenshot data in response'];
        }
        
        // 解码
        $decodedData = str_replace('_', '/', $screenshotData);
        $decodedData = str_replace('-', '+', $decodedData);
        if (strpos($decodedData, 'base64,') !== false) {
            $decodedData = preg_replace('/^data:image\/\w+;base64,/', '', $decodedData);
        }
        
        $imageBinary = base64_decode($decodedData);
        
        if (!$imageBinary || strlen($imageBinary) < 1000) {
            return ['success' => false, 'error' => 'Invalid image data'];
        }
        
        file_put_contents($outputPath, $imageBinary);
        return ['success' => true, 'size' => strlen($imageBinary)];
    }
    
    /**
     * 生成单场赛事的海报（带任务管理）
     */
    public function generateRacePoster($date, $venue, $raceNo) {
        // 创建任务
        $taskId = $this->createTask($date, $venue, $raceNo);
        
        // 获取任务信息
        $stmt = $this->pdo->prepare("
            SELECT share_url, status FROM hkracing_screenshot_tasks WHERE id = ?
        ");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($task['status'] == 'success') {
            echo "  任务已完成，跳过\n";
            return true;
        }
        
        // 开始处理
        $this->startTask($taskId);
        
        $shareUrl = $task['share_url'];
        $filename = $this->imageDir . "{$venue}_{$date}_R{$raceNo}_" . date('Ymd_His') . ".png";
        
        echo "  开始截图: {$shareUrl}\n";
        
        $result = $this->captureWithGooglePagespeed($shareUrl, $filename);
        
        if ($result['success']) {
            $this->updateTask($taskId, 'success', null, $filename);
            echo "  ✓ 成功！图片大小: " . round($result['size']/1024, 1) . " KB\n";
            return true;
        } else {
            $this->updateTask($taskId, 'failed', $result['error']);
            echo "  ✗ 失败: {$result['error']}\n";
            return false;
        }
    }
    
    /**
     * 生成所有赛事的海报
     */
    public function generateAllPosters($date, $venue) {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT race_no
            FROM hkracing_races r
            JOIN hkracing_meetings m ON r.meeting_id = m.id
            WHERE m.date = ? AND m.venue_code = ?
            ORDER BY race_no
        ");
        $stmt->execute([$date, $venue]);
        $races = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "找到 " . count($races) . " 场赛事\n";
        echo "每张截图约需30秒，请耐心等待...\n\n";
        
        $successCount = 0;
        foreach ($races as $index => $race) {
            echo "[" . ($index + 1) . "/" . count($races) . "] 第 {$race['race_no']} 場...\n";
            
            if ($this->generateRacePoster($date, $venue, $race['race_no'])) {
                $successCount++;
            }
            
            echo "\n";
            
            // 每张截图后延迟30秒
            if ($index < count($races) - 1) {
                echo "等待 30 秒后进行下一场...\n";
                sleep(30);
            }
        }
        
        return $successCount;
    }
    
    /**
     * 查看任务状态
     */
    public function getTaskStatus($date, $venue) {
        $stmt = $this->pdo->prepare("
            SELECT race_no, status, retry_count, image_path, error_message
            FROM hkracing_screenshot_tasks
            WHERE race_date = ? AND venue_code = ?
            ORDER BY race_no
        ");
        $stmt->execute([$date, $venue]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 重试失败的任务
     */
    public function retryFailed($date, $venue) {
        $stmt = $this->pdo->prepare("
            SELECT race_no FROM hkracing_screenshot_tasks
            WHERE race_date = ? AND venue_code = ? AND status = 'failed'
            ORDER BY race_no
        ");
        $stmt->execute([$date, $venue]);
        $failed = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($failed as $race) {
            echo "重试第 {$race['race_no']} 場...\n";
            $this->generateRacePoster($date, $venue, $race['race_no']);
            sleep(30);
        }
    }
}

// 运行
if (basename($_SERVER['SCRIPT_FILENAME']) == 'HKJC_meetingposter.php') {
    $action = $_GET['action'] ?? 'generate';
    $date = $_GET['date'] ?? '2026-04-26';
    $venue = $_GET['venue'] ?? 'ST';
    
    $poster = new HKJCRacingPoster();
    
    switch ($action) {
        case 'generate':
            echo "开始生成海报: {$date} {$venue}\n";
            echo "================================\n\n";
            $count = $poster->generateAllPosters($date, $venue);
            echo "\n完成! 成功生成 {$count} 张图片\n";
            break;
            
        case 'status':
            $tasks = $poster->getTaskStatus($date, $venue);
            echo "任务状态:\n";
            echo str_repeat("-", 50) . "\n";
            foreach ($tasks as $task) {
                $statusIcon = $task['status'] == 'success' ? '✅' : ($task['status'] == 'failed' ? '❌' : '⏳');
                echo "{$statusIcon} 第{$task['race_no']}场: {$task['status']}";
                if ($task['retry_count'] > 0) echo " (重试{$task['retry_count']}次)";
                if ($task['error_message']) echo " - {$task['error_message']}";
                echo "\n";
            }
            break;
            
        case 'retry':
            echo "重试失败的任务...\n";
            $poster->retryFailed($date, $venue);
            break;
            
        default:
            echo "用法: \n";
            echo "  php HKJC_meetingposter.php?action=generate&date=2026-04-26&venue=ST\n";
            echo "  php HKJC_meetingposter.php?action=status&date=2026-04-26&venue=ST\n";
            echo "  php HKJC_meetingposter.php?action=retry&date=2026-04-26&venue=ST\n";
            break;
    }
}
?>