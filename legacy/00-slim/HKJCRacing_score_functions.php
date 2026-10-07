<?php
/**
 * HKJC 評分共用函數庫
 * 供 analysis_api.php 和 HKJCRacing_v2_score_cron.php 共同使用
 */

// ========== 評分計算核心函數 ==========

/**
 * 計算 V2 評分（核心函數）
 * @param array $horseStats 馬匹統計資料
 * @param array $recentRuns 最近出賽記錄
 * @param int|string $draw 檔位
 * @param float|null $winOdds 賠率
 * @param string $raceClass 班次
 * @param string $horseCode 馬匹代碼
 * @param PDO $pdo 資料庫連線
 * @param string|null $trainerName 練馬師名稱
 * @param string|null $jockeyName 騎師名稱
 * @param int|string $distance 距離
 * @param string $venueCode 馬場代碼
 * @return array 評分結果
 */
function calculateV2Score($horseStats, $recentRuns, $draw, $winOdds, $raceClass, $horseCode, $pdo, $trainerName, $jockeyName, $distance, $venueCode) {
    // 處理空值
    $draw = intval($draw);
    if ($draw <= 0) $draw = 7;
    
    $jockeyName = trim($jockeyName ?? '');
    if (empty($jockeyName) || $jockeyName === '---') {
        $jockeyName = null;
    }
    
    $trainerName = trim($trainerName ?? '');
    if (empty($trainerName) || $trainerName === '---') {
        $trainerName = null;
    }
    
    // 各項分數初始化
    $winRateScore = 0;
    $recentScore = 0;
    $drawScore = 0;
    $trainerJockeyScore = 0;
    $distanceVenueScore = 0;
    $oddsScore = 0;
    $classChangeScore = 0;
    $trainerVenueScore = 0;
    $trainerFormScore = 0;
    
    $starts = intval($horseStats['starts'] ?? 0);
    $winRate = floatval($horseStats['win_rate'] ?? 0);
    
    // ========== 1. Win rate scoring (最高30分) ==========
    if ($starts >= 3) {
        if ($winRate >= 25) $winRateScore = 30;
        elseif ($winRate >= 20) $winRateScore = 28;
        elseif ($winRate >= 15) $winRateScore = 25;
        elseif ($winRate >= 12) $winRateScore = 22;
        elseif ($winRate >= 10) $winRateScore = 20;
        elseif ($winRate >= 8) $winRateScore = 15;
        elseif ($winRate >= 5) $winRateScore = 10;
        elseif ($winRate >= 3) $winRateScore = 5;
    }
    
    // ========== 2. Recent 3 performances scoring (最高30分) ==========
    $positionWeights = [50, 30, 20];
    $validRuns = array_filter($recentRuns, function($run) {
        return isset($run['finishing_position']) && $run['finishing_position'] > 0;
    });
    $validRuns = array_values($validRuns);
    
    foreach ($validRuns as $index => $run) {
        $position = intval($run['finishing_position']);
        $weight = $positionWeights[$index] ?? 20;
        $points = 0;
        
        if ($position == 1) $points = 30;
        elseif ($position == 2) $points = 20;
        elseif ($position == 3) $points = 15;
        elseif ($position <= 5) $points = 8;
        elseif ($position <= 8) $points = 3;
        
        $recentScore += round(($points * $weight) / 100, 1);
    }
    
    // ========== 3. Draw history scoring (最高15分) ==========
    if ($draw >= 1 && $draw <= 14) {
        if ($draw <= 3) $drawGroup = '內檔(1-3)';
        elseif ($draw <= 6) $drawGroup = '中檔(4-6)';
        else $drawGroup = '外檔(7+)';
        
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as rides, 
                SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
                ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate
            FROM hkracing_horse_performances
            WHERE horse_code = ? 
                AND CASE 
                    WHEN barrier_draw <= 3 THEN '內檔(1-3)'
                    WHEN barrier_draw <= 6 THEN '中檔(4-6)'
                    ELSE '外檔(7+)'
                END = ?
        ");
        $stmt->execute([$horseCode, $drawGroup]);
        $drawHistory = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($drawHistory && $drawHistory['rides'] >= 3) {
            $drawWinRate = $drawHistory['win_rate'];
            if ($drawWinRate >= 20) $drawScore = 15;
            elseif ($drawWinRate >= 15) $drawScore = 12;
            elseif ($drawWinRate >= 10) $drawScore = 8;
            elseif ($drawWinRate >= 5) $drawScore = 5;
            else $drawScore = 2;
        } else {
            if ($draw <= 3) $drawScore = 5;
            elseif ($draw <= 6) $drawScore = 3;
            else $drawScore = 1;
        }
    }
    
    // ========== 4. Trainer + Jockey combination (最高15分) ==========
    if ($trainerName && $jockeyName) {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as rides, 
                SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
                ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate
            FROM hkracing_horse_performances
            WHERE horse_code = ? AND trainer_name_ch = ? AND jockey_name_ch = ?
        ");
        $stmt->execute([$horseCode, $trainerName, $jockeyName]);
        $tjStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tjStats && $tjStats['rides'] >= 3) {
            $tjWinRate = $tjStats['win_rate'];
            if ($tjWinRate >= 25) $trainerJockeyScore = 15;
            elseif ($tjWinRate >= 20) $trainerJockeyScore = 12;
            elseif ($tjWinRate >= 15) $trainerJockeyScore = 10;
            elseif ($tjWinRate >= 10) $trainerJockeyScore = 7;
            elseif ($tjWinRate >= 5) $trainerJockeyScore = 4;
            else $trainerJockeyScore = 1;
        }
    }
    
    // ========== 5. Distance + venue performance (最高15分) ==========
    if ($distance && $venueCode) {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as rides, 
                SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
                ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate
            FROM hkracing_horse_performances
            WHERE horse_code = ? AND distance = ? AND venue_code = ?
        ");
        $stmt->execute([$horseCode, $distance, $venueCode]);
        $dvStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($dvStats && $dvStats['rides'] >= 3) {
            $dvWinRate = $dvStats['win_rate'];
            if ($dvWinRate >= 25) $distanceVenueScore = 15;
            elseif ($dvWinRate >= 20) $distanceVenueScore = 12;
            elseif ($dvWinRate >= 15) $distanceVenueScore = 10;
            elseif ($dvWinRate >= 10) $distanceVenueScore = 7;
            elseif ($dvWinRate >= 5) $distanceVenueScore = 4;
            else $distanceVenueScore = 1;
        }
    }
    
    // ========== 6. Odds scoring (最高15分) ==========
    if ($winOdds && $winOdds > 0) {
        if ($winOdds <= 2.0) $oddsScore = 15;
        elseif ($winOdds <= 3.0) $oddsScore = 13;
        elseif ($winOdds <= 4.0) $oddsScore = 11;
        elseif ($winOdds <= 6.0) $oddsScore = 9;
        elseif ($winOdds <= 10.0) $oddsScore = 6;
        elseif ($winOdds <= 15.0) $oddsScore = 4;
        else $oddsScore = 2;
    }
    
    // ========== 7. Class change scoring (±12分) ==========
    $classChangeText = '-';
    $currentClassNum = parseClassToNumber($raceClass);
    
    if (!empty($validRuns)) {
        $lastRun = $validRuns[0];
        $lastClass = isset($lastRun['class']) ? (string)$lastRun['class'] : '';
        $lastClassNum = parseClassToNumber($lastClass);
        
        if ($lastClassNum > 0 && $currentClassNum > 0) {
            if ($currentClassNum < $lastClassNum) {
                $classChangeScore = -8;
                $classChangeText = '<span style="color: #ef4444;">⬆️ 升班</span>';
            } elseif ($currentClassNum > $lastClassNum) {
                $classChangeScore = 12;
                $classChangeText = '<span style="color: #10b981;">⬇️ 降班</span>';
            }
        }
    }
    
    // ========== 8. Trainer venue performance (最高10分) ==========
    if ($trainerName && $venueCode) {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as rides, 
                SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
                ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate
            FROM hkracing_horse_performances
            WHERE trainer_name_ch = ? AND venue_code = ?
        ");
        $stmt->execute([$trainerName, $venueCode]);
        $tvStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tvStats && $tvStats['rides'] >= 10) {
            $tvWinRate = $tvStats['win_rate'];
            if ($tvWinRate >= 15) $trainerVenueScore = 10;
            elseif ($tvWinRate >= 12) $trainerVenueScore = 8;
            elseif ($tvWinRate >= 10) $trainerVenueScore = 6;
            elseif ($tvWinRate >= 8) $trainerVenueScore = 4;
            elseif ($tvWinRate >= 5) $trainerVenueScore = 2;
            else $trainerVenueScore = 1;
        }
    }
    
    // ========== 9. Trainer current form (最高10分) ==========
    if ($trainerName) {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as rides, 
                SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
                ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate
            FROM hkracing_horse_performances
            WHERE trainer_name_ch = ? AND race_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$trainerName]);
        $tfStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt2 = $pdo->prepare("
            SELECT ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate
            FROM hkracing_horse_performances
            WHERE trainer_name_ch = ?
        ");
        $stmt2->execute([$trainerName]);
        $toStats = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        if ($tfStats && $tfStats['rides'] >= 5) {
            $tfWinRate = $tfStats['win_rate'];
            $toWinRate = $toStats['win_rate'] ?? 0;
            $improvement = $tfWinRate - $toWinRate;
            
            if ($tfWinRate >= 20) $trainerFormScore = 10;
            elseif ($tfWinRate >= 15) $trainerFormScore = 8;
            elseif ($tfWinRate >= 12) $trainerFormScore = 6;
            elseif ($tfWinRate >= 10) $trainerFormScore = 4;
            elseif ($tfWinRate >= 8) $trainerFormScore = 2;
            else $trainerFormScore = 1;
            
            if ($improvement >= 5) $trainerFormScore += 3;
            elseif ($improvement >= 3) $trainerFormScore += 2;
            elseif ($improvement >= 1) $trainerFormScore += 1;
            elseif ($improvement <= -5) $trainerFormScore -= 2;
        }
    }
    
    // 計算總分 (滿分120分)
    $score = $winRateScore + $recentScore + $drawScore + $trainerJockeyScore + 
             $distanceVenueScore + $oddsScore + $classChangeScore + 
             $trainerVenueScore + $trainerFormScore;
    
    $score = min(120, max(0, $score));
    
    // Generate prediction tag (120分制)
    if ($score >= 102) {
        $tag = '🔥 熱門首選';
        $tagClass = 'prediction-hot';
    } elseif ($score >= 90) {
        $tag = '⭐ 值得關注';
        $tagClass = 'prediction-hot';
    } elseif ($score >= 78) {
        $tag = '⚖️ 位置之選';
        $tagClass = 'prediction-equal';
    } elseif ($score >= 66) {
        $tag = '📊 冷門配搭';
        $tagClass = 'prediction-equal';
    } else {
        $tag = '❄️ 機會渺茫';
        $tagClass = 'prediction-cold';
    }
    
    return [
        'score' => round($score),
        'tag' => $tag,
        'tag_class' => $tagClass,
        'class_change_text' => $classChangeText,
        'breakdown' => [
            'win_rate' => $winRateScore,
            'recent_form' => $recentScore,
            'draw_history' => $drawScore,
            'trainer_jockey' => $trainerJockeyScore,
            'distance_venue' => $distanceVenueScore,
            'odds' => $oddsScore,
            'class_change' => $classChangeScore,
            'trainer_venue' => $trainerVenueScore,
            'trainer_form' => $trainerFormScore
        ]
    ];
}

