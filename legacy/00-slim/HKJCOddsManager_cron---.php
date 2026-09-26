<?php
// HKJCOddsManager_cron.php - 修复时间判断

require_once 'HKJCOddsManager.php';
include_once ("lib/func_mailer_gmail.php");
include_once ("lib/constants.php");

echo "[" . date('Y-m-d H:i:s') . "] 开始批量更新赔率...\n";

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("SET time_zone = '+08:00'");
    
    $oddsManager = new HKJCOddsManager(
        $dbConfig['host'],
        $dbConfig['name'],
        $dbConfig['user'],
        $dbConfig['pass']
    );
    
    // 获取今天及未来的香港赛事
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            m.date, 
            m.venue_code, 
            r.race_no,
            r.post_time
        FROM hkracing_meetings m
        JOIN hkracing_races r ON m.id = r.meeting_id
        WHERE m.date >= CURDATE()
          /*AND m.date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
          AND m.venue_code IN ('ST', 'HV')*/
        ORDER BY m.date, r.race_no
    ");
    $stmt->execute();
    $races = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($races)) {
        echo "没有找到需要更新赔率的赛事\n";
        exit;
    }
    
    // 获取已有记录
    $dates = array_values(array_unique(array_column($races, 'date')));
    $placeholders = implode(',', array_fill(0, count($dates), '?'));
    
    $stmt = $pdo->prepare("
        SELECT race_date, venue_code, race_no, odds_type 
        FROM hkracing_odds_data 
        WHERE race_date IN ({$placeholders})
        GROUP BY race_date, venue_code, race_no, odds_type
    ");
    $stmt->execute($dates);
    $existingRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $existingMap = [];
    foreach ($existingRecords as $record) {
        $key = $record['race_date'] . '|' . $record['venue_code'] . '|' . $record['race_no'] . '|' . $record['odds_type'];
        $existingMap[$key] = true;
    }
    
    // 使用香港时间
    $hkNow = new DateTime('now', new DateTimeZone('Asia/Hong_Kong'));
    $currentHour = (int)$hkNow->format('H');
    $isAfterNine = ($currentHour >= 9);
    
    echo "当前香港时间: " . $hkNow->format('Y-m-d H:i:s') . "\n";
    echo "当前小时: {$currentHour}, 是否 >= 9: " . ($isAfterNine ? '是' : '否') . "\n\n";
    
    $stats = ['pre' => 0, 'curr' => 0, 'pre_skip' => 0, 'curr_skip' => 0];
    
    foreach ($races as $race) {
        $date = $race['date'];
        $venue = $race['venue_code'];
        $raceNo = $race['race_no'];
        $postTime = $race['post_time'];
        
        $raceTime = new DateTime($postTime, new DateTimeZone('Asia/Hong_Kong'));
        $raceEndTime = clone $raceTime;
        $raceEndTime->modify('+30 minutes');
        
        echo "赛事: {$date} {$venue} 第{$raceNo}场 (开赛: {$raceTime->format('H:i:s')})\n";
        
        // ========== Pre 赔率：只在比赛开始前抓取，且只抓一次 ==========
        $preKey = $date . '|' . $venue . '|' . $raceNo . '|Pre';
        $hasPre = isset($existingMap[$preKey]);
        
        // 计算比赛结束时间（开赛时间 + 10分钟）
        $raceEndTime = clone $raceTime;
        $raceEndTime->modify('+30 minutes');
        
        // 判断是否需要抓取 Pre 赔率
        // 1. 没有 Pre 数据
        // 2. 并且（比赛未开始 或者 比赛结束不超过24小时）
        $needPre = !$hasPre && ($hkNow < $raceTime || ($hkNow > $raceEndTime && $hkNow < (clone $raceEndTime)->modify('+24 hours')));
        
        if ($needPre) {
            echo "  Pre: ";
            $result = $oddsManager->fetchOdds($date, $venue, $raceNo, 'Pre', true);
            if (isset($result['success']) && $result['success']) {
                echo "✓ 成功 (ID: {$result['odds_data_id']})\n";
                $stats['pre']++;
                $existingMap[$preKey] = true;
            } else {
                echo "✗ 失败: " . ($result['message'] ?? '未知') . "\n";
            }
        } else {
            if ($hasPre) {
                echo "  Pre: ○ 已存在，跳过\n";
            } else {
                echo "  Pre: ○ 比赛已结束超过24小时，跳过\n";
            }
            $stats['pre_skip']++;
        }
        
        usleep(200000);
        
        // ========== Curr 赔率：只在比赛当天且上午9点后抓取 ==========
        // 修改 Curr 赔率的判断逻辑
        $isToday = ($date == date('Y-m-d'));
        
        if ($isToday && $isAfterNine) {
            // 比赛未结束 或者 比赛已结束但还没有 Curr 数据时，都尝试抓取一次
            $hasCurrData = false;
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM hkracing_odds_data 
                WHERE race_date = ? AND venue_code = ? AND race_no = ? AND odds_type = 'Curr'
            ");
            $stmt->execute([$date, $venue, $raceNo]);
            $hasCurrData = $stmt->fetchColumn() > 0;
            
            // 如果比赛未结束，或者已结束但没有 Curr 数据，都抓取
            if ($hkNow < $raceEndTime || !$hasCurrData) {
                echo "  Curr: ";
                $result = $oddsManager->fetchOdds($date, $venue, $raceNo, 'Curr', true);
                if (isset($result['success']) && $result['success']) {
                    echo "✓ 成功 (ID: {$result['odds_data_id']})\n";
                    $stats['curr']++;
                } else {
                    echo "✗ 失败: " . ($result['message'] ?? '未知') . "\n";
                }
            } else {
                echo "  Curr: ○ 比赛已结束，停止更新\n";
                $stats['curr_skip']++;
            }
        }
        
        echo "\n";
        usleep(200000);
    }
    
    echo "\n统计:\n";
    echo "  Pre: 成功 {$stats['pre']}, 跳过 {$stats['pre_skip']}\n";
    echo "  Curr: 成功 {$stats['curr']}, 跳过 {$stats['curr_skip']}\n";
    
    echo "[" . date('Y-m-d H:i:s') . "] 赔率更新完成\n";
    
    if($stats['pre'] || $stats['curr'] ){
        $emailtitle = "HRP";
    	$ipAddress = substr($_SERVER['SERVER_ADDR'],-3);
    	$Subject = "[horsepaper] ".$emailtitle." [odds update] (".$stats['pre'] ."/". $stats['curr'] .")". $ipAddress;	//$date;
    	$Body    =  "\n统计:\n";
        $Body    .=  "  Pre: 成功 {$stats['pre']}, 跳过 {$stats['pre_skip']}\n";
        $Body    .=  "  Curr: 成功 {$stats['curr']}, 跳过 {$stats['curr_skip']}\n";
    	$AltBody = '';
    	$recipients = '';
    	$log = '';
    	$recipients_BCC = array(
    		'hkhorsepaper@gmail.com' => 's',
    		'support@fengins.com' => 'fi',
    		// ..
    	 );
    	 
    	 $send = mailto($Subject,$Body,$AltBody,$recipients,$recipients_BCC,$log);
    
    }
    // ... 在现有的赔率更新代码之后 ...

    echo "\n" . str_repeat('-', 50) . "\n";
    echo "更新最终名次...\n";
    
    // 获取今天的赛事结果（比赛结束后）
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            m.date, 
            m.venue_code, 
            r.race_no,
            r.post_time
        FROM hkracing_meetings m
        JOIN hkracing_races r ON m.id = r.meeting_id
        WHERE m.date = CURDATE()
          AND m.venue_code IN ('ST', 'HV')
          AND r.post_time < DATE_SUB(NOW(), INTERVAL 2 HOUR)
    ");
    $stmt->execute();
    $finishedRaces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $finalUpdated = 0;
    foreach ($finishedRaces as $race) {
        echo "更新最终名次: {$race['date']} {$race['venue_code']} 第{$race['race_no']}场... ";
        $updated = $oddsManager->updateFinalPositions($race['date'], $race['venue_code'], $race['race_no']);
        echo "更新了 {$updated} 条记录\n";
        $finalUpdated += $updated;
        usleep(500000);
    }
    
    echo "最终名次更新完成，共更新 {$finalUpdated} 条记录\n";
    
    // ... 在现有代码之后 ...

    echo "\n" . str_repeat('-', 50) . "\n";
    echo "更新最终名次到 v2_score 表...\n";
    
    // 获取今天已结束的赛事（比赛时间超过2小时）
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            m.date, 
            m.venue_code, 
            r.race_no,
            r.post_time
        FROM hkracing_meetings m
        JOIN hkracing_races r ON m.id = r.meeting_id
        WHERE m.date = CURDATE()
          AND m.venue_code IN ('ST', 'HV')
          AND r.post_time < DATE_SUB(NOW(), INTERVAL 2 HOUR)
    ");
    $stmt->execute();
    $finishedRaces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $finalUpdated = 0;
    foreach ($finishedRaces as $race) {
        echo "更新最终名次: {$race['date']} {$race['venue_code']} 第{$race['race_no']}场... ";
        $updated = $oddsManager->updateFinalPositions($race['date'], $race['venue_code'], $race['race_no']);
        $finalUpdated += $updated;
        echo "完成\n";
        usleep(500000);
    }
    
    echo "最终名次更新完成，共更新 {$finalUpdated} 条记录\n";

    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    error_log("赔率更新错误: " . $e->getMessage());
}
?>