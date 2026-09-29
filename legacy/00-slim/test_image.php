<?php
// test_image.php - 放在 00/ 目录
include_once "lib/constants.php";

$date = '2026-05-06';
$venueCode = 'ST';

$pdo = new PDO(
    "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
    $dbConfig['user'],
    $dbConfig['pass']
);

// 获取第一场时间
$stmt = $pdo->prepare("
    SELECT r.post_time 
    FROM hkracing_races r
    JOIN hkracing_meetings m ON r.meeting_id = m.id
    WHERE m.date = ? AND m.venue_code = ?
    ORDER BY r.race_no ASC
    LIMIT 1
");
$stmt->execute([$date, $venueCode]);
$firstRace = $stmt->fetch(PDO::FETCH_ASSOC);

$sessionType = "日";
if ($firstRace && !empty($firstRace['post_time'])) {
    $hour = intval(date('H', strtotime($firstRace['post_time'])));
    if ($hour >= 18 || $hour < 6) {
        $sessionType = "夜";
    }
}

echo "赛马类型: {$sessionType}赛\n";

// 测试图片生成
function findChineseFont() {
    $fontPaths = [
        __DIR__ . '/fonts/NotoSansTC-VariableFont_wght.ttf',
        __DIR__ . '/fonts/wqy-microhei.ttc',
    ];
    foreach ($fontPaths as $path) {
        if (file_exists($path)) {
            echo "找到字体: {$path}\n";
            return $path;
        }
    }
    echo "未找到中文字体\n";
    return null;
}

$fontPath = findChineseFont();

// 创建测试图片
$width = 1200;
$height = 630;
$image = imagecreatetruecolor($width, $height);
$white = imagecolorallocate($image, 255, 255, 255);
$gold = imagecolorallocate($image, 255, 215, 0);
$black = imagecolorallocate($image, 0, 0, 0);

// 填充白色背景
imagefilledrectangle($image, 0, 0, $width, $height, $white);

// 写文字测试
if ($fontPath) {
    imagettftext($image, 70, 0, 50, 150, $black, $fontPath, "{$sessionType}赛");
    imagettftext($image, 50, 0, 50, 250, $black, $fontPath, "沙田賽馬日");
} else {
    imagestring($image, 5, 50, 150, "{$sessionType}赛", $black);
    imagestring($image, 5, 50, 250, "Sha Tin Race Day", $black);
}

// 保存图片
$tempFile = __DIR__ . '/test_output.png';
imagepng($image, $tempFile);
imagedestroy($image);

echo "测试图片已保存: {$tempFile}\n";
echo "访问: https://buycarl.com/00/test_output.png\n";