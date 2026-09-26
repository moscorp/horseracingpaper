<?php
error_reporting(E_ALL & ~E_NOTICE);
require_once("lib/func_mailer_gmail.php");
include_once('simplehtmldom_1_9_1/simple_html_dom.php');
require_once('lib/func.php');	
require_once("lib/constants.php");
require_once('lib/func_grec.php');	

header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 0);
ini_set("allow_url_fopen", 1);

$mail= _get('mail');
$mm= _get('mm');
if (!file_get_contents("data:,ok")) {
	die("Houston, we have a stream wrapper problem.");
}

// $mm=1;

//---------------------------------------------------------------------------------------------
if(function_exists("date_default_timezone_set") AND function_exists("date_default_timezone_get")){
	// Set the default timezone to use. Available as of PHP 5.1
	// @date_default_timezone_set(@date_default_timezone_get()); // auto get server's timezone
	@date_default_timezone_set('Asia/Hong_Kong');
}


$raceno=1;
//---------------------------------------------------------------------------------------------
//racing date detail---------------------------------------------------------------------------------------------
$url ="https://bet.hkjc.com/racing/script/rsdata.js?lang=ch&date=*&venue=*";
$today = date("Y-m-d");
$url = _get('venue') ? "https://bet.hkjc.com/racing/script/rsdata.js?lang=ch&date=".$today."&venue=".strtoupper(_get('venue')) /*."&CV=FO_L4.01R0f"*/ : $url;
// echo $url."<br>";
// echo _get('venue');

// $url = "https://bet.hkjc.com/racing/script/rsdata.js?lang=ch&date=2023-08-13&venue=S2";
// $content = file_get_contents($url);
// // print_r($content);
// $pattern = "/var\s+(\w+)\s+=\s+'([^']+)'/";
// preg_match_all($pattern, $content, $matches);
// $dataArray = array_combine($matches[1], $matches[2]);
// $top5Jockey = $dataArray['top5Jockey'];
// $top5Trainer = $dataArray['top5Trainer'];
// echo$top5Jockey ;
// print_r($dataArray);
// die(1);

$html = file_get_html("compress.zlib://".$url);
$racingdaydata = multiexplode2(array("var","="),$html) ;
print_r($racingdaydata);
$newArray = array();
foreach($racingdaydata as $val) {
	if($val[0]){
		$val1 = str_replace("'","",$val[1]);
		$newArray += [$val[0] => $val1];
	}
}
// print_r($newArray);
$mtgDate="";
foreach($racingdaydata as $key) {
	if(str_replace(' ', '',$key[0])=="mtgDate"){ $mtgDate = str_replace(array("'",";"," "),"",$key[1]); }
	if(str_replace(' ', '',$key[0])=="mtgVenue"){ $mtgVenue = str_replace(array("'",";"," "),"",$key[1]); }
	if(str_replace(' ', '',$key[0])=="mtgTotalRace"){ $mtgTotalRace = str_replace(array("'",";"," "),"",$key[1]); }
	if(str_replace(' ', '',$key[0])=="venueLong"){ $venueLong = str_replace(array("'",";"," "),"",$key[1]); }
	if(str_replace(' ', '',$key[0])=="racePostTime"){ $racePostTime = str_replace(array("[","]"," "),"",$key[1]); } 
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
    $racedatahead[$raceno] = $value["race"].", ".$value["venue"].", ".$value["time"].",  ".$value["class"].", ".$value["track"].", ".$value["dist"].", ".$value["going"];
}
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
//-
//-----------------------------------------------
// echo 	"<br>".$mtgDate;
// echo 	"<br>".$mtgVenue;
// $arr = json_decode($raceHeaderInfoEN, true);
$racetime = explode(",", $racePostTime);
$racetime = str_replace($mtgDate,"", $racetime);

$racingdate=$mtgDate;
$venue=$mtgVenue;
$venueLong=$venueLong;
$start="1";
$end=$mtgTotalRace;
$racetime = $racetime;

$racingsch = array(
	'racingdate' => $mtgDate,
	'venue' => $mtgVenue,
	'venueLong' => $venueLong,
	'start' => 1,
	'end' => $mtgTotalRace,
	'racetime' => $racetime,
);

print_r($racingsch);
// echo json_encode( $racingsch );
// echo $racingsch['end'];

//---------------------------------------------------------------------------------------------------------------------------------------------------
// if($racingdate <> date("Y-m-d")) exit;

// clean up memory
// $html->clear();
// unset($html);
//----------------------------------------------------------------------------------------
$horseinfo = array();
$horseinfo_sql = "select * from horseinfo where 1 ";
// echo $horseinfo_sql;
// die(1);
$horseinfo_array = mysqli_query($mysqli, $horseinfo_sql);

while($horseinfo = mysqli_fetch_array($horseinfo_array)){
    $TJ[$horseinfo['Trainer']][$horseinfo['Jockey']]['race']++;
    $TJ[$horseinfo['Trainer']][$horseinfo['Jockey']]['T'] = ((int)$horseinfo['Pla']<4) ? $TJ[$horseinfo['Trainer']][$horseinfo['Jockey']]['T']+1 : $TJ[$horseinfo['Trainer']][$horseinfo['Jockey']]['T'];
	$TJ[$horseinfo['Trainer']][$horseinfo['Jockey']]['F'] = ((int)$horseinfo['Pla']<=4) ? $TJ[$horseinfo['Trainer']][$horseinfo['Jockey']]['F']+1 : $TJ[$horseinfo['Trainer']][$horseinfo['Jockey']]['F'];
	$TJ[$horseinfo['Trainer']][$horseinfo['Jockey']]['W'] = ((int)$horseinfo['Pla']==1) ? $TJ[$horseinfo['Trainer']][$horseinfo['Jockey']]['W']+1 : $TJ[$horseinfo['Trainer']][$horseinfo['Jockey']]['W'];

	// list($RC_Track, $Course, $Course1) = array_pad(explode('"', $horseinfo['RC_Track_Course']), 2, '');
	$RC_str = explode('&quot', $horseinfo['RC_Track_Course']);
	$TJ[$horseinfo['Trainer']][$horseinfo['Jockey']][$RC_str[0]]++;
	// $TJ[$horseinfo['Trainer']][$horseinfo['Jockey']][$RC_Track][$Course]++;
	$RC_Track_arr[] .= $RC_str[0];
	
}
$RC_Track_arr = array_unique($RC_Track_arr);
//----------------------------------------------------------------------------------------
$emailtitle = $racingdate." [".$venueLong.$venue."]";

