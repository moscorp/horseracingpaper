<?php
/**
 * Mark Six Positional Prediction Functions
 * 13 methods, per-column prediction, backtestable
 * v3.0 - Added window optimization and confidence scores
 */

// ========== CONFIGURATION ==========
$minTrainingRows = 20;
$maxTrainingRows = 500;
$predictionMethods = [
    'WgtMA', 'Mode', 'ExpS', 'AutoCorr', 'Seasonal', 
    'FreqRec', 'KNN', 'Balance', 'SumTrend', 'Heatmap', 
    'Gap', 'Markov', 'LinReg'
];

/**
 * Get all historical draws ordered by ID (chronological)
 */
function getAllDraws($pdo) {
    $stmt = $pdo->query("SELECT id, year, nos, no1, no2, no3, no4, no5, no6, no7 FROM `00m6` ORDER BY id ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ============= STATISTICAL METHODS (with confidence scores) =============

/**
 * METHOD 1: Weighted Moving Average with confidence
 */
function predict_WgtMA($history, $windowSize = 10) {
    $recent = array_slice($history, -$windowSize);
    $n = count($recent);
    if ($n < 1) return ['prediction' => 25, 'confidence' => 0];
    
    $weights = range(1, $n);
    $weightedSum = 0;
    $weightSum = 0;
    for ($i = 0; $i < $n; $i++) {
        $weightedSum += $recent[$i] * $weights[$i];
        $weightSum += $weights[$i];
    }
    $prediction = round($weightedSum / $weightSum);
    $prediction = max(1, min(49, $prediction));
    
    // Calculate confidence based on variance
    $variance = (count($recent) > 1) ? stats_variance($recent) : 0;
    $confidence = max(0, min(100, 100 - ($variance / 2)));
    
    return ['prediction' => $prediction, 'confidence' => round($confidence, 1)];
}

/**
 * METHOD 2: Mode (Hot Numbers) with confidence
 */
function predict_Mode($history, $windowSize = 30) {
    $recent = array_slice($history, -$windowSize);
    $n = count($recent);
    if ($n < 1) return ['prediction' => 25, 'confidence' => 0];
    
    $freq = array_count_values($recent);
    arsort($freq);
    $prediction = key($freq);
    $maxCount = current($freq);
    
    $confidence = ($maxCount / $n) * 100;
    return ['prediction' => $prediction, 'confidence' => round($confidence, 1)];
}

/**
 * METHOD 3: Exponential Smoothing with confidence
 */
function predict_ExpS($history, $windowSize = 30, $alpha = 0.3) {
    $recent = array_slice($history, -$windowSize);
    $n = count($recent);
    if ($n < 2) {
        if ($n == 1) return ['prediction' => $recent[0], 'confidence' => 50];
        return ['prediction' => 25, 'confidence' => 0];
    }
    
    $smoothed = $recent[0];
    for ($i = 1; $i < $n; $i++) {
        $smoothed = $alpha * $recent[$i] + (1 - $alpha) * $smoothed;
    }
    $prediction = round($smoothed);
    $prediction = max(1, min(49, $prediction));
    
    // Calculate confidence based on residual error
    $residuals = [];
    $lastSmoothed = $recent[0];
    for ($i = 1; $i < $n; $i++) {
        $lastSmoothed = $alpha * $recent[$i] + (1 - $alpha) * $lastSmoothed;
        $residuals[] = abs($recent[$i] - $lastSmoothed);
    }
    $avgResidual = array_sum($residuals) / count($residuals);
    $confidence = max(0, min(100, 100 - ($avgResidual * 2)));
    
    return ['prediction' => $prediction, 'confidence' => round($confidence, 1)];
}

/**
 * METHOD 4: Autocorrelation with confidence
 */
function predict_AutoCorr($history, $windowSize = 30) {
    $series = array_slice($history, -$windowSize);
    $n = count($series);
    $maxLag = min(20, floor($n / 2));
    
    if ($n < 5 || $maxLag < 1) {
        return predict_Mode($history, 20);
    }
    
    $bestLag = 1;
    $bestCorr = -1;
    
    for ($lag = 1; $lag <= $maxLag; $lag++) {
        $x = array_slice($series, 0, $n - $lag);
        $y = array_slice($series, $lag, $n - $lag);
        $corr = correlation($x, $y);
        if (abs($corr) > abs($bestCorr)) {
            $bestCorr = $corr;
            $bestLag = $lag;
        }
    }
    
    $prediction = $series[$bestLag] ?? $series[0];
    $confidence = abs($bestCorr) * 100;
    
    return ['prediction' => $prediction, 'confidence' => round($confidence, 1)];
}

/**
 * METHOD 5: Seasonal Pattern with confidence
 */
function predict_Seasonal($history, $windowSize = 60) {
    $series = array_slice($history, -$windowSize);
    $n = count($series);
    $maxPeriod = min(30, floor($n / 2));
    
    if ($maxPeriod < 2) {
        return predict_Mode($history, 20);
    }
    
    $bestPeriod = 1;
    $bestScore = -1;
    
    for ($period = 2; $period <= $maxPeriod; $period++) {
        $nCycles = floor($n / $period);
        if ($nCycles < 2) continue;
        
        $scores = [];
        for ($i = 0; $i < $nCycles - 1; $i++) {
            $cycle1 = array_slice($series, $i * $period, $period);
            $cycle2 = array_slice($series, ($i + 1) * $period, $period);
            if (count($cycle1) != count($cycle2)) continue;
            
            $dist = 0;
            for ($j = 0; $j < $period; $j++) {
                $dist += abs($cycle1[$j] - $cycle2[$j]);
            }
            $dist = $dist / ($period * 49);
            $scores[] = 1 - $dist;
        }
        
        if (!empty($scores)) {
            $avgScore = array_sum($scores) / count($scores);
            if ($avgScore > $bestScore) {
                $bestScore = $avgScore;
                $bestPeriod = $period;
            }
        }
    }
    
    if ($bestPeriod < $n) {
        $position = ($n - 1) % $bestPeriod;
        $prediction = $series[$position];
    } else {
        $prediction = $series[0];
    }
    
    $confidence = $bestScore * 100;
    return ['prediction' => $prediction, 'confidence' => round($confidence, 1)];
}

/**
 * METHOD 6: Frequency + Recency with confidence
 */
function predict_FreqRec($history, $windowSize = 50) {
    $series = array_slice($history, -$windowSize);
    $n = count($series);
    if ($n < 1) return ['prediction' => 25, 'confidence' => 0];
    
    $scores = [];
    foreach ($series as $i => $num) {
        $recencyWeight = 1.0 / (1 + $i * 0.1);
        $scores[$num] = ($scores[$num] ?? 0) + $recencyWeight;
    }
    
    arsort($scores);
    $prediction = key($scores);
    $confidence = ($scores[$prediction] / array_sum($scores)) * 100;
    
    return ['prediction' => $prediction, 'confidence' => round($confidence, 1)];
}

/**
 * METHOD 7: K-Nearest Neighbor with confidence
 */
function predict_KNN($history, $windowSize = 50, $k = 5, $patternLen = 5) {
    $series = array_slice($history, -$windowSize);
    $n = count($series);
    
    if ($n < $patternLen + $k + 5) {
        return predict_Mode($history, 20);
    }
    
    $current = array_slice($series, 0, $patternLen);
    $distances = [];
    
    for ($i = $patternLen; $i < $n - 1; $i++) {
        $candidate = array_slice($series, $i, $patternLen);
        if (count($candidate) == $patternLen) {
            $dist = 0;
            for ($j = 0; $j < $patternLen; $j++) {
                $dist += abs($current[$j] - $candidate[$j]);
            }
            $dist = $dist / ($patternLen * 49);
            $distances[] = ['dist' => $dist, 'value' => $series[$i - 1] ?? 25];
        }
    }
    
    if (empty($distances)) {
        return predict_Mode($history, 20);
    }
    
    usort($distances, function($a, $b) {
        return $a['dist'] <=> $b['dist'];
    });
    
    $neighbors = array_slice($distances, 0, $k);
    $neighborValues = array_column($neighbors, 'value');
    sort($neighborValues);
    $median = $neighborValues[floor($k / 2)];
    $prediction = max(1, min(49, round($median)));
    $confidence = max(0, 100 - ($distances[0]['dist'] * 200));
    
    return ['prediction' => $prediction, 'confidence' => round($confidence, 1)];
}

/**
 * METHOD 8: Balance with confidence
 */
function predict_Balance($history, $windowSize = 30) {
    $series = array_slice($history, -$windowSize);
    $n = count($series);
    if ($n == 0) return ['prediction' => 25, 'confidence' => 0];
    
    $oddCount = 0;
    $highCount = 0;
    foreach ($series as $num) {
        if ($num % 2 == 1) $oddCount++;
        if ($num >= 25) $highCount++;
    }
    
    $oddRatio = $oddCount / $n;
    $highRatio = $highCount / $n;
    
    $base = 25;
    if ($oddRatio > 0.55) {
        $base -= ($oddRatio - 0.5) * 20;
    } elseif ($oddRatio < 0.45) {
        $base += (0.5 - $oddRatio) * 20;
    }
    if ($highRatio > 0.55) {
        $base -= ($highRatio - 0.5) * 20;
    } elseif ($highRatio < 0.45) {
        $base += (0.5 - $highRatio) * 20;
    }
    
    $prediction = max(1, min(49, round($base)));
    $confidence = (1 - abs($oddRatio - 0.5) - abs($highRatio - 0.5)) * 100;
    $confidence = max(0, min(100, $confidence));
    
    return ['prediction' => $prediction, 'confidence' => round($confidence, 1)];
}

/**
 * METHOD 9: Sum Trend with confidence
 */
function predict_SumTrend($history, $windowSize = 50) {
    $series = array_slice($history, -$windowSize);
    $n = count($series);
    $lookback = max(7, floor($n / 4));
    
    if ($n < $lookback + 2) {
        return predict_Mode($history, 20);
    }
    
    $recent = array_slice($series, 0, $lookback + 7);
    $sums = [];
    for ($i = 0; $i < count($recent) - 7; $i++) {
        $sums[] = array_sum(array_slice($recent, $i, 7));
    }
    
    if (count($sums) < 2) {
        return predict_Mode($history, 20);
    }
    
    $avgSum = array_sum($sums) / count($sums);
    $lastSum = $sums[0];
    
    if ($lastSum > $avgSum * 1.05) {
        $nextSum = $lastSum * 0.98;
    } elseif ($lastSum < $avgSum * 0.95) {
        $nextSum = $lastSum * 1.02;
    } else {
        $nextSum = $avgSum;
    }
    
    // Distribute across 7 positions
    $colAvgs = [];
    for ($colIdx = 0; $colIdx < 7; $colIdx++) {
        $colVals = [];
        for ($j = $colIdx; $j < $lookback; $j += 7) {
            if (isset($series[$j])) $colVals[] = $series[$j];
        }
        if (empty($colVals)) $colVals = array_slice($series, 0, $lookback);
        $colAvgs[] = array_sum($colVals) / count($colVals);
    }
    
    $total = array_sum($colAvgs);
    $proportions = ($total > 0) ? array_map(function($v) use ($total) { return $v / $total; }, $colAvgs) : array_fill(0, 7, 1/7);
    $predictions = array_map(function($p) use ($nextSum) { return max(1, min(49, round($nextSum * $p))); }, $proportions);
    $prediction = round(array_sum($predictions) / 7);
    $confidence = 30; // Fixed heuristic
    
    return ['prediction' => $prediction, 'confidence' => $confidence];
}

/**
 * METHOD 10: Heatmap Zones with confidence
 */
function predict_Heatmap($history, $windowSize = 30) {
    $series = array_slice($history, -$windowSize);
    $n = count($series);
    if ($n == 0) return ['prediction' => 25, 'confidence' => 0];
    
    $zones = [
        '1-10' => 0, '11-20' => 0, '21-30' => 0, '31-40' => 0, '41-49' => 0
    ];
    
    foreach ($series as $num) {
        if ($num <= 10) $zones['1-10']++;
        elseif ($num <= 20) $zones['11-20']++;
        elseif ($num <= 30) $zones['21-30']++;
        elseif ($num <= 40) $zones['31-40']++;
        else $zones['41-49']++;
    }
    
    $expected = $n / 5;
    $zoneScores = [];
    foreach ($zones as $zone => $count) {
        $zoneScores[$zone] = $count - $expected;
    }
    arsort($zoneScores);
    $hottestZone = key($zoneScores);
    
    if ($zoneScores[$hottestZone] > 0) {
        list($lo, $hi) = explode('-', $hottestZone);
        $zoneVals = array_filter($series, function($num) use ($lo, $hi) { return $num >= $lo && $num <= $hi; });
        if (!empty($zoneVals)) {
            $freq = array_count_values($zoneVals);
            arsort($freq);
            $prediction = key($freq);
        } else {
            $prediction = ($lo + $hi) / 2;
        }
    } else {
        $prediction = 25;
    }
    
    $confidence = 30;
    return ['prediction' => $prediction, 'confidence' => $confidence];
}

/**
 * METHOD 11: Gap (Overdue) with confidence
 */
function predict_Gap($history, $windowSize = 50) {
    $series = array_slice($history, -$windowSize);
    $n = count($series);
    if ($n < 10) return ['prediction' => 25, 'confidence' => 10];
    
    $lastOccurrence = array_fill(1, 49, $n + 1);
    foreach ($series as $idx => $num) {
        $lastOccurrence[$num] = $idx;
    }
    
    $gaps = [];
    for ($num = 1; $num <= 49; $num++) {
        $pos = $lastOccurrence[$num];
        if ($pos < $n) {
            for ($j = $pos + 1; $j < $n; $j++) {
                if ($series[$j] == $num) {
                    $gaps[] = $j - $pos;
                    break;
                }
            }
        }
    }
    
    $avgGap = !empty($gaps) ? array_sum($gaps) / count($gaps) : 10;
    $overdue = [];
    for ($num = 1; $num <= 49; $num++) {
        if ($lastOccurrence[$num] > $avgGap * 1.5) {
            $overdue[] = ['num' => $num, 'gap' => $lastOccurrence[$num]];
        }
    }
    
    if (!empty($overdue)) {
        usort($overdue, function($a, $b) { return $b['gap'] <=> $a['gap']; });
        $prediction = $overdue[0]['num'];
        $confidence = min(100, ($overdue[0]['gap'] / $avgGap) * 30);
    } else {
        $maxNum = 1;
        $maxGap = 0;
        for ($num = 1; $num <= 49; $num++) {
            if ($lastOccurrence[$num] > $maxGap && $lastOccurrence[$num] <= $n) {
                $maxGap = $lastOccurrence[$num];
                $maxNum = $num;
            }
        }
        $prediction = $maxNum;
        $confidence = 20;
    }
    
    return ['prediction' => $prediction, 'confidence' => round($confidence, 1)];
}

/**
 * METHOD 12: Markov Chain with confidence
 */
function predict_Markov($history, $windowSize = 30) {
    $series = array_slice($history, -$windowSize);
    $n = count($series);
    if ($n < 3) {
        if ($n > 0) return ['prediction' => $series[0], 'confidence' => 0];
        return ['prediction' => 25, 'confidence' => 0];
    }
    
    $transitions = [];
    for ($i = 0; $i < $n - 1; $i++) {
        $current = $series[$i];
        $next = $series[$i + 1];
        if (!isset($transitions[$current])) $transitions[$current] = [];
        $transitions[$current][$next] = ($transitions[$current][$next] ?? 0) + 1;
    }
    
    $lastNum = $series[0];
    if (isset($transitions[$lastNum])) {
        arsort($transitions[$lastNum]);
        $prediction = key($transitions[$lastNum]);
        $total = array_sum($transitions[$lastNum]);
        $confidence = ($transitions[$lastNum][$prediction] / $total) * 100;
    } else {
        $freq = array_count_values($series);
        arsort($freq);
        $prediction = key($freq);
        $confidence = ($freq[$prediction] / $n) * 100;
    }
    
    return ['prediction' => $prediction, 'confidence' => round($confidence, 1)];
}

/**
 * METHOD 13: Linear Regression with confidence
 */
function predict_LinReg($history, $windowSize = 30) {
    $series = array_slice($history, -$windowSize);
    $n = count($series);
    if ($n < 3) {
        return predict_Mode($history, 20);
    }
    
    $x = range(0, $n - 1);
    $y = array_reverse($series);
    
    $xMean = array_sum($x) / $n;
    $yMean = array_sum($y) / $n;
    
    $numerator = 0;
    $denominator = 0;
    for ($i = 0; $i < $n; $i++) {
        $numerator += ($x[$i] - $xMean) * ($y[$i] - $yMean);
        $denominator += pow($x[$i] - $xMean, 2);
    }
    
    if ($denominator != 0) {
        $slope = $numerator / $denominator;
        $intercept = $yMean - $slope * $xMean;
        $predictionRaw = $intercept + $slope * $n;
    } else {
        $predictionRaw = $yMean;
    }
    
    $prediction = round($predictionRaw);
    $prediction = max(1, min(49, $prediction));
    
    // Calculate R-squared for confidence
    $predictedYs = array_map(function($xi) use ($intercept, $slope) { return $intercept + $slope * $xi; }, $x);
    $ssRes = 0;
    $ssTot = 0;
    for ($i = 0; $i < $n; $i++) {
        $ssRes += pow($y[$i] - $predictedYs[$i], 2);
        $ssTot += pow($y[$i] - $yMean, 2);
    }
    $rSquared = ($ssTot > 0) ? max(0, 1 - ($ssRes / $ssTot)) : 0;
    $confidence = $rSquared * 100;
    
    return ['prediction' => $prediction, 'confidence' => round($confidence, 1)];
}

/**
 * Helper: Calculate variance
 */
function stats_variance($values) {
    $n = count($values);
    if ($n < 2) return 0;
    $mean = array_sum($values) / $n;
    $variance = 0;
    foreach ($values as $v) {
        $variance += pow($v - $mean, 2);
    }
    return $variance / ($n - 1);
}

/**
 * Helper: Pearson correlation
 */
function correlation($arr1, $arr2) {
    $n = count($arr1);
    if ($n != count($arr2) || $n < 2) return 0;
    
    $mean1 = array_sum($arr1) / $n;
    $mean2 = array_sum($arr2) / $n;
    
    $sumXY = 0;
    $sumX2 = 0;
    $sumY2 = 0;
    
    for ($i = 0; $i < $n; $i++) {
        $dx = $arr1[$i] - $mean1;
        $dy = $arr2[$i] - $mean2;
        $sumXY += $dx * $dy;
        $sumX2 += $dx * $dx;
        $sumY2 += $dy * $dy;
    }
    
    $denom = sqrt($sumX2 * $sumY2);
    return $denom == 0 ? 0 : $sumXY / $denom;
}

/**
 * Run a single prediction using specified method (returns array with prediction and confidence)
 */
function predictColumnWithConfidence($history, $method, $windowSize = 30) {
    $functionName = "predict_$method";
    if (function_exists($functionName)) {
        return $functionName($history, $windowSize);
    }
    return predict_WgtMA($history, 10);
}

/**
 * Backtest for a specific column to find optimal window
 */
/**
 * Backtest for a specific column to find optimal window
 * Uses ALL available data with dynamic limit
 */
function backtestColumnForOptimalWindow($pdo, $method, $column, $maxWindow = null) {
    $draws = getAllDraws($pdo);
    $values = array_column($draws, $column);
    $totalDraws = count($values);
    
    // Dynamic max window: use 80% of available data, up to 500
    if ($maxWindow === null) {
        $maxWindow = min(500, floor($totalDraws * 0.8));
    }
    
    if ($totalDraws < 20) return null;
    
    $bestWindow = 20;
    $bestError = INF;
    $bestPrediction = null;
    
    // Start from 10, go up to maxWindow with adaptive step size
    $stepSize = ($maxWindow > 200) ? 10 : 5;
    
    for ($window = 20; $window <= $maxWindow; $window += $stepSize) {
        $history = array_slice($values, 0, $window);
        
        // Use next draw after the window as test
        $actualIndex = $window;
        if ($actualIndex >= $totalDraws) break;
        
        $actual = $values[$actualIndex];
        
        $result = predictColumnWithConfidence($history, $method, $window);
        $predicted = $result['prediction'];
        $error = abs($predicted - $actual);
        
        // Also track near-hit (within 3) for better optimization
        $nearHit = ($error <= 3) ? 1 : 0;
        
        // Composite score: prioritize exact matches, then near hits
        $score = ($error == 0) ? 0 : ($error + (1 - $nearHit) * 2);
        
        if ($score < $bestError) {
            $bestError = $score;
            $bestWindow = $window;
            $bestPrediction = $predicted;
        }
    }
    
    // Also test 80%, 90%, 100% of data as special cases
    $specialWindows = [floor($totalDraws * 0.5), floor($totalDraws * 0.7), floor($totalDraws * 0.9)];
    foreach ($specialWindows as $window) {
        if ($window <= $maxWindow && $window > 0 && $window != $bestWindow) {
            $history = array_slice($values, 0, $window);
            $actualIndex = $window;
            if ($actualIndex < $totalDraws) {
                $actual = $values[$actualIndex];
                $result = predictColumnWithConfidence($history, $method, $window);
                $error = abs($result['prediction'] - $actual);
                $nearHit = ($error <= 3) ? 1 : 0;
                $score = ($error == 0) ? 0 : ($error + (1 - $nearHit) * 2);
                
                if ($score < $bestError) {
                    $bestError = $score;
                    $bestWindow = $window;
                    $bestPrediction = $result['prediction'];
                }
            }
        }
    }
    
    return [
        'optimal_window' => $bestWindow,
        'error' => $bestError,
        'prediction' => $bestPrediction,
        'total_available' => $totalDraws,
        'max_tested' => $maxWindow
    ];
}

/**
 * Run full backtest with window optimization
 */
/**
 * Run full backtest with window optimization - NO HARD LIMIT
 */
function runFullBacktestOptimized($pdo) {
    global $predictionMethods;
    
    $draws = getAllDraws($pdo);
    $totalDraws = count($draws);
    $columns = ['no1', 'no2', 'no3', 'no4', 'no5', 'no6', 'no7'];
    
    echo "Total draws available: {$totalDraws}\n";
    echo "Maximum window to test: " . min(500, floor($totalDraws * 0.8)) . "\n\n";
    
    $resultsSummary = [];
    
    foreach ($predictionMethods as $method) {
        echo "\nðŸ“Š Testing method: $method\n";
        echo str_repeat("-", 40) . "\n";
        
        foreach ($columns as $col) {
            echo "  Optimizing: $col ... ";
            
            $optimal = backtestColumnForOptimalWindow($pdo, $method, $col);
            
            if ($optimal) {
                $resultsSummary[$method][$col] = $optimal['optimal_window'];
                echo "âœ“ optimal window = {$optimal['optimal_window']} draws (error: {$optimal['error']})\n";
                
                // Store in database
                $stmt = $pdo->prepare("
                    INSERT INTO m6_backtest_accuracy 
                    (method, training_rows, target_column, mae, hit_rate, near_hit_rate, tested_draws, last_updated)
                    VALUES (:method, :rows, :col, :mae, 0, 0, 1, NOW())
                    ON DUPLICATE KEY UPDATE
                    mae = VALUES(mae),
                    training_rows = VALUES(training_rows),
                    last_updated = NOW()
                ");
                $stmt->execute([
                    ':method' => $method,
                    ':rows' => $optimal['optimal_window'],
                    ':col' => $col,
                    ':mae' => $optimal['error']
                ]);
            } else {
                echo "âœ— failed (insufficient data)\n";
            }
        }
    }
    
    // Print summary table
    echo "\n\n" . str_repeat("=", 60) . "\n";
    echo "OPTIMAL WINDOWS SUMMARY\n";
    echo str_repeat("=", 60) . "\n";
    echo sprintf("%-12s", "Method");
    foreach ($columns as $col) {
        echo sprintf("%6s", $col);
    }
    echo "\n" . str_repeat("-", 60) . "\n";
    
    foreach ($predictionMethods as $method) {
        echo sprintf("%-12s", $method);
        foreach ($columns as $col) {
            $window = $resultsSummary[$method][$col] ?? '-';
            echo sprintf("%6s", $window);
        }
        echo "\n";
    }
    echo str_repeat("=", 60) . "\n";
    
    echo "Optimized backtest complete.\n";
}

/**
 * Generate final prediction for next draw using optimal windows
 */
// function predictNextDrawOptimized($pdo) {
//     global $predictionMethods;
    
//     $draws = getAllDraws($pdo);
//     $nextDrawId = count($draws) + 1;
//     $columns = ['no1', 'no2', 'no3', 'no4', 'no5', 'no6', 'no7'];
    
//     // Clear old predictions
//     $stmt = $pdo->prepare("DELETE FROM m6_position_predictions WHERE target_draw_id = :draw_id");
//     $stmt->execute([':draw_id' => $nextDrawId]);
    
//     foreach ($predictionMethods as $method) {
//         $predRow = [];
        
//         foreach ($columns as $col) {
//             // Get optimal training rows from backtest
//             $stmt = $pdo->prepare("
//                 SELECT training_rows FROM m6_backtest_accuracy 
//                 WHERE method = :method AND target_column = :col 
//                 ORDER BY mae ASC LIMIT 1
//             ");
//             $stmt->execute([':method' => $method, ':col' => $col]);
//             $opt = $stmt->fetch(PDO::FETCH_ASSOC);
//             $rows = $opt ? $opt['training_rows'] : 30;
            
//             $history = array_column($draws, $col);
//             $history = array_slice($history, -$rows);
//             $result = predictColumnWithConfidence($history, $method, $rows);
//             $predRow[$col] = $result['prediction'];
//         }
        
//         // Store prediction
//         $stmt = $pdo->prepare("
//             INSERT INTO m6_position_predictions 
//             (target_draw_id, method, training_rows, 
//              pred_no1, pred_no2, pred_no3, pred_no4, pred_no5, pred_no6, pred_no7)
//             VALUES 
//             (:draw_id, :method, :rows,
//              :no1, :no2, :no3, :no4, :no5, :no6, :no7)
//         ");
//         $stmt->execute([
//             ':draw_id' => $nextDrawId,
//             ':method' => $method,
//             ':rows' => 30,
//             ':no1' => $predRow['no1'],
//             ':no2' => $predRow['no2'],
//             ':no3' => $predRow['no3'],
//             ':no4' => $predRow['no4'],
//             ':no5' => $predRow['no5'],
//             ':no6' => $predRow['no6'],
//             ':no7' => $predRow['no7']
//         ]);
//     }
    
//     // Get consensus
//     $finalTicket = [];
//     foreach ($columns as $col) {
//         $stmt = $pdo->prepare("
//             SELECT pred_$col as value, COUNT(*) as cnt
//             FROM m6_position_predictions
//             WHERE target_draw_id = :draw_id AND pred_$col IS NOT NULL
//             GROUP BY pred_$col
//             ORDER BY cnt DESC
//             LIMIT 1
//         ");
//         $stmt->execute([':draw_id' => $nextDrawId]);
//         $result = $stmt->fetch(PDO::FETCH_ASSOC);
//         $finalTicket[$col] = $result ? $result['value'] : 24;
//     }
    
//     return [
//         'draw_id' => $nextDrawId,
//         'final_ticket' => $finalTicket
//     ];
// }
function predictNextDrawOptimized($pdo) {
    global $predictionMethods;
    
    $draws = getAllDraws($pdo);
    $lastDraw = end($draws);
    $nextDrawId = $lastDraw['id'] + 1;
    $nextYear = $lastDraw['year'];
    $nextNos = $lastDraw['nos'] + 1;
    
    if ($nextNos > 999) {
        $nextNos = 1;
        $nextYear++;
    }
    
    $columns = ['no1', 'no2', 'no3', 'no4', 'no5', 'no6', 'no7'];
    
    // Clear old predictions
    $stmt = $pdo->prepare("DELETE FROM m6_position_predictions WHERE target_draw_id = :draw_id");
    $stmt->execute([':draw_id' => $nextDrawId]);
    
    foreach ($predictionMethods as $method) {
        $predRow = [];
        
        foreach ($columns as $col) {
            $stmt = $pdo->prepare("
                SELECT training_rows FROM m6_backtest_accuracy 
                WHERE method = :method AND target_column = :col 
                ORDER BY mae ASC LIMIT 1
            ");
            $stmt->execute([':method' => $method, ':col' => $col]);
            $opt = $stmt->fetch(PDO::FETCH_ASSOC);
            $rows = $opt ? $opt['training_rows'] : 30;
            
            $history = array_column($draws, $col);
            $history = array_slice($history, -$rows);
            $result = predictColumnWithConfidence($history, $method, $rows);
            $predRow[$col] = $result['prediction'];
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO m6_position_predictions 
            (target_draw_id, draw_year, draw_nos, method, training_rows, 
             pred_no1, pred_no2, pred_no3, pred_no4, pred_no5, pred_no6, pred_no7)
            VALUES 
            (:draw_id, :year, :nos, :method, :rows,
             :no1, :no2, :no3, :no4, :no5, :no6, :no7)
        ");
        $stmt->execute([
            ':draw_id' => $nextDrawId,
            ':year' => $nextYear,
            ':nos' => $nextNos,
            ':method' => $method,
            ':rows' => 30,
            ':no1' => $predRow['no1'],
            ':no2' => $predRow['no2'],
            ':no3' => $predRow['no3'],
            ':no4' => $predRow['no4'],
            ':no5' => $predRow['no5'],
            ':no6' => $predRow['no6'],
            ':no7' => $predRow['no7']
        ]);
    }
    
    // Get consensus
    $finalTicket = [];
    foreach ($columns as $col) {
        $stmt = $pdo->prepare("
            SELECT pred_$col as value, COUNT(*) as cnt
            FROM m6_position_predictions
            WHERE target_draw_id = :draw_id AND pred_$col IS NOT NULL
            GROUP BY pred_$col
            ORDER BY cnt DESC
            LIMIT 1
        ");
        $stmt->execute([':draw_id' => $nextDrawId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $finalTicket[$col] = $result ? $result['value'] : 24;
    }
    
    return [
        'draw_id' => $nextDrawId,
        'draw_year' => $nextYear,
        'draw_nos' => $nextNos,
        'final_ticket' => $finalTicket
    ];
}
// Keep original functions for backward compatibility
function predictColumn($history, $method, $params = []) {
    $result = predictColumnWithConfidence($history, $method, $params[0] ?? 30);
    return $result['prediction'];
}

function runFullBacktest($pdo) {
    runFullBacktestOptimized($pdo);
}

function predictNextDraw($pdo) {
    return predictNextDrawOptimized($pdo);
}

function getConsensusPrediction($pdo, $drawId) {
    $consensus = [];
    $columns = ['no1', 'no2', 'no3', 'no4', 'no5', 'no6', 'no7'];
    
    foreach ($columns as $col) {
        $stmt = $pdo->prepare("
            SELECT pred_$col as value, COUNT(*) as agreement
            FROM m6_position_predictions
            WHERE target_draw_id = :draw_id AND pred_$col IS NOT NULL
            GROUP BY pred_$col
            ORDER BY agreement DESC
            LIMIT 1
        ");
        $stmt->execute([':draw_id' => $drawId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $consensus[$col] = $result ? $result['value'] : 24;
    }
    
    return $consensus;
}

function getOptimalConfig($pdo) {
    $stmt = $pdo->query("
        SELECT method, target_column, training_rows, mae
        FROM m6_backtest_accuracy a
        WHERE mae = (
            SELECT MIN(mae) 
            FROM m6_backtest_accuracy b 
            WHERE b.method = a.method AND b.target_column = a.target_column
        )
        ORDER BY method, target_column
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get optimal window from queue results (used by prediction)
 */
function getOptimalWindowFromQueue($pdo, $method, $column) {
    $stmt = $pdo->prepare("
        SELECT optimal_window 
        FROM m6_backtest_queue 
        WHERE method = :method AND `column` = :col AND status = 'completed'
        ORDER BY completed_at DESC
        LIMIT 1
    ");
    $stmt->execute([':method' => $method, ':col' => $column]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['optimal_window'] : 30;
}

/**
 * Run optimized backtest - finds true optimal windows for ALL methods
 * Takes ~1.4 seconds total
 */
/**
 * Run optimized backtest - finds true optimal windows for ALL methods
 * Takes ~1-2 seconds total
 */
function runOptimizedBacktest($pdo) {
    global $predictionMethods;
    $columns = ['no1', 'no2', 'no3', 'no4', 'no5', 'no6', 'no7'];
    $draws = getAllDraws($pdo);
    $totalDraws = count($draws);
    
    echo "Total draws: {$totalDraws}\n";
    
    foreach ($predictionMethods as $method) {
        echo "  {$method}: ";
        
        foreach ($columns as $col) {
            $values = array_column($draws, $col);
            $optimal = findOptimalWindowFast($values, $method, $totalDraws);
            
            // Store in database
            $stmt = $pdo->prepare("
                INSERT INTO m6_backtest_accuracy 
                (method, training_rows, target_column, mae, tested_draws, last_updated)
                VALUES (:method, :rows, :col, :mae, :tested, NOW())
                ON DUPLICATE KEY UPDATE
                training_rows = VALUES(training_rows),
                mae = VALUES(mae),
                last_updated = NOW()
            ");
            $stmt->execute([
                ':method' => $method,
                ':rows' => $optimal['window'],
                ':col' => $col,
                ':mae' => $optimal['error'],
                ':tested' => $optimal['tested']
            ]);
            
            echo "{$col}:{$optimal['window']} ";
        }
        echo "\n";
    }
    
    echo "✅ Backtest complete!\n";
}

/**
 * Find optimal window - optimized for speed
 */
 
//round.................. 
// function findOptimalWindowFast($values, $method, $totalDraws) {
//     $minWindow = 10;
//     $maxWindow = min(400, floor($totalDraws * 0.8));
    
//     $bestWindow = $minWindow;
//     $bestError = INF;
    
//     // Progressive scanning (finds true optimal quickly)
//     // Phase 1: Every 10 windows
//     for ($w = $minWindow; $w <= $maxWindow; $w += 10) {
//         $error = quickTestWindow($values, $method, $w, $totalDraws);
//         if ($error < $bestError) {
//             $bestError = $error;
//             $bestWindow = $w;
//         }
//     }
    
//     // Phase 2: Fine scan ±10
//     $start = max($minWindow, $bestWindow - 10);
//     $end = min($maxWindow, $bestWindow + 10);
//     for ($w = $start; $w <= $end; $w++) {
//         $error = quickTestWindow($values, $method, $w, $totalDraws);
//         if ($error < $bestError) {
//             $bestError = $error;
//             $bestWindow = $w;
//         }
//     }
    
//     return [
//         'window' => $bestWindow,
//         'error' => round($bestError, 2),
//         'tested' => ceil(($maxWindow - $minWindow) / 10) + 20
//     ];
// }

//no round...........
function findOptimalWindowFast($values, $method, $totalDraws) {
    $minWindow = 10;
    $maxWindow = min(400, floor($totalDraws * 0.8));
    
    $bestWindow = $minWindow;
    $bestError = INF;
    
    // PHASE 1: Every 5 windows (instead of 10) - gives 38, 43, etc.
    for ($w = $minWindow; $w <= $maxWindow; $w += 5) {
        $error = quickTestWindow($values, $method, $w, $totalDraws);
        if ($error < $bestError) {
            $bestError = $error;
            $bestWindow = $w;
        }
    }
    
    // PHASE 2: Fine scan ±10 with step 1 (gives exact numbers)
    $start = max($minWindow, $bestWindow - 10);
    $end = min($maxWindow, $bestWindow + 10);
    for ($w = $start; $w <= $end; $w++) {
        $error = quickTestWindow($values, $method, $w, $totalDraws);
        if ($error < $bestError) {
            $bestError = $error;
            $bestWindow = $w;
        }
    }
    
    return [
        'window' => $bestWindow,  // Now can be 37, 42, 116, 247!
        'error' => round($bestError, 2),
        'tested' => ceil(($maxWindow - $minWindow) / 5) + 20
    ];
}

//Precision....................
// function findOptimalWindowFast($values, $method, $totalDraws) {
//     $minWindow = 10;
//     $maxWindow = min(400, floor($totalDraws * 0.8));
    
//     $bestWindow = $minWindow;
//     $bestError = INF;
    
//     // Phase 1: Coarse scan every 5 windows
//     for ($w = $minWindow; $w <= $maxWindow; $w += 5) {
//         $error = quickTestWindow($values, $method, $w, $totalDraws);
//         if ($error < $bestError) {
//             $bestError = $error;
//             $bestWindow = $w;
//         }
//     }
    
//     // Phase 2: Fine scan ±15 with step 1
//     $start = max($minWindow, $bestWindow - 15);
//     $end = min($maxWindow, $bestWindow + 15);
//     for ($w = $start; $w <= $end; $w++) {
//         $error = quickTestWindow($values, $method, $w, $totalDraws);
//         if ($error < $bestError) {
//             $bestError = $error;
//             $bestWindow = $w;
//         }
//     }
    
//     return [
//         'window' => $bestWindow,
//         'error' => round($bestError, 2),
//         'tested' => ceil(($maxWindow - $minWindow) / 5) + 30
//     ];
// }

/**
 * Quick test - uses last 3 draws only (blazing fast)
 */
// function quickTestWindow($values, $method, $window, $totalDraws) {
//     $totalError = 0;
//     $testCount = 0;
    
//     // Test on last 3 draws only (enough for accuracy, fast)
//     for ($i = $totalDraws - 4; $i < $totalDraws - 1; $i++) {
//         if ($i < $window) continue;
        
//         $history = array_slice($values, $i - $window, $window);
//         $actual = $values[$i];
//         $result = predictColumnWithConfidence($history, $method, $window);
//         $totalError += abs($result['prediction'] - $actual);
//         $testCount++;
//     }
    
//     return $testCount > 0 ? $totalError / $testCount : INF;
// }

/**
 * Find optimal window - optimized for speed
 */
// function findOptimalWindowFast($values, $method, $totalDraws) {
//     $minWindow = 10;
//     $maxWindow = min(400, floor($totalDraws * 0.8));
    
//     $bestWindow = $minWindow;
//     $bestError = INF;
    
//     // Phase 1: Every 10 windows
//     for ($w = $minWindow; $w <= $maxWindow; $w += 10) {
//         $error = quickTestWindow($values, $method, $w, $totalDraws);
//         if ($error < $bestError) {
//             $bestError = $error;
//             $bestWindow = $w;
//         }
//     }
    
//     // Phase 2: Fine scan ±10
//     $start = max($minWindow, $bestWindow - 10);
//     $end = min($maxWindow, $bestWindow + 10);
//     for ($w = $start; $w <= $end; $w++) {
//         $error = quickTestWindow($values, $method, $w, $totalDraws);
//         if ($error < $bestError) {
//             $bestError = $error;
//             $bestWindow = $w;
//         }
//     }
    
//     return [
//         'window' => $bestWindow,
//         'error' => round($bestError, 2),
//         'tested' => ceil(($maxWindow - $minWindow) / 10) + 20
//     ];
// }

/**
 * Quick test - uses last 3 draws only (blazing fast)
 */
function quickTestWindow($values, $method, $window, $totalDraws) {
    $totalError = 0;
    $testCount = 0;
    
    for ($i = $totalDraws - 4; $i < $totalDraws - 1; $i++) {
        if ($i < $window) continue;
        
        $history = array_slice($values, $i - $window, $window);
        $actual = $values[$i];
        $result = predictColumnWithConfidence($history, $method, $window);
        $totalError += abs($result['prediction'] - $actual);
        $testCount++;
    }
    
    return $testCount > 0 ? $totalError / $testCount : INF;
}

/**
 * Run optimized backtest - finds true optimal windows for ALL methods
 */
// function runOptimizedBacktest($pdo) {
//     global $predictionMethods;
//     $columns = ['no1', 'no2', 'no3', 'no4', 'no5', 'no6', 'no7'];
//     $draws = getAllDraws($pdo);
//     $totalDraws = count($draws);
    
//     echo "Total draws: {$totalDraws}\n";
    
//     foreach ($predictionMethods as $method) {
//         echo "  {$method}: ";
        
//         foreach ($columns as $col) {
//             $values = array_column($draws, $col);
//             $optimal = findOptimalWindowFast($values, $method, $totalDraws);
            
//             $stmt = $pdo->prepare("
//                 INSERT INTO m6_backtest_accuracy 
//                 (method, training_rows, target_column, mae, tested_draws, last_updated)
//                 VALUES (:method, :rows, :col, :mae, :tested, NOW())
//                 ON DUPLICATE KEY UPDATE
//                 training_rows = VALUES(training_rows),
//                 mae = VALUES(mae),
//                 last_updated = NOW()
//             ");
//             $stmt->execute([
//                 ':method' => $method,
//                 ':rows' => $optimal['window'],
//                 ':col' => $col,
//                 ':mae' => $optimal['error'],
//                 ':tested' => $optimal['tested']
//             ]);
            
//             echo "{$col}:{$optimal['window']} ";
//         }
//         echo "\n";
//     }
    
//     echo "✅ Backtest complete!\n";
// }

/**
 * Run simulated backtest - tests each method on ALL historical draws
 * Returns accuracy stats for each method across the entire dataset
 */
function runSimulatedBacktest($pdo) {
    $draws = getAllDraws($pdo);
    $totalDraws = count($draws);
    $columns = ['no1', 'no2', 'no3', 'no4', 'no5', 'no6', 'no7'];
    global $predictionMethods;
    
    // Results storage
    $results = [];
    foreach ($predictionMethods as $method) {
        $results[$method] = [
            'total_correct' => 0,
            'total_predictions' => 0,
            'column_stats' => []
        ];
        foreach ($columns as $col) {
            $results[$method]['column_stats'][$col] = [
                'correct' => 0,
                'total' => 0
            ];
        }
    }
    
    // Also track consensus
    $consensusResults = [
        'total_correct' => 0,
        'total_predictions' => 0,
        'column_stats' => []
    ];
    foreach ($columns as $col) {
        $consensusResults['column_stats'][$col] = [
            'correct' => 0,
            'total' => 0
        ];
    }
    
    // Minimum training rows: 20
    $minTraining = 20;
    
    echo "Running simulated backtest on {$totalDraws} draws...\n";
    
    // For each draw position (starting from minTraining + 1)
    for ($testIdx = $minTraining; $testIdx < $totalDraws - 1; $testIdx++) {
        // Get historical data BEFORE this draw
        $history = array_slice($draws, 0, $testIdx);
        $actual = $draws[$testIdx];
        
        // Get optimal windows from database
        $optimalWindows = [];
        $stmt = $pdo->query("SELECT method, target_column, training_rows FROM m6_backtest_accuracy");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $optimalWindows[$row['method']][$row['target_column']] = $row['training_rows'];
        }
        
        // Test each method
        foreach ($predictionMethods as $method) {
            $correctCount = 0;
            foreach ($columns as $col) {
                // Get optimal window for this method+column
                $window = $optimalWindows[$method][$col] ?? 30;
                $window = min($window, count($history));
                
                if ($window < 5) continue;
                
                // Get historical values for this column
                $values = array_column($history, $col);
                $training = array_slice($values, -$window);
                
                // Predict
                $result = predictColumnWithConfidence($training, $method, $window);
                $predicted = $result['prediction'];
                $actualVal = $actual[$col];
                
                if ($predicted == $actualVal) {
                    $correctCount++;
                    $results[$method]['column_stats'][$col]['correct']++;
                    $results[$method]['total_correct']++;
                }
                $results[$method]['column_stats'][$col]['total']++;
                $results[$method]['total_predictions']++;
            }
        }
        
        // Test Consensus
        $consensus = getConsensusFromMethods($pdo, $testIdx);
        if ($consensus) {
            $correctCount = 0;
            foreach ($columns as $col) {
                if ($consensus[$col] == $actual[$col]) {
                    $correctCount++;
                    $consensusResults['column_stats'][$col]['correct']++;
                    $consensusResults['total_correct']++;
                }
                $consensusResults['column_stats'][$col]['total']++;
                $consensusResults['total_predictions']++;
            }
        }
        
        // Progress indicator
        if ($testIdx % 50 == 0) {
            echo "  Processed draw {$testIdx}/{$totalDraws}\n";
        }
    }
    
    // Calculate percentages
    $finalResults = [];
    foreach ($predictionMethods as $method) {
        $finalResults[$method] = [
            'total_correct' => $results[$method]['total_correct'],
            'total_predictions' => $results[$method]['total_predictions'],
            'overall_accuracy' => $results[$method]['total_predictions'] > 0 
                ? round(($results[$method]['total_correct'] / $results[$method]['total_predictions']) * 100, 1)
                : 0,
            'column_stats' => []
        ];
        foreach ($columns as $col) {
            $stats = $results[$method]['column_stats'][$col];
            $finalResults[$method]['column_stats'][$col] = [
                'correct' => $stats['correct'],
                'total' => $stats['total'],
                'accuracy' => $stats['total'] > 0 
                    ? round(($stats['correct'] / $stats['total']) * 100, 1)
                    : 0
            ];
        }
    }
    
    // Consensus results
    $finalResults['Consensus'] = [
        'total_correct' => $consensusResults['total_correct'],
        'total_predictions' => $consensusResults['total_predictions'],
        'overall_accuracy' => $consensusResults['total_predictions'] > 0 
            ? round(($consensusResults['total_correct'] / $consensusResults['total_predictions']) * 100, 1)
            : 0,
        'column_stats' => []
    ];
    foreach ($columns as $col) {
        $stats = $consensusResults['column_stats'][$col];
        $finalResults['Consensus']['column_stats'][$col] = [
            'correct' => $stats['correct'],
            'total' => $stats['total'],
            'accuracy' => $stats['total'] > 0 
                ? round(($stats['correct'] / $stats['total']) * 100, 1)
                : 0
        ];
    }
    
    // Store in database
    storeSimulatedResults($pdo, $finalResults);
    
    return $finalResults;
}

/**
 * Get consensus from all methods for a specific draw
 */
function getConsensusFromMethods($pdo, $drawId) {
    $stmt = $pdo->prepare("
        SELECT pred_no1, pred_no2, pred_no3, pred_no4, pred_no5, pred_no6, pred_no7
        FROM m6_position_predictions
        WHERE target_draw_id = :draw_id
    ");
    $stmt->execute([':draw_id' => $drawId]);
    $predictions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($predictions)) return null;
    
    $consensus = [];
    $columns = ['no1', 'no2', 'no3', 'no4', 'no5', 'no6', 'no7'];
    foreach ($columns as $col) {
        $values = array_column($predictions, 'pred_' . $col);
        $freq = array_count_values($values);
        arsort($freq);
        $consensus[$col] = key($freq);
    }
    
    return $consensus;
}

/**
 * Store simulated backtest results in database
 */
function storeSimulatedResults($pdo, $results) {
    // Create table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `m6_simulated_accuracy` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `method` VARCHAR(30) NOT NULL,
            `total_correct` INT DEFAULT 0,
            `total_predictions` INT DEFAULT 0,
            `overall_accuracy` DECIMAL(5,2) DEFAULT 0,
            `no1_accuracy` DECIMAL(5,2) DEFAULT 0,
            `no2_accuracy` DECIMAL(5,2) DEFAULT 0,
            `no3_accuracy` DECIMAL(5,2) DEFAULT 0,
            `no4_accuracy` DECIMAL(5,2) DEFAULT 0,
            `no5_accuracy` DECIMAL(5,2) DEFAULT 0,
            `no6_accuracy` DECIMAL(5,2) DEFAULT 0,
            `no7_accuracy` DECIMAL(5,2) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_method (method)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    foreach ($results as $method => $data) {
        $stmt = $pdo->prepare("
            INSERT INTO m6_simulated_accuracy 
            (method, total_correct, total_predictions, overall_accuracy,
             no1_accuracy, no2_accuracy, no3_accuracy, no4_accuracy, 
             no5_accuracy, no6_accuracy, no7_accuracy)
            VALUES 
            (:method, :correct, :total, :overall,
             :no1, :no2, :no3, :no4, :no5, :no6, :no7)
            ON DUPLICATE KEY UPDATE
            total_correct = VALUES(total_correct),
            total_predictions = VALUES(total_predictions),
            overall_accuracy = VALUES(overall_accuracy),
            no1_accuracy = VALUES(no1_accuracy),
            no2_accuracy = VALUES(no2_accuracy),
            no3_accuracy = VALUES(no3_accuracy),
            no4_accuracy = VALUES(no4_accuracy),
            no5_accuracy = VALUES(no5_accuracy),
            no6_accuracy = VALUES(no6_accuracy),
            no7_accuracy = VALUES(no7_accuracy),
            created_at = NOW()
        ");
        
        $stmt->execute([
            ':method' => $method,
            ':correct' => $data['total_correct'],
            ':total' => $data['total_predictions'],
            ':overall' => $data['overall_accuracy'],
            ':no1' => $data['column_stats']['no1']['accuracy'],
            ':no2' => $data['column_stats']['no2']['accuracy'],
            ':no3' => $data['column_stats']['no3']['accuracy'],
            ':no4' => $data['column_stats']['no4']['accuracy'],
            ':no5' => $data['column_stats']['no5']['accuracy'],
            ':no6' => $data['column_stats']['no6']['accuracy'],
            ':no7' => $data['column_stats']['no7']['accuracy']
        ]);
    }
    
    echo "✅ Simulated results stored in database.\n";
}

/**
 * Get historical accuracy summary - uses simulated data if available
 */
function getHistoricalAccuracySummary($pdo, $limit = 20) {
    // FIRST: Try to get simulated data (more accurate, based on hundreds of predictions)
    $stmt = $pdo->query("
        SELECT 
            method,
            overall_accuracy as avg_accuracy,
            total_correct,
            total_predictions as draws_analyzed,
            0 as avg_rank,
            0 as trend
        FROM m6_simulated_accuracy
        WHERE method != 'Consensus'
        ORDER BY overall_accuracy DESC
    ");
    $simulated = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If simulated data exists, use it
    if (!empty($simulated)) {
        foreach ($simulated as &$row) {
            $row['trend'] = 0; // No trend for simulated data
            // Add emoji-friendly method names
            $row['method_display'] = $row['method'];
        }
        return $simulated;
    }
    
    // Fallback: Use actual analysis data (from m6_draw_analysis)
    $stmt = $pdo->prepare("
        SELECT 
            method,
            AVG(accuracy) as avg_accuracy,
            SUM(correct_count) as total_correct,
            COUNT(*) as draws_analyzed,
            AVG(rank_position) as avg_rank
        FROM m6_draw_analysis
        WHERE method != 'Consensus'
        GROUP BY method
        ORDER BY avg_accuracy DESC
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent trend (last 5 draws)
    $stmt = $pdo->prepare("
        SELECT 
            method,
            AVG(accuracy) as recent_avg_accuracy
        FROM m6_draw_analysis
        WHERE method != 'Consensus'
        GROUP BY method
        ORDER BY draw_id DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit * 13, PDO::PARAM_INT);
    $stmt->execute();
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $recentMap = [];
    foreach ($recent as $r) {
        $recentMap[$r['method']] = $r['recent_avg_accuracy'];
    }
    
    // Combine
    foreach ($results as &$r) {
        $r['recent_avg'] = $recentMap[$r['method']] ?? $r['avg_accuracy'];
        $r['trend'] = $r['recent_avg'] - $r['avg_accuracy'];
    }
    
    return $results;
}

/**
 * Get best method for next prediction based on historical accuracy
 */
function getBestMethodForNextDraw($pdo) {
    // Try simulated data first
    $stmt = $pdo->query("
        SELECT 
            method,
            overall_accuracy as avg_accuracy,
            0 as avg_rank,
            total_predictions as sample_size
        FROM m6_simulated_accuracy
        WHERE method != 'Consensus'
        ORDER BY overall_accuracy DESC
        LIMIT 3
    ");
    $topMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If simulated data exists, use it for overall best
    if (!empty($topMethods)) {
        $overallBest = $topMethods[0] ?? null;
    } else {
        // Fallback: Use actual analysis data
        $stmt = $pdo->query("
            SELECT 
                method,
                AVG(accuracy) as avg_accuracy,
                AVG(rank_position) as avg_rank,
                COUNT(*) as sample_size
            FROM m6_draw_analysis
            WHERE method != 'Consensus'
            GROUP BY method
            ORDER BY avg_accuracy DESC, avg_rank ASC
            LIMIT 3
        ");
        $topMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $overallBest = $topMethods[0] ?? null;
    }
    
    // Per-column best methods - use simulated data if available
    $bestPerColumn = [];
    $columns = ['no1', 'no2', 'no3', 'no4', 'no5', 'no6', 'no7'];
    
    // Check if simulated data exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM m6_simulated_accuracy");
    $hasSimulated = $stmt->fetchColumn() > 0;
    
    if ($hasSimulated) {
        foreach ($columns as $col) {
            $colAcc = $col . '_accuracy';
            $stmt = $pdo->prepare("
                SELECT method, $colAcc as accuracy
                FROM m6_simulated_accuracy
                WHERE method != 'Consensus'
                ORDER BY $colAcc DESC
                LIMIT 1
            ");
            $stmt->execute();
            $best = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($best && $best['accuracy'] > 0) {
                $bestPerColumn[$col] = $best;
            }
        }
    } else {
        // Fallback: Use actual data
        foreach ($columns as $col) {
            $stmt = $pdo->prepare("
                SELECT 
                    p.method,
                    COUNT(*) as total_predictions,
                    SUM(CASE WHEN p.pred_$col = a.$col THEN 1 ELSE 0 END) as correct_count,
                    ROUND(SUM(CASE WHEN p.pred_$col = a.$col THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as accuracy
                FROM m6_position_predictions p
                JOIN `00m6` a ON p.target_draw_id = a.id
                WHERE p.pred_$col IS NOT NULL AND a.$col IS NOT NULL
                GROUP BY p.method
                ORDER BY accuracy DESC
                LIMIT 1
            ");
            $stmt->execute();
            $best = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($best && $best['accuracy'] > 0) {
                $bestPerColumn[$col] = $best;
            }
        }
    }
    
    return [
        'overall_best' => $overallBest,
        'top_three' => $topMethods,
        'per_column_best' => $bestPerColumn
    ];
}

/**
 * Display detailed results with clear per-column breakdown
 */
/**
 * Analyze current year draws - detailed per column
 */
function analyzeCurrentYearDetailed($draws, $columns, $methods, $optimalWindows) {
    $results = [];
    $drawCount = count($draws);
    $minTraining = 20;
    
    // Initialize results
    foreach ($methods as $method) {
        $results[$method] = [
            'total_correct' => 0,
            'total_predictions' => 0,
            'overall_accuracy' => 0,
            'by_column' => []
        ];
        foreach ($columns as $col) {
            $results[$method]['by_column'][$col] = [
                'correct' => 0,
                'total' => 0,
                'accuracy' => 0
            ];
        }
    }
    
    // Track consensus
    $consensusResults = [
        'total_correct' => 0,
        'total_predictions' => 0,
        'overall_accuracy' => 0,
        'by_column' => []
    ];
    foreach ($columns as $col) {
        $consensusResults['by_column'][$col] = [
            'correct' => 0,
            'total' => 0,
            'accuracy' => 0
        ];
    }
    
    // For each draw in current year
    for ($i = $minTraining; $i < $drawCount - 1; $i++) {
        $history = array_slice($draws, 0, $i);
        $actual = $draws[$i];
        
        // Test each method
        foreach ($methods as $method) {
            foreach ($columns as $col) {
                $window = $optimalWindows[$method][$col] ?? 30;
                $window = min($window, count($history));
                if ($window < 5) continue;
                
                $values = array_column($history, $col);
                $training = array_slice($values, -$window);
                $result = predictColumnWithConfidence($training, $method, $window);
                
                if ($result['prediction'] == $actual[$col]) {
                    $results[$method]['total_correct']++;
                    $results[$method]['by_column'][$col]['correct']++;
                }
                $results[$method]['total_predictions']++;
                $results[$method]['by_column'][$col]['total']++;
            }
        }
    }
    
    // Calculate accuracies
    foreach ($methods as $method) {
        if ($results[$method]['total_predictions'] > 0) {
            $results[$method]['overall_accuracy'] = 
                round(($results[$method]['total_correct'] / $results[$method]['total_predictions']) * 100, 1);
        }
        foreach ($columns as $col) {
            if ($results[$method]['by_column'][$col]['total'] > 0) {
                $results[$method]['by_column'][$col]['accuracy'] = 
                    round(($results[$method]['by_column'][$col]['correct'] / 
                           $results[$method]['by_column'][$col]['total']) * 100, 1);
            }
        }
    }
    
    $results['Consensus'] = $consensusResults;
    return $results;
}

/**
 * Analyze all data - detailed per column
 */
function analyzeAllDataDetailed($draws, $columns, $methods, $optimalWindows, $pdo) {
    $totalDraws = count($draws);
    $minTraining = 20;
    
    // Initialize results
    $results = [];
    foreach ($methods as $method) {
        $results[$method] = [
            'total_correct' => 0,
            'total_predictions' => 0,
            'overall_accuracy' => 0,
            'by_column' => []
        ];
        foreach ($columns as $col) {
            $results[$method]['by_column'][$col] = [
                'correct' => 0,
                'total' => 0,
                'accuracy' => 0
            ];
        }
    }
    
    // Track consensus
    $consensusResults = [
        'total_correct' => 0,
        'total_predictions' => 0,
        'overall_accuracy' => 0,
        'by_column' => []
    ];
    foreach ($columns as $col) {
        $consensusResults['by_column'][$col] = [
            'correct' => 0,
            'total' => 0,
            'accuracy' => 0
        ];
    }
    
    // For each draw in all data
    for ($i = $minTraining; $i < $totalDraws - 1; $i++) {
        $history = array_slice($draws, 0, $i);
        $actual = $draws[$i];
        
        // Test each method
        foreach ($methods as $method) {
            foreach ($columns as $col) {
                $window = $optimalWindows[$method][$col] ?? 30;
                $window = min($window, count($history));
                if ($window < 5) continue;
                
                $values = array_column($history, $col);
                $training = array_slice($values, -$window);
                $result = predictColumnWithConfidence($training, $method, $window);
                
                if ($result['prediction'] == $actual[$col]) {
                    $results[$method]['total_correct']++;
                    $results[$method]['by_column'][$col]['correct']++;
                }
                $results[$method]['total_predictions']++;
                $results[$method]['by_column'][$col]['total']++;
            }
        }
        
        // Test consensus
        $consensus = getConsensusFromMethods($pdo, $i);
        if ($consensus) {
            foreach ($columns as $col) {
                if ($consensus[$col] == $actual[$col]) {
                    $consensusResults['total_correct']++;
                    $consensusResults['by_column'][$col]['correct']++;
                }
                $consensusResults['total_predictions']++;
                $consensusResults['by_column'][$col]['total']++;
            }
        }
    }
    
    // Calculate accuracies
    foreach ($methods as $method) {
        if ($results[$method]['total_predictions'] > 0) {
            $results[$method]['overall_accuracy'] = 
                round(($results[$method]['total_correct'] / $results[$method]['total_predictions']) * 100, 1);
        }
        foreach ($columns as $col) {
            if ($results[$method]['by_column'][$col]['total'] > 0) {
                $results[$method]['by_column'][$col]['accuracy'] = 
                    round(($results[$method]['by_column'][$col]['correct'] / 
                           $results[$method]['by_column'][$col]['total']) * 100, 1);
            }
        }
    }
    
    if ($consensusResults['total_predictions'] > 0) {
        $consensusResults['overall_accuracy'] = 
            round(($consensusResults['total_correct'] / $consensusResults['total_predictions']) * 100, 1);
    }
    $results['Consensus'] = $consensusResults;
    
    return $results;
}

/**
 * Store detailed results in database
 */
/**
 * Store detailed results in database
 */
function storeDetailedResults($pdo, $currentYearResults, $allDataResults) {
    global $predictionMethods;
    $columns = ['no1', 'no2', 'no3', 'no4', 'no5', 'no6', 'no7'];
    
    // Drop old tables and recreate with correct schema
    $pdo->exec("DROP TABLE IF EXISTS `m6_current_year_accuracy`");
    $pdo->exec("DROP TABLE IF EXISTS `m6_all_data_accuracy`");
    
    // Create current year accuracy table
    $pdo->exec("
        CREATE TABLE `m6_current_year_accuracy` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `method` VARCHAR(30) NOT NULL,
            `correct` INT DEFAULT 0,
            `total` INT DEFAULT 0,
            `accuracy` DECIMAL(5,2) DEFAULT 0,
            `no1_accuracy` DECIMAL(5,2) DEFAULT 0,
            `no2_accuracy` DECIMAL(5,2) DEFAULT 0,
            `no3_accuracy` DECIMAL(5,2) DEFAULT 0,
            `no4_accuracy` DECIMAL(5,2) DEFAULT 0,
            `no5_accuracy` DECIMAL(5,2) DEFAULT 0,
            `no6_accuracy` DECIMAL(5,2) DEFAULT 0,
            `no7_accuracy` DECIMAL(5,2) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_method (method)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Create all data accuracy table
    $pdo->exec("
        CREATE TABLE `m6_all_data_accuracy` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `method` VARCHAR(30) NOT NULL,
            `correct` INT DEFAULT 0,
            `total` INT DEFAULT 0,
            `accuracy` DECIMAL(5,2) DEFAULT 0,
            `no1_accuracy` DECIMAL(5,2) DEFAULT 0,
            `no2_accuracy` DECIMAL(5,2) DEFAULT 0,
            `no3_accuracy` DECIMAL(5,2) DEFAULT 0,
            `no4_accuracy` DECIMAL(5,2) DEFAULT 0,
            `no5_accuracy` DECIMAL(5,2) DEFAULT 0,
            `no6_accuracy` DECIMAL(5,2) DEFAULT 0,
            `no7_accuracy` DECIMAL(5,2) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_method (method)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Store current year results
    foreach ($currentYearResults as $method => $data) {
        if ($method == 'Consensus' || $data['total_predictions'] == 0) continue;
        $stmt = $pdo->prepare("
            INSERT INTO m6_current_year_accuracy 
            (method, correct, total, accuracy,
             no1_accuracy, no2_accuracy, no3_accuracy, no4_accuracy, 
             no5_accuracy, no6_accuracy, no7_accuracy)
            VALUES 
            (:method, :correct, :total, :accuracy,
             :no1, :no2, :no3, :no4, :no5, :no6, :no7)
            ON DUPLICATE KEY UPDATE
            correct = VALUES(correct), total = VALUES(total), accuracy = VALUES(accuracy),
            no1_accuracy = VALUES(no1_accuracy), no2_accuracy = VALUES(no2_accuracy),
            no3_accuracy = VALUES(no3_accuracy), no4_accuracy = VALUES(no4_accuracy),
            no5_accuracy = VALUES(no5_accuracy), no6_accuracy = VALUES(no6_accuracy),
            no7_accuracy = VALUES(no7_accuracy)
        ");
        
        $stmt->execute([
            ':method' => $method,
            ':correct' => $data['total_correct'],
            ':total' => $data['total_predictions'],
            ':accuracy' => $data['overall_accuracy'],
            ':no1' => $data['by_column']['no1']['accuracy'] ?? 0,
            ':no2' => $data['by_column']['no2']['accuracy'] ?? 0,
            ':no3' => $data['by_column']['no3']['accuracy'] ?? 0,
            ':no4' => $data['by_column']['no4']['accuracy'] ?? 0,
            ':no5' => $data['by_column']['no5']['accuracy'] ?? 0,
            ':no6' => $data['by_column']['no6']['accuracy'] ?? 0,
            ':no7' => $data['by_column']['no7']['accuracy'] ?? 0
        ]);
    }
    
    // Store all data results
    foreach ($allDataResults as $method => $data) {
        if ($method == 'Consensus') continue;
        if ($data['total_predictions'] == 0) continue;
        $stmt = $pdo->prepare("
            INSERT INTO m6_all_data_accuracy 
            (method, correct, total, accuracy,
             no1_accuracy, no2_accuracy, no3_accuracy, no4_accuracy, 
             no5_accuracy, no6_accuracy, no7_accuracy)
            VALUES 
            (:method, :correct, :total, :accuracy,
             :no1, :no2, :no3, :no4, :no5, :no6, :no7)
            ON DUPLICATE KEY UPDATE
            correct = VALUES(correct), total = VALUES(total), accuracy = VALUES(accuracy),
            no1_accuracy = VALUES(no1_accuracy), no2_accuracy = VALUES(no2_accuracy),
            no3_accuracy = VALUES(no3_accuracy), no4_accuracy = VALUES(no4_accuracy),
            no5_accuracy = VALUES(no5_accuracy), no6_accuracy = VALUES(no6_accuracy),
            no7_accuracy = VALUES(no7_accuracy)
        ");
        
        $stmt->execute([
            ':method' => $method,
            ':correct' => $data['total_correct'],
            ':total' => $data['total_predictions'],
            ':accuracy' => $data['overall_accuracy'],
            ':no1' => $data['by_column']['no1']['accuracy'] ?? 0,
            ':no2' => $data['by_column']['no2']['accuracy'] ?? 0,
            ':no3' => $data['by_column']['no3']['accuracy'] ?? 0,
            ':no4' => $data['by_column']['no4']['accuracy'] ?? 0,
            ':no5' => $data['by_column']['no5']['accuracy'] ?? 0,
            ':no6' => $data['by_column']['no6']['accuracy'] ?? 0,
            ':no7' => $data['by_column']['no7']['accuracy'] ?? 0
        ]);
    }
    
    echo "✅ Detailed results stored in database.\n";
}

/**
 * Display detailed results with clear per-column breakdown
 */
function displayDetailedResults($currentYearResults, $allDataResults, $columns) {
    global $predictionMethods;
    
    echo "\n" . str_repeat("=", 100) . "\n";
    echo "📊 DUAL ACCURACY ANALYSIS - PER COLUMN BREAKDOWN\n";
    echo str_repeat("=", 100) . "\n\n";
    
    // === CURRENT YEAR ===
    $total2026 = 0;
    foreach ($currentYearResults as $method => $data) {
        if ($method != 'Consensus' && isset($data['total_predictions'])) {
            $total2026 = $data['total_predictions'];
            break;
        }
    }
    
    echo "┌──────────────────────────────────────────────────────────────────────────────────────────────────────┐\n";
    echo "│  📅 CURRENT YEAR (2026) - 64 draws analyzed (" . ($total2026 ? $total2026 : '?') . " predictions per method)        │\n";
    echo "├──────────────┬─────────────┬─────────────┬───────────────┬──────────────────────────────────────────┤\n";
    echo "│ Method       │   Correct   │    Total    │   Accuracy    │  no1  no2  no3  no4  no5  no6  no7      │\n";
    echo "├──────────────┼─────────────┼─────────────┼───────────────┼──────────────────────────────────────────┤\n";
    
    // Sort by overall accuracy
    $sorted = $currentYearResults;
    uasort($sorted, function($a, $b) {
        return ($b['overall_accuracy'] ?? 0) <=> ($a['overall_accuracy'] ?? 0);
    });
    
    foreach ($sorted as $method => $data) {
        if ($method == 'Consensus' || $data['total_predictions'] == 0) continue;
        
        // Build per-column accuracy string
        $colAcc = [];
        foreach ($columns as $col) {
            $acc = $data['by_column'][$col]['accuracy'] ?? 0;
            $colAcc[] = sprintf("%4.1f", $acc);
        }
        $colStr = implode('  ', $colAcc);
        
        // Bar for visual representation
        $barLength = round($data['overall_accuracy'] * 2);
        $bar = str_repeat('█', min($barLength, 30));
        
        echo sprintf("│ %-12s │ %9d │ %9d │ %9.1f%%  │ %s │ %s\n",
            $method, 
            $data['total_correct'], 
            $data['total_predictions'], 
            $data['overall_accuracy'],
            $colStr,
            $bar
        );
    }
    echo "├──────────────┼─────────────┼─────────────┼───────────────┼──────────────────────────────────────────┤\n";
    if (isset($currentYearResults['Consensus']) && $currentYearResults['Consensus']['total_predictions'] > 0) {
        $c = $currentYearResults['Consensus'];
        $colAcc = [];
        foreach ($columns as $col) {
            $acc = $c['by_column'][$col]['accuracy'] ?? 0;
            $colAcc[] = sprintf("%4.1f", $acc);
        }
        $colStr = implode('  ', $colAcc);
        echo sprintf("│ %-12s │ %9d │ %9d │ %9.1f%%  │ %s │\n",
            "Consensus", $c['total_correct'], $c['total_predictions'], $c['overall_accuracy'], $colStr);
    }
    echo "└──────────────┴─────────────┴─────────────┴───────────────┴──────────────────────────────────────────┘\n\n";
    
    // === ALL DATA ===
    $totalAll = 0;
    foreach ($allDataResults as $method => $data) {
        if ($method != 'Consensus' && isset($data['total_predictions'])) {
            $totalAll = $data['total_predictions'];
            break;
        }
    }
    
    echo "┌──────────────────────────────────────────────────────────────────────────────────────────────────────┐\n";
    echo "│  📊 ALL DATA (Simulated) - " . count($allDataResults) . " methods, " . number_format($totalAll) . " predictions each        │\n";
    echo "├──────────────┬─────────────┬─────────────┬───────────────┬──────────────────────────────────────────┤\n";
    echo "│ Method       │   Correct   │    Total    │   Accuracy    │  no1  no2  no3  no4  no5  no6  no7      │\n";
    echo "├──────────────┼─────────────┼─────────────┼───────────────┼──────────────────────────────────────────┤\n";
    
    $sortedAll = $allDataResults;
    uasort($sortedAll, function($a, $b) {
        return ($b['overall_accuracy'] ?? 0) <=> ($a['overall_accuracy'] ?? 0);
    });
    
    foreach ($sortedAll as $method => $data) {
        if ($method == 'Consensus' || $data['total_predictions'] == 0) continue;
        
        // Build per-column accuracy string
        $colAcc = [];
        foreach ($columns as $col) {
            $acc = $data['by_column'][$col]['accuracy'] ?? 0;
            $colAcc[] = sprintf("%4.1f", $acc);
        }
        $colStr = implode('  ', $colAcc);
        
        // Bar for visual representation
        $barLength = round($data['overall_accuracy'] * 5);
        $bar = str_repeat('█', min($barLength, 30));
        
        echo sprintf("│ %-12s │ %9d │ %9d │ %9.1f%%  │ %s │ %s\n",
            $method, 
            $data['total_correct'], 
            $data['total_predictions'], 
            $data['overall_accuracy'],
            $colStr,
            $bar
        );
    }
    echo "├──────────────┼─────────────┼─────────────┼───────────────┼──────────────────────────────────────────┤\n";
    if (isset($allDataResults['Consensus']) && $allDataResults['Consensus']['total_predictions'] > 0) {
        $c = $allDataResults['Consensus'];
        $colAcc = [];
        foreach ($columns as $col) {
            $acc = $c['by_column'][$col]['accuracy'] ?? 0;
            $colAcc[] = sprintf("%4.1f", $acc);
        }
        $colStr = implode('  ', $colAcc);
        echo sprintf("│ %-12s │ %9d │ %9d │ %9.1f%%  │ %s │\n",
            "Consensus", $c['total_correct'], $c['total_predictions'], $c['overall_accuracy'], $colStr);
    }
    echo "└──────────────┴─────────────┴─────────────┴───────────────┴──────────────────────────────────────────┘\n";
    
    // Summary legend
    echo "\n📌 LEGEND:\n";
    echo "   • Per-Column Format: no1  no2  no3  no4  no5  no6  no7\n";
    echo "   • Random Expectation: ~2.04% (1/49 per number)\n";
    echo "   • Overall Accuracy = Correct / (Draws × 7)\n\n";
    
    // Column-specific best methods
    echo "🏆 BEST METHOD PER COLUMN:\n";
    echo str_repeat("-", 60) . "\n";
    
    foreach ($columns as $col) {
        $bestMethod = '';
        $bestAcc = 0;
        foreach ($allDataResults as $method => $data) {
            if ($method == 'Consensus') continue;
            $acc = $data['by_column'][$col]['accuracy'] ?? 0;
            if ($acc > $bestAcc) {
                $bestAcc = $acc;
                $bestMethod = $method;
            }
        }
        echo sprintf("   %-4s: %-10s (%.1f%%)\n", $col, $bestMethod, $bestAcc);
    }
    echo str_repeat("-", 60) . "\n";
    
    // Overall best
    $bestOverall = '';
    $bestOverallAcc = 0;
    foreach ($allDataResults as $method => $data) {
        if ($method == 'Consensus') continue;
        if ($data['overall_accuracy'] > $bestOverallAcc) {
            $bestOverallAcc = $data['overall_accuracy'];
            $bestOverall = $method;
        }
    }
    echo "🏆 BEST OVERALL: " . $bestOverall . " (" . round($bestOverallAcc, 1) . "%)\n";
}

/**
 * Run simulated backtest with TWO analyses:
 * 1. Current Year (dynamic - uses current year from database)
 * 2. All Data - simulated across all historical draws
 */
function runDualSimulatedBacktest($pdo) {
    $draws = getAllDraws($pdo);
    $totalDraws = count($draws);
    $columns = ['no1', 'no2', 'no3', 'no4', 'no5', 'no6', 'no7'];
    global $predictionMethods;
    
    // Get current year dynamically
    $currentYear = date('Y');
    $stmt = $pdo->prepare("SELECT * FROM `00m6` WHERE year = :year ORDER BY id ASC");
    $stmt->execute([':year' => $currentYear]);
    $currentYearDraws = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $currentYearCount = count($currentYearDraws);
    echo "📅 Current year ({$currentYear}) draws: {$currentYearCount}\n";
    echo "📊 All draws: {$totalDraws}\n\n";
    
    // Get optimal windows from database
    $optimalWindows = [];
    $stmt = $pdo->query("SELECT method, target_column, training_rows FROM m6_backtest_accuracy");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $optimalWindows[$row['method']][$row['target_column']] = $row['training_rows'];
    }
    
    // === ANALYSIS 1: CURRENT YEAR ===
    echo "📊 Analyzing Current Year ({$currentYear}) Data...\n";
    $currentYearResults = analyzeCurrentYearDetailed($currentYearDraws, $columns, $predictionMethods, $optimalWindows);
    
    // === ANALYSIS 2: ALL DATA ===
    echo "📊 Analyzing All Data (Simulated)...\n";
    $allDataResults = analyzeAllDataDetailed($draws, $columns, $predictionMethods, $optimalWindows, $pdo);
    
    // === STORE RESULTS ===
    storeDetailedResults($pdo, $currentYearResults, $allDataResults);
    
    // === DISPLAY RESULTS ===
    displayDetailedResults($currentYearResults, $allDataResults, $columns);
    
    return [
        'current_year' => $currentYearResults,
        'all_data' => $allDataResults
    ];
}

?>