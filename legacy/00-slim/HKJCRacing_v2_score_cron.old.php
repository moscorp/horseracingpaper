<?php
/**
 * HKJC V2 綜合評分計算腳本
 * 
 * 智能版本偵測：
 * - 自動檢查該賽事是否有賠率資料
 * - 有賠率 → 儲存 version 2 (有賠率評分)
 * - 無賠率 → 儲存 version 1 (無賠率評分)
 * 
 * 執行方式：
 * php HKJCRacing_v2_score_cron.php --type=batch                    # 處理所有未完成賽事
 * php HKJCRacing_v2_score_cron.php --type=batch --date=2026-04-19  # 處理指定日期
 * php HKJCRacing_v2_score_cron.php --type=batch --race_id=RACE_xxx # 處理指定賽事
 * php HKJCRacing_v2_score_cron.php --type=batch --force            # 強制重新計算
 * 
 * Web 執行：
 * https://buycarl.com/00/HKJCRacing_v2_score_cron.php?type=batch
 * https://buycarl.com/00/HKJCRacing_v2_score_cron.php?type=batch&date=2026-04-19
 */

include_once ("lib/constants.php");
require_once "HKJCRacing_score_functions.php";

date_default_timezone_set('Asia/Hong_Kong');

// 允許 Web 執行
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

$options = getopt("", ["type::", "race_id::", "date::", "venue::", "force"]);
$type = $options['type'] ?? 'batch';
$raceId = isset($options['race_id']) ? $options['race_id'] : null;
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
            if ($raceId) {
                processSingleRaceSmart($pdo, $raceId, $force);
            } else {
                processBatchSmart($pdo, $date, $venue, $force);
            }
            break;
    }
    
    echo "\n[" . date('Y-m-d H:i:s') . "] 評分計算完成\n";
    
} catch (Exception $e) {
    echo "錯誤: " . $e->getMessage() . "\n";
    error_log("HKJC V2 Score Error: " . $e->getMessage());
    exit(1);
}

// ========== 主處理函數 ==========

/**
 * 智能批次處理：自動偵測所有賽事該用哪個版本
 */
function processBatchSmart($pdo, $date, $venue, $force = false) {
    echo "=== 智能批次處理開始 ===\n";
    echo "日期參數: " . ($date ?: '自動偵測') . ", 馬場: " . ($venue ?: '全部') . "\n";
    echo "強制更新: " . ($force ? '是' : '否') . "\n\n";
    
    // 修復後的 SQL - 只查詢香港本地賽事
    $sql = "
        SELECT 
            r.id, 
            r.race_no,
            m.date, 
            m.venue_code,
            COUNT(DISTINCT ru.horse_code) as horse_count,
            (
                SELECT COUNT(*) 
                FROM hkracing_odds_horse_details o 
                WHERE o.race_date = m.date 
                  AND o.venue_code = m.venue_code 
                  AND o.race_no = r.race_no
            ) as odds_count
        FROM hkracing_races r
        JOIN hkracing_meetings m ON r.meeting_id = m.id
        JOIN hkracing_runners ru ON ru.race_id = r.id
        WHERE m.venue_code IN ('ST', 'HV')  -- 只處理香港本地賽事
    ";
    
    // 靈活的日期條件
    if ($date) {
        $sql .= " AND m.date = :date";
    } else {
        // 沒有指定日期：處理今天及未來7天的賽事
        $sql .= " AND m.date >= CURDATE() AND m.date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
    }
    
    if ($venue) {
        $sql .= " AND m.venue_code = :venue";
    }
    
    // 只處理未完成的賽事
    if (!$force) {
        $sql .= " AND NOT EXISTS (
            SELECT 1 FROM hkracing_v2_score s 
            WHERE s.race_id = r.id 
              AND s.race_finished = 1
              AND s.score_version = 2
        )";
    }
    
    $sql .= " GROUP BY r.id ORDER BY m.date, r.race_no";
    
    $stmt = $pdo->prepare($sql);
    $params = [];
    if ($date) $params[':date'] = $date;
    if ($venue) $params[':venue'] = $venue;
    $stmt->execute($params);
    $races = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "找到 " . count($races) . " 場香港本地賽事需要處理\n\n";
    
    if (count($races) == 0) {
        echo "沒有找到需要處理的賽事。\n";
        echo "可能原因：\n";
        echo "1. 近期沒有賽事\n";
        echo "2. 所有賽事都已處理完成\n";
        echo "3. 賽事資料尚未匯入 hkracing_runners\n\n";
        
        // 顯示近期賽事供參考
        $stmt = $pdo->query("
            SELECT m.date, COUNT(DISTINCT r.id) as race_count, COUNT(DISTINCT ru.horse_code) as horse_count
            FROM hkracing_meetings m
            LEFT JOIN hkracing_races r ON r.meeting_id = m.id
            LEFT JOIN hkracing_runners ru ON ru.race_id = r.id
            WHERE m.date >= CURDATE()
            GROUP BY m.date
            ORDER BY m.date
            LIMIT 5
        ");
        $upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($upcoming) {
            echo "近期賽事概況：\n";
            foreach ($upcoming as $u) {
                echo "  - {$u['date']}: {$u['race_count']}場, {$u['horse_count']}匹馬\n";
            }
        }
        return;
    }
    
    $stats = [
        'total' => count($races),
        'with_odds' => 0,
        'without_odds' => 0,
        'skipped' => 0,
        'errors' => 0
    ];

    foreach ($races as $race) {
        echo "\n--- 處理賽事: {$race['date']} - {$race['venue_code']} 第{$race['race_no']}場 ---\n";
        echo "馬匹數量: {$race['horse_count']}, 賠率數量: {$race['odds_count']}\n";
        
        try {
            $result = processSingleRaceSmart($pdo, $race['id'], $force);
            
            if ($result['has_odds']) {
                $stats['with_odds']++;
                echo "✅ 有賠率資料 → 儲存 Version 2 (有賠率評分)\n";
            } else {
                $stats['without_odds']++;
                echo "📋 無賠率資料 → 儲存 Version 1 (無賠率評分)\n";
            }
            
            if ($result['skipped']) {
                $stats['skipped']++;
                echo "⏭️ 跳過 (已存在且無需更新)\n";
            } else {
                echo "✅ 成功更新 {$result['updated_count']} 匹馬\n";
            }
            
        } catch (Exception $e) {
            $stats['errors']++;
            echo "❌ 錯誤: " . $e->getMessage() . "\n";
        }
        
        usleep(100000);
    }
    
    echo "\n=== 處理完成 ===\n";
    echo "總賽事數: {$stats['total']}\n";
    echo "有賠率賽事: {$stats['with_odds']}\n";
    echo "無賠率賽事: {$stats['without_odds']}\n";
    echo "跳過賽事: {$stats['skipped']}\n";
    echo "錯誤賽事: {$stats['errors']}\n";
}

/**
 * 智能處理單場賽事：自動偵測該用哪個版本
 */
