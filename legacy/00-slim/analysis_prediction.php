<?php
// analysis_prediction.php - 机器学习预测（基于历史数据）

// 获取预测参数
$distance = $_GET['distance'] ?? '';
$barrier = $_GET['barrier'] ?? '';
$jockey = $_GET['jockey'] ?? '';
$trainer = $_GET['trainer'] ?? '';
$oddsRange = $_GET['odds_range'] ?? '';

// 获取所有选项
$stmtDistances = $pdo->query("SELECT DISTINCT distance FROM hkracing_horse_performances WHERE distance IS NOT NULL ORDER BY distance");
$allDistances = $stmtDistances ? $stmtDistances->fetchAll(PDO::FETCH_COLUMN) : [];

$allBarriers = ['1-3', '4-6', '7-9', '10+'];

$stmtJockeys = $pdo->query("
    SELECT DISTINCT jockey_name_ch 
    FROM hkracing_horse_performances 
    WHERE jockey_name_ch IS NOT NULL AND jockey_name_ch != ''
    ORDER BY jockey_name_ch 
    LIMIT 50
");
$allJockeys = $stmtJockeys ? $stmtJockeys->fetchAll(PDO::FETCH_COLUMN) : [];

$stmtTrainers = $pdo->query("
    SELECT DISTINCT trainer_name_ch 
    FROM hkracing_horse_performances 
    WHERE trainer_name_ch IS NOT NULL AND trainer_name_ch != ''
    ORDER BY trainer_name_ch 
    LIMIT 50
");
$allTrainers = $stmtTrainers ? $stmtTrainers->fetchAll(PDO::FETCH_COLUMN) : [];

$oddsRanges = ['< 2.0', '2.0-2.9', '3.0-4.9', '5.0-9.9', '≥ 10.0'];
?>

<style>
.stat-section {
    margin-bottom: 25px;
    border-bottom: 1px solid #2a3a4a;
    padding-bottom: 15px;
}
.stat-section-title {
    font-size: 16px;
    font-weight: bold;
    margin-bottom: 12px;
    padding: 8px 12px;
    background: #0f3460;
    border-radius: 8px;
    cursor: pointer;
    user-select: none;
}
.stat-section-title:hover {
    background: #1a4a7a;
}
.stat-section-title .toggle-icon {
    float: right;
    font-size: 14px;
}
.stat-section-content {
    display: block;
}
.stat-section-content.collapsed {
    display: none;
}
.stat-table-wrapper {
    overflow-x: auto;
    margin-bottom: 10px;
    width: 100%;
}
.stat-table-mini {
    width: 100%;
    min-width: 800px;
    border-collapse: collapse;
    font-size: 12px;
}
.stat-table-mini th {
    background: #0a2a4a;
    padding: 8px 8px;
    text-align: center;
    font-weight: bold;
    border-bottom: 1px solid #2a3a4a;
    white-space: nowrap;
}
.stat-table-mini td {
    padding: 6px 8px;
    text-align: center;
    border-bottom: 1px solid #1a3a5a;
}
.stat-table-mini td:first-child {
    text-align: left;
    font-weight: bold;
}
.stat-badge {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: bold;
    margin-left: 6px;
}
.stat-badge.best {
    background: #27ae60;
    color: white;
}
.odds-hot {
    font-weight: bold;
    color: #ffffff;
}
.prediction-result-card {
    background: linear-gradient(135deg, #0f3460 0%, #0a2a4a 100%);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    text-align: center;
}
.prediction-number {
    font-size: 48px;
    font-weight: bold;
    color: #f39c12;
}
.prediction-label {
    font-size: 14px;
    color: #aaa;
    margin-top: 5px;
}
.prediction-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-top: 15px;
}
.prediction-stat {
    text-align: center;
    padding: 10px;
    background: #0a2a4a;
    border-radius: 8px;
}
.prediction-stat-value {
    font-size: 20px;
    font-weight: bold;
}
.prediction-stat-label {
    font-size: 11px;
    color: #aaa;
}
.factor-list {
    margin-top: 15px;
    padding: 12px;
    background: #0a2a4a;
    border-radius: 8px;
}
.factor-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #1a3a5a;
}
.factor-item:last-child {
    border-bottom: none;
}
.factor-name {
    font-weight: bold;
}
.factor-impact-positive {
    color: #27ae60;
}
.factor-impact-negative {
    color: #e74c3c;
}
.factor-impact-neutral {
    color: #f39c12;
}
.loadingstatsReference {
    text-align: center;
    padding: 0px;
    color: #8899aa;
}
</style>

<!-- 预测输入表单 -->
<div class="card">
    <div class="card-title">🔮 勝率預測模型</div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 20px;">
        <div>
            <label style="display: block; margin-bottom: 5px; font-size: 12px;">途程(米)</label>
            <select id="predDistance" class="pred-input" style="width: 100%; padding: 8px; background: #0f3460; border: 1px solid #2a3a4a; border-radius: 8px; color: white;">
                <option value="">請選擇</option>
                <?php foreach ($allDistances as $d): ?>
                <option value="<?php echo $d; ?>"><?php echo $d; ?>m</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display: block; margin-bottom: 5px; font-size: 12px;">檔位</label>
            <select id="predBarrier" class="pred-input" style="width: 100%; padding: 8px; background: #0f3460; border: 1px solid #2a3a4a; border-radius: 8px; color: white;">
                <option value="">請選擇</option>
                <?php foreach ($allBarriers as $b): ?>
                <option value="<?php echo $b; ?>"><?php echo $b; ?>檔</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display: block; margin-bottom: 5px; font-size: 12px;">騎師</label>
            <select id="predJockey" class="pred-input" style="width: 100%; padding: 8px; background: #0f3460; border: 1px solid #2a3a4a; border-radius: 8px; color: white;">
                <option value="">請選擇</option>
                <?php foreach ($allJockeys as $j): ?>
                <option value="<?php echo htmlspecialchars($j); ?>"><?php echo htmlspecialchars($j); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display: block; margin-bottom: 5px; font-size: 12px;">練馬師</label>
            <select id="predTrainer" class="pred-input" style="width: 100%; padding: 8px; background: #0f3460; border: 1px solid #2a3a4a; border-radius: 8px; color: white;">
                <option value="">請選擇</option>
                <?php foreach ($allTrainers as $t): ?>
                <option value="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display: block; margin-bottom: 5px; font-size: 12px;">預計賠率</label>
            <select id="predOdds" class="pred-input" style="width: 100%; padding: 8px; background: #0f3460; border: 1px solid #2a3a4a; border-radius: 8px; color: white;">
                <option value="">請選擇</option>
                <?php foreach ($oddsRanges as $o): ?>
                <option value="<?php echo $o; ?>"><?php echo $o; ?>倍</option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<!-- 预测结果 -->
<div id="predictionResult" style="display: none;">
    <div class="card">
        <div class="card-title">📊 預測結果</div>
        <div id="resultContent"></div>
    </div>
</div>

<!-- 历史统计参考 -->
<div class="card">
    <div class="card-title">📈 歷史統計參考</div>
    <div id="statsReference" class="loadingstatsReference">加載統計數據...</div>
</div>

<script>
$(document).ready(function() {
    loadStatsReference();
    
    // 实时预测：任何输入变化时自动触发
    $('.pred-input').on('change', function() {
        updatePrediction();
    });
    
    // 初始加载时也触发一次（如果有默认值）
    updatePrediction();
});

function updatePrediction() {
    var params = {
        distance: $('#predDistance').val(),
        barrier: $('#predBarrier').val(),
        jockey: $('#predJockey').val(),
        trainer: $('#predTrainer').val(),
        odds_range: $('#predOdds').val()
    };
    
    // 如果没有选择任何参数，隐藏预测结果
    if (!params.distance && !params.barrier && !params.jockey && !params.trainer && !params.odds_range) {
        $('#predictionResult').hide();
        return;
    }
    
    $('#predictionResult').show();
    $('#resultContent').html('<div class="loading">計算中...</div>');
    
    // 添加一个标志，防止重复请求
    if (window.predictionLoading) return;
    window.predictionLoading = true;
    
    $.ajax({
        url: 'analysis_api.php',
        type: 'GET',
        data: $.extend({ action: 'predictionCalculate' }, params),
        dataType: 'json',
        success: function(response) {
            window.predictionLoading = false;
            console.log('Prediction response:', response);
            if (response.success) {
                displayPredictionResult(response.data);
            } else {
                $('#resultContent').html('<div class="loading">❌ ' + (response.error || '預測失敗') + '</div>');
            }
        },
        error: function(xhr, status, error) {
            window.predictionLoading = false;
            console.error('AJAX Error:', error);
            $('#resultContent').html('<div class="loading">❌ 請求失敗</div>');
        }
    });
}

function loadStatsReference() {
    $.ajax({
        url: 'analysis_api.php',
        type: 'GET',
        data: { action: 'predictionStats' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayExpandedStats(response.data);
            } else {
                $('#statsReference').html('<div class="loading">暫無統計數據</div>');
            }
        },
        error: function() {
            $('#statsReference').html('<div class="loading">加載失敗</div>');
        }
    });
}

