<?php
// analysis_jockey_barrier.php - Jockey barrier preference analysis

// Get racing season info
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

try {
    // Get all jockeys list for dropdown
    $stmtAllJockeys = $pdo->query("
        SELECT DISTINCT jockey_name_ch 
        FROM hkracing_horse_performances 
        WHERE jockey_name_ch IS NOT NULL AND jockey_name_ch != ''
        ORDER BY jockey_name_ch
    ");
    $allJockeys = $stmtAllJockeys ? $stmtAllJockeys->fetchAll(PDO::FETCH_COLUMN) : [];
    
    // Get initial data (all jockeys)
    $allTimeStats = getJockeyBarrierStats($pdo, $seasonStartDate, '');
    $seasonStats = getJockeyBarrierStats($pdo, $seasonStartDate, '', true);
    
    // Build combined data
    $combinedData = buildCombinedData($allTimeStats, $seasonStats);
    
    // Calculate best barrier for each jockey
    $bestBarrierAll = getBestBarrier($allTimeStats);
    $bestBarrierSeason = getBestBarrier($seasonStats);
    
} catch (Exception $e) {
    echo '<div class="card"><div class="loading">騎師檔位數據加載失敗: ' . htmlspecialchars($e->getMessage()) . '</div></div>';
    return;
}

// Helper functions
function getJockeyBarrierStats($pdo, $seasonStartDate, $filterJockey, $isSeason = false) {
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
    
    if ($isSeason) {
        $sql .= " AND race_date >= :season_start";
    }
    
    if (!empty($filterJockey)) {
        $sql .= " AND jockey_name_ch = :jockey";
    }
    
    $sql .= " GROUP BY jockey_name_ch, barrier_group
              HAVING rides >= " . ($isSeason ? "5" : "10") . "
              ORDER BY jockey_name_ch, 
                CASE barrier_group
                    WHEN '內檔(1-3)' THEN 1
                    WHEN '中檔(4-6)' THEN 2
                    WHEN '外檔(7+)' THEN 3
                END";
    
    $stmt = $pdo->prepare($sql);
    $params = [];
    if ($isSeason) {
        $params[':season_start'] = $seasonStartDate;
    }
    if (!empty($filterJockey)) {
        $params[':jockey'] = $filterJockey;
    }
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buildCombinedData($allTimeStats, $seasonStats) {
    $allTimeMap = [];
    foreach ($allTimeStats as $stat) {
        $key = $stat['jockey_name_ch'] . '|' . $stat['barrier_group'];
        $allTimeMap[$key] = $stat;
    }
    
    $seasonMap = [];
    foreach ($seasonStats as $stat) {
        $key = $stat['jockey_name_ch'] . '|' . $stat['barrier_group'];
        $seasonMap[$key] = $stat;
    }
    
    $allKeys = array_unique(array_merge(array_keys($allTimeMap), array_keys($seasonMap)));
    
    $combinedData = [];
    foreach ($allKeys as $key) {
        $allTime = $allTimeMap[$key] ?? null;
        $season = $seasonMap[$key] ?? null;
        
        if ($allTime || $season) {
            $parts = explode('|', $key);
            $combinedData[] = [
                'jockey_name_ch' => $parts[0],
                'barrier_group' => $parts[1],
                'all_rides' => $allTime['rides'] ?? 0,
                'all_wins' => $allTime['wins'] ?? 0,
                'all_top3' => $allTime['top3'] ?? 0,
                'all_win_rate' => $allTime['win_rate'] ?? 0,
                'all_top3_rate' => $allTime['top3_rate'] ?? 0,
                'season_rides' => $season['rides'] ?? 0,
                'season_wins' => $season['wins'] ?? 0,
                'season_top3' => $season['top3'] ?? 0,
                'season_win_rate' => $season['win_rate'] ?? 0,
                'season_top3_rate' => $season['top3_rate'] ?? 0,
            ];
        }
    }
    return $combinedData;
}

function getBestBarrier($stats) {
    $bestBarrier = [];
    foreach ($stats as $stat) {
        $jockey = $stat['jockey_name_ch'];
        if (!isset($bestBarrier[$jockey]) || $stat['win_rate'] > $bestBarrier[$jockey]['win_rate']) {
            $bestBarrier[$jockey] = $stat;
        }
    }
    return $bestBarrier;
}

$venueNames = ['ST' => '沙田', 'HV' => '跑馬地'];
?>

<!-- 篩選器 -->
<div class="card">
    <div class="card-title">🔍 騎師檔位偏好分析</div>
    <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
        <div style="flex: 1; min-width: 200px;">
            <label style="display: block; margin-bottom: 5px; font-size: 12px;">騎師</label>
            <select id="filterJockey" style="width: 100%; padding: 8px; background: #0f3460; border: 1px solid #2a3a4a; border-radius: 8px; color: white;">
                <option value="">全部騎師</option>
                <?php foreach ($allJockeys as $j): ?>
                <option value="<?php echo htmlspecialchars($j); ?>"><?php echo htmlspecialchars($j); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <div style="font-size: 12px; color: #8899aa;">⚡ 選擇後自動更新</div>
        </div>
    </div>
</div>

<!-- 騎師檔位統計表容器 -->
<div id="jockeyBarrierContainer" class="card">
    <div class="card-title">🎯 騎師各檔位表現</div>
    <div style="overflow-x: auto;">
        <table class="data-table" id="jockeyBarrierTable">
            <thead>
                <tr>
                    <th rowspan="2">騎師</th>
                    <th rowspan="2">檔位組別</th>
                    <th colspan="5">數據庫總數</th>
                    <th colspan="5"><?php echo $racingSeasonLabel; ?> 馬季</th>
                </tr>
                <tr>
                    <th class="sortable" data-sort="all_rides">出賽 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="all_wins">頭馬 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="all_top3">上名 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="all_win_rate">勝率 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="all_top3_rate">上名率 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="season_rides">出賽 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="season_wins">頭馬 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="season_top3">上名 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="season_win_rate">勝率 <span class="sort-icon"></span></th>
                    <th class="sortable" data-sort="season_top3_rate">上名率 <span class="sort-icon"></span></th>
                </tr>
            </thead>
            <tbody id="jockeyBarrierTableBody">
                <?php foreach ($combinedData as $item): ?>
                <tr data-jockey="<?php echo htmlspecialchars($item['jockey_name_ch']); ?>"
                    data-all_rides="<?php echo $item['all_rides']; ?>"
                    data-all_wins="<?php echo $item['all_wins']; ?>"
                    data-all_top3="<?php echo $item['all_top3']; ?>"
                    data-all_win_rate="<?php echo $item['all_win_rate']; ?>"
                    data-all_top3_rate="<?php echo $item['all_top3_rate']; ?>"
                    data-season_rides="<?php echo $item['season_rides']; ?>"
                    data-season_wins="<?php echo $item['season_wins']; ?>"
                    data-season_top3="<?php echo $item['season_top3']; ?>"
                    data-season_win_rate="<?php echo $item['season_win_rate']; ?>"
                    data-season_top3_rate="<?php echo $item['season_top3_rate']; ?>">
                    <td><strong><?php echo htmlspecialchars($item['jockey_name_ch']); ?></strong></td>
                    <td><?php echo $item['barrier_group']; ?></td>
                    <td class="all_rides"><?php echo $item['all_rides'] > 0 ? number_format($item['all_rides']) : '-'; ?></td>
                    <td class="all_wins"><?php echo $item['all_wins'] > 0 ? $item['all_wins'] : '-'; ?></td>
                    <td class="all_top3"><?php echo $item['all_top3'] > 0 ? $item['all_top3'] : '-'; ?></td>
                    <td class="all_win_rate"><?php echo $item['all_win_rate'] > 0 ? '<span class="odds-hot">' . $item['all_win_rate'] . '%</span>' : '-'; ?></td>
                    <td class="all_top3_rate"><?php echo $item['all_top3_rate'] > 0 ? $item['all_top3_rate'] . '%' : '-'; ?></td>
                    <td class="season_rides"><?php echo $item['season_rides'] > 0 ? number_format($item['season_rides']) : '-'; ?></td>
                    <td class="season_wins"><?php echo $item['season_wins'] > 0 ? $item['season_wins'] : '-'; ?></td>
                    <td class="season_top3"><?php echo $item['season_top3'] > 0 ? $item['season_top3'] : '-'; ?></td>
                    <td class="season_win_rate"><?php echo $item['season_win_rate'] > 0 ? '<span class="odds-hot">' . $item['season_win_rate'] . '%</span>' : '-'; ?></td>
                    <td class="season_top3_rate"><?php echo $item['season_top3_rate'] > 0 ? $item['season_top3_rate'] . '%' : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
    </div>
</div>

<!-- 騎師最佳檔位表容器 -->
<div id="bestBarrierContainer" class="card">
    <div class="card-title">🏆 騎師最佳檔位一覽</div>
    <div style="overflow-x: auto;">
        <table class="data-table" id="bestBarrierTable">
            <thead>
                <tr>
                    <th rowspan="2">騎師</th>
                    <th colspan="4">數據庫總數</th>
                    <th colspan="4"><?php echo $racingSeasonLabel; ?> 馬季</th>
                </tr>
                <tr>
                    <th>最佳檔位</th><th>勝率</th><th>出賽</th><th>策略建議</th>
                    <th>最佳檔位</th><th>勝率</th><th>出賽</th><th>策略建議</th>
                </tr>
            </thead>
            <tbody id="bestBarrierTableBody">
                <?php 
                $allJockeyNames = array_unique(array_merge(array_keys($bestBarrierAll), array_keys($bestBarrierSeason)));
                sort($allJockeyNames);
                foreach ($allJockeyNames as $jockey):
                    $allBest = $bestBarrierAll[$jockey] ?? null;
                    $seasonBest = $bestBarrierSeason[$jockey] ?? null;
                    
                    $allAdvice = '';
                    if ($allBest) {
                        if ($allBest['barrier_group'] == '內檔(1-3)') $allAdvice = '💡 擅長內檔，適合前領馬';
                        elseif ($allBest['barrier_group'] == '中檔(4-6)') $allAdvice = '⚖️ 中檔表現穩定，進退有據';
                        else $allAdvice = '🏃 擅長外檔，適合後上馬';
                    }
                    
                    $seasonAdvice = '';
                    if ($seasonBest) {
                        if ($seasonBest['barrier_group'] == '內檔(1-3)') $seasonAdvice = '💡 擅長內檔，適合前領馬';
                        elseif ($seasonBest['barrier_group'] == '中檔(4-6)') $seasonAdvice = '⚖️ 中檔表現穩定，進退有據';
                        else $seasonAdvice = '🏃 擅長外檔，適合後上馬';
                    }
                ?>
                <tr data-jockey="<?php echo htmlspecialchars($jockey); ?>">
                    <td><strong><?php echo htmlspecialchars($jockey); ?></strong></td>
                    <td><?php echo $allBest ? '<span class="odds-hot">' . $allBest['barrier_group'] . '</span>' : '-'; ?></td>
                    <td><?php echo $allBest ? $allBest['win_rate'] . '%' : '-'; ?></td>
                    <td><?php echo $allBest ? $allBest['rides'] . '次' : '-'; ?></td>
                    <td><?php echo $allAdvice ?: '-'; ?></td>
                    <td><?php echo $seasonBest ? '<span class="odds-hot">' . $seasonBest['barrier_group'] . '</span>' : '-'; ?></td>
                    <td><?php echo $seasonBest ? $seasonBest['win_rate'] . '%' : '-'; ?></td>
                    <td><?php echo $seasonBest ? $seasonBest['rides'] . '次' : '-'; ?></td>
                    <td><?php echo $seasonAdvice ?: '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
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
    // 初始化表格排序
    initSortableTable('jockeyBarrierTable');
    initSortableTable('bestBarrierTable');
    
    // 騎師選擇變更事件 - AJAX 更新
    $('#filterJockey').change(function() {
        var jockey = $(this).val();
        loadJockeyBarrierData(jockey);
    });
});