function processSingleRaceSmart($pdo, $raceId, $force = false) {
    // 確保 $raceId 是有效的
    if (empty($raceId)) {
        throw new Exception("無效的賽事 ID: {$raceId}");
    }
    
    echo "處理賽事 ID: {$raceId}\n";
    
    $result = [
        'has_odds' => false,
        'skipped' => false,
        'updated_count' => 0,
        'version_used' => null
    ];
    
    // 獲取賽事資訊
    $stmt = $pdo->prepare("
        SELECT r.*, m.venue_code, m.date
        FROM hkracing_races r
        JOIN hkracing_meetings m ON r.meeting_id = m.id
        WHERE r.id = ?
    ");
    $stmt->execute([$raceId]);
    $race = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$race) {
        throw new Exception("賽事不存在: {$raceId}");
    }
    
    // 獲取馬匹數量
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM hkracing_runners WHERE race_id = ?");
    $stmt->execute([$raceId]);
    $horseCount = $stmt->fetchColumn();
    
    echo "賽事 ID: {$raceId}, 馬匹數量: {$horseCount}\n";
    
    // 檢查賽事是否已結束（只有真正有完賽結果的才跳過）
    $isFinished = isRaceFinished($pdo, $raceId);
    if ($isFinished) {
        echo "賽事已有完賽結果，標記完成並跳過\n";
        markRaceAsFinished($pdo, $raceId);
        $result['skipped'] = true;
        return $result;
    }
    
    // 檢查是否有賠率資料
    $oddsCount = checkOddsAvailability($pdo, $race['date'], $race['venue_code'], $race['race_no']);
    $hasOdds = ($oddsCount > 0);
    $result['has_odds'] = $hasOdds;

    // 決定使用哪個版本
    if ($hasOdds) {
        $result['version_used'] = 2;

        if (!$force) {
            // 檢查是否已有完整的 Version 2 評分
            if (hasRaceVersion2Score($pdo, $raceId)) {
                $lastUpdated = getRaceLastUpdated($pdo, $raceId, 2);
                if ($lastUpdated && strtotime($lastUpdated) > strtotime('-5 minutes')) {
                    echo "Version 2 在5分鐘內剛更新過，跳過\n";
                    $result['skipped'] = true;
                    return $result;
                }
            }
        }
        
        $result['updated_count'] = calculateScoreWithOdds($pdo, $raceId, $force);
    } else {
        $result['version_used'] = 1;
        
        // 對於無賠率版本，檢查是否已經完整計算過
        if (!$force) {
            if (hasRaceVersion1Score($pdo, $raceId)) {
                echo "Version 1 已完整計算（{$horseCount} 匹馬），跳過\n";
                $result['skipped'] = true;
                return $result;
            }
        }
        
        // 檢查馬匹資料是否完整
        if (!isAllHorsesFetched($pdo, $raceId)) {
            echo "⚠️ 馬匹資料尚未完整，等待下次執行\n";
            $result['skipped'] = true;
            return $result;
        }
        
        $result['updated_count'] = calculateScoreWithoutOdds($pdo, $raceId, $force);
    }

    return $result;
}

/**
 * 計算無賠率版本的評分（Version 1）
 */