function displayExpandedStats(data) {
    var html = '';
    
    // 途程统计（双列对比：数据库总数 vs 当前马季）
    if (data.distanceAllStats && data.distanceSeasonStats) {
        html += '<div class="stat-section">';
        html += '<div class="stat-section-title" data-section="distance">📏 途程分析 <span class="toggle-icon">▼</span></div>';
        html += '<div class="stat-section-content" id="section-distance">';
        html += '<div class="stat-table-wrapper">';
        html += '<table class="stat-table-mini">';
        html += '<thead>';
        html += '<tr><th rowspan="2">途程(m)</th><th colspan="5">數據庫總數</th><th colspan="5">' + data.seasonLabel + ' 馬季</th></tr>';
        html += '<tr><th>出賽</th><th>頭馬</th><th>勝率</th><th>上名率</th><th>趨勢</th><th>出賽</th><th>頭馬</th><th>勝率</th><th>上名率</th><th>趨勢</th></tr>';
        html += '</thead><tbody>';
        
        for (var i = 0; i < data.distanceAllStats.length; i++) {
            var all = data.distanceAllStats[i];
            var season = data.distanceSeasonStats.find(function(s) { return s.distance == all.distance; }) || null;
            var barWidthAll = (all.win_rate / data.distanceAllStats[0].win_rate) * 50;
            var barWidthSeason = (season && data.distanceSeasonStats[0] && season.win_rate / data.distanceSeasonStats[0].win_rate * 50) || 0;
            
            html += '<tr>';
            html += '<td>' + all.distance + 'm</td>';
            html += '<td>' + all.runs + '次</td><td>' + all.wins + '場</td>';
            html += '<td class="odds-hot">' + all.win_rate + '%</td><td>' + all.place_rate + '%</td>';
            html += '<td><div class="stat-bar" style="width: ' + barWidthAll + 'px; height: 4px; background: #3498db; border-radius: 2px;"></div></td>';
            if (season) {
                html += '<td>' + season.runs + '次</td><td>' + season.wins + '場</td>';
                html += '<td class="odds-hot">' + season.win_rate + '%</td><td>' + season.place_rate + '%</td>';
                html += '<td><div class="stat-bar" style="width: ' + barWidthSeason + 'px; height: 4px; background: #f39c12; border-radius: 2px;"></div></td>';
            } else {
                html += '<td>-</td><td>-</td><td>-</td><td>-</td><td>-</td>';
            }
            html += '</tr>';
        }
        html += '</tbody></table></div></div></div>';
    }
    
    // 档位统计（双列对比）
    if (data.barrierAllStats && data.barrierSeasonStats) {
        html += '<div class="stat-section">';
        html += '<div class="stat-section-title" data-section="barrier">🎯 檔位分析 <span class="toggle-icon">▼</span></div>';
        html += '<div class="stat-section-content" id="section-barrier">';
        html += '<div class="stat-table-wrapper">';
        html += '<table class="stat-table-mini">';
        html += '<thead>';
        html += '<tr><th rowspan="2">檔位範圍</th><th colspan="5">數據庫總數</th><th colspan="5">' + data.seasonLabel + ' 馬季</th></tr>';
        html += '<tr><th>出賽</th><th>頭馬</th><th>勝率</th><th>上名率</th><th>趨勢</th><th>出賽</th><th>頭馬</th><th>勝率</th><th>上名率</th><th>趨勢</th></tr>';
        html += '</thead><tbody>';
        
        for (var i = 0; i < data.barrierAllStats.length; i++) {
            var all = data.barrierAllStats[i];
            var season = data.barrierSeasonStats.find(function(s) { return s.barrier_range == all.barrier_range; }) || null;
            var barWidthAll = (all.win_rate / data.barrierAllStats[0].win_rate) * 50;
            var barWidthSeason = (season && data.barrierSeasonStats[0] && season.win_rate / data.barrierSeasonStats[0].win_rate * 50) || 0;
            
            html += '<tr>';
            html += '<td>' + all.barrier_range + '檔</td>';
            html += '<td>' + all.runs + '次</td><td>' + all.wins + '場</td>';
            html += '<td class="odds-hot">' + all.win_rate + '%</td><td>' + all.place_rate + '%</td>';
            html += '<td><div class="stat-bar" style="width: ' + barWidthAll + 'px; height: 4px; background: #3498db; border-radius: 2px;"></div></td>';
            if (season) {
                html += '<td>' + season.runs + '次</td><td>' + season.wins + '場</td>';
                html += '<td class="odds-hot">' + season.win_rate + '%</td><td>' + season.place_rate + '%</td>';
                html += '<td><div class="stat-bar" style="width: ' + barWidthSeason + 'px; height: 4px; background: #f39c12; border-radius: 2px;"></div></td>';
            } else {
                html += '<td>-</td><td>-</td><td>-</td><td>-</td><td>-</td>';
            }
            html += '</tr>';
        }
        html += '</tbody></table></div></div></div>';
    }
    
    // 骑师统计（双列对比，前15名）
    if (data.jockeyAllStats && data.jockeySeasonStats) {
        html += '<div class="stat-section">';
        html += '<div class="stat-section-title" data-section="jockey">🏇 騎師分析 <span class="toggle-icon">▼</span></div>';
        html += '<div class="stat-section-content" id="section-jockey">';
        html += '<div class="stat-table-wrapper">';
        html += '<table class="stat-table-mini">';
        html += '<thead>';
        html += '<tr><th rowspan="2">騎師</th><th colspan="5">數據庫總數</th><th colspan="5">' + data.seasonLabel + ' 馬季</th></tr>';
        html += '<tr><th>出賽</th><th>頭馬</th><th>勝率</th><th>上名率</th><th>趨勢</th><th>出賽</th><th>頭馬</th><th>勝率</th><th>上名率</th><th>趨勢</th></tr>';
        html += '</thead><tbody>';
        
        var displayCount = Math.min(data.jockeyAllStats.length, 15);
        for (var i = 0; i < displayCount; i++) {
            var all = data.jockeyAllStats[i];
            var season = data.jockeySeasonStats.find(function(s) { return s.jockey_name_ch == all.jockey_name_ch; }) || null;
            var barWidthAll = (all.win_rate / data.jockeyAllStats[0].win_rate) * 50;
            var barWidthSeason = (season && data.jockeySeasonStats[0] && season.win_rate / data.jockeySeasonStats[0].win_rate * 50) || 0;
            
            html += '<tr>';
            html += '<td>' + escapeHtml(all.jockey_name_ch) + '</td>';
            html += '<td>' + all.rides + '次</td><td>' + all.wins + '場</td>';
            html += '<td class="odds-hot">' + all.win_rate + '%</td><td>' + all.place_rate + '%</td>';
            html += '<td><div class="stat-bar" style="width: ' + barWidthAll + 'px; height: 4px; background: #3498db; border-radius: 2px;"></div></td>';
            if (season) {
                html += '<td>' + season.rides + '次</td><td>' + season.wins + '場</td>';
                html += '<td class="odds-hot">' + season.win_rate + '%</td><td>' + season.place_rate + '%</td>';
                html += '<td><div class="stat-bar" style="width: ' + barWidthSeason + 'px; height: 4px; background: #f39c12; border-radius: 2px;"></div></td>';
            } else {
                html += '<td>-</td><td>-</td><td>-</td><td>-</td><td>-</td>';
            }
            html += '</tr>';
        }
        html += '</tbody></table></div></div></div>';
    }
    
    // 练马师统计（双列对比，前15名）
    if (data.trainerAllStats && data.trainerSeasonStats) {
        html += '<div class="stat-section">';
        html += '<div class="stat-section-title" data-section="trainer">👨 練馬師分析 <span class="toggle-icon">▼</span></div>';
        html += '<div class="stat-section-content" id="section-trainer">';
        html += '<div class="stat-table-wrapper">';
        html += '<table class="stat-table-mini">';
        html += '<thead>';
        html += '<tr><th rowspan="2">練馬師</th><th colspan="5">數據庫總數</th><th colspan="5">' + data.seasonLabel + ' 馬季</th></tr>';
        html += '<tr><th>出賽</th><th>頭馬</th><th>勝率</th><th>上名率</th><th>趨勢</th><th>出賽</th><th>頭馬</th><th>勝率</th><th>上名率</th><th>趨勢</th></tr>';
        html += '</thead><tbody>';
        
        var displayCount = Math.min(data.trainerAllStats.length, 15);
        for (var i = 0; i < displayCount; i++) {
            var all = data.trainerAllStats[i];
            var season = data.trainerSeasonStats.find(function(s) { return s.trainer_name_ch == all.trainer_name_ch; }) || null;
            var barWidthAll = (all.win_rate / data.trainerAllStats[0].win_rate) * 50;
            var barWidthSeason = (season && data.trainerSeasonStats[0] && season.win_rate / data.trainerSeasonStats[0].win_rate * 50) || 0;
            
            html += '<tr>';
            html += '<td>' + escapeHtml(all.trainer_name_ch) + '</td>';
            html += '<td>' + all.runs + '次</td><td>' + all.wins + '場</td>';
            html += '<td class="odds-hot">' + all.win_rate + '%</td><td>' + all.place_rate + '%</td>';
            html += '<td><div class="stat-bar" style="width: ' + barWidthAll + 'px; height: 4px; background: #3498db; border-radius: 2px;"></div></td>';
            if (season) {
                html += '<td>' + season.runs + '次</td><td>' + season.wins + '場</td>';
                html += '<td class="odds-hot">' + season.win_rate + '%</td><td>' + season.place_rate + '%</td>';
                html += '<td><div class="stat-bar" style="width: ' + barWidthSeason + 'px; height: 4px; background: #f39c12; border-radius: 2px;"></div></td>';
            } else {
                html += '<td>-</td><td>-</td><td>-</td><td>-</td><td>-</td>';
            }
            html += '</tr>';
        }
        html += '</tbody></table></div></div></div>';
    }
    
    // 赔率统计（双列对比）
    if (data.oddsAllStats && data.oddsSeasonStats) {
        html += '<div class="stat-section">';
        html += '<div class="stat-section-title" data-section="odds">💰 賠率分析 <span class="toggle-icon">▼</span></div>';
        html += '<div class="stat-section-content" id="section-odds">';
        html += '<div class="stat-table-wrapper">';
        html += '<table class="stat-table-mini">';
        html += '<thead>';
        html += '<tr><th rowspan="2">賠率範圍</th><th colspan="5">數據庫總數</th><th colspan="5">' + data.seasonLabel + ' 馬季</th></tr>';
        html += '<tr><th>出賽</th><th>頭馬</th><th>勝率</th><th>上名率</th><th>趨勢</th><th>出賽</th><th>頭馬</th><th>勝率</th><th>上名率</th><th>趨勢</th></tr>';
        html += '</thead><tbody>';
        
        for (var i = 0; i < data.oddsAllStats.length; i++) {
            var all = data.oddsAllStats[i];
            var season = data.oddsSeasonStats.find(function(s) { return s.odds_range == all.odds_range; }) || null;
            var barWidthAll = (all.win_rate / data.oddsAllStats[0].win_rate) * 50;
            var barWidthSeason = (season && data.oddsSeasonStats[0] && season.win_rate / data.oddsSeasonStats[0].win_rate * 50) || 0;
            
            html += '<tr>';
            html += '<td>' + all.odds_range + '</td>';
            html += '<td>' + all.runs + '次</td><td>' + all.wins + '場</td>';
            html += '<td class="odds-hot">' + all.win_rate + '%</td><td>' + all.place_rate + '%</td>';
            html += '<td><div class="stat-bar" style="width: ' + barWidthAll + 'px; height: 4px; background: #3498db; border-radius: 2px;"></div></td>';
            if (season) {
                html += '<td>' + season.runs + '次</td><td>' + season.wins + '場</td>';
                html += '<td class="odds-hot">' + season.win_rate + '%</td><td>' + season.place_rate + '%</td>';
                html += '<td><div class="stat-bar" style="width: ' + barWidthSeason + 'px; height: 4px; background: #f39c12; border-radius: 2px;"></div></td>';
            } else {
                html += '<td>-</td><td>-</td><td>-</td><td>-</td><td>-</td>';
            }
            html += '</tr>';
        }
        html += '</tbody></table></div></div></div>';
    }
    
    $('#statsReference').html(html);
    
    // 绑定折叠功能
    $('.stat-section-title').click(function() {
        var sectionId = $(this).data('section');
        var content = $('#section-' + sectionId);
        content.toggleClass('collapsed');
        var icon = $(this).find('.toggle-icon');
        if (content.hasClass('collapsed')) {
            icon.text('▶');
        } else {
            icon.text('▼');
        }
    });
}

