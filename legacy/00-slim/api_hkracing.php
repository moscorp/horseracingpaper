<?php
// api.php - 添加新的 getRacingDataAllComing 接口

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'HKJCRacingDataManager.php';
include_once ("lib/func_mailer_gmail.php");
include_once ("lib/constants.php");

// 设置 JSON 编码选项：不转义 Unicode，输出原始中文
// JSON_UNESCAPED_UNICODE 让中文正常显示
// JSON_UNESCAPED_SLASHES 让斜杠不转义
json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

try {
    $manager = new HKJCRacingDataManager(
        $dbConfig['host'],
        $dbConfig['name'],
        $dbConfig['user'],
        $dbConfig['pass']
    );
    
    // 注意：这里支持大小写不敏感的 action 匹配
    $action = $_GET['action'] ?? '';
    
    // 兼容大小写：统一转为小写后比较，但保留原始值用于某些需要区分的场景
    $actionLower = strtolower($action);
    
    $date = $_GET['date'] ?? null;
    $venue = $_GET['venue'] ?? null;
    $raceNo = $_GET['race_no'] ?? null;
    $forceRefresh = isset($_GET['refresh']) && ($_GET['refresh'] === 'true' || $_GET['refresh'] === '1');
    try {
        $pdo = new PDO(
            "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
            $dbConfig['user'],
            $dbConfig['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $pdo->exec("SET time_zone = '+08:00'");
    } catch (Exception $e) {
        error_log("PDO连接失败: " . $e->getMessage());
    }

    switch ($actionLower) {
        // ========== 新增：获取所有即将到来的完整赛马数据 ==========
    // ========== 获取所有即将到来的完整赛马数据 ==========
    case 'getracingdataallcoming':
    case 'getRacingDataAllComing':
    case 'getracingdataallcoming':
        
        $cacheKey = 'all_coming_racing_data';
        $collectHorses = !isset($_GET['collect_horses']) || $_GET['collect_horses'] !== 'false';
        $includeHistory = isset($_GET['include_history']) && $_GET['include_history'] !== 'false';
        
        // 尝试从缓存获取
        if (!$forceRefresh) {
            $cachedData = $manager->getDBCache($cacheKey);
            if ($cachedData) {
                // 如果需要历史数据但缓存中没有，补充历史数据
                if ($includeHistory && !isset($cachedData['horse_histories'])) {
                    // 收集香港马匹代码
                    $hkHorseCodes = [];
                    if (!empty($cachedData['raceMeetings'])) {
                        foreach ($cachedData['raceMeetings'] as $meeting) {
                            if ($meeting['venueCode'] == 'ST' || $meeting['venueCode'] == 'HV') {
                                foreach ($meeting['races'] as $race) {
                                    foreach ($race['runners'] as $runner) {
                                        $horseCode = $runner['horse_code'] ?? '';
                                        if ($horseCode && preg_match('/^[A-Z]\d{3}$/', $horseCode)) {
                                            $hkHorseCodes[] = $horseCode;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    $hkHorseCodes = array_unique($hkHorseCodes);
                    $hkHorseCodes = array_values($hkHorseCodes);
                    
                    // 关键：只有在有马匹代码时才查询
                    if (!empty($hkHorseCodes)) {
                        try {
                            $pdo = new PDO(
                                "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
                                $dbConfig['user'],
                                $dbConfig['pass'],
                                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                            );
// echo ("After reindex - First key: " . key($hkHorseCodes));
// echo ("Count: " . count($hkHorseCodes));
// exit;
                            $placeholders = implode(',', array_fill(0, count($hkHorseCodes), '?'));
                            $stmt = $pdo->prepare("
                                SELECT 
                                    horse_code, season, race_no, finishing_position, race_date,
                                    venue_code, distance, going, class, jockey_name_ch,
                                    margin, win_odds, actual_weight, running_position,
                                    finishing_time, gear
                                FROM hkracing_horse_performances 
                                WHERE horse_code IN ({$placeholders})
                                ORDER BY race_date DESC
                            ");
                            $stmt->execute($hkHorseCodes);
                            
                            $histories = [];
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $code = $row['horse_code'];
                                if (!isset($histories[$code])) {
                                    $histories[$code] = [];
                                }
                                if (count($histories[$code]) < 10) {
                                    $histories[$code][] = $row;
                                }
                            }
                            // print_r($histories);
                            $cachedData['horse_histories'] = $histories;
                            $cachedData['history_summary'] = [
                                'total_hk_horses' => count($hkHorseCodes),
                                'horses_with_history' => count($histories),
                                'limit_per_horse' => 10
                            ];
                        } catch (Exception $e) {
                            $cachedData['history_error'] = $e->getMessage();
                        }
                    } else {
                        $cachedData['history_summary'] = [
                            'message' => '当前赛事中没有香港马匹',
                            'total_hk_horses' => 0
                        ];
                    }
                }
                // print_r($cachedData);
                // exit;
                echo json_encode([
                    'success' => true,
                    'code' => 200,
                    'data' => $cachedData,
                    'source' => 'cache',
                    'cached_at' => date('Y-m-d H:i:s')
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
        }
        
        // 1. 获取所有活跃赛马日的基本信息
        // $activeMeetings = $manager->getActiveMeetingsOnly();
        // 1. 直接从数据库获取未来7天的香港赛事
        try {
            // 确保 PDO 已初始化
            if (!isset($pdo)) {
                $pdo = new PDO(
                    "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
                    $dbConfig['user'],
                    $dbConfig['pass'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                $pdo->exec("SET time_zone = '+08:00'");
            }
            
            // $stmt = $pdo->prepare("
            //     SELECT DISTINCT 
            //         m.date,
            //         m.venue_code as venueCode,
            //         'DEFINED' as status,
            //         m.id
            //     FROM hkracing_meetings m
            //     WHERE m.date >= CURDATE() 
            //       AND m.date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            //       AND m.venue_code IN ('ST', 'HV')
            //     ORDER BY m.date
            // ");
$hkNow = new DateTime('now', new DateTimeZone('Asia/Hong_Kong'));
$today = $hkNow->format('Y-m-d');

$stmt = $pdo->prepare("
    SELECT DISTINCT 
        m.date,
        m.venue_code as venueCode,
        m.status,
        m.id
    FROM hkracing_meetings m
    WHERE STR_TO_DATE(m.date, '%Y-%m-%d') >= STR_TO_DATE(?, '%Y-%m-%d')
      AND m.venue_code IN ('ST', 'HV')
    ORDER BY m.date
");
$stmt->execute([$today]);
$activeMeetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // $stmt->execute();
            // $activeMeetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 为每个 meeting 添加空的 races 数组（保持格式一致）
            foreach ($activeMeetings as &$meeting) {
                $meeting['races'] = [];
            }
            
            echo "从数据库获取到 " . count($activeMeetings) . " 个赛马日\n";
            
        } catch (Exception $e) {
            echo "错误: " . $e->getMessage() . "\n";
            $activeMeetings = [];
        }

        
        if (empty($activeMeetings)) {
            echo json_encode([
                'success' => true,
                'code' => 200,
                'data' => [
                    'raceMeetings' => [],
                    'total_meetings' => 0,
                    'message' => '当前没有即将到来的赛马日'
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        
        $allDetailedData = [];
        $syncStatus = [];
        
        // 初始化批量管理器
        if ($collectHorses) {
            require_once 'HKJCHorseHistoryBatchManager.php';
            $batchManager = new HKJCHorseHistoryBatchManager(
                $dbConfig['host'],
                $dbConfig['name'],
                $dbConfig['user'],
                $dbConfig['pass']
            );
            $collectedHorses = [];
        }
        
        // 2. 遍历每个活跃赛马日
        foreach ($activeMeetings as $meeting) {
            $date = $meeting['date'];
            $venue = $meeting['venueCode'];
            $meetingId = $meeting['id'];
            
            $dbData = $manager->getJoinedMeetingData($date, $venue);
            
            if (!empty($dbData)) {
                $meetingData = [
                    'id' => $dbData[0]['id'],
                    'venueCode' => $dbData[0]['venue_code'],
                    'date' => $dbData[0]['date'],
                    'status' => $dbData[0]['status'],
                    'totalNumberOfRace' => $dbData[0]['total_number_of_race'],
                    'races' => $dbData[0]['races']
                ];
                $allDetailedData[] = $meetingData;
                $syncStatus[$meetingId] = 'from_database';
                
                // 收集马匹
                if ($collectHorses && !empty($meetingData['races'])) {
                    foreach ($meetingData['races'] as $race) {
                        foreach ($race['runners'] as $runner) {
                            $horseCode = $runner['horse_code'] ?? '';
                            if ($horseCode && preg_match('/^[A-Z]\d{3}$/', $horseCode)) {
                                if (!in_array($horseCode, $collectedHorses)) {
                                    $batchManager->addToQueue($horseCode, $runner['name_ch'] ?? '');
                                    $collectedHorses[] = $horseCode;
                                }
                            }
                        }
                    }
                }
            } else {
                $apiData = $manager->getRacingData($date, $venue, null, $forceRefresh);
                if ($apiData && isset($apiData['data']['raceMeetings'][0])) {
                    $meetingData = $apiData['data']['raceMeetings'][0];
                    $allDetailedData[] = $meetingData;
                    $syncStatus[$meetingId] = 'from_api';
                    
                    if ($collectHorses && !empty($meetingData['races'])) {
                        foreach ($meetingData['races'] as $race) {
                            foreach ($race['runners'] as $runner) {
                                $horseCode = $runner['horse']['code'] ?? '';
                                if ($horseCode && preg_match('/^[A-Z]\d{3}$/', $horseCode)) {
                                    if (!in_array($horseCode, $collectedHorses)) {
                                        $batchManager->addToQueue($horseCode, $runner['name_ch'] ?? '');
                                        $collectedHorses[] = $horseCode;
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $syncStatus[$meetingId] = 'failed';
                }
            }
            
            usleep(100000);
        }
        
        // 3. 准备返回数据
        $resultData = [
            'raceMeetings' => $allDetailedData,
            'total_meetings' => count($allDetailedData),
            'sync_status' => $syncStatus,
            'generated_at' => date('Y-m-d H:i:s')
        ];
        
        // 添加马匹收集统计
        if ($collectHorses && isset($collectedHorses)) {
            $resultData['horse_collection'] = [
                'collected_count' => count($collectedHorses),
                'queue_status' => $batchManager->getQueueStats()
            ];
        }
        
        // 4. 添加赔率数据（先执行，确保 runner 有赔率字段）
        // 在 getRacingDataAllComing 中，添加赔率数据
        $includeOdds = isset($_GET['include_odds']) && $_GET['include_odds'] !== 'false';
        
        if ($includeOdds && !empty($resultData['raceMeetings']) && is_array($resultData['raceMeetings'])) {
            // 收集所有需要查询的日期
            $dates = array_values(array_unique(array_column($resultData['raceMeetings'], 'date')));
        
            if (!empty($dates)) {
                $placeholders = implode(',', array_fill(0, count($dates), '?'));
                
                // ========== 1. 获取 Pre 赔率（赛前） ==========
                $preStmt = $pdo->prepare("
                    SELECT hd1.*
                    FROM hkracing_odds_horse_details hd1
                    INNER JOIN (
                        SELECT race_date, venue_code, race_no, runner_no, MAX(id) as max_id
                        FROM hkracing_odds_horse_details
                        WHERE race_date IN ({$placeholders}) AND odds_type = 'Pre'
                        GROUP BY race_date, venue_code, race_no, runner_no
                    ) hd2 ON hd1.id = hd2.max_id
                ");
                $preStmt->execute($dates);
                $preOdds = [];
                while ($row = $preStmt->fetch(PDO::FETCH_ASSOC)) {
                    $key = $row['race_date'] . '|' . $row['venue_code'] . '|' . $row['race_no'] . '|' . $row['runner_no'];
                    $preOdds[$key] = $row;
                }
                
                // ========== 2. 获取 Curr 赔率（当前） ==========
                $currStmt = $pdo->prepare("
                    SELECT hd1.*
                    FROM hkracing_odds_horse_details hd1
                    INNER JOIN (
                        SELECT race_date, venue_code, race_no, runner_no, MAX(id) as max_id
                        FROM hkracing_odds_horse_details
                        WHERE race_date IN ({$placeholders}) AND odds_type = 'Curr'
                        GROUP BY race_date, venue_code, race_no, runner_no
                    ) hd2 ON hd1.id = hd2.max_id
                ");
                $currStmt->execute($dates);
                $currOdds = [];
                while ($row = $currStmt->fetch(PDO::FETCH_ASSOC)) {
                    $key = $row['race_date'] . '|' . $row['venue_code'] . '|' . $row['race_no'] . '|' . $row['runner_no'];
                    $currOdds[$key] = $row;
                }
                
                // ========== 3. 获取赔率历史趋势 ==========
                // 获取赔率历史趋势（同时收集 win_odds 和 place_odds）
                // 获取赔率历史趋势（同时收集 win_odds 和 place_odds 的所有历史）
                $stmt = $pdo->prepare("
                    SELECT 
                        race_date, venue_code, race_no, runner_no, 
                        win_odds, place_odds, captured_at
                    FROM hkracing_odds_horse_details
                    WHERE race_date IN ({$placeholders})
                      AND odds_type = 'Curr'
                      AND (win_odds IS NOT NULL OR place_odds IS NOT NULL)
                    ORDER BY captured_at ASC
                ");
                $stmt->execute($dates);
                
                $winHistoryOdds = [];
                $placeHistoryOdds = [];
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $key = $row['race_date'] . '|' . $row['venue_code'] . '|' . $row['race_no'] . '|' . $row['runner_no'];
                    
                    // 收集独赢赔率历史（保留所有值，不去重）
                    if ($row['win_odds'] !== null) {
                        if (!isset($winHistoryOdds[$key])) {
                            $winHistoryOdds[$key] = [];
                        }
                        $winHistoryOdds[$key][] = floatval($row['win_odds']);
                    }
                    
                    // 收集位置赔率历史
                    if ($row['place_odds'] !== null) {
                        if (!isset($placeHistoryOdds[$key])) {
                            $placeHistoryOdds[$key] = [];
                        }
                        $placeHistoryOdds[$key][] = floatval($row['place_odds']);
                    }
                }
                
                // ========== 4. 初始化所有 runner 的赔率字段 ==========
                foreach ($resultData['raceMeetings'] as &$meeting) {
                    if (!is_array($meeting) || !isset($meeting['races'])) continue;
                    
                    foreach ($meeting['races'] as &$race) {
                        if (!is_array($race) || !isset($race['runners'])) continue;
                        
                        foreach ($race['runners'] as &$runner) {
                            if (!is_array($runner)) continue;
                            
                            $runnerNo = trim($runner['runner_no'] ?? '');
                            
                            // 后备马设置默认值
                            if ($runnerNo === '') {
                                $runner['win_pre_odds'] = null;
                                $runner['win_curr_odds'] = null;
                                $runner['place_pre_odds'] = null;
                                $runner['place_curr_odds'] = null;
                                $runner['quinella_pre_odds'] = null;
                                $runner['quinella_odds'] = null;
                                $runner['quinella_place_pre_odds'] = null;
                                $runner['quinella_place_odds'] = null;
                                $runner['is_hot_favourite_pre'] = 0;
                                $runner['is_hot_favourite'] = 0;
                                $runner['prop_pre'] = null;
                                $runner['prop_curr'] = null;
                                $runner['win_curr_trend'] = [];
                                $runner['place_curr_trend'] = [];
                                continue;
                            }
                            
                            $key = $meeting['date'] . '|' . $meeting['venueCode'] . '|' . $race['race_no'] . '|' . $runnerNo;
                            
                            // ========== 设置 Pre 赔率 ==========
                            if (isset($preOdds[$key])) {
                                $p = $preOdds[$key];
                                $runner['win_pre_odds'] = $p['win_pre_odds'] !== null ? floatval($p['win_pre_odds']) : null;
                                $runner['place_pre_odds'] = $p['place_pre_odds'] !== null ? floatval($p['place_pre_odds']) : null;
                                $runner['quinella_pre_odds'] = $p['quinella_pre_odds'] !== null ? floatval($p['quinella_pre_odds']) : null;
                                $runner['quinella_place_pre_odds'] = $p['quinella_place_pre_odds'] !== null ? floatval($p['quinella_place_pre_odds']) : null;
                                $runner['is_hot_favourite_pre'] = intval($p['is_hot_favourite_pre'] ?? 0);
                            } else {
                                $runner['win_pre_odds'] = null;
                                $runner['place_pre_odds'] = null;
                                $runner['quinella_pre_odds'] = null;
                                $runner['quinella_place_pre_odds'] = null;
                                $runner['is_hot_favourite_pre'] = 0;
                            }
                            
                            // ========== 设置 Curr 赔率 ==========
                            if (isset($currOdds[$key])) {
                                $c = $currOdds[$key];
                                $runner['win_curr_odds'] = $c['win_odds'] !== null ? floatval($c['win_odds']) : null;
                                $runner['place_curr_odds'] = $c['place_odds'] !== null ? floatval($c['place_odds']) : null;
                                $runner['win_odds_drop'] = $c['win_odds_drop'] !== null ? floatval($c['win_odds_drop']) : null;  // 新增
                                $runner['place_odds_drop'] = $c['place_odds_drop'] !== null ? floatval($c['place_odds_drop']) : null;  // 新增
                                $runner['quinella_odds'] = $c['quinella_odds'] !== null ? floatval($c['quinella_odds']) : null;
                                $runner['quinella_place_odds'] = $c['quinella_place_odds'] !== null ? floatval($c['quinella_place_odds']) : null;
                                $runner['is_hot_favourite'] = intval($c['is_hot_favourite'] ?? 0);
                                
                                // 计算 Curr 比例
                                if ($runner['win_curr_odds'] !== null && $runner['win_curr_odds'] > 0 && $runner['place_curr_odds'] !== null) {
                                    $runner['prop_curr'] = (int)round(($runner['place_curr_odds'] / $runner['win_curr_odds']) * 100, 2);
                                } else {
                                    $runner['prop_curr'] = null;
                                }
                                
                                // 赔率趋势
                                // $runner['win_curr_trend'] = $historyOdds[$key] ?? [$runner['win_curr_odds']];
                                // $runner['place_curr_trend'] = [];
                                $runner['win_curr_trend'] = $winHistoryOdds[$key] ?? [$runner['win_curr_odds']];
                                $runner['place_curr_trend'] = $placeHistoryOdds[$key] ?? [$runner['place_curr_odds']];
                                
                            } else {
                                $runner['win_curr_odds'] = null;
                                $runner['place_curr_odds'] = null;
                                $runner['quinella_odds'] = null;
                                $runner['quinella_place_odds'] = null;
                                $runner['is_hot_favourite'] = 0;
                                $runner['prop_curr'] = null;
                                $runner['win_curr_trend'] = [];
                                $runner['place_curr_trend'] = [];
                            }
                            
                            // ========== 计算 Pre 比例 ==========
                            if ($runner['win_pre_odds'] !== null && $runner['win_pre_odds'] > 0 && $runner['place_pre_odds'] !== null) {
                                $runner['prop_pre'] = (int)round(($runner['place_pre_odds'] / $runner['win_pre_odds']) * 100, 2);
                            } else {
                                $runner['prop_pre'] = null;
                            }
                        }
                    }
                }
                
                $resultData['odds_summary'] = [
                    'included' => true,
                    'message' => '已附加赔率数据（包含 Pre/Curr、趋势和比例）'
                ];
            }
        }
        // 在 getRacingDataAllComing 中，添加实时赔率获取
        $getLiveOdds = isset($_GET['getLiveOdds']) && $_GET['getLiveOdds'] !== 'false';
        
        if ($getLiveOdds && !empty($resultData['raceMeetings'])) {
            // 直接从 GraphQL API 获取实时赔率
            require_once 'HKJCOddsManager.php';
            $oddsManager = new HKJCOddsManager(
                $dbConfig['host'],
                $dbConfig['name'],
                $dbConfig['user'],
                $dbConfig['pass']
            );
            
            foreach ($resultData['raceMeetings'] as &$meeting) {
                foreach ($meeting['races'] as &$race) {
                    // 获取实时赔率（Curr 类型）
                    $liveResult = $oddsManager->fetchOdds($meeting['date'], $meeting['venueCode'], $race['race_no'], 'Curr', true);
                    
                    if (isset($liveResult['success']) && $liveResult['success']) {
                        // 从数据库获取刚保存的赔率明细（不查询 drop 字段）
                        // $stmt = $pdo->prepare("
                        //     SELECT runner_no, win_odds, place_odds, is_hot_favourite
                        //     FROM hkracing_odds_horse_details 
                        //     WHERE odds_data_id = ?
                        // ");
                        $stmt = $pdo->prepare("
                            SELECT runner_no, win_odds, place_odds, win_odds_drop, place_odds_drop, is_hot_favourite
                            FROM hkracing_odds_horse_details 
                            WHERE odds_data_id = ?
                        ");
                        $stmt->execute([$liveResult['odds_data_id']]);
                        $liveOdds = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // 构建 runner_no 到赔率的映射
                        $oddsMap = [];
                        foreach ($liveOdds as $odds) {
                            $oddsMap[$odds['runner_no']] = $odds;
                        }
                        
                        // 更新 runners 中的赔率字段
                        foreach ($race['runners'] as &$runner) {
                            $runnerNo = trim($runner['runner_no'] ?? '');
                            if ($runnerNo !== '' && isset($oddsMap[$runnerNo])) {
                                $o = $oddsMap[$runnerNo];
                                $runner['win_live_odds'] = $o['win_odds'];
                                $runner['place_live_odds'] = $o['place_odds'];
                                $runner['is_hot_favourite'] = $o['is_hot_favourite'];
                                $runner['live_odds_time'] = $liveResult['captured_at'];
                            } else {
                                $runner['win_live_odds'] = null;
                                $runner['place_live_odds'] = null;
                                $runner['live_odds_time'] = null;
                            }
                        }
                    }
                }
            }
            
            $resultData['live_odds_summary'] = [
                'included' => true,
                'captured_at' => date('Y-m-d H:i:s'),
                'message' => '已从 GraphQL API 获取实时赔率'
            ];
        }
        // ========== 新增：添加赔率数据 ==========
        $includeHistory = isset($_GET['include_history']) && $_GET['include_history'] !== 'false';
        
        // 4. 添加香港马匹历史战绩
        // 4. 添加香港马匹历史战绩（如果请求）
        if ($includeHistory) {
            $hkHorseCodes = [];
                foreach ($resultData['raceMeetings'] as $meeting) {  // 使用 $resultData 而不是 $allDetailedData
                    if ($meeting['venueCode'] == 'ST' || $meeting['venueCode'] == 'HV') {
                        foreach ($meeting['races'] as $race) {
                            foreach ($race['runners'] as $runner) {
                                $horseCode = $runner['horse_code'] ?? '';
                                if ($horseCode && preg_match('/^[A-Z]\d{3}$/', $horseCode)) {
                                    $hkHorseCodes[] = $horseCode;
                                }
                            }
                        }
                    }
                }
            
            $hkHorseCodes = array_values(array_unique($hkHorseCodes));

            if (!empty($hkHorseCodes)) {
                try {
                    $pdo = new PDO(
                        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
                        $dbConfig['user'],
                        $dbConfig['pass'],
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                    );
                    
                    // 分批查询，避免参数过多（252个参数可能太多）
                    $batchSize = 50;
                    $allHistories = [];
                    
                    for ($i = 0; $i < count($hkHorseCodes); $i += $batchSize) {
                        $batch = array_slice($hkHorseCodes, $i, $batchSize);
                        $placeholders = implode(',', array_fill(0, count($batch), '?'));
                        
                        $sql = "
                            SELECT 
                                horse_code, season, race_no, finishing_position, race_date,
                                venue_code, distance, going, class, jockey_name_ch,
                                margin, win_odds, actual_weight, running_position,
                                finishing_time, gear
                            FROM hkracing_horse_performances 
                            WHERE horse_code IN ({$placeholders})
                            ORDER BY race_date DESC
                        ";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($batch);
                        
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $code = $row['horse_code'];
                            if (!isset($allHistories[$code])) {
                                $allHistories[$code] = [];
                            }
                            if (count($allHistories[$code]) < 10) {
                                $allHistories[$code][] = $row;
                            }
                        }
                    }
                    
                    $resultData['horse_histories'] = $allHistories;
                    $resultData['history_summary'] = [
                        'total_hk_horses' => count($hkHorseCodes),
                        'horses_with_history' => count($allHistories),
                        'limit_per_horse' => 10
                    ];
                    
                } catch (Exception $e) {
                    // 详细记录错误
                    error_log("History query error: " . $e->getMessage());
                    error_log("Error code: " . $e->getCode());
                    error_log("SQL State: " . ($e->errorInfo[0] ?? 'unknown'));
                    $resultData['history_error'] = $e->getMessage();
                }
            } else {
                $resultData['history_summary'] = [
                    'message' => '当前赛事中没有香港马匹',
                    'total_hk_horses' => 0
                ];
            }
        }

        // 5. 缓存结果
        // $manager->setDBCache($cacheKey, $resultData, 600);
        
        // 去重 - 基于 id 字段
        $uniqueMeetings = [];
        $seenIds = [];
        foreach ($resultData['raceMeetings'] as $meeting) {
            $id = $meeting['id'] ?? $meeting['id'] ?? null;
            if ($id && !isset($seenIds[$id])) {
                $seenIds[$id] = true;
                $uniqueMeetings[] = $meeting;
            }
        }
        $resultData['raceMeetings'] = $uniqueMeetings;
        $resultData['total_meetings'] = count($uniqueMeetings);
        
        echo json_encode([
            'success' => true,
            'code' => 200,
            'data' => $resultData,
            'source' => 'fresh'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;
            
        // ========== 原有的 getRacingData 接口 ==========
        case 'getracingdata':  // 支持小写
        case 'getRacingData':  // 原大小写
            if (!$date && !$venue) {
                $result = $manager->getActiveMeetingsOnly();
                echo json_encode([
                    'success' => true,
                    'code' => 200,
                    'data' => [
                        'activeMeetings' => $result,
                        'total' => count($result)
                    ]
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } elseif ($date && $venue && $raceNo) {
                $meetingData = $manager->getJoinedMeetingData($date, $venue, $raceNo);
                
                if (!empty($meetingData) && !empty($meetingData[0]['races'])) {
                    $targetRace = null;
                    foreach ($meetingData[0]['races'] as $race) {
                        if ($race['race_no'] == $raceNo) {
                            $targetRace = $race;
                            break;
                        }
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'code' => 200,
                        'data' => [
                            'meeting' => [
                                'id' => $meetingData[0]['id'],
                                'venueCode' => $meetingData[0]['venue_code'],
                                'date' => $meetingData[0]['date'],
                                'status' => $meetingData[0]['status']
                            ],
                            'race' => $targetRace
                        ]
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    echo json_encode([
                        'success' => false,
                        'code' => 404,
                        'error' => '未找到指定的赛事'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            } elseif ($date && $venue) {
                $dbData = $manager->getJoinedMeetingData($date, $venue);
                
                if (!empty($dbData)) {
                    $meeting = $dbData[0];
                    echo json_encode([
                        'success' => true,
                        'code' => 200,
                        'data' => [
                            'raceMeetings' => [
                                [
                                    'id' => $meeting['id'],
                                    'venueCode' => $meeting['venue_code'],
                                    'date' => $meeting['date'],
                                    'status' => $meeting['status'],
                                    'totalNumberOfRace' => $meeting['total_number_of_race'],
                                    'races' => $meeting['races']
                                ]
                            ]
                        ],
                        'source' => 'database'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    $apiData = $manager->getRacingData($date, $venue, null, $forceRefresh);
                    if ($apiData && !isset($apiData['errors'])) {
                        echo json_encode([
                            'success' => true,
                            'code' => 200,
                            'data' => $apiData['data'],
                            'source' => 'api'
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'code' => 500,
                            'error' => '无法获取数据'
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'code' => 400,
                    'error' => '参数不完整。请提供 date 和 venue，或都不提供'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            break;
            
        // ========== 获取当前活跃赛马日 ==========
        case 'getactivemeeting':
        case 'getActiveMeeting':
            $result = $manager->getCurrentActiveMeeting();
            echo json_encode([
                'success' => true,
                'code' => 200,
                'data' => $result
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
            
        // ========== 获取所有活跃赛马日 ==========
        case 'getactivemeetings':
        case 'getActiveMeetings':
            $result = $manager->getActiveMeetingsOnly();
            echo json_encode([
                'success' => true,
                'code' => 200,
                'data' => $result
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
            
        // ========== 从API获取并存储 ==========
        case 'fetchandstore':
        case 'fetchAndStore':
            if (!$date || !$venue) {
                echo json_encode([
                    'success' => false,
                    'code' => 400,
                    'error' => '请提供 date 和 venue 参数'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            }
            $result = $manager->getRacingData($date, $venue, null, $forceRefresh);
            echo json_encode([
                'success' => true,
                'code' => 200,
                'data' => $result
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            // 在 getRacingDataAllComing 或 fetchAndStore 成功后添加
            if (!empty($meetingData['races'])) {
                foreach ($meetingData['races'] as $race) {
                    if (!empty($race['runners'])) {
                        foreach ($race['runners'] as $runner) {
                            if (!empty($runner['horse']['code'])) {
                                $batchManager->addToQueue(
                                    $runner['horse']['code'],
                                    $runner['name_ch'] ?? ''
                                );
                            }
                        }
                    }
                }
            }
            break;
            
        // ========== 从数据库获取赛马日数据 ==========
        case 'getmeeting':
        case 'getMeeting':
            $result = $manager->getJoinedMeetingData($date, $venue, $raceNo);
            echo json_encode([
                'success' => true,
                'code' => 200,
                'data' => $result
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
          // 添加到 api_hkracing.php 的 switch 语句中
        
        case 'gethorsedata':
        case 'getHorseData':
            $horseCode = $_GET['horse_code'] ?? null;
            if (!$horseCode) {
                echo json_encode(['success' => false, 'error' => '请提供 horse_code 参数']);
                break;
            }
            $result = $horseManager->getHorseData($horseCode);
            echo json_encode(['success' => true, 'data' => $result], JSON_UNESCAPED_UNICODE);
            break;
        
        case 'fetchhorse':
        case 'fetchHorse':
            $horseCode = $_GET['horse_code'] ?? null;
            if (!$horseCode) {
                echo json_encode(['success' => false, 'error' => '请提供 horse_code 参数']);
                break;
            }
            $data = $horseManager->fetchHorseData($horseCode);
            if (!isset($data['error'])) {
                $horseManager->saveHorseData($horseCode, $data);
                echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'error' => $data['error']]);
            }
            break;          

        // ========== 获取马匹历史战绩（完整字段） ==========
        case 'gethorseperformances':
        case 'getHorsePerformances':
            $horseCode = $_GET['horse_code'] ?? null;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            
            if (!$horseCode) {
                echo json_encode([
                    'success' => false,
                    'code' => 400,
                    'error' => '请提供 horse_code 参数'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            }
            
            // 验证马匹代码格式（香港马）
            if (!preg_match('/^[A-Z]\d{3}$/', $horseCode)) {
                echo json_encode([
                    'success' => false,
                    'code' => 400,
                    'error' => '无效的马匹代码格式，应为字母+3位数字如 J071'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            }
            
            try {
                // 创建数据库连接
                $pdo = new PDO(
                    "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
                    $dbConfig['user'],
                    $dbConfig['pass'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                // 获取马匹基本信息
                $stmt = $pdo->prepare("
                    SELECT 
                        horse_code, name_ch, name_en, age, sex, colour,
                        trainer_name_ch, owner_name_ch, current_rating,
                        total_starts, total_wins, total_seconds, total_thirds,
                        total_prize_money, season_prize_money, last_sync_date
                    FROM hkracing_horses 
                    WHERE horse_code = ?
                ");
                $stmt->execute([$horseCode]);
                $horseInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // 修复：LIMIT 和 OFFSET 直接拼接（已转换为整数，安全）
                $stmt = $pdo->prepare("
                    SELECT 
                        id,
                        horse_code,
                        season,
                        race_no,
                        finishing_position,
                        race_date,
                        venue_code,
                        course,
                        distance,
                        going,
                        class,
                        barrier_draw,
                        rating_before,
                        trainer_name_ch as perf_trainer,
                        jockey_name_ch,
                        margin,
                        win_odds,
                        actual_weight,
                        running_position,
                        finishing_time,
                        horse_weight,
                        gear,
                        created_at
                    FROM hkracing_horse_performances 
                    WHERE horse_code = ?
                    ORDER BY race_date DESC, race_no DESC
                    LIMIT {$limit} OFFSET {$offset}
                ");
                $stmt->execute([$horseCode]);
                $performances = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // 获取总数
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM hkracing_horse_performances WHERE horse_code = ?");
                $stmt->execute([$horseCode]);
                $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                echo json_encode([
                    'success' => true,
                    'code' => 200,
                    'data' => [
                        'horse_info' => $horseInfo,
                        'performances' => $performances,
                        'pagination' => [
                            'total' => (int)$total,
                            'limit' => $limit,
                            'offset' => $offset,
                            'returned' => count($performances)
                        ]
                    ]
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'code' => 500,
                    'error' => $e->getMessage()
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            break;  
        // ========== 赛后更新结果 ==========
        case 'updateresults':
        case 'updateResults':
            $date = $_GET['date'] ?? null;
            $venue = $_GET['venue'] ?? null;
            
            if (!$date || !$venue) {
                echo json_encode([
                    'success' => false,
                    'code' => 400,
                    'error' => '请提供 date 和 venue 参数'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            }
            
            try {
                // 强制刷新获取最新数据（包含比赛结果）
                $data = $manager->getRacingData($date, $venue, null, true);
                
                if ($data && !isset($data['errors'])) {
                    // 更新 meeting 状态
                    $stmt = $pdo->prepare("
                        UPDATE hkracing_meetings 
                        SET status = 'RESULT_UPDATED', updated_at = NOW()
                        WHERE date = ? AND venue_code = ?
                    ");
                    $stmt->execute([$date, $venue]);
                    
                    echo json_encode([
                        'success' => true,
                        'code' => 200,
                        'message' => "已更新 {$date} {$venue} 的比赛结果",
                        'data' => $data['data']
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    echo json_encode([
                        'success' => false,
                        'code' => 500,
                        'error' => '更新失败'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'code' => 500,
                    'error' => $e->getMessage()
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            break;            
            
            // ========== 获取赔率数据 ==========
            case 'getodds':
            case 'getOdds':
                $date = $_GET['date'] ?? null;
                $venue = $_GET['venue'] ?? null;
                $raceNo = $_GET['race_no'] ?? null;
                $type = $_GET['type'] ?? 'Curr';  // Pre 或 Curr
                
                if (!$date || !$venue || !$raceNo) {
                    echo json_encode([
                        'success' => false,
                        'code' => 400,
                        'error' => '请提供 date, venue, race_no 参数'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    break;
                }
                
                try {
                    require_once 'HKJCOddsManager.php';
                    $oddsManager = new HKJCOddsManager(
                        $dbConfig['host'],
                        $dbConfig['name'],
                        $dbConfig['user'],
                        $dbConfig['pass']
                    );
                    
                    // 从数据库获取
                    $odds = $oddsManager->getCurrentOdds($date, $venue, $raceNo);
                    
                    if (!$odds) {
                        // 数据库没有，从API获取
                        $result = $oddsManager->fetchOdds($date, $venue, $raceNo, $type);
                        echo json_encode([
                            'success' => true,
                            'code' => 200,
                            'data' => $result,
                            'source' => 'api'
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    } else {
                        echo json_encode([
                            'success' => true,
                            'code' => 200,
                            'data' => json_decode($odds['data_snapshot'], true),
                            'source' => 'database',
                            'last_update' => $odds['last_update_time']
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'code' => 500,
                        'error' => $e->getMessage()
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                break;
            
            // ========== 获取赔率历史（走势图） ==========
            case 'getoddshistory':
            case 'getOddsHistory':
                $date = $_GET['date'] ?? null;
                $venue = $_GET['venue'] ?? null;
                $raceNo = $_GET['race_no'] ?? null;
                $oddsType = $_GET['odds_type'] ?? 'WIN';
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
                
                if (!$date || !$venue || !$raceNo) {
                    echo json_encode([
                        'success' => false,
                        'code' => 400,
                        'error' => '请提供 date, venue, race_no 参数'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    break;
                }
                
                try {
                    require_once 'HKJCOddsManager.php';
                    $oddsManager = new HKJCOddsManager(
                        $dbConfig['host'],
                        $dbConfig['name'],
                        $dbConfig['user'],
                        $dbConfig['pass']
                    );
                    
                    $history = $oddsManager->getOddsHistory($date, $venue, $raceNo, $oddsType, $limit);
                    
                    echo json_encode([
                        'success' => true,
                        'code' => 200,
                        'data' => $history,
                        'total' => count($history)
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'code' => 500,
                        'error' => $e->getMessage()
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                break;        
// 在 api_hkracing.php 的 switch 中添加
// 在 switch 语句中添加
case 'testodds':
    try {
        $stmt = $pdo->prepare("
            SELECT runner_no, win_odds, place_odds 
            FROM hkracing_odds_horse_details 
            WHERE race_date = '2026-04-06' AND venue_code = 'ST' AND race_no = 1
            LIMIT 5
        ");
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $result,
            'count' => count($result)
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    break;        
                
                
        // ========== 清空缓存 ==========
        case 'clearcache':
        case 'clearCache':
            $manager->clearCache();
            echo json_encode([
                'success' => true,
                'code' => 200,
                'message' => '缓存已清空'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
            
        // ========== 默认：显示可用接口 ==========
        default:
            echo json_encode([
                'success' => false,
                'code' => 400,
                'error' => '无效的 action: ' . $action,
                'available_actions' => [
                    'getRacingDataAllComing' => '获取所有即将到来的完整赛马数据（推荐）',
                    'getRacingData' => '统一数据接口（支持参数）',
                    'getActiveMeeting' => '获取当前活跃赛马日（用于跳转）',
                    'getActiveMeetings' => '获取所有活跃赛马日',
                    'fetchAndStore' => '从API获取并存储数据',
                    'getMeeting' => '从数据库获取赛马日数据',
                    'clearCache' => '清空所有缓存'
                ],
                'usage_examples' => [
                    '获取所有即将到来的完整数据' => '/api_hkracing.php?action=getRacingDataAllComing',
                    '强制刷新所有数据' => '/api_hkracing.php?action=getRacingDataAllComing&refresh=true',
                    '获取活跃赛马日列表' => '/api_hkracing.php?action=getRacingData',
                    '获取指定赛马日数据' => '/api_hkracing.php?action=getRacingData&date=2026-04-06&venue=ST',
                    '获取指定场次数据' => '/api_hkracing.php?action=getRacingData&date=2026-04-06&venue=ST&race_no=1'
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'code' => 500,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>