function calculateScoreWithoutOdds($pdo, $raceId, $force = false) {
    echo "計算 Version 1 (無賠率評分) - 賽事 ID: {$raceId}\n";
    
    // 確保 race_id 有效
    if (empty($raceId) || $raceId == '0') {
        echo "錯誤: 無效的賽事 ID\n";
        return 0;
    }
    
    // 獲取賽事資訊
    $stmt = $pdo->prepare("
        SELECT r.*, m.venue_code, m.date
        FROM hkracing_races r
        JOIN hkracing_meetings m ON r.meeting_id = m.id
        WHERE r.id = ?
    ");
    $stmt->execute([$raceId]);
    $race = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$race) {
        echo "錯誤: 找不到賽事 ID: {$raceId}\n";
        return 0;
    }
    
    $isHongKongRace = in_array($race['venue_code'], ['ST', 'HV']);
    
    // 獲取參賽馬匹 - 使用正確的欄位名 barrier_draw_number
    $stmt = $pdo->prepare("
        SELECT 
            runner_no,
            horse_code,
            name_ch as horse_name,
            jockey_name_ch as jockey_name,
            trainer_name_ch as trainer_name,
            barrier_draw_number as draw
        FROM hkracing_runners
        WHERE race_id = ?
    ");
    $stmt->execute([$raceId]);
    $runners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "找到 " . count($runners) . " 匹參賽馬匹\n";
    
    $updatedCount = 0;
    $skippedCount = 0;
    
    foreach ($runners as $runner) {
        $horseCode = trim($runner['horse_code']);
        if (empty($horseCode)) {
            echo "  ⚠️ 馬匹代碼為空，跳過\n";
            continue;
        }
        
        // 獲取檔位 - 如果是空的表示馬匹已退出
        $draw = trim($runner['draw'] ?? '');
        if (empty($draw)) {
            echo "  ⚠️ 馬匹 {$horseCode} 沒有檔位（已退出），跳過\n";
            $skippedCount++;
            continue;
        }
        $draw = intval($draw);
        
        if (!$force && scoreExists($pdo, $raceId, $horseCode, 1)) {
            echo "  ⏭️ 馬匹 {$horseCode} 評分已存在，跳過\n";
            continue;
        }
        
        // 清理騎師和練馬師名稱
        $jockeyName = trim($runner['jockey_name'] ?? '');
        if (empty($jockeyName) || $jockeyName === '---') {
            $jockeyName = null;
        }
        
        $trainerName = trim($runner['trainer_name'] ?? '');
        if (empty($trainerName)) {
            $trainerName = null;
        }
        
        echo "  🐎 處理馬匹: {$horseCode}, 檔位: {$draw}\n";
        
        if ($isHongKongRace) {
            $horseStats = getHorseStats($pdo, $horseCode);
            $recentRuns = getRecentRuns($pdo, $horseCode, 3);
            
            // $v2Result = calculateV2ScoreForCron(
            $v2Result = calculateV2Score(
                $horseStats,
                $recentRuns,
                $draw,
                null,
                $race['race_class_en'] ?? '',
                $horseCode,
                $pdo,
                $trainerName,
                $jockeyName,
                $race['distance'],
                $race['venue_code']
            );
            
            // 確保 breakdown 存在
            $breakdown = $v2Result['breakdown'] ?? [];
            
            $scoreResult = [
                'total_score' => $v2Result['score'],
                'prediction_tag' => $v2Result['tag'],
                'prediction_class' => $v2Result['tag_class'],
                'class_change_text' => $v2Result['class_change_text'],
                'scores' => [
                    'win_rate' => $breakdown['win_rate'] ?? 0,
                    'recent_form' => $breakdown['recent_form'] ?? 0,
                    'draw_history' => $breakdown['draw_history'] ?? 0,
                    'trainer_jockey' => $breakdown['trainer_jockey'] ?? 0,
                    'distance_venue' => $breakdown['distance_venue'] ?? 0,
                    'odds' => $breakdown['odds'] ?? 0,
                    'class_change' => $breakdown['class_change'] ?? 0,
                    'trainer_venue' => $breakdown['trainer_venue'] ?? 0,
                    'trainer_form' => $breakdown['trainer_form'] ?? 0
                ]
            ];
            
            saveScore($pdo, $raceId, $horseCode, $runner['runner_no'], $scoreResult, 1, null);
            echo "     總分: {$v2Result['score']}\n";
            $updatedCount++;
        } else {
            // 海外賽事
            $scoreResult = [
                'total_score' => 50,
                'prediction_tag' => '🌏 海外賽事',
                'prediction_class' => 'prediction-equal',
                'class_change_text' => '<span style="color: #9ca3af;">🌏 海外賽事</span>',
                'scores' => [
                    'win_rate' => 0, 'recent_form' => 0, 'draw_history' => 0,
                    'trainer_jockey' => 0, 'distance_venue' => 0, 'odds' => 0,
                    'class_change' => 0, 'trainer_venue' => 0, 'trainer_form' => 0
                ]
            ];
            saveScore($pdo, $raceId, $horseCode, $runner['runner_no'], $scoreResult, 1, null);
            $updatedCount++;
        }
    }
    
    echo "完成 {$updatedCount} 匹馬的無賠率評分計算，跳過 {$skippedCount} 匹已退出馬匹\n";
    return $updatedCount;
}

/**
 * 檢查賽事是否已結束（修復版）
 */
function isRaceFinished($pdo, $raceId) {
    // 1. 先檢查是否有完賽結果 (final_position 有值)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM hkracing_runners 
        WHERE race_id = ? AND final_position IS NOT NULL AND final_position > 0
    ");
    $stmt->execute([$raceId]);
    $finishedCount = $stmt->fetchColumn();
    
    if ($finishedCount > 0) {
        echo "  [檢查] 發現 {$finishedCount} 匹馬已有完賽結果\n";
        return true;
    }
    
    // 2. 檢查賽事時間是否已過
    $stmt = $pdo->prepare("
        SELECT m.date, r.post_time 
        FROM hkracing_races r
        JOIN hkracing_meetings m ON r.meeting_id = m.id
        WHERE r.id = ?
    ");
    $stmt->execute([$raceId]);
    $race = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($race && $race['post_time']) {
        // 修復：只取日期部分，如果 post_time 已經包含完整時間
        $datePart = $race['date'];
        $timePart = $race['post_time'];
        
        // 如果 post_time 已經包含日期（長度大於10），則直接使用
        if (strlen($timePart) > 10) {
            $raceDateTime = strtotime($timePart);
        } else {
            $raceDateTime = strtotime($datePart . ' ' . $timePart);
        }
        
        $currentTime = time();
        
        echo "  [檢查] 賽事日期: {$datePart}\n";
        echo "  [檢查] 賽事時間: {$timePart}\n";
        echo "  [檢查] 解析後時間戳: " . date('Y-m-d H:i:s', $raceDateTime) . "\n";
        echo "  [檢查] 當前時間: " . date('Y-m-d H:i:s', $currentTime) . "\n";
        
        // 只有當賽事時間已過 2 小時，才認為賽事結束
        if ($currentTime > $raceDateTime + 7200) {
            echo "  [檢查] 賽事已過2小時，標記為結束\n";
            return true;
        } else {
            echo "  [檢查] 賽事尚未開始或剛結束不久\n";
        }
    } else {
        echo "  [檢查] 無法獲取賽事時間\n";
    }
    
    return false;
}


/**
 * 計算有賠率版本的評分（Version 2）
 */
function calculateScoreWithOdds($pdo, $raceId, $force = false) {
    echo "計算 Version 2 (有賠率評分)\n";
    
    $stmt = $pdo->prepare("
        SELECT r.*, m.venue_code, m.date
        FROM hkracing_races r
        JOIN hkracing_meetings m ON r.meeting_id = m.id
        WHERE r.id = ?
    ");
    $stmt->execute([$raceId]);
    $race = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (isRaceFinished($pdo, $raceId)) {
        markRaceAsFinished($pdo, $raceId);
        echo "賽事已結束，標記完成\n";
        return 0;
    }
    
    $isHongKongRace = in_array($race['venue_code'], ['ST', 'HV']);
    
    $oddsMap = getOddsData($pdo, $race['date'], $race['venue_code'], $race['race_no']);
    $hasOdds = count($oddsMap) > 0;
    
    if (!$hasOdds) {
        echo "暫無賠率資料，跳過更新\n";
        return 0;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            runner_no,
            horse_code,
            name_ch as horse_name,
            jockey_name_ch as jockey_name,
            trainer_name_ch as trainer_name,
            barrier_draw_number as draw
        FROM hkracing_runners
        WHERE race_id = ?
    ");
    $stmt->execute([$raceId]);
    $runners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updatedCount = 0;
    $skippedCount = 0;
    
    foreach ($runners as $runner) {
        $horseCode = trim($runner['horse_code']);
        if (empty($horseCode)) continue;
        
        // 檢查檔位 - 如果是空的表示馬匹已退出
        $draw = trim($runner['draw'] ?? '');
        if (empty($draw)) {
            echo "  ⚠️ 馬匹 {$horseCode} 沒有檔位（已退出），跳過\n";
            $skippedCount++;
            continue;
        }
        
        $winOdds = isset($oddsMap[$horseCode]) ? floatval($oddsMap[$horseCode]) : null;
        
        if ($isHongKongRace) {
            $horseStats = getHorseStats($pdo, $horseCode);
            $recentRuns = getRecentRuns($pdo, $horseCode, 3);
            
            if ($winOdds) {
                // 有賠率：計算完整評分
                // $v2Result = calculateV2ScoreForCron(
                $v2Result = calculateV2Score(
                    $horseStats,
                    $recentRuns,
                    $draw,
                    $winOdds,
                    $race['race_class_en'] ?? '',
                    $horseCode,
                    $pdo,
                    $runner['trainer_name'],
                    $runner['jockey_name'],
                    $race['distance'],
                    $race['venue_code']
                );
                
                // 轉換為 saveScore 期望的格式
                $scoreResult = [
                    'total_score' => $v2Result['score'],
                    'prediction_tag' => $v2Result['tag'],
                    'prediction_class' => $v2Result['tag_class'],
                    'class_change_text' => $v2Result['class_change_text'],
                    'scores' => $v2Result['breakdown'] ?? []
                ];
                
                saveScore($pdo, $raceId, $horseCode, $runner['runner_no'], $scoreResult, 2, $winOdds);
                echo "  🐎 {$horseCode}: 賠率 {$winOdds}, 總分 {$v2Result['score']}\n";
                $updatedCount++;
            } else {
                // 無賠率但其他馬有：複製 Version 1 的評分
                copyScoreFromVersion1($pdo, $raceId, $horseCode);
                echo "  📋 {$horseCode}: 無賠率，複製 Version 1 評分\n";
                $updatedCount++;
            }
        } else {
            // 海外賽事
            $scoreResult = calculateOverseasScore($winOdds);
            saveScore($pdo, $raceId, $horseCode, $runner['runner_no'], $scoreResult, 2, $winOdds);
            $updatedCount++;
        }
    }
    
    echo "完成 {$updatedCount} 匹馬的有賠率評分計算，跳過 {$skippedCount} 匹已退出馬匹\n";
    return $updatedCount;
}

/**
 * 計算 V2 評分（無賠率版本）
 */
function calculateV2ScoreWithoutOdds($horseStats, $recentRuns, $draw, $raceClass, $horseCode, $pdo, $trainerName, $jockeyName, $distance, $venueCode) {
    $scores = [
        'win_rate' => 0,
        'recent_form' => 0,
        'draw_history' => 0,
        'trainer_jockey' => 0,
        'distance_venue' => 0,
        'odds' => 0,
        'class_change' => 0,
        'trainer_venue' => 0,
        'trainer_form' => 0
    ];
    
    $starts = intval($horseStats['starts'] ?? 0);
    $winRate = floatval($horseStats['win_rate'] ?? 0);
    
    // 1. 勝率評分 (最高30分)
    if ($starts >= 3) {
        if ($winRate >= 25) $scores['win_rate'] = 30;
        elseif ($winRate >= 20) $scores['win_rate'] = 28;
        elseif ($winRate >= 15) $scores['win_rate'] = 25;
        elseif ($winRate >= 12) $scores['win_rate'] = 22;
        elseif ($winRate >= 10) $scores['win_rate'] = 20;
        elseif ($winRate >= 8) $scores['win_rate'] = 15;
        elseif ($winRate >= 5) $scores['win_rate'] = 10;
        elseif ($winRate >= 3) $scores['win_rate'] = 5;
    }
    
    // 2. 近三場表現 (最高30分)
    $recentScore = 0;
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
    $scores['recent_form'] = $recentScore;
    
    // 3. 檔位歷史 (最高15分)
    $scores['draw_history'] = calculateDrawScore($pdo, $horseCode, $draw);
    
    // 4. 騎練合作 (最高15分)
    $scores['trainer_jockey'] = calculateTrainerJockeyScore($pdo, $horseCode, $trainerName, $jockeyName);
    
    // 5. 同程同場 (最高15分)
    $scores['distance_venue'] = calculateDistanceVenueScore($pdo, $horseCode, $distance, $venueCode);
    
    // 6. 賠率評分 (無賠率時為0)
    $scores['odds'] = 0;
    
    // 7. 班次變動 (±12分)
    $classChangeResult = calculateClassChangeScore($recentRuns, $raceClass);
    $scores['class_change'] = $classChangeResult['score'];
    $classChangeText = $classChangeResult['text'];
    
    // 8. 練馬師場地 (最高10分)
    $scores['trainer_venue'] = calculateTrainerVenueScore($pdo, $trainerName, $venueCode);
    
    // 9. 練馬師近態 (最高10分)
    $scores['trainer_form'] = calculateTrainerFormScore($pdo, $trainerName);
    
    // 計算總分
    $totalScore = array_sum($scores);
    $totalScore = min(100, max(0, $totalScore));
    
    $prediction = getPredictionByScore($totalScore);
    
    return [
        'total_score' => round($totalScore),
        'prediction_tag' => $prediction['tag'],
        'prediction_class' => $prediction['class'],
        'class_change_text' => $classChangeText,
        'scores' => $scores
    ];
}

/**
 * 計算 V2 評分（有賠率版本）
 */
function calculateV2ScoreWithOdds($horseStats, $recentRuns, $draw, $winOdds, $raceClass, $horseCode, $pdo, $trainerName, $jockeyName, $distance, $venueCode) {
    // 先計算無賠率基礎評分
    $baseResult = calculateV2ScoreWithoutOdds($horseStats, $recentRuns, $draw, $raceClass, $horseCode, $pdo, $trainerName, $jockeyName, $distance, $venueCode);
    
    // 計算賠率評分（最高15分）
    $oddsScore = calculateOddsScore($winOdds);
    $baseResult['scores']['odds'] = $oddsScore;
    
    // 重新計算總分
    $totalScore = array_sum($baseResult['scores']);
    $totalScore = min(100, max(0, $totalScore));
    
    $prediction = getPredictionByScore($totalScore);
    
    return [
        'total_score' => round($totalScore),
        'prediction_tag' => $prediction['tag'],
        'prediction_class' => $prediction['class'],
        'class_change_text' => $baseResult['class_change_text'],
        'scores' => $baseResult['scores']
    ];
}

/**
 * 海外賽事評分
 */
function calculateOverseasScore($winOdds) {
    if ($winOdds && $winOdds > 0) {
        $totalScore = round(max(0, 100 - ($winOdds * 3)), 0);
        $totalScore = min(100, max(0, $totalScore));
    } else {
        $totalScore = 50;
    }
    
    $prediction = getPredictionByScore($totalScore);
    
    return [
        'total_score' => $totalScore,
        'prediction_tag' => $prediction['tag'],
        'prediction_class' => $prediction['class'],
        'class_change_text' => '<span style="color: #9ca3af;">🌏 海外賽事</span>',
        'scores' => [
            'win_rate' => 0, 'recent_form' => 0, 'draw_history' => 0,
            'trainer_jockey' => 0, 'distance_venue' => 0, 'odds' => $totalScore,
            'class_change' => 0, 'trainer_venue' => 0, 'trainer_form' => 0
        ]
    ];
}

// ========== 輔助計算函數 ==========

/**
 * 賠率評分計算（最高15分）
 */
function calculateOddsScore($odds) {
    if ($odds <= 2.0) return 15;
    if ($odds <= 3.0) return 14;
    if ($odds <= 4.0) return 12;
    if ($odds <= 6.0) return 9;
    if ($odds <= 10.0) return 6;
    if ($odds <= 15.0) return 4;
    return 2;
}

/**
 * 根據總分獲取預測標籤
 */
function getPredictionByScore($score) {
    if ($score >= 85) {
        return ['tag' => '🔥 熱門首選', 'class' => 'prediction-hot'];
    } elseif ($score >= 75) {
        return ['tag' => '⭐ 值得關注', 'class' => 'prediction-hot'];
    } elseif ($score >= 65) {
        return ['tag' => '⚖️ 位置之選', 'class' => 'prediction-equal'];
    } elseif ($score >= 55) {
        return ['tag' => '📊 冷門配搭', 'class' => 'prediction-equal'];
    } else {
        return ['tag' => '❄️ 機會渺茫', 'class' => 'prediction-cold'];
    }
}

/**
 * 計算檔位評分
 */
/**
 * 計算檔位評分（修復版）
 */
function calculateDrawScore($pdo, $horseCode, $draw) {
    $drawNum = intval($draw);
    if ($drawNum <= 0) {
        return 0;  // 沒有檔位，返回 0
    }
    
    if ($drawNum <= 3) {
        $drawGroup = '內檔(1-3)';
    } elseif ($drawNum <= 6) {
        $drawGroup = '中檔(4-6)';
    } else {
        $drawGroup = '外檔(7+)';
    }
    
    $sql = "
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
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$horseCode, $drawGroup]);
    $drawHistory = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($drawHistory && $drawHistory['rides'] >= 3) {
        $drawWinRate = $drawHistory['win_rate'];
        if ($drawWinRate >= 20) return 15;
        if ($drawWinRate >= 15) return 12;
        if ($drawWinRate >= 10) return 8;
        if ($drawWinRate >= 5) return 5;
        return 2;
    } else {
        if ($drawNum <= 3) return 5;
        if ($drawNum <= 6) return 3;
        return 1;
    }
}

/**
 * 計算騎練合作評分
 */
function calculateTrainerJockeyScore($pdo, $horseCode, $trainerName, $jockeyName) {
    if (!$trainerName || !$jockeyName) return 0;
    
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
        if ($tjWinRate >= 25) return 15;
        if ($tjWinRate >= 20) return 12;
        if ($tjWinRate >= 15) return 10;
        if ($tjWinRate >= 10) return 7;
        if ($tjWinRate >= 5) return 4;
        return 1;
    }
    return 0;
}

/**
 * 計算同程同場評分
 */
function calculateDistanceVenueScore($pdo, $horseCode, $distance, $venueCode) {
    if (!$distance || !$venueCode) return 0;
    
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
        if ($dvWinRate >= 25) return 15;
        if ($dvWinRate >= 20) return 12;
        if ($dvWinRate >= 15) return 10;
        if ($dvWinRate >= 10) return 7;
        if ($dvWinRate >= 5) return 4;
        return 1;
    }
    return 0;
}

