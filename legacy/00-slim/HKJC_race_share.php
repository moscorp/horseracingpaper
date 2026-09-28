<?php
// HKJC_race_share.php 开头添加

error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "lib/constants.php";

$date = $_GET['date'] ?? '';
$venue = $_GET['venue'] ?? '';
$raceNo = $_GET['race_no'] ?? '';

// 获取赛事数据
try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("数据库连接失败");
}

// 获取赛事信息
$stmt = $pdo->prepare("
    SELECT 
        r.race_name_ch,
        r.distance,
        m.date,
        m.venue_code,
        COUNT(ru.id) as horse_count
    FROM hkracing_races r
    JOIN hkracing_meetings m ON r.meeting_id = m.id
    JOIN hkracing_runners ru ON ru.race_id = r.id
    WHERE m.date = ? AND m.venue_code = ? AND r.race_no = ?
    GROUP BY r.id
");
$stmt->execute([$date, $venue, $raceNo]);
$race = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$race) {
    die("赛事不存在");
}

$venueName = $race['venue_code'] == 'ST' ? '沙田' : '跑馬地';
$pageUrl = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// 获取马匹数据
$stmt = $pdo->prepare("
    SELECT 
        ru.runner_no,
        ru.name_ch as horse_name,
        ru.jockey_name_ch as jockey,
        ru.trainer_name_ch as trainer,
        ru.barrier_draw_number as draw,
        ohd.win_odds,
        ohd.win_odds_drop,
        ohd.is_hot_favourite,
        h.total_starts,
        ROUND(IFNULL(h.total_wins, 0) / NULLIF(h.total_starts, 0) * 100, 1) as win_rate
    FROM hkracing_runners ru
    LEFT JOIN hkracing_horses h ON h.horse_code = ru.horse_code
    LEFT JOIN (
        SELECT runner_no, win_odds, win_odds_drop, is_hot_favourite
        FROM hkracing_odds_horse_details 
        WHERE race_date = ? AND venue_code = ? AND race_no = ? AND odds_type = 'Curr'
        ORDER BY captured_at DESC
        LIMIT 1
    ) ohd ON ohd.runner_no = ru.runner_no
    WHERE ru.race_id = (SELECT id FROM hkracing_races WHERE meeting_id = (SELECT id FROM hkracing_meetings WHERE date = ? AND venue_code = ?) AND race_no = ?)
    ORDER BY CAST(ru.runner_no AS UNSIGNED)
");
$stmt->execute([$date, $venue, $raceNo, $date, $venue, $raceNo]);
$runners = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-HK">
<head>
    <meta charset="UTF-8">
    <meta property="og:title" content="🏇 <?php echo $venueName; ?> 第<?php echo $raceNo; ?>場 - <?php echo htmlspecialchars($race['race_name_ch']); ?>" />
    <meta property="og:description" content="日期: <?php echo $date; ?> | 途程: <?php echo $race['distance']; ?>米 | 參賽馬匹: <?php echo $race['horse_count']; ?>匹" />
    <meta property="og:image" content="https://<?php echo $_SERVER['HTTP_HOST']; ?>/00/image/racing_preview.jpg" />
    <meta property="og:url" content="<?php echo $pageUrl; ?>" />
    <meta property="og:type" content="website" />
    <meta name="twitter:card" content="summary_large_image" />
    <title><?php echo $venueName; ?> 第<?php echo $raceNo; ?>場 - 賽馬分析</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        .header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            padding: 24px;
            text-align: center;
        }
        .header h1 { font-size: 1.5rem; margin-bottom: 8px; }
        .header .info { font-size: 0.85rem; opacity: 0.8; margin-top: 8px; }
        .race-class {
            display: inline-block;
            background: #3b82f6;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            margin-top: 8px;
        }
        .content { padding: 20px; }
        .runner-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 12px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        .runner-card:hover { background: #f1f5f9; }
        .runner-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            flex-wrap: wrap;
            gap: 8px;
        }
        .runner-number {
            font-size: 1.2rem;
            font-weight: bold;
            background: #1e293b;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
        }
        .runner-name {
            font-size: 1.1rem;
            font-weight: bold;
            color: #0f172a;
        }
        .hot-icon { color: #e94560; margin-left: 4px; }
        .draw-badge {
            background: #fef3c7;
            color: #92400e;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
        }
        .runner-details {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 8px;
        }
        .odds-good { color: #dc2626; font-weight: bold; }
        .odds-drop { color: #10b981; }
        .footer {
            background: #f1f5f9;
            padding: 12px;
            text-align: center;
            font-size: 0.7rem;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
        @media (max-width: 600px) {
            .runner-header { flex-direction: column; align-items: flex-start; }
            .container { margin: 0; border-radius: 0; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏇 <?php echo $venueName; ?> 賽馬日</h1>
            <div class="info">📅 <?php echo $date; ?> | 📏 <?php echo $race['distance']; ?>米 | 🐎 <?php echo $race['horse_count']; ?>匹</div>
            <div class="race-class">第<?php echo $raceNo; ?>場 - <?php echo htmlspecialchars($race['race_name_ch']); ?></div>
        </div>
        
        <div class="content">
            <?php foreach ($runners as $runner): ?>
            <div class="runner-card">
                <div class="runner-header">
                    <div>
                        <span class="runner-number"><?php echo $runner['runner_no']; ?>號</span>
                        <span class="runner-name"><?php echo htmlspecialchars($runner['horse_name'] ?? '-'); ?>
                            <?php if ($runner['is_hot_favourite'] ?? false): ?><span class="hot-icon">❤️</span><?php endif; ?>
                        </span>
                    </div>
                    <div>
                        <span class="draw-badge">檔位: <?php echo $runner['draw'] ?? '-'; ?></span>
                    </div>
                </div>
                <div class="runner-details">
                    <span>👨‍🏫 騎師: <?php echo htmlspecialchars($runner['jockey'] ?? '-'); ?></span>
                    <span>👨‍🔧 練馬師: <?php echo htmlspecialchars($runner['trainer'] ?? '-'); ?></span>
                    <?php if ($runner['win_odds']): ?>
                    <span class="<?php echo $runner['win_odds'] <= 3 ? 'odds-good' : ''; ?>">
                        💰 賠率: <?php echo number_format($runner['win_odds'], 1); ?>倍
                    </span>
                    <?php endif; ?>
                    <?php if ($runner['win_odds_drop']): ?>
                    <span class="odds-drop">📉 跌幅: <?php echo number_format($runner['win_odds_drop'], 1); ?></span>
                    <?php endif; ?>
                    <span>🏆 出賽: <?php echo $runner['total_starts'] ?? 0; ?>次</span>
                    <span>📊 勝率: <?php echo $runner['win_rate'] ?? 0; ?>%</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="footer">
            ⚠️ 數據僅供參考 | 來源: 香港賽馬會 | 請理性投注
        </div>
    </div>
</body>
</html>