/**
 * 解析班次為數字
 */
function parseClassToNumber($class) {
    if (empty($class)) return 0;
    
    if (preg_match('/Class\s+(\d+)/i', $class, $matches)) {
        return intval($matches[1]);
    }
    
    if (preg_match('/([一二三四五])班/', $class, $matches)) {
        $map = ['一' => 1, '二' => 2, '三' => 3, '四' => 4, '五' => 5];
        return $map[$matches[1]] ?? 0;
    }
    
    if (preg_match('/(\d+)/', $class, $matches)) {
        $num = intval($matches[1]);
        if ($num >= 1 && $num <= 5) return $num;
    }
    
    return 0;
}

/**
 * 统一的优先级计算函数
 * 与 analysis_api.php 中的 calculateSelectionPriority 逻辑完全一致
 * 
 * @param array $horse 马匹数据 (包含 horse_code, draw, current_odds, v2_score)
 * @param array $raceInfo 赛事信息 (包含 distance, venue_code, go_en, go_ch, race_class, race_id)
 * @param PDO $pdo 数据库连接
 * @return array 包含 priority_score, history_score, distance_win_rate, draw_top3_rate, suggestion
 */
// function calculateUnifiedPriority($horse, $raceInfo, $pdo) {
//     $horseCode = $horse['horse_code'];
//     $draw = intval($horse['draw']);
//     $currentOdds = floatval($horse['current_odds']);
//     $v2Score = floatval($horse['v2_score']);
//     $distance = intval($raceInfo['distance']);
//     $venueCode = $raceInfo['venue_code'];
//     $going = !empty($raceInfo['go_en']) ? $raceInfo['go_en'] : ($raceInfo['go_ch'] ?? '');
//     $classCode = $raceInfo['class_code'] ?? '';
    
