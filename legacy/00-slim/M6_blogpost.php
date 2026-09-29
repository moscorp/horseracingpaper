<?php
/**
 * Mark Six Prediction Blog Post Generator
 * 
 * Usage:
 * php M6_blogpost.php --action=backtest    # Run backtest and update accuracy
 * php M6_blogpost.php --action=predict     # Generate prediction for next draw
 * php M6_blogpost.php --action=publish     # Generate AND publish to WordPress
 * php M6_blogpost.php --action=full_update # Backtest + Publish + Analysis
 * 
 * Web:
 * https://buycarl.com/00/M6_blogpost.php?action=full_update
 */

include_once ("lib/constants.php");
require_once "M6_prediction_functions.php";

date_default_timezone_set('Asia/Hong_Kong');

if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

$action = $_GET['action'] ?? $argv[1] ?? 'predict';

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '+08:00'");
    
    switch ($action) {
        case 'backtest':
            echo "=== Running Full Backtest ===\n";
            runFullBacktest($pdo);
            break;
            
        case 'predict':
            echo "=== Generating Next Draw Prediction ===\n";
            $result = predictNextDraw($pdo);
            echo "Next Draw ID: " . $result['draw_id'] . "\n";
            echo "Final Ticket: ";
            print_r($result['final_ticket']);
            break;
            
        case 'publish':
            echo "=== Generating and Publishing Blog Post ===\n";
            publishPredictionPost($pdo);
            break;
            
        case 'full_update':
            echo "=== Full Update: Backtest + Publish ===\n";
            
            // Step 0: Run dual simulated backtest
            echo "Step 0: Running accuracy analysis...\n";
            runDualSimulatedBacktest($pdo);
            
            // Step 1: Run optimized backtest
            echo "Step 1: Running backtest...\n";
            runOptimizedBacktest($pdo);
            
            // Step 2: Generate and publish prediction
            echo "Step 2: Generating and publishing prediction...\n";
            
            $stmt = $pdo->query("SELECT * FROM `00m6` ORDER BY year DESC, nos DESC LIMIT 1");
            $lastDraw = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextYear = $lastDraw['year'];
            $nextNos = $lastDraw['nos'] + 1;
            
            // Check if prediction already exists using year+nos
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM m6_blog_posts WHERE draw_year = :year AND draw_nos = :nos AND post_type = 'prediction'");
            $stmt->execute([':year' => $nextYear, ':nos' => $nextNos]);
            $predictionExists = $stmt->fetchColumn();
            
            if (!$predictionExists) {
                publishPredictionPost($pdo);
            } else {
                $drawNumber = getDrawNumber($nextYear, $nextNos);
                echo "⚠️ Prediction for {$drawNumber} already exists. Skipping.\n";
            }
            
            // Step 3: Publish analysis for latest draw
            echo "Step 3: Publishing analysis for latest draw...\n";
            $stmt = $pdo->query("SELECT (id) as latest_id FROM `00m6` ORDER BY year DESC, nos DESC limit 1");
            $latestId = $stmt->fetch(PDO::FETCH_ASSOC)['latest_id'];
            
            $stmt = $pdo->prepare("SELECT * FROM `00m6` WHERE id = :draw_id");
            $stmt->execute([':draw_id' => $latestId]);
            $latestDraw = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM m6_blog_posts WHERE draw_year = :year AND draw_nos = :nos AND post_type = 'analysis'");
            $stmt->execute([':year' => $latestDraw['year'], ':nos' => $latestDraw['nos']]);
            $analysisExists = $stmt->fetchColumn();
            
            if (!$analysisExists) {
                publishAnalysisPost($pdo, $latestId);
            } else {
                $drawNumber = getDrawNumber($latestDraw['year'], $latestDraw['nos']);
                echo "⚠️ Analysis for {$drawNumber} already exists. Skipping.\n";
            }
            
            echo "✅ Full update complete!\n";
            break;
            
        case 'simulate':
            echo "=== Running Simulated Backtest ===\n";
            echo "This will test all methods on ALL historical draws...\n";
            $results = runSimulatedBacktest($pdo);
            
            echo "\n📊 SIMULATED ACCURACY RESULTS\n";
            echo str_repeat("-", 60) . "\n";
            echo sprintf("%-12s %10s %12s %10s\n", "Method", "Correct", "Total", "Accuracy");
            echo str_repeat("-", 60) . "\n";
            foreach ($results as $method => $data) {
                echo sprintf("%-12s %10d %12d %9.1f%%\n", 
                    $method, 
                    $data['total_correct'], 
                    $data['total_predictions'], 
                    $data['overall_accuracy']
                );
            }
            echo str_repeat("-", 60) . "\n";
            break;
        case 'dual_simulate':
            echo "=== Running Dual Simulated Backtest ===\n";
            echo "This will test methods on BOTH current year and all data...\n";
            $results = runDualSimulatedBacktest($pdo);
            break;
        default:
            echo "Usage: action=backtest|predict|publish|full_update\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    error_log("M6 Blogpost Error: " . $e->getMessage());
    exit(1);
}

/**
 * Publish prediction blog post to WordPress (with duplicate check)
 */
