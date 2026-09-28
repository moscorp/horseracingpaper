<?php
/**
 * Mark Six Positional Prediction Functions
 * 13 methods, per-column prediction, backtestable
 */

// ========== CONFIGURATION ==========
$minTrainingRows = 20;
// $maxTrainingRows = 200;
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

/**
 * Extract a single column's history as a flat array
 */
function getColumnHistory($draws, $column) {
    return array_column($draws, $column);
}

/**
 * ========== METHOD 1: Weighted Moving Average ==========
 */
function predict_WgtMA($history, $windowSize = 10) {
    $recent = array_slice($history, -$windowSize);
    if (count($recent) < $windowSize) return round(array_sum($recent) / count($recent));
    
    $weights = range(1, $windowSize);
    $weightedSum = 0;
    $weightSum = 0;
    for ($i = 0; $i < $windowSize; $i++) {
        $weightedSum += $recent[$i] * $weights[$i];
        $weightSum += $weights[$i];
    }
    return round($weightedSum / $weightSum);
}

/**
 * ========== METHOD 2: Mode (Hot Numbers) ==========
 */
function predict_Mode($history, $windowSize = 30) {
    $recent = array_slice($history, -$windowSize);
    $freq = array_count_values($recent);
    arsort($freq);
    return key($freq);
}

/**
 * ========== METHOD 3: Exponential Smoothing ==========
 */
function predict_ExpS($history, $alpha = 0.3) {
    if (empty($history)) return 24;
    $smoothed = $history[0];
    for ($i = 1; $i < count($history); $i++) {
        $smoothed = $alpha * $history[$i] + (1 - $alpha) * $smoothed;
    }
    return round($smoothed);
}

/**
 * ========== METHOD 4: Autocorrelation ==========
 */
function predict_AutoCorr($history, $maxLag = 10) {
    $n = count($history);
    if ($n < $maxLag + 5) return predict_WgtMA($history, 10);
    
    $bestLag = 1;
    $bestCorr = -1;
    for ($lag = 1; $lag <= min($maxLag, floor($n/2)); $lag++) {
        $corr = correlation(
            array_slice($history, 0, $n - $lag),
            array_slice($history, $lag, $n - $lag)
        );
        if ($corr > $bestCorr) {
            $bestCorr = $corr;
            $bestLag = $lag;
        }
    }
    
    if ($bestCorr > 0.3 && $n >= $bestLag) {
        return $history[$n - $bestLag];
    }
    return predict_WgtMA($history, 10);
}

/**
 * ========== METHOD 5: Seasonal (Cyclical) ==========
 */
function predict_Seasonal($history, $cycleLength = 7) {
    $n = count($history);
    if ($n < $cycleLength * 2) return predict_WgtMA($history, 10);
    
    $seasonalValues = [];
    for ($i = $n - $cycleLength; $i < $n; $i++) {
        if (isset($history[$i - $cycleLength])) {
            $diff = $history[$i] - $history[$i - $cycleLength];
            $seasonalValues[] = $history[$i] + $diff;
        }
    }
    if (empty($seasonalValues)) return predict_WgtMA($history, 10);
    return round(array_sum($seasonalValues) / count($seasonalValues));
}

/**
 * ========== METHOD 6: Frequency + Recency ==========
 */
function predict_FreqRec($history, $windowSize = 50) {
    $recent = array_slice($history, -$windowSize);
    $freq = array_count_values($recent);
    
    $recencyScore = [];
    $rank = 1;
    foreach (array_reverse($recent) as $num) {
        $recencyScore[$num] = ($recencyScore[$num] ?? 0) + (1 / $rank);
        $rank++;
    }
    
    $combined = [];
    foreach ($freq as $num => $f) {
        $combined[$num] = $f * 0.6 + ($recencyScore[$num] ?? 0) * 0.4;
    }
    arsort($combined);
    return key($combined);
}

/**
 * ========== METHOD 7: K-Nearest Neighbor ==========
 */
function predict_KNN($history, $patternLength = 5) {
    $n = count($history);
    if ($n < $patternLength + 5) return predict_WgtMA($history, 10);
    
    $currentPattern = array_slice($history, -$patternLength);
    $bestMatch = null;
    $bestDistance = PHP_INT_MAX;
    
    for ($i = 0; $i <= $n - $patternLength - 1; $i++) {
        $pattern = array_slice($history, $i, $patternLength);
        $dist = euclideanDistance($currentPattern, $pattern);
        if ($dist < $bestDistance) {
            $bestDistance = $dist;
            $bestMatch = $history[$i + $patternLength] ?? null;
        }
    }
    
    return $bestMatch ?? predict_WgtMA($history, 10);
}

