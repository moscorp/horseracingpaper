<?php
/**
 * HKJC 賽馬分析博文自動發布腳本
 * 
 * 功能：從 priority 結果中選取每場賽事的第1名，發布到 WordPress
 * 
 * 執行方式：
 * php HKJC_blogpost.php --type=batch                    # 處理今天及未來7天的賽事
 * php HKJC_blogpost.php --type=batch --date=2026-05-04  # 處理指定日期
 * php HKJC_blogpost.php --type=batch --force            # 強制重新發布
 * 
 * Web 執行：
 * https://buycarl.com/00/HKJC_blogpost.php?type=batch
 */

include_once ("lib/constants.php");
require_once "HKJCRacing_score_functions.php";

date_default_timezone_set('Asia/Hong_Kong');

// 允許 Web 執行
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

$options = getopt("", ["type::", "date::", "venue::", "force"]);
$type = $options['type'] ?? 'batch';
$date = $options['date'] ?? null;
$venue = $options['venue'] ?? '';
$force = isset($options['force']) ? true : false;

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '+08:00'");
    
    switch ($type) {
        case 'batch':
        default:
            processBatchBlogPost($pdo, $date, $venue, $force);
            break;
    }
    
    echo "\n[" . date('Y-m-d H:i:s') . "] 博文發布完成\n";
    
} catch (Exception $e) {
    echo "錯誤: " . $e->getMessage() . "\n";
    error_log("HKJC Blogpost Error: " . $e->getMessage());
    exit(1);
}

/**
 * 批次處理博文發布
 */
function processBatchBlogPost($pdo, $date, $venue, $force = false) {
    echo "=== 批次發布賽馬分析博文 ===\n";
    echo "日期: " . ($date ?: '今天及未來7天') . "\n";
    echo "馬場: " . ($venue ?: '全部') . "\n";
    echo "強制發布: " . ($force ? '是' : '否') . "\n\n";
    
    // 分別處理 version 1 和 version 2
    $versions = [1, 2];
    
    foreach ($versions as $version) {
        echo "\n--- 處理 Version {$version} (" . ($version == 2 ? "含賠率" : "僅歷史數據") . ") ---\n";
        
        $sql = "
            SELECT DISTINCT 
                r.id as race_id,
                r.race_no,
                m.date,
                m.venue_code,
                COUNT(DISTINCT ru.horse_code) as horse_count,
                " . ($version == 2 ? "1" : "0") . " as has_odds
            FROM hkracing_races r
            JOIN hkracing_meetings m ON r.meeting_id = m.id
            JOIN hkracing_runners ru ON ru.race_id = r.id
            JOIN hkracing_v2_score s ON s.race_id = r.id AND s.score_version = {$version}
            WHERE m.venue_code IN ('ST', 'HV')
        ";
        
        if ($date) {
            $sql .= " AND m.date = :date";
        } else {
            $sql .= " AND m.date >= CURDATE() AND m.date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
        }
        
        if ($venue) {
            $sql .= " AND m.venue_code = :venue";
        }
        
        // 檢查是否已發布過該版本
        if (!$force) {
            $sql .= " AND NOT EXISTS (
                SELECT 1 FROM hkracing_blog_posts bp 
                WHERE bp.race_date = m.date 
                  AND bp.venue_code = m.venue_code
                  AND bp.score_version = {$version}
            )";
        }
        
        $sql .= " GROUP BY r.id, m.date, m.venue_code, r.race_no
                  ORDER BY m.date, r.race_no";
        
        $stmt = $pdo->prepare($sql);
        $params = [];
        if ($date) $params[':date'] = $date;
        if ($venue) $params[':venue'] = $venue;
        $stmt->execute($params);
        $races = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($races)) {
            echo "沒有找到需要發布的賽事\n";
            continue;
        }
        
        // 按日期和馬場分組
        $groupedRaces = [];
        foreach ($races as $race) {
            $key = $race['date'] . '_' . $race['venue_code'];
            if (!isset($groupedRaces[$key])) {
                $groupedRaces[$key] = [
                    'date' => $race['date'],
                    'venue_code' => $race['venue_code'],
                    'races' => [],
                    'has_odds' => ($version == 2),
                    'score_version' => $version
                ];
            }
            $groupedRaces[$key]['races'][] = $race;
        }
        
        foreach ($groupedRaces as $group) {
            echo "\n--- 處理: {$group['date']} - {$group['venue_code']} (Version {$version}) ---\n";
            
            // $postId = publishRaceAnalysisPost($pdo, $group['date'], $group['venue_code'], $group['races'], $group['has_odds'], $version);
            $postId = publishRaceAnalysisPost($pdo, $group['date'], $group['venue_code'], $group['races'], $group['has_odds'], $group['score_version']);
            
            if ($postId) {
                echo "✅ 博文已發布，ID: {$postId}\n";
                recordBlogPost($pdo, $group['date'], $group['venue_code'], $postId, $version);
            } else {
                echo "❌ 發布失敗\n";
            }
            
            sleep(2);
        }
    }
    
    echo "\n完成\n";
}
/**
 * 發布單篇賽馬分析博文
 */
function publishRaceAnalysisPost($pdo, $date, $venueCode, $races, $hasOdds, $scoreVersion) {
    $topPicks = [];
    
    foreach ($races as $race) {
        $topPick = getTopPriorityHorse($pdo, $race['race_id'], $race['race_no'], $scoreVersion);
        if ($topPick) {
            $topPick['race_no'] = $race['race_no'];
            $topPicks[] = $topPick;
        }
    }
    
    if (empty($topPicks)) {
        echo "沒有找到 priority 數據\n";
        return false;
    }
    
    // 生成特色图片（可選：版本2使用不同圖片）
    echo "生成特色图片... ";
    $featuredImageId = generateAndUploadFeaturedImage($date, $venueCode, $pdo, $scoreVersion);
    echo $featuredImageId ? "成功 (ID: {$featuredImageId})\n" : "失敗\n";
    
    // 生成標題和內容（區分版本）
    $title = generatePostTitle($date, $venueCode, count($topPicks), $hasOdds, $scoreVersion);
    $content = generatePostContent($date, $venueCode, $topPicks, $hasOdds, $scoreVersion);
    $excerpt = generatePostExcerpt($topPicks);
    
    return postToWordPress($title, $content, $excerpt, $date, $venueCode, $featuredImageId, $topPicks, $scoreVersion);
}

