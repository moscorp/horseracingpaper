<?php
// test_api_debug.php - 放在 00/ 目录

require_once 'HKJC_graphql_client.php';

$client = new GraphQLClient();
$data = $client->getRacingData('2026-05-09', 'ST');
// $data1 = $client->getActiveMeetings();
print_r($data);

echo "=== API 返回数据结构 ===\n";

if (empty($data)) {
    echo "❌ 数据为空\n";
    exit;
}

if (empty($data['raceMeetings'])) {
    echo "❌ 没有 raceMeetings\n";
    print_r(array_keys($data));
    exit;
}

$meeting = $data['raceMeetings'][0];
echo "✅ 找到 raceMeetings\n";
echo "场馆: " . ($meeting['venueCode'] ?? '未知') . "\n";
echo "日期: " . ($meeting['date'] ?? '未知') . "\n";
echo "总场次: " . ($meeting['totalNumberOfRace'] ?? '未知') . "\n";

// 检查 pmPools
echo "\n--- pmPools 检查 ---\n";
if (empty($meeting['pmPools'])) {
    echo "❌ pmPools 为空\n";
    
    // 看看有哪些顶层字段
    echo "顶层字段: " . implode(', ', array_keys($meeting)) . "\n";
} else {
    echo "✅ pmPools 存在，共 " . count($meeting['pmPools']) . " 个池\n";
    foreach ($meeting['pmPools'] as $pool) {
        echo "  - oddsType: " . ($pool['oddsType'] ?? '未知') . "\n";
        echo "    oddsNodes 数量: " . (count($pool['oddsNodes'] ?? []) > 0 ? count($pool['oddsNodes']) : '空') . "\n";
    }
}

// 检查 races 结构
echo "\n--- races 检查 ---\n";
if (empty($meeting['races'])) {
    echo "❌ races 为空\n";
} else {
    echo "✅ 共 " . count($meeting['races']) . " 场\n";
    $firstRace = $meeting['races'][0];
    echo "第1场: no=" . ($firstRace['no'] ?? '未知') . "\n";
    echo "runners 数量: " . count($firstRace['runners'] ?? []) . "\n";
}

// 输出完整结构的前 1000 字符（用于调试）
echo "\n--- 原始数据预览 ---\n";
echo substr(json_encode($data, JSON_UNESCAPED_UNICODE), 0, 2000) . "...\n";