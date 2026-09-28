<?php
/**
 * HKJC 赛后回顾分析博文自动发布脚本
 * 
 * 功能：分析每场赛事的预测准确性，找出影响结果的关键因素
 * 
 * 执行方式：
 * php HKJC_race_review.php --type=batch                    # 处理今天已结束的赛事
 * php HKJC_race_review.php --date=2026-05-09               # 处理指定日期
 * php HKJC_race_review.php --type=batch --force            # 强制重新发布
 */

include_once ("lib/constants.php");
require_once "HKJCRacing_score_functions.php";

date_default_timezone_set('Asia/Hong_Kong');

if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

// 兼容 Web 和 CLI
if (php_sapi_name() === 'cli') {
    $options = getopt("", ["type::", "date::", "venue::", "force"]);
    $type = $options['type'] ?? 'batch';
    $date = $options['date'] ?? null;
    $venue = $options['venue'] ?? '';
    $force = isset($options['force']) ? true : false;
} else {
    $type = isset($_GET['type']) ? $_GET['type'] : 'batch';
    $date = isset($_GET['date']) ? $_GET['date'] : null;
    $venue = isset($_GET['venue']) ? $_GET['venue'] : '';
    $force = isset($_GET['force']) ? true : false;
}

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '+08:00'");
    
    processBatchReview($pdo, $date, $venue, $force);
    
    echo "\n[" . date('Y-m-d H:i:s') . "] 赛后回顾发布完成\n";
    
} catch (Exception $e) {
    echo "錯誤: " . $e->getMessage() . "\n";
    error_log("HKJC Race Review Error: " . $e->getMessage());
    exit(1);
}

function processBatchReview($pdo, $date, $venue, $force = false) {
    echo "=== 批次發布賽後回顧 ===\n";
    echo "日期: " . ($date ?: '今天') . "\n";
    echo "馬場: " . ($venue ?: '全部') . "\n";
    echo "強制發布: " . ($force ? '是' : '否') . "\n\n";
    
    // 获取已结束的赛事（有 final_position）
    $sql = "
        SELECT DISTINCT 
            r.id as race_id,
            r.race_no,
            m.date,
            m.venue_code,
            COUNT(DISTINCT ru.horse_code) as horse_count
        FROM hkracing_races r
        JOIN hkracing_meetings m ON r.meeting_id = m.id
        JOIN hkracing_runners ru ON ru.race_id = r.id
        JOIN hkracing_v2_score s ON s.race_id = r.id AND s.score_version = 2
        WHERE m.venue_code IN ('ST', 'HV')
          AND s.final_position IS NOT NULL
          AND s.final_position > 0
    ";
    
    if ($date) {
        $sql .= " AND m.date = :date";
    } else {
        $sql .= " AND m.date = CURDATE()";
    }
    
    if ($venue) {
        $sql .= " AND m.venue_code = :venue";
    }
    
    if (!$force) {
        $sql .= " AND NOT EXISTS (
            SELECT 1 FROM hkracing_review_posts rp 
            WHERE rp.race_date = m.date AND rp.venue_code = m.venue_code
        )";
    }
    
    $sql .= " GROUP BY r.id ORDER BY m.date, r.race_no";
    
    $stmt = $pdo->prepare($sql);
    $params = [];
    if ($date) $params[':date'] = $date;
    if ($venue) $params[':venue'] = $venue;
    $stmt->execute($params);
    $races = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($races)) {
        echo "沒有找到需要回顧的賽事\n";
        return;
    }
    
    // 按日期和馬場分組
    $groupedRaces = [];
    foreach ($races as $race) {
        $key = $race['date'] . '_' . $race['venue_code'];
        if (!isset($groupedRaces[$key])) {
            $groupedRaces[$key] = [
                'date' => $race['date'],
                'venue_code' => $race['venue_code'],
                'races' => []
            ];
        }
        $groupedRaces[$key]['races'][] = $race;
    }
    
    $publishedCount = 0;
    foreach ($groupedRaces as $group) {
        echo "\n--- 處理: {$group['date']} - {$group['venue_code']} ---\n";
        
        $postId = publishRaceReview($pdo, $group['date'], $group['venue_code'], $group['races']);
        
        if ($postId) {
            $publishedCount++;
            echo "✅ 回顧已發布，ID: {$postId}\n";
            recordReviewPost($pdo, $group['date'], $group['venue_code'], $postId);
        } else {
            echo "❌ 發布失敗\n";
        }
        
        sleep(2);
    }
    
    echo "\n成功發布 {$publishedCount} 篇回顧\n";
}