//     // 1. 相同途程胜率 (权重 30%)
//     $distanceWinRate = 0;
//     $distanceTop3Rate = 0;
//     try {
//         $stmt = $pdo->prepare("
//             SELECT 
//                 COUNT(*) as total,
//                 SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
//                 SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) as top3
//             FROM hkracing_horse_performances
//             WHERE horse_code = ? 
//                 AND distance = ?
//                 AND venue_code = ?
//                 AND finishing_position IS NOT NULL
//                 AND finishing_position > 0
//         ");
//         $stmt->execute([$horseCode, $distance, $venueCode]);
//         $stats = $stmt->fetch(PDO::FETCH_ASSOC);
//         $total = intval($stats['total'] ?? 0);
//         $wins = intval($stats['wins'] ?? 0);
//         $top3 = intval($stats['top3'] ?? 0);
//         $distanceWinRate = $total > 0 ? ($wins / $total * 100) : 0;
//         $distanceTop3Rate = $total > 0 ? ($top3 / $total * 100) : 0;
//     } catch (Exception $e) {
//         error_log("distance stats error: " . $e->getMessage());
//     }
//     $distanceScore = min(30, ($distanceWinRate * 1.2) + ($distanceTop3Rate * 0.3));
    
//     // 2. 相同场地状况胜率 (权重 20%)
//     $goingScore = 10; // 默认分
//     if (!empty($going)) {
//         try {
//             $stmt = $pdo->prepare("
//                 SELECT 
//                     COUNT(*) as total,
//                     SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
//                     SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) as top3
//                 FROM hkracing_horse_performances
//                 WHERE horse_code = ? 
//                     AND (going_en = ? OR going_ch = ?)
//                     AND finishing_position IS NOT NULL
//                     AND finishing_position > 0
//             ");
//             $stmt->execute([$horseCode, $going, $going]);
//             $stats = $stmt->fetch(PDO::FETCH_ASSOC);
//             $total = intval($stats['total'] ?? 0);
//             $wins = intval($stats['wins'] ?? 0);
//             $top3 = intval($stats['top3'] ?? 0);
//             $goingWinRate = $total > 0 ? ($wins / $total * 100) : 0;
//             $goingTop3Rate = $total > 0 ? ($top3 / $total * 100) : 0;
//             $goingScore = min(20, ($goingWinRate * 0.8) + ($goingTop3Rate * 0.2));
//         } catch (Exception $e) {
//             error_log("going stats error: " . $e->getMessage());
//         }
//     }
    
//     // 3. 相同档位表现 (权重 15%)
//     $drawWinRate = 0;
//     $drawTop3Rate = 0;
//     try {
//         $stmt = $pdo->prepare("
//             SELECT 
//                 COUNT(*) as total,
//                 SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
//                 SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) as top3
//             FROM hkracing_horse_performances
//             WHERE horse_code = ? 
//                 AND barrier_draw = ?
//                 AND finishing_position IS NOT NULL
//                 AND finishing_position > 0
//         ");
//         $stmt->execute([$horseCode, $draw]);
//         $stats = $stmt->fetch(PDO::FETCH_ASSOC);
//         $total = intval($stats['total'] ?? 0);
//         $wins = intval($stats['wins'] ?? 0);
//         $top3 = intval($stats['top3'] ?? 0);
//         $drawWinRate = $total > 0 ? ($wins / $total * 100) : 0;
//         $drawTop3Rate = $total > 0 ? ($top3 / $total * 100) : 0;
//     } catch (Exception $e) {
//         error_log("draw stats error: " . $e->getMessage());
//     }
    
//     // 档位基础分 + 上名率加成
//     $drawScore = 0;
//     if ($draw <= 3) $drawScore = 8;
//     elseif ($draw <= 6) $drawScore = 5;
//     else $drawScore = 3;
//     if ($drawTop3Rate >= 50) $drawScore += 7;
//     elseif ($drawTop3Rate >= 30) $drawScore += 4;
//     elseif ($drawTop3Rate >= 20) $drawScore += 2;
//     $drawScore = min(15, $drawScore);
    
//     // 4. 同班次胜率 (权重 15%)
//     $classScore = 7;
//     if (!empty($classCode)) {
//         try {
//             $stmt = $pdo->prepare("
//                 SELECT 
//                     COUNT(*) as total,
//                     SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins
//                 FROM hkracing_horse_performances
//                 WHERE horse_code = ? AND class_code = ?
//                     AND finishing_position IS NOT NULL
//                     AND finishing_position > 0
//             ");
//             $stmt->execute([$horseCode, $classCode]);
//             $stats = $stmt->fetch(PDO::FETCH_ASSOC);
//             $total = intval($stats['total'] ?? 0);
//             $wins = intval($stats['wins'] ?? 0);
//             $classWinRate = $total > 0 ? ($wins / $total * 100) : 0;
//             $classScore = min(15, $classWinRate);
//         } catch (Exception $e) {
//             error_log("class stats error: " . $e->getMessage());
//         }
//     }
    
//     // 5. 近5场状态 (权重 20%)
//     $recentScore = 0;
//     try {
//         $stmt = $pdo->prepare("
//             SELECT finishing_position
//             FROM hkracing_horse_performances
//             WHERE horse_code = ?
//                 AND finishing_position IS NOT NULL
//                 AND finishing_position > 0
//             ORDER BY race_date DESC
//             LIMIT 5
//         ");
//         $stmt->execute([$horseCode]);
//         $recentRuns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
//         $positionScores = [1 => 20, 2 => 16, 3 => 13, 4 => 10, 5 => 8, 6 => 6, 7 => 5, 8 => 4, 9 => 3, 10 => 2];
//         foreach ($recentRuns as $idx => $run) {
//             $weight = 1 - ($idx * 0.12);
//             $weight = max(0.5, $weight);
//             $pos = intval($run['finishing_position']);
//             $posScore = $positionScores[$pos] ?? max(0, 20 - $pos);
//             $recentScore += $posScore * $weight;
//         }
//         $recentCount = count($recentRuns);
//         $recentScore = $recentCount > 0 ? min(20, $recentScore / $recentCount) : 0;
//     } catch (Exception $e) {
//         error_log("recent runs error: " . $e->getMessage());
//     }
    