function publishPredictionPost($pdo) {
    $prediction = predictNextDraw($pdo);
    
    $stmt = $pdo->query("SELECT * FROM `00m6` ORDER BY year DESC, nos DESC LIMIT 1");
    $lastDraw = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get the NEXT draw number (last draw + 1)
    $nextYear = $lastDraw['year'];
    $nextNos = $lastDraw['nos'] + 1;
    
    // If nos exceeds 999, handle year rollover
    if ($nextNos > 999) {
        $nextNos = 1;
        $nextYear++;
    }
    
    $drawNumber = getDrawNumber($nextYear, $nextNos);
    $drawSlug = getDrawSlug($nextYear, $nextNos);
    $nextDrawDate = getNextDrawDate($lastDraw['memo']);
    
    // Check for duplicate using year+nos
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM m6_blog_posts WHERE draw_year = :year AND draw_nos = :nos AND post_type = 'prediction'");
    $stmt->execute([':year' => $nextYear, ':nos' => $nextNos]);
    $existing = $stmt->fetchColumn();
    
    if ($existing > 0) {
        echo "⚠️ Prediction blog post for {$drawNumber} already exists. Skipping.\n";
        return;
    }
    
    $optimalConfigs = getOptimalConfig($pdo);
    $consensus = getConsensusPrediction($pdo, $prediction['draw_id']);
    
    $title = generateM6Title($drawNumber, $nextDrawDate);
    $content = generateM6Content($pdo, $prediction, $optimalConfigs, $lastDraw, $consensus);
    $excerpt = generateM6Excerpt($prediction['final_ticket'], $consensus);
    $featuredImageId = generateM6FeaturedImage($drawNumber, $nextDrawDate);
    
    // ✅ FIXED: Pass 3 arguments to generateM6SeoData
    $seoData = generateM6SeoData($nextYear, $nextNos, $nextDrawDate);
    
    $postId = postM6ToWordPress($title, $content, $excerpt, $featuredImageId, $seoData);
    
    if ($postId) {
        $stmt = $pdo->prepare("
            INSERT INTO m6_blog_posts (draw_year, draw_nos, post_type, wp_post_id, title, prediction_date)
            VALUES (:year, :nos, 'prediction', :post_id, :title, :date)
        ");
        $stmt->execute([
            ':year' => $nextYear,
            ':nos' => $nextNos,
            ':post_id' => $postId,
            ':title' => $title,
            ':date' => $nextDrawDate
        ]);
        
        updateM6YoastViaInternalScript($postId, $drawNumber, $nextDrawDate, $seoData);
        echo "✅ Blog post published! ID: {$postId}\n";
    } else {
        echo "❌ Failed to publish\n";
    }
}

/**
 * Publish analysis blog post for a specific draw
 */
function publishAnalysisPost($pdo, $drawId) {
    // Check if analysis already exists using draw_id (backward compatible)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM m6_blog_posts WHERE draw_id = :draw_id AND post_type = 'analysis'");
    $stmt->execute([':draw_id' => $drawId]);
    if ($stmt->fetchColumn() > 0) {
        echo "⚠️ Analysis blog post for Draw #{$drawId} already exists. Skipping.\n";
        return;
    }
    
    // Get actual results
    $stmt = $pdo->prepare("SELECT * FROM `00m6` WHERE id = :draw_id");
    $stmt->execute([':draw_id' => $drawId]);
    $actual = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$actual) {
        echo "Draw #{$drawId} not found!\n";
        return;
    }
    
    // Get predictions
    $stmt = $pdo->prepare("SELECT * FROM m6_position_predictions WHERE target_draw_id = :draw_id ORDER BY method");
    $stmt->execute([':draw_id' => $drawId]);
    $predictions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($predictions)) {
        echo "No predictions found for Draw #{$drawId}. Skipping analysis.\n";
        return;
    }
    
    // Calculate accuracy
    $accuracyResults = [];
    $columns = ['no1', 'no2', 'no3', 'no4', 'no5', 'no6', 'no7'];
    
    foreach ($predictions as $pred) {
        $method = $pred['method'];
        $correctCount = 0;
        foreach ($columns as $col) {
            if ($pred['pred_' . $col] == $actual[$col]) $correctCount++;
        }
        $accuracy = round(($correctCount / 7) * 100, 1);
        $accuracyResults[$method] = ['correct_count' => $correctCount, 'accuracy' => $accuracy];
    }
    
    uasort($accuracyResults, function($a, $b) { return $b['accuracy'] <=> $a['accuracy']; });
    
    $consensus = getConsensusPrediction($pdo, $drawId);
    $consensusCorrect = 0;
    foreach ($columns as $col) {
        if ($consensus[$col] == $actual[$col]) $consensusCorrect++;
    }
    
    // Generate content with proper draw number
    $drawYear = $actual['year'];
    $drawNos = $actual['nos'];
    $drawNumber = getDrawNumber($drawYear, $drawNos);
    $formattedDate = formatDrawDate($actual['memo']);
    
    // Get historical summary and next prediction advice
    $historicalSummary = getHistoricalAccuracySummary($pdo, 20);
    $nextPredictionAdvice = getBestMethodForNextDraw($pdo);
    
    // Build the analysis array for generateAnalysisContent
    $analysis = [
        'draw_id' => $drawId,
        'actual' => $actual,
        'accuracy_results' => $accuracyResults,
        'consensus_accuracy' => round(($consensusCorrect / 7) * 100, 1),
        'consensus_correct' => $consensusCorrect,
        'consensus_details' => []
    ];
    
    // Build consensus details
    foreach ($columns as $col) {
        $analysis['consensus_details'][$col] = [
            'predicted' => $consensus[$col],
            'actual' => $actual[$col],
            'correct' => ($consensus[$col] == $actual[$col])
        ];
    }
    
    $title = "📊 六合彩{$drawNumber}賽後分析 | 預測準確度排行榜 | {$formattedDate}";
    
    // ✅ FIXED: Pass 6 arguments to generateAnalysisContent
    $content = generateAnalysisContent($pdo, $analysis, $historicalSummary, $nextPredictionAdvice, $actual, $drawId);
    
    $excerpt = generateAnalysisExcerpt($accuracyResults);
    
    $topAccuracy = reset($accuracyResults)['accuracy'];
    $featuredImageId = generateAnalysisFeaturedImage($drawNumber, $formattedDate, $topAccuracy);
    
    // Post to WordPress
    $postId = postAnalysisToWordPress($title, $content, $excerpt, $featuredImageId, $drawNumber, $formattedDate);
    
    if ($postId) {
        // Record with year and nos
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
        echo "❌ Failed to publish analysis\n";
    }
}

/**
 * Generate SEO data for Mark Six post
 */
// function generateM6SeoData($drawNumber, $drawDate) {
//     return [
//         'focus_keyphrase' => "六合彩第{$drawNumber}期預測",
//         'seo_title' => "六合彩第{$drawNumber}期預測 | 13種方法+共識決 | 每欄獨立分析",
//         'seo_description' => "六合彩第{$drawNumber}期預測分析。使用13種統計方法+共識決，每欄獨立預測。",
//         'slug' => "mark-six-prediction-draw-{$drawNumber}",
//         'draw_number' => $drawNumber,
//         'draw_date' => $drawDate
//     ];
// }
/**
 * Generate SEO data for Mark Six post - OPTIMIZED VERSION
 */
// function generateM6SeoData($drawNumber, $drawDate) {
//     $dateFormatted = date('Y年m月d日', strtotime($drawDate));
    
//     // ✅ Primary focus keyphrase (most important)
//     $focusKeyphrase = "六合彩第{$drawNumber}期預測";
    
//     // ✅ SEO Title (under 60 characters - best for Google)
//     // Format: Primary Keyword | Secondary Keyword | Brand
//     $seoTitle = "六合彩第{$drawNumber}期預測 | 13種方法分析 + 共識決號碼 | BuyCarl";
    
//     // ✅ Meta Description (150-160 characters - best for Google)
//     // Include: Primary keyword, what user gets, call to action
//     $seoDescription = "六合彩第{$drawNumber}期預測分析。使用13種統計方法（加權移動平均、熱門號碼、馬可夫鏈等）獨立預測每個號碼，並提供共識決結果。立即查看最新預測！";
    
//     // ✅ Slug (URL-friendly)
//     $slug = "mark-six-prediction-draw-{$drawNumber}";
    