function publishRaceReview($pdo, $date, $venueCode, $races) {
    // 分析每场赛事
    $raceAnalyses = [];
    $totalAccuracy = 0;
    $totalTop3Accuracy = 0;
    
    foreach ($races as $race) {
        $analysis = analyzeRaceResult($pdo, $race['race_id'], $race['race_no'], $date, $venueCode);
        if ($analysis) {
            $raceAnalyses[] = $analysis;
            if ($analysis['hit_champion']) $totalAccuracy++;
            if ($analysis['hit_top3']) $totalTop3Accuracy++;
        }
    }
    
    if (empty($raceAnalyses)) {
        return false;
    }
    
    $raceCount = count($raceAnalyses);
    $championAccuracy = round($totalAccuracy / $raceCount * 100, 1);
    $top3Accuracy = round($totalTop3Accuracy / $raceCount * 100, 1);
    
    // 生成特色图片
    echo "生成特色图片... ";
    $featuredImageId = generateReviewFeaturedImage($date, $venueCode, $championAccuracy, $top3Accuracy);
    echo $featuredImageId ? "成功 (ID: {$featuredImageId})\n" : "失敗\n";
    
    // 生成博文標題和內容
    $title = generateReviewTitle($date, $venueCode, $raceCount, $championAccuracy);
    $content = generateReviewContent($date, $venueCode, $raceAnalyses, $championAccuracy, $top3Accuracy);
    $excerpt = generateReviewExcerpt($raceAnalyses, $championAccuracy);
    
    // 生成 SEO 数据
    $seoTitle = generateReviewSeoTitle($date, $venueCode, $championAccuracy);
    $seoDescription = generateReviewSeoDescription($date, $venueCode, $championAccuracy, $top3Accuracy);
    $seoKeywords = generateReviewSeoKeywords($venueCode);
    
    // 發布到 WordPress
    return postReviewToWordPress($title, $content, $excerpt, $date, $venueCode, $featuredImageId, $seoTitle, $seoDescription, $seoKeywords);
}

function analyzeRaceResult($pdo, $raceId, $raceNo, $date, $venueCode) {
    // 获取所有马匹的评分和结果
    $stmt = $pdo->prepare("
        SELECT 
            s.runner_no,
            s.total_score,
            s.priority,
            s.final_position,
            s.score_win_rate,
            s.score_recent_form,
            s.score_draw_history,
            s.score_trainer_jockey,
            s.score_distance_venue,
            s.score_odds,
            s.score_class_change,
            s.score_trainer_venue,
            s.score_trainer_form,
            s.win_odds_snapshot,
            ru.name_ch as horse_name,
            ru.barrier_draw_number as draw,
            ru.jockey_name_ch as jockey_name,
            ru.trainer_name_ch as trainer_name
        FROM hkracing_v2_score s
        JOIN hkracing_runners ru ON ru.race_id = s.race_id AND ru.runner_no = s.runner_no
        WHERE s.race_id = ? 
          AND s.score_version = 2
        ORDER BY s.priority
    ");
    $stmt->execute([$raceId]);
    $horses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($horses)) {
        return null;
    }
    
    // 找出冠军和优先第一
    $champion = null;
    $topPriority = null;
    $top3Horses = [];
    
    foreach ($horses as $horse) {
        if ($horse['final_position'] == 1) {
            $champion = $horse;
        }
        if ($horse['priority'] == 1) {
            $topPriority = $horse;
        }
        if ($horse['final_position'] <= 3) {
            $top3Horses[] = $horse;
        }
    }
    
    // 分析冠军的关键因素
    $keyFactors = analyzeKeyFactors($champion, $topPriority, $horses);
    
    return [
        'race_no' => $raceNo,
        'champion' => $champion,
        'top_priority' => $topPriority,
        'top3_horses' => $top3Horses,
        'hit_champion' => ($topPriority && $topPriority['runner_no'] == $champion['runner_no']),
        'hit_top3' => in_array($topPriority['runner_no'], array_column($top3Horses, 'runner_no')),
        'key_factors' => $keyFactors,
        'all_horses' => $horses
    ];
}