//     // 6. 赔率调整 (±12)
//     $oddsBonus = 0;
//     if ($currentOdds > 0) {
//         if ($currentOdds <= 2.5) $oddsBonus = 12;
//         elseif ($currentOdds <= 4) $oddsBonus = 8;
//         elseif ($currentOdds <= 6) $oddsBonus = 4;
//         elseif ($currentOdds <= 10) $oddsBonus = 0;
//         elseif ($currentOdds <= 20) $oddsBonus = -3;
//         else $oddsBonus = -6;
//     }
    
//     // 计算历史条件总分
//     $historyScore = $distanceScore + $goingScore + $drawScore + $classScore + $recentScore + $oddsBonus;
//     $historyScore = max(0, min(100, $historyScore));
    
//     // 最终优先分数 = 历史条件分(40%) + 综合评分(60%)
//     $finalPriority = ($historyScore * 0.4) + ($v2Score * 0.6);
//     $finalPriority = round($finalPriority, 1);
    
//     // 生成建议文字
//     $suggestion = getSuggestionTextUnified($finalPriority, $currentOdds, $distanceWinRate, $drawTop3Rate);
    
//     return [
//         'priority_score' => $finalPriority,
//         'history_score' => round($historyScore, 1),
//         'distance_win_rate' => round($distanceWinRate, 1),
//         'draw_top3_rate' => round($drawTop3Rate, 1),
//         'suggestion' => $suggestion
//     ];
// }

/**
 * 统一的建议文字生成函数
 */
function getSuggestionTextUnified($score, $odds, $distanceWinRate, $drawTop3Rate) {
    if ($score >= 70) {
        $base = '🔥 强烈推荐 - ';
    } elseif ($score >= 55) {
        $base = '⭐ 值得留意 - ';
    } elseif ($score >= 40) {
        $base = '📌 可作为配脚 - ';
    } else {
        $base = '⚠️ 需谨慎 - ';
    }
    
    $reasons = [];
    if ($distanceWinRate >= 20) $reasons[] = "🏇 同程胜率{$distanceWinRate}%";
    elseif ($distanceWinRate >= 10) $reasons[] = "📊 同程有经验({$distanceWinRate}%)";
    if ($drawTop3Rate >= 40) $reasons[] = "🎯 此档位表现佳({$drawTop3Rate}%)";
    if ($odds <= 4 && $odds > 0) $reasons[] = "📈 热门追捧";
    if ($odds >= 15) $reasons[] = "❄️ 冷门分子";
    
    $reasonText = !empty($reasons) ? ' [' . implode('] [', $reasons) . ']' : '';
    
    return $base . $reasonText;
}

//----------------------------------------------------------------------------------------------
// ========== 统一的优先级计算函数 ==========

/**
 * 统一的选马优先顺序计算函数
 * 与 analysis_api.php 中的 calculateSelectionPriority 逻辑完全一致
 * 
 * @param array $horse 马匹数据 (必须包含: horse_code, draw, current_odds, v2_score, runner_no, horse_name)
 * @param array $raceInfo 赛事信息 (必须包含: race_id, distance, venue_code, go_en/go_ch, class_code, race_class)
 * @param PDO $pdo 数据库连接
 * @return array 包含 priority_score, history_score, distance_win_rate, draw_top3_rate, suggestion
 */
// ========== 统一的优先级计算函数（增强版） ==========

/**
 * 统一的选马优先顺序计算函数 - 增强版
 * 包含：场地适性、班次能力、路程专项、档位统计、近况状态、
 *       配备变化、骑师更换、休憩日数、体重变化、档位+跑法配合、
 *       跑道偏差、血统分析
 * 
 * @param array $horse 马匹数据 
 * @param array $raceInfo 赛事信息
 * @param PDO $pdo 数据库连接
 * @return array 优先级计算结果
 */
