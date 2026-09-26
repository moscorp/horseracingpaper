<?php
// cron_process_horses.php - 定时执行

require_once 'HKJCHorseHistoryBatchManager.php';
include_once ("lib/func_mailer_gmail.php");
include_once ("lib/constants.php");

// 设置最大执行时间
set_time_limit(15);

echo "[" . date('Y-m-d H:i:s') . "] 开始处理马匹历史队列...\n";

try {
    $batchManager = new HKJCHorseHistoryBatchManager(
        $dbConfig['host'],
        $dbConfig['name'],
        $dbConfig['user'],
        $dbConfig['pass']
    );
    
    // 重置卡住的任务（超过30分钟）
    $resetCount = $batchManager->resetStuckTasks();
    if ($resetCount > 0) {
        echo "重置了 {$resetCount} 个卡住的任务\n";
    }
    
    // 获取队列状态
    $stats = $batchManager->getQueueStats();
    echo "队列状态: 待处理 {$stats['pending_count']} 匹, 已完成 {$stats['completed_count']} 匹\n";
    
    // 处理一批
    $result = $batchManager->processBatch();
    
    echo "处理结果: 成功 {$result['results']['success']}, 失败 {$result['results']['failed']}, 跳过 {$result['results']['skipped']}\n";
    echo "耗时: {$result['duration_ms']}ms\n";
    
    if($result['results']['success']){
        $emailtitle = "HRP";
    	$ipAddress = substr($_SERVER['SERVER_ADDR'],-3);
    	$Subject = "[horsepaper] ".$emailtitle." [HKJCHorseHistoryBatchManager] (".$result['results']['success'] .")". $ipAddress;	//$date;
    	$Body    =  "处理结果: 成功 {$result['results']['success']}, 失败 {$result['results']['failed']}, 跳过 {$result['results']['skipped']}\n";
        $Body    .=   "耗时: {$result['duration_ms']}ms\n";
    	$AltBody = '';
    	$recipients = '';
    	$log = '';
    	$recipients_BCC = array(
    		'hkhorsepaper@gmail.com' => 's',
    		'support@fengins.com' => 'fi',
    		// ..
    	 );
    	 
    	 $send = mailto($Subject,$Body,$AltBody,$recipients,$recipients_BCC,$log);
    
    }
    
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    error_log("马匹历史抓取错误: " . $e->getMessage());
}

echo "[" . date('Y-m-d H:i:s') . "] 完成\n";
?>