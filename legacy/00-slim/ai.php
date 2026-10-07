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

            // function getPlaColor($pla) {
            //     if ($pla == 1) {
            //         return "<font color=\'#FF0000\'>";  //red
            //     } elseif ($pla == 2) {
            //         return "<font color=\'#0066FF\'>";  //blue
            //     } elseif ($pla == 3) {
            //         return "<font color=\'#00FF00\'>";  //green
            //     } elseif ($pla == 4) {
            //         return "<font color=\'gray\'>";
            //     } else {
            //         return "<font color=\'lightgrey\'>";
            //     }
            // }
            function getPlaColor($pla) {
                $colorMap = [
                    1 => 'red',
                    2 => 'blue',
                    3 => 'green',
                    4 => 'BROWN'
                ];
            
                if (isset($colorMap[$pla])) {
                    return "<font color='{$colorMap[$pla]}'>";
                } else {
                    return "<font color='gray'>";
                }
            }
            function getWinOddsComparison($win_oddsx1, $win_oddsx2) {
                if ($win_oddsx1 * 2 < $win_oddsx2) {
                    return "<b>";
                } else {
                    return "</b>";
                }
            }
            function getTrackCourseComparison($rc_track_course1, $rc_track_course2) {
                if ($rc_track_course1 !== $rc_track_course2) {
                    return "<u>";
                } else {
                    return "</u>";
                }
            }
            function getDigitValue($number) {
                if ($number === 0) {
                    return 'x';
                } elseif ($number === '' || $number === null) {
                    return '-';
                } elseif ($number <= 10) {
                    return 'h';
                } elseif ($number > 10 && $number < 100) {
                    return intval($number / 10);
                } elseif ($number >=100 ) {
                    return 'c';
                } else {
                    return '?';
                }
            }
            function currentWinOddsComparison($win1, $win2) {
                if ($win1 === '' || $win1 === null) {
                    return "?";
                }else{
                    if ($win1 * 2 < $win2) {
                        return "<b>" . "<";
                    } elseif (intval($win1 / 10) == intval($win2 / 10)) {
                        return "~";
                    } elseif ($win1 > $win2 * 1.5) {
                        return "+";
                    } else {
                        return "=";
                    }
                }
            }
            

echo getPlaColor(1)."1".getPlaColor(2)."2".getPlaColor(3)."3".getPlaColor(4)."4"."</font></font></font></font><b>drop</b>"."<u>xrc</u>"."</font><br>";

if($mm){
    $horseinfo_sql = "select * from horseinfo where 1 order by Date desc limit 1";
    $last_horseinfo = mysqli_query($mysqli, $horseinfo_sql);
    $row = mysqli_fetch_assoc($last_horseinfo);
    echo "hir" . $row['Date'] . "--";
}
//  die(1);   
// $user = mysqli_fetch_array($user);
$qinmatrix = !empty($_GET[$qm]) ? $_GET[$qm] : null;

$logs = "";
date_default_timezone_set('Asia/Hong_Kong');
$hkcurrenttime = date("H:i",time());

if($mm){ echo $hkcurrenttime . "<br>"; }

$time_start = microtime(true); 
if($mm) { echo "[1]-".number_format(microtime(true) - $time_start, 2)."-"; }


// function _get($str){
// 	$val = !empty($_GET[$str]) ? $_GET[$str] : null;
// 	return $val;
// }
//--------------------------------------------------------------------
        // $insert = "	INSERT INTO logs
        //             VALUES('','ai',now())";
        // // echo $insert;
		// mysqli_query($mysqli, $insert);
//--------------------------------------------------------------------		
// https://bet2.hkjc.com/racing/getJSON.aspx?type=winplaodds&date=2023-03-15&venue=hv&start=1&end=9
// http://bet2.hkjc.com/racing/getJSON.aspx?type=win&date=2018-07-01&venue=ST&raceno=1
// http://bet2.hkjc.com/racing/getJSON.aspx?type=pla&date=2018-07-01&venue=ST&raceno=1
// http://bet2.hkjc.com/racing/getJSON.aspx?type=qin&date=2018-07-01&venue=ST&raceno=1
// http://bet2.hkjc.com/racing/getJSON.aspx?type=qpl&date=2018-07-01&venue=ST&raceno=1
// http://bet2.hkjc.com/racing/getJSON.aspx?type=tri&date=2018-07-01&venue=ST&raceno=1
// http://bet2.hkjc.com/racing/getJSON.aspx?type=ff&date=2018-07-01&venue=ST&raceno=1
// http://bet2.hkjc.com/racing/getJSON.aspx?type=dbl&date=2018-07-01&venue=ST&raceno=1
// http://bet2.hkjc.com/racing/getJSON.aspx?type=raceres&date=2018-07-01&venue=ST&raceno=1
// http://bet2.hkjc.com/racing/getJSON.aspx?type=pooltot&date=2018-07-01&venue=ST&raceno=1


	// ```
	// $json_string = '{"OUT":"165605@@@;1-2=9.3=0;1-3=84=0;1-4=34=0;1-5=14=0;1-6=13=0;1-7=7.4=0;1-8=72=0;1-9=20=0;1-10=18=0;1-11=41=0;1-12=9.4=0;2-3=56=0;2-4=23=0;2-5=12=0;2-6=12=0;2-7=5.2=0;2-8=56=0;2-9=13=0;2-10=16=0;2-11=57=0;2-12=6.5=0;3-4=120=0;3-5=109=0;3-6=107=0;3-7=52=0;3-8=138=0;3-9=98=0;3-10=140=0;3-11=152=0;3-12=67=0;4-5=31=0;4-6=27=0;4-7=18=0;4-8=107=0;4-9=27=0;4-10=51=0;4-11=103=0;4-12=30=0;5-6=16=0;5-7=7.6=0;5-8=83=0;5-9=15=0;5-10=19=0;5-11=46=0;5-12=11=0;6-7=7.0=0;6-8=58=0;6-9=14=0;6-10=20=0;6-11=45=0;6-12=8.9=0;7-8=34=0;7-9=5.7=0;7-10=10=0;7-11=24=0;7-12=3.7=1;8-9=53=0;8-10=57=0;8-11=104=0;8-12=37=0;9-10=17=0;9-11=41=0;9-12=7.1=0;10-11=40=0;10-12=9.6=0;11-12=27=0"}';
	// $data = json_decode($json_string, true);
	// $out = $data['OUT'];
	
	// // Split the string into an array
	// $array = explode(';', $out);
	
	// // Loop through the array and split each element into a key-value pair
	// $result = array();
	// foreach ($array as $element) {
	//   $pair = explode('=', $element);
	//   $keys = explode('-', $pair[0]);
	//   $value = $pair[1];
	//   $result[$keys[0]][$keys[1]] = $value;
	// }
	
	// // Access the values by key
	// echo $result['1']['2']; // Outputs: 9.3
	// ```
	
	
// result
// http://racing.hkjc.com/racing/information/Chinese/Racing/DisplaySectionalTime.aspx?RaceDate=12/09/2018&RaceNo=1&All=0#Race1

    // Input array
    // $arr = array(2, 3, 5, 6, 7); 
    // print_r(Stand_Deviation($arr));

// $html->clear();
// unset($html);

//horse detail---------------------------------------------------------------------------------------------
/*
$url = "https://iosbsinfo02.hkjc.com/infoA/AOSBS/HR_GetInfo.ashx?QT=HR_ODDS_ALL&Race=*&Venue=*&Result=1&Dividend=1&JTC=1&JKC=1&Lang=zh-HK";
$xml=simplexml_load_file($url) or die("Error: Cannot create object");

$racedetail =array();
$JockeyCode_cnt =array();
$TrainerCode_cnt =array();
$TrainerJockey_cnt =array();
$JC = array();
$isEmpty=0;
foreach($xml->Meetings->MeetingInfo->JTCInfo as $Record){
	// if($xml->Meetings->MeetingInfo-> == $venue)
	foreach($Record->EntriesData as $rec){
		$R='';
		$SEL='';
		foreach($rec as $key){
			$R = $key->attributes()->{'R'}*1;
			$SEL = $key->attributes()->{'SEL'}*1;
			$WIN = $key->attributes()->{'WIN'};
// 			echo $WIN."<br>";
			$isEmpty = $WIN ? 0 : 1;
			$PLA = $key->attributes()->{'PLA'};
			$JockeyCode = $key->attributes()->{'JockeyCode'};
			$JockeyName = $key->attributes()->{'JockeyName'};
			
			$TrainerCode = $key->attributes()->{'TrainerCode'};
			$TrainerName = $key->attributes()->{'TrainerName'};
			$Trainer_Jockey = $key->attributes()->{'TrainerName'}.$key->attributes()->{'JockeyName'};
			$OddsInRange = $key->attributes()->{'OddsInRange'};

			$racedetail[$R][$SEL]['WIN'] = $WIN;
			$racedetail[$R][$SEL]['PLA'] = $PLA;
			$racedetail[$R][$SEL]['JockeyCode'] = $JockeyCode;
			$racedetail[$R][$SEL]['JockeyName'] = $JockeyName;
			$racedetail[$R][$SEL]['Trainer_Jockey'] = $Trainer_Jockey;
			$racedetail[$R][$SEL]['TrainerName'] = $TrainerName;
			$racedetail[$R][$SEL]['TrainerCode'] = $TrainerCode;
			$racedetail[$R][$SEL]['OddsInRange'] = $OddsInRange;
			$racedetail[$R][$SEL]['HandicapWeight'] = $key->attributes()->{'HandicapWeight'};
			// $racedetail[$R][$SEL]['WeightAllowance'] = $key->attributes()->{'WeightAllowance'};
			// $racedetail[$R][$SEL]['RatingRange'] = $key->attributes()->{'RatingRange'};

			array_push($JC,$JockeyName);

			// $JockeyCode_cnt[(string)$JockeyName]++;
			// $TrainerCode_cnt[(string)$TrainerName]++;
			// $TrainerJockey_cnt[(string)$Trainer_Jockey]++;
		}
	}
}

// print_r($racedetail);
// var_dump($racedetail);
*/

$url ="https://bet2.hkjc.com/racing/script/rsdata.js?lang=ch&date=*&venue=*";
$today = date("Y-m-d");
$url = isset($_GET['venue']) ? "https://bet2.hkjc.com/racing/script/rsdata.js?lang=ch&date=".$today."&venue=".strtoupper($_GET['venue'])."&CV=FO_L4.01R0f" : $url;
// echo $url;
// curl_setopt($handle, CURLOPT_ENCODING, 'gzip,deflate,sdch');
$html = file_get_html("compress.zlib://".$url);

$racingdaydata = multiexplode2(array("var","="),$html) ;
// print_r($racingdaydata);
$newArray = array();
foreach($racingdaydata as $val) {
	if($val[0]){
		$val1 = str_replace("'","",$val[1]);
		$newArray += [$val[0] => $val1];
	}
}
// print_r($racingdaydata);
$mtgDate="";
foreach($racingdaydata as $key) {
	if(str_replace(' ', '',$key[0])=="mtgDate"){ $mtgDate = str_replace(array("'",";"," "),"",$key[1]); }
	if(str_replace(' ', '',$key[0])=="mtgVenue"){ $mtgVenue = str_replace(array("'",";"," "),"",$key[1]); }
	if(str_replace(' ', '',$key[0])=="mtgTotalRace"){ $mtgTotalRace = str_replace(array("'",";"," "),"",$key[1]); }
	if(str_replace(' ', '',$key[0])=="venueLong"){ $venueLong = str_replace(array("'",";"," "),"",$key[1]); }
	if(str_replace(' ', '',$key[0])=="racePostTime"){ $racePostTime = str_replace(array("[","]"," "),"",$key[1]); }
                                                    // 	echo $key[1];
	                                               //   $racePostTimearray = json_decode(str_replace('"', "'",$key[1]),true); } 
	if(str_replace(' ', '',$key[0])=="raceHeaderInfoEN"){ $raceHeaderInfoEN = $key[1];}	//str_replace(array("[","]"," "),"",$key[1]); } //

	if(str_replace(' ', '',$key[0])=="poolStatusByRace"){ $poolStatusByRace = $key[1];}	//str_replace(array("[","]"," "),"",$key[1]); } //
}
//-----------------------------------------------
$raceHeaderInfoEN = str_replace("];","]",$raceHeaderInfoEN);
// echo $raceHeaderInfoEN;
$racedata = json_decode($raceHeaderInfoEN, true);
// echo "<pre>" ;
// print_r($racedata);

$racedatahead = array();
foreach ($racedata as $raceno => $value) {
	if($raceno){
		$racedatahead[$raceno] = $value["race"].", ".$value["venue"].", ".$value["time"].",  "
								.(isset($value["class"]) ? $value["class"] : "").", ".$value["track"].", ".$value["dist"].", ".$value["going"];
		$racedataheadvenue[$raceno] = $value["venue"];
								
		$racedist[$raceno] = str_replace("m","",$value["dist"]);
		$racetimehm[$raceno] = $value["time"];
	}
}
// print_r($racetimehm);
//-----------------------------------------------
$poolStatusByRace = str_replace("];","]",$poolStatusByRace);
$racedata = json_decode($poolStatusByRace, true);
// print_r($poolStatusByRace);
$poolStatus = array();
foreach ($racedata as $raceno => $value) {
    $poolStatus[$raceno] = count($value) ? 1 : 0;	
	if(count($value)>0){
		$tmp[] = $raceno;
	}
}
// print_r($poolStatus);
// print_r($tmp);
// echo $tmp[0];
//-
//-----------------------------------------------
// echo 	"<br>".$mtgDate;
// echo 	"<br>".$mtgVenue;
// $arr = json_decode($raceHeaderInfoEN, true);
$racetime = explode(",", $racePostTime);
$racetime = str_replace($mtgDate,"", $racetime);

$racingdate=$mtgDate;
$venue=isset($_GET['venue']) ? $_GET['venue'] : $mtgVenue;
// echo $venue;
$venueLong=$venueLong;
$start="1";	//$tmp[0];	//"1";
$end=$mtgTotalRace;

$date1 = new DateTime (date("Y-m-d"));
$date2 = new DateTime ($racingdate);
$interval = $date1->diff ($date2);
// echo “difference ” . $interval -> y . ” years, ” . $interval -> m.” months, “.$interval -> d.” days “;
// echo “difference ” . $interval -> days . ” days “;

if($racingdate <> date("Y-m-d") && !$mm) {
	$logs = "ai-no_data";
	$insert = "	INSERT INTO logs
	VALUES('','$logs',now())";
	mysqli_query($mysqli, $insert);
	die("Horse paper will be release at ".$interval->days." days later, ".$racingdate." (".$venue.").");
}

//------------------------------------------------------------------------------------------------------------------
$horserankhistory = array();
$sql = "SELECT rc.horseno, rc.horsename, rc.brandno
        FROM RaceCard rc
        JOIN (
            SELECT MAX(racingdate) AS max_date
            FROM RaceCard
        ) t ON rc.racingdate = t.max_date";
$sql = "SELECT rc.horseno, rc.horsename, rc.brandno
        FROM RaceCard rc
        where racingdate = '$racingdate'";
$latest_racecard_result = mysqli_query($mysqli, $sql);
while ($row = mysqli_fetch_assoc($latest_racecard_result)) {
    $hist = "SELECT raceindex, horseid, horsename, date, GROUP_CONCAT(pla) AS plax, GROUP_CONCAT(win_odds) AS win_oddsx, GROUP_CONCAT(rc_track_course) as rc_track_course, GROUP_CONCAT(dr) as dr
                FROM (
                    SELECT DISTINCT raceindex, horseid, horsename, pla, win_odds,dr, date, SUBSTRING_INDEX(REPLACE(rc_track_course, '&quot;', '\"'), '\"', 1) AS rc_track_course
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
        if($hist_row['date'] != $racingdate){
            $horseid = $hist_row['horseid'];
            $horserankhistory[$horseid]['plax'] = $hist_row['plax'];
            $horserankhistory[$horseid]['win_oddsx'] = $hist_row['win_oddsx'];
            $horserankhistory[$horseid]['rc_track_course'] = $hist_row['rc_track_course'];
            $horserankhistory[$horseid]['dr'] = $hist_row['dr'];
        }
    }
}
// print_r($horserankhistory);
// $sql = "SELECT pattern, pla FROM historyodds_pattern";
// $pattern = mysqli_query($mysqli, $sql);
// $plapattern = array(); // Initialize an empty array
// $temp = array();
// while ($row = mysqli_fetch_assoc($pattern)) {
//     $temp['pattern'] = htmlspecialchars($row['pattern']); // Safely encode special characters
//     $temp['pla'] = htmlspecialchars($row['pla']); // Safely encode special characters
//     $plapattern[] = $temp;
// }
// print_r($plapattern);
$sql = "SELECT historyodds, rank FROM historyodds where rank and venue='$venue'";
$pattern = mysqli_query($mysqli, $sql);
$plapattern = array(); // Initialize an empty array
$temp = array();
while ($row = mysqli_fetch_assoc($pattern)) {
    $temp['pattern'] = strip_tags($row['historyodds']); // Safely encode special characters
    $temp['pla'] = ($row['rank']); // Safely encode special characters
    $plapattern[] = $temp;
}
// print_r($plapattern);

//------------------------------------------------------------------------------------------------------------------

//----------------------------------------------------------------------------------------
$horseinfo = array();
$horseinfo_sql = "select * from horseinfo where 1 ";
$horseinfo_array = mysqli_query($mysqli, $horseinfo_sql);

while($horseinfo = mysqli_fetch_array($horseinfo_array)){
	if( isset($horseinfo['Trainer']) && isset($horseinfo['Jockey']) ){
		$TJ[$horseinfo['Trainer']][$horseinfo['Jockey']]['race']++;
		$TJ[$horseinfo['Trainer']][$horseinfo['Jockey']]['T'] = ((int)$horseinfo['Pla']<4) ? $TJ[$horseinfo['Trainer']][$horseinfo['Jockey']]['T']+1 : $TJ[$horseinfo['Trainer']][$horseinfo['Jockey']]['T'];
		$TJ[$horseinfo['Trainer']][$horseinfo['Jockey']]['F'] = ((int)$horseinfo['Pla']<=4) ? $TJ[$horseinfo['Trainer']][$horseinfo['Jockey']]['F']+1 : $TJ[$horseinfo['Trainer']][$horseinfo['Jockey']]['F'];
		$TJ[$horseinfo['Trainer']][$horseinfo['Jockey']]['W'] = ((int)$horseinfo['Pla']==1) ? $TJ[$horseinfo['Trainer']][$horseinfo['Jockey']]['W']+1 : $TJ[$horseinfo['Trainer']][$horseinfo['Jockey']]['W'];
	}
}

//----------------------------------------------------------------------------------------
$emailtitle = $racingdate." [".$venueLong.$venue."]";

    // clean up memory
// $html->clear();
// unset($html);

if($mm) { echo "[2]-".number_format(microtime(true) - $time_start, 2)."-"; }
//pooltot----------------------------------------------------------------------------------
$array = array();
for ($raceno = $start; $raceno <= $end; $raceno++) {
	// $windata = array();
	// sleep(1);
// 	$url = "http://bet2.hkjc.com/racing/getJSON.aspx?type=winplaodds&date=".$racingdate."&venue=".$venue."&raceno=".$raceno;
	$url = "https://bet2.hkjc.com/racing/getJSON.aspx?type=pooltot&date=".$racingdate."&venue=".$venue."&raceno=".$raceno;
	$pooltot = postRequest($url,[]);
	$pooltot = html_entity_decode($pooltot);
	$pooltotarray[$raceno] = json_decode($pooltot, true);
}
// print_r($pooltotarray);
for ($raceno = $start; $raceno <= $end; $raceno++) {
// echo $pooltotarray[$raceno]['inv'][0]['value']."<br>";
    $poolpro[$raceno] = 100*$pooltotarray[$raceno]['inv'][1]['value']/$pooltotarray[$raceno]['inv'][0]['value'];
}
if($mm) { echo "[3]-".number_format(microtime(true) - $time_start, 2)."-"; }

if($qinmatrix){ //-----------------------------------------------------------------------------------------------------------------------------------------
    //wwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwww
    $array = array();
    for ($raceno = $start; $raceno <= $end; $raceno++) {
    	// $windata = array();
    	// sleep(1);
    	$url = "http://bet2.hkjc.com/racing/getJSON.aspx?type=winplaodds&date=".$racingdate."&venue=".$venue."&raceno=".$raceno;
    	// echo $url."<br>";
    // while(!$windata){
    	$windata = postRequest($url,[]);
    // }
    // $windata = getFileFTW($url);
    
    	// print_r($windata);
    	$json_data = json_decode($windata, true);
    	$output_string = $json_data['OUT'];
    	$output_array = explode(';', $output_string);
    	$first_value = substr($output_array[0], 0, strpos($output_array[0], '@@@'));
    
    	$table_data = array();
    
    	for ($i = 1; $i < count($output_array); $i++) {
    		$row_data = explode('=', $output_array[$i]);
    		$horseno = $row_data[0];
    		$odds = $row_data[1];
    		$winarray[$raceno][$horseno] = $odds;
    	}
    	// print_r($winarray[$raceno]);
    	// echo "<br><br><br>";
    }
    // print_r($winarray);
    if($mm) { echo "[3.1]-".number_format(microtime(true) - $time_start, 2)."-"; }
    // if($qinmatrix){ //-----------------------------------------------------------------------------------------------------------------------------------------
        //QQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQ----------------------------------------------------------------------------------------
        // http://bet2.hkjc.com/racing/getJSON.aspx?type=qin&date=2018-07-01&venue=ST&raceno=1
        // http://bet2.hkjc.com/racing/getJSON.aspx?type=qpl&date=2018-07-01&venue=ST&raceno=1
        for ($raceno = $start; $raceno <= $end; $raceno++) {
        	$url = "https://bet2.hkjc.com/racing/getJSON.aspx?type=qin&date=".$racingdate."&venue=".$venue."&raceno=".$raceno;
        	$qin[$raceno] = postRequest($url,[]);
        // 	print_r($qin);
        	// echo "<br><br><br>";
        }
        for ($raceno = $start; $raceno <= $end; $raceno++) {
        	$url = "https://bet2.hkjc.com/racing/getJSON.aspx?type=qinpre&date=".$racingdate."&venue=".$venue."&raceno=".$raceno;
        	$qinpre[$raceno] = postRequest($url,[]);
        	// print_r($qin);
        	// echo "<br><br><br>";
        }
        for ($raceno = $start; $raceno <= $end; $raceno++) {
        	$url = "https://bet2.hkjc.com/racing/getJSON.aspx?type=qpl&date=".$racingdate."&venue=".$venue."&raceno=".$raceno;
        	$qpl[$raceno] = postRequest($url,[]);
        	// print_r($qin);
        	// echo "<br><br><br>";
        }
}
if($mm) { echo "[3.2]-".number_format(microtime(true) - $time_start, 2)."-"; }
//----------------------------------------------------------------------------------------
$url = "https://bet2.hkjc.com/racing/getJSON.aspx?type=winplaodds&date=".$racingdate."&venue=".$venue."&start=1&end=".$end;
$url_pre = "https://bet2.hkjc.com/racing/getJSON.aspx?type=winplaoddspre&date=".$racingdate."&venue=".$venue."&start=1&end=".$end;
// echo $url."<br>";
// echo $url_pre."<br>";
$obj_pre = postRequest($url,[]);
$obj = postRequest($url_pre,[]);
// print_r ($obj);
// print_r ($obj_pre);

// $RaceCard_url = "https://racing.hkjc.com/racing/SystemDataPage/racing/overseas/RaceCard-SystemDataPage.aspx?match_id=".str_replace("-","",$racingdate)."/".$venue."/".$raceno."&lang=Chinese";
// echo $RaceCard_url."<br>";
//-----------------------------------------------------------------------------------------------------------------------------------------
if($mm) { echo "[3.3]-".number_format(microtime(true) - $time_start, 2)."-"; }
$tri20 = array();
$cnt=0;
for ($raceno = $start; $raceno <= $end; $raceno++) {
	// $tri = "https://bet2.hkjc.com/racing/getJSON.aspx?type=tritop&date=2022-07-27&venue=S2&raceno=1";
	$tri = "https://bet2.hkjc.com/racing/getJSON.aspx?type=tritop&date=".$racingdate."&venue=".$venue."&raceno=".$raceno;
	$tri = postRequest($tri,[]);
	list($t['body'], $t['odds']) = explode(':',$tri);
	list($t['body'], $t['odds']) = explode('@;',$t['odds']);
	$tristr = "".str_replace('"}','',$t['odds']);
	$racearray = explode(';', $tristr);
	// print_r($racearray);
	$cnt=0;
		foreach($racearray as $key=>$value){
			$arr = explode('=', $value);
			// echo $arr[0];
			// echo $arr[1];	
			foreach (explode('-', $arr[0]) as $v) {
				$tritemp[] = $v;
				// echo $v."<br>";
				$tri20[$raceno][$v]++;
				$tri20[$raceno]['top'][] = $cnt ? "" : $v;
			}
			$cnt++;
		}
}
// print_r($tri20[1]['top']);
// print_r($tri20[2]['top']);
// if(in_array('3',($tri20[1]['top']))){ echo 1; }
//-----------------------------------------------------------------------------------------------------------------------------------------
// https://bet2.hkjc.com/racing/getJSON.aspx?type=qtttop&date=2022-07-27&venue=S2&raceno=1
$qtt20 = array();
$cnt=0;
for ($raceno = $start; $raceno <= $end; $raceno++) {
	$qtt = "https://bet2.hkjc.com/racing/getJSON.aspx?type=qtttop&date=".$racingdate."&venue=".$venue."&raceno=".$raceno;
	$qtt = postRequest($qtt,[]);
	list($t['body'], $t['odds']) = explode(':',$qtt);
	list($t['body'], $t['odds']) = explode('@;',$t['odds']);
	$tristr = "".str_replace('"}','',$t['odds']);
	$racearray = explode(';', $tristr);
// 	print_r($racearray);
// 	echo "---<br>";
	$cnt=0;
		foreach($racearray as $key=>$value){
			$arr = explode('=', $value);
			// echo $arr[0];
			// echo $arr[1];	
			foreach (explode('-', $arr[0]) as $v) {
				$tritemp[] = $v;
				// echo $v."<br>";
				$qtt20[$raceno][$v]++;
				$qtt20[$raceno]['top'][] = $cnt ? "" : $v;
			}
			$cnt++;
		}
}
// print_r($qtt20);
//-----------------------------------------------------------------------------------------------------------------------------------------
// https://bet2.hkjc.com/racing/getJSON.aspx?type=qtttop&date=2022-07-27&venue=S2&raceno=1
$fft20 = array();
$cnt=0;
for ($raceno = $start; $raceno <= $end; $raceno++) {
	$fft = "https://bet2.hkjc.com/racing/getJSON.aspx?type=fftop&date=".$racingdate."&venue=".$venue."&raceno=".$raceno;
	$fft = postRequest($fft,[]);
	list($t['body'], $t['odds']) = explode(':',$fft);
	list($t['body'], $t['odds']) = explode('@;',$t['odds']);
	$tristr = "".str_replace('"}','',$t['odds']);
	$racearray = explode(';', $tristr);
	// print_r($racearray);
	$cnt=0;
		foreach($racearray as $key=>$value){
			$arr = explode('=', $value);
			// echo $arr[0];
			// echo $arr[1];	
			foreach (explode('-', $arr[0]) as $v) {
				$tritemp[] = $v;
				// echo $v."<br>";
				$fft20[$raceno][$v]++;
				$fft20[$raceno]['top'][] = $cnt ? "" : $v;
			}
			$cnt++;
		}
}
// print_r($qtt20);
//-----------------------------------------------------------------------------------------------------------------------------------------
// https://bet2.hkjc.com/racing/getJSON.aspx?type=tcetop&date=2022-07-27&venue=S2&raceno=1
$ttt20 = array();
$cnt=0;
for ($raceno = $start; $raceno <= $end; $raceno++) {
	$ttt = "https://bet2.hkjc.com/racing/getJSON.aspx?type=tcetop&date=".$racingdate."&venue=".$venue."&raceno=".$raceno;
	$ttt = postRequest($ttt,[]);
	list($t['body'], $t['odds']) = explode(':',$ttt);
	list($t['body'], $t['odds']) = explode('@;',$t['odds']);
	$tristr = "".str_replace('"}','',$t['odds']);
	$racearray = explode(';', $tristr);
	// print_r($racearray);
	$cnt=0;
		foreach($racearray as $key=>$value){
			$arr = explode('=', $value);
			// echo $arr[0];
			// echo $arr[1];	
			foreach (explode('-', $arr[0]) as $v) {
				$tritemp[] = $v;
				// echo $v."<br>";
				$ttt20[$raceno][$v]++;
				$ttt20[$raceno]['top'][] = $cnt ? "" : $v;
			}
			$cnt++;
		}
}
// print_r($ttt20);
// die(1);
if($mm) { echo "[4]-".number_format(microtime(true) - $time_start, 2)."-"; }


// mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$racecard_sql = "select COUNT(*) as cnt from RaceCard where racingdate='".$racingdate."' and venue='".$venue."'";
$racecard_sql = "select * from RaceCard where racingdate='".$racingdate."' and venue='".$venue."'";
// echo 1;
// echo $racecard_sql ;
$racecard_sql = mysqli_query($mysqli, $racecard_sql);
// $save_racecard = mysql_num_rows($racecard_sql) ? 1 : 0;
// $save_racecard = mysqli_fetch_object($racecard_sql);
// echo $save_racecard->cnt;
$nofrec = $racecard_sql->num_rows;

// die(1);
// if(1){
 if($_GET['save2db'] && $nofrec==0){ //-----------------------------------------------------------------------------------------------------------------------------------------
	// echo $_GET['save2db'].$save_racecard;
	$logs .= "-racecard2db";
	$insert = "	INSERT INTO logs
		VALUES('','ai-save2db',now())";
// echo $insert;
	mysqli_query($mysqli, $insert);
// 	die(1);
	$rowData = array();
	$data=0;    
	$update_RaceCard = "";
	for ($raceno = $start; $raceno <= $end; $raceno++) {
	    $venue = strtoupper ($venue);
		if(substr($venue,0,2)=="S1" || $venue == "S2" || $venue == "S3" || $venue == "S4" || $venue == "S5" || $venue == "S6"){
			// $RaceCard_url = "https://racing.hkjc.com/racing/overseas/chinese/racecard.aspx?para=/".str_replace("-","",$racingdate)."/".$venue."/".$raceno; //20220521/S1/1";
			// $RaceCard_url = "https://bet2.hkjc.com/racing/pages/odds_wp.aspx?lang=ch&date=".$racingdate."&venue=".$venue."&raceno=".$raceno;
			// $RaceCard_url = "https://bet2.hkjc.com/racing/pages/odds_wp.aspx?lang=ch&date=2022-07-27&venue=S2&raceno=1
			$RaceCard_url = "https://racing.hkjc.com/racing/SystemDataPage/racing/overseas/RaceCard-SystemDataPage.aspx?match_id=".str_replace("-","",$racingdate)."/".$venue."/".$raceno."&lang=Chinese";
		}else{
			$RaceCard_url = "https://racing.hkjc.com/racing/information/chinese/Racing/Racecard.aspx?RaceDate=".str_replace("-","/",$racingdate)."&Racecourse=".$venue."&RaceNo=".$raceno;
		}

		// $RaceCard_url = "https://racing.hkjc.com/racing/information/English/Racing/Racecard.aspx?RaceDate=2022/05/07&Racecourse=ST&RaceNo=1";
// 		echo $RaceCard_url."<br>";

		$html = postRequest($RaceCard_url,[]);
		$htmls = new simple_html_dom();
		$htmls->load($html);
		//Horse No.	Last 6 Runs	Colour	Horse	Wt.	Jockey	Draw	Trainer	Rtg.	Rtg.+/-	Horse Wt. (Declaration)	Priority	Gear
		//Horse No. Last 6 Runs Colour Horse Brand No. Wt. Jockey Over Wt. Draw Trainer Int'l Rtg. Rtg. Rtg.+/- Horse Wt. (Declaration) Wt.+/- (vs Declaration) Best Time Age WFA Sex Season Stakes Priority Gear Owner Sire Dam Import Cat.
		$cnt=0;
		//local-------------------
		// print_r($htmls);		
		if(substr($venue,0,2)=="S1" || $venue == "S2" || $venue == "S3" || $venue == "S4" || $venue == "S5" || $venue == "S6"){
// 			echo "-".$htmls->find('//p[class="info"]')->innertext;		
			foreach($htmls->find('//table[class="draggable"] tr') as $row){
				foreach($row->find('td') as $cell) {
					// if(isset($cell->href)){
						if(substr(trim($cell->innertext),0,1)=="<"){
							$rowData[$raceno][$cnt][] = $cell->find('a', 0)->innertext;
				// 			echo $cell->find('a', 0)->innertext."<br>";
						}else{		
							$str = str_replace('<span class="color_red">',"",$cell->innertext);
							$str = str_replace("<span>","",$str);
							$rowData[$raceno][$cnt][] = $str;
				// 			echo $str."<br>";
						}
					}
					$cnt++;				
			}
		}else{
			foreach($htmls->find('//table[class="starter f_tac f_fs13 draggable hiddenable"]  tr') as $row){	
				// print_r($row);		
				foreach($row->find('td') as $cell) {
					// if(isset($cell->href)){
					if(substr(trim($cell->innertext),0,1)=="<"){
						$rowData[$raceno][$cnt][] = $cell->find('a', 0)->innertext;
						// echo $cell->find('a', 0)->href . '<br>';
						// echo $cell->find('a', 0)->innertext . '<br>';
					}else{		
						$rowData[$raceno][$cnt][] = $cell->innertext;
						// echo $raceno."-".$cnt."-".$cell->innertext."-".$rowData[$raceno][$cnt]."<br>";
					}
					// echo $raceno."-".$cnt."-".$cell->innertext."-".$rowData[$raceno][$cnt]."<br>";
				}
				$cnt++;
			}
		}
	}
// 	print_r($rowData);
	// CREATE TABLE RaceCard (
	//     id INT(11) NOT NULL AUTO_INCREMENT,
	//     racingdate date,
	// 	venue varchar(5),
	// 	raceno varchar(5),
	//     HorseNo varchar(50),
	//     Last6Runs varchar(50),
	//     Colour varchar(50),
	//     horsename varchar(50),
	//     BrandNo varchar(50),
	// 	Wt varchar(50),
	//     Jockey varchar(50),
	//     OverWt varchar(50),
	//     Draw varchar(50),
	// 	Trainer varchar(50),
	// 	IntlRtg varchar(50),
	// 	Rtg varchar(50),
	// 	Rtgdiff varchar(50),
	// 	HorseWt varchar(50),
	// 	HorseWtDiff varchar(50),
	// 	BestTime varchar(50),
	// 	Age varchar(50),
	// 	WFA varchar(50),
	// 	Sex varchar(50),
	// 	SeasonStakes varchar(50),
	// 	Priority varchar(50),
	// 	Gear varchar(50),
	// 	Owner varchar(50),
	// 	Sire varchar(50),
	// 	Dam varchar(50),
	// 	ImportCat varchar(50),
	//     PRIMARY KEY (id),
	// 	KEY barrierday (racingdate),
	// 	KEY venue (venue),
	// 	KEY horsename (horsename) 
	// 	) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
	// 	) ENGINE=InnoDB DEFAULT CHARSET=utf8
	// 	) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

	$RaceCard_array = array();
	foreach($rowData as $raceno => $data){
		foreach($data as $row => $rowdetail){
			// echo "<br>".$raceno."-".$row."-".$rowdetail;
			// print_r( $rowdetail);	//$rowData[$raceno][$horseno][1];
			if(substr($venue,0,2)=="S1" || $venue == "S2" || $venue == "S3" || $venue == "S4" || $venue == "S5" || $venue == "S6"){
				$RaceCard_array[$raceno][$rowdetail[0]]['HorseNo'] = $rowdetail['1'];
				$RaceCard_array[$raceno][$rowdetail[0]]['Last6Runs'] = "";
				$RaceCard_array[$raceno][$rowdetail[0]]['Colour'] = "";
				$RaceCard_array[$raceno][$rowdetail[0]]['horsename'] = $rowdetail['3'];				
				$RaceCard_array[$raceno][$rowdetail[0]]['BrandNo'] = "";
				$RaceCard_array[$raceno][$rowdetail[0]]['Wt'] = $rowdetail['6'];
				$RaceCard_array[$raceno][$rowdetail[0]]['Jockey'] = $rowdetail['7'];
				$RaceCard_array[$raceno][$rowdetail[0]]['OverWt'] = "";
				$RaceCard_array[$raceno][$rowdetail[0]]['Draw'] = $rowdetail['4'];
				$RaceCard_array[$raceno][$rowdetail[0]]['Trainer'] = $rowdetail['5'];
				$RaceCard_array[$raceno][$rowdetail[0]]['IntlRtg'] = "";
				$RaceCard_array[$raceno][$rowdetail[0]]['Rtg'] = "";
				$RaceCard_array[$raceno][$rowdetail[0]]['Rtgdiff'] = "";
				$RaceCard_array[$raceno][$rowdetail[0]]['HorseWt'] = "";
				$RaceCard_array[$raceno][$rowdetail[0]]['HorseWtDiff'] = "";
				$RaceCard_array[$raceno][$rowdetail[0]]['BestTime'] = "";
				$RaceCard_array[$raceno][$rowdetail[0]]['Age'] = $rowdetail['9'];
				$RaceCard_array[$raceno][$rowdetail[0]]['WFA'] = "";
				$RaceCard_array[$raceno][$rowdetail[0]]['Sex'] = $rowdetail['10'];
				$RaceCard_array[$raceno][$rowdetail[0]]['SeasonStakes'] = "";
				$RaceCard_array[$raceno][$rowdetail[0]]['Priority'] = "";
				$RaceCard_array[$raceno][$rowdetail[0]]['Gear'] = "";
				$RaceCard_array[$raceno][$rowdetail[0]]['Owner'] = "";
				$RaceCard_array[$raceno][$rowdetail[0]]['Sire'] = "";
				$RaceCard_array[$raceno][$rowdetail[0]]['Dam'] = "";
				$RaceCard_array[$raceno][$rowdetail[0]]['ImportCat'] = "";
				//----------------------------------------------------------------------------------
				$update_RaceCard .= '(NULL, "'.$racingdate.'", "'.$venue.'", "'.$raceno.'", "'.
					$RaceCard_array[$raceno][$rowdetail[0]]['HorseNo'].'","'.
					"-".'","'.
					"-".'","'.
					$RaceCard_array[$raceno][$rowdetail[0]]['horsename'].'","'.
					"-".'","'.
					$RaceCard_array[$raceno][$rowdetail[0]]['Wt'].'","'.
					$RaceCard_array[$raceno][$rowdetail[0]]['Jockey'].'","'.
					"-".'","'.
					$RaceCard_array[$raceno][$rowdetail[0]]['Draw'].'","'.
					$RaceCard_array[$raceno][$rowdetail[0]]['Trainer'].'","'.			
					"-".'","'.
					"-".'","'.
					"-".'","'.
					"-".'","'.
					"-".'","'.
					"-".'","'.
					$RaceCard_array[$raceno][$rowdetail[0]]['Age'].'","'.
					"-".'","'.
					$RaceCard_array[$raceno][$rowdetail[0]]['Sex'].'","'.
					"-".'","'.					
					$rowdetail['20'].'","'.
					$rowdetail['21'].'","'.
					$rowdetail['22'].'","'.
					$rowdetail['23'].'","'.
					$rowdetail['24'].'","'.
					$rowdetail['25'].'"),';
					$rowdetail['25'].'"),'; 
			}else{
				if (is_numeric($rowdetail['0'])) {
					$RaceCard_array[$raceno][$rowdetail[0]]['HorseNo'] = $rowdetail['0'];
					$RaceCard_array[$raceno][$rowdetail[0]]['Last6Runs'] = $rowdetail['1'];
					$RaceCard_array[$raceno][$rowdetail[0]]['Colour'] = $rowdetail['2'];
					$RaceCard_array[$raceno][$rowdetail[0]]['horsename'] = $rowdetail['3'];
					// echo $rowdetail['3'].$rowdetail['3']->href . '<br>';
					$RaceCard_array[$raceno][$rowdetail[0]]['BrandNo'] = $rowdetail['4'];
					$RaceCard_array[$raceno][$rowdetail[0]]['Wt'] = $rowdetail['5'];
					$RaceCard_array[$raceno][$rowdetail[0]]['Jockey'] = $rowdetail['6'];
					$RaceCard_array[$raceno][$rowdetail[0]]['OverWt'] = $rowdetail['7'];
					$RaceCard_array[$raceno][$rowdetail[0]]['Draw'] = $rowdetail['8'];
					$RaceCard_array[$raceno][$rowdetail[0]]['Trainer'] = $rowdetail['9'];
					$RaceCard_array[$raceno][$rowdetail[0]]['IntlRtg'] = $rowdetail['10'];
					$RaceCard_array[$raceno][$rowdetail[0]]['Rtg'] = $rowdetail['11'];
					$RaceCard_array[$raceno][$rowdetail[0]]['Rtgdiff'] = $rowdetail['12'];
					$RaceCard_array[$raceno][$rowdetail[0]]['HorseWt'] = $rowdetail['13'];
					$RaceCard_array[$raceno][$rowdetail[0]]['HorseWtDiff'] = $rowdetail['14'];
					$RaceCard_array[$raceno][$rowdetail[0]]['BestTime'] = $rowdetail['15'];
					$RaceCard_array[$raceno][$rowdetail[0]]['Age'] = $rowdetail['16'];
					$RaceCard_array[$raceno][$rowdetail[0]]['WFA'] = $rowdetail['17'];
					$RaceCard_array[$raceno][$rowdetail[0]]['Sex'] = $rowdetail['18'];
					$RaceCard_array[$raceno][$rowdetail[0]]['SeasonStakes'] = $rowdetail['19'];
					$RaceCard_array[$raceno][$rowdetail[0]]['Priority'] = $rowdetail['20'];
					$RaceCard_array[$raceno][$rowdetail[0]]['Gear'] = $rowdetail['21'];
					$RaceCard_array[$raceno][$rowdetail[0]]['Owner'] = $rowdetail['22'];
					$RaceCard_array[$raceno][$rowdetail[0]]['Sire'] = $rowdetail['23'];
					$RaceCard_array[$raceno][$rowdetail[0]]['Dam'] = $rowdetail['24'];
					$RaceCard_array[$raceno][$rowdetail[0]]['ImportCat'] = $rowdetail['25'];
					$RaceCard_array[$raceno][$rowdetail[0]]['ImportCat'] = $rowdetail['25'];
				
					$update_RaceCard .= '(NULL, "'.$racingdate.'", "'.$venue.'", "'.$raceno.'", "'.
						$rowdetail['0'].'","'.
						$rowdetail['1'].'","'.
						$rowdetail['2'].'","'.
						$rowdetail['3'].'","'.
						$rowdetail['4'].'","'.
						$rowdetail['5'].'","'.
						$rowdetail['6'].'","'.
						$rowdetail['7'].'","'.
						$rowdetail['8'].'","'.
						$rowdetail['9'].'","'.
						$rowdetail['10'].'","'.
						$rowdetail['11'].'","'.
						$rowdetail['12'].'","'.
						$rowdetail['13'].'","'.
						$rowdetail['14'].'","'.
						$rowdetail['15'].'","'.
						$rowdetail['16'].'","'.
						$rowdetail['17'].'","'.
						$rowdetail['18'].'","'.
						$rowdetail['19'].'","'.
						$rowdetail['20'].'","'.
						$rowdetail['21'].'","'.
						$rowdetail['22'].'","'.
						$rowdetail['23'].'","'.
						$rowdetail['24'].'","'.
						$rowdetail['25'].'"),'; 
						$rowdetail['25'].'"),'; 
				}
			}
			
		}
	}

	// echo "INSERT INTO `RaceCard` (`id`, `racingdate`, `venue`, `raceno`, `HorseNo`, `Last6Runs`, `Colour`, `horsename`, `BrandNo`, `Wt`, `Jockey`, `OverWt`, `Draw`, `Trainer`, `IntlRtg`, `Rtg`, `Rtgdiff`, `HorseWt`, `HorseWtDiff`, `BestTime`, `Age`, `WFA`, `Sex`, `SeasonStakes`, `Priority`, `Gear`, `Owner`, `Sire`, `Dam`, `ImportCat`) VALUES (NULL, NULL, NULL, NULL, '123', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);";
	$update_RaceCard = substr($update_RaceCard,0,strlen($update_RaceCard)-1).";";
// 	$sql = "INSERT INTO `racecard` VALUES 
// 	".$update_RaceCard;
	$sql = "INSERT INTO `RaceCard` (`id`, `racingdate`, `venue`, `raceno`, `HorseNo`, `Last6Runs`, `Colour`, `horsename`, `BrandNo`, `Wt`, `Jockey`, `OverWt`, `Draw`, `Trainer`, `IntlRtg`, `Rtg`, `Rtgdiff`, `HorseWt`, `HorseWtDiff`, `BestTime`, `Age`, `WFA`, `Sex`, `SeasonStakes`, `Priority`, `Gear`, `Owner`, `Sire`, `Dam`, `ImportCat`) VALUES 
	".$update_RaceCard;
	// $sql = "INSERT INTO RaceCard (id, racingdate, venue, raceno, HorseNo, Last6Runs, Colour, horsename, BrandNo, Wt, Jockey, OverWt, Draw, Trainer, IntlRtg, Rtg, Rtgdiff, HorseWt, HorseWtDiff, BestTime, Age, WFA, Sex, SeasonStakes, Priority, Gear, Owner, Sire, Dam, ImportCat) VALUES 
	// 	".$update_RaceCard;
	// echo $sql."<>";
// 	echo 2;
	// if($racingdate == date("Y-m-d")) {
		mysqli_query($mysqli, $sql);
		$error_message = mysqli_error($mysqli);

		if($error_message == ""){
			// echo "No error related to SQL query.";
			$recipients_BCC = array(
				'hkhorsepaper@gmail.com' => '.'
			
				// ..
			 );
			// $ipAddress = substr($_SERVER['REMOTE_ADDR'],-3);
			$ipAddress = substr($_SERVER['SERVER_NAME'],-3);
			mailto('HK Horse Paper :'.$ipAddress,'RaceCard were updated.','3','',$recipients_BCC,'4');
		}else{
			// echo "Query Failed: ".$error_message;
		}
		// mysqli_close($mysqli);
	// }
	// print_r($RaceCard_array);
}
$racecard_sql = "select *,
	(select concat(win_odds,'[',pla,'[',Running_Position,'(',Dr) from horseinfo where horseid=RaceCard.brandno and jockey=SUBSTRING_INDEX(RaceCard.jockey,'(',1) and horseinfo.date<>'$racingdate' order by horseinfo.date desc limit 1) as samejockey,
	(select Dist from horseinfo where horseid=RaceCard.brandno and jockey=SUBSTRING_INDEX(RaceCard.jockey,'(',1) and horseinfo.date<>'$racingdate' order by horseinfo.date desc limit 1) as Dist,
	(select concat(win_odds,'[',pla,'(',Dr) from horseinfo where horseid=RaceCard.brandno and horseinfo.date<>'$racingdate' order by horseinfo.date desc limit 1) as lastodds,
	(select datediff('$racingdate',date) from horseinfo where horseid=RaceCard.brandno and horseinfo.date<>'$racingdate' order by horseinfo.date desc limit 1) as daydiff,
	if(SUBSTRING_INDEX(RaceCard.jockey,'(',1)=(select jockey from horseinfo where horseid=RaceCard.brandno and horseinfo.date<>'$racingdate' order by horseinfo.date desc limit 1),1,0) as lastjockey
	from RaceCard 
	where racingdate='".$racingdate."' and venue='".$venue."'";
// echo $racecard_sql;
// die(1);
// echo $racecard_sql;
$trainer = $jockey = $trainjockey = array();
$racecard_array = mysqli_query($mysqli, $racecard_sql);
while($row = mysqli_fetch_array($racecard_array)){
		// printf("%s (%s)\n", $row["horsename"], $row[1]);
		// $racecard[] = $row;
	$runningtype="";
	if($row['samejockey']){
		list($win_odds, $pla, $Running_Position) = explode('[', $row['samejockey']);
		list($pos1, $pos2, $pos3, $pos4) = explode(' ', $Running_Position);
		// $runningtype = ($pos1 <=3 && $pos2 <=3 )
		$runningtype = $pos1.".".$pos2.".".$pos3.".".$pos4;
	}
	$racecard[$row['raceno']][$row['HorseNo']]['samejockey'] = $row['samejockey'];
	$racecard[$row['raceno']][$row['HorseNo']]['runningtype'] = $runningtype;
	$racecard[$row['raceno']][$row['HorseNo']]['lastodds'] = $row['lastodds'];
	$racecard[$row['raceno']][$row['HorseNo']]['lastjockey'] = $row['lastjockey'];
	$racecard[$row['raceno']][$row['HorseNo']]['Dist'] = $row['Dist'];
	$racecard[$row['raceno']][$row['HorseNo']]['daydiff'] = $row['daydiff'];

	$racecard[$row['raceno']][$row['HorseNo']]['HorseNo'] = $row['HorseNo'];
	$racecard[$row['raceno']][$row['HorseNo']]['Last6Runs'] = $row['Last6Runs'];
	$racecard[$row['raceno']][$row['HorseNo']]['Colour'] = $row['Colour'];
	$racecard[$row['raceno']][$row['HorseNo']]['horsename'] = $row['horsename'];
	$racecard[$row['raceno']][$row['HorseNo']]['BrandNo'] = $row['BrandNo'];
	$racecard[$row['raceno']][$row['HorseNo']]['Wt'] = $row['Wt'];
	$racecard[$row['raceno']][$row['HorseNo']]['Jockey'] = $row['Jockey'];
	$racecard[$row['raceno']][$row['HorseNo']]['OverWt'] = $row['OverWt'];
	$racecard[$row['raceno']][$row['HorseNo']]['Draw'] = $row['Draw']*1;
	$racecard[$row['raceno']][$row['HorseNo']]['Trainer'] = $row['Trainer'];
	$racecard[$row['raceno']][$row['HorseNo']]['IntlRtg'] = $row['IntlRtg'];
	$racecard[$row['raceno']][$row['HorseNo']]['Rtg'] = $row['Rtg'];
	$racecard[$row['raceno']][$row['HorseNo']]['Rtgdiff'] = $row['Rtgdiff'];
	$racecard[$row['raceno']][$row['HorseNo']]['HorseWt'] = $row['HorseWt'];
	$racecard[$row['raceno']][$row['HorseNo']]['HorseWtDiff'] = $row['HorseWtDiff'];
	$racecard[$row['raceno']][$row['HorseNo']]['BestTime'] = $row['BestTime'];
	$racecard[$row['raceno']][$row['HorseNo']]['BestTime_val'] = ($row['BestTime'] ? str_replace('.', '',$row['BestTime']) : 0);
	
	$racecard[$row['raceno']][$row['HorseNo']]['Age'] = $row['Age'];
	$racecard[$row['raceno']][$row['HorseNo']]['WFA'] = $row['WFA'];
	$racecard[$row['raceno']][$row['HorseNo']]['Sex'] = $row['Sex'];
	$racecard[$row['raceno']][$row['HorseNo']]['SeasonStakes'] = $row['SeasonStakes'];
	$racecard[$row['raceno']][$row['HorseNo']]['Priority'] = $row['Priority'];
	$racecard[$row['raceno']][$row['HorseNo']]['Gear'] = $row['Gear'];
	$racecard[$row['raceno']][$row['HorseNo']]['Owner'] = $row['Owner'];
	$racecard[$row['raceno']][$row['HorseNo']]['Sire'] = $row['Sire'];
	$racecard[$row['raceno']][$row['HorseNo']]['Dam'] = $row['Dam'];
	$racecard[$row['raceno']][$row['HorseNo']]['ImportCat'] = $row['ImportCat'];

	$racecard[$row['raceno']][$row['HorseNo']]['Trainer_Jockey'] = $row['Trainer'].$row['Jockey'];
	
	$racecard[$row['raceno']][$row['Trainer']]++;

	// $JockeyCode_cnt[(string)$runnerinfo[$raceno][$saddleno]['Jockey']]++;
	// $TrainerCode_cnt[(string)$runnerinfo[$raceno][$saddleno]['Trainer']]++;
	$racecard['Trainer_Jockey'][(string)$row['Trainer'].(string)$row['Jockey']]++;
	$racecard['Trainer'][(string)$row['Trainer']]++;
	$racecard['Jockey'][(string)$row['Jockey']]++;

	array_push($trainerjockey,(string)$row['Trainer'].(string)$row['Jockey']);
	array_push($trainer,(string)$row['Trainer']);
	array_push($jockey,(string)$row['Jockey']);
} 

// print_r($racecard);
if($mm) { echo "[5]-".number_format(microtime(true) - $time_start, 2)."-"; }