//race result---------------------------------------------------------------------------------------------
$st_time   =   strtotime($racetime);
$cur_time   =   strtotime(now);
if($st_time < $cur_time){
	$resultx = array();
	for ($raceno = $start; $raceno <= $end; $raceno++) {
		$result_url = "https://bet.hkjc.com/racing/pages/results.aspx?lang=ch&date=$racingdate&venue=$venue&raceno=$raceno";
		// echo $result_url;
		$html = postRequest($result_url,[]);
		$htmls = new simple_html_dom();
		$htmls->load($html);
		// print_r($htmls);
		//die(1);	
		foreach($htmls->find('div[style="padding-bottom: 2px; padding-top: 2px; border-bottom: #cccccc 1px solid"]') as $resulttable){
			$rowcnt=0;			
			foreach($resulttable->find('table tr') as $row) {	
				if($rowcnt){
					$rowData = array();
					foreach($row->find('td') as $cell) {		
						$rowData[] = $cell->innertext;		
					}
					// echo $raceno."-".$rowData[0]."-".$rowData[1]."<br>";
					// $resultx[$raceno][$rowData[0]] = $rowData[1];
					$resultx[$raceno][$rowData[1]] = $rowData[0];
				}
				$rowcnt++;

			}

		}
		sleep(5);
	}
	// print_r($resultx);
}

//horse detail---------------------------------------------------------------------------------------------
$racecard_sql = "select *,
	(select concat(win_odds,'[',pla,'[',Running_Position) from horseinfo where horseid=RaceCard.brandno and jockey=SUBSTRING_INDEX(RaceCard.jockey,'(',1) order by horseinfo.date desc limit 1) as samejockey,
	(select Dist from horseinfo where horseid=RaceCard.brandno and jockey=SUBSTRING_INDEX(RaceCard.jockey,'(',1) order by horseinfo.date desc limit 1) as Dist,
	(select concat(win_odds,'[',pla) from horseinfo where horseid=RaceCard.brandno order by horseinfo.date desc limit 1) as lastodds,
	if(SUBSTRING_INDEX(RaceCard.jockey,'(',1)=(select jockey from horseinfo where horseid=RaceCard.brandno order by horseinfo.date desc limit 1),1,0) as lastjockey
	from RaceCard 
	where racingdate='".$racingsch['racingdate']."' and venue='".$racingsch['venue']."'";
echo $racecard_sql;
// die(1);
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

	$racecard[$row['raceno']][$row['HorseNo']]['HorseNo'] = $row['HorseNo'];
	$racecard[$row['raceno']][$row['HorseNo']]['Last6Runs'] = $row['Last6Runs'];
	$racecard[$row['raceno']][$row['HorseNo']]['Colour'] = $row['Colour'];
	$racecard[$row['raceno']][$row['HorseNo']]['horsename'] = ($row['horsename']);
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

	// $JockeyCode_cnt[(string)$runnerinfo[$raceno][$saddleno]['Jockey']]++;
	// $TrainerCode_cnt[(string)$runnerinfo[$raceno][$saddleno]['Trainer']]++;
	$racecard['Trainer_Jockey'][(string)$row['Trainer'].(string)$row['Jockey']]++;
	$racecard['Trainer'][(string)$row['Trainer']]++;
	$racecard['Jockey'][(string)$row['Jockey']]++;

	array_push($trainerjockey,(string)$row['Trainer'].(string)$row['Jockey']);
	array_push($trainer,(string)$row['Trainer']);
	array_push($jockey,(string)$row['Jockey']);
} 

//-----------------------------------------------------------------------------------------------------------------------------------------
$tri20 = array();
for ($raceno = $start; $raceno <= $end; $raceno++) {
	// $tri = "https://bet.hkjc.com/racing/getJSON.aspx?type=tritop&date=2022-07-27&venue=S2&raceno=1";
	$tri = "https://bet.hkjc.com/racing/getJSON.aspx?type=tritop&date=".$racingdate."&venue=".$venue."&raceno=".$raceno;
	$tri = postRequest($tri,[]);
	list($t['body'], $t['odds']) = explode(':',$tri);
	list($t['body'], $t['odds']) = explode('@;',$t['odds']);
	$tristr = "".str_replace('"}','',$t['odds']);
	$racearray = explode(';', $tristr);
	// print_r($racearray);
		foreach($racearray as $key=>$value){
			$arr = explode('=', $value);
			// echo $arr[0];
			// echo $arr[1];	
			foreach (explode('-', $arr[0]) as $v) {
				$tritemp[] = $v;
				// echo $v."<br>";
				$tri20[$raceno][$v]++;
			}
		}
}
// print_r($tri20);

