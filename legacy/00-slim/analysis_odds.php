<?php
// analysis_odds.php - 赔率趋势组件（使用 AJAX 加載圖表數據）

try {
    $today = date('Y-m-d');
    
    // 獲取今天所有賽事列表
    $raceListSql = "
        SELECT DISTINCT venue_code, race_no 
        FROM hkracing_odds_horse_details 
        WHERE race_date = '$today' AND win_odds > 0
        ORDER BY race_no";
    
    $raceListStmt = $pdo->query($raceListSql);
    $raceList = $raceListStmt ? $raceListStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
    // 獲取選中的賽事（從 GET 參數或默認第一場）
    $selectedVenue = isset($_GET['odds_venue']) ? $_GET['odds_venue'] : ($raceList[0]['venue_code'] ?? 'ST');
    $selectedRaceNo = isset($_GET['odds_race_no']) ? $_GET['odds_race_no'] : ($raceList[0]['race_no'] ?? 1);
    
    // 獲取每匹馬的最新賠率記錄（用於統計卡片）
    $latestOddsSql = "
        SELECT o1.*
        FROM hkracing_odds_horse_details o1
        INNER JOIN (
            SELECT horse_code, venue_code, race_no, MAX(captured_at) as latest_time
            FROM hkracing_odds_horse_details
            WHERE race_date = '$today' AND win_odds > 0
            GROUP BY horse_code, venue_code, race_no
        ) o2 ON o1.horse_code = o2.horse_code 
            AND o1.venue_code = o2.venue_code 
            AND o1.race_no = o2.race_no 
            AND o1.captured_at = o2.latest_time
        WHERE o1.win_odds > 0
    ";
    
    // 获取今天所有賽事的賠率
    $todaySql = "
        SELECT 
            venue_code,
            race_no,
            COUNT(*) as horse_count,
            MIN(win_odds) as min_odds,
            ROUND(AVG(win_odds), 1) as avg_odds,
            MAX(win_odds) as max_odds
        FROM ($latestOddsSql) as latest_odds
        GROUP BY venue_code, race_no
        ORDER BY race_no";
    
    $todayStmt = $pdo->query($todaySql);
    $todayRaces = $todayStmt ? $todayStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
    // 获取赔率区间统计
    $sql = "
        SELECT 
            CASE 
                WHEN win_odds < 2 THEN '< 2.0'
                WHEN win_odds < 3 THEN '2.0 - 2.9'
                WHEN win_odds < 5 THEN '3.0 - 4.9'
                WHEN win_odds < 10 THEN '5.0 - 9.9'
                ELSE '≥ 10.0'
            END as odds_range,
            COUNT(*) as total,
            ROUND(AVG(win_odds), 1) as avg_odds
        FROM ($latestOddsSql) as latest_odds
        GROUP BY odds_range
        ORDER BY MIN(win_odds)";
    
    $stmt = $pdo->query($sql);
    $oddsRanges = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
    // 获取今天总马匹数
    $totalSql = "SELECT COUNT(*) as total_horses FROM ($latestOddsSql) as latest_odds";
    $totalStmt = $pdo->query($totalSql);
    $totalStats = $totalStmt ? $totalStmt->fetch(PDO::FETCH_ASSOC) : [];
    
    // 获取赔率更新时间
    $timeSql = "SELECT MAX(captured_at) as last_update FROM hkracing_odds_horse_details WHERE race_date = '$today'";
    $timeStmt = $pdo->query($timeSql);
    $lastUpdate = $timeStmt ? $timeStmt->fetch(PDO::FETCH_ASSOC) : [];
    
    // 获取大热门马匹
    $hotHorsesSql = "
        SELECT 
            horse_code, 
            horse_name_ch, 
            venue_code, 
            race_no, 
            win_odds,
            runner_no
        FROM ($latestOddsSql) as latest_odds
        ORDER BY win_odds ASC
        LIMIT 10";
    
    $hotStmt = $pdo->query($hotHorsesSql);
    $hotHorses = $hotStmt ? $hotStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
    // 馬場名稱映射
    $venueNames = ['ST' => '沙田', 'HV' => '跑馬地', 'S1' => '悉尼', 'S2' => '墨爾本'];
    $selectedVenueName = $venueNames[$selectedVenue] ?? $selectedVenue;
    
} catch (Exception $e) {
    echo '<div class="card"><div class="loading">📊 賠率數據加載失敗: ' . htmlspecialchars($e->getMessage()) . '</div></div>';
    return;
}