/**
 * 計算班次變動評分
 */
function calculateClassChangeScore($recentRuns, $raceClass) {
    $classChangeScore = 0;
    $classChangeText = '-';
    
    $currentClassNum = parseClassToNumber($raceClass);
    
    $validRuns = array_filter($recentRuns, function($run) {
        return isset($run['finishing_position']) && $run['finishing_position'] > 0;
    });
    $validRuns = array_values($validRuns);
    
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
            } else {
                $classChangeText = '<span style="color: #6b7280;">-</span>';
            }
        }
    }
    
    return ['score' => $classChangeScore, 'text' => $classChangeText];
}

/**
 * 計算練馬師場地評分
 */
function calculateTrainerVenueScore($pdo, $trainerName, $venueCode) {
    if (!$trainerName || !$venueCode) return 0;
    
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
        if ($tvWinRate >= 15) return 10;
        if ($tvWinRate >= 12) return 8;
        if ($tvWinRate >= 10) return 6;
        if ($tvWinRate >= 8) return 4;
        if ($tvWinRate >= 5) return 2;
        return 1;
    }
    return 0;
}

/**
 * 計算練馬師近態評分
 */
function calculateTrainerFormScore($pdo, $trainerName) {
    if (!$trainerName) return 0;
    
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
    
    if ($tfStats && $tfStats['rides'] >= 5) {
        $tfWinRate = $tfStats['win_rate'];
        if ($tfWinRate >= 20) return 10;
        if ($tfWinRate >= 15) return 8;
        if ($tfWinRate >= 12) return 6;
        if ($tfWinRate >= 10) return 4;
        if ($tfWinRate >= 8) return 2;
        return 1;
    }
    return 0;
}

