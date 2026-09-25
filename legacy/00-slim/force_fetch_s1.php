<?php
// force_fetch_s1_odds.php
require_once 'HKJCOddsManager.php';

$dbConfig = [
    'host' => 'localhost',
    'name' => 'fengrmkw_moosay',
    'user' => 'fengrmkw_melvin',
    'pass' => 'mopass.24626388'
];

$oddsManager = new HKJCOddsManager(
    $dbConfig['host'],
    $dbConfig['name'],
    $dbConfig['user'],
    $dbConfig['pass']
);

// 强制抓取 S1 所有场次的 Pre 赔率
for ($raceNo = 1; $raceNo <= 10; $raceNo++) {
    echo "强制抓取 S1 第{$raceNo}场 Pre 赔率... ";
    $result = $oddsManager->fetchOdds('2026-04-11', 'S1', $raceNo, 'Pre', true);
    if (isset($result['success']) && $result['success']) {
        echo "✓ 成功 (记录ID: {$result['odds_data_id']})\n";
    } else {
        echo "✗ 失败: " . ($result['message'] ?? '未知') . "\n";
    }
    sleep(1);
}
?>