// 檢查今天是否有賽事
if (empty($todayRaces) && empty($oddsRanges)) {
    echo '<div class="card"><div class="loading">📊 今天 (' . $today . ') 暫無賠率數據</div></div>';
    return;
}
?>

<!-- 統計卡片 -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?php echo count($todayRaces); ?></div>
        <div class="stat-label">今日賽事場次</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($totalStats['total_horses'] ?? 0); ?></div>
        <div class="stat-label">今日參賽馬匹</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" style="font-size: 14px;"><?php echo htmlspecialchars($lastUpdate['last_update'] ?? '-'); ?></div>
        <div class="stat-label">最後賠率更新</div>
    </div>
</div>

<!-- 賽事選擇器 -->
<div class="card">
    <div class="card-title">🎯 賠率趨勢分析</div>
    <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
        <div style="flex: 1; min-width: 200px;">
            <label style="display: block; margin-bottom: 5px; font-size: 12px;">選擇賽事</label>
            <select id="oddsRaceSelect" style="width: 100%; padding: 8px; background: #0f3460; border: 1px solid #2a3a4a; border-radius: 8px; color: white;">
                <!--<option value="" disabled selected>-- 請選擇賽事 --</option>-->
                <option value="" disabled selected style="color: #888;">-- 請選擇賽事 (共 <?php echo count($todayRaces); ?> 場) --</option>
                <?php foreach ($todayRaces as $race): 
                    $venueName = $venueNames[$race['venue_code']] ?? $race['venue_code'];
                    $selected = ($race['venue_code'] == $selectedVenue && $race['race_no'] == $selectedRaceNo) ? 'selected' : '';
                ?>
                <option value="<?php echo $race['venue_code'] . '|' . $race['race_no']; ?>" <?php echo $selected; ?>>
                    <?php echo $venueName . ' 第' . $race['race_no'] . '場 (' . $race['horse_count'] . '匹)'; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <div style="font-size: 12px; color: #8899aa;">⚡ 選擇後自動更新圖表</div>
        </div>
    </div>
</div>

<!-- 圖表容器 -->
<div id="chartsContainer">
    <!-- 獨贏賠率趨勢圖 -->
    <div class="card" id="winChartCard">
        <div class="card-title">📈 獨贏賠率趨勢 - <span id="selectedRaceName"><?php echo $selectedVenueName; ?> 第<?php echo $selectedRaceNo; ?>場</span></div>
        <div class="card-body" style="height: 480px; padding: 10px;">
            <canvas id="winOddsChart" style="width: 100%; height: 100%; display: block;"></canvas>
        </div>
        <div style="margin-top: 10px; font-size: 11px; color: #8899aa; text-align: center; padding-bottom: 10px;">
            💡 開閘前賠率: 各馬匹起點 | 賠率下跌 = 市場看好
        </div>
    </div>
    
    <!-- 位置賠率趨勢圖 -->
    <div class="card" id="placeChartCard">
        <div class="card-title">📉 位置賠率趨勢 - <span id="selectedRaceNamePlace"><?php echo $selectedVenueName; ?> 第<?php echo $selectedRaceNo; ?>場</span></div>
        <div class="card-body" style="height: 480px; padding: 10px;">
            <canvas id="placeOddsChart" style="width: 100%; height: 100%; display: block;"></canvas>
        </div>
        <div style="margin-top: 10px; font-size: 11px; color: #8899aa; text-align: center; padding-bottom: 10px;">
            💡 位置賠率越低，入圍機會越高
        </div>
    </div>

