<?php
// analysis_track.php - Track performance analysis (Complete version)

// Get racing season info (Hong Kong season runs from September to August)
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
$racingSeasonLabel = "{$racingSeasonStart}/{$racingSeasonEnd}";

// Get filter parameters
$filterVenue = $_GET['venue'] ?? '';
$filterCourse = $_GET['course'] ?? '';
$filterDistance = $_GET['distance'] ?? '';

try {
    // Get all available venues, courses, distances
    $stmtVenues = $pdo->query("
        SELECT DISTINCT venue_code 
        FROM hkracing_horse_performances 
        WHERE finishing_position IS NOT NULL 
        ORDER BY venue_code
    ");
    $allVenues = $stmtVenues ? $stmtVenues->fetchAll(PDO::FETCH_COLUMN) : [];
    
    $stmtCourses = $pdo->query("
        SELECT DISTINCT course 
        FROM hkracing_horse_performances 
        WHERE course IS NOT NULL AND course != ''
        ORDER BY course
    ");
    $allCourses = $stmtCourses ? $stmtCourses->fetchAll(PDO::FETCH_COLUMN) : [];
    
    $stmtDistances = $pdo->query("
        SELECT DISTINCT distance 
        FROM hkracing_horse_performances 
        WHERE distance IS NOT NULL
        ORDER BY distance
    ");
    $allDistances = $stmtDistances ? $stmtDistances->fetchAll(PDO::FETCH_COLUMN) : [];
    
    // ========== All-time venue statistics ==========
    $allTimeStmt = $pdo->query("
        SELECT 
            venue_code,
            COUNT(*) as total_runs,
            SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
            SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) as top3,
            ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate,
            ROUND(SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as top3_rate,
            ROUND(AVG(barrier_draw), 1) as avg_barrier,
            SUM(CASE WHEN barrier_draw <= 3 AND finishing_position = 1 THEN 1 ELSE 0 END) as inside_wins,
            SUM(CASE WHEN barrier_draw >= 10 AND finishing_position = 1 THEN 1 ELSE 0 END) as outside_wins,
            SUM(CASE WHEN barrier_draw <= 3 THEN 1 ELSE 0 END) as inside_runs,
            SUM(CASE WHEN barrier_draw >= 10 THEN 1 ELSE 0 END) as outside_runs
        FROM hkracing_horse_performances
        WHERE finishing_position IS NOT NULL AND finishing_position > 0
        GROUP BY venue_code
        ORDER BY total_runs DESC
    ");
    $allTimeVenues = $allTimeStmt ? $allTimeStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
    // ========== Current season venue statistics ==========
    $seasonStmt = $pdo->prepare("
        SELECT 
            venue_code,
            COUNT(*) as total_runs,
            SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
            SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) as top3,
            ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate,
            ROUND(SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as top3_rate,
            ROUND(AVG(barrier_draw), 1) as avg_barrier,
            SUM(CASE WHEN barrier_draw <= 3 AND finishing_position = 1 THEN 1 ELSE 0 END) as inside_wins,
            SUM(CASE WHEN barrier_draw >= 10 AND finishing_position = 1 THEN 1 ELSE 0 END) as outside_wins,
            SUM(CASE WHEN barrier_draw <= 3 THEN 1 ELSE 0 END) as inside_runs,
            SUM(CASE WHEN barrier_draw >= 10 THEN 1 ELSE 0 END) as outside_runs
        FROM hkracing_horse_performances
        WHERE finishing_position IS NOT NULL AND finishing_position > 0
            AND race_date >= :season_start
        GROUP BY venue_code
        ORDER BY total_runs DESC
    ");
    $seasonStmt->execute([':season_start' => $seasonStartDate]);
    $seasonVenues = $seasonStmt ? $seasonStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
    // Build maps for venue data
    $allTimeVenueMap = [];
    foreach ($allTimeVenues as $venue) {
        $allTimeVenueMap[$venue['venue_code']] = $venue;
    }
    
    $seasonVenueMap = [];
    foreach ($seasonVenues as $venue) {
        $seasonVenueMap[$venue['venue_code']] = $venue;
    }
    
    // Merge venue codes
    $allVenueCodes = array_unique(array_merge(array_keys($allTimeVenueMap), array_keys($seasonVenueMap)));
    
    // Build combined venue data
    $combinedVenues = [];
    foreach ($allVenueCodes as $code) {
        $allTime = $allTimeVenueMap[$code] ?? null;
        $season = $seasonVenueMap[$code] ?? null;
        
        $combinedVenues[] = [
            'venue_code' => $code,
            'all_runs' => $allTime['total_runs'] ?? 0,
            'all_wins' => $allTime['wins'] ?? 0,
            'all_top3' => $allTime['top3'] ?? 0,
            'all_win_rate' => $allTime['win_rate'] ?? 0,
            'all_top3_rate' => $allTime['top3_rate'] ?? 0,
            'all_avg_barrier' => $allTime['avg_barrier'] ?? 0,
            'all_inside_win_rate' => ($allTime['inside_runs'] ?? 0) > 0 ? round(($allTime['inside_wins'] ?? 0) / ($allTime['inside_runs'] ?? 1) * 100, 1) : 0,
            'all_outside_win_rate' => ($allTime['outside_runs'] ?? 0) > 0 ? round(($allTime['outside_wins'] ?? 0) / ($allTime['outside_runs'] ?? 1) * 100, 1) : 0,
            'season_runs' => $season['total_runs'] ?? 0,
            'season_wins' => $season['wins'] ?? 0,
            'season_top3' => $season['top3'] ?? 0,
            'season_win_rate' => $season['win_rate'] ?? 0,
            'season_top3_rate' => $season['top3_rate'] ?? 0,
            'season_avg_barrier' => $season['avg_barrier'] ?? 0,
            'season_inside_win_rate' => ($season['inside_runs'] ?? 0) > 0 ? round(($season['inside_wins'] ?? 0) / ($season['inside_runs'] ?? 1) * 100, 1) : 0,
            'season_outside_win_rate' => ($season['outside_runs'] ?? 0) > 0 ? round(($season['outside_wins'] ?? 0) / ($season['outside_runs'] ?? 1) * 100, 1) : 0,
        ];
    }
    
    // Sort by all-time win rate descending
    usort($combinedVenues, function($a, $b) {
        return $b['all_win_rate'] <=> $a['all_win_rate'];
    });
    
    // ========== Distance barrier analysis (All-time) ==========
    $distanceBarrierStmt = $pdo->query("
        SELECT 
            distance,
            CASE 
                WHEN barrier_draw <= 3 THEN '內檔'
                WHEN barrier_draw BETWEEN 4 AND 6 THEN '中檔'
                WHEN barrier_draw >= 7 THEN '外檔'
            END as barrier_group,
            COUNT(*) as runs,
            SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
            ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate
        FROM hkracing_horse_performances
        WHERE finishing_position IS NOT NULL 
          AND finishing_position > 0
          AND barrier_draw IS NOT NULL
          AND venue_code IN ('ST', 'HV')
        GROUP BY distance, barrier_group
        HAVING runs >= 30
        ORDER BY distance, 
            CASE barrier_group
                WHEN '內檔' THEN 1
                WHEN '中檔' THEN 2
                WHEN '外檔' THEN 3
            END
    ");
    $distanceBarrierAll = $distanceBarrierStmt ? $distanceBarrierStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
    // Find best barrier by distance (All-time)
    $bestBarrierByDistanceAll = [];
    foreach ($distanceBarrierAll as $item) {
        $dist = $item['distance'];
        if (!isset($bestBarrierByDistanceAll[$dist]) || $item['win_rate'] > $bestBarrierByDistanceAll[$dist]['win_rate']) {
            $bestBarrierByDistanceAll[$dist] = $item;
        }
    }
    
    // ========== Course barrier analysis (All-time) ==========
    $courseBarrierStmt = $pdo->query("
        SELECT 
            venue_code,
            course,
            CASE 
                WHEN barrier_draw <= 3 THEN '內檔'
                WHEN barrier_draw BETWEEN 4 AND 6 THEN '中檔'
                WHEN barrier_draw >= 7 THEN '外檔'
            END as barrier_group,
            COUNT(*) as runs,
            SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
            ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate
        FROM hkracing_horse_performances
        WHERE finishing_position IS NOT NULL 
          AND finishing_position > 0
          AND barrier_draw IS NOT NULL
          AND course IS NOT NULL
          AND venue_code IN ('ST', 'HV')
        GROUP BY venue_code, course, barrier_group
        HAVING runs >= 30
        ORDER BY venue_code, course,
            CASE barrier_group
                WHEN '內檔' THEN 1
                WHEN '中檔' THEN 2
                WHEN '外檔' THEN 3
            END
    ");
    $courseBarrierAll = $courseBarrierStmt ? $courseBarrierStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
    // Find best barrier by course (All-time)
    $bestBarrierByCourseAll = [];
    foreach ($courseBarrierAll as $item) {
        $key = $item['venue_code'] . '|' . $item['course'];
        if (!isset($bestBarrierByCourseAll[$key]) || $item['win_rate'] > $bestBarrierByCourseAll[$key]['win_rate']) {
            $bestBarrierByCourseAll[$key] = $item;
        }
    }
    
    // Venue name mapping
    $venueNames = [
        'ST' => '沙田', 'HV' => '跑馬地',
        'S1' => '悉尼', 'S2' => '墨爾本', 'S3' => '布里斯班',
        'S4' => '阿德萊德', 'S5' => '珀斯',
    ];
    
} catch (Exception $e) {
    echo '<div class="card"><div class="loading">場地數據加載失敗: ' . htmlspecialchars($e->getMessage()) . '</div></div>';
    return;
}
?>

<!-- Venue statistics with all-time and season comparison -->
<div class="card">
    <div class="card-title">🏟️ 馬場綜合統計</div>
    <div style="overflow-x: auto;">
    <table class="data-table" id="venueTable">
        <thead>
            <tr>
                <th rowspan="2">馬場</th>
                <th colspan="5">數據庫總數</th>
                <th colspan="5"><?php echo $racingSeasonLabel; ?> 馬季</th>
            </tr>
            <tr>
                <th class="sortable" data-sort="all_win_rate">勝率 <span class="sort-icon"></span></th>
                <th class="sortable" data-sort="all_top3_rate">上名率 <span class="sort-icon"></span></th>
                <th class="sortable" data-sort="all_avg_barrier">平均檔位 <span class="sort-icon"></span></th>
                <th class="sortable" data-sort="all_inside_win_rate">內檔勝率 <span class="sort-icon"></span></th>
                <th class="sortable" data-sort="all_outside_win_rate">外檔勝率 <span class="sort-icon"></span></th>
                <th class="sortable" data-sort="season_win_rate">勝率 <span class="sort-icon"></span></th>
                <th class="sortable" data-sort="season_top3_rate">上名率 <span class="sort-icon"></span></th>
                <th class="sortable" data-sort="season_avg_barrier">平均檔位 <span class="sort-icon"></span></th>
                <th class="sortable" data-sort="season_inside_win_rate">內檔勝率 <span class="sort-icon"></span></th>
                <th class="sortable" data-sort="season_outside_win_rate">外檔勝率 <span class="sort-icon"></span></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($combinedVenues as $venue): 
                $venueName = $venueNames[$venue['venue_code']] ?? $venue['venue_code'];
                $isOverseas = !in_array($venue['venue_code'], ['ST', 'HV']);
            ?>
            <tr data-all_win_rate="<?php echo $venue['all_win_rate']; ?>"
                data-all_top3_rate="<?php echo $venue['all_top3_rate']; ?>"
                data-all_avg_barrier="<?php echo $venue['all_avg_barrier']; ?>"
                data-all_inside_win_rate="<?php echo $venue['all_inside_win_rate']; ?>"
                data-all_outside_win_rate="<?php echo $venue['all_outside_win_rate']; ?>"
                data-season_win_rate="<?php echo $venue['season_win_rate']; ?>"
                data-season_top3_rate="<?php echo $venue['season_top3_rate']; ?>"
                data-season_avg_barrier="<?php echo $venue['season_avg_barrier']; ?>"
                data-season_inside_win_rate="<?php echo $venue['season_inside_win_rate']; ?>"
                data-season_outside_win_rate="<?php echo $venue['season_outside_win_rate']; ?>">
                <td>
                    <strong><?php echo $venueName; ?></strong>
                    <?php if ($isOverseas): ?>
                    <span style="font-size: 10px; background: #f39c12; padding: 2px 5px; border-radius: 5px; margin-left: 5px;">海外</span>
                    <?php endif; ?>
                </td>
                <!-- All-time -->
                <td class="all_win_rate"><span class="odds-hot"><?php echo $venue['all_win_rate']; ?>%</span></td>
                <td class="all_top3_rate"><?php echo $venue['all_top3_rate']; ?>%</td>
                <td class="all_avg_barrier"><?php echo $venue['all_avg_barrier']; ?>檔</td>
                <td class="all_inside_win_rate"><?php echo $venue['all_inside_win_rate']; ?>%</td>
                <td class="all_outside_win_rate"><?php echo $venue['all_outside_win_rate']; ?>%</td>
                <!-- Current season -->
                <td class="season_win_rate"><span class="odds-hot"><?php echo $venue['season_win_rate'] > 0 ? $venue['season_win_rate'] : '-'; ?>%</span></td>
                <td class="season_top3_rate"><?php echo $venue['season_top3_rate'] > 0 ? $venue['season_top3_rate'] : '-'; ?>%</td>
                <td class="season_avg_barrier"><?php echo $venue['season_avg_barrier'] > 0 ? $venue['season_avg_barrier'] : '-'; ?>檔</td>
                <td class="season_inside_win_rate"><?php echo $venue['season_inside_win_rate'] > 0 ? $venue['season_inside_win_rate'] : '-'; ?>%</td>
                <td class="season_outside_win_rate"><?php echo $venue['season_outside_win_rate'] > 0 ? $venue['season_outside_win_rate'] : '-'; ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Filter section -->
<div class="card">
    <div class="card-title">🔍 賽道 x 途程 x 檔位 篩選器</div>
    <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
        <div style="flex: 1; min-width: 150px;">
            <label style="display: block; margin-bottom: 5px; font-size: 12px;">馬場</label>
            <select id="filterVenue" style="width: 100%; padding: 8px; background: #0f3460; border: 1px solid #2a3a4a; border-radius: 8px; color: white;">
                <option value="">全部馬場</option>
                <?php foreach ($allVenues as $v): 
                    $vName = $venueNames[$v] ?? $v;
                ?>
                <option value="<?php echo $v; ?>" <?php echo $filterVenue == $v ? 'selected' : ''; ?>><?php echo $vName; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="flex: 2; min-width: 200px;">
            <label style="display: block; margin-bottom: 5px; font-size: 12px;">賽道</label>
            <select id="filterCourse" style="width: 100%; padding: 8px; background: #0f3460; border: 1px solid #2a3a4a; border-radius: 8px; color: white;">
                <option value="">全部賽道</option>
                <?php foreach ($allCourses as $c): ?>
                <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $filterCourse == $c ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="flex: 1; min-width: 120px;">
            <label style="display: block; margin-bottom: 5px; font-size: 12px;">途程(米)</label>
            <select id="filterDistance" style="width: 100%; padding: 8px; background: #0f3460; border: 1px solid #2a3a4a; border-radius: 8px; color: white;">
                <option value="">全部途程</option>
                <?php foreach ($allDistances as $d): ?>
                <option value="<?php echo $d; ?>" <?php echo $filterDistance == $d ? 'selected' : ''; ?>><?php echo $d; ?>m</option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div id="analysisTableContainer" style="overflow-x: auto; margin-top: 20px;">
        <table class="data-table" id="analysisTable">
            <thead>
                <tr>
                    <th>馬場</th>
                    <th>賽道</th>
                    <th>途程(米)</th>
                    <th>檔位組別</th>
                    <th>出賽</th>
                    <th>勝率</th>
                    <th>上名率</th>
                    <th>策略建議</th>
                </tr>
            </thead>
            <tbody id="analysisTableBody">
                <tr><td colspan="8" class="loading">請使用篩選器加載數據...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Distance best barrier analysis -->
<div class="card">
    <div class="card-title">📏 途程最佳檔位分析</div>
    <?php if (empty($bestBarrierByDistanceAll)): ?>
    <div class="loading">暫無途程數據</div>
    <?php else: ?>
    <div style="overflow-x: auto;">
    <table class="data-table">
        <thead>
            <tr>
                <th>途程(米)</th>
                <th>最佳檔位組別</th>
                <th>出賽次數</th>
                <th>勝率</th>
                <th>策略建議</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bestBarrierByDistanceAll as $distance => $best): ?>
            <tr>
                <td><strong><?php echo $distance; ?>m</strong></td>
                <td><span class="odds-hot"><?php echo $best['barrier_group']; ?></span></td>
                <td><?php echo number_format($best['runs']); ?>次</td>
                <td><?php echo $best['win_rate']; ?>%</td>
                <td>
                    <?php if ($best['barrier_group'] == '內檔'): ?>
                    💡 短途賽事內檔有優勢
                    <?php elseif ($best['barrier_group'] == '中檔'): ?>
                    ⚖️ 中檔較為平衡
                    <?php else: ?>
                    🏃 長途賽事外檔影響減小
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- Course best barrier analysis -->
<div class="card">
    <div class="card-title">🎯 賽道最佳檔位分析</div>
    <?php if (empty($bestBarrierByCourseAll)): ?>
    <div class="loading">暫無賽道數據</div>
    <?php else: ?>
    <div style="overflow-x: auto;">
    <table class="data-table">
        <thead>
            <tr>
                <th>馬場</th>
                <th>賽道</th>
                <th>最佳檔位</th>
                <th>出賽</th>
                <th>勝率</th>
                <th>備註</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bestBarrierByCourseAll as $key => $best): 
                $venueName = $venueNames[$best['venue_code']] ?? $best['venue_code'];
                $courseShort = str_replace(['跑馬地草地', '沙田草地'], ['谷草', '田草'], $best['course']);
            ?>
            <tr>
                <td><strong><?php echo $venueName; ?></strong></td>
                <td><?php echo $courseShort; ?>賽道</td>
                <td><span class="odds-hot"><?php echo $best['barrier_group']; ?></span></td>
                <td><?php echo number_format($best['runs']); ?>次</td>
                <td><?php echo $best['win_rate']; ?>%</td>
                <td>
                    <?php if (strpos($best['course'], 'A') !== false): ?>
                    A賽道利內檔
                    <?php elseif (strpos($best['course'], 'C') !== false): ?>
                    C賽道利中外檔
                    <?php else: ?>
                    各檔位差異較小
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- Strategy summary -->
<div class="card">
    <div class="card-title">💡 檔位策略總結</div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px;">
        <?php 
        $stAll = $allTimeVenueMap['ST'] ?? null;
        $hvAll = $allTimeVenueMap['HV'] ?? null;
        $stSeason = $seasonVenueMap['ST'] ?? null;
        $hvSeason = $seasonVenueMap['HV'] ?? null;
        ?>
        <div style="background: #0f3460; padding: 15px; border-radius: 10px;">
            <h4 style="color: #e94560; margin-bottom: 10px;">🎯 沙田 (ST)</h4>
            <p>📊 數據庫總數: 平均檔位 <?php echo $stAll['avg_barrier'] ?? '7.0'; ?>檔 | 內檔勝率 <?php echo $stAll && ($stAll['inside_runs'] ?? 0) > 0 ? round($stAll['inside_wins'] / $stAll['inside_runs'] * 100, 1) : 0; ?>% | 外檔勝率 <?php echo $stAll && ($stAll['outside_runs'] ?? 0) > 0 ? round($stAll['outside_wins'] / $stAll['outside_runs'] * 100, 1) : 0; ?>%</p>
            <p>📈 <?php echo $racingSeasonLabel; ?> 馬季: 平均檔位 <?php echo $stSeason['avg_barrier'] ?? '-'; ?>檔 | 內檔勝率 <?php echo $stSeason && ($stSeason['inside_runs'] ?? 0) > 0 ? round($stSeason['inside_wins'] / $stSeason['inside_runs'] * 100, 1) : 0; ?>% | 外檔勝率 <?php echo $stSeason && ($stSeason['outside_runs'] ?? 0) > 0 ? round($stSeason['outside_wins'] / $stSeason['outside_runs'] * 100, 1) : 0; ?>%</p>
            <p>💡 建議: 1000-1400米優先考慮內檔，1600米以上檔位影響減小</p>
        </div>
        <div style="background: #0f3460; padding: 15px; border-radius: 10px;">
            <h4 style="color: #e94560; margin-bottom: 10px;">🎯 跑馬地 (HV)</h4>
            <p>📊 數據庫總數: 平均檔位 <?php echo $hvAll['avg_barrier'] ?? '6.4'; ?>檔 | 內檔勝率 <?php echo $hvAll && ($hvAll['inside_runs'] ?? 0) > 0 ? round($hvAll['inside_wins'] / $hvAll['inside_runs'] * 100, 1) : 0; ?>% | 外檔勝率 <?php echo $hvAll && ($hvAll['outside_runs'] ?? 0) > 0 ? round($hvAll['outside_wins'] / $hvAll['outside_runs'] * 100, 1) : 0; ?>%</p>
            <p>📈 <?php echo $racingSeasonLabel; ?> 馬季: 平均檔位 <?php echo $hvSeason['avg_barrier'] ?? '-'; ?>檔 | 內檔勝率 <?php echo $hvSeason && ($hvSeason['inside_runs'] ?? 0) > 0 ? round($hvSeason['inside_wins'] / $hvSeason['inside_runs'] * 100, 1) : 0; ?>% | 外檔勝率 <?php echo $hvSeason && ($hvSeason['outside_runs'] ?? 0) > 0 ? round($hvSeason['outside_wins'] / $hvSeason['outside_runs'] * 100, 1) : 0; ?>%</p>
            <p>💡 建議: 跑馬地彎位較急，內檔優勢更明顯</p>
        </div>
    </div>
    <div style="margin-top: 15px; padding: 12px; background: #1e2a3a; border-radius: 8px; font-size: 13px;">
        <strong>📌 Note:</strong> Barrier draw is just one reference factor. Actual performance also depends on horse ability, jockey performance, track conditions, etc.
    </div>
</div>

<!-- Barrier explanation -->
<div class="card">
    <div class="card-title">📖 檔位說明</div>
    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        <div><span style="background: #27ae60; padding: 5px 12px; border-radius: 20px;">內檔 (1-3檔)</span> - Inside position, shorter turns, suitable for front runners</div>
        <div><span style="background: #f39c12; padding: 5px 12px; border-radius: 20px;">中檔 (4-6檔)</span> - Balanced position, flexible for attack or defense</div>
        <div><span style="background: #e74c3c; padding: 5px 12px; border-radius: 20px;">外檔 (7檔或以上)</span> - Outside position, longer turns, suitable for closers</div>
    </div>
</div>

<style>
.sortable {
    cursor: pointer;
    user-select: none;
    position: relative;
    padding-right: 20px !important;
}
.sortable:hover {
    background: #2a3a4a;
}
.sort-icon {
    display: inline-block;
    width: 12px;
    margin-left: 5px;
    font-size: 10px;
    color: #8899aa;
}
.sortable.asc .sort-icon::before {
    content: '▲';
}
.sortable.desc .sort-icon::before {
    content: '▼';
}
.sortable .sort-icon::before {
    content: '⇅';
    opacity: 0.5;
}
</style>

<script>
$(document).ready(function() {
    // Initialize sortable on venue table
    initSortableTable('venueTable');
    
    // Initial load of analysis data (if filter conditions exist)
    if ($('#filterVenue').val() || $('#filterCourse').val() || $('#filterDistance').val()) {
        loadAnalysisData();
    }
    
    // Real-time update: refresh data when dropdown changes
    $('#filterVenue, #filterCourse, #filterDistance').change(function() {
        loadAnalysisData();
    });
});

function initSortableTable(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const headers = table.querySelectorAll('.sortable');
    let currentSort = { column: 'all_win_rate', order: 'desc' };
    
    function sortTable(column, order) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        const getValue = (row, col) => {
            const val = row.getAttribute('data-' + col);
            return parseFloat(val) || 0;
        };
        
        rows.sort((a, b) => {
            const aVal = getValue(a, column);
            const bVal = getValue(b, column);
            if (order === 'asc') {
                return aVal - bVal;
            } else {
                return bVal - aVal;
            }
        });
        
        rows.forEach(row => {
            tbody.appendChild(row);
        });
    }
    
    function updateHeaderStyle(column, order) {
        headers.forEach(header => {
            header.classList.remove('asc', 'desc');
            const sortCol = header.getAttribute('data-sort');
            if (sortCol === column) {
                header.classList.add(order);
            }
        });
    }
    
    headers.forEach(header => {
        header.addEventListener('click', () => {
            const column = header.getAttribute('data-sort');
            let newOrder = 'desc';
            if (currentSort.column === column) {
                newOrder = currentSort.order === 'desc' ? 'asc' : 'desc';
            }
            currentSort = { column: column, order: newOrder };
            sortTable(column, newOrder);
            updateHeaderStyle(column, newOrder);
        });
    });
    
    sortTable('all_win_rate', 'desc');
    updateHeaderStyle('all_win_rate', 'desc');
}

function loadAnalysisData() {
    var venue = $('#filterVenue').val();
    var course = $('#filterCourse').val();
    var distance = $('#filterDistance').val();
    
    $('#analysisTableBody').html('<tr><td colspan="8" class="loading">加載中...</td></tr>');
    
    $.ajax({
        url: 'analysis_api.php',
        type: 'GET',
        data: {
            action: 'trackFilter',
            venue: venue,
            course: course,
            distance: distance
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                var html = '';
                for (var i = 0; i < response.data.length; i++) {
                    var item = response.data[i];
                    var barrierAdvice = '';
                    var barrierClass = '';
                    if (item.win_rate >= 12) {
                        barrierAdvice = '⭐ 強勢檔位';
                        barrierClass = 'odds-hot';
                    } else if (item.win_rate >= 8) {
                        barrierAdvice = '✓ 可考慮';
                        barrierClass = 'odds-up';
                    } else {
                        barrierAdvice = '⚠️ 謹慎';
                        barrierClass = 'odds-down';
                    }
                    
                    html += '<tr>';
                    html += '<td>' + escapeHtml(item.venue_name) + '</td>';
                    html += '<td>' + escapeHtml(item.course_short) + '賽道</td>';
                    html += '<td>' + item.distance + 'm</td>';
                    html += '<td><strong>' + item.barrier_group + '</strong></td>';
                    html += '<td>' + numberFormat(item.runs) + '次</td>';
                    html += '<td><span class="odds-hot">' + item.win_rate + '%</span></td>';
                    html += '<td>' + item.top3_rate + '%</td>';
                    html += '<td><span class="' + barrierClass + '">' + barrierAdvice + '</span></td>';
                    html += '</tr>';
                }
                $('#analysisTableBody').html(html);
            } else {
                $('#analysisTableBody').html('<tr><td colspan="8" class="loading">暫無數據</td></tr>');
            }
        },
        error: function() {
            $('#analysisTableBody').html('<tr><td colspan="8" class="loading">加載失敗，請重試</td></tr>');
        }
    });
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

function numberFormat(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
</script>