function displayPredictionResult(data) {
    var html = '';
    
    html += '<div class="prediction-result-card">';
    html += '<div class="prediction-number">' + data.predicted_win_rate + '%</div>';
    html += '<div class="prediction-label">預測勝率</div>';
    html += '<div class="prediction-stats-grid">';
    html += '<div class="prediction-stat"><div class="prediction-stat-value">' + data.confidence + '%</div><div class="prediction-stat-label">信心指數</div></div>';
    html += '<div class="prediction-stat"><div class="prediction-stat-value">' + data.sample_size + '</div><div class="prediction-stat-label">參考樣本數</div></div>';
    html += '<div class="prediction-stat"><div class="prediction-stat-value">' + (data.factors ? data.factors.length : 0) + '</div><div class="prediction-stat-label">分析因素</div></div>';
    html += '</div></div>';
    
    if (data.factors && data.factors.length > 0) {
        html += '<div class="factor-list">';
        html += '<strong>📋 詳細分析</strong>';
        for (var i = 0; i < data.factors.length; i++) {
            var f = data.factors[i];
            var impactClass = f.impact > 0 ? 'factor-impact-positive' : (f.impact < 0 ? 'factor-impact-negative' : 'factor-impact-neutral');
            html += '<div class="factor-item">';
            html += '<span class="factor-name">' + f.name + '</span>';
            html += '<span>' + f.value + ' → <span class="' + impactClass + '">' + (f.impact > 0 ? '+' : '') + f.impact + '%</span></span>';
            html += '</div>';
        }
        html += '</div>';
    }
    
    if (data.advice) {
        html += '<div style="margin-top: 15px; padding: 12px; background: #0a2a4a; border-radius: 8px;">';
        html += '<strong>💡 建議：</strong> ' + data.advice;
        html += '</div>';
    }
    
    $('#resultContent').html(html);
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
</script>