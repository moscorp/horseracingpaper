<?php
// HKJCOddsManager.php - 修复版（包含时区转换和完整赔率）

class HKJCOddsManager {
    private $db;
    private $apiUrl = 'https://info.cld.hkjc.com/graphql/base/';
    
    const ODDS_TYPES_PRE = ['WINPre', 'PLAPre', 'QINPre', 'QPLPre'];
    const ODDS_TYPES_CURRENT = ['WIN', 'PLA', 'QIN', 'QPL', 'TRITop', 'FFTop', 'TCETop', 'QTTTop', 'DBL'];
    
    public function __construct($dbHost, $dbName, $dbUser, $dbPass) {
        try {
            $this->db = new PDO(
                "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
                $dbUser,
                $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            // 关键：设置连接时区为香港时间
            $this->db->exec("SET time_zone = '+08:00'");
            $this->db->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            throw new Exception("数据库连接失败: " . $e->getMessage());
        }
    }
    
    /**
     * 检查是否需要更新赔率
     */
    private function shouldUpdate($date, $venueCode, $raceNo, $type) {
        // 如果是 Curr 类型且比赛已结束，也允许抓取一次最终赔率
        $stmt = $this->db->prepare("
            SELECT r.post_time
            FROM hkracing_races r
            JOIN hkracing_meetings m ON r.meeting_id = m.id
            WHERE m.date = ? AND m.venue_code = ? AND r.race_no = ?
        ");
        $stmt->execute([$date, $venueCode, $raceNo]);
        $race = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$race || !$race['post_time']) {
            return false;
        }
        
        $now = new DateTime('now', new DateTimeZone('Asia/Hong_Kong'));
        $raceTime = new DateTime($race['post_time'], new DateTimeZone('Asia/Hong_Kong'));
        $raceEndTime = clone $raceTime;
        $raceEndTime->modify('+30 minutes');
        echo "22222222222222222222";
        // 比赛已结束，但如果是 Curr 类型且还没有任何 Curr 数据，允许抓取一次
        if ($now > $raceEndTime) {
            echo "11111111111";
            if ($type == 'Curr') {
                // 检查是否已有 Curr 数据
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) FROM hkracing_odds_data 
                    WHERE race_date = ? AND venue_code = ? AND race_no = ? AND odds_type = 'Curr'
                ");
                $stmt->execute([$date, $venueCode, $raceNo]);
                $hasCurr = $stmt->fetchColumn() > 0;
                return !$hasCurr; // 如果没有 Curr 数据，允许抓取一次
            }
            return false;
        }
        
        if ($now < $raceTime) {
            return $type == 'Pre';
        }
        
        if ($now >= $raceTime && $now <= $raceEndTime) {
            return $type == 'Curr';
        }
        
        return false;
    }
    
    // public function fetchOdds($date, $venueCode, $raceNo, $type = 'Curr', $force = false) {
    //     // 检查是否需要更新
    //     if (!$force && !$this->shouldUpdate($date, $venueCode, $raceNo, $type)) {
    //         return ['message' => '比赛已结束或未到更新时机', 'skipped' => true];
    //     }
        
    //     // 调试输出（如果需要，但不要加 return）
    //     // echo "Fetching: {$date} {$venueCode} R{$raceNo} ({$type})\n";
        
    //     $oddsTypes = ($type == 'Pre') ? self::ODDS_TYPES_PRE : self::ODDS_TYPES_CURRENT;
        
    //     $query = $this->getOddsQuery();
    //     $variables = [
    //         'date' => $date,
    //         'venueCode' => $venueCode,
    //         'raceNo' => (int)$raceNo,
    //         'oddsTypes' => $oddsTypes
    //     ];
        
    //     $data = $this->executeGraphQL($query, $variables);
        
    //     // 检查是否有赔率数据
    //     if ($data && !isset($data['errors'])) {
    //         if (empty($data['data']['raceMeetings'][0]['pmPools'])) {
    //             return ['success' => false, 'message' => '无赔率数据（可能尚未发布）'];
    //         }
    //         return $this->saveOdds($date, $venueCode, $raceNo, $data, $type);
    //     }
        
    //     return $data ?? ['success' => false, 'message' => 'API请求失败'];
    // }
    public function fetchOdds($date, $venueCode, $raceNo, $type = 'Curr', $force = false) {
        // 检查是否需要更新
        if (!$force && !$this->shouldUpdate($date, $venueCode, $raceNo, $type)) {
            return ['message' => '比赛已结束或未到更新时机', 'skipped' => true];
        }
        
        // ✅ 使用完整查询（包含 races 和 runners）
        $query = $this->getFullRacingQuery();
        $variables = [
            'date' => $date,
            'venueCode' => $venueCode,
            'foOddsTypes' => [],
            'foFilter' => [],
            'resultOddsType' => ['WIN', 'PLA', 'QIN', 'QPL']
        ];
        
        $data = $this->executeGraphQL($query, $variables);
        
        // 检查是否有赔率数据
        if ($data && !isset($data['errors'])) {
            if (empty($data['data']['raceMeetings'][0]['pmPools'])) {
                return ['success' => false, 'message' => '无赔率数据（可能尚未发布）'];
            }
            return $this->saveOdds($date, $venueCode, $raceNo, $data, $type);
        }
        
        return $data ?? ['success' => false, 'message' => 'API请求失败'];
    }
    
