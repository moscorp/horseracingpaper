<?php

error_reporting(E_ALL);
// ini_set('display_errors', true);
// ini_set("allow_url_fopen", 1);
header('Content-Type: text/html; charset=utf-8');
// require_once('lib/mossql.php');	
require_once('lib/func.php');	
require_once('lib/func_grec.php');	
// require_once("lib/func_mailer_gmail.php");
include_once('simplehtmldom_1_9_1/simple_html_dom.php');

require_once("lib/constants.php");
$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
mysqli_set_charset($mysqli, "utf8mb4");
$logs = "";
if (!$mysqli) {
    die('DATABASE ERROR: ' . mysqli_connect_errno());
}
//---------------------------------------------------------------------------------------------------------------------------------
function get_innertext($html_tag) {
    /**
     * Retrieves the inner text of an HTML tag, excluding any images.
     * 
     * @param string $html_tag The HTML tag to be processed.
     * @return string The inner text of the HTML tag, excluding any images.
     */
    if (strpos($html_tag, '<img') !== false) {
        return '';
    } else {
        // Remove the opening and closing tags
        $inner_text = str_replace(array('<td>', '</td>'), '', $html_tag);
        return $inner_text;
    }
}
//---------------------------------------------------------------------------------------------------------------------------------
$today = date("Y-m-d");
$url ="https://bet2.hkjc.com/racing/script/rsdata.js?lang=ch&date=*&venue=*";
$url = isset($_GET['venue']) ? "https://bet2.hkjc.com/racing/script/rsdata.js?lang=ch&date=".$today."&venue=".$_GET['venue']."&CV=FO_L4.01R0f" : $url;
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
	if($raceno){
	    $class = isset($value["class"]) ? $value["class"] : "";
		$racedatahead[$raceno] = $value["race"].", ".$value["venue"].", ".$value["time"].",  ".$class.", ".$value["track"].", ".$value["dist"].", ".$value["going"];
		$racedist[$raceno] = str_replace("m","",$value["dist"]);
		$racetime = $value["time"];
	}
}
$lastracetime = $racetime;
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
// $venue= isset($_GET['venue']) ? $_GET['venue'] : $mtgVenue;
$venue= $mtgVenue;
$venueLong=$venueLong;
$start="1";
$end=$mtgTotalRace;

$date1 = new DateTime (date("Y-m-d"));
$date2 = new DateTime ($racingdate);
$interval = $date1->diff ($date2);
//---------------------------------------------------------------------------------------------------------------------------------

$racecard_sql = "select COUNT(*) as cnt from RaceCard where racingdate='".$racingdate."' and venue='".$venue."'";
$racecard_sql = "select * from RaceCard where racingdate='".$racingdate."' and venue='".$venue."'";
$racecard_sql = "DELETE from RaceCard where racingdate='".$racingdate."' and venue='".$venue."'";
// echo $racecard_sql ;
$racecard_sql = mysqli_query($mysqli, $racecard_sql);
// $save_racecard = mysql_num_rows($racecard_sql) ? 1 : 0;
// $save_racecard = mysqli_fetch_object($racecard_sql);
// echo $save_racecard->cnt;
// $nofrec = $racecard_sql->num_rows;

// die(1);
if(1){
//  if($nofrec==0){ //-----------------------------------------------------------------------------------------------------------------------------------------
	// echo $_GET['save2db'].$save_racecard;
	$logs .= "-racecard2db-".$venue;
	$insert = "	INSERT INTO logs
		VALUES(0,'ai-updateRaceCard-daily',now())";
// echo $insert;
	mysqli_query($mysqli, $insert);
// 	die(1);
	$rowData = array();
	$data=0;    
	$update_RaceCard = "";
	for ($raceno = $start; $raceno <= $end; $raceno++) {
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
			// echo "-".$htmls->find('//p[class="info"]')->innertext;			

			foreach($htmls->find('//table[class="draggable"] tr') as $row){
				foreach($row->find('td, th') as $cell) {
					// if(isset($cell->href)){
				// 		if(substr(trim($cell->innertext),0,1)=="<"){
        				$html_tag = $cell->innertext;
						if (strpos($html_tag, '<img') !== false) {
						  //  echo $html_tag;
							$rowData[$raceno][$cnt][] = ""; //$cell->find('a', 0)->innertext;
						}else{		
							$str = str_replace('<span class="color_red">',"",$cell->innertext);
							$str = str_replace("<span>","",$str);
							$rowData[$raceno][$cnt][] = $str;
						}
				}
				$cnt++;				
			}
		}else{
			foreach($htmls->find('//table[class="starter f_tac f_fs13 draggable hiddenable"]  tr') as $row){	
				// print_r($row);	
                echo 1;	
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
					echo $raceno."-".$cnt."-".$cell->innertext."-".$rowData[$raceno][$cnt]."<br>";
				}
				$cnt++;
			}
		}
	}
	// print_r($rowData);
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
	$sql = "INSERT INTO `racecard` VALUES 
	".$update_RaceCard;
	$sql = "INSERT INTO `RaceCard` (`id`, `racingdate`, `venue`, `raceno`, `HorseNo`, `Last6Runs`, `Colour`, `horsename`, `BrandNo`, `Wt`, `Jockey`, `OverWt`, `Draw`, `Trainer`, `IntlRtg`, `Rtg`, `Rtgdiff`, `HorseWt`, `HorseWtDiff`, `BestTime`, `Age`, `WFA`, `Sex`, `SeasonStakes`, `Priority`, `Gear`, `Owner`, `Sire`, `Dam`, `ImportCat`) VALUES 
	".$update_RaceCard;
	// $sql = "INSERT INTO RaceCard (id, racingdate, venue, raceno, HorseNo, Last6Runs, Colour, horsename, BrandNo, Wt, Jockey, OverWt, Draw, Trainer, IntlRtg, Rtg, Rtgdiff, HorseWt, HorseWtDiff, BestTime, Age, WFA, Sex, SeasonStakes, Priority, Gear, Owner, Sire, Dam, ImportCat) VALUES 
	// 	".$update_RaceCard;
	// echo $sql."<br>";
	// die(1);
	// if($racingdate == date("Y-m-d")) {
		$update = mysqli_query($mysqli, $sql);
		$error_message = mysqli_error($mysqli);

		if($error_message == ""){
			// echo "No error related to SQL query.";
			$recipients_BCC = array(
				'hkhorsepaper@gmail.com' => '.'
			
				// ..
			 );
			// $ipAddress = substr($_SERVER['REMOTE_ADDR'],-3);
			$ipAddress = substr($_SERVER['SERVER_NAME'],-3);
			mailto('HK Horse Paper :'.$ipAddress,'RaceCard were updated.$racingdate.$venue','3','',$recipients_BCC,'4');
		}else{
			echo "Query Failed: ".$error_message;
		}
		// mysqli_close($mysqli);
	// }
	// print_r($RaceCard_array);
}


?>