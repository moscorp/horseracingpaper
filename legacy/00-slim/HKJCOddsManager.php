<?php
// HKJCOddsManager.php - 使用你原有的 racingdata 函数

require_once 'lib/func_basedata.php';  // 使用你原有的函数

class HKJCOddsManager {
    private $db;
    
    public function __construct($dbHost, $dbName, $dbUser, $dbPass) {
        try {
            $this->db = new PDO(
                "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
                $dbUser,
                $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $this->db->exec("SET time_zone = '+08:00'");
        } catch (PDOException $e) {
            throw new Exception("数据库连接失败: " . $e->getMessage());
        }
    }
    
    /**
     * 获取赔率数据 - 直接使用你原来的 racingdata 函数
     */
    // public function fetchOdds($date, $venueCode, $raceNo, $type = 'Curr', $force = false) {
    //     // 使用你原有的 racingdata 函数
    //     $data = racingdata($date, $venueCode, $raceNo, $type);
        
    //     if (!$data || empty($data['data']['raceMeetings'][0]['pmPools'])) {
    //         return ['success' => false, 'message' => '赔率尚未发布，稍后再试'];
    //     }
        
    //     return $this->saveOdds($date, $venueCode, $raceNo, $data, $type);
    // }
    // public function fetchOdds($date, $venueCode, $raceNo, $type = 'Curr', $force = false) {
    //     // 确保 raceNo 是整数
    //     $raceNo = (int)$raceNo;
        
    //     // 使用你原有的 racingdata 函数
    //     $data = racingdata($date, $venueCode, $raceNo, $type);
        
    //     // 调试：只打印错误信息
    //     if (isset($data['errors'])) {
    //         echo "DEBUG Error: " . json_encode($data['errors']) . "\n";
    //         return ['success' => false, 'message' => 'API类型错误，请检查参数'];
    //     }
        
    //     if (!$data || empty($data['data']['raceMeetings'][0]['pmPools'])) {
    //         return ['success' => false, 'message' => '赔率尚未发布，稍后再试'];
    //     }
        
    //     return $this->saveOdds($date, $venueCode, $raceNo, $data, $type);
    // }
    public function fetchOdds($date, $venueCode, $raceNo, $type = 'Curr', $force = false) {
    $raceNo = (int)$raceNo;
    
    // ✅ 根据 type 参数决定请求的赔率类型
    $oddsType = ($type == 'Pre') ? 'Pre' : '';
    
    // 尝试获取 WIN 赔率
    $data = racingdata($date, $venueCode, $raceNo, 'WIN');
    
    // 如果没有 WIN 赔率，尝试 PLA
    if (!$data || empty($data['data']['raceMeetings'][0]['pmPools'])) {
        $data = racingdata($date, $venueCode, $raceNo, 'PLA');
    }
    
    // 如果还是没有，尝试不指定类型
    if (!$data || empty($data['data']['raceMeetings'][0]['pmPools'])) {
        $data = racingdata($date, $venueCode, $raceNo, '');
    }
    
    if (!$data || empty($data['data']['raceMeetings'][0]['pmPools'])) {
        return ['success' => false, 'message' => '赔率尚未发布，稍后再试'];
    }
    
    return $this->saveOdds($date, $venueCode, $raceNo, $data, $type);
}
    
    /**
     * 保存赔率数据
     */
    private function saveOdds($date, $venueCode, $raceNo, $data, $type) {
        $pools = $data['data']['raceMeetings'][0]['pmPools'];
        $hkTime = new DateTime('now', new DateTimeZone('Asia/Hong_Kong'));
        $capturedAt = $hkTime->format('Y-m-d H:i:s');
        
        // 提取马匹赔率
        $horseOdds = $this->extractHorseOdds($pools, $type);
        
        // 获取所有 runner
        $allRunners = $this->getAllRunners($date, $venueCode, $raceNo);
        
        if (empty($allRunners)) {
            return ['success' => false, 'message' => '找不到参赛马匹'];
        }
        
        // 保存主记录
        $stmt = $this->db->prepare("
            INSERT INTO hkracing_odds_data 
            (race_date, venue_code, race_no, odds_type, data_snapshot, horse_odds, captured_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $date, $venueCode, $raceNo, $type,
            json_encode($data, JSON_UNESCAPED_UNICODE),
            json_encode($horseOdds, JSON_UNESCAPED_UNICODE),
            $capturedAt
        ]);
        
        $oddsDataId = $this->db->lastInsertId();
        
        // 保存马匹赔率明细
        $savedCount = $this->saveAllHorseOddsDetails($oddsDataId, $date, $venueCode, $raceNo, $allRunners, $horseOdds, $capturedAt, $type);
        
        return [
            'success' => true,
            'odds_data_id' => $oddsDataId,
            'captured_at' => $capturedAt,
            'horse_count' => $savedCount,
            'total_runners' => count($allRunners)
        ];
    }
    
    /**
     * 从赔率池中提取马匹赔率 - 保持原有逻辑
     */
    // private function extractHorseOdds($pools, $type) {
    //     $horseOdds = [];
        
    //     foreach ($pools as $pool) {
    //         $oddsType = $pool['oddsType'];
            
    //         if (in_array($oddsType, ['WIN', 'WINPre', 'PLA', 'PLAPre'])) {
    //             foreach ($pool['oddsNodes'] as $node) {
    //                 $runnerNo = $node['combString'];
    //                 if (!isset($horseOdds[$runnerNo])) {
    //                     $horseOdds[$runnerNo] = [];
    //                 }
    //                 if ($oddsType == 'WINPre') {
    //                     $horseOdds[$runnerNo]['win_pre_odds'] = $node['oddsValue'] ?? null;
    //                     $horseOdds[$runnerNo]['is_hot_favourite_pre'] = $node['hotFavourite'] ?? 0;
    //                 } elseif ($oddsType == 'WIN') {
    //                     $horseOdds[$runnerNo]['win_odds'] = $node['oddsValue'] ?? null;
    //                     $horseOdds[$runnerNo]['is_hot_favourite'] = $node['hotFavourite'] ?? 0;
    //                     $horseOdds[$runnerNo]['win_odds_drop'] = $node['oddsDropValue'] ?? null;
    //                 } elseif ($oddsType == 'PLAPre') {
    //                     $horseOdds[$runnerNo]['place_pre_odds'] = $node['oddsValue'] ?? null;
    //                 } elseif ($oddsType == 'PLA') {
    //                     $horseOdds[$runnerNo]['place_odds'] = $node['oddsValue'] ?? null;
    //                     $horseOdds[$runnerNo]['place_odds_drop'] = $node['oddsDropValue'] ?? null;
    //                 }
    //             }
    //         }
    //     }
        
    //     return $horseOdds;
    // }
private function extractHorseOdds($pools, $type) {
    $horseOdds = [];
    
    foreach ($pools as $pool) {
        $oddsType = $pool['oddsType'];
        
        // 处理 WIN / WINPre
        if ($oddsType == 'WIN' || $oddsType == 'WINPre') {
            if (!empty($pool['oddsNodes'])) {
                foreach ($pool['oddsNodes'] as $node) {
                    // ✅ 转换 runner_no 为整数（去掉前导零）
                    $runnerNo = (int)$node['combString'];
                    if (!isset($horseOdds[$runnerNo])) {
                        $horseOdds[$runnerNo] = [];
                    }
                    if ($oddsType == 'WINPre') {
                        $horseOdds[$runnerNo]['win_pre_odds'] = $node['oddsValue'] ?? null;
                        $horseOdds[$runnerNo]['is_hot_favourite_pre'] = $node['hotFavourite'] ?? 0;
                    } else {
                        $horseOdds[$runnerNo]['win_odds'] = $node['oddsValue'] ?? null;
                        $horseOdds[$runnerNo]['is_hot_favourite'] = $node['hotFavourite'] ?? 0;
                        $horseOdds[$runnerNo]['win_odds_drop'] = $node['oddsDropValue'] ?? null;
                    }
                }
            }
        }
        
        // 处理 PLA / PLAPre
        if ($oddsType == 'PLA' || $oddsType == 'PLAPre') {
            if (!empty($pool['oddsNodes'])) {
                foreach ($pool['oddsNodes'] as $node) {
                    // ✅ 转换 runner_no 为整数（去掉前导零）
                    $runnerNo = (int)$node['combString'];
                    if (!isset($horseOdds[$runnerNo])) {
                        $horseOdds[$runnerNo] = [];
                    }
                    if ($oddsType == 'PLAPre') {
                        $horseOdds[$runnerNo]['place_pre_odds'] = $node['oddsValue'] ?? null;
                    } else {
                        $horseOdds[$runnerNo]['place_odds'] = $node['oddsValue'] ?? null;
                        $horseOdds[$runnerNo]['place_odds_drop'] = $node['oddsDropValue'] ?? null;
                    }
                }
            }
        }
    }
    
    return $horseOdds;
}
    
    /**
     * 获取该场赛事的所有参赛马匹
     */
private function getAllRunners($date, $venueCode, $raceNo) {
    $stmt = $this->db->prepare("
        SELECT 
            CAST(ru.runner_no AS UNSIGNED) as runner_no,
            ru.horse_code,
            ru.name_ch,
            ru.name_en
        FROM hkracing_runners ru
        JOIN hkracing_races r ON ru.race_id = r.id
        JOIN hkracing_meetings m ON r.meeting_id = m.id
        WHERE m.date = ? AND m.venue_code = ? AND r.race_no = ?
          AND ru.runner_no != ''
          AND ru.runner_no IS NOT NULL
        ORDER BY runner_no
    ");
    $stmt->execute([$date, $venueCode, $raceNo]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    
    /**
     * 保存所有马匹的赔率明细
     */
    private function saveAllHorseOddsDetails($oddsDataId, $date, $venueCode, $raceNo, $allRunners, $horseOdds, $capturedAt, $type, $finalPositions = []) {
    $detailStmt = $this->db->prepare("
        INSERT INTO hkracing_odds_horse_details 
        (odds_data_id, race_date, venue_code, race_no, horse_code, horse_name_ch, 
         horse_name_en, runner_no, win_pre_odds, win_odds, win_odds_drop,
         place_pre_odds, place_odds, place_odds_drop,
         is_hot_favourite_pre, is_hot_favourite, odds_type, captured_at, final_position)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $savedCount = 0;
    foreach ($allRunners as $runner) {
        $runnerNo = (int)$runner['runner_no'];  // 确保是整数
        
        // 直接匹配整数 runner_no
        $odds = $horseOdds[$runnerNo] ?? [];
        
        $detailStmt->execute([
            $oddsDataId,
            $date,
            $venueCode,
            $raceNo,
            $runner['horse_code'],
            $runner['name_ch'],
            $runner['name_en'],
            $runnerNo,
            $odds['win_pre_odds'] ?? null,
            $odds['win_odds'] ?? null,
            $odds['win_odds_drop'] ?? null,
            $odds['place_pre_odds'] ?? null,
            $odds['place_odds'] ?? null,
            $odds['place_odds_drop'] ?? null,
            $odds['is_hot_favourite_pre'] ?? 0,
            $odds['is_hot_favourite'] ?? 0,
            $type,
            $capturedAt,
            $finalPositions[$runnerNo] ?? null
        ]);
        $savedCount++;
    }
    
    return $savedCount;
}
    
    /**
     * 更新最终名次（从 GraphQL 数据）
     */
    // public function updateFinalPositions($date, $venueCode, $raceNo) {
    //     try {
    //         $data = $this->graphql->getRacingData($date, $venueCode);
            
    //         if (!$data || empty($data['raceMeetings'][0]['races'])) {
    //             return 0;
    //         }
            
    //         $targetRace = null;
    //         foreach ($data['raceMeetings'][0]['races'] as $race) {
    //             if ($race['no'] == $raceNo) {
    //                 $targetRace = $race;
    //                 break;
    //             }
    //         }
            
    //         if (!$targetRace || empty($targetRace['runners'])) {
    //             return 0;
    //         }
            
    //         // 获取 race_id
    //         $stmt = $this->db->prepare("
    //             SELECT r.id 
    //             FROM hkracing_races r
    //             JOIN hkracing_meetings m ON r.meeting_id = m.id
    //             WHERE m.date = ? AND m.venue_code = ? AND r.race_no = ?
    //         ");
    //         $stmt->execute([$date, $venueCode, $raceNo]);
    //         $raceResult = $stmt->fetch(PDO::FETCH_ASSOC);
    //         $raceId = $raceResult ? $raceResult['id'] : null;
            
    //         $updated = 0;
            
    //         foreach ($targetRace['runners'] as $runner) {
    //             $runnerNo = $runner['no'];
    //             $finalPosition = $runner['finalPosition'] ?? null;
    //             $winOdds = $runner['winOdds'] ?? null;
                
    //             if ($finalPosition !== null && $finalPosition > 0) {
    //                 // 更新 odds_horse_details
    //                 $stmt1 = $this->db->prepare("
    //                     UPDATE hkracing_odds_horse_details 
    //                     SET final_position = ?, captured_at = NOW()
    //                     WHERE race_date = ? AND venue_code = ? AND race_no = ? AND runner_no = ?
    //                 ");
    //                 $stmt1->execute([$finalPosition, $date, $venueCode, $raceNo, $runnerNo]);
    //                 $updated += $stmt1->rowCount();
                    
    //                 // 更新 runners 表
    //                 if ($raceId) {
    //                     $stmt2 = $this->db->prepare("
    //                         UPDATE hkracing_runners 
    //                         SET final_position = ?, win_odds = ?, updated_at = NOW()
    //                         WHERE race_id = ? AND runner_no = ?
    //                     ");
    //                     $stmt2->execute([$finalPosition, $winOdds, $raceId, $runnerNo]);
    //                     $updated += $stmt2->rowCount();
    //                 }
                    
    //                 // 更新 v2_score 表
    //                 if ($raceId) {
    //                     $stmt3 = $this->db->prepare("
    //                         UPDATE hkracing_v2_score 
    //                         SET final_position = ?, final_win_odds = ?, last_updated = NOW()
    //                         WHERE race_id = ? AND runner_no = ? AND score_version = 2
    //                     ");
    //                     $stmt3->execute([$finalPosition, $winOdds, $raceId, $runnerNo]);
    //                     $updated += $stmt3->rowCount();
    //                 }
    //             }
    //         }
            
    //         return $updated;
            
    //     } catch (Exception $e) {
    //         error_log("updateFinalPositions error: " . $e->getMessage());
    //         return 0;
    //     }
    // }
    /**
     * 更新最终名次 - 使用 basedata 函数获取结果
     */
    // public function updateFinalPositions($date, $venueCode, $raceNo) {
    //     try {
    //         // 使用 basedata 函数获取完赛结果
    //         $resultOddsType = ['WIN', 'PLA'];
    //         $data = basedata($date, $venueCode, $resultOddsType);
            
    //         if (!$data || empty($data['data']['raceMeetings'][0]['races'])) {
    //             return 0;
    //         }
            
    //         // 找到对应的场次
    //         $targetRace = null;
    //         foreach ($data['data']['raceMeetings'][0]['races'] as $race) {
    //             if ($race['no'] == $raceNo) {
    //                 $targetRace = $race;
    //                 break;
    //             }
    //         }
            
    //         if (!$targetRace || empty($targetRace['runners'])) {
    //             return 0;
    //         }
            
    //         // 获取 race_id
    //         $stmt = $this->db->prepare("
    //             SELECT r.id 
    //             FROM hkracing_races r
    //             JOIN hkracing_meetings m ON r.meeting_id = m.id
    //             WHERE m.date = ? AND m.venue_code = ? AND r.race_no = ?
    //         ");
    //         $stmt->execute([$date, $venueCode, $raceNo]);
    //         $raceResult = $stmt->fetch(PDO::FETCH_ASSOC);
    //         $raceId = $raceResult ? $raceResult['id'] : null;
            
    //         $updated = 0;
            
    //         foreach ($targetRace['runners'] as $runner) {
    //             $runnerNo = $runner['no'];
    //             $finalPosition = $runner['finalPosition'] ?? null;
    //             $winOdds = $runner['winOdds'] ?? null;
                
    //             if ($finalPosition !== null && $finalPosition > 0) {
    //                 // 更新 odds_horse_details
    //                 $stmt1 = $this->db->prepare("
    //                     UPDATE hkracing_odds_horse_details 
    //                     SET final_position = ?, captured_at = NOW()
    //                     WHERE race_date = ? AND venue_code = ? AND race_no = ? AND runner_no = ?
    //                 ");
    //                 $stmt1->execute([$finalPosition, $date, $venueCode, $raceNo, $runnerNo]);
    //                 $updated += $stmt1->rowCount();
                    
    //                 // 更新 runners 表
    //                 if ($raceId) {
    //                     $stmt2 = $this->db->prepare("
    //                         UPDATE hkracing_runners 
    //                         SET final_position = ?, win_odds = ?, updated_at = NOW()
    //                         WHERE race_id = ? AND runner_no = ?
    //                     ");
    //                     $stmt2->execute([$finalPosition, $winOdds, $raceId, $runnerNo]);
    //                     $updated += $stmt2->rowCount();
    //                 }
                    
    //                 // 更新 v2_score 表
    //                 if ($raceId) {
    //                     $stmt3 = $this->db->prepare("
    //                         UPDATE hkracing_v2_score 
    //                         SET final_position = ?, final_win_odds = ?, last_updated = NOW()
    //                         WHERE race_id = ? AND runner_no = ? AND score_version = 2
    //                     ");
    //                     $stmt3->execute([$finalPosition, $winOdds, $raceId, $runnerNo]);
    //                     $updated += $stmt3->rowCount();
    //                 }
    //             }
    //         }
            
    //         return $updated;
            
    //     } catch (Exception $e) {
    //         error_log("updateFinalPositions error: " . $e->getMessage());
    //         return 0;
    //     }
    // }
    /**
     * Update final position only (no odds update)
     */
    // public function updateFinalPositions($date, $venueCode, $raceNo) {
    //     try {
    //         $resultOddsType = ['WIN', 'PLA'];
    //         $data = basedata($date, $venueCode, $resultOddsType);
            
    //         if (!$data || empty($data['data']['raceMeetings'][0]['races'])) {
    //             return 0;
    //         }
            
    //         // 找到对应的场次
    //         $targetRace = null;
    //         foreach ($data['data']['raceMeetings'][0]['races'] as $race) {
    //             if ($race['no'] == $raceNo) {
    //                 $targetRace = $race;
    //                 break;
    //             }
    //         }
            
    //         if (!$targetRace || empty($targetRace['runners'])) {
    //             return 0;
    //         }
            
    //         // 获取 race_id
    //         $stmt = $this->db->prepare("
    //             SELECT r.id 
    //             FROM hkracing_races r
    //             JOIN hkracing_meetings m ON r.meeting_id = m.id
    //             WHERE m.date = ? AND m.venue_code = ? AND r.race_no = ?
    //         ");
    //         $stmt->execute([$date, $venueCode, $raceNo]);
    //         $raceResult = $stmt->fetch(PDO::FETCH_ASSOC);
    //         $raceId = $raceResult ? $raceResult['id'] : null;
            
    //         $updated = 0;
            
    //         foreach ($targetRace['runners'] as $runner) {
    //             $runnerNo = $runner['no'];
    //             $finalPosition = $runner['finalPosition'] ?? null;
                
    //             if ($finalPosition !== null && $finalPosition > 0) {
    //                 // 更新 odds_horse_details
    //                 $stmt1 = $this->db->prepare("
    //                     UPDATE hkracing_odds_horse_details 
    //                     SET final_position = ?, captured_at = NOW()
    //                     WHERE race_date = ? AND venue_code = ? AND race_no = ? AND runner_no = ?
    //                 ");
    //                 $stmt1->execute([$finalPosition, $date, $venueCode, $raceNo, $runnerNo]);
    //                 $updated += $stmt1->rowCount();
                    
    //                 // 更新 runners 表 (移除 win_odds)
    //                 if ($raceId) {
    //                     $stmt2 = $this->db->prepare("
    //                         UPDATE hkracing_runners 
    //                         SET final_position = ?, updated_at = NOW()
    //                         WHERE race_id = ? AND runner_no = ?
    //                     ");
    //                     $stmt2->execute([$finalPosition, $raceId, $runnerNo]);
    //                     $updated += $stmt2->rowCount();
    //                 }
                    
    //                 // 更新 v2_score 表 - ONLY final_position, NOT odds
    //                 if ($raceId) {
    //                     $stmt3 = $this->db->prepare("
    //                         UPDATE hkracing_v2_score 
    //                         SET final_position = ?, last_updated = NOW()
    //                         WHERE race_id = ? AND runner_no = ? AND score_version = 2
    //                     ");
    //                     $stmt3->execute([$finalPosition, $raceId, $runnerNo]);
    //                     $updated += $stmt3->rowCount();
    //                 }
    //             }
    //         }
            
    //         return $updated;
            
    //     } catch (Exception $e) {
    //         error_log("updateFinalPositions error: " . $e->getMessage());
    //         return 0;
    //     }
    // }
    
    /**
 * Update win_odds_snapshot and place_odds in hkracing_v2_score for version 2 records
 */
// public function updateV2ScoreOdds($date, $venueCode, $raceNo) {
//     try {
//         // Get race_id first
//         $stmt = $this->db->prepare("
//             SELECT r.id 
//             FROM hkracing_races r
//             JOIN hkracing_meetings m ON r.meeting_id = m.id
//             WHERE m.date = ? AND m.venue_code = ? AND r.race_no = ?
//         ");
//         $stmt->execute([$date, $venueCode, $raceNo]);
//         $race = $stmt->fetch(PDO::FETCH_ASSOC);
        
//         if (!$race) {
//             return 0;
//         }
        
//         $raceId = $race['id'];
        
//         // Get latest odds for this race
//         $stmt = $this->db->prepare("
//             SELECT runner_no, win_odds, place_odds
//             FROM hkracing_odds_horse_details
//             WHERE race_date = ? AND venue_code = ? AND race_no = ? 
//               AND odds_type = 'Curr'
//               AND (win_odds > 0 OR place_odds > 0)
//         ");
//         $stmt->execute([$date, $venueCode, $raceNo]);
//         $oddsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
//         $updated = 0;
//         foreach ($oddsList as $odds) {
//             $stmt = $this->db->prepare("
//                 UPDATE hkracing_v2_score 
//                 SET win_odds_snapshot = ?, 
//                     place_odds = ?, 
//                     has_odds = 1,
//                     last_updated = NOW()
//                 WHERE race_id = ? AND runner_no = ? AND score_version = 2
//             ");
//             $stmt->execute([
//                 $odds['win_odds'],
//                 $odds['place_odds'],
//                 $raceId,
//                 $odds['runner_no']
//             ]);
//             $updated += $stmt->rowCount();
//         }
        
//         return $updated;
        
//     } catch (Exception $e) {
//         error_log("updateV2ScoreOdds error: " . $e->getMessage());
//         return 0;
//     }
// }
/**
 * Update win_odds_snapshot and place_odds in hkracing_v2_score for version 2 records
 */
public function updateV2ScoreOdds($date, $venueCode, $raceNo) {
    try {
        // Get race_id first
        $stmt = $this->db->prepare("
            SELECT r.id 
            FROM hkracing_races r
            JOIN hkracing_meetings m ON r.meeting_id = m.id
            WHERE m.date = ? AND m.venue_code = ? AND r.race_no = ?
        ");
        $stmt->execute([$date, $venueCode, $raceNo]);
        $race = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$race) {
            return 0;
        }
        
        $raceId = $race['id'];
        
        // Get latest odds for this race
        $stmt = $this->db->prepare("
            SELECT runner_no, win_odds, place_odds
            FROM hkracing_odds_horse_details
            WHERE race_date = ? AND venue_code = ? AND race_no = ? 
              AND odds_type = 'Curr'
              AND (win_odds > 0 OR place_odds > 0)
        ");
        $stmt->execute([$date, $venueCode, $raceNo]);
        $oddsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $updated = 0;
        foreach ($oddsList as $odds) {
            $stmt = $this->db->prepare("
                UPDATE hkracing_v2_score 
                SET win_odds_snapshot = ?, 
                    place_odds = ?, 
                    has_odds = 1,
                    last_updated = NOW()
                WHERE race_id = ? AND runner_no = ? AND score_version = 2
            ");
            $stmt->execute([
                $odds['win_odds'],
                $odds['place_odds'],
                $raceId,
                $odds['runner_no']
            ]);
            $updated += $stmt->rowCount();
        }
        
        return $updated;
        
    } catch (Exception $e) {
        error_log("updateV2ScoreOdds error: " . $e->getMessage());
        return 0;
    }
}
/**
 * Update final position only (no odds update)
 */
    // public function updateFinalPositions($date, $venueCode, $raceNo) {
    //     try {
    //         $data = basedata($date, $venueCode, ['WIN', 'PLA']);
            
    //         if (!$data || empty($data['data']['raceMeetings'][0]['races'])) {
    //             return 0;
    //         }
            
    //         $targetRace = null;
    //         foreach ($data['data']['raceMeetings'][0]['races'] as $race) {
    //             if ($race['no'] == $raceNo) {
    //                 $targetRace = $race;
    //                 break;
    //             }
    //         }
            
    //         if (!$targetRace || empty($targetRace['runners'])) {
    //             return 0;
    //         }
            
    //         // Get race_id
    //         $stmt = $this->db->prepare("
    //             SELECT r.id 
    //             FROM hkracing_races r
    //             JOIN hkracing_meetings m ON r.meeting_id = m.id
    //             WHERE m.date = ? AND m.venue_code = ? AND r.race_no = ?
    //         ");
    //         $stmt->execute([$date, $venueCode, $raceNo]);
    //         $raceResult = $stmt->fetch(PDO::FETCH_ASSOC);
    //         $raceId = $raceResult ? $raceResult['id'] : null;
            
    //         $updated = 0;
            
    //         foreach ($targetRace['runners'] as $runner) {
    //             $runnerNo = $runner['no'];
    //             $finalPosition = $runner['finalPosition'] ?? null;
                
    //             if ($finalPosition !== null && $finalPosition > 0) {
    //                 // Update odds_horse_details
    //                 $stmt1 = $this->db->prepare("
    //                     UPDATE hkracing_odds_horse_details 
    //                     SET final_position = ?, captured_at = NOW()
    //                     WHERE race_date = ? AND venue_code = ? AND race_no = ? AND runner_no = ?
    //                 ");
    //                 $stmt1->execute([$finalPosition, $date, $venueCode, $raceNo, $runnerNo]);
    //                 $updated += $stmt1->rowCount();
                    
    //                 // Update runners table
    //                 if ($raceId) {
    //                     $stmt2 = $this->db->prepare("
    //                         UPDATE hkracing_runners 
    //                         SET final_position = ?, updated_at = NOW()
    //                         WHERE race_id = ? AND runner_no = ?
    //                     ");
    //                     $stmt2->execute([$finalPosition, $raceId, $runnerNo]);
    //                     $updated += $stmt2->rowCount();
    //                 }
                    
    //                 // Update v2_score table - ONLY final_position, NOT odds
    //                 if ($raceId) {
    //                     $stmt3 = $this->db->prepare("
    //                         UPDATE hkracing_v2_score 
    //                         SET final_position = ?, last_updated = NOW()
    //                         WHERE race_id = ? AND runner_no = ? AND score_version = 2
    //                     ");
    //                     $stmt3->execute([$finalPosition, $raceId, $runnerNo]);
    //                     $updated += $stmt3->rowCount();
    //                 }
    //             }
    //         }
            
    //         return $updated;
            
    //     } catch (Exception $e) {
    //         error_log("updateFinalPositions error: " . $e->getMessage());
    //         return 0;
    //     }
    // }
    /**
     * 更新最终名次 - 使用 basedata 函数获取结果
     */
    public function updateFinalPositions($date, $venueCode, $raceNo) {
        try {
            // 使用 basedata 函数获取完赛结果
            $resultOddsType = ['WIN', 'PLA'];
            $data = basedata($date, $venueCode, $resultOddsType);
            
            if (!$data || empty($data['data']['raceMeetings'][0]['races'])) {
                return 0;
            }
            
            // 找到对应的场次
            $targetRace = null;
            foreach ($data['data']['raceMeetings'][0]['races'] as $race) {
                if ($race['no'] == $raceNo) {
                    $targetRace = $race;
                    break;
                }
            }
            
            if (!$targetRace || empty($targetRace['runners'])) {
                return 0;
            }
            
            // 获取 race_id
            $stmt = $this->db->prepare("
                SELECT r.id 
                FROM hkracing_races r
                JOIN hkracing_meetings m ON r.meeting_id = m.id
                WHERE m.date = ? AND m.venue_code = ? AND r.race_no = ?
            ");
            $stmt->execute([$date, $venueCode, $raceNo]);
            $raceResult = $stmt->fetch(PDO::FETCH_ASSOC);
            $raceId = $raceResult ? $raceResult['id'] : null;
            
            $updated = 0;
            
            foreach ($targetRace['runners'] as $runner) {
                $runnerNo = $runner['no'];
                $finalPosition = $runner['finalPosition'] ?? null;
                
                if ($finalPosition !== null && $finalPosition > 0) {
                    // 更新 odds_horse_details
                    $stmt1 = $this->db->prepare("
                        UPDATE hkracing_odds_horse_details 
                        SET final_position = ?, captured_at = NOW()
                        WHERE race_date = ? AND venue_code = ? AND race_no = ? AND runner_no = ?
                    ");
                    $stmt1->execute([$finalPosition, $date, $venueCode, $raceNo, $runnerNo]);
                    $updated += $stmt1->rowCount();
                    
                    // 更新 runners 表 (只更新 final_position，不更新 win_odds)
                    if ($raceId) {
                        $stmt2 = $this->db->prepare("
                            UPDATE hkracing_runners 
                            SET final_position = ?, updated_at = NOW()
                            WHERE race_id = ? AND runner_no = ?
                        ");
                        $stmt2->execute([$finalPosition, $raceId, $runnerNo]);
                        $updated += $stmt2->rowCount();
                    }
                    
                    // 更新 v2_score 表 (只更新 final_position，不更新 odds)
                    if ($raceId) {
                        $stmt3 = $this->db->prepare("
                            UPDATE hkracing_v2_score 
                            SET final_position = ?, last_updated = NOW()
                            WHERE race_id = ? AND runner_no = ? AND score_version = 2
                        ");
                        $stmt3->execute([$finalPosition, $raceId, $runnerNo]);
                        $updated += $stmt3->rowCount();
                    }
                }
            }
            
            return $updated;
            
        } catch (Exception $e) {
            error_log("updateFinalPositions error: " . $e->getMessage());
            return 0;
        }
    }
}