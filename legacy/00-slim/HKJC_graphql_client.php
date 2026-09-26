<?php
// HKJC_graphql_client.php - GraphQL 请求客户端

require_once 'HKJC_graphql_queries.php';

class GraphQLClient {
    private $apiUrl = 'https://info.cld.hkjc.com/graphql/base/';
    
    /**
     * 执行 GraphQL 请求
     * @param string $query GraphQL 查询字符串
     * @param array $variables 变量
     * @return array|null 响应数据
     */
    public function execute($query, $variables = []) {
        $payload = json_encode([
            'query' => $query,
            'variables' => $variables
        ]);
        
        $headers = [
            'Content-Type: application/json',
            'Accept-Encoding: gzip, deflate',
            'Accept: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception("CURL Error: " . $curlError);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: $httpCode, Response: $response");
        }
        
        $data = json_decode($response, true);
        if (isset($data['errors'])) {
            throw new Exception("GraphQL Error: " . json_encode($data['errors']));
        }
        
        return $data;
    }
    
    /**
     * 获取完整的赛马数据
     * @param string $date 日期 Y-m-d
     * @param string $venueCode 场地代码 ST/HV
     * @return array|null
     */
    public function getRacingData($date, $venueCode) {
        $query = getFullRacingQuery();
        $variables = [
            'date' => $date,
            'venueCode' => $venueCode,
            'foOddsTypes' => [],
            'foFilter' => [],
            'resultOddsType' => ['WIN', 'PLA', 'QIN', 'QPL']
        ];
        
        $result = $this->execute($query, $variables);
        return $result['data'] ?? null;
    }
    
    /**
     * 获取未来指定天数内的赛马日
     * @param int $days 未来天数
     * @return array
     */
    // public function getUpcomingRaceDays($days = 7) {
    //     $raceDays = [];
    //     $today = new DateTime('now', new DateTimeZone('Asia/Hong_Kong'));
        
    //     for ($i = 0; $i <= $days; $i++) {
    //         $date = clone $today;
    //         $date->modify("+{$i} days");
    //         $dateStr = $date->format('Y-m-d');
            
    //         try {
    //             // 测试沙田
    //             $data = $this->getRacingData($dateStr, 'ST');
    //             if ($data && isset($data['raceMeetings'][0]) && $data['raceMeetings'][0]['totalNumberOfRace'] > 0) {
    //                 $raceDays[] = [
    //                     'date' => $dateStr,
    //                     'venue' => 'ST',
    //                     'venue_name' => '沙田',
    //                     'race_count' => $data['raceMeetings'][0]['totalNumberOfRace']
    //                 ];
    //             }
                
    //             // 测试跑马地
    //             $data = $this->getRacingData($dateStr, 'HV');
    //             if ($data && isset($data['raceMeetings'][0]) && $data['raceMeetings'][0]['totalNumberOfRace'] > 0) {
    //                 $raceDays[] = [
    //                     'date' => $dateStr,
    //                     'venue' => 'HV',
    //                     'venue_name' => '跑馬地',
    //                     'race_count' => $data['raceMeetings'][0]['totalNumberOfRace']
    //                 ];
    //             }
    //         } catch (Exception $e) {
    //             // 没有数据，跳过
    //         }
            
    //         usleep(200000); // 延迟0.2秒
    //     }
        
    //     return $raceDays;
    // }
    /**
     * 获取未来指定天数内的赛马日
     * @param int $days 未来天数
     * @return array
     */
    // public function getUpcomingRaceDays($days = 7) {
    //     $raceDays = [];
    //     $today = new DateTime('now', new DateTimeZone('Asia/Hong_Kong'));
        
    //     for ($i = 0; $i <= $days; $i++) {
    //         $date = clone $today;
    //         $date->modify("+{$i} days");
    //         $dateStr = $date->format('Y-m-d');
            
    //         try {
    //             // 测试沙田
    //             $data = $this->getRacingData($dateStr, 'ST');
    //             print_r($data);
    //             exit;
    //             // ✅ 关键修复：检查是否有实际的 runners
    //             if ($data && isset($data['raceMeetings'][0]) && $data['raceMeetings'][0]['totalNumberOfRace'] > 0) {
    //                 $races = $data['raceMeetings'][0]['races'] ?? [];
    //                 $hasRunners = false;
    //                 foreach ($races as $race) {
    //                     if (!empty($race['runners']) && count($race['runners']) > 0) {
    //                         $hasRunners = true;
    //                         break;
    //                     }
    //                 }
                    
    //                 if ($hasRunners) {
    //                     $raceDays[] = [
    //                         'date' => $dateStr,
    //                         'venue' => 'ST',
    //                         'venue_name' => '沙田',
    //                         'race_count' => $data['raceMeetings'][0]['totalNumberOfRace']
    //                     ];
    //                 }
    //             }
                
    //             // 测试跑马地
    //             $data = $this->getRacingData($dateStr, 'HV');
    //             if ($data && isset($data['raceMeetings'][0]) && $data['raceMeetings'][0]['totalNumberOfRace'] > 0) {
    //                 $races = $data['raceMeetings'][0]['races'] ?? [];
    //                 $hasRunners = false;
    //                 foreach ($races as $race) {
    //                     if (!empty($race['runners']) && count($race['runners']) > 0) {
    //                         $hasRunners = true;
    //                         break;
    //                     }
    //                 }
                    
    //                 if ($hasRunners) {
    //                     $raceDays[] = [
    //                         'date' => $dateStr,
    //                         'venue' => 'HV',
    //                         'venue_name' => '跑馬地',
    //                         'race_count' => $data['raceMeetings'][0]['totalNumberOfRace']
    //                     ];
    //                 }
    //             }
    //         } catch (Exception $e) {
    //             // 没有数据，跳过
    //         }
            
    //         usleep(200000); // 延迟0.2秒
    //     }
        
    //     return $raceDays;
    // }    
    /**
     * 获取活跃的赛马日
     * @return array
     */
    /**
     * 获取活跃的赛马日（使用完整查询）
     * @return array
     */
    public function getActiveMeetings() {
        // 使用完整查询，但不传日期参数
        $query = getFullRacingQuery();
        $variables = [
            'date' => null,
            'venueCode' => null,
            'foOddsTypes' => [],
            'foFilter' => [],
            'resultOddsType' => ['WIN', 'PLA', 'QIN', 'QPL']
        ];
        
        try {
            $result = $this->execute($query, $variables);
            
            if ($result && isset($result['data']['activeMeetings'])) {
                return $result['data']['activeMeetings'];
            }
        } catch (Exception $e) {
            error_log("getActiveMeetings error: " . $e->getMessage());
        }
        
        return [];
    }
    
    /**
     * 获取未来赛马日
     * @param int $days 忽略
     * @return array
     */
    public function getUpcomingRaceDays($days = 30) {
        $raceDays = [];
        $activeMeetings = $this->getActiveMeetings();
        
        foreach ($activeMeetings as $meeting) {
            $raceDays[] = [
                'date' => $meeting['date'],
                'venue' => $meeting['venueCode'],
                'venue_name' => ($meeting['venueCode'] == 'ST' ? '沙田' : '跑馬地'),
                'race_count' => $meeting['totalNumberOfRace'] ?? 0
            ];
        }
        
        return $raceDays;
    }
}