//TJ ratio----------------------------------------------------------------------
$histperiodt = date("Y-m-d",mktime(0, 0, 0, date(m), date(d), date(Y)-1));
$TJ_sql = "select * from horseinfo_summary";
$TJ_array = mysqli_query($mysqli, $TJ_sql);
while($row = mysqli_fetch_array($TJ_array)){
	$TJratio[$row['Trainer']][$row['Jockey']]['day_racecnt'] = $row['day_racecnt'];
	$TJratio[$row['Trainer']][$row['Jockey']]['day_ispla'] = $row['day_ispla'];
	$TJratio[$row['Trainer']][$row['Jockey']]['day_iswin'] = $row['day_iswin'];
	$TJratio[$row['Trainer']][$row['Jockey']]['day_ratio'] = $row['day_racecnt'] ? round(100*$row['day_ispla']/$row['day_racecnt']) : "-";
	
	$TJratio[$row['Trainer']][$row['Jockey']]['night_racecnt'] = $row['night_racecnt'];
	$TJratio[$row['Trainer']][$row['Jockey']]['night_ispla'] = $row['night_ispla'];
	$TJratio[$row['Trainer']][$row['Jockey']]['night_iswin'] = $row['night_iswin'];
	$TJratio[$row['Trainer']][$row['Jockey']]['night_ratio'] = $row['night_racecnt'] ? round(100*$row['night_ispla']/$row['night_racecnt']) : "-";
	
	$TJratio[$row['Trainer']][$row['Jockey']]['total_ratio'] = ($row['night_racecnt'] || $row['day_racecnt']) ? round(100*($row['day_ispla']+$row['night_ispla']) / ($row['day_racecnt']+$row['night_racecnt']) ) : "-";
	
	//-----
// 	echo $row['day_racecnt1yr'];
	$TJratio[$row['Trainer']][$row['Jockey']]['day_racecnt1yr'] = $row['day_racecnt1yr'];
	$TJratio[$row['Trainer']][$row['Jockey']]['day_ispla1yr'] = $row['day_ispla1yr'];
	$TJratio[$row['Trainer']][$row['Jockey']]['day_iswin1yr'] = $row['day_iswin1yr'];
	$TJratio[$row['Trainer']][$row['Jockey']]['day_ratio1yr'] = $row['day_racecnt1yr'] ? round(100*$row['day_ispla1yr']/$row['day_racecnt1yr']) : "-";
	
	$TJratio[$row['Trainer']][$row['Jockey']]['night_racecnt1yr'] = $row['night_racecnt1yr'];
	$TJratio[$row['Trainer']][$row['Jockey']]['night_ispla1yr'] = $row['night_ispla1yr'];
	$TJratio[$row['Trainer']][$row['Jockey']]['night_iswin1yr'] = $row['night_iswin1yr'];
	$TJratio[$row['Trainer']][$row['Jockey']]['night_ratio1yr'] = $row['night_racecnt1yr'] ? round(100*$row['night_ispla1yr']/$row['night_racecnt1yr']) : "-";
	
	$TJratio[$row['Trainer']][$row['Jockey']]['total_ratio1yr'] = ($row['night_racecnt1yr'] || $row['day_racecnt1yr']) ? round(100*($row['day_ispla1yr']+$row['night_ispla1yr']) / ($row['day_racecnt1yr']+$row['night_racecnt1yr']) ) : "-";
	
	
// 	if($row){
//         $TJratio[$row['Trainer']][$row['Jockey']]['day_racecnt'] = $row['day_racecnt'];
//         $TJratio[$row['Trainer']][$row['Jockey']]['day_ispla'] = $row['day_ispla'];
//         $TJratio[$row['Trainer']][$row['Jockey']]['day_iswin'] = $row['day_iswin'];
//         $TJratio[$row['Trainer']][$row['Jockey']]['day_ratio'] = $row['day_racecnt'] ? round(100*$row['day_ispla']/$row['day_racecnt']) : "-";
        
//         $TJratio[$row['Trainer']][$row['Jockey']]['night_racecnt'] = $row['night_racecnt'];
//         $TJratio[$row['Trainer']][$row['Jockey']]['night_ispla'] = $row['night_ispla'];
//         $TJratio[$row['Trainer']][$row['Jockey']]['night_iswin'] = $row['night_iswin'];
//         $TJratio[$row['Trainer']][$row['Jockey']]['night_ratio'] = $row['night_racecnt'] ? round(100*$row['night_ispla']/$row['night_racecnt']) : "-";
        
//         $TJratio[$row['Trainer']][$row['Jockey']]['total_ratio'] = ($row['night_racecnt'] || $row['day_racecnt']) ? round(100*($row['day_ispla']+$row['night_ispla']) / ($row['day_racecnt']+$row['night_racecnt']) ) : "-";
// 	}
	
}

// mysqli_close($mysqli);
// TRI top20-----------------------------------------------------------------------------------------------------------------------------------------
// ini_set('memory_limit','160M');
// echo 4;
$rowData = array();
$data=0;    
$update_RaceCard = "";
for ($raceno = $start; $raceno <= $end; $raceno++) {
	$tri20_url = "https://bet2.hkjc.com/racing/pages/odds_tri.aspx?lang=ch&date=".$racingdate."&venue=".$venue."&raceno=".$raceno;
	// echo $tri20_url."<br>";

	// $html = postRequest($tri20_url,[]);
	// $htmls = new simple_html_dom();
	// $htmls->load($html);
	// print_r($htmls);		
	// $htmls = file_get_html($tri20_url);
	// $div = $htmls->find('div[id="container"] div[class="bodyMainTable"] div[class="bodyMainTableRow"] div[class="bodyMainOddsTable content"] div[id="oddsContentMain"] table table div[id="combOddsTable"] table tr' );
	// $div = $htmls->find("div[@id='combOddsTable'] table tr"); 
	// print_r($div);	
	// foreach($htmls->find("div[@id='combOddsTable'] table tr") as $row){	
	// foreach($htmls->find("table[style*='border:1px solid #CCCCCC;'] tr",0) as $row){		
	// 	print_r($row);	
	// 	foreach($row->find('td') as $cell) {
	// 		print_r($cell);	
	// 	}
	// }
	// $table = $div->find('table');
	// $tb = $htmls->find('table[style="border:1px solid #CCCCCC;"]  tr',0); 
	// print_r($tb);
	// foreach($div->find('table  tr') as $row){
	// foreach($htmls->getElementById("combOddsTable")->find('table tr') as $row){	
	// foreach($div->find('table[@style="border:1px solid #CCCCCC;"]  tr',0) as $row){
		// print_r($row);
		// foreach($row->find('td') as $cell) {
		// 	// if(isset($cell->href)){
		// 		echo $cell->find('strong')->innertext;
		// 	if(substr(trim($cell->innertext),0,1)=="<"){
		// 		$rowData[$raceno][$cnt][] = $cell->find('a', 0)->innertext;
		// 	}else{		
		// 		$rowData[$raceno][$cnt][] = $cell->innertext;
		// 	}
		// 	echo $raceno."-".$cnt."-".$cell->innertext."<br>";
		// }
		// $cnt++;
	// }
}

for ($raceno = $start; $raceno <= $end; $raceno++) {
// https://racing.hkjc.com/racing/overseas/chinese/results.aspx?para=/20230325/S2/1
	$result_url = "https://racing.hkjc.com/racing/overseas/chinese/results.aspx?para=/".str_replace("-","",$racingdate)."/".$venue."/".$raceno;
// 	echo $result_url."<br>";
	// $html = postRequest($result_url,[]);
	// $htmls = new simple_html_dom();
	// $htmls->load($html);
	// $race_cnt=1;
	// // foreach($htmls->find('div[class="simInsideContent simResults"]') as $group){
	// 	$pla=0;
	// 	foreach($group->find('table[class="resultsTable draggable"] tr') as $row){
	// 		$rowData = array();
	// 		$data=0;
	// 		foreach($row->find('td') as $cell) {		
	// 			$rowData[] = $cell->innertext;
	// 			echo $race_cnt."-".$result_cnt."-".$cell->innertext."<br>";
	// 			$data++;
	// 		}
			
	// 		if($pla==14){
	// 			// echo $race_cnt."-".$pla."-".$rowData[1]."<br>";
	// 			$result[$race_cnt][$pla] = $rowData[1];
	// 		}
	// 		$pla++;
	// 	}
	// 	$race_cnt++;
	// // }

}

if($mm) { echo "[6]-".number_format(microtime(true) - $time_start, 2)."-"; }
// echo strtotime($hkcurrenttime)."xx".strtotime($racetimehm['1']);
// if(strtotime($hkcurrenttime) > strtotime($racetimehm['1'])){

if(1){
    
//-----------------------------------------------------------------------------------------------------------------------------------------------
    $result_url = "https://racing.hkjc.com/racing/information/Chinese/Racing/ResultsAll.aspx?RaceDate=".str_replace("-","/",$racingdate);
    // https://racing.hkjc.com/racing/overseas/chinese/results.aspx?para=/20221101/S2
    // https://racing.hkjc.com/racing/overseas/Chinese/results.aspx?para=/20230225/S1/1
    // https://racing.hkjc.com/racing/information/chinese/Racing/LocalResults.aspx?RaceDate=2023/07/09&Racecourse=ST
    // https://bet2.hkjc.com/racing/pages/results.aspx?lang=ch&date=2023-08-13&venue=S1&raceno=6
    // echo $result_url.$venue;
    $html = postRequest($result_url,[]);
    $htmls = new simple_html_dom();
    $htmls->load($html);
    // print_r($htmls);
    //die(1);
    
    $group=array();
    $race=array();
    $result = array(); // Initialize the $result array
    $resultfull=array();
    $race_cnt=1;
    
    $html = str_get_html($htmls);

    $raceno = array();
    
    foreach ($html->find('div[class=race_result]') as $race_result) {
        $eachrace = $race_result->find('div.f_fs13.margin_top15');
        foreach ($eachrace as $key => $div) {
            $pla = 1; // Initialize the variable $pla
            $racetitle = $div->find('div.color_w.f_fs13.font_wb', 0)->innertext;
            $racetitleno = preg_replace('/\D/', '', $racetitle); // Extract the numeric part
            
            $race_cnt = ''; // Set the $race_cnt variable
            
            foreach($div->find('table[class=f_fs13 f_tac result] tr') as $row) {
                $rowData = array();
                foreach($row->find('td') as $cell) {
                    $rowData[] = $cell->innertext;
                    // echo $cell->innertext."<br>";
                }
                // echo $rowData[0].$rowData[1].$rowData[2]."<br>";
                if($rowData[1]){
                    $rankpla = $rowData[0];
                    $result[$racetitleno][$rankpla] = $rowData[1]; // Use $pla as the key for $result array
                    $resultx[$venue][$racetitleno][$rowData[1]] = $rankpla; // Use $pla as the key for $resultfull array
                    $resultfull[$venue][$racetitleno][$rowData[1]] = $rankpla; // Use $pla as the key for $resultfull array
                    // echo $venue.$racetitleno.$rankpla.$rowData[1]."<br>";
                    
                    $pla++; // Increment the $pla variable
                }
            }
        }
    }

    // Output the raceno
    // print_r($resultx);
    // echo "xxx<br>";
    // print_r($resultfull);
    //---------------------------------------------------------------------------------------------------------------------------
    // foreach($htmls->find('div[class=f_fs13 margin_top15]') as $group){
    // // foreach($htmls->find('div[class=race_result]') as $group){
        
    // 	$pla=0;
    
    //     $divElement = $group->find('div[class=bg_blue color_w f_fs13 font_wb]', 0)->innertext;
    //     $divElements = $group->find('div[class=bg_green color_w f_fs13 font_wb]', 0)->innertext;
    //     $value = $divElement ? $divElement : $divElements;
    //     // Extract the value within parentheses
    //     // $matches = [];
    //     // preg_match('/\((.*?)\)/', $value, $matches);
    //     // $result_venue = $matches[1];
    //     if (preg_match('/\((.*?)\)/', $string, $matches)) {
    //         $result_venue = $matches[1];
    //     } else {
    //         $result_venue = "";
    //     }
        
    //     // Extract the race number using regular expressions
    //     if (preg_match('/第\s*(\d+)\s*場/', $string, $matches)) {
    //         $raceno = intval($matches[1]);
    //     } else {
    //         $raceno = 0;
    //     }
    //     // echo $value.$result_venue . $raceno."<br>";
        
    // 	foreach($group->find('table[class=f_fs13 f_tac result] tr') as $row){
    // 		$rowData = array();
    // 		$data=0;
    // 		foreach($row->find('td') as $cell) {		
    // 			$rowData[] = $cell->innertext;
    // // 			echo $race_cnt."-".$result_cnt."-".$cell->innertext."<br>";
    // 			$data++;
    // 		}
    // 		$result[$race_cnt][$pla] = $rowData[1];
    // 		$resultfull[$result_venue][$race_cnt][$pla] = $rowData[1];
    // 		$pla++;
    // 	}
    // 	$race_cnt++;
    // }
    /**************************************************************************************** */
    $venue = strtoupper($venue);
    if(substr($venue,0,2)=="S1" || $venue == "S2" || $venue == "S3" || $venue == "S4" || $venue == "S5" || $venue == "S6"){
    	$race_cnt=1;
    	foreach($htmls->find('div[class=f_fs13 margin_top15]') as $group){
    	    
    		$pla=1;
    		$rank=1;
    		$trcount=0;
    		
            // $divElement = $group->find('div[class=color_w f_fs13 font_wb]', 0); //bg_green 
            $racetitle = $group->find('div.color_w.f_fs13.font_wb', 0)->innertext;
            // $string = $divElement->innertext;
            // Extract the value within parentheses
            // $matches = [];
            // preg_match('/\((.*?)\)/', $value, $matches);
            $parts = explode('-', $racetitle);
            $part1 = trim($parts[0]);
            $part2 = trim($parts[1]);

            $startPos = strpos($part1, '(');
            // Extract the two characters after "("
            $racetitle_venue = substr($racetitle, $startPos + 2, 2);
            $raceno = trim(preg_replace('/[^0-9]/', '', $part2));
            
            // $venue = $value; //$matches[1];
            // echo $result_venue . $string."x<br>";
            
    //         if($tmpvenuee <> $result_venue) $race_cnt=1;
    // 		foreach($group->find('table[class=f_fs13 f_tac dividends] tr') as $row){
    // 			$rowData = array();
    // 			$data=0;
    // 			foreach($row->find('td') as $cell) {		
    // 				$rowData[] = $cell->innertext;
    // 				// echo $race_cnt."-".$result_cnt."-".$cell->innertext."<br>";
    // 				$data++;
    // 			}
    			
    // 			if($pla==14){
    // 				echo $race_cnt."-".$rank."-".$rowData[1]."<br>";
    // 				$result[$race_cnt][$pla] = $rowData[1];
    // 				$resultx[$result_venue][$race_cnt][$rank] = $rowData[1];
    // 				$resultfull[$result_venue][$race_cnt][$pla] = $rowData[1];
    // 			}
    // 			$pla++;
    // 			$rank++;
    // 		}
    // 		if($pla) $race_cnt++;
    // 		$tmpvenuee = $result_venue;
    
            foreach($group->find('table[class=f_fs13 f_tac dividends] tr') as $row) {
                $rowData = array();
                foreach($row->find('td') as $cell) {
                    $rowData[] = $cell->innertext;
                    // echo $cell->innertext."<br>";
                }
                // echo $trcount."--".$rowData[0].$rowData[1].$rowData[2]."<br>";
                
                if($trcount==14 && $rowData[1]){    //tr14 = QQF
                    $ttt = $rowData[1];
                    $array = explode(',', $ttt);
                    // $result[$racetitleno][$rankpla] = $rowData[1]; // Use $pla as the key for $result array
                    $result[$raceno][1] = $array[0]; 
                    $result[$raceno][2] = $array[1]; 
                    $result[$raceno][3] = $array[2]; 
                    $result[$raceno][4] = $array[3]; 
                    
                    $resultfull[$racetitle_venue][$raceno][14] = $ttt;
                    $resultfull[$racetitle_venue][$raceno][1] = $array[0]; 
                    $resultfull[$racetitle_venue][$raceno][2] = $array[1]; 
                    $resultfull[$racetitle_venue][$raceno][3] = $array[2]; 
                    $resultfull[$racetitle_venue][$raceno][4] = $array[3]; 
                    // echo $venue.$raceno.$ttt."<br>";
                    
                    // $resultx[$venue][$raceno][1] = $array[0]; 
                    // $resultx[$venue][$raceno][2] = $array[1]; 
                    // $resultx[$venue][$raceno][3] = $array[2]; 
                    // $resultx[$venue][$raceno][4] = $array[3]; 
                    
                    $resultx[$racetitle_venue][$raceno][$array[0]] = 1; 
                    $resultx[$racetitle_venue][$raceno][$array[1]] = 2; 
                    $resultx[$racetitle_venue][$raceno][$array[2]] = 3; 
                    $resultx[$racetitle_venue][$raceno][$array[3]] = 4; 
                    // print_r($resultx);
                    $pla++; // Increment the $pla variable
                }
                $trcount++;
            }
            
    	}
    }
}
/*****************************************************************************************/
// print_r($resultx);
// print_r($resultarray);
// $resultx=array();
$resultarray=array();

$sql_update="";
if(!empty($result)){
	foreach($result as $raceno => $data){
        $sql_case="";

// 		echo $resultfull[$raceno][14];
		if(substr($venue,0,2)=="S1" || $venue == "S2" || $venue == "S3" || $venue == "S4" || $venue == "S5" || $venue == "S6"){
			$place = explode(",", $resultfull[$venue][$raceno][14]);
 
			$sql_update .= "UPDATE `RaceCard` SET `result` = 1 WHERE `RaceCard`.`racingdate` ='".$racingdate."' and `RaceCard`.`venue` ='".$venue."' and `RaceCard`.`raceno` ='".$raceno."' and `RaceCard`.`horseno` ='".$place[0]."'; ";
			$sql_update .= "UPDATE `RaceCard` SET `result` = 2 WHERE `RaceCard`.`racingdate` ='".$racingdate."' and `RaceCard`.`venue` ='".$venue."' and `RaceCard`.`raceno` ='".$raceno."' and `RaceCard`.`horseno` ='".$place[1]."'; ";
			$sql_update .= "UPDATE `RaceCard` SET `result` = 3 WHERE `RaceCard`.`racingdate` ='".$racingdate."' and `RaceCard`.`venue` ='".$venue."' and `RaceCard`.`raceno` ='".$raceno."' and `RaceCard`.`horseno` ='".$place[2]."'; ";
			$sql_update .= "UPDATE `RaceCard` SET `result` = 4 WHERE `RaceCard`.`racingdate` ='".$racingdate."' and `RaceCard`.`venue` ='".$venue."' and `RaceCard`.`raceno` ='".$raceno."' and `RaceCard`.`horseno` ='".$place[3]."'; ";
// 			$sql_case =" when `RaceCard`.`horseno` ='".$place[0]."' then  1 ";
// 			$sql_case =" when `RaceCard`.`horseno` ='".$place[1]."' then  2 ";
// 			$sql_case =" when `RaceCard`.`horseno` ='".$place[2]."' then  3 ";
// 			$sql_case =" when `RaceCard`.`horseno` ='".$place[3]."' then  4 ";
			$resultarray[$raceno] = array($place[0],$place[1],$place[2],$place[3]);
		}else{
		  //  print_r($data);
		  //  echo "<br>";
			foreach($data as $pla => $horseno){
				// echo $raceno."-".$pla."-".$horseno."<br>";
				// $resultx[$venue][$raceno][$horseno] = ($pla < 5) ? $pla : "";
				if($horseno){
					// $sql_update = " update `racecard` SET `result` = $pla WHERE `racecard`.`racingdate` ='".$racingdate."' and `racecard`.`venue` ='".$venue."' and `racecard`.`raceno` ='".$raceno."' and `racecard`.`horseno` ='".$horseno."' ; ";
					// mysqli_query($mysqli, $sql_update);
					$sql_case .=" when `RaceCard`.`horseno` ='".$horseno."' 
									then $pla ";
					$resultarray[$raceno][$pla] = $horseno;
					// array_push($resultarray[$raceno],$horseno);
					// print_r($resultarray);
					
				}
			}
			$sql_where = " and `RaceCard`.`racingdate` ='".$racingdate."' and `RaceCard`.`venue` ='".$venue."' and `RaceCard`.`raceno` ='".$raceno."' ;";
    		$sql_update .= "
    			UPDATE `RaceCard` 
    				SET `result` = CASE
    								$sql_case
    							END
    			WHERE 1 $sql_where ;";  
		}

    
    // 		echo $sql_update."<br>";
    		mysqli_query($mysqli, $sql_update);
		
// 		}

	}

// UPDATE table_to_update 
// SET 
//     cod_user = CASE
//         WHEN user_rol = 'student'   THEN '622057'
//         WHEN user_rol = 'assistant' THEN '2913659'
//         WHEN user_rol = 'admin'     THEN '6160230'
//     END,
//     date = '12082014'
// WHERE user_rol IN ('student','assistant','admin')
// AND cod_office = '17389551';

	if($sql_case){
// 		$sql_update = "
// 			UPDATE `RaceCard` 
// 				SET `result` = CASE
// 								$sql_case
// 							END
// 			WHERE 1 $sql_where ";  

// 		echo $sql_update."<br>";
// 		mysqli_query($mysqli, $sql_update);
// 		$error_message = mysqli_error($mysqli);
// 		echo $error_message;

		$logs = "ai-update_result2db";
		$insert = "	INSERT INTO logs
		VALUES('','$logs',now())";
		// echo $insert;
		mysqli_query($mysqli, $insert);
		// echo ".";		
	}	
}
// echo "dddddddddddd".$venue;
// print_r($resultx);
//-----------------------------------------------------------------------------------------------------------------------------------------------


