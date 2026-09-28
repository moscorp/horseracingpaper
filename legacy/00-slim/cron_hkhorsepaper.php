<?php
header('Content-Type: text/plain; charset=UTF-8');
//$type="winplaodds";	//"winplaoddspre";
//$date="2020-03-14";
//$venue="ST";
//$start="1";
//$end="10";
//if($date <> date("Y-m-d") exit;
// probility=1/winx0.82
// hr=1000lb   ass<128lb
// 1650m=weighter is better(133), 1000m=lighter is better(118), 1200-=random

$history_prop = array(
array('7-3-2-2' =>	'0-1-3-4'),
array('3-5-4-2' =>	'3-0-4-0'),
array('5-4-2-3' =>	'1-3-2-0'),
array('5-3-5-3' =>	'2-4-0-0'),
array('4-5-2-4' =>	'1-3-0-0'),
array('8-4-3-2' =>	'1-0-0-0'),
array('6-2-2-3' =>	'1-3-0-4'),
array('4-5-4-3' =>	'4-1-3-2'),
array('4-3-3-4' =>	'2-0-0-0'),
array('4-4-5-2' =>	'0-0-2-0'),
array('5-3-3-3' =>	'0-0-4-1'),
array('3-4-5-3' =>	'1-4-2-0'),
array('4-4-3-6' =>	'3-1-0-0'),
array('5-3-2-3' =>	'0-3-0-0'),
array('6-3-4-1' =>	'1-3-0-0'),
array('4-4-3-3' =>	'2-1-3-4'),
array('5-4-4-2' =>	'0-3-0-0'),
array('4-3-4-2' =>	'2-0-0-0'),
array('3-12-12-9' =>	'1-0-2-0'),
array('4-3-3-5' =>	'3-2-0-1'),
array('4-5-3-2' =>	'0-2-0-1'),
array('4-4-4-3' =>	'0-0-1-0'),
array('4-3-3-3' =>	'2-4-1-0'),
array('4-3-3-2' =>	'0-1-4-0'),
array('5-5-1-3' =>	'1-4-0-3'),
array('3-3-2-3' =>	'3-0-4-2'),
array('5-3-4-3' =>	'1-0-2-4'),
array('5-3-3-4' =>	'2-0-0-1'),
array('5-2-4-4' =>	'4-2-1-3'),
array('6-3-3-4' =>	'4-0-3-2'),
array('5-3-3-3' =>	'2-3-0-1'),
array('8-2-2-1' =>	'1-0-0-0'),
array('5-3-3-2' =>	'0-4-1-0'),
array('5-4-3-3' =>	'1-2-0-0'),
array('4-5-3-2' =>	'3-2-4-0'),
array('5-3-3-5' =>	'0-2-1-0'),
array('4-4-4-3' =>	'2-1-3-0'),
array('5-3-4-4' =>	'0-1-0-3'),
array('4-4-3-3' =>	'1-0-2-4'),
array('3-3-3-4' =>	'1-0-3-0'),
array('4-3-3-3' =>	'0-0-1-0'),
array('5-3-2-3' =>	'0-1-0-2'),
array('3-5-4-4' =>	'0-2-3-0'),
array('4-4-3-3' =>	'3-0-1-4'),
array('5-2-2-3' =>	'1-2-0-3'),
array('7-3-2-2' =>	'3-0-1-0'),
array('4-5-3-2' =>	'4-1-3-2'),
array('5-3-2-2' =>	'1-3-2-0'),
array('4-3-4-2' =>	'3-0-0-1'),
array('5-3-4-3' =>	'2-4-3-0'),
array('5-5-3-1' =>	'3-0-0-2'),
array('5-4-3-2' =>	'3-1-0-2'),
array('6-2-2-2' =>	'1-2-0-3'),
array('4-4-3-2' =>	'2-4-0-0'),
array('3-13-9-7' =>	'3-1-0-4'),
array('4-5-4-4' =>	'0-2-0-0'),
array('5-3-3-2' =>	'2-3-1-0'),
array('6-3-3-3' =>	'3-0-1-0'),
array('4-3-2-3' =>	'0-0-0-1'),
array('3-4-3-3' =>	'1-0-3-0'),
array('4-4-3-3' =>	'4-1-2-0'),
array('4-3-2-4' =>	'1-2-0-0'),
array('3-10-7-9' =>	'0-0-1-0'),
array('3-4-4-3' =>	'1-0-0-0'),
array('4-3-3-3' =>	'0-0-2-0'),
array('3-5-2-2' =>	'0-0-0-2'),
array('4-3-2-5' =>	'0-3-1-4'),
array('7-3-3-3' =>	'3-1-0-2'),
array('4-4-3-3' =>	'0-1-0-0'),
array('4-3-4-4' =>	'3-2-1-0'),
array('5-3-3-2' =>	'1-3-0-4'),
array('4-3-4-3' =>	'0-2-3-0'),
array('5-3-4-3' =>	'3-0-0-0'),
array('5-3-3-4' =>	'1-3-0-2'),
array('5-3-3-4' =>	'1-3-0-2'),
array('4-4-4-2' =>	'1-0-3-0'),
array('7-3-3-3' =>	'1-0-0-0'),
array('5-3-4-3' =>	'4-1-0-4'), 
array('3-4-4-3' =>	'0-2-0-4'),
array('5-4-2-1' =>	'4-1-3-0'),
array('5-5-4-4' =>	'3-0-0-2'),
array('5-4-2-3' =>	'4-3-0-2'),
array('3-6-3-4' =>	'3-0-1-0'),
array('5-3-3-4' =>	'4-0-0-0'),
array('10-3-1-2' =>	'0-0-1-3'),
array('7-5-4-3' =>	'1-2-0-3'),
array('4-5-5-3' =>	'0-3-1-0'),
array('4-5-3-2' =>	'0-3-4-1'),
array('6-4-4-2' =>	'0-1-3-0'),
array('6-3-4-3' =>	'2-0-0-1'),
array('4-4-3-2' =>	'1-2-0-3'),
array('4-4-3-3' =>	'1-3-0-2'),
array('5-3-4-3' =>	'0-2-3-0'),
array('2-5-3-5' =>	'3-0-2-4'),
array('6-2-2-2' =>	'1-4-2-0'),
array('5-5-3-3' =>	'0-2-4-0'),
array('4-3-4-2' =>	'0-4-1-2'),
array('5-3-3-2' =>	'2-0-1-3'),
array('5-3-2-2' =>	'0-2-4-3'),
array('5-4-3-3' =>	'3-0-0-0'),
array('5-5-2-2' =>	'2-1-0-0'),
array('3-5-3-4' =>	'1-3-0-0'),
array('6-2-3-4' =>	'1-4-2-0'),
array('3-5-4-2' =>	'0-3-2-0'),
array('4-4-3-3' =>	'1-3-4-0'),
array('5-3-3-2' =>	'3-1-0-0'),
array('5-7-3-2' =>	'1-0-0-0'),
array('4-3-5-3' =>	'1-2-3-0'),
array('10-1-1-2' =>	'2-4-0-0'),
array('5-4-3-3' =>	'3-2-4-0'),
array('5-3-3-2' =>	'1-0-3-4'),
array('3-7-4-2' =>	'4-3-0-0'),
array('5-3-4-3' =>	'1-0-0-3'),
array('3-6-3-2' =>	'4-2-0-1'),
array('4-3-2-4' =>	'1-3-0-0'),
array('4-3-3-4' =>	'3-0-2-0'),
array('2-5-3-3' =>	'0-0-1-3'),
array('3-3-3-2' =>	'0-2-4-0'),
array('7-2-2-3' =>	'1-3-2-0'),
array('5-2-3-2' =>	'2-1-0-0'),
array('7-4-4-2' =>	'1-0-3-2'),
array('5-3-3-5' =>	'2-1-0-3'),
array('4-4-4-3' =>	'1-0-0-4'),
array('4-4-5-3' =>	'1-0-0-0'),
array('5-4-3-4' =>	'1-4-0-0'),
array('5-3-4-3' =>	'1-2-0-0'),
array('3-4-5-3' =>	'1-0-0-3'),
array('4-7-3-2' =>	'1-2-0-0'),
array('5-4-3-3' =>	'3-1-4-0'),
array('4-5-3-3' =>	'2-3-4-0'),
array('5-4-4-2' =>	'1-2-0-3'),
array('7-3-4-2' =>	'2-3-4-0'),
array('6-5-2-3' =>	'0-0-4-0'),
array('9-4-3-5' =>	'1-4-0-0'),
array('3-6-2-4' =>	'0-3-0-0'),
array('5-8-6-1' =>	'0-0-0-0'),
array('2-4-5-3' =>	'4-1-0-0'),
array('5-3-3-2' =>	'3-0-2-0'),
array('5-3-3-3' =>	'0-2-0-0'),
array('4-3-2-7' =>	'1-0-2-0'),
array('8-3-3-3' =>	'0-3-0-0'),
array('5-3-3-2' =>	'1-0-3-2'),
array('6-3-4-2' =>	'1-2-0-0'),
array('4-5-3-4' =>	'0-3-0-0'),
array('8-4-2-2' =>	'2-1-3-0'),
array('4-4-3-4' =>	'0-4-1-0'),
array('4-3-3-3' =>	'2-0-0-1'),
array('4-4-3-3' =>	'1-2-0-3'),
array('7-1-5-5' =>	'0-0-0-0'),
array('10-2-2-1' =>	'0-0-2-1'),
array('5-4-4-3' =>	'1-2-0-0'),
array('5-3-3-2' =>	'0-0-0-0'),
array('5-2-4-3' =>	'2-1-0-0'),
array('6-2-3-4' =>	'0-1-2-4'),
array('4-5-3-2' =>	'4-1-2-0'),
array('3-4-2-7' =>	'3-0-0-0'),
array('2-4-4-2' =>	'3-0-4-0'),
array('4-3-2-3' =>	'1-4-0-0'),
array('2-5-5-5' =>	'0-1-3-0'),
array('5-5-2-3' =>	'2-4-3-1'),
array('5-4-3-2' =>	'2-0-1-3'),
array('5-3-3-3' =>	'2-3-0-0'),
array('5-3-3-2' =>	'0-1-2-4'),
array('3-4-2-4' =>	'0-2-0-0'),
array('5-2-4-1' =>	'1-3-0-2'),
array('5-3-3-2' =>	'1-0-0-0'),
array('3-6-3-3' =>	'0-3-0-0'),
array('6-5-3-2' =>	'0-0-0-1'),
array('5-4-3-2' =>	'3-0-2-1'),
array('3-10-3-4' =>	'4-1-2-3'),
array('5-3-3-2' =>	'3-0-1-4'),
array('5-3-3-3' =>	'3-2-1-0'),
array('4-3-3-3' =>	'0-0-1-0'),
array('4-6-3-2' =>	'1-2-0-0'),
array('5-6-3-2' =>	'1-0-2-3'),
array('4-4-4-3' =>	'2-1-3-0'),
array('4-3-3-4' =>	'1-0-2-0'),
array('5-5-2-4' =>	'0-2-0-4'),
array('4-5-3-3' =>	'4-2-0-0'),
array('5-3-3-3' =>	'1-4-3-2'),
array('4-3-3-7' =>	'3-0-2-0'),
array('6-2-2-2' =>	'1-3-0-0'),
array('10-3-2-1' =>	'2-3-0-0'),
array('4-4-2-4' =>	'3-2-1-0'),
array('7-3-3-2' =>	'0-0-2-1'),
array('8-3-2-2' =>	'0-0-0-0'),
array('2-6-2-3' =>	'0-3-2-0'),
array('7-3-4-1' =>	'1-2-4-0'),
array('5-3-4-3' =>	'1-0-0-3'),
array('4-3-3-3' =>	'3-2-4-1'),
array('7-4-3-2' =>	'0-0-0-1'),
array('4-3-3-3' =>	'3-1-0-4'),
array('4-4-3-3' =>	'0-2-1-3'),
array('3-3-5-3' =>	'2-4-0-3'),
array('4-5-4-3' =>	'4-2-0-0'),
array('5-4-3-4' =>	'1-0-0-3'),
array('4-3-3-3' =>	'3-2-4-1'),
array('7-4-3-2' =>	'0-0-0-1'),
array('4-3-3-2' =>	'3-1-0-4'),
array('4-4-3-3' =>	'0-2-1-3'),
array('3-3-5-3' =>	'2-4-0-3'),
array('4-5-4-3' =>	'4-2-0-0'),
array('5-4-3-4' =>	'1-0-0-3'),
array('3-4-3-3' =>	'2-0-1-0'),
array('6-2-2-2' =>	'3-1-0-4'),
array('4-4-2-3' =>	'2-1-0-4'),
array('4-4-2-3' =>	'2-1-0-4'),
array('3-3-3-2' =>	'3-0-1-0'),
array('5-2-2-4' =>	'1-0-0-3'),
array('3-5-3-3' =>	'4-3-0-2'),
array('4-3-2-3' =>	'1-0-3-0'),
array('6-3-2-2' =>	'1-3-0-0'),
array('3-2-4-5' =>	'0-2-0-1'),
array('5-2-4-4' =>	'1-0-4-0'),
array('4-4-3-3' =>	'3-2-0-4'),
array('4-3-3-3' =>	'0-1-0-0'),
array('4-4-4-5' =>	'0-0-3-1'),
array('4-4-2-3' =>	'0-4-1-0'),
array('3-5-3-2' =>	'2-1-0-0'),
array('5-4-4-2' =>	'0-1-3-0'),
array('3-5-3-3' =>	'0-2-1-3'),
array('4-5-3-3' =>	'4-1-0-3'),
array('' =>	''),
array('' =>	''),
array('' =>	''),
array('' =>	''),
array('' =>	''),
array('' =>	''),
array('' =>	''),
array('' =>	''),
array('' =>	'')
);
//---------------------------------------------------------------------------------------------
// error_reporting(E_ERROR | E_WARNING | E_PARSE);
error_reporting(E_ERROR | E_PARSE);
if(function_exists("date_default_timezone_set") AND function_exists("date_default_timezone_get")){
	// Set the default timezone to use. Available as of PHP 5.1
	// @date_default_timezone_set(@date_default_timezone_get()); // auto get server's timezone
	@date_default_timezone_set('Asia/Hong_Kong');
}
// require_once("../lib/class.phpmailer.php");
require_once("lib/func_mailer_gmail.php");