/**
 * 解析班次為數字
 */
// function parseClassToNumber($class) {
//     if (empty($class)) return 0;
    
//     if (preg_match('/([一二三四五])班/', $class, $matches)) {
//         $map = ['一' => 1, '二' => 2, '三' => 3, '四' => 4, '五' => 5];
//         return $map[$matches[1]] ?? 0;
//     }
    
//     if (preg_match('/(\d+)/', $class, $matches)) {
//         $num = intval($matches[1]);
//         if ($num >= 1 && $num <= 5) return $num;
//     }
    
//     return 0;
// }

// ========== 資料獲取函數 ==========
// ========== 資料獲取函數（修復版） ==========

/**
 * 獲取馬匹統計資料
 */
function getHorseStats($pdo, $horseCode) {
    $stmt = $pdo->prepare("
        SELECT 
            total_starts as starts,
            total_wins as wins,
            total_seconds as seconds,
            total_thirds as thirds,
            ROUND(IFNULL(total_wins, 0) / NULLIF(total_starts, 0) * 100, 1) as win_rate
        FROM hkracing_horses
        WHERE horse_code = ?
    ");
    $stmt->execute([$horseCode]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $result['starts'] = intval($result['starts']);
        $result['wins'] = intval($result['wins']);
        $result['seconds'] = intval($result['seconds']);
        $result['thirds'] = intval($result['thirds']);
        $result['win_rate'] = floatval($result['win_rate']);
    }
    
    return $result ?: ['starts' => 0, 'wins' => 0, 'seconds' => 0, 'thirds' => 0, 'win_rate' => 0];
}

/**
 * 獲取最近出賽記錄（修復版 - 不使用參數綁定 LIMIT）
 */
function getRecentRuns($pdo, $horseCode, $limit = 3) {
    $limit = intval($limit);
    $sql = "
        SELECT finishing_position, barrier_draw as draw, class, race_date
        FROM hkracing_horse_performances
        WHERE horse_code = ?
        ORDER BY race_date DESC
        LIMIT {$limit}
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$horseCode]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * 獲取賠率資料
 */
function getOddsData($pdo, $date, $venueCode, $raceNo) {
    $oddsMap = [];
    $stmt = $pdo->prepare("
        SELECT horse_code, win_odds as win_curr_odds 
        FROM hkracing_odds_horse_details 
        WHERE race_date = ? AND venue_code = ? AND race_no = ?
    ");
    $stmt->execute([$date, $venueCode, $raceNo]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $oddsMap[trim($row['horse_code'])] = $row['win_curr_odds'];
    }
    return $oddsMap;
}

// ========== 資料庫操作函數 ==========

/**
 * 儲存評分到資料庫
 */
function saveScore($pdo, $raceId, $horseCode, $runnerNo, $scoreResult, $version, $winOdds) {
    $scores = $scoreResult['scores'] ?? [];
    // 添加驗證
    if (empty($raceId) || $raceId == '0') {
        echo "     錯誤: saveScore() 收到無效的 race_id: " . var_export($raceId, true) . "\n";
        return false;
    }
    
    echo "     儲存評分 - race_id: {$raceId}, horse: {$horseCode}\n";
    
    $sql = "
        INSERT INTO hkracing_v2_score (
            race_id, horse_code, runner_no,
            score_win_rate, score_recent_form, score_draw_history,
            score_trainer_jockey, score_distance_venue, score_odds,
            score_class_change, score_trainer_venue, score_trainer_form,
            total_score, prediction_tag, prediction_class, class_change_text,
            win_odds_snapshot, has_odds, score_version, last_updated
        ) VALUES (
            :race_id, :horse_code, :runner_no,
            :score_win_rate, :score_recent_form, :score_draw_history,
            :score_trainer_jockey, :score_distance_venue, :score_odds,
            :score_class_change, :score_trainer_venue, :score_trainer_form,
            :total_score, :prediction_tag, :prediction_class, :class_change_text,
            :win_odds_snapshot, :has_odds, :score_version, NOW()
        ) ON DUPLICATE KEY UPDATE
            score_win_rate = VALUES(score_win_rate),
            score_recent_form = VALUES(score_recent_form),
            score_draw_history = VALUES(score_draw_history),
            score_trainer_jockey = VALUES(score_trainer_jockey),
            score_distance_venue = VALUES(score_distance_venue),
            score_odds = VALUES(score_odds),
            score_class_change = VALUES(score_class_change),
            score_trainer_venue = VALUES(score_trainer_venue),
            score_trainer_form = VALUES(score_trainer_form),
            total_score = VALUES(total_score),
            prediction_tag = VALUES(prediction_tag),
            prediction_class = VALUES(prediction_class),
            class_change_text = VALUES(class_change_text),
            win_odds_snapshot = VALUES(win_odds_snapshot),
            has_odds = VALUES(has_odds),
            last_updated = NOW()
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':race_id' => $raceId,
        ':horse_code' => $horseCode,
        ':runner_no' => $runnerNo,
        ':score_win_rate' => $scores['win_rate'] ?? 0,
        ':score_recent_form' => $scores['recent_form'] ?? 0,
        ':score_draw_history' => $scores['draw_history'] ?? 0,
        ':score_trainer_jockey' => $scores['trainer_jockey'] ?? 0,
        ':score_distance_venue' => $scores['distance_venue'] ?? 0,
        ':score_odds' => $scores['odds'] ?? 0,
        ':score_class_change' => $scores['class_change'] ?? 0,
        ':score_trainer_venue' => $scores['trainer_venue'] ?? 0,
        ':score_trainer_form' => $scores['trainer_form'] ?? 0,
        ':total_score' => $scoreResult['total_score'],
        ':prediction_tag' => $scoreResult['prediction_tag'],
        ':prediction_class' => $scoreResult['prediction_class'],
        ':class_change_text' => $scoreResult['class_change_text'],
        ':win_odds_snapshot' => $winOdds,
        ':has_odds' => $winOdds ? 1 : 0,
        ':score_version' => $version
    ]);
}