// print_r ($obj);
// $arr ='
// {"OUT":"054941@@@WIN;1=2.9=1;2=13=0;3=17=0;4=86=0;5=123=0;6=17=0;7=11=0;8=57=0;9=39=0;10=3.4=0;11=78=0;12=18=0;13=9.5=0;14=15=0#PLA;1=1.5=1;2=3.4=0;3=4.4=0;4=12=0;5=19=0;6=3.9=0;7=3.3=0;8=12=0;9=8.2=0;10=1.8=0;11=13=0;12=3.4=0;13=2.3=0;14=4.0=0@@@WIN;1=13=0;2=7.8=0;3=6.4=0;4=26=0;5=12=0;6=8.7=0;7=4.9=0;8=31=0;9=10=0;10=4.8=1;11=24=0;12=21=0#PLA;1=4.7=0;2=2.2=0;3=2.4=0;4=5.9=0;5=3.1=0;6=4.3=0;7=2.0=1;8=6.6=0;9=2.5=0;10=2.4=0;11=7.0=0;12=4.0=0@@@WIN;1=5.1=0;2=13=0;3=5.5=0;4=23=0;5=6.4=0;6=30=0;7=101=0;8=47=0;9=19=0;10=34=0;11=4.2=1;12=14=0;13=9.7=0;14=108=0#PLA;1=2.0=0;2=6.0=0;3=1.7=1;4=5.9=0;5=2.3=0;6=7.2=0;7=14=0;8=7.3=0;9=5.3=0;10=7.8=0;11=2.3=0;12=3.4=0;13=3.0=0;14=14=0@@@WIN;1=3.1=0;2=27=0;3=17=0;4=3.6=0;5=2.5=1;6=13=0;7=20=0#PLA;1=1.2=1;2=4.1=0;3=2.0=0;4=1.4=0;5=1.3=0;6=3.7=0;7=2.9=0@@@WIN;1=106=0;2=17=0;3=13=0;4=11=0;5=4.2=0;6=34=0;7=11=0;8=96=0;9=13=0;10=2.6=1;11=9.2=0;12=17=0#PLA;1=15=0;2=4.7=0;3=4.3=0;4=4.6=0;5=1.7=0;6=5.7=0;7=5.3=0;8=20=0;9=3.7=0;10=1.0=1;11=2.6=0;12=4.6=0@@@WIN;1=10=0;2=49=0;3=1.8=1;4=10=0;5=81=0;6=86=0;7=16=0;8=15=0;9=11=0;10=11=0;11=213=0;12=11=0;13=67=0;14=86=0#PLA;1=2.9=0;2=12=0;3=1.0=1;4=4.0=0;5=12=0;6=13=0;7=3.9=0;8=3.4=0;9=5.0=0;10=3.1=0;11=28=0;12=2.0=0;13=9.2=0;14=15=0@@@WIN;1=23=0;2=7.2=0;3=37=0;4=5.8=0;5=20=0;6=2.2=1;7=13=0;8=17=0;9=25=0;10=16=0;11=26=0;12=14=0#PLA;1=4.7=0;2=2.4=0;3=8.2=0;4=2.0=0;5=5.8=0;6=1.1=1;7=3.5=0;8=10=0;9=6.0=0;10=3.4=0;11=4.3=0;12=3.3=0@@@WIN;1=91=0;2=20=0;3=6.4=0;4=6.6=0;5=7.7=0;6=5.4=1;7=6.3=0;8=15=0;9=134=0;10=131=0;11=71=0;12=32=0;13=15=0;14=5.4=0#PLA;1=19=0;2=5.3=0;3=2.3=0;4=2.6=0;5=2.0=0;6=1.8=1;7=2.0=0;8=5.3=0;9=27=0;10=26=0;11=8.6=0;12=11=0;13=3.0=0;14=3.7=0@@@WIN;1=3.2=1;2=74=0;3=5.4=0;4=15=0;5=20=0;6=35=0;7=7.5=0;8=15=0;9=10=0;10=50=0;11=6.3=0;12=11=0#PLA;1=2.1=1;2=6.6=0;3=2.9=0;4=3.7=0;5=5.6=0;6=6.6=0;7=2.1=0;8=3.7=0;9=2.2=0;10=8.1=0;11=2.4=0;12=2.9=0@@@WIN;1=21=0;2=4.5=0;3=3.4=1;4=13=0;5=24=0;6=70=0;7=23=0;8=42=0;9=12=0;10=11=0;11=13=0;12=5.8=0;13=33=0;14=56=0#PLA;1=5.0=0;2=2.1=0;3=1.9=1;4=4.2=0;5=5.0=0;6=12=0;7=5.5=0;8=8.0=0;9=4.3=0;10=3.1=0;11=3.1=0;12=2.0=0;13=6.4=0;14=9.3=0@@@WIN;1=3.5=0;2=22=0;3=11=0;4=36=0;5=9.1=0;6=7.8=0;7=61=0;8=11=0;9=16=0;10=112=0;11=19=0;12=3.3=1#PLA;1=3.7=0;2=5.6=0;3=10=0;4=8.7=0;5=3.7=0;6=2.6=0;7=13=0;8=3.3=0;9=3.9=0;10=19=0;11=4.6=0;12=1.0=1"}
// ';
// $arr_pre ='
// {"OUT":"054941@@@WIN;1=2.8=1;2=13=0;3=17=0;4=86=0;5=123=0;6=17=0;7=11=0;8=57=0;9=39=0;10=3.4=0;11=78=0;12=18=0;13=9.5=0;14=15=0#PLA;1=1.5=1;2=3.4=0;3=4.4=0;4=12=0;5=19=0;6=3.9=0;7=3.3=0;8=12=0;9=8.2=0;10=1.8=0;11=13=0;12=3.4=0;13=2.3=0;14=4.0=0@@@WIN;1=13=0;2=7.8=0;3=6.4=0;4=26=0;5=12=0;6=8.7=0;7=4.9=0;8=31=0;9=10=0;10=4.8=1;11=24=0;12=21=0#PLA;1=4.7=0;2=2.2=0;3=2.4=0;4=5.9=0;5=3.1=0;6=4.3=0;7=2.0=1;8=6.6=0;9=2.5=0;10=2.4=0;11=7.0=0;12=4.0=0@@@WIN;1=5.1=0;2=13=0;3=5.5=0;4=23=0;5=6.4=0;6=30=0;7=101=0;8=47=0;9=19=0;10=34=0;11=4.2=1;12=14=0;13=9.7=0;14=108=0#PLA;1=2.0=0;2=6.0=0;3=1.7=1;4=5.9=0;5=2.3=0;6=7.2=0;7=14=0;8=7.3=0;9=5.3=0;10=7.8=0;11=2.3=0;12=3.4=0;13=3.0=0;14=14=0@@@WIN;1=3.1=0;2=27=0;3=17=0;4=3.6=0;5=2.5=1;6=13=0;7=20=0#PLA;1=1.2=1;2=4.1=0;3=2.0=0;4=1.4=0;5=1.3=0;6=3.7=0;7=2.9=0@@@WIN;1=106=0;2=17=0;3=13=0;4=11=0;5=4.2=0;6=34=0;7=11=0;8=96=0;9=13=0;10=2.6=1;11=9.2=0;12=17=0#PLA;1=15=0;2=4.7=0;3=4.3=0;4=4.6=0;5=1.7=0;6=5.7=0;7=5.3=0;8=20=0;9=3.7=0;10=1.0=1;11=2.6=0;12=4.6=0@@@WIN;1=10=0;2=49=0;3=1.8=1;4=10=0;5=81=0;6=86=0;7=16=0;8=15=0;9=11=0;10=11=0;11=213=0;12=11=0;13=67=0;14=86=0#PLA;1=2.9=0;2=12=0;3=1.0=1;4=4.0=0;5=12=0;6=13=0;7=3.9=0;8=3.4=0;9=5.0=0;10=3.1=0;11=28=0;12=2.0=0;13=9.2=0;14=15=0@@@WIN;1=23=0;2=7.2=0;3=37=0;4=5.8=0;5=20=0;6=2.2=1;7=13=0;8=17=0;9=25=0;10=16=0;11=26=0;12=14=0#PLA;1=4.7=0;2=2.4=0;3=8.2=0;4=2.0=0;5=5.8=0;6=1.1=1;7=3.5=0;8=10=0;9=6.0=0;10=3.4=0;11=4.3=0;12=3.3=0@@@WIN;1=91=0;2=20=0;3=6.4=0;4=6.6=0;5=7.7=0;6=5.4=1;7=6.3=0;8=15=0;9=134=0;10=131=0;11=71=0;12=32=0;13=15=0;14=5.4=0#PLA;1=19=0;2=5.3=0;3=2.3=0;4=2.6=0;5=2.0=0;6=1.8=1;7=2.0=0;8=5.3=0;9=27=0;10=26=0;11=8.6=0;12=11=0;13=3.0=0;14=3.7=0@@@WIN;1=3.2=1;2=74=0;3=5.4=0;4=15=0;5=20=0;6=35=0;7=7.5=0;8=15=0;9=10=0;10=50=0;11=6.3=0;12=11=0#PLA;1=2.1=1;2=6.6=0;3=2.9=0;4=3.7=0;5=5.6=0;6=6.6=0;7=2.1=0;8=3.7=0;9=2.2=0;10=8.1=0;11=2.4=0;12=2.9=0@@@WIN;1=21=0;2=4.5=0;3=3.4=1;4=13=0;5=24=0;6=70=0;7=23=0;8=42=0;9=12=0;10=11=0;11=13=0;12=5.8=0;13=33=0;14=56=0#PLA;1=5.0=0;2=2.1=0;3=1.9=1;4=4.2=0;5=5.0=0;6=12=0;7=5.5=0;8=8.0=0;9=4.3=0;10=3.1=0;11=3.1=0;12=2.0=0;13=6.4=0;14=9.3=0@@@WIN;1=3.5=0;2=22=0;3=11=0;4=36=0;5=9.1=0;6=7.8=0;7=61=0;8=11=0;9=16=0;10=112=0;11=19=0;12=3.3=1#PLA;1=3.7=0;2=5.6=0;3=10=0;4=8.7=0;5=3.7=0;6=2.6=0;7=13=0;8=3.3=0;9=3.9=0;10=19=0;11=4.6=0;12=1.0=1"}
// ';
//2022-04-03
// $obj_pre = '{"OUT":"160638@@@WIN;1=16=0;2=2.9=1;3=16=0;4=53=0;5=80=0;6=4.1=2;7=9.8=0;8=9.7=0;9=79=0;10=6.1=2;11=25=0;12=150=0;13=172=0;14=22=0#PLA;1=4.1=0;2=1.3=1;3=4.1=0;4=11=0;5=13=0;6=1.6=2;7=2.9=0;8=2.7=0;9=15=0;10=2.2=0;11=6.5=0;12=32=0;13=31=0;14=5.7=0@@@WIN;1=3.0=1;2=21=0;3=61=0;4=8.5=0;5=8.6=0;6=5.0=0;7=11=0;8=12=2;9=19=0;10=35=2;11=75=0;12=82=2;13=37=3;14=11=2#PLA;1=1.5=1;2=5.3=0;3=12=0;4=2.6=0;5=2.4=0;6=1.8=0;7=3.1=0;8=4.3=0;9=4.5=0;10=8.7=0;11=15=0;12=18=0;13=8.4=2;14=4.1=2@@@WIN;1=4.8=0;2=7.0=0;3=3.0=1;4=134=0;5=4.3=0;6=62=0;7=6.0=0;8=18=0;9=17=0#PLA;1=1.4=0;2=2.7=0;3=1.3=1;4=16=0;5=1.8=0;6=9.9=0;7=1.7=0;8=4.1=0;9=3.9=0@@@WIN;1=25=0;2=61=0;3=2.5=1;4=5.4=0;5=55=2;6=4.2=2;7=8.4=2;8=28=0;9=7.5=0;10=32=2#PLA;1=4.9=0;2=10=0;3=1.3=1;4=1.8=0;5=10=0;6=1.5=2;7=2.2=0;8=5.7=0;9=2.0=0;10=6.6=0@@@WIN;1=12=0;2=7.7=0;3=3.2=0;4=16=2;5=136=0;6=20=0;7=2.3=1;8=198=0;9=50=0;10=241=0;11=20=0;12=37=0;13=76=0;14=32=0#PLA;1=2.8=0;2=2.0=0;3=1.5=0;4=3.8=0;5=19=0;6=4.4=0;7=1.2=1;8=27=0;9=10=0;10=31=0;11=4.2=0;12=6.6=0;13=14=0;14=6.3=0@@@WIN;1=13=0;2=9.7=0;3=3.7=0;4=108=0;5=28=0;6=54=0;7=10=0;8=2.5=1;9=75=0;10=6.4=2;11=60=0;12=40=2#PLA;1=3.2=0;2=2.5=0;3=1.6=0;4=19=0;5=4.8=0;6=9.8=0;7=2.6=0;8=1.1=1;9=14=0;10=2.2=0;11=11=0;12=8.9=0@@@WIN;1=4.4=0;2=7.8=0;3=5.4=2;4=9.3=0;5=16=0;6=18=0;7=10=0;8=2.9=1;9=45=0#PLA;1=1.8=0;2=2.3=0;3=1.8=0;4=2.2=0;5=3.7=0;6=3.6=0;7=2.9=0;8=1.5=1;9=7.1=0@@@WIN;1=11=0;2=4.2=0;3=4.0=1;4=33=0;5=42=0;6=14=0;7=23=0;8=7.4=0;9=24=0;10=12=0;11=14=0;12=99=0;13=8.8=0;14=34=0#PLA;1=4.1=0;2=2.2=0;3=1.7=1;4=6.8=0;5=8.4=0;6=4.1=0;7=5.1=0;8=3.0=0;9=5.3=0;10=3.4=0;11=4.2=0;12=15=0;13=2.7=0;14=5.9=0@@@WIN;1=1.7=1;2=9.4=0;3=7.2=0;4=11=0;5=21=0;6=32=0;7=45=0;8=36=0;9=36=0;10=55=0;11=12=0;12=19=0#PLA;1=1.2=1;2=3.0=0;3=2.3=0;4=2.7=0;5=2.9=0;6=5.8=0;7=7.4=0;8=4.7=0;9=5.9=0;10=8.8=0;11=3.5=0;12=3.9=0@@@WIN;1=48=0;2=3.9=0;3=36=0;4=3.5=1;5=7.4=0;6=15=0;7=4.4=0;8=47=0;9=11=0;10=51=0;11=12=0#PLA;1=7.4=0;2=1.4=1;3=5.6=0;4=2.3=0;5=2.3=0;6=3.1=0;7=1.9=0;8=8.2=0;9=3.6=0;10=6.3=0;11=2.6=0"}' ;
// $obj = '{"OUT":"050722@@@WIN;1=17=0;2=2.6=1;3=10=0;4=29=0;5=38=0;6=6.8=0;7=8.8=0;8=7.9=0;9=59=0;10=13=0;11=18=0;12=88=0;13=67=0;14=14=0#PLA;1=4.2=0;2=1.3=1;3=3.4=0;4=6.9=0;5=5.3=0;6=2.4=0;7=2.8=0;8=3.5=0;9=11=0;10=3.4=0;11=6.6=0;12=14=0;13=13=0;14=3.5=0@@@WIN;1=5.1=0;2=14=0;3=45=0;4=9.2=0;5=6.6=0;6=5.0=1;7=6.4=0;8=15=0;9=12=0;10=21=0;11=44=0;12=57=0;13=58=0;14=16=0#PLA;1=3.0=0;2=3.3=0;3=8.4=0;4=2.6=0;5=2.2=0;6=1.9=1;7=3.0=0;8=4.7=0;9=3.9=0;10=5.3=0;11=6.3=0;12=11=0;13=11=0;14=4.9=0@@@WIN;1=4.3=0;2=7.5=0;3=3.4=1;4=26=0;5=5.0=0;6=26=0;7=7.5=0;8=13=0;9=14=0#PLA;1=1.5=1;2=2.2=0;3=1.7=0;4=5.1=0;5=1.9=0;6=4.2=0;7=2.5=0;8=3.4=0;9=3.0=0@@@WIN;1=11=0;2=21=0;3=2.6=1;4=6.5=0;5=23=0;6=4.8=0;7=17=0;8=22=0;9=5.7=0;10=35=0#PLA;1=3.0=0;2=4.7=0;3=1.4=1;4=2.3=0;5=4.8=0;6=1.6=0;7=3.3=0;8=4.8=0;9=2.0=0;10=6.6=0@@@WIN;1=9.4=0;2=4.9=0;3=3.8=1;4=16=0;5=49=0;6=11=0;7=4.5=0;8=79=0;9=26=0;10=78=0;11=16=0;12=23=0;13=52=0;14=14=0#PLA;1=3.4=0;2=2.4=0;3=1.9=1;4=3.7=0;5=7.0=0;6=3.2=0;7=2.1=0;8=8.7=0;9=6.8=0;10=12=0;11=3.5=0;12=4.5=0;13=7.5=0;14=4.3=0@@@WIN;1=10=0;2=6.2=0;3=5.0=0;4=50=0;5=22=0;6=23=0;7=7.4=0;8=3.5=1;9=55=0;10=6.8=0;11=26=0;12=41=0#PLA;1=3.4=0;2=2.9=0;3=2.4=0;4=8.1=0;5=4.4=0;6=4.0=0;7=2.2=0;8=1.8=1;9=8.7=0;10=2.0=0;11=4.3=0;12=7.2=0@@@WIN;1=4.6=0;2=9.5=0;3=7.9=0;4=6.9=0;5=15=0;6=11=0;7=11=0;8=2.8=1;9=35=0#PLA;1=2.2=0;2=2.3=0;3=2.3=0;4=1.8=1;5=3.3=0;6=2.1=0;7=2.9=0;8=2.2=0;9=4.5=0@@@WIN;1=9.3=0;2=5.1=0;3=4.1=1;4=27=0;5=28=0;6=16=0;7=24=0;8=8.4=0;9=17=0;10=14=0;11=11=0;12=69=0;13=9.0=0;14=29=0#PLA;1=4.8=0;2=3.0=0;3=1.0=1;4=9.0=0;5=10=0;6=7.3=0;7=4.4=0;8=4.3=0;9=5.5=0;10=6.3=0;11=5.5=0;12=17=0;13=3.7=0;14=7.8=0@@@WIN;1=2.0=1;2=11=0;3=6.2=0;4=15=0;5=16=0;6=23=0;7=35=0;8=19=0;9=30=0;10=40=0;11=9.4=0;12=17=0#PLA;1=2.5=0;2=5.1=0;3=2.3=0;4=4.1=0;5=3.3=0;6=6.0=0;7=6.9=0;8=1.6=1;9=1.9=0;10=8.2=0;11=3.9=0;12=3.8=0@@@WIN;1=37=0;2=3.7=1;3=27=0;4=4.5=0;5=7.5=0;6=17=0;7=4.5=0;8=32=0;9=10=0;10=42=0;11=10=0#PLA;1=6.3=0;2=1.5=0;3=5.0=0;4=2.6=0;5=2.6=0;6=3.9=0;7=1.2=1;8=6.8=0;9=4.7=0;10=7.5=0;11=3.1=0"}' ;

// 2022-04-06
// {"OUT":"175936@@@WIN;1=6.5=0;2=3.5=0;3=32=0;4=2.2=1;5=9.7=0;6=6.1=0;7=25=0#PLA;1=1.7=0;2=1.4=0;3=4.5=0;4=1.2=1;5=1.9=0;6=1.7=0;7=4.5=0@@@WIN;1=45=0;2=3.3=1;3=4.4=0;4=11=0;5=13=0;6=6.3=0;7=35=0;8=5.0=0;9=28=0;10=11=0#PLA;1=6.7=0;2=1.8=0;3=1.6=1;4=2.8=0;5=3.3=0;6=2.3=0;7=5.9=0;8=1.8=0;9=4.5=0;10=3.0=0@@@WIN;1=32=0;2=17=0;3=5.1=1;4=33=0;5=6.6=0;6=11=0;7=5.4=0;8=13=0;9=9.8=0;10=13=0;11=5.6=0;12=19=0#PLA;1=7.0=0;2=5.3=0;3=2.9=0;4=5.9=0;5=2.4=0;6=3.1=0;7=2.6=0;8=3.6=0;9=2.7=0;10=3.7=0;11=1.9=1;12=4.0=0@@@WIN;1=6.9=0;2=2.8=1;3=3.1=0;4=14=0;5=6.4=0;6=12=0;7=11=0#PLA;1=2.0=0;2=1.6=0;3=1.3=1;4=2.8=0;5=2.1=0;6=2.3=0;7=2.0=0@@@WIN;1=43=0;2=26=0;3=20=0;4=39=0;5=4.7=0;6=18=0;7=7.6=0;8=7.1=0;9=19=0;10=9.2=0;11=3.8=1;12=8.7=0#PLA;1=7.4=0;2=4.7=0;3=4.3=0;4=6.2=0;5=2.5=0;6=6.1=0;7=2.7=0;8=2.0=1;9=4.8=0;10=2.6=0;11=2.2=0;12=2.3=0@@@WIN;1=14=0;2=7.2=0;3=11=0;4=15=0;5=6.0=0;6=7.7=0;7=4.5=1;8=5.6=0;9=6.1=0#PLA;1=3.7=0;2=2.5=0;3=2.6=0;4=4.1=0;5=2.2=0;6=2.8=0;7=1.6=1;8=2.6=0;9=1.7=0@@@WIN;1=16=0;2=3.3=1;3=12=0;4=47=0;5=45=0;6=6.7=0;7=46=0;8=9.7=0;9=14=0;10=8.0=0;11=4.3=0;12=38=0#PLA;1=4.3=0;2=1.7=0;3=2.9=0;4=8.2=0;5=8.5=0;6=2.9=0;7=10=0;8=3.0=0;9=3.4=0;10=3.0=0;11=1.4=1;12=6.6=0@@@WIN;1=7.2=0;2=2.7=1;3=27=0;4=18=0;5=58=0;6=36=0;7=12=0;8=22=0;9=5.4=0;10=6.5=0;11=23=0;12=18=0#PLA;1=2.6=0;2=1.7=1;3=5.9=0;4=4.5=0;5=8.4=0;6=5.9=0;7=2.8=0;8=4.2=0;9=2.6=0;10=2.2=0;11=4.2=0;12=3.3=0@@@WIN;1=24=0;2=5.7=0;3=13=0;4=2.7=1;5=25=0;6=6.5=0;7=24=0;8=29=0;9=14=0;10=10=0;11=12=0;12=21=0#PLA;1=6.6=0;2=2.3=0;3=3.5=0;4=1.9=1;5=7.1=0;6=2.1=0;7=5.4=0;8=7.6=0;9=3.7=0;10=2.6=0;11=2.5=0;12=3.7=0"} {"OUT":"045210@@@WIN;1=5.5=0;2=3.7=0;3=21=0;4=2.3=1;5=11=0;6=6.9=0;7=18=0#PLA;1=1.5=0;2=1.0=1;3=4.5=0;4=1.5=0;5=2.8=0;6=1.9=0;7=4.5=0@@@WIN;1=35=0;2=2.8=1;3=5.1=0;4=10=0;5=15=0;6=7.2=0;7=29=0;8=5.0=0;9=26=0;10=14=0#PLA;1=5.9=0;2=1.5=1;3=1.7=0;4=2.8=0;5=4.0=0;6=2.5=0;7=7.1=0;8=1.9=0;9=4.2=0;10=2.8=0@@@WIN;1=19=0;2=16=0;3=5.5=0;4=26=0;5=6.7=0;6=10=0;7=4.6=1;8=13=0;9=11=0;10=14=0;11=6.8=0;12=19=0#PLA;1=7.4=0;2=5.5=0;3=3.0=0;4=4.6=0;5=2.8=0;6=2.6=0;7=1.6=1;8=4.0=0;9=3.7=0;10=3.9=0;11=2.8=0;12=3.3=0@@@WIN;1=7.1=0;2=3.1=0;3=2.7=1;4=13=0;5=6.1=0;6=13=0;7=13=0#PLA;1=1.3=0;2=1.7=0;3=1.3=1;4=2.7=0;5=2.4=0;6=2.3=0;7=2.6=0@@@WIN;1=38=0;2=21=0;3=25=0;4=39=0;5=4.8=0;6=13=0;7=7.7=0;8=8.0=0;9=16=0;10=9.2=0;11=3.8=1;12=8.9=0#PLA;1=6.8=0;2=4.3=0;3=5.3=0;4=6.8=0;5=2.1=0;6=8.0=0;7=3.1=0;8=2.3=0;9=5.2=0;10=2.6=0;11=1.9=1;12=2.1=0@@@WIN;1=14=0;2=6.5=0;3=10=0;4=12=0;5=5.3=0;6=9.8=0;7=4.7=1;8=5.9=0;9=7.1=0#PLA;1=4.4=0;2=2.5=0;3=1.8=0;4=3.8=0;5=2.0=0;6=3.5=0;7=1.6=1;8=2.6=0;9=2.4=0@@@WIN;1=14=0;2=4.1=1;3=13=0;4=30=0;5=38=0;6=7.3=0;7=43=0;8=11=0;9=15=0;10=5.6=0;11=4.2=0;12=24=0#PLA;1=3.7=0;2=1.7=1;3=4.2=0;4=5.1=0;5=6.2=0;6=3.1=0;7=10=0;8=3.6=0;9=3.5=0;10=2.2=0;11=1.9=0;12=4.0=0@@@WIN;1=8.7=0;2=2.6=1;3=22=0;4=15=0;5=36=0;6=34=0;7=13=0;8=16=0;9=5.3=0;10=8.7=0;11=22=0;12=13=0#PLA;1=4.2=0;2=1.2=1;3=5.8=0;4=4.7=0;5=6.6=0;6=5.6=0;7=3.8=0;8=3.2=0;9=3.4=0;10=3.9=0;11=5.0=0;12=1.8=0@@@WIN;1=24=0;2=7.4=0;3=18=0;4=2.0=1;5=28=0;6=9.0=0;7=31=0;8=28=0;9=13=0;10=11=0;11=14=0;12=24=0#PLA;1=6.0=0;2=3.1=0;3=4.5=0;4=1.5=1;5=5.8=0;6=2.4=0;7=4.9=0;8=6.0=0;9=3.3=0;10=2.7=0;11=2.6=0;12=3.8=0"}
// 2022-04-16
// {"OUT":"121857@@@WIN;1=25=0;2=7.3=0;3=6.0=0;4=6.0=0;5=11=0;6=16=0;7=19=0;8=13=0;9=17=0;10=4.7=1;11=36=0;12=20=0;13=28=0;14=20=0#PLA;1=7.1=0;2=2.6=0;3=2.6=0;4=2.4=0;5=3.2=0;6=4.4=0;7=5.2=0;8=3.8=0;9=5.1=0;10=2.2=1;11=6.7=0;12=5.8=0;13=6.2=0;14=4.4=0@@@WIN;1=9.1=0;2=15=0;3=6.1=0;4=39=0;5=38=0;6=10=0;7=4.0=1;8=10=0;9=38=0;10=19=0;11=4.2=0;12=14=0#PLA;1=3.1=0;2=4.0=0;3=2.3=0;4=7.7=0;5=6.8=0;6=3.3=0;7=1.5=1;8=2.5=0;9=6.6=0;10=4.6=0;11=2.2=0;12=4.3=0@@@WIN;1=4.2=1;2=5.2=0;3=9.2=0;4=10=0;5=72=0;6=18=0;7=14=0;8=16=0;9=17=0;10=66=0;11=59=0;12=10=0;13=5.0=0#PLA;1=2.2=0;2=2.2=0;3=3.0=0;4=2.9=0;5=12=0;6=4.1=0;7=3.8=0;8=4.2=0;9=3.8=0;10=11=0;11=11=0;12=2.7=0;13=2.2=1@@@WIN;1=10=0;2=8.5=0;3=3.1=1;4=18=0;5=4.9=0;6=17=0;7=47=0;8=8.4=0;9=47=0;10=10=0;11=11=0;12=50=0#PLA;1=3.4=0;2=2.6=0;3=1.8=1;4=4.5=0;5=2.1=0;6=4.5=0;7=7.6=0;8=2.3=0;9=7.4=0;10=3.0=0;11=2.9=0;12=8.5=0@@@WIN;1=18=0;2=6.7=0;3=26=0;4=20=0;5=15=0;6=42=0;7=2.4=1;8=54=0;9=20=0;10=18=0;11=11=0;12=20=0;13=15=0;14=9.8=0#PLA;1=4.7=0;2=2.3=0;3=5.0=0;4=6.2=0;5=3.8=0;6=8.8=0;7=1.7=1;8=9.0=0;9=4.1=0;10=4.5=0;11=3.8=0;12=5.2=0;13=4.4=0;14=2.4=0@@@WIN;1=4.8=0;2=12=0;3=10=0;4=3.0=1;5=8.2=0;6=15=0;7=10=0;8=15=0;9=17=0;10=16=0;11=31=0#PLA;1=2.8=0;2=3.3=0;3=2.9=0;4=1.2=1;5=2.8=0;6=4.5=0;7=2.9=0;8=3.9=0;9=4.3=0;10=3.7=0;11=5.9=0@@@WIN;1=1.6=1;2=4.1=0;3=29=0;4=42=0;5=27=0;6=14=0;7=27=0;8=SCR=0;9=24=0;10=12=0;11=25=0#PLA;1=1.1=1;2=1.6=0;3=5.9=0;4=4.9=0;5=4.2=0;6=2.5=0;7=3.8=0;8=SCR=0;9=3.9=0;10=2.9=0;11=4.0=0@@@WIN;1=13=0;2=18=0;3=31=0;4=7.5=0;5=12=0;6=10=0;7=16=0;8=11=0;9=4.0=1;10=18=0;11=11=0;12=6.1=0;13=70=0;14=37=0#PLA;1=3.7=0;2=7.1=0;3=7.9=0;4=2.8=0;5=4.0=0;6=2.7=0;7=4.5=0;8=3.3=0;9=2.6=0;10=3.2=0;11=4.2=0;12=2.2=1;13=10=0;14=6.4=0@@@WIN;1=11=0;2=10=0;3=43=0;4=7.5=0;5=49=0;6=29=0;7=2.0=1;8=14=0;9=14=0;10=24=0;11=11=0;12=18=0#PLA;1=4.5=0;2=2.2=0;3=6.3=0;4=2.3=0;5=7.2=0;6=6.6=0;7=1.8=1;8=3.1=0;9=3.0=0;10=4.8=0;11=2.3=0;12=3.8=0@@@WIN;1=8.7=0;2=11=0;3=2.8=1;4=6.0=0;5=17=0;6=24=0;7=14=0;8=14=0;9=22=0;10=36=0;11=32=0;12=17=0;13=33=0;14=17=0#PLA;1=2.1=0;2=2.9=0;3=1.9=1;4=2.7=0;5=5.1=0;6=8.4=0;7=2.9=0;8=5.6=0;9=6.9=0;10=7.3=0;11=5.9=0;12=4.3=0;13=7.4=0;14=3.4=0"} {"OUT":"061549@@@WIN;1=19=0;2=6.3=0;3=6.2=0;4=7.2=0;5=11=0;6=18=0;7=16=0;8=10=0;9=20=0;10=5.5=1;11=35=0;12=16=0;13=30=0;14=18=0#PLA;1=6.2=0;2=2.6=0;3=2.8=0;4=2.6=1;5=3.4=0;6=4.2=0;7=4.9=0;8=3.0=0;9=5.2=0;10=2.9=0;11=7.1=0;12=4.6=0;13=6.8=0;14=3.7=0@@@WIN;1=10=0;2=13=0;3=6.2=0;4=35=0;5=38=0;6=9.1=0;7=4.5=0;8=10=0;9=37=0;10=18=0;11=4.4=1;12=11=0#PLA;1=3.8=0;2=3.9=0;3=2.2=0;4=7.9=0;5=7.3=0;6=2.8=0;7=1.9=0;8=2.6=0;9=7.0=0;10=5.0=0;11=1.9=1;12=3.5=0@@@WIN;1=4.0=1;2=5.9=0;3=10=0;4=11=0;5=67=0;6=20=0;7=12=0;8=15=0;9=21=0;10=55=0;11=38=0;12=10=0;13=4.7=0#PLA;1=1.5=1;2=2.7=0;3=2.9=0;4=2.9=0;5=10=0;6=4.6=0;7=4.9=0;8=3.8=0;9=4.7=0;10=9.5=0;11=10=0;12=2.8=0;13=2.5=0@@@WIN;1=10=0;2=8.9=0;3=3.1=1;4=17=0;5=4.7=0;6=23=0;7=33=0;8=10=0;9=35=0;10=8.6=0;11=11=0;12=41=0#PLA;1=3.6=0;2=3.3=0;3=1.7=0;4=4.6=0;5=1.6=1;6=5.2=0;7=6.4=0;8=2.9=0;9=5.4=0;10=2.9=0;11=3.4=0;12=8.4=0@@@WIN;1=17=0;2=7.7=0;3=27=0;4=19=0;5=14=0;6=41=0;7=2.3=1;8=48=0;9=27=0;10=18=0;11=12=0;12=14=0;13=12=0;14=13=0#PLA;1=5.2=0;2=2.1=0;3=5.3=0;4=6.9=0;5=3.5=0;6=7.6=0;7=1.8=1;8=8.0=0;9=4.5=0;10=4.2=0;11=3.7=0;12=3.5=0;13=3.5=0;14=3.8=0@@@WIN;1=5.5=0;2=10=0;3=9.8=0;4=3.4=1;5=8.4=0;6=14=0;7=9.3=0;8=13=0;9=15=0;10=13=0;11=25=0#PLA;1=3.6=0;2=2.8=0;3=3.1=0;4=1.4=1;5=3.1=0;6=4.7=0;7=2.4=0;8=4.0=0;9=3.9=0;10=2.8=0;11=5.4=0@@@WIN;1=1.8=1;2=4.3=0;3=21=0;4=36=0;5=25=0;6=9.9=0;7=30=0;8=SCR=0;9=26=0;10=12=0;11=19=0#PLA;1=1.0=1;2=1.7=0;3=5.7=0;4=4.3=0;5=5.2=0;6=2.6=0;7=5.0=0;8=SCR=0;9=4.6=0;10=2.7=0;11=3.4=0@@@WIN;1=13=0;2=13=0;3=25=0;4=6.8=0;5=12=0;6=12=0;7=17=0;8=12=0;9=4.8=1;10=12=0;11=11=0;12=6.5=0;13=56=0;14=28=0#PLA;1=4.1=0;2=6.6=0;3=6.3=0;4=2.5=0;5=3.9=0;6=3.1=0;7=6.3=0;8=4.5=0;9=3.1=0;10=1.9=1;11=4.2=0;12=2.7=0;13=9.4=0;14=5.3=0@@@WIN;1=7.9=0;2=12=0;3=37=0;4=9.3=0;5=46=0;6=17=0;7=2.1=1;8=12=0;9=12=0;10=25=0;11=16=0;12=17=0#PLA;1=4.8=0;2=1.8=1;3=5.3=0;4=2.6=0;5=6.1=0;6=6.3=0;7=2.2=0;8=2.8=0;9=3.4=0;10=4.0=0;11=2.9=0;12=3.2=0@@@WIN;1=8.9=0;2=13=0;3=3.0=1;4=5.5=0;5=24=0;6=13=0;7=15=0;8=13=0;9=18=0;10=35=0;11=31=0;12=17=0;13=28=0;14=17=0#PLA;1=2.5=0;2=3.7=0;3=2.1=1;4=2.8=0;5=6.9=0;6=6.5=0;7=2.1=0;8=4.3=0;9=8.4=0;10=7.5=0;11=3.8=0;12=4.4=0;13=6.7=0;14=3.6=0"}
//2022-05-01
// {"OUT":"060351@@@WIN;1=6.0=0;2=29=0;3=18=0;4=4.3=0;5=15=0;6=14=0;7=1.6=1#PLA;1=1.4=0;2=3.9=0;3=3.2=0;4=1.2=1;5=2.4=0;6=2.5=0;7=1.3=0@@@WIN;1=11=0;2=19=0;3=2.4=1;4=50=0;5=19=0;6=55=0;7=8.0=0;8=16=0;9=37=0;10=7.5=0;11=15=0;12=19=0;13=10=0;14=58=0#PLA;1=3.5=0;2=5.4=0;3=1.7=1;4=9.2=0;5=4.8=0;6=9.8=0;7=2.7=0;8=4.7=0;9=6.9=0;10=2.1=0;11=3.3=0;12=4.1=0;13=3.3=0;14=8.0=0@@@WIN;1=3.9=0;2=1.6=1;3=9.6=0;4=8.1=0;5=10=0#PLA;1=2.1=0;2=1.2=1;3=2.9=0;4=2.7=0;5=2.3=0@@@WIN;1=8.8=0;2=10=0;3=1.5=1;4=5.0=0;5=31=0;6=38=0;7=29=0;8=31=0;9=50=0#PLA;1=1.8=0;2=2.0=0;3=1.3=1;4=1.6=0;5=4.1=0;6=4.4=0;7=3.6=0;8=3.6=0;9=4.6=0@@@WIN;1=12=0;2=3.0=1;3=8.8=0;4=8.8=0;5=9.8=0;6=13=0;7=42=0;8=8.9=0;9=73=0;10=8.3=0;11=78=0;12=80=0;13=12=0;14=53=0#PLA;1=4.0=0;2=1.5=1;3=2.7=0;4=3.7=0;5=3.7=0;6=3.6=0;7=8.2=0;8=3.4=0;9=12=0;10=2.7=0;11=12=0;12=18=0;13=2.6=0;14=6.0=0@@@WIN;1=16=0;2=3.8=1;3=6.1=0;4=7.2=0;5=6.2=0;6=30=0;7=14=0;8=7.7=0;9=57=0;10=21=0;11=16=0;12=73=0;13=30=0;14=39=0#PLA;1=4.4=0;2=1.8=1;3=2.5=0;4=2.8=0;5=2.0=0;6=7.3=0;7=3.9=0;8=2.8=0;9=9.6=0;10=5.5=0;11=4.0=0;12=12=0;13=8.1=0;14=6.4=0@@@WIN;1=5.0=0;2=30=0;3=7.6=0;4=30=0;5=6.5=0;6=14=0;7=32=0;8=34=0;9=16=0;10=4.9=1;11=13=0;12=5.8=0;13=42=0#PLA;1=2.3=0;2=5.9=0;3=3.1=0;4=5.9=0;5=2.1=0;6=3.6=0;7=7.9=0;8=7.9=0;9=7.4=0;10=2.3=0;11=3.7=0;12=1.7=1;13=7.5=0@@@WIN;1=6.3=0;2=14=0;3=22=0;4=10=0;5=4.5=1;6=27=0;7=9.4=0;8=15=0;9=11=0;10=4.8=0;11=16=0;12=15=0#PLA;1=3.6=0;2=4.0=0;3=4.5=0;4=3.0=0;5=2.2=0;6=5.7=0;7=2.8=0;8=4.4=0;9=3.3=0;10=1.6=1;11=4.2=0;12=4.4=0@@@WIN;1=5.3=0;2=4.5=1;3=17=0;4=22=0;5=9.9=0;6=11=0;7=16=0;8=65=0;9=42=0;10=11=0;11=8.7=0;12=13=0;13=12=0;14=18=0#PLA;1=2.6=1;2=3.3=0;3=4.4=0;4=6.3=0;5=3.9=0;6=2.7=0;7=4.1=0;8=7.9=0;9=7.4=0;10=3.3=0;11=2.8=0;12=3.0=0;13=3.5=0;14=5.6=0@@@WIN;1=2.2=1;2=8.0=0;3=6.6=0;4=7.4=0;5=16=0;6=23=0;7=18=0;8=15=0;9=36=0;10=32=0;11=13=0#PLA;1=1.4=1;2=2.8=0;3=1.9=0;4=2.8=0;5=2.9=0;6=4.8=0;7=5.2=0;8=3.2=0;9=6.5=0;10=5.9=0;11=3.1=0@@@WIN;1=4.9=0;2=5.9=0;3=17=0;4=38=0;5=24=0;6=23=0;7=17=0;8=45=0;9=39=0;10=31=0;11=14=0;12=2.1=1#PLA;1=2.3=0;2=2.5=0;3=3.0=0;4=4.6=0;5=3.6=0;6=3.9=0;7=3.3=0;8=6.4=0;9=7.3=0;10=4.0=0;11=2.5=0;12=2.0=1"}
// {"OUT":"172906@@@WIN;1=8.4=0;2=29=0;3=48=0;4=6.2=0;5=29=0;6=15=0;7=1.3=1#PLA;1=1.8=0;2=3.9=0;3=6.3=0;4=1.2=2;5=4.1=0;6=2.0=2;7=1.0=1@@@WIN;1=12=0;2=39=0;3=2.4=1;4=104=0;5=9.2=3;6=81=0;7=6.7=0;8=32=0;9=51=0;10=5.5=2;11=23=0;12=33=0;13=11=0;14=79=0#PLA;1=3.1=0;2=9.2=0;3=1.2=1;4=20=0;5=2.6=3;6=15=0;7=2.5=0;8=8.0=0;9=10=0;10=1.7=0;11=4.7=0;12=6.9=0;13=3.2=2;14=14=0@@@WIN;1=5.1=0;2=1.6=1;3=8.3=0;4=5.5=0;5=11=0#PLA;1=1.8=0;2=1.2=1;3=2.4=0;4=2.3=0;5=4.7=0@@@WIN;1=6.2=0;2=11=0;3=1.6=1;4=4.3=0;5=40=0;6=80=0;7=37=0;8=30=0;9=71=0#PLA;1=1.3=2;2=2.4=0;3=1.1=1;4=1.2=0;5=7.3=0;6=10=0;7=5.4=0;8=4.5=0;9=9.1=0@@@WIN;1=11=0;2=2.0=1;3=17=0;4=17=0;5=15=0;6=SCR=0;7=68=0;8=8.5=0;9=95=0;10=11=0;11=126=0;12=192=0;13=5.4=0;14=84=0#PLA;1=3.0=0;2=1.0=1;3=4.1=0;4=4.2=0;5=4.8=0;6=SCR=0;7=14=0;8=2.2=0;9=17=0;10=2.9=0;11=25=0;12=30=0;13=1.8=0;14=16=0@@@WIN;1=24=0;2=2.1=1;3=9.4=0;4=6.9=0;5=11=0;6=71=0;7=24=0;8=10=0;9=55=0;10=10=0;11=19=0;12=159=0;13=113=0;14=39=2#PLA;1=5.5=0;2=1.1=1;3=2.6=0;4=2.2=0;5=3.1=0;6=15=0;7=6.3=0;8=2.4=2;9=10=0;10=3.7=0;11=5.6=0;12=33=0;13=20=0;14=7.8=2@@@WIN;1=6.5=0;2=40=0;3=8.7=0;4=22=0;5=6.0=0;6=20=0;7=SCR=0;8=66=0;9=32=0;10=3.5=1;11=11=0;12=4.3=2;13=67=0#PLA;1=2.3=0;2=8.9=0;3=2.7=0;4=5.4=0;5=1.8=0;6=5.3=0;7=SCR=0;8=13=0;9=5.8=2;10=1.5=1;11=3.4=0;12=1.8=0;13=13=0@@@WIN;1=4.8=0;2=19=0;3=32=0;4=8.7=0;5=4.4=1;6=39=0;7=8.8=0;8=17=0;9=7.6=0;10=8.7=0;11=38=0;12=8.6=3#PLA;1=1.7=1;2=5.3=0;3=8.0=0;4=2.9=0;5=1.8=0;6=10=0;7=2.9=0;8=4.4=0;9=2.9=0;10=2.9=0;11=8.5=0;12=2.5=2@@@WIN;1=4.4=2;2=3.7=1;3=35=0;4=54=0;5=14=2;6=11=0;7=24=0;8=179=0;9=108=0;10=5.5=0;11=12=0;12=13=0;13=10=0;14=53=0#PLA;1=1.7=1;2=1.7=0;3=8.2=0;4=12=0;5=3.9=0;6=3.7=0;7=6.2=0;8=32=0;9=20=0;10=2.0=0;11=3.4=0;12=3.6=0;13=3.0=2;14=10=0@@@WIN;1=1.4=1;2=14=0;3=4.7=2;4=9.3=0;5=49=0;6=51=0;7=71=0;8=48=0;9=156=0;10=125=0;11=19=0#PLA;1=1.0=1;2=2.7=0;3=1.3=2;4=2.0=0;5=6.2=0;6=9.0=0;7=9.9=0;8=7.2=0;9=20=0;10=14=0;11=3.0=0@@@WIN;1=6.5=0;2=8.4=0;3=11=0;4=49=0;5=30=0;6=21=0;7=26=0;8=48=0;9=79=0;10=29=0;11=18=0;12=1.6=1#PLA;1=1.9=0;2=2.3=0;3=2.7=0;4=7.4=0;5=5.8=0;6=3.8=0;7=4.7=0;8=8.7=0;9=11=0;10=5.3=0;11=3.7=0;12=1.1=1"}

