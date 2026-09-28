<?php
/**
 * Mark Six Post-Draw Analysis Blog Post Generator
 * 
 * Usage:
 * php M6_analysis_blogpost.php --action=analyze --draw=482
 * php M6_analysis_blogpost.php --action=publish --draw=latest
 * 
 * Web:
 * https://buycarl.com/00/M6_analysis_blogpost.php?action=publish&draw=latest
 */

include_once ("lib/constants.php");
require_once "M6_prediction_functions.php";

date_default_timezone_set('Asia/Hong_Kong');
// These functions are in M6_prediction_functions.php, but we need to ensure they're accessible
// If you get errors about undefined functions, uncomment these:

function getHistoricalAccuracySummary($pdo, $limit = 20) {
    return getHistoricalAccuracySummary($pdo, $limit);
}

if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

// ========== HELPER FUNCTIONS ==========

function formatDrawDate($memo) {
    if (preg_match('/\d{2}\/\d{2}>(\d{4})\/(\d{2})\/(\d{2})/', $memo, $matches)) {
        return "{$matches[1]}年{$matches[2]}月{$matches[3]}日";
    }
    if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $memo, $matches)) {
        return "{$matches[1]}年{$matches[2]}月{$matches[3]}日";
    }
    if (preg_match('/(\d{4})\/(\d{2})\/(\d{2})/', $memo, $matches)) {
        return "{$matches[1]}年{$matches[2]}月{$matches[3]}日";
    }
    return date('Y年m月d日');
}

function findChineseFont() {
    $fontPaths = [
        __DIR__ . '/fonts/NotoSansTC-VariableFont_wght.ttf',
        __DIR__ . '/fonts/NotoSansCJKjp-Regular.otf',
        __DIR__ . '/fonts/wqy-microhei.ttc',
    ];
    foreach ($fontPaths as $path) {
        if (file_exists($path) && filesize($path) > 1000000) {
            return $path;
        }
    }
    return null;
}

function getNumberColor($num) {
    if ($num >= 1 && $num <= 9) return ['bg' => '#e74c3c'];
    if ($num >= 10 && $num <= 19) return ['bg' => '#3498db'];
    if ($num >= 20 && $num <= 29) return ['bg' => '#2ecc71'];
    if ($num >= 30 && $num <= 39) return ['bg' => '#f39c12'];
    if ($num >= 40 && $num <= 49) return ['bg' => '#9b59b6'];
    return ['bg' => '#95a5a6'];
}

// ========== PARAMETER PARSING ==========

$action = $_GET['action'] ?? null;
$drawId = $_GET['draw'] ?? null;
$isLatest = isset($_GET['latest']) && ($_GET['latest'] == '1' || $_GET['latest'] == 'true');

if (php_sapi_name() === 'cli') {
    $options = getopt("", ["action:", "draw:", "latest"]);
    $action = $options['action'] ?? $action;
    $drawId = $options['draw'] ?? $drawId;
    $isLatest = isset($options['latest']) || $isLatest;
}

if ($drawId === 'latest') {
    $isLatest = true;
    $drawId = null;
}

$action = $action ?? 'analyze';

if (php_sapi_name() !== 'cli') {
    echo "Debug: action={$action}, drawId={$drawId}, isLatest=" . ($isLatest ? 'true' : 'false') . "\n";
}

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '+08:00'");
    
    switch ($action) {
        case 'analyze':
            if ($isLatest) {
                analyzeLatestDraw($pdo);
            } elseif ($drawId) {
                analyzeDraw($pdo, $drawId);
            } else {
                echo "Usage: action=analyze&draw=482 or action=analyze&latest=1\n";
            }
            break;
            
        case 'publish':
            if ($drawId) {
                publishAnalysisPost($pdo, $drawId);
            } elseif ($isLatest) {
                $stmt = $pdo->query("SELECT MAX(id) as latest_id FROM `00m6`");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $latestId = $result['latest_id'];
                if ($latestId) {
                    publishAnalysisPost($pdo, $latestId);
                } else {
                    echo "No draws found!\n";
                }
            } else {
                echo "Usage: action=publish&draw=482 or action=publish&draw=latest\n";
            }
            break;
            
        default:
            echo "Usage: action=analyze|publish\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    error_log("M6 Analysis Error: " . $e->getMessage());
    exit(1);
}

// ========== CORE FUNCTIONS ==========