/**
 * 從 Version 1 複製評分到 Version 2
 */
function copyScoreFromVersion1($pdo, $raceId, $horseCode) {
    $stmt = $pdo->prepare("
        INSERT INTO hkracing_v2_score (
            race_id, horse_code, runner_no,
            score_win_rate, score_recent_form, score_draw_history,
            score_trainer_jockey, score_distance_venue, score_odds,
            score_class_change, score_trainer_venue, score_trainer_form,
            total_score, prediction_tag, prediction_class, class_change_text,
            win_odds_snapshot, has_odds, score_version, last_updated
        )
        SELECT 
            race_id, horse_code, runner_no,
            score_win_rate, score_recent_form, score_draw_history,
            score_trainer_jockey, score_distance_venue, 0,
            score_class_change, score_trainer_venue, score_trainer_form,
            total_score, prediction_tag, prediction_class, class_change_text,
            NULL, 0, 2, NOW()
        FROM hkracing_v2_score
        WHERE race_id = ? AND horse_code = ? AND score_version = 1
        ON DUPLICATE KEY UPDATE
            total_score = VALUES(total_score),
            last_updated = NOW()
    ");
    $stmt->execute([$raceId, $horseCode]);
}

/**
 * 檢查評分是否已存在
 */
function scoreExists($pdo, $raceId, $horseCode, $version) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM hkracing_v2_score 
        WHERE race_id = ? AND horse_code = ? AND score_version = ?
    ");
    $stmt->execute([$raceId, $horseCode, $version]);
    return $stmt->fetchColumn() > 0;
}

/**
 * 獲取已存在的評分版本資訊
 */