// print_r($obj);
// print_r($obj_pre);
// {"OUT":"073415@@@WIN#PLA@@@WIN;1=14=0;2=27=0;3=34=0;4=8.1=0;5=4.5=0;6=5.5=0;7=20=0;8=33=0;9=SCR=0;10=3.6=1;11=39=0;12=36=0;13=39=0;14=11=0;15=32=0#PLA;1=3.5=0;2=6.1=0;3=7.6=0;4=3.1=0;5=2.5=0;6=2.4=0;7=4.3=0;8=4.5=0;9=SCR=0;10=1.6=1;11=7.8=0;12=8.9=0;13=7.0=0;14=3.3=0;15=6.1=0@@@WIN#PLA;1=999=1;2=999=0;3=999=0;4=999=0;5=999=0;6=999=0;7=999=0;8=999=0;9=999=0;10=999=0;11=SCR=0;12=999=0;13=999=0;14=999=0;15=999=0;16=SCR=0;17=999=0;18=999=0;19=---=0@@@WIN;1=23=0;2=19=0;3=6.2=0;4=15=0;5=29=0;6=16=0;7=24=0;8=10=0;9=SCR=0;10=26=0;11=16=0;12=27=0;13=33=0;14=25=0;15=42=0;16=2.3=1#PLA;1=5.1=0;2=4.8=0;3=2.2=0;4=5.2=0;5=6.1=0;6=4.3=0;7=5.6=0;8=2.6=0;9=SCR=0;10=5.6=0;11=4.9=0;12=7.1=0;13=6.9=0;14=6.4=0;15=6.8=0;16=1.4=1@@@WIN;1=20=0;2=7.5=0;3=5.8=0;4=9.0=0;5=8.1=0;6=15=0;7=14=0;8=20=0;9=21=0;10=14=0;11=3.9=1;12=13=0#PLA;1=4.6=0;2=2.1=0;3=2.2=0;4=3.6=0;5=3.5=0;6=4.0=0;7=4.0=0;8=6.1=0;9=4.8=0;10=4.9=0;11=1.5=1;12=4.8=0@@@WIN;1=25=0;2=17=0;3=2.7=1;4=5.8=0;5=SCR=0;6=20=0;7=12=0;8=11=0;9=19=0;10=25=0;11=22=0;12=24=0;13=11=0;14=21=0;15=16=0#PLA;1=6.5=0;2=4.2=0;3=1.6=1;4=2.4=0;5=SCR=0;6=5.8=0;7=4.8=0;8=3.4=0;9=4.5=0;10=4.6=0;11=6.3=0;12=4.8=0;13=3.4=0;14=5.0=0;15=4.1=0@@@WIN;1=21=0;2=2.9=1;3=11=0;4=21=0;5=16=0;6=10=0;7=7.0=0;8=18=0;9=8.9=0;10=40=0;11=27=0;12=18=0;13=21=0;14=14=0#PLA;1=5.7=0;2=1.6=1;3=4.4=0;4=4.3=0;5=4.0=0;6=4.5=0;7=2.5=0;8=7.1=0;9=4.0=0;10=5.8=0;11=5.5=0;12=5.0=0;13=4.3=0;14=3.0=0@@@WIN;1=25=0;2=41=0;3=17=0;4=23=0;5=41=0;6=14=0;7=48=0;8=16=0;9=37=0;10=6.5=0;11=30=0;12=5.2=0;13=3.8=1;14=14=0;15=7.4=0#PLA;1=6.1=0;2=6.5=0;3=4.4=0;4=5.6=0;5=5.5=0;6=5.1=0;7=9.9=0;8=4.0=0;9=7.5=0;10=2.1=0;11=9.4=0;12=2.4=0;13=1.6=1;14=6.4=0;15=3.2=0@@@WIN;1=7.3=0;2=11=0;3=4.4=1;4=14=0;5=9.7=0;6=12=0;7=5.2=0;8=7.0=0;9=9.1=0;10=15=0#PLA;1=2.0=0;2=3.7=0;3=1.4=1;4=3.8=0;5=3.6=0;6=3.8=0;7=2.1=0;8=2.7=0;9=2.8=0;10=4.9=0"}
/* {"OUT":"152842@@@WIN#PLA
		@@@WIN;1=16=0;2=30=0;3=47=0;4=7.3=0;5=6.3=0;6=4.6=0;7=22=0;8=42=0;9=SCR=0;10=3.4=1;11=55=0;12=48=0;13=45=0;14=7.9=0;15=26=0
		#PLA;1=4.3=0;2=3.7=0;3=9.5=0;4=3.5=0;5=3.3=0;6=2.0=0;7=4.0=0;8=5.9=0;9=SCR=0;10=1.5=1;11=9.0=0;12=9.8=0;13=8.1=0;14=2.9=0;15=5.2=0
		@@@WIN#PLA;1=999=1;2=999=0;3=999=0;4=999=0;5=999=0;6=999=0;7=999=0;8=999=0;9=999=0;10=999=0;11=SCR=0;12=999=0;13=999=0;14=999=0;15=999=0;16=SCR=0;17=999=0;18=999=0;19=---=0@@@WIN;1=38=0;2=24=0;3=6.9=0;4=16=0;5=36=0;6=19=0;7=47=0;8=15=0;9=SCR=0;10=39=0;11=14=0;12=46=0;13=50=0;14=44=0;15=32=0;16=1.7=1#PLA;1=5.3=0;2=5.3=0;3=1.8=0;4=3.4=0;5=6.1=0;6=4.4=0;7=7.5=0;8=3.6=0;9=SCR=0;10=7.1=0;11=3.3=0;12=8.7=0;13=7.3=0;14=6.3=0;15=5.5=0;16=1.6=1@@@WIN;1=26=0;2=11=0;3=6.1=0;4=7.4=0;5=7.1=0;6=22=0;7=18=0;8=23=0;9=22=0;10=13=0;11=3.0=1;12=17=0#PLA;1=4.3=0;2=2.9=0;3=2.6=0;4=2.2=0;5=2.5=0;6=5.1=0;7=3.8=0;8=5.0=0;9=5.1=0;10=4.4=0;11=1.8=1;12=4.7=0@@@WIN;1=33=0;2=15=0;3=2.2=1;4=8.5=0;5=SCR=0;6=24=0;7=22=0;8=14=0;9=17=0;10=40=0;11=29=0;12=23=0;13=8.2=0;14=15=0;15=21=0#PLA;1=5.4=0;2=3.6=0;3=1.4=1;4=4.0=0;5=SCR=0;6=5.7=0;7=5.2=0;8=3.8=0;9=4.2=0;10=5.9=0;11=6.0=0;12=4.8=0;13=2.7=0;14=4.0=0;15=5.1=0@@@WIN;1=25=0;2=2.2=1;3=9.0=0;4=26=0;5=17=0;6=15=0;7=9.5=0;8=25=0;9=9.2=0;10=43=0;11=37=0;12=14=0;13=35=0;14=20=0#PLA;1=6.8=0;2=1.5=1;3=3.0=0;4=4.8=0;5=4.0=0;6=4.4=0;7=3.3=0;8=5.8=0;9=3.1=0;10=6.2=0;11=6.0=0;12=3.2=0;13=6.2=0;14=4.3=0@@@WIN;1=41=0;2=66=0;3=20=0;4=43=0;5=54=0;6=22=0;7=71=0;8=28=0;9=SCR=0;10=4.1=0;11=47=0;12=2.8=1;13=5.2=0;14=25=0;15=7.3=0#PLA;1=7.6=0;2=10=0;3=4.3=0;4=8.5=0;5=9.2=0;6=7.7=0;7=11=0;8=6.9=0;9=SCR=0;10=1.2=1;11=10=0;12=2.2=0;13=2.4=0;14=10=0;15=1.4=0@@@WIN;1=11=0;2=9.1=0;3=3.2=1;4=19=0;5=6.0=0;6=11=0;7=6.2=0;8=8.2=0;9=14=0;10=19=0#PLA;1=3.5=0;2=2.4=0;3=1.7=1;4=3.8=0;5=2.3=0;6=4.6=0;7=2.0=0;8=2.8=0;9=3.1=0;10=3.8=0"}

{"OUT":"063422@@@WIN;1=7.8=0;2=25=0;3=29=0;4=11=0;5=28=0;6=7.6=0;7=3.6=1;8=9.3=0;9=13=0;10=17=0;11=37=0;12=30=0;13=10=0;14=11=0#PLA;1=2.6=0;2=6.9=0;3=8.3=0;4=3.8=0;5=7.4=0;6=2.8=0;7=1.9=1;8=3.0=0;9=3.8=0;10=4.1=0;11=8.8=0;12=5.8=0;13=3.5=0;14=3.1=0@@@WIN;1=14=0;2=40=0;3=5.4=0;4=21=0;5=4.0=1;6=5.1=0;7=38=0;8=51=0;9=7.4=0;10=33=0;11=17=0;12=41=0;13=75=0;14=7.2=0#PLA;1=4.8=0;2=8.4=0;3=1.7=1;4=5.8=0;5=1.8=0;6=2.8=0;7=9.1=0;8=7.1=0;9=1.9=0;10=6.1=0;11=4.9=0;12=8.3=0;13=15=0;14=2.9=0@@@WIN;1=3.0=1;2=6.4=0;3=12=0;4=4.4=0;5=8.1=0;6=9.5=0;7=15=0;8=7.9=0#PLA;1=1.8=0;2=1.6=1;3=3.2=0;4=2.0=0;5=2.1=0;6=2.6=0;7=3.1=0;8=2.2=0@@@WIN;1=7.3=0;2=8.1=0;3=47=0;4=13=0;5=15=0;6=12=0;7=25=0;8=8.1=0;9=14=0;10=13=0;11=15=0;12=20=0;13=4.3=1;14=16=0#PLA;1=3.2=0;2=2.4=0;3=10=0;4=5.2=0;5=4.5=0;6=3.7=0;7=5.6=0;8=2.9=0;9=4.3=0;10=3.8=0;11=5.6=0;12=5.4=0;13=1.9=1;14=4.2=0@@@WIN;1=14=0;2=20=0;3=55=0;4=4.8=0;5=3.1=1;6=60=0;7=19=0;8=10=0;9=5.0=0;10=19=0;11=12=0;12=17=0#PLA;1=4.1=0;2=5.2=0;3=9.5=0;4=1.8=0;5=1.3=1;6=11=0;7=4.5=0;8=3.5=0;9=2.1=0;10=4.9=0;11=2.9=0;12=4.9=0@@@WIN;1=16=0;2=1.5=1;3=43=0;4=31=0;5=8.8=0;6=27=0;7=18=0;8=28=0;9=13=0;10=54=0;11=38=0;12=15=0;13=57=0;14=49=0#PLA;1=3.5=0;2=1.4=1;3=9.1=0;4=5.7=0;5=2.4=0;6=5.7=0;7=2.7=0;8=5.1=0;9=2.7=0;10=6.2=0;11=6.7=0;12=3.5=0;13=7.9=0;14=7.3=0@@@WIN;1=18=0;2=6.2=0;3=15=0;4=5.7=0;5=13=0;6=4.8=0;7=17=0;8=15=0;9=14=0;10=3.5=1#PLA;1=6.6=0;2=2.2=0;3=5.0=0;4=2.5=0;5=3.0=0;6=1.9=0;7=6.3=0;8=6.0=0;9=5.8=0;10=1.0=1@@@WIN;1=5.0=0;2=15=0;3=16=0;4=11=0;5=8.1=0;6=17=0;7=11=0;8=18=0;9=5.4=0;10=4.3=1;11=16=0#PLA;1=1.5=1;2=4.2=0;3=6.1=0;4=3.6=0;5=2.3=0;6=4.9=0;7=3.1=0;8=4.9=0;9=2.0=0;10=2.4=0;11=3.9=0@@@WIN;1=10=0;2=12=0;3=12=0;4=13=0;5=7.9=0;6=20=0;7=14=0;8=9.0=0;9=23=0;10=30=0;11=2.3=1;12=29=0#PLA;1=3.3=0;2=3.8=0;3=3.0=0;4=3.5=0;5=2.5=0;6=3.5=0;7=2.6=0;8=2.7=0;9=5.2=0;10=5.7=0;11=2.4=1;12=3.9=0@@@WIN;1=6.6=0;2=7.8=0;3=27=0;4=14=0;5=11=0;6=9.2=0;7=25=0;8=13=0;9=47=0;10=28=0;11=14=0;12=3.6=1;13=11=0;14=30=0#PLA;1=1.6=1;2=3.6=0;3=6.6=0;4=5.7=0;5=4.3=0;6=4.3=0;7=5.6=0;8=4.3=0;9=7.9=0;10=6.8=0;11=3.2=0;12=1.7=0;13=3.8=0;14=7.2=0"} 
{"OUT":"100029@@@WIN;1=7.3=0;2=25=0;3=30=0;4=13=0;5=29=0;6=7.9=0;7=3.5=1;8=8.9=0;9=16=0;10=15=0;11=33=0;12=32=0;13=9.8=0;14=12=0#PLA;1=2.7=0;2=6.8=0;3=8.5=0;4=4.4=0;5=8.6=0;6=2.9=0;7=1.7=1;8=2.7=0;9=4.4=0;10=4.0=0;11=6.8=0;12=6.3=0;13=3.2=0;14=3.5=0@@@WIN;1=14=0;2=42=0;3=5.6=0;4=23=0;5=3.9=1;6=5.2=0;7=36=0;8=54=0;9=6.8=0;10=32=0;11=16=0;12=43=0;13=80=0;14=7.5=0#PLA;1=4.2=0;2=8.9=0;3=1.7=1;4=5.6=0;5=1.8=0;6=2.8=0;7=8.3=0;8=7.6=0;9=2.0=0;10=6.4=0;11=4.6=0;12=8.4=0;13=16=0;14=2.9=0@@@WIN;1=2.8=1;2=6.8=0;3=12=0;4=4.7=0;5=8.0=0;6=9.3=0;7=14=0;8=7.8=0#PLA;1=1.7=0;2=1.5=1;3=3.0=0;4=2.0=0;5=2.2=0;6=2.7=0;7=3.1=0;8=2.2=0@@@WIN;1=7.0=0;2=7.3=0;3=50=0;4=14=0;5=15=0;6=13=0;7=26=0;8=8.6=0;9=15=0;10=13=0;11=16=0;12=23=0;13=4.2=1;14=13=0#PLA;1=3.0=0;2=2.4=0;3=10=0;4=5.1=0;5=4.3=0;6=3.9=0;7=5.6=0;8=2.8=0;9=4.5=0;10=4.0=0;11=6.0=0;12=5.7=0;13=2.0=1;14=3.6=0@@@WIN;1=13=0;2=19=0;3=56=0;4=5.0=0;5=3.3=1;6=66=0;7=19=0;8=11=0;9=4.9=0;10=14=0;11=11=0;12=17=0#PLA;1=3.8=0;2=4.7=0;3=9.7=0;4=1.9=0;5=1.5=1;6=11=0;7=4.2=0;8=3.9=0;9=2.1=0;10=3.8=0;11=2.9=0;12=4.6=0@@@WIN;1=14=0;2=1.7=1;3=39=0;4=29=0;5=7.8=0;6=28=0;7=12=0;8=28=0;9=11=0;10=55=0;11=38=0;12=14=0;13=49=0;14=51=0#PLA;1=3.4=0;2=1.7=1;3=9.0=0;4=5.6=0;5=2.8=0;6=6.1=0;7=2.0=0;8=4.0=0;9=2.9=0;10=6.9=0;11=6.8=0;12=3.1=0;13=7.5=0;14=8.3=0@@@WIN;1=SCR=0;2=5.4=0;3=14=0;4=5.6=0;5=13=0;6=4.2=0;7=17=0;8=14=0;9=13=0;10=3.7=1#PLA;1=SCR=0;2=2.4=0;3=4.2=0;4=2.3=0;5=3.1=0;6=1.3=0;7=5.5=0;8=5.3=0;9=5.0=0;10=1.1=1@@@WIN;1=4.9=0;2=16=0;3=16=0;4=11=0;5=8.3=0;6=17=0;7=11=0;8=15=0;9=5.9=0;10=4.2=1;11=15=0#PLA;1=1.8=1;2=4.2=0;3=5.9=0;4=2.9=0;5=2.5=0;6=4.4=0;7=3.4=0;8=3.8=0;9=2.3=0;10=2.3=0;11=3.5=0@@@WIN;1=11=0;2=10=0;3=11=0;4=14=0;5=8.1=0;6=21=0;7=13=0;8=9.3=0;9=24=0;10=30=0;11=2.4=1;12=30=0#PLA;1=2.8=0;2=3.2=0;3=2.9=0;4=3.8=0;5=2.8=0;6=3.9=0;7=2.6=0;8=2.8=0;9=5.5=0;10=5.6=0;11=2.3=1;12=4.1=0@@@WIN;1=7.3=0;2=7.3=0;3=30=0;4=14=0;5=11=0;6=10=0;7=26=0;8=13=0;9=46=0;10=28=0;11=13=0;12=3.4=1;13=11=0;14=29=0#PLA;1=2.0=0;2=3.7=0;3=7.7=0;4=4.7=0;5=4.3=0;6=3.8=0;7=5.2=0;8=4.2=0;9=7.8=0;10=7.0=0;11=3.3=0;12=1.6=1;13=4.2=0;14=5.4=0"} 		
		*/
list($d['body'], $d['headers']) = explode(':',$obj);
list($d_pre['body'], $d_pre['headers']) = explode(':',$obj_pre);


// list($curr['headers'], $curr['body']) = explode('@@@WIN#PLA',$obj);
// $curr_ex = multiexplode(array("@@@WIN","#PLA"),$curr['body']);
// print_r($curr_ex);


