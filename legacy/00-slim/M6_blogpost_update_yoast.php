<?php
/**
 * Mark Six Yoast Meta Update Script
 * Called after blog post creation to set SEO metadata
 */

// Load WordPress
require_once('/home/fengrmkw/buycarl.com/wp-load.php');

// Set response to JSON
header('Content-Type: application/json');

// Get parameters
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
$token = isset($_GET['token']) ? $_GET['token'] : '';
$draw_number = isset($_GET['draw_number']) ? $_GET['draw_number'] : '';
$draw_date = isset($_GET['draw_date']) ? $_GET['draw_date'] : '';
$focus_keyphrase = isset($_GET['focus_keyphrase']) ? urldecode($_GET['focus_keyphrase']) : '';
$seo_title = isset($_GET['seo_title']) ? urldecode($_GET['seo_title']) : '';
$seo_description = isset($_GET['seo_description']) ? urldecode($_GET['seo_description']) : '';

// Simple token validation
$secret_token = 'your_secret_token_2026';  // Match with M6_blogpost.php

if ($token !== $secret_token || $post_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid token or post_id']);
    exit;
}

// Generate default SEO if not provided
if (empty($focus_keyphrase)) {
    $focus_keyphrase = "六合彩第{$draw_number}期預測";
}
if (empty($seo_title)) {
    $seo_title = "六合彩第{$draw_number}期預測 | 13種方法+共識決 | 每欄獨立分析";
}
if (empty($seo_description)) {
    $seo_description = "六合彩第{$draw_number}期預測分析。使用13種統計方法（加權移動平均、熱門號碼、馬可夫鏈等）+共識決，每欄獨立預測。提供信心度評分，助您參考選號。";
}

// Update slug if needed
if (!empty($draw_number)) {
    $slug = "mark-six-prediction-draw-{$draw_number}";
    wp_update_post(['ID' => $post_id, 'post_name' => $slug]);
}

// Update Yoast meta data
update_post_meta($post_id, '_yoast_wpseo_focuskw', $focus_keyphrase);
update_post_meta($post_id, '_yoast_wpseo_title', $seo_title);
update_post_meta($post_id, '_yoast_wpseo_metadesc', $seo_description);

// Save draw info to post meta
update_post_meta($post_id, '_m6_draw_number', $draw_number);
update_post_meta($post_id, '_m6_draw_date', $draw_date);
update_post_meta($post_id, '_m6_prediction_date', date('Y-m-d H:i:s'));

echo json_encode([
    'success' => true,
    'post_id' => $post_id,
    'seo_title' => $seo_title,
    'seo_description' => $seo_description,
    'focus_keyphrase' => $focus_keyphrase,
    'slug' => $slug ?? null
]);
?>