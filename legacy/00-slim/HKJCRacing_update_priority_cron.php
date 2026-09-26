<?php
/**
 * 更新選馬優先順序 (Priority)
 * 
 * 執行方式：
 * php HKJCRacing_update_priority_cron.php --type=batch                    # 處理所有未完成的賽事
 * php HKJCRacing_update_priority_cron.php --type=batch --date=2026-04-26  # 處理指定日期
 * php HKJCRacing_update_priority_cron.php --type=batch --race_id=RACE_xxx # 處理指定賽事
 */

include_once ("lib/constants.php");
require_once "HKJCRacing_score_functions.php";

date_default_timezone_set('Asia/Hong_Kong');

// 在文件开头引入统一函数
require_once "HKJCRacing_score_functions.php";

// function updateRacePriority($pdo, $raceId, $date, $venueCode, $raceNo) {
//     // 獲取賽事詳細資訊
//     $stmt = $pdo->prepare("
//         SELECT distance, race_class_en as race_class, go_en, go_ch, class_code
//         FROM hkracing_races
//         WHERE id = ?
//     ");
//     $stmt->execute([$raceId]);
//     $raceDetail = $stmt->fetch(PDO::FETCH_ASSOC);
    
//     if (!$raceDetail) {
//         echo "  无法获取赛事详情\n";
//         return 0;
//     }
    
//     $raceInfo = [
//         'distance' => $raceDetail['distance'],
//         'venue_code' => $venueCode,
//         'go_en' => $raceDetail['go_en'],
//         'go_ch' => $raceDetail['go_ch'],
//         'race_class' => $raceDetail['race_class'],
//         'class_code' => $raceDetail['class_code'],
//         'race_id' => $raceId
//     ];
    
//     // 使用统一的函数计算优先级
//     $priorities = calculateRacePriorities($pdo, $raceId, $raceInfo);
    
//     if (empty($priorities)) {
//         echo "  无法计算优先级\n";
//         return 0;
//     }
    
//     // 更新数据库
//     $updatedCount = updateRacePriorityInDb($pdo, $raceId, $priorities);
    
//     // 输出结果
//     foreach ($priorities as $index => $horse) {
//         $rank = $index + 1;
//         echo "    排名 {$rank}: 馬匹 {$horse['runner_no']} 號 (優先分: {$horse['priority_score']})\n";
//     }
    
//     return $updatedCount;
// }

