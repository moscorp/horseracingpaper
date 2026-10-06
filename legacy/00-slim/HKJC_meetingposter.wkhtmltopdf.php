<?php
// HKJC_meetingposter.php - 使用 HTML 转图片生成高质量海报

require_once 'lib/constants.php';

class HKJCRacingPoster {
    private $pdo;
    private $imageDir = 'image/horseracing/';
    private $tempHtmlDir = 'temp/html/';
    
    public function __construct() {
        global $dbConfig;
        $this->pdo = new PDO(
            "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
            $dbConfig['user'],
            $dbConfig['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // 创建目录
        foreach ([$this->imageDir, $this->tempHtmlDir] as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
        }
    }
    
    /**
     * 生成单场赛事的海报（基于 HTML）
     */
    public function generateRacePoster($date, $venue, $raceNo) {
        // 1. 获取赛事数据
        $raceData = $this->getRaceData($date, $venue, $raceNo);
        if (!$raceData) {
            return false;
        }
        
        // 2. 生成 HTML 内容
        $html = $this->generatePosterHTML($date, $venue, $raceNo, $raceData);
        
        // 3. 保存 HTML 到临时文件
        $htmlFile = $this->tempHtmlDir . "{$venue}_{$date}_R{$raceNo}.html";
        file_put_contents($htmlFile, $html);
        
        // 4. 转换为图片
        $filename = $this->imageDir . "{$venue}_{$date}_R{$raceNo}_" . date('Ymd_His') . ".png";
        
        // 使用 wkhtmltoimage 转换
        $cmd = "wkhtmltoimage --width 1200 --quality 100 \"{$htmlFile}\" \"{$filename}\" 2>&1";
        exec($cmd, $output, $returnCode);
        
        if ($returnCode !== 0) {
            error_log("wkhtmltoimage 错误: " . implode("\n", $output));
            return false;
        }
        
        // 5. 记录到数据库
        $this->logPoster($date, $venue, $raceNo, $filename);
        
        // 6. 清理临时文件
        unlink($htmlFile);
        
        return $filename;
    }
    
    /**
     * 生成海报 HTML（复用 analysis_upcoming 的样式）
     */
    private function generatePosterHTML($date, $venue, $raceNo, $raceData) {
        $venueName = $venue == 'ST' ? '沙田' : '跑馬地';
        
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>賽馬分析 - ' . $venueName . ' 第' . $raceNo . '場</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                body {
                    font-family: "Noto Sans CJK JP", "Source Han Sans SC", "Microsoft YaHei", sans-serif;
                    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
                    padding: 20px;
                    width: 1150px;
                    margin: 0 auto;
                }
                .race-container {
                    background: white;
                    border-radius: 16px;
                    overflow: hidden;
                    box-shadow: 0 20px 25px -12px rgba(0,0,0,0.3);
                }
                .race-header {
                    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
                    color: white;
                    padding: 20px 24px;
                }
                .race-header h3 {
                    font-size: 1.5rem;
                    margin-bottom: 8px;
                }
                .race-header .info {
                    font-size: 0.85rem;
                    opacity: 0.8;
                    display: inline-block;
                    margin-right: 24px;
                }
                .class-label {
                    display: inline-block;
                    padding: 4px 12px;
                    border-radius: 20px;
                    font-size: 0.75rem;
                    font-weight: 600;
                    background: #3b82f6;
                }
                .table-wrapper {
                    overflow-x: auto;
                    padding: 20px;
                }
                .sortable-table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 0.75rem;
                }
                .sortable-table th {
                    background: #f8fafc;
                    color: #1e293b;
                    padding: 12px 8px;
                    text-align: center;
                    font-weight: 600;
                    border-bottom: 2px solid #e2e8f0;
                }
                .sortable-table td {
                    padding: 10px 8px;
                    border-bottom: 1px solid #f1f5f9;
                    text-align: center;
                    color: #334155;
                }
                .draw-cell { font-weight: 700; }
                .runner-no-cell { font-weight: 700; }
                .horse-name { text-align: left; font-weight: 700; }
                .jockey-name, .trainer-name { text-align: left; }
                .odds-cell { color: #dc2626; font-weight: 700; }
                .heart-icon { color: #e94560; margin-left: 4px; }
                .trainer-count, .jockey-count { color: #888; font-size: 10px; }
                .rank-1st-number { color: #dc2626; font-weight: bold; font-size: 1rem; }
                .rank-2nd-number { color: #f97316; font-weight: bold; }
                .rank-3rd-number { color: #eab308; font-weight: bold; }
                .rank-4th-number { color: #22c55e; font-weight: bold; }
                .footer {
                    background: #f8fafc;
                    padding: 12px 20px;
                    text-align: center;
                    font-size: 0.7rem;
                    color: #64748b;
                    border-top: 1px solid #e2e8f0;
                }
            </style>
        </head>
        <body>
            <div class="race-container">
                <div class="race-header">
                    <h3>🏇 ' . $venueName . ' 賽馬日 - 第' . $raceNo . '場 ' . htmlspecialchars($raceData['info']['race_name']) . '</h3>
                    <div class="info">📅 ' . $date . '</div>
                    <div class="info">🏟️ ' . $venueName . '</div>
                    <div class="info">📏 ' . $raceData['info']['distance'] . '米</div>
                </div>
                
                <div class="table-wrapper">
                    <table class="sortable-table">
                        <thead>
                            <tr>
                                <th>檔</th><th>號</th><th>馬匹</th><th>騎師</th><th>練馬師</th>
                                <th>賠率</th><th>跌幅</th><th>出賽</th><th>勝率</th>
                            </tr>
                        </thead>
                        <tbody>';
        
        foreach ($raceData['runners'] as $runner) {
            // 马号颜色（根据时间排名）
            $runnerNoClass = $this->getRunnerNumberClass($runner, $raceData['timeRankMap'] ?? []);
            
            $html .= '<tr>';
            $html .= '<td class="draw-cell">' . ($runner['draw'] ?? '-') . '</td>';
            $html .= '<td class="runner-no-cell ' . $runnerNoClass . '">' . $runner['runner_no'] . '</td>';
            $html .= '<td class="horse-name">' . htmlspecialchars($runner['horse_name'] ?? '-');
            if ($runner['is_hot'] ?? false) $html .= ' <span class="heart-icon">❤️</span>';
            $html .= '</td>';
            $html .= '<td class="jockey-name">' . htmlspecialchars($runner['jockey'] ?? '-') . '</td>';
            $html .= '<td class="trainer-name">' . htmlspecialchars($runner['trainer'] ?? '-') . '</td>';
            
            // 赔率
            $oddsClass = ($runner['win_odds'] && $runner['win_odds'] <= 3) ? 'odds-cell' : '';
            $html .= '<td class="' . $oddsClass . '">' . ($runner['win_odds'] ? number_format($runner['win_odds'], 1) . '倍' : '-') . '</td>';
            
            // 跌幅
            $dropClass = ($runner['win_odds_drop'] && $runner['win_odds_drop'] > 0) ? 'style="color:#10b981;"' : '';
            $html .= '<td ' . $dropClass . '>' . ($runner['win_odds_drop'] ? number_format($runner['win_odds_drop'], 1) : '-') . '</td>';
            
            $html .= '<td>' . ($runner['total_starts'] ?? 0) . '</td>';
            $html .= '<td>' . ($runner['win_rate'] ?? 0) . '%</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody>
                    </table>
                </div>
                <div class="footer">
                    數據來源: 香港賽馬會 | 僅供參考 | 生成時間: ' . date('Y-m-d H:i:s') . '
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * 获取赛事数据
     */
    private function getRaceData($date, $venue, $raceNo) {
        $stmt = $this->pdo->prepare("
            SELECT 
                r.race_name_ch,
                r.distance,
                ru.runner_no,
                ru.name_ch as horse_name,
                ru.jockey_name_ch as jockey,
                ru.trainer_name_ch as trainer,
                ru.barrier_draw_number as draw,
                ohd.win_odds,
                ohd.win_odds_drop,
                ohd.is_hot_favourite as is_hot,
                h.total_starts,
                ROUND(IFNULL(h.total_wins, 0) / NULLIF(h.total_starts, 0) * 100, 1) as win_rate
            FROM hkracing_races r
            JOIN hkracing_meetings m ON r.meeting_id = m.id
            JOIN hkracing_runners ru ON ru.race_id = r.id
            LEFT JOIN hkracing_horses h ON h.horse_code = ru.horse_code
            LEFT JOIN (
                SELECT runner_no, win_odds, win_odds_drop, is_hot_favourite
                FROM hkracing_odds_horse_details 
                WHERE race_date = ? AND venue_code = ? AND race_no = ? AND odds_type = 'Curr'
                ORDER BY captured_at DESC
                LIMIT 1
            ) ohd ON ohd.runner_no = ru.runner_no
            WHERE m.date = ? AND m.venue_code = ? AND r.race_no = ?
            ORDER BY CAST(ru.runner_no AS UNSIGNED)
        ");
        $stmt->execute([$date, $venue, $raceNo, $date, $venue, $raceNo]);
        
        $runners = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($runners)) {
            return false;
        }
        
        // 获取时间排名
        $timeRankMap = $this->calculateTimeRank($date, $venue, $runners);
        
        return [
            'info' => [
                'race_name' => $runners[0]['race_name_ch'] ?? '',
                'distance' => $runners[0]['distance'] ?? 0
            ],
            'runners' => $runners,
            'timeRankMap' => $timeRankMap
        ];
    }
    
    /**
     * 计算同程时间排名
     */
    private function calculateTimeRank($date, $venue, $runners) {
        // 获取每匹马的最佳时间
        $finishingTimes = [];
        foreach ($runners as $runner) {
            $horseCode = $runner['horse_code'] ?? '';
            if (empty($horseCode)) continue;
            
            $stmt = $this->pdo->prepare("
                SELECT finishing_time
                FROM hkracing_horse_performances
                WHERE horse_code = ?
                  AND distance = ?
                  AND venue_code = ?
                  AND finishing_time IS NOT NULL
                ORDER BY race_date DESC
                LIMIT 1
            ");
            $stmt->execute([$horseCode, $runners[0]['distance'], $venue]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row && $row['finishing_time']) {
                $timeStr = $row['finishing_time'];
                // 解析时间
                if (strpos($timeStr, ':') !== false) {
                    $parts = explode(':', $timeStr);
                    $seconds = intval($parts[0]) * 60 + floatval($parts[1]);
                } elseif (substr_count($timeStr, '.') == 2) {
                    $parts = explode('.', $timeStr);
                    $seconds = intval($parts[0]) * 60 + intval($parts[1]) + intval($parts[2]) / 100;
                } else {
                    $seconds = floatval($timeStr);
                }
                $finishingTimes[$runner['runner_no']] = $seconds;
            }
        }
        
        // 排序
        asort($finishingTimes);
        $rankMap = [];
        $rank = 1;
        foreach ($finishingTimes as $runnerNo => $time) {
            $rankMap[$runnerNo] = $rank++;
        }
        
        return $rankMap;
    }
    
    private function getRunnerNumberClass($runner, $rankMap) {
        $rank = $rankMap[$runner['runner_no']] ?? 0;
        if ($rank == 1) return 'rank-1st-number';
        if ($rank == 2) return 'rank-2nd-number';
        if ($rank == 3) return 'rank-3rd-number';
        if ($rank == 4) return 'rank-4th-number';
        return '';
    }
    
    private function logPoster($date, $venue, $raceNo, $filename) {
        $stmt = $this->pdo->prepare("
            INSERT INTO hkracing_poster_log (race_date, venue_code, race_no, image_path, image_filename, status)
            VALUES (?, ?, ?, ?, ?, 'generated')
            ON DUPLICATE KEY UPDATE
            image_path = VALUES(image_path),
            image_filename = VALUES(image_filename),
            status = 'generated'
        ");
        $stmt->execute([$date, $venue, $raceNo, $filename, basename($filename)]);
    }
}

// API 入口
if (basename($_SERVER['SCRIPT_FILENAME']) == 'HKJC_meetingposter.php') {
    $action = $_GET['action'] ?? '';
    $poster = new HKJCRacingPoster();
    
    if ($action == 'generate' && isset($_GET['date']) && isset($_GET['venue'])) {
        // 获取所有场次
        $stmt = $poster->pdo->prepare("
            SELECT DISTINCT race_no
            FROM hkracing_races r
            JOIN hkracing_meetings m ON r.meeting_id = m.id
            WHERE m.date = ? AND m.venue_code = ?
            ORDER BY race_no
        ");
        $stmt->execute([$_GET['date'], $_GET['venue']]);
        $races = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $generated = [];
        foreach ($races as $race) {
            $filename = $poster->generateRacePoster($_GET['date'], $_GET['venue'], $race['race_no']);
            if ($filename) {
                $generated[] = $filename;
            }
            sleep(1); // 避免资源竞争
        }
        
        echo json_encode(['success' => true, 'files' => $generated, 'count' => count($generated)]);
    }
}
?>