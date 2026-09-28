<?php
// sync_cron.php - 修复版 renamed to HKJCHorseHistoryBatchManager_sync.php

require_once 'HKJCRacingDataManager.php';
require_once 'HKJCHorseHistoryBatchManager.php';
include_once ("lib/func_mailer_gmail.php");
include_once ("lib/constants.php");

echo "[" . date('Y-m-d H:i:s') . "] 开始同步赛马数据...\n";

try {
    $manager = new HKJCRacingDataManager(
        $dbConfig['host'],
        $dbConfig['name'],
        $dbConfig['user'],
        $dbConfig['pass']
    );
    
    $batchManager = new HKJCHorseHistoryBatchManager(
        $dbConfig['host'],
        $dbConfig['name'],
        $dbConfig['user'],
        $dbConfig['pass']
    );
    
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $hkNow = new DateTime('now', new DateTimeZone('Asia/Hong_Kong'));
    $today = $hkNow->format('Y-m-d');
    
    // ========== 第一部分：同步今天及未来的所有赛事 ==========
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            date,
            venue_code as venueCode,
            status,
            id
        FROM hkracing_meetings 
        WHERE date >= ?
        ORDER BY date
    ");
    $stmt->execute([$today]);
    $activeMeetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($activeMeetings)) {
        echo "没有找到即将到来的赛马日。\n";
    } else {
        echo "找到 " . count($activeMeetings) . " 个即将到来的赛马日\n";
        
        foreach ($activeMeetings as $meeting) {
            $date = $meeting['date'];
            $venue = $meeting['venueCode'];
            $isHongKong = ($venue == 'ST' || $venue == 'HV');
            
            echo "同步: {$date} - {$venue} " . ($isHongKong ? '(香港)' : '(海外)') . " ... ";
            
            $data = $manager->getRacingData($date, $venue, null, true);
            
            if ($data && !isset($data['errors'])) {
                echo "成功\n";
                
                if ($isHongKong && !empty($data['data']['raceMeetings'][0]['races'])) {
                    $collected = 0;
                    foreach ($data['data']['raceMeetings'][0]['races'] as $race) {
                        if (!empty($race['runners'])) {
                            foreach ($race['runners'] as $runner) {
                                $horseCode = $runner['horse']['code'] ?? '';
                                if ($horseCode && preg_match('/^[A-Z]\d{3}$/', $horseCode)) {
                                    $batchManager->addToQueue($horseCode, $runner['name_ch'] ?? '', 3);
                                    $collected++;
                                }
                            }
                        }
                    }
                    if ($collected > 0) {
                        echo "    收集到 {$collected} 匹香港马匹\n";
                    }
                }
            } else {
                $errorMsg = isset($data['errors'][0]['message']) ? $data['errors'][0]['message'] : '未知错误';
                echo "失败: {$errorMsg}\n";
            }
            sleep(1);
        }
    }
    
    // ========== 第二部分：检查已结束但未更新结果的香港赛事 ==========
    echo "\n检查需要更新结果的香港赛事...\n";
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            m.date, 
            m.venue_code,
            m.id as meeting_id,
            m.updated_at as last_updated
        FROM hkracing_meetings m
        WHERE m.date < ?
          AND m.status != 'RESULT_UPDATED'
          AND m.status != 'FAILED'
          AND m.venue_code IN ('ST', 'HV')
        ORDER BY m.date DESC
        LIMIT 5
    ");
    $stmt->execute([$today]);
    $pastMeetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pastMeetings)) {
        echo "没有需要更新结果的香港赛事\n";
    } else {
        echo "发现 " . count($pastMeetings) . " 个需要更新结果的香港赛马日\n";
        foreach ($pastMeetings as $meeting) {
            $threeDaysAgo = date('Y-m-d', strtotime('-3 days'));
            if ($meeting['date'] < $threeDaysAgo) {
                echo "  跳过: {$meeting['date']} - {$meeting['venue_code']} (超过3天，标记为失败)\n";
                $updateStmt = $pdo->prepare("
                    UPDATE hkracing_meetings 
                    SET status = 'FAILED', updated_at = NOW()
                    WHERE id = ?
                ");
                $updateStmt->execute([$meeting['meeting_id']]);
                continue;
            }
            
            echo "  更新结果: {$meeting['date']} - {$meeting['venue_code']} ... ";
            
            $data = $manager->getRacingData($meeting['date'], $meeting['venue_code'], null, true);
            
            if ($data && !isset($data['errors'])) {
                $updateStmt = $pdo->prepare("
                    UPDATE hkracing_meetings 
                    SET status = 'RESULT_UPDATED', updated_at = NOW()
                    WHERE id = ?
                ");
                $updateStmt->execute([$meeting['meeting_id']]);
                echo "成功\n";
                
                if (!empty($data['data']['raceMeetings'][0]['races'])) {
                    foreach ($data['data']['raceMeetings'][0]['races'] as $race) {
                        if (!empty($race['runners'])) {
                            foreach ($race['runners'] as $runner) {
                                $horseCode = $runner['horse']['code'] ?? '';
                                if ($horseCode && preg_match('/^[A-Z]\d{3}$/', $horseCode)) {
                                    $batchManager->addToQueue($horseCode, $runner['name_ch'] ?? '', 10, '赛后更新');
                                }
                            }
                        }
                    }
                }
            } else {
                echo "失败\n";
            }
            sleep(1);
        }
    }
    
    // ========== 第三部分：检查已有马匹是否有新比赛 ==========
    echo "\n检查已有香港马匹的新比赛...\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            ru.horse_code,
            MAX(m.date) as latest_race_date,
            h.last_sync_date,
            h.name_ch
        FROM hkracing_runners ru
        JOIN hkracing_races r ON ru.race_id = r.id
        JOIN hkracing_meetings m ON r.meeting_id = m.id
        LEFT JOIN hkracing_horses h ON ru.horse_code = h.horse_code
        WHERE ru.horse_code REGEXP '^[A-Z][0-9]{3}$'
        GROUP BY ru.horse_code
        HAVING (latest_race_date > last_sync_date OR last_sync_date IS NULL)
           AND latest_race_date IS NOT NULL
        ORDER BY latest_race_date DESC
        LIMIT 100
    ");
    $stmt->execute();
    $horsesNeedUpdate = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($horsesNeedUpdate)) {
        echo "没有发现需要更新的马匹\n";
    } else {
        echo "发现 " . count($horsesNeedUpdate) . " 匹需要更新（有新比赛）\n";
        foreach ($horsesNeedUpdate as $horse) {
            $batchManager->addToQueue($horse['horse_code'], $horse['name_ch'] ?? '', 5, "新比赛");
        }
    }
    
    // ========== 第四部分：处理队列中的马匹（实际抓取历史数据） ==========
    echo "\n开始处理马匹历史队列...\n";
    
    // 先重置卡住的任务
    $resetCount = $batchManager->resetStuckTasks();
    if ($resetCount > 0) {
        echo "重置了 {$resetCount} 个卡住的任务\n";
    }
    
    // 清空 pending 记录的 last_attempt，确保它们能被处理
    $pdo->exec("UPDATE hkracing_pending_horses SET last_attempt = NULL WHERE status = 'pending' AND last_attempt IS NOT NULL");
    echo "已重置 pending 记录的状态\n";
    
    // 处理批次（每批最多5匹）
    $totalProcessed = 0;
    $totalSuccess = 0;
    $totalFailed = 0;
    $totalSkipped = 0;
    $batchCount = 0;
    $maxBatches = 10; // 最多处理10批（50匹），避免执行时间过长
    
    while ($batchCount < $maxBatches) {
        $processResult = $batchManager->processBatch();
        $batchCount++;
        
        $totalSuccess += $processResult['results']['success'];
        $totalFailed += $processResult['results']['failed'];
        $totalSkipped += $processResult['results']['skipped'];
        $totalProcessed += ($processResult['results']['success'] + $processResult['results']['failed'] + $processResult['results']['skipped']);
        
        echo "  批次 {$batchCount}: 成功 {$processResult['results']['success']}, 失败 {$processResult['results']['failed']}, 跳过 {$processResult['results']['skipped']} (耗时 {$processResult['duration_ms']}ms)\n";
        
        // 如果没有处理任何马匹，退出循环
        if ($processResult['results']['success'] == 0 && $processResult['results']['failed'] == 0 && $processResult['results']['skipped'] == 0) {
            echo "  队列已空，停止处理\n";
            break;
        }
        
        // 批次间稍作延迟，避免请求过于频繁
        sleep(1);
    }
    
    echo "\n历史数据抓取完成: 共处理 {$totalProcessed} 匹, 成功 {$totalSuccess}, 失败 {$totalFailed}, 跳过 {$totalSkipped}\n";
    
    // ========== 第五部分：显示统计 ==========
    $stats = $batchManager->getQueueStats();
    echo "\n队列统计:\n";
    echo "  待处理: {$stats['pending_count']} 匹\n";
    echo "  处理中: {$stats['processing_count']} 匹\n";
    echo "  已完成: {$stats['completed_count']} 匹\n";
    echo "  失败: {$stats['failed_count']} 匹\n";
    echo "  香港马总数: {$stats['total_hk_horses']} 匹\n";
    echo "  已同步: {$stats['synced_horses']} 匹\n";
    
    echo "\n[" . date('Y-m-d H:i:s') . "] 同步完成！\n";
    
    // 发送邮件通知（仅当有处理结果时）
    if ($totalSuccess) {
        $emailtitle = "HRP";
        $ipAddress = substr($_SERVER['SERVER_ADDR'], -3);
        $Subject = "[horsepaper] " . $emailtitle . " [HKJCRacingDataManager] (成功:{$totalSuccess}/失败:{$totalFailed}) " . $ipAddress;
        $Body = "\n处理结果:\n";
        $Body .= "  本次处理: {$totalProcessed} 匹\n";
        $Body .= "  成功: {$totalSuccess} 匹\n";
        $Body .= "  失败: {$totalFailed} 匹\n";
        $Body .= "  跳过: {$totalSkipped} 匹\n";
        $Body .= "\n队列统计:\n";
        $Body .= "  待处理: {$stats['pending_count']} 匹\n";
        $Body .= "  处理中: {$stats['processing_count']} 匹\n";
        $Body .= "  已完成: {$stats['completed_count']} 匹\n";
        $Body .= "  失败: {$stats['failed_count']} 匹\n";
        $Body .= "  香港马总数: {$stats['total_hk_horses']} 匹\n";
        $Body .= "  已同步: {$stats['synced_horses']} 匹\n";
        $AltBody = '';
        $recipients = '';
        $log = '';
        $recipients_BCC = array(
            'hkhorsepaper@gmail.com' => 's',
            'support@fengins.com' => 'fi',
        );
        
        $send = mailto($Subject, $Body, $AltBody, $recipients, $recipients_BCC, $log);
    }
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    error_log("同步错误: " . $e->getMessage());
}
?>