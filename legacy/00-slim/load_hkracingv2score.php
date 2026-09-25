<?php

include_once ("lib/constants.php");
$mysqli = mysqli_connect(DB_HOSTwp, DB_USERwp, DB_PASSwp, DB_NAMEwp);

function load_hkracing_v2_score($racingdate, $venue = '') {
    global $mysqli;
    
    $racingdate = mysqli_real_escape_string($mysqli, $racingdate);
    $venue = mysqli_real_escape_string($mysqli, $venue);
    
    $whereConditions = "m.date = '$racingdate'";
    if (!empty($venue)) {
        $whereConditions .= " AND m.venue_code = '$venue'";
    }
    $whereConditions .= " AND m.venue_code IN ('ST', 'HV')";
    
    $sql = "
        SELECT 
            m.date AS racing_date,
            m.venue_code AS venue,
            r.race_no,
            COALESCE(s_v1.runner_no, s_v2.runner_no) AS runner_no,
            COALESCE(s_v1.horse_code, s_v2.horse_code) AS horse_code,
            COALESCE(s_v1.total_score, 0) AS score_version_1,
            COALESCE(s_v2.total_score, 0) AS score_version_2,
            s_v1.prediction_tag AS tag_version_1,
            s_v2.prediction_tag AS tag_version_2,
            s_v2.win_odds_snapshot AS win_odds,
            CASE 
                WHEN s_v2.total_score IS NOT NULL THEN s_v2.total_score - COALESCE(s_v1.total_score, 0)
                ELSE NULL
            END AS score_difference
        FROM hkracing_races r
        JOIN hkracing_meetings m ON r.meeting_id = m.id
        LEFT JOIN hkracing_v2_score s_v1 ON r.id = s_v1.race_id AND s_v1.score_version = 1
        LEFT JOIN hkracing_v2_score s_v2 ON r.id = s_v2.race_id AND s_v2.score_version = 2
        WHERE $whereConditions
        ORDER BY m.date, r.race_no, COALESCE(s_v1.runner_no, s_v2.runner_no) + 0
    ";
    $sql = "select * from hkracing_races limit 1";
    $result = mysqli_query($mysqli, $sql);
    
    if (!$result) {
        echo ": " . $mysqli->error . mysqli_error($connection) . "<br>";
        return [];
    }
    
    $hkracing_v2_score = [];
    
    while ($row = $result->fetch_assoc()) {
        $racingdate_key = $row['racing_date'];
        $race_no = $row['race_no'];
        $runner_no = $row['runner_no'];
        $horse_code = $row['horse_code'];
        // $venue = $row['venue'];
        
        if (empty($runner_no)) continue;
        
        $hkracing_v2_score[$racingdate_key][$venue][$race_no][$runner_no] = [
            'horse_code' => $horse_code,
            'score_version_1' => floatval($row['score_version_1']),
            'score_version_2' => floatval($row['score_version_2']),
            'tag_version_1' => $row['tag_version_1'],
            'tag_version_2' => $row['tag_version_2'],
            'win_odds' => $row['win_odds'],
            'score_difference' => $row['score_difference']
        ];
    }
    
    return $hkracing_v2_score;
}

/**
 * 顯示評分表格
 * @param array $scores 評分資料陣列
 */
// function display_score_table($scores) {
//     if (empty($scores)) {
//         echo "<p>沒有找到評分資料</p>";
//         return;
//     }
    
//     foreach ($scores as $racing_date => $races) {
//         echo "<h3>賽事日期: {$racing_date}</h3>";
        
//         foreach ($races as $race_no => $runners) {
//             echo "<h4>第 {$race_no} 場</h4>";
//             echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse;'>";
//             echo "<tr style='background-color: #f2f2f2;'>";
//             echo "<th>馬號</th>";
//             echo "<th>馬匹代碼</th>";
//             echo "<th>Version 1 評分</th>";
//             echo "<th>Version 1 標籤</th>";
//             echo "<th>Version 2 評分</th>";
//             echo "<th>Version 2 標籤</th>";
//             echo "<th>賠率</th>";
//             echo "<th>分數差異</th>";
//             echo "</tr>";
            
//             foreach ($runners as $runner_no => $data) {
//                 $score_v1 = $data['score_version_1'];
//                 $score_v2 = $data['score_version_2'];
//                 $tag_v1 = $data['tag_version_1'] ?? '-';
//                 $tag_v2 = $data['tag_version_2'] ?? '-';
//                 $win_odds = $data['win_odds'] ?? '-';
//                 $diff = $data['score_difference'] ?? '-';
                
//                 // 根據分數設定背景色
//                 $v1_color = get_score_color($score_v1);
//                 $v2_color = get_score_color($score_v2);
                