/**
 * ========== METHOD 8: Balance ==========
 */
function predict_Balance($history, $windowSize = 30) {
    $recent = array_slice($history, -$windowSize);
    $oddCount = 0;
    $highCount = 0;
    foreach ($recent as $num) {
        if ($num % 2 == 1) $oddCount++;
        if ($num >= 25) $highCount++;
    }
    
    $oddRatio = $oddCount / count($recent);
    $highRatio = $highCount / count($recent);
    
    if ($oddRatio > 0.6) return 24;
    if ($oddRatio < 0.4) return 25;
    if ($highRatio > 0.6) return 20;
    if ($highRatio < 0.4) return 35;
    
    return round(array_sum($recent) / count($recent));
}

/**
 * ========== METHOD 9: Sum Trend Analysis ==========
 */
function predict_SumTrend($history, $windowSize = 10) {
    $recent = array_slice($history, -$windowSize);
    $sum = array_sum($recent);
    $avg = $sum / count($recent);
    $trend = ($recent[count($recent)-1] - $recent[0]) / count($recent);
    $prediction = $avg + $trend;
    return round(max(1, min(49, $prediction)));
}

/**
 * ========== METHOD 10: Heatmap (Zone) ==========
 */
function predict_Heatmap($history, $windowSize = 50) {
    $recent = array_slice($history, -$windowSize);
    $zones = array_fill(1, 7, 0);
    foreach ($recent as $num) {
        $zone = ceil($num / 7);
        $zones[$zone]++;
    }
    
    $coldZone = array_search(min($zones), $zones);
    $prediction = ($coldZone - 1) * 7 + 4;
    return max(1, min(49, $prediction));
}

/**
 * ========== METHOD 11: Gap (Overdue numbers) ==========
 */
function predict_Gap($history) {
    $lastSeen = array_fill(1, 49, 0);
    $currentDraw = count($history);
    foreach ($history as $drawIndex => $num) {
        $lastSeen[$num] = $currentDraw - $drawIndex;
    }
    
    $maxGap = 0;
    $bestNum = 24;
    foreach ($lastSeen as $num => $gap) {
        if ($gap > $maxGap) {
            $maxGap = $gap;
            $bestNum = $num;
        }
    }
    return $bestNum;
}

/**
 * ========== METHOD 12: Markov Chain ==========
 */
function predict_Markov($history) {
    $transitions = [];
    for ($i = 0; $i < count($history) - 1; $i++) {
        $current = $history[$i];
        $next = $history[$i + 1];
        if (!isset($transitions[$current])) {
            $transitions[$current] = [];
        }
        $transitions[$current][$next] = ($transitions[$current][$next] ?? 0) + 1;
    }
    
    $last = $history[count($history) - 1];
    if (isset($transitions[$last])) {
        arsort($transitions[$last]);
        return key($transitions[$last]);
    }
    return predict_Mode($history, 30);
}

/**
 * ========== METHOD 13: Linear Regression ==========
 */
