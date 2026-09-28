<?php
// test_final.php - 最终测试脚本
require_once 'HKJCRacingDataManager.php';

$dbConfig = [
    'host' => 'localhost',
    'name' => 'fengrmkw_moosay',
    'user' => 'fengrmkw_melvin', // 替换为您的真实信息
    'pass' => 'YOUR_DB_PASSWORD'
];

try {
    $manager = new HKJCRacingDataManager(
        $dbConfig['host'],
        $dbConfig['name'],
        $dbConfig['user'],
        $dbConfig['pass']
    );
    
    echo "=== 测试1: 获取活跃赛马日 ===\n";
    $active = $manager->getActiveMeetingsOnly();
    print_r($active);
    
    if (!empty($active)) {
        echo "\n=== 测试2: 获取用于跳转的当前赛马日 ===\n";
        $current = $manager->getCurrentActiveMeeting();
        print_r($current);
        
        echo "\n=== 测试3: 获取并缓存特定日期的完整赛马数据 ===\n";
        // 这里会触发API调用并存入数据库
        $fullData = $manager->getRacingData($current['date'], $current['venueCode'], null, true);
        if ($fullData && empty($fullData['errors'])) {
            echo "数据获取成功！\n";
            if (isset($fullData['data']['raceMeetings'][0])) {
                $meeting = $fullData['data']['raceMeetings'][0];
                echo "赛事日: " . $meeting['venueCode'] . " - " . $meeting['date'] . "\n";
                echo "总场次: " . ($meeting['totalNumberOfRace'] ?? 'N/A') . "\n";
            }
        } else {
            echo "数据获取失败，请检查错误日志。\n";
        }
    }
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}