function updateRacePriority($pdo, $raceId, $date, $venueCode, $raceNo) {
    // 獲取該賽事的所有評分記錄（包含完整數據）
    // $stmt = $pdo->prepare("
    //     SELECT 
    //         s.horse_code,
    //         s.runner_no,
    //         s.total_score as v2_score,
    //         s.win_odds_snapshot as current_odds,
    //         s.score_win_rate,
    //         s.score_recent_form,
    //         s.score_draw_history,
    //         s.score_trainer_jockey,
    //         s.score_distance_venue,
    //         s.score_odds,
    //         s.score_class_change,
    //         s.score_trainer_venue,
    //         s.score_trainer_form,
    //         ru.barrier_draw_number as draw,
    //         ru.name_ch as horse_name,
    //         ru.jockey_name_ch as jockey_name,
    //         ru.trainer_name_ch as trainer_name
    //     FROM hkracing_v2_score s
    //     JOIN hkracing_runners ru ON ru.race_id = s.race_id AND ru.runner_no = s.runner_no
    //     WHERE s.race_id = ? 
    //       AND s.score_version = 2
    // ");
    // $stmt->execute([$raceId]);
    // $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // if (empty($scores)) {
    //     echo "  沒有找到評分記錄\n";
    //     return 0;
    // }
    
        // 先尝试获取 Version 2，如果没有则使用 Version 1
    $stmt = $pdo->prepare("
        SELECT 
            s.horse_code,
            s.runner_no,
            s.total_score as v2_score,
            s.win_odds_snapshot as current_odds,
            s.score_win_rate,
            s.score_recent_form,
            s.score_draw_history,
            s.score_trainer_jockey,
            s.score_distance_venue,
            s.score_odds,
            s.score_class_change,
            s.score_trainer_venue,
            s.score_trainer_form,
            ru.barrier_draw_number as draw,
            ru.name_ch as horse_name,
            ru.jockey_name_ch as jockey_name,
            ru.trainer_name_ch as trainer_name
        FROM hkracing_v2_score s
        JOIN hkracing_runners ru ON ru.race_id = s.race_id AND ru.runner_no = s.runner_no
        WHERE s.race_id = ? 
          AND s.score_version = (SELECT MAX(score_version) FROM hkracing_v2_score WHERE race_id = ?)
    ");
    $stmt->execute([$raceId, $raceId]);
    $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($scores)) {
        echo "  沒有找到評分記錄\n";
        return 0;
    }
    
    // 獲取賽事詳細資訊
    $stmt = $pdo->prepare("
        SELECT distance, race_class_en as race_class, go_en, go_ch, class_code
        FROM hkracing_races
        WHERE id = ?
    ");
    $stmt->execute([$raceId]);
    $raceDetail = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 構建 $horses 陣列（與 analysis_api.php 中的格式一致）
    $horses = [];
    foreach ($scores as $score) {
        // 獲取同程勝率（用于计算）
        $distanceWinRate = 0;
        $stmtDist = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins
            FROM hkracing_horse_performances
            WHERE horse_code = ? AND distance = ? AND venue_code = ?
                AND finishing_position IS NOT NULL AND finishing_position > 0
        ");
        $stmtDist->execute([$score['horse_code'], $raceDetail['distance'], $venueCode]);
        $distStats = $stmtDist->fetch(PDO::FETCH_ASSOC);
        $distanceWinRate = ($distStats['total'] > 0) ? ($distStats['wins'] / $distStats['total'] * 100) : 0;
        
        // 獲取同檔上名率
        $drawTop3Rate = 0;
        $draw = intval($score['draw']);
        if ($draw > 0) {
            $stmtDraw = $pdo->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) as top3
                FROM hkracing_horse_performances
                WHERE horse_code = ? AND barrier_draw = ?
                    AND finishing_position IS NOT NULL AND finishing_position > 0
            ");
            $stmtDraw->execute([$score['horse_code'], $draw]);
            $drawStats = $stmtDraw->fetch(PDO::FETCH_ASSOC);
            $drawTop3Rate = ($drawStats['total'] > 0) ? ($drawStats['top3'] / $drawStats['total'] * 100) : 0;
        }
        
        $horses[] = [
            'runner_no' => $score['runner_no'],
            'horse_name' => $score['horse_name'],
            'draw' => $draw,
            'current_win_odds' => $score['current_odds'],
            'v2_score' => $score['v2_score'],
            'win_rate' => $score['score_win_rate'],
            'distance_win_rate' => $distanceWinRate,
            'draw_top3_rate' => $drawTop3Rate,
            'horse_code' => $score['horse_code']
        ];
    }
    
    // 構建 $race 陣列（與 analysis_api.php 中的格式一致）
    $race = [
        'venue_code' => $venueCode,
        'distance' => $raceDetail['distance'],
        'race_class' => $raceDetail['race_class'],
        'go_en' => $raceDetail['go_en'],
        'go_ch' => $raceDetail['go_ch'],
        'class_code' => $raceDetail['class_code'],
        'id' => $raceId
    ];
    
    // ✅ 直接使用 analysis_api.php 中的 calculateSelectionPriority 函數
    // 注意：這個函數需要在當前文件中可用
    // 方法1：require_once "analysis_api.php"（但可能會有衝突）
    // 方法2：將 calculateSelectionPriority 複製到這個文件中
    
    // 為了避免衝突，我們直接複製 calculateSelectionPriority 的核心邏輯過來
    $priorities = calculatePriorityFromHorses($horses, $race, $pdo);
    
    // 更新資料庫中的 priority 字段
    // $updatedCount = 0;
    // foreach ($priorities as $index => $priority) {
    //     $rank = $index + 1;
    //     $stmt = $pdo->prepare("
    //         UPDATE hkracing_v2_score 
    //         SET priority = :priority, last_updated = NOW()
    //         WHERE race_id = :race_id AND runner_no = :runner_no AND score_version = 2
    //     ");
    //     $stmt->execute([
    //         ':priority' => $rank,
    //         ':race_id' => $raceId,
    //         ':runner_no' => $priority['runner_no']
    //     ]);
    //     $updatedCount++;
    //     echo "    排名 {$rank}: 馬匹 {$priority['runner_no']} 號 (優先分: {$priority['priority_score']})\n";
    // }
    // 更新資料庫中的 priority 和 blog_priority_score 字段
    $updatedCount = 0;
    foreach ($priorities as $index => $priority) {
        $rank = $index + 1;
        $blogPriorityScore = $priority['priority_score'];  // 使用计算出的优先分数
        
        $stmt = $pdo->prepare("
            UPDATE hkracing_v2_score 
            SET priority = :priority, 
                blog_score = :blog_score,
                blog_priority_score = :blog_priority_score,
                last_updated = NOW()
            WHERE race_id = :race_id AND runner_no = :runner_no AND score_version = 2
        ");
        $stmt->execute([
            ':priority' => $rank,
            ':blog_score' => $blogPriorityScore,
            ':blog_priority_score' => $blogPriorityScore,
            ':race_id' => $raceId,
            ':runner_no' => $priority['runner_no']
        ]);
        $updatedCount++;
        echo "    排名 {$rank}: 馬匹 {$priority['runner_no']} 號 (優先分: {$blogPriorityScore})\n";
    }
    
    return $updatedCount;
}

/**
 * 複製自 analysis_api.php 的 calculateSelectionPriority 核心邏輯
 */
// function calculatePriorityFromHorses($horses, $race, $pdo) {
//     $raceInfo = [
//         'distance' => $race['distance'] ?? 0,
//         'venue_code' => $race['venue_code'] ?? '',
//         'go_en' => $race['go_en'] ?? '',
//         'go_ch' => $race['go_ch'] ?? '',
//         'race_class' => $race['race_class'] ?? '',
//         'class_code' => $race['class_code'] ?? '',
//         'race_id' => $race['id'] ?? ''
//     ];
    
//     $priorities = [];
    
//     foreach ($horses as $horse) {
//         $horseData = [
//             'horse_code' => $horse['horse_code'],
//             'draw' => $horse['draw'],
//             'current_odds' => floatval($horse['current_win_odds'] ?? 0),
//             'v2_score' => $horse['v2_score'] ?? 50,
//             'runner_no' => $horse['runner_no'],
//             'horse_name' => $horse['horse_name']
//         ];
        
//         // 使用統一的優先級計算函數
//         $priorityResult = calculateUnifiedPriority($horseData, $raceInfo, $pdo);
        
//         $priorities[] = [
//             'runner_no' => $horse['runner_no'],
//             'horse_name' => $horse['horse_name'],
//             'draw' => $horse['draw'],
//             'current_odds' => $horseData['current_odds'],
//             'v2_score' => $horseData['v2_score'],
//             'history_score' => $priorityResult['history_score'],
//             'priority_score' => $priorityResult['priority_score'],
//             'distance_win_rate' => $priorityResult['distance_win_rate'],
//             'draw_top3_rate' => $priorityResult['draw_top3_rate'],
//             'recent_form' => $priorityResult['recent_form'],
//             'suggestion' => $priorityResult['suggestion']
//         ];
//     }
    
//     // 按優先分數排序（降序）
//     usort($priorities, function($a, $b) {
//         return $b['priority_score'] <=> $a['priority_score'];
//     });
    
//     return $priorities;
// }

function calculatePriorityFromHorses($horses, $race, $pdo) {
    $raceInfo = [
        'distance' => $race['distance'] ?? 0,
        'venue_code' => $race['venue_code'] ?? '',
        'go_en' => $race['go_en'] ?? '',
        'go_ch' => $race['go_ch'] ?? '',
        'race_class' => $race['race_class'] ?? '',
        'class_code' => $race['class_code'] ?? '',
        'race_id' => $race['id'] ?? ''
    ];
    
    $priorities = [];
    
    foreach ($horses as $horse) {
        $horseData = [
            'horse_code' => $horse['horse_code'],
            'draw' => $horse['draw'],
            'current_odds' => floatval($horse['current_win_odds'] ?? 0),
            'v2_score' => $horse['v2_score'] ?? 50,
            'runner_no' => $horse['runner_no'],
            'horse_name' => $horse['horse_name'],
            'jockey_name' => $horse['jockey_name'] ?? '',
            'trainer_name' => $horse['trainer_name'] ?? '',
            'gear' => $horse['gear'] ?? '',
            'weight' => $horse['weight'] ?? 0
        ];
        
        // 使用統一的優先級計算函數
        $priorityResult = calculateUnifiedPriority($horseData, $raceInfo, $pdo);
        
        $priorities[] = [
            'runner_no' => $horse['runner_no'],
            'horse_name' => $horse['horse_name'],
            'draw' => $horse['draw'],
            'current_odds' => $horseData['current_odds'],
            'v2_score' => $horseData['v2_score'],
            'history_score' => $priorityResult['history_score'],
            'priority_score' => $priorityResult['priority_score'],  // 这是 blog_priority_score
            'distance_win_rate' => $priorityResult['distance_win_rate'],
            'draw_top3_rate' => $priorityResult['draw_top3_rate'],
            'recent_form' => $priorityResult['recent_form'],
            'suggestion' => $priorityResult['suggestion']
        ];
    }
    
    // 按優先分數排序（降序）
    usort($priorities, function($a, $b) {
        return $b['priority_score'] <=> $a['priority_score'];
    });
    
    return $priorities;
}

// 允許 Web 執行
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

// 同时支持 Web 和 CLI 的参数解析
$type = 'batch';
$raceId = null;
$date = null;
$venue = '';
$force = false;

if (php_sapi_name() === 'cli') {
    // 命令行模式
    $options = getopt("", ["type::", "race_id::", "date::", "venue::", "force"]);
    $type = $options['type'] ?? 'batch';
    $raceId = isset($options['race_id']) ? $options['race_id'] : null;
    $date = $options['date'] ?? null;
    $venue = $options['venue'] ?? '';
    $force = isset($options['force']) ? true : false;
} else {
    // Web 模式 - 从 $_GET 获取参数
    $type = isset($_GET['type']) ? $_GET['type'] : 'batch';
    $raceId = isset($_GET['race_id']) ? $_GET['race_id'] : null;
    $date = isset($_GET['date']) ? $_GET['date'] : null;
    $venue = isset($_GET['venue']) ? $_GET['venue'] : '';
    $force = isset($_GET['force']) ? true : false;
}

// 添加调试输出
echo "=== 参数信息 ===\n";
echo "运行模式: " . (php_sapi_name() === 'cli' ? 'CLI' : 'Web') . "\n";
echo "type: {$type}\n";
echo "force: " . ($force ? '是' : '否') . "\n";
echo "date: " . ($date ?: '自动') . "\n";
echo "venue: " . ($venue ?: '全部') . "\n";
echo "================\n\n";

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '+08:00'");
    
    if ($raceId) {
        processSingleRacePriority($pdo, $raceId, $force);
    } else {
        processBatchPriority($pdo, $date, $venue, $force);
    }
    
    echo "\n[" . date('Y-m-d H:i:s') . "] 優先順序更新完成\n";
    
} catch (Exception $e) {
    echo "錯誤: " . $e->getMessage() . "\n";
    error_log("Priority Update Error: " . $e->getMessage());
    exit(1);
}