    /**
     * 从完整查询结果中提取指定场次的赔率
     */
    private function extractOddsFromFullData($data, $date, $venueCode, $raceNo, $type) {
        if (empty($data['data']['raceMeetings'][0]['pmPools'])) {
            return ['success' => false, 'message' => '无赔率数据'];
        }
        
        // 找到对应的赛事
        $raceMeeting = $data['data']['raceMeetings'][0];
        $targetRace = null;
        foreach ($raceMeeting['races'] as $race) {
            if ($race['no'] == $raceNo) {
                $targetRace = $race;
                break;
            }
        }
        
        if (!$targetRace) {
            return ['success' => false, 'message' => '找不到指定场次'];
        }
        
        // 构建赔率池
        $pools = [];
        
        // 根据类型筛选需要的赔率池
        if ($type == 'Pre') {
            $neededTypes = ['WINPre', 'PLAPre', 'QINPre', 'QPLPre'];
        } else {
            $neededTypes = ['WIN', 'PLA', 'QIN', 'QPL'];
        }
        
        foreach ($raceMeeting['pmPools'] as $pool) {
            if (in_array($pool['oddsType'], $neededTypes)) {
                $pools[] = $pool;
            }
        }
        
        if (empty($pools)) {
            return ['success' => false, 'message' => '无有效赔率数据'];
        }
        
        // 重新包装数据格式，使其与原有 saveOdds 方法兼容
        $wrappedData = [
            'data' => [
                'raceMeetings' => [
                    [
                        'pmPools' => $pools
                    ]
                ]
            ]
        ];
        
        return $this->saveOdds($date, $venueCode, $raceNo, $wrappedData, $type);
    }
    
    /**
     * 保存赔率数据（修复版）
     */
    // private function saveOdds($date, $venueCode, $raceNo, $data, $type) {
    //     // if (empty($data['data']['raceMeetings'][0]['pmPools'])) {
    //     //     return ['success' => false, 'message' => '无赔率数据'];
    //     // }
    //     // 检查 API 是否返回了有效的赔率池
    //     if (empty($data['data']['raceMeetings'][0]['pmPools'])) {
    //         // 记录日志，便于调试
    //         error_log("No odds data for {$date} {$venueCode} R{$raceNo} ({$type})");
    //         return ['success' => false, 'message' => '无赔率数据（API返回为空）'];
    //     }
        
    //     $pools = $data['data']['raceMeetings'][0]['pmPools'];
    //     $hkTime = new DateTime('now', new DateTimeZone('Asia/Hong_Kong'));
    //     $capturedAt = $hkTime->format('Y-m-d H:i:s');
        
    //     // 提取马匹赔率（现在包含 drop 值）
    //     $horseOdds = $this->extractHorseOdds($pools, $type);
    //     // print_r($horseOdds);
    //     // 获取所有 runner
    //     $allRunners = $this->getAllRunners($date, $venueCode, $raceNo);
        
    //     if (empty($allRunners)) {
    //         return ['success' => false, 'message' => '找不到参赛马匹'];
    //     }
        
    //     // 检查是否有任何有效赔率
    //     // 检查是否有任何有效赔率（包括 Pre 赔率）
    //     // 检查是否有任何有效赔率（包括 Pre 赔率）
    //     $hasAnyValidOdds = false;
    //     foreach ($allRunners as $runner) {
    //         $runnerNo = $runner['runner_no'];
    //         $odds = $horseOdds[$runnerNo] ?? $horseOdds[ltrim($runnerNo, '0')] ?? $horseOdds[str_pad($runnerNo, 2, '0', STR_PAD_LEFT)] ?? [];
    //         // print_r($odds);
            
    //         // 检查 Pre 赔率 (win_pre_odds, place_pre_odds) 和 Curr 赔率 (win_odds, place_odds)
    //         if (($odds['win_pre_odds'] ?? null) !== null ||
    //             ($odds['win_odds'] ?? null) !== null ||
    //             ($odds['place_pre_odds'] ?? null) !== null ||
    //             ($odds['place_odds'] ?? null) !== null) {
    //             $hasAnyValidOdds = true;
    //             break;
    //         }
    //     }
        
    //     if (!$hasAnyValidOdds) {
    //         // 记录调试信息
    //         error_log("No valid odds found for {$date} {$venueCode} R{$raceNo} ({$type})");
    //         error_log("HorseOdds: " . json_encode($horseOdds));
    //         return ['success' => false, 'message' => '无有效赔率数据，跳过保存'];
    //     }
        
    //     // 保存主记录
    //     $stmt = $this->db->prepare("
    //         INSERT INTO hkracing_odds_data 
    //         (race_date, venue_code, race_no, odds_type, data_snapshot, horse_odds, captured_at)
    //         VALUES (?, ?, ?, ?, ?, ?, ?)
    //     ");
        
    //     $stmt->execute([
    //         $date,
    //         $venueCode,
    //         $raceNo,
    //         $type,
    //         json_encode($data, JSON_UNESCAPED_UNICODE),
    //         json_encode($horseOdds, JSON_UNESCAPED_UNICODE),
    //         $capturedAt
    //     ]);
        