ini_set("allow_url_fopen", 1);
header('Content-Type: text/html; charset=utf-8');

$mysqli = mysqli_connect('localhost', 'melvin_melvin', 'mopass', 'moosay');
$mail= $_GET['mail'] ? $_GET['mail'] : 0;
$raceno=1;
//---------------------------------------------------------------------------------------------
function getClosest($search, $arr) {
	$closest = null;
	foreach ($arr as $item) {
	   if ($closest === null || abs($search - $closest) > abs($item - $search)) {
		  $closest = $item;
	   }
	}
	return $closest;
 }

function postRequest($url, $data, $refer = "", $timeout = 10, $header = [])
{
    $curlObj = curl_init();
    $ssl = stripos($url,'https://') === 0 ? true : false;
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_AUTOREFERER => 1,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)',
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_0,
        CURLOPT_HTTPHEADER => ['Expect:'],
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_REFERER => $refer
    ];
    if (!empty($header)) {
        $options[CURLOPT_HTTPHEADER] = $header;
    }
    if ($refer) {
        $options[CURLOPT_REFERER] = $refer;
    }
    if ($ssl) {
        //support https
        $options[CURLOPT_SSL_VERIFYHOST] = false;
        $options[CURLOPT_SSL_VERIFYPEER] = false;
    }
    curl_setopt_array($curlObj, $options);
    $returnData = curl_exec($curlObj);
    if (curl_errno($curlObj)) {
        //error message
        $returnData = curl_error($curlObj);
    }
    curl_close($curlObj);
    return $returnData;
}

function removeBOM($data) {
    if (0 === strpos(bin2hex($data), 'efbbbf')) {
       return substr($data, 3);
    }
    return $data;
}
function count_filtered_array ($array,$value){
	$cnt=0;
	$len=count($array);
	for($i=0;$i<$len;$i++)
		 if($array[$i]==$value) { $cnt++; }
	return $cnt;
}
function multiexplode ($delimiters,$string) {
    $ready = str_replace($delimiters, $delimiters[0], $string);
    $launch = explode($delimiters[0], $ready);
    return  $launch;
}
function array_icount_values($arr,$value) {
	$cnt=0;
    foreach ($arr as $values) {
        if ($values == $value) {
            $cnt++;
        }
    }
    return $cnt;
}

//racing date detail---------------------------------------------------------------------------------------------
include_once('simplehtmldom_1_9_1/simple_html_dom.php');
function multiexplode2 ($delimiters,$string) {
    $ary = explode($delimiters[0],$string);
    array_shift($delimiters);
    if($delimiters != NULL) {
        foreach($ary as $key => $val) {
             $ary[$key] = multiexplode2($delimiters, $val);
        }
    }
    return  $ary;
}

$url ="https://bet.hkjc.com/racing/script/rsdata.js?lang=ch&date=*&venue=*";
$html = file_get_html($url);

$racingdaydata = multiexplode2(array("var","="),$html) ;
$newArray = array();
foreach($racingdaydata as $val) {
	if($val[0]){
		$val1 = str_replace("'","",$val[1]);
		$newArray += [$val[0] => $val1];
	}
}

$mtgDate="";
foreach($racingdaydata as $key) {
	if(str_replace(' ', '',$key[0])=="mtgDate"){ $mtgDate = str_replace(array("'",";"," "),"",$key[1]); }
	if(str_replace(' ', '',$key[0])=="mtgVenue"){ $mtgVenue = str_replace(array("'",";"," "),"",$key[1]); }
	if(str_replace(' ', '',$key[0])=="mtgTotalRace"){ $mtgTotalRace = str_replace(array("'",";"," "),"",$key[1]); }
	if(str_replace(' ', '',$key[0])=="venueLong"){ $venueLong = str_replace(array("'",";"," "),"",$key[1]); }
	 
}
//print_r ($newArray);
//echo 	"<br>".$mtgDate;
//echo 	"<br>".$mtgVenue;
//echo 	"<br>".$mtgTotalRace;
$racingdate=$mtgDate;
$venue=$mtgVenue;
$venueLong=$venueLong;
$start="1";
$end=$mtgTotalRace;
if($racingdate <> date("Y-m-d")) exit;

    // clean up memory
$html->clear();
unset($html);

//horse detail---------------------------------------------------------------------------------------------
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
			// echo $WIN."<br>";
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

//if empty -------------------------------------------------
if(1){
	$racedetail =array();
	$JockeyCode_cnt =array();
	$TrainerCode_cnt =array();
	$TrainerJockey_cnt =array();
	$JC = array();
	$R='';
	foreach($xml->Meetings->MeetingInfo->Races as $Record){
		// if($xml->Meetings->MeetingInfo-> == $venue)
		foreach($Record as $rec){
			$R = $rec->attributes()->{'RaceNo'}*1;
			foreach($rec->Starters as $key2){		
			// $SEL='';
				foreach($key2 as $key1){			
					$SEL = $key1->attributes()->{'Number'}*1;				
					foreach($key1 as $key){
						$JockeyCode = $key->attributes()->{'JockeyCode'};
						$JockeyName = $key->attributes()->{'Jockey'};
						
						$TrainerCode = $key->attributes()->{'TrainerCode'};
						$TrainerName = $key->attributes()->{'Trainer'};
						$Trainer_Jockey = $key->attributes()->{'Trainer'}.$key->attributes()->{'Jockey'};
						$OddsInRange = $key->attributes()->{'OddsInRange'};

						$racedetail[$R][$SEL]['JockeyCode'] = $JockeyCode;
						$racedetail[$R][$SEL]['JockeyName'] = $JockeyName;
						$racedetail[$R][$SEL]['Trainer_Jockey'] = $Trainer_Jockey;
						$racedetail[$R][$SEL]['TrainerName'] = $TrainerName;
						$racedetail[$R][$SEL]['TrainerCode'] = $TrainerCode;
						$racedetail[$R][$SEL]['OddsInRange'] = $OddsInRange;
						$racedetail[$R][$SEL]['HandicapWeight'] = $key->attributes()->{'HandicapWeight'};

						array_push($JC,$JockeyName);
					}

				}
			}
			foreach($rec->Pools as $key2){	
				foreach($key2 as $key1){						
					if($key1->attributes()->{'Pool'} == "WIN"){
						foreach($key1 as $key0){	
							foreach($key0 as $key){
								$WIN = $key->attributes()->{'Odds'};
								$racedetail[$R][$SEL]['WIN'] = $WIN;
								// echo $WIN;
							}
						}
					}
					if($key1->attributes()->{'Pool'} == "PLA"){
						foreach($key1 as $key0){	
							foreach($key0 as $key){
								$PLA = $key->attributes()->{'Odds'};
								$racedetail[$R][$SEL]['PLA'] = $PLA;
								// echo $PLA;
							}
						}
					}
				}
			}//---------------
		}
	}
}
// }//----------------------------------------------------------------------------------------------------------
// print_r($JC);
// print_r($TrainerCode_cnt);

$raceresult =array();
$runnerinfo =array();
$JockeyArray = array();
$tri = $tritemp = array();
//--------------------------------------------------------------------------------------------------------------------------
$JockeyChallenge = $JC = array();
if($xml->Meetings->MeetingInfo->JTCInfo->JockeysData){
	foreach($xml->Meetings->MeetingInfo->JTCInfo->JockeysData as $Record){
		foreach($Record as $key){
			array_push($JC,$key->attributes()->{'Name'});
		}
	}
}
// print_r($JC);

//JockeyChallenge
$cnt=1;
foreach($xml->Meetings->MeetingInfo->JKCInfo as $Record){
	foreach($Record->OddsSet as $rec){
		foreach($rec as $key){
			$Name = $key->attributes()->{'Name'};
			$Value = $key->attributes()->{'Value'};
			$Opening = $key->attributes()->{'Opening'};
			$LastOdds = $key->attributes()->{'LastOdds'};
			$DisplayOrder = $key->attributes()->{'DisplayOrder'};
			$TotalPoints = $key->attributes()->{'TotalPoints'};

			$JockeyChallenge[(string)$Name]['Name'] = $Name;
			$JockeyChallenge[(string)$Name]['Value'] = $Value;
			$JockeyChallenge[(string)$Name]['Opening'] = $Opening;
			$JockeyChallenge[(string)$Name]['LastOdds'] = $LastOdds;
			$JockeyChallenge[(string)$Name]['DisplayOrder'] = $DisplayOrder;
			$JockeyChallenge[(string)$Name]['TotalPoints'] = $TotalPoints;
			// array_push($JC,$Name);
		}
		$cnt++;
	}
}
// print_r($JockeyChallenge);
// TRI top20
$raceno=1;
foreach($xml->Meetings->MeetingInfo->Races->RaceInfo as $Record){
    foreach($Record->attributes() as $a => $b) {
        if($a=='RaceNo'){ 
            $raceno = $b*1; 
        }
        $a = str_replace(' ','',$a);
        $raceresult[$raceno][$a]= str_replace('"','',$b);
	}
	// $runnerinfo[$raceno]['PostTime'] = $Record->attributes()->{'PostTime'};
	// $runnerinfo[$raceno]['Distance'] = $Record->attributes()->{'Distance'};
	// $runnerinfo[$raceno]['Track'] =  $Record->attributes()->{'Track'};
 //           //each horse info
 //           //$saddleno=1;
	// $cnt=1;
    foreach($Record->Pools->PoolInfo as $TriPool){
    	if($TriPool->attributes()->{'Pool'}=="TRI" && $TriPool->attributes()->{'OddsType'}=="2") {	//TRI only
    		foreach($TriPool as $OddsSet => $OddsInfo){
				$tritemp = array();
				foreach($OddsInfo as $val){
					// $Number = explode('-', $val->attributes()->{'Number'});
					// array_merge($tri[$raceno], $Number[0],$Number[1],$Number[2]);
					// $tritemp[]  = explode('-', $val->attributes()->{'Number'});
					foreach (explode('-', $val->attributes()->{'Number'}) as $v) {
						$tritemp[] = $v;
						// echo $v;
					}
					// $tri[$raceno]=$Number[0];
					// $tri[$raceno]=$Number[1];
					// $tri[$raceno]=$Number[2];
					// $runnerinfo[$raceno][$cnt]['TRI'] =  $val->attributes()->{'Number'};
					// $cnt++;
					// print_r($tri);
					// print_r($tritemp)."-----------";
				}	
				// echo "<br>";
				// print_r($tritemp);		
				// // array_push($tri[$raceno],...$tritemp);
				// echo $raceno."<br>";
				$tri[$raceno]=$tritemp;
			}			
			// print_r($tritemp);
			// array_push($tri[$raceno],...$tritemp);
    	}
	}
}
// print_r($tri);
$racecount=1;
$tritop20count = array();
foreach($tri as $raceno){
	foreach($raceno as $cnt => $horseno){
		// echo $racecount."-".$horseno."-".$cnt."<br>";
		$tritop20count[$racecount][$horseno]++;
	}
	$racecount++;
}
// print_r($tritop20count);
// F-F top20----------------------------------------------------------------------------------------------------
$raceno=1;
foreach($xml->Meetings->MeetingInfo->Races->RaceInfo as $Record){
    foreach($Record->attributes() as $a => $b) {
        if($a=='RaceNo'){ 
            $raceno = $b*1; 
        }
        $a = str_replace(' ','',$a);
        $raceresult[$raceno][$a]= str_replace('"','',$b);
	}

    foreach($Record->Pools->PoolInfo as $TriPool){
    	if($TriPool->attributes()->{'Pool'}=="F-F" && $TriPool->attributes()->{'OddsType'}=="2") {	//TRI only
    		foreach($TriPool as $OddsSet => $OddsInfo){
				$fftemp = array();
				foreach($OddsInfo as $val){
					// $Number = explode('-', $val->attributes()->{'Number'});
					// array_merge($tri[$raceno], $Number[0],$Number[1],$Number[2]);
					// $tritemp[]  = explode('-', $val->attributes()->{'Number'});
					foreach (explode('-', $val->attributes()->{'Number'}) as $v) {
						$fftemp[] = $v;
						// echo $v;
					}
				}	
				$ff[$raceno]=$fftemp;
			}			
    	}
	}
}
// print_r($ff);
$racecount=1;
$fftop20count = array();
foreach($ff as $raceno){
	foreach($raceno as $cnt => $horseno){
		// echo $racecount."-".$horseno."-".$cnt."<br>";
		$fftop20count[$racecount][$horseno]++;
	}
	$racecount++;
}
// print_r($tritop20count);
// QTT top20----------------------------------------------------------------------------------------------------
$raceno=1;
foreach($xml->Meetings->MeetingInfo->Races->RaceInfo as $Record){
    foreach($Record->attributes() as $a => $b) {
        if($a=='RaceNo'){ 
            $raceno = $b*1; 
        }
        $a = str_replace(' ','',$a);
        $raceresult[$raceno][$a]= str_replace('"','',$b);
	}

    foreach($Record->Pools->PoolInfo as $TriPool){
    	if($TriPool->attributes()->{'Pool'}=="QTT" && $TriPool->attributes()->{'OddsType'}=="2") {	//TRI only
    		foreach($TriPool as $OddsSet => $OddsInfo){
				$qtttemp = array();
				foreach($OddsInfo as $val){
					// $Number = explode('-', $val->attributes()->{'Number'});
					// array_merge($tri[$raceno], $Number[0],$Number[1],$Number[2]);
					// $tritemp[]  = explode('-', $val->attributes()->{'Number'});
					foreach (explode('-', $val->attributes()->{'Number'}) as $v) {
						$qtttemp[] = $v;
						// echo $v;
					}
				}	
				$qtt[$raceno]=$qtttemp;
			}			
    	}
	}
}
// print_r($ff);
$racecount=1;
$qtttop20count = array();
foreach($qtt as $raceno){
	foreach($raceno as $cnt => $horseno){
		// echo $racecount."-".$horseno."-".$cnt."<br>";
		$qtttop20count[$racecount][$horseno]++;
	}
	$racecount++;
}
// print_r($tritop20count);


    // print_r($runnerinfo);
    