function getExistingScoreVersion($pdo, $raceId, $version) {
    $stmt = $pdo->prepare("
        SELECT last_updated, total_score 
        FROM hkracing_v2_score 
        WHERE race_id = ? AND score_version = ?
        LIMIT 1
    ");
    $stmt->execute([$raceId, $version]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 檢查賽事是否已有 Version 1 評分記錄（完整計算過）
 */
function hasRaceVersion1Score($pdo, $raceId) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM hkracing_v2_score 
        WHERE race_id = ? AND score_version = 1
    ");
    $stmt->execute([$raceId]);  // 確保使用 $raceId，而不是空字符串
    $count = $stmt->fetchColumn();
    
    echo "  [檢查] 賽事 {$raceId} 在 hkracing_v2_score 中有 {$count} 筆 Version 1 記錄\n";
    
    return $count > 0;
}

/**
 * 檢查賽事是否已有 Version 2 評分記錄
 */
function hasRaceVersion2Score($pdo, $raceId) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM hkracing_v2_score 
        WHERE race_id = ? AND score_version = 2
    ");
    $stmt->execute([$raceId]);
    return $stmt->fetchColumn() > 0;
}

/**
 * 獲取賽事最後更新時間（Version 1）
 */
function getRaceLastUpdated($pdo, $raceId, $version) {
    $stmt = $pdo->prepare("
        SELECT MAX(last_updated) as last_updated 
        FROM hkracing_v2_score 
        WHERE race_id = ? AND score_version = ?
    ");
    $stmt->execute([$raceId, $version]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['last_updated'] ?? null;
}

/**
 * 檢查賠率資料可用性
 */
function checkOddsAvailability($pdo, $date, $venueCode, $raceNo) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM hkracing_odds_horse_details 
        WHERE race_date = ? AND venue_code = ? AND race_no = ?
    ");
    $stmt->execute([$date, $venueCode, $raceNo]);
    return intval($stmt->fetchColumn());
}

/**
 * 檢查馬匹資料是否完整
 */
function isAllHorsesFetched($pdo, $raceId) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN horse_code IS NOT NULL AND horse_code != '' THEN 1 ELSE 0 END) as has_code
        FROM hkracing_runners
        WHERE race_id = ?
    ");
    $stmt->execute([$raceId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return ($result['total'] > 0 && $result['total'] == $result['has_code']);
}

/**
 * 檢查賽事是否已結束
 */
// function isRaceFinished($pdo, $raceId) {
//     // 1. 先檢查是否有完賽結果 (final_position 有值)
//     $stmt = $pdo->prepare("
//         SELECT COUNT(*) 
//         FROM hkracing_runners 
//         WHERE race_id = ? AND final_position IS NOT NULL AND final_position > 0
//     ");
//     $stmt->execute([$raceId]);
//     $finishedCount = $stmt->fetchColumn();
    
//     if ($finishedCount > 0) {
//         return true;
//     }
    
//     // 2. 檢查賽事時間是否已過 (只有當賽事時間已過2小時才算結束)
//     $stmt = $pdo->prepare("
//         SELECT m.date, r.post_time 
//         FROM hkracing_races r
//         JOIN hkracing_meetings m ON r.meeting_id = m.id
//         WHERE r.id = ?
//     ");
//     $stmt->execute([$raceId]);
//     $race = $stmt->fetch(PDO::FETCH_ASSOC);
    
//     if ($race && $race['post_time']) {
//         $raceDateTime = strtotime($race['date'] . ' ' . $race['post_time']);
//         $currentTime = time();
        
//         // 只有當賽事時間已過 2 小時，才認為賽事結束
//         // 未來賽事不應該被標記為結束
//         if ($currentTime > $raceDateTime + 7200) {
//             return true;
//         }
//     }
    
//     return false;
// }

/**
 * 標記賽事為已完成
 */
function markRaceAsFinished($pdo, $raceId) {
    $stmt = $pdo->prepare("
        UPDATE hkracing_v2_score 
        SET race_finished = 1, last_updated = NOW()
        WHERE race_id = ?
    ");
    $stmt->execute([$raceId]);
}

//-----------------------------------
// function calculateV2ScoreForCron($horseStats, $recentRuns, $draw, $winOdds, $raceClass, $horseCode, $pdo, $trainerName, $jockeyName, $distance, $venueCode) {
//     // 處理空值
//     $draw = intval($draw);
//     if ($draw <= 0) $draw = 7; // 預設中檔
    
//     $jockeyName = trim($jockeyName);
//     if (empty($jockeyName) || $jockeyName === '---') {
//         $jockeyName = null;
//     }
    
//     $trainerName = trim($trainerName);
//     if (empty($trainerName) || $trainerName === '---') {
//         $trainerName = null;
//     }
    
//     // 各項分數初始化
//     $winRateScore = 0;
//     $recentScore = 0;
//     $drawScore = 0;
//     $trainerJockeyScore = 0;
//     $distanceVenueScore = 0;
//     $oddsScore = 0;
//     $classChangeScore = 0;
//     $trainerVenueScore = 0;
//     $trainerFormScore = 0;
    
//     $starts = intval($horseStats['starts'] ?? 0);
//     $winRate = floatval($horseStats['win_rate'] ?? 0);
    
//     // ========== 1. Win rate scoring (最高30分) ==========
//     if ($starts >= 3) {
//         if ($winRate >= 25) $winRateScore = 30;
//         elseif ($winRate >= 20) $winRateScore = 28;
//         elseif ($winRate >= 15) $winRateScore = 25;
//         elseif ($winRate >= 12) $winRateScore = 22;
//         elseif ($winRate >= 10) $winRateScore = 20;
//         elseif ($winRate >= 8) $winRateScore = 15;
//         elseif ($winRate >= 5) $winRateScore = 10;
//         elseif ($winRate >= 3) $winRateScore = 5;
//     }
    
//     // ========== 2. Recent 3 performances scoring (最高30分) ==========
//     $positionWeights = [50, 30, 20];
//     $validRuns = array_filter($recentRuns, function($run) {
//         return isset($run['finishing_position']) && $run['finishing_position'] > 0;
//     });
//     $validRuns = array_values($validRuns);
    
//     foreach ($validRuns as $index => $run) {
//         $position = intval($run['finishing_position']);
//         $weight = $positionWeights[$index] ?? 20;
//         $points = 0;
        
//         if ($position == 1) $points = 30;
//         elseif ($position == 2) $points = 20;
//         elseif ($position == 3) $points = 15;
//         elseif ($position <= 5) $points = 8;
//         elseif ($position <= 8) $points = 3;
        
//         $recentScore += round(($points * $weight) / 100, 1);
//     }
    
//     // ========== 3. Draw history scoring (最高15分) ==========
//     if ($draw >= 1 && $draw <= 14) {
//         if ($draw <= 3) $drawGroup = '內檔(1-3)';
//         elseif ($draw <= 6) $drawGroup = '中檔(4-6)';
//         else $drawGroup = '外檔(7+)';
        
//         $stmt = $pdo->prepare("
//             SELECT 
//                 COUNT(*) as rides, 
//                 SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
//                 ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate
//             FROM hkracing_horse_performances
//             WHERE horse_code = ? 
//                 AND CASE 
//                     WHEN barrier_draw <= 3 THEN '內檔(1-3)'
//                     WHEN barrier_draw <= 6 THEN '中檔(4-6)'
//                     ELSE '外檔(7+)'
//                 END = ?
//         ");
//         $stmt->execute([$horseCode, $drawGroup]);
//         $drawHistory = $stmt->fetch(PDO::FETCH_ASSOC);
        
//         if ($drawHistory && $drawHistory['rides'] >= 3) {
//             $drawWinRate = $drawHistory['win_rate'];
//             if ($drawWinRate >= 20) $drawScore = 15;
//             elseif ($drawWinRate >= 15) $drawScore = 12;
//             elseif ($drawWinRate >= 10) $drawScore = 8;
//             elseif ($drawWinRate >= 5) $drawScore = 5;
//             else $drawScore = 2;
//         } else {
//             if ($draw <= 3) $drawScore = 5;
//             elseif ($draw <= 6) $drawScore = 3;
//             else $drawScore = 1;
//         }
//     }
    
//     // ========== 4. Trainer + Jockey combination (最高15分) ==========
//     if ($trainerName && $jockeyName) {
//         $stmt = $pdo->prepare("
//             SELECT 
//                 COUNT(*) as rides, 
//                 SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
//                 ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate
//             FROM hkracing_horse_performances
//             WHERE horse_code = ? AND trainer_name_ch = ? AND jockey_name_ch = ?
//         ");
//         $stmt->execute([$horseCode, $trainerName, $jockeyName]);
//         $tjStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
//         if ($tjStats && $tjStats['rides'] >= 3) {
//             $tjWinRate = $tjStats['win_rate'];
//             if ($tjWinRate >= 25) $trainerJockeyScore = 15;
//             elseif ($tjWinRate >= 20) $trainerJockeyScore = 12;
//             elseif ($tjWinRate >= 15) $trainerJockeyScore = 10;
//             elseif ($tjWinRate >= 10) $trainerJockeyScore = 7;
//             elseif ($tjWinRate >= 5) $trainerJockeyScore = 4;
//             else $trainerJockeyScore = 1;
//         }
//     }
    
//     // ========== 5. Distance + venue performance (最高15分) ==========
//     if ($distance && $venueCode) {
//         $stmt = $pdo->prepare("
//             SELECT 
//                 COUNT(*) as rides, 
//                 SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
//                 ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate
//             FROM hkracing_horse_performances
//             WHERE horse_code = ? AND distance = ? AND venue_code = ?
//         ");
//         $stmt->execute([$horseCode, $distance, $venueCode]);
//         $dvStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
//         if ($dvStats && $dvStats['rides'] >= 3) {
//             $dvWinRate = $dvStats['win_rate'];
//             if ($dvWinRate >= 25) $distanceVenueScore = 15;
//             elseif ($dvWinRate >= 20) $distanceVenueScore = 12;
//             elseif ($dvWinRate >= 15) $distanceVenueScore = 10;
//             elseif ($dvWinRate >= 10) $distanceVenueScore = 7;
//             elseif ($dvWinRate >= 5) $distanceVenueScore = 4;
//             else $distanceVenueScore = 1;
//         }
//     }
    
//     // ========== 6. Odds scoring (最高15分) ==========
//     if ($winOdds && $winOdds > 0) {
//         if ($winOdds <= 2.0) $oddsScore = 15;
//         elseif ($winOdds <= 3.0) $oddsScore = 13;
//         elseif ($winOdds <= 4.0) $oddsScore = 11;
//         elseif ($winOdds <= 6.0) $oddsScore = 9;
//         elseif ($winOdds <= 10.0) $oddsScore = 6;
//         elseif ($winOdds <= 15.0) $oddsScore = 4;
//         else $oddsScore = 2;
//     }
    
//     // ========== 7. Class change scoring (±12分) ==========
//     $classChangeText = '-';
//     $currentClassNum = parseClassToNumber($raceClass);
    
//     if (!empty($validRuns)) {
//         $lastRun = $validRuns[0];
//         $lastClass = isset($lastRun['class']) ? (string)$lastRun['class'] : '';
//         $lastClassNum = parseClassToNumber($lastClass);
        
//         if ($lastClassNum > 0 && $currentClassNum > 0) {
//             if ($currentClassNum < $lastClassNum) {
//                 $classChangeScore = -8;
//                 $classChangeText = '<span style="color: #ef4444;">⬆️ 升班</span>';
//             } elseif ($currentClassNum > $lastClassNum) {
//                 $classChangeScore = 12;
//                 $classChangeText = '<span style="color: #10b981;">⬇️ 降班</span>';
//             }
//         }
//     }
    
//     // ========== 8. Trainer venue performance (最高10分) ==========
//     if ($trainerName && $venueCode) {
//         $stmt = $pdo->prepare("
//             SELECT 
//                 COUNT(*) as rides, 
//                 SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
//                 ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate
//             FROM hkracing_horse_performances
//             WHERE trainer_name_ch = ? AND venue_code = ?
//         ");
//         $stmt->execute([$trainerName, $venueCode]);
//         $tvStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
//         if ($tvStats && $tvStats['rides'] >= 10) {
//             $tvWinRate = $tvStats['win_rate'];
//             if ($tvWinRate >= 15) $trainerVenueScore = 10;
//             elseif ($tvWinRate >= 12) $trainerVenueScore = 8;
//             elseif ($tvWinRate >= 10) $trainerVenueScore = 6;
//             elseif ($tvWinRate >= 8) $trainerVenueScore = 4;
//             elseif ($tvWinRate >= 5) $trainerVenueScore = 2;
//             else $trainerVenueScore = 1;
//         }
//     }
    
//     // ========== 9. Trainer current form (最高10分) ==========
//     if ($trainerName) {
//         $stmt = $pdo->prepare("
//             SELECT 
//                 COUNT(*) as rides, 
//                 SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
//                 ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate
//             FROM hkracing_horse_performances
//             WHERE trainer_name_ch = ? AND race_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
//         ");
//         $stmt->execute([$trainerName]);
//         $tfStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
//         $stmt2 = $pdo->prepare("
//             SELECT ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate
//             FROM hkracing_horse_performances
//             WHERE trainer_name_ch = ?
//         ");
//         $stmt2->execute([$trainerName]);
//         $toStats = $stmt2->fetch(PDO::FETCH_ASSOC);
        
//         if ($tfStats && $tfStats['rides'] >= 5) {
//             $tfWinRate = $tfStats['win_rate'];
//             $toWinRate = $toStats['win_rate'] ?? 0;
//             $improvement = $tfWinRate - $toWinRate;
            
//             if ($tfWinRate >= 20) $trainerFormScore = 10;
//             elseif ($tfWinRate >= 15) $trainerFormScore = 8;
//             elseif ($tfWinRate >= 12) $trainerFormScore = 6;
//             elseif ($tfWinRate >= 10) $trainerFormScore = 4;
//             elseif ($tfWinRate >= 8) $trainerFormScore = 2;
//             else $trainerFormScore = 1;
            
//             if ($improvement >= 5) $trainerFormScore += 3;
//             elseif ($improvement >= 3) $trainerFormScore += 2;
//             elseif ($improvement >= 1) $trainerFormScore += 1;
//             elseif ($improvement <= -5) $trainerFormScore -= 2;
//         }
//     }
    
//     // 計算總分
//     $score = $winRateScore + $recentScore + $drawScore + $trainerJockeyScore + 
//              $distanceVenueScore + $oddsScore + $classChangeScore + 
//              $trainerVenueScore + $trainerFormScore;
    
//     // Ensure score is within 0-100 range
//     // $score = min(100, max(0, $score));
    
//     // // Generate prediction tag
//     // if ($score >= 85) {
//     //     $tag = '🔥 熱門首選';
//     //     $tagClass = 'prediction-hot';
//     // } elseif ($score >= 75) {
//     //     $tag = '⭐ 值得關注';
//     //     $tagClass = 'prediction-hot';
//     // } elseif ($score >= 65) {
//     //     $tag = '⚖️ 位置之選';
//     //     $tagClass = 'prediction-equal';
//     // } elseif ($score >= 55) {
//     //     $tag = '📊 冷門配搭';
//     //     $tagClass = 'prediction-equal';
//     // } else {
//     //     $tag = '❄️ 機會渺茫';
//     //     $tagClass = 'prediction-cold';
//     // }
    
//     // // 返回完整結果，包含各項分數
//     // return [
//     //     'score' => round($score),
//     //     'tag' => $tag,
//     //     'tag_class' => $tagClass,
//     //     'class_change_text' => $classChangeText,
//     //     'breakdown' => [
//     //         'win_rate' => $winRateScore,
//     //         'recent_form' => $recentScore,
//     //         'draw_history' => $drawScore,
//     //         'trainer_jockey' => $trainerJockeyScore,
//     //         'distance_venue' => $distanceVenueScore,
//     //         'odds' => $oddsScore,
//     //         'class_change' => $classChangeScore,
//     //         'trainer_venue' => $trainerVenueScore,
//     //         'trainer_form' => $trainerFormScore
//     //     ]
//     // ];
//     // Ensure score is within 0-120 range
//     $score = min(120, max(0, $score));
    
//     // Generate prediction tag (120分制，保持原有百分比)
//     if ($score >= 102) {        // 85% of 120
//         $tag = '🔥 熱門首選';
//         $tagClass = 'prediction-hot';
//     } elseif ($score >= 90) {   // 75% of 120
//         $tag = '⭐ 值得關注';
//         $tagClass = 'prediction-hot';
//     } elseif ($score >= 78) {   // 65% of 120
//         $tag = '⚖️ 位置之選';
//         $tagClass = 'prediction-equal';
//     } elseif ($score >= 66) {   // 55% of 120
//         $tag = '📊 冷門配搭';
//         $tagClass = 'prediction-equal';
//     } else {
//         $tag = '❄️ 機會渺茫';
//         $tagClass = 'prediction-cold';
//     }
    
//     return [
//         'score' => round($score),
//         'tag' => $tag,
//         'tag_class' => $tagClass,
//         'class_change_text' => $classChangeText,
//         'breakdown' => [
//             'win_rate' => $winRateScore,
//             'recent_form' => $recentScore,
//             'draw_history' => $drawScore,
//             'trainer_jockey' => $trainerJockeyScore,
//             'distance_venue' => $distanceVenueScore,
//             'odds' => $oddsScore,
//             'class_change' => $classChangeScore,
//             'trainer_venue' => $trainerVenueScore,
//             'trainer_form' => $trainerFormScore
//         ]
//     ];
// }

?>