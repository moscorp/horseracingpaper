<?php
// analysis_trainers.php - 練馬師勝率榜组件

try {
    // 获取马季信息（香港马季从9月到次年8月）
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
    
    // ========== 数据库总数统计（全历史） ==========
    $stmt = $pdo->query("
        SELECT 
            h.trainer_name_ch,
            COUNT(*) as runs,
            SUM(CASE WHEN p.finishing_position = 1 THEN 1 ELSE 0 END) as wins,
            SUM(CASE WHEN p.finishing_position <= 3 THEN 1 ELSE 0 END) as top3,
            ROUND(SUM(CASE WHEN p.finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate,
            ROUND(SUM(CASE WHEN p.finishing_position <= 3 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as place_rate
        FROM hkracing_horse_performances p
        INNER JOIN hkracing_horses h ON p.horse_code = h.horse_code
        WHERE h.trainer_name_ch IS NOT NULL AND h.trainer_name_ch != ''
        GROUP BY h.trainer_name_ch
        HAVING runs >= 10
        ORDER BY win_rate DESC
        LIMIT 30
    ");
    $allStats = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
    $allStatsMap = [];
    foreach ($allStats as $stat) {
        $allStatsMap[$stat['trainer_name_ch']] = $stat;
    }
    
    // ========== 当前马季统计 ==========
    $stmt = $pdo->prepare("
        SELECT 
            h.trainer_name_ch,
            COUNT(*) as runs,
            SUM(CASE WHEN p.finishing_position = 1 THEN 1 ELSE 0 END) as wins,
            SUM(CASE WHEN p.finishing_position <= 3 THEN 1 ELSE 0 END) as top3,
            ROUND(SUM(CASE WHEN p.finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate,
            ROUND(SUM(CASE WHEN p.finishing_position <= 3 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as place_rate
        FROM hkracing_horse_performances p
        INNER JOIN hkracing_horses h ON p.horse_code = h.horse_code
        WHERE h.trainer_name_ch IS NOT NULL AND h.trainer_name_ch != ''
            AND p.race_date >= :season_start
        GROUP BY h.trainer_name_ch
        HAVING runs >= 5
        ORDER BY win_rate DESC
        LIMIT 30
    ");
    $stmt->execute([':season_start' => $seasonStartDate]);
    $seasonStats = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
    $seasonStatsMap = [];
    foreach ($seasonStats as $stat) {
        $seasonStatsMap[$stat['trainer_name_ch']] = $stat;
    }
    
    // ========== 合并显示 ==========
    $allTrainerNames = array_unique(array_merge(
        array_keys($allStatsMap),
        array_keys($seasonStatsMap)
    ));
    
    $combinedData = [];
    foreach ($allTrainerNames as $name) {
        $all = $allStatsMap[$name] ?? null;
        $season = $seasonStatsMap[$name] ?? null;
        
        if ($all || $season) {
            $combinedData[] = [
                'name' => $name,
                'all_runs' => $all['runs'] ?? 0,
                'all_wins' => $all['wins'] ?? 0,
                'all_top3' => $all['top3'] ?? 0,
                'all_win_rate' => $all['win_rate'] ?? 0,
                'all_place_rate' => $all['place_rate'] ?? 0,
                'season_runs' => $season['runs'] ?? 0,
                'season_wins' => $season['wins'] ?? 0,
                'season_top3' => $season['top3'] ?? 0,
                'season_win_rate' => $season['win_rate'] ?? 0,
                'season_place_rate' => $season['place_rate'] ?? 0,
            ];
        }
    }
    
    usort($combinedData, function($a, $b) {
        return $b['all_win_rate'] <=> $a['all_win_rate'];
    });
    
    $seasonActiveTrainers = count($seasonStats);
    $seasonTotalRuns = array_sum(array_column($seasonStats, 'runs'));
    $allActiveTrainers = count($allStats);
    $allTotalRuns = array_sum(array_column($allStats, 'runs'));
    
} catch (Exception $e) {
    echo '<div class="card"><div class="loading">練馬師數據加載失敗</div></div>';
    return;
}
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($seasonActiveTrainers); ?></div>
        <div class="stat-label">活躍練馬師人數 <span class="stat-note">(<?php echo $racingSeasonLabel; ?> 馬季)</span></div>
        <div class="stat-total-ref">數據庫總數: <?php echo number_format($allActiveTrainers); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($seasonTotalRuns); ?></div>
        <div class="stat-label">總出馬次數 <span class="stat-note">(<?php echo $racingSeasonLabel; ?> 馬季)</span></div>
        <div class="stat-total-ref">數據庫總數: <?php echo number_format($allTotalRuns); ?></div>
    </div>
</div>

<div class="card">
    <div class="card-title">🏆 練馬師勝率排行榜</div>
    <?php if (empty($combinedData)): ?>
    <div class="loading">暫無足夠的練馬師數據</div>
    <?php else: ?>
    <div style="overflow-x: auto;">
        <table class="data-table" id="trainerTable">
            <thead>
                <tr>
                    <th rowspan="2">排名</th>
                    <th rowspan="2">練馬師</th>
                    <th colspan="5">數據庫總數</th>
                    <th colspan="5"><?php echo $racingSeasonLabel; ?> 馬季</th>
                </tr>
                <tr>
                    <th class="sortable" data-sort="all_runs">出馬 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="all_wins">頭馬 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="all_top3">上名 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="all_win_rate">勝率 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="all_place_rate">上名率 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="season_runs">出馬 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="season_wins">頭馬 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="season_top3">上名 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="season_win_rate">勝率 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="season_place_rate">上名率 <span class="sort-icon"></span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($combinedData as $i => $trainer): ?>
                <tr data-name="<?php echo htmlspecialchars($trainer['name']); ?>"
                    data-all_runs="<?php echo $trainer['all_runs']; ?>"
                    data-all_wins="<?php echo $trainer['all_wins']; ?>"
                    data-all_top3="<?php echo $trainer['all_top3']; ?>"
                    data-all_win_rate="<?php echo $trainer['all_win_rate']; ?>"
                    data-all_place_rate="<?php echo $trainer['all_place_rate']; ?>"
                    data-season_runs="<?php echo $trainer['season_runs']; ?>"
                    data-season_wins="<?php echo $trainer['season_wins']; ?>"
                    data-season_top3="<?php echo $trainer['season_top3']; ?>"
                    data-season_win_rate="<?php echo $trainer['season_win_rate']; ?>"
                    data-season_place_rate="<?php echo $trainer['season_place_rate']; ?>">
                    <td class="rank"><?php echo $i + 1; ?></td>
                    <td><strong><?php echo htmlspecialchars($trainer['name']); ?></strong></td>
                    <td class="all_runs"><?php echo $trainer['all_runs'] > 0 ? number_format($trainer['all_runs']) : '-'; ?></td>
                    <td class="all_wins"><?php echo $trainer['all_wins'] > 0 ? $trainer['all_wins'] : '-'; ?></td>
                    <td class="all_top3"><?php echo $trainer['all_top3'] > 0 ? $trainer['all_top3'] : '-'; ?></td>
                    <td class="all_win_rate"><?php echo $trainer['all_win_rate'] > 0 ? '<span class="odds-hot">'.$trainer['all_win_rate'].'%</span>' : '-'; ?></td>
                    <td class="all_place_rate"><?php echo $trainer['all_place_rate'] > 0 ? $trainer['all_place_rate'].'%' : '-'; ?></td>
                    <td class="season_runs"><?php echo $trainer['season_runs'] > 0 ? number_format($trainer['season_runs']) : '-'; ?></td>
                    <td class="season_wins"><?php echo $trainer['season_wins'] > 0 ? $trainer['season_wins'] : '-'; ?></td>
                    <td class="season_top3"><?php echo $trainer['season_top3'] > 0 ? $trainer['season_top3'] : '-'; ?></td>
                    <td class="season_win_rate"><?php echo $trainer['season_win_rate'] > 0 ? '<span class="odds-hot">'.$trainer['season_win_rate'].'%</span>' : '-'; ?></td>
                    <td class="season_place_rate"><?php echo $trainer['season_place_rate'] > 0 ? $trainer['season_place_rate'].'%' : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<style>
.stat-note {
    font-size: 0.7rem;
    font-weight: normal;
    color: #888;
}
.stat-total-ref {
    font-size: 0.7rem;
    color: #aaa;
    margin-top: 4px;
    border-top: 1px dashed #eee;
    padding-top: 4px;
}
.data-table th {
    text-align: center;
    background: #f5f5f5;
}
.data-table td {
    text-align: center;
}
.data-table td:first-child,
.data-table td:nth-child(2) {
    text-align: left;
}
.sortable {
    cursor: pointer;
    user-select: none;
    position: relative;
    padding-right: 20px !important;
}
.sortable:hover {
    background-color: #e8e8e8;
}
.sort-icon {
    display: inline-block;
    width: 12px;
    margin-left: 5px;
    font-size: 10px;
    color: #999;
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
.odds-hot {
    font-weight: bold;
    color: #ffffff;
}
</style>

<script>
(function() {
    const table = document.getElementById('trainerTable');
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
            let aVal = getValue(a, column);
            let bVal = getValue(b, column);
            if (order === 'asc') {
                return aVal - bVal;
            } else {
                return bVal - aVal;
            }
        });
        
        rows.forEach((row, index) => {
            tbody.appendChild(row);
            const rankCell = row.querySelector('.rank');
            if (rankCell) rankCell.textContent = index + 1;
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
})();
</script>