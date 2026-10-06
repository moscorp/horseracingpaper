<?php
// race_analysis.php - 赛事分析页面

require_once 'HKJCRacingDataManager.php';
require_once 'HKJCHorseHistoryManager.php';
require_once 'HKJCOddsManager.php';
include_once ("lib/constants.php");
include_once ("lib/func_mailer_gmail.php");

// 设置时区
date_default_timezone_set('Asia/Hong_Kong');

// 获取参数
$action = $_GET['action'] ?? 'dashboard';
$date = $_GET['date'] ?? date('Y-m-d');
$venue = $_GET['venue'] ?? 'ST';
$raceNo = $_GET['race_no'] ?? null;

// 初始化管理器
$racingManager = new HKJCRacingDataManager(
    $dbConfig['host'],
    $dbConfig['name'],
    $dbConfig['user'],
    $dbConfig['pass']
);

$horseManager = new HKJCHorseHistoryManager(
    $dbConfig['host'],
    $dbConfig['name'],
    $dbConfig['user'],
    $dbConfig['pass']
);

$oddsManager = new HKJCOddsManager(
    $dbConfig['host'],
    $dbConfig['name'],
    $dbConfig['user'],
    $dbConfig['pass']
);

// 获取数据库连接
$pdo = new PDO(
    "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
    $dbConfig['user'],
    $dbConfig['pass']
);
$pdo->exec("SET time_zone = '+08:00'");
?>
<!DOCTYPE html>
<html lang="zh-HK">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>賽馬分析系統 - HorsePaper</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            color: #e0e0e0;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* 头部 */
        .header {
            background: linear-gradient(135deg, #0f3460 0%, #1a1a2e 100%);
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
        
        /* 数据完整性警告 */
        .data-status {
            background: #1e2a3a;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-left: 4px solid #e94560;
        }
        
        .data-status.pending {
            border-left-color: #f39c12;
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
        
        /* 导航标签 */
        .nav-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .nav-tab {
            background: #1e2a3a;
            padding: 12px 25px;
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
        
        /* 卡片 */
        .card {
            background: #1e2a3a;
            border-radius: 12px;
            padding: 20px;
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
        
        /* 表格 */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .data-table th,
        .data-table td {
            padding: 10px 8px;
            text-align: left;
            border-bottom: 1px solid #2a3a4a;
        }
        
        .data-table th {
            background: #0f3460;
            color: #e94560;
            font-weight: bold;
        }
        
        .data-table tr:hover {
            background: #2a3a4a;
        }
        
        /* 统计数字 */
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
        
        /* 赔率趋势 */
        .odds-trend {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .odds-up {
            background: #27ae60;
            color: white;
        }
        
        .odds-down {
            background: #e74c3c;
            color: white;
        }
        
        .odds-hot {
            background: #e94560;
            color: white;
        }
        
        /* 热门标记 */
        .hot-fav {
            background: #e94560;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            display: inline-block;
        }
        
        /* 场次选择器 */
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
        
        /* 加载动画 */
        .loading {
            text-align: center;
            padding: 40px;
            color: #8899aa;
        }
        
        /* 响应式 */
        @media (max-width: 768px) {
            .data-table {
                font-size: 11px;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- 头部 -->
    <div class="header">
        <h1>🏇 賽馬分析系統</h1>
        <div class="subtitle">基於歷史數據 + 即時賠率 | 香港賽馬會數據</div>
    </div>

    <?php
    // ========== 检查数据完整性 ==========
    $stmt = $pdo->query("
        SELECT 
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
        FROM hkracing_pending_horses
    ");
    $queueStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $totalHorses = $queueStats['pending'] + $queueStats['processing'] + $queueStats['completed'] + $queueStats['failed'];
    $pendingPercent = $totalHorses > 0 ? round(($queueStats['pending'] + $queueStats['processing']) / $totalHorses * 100) : 0;
    $isComplete = ($queueStats['pending'] + $queueStats['processing']) == 0;
    ?>
    
    <!-- 数据状态 -->
    <div class="data-status <?php echo $isComplete ? 'complete' : 'pending'; ?>">
        <strong><?php echo $isComplete ? '✅ 数据完整性檢查' : '⚠️ 數據同步中'; ?></strong>
        <div style="margin-top: 8px; font-size: 13px;">
            <?php if (!$isComplete): ?>
                尚有 <?php echo $queueStats['pending']; ?> 匹馬匹等待抓取歷史數據
                (處理中: <?php echo $queueStats['processing']; ?>, 已完成: <?php echo $queueStats['completed']; ?>)
            <?php else: ?>
                所有 <?php echo $queueStats['completed']; ?> 匹香港馬匹歷史數據已同步完成！
            <?php endif; ?>
        </div>
        <div class="status-bar">
            <div class="status-fill" style="width: <?php echo $isComplete ? 100 : $pendingPercent; ?>%;">
                <?php echo $isComplete ? '100% 完成' : $pendingPercent . '%'; ?>
            </div>
        </div>
    </div>
    
    <!-- 导航标签 -->
    <div class="nav-tabs">
        <button class="nav-tab <?php echo $action == 'dashboard' ? 'active' : ''; ?>" onclick="showTab('dashboard')">📊 分析儀表板</button>
        <button class="nav-tab <?php echo $action == 'horses' ? 'active' : ''; ?>" onclick="showTab('horses')">🐴 馬匹勝率榜</button>
        <button class="nav-tab <?php echo $action == 'jockeys' ? 'active' : ''; ?>" onclick="showTab('jockeys')">🎠 騎師合作分析</button>
        <button class="nav-tab <?php echo $action == 'track' ? 'active' : ''; ?>" onclick="showTab('track')">🏟️ 場地性能分析</button>
        <button class="nav-tab <?php echo $action == 'odds' ? 'active' : ''; ?>" onclick="showTab('odds')">📈 賠率趨勢分析</button>
        <button class="nav-tab <?php echo $action == 'upcoming' ? 'active' : ''; ?>" onclick="showTab('upcoming')">📅 即時賽事分析</button>
    </div>
    
    <?php
    // ========== 仪表板 ==========
    if ($action == 'dashboard'): 
        // 获取统计概览
        $stmt = $pdo->query("
            SELECT 
                (SELECT COUNT(DISTINCT horse_code) FROM hkracing_horses) as total_horses,
                (SELECT COUNT(*) FROM hkracing_horse_performances) as total_performances,
                (SELECT COUNT(DISTINCT jockey_name_ch) FROM hkracing_horse_performances WHERE jockey_name_ch IS NOT NULL) as total_jockeys,
                (SELECT COUNT(DISTINCT trainer_name_ch) FROM hkracing_horses) as total_trainers
        ");
        $overview = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 整体胜率统计
        $stmt = $pdo->query("
            SELECT 
                SUM(total_wins) as total_wins,
                SUM(total_starts) as total_starts,
                ROUND(SUM(total_wins) / SUM(total_starts) * 100, 1) as overall_win_rate
            FROM hkracing_horses
            WHERE total_starts > 0
        ");
        $winStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 最近7天抓取统计
        $stmt = $pdo->query("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success,
                SUM(performances_count) as performances
            FROM hkracing_horse_fetch_log
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
            LIMIT 7
        ");
        $fetchLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 胜率最高的马匹
        $stmt = $pdo->query("
            SELECT horse_code, name_ch, total_starts, total_wins, 
                   ROUND(total_wins / total_starts * 100, 1) as win_rate
            FROM hkracing_horses
            WHERE total_starts >= 5
            ORDER BY win_rate DESC
            LIMIT 10
        ");
        $topHorses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    
    <!-- 统计卡片 -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($overview['total_horses']); ?></div>
            <div class="stat-label">香港馬匹總數</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($overview['total_performances']); ?></div>
            <div class="stat-label">歷史出賽紀錄</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($overview['total_jockeys']); ?></div>
            <div class="stat-label">騎師人數</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $winStats['overall_win_rate'] ?? 0; ?>%</div>
            <div class="stat-label">整體勝率</div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-title">🏆 勝率最高馬匹 TOP 10</div>
        <table class="data-table">
            <thead>
                <tr><th>排名</th><th>馬匹</th><th>出賽</th><th>勝場</th><th>勝率</th></tr>
            </thead>
            <tbody>
                <?php foreach ($topHorses as $i => $horse): ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td><?php echo htmlspecialchars($horse['name_ch']); ?></td>
                    <td><?php echo $horse['total_starts']; ?></td>
                    <td><?php echo $horse['total_wins']; ?></td>
                    <td><?php echo $horse['win_rate']; ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="card">
        <div class="card-title">📊 最近抓取統計</div>
        <table class="data-table">
            <thead><tr><th>日期</th><th>抓取次數</th><th>成功</th><th>獲取紀錄數</th></tr></thead>
            <tbody>
                <?php foreach ($fetchLogs as $log): ?>
                <tr>
                    <td><?php echo $log['date']; ?></td>
                    <td><?php echo $log['total']; ?></td>
                    <td><?php echo $log['success']; ?></td>
                    <td><?php echo number_format($log['performances']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php
    // ========== 马匹胜率榜 ==========
    elseif ($action == 'horses'):
        $sort = $_GET['sort'] ?? 'win_rate';
        $order = $_GET['order'] ?? 'DESC';
        
        $stmt = $pdo->query("
            SELECT 
                horse_code, name_ch, age, sex, trainer_name_ch,
                total_starts, total_wins, total_seconds, total_thirds,
                ROUND(total_wins / NULLIF(total_starts, 0) * 100, 1) as win_rate,
                ROUND((total_wins + total_seconds + total_thirds) / NULLIF(total_starts, 0) * 100, 1) as place_rate
            FROM hkracing_horses
            WHERE total_starts >= 3
            ORDER BY win_rate DESC
            LIMIT 100
        ");
        $horses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    
    <div class="card">
        <div class="card-title">🐴 馬匹勝率排行榜 (出賽≥3場)</div>
        <table class="data-table">
            <thead>
                <tr><th>馬匹</th><th>練馬師</th><th>出賽</th><th>冠</th><th>亞</th><th>季</th><th>勝率</th><th>上名率</th></tr>
            </thead>
            <tbody>
                <?php foreach ($horses as $horse): ?>
                <tr>
                    <td><?php echo htmlspecialchars($horse['name_ch']); ?></td>
                    <td><?php echo htmlspecialchars($horse['trainer_name_ch']); ?></td>
                    <td><?php echo $horse['total_starts']; ?></td>
                    <td><?php echo $horse['total_wins']; ?></td>
                    <td><?php echo $horse['total_seconds']; ?></td>
                    <td><?php echo $horse['total_thirds']; ?></td>
                    <td><span class="odds-hot"><?php echo $horse['win_rate']; ?>%</span></td>
                    <td><?php echo $horse['place_rate']; ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php
    // ========== 骑师合作分析 ==========
    elseif ($action == 'jockeys'):
        $stmt = $pdo->query("
            SELECT 
                jockey_name_ch,
                COUNT(*) as rides,
                SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN finishing_position <= 3 THEN 1 ELSE 0 END) as top3,
                ROUND(SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate
            FROM hkracing_horse_performances
            WHERE jockey_name_ch IS NOT NULL AND jockey_name_ch != ''
            GROUP BY jockey_name_ch
            HAVING rides >= 10
            ORDER BY win_rate DESC
            LIMIT 30
        ");
        $jockeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    
    <div class="card">
        <div class="card-title">🎠 騎師勝率排行榜 (出賽≥10場)</div>
        <table class="data-table">
            <thead><tr><th>騎師</th><th>出賽</th><th>頭馬</th><th>上名</th><th>勝率</th><th>上名率</th></tr></thead>
            <tbody>
                <?php foreach ($jockeys as $jockey): ?>
                <tr>
                    <td><?php echo htmlspecialchars($jockey['jockey_name_ch']); ?></td>
                    <td><?php echo $jockey['rides']; ?></td>
                    <td><?php echo $jockey['wins']; ?></td>
                    <td><?php echo $jockey['top3']; ?></td>
                    <td><span class="odds-hot"><?php echo $jockey['win_rate']; ?>%</span></td>
                    <td><?php echo round($jockey['top3'] / $jockey['rides'] * 100, 1); ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php
    // ========== 场地性能分析 ==========
    elseif ($action == 'track'):
        $stmt = $pdo->query("
            SELECT 
                venue_code,
                COUNT(*) as races,
                ROUND(AVG(finishing_position), 1) as avg_position,
                SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins
            FROM hkracing_horse_performances
            WHERE finishing_position IS NOT NULL AND finishing_position > 0
            GROUP BY venue_code
        ");
        $venues = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 途程性能
        $stmt = $pdo->query("
            SELECT 
                distance,
                COUNT(*) as runs,
                SUM(CASE WHEN finishing_position = 1 THEN 1 ELSE 0 END) as wins,
                ROUND(AVG(finishing_position), 1) as avg_pos
            FROM hkracing_horse_performances
            WHERE finishing_position IS NOT NULL AND finishing_position > 0
            GROUP BY distance
            ORDER BY distance
        ");
        $distances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    
    <div class="card">
        <div class="card-title">🏟️ 馬場性能分析</div>
        <table class="data-table">
            <thead><tr><th>馬場</th><th>賽事場數</th><th>頭馬數</th><th>平均名次</th></tr></thead>
            <tbody>
                <?php foreach ($venues as $venue): ?>
                <tr>
                    <td><?php echo $venue['venue_code'] == 'ST' ? '沙田' : ($venue['venue_code'] == 'HV' ? '跑馬地' : $venue['venue_code']); ?></td>
                    <td><?php echo $venue['races']; ?></td>
                    <td><?php echo $venue['wins']; ?></td>
                    <td><?php echo $venue['avg_position']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="card">
        <div class="card-title">📏 途程性能分析</div>
        <table class="data-table">
            <thead><tr><th>途程(米)</th><th>出賽次數</th><th>頭馬數</th><th>平均名次</th></tr></thead>
            <tbody>
                <?php foreach ($distances as $dist): ?>
                <tr>
                    <td><?php echo $dist['distance']; ?>m</td>
                    <td><?php echo $dist['runs']; ?></td>
                    <td><?php echo $dist['wins']; ?></td>
                    <td><?php echo $dist['avg_pos']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php
    // ========== 赔率趋势分析 ==========
    elseif ($action == 'odds'):
        // 热门马表现
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_hot_favourite = 1 AND finishing_position = 1 THEN 1 ELSE 0 END) as hot_wins,
                SUM(CASE WHEN is_hot_favourite = 1 AND finishing_position <= 3 THEN 1 ELSE 0 END) as hot_top3,
                SUM(CASE WHEN is_hot_favourite = 1 THEN 1 ELSE 0 END) as hot_total
            FROM hkracing_runners r
            JOIN hkracing_races rc ON r.race_id = rc.id
            WHERE r.is_hot_favourite = 1 AND r.final_position > 0
        ");
        $hotStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 赔率区间胜率
        $stmt = $pdo->query("
            SELECT 
                CASE 
                    WHEN win_odds < 2 THEN '< 2.0'
                    WHEN win_odds < 3 THEN '2.0 - 2.9'
                    WHEN win_odds < 5 THEN '3.0 - 4.9'
                    WHEN win_odds < 10 THEN '5.0 - 9.9'
                    ELSE '≥ 10.0'
                END as odds_range,
                COUNT(*) as total,
                SUM(CASE WHEN final_position = 1 THEN 1 ELSE 0 END) as wins,
                ROUND(SUM(CASE WHEN final_position = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as win_rate
            FROM hkracing_runners
            WHERE win_odds > 0 AND final_position > 0
            GROUP BY odds_range
            ORDER BY MIN(win_odds)
        ");
        $oddsRanges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $hotStats['hot_total'] ?? 0; ?></div>
            <div class="stat-label">大熱門馬次數</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo round(($hotStats['hot_wins'] ?? 0) / max(1, $hotStats['hot_total']) * 100, 1); ?>%</div>
            <div class="stat-label">大熱門勝率</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo round(($hotStats['hot_top3'] ?? 0) / max(1, $hotStats['hot_total']) * 100, 1); ?>%</div>
            <div class="stat-label">大熱門上名率</div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-title">📊 賠率區間勝率分析</div>
        <table class="data-table">
            <thead><tr><th>賠率區間</th><th>出賽次數</th><th>頭馬數</th><th>勝率</th></tr></thead>
            <tbody>
                <?php foreach ($oddsRanges as $range): ?>
                <tr>
                    <td><?php echo $range['odds_range']; ?></td>
                    <td><?php echo $range['total']; ?></td>
                    <td><?php echo $range['wins']; ?></td>
                    <td><?php echo $range['win_rate']; ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php
    // ========== 即時賽事分析（核心功能） ==========
    elseif ($action == 'upcoming'):
        // 获取今天的赛事
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("
            SELECT m.id, m.venue_code, m.date, r.race_no, r.post_time
            FROM hkracing_meetings m
            JOIN hkracing_races r ON m.id = r.meeting_id
            WHERE m.date = ?
            ORDER BY r.race_no
        ");
        $stmt->execute([$today]);
        $todayRaces = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 如果没有今天的赛事，查找未来最近的赛事
        if (empty($todayRaces)) {
            $stmt = $pdo->prepare("
                SELECT m.id, m.venue_code, m.date, r.race_no, r.post_time
                FROM hkracing_meetings m
                JOIN hkracing_races r ON m.id = r.meeting_id
                WHERE m.date > ?
                ORDER BY m.date, r.race_no
                LIMIT 12
            ");
            $stmt->execute([$today]);
            $todayRaces = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $infoMsg = "今日無賽事，顯示未來賽事";
        } else {
            $infoMsg = "今日賽事";
        }
        
        // 按场次分组
        $racesByNo = [];
        foreach ($todayRaces as $race) {
            $racesByNo[$race['race_no']] = $race;
        }
        
        // 获取赔率数据（如果有）
        $oddsAvailable = false;
        $oddsData = [];
        if (!empty($todayRaces)) {
            $firstRace = $todayRaces[0];
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM hkracing_odds_horse_details 
                WHERE race_date = ? AND venue_code = ? AND odds_type = 'Pre'
            ");
            $stmt->execute([$firstRace['date'], $firstRace['venue_code']]);
            $oddsAvailable = $stmt->fetchColumn() > 0;
            
            if ($oddsAvailable) {
                $stmt = $pdo->prepare("
                    SELECT race_no, runner_no, win_pre_odds, place_pre_odds, is_hot_favourite_pre
                    FROM hkracing_odds_horse_details 
                    WHERE race_date = ? AND venue_code = ? AND odds_type = 'Pre'
                ");
                $stmt->execute([$firstRace['date'], $firstRace['venue_code']]);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $oddsData[$row['race_no']][$row['runner_no']] = $row;
                }
            }
        }
    ?>
    
    <div class="card">
        <div class="card-title">
            📅 <?php echo $infoMsg; ?>
            <?php if (!empty($todayRaces)): ?>
                - <?php echo $todayRaces[0]['venue_code'] == 'ST' ? '沙田' : '跑馬地'; ?> 
                <?php echo $todayRaces[0]['date']; ?>
            <?php endif; ?>
        </div>
        
        <?php if (empty($todayRaces)): ?>
            <div class="loading">📭 暫時沒有即將舉行的賽事</div>
        <?php else: ?>
            <div class="race-selector">
                <label>選擇場次：</label>
                <select id="raceSelect" onchange="loadRaceAnalysis()">
                    <?php foreach ($racesByNo as $race): ?>
                    <option value="<?php echo $race['race_no']; ?>">第 <?php echo $race['race_no']; ?> 場 - <?php echo substr($race['post_time'], 11, 5); ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn" onclick="loadRaceAnalysis()">🔍 分析</button>
                <?php if ($oddsAvailable): ?>
                <span style="margin-left: 10px; padding: 5px 10px; background: #27ae60; border-radius: 8px; font-size: 12px;">✅ 賠率已發佈</span>
                <?php else: ?>
                <span style="margin-left: 10px; padding: 5px 10px; background: #f39c12; border-radius: 8px; font-size: 12px;">⏳ 賠率尚未發佈</span>
                <?php endif; ?>
            </div>
            
            <div id="raceAnalysisResult">
                <div class="loading">請選擇場次進行分析...</div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function showTab(tabName) {
            const url = new URL(window.location.href);
            url.searchParams.set('action', tabName);
            window.location.href = url.toString();
        }
        
        function loadRaceAnalysis() {
            const raceNo = document.getElementById('raceSelect').value;
            const date = '<?php echo !empty($todayRaces) ? $todayRaces[0]['date'] : ''; ?>';
            const venue = '<?php echo !empty($todayRaces) ? $todayRaces[0]['venue_code'] : ''; ?>';
            
            fetch(`/00/api_hkracing.php?action=getRaceAnalysis&date=${date}&venue=${venue}&race_no=${raceNo}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayRaceAnalysis(data.data);
                    } else {
                        document.getElementById('raceAnalysisResult').innerHTML = `<div class="loading">❌ ${data.error || '分析失敗'}</div>`;
                    }
                })
                .catch(error => {
                    document.getElementById('raceAnalysisResult').innerHTML = `<div class="loading">❌ 網絡錯誤: ${error.message}</div>`;
                });
        }
        
        function displayRaceAnalysis(data) {
            let html = `
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">${data.race_info.race_no}</div>
                        <div class="stat-label">場次</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">${data.race_info.distance}m</div>
                        <div class="stat-label">途程</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">${data.runners.length}</div>
                        <div class="stat-label">參賽馬匹</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">${data.summary.avg_win_rate || 0}%</div>
                        <div class="stat-label">平均勝率</div>
                    </div>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>檔位</th>
                            <th>馬匹</th>
                            <th>騎師</th>
                            <th>練馬師</th>
                            <th>出賽</th>
                            <th>勝率</th>
                            <th>上名率</th>
                            <th>同程勝率</th>
                            ${data.has_odds ? '<th>獨贏賠率</th><th>位置賠率</th><th>預測</th>' : ''}
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            data.runners.forEach(runner => {
                const winRate = runner.statistics?.win_rate || 0;
                const placeRate = runner.statistics?.place_rate || 0;
                const distanceRate = runner.statistics?.distance_win_rate || 0;
                let winRateClass = winRate >= 20 ? 'odds-hot' : '';
                let prediction = '';
                let predictionClass = '';
                
                if (data.has_odds && runner.odds) {
                    const odds = runner.odds.win_pre_odds;
                    if (odds && odds <= 3) {
                        prediction = '🔥 熱門';
                        predictionClass = 'odds-hot';
                    } else if (runner.statistics?.win_rate >= 20) {
                        prediction = '⭐ 狀態佳';
                        predictionClass = 'odds-up';
                    } else if (odds && odds >= 20) {
                        prediction = '💀 冷門';
                        predictionClass = 'odds-down';
                    } else {
                        prediction = '⚖️ 均衡';
                    }
                }
                
                html += `
                    <tr>
                        <td>${runner.barrier_draw_number || '-'}</td>
                        <td><strong>${runner.name_ch}</strong><br><small>${runner.horse_code}</small></td>
                        <td>${runner.jockey_name_ch}</td>
                        <td>${runner.trainer_name_ch}</td>
                        <td>${runner.statistics?.total_starts || 0}</td>
                        <td><span class="${winRateClass}">${winRate}%</span></td>
                        <td>${placeRate}%</td>
                        <td>${distanceRate}%</td>
                `;
                
                if (data.has_odds && runner.odds) {
                    const winOdds = runner.odds.win_pre_odds;
                    const placeOdds = runner.odds.place_pre_odds;
                    html += `
                        <td>${winOdds ? (winOdds.toFixed(1) + '倍') : '-'}</td>
                        <td>${placeOdds ? (placeOdds.toFixed(1) + '倍') : '-'}</td>
                        <td><span class="${predictionClass}">${prediction}</span></td>
                    `;
                }
                
                html += `</tr>`;
            });
            
            html += `
                    </tbody>
                </table>
                
                <div style="margin-top: 20px; padding: 15px; background: #0f3460; border-radius: 10px;">
                    <strong>📈 分析總結</strong><br>
                    ${data.summary.message || '正在分析中...'}
                </div>
            `;
            
            document.getElementById('raceAnalysisResult').innerHTML = html;
        }
        
        // 如果有赛事，自动加载第一场
        <?php if (!empty($todayRaces)): ?>
        window.onload = function() {
            loadRaceAnalysis();
        };
        <?php endif; ?>
    </script>
    
    <?php endif; ?>
</div>
</body>
</html>