/**
 * 批次處理優先順序
 */
function processBatchPriority($pdo, $date, $venue, $force = false) {
    echo "=== 批次更新選馬優先順序 ===\n";
    echo "日期: " . ($date ?: '今天及未來7天') . "\n";
    echo "馬場: " . ($venue ?: '全部') . "\n";
    echo "強制更新: " . ($force ? '是' : '否') . "\n\n";
    
    // 獲取需要處理的賽事
    $sql = "
        SELECT DISTINCT r.id, r.race_no, m.date, m.venue_code, 
               COUNT(DISTINCT ru.horse_code) as horse_count
        FROM hkracing_races r
        JOIN hkracing_meetings m ON r.meeting_id = m.id
        JOIN hkracing_runners ru ON ru.race_id = r.id
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
    
    // 关键修改：只有当 force = false 时才跳过已有 priority 的赛事
    if (!$force) {
        $sql .= " AND NOT EXISTS (
            SELECT 1 FROM hkracing_v2_score s 
            WHERE s.race_id = r.id AND s.priority IS NOT NULL
        )";
    } else {
        echo "⚠️ 强制模式：将更新所有赛事的 priority\n";
    }
    
    $sql .= " GROUP BY r.id ORDER BY m.date, r.race_no";
    
    echo "SQL: " . $sql . "\n\n";
    
    $stmt = $pdo->prepare($sql);
    $params = [];
    if ($date) $params[':date'] = $date;
    if ($venue) $params[':venue'] = $venue;
    $stmt->execute($params);
    $races = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "找到 " . count($races) . " 場賽事需要更新優先順序\n\n";
    
    $updated = 0;
    foreach ($races as $race) {
        echo "處理: {$race['date']} - {$race['venue_code']} 第{$race['race_no']}場\n";
        
        try {
            $priorityCount = updateRacePriority($pdo, $race['id'], $race['date'], $race['venue_code'], $race['race_no']);
            echo "  ✅ 更新了 {$priorityCount} 匹馬的優先順序\n";
            $updated++;
        } catch (Exception $e) {
            echo "  ❌ 錯誤: " . $e->getMessage() . "\n";
        }
        
        usleep(100000);
    }
    
    echo "\n完成！更新了 {$updated} 場賽事\n";
}