<!-- 今日賽事賠率 -->
<div class="card">
    <div class="card-title">🏇 今日賽事賠率一覽 (<?php echo $today; ?>)</div>
    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr><th>賽事</th><th>馬匹數</th><th>最熱門賠率</th><th>平均賠率</th><th>最冷門賠率</th>
            </thead>
            <tbody>
                <?php foreach ($todayRaces as $race): 
                    $venueName = $venueNames[$race['venue_code']] ?? $race['venue_code'];
                ?>
                <tr>
                    <td><strong><?php echo $venueName . ' 第' . $race['race_no'] . '場'; ?></strong></td>
                    <td><?php echo $race['horse_count']; ?> 匹</span></td>
                    <td class="odds-hot"><?php echo $race['min_odds']; ?>倍</span></td>
                    <td><?php echo $race['avg_odds']; ?>倍</span></td>
                    <td><?php echo $race['max_odds']; ?>倍</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 賠率區間分佈 -->
<div class="card">
    <div class="card-title">📊 今日賠率區間分佈</div>
    <?php if (empty($oddsRanges)): ?>
    <div class="loading">暫無數據</div>
    <?php else: ?>
    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead><tr><th>賠率區間</th><th>馬匹數量</th><th>佔比</th><th>平均賠率</th></thead>
            <tbody>
                <?php 
                $totalHorses = array_sum(array_column($oddsRanges, 'total'));
                foreach ($oddsRanges as $range): 
                    $percentage = $totalHorses > 0 ? round($range['total'] / $totalHorses * 100, 1) : 0;
                ?>
                <tr>
                    <td><strong><?php echo $range['odds_range']; ?></strong></td>
                    <td><?php echo number_format($range['total']); ?> 匹</span></td>
                    <td><?php echo $percentage; ?>%</span></td>
                    <td><?php echo $range['avg_odds']; ?>倍</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- 今日最熱門馬匹 -->
<?php if (!empty($hotHorses)): ?>
<div class="card">
    <div class="card-title">🔥 今日最熱門馬匹 (賠率最低)</div>
    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead><tr><th>馬場</th><th>場次</th><th>馬號</th><th>馬名</th><th>賠率</th></thead>
            <tbody>
                <?php foreach ($hotHorses as $horse): 
                    $venueName = $venueNames[$horse['venue_code']] ?? $horse['venue_code'];
                ?>
                <tr>
                    <td><?php echo $venueName; ?></span></td>
                    <td>第 <?php echo $horse['race_no']; ?> 場</span></td>
                    <td><?php echo $horse['runner_no']; ?> 號</span></td>
                    <td><strong><?php echo htmlspecialchars($horse['horse_name_ch'] ?? $horse['horse_code']); ?></strong></td>
                    <td class="odds-hot"><?php echo $horse['win_odds']; ?>倍</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- 賠率說明 -->
<div class="card">
    <div class="card-title">📖 賠率說明</div>
    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        <div><span style="background: #e94560; padding: 5px 12px; border-radius: 20px;">≤ 2.0倍</span> - 超級大熱門</div>
        <div><span style="background: #f39c12; padding: 5px 12px; border-radius: 20px;">2.0-2.9倍</span> - 大熱門</div>
        <div><span style="background: #27ae60; padding: 5px 12px; border-radius: 20px;">3.0-4.9倍</span> - 次熱門</div>
        <div><span style="background: #3498db; padding: 5px 12px; border-radius: 20px;">5.0-9.9倍</span> - 均勢</div>
        <div><span style="background: #7f8c8d; padding: 5px 12px; border-radius: 20px;">≥ 10.0倍</span> - 冷門</div>
    </div>
</div>

<!-- 引入 Chart.js 和 jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// 定義全局變量存儲圖表實例
var winChart = null;
var placeChart = null;

