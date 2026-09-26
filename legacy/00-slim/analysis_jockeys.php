<?php
// analysis_jockeys.php - 騎師勝率榜组件

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
    
    // ========== 获取所有骑师（数据库总数） ==========
    $stmt = $pdo->query("
        SELECT DISTINCT jockey_name_ch
        FROM hkracing_horse_performances
        WHERE jockey_name_ch IS NOT NULL AND jockey_name_ch != ''
        ORDER BY jockey_name_ch
    ");
    $allJockeysList = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    
    // ========== 数据库总数统计（全历史） ==========
    $stmt = $pdo->query("
        SELECT 
            jockey_name_ch,
            COUNT(*) as rides,
            SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
            SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) as top3,
            ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate,
            ROUND(SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as place_rate
        FROM hkracing_horse_performances
        WHERE jockey_name_ch IS NOT NULL AND jockey_name_ch != ''
        GROUP BY jockey_name_ch
        HAVING rides >= 10
        ORDER BY win_rate DESC
        LIMIT 30
    ");
    $allStats = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
    // 建立骑师名称到全历史数据的映射
    $allStatsMap = [];
    foreach ($allStats as $stat) {
        $allStatsMap[$stat['jockey_name_ch']] = $stat;
    }
    
    // ========== 当前马季统计 ==========
    $stmt = $pdo->prepare("
        SELECT 
            jockey_name_ch,
            COUNT(*) as rides,
            SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
            SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) as top3,
            ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate,
            ROUND(SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as place_rate
        FROM hkracing_horse_performances
        WHERE jockey_name_ch IS NOT NULL AND jockey_name_ch != ''
            AND race_date >= :season_start
        GROUP BY jockey_name_ch
        HAVING rides >= 5
        ORDER BY win_rate DESC
        LIMIT 30
    ");
    $stmt->execute([':season_start' => $seasonStartDate]);
    $seasonStats = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
    // 建立骑师名称到当前马季数据的映射
    $seasonStatsMap = [];
    foreach ($seasonStats as $stat) {
        $seasonStatsMap[$stat['jockey_name_ch']] = $stat;
    }
    
    // ========== 合并显示：取两个统计的并集 ==========
    $allJockeyNames = array_unique(array_merge(
        array_keys($allStatsMap),
        array_keys($seasonStatsMap)
    ));
    
    // 合并数据
    $combinedData = [];
    foreach ($allJockeyNames as $name) {
        $all = $allStatsMap[$name] ?? null;
        $season = $seasonStatsMap[$name] ?? null;
        
        if ($all || $season) {
            $combinedData[] = [
                'name' => $name,
                'all_rides' => $all['rides'] ?? 0,
                'all_wins' => $all['wins'] ?? 0,
                'all_top3' => $all['top3'] ?? 0,
                'all_win_rate' => $all['win_rate'] ?? 0,
                'all_place_rate' => $all['place_rate'] ?? 0,
                'season_rides' => $season['rides'] ?? 0,
                'season_wins' => $season['wins'] ?? 0,
                'season_top3' => $season['top3'] ?? 0,
                'season_win_rate' => $season['win_rate'] ?? 0,
                'season_place_rate' => $season['place_rate'] ?? 0,
            ];
        }
    }
    
    // 默认按全历史胜率排序
    usort($combinedData, function($a, $b) {
        return $b['all_win_rate'] <=> $a['all_win_rate'];
    });
    
    // 顶部卡片统计
    $seasonActiveJockeys = count($seasonStats);
    $seasonTotalRides = array_sum(array_column($seasonStats, 'rides'));
    $allActiveJockeys = count($allStats);
    $allTotalRides = array_sum(array_column($allStats, 'rides'));
    
} catch (Exception $e) {
    echo '<div class="card"><div class="loading">騎師數據加載失敗</div></div>';
    return;
}
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($seasonActiveJockeys); ?></div>
        <div class="stat-label">活躍騎師人數 <span class="stat-note">(<?php echo $racingSeasonLabel; ?> 馬季)</span></div>
        <div class="stat-total-ref">數據庫總數: <?php echo number_format($allActiveJockeys); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($seasonTotalRides); ?></div>
        <div class="stat-label">總出賽次數 <span class="stat-note">(<?php echo $racingSeasonLabel; ?> 馬季)</span></div>
        <div class="stat-total-ref">數據庫總數: <?php echo number_format($allTotalRides); ?></div>
    </div>
</div>

<div class="card">
    <div class="card-title">🏆 騎師勝率排行榜</div>
    <?php if (empty($combinedData)): ?>
    <div class="loading">暫無足夠的騎師數據</div>
    <?php else: ?>
    <div style="overflow-x: auto;">
        <table class="data-table" id="jockeyTable">
            <thead>
                <tr>
                    <th rowspan="2">排名</th>
                    <th rowspan="2">騎師</th>
                    <th colspan="5">數據庫總數</th>
                    <th colspan="5"><?php echo $racingSeasonLabel; ?> 馬季</th>
                </tr>
                <tr>
                    <th class="sortable" data-sort="all_rides">出賽 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="all_wins">頭馬 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="all_top3">上名 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="all_win_rate">勝率 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="all_place_rate">上名率 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="season_rides">出賽 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="season_wins">頭馬 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="season_top3">上名 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="season_win_rate">勝率 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="season_place_rate">上名率 <span class="sort-icon"></span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($combinedData as $i => $jockey): ?>
                <tr data-name="<?php echo htmlspecialchars($jockey['name']); ?>"
                    data-all_rides="<?php echo $jockey['all_rides']; ?>"
                    data-all_wins="<?php echo $jockey['all_wins']; ?>"
                    data-all_top3="<?php echo $jockey['all_top3']; ?>"
                    data-all_win_rate="<?php echo $jockey['all_win_rate']; ?>"
                    data-all_place_rate="<?php echo $jockey['all_place_rate']; ?>"
                    data-season_rides="<?php echo $jockey['season_rides']; ?>"
                    data-season_wins="<?php echo $jockey['season_wins']; ?>"
                    data-season_top3="<?php echo $jockey['season_top3']; ?>"
                    data-season_win_rate="<?php echo $jockey['season_win_rate']; ?>"
                    data-season_place_rate="<?php echo $jockey['season_place_rate']; ?>">
                    <td class="rank"><?php echo $i + 1; ?></td>
                    <td><strong><?php echo htmlspecialchars($jockey['name']); ?></strong></td>
                    <!-- 数据库总数 -->
                    <td class="all_rides"><?php echo $jockey['all_rides'] > 0 ? number_format($jockey['all_rides']) : '-'; ?></td>
                    <td class="all_wins"><?php echo $jockey['all_wins'] > 0 ? $jockey['all_wins'] : '-'; ?></td>
                    <td class="all_top3"><?php echo $jockey['all_top3'] > 0 ? $jockey['all_top3'] : '-'; ?></td>
                    <td class="all_win_rate"><?php echo $jockey['all_win_rate'] > 0 ? '<span class="odds-hot">'.$jockey['all_win_rate'].'%</span>' : '-'; ?></td>
                    <td class="all_place_rate"><?php echo $jockey['all_place_rate'] > 0 ? $jockey['all_place_rate'].'%' : '-'; ?></td>
                    <!-- 当前马季 -->
                    <td class="season_rides"><?php echo $jockey['season_rides'] > 0 ? number_format($jockey['season_rides']) : '-'; ?></td>
                    <td class="season_wins"><?php echo $jockey['season_wins'] > 0 ? $jockey['season_wins'] : '-'; ?></td>
                    <td class="season_top3"><?php echo $jockey['season_top3'] > 0 ? $jockey['season_top3'] : '-'; ?></td>
                    <td class="season_win_rate"><?php echo $jockey['season_win_rate'] > 0 ? '<span class="odds-hot">'.$jockey['season_win_rate'].'%</span>' : '-'; ?></td>
                    <td class="season_place_rate"><?php echo $jockey['season_place_rate'] > 0 ? $jockey['season_place_rate'].'%' : '-'; ?></td>
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
</style>

<script>
(function() {
    const table = document.getElementById('jockeyTable');
    if (!table) return;
    
    const headers = table.querySelectorAll('.sortable');
    let currentSort = { column: 'all_win_rate', order: 'desc' };
    
    // 排序函数
    function sortTable(column, order) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        // 获取比较值
        const getValue = (row, col) => {
            const val = row.getAttribute('data-' + col);
            // 百分比和数字都转为数值
            return parseFloat(val) || 0;
        };
        
        // 排序
        rows.sort((a, b) => {
            let aVal = getValue(a, column);
            let bVal = getValue(b, column);
            
            if (order === 'asc') {
                return aVal - bVal;
            } else {
                return bVal - aVal;
            }
        });
        
        // 重新排列行并更新排名
        rows.forEach((row, index) => {
            tbody.appendChild(row);
            const rankCell = row.querySelector('.rank');
            if (rankCell) rankCell.textContent = index + 1;
        });
    }
    
    // 更新表头样式
    function updateHeaderStyle(column, order) {
        headers.forEach(header => {
            header.classList.remove('asc', 'desc');
            const sortCol = header.getAttribute('data-sort');
            if (sortCol === column) {
                header.classList.add(order);
            }
        });
    }
    
    // 绑定点击事件
    headers.forEach(header => {
        header.addEventListener('click', () => {
            const column = header.getAttribute('data-sort');
            let newOrder = 'desc';
            
            // 如果点击的是当前排序列，切换顺序
            if (currentSort.column === column) {
                newOrder = currentSort.order === 'desc' ? 'asc' : 'desc';
            }
            
            currentSort = { column: column, order: newOrder };
            sortTable(column, newOrder);
            updateHeaderStyle(column, newOrder);
        });
    });
    
    // 初始化：按默认排序（全历史胜率降序）
    sortTable('all_win_rate', 'desc');
    updateHeaderStyle('all_win_rate', 'desc');
})();
</script>