function analyzeDraw($pdo, $drawId) {
    echo "=== Analyzing Draw #{$drawId} ===\n";
    
    if ($drawId === 'latest' || $drawId === null) {
        $stmt = $pdo->query("SELECT MAX(id) as latest_id FROM `00m6`");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $drawId = $result['latest_id'];
        if (!$drawId) {
            echo "No draws found!\n";
            return null;
        }
        echo "Latest draw ID: {$drawId}\n";
    }
    
    $stmt = $pdo->prepare("SELECT * FROM `00m6` WHERE id = :draw_id");
    $stmt->execute([':draw_id' => $drawId]);
    $actual = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$actual) {
        echo "Draw #{$drawId} not found!\n";
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM m6_position_predictions WHERE target_draw_id = :draw_id ORDER BY method");
    $stmt->execute([':draw_id' => $drawId]);
    $predictions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($predictions)) {
        echo "No predictions found for Draw #{$drawId}. Run backtest first.\n";
        return;
    }
    
    $accuracyResults = [];
    $columns = ['no1', 'no2', 'no3', 'no4', 'no5', 'no6', 'no7'];
    
    foreach ($predictions as $pred) {
        $method = $pred['method'];
        $correctCount = 0;
        $details = [];
        
        foreach ($columns as $col) {
            $predicted = $pred['pred_' . $col];
            $actualVal = $actual[$col];
            $isCorrect = ($predicted == $actualVal);
            if ($isCorrect) $correctCount++;
            $details[$col] = [
                'predicted' => $predicted,
                'actual' => $actualVal,
                'correct' => $isCorrect
            ];
        }
        
        $accuracy = round(($correctCount / 7) * 100, 1);
        $accuracyResults[$method] = [
            'correct_count' => $correctCount,
            'accuracy' => $accuracy,
            'details' => $details
        ];
    }
    
    $consensus = getConsensusPrediction($pdo, $drawId);
    $consensusCorrect = 0;
    $consensusDetails = [];
    foreach ($columns as $col) {
        $isCorrect = ($consensus[$col] == $actual[$col]);
        if ($isCorrect) $consensusCorrect++;
        $consensusDetails[$col] = [
            'predicted' => $consensus[$col],
            'actual' => $actual[$col],
            'correct' => $isCorrect
        ];
    }
    $consensusAccuracy = round(($consensusCorrect / 7) * 100, 1);
    
    uasort($accuracyResults, function($a, $b) {
        return $b['accuracy'] <=> $a['accuracy'];
    });
    
    storeAnalysisResults($pdo, $drawId, $accuracyResults, $consensusAccuracy, $consensusCorrect);
    
    echo "\n📊 Draw #{$drawId} Results Analysis\n";
    echo "Actual Numbers: ";
    foreach ($columns as $col) {
        echo $actual[$col] . " ";
    }
    echo "\n\n";
    echo "Method Performance:\n";
    echo str_repeat("-", 50) . "\n";
    foreach ($accuracyResults as $method => $data) {
        echo sprintf("%-12s: %d/7 correct (%.1f%%) %s\n", 
            $method, $data['correct_count'], $data['accuracy'],
            $data['accuracy'] >= 42.8 ? "🏆" : ($data['accuracy'] >= 28.5 ? "📌" : "")
        );
    }
    echo str_repeat("-", 50) . "\n";
    echo sprintf("%-12s: %d/7 correct (%.1f%%)\n", "Consensus", $consensusCorrect, $consensusAccuracy);
    
    return [
        'draw_id' => $drawId,
        'actual' => $actual,
        'accuracy_results' => $accuracyResults,
        'consensus_accuracy' => $consensusAccuracy,
        'consensus_correct' => $consensusCorrect,
        'consensus_details' => $consensusDetails
    ];
}

function analyzeLatestDraw($pdo) {
    $stmt = $pdo->query("SELECT MAX(id) as latest_id FROM `00m6`");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $latestId = $result['latest_id'];
    if ($latestId) {
        analyzeDraw($pdo, $latestId);
    } else {
        echo "No draws found!\n";
    }
}

