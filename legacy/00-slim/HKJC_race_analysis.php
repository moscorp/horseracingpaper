<?php
$isBot = false;
$botAgents = ['Googlebot', 'Bingbot', 'Slurp', 'DuckDuckBot', 'Baiduspider', 'YandexBot'];

foreach ($botAgents as $bot) {
    if (stripos($_SERVER['HTTP_USER_AGENT'], $bot) !== false) {
        $isBot = true;
        break;
    }
}

// 如果是搜索引擎爬虫，直接返回完整 HTML 内容
if ($isBot || isset($_GET['full'])) {
    // 直接输出完整的分析内容（不依赖 JS）
    renderFullAnalysisContent();
    exit;
}

// HKJC_race_analysis.php - Main page

include_once ("lib/constants.php");

date_default_timezone_set('Asia/Hong_Kong');

// Get current selected tab
$activeTab = $_GET['tab'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="zh-HK">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🏇 Hong Kong Horse Racing Analysis | HKJC Stats, Odds & Predictions | 賽馬分析系統 - HorsePaper</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #e0e0e0;
        }
        /* Use .app or .container to control content spacing */
        .app-content {
            padding: 0px;  /* This is the spacing you want */
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0px;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        }
        
        /* Header styles */
        .header {
            /*background: linear-gradient(135deg, #0f3460 0%, #1a1a2e 100%);*/
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border-radius: 15px;
            padding: 20px 30px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            border: 1px solid #e94560;
        }
        
        .header h1 {
            color: #e94560;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header .subtitle {
            color: #8899aa;
            font-size: 14px;
        }
        
        /* Data status */
        .data-status {
            background: #1e2a3a;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-left: 4px solid #e94560;
        }
        
        .data-status.complete {
            border-left-color: #27ae60;
        }
        
        .status-bar {
            background: #0f3460;
            border-radius: 10px;
            height: 30px;
            margin-top: 10px;
            overflow: hidden;
        }
        
        .status-fill {
            background: linear-gradient(90deg, #e94560, #f39c12);
            height: 100%;
            border-radius: 10px;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        
        /* Navigation tabs */
        .nav-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .nav-tab {
            background: #1e2a3a;
            padding: 12px 10px;  /*12px 25px*/
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            color: #8899aa;
            font-size: 14px;
        }
        
        .nav-tab:hover {
            background: #2a3a4a;
        }
        
        .nav-tab.active {
            background: #e94560;
            color: white;
        }
        
        /* Content area */
        .content-area {
            min-height: 500px;
        }
        
        /* Loading animation */
        .loading {
            text-align: center;
            padding: 60px;
            color: #8899aa;
        }
        
        .loading-spinner {
            border: 3px solid #2a3a4a;
            border-top: 3px solid #e94560;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Card */
        .card {
            background: #1e2a3a;
            border-radius: 12px;
            padding: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .card-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e94560;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* Table styles - prevent line breaks */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .data-table th,
        .data-table td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #2a3a4a;
            white-space: nowrap;  /* Prevent line breaks */
        }
        
        .data-table th {
            background: #0f3460;
            color: #e94560;
            font-weight: bold;
            white-space: nowrap;  /* Force header no line break */
        }
        
        .data-table tr:hover {
            background: #2a3a4a;
        }
        
        /* Allow specific columns to wrap (e.g., notes column) */
        .data-table td.allow-wrap,
        .data-table th.allow-wrap {
            white-space: normal;
            min-width: 150px;
            max-width: 250px;
        }
        
        /* Scroll container - when table is too wide */
        .table-wrapper {
            overflow-x: auto;
            margin: 0 -5px;
            padding: 0 5px;
        }
        
        /* Statistics cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: #0f3460;
            border-radius: 10px;
            padding: 15px;
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
        
        /* Odds labels */
        .odds-hot {
            background: #e94560;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            display: inline-block;
        }
        
        .odds-up {
            background: #27ae60;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
        }
        
        .odds-down {
            background: #e74c3c;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
        }
        
        /* Race selector */
        .race-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .race-selector select,
        .race-selector input {
            background: #0f3460;
            border: 1px solid #2a3a4a;
            padding: 8px 15px;
            border-radius: 8px;
            color: white;
        }
        
        .btn {
            background: #e94560;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #c7354e;
        }
        
        /* Filter */
        .filter-select {
            background: #0f3460;
            border: 1px solid #2a3a4a;
            padding: 8px 15px;
            border-radius: 8px;
            color: white;
        }
        
        /* Responsive */
        /*@media (max-width: 768px) {*/
        /*    .data-table th,*/
        /*    .data-table td {*/
                white-space: normal;  /* Mobile version allows line breaks */
        /*        min-width: 80px;*/
        /*        font-size: 11px;*/
        /*        padding: 8px 6px;*/
        /*    }*/
        /*    .stats-grid {*/
        /*        grid-template-columns: repeat(2, 1fr);*/
        /*    }*/
        /*    .nav-tab {*/
        /*        padding: 8px 15px;*/
        /*        font-size: 12px;*/
        /*    }*/
        /*    .card {*/
        /*        padding: 15px;*/
        /*    }*/
        /*}*/
        @media (max-width: 768px) {
            /* Table horizontal scroll */
            .stat-table-wrapper,
            .table-wrapper,
            [class*="table-wrapper"] {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
                margin: 0 -12px !important;
                padding: 0 12px !important;
                width: calc(100% + 24px) !important;
            }
            
            /* Ensure table has minimum width */
            .data-table,
            .stat-table-mini,
            [class*="data-table"],
            [class*="stat-table"] {
                min-width: 650px;
                width: auto;
            }
            
            /* Card grid optimization */
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            /* Font adjustment */
            body {
                font-size: 13px;
            }
            
            .card {
                padding: 12px;
            }
            
            .stat-number {
                font-size: 20px;
            }
            
            /* Navigation tabs wrap */
            .nav-tabs {
                gap: 8px;
            }
            
            .nav-tab {
                padding: 6px 12px;
                font-size: 11px;
            }
        }
        /* Desktop version force no line break */
        @media (min-width: 769px) {
            .data-table th,
            .data-table td {
                white-space: nowrap;
            }
        }
        /* Default: desktop version shows, mobile version hides */
        .desktop-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .mobile-nav {
            display: none;
            margin-bottom: 20px;
        }
        
        .mobile-nav-select {
            width: 100%;
            padding: 12px 15px;
            background: #1e2a3a;
            border: 1px solid #e94560;
            border-radius: 10px;
            color: white;
            font-size: 16px;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='white'><path d='M7 10l5 5 5-5z'/></svg>");
            background-repeat: no-repeat;
            background-position: right 15px center;
            margin-bottom: 10px;  /* 👈 添加这一行：下拉菜单底部间距 */
        }
            /* 手机版按钮容器 */
        .mobile-buttons {
            display: flex;
            gap: 10px;
            margin-top: 0;  /* 确保没有额外上边距 */
        }
        
        /* 手机版按钮样式 */
        .mobile-buttons .nav-tab {
            flex: 1;
            text-align: center;
            padding: 10px 12px;
            font-size: 13px;
        }
        
        /* 调整标签样式让下拉菜单更美观 */
        .mobile-nav-select option {
            background: #1e2a3a;
            color: white;
            padding: 10px;
        }
        
        .mobile-nav-select:focus {
            outline: none;
            border-color: #f39c12;
        }
        
        /* Mobile version: hide desktop navigation, show dropdown menu */
        @media (max-width: 768px) {
            .desktop-nav {
                display: none;
            }
            
            .mobile-nav {
                display: block;
            }
            
            /* Adjust tab styles for better dropdown appearance */
            .mobile-nav-select option {
                background: #1e2a3a;
                color: white;
                padding: 10px;
            }
        }
        
        /* Tablet devices (optional, keep desktop version between 769px-1024px but allow wrapping) */
        @media (min-width: 769px) and (max-width: 1024px) {
            .desktop-nav {
                gap: 8px;
            }
            
            .nav-tab {
                padding: 8px 12px;
                font-size: 12px;
            }
        }
        /* Class label styles - for race class display */
        .class-label {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .class-premier { 
            background: #8b5cf6; 
            color: white; 
        }  /* Class 1 - Purple */
        
        .class-classic { 
            background: #3b82f6; 
            color: white; 
        }  /* Class 2 - Blue */
        
        .class-group { 
            background: #10b981; 
            color: white; 
        }  /* Class 3 - Green */
        
        .class-ordinary { 
            background: #6b7280; 
            color: white; 
        }  /* Class 4 & 5 - Gray */
        
        .class-default { 
            background: #f59e0b; 
            color: white; 
        }  /* Default - Orange */
        
        /* Score cell styles */
        .score-cell {
            display: inline-block;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #fbbf24;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            min-width: 45px;
        }
        
        /* Class change cell */
        .class-change-cell {
            font-size: 0.75rem;
            white-space: nowrap;
        }
        
        /* Win rate high */
        .win-rate-high {
            color: #dc2626;
            font-weight: 800;
        }
        
        .win-rate-cell {
            font-weight: 700;
            color: #10b981;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <div class="header">
        <h1>🏇 賽馬分析系統</h1>
        <div class="subtitle">基於歷史數據 + 即時賠率 | 香港賽馬會數據</div>
    </div>
    
    <!-- Navigation tabs - Desktop version -->
    <div class="nav-tabs desktop-nav">
        <button class="nav-tab <?php echo $activeTab == 'dashboard' ? 'active' : ''; ?>" data-tab="dashboard">📊 分析儀表板</button>
        <button class="nav-tab <?php echo $activeTab == 'horses' ? 'active' : ''; ?>" data-tab="horses">🐴 馬匹勝率榜</button>
        <button class="nav-tab <?php echo $activeTab == 'trainers' ? 'active' : ''; ?>" data-tab="trainers">🏆 練馬師勝率榜</button>
        <button class="nav-tab <?php echo $activeTab == 'jockeys' ? 'active' : ''; ?>" data-tab="jockeys">🎠 騎師勝率榜</button>
        <button class="nav-tab <?php echo $activeTab == 'jockey_trainer' ? 'active' : ''; ?>" data-tab="jockey_trainer">🤝 騎練合作分析</button>
        <button class="nav-tab <?php echo $activeTab == 'track' ? 'active' : ''; ?>" data-tab="track">🏟️ 場地性能分析</button>
        <button class="nav-tab <?php echo $activeTab == 'jockey_barrier' ? 'active' : ''; ?>" data-tab="jockey_barrier">🎯 騎師檔位偏好</button>
        <button class="nav-tab <?php echo $activeTab == 'prediction' ? 'active' : ''; ?>" data-tab="prediction">🔮 勝率預測</button>
        <button class="nav-tab <?php echo $activeTab == 'odds' ? 'active' : ''; ?>" data-tab="odds">📈 賠率趨勢分析</button>
        <button class="nav-tab <?php echo $activeTab == 'upcoming' ? 'active' : ''; ?>" data-tab="upcoming">📅 即時賽事分析</button>
    </div>
    
    <!-- Navigation dropdown - Mobile version -->
    <div class="mobile-nav">
        <select id="mobileNavSelect" class="mobile-nav-select">
            <option value="dashboard" <?php echo $activeTab == 'dashboard' ? 'selected' : ''; ?>>📊 分析儀表板</option>
            <option value="horses" <?php echo $activeTab == 'horses' ? 'selected' : ''; ?>>🐴 馬匹勝率榜</option>
            <option value="trainers" <?php echo $activeTab == 'trainers' ? 'selected' : ''; ?>>🏆 練馬師勝率榜</option>
            <option value="jockeys" <?php echo $activeTab == 'jockeys' ? 'selected' : ''; ?>>🎠 騎師勝率榜</option>
            <option value="jockey_trainer" <?php echo $activeTab == 'jockey_trainer' ? 'selected' : ''; ?>>🤝 騎練合作分析</option>
            <option value="track" <?php echo $activeTab == 'track' ? 'selected' : ''; ?>>🏟️ 場地性能分析</option>
            <option value="jockey_barrier" <?php echo $activeTab == 'jockey_barrier' ? 'selected' : ''; ?>>🎯 騎師檔位偏好</option>
            <option value="prediction" <?php echo $activeTab == 'prediction' ? 'selected' : ''; ?>>🔮 勝率預測</option>
            <!--<option value="odds" <?php echo $activeTab == 'odds' ? 'selected' : ''; ?>>📈 賠率趨勢分析</option>-->
            <!--<option value="upcoming" <?php echo $activeTab == 'upcoming' ? 'selected' : ''; ?>>📅 即時賽事分析</option>-->
        </select>
        <button class="nav-tab <?php echo $activeTab == 'odds' ? 'active' : ''; ?>" data-tab="odds">📈 賠率趨勢分析</button>
        <button class="nav-tab <?php echo $activeTab == 'upcoming' ? 'active' : ''; ?>" data-tab="upcoming">📅 即時賽事分析</button>
    </div>
    <!-- Content area -->
    <div id="contentArea" class="content-area">
        <div class="loading"><div class="loading-spinner"></div>加載中...</div>
    </div>
    
    <!-- Data status area -->
    <div id="dataStatus" class="data-status">
        <div class="loading"><div class="loading-spinner"></div>加載數據狀態...</div>
    </div>
</div>

<!-- Notify parent page to adjust iframe height - Fix double scrollbar issue -->
<script>
(function() {
    // Notify parent page to adjust iframe height
    function notifyParentResize() {
        if (window.parent !== window) {
            try {
                // Get actual content height
                const height = Math.max(
                    document.body.scrollHeight,
                    document.body.offsetHeight,
                    document.documentElement.clientHeight,
                    document.documentElement.scrollHeight,
                    document.documentElement.offsetHeight
                );
                
                if (height > 0) {
                    // Method 1: postMessage (most reliable, supports cross-origin)
                    window.parent.postMessage({
                        type: 'resizeIframe',
                        height: height
                    }, '*');
                    
                    // Method 2: Directly try to modify parent page iframe (works for same origin)
                    try {
                        const iframe = window.parent.document.getElementById('racing-iframe');
                        if (iframe && iframe.style && iframe.style.height !== height + 'px') {
                            iframe.style.height = height + 'px';
                            console.log('Iframe resized to:', height + 'px');
                        }
                    } catch(e) {
                        // Cross-origin error, ignore
                    }
                }
            } catch(e) {
                console.log('notifyParentResize error:', e);
            }
        }
    }

    // Notify after page fully loads
    window.addEventListener('load', function() {
        setTimeout(notifyParentResize, 50);
        setTimeout(notifyParentResize, 200);
        setTimeout(notifyParentResize, 500);
        setTimeout(notifyParentResize, 1000);
    });

    // Listen for AJAX request completion (your page uses jQuery)
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ajaxComplete(function(event, xhr, settings) {
            // Delay height adjustment after AJAX completes
            setTimeout(notifyParentResize, 100);
            setTimeout(notifyParentResize, 300);
        });
    }

    // Listen for DOM changes (auto-adjust when content loads dynamically)
    if (window.MutationObserver) {
        let resizeTimeout;
        const observer = new MutationObserver(function() {
            // Debounce to avoid frequent adjustments
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                notifyParentResize();
            }, 100);
        });
        
        // Observe entire document changes
        observer.observe(document.body, {
            childList: true,      // Child element changes
            subtree: true,        // All descendant elements
            attributes: true,     // Attribute changes
            attributeFilter: ['style', 'class', 'height', 'width'] // Only focus on these attributes
        });
    }

    // Readjust when window size changes
    window.addEventListener('resize', function() {
        setTimeout(notifyParentResize, 50);
    });

    // Readjust after all images load
    function handleImages() {
        const images = document.querySelectorAll('img');
        let loadedCount = 0;
        images.forEach(img => {
            if (img.complete) {
                loadedCount++;
            } else {
                img.addEventListener('load', function() {
                    setTimeout(notifyParentResize, 100);
                });
            }
        });
        if (loadedCount === images.length && images.length > 0) {
            setTimeout(notifyParentResize, 100);
        }
    }
    window.addEventListener('load', handleImages);
    
    // Periodic check (fallback, check height change every 2 seconds)
    let lastHeight = 0;
    setInterval(function() {
        const currentHeight = Math.max(
            document.body.scrollHeight,
            document.documentElement.scrollHeight
        );
        // If height changes more than 5px, readjust
        if (Math.abs(currentHeight - lastHeight) > 5) {
            lastHeight = currentHeight;
            notifyParentResize();
        }
    }, 2000);
    
    // Execute immediately once
    setTimeout(notifyParentResize, 10);
})();
</script>

<!-- Your original script remains unchanged -->
<script>
$(document).ready(function() {
    // Load data status
    loadDataStatus();
    
    // Load default content
    loadContent('<?php echo $activeTab; ?>');
    
    // // Tab click event
    // Desktop tab click
    $('.nav-tab').click(function() {
        switchTab($(this).data('tab'));
    });
    
    // Mobile dropdown menu switch
    $('#mobileNavSelect').change(function() {
        switchTab($(this).val());
    });
});

function switchTab(tab) {
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    window.history.pushState({}, '', url);
    
    $('.nav-tab').removeClass('active');
    $(`.nav-tab[data-tab="${tab}"]`).addClass('active');
    $('#mobileNavSelect').val(tab);
    
    loadContent(tab);
}

function loadDataStatus() {
    $.ajax({
        url: 'analysis_api.php',
        type: 'GET',
        data: { action: 'dataStatus' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                const isComplete = data.pending_count === 0 && data.processing_count === 0;
                const total = data.total_horses;
                const completed = data.completed_count;
                // Fix: Correctly calculate percentage
                const percent = total > 0 ? Math.round((completed / total) * 100) : 0;
                
                let html = `
                    <strong>${isComplete ? '✅ Data Integrity Check' : '⚠️ Syncing Data'}</strong>
                    <div style="margin-top: 8px; font-size: 13px;">
                `;
                
                if (!isComplete) {
                    html += `${data.pending_count} horses waiting for historical data fetch
                            (Processing: ${data.processing_count}, Completed: ${data.completed_count} / Total: ${total})`;
                } else {
                    html += `All ${data.completed_count} newly added Hong Kong horses historical data synced! (Total: ${total})`;
                }
                
                // Fix: Progress bar width and display text should be consistent
                const displayPercent = isComplete ? 100 : percent;
                const displayText = isComplete ? '100% Complete' : displayPercent + '%';
                
                html += `
                    </div>
                    <div class="status-bar">
                        <div class="status-fill" style="width: ${displayPercent}%;">
                            ${displayText}
                        </div>
                    </div>
                `;
                
                $('#dataStatus').removeClass('pending').addClass(isComplete ? 'complete' : 'pending').html(html);
            }
        },
        error: function() {
            $('#dataStatus').html('<div class="loading">❌ Unable to load data status</div>');
        }
    });
}

function loadContent(tab) {
    $('#contentArea').html('<div class="loading"><div class="loading-spinner"></div>Loading...</div>');
    
    // Tab mapping
    let action = tab;
    if (tab === 'jockey_trainer') action = 'jockey_trainer';
    
    $.ajax({
        url: 'analysis_api.php',
        type: 'GET',
        data: { action: action },
        dataType: 'html',
        success: function(html) {
            $('#contentArea').html(html);
            
            // After content loads, notify parent page to adjust height (multiple attempts to ensure success)
            if (typeof notifyParentResize !== 'undefined') {
                setTimeout(notifyParentResize, 50);
                setTimeout(notifyParentResize, 200);
                setTimeout(notifyParentResize, 500);
            } else if (window.parent !== window) {
                setTimeout(function() {
                    const height = document.body.scrollHeight;
                    window.parent.postMessage({ type: 'resizeIframe', height: height }, '*');
                }, 100);
            }
        },
        error: function() {
            $('#contentArea').html('<div class="card"><div class="loading">❌ Load failed, please refresh the page and try again</div></div>');
        }
    });
}

function loadRaceAnalysis() {
    const date = $('#raceDate').val();
    const venue = $('#raceVenue').val();
    const raceNo = $('#raceSelect').val();
    
    if (!date || !venue || !raceNo) {
        alert('Please select complete race information');
        return;
    }
    
    $('#raceAnalysisResult').html('<div class="loading"><div class="loading-spinner"></div>Analyzing...</div>');
    
    $.ajax({
        url: 'analysis_api.php',
        type: 'GET',
        data: { action: 'raceDetail', date: date, venue: venue, race_no: raceNo },
        dataType: 'html',
        success: function(html) {
            $('#raceAnalysisResult').html(html);
            
            // Notify parent page after analysis results load
            if (typeof notifyParentResize !== 'undefined') {
                setTimeout(notifyParentResize, 100);
            }
        },
        error: function() {
            $('#raceAnalysisResult').html('<div class="loading">❌ Analysis failed</div>');
        }
    });
}
</script>
</body>
</html>