function analyzeKeyFactors($champion, $topPriority, $allHorses) {
    if (!$champion) return [];
    
    $factors = [];
    
    // 对比冠军和优先第一的各项评分
    if ($topPriority) {
        $scoreDiffs = [
            'win_rate' => $champion['score_win_rate'] - $topPriority['score_win_rate'],
            'recent_form' => $champion['score_recent_form'] - $topPriority['score_recent_form'],
            'draw_history' => $champion['score_draw_history'] - $topPriority['score_draw_history'],
            'trainer_jockey' => $champion['score_trainer_jockey'] - $topPriority['score_trainer_jockey'],
            'distance_venue' => $champion['score_distance_venue'] - $topPriority['score_distance_venue'],
            'odds' => $champion['score_odds'] - $topPriority['score_odds'],
            'trainer_venue' => $champion['score_trainer_venue'] - $topPriority['score_trainer_venue'],
            'trainer_form' => $champion['score_trainer_form'] - $topPriority['score_trainer_form']
        ];
        
        // 找出冠军的优势项
        foreach ($scoreDiffs as $factor => $diff) {
            if ($diff > 5) {
                $factors[] = [
                    'factor' => $factor,
                    'advantage' => 'champion',
                    'diff' => round($diff, 1)
                ];
            } elseif ($diff < -5) {
                $factors[] = [
                    'factor' => $factor,
                    'advantage' => 'predicted',
                    'diff' => round(abs($diff), 1)
                ];
            }
        }
    }
    
    // 检查赔率因素
    if ($champion['win_odds_snapshot'] && $champion['win_odds_snapshot'] > 0) {
        if ($champion['win_odds_snapshot'] >= 10) {
            $factors[] = [
                'factor' => 'odds',
                'advantage' => 'upset',
                'value' => $champion['win_odds_snapshot']
            ];
        } elseif ($champion['win_odds_snapshot'] <= 3) {
            $factors[] = [
                'factor' => 'odds',
                'advantage' => 'favorite',
                'value' => $champion['win_odds_snapshot']
            ];
        }
    }
    
    // 检查档位优势
    if ($champion['draw'] && $champion['draw'] <= 3) {
        $factors[] = [
            'factor' => 'draw',
            'advantage' => 'inside',
            'value' => $champion['draw']
        ];
    } elseif ($champion['draw'] && $champion['draw'] >= 10) {
        $factors[] = [
            'factor' => 'draw',
            'advantage' => 'outside',
            'value' => $champion['draw']
        ];
    }
    
    return $factors;
}

// function generateReviewTitle($date, $venueCode, $raceCount, $accuracy) {
//     $venueName = $venueCode == 'ST' ? '沙田' : '跑馬地';
//     $dateFormatted = date('Y年m月d日', strtotime($date));
//     $accuracyClass = $accuracy >= 50 ? '🎯' : '📊';
    
//     return "🏇 {$dateFormatted} {$venueName} 賽後回顧 - 命中率 {$accuracy}% {$accuracyClass}";
// }
function generateReviewTitle($date, $venueCode, $raceCount, $accuracy) {
    $venueName = $venueCode == 'ST' ? '沙田' : '跑馬地';
    $dateFormatted = date('Y年m月d日', strtotime($date));
    $accuracyClass = $accuracy >= 50 ? '🎯' : '📊';
    
    return "🏇 {$dateFormatted} {$venueName} 賽後回顧 - 命中率 {$accuracy}% {$accuracyClass}";
}