function initSortableTable(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const headers = table.querySelectorAll('.sortable');
    if (!headers.length) return;
    
    let currentSort = { column: 'all_win_rate', order: 'desc' };
    
    function sortTable(column, order) {
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
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
        
        rows.forEach(row => tbody.appendChild(row));
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
        $(header).off('click').on('click', function() {
            const column = $(this).data('sort');
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

function loadJockeyBarrierData(jockey) {
    // 顯示加載中
    $('#jockeyBarrierTableBody').html('<tr><td colspan="11" class="loading">加載中...</td></tr>');
    $('#bestBarrierTableBody').html('<tr><td colspan="9" class="loading">加載中...</td></tr>');
    
    $.ajax({
        url: 'analysis_api.php',
        type: 'GET',
        data: {
            action: 'jockeyBarrierData',
            jockey: jockey
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateJockeyBarrierTable(response.data);
                updateBestBarrierTable(response.bestBarrier);
            } else {
                $('#jockeyBarrierTableBody').html('<tr><td colspan="11" class="loading">數據加載失敗</td></tr>');
                $('#bestBarrierTableBody').html('<tr><td colspan="9" class="loading">數據加載失敗</td></tr>');
            }
        },
        error: function() {
            $('#jockeyBarrierTableBody').html('<tr><td colspan="11" class="loading">加載失敗，請重試</td></tr>');
            $('#bestBarrierTableBody').html('<tr><td colspan="9" class="loading">加載失敗，請重試</td></tr>');
        }
    });
}

function updateJockeyBarrierTable(data) {
    if (!data || data.length === 0) {
        $('#jockeyBarrierTableBody').html('<tr><td colspan="11" class="loading">暫無數據</td></tr>');
        return;
    }
    
    var html = '';
    for (var i = 0; i < data.length; i++) {
        var item = data[i];
        var preference = '';
        var prefClass = '';
        var allWinRate = item.all_win_rate || 0;
        
        if (allWinRate >= 15) {
            preference = '🔥 極度擅長';
            prefClass = 'odds-hot';
        } else if (allWinRate >= 10) {
            preference = '⭐ 擅長';
            prefClass = 'odds-up';
        } else if (allWinRate >= 5) {
            preference = '✓ 一般';
            prefClass = '';
        } else {
            preference = '⚠️ 較弱';
            prefClass = 'odds-down';
        }
        
        html += '<tr data-all_rides="' + (item.all_rides || 0) + '"'
            + ' data-all_wins="' + (item.all_wins || 0) + '"'
            + ' data-all_top3="' + (item.all_top3 || 0) + '"'
            + ' data-all_win_rate="' + (item.all_win_rate || 0) + '"'
            + ' data-all_top3_rate="' + (item.all_top3_rate || 0) + '"'
            + ' data-season_rides="' + (item.season_rides || 0) + '"'
            + ' data-season_wins="' + (item.season_wins || 0) + '"'
            + ' data-season_top3="' + (item.season_top3 || 0) + '"'
            + ' data-season_win_rate="' + (item.season_win_rate || 0) + '"'
            + ' data-season_top3_rate="' + (item.season_top3_rate || 0) + '">';
        html += '<td><strong>' + escapeHtml(item.jockey_name_ch) + '</strong> <span class="' + prefClass + '" style="margin-left: 8px; padding: 2px 6px; border-radius: 10px; font-size: 10px;">' + preference + '</span></td>';
        html += '<td>' + escapeHtml(item.barrier_group) + '</td>';
        html += '<td class="all_rides">' + (item.all_rides > 0 ? numberFormat(item.all_rides) : '-') + '</td>';
        html += '<td class="all_wins">' + (item.all_wins > 0 ? item.all_wins : '-') + '</td>';
        html += '<td class="all_top3">' + (item.all_top3 > 0 ? item.all_top3 : '-') + '</td>';
        html += '<td class="all_win_rate">' + (item.all_win_rate > 0 ? '<span class="odds-hot">' + item.all_win_rate + '%</span>' : '-') + '</td>';
        html += '<td class="all_top3_rate">' + (item.all_top3_rate > 0 ? item.all_top3_rate + '%' : '-') + '</td>';
        html += '<td class="season_rides">' + (item.season_rides > 0 ? numberFormat(item.season_rides) : '-') + '</td>';
        html += '<td class="season_wins">' + (item.season_wins > 0 ? item.season_wins : '-') + '</td>';
        html += '<td class="season_top3">' + (item.season_top3 > 0 ? item.season_top3 : '-') + '</td>';
        html += '<td class="season_win_rate">' + (item.season_win_rate > 0 ? '<span class="odds-hot">' + item.season_win_rate + '%</span>' : '-') + '</td>';
        html += '<td class="season_top3_rate">' + (item.season_top3_rate > 0 ? item.season_top3_rate + '%' : '-') + '</td>';
        html += '</tr>';
    }
    $('#jockeyBarrierTableBody').html(html);
}

function updateBestBarrierTable(bestBarrier) {
    if (!bestBarrier || Object.keys(bestBarrier).length === 0) {
        $('#bestBarrierTableBody').html('<tr><td colspan="9" class="loading">暫無數據</td></tr>');
        return;
    }
    
    var html = '';
    var jockeys = Object.keys(bestBarrier).sort();
    for (var i = 0; i < jockeys.length; i++) {
        var jockey = jockeys[i];
        var allBest = bestBarrier[jockey];
        var seasonBest = bestBarrier[jockey + '_season'] || null;
        
        var allAdvice = '';
        if (allBest) {
            if (allBest.barrier_group == '內檔(1-3)') allAdvice = '💡 擅長內檔，適合前領馬';
            else if (allBest.barrier_group == '中檔(4-6)') allAdvice = '⚖️ 中檔表現穩定，進退有據';
            else allAdvice = '🏃 擅長外檔，適合後上馬';
        }
        
        var seasonAdvice = '';
        if (seasonBest) {
            if (seasonBest.barrier_group == '內檔(1-3)') seasonAdvice = '💡 擅長內檔，適合前領馬';
            else if (seasonBest.barrier_group == '中檔(4-6)') seasonAdvice = '⚖️ 中檔表現穩定，進退有據';
            else seasonAdvice = '🏃 擅長外檔，適合後上馬';
        }
        
        html += '<tr>';
        html += '<td><strong>' + escapeHtml(jockey) + '</strong></td>';
        html += '<td>' + (allBest ? '<span class="odds-hot">' + allBest.barrier_group + '</span>' : '-') + '</td>';
        html += '<td>' + (allBest ? allBest.win_rate + '%' : '-') + '</td>';
        html += '<td>' + (allBest ? allBest.rides + '次' : '-') + '</td>';
        html += '<td>' + (allAdvice || '-') + '</td>';
        html += '<td>' + (seasonBest ? '<span class="odds-hot">' + seasonBest.barrier_group + '</span>' : '-') + '</td>';
        html += '<td>' + (seasonBest ? seasonBest.win_rate + '%' : '-') + '</td>';
        html += '<td>' + (seasonBest ? seasonBest.rides + '次' : '-') + '</td>';
        html += '<td>' + (seasonAdvice || '-') + '</td>';
        html += '</tr>';
    }
    $('#bestBarrierTableBody').html(html);
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