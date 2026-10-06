<?php
// HKJC_meetingposter.php - 赛马海报生成器

require_once 'lib/constants.php';

class HKJCRacingPoster {
    private $pdo;
    private $imageDir = 'image/horseracing/';
    
    public function __construct() {
        global $dbConfig;
        $this->pdo = new PDO(
            "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
            $dbConfig['user'],
            $dbConfig['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // 创建目录
        if (!file_exists($this->imageDir)) {
            mkdir($this->imageDir, 0777, true);
        }
    }
    
    /**
     * 生成单场赛事的海报
     */
    public function generateRacePoster($date, $venue, $raceNo, $raceData) {
        $filename = $this->imageDir . "{$venue}_{$date}_R{$raceNo}_" . date('Ymd_His') . ".png";
        
        // 创建画布
        $width = 1200;
        $height = 800;
        $img = imagecreatetruecolor($width, $height);
        
        // 颜色定义
        $colors = $this->getColors($img);
        
        // 背景
        imagefill($img, 0, 0, $colors['bg_dark']);
        
        // 绘制头部渐变
        $this->drawHeader($img, $colors, $date, $venue, $raceNo, $raceData);
        
        // 绘制表格
        $this->drawTable($img, $colors, $raceData);
        
        // 保存图片
        imagepng($img, $filename);
        imagedestroy($img);
        
        // 记录到数据库
        $this->logPoster($date, $venue, $raceNo, $filename);
        
        return $filename;
    }
    
    /**
     * 生成所有赛事的海报
     */
    public function generateAllPosters($date, $venue) {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT race_no
            FROM hkracing_races r
            JOIN hkracing_meetings m ON r.meeting_id = m.id
            WHERE m.date = ? AND m.venue_code = ?
            ORDER BY race_no
        ");
        $stmt->execute([$date, $venue]);
        $races = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $generated = [];
        foreach ($races as $race) {
            $raceData = $this->getRaceData($date, $venue, $race['race_no']);
            if ($raceData) {
                $filename = $this->generateRacePoster($date, $venue, $race['race_no'], $raceData);
                $generated[] = $filename;
            }
        }
        
        return $generated;
    }
    
    /**
     * 获取赛事数据
     */
    private function getRaceData($date, $venue, $raceNo) {
        $stmt = $this->pdo->prepare("
            SELECT 
                r.race_name_ch,
                r.distance,
                r.post_time,
                ru.runner_no,
                ru.name_ch as horse_name,
                ru.jockey_name_ch as jockey,
                ru.trainer_name_ch as trainer,
                ru.barrier_draw_number as draw,
                ru.handicap_weight as weight,
                ohd.win_odds,
                ohd.win_odds_drop,
                h.total_starts,
                h.total_wins,
                CASE 
                    WHEN h.total_starts > 0 THEN ROUND(h.total_wins / h.total_starts * 100, 1)
                    ELSE 0
                END as win_rate
            FROM hkracing_races r
            JOIN hkracing_meetings m ON r.meeting_id = m.id
            JOIN hkracing_runners ru ON ru.race_id = r.id
            LEFT JOIN hkracing_horses h ON h.horse_code = ru.horse_code
            LEFT JOIN (
                SELECT DISTINCT runner_no, win_odds, win_odds_drop
                FROM hkracing_odds_horse_details 
                WHERE race_date = ? AND venue_code = ? AND race_no = ? AND odds_type = 'Curr'
                ORDER BY captured_at DESC
                LIMIT 1
            ) ohd ON ohd.runner_no = ru.runner_no
            WHERE m.date = ? AND m.venue_code = ? AND r.race_no = ?
            ORDER BY CAST(ru.runner_no AS UNSIGNED)
        ");
        $stmt->execute([$date, $venue, $raceNo, $date, $venue, $raceNo]);
        
        return [
            'info' => [
                'date' => $date,
                'venue' => $venue,
                'race_no' => $raceNo,
                'race_name' => $stmt->fetch(PDO::FETCH_ASSOC)['race_name_ch'] ?? '',
                'distance' => $stmt->fetch(PDO::FETCH_ASSOC)['distance'] ?? 0
            ],
            'runners' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    }
    
    private function getColors($img) {
        return [
            'bg_dark' => imagecolorallocate($img, 15, 25, 45),
            'bg_light' => imagecolorallocate($img, 30, 40, 60),
            'header_bg' => imagecolorallocate($img, 45, 55, 75),
            'gold' => imagecolorallocate($img, 255, 215, 0),
            'white' => imagecolorallocate($img, 255, 255, 255),
            'gray' => imagecolorallocate($img, 200, 200, 200),
            'dark_gray' => imagecolorallocate($img, 150, 150, 150),
            'red' => imagecolorallocate($img, 239, 68, 68),
            'green' => imagecolorallocate($img, 16, 185, 129),
            'orange' => imagecolorallocate($img, 245, 158, 11),
            'table_header' => imagecolorallocate($img, 55, 65, 85),
            'table_row_even' => imagecolorallocate($img, 35, 45, 65),
            'table_row_odd' => imagecolorallocate($img, 40, 50, 70),
            'border' => imagecolorallocate($img, 80, 90, 110)
        ];
    }
    private function drawHeader($img, $colors, $date, $venue, $raceNo, $raceData) {
        $font = $this->getFontPath();
        $venueName = $venue == 'ST' ? '沙田' : '跑馬地';
        
        // 标题
        $title = "🏇 {$venueName} 賽馬日 - 第{$raceNo}場 {$raceData['info']['race_name']}";
        if ($font) {
            imagettftext($img, 22, 0, 50, 60, $colors['gold'], $font, $title);
            // 副标题
            $subtitle = "日期: {$date} | 途程: {$raceData['info']['distance']}米 | 開跑時間: -";
            imagettftext($img, 14, 0, 50, 100, $colors['gray'], $font, $subtitle);
        } else {
            // 备用：使用内置字体
            imagestring($img, 5, 50, 50, $title, $colors['gold']);
            imagestring($img, 4, 50, 80, $subtitle, $colors['gray']);
        }
    }
    
    private function drawTable($img, $colors, $raceData) {
        $font = $this->getFontPath();
        $headers = ['檔', '號', '馬匹', '騎師', '練馬師', '賠率', '跌幅', '出賽', '勝率'];
        $x = 30;
        $y = 140;
        $colWidths = [40, 40, 180, 120, 120, 70, 70, 60, 60];
        $rowHeight = 38;
        
        // 表头
        for ($i = 0; $i < count($headers); $i++) {
            $xPos = $x + array_sum(array_slice($colWidths, 0, $i));
            imagefilledrectangle($img, $xPos, $y, $xPos + $colWidths[$i], $y + $rowHeight, $colors['table_header']);
            imagerectangle($img, $xPos, $y, $xPos + $colWidths[$i], $y + $rowHeight, $colors['border']);
            
            if ($font) {
                imagettftext($img, 12, 0, $xPos + 8, $y + 25, $colors['white'], $font, $headers[$i]);
            } else {
                imagestring($img, 4, $xPos + 8, $y + 12, $headers[$i], $colors['white']);
            }
        }
        
        $y += $rowHeight;
        
        // 数据行
        foreach ($raceData['runners'] as $index => $runner) {
            $bgColor = ($index % 2 == 0) ? $colors['table_row_even'] : $colors['table_row_odd'];
            
            $rowData = [
                $runner['draw'] ?? '-',
                $runner['runner_no'],
                mb_substr($runner['horse_name'] ?? '-', 0, 12),
                mb_substr($runner['jockey'] ?? '-', 0, 8),
                mb_substr($runner['trainer'] ?? '-', 0, 8),
                $runner['win_odds'] ? number_format($runner['win_odds'], 1) . '倍' : '-',
                $runner['win_odds_drop'] ? number_format($runner['win_odds_drop'], 1) : '-',
                $runner['total_starts'] ?? 0,
                ($runner['win_rate'] ?? 0) . '%'
            ];
            
            for ($i = 0; $i < count($rowData); $i++) {
                $xPos = $x + array_sum(array_slice($colWidths, 0, $i));
                imagefilledrectangle($img, $xPos, $y, $xPos + $colWidths[$i], $y + $rowHeight, $bgColor);
                imagerectangle($img, $xPos, $y, $xPos + $colWidths[$i], $y + $rowHeight, $colors['border']);
                
                // 文本颜色
                $textColor = $colors['gray'];
                if ($i == 5 && $runner['win_odds'] && $runner['win_odds'] <= 3) {
                    $textColor = $colors['red'];  // 热门赔率红色
                } elseif ($i == 6 && $runner['win_odds_drop'] && $runner['win_odds_drop'] > 0) {
                    $textColor = $colors['green']; // 跌幅绿色
                } elseif ($i == 0 || $i == 1) {
                    $textColor = $colors['white']; // 档位和马号白色
                }
                
                if ($font) {
                    // 根据内容长度调整字体大小
                    $fontSize = (strlen($rowData[$i]) > 8) ? 10 : 11;
                    imagettftext($img, $fontSize, 0, $xPos + 6, $y + 26, $textColor, $font, (string)$rowData[$i]);
                } else {
                    imagestring($img, 3, $xPos + 6, $y + 14, (string)$rowData[$i], $textColor);
                }
            }
            $y += $rowHeight;
            
            // 防止超出画布
            if ($y > 750) break;
        }
        
        // 底部信息
        $footerY = $y + 30;
        if ($font) {
            imagettftext($img, 10, 0, 50, $footerY, $colors['dark_gray'], $font, "數據來源: 香港賽馬會 | 僅供參考 | 生成時間: " . date('Y-m-d H:i:s'));
        } else {
            imagestring($img, 2, 50, $footerY - 10, "數據來源: 香港賽馬會 | 僅供參考", $colors['dark_gray']);
        }
    }
    // private function drawHeader($img, $colors, $date, $venue, $raceNo, $raceData) {
    //     $venueName = $venue == 'ST' ? '沙田' : '跑馬地';
        
    //     // 标题
    //     $title = "🏇 {$venueName} 賽馬日 - 第{$raceNo}場 {$raceData['info']['race_name']}";
    //     imagettftext($img, 24, 0, 50, 60, $colors['gold'], $this->getFontPath(), $title);
        
    //     // 日期和途程
    //     $subtitle = "日期: {$date} | 途程: {$raceData['info']['distance']}米 | 開跑時間: -";
    //     imagettftext($img, 16, 0, 50, 100, $colors['gray'], $this->getFontPath(), $subtitle);
    // }
    
    // private function drawTable($img, $colors, $raceData) {
    //     $headers = ['檔', '號', '馬匹', '騎師', '練馬師', '賠率', '跌幅', '出賽', '勝率'];
    //     $x = 50;
    //     $y = 140;
    //     $colWidths = [40, 40, 180, 120, 120, 70, 70, 70, 70];
    //     $rowHeight = 35;
        
    //     // 表头
    //     for ($i = 0; $i < count($headers); $i++) {
    //         $xPos = $x + array_sum(array_slice($colWidths, 0, $i));
    //         imagefilledrectangle($img, $xPos, $y, $xPos + $colWidths[$i], $y + $rowHeight, $colors['table_header']);
    //         imagerectangle($img, $xPos, $y, $xPos + $colWidths[$i], $y + $rowHeight, $colors['border']);
    //         imagettftext($img, 12, 0, $xPos + 5, $y + 23, $colors['white'], $this->getFontPath(), $headers[$i]);
    //     }
        
    //     $y += $rowHeight;
        
    //     // 数据行
    //     foreach ($raceData['runners'] as $index => $runner) {
    //         $bgColor = ($index % 2 == 0) ? $colors['table_row_even'] : $colors['table_row_odd'];
    //         $rowData = [
    //             $runner['draw'] ?? '-',
    //             $runner['runner_no'],
    //             $runner['horse_name'] ?? '-',
    //             $runner['jockey'] ?? '-',
    //             $runner['trainer'] ?? '-',
    //             $runner['win_odds'] ? number_format($runner['win_odds'], 1) . '倍' : '-',
    //             $runner['win_odds_drop'] ? number_format($runner['win_odds_drop'], 1) : '-',
    //             $runner['total_starts'] ?? 0,
    //             ($runner['win_rate'] ?? 0) . '%'
    //         ];
            
    //         for ($i = 0; $i < count($rowData); $i++) {
    //             $xPos = $x + array_sum(array_slice($colWidths, 0, $i));
    //             imagefilledrectangle($img, $xPos, $y, $xPos + $colWidths[$i], $y + $rowHeight, $bgColor);
    //             imagerectangle($img, $xPos, $y, $xPos + $colWidths[$i], $y + $rowHeight, $colors['border']);
                
    //             $textColor = $colors['gray'];
    //             if ($i == 5 && $runner['win_odds'] && $runner['win_odds'] <= 3) {
    //                 $textColor = $colors['red'];  // 热门赔率红色
    //             } elseif ($i == 6 && $runner['win_odds_drop'] && $runner['win_odds_drop'] > 0) {
    //                 $textColor = $colors['green']; // 跌幅绿色
    //             }
                
    //             imagettftext($img, 11, 0, $xPos + 5, $y + 23, $textColor, $this->getFontPath(), $rowData[$i]);
    //         }
    //         $y += $rowHeight;
    //     }
        
    //     // 绘制底部信息
    //     $footerY = $y + 20;
    //     imagettftext($img, 10, 0, 50, $footerY, $colors['dark_gray'], $this->getFontPath(), "數據來源: 香港賽馬會 | 僅供參考");
    // }
    
    // private function getFontPath() {
    //     // 使用系统字体或下载中文字体
    //     $fontPaths = [
    //         '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
    //         'C:\Windows\Fonts\Arial.ttf',
    //         '/System/Library/Fonts/Helvetica.ttc'
    //     ];
        
    //     foreach ($fontPaths as $path) {
    //         if (file_exists($path)) {
    //             return $path;
    //         }
    //     }
    //     return 4; // GD 内置字体
    // }
    
    /**
     * 获取可用的字体路径（优先使用 .ttf 格式）
     * @return string|false 字体路径，如果不可用则返回 false
     */
    private function getFontPath() {
        // 按优先级排序的字体列表
        $fonts = [
            // 1. 首选：体积小、兼容性好的 Noto Sans CJK JP (TTF)
            __DIR__ . '/fonts/NotoSansCJKjp-Regular.ttf',
            // 2. 次选：体积小、兼容性好的 Source Han Sans SC (TTF)
            __DIR__ . '/fonts/SourceHanSansSC-Regular.ttf',
            // 3. 备选：文泉驿微米黑 (TTC)
            __DIR__ . '/fonts/wqy-microhei.ttc',
            // 4. 后备：OTF 格式（通常也可以使用，但优先级较低）
            __DIR__ . '/fonts/NotoSansCJKjp-Regular.otf',
            __DIR__ . '/fonts/SourceHanSansSC-Regular.otf',
        ];
        
        foreach ($fonts as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        // 如果所有字体都不存在，返回 false，触发降级处理
        return false;
    }
    
    /**
     * 安全的绘制文本函数（包含降级处理）
     * @param resource $img 图像资源
     * @param int $size 字体大小
     * @param int $x X坐标
     * @param int $y Y坐标
     * @param int $color 颜色
     * @param string $text 文本
     * @param bool $bold 是否加粗
     */
    private function safeDrawText($img, $size, $x, $y, $color, $text, $bold = false) {
        $font = $this->getFontPath();
        $fontSize = $bold ? $size + 2 : $size;
        
        // 对中文进行简单的长度截取，防止超出边界
        $text = mb_substr($text, 0, 15);
        
        // 如果成功获取到字体路径，尝试使用 imagettftext
        if ($font) {
            // 使用 @ 屏蔽可能的警告，如果失败则自动降级
            if (@imagettftext($img, $fontSize, 0, $x, $y, $color, $font, $text)) {
                return;
            }
        }
        
        // 降级方案：使用 GD 内置字体
        $gdFontMap = [10 => 5, 12 => 5, 14 => 5, 16 => 5, 18 => 5, 20 => 5];
        $gdFont = $gdFontMap[$size] ?? 4;
        // 调整 Y 坐标以适配内置字体
        $yOffset = [1 => 8, 2 => 12, 3 => 14, 4 => 16, 5 => 18];
        $adjustedY = $y - ($yOffset[$gdFont] ?? 16);
        imagestring($img, $gdFont, $x, $adjustedY, $text, $color);
    }
    
    private function logPoster($date, $venue, $raceNo, $filename) {
        $stmt = $this->pdo->prepare("
            INSERT INTO hkracing_poster_log (race_date, venue_code, race_no, image_path, image_filename, status)
            VALUES (?, ?, ?, ?, ?, 'generated')
            ON DUPLICATE KEY UPDATE
            image_path = VALUES(image_path),
            image_filename = VALUES(image_filename),
            status = 'generated',
            created_at = NOW()
        ");
        $stmt->execute([$date, $venue, $raceNo, $filename, basename($filename)]);
    }
}

// API 入口
if (basename($_SERVER['SCRIPT_FILENAME']) == 'HKJC_meetingposter.php') {
    $action = $_GET['action'] ?? '';
    $poster = new HKJCRacingPoster();
    
    if ($action == 'generate' && isset($_GET['date']) && isset($_GET['venue'])) {
        $files = $poster->generateAllPosters($_GET['date'], $_GET['venue']);
        echo json_encode(['success' => true, 'files' => $files]);
    }
}
?>