//-----------------------------------------------------------------------------------------------------------------------------------------
// https://bet.hkjc.com/racing/getJSON.aspx?type=qtttop&date=2022-07-27&venue=S2&raceno=1
$qtt20 = array();
for ($raceno = $start; $raceno <= $end; $raceno++) {
	$qtt = "https://bet.hkjc.com/racing/getJSON.aspx?type=qtttop&date=".$racingdate."&venue=".$venue."&raceno=".$raceno;
	$qtt = postRequest($qtt,[]);
	list($t['body'], $t['odds']) = explode(':',$qtt);
	list($t['body'], $t['odds']) = explode('@;',$t['odds']);
	$tristr = "".str_replace('"}','',$t['odds']);
	$racearray = explode(';', $tristr);
	// print_r($racearray);
		foreach($racearray as $key=>$value){
			$arr = explode('=', $value);
			// echo $arr[0];
			// echo $arr[1];	
			foreach (explode('-', $arr[0]) as $v) {
				$tritemp[] = $v;
				// echo $v."<br>";
				$qtt20[$raceno][$v]++;
			}
		}
}
// print_r($qtt20);
//-----------------------------------------------------------------------------------------------------------------------------------------
// https://bet.hkjc.com/racing/getJSON.aspx?type=qtttop&date=2022-07-27&venue=S2&raceno=1
$fft20 = array();
for ($raceno = $start; $raceno <= $end; $raceno++) {
	$fft = "https://bet.hkjc.com/racing/getJSON.aspx?type=fftop&date=".$racingdate."&venue=".$venue."&raceno=".$raceno;
	$fft = postRequest($fft,[]);
	list($t['body'], $t['odds']) = explode(':',$fft);
	list($t['body'], $t['odds']) = explode('@;',$t['odds']);
	$tristr = "".str_replace('"}','',$t['odds']);
	$racearray = explode(';', $tristr);
	// print_r($racearray);
		foreach($racearray as $key=>$value){
			$arr = explode('=', $value);
			// echo $arr[0];
			// echo $arr[1];	
			foreach (explode('-', $arr[0]) as $v) {
				$tritemp[] = $v;
				// echo $v."<br>";
				$fft20[$raceno][$v]++;
			}
		}
}
// print_r($qtt20);
//-----------------------------------------------------------------------------------------------------------------------------------------
// https://bet.hkjc.com/racing/getJSON.aspx?type=tcetop&date=2022-07-27&venue=S2&raceno=1
$ttt20 = array();
for ($raceno = $start; $raceno <= $end; $raceno++) {
	$ttt = "https://bet.hkjc.com/racing/getJSON.aspx?type=tcetop&date=".$racingdate."&venue=".$venue."&raceno=".$raceno;
	$ttt = postRequest($ttt,[]);
	list($t['body'], $t['odds']) = explode(':',$ttt);
	list($t['body'], $t['odds']) = explode('@;',$t['odds']);
	$tristr = "".str_replace('"}','',$t['odds']);
	$racearray = explode(';', $tristr);
	// print_r($racearray);
		foreach($racearray as $key=>$value){
			$arr = explode('=', $value);
			// echo $arr[0];
			// echo $arr[1];	
			foreach (explode('-', $arr[0]) as $v) {
				$tritemp[] = $v;
				// echo $v."<br>";
				$ttt20[$raceno][$v]++;
			}
		}
}

//odds detail---------------------------------------------------------------------------------------------
// $oddst = array();

$url = "https://bet.hkjc.com/racing/getJSON.aspx?type=winplaodds&date=".$racingdate."&venue=".$venue."&start=1&end=".$end;
$url_pre = "https://bet.hkjc.com/racing/getJSON.aspx?type=winplaoddspre&date=".$racingdate."&venue=".$venue."&start=1&end=".$end;
// echo $url."<br>";
// echo $url_pre."<br>";
// $obj_pre = postRequest($url,[]);
// $obj = postRequest($url_pre,[]);

function fetchodds($racingdate,$venue,$end,$precurr){
	for ($raceno = 1; $raceno <= $end; $raceno++) {
		// $url = "https://bet.hkjc.com/racing/getJSON.aspx?type=winplaodds&date=".$racingsch['racingdate']."&venue=".$racingsch['venue']."&start=$raceno&end=".$raceno;
		switch ($precurr){
			case "pre"  : $type = "winplaoddspre"; break;
			case "curr" : $type = "winplaodds";    break;
		}
		$url = "https://bet.hkjc.com/racing/getJSON.aspx?type=$type&date=".$racingdate."&venue=".$venue."&start=$raceno&end=".$raceno;
		// echo $url."<br>";
		$obj = postRequest($url,[]);
		list($d['body'], $d['headers']) = explode(':',$obj);
		// print_r($obj);
		$jsonString = (isset($obj)) ? multiexplode(array("@@@WIN;","#PLA;"),$d['headers']) : "";
		// print_r($jsonString);
		// echo "<br>0:".$jsonString[0];
		// echo "<br>1:".$jsonString[1];
		$array_win = explode(';', $jsonString[1]);
		foreach($array_win as $odds_win){
			$arr = explode('=', $odds_win);
			$horseno = $arr[0];
			$win = $arr[1];
			// echo $cnt."----------".$raceno."-".$key."-".$arr[0]."-".$arr[1]."-".$arr[2]."<br>";
			$odds[$raceno][$horseno][$precurr]["win"] = $win;
			// $oddst[$raceno][$precurr]["wptotal"] += $win;
		}
		// echo "<br>2:".$jsonString[2];
		$array_pla = explode(';', $jsonString[2]);
		foreach($array_pla as $odds_pla){
			$arr = explode('=', $odds_pla);
			$horseno = $arr[0];
			$pla = $arr[1];
			$odds[$raceno][$horseno][$precurr]["pla"] = $pla;
		}
	}
	// print_r($odds);
	return $odds;
}
$oddsarr_pre= fetchodds($racingsch['racingdate'],$racingsch['venue'],$racingsch['end'],'pre');
$oddsarr_curr= fetchodds($racingsch['racingdate'],$racingsch['venue'],$racingsch['end'],'curr');

