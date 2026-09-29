<?php
/**
 * 试闸数据抓取脚本 (修复版)
 * 功能：自动抓取 HKJC 试闸结果，包含练马师、排位、配备等完整字段
 */
// ========== 配置 ==========
$maxDaysToFetch = 5; // 只抓取最近多少天的试闸数据，请按需调整（例如 14, 30, 45）

// ========== 1. 引入 simple_html_dom（带路径回退） ==========
$loaded = false;
$possiblePaths = [
    'simplehtmldom_1_9_1/simple_html_dom.php',
    'simplehtmldom/simple_html_dom.php',
    'inc/simple_html_dom.php',
    __DIR__ . '/simplehtmldom_1_9_1/simple_html_dom.php',
    __DIR__ . '/simplehtmldom/simple_html_dom.php',
];
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        include_once($path);
        $loaded = true;
        break;
    }
}
if (!$loaded) {
    die("致命错误：找不到 simple_html_dom.php 文件，请检查路径。\n");
}

// ========== 2. 数据库连接 ==========
require_once("lib/constants.php");
$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
mysqli_set_charset($mysqli, "utf8mb4");
if (!$mysqli) {
    die('数据库连接失败: ' . mysqli_connect_errno());
}

// ========== 3. 函数定义 ==========
function postRequest($url, $data, $refer = "", $timeout = 15, $header = []) {
    $curlObj = curl_init();
    $ssl = stripos($url, 'https://') === 0;
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_POST => !empty($data),
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_AUTOREFERER => 1,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_HTTPHEADER => array_merge(['Expect:'], $header),
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_REFERER => $refer ?: 'https://racing.hkjc.com/'
    ];
    if ($ssl) {
        $options[CURLOPT_SSL_VERIFYHOST] = false;
        $options[CURLOPT_SSL_VERIFYPEER] = false;
    }
    curl_setopt_array($curlObj, $options);
    $returnData = curl_exec($curlObj);
    curl_close($curlObj);
    return $returnData;
}

function getTrialDatesFromSelector($mysqli) {
    $dates = [];
    // 用一个肯定有数据的日期作为种子页
    $seedDate = '15/05/2026';
    $url = "https://racing.hkjc.com/racing/information/chinese/Horse/BTResult.aspx?Date=" . urlencode($seedDate);
    echo "获取日期列表: $url\n";
    $html = postRequest($url, []);
    if (empty($html)) return $dates;
    $htmlDom = new simple_html_dom();
    $htmlDom->load($html);
    foreach ($htmlDom->find('select#selectId option') as $option) {
        $dateStr = trim($option->plaintext);
        if (preg_match('#\d{2}/\d{2}/\d{4}#', $dateStr)) {
            $dates[] = $dateStr;
        }
    }
    return $dates;
}

function saveTrialDataForDate($mysqli, $dateDMY) {
    $dateTime = DateTime::createFromFormat('d/m/Y', $dateDMY);
    if (!$dateTime) return false;
    $ymd = $dateTime->format('Y-m-d');
    
    // 检查是否已存在数据
    $check = mysqli_query($mysqli, "SELECT id FROM barrierresult WHERE barrierday='$ymd' LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        echo "  跳过 (数据已存在)\n";
        return false;
    }
    
    $url = "https://racing.hkjc.com/racing/information/chinese/Horse/BTResult.aspx?Date=" . urlencode($dateDMY);
    echo "  抓取: $url\n";
    $html = postRequest($url, []);
    if (empty($html)) {
        echo "  错误：无法获取页面内容。\n";
        return false;
    }
    
    $htmlDom = new simple_html_dom();
    $htmlDom->load($html);
    $savedCount = 0;
    
    // 查找每个试闸组 (div#divBtresult 内部)
    $groups = $htmlDom->find('div#divBtresult');
    foreach ($groups as $groupDiv) {
        // 修复: 提取试闸组标题 (Going)
        // 标题通常位于 <strong> 标签内，例如「從化試閘」或「第 1 組 - 從化草地 - 1400米」
        $goingTitle = '';
        $strongTag = $groupDiv->find('strong', 0);
        if ($strongTag) {
            $goingTitle = trim($strongTag->plaintext);
        }
        if (empty($goingTitle)) {
            // 备用: 查找包含「組」字的表格单元格
            $allTd = $groupDiv->find('td');
            foreach ($allTd as $td) {
                $text = trim($td->plaintext);
                if (strpos($text, '組') !== false && strpos($text, '-') !== false) {
                    $goingTitle = $text;
                    break;
                }
            }
        }
        
        // 查找该组的数据表格 (table.bigborder)
        $tables = $groupDiv->find('table.bigborder');
        foreach ($tables as $table) {
            $rows = $table->find('tr');
            foreach ($rows as $row) {
                if (strpos($row->plaintext, '馬名') !== false) continue; // 跳过表头
                $cells = $row->find('td');
                if (count($cells) < 5) continue;
                
                $horseLink = $cells[0]->find('a', 0);
                $horseName = $horseLink ? trim($horseLink->plaintext) : '';
                if (empty($horseName)) continue;
                
                // 按列索引提取字段（基于您提供的页面结构）
                $jockey   = isset($cells[1]) ? trim($cells[1]->plaintext) : '';
                $trainer  = isset($cells[2]) ? trim($cells[2]->plaintext) : '';
                $draw     = isset($cells[3]) ? trim($cells[3]->plaintext) : '';
                $gear     = isset($cells[4]) ? trim($cells[4]->plaintext) : '';
                $margin   = isset($cells[5]) ? trim($cells[5]->plaintext) : '';
                $running  = isset($cells[6]) ? trim($cells[6]->plaintext) : '';
                $finish   = isset($cells[7]) ? trim($cells[7]->plaintext) : '';
                $result   = isset($cells[8]) ? trim($cells[8]->plaintext) : '';
                $comment  = isset($cells[9]) ? trim($cells[9]->plaintext) : '';
                
                $sql = "INSERT INTO barrierresult 
                    (barrierday, Going, Horse, Jockey, Trainer, Draw, Gear, Margin, 
                     RunningPosition, FinishTime, Timex, Result, Comment) 
                    VALUES (
                        '$ymd',
                        '" . mysqli_real_escape_string($mysqli, $goingTitle) . "',
                        '" . mysqli_real_escape_string($mysqli, $horseName) . "',
                        '" . mysqli_real_escape_string($mysqli, $jockey) . "',
                        '" . mysqli_real_escape_string($mysqli, $trainer) . "',
                        '" . mysqli_real_escape_string($mysqli, $draw) . "',
                        '" . mysqli_real_escape_string($mysqli, $gear) . "',
                        '" . mysqli_real_escape_string($mysqli, $margin) . "',
                        '" . mysqli_real_escape_string($mysqli, $running) . "',
                        '" . mysqli_real_escape_string($mysqli, $finish) . "',
                        '" . mysqli_real_escape_string($mysqli, $finish) . "',
                        '" . mysqli_real_escape_string($mysqli, $result) . "',
                        '" . mysqli_real_escape_string($mysqli, $comment) . "'
                    )";
                
                if (mysqli_query($mysqli, $sql)) {
                    $savedCount++;
                }
            }
        }
    }
    
    if ($savedCount > 0) {
        echo "  ✅ 保存了 {$savedCount} 条记录 (Going: {$goingTitle})\n";

        // ✅ 安全插入 barrierday 表
        $insertBarrierday = "INSERT IGNORE INTO barrierday (barrierday) VALUES ('$ymd')";
        if (!mysqli_query($mysqli, $insertBarrierday)) {
            echo "  警告：更新 barrierday 表失败: " . mysqli_error($mysqli) . "\n";
        }
    } else {
        echo "  ⚠️ 未找到有效数据。\n";
    }
    return $savedCount > 0;
}

