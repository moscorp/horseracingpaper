<?php
// analysis_horses.php - Horse win rate ranking component

try {
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
    
    // ========== All-time statistics (database total) ==========
    $allTimeSql = "
        SELECT 
            r.name_ch as name_ch,
            MAX(hp.trainer_name_ch) as trainer_name_ch,
            COUNT(*) as total_starts,
            SUM(CASE WHEN hp.finishing_position = 1 THEN 1 ELSE 0 END) as total_wins,
            SUM(CASE WHEN hp.finishing_position = 2 THEN 1 ELSE 0 END) as total_seconds,
            SUM(CASE WHEN hp.finishing_position = 3 THEN 1 ELSE 0 END) as total_thirds,
            ROUND(SUM(CASE WHEN hp.finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate,
            ROUND(SUM(CASE WHEN hp.finishing_position <= 3 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as place_rate
        FROM hkracing_horse_performances hp
        INNER JOIN hkracing_runners r ON hp.horse_code = r.horse_code
        WHERE hp.finishing_position IS NOT NULL
            AND hp.finishing_position > 0
            AND r.name_ch IS NOT NULL
        GROUP BY r.name_ch
        HAVING total_starts >= 3
        ORDER BY win_rate DESC
        LIMIT 100
    ";
    
    $allTimeStmt = $pdo->query($allTimeSql);
    $allTimeHorses = $allTimeStmt ? $allTimeStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
    // Build map for all-time data
    $allTimeMap = [];
    foreach ($allTimeHorses as $horse) {
        $allTimeMap[$horse['name_ch']] = $horse;
    }
    
    // ========== Current season statistics ==========
    $seasonSql = "
        SELECT 
            r.name_ch as name_ch,
            MAX(hp.trainer_name_ch) as trainer_name_ch,
            COUNT(*) as total_starts,
            SUM(CASE WHEN hp.finishing_position = 1 THEN 1 ELSE 0 END) as total_wins,
            SUM(CASE WHEN hp.finishing_position = 2 THEN 1 ELSE 0 END) as total_seconds,
            SUM(CASE WHEN hp.finishing_position = 3 THEN 1 ELSE 0 END) as total_thirds,
            ROUND(SUM(CASE WHEN hp.finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate,
            ROUND(SUM(CASE WHEN hp.finishing_position <= 3 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as place_rate
        FROM hkracing_horse_performances hp
        INNER JOIN hkracing_runners r ON hp.horse_code = r.horse_code
        WHERE hp.finishing_position IS NOT NULL
            AND hp.finishing_position > 0
            AND r.name_ch IS NOT NULL
            AND hp.race_date >= '{$seasonStartDate}'
        GROUP BY r.name_ch
        HAVING total_starts >= 3
        ORDER BY win_rate DESC
        LIMIT 100
    ";
    
    $seasonStmt = $pdo->query($seasonSql);
    $seasonHorses = $seasonStmt ? $seasonStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
    // Build map for season data
    $seasonMap = [];
    foreach ($seasonHorses as $horse) {
        $seasonMap[$horse['name_ch']] = $horse;
    }
    
    // ========== Merge display: take union of both datasets ==========
    $allHorseNames = array_unique(array_merge(
        array_keys($allTimeMap),
        array_keys($seasonMap)
    ));
    
    // Merge data
    $combinedData = [];
    foreach ($allHorseNames as $name) {
        $allTime = $allTimeMap[$name] ?? null;
        $season = $seasonMap[$name] ?? null;
        
        if ($allTime || $season) {
            $combinedData[] = [
                'name' => $name,
                'trainer' => $allTime['trainer_name_ch'] ?? $season['trainer_name_ch'] ?? '',
                'all_starts' => $allTime['total_starts'] ?? 0,
                'all_wins' => $allTime['total_wins'] ?? 0,
                'all_seconds' => $allTime['total_seconds'] ?? 0,
                'all_thirds' => $allTime['total_thirds'] ?? 0,
                'all_win_rate' => $allTime['win_rate'] ?? 0,
                'all_place_rate' => $allTime['place_rate'] ?? 0,
                'season_starts' => $season['total_starts'] ?? 0,
                'season_wins' => $season['total_wins'] ?? 0,
                'season_seconds' => $season['total_seconds'] ?? 0,
                'season_thirds' => $season['total_thirds'] ?? 0,
                'season_win_rate' => $season['win_rate'] ?? 0,
                'season_place_rate' => $season['place_rate'] ?? 0,
            ];
        }
    }
    
    // Default sort by all-time win rate descending
    usort($combinedData, function($a, $b) {
        return $b['all_win_rate'] <=> $a['all_win_rate'];
    });
    
    // Top card statistics
    $seasonActiveHorses = count($seasonHorses);
    $allActiveHorses = count($allTimeHorses);
    
} catch (Exception $e) {
    echo '<div class="card"><div class="loading">馬匹數據加載失敗: ' . htmlspecialchars($e->getMessage()) . '</div></div>';
    return;
}
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($seasonActiveHorses); ?></div>
        <div class="stat-label">活躍馬匹數量 <span class="stat-note">(<?php echo $racingSeasonLabel; ?> 馬季)</span></div>
        <div class="stat-total-ref">數據庫總數: <?php echo number_format($allActiveHorses); ?></div>
    </div>
</div>

<div class="card">
    <div class="card-title">🏆 馬匹勝率排行榜 (出賽≥3場)</div>
    <?php if (empty($combinedData)): ?>
    <div class="loading">暫無足夠的馬匹數據</div>
    <?php else: ?>
    <div style="overflow-x: auto;">
        <table class="data-table" id="horseTable">
            <thead>
                <tr>
                    <th rowspan="2">排名</th>
                    <th rowspan="2">馬匹</th>
                    <th rowspan="2">練馬師</th>
                    <th colspan="5">數據庫總數</th>
                    <th colspan="5"><?php echo $racingSeasonLabel; ?> 馬季</th>
                </tr>
                <tr>
                    <th class="sortable" data-sort="all_starts">出賽 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="all_wins">頭馬 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="all_seconds">亞軍 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="all_thirds">季軍 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="all_win_rate">勝率 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="season_starts">出賽 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="season_wins">頭馬 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="season_seconds">亞軍 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="season_thirds">季軍 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="season_win_rate">勝率 <span class="sort-icon"></span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($combinedData as $i => $horse): ?>
                <tr data-name="<?php echo htmlspecialchars($horse['name']); ?>"
                    data-trainer="<?php echo htmlspecialchars($horse['trainer']); ?>"
                    data-all_starts="<?php echo $horse['all_starts']; ?>"
                    data-all_wins="<?php echo $horse['all_wins']; ?>"
                    data-all_seconds="<?php echo $horse['all_seconds']; ?>"
                    data-all_thirds="<?php echo $horse['all_thirds']; ?>"
                    data-all_win_rate="<?php echo $horse['all_win_rate']; ?>"
                    data-all_place_rate="<?php echo $horse['all_place_rate']; ?>"
                    data-season_starts="<?php echo $horse['season_starts']; ?>"
                    data-season_wins="<?php echo $horse['season_wins']; ?>"
                    data-season_seconds="<?php echo $horse['season_seconds']; ?>"
                    data-season_thirds="<?php echo $horse['season_thirds']; ?>"
                    data-season_win_rate="<?php echo $horse['season_win_rate']; ?>"
                    data-season_place_rate="<?php echo $horse['season_place_rate']; ?>">
                    <td class="rank"><?php echo $i + 1; ?></td>
                    <td><strong><?php echo htmlspecialchars($horse['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($horse['trainer']); ?></td>
                    <!-- Database total -->
                    <td class="all_starts"><?php echo $horse['all_starts'] > 0 ? number_format($horse['all_starts']) : '-'; ?></td>
                    <td class="all_wins"><?php echo $horse['all_wins'] > 0 ? $horse['all_wins'] : '-'; ?></span></td>
                    <td class="all_seconds"><?php echo $horse['all_seconds'] > 0 ? $horse['all_seconds'] : '-'; ?></span></td>
                    <td class="all_thirds"><?php echo $horse['all_thirds'] > 0 ? $horse['all_thirds'] : '-'; ?></span></td>
                    <td class="all_win_rate"><?php echo $horse['all_win_rate'] > 0 ? '<span class="odds-hot">'.$horse['all_win_rate'].'%</span>' : '-'; ?></span></td>
                    <!-- Current season -->
                    <td class="season_starts"><?php echo $horse['season_starts'] > 0 ? number_format($horse['season_starts']) : '-'; ?></span></td>
                    <td class="season_wins"><?php echo $horse['season_wins'] > 0 ? $horse['season_wins'] : '-'; ?></span></td>
                    <td class="season_seconds"><?php echo $horse['season_seconds'] > 0 ? $horse['season_seconds'] : '-'; ?></span></td>
                    <td class="season_thirds"><?php echo $horse['season_thirds'] > 0 ? $horse['season_thirds'] : '-'; ?></span></td>
                    <td class="season_win_rate"><?php echo $horse['season_win_rate'] > 0 ? '<span class="odds-hot">'.$horse['season_win_rate'].'%</span>' : '-'; ?></span></td>
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
    background: #0f3460;
}
.data-table td {
    text-align: center;
}
.data-table td:first-child,
.data-table td:nth-child(2),
.data-table td:nth-child(3) {
    text-align: left;
}
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
(function() {
    const table = document.getElementById('horseTable');
    if (!table) return;
    
    const headers = table.querySelectorAll('.sortable');
    let currentSort = { column: 'all_win_rate', order: 'desc' };
    
    // Sorting function
    function sortTable(column, order) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        // Get comparison value
        const getValue = (row, col) => {
            const val = row.getAttribute('data-' + col);
            return parseFloat(val) || 0;
        };
        
        const getStringValue = (row, col) => {
            return row.getAttribute('data-' + col) || '';
        };
        
        // Sort
        rows.sort((a, b) => {
            let aVal, bVal;
            
            // String columns (name, trainer)
            if (column === 'name' || column === 'trainer') {
                aVal = getStringValue(a, column);
                bVal = getStringValue(b, column);
                if (order === 'asc') {
                    return aVal.localeCompare(bVal);
                } else {
                    return bVal.localeCompare(aVal);
                }
            } 
            // Numeric columns
            else {
                aVal = getValue(a, column);
                bVal = getValue(b, column);
                if (order === 'asc') {
                    return aVal - bVal;
                } else {
                    return bVal - aVal;
                }
            }
        });
        
        // Reorder rows and update rank
        rows.forEach((row, index) => {
            tbody.appendChild(row);
            const rankCell = row.querySelector('.rank');
            if (rankCell) rankCell.textContent = index + 1;
        });
    }
    
    // Update header style
    function updateHeaderStyle(column, order) {
        headers.forEach(header => {
            header.classList.remove('asc', 'desc');
            const sortCol = header.getAttribute('data-sort');
            if (sortCol === column) {
                header.classList.add(order);
            }
        });
    }
    
    // Bind click events
    headers.forEach(header => {
        header.addEventListener('click', () => {
            const column = header.getAttribute('data-sort');
            let newOrder = 'desc';
            
            // If clicking the same column, toggle order
            if (currentSort.column === column) {
                newOrder = currentSort.order === 'desc' ? 'asc' : 'desc';
            }
            
            currentSort = { column: column, order: newOrder };
            sortTable(column, newOrder);
            updateHeaderStyle(column, newOrder);
        });
    });
    
    // Initialize: sort by all-time win rate descending
    sortTable('all_win_rate', 'desc');
    updateHeaderStyle('all_win_rate', 'desc');
})();
</script>