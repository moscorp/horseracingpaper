<?php
// analysis_api.php - V2 完整版 (保留所有原有功能)

include_once ("lib/constants.php");
require_once "HKJCRacing_score_functions.php";

date_default_timezone_set('Asia/Hong_Kong');

$action = $_GET['action'] ?? '';

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '+08:00'");
    
    switch ($action) {
        case 'dataStatus':
            header('Content-Type: application/json; charset=utf-8');
            getDataStatus($pdo);
            break;
        case 'dashboard':
        case 'horses':
        case 'track':
        case 'odds':
        case 'upcoming':
            header('Content-Type: text/html; charset=utf-8');
            renderComponent($action, $pdo);
            break;
        case 'getRaces':
            header('Content-Type: application/json; charset=utf-8');
            getRaces($pdo);
            break;
        case 'raceDetail':
            header('Content-Type: text/html; charset=utf-8');
            echo getRaceDetail($pdo);
            break;
        case 'trainers':
        case 'jockeys':
        case 'jockey_trainer':
        case 'jockey_barrier':
        case 'prediction':
            header('Content-Type: text/html; charset=utf-8');
            renderComponent($action, $pdo);
            break;
        case 'trackFilter':
            header('Content-Type: application/json; charset=utf-8');
            getTrackFilter($pdo);
            break;
        case 'jockeyTrainerData':
            header('Content-Type: application/json; charset=utf-8');
            getJockeyTrainerData($pdo);
            break;
        case 'jockeyBarrierData':
            header('Content-Type: application/json; charset=utf-8');
            getJockeyBarrierData($pdo);
            break;
        case 'predictionCalculate':
            header('Content-Type: application/json; charset=utf-8');
            getPredictionCalculate($pdo);
            break;
        case 'predictionStats':
            header('Content-Type: application/json; charset=utf-8');
            getPredictionStats($pdo);
            break;
        case 'oddsChartData':
            header('Content-Type: application/json; charset=utf-8');
            getOddsChartData($pdo);
            break;            
        case 'generatePoster':
            require_once 'HKJC_meetingposter.php';
            $poster = new HKJCRacingPoster();
            $files = $poster->generateAllPosters($_GET['date'], $_GET['venue']);
            echo json_encode(['success' => true, 'files' => $files]);
            break;
        default:
            $componentActions = ['dashboard', 'horses', 'track', 'odds', 'upcoming', 'trainers', 'jockeys', 'jockey_trainer', 'jockey_barrier'];
            if (in_array($action, $componentActions)) {
                header('Content-Type: text/html; charset=utf-8');
                renderComponent($action, $pdo);
            } else {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => '无效的 action: ' . $action]);
            }
            break;
    }
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// ========== renderComponent 函數 ==========
function renderComponent($component, $pdo) {
    ob_start();
    include "analysis_{$component}.php";
    $html = ob_get_clean();
    echo $html;
}

// ========== getDataStatus 函數 ==========
function getDataStatus($pdo) {
    $stmt = $pdo->query("
        SELECT 
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM hkracing_history_queue
    ");
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("
        SELECT COUNT(*) as pending_incremental
        FROM hkracing_incremental_queue
        WHERE status = 'pending'
    ");
    $incResult = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT horse_code) as total
        FROM hkracing_runners
        WHERE horse_code REGEXP '^[A-Z][0-9]{3}$'
    ");
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("
        SELECT COUNT(*) as synced
        FROM hkracing_horses
        WHERE horse_code REGEXP '^[A-Z][0-9]{3}$'
          AND last_sync_date IS NOT NULL
    ");
    $synced = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'pending_count' => (int)($result['pending'] ?? 0),
            'processing_count' => (int)($result['processing'] ?? 0),
            'completed_count' => (int)($result['completed'] ?? 0),
            'pending_incremental' => (int)($incResult['pending_incremental'] ?? 0),
            // 'total_horses' => (int)($result['pending'] ?? 0) + (int)($result['processing'] ?? 0) + (int)($result['completed'] ?? 0),
            'total_horses' => (int)($total['total'] ?? 0),
            'synced_horses' => (int)($synced['synced'] ?? 0)
        ]
    ]);
}

// ========== getRaces 函數 ==========
function getRaces($pdo) {
    $date = $_GET['date'] ?? '';
    $venue = $_GET['venue'] ?? '';
    
    if ($date && $venue) {
        $stmt = $pdo->prepare("
            SELECT race_no FROM hkracing_races r
            JOIN hkracing_meetings m ON r.meeting_id = m.id
            WHERE m.date = ? AND m.venue_code = ?
            ORDER BY race_no
        ");
        $stmt->execute([$date, $venue]);
        $races = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'races' => $races]);
    } else {
        echo json_encode(['success' => false, 'races' => []]);
    }
}

// ========== trackFilter 函數 ==========
function getTrackFilter($pdo) {
    $venue = $_GET['venue'] ?? '';
    $course = $_GET['course'] ?? '';
    $distance = $_GET['distance'] ?? '';
    
    $sql = "
        SELECT 
            venue_code,
            course,
            distance,
            CASE 
                WHEN barrier_draw <= 3 THEN '內檔'
                WHEN barrier_draw BETWEEN 4 AND 6 THEN '中檔'
                WHEN barrier_draw >= 7 THEN '外檔'
            END as barrier_group,
            COUNT(*) as runs,
            SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
            SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) as top3,
            ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate,
            ROUND(SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as top3_rate
        FROM hkracing_horse_performances
        WHERE finishing_position IS NOT NULL 
          AND finishing_position > 0
          AND course IS NOT NULL
          AND course != ''
          AND barrier_draw IS NOT NULL
    ";
    
    $params = [];
    if ($venue) {
        $sql .= " AND venue_code = :venue";
        $params[':venue'] = $venue;
    }
    if ($course) {
        $sql .= " AND course = :course";
        $params[':course'] = $course;
    }
    if ($distance) {
        $sql .= " AND distance = :distance";
        $params[':distance'] = $distance;
    }
    
    $sql .= " GROUP BY venue_code, course, distance, barrier_group
              HAVING runs >= 20
              ORDER BY venue_code, course, distance, 
                CASE barrier_group
                    WHEN '內檔' THEN 1
                    WHEN '中檔' THEN 2
                    WHEN '外檔' THEN 3
                END
              LIMIT 200";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $venueNames = ['ST' => '沙田', 'HV' => '跑馬地', 'S1' => '悉尼', 'S2' => '墨爾本', 'S3' => '布里斯班'];
    
    $result = [];
    foreach ($data as $row) {
        $result[] = [
            'venue_name' => $venueNames[$row['venue_code']] ?? $row['venue_code'],
            'course_short' => str_replace(['跑馬地草地', '沙田草地'], ['谷草', '田草'], $row['course']),
            'distance' => $row['distance'],
            'barrier_group' => $row['barrier_group'],
            'runs' => (int)$row['runs'],
            'win_rate' => (float)$row['win_rate'],
            'top3_rate' => (float)$row['top3_rate']
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $result]);
}