// 加載圖表數據的函數
function loadChartData(venue, raceNo) {
    // 顯示加載中
    $('#winChartCard').find('.card-title').html('📈 獨贏賠率趨勢 - 加載中...');
    $('#placeChartCard').find('.card-title').html('📉 位置賠率趨勢 - 加載中...');
    
    $.ajax({
        url: 'analysis_api.php',
        type: 'GET',
        data: {
            action: 'oddsChartData',
            venue: venue,
            race_no: raceNo
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // 更新標題
                $('#winChartCard').find('.card-title').html('📈 獨贏賠率趨勢 - ' + response.race_name);
                $('#placeChartCard').find('.card-title').html('📉 位置賠率趨勢 - ' + response.race_name);
                
                // 更新圖表
                updateCharts(response.win_data, response.place_data, response.labels);
            } else {
                $('#winChartCard').find('.card-title').html('📈 獨贏賠率趨勢 - 加載失敗');
                $('#placeChartCard').find('.card-title').html('📉 位置賠率趨勢 - 加載失敗');
            }
        },
        error: function() {
            $('#winChartCard').find('.card-title').html('📈 獨贏賠率趨勢 - 加載失敗');
            $('#placeChartCard').find('.card-title').html('📉 位置賠率趨勢 - 加載失敗');
        }
    });
}

// 更新圖表
// 更新圖表
function updateCharts(winData, placeData, labels) {
    // 銷毀舊圖表
    if (winChart) {
        winChart.destroy();
    }
    if (placeChart) {
        placeChart.destroy();
    }
    
    // 重新獲取 Canvas 元素並重置尺寸
    var winCanvas = document.getElementById('winOddsChart');
    var placeCanvas = document.getElementById('placeOddsChart');
    
    // 重置 Canvas 尺寸 - 關鍵修復
    if (winCanvas) {
        var container = winCanvas.parentElement;
        winCanvas.style.width = '100%';
        winCanvas.style.height = '100%';
        winCanvas.width = container.clientWidth;
        winCanvas.height = container.clientHeight;
    }
    
    if (placeCanvas) {
        var container = placeCanvas.parentElement;
        placeCanvas.style.width = '100%';
        placeCanvas.style.height = '100%';
        placeCanvas.width = container.clientWidth;
        placeCanvas.height = container.clientHeight;
    }
    
    // 創建獨贏圖表
    if (winData && winData.length > 0 && labels && labels.length > 0) {
        var winCtx = winCanvas.getContext('2d');
        winChart = new Chart(winCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: winData.map(function(d) {
                    return {
                        label: d.label,
                        data: d.data,
                        borderColor: d.borderColor,
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: d.borderColor,
                        tension: 0.1,
                        fill: false
                    };
                })
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                layout: {
                    padding: {
                        left: 10,
                        right: 10,
                        top: 20,
                        bottom: 30
                    }
                },
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw + '倍';
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                        labels: { 
                            color: '#e0e0e0', 
                            font: { size: 10 }, 
                            boxWidth: 12,
                            padding: 8
                        }
                    }
                },
                scales: {
                    y: {
                        title: { 
                            display: true, 
                            text: '賠率', 
                            color: '#8899aa',
                            font: { size: 11 }
                        },
                        grid: { color: '#2a3a4a' },
                        ticks: { 
                            color: '#e0e0e0',
                            font: { size: 10 },
                            stepSize: 10,
                            callback: function(value) {
                                return value + '倍';
                            }
                        }
                    },
                    x: {
                        title: { 
                            display: true, 
                            text: '時間', 
                            color: '#8899aa',
                            font: { size: 11 }
                        },
                        grid: { color: '#2a3a4a' },
                        ticks: { 
                            color: '#e0e0e0',
                            font: { size: 10 },
                            maxRotation: 45,
                            minRotation: 45,
                            autoSkip: true,
                            maxTicksLimit: 10
                        }
                    }
                }
            }
        });
    } else {
        $('#winOddsChart').parent().html('<div class="loading">📊 暫無足夠的獨贏賠率趨勢數據</div>');
    }
    
    // 創建位置圖表
    if (placeData && placeData.length > 0 && labels && labels.length > 0) {
        var placeCtx = placeCanvas.getContext('2d');
        placeChart = new Chart(placeCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: placeData.map(function(d) {
                    return {
                        label: d.label,
                        data: d.data,
                        borderColor: d.borderColor,
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: d.borderColor,
                        tension: 0.1,
                        fill: false
                    };
                })
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                layout: {
                    padding: {
                        left: 10,
                        right: 10,
                        top: 20,
                        bottom: 30
                    }
                },
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw + '倍';
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                        labels: { 
                            color: '#e0e0e0', 
                            font: { size: 10 }, 
                            boxWidth: 12,
                            padding: 8
                        }
                    }
                },
                scales: {
                    y: {
                        title: { 
                            display: true, 
                            text: '賠率', 
                            color: '#8899aa',
                            font: { size: 11 }
                        },
                        grid: { color: '#2a3a4a' },
                        ticks: { 
                            color: '#e0e0e0',
                            font: { size: 10 },
                            stepSize: 10,
                            callback: function(value) {
                                return value + '倍';
                            }
                        }
                    },
                    x: {
                        title: { 
                            display: true, 
                            text: '時間', 
                            color: '#8899aa',
                            font: { size: 11 }
                        },
                        grid: { color: '#2a3a4a' },
                        ticks: { 
                            color: '#e0e0e0',
                            font: { size: 10 },
                            maxRotation: 45,
                            minRotation: 45,
                            autoSkip: true,
                            maxTicksLimit: 10
                        }
                    }
                }
            }
        });
    } else {
        $('#placeOddsChart').parent().html('<div class="loading">📊 暫無足夠的位置賠率趨勢數據</div>');
    }
    
    // 延遲重新調整圖表尺寸
    setTimeout(function() {
        if (winChart) {
            winChart.resize();
            winChart.update();
        }
        if (placeChart) {
            placeChart.resize();
            placeChart.update();
        }
    }, 150);
}
// 初始化圖表（加載默認賽事的數據）
function initDefaultChart() {
    var defaultVenue = '<?php echo $selectedVenue; ?>';
    var defaultRaceNo = <?php echo $selectedRaceNo; ?>;
    loadChartData(defaultVenue, defaultRaceNo);
}