// print_r($oddsarr_pre);
// print_r($oddsarr_curr);
// print_r($oddst);


foreach($oddsarr_pre as $raceno => $horsedata){
    foreach($horsedata as $horseno => $oddsvalue){
        $odds[$raceno][$horseno]['pre']['win'] = $oddsvalue['pre']['win'];
        $odds[$raceno][$horseno]['pre']['pla'] = $oddsvalue['pre']['pla'];
		$oddst[$raceno]['pre']["wptotal"] += $oddsvalue['pre']['win'];
    }
}
foreach($oddsarr_curr as $raceno => $horsedata){
    foreach($horsedata as $horseno => $oddsvalue){
        $odds[$raceno][$horseno]['curr']['win'] = $oddsvalue['curr']['win'];
        $odds[$raceno][$horseno]['curr']['pla'] = $oddsvalue['curr']['pla'];
		$oddst[$raceno]['curr']["wptotal"] += $oddsvalue['curr']['win'];
    }
}
//add prop ----------------------------------------------------------------------------------------------------------------------------
foreach ($odds as $raceno => $value){
	$prop_pre_max = $prop_curr_max = 0;
	foreach($value as $horseno => $title){
		$prop_pre = is_numeric($title["pre"]["pla"]) ? number_format(100*$title["pre"]["pla"]/$title["pre"]["win"],0) : 0;
		$odds[$raceno][$horseno]["pre"]["prop"] = $prop_pre;
		$prop_pre_array[$raceno][] = $prop_pre;

		$win_pre_array[$raceno][] = $title["pre"]["win"];

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
			});
		}else{
			// echo 2;
			uasort($racearray, function($a, $b) {
				return ($a["pre"]["win"] <= $b["pre"]["win"]) ? -1 : 1;
			});
		}
	}
// die(1);
	$firstbig3x=0;
	$cnt=$foundbig3x=0;
	$bestimearray = array();
	foreach($racearray as $horseno => $detail){
		$odds_sorted[$raceno][$horseno]['pre']["win"]  = $detail['pre']["win"];
		$odds_sorted[$raceno][$horseno]['pre']["pla"]  = $detail['pre']["pla"];
		$odds_sorted[$raceno][$horseno]['pre']["prop"] = $detail['pre']["prop"];
		$odds_sorted[$raceno][$horseno]['pre']["samewin"] = array_icount_values($win_pre_array[$raceno],$detail['pre']["win"]);
		$odds_sorted[$raceno][$horseno]['pre']["samewp"] = array_icount_values($prop_pre_array[$raceno],$detail['pre']["prop"]);
		//first big3x
		if($foundbig3x==0 && $firstbig3x==0 && $detail['pre']["prop"] >=30 && $detail['pre']["prop"] <40 && $detail['pre']["prop"] > $firstbig3x){
			$firstbig3x = $detail['pre']["prop"];
			$foundbig3x++;
		}
		$odds_sorted[$raceno][$horseno]['pre']["firstbig3x"] = $firstbig3x;
		//firstbig3x ----------------------------------------------------------------------------------------------------------------------
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

		$temp_pre_prop = $detail['pre']["prop"];
		$cnt++;
	}

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
//--------------------------------------------------------------------------------------------------
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
		// echo $bc['comment'];
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
$run = mysqli_query($mysqli, $query);
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
			WHERE racingdate = '".$racingdate."' and venue = '".$venue."' order by proptime asc limit 1";
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
// echo $sql."<br><br>";
// $pro_history = $db->get_results($sql);
$array = mysqli_query($mysqli, $sql);
while($data = mysqli_fetch_array($array)){
// foreach ( $pro_history as $data ) {
	$history_array[$data['raceno']][$data['horseno']]["max_prop_curr"]= $data['max_prop_curr'];
	$history_array[$data['raceno']][$data['horseno']]["min_prop_curr"]= $data['min_prop_curr'];
	$history_array[$data['raceno']][$data['horseno']]["max_tri20"]= $data['max_tri20'];
	$history_array[$data['raceno']][$data['horseno']]["max_ttt20"]= $data['max_ttt20'];
	$history_array[$data['raceno']][$data['horseno']]["max_fft20"]= $data['max_fft20'];
	$history_array[$data['raceno']][$data['horseno']]["max_qtt20"]= $data['max_qtt20'];
}
// print_r($history_array);
//-------------------------------------------------------------------------------------------------------------------------
if($mm){
	$columns =  48;
	$dispfull = 1;
	// echo $dispfull;
}else{
	$columns =  41;
	$dispfull = 0;
}