//$exploded = (isset($obj)) ? multiexplode(array("@@@WIN;","#PLA;"),$obj->{'OUT'}) : "";
//$exploded_pre = (isset($obj_pre)) ? multiexplode(array("@@@WIN;","#PLA;"),$obj_pre->{'OUT'}) : "";
if(isset($obj_pre)) {
	$exploded_pre = (isset($obj)) ? multiexplode(array("@@@WIN","#PLA"),$d['headers']) : "";
	$exploded = (isset($obj_pre)) ? multiexplode(array("@@@WIN","#PLA"),$d_pre['headers']) : "";
}else{
	$exploded = (isset($obj)) ? multiexplode(array("@@@WIN","#PLA"),$d['headers']) : "";
	$exploded_pre = (isset($obj_pre)) ? multiexplode(array("@@@WIN","#PLA"),$d_pre['headers']) : "";
}
$arraycount = (isset($obj)) ? count($exploded) : 0;
$arraycount_pre = (isset($obj_pre)) ? count($exploded_pre) : 0;
// print_r($exploded)."<hr><br>---<br>";	//pre-sell
// print_r($exploded_pre);		//racing date
// echo "<br><br>";

$winstr = ($arraycount>1) ? $exploded[1] : 0;
$plastr = ($arraycount>1) ? $exploded[2] : 0;

$winstr_pre = ($arraycount_pre>1) ? $exploded_pre[1] : 0;
$plastr_pre = ($arraycount_pre>1) ? $exploded_pre[2] : 0;

// print_r($winstr_pre)."<hr>";
// print_r($plastr_pre);

$odds = array();
$odds_pre = array();
$prop_pre_array = array();
$prop_curr_array = array();

$cnt=0;
$raceno=0;
foreach($poolStatus as $racestatus){
	// echo $racestatus.$raceno."<br>";
	if(!$racestatus ){
		$raceno++;
	}
}
$raceno--;
// echo $raceno;
$termination=0;
/*
    [0] =&gt; 113243
    [1] =&gt; 1=158=0;2=13=0;3=3.1=1;4=12=0;5=10=0;6=6.1=0;7=83=0;8=14=0;9=54=0;10=19=0;11=13=0;12=5.6=0;13=45=0;14=23=0
    [2] =&gt; 1=32=0;2=3.4=0;3=1.5=1;4=4.0=0;5=3.3=0;6=2.2=0;7=17=0;8=3.8=0;9=11=0;10=5.5=0;11=3.7=0;12=2.0=0;13=9.1=0;14=4.6=0
*/
foreach($exploded_pre as $key=>$value){
	// $oddtmp[$cnt]
}
//---------------------------------------------------------------------------------------------------------------------
if(isset($obj_pre)){	
	// if(!$poolStatus[$raceno]){
	// 	$raceno++;
	// }
	foreach($exploded_pre as $key=>$value){
		$totalwp=0;
		if($cnt){		
			// echo $raceno."<br>";	
			$racearray = explode(';', $value);
			foreach($racearray as $key=>$value){
				$arr = explode('=', $value);
				$horseno = $arr[0];
				$wp = $arr[1];	
				// echo $cnt."-".$raceno."-".$arr[0]."-".$arr[1]."-".$arr[2]."<br>";
				$termination = ($wp=="---" || $wp=="999" || !$wp) ? 1 : 0;
				// $termination = ($wp=="---" || $wp=="999" || !$wp) ? 1 : 0;			
				// || $wp=="999" || !$wp
				if($cnt % 2 == 0){
					$odds[$raceno][$horseno]["pre"]["pla"] = ($wp=="---" ) ? "-" : $wp;
					// echo $horseno."pla-".$wp."(".$cnt."(".$termination."<br>";
				}else{
					$odds[$raceno][$horseno]["pre"]["win"] = ($wp=="---") ? "-" : $wp;
					// echo $horseno."win-".$wp."(".$cnt."(".$termination."<br>";
				}	
				$oddst[$raceno]["pre"]["wptotal"] += $wp;
				$totalwp = $totalwp+$wp;
				// $totalpla = $totalpla+$pla;			
			}
		}					
		// echo $raceno."-".$totalwp."-".$totalpla."<br>";
		if($cnt % 2 == 0){// && $totalwp && $totalpla){
			// echo $raceno."-".$totalwp."-".$totalpla."<br>";
			$raceno++;
		}
		if(!$totalwp){
			// $raceno--;
		}
		// echo $raceno."-".$termination."<br>";
		// $cnt = ($termination ) ? $cnt++ : $cnt;
		if($termination){
			$raceno--;
			$cnt++;
		}
		$cnt++;	
		// $termination = ($wp=="---") ? 1 : 0;
		$cnt = $cnt+$termination;//
		$raceno = $raceno+$termination;//
	}
}

// print_r($odds);

//print_r($history);
// print_r($odds_prex);
//---------------------------------------------------------------------------------------------------------------------
$cnt=0;
$raceno=0;
foreach($poolStatus as $racestatus){
	// echo $racestatus.$raceno."<br>";
	if(!$racestatus ){
		$raceno++;
	}
}
$raceno--;
$termination=0;
// if(!$poolStatus[$raceno]){
// 	$raceno++;
// }
foreach($exploded as $key=>$value){
	if($cnt){
		// echo $raceno."<br>";
		$racearray = explode(';', $value);
		$totalwp=0;
//		print_r($racearray);
/*
    [0] =&gt; 1=158=0
    [1] =&gt; 2=13=0
    [2] =&gt; 3=3.1=1
*/
		foreach($racearray as $key=>$value){
			$arr = explode('=', $value);
			$horseno = $arr[0];
			$wp = $arr[1];
			$termination = ($wp=="---" || $wp=="999" || !$wp ) ? 1 : 0;
			// echo $cnt."-".$raceno."-".$arr[0]."-".$arr[1]."-".$arr[2]."<br>";
	// if($odds[$raceno][$horseno]["pla"] && $odds[$raceno][$horseno]["win"]){
			if($cnt % 2 == 0){
				$odds[$raceno][$horseno]["curr"]["pla"] = ($wp=="---" || $wp=="999" || !$wp) ? 1 : $wp;
				// echo $horseno."pla-".$wp."(".$cnt."..(".$termination.$raceno."<br>";
			}else{
				$odds[$raceno][$horseno]["curr"]["win"] = ($wp=="---" || $wp=="999" || !$wp) ? 1 : $wp;
				// echo $horseno."win-".$wp."(".$cnt."(".$termination.$raceno."<br>";
			}
			$oddst[$raceno]["curr"]["wptotal"] += $wp;
			$totalwp = $totalwp+$wp;
	// }
			//echo $odds[1][$horseno]["win"]."<br>";
		}
	}
	if($cnt % 2 == 0){// && $totalwp && $totalpla){
		$raceno++;
	}
	if(!$totalwp){
		// $raceno--;
	}
		// echo $raceno."-".$termination."<br>";
		// $cnt = ($termination ) ? $cnt++ : $cnt;
		if($termination){
			$raceno--;
			$cnt++;
		}
	$cnt++;
	// $termination = ($wp=="---") ? 1 : 0;
	$cnt = $cnt+$termination;//
	$raceno = $raceno+$termination;//
}
//--------------------------------------------------------------------------------------------------------------------
//--------------------------------------------------------------------------------------------------------------------
//--------------------------------------------------------------------------------------------------------------------
$odds = array();
$odds_pre = array();

$raceno=0;
foreach($poolStatus as $racestatus){
	// echo $racestatus.$raceno."<br>";
	if(!$racestatus ){
		$raceno++;
	}
}
$raceno--;
// $oddstring = $obj;
// list($arr['headers'], $arr['body']) = explode('@@@WIN#PLA@@@WIN',$oddstring);
// list($arr['headers'], $arr['body']) = explode('@@@WIN',$oddstring);
// $bodytoarray = multiexplode(array("@@@WIN","#PLA"),$arr['body']);
$bodytoarray = $exploded_pre;	//pre

// print_r($bodytoarray);
$cnt=0;
// $raceno=2;
foreach($bodytoarray as $key => $oddsarray){
	// print_r($oddsarray);
	$eachno = explode(';', $oddsarray);	//1=2=0;...
		foreach($eachno as $value){
			$arr = explode('=', $value);
			$horseno = $arr[0];
			$wp = $arr[1];	
			// echo "<br>".$cnt."-[[".$raceno."]]-".$key."-".$arr[0]."-".$arr[1]."-".$arr[2]."<br>";
			if($cnt % 2 == 0 ){
				$odds[$raceno][$horseno]["pre"]["pla"] = $wp;	//($wp=="---" ) ? "-" : $wp;
			}else{
				$odds[$raceno][$horseno]["pre"]["win"] = $wp;	//($wp=="---") ? "-" : $wp;
			}	
		}
	if($cnt % 2 == 0){// && $key){
		$raceno++;
	}
	$cnt++;
}
// print_r($odds);
//--------------------------------------------------------------------------------------------------------------------
$raceno=0;
foreach($poolStatus as $racestatus){
	// echo $racestatus.$raceno."<br>";
	if(!$racestatus ){
		$raceno++;
	}
}
$raceno--;
// $oddstring = $obj_pre;
// list($arr['headers'], $arr['body']) = explode('@@@WIN#PLA@@@WIN',$oddstring);
// list($arr['headers'], $arr['body']) = explode('@@@WIN',$oddstring);
// $bodytoarray = multiexplode(array("@@@WIN","#PLA"),$arr['body']);
$bodytoarray = $exploded;
// print_r($bodytoarray);
$cnt=0;
// $raceno=2;
foreach($bodytoarray as $key => $oddsarray){
	$eachno = explode(';', $oddsarray);	//1=2=0;...
		foreach($eachno as $value){
			$arr = explode('=', $value);
			$horseno = $arr[0];
			$wp = $arr[1];	
			// echo $cnt."-[".$raceno."]-key:".$key."-".$arr[0]."-".$arr[1]."-".$arr[2]."<br>";
			if($cnt % 2 == 0 ){
				$odds[$raceno][$horseno]["curr"]["pla"] = $wp;	//($wp=="---" ) ? "-" : $wp;
			}else{
				$odds[$raceno][$horseno]["curr"]["win"] = $wp;	//($wp=="---") ? "-" : $wp;
			}	
		}
	if($cnt % 2 == 0){// && $key){
		$raceno++;
	}
	$cnt++;
}
// print_r($odds);
//--------------------------------------------------------------------------------------------------------------------
if($mm) { echo "[7]-".number_format(microtime(true) - $time_start, 2)."-"; }

foreach ($odds as $raceno => $value){
	$prop_pre_max = $prop_curr_max = 0;
	foreach($value as $horseno => $title){
		$prop_pre = is_numeric($title["pre"]["pla"]) ? number_format(100*$title["pre"]["pla"]/$title["pre"]["win"],0) : 0;
		$odds[$raceno][$horseno]["pre"]["prop"] = $prop_pre;
		$prop_pre_array[$raceno][] = $prop_pre;

		$win_pre_array[$raceno][] = $title["pre"]["win"];
		// echo $raceno."-".$horseno."-".$title["pre"]["win"]."<br>";

		$prop_curr  = is_numeric($title["curr"]["pla"]) ? number_format(100*$title["curr"]["pla"]/$title["curr"]["win"],0) : 0;
		$odds[$raceno][$horseno]["curr"]["prop"] = $prop_curr;
		$prop_curr_array[$raceno][] = $prop_curr;

		$stdev[$raceno][] = is_numeric($title["pre"]["pla"]) ? ($prop_curr-$prop_pre) : $prop_pre;

		$prop_pre_max = ($prop_pre_max > $prop_pre) ? $prop_pre_max : $prop_pre;
		$prop_curr_max = ($prop_curr_max > $prop_curr) ? $prop_curr_max : $prop_curr;
	}
	$wpratio_max[$raceno]['pre'] = $prop_pre_max;
	$wpratio_max[$raceno]['curr'] = $prop_curr_max;
	// echo $prop_pre_max."<br>";
	// print_r($stdev)."<br>";
}

// print_r($prop_pre_array);
// sorting ---------------------------------------------------------------------------------------------------------------------
$pick = array();
$odds_sorted = array();
$datasource = empty($odds_pre) ? $odds : $odds_pre;
// print_r($odds); //== pre
// print_r($datasource);
// die(1);
foreach($datasource as $raceno => $racearray){
	foreach($racearray as $horseno => $detail){
		// print_r($detail);
		if($detail["pre"]["win"]=="999" || $detail["pre"]["win"]=="1" || $detail["pre"]["win"]=="---" || !$detail["pre"]["pla"]){
			// echo 1;
			uasort($racearray, function($a, $b) {
					return ($a["curr"]["win"] <= $b["curr"]["win"]) ? -1 : 1;
					return ($a["curr"]["pla"] <= $b["curr"]["pla"]) ? -1 : 1;
			});
		}else{
			// echo 2;
			uasort($racearray, function($a, $b) {
				return ($a["pre"]["win"] <= $b["pre"]["win"]) ? -1 : 1;
				return ($a["pre"]["pla"] <= $b["pre"]["pla"]) ? -1 : 1;
			});
		}
	}
// die(1);
	$firstbig3x="";
	$cnt=$foundbig3x=0;
	$bestimearray = array();
	$have3xpre=0;
	$have3xcurr=0;
	foreach($racearray as $horseno => $detail){
		$odds_sorted[$raceno][$horseno]['pre']["win"]  = $detail['pre']["win"];
		$odds_sorted[$raceno][$horseno]['pre']["pla"]  = $detail['pre']["pla"];
		$odds_sorted[$raceno][$horseno]['pre']["prop"] = $detail['pre']["prop"];
		$odds_sorted[$raceno][$horseno]['pre']["samewin"] = array_icount_values($win_pre_array[$raceno],$detail['pre']["win"]);
		$odds_sorted[$raceno][$horseno]['pre']["samewp"] = array_icount_values($prop_pre_array[$raceno],$detail['pre']["prop"]);
		//first big3x
// 		if($foundbig3x==0 && $firstbig3x==0 && $detail['pre']["prop"] >=30 && $detail['pre']["prop"] <40 && $detail['pre']["prop"] > $firstbig3x){
        if($detail['pre']["prop"] >=30 && $detail['pre']["prop"] <40 ){
// 			$firstbig3x = $detail['pre']["prop"];
// 			$biggest3[$raceno][$horseno]['pre'] = ($biggest3[$raceno][$horseno]['pre'] > $detail['pre']["prop"]) ? $biggest3[$raceno][$horseno]['pre'] : $detail['pre']["prop"];
// 			$biggest3[$raceno][$horseno]['curr'] = ($biggest3[$raceno][$horseno]['curr'] > $detail['curr']["prop"]) ? $biggest3[$raceno][$horseno]['curr'] : $detail['curr']["prop"];
			$foundbig3x++;
			$biggest3x[$raceno]['pre'] = ($biggest3x[$raceno]['pre']*1 > $detail['pre']["prop"]*1) ? $biggest3x[$raceno]['pre']*1 : $detail['pre']["prop"]*1;
			$have3xpre++;
// 			echo "<br>".$raceno."..".$have3xpre."..".$detail['pre']["prop"]."..".$biggest3x[$raceno]['pre'];
		}
		$odds_sorted[$raceno][$horseno]['pre']["firstbig3x"] = $firstbig3x;
		//firstbig3x ----------------------------------------------------------------------------------------------------------------------
        if($detail['curr']["prop"] >=30 && $detail['curr']["prop"] <40 ){
			$biggest3x[$raceno]['curr'] = ($biggest3x[$raceno]['curr'] > $detail['curr']["prop"]) ? $biggest3x[$raceno]['curr'] : $detail['curr']["prop"];
			$have3xcurr++;
		}
		
		//-------------------------------------------------------------------------------
		$pick[$raceno][$horseno] = ($odds_sorted[$raceno][$horseno]['pre']["prop"] == $firstbig3x) ? "p" : "";	
		
		//isH
		// if($cnt>0 && $temp_pre_prop < $detail['pre']["prop"]){
			$odds_sorted[$raceno][$horseno]['pre']["isH"] = ($cnt>0 && $temp_pre_prop < $detail['pre']["prop"]) ? "H" : "";
		// }

		$sorted_prop_pre_array[$raceno][] = $detail['pre']["prop"];

		$odds_sorted[$raceno][$horseno]['curr']["win"]=$detail['curr']["win"];
		$odds_sorted[$raceno][$horseno]['curr']["pla"]=$detail['curr']["pla"];
		$odds_sorted[$raceno][$horseno]['curr']["prop"]=$detail['curr']["prop"];		
		$odds_sorted[$raceno][$horseno]['curr']["samewp"] = array_icount_values($prop_curr_array[$raceno],$detail['curr']["prop"]);
		
		$table_winpre[$raceno][$horseno]=$detail['pre']["win"];
		$table_win[$raceno][$horseno]=$detail['curr']["win"];
		$table_pla[$raceno][$horseno]=$detail['curr']["pla"];

		$temp_pre_prop = $detail['pre']["prop"];
		$cnt++;
	}
	$biggest3x[$raceno]['pre'] = ($have3xpre>1) ? $biggest3x[$raceno]['pre'] : "";
	$biggest3x[$raceno]['curr'] = ($have3xcurr>1) ? $biggest3x[$raceno]['curr'] : "";
	// print_r($table_win);

	//find b2u_nextbig -------------------------------------------------------------------------------------------------------
	krsort($sorted_prop_pre_array[$raceno]);	//sort prop DESC order
	// print_r($sorted_prop_pre_array[$raceno]);
	$max_1 = $max_2 = $cnt_b2u_nextbig = 0;
	foreach($sorted_prop_pre_array[$raceno] as $value){
		if ( $value > $max_1) {
			$max_2 = $max_1;
			$max_1 = $value;
			// echo "<br>Max1=".$max_1."Max2=".$max_2."value=".$value;
		} else if ($value<40 && $value>30  && $value > $max_2 && $value != $max_2) {
			$max_2 = $value;
			// echo "<br>value=".$value;
			$b2u_nextbig = ($cnt_b2u_nextbig<=2) ? $tempvalue : $b2u_nextbig;
			$cnt_b2u_nextbig++;
		}
		$tempvalue = $value;
	}
	/*best time-------------------*/
	$bestimearray = array();
	foreach($racearray as $horseno => $detail){
		// echo $racecard[$raceno][$horseno]['BestTime_val'];
		if($racecard[$raceno][$horseno]['BestTime_val']){
			$bestimearray[] = (int)$racecard[$raceno][$horseno]['BestTime_val'];
		}
	}
	$bestime_min[$raceno] = !empty($bestimearray) ? min($bestimearray) : 0;

	//b2u_nextbig -------------------------------------------------------------------------------------------------------
	$odds_sorted[$raceno]["b2u_nextbig"] = $b2u_nextbig;
	foreach($racearray as $horseno => $detail){
		$pick[$raceno][$horseno] .= ($detail['pre']["prop"] == $b2u_nextbig && !$odds_sorted[$raceno][$horseno]['pre']["isH"]) ? "b" : "";
	}
	// print_r($pick);
	//find big2xRel -------------------------------------------------------------------------------------------------------
	$big2xRel_tmp=$prop_temp=array();
	$big2xRel_cnt=0;
	$horse_cnt = count($racearray);	//."<br>";
	$cnt=1;
	$same3_2x=1;

	foreach($racearray as $horseno => $detail){
		if($odds_sorted[$raceno][$horseno]['pre']["samewp"]>1 && $detail['pre']["prop"]>20 && $detail['pre']["prop"]<30 && $big2xRel_cnt && !in_array($detail['pre']["prop"], $prop_temp)){
			$big2xRel_tmp[$raceno][$horseno_tmp] = $horseno_tmp;
			$prop_temp[] = $detail['pre']["prop"];
			$pick[$raceno][$horseno_tmp] .= "p";  //big2xRel
			
		} 
		if($odds_sorted[$raceno][$horseno]['pre']["samewp"]>2 && $detail['pre']["prop"]>20 && $detail['pre']["prop"]<30){
			$pick[$raceno][$horseno] .= ($same3_2x==2) ? "t" : "";  //second same 2x
			$same3_2x++;			
		} 

		//bottom 18 ----------------------------------------------------------&& $detail['pre']["prop"] > $last_prop
		$pick[$raceno][$horseno] .= ($horse_cnt == $big2xRel_cnt+1 && $detail['pre']["prop"]=="18") ? "u" : "";
		//middle 5x --------------------------------------------------------------------------------------------------
		$pick[$raceno][$horseno] .= ($big2xRel_cnt>3 && $detail['pre']["prop"]>=50) ? "5" : "";
		//bottom 19 -------------------------------------------------------------------------------------------------------
		$pick[$raceno][$horseno_tmp] .= ($horse_cnt == $big2xRel_cnt+1 && $detail['pre']["prop"]=="19") ? "F" : "";
		//TTD-------------------------------------------------------------------------------------------------------
		$pick[$raceno][$horseno] .= ($detail['pre']["prop"]<60 && ($detail['pre']["prop"]-$detail['curr']["prop"])>20) ? "x" : "";
		//M29-------------------------------------------------------------------------------------------------------
		$pick[$raceno][$horseno] .= ($detail['pre']["prop"]==29 && $cnt<=5) ? "*" : "";
		//top same pla-------------------------------------------------------------------------------------------------------
		$pick[$raceno][$horseno] .= ($cnt<3 && $last_pla == $odds_sorted[$raceno][$horseno]['pre']["pla"]) ? "x" : "";
		//top same pla-------------------------------------------------------------------------------------------------------
		$pick[$raceno][$horseno] .= ($cnt>1 && $cnt<3 && $last_pla > $odds_sorted[$raceno][$horseno]['pre']["pla"]) ? "*" : "";
		// echo $raceno."-".$horseno."-".$last_pla."-".$odds_sorted[$raceno][$horseno]['pre']["pla"]."<br>";
		
		$horseno_tmp = $horseno;
		$last_prop = $detail['pre']["prop"];
		$last_pla = $odds_sorted[$raceno][$horseno]['pre']["pla"];
		$big2xRel_cnt++;
		$cnt++;
	}
	
// print_r($sorted_prop_pre_array)."<br><br>";
}
// print_r($pick);
//3H, H-H-H(noty) or H-(H)-H(isy)-----------------------------------------------------------------------------------------------------------------
$countH = array();
foreach($odds_sorted as $raceno => $racearray){
	$isH_cnt=$isH_cntx=$last_isH=$last_horseno=0;
	foreach($racearray as $horseno => $detail){
		if($detail['pre']["isH"]=="H" && !$last_isH){ 
			$isH_cnt++;
		}
		$last_isH = $detail['pre']["isH"];
	}
	if($isH_cnt==3){
		foreach($racearray as $horseno => $detail){
			if($detail['pre']["isH"]=="H" && !$last_isH){ 
				
				$isH_cntx++;
				$pick[$raceno][$horseno] .= ($isH_cntx==3 && !$last_isH && !$pick[$raceno][$last_horseno]) ? "H" : "";	//-------------------------
			}
			$last_isH = $detail['pre']["isH"];
			$last_horseno = $horseno;
		}
	}
	$countH[$raceno] = $isH_cnt;		
}

//----------------------------------------------------------------------------------------------------------------------
$barriercomment = mysqli_query($mysqli, "select * from barriercomment ");
function barriercomment($barriercomment,$comment){
	$str='';	
	foreach ($barriercomment as $bc) {
// 		echo $bc['comment'];
		if (strpos($comment, $bc['comment']) !== false) {
			$str .= $bc['rank'];
		}
	}
	return $str;
}
// echo $venue;
//saving t20//----------------------------------------------------------------------------------------------------------------------
$pront20 = "";
foreach($odds_sorted as $raceno => $racearray){
	foreach($racearray as $horseno => $detail){
		if(is_numeric($horseno) && is_numeric($detail['pre']["prop"]) && is_numeric($detail['curr']["prop"])){
			// if($tri20[$raceno][$horseno] || $ttt20[$raceno][$horseno] || $fft20[$raceno][$horseno] || $qtt20[$raceno][$horseno]){
				$pront20 .= "(NULL,'".$racingdate."','".$venue."',".$raceno.",".$horseno.",now(),".$detail['pre']["prop"].",".$detail['curr']["prop"].",NULL,NULL,NULL,NULL,'"
				.($tri20[$raceno][$horseno] ? $tri20[$raceno][$horseno] : 0)."','"
				.($ttt20[$raceno][$horseno] ? $ttt20[$raceno][$horseno] : 0)."','"
				.($fft20[$raceno][$horseno] ? $fft20[$raceno][$horseno] : 0)."','"
				.($qtt20[$raceno][$horseno] ? $qtt20[$raceno][$horseno] : 0)."',now()),";			
			// }
		}
	}
}	

$pront20 = substr($pront20,0,strlen($pront20)-1)."";
$query = "insert into `hrp_prophist` (
	`id` ,
	`racingdate` ,
	`venue` ,
	`raceno` ,
	`horseno` ,
	`proptime` ,
	`prop` ,
	`prop_curr` ,
	`prex_wpratio_maxi_save` ,
	`trihint` ,
	`trihint_c` ,
	`wpmaxhistory` ,
	`tri20` ,
	`ttt20` ,
	`fft20` ,
	`qtt20` ,
	`rectime`
	) values ".$pront20;
// echo $query."<br>";
mysqli_query($mysqli, $query);
// $mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
// $db->query($query);	
// print_r(mysqli_error_list($mysqli));
// mysqli_close($mysqli);
//pop max history----------------------------------------------------------------------------------------------------------------------
// mysql_select_db(DB_NAME);

$today = date("Y-m-d");
// $sql = "SELECT 
// 			FROM `".DB_NAME."`.`hrp_prophist`
// 			WHERE racingdate = '".$racingdate."' asc limit 1";
$sql = "SELECT *
			FROM `hrp_prophist`
			WHERE racingdate = '".$racingdate."' and venue = '".$venue."' and prop_curr and prop order by proptime asc limit 1";
// echo $sql."<br><br>";
// $pro_history_inittime = $db->get_row($sql);
$history_array = array();
$sql = "SELECT raceno, horseno, 
			Min( prop_curr ) AS min_prop_curr,
			MAX( prop_curr ) AS max_prop_curr,
			MAX( tri20 ) AS max_tri20, 
			MAX( ttt20 ) AS max_ttt20, 
			MAX( fft20 ) AS max_fft20, 
			MAX( qtt20 ) AS max_qtt20
			FROM `hrp_prophist`
			WHERE racingdate = '".$racingdate."' and venue = '".$venue."' and DATE_FORMAT(rectime,'%Y-%m-%d') = '".$today."' 
			GROUP BY raceno,horseno
 		";		
		//and proptime <>'".$pro_history_inittime->proptime."'
// echo $sql."<br>";
// $pro_history = $db->get_results($sql);
$pro_history = mysqli_query($mysqli, $sql);
// $pro_history = mysqli_fetch_assoc($pro_history);
// if(mysql_num_rows($select_crm)>0)
// foreach ( $pro_history as $data ) {
if(mysqli_num_rows($pro_history)>0){
    while($data = mysqli_fetch_assoc($pro_history)){
        // echo $data['max_prop_curr'];
    	$history_array[$data['raceno']][$data['horseno']]["max_prop_curr"]= $data['max_prop_curr'];
    	$history_array[$data['raceno']][$data['horseno']]["min_prop_curr"]= $data['min_prop_curr'];
    	$history_array[$data['raceno']][$data['horseno']]["max_tri20"]= $data['max_tri20'];
    	$history_array[$data['raceno']][$data['horseno']]["max_ttt20"]= $data['max_ttt20'];
    	$history_array[$data['raceno']][$data['horseno']]["max_fft20"]= $data['max_fft20'];
    	$history_array[$data['raceno']][$data['horseno']]["max_qtt20"]= $data['max_qtt20'];
    }
}

