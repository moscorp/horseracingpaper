<?php
include_once ("lib/constants.php");

$pdo = new PDO(
    "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
    $dbConfig['user'],
    $dbConfig['pass']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 測試獲取單一馬匹資料
$horseCode = 'G312';

echo "測試馬匹: {$horseCode}\n\n";

// 測試 getHorseStats
$stmt = $pdo->prepare("
    SELECT 
        total_starts as starts,
        total_wins as wins,
        ROUND(IFNULL(total_wins, 0) / NULLIF(total_starts, 0) * 100, 1) as win_rate
    FROM hkracing_horses
    WHERE horse_code = ?
");
$stmt->execute([$horseCode]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
echo "馬匹統計: " . print_r($stats, true) . "\n";

// 測試 getRecentRuns
$sql = "
    SELECT finishing_position, barrier_draw as draw, class, race_date
    FROM hkracing_horse_performances
    WHERE horse_code = ?
    ORDER BY race_date DESC
    LIMIT 3
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$horseCode]);
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "最近出賽: " . print_r($recent, true) . "\n";

echo "測試完成\n";
?>