<?php
// HKJCRacingDataManager_sync-v2.php - 分离队列版本（保留邮件功能）

require_once 'HKJCRacingDataManager.php';
require_once 'HKJCHorseHistoryBatchManagerV2.php';
include_once ("lib/func_mailer_gmail.php");
include_once ("lib/constants.php");

$hkNow = new DateTime('now', new DateTimeZone('Asia/Hong_Kong'));
$hkcurrent = $hkNow->format('Y-m-d H:i:s');
/**
 * 同步未来赛程到 hkracing_meetings 表
 */
function syncRaceMeetings($pdo, $manager) {
    echo "\n同步未来赛程...\n";
    
    // // 获取未来30天的赛程
    // $dates = [];
    // for ($i = 0; $i <= 30; $i++) {
    //     $dates[] = date('Y-m-d', strtotime("+{$i} days"));
    // }
    
    // $syncedCount = 0;
    // foreach ($dates as $date) {
    //     // 尝试获取香港赛事
    //     foreach (['ST', 'HV'] as $venue) {
    //         // 检查是否已存在
    //         $stmt = $pdo->prepare("
    //             SELECT COUNT(*) FROM hkracing_meetings 
    //             WHERE date = ? AND venue_code = ?
    //         ");
    //         $stmt->execute([$date, $venue]);
    //         if ($stmt->fetchColumn() > 0) {
    //             continue;  // 已存在
    //         }
            
    //         echo "检查: {$date} - {$venue} ... ";
    //         $data = $manager->getRacingData($date, $venue, null, true);
            
    //         if ($data && !isset($data['errors']) && !empty($data['data']['raceMeetings'])) {
    //             $meetingData = $data['data']['raceMeetings'][0];
    //             $manager->storeToDatabase($meetingData);
    //             echo "已添加\n";
    //             $syncedCount++;
    //         } else {
    //             echo "无赛事\n";
    //         }
            
    //         sleep(1);
    //     }
    // }
    
    // echo "完成，新增 {$syncedCount} 个赛马日\n";
    echo "\n[3/5] 同步今天及未来的赛事(if no meeting data)...\n";

    $data = $manager->getRacingData(null, null, null, true);
    
    if (!$data || isset($data['errors'])) {
        $errorMsg = isset($data['errors'][0]['message']) ? $data['errors'][0]['message'] : '未知错误';
        echo "获取赛程失败: {$errorMsg}\n";
    } else {
        $raceMeetings = $data['data']['raceMeetings'] ?? [];
        echo "找到 " . count($raceMeetings) . " 个即将到来的赛马日\n";
        
        foreach ($raceMeetings as $meetingData) {
            $date = $meetingData['date'];
            $venue = $meetingData['venueCode'];
            $isHongKong = ($venue == 'ST' || $venue == 'HV');
            
            echo "处理: {$date} - {$venue} ... ";
            
            // storeToDatabase 会处理：
            // 1. 插入/更新 hkracing_meetings
            // 2. 插入/更新 hkracing_races  
            // 3. 插入/更新 hkracing_runners
            $manager->storeToDatabase($meetingData);
            
            echo "完成\n";
            
            // 收集马匹到历史队列
            if ($isHongKong && !empty($meetingData['races'])) {
                $collected = 0;
                foreach ($meetingData['races'] as $race) {
                    if (!empty($race['runners'])) {
                        foreach ($race['runners'] as $runner) {
                            $horseCode = $runner['horse']['code'] ?? '';
                            if ($horseCode && preg_match('/^[A-Z]\d{3}$/', $horseCode)) {
                                $batchManager->addToHistoryQueue($horseCode, $runner['name_ch'] ?? '', 3);
                                $collected++;
                            }
                        }
                    }
                }
                echo "  收集了 {$collected} 匹香港马匹\n";
            }
            
            sleep(1);
        }
    }
}

// echo "[" . date('Y-m-d H:i:s') . "] 开始同步赛马数据 (V2 - 分离队列)...\n";
echo "[" . $hkcurrent . "] 开始同步赛马数据 (V2 - 分离队列)...\n";