//     return [
//         'focus_keyphrase' => $focusKeyphrase,
//         'seo_title' => $seoTitle,
//         'seo_description' => $seoDescription,
//         'slug' => $slug,
//         'draw_number' => $drawNumber,
//         'draw_date' => $drawDate
//     ];
// }
function generateM6SeoData($year, $nos, $drawDate) {
    $drawNumber = getDrawNumber($year, $nos);
    $drawSlug = getDrawSlug($year, $nos);
    $dateFormatted = date('Y年m月d日', strtotime($drawDate));
    
    return [
        'focus_keyphrase' => "六合彩{$drawNumber}預測",
        'seo_title' => "六合彩{$drawNumber}預測 | 13種方法+共識決 | 每欄獨立分析",
        'seo_description' => "六合彩{$drawNumber}預測分析。使用13種統計方法+共識決，每欄獨立預測。",
        'slug' => "mark-six-prediction-{$drawSlug}",
        'draw_number' => $drawNumber,
        'draw_year' => $year,
        'draw_nos' => $nos,
        'draw_date' => $drawDate
    ];
}
/**
 * Update Yoast SEO via internal script
 */
function updateM6YoastViaInternalScript($postId, $drawNumber, $drawDate, $seoData) {
    $secret_token = 'your_secret_token_2026';
    $url = "https://buycarl.com/00/M6_blogpost_update_yoast.php?post_id={$postId}&token={$secret_token}&draw_number={$drawNumber}&draw_date={$drawDate}&focus_keyphrase=" . urlencode($seoData['focus_keyphrase']) . "&seo_title=" . urlencode($seoData['seo_title']) . "&seo_description=" . urlencode($seoData['seo_description']);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_exec($ch);
    curl_close($ch);
}

/**
 * Generate blog post title
 */
// function generateM6Title($drawNumber, $drawDate) {
//     $dateFormatted = date('Y年m月d日', strtotime($drawDate));
//     return "🎯 六合彩第{$drawNumber}期預測分析 | {$dateFormatted} 13種方法+共識決";
// }

function generateM6Title($drawNumber, $drawDate) {
    $dateFormatted = date('Y年m月d日', strtotime($drawDate));
    return "🎯 六合彩{$drawNumber}預測分析 | {$dateFormatted} 13種方法+共識決";
}

/**
 * Generate blog post content
 */
