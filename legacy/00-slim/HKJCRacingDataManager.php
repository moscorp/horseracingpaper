<?php
// HKJCRacingDataManager.php - 完整版

class HKJCRacingDataManager {
    private $db;
    private $cacheDir;
    private $cacheTTL = 300; // 5分钟缓存
    private $apiUrl = 'https://info.cld.hkjc.com/graphql/base/';

    public function __construct($dbHost, $dbName, $dbUser, $dbPass, $cacheTTL = 300) {
        try {
            $this->db = new PDO(
                "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
                $dbUser,
                $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            throw new Exception("数据库连接失败: " . $e->getMessage());
        }

        $this->cacheDir = sys_get_temp_dir() . '/hkjc_racing_cache/';
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }

        $this->cacheTTL = $cacheTTL;
    }

    /**
     * 核心方法：获取赛马数据
     */
    public function getRacingData($racingdate = null, $venueCode = null, $resultOddsType = null, $forceRefresh = false) {
        $cacheKey = 'racing_data_' . md5($racingdate . $venueCode . $resultOddsType);
        
        if (!$forceRefresh) {
            $dbCached = $this->getDBCache($cacheKey);
            if ($dbCached) {
                return $dbCached;
            }
            $fileCached = $this->getFileCache($cacheKey);
            if ($fileCached) {
                $this->setDBCache($cacheKey, $fileCached);
                return $fileCached;
            }
        }

        $query = $this->getFullGraphQLQuery();
        
        $variables = [
            'date' => $racingdate,
            'venueCode' => $venueCode,
            'foOddsTypes' => [],
            'resultOddsType' => $resultOddsType ? [$resultOddsType] : null
        ];
        $variables = array_filter($variables, function($value) { return $value !== null; });
        
        $data = $this->executeGraphQL($query, $variables);
        // print_r($variables);
        // print_r($data);
        if ($data && !isset($data['errors'])) {
            $this->setDBCache($cacheKey, $data, $this->cacheTTL);
            $this->setFileCache($cacheKey, $data, $this->cacheTTL);
            
            // 存储到数据库
            if (!empty($data['data']['raceMeetings'])) {
                $this->storeToDatabase($data['data']['raceMeetings'][0]);
            }
            if (!empty($data['data']['activeMeetings'])) {
                foreach ($data['data']['activeMeetings'] as $activeMeeting) {
                    $this->storeActiveMeeting($activeMeeting);
                }
            }
            
            return $data;
        }
        
        if ($data && isset($data['errors'])) {
            error_log("GraphQL API Error: " . json_encode($data['errors']));
        }
        return null;
    }

    /**
     * 获取活跃赛马日列表
     */
    // public function getActiveMeetingsOnly() {
    //     $result = $this->getRacingData();
    //     if ($result && isset($result['data']['activeMeetings'])) {
    //         return $result['data']['activeMeetings'];
    //     }
    //     return [];
    // }
    // public function getActiveMeetingsOnly() {
    //     $cacheKey = 'active_meetings_list';
        
    //     $cached = $this->getFileCache($cacheKey);
    //     if ($cached) {
    //         return $cached;
    //     }
        
    //     // 先尝试从 API 获取
    //     $query = '{ activeMeetings: raceMeetings { id venueCode date status races { no postTime status wageringFieldSize } } }';
    //     $data = $this->executeGraphQL($query);
        
    //     if (!empty($data['data']['activeMeetings'])) {
    //         $result = $data['data']['activeMeetings'];
    //         $this->setFileCache($cacheKey, $result, 300);
    //         return $result;
    //     }
        
