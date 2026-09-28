<?php
/**
 * 试闸信号自动更新脚本 (修正版)
 * 支持 Web 和 CLI 模式，正确识别 force 参数
 */

include_once ("lib/constants.php");
date_default_timezone_set('Asia/Hong_Kong');

// 允许 Web 执行
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

// ========== 统一参数获取 (关键修正) ==========
$type = 'batch';
$raceId = null;
$date = null;
$venue = '';
$force = false;

if (php_sapi_name() === 'cli') {
    // 命令行模式
    $options = getopt("", ["type::", "race_id::", "date::", "venue::", "force"]);
    $type = $options['type'] ?? 'batch';
    $raceId = $options['race_id'] ?? null;
    $date = $options['date'] ?? null;
    $venue = $options['venue'] ?? '';
    $force = isset($options['force']);
} else {
    // Web 模式 - 从 $_GET 获取参数
    $type = isset($_GET['type']) ? $_GET['type'] : 'batch';
    $raceId = isset($_GET['race_id']) ? $_GET['race_id'] : null;
    $date = isset($_GET['date']) ? $_GET['date'] : null;
    $venue = isset($_GET['venue']) ? $_GET['venue'] : '';
    // 关键修正：检查 $_GET['force'] 是否为 1 或 true
    $force = (isset($_GET['force']) && ($_GET['force'] == 1 || $_GET['force'] == 'true'));
}

// 添加调试输出（可选，方便确认参数）
echo "=== 参数信息 ===\n";
echo "运行模式: " . (php_sapi_name() === 'cli' ? 'CLI' : 'Web') . "\n";
echo "type: {$type}\n";
echo "force: " . ($force ? '是' : '否') . "\n";
echo "date: " . ($date ?: '自动') . "\n";
echo "venue: " . ($venue ?: '全部') . "\n";
echo "================\n\n";

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("SET time_zone = '+08:00'");
    
    echo "[" . date('Y-m-d H:i:s') . "] 开始更新试闸信号...\n";
    
    if ($type == 'batch') {
        processBatchBarrierSignals($pdo, $date, $venue, $force);
    } else {
        echo "未知的类型: {$type}\n";
    }
    
    echo "\n[" . date('Y-m-d H:i:s') . "] 试闸信号更新完成\n";
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    error_log("Barrier Signal Error: " . $e->getMessage());
    exit(1);
}

/**
 * 批次处理试闸信号 (修正版)
 */
function processBatchBarrierSignals($pdo, $date, $venue, $force = false) {
    echo "=== 批次更新试闸信号 ===\n";
    echo "日期: " . ($date ?: '今天及未來7天') . "\n";
    echo "馬場: " . ($venue ?: '全部') . "\n";
    echo "強制更新: " . ($force ? '是' : '否') . "\n\n";
    
    // 获取需要处理的赛事
    $sql = "
        SELECT DISTINCT r.id, r.race_no, m.date, m.venue_code, r.post_time
        FROM hkracing_races r
        JOIN hkracing_meetings m ON r.meeting_id = m.id
        JOIN hkracing_runners ru ON ru.race_id = r.id
        WHERE m.venue_code IN ('ST', 'HV')
    ";
    
    $params = [];
    if ($date) {
        // 关键修正：当指定了日期，就只处理该日期
        $sql .= " AND m.date = :date";
        $params[':date'] = $date;
    } else {
        // 没有指定日期：处理今天及未来7天的赛事
        $sql .= " AND m.date >= CURDATE() AND m.date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
    }
    
    if ($venue) {
        $sql .= " AND m.venue_code = :venue";
        $params[':venue'] = $venue;
    }
    
    // 如果不强制更新，只处理还没有试闸信号的赛事
    if (!$force) {
        $sql .= " AND NOT EXISTS (
            SELECT 1 FROM hkracing_v2_score s 
            WHERE s.race_id = r.id AND s.barrier_trial_signal IS NOT NULL
        )";
    }
    
    $sql .= " GROUP BY r.id ORDER BY m.date, r.race_no";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $races = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "找到 " . count($races) . " 場賽事需要更新试闸信号\n\n";
    
    if (count($races) == 0) {
        echo "没有需要更新的赛事。\n";
        return;
    }
    
    $updated = 0;
    foreach ($races as $race) {
        echo "處理: {$race['date']} - {$race['venue_code']} 第{$race['race_no']}場\n";
        
        try {
            $updatedCount = updateRaceBarrierSignals($pdo, $race['id'], $race['post_time']);
            echo "  ✅ 更新了 {$updatedCount} 匹馬的试闸信号\n";
            $updated++;
        } catch (Exception $e) {
            echo "  ❌ 錯誤: " . $e->getMessage() . "\n";
        }
        
        usleep(100000);
    }
    
    echo "\n完成！更新了 {$updated} 場賽事的试闸信号\n";
}

