<?php
// test_racingdata.php - 测试 racingdata 函数

require_once 'lib/func_basedata.php';

$date = '2026-05-09';
$venueCode = 'ST';
$raceNo = 1;
$type = 'Curr';

echo "=== 测试 racingdata 函数 ===\n";
echo "日期: $date, 场地: $venueCode, 场次: $raceNo, 类型: $type\n\n";

$data = racingdata($date, $venueCode, $raceNo, $type);

if (empty($data)) {
    echo "❌ 返回空数据\n";
    exit;
}

if (!empty($data['errors'])) {
    echo "❌ API 错误:\n";
    print_r($data['errors']);
    exit;
}

if (empty($data['data']['raceMeetings'][0]['pmPools'])) {
    echo "❌ pmPools 为空\n";
    echo "完整响应:\n";
    print_r($data);
    exit;
}

echo "✅ 成功获取赔率数据\n";
foreach ($data['data']['raceMeetings'][0]['pmPools'] as $pool) {
    echo "\n--- " . $pool['oddsType'] . " ---\n";
    if (!empty($pool['oddsNodes'])) {
        foreach ($pool['oddsNodes'] as $node) {
            echo "马匹 {$node['combString']}: {$node['oddsValue']}\n";
        }
    } else {
        echo "无 oddsNodes\n";
    }
}
?>