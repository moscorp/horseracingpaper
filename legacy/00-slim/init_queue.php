<?php
// init_queue.php - 初始化队列，从 runners 表收集所有香港马

require_once 'HKJCHorseHistoryBatchManager.php';

$dbConfig = [
    'host' => 'localhost',
    'name' => 'fengrmkw_moosay',
    'user' => 'fengrmkw_melvin',
    'pass' => 'mopass.24626388'
];

echo "[" . date('Y-m-d H:i:s') . "] 开始初始化队列...\n";

try {
    $batchManager = new HKJCHorseHistoryBatchManager(
        $dbConfig['host'],
        $dbConfig['name'],
        $dbConfig['user'],
        $dbConfig['pass']
    );
    
    // 清空现有队列
    echo "清空现有队列...\n";
    $db = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['pass']
    );
    $db->exec("TRUNCATE TABLE hkracing_pending_horses");
    
    // 重新收集
    echo "收集马匹...\n";
    $count = $batchManager->collectHorsesFromRunners();
    
    echo "已添加 {$count} 匹香港马到队列\n";
    
    // 显示统计
    $stats = $batchManager->getQueueStats();
    echo "\n队列统计:\n";
    echo "  待处理: {$stats['pending_count']}\n";
    echo "  香港马总数: {$stats['total_hk_horses']}\n";
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}

echo "[" . date('Y-m-d H:i:s') . "] 初始化完成\n";
?>