/**
 * 獲取每場賽事的 priority 第1名馬匹
 */
function getTopPriorityHorse($pdo, $raceId, $raceNo, $scoreVersion) {
    echo ">>> getTopPriorityHorse called with version: {$scoreVersion} <<<\n";
    
    if ($scoreVersion == 2) {
        // 先检查 priority 是否存在
        $checkStmt = $pdo->prepare("
            SELECT COUNT(*) FROM hkracing_v2_score
            WHERE race_id = ? AND score_version = 2 AND priority IS NOT NULL
        ");
        $checkStmt->execute([$raceId]);
        $hasPriority = $checkStmt->fetchColumn() > 0;
        
        if (!$hasPriority) {
            echo ">>> Warning: priority not calculated yet for race_id {$raceId}, falling back to total_score <<<\n";
            // 降级使用 total_score
            $stmt = $pdo->prepare("
                SELECT 
                    s.runner_no,
                    s.total_score as v2_score,
                    s.win_odds_snapshot as current_odds,
                    s.score_version,
                    ru.barrier_draw_number as draw,
                    ru.name_ch as horse_name,
                    ru.jockey_name_ch as jockey_name,
                    ru.trainer_name_ch as trainer_name
                FROM hkracing_v2_score s
                JOIN hkracing_runners ru ON ru.race_id = s.race_id AND ru.runner_no = s.runner_no
                WHERE s.race_id = ? 
                  AND s.score_version = 2
                ORDER BY s.total_score DESC
                LIMIT 1
            ");
            $stmt->execute([$raceId]);
        } else {
            // 正常使用 priority
            echo ">>> Version 2: Using priority = 1 <<<\n";
            $stmt = $pdo->prepare("
                SELECT 
                    s.runner_no,
                    s.total_score as v2_score,
                    s.win_odds_snapshot as current_odds,
                    s.score_version,
                    ru.barrier_draw_number as draw,
                    ru.name_ch as horse_name,
                    ru.jockey_name_ch as jockey_name,
                    ru.trainer_name_ch as trainer_name
                FROM hkracing_v2_score s
                JOIN hkracing_runners ru ON ru.race_id = s.race_id AND ru.runner_no = s.runner_no
                WHERE s.race_id = ? 
                  AND s.score_version = 2
                  AND s.priority = 1
                LIMIT 1
            ");
            $stmt->execute([$raceId]);
        }
    } else {
        // Version 1: 使用 total_score 排序
        echo ">>> Version 1: Using total_score DESC <<<\n";
        $stmt = $pdo->prepare("
            SELECT 
                s.runner_no,
                s.total_score as v2_score,
                s.win_odds_snapshot as current_odds,
                s.score_version,
                ru.barrier_draw_number as draw,
                ru.name_ch as horse_name,
                ru.jockey_name_ch as jockey_name,
                ru.trainer_name_ch as trainer_name
            FROM hkracing_v2_score s
            JOIN hkracing_runners ru ON ru.race_id = s.race_id AND ru.runner_no = s.runner_no
            WHERE s.race_id = ? 
              AND s.score_version = 1
            ORDER BY s.total_score DESC
            LIMIT 1
        ");
        $stmt->execute([$raceId]);
    }
    
    $horse = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$horse) {
        echo ">>> No horse found! <<<\n";
        return null;
    }
    
    echo ">>> Selected runner_no: {$horse['runner_no']} <<<\n";
    
    // 獲取詳細數據
    $horseCode = getHorseCodeFromRunner($pdo, $raceId, $horse['runner_no']);
    
    // 計算詳細分數
    $details = getHorsePriorityDetails($pdo, $horseCode, $horse['draw'], $horse['current_odds'], $horse['v2_score']);
    
    return [
        'race_no' => $raceNo,
        'runner_no' => $horse['runner_no'],
        'horse_name' => $horse['horse_name'],
        'draw' => $horse['draw'],
        'current_odds' => $horse['current_odds'],
        'v2_score' => round($horse['v2_score'], 1),
        'priority_score' => $details['priority_score'],
        'history_score' => $details['history_score'],
        'distance_win_rate' => $details['distance_win_rate'],
        'draw_top3_rate' => $details['draw_top3_rate'],
        'recent_form' => $details['recent_form'],
        'suggestion' => $details['suggestion'],
        'score_version' => $scoreVersion
    ];
}
/**
 * 獲取馬匹代碼
 */
function getHorseCodeFromRunner($pdo, $raceId, $runnerNo) {
    $stmt = $pdo->prepare("
        SELECT horse_code 
        FROM hkracing_runners 
        WHERE race_id = ? AND runner_no = ?
    ");
    $stmt->execute([$raceId, $runnerNo]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['horse_code'] : '';
}

/**
 * 獲取馬匹優先級詳細數據
 */
// function getHorsePriorityDetails($pdo, $horseCode, $draw, $currentOdds, $v2Score) {
//     // 獲取同程勝率
//     $distanceWinRate = 0;
//     $stmt = $pdo->prepare("
//         SELECT 
//             COUNT(*) as total,
//             SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins
//         FROM hkracing_horse_performances
//         WHERE horse_code = ? AND finishing_position IS NOT NULL AND finishing_position > 0
//     ");
//     $stmt->execute([$horseCode]);
//     $stats = $stmt->fetch(PDO::FETCH_ASSOC);
//     $total = intval($stats['total'] ?? 0);
//     $wins = intval($stats['wins'] ?? 0);
//     $winRate = $total > 0 ? round($wins / $total * 100, 1) : 0;
    
//     // 獲取同檔上名率
//     $drawTop3Rate = 0;
//     if ($draw > 0) {
//         $stmt = $pdo->prepare("
//             SELECT 
//                 COUNT(*) as total,
//                 SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) as top3
//             FROM hkracing_horse_performances
//             WHERE horse_code = ? AND barrier_draw = ?
//                 AND finishing_position IS NOT NULL AND finishing_position > 0
//         ");
//         $stmt->execute([$horseCode, $draw]);
//         $drawStats = $stmt->fetch(PDO::FETCH_ASSOC);
//         $drawTotal = intval($drawStats['total'] ?? 0);
//         $drawTop3 = intval($drawStats['top3'] ?? 0);
//         $drawTop3Rate = $drawTotal > 0 ? round($drawTop3 / $drawTotal * 100, 1) : 0;
//     }
    
//     // 獲取近況
//     $stmt = $pdo->prepare("
//         SELECT finishing_position
//         FROM hkracing_horse_performances
//         WHERE horse_code = ? AND finishing_position IS NOT NULL AND finishing_position > 0
//         ORDER BY race_date DESC
//         LIMIT 5
//     ");
//     $stmt->execute([$horseCode]);
//     $recentRuns = $stmt->fetchAll(PDO::FETCH_ASSOC);
//     $recentCount = count($recentRuns);
    
//     // 簡單計算優先分數
//     $historyScore = min(100, $winRate + $drawTop3Rate / 2);
//     $priorityScore = round(($historyScore * 0.4) + ($v2Score * 0.6), 1);
    
//     // 生成建議
//     $suggestion = generateSimpleSuggestion($priorityScore, $currentOdds, $winRate, $drawTop3Rate);
    
//     return [
//         'priority_score' => $priorityScore,
//         'history_score' => round($historyScore, 1),
//         'distance_win_rate' => $winRate,
//         'draw_top3_rate' => $drawTop3Rate,
//         'recent_form' => $recentCount . '場',
//         'suggestion' => $suggestion
//     ];
// }

/**
 * 獲取馬匹優先級詳細數據 - 使用統一計算邏輯
 */
function getHorsePriorityDetails($pdo, $horseCode, $draw, $currentOdds, $v2Score) {
    // 构建 raceInfo 用于统一计算
    $raceInfo = [
        'distance' => 0,  // 需要从数据库获取
        'venue_code' => '',
        'go_en' => '',
        'go_ch' => '',
        'race_class' => '',
        'class_code' => '',
        'race_date' => date('Y-m-d')
    ];
    
    // 获取马匹的详细数据用于计算
    try {
        // 获取马匹的胜率数据
        $stmt = $pdo->prepare("
            SELECT 
                total_starts as starts,
                total_wins as wins,
                ROUND(IFNULL(total_wins, 0) / NULLIF(total_starts, 0) * 100, 1) as win_rate
            FROM hkracing_horses
            WHERE horse_code = ?
        ");
        $stmt->execute([$horseCode]);
        $horseStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 获取最近出赛记录
        $stmt = $pdo->prepare("
            SELECT finishing_position, class
            FROM hkracing_horse_performances
            WHERE horse_code = ?
                AND finishing_position IS NOT NULL
                AND finishing_position > 0
            ORDER BY race_date DESC
            LIMIT 5
        ");
        $stmt->execute([$horseCode]);
        $recentRuns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 获取骑师、练马师信息
        $stmt = $pdo->prepare("
            SELECT jockey_name_ch, trainer_name_ch
            FROM hkracing_runners
            WHERE horse_code = ?
            LIMIT 1
        ");
        $stmt->execute([$horseCode]);
        $runner = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 调用统一的 calculateUnifiedPriority 函数
        $horseData = [
            'horse_code' => $horseCode,
            'draw' => $draw,
            'current_odds' => floatval($currentOdds),
            'v2_score' => floatval($v2Score),
            'jockey_name' => $runner['jockey_name_ch'] ?? '',
            'trainer_name' => $runner['trainer_name_ch'] ?? '',
            'gear' => '',
            'weight' => 0
        ];
        
        $result = calculateUnifiedPriority($horseData, $raceInfo, $pdo);
        
        return [
            'priority_score' => $result['priority_score'],
            'history_score' => $result['history_score'],
            'distance_win_rate' => $result['distance_win_rate'],
            'draw_top3_rate' => $result['draw_top3_rate'],
            'recent_form' => $result['recent_form'],
            'suggestion' => $result['suggestion']
        ];
        
    } catch (Exception $e) {
        error_log("getHorsePriorityDetails error: " . $e->getMessage());
        
        // 降级使用简单计算
        $distanceWinRate = 0;
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins
            FROM hkracing_horse_performances
            WHERE horse_code = ? AND finishing_position IS NOT NULL AND finishing_position > 0
        ");
        $stmt->execute([$horseCode]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = intval($stats['total'] ?? 0);
        $wins = intval($stats['wins'] ?? 0);
        $winRate = $total > 0 ? round($wins / $total * 100, 1) : 0;
        
        $drawTop3Rate = 0;
        if ($draw > 0) {
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) as top3
                FROM hkracing_horse_performances
                WHERE horse_code = ? AND barrier_draw = ?
                    AND finishing_position IS NOT NULL AND finishing_position > 0
            ");
            $stmt->execute([$horseCode, $draw]);
            $drawStats = $stmt->fetch(PDO::FETCH_ASSOC);
            $drawTotal = intval($drawStats['total'] ?? 0);
            $drawTop3 = intval($drawStats['top3'] ?? 0);
            $drawTop3Rate = $drawTotal > 0 ? round($drawTop3 / $drawTotal * 100, 1) : 0;
        }
        
        $recentCount = 0;
        $stmt = $pdo->prepare("
            SELECT finishing_position
            FROM hkracing_horse_performances
            WHERE horse_code = ? AND finishing_position IS NOT NULL AND finishing_position > 0
            ORDER BY race_date DESC
            LIMIT 5
        ");
        $stmt->execute([$horseCode]);
        $recentRuns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $recentCount = count($recentRuns);
        
        $historyScore = min(100, $winRate + $drawTop3Rate / 2);
        $priorityScore = round(($historyScore * 0.4) + ($v2Score * 0.6), 1);
        $suggestion = generateSimpleSuggestion($priorityScore, $currentOdds, $winRate, $drawTop3Rate);
        
        return [
            'priority_score' => $priorityScore,
            'history_score' => round($historyScore, 1),
            'distance_win_rate' => $winRate,
            'draw_top3_rate' => $drawTop3Rate,
            'recent_form' => $recentCount . '場',
            'suggestion' => $suggestion
        ];
    }
}

/**
 * 生成簡單建議文字
 */
function generateSimpleSuggestion($score, $odds, $distanceWinRate, $drawTop3Rate) {
    if ($score >= 70) {
        $base = '🔥 强烈推荐';
    } elseif ($score >= 55) {
        $base = '⭐ 值得留意';
    } elseif ($score >= 40) {
        $base = '📌 可作為配腳';
    } else {
        $base = '⚠️ 需謹慎';
    }
    
    $reasons = [];
    if ($distanceWinRate >= 20) $reasons[] = "🏇 同程勝率{$distanceWinRate}%";
    elseif ($distanceWinRate >= 10) $reasons[] = "📊 同程有經驗({$distanceWinRate}%)";
    if ($drawTop3Rate >= 40) $reasons[] = "🎯 此檔位表現佳({$drawTop3Rate}%)";
    if ($odds <= 4 && $odds > 0) $reasons[] = "📈 熱門追捧";
    
    $reasonText = !empty($reasons) ? ' - ' . implode(' | ', $reasons) : '';
    
    return $base . $reasonText;
}

/**
 * 生成博文標題
 */
function generatePostTitle($date, $venueCode, $raceCount, $hasOdds, $scoreVersion) {
    $venueName = $venueCode == 'ST' ? '沙田' : '跑馬地';
    $dateFormatted = date('Y年m月d日', strtotime($date));
    
    if ($scoreVersion == 2) {
        $versionText = '含賠率分析';
    } else {
        $versionText = '僅歷史數據';
    }
    
    return "🏇 {$dateFormatted} {$venueName} 賽事分析 - 全日{$raceCount}場 ({$versionText})";
}

/**
 * 生成博文內容
 */
     //     <div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: white; padding: 50px 30px; border-radius: 20px; margin-bottom: 30px; text-align: center; box-shadow: 0 8px 25px rgba(0,0,0,0.15);">
    //     <div style="font-size: 80px; margin-bottom: 15px;">🏇</div>
    //     <h1 style="margin: 0 0 15px 0; font-size: 42px; font-weight: bold; color: #FFD700; letter-spacing: 2px;">{$venueName} 賽馬日分析</h1>
    //     <p style="margin: 0 0 10px 0; font-size: 20px; opacity: 0.95;">{$dateFormatted}（星期{$weekday}）</p>
    //     <div style="margin-top: 25px; display: flex; gap: 30px; justify-content: center; font-size: 16px; flex-wrap: wrap;">
    //         <span style="background: rgba(255,255,255,0.2); padding: 8px 20px; border-radius: 30px;">📅 {$dateFormatted}</span>
    //         <span style="background: rgba(255,255,255,0.2); padding: 8px 20px; border-radius: 30px;">🏟️ {$venueName}馬場</span>
    //         <span style="background: rgba(255,255,255,0.2); padding: 8px 20px; border-radius: 30px;">🐎 精選場數: <strong style="color: #FFD700; font-size: 20px;">{$raceCount}</strong>場</span>
    //         <span style="background: rgba(255,255,255,0.2); padding: 8px 20px; border-radius: 30px;">{$oddsText}</span>
    //     </div>
    //     <div style="margin-top: 20px; font-size: 14px; opacity: 0.85;">
    //         📌 {$noteText}
    //     </div>
    // </div>
    // <p>📌 查看其他賽馬日分析：<a href="https://buycarl.com/category/racing-news/" target="_blank" rel="noopener noreferrer">👉 所有賽馬分析文章</a> | <a href="https://buycarl.com/blog/" target="_blank" rel="noopener noreferrer">👉 返回部落格首頁</a></p>
    
    
function generatePostContent($date, $venueCode, $topPicks, $hasOdds) {
    $venueName = $venueCode == 'ST' ? '沙田' : '跑馬地';
    $dateFormatted = date('Y年m月d日', strtotime($date));
    $weekday = ['日', '一', '二', '三', '四', '五', '六'][date('w', strtotime($date))];
    $raceCount = count($topPicks);
    $focusKeyphrase = "{$dateFormatted} {$venueName}賽馬分析";
    
    // 根據是否有賠率顯示不同文字
    if ($hasOdds) {
        $oddsText = '✅ 已有賠率數據';
        $noteText = '包含即時賠率分析';
    } else {
        $oddsText = '📋 僅歷史數據分析';
        $noteText = '賠率尚未發佈，基於歷史數據';
    }
    
    $updatedTime = date('Y-m-d H:i:s');
    
    $content = '';
    
    // ========== 文章介紹段落（繁體中文） ==========
    $content .= <<<HTML
<div style="max-width:1200px; margin:0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

<h2>{$dateFormatted} {$venueName}賽馬分析 - 賽事概覽</h2>

<p><strong>{$dateFormatted} {$venueName}賽馬分析</strong>：（星期{$weekday}）於<strong>{$venueName}馬場</strong>舉行共 <strong>{$raceCount} 場賽事</strong>。以下為每場賽事的<strong>精選馬匹優先順序</strong>，基於歷史數據及賠率分析，包括：同程勝率、檔位表現、近況狀態等關鍵因素。</p>

<p>香港賽馬向來競爭激烈，要從眾多馬匹中挑選心水馬並不容易。我們的<strong>{$venueName}賽馬分析</strong>系統，透過大數據分析，為您提供每場賽事的<strong>馬匹優先順序</strong>，助您提高命中率。無論您是資深馬迷還是新手，這份<strong>{$dateFormatted} {$venueName}賽馬分析</strong>都能為您提供重要參考。</p>

HTML;
    
    // ========== 表格區域 ==========
    $content .= <<<HTML
    <div style="background: #f8f9fa; border-radius: 20px; padding: 25px; margin-bottom: 25px;">
        <h3 style="margin-top: 0; color: #333; font-size: 24px; border-left: 5px solid #667eea; padding-left: 15px;">📊 精選馬匹一覽</h3>
        <p style="color: #666; margin-bottom: 20px; font-size: 15px;">以下為每場賽事中，基於歷史數據分析的優先首選馬匹：</p>
        
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 15px; min-width: 700px; white-space: nowrap;">
                <thead>
                    <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <th style="text-align: center; padding: 14px 12px; border-radius: 12px 0 0 0;">場次</th>
                        <th style="text-align: center; padding: 14px 12px;">馬號</th>
                        <th style="text-align: left; padding: 14px 12px;">馬名</th>
                        <th style="text-align: center; padding: 14px 12px;">檔位</th>
                        <th style="text-align: center; padding: 14px 12px;">優先分</th>
                        <th style="text-align: left; padding: 14px 12px; border-radius: 0 12px 0 0;">建議</th>
                    </tr>
                </thead>
                <tbody>
HTML;
    
    // 添加表格內容
    foreach ($topPicks as $pick) {
        $priorityScore = $pick['priority_score'];
        $suggestion = mb_substr($pick['suggestion'], 0, 40);
        
        $rowBg = '';
        if ($priorityScore >= 70) {
            $rowBg = 'background: #fee2e2;';
        } elseif ($priorityScore >= 55) {
            $rowBg = 'background: #fef3c7;';
        } elseif ($priorityScore >= 40) {
            $rowBg = 'background: #e0f2fe;';
        }
        
        $content .= <<<HTML
                    <tr style="border-bottom: 1px solid #e0e0e0; {$rowBg}">
                        <td style="text-align: center; padding: 12px; font-weight: bold; white-space: nowrap;">第{$pick['race_no']}場</td>
                        <td style="text-align: center; padding: 12px; font-weight: bold; font-size: 18px; white-space: nowrap;">{$pick['runner_no']}</td>
                        <td style="text-align: left; padding: 12px; font-weight: 500; white-space: nowrap;">{$pick['horse_name']}</td>
                        <td style="text-align: center; padding: 12px; white-space: nowrap;">{$pick['draw']}</td>
                        <td style="text-align: center; padding: 12px; font-weight: bold; color: #667eea; white-space: nowrap;">{$priorityScore}分</td>
                        <td style="text-align: left; padding: 12px; white-space: nowrap;">{$suggestion}</td>
                    </tr>
HTML;
    }
    
    $content .= <<<HTML
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 30px; padding: 18px; background: #e9ecef; border-radius: 16px; font-size: 14px;">
            <strong style="font-size: 16px;">📊 評分解讀：</strong>
            <ul style="margin: 12px 0 0 20px;">
                <li><span style="background: #fee2e2; padding: 4px 12px; border-radius: 20px; font-weight: bold;">優先分 ≥70</span>：🔥 強烈推薦</li>
                <li><span style="background: #fef3c7; padding: 4px 12px; border-radius: 20px; font-weight: bold;">優先分 55-69</span>：⭐ 值得留意</li>
                <li><span style="background: #e0f2fe; padding: 4px 12px; border-radius: 20px; font-weight: bold;">優先分 40-54</span>：📌 可作配腳</li>
                <li><span style="background: #f8f9fa; padding: 4px 12px; border-radius: 20px; font-weight: bold;">優先分 &lt;40</span>：⚠️ 需謹慎</li>
            </ul>
        </div>
    </div>

HTML;
    
    // ========== 評分標準說明 ==========
    $content .= <<<HTML
<h2>📈 評分標準說明</h2>
<p>我們的<strong>{$venueName}賽馬分析</strong>系統採用以下評分標準，確保<strong>馬匹優先順序</strong>的客觀性：</p>
<ul>
    <li><strong>同程勝率 (權重20%)</strong> - 相同場地及距離的歷史表現</li>
    <li><strong>場地適性 (權重12%)</strong> - 好地、黏地等場地狀況表現</li>
    <li><strong>檔位表現 (權重10%)</strong> - 相同檔位的勝率及上名率</li>
    <li><strong>配備變化 (權重8%)</strong> - 首次戴眼罩等配備變動影響</li>
    <li><strong>騎師更換 (權重8%)</strong> - 換騎師對馬匹的影響</li>
    <li><strong>休憩日數 (權重5%)</strong> - 休息21-35天為最佳狀態</li>
    <li><strong>體重變化 (權重5%)</strong> - 減磅有利，增磅不利</li>
    <li><strong>近況狀態 (權重8%)</strong> - 最近5場表現加權計算</li>
    <li><strong>賠率調整 (±10)</strong> - 熱門加分，冷門扣分</li>
</ul>
<p>最終<strong>優先分</strong> = 歷史條件分(50%) + 綜合評分(50%)，分數越高代表越值得留意。這就是<strong>{$dateFormatted} {$venueName}賽馬分析</strong>的核心算法。</p>

<h2>🔗 相關連結</h2>
<p>📌 了解更多香港賽馬資訊，可參考 <a href="https://racing.hkjc.com/" target="_blank" rel="noopener noreferrer">香港賽馬會官方網站</a>（外鏈）。</p>
<p>📌 查看其他賽馬日分析：<a href="https://buycarl.com/racing-analysis/" target="_blank" rel="noopener noreferrer">👉 賽馬分析系統首頁</a>（內鏈）</p>
<p>📌 所有賽馬分析文章：<a href="https://buycarl.com/category/racing-news/" target="_blank" rel="noopener noreferrer">👉 Racing News 分類</a>（內鏈）</p>
<p>📌 返回部落格首頁：<a href="https://buycarl.com/blog/">Buy Carl 部落格</a>（內鏈）</p>

<h2>📌 賽事數據來源</h2>
<p>本<strong>{$venueName}賽馬分析</strong>所使用的數據來自香港賽馬會官方公佈的歷史賽績，包括馬匹出賽紀錄、騎師練馬師搭配、檔位統計等。我們每週持續更新數據庫，確保<strong>馬匹優先順序</strong>的準確性。</p>

<h2>🏆 我們的賽馬分析理念</h2>
<div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 16px; padding: 10px; margin-bottom: 25px;">
    <p style="font-size: 16px; margin-top: 0;"><strong>💡 不只是看勝率，而是看「贏的條件」</strong></p>
    <p>我們的核心分析邏輯不是問「這匹馬贏過多少次」，而是問：<strong>「這匹馬今天的條件與牠過往贏馬的條件有多吻合？」</strong></p>
    
    <p><strong>🏇 賽事核心分析框架：</strong></p>
    <ul style="margin-bottom: 0;">
        <li><strong>場地適性</strong> - 好地、黏地、軟地，每匹馬有不同的擅長</li>
        <li><strong>班次與評分</strong> - 降班有利？升班硬拼？評分是否在贏馬區間？</li>
        <li><strong>路程專項</strong> - 同程同場的往績是最強參考指標</li>
        <li><strong>檔位統計</strong> - 內檔放頭？外檔後上？跑法配合是關鍵</li>
        <li><strong>配備與狀態</strong> - 首次戴眼罩？體重變化？休息日數？</li>
    </ul>
    
    <p style="margin-bottom: 0; margin-top: 12px;"><strong>📊 我們的評分系統會綜合以上所有因素，計算出每匹馬的「優先分數」，而不是單純看勝率。</strong></p>
    <p style="margin-bottom: 0; font-size: 13px; color: #666;">當一匹馬在「同程」、「同檔位」、「同場地」、「近況佳」、「配備變動利好」等多個條件都吻合時，即使牠的歷史勝率不高，也可能迎來爆冷機會！</p>
</div>

<h2>⚠️ 免責聲明</h2>
<p>以上<strong>{$dateFormatted} {$venueName}賽馬分析</strong>僅供參考，投注前請考慮即時賠率及場地狀況變化。請遵守香港賽馬會相關規定，理性投注。本網站不保證分析結果的準確性，使用者應自行承擔風險。</p>

<div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #dee2e6; font-size: 12px; color: #999;">
    {$oddsText} | {$noteText}<br>
    <div style="margin-top: 10px;">
        分析系統由 <a href="https://buycarl.com/about-racing-system/" target="_blank">BuyCarl 賽馬分析團隊</a> 提供 | 理念：以條件匹配取代單純勝率統計
    </div>
    最後更新: {$updatedTime}
</div>
</div>
HTML;

    return $content;
}
/**
 * 生成博文摘要
 */
function generatePostExcerpt($topPicks) {
    $venueName = $venueCode ?? '';
    $raceCount = count($topPicks);
    
    // 创建美观的 HTML 摘录（用于列表页）
    $excerpt = '<div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); padding: 20px; border-radius: 12px; color: white;">';
    $excerpt .= '<div style="font-size: 48px; text-align: center; margin-bottom: 10px;">🏇</div>';
    $excerpt .= '<h3 style="color: #FFD700; margin: 0 0 10px 0; font-size: 20px; text-align: center;">賽日精選</h3>';
    $excerpt .= '<div style="font-size: 14px; line-height: 1.6;">';
    
    $count = 0;
    foreach ($topPicks as $pick) {
        if ($count >= 5) {
            $excerpt .= '<span style="display: inline-block; background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 15px; margin-left: 5px;">+' . (count($topPicks) - 5) . '場</span>';
            break;
        }
        $excerpt .= '<span style="display: inline-block; background: rgba(255,255,255,0.15); padding: 4px 10px; border-radius: 20px; margin: 3px;">';
        $excerpt .= "R{$pick['race_no']}: {$pick['runner_no']}號 {$pick['horse_name']}";
        $excerpt .= '</span> ';
        $count++;
    }
    
    $excerpt .= '</div>';
    $excerpt .= '<div style="text-align: center; margin-top: 12px; font-size: 12px; opacity: 0.8;">📊 點擊查看詳細分析</div>';
    $excerpt .= '</div>';
    
    return $excerpt;
}

/**
 * 發布到 WordPress
 */
function postToWordPress($title, $content, $excerpt, $date, $venueCode, $featuredImageId = null, $topPicks = null) {
    $wpUrl = 'https://buycarl.com/wp-json/wp/v2/posts';
    $username = getWpUsername();
    $password = getWpAppPassword();
    
    $categoryId = getRacingNewsCategoryId();
    
    // 根据场地获取标签 ID
    $tagIds = getVenueTagIds($venueCode);
    
    // 生成纯文本摘录
    $plainExcerpt = $excerpt;
    if (empty($plainExcerpt) && $topPicks) {
        $plainExcerpt = generatePostExcerpt($topPicks);
    }
    
    // 添加隐藏数据
    $hiddenData = '<!-- racing_date:' . $date . ' racing_venue:' . $venueCode . ' -->';
    $contentWithMeta = $hiddenData . $content;
    
    $postData = [
        'title' => $title,
        'content' => $contentWithMeta,
        'excerpt' => strip_tags($plainExcerpt),
        'status' => 'publish',
        'categories' => [$categoryId],
        'tags' => $tagIds,  // 添加标签
        'slug' => sanitizeTitle($title),
    ];
    
    if ($featuredImageId && is_numeric($featuredImageId)) {
        $postData['featured_media'] = (int)$featuredImageId;
    }
    
    $ch = curl_init($wpUrl);
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
        $postId = $result['id'];
        echo "文章已發布，ID: {$postId}\n";
        
        updateYoastViaInternalScript($postId, $date, $venueCode);
        
        return $postId;
    }
    
    echo "WordPress API 錯誤: HTTP {$httpCode}\n";
    echo "響應: " . substr($response, 0, 500) . "\n";
    return false;
}

/**
 * 通过 WordPress 内部脚本更新 Yoast SEO
 */
function updateYoastViaInternalScript($postId, $date, $venueCode) {
    $secret_token = 'your_secret_token_2026';  // 必须与 wp_update_yoast.php 中的 token 一致
    
    // ✅ 将日期和场地作为 URL 参数传递
    $url = "https://buycarl.com/00/HKJC_blogpost_update_yoast.php?post_id={$postId}&token={$secret_token}&date={$date}&venue={$venueCode}";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if ($result['success']) {
            echo "    Yoast SEO 已更新\n";
        } else {
            echo "    Yoast SEO 更新失败: " . ($result['error'] ?? '未知错误') . "\n";
        }
    } else {
        echo "    Yoast SEO 更新失败: HTTP {$httpCode}\n";
    }
}
/**
 * 獲取 WordPress 用戶名（需配置）
 */
function getWpUsername() {
    // 從配置文件或環境變量讀取
    global $wpConfig;
    return $wpConfig['username'] ?? 'your_username';
}

/**
 * 獲取 WordPress 應用密碼（需配置）
 */
function getWpAppPassword() {
    global $wpConfig;
    return $wpConfig['app_password'] ?? 'your_app_password';
}

/**
 * 獲取 Racing News 分類 ID
 */
/**
 * 獲取或創建 Racing News 分類 ID
 */
function getRacingNewsCategoryId() {
    $categoryName = 'Racing News';
    
    // 先查詢是否已存在
    $url = 'https://buycarl.com/wp-json/wp/v2/categories?slug=racing-news';
    $username = getWpUsername();
    $password = getWpAppPassword();
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode($username . ':' . $password)
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $categories = json_decode($response, true);
        if (!empty($categories)) {
            return $categories[0]['id'];
        }
    }
    
    // 不存在則創建
    $ch = curl_init('https://buycarl.com/wp-json/wp/v2/categories');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'name' => $categoryName,
        'slug' => 'racing-news'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($username . ':' . $password)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 201) {
        $newCategory = json_decode($response, true);
        return $newCategory['id'];
    }
    
    // 如果都失敗，返回默認分類 ID（通常是 1）
    return 1;
}

/**
 * 生成文章標籤
 */
function getPostTags($date, $venueCode) {
    $venueName = $venueCode == 'ST' ? '沙田' : '跑馬地';
    $yearMonth = date('Y-m', strtotime($date));
    
    return [
        sanitizeTitle($venueName),
        sanitizeTitle($yearMonth),
        '賽馬分析',
        '優先精選'
    ];
}

/**
 * 清理標題用於 slug
 */
function sanitizeTitle($title) {
    $title = preg_replace('/[^a-zA-Z0-9\u4e00-\u9fff]/u', '-', $title);
    $title = preg_replace('/-+/', '-', $title);
    return trim($title, '-');
}

/**
 * 記錄已發布的博文
 */
function recordBlogPost($pdo, $date, $venueCode, $postId, $scoreVersion) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS hkracing_blog_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            race_date DATE NOT NULL,
            venue_code VARCHAR(10) NOT NULL,
            wp_post_id INT NOT NULL,
            score_version INT DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_race_date (race_date),
            UNIQUE KEY uk_race_venue_version (race_date, venue_code, score_version)
        )
    ");
    
    $stmt = $pdo->prepare("
        INSERT INTO hkracing_blog_posts (race_date, venue_code, wp_post_id, score_version)
        VALUES (:date, :venue, :post_id, :version)
        ON DUPLICATE KEY UPDATE
            wp_post_id = VALUES(wp_post_id),
            created_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([
        ':date' => $date,
        ':venue' => $venueCode,
        ':post_id' => $postId,
        ':version' => $scoreVersion
    ]);
}

function checkHasOddsForDate($pdo, $date, $venueCode) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM hkracing_v2_score s
        JOIN hkracing_races r ON s.race_id = r.id
        JOIN hkracing_meetings m ON r.meeting_id = m.id
        WHERE m.date = ? AND m.venue_code = ? AND s.score_version = 2
        LIMIT 1
    ");
    $stmt->execute([$date, $venueCode]);
    return $stmt->fetchColumn() > 0;
}

/**
 * 查找可用的繁体中文汉字体
 */
function findChineseFont() {
    $fontPaths = [
        // 🏆 最佳选择：Noto Sans TC 繁体中文可变字体 (TTF)
        __DIR__ . '/fonts/NotoSansTC-VariableFont_wght.ttf',
        
        // 备选1：日文字体 (能显示大部分繁体汉字，作为备用)
        __DIR__ . '/fonts/NotoSansCJKjp-Regular.otf',
        
        // 备选2：文泉驿微米黑 (开源通用字体)
        __DIR__ . '/fonts/wqy-microhei.ttc',
        
        // 备选3：简体字体 (最后的选择)
        __DIR__ . '/fonts/SourceHanSansSC-Regular.otf',
    ];
    
    foreach ($fontPaths as $path) {
        if (file_exists($path)) {
            // 确保是完整字体文件（大于 1MB）
            if (filesize($path) > 1000000) {
                return $path;
            }
        }
    }
    return null;
}
/**
 * 生成特色图片并上传到 WordPress
 */
/**
 * 生成特色图片并上传到 WordPress
 */

function generateAndUploadFeaturedImage($date, $venueCode, $pdo = null) {
    $venueName = $venueCode == 'ST' ? '沙田' : '跑馬地';
    $dateChinese = date('Y年m月d日', strtotime($date));
    $weekday = ['日', '一', '二', '三', '四', '五', '六'][date('w', strtotime($date))];
    
    // 获取日赛/夜赛信息
    $sessionType = "日賽";
    $timeLabel = "DAY RACING";
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT r.post_time 
                FROM hkracing_races r
                JOIN hkracing_meetings m ON r.meeting_id = m.id
                WHERE m.date = ? AND m.venue_code = ?
                ORDER BY r.race_no ASC
                LIMIT 1
            ");
            $stmt->execute([$date, $venueCode]);
            $firstRace = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($firstRace && !empty($firstRace['post_time'])) {
                $hour = intval(date('H', strtotime($firstRace['post_time'])));
                if ($hour >= 18 || $hour < 6) {
                    $sessionType = "夜賽";
                    $timeLabel = "NIGHT RACING";
                }
            }
        } catch (Exception $e) {
            error_log("获取开跑时间失败: " . $e->getMessage());
        }
    }
    
    // 创建图片
    $width = 1200;
    $height = 630;
    $image = imagecreatetruecolor($width, $height);
    imageantialias($image, true);
    
    // 背景渐变
    for ($i = 0; $i < $height; $i++) {
        $ratio = $i / $height;
        if ($sessionType === "夜賽") {
            $r = 20 + (75 - 20) * $ratio;
            $g = 20 + (50 - 20) * $ratio;
            $b = 70 + (150 - 70) * $ratio;
        } else {
            $r = 102 + (118 - 102) * $ratio;
            $g = 126 + (75 - 126) * $ratio;
            $b = 234 + (162 - 234) * $ratio;
        }
        $color = imagecolorallocate($image, (int)$r, (int)$g, (int)$b);
        imageline($image, 0, $i, $width, $i, $color);
    }
    
    // 设置文字颜色
    $white = imagecolorallocate($image, 255, 255, 255);
    $gold = imagecolorallocate($image, 255, 215, 0);
    $lightWhite = imagecolorallocate($image, 220, 220, 220);
    
    // 查找字体
    $fontPath = findChineseFont();
    
    if ($fontPath) {
        // 1. 赛事类型
        imagettftext($image, 70, 0, 80, 140, $gold, $fontPath, $sessionType);
        // 2. 场地名称
        imagettftext($image, 65, 0, 280, 140, $white, $fontPath, $venueName);
        // 3. 主标题
        imagettftext($image, 55, 0, 80, 240, $white, $fontPath, "賽馬日分析");
        // 4. 日期
        imagettftext($image, 45, 0, 80, 340, $gold, $fontPath, $dateChinese);
        // 5. 星期
        imagettftext($image, 35, 0, 80, 410, $lightWhite, $fontPath, "星期{$weekday}");
        // 6. 英文标题
        $englishSub = $venueCode == 'ST' ? 'Sha Tin Race Day Analysis' : 'Happy Valley Race Day Analysis';
        imagettftext($image, 28, 0, 80, 490, $lightWhite, $fontPath, $englishSub);
        // 7. 底部说明
        $currentYear = date('Y');
        $footer = "© {$currentYear} BuyCarl.com | Priority Selection Based on Historical Data";
        imagettftext($image, 22, 0, 80, 570, $lightWhite, $fontPath, $footer);
    } else {
        // 降级方案
        imagestring($image, 5, 80, 250, $venueName . " Race Day", $white);
        imagestring($image, 5, 80, 300, $dateChinese, $gold);
        imagestring($image, 4, 80, 350, $englishSub, $lightWhite);
    }
    
    // 装饰边框
    imagerectangle($image, 5, 5, $width-6, $height-6, $gold);
    imagerectangle($image, 8, 8, $width-9, $height-9, $white);
    
    // 装饰圆点
    $decoColor = imagecolorallocate($image, 255, 215, 0);
    for ($i = 0; $i < 3; $i++) {
        imagefilledellipse($image, 1060 + ($i * 30), 100, 15, 15, $decoColor);
    }
    
    // ✅ 生成唯一文件名：日期 + 场地 + 时间戳 + 随机数
    $uniqueId = time() . '_' . rand(1000, 9999);
    $fileName = "racing-{$date}-{$venueCode}-{$uniqueId}.png";
    
    // 保存图片
    $tempFile = tempnam(sys_get_temp_dir(), 'racing_') . '.png';
    imagepng($image, $tempFile);
    imagedestroy($image);
    
    // 上传到 WordPress
    $mediaId = uploadToWordPress($tempFile, $fileName);
    
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }
    
    return $mediaId;
}
/**
 * 上传图片到 WordPress 媒体库
 */
