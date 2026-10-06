<?php
// test_batch.php - 简化测试

require_once 'HKJCHorseHistoryBatchManager.php';

$dbConfig = [
    'host' => 'localhost',
    'name' => 'fengrmkw_moosay',
    'user' => 'fengrmkw_melvin',
    'pass' => 'YOUR_DB_PASSWORD'
];

echo "开始测试...\n";

try {
    $batchManager = new HKJCHorseHistoryBatchManager(
        $dbConfig['host'],
        $dbConfig['name'],
        $dbConfig['user'],
        $dbConfig['pass']
    );
    
    // 只处理2匹，不收集新马
    echo "处理2匹马...\n";
    $result = $batchManager->processBatch();
    
    print_r($result);
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    echo "文件: " . $e->getFile() . "\n";
    echo "行数: " . $e->getLine() . "\n";
}
?>