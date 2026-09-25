<?php
// HKJC_facebook_poster.php - Facebook 海报发布器

require_once 'HKJC_meetingposter.php';

class HKJCFacebookPoster {
    private $pdo;
    private $accessToken;
    private $pageId;
    
    public function __construct() {
        global $dbConfig;
        $this->pdo = new PDO(
            "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
            $dbConfig['user'],
            $dbConfig['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // 请替换为你的 Facebook 凭证
        $this->accessToken = 'YOUR_FACEBOOK_ACCESS_TOKEN';
        $this->pageId = 'YOUR_FACEBOOK_PAGE_ID';
    }
    
    /**
     * 发布单日的所有海报（一次帖子多张图片）
     */
    public function postDailyRacePosters($date, $venue) {
        // 获取未发布的 posters
        $stmt = $this->pdo->prepare("
            SELECT id, image_path, race_no
            FROM hkracing_poster_log
            WHERE race_date = ? AND venue_code = ? 
              AND status = 'generated'
              AND (facebook_post_id IS NULL OR facebook_post_id = '')
            ORDER BY race_no
        ");
        $stmt->execute([$date, $venue]);
        $posters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($posters)) {
            return ['success' => false, 'message' => '没有可发布的图片'];
        }
        
        // 构建帖子内容
        $venueName = $venue == 'ST' ? '沙田' : '跑馬地';
        $message = "🏇 {$venueName} 賽馬日 ({$date})\n\n";
        $message .= "📊 各場賽事分析圖表\n";
        $message .= "數據僅供參考，投注請理性！\n\n";
        $message .= "#香港賽馬 #賽馬分析 #HorseRacing";
        
        // 上传所有图片到 Facebook
        $mediaIds = [];
        foreach ($posters as $poster) {
            $mediaId = $this->uploadPhoto($poster['image_path']);
            if ($mediaId) {
                $mediaIds[] = ['media_fbid' => $mediaId];
            }
        }
        
        if (empty($mediaIds)) {
            return ['success' => false, 'message' => '图片上传失败'];
        }
        
        // 发布包含多张图片的帖子
        $postId = $this->createAlbumPost($message, $mediaIds);
        
        if ($postId) {
            // 更新数据库
            $this->updatePosterStatus($posters, $postId);
            return ['success' => true, 'post_id' => $postId, 'image_count' => count($mediaIds)];
        }
        
        return ['success' => false, 'message' => '发布失败'];
    }
    
    /**
     * 上传单张图片到 Facebook
     */
    private function uploadPhoto($imagePath) {
        $url = "https://graph.facebook.com/v18.0/{$this->pageId}/photos";
        
        $postFields = [
            'source' => new CURLFile($imagePath),
            'access_token' => $this->accessToken,
            'published' => false  // 先不上架，用于创建相册
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        return $data['id'] ?? null;
    }
    
    /**
     * 创建包含多张图片的帖子
     */
    private function createAlbumPost($message, $mediaIds) {
        $url = "https://graph.facebook.com/v18.0/{$this->pageId}/feed";
        
        $postFields = [
            'message' => $message,
            'attached_media' => json_encode($mediaIds),
            'access_token' => $this->accessToken
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        return $data['id'] ?? null;
    }
    
    /**
     * 更新数据库中的发布状态
     */
    private function updatePosterStatus($posters, $postId) {
        $ids = array_column($posters, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        $stmt = $this->pdo->prepare("
            UPDATE hkracing_poster_log 
            SET status = 'posted', 
                facebook_post_id = ?,
                posted_at = NOW()
            WHERE id IN ({$placeholders})
        ");
        
        $params = array_merge([$postId], $ids);
        $stmt->execute($params);
    }
}

// 配置文件和 Cron 入口
if (basename($_SERVER['SCRIPT_FILENAME']) == 'HKJC_facebook_poster.php') {
    $action = $_GET['action'] ?? '';
    
    if ($action == 'cron_daily') {
        // 获取需要发布的赛马日（今天或未来几天）
        $today = date('Y-m-d');
        
        $fb = new HKJCFacebookPoster();
        $result = $fb->postDailyRacePosters($today, 'ST');
        echo json_encode($result);
    }
}
?>