/**
 * 處理單場賽事的優先順序
 */
function processSingleRacePriority($pdo, $raceId, $force = false) {
    echo "處理單場賽事 ID: {$raceId}\n";
    
    // 獲取賽事資訊
    $stmt = $pdo->prepare("
        SELECT r.id, r.race_no, m.date, m.venue_code
        FROM hkracing_races r
        JOIN hkracing_meetings m ON r.meeting_id = m.id
        WHERE r.id = ?
    ");
    $stmt->execute([$raceId]);
    $race = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$race) {
        throw new Exception("賽事不存在: {$raceId}");
    }
    
    return updateRacePriority($pdo, $raceId, $race['date'], $race['venue_code'], $race['race_no']);
}

/**
 * 更新一場賽事所有馬匹的優先順序
 */
/**
 * 更新一場賽事所有馬匹的優先順序
 */
// function updateRacePriority($pdo, $raceId, $date, $venueCode, $raceNo) {
//     // 獲取該賽事的所有評分記錄
//     $stmt = $pdo->prepare("
//         SELECT 
//             s.horse_code,
//             s.runner_no,
//             s.total_score,
//             s.win_odds_snapshot as current_odds,
//             ru.barrier_draw_number as draw,
//             ru.name_ch as horse_name
//         FROM hkracing_v2_score s
//         JOIN hkracing_runners ru ON ru.race_id = s.race_id AND ru.runner_no = s.runner_no
//         WHERE s.race_id = ? 
//           AND s.score_version = 2
//     ");
//     $stmt->execute([$raceId]);
//     $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
//     if (empty($scores)) {
//         echo "  沒有找到評分記錄\n";
//         return 0;
//     }
    
