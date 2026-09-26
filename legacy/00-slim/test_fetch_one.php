<?php
// test_fetch_one.php - 测试抓取单匹马

require_once 'HKJCHorseHistoryManager.php';

$dbConfig = [
    'host' => 'localhost',
    'name' => 'fengrmkw_moosay',
    'user' => 'fengrmkw_melvin',
    'pass' => 'YOUR_DB_PASSWORD'
];

$horseManager = new HKJCHorseHistoryManager(
    $dbConfig['host'],
    $dbConfig['name'],
    $dbConfig['user'],
    $dbConfig['pass']
);

// 抓取 J071
$data = $horseManager->fetchHorseHistory('J071');

if (!isset($data['error'])) {
    echo "抓取成功！\n";
    echo "马名: " . $data['info']['name_ch'] . "\n";
    echo "战绩数量: " . count($data['performances']) . "\n";
    
    // 保存到数据库
    $horseManager->saveHorseHistory('J071', $data);
    echo "已保存到数据库\n";
    
    // 显示前3场战绩
    echo "\n最近3场战绩:\n";
    $count = 0;
    foreach ($data['performances'] as $perf) {
        if ($count++ >= 3) break;
        echo "  {$perf['race_date']} {$perf['venue_code']} {$perf['distance']}m: 第{$perf['finishing_position']}名\n";
    }
} else {
    echo "错误: " . $data['error'] . "\n";
}
?>