//--------------------------------------------------------------------------------------------------------------------------
// $a = new stdClass();
foreach($xml->Meetings->MeetingInfo->Races->RaceInfo as $Record){
    $raceno=1;
    foreach($Record->attributes() as $a => $b) {
        if($a=='RaceNo'){ 
            $raceno = $b*1; 

        }
        $a = str_replace(' ','',$a);
        $raceresult[$raceno][$a]= str_replace('"','',$b);
	}
	$runnerinfo[$raceno]['PostTime'] = $Record->attributes()->{'PostTime'};
	$runnerinfo[$raceno]['Distance'] = $Record->attributes()->{'Distance'};
	$runnerinfo[$raceno]['Track'] =  $Record->attributes()->{'Track'};
	$runnerinfo[$raceno]['Classes'] =  $Record->attributes()->{'Classes'};
	$runnerinfo[$raceno]['Course'] =  $Record->attributes()->{'Course'};
	$runnerinfo[$raceno]['RaceName'] =  $Record->attributes()->{'RaceName'};

            //each horse info
            //$saddleno=1;
            foreach($Record->Starters as $starters){
                // echo $raceno."--".$saddleno."--".$starters->Starter->Runner->attributes()->{'Name'}."\n";
                foreach($starters as $key => $val){
                    $saddleno = $val->attributes()->{'Number'}*1;
                    // echo $raceno."--".$saddleno."--".$val->Runner->attributes()->{'Name'}."\n";
					$runnerinfo[$raceno][$saddleno]['Name'] = $val->Runner->attributes()->{'Name'};
					$runnerinfo[$raceno][$saddleno]['Draw'] = $val->Runner->attributes()->{'Draw'};
					$runnerinfo[$raceno][$saddleno]['WeightAllowance'] = $val->Runner->attributes()->{'WeightAllowance'};
					$runnerinfo[$raceno][$saddleno]['RatingRange'] = $val->Runner->attributes()->{'RatingRange'};
                    $runnerinfo[$raceno][$saddleno]['Trainer'] = $val->Runner->attributes()->{'Trainer'};
					$runnerinfo[$raceno][$saddleno]['Jockey'] = $val->Runner->attributes()->{'Jockey'};
					$runnerinfo[$raceno][$saddleno]['Trainer_Jockey'] = $val->Runner->attributes()->{'Trainer'}.$val->Runner->attributes()->{'Jockey'};
                    $runnerinfo[$raceno][$saddleno]['LastSixRuns'] = $val->Runner->attributes()->{'LastSixRuns'};
					$runnerinfo[$raceno][$saddleno]['BestTime'] = $val->Runner->attributes()->{'BestTime'};
					$runnerinfo[$raceno][$saddleno]['BestTime_val'] = $val->Runner->attributes()->{'BestTime'} ? str_replace('.', '',$val->Runner->attributes()->{'BestTime'}) : 0;
					$runnerinfo[$raceno][$saddleno]['StakesWon'] = $val->Runner->attributes()->{'StakesWon'};  
					$runnerinfo[$raceno][$saddleno]['TrumpCard'] = ($val->Runner->attributes()->{'TrumpCard'} <>'-' ? $val->Runner->attributes()->{'TrumpCard'} : '');  
					
					// $counter[$val->Runner->attributes()->{'Trainer'}]++;
					// $counter[$runnerinfo[$raceno][$saddleno]['Jockey']]++;   
					array_push($JockeyArray,$val->Runner->attributes()->{'Jockey'});

					$JockeyCode_cnt[(string)$runnerinfo[$raceno][$saddleno]['Jockey']]++;
					$TrainerCode_cnt[(string)$runnerinfo[$raceno][$saddleno]['Trainer']]++;
					$TrainerJockey_cnt[(string)$runnerinfo[$raceno][$saddleno]['Trainer_Jockey']]++;
                }
            }
			//------------------------
}
$JockeyArray = array_unique($JockeyArray);
// print_r($JockeyArray);


// $url ="https://bet.hkjc.com/racing/script/rsdata.js?lang=ch&date=*&venue=*";
// $html = file_get_html($url);

// $racingdaydata = multiexplode2(array("var","="),$html) ;
// $newArray = array();
// foreach($racingdaydata as $val) {
// 	if($val[0]){
// 		$val1 = str_replace("'","",$val[1]);
// 		$newArray += [$val[0] => $val1];
// 	}
// }

// $mtgDate="";
// foreach($racingdaydata as $key) {
// 	if(str_replace(' ', '',$key[0])=="mtgDate"){ $mtgDate = str_replace(array("'",";"," "),"",$key[1]); }
// 	if(str_replace(' ', '',$key[0])=="mtgVenue"){ $mtgVenue = str_replace(array("'",";"," "),"",$key[1]); }
// 	if(str_replace(' ', '',$key[0])=="mtgTotalRace"){ $mtgTotalRace = str_replace(array("'",";"," "),"",$key[1]); }
// 	if(str_replace(' ', '',$key[0])=="venueLong"){ $venueLong = str_replace(array("'",";"," "),"",$key[1]); }
	 
// }
// //print_r ($newArray);
// //echo 	"<br>".$mtgDate;
// //echo 	"<br>".$mtgVenue;
// //echo 	"<br>".$mtgTotalRace;
// $racingdate=$mtgDate;
// $venue=$mtgVenue;
// $venueLong=$venueLong;
// $start="1";
// $end=$mtgTotalRace;
// if($racingdate <> date("Y-m-d")) exit;

//     // clean up memory
// $html->clear();
// unset($html);
//----------------------------------------------------------------------------------------------------------------
$infourl = "https://bet.hkjc.com/racing/script/rsdata.js?lang=ch&date=2020-02-23&venue=ST";
$infourl = "https://bet.hkjc.com/racing/script/rsdata.js?lang=ch&date=2020-02-26&venue=HV";
//$info = file_get_contents($infourl);
//var_dump($info);

//$results  = utf8_encode($info);
//print $results['mtgTotalRace'];


$url = "https://bet.hkjc.com/racing/getJSON.aspx?type=winplaodds&date=2020-02-23&venue=ST&start=1&end=10";
$url = "https://bet.hkjc.com/racing/getJSON.aspx?type=winplaodds&date=2020-02-26&venue=HV&start=1&end=9";
$url = "https://bet.hkjc.com/racing/getJSON.aspx?type=winplaodds&date=2020-03-01&venue=ST&start=1&end=10";
$url = "https://bet.hkjc.com/racing/getJSON.aspx?type=winplaodds&date=2020-03-04&venue=HV&start=1&end=8";
$url = "https://bet.hkjc.com/racing/getJSON.aspx?type=winplaodds&date=2020-03-08&venue=ST&start=1&end=11";
$url = "https://bet.hkjc.com/racing/getJSON.aspx?type=winplaodds&date=2020-03-11&venue=ST&start=1&end=11";

$url = "https://bet.hkjc.com/racing/getJSON.aspx?type=winplaodds&date=".$racingdate."&venue=".$venue."&start=1&end=".$end;
$url_pre = "https://bet.hkjc.com/racing/getJSON.aspx?type=winplaoddspre&date=".$racingdate."&venue=".$venue."&start=1&end=".$end;
// echo $url."<br>";
// echo $url_pre;
//echo 'file_get_contents: ', file_get_contents($url) ? 'Enabled' : 'Disabled';

$obj = postRequest($url,[]);
//echo "<br>3/";
//var_dump(json_decode(removeBOM($obj)));
//echo json_last_error() . "\n"; // 4
//echo json_last_error_msg() . "\n"; // Syntax error, malformed JSON

$obj_pre = postRequest($url_pre,[]);
//echo "<br>4/";
//var_dump(json_decode(removeBOM($obj_pre)));
//echo json_last_error() . "\n"; // 4
//echo json_last_error_msg() . "\n"; // Syntax error, malformed JSON
/*
if(! isset($obj) ){
	$listr=($listr) ? "cf" : "f";
		$output = file_get_contents($url);
		$obj = json_decode(removeBOM($output));

		$output_pre = file_get_contents($url_pre);
		$obj_pre = json_decode(removeBOM($output_pre));

}
*/
//}while( isset($obj->{'OUT'}) );
//echo $try;
//$obj = json_decode($json);
//if(empty($obj->{'OUT'})) exit;
//var_dump($obj->{'OUT'});
//var_dump($obj_pre->{'OUT'});
//print_r($obj->OUT);
//echo $obj['OUT'];