    //     $oddsDataId = $this->db->lastInsertId();
        
    //     // 保存马匹赔率明细
    //     $savedCount = $this->saveAllHorseOddsDetails($oddsDataId, $date, $venueCode, $raceNo, $allRunners, $horseOdds, $capturedAt, $type);
    //     // echo ">>>>".$savedCount;
    //     // exit;
    //     if ($savedCount == 0) {
    //         $this->db->prepare("DELETE FROM hkracing_odds_data WHERE id = ?")->execute([$oddsDataId]);
    //         return ['success' => false, 'message' => '无有效赔率数据，已删除主记录'];
    //     }
        
    //     return [
    //         'success' => true,
    //         'odds_data_id' => $oddsDataId,
    //         'captured_at' => $capturedAt,
    //         'horse_count' => $savedCount,
    //         'total_runners' => count($allRunners)
    //     ];
    // }
    private function saveOdds($date, $venueCode, $raceNo, $data, $type) {
        if (empty($data['data']['raceMeetings'][0]['pmPools'])) {
            error_log("No odds data for {$date} {$venueCode} R{$raceNo} ({$type})");
            return ['success' => false, 'message' => '无赔率数据（API返回为空）'];
        }
        
        $pools = $data['data']['raceMeetings'][0]['pmPools'];
        $hkTime = new DateTime('now', new DateTimeZone('Asia/Hong_Kong'));
        $capturedAt = $hkTime->format('Y-m-d H:i:s');
        
        // 提取马匹赔率（包含 drop 值）
        $horseOdds = $this->extractHorseOdds($pools, $type);
        
        // ✅ 新增：提取 finalPosition（从 races 数据中）
        $finalPositions = [];
        if (!empty($data['data']['raceMeetings'][0]['races'])) {
            foreach ($data['data']['raceMeetings'][0]['races'] as $race) {
                if ($race['no'] == $raceNo && !empty($race['runners'])) {
                    foreach ($race['runners'] as $runner) {
                        $runnerNo = $runner['no'];
                        $finalPositions[$runnerNo] = $runner['finalPosition'] ?? null;
                    }
                    break;
                }
            }
        }
        
        // 获取所有 runner
        $allRunners = $this->getAllRunners($date, $venueCode, $raceNo);
        
        if (empty($allRunners)) {
            return ['success' => false, 'message' => '找不到参赛马匹'];
        }
        
        // 检查是否有任何有效赔率
        $hasAnyValidOdds = false;
        foreach ($allRunners as $runner) {
            $runnerNo = $runner['runner_no'];
            $odds = $horseOdds[$runnerNo] ?? $horseOdds[ltrim($runnerNo, '0')] ?? $horseOdds[str_pad($runnerNo, 2, '0', STR_PAD_LEFT)] ?? [];
            
            if (($odds['win_pre_odds'] ?? null) !== null ||
                ($odds['win_odds'] ?? null) !== null ||
                ($odds['place_pre_odds'] ?? null) !== null ||
                ($odds['place_odds'] ?? null) !== null) {
                $hasAnyValidOdds = true;
                break;
            }
        }
        
        if (!$hasAnyValidOdds) {
            error_log("No valid odds found for {$date} {$venueCode} R{$raceNo} ({$type})");
            return ['success' => false, 'message' => '无有效赔率数据，跳过保存'];
        }
        
        // 保存主记录
        $stmt = $this->db->prepare("
            INSERT INTO hkracing_odds_data 
            (race_date, venue_code, race_no, odds_type, data_snapshot, horse_odds, captured_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $date,
            $venueCode,
            $raceNo,
            $type,
            json_encode($data, JSON_UNESCAPED_UNICODE),
            json_encode($horseOdds, JSON_UNESCAPED_UNICODE),
            $capturedAt
        ]);
        
        $oddsDataId = $this->db->lastInsertId();
        
        // 保存马匹赔率明细（包含 finalPosition）
        $savedCount = $this->saveAllHorseOddsDetails($oddsDataId, $date, $venueCode, $raceNo, $allRunners, $horseOdds, $capturedAt, $type, $finalPositions);
        
        if ($savedCount == 0) {
            $this->db->prepare("DELETE FROM hkracing_odds_data WHERE id = ?")->execute([$oddsDataId]);
            return ['success' => false, 'message' => '无有效赔率数据，已删除主记录'];
        }
        
        return [
            'success' => true,
            'odds_data_id' => $oddsDataId,
            'captured_at' => $capturedAt,
            'horse_count' => $savedCount,
            'total_runners' => count($allRunners)
        ];
    }
    
