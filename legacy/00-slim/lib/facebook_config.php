<?php
// facebook_config.php - Facebook API配置

define('FACEBOOK_APP_ID', '1692263438629251');
define('FACEBOOK_APP_SECRET', '86f2e01929c0cc57b7928dbb9b2b0673');
define('FACEBOOK_PAGE_ACCESS_TOKEN', '1692263438629251|bZu8lMDnwCOQ41SpfT2OfP1N7cY');
define('FACEBOOK_PAGE_ID', 'YOUR_PAGE_ID');

// 获取长期 Token 的方法
function getLongLivedToken($shortLivedToken) {
    $url = "https://graph.facebook.com/v18.0/oauth/access_token";
    $url .= "?grant_type=fb_exchange_token";
    $url .= "&client_id=" . FACEBOOK_APP_ID;
    $url .= "&client_secret=" . FACEBOOK_APP_SECRET;
    $url .= "&fb_exchange_token=" . $shortLivedToken;
    
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}
?>