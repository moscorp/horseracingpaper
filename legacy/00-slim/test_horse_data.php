<?php
// test_horse_data.php
include_once ("lib/constants.php");
 
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
$horseCode = 'G312';

echo "Testing horse: $horseCode\n\n";

// Test 1: Get horse stats
$stmt = $pdo->prepare("SELECT * FROM hkracing_horses WHERE horse_code = ?");
$stmt->execute([$horseCode]);
$horse = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Horse stats: " . json_encode($horse) . "\n\n";

// Test 2: Get recent performances
$stmt = $pdo->prepare("SELECT * FROM hkracing_horse_performances WHERE horse_code = ? ORDER BY race_date DESC LIMIT 3");
$stmt->execute([$horseCode]);
$performances = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Recent performances: " . count($performances) . " records\n";
foreach ($performances as $perf) {
    echo "  - Date: {$perf['race_date']}, Position: {$perf['finishing_position']}, Class: {$perf['class']}\n";
}