function generateReviewContent($date, $venueCode, $raceAnalyses, $championAccuracy, $top3Accuracy) {
    $venueName = $venueCode == 'ST' ? '沙田' : '跑馬地';
    $dateFormatted = date('Y年m月d日', strtotime($date));
    $weekday = ['日', '一', '二', '三', '四', '五', '六'][date('w', strtotime($date))];
    $raceCount = count($raceAnalyses);
    
    // 准确率评级
    if ($championAccuracy >= 60) {
        $rating = '🔥 極佳 - 預測系統表現出色！';
        $ratingColor = '#10b981';
    } elseif ($championAccuracy >= 40) {
        $rating = '⭐ 良好 - 預測系統有一定參考價值';
        $ratingColor = '#f59e0b';
    } elseif ($championAccuracy >= 20) {
        $rating = '📌 一般 - 需要結合其他因素分析';
        $ratingColor = '#3b82f6';
    } else {
        $rating = '⚠️ 欠佳 - 賽果較難預測，冷門頻出';
        $ratingColor = '#ef4444';
    }
    
    $content = <<<HTML
<div style="max-width:1200px; margin:0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

<div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: white; padding: 30px; border-radius: 16px; margin-bottom: 25px; text-align: center;">
    <h1 style="color: #FFD700; margin: 0;">🏇 {$dateFormatted} {$venueName} 賽後回顧</h1>
    <p style="margin: 10px 0 0;">星期{$weekday} | 共 {$raceCount} 場賽事</p>
</div>

<div style="background: #f8f9fa; border-radius: 16px; padding: 20px; margin-bottom: 25px;">
    <h2>📊 預測準確率總結</h2>
    <div style="display: flex; gap: 30px; flex-wrap: wrap; justify-content: center; margin: 20px 0;">
        <div style="text-align: center;">
            <div style="font-size: 48px; font-weight: bold; color: #667eea;">{$championAccuracy}%</div>
            <div style="color: #666;">🏆 冠軍命中率</div>
            <div style="font-size: 12px; color: #999;">({$raceCount}場中命中的場次)</div>
        </div>
        <div style="text-align: center;">
            <div style="font-size: 48px; font-weight: bold; color: #764ba2;">{$top3Accuracy}%</div>
            <div style="color: #666;">🥇🥈🥉 位置命中率</div>
            <div style="font-size: 12px; color: #999;">(前3名命中率)</div>
        </div>
    </div>
    <div style="background: {$ratingColor}20; padding: 12px; border-radius: 12px; text-align: center;">
        <strong>📈 評級：</strong> {$rating}
    </div>
</div>

<h2>📋 逐場詳細分析</h2>

HTML;
    
    foreach ($raceAnalyses as $analysis) {
        $champion = $analysis['champion'];
        $topPriority = $analysis['top_priority'];
        $hitChampion = $analysis['hit_champion'];
        $hitTop3 = $analysis['hit_top3'];
        $allHorses = $analysis['all_horses'];
        
        $hitClass = $hitChampion ? '#10b981' : ($hitTop3 ? '#f59e0b' : '#ef4444');
        $hitIcon = $hitChampion ? '✅ 命中冠軍！' : ($hitTop3 ? '⚠️ 僅命中位置' : '❌ 未中');
        
        $content .= <<<HTML
<div style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; flex-wrap: wrap;">
        <h3 style="margin: 0; color: #667eea;">第{$analysis['race_no']}場</h3>
        <span style="background: {$hitClass}; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px;">{$hitIcon}</span>
    </div>
    
    <h4>📊 全部馬匹排名</h4>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <th style="padding: 10px; text-align: center;">排名</th>
                    <th style="padding: 10px; text-align: center;">馬號</th>
                    <th style="padding: 10px; text-align: left;">馬名</th>
                    <th style="padding: 10px; text-align: center;">檔位</th>
                    <th style="padding: 10px; text-align: center;">評分</th>
                    <th style="padding: 10px; text-align: center;">賠率</th>
                    <th style="padding: 10px; text-align: center;">實際名次</th>
                    <th style="padding: 10px; text-align: center;">結果</th>
                </tr>
            </thead>
            <tbody>
HTML;
        
        foreach ($allHorses as $horse) {
            $rank = $horse['priority'];
            $isChampion = ($horse['runner_no'] == $champion['runner_no']);
            $isTopPriority = ($horse['runner_no'] == $topPriority['runner_no']);
            $isTop3 = ($horse['final_position'] <= 3);
            
            $rowBg = '';
            $resultIcon = '';
            if ($isChampion) {
                $rowBg = 'background: #d1fae5;';  // 冠军 - 浅绿色
                $resultIcon = '🏆 冠軍';
            } elseif ($isTopPriority && $horse['final_position'] == 1) {
                $rowBg = 'background: #c7d2fe;';  // 预测首選且冠軍 - 浅紫色
                $resultIcon = '✅ 預測命中';
            } elseif ($isTopPriority) {
                $rowBg = 'background: #fef3c7;';  // 预测首選 - 浅橙色
                $resultIcon = '⭐ 優先首選';
            } elseif ($isTop3) {
                $rowBg = 'background: #e0f2fe;';  // 位置 - 浅蓝色
                $resultIcon = '🥇🥈🥉 位置';
            } elseif ($horse['final_position'] > 0) {
                $resultIcon = "第{$horse['final_position']}名";
            } else {
                $resultIcon = '-';
            }
            
            $odds = $horse['win_odds_snapshot'] ?: '-';
            
            $content .= <<<HTML
                <tr style="border-bottom: 1px solid #e0e0e0; {$rowBg}">
                    <td style="padding: 8px; text-align: center; font-weight: bold;">{$rank}</td>
                    <td style="padding: 8px; text-align: center; font-weight: bold;">{$horse['runner_no']}</td>
                    <td style="padding: 8px; text-align: left;">{$horse['horse_name']}</td>
                    <td style="padding: 8px; text-align: center;">{$horse['draw']}</td>
                    <td style="padding: 8px; text-align: center;">{$horse['total_score']}</td>
                    <td style="padding: 8px; text-align: center;">{$odds}</td>
                    <td style="padding: 8px; text-align: center; font-weight: bold;">{$horse['final_position']}</td>
                    <td style="padding: 8px; text-align: center;">{$resultIcon}</td>
                </tr>
HTML;
        }
        
        $content .= '</tbody></table></div>';
        
        // 关键因素分析
        if (!empty($analysis['key_factors'])) {
            $content .= '<div style="background: #f0fdf4; border-radius: 8px; padding: 12px; margin-top: 15px;">';
            $content .= '<strong>🔍 關鍵因素分析：</strong><br>';
            foreach ($analysis['key_factors'] as $factor) {
                if ($factor['factor'] == 'odds') {
                    if ($factor['advantage'] == 'upset') {
                        $content .= "• 爆冷因素：冷門賠率 ({$factor['value']}倍) 成功突圍<br>";
                    } else {
                        $content .= "• 熱門因素：大熱門 ({$factor['value']}倍) 順利勝出<br>";
                    }
                } elseif ($factor['factor'] == 'draw') {
                    if ($factor['advantage'] == 'inside') {
                        $content .= "• 檔位優勢：內檔 ({$factor['value']}檔) 起步有利<br>";
                    } else {
                        $content .= "• 檔位優勢：外檔 ({$factor['value']}檔) 後上突擊<br>";
                    }
                } else {
                    $factorNames = [
                        'win_rate' => '勝率', 'recent_form' => '近況', 'draw_history' => '檔位表現',
                        'trainer_jockey' => '騎練合作', 'distance_venue' => '同程能力', 'odds' => '賠率',
                        'trainer_venue' => '練馬師場地', 'trainer_form' => '練馬師近態'
                    ];
                    $name = $factorNames[$factor['factor']] ?? $factor['factor'];
                    if ($factor['advantage'] == 'champion') {
                        $content .= "• 冠軍優勢：{$name} 比預測首選高分 (+{$factor['diff']})<br>";
                    } else {
                        $content .= "• 預測失誤：{$name} 預測首選比冠軍高分 (+{$factor['diff']})<br>";
                    }
                }
            }
            $content .= '</div>';
        }
        
        $content .= '</div>';
    }
    
    $updatedTime = date('Y-m-d H:i:s');
    
    $content .= <<<HTML
<div style="background: #e9ecef; border-radius: 12px; padding: 15px; margin-top: 20px;">
    <h3>📈 總結</h3>
    <ul>
        <li>冠軍命中率：<strong>{$championAccuracy}%</strong></li>
        <li>位置命中率：<strong>{$top3Accuracy}%</strong></li>
        <li>最具優勢評分因素：賠率、近況、檔位表現</li>
        <li>需改善因素：班次變動、練馬師場地適性</li>
    </ul>
    <p style="font-size: 12px; color: #666; margin-top: 10px;">⚠️ 以上分析僅供參考，投注前請考慮即時賠率及場地狀況變化</p>
</div>

<div style="text-align: center; font-size: 12px; color: #999; margin-top: 20px;">
    最後更新: {$updatedTime}
</div>
</div>
HTML;

    return $content;
}