function predict_LinReg($history) {
    $n = count($history);
    if ($n < 5) return predict_WgtMA($history, 10);
    
    $sumX = 0;
    $sumY = 0;
    $sumXY = 0;
    $sumX2 = 0;
    
    for ($i = 0; $i < $n; $i++) {
        $x = $i + 1;
        $y = $history[$i];
        $sumX += $x;
        $sumY += $y;
        $sumXY += $x * $y;
        $sumX2 += $x * $x;
    }
    
    $denom = ($n * $sumX2 - $sumX * $sumX);
    if ($denom == 0) return round($sumY / $n);
    
    $b = ($n * $sumXY - $sumX * $sumY) / $denom;
    $a = ($sumY - $b * $sumX) / $n;
    
    $nextX = $n + 1;
    $prediction = $a + $b * $nextX;
    return round(max(1, min(49, $prediction)));
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
 * Helper: Euclidean distance for KNN
 */
function euclideanDistance($arr1, $arr2) {
    $sum = 0;
    for ($i = 0; $i < count($arr1); $i++) {
        $sum += pow($arr1[$i] - $arr2[$i], 2);
    }
    return sqrt($sum);
}

/**
 * Run a single prediction using specified method
 */
function predictColumn($history, $method, $params = []) {
    $functionName = "predict_$method";
    if (function_exists($functionName)) {
        return $functionName($history, ...$params);
    }
    return predict_WgtMA($history, 10);
}

/**
 * Backtest: For a given method and training window size, calculate accuracy
 */
function backtestMethod($pdo, $method, $trainingRows, $columns = ['no1','no2','no3','no4','no5','no6','no7']) {
    $draws = getAllDraws($pdo);
    $totalDraws = count($draws);
    
    if ($totalDraws < $trainingRows + 1) {
        return null;
    }
    
    $results = [];
    foreach ($columns as $col) {
        $errors = [];
        $exactHits = 0;
        $nearHits = 0;
        $testCount = 0;
        
        for ($i = $trainingRows; $i < $totalDraws - 1; $i++) {
            $history = array_column(array_slice($draws, 0, $i), $col);
            $actual = $draws[$i][$col];
            
            if (count($history) >= $trainingRows) {
                $history = array_slice($history, -$trainingRows);
                $predicted = predictColumn($history, $method);
                
                $error = abs($predicted - $actual);
                $errors[] = $error;
                if ($error == 0) $exactHits++;
                if ($error <= 2) $nearHits++;
                $testCount++;
            }
        }
        
        if ($testCount > 0) {
            $results[$col] = [
                'mae' => array_sum($errors) / $testCount,
                'hit_rate' => $exactHits / $testCount,
                'near_hit_rate' => $nearHits / $testCount,
                'tested_draws' => $testCount
            ];
        }
    }
    
    return $results;
}

/**
 * Run full backtest across all methods and training windows
 */
function runFullBacktest($pdo) {
    global $predictionMethods, $minTrainingRows, $maxTrainingRows;
    
    // $windows = [10, 20, 30, 50, 100];
    $windows = [30, 50, 100, 200, 500];  // ← UPDATED: Larger windows
    // if ($maxTrainingRows > 100) $windows[] = 200;
    
    foreach ($predictionMethods as $method) {
        foreach ($windows as $window) {
            echo "  Testing: $method with {$window} rows...\n";
            $accuracy = backtestMethod($pdo, $method, $window);
            
            if ($accuracy) {
                foreach ($accuracy as $col => $metrics) {
                    $stmt = $pdo->prepare("
                        INSERT INTO m6_backtest_accuracy 
                        (method, training_rows, target_column, mae, hit_rate, near_hit_rate, tested_draws, last_updated)
                        VALUES (:method, :rows, :col, :mae, :hit, :near, :tested, NOW())
                        ON DUPLICATE KEY UPDATE
                        mae = VALUES(mae),
                        hit_rate = VALUES(hit_rate),
                        near_hit_rate = VALUES(near_hit_rate),
                        tested_draws = VALUES(tested_draws),
                        last_updated = NOW()
                    ");
                    $stmt->execute([
                        ':method' => $method,
                        ':rows' => $window,
                        ':col' => $col,
                        ':mae' => $metrics['mae'],
                        ':hit' => $metrics['hit_rate'],
                        ':near' => $metrics['near_hit_rate'],
                        ':tested' => $metrics['tested_draws']
                    ]);
                }
            }
        }
    }
    
    echo "Backtest complete.\n";
}

/**
 * Get optimal training rows for each (method, column)
 */
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
 * Generate final prediction for next draw using optimal configs
 * EACH METHOD = ONE ROW with ALL 7 columns
 */
function predictNextDraw($pdo) {
    global $predictionMethods;
    
    $draws = getAllDraws($pdo);
    $nextDrawId = count($draws) + 1;
    
    // First, clear old predictions for this draw
    $stmt = $pdo->prepare("DELETE FROM m6_position_predictions WHERE target_draw_id = :draw_id");
    $stmt->execute([':draw_id' => $nextDrawId]);
    
    $columns = ['no1', 'no2', 'no3', 'no4', 'no5', 'no6', 'no7'];
    
    // For EACH method, store ONE row with ALL 7 predictions
    foreach ($predictionMethods as $method) {
        $predRow = [];
        
        // Get predictions for each column
        foreach ($columns as $col) {
            // Get optimal training rows for this method+column
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
            $predRow[$col] = predictColumn($history, $method);
        }
        
        // Insert ONE row per method with ALL 7 columns
        $stmt = $pdo->prepare("
            INSERT INTO m6_position_predictions 
            (target_draw_id, method, training_rows, 
             pred_no1, pred_no2, pred_no3, pred_no4, pred_no5, pred_no6, pred_no7)
            VALUES 
            (:draw_id, :method, :rows,
             :no1, :no2, :no3, :no4, :no5, :no6, :no7)
        ");
        $stmt->execute([
            ':draw_id' => $nextDrawId,
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
    
    // Get final consensus ticket (majority vote per column)
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
        'final_ticket' => $finalTicket
    ];
}

/**
 * Get consensus prediction (majority vote from all 13 methods)
 */
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
?>