<?php
// analysis_upcoming.php - 实时赛事分析组件 (V2 with sortable table)

$today = date('Y-m-d');

// 获取今天的赛事
$stmt = $pdo->prepare("
    SELECT DISTINCT m.date, m.venue_code
    FROM hkracing_meetings m
    WHERE m.date >= ?
    ORDER BY m.date
    LIMIT 3
");
$stmt->execute([$today]);
$availableDates = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selectedDate = $_GET['date'] ?? ($availableDates[0]['date'] ?? '');
$selectedVenue = $_GET['venue'] ?? ($availableDates[0]['venue_code'] ?? '');

if ($selectedDate && $selectedVenue) {
    $stmt = $pdo->prepare("
        SELECT race_no, race_class_ch FROM hkracing_races r
        JOIN hkracing_meetings m ON r.meeting_id = m.id
        WHERE m.date = ? AND m.venue_code = ?
        ORDER BY race_no
    ");
    $stmt->execute([$selectedDate, $selectedVenue]);
    $races = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM hkracing_odds_horse_details 
        WHERE race_date = ? AND venue_code = ?
    ");
    $stmt->execute([$selectedDate, $selectedVenue]);
    $hasOdds = $stmt->fetchColumn() > 0;
} else {
    $races = [];
    $hasOdds = false;
}
?>

<style>
/* ========== V2 完整專業 CSS 樣式 ========== */
* {
    box-sizing: border-box;
}

.race-analysis-v2 {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    margin-top: 24px;
}

/* 賽事標題區 */
.race-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
    color: white;
    padding: 20px 24px;
    border-radius: 16px;
    margin-bottom: 24px;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
}

.race-header h3 {
    margin: 0 0 8px 0;
    font-size: 1.4rem;
    font-weight: 600;
}

.race-header h3::before {
    content: "🏇 ";
}

.race-header div {
    opacity: 0.8;
    font-size: 0.85rem;
    display: inline-block;
    margin-right: 24px;
}

/* 班次標籤 - 不同顏色 */
.class-label {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.class-classic { background: #3b82f6; color: white; }
.class-premier { background: #8b5cf6; color: white; }
.class-group { background: #10b981; color: white; }
.class-ordinary { background: #6b7280; color: white; }
.class-default { background: #f59e0b; color: white; }

/* 表格容器 */
.table-wrapper {
    overflow-x: auto;
    border-radius: 12px;
    background: white;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
}

/* 可排序表格 */
.sortable-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8rem;
    min-width: 1100px;
}

.sortable-table th {
    background: #f8fafc;
    color: #1e293b;
    padding: 14px 12px;
    text-align: center;
    font-weight: 600;
    cursor: pointer;
    user-select: none;
    white-space: nowrap;
    border-bottom: 2px solid #e2e8f0;
    transition: all 0.2s ease;
}

.sortable-table th:hover {
    background: #f1f5f9;
    color: #3b82f6;
}

.sortable-table th.sorted-asc {
    background: #eff6ff;
    color: #2563eb;
    border-bottom-color: #3b82f6;
}

.sortable-table th.sorted-asc::after {
    content: " ▲";
    font-size: 10px;
}

.sortable-table th.sorted-desc {
    background: #eff6ff;
    color: #2563eb;
    border-bottom-color: #3b82f6;
}

.sortable-table th.sorted-desc::after {
    content: " ▼";
    font-size: 10px;
}

.sortable-table td {
    padding: 12px 10px;
    border-bottom: 1px solid #f1f5f9;
    text-align: center;
    color: #334155;
}

.sortable-table tbody tr {
    transition: background 0.15s ease;
}

.sortable-table tbody tr:hover {
    background: #fefce8;
}

/* 檔位欄位 */
.draw-cell {
    font-weight: 700;
    background: #fef3c7;
    color: #92400e;
    text-align: center;
    font-size: 1rem;
}
.runner-no-cell {
    font-weight: 700;
    /*background: #fef3c7;*/
    color: #92400e;
    text-align: center;
    font-size: 1rem;
}
/* 馬匹名稱 */
.horse-name {
    font-weight: 700;
    text-align: left;
    color: #0f172a;
}

/* 騎師/練馬師 */
.jockey-name, .trainer-name {
    text-align: left;
    color: #475569;
}

/* 賠率 */
.odds-cell {
    color: #dc2626;
    font-weight: 700;
}

.odds-value {
    display: inline-block;
    background: #fef2f2;
    padding: 4px 10px;
    border-radius: 20px;
}

/* 預測標籤 */
.prediction-cell {
    text-align: center;
}

.prediction-hot {
    display: inline-block;
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.7rem;
}

.prediction-equal {
    display: inline-block;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.7rem;
}

.prediction-cold {
    display: inline-block;
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.7rem;
}

.prediction-unknown {
    display: inline-block;
    background: #e2e8f0;
    color: #64748b;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 0.7rem;
}

/* 勝率欄位 */
.win-rate-cell {
    font-weight: 700;
    color: #10b981;
}

.win-rate-high {
    color: #dc2626;
    font-weight: 800;
}

/* V2 進階欄位 */
.v2-col {
    background: #fafbfc;
    font-size: 0.75rem;
    text-align: center;
}

/* 分析總結 */
.analysis-summary {
    margin-top: 20px;
    padding: 16px 20px;
    background: linear-gradient(135deg, #f0f9ff 0%, #eef2ff 100%);
    border-radius: 12px;
    border-left: 4px solid #3b82f6;
}

.analysis-summary h4 {
    margin: 0 0 8px 0;
    color: #0f172a;
    font-size: 0.95rem;
    font-weight: 700;
}

.analysis-summary p {
    margin: 6px 0;
    color: #334155;
    font-size: 0.8rem;
}

.analysis-summary .win-rate-meaning {
    background: #e0f2fe;
    padding: 8px 12px;
    border-radius: 8px;
    margin-top: 10px;
    font-size: 0.75rem;
    color: #0369a1;
}

.analysis-summary small {
    font-size: 0.7rem;
    color: #64748b;
    display: block;
    margin-top: 10px;
    padding-top: 8px;
    border-top: 1px solid #cbd5e1;
}

/* 載入動畫 */
.loading {
    text-align: center;
    padding: 40px;
    color: #64748b;
}

.loading-spinner {
    display: inline-block;
    width: 30px;
    height: 30px;
    border: 3px solid #e2e8f0;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-right: 10px;
    vertical-align: middle;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* 賽事選擇器 */
.race-selector {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
    margin-bottom: 24px;
    background: #f8fafc;
    padding: 16px 20px;
    border-radius: 16px;
}

.race-selector select {
    padding: 10px 16px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: white;
    font-size: 0.85rem;
    cursor: pointer;
    color: #1e293b;
}

.race-selector button {
    padding: 10px 24px;
    border-radius: 10px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    border: none;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.race-selector button:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59,130,246,0.3);
}

/* 響應式 */
@media (max-width: 1000px) {
    .v2-col {
        display: none;
    }
    .sortable-table {
        min-width: 900px;
    }
}

/* 滾動條美化 */
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
/* 響應式 */
@media (max-width: 1000px) {
    /* Keep v2-col visible on mobile */
    .v2-col {
        display: table-cell !important;
    }
    
    /* Make score cell smaller on mobile */
    .score-cell {
        padding: 2px 6px;
        font-size: 0.65rem;
        min-width: 35px;
    }
    
    /* Adjust table for better mobile viewing */
    .sortable-table th,
    .sortable-table td {
        padding: 8px 6px;
        font-size: 0.7rem;
    }
}

@media (max-width: 768px) {
    /* Ensure horizontal scroll on very small screens */
    .table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .sortable-table {
        min-width: 750px;
    }
    
    /* Keep all columns visible */
    .v2-col {
        display: table-cell !important;
    }
}

/* 新增样式 */
.trainer-count, .jockey-count {
    color: #888;
    font-size: 11px;
    margin-left: 2px;
}

.heart-icon {
    color: #e94560;
    margin-left: 4px;
}

.odds-drop-cell {
    color: #888;
    font-size: 0.8rem;
}

.draw-cell {
    font-weight: bold;
    text-align: center;
}

/* 档位颜色 */
.draw-cell.draw-1-3 { color: #10b981; }
.draw-cell.draw-4-6 { color: #f59e0b; }
.draw-cell.draw-7-9 { color: #ef4444; }
.draw-cell.draw-10-plus { color: #6b7280; }

</style>

<div class="card">
    <div class="card-title">📅 即時賽事分析</div>
    
    <?php if (empty($availableDates)): ?>
    <div class="loading">📭 暫時沒有即將舉行的賽事</div>
    <?php else: ?>
    <div class="race-selector">
        <!--<select id="raceDate" onchange="updateRaceVenue()">-->
        <!--    <?php foreach ($availableDates as $date): ?>-->
        <!--    <option value="<?php echo $date['date']; ?>" data-venue="<?php echo $date['venue_code']; ?>" -->
        <!--        <?php echo $selectedDate == $date['date'] ? 'selected' : ''; ?>>-->
        <!--        <?php echo $date['date']; ?> - <?php echo $date['venue_code'] == 'ST' ? '沙田' : '跑馬地'; ?>-->
        <!--    </option>-->
        <!--    <?php endforeach; ?>-->
        <!--</select>-->
        <select id="raceDate" onchange="updateRaceVenue()">
            <?php foreach ($availableDates as $date): ?>
            <option value="<?php echo $date['date']; ?>" data-venue="<?php echo $date['venue_code']; ?>" 
                <?php echo $selectedDate == $date['date'] ? 'selected' : ''; ?>>
                <?php 
                $venueCode = $date['venue_code'];
                if ($venueCode == 'ST') {
                    $venueName = '沙田';
                } elseif ($venueCode == 'HV') {
                    $venueName = '跑馬地';
                } else {
                    // 海外场地
                    $venueName = '海外 (' . $venueCode . ')';
                }
                echo $date['date'] . ' - ' . $venueName;
                ?>
            </option>
            <?php endforeach; ?>
        </select>
        
        <select id="raceSelect">
            <?php foreach ($races as $race): ?>
            <option value="<?php echo $race['race_no']; ?>">第 <?php echo $race['race_no']; ?> 場</option>
            <?php endforeach; ?>
        </select>
        
        <button class="btn" onclick="loadRaceAnalysis()">🔍 分析</button>
        
        <?php if ($hasOdds): ?>
        <span style="margin-left: 10px; padding: 5px 10px; background: #27ae60; border-radius: 8px; font-size: 12px; color:white;">✅ 賠率已發佈</span>
        <?php else: ?>
        <span style="margin-left: 10px; padding: 5px 10px; background: #f39c12; border-radius: 8px; font-size: 12px; color:white;">⏳ 賠率尚未發佈</span>
        <?php endif; ?>
    </div>
    
    <div id="raceAnalysisResult">
        <div class="loading">請選擇場次進行分析...</div>
    </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function updateRaceVenue() {
    const selected = $('#raceDate option:selected');
    const venue = selected.data('venue');
    const date = $('#raceDate').val();
    
    $.ajax({
        url: 'analysis_api.php',
        type: 'GET',
        data: { action: 'getRaces', date: date, venue: venue },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.races) {
                let html = '';
                response.races.forEach(race => {
                    html += `<option value="${race.race_no}" data-class="${race.race_class_ch || ''}">第 ${race.race_no} 場</option>`;
                });
                $('#raceSelect').html(html);
            }
        }
    });
}

function loadRaceAnalysis() {
    const date = $('#raceDate').val();
    const venue = $('#raceDate option:selected').data('venue');
    const raceNo = $('#raceSelect').val();
    
    if (!date || !venue || !raceNo) {
        alert('請選擇完整的賽事信息');
        return;
    }
    
    $('#raceAnalysisResult').html('<div class="loading"><div class="loading-spinner"></div>分析中...</div>');
    
    $.ajax({
        url: 'analysis_api.php',
        type: 'GET',
        data: { action: 'raceDetail', date: date, venue: venue, race_no: raceNo },
        dataType: 'html',
        success: function(html) {
            $('#raceAnalysisResult').html(html);
            initSortableTable();
        },
        error: function(xhr, status, error) {
            $('#raceAnalysisResult').html('<div class="loading">❌ 分析失敗: ' + error + '</div>');
        }
    });
}

// 表格排序功能
function initSortableTable() {
    const table = document.querySelector('.sortable-table');
    if (!table) return;
    
    const headers = table.querySelectorAll('th');
    let currentSortColumn = -1;
    let currentSortOrder = 'asc';
    
    headers.forEach((header, index) => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', () => {
            headers.forEach(h => {
                h.classList.remove('sorted-asc', 'sorted-desc');
            });
            
            if (currentSortColumn === index) {
                currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                currentSortOrder = 'asc';
                currentSortColumn = index;
            }
            
            header.classList.add(currentSortOrder === 'asc' ? 'sorted-asc' : 'sorted-desc');
            
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            const isNumeric = (colIndex) => {
                const firstRow = rows[0];
                if (!firstRow) return false;
                const cellText = firstRow.children[colIndex]?.innerText || '';
                return /^[\d.-]+%?倍?$/.test(cellText.replace(/[%倍]/g, ''));
            };
            
            rows.sort((rowA, rowB) => {
                let valA = rowA.children[index]?.innerText || '';
                let valB = rowB.children[index]?.innerText || '';
                
                if (isNumeric(index)) {
                    valA = parseFloat(valA.replace(/[%倍]/g, '')) || 0;
                    valB = parseFloat(valB.replace(/[%倍]/g, '')) || 0;
                }
                
                if (valA < valB) return currentSortOrder === 'asc' ? -1 : 1;
                if (valA > valB) return currentSortOrder === 'asc' ? 1 : -1;
                return 0;
            });
            
            rows.forEach(row => tbody.appendChild(row));
        });
    });
}

// function toggleHelpPanel() {
//     const content = document.querySelector('.help-content');
//     const btn = document.querySelector('.help-toggle');
//     if (content.style.display === 'none') {
//         content.style.display = 'block';
//         btn.innerHTML = '📘 顯示/隱藏 欄位說明 ▲';
//     } else {
//         content.style.display = 'none';
//         btn.innerHTML = '📘 顯示/隱藏 欄位說明 ▼';
//     }
// }

// 修改现有的 toggleHelpPanel 函数，支持指定要切换的面板
// 修改现有的 toggleHelpPanel 函数，支持指定要切换的面板
function toggleHelpPanel(element) {
    var panel;
    var button;
    
    if (typeof element === 'string') {
        // 传入的是 panelId 字符串
        panel = document.getElementById(element);
        if (panel) {
            button = panel.previousElementSibling;
            while (button && button.tagName !== 'BUTTON') {
                button = button.previousElementSibling;
            }
        }
    } else {
        // 传入的是按钮元素
        button = element;
        var helpSection = button.closest('.help-section');
        if (helpSection) {
            panel = helpSection.querySelector('.help-content');
        }
    }
    
    if (panel) {
        if (panel.style.display === 'none' || panel.style.display === '') {
            panel.style.display = 'block';
            if (button) {
                // 根据不同的面板更新不同的文字
                if (panelId === 'priorityPanel') {
                    const iconSpan = button.querySelector('#priorityPanelIcon');
                    if (iconSpan) {
                        iconSpan.innerHTML = '▲';
                    } else {
                        button.innerHTML = button.innerHTML.replace('▼', '▲');
                    }
                } else if (panelId === 'analysisBasis') {
                    button.innerHTML = '📐 顯示/隱藏 分析依據說明 ▲';
                } else {
                    // 原有的欄位說明按钮
                    button.innerHTML = '📘 顯示/隱藏 欄位說明 ▲';
                }
            }
        } else {
            panel.style.display = 'none';
            if (button) {
                if (panelId === 'priorityPanel') {
                    const iconSpan = button.querySelector('#priorityPanelIcon');
                    if (iconSpan) {
                        iconSpan.innerHTML = '▼';
                    } else {
                        button.innerHTML = button.innerHTML.replace('▲', '▼');
                    }
                } else if (panelId === 'analysisBasis') {
                    button.innerHTML = '📐 顯示/隱藏 分析依據說明 ▼';
                } else {
                    // 原有的欄位說明按钮
                    button.innerHTML = '📘 顯示/隱藏 欄位說明 ▼';
                }
            }
        }
    }
}

function togglePriorityPanel() {
    var panel = document.getElementById('priorityPanel');
    var icon = document.getElementById('priorityPanelIcon');
    if (panel.style.display === 'none' || panel.style.display === '') {
        panel.style.display = 'block';
        icon.innerHTML = '▲';
    } else {
        panel.style.display = 'none';
        icon.innerHTML = '▼';
    }
}

function toggleAnalysisBasis() {
    var panel = document.getElementById('analysisBasis');
    var btn = event.currentTarget;
    if (panel.style.display === 'none' || panel.style.display === '') {
        panel.style.display = 'block';
        btn.innerHTML = '📐 顯示/隱藏 分析依據說明 ▲';
    } else {
        panel.style.display = 'none';
        btn.innerHTML = '📐 顯示/隱藏 分析依據說明 ▼';
    }
}

$(document).ready(function() {
    initSortableTable();
});
</script>