// function generateReviewExcerpt($raceAnalyses, $accuracy) {
//     $hitCount = 0;
//     foreach ($raceAnalyses as $analysis) {
//         if ($analysis['hit_champion']) $hitCount++;
//     }
    
//     return "🏇 賽後回顧：{$hitCount}/" . count($raceAnalyses) . " 場命中冠軍 ({$accuracy}%)。分析每場賽事的預測準確性及關鍵影響因素。";
// }
function generateReviewExcerpt($raceAnalyses, $accuracy) {
    $hitCount = 0;
    $hitTop3Count = 0;
    $upsetCount = 0;
    $favoriteCount = 0;
    
    foreach ($raceAnalyses as $analysis) {
        if ($analysis['hit_champion']) $hitCount++;
        if ($analysis['hit_top3']) $hitTop3Count++;
        
        if ($analysis['champion'] && $analysis['champion']['win_odds_snapshot']) {
            $odds = $analysis['champion']['win_odds_snapshot'];
            if ($odds >= 10) $upsetCount++;
            elseif ($odds <= 4) $favoriteCount++;
        }
    }
    
    $total = count($raceAnalyses);
    $top3Rate = round($hitTop3Count / $total * 100, 1);
    
    // 第一行：总体统计
    $excerpt = "🏇 賽後回顧：";
    $excerpt .= "冠軍命中率 {$accuracy}% ({$hitCount}/{$total}) | ";
    $excerpt .= "位置命中率 {$top3Rate}% ({$hitTop3Count}/{$total})\n";
    
    // 第二行：爆冷/热门统计
    if ($upsetCount > 0 || $favoriteCount > 0) {
        $excerpt .= "📊 賽果統計：";
        if ($upsetCount > 0) $excerpt .= "爆冷場次 {$upsetCount} 場";
        if ($upsetCount > 0 && $favoriteCount > 0) $excerpt .= " | ";
        if ($favoriteCount > 0) $excerpt .= "熱門勝出 {$favoriteCount} 場";
        $excerpt .= "\n";
    }
    
    // 第三行起：逐场结果（限制显示前5场，避免过长）
    $excerpt .= "📋 精選場次：";
    $displayCount = 0;
    foreach ($raceAnalyses as $analysis) {
        if ($displayCount >= 5) {
            $remaining = count($raceAnalyses) - 5;
            if ($remaining > 0) {
                $excerpt .= " ... 等 {$remaining} 場";
            }
            break;
        }
        
        $champion = $analysis['champion'];
        $topPriority = $analysis['top_priority'];
        $hitIcon = $analysis['hit_champion'] ? '✅' : '❌';
        
        $excerpt .= " R{$analysis['race_no']}:{$hitIcon} ";
        $excerpt .= "冠{$champion['runner_no']}號";
        
        if ($champion['win_odds_snapshot'] && $champion['win_odds_snapshot'] >= 10) {
            $excerpt .= "(🔥爆冷)";
        } elseif ($champion['win_odds_snapshot'] && $champion['win_odds_snapshot'] <= 4) {
            $excerpt .= "(⭐熱門)";
        }
        
        $displayCount++;
    }
    
    $excerpt .= "\n\n📊 分析基於：同程勝率、檔位表現、近況狀態、賠率因素等13項評分指標。";
    
    return $excerpt;
}
function postReviewToWordPress($title, $content, $excerpt, $date, $venueCode, $featuredImageId = null, $seoTitle = null, $seoDescription = null, $seoKeywords = null) {
    $wpUrl = 'https://buycarl.com/wp-json/wp/v2/posts';
    $username = getWpUsername();
    $password = getWpAppPassword();
    
    $categoryId = getReviewCategoryId();
    
    // 如果没有传入 SEO 数据，自动生成
    if (!$seoTitle) {
        $seoTitle = generateReviewSeoTitle($date, $venueCode, 0);
    }
    if (!$seoDescription) {
        $seoDescription = generateReviewSeoDescription($date, $venueCode, 0, 0);
    }
    if (!$seoKeywords) {
        $seoKeywords = generateReviewSeoKeywords($venueCode);
    }
    
    $postData = [
        'title' => $title,
        'content' => $content,
        'excerpt' => strip_tags($excerpt),
        'status' => 'publish',
        'categories' => [$categoryId],
        'slug' => sanitizeReviewTitle($date, $venueCode),
        'meta' => [
            '_yoast_wpseo_title' => $seoTitle,
            '_yoast_wpseo_metadesc' => $seoDescription,
            '_yoast_wpseo_focuskw' => $seoKeywords,
        ]
    ];
    
    // 添加特色图片
    if ($featuredImageId && is_numeric($featuredImageId)) {
        $postData['featured_media'] = (int)$featuredImageId;
    }
    
    $ch = curl_init($wpUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($username . ':' . $password)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 201) {
        $result = json_decode($response, true);
        return $result['id'];
    }
    
    echo "WordPress API 錯誤: HTTP {$httpCode}\n";
    echo "響應: " . substr($response, 0, 500) . "\n";
    return false;
}

