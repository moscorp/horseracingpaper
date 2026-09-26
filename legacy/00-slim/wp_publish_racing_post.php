<?php
/**
 * WordPress 内部发布脚本
 * 路径: /home/fengrmkw/buycarl.com/wp_publish_racing_post.php
 */

// 加载 WordPress
// require_once(dirname(__FILE__) . '/wp-load.php');
require_once('/home/fengrmkw/buycarl.com/wp-load.php');

// 设置时区
date_default_timezone_set('Asia/Hong_Kong');

// 获取参数
$date = $_GET['date'] ?? '';
$venue = $_GET['venue'] ?? '';
$action = $_GET['action'] ?? '';

if ($action !== 'publish') {
    echo "Usage: wp_publish_racing_post.php?action=publish&date=2026-05-06&venue=ST\n";
    exit;
}

// 连接赛马数据库
include_once('lib/constants.php');

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("数据库连接失败: " . $e->getMessage());
}

// 获取赛事数据
$stmt = $pdo->prepare("
    SELECT r.id as race_id, r.race_no, s.runner_no, s.total_score, s.win_odds_snapshot,
           ru.barrier_draw_number as draw, ru.name_ch as horse_name
    FROM hkracing_v2_score s
    JOIN hkracing_races r ON s.race_id = r.id
    JOIN hkracing_meetings m ON r.meeting_id = m.id
    JOIN hkracing_runners ru ON ru.race_id = r.id AND ru.runner_no = s.runner_no
    WHERE m.date = ? AND m.venue_code = ? AND s.score_version = 1
    ORDER BY r.race_no, s.total_score DESC
");
$stmt->execute([$date, $venue]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 按 race_no 分组，取每组第一名
$topPicks = [];
foreach ($results as $row) {
    $raceNo = $row['race_no'];
    if (!isset($topPicks[$raceNo])) {
        $topPicks[$raceNo] = $row;
    }
}

if (empty($topPicks)) {
    die("没有找到 priority 数据\n");
}

// 生成内容
$venueName = $venue == 'ST' ? '沙田' : '跑馬地';
$dateFormatted = date('Y年m月d日', strtotime($date));
$weekday = ['日', '一', '二', '三', '四', '五', '六'][date('w', strtotime($date))];
$raceCount = count($topPicks);

// 构建 SEO 数据
$seoTitle = "{$dateFormatted} {$venueName} 賽馬日分析 - 精選馬匹優先順序";
$seoDescription = "{$dateFormatted} {$venueName} 賽馬日分析。基於歷史數據分析，提供每場賽事精選馬匹優先順序，助您掌握賽馬勝算。";
$seoKeywords = "{$venueName}賽馬分析, 香港賽馬, 賽馬貼士, 馬匹優先順序";

// 构建文章内容
$content = '<div style="max-width:1000px; margin:0 auto;">';
$content .= '<div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: white; padding: 30px; text-align: center; border-radius: 16px;">';
$content .= '<h1 style="color: #FFD700;">🏇 ' . $venueName . ' 賽馬日分析</h1>';
$content .= '<p>' . $dateFormatted . '（星期' . $weekday . '） | 精選 ' . $raceCount . ' 場</p>';
$content .= '</div>';

$content .= '<table style="width:100%; border-collapse:collapse; margin-top:20px;">';
$content .= '<tr style="background:#667eea; color:white;"><th>場次</th><th>馬號</th><th>馬名</th><th>檔位</th><th>評分</th><th>賠率</th></tr>';

foreach ($topPicks as $raceNo => $pick) {
    $content .= '<tr style="border-bottom:1px solid #ddd;">';
    $content .= '<td style="padding:10px;">第' . $raceNo . '場</td>';
    $content .= '<td style="padding:10px; font-weight:bold;">' . $pick['runner_no'] . '</td>';
    $content .= '<td style="padding:10px;">' . $pick['horse_name'] . '</td>';
    $content .= '<td style="padding:10px;">' . $pick['draw'] . '</td>';
    $content .= '<td style="padding:10px;">' . round($pick['total_score']) . '</td>';
    $content .= '<td style="padding:10px;">' . ($pick['win_odds_snapshot'] ?? '-') . '</td>';
    $content .= '</tr>';
}

$content .= '</table>';
$content .= '<p style="text-align:center; font-size:12px; color:#999; margin-top:20px;">⚠️ 僅供參考 | ' . date('Y-m-d H:i') . '</p>';
$content .= '</div>';

// 创建 WordPress 文章
$postData = [
    'post_title'    => $seoTitle,
    'post_content'  => $content,
    'post_excerpt'  => "🏆 賽日精選：" . implode(' | ', array_map(function($p) { return "R{$p['race_no']}: {$p['runner_no']}號"; }, $topPicks)),
    'post_status'   => 'publish',
    'post_author'   => 1,  // 管理员 ID
    'post_category' => [get_cat_ID('Racing News')],
    'meta_input'    => [
        '_yoast_wpseo_title' => $seoTitle,
        '_yoast_wpseo_metadesc' => $seoDescription,
        '_yoast_wpseo_focuskw' => $seoKeywords,
    ]
];

// 插入文章
$postId = wp_insert_post($postData, true);

if (is_wp_error($postId)) {
    echo "发布失败: " . $postId->get_error_message() . "\n";
} else {
    echo "✅ 文章已发布！ID: {$postId}\n";
    echo "   SEO 标题: {$seoTitle}\n";
    echo "   URL: https://buycarl.com/?p={$postId}\n";
}

$pdo = null;