// ========== getJockeyTrainerData 函數 ==========
function getJockeyTrainerData($pdo) {
    $jockey = $_GET['jockey'] ?? '';
    $trainer = $_GET['trainer'] ?? '';
    
    try {
        $statsSql = "
            SELECT 
                COUNT(DISTINCT CONCAT(jockey_name_ch, '|', trainer_name_ch)) as total_pairs,
                SUM(collab) as total_collabs
            FROM (
                SELECT 
                    jockey_name_ch,
                    trainer_name_ch,
                    COUNT(*) as collab
                FROM hkracing_horse_performances
                WHERE jockey_name_ch IS NOT NULL AND jockey_name_ch != ''
                  AND trainer_name_ch IS NOT NULL AND trainer_name_ch != ''
                  AND finishing_position IS NOT NULL AND finishing_position > 0
        ";
        
        if ($jockey) $statsSql .= " AND jockey_name_ch = :jockey";
        if ($trainer) $statsSql .= " AND trainer_name_ch = :trainer";
        
        $statsSql .= " GROUP BY jockey_name_ch, trainer_name_ch
                       HAVING collab >= 3
            ) t";
        
        $statsStmt = $pdo->prepare($statsSql);
        $statsParams = [];
        if ($jockey) $statsParams[':jockey'] = $jockey;
        if ($trainer) $statsParams[':trainer'] = $trainer;
        $statsStmt->execute($statsParams);
        $statsRow = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        $winRateSql = "
            SELECT 
                jockey_name_ch,
                trainer_name_ch,
                COUNT(*) as collaborations,
                SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) as top3,
                ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate,
                ROUND(SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as top3_rate
            FROM hkracing_horse_performances
            WHERE jockey_name_ch IS NOT NULL AND jockey_name_ch != ''
              AND trainer_name_ch IS NOT NULL AND trainer_name_ch != ''
              AND finishing_position IS NOT NULL AND finishing_position > 0
        ";
        
        if ($jockey) $winRateSql .= " AND jockey_name_ch = :jockey";
        if ($trainer) $winRateSql .= " AND trainer_name_ch = :trainer";
        
        $winRateSql .= " GROUP BY jockey_name_ch, trainer_name_ch
                         HAVING collaborations >= 3
                         ORDER BY win_rate DESC
                         LIMIT 100";
        
        $winRateStmt = $pdo->prepare($winRateSql);
        $winRateStmt->execute($statsParams);
        $winRatePairs = $winRateStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $topWinsSql = "
            SELECT 
                jockey_name_ch,
                trainer_name_ch,
                COUNT(*) as collaborations,
                SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
                ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate
            FROM hkracing_horse_performances
            WHERE jockey_name_ch IS NOT NULL AND jockey_name_ch != ''
              AND trainer_name_ch IS NOT NULL AND trainer_name_ch != ''
              AND finishing_position IS NOT NULL AND finishing_position > 0
        ";
        
        if ($jockey) $topWinsSql .= " AND jockey_name_ch = :jockey2";
        if ($trainer) $topWinsSql .= " AND trainer_name_ch = :trainer2";
        
        $topWinsSql .= " GROUP BY jockey_name_ch, trainer_name_ch
                         HAVING collaborations >= 3
                         ORDER BY wins DESC
                         LIMIT 20";
        
        $topWinsStmt = $pdo->prepare($topWinsSql);
        $topWinsParams = [];
        if ($jockey) $topWinsParams[':jockey2'] = $jockey;
        if ($trainer) $topWinsParams[':trainer2'] = $trainer;
        $topWinsStmt->execute($topWinsParams);
        $topWinsPairs = $topWinsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $trainerBestSql = "
            SELECT 
                trainer_name_ch,
                jockey_name_ch,
                COUNT(*) as collaborations,
                SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
                ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate
            FROM hkracing_horse_performances
            WHERE trainer_name_ch IS NOT NULL
              AND jockey_name_ch IS NOT NULL
              AND finishing_position IS NOT NULL AND finishing_position > 0
        ";
        
        if ($jockey) $trainerBestSql .= " AND jockey_name_ch = :jockey3";
        if ($trainer) $trainerBestSql .= " AND trainer_name_ch = :trainer3";
        
        $trainerBestSql .= " GROUP BY trainer_name_ch, jockey_name_ch
                             HAVING collaborations >= 3
                             ORDER BY wins DESC
                             LIMIT 30";
        
        $trainerBestStmt = $pdo->prepare($trainerBestSql);
        $trainerBestParams = [];
        if ($jockey) $trainerBestParams[':jockey3'] = $jockey;
        if ($trainer) $trainerBestParams[':trainer3'] = $trainer;
        $trainerBestStmt->execute($trainerBestParams);
        $trainerBestPairs = $trainerBestStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'totalPairs' => (int)($statsRow['total_pairs'] ?? 0),
                'totalCollabs' => (int)($statsRow['total_collabs'] ?? 0)
            ],
            'winRatePairs' => $winRatePairs,
            'topWinsPairs' => $topWinsPairs,
            'trainerBestPairs' => $trainerBestPairs
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// ========== getJockeyBarrierData 函數 ==========
function getJockeyBarrierData($pdo) {
    $jockey = $_GET['jockey'] ?? '';
    
    $sql = "
        SELECT 
            jockey_name_ch,
            CASE 
                WHEN barrier_draw <= 3 THEN '內檔(1-3)'
                WHEN barrier_draw BETWEEN 4 AND 6 THEN '中檔(4-6)'
                WHEN barrier_draw >= 7 THEN '外檔(7+)'
            END as barrier_group,
            COUNT(*) as rides,
            SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
            SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) as top3,
            ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate,
            ROUND(SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as top3_rate
        FROM hkracing_horse_performances
        WHERE jockey_name_ch IS NOT NULL 
          AND jockey_name_ch != ''
          AND barrier_draw IS NOT NULL
          AND finishing_position IS NOT NULL
          AND finishing_position > 0
    ";
    
    $params = [];
    if ($jockey) {
        $sql .= " AND jockey_name_ch = :jockey";
        $params[':jockey'] = $jockey;
    }
    
    $sql .= " GROUP BY jockey_name_ch, barrier_group
              HAVING rides >= 10
              ORDER BY jockey_name_ch, 
                CASE barrier_group
                    WHEN '內檔(1-3)' THEN 1
                    WHEN '中檔(4-6)' THEN 2
                    WHEN '外檔(7+)' THEN 3
                END";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $bestBarrier = [];
    foreach ($data as $item) {
        $j = $item['jockey_name_ch'];
        if (!isset($bestBarrier[$j]) || $item['win_rate'] > $bestBarrier[$j]['win_rate']) {
            $bestBarrier[$j] = $item;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $data, 'bestBarrier' => $bestBarrier]);
}

// ========== getPredictionCalculate 函數 ==========
function getPredictionCalculate($pdo) {
    $distance = $_GET['distance'] ?? '';
    $barrier = $_GET['barrier'] ?? '';
    $jockey = $_GET['jockey'] ?? '';
    $trainer = $_GET['trainer'] ?? '';
    $oddsRange = $_GET['odds_range'] ?? '';
    
    $factors = [];
    $totalImpact = 0;
    $baseWinRate = 8.5;
    
    if (!empty($distance)) {
        $stmt = $pdo->prepare("
            SELECT 
                ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate,
                COUNT(*) as runs
            FROM hkracing_horse_performances
            WHERE distance = ? AND finishing_position IS NOT NULL AND finishing_position > 0
        ");
        $stmt->execute([$distance]);
        $distStat = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($distStat && $distStat['runs'] > 0) {
            $impact = round($distStat['win_rate'] - $baseWinRate, 1);
            $factors[] = ['name' => '途程', 'value' => $distance . 'm', 'impact' => $impact];
            $totalImpact += $impact;
        }
    }
    
    if (!empty($barrier)) {
        $barrierRange = explode('-', $barrier);
        $minBarrier = intval($barrierRange[0]);
        $maxBarrier = intval($barrierRange[1]);
        
        $stmt = $pdo->prepare("
            SELECT 
                ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate,
                COUNT(*) as runs
            FROM hkracing_horse_performances
            WHERE barrier_draw BETWEEN ? AND ? 
              AND finishing_position IS NOT NULL AND finishing_position > 0
        ");
        $stmt->execute([$minBarrier, $maxBarrier]);
        $barrierStat = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($barrierStat && $barrierStat['runs'] > 0) {
            $impact = round($barrierStat['win_rate'] - $baseWinRate, 1);
            $factors[] = ['name' => '檔位', 'value' => $barrier . '檔', 'impact' => $impact];
            $totalImpact += $impact;
        }
    }
    
    if (!empty($jockey)) {
        $stmt = $pdo->prepare("
            SELECT 
                ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate,
                COUNT(*) as rides
            FROM hkracing_horse_performances
            WHERE jockey_name_ch = ? AND finishing_position IS NOT NULL AND finishing_position > 0
        ");
        $stmt->execute([$jockey]);
        $jockeyStat = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($jockeyStat && $jockeyStat['rides'] > 0) {
            $impact = round($jockeyStat['win_rate'] - $baseWinRate, 1);
            $factors[] = ['name' => '騎師', 'value' => $jockey, 'impact' => $impact];
            $totalImpact += $impact;
        }
    }
    
    if (!empty($trainer)) {
        $stmt = $pdo->prepare("
            SELECT 
                ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate,
                COUNT(*) as runs
            FROM hkracing_horse_performances
            WHERE trainer_name_ch = ? AND finishing_position IS NOT NULL AND finishing_position > 0
        ");
        $stmt->execute([$trainer]);
        $trainerStat = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($trainerStat && $trainerStat['runs'] > 0) {
            $impact = round($trainerStat['win_rate'] - $baseWinRate, 1);
            $factors[] = ['name' => '練馬師', 'value' => $trainer, 'impact' => $impact];
            $totalImpact += $impact;
        }
    }
    
    if (!empty($oddsRange)) {
        $oddsMap = ['< 2.0' => [0, 2], '2.0-2.9' => [2, 3], '3.0-4.9' => [3, 5], '5.0-9.9' => [5, 10], '≥ 10.0' => [10, 100]];
        
        // 賠率評分對照表 (最高15分)
        $oddsScoreTable = [
            '< 2.0'   => 15,
            '2.0-2.9' => 14,
            '3.0-4.9' => 12,
            '5.0-9.9' => 8,
            '≥ 10.0'  => 3,
        ];
        
        if (isset($oddsMap[$oddsRange])) {
            $minOdds = $oddsMap[$oddsRange][0];
            $maxOdds = $oddsMap[$oddsRange][1];
            $stmt = $pdo->prepare("
                SELECT 
                    ROUND(SUM(CASE WHEN final_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate,
                    COUNT(*) as runs
                FROM hkracing_runners
                WHERE win_odds >= ? AND win_odds < ? AND final_position > 0
            ");
            $stmt->execute([$minOdds, $maxOdds]);
            $oddsStat = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($oddsStat && $oddsStat['runs'] > 0) {
                $impact = round($oddsStat['win_rate'] - $baseWinRate, 1);
                
                // 賠率評分 (基於區間，最高15分)
                $oddsScore = $oddsScoreTable[$oddsRange] ?? 0;
                
                $factors[] = [
                    'name' => '賠率', 
                    'value' => $oddsRange, 
                    'impact' => $impact,
                    'score' => $oddsScore,      // 新增：評分
                    'max_score' => 15,           // 新增：滿分
                    'win_rate' => $oddsStat['win_rate'],  // 新增：實際勝率
                    'runs' => $oddsStat['runs']
                ];
                $totalImpact += $impact;
                $totalScore += $oddsScore;      // 新增：累計評分
            }
        }
    }
    
    $predictedWinRate = round($baseWinRate + $totalImpact, 1);
    $predictedWinRate = max(2, min(35, $predictedWinRate));
    $confidence = min(95, 50 + count($factors) * 10);
    $sampleSize = max(50, count($factors) * 50);
    
    $advice = '';
    if ($predictedWinRate >= 15) {
        $advice = '此組合勝率較高，值得重點關注！';
    } else if ($predictedWinRate >= 10) {
        $advice = '此組合表現不錯，可作為參考。';
    } else if ($predictedWinRate >= 5) {
        $advice = '此組合勝率一般，建議結合其他因素分析。';
    } else {
        $advice = '此組合勝率偏低，需謹慎考慮。';
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'predicted_win_rate' => $predictedWinRate,
            'confidence' => $confidence,
            'sample_size' => $sampleSize,
            'factors' => $factors,
            'advice' => $advice
        ]
    ]);
}

// ========== getPredictionStats 函數 ==========
function getPredictionStats($pdo) {
    $currentYear = date('Y');
    $currentMonth = date('n');
    if ($currentMonth >= 9) {
        $racingSeasonStart = $currentYear;
        $racingSeasonEnd = $currentYear + 1;
    } else {
        $racingSeasonStart = $currentYear - 1;
        $racingSeasonEnd = $currentYear;
    }
    $seasonStartDate = "{$racingSeasonStart}-09-01";
    $seasonLabel = "{$racingSeasonStart}/{$racingSeasonEnd}";
    
    $stmt = $pdo->query("
        SELECT 
            distance,
            COUNT(*) as runs,
            SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
            ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate,
            ROUND(SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as place_rate
        FROM hkracing_horse_performances
        WHERE finishing_position IS NOT NULL AND finishing_position > 0 AND distance IS NOT NULL
        GROUP BY distance
        HAVING runs >= 30
        ORDER BY win_rate DESC
    ");
    $distanceAllStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("
        SELECT 
            distance,
            COUNT(*) as runs,
            SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
            ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate,
            ROUND(SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as place_rate
        FROM hkracing_horse_performances
        WHERE finishing_position IS NOT NULL AND finishing_position > 0 AND distance IS NOT NULL
            AND race_date >= :season_start
        GROUP BY distance
        HAVING runs >= 10
        ORDER BY win_rate DESC
    ");
    $stmt->execute([':season_start' => $seasonStartDate]);
    $distanceSeasonStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $barrierQuery = "
        SELECT 
            CASE 
                WHEN barrier_draw BETWEEN 1 AND 3 THEN '1-3'
                WHEN barrier_draw BETWEEN 4 AND 6 THEN '4-6'
                WHEN barrier_draw BETWEEN 7 AND 9 THEN '7-9'
                WHEN barrier_draw >= 10 THEN '10+'
            END as barrier_range,
            COUNT(*) as runs,
            SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
            ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate,
            ROUND(SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as place_rate
        FROM hkracing_horse_performances
        WHERE barrier_draw IS NOT NULL AND finishing_position IS NOT NULL AND finishing_position > 0
    ";
    
    $stmt = $pdo->query($barrierQuery . " GROUP BY barrier_range HAVING runs >= 30 ORDER BY win_rate DESC");
    $barrierAllStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare($barrierQuery . " AND race_date >= :season_start GROUP BY barrier_range HAVING runs >= 10 ORDER BY win_rate DESC");
    $stmt->execute([':season_start' => $seasonStartDate]);
    $barrierSeasonStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'seasonLabel' => $seasonLabel,
            'distanceAllStats' => $distanceAllStats,
            'distanceSeasonStats' => $distanceSeasonStats,
            'barrierAllStats' => $barrierAllStats,
            'barrierSeasonStats' => $barrierSeasonStats
        ]
    ]);
}

// ========== V2 核心函數 ==========
function safeFloat($value, $default = 0) {
    if (is_null($value) || $value === '') return $default;
    $float = floatval($value);
    return is_numeric($float) ? $float : $default;
}

function safeInt($value, $default = 0) {
    if (is_null($value) || $value === '') return $default;
    $int = intval($value);
    return is_numeric($int) ? $int : $default;
}

/**
 * 分析马匹跑法类型
 * @param string $runningPosition 沿途走位，格式如 "2 2 6" 或 "9 10 12 13"
 * @return array 跑法类型和图标
 */
function analyzeRunningStyle($runningPosition) {
    if (empty($runningPosition)) {
        return ['type' => 'unknown', 'icon' => '❓', 'text' => '未知'];
    }
    
    // 解析位置
    $positions = preg_split('/\s+/', trim($runningPosition));
    if (count($positions) < 2) {
        return ['type' => 'unknown', 'icon' => '❓', 'text' => '數據不足'];
    }
    
    // 取第一个位置（起步/转弯前）和最后一个位置（终点）
    $firstPos = intval($positions[0]);
    $lastPos = intval($positions[count($positions) - 1]);
    
    // 判断跑法
    // 放头马：起步位置 <= 3，且终点位置 <= 3
    if ($firstPos <= 3 && $lastPos <= 3) {
        return ['type' => 'leader', 'icon' => '🏃‍♂️', 'text' => '放頭馬'];
    }
    // 跟前马：起步位置 4-6，且终点位置 <= 6
    elseif ($firstPos <= 6 && $lastPos <= 6) {
        return ['type' => 'prominent', 'icon' => '🏃', 'text' => '跟前馬'];
    }
    // 居中后上马：起步位置 4-9，终点位置有明显改善（比起步位置好）
    elseif ($firstPos >= 4 && $firstPos <= 9 && $lastPos < $firstPos && $lastPos <= 6) {
        return ['type' => 'midfielder', 'icon' => '🚶‍♂️💨', 'text' => '居中後上'];
    }
    // 居中马：起步位置 7-9
    elseif ($firstPos >= 7 && $firstPos <= 9) {
        return ['type' => 'midfielder', 'icon' => '🚶‍♂️', 'text' => '居中馬'];
    }
    // 后上马：起步位置 >= 10
    elseif ($firstPos >= 10) {
        return ['type' => 'closer', 'icon' => '🏃‍♂️💨', 'text' => '後上馬'];
    }
    
    return ['type' => 'average', 'icon' => '⚖️', 'text' => '均速馬'];
}

/**
 * 获取马匹最近3场的跑法分析（取最多出现的类型）
 * @param array $recentRuns 最近出赛记录（需包含 running_position）
 * @return array 主要跑法类型和图标
 */
function getHorseRunningStyle($recentRuns) {
    if (empty($recentRuns)) {
        return ['type' => 'unknown', 'icon' => '❓', 'text' => '未知', 'detail' => ''];
    }
    
    $styles = [];
    $validRuns = 0;
    
    foreach ($recentRuns as $run) {
        if (!empty($run['running_position'])) {
            $style = analyzeRunningStyle($run['running_position']);
            $styles[] = $style['type'];
            $validRuns++;
        }
    }
    
    if ($validRuns == 0) {
        return ['type' => 'unknown', 'icon' => '❓', 'text' => '未知', 'detail' => ''];
    }
    
    // 统计最常出现的跑法
    $counts = array_count_values($styles);
    arsort($counts);
    $mainType = key($counts);
    
    // 获取对应的图标和文字
    $styleMap = [
        'leader' => ['icon' => '🏃‍♂️', 'text' => '放頭馬'],
        'prominent' => ['icon' => '🏃', 'text' => '跟前馬'],
        'midfielder' => ['icon' => '🚶‍♂️', 'text' => '居中馬'],
        'closer' => ['icon' => '🏃‍♂️💨', 'text' => '後上馬'],
        'average' => ['icon' => '⚖️', 'text' => '均速馬'],
        'unknown' => ['icon' => '❓', 'text' => '未知']
    ];
    
    $result = $styleMap[$mainType] ?? ['icon' => '❓', 'text' => '未知'];
    
    // 构建详细显示（最近3场的跑法图标）
    $detailIcons = [];
    $displayCount = 0;
    foreach ($recentRuns as $run) {
        if (!empty($run['running_position']) && $displayCount < 3) {
            $style = analyzeRunningStyle($run['running_position']);
            $detailIcons[] = $style['icon'];
            $displayCount++;
        }
    }
    $detail = !empty($detailIcons) ? ' (' . implode(' ', $detailIcons) . ')' : '';
    
    return [
        'type' => $mainType,
        'icon' => $result['icon'],
        'text' => $result['text'],
        'detail' => $detail
    ];
}

/**
 * 获取班次变化符号（基于最近一场的 class 和当前 class）
 * @param string|int $currentClass 当前班次
 * @param string|int $lastClass 上次班次
 * @return string 上升/下降符号
 */
function getClassChangeSymbol($currentClass, $lastClass) {
    if (empty($lastClass) || empty($currentClass)) {
        return '';
    }
    
    $currentNum = intval($currentClass);
    $lastNum = intval($lastClass);
    
    if ($currentNum == 0 || $lastNum == 0) {
        return '';
    }
    
    if ($currentNum < $lastNum) {
        // 升班（数字变小，难度增加）
        return '<span class="class-up-symbol">⬆️'. '</span>';   //升' . ($lastNum - $currentNum) . '</span>';
    } elseif ($currentNum > $lastNum) {
        // 降班（数字变大，难度降低）
        return '<span class="class-down-symbol">⬇️'. '</span>'; //降' . ($currentNum - $lastNum) . '</span>';
    }
    
    return '';
}

/**
 * 解析班次字符串为数字
 */
// function parseClassToNumber($class) {
//     if (empty($class)) {
//         return 0;
//     }
    
//     // 处理 "Class 4", "Class 3" 格式
//     if (preg_match('/Class\s+(\d+)/i', $class, $matches)) {
//         return intval($matches[1]);
//     }
    
//     // 处理 "第四班", "第三班" 格式
//     if (preg_match('/([一二三四五])班/', $class, $matches)) {
//         $map = ['一' => 1, '二' => 2, '三' => 3, '四' => 4, '五' => 5];
//         return $map[$matches[1]] ?? 0;
//     }
    
//     // 处理纯数字
//     if (is_numeric($class)) {
//         return intval($class);
//     }
    
//     return 0;
// }
//----------------------------------------------------------------------------------------------------------------- 
/**
 * 基于历史数据计算选马优先顺序
 * @param array $horses 马匹数据数组（包含v2_score, win_rate, current_win_odds等）
 * @param array $race 赛事条件（venue, course, distance, going, class等）
 * @param PDO $pdo 数据库连接
 * @return array 按优先顺序排序的马匹列表，附带建议
 */
function calculateSelectionPriority($horses, $race, $pdo) {
    // 如果没有数据库连接或不是香港赛事，返回基于现有评分的简单排序
    if ($pdo === null) {
        $simplePriorities = [];
        foreach ($horses as $horse) {
            $horseNameClean = preg_replace('/<[^>]*>/', '', $horse['horse_name_display']);
            $horseNameClean = str_replace('❤️', '', $horseNameClean);
            $horseNameClean = trim($horseNameClean);
            
            $simplePriorities[] = [
                'runner_no' => $horse['runner_no'],
                'horse_name' => $horseNameClean,
                'draw' => $horse['draw'],
                'current_odds' => floatval($horse['current_win_odds'] ?? 0),
                'v2_score' => $horse['v2_score'] ?? 50,
                'history_score' => 0,
                'priority_score' => $horse['v2_score'] ?? 50,
                'distance_win_rate' => 0,
                'draw_top3_rate' => 0,
                'recent_form' => 'N/A',
                'suggestion' => '🌏 海外賽事，以綜合評分參考',
                'breakdown' => $priorityResult['breakdown']  // 添加这一行
            ];
        }
        
        usort($simplePriorities, function($a, $b) {
            return $b['priority_score'] <=> $a['priority_score'];
        });
        
        return $simplePriorities;
    }
    
    // 构建 raceInfo
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
        // 提取纯马名
        $horseNameClean = preg_replace('/<[^>]*>/', '', $horse['horse_name_display']);
        $horseNameClean = str_replace('❤️', '', $horseNameClean);
        $horseNameClean = trim($horseNameClean);
        
        // 获取马匹代码
        $horseCode = $horse['horse_code'] ?? '';
        
        if (empty($horseCode) && !empty($horseNameClean)) {
            try {
                $stmtCode = $pdo->prepare("
                    SELECT horse_code 
                    FROM hkracing_runners 
                    WHERE name_ch = ? 
                    LIMIT 1
                ");
                $stmtCode->execute([$horseNameClean]);
                $codeRow = $stmtCode->fetch(PDO::FETCH_ASSOC);
                if ($codeRow) {
                    $horseCode = $codeRow['horse_code'];
                }
            } catch (Exception $e) {
                error_log("Error fetching horse code: " . $e->getMessage());
            }
        }
        
        if (empty($horseCode)) {
            // 无法获取马匹代码，使用默认值
            $priorities[] = [
                'runner_no' => $horse['runner_no'],
                'horse_name' => $horseNameClean,
                'draw' => $horse['draw'],
                'current_odds' => floatval($horse['current_win_odds'] ?? 0),
                'v2_score' => $horse['v2_score'] ?? 50,
                'history_score' => 0,
                'priority_score' => ($horse['v2_score'] ?? 50),
                'distance_win_rate' => 0,
                'draw_top3_rate' => 0,
                'recent_form' => 'N/A',
                'suggestion' => '📋 歷史數據不足，基於綜合評分參考'
            ];
            continue;
        }
        
        $horseData = [
            'horse_code' => $horseCode,
            'draw' => $horse['draw'],
            'current_odds' => floatval($horse['current_win_odds'] ?? 0),
            'v2_score' => $horse['v2_score'] ?? 50,
            'runner_no' => $horse['runner_no'],
            'horse_name' => $horseNameClean
        ];
        
        // 使用统一的优先级计算函数
        $priorityResult = calculateUnifiedPriority($horseData, $raceInfo, $pdo);
        
        $priorities[] = [
            'runner_no' => $horse['runner_no'],
            'horse_name' => $horseNameClean,
            'draw' => $horse['draw'],
            'current_odds' => $horseData['current_odds'],
            'v2_score' => $horseData['v2_score'],
            'history_score' => $priorityResult['history_score'],
            'priority_score' => $priorityResult['priority_score'],
            'distance_win_rate' => $priorityResult['distance_win_rate'],
            'draw_top3_rate' => $priorityResult['draw_top3_rate'],
            'recent_form' => $priorityResult['recent_form'],
            'suggestion' => $priorityResult['suggestion']
        ];
    }
    
    // 按优先分数排序（降序）
    usort($priorities, function($a, $b) {
        return $b['priority_score'] <=> $a['priority_score'];
    });
    
    return $priorities;
}

/**
 * 根据分数和条件生成建议文字
 */
function getSuggestionText($score, $odds, $distanceWinRate, $drawTop3Rate, $goingWinRate = 0) {
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
    if ($odds <= 4 && $odds > 0) $reasons[] = "📈 熱門追捧";
    if ($odds >= 15) $reasons[] = "❄️ 冷門分子";
    
    $reasonText = !empty($reasons) ? ' [' . implode('] [', $reasons) . ']' : '';
    
    return $base . $reasonText;
}
/**
//  * 根据分数和条件生成建议文字
//  */
// function getSuggestionText($score, $odds, $distanceWinRate, $drawTop3Rate) {
//     if ($score >= 70) {
//         $base = '🔥 优先首选 - ';
//     } elseif ($score >= 55) {
//         $base = '⭐ 重点关注 - ';
//     } elseif ($score >= 40) {
//         $base = '📌 可以考虑 - ';
//     } else {
//         $base = '⚠️ 谨慎参与 - ';
//     }
    
//     $reasons = [];
//     if ($distanceWinRate >= 20) $reasons[] = "同程胜率高({$distanceWinRate}%)";
//     if ($drawTop3Rate >= 40) $reasons[] = "同档上名率高({$drawTop3Rate}%)";
//     if ($odds <= 5) $reasons[] = "热门赔率({$odds}倍)";
//     if ($odds >= 15) $reasons[] = "冷门赔率";
    
//     $reasonText = !empty($reasons) ? ' (' . implode(', ', $reasons) . ')' : '';
    
//     return $base . $reasonText;
// }
//-----------------------------------------------------------------------------------------------------------------
/**
 * Get race detail with full analysis for a specific race
 * @param PDO $pdo Database connection
 * @return string HTML output for race analysis
 */
function getRaceDetail($pdo) {
    $date = $_GET['date'] ?? '';
    $venue = $_GET['venue'] ?? '';
    $raceNo = $_GET['race_no'] ?? '';
    
    if (empty($date) || empty($venue) || empty($raceNo)) {
        return '<div class="loading">❌ 請選擇完整的賽事信息</div>';
    }
    
    // Get race information
    $stmt = $pdo->prepare("
        SELECT 
            r.*, 
            r.id as race_id,
            m.venue_code, 
            m.date,
            r.race_name_ch as race_name,
            r.distance,
            r.post_time as race_time,
            SUBSTRING(r.race_class_en,-1) as race_class,
            r.go_en,
            r.go_ch,
            r.course_en,
            r.course_ch
        FROM hkracing_races r
        JOIN hkracing_meetings m ON r.meeting_id = m.id
        WHERE m.date = ? AND m.venue_code = ? AND r.race_no = ?
    ");
    $stmt->execute([$date, $venue, $raceNo]);
    $race = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 如果没有 go_en/go_ch，尝试从 hkracing_horse_performances 获取该赛事日期的常见场地状况
    if (empty($race['go_en']) && empty($race['go_ch'])) {
        // 获取同一天其他场次的场地状况作为参考
        $stmtGoing = $pdo->prepare("
            SELECT DISTINCT go_en, go_ch
            FROM hkracing_races r2
            JOIN hkracing_meetings m2 ON r2.meeting_id = m2.id
            WHERE m2.date = ? AND (go_en IS NOT NULL OR go_ch IS NOT NULL)
            LIMIT 1
        ");
        $stmtGoing->execute([$date]);
        $goingRef = $stmtGoing->fetch(PDO::FETCH_ASSOC);
        if ($goingRef) {
            $race['go_en'] = $goingRef['go_en'];
            $race['go_ch'] = $goingRef['go_ch'];
        }
    }

    if (!$race) {
        return '<div class="loading">❌ 賽事資料不存在</div>';
    }
    
    $isHongKongRace = in_array($race['venue_code'], ['ST', 'HV']);
    
    // Get runners
    $stmt = $pdo->prepare("
        SELECT 
            ru.runner_no,
            ru.horse_code,
            ru.name_ch as horse_name,
            ru.name_en as horse_name_en,
            ru.jockey_name_ch as jockey_name,
            ru.trainer_name_ch as trainer_name,
            ru.barrier_draw_number as draw,
            ru.handicap_weight as weight,
            ru.current_rating as rating
        FROM hkracing_runners ru
        JOIN hkracing_races r ON ru.race_id = r.id
        WHERE r.id = (
            SELECT id FROM hkracing_races 
            WHERE meeting_id = (SELECT id FROM hkracing_meetings WHERE date = ? AND venue_code = ?)
            AND race_no = ?
        )
        ORDER BY CAST(ru.runner_no AS UNSIGNED)
    ");
    $stmt->execute([$date, $venue, $raceNo]);
    $runners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ========== 1. 获取今日赛事的当前赔率（从 hkracing_odds_horse_details） ==========
    $currentOddsMap = [];
    $stmtOdds = $pdo->prepare("
        SELECT 
            runner_no,
            win_odds as current_win_odds,
            is_hot_favourite
        FROM hkracing_odds_horse_details 
        WHERE race_date = ? AND venue_code = ? AND race_no = ? AND odds_type = 'Curr'
        ORDER BY captured_at DESC
        /*LIMIT 1*/
    ");
    $stmtOdds->execute([$date, $venue, $raceNo]);
    while ($row = $stmtOdds->fetch(PDO::FETCH_ASSOC)) {
        $currentOddsMap[$row['runner_no']] = $row;
    }
    
    // ========== 2. 获取赔率跌幅（从 oddsDrop 表，取每个马匹最新的 oddsDropValue） ==========
    $oddsDropValueMap = [];
    foreach ($runners as $runner) {
        $horseCode = trim($runner['horse_code']);
        if (!empty($horseCode)) {
            $stmtDrop = $pdo->prepare("
                SELECT oddsDropValue
                FROM oddsDrop
                WHERE horsecode = ?
                ORDER BY date DESC
                LIMIT 1
            ");
            $stmtDrop->execute([$horseCode]);
            $dropRow = $stmtDrop->fetch(PDO::FETCH_ASSOC);
            
            if ($dropRow && $dropRow['oddsDropValue'] !== null) {
                $oddsDropValueMap[$horseCode] = $dropRow['oddsDropValue'];
            }
        }
    }
    
    // ========== 3. 获取每匹马上一次出赛的赔率（从 hkracing_horse_performances） ==========
    $prevOddsMap = [];
    foreach ($runners as $runner) {
        $horseCode = trim($runner['horse_code']);
        $runnerNo = $runner['runner_no'];
        
        $stmtPrev = $pdo->prepare("
            SELECT win_odds
            FROM hkracing_horse_performances
            WHERE horse_code = ?
              AND win_odds IS NOT NULL
              AND win_odds > 0
            ORDER BY race_date DESC
            LIMIT 1
        ");
        $stmtPrev->execute([$horseCode]);
        $prevRow = $stmtPrev->fetch(PDO::FETCH_ASSOC);
        
        if ($prevRow && $prevRow['win_odds'] > 0) {
            $prevOddsMap[$runnerNo] = $prevRow['win_odds'];
        }
    }
    // ========== 3. 获取每匹马上一次出赛的赔率、骑师、练马师和名次 ==========
    $prevOddsMap = [];
    $prevJockeyMap = [];
    $prevTrainerMap = [];
    $prevFinalPositionMap = [];
    
    foreach ($runners as $runner) {
        $horseCode = trim($runner['horse_code']);
        $runnerNo = $runner['runner_no'];
        
        // 获取上次赔率
        $stmtPrev = $pdo->prepare("
            SELECT win_odds
            FROM hkracing_horse_performances
            WHERE horse_code = ?
              AND win_odds IS NOT NULL
              AND win_odds > 0
            ORDER BY race_date DESC
            LIMIT 1
        ");
        $stmtPrev->execute([$horseCode]);
        $prevRow = $stmtPrev->fetch(PDO::FETCH_ASSOC);
        
        if ($prevRow && $prevRow['win_odds'] > 0) {
            $prevOddsMap[$runnerNo] = $prevRow['win_odds'];
        }
        
        // 获取上一次出赛的骑师、练马师和名次
        $stmtPrevDetails = $pdo->prepare("
            SELECT 
                jockey_name_ch,
                trainer_name_ch,
                finishing_position
            FROM hkracing_horse_performances
            WHERE horse_code = ?
              AND finishing_position IS NOT NULL
            ORDER BY race_date DESC
            LIMIT 1
        ");
        $stmtPrevDetails->execute([$horseCode]);
        $prevDetails = $stmtPrevDetails->fetch(PDO::FETCH_ASSOC);
        
        if ($prevDetails) {
            if ($prevDetails['jockey_name_ch']) {
                $prevJockeyMap[$runnerNo] = $prevDetails['jockey_name_ch'];
            }
            if ($prevDetails['trainer_name_ch']) {
                $prevTrainerMap[$runnerNo] = $prevDetails['trainer_name_ch'];
            }
            if ($prevDetails['finishing_position']) {
                $prevFinalPositionMap[$runnerNo] = $prevDetails['finishing_position'];
            }
        }
    }
    
    // ========== 4. 获取每匹马在不同档位的历史表现 ==========
    $horseDrawStats = [];
    $stmtDraw = $pdo->prepare("
        SELECT 
            horse_code,
            barrier_draw,
            COUNT(*) as total_runs,
            SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
            SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) as top3
        FROM hkracing_horse_performances
        WHERE finishing_position IS NOT NULL AND finishing_position > 0
          AND barrier_draw IS NOT NULL
        GROUP BY horse_code, barrier_draw
    ");
    $stmtDraw->execute();
    while ($row = $stmtDraw->fetch(PDO::FETCH_ASSOC)) {
        $key = $row['horse_code'] . '_' . $row['barrier_draw'];
        $horseDrawStats[$key] = [
            'runs' => $row['total_runs'],
            'wins' => $row['wins'],
            'top3' => $row['top3'],
            'win_rate' => $row['total_runs'] > 0 ? round($row['wins'] / $row['total_runs'] * 100, 1) : 0,
            'top3_rate' => $row['total_runs'] > 0 ? round($row['top3'] / $row['total_runs'] * 100, 1) : 0
        ];
    }
    
    // ========== 5. 计算练马师+骑师组合的出赛数量变化 ==========
    $currentDate = $date;
    
    $stmtPrevDate = $pdo->prepare("
        SELECT DISTINCT date
        FROM hkracing_meetings 
        WHERE date < ? AND venue_code = ?
        ORDER BY date DESC
        LIMIT 1
    ");
    $stmtPrevDate->execute([$currentDate, $venue]);
    $prevDateRow = $stmtPrevDate->fetch(PDO::FETCH_ASSOC);
    $prevDate = $prevDateRow ? $prevDateRow['date'] : null;
    
    // 统计当前赛马日每个骑练组合的出赛数量
    $currentJockeyTrainerCounts = [];
    $stmtCurrent = $pdo->prepare("
        SELECT 
            CONCAT(ru.jockey_name_ch, '|', ru.trainer_name_ch) as combo,
            COUNT(*) as cnt
        FROM hkracing_runners ru
        JOIN hkracing_races r ON ru.race_id = r.id
        JOIN hkracing_meetings m ON r.meeting_id = m.id
        WHERE m.date = ? AND m.venue_code = ?
        GROUP BY combo
    ");
    $stmtCurrent->execute([$currentDate, $venue]);
    while ($row = $stmtCurrent->fetch(PDO::FETCH_ASSOC)) {
        $currentJockeyTrainerCounts[$row['combo']] = $row['cnt'];
    }
    
    // 统计上一次赛马日每个骑练组合的出赛数量
    $prevJockeyTrainerCounts = [];
    if ($prevDate) {
        $stmtPrev = $pdo->prepare("
            SELECT 
                CONCAT(ru.jockey_name_ch, '|', ru.trainer_name_ch) as combo,
                COUNT(*) as cnt
            FROM hkracing_runners ru
            JOIN hkracing_races r ON ru.race_id = r.id
            JOIN hkracing_meetings m ON r.meeting_id = m.id
            WHERE m.date = ? AND m.venue_code = ?
            GROUP BY combo
        ");
        $stmtPrev->execute([$prevDate, $venue]);
        while ($row = $stmtPrev->fetch(PDO::FETCH_ASSOC)) {
            $prevJockeyTrainerCounts[$row['combo']] = $row['cnt'];
        }
    }
    
    // ========== 6. 获取练马师当日马匹数量 ==========
    $stmtTrainer = $pdo->prepare("
        SELECT trainer_name_ch, COUNT(*) as horse_count
        FROM hkracing_runners ru
        JOIN hkracing_races r ON ru.race_id = r.id
        JOIN hkracing_meetings m ON r.meeting_id = m.id
        WHERE m.date = ? AND m.venue_code = ?
        GROUP BY trainer_name_ch
    ");
    $stmtTrainer->execute([$date, $venue]);
    $trainerCounts = [];
    while ($row = $stmtTrainer->fetch(PDO::FETCH_ASSOC)) {
        $trainerCounts[$row['trainer_name_ch']] = $row['horse_count'];
    }
    
    // ========== 7. 获取骑师当日马匹数量 ==========
    $stmtJockey = $pdo->prepare("
        SELECT jockey_name_ch, COUNT(*) as horse_count
        FROM hkracing_runners ru
        JOIN hkracing_races r ON ru.race_id = r.id
        JOIN hkracing_meetings m ON r.meeting_id = m.id
        WHERE m.date = ? AND m.venue_code = ?
        GROUP BY jockey_name_ch
    ");
    $stmtJockey->execute([$date, $venue]);
    $jockeyCounts = [];
    while ($row = $stmtJockey->fetch(PDO::FETCH_ASSOC)) {
        $jockeyCounts[$row['jockey_name_ch']] = $row['horse_count'];
    }
    
    // ========== 8. 计算同场练马师/骑师数量 ==========
    $raceTrainerCounts = [];
    $raceJockeyCounts = [];
    foreach ($runners as $runner) {
        $trainer = $runner['trainer_name'];
        $jockey = $runner['jockey_name'];
        if ($trainer) $raceTrainerCounts[$trainer] = ($raceTrainerCounts[$trainer] ?? 0) + 1;
        if ($jockey) $raceJockeyCounts[$jockey] = ($raceJockeyCounts[$jockey] ?? 0) + 1;
    }
    
    $hasOdds = count($currentOddsMap) > 0;
    
    // ========== 获取同程完成时间排名（用于颜色标记） ==========
    $distance = $race['distance'];
    $venueCode = $race['venue_code'];
    $finishingTimeRank = [];
    
    foreach ($runners as $runner) {
        $horseCode = trim($runner['horse_code']);
        $runnerNo = $runner['runner_no'];
        
        if (empty($horseCode) || empty($runnerNo)) {
            continue;  // 跳过无效数据
        }
        
        $stmtTime = $pdo->prepare("
            SELECT finishing_time
            FROM hkracing_horse_performances
            WHERE horse_code = ?
              AND distance = ?
              AND venue_code = ?
              AND finishing_time IS NOT NULL
              AND finishing_time != ''
            ORDER BY race_date DESC
            LIMIT 1
        ");
        $stmtTime->execute([$horseCode, $distance, $venueCode]);
        $timeRow = $stmtTime->fetch(PDO::FETCH_ASSOC);
        
        if ($timeRow && !empty($timeRow['finishing_time'])) {
            $timeStr = $timeRow['finishing_time'];
            
            // 正确解析时间格式 "1.35.63" 或 "1:35.63"
            $seconds = 0;
            if (strpos($timeStr, ':') !== false) {
                // 格式: "1:35.63"
                $parts = explode(':', $timeStr);
                $minutes = intval($parts[0]);
                $secs = floatval($parts[1]);
                $seconds = $minutes * 60 + $secs;
            } elseif (substr_count($timeStr, '.') == 2) {
                // 格式: "1.35.63" (分钟.秒.百分秒)
                $parts = explode('.', $timeStr);
                $minutes = intval($parts[0]);
                $secs = intval($parts[1]);
                $frac = intval($parts[2]) / 100;
                $seconds = $minutes * 60 + $secs + $frac;
            } else {
                // 格式: "95.63" (只有秒数)
                $seconds = floatval($timeStr);
            }
            
            $finishingTimeRank[$runnerNo] = [
                'time' => $timeStr,
                'seconds' => $seconds
            ];
        }
    }
    
    // 按时间排序（秒数越小越快）
    uasort($finishingTimeRank, function($a, $b) {
        return $a['seconds'] - $b['seconds'];
    });
    
    // 创建排名映射
    $timeRankMap = [];
    $rank = 1;
    foreach ($finishingTimeRank as $runnerNo => $data) {
        $timeRankMap[$runnerNo] = $rank;
        $rank++;
    }
    
    // 调试输出
    // error_log("完成时间排名: " . json_encode($timeRankMap));

    // 按时间排序，最快的排前面
    uasort($finishingTimeRank, function($a, $b) {
        return $a['seconds'] - $b['seconds'];
    });
    
    // ========== 9. 构建马匹数据 ==========
    $horses = [];
    
    foreach ($runners as $runner) {
        $horseCode = trim($runner['horse_code']);
        if (empty($horseCode)) continue;
        
        $runnerNo = $runner['runner_no'];
        $draw = $runner['draw'];
        $trainer = $runner['trainer_name'] ?? '-';
        $jockey = $runner['jockey_name'] ?? '-';
        
         // 从映射中获取上一次数据
        $prevWinOdds = $prevOddsMap[$runnerNo] ?? $prevOddsMap[ltrim($runnerNo, '0')] ?? null;
        $prevJockey = $prevJockeyMap[$runnerNo] ?? $prevJockeyMap[ltrim($runnerNo, '0')] ?? null;
        $prevTrainer = $prevTrainerMap[$runnerNo] ?? $prevTrainerMap[ltrim($runnerNo, '0')] ?? null;
        $prevFinalPosition = $prevFinalPositionMap[$runnerNo] ?? $prevFinalPositionMap[ltrim($runnerNo, '0')] ?? null;
        
        // 调试输出（可选，确认数据正确）
        error_log("马匹: {$runner['horse_name']}, 上次骑师: {$prevJockey}, 上次练马师: {$prevTrainer}, 上次名次: {$prevFinalPosition}");
        
        // 格式化显示上次赔率 + 名次
        $prevWinOddsDisplay = ($prevWinOdds && $prevWinOdds > 0) ? number_format($prevWinOdds, 1) . '倍' : '-';
        if ($prevFinalPosition && $prevFinalPosition > 0) {
            if ($prevFinalPosition == 1) {
                $prevWinOddsDisplay .= " <span class='prev-position'>🏆</span>";
            } elseif ($prevFinalPosition == 2) {
                $prevWinOddsDisplay .= " <span class='prev-position'>🥈</span>";
            } elseif ($prevFinalPosition == 3) {
                $prevWinOddsDisplay .= " <span class='prev-position'>🥉</span>";
            } else {
                $prevWinOddsDisplay .= " <span class='prev-position'>({$prevFinalPosition})</span>";
            }
        }
        
        // 骑师显示（添加上一次骑师）
        $jockeyDisplay = $jockey;
        if ($prevJockey && $prevJockey != $jockey) {
            $jockeyDisplay = "<span class='prev-name'>{$prevJockey}→</span>" . $jockeyDisplay;
        }
        
        // 练马师显示（添加上一次练马师）
        $trainerDisplay = $trainer;
        if ($prevTrainer && $prevTrainer != $trainer) {
            $trainerDisplay = "<span class='prev-name'>{$prevTrainer}→</span>" . $trainerDisplay;
        }
        
        // 然后继续添加练马师和骑师计数的显示
        if (isset($trainerCounts[$trainer]) && $trainerCounts[$trainer] > 0) {
            $trainerDisplay .= " <span class='trainer-count'>[{$trainerCounts[$trainer]}]</span>";
        }
        if (($raceTrainerCounts[$trainer] ?? 0) > 1) {
            $trainerDisplay = "<strong>{$trainerDisplay}</strong>";
        }
        
        if (isset($jockeyCounts[$jockey]) && $jockeyCounts[$jockey] > 0) {
            $jockeyDisplay .= " <span class='jockey-count'>[{$jockeyCounts[$jockey]}]</span>";
        }
        if (($raceJockeyCounts[$jockey] ?? 0) > 1) {
            $jockeyDisplay = "<strong>{$jockeyDisplay}</strong>";
        }
        
        // // 获取完成时间和排名颜色
        $runnerNo = $runner['runner_no'];
        $bestTime = '-';
        $runnerNumberClass = 'rank-none-number';
        $bestTimeClass = 'rank-none-time';
        
        if (isset($finishingTimeRank[$runnerNo])) {
            $bestTime = $finishingTimeRank[$runnerNo]['time'];
            $rank = $timeRankMap[$runnerNo];
            
            if ($rank == 1) {
                $runnerNumberClass = 'rank-1st-number';
                $bestTimeClass = 'rank-1st-time';
            } elseif ($rank == 2) {
                $runnerNumberClass = 'rank-2nd-number';
                $bestTimeClass = 'rank-2nd-time';
            } elseif ($rank == 3) {
                $runnerNumberClass = 'rank-3rd-number';
                $bestTimeClass = 'rank-3rd-time';
            } elseif ($rank == 4) {
                $runnerNumberClass = 'rank-4th-number';
                $bestTimeClass = 'rank-4th-time';
            } else {
                $runnerNumberClass = 'rank-other-number';
                $bestTimeClass = 'rank-other-time';
            }
        }

        // 获取赔率数据
        $currentOdds = $currentOddsMap[$runnerNo] ?? $currentOddsMap[ltrim($runnerNo, '0')] ?? [];
        $currentWinOdds = $currentOdds['current_win_odds'] ?? null;
        $isHot = ($currentOdds['is_hot_favourite'] ?? 0) == 1;
        
        // 获取上次赔率
        $prevWinOdds = $prevOddsMap[$runnerNo] ?? $prevOddsMap[ltrim($runnerNo, '0')] ?? null;
        
        // 获取跌幅（直接从 oddsDrop 表取最新值）
        $oddsDropValue = $oddsDropValueMap[$horseCode] ?? null;
        
        // 获取档位颜色
        $drawColorInfo = getDrawColorForHorse($horseCode, $draw, $horseDrawStats);
        $drawColor = $drawColorInfo['color'];
        $drawTooltip = $drawColorInfo['tooltip'];
        
        // 获取马匹统计
        $horseStats = null;
        $recentRuns = [];
        $ratingChange = null;
        $weightChange = null;
        $lastWeight = null;
        
        if ($isHongKongRace) {
            $stmt2 = $pdo->prepare("
                SELECT 
                    total_starts as starts,
                    total_wins as wins,
                    total_seconds as seconds,
                    total_thirds as thirds,
                    current_rating,
                    ROUND(IFNULL(total_wins, 0) / NULLIF(total_starts, 0) * 100, 1) as win_rate
                FROM hkracing_horses
                WHERE horse_code = ?
            ");
            $stmt2->execute([$horseCode]);
            $horseStats = $stmt2->fetch(PDO::FETCH_ASSOC);
            
            if ($horseStats) {
                $horseStats['starts'] = intval($horseStats['starts']);
                $horseStats['wins'] = intval($horseStats['wins']);
                $horseStats['win_rate'] = floatval($horseStats['win_rate']);
                
                // 计算评分变化
                $stmtRating = $pdo->prepare("
                    SELECT rating_before
                    FROM hkracing_horse_performances
                    WHERE horse_code = ?
                    ORDER BY race_date DESC
                    LIMIT 1
                ");
                $stmtRating->execute([$horseCode]);
                $lastPerf = $stmtRating->fetch(PDO::FETCH_ASSOC);
                
                if ($lastPerf && $lastPerf['rating_before'] > 0) {
                    $currentRating = intval($horseStats['current_rating'] ?? 0);
                    $lastRating = intval($lastPerf['rating_before']);
                    if ($currentRating > 0 && $lastRating > 0) {
                        $ratingChange = $currentRating - $lastRating;
                    }
                }
                
                // 获取上一次出赛的负磅
                $stmtWeight = $pdo->prepare("
                    SELECT actual_weight
                    FROM hkracing_horse_performances
                    WHERE horse_code = ?
                      AND actual_weight IS NOT NULL
                      AND actual_weight > 0
                    ORDER BY race_date DESC
                    LIMIT 1
                ");
                $stmtWeight->execute([$horseCode]);
                $lastWeightPerf = $stmtWeight->fetch(PDO::FETCH_ASSOC);
                
                if ($lastWeightPerf && $lastWeightPerf['actual_weight'] > 0) {
                    $currentWeight = floatval($runner['weight'] ?? 0);
                    $lastWeight = floatval($lastWeightPerf['actual_weight']);
                    if ($currentWeight > 0 && $lastWeight > 0) {
                        $weightChange = $currentWeight - $lastWeight;
                    }
                }
            }
            
            // 获取最近表现（只定义一次）
            $stmt3 = $pdo->prepare("
                SELECT finishing_position, barrier_draw as draw, class, running_position
                FROM hkracing_horse_performances
                WHERE horse_code = ?
                ORDER BY race_date DESC
                LIMIT 3
            ");
            $stmt3->execute([$horseCode]);
            $recentRuns = $stmt3->fetchAll(PDO::FETCH_ASSOC);
            
            // 获取跑法分析
            $runningStyle = getHorseRunningStyle($recentRuns);
            $runningStyleIcon = $runningStyle['icon'];
            $runningStyleText = $runningStyle['text'];
            $runningStyleDetail = $runningStyle['detail'];
            
            // 获取上次班次（最近一场的 class）
            $lastClass = '';
            if (!empty($recentRuns) && isset($recentRuns[0]['class'])) {
                $lastClass = $recentRuns[0]['class'];
            }
            
            // 获取当前班次
            $currentClass = $race['race_class'] ?? '';
            
            // 获取班次变化符号
            $classChangeSymbol = getClassChangeSymbol($currentClass, $lastClass);
        }
        
        // 计算综合评分
        if ($isHongKongRace && $horseStats && $horseStats['starts'] > 0) {
            $v2Result = calculateV2Score(
                $horseStats, 
                $recentRuns, 
                $draw, 
                $currentWinOdds, 
                $race['race_class'] ?? '', 
                $horseCode, 
                $pdo,
                $trainer,
                $jockey,
                $race['distance'],
                $race['venue_code']
            );
            $classChangeText = $v2Result['class_change_text'];
            $score = $v2Result['score'];
        } elseif ($isHongKongRace && $horseStats && $horseStats['starts'] == 0) {
            $score = 45;
            $classChangeText = '<span style="color: #9ca3af;">📋 新馬/無出賽紀錄</span>';
        } else {
            $score = $currentWinOdds ? round(max(0, 100 - ($currentWinOdds * 3)), 0) : 50;
            $score = min(100, max(0, $score));
            $classChangeText = '<span style="color: #9ca3af;">🌏 海外賽事</span>';
        }
        
        $predictionTag = getPredictionTag($currentWinOdds, $hasOdds);
        
        // 格式化显示
        $currentWinOddsDisplay = ($currentWinOdds && $currentWinOdds > 0) ? number_format($currentWinOdds, 1) : '-'; // . '倍'
        $prevWinOddsDisplay = ($prevWinOdds && $prevWinOdds > 0) ? number_format($prevWinOdds, 1)  : '-'; //. '倍'
        $oddsDropDisplay = ($oddsDropValue !== null && $oddsDropValue != 0) ? number_format($oddsDropValue, 0) : '-';
        
        // 计算赔率变化（今日 vs 上次）
        $oddsChange = null;
        $oddsChangeDisplay = '-';
        $oddsChangeClass = '';
        if ($currentWinOdds && $prevWinOdds && $prevWinOdds > 0) {
            $oddsChange = $currentWinOdds - $prevWinOdds;
            if ($oddsChange > 0) {
                $oddsChangeDisplay = '▲+' . number_format($oddsChange, 1);
                $oddsChangeClass = 'odds-up';
            } elseif ($oddsChange < 0) {
                $oddsChangeDisplay = '▼' . number_format(abs($oddsChange), 1);
                $oddsChangeClass = 'odds-down';
            } else {
                $oddsChangeDisplay = '● 持平';
                $oddsChangeClass = 'odds-equal';
            }
        }
        
        // 负磅变化显示
        $weightDisplay = $runner['weight'] ?? '-';
        if ($weightChange !== null && $weightChange != 0) {
            $changeClass = $weightChange > 0 ? 'weight-up' : 'weight-down';
            $changeSign = $weightChange > 0 ? '+' : '';
            $weightDisplay .= " <span class='{$changeClass}' title='上一次: {$lastWeight}磅'>{$changeSign}{$weightChange}</span>";
        }
        
        // 练马师显示
        $trainerDisplay = $trainer;
        if (isset($trainerCounts[$trainer]) && $trainerCounts[$trainer] > 0) {
            $trainerDisplay .= " <span class='trainer-count'>[{$trainerCounts[$trainer]}]</span>";
        }
        if (($raceTrainerCounts[$trainer] ?? 0) > 1) {
            $trainerDisplay = "<strong>{$trainerDisplay}</strong>";
        }
        
        // 骑师显示
        $jockeyDisplay = $jockey;
        if (isset($jockeyCounts[$jockey]) && $jockeyCounts[$jockey] > 0) {
            $jockeyDisplay .= " <span class='jockey-count'>[{$jockeyCounts[$jockey]}]</span>";
        }
        if (($raceJockeyCounts[$jockey] ?? 0) > 1) {
            $jockeyDisplay = "<strong>{$jockeyDisplay}</strong>";
        }
        
        // 热门爱心
        $heartIcon = $isHot ? ' <span class="heart-icon">❤️</span>' : '';
        
        // 评分变化显示
        $ratingDisplay = $runner['rating'] ?? '-';
        if ($ratingChange !== null && $ratingChange != 0) {
            $changeClass = $ratingChange > 0 ? 'rating-up' : 'rating-down';
            $changeSign = $ratingChange > 0 ? '+' : '';
            $ratingDisplay .= " <span class='{$changeClass}'>{$changeSign}{$ratingChange}</span>";
        }
        
        // 计算骑练组合的出赛数量
        $comboKey = $jockey . '|' . $trainer;
        $currentComboCount = $currentJockeyTrainerCounts[$comboKey] ?? 0;
        $prevComboCount = $prevJockeyTrainerCounts[$comboKey] ?? 0;
        
        // 计算变化
        $comboChange = $currentComboCount - $prevComboCount;
        $comboChangeDisplay = '';
        if ($prevComboCount > 0 || $currentComboCount > 0) {
            if ($comboChange > 0) {
                $comboChangeDisplay = "<span class='combo-up'>{$prevComboCount}▲{$currentComboCount}</span>"; //$comboChange
            } elseif ($comboChange < 0) {
                $comboChangeDisplay = "<span class='combo-down'>{$prevComboCount}▼{$currentComboCount}</span>";
            } else {
                $comboChangeDisplay = "<span class='combo-equal'>{$prevComboCount}●{$currentComboCount}</span>";
            }
            
            $comboTooltip = "上次 ({$prevDate}): {$prevComboCount}匹 | 今日: {$currentComboCount}匹";
            if ($prevDate) {
                $comboChangeDisplay = "<span class='combo-change' title='" . htmlspecialchars($comboTooltip) . "'>{$comboChangeDisplay}</span>";
            }
        } else {
            $comboChangeDisplay = "<span class='combo-none'>-</span>";
        }
        // 骑师显示（添加上一次骑师）
        $jockeyDisplay = $jockey;
        $prevJockeyDisplay = '';
        if ($prevJockey && $prevJockey != $jockey) {
            $prevJockeyDisplay = "<span class='prev-name'>{$prevJockey}→</span>";
            $jockeyDisplay = $prevJockeyDisplay . $jockeyDisplay;
        }
        
        // 练马师显示（添加上一次练马师）
        $trainerDisplay = $trainer;
        $prevTrainerDisplay = '';
        if ($prevTrainer && $prevTrainer != $trainer) {
            $prevTrainerDisplay = "<span class='prev-name'>{$prevTrainer}→</span>";
            $trainerDisplay = $prevTrainerDisplay . $trainerDisplay;
        }
        
        // 上次赔率 + 名次显示
        $prevWinOddsDisplay = ($prevWinOdds && $prevWinOdds > 0) ? number_format($prevWinOdds, 1) . '' : '-'; //倍
        if ($prevFinalPosition && $prevFinalPosition > 0) {
            $positionText = '';
            if ($prevFinalPosition == 1) {
                $positionText = '🏆';  // 冠军图标
            } elseif ($prevFinalPosition == 2) {
                $positionText = '🥈';  // 亚军图标
            } elseif ($prevFinalPosition == 3) {
                $positionText = '🥉';  // 季军图标
            } else {
                $positionText = "({$prevFinalPosition})";
            }
            $prevWinOddsDisplay .= " <span class='prev-position'>{$positionText}</span>";
        }
        
        // 继续添加练马师和骑师计数的显示
        if (isset($trainerCounts[$trainer]) && $trainerCounts[$trainer] > 0) {
            $trainerDisplay .= " <span class='trainer-count'>[{$trainerCounts[$trainer]}]</span>";
        }
        if (($raceTrainerCounts[$trainer] ?? 0) > 1) {
            $trainerDisplay = "<strong>{$trainerDisplay}</strong>";
        }
        
        if (isset($jockeyCounts[$jockey]) && $jockeyCounts[$jockey] > 0) {
            $jockeyDisplay .= " <span class='jockey-count'>[{$jockeyCounts[$jockey]}]</span>";
        }
        if (($raceJockeyCounts[$jockey] ?? 0) > 1) {
            $jockeyDisplay = "<strong>{$jockeyDisplay}</strong>";
        }
        
        // 评分变化符号
        // $ratingChangeSymbol = getRatingChangeSymbol($ratingChange);
        
        // 马名显示（包含评分变化符号）
        // $horseNameDisplay = $runner['horse_name'] . $heartIcon . $ratingChangeSymbol;
        $horseNameDisplay = $runner['horse_name'] . $heartIcon . $classChangeSymbol;
    
        $horses[] = [
            'runner_no' => $runnerNo,
            'draw' => $draw,
            'draw_color' => $drawColor,
            'draw_tooltip' => $drawTooltip,
            'horse_name_display' => $runner['horse_name'] . $heartIcon,
            'jockey_display' => $jockeyDisplay,        // 更新：带上次骑师
            'trainer_display' => $trainerDisplay,      // 更新：带上次练马师
            'prev_win_odds' => $prevWinOddsDisplay,    // 更新：带名次
            'running_style_icon' => $runningStyleIcon,      // 新增
            'running_style_text' => $runningStyleText,      // 新增
            'running_style_detail' => $runningStyleDetail,  // 新增
            'current_win_odds' => $currentWinOddsDisplay,
            'odds_drop' => $oddsDropDisplay,
            'odds_change' => $oddsChangeDisplay,
            'odds_change_class' => $oddsChangeClass,
            'weight_display' => $weightDisplay,
            'rating_display' => $ratingDisplay,
            'starts' => $horseStats['starts'] ?? 0,
            'win_rate' => $horseStats['win_rate'] ?? 0,
            'v2_score' => $score,
            'prediction_tag' => $predictionTag,
            'class_change_text' => $classChangeText,
            'combo_change' => $comboChangeDisplay,
            'prev_win_odds' => $prevWinOddsDisplay,
            'runner_no_display' => "<span class='{$runnerNumberClass}'>{$runnerNo}</span>",
            'best_time' => $bestTime,
            'best_time_class' => $bestTimeClass,  // 改名避免重复
            'horse_name_display' => $horseNameDisplay,
        ];
    }
    
    // 按档位排序
    usort($horses, function($a, $b) {
        $drawA = isset($a['draw']) && is_numeric($a['draw']) ? intval($a['draw']) : 99;
        $drawB = isset($b['draw']) && is_numeric($b['draw']) ? intval($b['draw']) : 99;
        return $drawA - $drawB;
    });
    
    // return renderRaceAnalysisHTML($race, $horses, $hasOdds, $isHongKongRace);
    return renderRaceAnalysisHTML($race, $horses, $hasOdds, $isHongKongRace, $pdo);
}

/**
 * 基于该马在该档位的历史表现获取档位颜色
 */
function getDrawColorForHorse($horseCode, $draw, $horseDrawStats) {
    $drawNum = intval($draw);
    $key = $horseCode . '_' . $drawNum;
    
    $defaultColor = '#6b7280';
    $tooltip = '無歷史數據';
    
    if (isset($horseDrawStats[$key])) {
        $stats = $horseDrawStats[$key];
        $winRate = $stats['win_rate'];
        $top3Rate = $stats['top3_rate'];
        $runs = $stats['runs'];
        
        $tooltip = "同檔出賽{$runs}次，勝率{$winRate}%，上名率{$top3Rate}%";
        
        if ($winRate >= 20) {
            return ['color' => '#10b981', 'tooltip' => $tooltip];      // 绿色 - 高胜率
        } elseif ($winRate >= 10) {
            return ['color' => '#22c55e', 'tooltip' => $tooltip];      // 浅绿 - 中高胜率
        } elseif ($winRate >= 5) {
            return ['color' => '#f59e0b', 'tooltip' => $tooltip];      // 橙色 - 中等胜率
        } elseif ($winRate > 0) {
            return ['color' => '#8b5cf6', 'tooltip' => $tooltip];      // 紫色 - 低胜率（原红色改为紫色）
        } elseif ($top3Rate >= 50) {
            return ['color' => '#ef4444', 'tooltip' => $tooltip];      // 红色 - 上名率高（原紫色改为红色）>=30
        } else {
            return ['color' => '', 'tooltip' => $tooltip];      // 紫色 - 无胜出记录#8b5cf6
        }
    }
    
    return ['color' => $defaultColor, 'tooltip' => $tooltip];
}

/**
 * 从数据库获取已存储的优先顺序排名
 * @param PDO $pdo 数据库连接
 * @param string $raceId 赛事ID
 * @param string $runnerNo 马匹号码
 * @return int|null 优先排名，如果没有则返回null
 */
function getStoredPriority($pdo, $raceId, $runnerNo) {
    if (!$pdo) return null;
    try {
        $stmt = $pdo->prepare("
            SELECT priority FROM hkracing_v2_score 
            WHERE race_id = ? AND runner_no = ? AND score_version = 2
            ORDER BY last_updated DESC
            LIMIT 1
        ");
        $stmt->execute([$raceId, $runnerNo]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['priority'] : null;
    } catch (Exception $e) {
        error_log("getStoredPriority error: " . $e->getMessage());
        return null;
    }
}

/**
 * 基于历史数据获取档位颜色
 */
function getDrawColorFromHistory($draw, $drawStats) {
    $drawNum = intval($draw);
    
    if ($drawNum <= 3) {
        $range = '1-3';
    } elseif ($drawNum <= 6) {
        $range = '4-6';
    } elseif ($drawNum <= 9) {
        $range = '7-9';
    } else {
        $range = '10+';
    }
    
    $winRate = $drawStats[$range] ?? 0;
    
    if ($winRate >= 12) {
        return '#10b981'; // 绿色 - 高胜率档位
    } elseif ($winRate >= 8) {
        return '#f59e0b'; // 橙色 - 中等胜率档位
    } elseif ($winRate >= 5) {
        return '#ef4444'; // 红色 - 较低胜率档位
    } else {
        return '#6b7280'; // 灰色 - 低胜率档位
    }
}
/**
 * Get prediction tag based on odds
 * @param float|null $winOdds Win odds value
 * @param bool $hasOdds Whether odds data is available
 * @return array Prediction tag with class and text
 */
function getPredictionTag($winOdds, $hasOdds) {
    if (!$hasOdds || $winOdds === null || $winOdds <= 0) {
        return ['class' => 'prediction-unknown', 'text' => '⏳ 待定'];
    }
    
    if ($winOdds <= 3.0) {
        return ['class' => 'prediction-hot', 'text' => '🔥 熱門'];
    } elseif ($winOdds <= 10.0) {
        return ['class' => 'prediction-equal', 'text' => '⚖️ 均勢'];
    } else {
        return ['class' => 'prediction-cold', 'text' => '❄️ 冷門'];
    }
}

/**
 * Get class change text for display
 * @param array $recentRuns Recent race performances
 * @param string $currentClass Current race class
 * @return string HTML formatted class change text
 */
function getClassChangeText($recentRuns, $currentClass) {
    if (empty($recentRuns)) {
        return '<span style="color: #9ca3af;">📋 無最近戰績</span>';
    }
    
    // Parse current class number
    $currentClassNum = parseClassToNumber($currentClass);
    
    if ($currentClassNum == 0) {
        return '<span style="color: #9ca3af;">📋 班次待定</span>';
    }
    
    // Get last race class
    $lastRun = $recentRuns[0];
    $lastClass = $lastRun['class'] ?? '';
    $lastClassNum = parseClassToNumber($lastClass);
    
    // No recent class data available
    if ($lastClassNum == 0) {
        return '<span style="color: #9ca3af;">📋 無班次數據</span>';
    }
    
    // Calculate class change
    if ($currentClassNum < $lastClassNum) {
        $change = $lastClassNum - $currentClassNum;
        return '<span style="color: #ef4444;">⬆️ 升班 ' . $change . ' 級</span>';
    } elseif ($currentClassNum > $lastClassNum) {
        $change = $currentClassNum - $lastClassNum;
        return '<span style="color: #10b981;">⬇️ 降班 ' . $change . ' 級</span>';
    } else {
        return '<span style="color: #6b7280;">➡️ 班次不變</span>';
    }
}

/**
 * Get CSS class for race class color styling
 * @param string $raceClass Race class string
 * @return string CSS class name
 */
function getClassColorStyle($raceClass) {
    if (empty($raceClass)) return 'class-default';
    
    // Parse class number
    $classNum = parseClassToNumber($raceClass);
    
    if ($classNum == 1) return 'class-premier';  // Purple for Class 1
    if ($classNum == 2) return 'class-classic';  // Blue for Class 2
    if ($classNum == 3) return 'class-group';    // Green for Class 3
    if ($classNum == 4) return 'class-ordinary'; // Gray for Class 4
    if ($classNum == 5) return 'class-ordinary'; // Gray for Class 5
    
    // Griffin or international races
    if (strtoupper($raceClass) == 'GRIFFIN') return 'class-group';
    if (preg_match('/^G\d+$/i', $raceClass)) return 'class-premier';
    
    return 'class-default';
}

/**
 * Get draw analysis text based on horse win rates by draw position
 * @param array $horses Array of horse data
 * @return string Analysis text
 */
function getDrawAnalysis($horses) {
    $drawStats = ['內檔(1-3)' => 0, '中檔(4-6)' => 0, '外檔(7+)' => 0];
    $drawWinners = ['內檔(1-3)' => 0, '中檔(4-6)' => 0, '外檔(7+)' => 0];
    
    foreach ($horses as $horse) {
        $draw = intval($horse['draw'] ?? 0);
        $winRate = floatval($horse['win_rate']);
        
        if ($draw <= 3) {
            $drawStats['內檔(1-3)']++;
            if ($winRate >= 10) $drawWinners['內檔(1-3)']++;
        } elseif ($draw <= 6) {
            $drawStats['中檔(4-6)']++;
            if ($winRate >= 10) $drawWinners['中檔(4-6)']++;
        } elseif ($draw >= 7 && $draw <= 14) {
            $drawStats['外檔(7+)']++;
            if ($winRate >= 10) $drawWinners['外檔(7+)']++;
        }
    }
    
    // Find best performing draw group
    $bestDraw = '';
    $bestScore = 0;
    foreach ($drawStats as $group => $count) {
        if ($count > 0) {
            $score = ($drawWinners[$group] / $count) * 100;
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestDraw = $group;
            }
        }
    }
    
    if ($bestDraw == '內檔(1-3)') return '內檔馬匹勝率較高，建議關注內檔馬';
    if ($bestDraw == '中檔(4-6)') return '中檔馬錶現穩定，各檔位差距不大';
    if ($bestDraw == '外檔(7+)') return '外檔馬匹勝率較高，長途賽事外檔影響較小';
    
    return '各檔位表現均衡';
}

/**
 * Render race analysis HTML
 * @param array $race Race data
 * @param array $horses Horse data array
 * @param bool $hasOdds Whether odds data is available
 * @param bool $isHongKongRace Whether this is a Hong Kong local race
 * @return string HTML output
 */
// function renderRaceAnalysisHTML($race, $horses, $hasOdds, $isHongKongRace = true) {
function renderRaceAnalysisHTML($race, $horses, $hasOdds, $isHongKongRace = true, $pdo = null) {
    // $venueName = $race['venue_code'] == 'ST' ? '沙田' : ($race['venue_code'] == 'HV' ? '跑馬地' : $race['venue_code']);
    if ($race['venue_code'] == 'ST') {
        $venueName = '沙田';
    } elseif ($race['venue_code'] == 'HV') {
        $venueName = '跑馬地';
    } else {
        $venueName = $overseasVenueNames[$race['venue_code']] ?? '海外 (' . $race['venue_code'] . ')';
    }
    $raceClass = $race['race_class'] ?? '班次待定';
    $classColor = getClassColorStyle($raceClass);
    $raceTime = isset($race['race_time']) ? date('H:i:s', strtotime($race['race_time'])) : '時間待定';
    $distance = $race['distance'] ?? '距離待定';
    $horseCount = count($horses);
    
    $html = '<div class="race-analysis-v2">';
    
    // Race header
    $html .= '<div class="race-header">';
    $html .= '<h3>📅 第 ' . htmlspecialchars($race['race_no']) . ' 場 - ' . htmlspecialchars($race['race_name'] ?? '賽事') . '</h3>';
    $html .= '<div>🏟️ ' . $venueName . ' | ' . $distance . '米 | ' . $raceTime . '</div>';
    $html .= '<div><span class="class-label ' . $classColor . '">' . htmlspecialchars($raceClass) . '</span></div>';
    $html .= '<div>🐎 參賽馬匹: ' . $horseCount . ' 匹</div>';
    
    if (!$isHongKongRace) {
        $html .= '<div><span style="background: #8b5cf6; padding: 2px 8px; border-radius: 12px; font-size: 11px;">🌏 海外賽事 - 僅供參考</span></div>';
    } elseif (!$hasOdds) {
        $html .= '<div><span style="background: #f39c12; padding: 2px 8px; border-radius: 12px; font-size: 11px;">⏳ 賠率尚未發佈，預測為待定狀態</span></div>';
    }
    $html .= '</div>';
    
    // Table
    $html .= '<div class="table-wrapper">';
    $html .= '<table class="sortable-table">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th>檔位</th>';
    $html .= '<th>馬號</th>';
    $html .= '<th>馬匹</th>';
    $html .= '<th>跑法</th>';
    $html .= '<th>騎師</th>';
    $html .= '<th>練馬師</th>';
    $html .= '<th>騎練變化</th>';  // 新增<br>出賽組合
    $html .= '<th>上次獨贏</th>';
    $html .= '<th>上次落飛</th>';
    $html .= '<th>今次獨贏</th>';
    // $html .= '<th>賠率變化</th>';
    $html .= '<th>負磅</th>';
    $html .= '<th>評分</th>';
    $html .= '<th>出賽</th>';
    $html .= '<th>勝率</th>';
    $html .= '<th>預測</th>';
    $html .= '<th>綜合評分</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    foreach ($horses as $horse) {
        $draw = $horse['draw'] ?? '-';
        $drawColor = $horse['draw_color'] ?? '#6b7280';
        
        $winRateClass = ($horse['win_rate'] >= 15) ? 'win-rate-high' : 'win-rate-cell';
        
        $html .= '';
        $html .= '<td class="draw-cell" style="color:' . $drawColor . '; font-weight:bold; cursor:help;" title="' . htmlspecialchars($drawTooltip) . '">' . htmlspecialchars($draw) . '</td>';
        $html .= '<td class="runner-no-cell">' . $horse['runner_no_display'] . '</td>';  // 使用带颜色的马号
        $html .= '<td class="horse-name">' . $horse['horse_name_display'] . '</td>';
         $html .= '<td class="running-style-cell" title="' . htmlspecialchars($horse['running_style_text'] . $horse['running_style_detail']) . '">' . $horse['running_style_icon'] . '</td>';  // 使用 $horse 数组
        $html .= '<td class="jockey-name">' . $horse['jockey_display'] . '</td>';
        $html .= '<td class="trainer-name">' . $horse['trainer_display'] . '</td>';
        $html .= '<td class="combo-change-cell">' . $horse['combo_change'] . '</td>';  // 新增
        $html .= '<td class="odds-cell">' . $horse['prev_win_odds'] . '</td>';
        $html .= '<td class="odds-drop-cell">' . $horse['odds_drop'] . '</td>';
        $html .= '<td class="odds-cell">' . $horse['current_win_odds'] . '</td>';
        $html .= '<td>' . $horse['weight_display'] . '</td>';
        $html .= '<td class="rating-cell">' . $horse['rating_display'] . '</td>';
        $html .= '<td>' . ($horse['starts'] > 0 ? $horse['starts'] : '-') . '</td>';
        $html .= '<td class="' . $winRateClass . '">' . ($horse['win_rate'] > 0 ? $horse['win_rate'] . '%' : '-') . '</td>';
        $html .= '<td class="prediction-cell"><span class="' . $horse['prediction_tag']['class'] . '">' . $horse['prediction_tag']['text'] . '</span></td>';
        $html .= '<td class="v2-col"><span class="score-cell">' . $horse['v2_score'] . '</span></td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody>';
    $html .= '<table>';
    $html .= '</div>';
    
    // CSS styles
    $html .= '<style>
        /* 表格容器 - 支持手机横向滚动 */
        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 0 -1px;
            border-radius: 12px;
        }
        
        .sortable-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.75rem;
            min-width: 1200px;  /* 确保表格有最小宽度，触发滚动 */
            white-space: nowrap; /* 防止换行 */
        }
        
        /* 手机端调整 */
        @media (max-width: 768px) {
            .sortable-table {
                font-size: 0.7rem;
                min-width: 1100px;
            }
            
            .sortable-table th,
            .sortable-table td {
                padding: 8px 6px;
            }
            
            .table-wrapper {
                margin: 0 -12px;
                padding: 0 12px;
            }
        }
        
        /* 保持某些列不换行 */
        .sortable-table th,
        .sortable-table td {
            white-space: nowrap;
        }
        
        /* 马名可以稍微允许换行（如果太长） */
        .sortable-table td.horse-name {
            white-space: normal;
            min-width: 80px;
            max-width: 120px;
        }
        
        /* 档位、马号等小列固定宽度 */
        .sortable-table th:first-child,
        .sortable-table td:first-child,
        .sortable-table th:nth-child(2),
        .sortable-table td:nth-child(2) {
            text-align: center;
            width: 35px;
        }
        
        /* 赔率列宽度 */
        .sortable-table td.odds-cell,
        .sortable-table td.odds-drop-cell,
        .sortable-table td.odds-change-cell {
            text-align: center;
            min-width: 45px;
        }
        
        /* 滚动条样式 */
        .table-wrapper::-webkit-scrollbar {
            height: 6px;
        }
        
        .table-wrapper::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        
        .table-wrapper::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        .prev-name {
            font-size: 0.65rem;
            color: #888;
            margin-right: 2px;
        }
        .prev-position {
            font-size: 0.65rem;
            color: #f59e0b;
            margin-left: 2px;
        }
        .running-style-cell {
            text-align: center;
            font-size: 1rem;
            cursor: help;
        }
         /* 评分变化符号 */
        .rating-up-symbol {
            color: #ef4444;
            font-size: 0.7rem;
            margin-left: 3px;
            font-weight: bold;
        }
        .rating-down-symbol {
            color: #10b981;
            font-size: 0.7rem;
            margin-left: 3px;
            font-weight: bold;
        }
        
        /* 马号颜色 - 基于最佳时间排名 */
        .time-rank-1st {
            color: #dc2626;
            font-weight: bold;
            font-size: 1rem;
        }
        .time-rank-2nd {
            color: #f97316;
            font-weight: bold;
            font-size: 0.95rem;
        }
        .time-rank-3rd {
            color: #eab308;
            font-weight: bold;
            font-size: 0.9rem;
        }
        .time-rank-4th {
            color: #22c55e;
            font-weight: bold;
            font-size: 0.85rem;
        }
        .time-rank-other {
            color: #1f2937;
            font-size: 0.8rem;
        }
        .time-rank-none {
            color: #9ca3af;
            font-size: 0.8rem;
            font-style: italic;
        }
        
        /* 最佳时间列 */
        .best-time-cell {
            text-align: center;
            font-size: 0.75rem;
            font-family: monospace;
        }
        .best-time-cell.time-rank-1st {
            color: #dc2626;
            font-weight: bold;
        }
        .best-time-cell.time-rank-2nd {
            color: #f97316;
        }
        .best-time-cell.time-rank-3rd {
            color: #eab308;
        }
        .best-time-cell.time-rank-4th {
            color: #22c55e;
        }
        .class-up-symbol {
            color: #ef4444;
            font-size: 0.65rem;
            margin-left: 4px;
            font-weight: bold;
            display: inline-block;
            background: #fee2e2;
            padding: 2px 5px;
            border-radius: 12px;
        }
        .class-down-symbol {
            color: #10b981;
            font-size: 0.65rem;
            margin-left: 4px;
            font-weight: bold;
            display: inline-block;
            background: #d1fae5;
            padding: 2px 5px;
            border-radius: 12px;
        }
        .heart-icon {
            color: #e94560;
            margin-left: 4px;
            display: inline-block;
        }
         .rank-1st-number {
            color: #dc2626;
            font-weight: bold;
            // font-size: 1rem;
        }
        .rank-2nd-number {
            color: #f97316;
            font-weight: bold;
            // font-size: 0.95rem;
        }
        .rank-3rd-number {
            color: #eab308;
            font-weight: bold;
            // font-size: 0.9rem;
        }
        .rank-4th-number {
            color: #22c55e;
            font-weight: bold;
            // font-size: 0.85rem;
        }
        .rank-other-number {
            color: #1f2937;
            // font-size: 0.8rem;
        }
        .rank-none-number {
            color: #9ca3af;
            // font-size: 0.8rem;
            font-style: italic;
        }
        
        /* 最佳时间颜色 */
        .rank-1st-time {
            color: #dc2626;
            font-weight: bold;
        }
        .rank-2nd-time {
            color: #f97316;
        }
        .rank-3rd-time {
            color: #eab308;
        }
        .rank-4th-time {
            color: #22c55e;
        }
        .rank-other-time {
            color: #1f2937;
        }
        .rank-none-time {
            color: #9ca3af;
            font-style: italic;
        }
        /* 评分标签样式 */
        .gear-up, .jockey-up, .fresh-up {
            background: #10b98120 !important;
            color: #10b981;
        }
        .gear-down, .jockey-down, .fresh-down, .weight-down {
            background: #ef444420 !important;
            color: #ef4444;
        }
        .weight-up {
            background: #10b98120 !important;
            color: #10b981;
        }
        .draw-cell { font-weight: bold; text-align: center; }
        .odds-drop-cell { color: #888; font-size: 0.85rem; }
        .trainer-count, .jockey-count { color: #888; font-size: 11px; margin-left: 2px; }
        .heart-icon { color: #e94560; margin-left: 4px; }
        .rating-up { color: #10b981; font-weight: bold; font-size: 0.75rem; }
        .rating-down { color: #ef4444; font-weight: bold; font-size: 0.75rem; }
        .sortable-table th { cursor: pointer; }
        .sortable-table th:hover { background: #f1f5f9; }
        .rating-cell { font-size: 0.85rem; }
        .combo-up { color: #10b981; font-weight: bold; }
        .combo-down { color: #ef4444; font-weight: bold; }
        .combo-equal { color: #6b7280; font-weight: bold; }
        .combo-none { color: #9ca3af; }
        .combo-change { cursor: help; border-bottom: 1px dotted #888; }
        .combo-change-cell { text-align: center; font-size: 0.85rem; }
        .weight-up { color: #ef4444; font-weight: bold; font-size: 0.7rem; cursor: help; }
        .weight-down { color: #10b981; font-weight: bold; font-size: 0.7rem; cursor: help; }
    </style>';
    
    $html .= '</div>';
    
    // Analysis summary section
    // $html .= '<div class="analysis-summary">';
    // $html .= '<h4>📊 分析總結</h4>';
    // 在 renderRaceAnalysisHTML 函数中，于 $html .= '<div class="analysis-summary">'; 之后添加：

    // 计算选马优先顺序（需要传入$horses和$race和$pdo）
    // $selectionPriorities = calculateSelectionPriority($horses, $race, $pdo);
    // 计算选马优先顺序（需要传入$horses和$race和$pdo）
    // 优先使用数据库中已存储的 priority
    $selectionPriorities = [];
    $raceId = $race['id'] ?? '';
    
    if ($pdo && $raceId) {
        // 尝试从数据库获取已存储的 priority
        $storedPriorities = [];
        foreach ($horses as $horse) {
            $storedPriority = getStoredPriority($pdo, $raceId, $horse['runner_no']);
            if ($storedPriority !== null) {
                $storedPriorities[$horse['runner_no']] = $storedPriority;
            }
        }
        
        // 如果所有马匹都有 stored priority，直接使用
        if (count($storedPriorities) === count($horses)) {
            // 按 priority 排序
            $sortedRunners = [];
            foreach ($storedPriorities as $runnerNo => $priority) {
                // 找到对应的 horse 数据
                foreach ($horses as $horse) {
                    if ($horse['runner_no'] == $runnerNo) {
                        $sortedRunners[$priority] = [
                            'runner_no' => $horse['runner_no'],
                            'horse_name' => preg_replace('/<[^>]*>/', '', $horse['horse_name_display']),
                            'draw' => $horse['draw'],
                            'current_odds' => floatval($horse['current_win_odds'] ?? 0),
                            'v2_score' => $horse['v2_score'] ?? 50,
                            'priority_score' => 0, // 从 priority 转换
                            'distance_win_rate' => 0,
                            'draw_top3_rate' => 0,
                            'suggestion' => '📊 基於資料庫排名'
                        ];
                        break;
                    }
                }
            }
            ksort($sortedRunners);
            $selectionPriorities = array_values($sortedRunners);
            // 重新计算 priority_score（用于显示进度条）
            $maxScore = 100;
            foreach ($selectionPriorities as $idx => &$item) {
                $rank = $idx + 1;
                $item['priority_score'] = round($maxScore - ($rank - 1) * ($maxScore / count($selectionPriorities)), 1);
                $item['history_score'] = 0;
            }
        }
    }
    
    // 如果没有 stored priority，使用 calculateSelectionPriority 计算
    if (empty($selectionPriorities)) {
        $selectionPriorities = calculateSelectionPriority($horses, $race, $pdo);
    }
    
    $avgWinRate = 0;
    $hotOddsCount = 0;
    $highWinRateCount = 0;
    foreach ($horses as $h) {
        $wr = floatval($h['win_rate'] ?? 0);
        $avgWinRate += $wr;
        if (floatval($h['current_win_odds'] ?? 0) <= 3 && $h['current_win_odds'] > 0) $hotOddsCount++;
        if ($wr >= 15) $highWinRateCount++;
    }
    $avgWinRate = count($horses) > 0 ? round($avgWinRate / count($horses), 1) : 0;
    
    // 在 renderRaceAnalysisHTML 函数中，找到 analysis-summary 部分
    
    $html .= '<div class="analysis-summary">';
    $html .= '<h4>📊 分析總結</h4>';
    
    // ========== 選馬優先順序提示（复用现有的折叠机制） ==========
    $html .= '<div class="help-section" style="margin-top: 12px;">';
    $html .= '<button class="help-toggle" onclick="toggleHelpPanel(\'priorityPanel\')" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 12px; color: white; cursor: pointer; font-size: 0.85rem; padding: 12px 15px; width: 100%; text-align: left; display: flex; align-items: center; justify-content: space-between;">';
    $html .= '<div style="display: flex; align-items: center; gap: 8px;">';
    $html .= '<span style="font-size: 1.2rem;">🏆</span>';
    $html .= '<span style="font-weight: bold;">選馬優先順序提示 (基於歷史數據分析)</span>';
    // $html .= '<span style="background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 20px; font-size: 0.65rem;">增強版 V2</span>';
    $html .= '</div>';
    $html .= '<span id="priorityPanelIcon" style="font-size: 1rem;">▼</span>';
    $html .= '</button>';
    $html .= '<div id="priorityPanel" class="help-content" style="display: none; margin-top: 10px;">';
    
    // 计算选马优先顺序
    $selectionPriorities = calculateSelectionPriority($horses, $race, $pdo);
    
    // 显示优先顺序列表
    $html .= '<div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border-radius: 12px; padding: 12px 15px; color: white;">';
    
    foreach ($selectionPriorities as $idx => $horse) {
        $rank = $idx + 1;
        $rankColor = '';
        $rankIcon = '';
        if ($rank == 1) {
            $rankColor = '#FFD700';
            $rankIcon = '👑';
        } elseif ($rank == 2) {
            $rankColor = '#C0C0C0';
            $rankIcon = '🥈';
        } elseif ($rank == 3) {
            $rankColor = '#CD7F32';
            $rankIcon = '🥉';
        } else {
            $rankColor = 'rgba(255,255,255,0.7)';
            $rankIcon = $rank;
        }
        
        // 优先分数对应的背景色条宽度
        $barWidth = min(100, $horse['priority_score']);
        
        // 获取详细评分 breakdown（如果有）
        $breakdown = isset($horse['breakdown']) ? $horse['breakdown'] : [];
        
        $html .= '<div style="background: rgba(255,255,255,0.1); border-radius: 10px; padding: 10px 12px; margin-bottom: 10px;">';
        
        // 第一行：排名、马号、马名、档位、赔率
        $html .= '<div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 8px;">';
        $html .= '<span style="min-width: 35px; font-weight: bold; font-size: 1.1rem;">' . $rankIcon . '</span>';
        $html .= '<span style="min-width: 45px; font-weight: bold; color: ' . $rankColor . '; font-size: 1rem;">' . $horse['runner_no'] . '號</span>';
        $html .= '<span style="flex: 1; font-weight: bold; font-size: 0.9rem;">' . htmlspecialchars($horse['horse_name']) . '</span>';
        $html .= '<span style="background: rgba(0,0,0,0.3); padding: 2px 8px; border-radius: 20px; font-size: 0.7rem;">檔 ' . $horse['draw'] . '</span>';
        // $html .= '<span style="background: rgba(0,0,0,0.3); padding: 2px 8px; border-radius: 20px; font-size: 0.7rem;">' . ($horse['current_odds'] > 0 ? $horse['current_odds'] . '倍' : '賠率待定') . '</span>';
        $html .= '</div>';
        
        // 进度条
        $html .= '<div style="margin-bottom: 8px;">';
        $html .= '<div style="background: rgba(0,0,0,0.4); border-radius: 10px; height: 8px; overflow: hidden;">';
        $html .= '<div style="background: ' . ($rank == 1 ? '#FFD700' : '#4FC3F7') . '; width: ' . $barWidth . '%; height: 100%; border-radius: 10px;"></div>';
        $html .= '</div>';
        $html .= '<div style="display: flex; justify-content: space-between; margin-top: 4px; font-size: 0.65rem; opacity: 0.8;">';
        $html .= '<span>🎯 優先度 ' . $horse['priority_score'] . '分</span>';
        $html .= '<span>📊 歷史條件 ' . ($horse['history_score'] ?? 0) . '分 | 綜合評分 ' . $horse['v2_score'] . '分</span>';
        $html .= '</div>';
        $html .= '</div>';
        
        // 核心指标
        $html .= '<div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 8px; font-size: 0.7rem;">';
        $html .= '<span title="同程勝率">🏇 同程: ' . ($horse['distance_win_rate'] ?? 0) . '%</span>';
        $html .= '<span title="同檔上名率">🎯 同檔上名: ' . ($horse['draw_top3_rate'] ?? 0) . '%</span>';
        $html .= '<span title="近況">📋 近況: ' . ($horse['recent_form'] ?? 'N/A') . '</span>';
        $html .= '</div>';
        
        // 详细评分标签（如果有 breakdown）
        if (!empty($breakdown)) {
            $html .= '<div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">';
            
            if (isset($breakdown['gear_change_score']) && $breakdown['gear_change_score'] != 0) {
                $gearClass = $breakdown['gear_change_score'] > 0 ? 'gear-up' : 'gear-down';
                $gearIcon = $breakdown['gear_change_score'] > 0 ? '🔧+' : '🔧';
                $html .= '<span class="' . $gearClass . '" style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 15px; font-size: 0.65rem;">' . $gearIcon . abs($breakdown['gear_change_score']) . '</span>';
            }
            
            if (isset($breakdown['jockey_change_score']) && $breakdown['jockey_change_score'] != 0) {
                $jockeyClass = $breakdown['jockey_change_score'] > 0 ? 'jockey-up' : 'jockey-down';
                $jockeyIcon = $breakdown['jockey_change_score'] > 0 ? '🏇+' : '🏇';
                $html .= '<span class="' . $jockeyClass . '" style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 15px; font-size: 0.65rem;">' . $jockeyIcon . abs($breakdown['jockey_change_score']) . '</span>';
            }
            
            if (isset($breakdown['freshen_score']) && $breakdown['freshen_score'] != 0) {
                $freshClass = $breakdown['freshen_score'] > 0 ? 'fresh-up' : 'fresh-down';
                $freshIcon = $breakdown['freshen_score'] > 0 ? '🌿+' : '⏰';
                $html .= '<span class="' . $freshClass . '" style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 15px; font-size: 0.65rem;">' . $freshIcon . abs($breakdown['freshen_score']) . '</span>';
            }
            
            if (isset($breakdown['weight_change_score']) && $breakdown['weight_change_score'] != 0) {
                $weightClass = $breakdown['weight_change_score'] > 0 ? 'weight-up' : 'weight-down';
                $weightIcon = $breakdown['weight_change_score'] > 0 ? '⚖️-' : '⚖️+';
                $html .= '<span class="' . $weightClass . '" style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 15px; font-size: 0.65rem;">' . $weightIcon . abs($breakdown['weight_change_score']) . '</span>';
            }
            
            if (isset($breakdown['running_style_score']) && $breakdown['running_style_score'] != 0) {
                $html .= '<span style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 15px; font-size: 0.65rem;">🏃 ' . ($breakdown['running_style_score'] > 0 ? '+' : '') . $breakdown['running_style_score'] . '</span>';
            }
            
            if (isset($breakdown['pedigree_score']) && $breakdown['pedigree_score'] > 0) {
                $html .= '<span style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 15px; font-size: 0.65rem;">🧬血統 +' . $breakdown['pedigree_score'] . '</span>';
            }
            
            $html .= '</div>';
        }
        
        // 建议文字
        $html .= '<div style="margin-top: 6px; font-size: 0.7rem; opacity: 0.9; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 6px;">';
        $html .= '💡 ' . htmlspecialchars($horse['suggestion']);
        $html .= '</div>';
        
        $html .= '</div>';
    }
    $html .= '</div>'; // 结束 priority-list
    
    // 添加分析依据说明（增强版）
    $html .= '<div class="help-section" style="margin-top: 12px;">';
    $html .= '<button class="help-toggle" onclick="toggleHelpPanel(\'analysisBasis\')" style="background: none; border: none; color: #3b82f6; cursor: pointer; font-size: 0.75rem; padding: 4px 8px;">';
    $html .= '📐 顯示/隱藏 分析依據說明 ▼';
    $html .= '</button>';
    $html .= '<div id="analysisBasis" class="help-content" style="display: none; margin-top: 10px;">';
    $html .= '<div style="font-size: 0.7rem; color: #555; padding: 10px; background: #f8f9fa; border-radius: 8px;">';
    $html .= '<strong>🎯 評分項目權重</strong><br>';
    $html .= '• <strong>同程勝率 (20%)</strong>：相同場地+相同距離的歷史勝率及上名率<br>';
    $html .= '• <strong>場地適性 (12%)</strong>：相同場地狀況(好地/黏地等)的歷史表現<br>';
    $html .= '• <strong>檔位表現 (10%)</strong>：相同檔位的勝率及上名率<br>';
    $html .= '• <strong>檔位+跑法配合 (5%)</strong>：內檔放頭/外檔後上的適配度<br>';
    $html .= '• <strong>配備變化 (8%)</strong>：首次戴眼罩(+5)、除眼罩(-2)等<br>';
    $html .= '• <strong>騎師更換 (8%)</strong>：換王牌騎師、合作記錄、氣勢<br>';
    $html .= '• <strong>休憩日數 (5%)</strong>：21-35天最佳，過短過長扣分<br>';
    $html .= '• <strong>體重變化 (5%)</strong>：減磅有利(+5)，增磅不利(-3)<br>';
    $html .= '• <strong>跑道偏差 (3%)</strong>：短途內檔優勢、谷草特殊性<br>';
    $html .= '• <strong>血統分析 (4%)</strong>：父系距離適性、年齡成熟度<br>';
    $html .= '• <strong>班次能力 (8%)</strong>：相同班次的歷史勝率<br>';
    $html .= '• <strong>近況狀態 (8%)</strong>：最近5場表現，越近場次權重越高<br>';
    $html .= '• <strong>賠率調整 (±10)</strong>：熱門加分，冷門扣分<br>';
    $html .= '<br><strong>📊 最終優先分</strong> = 歷史條件分(50%) + 綜合評分(50%)';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '</div>'; // 结束 priorityPanel
    $html .= '</div>'; // 结束 help-section
    
    // 原有的统计信息
    $html .= '<p>🔹 全場平均勝率: <strong>' . $avgWinRate . '%</strong></p>';
    $html .= '<p>🔹 熱門馬匹數量 (賠率≤3.0): <strong>' . $hotOddsCount . '</strong> 匹</p>';
    $html .= '<p>🔹 高勝率馬匹 (勝率≥15%): <strong>' . $highWinRateCount . '</strong> 匹</p>';
    $html .= '<p>🔹 檔位分析: ' . getDrawAnalysis($horses) . '</p>';

    // $html .= '<p>🔹 全場平均勝率: <strong>' . $avgWinRate . '%</strong></p>';
    // $html .= '<p>🔹 熱門馬匹數量 (賠率≤3.0): <strong>' . $hotOddsCount . '</strong> 匹</p>';
    // $html .= '<p>🔹 高勝率馬匹 (勝率≥15%): <strong>' . $highWinRateCount . '</strong> 匹</p>';
    // $html .= '<p>🔹 檔位分析: ' . getDrawAnalysis($horses) . '</p>';
    
    // ========== 可折叠说明区域 ==========
        $html .= '<div class="help-section" style="margin-top: 12px;">';
        $html .= '<button class="help-toggle" onclick="toggleHelpPanel(this)" style="background: none; border: none; color: #3b82f6; cursor: pointer; font-size: 0.75rem; padding: 4px 8px;">';
        $html .= '📘 顯示/隱藏 欄位說明 ▼';
        $html .= '</button>';
        $html .= '<div class="help-content" style="display: none; margin-top: 10px;">';
        
        // 檔位說明
        $html .= '<div class="draw-meaning" style="background: #d1fae5; padding: 8px 12px; border-radius: 8px; margin-bottom: 8px; font-size: 0.7rem; color: #065f46;">';
        $html .= '🎯 <strong>檔位說明：</strong> ';
        $html .= '<span style="color:#10b981;">🟢 綠</span>(勝率≥20%) ';
        $html .= '<span style="color:#22c55e;">🟢 淺綠</span>(10-19%) ';
        $html .= '<span style="color:#f59e0b;">🟠 橙</span>(5-9%) ';
        $html .= '<span style="color:#8b5cf6;">🟣 紫</span>(<5%) ';        // 紫色代表低胜率
        $html .= '<span style="color:#ef4444;">🔴 紅</span>(上名率≥30%) ';   // 红色代表上名率高
        $html .= '- 懸停查看詳情';
        $html .= '</div>';
        // ========== 最佳完成時間說明 ==========
        $html .= '<div class="best-time-meaning" style="background: #e0e7ff; padding: 8px 12px; border-radius: 8px; margin-bottom: 8px; font-size: 0.7rem; color: #3730a3;">';
        $html .= '⏱️ <strong>最佳完成時間說明：</strong> ';
        $html .= '顯示該馬匹在<b>相同途程及相同馬場</b>的最快完成時間<br>';
        $html .= '• <span style="color:#dc2626; font-weight:bold;">🔴 紅色</span> 全場最快（第1名） ';
        $html .= '<span style="color:#f97316; font-weight:bold;">🟠 橙色</span> 第2名 ';
        $html .= '<span style="color:#eab308; font-weight:bold;">🟡 黃色</span> 第3名 ';
        $html .= '<span style="color:#22c55e; font-weight:bold;">🟢 綠色</span> 第4名 ';
        $html .= '<span style="color:#1f2937;">⚫ 黑色</span> 第5名或以後 ';
        $html .= '<span style="color:#9ca3af;">⬜ 灰色</span> 無記錄<br>';
        $html .= '• 馬號顏色同步反映排名，方便快速識別同程表現較佳的馬匹';
        $html .= '</div>';
        // ========== 班次變化說明 ==========
        $html .= '<div class="class-change-meaning" style="background: #fef3c7; padding: 8px 12px; border-radius: 8px; margin-bottom: 8px; font-size: 0.7rem; color: #92400e;">';
        $html .= '🔄 <strong>班次變化說明：</strong> ';
        $html .= '<span style="color:#ef4444; background:#fee2e2; padding:2px 5px; border-radius:12px;">⬆️升N</span> 升班（班次數字變小，難度增加） ';
        $html .= '<span style="color:#10b981; background:#d1fae5; padding:2px 5px; border-radius:12px;">⬇️降N</span> 降班（班次數字變大，難度降低） ';
        $html .= '- 比較對象：上一次出賽班次 vs 今日班次 | 班次數字越小級別越高（如Class 1 > Class 2）';
        $html .= '</div>';
        $html .= '<div class="running-style-meaning" style="background: #e0e7ff; padding: 8px 12px; border-radius: 8px; margin-bottom: 8px; font-size: 0.7rem; color: #3730a3;">';
        $html .= '🏃 <strong>跑法說明：</strong> ';
        $html .= '<span style="color:#10b981;">🏃‍♂️ 放頭馬</span> 起步領放/跟前 ';
        $html .= '<span style="color:#f59e0b;">🏃 跟前馬</span> 跟前位置 ';
        $html .= '<span style="color:#6b7280;">🚶‍♂️ 居中馬</span> 居中位置 ';
        $html .= '<span style="color:#8b5cf6;">🏃‍♂️💨 後上馬</span> 留後衝刺 ';
        $html .= '- 懸停查看近三場跑法 | 數據基於沿途走位分析';
        $html .= '</div>';
        
        // 负磅说明
        $html .= '<div class="weight-meaning" style="background: #fef3c7; padding: 8px 12px; border-radius: 8px; margin-bottom: 8px; font-size: 0.7rem; color: #92400e;">';
        $html .= '⚖️ <strong>負磅變化：</strong> ';
        $html .= '<span style="color:#ef4444;">🔴 +數字</span>(負磅增加) ';
        $html .= '<span style="color:#10b981;">🟢 -數字</span>(負磅減少) ';
        $html .= '- 比較上一次出賽 | 鼠標懸停查看上次負磅';
        $html .= '</div>';
        
        // 評分說明
        $html .= '<div class="rating-meaning" style="background: #fef3c7; padding: 8px 12px; border-radius: 8px; margin-bottom: 8px; font-size: 0.7rem; color: #92400e;">';
        $html .= '⭐ <strong>評分變化：</strong> ';
        $html .= '<span style="color:#10b981;">🟢 +數字</span>(評分上升) ';
        $html .= '<span style="color:#ef4444;">🔴 -數字</span>(評分下降) ';
        $html .= '- 比較上一次出賽';
        $html .= '</div>';
        
        // 騎練組合說明
        $html .= '<div class="combo-meaning" style="background: #e0e7ff; padding: 8px 12px; border-radius: 8px; margin-bottom: 8px; font-size: 0.7rem; color: #3730a3;">';
        $html .= '👥 <strong>騎練組合變化：</strong> ';
        $html .= '<span style="color:#10b981;">🟢 ▲+數字</span>(比上次多) ';
        $html .= '<span style="color:#ef4444;">🔴 ▼-數字</span>(比上次少) ';
        $html .= '<span style="color:#6b7280;">⚫ ●</span>(持平) ';
        $html .= '- 懸停查看詳情 | 比較對象：上一次賽馬日';
        $html .= '</div>';
        
        // 賠率說明
        // $html .= '<div class="odds-meaning" style="background: #fee2e2; padding: 8px 12px; border-radius: 8px; margin-bottom: 8px; font-size: 0.7rem; color: #991b1b;">';
        // $html .= '💰 <strong>賠率數據：</strong> ';
        // $html .= '最近一場賽事的最終賠率 | ';
        // $html .= '<span style="color:#e94560;">❤️ 愛心</span>(該場大熱門) | ';
        // $html .= '跌幅正值表示賠率下跌(市場看好)';
        // $html .= '</div>';
        $html .= '<div class="odds-meaning" style="background: #fee2e2; padding: 8px 12px; border-radius: 8px; margin-bottom: 8px; font-size: 0.7rem; color: #991b1b;">';
        $html .= '💰 <strong>賠率說明：</strong> ';
        $html .= '「上次獨贏」= 該馬<b>上一次出賽</b>的最終賠率 | ';
        $html .= '「上次落飛」= <b>上一次</b>的賠率變化值 | '; //(oddsDrop表最新記錄) 跌幅 馬會提供的
        $html .= '「今日獨贏」= 即時最新賠率 | ';
        $html .= '「賠率變化」= 今日賠率 - 上次賠率 ';
        $html .= '<span style="color:#10b981;">🟢 ▼下跌</span>(變熱/看好) ';
        $html .= '<span style="color:#ef4444;">🔴 ▲上升</span>(變冷/看淡) ';
        $html .= '<span style="color:#6b7280;">⚫ ●持平</span>';
        $html .= '</div>';
        
        // 勝率說明
        // $html .= '<div class="win-rate-meaning" style="background: #e0f2fe; padding: 8px 12px; border-radius: 8px; font-size: 0.7rem; color: #0369a1;">';
        // $html .= '📖 <strong>勝率：</strong> ';
        // $html .= '頭馬次數 ÷ 總出賽次數 × 100% (統計不少於3場出賽)';
        // $html .= '</div>';
        
        $html .= '</div>'; // 结束 help-content
        $html .= '</div>'; // 结束 help-section
        
    if (!$isHongKongRace) {
        $html .= '<div class="overseas-note" style="background: #ede9fe; padding: 8px 12px; border-radius: 8px; margin-top: 10px; font-size: 0.75rem; color: #6d28d9;">';
        $html .= '🌏 <strong>海外賽事說明：</strong><br>';
        $html .= '• 此為海外賽事，歷史數據庫中沒有這些馬匹的往績記錄<br>';
        $html .= '• 綜合評分主要基於即時賠率計算<br>';
        $html .= '• 建議參考賠率趨勢及馬匹狀態作判斷';
        $html .= '</div>';
    } else {
        // Win rate explanation
        $html .= '<div class="win-rate-meaning" style="background: #e0f2fe; padding: 8px 12px; border-radius: 8px; margin-top: 10px; font-size: 0.75rem; color: #0369a1;">';
        $html .= '📖 <strong>勝率說明：</strong><br>';
        $html .= '• 計算方式：該馬匹在過去出賽中的頭馬次數 ÷ 總出賽次數 × 100%<br>';
        $html .= '• 僅統計不少於3場出賽的馬匹，數據更具參考價值<br>';
        $html .= '• 勝率越高代表該馬匹的爭勝能力越強';
        $html .= '</div>';
        
        // Score explanation with odds factor and prediction icons
        $html .= '<div class="score-meaning" style="background: #fef3c7; padding: 8px 12px; border-radius: 8px; margin-top: 10px; font-size: 0.75rem; color: #92400e;">';
        $html .= '⭐ <strong>綜合評分說明：</strong><br>';
        $html .= '• 勝率評分 (最高30分)：基於馬匹歷史勝率<br>';
        $html .= '• 近三場表現 (最高30分)：權重為50%/30%/20%<br>';
        $html .= '• 檔位歷史 (最高15分)：相同檔位組別的歷史表現<br>';
        $html .= '• 騎練合作 (最高15分)：該馬匹配上指定騎練組合的勝率<br>';
        $html .= '• 同程同場 (最高15分)：相同途程及馬場的歷史表現<br>';
        $html .= '• 賠率評分 (最高15分)：即時賠率越低分數越高 (≤2倍+15分, ≤3倍+14分, ≤4倍+12分, ≤10倍+8分, ≥10.0倍+3分)<br>';
        $html .= '• 班次變動 (±12分)：降班加分，升班扣分<br>';
        $html .= '• 練馬師場地 (最高10分)：練馬師在該馬場的整體勝率<br>';
        $html .= '• 練馬師近態 (最高10分)：練馬師最近30天表現及狀態趨勢<br>';
        $html .= '• 滿分~120分，分數越高代表綜合實力越強<br>';
        $html .= '• 預測標籤：<span style="color:#f59e0b;">⏳ 待定</span> → <span style="color:#dc2626;">🔥 熱門</span> / <span style="color:#f59e0b;">⚖️ 均勢</span> / <span style="color:#6b7280;">❄️ 冷門</span> (賠率發佈後自動更新)';
        $html .= '</div>';
    }
    
    $html .= '<small>⚠️ 以上分析僅供參考，投注前請考慮即時賠率及場地狀況變化</small>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    return $html;
}


/**
 * Get draw color based on barrier position
 */
function getDrawColorClass($draw) {
    $drawNum = intval($draw);
    if ($drawNum <= 3) {
        return '#10b981'; // 绿色 - 最佳档位
    } elseif ($drawNum <= 6) {
        return '#f59e0b'; // 橙色 - 良好档位
    } elseif ($drawNum <= 9) {
        return '#ef4444'; // 红色 - 一般档位
    } else {
        return '#6b7280'; // 灰色 - 较差档位
    }
}

function getOddsChartData($pdo) {
    $venue = $_GET['venue'] ?? '';
    $raceNo = $_GET['race_no'] ?? 0;
    $today = date('Y-m-d');
    
    try {
        // 獲取所選賽事的賠率趨勢數據
        $trendSql = "
            SELECT 
                horse_code,
                horse_name_ch,
                runner_no,
                captured_at,
                win_pre_odds,
                win_odds,
                place_pre_odds,
                place_odds,
                TIME(captured_at) as time_only
            FROM hkracing_odds_horse_details
            WHERE race_date = '$today' 
                AND venue_code = '$venue'
                AND race_no = $raceNo
                AND win_odds > 0
            ORDER BY captured_at, horse_code
        ";
        
        $trendStmt = $pdo->query($trendSql);
        $trendData = $trendStmt ? $trendStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        
        // 組織趨勢數據
        $winTrendData = [];
        $placeTrendData = [];
        $timePoints = [];
        $horseColors = [];
        
        $colorPalette = [
            '#e94560', '#f39c12', '#27ae60', '#3498db', '#9b59b6',
            '#1abc9c', '#e67e22', '#2c3e50', '#c0392b', '#16a085',
            '#2980b9', '#8e44ad', '#d35400', '#7f8c8d', '#2ecc71'
        ];
        
        $colorIndex = 0;
        
        // 收集所有時間點
        foreach ($trendData as $row) {
            $timePoint = $row['time_only'];
            if (!in_array($timePoint, $timePoints)) {
                $timePoints[] = $timePoint;
            }
        }
        sort($timePoints);
        
        // 按馬匹分組
        foreach ($trendData as $row) {
            $horseCode = $row['horse_code'];
            $horseName = $row['horse_name_ch'] ?? $horseCode;
            $runnerNo = $row['runner_no'];
            $timePoint = $row['time_only'];
            
            if (!isset($horseColors[$horseCode])) {
                $horseColors[$horseCode] = [
                    'color' => $colorPalette[$colorIndex % count($colorPalette)],
                    'name' => $horseName,
                    'runner_no' => $runnerNo
                ];
                $colorIndex++;
            }
            
            // 獨贏數據
            if (!isset($winTrendData[$horseCode])) {
                $winTrendData[$horseCode] = [
                    'name' => $horseName,
                    'runner_no' => $runnerNo,
                    'pre_odds' => $row['win_pre_odds'],
                    'values' => array_fill(0, count($timePoints), null)
                ];
            }
            $timeIdx = array_search($timePoint, $timePoints);
            if ($timeIdx !== false && $row['win_odds'] > 0) {
                $winTrendData[$horseCode]['values'][$timeIdx] = $row['win_odds'];
            }
            
            // 位置數據
            if (!isset($placeTrendData[$horseCode])) {
                $placeTrendData[$horseCode] = [
                    'name' => $horseName,
                    'runner_no' => $runnerNo,
                    'pre_odds' => $row['place_pre_odds'],
                    'values' => array_fill(0, count($timePoints), null)
                ];
            }
            if ($row['place_odds'] > 0) {
                $placeTrendData[$horseCode]['values'][$timeIdx] = $row['place_odds'];
            }
        }
        
        // 填充缺失值
        foreach ($winTrendData as &$data) {
            $lastValue = $data['pre_odds'];
            for ($i = 0; $i < count($data['values']); $i++) {
                if ($data['values'][$i] === null || $data['values'][$i] == 0) {
                    $data['values'][$i] = $lastValue;
                } else {
                    $lastValue = $data['values'][$i];
                }
            }
            $data['values'] = array_values(array_filter($data['values'], function($v) { return $v !== null && $v > 0; }));
        }
        
        foreach ($placeTrendData as &$data) {
            $lastValue = $data['pre_odds'];
            for ($i = 0; $i < count($data['values']); $i++) {
                if ($data['values'][$i] === null || $data['values'][$i] == 0) {
                    $data['values'][$i] = $lastValue;
                } else {
                    $lastValue = $data['values'][$i];
                }
            }
            $data['values'] = array_values(array_filter($data['values'], function($v) { return $v !== null && $v > 0; }));
        }
        
        // 準備返回數據
        $winChartData = [];
        foreach ($winTrendData as $horseCode => $data) {
            if (count($data['values']) >= 2) {
                $winChartData[] = [
                    'label' => $data['runner_no'] . '號 - ' . $data['name'],
                    'data' => $data['values'],
                    'borderColor' => $horseColors[$horseCode]['color']
                ];
            }
        }
        
        $placeChartData = [];
        foreach ($placeTrendData as $horseCode => $data) {
            if (count($data['values']) >= 2) {
                $placeChartData[] = [
                    'label' => $data['runner_no'] . '號 - ' . $data['name'],
                    'data' => $data['values'],
                    'borderColor' => $horseColors[$horseCode]['color']
                ];
            }
        }
        
        // 準備時間標籤
        $chartLabels = [];
        foreach ($timePoints as $tp) {
            $chartLabels[] = date('H:i', strtotime($tp));
        }
        
        // 獲取賽事名稱
        $venueNames = ['ST' => '沙田', 'HV' => '跑馬地'];
        $raceName = ($venueNames[$venue] ?? $venue) . ' 第' . $raceNo . '場';
        
        echo json_encode([
            'success' => true,
            'race_name' => $raceName,
            'labels' => $chartLabels,
            'win_data' => $winChartData,
            'place_data' => $placeChartData
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}


?>