function calculateUnifiedPriority($horse, $raceInfo, $pdo) {
    $horseCode = $horse['horse_code'];
    $draw = intval($horse['draw']);
    $currentOdds = floatval($horse['current_odds']);
    $v2Score = floatval($horse['v2_score']);
    $currentJockey = $horse['jockey_name'] ?? '';
    $currentTrainer = $horse['trainer_name'] ?? '';
    $currentGear = $horse['gear'] ?? '';
    $currentWeight = floatval($horse['weight'] ?? 0);
    
    $distance = intval($raceInfo['distance']);
    $venueCode = $raceInfo['venue_code'];
    $going = !empty($raceInfo['go_en']) ? $raceInfo['go_en'] : ($raceInfo['go_ch'] ?? '');
    $classCode = $raceInfo['class_code'] ?? '';
    $raceDate = $raceInfo['race_date'] ?? date('Y-m-d');
    
    // 获取上次出赛信息（用于多个评分项）
    $lastRun = getLastRun($pdo, $horseCode);
    
    // ========== 1. 相同途程胜率 (权重 30%) ==========
    $distanceWinRate = 0;
    $distanceTop3Rate = 0;
    $distanceScore = 0;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) as top3
            FROM hkracing_horse_performances
            WHERE horse_code = ? 
                AND distance = ?
                AND venue_code = ?
                AND finishing_position IS NOT NULL
                AND finishing_position > 0
        ");
        $stmt->execute([$horseCode, $distance, $venueCode]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = intval($stats['total'] ?? 0);
        $wins = intval($stats['wins'] ?? 0);
        $top3 = intval($stats['top3'] ?? 0);
        // $distanceWinRate = $total > 0 ? ($wins / $total * 100) : 0;
        $distanceWinRate = $total > 0 ? round($wins / $total * 100, 1) : 0;
        $distanceTop3Rate = $total > 0 ? ($top3 / $total * 100) : 0;
        $distanceScore = min(30, ($distanceWinRate * 1.2) + ($distanceTop3Rate * 0.3));
    } catch (Exception $e) {
        error_log("calculateUnifiedPriority - distance stats error: " . $e->getMessage());
    }
    
    // ========== 2. 相同场地状况胜率 (权重 15%) - 降低权重为配備等让位 ==========
    $goingScore = 8; // 默认分
    if (!empty($going)) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
                    SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) as top3
                FROM hkracing_horse_performances
                WHERE horse_code = ? 
                    AND (going_en = ? OR going_ch = ?)
                    AND finishing_position IS NOT NULL
                    AND finishing_position > 0
            ");
            $stmt->execute([$horseCode, $going, $going]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = intval($stats['total'] ?? 0);
            $wins = intval($stats['wins'] ?? 0);
            $top3 = intval($stats['top3'] ?? 0);
            $goingWinRate = $total > 0 ? ($wins / $total * 100) : 0;
            $goingTop3Rate = $total > 0 ? ($top3 / $total * 100) : 0;
            $goingScore = min(15, ($goingWinRate * 0.8) + ($goingTop3Rate * 0.2));
        } catch (Exception $e) {
            error_log("calculateUnifiedPriority - going stats error: " . $e->getMessage());
        }
    }
    
    // ========== 3. 相同档位表现 + 跑法配合 (权重 12%) ==========
    $drawWinRate = 0;
    $drawTop3Rate = 0;
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) as top3
            FROM hkracing_horse_performances
            WHERE horse_code = ? 
                AND barrier_draw = ?
                AND finishing_position IS NOT NULL
                AND finishing_position > 0
        ");
        $stmt->execute([$horseCode, $draw]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = intval($stats['total'] ?? 0);
        $wins = intval($stats['wins'] ?? 0);
        $top3 = intval($stats['top3'] ?? 0);
        $drawWinRate = $total > 0 ? ($wins / $total * 100) : 0;
        $drawTop3Rate = $total > 0 ? ($top3 / $total * 100) : 0;
    } catch (Exception $e) {
        error_log("calculateUnifiedPriority - draw stats error: " . $e->getMessage());
    }
    
    // 档位基础分
    $drawScore = 0;
    if ($draw <= 3) $drawScore = 6;
    elseif ($draw <= 6) $drawScore = 4;
    else $drawScore = 2;
    if ($drawTop3Rate >= 50) $drawScore += 5;
    elseif ($drawTop3Rate >= 30) $drawScore += 3;
    elseif ($drawTop3Rate >= 20) $drawScore += 1;
    $drawScore = min(12, $drawScore);
    
    // ========== 4. 档位+跑法配合评分 (新增，权重 5%) ==========
    $runningStyleScore = calculateRunningStyleWithDrawScore($pdo, $horseCode, $draw);
    
    // ========== 5. 配备变化评分 (新增，权重 8%) ==========
    $gearChangeScore = calculateGearChangeScore($pdo, $horseCode, $currentGear, $lastRun);
    
    // ========== 6. 骑师更换评分 (新增，权重 8%) ==========
    $jockeyChangeScore = calculateJockeyChangeScore($pdo, $horseCode, $currentJockey, $currentTrainer, $lastRun, $raceDate);
    
    // ========== 7. 休憩日数评分 (新增，权重 5%) ==========
    $freshenScore = calculateFreshenScore($pdo, $horseCode, $raceDate, $lastRun);
    
    // ========== 8. 体重变化评分 (新增，权重 5%) ==========
    $weightChangeScore = calculateWeightChangeScore($pdo, $horseCode, $currentWeight, $lastRun);
    
    // ========== 9. 跑道偏差评分 (新增，权重 3%) ==========
    $trackBiasScore = calculateTrackBiasScore($pdo, $draw, $distance, $venueCode);
    
    // ========== 10. 血统分析评分 (新增，权重 4%) ==========
    $pedigreeScore = calculatePedigreeScore($pdo, $horseCode, $distance);
    
    // ========== 11. 同班次胜率 (权重 10%) ==========
    $classScore = 5; // 默认分
    if (!empty($classCode)) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins
                FROM hkracing_horse_performances
                WHERE horse_code = ? AND class_code = ?
                    AND finishing_position IS NOT NULL
                    AND finishing_position > 0
            ");
            $stmt->execute([$horseCode, $classCode]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = intval($stats['total'] ?? 0);
            $wins = intval($stats['wins'] ?? 0);
            $classWinRate = $total > 0 ? ($wins / $total * 100) : 0;
            $classScore = min(10, $classWinRate);
        } catch (Exception $e) {
            error_log("calculateUnifiedPriority - class stats error: " . $e->getMessage());
        }
    }
    
    // ========== 12. 近5场状态 (权重 10%) ==========
    $recentScore = 0;
    $recentCount = 0;
    try {
        $stmt = $pdo->prepare("
            SELECT finishing_position
            FROM hkracing_horse_performances
            WHERE horse_code = ?
                AND finishing_position IS NOT NULL
                AND finishing_position > 0
            ORDER BY race_date DESC
            LIMIT 5
        ");
        $stmt->execute([$horseCode]);
        $recentRuns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $positionScores = [1 => 20, 2 => 16, 3 => 13, 4 => 10, 5 => 8, 6 => 6, 7 => 5, 8 => 4, 9 => 3, 10 => 2];
        foreach ($recentRuns as $idx => $run) {
            $weight = 1 - ($idx * 0.12);
            $weight = max(0.5, $weight);
            $pos = intval($run['finishing_position']);
            $posScore = $positionScores[$pos] ?? max(0, 20 - $pos);
            $recentScore += $posScore * $weight;
        }
        $recentCount = count($recentRuns);
        $recentScore = $recentCount > 0 ? min(10, $recentScore / ($recentCount * 2)) : 0;
    } catch (Exception $e) {
        error_log("calculateUnifiedPriority - recent runs error: " . $e->getMessage());
    }
    
    // ========== 13. 赔率调整 (权重 额外 ±10) ==========
    $oddsBonus = 0;
    if ($currentOdds > 0) {
        if ($currentOdds <= 2.5) $oddsBonus = 10;
        elseif ($currentOdds <= 4) $oddsBonus = 7;
        elseif ($currentOdds <= 6) $oddsBonus = 4;
        elseif ($currentOdds <= 10) $oddsBonus = 0;
        elseif ($currentOdds <= 20) $oddsBonus = -3;
        else $oddsBonus = -5;
    }
    
    // ========== 计算历史条件总分 ==========
    $historyScore = $distanceScore + $goingScore + $drawScore + $runningStyleScore + 
                    $gearChangeScore + $jockeyChangeScore + $freshenScore + 
                    $weightChangeScore + $trackBiasScore + $pedigreeScore + 
                    $classScore + $recentScore + $oddsBonus;
    $historyScore = max(0, min(100, $historyScore));
    
    // ========== 最终优先分数 = 历史条件分(50%) + 综合评分(50%) ==========
    // 提高历史条件的权重，因为新增了很多评分项
    $finalPriority = ($historyScore * 0.5) + ($v2Score * 0.5);
    $finalPriority = round($finalPriority, 1);
    
    // ========== 生成建议文字 ==========
    $suggestion = getPrioritySuggestionTextEnhanced($finalPriority, $currentOdds, round($distanceWinRate,1), $drawTop3Rate, 
                  $gearChangeScore, $jockeyChangeScore, $freshenScore);
    
    
    return [
        'priority_score' => $finalPriority,
        'history_score' => round($historyScore, 1),
        'distance_win_rate' => round($distanceWinRate, 1),
        'distance_top3_rate' => round($distanceTop3Rate, 1),
        'draw_win_rate' => round($drawWinRate, 1),
        'draw_top3_rate' => round($drawTop3Rate, 1),
        'recent_form' => $recentCount . '場',
        'suggestion' => $suggestion,
        'breakdown' => [
            'distance_score' => round($distanceScore, 1),
            'going_score' => round($goingScore, 1),
            'draw_score' => round($drawScore, 1),
            'running_style_score' => round($runningStyleScore, 1),
            'gear_change_score' => round($gearChangeScore, 1),
            'jockey_change_score' => round($jockeyChangeScore, 1),
            'freshen_score' => round($freshenScore, 1),
            'weight_change_score' => round($weightChangeScore, 1),
            'track_bias_score' => round($trackBiasScore, 1),
            'pedigree_score' => round($pedigreeScore, 1),
            'class_score' => round($classScore, 1),
            'recent_score' => round($recentScore, 1),
            'odds_bonus' => $oddsBonus
        ]
    ];
}