function storeAnalysisResults($pdo, $drawId, $accuracyResults, $consensusAccuracy, $consensusCorrect) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `m6_draw_analysis` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `draw_id` INT NOT NULL,
            `method` VARCHAR(30) NOT NULL,
            `correct_count` INT DEFAULT 0,
            `accuracy` DECIMAL(5,2) DEFAULT 0,
            `rank_position` INT DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_draw_method (draw_id, method)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    $rank = 1;
    foreach ($accuracyResults as $method => $data) {
        $stmt = $pdo->prepare("
            INSERT INTO m6_draw_analysis (draw_id, method, correct_count, accuracy, rank_position)
            VALUES (:draw_id, :method, :correct, :accuracy, :rank)
            ON DUPLICATE KEY UPDATE
            correct_count = VALUES(correct_count),
            accuracy = VALUES(accuracy),
            rank_position = VALUES(rank_position)
        ");
        $stmt->execute([
            ':draw_id' => $drawId,
            ':method' => $method,
            ':correct' => $data['correct_count'],
            ':accuracy' => $data['accuracy'],
            ':rank' => $rank
        ]);
        $rank++;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO m6_draw_analysis (draw_id, method, correct_count, accuracy)
        VALUES (:draw_id, 'Consensus', :correct, :accuracy)
        ON DUPLICATE KEY UPDATE
        correct_count = VALUES(correct_count),
        accuracy = VALUES(accuracy)
    ");
    $stmt->execute([
        ':draw_id' => $drawId,
        ':correct' => $consensusCorrect,
        ':accuracy' => $consensusAccuracy
    ]);
    
    echo "Analysis results stored in database.\n";
}

// function getHistoricalAccuracySummary($pdo, $limit = 20) {
//     $stmt = $pdo->prepare("
//         SELECT 
//             method,
//             AVG(accuracy) as avg_accuracy,
//             SUM(correct_count) as total_correct,
//             COUNT(*) as draws_analyzed,
//             AVG(rank_position) as avg_rank
//         FROM m6_draw_analysis
//         WHERE method != 'Consensus'
//         GROUP BY method
//         ORDER BY avg_accuracy DESC
//     ");
//     $stmt->execute();
//     $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
//     $stmt = $pdo->prepare("
//         SELECT method, AVG(accuracy) as recent_avg_accuracy
//         FROM m6_draw_analysis
//         WHERE method != 'Consensus'
//         GROUP BY method
//         ORDER BY draw_id DESC
//         LIMIT :limit
//     ");
//     $stmt->bindValue(':limit', $limit * 13, PDO::PARAM_INT);
//     $stmt->execute();
//     $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
//     $recentMap = [];
//     foreach ($recent as $r) {
//         $recentMap[$r['method']] = $r['recent_avg_accuracy'];
//     }
    
//     foreach ($results as &$r) {
//         $r['recent_avg'] = $recentMap[$r['method']] ?? $r['avg_accuracy'];
//         $r['trend'] = $r['recent_avg'] - $r['avg_accuracy'];
//     }
    
//     return $results;
// }

// function getBestMethodForNextDraw($pdo) {
//     $stmt = $pdo->query("
//         SELECT method, AVG(accuracy) as avg_accuracy, AVG(rank_position) as avg_rank, COUNT(*) as sample_size
//         FROM m6_draw_analysis
//         WHERE method != 'Consensus'
//         GROUP BY method
//         ORDER BY avg_accuracy DESC, avg_rank ASC
//         LIMIT 3
//     ");
//     $topMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
//     $bestPerColumn = [];
//     $columns = ['no1', 'no2', 'no3', 'no4', 'no5', 'no6', 'no7'];
    
//     foreach ($columns as $col) {
//         $stmt = $pdo->prepare("
//             SELECT p.method, COUNT(*) as total_predictions,
//                   SUM(CASE WHEN p.pred_$col = a.$col THEN 1 ELSE 0 END) as correct_count,
//                   ROUND(SUM(CASE WHEN p.pred_$col = a.$col THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as accuracy
//             FROM m6_position_predictions p
//             JOIN `00m6` a ON p.target_draw_id = a.id
//             WHERE p.pred_$col IS NOT NULL AND a.$col IS NOT NULL
//             GROUP BY p.method
//             ORDER BY accuracy DESC
//             LIMIT 1
//         ");
//         $stmt->execute();
//         $best = $stmt->fetch(PDO::FETCH_ASSOC);
//         if ($best && $best['accuracy'] > 0) {
//             $bestPerColumn[$col] = $best;
//         }
//     }
    
//     return [
//         'overall_best' => $topMethods[0] ?? null,
//         'top_three' => $topMethods,
//         'per_column_best' => $bestPerColumn
//     ];
// }

function publishAnalysisPost($pdo, $drawId) {
    if ($drawId === 'latest' || $drawId === null) {
        $stmt = $pdo->query("SELECT MAX(id) as latest_id FROM `00m6`");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $drawId = $result['latest_id'];
        if (!$drawId) {
            echo "No draws found!\n";
            return;
        }
        echo "Latest draw ID: {$drawId}\n";
    }
    
    // Check for duplicate using draw_year + draw_nos
    $stmt = $pdo->prepare("SELECT * FROM `00m6` WHERE id = :draw_id");
    $stmt->execute([':draw_id' => $drawId]);
    $actual = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$actual) {
        echo "Draw #{$drawId} not found!\n";
        return;
    }
    
    $drawYear = $actual['year'];
    $drawNos = $actual['nos'];
    $drawNumber = getDrawNumber($drawYear, $drawNos);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM m6_blog_posts WHERE draw_year = :year AND draw_nos = :nos AND post_type = 'analysis'");
    $stmt->execute([':year' => $drawYear, ':nos' => $drawNos]);
    if ($stmt->fetchColumn() > 0) {
        echo "⚠️ Analysis blog post for {$drawNumber} already exists. Skipping.\n";
        return;
    }
    
    $analysis = analyzeDraw($pdo, $drawId);
    if (!$analysis) {
        echo "Analysis failed!\n";
        return;
    }
    
    $historicalSummary = getHistoricalAccuracySummary($pdo, 20);
    $nextPredictionAdvice = getBestMethodForNextDraw($pdo);
    
    $formattedDate = formatDrawDate($actual['memo']);
    
    $title = "📊 六合彩{$drawNumber}賽後分析 | 預測準確度排行榜 | {$formattedDate}";
    $content = generateAnalysisContent($pdo, $analysis, $historicalSummary, $nextPredictionAdvice, $actual);
    $excerpt = generateAnalysisExcerpt($analysis);
    
    $featuredImageId = generateAnalysisFeaturedImage($drawNumber, $formattedDate, $analysis['consensus_accuracy']);
    
    $postId = postAnalysisToWordPress($title, $content, $excerpt, $featuredImageId, $drawNumber, $formattedDate);
    
    if ($postId) {
        $stmt = $pdo->prepare("
            INSERT INTO m6_blog_posts (draw_id, draw_year, draw_nos, post_type, wp_post_id, title, prediction_date)
            VALUES (:draw_id, :year, :nos, 'analysis', :post_id, :title, :date)
        ");
        $stmt->execute([
            ':draw_id' => $drawId,
            ':year' => $drawYear,
            ':nos' => $drawNos,
            ':post_id' => $postId,
            ':title' => $title,
            ':date' => date('Y-m-d')
        ]);
        echo "✅ Analysis blog post published! ID: {$postId}\n";
    } else {
        echo "❌ Failed to publish\n";
    }
}

function generateAnalysisExcerpt($analysis) {
    $topMethod = key($analysis['accuracy_results']);
    $topAccuracy = $analysis['accuracy_results'][$topMethod]['accuracy'];
    return "📊 六合彩第{$analysis['draw_id']}期賽後分析。{$topMethod}以{$topAccuracy}%準確率奪冠！";
}

function generateAnalysisContent($pdo, $analysis, $historicalSummary, $nextPredictionAdvice, $actual) {
    $drawId = $analysis['draw_id'];
    $accuracyResults = $analysis['accuracy_results'];
    $consensusAccuracy = $analysis['consensus_accuracy'];
    $formattedDate = formatDrawDate($actual['memo']);
    
    $columns = ['no1', 'no2', 'no3', 'no4', 'no5', 'no6', 'no7'];
    
    $emojis = [
        'WgtMA' => '📊', 'Mode' => '🔥', 'ExpS' => '📈', 'AutoCorr' => '🔄',
        'Seasonal' => '📅', 'FreqRec' => '⚡', 'KNN' => '👥', 'Balance' => '⚖️',
        'SumTrend' => '📉', 'Heatmap' => '🗺️', 'Gap' => '⏰', 'Markov' => '🔗', 'LinReg' => '📐'
    ];
    
    $html = '<div style="max-width:100%; overflow-x:auto; font-family: Arial, sans-serif;">';
    
    // Header
    $html .= '<div style="background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; padding: 30px; text-align: center; border-radius: 15px; margin-bottom: 25px;">';
    $html .= '<h1 style="color: #FFD700; margin: 0 0 10px;">📊 六合彩第' . $drawId . '期賽後分析</h1>';
    $html .= '<p style="margin: 0; font-size: 18px;">開彩日期: ' . $formattedDate . '</p>';
    $html .= '</div>';
    
    // ========== FIXED: Actual results - NO DUPLICATE ==========
    $html .= '<div style="background: #e8f5e9; border: 2px solid #4caf50; border-radius: 15px; padding: 20px; margin-bottom: 25px; text-align: center;">';
    $html .= '<h3 style="margin: 0 0 15px 0;">🎯 實際開獎號碼</h3>';
    $html .= '<div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; align-items: center;">';
    
    // Show all 7 numbers (no1 to no7) - each with correct color
    foreach ($columns as $col) {
        $num = $actual[$col];
        $color = getNumberColor($num);
        $html .= '<div style="background: ' . $color['bg'] . '; color: white; width: 55px; height: 55px; line-height: 55px; border-radius: 50%; text-align: center; font-size: 24px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">';
        $html .= $num;
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '<p style="margin: 15px 0 0 0; font-size: 11px; color: #888;">🔴1-9 🔵10-19 🟢20-29 🟡30-39 🟣40-49</p>';
    $html .= '</div>';
    
    // ========== TOP METHODS (Show all tied winners) ==========
    $topAccuracy = 0;
    foreach ($accuracyResults as $data) {
        if ($data['accuracy'] > $topAccuracy) {
            $topAccuracy = $data['accuracy'];
        }
    }
    
    $topMethods = [];
    foreach ($accuracyResults as $method => $data) {
        if ($data['accuracy'] == $topAccuracy) {
            $topMethods[] = [
                'method' => $method,
                'correct_count' => $data['correct_count'],
                'accuracy' => $data['accuracy']
            ];
        }
    }
    
    $html .= '<div style="background: #FFF8E1; border: 2px solid #FFC107; border-radius: 15px; padding: 20px; margin-bottom: 25px;">';
    $html .= '<h3 style="margin: 0 0 15px;">🏆 本期最佳預測方法</h3>';
    
    if (count($topMethods) == 1) {
        $winner = $topMethods[0];
        $html .= '<div style="font-size: 48px; font-weight: bold; color: #e74c3c; text-align: center;">' . $winner['method'] . '</div>';
        $html .= '<div style="font-size: 24px; text-align: center; color: #666;">準確率: ' . $winner['accuracy'] . '% (' . $winner['correct_count'] . '/7)</div>';
        $html .= '<p style="margin: 15px 0 0 0; font-size: 14px; text-align: center;">🎉 恭喜 ' . $winner['method'] . ' 成為本期預測冠軍！</p>';
    } else {
        $html .= '<div style="font-size: 24px; text-align: center; color: #666; margin-bottom: 10px;">🏆 並列冠軍！</div>';
        $html .= '<div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 30px;">';
        foreach ($topMethods as $winner) {
            $html .= '<div style="text-align: center;">';
            $html .= '<div style="font-size: 36px; font-weight: bold; color: #e74c3c;">' . $winner['method'] . '</div>';
            $html .= '<div style="font-size: 18px; color: #666;">準確率: ' . $winner['accuracy'] . '% (' . $winner['correct_count'] . '/7)</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '<p style="margin: 15px 0 0 0; font-size: 14px; text-align: center;">🎉 恭喜以上方法並列本期預測冠軍！</p>';
    }
    
    $html .= '</div>';
    
    // ========== COMPARISON TABLE - FIXED: no wrap, no checkmark ==========
    $html .= '<h3>📋 預測 vs 實際對比表</h3>';
    $html .= '<div style="overflow-x: auto; max-width: 100%;">';
    $html .= '<table style="width: 100%; border-collapse: collapse; background: #f8f9fa; border-radius: 12px; overflow: hidden; font-size: 13px; min-width: 700px; table-layout: fixed;">';
    $html .= '<thead>';
    $html .= '<tr style="background: #2c3e50; color: white;">';
    $html .= '<th style="padding: 10px; text-align: left; white-space: nowrap;">預測方法</th>';
    $html .= '<th style="padding: 10px; text-align: center; white-space: nowrap;">no1</th>';
    $html .= '<th style="padding: 10px; text-align: center; white-space: nowrap;">no2</th>';
    $html .= '<th style="padding: 10px; text-align: center; white-space: nowrap;">no3</th>';
    $html .= '<th style="padding: 10px; text-align: center; white-space: nowrap;">no4</th>';
    $html .= '<th style="padding: 10px; text-align: center; white-space: nowrap;">no5</th>';
    $html .= '<th style="padding: 10px; text-align: center; white-space: nowrap;">no6</th>';
    $html .= '<th style="padding: 10px; text-align: center; white-space: nowrap; background: #f39c12;">no7 ⭐</th>';
    $html .= '<th style="padding: 10px; text-align: center; white-space: nowrap;">✅ 正確數</th>';
    $html .= '<th style="padding: 10px; text-align: center; white-space: nowrap;">準確率</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    $stmt = $pdo->prepare("
        SELECT * FROM m6_position_predictions 
        WHERE target_draw_id = :draw_id
        ORDER BY FIELD(method, 'WgtMA', 'Mode', 'ExpS', 'AutoCorr', 'Seasonal', 'FreqRec', 'KNN', 'Balance', 'SumTrend', 'Heatmap', 'Gap', 'Markov', 'LinReg')
    ");
    $stmt->execute([':draw_id' => $drawId]);
    $allPredictions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($allPredictions as $pred) {
        $method = $pred['method'];
        $emoji = $emojis[$method] ?? '📌';
        $methodData = $accuracyResults[$method] ?? ['correct_count' => 0, 'accuracy' => 0];
        
        $html .= '<tr style="border-bottom: 1px solid #e0e0e0;">';
        $html .= '<td style="padding: 8px; font-weight: bold; white-space: nowrap;">' . $emoji . ' ' . $method . '</td>';
        
        foreach ($columns as $col) {
            $predicted = $pred['pred_' . $col];
            $actualVal = $actual[$col];
            $isCorrect = ($predicted == $actualVal);
            
            if ($isCorrect) {
                $numColor = getNumberColor($predicted);
                $html .= '<td style="padding: 8px; text-align: center; white-space: nowrap;">';
                $html .= '<span style="display: inline-block; background: ' . $numColor['bg'] . '; color: white; width: 32px; height: 32px; line-height: 32px; border-radius: 50%; font-weight: bold; font-size: 14px;">' . $predicted . '</span>';
                $html .= '</td>';
            } else {
                $html .= '<td style="padding: 8px; text-align: center; color: #999; white-space: nowrap;">' . $predicted . '</td>';
            }
        }
        
        $html .= '<td style="padding: 8px; text-align: center; font-weight: bold; white-space: nowrap;">' . $methodData['correct_count'] . '/7</td>';
        $html .= '<td style="padding: 8px; text-align: center; font-weight: bold; color: ' . ($methodData['accuracy'] >= 42.8 ? '#4caf50' : '#ff9800') . '; white-space: nowrap;">' . $methodData['accuracy'] . '%</td>';
        $html .= '</tr>';
    }
    
    // Consensus row
    $consensusDetails = $analysis['consensus_details'];
    $html .= '<tr style="background: #ffeaa7; border-top: 2px solid #f39c12;">';
    $html .= '<td style="padding: 10px; font-weight: bold; white-space: nowrap;">🤝 共識決 (多數決)</td>';
    
    foreach ($columns as $col) {
        $predicted = $consensusDetails[$col]['predicted'];
        $actualVal = $consensusDetails[$col]['actual'];
        $isCorrect = $consensusDetails[$col]['correct'];
        
        if ($isCorrect) {
            $numColor = getNumberColor($predicted);
            $html .= '<td style="padding: 10px; text-align: center; white-space: nowrap;">';
            $html .= '<span style="display: inline-block; background: ' . $numColor['bg'] . '; color: white; width: 32px; height: 32px; line-height: 32px; border-radius: 50%; font-weight: bold; font-size: 14px;">' . $predicted . '</span>';
            $html .= '</td>';
        } else {
            $html .= '<td style="padding: 10px; text-align: center; white-space: nowrap;">' . $predicted . '</td>';
        }
    }
    
    $html .= '<td style="padding: 10px; text-align: center; font-weight: bold; white-space: nowrap;">' . $analysis['consensus_correct'] . '/7</td>';
    $html .= '<td style="padding: 10px; text-align: center; font-weight: bold; white-space: nowrap;">' . $consensusAccuracy . '%</td>';
    $html .= '</tr>';
    
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    
    // ========== FIXED: Historical Accuracy (with margin-top) ==========
    $html .= '<h3 style="margin-top: 30px;">📈 歷史準確度排行榜</h3>'; // (過去20期)
    $html .= '<div style="overflow-x: auto; max-width: 100%;">';
    $html .= '<table style="width: 100%; border-collapse: collapse; background: #f8f9fa; border-radius: 12px; overflow: hidden; font-size: 14px;">';
    $html .= '<thead>';
    $html .= '<tr style="background: #2c3e50; color: white;">';
    $html .= '<th style="padding: 10px; text-align: left; white-space: nowrap;">方法</th>';
    $html .= '<th style="padding: 10px; text-align: center; white-space: nowrap;">平均準確率</th>';
    $html .= '<th style="padding: 10px; text-align: center; white-space: nowrap;">總正確數</th>';
    $html .= '<th style="padding: 10px; text-align: center; white-space: nowrap;">分析期數</th>';
    $html .= '<th style="padding: 10px; text-align: center; white-space: nowrap;">近期趨勢</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    foreach ($historicalSummary as $method) {
        $trendIcon = '';
        if ($method['trend'] > 5) $trendIcon = '📈 上升';
        elseif ($method['trend'] < -5) $trendIcon = '📉 下降';
        else $trendIcon = '➡️ 平穩';
        
        $html .= '<tr style="border-bottom: 1px solid #e0e0e0;">';
        $html .= '<td style="padding: 10px; font-weight: bold; white-space: nowrap;">' . ($emojis[$method['method']] ?? '📌') . ' ' . $method['method'] . '</td>';
        $html .= '<td style="padding: 10px; text-align: center; font-weight: bold; color: #e74c3c; white-space: nowrap;">' . round($method['avg_accuracy'], 1) . '%</td>';
        $html .= '<td style="padding: 10px; text-align: center; white-space: nowrap;">' . $method['total_correct'] . '</td>';
        $html .= '<td style="padding: 10px; text-align: center; white-space: nowrap;">' . $method['draws_analyzed'] . '</td>';
        $html .= '<td style="padding: 10px; text-align: center; white-space: nowrap;">' . $trendIcon . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    
    // ========== FIXED: Next Prediction Advice ==========
    $bestOverall = $nextPredictionAdvice['overall_best'];
    $bestPerColumn = $nextPredictionAdvice['per_column_best'];
    
    $html .= '<div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 25px; margin-top: 25px; border-radius: 15px;">';
    $html .= '<h3 style="margin: 0 0 15px; color: #FFD700;">🔮 下一期預測建議</h3>';
    
    if ($bestOverall) {
        $html .= '<p style="font-size: 18px;">🏆 <strong>整體最佳方法</strong>: ' . $bestOverall['method'] . ' (平均準確率 ' . round($bestOverall['avg_accuracy'], 1) . '%)</p>';
    }
    
    $html .= '<p style="margin-top: 15px;"><strong>📊 各位置最佳方法 (基於歷史數據):</strong></p>';
    $html .= '<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 10px;">';
    foreach ($bestPerColumn as $col => $best) {
        $html .= '<div style="white-space: nowrap;">• ' . $col . ': <strong>' . $best['method'] . '</strong> (' . round($best['accuracy'], 1) . '%)</div>';
    }
    $html .= '</div>';
    
    $html .= '<p style="margin-top: 20px; font-size: 14px; opacity: 0.9;">💡 建議: 優先採用歷史表現最佳的方法進行下一期預測</p>';
    $html .= '</div>';
    
    // Disclaimer
    $html .= '<div style="text-align: center; margin-top: 25px; padding: 15px; font-size: 11px; color: #999; border-top: 1px solid #ddd;">';
    $html .= '📊 六合彩賽後分析系統 | 基於歷史數據統計 | 僅供參考<br>';
    $html .= '綠色圓圈表示該方法預測正確 | 最後更新: ' . date('Y-m-d H:i:s');
    $html .= '</div>';
    
    $html .= '</div>';
    
    return $html;
}

// ========== IMAGE GENERATION FUNCTIONS ==========

function generateAnalysisFeaturedImage($drawNumber, $formattedDate, $topAccuracy) {
    $width = 1200;
    $height = 630;
    $image = imagecreatetruecolor($width, $height);
    imageantialias($image, true);
    
    // RED + BLUE + GREEN gradient
    for ($i = 0; $i < $height; $i++) {
        $ratio = $i / $height;
        $r = 200 + (255 - 200) * (1 - $ratio);
        $g = 50 + (200 - 50) * (1 - abs($ratio - 0.5) * 2);
        $b = 50 + (200 - 50) * $ratio;
        $color = imagecolorallocate($image, (int)$r, (int)$g, (int)$b);
        imageline($image, 0, $i, $width, $i, $color);
    }
    
    $white = imagecolorallocate($image, 255, 255, 255);
    $gold = imagecolorallocate($image, 255, 215, 0);
    $darkText = imagecolorallocate($image, 30, 30, 30);
    
    $fontPath = findChineseFont();
    
    if ($fontPath) {
        imagettftext($image, 60, 0, 80, 120, $gold, $fontPath, "六合彩賽後分析");
        imagettftext($image, 55, 0, 80, 200, $darkText, $fontPath, "第{$drawNumber}期");
        imagettftext($image, 35, 0, 80, 280, $darkText, $fontPath, "最佳準確率: {$topAccuracy}%");
        imagettftext($image, 28, 0, 80, 370, $gold, $fontPath, "預測方法排行榜");
        $currentYear = date('Y');
        imagettftext($image, 22, 0, 80, 550, $darkText, $fontPath, "© {$currentYear} BuyCarl.com | 數據分析");
    } else {
        imagestring($image, 5, 80, 100, "MARK SIX ANALYSIS", $gold);
        imagestring($image, 5, 80, 160, "Draw #" . $drawNumber, $white);
        imagestring($image, 4, 80, 220, "Best Accuracy: {$topAccuracy}%", $white);
    }
    
    imagerectangle($image, 5, 5, $width - 6, $height - 6, $gold);
    imagerectangle($image, 8, 8, $width - 9, $height - 9, $white);
    
    $fileName = "m6-analysis-{$drawNumber}-" . time() . ".png";
    $tempFile = tempnam(sys_get_temp_dir(), 'm6a_') . '.png';
    imagepng($image, $tempFile);
    imagedestroy($image);
    
    return uploadToWordPress($tempFile, $fileName);
}

// ========== WORDPRESS HELPER FUNCTIONS ==========

function getWpUsername() {
    global $wpConfig;
    return $wpConfig['username'] ?? 'your_username';
}

function getWpAppPassword() {
    global $wpConfig;
    return $wpConfig['app_password'] ?? 'your_app_password';
}

function uploadToWordPress($filePath, $fileName) {
    $username = getWpUsername();
    $password = getWpAppPassword();
    $url = 'https://buycarl.com/wp-json/wp/v2/media';
    
    $checkUrl = $url . '?search=' . urlencode($fileName);
    $ch = curl_init($checkUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . base64_encode($username . ':' . $password)]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $existing = json_decode($response, true);
        if (!empty($existing)) {
            foreach ($existing as $media) {
                if (basename($media['source_url']) === $fileName) return $media['id'];
            }
        }
    }
    
    $fileContent = file_get_contents($filePath);
    $fileType = mime_content_type($filePath);
    $boundary = '----WebKitFormBoundary' . md5(uniqid());
    $body = "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$fileName}\"\r\n";
    $body .= "Content-Type: {$fileType}\r\n\r\n" . $fileContent . "\r\n--{$boundary}--";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode($username . ':' . $password),
        'Content-Type: multipart/form-data; boundary=' . $boundary,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    return $result['id'] ?? null;
}

function postAnalysisToWordPress($title, $content, $excerpt, $featuredImageId, $drawNumber, $formattedDate) {
    $username = getWpUsername();
    $password = getWpAppPassword();
    
    $categoryId = getOrCreateCategory('Mark Six Analysis');
    $tagIds = getOrCreateTags(['六合彩', 'Mark Six', '賽後分析', '準確度排行榜']);
    
    $postData = [
        'title' => $title,
        'content' => $content,
        'excerpt' => $excerpt,
        'status' => 'publish',
        'categories' => [$categoryId],
        'tags' => $tagIds,
        'slug' => "mark-six-analysis-draw-{$drawNumber}"
    ];
    
    if ($featuredImageId) {
        $postData['featured_media'] = (int)$featuredImageId;
    }
    
    $ch = curl_init('https://buycarl.com/wp-json/wp/v2/posts');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($username . ':' . $password)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 201) {
        $result = json_decode($response, true);
        updateAnalysisYoastViaInternalScript($result['id'], $drawNumber, $formattedDate);
        return $result['id'];
    }
    return false;
}

function updateAnalysisYoastViaInternalScript($postId, $drawNumber, $formattedDate) {
    $secret_token = 'your_secret_token_2026';
    $focusKeyphrase = "六合彩第{$drawNumber}期賽後分析";
    $seoTitle = "六合彩第{$drawNumber}期賽後分析 | 預測準確度排行榜";
    $seoDescription = "六合彩第{$drawNumber}期賽後分析。比較13種預測方法的準確度，為下一期預測做準備。";
    $url = "https://buycarl.com/00/M6_blogpost_update_yoast.php?post_id={$postId}&token={$secret_token}&draw_number={$drawNumber}&draw_date=" . urlencode($formattedDate) . "&focus_keyphrase=" . urlencode($focusKeyphrase) . "&seo_title=" . urlencode($seoTitle) . "&seo_description=" . urlencode($seoDescription);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_exec($ch);
    curl_close($ch);
}

function getOrCreateCategory($name) {
    $username = getWpUsername();
    $password = getWpAppPassword();
    $url = 'https://buycarl.com/wp-json/wp/v2/categories?search=' . urlencode($name);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . base64_encode($username . ':' . $password)]);
    $response = curl_exec($ch);
    curl_close($ch);
    $cats = json_decode($response, true);
    if (!empty($cats)) return $cats[0]['id'];
    
    $ch = curl_init('https://buycarl.com/wp-json/wp/v2/categories');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['name' => $name, 'slug' => sanitizeTitle($name)]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Basic ' . base64_encode($username . ':' . $password)]);
    $response = curl_exec($ch);
    curl_close($ch);
    $new = json_decode($response, true);
    return $new['id'] ?? 1;
}