if($mm) { echo "[8]-".number_format(microtime(true) - $time_start, 2)."-"; }
//----------------------------------------------------------------------------------------------------------------------
// echo 5;
// print_r($odds_sorted);
$horseinfo_hints=array();
$horseinfo_hints_query = "select * from horseinfo_hints     
        WHERE racingdate = '$racingdate'
        AND venue = '$venue' ";
// echo $horseinfo_hints_query;        
$horseinfo_hints_result = mysqli_query($mysqli, $horseinfo_hints_query);        
if(mysqli_num_rows($horseinfo_hints_result)>0){
    while($data = mysqli_fetch_assoc($horseinfo_hints_result)){
        $horseinfo_hints[$data['raceno']][$data['horseno']]['trackpla'] = $data['trackpla'];
        $horseinfo_hints[$data['raceno']][$data['horseno']]['jockeypla'] = $data['jcpla'];
        $horseinfo_hints[$data['raceno']][$data['horseno']]['jockeysamelast'] = $data['jcex'];
        $horseinfo_hints[$data['raceno']][$data['horseno']]['dist'] = $data['dist'];
        $horseinfo_hints[$data['raceno']][$data['horseno']]['ai2pos'] = $data['ai2pos'];
        $horseinfo_hints[$data['raceno']][$data['horseno']]['same2last'] = $data['same2last'];
    }
}
$pick = array();
$hrp_pick2_query = "select * from hrp_pick2
        WHERE racingdate = '$racingdate'
        AND venue = '$venue' ";
// echo $horseinfo_hints_query;        
$hrp_pick2_result = mysqli_query($mysqli, $hrp_pick2_query);        
if(mysqli_num_rows($hrp_pick2_result)>0){
    while($data = mysqli_fetch_assoc($hrp_pick2_result)){
        $pick[$data['raceno']]['pick1'] = $data['pick1'];
        $pick[$data['raceno']]['pick2'] = $data['pick2'];
        $pick[$data['raceno']]['pick3'] = $data['pick3'];
        $pick[$data['raceno']]['pick4'] = $data['pick4'];
    }
}
    //  print_r($pick);   
//----------------------------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------------------
//table start----------------------------------------------------------------------------------------------------------------------
//table start----------------------------------------------------------------------------------------------------------------------
//table start----------------------------------------------------------------------------------------------------------------------
//table start----------------------------------------------------------------------------------------------------------------------
//table start----------------------------------------------------------------------------------------------------------------------
//table start----------------------------------------------------------------------------------------------------------------------
//table start----------------------------------------------------------------------------------------------------------------------
$Trainer_Jockey = "";
$ispla_Trainer_Jockey = array();