/**
 * 获取马匹上次出赛信息
 */
function getLastRun($pdo, $horseCode) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                race_date,
                finishing_position,
                gear,
                jockey_name_ch as jockey,
                trainer_name_ch as trainer,
                actual_weight,
                running_position,
                barrier_draw
            FROM hkracing_horse_performances
            WHERE horse_code = ?
                AND finishing_position IS NOT NULL
            ORDER BY race_date DESC
            LIMIT 1
        ");
        $stmt->execute([$horseCode]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("getLastRun error: " . $e->getMessage());
        return null;
    }
}

/**
 * 配备变化评分 (最高8分)
 */
function calculateGearChangeScore($pdo, $horseCode, $currentGear, $lastRun) {
    if (empty($lastRun)) return 0;
    
    $lastGear = $lastRun['gear'] ?? '';
    
    // 没有变化
    if ($currentGear == $lastGear) return 0;
    
    $score = 0;
    
    // 配备变化评分规则
    $gearRules = [
        '首次戴眼罩' => ['pattern' => '/眼罩/', 'is_first' => true, 'score' => 5],
        '除下眼罩' => ['pattern' => '/眼罩/', 'is_first' => false, 'score' => -2],
        '首次戴頭罩' => ['pattern' => '/頭罩/', 'is_first' => true, 'score' => 3],
        '除下頭罩' => ['pattern' => '/頭罩/', 'is_first' => false, 'score' => -1],
        '戴羊毛面箍' => ['pattern' => '/羊毛/', 'is_first' => false, 'score' => 2],
        '戴鼻箍' => ['pattern' => '/鼻箍/', 'is_first' => false, 'score' => 2],
        '除下鼻箍' => ['pattern' => '/鼻箍/', 'is_first' => false, 'score' => -1],
    ];
    
    // 检查今次是否首次使用某种配备
    if (!empty($currentGear) && empty($lastGear)) {
        foreach ($gearRules as $rule) {
            if (preg_match($rule['pattern'], $currentGear) && $rule['is_first']) {
                $score = $rule['score'];
                break;
            }
        }
    } elseif (!empty($currentGear) && !empty($lastGear)) {
        // 检查更换配备
        if (strpos($currentGear, '眼罩') !== false && strpos($lastGear, '眼罩') === false) {
            $score = 5;
        } elseif (strpos($currentGear, '眼罩') === false && strpos($lastGear, '眼罩') !== false) {
            $score = -2;
        } elseif (strpos($currentGear, '頭罩') !== false && strpos($lastGear, '頭罩') === false) {
            $score = 3;
        } elseif (strpos($currentGear, '鼻箍') !== false && strpos($lastGear, '鼻箍') === false) {
            $score = 2;
        }
    }
    
    // 检查上次配备是否有效果
    if ($lastRun['finishing_position'] == 1 && !empty($lastGear)) {
        if ($currentGear != $lastGear) $score -= 2;
    }
    
    return max(-3, min(8, $score));
}

/**
 * 骑师更换评分 (最高8分)
 */
function calculateJockeyChangeScore($pdo, $horseCode, $currentJockey, $currentTrainer, $lastRun, $raceDate) {
    if (empty($lastRun)) return 0;
    
    $lastJockey = $lastRun['jockey'] ?? '';
    $score = 0;
    
    // 骑师更换
    if (!empty($currentJockey) && $currentJockey != $lastJockey) {
        // 检查新骑师与该马的合作记录
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as rides,
                    SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
                    ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate
                FROM hkracing_horse_performances
                WHERE horse_code = ? AND jockey_name_ch = ?
            ");
            $stmt->execute([$horseCode, $currentJockey]);
            $jockeyStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($jockeyStats && $jockeyStats['rides'] >= 2) {
                $winRate = $jockeyStats['win_rate'];
                if ($winRate >= 25) $score += 6;
                elseif ($winRate >= 15) $score += 4;
                elseif ($winRate >= 10) $score += 2;
                else $score += 0;
            } else {
                // 无合作记录，给中性分
                $score += 0;
            }
        } catch (Exception $e) {
            error_log("jockey change query error: " . $e->getMessage());
        }
        
        // 检查骑师是否在当天有赢马（气势）
        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT race_id) as wins_today
                FROM hkracing_runners ru
                JOIN hkracing_v2_score s ON ru.race_id = s.race_id AND ru.runner_no = s.runner_no
                WHERE ru.jockey_name_ch = ? 
                    AND s.race_finished = 1 
                    AND s.prediction_class = 'prediction-hot'
                    AND DATE(s.last_updated) = ?
            ");
            $stmt->execute([$currentJockey, $raceDate]);
            $todayStats = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($todayStats && $todayStats['wins_today'] > 0) {
                $score += 2;
            }
        } catch (Exception $e) {
            error_log("jockey today stats error: " . $e->getMessage());
        }
    } else {
        // 骑师不变，检查上次成绩
        if ($lastRun['finishing_position'] == 1) {
            $score += 2; // 上仗赢马，骑师留任加分
        } elseif ($lastRun['finishing_position'] <= 3) {
            $score += 1; // 上仗上名，骑师留任小加分
        }
    }
    
    // 见习生减磅优势
    if (strpos($currentJockey, '见习') !== false || strpos($currentJockey, '见习生') !== false) {
        $score += 2;
    }
    
    return max(-3, min(8, $score));
}