$arr = '
{"OUT":"170318@@@WIN;1=219=0;2=5.7=3;3=2.8=1;4=18=0;5=6.8=2;6=7.3=0;7=115=0;8=22=0;9=63=0;10=24=0;11=16=0;12=7.5=0;13=73=0;14=40=0#PLA;1=47=0;2=1.7=2;3=1.3=1;4=4.4=0;5=2.9=0;6=2.3=0;7=23=0;8=4.5=0;9=13=0;10=6.8=0;11=4.3=0;12=2.5=0;13=17=0;14=9.0=0@@@WIN;1=12=2;2=16=0;3=180=0;4=11=0;5=90=0;6=7.0=0;7=5.8=0;8=21=0;9=10=2;10=11=2;11=12=2;12=100=0;13=214=0;14=3.1=1#PLA;1=4.8=0;2=4.3=0;3=47=0;4=2.6=0;5=24=0;6=2.0=0;7=2.3=0;8=4.7=0;9=3.2=2;10=4.5=0;11=4.0=0;12=27=0;13=42=0;14=1.3=1@@@WIN;1=87=0;2=204=0;3=6.6=0;4=37=0;5=49=0;6=17=0;7=6.8=0;8=4.7=2;9=145=0;10=518=0;11=12=0;12=49=0;13=55=0;14=2.2=1#PLA;1=13=0;2=32=0;3=1.5=3;4=8.9=0;5=9.1=0;6=3.3=0;7=2.3=0;8=1.7=2;9=29=0;10=57=0;11=2.5=0;12=7.6=0;13=10=0;14=1.4=1@@@WIN;1=86=0;2=8.0=0;3=41=0;4=43=0;5=2.4=1;6=108=0;7=79=0;8=4.8=0;9=13=2;10=31=0;11=7.2=0;12=32=0;13=43=0;14=10=2#PLA;1=16=0;2=2.3=0;3=8.9=0;4=10=0;5=1.5=0;6=26=0;7=20=0;8=1.5=1;9=2.8=2;10=7.3=0;11=2.3=0;12=6.7=0;13=10=0;14=2.7=2@@@WIN;1=64=0;2=9.2=0;3=2.4=1;4=3.6=0;5=255=0;6=179=0;7=228=0;8=43=0;9=4.2=0;10=102=0;11=99=0;12=92=0;13=10=2;14=164=0#PLA;1=9.2=0;2=2.2=2;3=1.3=0;4=1.3=1;5=39=0;6=23=0;7=35=0;8=7.0=0;9=1.7=0;10=14=0;11=15=0;12=13=0;13=2.1=2;14=21=0@@@WIN;1=14=0;2=9.7=3;3=190=0;4=11=0;5=5.4=0;6=20=0;7=50=0;8=107=0;9=2.2=1;10=348=0;11=250=0;12=9.1=2;13=58=2;14=10=0#PLA;1=2.8=0;2=2.2=2;3=32=0;4=2.8=0;5=2.2=0;6=6.0=0;7=8.8=0;8=21=0;9=1.2=1;10=71=0;11=46=0;12=2.9=0;13=8.4=2;14=3.1=0@@@WIN;1=2.2=1;2=13=2;3=2.8=0;4=7.3=0;5=35=0;6=122=0;7=16=0;8=17=2;9=135=0;10=45=0#PLA;1=1.4=0;2=2.5=2;3=1.0=1;4=2.1=0;5=6.5=0;6=15=2;7=2.8=0;8=3.3=0;9=19=0;10=6.7=0@@@WIN;1=241=0;2=4.9=0;3=7.2=0;4=22=2;5=15=2;6=2.4=1;7=4.8=2;8=144=0;9=SCR=0;10=11=3;11=199=0;12=21=3#PLA;1=39=0;2=1.7=0;3=2.3=0;4=3.7=2;5=3.4=0;6=1.2=1;7=1.7=0;8=22=0;9=SCR=0;10=3.1=2;11=25=0;12=5.1=2@@@WIN;1=64=0;2=9.4=3;3=5.2=2;4=15=0;5=1.9=1;6=84=0;7=155=0;8=224=0;9=10=0;10=14=2;11=150=0;12=11=0;13=34=0;14=256=0#PLA;1=14=0;2=2.7=2;3=1.7=0;4=3.1=0;5=1.2=1;6=15=0;7=23=0;8=32=0;9=2.5=0;10=3.2=2;11=24=0;12=2.5=0;13=6.2=2;14=46=0@@@WIN;1=3.3=1;2=37=0;3=19=0;4=5.8=0;5=290=0;6=6.4=2;7=125=0;8=10=2;9=18=0;10=4.1=0;11=258=0;12=13=0;13=39=0;14=375=0#PLA;1=1.5=1;2=8.6=0;3=4.7=0;4=1.6=0;5=58=0;6=2.5=0;7=24=0;8=2.8=2;9=4.6=0;10=1.7=0;11=41=0;12=3.7=0;13=7.4=0;14=69=0"}
';
//0308
$arr ='
{"OUT":"054941@@@WIN;1=2.9=1;2=13=0;3=17=0;4=86=0;5=123=0;6=17=0;7=11=0;8=57=0;9=39=0;10=3.4=0;11=78=0;12=18=0;13=9.5=0;14=15=0#PLA;1=1.5=1;2=3.4=0;3=4.4=0;4=12=0;5=19=0;6=3.9=0;7=3.3=0;8=12=0;9=8.2=0;10=1.8=0;11=13=0;12=3.4=0;13=2.3=0;14=4.0=0@@@WIN;1=13=0;2=7.8=0;3=6.4=0;4=26=0;5=12=0;6=8.7=0;7=4.9=0;8=31=0;9=10=0;10=4.8=1;11=24=0;12=21=0#PLA;1=4.7=0;2=2.2=0;3=2.4=0;4=5.9=0;5=3.1=0;6=4.3=0;7=2.0=1;8=6.6=0;9=2.5=0;10=2.4=0;11=7.0=0;12=4.0=0@@@WIN;1=5.1=0;2=13=0;3=5.5=0;4=23=0;5=6.4=0;6=30=0;7=101=0;8=47=0;9=19=0;10=34=0;11=4.2=1;12=14=0;13=9.7=0;14=108=0#PLA;1=2.0=0;2=6.0=0;3=1.7=1;4=5.9=0;5=2.3=0;6=7.2=0;7=14=0;8=7.3=0;9=5.3=0;10=7.8=0;11=2.3=0;12=3.4=0;13=3.0=0;14=14=0@@@WIN;1=3.1=0;2=27=0;3=17=0;4=3.6=0;5=2.5=1;6=13=0;7=20=0#PLA;1=1.2=1;2=4.1=0;3=2.0=0;4=1.4=0;5=1.3=0;6=3.7=0;7=2.9=0@@@WIN;1=106=0;2=17=0;3=13=0;4=11=0;5=4.2=0;6=34=0;7=11=0;8=96=0;9=13=0;10=2.6=1;11=9.2=0;12=17=0#PLA;1=15=0;2=4.7=0;3=4.3=0;4=4.6=0;5=1.7=0;6=5.7=0;7=5.3=0;8=20=0;9=3.7=0;10=1.0=1;11=2.6=0;12=4.6=0@@@WIN;1=10=0;2=49=0;3=1.8=1;4=10=0;5=81=0;6=86=0;7=16=0;8=15=0;9=11=0;10=11=0;11=213=0;12=11=0;13=67=0;14=86=0#PLA;1=2.9=0;2=12=0;3=1.0=1;4=4.0=0;5=12=0;6=13=0;7=3.9=0;8=3.4=0;9=5.0=0;10=3.1=0;11=28=0;12=2.0=0;13=9.2=0;14=15=0@@@WIN;1=23=0;2=7.2=0;3=37=0;4=5.8=0;5=20=0;6=2.2=1;7=13=0;8=17=0;9=25=0;10=16=0;11=26=0;12=14=0#PLA;1=4.7=0;2=2.4=0;3=8.2=0;4=2.0=0;5=5.8=0;6=1.1=1;7=3.5=0;8=10=0;9=6.0=0;10=3.4=0;11=4.3=0;12=3.3=0@@@WIN;1=91=0;2=20=0;3=6.4=0;4=6.6=0;5=7.7=0;6=5.4=1;7=6.3=0;8=15=0;9=134=0;10=131=0;11=71=0;12=32=0;13=15=0;14=5.4=0#PLA;1=19=0;2=5.3=0;3=2.3=0;4=2.6=0;5=2.0=0;6=1.8=1;7=2.0=0;8=5.3=0;9=27=0;10=26=0;11=8.6=0;12=11=0;13=3.0=0;14=3.7=0@@@WIN;1=3.2=1;2=74=0;3=5.4=0;4=15=0;5=20=0;6=35=0;7=7.5=0;8=15=0;9=10=0;10=50=0;11=6.3=0;12=11=0#PLA;1=2.1=1;2=6.6=0;3=2.9=0;4=3.7=0;5=5.6=0;6=6.6=0;7=2.1=0;8=3.7=0;9=2.2=0;10=8.1=0;11=2.4=0;12=2.9=0@@@WIN;1=21=0;2=4.5=0;3=3.4=1;4=13=0;5=24=0;6=70=0;7=23=0;8=42=0;9=12=0;10=11=0;11=13=0;12=5.8=0;13=33=0;14=56=0#PLA;1=5.0=0;2=2.1=0;3=1.9=1;4=4.2=0;5=5.0=0;6=12=0;7=5.5=0;8=8.0=0;9=4.3=0;10=3.1=0;11=3.1=0;12=2.0=0;13=6.4=0;14=9.3=0@@@WIN;1=3.5=0;2=22=0;3=11=0;4=36=0;5=9.1=0;6=7.8=0;7=61=0;8=11=0;9=16=0;10=112=0;11=19=0;12=3.3=1#PLA;1=3.7=0;2=5.6=0;3=10=0;4=8.7=0;5=3.7=0;6=2.6=0;7=13=0;8=3.3=0;9=3.9=0;10=19=0;11=4.6=0;12=1.0=1"}
';
$arr_pre ='
{"OUT":"054941@@@WIN;1=2.8=1;2=13=0;3=17=0;4=86=0;5=123=0;6=17=0;7=11=0;8=57=0;9=39=0;10=3.4=0;11=78=0;12=18=0;13=9.5=0;14=15=0#PLA;1=1.5=1;2=3.4=0;3=4.4=0;4=12=0;5=19=0;6=3.9=0;7=3.3=0;8=12=0;9=8.2=0;10=1.8=0;11=13=0;12=3.4=0;13=2.3=0;14=4.0=0@@@WIN;1=13=0;2=7.8=0;3=6.4=0;4=26=0;5=12=0;6=8.7=0;7=4.9=0;8=31=0;9=10=0;10=4.8=1;11=24=0;12=21=0#PLA;1=4.7=0;2=2.2=0;3=2.4=0;4=5.9=0;5=3.1=0;6=4.3=0;7=2.0=1;8=6.6=0;9=2.5=0;10=2.4=0;11=7.0=0;12=4.0=0@@@WIN;1=5.1=0;2=13=0;3=5.5=0;4=23=0;5=6.4=0;6=30=0;7=101=0;8=47=0;9=19=0;10=34=0;11=4.2=1;12=14=0;13=9.7=0;14=108=0#PLA;1=2.0=0;2=6.0=0;3=1.7=1;4=5.9=0;5=2.3=0;6=7.2=0;7=14=0;8=7.3=0;9=5.3=0;10=7.8=0;11=2.3=0;12=3.4=0;13=3.0=0;14=14=0@@@WIN;1=3.1=0;2=27=0;3=17=0;4=3.6=0;5=2.5=1;6=13=0;7=20=0#PLA;1=1.2=1;2=4.1=0;3=2.0=0;4=1.4=0;5=1.3=0;6=3.7=0;7=2.9=0@@@WIN;1=106=0;2=17=0;3=13=0;4=11=0;5=4.2=0;6=34=0;7=11=0;8=96=0;9=13=0;10=2.6=1;11=9.2=0;12=17=0#PLA;1=15=0;2=4.7=0;3=4.3=0;4=4.6=0;5=1.7=0;6=5.7=0;7=5.3=0;8=20=0;9=3.7=0;10=1.0=1;11=2.6=0;12=4.6=0@@@WIN;1=10=0;2=49=0;3=1.8=1;4=10=0;5=81=0;6=86=0;7=16=0;8=15=0;9=11=0;10=11=0;11=213=0;12=11=0;13=67=0;14=86=0#PLA;1=2.9=0;2=12=0;3=1.0=1;4=4.0=0;5=12=0;6=13=0;7=3.9=0;8=3.4=0;9=5.0=0;10=3.1=0;11=28=0;12=2.0=0;13=9.2=0;14=15=0@@@WIN;1=23=0;2=7.2=0;3=37=0;4=5.8=0;5=20=0;6=2.2=1;7=13=0;8=17=0;9=25=0;10=16=0;11=26=0;12=14=0#PLA;1=4.7=0;2=2.4=0;3=8.2=0;4=2.0=0;5=5.8=0;6=1.1=1;7=3.5=0;8=10=0;9=6.0=0;10=3.4=0;11=4.3=0;12=3.3=0@@@WIN;1=91=0;2=20=0;3=6.4=0;4=6.6=0;5=7.7=0;6=5.4=1;7=6.3=0;8=15=0;9=134=0;10=131=0;11=71=0;12=32=0;13=15=0;14=5.4=0#PLA;1=19=0;2=5.3=0;3=2.3=0;4=2.6=0;5=2.0=0;6=1.8=1;7=2.0=0;8=5.3=0;9=27=0;10=26=0;11=8.6=0;12=11=0;13=3.0=0;14=3.7=0@@@WIN;1=3.2=1;2=74=0;3=5.4=0;4=15=0;5=20=0;6=35=0;7=7.5=0;8=15=0;9=10=0;10=50=0;11=6.3=0;12=11=0#PLA;1=2.1=1;2=6.6=0;3=2.9=0;4=3.7=0;5=5.6=0;6=6.6=0;7=2.1=0;8=3.7=0;9=2.2=0;10=8.1=0;11=2.4=0;12=2.9=0@@@WIN;1=21=0;2=4.5=0;3=3.4=1;4=13=0;5=24=0;6=70=0;7=23=0;8=42=0;9=12=0;10=11=0;11=13=0;12=5.8=0;13=33=0;14=56=0#PLA;1=5.0=0;2=2.1=0;3=1.9=1;4=4.2=0;5=5.0=0;6=12=0;7=5.5=0;8=8.0=0;9=4.3=0;10=3.1=0;11=3.1=0;12=2.0=0;13=6.4=0;14=9.3=0@@@WIN;1=3.5=0;2=22=0;3=11=0;4=36=0;5=9.1=0;6=7.8=0;7=61=0;8=11=0;9=16=0;10=112=0;11=19=0;12=3.3=1#PLA;1=3.7=0;2=5.6=0;3=10=0;4=8.7=0;5=3.7=0;6=2.6=0;7=13=0;8=3.3=0;9=3.9=0;10=19=0;11=4.6=0;12=1.0=1"}
';

list($d['body'], $d['headers']) = explode(':',$obj);
list($d_pre['body'], $d_pre['headers']) = explode(':',$obj_pre);

//$exploded = (isset($obj)) ? multiexplode(array("@@@WIN;","#PLA;"),$obj->{'OUT'}) : "";
//$exploded_pre = (isset($obj_pre)) ? multiexplode(array("@@@WIN;","#PLA;"),$obj_pre->{'OUT'}) : "";
if(isset($obj_pre)) {
	$exploded_pre = (isset($obj)) ? multiexplode(array("@@@WIN;","#PLA;"),$d['headers']) : "";
	$exploded = (isset($obj_pre)) ? multiexplode(array("@@@WIN;","#PLA;"),$d_pre['headers']) : "";
}else{
	$exploded = (isset($obj)) ? multiexplode(array("@@@WIN;","#PLA;"),$d['headers']) : "";
	$exploded_pre = (isset($obj_pre)) ? multiexplode(array("@@@WIN;","#PLA;"),$d_pre['headers']) : "";
}
$arraycount = (isset($obj)) ? count($exploded) : 0;
$arraycount_pre = (isset($obj_pre)) ? count($exploded_pre) : 0;

//------------------------------------------------------------------
//$exploded = multiexplode(array("@@@WIN;","#PLA;"),$arr);
//$exploded_pre = multiexplode(array("@@@WIN;","#PLA;"),$arr_pre);
//$arraycount = count($exploded);
//$arraycount_pre = count($exploded_pre);

//------------------------------------------------------------------
// print_r($exploded)."<hr>";
// print_r($exploded_pre);
echo "<br><br>";

//echo $arraycount;

$winstr = ($arraycount>1) ? $exploded[1] : 0;
$plastr = ($arraycount>1) ? $exploded[2] : 0;

$winstr_pre = ($arraycount_pre>1) ? $exploded_pre[1] : 0;
$plastr_pre = ($arraycount_pre>1) ? $exploded_pre[2] : 0;

// print_r($winstr_pre)."<hr>";
// print_r($plastr_pre);

