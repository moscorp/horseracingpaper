<?php

// error_reporting(E_ALL);
error_reporting(E_ALL & ~E_NOTICE); //only in log
ini_set('display_errors', 0);   //hide from user
// ini_set("allow_url_fopen", 1);
header('Content-Type: text/html; charset=utf-8');
require_once('lib/mossql.php');	
require_once('lib/func.php');	
require_once('lib/func_grec.php');	
require_once("lib/func_mailer_gmail.php");
include_once('simplehtmldom_1_9_1/simple_html_dom.php');

require_once("lib/constants.php");
$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$horserankhistory = array();
$sql = "SELECT rc.horseno, rc.horsename, rc.brandno
        FROM RaceCard rc
        JOIN (
            SELECT MAX(racingdate) AS max_date
            FROM RaceCard
        ) t ON rc.racingdate = t.max_date";
        
$latest_racecard_result = mysqli_query($mysqli, $sql);
while ($row = mysqli_fetch_assoc($latest_racecard_result)) {
    $hist = "SELECT raceindex, horseid, horsename, GROUP_CONCAT(pla) AS plax, GROUP_CONCAT(win_odds) AS win_oddsx, GROUP_CONCAT(rc_track_course) as rc_track_course
                FROM (
                    SELECT DISTINCT raceindex, horseid, horsename, pla, win_odds,date, SUBSTRING_INDEX(REPLACE(rc_track_course, '&quot;', '\"'), '\"', 1) AS rc_track_course
                    FROM horseinfo
                    WHERE horseid = '".$row['brandno']."'
                    ORDER BY date desc
                    limit 4
                ) t
                GROUP BY horseid 
                ";
                // echo $hist."<br>";
    $hist_data = mysqli_query($mysqli, $hist);
    while ($hist_row = mysqli_fetch_assoc($hist_data)) {
        $temp = array();
        $temp['raceindex'] = $hist_row['raceindex'];
        $temp['horseid'] = $hist_row['horseid'];
        $temp['horsename'] = $hist_row['horsename'] ? $hist_row['horsename'] : $row['horsename'];
        $temp['plax'] = $hist_row['plax'];
        $temp['win_oddsx'] = $hist_row['win_oddsx'];
        
        $horseid = $hist_row['horseid'];
        $horserankhistory[$horseid]['plax'] = $hist_row['plax'];
        $horserankhistory[$horseid]['win_oddsx'] = $hist_row['win_oddsx'];
        $horserankhistory[$horseid]['rc_track_course'] = $hist_row['rc_track_course'];
    }
}

print_r($horserankhistory);
// echo 
        

?>