/**
 * 休憩日数评分 (最高5分)
 */
function calculateFreshenScore($pdo, $horseCode, $raceDate, $lastRun) {
    if (empty($lastRun) || empty($lastRun['race_date'])) return 0;
    
    $lastRaceDate = new DateTime($lastRun['race_date']);
    $currentRaceDate = new DateTime($raceDate);
    $interval = $lastRaceDate->diff($currentRaceDate);
    $daysOff = $interval->days;
    
    $score = 0;
    
    // 休息日数评分
    if ($daysOff >= 21 && $daysOff <= 35) {
        $score = 5; // 新鲜且有状态
    } elseif ($daysOff >= 14 && $daysOff <= 20) {
        $score = 3; // 良好休息
    } elseif ($daysOff >= 36 && $daysOff <= 60) {
        $score = 1; // 略长
    } elseif ($daysOff >= 61 && $daysOff <= 120) {
        $score = -1; // 久休，可能需要热身
    } elseif ($daysOff > 120) {
        $score = -3; // 超久休，状态成疑
    } elseif ($daysOff <= 7) {
        $score = -2; // 背对背，体力消耗大
    } elseif ($daysOff <= 13) {
        $score = 0; // 正常
    }
    
    // 调整：如果上次赢了，短休息可接受
    if ($lastRun['finishing_position'] == 1 && $daysOff <= 14 && $daysOff >= 7) {
        $score += 2;
    }
    
    return max(-3, min(5, $score));
}

/**
 * 体重变化评分 (最高5分)
 */
function calculateWeightChangeScore($pdo, $horseCode, $currentWeight, $lastRun) {
    if (empty($lastRun) || empty($lastRun['actual_weight']) || $currentWeight <= 0) return 0;
    
    $lastWeight = floatval($lastRun['actual_weight']);
    $weightChange = $currentWeight - $lastWeight;
    
    $score = 0;
    
    // 体重变化评分（负磅越轻越好）
    if ($weightChange <= -5) {
        $score = 5; // 大幅减磅，有利
    } elseif ($weightChange <= -2) {
        $score = 3; // 减磅
    } elseif ($weightChange >= 5) {
        $score = -3; // 大幅增磅，不利
    } elseif ($weightChange >= 2) {
        $score = -1; // 增磅
    } else {
        $score = 0; // 持平
    }
    
    // 调整：上次赢马增磅可能仍可应付
    if ($lastRun['finishing_position'] == 1 && $weightChange > 0) {
        $score += 2;
    }
    
    return max(-3, min(5, $score));
}

/**
 * 档位+跑法配合评分 (最高5分)
 */
