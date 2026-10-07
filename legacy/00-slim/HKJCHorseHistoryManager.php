<?php
// HKJCHorseHistoryManager.php - 马匹历史战绩抓取类（独立于 RacingDataManager）

class HKJCHorseHistoryManager {
    private $db;
    private $baseUrl = 'https://racing.hkjc.com/zh-hk/local/information/horse?HorseNo=';
    
    public function __construct($dbHost, $dbName, $dbUser, $dbPass) {
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
    }
    
    /**
     * 获取并解析马匹历史数据
     * @param string $horseCode 马匹代码，如 'J071'
     * @return array 解析后的数据
     */
    public function fetchHorseHistory($horseCode) {
        $url = $this->baseUrl . urlencode($horseCode);
        $html = $this->getHtmlContent($url);
        
        if (!$html) {
            return ['error' => '无法获取页面内容'];
        }
        
        return $this->parseHorseHistory($html, $horseCode);
    }
    
    /**
     * 使用cURL获取HTML内容
     */
    private function getHtmlContent($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("获取马匹页面失败，HTTP状态码：$httpCode, URL：$url");
            return false;
        }
        
        return $response;
    }
    
    /**
     * 解析HTML，提取马匹信息和战绩
     */
    private function parseHorseHistory($html, $horseCode) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        
        // 初始化数据
        $horseInfo = $this->parseHorseInfo($xpath, $horseCode);
        $performances = $this->parsePerformances($xpath, $horseCode);
        
        return ['info' => $horseInfo, 'performances' => $performances];
    }
    
    /**
     * 解析马匹基本信息
     */
    private function parseHorseInfo($xpath, $horseCode) {
        $horseInfo = [
            'horse_code' => $horseCode,
            'name_ch' => '',
            'name_en' => '',
            'age' => null,
            'sex' => '',
            'colour' => '',
            'import_type' => '',
            'country_of_origin' => '',
            'trainer_name_ch' => '',
            'owner_name_ch' => '',
            'current_rating' => null,
            'season_start_rating' => null,
            'sire' => '',
            'dam' => '',
            'maternal_sire' => '',
            'total_starts' => 0,
            'total_wins' => 0,
            'total_seconds' => 0,
            'total_thirds' => 0,
            'total_prize_money' => 0,
            'season_prize_money' => 0,
        ];
        
        // 获取马名
        $nameNode = $xpath->query("//span[@class='title_text']");
        if ($nameNode->length > 0) {
            $fullName = trim($nameNode->item(0)->nodeValue);
            if (preg_match('/^(.*?)\s*\(([^)]+)\)$/', $fullName, $matches)) {
                $horseInfo['name_ch'] = $matches[1];
            } else {
                $horseInfo['name_ch'] = $fullName;
            }
        }
        
        // 解析基本信息表格
        $infoRows = $xpath->query("//table[contains(@class, 'horseProfile')]//tr");
        foreach ($infoRows as $row) {
            $cells = $xpath->query('.//td', $row);
            if ($cells->length >= 3) {
                $label = trim($cells->item(0)->nodeValue);
                $value = trim($cells->item(2)->nodeValue);
                
                if (strpos($label, '出生地') !== false) {
                    if (preg_match('/([^\/]+)\s*\/\s*(\d+)/', $value, $m)) {
                        $horseInfo['country_of_origin'] = trim($m[1]);
                        $horseInfo['age'] = intval($m[2]);
                    }
                } elseif (strpos($label, '毛色') !== false) {
                    if (preg_match('/([^\/]+)\s*\/\s*(.+)/', $value, $m)) {
                        $horseInfo['colour'] = trim($m[1]);
                        $horseInfo['sex'] = trim($m[2]);
                    }
                } elseif (strpos($label, '進口類別') !== false) {
                    $horseInfo['import_type'] = $value;
                } elseif (strpos($label, '今季獎金') !== false) {
                    $horseInfo['season_prize_money'] = intval(preg_replace('/[^0-9]/', '', $value));
                } elseif (strpos($label, '總獎金') !== false) {
                    $horseInfo['total_prize_money'] = intval(preg_replace('/[^0-9]/', '', $value));
                } elseif (strpos($label, '冠-亞-季-總出賽次數') !== false) {
                    if (preg_match('/(\d+)-(\d+)-(\d+)-(\d+)/', $value, $m)) {
                        $horseInfo['total_wins'] = intval($m[1]);
                        $horseInfo['total_seconds'] = intval($m[2]);
                        $horseInfo['total_thirds'] = intval($m[3]);
                        $horseInfo['total_starts'] = intval($m[4]);
                    }
                }
            }
        }
        
        // 解析右侧表格
        $rightRows = $xpath->query("//table[contains(@class, 'table_top_right')]//tr");
        foreach ($rightRows as $row) {
            $cells = $xpath->query('.//td', $row);
            if ($cells->length >= 3) {
                $label = trim($cells->item(0)->nodeValue);
                $value = trim($cells->item(2)->nodeValue);
                
                if (strpos($label, '練馬師') !== false) {
                    $horseInfo['trainer_name_ch'] = $value;
                } elseif (strpos($label, '馬主') !== false) {
                    $horseInfo['owner_name_ch'] = $value;
                } elseif (strpos($label, '現時評分') !== false) {
                    $horseInfo['current_rating'] = intval($value);
                } elseif (strpos($label, '季初評分') !== false) {
                    $horseInfo['season_start_rating'] = intval($value);
                } elseif (strpos($label, '父系') !== false) {
                    $horseInfo['sire'] = $value;
                } elseif (strpos($label, '母系') !== false) {
                    $horseInfo['dam'] = $value;
                } elseif (strpos($label, '外祖父') !== false) {
                    $horseInfo['maternal_sire'] = $value;
                }
            }
        }
        
        return $horseInfo;
    }
    
    /**
     * 解析战绩表格
     */
    private function parsePerformances($xpath, $horseCode) {
        $performances = [];
        
        // 查找所有战绩行
        $rows = $xpath->query("//table[contains(@class, 'bigborder')]//tr");
        $currentSeason = '';
        
        foreach ($rows as $row) {
            // 检查是否是赛季标题行
            $headerCols = $xpath->query('.//td[@colspan="19"]', $row);
            if ($headerCols->length > 0) {
                $seasonText = trim($headerCols->item(0)->nodeValue);
                if (preg_match('/(\d{2}\/\d{2})/', $seasonText, $matches)) {
                    $currentSeason = $matches[1];
                }
                continue;
            }
            
            // 解析数据行
            $cols = $xpath->query('.//td', $row);
            if ($cols->length >= 18 && $currentSeason) {
                $perf = $this->parsePerformanceRow($cols, $currentSeason, $horseCode);
                if ($perf && $perf['race_date']) {
                    $performances[] = $perf;
                }
            }
        }
        
        return $performances;
    }
    
    /**
     * 解析单行战绩
     */
    private function parsePerformanceRow($cols, $season, $horseCode) {
        try {
            // 获取场次
            $raceNoText = trim($cols->item(0)->nodeValue);
            $raceNo = intval($raceNoText);
            
            // 获取名次
            $positionText = trim($cols->item(1)->nodeValue);
            $position = $positionText == '--' ? null : intval($positionText);
            
            // 解析日期
            $dateStr = trim($cols->item(2)->nodeValue);
            $raceDate = $this->parseDate($dateStr);
            
            if (!$raceDate) {
                return null;
            }
            
            // 马场
            $courseStr = trim($cols->item(3)->nodeValue);
            $venueCode = strpos($courseStr, '跑馬地') !== false ? 'HV' : (strpos($courseStr, '沙田') !== false ? 'ST' : '');
            
            // 途程
            $distance = intval(preg_replace('/[^0-9]/', '', trim($cols->item(4)->nodeValue)));
            
            return [
                'horse_code' => $horseCode,
                'season' => $season,
                'race_no' => $raceNo,
                'finishing_position' => $position,
                'race_date' => $raceDate,
                'venue_code' => $venueCode,
                'course' => $courseStr,
                'distance' => $distance,
                'going' => trim($cols->item(5)->nodeValue),
                'class' => trim($cols->item(6)->nodeValue),
                'barrier_draw' => intval(trim($cols->item(7)->nodeValue)),
                'rating_before' => intval(trim($cols->item(8)->nodeValue)),
                'trainer_name_ch' => trim($cols->item(9)->nodeValue),
                'jockey_name_ch' => trim($cols->item(10)->nodeValue),
                'margin' => trim($cols->item(11)->nodeValue),
                'win_odds' => floatval(trim($cols->item(12)->nodeValue)),
                'actual_weight' => floatval(trim($cols->item(13)->nodeValue)),
                'running_position' => trim($cols->item(14)->nodeValue),
                'finishing_time' => trim($cols->item(15)->nodeValue),
                'horse_weight' => intval(trim($cols->item(16)->nodeValue)),
                'gear' => trim($cols->item(17)->nodeValue),
            ];
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * 解析日期
     */
    // private function parseDate($dateStr) {
    //     if (preg_match('/(\d{2})\/(\d{2})\/(\d{2})/', $dateStr, $matches)) {
    //         $day = $matches[1];
    //         $month = $matches[2];
    //         $year = 2000 + intval($matches[3]);
    //         return sprintf('%04d-%02d-%02d', $year, $month, $day);
    //     }
    //     if (preg_match('/(\d{4}-\d{2}-\d{2})/', $dateStr, $matches)) {
    //         return $matches[1];
    //     }
    //     return null;
    // }
    // 找到 parseDate 方法，替换为：

    private function parseDate($dateStr) {
        // 处理格式: 28/01/26 -> 2026-01-28
        if (preg_match('/(\d{2})\/(\d{2})\/(\d{2})/', $dateStr, $matches)) {
            $day = $matches[1];
            $month = $matches[2];
            $year = 2000 + intval($matches[3]);
            // 验证日期有效性
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }
        // 处理格式: 2026-01-28
        if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $dateStr, $matches)) {
            $year = $matches[1];
            $month = $matches[2];
            $day = $matches[3];
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }
        return null;
    }
    /**
     * 保存马匹历史数据到数据库
     */
    /**
     * 保存马匹历史数据到数据库
     */
    public function saveHorseHistory($horseCode, $data) {
        try {
            $this->db->beginTransaction();
            
            // 使用 PHP 生成香港时间
            $hkTime = new DateTime('now', new DateTimeZone('Asia/Hong_Kong'));
            $today = $hkTime->format('Y-m-d');
            $now = $hkTime->format('Y-m-d H:i:s');
            
            $info = $data['info'];
            
            // 保存或更新主信息
            $stmt = $this->db->prepare("
                INSERT INTO hkracing_horses 
                (horse_code, name_ch, age, sex, colour, import_type, country_of_origin,
                 trainer_name_ch, owner_name_ch, current_rating, season_start_rating,
                 sire, dam, maternal_sire, total_starts, total_wins, total_seconds,
                 total_thirds, total_prize_money, season_prize_money, last_sync_date, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                name_ch = VALUES(name_ch),
                age = VALUES(age),
                sex = VALUES(sex),
                colour = VALUES(colour),
                import_type = VALUES(import_type),
                country_of_origin = VALUES(country_of_origin),
                trainer_name_ch = VALUES(trainer_name_ch),
                owner_name_ch = VALUES(owner_name_ch),
                current_rating = VALUES(current_rating),
                season_start_rating = VALUES(season_start_rating),
                sire = VALUES(sire),
                dam = VALUES(dam),
                maternal_sire = VALUES(maternal_sire),
                total_starts = VALUES(total_starts),
                total_wins = VALUES(total_wins),
                total_seconds = VALUES(total_seconds),
                total_thirds = VALUES(total_thirds),
                total_prize_money = VALUES(total_prize_money),
                season_prize_money = VALUES(season_prize_money),
                last_sync_date = VALUES(last_sync_date),
                updated_at = VALUES(updated_at)
            ");
            
            $stmt->execute([
                $info['horse_code'],
                $info['name_ch'],
                $info['age'],
                $info['sex'],
                $info['colour'],
                $info['import_type'],
                $info['country_of_origin'],
                $info['trainer_name_ch'],
                $info['owner_name_ch'],
                $info['current_rating'],
                $info['season_start_rating'],
                $info['sire'],
                $info['dam'],
                $info['maternal_sire'],
                $info['total_starts'],
                $info['total_wins'],
                $info['total_seconds'],
                $info['total_thirds'],
                $info['total_prize_money'],
                $info['season_prize_money'],
                $today,
                $now
            ]);
            
            // 保存战绩
            $perfStmt = $this->db->prepare("
                INSERT INTO hkracing_horse_performances 
                (horse_code, season, race_no, finishing_position, race_date, venue_code,
                 course, distance, going, class, barrier_draw, rating_before,
                 trainer_name_ch, jockey_name_ch, margin, win_odds, actual_weight,
                 running_position, finishing_time, horse_weight, gear)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                finishing_position = VALUES(finishing_position),
                margin = VALUES(margin),
                win_odds = VALUES(win_odds),
                actual_weight = VALUES(actual_weight),
                running_position = VALUES(running_position),
                finishing_time = VALUES(finishing_time),
                horse_weight = VALUES(horse_weight),
                gear = VALUES(gear)
            ");
            
            foreach ($data['performances'] as $perf) {
                $perfStmt->execute([
                    $perf['horse_code'],
                    $perf['season'],
                    $perf['race_no'],
                    $perf['finishing_position'],
                    $perf['race_date'],
                    $perf['venue_code'],
                    $perf['course'],
                    $perf['distance'],
                    $perf['going'],
                    $perf['class'],
                    $perf['barrier_draw'],
                    $perf['rating_before'],
                    $perf['trainer_name_ch'],
                    $perf['jockey_name_ch'],
                    $perf['margin'],
                    $perf['win_odds'],
                    $perf['actual_weight'],
                    $perf['running_position'],
                    $perf['finishing_time'],
                    $perf['horse_weight'],
                    $perf['gear']
                ]);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("保存马匹历史数据失败 ({$horseCode}): " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 从数据库获取马匹历史数据
     */
    public function getHorseHistory($horseCode) {
        $stmt = $this->db->prepare("SELECT * FROM hkracing_horses WHERE horse_code = ?");
        $stmt->execute([$horseCode]);
        $horse = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($horse) {
            $stmt = $this->db->prepare("
                SELECT * FROM hkracing_horse_performances 
                WHERE horse_code = ? 
                ORDER BY race_date DESC, race_no DESC
            ");
            $stmt->execute([$horseCode]);
            $horse['performances'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $horse;
    }
}
?>