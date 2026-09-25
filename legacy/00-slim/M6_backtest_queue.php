<?php
/**
 * Mark Six Backtest Queue Processor - OPTIMIZED VERSION
 * One method per execution (processes all 7 columns at once)
 * 
 * Usage:
 * https://buycarl.com/00/M6_backtest_queue.php?action=process
 * https://buycarl.com/00/M6_backtest_queue.php?action=status
 * https://buycarl.com/00/M6_backtest_queue.php?action=reset
 */

include_once ("lib/constants.php");
require_once "M6_prediction_functions.php";

date_default_timezone_set('Asia/Hong_Kong');

$action = $_GET['action'] ?? 'process';

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create queue table if not exists (one row per METHOD, not per column)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `m6_backtest_queue` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `method` VARCHAR(30) NOT NULL,
            `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
            `results` JSON NULL,
            `started_at` DATETIME DEFAULT NULL,
            `completed_at` DATETIME DEFAULT NULL,
            `error_message` TEXT DEFAULT NULL,
            UNIQUE KEY uk_method (method),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    switch ($action) {
        case 'process':
            processNextMethod($pdo);
            break;
        case 'status':
            showQueueStatus($pdo);
            break;
        case 'reset':
            resetQueue($pdo);
            break;
        case 'publish_if_ready':
            publishIfReady($pdo);
            break;
        default:
            echo "Usage: action=process|status|reset|publish_if_ready\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    error_log("M6 Backtest Queue Error: " . $e->getMessage());
}

/**
 * Process the next pending method (processes ALL 7 columns at once)
 */
function processNextMethod($pdo) {
    global $predictionMethods;
    $columns = ['no1', 'no2', 'no3', 'no4', 'no5', 'no6', 'no7'];
    
    // Initialize queue if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM m6_backtest_queue");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        initializeQueue($pdo, $predictionMethods);
    }
    
    // Get next pending method
    $stmt = $pdo->prepare("
        SELECT * FROM m6_backtest_queue 
        WHERE status = 'pending' 
        ORDER BY id ASC 
        LIMIT 1
    ");
    $stmt->execute();
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$task) {
        $completed = $pdo->query("SELECT COUNT(*) FROM m6_backtest_queue WHERE status = 'completed'")->fetchColumn();
        $total = $pdo->query("SELECT COUNT(*) FROM m6_backtest_queue")->fetchColumn();
        echo "✅ All methods completed! ({$completed}/{$total})\n";
        return;
    }
    
    $method = $task['method'];
    echo "\n🔄 Processing method: {$method}\n";
    echo str_repeat("=", 50) . "\n";
    
    // Mark as processing
    $stmt = $pdo->prepare("
        UPDATE m6_backtest_queue 
        SET status = 'processing', started_at = NOW() 
        WHERE id = :id
    ");
    $stmt->execute([':id' => $task['id']]);
    
    $startTime = microtime(true);
    $results = [];
    $totalWindowsTested = 0;
    
    try {
        // Process ALL 7 columns for this method
        foreach ($columns as $col) {
            echo "\n  📊 {$method} on {$col}:\n";
            $optimal = backtestSingleCombination($pdo, $method, $col);
            
            if ($optimal) {
                $results[$col] = $optimal;
                $totalWindowsTested += $optimal['windows_tested'];
                echo "    ✅ Optimal window: {$optimal['optimal_window']} draws\n";
                echo "    📈 Error: {$optimal['error']}\n";
                echo "    🎯 Prediction: {$optimal['prediction']} (confidence: {$optimal['confidence']}%)\n";
                echo "    🔍 Tested: {$optimal['windows_tested']} window sizes\n";
                
                // Store in backtest_accuracy table
                $stmt = $pdo->prepare("
                    INSERT INTO m6_backtest_accuracy 
                    (method, training_rows, target_column, mae, hit_rate, near_hit_rate, tested_draws, last_updated)
                    VALUES (:method, :rows, :col, :mae, 0, 0, :tested, NOW())
                    ON DUPLICATE KEY UPDATE
                    training_rows = VALUES(training_rows),
                    mae = VALUES(mae),
                    tested_draws = VALUES(tested_draws),
                    last_updated = NOW()
                ");
                $stmt->execute([
                    ':method' => $method,
                    ':rows' => $optimal['optimal_window'],
                    ':col' => $col,
                    ':mae' => $optimal['error'],
                    ':tested' => $optimal['windows_tested']
                ]);
            } else {
                echo "    ❌ Failed\n";
                $results[$col] = null;
            }
        }
        
        $duration = round(microtime(true) - $startTime, 2);
        
        // Update queue as completed
        $stmt = $pdo->prepare("
            UPDATE m6_backtest_queue 
            SET status = 'completed', 
                results = :results, 
                completed_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':id' => $task['id'],
            ':results' => json_encode($results)
        ]);
        
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "✅ Completed {$method} in {$duration} seconds\n";
        echo "📊 Total window tests: {$totalWindowsTested}\n";
        
    } catch (Exception $e) {
        $stmt = $pdo->prepare("
            UPDATE m6_backtest_queue 
            SET status = 'failed', error_message = :error, completed_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':id' => $task['id'],
            ':error' => $e->getMessage()
        ]);
        
        echo "❌ Failed: {$method} - " . $e->getMessage() . "\n";
    }
    
    showQueueProgress($pdo);
}

/**
 * Backtest a single method+column combination
 */
/**
 * Backtest a single method+column combination - NO FIXED WINDOWS
 * Tests EVERY possible window size to find TRUE optimal
 */
function backtestSingleCombination($pdo, $method, $column) {
    $draws = getAllDraws($pdo);
    $values = array_column($draws, $column);
    $totalDraws = count($values);
    
    if ($totalDraws < 20) return null;
    
    // Dynamic range: from 10 to 80% of available data
    $minWindow = 10;
    $maxWindow = min(500, floor($totalDraws * 0.8));
    
    $bestWindow = $minWindow;
    $bestError = INF;
    $bestPredictions = [];
    
    echo "    Testing windows {$minWindow} to {$maxWindow}... ";
    $startTime = microtime(true);
    
    // Use progressive sampling for large ranges
    if ($maxWindow - $minWindow > 200) {
        // Phase 1: Coarse scan (every 20 windows)
        $coarseBest = $minWindow;
        for ($window = $minWindow; $window <= $maxWindow; $window += 20) {
            $error = testWindowAccuracy($values, $method, $window, $totalDraws);
            if ($error < $bestError) {
                $bestError = $error;
                $coarseBest = $window;
            }
        }
        
        // Phase 2: Fine scan around coarse best (±30, test every window)
        $fineStart = max($minWindow, $coarseBest - 30);
        $fineEnd = min($maxWindow, $coarseBest + 30);
        
        for ($window = $fineStart; $window <= $fineEnd; $window++) {
            $error = testWindowAccuracy($values, $method, $window, $totalDraws);
            if ($error < $bestError) {
                $bestError = $error;
                $bestWindow = $window;
            }
        }
        
        // Phase 3: Ultra-fine scan if needed (check neighbors of best)
        for ($window = max($minWindow, $bestWindow - 5); $window <= min($maxWindow, $bestWindow + 5); $window++) {
            $error = testWindowAccuracy($values, $method, $window, $totalDraws);
            if ($error < $bestError) {
                $bestError = $error;
                $bestWindow = $window;
            }
        }
    } else {
        // Test EVERY window (when range is small)
        for ($window = $minWindow; $window <= $maxWindow; $window++) {
            $error = testWindowAccuracy($values, $method, $window, $totalDraws);
            if ($error < $bestError) {
                $bestError = $error;
                $bestWindow = $window;
            }
        }
    }
    
    $duration = round(microtime(true) - $startTime, 2);
    echo "done in {$duration}s\n";
    
    // Get final prediction using optimal window
    $history = array_slice($values, 0, $bestWindow);
    $result = predictColumnWithConfidence($history, $method, $bestWindow);
    
    return [
        'optimal_window' => $bestWindow,
        'error' => round($bestError, 2),
        'prediction' => $result['prediction'],
        'confidence' => $result['confidence'],
        'windows_tested' => ($maxWindow - $minWindow + 1)
    ];
}

/**
 * Test a specific window size and return average error
 */
function testWindowAccuracy($values, $method, $window, $totalDraws) {
    $totalError = 0;
    $testCount = 0;
    
    // Test on last 10 draws for better accuracy
    $testStart = max($window + 1, $totalDraws - 15);
    
    for ($testIdx = $testStart; $testIdx < $totalDraws; $testIdx++) {
        $history = array_slice($values, $testIdx - $window, $window);
        $actual = $values[$testIdx];
        $result = predictColumnWithConfidence($history, $method, $window);
        $error = abs($result['prediction'] - $actual);
        $totalError += $error;
        $testCount++;
    }
    
    return $testCount > 0 ? $totalError / $testCount : INF;
}
/**
 * Initialize queue with all methods (ONE task per method)
 */
function initializeQueue($pdo, $methods) {
    echo "Initializing backtest queue (one task per method)...\n";
    
    foreach ($methods as $method) {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO m6_backtest_queue (method, status)
            VALUES (:method, 'pending')
        ");
        $stmt->execute([':method' => $method]);
    }
    
    $total = count($methods);
    echo "Queue initialized with {$total} tasks (each processes all 7 columns).\n";
}

/**
 * Show queue progress
 */
function showQueueProgress($pdo) {
    $stmt = $pdo->query("
        SELECT 
            status,
            COUNT(*) as count
        FROM m6_backtest_queue
        GROUP BY status
    ");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total = 0;
    $completed = 0;
    foreach ($stats as $stat) {
        $total += $stat['count'];
        if ($stat['status'] == 'completed') {
            $completed = $stat['count'];
        }
    }
    
    $percent = round(($completed / $total) * 100, 1);
    echo "📊 Progress: {$completed}/{$total} methods ({$percent}%)\n";
    
    foreach ($stats as $stat) {
        if ($stat['status'] == 'pending') {
            echo "   ⏳ Pending: {$stat['count']} methods\n";
        } elseif ($stat['status'] == 'processing') {
            echo "   🔄 Processing: {$stat['count']} methods\n";
        } elseif ($stat['status'] == 'failed') {
            echo "   ❌ Failed: {$stat['count']} methods\n";
        }
    }
}

/**
 * Show full queue status
 */
function showQueueStatus($pdo) {
    echo "\n=== Backtest Queue Status ===\n";
    showQueueProgress($pdo);
    
    $stmt = $pdo->query("
        SELECT method, status, results, error_message, started_at, completed_at
        FROM m6_backtest_queue
        ORDER BY FIELD(status, 'completed', 'processing', 'pending', 'failed'), id
    ");
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nDetailed Status:\n";
    echo str_repeat("-", 80) . "\n";
    foreach ($tasks as $task) {
        $statusIcon = '';
        if ($task['status'] == 'completed') $statusIcon = '✅';
        elseif ($task['status'] == 'processing') $statusIcon = '🔄';
        elseif ($task['status'] == 'pending') $statusIcon = '⏳';
        elseif ($task['status'] == 'failed') $statusIcon = '❌';
        else $statusIcon = '❓';
        
        echo sprintf("%s %-12s", $statusIcon, $task['method']);
        
        if ($task['status'] == 'completed' && $task['results']) {
            $results = json_decode($task['results'], true);
            echo " - ";
            foreach ($results as $col => $data) {
                if ($data) {
                    echo "{$col}:{$data['optimal_window']} ";
                }
            }
        } elseif ($task['status'] == 'failed') {
            echo " - " . substr($task['error_message'], 0, 50);
        }
        echo "\n";
    }
}

/**
 * Reset queue
 */
function resetQueue($pdo) {
    $stmt = $pdo->exec("DELETE FROM m6_backtest_queue");
    echo "Queue reset. Run process again to re-initialize.\n";
}

/**
 * Check if all methods are done, then publish
 */
function publishIfReady($pdo) {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
        FROM m6_backtest_queue
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total = $result['total'];
    $completed = $result['completed'];
    $failed = $result['failed'];
    
    if ($total > 0 && $completed == $total) {
        echo "✅ All methods completed! Publishing blog post...\n";
        require_once "M6_blogpost.php";
        publishPredictionPost($pdo);
    } elseif ($failed > 0) {
        echo "⚠️ {$failed} method(s) failed. Please check and reset if needed.\n";
        showQueueProgress($pdo);
    } else {
        $remaining = $total - $completed;
        echo "⏳ Waiting for {$remaining} method(s) to complete.\n";
        showQueueProgress($pdo);
    }
}
?>