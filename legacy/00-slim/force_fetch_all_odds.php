<?php
// force_fetch_all_odds.php - 强制抓取所有场次的 Pre 和 Curr 赔率

require_once 'HKJCOddsManager.php';

$dbConfig = [
    'host' => 'localhost',
    'name' => 'fengrmkw_moosay',
    'user' => 'fengrmkw_melvin',
    'pass' => 'YOUR_DB_PASSWORD'
];

echo "[" . date('Y-m-d H:i:s') . "] 开始强制抓取所有赔率...\n";

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $oddsManager = new HKJCOddsManager(
        $dbConfig['host'],
        $dbConfig['name'],
        $dbConfig['user'],
        $dbConfig['pass']
    );
    
    // 获取所有需要抓取的赛事（今天及未来7天）
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            m.date, 
            m.venue_code, 
            r.race_no,
            r.post_time
        FROM hkracing_meetings m
        JOIN hkracing_races r ON m.id = r.meeting_id
        WHERE m.date >= CURDATE()
          AND m.date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
          AND m.venue_code IN ('ST', 'HV')
        ORDER BY m.date, r.race_no
    ");
    $stmt->execute();
    $races = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($races)) {
        echo "没有找到需要抓取的赛事\n";
        exit;
    }
    
    echo "找到 " . count($races) . " 场赛事\n\n";
    
    $results = [
        'pre' => ['success' => 0, 'failed' => 0, 'skipped' => 0],
        'curr' => ['success' => 0, 'failed' => 0, 'skipped' => 0]
    ];
    
    foreach ($races as $race) {
        $date = $race['date'];
        $venue = $race['venue_code'];
        $raceNo = $race['race_no'];
        $postTime = $race['post_time'];
        
        echo "========================================\n";
        echo "赛事: {$date} {$venue} 第{$raceNo}场\n";
        echo "开赛时间: {$postTime}\n";
        
        $now = new DateTime('now', new DateTimeZone('Asia/Hong_Kong'));
        $raceTime = new DateTime($postTime, new DateTimeZone('Asia/Hong_Kong'));
        
        // 判断比赛状态
        if ($now > $raceTime) {
            echo "状态: 比赛已结束\n";
        } elseif ($now >= $raceTime && $now <= (clone $raceTime)->modify('+10 minutes')) {
            echo "状态: 比赛进行中\n";
        } else {
            echo "状态: 比赛未开始\n";
        }
        
        // 1. 强制抓取 Pre 赔率
        echo "\n[Pre 赔率] ";
        try {
            $result = $oddsManager->fetchOdds($date, $venue, $raceNo, 'Pre', true);
            if (isset($result['success']) && $result['success']) {
                echo "✓ 成功 (记录ID: {$result['odds_data_id']}, {$result['horse_count']}匹)\n";
                $results['pre']['success']++;
            } elseif (isset($result['skipped']) && $result['skipped']) {
                echo "○ 跳过: {$result['message']}\n";
                $results['pre']['skipped']++;
            } else {
                echo "✗ 失败: " . ($result['message'] ?? '未知错误') . "\n";
                $results['pre']['failed']++;
            }
        } catch (Exception $e) {
            echo "✗ 错误: " . $e->getMessage() . "\n";
            $results['pre']['failed']++;
        }
        
        // 等待0.5秒，避免请求过快
        usleep(500000);
        
        // 2. 强制抓取 Curr 赔率
        echo "[Curr 赔率] ";
        try {
            $result = $oddsManager->fetchOdds($date, $venue, $raceNo, 'Curr', true);
            if (isset($result['success']) && $result['success']) {
                echo "✓ 成功 (记录ID: {$result['odds_data_id']}, {$result['horse_count']}匹)\n";
                $results['curr']['success']++;
            } elseif (isset($result['skipped']) && $result['skipped']) {
                echo "○ 跳过: {$result['message']}\n";
                $results['curr']['skipped']++;
            } else {
                echo "✗ 失败: " . ($result['message'] ?? '未知错误') . "\n";
                $results['curr']['failed']++;
            }
        } catch (Exception $e) {
            echo "✗ 错误: " . $e->getMessage() . "\n";
            $results['curr']['failed']++;
        }
        
        echo "\n";
        usleep(500000);
    }
    
    // 输出统计结果
    echo "\n========================================\n";
    echo "抓取完成统计:\n";
    echo "Pre 赔率: 成功 {$results['pre']['success']}, 失败 {$results['pre']['failed']}, 跳过 {$results['pre']['skipped']}\n";
    echo "Curr 赔率: 成功 {$results['curr']['success']}, 失败 {$results['curr']['failed']}, 跳过 {$results['curr']['skipped']}\n";
    
    // 验证数据库中的记录数
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM hkracing_odds_data");
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\n数据库中的赔率记录总数: {$total['total']}\n";
    
    $stmt = $pdo->query("
        SELECT odds_type, COUNT(*) as count 
        FROM hkracing_odds_data 
        GROUP BY odds_type
    ");
    echo "按类型统计:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  {$row['odds_type']}: {$row['count']} 条\n";
    }
    
    echo "\n[" . date('Y-m-d H:i:s') . "] 强制抓取完成！\n";
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    error_log("强制抓取赔率错误: " . $e->getMessage());
}
?>