function uploadToWordPress($filePath, $fileName) {
    $username = getWpUsername();
    $password = getWpAppPassword();
    
    $url = 'https://buycarl.com/wp-json/wp/v2/media';
    
    // ✅ 先检查是否存在同名图片（避免重复上传）
    $checkUrl = $url . '?search=' . urlencode($fileName);
    $ch = curl_init($checkUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode($username . ':' . $password)
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $existing = json_decode($response, true);
        if (!empty($existing)) {
            foreach ($existing as $media) {
                if ($media['title']['raw'] === $fileName || basename($media['source_url']) === $fileName) {
                    echo "    图片已存在，跳过上传 (ID: {$media['id']})\n";
                    return $media['id'];
                }
            }
        }
    }
    
    // 上传新图片
    $fileContent = file_get_contents($filePath);
    $fileType = mime_content_type($filePath);
    
    $boundary = wp_generate_boundary();
    
    $body = "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$fileName}\"\r\n";
    $body .= "Content-Type: {$fileType}\r\n\r\n";
    $body .= $fileContent . "\r\n";
    $body .= "--{$boundary}--";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode($username . ':' . $password),
        'Content-Type: multipart/form-data; boundary=' . $boundary,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 201) {
        $result = json_decode($response, true);
        return $result['id'];
    }
    
    echo "    上传图片失败: HTTP {$httpCode}\n";
    return null;
}