function generateM6Content($pdo, $prediction, $optimalConfigs, $lastDraw, $consensus) {
    // $drawDate = date('Y-m-d', strtotime('+2 days'));
    $drawDate = getNextDrawDate($lastDraw['memo']);
    
    // Get accuracy badge
    $accuracyBadge = getAccuracyBadge($pdo);
    
    // Get best method per column from accuracy data
    $bestPerColumn = [];
    $stmt = $pdo->query("
        SELECT 
            'no1' as col, method, no1_accuracy as acc FROM m6_all_data_accuracy
            UNION ALL
            SELECT 'no2', method, no2_accuracy FROM m6_all_data_accuracy
            UNION ALL
            SELECT 'no3', method, no3_accuracy FROM m6_all_data_accuracy
            UNION ALL
            SELECT 'no4', method, no4_accuracy FROM m6_all_data_accuracy
            UNION ALL
            SELECT 'no5', method, no5_accuracy FROM m6_all_data_accuracy
            UNION ALL
            SELECT 'no6', method, no6_accuracy FROM m6_all_data_accuracy
            UNION ALL
            SELECT 'no7', method, no7_accuracy FROM m6_all_data_accuracy
    ");
    $allResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Find best method per column
    foreach (['no1','no2','no3','no4','no5','no6','no7'] as $col) {
        $bestAcc = 0;
        $bestMethod = '';
        foreach ($allResults as $row) {
            if ($row['col'] == $col && $row['acc'] > $bestAcc) {
                $bestAcc = $row['acc'];
                $bestMethod = $row['method'];
            }
        }
        $bestPerColumn[$col] = $bestMethod;
    }
    
    // Get ALL predictions
    $stmt = $pdo->prepare("
        SELECT method, pred_no1, pred_no2, pred_no3, pred_no4, pred_no5, pred_no6, pred_no7
        FROM m6_position_predictions
        WHERE target_draw_id = :draw_id
        ORDER BY FIELD(method, 'WgtMA', 'Mode', 'ExpS', 'AutoCorr', 'Seasonal', 'FreqRec', 'KNN', 'Balance', 'SumTrend', 'Heatmap', 'Gap', 'Markov', 'LinReg')
    ");
    $stmt->execute([':draw_id' => $prediction['draw_id']]);
    $allMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate confidence scores
    $confidence = [];
    $columns = ['no1', 'no2', 'no3', 'no4', 'no5', 'no6', 'no7'];
    foreach ($columns as $col) {
        $values = array_column($allMethods, 'pred_' . $col);
        $freq = array_count_values($values);
        arsort($freq);
        $confidence[$col] = current($freq) ?: 1;
    }
    
    // Emoji mapping
    $emojis = [
        'WgtMA' => '📊', 'Mode' => '🔥', 'ExpS' => '📈', 'AutoCorr' => '🔄',
        'Seasonal' => '📅', 'FreqRec' => '⚡', 'KNN' => '👥', 'Balance' => '⚖️',
        'SumTrend' => '📉', 'Heatmap' => '🗺️', 'Gap' => '⏰', 'Markov' => '🔗', 'LinReg' => '📐'
    ];
    
    $html = '<div style="max-width:100%; overflow-x:auto; font-family: Arial, sans-serif;">';
    
    // Header with accuracy badge
    $html .= '<div style="background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; padding: 30px; text-align: center; border-radius: 15px; margin-bottom: 25px;">';
    
    // Use correct draw number format
    $drawNumber = getDrawNumber($prediction['draw_year'], $prediction['draw_nos']);
    $html .= '<h1 style="color: #FFD700; margin: 0 0 10px;">🎯 六合彩' . $drawNumber . '預測</h1>';
    $html .= '<p style="margin: 0; font-size: 18px;">預計開彩日期: ' . $drawDate . '</p>';

    // Accuracy badge
    $badgeColor = '#666';
    if ($accuracyBadge['confidence'] == '高') $badgeColor = '#4caf50';
    elseif ($accuracyBadge['confidence'] == '中') $badgeColor = '#ff9800';
    else $badgeColor = '#f44336';
    
    $html .= '<div style="margin-top: 15px; padding: 8px 20px; background: rgba(255,255,255,0.1); border-radius: 30px; display: inline-block; border: 1px solid ' . $badgeColor . ';">';
    $html .= '<span style="font-size: 14px; color: #ccc;">' . $accuracyBadge['badge'] . '</span>';
    $html .= '</div>';
    $html .= '</div>';
    
    // ========== LEGEND FOR HIGHLIGHTING ==========
    $html .= '<div style="background: #e8f4fd; padding: 10px 15px; margin-bottom: 15px; border-radius: 8px; font-size: 13px; border-left: 4px solid #2196F3;">';
    $html .= '📌 <strong>圖例：</strong> ';
    $html .= '<span style="background: #c8e6c9; padding: 2px 8px; border-radius: 4px;">綠色背景</span> = 該方法為該位置的歷史最佳方法';
    $html .= ' &nbsp;|&nbsp; ';
    $html .= '<span style="background: #fff3cd; padding: 2px 8px; border-radius: 4px;">黃色背景</span> = 共識決 (多數決)';
    $html .= '</div>';
    // ========== END LEGEND ==========
    
    // Consensus box
    $html .= '<div style="background: #FFF8E1; border: 2px solid #FFC107; border-radius: 15px; padding: 20px; margin-bottom: 25px; text-align: center;">';
    $html .= '<h3 style="margin: 0 0 15px 0; font-size: 18px;">🤝 13種方法共識結果 (多數決)</h3>';
    $html .= '<div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; max-width: 100%; margin: 0 auto;">';
    
    $nums = ['no1', 'no2', 'no3', 'no4', 'no5', 'no6'];
    foreach ($nums as $col) {
        $html .= '<div style="text-align: center; padding: 8px 2px;">';
        $html .= '<div style="font-size: 26px; font-weight: bold; color: #e74c3c; line-height: 1.3;">' . $consensus[$col] . '</div>';
        $html .= '<div style="font-size: 11px; color: #666;">(' . $confidence[$col] . '/13)</div>';
        $html .= '</div>';
    }
    
    $html .= '<div style="text-align: center; padding: 8px 2px; background: #FFF3E0; border-radius: 30px; margin: 0 2px;">';
    $html .= '<div style="font-size: 26px; font-weight: bold; color: #e74c3c; line-height: 1.3;">+' . $consensus['no7'] . '</div>';
    $html .= '<div style="font-size: 11px; color: #666;">(' . $confidence['no7'] . '/13)</div>';
    $html .= '</div>';
    
    $html .= '</div>';
    $html .= '<p style="margin: 15px 0 0 0; font-size: 12px; color: #666;">📌 括號內數字 = 13種方法中有多少種預測此號碼</p>';
    $html .= '</div>';
    
    // Table title
    $html .= '<h3>📊 13種預測方法完整表 (每種方法一行，顯示全部7個號碼)</h3>';
    $html .= '<p style="font-size: 12px; color: #666; margin-bottom: 10px;">📌 表格可左右滑動查看所有號碼</p>';
    
    // THE TABLE - with highlighting for best method per column
    $html .= '<div style="overflow-x: auto; max-width: 100%;">';
    $html .= '<table style="width: 100%; border-collapse: collapse; background: #f8f9fa; border-radius: 12px; overflow: hidden; font-size: 14px; min-width: 700px;">';
    $html .= '<thead>';
    $html .= '<tr style="background: #2c3e50; color: white;">';
    $html .= '<th style="padding: 12px; text-align: left; white-space: nowrap;">預測方法</th>';
    
    // Column headers with best method indicator
    foreach (['no1', 'no2', 'no3', 'no4', 'no5', 'no6', 'no7'] as $col) {
        $bestMethod = $bestPerColumn[$col] ?? '';
        $star = !empty($bestMethod) ? ' ⭐' : '';
        $html .= '<th style="padding: 12px; text-align: center; white-space: nowrap; ' . ($col == 'no7' ? 'background: #f39c12;' : '') . '">' . $col . $star . '</th>';
    }
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    // Loop through each method
    foreach ($allMethods as $method) {
        $name = $method['method'];
        $emoji = $emojis[$name] ?? '📌';
        
        // Check if this method is the best for any column
        $isBestForAny = false;
        foreach (['no1','no2','no3','no4','no5','no6','no7'] as $col) {
            if ($bestPerColumn[$col] == $name) {
                $isBestForAny = true;
                break;
            }
        }
        
        $html .= '<tr style="border-bottom: 1px solid #e0e0e0;' . ($isBestForAny ? ' background: #f0f8ff;' : '') . '">';
        $html .= '<td style="padding: 12px; font-weight: bold; white-space: nowrap;">' . $emoji . ' ' . $name . ($isBestForAny ? ' 🏆' : '') . '</td>';
        
        foreach (['no1', 'no2', 'no3', 'no4', 'no5', 'no6', 'no7'] as $col) {
            $predicted = $method['pred_' . $col];
            $isBest = ($bestPerColumn[$col] == $name);
            
            // Highlight if this method is the best for this column
            $style = 'padding: 12px; text-align: center; font-size: 18px; font-weight: bold; white-space: nowrap;';
            if ($isBest) {
                $style .= ' background: #c8e6c9; border-radius: 8px;'; // Green highlight
            }
            if ($col == 'no7' && !$isBest) {
                $style .= ' background: #fff3e0;';
            }
            
            $html .= '<td style="' . $style . '">' . $predicted . '</td>';
        }
        $html .= '</tr>';
    }
    
    // Consensus row (yellow background)
    $html .= '<tr style="background: #ffeaa7; border-top: 3px solid #f39c12;">';
    $html .= '<td style="padding: 12px; font-weight: bold; white-space: nowrap;">🤝 共識決 (多數決)</td>';
    foreach (['no1', 'no2', 'no3', 'no4', 'no5', 'no6', 'no7'] as $col) {
        $style = 'padding: 12px; text-align: center; white-space: nowrap;';
        if ($col == 'no7') {
            $style .= ' background: #f39c12; color: white;';
        }
        $html .= '<td style="' . $style . '"><span style="font-size: 20px; font-weight: bold; color: ' . ($col == 'no7' ? 'white' : '#e74c3c') . ';">' . $consensus[$col] . '</span><br><span style="font-size: 10px; ' . ($col == 'no7' ? 'color: white;' : 'color: #666;') . '">(' . $confidence[$col] . '/13)</span></td>';
    }
    $html .= '</tr>';
    
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    
    // Method explanations
    $html .= '<div style="background: #e8f4fd; padding: 15px; margin-top: 25px; border-radius: 12px;">';
    $html .= '<h4 style="margin: 0 0 10px;">📖 13種預測方法說明</h4>';
    $html .= '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; font-size: 13px;">';
    $html .= '<div>📊 WgtMA - 加權移動平均</div>';
    $html .= '<div>🔥 Mode - 熱門號碼</div>';
    $html .= '<div>📈 ExpS - 指數平滑</div>';
    $html .= '<div>🔄 AutoCorr - 自相關</div>';
    $html .= '<div>📅 Seasonal - 週期性</div>';
    $html .= '<div>⚡ FreqRec - 頻率+新鮮度</div>';
    $html .= '<div>👥 KNN - 最近鄰居</div>';
    $html .= '<div>⚖️ Balance - 奇偶大小平衡</div>';
    $html .= '<div>📉 SumTrend - 總和趨勢</div>';
    $html .= '<div>🗺️ Heatmap - 區域熱圖</div>';
    $html .= '<div>⏰ Gap - 遺漏值</div>';
    $html .= '<div>🔗 Markov - 馬可夫鏈</div>';
    $html .= '<div>📐 LinReg - 線性迴歸</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Add to generateM6Content() after the header
    // $html .= '<div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 10px; padding: 15px; margin: 20px 0;">';
    // $html .= '<h4 style="margin: 0 0 10px; color: #856404;">📌 關於預測準確度</h4>';
    // $html .= '<ul style="margin: 0; padding-left: 20px; color: #856404;">';
    // $html .= '<li>📊 基於 ' . date('Y') . '年 ' . $currentYearCount . ' 期及過往 ' . $totalDraws . ' 期數據分析</li>';
    // $html .= '<li>🎯 所有方法準確率約 2% (隨機水平)</li>';
    // $html .= '<li>🏆 最佳方法: Gap (' . $bestAccuracy . '%)</li>';
    // $html .= '<li>⚠️ 六合彩為隨機遊戲，預測僅供參考</li>';
    // $html .= '</ul>';
    // $html .= '</div>';

    // Disclaimer
    $html .= '<div style="text-align: center; margin-top: 25px; padding: 15px; font-size: 11px; color: #999; border-top: 1px solid #ddd;">';
    $html .= '🎯 六合彩預測系統 v2.0 | 13種方法 + 共識決 | 每欄獨立回測<br>';
    $html .= '基於歷史數據統計分析 | 僅供參考 | 最後更新: ' . date('Y-m-d H:i:s');
    $html .= '</div>';
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate excerpt
 */
function generateM6Excerpt($finalTicket, $consensus) {
    return "🤝 六合彩最新預測（13方法共識）：{$consensus['no1']}, {$consensus['no2']}, {$consensus['no3']}, {$consensus['no4']}, {$consensus['no5']}, {$consensus['no6']} + {$consensus['no7']}。";
}

/**
 * Get next draw date from memo field
 */
// function getNextDrawDate($memo) {
//     if (preg_match('/\d{2}\/\d{2}>(\d{4})\/(\d{2})\/(\d{2})/', $memo, $matches)) {
//         return "{$matches[1]}-{$matches[2]}-{$matches[3]}";
//     }
//     return date('Y-m-d', strtotime('+3 days'));
// }
function getNextDrawDate($memo) {
    // Pattern: 06/28>2026/06/30>30,824,589...
    if (preg_match('/\d{2}\/\d{2}>(\d{4})\/(\d{2})\/(\d{2})/', $memo, $matches)) {
        // The memo date is the draw date for this draw
        return "{$matches[1]}-{$matches[2]}-{$matches[3]}";
    }
    // Fallback: use today's date
    return date('Y-m-d');
}
/**
 * Format draw date from memo
 */
function formatDrawDate($memo) {
    if (preg_match('/\d{2}\/\d{2}>(\d{4})\/(\d{2})\/(\d{2})/', $memo, $matches)) {
        return "{$matches[1]}年{$matches[2]}月{$matches[3]}日";
    }
    return date('Y年m月d日');
}

/**
 * Get next draw number from year + nos
 */
function getDrawNumber($year, $nos) {
    return $year . '年第' . str_pad($nos, 3, '0', STR_PAD_LEFT) . '期';
}

/**
 * Get slug-safe draw identifier
 */
function getDrawSlug($year, $nos) {
    return $year . '-' . str_pad($nos, 3, '0', STR_PAD_LEFT);
}

/**
 * Generate analysis title
 */
function generateAnalysisTitle($drawNumber, $formattedDate) {
    return "📊 六合彩第{$drawNumber}期賽後分析 | 預測準確度排行榜 | {$formattedDate}";
}

/**
 * Generate analysis excerpt
 */
function generateAnalysisExcerpt($accuracyResults) {
    $topMethod = key($accuracyResults);
    $topAccuracy = $accuracyResults[$topMethod]['accuracy'];
    return "📊 六合彩賽後分析。{$topMethod}以{$topAccuracy}%準確率奪冠！";
}

/**
 * Simple analysis content generator
 */
function generateAnalysisContent($pdo, $analysis, $historicalSummary, $nextPredictionAdvice, $actual, $drawId) {
    // Extract data from analysis array
    $accuracyResults = $analysis['accuracy_results'] ?? [];
    $consensusAccuracy = $analysis['consensus_accuracy'] ?? 0;
    $consensusDetails = $analysis['consensus_details'] ?? [];
    $consensusCorrect = $analysis['consensus_correct'] ?? 0;
    
    $formattedDate = formatDrawDate($actual['memo']);
    $drawNumber = getDrawNumber($actual['year'], $actual['nos']);
    
    $columns = ['no1', 'no2', 'no3', 'no4', 'no5', 'no6', 'no7'];
    
    $emojis = [
        'WgtMA' => '📊', 'Mode' => '🔥', 'ExpS' => '📈', 'AutoCorr' => '🔄',
        'Seasonal' => '📅', 'FreqRec' => '⚡', 'KNN' => '👥', 'Balance' => '⚖️',
        'SumTrend' => '📉', 'Heatmap' => '🗺️', 'Gap' => '⏰', 'Markov' => '🔗', 'LinReg' => '📐'
    ];
    
    $html = '<div style="max-width:100%; overflow-x:auto; font-family: Arial, sans-serif;">';
    
    // Header
    $html .= '<div style="background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; padding: 30px; text-align: center; border-radius: 15px; margin-bottom: 25px;">';
    $html .= '<h1 style="color: #FFD700; margin: 0 0 10px;">📊 六合彩' . $drawNumber . '賽後分析</h1>';
    $html .= '<p style="margin: 0; font-size: 18px;">開彩日期: ' . $formattedDate . '</p>';
    $html .= '</div>';
    
    // Actual results
    $html .= '<div style="background: #e8f5e9; border: 2px solid #4caf50; border-radius: 15px; padding: 20px; margin-bottom: 25px; text-align: center;">';
    $html .= '<h3 style="margin: 0 0 15px 0;">🎯 實際開獎號碼</h3>';
    $html .= '<div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; align-items: center;">';
    
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
    
    // ========== TOP METHODS SECTION (FIXED) ==========
    $topAccuracy = 0;
    $topMethods = [];
    if (!empty($accuracyResults)) {
        foreach ($accuracyResults as $data) {
            if ($data['accuracy'] > $topAccuracy) {
                $topAccuracy = $data['accuracy'];
            }
        }
        
        foreach ($accuracyResults as $method => $data) {
            if ($data['accuracy'] == $topAccuracy) {
                $topMethods[] = [
                    'method' => $method,
                    'correct_count' => $data['correct_count'],
                    'accuracy' => $data['accuracy']
                ];
            }
        }
    }
    
    $html .= '<div style="background: #FFF8E1; border: 2px solid #FFC107; border-radius: 15px; padding: 20px; margin-bottom: 25px;">';
    $html .= '<h3 style="margin: 0 0 15px;">🏆 本期最佳預測方法</h3>';
    
    // Check if all methods have 0% accuracy
    if ($topAccuracy == 0) {
        $html .= '<div style="font-size: 24px; text-align: center; color: #e74c3c; padding: 20px 0;">';
        $html .= '😅 本期無方法預測正確';
        $html .= '</div>';
        $html .= '<div style="font-size: 16px; text-align: center; color: #666;">所有方法均未能命中任何號碼，下期加油！</div>';
    } else if (count($topMethods) == 1) {
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
    // ========== END TOP METHODS SECTION ==========
    
    // Detailed comparison table
    $html .= '<h3>📋 預測 vs 實際對比表</h3>';
    $html .= '<div style="overflow-x: auto; max-width: 100%;">';
    $html .= '<table style="width: 100%; border-collapse: collapse; background: #f8f9fa; border-radius: 12px; overflow: hidden; font-size: 13px; min-width: 700px;">';
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
    if (!empty($consensusDetails)) {
        $html .= '<tr style="background: #ffeaa7; border-top: 2px solid #f39c12;">';
        $html .= '<td style="padding: 10px; font-weight: bold; white-space: nowrap;">🤝 共識決 (多數決)</td>';
        
        foreach ($columns as $col) {
            $predicted = $consensusDetails[$col]['predicted'] ?? '-';
            $isCorrect = $consensusDetails[$col]['correct'] ?? false;
            
            if ($isCorrect) {
                $numColor = getNumberColor($predicted);
                $html .= '<td style="padding: 10px; text-align: center; white-space: nowrap;">';
                $html .= '<span style="display: inline-block; background: ' . $numColor['bg'] . '; color: white; width: 32px; height: 32px; line-height: 32px; border-radius: 50%; font-weight: bold; font-size: 14px;">' . $predicted . '</span>';
                $html .= '</td>';
            } else {
                $html .= '<td style="padding: 10px; text-align: center; white-space: nowrap;">' . $predicted . '</td>';
            }
        }
        
        $html .= '<td style="padding: 10px; text-align: center; font-weight: bold; white-space: nowrap;">' . ($consensusCorrect ?? 0) . '/7</td>';
        $html .= '<td style="padding: 10px; text-align: center; font-weight: bold; white-space: nowrap;">' . ($consensusAccuracy ?? 0) . '%</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    
    // Historical Accuracy
    $html .= '<h3 style="margin-top: 30px;">📈 歷史準確度排行榜</h3>';
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
        if (isset($method['trend'])) {
            if ($method['trend'] > 5) $trendIcon = '📈 上升';
            elseif ($method['trend'] < -5) $trendIcon = '📉 下降';
            else $trendIcon = '➡️ 平穩';
        } else {
            $trendIcon = '➡️ 平穩';
        }
        
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
    
    // Next prediction advice
    $bestOverall = $nextPredictionAdvice['overall_best'] ?? null;
    $bestPerColumn = $nextPredictionAdvice['per_column_best'] ?? [];
    
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

/**
 * Get number color for HKJC scheme
 */
function getNumberColor($num) {
    if ($num >= 1 && $num <= 9) return ['bg' => '#e74c3c'];
    if ($num >= 10 && $num <= 19) return ['bg' => '#3498db'];
    if ($num >= 20 && $num <= 29) return ['bg' => '#2ecc71'];
    if ($num >= 30 && $num <= 39) return ['bg' => '#f39c12'];
    if ($num >= 40 && $num <= 49) return ['bg' => '#9b59b6'];
    return ['bg' => '#95a5a6'];
}

/**
 * Generate featured image for prediction
 */
// function generateM6FeaturedImage($drawNumber, $drawDate) {
//     $width = 1200; $height = 630;
//     $image = imagecreatetruecolor($width, $height);
//     imageantialias($image, true);
    
//     for ($i = 0; $i < $height; $i++) {
//         $ratio = $i / $height;
//         $r = 200 + (255 - 200) * (1 - $ratio);
//         $g = 50 + (200 - 50) * (1 - abs($ratio - 0.5) * 2);
//         $b = 50 + (200 - 50) * $ratio;
//         imagecolorallocate($image, (int)$r, (int)$g, (int)$b);
//         imageline($image, 0, $i, $width, $i, imagecolorallocate($image, (int)$r, (int)$g, (int)$b));
//     }
    
//     $gold = imagecolorallocate($image, 255, 215, 0);
//     $white = imagecolorallocate($image, 255, 255, 255);
//     $dark = imagecolorallocate($image, 30, 30, 30);
//     $font = findChineseFont();
    
//     if ($font) {
//         imagettftext($image, 70, 0, 80, 120, $gold, $font, "六合彩預測");
//         imagettftext($image, 65, 0, 80, 210, $dark, $font, "第{$drawNumber}");
//         imagettftext($image, 45, 0, 80, 300, $dark, $font, $drawDate);
//         imagettftext($image, 32, 0, 80, 390, $gold, $font, "13種方法 + 共識決");
//         imagettftext($image, 28, 0, 80, 480, $dark, $font, "每欄獨立預測 | 基於歷史數據分析");
//     } else {
//         imagestring($image, 5, 80, 100, "MARK SIX PREDICTION", $gold);
//         imagestring($image, 5, 80, 160, "Draw #" . $drawNumber, $white);
//         imagestring($image, 4, 80, 220, $drawDate, $white);
//     }
    
//     imagerectangle($image, 5, 5, $width-6, $height-6, $gold);
//     $fileName = "m6-prediction-{$drawNumber}.png";
//     $tempFile = tempnam(sys_get_temp_dir(), 'm6_') . '.png';
//     imagepng($image, $tempFile);
//     imagedestroy($image);
    
//     return uploadToWordPress($tempFile, $fileName);
// }

function generateM6FeaturedImage($drawNumber, $drawDate) {
    $width = 1200; $height = 630;
    $image = imagecreatetruecolor($width, $height);
    imageantialias($image, true);
    
    // Gradient background
    for ($i = 0; $i < $height; $i++) {
        $ratio = $i / $height;
        $r = 200 + (255 - 200) * (1 - $ratio);
        $g = 50 + (200 - 50) * (1 - abs($ratio - 0.5) * 2);
        $b = 50 + (200 - 50) * $ratio;
        $color = imagecolorallocate($image, (int)$r, (int)$g, (int)$b);
        imageline($image, 0, $i, $width, $i, $color);
    }
    
    // Colors
    $gold = imagecolorallocate($image, 255, 215, 0);
    $white = imagecolorallocate($image, 255, 255, 255);
    $dark = imagecolorallocate($image, 30, 30, 30);
    $lightGray = imagecolorallocate($image, 180, 180, 180);
    $font = findChineseFont();
    
    if ($font) {
        // Title
        imagettftext($image, 70, 0, 80, 120, $gold, $font, "六合彩預測");
        // Draw number
        imagettftext($image, 65, 0, 80, 210, $dark, $font, "第{$drawNumber}");
        // Date
        imagettftext($image, 45, 0, 80, 300, $dark, $font, $drawDate);
        // Subtitle
        imagettftext($image, 32, 0, 80, 390, $gold, $font, "13種方法 + 共識決");
        // Description
        imagettftext($image, 28, 0, 80, 480, $dark, $font, "每欄獨立預測 | 基於歷史數據分析");
        // ✅ FOOTER
        $currentYear = date('Y');
        imagettftext($image, 20, 0, 80, 570, $lightGray, $font, "© {$currentYear} BuyCarl.com | 僅供參考 | 理性投注");
    } else {
        // Fallback without font
        imagestring($image, 5, 80, 100, "MARK SIX PREDICTION", $gold);
        imagestring($image, 5, 80, 160, "Draw #" . $drawNumber, $white);
        imagestring($image, 4, 80, 220, $drawDate, $white);
        imagestring($image, 4, 80, 280, "13 Methods + Consensus", $gold);
        imagestring($image, 3, 80, 340, "Per-Column Independent Prediction", $white);
        // ✅ FOOTER (fallback)
        imagestring($image, 2, 80, 560, "© BuyCarl.com | For reference only", $lightGray);
    }
    
    // Border
    imagerectangle($image, 5, 5, $width-6, $height-6, $gold);
    imagerectangle($image, 8, 8, $width-9, $height-9, $white);
    
    // Decorative circles
    $decoColor = imagecolorallocate($image, 255, 215, 0);
    for ($i = 0; $i < 3; $i++) {
        imagefilledellipse($image, 1080 + ($i * 30), 80, 15, 15, $decoColor);
    }
    
    $fileName = "m6-prediction-{$drawNumber}.png";
    $tempFile = tempnam(sys_get_temp_dir(), 'm6_') . '.png';
    imagepng($image, $tempFile);
    imagedestroy($image);
    
    return uploadToWordPress($tempFile, $fileName);
}

/**
 * Generate analysis featured image
 */
// function generateAnalysisFeaturedImage($drawNumber, $formattedDate, $topAccuracy) {
//     $width = 1200; $height = 630;
//     $image = imagecreatetruecolor($width, $height);
//     imageantialias($image, true);
    
//     for ($i = 0; $i < $height; $i++) {
//         $ratio = $i / $height;
//         $r = 200 + (255 - 200) * (1 - $ratio);
//         $g = 50 + (200 - 50) * (1 - abs($ratio - 0.5) * 2);
//         $b = 50 + (200 - 50) * $ratio;
//         imageline($image, 0, $i, $width, $i, imagecolorallocate($image, (int)$r, (int)$g, (int)$b));
//     }
    
//     $gold = imagecolorallocate($image, 255, 215, 0);
//     $dark = imagecolorallocate($image, 30, 30, 30);
//     $font = findChineseFont();
    
//     if ($font) {
//         imagettftext($image, 60, 0, 80, 120, $gold, $font, "六合彩賽後分析");
//         imagettftext($image, 55, 0, 80, 200, $dark, $font, "第{$drawNumber}");
//         imagettftext($image, 35, 0, 80, 280, $dark, $font, "最佳準確率: {$topAccuracy}%");
//         imagettftext($image, 28, 0, 80, 370, $gold, $font, "預測方法排行榜");
//     } else {
//         imagestring($image, 5, 80, 100, "MARK SIX ANALYSIS", $gold);
//         imagestring($image, 5, 80, 160, "Draw #" . $drawNumber, $dark);
//         imagestring($image, 4, 80, 220, "Best Accuracy: {$topAccuracy}%", $dark);
//     }
    
//     imagerectangle($image, 5, 5, $width-6, $height-6, $gold);
//     $fileName = "m6-analysis-{$drawNumber}.png";
//     $tempFile = tempnam(sys_get_temp_dir(), 'm6a_') . '.png';
//     imagepng($image, $tempFile);
//     imagedestroy($image);
    
//     return uploadToWordPress($tempFile, $fileName);
// }

function generateAnalysisFeaturedImage($drawNumber, $formattedDate, $topAccuracy) {
    $width = 1200; $height = 630;
    $image = imagecreatetruecolor($width, $height);
    imageantialias($image, true);
    
    // Gradient background
    for ($i = 0; $i < $height; $i++) {
        $ratio = $i / $height;
        $r = 200 + (255 - 200) * (1 - $ratio);
        $g = 50 + (200 - 50) * (1 - abs($ratio - 0.5) * 2);
        $b = 50 + (200 - 50) * $ratio;
        $color = imagecolorallocate($image, (int)$r, (int)$g, (int)$b);
        imageline($image, 0, $i, $width, $i, $color);
    }
    
    // Colors
    $gold = imagecolorallocate($image, 255, 215, 0);
    $white = imagecolorallocate($image, 255, 255, 255);
    $dark = imagecolorallocate($image, 30, 30, 30);
    $lightGray = imagecolorallocate($image, 180, 180, 180);
    $font = findChineseFont();
    
    if ($font) {
        // Title
        imagettftext($image, 60, 0, 80, 120, $gold, $font, "六合彩賽後分析");
        // Draw number
        imagettftext($image, 55, 0, 80, 200, $dark, $font, "第{$drawNumber}");
        // Date
        imagettftext($image, 35, 0, 80, 280, $dark, $font, $formattedDate);
        // Best accuracy
        imagettftext($image, 35, 0, 80, 350, $dark, $font, "最佳準確率: {$topAccuracy}%");
        // Subtitle
        imagettftext($image, 28, 0, 80, 430, $gold, $font, "預測方法排行榜");
        // ✅ FOOTER
        $currentYear = date('Y');
        imagettftext($image, 20, 0, 80, 570, $lightGray, $font, "© {$currentYear} BuyCarl.com | 數據分析 | 僅供參考");
    } else {
        // Fallback without font
        imagestring($image, 5, 80, 100, "MARK SIX ANALYSIS", $gold);
        imagestring($image, 5, 80, 160, "Draw #" . $drawNumber, $dark);
        imagestring($image, 4, 80, 220, $formattedDate, $dark);
        imagestring($image, 4, 80, 280, "Best Accuracy: {$topAccuracy}%", $dark);
        imagestring($image, 4, 80, 340, "Method Rankings", $gold);
        // ✅ FOOTER (fallback)
        imagestring($image, 2, 80, 560, "© BuyCarl.com | Data Analysis | For reference only", $lightGray);
    }
    
    // Border
    imagerectangle($image, 5, 5, $width-6, $height-6, $gold);
    imagerectangle($image, 8, 8, $width-9, $height-9, $white);
    
    // Decorative circles
    $decoColor = imagecolorallocate($image, 255, 215, 0);
    for ($i = 0; $i < 3; $i++) {
        imagefilledellipse($image, 1080 + ($i * 30), 80, 15, 15, $decoColor);
    }
    
    $fileName = "m6-analysis-{$drawNumber}.png";
    $tempFile = tempnam(sys_get_temp_dir(), 'm6a_') . '.png';
    imagepng($image, $tempFile);
    imagedestroy($image);
    
    return uploadToWordPress($tempFile, $fileName);
}

/**
 * Upload to WordPress
 */
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
    
    $existing = json_decode($response, true);
    if (!empty($existing)) {
        foreach ($existing as $media) {
            if (basename($media['source_url']) === $fileName) return $media['id'];
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

/**
 * Post to WordPress with SEO
 */
function postM6ToWordPress($title, $content, $excerpt, $featuredImageId = null, $seoData = null) {
    $username = getWpUsername();
    $password = getWpAppPassword();
    $categoryId = getOrCreateCategory('Mark Six Prediction');
    $tagIds = getOrCreateTags(['六合彩', 'Mark Six', '彩票預測', '共識決']);
    
    $postData = [
        'title' => $title,
        'content' => $content,
        'excerpt' => $excerpt,
        'status' => 'publish',
        'categories' => [$categoryId],
        'tags' => $tagIds,
        'slug' => $seoData['slug'] ?? sanitizeTitle($title)
    ];
    if ($featuredImageId) $postData['featured_media'] = (int)$featuredImageId;
    
    $ch = curl_init('https://buycarl.com/wp-json/wp/v2/posts');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($username . ':' . $password)
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    return $result['id'] ?? false;
}

/**
 * Post analysis to WordPress
 */
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
    if ($featuredImageId) $postData['featured_media'] = (int)$featuredImageId;
    
    $ch = curl_init('https://buycarl.com/wp-json/wp/v2/posts');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($username . ':' . $password)
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    if (isset($result['id'])) {
        updateAnalysisYoastViaInternalScript($result['id'], $drawNumber, $formattedDate);
        return $result['id'];
    }
    return false;
}

/**
 * Update Yoast for analysis post
 */
function updateAnalysisYoastViaInternalScript($postId, $drawNumber, $formattedDate) {
    $secret_token = 'your_secret_token_2026';
    $focusKeyphrase = "六合彩第{$drawNumber}期賽後分析";
    $seoTitle = "六合彩第{$drawNumber}期賽後分析 | 預測準確度排行榜";
    $seoDescription = "六合彩第{$drawNumber}期賽後分析。比較13種預測方法的準確度。";
    $url = "https://buycarl.com/00/M6_blogpost_update_yoast.php?post_id={$postId}&token={$secret_token}&draw_number={$drawNumber}&draw_date=" . urlencode($formattedDate) . "&focus_keyphrase=" . urlencode($focusKeyphrase) . "&seo_title=" . urlencode($seoTitle) . "&seo_description=" . urlencode($seoDescription);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_exec($ch);
    curl_close($ch);
}

/**
 * Find Chinese font
 */
function findChineseFont() {
    $paths = [
        __DIR__ . '/fonts/NotoSansTC-VariableFont_wght.ttf',
        __DIR__ . '/fonts/NotoSansCJKjp-Regular.otf',
        __DIR__ . '/fonts/wqy-microhei.ttc',
    ];
    foreach ($paths as $p) {
        if (file_exists($p) && filesize($p) > 1000000) return $p;
    }
    return null;
}

/**
 * WordPress helper functions
 */
function getWpUsername() { global $wpConfig; return $wpConfig['username'] ?? 'your_username'; }
function getWpAppPassword() { global $wpConfig; return $wpConfig['app_password'] ?? 'your_app_password'; }
function sanitizeTitle($title) { $title = preg_replace('/[^a-zA-Z0-9\u4e00-\u9fff]/u', '-', $title); return trim(preg_replace('/-+/', '-', $title), '-'); }

function getOrCreateCategory($name) {
    $username = getWpUsername(); $password = getWpAppPassword();
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
    $username = getWpUsername(); $password = getWpAppPassword();
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

function getOrCreateTags($names) { $ids = []; foreach ($names as $n) { $id = getOrCreateTag($n); if ($id) $ids[] = $id; } return $ids; }


/**
 * Get accuracy badge for prediction blog
 * Returns formatted accuracy info WITHOUT showing numbers
 */
/**
 * Get accuracy badge for prediction blog
 * Returns formatted accuracy info WITHOUT showing numbers
 */
function getAccuracyBadge($pdo) {
    // Get best method from all_data_accuracy
    $stmt = $pdo->query("
        SELECT method, accuracy 
        FROM m6_all_data_accuracy 
        ORDER BY accuracy DESC 
        LIMIT 1
    ");
    $bestAll = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get best method from current_year_accuracy
    $stmt = $pdo->query("
        SELECT method, accuracy 
        FROM m6_current_year_accuracy 
        ORDER BY accuracy DESC 
        LIMIT 1
    ");
    $bestCurrent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get average accuracy across all methods
    $stmt = $pdo->query("
        SELECT AVG(accuracy) as avg_accuracy 
        FROM m6_all_data_accuracy
    ");
    $avgResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $avgAccuracy = round($avgResult['avg_accuracy'] ?? 0, 1);
    
    // Build badge
    $badge = "📊 基於歷史數據分析";
    
    if ($bestAll) {
        $badge .= " | 最佳: {$bestAll['method']}";
    }
    
    if ($bestCurrent) {
        $badge .= " | 近期最佳: {$bestCurrent['method']}";
    }
    
    // Confidence level based on average accuracy
    $confidence = '低';
    if ($avgAccuracy > 2.3) $confidence = '中';
    if ($avgAccuracy > 2.5) $confidence = '高';
    $badge .= " | 信心度: {$confidence}";
    
    return [
        'badge' => $badge,
        'best_method' => $bestAll['method'] ?? '分析中',
        'confidence' => $confidence,
        'current_best' => $bestCurrent['method'] ?? '分析中',
        'all_best' => $bestAll['method'] ?? '分析中',
        'avg_accuracy' => $avgAccuracy
    ];
}
?>