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
            r.race_no,
            ru.runner_no,
            ru.horse_code,
            COALESCE(v1.total_score, 0) AS score_version_1,
            v1.prediction_tag AS tag_version_1,
            v2.total_score AS score_version_2,
            v2.prediction_tag AS tag_version_2,
            v2.win_odds_snapshot AS win_odds,
            if(v2.barrier_trial_level,v2.barrier_trial_level,v1.barrier_trial_level) as barrier_trial_level,
            v2.priority AS priority,  -- 添加 priority 字段
            v2.blog_priority_score AS blog_priority_score
        FROM hkracing_races r
        JOIN hkracing_meetings m ON r.meeting_id = m.id
        JOIN hkracing_runners ru ON ru.race_id = r.id
        LEFT JOIN hkracing_v2_score v1 ON v1.race_id = r.id 
            AND v1.horse_code = ru.horse_code 
            AND v1.score_version = 1
        LEFT JOIN hkracing_v2_score v2 ON v2.race_id = r.id 
            AND v2.horse_code = ru.horse_code 
            AND v2.score_version = 2
        WHERE $whereConditions
        ORDER BY r.race_no, v2.priority, ru.runner_no + 0  -- 按 priority 排序
    ";
    // echo $sql;
    $result = mysqli_query($mysqli, $sql);
    
    if (!$result) {
        echo "SQL Error: " . $mysqli->error . "<br>";
        return [];
    }
    
    $hkracing_v2_score_array = [];
    
    while ($row = $result->fetch_assoc()) {
        $racingdate_key = $row['racing_date'];
        $race_no = $row['race_no'];
        $runner_no = $row['runner_no'];
        $horse_code = $row['horse_code'];
        
        if (empty($runner_no)) continue;
        
        $hkracing_v2_score_array[$racingdate_key][$venue][$race_no][$runner_no] = [
            'horse_code' => $horse_code,
            'score_version_1' => floatval($row['score_version_1']),
            'score_version_2' => floatval($row['score_version_2']),
            'tag_version_1' => $row['tag_version_1'],
            'tag_version_2' => $row['tag_version_2'],
            'win_odds' => $row['win_odds'],
            'barrier_trial_level' => $row['barrier_trial_level'],
            'blog_score' => $row['blog_score'], // added
            'blog_priority_score' => $row['blog_priority_score'], //added
            'priority' => $row['priority'] !== null ? intval($row['priority']) : null  
        ];
    }
    
    // ========== 计算排名 ==========    // ========== 计算排名 ==========
    foreach ($hkracing_v2_score_array as $racingdate_key => &$venue_data) {
        foreach ($venue_data as $venue_code => &$race_data) {
            foreach ($race_data as $race_no => &$runners) {
                
                // 收集各类分数
                $scores_v1 = [];
                $scores_v2 = [];
                $blog_priority_scores = [];  // 新增：收集 blog_priority_score
                $blog_scores = [];            // 新增：收集 blog_score
                
                foreach ($runners as $runner_no => &$runner) {
                    if ($runner['score_version_1'] > 0) {
                        $scores_v1[$runner_no] = $runner['score_version_1'];
                    }
                    if ($runner['score_version_2'] > 0) {
                        $scores_v2[$runner_no] = $runner['score_version_2'];
                    }
                    if ($runner['blog_priority_score'] !== null && $runner['blog_priority_score'] > 0) {
                        $blog_priority_scores[$runner_no] = $runner['blog_priority_score'];
                    }
                    if ($runner['blog_score'] !== null && $runner['blog_score'] > 0) {
                        $blog_scores[$runner_no] = $runner['blog_score'];
                    }
                }
                
                // ========== Version 1 排名 ==========
                arsort($scores_v1);
                $rank_v1 = 1;
                $prev_score_v1 = null;
                foreach ($scores_v1 as $runner_no => $score) {
                    if ($prev_score_v1 !== null && $score == $prev_score_v1) {
                        // 同分，排名不变
                    } else {
                        $rank_v1 = count(array_slice($scores_v1, 0, array_search($runner_no, array_keys($scores_v1)))) + 1;
                    }
                    $runners[$runner_no]['score_version_1_rank'] = $rank_v1;
                    $prev_score_v1 = $score;
                    $rank_v1++;
                }
                
                // ========== Version 2 排名 ==========
                arsort($scores_v2);
                $rank_v2 = 1;
                $prev_score_v2 = null;
                foreach ($scores_v2 as $runner_no => $score) {
                    if ($prev_score_v2 !== null && $score == $prev_score_v2) {
                        // 同分，排名不变
                    } else {
                        $rank_v2 = count(array_slice($scores_v2, 0, array_search($runner_no, array_keys($scores_v2)))) + 1;
                    }
                    $runners[$runner_no]['score_version_2_rank'] = $rank_v2;
                    $prev_score_v2 = $score;
                    $rank_v2++;
                }
                
                // ========== 新增：blog_priority_score 排名（按分数从高到低） ==========
                arsort($blog_priority_scores);
                $race_data['blog_priority_score_rank'] = [];
                $rank_bp = 1;
                $prev_score_bp = null;
                foreach ($blog_priority_scores as $runner_no => $score) {
                    if ($prev_score_bp !== null && $score == $prev_score_bp) {
                        // 同分，排名不变
                    } else {
                        $rank_bp = count(array_slice($blog_priority_scores, 0, array_search($runner_no, array_keys($blog_priority_scores)))) + 1;
                    }
                    $hkracing_v2_score_array[$racingdate_key][$venue][$race_no]['blog_priority_score_rank'][$rank_bp] = [
                        'runner_no' => $runner_no,
                        'blog_priority_score' => $score,
                        'horse_code' => $runners[$runner_no]['horse_code'],
                        'score_version_2' => $runners[$runner_no]['score_version_2']
                    ];
                    $runners[$runner_no]['blog_priority_score_rank'] = $rank_bp;
                    $prev_score_bp = $score;
                    $rank_bp++;
                }
                
                // ========== 新增：blog_score 排名（按分数从高到低） ==========
                arsort($blog_scores);
                $race_data['blog_score_rank'] = [];
                $rank_bs = 1;
                $prev_score_bs = null;
                foreach ($blog_scores as $runner_no => $score) {
                    if ($prev_score_bs !== null && $score == $prev_score_bs) {
                        // 同分，排名不变
                    } else {
                        $rank_bs = count(array_slice($blog_scores, 0, array_search($runner_no, array_keys($blog_scores)))) + 1;
                    }
                    $hkracing_v2_score_array[$racingdate_key][$venue][$race_no]['blog_score_rank'][$rank_bs] = [
                        'runner_no' => $runner_no,
                        'blog_score' => $score,
                        'horse_code' => $runners[$runner_no]['horse_code']
                    ];
                    $runners[$runner_no]['blog_score_rank'] = $rank_bs;
                    $prev_score_bs = $score;
                    $rank_bs++;
                }
                
                // 为没有分数的马匹设置默认排名
                foreach ($runners as $runner_no => &$runner) {
                    if (!isset($runner['score_version_1_rank'])) {
                        $runner['score_version_1_rank'] = null;
                    }
                    if (!isset($runner['score_version_2_rank'])) {
                        $runner['score_version_2_rank'] = null;
                    }
                    if (!isset($runner['blog_priority_score_rank'])) {
                        $runner['blog_priority_score_rank'] = null;
                    }
                    if (!isset($runner['blog_score_rank'])) {
                        $runner['blog_score_rank'] = null;
                    }
                }
            }
        }
    }
    unset($venue_data, $race_data, $runners, $runner); // 解除引用
    
    return $hkracing_v2_score_array;
}