/**
 * 生成 boundary（如果 WordPress 函数不存在）
 */
function wp_generate_boundary() {
    return '----WebKitFormBoundary' . md5(uniqid());
}

/**
 * 获取场地标签 ID（ST 或 HV）
 * 如果标签不存在则自动创建
 */
function getVenueTagIds($venueCode) {
    $tagNames = [];
    
    // 添加场地标签
    if ($venueCode == 'ST') {
        $tagNames[] = '沙田';
        $tagNames[] = 'ST';
    } elseif ($venueCode == 'HV') {
        $tagNames[] = '跑馬地';
        $tagNames[] = 'HV';
    }
    
    // 添加通用标签
    $tagNames[] = '賽馬分析';
    $tagNames[] = '香港賽馬';
    
    $tagIds = [];
    foreach ($tagNames as $tagName) {
        $tagId = getOrCreateTag($tagName);
        if ($tagId) {
            $tagIds[] = $tagId;
        }
    }
    
    return $tagIds;
}

/**
 * 获取或创建 WordPress 标签
 */
function getOrCreateTag($tagName) {
    $username = getWpUsername();
    $password = getWpAppPassword();
    
    // 先搜索是否已存在
    $url = 'https://buycarl.com/wp-json/wp/v2/tags?search=' . urlencode($tagName);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode($username . ':' . $password)
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $tags = json_decode($response, true);
        if (!empty($tags)) {
            foreach ($tags as $tag) {
                if ($tag['name'] === $tagName) {
                    return $tag['id'];
                }
            }
        }
    }
    
    // 不存在则创建
    $ch = curl_init('https://buycarl.com/wp-json/wp/v2/tags');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['name' => $tagName]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($username . ':' . $password)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 201) {
        $newTag = json_decode($response, true);
        return $newTag['id'];
    }
    
    return null;
}
?>