function calculateRunningStyleWithDrawScore($pdo, $horseCode, $draw) {
    // 获取最近3场的跑法
    try {
        $stmt = $pdo->prepare("
            SELECT running_position, barrier_draw
            FROM hkracing_horse_performances
            WHERE horse_code = ?
                AND running_position IS NOT NULL
            ORDER BY race_date DESC
            LIMIT 3
        ");
        $stmt->execute([$horseCode]);
        $recentRuns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return 0;
    }
    
    if (empty($recentRuns)) return 0;
    
    // 分析跑法
    $runningStyle = 'unknown';
    foreach ($recentRuns as $run) {
        $positions = preg_split('/\s+/', trim($run['running_position']));
        if (count($positions) >= 2) {
            $firstPos = intval($positions[0]);
            if ($firstPos <= 3) {
                $runningStyle = 'leader';
                break;
            } elseif ($firstPos <= 6) {
                $runningStyle = 'prominent';
            } elseif ($firstPos >= 10) {
                $runningStyle = 'closer';
            } else {
                $runningStyle = 'midfielder';
            }
        }
    }
    
    $score = 0;
    
    // 档位+跑法配合评分
    if ($runningStyle == 'leader') {
        // 放头马适合内档
        if ($draw <= 3) $score = 5;
        elseif ($draw <= 6) $score = 2;
        else $score = -2;
    } elseif ($runningStyle == 'closer') {
        // 后上马外档影响较小
        if ($draw >= 7) $score = 3;
        elseif ($draw <= 3) $score = 1;
        else $score = 2;
    } elseif ($runningStyle == 'prominent') {
        // 跟前马中档最好
        if ($draw >= 4 && $draw <= 6) $score = 4;
        elseif ($draw <= 3) $score = 3;
        else $score = 1;
    } else {
        // 居中马
        $score = 2;
    }
    
    return min(5, max(-3, $score));
}

/**
 * 跑道偏差评分 (最高3分)
 */
function calculateTrackBiasScore($pdo, $draw, $distance, $venueCode) {
    // 基于距离和场地分析档位偏差
    $score = 0;
    
    // 短途赛 (1000-1200米) 内档优势明显
    if ($distance <= 1200) {
        if ($draw <= 3) $score = 3;
        elseif ($draw <= 6) $score = 1;
        else $score = -1;
    } 
    // 中距离 (1400-1600米) 档位影响中等
    elseif ($distance <= 1600) {
        if ($draw <= 4) $score = 2;
        elseif ($draw <= 7) $score = 1;
        else $score = 0;
    }
    // 长途 (1800米以上) 档位影响较小
    else {
        if ($draw <= 3) $score = 1;
        else $score = 0;
    }
    
    // 跑马地赛道特殊性（弯急路窄，内档优势更大）
    if ($venueCode == 'HV' && $distance <= 1200) {
        if ($draw <= 3) $score += 1;
        elseif ($draw >= 10) $score -= 1;
    }
    
    return max(-2, min(3, $score));
}

/**
 * 血统分析评分 (最高4分)
 */
function calculatePedigreeScore($pdo, $horseCode, $distance) {
    // 获取马匹血统信息
    try {
        $stmt = $pdo->prepare("
            SELECT sire, dam, age
            FROM hkracing_horses
            WHERE horse_code = ?
        ");
        $stmt->execute([$horseCode]);
        $horseData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return 0;
    }
    
    if (empty($horseData)) return 0;
    
    $score = 0;
    
    // 1. 根据父系判断距离适应性
    if (!empty($horseData['sire'])) {
        $sire = $horseData['sire'];
        
        // 常见长途父系
        $stayers = ['Galileo', 'Montjeu', 'Sadler\'s Wells', 'Deep Impact', 'Ruler of The World', 'Sea The Stars', 'Nathaniel'];
        // 常见短途父系
        $sprinters = ['Exceed And Excel', 'I Am Invincible', 'Not A Single Doubt', 'Snitzel', 'Written Tycoon', 'Capitalist', 'Brave Smash'];
        
        foreach ($stayers as $stayer) {
            if (stripos($sire, $stayer) !== false) {
                if ($distance >= 1800) $score += 2;
                elseif ($distance >= 1600) $score += 1;
                break;
            }
        }
        
        foreach ($sprinters as $sprinter) {
            if (stripos($sire, $sprinter) !== false) {
                if ($distance <= 1200) $score += 2;
                elseif ($distance <= 1400) $score += 1;
                break;
            }
        }
    }
    
    // 2. 根据母亲（母系）判断
    if (!empty($horseData['dam'])) {
        $dam = $horseData['dam'];
        // 母系有佳绩的加分
        $damKeywords = ['star', 'classic', 'champion', 'winner', 'stakes'];
        foreach ($damKeywords as $keyword) {
            if (stripos($dam, $keyword) !== false) {
                $score += 1;
                break;
            }
        }
    }
    
    // 3. 根据年龄判断成熟度（使用 age 字段）
    $age = intval($horseData['age'] ?? 0);
    if ($age == 4) {
        $score += 2;      // 4岁黄金期
    } elseif ($age == 5) {
        $score += 1;      // 5岁仍佳
    } elseif ($age == 3) {
        $score += 1;      // 3岁进步中
    } elseif ($age >= 7) {
        $score -= 1;      // 7岁或以上老化
    }
    
    return max(0, min(4, $score));
}

/**
 * 增强版建议文字生成函数
 */
function getPrioritySuggestionTextEnhanced($score, $odds, $distanceWinRate, $drawTop3Rate, $gearScore, $jockeyScore, $freshenScore) {
    if ($score >= 70) {
        $base = '🔥 強烈推薦 - ';
    } elseif ($score >= 55) {
        $base = '⭐ 值得留意 - ';
    } elseif ($score >= 40) {
        $base = '📌 可作為配腳 - ';
    } else {
        $base = '⚠️ 需謹慎 - ';
    }
    
    $reasons = [];
    if ($distanceWinRate >= 20) $reasons[] = "🏇 同程勝率{$distanceWinRate}%";
    elseif ($distanceWinRate >= 10) $reasons[] = "📊 同程有經驗({$distanceWinRate}%)";
    if ($drawTop3Rate >= 40) $reasons[] = "🎯 此檔位表現佳({$drawTop3Rate}%)";
    if ($gearScore >= 4) $reasons[] = "🔧 配備變動利好";
    if ($jockeyScore >= 4) $reasons[] = "🏇 騎師配置佳";
    if ($freshenScore >= 3) $reasons[] = "🌿 新鮮感足";
    if ($odds <= 4 && $odds > 0) $reasons[] = "📈 熱門追捧";
    if ($odds >= 15) $reasons[] = "❄️ 冷門分子";
    
    $reasonText = !empty($reasons) ? ' [' . implode('] [', $reasons) . ']' : '';
    
    return $base . $reasonText;
}

/**
 * 批量计算一场赛事的优先级排名（增强版）
 */
function calculateRacePriorities($pdo, $raceId, $raceInfo) {
    // 获取该赛事的所有马匹数据（包含骑师、配备、负磅）
    $stmt = $pdo->prepare("
        SELECT 
            s.horse_code,
            s.runner_no,
            s.total_score as v2_score,
            s.win_odds_snapshot as current_odds,
            ru.barrier_draw_number as draw,
            ru.name_ch as horse_name,
            ru.jockey_name_ch as jockey_name,
            ru.trainer_name_ch as trainer_name,
            ru.gear_info as gear,
            ru.handicap_weight as weight
        FROM hkracing_v2_score s
        JOIN hkracing_runners ru ON ru.race_id = s.race_id AND ru.runner_no = s.runner_no
        WHERE s.race_id = ? 
          AND s.score_version = 2
    ");
    $stmt->execute([$raceId]);
    $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($scores)) {
        return [];
    }
    
    $priorities = [];
    foreach ($scores as $score) {
        $horse = [
            'horse_code' => $score['horse_code'],
            'draw' => $score['draw'],
            'current_odds' => floatval($score['current_odds']),
            'v2_score' => $score['v2_score'],
            'runner_no' => $score['runner_no'],
            'horse_name' => $score['horse_name'],
            'jockey_name' => $score['jockey_name'],
            'trainer_name' => $score['trainer_name'],
            'gear' => $score['gear'],
            'weight' => $score['weight']
        ];
        
        $result = calculateUnifiedPriority($horse, $raceInfo, $pdo);
        
        $priorities[] = [
            'runner_no' => $score['runner_no'],
            'horse_name' => $score['horse_name'],
            'draw' => $score['draw'],
            'current_odds' => floatval($score['current_odds']),
            'v2_score' => round($score['v2_score'], 1),
            'priority_score' => round($result['priority_score'], 1),
            'history_score' => round($result['history_score'], 1),
            'distance_win_rate' => round($result['distance_win_rate'], 1),
            'draw_top3_rate' => round($result['draw_top3_rate'], 1),  // 加上 round
            'recent_form' => $result['recent_form'],
            'suggestion' => $result['suggestion'],
            'breakdown' => array_map(function($v) { return round($v, 1); }, $result['breakdown'])
        ];
    }
    
    // 按优先分数排序（降序）
    usort($priorities, function($a, $b) {
        return $b['priority_score'] <=> $a['priority_score'];
    });
    
    return $priorities;
}

/**
 * 更新数据库中的 priority 字段
 * 
 * @param PDO $pdo 数据库连接
 * @param string $raceId 赛事ID
 * @param array $priorities 从 calculateRacePriorities 返回的优先级数组
 * @return int 更新的马匹数量
 */
function updateRacePriorityInDb($pdo, $raceId, $priorities) {
    $updatedCount = 0;
    
    foreach ($priorities as $index => $priority) {
        $rank = $index + 1;
        $stmt = $pdo->prepare("
            UPDATE hkracing_v2_score 
            SET priority = :priority, last_updated = NOW()
            WHERE race_id = :race_id AND runner_no = :runner_no AND score_version = 2
        ");
        $stmt->execute([
            ':priority' => $rank,
            ':race_id' => $raceId,
            ':runner_no' => $priority['runner_no']
        ]);
        $updatedCount++;
    }
    
    return $updatedCount;
}