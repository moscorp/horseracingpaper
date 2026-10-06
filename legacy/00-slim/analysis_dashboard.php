<?php
// analysis_dashboard.php - 仪表板组件（安全版）

try {
    // 获取马季信息（香港马季从9月到次年8月）
    $currentYear = date('Y');
    $currentMonth = date('n');
    // 如果当前月份 >= 9，马季为 currentYear-currentYear+1，否则为 currentYear-1-currentYear
    if ($currentMonth >= 9) {
        $racingSeasonStart = $currentYear;
        $racingSeasonEnd = $currentYear + 1;
    } else {
        $racingSeasonStart = $currentYear - 1;
        $racingSeasonEnd = $currentYear;
    }
    $racingSeasonLabel = "{$racingSeasonStart}/{$racingSeasonEnd}";
    
    // 获取上个完整马季的标签（用于全季统计对比）
    $prevSeasonStart = $racingSeasonStart - 1;
    $prevSeasonEnd = $racingSeasonEnd - 1;
    $fullSeasonLabel = "{$prevSeasonStart}/{$prevSeasonEnd}";
    
    // ========== 当前马季统计（从 Sept 到 当前日期） ==========
    $seasonStartDate = "{$racingSeasonStart}-09-01";
    $currentDate = date('Y-m-d');
    
    // 当前马季 - 马匹总数
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT h.horse_code) as cnt 
        FROM hkracing_horses h
        WHERE h.created_at >= :season_start 
           OR EXISTS (
               SELECT 1 FROM hkracing_horse_performances p 
               WHERE p.horse_code = h.horse_code AND p.race_date >= :season_start
           )
    ");
    $stmt->execute([':season_start' => $seasonStartDate]);
    $seasonHorses = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0) : 0;
    
    // 当前马季 - 出赛纪录数
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as cnt 
        FROM hkracing_horse_performances 
        WHERE race_date >= :season_start
    ");
    $stmt->execute([':season_start' => $seasonStartDate]);
    $seasonPerformances = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0) : 0;
    
    // 当前马季 - 骑师人数
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT jockey_name_ch) as cnt 
        FROM hkracing_horse_performances 
        WHERE jockey_name_ch IS NOT NULL AND jockey_name_ch != '' 
        AND race_date >= :season_start
    ");
    $stmt->execute([':season_start' => $seasonStartDate]);
    $seasonJockeys = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0) : 0;
    
    // 当前马季 - 练马师人数
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT h.trainer_name_ch) as cnt 
        FROM hkracing_horses h
        WHERE h.trainer_name_ch IS NOT NULL AND h.trainer_name_ch != ''
        AND (h.created_at >= :season_start 
            OR EXISTS (SELECT 1 FROM hkracing_horse_performances p WHERE p.horse_code = h.horse_code AND p.race_date >= :season_start))
    ");
    $stmt->execute([':season_start' => $seasonStartDate]);
    $seasonTrainers = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0) : 0;
    
    // ========== 全季统计（所有历史数据） ==========
    $stmt = $pdo->query("SELECT COUNT(DISTINCT horse_code) as cnt FROM hkracing_horses");
    $totalHorses = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0) : 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM hkracing_horse_performances");
    $totalPerformances = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0) : 0;
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT jockey_name_ch) as cnt FROM hkracing_horse_performances WHERE jockey_name_ch IS NOT NULL AND jockey_name_ch != ''");
    $totalJockeys = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0) : 0;
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT trainer_name_ch) as cnt FROM hkracing_horses WHERE trainer_name_ch IS NOT NULL AND trainer_name_ch != ''");
    $totalTrainers = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0) : 0;
    
    // 胜率最高的马匹（全季）
    $stmt = $pdo->query("
        SELECT horse_code, name_ch, total_starts, total_wins, 
               ROUND(total_wins / NULLIF(total_starts, 0) * 100, 1) as win_rate
        FROM hkracing_horses
        WHERE total_starts >= 5
        ORDER BY win_rate DESC
        LIMIT 10
    ");
    $topHorses = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
    // 最近抓取统计
    $stmt = $pdo->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success,
            SUM(performances_count) as performances
        FROM hkracing_horse_fetch_log
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
        LIMIT 7
    ");
    $fetchLogs = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
} catch (Exception $e) {
    echo '<div class="card"><div class="loading">數據加載失敗</div></div>';
    return;
}
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($seasonHorses); ?></div>
        <div class="stat-label">香港馬匹總數 <span class="stat-note">(<?php echo $racingSeasonLabel; ?> 馬季)</span></div>
        <div class="stat-total-ref">數據庫總數: <?php echo number_format($totalHorses); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($seasonPerformances); ?></div>
        <div class="stat-label">歷史出賽紀錄 <span class="stat-note">(<?php echo $racingSeasonLabel; ?> 馬季)</span></div>
        <div class="stat-total-ref">數據庫總數: <?php echo number_format($totalPerformances); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($seasonJockeys); ?></div>
        <div class="stat-label">騎師人數 <span class="stat-note">(<?php echo $racingSeasonLabel; ?> 馬季)</span></div>
        <div class="stat-total-ref">數據庫總數: <?php echo number_format($totalJockeys); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($seasonTrainers); ?></div>
        <div class="stat-label">練馬師人數 <span class="stat-note">(<?php echo $racingSeasonLabel; ?> 馬季)</span></div>
        <div class="stat-total-ref">數據庫總數: <?php echo number_format($totalTrainers); ?></div>
    </div>
</div>

<div class="card">
    <div class="card-title">🏆 勝率最高馬匹 TOP 10 <span class="stat-note">(全季統計)</span></div>
    <?php if (empty($topHorses)): ?>
    <div class="loading">暫無足夠的馬匹數據</div>
    <?php else: ?>
    <table class="data-table">
        <thead><th>排名</th><th>馬匹</th><th>出賽</th><th>勝場</th><th>勝率</th></tr>
        </thead>
        <tbody>
            <?php foreach ($topHorses as $i => $horse): ?>
            <tr>
                <td><?php echo $i + 1; ?></td>
                <td><?php echo htmlspecialchars($horse['name_ch']); ?></td>
                <td><?php echo $horse['total_starts']; ?></td>
                <td><?php echo $horse['total_wins']; ?></td>
                <td><span class="odds-hot"><?php echo $horse['win_rate']; ?>%</span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-title">📊 最近抓取統計</div>
    <?php if (empty($fetchLogs)): ?>
    <div class="loading">暫無抓取記錄</div>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr><th>日期</th><th>抓取次數</th><th>成功</th><th>獲取紀錄數</th></tr>
        </thead>
        <tbody>
            <?php foreach ($fetchLogs as $log): ?>
            <tr>
                <td><?php echo $log['date']; ?></td>
                <td><?php echo $log['total']; ?></td>
                <td><?php echo $log['success']; ?></td>
                <td><?php echo number_format($log['performances']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<style>
.stat-note {
    font-size: 0.7rem;
    font-weight: normal;
    color: #888;
}

.stat-total-ref {
    font-size: 0.7rem;
    color: #aaa;
    margin-top: 4px;
    border-top: 1px dashed #eee;
    padding-top: 4px;
}
</style>