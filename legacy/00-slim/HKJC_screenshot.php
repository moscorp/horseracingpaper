// HKJC_screenshot.php - 使用 Microlink 截图
class HKJCScreenshot {
    private $apiUrl = 'https://api.microlink.io/';
    
    public function capture($url, $outputPath) {
        $params = [
            'url' => $url,
            'screenshot' => true,
            'viewport' => ['width' => 1200, 'height' => 800],
            'fullPage' => false,
            'waitUntil' => 'networkidle2',
            'element' => '.container'  // 只截取内容区域
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: Mozilla/5.0'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['data']['screenshot']['url'])) {
                // 下载图片
                $imgData = file_get_contents($data['data']['screenshot']['url']);
                file_put_contents($outputPath, $imgData);
                return true;
            }
        }
        return false;
    }
}