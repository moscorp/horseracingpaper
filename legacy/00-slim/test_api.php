<?php
// test_basedata.php - 直接测试您的 basedata 函数

// require_once 'path/to/your/basedata/file.php'; // 引入包含 basedata 函数的文件
include_once ("lib/func_basedata.php");

$result = basedata('2026-04-15', 'HV', null);

if ($result && !isset($result['errors'])) {
    echo "成功!\n";
    if (!empty($result['data']['raceMeetings'][0])) {
        $meeting = $result['data']['raceMeetings'][0];
        echo "赛事日: {$meeting['venueCode']} - {$meeting['date']}\n";
        echo "总场次: {$meeting['totalNumberOfRace']}\n";
        print_r($meeting);
    }
} else {
    echo "失败: " . json_encode($result['errors'] ?? ['未知错误']) . "\n";
}