/**
 * 更新一场赛事所有马匹的试闸信号 (保持不变，功能强大)
 */
function updateRaceBarrierSignals($pdo, $raceId, $racePostTime) {
    // 获取该赛事的所有马匹
    $stmt = $pdo->prepare("
        SELECT 
            ru.runner_no,
            ru.horse_code,
            ru.name_ch as horse_name
        FROM hkracing_runners ru
        WHERE ru.race_id = ?
    ");
    $stmt->execute([$raceId]);
    $runners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($runners)) {
        return 0;
    }
    
    $updatedCount = 0;
    
    foreach ($runners as $runner) {
        // 查询最近14天的试闸记录
        $trialStmt = $pdo->prepare("
            SELECT Comment, barrierday, Going
            FROM barrierresult
            WHERE Horse = ?
              AND barrierday >= DATE_SUB(?, INTERVAL 14 DAY)
            ORDER BY barrierday DESC LIMIT 1
        ");
        $trialStmt->execute([$runner['horse_name'], $racePostTime]);
        $trial = $trialStmt->fetch(PDO::FETCH_ASSOC);
        
        $signalScore = 0;
        $signalLevel = null;
        $signalKeyword = null;
        $trialDate = null;
        $trialComment = null;
        
        if ($trial && !empty($trial['Comment'])) {
            $comment = $trial['Comment'];
            $trialComment = $comment;
            $trialDate = $trial['barrierday'];
            
            // A级信号：强意图（+20分）
            $aKeywords = ['姿態從容', '分段加快', '騎落仲有', '催策', '餘勢不俗', '大勝', '越贏越遠', '力策奪冠'];
            foreach ($aKeywords as $keyword) {
                if (strpos($comment, $keyword) !== false) {
                    $signalScore = 20;
                    $signalLevel = 'A';
                    $signalKeyword = $keyword;
                    break;
                }
            }
            
            // B级信号：中等意图（+12分）
            if ($signalLevel === null) {
                $bKeywords = ['從容', '輕鬆', '追近', '不俗', '走勢持續', '反應良好'];
                foreach ($bKeywords as $keyword) {
                    if (strpos($comment, $keyword) !== false) {
                        $signalScore = 12;
                        $signalLevel = 'B';
                        $signalKeyword = $keyword;
                        break;
                    }
                }
            }
            
            // C级信号：一般意图（+6分）
            if ($signalLevel === null) {
                $cKeywords = ['不過不失', '中規中矩', '順走', '跟跑'];
                foreach ($cKeywords as $keyword) {
                    if (strpos($comment, $keyword) !== false) {
                        $signalScore = 6;
                        $signalLevel = 'C';
                        $signalKeyword = $keyword;
                        break;
                    }
                }
            }
        }
        
        // 更新评分表中的试闸信号字段 (更新所有版本)
        $updateStmt = $pdo->prepare("
            UPDATE hkracing_v2_score 
            SET barrier_trial_signal = :signal,
                barrier_trial_level = :level,
                barrier_trial_keyword = :keyword,
                barrier_trial_date = :trial_date,
                barrier_trial_comment = :comment,
                last_updated = NOW()
            WHERE race_id = :race_id 
              AND runner_no = :runner_no
        ");
        
        $updateStmt->execute([
            ':signal' => $signalScore,
            ':level' => $signalLevel,
            ':keyword' => $signalKeyword,
            ':trial_date' => $trialDate,
            ':comment' => $trialComment,
            ':race_id' => $raceId,
            ':runner_no' => $runner['runner_no']
        ]);
        
        if ($signalScore > 0) {
            echo "    🐎 {$runner['horse_name']}: {$signalLevel}级信号 ({$signalKeyword}) +{$signalScore}分\n";
        }
        
        $updatedCount++;
    }
    
    return $updatedCount;
}
?>