//     // 獲取賽事詳細資訊
//     $stmt = $pdo->prepare("
//         SELECT distance, race_class_en as race_class, go_en, go_ch
//         FROM hkracing_races
//         WHERE id = ?
//     ");
//     $stmt->execute([$raceId]);
//     $raceDetail = $stmt->fetch(PDO::FETCH_ASSOC);
    
//     // 構建馬匹數據數組（與 calculateSelectionPriority 需要的格式一致）
//     $horsesForPriority = [];
//     foreach ($scores as $score) {
//         $horseCode = $score['horse_code'];
//         $draw = intval($score['draw']);
        
//         // 獲取同程勝率
//         $distanceWinRate = 0;
//         $stmt = $pdo->prepare("
//             SELECT 
//                 COUNT(*) as total,
//                 SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins
//             FROM hkracing_horse_performances
//             WHERE horse_code = ? 
//                 AND distance = ?
//                 AND venue_code = ?
//                 AND finishing_position IS NOT NULL
//                 AND finishing_position > 0
//         ");
//         $stmt->execute([$horseCode, $raceDetail['distance'], $venueCode]);
//         $distStats = $stmt->fetch(PDO::FETCH_ASSOC);
//         $distanceWinRate = ($distStats['total'] > 0) ? ($distStats['wins'] / $distStats['total'] * 100) : 0;
        