function getReviewCategoryId() {
    $categoryName = 'Race Review';
    
    $url = 'https://buycarl.com/wp-json/wp/v2/categories?slug=race-review';
    $username = getWpUsername();
    $password = getWpAppPassword();
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode($username . ':' . $password)
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $categories = json_decode($response, true);
        if (!empty($categories)) {
            return $categories[0]['id'];
        }
    }
    
    // 创建分类
    $ch = curl_init('https://buycarl.com/wp-json/wp/v2/categories');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'name' => $categoryName,
        'slug' => 'race-review'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($username . ':' . $password)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 201) {
        $newCategory = json_decode($response, true);
        return $newCategory['id'];
    }
    
    return 1;
}

function sanitizeReviewTitle($date, $venueCode) {
    $venueName = $venueCode == 'ST' ? 'shatin' : 'happyvalley';
    return "race-review-{$date}-{$venueName}";
}

function getWpUsername() {
    global $wpConfig;
    return $wpConfig['username'] ?? 'your_username';
}

function getWpAppPassword() {
    global $wpConfig;
    return $wpConfig['app_password'] ?? 'your_app_password';
}

function recordReviewPost($pdo, $date, $venueCode, $postId) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS hkracing_review_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            race_date DATE NOT NULL,
            venue_code VARCHAR(10) NOT NULL,
            wp_post_id INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_race_venue (race_date, venue_code)
        )
    ");
    
    $stmt = $pdo->prepare("
        INSERT INTO hkracing_review_posts (race_date, venue_code, wp_post_id)
        VALUES (:date, :venue, :post_id)
        ON DUPLICATE KEY UPDATE
            wp_post_id = VALUES(wp_post_id),
            created_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([
        ':date' => $date,
        ':venue' => $venueCode,
        ':post_id' => $postId
    ]);
}