$(document).ready(function() {
    // 初始化默認圖表
    initDefaultChart();
    
    // 賽事選擇器變更事件
    $('#oddsRaceSelect').change(function() {
        var value = $(this).val();
        var parts = value.split('|');
        var venue = parts[0];
        var raceNo = parts[1];
        
        // 使用 AJAX 加載新賽事的圖表數據
        loadChartData(venue, raceNo);
    });
});
</script>

<style>
.odds-hot { color: #e94560; font-weight: bold; color: #ffffff;}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}
.stat-card {
    background: #0f3460;
    padding: 15px;
    border-radius: 10px;
    text-align: center;
}
.stat-number {
    font-size: 28px;
    font-weight: bold;
    color: #e94560;
}
.stat-label {
    font-size: 12px;
    color: #8899aa;
    margin-top: 5px;
}
.loading {
    text-align: center;
    padding: 40px;
    color: #8899aa;
}
.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.data-table th, .data-table td {
    padding: 10px 8px;
    text-align: center;
    border-bottom: 1px solid #2a3a4a;
}
.data-table th {
    background: #0f3460;
    color: #e94560;
    font-weight: bold;
}
/* 添加到 style 標籤 */
#winChartCard .card-body, 
#placeChartCard .card-body {
    height: 480px !important;
    position: relative;
    padding: 10px;
}

#winOddsChart, #placeOddsChart {
    display: block;
    width: 100% !important;
    height: 100% !important;
}

/* 確保圖表容器不會被壓縮 */
.chart-wrapper {
    height: 100%;
    width: 100%;
}
</style>