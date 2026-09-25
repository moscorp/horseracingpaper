<?php

ini_set('max_execution_time', '3000000');
error_reporting(E_ALL);
// ini_set("allow_url_fopen", 1);
header('Content-Type: text/html; charset=utf-8');
// require_once('lib/mossql.php');	
require_once('lib/func.php');	
require_once('lib/func_horsehistory_oversea.php');	
require_once("lib/func_mailer_gmail.php");
require_once('simplehtmldom_1_9_1/simple_html_dom.php');

require_once("lib/constants.php");
$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
mysqli_set_charset($mysqli, "utf8mb4");
$today = date("Y-m-d");
$venuelocal = ['HV', 'ST'];

//--------------------------------------------------------------------
// $horseid = "E079";
//--------------------------------------------------------------------
$cnt=0;
//--------------------------------------------------------------------
$msg = 'ai-horseinfooversea-updatestart';
$insert = "	INSERT INTO logs
VALUES(0,$msg,now())";
// echo $insert."<br>";
mysqli_query($mysqli, $insert);
//--------------------------------------------------------------------		

$racecard_sql = "select racingdate from RaceCard where 1 order by racingdate desc limit 1";
// echo $racecard_sql;
$racecard_array = mysqli_query($mysqli, $racecard_sql);
$row = mysqli_fetch_assoc($racecard_array);
// echo "--".$row['racingdate'];
$whr = " and racingdate='".$row['racingdate']."' ";//limit 2";

//--------------------------------------------------------------------
$sql = "select * from horseinfo where 1";   

$horseinfo_saved_array= mysqli_query($mysqli, $sql);
// $nofrec = mysqli_num_rows($horseinfo_saved);
while($row = mysqli_fetch_array($horseinfo_saved_array)){
    $horseinfo_saved[] = $row['horseid'].$row['Date'];
    // $horseinfo_saved[] = $row['horseid'].$row['Date'];
}
// print_r($horseinfo_saved);
// exit;
//--------------------------------------------------------------------
$horseinfo_array = array();
// $racecard_sql = "select horsename,BrandNo from RaceCard where SUBSTRING(BrandNo,-1) REGEXP '^[0-9]+$' group by BrandNo";//.$whr;

$sql = "SELECT rc.venue, rc.raceno, rc.horseno, rc.horsename, rc.BrandNo, rc.racingdate
        FROM RaceCard rc
        WHERE racingdate>='$today' 
        GROUP BY rc.BrandNo
        order by rc.raceno,rc.horseno";
// echo $sql;
$racecard_array = mysqli_query($mysqli, $sql);
$racecard = array();
$horseidcount=0;



// Example usage
// $racingdate = '2025-10-18';
// $venueCode = 'S1';
// $horseinfo = fetchHorseInfo($ids, $meetingDate, $raceNumber);

// // Output the horseinfo array for debugging
// print_r($horseinfo);