//                 echo "<tr>";
//                 echo "<td style='text-align: center;'>{$runner_no}</td>";
//                 echo "<td>{$data['horse_code']}</td>";
//                 echo "<td style='background-color: {$v1_color}; text-align: center; font-weight: bold;'>{$score_v1}</td>";
//                 echo "<td style='text-align: center;'>{$tag_v1}</td>";
//                 echo "<td style='background-color: {$v2_color}; text-align: center; font-weight: bold;'>{$score_v2}</td>";
//                 echo "<td style='text-align: center;'>{$tag_v2}</td>";
//                 echo "<td style='text-align: center;'>{$win_odds}</td>";
//                 echo "<td style='text-align: center;'>{$diff}</td>";
//                 echo "</tr>";
//             }
//             echo "</table><br>";
//         }
//     }
// }

// /**
//  * 根據分數獲取背景顏色
//  * @param float $score 分數
//  * @return string 顏色代碼
//  */
// function get_score_color($score) {
//     if ($score >= 102) return '#d4edda';      // 深綠 - 熱門首選
//     if ($score >= 90) return '#d1ecf1';       // 淺藍 - 值得關注
//     if ($score >= 78) return '#fff3cd';       // 淺黃 - 位置之選
//     if ($score >= 66) return '#f8d7da';       // 淺紅 - 冷門配搭
//     return '#e2e3e5';                          // 灰色 - 機會渺茫
// }

// /**
//  * 獲取單場賽事的評分摘要
//  * @param array $scores 評分資料陣列
//  * @param string $racing_date 賽事日期
//  * @param int $race_no 場次
//  * @return array 摘要資料
//  */
// function get_race_summary($scores, $racing_date, $race_no) {
//     $summary = [
//         'total_horses' => 0,
//         'avg_score_v1' => 0,
//         'avg_score_v2' => 0,
//         'max_score_v1' => 0,
//         'max_score_v2' => 0,
//         'min_score_v1' => 100,
//         'min_score_v2' => 100,
//         'hot_count' => 0,
//         'equal_count' => 0,
//         'cold_count' => 0
//     ];
    
//     if (isset($scores[$racing_date][$race_no])) {
//         $total = 0;
//         $total_v1 = 0;
//         $total_v2 = 0;
//         $count = 0;
        
//         foreach ($scores[$racing_date][$race_no] as $data) {
//             $count++;
//             $total_v1 += $data['score_version_1'];
//             $total_v2 += $data['score_version_2'];
            
//             if ($data['score_version_1'] > $summary['max_score_v1']) {
//                 $summary['max_score_v1'] = $data['score_version_1'];
//             }
//             if ($data['score_version_2'] > $summary['max_score_v2']) {
//                 $summary['max_score_v2'] = $data['score_version_2'];
//             }
//             if ($data['score_version_1'] < $summary['min_score_v1']) {
//                 $summary['min_score_v1'] = $data['score_version_1'];
//             }
//             if ($data['score_version_2'] < $summary['min_score_v2']) {
//                 $summary['min_score_v2'] = $data['score_version_2'];
//             }
            
//             // 統計標籤 (Version 2)
//             $tag = $data['tag_version_2'] ?? '';
//             if (strpos($tag, '熱門') !== false) $summary['hot_count']++;
//             elseif (strpos($tag, '均勢') !== false || strpos($tag, '位置') !== false) $summary['equal_count']++;
//             else $summary['cold_count']++;
//         }
        
//         $summary['total_horses'] = $count;
//         $summary['avg_score_v1'] = $count > 0 ? round($total_v1 / $count, 1) : 0;
//         $summary['avg_score_v2'] = $count > 0 ? round($total_v2 / $count, 1) : 0;
//     }
    
//     return $summary;
// }

// // ========== 使用範例 ==========

// // 查詢今天的賽事
// $racing_date = date('Y-m-d');
// // $racing_date = '2026-04-19';  // 或指定日期

// // 載入評分資料 (全部馬場)
// $scores = load_hkracing_v2_score($racing_date);

// // 或者只載入沙田賽事
// // $scores = load_hkracing_v2_score($racing_date, 'ST');

// // 顯示表格
// display_score_table($scores);

// // 顯示摘要
// echo "<h3>賽事摘要</h3>";
// foreach ($scores as $racing_date_key => $races) {
//     foreach ($races as $race_no => $runners) {
//         $summary = get_race_summary($scores, $racing_date_key, $race_no);
//         echo "<div style='margin-bottom: 15px; padding: 10px; background-color: #f8f9fa; border-radius: 5px;'>";
//         echo "<strong>第 {$race_no} 場 摘要:</strong><br>";
//         echo "參賽馬匹: {$summary['total_horses']} 匹<br>";
//         echo "平均分 (V1/V2): {$summary['avg_score_v1']} / {$summary['avg_score_v2']}<br>";
//         echo "最高分 (V1/V2): {$summary['max_score_v1']} / {$summary['max_score_v2']}<br>";
//         echo "最低分 (V1/V2): {$summary['min_score_v1']} / {$summary['min_score_v2']}<br>";
//         echo "預測分佈: 🔥 熱門:{$summary['hot_count']} | ⚖️ 均勢:{$summary['equal_count']} | ❄️ 冷門:{$summary['cold_count']}<br>";
//         echo "</div>";
//     }
// }

$mysqli->close();

?>