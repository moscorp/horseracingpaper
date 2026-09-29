<?php
/**
 * WordPress 内部 Yoast 元数据更新脚本 HKJC_blogpost_update_yoast.php
 * 通过 REST API 调用，接收 post_id 并更新 Yoast 数据
 */

// 加载 WordPress
// require_once(dirname(__FILE__) . '/wp-load.php');
require_once('/home/fengrmkw/buycarl.com/wp-load.php');

// 设置响应为 JSON
header('Content-Type: application/json');

// 获取参数
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
$token = isset($_GET['token']) ? $_GET['token'] : '';
$raceDate = isset($_GET['date']) ? $_GET['date'] : '';
$venueCode = isset($_GET['venue']) ? $_GET['venue'] : '';

// 简单验证
$secret_token = 'your_secret_token_2026';

if ($token !== $secret_token || $post_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid token or post_id']);
    exit;
}

// 如果没有通过参数传递，尝试从 post meta 读取（备用）
if (empty($raceDate) || empty($venueCode)) {
    $raceDate = get_post_meta($post_id, '_racing_date', true);
    $venueCode = get_post_meta($post_id, '_racing_venue_code', true);
}

if (empty($raceDate) || empty($venueCode)) {
    echo json_encode(['success' => false, 'error' => 'Missing venue or date - post_id:' . $post_id]);
    exit;
}

// 生成 SEO 数据
$venueName = $venueCode == 'ST' ? '沙田' : '跑馬地';
$dateFormatted = date('Y年m月d日', strtotime($raceDate));

// ✅ 焦点关键词（单个）
$focusKeyphrase = "{$dateFormatted} {$venueName}賽馬分析";

// ✅ SEO 标题（包含关键词 + 品牌）
$seoTitle = "{$dateFormatted} {$venueName}賽馬分析 | 精選馬匹優先順序";

// ✅ 元描述（150-160字符，自然包含关键词）
$seoDescription = "{$dateFormatted} {$venueName}賽馬分析。基於歷史數據提供每場賽事精選馬匹優先順序，包括檔位分析、同程勝率、近況狀態等，助您掌握賽馬勝算。";

// ✅ 更新 slug（包含关键词）
$slug = sanitize_title("{$dateSlug}-{$venueCode}-racing-analysis");
wp_update_post(['ID' => $post_id, 'post_name' => $slug]);

// 更新 Yoast 元数据
update_post_meta($post_id, '_yoast_wpseo_focuskw', $focusKeyphrase);
update_post_meta($post_id, '_yoast_wpseo_title', $seoTitle);
update_post_meta($post_id, '_yoast_wpseo_metadesc', $seoDescription);

// 同时保存日期和场地到 post meta（供将来使用）
update_post_meta($post_id, '_racing_date', $raceDate);
update_post_meta($post_id, '_racing_venue_code', $venueCode);

echo json_encode([
    'success' => true,
    'post_id' => $post_id,
    'seo_title' => $seoTitle,
    'seo_description' => $seoDescription,
    'focus_keyphrase' => $focusKeyphrase,
    'slug' => $slug
]);