$odds = array();
$odds_pre = array();
$cnt=0;
$raceno=0;
$termination=0;
/*
    [0] =&gt; 113243
    [1] =&gt; 1=158=0;2=13=0;3=3.1=1;4=12=0;5=10=0;6=6.1=0;7=83=0;8=14=0;9=54=0;10=19=0;11=13=0;12=5.6=0;13=45=0;14=23=0
    [2] =&gt; 1=32=0;2=3.4=0;3=1.5=1;4=4.0=0;5=3.3=0;6=2.2=0;7=17=0;8=3.8=0;9=11=0;10=5.5=0;11=3.7=0;12=2.0=0;13=9.1=0;14=4.6=0
*/
if(1){	//if(isset($obj_pre)){
	foreach($exploded_pre as $key=>$value){
		if($cnt){			
			$racearray = explode(';', $value);
			foreach($racearray as $key=>$value){
				$arr = explode('=', $value);
				$horseno = $arr[0];
				$wp = $arr[1];	
				// $termination = ($wp=="---") ? 0 : 1;			
				if($cnt % 2 == 0){
					$odds_pre[$raceno][$horseno]["pla"] = $wp;					
					// echo $horseno."pla-".$wp."(".$cnt."(".$termination."<br>";
				}else{
					$odds_pre[$raceno][$horseno]["win"] = $wp;
					// echo $horseno."win-".$wp."(".$cnt."(".$termination."<br>";
				}
				
			}
		}					
		if($cnt % 2 == 0){
			$raceno++;
		}
		$cnt++;	
		$termination = ($wp=="---") ? 1 : 0;
		$cnt = $cnt+$termination;//
		$raceno = $raceno+$termination;//
	}
}
echo "<hr>";
// print_r($odds_pre);
$odds_prex = array();
foreach ($odds_pre as $key1 => $value){
	foreach($value as $key2 => $title){
		$odds_prex[$key1][$key2]["win"] = $title["win"];
		$odds_prex[$key1][$key2]["pla"] = $title["pla"];
		$odds_prex[$key1][$key2]["prpo"] = is_numeric($title["pla"]) ? number_format(10*$title["pla"]/$title["win"],0) : 0;
	}
}
//print_r($history);
// print_r($odds_prex);
$cnt=0;
$raceno=0;
$termination=0;
foreach($exploded as $key=>$value){
	if($cnt){
		$racearray = explode(';', $value);
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
			if($cnt % 2 == 0){
				$odds[$raceno][$horseno]["pla"] = $wp;
				// echo $horseno."pla-".$wp."(".$cnt."..(".$termination.$raceno."<br>";
			}else{
				$odds[$raceno][$horseno]["win"] = $wp;
				// echo $horseno."win-".$wp."(".$cnt."(".$termination.$raceno."<br>";
				
			}
			//echo $odds[1][$horseno]["win"]."<br>";
		}
	}
	if($cnt % 2 == 0){
		$raceno++;
	}
	$cnt++;
	$termination = ($wp=="---") ? 1 : 0;
	$cnt = $cnt+$termination;//
	$raceno = $raceno+$termination;//
}
$barriercomment = mysqli_query($mysqli, "select * from barriercomment ");
function barriercomment($barriercomment,$comment){
	$str='';	
	foreach ($barriercomment as $bc) {
		// echo $bc['comment'];
		if (strpos($comment, $bc['comment']) !== false) {
			$str .= $bc['rank'];
		}
	}
	return $str;
}
// var_dump($odds);
$tablestyle = "style=\"	
border : 1px solid #eeeeee;
border-collapse: collapse;
margin:0; padding:1px;
vertical-align: top;
#background-color: white;
background: #76b852; /* fallback for old browsers */
background: -webkit-linear-gradient(right, #76b852, #8DC26F);
background: -moz-linear-gradient(right, #76b852, #8DC26F);
background: -o-linear-gradient(right, #76b852, #8DC26F);
background: linear-gradient(to left, #76b852, #8DC26F);
font-family: 'Roboto', sans-serif;
font-size: 14px;
-webkit-font-smoothing: antialiased;
-moz-osx-font-smoothing: grayscale;    \"";

$td = "border : 1px solid #FFFFFF;";

$racingdate=$mtgDate;
$venue=$mtgVenue;
$venueLong=$venueLong;
$remain_trainer  = array();
$remain_jockey  = array();
$resultcount_Trainer= array();
$resultcount_Jockey= array();
$resultcount_TrainerJockey= array();
$analysis = array();

$emailtitle = $listr = $racingdate." [".$venueLong.$venue."]";
$listr .= "<table bgcolor=lightgrey cellpadding='0' cellspacing='0' $tablestyle>";	//<tr>
     foreach ($odds as $key1 => $value):
		 $listr .= "<tr><td style=\"text-align: left;\">";
		 //race no,name,time
		 $listr .= "<b>[ ".$key1." ] ".$runnerinfo[$key1]['Distance'].$runnerinfo[$key1]['Track']."-".$runnerinfo[$key1]['Classes']."-".$runnerinfo[$key1]['RaceName']."-".$runnerinfo[$key1]['PostTime'];	//race no 	//<tr>
		$listr .= "<table cellpadding='1' cellspacing='1' $tablestyle style=\"background-color: white;\">";
$db_raceno = $key1;
$db_Distance = $runnerinfo[$key1]['Distance'];
$db_Track = $runnerinfo[$key1]['Track'];
		asort($value);
		// print_r($value);
//probability with history------------------------------------------------
		$prop=array();
		$joinresult_pattern=array();
		$hist_prop=array();
		$pattern="";
		$cnt=0;
		$capability_pre_win_sum = $capability_pre_pla_sum = $capability_pre_wp_sum = 0;
		foreach($value as $key2 => $title){
			if(is_numeric($title["pla"]) && $cnt<4){
				$prop[$key1][$key2]["prpo"] =  (is_numeric($title['pla']) && is_numeric($title['win'])) ? number_format(10*$title["pla"]/$title["win"],0) : 0;
				//$pattern = ($cnt) ? $pattern."-".number_format(10*$title["pla"]/$title["win"],0) : number_format(10*$title["pla"]/$title["win"],0);
				$val = (is_numeric($title['pla']) && is_numeric($title['win'])) ? number_format(10*$title["pla"]/$title["win"],1) : 0;
				$pattern = ($cnt) ? $pattern."-".(substr($val,0,strrpos($val, '.', 0))) : substr($val,0,strrpos($val, '.', 0));
			}
			$cnt++;
		}
		// echo "<br>".$pattern;
		$resultpattern = mysqli_query($mysqli, "select * from raceresult_pattern ");
		if(mysqli_num_rows($resultpattern) ){
			foreach ($resultpattern as $rp) {
				
				if($rp['wpratio_pattern'] == $pattern){
					// echo $key1.">>".$rp['wpratio_pattern'].">>>".$pattern."<br>";
					list($a,$b,$c,$d) = explode('-',$rp['result_orderbyprew']);
						$hist_prop[1][$a] = isset($a) ? $hist_prop[1][$a]+1 : $hist_prop[1][$a];
						$hist_prop[2][$b] = isset($b) ? $hist_prop[2][$b]+1 : $hist_prop[2][$b];
						$hist_prop[3][$c] = isset($c) ? $hist_prop[3][$c]+1 : $hist_prop[3][$c];
						$hist_prop[4][$d] = isset($d) ? $hist_prop[4][$d]+1 : $hist_prop[4][$d];
				}
			}
		}
		//print_r($history_prop);
		/*old,,, manual input----------------------------------
		$hist_prop=array();
		foreach ($history_prop as $aValue) {
			foreach($aValue as $k => $v) {
				if($k == $pattern){
					//echo ">>".$v."<br>";
					list($a,$b,$c,$d) = explode('-',$v);
					$hist_prop[1][$a] = isset($a) ? $hist_prop[1][$a]+1 : $hist_prop[1][$a];
					$hist_prop[2][$b] = isset($b) ? $hist_prop[2][$b]+1 : $hist_prop[2][$b];
					$hist_prop[3][$c] = isset($c) ? $hist_prop[3][$c]+1 : $hist_prop[3][$c];
					$hist_prop[4][$d] = isset($d) ? $hist_prop[4][$d]+1 : $hist_prop[4][$d];
				}
			}
		}------------------------------------------------------*/

//print_r($hist_prop);
/*
array('5-5-2-3' =>	'2-4-3-1'),
array('5-5-2-3' =>	'3-2-0-1'),
*/
//------------------------------------------------
		$cnt=0;
		$temp_win=array();
		$temp_pla=array();
		$temp_pla_array=array();
		$result="";
        foreach($value as $key => $title):
			$temp_win[$cnt]= $title['win'];
			$temp_pla[$cnt]= $title['pla'];
			array_push($temp_pla_array,$title['pla']);
			$cnt++;
        endforeach;
/*best time-------------------*/
$btarray = array();
foreach ($value as $key2 => $val){
	if($runnerinfo[$key1][$key2]['BestTime_val']){
		$btarray[] = (int)$runnerinfo[$key1][$key2]['BestTime_val'];
	}
}
$bt_min = !empty($btarray) ? min($btarray) : 0;
/*bigger wp ratio-------------------*/
$wpratioarray = array();
$prex_wpratioarray = array();

foreach ($value as $key2 => $val){
	if(is_numeric($val['pla'])){
		$wpratioarray[] = (is_numeric($val['pla']) && is_numeric($val['win'])) ? number_format(100*$val['pla']/$val['win'],0) : 0;
		
		$capability_pre_win_sum = $val['win'] ? ($capability_pre_win_sum + 1/($val['win']+1)) : 0;
		$capability_pre_pla_sum = $val['win'] ? ($capability_pre_pla_sum + 1/($val['pla']+1)) : 0;
		$capability_pre_wp_sum = $val['win'] ? ($capability_pre_wp_sum + 1/($val['win']+1) + 1/($val['pla']+1)) : 0;
	}
	if(is_numeric($odds_prex[$key1][$key2]["pla"])){
		$prex_wpratioarray[] = number_format(100*$odds_prex[$key1][$key2]["pla"]/$odds_prex[$key1][$key2]["win"],0);
	}
}
// print_r($wpratioarray);
$wpratio_max = max($wpratioarray);
// echo $wpratio_max;
$prex_wpratio_max = max($prex_wpratioarray);
//prop history -------------------------------------------------------------------------
		$prop_history_array = [];
		$query = "select max(prop) as max,min(prop) as min,DATE_FORMAT(proptime,'%h') as prophr from hrp_prophist 
					where racingdate = '".$racingdate."' and raceno = '".$key1."' 
					group by DATE_FORMAT(proptime,'%h'),horseno 
					order by proptime,DATE_FORMAT(proptime,'%h') asc";
		$query = "select *,
					count(if(wpmaxhistory=1,wpmaxhistory,null)) as wpmaxhistory,
					max(prop) as max,min(prop) as min,
					avg(prop) as avg,
					sum(prop_history_avg_save) as prop_history_avg_save, 
					max(prex_wpratio_maxi_save) as prex_wpratio_maxi_save,
					/*sum(if(trihint,1,0)) as trihint,
					sum(if(trihint_c,1,0)) as trihint_c,*/
					DATE_FORMAT(proptime,'%h') as prophr from hrp_prophist 
					where racingdate = '".$racingdate."' and raceno = '".$key1."' 
					group by DATE_FORMAT(proptime,'%d'),horseno 
					order by proptime,DATE_FORMAT(proptime,'%h') asc";					
		// echo $query;
		$prop_history = mysqli_query($mysqli,$query);
		// print_r($prop_history);
		while( $row = $prop_history->fetch_array(MYSQLI_ASSOC) ){
			$prop_history_array[]=$row;
		}
		
		// print_r($prop_history_array);
////pattern selection-----------------------------------

// print_r($wpratioarray);
// $prex_wpratioarray[]
$pattern = array();
$mosratio = array();
foreach ($value as $horseno => $winpla){
	if(is_numeric($winpla['pla'])){
		$pattern[]['raceno'] = $key1;
		$pattern[]['horseno'] = $horseno;
		$pattern[]['wp'] = (is_numeric($winpla['pla']) && is_numeric($winpla['win'])) ? number_format(100*$winpla['pla']/$winpla['win'],0) : 0;		
		$mosratio[$key1][$horseno]['wp'] = (is_numeric($winpla['pla']) && is_numeric($winpla['win'])) ? number_format(100*$winpla['pla']/$winpla['win'],0) : 0;		
	}
}

foreach($value as $horseno => $wp){
	// foreach($horseno as $chksame_wp){
		$chk_samewp[$key1][$horseno] = array_count_values($mosratio[$key1][$horseno]['wp']);
		arsort($chk_samewp[$key1][$horseno]);
		// echo $key1."-".$horseno."-".$chk_samewp[$key1][$horseno]."-".$mosratio[$key1][$horseno]['wp']."<br>"; 
		// arsort($result[$k]);
		// echo $key1.$horseno.$mosratio[$key1][$horseno]['wp']."<br>"; }
	}
// print_r($chk_samewp)."<br><br><br>";
// print_r($mosratio);
// print_r($pattern);
//table start --------------------------------------------------------------------------------------------------------------------------------------------------------------------
		$cnt=1;
		$temp_wp=0;
		$temp_w=0;
		$temp_p=0;
		$bt_mini='';
		$wpratio_maxi='';
		$wprankingcnt=1;
		$mosthotwin=0;
		foreach($value as $key2 => $title):
			
			$sel_pla="";
			$hl_samepla="";
			$hl_wm25="";
			$hl_smallerpla="";
			$TrumpCard="";
			$samewp="";
			$cwin = $cpla =  $cases = "";
			$cratio = 0;
			// $capability_pre_win = $capability_pre_pla = 0;
		// echo "<br>".$pattern;
			$mosthotwin = ($cnt==1 && is_numeric($title['win'])) ? $title['win'] : $mosthotwin;

$VeterinaryRecord = mysqli_query($mysqli, "select * from VeterinaryRecord where horsename like '".$runnerinfo[$key1][$key2]['Name']."' order by chkdate desc limit 1");
$vr = mysqli_num_rows($VeterinaryRecord) ? "v" : "";			
/*pick barrier result----------------------------------------------------------------------------------*/
$searchbarrier = mysqli_query($mysqli, "select * from barrierresult where Horse like '".$runnerinfo[$key1][$key2]['Name']."' order by barrierday desc limit 1");

			$listr .= "<tr style='$td'>";	//<td style=\"background-color:#FF99FF;border : 1px solid #FFFFFF;\">".$key2;	//horse no
			$wp = (is_numeric($title['win']) && is_numeric($title['pla'])) ? number_format(100*$title['pla']/$title['win'],0) : 0;
			//hot cool signal
			$listr .= "<td>".( ($temp_wp <= $wp && $cnt>1) ? "H" : "-");	
			$temp_wp = $wp;
			$wpratio_maxi = (is_numeric($title['pla']) && $wpratio_max==$wp) ? "<font color=red>" : "";
			$wpratio_maxi_save = (is_numeric($title['pla']) && $wpratio_max==$wp) ? $wp : 0;

			$samewp = (array_icount_values($wpratioarray, $wp) >1) ? "background-color:yellow;" : "";
			$isbigger = ($cnt>1 && $wp > $db_prop) ? "<u>" : "";
			$listr .= "<td style='$td $samewp' nowrap>".$wpratio_maxi.$isbigger.($wp ? $wp : "&nbsp;");//."<".$title['win']."<".$title['pla'];		//pre wp			
//------------------------------------------------------------------------------------------------------			
$analysis[(int)$key1][(string)$runnerinfo[$key1][$key2]['Jockey']]['Jockey'] = (string)$runnerinfo[$key1][$key2]['Jockey'];
$analysis[(int)$key1][(string)$runnerinfo[$key1][$key2]['Jockey']]['raceno'] = $key1;
$analysis[(int)$key1][(string)$runnerinfo[$key1][$key2]['Jockey']]['wp'] = $wp;
$analysis[(int)$key1][(string)$runnerinfo[$key1][$key2]['Jockey']]['wprankingcnt'] = $wprankingcnt;
$analysis[(int)$key1][(string)$runnerinfo[$key1][$key2]['Jockey']]['style'] = $samewp;
// print_r($analysis);

			$prex_wpratio_maxi = (is_numeric($odds_prex[$key1][$key2]["pla"]) && $prex_wpratio_max==number_format(100*$odds_prex[$key1][$key2]["pla"]/$odds_prex[$key1][$key2]["win"],0)) ? "<font color=red>" : "";
			$prex_wpratio_maxi_save = (is_numeric($odds_prex[$key1][$key2]["pla"]) && $prex_wpratio_max==number_format(100*$odds_prex[$key1][$key2]["pla"]/$odds_prex[$key1][$key2]["win"],0)) ? $prex_wpratio_max : 0;

			$wp_pre = (is_numeric($odds_prex[$key1][$key2]["pla"]) ? number_format(100*$odds_prex[$key1][$key2]["pla"]/$odds_prex[$key1][$key2]["win"],0) : 0);
			$updntrend = ($wp_pre < $wp) ? "font-style: italic;color:grey;" : "";
			$listr .= "<td style='$td $updntrend' nowrap>".$prex_wpratio_maxi.(is_numeric($odds_prex[$key1][$key2]["pla"]) ? number_format(100*$odds_prex[$key1][$key2]["pla"]/$odds_prex[$key1][$key2]["win"],0) : "&nbsp;");
			
//racing day's prop history---------------------------------------------
			if(empty($prop_history_array)){
				$listr .= "<td><td>";
			}else{
				// print_r($prop_history_array);
				$tmp=0;
				$prop_history_avg_save=0;
				foreach ( $prop_history_array as $hr   ){		
					if($hr['racingdate']==$racingdate && $hr['raceno']==$key1 && $hr['horseno']==$key2 ){
						$updntrend = ($hr['prop'] < $wp ? "font-style: italic;color:grey;" : "");
						$listr .= "<td style='$updntrend' nowrap>".$hr['max'];
						$listr .= "<td style='$updntrend' nowrap>".$hr['min'];	
						// $listr .= "<td style='$updntrend' nowrap>".number_format($hr['avg'],0);	
						// $listr .= "<td style='$updntrend' nowrap>".$hr['prop_history_avg_save'];
						$listr .= "<td style='$updntrend' nowrap><font color=red>".($hr['prex_wpratio_maxi_save'] ? $hr['prex_wpratio_maxi_save'] : "");	
						$max = $hr['max'];
						$min = $hr['min'];
						$wpmaxhistory = $hr['wpmaxhistory'];
						$avg = number_format($hr['avg'],0);
						$prex_wpratio_maxi_save = $hr['prex_wpratio_maxi_save'];
						$trihint_saved = $hr['trihint'];
						$trihint_saved_c = $hr['trihint_c'];
						// echo $trihint_saved.$trihint_saved_c."<br>";
						$tmp++;					
					}
				}
				if(!$tmp){
					$listr .= "<td><td><td>";
				}
			}
			//-----------------------
			$prop_history_hint = ($max > $wp && $max > $wp_pre && $min < $wp && $min < $wp_pre) ? "<b><u>" : "";
			$prop_history_avg = ($avg== $wp) ? "<font color=red>" : "";
			$prop_history_avg_save = ($avg== $wp) ? 1 : 0;
			// $listr .= "<td style='$updntrend' nowrap>".$prop_history_array['max'];
			// $listr .= "<td style='$updntrend' nowrap>".$prop_history_array['min'];
			$listr .= "<td style='$updntrend' nowrap>.".($wpmaxhistory ? $wpmaxhistory : "");
/*tri big-odds ----------------------------------------------------------------------------------------------------------*/
			if(is_numeric($title['win'])){
				$trihint = ($mosthotwin>2.1 && in_array( $key2, $tri[$key1] )) ? ( ($title['win'] > 20) ? (($title['win'] > 30) ? "T3" : "T2")  : ".") : "";
				// $trihint .= in_array( $key2, $tri[$key1] ) ? "." : "";
				$trihint_c = ($mosthotwin>2.1 && in_array( $key2, $tri[$key1] )) ? ( ($odds_prex[$key1][$key2]["win"] > 20) ? (($odds_prex[$key1][$key2]["win"] > 30) ? "T3" : "T2")  : ".") : "";
			}
			$listr .= "<td nowrap>t".$tritop20count[$key1][$key2];
			$listr .= "<td nowrap>f".$fftop20count[$key1][$key2];
			$listr .= "<td nowrap>q".$qtttop20count[$key1][$key2];
			$listr .= "<td nowrap>|".($tritop20count[$key1][$key2]+$fftop20count[$key1][$key2]+$qtttop20count[$key1][$key2]);
//save prop---------------------------------------------
			// echo strtotime($runnerinfo[$key1]['PostTime']).strtotime(now)."<br>";
			if (is_numeric($odds_prex[$key1][$key2]["pla"]) && strtotime($runnerinfo[$key1]['PostTime']).strtotime(now) && $mail){	
				$wpmaxhistory = $prex_wpratio_maxi ? 1 : 0;			
				$query = "insert into hrp_prophist values ('','".$racingdate."',".$key1.",".$key2.",now(),".$wp_pre.",".$prop_history_avg_save.",".$prex_wpratio_maxi_save.",'".$trihint."','".$trihint_c."',$wpmaxhistory)";
				// echo $query;
				mysqli_query($mysqli, $query);				
			}			
//---------------------------------------------
			$listr .= "<td style=\"background-color:#FF99FF;border : 1px solid #FFFFFF;\">".$prop_history_hint.$prop_history_avg.$key2;	//horse no ------------------------------------------------
			$db_horseno = $key2;			
/*compare wp-------------------*/
			if(is_numeric($title['win']) && is_numeric($title['pla']) ){
				// echo $title['win']."---".$temp_w."<br>";
				$cwin = ($cnt>1 && $title['win'] && $temp_w) ? $title['win']/$temp_w : $title['win'];
				$cpla = ($cnt>1 && $title['pla'] && $temp_p) ? $title['pla']/$temp_p : $title['pla'];
				$cratio = $cpla/$cwin;
				$cases .= ($cnt>1 && $cratio<0.56) ? "W" : "";
				$cases .= ($cnt>1 && $cratio<0.76) ? "P" : "";
				$cases .= ($cnt>1 && $cratio<0.41) ? "W" : "";
			}
/*same win-------------------*/
			if(!empty($temp_win[$cnt]) && !empty($title['win']) && $temp_win[$cnt]==$title['win']){
				$hl_samewin = "background-color:#FFFF99;";
				if($temp_pla[$cnt]>$title['pla']){ $sel_pla = "<font color=red>"; }
			}else{
				$hl_samewin = "";
			}
			if(!empty($title['win']) && $temp_w==$title['win']){
				$hl_samewin1 = "background-color:#FFFF99;";
			}else{
				$hl_samewin1 = "";
			}
/*same pla---------------------------*/
			if(!empty($title['win']) && count_filtered_array( $temp_pla_array, $title['pla'] )>1  ){ 
				$hl_samepla = "<font color=green><b>"; 
			}
/*win more than 25%---------------------------*/
			if(is_numeric($odds_prex[$key1][$key2]["win"]) && is_numeric($title['win'])){
				if( abs(100*$odds_prex[$key1][$key2]["win"]/$title['win'])<75 ){ 
					$hl_wm25 = "<u><b>"; 
				}
			}
/*pla smaller than last---------------------------*/
			if($temp_p > $title['pla'] ){ 
				$hl_smallerpla = "<i><font color=red>"; 
			}
/*1-4 place---------------------------------------------------------------------------------------------------------------*/
			// $results = $raceresult[$key1]['Results'];
			$d = explode(',',$raceresult[$key1]['Results']);
			$result= ($key2 == $d[0]) ? 1 : (($key2 == $d[1]) ? 2 : (($key2 == $d[2]) ? 3 : (($key2 == $d[3]) ? 4 : "")));
        	// $results[$key1][2]= $d[1];
        	// $results[$key1][3]= $d[2];
			// $results[$key1][4]= $d[3];	
			$joinresult_pattern[$key1]['pattern'] =  	$joinresult_pattern[$key1]['pattern']."-".($result ? $result : '0');
				// echo $result."<br>";

			$listr .= "<td style='$td' >&nbsp;".$result;
			$listr .= "<td style='' ><input type=\"checkbox\" name=\"test\" value=\"value1\">";
			$listr .= "<td style='' ><input type=\"checkbox\" name=\"test\" value=\"value1\">";
			$listr .= "<td style='' ><input type=\"checkbox\" name=\"test\" value=\"value1\">";
$db_result = $result;
$analysis[(int)$key1][(string)$runnerinfo[$key1][$key2]['Jockey']]['result'] = $result;
// /*tri big-odds ----------------------------------------------------------------------------------------------------------*/
// 			if(is_numeric($title['win'])){
// 				$trihint = ($mosthotwin>2.1 && in_array( $key2, $tri[$key1] )) ? ( ($title['win'] > 20) ? (($title['win'] > 30) ? "T" : "t")  : "t") : "";
// 				$trihint .= in_array( $key2, $tri[$key1] ) ? "." : "";
// 			}
/*-------------------------------*/
			$bcommentstyle = "";
			$row = mysqli_fetch_assoc($searchbarrier);
			if(mysqli_num_rows($searchbarrier) ){
					if($row['barrierday']){  
						$date = new DateTime($row['barrierday']);
						$now = new DateTime();
			
						$bcommentstyle = ($date->diff($now)->format("%a") < 14) ? "color:red;" : ""; 
					}			
				}
			$listr .= "<td style='$td $bcommentstyle' bgcolor=#eeeeee>".$trihint.(mysqli_num_rows($searchbarrier) ? barriercomment($barriercomment,$row['Comment']) : "&nbsp;");
			$listr .= (($trihint_saved || $trihint_saved_c) && $title['win'] > 20) ? "<font color=red>T" : "";
			$listr .= "<td style='$td' bgcolor=#eeeeee>".$vr;
$db_barrier = (mysqli_num_rows($searchbarrier) ? barriercomment($barriercomment,$row['Comment']) : '');		
	
			if($result){
				$joinresult_pattern[$key1]['counter']++;				

				// $resultcount_Trainer[(string)$runnerinfo[$key1][$key2]['Trainer']]++;
				$resultcount_Trainer[(string)$runnerinfo[$key1][$key2]['Trainer']] = $resultcount_Trainer[(string)$runnerinfo[$key1][$key2]['Trainer']].$result;
				// $resultcount_Jockey[(string)$runnerinfo[$key1][$key2]['Jockey']]++;
				$resultcount_Jockey[(string)$runnerinfo[$key1][$key2]['Jockey']] = $resultcount_Jockey[(string)$runnerinfo[$key1][$key2]['Jockey']].$result;
				$resultcount_TrainerJockey[(string)$runnerinfo[$key1][$key2]['Trainer'].(string)$runnerinfo[$key1][$key2]['Jockey']]++;

				// (is_numeric($title['pla']) ? number_format(100*$title['pla']/$title['win'],0) : "&nbsp;");
				// $val = number_format(10*$title["pla"]/$title["win"],1);
				// $pattern = ($cnt) ? $pattern."-".(substr($val,0,strrpos($val, '.', 0))) : substr($val,0,strrpos($val, '.', 0));
			}
/*place pattern ----------------------------------------------------------------------------------------------------------*/	
// echo $key2;
// print_r($hist_prop);				
			$listr .= "<td style='$td' >".((int)$hist_prop[$cnt][1] ? (int)$hist_prop[$cnt][1] : "&nbsp;");	//($cnt==1  ? $hist_prop[1] : "&nbsp;");
			$listr .= "<td style='$td' >".((int)$hist_prop[$cnt][2] ? (int)$hist_prop[$cnt][2] : "&nbsp;");	//($cnt==2  ? $hist_prop[2] : "&nbsp;");
			$listr .= "<td style='$td' >".((int)$hist_prop[$cnt][3] ? (int)$hist_prop[$cnt][3] : "&nbsp;");	//($cnt==3  ? $hist_prop[3] : "&nbsp;");
			$listr .= "<td style='$td' >".((int)$hist_prop[$cnt][4] ? (int)$hist_prop[$cnt][4] : "&nbsp;");	//($cnt==4  ? $hist_prop[4] : "&nbsp;");
			//$listr .= "<td >".($title['win']>0 ? number_format(1/($title['win']+$title['pla'])*0.82,2) : "&nbsp;");
$db_prop1 = (int)$hist_prop[$cnt][1];
$db_prop2 = (int)$hist_prop[$cnt][2];
$db_prop3 = (int)$hist_prop[$cnt][3];
$db_prop4 = (int)$hist_prop[$cnt][4];			
//------------------------------------------------------------------------------------------------------------
$pladrop = ($odds_prex[$key1][$key2]["win"]) ?
	(($title['win']<$odds_prex[$key1][$key2]["win"] && $odds_prex[$key1][$key2]["pla"] < $title['pla']) ? "<font color=red>" : "") : "";

//------------------------------------------------------------------------------------------------------------
			$listr .= "<td style='$td' align=left nowrap>".$racedetail[$key1][$key2]['OddsInRange'];	// ABC
$sql = "select * from racingrecord_summary where horsename like '".$runnerinfo[$key1][$key2]['Name']."' and racingdate<'".$racingdate."' order by racingdate desc limit 1";	
		
mysqli_query($mysqli, "SET horsename utf8;");  //utf8mb4
$lastwin = mysqli_query($mysqli, $sql);
$lastwinrec = mysqli_fetch_assoc($lastwin);
// while( $lastwinrecs = $lastwin->fetch_array(MYSQLI_ASSOC) ){
// while ($lastwinrec = mysql_fetch_array($lastwin, MYSQL_ASSOC)) {
	// $result = $mysqli->query($query);
// while ($lastwinrecs = $lastwin->fetch_assoc()) {	
// while(null !== ($lastwinrecs = mysqli_fetch_assoc($lastwin))){
// 	$listr .= "<td bgcolor=lightgrey>".$lastwinrecs['win'];
// }
// for($i = 0; $array[$i] = mysql_fetch_assoc($lastwin); $i++) {
// 	$listr .= "<td bgcolor=lightgrey>".$array[$i];
// }
   
// Delete last empty one
// array_pop($array);
// $listr .= "<td bgcolor=lightgrey>".$sql;
			$listr .= "<td nowrap bgcolor=lightgrey>".$lastwinrec['win'].($lastwinrec['Result'] ? $lastwinrec['Result'] : "");
			$listr .= "<td nowrap style='$td $hl_samewin $hl_samewin1 border-right: 1px solid blue;'>".$title['win'];
			$listr .= "<td nowrap style='$td $hl_samewin $hl_samewin1 border: 1px solid blue;' >".$pladrop.$hl_wm25.($odds_prex[$key1][$key2]["win"] ? $odds_prex[$key1][$key2]["win"] : "&nbsp;");
			$listr .= "<td nowrap style=\"border: 1px solid blue;\">".$pladrop.($odds_prex[$key1][$key2]["pla"] ? $odds_prex[$key1][$key2]["pla"] : "&nbsp;");
			$listr .= "<td style='$td' nowrap>".$hl_samepla.$sel_pla.$hl_smallerpla.$title['pla'];
			// getClosest($search, $arr)

$db_OddsInRange = $racedetail[$key1][$key2]['OddsInRange'];
$db_prewin = $title['win'];
$db_win = $odds_prex[$key1][$key2]["win"];
$db_pla = $odds_prex[$key1][$key2]["pla"];
$db_prepla = $title['pla'];

/*trainer jockey detail-------------------------------------------------------------------------------------------------------------*/
			$TrumpCard = $runnerinfo[$key1][$key2]['TrumpCard'] ? "<font color=red>" : "";
			$db_trainer_style = $runnerinfo[$key1][$key2]['TrumpCard'] ? "color:red;" : "";			
			//Trainer counter						
			if((string)$runnerinfo[$key1][$key2]['Trainer'] == (string)$racedetail[$key1][$key2]['TrainerName']){
				$remain_trainer[(string)$runnerinfo[$key1][$key2]['Trainer']]++;
			}
			$listr .= "<td style='$td' align=left nowrap>".$TrumpCard.($runnerinfo[$key1][$key2]['Trainer'])."[".$remain_trainer[(string)$runnerinfo[$key1][$key2]['Trainer']].'/'.$TrainerCode_cnt[(string)$runnerinfo[$key1][$key2]['Trainer']]."]";
			$listr .= "<td style='$td' align=left nowrap>".$resultcount_Trainer[(string)$runnerinfo[$key1][$key2]['Trainer']];
			$db_trainer = $runnerinfo[$key1][$key2]['Trainer'];		
			$db_trainercnt = "[".$remain_trainer[(string)$runnerinfo[$key1][$key2]['Trainer']].'/'.$TrainerCode_cnt[(string)$runnerinfo[$key1][$key2]['Trainer']]."]";
			$db_trainerresultcnt = $resultcount_Trainer[(string)$runnerinfo[$key1][$key2]['Trainer']];

			//Jockey counter			
			if((string)$runnerinfo[$key1][$key2]['Jockey'] == (string)$racedetail[$key1][$key2]['JockeyName']){
				$remain_jockey[(string)$runnerinfo[$key1][$key2]['Jockey']]++;
			}
			$samejokey = ($row['Jockey'] == $runnerinfo[$key1][$key2]['Jockey']) ? "<font color=red>" : "";
			$listr .= "<td style='$td' align=left nowrap>".$samejokey.($runnerinfo[$key1][$key2]['Jockey'])."[".$remain_jockey[(string)$runnerinfo[$key1][$key2]['Jockey']].'/'.$JockeyCode_cnt[(string)$runnerinfo[$key1][$key2]['Jockey']]."]";
			$listr .= "<td style='$td' align=left nowrap>".$resultcount_Jockey[(string)$runnerinfo[$key1][$key2]['Jockey']];	 
			
			$samejockey = ((string)$racedetail[$key1][$key2]['JockeyName'] == (string)$flrow['lastjockey']) ? "color:red;" : "";
			$listr .= "<td style=\"$samejockey\" nowrap>".$fl;
			$listr .= "<td nowrap>".$flwin;
			$listr .= "<td nowrap>".$fldate;
			/***************************************************************************/				
			$db_jockey = $runnerinfo[$key1][$key2]['Jockey'];
			$db_jockeyresultcnt = $resultcount_Jockey[(string)$runnerinfo[$key1][$key2]['Jockey']];
			//Trainer+Jockey counter
			if((string)$runnerinfo[$key1][$key2]['Trainer'].(string)$runnerinfo[$key1][$key2]['Jockey'] == (string)$racedetail[$key1][$key2]['Trainer_Jockey']){
				$remain_trainerjockey[(string)$runnerinfo[$key1][$key2]['Trainer_Jockey']]++;
			}
			$listr .= "<td style='$td' align=left nowrap>"."[".$remain_trainerjockey[(string)$runnerinfo[$key1][$key2]['Trainer_Jockey']].'/'.$TrainerJockey_cnt[(string)$runnerinfo[$key1][$key2]['Trainer_Jockey']]."]";
			$listr .= "<td style='$td' align=left nowrap>".$resultcount_TrainerJockey[(string)$runnerinfo[$key1][$key2]['Trainer'].(string)$runnerinfo[$key1][$key2]['Jockey']];
			$db_trainerjockey = "[".$remain_trainerjockey[(string)$runnerinfo[$key1][$key2]['Trainer_Jockey']].'/'.$TrainerJockey_cnt[(string)$runnerinfo[$key1][$key2]['Trainer_Jockey']]."]";
			$db_trainerjockeycnt = $resultcount_TrainerJockey[(string)$runnerinfo[$key1][$key2]['Trainer'].(string)$runnerinfo[$key1][$key2]['Jockey']];
			
			$listr .= "<td style='$td' align=right nowrap>".($runnerinfo[$key1][$key2]['StakesWon'] ? number_format($runnerinfo[$key1][$key2]['StakesWon']*1,0) : "&nbsp;");
			$db_StakesWon = $runnerinfo[$key1][$key2]['StakesWon'];

//----------------------------------------------------------			
			$wpratio_maxi = (is_numeric($title['win']) && is_numeric($title['pla']) && $wpratio_max==number_format(100*$title['pla']/$title['win'],0)) ? "<font color=red>" : "";
			// $listr .= "<td $td nowrap>".$wpratio_maxi.(is_numeric($title['pla']) ? number_format(100*$title['pla']/$title['win'],0) : "&nbsp;");
			
			// capability=1/(odds+1), wincap = capability/capability_sum
			$winrate_win = $title['win'] ? (1/($title['win']+1)/$capability_pre_win_sum) : 0;
			$winrate_pla = $title['pla'] ? (1/($title['pla']+1)/$capability_pre_pla_sum) : 0;
			$winrate_wp = $title['win'] ? (1/($title['win']+$title['pla']+1)/$capability_pre_wp_sum) : 0;
			$chk_winrate_win = ($cnt>1 && $winrate_win> $temp_winrate_win) ? "<font color=red>" : "";
			$chk_winrate_pla = ($cnt>1 && $winrate_pla> $temp_winrate_pla) ? "<font color=red>" : "";
			$chk_winrate_wp = ($cnt>1 && $winrate_wp> $temp_winrate_wp) ? "<font color=red>" : "";
			$listr .= "<td $td align=right nowrap>(".$chk_winrate_win.number_format(100*$winrate_win,1);
			$listr .= "<td $td align=right nowrap>(".$chk_winrate_pla.number_format(100*$winrate_pla,1);
			$listr .= "<td $td align=right nowrap>(".$chk_winrate_wp.number_format(100*$winrate_wp,1);
			$listr .= "<td $td align=right nowrap>(".number_format(100*(1/($title['win']+1)/$capability_pre_wp_sum)*($title['win']+1)-1,0);
			$listr .= "<td $td align=right nowrap>(".number_format(100*(1/($title['win']+$title['pla']+1)/$capability_pre_wp_sum)*($title['win']+1+$title['pla']+1)-1,0);
			//------------------------------------------------------------------------------------
			$listr .= "<td $td nowrap>(".number_format($cratio,3);
			$listr .= "<td $td nowrap>".$cases;
$temp_winrate_win =  $winrate_win;
$temp_winrate_pla =  $winrate_pla;
$temp_winrate_wp =  $winrate_wp;


$db_prop = ((is_numeric($title['win']) && is_numeric($title['pla'])) ? number_format(100*$title['pla']/$title['win'],0) : "&nbsp;");

			$bt_mini = ($bt_min==$runnerinfo[$key1][$key2]['BestTime_val']) ? "<font color=red>" : "";			
			$listr .= "<td style='$td' align=right nowrap>".$bt_mini.($runnerinfo[$key1][$key2]['BestTime'] ? $runnerinfo[$key1][$key2]['BestTime'] : "&nbsp;");
			
			
			$listr .= "<td style='$td' align=right nowrap>".($runnerinfo[$key1][$key2]['LastSixRuns']);
$db_besttime = $runnerinfo[$key1][$key2]['BestTime'];
$db_besttime_style = $bt_mini;

$db_LastSixRuns = $runnerinfo[$key1][$key2]['LastSixRuns'];

//last race record*************************************************************************/
$isdrawdecrease =0;
$FormLine = mysqli_query($mysqli, "select * from FormLine where horsename like '".$runnerinfo[$key1][$key2]['Name']."' order by rectime desc limit 1");
$fl =$flwin=$fldate="";
if($FormLine && mysqli_num_rows($FormLine)==1){
	$flrow = mysqli_fetch_assoc($FormLine);				
	$fl = $flrow['lastjockey'];
	$fl .= $flrow['draw'];
	$islastdraw = $flrow['draw'] ? strtok($flrow['draw'], '(') : 0;
	$isdrawdecrease = $flrow['draw'] ? ($islastdraw > $runnerinfo[$key1][$key2]['Draw'] ? 1 : 0) : 0;

	// $fl .= "[".$flrow['placing']."]";
	$flwin = "[".$flrow['winodds']."]";
	$fldate = substr($flrow['raceday'],0,5);
}
/*race detail-------------------------------------------------------------------------------------------------------------*/
$listr .= "<td style='$td' align=left nowrap>".($runnerinfo[$key1][$key2]['Name']);
$listr .= "<td style='$td' align=left nowrap>".($isdrawdecrease ? "<font color=red>" : "").($runnerinfo[$key1][$key2]['Draw']);
$listr .= "<td style='$td' align=left nowrap>".($racedetail[$key1][$key2]['HandicapWeight']);
$listr .= "<td style='$td' align=left nowrap>".($runnerinfo[$key1][$key2]['WeightAllowance'] ? $runnerinfo[$key1][$key2]['WeightAllowance'] : "&nbsp;");
$listr .= "<td style='$td' align=left nowrap>".($runnerinfo[$key1][$key2]['RatingRange'] ? $runnerinfo[$key1][$key2]['RatingRange'] : "&nbsp;");
$db_horsename = ($runnerinfo[$key1][$key2]['Name']);
$db_draw = ($runnerinfo[$key1][$key2]['Draw']);
$db_HandicapWeight = ($racedetail[$key1][$key2]['HandicapWeight']);
$db_WeightAllowance = $runnerinfo[$key1][$key2]['WeightAllowance'];
$db_RatingRange = $runnerinfo[$key1][$key2]['RatingRange'];


//last race record*************************************************************************/
// $FormLine = mysqli_query($mysqli, "select * from FormLine where horsename like '".$runnerinfo[$key1][$key2]['Name']."' order by rectime desc limit 1");
// $fl =$flwin=$fldate="";
// if($FormLine && mysqli_num_rows($FormLine)==1){
// 	$flrow = mysqli_fetch_assoc($FormLine);				
// 	$fl = $flrow['lastjockey'];
// 	$fl .= $flrow['draw'];
// 	// $fl .= "[".$flrow['placing']."]";
// 	$flwin = "[".$flrow['winodds']."]";
// 	$fldate = substr($flrow['raceday'],0,5);
// }

/*pick barrier result----------------------------------------------------------------------------------*/
$db_barrierday = "";
$db_barrierGoing = "";
$db_barrierJockey = "";
$db_barrierTimex = "";
$db_barrierResult = "";
$db_barrierComment = "";
// echo "select * from barrierresult where Horse='".$runnerinfo[$key1][$key2]['Name']."' order by barrierday desc limit 1"."\n";
if(mysqli_num_rows($searchbarrier) ){
	// echo "select * from barrierresult where Horse like '".$runnerinfo[$key1][$key2]['Name']."' order by barrierday desc limit 1"."\n";
	// while ($row = mysqli_fetch_array($query)){
	// $row = mysqli_fetch_assoc($searchbarrier);
		if($row['barrierday']){
			$date_expire = '2014-08-06 00:00:00';    
			$date = new DateTime($row['barrierday']);
			$now = new DateTime();

        	$b = ($date->diff($now)->format("%a") < 14) ? "<font color=red>" : ""; 
		}
		$listr .= "<td style='$td' nowrap>".$b.$row['barrierday'];
		// $listr .= "<td style='$td' nowrap>".$row['Going'];
		$samejokey = ($row['Jockey'] == $runnerinfo[$key1][$key2]['Jockey']) ? "<font color=red>" : ""; 
		$listr .= "<td style='$td' nowrap>".$samejokey.$row['Jockey'];
		$listr .= "<td style='$td' nowrap>".$row['Timex'];
		$listr .= "<td style='$td' nowrap>".$row['Result'];
		$listr .= "<td style='$td' nowrap>".$row['Comment'];		
	// }
	$db_barrierday = $row['barrierday'];
	$db_barrierGoing = $row['Going'];
	$db_barrierJockey = $row['Jockey'];
	$db_barrierTimex = $row['Timex'];
	$db_barrierResult = $row['Result'];
	$db_barrierComment = $row['Comment'];
}
//------------------------------------------------------------------------------------------------------	
if(mysqli_num_rows($VeterinaryRecord)){
	$vrow = mysqli_fetch_assoc($VeterinaryRecord);
	$listr .= "<td style='$td' nowrap>".$vrow['chkdate'];
	$listr .= "<td style='$td' nowrap>".$vrow['detail'];
	$listr .= "<td style='$td' nowrap>".$vrow['passdate'];
}

//------------------------------------------------------------------------------------------------------	
$insert_raceinfo = "INSERT INTO racingrecord (id, racingdate, venueLong, venue, raceno, 
	Distance, Track, Horseno, Result, barrier, prop1, prop2, prop3, prop4, 
	horsename, Draw, HandicapWeight, WeightAllowance, RatingRange, trainer, trainercnt, trainer_style, trainerresultcnt, 
	jockey, jockeyresultcnt, trainerjockey, trainerjockeycnt, OddsInRange, 
	prewin, prewin_style, win, win_style, pla, prepla, prepla_style, prop, prop_style, 
	besttime, beattime_style, StakesWon, LastSixRuns, 
	barrierday, barrierday_style, barrierGoing, barrierHorse, barrierJockey, barrierJockey_style, 
	barrierTimex, barrierResult, barrierComment, rectime) VALUES 
	(NULL, '$racingdate', '$venueLong', '$venue', '$db_raceno', 
	'$db_Distance', '$db_Track', '$db_horseno', '$db_result', '$db_barrier', '$db_prop1', '$db_prop2', '$db_prop3', '$db_prop4', 
	'$db_horsename', '$db_Draw','$db_HandicapWeight', '$db_WeightAllowance', '$db_RatingRange', '$db_trainer', '$db_trainercnt', '$db_trainer_style', '$db_trainerresultcnt', 
	'$db_jockey', '$db_jockeyresultcnt', '$db_trainerjockey', '$db_trainerjockeycnt', '$db_OddsInRange', 
	'$db_prewin', '$db_prewin_style', '$db_win', '$db_win_style', '$db_pla', '$db_prepla', '$db_prepla_style', '$db_prop', '$db_prop_style', 
	'$db_besttime', '$db_beattime_style', '$db_StakesWon', '$db_LastSixRuns', 
	'$db_barrierday', '$db_barrierday_style', '$db_barrierGoing', '$db_barrierHorse', '$db_barrierJockey', '$db_barrierJockey_style', 
	'$db_barrierTimex', '$db_barrierResult', '$db_barrierComment', now())";
//echo $insert_raceinfo."<br>";
mysqli_query($mysqli, $insert_raceinfo);

			$cnt++;
			$temp_w = $title['win'] ? $title['win'] : $temp_w;
			$temp_p = $title['pla'] ? $title['pla'] : $temp_p;
			$wprankingcnt++;	// end of each race
		endforeach;
		//update.insert result pattern to db
		if($joinresult_pattern[$key1]['counter']){
			$new_resultpattern = substr((string)$joinresult_pattern[$key1]['pattern'],1,7);
			$search = mysqli_query($mysqli, "select * from raceresult_pattern where racingday like '".$racingdate."' and raceno ='".$key1."' limit 1");
			if(mysqli_num_rows($search) ){
			}else{
				$wpratio_pattern=$pattern;
				$result_orderbyprew=$new_resultpattern;
				$insertnewpattern = "insert into raceresult_pattern (racingday, raceno, wpratio_pattern, result_orderbyprew)
									values('".$racingdate."','".$key1."','".$wpratio_pattern."','".$result_orderbyprew."')";
				// echo $insertnewpattern."\n";
				mysqli_query($mysqli, $insertnewpattern);
			}
		}
		$listr .= "</table>";
		
     endforeach; 
$listr .= "</table><br>";
//--------------------------------------------------------------------------------------------------------
$JCx = array_merge($JC,$JockeyArray);
$JCx = array_unique($JCx);

$listr .= "<table border=1 bgcolor=lightgrey cellpadding='2' cellspacing='1' $tablestyle>";	
$listr .= "<tr><td>.";
foreach($JCx as $val){
	$listr .= "<td colspan=3>".$JockeyChallenge[(string)$val]['LastOdds'].$val."<font color=red>".$JockeyChallenge[(string)$val]['TotalPoints']."</font>";
}
// $analysis = utf8_decode( $analysis );
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
// print_r($analysis);
// $listr .= "<table border=1 bgcolor=lightgrey cellpadding='2' cellspacing='1' $tablestyle>";	
// $listr .= "<tr><td>.";
// foreach ($odds as $key1 => $value){
// 	$listr .= "<td colspan=3>".$key1;
// }
// // $analysis = utf8_decode( $analysis );
// foreach($JC as $val){
// 	$jockey = $analysis[$raceno][(string)$val]['Jockey'];
// 	$listr .= "<tr><td>".$jockey;	//race no
// 	foreach ($odds as $key1 => $value){
// 		$raceno = $key1;
// 		$analysisstyle = $analysis[$raceno][(string)$val]['style'];
// 		$analysiscontent = $analysis[$raceno][(string)$val]['wp'] ? "[".$analysis[$raceno][(string)$val]['wprankingcnt']."] " : "&nbsp;";
// 		$listr .= "<td style=\"$analysisstyle\">".$analysiscontent;
// 		$listr .= "<td style=\"$analysisstyle\">".$analysis[$raceno][(string)$val]['wp'];
// 		$listr .= "<td style='color:red; font-weight: bold;'>".($analysis[$raceno][(string)$val]['result'] ? $analysis[$raceno][(string)$val]['result'] : "&nbsp;");
// 	}
// }
// $listr .= "</table><br>";
//--------------------------------------------------------------------------------------------------------
echo $listr;
// if(1){
if($mail && $arraycount>1){

	// $mail = new PHPMailer();
	// $mail->CharSet = 'UTF-8';
	// $mail->Encoding = 'base64';
	
	// $mail->IsSMTP();							// set mailer to use SMTP
	// $mail->Host = "smtp.juraron.com.hk";		// specify main and backup server
	// $mail->SMTPAuth = true;						// turn on SMTP authentication
	// $mail->Username = "jhkdb@juraron.com.hk";	// SMTP username
	// $mail->Password = "123x1*";					// SMTP password
	// $mail->From = "jhkdb@juraron.com.hk";
	// $mail->SMTPSecure = "tls";

	// $headers  = "From: hkhorsepaper<hkhorsepaper@hotmail.com>\r\n";
	// $headers .= "Reply-To: hkhorsepaper@hotmail.com\r\n";
	// $headers .= "Return-Path: hkhorsepaper@hotmail.com\r\n";
	// $headers .= "X-Mailer: Drupal\n";
	// $headers .= 'MIME-Version: 1.0' . "\n";
	// $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

	// $mail->Host = "smtp.live.com";		// specify main and backup server
	// $mail->SMTPAuth = true;						// turn on SMTP authentication
	// $mail->Username = "hkhorsepaper@hotmail.com";	// SMTP username
	// $mail->Password = "hr.96692244";					// SMTP password
    // $mail->Port = 587;
	// $mail->SetFrom('hkhorsepaper@hotmail.com');
	// $mail->AddReplyTo("hkhorsepaper@hotmail.com", "hkhorsepaper");
	// $mail->addCustomHeader('X-custom-header: $headers');
	// $mail->SMTPSecure = "ssl";    

	// $mail->SMTPSecure = "ssl"; 
	// $mail->Host = "smtp.gmail.com"; 
	// $mail->Port = 465; 
	// $mail->CharSet = "utf-8"; 
	// $mail->Username = "hkhorsepaper@gmail.com"; 
	// $mail->Password = "mm96692244"; 

	// $mailer->Host = 'tls://smtp.gmail.com';
	// $mailer->SMTPAuth = true;
	// $mailer->Username = "hkhorsepaper@gmail.com";
	// $mailer->Password = "mm96692244";
	// $mailer->SMTPSecure = 'tls';
	// $mailer->Port = 587;

	// $mail->SetFrom('hkhorsepaper@gmail.com');

	// $mail->isHTML();
	// $mail->SMTPDebug = 2;
	// $mail->Debugoutput = 'html';	
	// $mail->FromName = "mm";
	// $mail->AddBCC("melvin@juraron.com.hk",".");
	// $mail->AddBCC("hkhorsepaper@gmail.com",".");
	// // $mail->AddAddress("hkhorsepaper@gmail.com");

	// $mail->WordWrap = 50;                                 // set word wrap to 50 characters
	// $mail->IsHTML(true);                                    // set email format to HTML

	// $mail->Subject = "[moosay] -".$emailtitle;	//$date;
	// //$mail->AddEmbeddedImage($string, 'mflow_c', 'mflow_c.png');
	// $mail->Body = $listr;	//"<img src='".$string."'>"; 

	$Subject = "[horsepaper] -".$emailtitle;	//$date;
	$Body    = $listr;
	$AltBody = '';
	$recipients = '';
	$log = '';
	$recipients_BCC = array(
		'hkhorsepaper@gmail.com' => 's',
		'melvin@juraron.com.hk' => 'm',
		// ..
	 );
	 $send = mailto($Subject,$Body,$AltBody,$recipients,$recipients_BCC,$log);
	//  echo $send;

	// if(!$mail->Send()){
	// 	echo "error";
	// }else{
	// 	echo "done";
	// }
	// $mail->ClearAddresses();
	// $mail->ClearAttachments(); 
}

?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
tablex {
	border : 1px;
	border-collapse: collapse;
	vertical-align: top;
	#background-color: white;
	background: #76b852; /* fallback for old browsers */
    background: -webkit-linear-gradient(right, #76b852, #8DC26F);
    background: -moz-linear-gradient(right, #76b852, #8DC26F);
    background: -o-linear-gradient(right, #76b852, #8DC26F);
    background: linear-gradient(to left, #76b852, #8DC26F);
    font-family: "Roboto", sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;      
}
thx, tdx {
  padding: 5px;
  text-align: right;
  vertical-align: top;
  font-size: 9px;
}
</style>