// echo "<table border=1>";
$listr__ = "";
foreach($odds_sorted as $raceno => $horsedata){
	$class = ".qpmatrix".$raceno;
	$listr__ .= $raceno ? "<a href='#' onclick=\"w3.toggleShow('{$class}')\">@</a>".$racedatahead[$raceno] : "";  ///racetime-----------------$racedatahead[$raceno]
	// $listr .= "<div class='qpmatrix'>";
	$class = str_replace('.','',$class)." qpmatrix";
	$listr__ .= "<table class='$class'><tr><td>";
	$listr__ .= output_wp_matrix($raceno,$qinpre[$raceno],$table_winpre[$raceno],$resultarray[$raceno]);
	$listr__ .= "<td>";
	$listr__ .= output_wp_matrix($raceno,$qin[$raceno],$table_win[$raceno],$resultarray[$raceno]);
	$listr__ .= "<td>";
	$listr__ .= output_wp_matrix($raceno,$qpl[$raceno],$table_pla[$raceno],$resultarray[$raceno]);
	$listr__ .= "</table>";

	// $listr__ .= $racedatahead[$raceno];//"<tr><td colspan=$columns>".$racedatahead[$raceno];
	$listr__ .= "<table border=1>";
	
	$listr__ .= "<thead><tr>";
	for($i = 1; $i<$columns; $i++ ){
		$listr__ .= "<th class='order'></th>";
	}
	$listr__ .= "</thead>";
	$listr__ .= "<tbody>";
    foreach($horsedata as $horseno => $detail){
		if(is_numeric($horseno)){
			//-----------------------------------------------------------------------------------------------------
			$firstbig3x = ($detail['pre']["firstbig3x"] == $detail['pre']["prop"]) ? "<i><font color=blue>" : "";
			$is_maxwp_pre = ($detail['pre']["prop"] == $wpratio_max[$raceno]['pre'])  ? "<b><font color=red>" : "";
			$is_maxwp_curr = ($detail['curr']["prop"] == $wpratio_max[$raceno]['curr'])  ? "<b><font color=red>" : "";

			//same3big-------------------------------------------------------------------------------------------------------
			if(!$same3big_selected && $detail['pre']["prop"]>30 && $detail['pre']["samewp"]>1 && $detail['pre']["prop"]<$detail['pre']["firstbig3x"]){
				$pick[$raceno][$horseno] .= "s3";
				$same3big_selected=1;
			}
			$is_samewp_pre  = ($detail['pre']["samewp"]>1) ? "bgcolor=yellow" : "";
			$is_samewp_pre3  = ($detail['pre']["samewp"]==3 && $detail['pre']["prop"]>=20 )  ? "<u>" : "";
			$is_samewin_pre  = ($detail['pre']["samewin"]>1) ? "bgcolor=yellow" : "";
			$is_samewp_curr = ($detail['curr']["samewp"]>1) ? "bgcolor=#FFFF99" : "";
			$is_24xontop = ($detail['pre']["samewp"]==2 && $detail['pre']["prop"]>=40 && $detail['pre']["prop"]<50 )  ? "<b>" : "";
			$nosamehist = $racecard[$raceno][$horseno]['BestTime'] ? "" : "bgcolor=lightgrey";
			$isbestime = ($bestime_min[$raceno] == $racecard[$raceno][$horseno]['BestTime_val']) ? "<font color=red>" : "";

			$windiff = ($detail['pre']["win"]/$detail['curr']["win"] >1.15) ? "<font color=blue>" : "";
			$windiff .= ($detail['curr']["win"]/$detail['pre']["win"] >1.15) ? "<font color=gray>" : "";

			$pladiff = ($detail['pre']["pla"]/$detail['curr']["pla"] >1.15) ? "<font color=blue>" : "";
			$pladiff .= ($detail['curr']["pla"]/$detail['pre']["pla"] >1.15) ? "<font color=gray>" : "";

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
			// echo $detail['pre']["prop"]."-".$history_array[$raceno][$horseno]["max_prop_curr"]."-".$detail['pre']["prop"]."-".$history_array[$raceno][$horseno]["min_prop_curr"]."<br>";
			//-----------------------------------------------------------------------------------------------------------------------------------------
			$listr__ .= "<tr>";
			// echo "<td>".$detail['pre']['prop'];
			// echo "<td>".$detail['curr']['prop'];
			$listr__ .= ($dispfull ? "<td $is_samewp_pre>".$is_samewp_pre3.$is_maxwp_pre.$firstbig3x.$is_24xontop.$detail['pre']["prop"] : "");
			//--
			$trainer_jockey_prop[(string)$racecard[$raceno][$horseno]['Trainer'].(string)$racecard[$raceno][$horseno]['Jockey']] .= "[".$is_samewp_pre3.$is_maxwp_pre.$firstbig3x.$detail['pre']["prop"]."</font></b>";
			$jockey_prop[(string)$racecard[$raceno][$horseno]['Jockey']][$raceno]['pre'] = $is_samewp_pre3.$is_maxwp_pre.$firstbig3x.$detail['pre']["prop"]."</font></b>";
			$jockey_prop[(string)$racecard[$raceno][$horseno]['Jockey']][$raceno]['curr'] = $is_bigger_prop_pre.$is_maxwp_curr.$detail['curr']["prop"]."</font></b>";
			$jockey_prop[(string)$racecard[$raceno][$horseno]['Jockey']][$raceno]['horseno'] = $horseno;
			$jockey_prop[(string)$racecard[$raceno][$horseno]['Jockey']][$raceno]['max_prop_curr'] = $history_array[$raceno][$horseno]["max_prop_curr"];
			$trainer_prop[(string)$racecard[$raceno][$horseno]['Trainer']]['pre'] += $detail['pre']["prop"];
			//--
			$is_bigger_prop_pre = ($detail['curr']["prop"] > $detail['pre']["prop"])   ? "<b>" : "";
			$listr__ .= ($dispfull ? "<td $is_samewp_curr>".$is_bigger_prop_pre.$is_maxwp_curr.$detail['curr']["prop"] : "");
			//--
			$is_bigger_prop_hist_min = ($history_array[$raceno][$horseno]["min_prop_curr"] >= $detail['pre']["prop"])   ? "<b>" : "";
			$is_bigger_prop_curr = ($history_array[$raceno][$horseno]["max_prop_curr"] > $detail['pre']["prop"])   ? "<b>" : "";
			$listr__ .= ($dispfull ? "<td bgcolor=lightgrey>".$is_bigger_prop_hist_min.$history_array[$raceno][$horseno]["min_prop_curr"] : "");
			$listr__ .= ($dispfull ? "<td bgcolor=lightgrey>".$is_bigger_prop_curr.$history_array[$raceno][$horseno]["max_prop_curr"] : "");
			//-----------------------------------------------------------------------------------------------------------------------------------------
			$prepla_todd = 100*$detail['pre']["pla"]/$oddst[$raceno]["pre"]["wptotal"];
			$currpla_todd = 100*$detail['curr']["pla"]/$oddst[$raceno]["curr"]["wptotal"];
			$is_curr_drop = ($currpla_todd > $prepla_todd) ? "<font color=grey>" : "<b>";
			$small_prepla_todd = ($tmp_prepla_todd && $prepla_todd < $tmp_prepla_todd) ? "<font color=red>" : "";
			$listr__ .= ($dispfull ? "<td bgcolor=#FFCD99>".$small_prepla_todd.number_format($prepla_todd,1) : "");
			$listr__ .= ($dispfull ? "<td bgcolor=#FFCD99>".$is_curr_drop.number_format($currpla_todd,1) : "");

			$save2_plahist[$racingdate][$venue][$raceno][$horseno]=$currpla_todd;

			$tmp_prepla_todd = $prepla_todd;
			if($prepla_todd >= $currpla_todd){
				$oddchange = "bgcolor=#FFCDFF";
			}else{
				$oddchange = "lightgrey";
			}
			//--

			$listr__ .= "<td>".$samenos.$firstbig3x.$horseno;
			$listr__ .= "<td><b>".($resultx[$raceno][$horseno] ? $resultx[$raceno][$horseno] : "");
			if($resultx[$raceno][$horseno] && $racecard[$raceno][$horseno]['Trainer']){
				$ispla_Trainer_Jockey[] =  (string)$racecard[$raceno][$horseno]['Trainer'].(string)$racecard[$raceno][$horseno]['Jockey'];
				$accu_result[(string)$racecard[$raceno][$horseno]['Trainer'].(string)$racecard[$raceno][$horseno]['Jockey']] .= $resultx[$raceno][$horseno];
				$accu_result[(string)$racecard[$raceno][$horseno]['Trainer']] .= $resultx[$raceno][$horseno];
				$accu_result[(string)$racecard[$raceno][$horseno]['Jockey']] .= $resultx[$raceno][$horseno];
				$accu_result[$racecard[$raceno][$horseno]['Draw']] .= $resultx[$raceno][$horseno];

				$jockey_result[(string)$racecard[$raceno][$horseno]['Jockey']][$raceno] = $resultx[$raceno][$horseno];
				$jockey_accu[(string)$racecard[$raceno][$horseno]['Jockey']] .= $resultx[$raceno][$horseno];
				$trainer_accu[(string)$racecard[$raceno][$horseno]['Trainer']] .= $resultx[$raceno][$horseno];
			}
			$Trainer_Jockey = (string)$racecard[$raceno][$horseno]['Trainer'].(string)$racecard[$raceno][$horseno]['Jockey'];
			$bold_ispla_Trainer_Jockey = in_array($Trainer_Jockey , $ispla_Trainer_Jockey)  ? "<b>" : "";

			$checkboxname = $racecard[$raceno][$horseno]['Jockey'].$raceno;
			$terminated = $detail['curr']["prop"] ? 0 : 1;
			$listr__ .= "<td style='' $nosamehist>".(!$terminated ? "<input type=\"checkbox\" name=\"test\" value=\"value1\">" : "");
			$listr__ .= "<td style='' $oddchange>".(!$terminated ? "<input type=\"checkbox\" name=\"test\" value=\"value1\">" : "");			
			$listr__ .= "<td style='' $uppro ><input type=\"checkbox\" name=\"$checkboxname\" value=\"value1\" onchange=\"getCheckedBoxes('$checkboxname');this.checked^=1;\">";
			//------------------------------------------------------------------------------------------------------
			$max_tri20 = ($history_array[$raceno][$horseno]["max_tri20"]>0 && !$tri20[$raceno][$horseno]) ? "+".$history_array[$raceno][$horseno]["max_tri20"] : "";
			$listr__ .= "<td style='' ><i>".$max_tri20.$tri20[$raceno][$horseno];
			$max_ttt20 = ($history_array[$raceno][$horseno]["max_ttt20"]>0 && !$ttt20[$raceno][$horseno]) ? "+".$history_array[$raceno][$horseno]["max_ttt20"] : "";
			$listr__ .= "<td style='' >".$max_ttt20.$ttt20[$raceno][$horseno];
			$max_fft20 = ($history_array[$raceno][$horseno]["max_fft20"]>0 && !$fft20[$raceno][$horseno]) ? "+".$history_array[$raceno][$horseno]["max_fft20"] : "";
			$listr__ .= "<td style='' ><i>".$max_fft20.$fft20[$raceno][$horseno];
			$max_qtt20 = ($history_array[$raceno][$horseno]["max_qtt20"]>0 && !$qtt20[$raceno][$horseno]) ? "+".$history_array[$raceno][$horseno]["max_qtt20"] : "";
			$listr__ .= "<td style='' >".$max_qtt20.$qtt20[$raceno][$horseno];

			$listr__ .= ($dispfull ? "<td>".$detail['pre']["isH"] : "");
			$listr__ .= "<td>".($detail['pre']["prop"]==$odds_sorted[$raceno]["b2u_nextbig"] ? "n2b" : " ");
			$signalx[$raceno][$horseno] .= ($detail['pre']["prop"]==$odds_sorted[$raceno]["b2u_nextbig"] ? "n2b" : " ");
			$listr__ .= "<td>".$pick[$raceno][$horseno];
				$pick_horsename[$raceno][$horseno] = $pick[$raceno][$horseno] ? $racecard[$raceno][$horseno]['horsename'] : "";
				// echo $pick_horsename[$raceno][$horseno]."<br>";
			//------------------------------------------------------------------------------------------------------
			$dist = "";
			if($racecard[$raceno][$horseno]['Dist'] == $racedist[$raceno]){
				$dist = "";
			}else{
				if($racecard[$raceno][$horseno]['Dist'] < $racedist[$raceno]){
					$dist = "<font color=blue>";
				}else{
					$dist = "<font color=green>";
				}
			}
			$islastjockey = $racecard[$raceno][$horseno]['lastjockey'] ? "<b>" : "";
			$listr__ .= "<td nowrap>".$islastjockey.$racecard[$raceno][$horseno]['lastodds'];
			list($winodds, $pay, $runtype) = explode('[', $racecard[$raceno][$horseno]['samejockey']);
			$listr__ .= "<td nowrap>".$dist.$winodds;	//.$racecard[$raceno][$horseno]['Dist'].$racedist[$raceno];
			$listr__ .="<td nowrap>".$dist.$racecard[$raceno][$horseno]['runningtype'];
			//--------------------------------------

			$listr__ .= "<td align=right $is_samewin_pre>".$detail['pre']["win"]*1;
			$listr__ .= "<td align=right><b>".$windiff.$detail['curr']["win"]*1;
			$listr__ .= "<td><b>".$pladiff.$detail['curr']["pla"]*1;
			$listr__ .= "<td>".$pladrop.$hl_smallerpla.$detail['pre']["pla"]*1;
			//------------------------------------------------------------------------------------------------------
			// echo "<td>".$detail['pre']['win']."<td>".$detail['curr']['win'];
			// echo "<td>".$detail['curr']['pla']."<td>".$detail['pre']['pla'];
			$listr__ .= "<td>".$pick[$raceno][$horseno];
			$listr__ .= "<td nowrap bgcolor=#CDFF99><font color=brown>".$racecard[$raceno][$horseno]['Draw']*1;
			$listr__ .= "<td nowrap>".$accu_result[$racecard[$raceno][$horseno]['Draw']];
			$listr__ .= "<td nowrap>".$racecard[$raceno][$horseno]['horsename'];
			$jockeyshort = explode('(', $racecard[$raceno][$horseno]['Jockey']);
			$listr__ .= "<td nowrap>".$TJ[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['race'];
			$listr__ .= "<td nowrap>".$TJ[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['F'];
			$listr__ .= "<td nowrap>".$TJ[$racecard[$raceno][$horseno]['Trainer']][$jockeyshort[0]]['W'];
			
			$listr__ .= "<td nowrap>[".$bold_ispla_Trainer_Jockey.$racecard['Trainer_Jockey'][(string)$racecard[$raceno][$horseno]['Trainer'].(string)$racecard[$raceno][$horseno]['Jockey']]."]";
			$listr__ .= "<td nowrap>".$accu_result[(string)$racecard[$raceno][$horseno]['Trainer'].(string)$racecard[$raceno][$horseno]['Jockey']];
			$listr__ .= "<td nowrap>".$racecard['Trainer'][(string)$racecard[$raceno][$horseno]['Trainer']];
			$listr__ .= "<td nowrap>".$accu_result[(string)$racecard[$raceno][$horseno]['Trainer']];
			$listr__ .= "<td nowrap>".$racecard[$raceno][$horseno]['Trainer'];
			$listr__ .= "<td nowrap>".$racecard['Jockey'][(string)$racecard[$raceno][$horseno]['Jockey']];
			$listr__ .= "<td nowrap>".$accu_result[(string)$racecard[$raceno][$horseno]['Jockey']];
			$listr__ .= "<td nowrap>".$racecard[$raceno][$horseno]['Jockey'];			

			// echo "<td nowrap>".$odds."(".$pay.")";
			// echo "<td>".$racecard[$raceno][$horseno]['Sex'];
			$listr__ .= "<td nowrap>".$racecard[$raceno][$horseno]['Last6Runs'];
			$listr__ .= "<td nowrap>".$isbestime.$racecard[$raceno][$horseno]['BestTime'];
			$listr__ .= "<td nowrap>".$racecard[$raceno][$horseno]['SeasonStakes'];
			$listr__ .= "<td nowrap>".$racecard[$raceno][$horseno]['OverWt'];
			$listr__ .= "<td nowrap>".$racecard[$raceno][$horseno]['Gear'];
			//-------------
			
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
			$listr__ .= "<td nowrap style=' $bcommentstyle' bgcolor=#eeeeee>".(mysqli_num_rows($searchbarrier) ? barriercomment($barriercomment,$row['Comment']) : "_&nbsp;");
			$listr__ .= "<td nowrap style=' $bcommentstyle' bgcolor=#eeeeee>".$row['Comment'];
//-----------------------------------------

			$listr__ .= "<td nowrap>".Grec($racecard[$raceno][$horseno]['BrandNo']);

//-----------------------------------------

		}
    }
	$listr__ .= "</tbody></table>";
}

//--------------------------------------------------------------------------------------------------------

// $racecard['Trainer_Jockey'][(string)$row['Trainer'].(string)$row['Jockey']]++;
// $racecard['Trainer'][(string)$row['Trainer']]++;
// $racecard['Jockey'][(string)$row['Jockey']]++;
$tablestyle1 = "style=\"border-collapse: collapse;\"";
$trainerjockey = array_unique($trainerjockey);
$trainer = array_unique($trainer);
$jockey = array_unique($jockey);
// print_r($trainer_jockey_prop);
$listr__ .= "<hr>";
$listr__ .= "<table border=1 bgcolor=lightgrey cellpadding='2' cellspacing='1' $tablestyle1>";	
$listr__ .= "<tr><td colspan=3>.";
foreach($jockey as $jockeyname){
	$listr__ .= "<td nowrap>".$jockeyname;
}
// $listr__ .= "<td>Sub";
foreach($trainer as $trainername){
	$listr__ .= "<tr>";
	$listr__ .= "<td nowrap>".$trainername;
	$listr__ .= "<td align=right>".number_format($trainer_prop[$trainername]['pre']);
	$listr__ .= "<td nowrap>".($trainer_accu[$trainername] ? $trainer_accu[$trainername] : "");
	foreach($jockey as $jockeyname){
		$listr__ .= "<td>".$trainer_jockey_prop[$trainername.$jockeyname];
	}	
}
$listr__ .= "</table>";
//------------------------
$listr__ .= "<hr>";
$listr__ .= "<table border=1 bgcolor=lightgrey cellpadding='2' cellspacing='1' $tablestyle1>";	
$listr__ .= "<tr><td>.";
foreach($jockey as $jockeyname){
	$listr__ .= "<td colspan=4 nowrap>".$jockeyname;
}
foreach($odds_sorted as $raceno => $val){
	$listr__ .= "<tr>";
	$listr__ .= "<td>".$raceno;
	foreach($jockey as $jockeyname){
		$checkboxname = $jockeyname.$raceno;
		$listr__ .= "<td><font color=gray>".$jockey_prop[$jockeyname][$raceno]['horseno'];
		$listr__ .= "<td>".($jockey_prop[$jockeyname][$raceno]['curr'] ? $jockey_prop[$jockeyname][$raceno]['pre'].">".$jockey_prop[$jockeyname][$raceno]['curr'].".".$jockey_prop[$jockeyname][$raceno]['max_prop_curr'] : $jockey_prop[$jockeyname][$raceno]['pre']);
		$listr__ .= "<td style='' >".($jockey_prop[$jockeyname][$raceno]['horseno'] ? "<input type=\"checkbox\" id=\"$checkboxname\" name=\"$checkboxname\" value=\"value1\" onchange=\"getCheckedBoxes('$checkboxname');this.checked^=1;\">" : "");
		$listr__ .= "<td>".($jockey_result[$jockeyname][$raceno] ? "[".$jockey_result[$jockeyname][$raceno]."]" : "&nbsp;");
	}
}
$listr__ .= "</table>";
//------------------------
$listr__ .= "<hr>";
$listr__ .= "<table border=1 bgcolor=lightgrey cellpadding='2' cellspacing='1' $tablestyle1>";	
$listr__ .= "<tr><td colspan=3>.";
foreach($odds_sorted as $raceno => $val){
	$listr__ .= "<td colspan=4 nowrap>".$raceno;
}
foreach($jockey as $jockeyname){
	$listr__ .= "<tr>";
	$listr__ .= "<td nowrap>".$jockeyname;
	$listr__ .= "<td nowrap>".$uprop_countJockey[$jockeyname];
	$listr__ .= "<td nowrap>".($jockey_accu[$jockeyname] ? $jockey_accu[$jockeyname] : "");
	foreach($odds_sorted as $raceno => $val){
		$checkboxname = $jockeyname.$raceno;
		$listr__ .= "<td><font color=gray>".$jockey_prop[$jockeyname][$raceno]['horseno'];
		$listr__ .= "<td>".($jockey_prop[$jockeyname][$raceno]['curr'] ? $jockey_prop[$jockeyname][$raceno]['pre'].">".$jockey_prop[$jockeyname][$raceno]['curr'].".".$jockey_prop[$jockeyname][$raceno]['max_prop_curr'] : $jockey_prop[$jockeyname][$raceno]['pre']);
		$listr__ .= "<td style='' >".($jockey_prop[$jockeyname][$raceno]['horseno'] ? "<input type=\"checkbox\" id=\"$checkboxname\" name=\"$checkboxname\" value=\"value1\" onchange=\"getCheckedBoxes('$checkboxname');this.checked^=1;\">" : "");
		$listr__ .= "<td><font color=brown>".($jockey_result[$jockeyname][$raceno] ? "[".$jockey_result[$jockeyname][$raceno]."]" : "&nbsp;");
	}
}
$listr__ .= "</table>";
//---------------------------------------------------------------
$RaceVenue = array("HV", "ST");
if (in_array($venue, $RaceVenue)) {
}

foreach($RC_Track_arr as $RC_Track){
	// echo $RC_Track;
}

//---------------------------------------------------------------
echo $listr__;


if($mail && $racingdate == date("Y-m-d")){
	// $ipAddress = substr($_SERVER['SERVER_NAME'],-3);
	$ipAddress = substr($_SERVER['HTTP_CLIENT_IP'],-3);
	$Subject = "[horsepaper] -".$emailtitle." [AI] ".$ipAddress;	//$date;
	$Body    = $listr__;
	$AltBody = '';
	$recipients = '';
	$log = '';
	$recipients_BCC = array(
		'hkhorsepaper@gmail.com' => 's',
		'melvin@juraron.com.hk' => 'm',
		// ..
	 );
	 
	 $send = mailto($Subject,$Body,$AltBody,$recipients,$recipients_BCC,$log);

}

if($logs){
	$insert = "	INSERT INTO logs
	VALUES('','ai'.$logs,now())";
	// echo $insert;
	mysqli_query($mysqli, $insert);
}


?>

<script src="https://www.w3schools.com/lib/w3.js"></script>
<STYLE type="text/css" media="all">
BODY, FIELDSET, MARQUEE, TEXTAREA, TD, TR, SELECT, input {
	font-family: Tahoma, Verdana, sans-serif;
	font-size: 14px;
	font-weight: normal;
	text-decoration: none;
}
table,td {
	border: 1px solid gray;
    border-spacing: 0px;
    border-collapse: separate;
}
td { 
    padding: 2px;
}
img { border: 0 solid; }


a
{
    text-decoration: none;
}    

.qpmatrix { display: none;}
</STYLE>


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