function getOrCreateTag($tagName) {
    $username = getWpUsername();
    $password = getWpAppPassword();
    $url = 'https://buycarl.com/wp-json/wp/v2/tags?search=' . urlencode($tagName);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . base64_encode($username . ':' . $password)]);
    $response = curl_exec($ch);
    curl_close($ch);
    $tags = json_decode($response, true);
    if (!empty($tags)) {
        foreach ($tags as $tag) if ($tag['name'] === $tagName) return $tag['id'];
    }
    $ch = curl_init('https://buycarl.com/wp-json/wp/v2/tags');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['name' => $tagName]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Basic ' . base64_encode($username . ':' . $password)]);
    $response = curl_exec($ch);
    curl_close($ch);
    $new = json_decode($response, true);
    return $new['id'] ?? null;
}

function getOrCreateTags($tagNames) {
    $ids = [];
    foreach ($tagNames as $name) {
        $id = getOrCreateTag($name);
        if ($id) $ids[] = $id;
    }
    return $ids;
}

function sanitizeTitle($title) {
    $title = preg_replace('/[^a-zA-Z0-9\u4e00-\u9fff]/u', '-', $title);
    $title = preg_replace('/-+/', '-', $title);
    return trim($title, '-');
}

// ========== MISSING FUNCTIONS FROM M6_prediction_functions.php ==========

function getDrawNumber($year, $nos) {
    return $year . '年第' . str_pad($nos, 3, '0', STR_PAD_LEFT) . '期';
}

function getDrawSlug($year, $nos) {
    return $year . '-' . str_pad($nos, 3, '0', STR_PAD_LEFT);
}

// Also update publishAnalysisPost to use draw_year and draw_nos
// Find this section in publishAnalysisPost() and replace:
?>