// 如果你需要输出 priority 数据，可以添加一个调试函数
function output_priority_data($racingdate, $venue = '') {
    $data = load_hkracing_v2_score($racingdate, $venue);
    
    echo "<pre>";
    echo "=== Priority Data for {$racingdate} ===\n\n";
    
    foreach ($data as $date => $venues) {
        foreach ($venues as $venueCode => $races) {
            foreach ($races as $raceNo => $runners) {
                echo "Race {$raceNo}:\n";
                
                // 按 priority 排序
                $sortedRunners = $runners;
                uasort($sortedRunners, function($a, $b) {
                    $priA = $a['priority'] ?? 999;
                    $priB = $b['priority'] ?? 999;
                    return $priA - $priB;
                });
                
                foreach ($sortedRunners as $runnerNo => $runner) {
                    $priority = $runner['priority'] ?? '-';
                    $score = $runner['score_version_2'] ?? $runner['score_version_1'];
                    $tag = $runner['tag_version_2'] ?? $runner['tag_version_1'];
                    $odds = $runner['win_odds'] ?? '-';
                    
                    echo "  {$priority}.  {$runnerNo}號 - {$runner['horse_code']} | 評分: {$score} | 賠率: {$odds} | {$tag}\n";
                }
                echo "\n";
            }
        }
    }
    echo "</pre>";
}

// 如果你直接运行这个文件，可以输出今天的 priority 数据
// 取消下面的注释来使用：
// $racingdate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
// $venue = isset($_GET['venue']) ? $_GET['venue'] : '';
// output_priority_data($racingdate, $venue);
?>