<?php
// HKJC_meetingposter.php - 使用截图 API 生成海报

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
        
        // 设置网站根 URL
        $this->baseUrl = "https://" . $_SERVER['HTTP_HOST'] . "/00";
        
        if (!file_exists($this->imageDir)) {
            mkdir($this->imageDir, 0777, true);
        }
    }
    
    public function generateRacePoster($date, $venue, $raceNo) {
        // 1. 生成分享页面的 URL
        $shareUrl = $this->baseUrl . "/HKJC_race_share.php?date={$date}&venue={$venue}&race_no={$raceNo}";
        
        // 2. 使用 Microlink 截图
        $filename = $this->imageDir . "{$venue}_{$date}_R{$raceNo}_" . date('Ymd_His') . ".png";
        
        $success = $this->captureWithMicrolink($shareUrl, $filename);
        
        if ($success) {
            $this->logPoster($date, $venue, $raceNo, $filename);
            return $filename;
        }
        
        return false;
    }
    
    private function captureWithMicrolink($url, $outputPath) {
        $apiUrl = 'https://api.microlink.io/';
        
        $params = [
            'url' => $url,
            'screenshot' => true,
            'viewport' => ['width' => 1200, 'height' => 800],
            'fullPage' => true,
            'waitUntil' => 'networkidle2',
            'quality' => 90
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['data']['screenshot']['url'])) {
                $imgData = file_get_contents($data['data']['screenshot']['url']);
                if ($imgData && file_put_contents($outputPath, $imgData)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    private function logPoster($date, $venue, $raceNo, $filename) {
        $stmt = $this->pdo->prepare("
            INSERT INTO hkracing_poster_log (race_date, venue_code, race_no, image_path, image_filename, status)
            VALUES (?, ?, ?, ?, ?, 'generated')
            ON DUPLICATE KEY UPDATE
            image_path = VALUES(image_path),
            image_filename = VALUES(image_filename),
            status = 'generated'
        ");
        $stmt->execute([$date, $venue, $raceNo, $filename, basename($filename)]);
    }
    
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
        
        $generated = [];
        foreach ($races as $race) {
            echo "生成第 {$race['race_no']} 場...\n";
            $filename = $this->generateRacePoster($date, $venue, $race['race_no']);
            if ($filename) {
                $generated[] = $filename;
                echo "✓ 成功: " . basename($filename) . "\n";
            } else {
                echo "✗ 失敗\n";
            }
            sleep(2); // 避免请求过快
        }
        
        return $generated;
    }
}

// 运行
if (basename($_SERVER['SCRIPT_FILENAME']) == 'HKJC_meetingposter.php') {
    $poster = new HKJCRacingPoster();
    $files = $poster->generateAllPosters('2026-04-26', 'ST');
    echo "\n完成! 生成 " . count($files) . " 张图片\n";
}
?>