    /**
     * 获取该场赛事的所有参赛马匹
     */
    private function getAllRunners($date, $venueCode, $raceNo) {
        $stmt = $this->db->prepare("
            SELECT 
                ru.runner_no,
                ru.horse_code,
                ru.name_ch,
                ru.name_en
            FROM hkracing_runners ru
            JOIN hkracing_races r ON ru.race_id = r.id
            JOIN hkracing_meetings m ON r.meeting_id = m.id
            WHERE m.date = ? AND m.venue_code = ? AND r.race_no = ?
              AND ru.runner_no != ''
              AND ru.runner_no IS NOT NULL
            ORDER BY CAST(ru.runner_no AS UNSIGNED)
        ");
        $stmt->execute([$date, $venueCode, $raceNo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 从赔率池中提取马匹赔率
     */
    /**
     * 从赔率池中提取马匹赔率（包含所有赔率类型）
     */
    private function extractHorseOdds($pools, $oddsType = 'Curr') {
        $horseOdds = [];
        
        foreach ($pools as $pool) {
            $poolOddsType = $pool['oddsType'];
            
            // ========== 独赢赔率 (WIN / WINPre) ==========
            if ($poolOddsType == 'WIN' || $poolOddsType == 'WINPre') {
                if (!empty($pool['oddsNodes'])) {
                    foreach ($pool['oddsNodes'] as $node) {
                        $runnerNo = $node['combString'];
                        if (!isset($horseOdds[$runnerNo])) {
                            $horseOdds[$runnerNo] = [];
                        }
                        if ($poolOddsType == 'WINPre') {
                            $horseOdds[$runnerNo]['win_pre_odds'] = $node['oddsValue'] ?? null;
                            $horseOdds[$runnerNo]['is_hot_favourite_pre'] = $node['hotFavourite'] ?? 0;
                        } else {
                            $horseOdds[$runnerNo]['win_odds'] = $node['oddsValue'] ?? null;
                            $horseOdds[$runnerNo]['is_hot_favourite'] = $node['hotFavourite'] ?? 0;
                            // 新增：提取独赢赔率变化值
                            $horseOdds[$runnerNo]['win_odds_drop'] = $node['oddsDropValue'] ?? null;
                        }
                    }
                }
            }
            
            // ========== 位置赔率 (PLA / PLAPre) ==========
            if ($poolOddsType == 'PLA' || $poolOddsType == 'PLAPre') {
                if (!empty($pool['oddsNodes'])) {
                    foreach ($pool['oddsNodes'] as $node) {
                        $runnerNo = $node['combString'];
                        if (!isset($horseOdds[$runnerNo])) {
                            $horseOdds[$runnerNo] = [];
                        }
                        if ($poolOddsType == 'PLAPre') {
                            $horseOdds[$runnerNo]['place_pre_odds'] = $node['oddsValue'] ?? null;
                        } else {
                            $horseOdds[$runnerNo]['place_odds'] = $node['oddsValue'] ?? null;
                            // 新增：提取位置赔率变化值
                            $horseOdds[$runnerNo]['place_odds_drop'] = $node['oddsDropValue'] ?? null;
                        }
                    }
                }
            }
            
            // ========== 连赢赔率 (QIN / QINPre) ==========
            if ($poolOddsType == 'QIN' || $poolOddsType == 'QINPre') {
                if (!empty($pool['oddsNodes'])) {
                    foreach ($pool['oddsNodes'] as $node) {
                        $combString = $node['combString'];  // 格式如 "1,2"
                        if ($poolOddsType == 'QINPre') {
                            $horseOdds['quinella_pre'][$combString] = $node['oddsValue'] ?? null;
                        } else {
                            $horseOdds['quinella_curr'][$combString] = $node['oddsValue'] ?? null;
                        }
                    }
                }
            }
            
            // ========== 位置Q赔率 (QPL / QPLPre) ==========
            if ($poolOddsType == 'QPL' || $poolOddsType == 'QPLPre') {
                if (!empty($pool['oddsNodes'])) {
                    foreach ($pool['oddsNodes'] as $node) {
                        $combString = $node['combString'];  // 格式如 "1,2"
                        if ($poolOddsType == 'QPLPre') {
                            $horseOdds['quinella_place_pre'][$combString] = $node['oddsValue'] ?? null;
                        } else {
                            $horseOdds['quinella_place_curr'][$combString] = $node['oddsValue'] ?? null;
                        }
                    }
                }
            }
        }
        
        // 为每个 runner 设置默认值
        $result = [];
        
        // 先处理单匹马赔率
        foreach ($horseOdds as $runnerNo => $odds) {
            if (is_array($odds) && (isset($odds['win_pre_odds']) || isset($odds['win_odds']) || isset($odds['place_pre_odds']) || isset($odds['place_odds']))) {
                $result[$runnerNo] = array_merge([
                    'runner_no' => $runnerNo,
                    'win_pre_odds' => null,
                    'win_odds' => null,
                    'win_odds_drop' => null,
                    'place_pre_odds' => null,
                    'place_odds' => null,
                    'place_odds_drop' => null,
                    'quinella_pre_odds' => null,
                    'quinella_odds' => null,
                    'quinella_place_pre_odds' => null,
                    'quinella_place_odds' => null,
                    'is_hot_favourite_pre' => 0,
                    'is_hot_favourite' => 0
                ], $odds);
            }
        }
        
        // 添加连赢赔率（作为额外字段）
        if (!empty($horseOdds['quinella_pre'])) {
            $result['quinella_pre'] = $horseOdds['quinella_pre'];
        }
        if (!empty($horseOdds['quinella_curr'])) {
            $result['quinella_curr'] = $horseOdds['quinella_curr'];
        }
        if (!empty($horseOdds['quinella_place_pre'])) {
            $result['quinella_place_pre'] = $horseOdds['quinella_place_pre'];
        }
        if (!empty($horseOdds['quinella_place_curr'])) {
            $result['quinella_place_curr'] = $horseOdds['quinella_place_curr'];
        }
        
        return $result;
    }
    
    /**
     * 保存所有马匹的赔率明细（补全缺失的 runner）
     */
    /**
     * 保存所有马匹的赔率明细（包含赔率变化值）
     */
    // private function saveAllHorseOddsDetails($oddsDataId, $date, $venueCode, $raceNo, $allRunners, $horseOdds, $capturedAt, $type) {
    //     $detailStmt = $this->db->prepare("
    //         INSERT INTO hkracing_odds_horse_details 
    //         (odds_data_id, race_date, venue_code, race_no, horse_code, horse_name_ch, 
    //          horse_name_en, runner_no, win_pre_odds, win_odds, win_odds_drop,
    //          place_pre_odds, place_odds, place_odds_drop,
    //          quinella_pre_odds, quinella_odds, quinella_place_pre_odds, quinella_place_odds,
    //          is_hot_favourite_pre, is_hot_favourite, odds_type, captured_at)
    //         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    //     ");
        
    //     $savedCount = 0;
    //     foreach ($allRunners as $runner) {
    //         $runnerNo = $runner['runner_no'];
            
    //         // 尝试多种格式匹配
    //         $odds = [];
    //         // 格式1: 原始值 (如 "1")
    //         if (isset($horseOdds[$runnerNo])) {
    //             $odds = $horseOdds[$runnerNo];
    //         }
    //         // 格式2: 带前导零 (如 "01")
    //         elseif (isset($horseOdds[str_pad($runnerNo, 2, '0', STR_PAD_LEFT)])) {
    //             $odds = $horseOdds[str_pad($runnerNo, 2, '0', STR_PAD_LEFT)];
    //         }
    //         // 格式3: 去掉前导零 (如 "1" 从 "01")
    //         elseif (isset($horseOdds[ltrim($runnerNo, '0')])) {
    //             $odds = $horseOdds[ltrim($runnerNo, '0')];
    //         }
            
    //         // 检查是否有有效赔率
    //         $hasValidOdds = (isset($odds['win_pre_odds']) && $odds['win_pre_odds'] !== null) ||
    //                         (isset($odds['win_odds']) && $odds['win_odds'] !== null) ||
    //                         (isset($odds['place_pre_odds']) && $odds['place_pre_odds'] !== null) ||
    //                         (isset($odds['place_odds']) && $odds['place_odds'] !== null);
            
    //         if (!$hasValidOdds) {
    //             continue;
    //         }
            
    //         $detailStmt->execute([
    //             $oddsDataId,
    //             $date,
    //             $venueCode,
    //             $raceNo,
    //             $runner['horse_code'],
    //             $runner['name_ch'],
    //             $runner['name_en'],
    //             $runnerNo,
    //             $odds['win_pre_odds'] ?? null,
    //             $odds['win_odds'] ?? null,
    //             $odds['win_odds_drop'] ?? null,
    //             $odds['place_pre_odds'] ?? null,
    //             $odds['place_odds'] ?? null,
    //             $odds['place_odds_drop'] ?? null,
    //             $odds['quinella_pre_odds'] ?? null,
    //             $odds['quinella_odds'] ?? null,
    //             $odds['quinella_place_pre_odds'] ?? null,
    //             $odds['quinella_place_odds'] ?? null,
    //             $odds['is_hot_favourite_pre'] ?? 0,
    //             $odds['is_hot_favourite'] ?? 0,
    //             $type,
    //             $capturedAt
    //         ]);
    //         $savedCount++;
    //     }
        
    //     return $savedCount;
    // }
    private function saveAllHorseOddsDetails($oddsDataId, $date, $venueCode, $raceNo, $allRunners, $horseOdds, $capturedAt, $type, $finalPositions = []) {
        $detailStmt = $this->db->prepare("
            INSERT INTO hkracing_odds_horse_details 
            (odds_data_id, race_date, venue_code, race_no, horse_code, horse_name_ch, 
             horse_name_en, runner_no, win_pre_odds, win_odds, win_odds_drop,
             place_pre_odds, place_odds, place_odds_drop,
             quinella_pre_odds, quinella_odds, quinella_place_pre_odds, quinella_place_odds,
             is_hot_favourite_pre, is_hot_favourite, odds_type, captured_at, final_position)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $savedCount = 0;
        foreach ($allRunners as $runner) {
            $runnerNo = $runner['runner_no'];
            
            // 尝试多种格式匹配
            $odds = [];
            if (isset($horseOdds[$runnerNo])) {
                $odds = $horseOdds[$runnerNo];
            } elseif (isset($horseOdds[str_pad($runnerNo, 2, '0', STR_PAD_LEFT)])) {
                $odds = $horseOdds[str_pad($runnerNo, 2, '0', STR_PAD_LEFT)];
            } elseif (isset($horseOdds[ltrim($runnerNo, '0')])) {
                $odds = $horseOdds[ltrim($runnerNo, '0')];
            }
            
            // 获取 final_position
            $finalPosition = $finalPositions[$runnerNo] ?? $finalPositions[ltrim($runnerNo, '0')] ?? null;
            
            // 检查是否有有效赔率（如果有 final_position 也保存）
            $hasValidOdds = (isset($odds['win_pre_odds']) && $odds['win_pre_odds'] !== null) ||
                            (isset($odds['win_odds']) && $odds['win_odds'] !== null) ||
                            (isset($odds['place_pre_odds']) && $odds['place_pre_odds'] !== null) ||
                            (isset($odds['place_odds']) && $odds['place_odds'] !== null) ||
                            ($finalPosition !== null && $finalPosition > 0);  // ✅ 有最终名次也保存
            
            if (!$hasValidOdds) {
                continue;
            }
            
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
                $odds['quinella_pre_odds'] ?? null,
                $odds['quinella_odds'] ?? null,
                $odds['quinella_place_pre_odds'] ?? null,
                $odds['quinella_place_odds'] ?? null,
                $odds['is_hot_favourite_pre'] ?? 0,
                $odds['is_hot_favourite'] ?? 0,
                $type,
                $capturedAt,
                $finalPosition
            ]);
            $savedCount++;
        }
        
        return $savedCount;
    }
/**
 * 获取赔率查询 GraphQL
 */
private function getOddsQuery() {
    return <<<'GRAPHQL'
    query racing($date: String, $venueCode: String, $oddsTypes: [OddsType], $raceNo: Int) {
      raceMeetings(date: $date, venueCode: $venueCode) {
        pmPools(oddsTypes: $oddsTypes, raceNo: $raceNo) {
          id
          status
          sellStatus
          oddsType
          lastUpdateTime
          guarantee
          minTicketCost
          name_en
          name_ch
          leg {
            number
            races
          }
          cWinSelections {
            composite
            name_ch
            name_en
            starters
          }
          oddsNodes {
            combString
            oddsValue
            hotFavourite
            oddsDropValue
            bankerOdds {
              combString
              oddsValue
            }
          }
        }
      }
    }
GRAPHQL;
}    
//     /**
//      * 获取赔率查询 GraphQL
//      */
//     private function getOddsQuery() {
//         return <<<'GRAPHQL'
//         query racing($date: String, $venueCode: String, $oddsTypes: [OddsType], $raceNo: Int) {
//           raceMeetings(date: $date, venueCode: $venueCode) {
//             pmPools(oddsTypes: $oddsTypes, raceNo: $raceNo) {
//               id
//               status
//               sellStatus
//               oddsType
//               lastUpdateTime
//               guarantee
//               minTicketCost
//               name_en
//               name_ch
//               leg {
//                 number
//                 races
//               }
//               cWinSelections {
//                 composite
//                 name_ch
//                 name_en
//                 starters
//               }
//               oddsNodes {
//                 combString
//                 oddsValue
//                 hotFavourite
//                 oddsDropValue
//                 bankerOdds {
//                   combString
//                   oddsValue
//                 }
//               }
//             }
//           }
//         }
// GRAPHQL;
//     }
/**
 * 获取赔率查询 GraphQL - 使用已验证的完整查询结构
 */
/**
 * 获取完整的 racing 查询（与白名单匹配）
 */
private function getFullRacingQuery() {
    return <<<'GRAPHQL'
    fragment raceFragment on Race {
      id
      no
      status
      raceName_en
      raceName_ch
      postTime
      country_en
      country_ch
      distance
      wageringFieldSize
      go_en
      go_ch
      ratingType
      raceTrack {
        description_en
        description_ch
      }
      raceCourse {
        description_en
        description_ch
        displayCode
      }
      raceClass_en
      raceClass_ch
      judgeSigns {
        value_en
      }
    }
    
    fragment racingBlockFragment on RaceMeeting {
      jpEsts: pmPools(
        oddsTypes: [TCE, TRI, FF, QTT, DT, TT, SixUP]
        filters: ["jackpot", "estimatedDividend"]
      ) {
        leg {
          number
          races
        }
        oddsType
        jackpot
        estimatedDividend
        mergedPoolId
      }
      poolInvs: pmPools(
        oddsTypes: [WIN, PLA, QIN, QPL, CWA, CWB, CWC, IWN, FCT, TCE, TRI, FF, QTT, DBL, TBL, DT, TT, SixUP]
      ) {
        id
        leg {
          races
        }
      }
      penetrometerReadings(filters: ["first"]) {
        reading
        readingTime
      }
      hammerReadings(filters: ["first"]) {
        reading
        readingTime
      }
      changeHistories(filters: ["top3"]) {
        type
        time
        raceNo
        runnerNo
        horseName_ch
        horseName_en
        jockeyName_ch
        jockeyName_en
        scratchHorseName_ch
        scratchHorseName_en
        handicapWeight
        scrResvIndicator
      }
    }
    
    fragment racingFoPoolFragment on RacingFoPool {
      instNo
      poolId
      oddsType
      status
      sellStatus
      otherSelNo
      inplayUpTo
      expStartDateTime
      expStopDateTime
      raceStopSellNo
      raceStopSellStatus
      includeRaces
      excludeRaces
      lastUpdateTime
      selections {
        order
        number
        code
        name_en
        name_ch
        scheduleRides
        remainingRides
        points
        lineId
        combId
        combStatus
        openOdds
        prevOdds
        currentOdds
        results {
          raceNo
          points
          point1st
          point2nd
          point3rd
          dhRmk1st
          dhRmk2nd
          dhRmk3rd
          count1st
          count2nd
          count3rd
          count4th
          numerator4th
          denominator4th
        }
      }
      otherSelections {
        order
        code
        name_en
        name_ch
        scheduleRides
        remainingRides
        points
        results {
          raceNo
          points
          point1st
          point2nd
          point3rd
          dhRmk1st
          dhRmk2nd
          dhRmk3rd
          count1st
          count2nd
          count3rd
          count4th
          numerator4th
          denominator4th
        }
      }
    }
    
    query racing($date: String, $venueCode: String, $foOddsTypes: [OddsType], $foFilter: [String], $resultOddsType: [OddsType]) {
      timeOffset {
        rc
      }
      activeMeetings: raceMeetings {
        id
        venueCode
        date
        status
        races {
          no
          postTime
          status
          wageringFieldSize
        }
      }
      raceMeetings(date: $date, venueCode: $venueCode) {
        id
        status
        venueCode
        date
        totalNumberOfRace
        currentNumberOfRace
        dateOfWeek
        meetingType
        totalInvestment
        country {
          code
          namech
          nameen
          seq
        }
        races {
          ...raceFragment
          runners {
            id
            no
            standbyNo
            status
            name_ch
            name_en
            horse {
              id
              code
            }
            color
            barrierDrawNumber
            handicapWeight
            currentWeight
            currentRating
            internationalRating
            gearInfo
            racingColorFileName
            allowance
            trainerPreference
            last6run
            saddleClothNo
            trumpCard
            priority
            finalPosition
            deadHeat
            winOdds
            jockey {
              code
              name_en
              name_ch
            }
            trainer {
              code
              name_en
              name_ch
            }
          }
        }
        obSt: pmPools(oddsTypes: [WIN, PLA]) {
          leg {
            races
          }
          oddsType
          comingleStatus
        }
        poolInvs: pmPools(
          oddsTypes: [WIN, PLA, QIN, QPL, CWA, CWB, CWC, IWN, FCT, TCE, TRI, FF, QTT, DBL, TBL, DT, TT, SixUP]
        ) {
          id
          leg {
            number
            races
          }
          status
          sellStatus
          oddsType
          investment
          mergedPoolId
          lastUpdateTime
        }
        resPools: pmPools(oddsTypes: $resultOddsType) {
          leg {
            number
            races
          }
          status
          oddsType
          name_en
          name_ch
          lastUpdateTime
          dividends(officialOnly: true) {
            winComb
            type
            div
            seq
            status
            guarantee
            partial
            partialUnit
          }
          cWinSelections {
            composite
            name_ch
            name_en
            starters
          }
        }
        ...racingBlockFragment
        pmPools(oddsTypes: []) {
          id
        }
        foPools(oddsTypes: $foOddsTypes, filters: $foFilter) {
          ...racingFoPoolFragment
        }
        jkcInstNo: foPools(oddsTypes: [JKC], filters: ["top"]) {
          instNo
        }
        tncInstNo: foPools(oddsTypes: [TNC], filters: ["top"]) {
          instNo
        }
      }
    }
GRAPHQL;
} 
    /**
     * 执行 GraphQL 请求
     */
    private function executeGraphQL($query, $variables = []) {
        $payload = json_encode(['query' => $query, 'variables' => $variables]);
        
        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept-Encoding: gzip, deflate'
        ]);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('CURL错误: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("API返回HTTP状态码: $httpCode");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * 获取赔率历史（修复版）
     */
    public function getOddsHistory($date, $venueCode, $raceNo, $limit = 100) {
        // 先获取 race_id
        $stmt = $this->db->prepare("
            SELECT r.id 
            FROM hkracing_races r
            JOIN hkracing_meetings m ON r.meeting_id = m.id
            WHERE m.date = ? AND m.venue_code = ? AND r.race_no = ?
        ");
        $stmt->execute([$date, $venueCode, $raceNo]);
        $race = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$race) {
            return [];
        }
        
        $raceId = $race['id'];
        
        // 查询赔率历史（不使用 GROUP BY，直接查询）
        $stmt = $this->db->prepare("
            SELECT 
                od.id,
                od.odds_type,
                od.captured_at,
                od.horse_odds
            FROM hkracing_odds_data od
            WHERE od.race_date = ? 
              AND od.venue_code = ? 
              AND od.race_no = ?
            ORDER BY od.captured_at DESC
            LIMIT ?
        ");
        $stmt->execute([$date, $venueCode, $raceNo, $limit]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 单独获取每个快照的马匹数量
        foreach ($results as &$row) {
            $stmt2 = $this->db->prepare("
                SELECT COUNT(*) as horse_count 
                FROM hkracing_odds_horse_details 
                WHERE odds_data_id = ?
            ");
            $stmt2->execute([$row['id']]);
            $row['horse_count'] = $stmt2->fetchColumn();
        }
        
        return $results;
    }
    
    /**
     * 获取某匹马在某场赛事的赔率变化
     */
    public function getHorseOddsTrend($horseCode, $date, $venueCode, $raceNo) {
        $stmt = $this->db->prepare("
            SELECT 
                ohd.captured_at,
                ohd.win_odds,
                ohd.place_odds,
                od.odds_type
            FROM hkracing_odds_horse_details ohd
            JOIN hkracing_odds_data od ON ohd.odds_data_id = od.id
            WHERE ohd.horse_code = ?
              AND ohd.race_date = ?
              AND ohd.venue_code = ?
              AND ohd.race_no = ?
            ORDER BY ohd.captured_at ASC
        ");
        $stmt->execute([$horseCode, $date, $venueCode, $raceNo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
 * 更新马匹的最终名次
 * @param string $date 日期
 * @param string $venueCode 场地
 * @param int $raceNo 场次
 * @return int 更新的记录数
 */
// public function updateFinalPositions($date, $venueCode, $raceNo) {
//     // 从 API 获取赛事结果
//     $query = $this->getFullRacingQuery();
//     $variables = [
//         'date' => $date,
//         'venueCode' => $venueCode,
//         'foOddsTypes' => [],
//         'foFilter' => [],
//         'resultOddsType' => ['WIN', 'PLA', 'QIN', 'QPL']
//     ];
    
//     $data = $this->executeGraphQL($query, $variables);
    
//     if (!$data || empty($data['data']['raceMeetings'][0]['races'])) {
//         return 0;
//     }
    
//     // 找到对应的场次
//     $targetRace = null;
//     foreach ($data['data']['raceMeetings'][0]['races'] as $race) {
//         if ($race['no'] == $raceNo) {
//             $targetRace = $race;
//             break;
//         }
//     }
    
//     if (!$targetRace || empty($targetRace['runners'])) {
//         return 0;
//     }
    
//     // 更新 final_position
//     $updated = 0;
//     $stmt = $this->db->prepare("
//         UPDATE hkracing_odds_horse_details 
//         SET final_position = ?
//         WHERE race_date = ? AND venue_code = ? AND race_no = ? AND runner_no = ?
//     ");
    
//     foreach ($targetRace['runners'] as $runner) {
//         if (isset($runner['finalPosition']) && $runner['finalPosition'] > 0) {
//             $stmt->execute([
//                 $runner['finalPosition'],
//                 $date,
//                 $venueCode,
//                 $raceNo,
//                 $runner['no']
//             ]);
//             $updated += $stmt->rowCount();
//         }
//     }
    
//     return $updated;
// }
/**
 * 更新最终名次（比赛结束后调用）
 * @param string $date 日期
 * @param string $venueCode 场地
 * @param int $raceNo 场次
 * @return int 更新的记录数
 */
/**
 * 更新最终名次（比赛结束后调用）
 * 同时更新 hkracing_odds_horse_details、hkracing_runners 和 hkracing_v2_score
 */
public function updateFinalPositions($date, $venueCode, $raceNo) {
    // 使用完整查询获取包含 finalPosition 的数据
    $query = $this->getFullRacingQuery();
    $variables = [
        'date' => $date,
        'venueCode' => $venueCode,
        'foOddsTypes' => [],
        'foFilter' => [],
        'resultOddsType' => ['WIN', 'PLA', 'QIN', 'QPL']
    ];
    
    $data = $this->executeGraphQL($query, $variables);
    
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
    
    // 更新 hkracing_odds_horse_details
    $stmt1 = $this->db->prepare("
        UPDATE hkracing_odds_horse_details 
        SET final_position = ?, captured_at = NOW()
        WHERE race_date = ? AND venue_code = ? AND race_no = ? AND runner_no = ?
    ");
    
    // 更新 hkracing_runners
    $stmt2 = $this->db->prepare("
        UPDATE hkracing_runners 
        SET final_position = ?, win_odds = ?, updated_at = NOW()
        WHERE race_id = ? AND runner_no = ?
    ");
    
    // ✅ 更新 hkracing_v2_score（新增）
    $stmt3 = $this->db->prepare("
        UPDATE hkracing_v2_score 
        SET final_position = ?, final_win_odds = ?, last_updated = NOW()
        WHERE race_id = ? AND runner_no = ? AND score_version = 2
    ");
    
    $updatedOdds = 0;
    $updatedRunners = 0;
    $updatedScores = 0;
    
    foreach ($targetRace['runners'] as $runner) {
        $runnerNo = $runner['no'];
        $finalPosition = $runner['finalPosition'] ?? null;
        $winOdds = $runner['winOdds'] ?? null;
        
        if ($finalPosition !== null && $finalPosition > 0) {
            // 1. 更新赔率明细表
            $stmt1->execute([$finalPosition, $date, $venueCode, $raceNo, $runnerNo]);
            $updatedOdds += $stmt1->rowCount();
            
            // 2. 更新 runners 表
            if ($raceId) {
                $stmt2->execute([$finalPosition, $winOdds, $raceId, $runnerNo]);
                $updatedRunners += $stmt2->rowCount();
            }
            
            // 3. ✅ 更新 v2_score 表（Version 2 的记录）
            if ($raceId) {
                $stmt3->execute([$finalPosition, $winOdds, $raceId, $runnerNo]);
                $updatedScores += $stmt3->rowCount();
            }
        }
    }
    
    echo "更新完成 - 赔率表: {$updatedOdds}, runners表: {$updatedRunners}, v2_score表: {$updatedScores}\n";
    
    return $updatedOdds + $updatedRunners + $updatedScores;
}


}
?>