if($_GET['mm']){
    if(substr($venue,0,2)=="S1" || $venue == "S2" || $venue == "S3" || $venue == "S4" || $venue == "S5" || $venue == "S6"){
    	$columns =  56-4;
	}else{
	    $columns =  56;
	}
	$dispfull = 1;
// 	echo $columns.$venue;
}else{
	$columns =  47;
	$dispfull = 0;
}
foreach($odds_sorted as $raceno => $racearray){
	$tmpraceno = "";
	$temp_pla = 0;
	$same3big_selected=0;
	$tmp_prepla_todd = 0;
	// $listr .= $raceno.")    ".$racedatahead[$raceno];  ///racetime-----------------$racedatahead[$raceno]
	$class = ".qpmatrix".$raceno;
	$listr .= $raceno ? "<a href='#' onclick=\"w3.toggleShow('{$class}')\">@</a>".$racedatahead[$raceno] : "";  ///racetime-----------------$racedatahead[$raceno]
	$listr .= $raceno ? "  (".number_format($poolpro[$raceno]).")" : "";
	// $listr .= "<div class='qpmatrix'>";
	$class = str_replace('.','',$class)." qpmatrix";
	$venuefull = $racedataheadvenue[$raceno];
	
	if($qinmatrix){ //-----------------------------------------------------------------------------------------------------------------------------------------
    	$listr .= "<table class='$class'><tr><td>";
    	$listr .= output_wp_matrix($raceno,$qinpre[$raceno],$table_winpre[$raceno],$resultarray[$raceno]);
    	$listr .= "<td>";
    	$listr .= output_wp_matrix($raceno,$qin[$raceno],$table_win[$raceno],$resultarray[$raceno]);
    	$listr .= "<td>";
    	$listr .= output_wp_matrix($raceno,$qpl[$raceno],$table_pla[$raceno],$resultarray[$raceno]);
    	$listr .= "</table>";
	}
	// $listr .= "</div>";

	$listr .= "<table border=1 cellpadding='2' cellspacing='1' border-collapse= collapse; style=\"border-collapse: collapse;\">";
	$racerowcnt=1;
	foreach($racearray as $horseno => $detail){
		if($raceno && is_numeric($horseno) && $racecard[$raceno][$horseno]['horsename'] && $poolStatus[$raceno]){
			// $query = "insert into hrp_prophist values ('','".$racingdate."',".$raceno.",".$horseno.",now(),".$detail['pre']["prop"].",".$detail['curr']["prop"].",".$tri20[$raceno][$horseno].",'".$ttt20[$raceno][$horseno]."','".$fft20[$raceno][$horseno]."','".$qtt20[$raceno][$horseno]."')";
			// echo $query."<br>";
			// mysqli_query($mysqli, $query);		
			// $trainer_jockey_prop[(string)$racecard[$raceno][$horseno]['Trainer'].(string)$racecard[$raceno][$horseno]['Jockey']] .= "[".$detail['pre']["prop"];

// 			$firstbig3x = ($detail['pre']["firstbig3x"] == $detail['pre']["prop"]) ? "<u> " : "";
			$is_maxwp_pre = ($detail['pre']["prop"] == $wpratio_max[$raceno]['pre'])  ? "<b><font color=red>" : "";
			$is_maxwp_curr = ($detail['curr']["prop"] == $wpratio_max[$raceno]['curr'])  ? "<b><font color=red>" : "";
// 			$isbiggest3pre  = ($biggest3[$raceno][$horseno]['pre']  == $detail['pre']["prop"] ) ? "<b>" : "";
            $isbiggest3pre  = ($biggest3x[$raceno]['pre']  == $detail['pre']["prop"] ) ? "*" : "";
// 			$isbiggest3curr = ($biggest3[$raceno][$horseno]['curr'] == $detail['curr']["prop"]) ? "<b>" : "";
            $isbiggest3curr = ($biggest3x[$raceno]['curr'] == $detail['curr']["prop"]) ? "*" : "";
            //------------------------------------------------------------------------------------------------------------------------------------------
            $horseid = $racecard[$raceno][$horseno]['BrandNo'];
            list($pla1, $pla2, $pla3, $pla4) = explode(',', $horserankhistory[$horseid]['plax']);
            list($win_oddsx1, $win_oddsx2, $win_oddsx3, $win_oddsx4) = explode(',', $horserankhistory[$horseid]['win_oddsx']);
            list($rc_track_course1, $rc_track_course2, $rc_track_course3, $rc_track_course4) = explode(',', $horserankhistory[$horseid]['rc_track_course']);
            $histchk_pla1 = getPlaColor($pla1);
            $histchk_pla2 = getPlaColor($pla2);
            $histchk_pla3 = getPlaColor($pla3);
            $histchk_pla4 = getPlaColor($pla4);
            $histchk_win_oddsx1 = getWinOddsComparison($win_oddsx1,$win_oddsx2);
            $histchk_win_oddsx2 = getWinOddsComparison($win_oddsx2,$win_oddsx3);
            $histchk_win_oddsx3 = getWinOddsComparison($win_oddsx3,$win_oddsx4);
            $histchk_course1 = getTrackCourseComparison($rc_track_course1, $rc_track_course2);
            $histchk_course2 = getTrackCourseComparison($rc_track_course2, $rc_track_course3);
            $histchk_course3 = getTrackCourseComparison($rc_track_course3, $rc_track_course4);
            
            $historyodds = currentWinOddsComparison($detail['curr']["win"],$win_oddsx1);
            $historyodds .= $histchk_pla1.$histchk_win_oddsx1.$histchk_course1.getDigitValue($win_oddsx1)."</font>"
                           .$histchk_pla2.$histchk_win_oddsx2.$histchk_course2.getDigitValue($win_oddsx2)."</font>"
                           .$histchk_pla3.$histchk_win_oddsx3.$histchk_course3.getDigitValue($win_oddsx3)."</font>";
                $resultrank = $resultx[$venue][$raceno][$horseno];
                // echo $venue.$raceno.$horseno."<br>";
       			if($resultrank>0){
                    	$insert = "	INSERT INTO historyodds
                    	VALUES('','$venue','$raceno','$horseid','$historyodds','$resultrank',now())";
                    // 	echo $insert."<br>";
                    	mysqli_query($mysqli, $insert);
    			}
            $cntin = 0;
            $cntout = 0;
            $plaxArray = explode(', ', $horserankhistory[$horseid]['plax']);
            for ($i = 0; $i < count($plaxArray); $i++) {
                if ($horserankhistory[$horseid]['dr'][$i] < 5 && $plaxArray[$i] < 4) {
                    $cntin++;
                } elseif ($horserankhistory[$horseid]['dr'][$i] >= 5 && $plaxArray[$i] < 4) {
                    $cntout++;
                }
            }

            // $inoutratio = ($cntin || $cntout) ? (':'.$cntin.'-'.$cntout) : "";
            // $inouthistory = ($cntin || $cntout) ? ( ($cntin > $cntout) ? "align=left" : ( ($cntin == $cntout) ? "align=center" : "align=right") ) : "align=center style='background-color:lightgrey'";
            $inouthistory = ($cntin || $cntout) ? (($cntin > $cntout) ? "align=left" : (($cntin == $cntout) ? "align=center" : "align=right")) : "align=center style='background-color:lightgrey'";
            
            $inoutalert = ($cntin || $cntout) ? (($cntin > $cntout && $racecard[$raceno][$horseno]['Draw'] <= 5) ? "<font color=red>" : (($cntin < $cntout && $racecard[$raceno][$horseno]['Draw'] > 5) ? "<font color=red>" : "")) : "";

			//same3big-------------------------------------------------------------------------------------------------------
			if(!$same3big_selected && $detail['pre']["prop"]>30 && $detail['pre']["samewp"]>1 && $detail['pre']["prop"]<$detail['pre']["firstbig3x"]){
				$pick[$raceno][$horseno] .= "s3";
				$same3big_selected=1;
			}

			$dev = number_format(Stand_Deviation($stdev[$raceno]),1);
			if($raceno == $tmpraceno){
				$listr .= "<tr>";
			}else{
				// $listr .= "<tr><td colspan=38>".$raceno." (".$dev.")  $racedatahead[$raceno]";  ///racetime-----------------$racedatahead[$raceno]
				$listr .= "<thead><tr>";
				for($i = 1; $i<$columns; $i++ ){
					$listr .= "<th class='order'></th>";
				}
				$listr .= "</tr>";
				$listr .= "</thead>";
				$listr .= "<tr>";
			}
			// $listr .= "".( ? "<tr><td>" : 
			$is_samewp_pre  = ($detail['pre']["samewp"]>1) ? "bgcolor=yellow" : "";
			$is_samewp_pre3  = ($detail['pre']["samewp"]==3 && $detail['pre']["prop"]>=20 )  ? "<u>" : "";
			$is_samewin_pre  = ($detail['pre']["samewin"]>1) ? "bgcolor=yellow" : "";
			$is_samewin_preX  = ($detail['pre']["samewin"]>1) ? "." : "";
			$is_samewp_curr = ($detail['curr']["samewp"]>1) ? "bgcolor=#FFFF99" : "";
			$is_24xontop = ($detail['pre']["samewp"]==2 && $detail['pre']["prop"]>=40 && $detail['pre']["prop"]<50 )  ? "<b>" : "";
			$nosamehist = $racecard[$raceno][$horseno]['BestTime'] ? "" : "bgcolor=lightgrey";
			$isbestime = ($bestime_min[$raceno] && $bestime_min[$raceno] == $racecard[$raceno][$horseno]['BestTime_val']) ? "<font color=red>*</font>" : "";

			$windiff = ($detail['pre']["win"]/$detail['curr']["win"] >1.15) ? "<font color=blue><b>" : "";
			$windiff .= ($detail['curr']["win"]/$detail['pre']["win"] >1.15) ? "<font color=lightgrey>" : "";

			$pladiff = ($detail['pre']["pla"]/$detail['curr']["pla"] >1.15) ? "<font color=blue>" : "";
			$pladiff .= ($detail['curr']["pla"]/$detail['pre']["pla"] >1.15) ? "<font color=gray><i>" : "";

			$pladrop = ($detail['pre']["win"]) ?
				(($detail['curr']["win"]<$detail['pre']["win"] && $detail['pre']["pla"] < $detail['pre']["pla"]) ? "<i>" : "") : "";
			/*pla smaller than last---------------------------*/
			$hl_smallerpla = ($temp_pla > $detail['pre']["pla"] ) ? "<i><font color=red>" : ""; 
			$samenos = ($horseno == $racecard[$raceno][$horseno]['Draw']) ? "<u>" : "";

			if($detail['pre']["prop"] < $history_array[$raceno][$horseno]["max_prop_curr"] && $detail['pre']["prop"] <= $history_array[$raceno][$horseno]["min_prop_curr"]){
				$uppro = "bgcolor=yellow";
				$uprop_countJockey[$racecard[$raceno][$horseno]['Jockey']]++;
			}else{
				$uppro = "";
			}
			//----------------------------------------------
			$jockeyshort = explode('(', $racecard[$raceno][$horseno]['Jockey']);
			$tjcss = ($TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['night_ratio']>=50) ? "<font color=red>" : "";
			$TJratio_disp = (isset($TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['night_ratio']))
								? $tjcss.$TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['night_ratio']
								: "-";
			$TJratio_disp .= "</font></b>[".$TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['night_racecnt']."]";
// 			$TJratio_disp .= "/";
			$tjcss = ($TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['day_ratio']>=50) ? "<font color=red>" : "";
			$TJratio_disp1 = (isset($TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['day_ratio']))
								? $tjcss.$TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['day_ratio']
								: "-";
			$TJratio_disp1 .= "</font></b>[".$TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['day_racecnt']."]";
			//----
			$tjcss1y = ($TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['night_ratio1yr']>=50) ? "<font color=red>" : "";
// 			echo $TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['night_ratio1yr'];
			$TJratio_night1yr = (isset($TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['night_ratio1yr']))
								? $tjcss1y.$TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['night_ratio1yr']
								: "-";
			$TJratio_night1yr .= "</font></b>[".$TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['night_racecnt1yr']."]";
// 			$TJratio_disp .= "/";
			$tjcss1y = ($TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['day_ratio1yr']>=50) ? "<font color=red>" : "";
			$TJratio_day1yr = (isset($TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['day_ratio1yr']))
								? $tjcss1y.$TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['day_ratio1yr']
								: "-";
			$TJratio_day1yr .= "</font></b>[".$TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['day_racecnt1yr']."]";
			//-----
			
			if($TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['day_ratio'] > $TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['night_ratio']){
			    $isday = "<b>";
			    $isnight = "";
			    $isday = $TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['day_ratio'] ? "d" : "";
			}else{
			    $isday = "";
			    $isnight = "<b>";
			    $isnight = $TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['night_ratio'] ? "n" : "";
			}
			
			
			$TJdorn = "";   
			if(($TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['total_ratio']>=50)){
    			$TJratio_total =  "bgcolor=yellow";
    			$TJcnt50[$racecard[$raceno][$horseno]['Trainer']]++;
    			$TJdorn = ($TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['night_ratio'] > $TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['day_ratio']) ? "N" : "D";
			}else{
			    $TJratio_total =  "";
			}
			
			if($TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['total_ratio']>=38 && $TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['total_ratio']<50){
			    $TJratio_35_50 = "bgcolor=#CDFFCD";
			    $TJcnt_35_50[$racecard[$raceno][$horseno]['Trainer']]++;
			    $TJdorn = ($TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['night_ratio'] > $TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['day_ratio']) ? "N" : "D";
    		}else{
			     $TJratio_35_50 = "";
			}
            //------------------------------------------------------------------------------------------------------
			$dist = "";
			if($racecard[$raceno][$horseno]['Dist']){
    			if($racecard[$raceno][$horseno]['Dist'] && $racecard[$raceno][$horseno]['Dist'] == $racedist[$raceno]){
    				$dist = "";
    			}else{
    				if($racecard[$raceno][$horseno]['Dist'] < $racedist[$raceno]){
    					$dist = "</font><font color=blue>+"; //this time increase
    				}else{
    					$dist = "</font><font color=green>-";
    				}
    			}
			}
			$islastjockey = $racecard[$raceno][$horseno]['lastjockey'] ? "<b>" : "";
			$islessthan10day = ($racecard[$raceno][$horseno]['daydiff']<=10) ? "<font color=red>" : "";		
			$same2last = $horseinfo_hints[$raceno][$horseno]['same2last'] ? "*" : "";

// echo $detail['pre']["prop"]."-".$history_array[$raceno][$horseno]["max_prop_curr"]."-".$detail['pre']["prop"]."-".$history_array[$raceno][$horseno]["min_prop_curr"]."<br>";
			//-----------------------------------------------------------------------------------------------------------------------------------------
			$wpdiff = $detail['pre']["win"]/($detail['pre']["win"]+$detail['pre']["pla"]) - $detail['pre']["pla"]/($detail['pre']["win"]+$detail['pre']["pla"]);
// 			$listr .= ($dispfull ? "<td >".number_format($wpdiff*100+$detail['pre']["prop"]-80) : "");
			//-----------------------------------------------------------------------------------------------------------------------------------------
// 			$firstbig3x
			$listr .= ($dispfull ? "<td $is_samewp_pre>".$isbiggest3pre.$is_samewp_pre3.$is_maxwp_pre.$is_24xontop.$detail['pre']["prop"].$is_samewin_preX.$same2last : "");
			//--
			$trainer_jockey_prop[(string)$racecard[$raceno][$horseno]['Trainer'].(string)$racecard[$raceno][$horseno]['Jockey']] .= "[".$is_samewp_pre3.$is_maxwp_pre.$firstbig3x.$detail['pre']["prop"]."</font></b>";
			$jockey_prop[(string)$racecard[$raceno][$horseno]['Jockey']][$raceno]['pre'] = $is_samewp_pre3.$is_maxwp_pre.$firstbig3x.$detail['pre']["prop"]."</font></b>";
			$jockey_prop[(string)$racecard[$raceno][$horseno]['Jockey']][$raceno]['curr'] = $is_bigger_prop_pre.$is_maxwp_curr.$detail['curr']["prop"]."</font></b>";
			$jockey_prop[(string)$racecard[$raceno][$horseno]['Jockey']][$raceno]['horseno'] = $horseno;
			$jockey_prop[(string)$racecard[$raceno][$horseno]['Jockey']][$raceno]['max_prop_curr'] = $history_array[$raceno][$horseno]["max_prop_curr"];
			$trainer_prop[(string)$racecard[$raceno][$horseno]['Trainer']]['pre'] += $detail['pre']["prop"];
			//--
			$is_bigger_prop_pre = ($detail['curr']["prop"] > $detail['pre']["prop"])   ? "<b>" : "";
			$listr .= ($dispfull ? "<td $is_samewp_curr nowrap>".$isbiggest3curr.$is_bigger_prop_pre.$is_maxwp_curr.$detail['curr']["prop"] : "");
			
			$is_bigger_prop_hist_min = ($history_array[$raceno][$horseno]["min_prop_curr"] >= $detail['pre']["prop"])   ? "<b>" : "";
			$is_bigger_prop_curr = ($history_array[$raceno][$horseno]["max_prop_curr"] > $detail['pre']["prop"])   ? "<b>" : "";

			$listr .= ($dispfull ? "<td bgcolor=lightgrey>".$is_bigger_prop_hist_min.$history_array[$raceno][$horseno]["min_prop_curr"] : "");
			$listr .= ($dispfull ? "<td bgcolor=lightgrey>".$is_bigger_prop_curr.$history_array[$raceno][$horseno]["max_prop_curr"] : "");
			//-----------------------------------------------------------------------------------------------------------------------------------------
			$prepla_todd = 100*$detail['pre']["pla"]/$oddst[$raceno]["pre"]["wptotal"];
			$currpla_todd = 100*$detail['curr']["pla"]/$oddst[$raceno]["curr"]["wptotal"];
			$is_curr_drop = ($currpla_todd > $prepla_todd) ? "<font color=grey>" : "<b>";
			$small_prepla_todd = ($tmp_prepla_todd && $prepla_todd < $tmp_prepla_todd) ? "<font color=red>" : "";
// 			$listr .= ($dispfull ? "<td bgcolor=#FFCD99>".$small_prepla_todd.number_format($prepla_todd,1) : "");
// 			$listr .= ($dispfull ? "<td bgcolor=#FFCD99>".$is_curr_drop.number_format($currpla_todd,1) : "");

            //------------------------------------------------
            if(substr($venue,0,2)=="S1" || $venue == "S2" || $venue == "S3" || $venue == "S4" || $venue == "S5" || $venue == "S6"){
            }else{
                $listr .= "<td nowrap>".$horseinfo_hints[$raceno][$horseno]['trackpla'];
                $trackpla_parts = array();
                $listr .= "<td nowrap>";
                $jockeypla = $horseinfo_hints[$raceno][$horseno]['jockeypla'];
                if (strpos($jockeypla, '/') !== false) {
                    $jockeypla_parts = explode("/", $jockeypla);
                    if (count($jockeypla_parts) == 2 && is_numeric($jockeypla_parts[0]*1) && is_numeric($jockeypla_parts[1]*1) && $jockeypla_parts[1] != '0') {
                        $a = floatval($jockeypla_parts[0]);
                        $b = floatval($jockeypla_parts[1]);
                        $ratio = $a / $b;
                        if ($ratio > 0.8) {
                            $listr .= "<span style='color:red;'>{$jockeypla}</span>";
                        } elseif ($ratio > 0.5) {
                            $listr .= "<span style='color:blue;'>{$jockeypla}</span>";
                        } elseif ($ratio > 0.4) {
                            $listr .= "<span style='color:green;'>{$jockeypla}</span>";
                        } else {
                            $listr .= "<span style='color:lightgrey;'>{$jockeypla}</span>";
                        }
                    } else {
                        $listr .= $jockeypla;
                    }
                } else {
                    $listr .= $jockeypla;
                }
                $listr .= "</td>";
                $listr .= "<td nowrap>".$horseinfo_hints[$raceno][$horseno]['jockeysamelast'];
                $listr .= "<td nowrap>".$horseinfo_hints[$raceno][$horseno]['dist'];
                
            }
            $alertsamepos = ($racerowcnt==$horseinfo_hints[$raceno][$horseno]['ai2pos']) ? "<font color=red>" : "";
            $listr .= "<td nowrap>".$alertsamepos.$horseinfo_hints[$raceno][$horseno]['ai2pos'];
            //------------------------------------------------
        

			$save2_plahist[$racingdate][$venue][$raceno][$horseno]=$currpla_todd;

			$tmp_prepla_todd = $prepla_todd;
			if($prepla_todd >= $currpla_todd){
				$oddchange = "bgcolor=#FFCDFF";
			}else{
				$oddchange = "lightgrey";
			}
			//--
			if($dispfull){
    			$listr .= "<td nowrap $TJratio_total $TJratio_35_50>".$isnight.$isday.$TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['total_ratio1yr'];
    // 			$listr .= "<td nowrap $TJratio_total $TJratio_35_50>".$isnight.$TJratio_night1yr;
    // 			$listr .= "<td nowrap $TJratio_total $TJratio_35_50>".$isday.$TJratio_day1yr;
    			//----
    			$listr .= "<td nowrap $TJratio_total $TJratio_35_50>".$isnight.$isday.$TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['total_ratio'].$TJdorn;
    // 			$listr .= "<td nowrap $TJratio_total $TJratio_35_50>".$isnight.$TJratio_disp;
    // 			$listr .= "<td nowrap $TJratio_total $TJratio_35_50>".$isday.$TJratio_disp1;
    
    			$listr .= "<td align=right $is_samewin_pre>".$detail['pre']["win"]*1;
    			$listr .= "<td align=right><b>".$windiff.$detail['curr']["win"]*1;
    			$listr .= "<td><b>".$pladiff.$detail['curr']["pla"]*1;
    			$listr .= "<td>".$pladrop.$hl_smallerpla.$detail['pre']["pla"]*1;   
    			//-------------------------------------------------------
    // 			$isranked = (in_array(strip_tags($historyodds['pattern']), array_column($plapattern, 'pattern')) ? "*" : "");
                $patternCount = count(strip_tags($historyodds['pattern']));
                $isranked = (in_array(strip_tags($historyodds['pattern']), array_column($plapattern, 'pattern')) ? $patternCount : "");
    			$listr .= "<td nowrap>".$historyodds."</td>";
    			$listr .= "<td nowrap>".$isranked.$inoutratio."</td>";
			}
			
			$listr .= "<td nowrap>".$isbestime.$islastjockey.$isbiggest3pre.$dist."</td>"; ///////////--------------------------------------///////
			$listr .= "<td nowrap>".$windiff.$horseno."</td>"; ///////////--------------------------------------///////
			
			$listr .= "<td><b>".($resultx[$venue][$raceno][$horseno] ? $resultx[$venue][$raceno][$horseno] : " ")."</td>";//.$venue.$raceno.$horseno;
// 			$listr .= "<td><b>".($resultfull[$venue][$raceno][$horseno] ? $resultfull[$venue][$raceno][$horseno] : "");
			
			if($resultx[$venue][$raceno][$horseno] && $racecard[$raceno][$horseno]['Trainer']){
				$ispla_Trainer_Jockey[] =  (string)$racecard[$raceno][$horseno]['Trainer'].(string)$racecard[$raceno][$horseno]['Jockey'];
				$accu_result[(string)$racecard[$raceno][$horseno]['Trainer'].(string)$racecard[$raceno][$horseno]['Jockey']] .= $resultx[$venue][$raceno][$horseno];
				$accu_result[(string)$racecard[$raceno][$horseno]['Trainer']] .= $resultx[$venue][$raceno][$horseno];
				$accu_result[(string)$racecard[$raceno][$horseno]['Jockey']] .= $resultx[$venue][$raceno][$horseno];
				$accu_result[$racecard[$raceno][$horseno]['Draw']] .= $resultx[$venue][$raceno][$horseno];

				$jockey_result[(string)$racecard[$raceno][$horseno]['Jockey']][$raceno] = $resultx[$venue][$raceno][$horseno];
				$jockey_accu[(string)$racecard[$raceno][$horseno]['Jockey']] .= $resultx[$venue][$raceno][$horseno];
				$trainer_accu[(string)$racecard[$raceno][$horseno]['Trainer']] .= $resultx[$venue][$raceno][$horseno];
			}
			$Trainer_Jockey = (string)$racecard[$raceno][$horseno]['Trainer'].(string)$racecard[$raceno][$horseno]['Jockey'];
			$bold_ispla_Trainer_Jockey = in_array($Trainer_Jockey , $ispla_Trainer_Jockey)  ? "<b>" : "";
            //-----------------------------------------------------------------------------------------------------------------------------
            $pick1 = in_array($horseno, explode(",", $pick[$raceno]['pick1'])) ? " bgcolor=#99FF99 " : "";
            $pick2 = in_array($horseno, explode(",", $pick[$raceno]['pick2'])) ? " bgcolor=#99FF99 " : "";
            $pick3 = in_array($horseno, explode(",", $pick[$raceno]['pick3'])) ? " bgcolor=#99FF99 " : "";
            $pick4 = in_array($horseno, explode(",", $pick[$raceno]['pick4'])) ? " bgcolor=lightgrey " : "";
            //-----------------------------------------------------------------------------------------------------------------------------
			$checkboxname = $racecard[$raceno][$horseno]['Jockey'].$raceno;
			$terminated = $detail['curr']["prop"] ? 0 : 1;
			$listr .= "<td style='' $nosamehist $pick1 $pick4>".(!$terminated ? "<input type=\"checkbox\" name=\"test\" value=\"value1\" >" : "");
			$listr .= "<td style='' $oddchange $pick2 >".(!$terminated ? "<input type=\"checkbox\" name=\"test\" value=\"value1\" >" : "");			
			$listr .= "<td style='' $uppro $pick3 >".(!$terminated ? "<input type=\"checkbox\" name=\"$checkboxname\" value=\"value1\" onchange=\"getCheckedBoxes('$checkboxname');this.checked^=1;\" >" : "");
			$checkboxTJ = $racecard[$raceno][$horseno]['Trainer'].$jockeyshort[0];
			$listr .= "<td style='' $uppro >".(!$terminated ? "<input type=\"checkbox\" name=\"$checkboxTJ\" value=\"value1\" onchange=\"getCheckedBoxes('$checkboxTJ');this.checked^=1;\">" : "");
			
			$max_tri20 = ($history_array[$raceno][$horseno]["max_tri20"]>0 && !$tri20[$raceno][$horseno]) ? "+".$history_array[$raceno][$horseno]["max_tri20"] : "";
			$istop = in_array($horseno, $tri20[$raceno]['top']) ? "<font color=red>" : "";
			$listr .= "<td style='' ><i>".$istop.$max_tri20.$tri20[$raceno][$horseno];
			$max_ttt20 = ($history_array[$raceno][$horseno]["max_ttt20"]>0 && !$ttt20[$raceno][$horseno]) ? "+".$history_array[$raceno][$horseno]["max_ttt20"] : "";
			$istop = in_array($horseno, $tri20[$raceno]['top']) ? "<font color=red>" : "";
			$listr .= "<td style='' >".$istop.$max_ttt20.$ttt20[$raceno][$horseno];
			$max_fft20 = ($history_array[$raceno][$horseno]["max_fft20"]>0 && !$fft20[$raceno][$horseno]) ? "+".$history_array[$raceno][$horseno]["max_fft20"] : "";
			$istop = in_array($horseno, $fft20[$raceno]['top']) ? "<font color=red>" : "";
			$listr .= "<td style='' ><i>".$istop.$max_fft20.$fft20[$raceno][$horseno];
			$max_qtt20 = ($history_array[$raceno][$horseno]["max_qtt20"]>0 && !$qtt20[$raceno][$horseno]) ? "+".$history_array[$raceno][$horseno]["max_qtt20"] : "";
			$istop = in_array($horseno, $qtt20[$raceno]['top']) ? "<font color=red>" : "";
			$listr .= "<td style='' >".$istop.$max_qtt20.$qtt20[$raceno][$horseno];

// 			$listr .= ($dispfull ? "<td>".$detail['pre']["isH"] : "");
			
			$listr .= "<td>".($detail['pre']["prop"]==$odds_sorted[$raceno]["b2u_nextbig"] ? "n2b" : " ");
			$signalx[$raceno][$horseno] .= ($detail['pre']["prop"]==$odds_sorted[$raceno]["b2u_nextbig"] ? "n2b" : " ");
// 			$listr .= "<td>".$pick[$raceno][$horseno];
			$listr .= "".$pick[$raceno][$horseno];
				$pick_horsename[$raceno][$horseno] = $pick[$raceno][$horseno] ? $racecard[$raceno][$horseno]['horsename'] : "";
				// echo $pick_horsename[$raceno][$horseno]."<br>";
			//--------------------------------------
						
			$sql = "select *,count(*) as cnt from barrierresult where Horse like '".$racecard[$raceno][$horseno]['horsename']."' order by barrierday desc limit 1";
			// echo $sql."<br>";
			$searchbarrier = mysqli_query($mysqli, $sql);
			$bcommentstyle = "";
			$row = mysqli_fetch_assoc($searchbarrier);
// 			$listr .= "<td>".$row['Comment'];
// 			if(mysqli_num_rows($searchbarrier) ){
            $searchbarrier_nofrow = mysqli_fetch_object($row);
		    if($searchbarrier_nofrow->cnt){
			 //   echo 1;
					if($row['barrierday']){  
						$date = new DateTime($row['barrierday']);
						$now = new DateTime();
			
						$bcommentstyle = ($date->diff($now)->format("%a") < 14) ? "color:red;" : ""; 
					}			
				}
				// $td $trihint.
// 			$listr .= "<td nowrap style=' $bcommentstyle' bgcolor=#eeeeee>".$row['Comment'].mysqli_num_rows($searchbarrier).(mysqli_num_rows($searchbarrier) ? barriercomment($barriercomment,$row['Comment']) : "_&nbsp;");
			//.mysqli_num_rows($searchbarrier)
			$listr .= "<td nowrap style=' $bcommentstyle' bgcolor=#eeeeee>".(mysqli_num_rows($searchbarrier) ? barriercomment($barriercomment,$row['Comment']) : "_&nbsp;");
			//--------------------------------------

			
// 			list($win_odds, $pla, $lastracedate) = explode('[', $racecard[$raceno][$horseno]['lastodds']);
			$listr .= "<td nowrap>".$islessthan10day.$islastjockey.$racecard[$raceno][$horseno]['daydiff'];
			$listr .= "<td nowrap>".$islessthan10day.$islastjockey.$racecard[$raceno][$horseno]['lastodds'];
			

			
// 			$listr .= "<td nowrap $TJratio_total $TJratio_35_50>".$TJratio[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['total_ratio'].$TJdorn;
// 			$listr .= "<td nowrap $TJratio_total $TJratio_35_50>".$isnight.$TJratio_disp;
// 			$listr .= "<td nowrap $TJratio_total $TJratio_35_50>".$isday.$TJratio_disp1;

			//--------------------------------------
			if(!$dispfull){
    			$listr .= "<td align=right $is_samewin_pre>".$detail['pre']["win"]*1;
    			$listr .= "<td align=right><b>".$windiff.$detail['curr']["win"]*1;
    			$listr .= "<td><b>".$pladiff.$detail['curr']["pla"]*1;
    			$listr .= "<td>".$pladrop.$hl_smallerpla.$detail['pre']["pla"]*1;
			}
			// $listr .= "<td>";
			// $listr .= "<td>";
			// $listr .= "<td >".$detail['pre']["samewp"];
			// $listr .= "<td>".$detail['pre']["samewin"];
			// $listr .= "<td>".$detail['curr']["samewp"];
			// $listr .= "<td>".$detail['pre']["firstbig3x"]; //----------
			$listr .= "<td nowrap bgcolor=#CDFF99 $inouthistory >".$inoutalert.$racecard[$raceno][$horseno]['Draw'];//."-".$cntin."-".$cntout;
			$listr .= "<td nowrap><b>".$accu_result[$racecard[$raceno][$horseno]['Draw']];
			$listr .= "<td nowrap>".$racecard[$raceno][$horseno]['horsename'];
			$jockeyshort = explode('(', $racecard[$raceno][$horseno]['Jockey']);
			$listr .= "<td nowrap>".$TJ[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['race'];
			$listr .= "<td nowrap>".$TJ[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['F'];
			$listr .= "<td nowrap>".$TJ[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['W'];
			
			$listr .= "<td nowrap>[".$bold_ispla_Trainer_Jockey.$racecard['Trainer_Jockey'][(string)$racecard[$raceno][$horseno]['Trainer'].(string)$racecard[$raceno][$horseno]['Jockey']]."]";
			$listr .= "<td nowrap><b>".$accu_result[(string)$racecard[$raceno][$horseno]['Trainer'].(string)$racecard[$raceno][$horseno]['Jockey']];
			if($racecard[$raceno][(string)$racecard[$raceno][$horseno]['Trainer']]==2){
			    $alertsameTrainer = "<font color=blue>";
			}elseif($racecard[$raceno][(string)$racecard[$raceno][$horseno]['Trainer']]>2){
			    $alertsameTrainer = "<font color=red>";
			}else{
        		$alertsameTrainer = "";
			}
			$listr .= "<td nowrap>".$racecard['Trainer'][(string)$racecard[$raceno][$horseno]['Trainer']];
			$listr .= "<td nowrap><b>".$accu_result[(string)$racecard[$raceno][$horseno]['Trainer']];
			$listr .= "<td nowrap>".$alertsameTrainer.$racecard[$raceno][$horseno]['Trainer'];
			$listr .= "<td nowrap>".$racecard['Jockey'][(string)$racecard[$raceno][$horseno]['Jockey']];
			$listr .= "<td nowrap><b>".$accu_result[(string)$racecard[$raceno][$horseno]['Jockey']];
			$listr .= "<td nowrap>".$racecard[$raceno][$horseno]['Jockey'];			

			// $listr .= "<td nowrap>".$odds."(".$pay.")";
			// $listr .= "<td>".$racecard[$raceno][$horseno]['Sex'];
			$listr .= "<td nowrap>".$racecard[$raceno][$horseno]['Last6Runs'];
			//-----------------
			list($winodds, $pay, $runtype) = explode('[', $racecard[$raceno][$horseno]['samejockey']);
			$listr .= "<td nowrap>".$islessthan10day.$dist.$winodds;	//.$racecard[$raceno][$horseno]['Dist'].$racedist[$raceno];
			$listr .= "<td nowrap>".$dist.$racecard[$raceno][$horseno]['runningtype'];
			//-----------------
			$listr .= "<td nowrap>".$isbestime.$racecard[$raceno][$horseno]['BestTime'];
			$listr .= "<td nowrap>".$racecard[$raceno][$horseno]['SeasonStakes'];
			$listr .= "<td nowrap>".$racecard[$raceno][$horseno]['OverWt'];
			$listr .= "<td nowrap>".$racecard[$raceno][$horseno]['Gear'];
			//-------------
			
// 			$sql = "select *,count(*) as cnt from barrierresult where Horse like '".$racecard[$raceno][$horseno]['horsename']."' order by barrierday desc limit 1";
// 			// echo $sql."<br>";
// 			$searchbarrier = mysqli_query($mysqli, $sql);
// 			$bcommentstyle = "";
// 			$row = mysqli_fetch_assoc($searchbarrier);
// // 			$listr .= "<td>".$row['Comment'];
// // 			if(mysqli_num_rows($searchbarrier) ){
//             $searchbarrier_nofrow = mysqli_fetch_object($row);
// 		    if($searchbarrier_nofrow->cnt){
// 			 //   echo 1;
// 					if($row['barrierday']){  
// 						$date = new DateTime($row['barrierday']);
// 						$now = new DateTime();
			
// 						$bcommentstyle = ($date->diff($now)->format("%a") < 14) ? "color:red;" : ""; 
// 					}			
// 				}
// 				// $td $trihint.
// // 			$listr .= "<td nowrap style=' $bcommentstyle' bgcolor=#eeeeee>".$row['Comment'].mysqli_num_rows($searchbarrier).(mysqli_num_rows($searchbarrier) ? barriercomment($barriercomment,$row['Comment']) : "_&nbsp;");
// 			//.mysqli_num_rows($searchbarrier)
// 			$listr .= "<td nowrap style=' $bcommentstyle' bgcolor=#eeeeee>".(mysqli_num_rows($searchbarrier) ? barriercomment($barriercomment,$row['Comment']) : "_&nbsp;");
			$listr .= "<td nowrap style=' $bcommentstyle' bgcolor=#eeeeee>".$row['Comment'];
//-----------------------------------------

			$listr .= "<td nowrap>".Grec($racecard[$raceno][$horseno]['BrandNo']);
			// echo $racecard[$raceno][$horseno]['BrandNo'];
			// echo Grec($racecard[$raceno][$horseno]['BrandNo']);
//-----------------------------------------
			$tmpraceno = $raceno;
			$temp_pla = $detail['pre']["pla"];
		}
		$racerowcnt++;
	}
	$listr .= "</tbody></table><br>";
	
}

// print_r($save2_plahist);

//--------------------------------------------------------------------------------------------------------

// $racecard['Trainer_Jockey'][(string)$row['Trainer'].(string)$row['Jockey']]++;
// $racecard['Trainer'][(string)$row['Trainer']]++;
// $racecard['Jockey'][(string)$row['Jockey']]++;
$tablestyle1 = "style=\"border-collapse: collapse;\"";
$trainerjockey = array_unique($trainerjockey);
$trainer = array_unique($trainer);
$jockey = array_unique($jockey);
// print_r($trainer_jockey_prop);
$listr .= "<hr>";
$listr .= "<table border=1 bgcolor=lightgrey cellpadding='2' cellspacing='1' $tablestyle1>";	
$listr .= "<tr><td colspan=3>A/N/D";
arsort($racecard['Jockey']);
// foreach($jockey as $jockeyname){
foreach($racecard['Jockey'] as $jockeyname => $numJockey){
	$listr .= "<td nowrap>".$jockeyname;
	$jockeyshort = explode('(', $jockeyname);
// 	$listr .= $accu_result[$jockeyshort[0]];
	$listr .= "<font color=green><b>".$accu_result[$jockeyname];
}
// $listr .= "<td>Sub";
// print_r($racecard['Jockey']);

arsort($racecard['Trainer']);


// foreach($trainer as $trainername){
foreach($racecard['Trainer'] as $trainername => $numTrainer){
	$listr .= "<tr>";
	$listr .= "<td nowrap>".$trainername;
// 	$listr .= "<td align=right>".number_format($trainer_prop[$trainername]['pre']);
	$listr .= "<td align=right>".$racecard['Trainer'][$trainername];
	$listr .= "]".$TJcnt50[$trainername];
	$listr .= "]".$TJcnt_35_50[$trainername];
	$listr .= "<td nowrap>".($trainer_accu[$trainername] ? $trainer_accu[$trainername] : "");
// 	foreach($jockey as $jockeyname){
    foreach($racecard['Jockey'] as $jockeyname => $numJockey){
		$listr .= "<td>".$trainer_jockey_prop[$trainername.$jockeyname];
		$listr .= "</font>";
		if($trainer_jockey_prop[$trainername.$jockeyname]){
    		$jockeyshort = explode('(', $jockeyname);
    		$checkboxTJ = $trainername.$jockeyshort[0];
    // 		$listr .=  $trainername.$jockeyshort[0];
    		$listr .= ($trainername && $jockeyshort[0]) ? "<input type=\"checkbox\" id=\"$checkboxTJ\" name=\"$checkboxTJ\" value=\"value1\" onchange=\"getCheckedBoxes('$checkboxTJ');this.checked^=1;\">" : "";
    		$listr .= "<font color=green><b>".$accu_result[$trainername.$jockeyshort[0]]."</b></font>";
    		
    		//-------------------
        		
    			$tjcss = ($TJratio[$trainername][$jockeyname[0]]['night_ratio']>=50) ? "<font color=red>" : "";
    			$TJratio_disp = (isset($TJratio[$trainername][$jockeyshort[0]]['night_ratio']))
    								? $tjcss.$TJratio[$trainername][$jockeyshort[0]]['night_ratio']
    								: "-";
    			$TJratio_disp .= "</font>"; //[".$TJratio[$trainername][$jockeyshort[0]]['night_racecnt']."]";
    			$TJratio_disp .= "/";
    			$tjcss = ($TJratio[$trainername][$jockeyshort[0]]['day_ratio']>=50) ? "<font color=red>" : "";
    			$TJratio_disp .= (isset($TJratio[$trainername][$jockeyshort[0]]['day_ratio']))
    								? $tjcss.$TJratio[$trainername][$jockeyshort[0]]['day_ratio']
    								: "-";
    			$TJratio_disp .= "</font>"; //[".$TJratio[$trainername][$jockeyshort[0]]['day_racecnt']."]";
    			
    			$TJratio_total = ($TJratio[$trainername][$jockeyshort[0]]['total_ratio']>=50) ? "<b>" : "";
    		//---
    		
    		$listr .= "</font><br>".$TJratio_total.($TJratio[$trainername][$jockeyshort[0]]['total_ratio'] ? $TJratio[$trainername][$jockeyshort[0]]['total_ratio'] : '-')."/".$TJratio_disp;
		}
	}	
}
$listr .= "</table>";
//------------------------
$listr .= "<hr>";
$listr .= "<table border=1 bgcolor=lightgrey cellpadding='2' cellspacing='1' $tablestyle1>";	
$listr .= "<tr><td>.";
// foreach($jockey as $jockeyname){
foreach($racecard['Jockey'] as $jockeyname => $numJockey){
	$listr .= "<td colspan=4 nowrap>".$jockeyname;
}
foreach($odds_sorted as $raceno => $val){
	$listr .= "<tr>";
	$listr .= "<td>".$raceno;
// 	foreach($jockey as $jockeyname){
    foreach($racecard['Jockey'] as $jockeyname => $numJockey){
		$checkboxname = $jockeyname.$raceno;
		$listr .= "<td><font color=gray>".$jockey_prop[$jockeyname][$raceno]['horseno'];
		$listr .= "<td>".($jockey_prop[$jockeyname][$raceno]['curr'] ? $jockey_prop[$jockeyname][$raceno]['pre'].">".$jockey_prop[$jockeyname][$raceno]['curr'].".".$jockey_prop[$jockeyname][$raceno]['max_prop_curr'] : $jockey_prop[$jockeyname][$raceno]['pre']);
		$listr .= "<td style='' >".($jockey_prop[$jockeyname][$raceno]['horseno'] ? "<input type=\"checkbox\" id=\"$checkboxname\" name=\"$checkboxname\" value=\"value1\" onchange=\"getCheckedBoxes('$checkboxname');this.checked^=1;\">" : "");
		$listr .= "<td>".($jockey_result[$jockeyname][$raceno] ? "[".$jockey_result[$jockeyname][$raceno]."]" : "&nbsp;");
	}
}
$listr .= "</table>";
//------------------------
$listr .= "<hr>";
$listr .= "<table border=1 bgcolor=lightgrey cellpadding='2' cellspacing='1' $tablestyle1>";	
$listr .= "<tr><td colspan=4>.";
foreach($odds_sorted as $raceno => $val){
	$listr .= "<td colspan=4 nowrap>".$raceno;
}
// foreach($jockey as $jockeyname){
foreach($racecard['Jockey'] as $jockeyname => $numJockey){
	$listr .= "<tr>";
	$listr .= "<td nowrap>".$jockeyname;
	$listr .= "<td nowrap>".$numJockey;
	$listr .= "<td nowrap>".$uprop_countJockey[$jockeyname];
	$listr .= "<td nowrap>".($jockey_accu[$jockeyname] ? $jockey_accu[$jockeyname] : "");
	foreach($odds_sorted as $raceno => $val){
		$checkboxname = $jockeyname.$raceno;
		$listr .= "<td><font color=gray>".$jockey_prop[$jockeyname][$raceno]['horseno'];
		$listr .= "<td>".($jockey_prop[$jockeyname][$raceno]['curr'] ? $jockey_prop[$jockeyname][$raceno]['pre'].">".$jockey_prop[$jockeyname][$raceno]['curr'].".".$jockey_prop[$jockeyname][$raceno]['max_prop_curr'] : $jockey_prop[$jockeyname][$raceno]['pre']);
		$listr .= "<td style='' >".($jockey_prop[$jockeyname][$raceno]['horseno'] ? "<input type=\"checkbox\" id=\"$checkboxname\" name=\"$checkboxname\" value=\"value1\" onchange=\"getCheckedBoxes('$checkboxname');this.checked^=1;\">" : "");
		$listr .= "<td><font color=brown>".($jockey_result[$jockeyname][$raceno] ? "[".$jockey_result[$jockeyname][$raceno]."]" : "&nbsp;");
	}
}
$listr .= "</table>";

/*
$JCx = array_merge($JC,$JockeyArray);
$JCx = array_unique($JCx);

$listr .= "<table border=1 bgcolor=lightgrey cellpadding='2' cellspacing='1' $tablestyle>";	
$listr .= "<tr><td>.";
foreach($JCx as $val){
	$listr .= "<td colspan=3>".$JockeyChallenge[(string)$val]['LastOdds'].$val."<font color=red>".$JockeyChallenge[(string)$val]['TotalPoints']."</font>";
}

$jccnt = array();
foreach ($odds as $key1 => $value){
	$raceno = $key1;
	$listr .= "<tr><td>".$raceno;	//race no
	foreach($JCx as $val){
		$analysisstyle = $analysis[$raceno][(string)$val]['style'];
		$analysiscontent = $analysis[$raceno][(string)$val]['wp'] ? "[".$analysis[$raceno][(string)$val]['wprankingcnt']."] " : "&nbsp;";
		$listr .= "<td style=\"$analysisstyle\">".$analysiscontent;
		$listr .= "<td style=\"$analysisstyle\">".$analysis[$raceno][(string)$val]['wp'];
		$listr .= "<td style='color:red; font-weight: bold;'>".($analysis[$raceno][(string)$val]['result'] ? $analysis[$raceno][(string)$val]['result'] : "&nbsp;");
		$jccnt[(string)$val]['sum'] = ($analysis[$raceno][(string)$val]['wp']) ? $jccnt[(string)$val]['sum'] + ($analysis[$raceno][(string)$val]['wp']/$analysis[$raceno][(string)$val]['wprankingcnt']) : $jccnt[(string)$val]['sum'];
		if($analysis[$raceno][(string)$val]['wp']) $jccnt[(string)$val]['cnt']++;
	}
}
$listr .= "<tr><td>";
foreach($JCx as $val){
	$listr .= "<td>.<td>".($jccnt[(string)$val]['cnt'] ? number_format($jccnt[(string)$val]['sum']/$jccnt[(string)$val]['cnt'],0) : "")."<td>.";
}
$listr .= "</table><br>";
*/
echo $listr;
// return  $listr;

//-----------------

$saved_pick_query = "select count(*) as cnt from hrp_pick where racingdate='".$racingdate."' and venue='".$venue."' ";
// echo "<br><br><br><br>".$saved_pick_query ;
$saved_pick = mysqli_query($mysqli, $saved_pick_query);
$saved_pick_nofrow = mysqli_fetch_object($saved_pick);
// echo $saved_pick_nofrow->cnt;
// if($_GET['save2db']){	
if(!$saved_pick_nofrow->cnt){	
	$insert_query = "";
	$cnt = 0;
	foreach($odds as $raceno => $racearray){
		foreach($racearray as $horseno => $detail){
			if(isset($pick[$raceno][$horseno])){
				// echo $raceno."-".$horseno."-".$pick[$raceno][$horseno]."<br>";
				$insert_query .= $raceno ? "(NULL, '".$racingdate."', '".$venue."', ".$raceno.", ".$horseno.",'".$pick_horsename[$raceno][$horseno]."','".$signalx[$raceno][$horseno]."','".$pick[$raceno][$horseno]."', '".$resultfinal."', CURRENT_TIMESTAMP)," : ""; 
			}
		}
	}
	$insert_query = substr($insert_query,0,strlen($insert_query)-1).";";
	$sql = "INSERT INTO `hrp_pick` (`id`, `racingdate`, `venue`, `raceno`, `horseno`, `horsename`, `signalx`, `pick`, `result`, `rectime`) VALUES ".$insert_query;
	// echo $sql;
	if($racingdate == date("Y-m-d")) {
		mysqli_query($mysqli, $sql);
	}
	$logs .= $insert_query ? "-hrp_pick" : "";
}


if($_GET['mail'] && $racingdate == date("Y-m-d")){
	// $ipAddress = substr($_SERVER['SERVER_NAME'],-3);
	$ipAddress = substr($_SERVER['SERVER_ADDR'],-3);
	$Subject = "[horsepaper] -".$emailtitle." [AI] ".$ipAddress;	//$date;
	$Body    = $listr;
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

if($logs){
    $logs = "ai.".$logs;
	$insert = "	INSERT INTO logs
	VALUES('','$logs',now())";
	// echo $insert;
	mysqli_query($mysqli, $insert);
}

if($mm) { echo "[9]-".number_format(microtime(true) - $time_start, 2)."-"; }

?>

<script type="text/javascript" >
// function selectAll(status,name) {
// 	alert(name);
//    $('input[name=name]').each(function(){
//       $(this).prop('checked', status);
//    });
// }

function getCheckedBoxes(chkboxName) {
	// document.querySelector(`[data-name=${CSS.escape(name)}]`);
	
	var elms = document.querySelectorAll(`[name=${CSS.escape(chkboxName)}]`);
	for(var i = 0; i < elms.length; i++) {
	//  elms[i].checked^=1;
		elms[i].checked = !elms[i].checked;
	}
	// this.checked^=1;
	// this.checked^=1;
	// chkboxName.checked^=1;
	// document.getElementById(chkboxName).checked = true;
	
}

function table_sort() {
  const styleSheet = document.createElement('style')
  styleSheet.innerHTML = `
        .order-inactive span {
            visibility:hidden;
        }
        .order-inactive:hover span {
            visibility:visible;
        }
        .order-active span {
            visibility: visible;
        }
    `
  document.head.appendChild(styleSheet)

  document.querySelectorAll('th.order').forEach(th_elem => {
    let asc = true
    const span_elem = document.createElement('span')
    span_elem.style = "font-size:0.8rem; margin-left:0.5rem"
    span_elem.innerHTML = "▼"
    th_elem.appendChild(span_elem)
    th_elem.classList.add('order-inactive')

    const index = Array.from(th_elem.parentNode.children).indexOf(th_elem)
    th_elem.addEventListener('click', (e) => {
      document.querySelectorAll('th.order').forEach(elem => {
        elem.classList.remove('order-active')
        elem.classList.add('order-inactive')
      })
      th_elem.classList.remove('order-inactive')
      th_elem.classList.add('order-active')

      if (!asc) {
        th_elem.querySelector('span').innerHTML = '▲'
      } else {
        th_elem.querySelector('span').innerHTML = '▼'
      }
      const arr = Array.from(th_elem.closest("table").querySelectorAll('tbody tr'))
	//   const arr = Array.from(th_elem.closest("table").querySelectorAll('tbody tr')).slice(0)
      arr.sort((a, b) => {
        const a_val = (a.children[index].innerText)
        const b_val = (b.children[index].innerText)
		if (asc) {
			return parseFloat(a_val) - parseFloat(b_val); // sort by number
		}else{
			return parseFloat(b_val) - parseFloat(a_val); // sort by number
		}
		
		// return a_val > b_val ? 1 : -1; // sort by string
        // return (asc) ? a_val.localeCompare(b_val) : b_val.localeCompare(a_val)
      })
      arr.forEach(elem => {
        th_elem.closest("table").querySelector("tbody").appendChild(elem)
      })
      asc = !asc
    })
  })
}

table_sort();
</script>
<!-- <script src="lib/w3.js"></script> -->
<script src="https://www.w3schools.com/lib/w3.js"></script>
<STYLE type="text/css" media="all">
   BODY, FIELDSET, MARQUEE, TEXTAREA, TD, TR, SELECT, input {
	font-family: Calibri,Tahoma, Verdana, sans-serif;
	font-size: 15px;
	font-weight: normal;
	text-decoration: none;
}
img { border: 0 solid; }


a
{
    text-decoration: none;
}    

.qpmatrix { display: none;}
</STYLE>