while ($racecard = mysqli_fetch_assoc($racecard_array)) {
    $venue = $racecard['venue'];
    $raceno = $racecard['raceno'];
    $horseid = substr($racecard['BrandNo'],0,14);
    $racingdateshort = substr($horseid,0,8);
    $horsename = $racecard['horsename'];
    $racingdate = $racecard['racingdate'];
    // $rc_str = $horseid.$racingdate;


    if(!in_array($venue,$venuelocal)){
        echo $racingdateshort.$venue.$raceno.$horseid;
        // $horseinfo = fetchHorseInfo($horseid, $racingdateshort, $raceno);
        $horsehist = fetchHorseHistory($horseid, $racingdateshort, $raceno, $venue);

        // Output the horseinfo array for debugging
        // print_r($horsehist);
        
        //-----------------------------------------------------------------------------------------------------
        $insert_horseinfo="";
        $sql =""; 
        $cnt = 0;   
        if(isset($horsehist)){
            foreach ($horsehist as $horse) {
                foreach ($horse as $key => $histDetail) {
                    if (is_array($histDetail) && isset($histDetail[0]['id'])) {
                        foreach ($histDetail as $detail) {
                            $horseid = $detail['id']; 
                            $horsename = $detail['id']; 
                            $RaceIndex = $detail['raceIndex']; 
                            $Pla = $detail['placeNo']; 
                            $Date = $detail['date']; 
                            $RC_Track_Course = $detail['track']; 
                            $Dist = $detail['distance']; 
                            $G = $detail['going']; 
                            $RaceClass = $detail['className']; 
                            $Dr = $detail['barrierDrNo']; 
                            $Rtg = $detail['horseWeight']; 
                            $Trainer = $detail['trainer']['name_ch']; 
                            $Jockey = $detail['jockey']['name_ch']; 
                            $LBW = ""; 
                            $Win_Odds = $detail['winOdds']; 
                            $Act_Wt = $detail['actualWeight']; 
                            $Running_Position = $detail['placeNo']; 
                            $Finish_Time = $detail['finalTimeOfRace']; 
                            $Declar_Wt = $detail['horseWeight']; 
                            $Gear = $detail['gear'];
                            
                            if (!empty($horseinfo_saved) && is_array($horseinfo_saved) && !in_array($horseid . $Date, $horseinfo_saved) && $horseid) {
                                $insert_horseinfo .= "(0, 
                                    '".addslashes($horseid)."', 
                                    '".addslashes($horsename)."', 
                                    '".addslashes($RaceIndex)."', 
                                    '".addslashes($Pla)."', 
                                    '".addslashes($Date)."', 
                                    '".addslashes($RC_Track_Course)."', 
                                    '".addslashes($Dist)."', 
                                    '".addslashes($G)."', 
                                    '".addslashes($RaceClass)."', 
                                    '".addslashes($Dr)."', 
                                    '".addslashes($Rtg)."', 
                                    '".addslashes($Trainer)."', 
                                    '".addslashes($Jockey)."', 
                                    '".addslashes($LBW)."', 
                                    '".addslashes($Win_Odds)."', 
                                    '".addslashes($Act_Wt)."', 
                                    '".addslashes($Running_Position)."', 
                                    '".addslashes($Finish_Time)."', 
                                    '".addslashes($Declar_Wt)."', 
                                    '".addslashes($Gear)."',
                                    now()),";
                                $cnt++;
                                // echo $insert_horseinfo."<br>";
                            // }
                            //******************************************************** */
                            // }
                        }
                            
                    }
                }
            }
        }

                       
            
            if(strlen($insert_horseinfo)>5){
                $insert_horseinfo = substr($insert_horseinfo,0,strlen($insert_horseinfo)-1).";";
                $sql = "INSERT INTO `horseinfo` (`id`, `horseid`, `horsename`, `RaceIndex`, `Pla`, `Date`, `RC_Track_Course`, `Dist`, `G`, `RaceClass`, `Dr`, `Rtg`, `Trainer`, `Jockey`, `LBW`, `Win_Odds`, `Act_Wt`, `Running_Position`, `Finish_Time`, `Declar_Wt`, `Gear`, `rectime`) 
                    VALUES ".$insert_horseinfo; 
                    echo $sql."<br>";
                    // if($racingdate == date("Y-m-d")) {
                    mysqli_query($mysqli, $sql);
                    $error_message = mysqli_error($mysqli);
                    if($error_message == ""){
                        echo "No error related to SQL query<br>";
                        $cnt++;
                    }else{
                        echo "<u>Query Failed: ".$error_message."<u><br>";
                    }
                    // die(1);
                    //--------------------------------------------------------------------
                    $msg = 'ai-horseinfo-oversea-'.$cnt;
                    $insert = "	INSERT INTO logs
                    VALUES(0,'$msg',now())";
                    echo $insert;
                    mysqli_query($mysqli, $insert);
                    //--------------------------------------------------------------------	
                    // sleep(3);
            }     
            $horseidcount++;
            if($horseidcount==5){
                // die(1);
            }
        }
    }
        // sleep(3);
        // die(1);
}



?>