/**
 * 生成赛后回顾特色图片
 */
function generateReviewFeaturedImage($date, $venueCode, $championAccuracy, $top3Accuracy) {
    $venueName = $venueCode == 'ST' ? '沙田' : '跑馬地';
    $dateFormatted = date('Y-m-d', strtotime($date));
    $dateChinese = date('Y年m月d日', strtotime($date));
    
    // 创建图片
    $width = 1200;
    $height = 630;
    $image = imagecreatetruecolor($width, $height);
    imageantialias($image, true);
    
    // 背景渐变（赛后回顾使用暖色调）
    for ($i = 0; $i < $height; $i++) {
        $ratio = $i / $height;
        $r = 200 + (55 - 200) * $ratio;
        $g = 100 + (40 - 100) * $ratio;
        $b = 80 + (35 - 80) * $ratio;
        $color = imagecolorallocate($image, (int)$r, (int)$g, (int)$b);
        imageline($image, 0, $i, $width, $i, $color);
    }
    
    // 设置文字颜色
    $white = imagecolorallocate($image, 255, 255, 255);
    $gold = imagecolorallocate($image, 255, 215, 0);
    $lightWhite = imagecolorallocate($image, 220, 220, 220);
    $red = imagecolorallocate($image, 255, 100, 100);
    
    // 查找字体
    $fontPath = findChineseFontForReview();
    
    if ($fontPath) {
        // 标题
        imagettftext($image, 60, 0, 80, 120, $gold, $fontPath, "★ 賽後回顧 ★");
        
        // 场地名称
        imagettftext($image, 52, 0, 80, 210, $white, $fontPath, $venueName);
        
        // 日期
        imagettftext($image, 36, 0, 80, 290, $lightWhite, $fontPath, $dateChinese);
        
        // 准确率标签
        imagettftext($image, 42, 0, 80, 400, $gold, $fontPath, "冠軍命中率");
        
        // 根据准确率设置颜色
        if ($championAccuracy >= 50) {
            $accuracyColor = imagecolorallocate($image, 100, 230, 100);
        } elseif ($championAccuracy >= 25) {
            $accuracyColor = $gold;
        } else {
            $accuracyColor = $red;
        }
        imagettftext($image, 85, 0, 80, 520, $accuracyColor, $fontPath, "{$championAccuracy}%");
        
        // 底部信息
        $footer = "位置命中率: {$top3Accuracy}% | 基於13項評分指標";
        imagettftext($image, 22, 0, 80, 590, $lightWhite, $fontPath, $footer);
        
        // 右上角文字
        imagettftext($image, 26, 0, 900, 120, $lightWhite, $fontPath, "RACE");
        imagettftext($image, 26, 0, 900, 160, $lightWhite, $fontPath, "REVIEW");
        
    } else {
        // 降级方案
        imagestring($image, 5, 80, 120, "Race Review", $white);
        imagestring($image, 5, 80, 200, $venueName, $white);
        imagestring($image, 5, 80, 280, $dateChinese, $lightWhite);
        imagestring($image, 5, 80, 360, "Champion Rate: {$championAccuracy}%", $gold);
        imagestring($image, 4, 80, 440, "Top3 Rate: {$top3Accuracy}%", $lightWhite);
    }
    
    // 简洁边框（仅外框，无装饰圆点）
    $goldColor = imagecolorallocate($image, 255, 215, 0);
    imagerectangle($image, 10, 10, $width-11, $height-11, $goldColor);
    imagerectangle($image, 15, 15, $width-16, $height-16, $white);
    
    // 保存图片
    $tempFile = tempnam(sys_get_temp_dir(), 'review_') . '.png';
    imagepng($image, $tempFile);
    imagedestroy($image);
    
    // 上传
    $mediaId = uploadReviewImageToWordPress($tempFile, "review-{$dateFormatted}-{$venueCode}.png");
    
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }
    
    return $mediaId;
}

