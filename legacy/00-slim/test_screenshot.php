<?php
// test_screenshot.php - 测试单场截图

require_once 'lib/constants.php';

class ScreenshotTester {
    private $imageDir = 'image/horseracing/';
    private $baseUrl;
    
    public function __construct() {
        $this->baseUrl = "https://" . $_SERVER['HTTP_HOST'] . "/00";
        
        if (!file_exists($this->imageDir)) {
            mkdir($this->imageDir, 0777, true);
        }
    }
    
    public function testCapture($date, $venue, $raceNo) {
        $shareUrl = $this->baseUrl . "/race_share.php?date={$date}&venue={$venue}&race_no={$raceNo}";
        $filename = $this->imageDir . "test_{$venue}_{$date}_R{$raceNo}.png";
        
        echo "URL: {$shareUrl}\n";
        echo "输出: {$filename}\n\n";
        
        return $this->captureWithGooglePagespeed($shareUrl, $filename);
    }
    
    private function captureWithGooglePagespeed($url, $outputPath) {
        $apiUrl = "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=" . urlencode($url) . "&screenshot=true";
        
        echo "调用 API...\n";
        
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
        
        echo "HTTP Code: {$httpCode}\n";
        
        if ($httpCode == 429) {
            echo "频率限制！等待 60 秒后重试...\n";
            sleep(60);
            return $this->captureWithGooglePagespeed($url, $outputPath);
        }
        
        if ($httpCode != 200 || !$response) {
            echo "请求失败\n";
            return false;
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
            echo "未找到截图数据\n";
            return false;
        }
        
        // 解码
        $decodedData = str_replace('_', '/', $screenshotData);
        $decodedData = str_replace('-', '+', $decodedData);
        if (strpos($decodedData, 'base64,') !== false) {
            $decodedData = preg_replace('/^data:image\/\w+;base64,/', '', $decodedData);
        }
        
        $imageBinary = base64_decode($decodedData);
        
        if (!$imageBinary || strlen($imageBinary) < 1000) {
            echo "图片数据无效\n";
            return false;
        }
        
        file_put_contents($outputPath, $imageBinary);
        echo "成功！图片大小: " . round(strlen($imageBinary)/1024, 1) . " KB\n";
        echo "保存路径: {$outputPath}\n";
        
        return true;
    }
}

// 运行测试
$tester = new ScreenshotTester();
$date = '2026-04-26';
$venue = 'ST';
$raceNo = 1;

echo "========== 测试截图 ==========\n";
echo "日期: {$date}\n";
echo "场地: {$venue}\n";
echo "场次: {$raceNo}\n\n";

$success = $tester->testCapture($date, $venue, $raceNo);

if ($success) {
    echo "\n✅ 截图成功！\n";
} else {
    echo "\n❌ 截图失败\n";
}
?>