// ========== 4. 主程序 ==========
// echo "[" . date('Y-m-d H:i:s') . "] 开始更新试闸数据...\n";

// // 方案A: 自动获取日期列表
// $trialDates = getTrialDatesFromSelector($mysqli);
// if (empty($trialDates)) {
//     echo "未能自动获取日期列表，将使用预定义的近期日期。\n";
//     // 方案B: 手动指定近期有试闸的日期（备用）
//     $trialDates = [
//         '15/05/2026', '14/05/2026', '13/05/2026',
//         '08/05/2026', '07/05/2026', '06/05/2026'
//     ];
// }
// echo "共 " . count($trialDates) . " 个日期待处理。\n";

// $successCount = 0;
// foreach ($trialDates as $idx => $dateDMY) {
//     echo "\n[" . ($idx+1) . "] 处理日期: $dateDMY\n";
//     if (saveTrialDataForDate($mysqli, $dateDMY)) {
//         $successCount++;
//     }
//     usleep(500000);
// }

// echo "\n[" . date('Y-m-d H:i:s') . "] 完成！成功处理 {$successCount} 天。\n";
// mysqli_close($mysqli);

// ========== 优化后的主程序 ==========
echo "[" . date('Y-m-d H:i:s') . "] 开始更新试闸数据 (仅处理最近 {$maxDaysToFetch} 天)...\n";

// 1. 尝试获取全部可用日期列表
$allTrialDates = getTrialDatesFromSelector($mysqli);
$datesToFetch = [];

if (!empty($allTrialDates)) {
    echo "从页面获取到 " . count($allTrialDates) . " 个试闸日期。\n";
    $recentThreshold = date('Y-m-d', strtotime("-{$maxDaysToFetch} days"));
    foreach ($allTrialDates as $dateDMY) {
        $dateYMD = DateTime::createFromFormat('d/m/Y', $dateDMY)->format('Y-m-d');
        if ($dateYMD >= $recentThreshold) {
            $datesToFetch[] = $dateDMY;
        }
    }
    echo "其中最近 {$maxDaysToFetch} 天内的有 " . count($datesToFetch) . " 个。\n";
} else {
    // 2. 备用方案：如果无法获取列表，直接生成最近 N 天的可能日期（通常是周三、六、日）
    echo "未能获取日期列表，将生成最近 {$maxDaysToFetch} 天的可能试闸日期。\n";
    for ($i = 0; $i <= $maxDaysToFetch; $i++) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dayOfWeek = date('N', strtotime($date));
        // 试闸通常安排在周三(3)、周六(6)、周日(7)
        if (in_array($dayOfWeek, [3, 6, 7])) {
            $datesToFetch[] = DateTime::createFromFormat('Y-m-d', $date)->format('d/m/Y');
        }
    }
    $datesToFetch = array_unique($datesToFetch);
    rsort($datesToFetch);
    echo "生成了 " . count($datesToFetch) . " 个待检查日期。\n";
}

// 3. 逐个处理
$successCount = 0;
foreach ($datesToFetch as $idx => $dateDMY) {
    echo "\n[" . ($idx+1) . "] 处理日期: $dateDMY\n";
    if (saveTrialDataForDate($mysqli, $dateDMY)) {
        $successCount++;
    }
    usleep(300000); // 稍作停顿，礼貌抓取
}

echo "\n[" . date('Y-m-d H:i:s') . "] 完成！成功处理 {$successCount} 天。\n";
mysqli_close($mysqli);
?>