/**
 * 查找中文字体
 */
function findChineseFontForReview() {
    $fontPaths = [
        __DIR__ . '/fonts/NotoSansTC-VariableFont_wght.ttf',
        __DIR__ . '/fonts/wqy-microhei.ttc',
        __DIR__ . '/fonts/NotoSansCJKtc-Regular.otf',
    ];
    
    foreach ($fontPaths as $path) {
        if (file_exists($path) && filesize($path) > 1000000) {
            return $path;
        }
    }
    return null;
}

/**
 * 上传图片到 WordPress
 */
function uploadReviewImageToWordPress($filePath, $fileName) {
    $username = getWpUsername();
    $password = getWpAppPassword();
    
    $url = 'https://buycarl.com/wp-json/wp/v2/media';
    
    $fileContent = file_get_contents($filePath);
    $fileType = mime_content_type($filePath);
    
    $boundary = '----WebKitFormBoundary' . md5(uniqid());
    
    $body = "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$fileName}\"\r\n";
    $body .= "Content-Type: {$fileType}\r\n\r\n";
    $body .= $fileContent . "\r\n";
    $body .= "--{$boundary}--";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode($username . ':' . $password),
        'Content-Type: multipart/form-data; boundary=' . $boundary,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 201) {
        $result = json_decode($response, true);
        return $result['id'];
    }
    
    return null;
}

/**
 * 生成 SEO 标题
 */
function generateReviewSeoTitle($date, $venueCode, $accuracy) {
    $venueName = $venueCode == 'ST' ? '沙田' : '跑馬地';
    $dateFormatted = date('Y年m月d日', strtotime($date));
    
    return "{$dateFormatted} {$venueName} 賽後回顧 | 預測命中率 {$accuracy}% | 賽馬分析";
}

/**
 * 生成 SEO 描述
 */
function generateReviewSeoDescription($date, $venueCode, $accuracy, $top3Accuracy) {
    $venueName = $venueCode == 'ST' ? '沙田' : '跑馬地';
    $dateFormatted = date('Y年m月d日', strtotime($date));
    
    return "{$dateFormatted} {$venueName} 賽後回顧。冠軍命中率 {$accuracy}%，位置命中率 {$top3Accuracy}%。分析每場賽事預測準確性，找出影響賽果的關鍵因素。香港賽馬分析。";
}

/**
 * 生成 SEO 关键词
 */
function generateReviewSeoKeywords($venueCode) {
    $venueName = $venueCode == 'ST' ? '沙田' : '跑馬地';
    
    return "賽後回顧, {$venueName}賽馬, 賽馬分析, 預測準確率, 賽馬統計";
}
?>