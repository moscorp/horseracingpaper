// 创建一个测试文件 test_odds_api.php
<?php
require_once 'lib/func_basedata.php';

$date = '2026-05-17';
$venue = 'ST';
$raceNo = 1;
$type = 'WIN';

echo "Testing API for {$date} {$venue} Race {$raceNo}...\n";

$data = racingdata($date, $venue, $raceNo, $type);

if ($data) {
    echo "API Response:\n";
    print_r($data);
} else {
    echo "No response from API\n";
}
?>