    //     // 如果 API 返回空，从数据库获取未来赛事
    //     $stmt = $this->db->prepare("
    //         SELECT DISTINCT 
    //             date,
    //             venue_code as venueCode,
    //             'DEFINED' as status
    //         FROM hkracing_meetings 
    //         WHERE date >= CURDATE() 
    //           AND date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    //           AND venue_code IN ('ST', 'HV')
    //         ORDER BY date
    //     ");
    //     $stmt->execute();
    //     $dbMeetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    //     // 为每个 meeting 添加空的 races 数组
    //     foreach ($dbMeetings as &$meeting) {
    //         $meeting['races'] = [];
    //     }
        
    //     $this->setFileCache($cacheKey, $dbMeetings, 300);
    //     return $dbMeetings;
    // }
    /**
     * 获取活跃赛马日列表
     */
        /**
     * 获取活跃赛马日列表
     */
    /**
     * 获取活跃赛马日列表（直接使用数据库）
     */
    public function getActiveMeetingsOnly() {
        $cacheKey = 'active_meetings_list';
        
        $cached = $this->getFileCache($cacheKey);
        if ($cached) {
            return $cached;
        }
        
        // 直接从数据库获取未来赛事
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT 
                    date,
                    venue_code as venueCode,
                    status,
                    id
                FROM hkracing_meetings 
                WHERE date >= CURDATE() 
                  AND date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                  AND venue_code IN ('ST', 'HV')
                ORDER BY date
            ");
            $stmt->execute();
            $dbMeetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 为每个 meeting 添加空的 races 数组
            foreach ($dbMeetings as &$meeting) {
                $meeting['races'] = [];
            }
            
            $this->setFileCache($cacheKey, $dbMeetings, 300);
            return $dbMeetings;
            
        } catch (Exception $e) {
            error_log("获取数据库赛事失败: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 获取用于跳转的当前活跃赛马日
     */
    public function getCurrentActiveMeeting() {
        $activeMeetings = $this->getActiveMeetingsOnly();
        if (!empty($activeMeetings)) {
            $first = $activeMeetings[0];
            return [
                'id' => $first['id'],
                'date' => $first['date'],
                'venueCode' => $first['venueCode'],
                'firstRaceNo' => $first['races'][0]['no'] ?? 1,
                'firstRaceTime' => $first['races'][0]['postTime'] ?? null
            ];
        }
        return null;
    }

    /**
     * 获取完整的GraphQL查询
     */
    private function getFullGraphQLQuery() {
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
     * 执行GraphQL请求
     */
    // private function executeGraphQL($query, $variables = []) {
    //     $payload = json_encode(['query' => $query, 'variables' => $variables]);
        
    //     $ch = curl_init($this->apiUrl);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //     curl_setopt($ch, CURLOPT_POST, true);
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, [
    //         'Content-Type: application/json',
    //         'Accept-Encoding: gzip, deflate',
    //         'Accept: application/json'
    //     ]);
    //     curl_setopt($ch, CURLOPT_ENCODING, '');
    //     curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
    //     $response = curl_exec($ch);
    //     $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
    //     if (curl_errno($ch)) {
    //         throw new Exception('CURL错误: ' . curl_error($ch));
    //     }
        
    //     curl_close($ch);
        
    //     if ($httpCode !== 200) {
    //         throw new Exception("API返回HTTP状态码: $httpCode");
    //     }
        
    //     return json_decode($response, true);
    // }

// public  function executeGraphQL($query, $variables = []) {
//     $payload = json_encode(['query' => $query, 'variables' => $variables]);
    
//     $ch = curl_init($this->apiUrl);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_POST, true);
//     curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
//     curl_setopt($ch, CURLOPT_HTTPHEADER, [
//         'Content-Type: application/json',
//         'Accept-Encoding: gzip, deflate'  // ✅ 关键：添加压缩支持
//     ]);
//     curl_setopt($ch, CURLOPT_ENCODING, '');  // ✅ 关键：让 cURL 处理编码
//     curl_setopt($ch, CURLOPT_TIMEOUT, 30);
//     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//     curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
//     $response = curl_exec($ch);
//     $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
//     if (curl_errno($ch)) {
//         throw new Exception('CURL错误: ' . curl_error($ch));
//     }
    
//     curl_close($ch);
    
//     if ($httpCode !== 200) {
//         throw new Exception("API返回HTTP状态码: $httpCode");
//     }
    
//     return json_decode($response, true);
// }
public function executeGraphQL($query, $variables = []) {
    $payload = json_encode(['query' => $query, 'variables' => $variables]);
    
    // 调试输出
    // echo "========== DEBUG INFO ==========\n";
    // echo "URL: " . $this->apiUrl . "\n";
    // echo "Query: " . substr($query, 0, 200) . "...\n";
    // echo "Variables: " . json_encode($variables) . "\n";
    // echo "Payload length: " . strlen($payload) . "\n";
    
    $ch = curl_init($this->apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Accept-Encoding: gzip, deflate'
    ]);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_VERBOSE, true);  // 详细输出
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    
    // echo "HTTP Code: " . $httpCode . "\n";
    // echo "CURL Error: " . $curlError . "\n";
    // echo "Response: " . $response . "\n";
    // echo "===============================\n";
    
    if (curl_errno($ch)) {
        throw new Exception('CURL错误: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("API返回HTTP状态码: $httpCode, Response: $response");
    }
    
    return json_decode($response, true);
}
    /**
     * 存储赛马日数据到数据库
     */
public function storeToDatabase($meetingData) {
    try {
        // 添加调试日志
        error_log("=== storeToDatabase 开始 ===");
        error_log("meetingData: " . json_encode([
            'id' => $meetingData['id'] ?? 'null',
            'venueCode' => $meetingData['venueCode'] ?? 'null',
            'date' => $meetingData['date'] ?? 'null',
            'totalNumberOfRace' => $meetingData['totalNumberOfRace'] ?? 'null',
            'races_count' => count($meetingData['races'] ?? [])
        ]));
        
        $this->db->beginTransaction();
        
        // ✅ 修复条件：只要 meetingData 有 id 就插入，不要求 totalNumberOfRace > 0
        if (isset($meetingData['id'])) {
            error_log("插入 meeting: {$meetingData['id']}");
            
            $stmt = $this->db->prepare("
                INSERT INTO hkracing_meetings 
                (id, venue_code, date, status, total_number_of_race, current_number_of_race, 
                 date_of_week, meeting_type, total_investment)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                venue_code = VALUES(venue_code),
                status = VALUES(status),
                total_number_of_race = VALUES(total_number_of_race),
                current_number_of_race = VALUES(current_number_of_race),
                updated_at = CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([
                $meetingData['id'],
                $meetingData['venueCode'],
                $meetingData['date'],
                $meetingData['status'],
                $meetingData['totalNumberOfRace'] ?? 0,
                $meetingData['currentNumberOfRace'] ?? 0,
                $meetingData['dateOfWeek'] ?? null,
                $meetingData['meetingType'] ?? null,
                $meetingData['totalInvestment'] ?? 0
            ]);
            error_log("meeting 插入成功，影响行数: " . $stmt->rowCount());
        } else {
            error_log("meetingData 缺少 id 字段");
        }
        
        // 存储赛事
        if (isset($meetingData['races']) && !empty($meetingData['races'])) {
            error_log("开始处理 " . count($meetingData['races']) . " 场赛事");
            
            foreach ($meetingData['races'] as $index => $race) {
                error_log("处理第 " . ($index+1) . " 场, race_id: " . ($race['id'] ?? 'null'));
                
                if (!isset($race['id'])) {
                    error_log("race 缺少 id，跳过");
                    continue;
                }
                
                $stmt = $this->db->prepare("
                    INSERT INTO hkracing_races 
                    (id, meeting_id, race_no, status, race_name_en, race_name_ch, post_time,
                     distance, wagering_field_size, rating_type, class_code, race_class_en, race_class_ch)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    status = VALUES(status),
                    race_name_en = VALUES(race_name_en),
                    race_name_ch = VALUES(race_name_ch),
                    updated_at = CURRENT_TIMESTAMP
                ");
                
                $postTime = null;
                if (isset($race['postTime'])) {
                    $datetime = new DateTime($race['postTime']);
                    $datetime->setTimezone(new DateTimeZone('Asia/Hong_Kong'));
                    $postTime = $datetime->format('Y-m-d H:i:s');
                }
                
                $stmt->execute([
                    $race['id'],
                    $meetingData['id'],
                    $race['no'],
                    $race['status'] ?? null,
                    $race['raceName_en'] ?? null,
                    $race['raceName_ch'] ?? null,
                    $postTime,
                    $race['distance'] ?? null,
                    $race['wageringFieldSize'] ?? null,
                    $race['ratingType'] ?? null,
                    $race['claCode'] ?? null,
                    $race['raceClass_en'] ?? null,
                    $race['raceClass_ch'] ?? null
                ]);
                error_log("race 插入成功，影响行数: " . $stmt->rowCount());
                
                // 存储马匹
                if (isset($race['runners']) && !empty($race['runners'])) {
                    error_log("  处理 " . count($race['runners']) . " 匹参赛马");
                    
                    foreach ($race['runners'] as $runner) {
                        if (!isset($runner['id'])) {
                            continue;
                        }
                        
                        $stmt = $this->db->prepare("
                            INSERT INTO hkracing_runners 
                            (id, race_id, horse_code, horse_id, runner_no, status, name_ch, name_en,
                             barrier_draw_number, handicap_weight, current_rating, gear_info,
                             jockey_code, jockey_name_en, jockey_name_ch, trainer_code,
                             trainer_name_en, trainer_name_ch, final_position, win_odds)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE
                            status = VALUES(status),
                            barrier_draw_number = VALUES(barrier_draw_number),
                            handicap_weight = VALUES(handicap_weight),
                            jockey_code = VALUES(jockey_code),
                            trainer_code = VALUES(trainer_code),
                            updated_at = CURRENT_TIMESTAMP
                        ");
                        
                        $stmt->execute([
                            $runner['id'],
                            $race['id'],
                            $runner['horse']['code'] ?? null,
                            $runner['horse']['id'] ?? null,
                            $runner['no'] ?? null,
                            $runner['status'] ?? null,
                            $runner['name_ch'] ?? null,
                            $runner['name_en'] ?? null,
                            $runner['barrierDrawNumber'] ?? null,
                            $runner['handicapWeight'] ?? null,
                            $runner['currentRating'] ?? null,
                            $runner['gearInfo'] ?? null,
                            $runner['jockey']['code'] ?? null,
                            $runner['jockey']['name_en'] ?? null,
                            $runner['jockey']['name_ch'] ?? null,
                            $runner['trainer']['code'] ?? null,
                            $runner['trainer']['name_en'] ?? null,
                            $runner['trainer']['name_ch'] ?? null,
                            $runner['finalPosition'] ?? 0,
                            $runner['winOdds'] ?? null
                        ]);
                    }
                    error_log("  马匹插入完成");
                }
            }
        } else {
            error_log("没有 races 数据");
        }
        
        $this->db->commit();
        error_log("=== storeToDatabase 完成 ===");
        return true;
        
    } catch (Exception $e) {
        $this->db->rollBack();
        error_log("数据库存储错误: " . $e->getMessage());
        error_log("错误堆栈: " . $e->getTraceAsString());
        return false;
    }
}
    
    /**
     * 存储活跃赛马日
     */
    private function storeActiveMeeting($meetingData) {
        try {
            if(isset($meetingData['id'])){
                $stmt = $this->db->prepare("
                    INSERT INTO hkracing_active_meetings (meeting_id)
                    VALUES (?)
                    ON DUPLICATE KEY UPDATE activated_at = CURRENT_TIMESTAMP
                ");
                $stmt->execute([$meetingData['id']]);
            }
        } catch (Exception $e) {
            error_log("存储活跃赛马日错误: " . $e->getMessage());
        }
    }

    /**
     * 数据库缓存方法
     */
    public  function getDBCache($key) {
        try {
            $stmt = $this->db->prepare("SELECT response_data FROM hkracing_cache WHERE cache_key = ? AND expires_at > NOW()");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? json_decode($result['response_data'], true) : null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    public  function setDBCache($key, $data, $ttl = 300) {
        try {
            $expiresAt = date('Y-m-d H:i:s', time() + $ttl);
            $stmt = $this->db->prepare("
                INSERT INTO hkracing_cache (cache_key, response_data, expires_at) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                response_data = VALUES(response_data), 
                expires_at = VALUES(expires_at)
            ");
            $stmt->execute([$key, json_encode($data), $expiresAt]);
        } catch (Exception $e) {
            error_log("数据库缓存写入错误: " . $e->getMessage());
        }
    }
    
    /**
     * 文件缓存方法
     */
    private function getFileCache($key) {
        $filePath = $this->cacheDir . md5($key) . '.json';
        if (file_exists($filePath) && (time() - filemtime($filePath)) < $this->cacheTTL) {
            return json_decode(file_get_contents($filePath), true);
        }
        return null;
    }
    
    private function setFileCache($key, $data, $ttl = 300) {
        $filePath = $this->cacheDir . md5($key) . '.json';
        file_put_contents($filePath, json_encode($data));
        touch($filePath, time() + $ttl);
    }
    

    public function getJoinedMeetingData($date = null, $venueCode = null, $raceNo = null) {
        $sql = "
            SELECT DISTINCT
                m.id, 
                m.venue_code, 
                m.date, 
                m.status, 
                m.total_number_of_race,
                COALESCE(
                    (SELECT 
                        JSON_ARRAYAGG(
                            JSON_OBJECT(
                                'race_id', r.id,
                                'race_no', r.race_no,
                                'race_name_en', r.race_name_en,
                                'race_name_ch', r.race_name_ch,
                                'post_time', r.post_time,
                                'distance', r.distance,
                                'wagering_field_size', r.wagering_field_size,
                                'status', r.status,
                                'class_code', r.class_code,
                                'race_class_en', r.race_class_en,
                                'race_class_ch', r.race_class_ch,
                                'runners', (
                                    SELECT IFNULL(
                                        JSON_ARRAYAGG(
                                            JSON_OBJECT(
                                                'runner_id', ru.id,
                                                'runner_no', ru.runner_no,
                                                'standby_no', ru.standby_no,
                                                'status', ru.status,
                                                'name_en', ru.name_en,
                                                'name_ch', ru.name_ch,
                                                'horse_code', ru.horse_code,
                                                'barrier_draw_number', ru.barrier_draw_number,
                                                'handicap_weight', ru.handicap_weight,
                                                'jockey_name_en', ru.jockey_name_en,
                                                'jockey_name_ch', ru.jockey_name_ch,
                                                'trainer_name_en', ru.trainer_name_en,
                                                'trainer_name_ch', ru.trainer_name_ch,
                                                'final_position', ru.final_position,
                                                'win_odds', ru.win_odds
                                            )
                                        ),
                                        JSON_ARRAY()
                                    )
                                    FROM hkracing_runners ru 
                                    WHERE ru.race_id = r.id
                                    ORDER BY CAST(ru.runner_no AS UNSIGNED)
                                )
                            )
                        )
                        FROM hkracing_races r
                        WHERE r.meeting_id = m.id
                        ORDER BY r.race_no
                    ),
                    JSON_ARRAY()
                ) as races
            FROM hkracing_meetings m
            WHERE 1=1
        ";
        
        $params = [];
        if ($date) {
            $sql .= " AND m.date = ?";
            $params[] = $date;
        }
        if ($venueCode) {
            $sql .= " AND m.venue_code = ?";
            $params[] = $venueCode;
        }
        if ($raceNo) {
            // 如果指定了场次，需要过滤 races
            $sql .= " AND EXISTS (SELECT 1 FROM hkracing_races WHERE meeting_id = m.id AND race_no = ?)";
            $params[] = $raceNo;
        }
        
        $sql .= " ORDER BY m.date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 解析 JSON
        foreach ($results as &$row) {
            $row['races'] = json_decode($row['races'], true);
            // 如果指定了场次，只返回该场次
            if ($raceNo && is_array($row['races'])) {
                $filteredRaces = [];
                foreach ($row['races'] as $race) {
                    if ($race['race_no'] == $raceNo) {
                        $filteredRaces[] = $race;
                        break;
                    }
                }
                $row['races'] = $filteredRaces;
            }
        }
        
        return $results;
    }

    /**
     * 清空所有缓存
     */
    public function clearCache() {
        $files = glob($this->cacheDir . '*.json');
        foreach ($files as $file) {
            unlink($file);
        }
        
        try {
            $this->db->exec("TRUNCATE TABLE hkracing_cache");
        } catch (Exception $e) {
            error_log("清空数据库缓存失败: " . $e->getMessage());
        }
        
        return true;
    }
    // 添加到 HKJCRacingDataManager 类中
    public function addHistoryToData($data, $originalManager) {
        // 如果已经有历史数据，直接返回
        if (isset($data['horse_histories'])) {
            return $data;
        }
        
        // 收集香港马匹代码
        $hkHorseCodes = [];
        if (!empty($data['raceMeetings'])) {
            foreach ($data['raceMeetings'] as $meeting) {
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
        
        if (!empty($hkHorseCodes)) {
            // 查询数据库获取历史
            $placeholders = implode(',', array_fill(0, count($hkHorseCodes), '?'));
            $stmt = $this->db->prepare("
                SELECT horse_code, season, race_no, finishing_position, race_date,
                       venue_code, distance, going, jockey_name_ch, margin, win_odds
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
            
            $data['horse_histories'] = $histories;
            $data['history_summary'] = [
                'total_hk_horses' => count($hkHorseCodes),
                'horses_with_history' => count($histories)
            ];
        }
        
        return $data;
    }
    
    /**
     * 获取未来赛马日列表（使用 GraphQL）
     * @param int $days 获取未来多少天
     * @return array 赛马日列表
     */
    public function getUpcomingRaceDaysFromGraphQL($days = 60) {
        $query = '
        query GetRaceCalendar($startDate: Date!, $endDate: Date!) {
            raceCalendar(startDate: $startDate, endDate: $endDate) {
                date
                venue {
                    code
                    nameZh
                    nameEn
                }
                raceCount
                isHoliday
                status
            }
        }';
        
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime("+{$days} days"));
        
        try {
            $result = $this->executeGraphQL($query, [
                'startDate' => $startDate,
                'endDate' => $endDate
            ]);
            
            if ($result && isset($result['data']['raceCalendar'])) {
                $raceDays = [];
                foreach ($result['data']['raceCalendar'] as $day) {
                    // 只保留香港本地赛马（沙田ST或跑马地HV）
                    $venueCode = $day['venue']['code'] ?? '';
                    if (in_array($venueCode, ['ST', 'HV'])) {
                        $raceDays[] = [
                            'date' => $day['date'],
                            'venue' => $venueCode,
                            'venue_name' => $day['venue']['nameZh'] ?? ($venueCode == 'ST' ? '沙田' : '跑馬地'),
                            'race_count' => $day['raceCount'] ?? 0,
                            'status' => $day['status'] ?? 'PENDING'
                        ];
                    }
                }
                return $raceDays;
            }
        } catch (Exception $e) {
            error_log("getUpcomingRaceDaysFromGraphQL error: " . $e->getMessage());
        }
        
        return [];
    }
    
    /**
     * 获取活跃的赛马日（正在接受投注的）
     */
    public function getActiveRaceMeetingsFromGraphQL() {
        $query = '
        query GetActiveRaceMeetings {
            activeRaceMeetings {
                meetingId
                meetingDate
                venue {
                    code
                    nameZh
                    nameEn
                }
                races {
                    raceNo
                    raceName
                    startTime
                }
                status
            }
        }';
        
        try {
            $result = $this->executeGraphQL($query);
            echo "xxxxxxxxxxxxxxx";
            print_r($result);
            if ($result && isset($result['data']['activeRaceMeetings'])) {
                $raceDays = [];
                foreach ($result['data']['activeRaceMeetings'] as $meeting) {
                    $venueCode = $meeting['venue']['code'] ?? '';
                    if (in_array($venueCode, ['ST', 'HV'])) {
                        $raceDays[] = [
                            'date' => $meeting['meetingDate'],
                            'venue' => $venueCode,
                            'venue_name' => $meeting['venue']['nameZh'] ?? ($venueCode == 'ST' ? '沙田' : '跑馬地'),
                            'meeting_id' => $meeting['meetingId'],
                            'race_count' => count($meeting['races'] ?? []),
                            'status' => $meeting['status']
                        ];
                    }
                }
                return $raceDays;
            }
        } catch (Exception $e) {
            error_log("getActiveRaceMeetingsFromGraphQL error: " . $e->getMessage());
            echo "Caught Exception: " . $e->getMessage() . "\n";  // 添加这行
        }
        
        return [];
    }
    
    /**
     * 获取特定日期的完整赛事数据（包括马匹、赔率）
     * @param string $date 日期 Y-m-d
     * @param string $venueCode 场地代码 ST/HV
     * @return array|null
     */
    public function getRaceDataFromGraphQL($date, $venueCode) {
        $query = '
        query GetRaceData($date: Date!, $venueCode: String!) {
            raceMeeting(date: $date, venueCode: $venueCode) {
                meetingId
                meetingDate
                venue {
                    code
                    nameZh
                    nameEn
                }
                races {
                    raceNo
                    raceName
                    raceClass
                    distance
                    going
                    startTime
                    status
                    runners {
                        horseCode
                        horseName {
                            en
                            zh
                        }
                        draw
                        weight
                        rating
                        jockey {
                            nameEn
                            nameZh
                        }
                        trainer {
                            nameEn
                            nameZh
                        }
                        latestOdds {
                            win
                            place
                        }
                    }
                }
            }
        }';
        
        try {
            $result = $this->executeGraphQL($query, [
                'date' => $date,
                'venueCode' => $venueCode
            ]);
            
            if ($result && isset($result['data']['raceMeeting'])) {
                return $result['data']['raceMeeting'];
            }
        } catch (Exception $e) {
            error_log("getRaceDataFromGraphQL error: " . $e->getMessage());
        }
        
        return null;
    }    
    
    /**
     * 使用 GraphQL 获取未来赛马日
     * @param int $days 未来天数
     * @return array
     */
    public function getUpcomingRaceDaysGraphQL($days = 30) {
        require_once 'HKJC_graphql_client.php';
        $client = new GraphQLClient();
        return $client->getUpcomingRaceDays($days);
    }
    
    /**
     * 使用 GraphQL 获取完整赛马数据
     * @param string $date 日期
     * @param string $venueCode 场地
     * @return array|null
     */
    public function getRacingDataGraphQL($date, $venueCode) {
        require_once 'HKJC_graphql_client.php';
        $client = new GraphQLClient();
        $data = $client->getRacingData($date, $venueCode);
        
        if ($data && isset($data['raceMeetings'][0])) {
            return $data['raceMeetings'][0];
        }
        return null;
    }
}