try {
    $manager = new HKJCRacingDataManager(
        $dbConfig['host'],
        $dbConfig['name'],
        $dbConfig['user'],
        $dbConfig['pass']
    );
    
    $batchManager = new HKJCHorseHistoryBatchManagerV2(
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
    
    // 统计变量（用于邮件）
    $totalNewHorses = 0;
    $totalIncremental = 0;
    $historySuccess = 0;
    $historyFailed = 0;
    $incrementalSuccess = 0;
    $incrementalFailed = 0;
    
    // ========== 第一部分：收集新马匹到历史队列 ==========
    echo "\n[1/5] 收集新马匹到历史队列...\n";
    
    $stmt = $pdo->query("
        SELECT DISTINCT 
            ru.horse_code,
            ru.name_ch as horse_name_ch
        FROM hkracing_runners ru
        LEFT JOIN hkracing_horses h ON ru.horse_code = h.horse_code
        WHERE ru.horse_code REGEXP '^[A-Z][0-9]{3}$'
          AND h.horse_code IS NULL
        LIMIT 100
    ");
    
    $newHorses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $historyAdded = 0;
    foreach ($newHorses as $horse) {
        if ($batchManager->addToHistoryQueue($horse['horse_code'], $horse['horse_name_ch'], 10)) {
            $historyAdded++;
        }
    }
    echo "  添加 {$historyAdded} 匹新马到历史队列\n";
    $totalNewHorses = $historyAdded;
    
    // ========== 第二部分：检查新比赛，添加到增量队列 ==========
    echo "\n[2/5] 检查新比赛，添加到增量队列...\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            ru.horse_code,
            ru.name_ch as horse_name_ch,
            MAX(m.date) as latest_race_date,
            h.last_sync_date
        FROM hkracing_runners ru
        JOIN hkracing_races r ON ru.race_id = r.id
        JOIN hkracing_meetings m ON r.meeting_id = m.id
        LEFT JOIN hkracing_horses h ON ru.horse_code = h.horse_code
        WHERE ru.horse_code REGEXP '^[A-Z][0-9]{3}$'
          AND h.last_sync_date IS NOT NULL
        GROUP BY ru.horse_code
        HAVING latest_race_date > last_sync_date
        ORDER BY latest_race_date DESC
        LIMIT 50
    ");
    $stmt->execute();
    $horsesNeedUpdate = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $incrementalAdded = 0;
    foreach ($horsesNeedUpdate as $horse) {
        if ($batchManager->addToIncrementalQueue($horse['horse_code'], $horse['horse_name_ch'], 'new_race')) {
            $incrementalAdded++;
        }
    }
    echo "  添加 {$incrementalAdded} 匹需要增量的马匹\n";
    $totalIncremental = $incrementalAdded;
    // ========== 第2.5部分：通过 GraphQL 获取未来赛马日 ==========
    // echo "\n[2.5/5] 通过 GraphQL 获取未来赛马日...\n";
    
    // try {
    //     $graphQLDays = $manager->getUpcomingRaceDaysGraphQL(7);
        
    //     if (!empty($graphQLDays)) {
    //         echo "  ✓ GraphQL 成功获取 " . count($graphQLDays) . " 个赛马日\n";
            
    //         $addedCount = 0;
    //         foreach ($graphQLDays as $raceDay) {
    //             $stmt = $pdo->prepare("
    //                 INSERT INTO hkracing_meetings (date, venue_code, status, created_at, updated_at) 
    //                 VALUES (?, ?, 'PENDING', NOW(), NOW())
    //                 ON DUPLICATE KEY UPDATE 
    //                     status = IF(status = 'RESULT_UPDATED', 'RESULT_UPDATED', 'PENDING'),
    //                     updated_at = NOW()
    //             ");
    //             $stmt->execute([$raceDay['date'], $raceDay['venue']]);
                
    //             if ($stmt->rowCount() > 0) {
    //                 $addedCount++;
    //             }
    //         }
    //         echo "  新增 {$addedCount} 个赛马日到数据库\n";
    //     } else {
    //         echo "  ⚠️ GraphQL 返回空数据\n";
    //     }
    // } catch (Exception $e) {
    //     echo "  ✗ GraphQL 失败: " . $e->getMessage() . "\n";
    // }
    // ========== 第2.5部分：通过 GraphQL 获取未来赛马日 ==========
    
    // echo "\n[2.5/5] 通过 GraphQL 获取未来赛马日...\n";
    
    // $raceDays = [];
    
    // // 方法1: 从 activeRaceMeetings 获取
    // try {
    //     echo "  尝试获取活跃赛马日 (activeRaceMeetings)... ";
    //     $activeMeetings = $manager->getActiveRaceMeetingsFromGraphQL();
    //     if (!empty($activeMeetings)) {
    //         $raceDays = $activeMeetings;
    //         echo "成功！找到 " . count($raceDays) . " 个\n";
    //         foreach ($raceDays as $day) {
    //             echo "    ✓ {$day['date']} - {$day['venue_name']}";
    //             if (isset($day['race_count'])) echo " ({$day['race_count']}场)";
    //             echo "\n";
    //         }
    //     } else {
    //         echo "无数据\n";
    //     }
    // } catch (Exception $e) {
    //     echo "失败: " . $e->getMessage() . "\n";
    // }
    
    // // 方法2: 如果上面没有，尝试 raceCalendar
    // if (empty($raceDays)) {
    //     echo "  尝试获取赛马日历 (raceCalendar)... ";
    //     $calendarDays = $manager->getUpcomingRaceDaysFromGraphQL(60);
    //     if (!empty($calendarDays)) {
    //         $raceDays = $calendarDays;
    //         echo "成功！找到 " . count($raceDays) . " 个\n";
    //         foreach (array_slice($raceDays, 0, 5) as $day) {
    //             echo "    ✓ {$day['date']} - {$day['venue_name']}\n";
    //         }
    //         if (count($raceDays) > 5) {
    //             echo "    ... 共 " . count($raceDays) . " 个\n";
    //         }
    //     } else {
    //         echo "无数据\n";
    //     }
    // }
    
    // // 同步到数据库
    // if (!empty($raceDays)) {
    //     $addedCount = 0;
    //     foreach ($raceDays as $raceDay) {
    //         $stmt = $pdo->prepare("
    //             INSERT INTO hkracing_meetings (date, venue_code, status, created_at, updated_at) 
    //             VALUES (?, ?, 'PENDING', NOW(), NOW())
    //             ON DUPLICATE KEY UPDATE 
    //                 status = IF(status = 'RESULT_UPDATED', 'RESULT_UPDATED', 'PENDING'),
    //                 updated_at = NOW()
    //         ");
    //         $stmt->execute([$raceDay['date'], $raceDay['venue']]);
            
    //         if ($stmt->rowCount() > 0) {
    //             $addedCount++;
    //         }
    //     }
    //     echo "  已确保 {$addedCount} 个赛马日存在于数据库\n";
    // } else {
    //     echo "  ⚠️ 未能获取赛马日，将使用现有数据\n";
    // }
    
    // 确保明天的赛马日存在
    // $tomorrow = date('Y-m-d', strtotime('+1 day'));
    // echo "\n[2.6/5] 确保明天 ({$tomorrow}) 的赛马日存在...\n";
    // foreach (['ST', 'HV'] as $venue) {
    //     $stmt = $pdo->prepare("
    //         INSERT IGNORE INTO hkracing_meetings (date, venue_code, status, created_at, updated_at) 
    //         VALUES (?, ?, 'PENDING', NOW(), NOW())
    //     ");
    //     $stmt->execute([$tomorrow, $venue]);
    // }
    echo "  完成\n";
    // ========== 第三部分：同步今天及未来的所有赛事（保持原有逻辑） ==========
    // echo "\n[3/5] 同步今天及未来的赛事...\n";
    
    // $stmt = $pdo->prepare("
    //     SELECT DISTINCT 
    //         date,
    //         venue_code as venueCode,
    //         status,
    //         id
    //     FROM hkracing_meetings 
    //     WHERE date >= ?
    //     ORDER BY date
    // ");
    // $stmt->execute([$today]);
    // $activeMeetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // if (empty($activeMeetings)) {
    //     echo "没有找到即将到来的赛马日。\n";
    // } else {
    //     echo "找到 " . count($activeMeetings) . " 个即将到来的赛马日\n";
        
    //     foreach ($activeMeetings as $meeting) {
    //         $date = $meeting['date'];
    //         $venue = $meeting['venueCode'];
    //         $isHongKong = ($venue == 'ST' || $venue == 'HV');
            
    //         echo "同步: {$date} - {$venue} " . ($isHongKong ? '(香港)' : '(海外)') . " ... ";
            
    //         $data = $manager->getRacingData($date, $venue, null, true);
            
    //         if ($data && !isset($data['errors'])) {
    //             echo "成功\n";
                
    //             if ($isHongKong && !empty($data['data']['raceMeetings'][0]['races'])) {
    //                 $collected = 0;
    //                 foreach ($data['data']['raceMeetings'][0]['races'] as $race) {
    //                     if (!empty($race['runners'])) {
    //                         foreach ($race['runners'] as $runner) {
    //                             $horseCode = $runner['horse']['code'] ?? '';
    //                             if ($horseCode && preg_match('/^[A-Z]\d{3}$/', $horseCode)) {
    //                                 // 新版本使用 addToHistoryQueue
    //                                 $batchManager->addToHistoryQueue($horseCode, $runner['name_ch'] ?? '', 3);
    //                                 $collected++;
    //                             }
    //                         }
    //                     }
    //                 }
    //                 if ($collected > 0) {
    //                     echo "    收集到 {$collected} 匹香港马匹\n";
    //                 }
    //             }
    //         } else {
    //             $errorMsg = isset($data['errors'][0]['message']) ? $data['errors'][0]['message'] : '未知错误';
    //             echo "失败: {$errorMsg}\n";
    //         }
    //         sleep(1);
    //     }
    // }
    // ========== 第三部分：同步今天及未来的赛事 ==========
    echo "\n[3/5] 同步今天及未来的赛事...\n";
    
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
        syncRaceMeetings($pdo, $manager);
    } else {
        // echo "找到 " . count($activeMeetings) . " 个即将到来的赛马日\n";
        
        // foreach ($activeMeetings as $meeting) {
        //     $date = $meeting['date'];
        //     $venue = $meeting['venueCode'];
        //     $isHongKong = ($venue == 'ST' || $venue == 'HV');
            
        //     echo "同步: {$date} - {$venue} " . ($isHongKong ? '(香港)' : '(海外)') . " ... ";
            
        //     $data = $manager->getRacingData($date, $venue, null, true);
            
        //     if ($data && !isset($data['errors'])) {
        //         echo "成功\n";
                
        //         // ✅ 调用现有的 storeToDatabase 方法保存数据
        //         $meetingData = $data['data']['raceMeetings'][0] ?? null;
        //         if ($meetingData) {
        //             $manager->storeToDatabase($meetingData);
        //             echo "    已保存到数据库\n";
        //         }
                
        //         if ($isHongKong && !empty($data['data']['raceMeetings'][0]['races'])) {
        //             $collected = 0;
        //             foreach ($data['data']['raceMeetings'][0]['races'] as $race) {
        //                 if (!empty($race['runners'])) {
        //                     foreach ($race['runners'] as $runner) {
        //                         $horseCode = $runner['horse']['code'] ?? '';
        //                         if ($horseCode && preg_match('/^[A-Z]\d{3}$/', $horseCode)) {
        //                             $batchManager->addToHistoryQueue($horseCode, $runner['name_ch'] ?? '', 3);
        //                             $collected++;
        //                         }
        //                     }
        //                 }
        //             }
        //             if ($collected > 0) {
        //                 echo "    收集到 {$collected} 匹香港马匹\n";
        //             }
        //         }
        //     } else {
        //         $errorMsg = isset($data['errors'][0]['message']) ? $data['errors'][0]['message'] : '未知错误';
        //         echo "失败: {$errorMsg}\n";
        //     }
        //     sleep(1);
        // }
    }
    
    // ========== 第四部分：检查已结束但未更新结果的香港赛事 ==========
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
                                    // 赛后更新添加到增量队列
                                    $batchManager->addToIncrementalQueue($horseCode, $runner['name_ch'] ?? '', 'post_race_update');
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
    
    // ========== 第五部分：重置卡住的任务 ==========
    echo "\n重置卡住的任务...\n";
    $resetCount = $batchManager->resetStuckTasks();
    if ($resetCount > 0) {
        echo "  重置了 {$resetCount} 个卡住的任务\n";
    }
    
    // ========== 第六部分：处理历史队列 ==========
    echo "\n处理历史队列...\n";
    $batchCount = 0;
    $maxBatches = 5;    //10
    
    while ($batchCount < $maxBatches) {
        $processResult = $batchManager->processHistoryBatch();
        $batchCount++;
        
        $historySuccess += $processResult['results']['success'];
        $historyFailed += $processResult['results']['failed'];
        
        echo "  批次 {$batchCount}: 成功 {$processResult['results']['success']}, 失败 {$processResult['results']['failed']}\n";
        
        if ($processResult['results']['success'] == 0 && $processResult['results']['failed'] == 0) {
            break;
        }
        sleep(1);
    }
    
    // ========== 第七部分：处理增量队列 ==========
    echo "\n处理增量队列...\n";
    $batchCount = 0;
    
    while ($batchCount < $maxBatches) {
        $processResult = $batchManager->processIncrementalBatch();
        $batchCount++;
        
        $incrementalSuccess += $processResult['results']['success'];
        $incrementalFailed += $processResult['results']['failed'];
        
        echo "  批次 {$batchCount}: 成功 {$processResult['results']['success']}, 失败 {$processResult['results']['failed']}\n";
        
        if ($processResult['results']['success'] == 0 && $processResult['results']['failed'] == 0) {
            break;
        }
        sleep(1);
    }
    
    // ========== 显示统计 ==========
    $stats = $batchManager->getQueueStats();
    echo "\n📊 队列统计:\n";
    echo "  历史队列 - 待处理: {$stats['history_pending']} 匹\n";
    echo "  增量队列 - 待处理: {$stats['incremental_pending']} 匹\n";
    echo "  香港马总数: {$stats['total_hk_horses']} 匹\n";
    echo "  已同步: {$stats['synced_horses']} 匹\n";
    
    echo "\n[" . date('Y-m-d H:i:s') . "] 同步完成！\n";
    
    // ========== 发送邮件通知 ==========
    if ($historySuccess > 0) {
        // if ($historySuccess > 0 || $incrementalSuccess > 0 || $totalNewHorses > 0 || $totalIncremental > 0) {
        $emailtitle = "HRP V2";
        $ipAddress = substr($_SERVER['SERVER_ADDR'], -3);
        $Subject = "[horsepaper] " . $emailtitle . " [HKJCRacingDataManager_sync-v2] (历史:{$historySuccess}/增量:{$incrementalSuccess}) " . $ipAddress;
        
        $Body = "========== 处理结果 ==========\n";
        $Body .= "\n【新马匹收集】\n";
        $Body .= "  新增到历史队列: {$totalNewHorses} 匹\n";
        $Body .= "\n【增量更新】\n";
        $Body .= "  新增到增量队列: {$totalIncremental} 匹\n";
        $Body .= "\n【历史队列处理】\n";
        $Body .= "  成功: {$historySuccess} 匹\n";
        $Body .= "  失败: {$historyFailed} 匹\n";
        $Body .= "\n【增量队列处理】\n";
        $Body .= "  成功: {$incrementalSuccess} 匹\n";
        $Body .= "  失败: {$incrementalFailed} 匹\n";
        $Body .= "\n【当前队列状态】\n";
        $Body .= "  历史队列待处理: {$stats['history_pending']} 匹\n";
        $Body .= "  增量队列待处理: {$stats['incremental_pending']} 匹\n";
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
        echo "\n邮件通知已发送\n";
    }
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    error_log("同步错误: " . $e->getMessage());
}
?>