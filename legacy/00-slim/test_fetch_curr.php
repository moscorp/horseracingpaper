<?php
// force_fetch_curr.php - 强制抓取当前赔率

require_once 'HKJCOddsManager.php';

$dbConfig = [
    'host' => 'localhost',
    'name' => 'fengrmkw_moosay',
    'user' => 'fengrmkw_melvin',
    'pass' => 'YOUR_DB_PASSWORD'
];

$oddsManager = new HKJCOddsManager(
    $dbConfig['host'],
    $dbConfig['name'],
    $dbConfig['user'],
    $dbConfig['pass']
);

// 强制抓取第1场到第11场的 Curr 赔率
for ($raceNo = 1; $raceNo <= 11; $raceNo++) {
    echo "抓取 ST 第{$raceNo}场 Curr 赔率... ";
    $result = $oddsManager->fetchOdds('2026-04-06', 'ST', $raceNo, 'Curr', true);
    if (isset($result['success']) && $result['success']) {
        echo "成功 (记录ID: {$result['odds_data_id']}, {$result['horse_count']}匹)\n";
    } else {
        echo "失败: " . ($result['message'] ?? '未知错误') . "\n";
    }
    sleep(1);
}
?>