//         // 獲取同檔上名率
//         $drawTop3Rate = 0;
//         if ($draw > 0) {
//             $stmt = $pdo->prepare("
//                 SELECT 
//                     COUNT(*) as total,
//                     SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) as top3
//                 FROM hkracing_horse_performances
//                 WHERE horse_code = ? AND barrier_draw = ?
//                     AND finishing_position IS NOT NULL
//                     AND finishing_position > 0
//             ");
//             $stmt->execute([$horseCode, $draw]);
//             $drawStats = $stmt->fetch(PDO::FETCH_ASSOC);
//             $drawTop3Rate = ($drawStats['total'] > 0) ? ($drawStats['top3'] / $drawStats['total'] * 100) : 0;
//         }
        
//         // 獲取場地狀況適性分數
//         $goingScore = 10; // 默認分
//         $going = $raceDetail['go_en'] ?? $raceDetail['go_ch'] ?? '';
//         if (!empty($going)) {
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
//             $goingStats = $stmt->fetch(PDO::FETCH_ASSOC);
//             $goingTotal = intval($goingStats['total'] ?? 0);
//             $goingWins = intval($goingStats['wins'] ?? 0);
//             $goingTop3 = intval($goingStats['top3'] ?? 0);
//             $goingWinRate = $goingTotal > 0 ? ($goingWins / $goingTotal * 100) : 0;
//             $goingTop3Rate = $goingTotal > 0 ? ($goingTop3 / $goingTotal * 100) : 0;
//             $goingScore = min(20, ($goingWinRate * 0.8) + ($goingTop3Rate * 0.2));
//         }
        
//         // 獲取近5場狀態分數
//         $recentScore = 0;
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
        
//         // 獲取班次能力分數
//         $classCode = $raceDetail['class_code'] ?? '';
//         $classScore = 7;
//         if (!empty($classCode)) {
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
//             $classStats = $stmt->fetch(PDO::FETCH_ASSOC);
//             $classTotal = intval($classStats['total'] ?? 0);
//             $classWins = intval($classStats['wins'] ?? 0);
//             $classWinRate = $classTotal > 0 ? ($classWins / $classTotal * 100) : 0;
//             $classScore = min(15, $classWinRate);
//         }
        
//         $horsesForPriority[] = [
//             'runner_no' => $score['runner_no'],
//             'horse_name' => $score['horse_name'],
//             'horse_code' => $horseCode,
//             'draw' => $draw,
//             'current_odds' => floatval($score['current_odds'] ?? 0),
//             'v2_score' => $score['total_score'],
//             'distance_win_rate' => $distanceWinRate,
//             'draw_top3_rate' => $drawTop3Rate,
//             'going_score' => $goingScore,
//             'recent_score' => $recentScore,
//             'class_score' => $classScore
//         ];
//     }
    
//     // 使用與 calculateSelectionPriority 相同的邏輯計算優先分數
//     $priorities = [];
//     foreach ($horsesForPriority as $horse) {
//         // 1. 同程勝率 (權重 30%)
//         $distanceScore = min(30, $horse['distance_win_rate'] * 1.2);
        
//         // 2. 場地狀況適性 (權重 20%)
//         $goingScore = $horse['going_score'];
        
//         // 3. 同檔位表現 (權重 15%)
//         $drawScore = 0;
//         $draw = $horse['draw'];
//         $drawTop3Rate = $horse['draw_top3_rate'];
//         if ($draw <= 3) $drawScore = 8;
//         elseif ($draw <= 6) $drawScore = 5;
//         else $drawScore = 3;
//         if ($drawTop3Rate >= 50) $drawScore += 7;
//         elseif ($drawTop3Rate >= 30) $drawScore += 4;
//         elseif ($drawTop3Rate >= 20) $drawScore += 2;
//         $drawScore = min(15, $drawScore);
        
