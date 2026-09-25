<?php
// debug_full.php
require_once 'HKJCRacingDataManager.php';

$dbConfig = [
    'host' => 'localhost',
    'name' => 'fengrmkw_moosay',
    'user' => 'fengrmkw_melvin',
    'pass' => 'mopass.24626388'
];

// 模拟 $hkHorseCodes
$hkHorseCodes = [];
for ($i = 0; $i < 252; $i++) {
    $hkHorseCodes[] = 'J' . str_pad($i, 3, '0', STR_PAD_LEFT);
}
$hkHorseCodes = array_values(array_unique($hkHorseCodes));

echo "Total codes: " . count($hkHorseCodes) . "\n";

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // 测试带 252 个参数的查询
    $placeholders = implode(',', array_fill(0, count($hkHorseCodes), '?'));
    $sql = "SELECT horse_code FROM hkracing_horse_performances WHERE horse_code IN ({$placeholders}) LIMIT 1";
    
    echo "SQL length: " . strlen($sql) . "\n";
    echo "Placeholders count: " . substr_count($sql, '?') . "\n";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($hkHorseCodes);
    $result = $stmt->fetchAll();
    
    echo "Query successful!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
?>