//         // 4. 班次能力 (權重 15%)
//         $classScore = $horse['class_score'];
        
//         // 5. 近況狀態 (權重 20%)
//         $recentScore = $horse['recent_score'];
        
//         // 6. 賠率調整 (±10)
//         $oddsBonus = 0;
//         $currentOdds = $horse['current_odds'];
//         if ($currentOdds > 0) {
//             if ($currentOdds <= 2.5) $oddsBonus = 12;
//             elseif ($currentOdds <= 4) $oddsBonus = 8;
//             elseif ($currentOdds <= 6) $oddsBonus = 4;
//             elseif ($currentOdds <= 10) $oddsBonus = 0;
//             elseif ($currentOdds <= 20) $oddsBonus = -3;
//             else $oddsBonus = -6;
//         }
        
//         // 計算歷史條件總分
//         $historyScore = $distanceScore + $goingScore + $drawScore + $classScore + $recentScore + $oddsBonus;
//         $historyScore = max(0, min(100, $historyScore));
        
//         // 最終優先分數 = 歷史條件分(40%) + 綜合評分(60%)
//         $finalPriority = ($historyScore * 0.4) + ($horse['v2_score'] * 0.6);
        
//         $priorities[] = [
//             'runner_no' => $horse['runner_no'],
//             'priority_score' => round($finalPriority, 1),
//             'horse_code' => $horse['horse_code']
//         ];
//     }
    
//     // 按優先分數排序
//     usort($priorities, function($a, $b) {
//         return $b['priority_score'] <=> $a['priority_score'];
//     });
    
//     // 更新資料庫中的 priority 字段
//     $updatedCount = 0;
//     foreach ($priorities as $index => $priority) {
//         $rank = $index + 1;
//         $stmt = $pdo->prepare("
//             UPDATE hkracing_v2_score 
//             SET priority = :priority, last_updated = NOW()
//             WHERE race_id = :race_id AND runner_no = :runner_no AND score_version = 2
//         ");
//         $stmt->execute([
//             ':priority' => $rank,
//             ':race_id' => $raceId,
//             ':runner_no' => $priority['runner_no']
//         ]);
//         $updatedCount++;
//         echo "    排名 {$rank}: 馬匹 {$priority['runner_no']} 號 (優先分: {$priority['priority_score']})\n";
//     }
    
//     return $updatedCount;
// }
/**
 * 計算歷史條件分數
 */
function calculateHistoryScore($horse, $raceInfo) {
    $distanceWinRate = $horse['distance_win_rate'];
    $drawTop3Rate = $horse['draw_top3_rate'];
    $currentOdds = $horse['current_odds'];
    
    // 同程勝率評分 (最高30分)
    $distanceScore = min(30, $distanceWinRate * 1.2);
    
    // 檔位表現評分 (最高15分)
    $drawScore = 0;
    $draw = intval($horse['draw']);
    if ($draw <= 3) $drawScore = 8;
    elseif ($draw <= 6) $drawScore = 5;
    else $drawScore = 3;
    
    // 加上上名率加成
    if ($drawTop3Rate >= 50) $drawScore += 7;
    elseif ($drawTop3Rate >= 30) $drawScore += 4;
    elseif ($drawTop3Rate >= 20) $drawScore += 2;
    $drawScore = min(15, $drawScore);
    
    // 賠率調整 (±10)
    $oddsBonus = 0;
    if ($currentOdds > 0) {
        if ($currentOdds <= 3) $oddsBonus = 10;
        elseif ($currentOdds <= 5) $oddsBonus = 5;
        elseif ($currentOdds <= 10) $oddsBonus = 0;
        elseif ($currentOdds <= 20) $oddsBonus = -3;
        else $oddsBonus = -6;
    }
    
    // 基礎分 + 賠率調整
    $totalScore = $distanceScore + $drawScore + $oddsBonus;
    
    return max(0, min(100, $totalScore));
}

// 輔助函數：解析班次
// function parseClassToNumber($class) {
//     if (empty($class)) return 0;
//     if (preg_match('/(\d+)/', $class, $matches)) {
//         return intval($matches[1]);
//     }
//     return 0;
// }
?>