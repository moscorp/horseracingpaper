<?php

// error_reporting(E_ALL);
include_once ("lib/func_basedata.php");
include_once ("lib/func_mailer_gmail.php");
include_once ("lib/constants.php");

$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
mysqli_set_charset($mysqli, "utf8mb4");
$logs = "";
if (!$mysqli) {
    die('DATABASE ERROR: ' . mysqli_connect_errno());
}
//---------------------------------------------------------------------------------------------------------------------------------

$racecard_sql = "SELECT CONCAT(racingdate, venue, raceno, horseno) AS recdata FROM RaceCard";
$racecard_data = mysqli_query($mysqli, $racecard_sql);

if (!$racecard_data) {
    // Handle query error
    die('Query Error: ' . mysqli_error($mysqli));
}

$racecard_recs = []; // Initialize an array to hold all records

while ($racecard_rec = mysqli_fetch_assoc($racecard_data)) {
    $racecard_recs[] = $racecard_rec['recdata']; // Add each record to the array
}

// print_r($racecard_recs);

// $raceingdate = $venueCode = $resultOddsType = "";
$basedata = basedata($raceingdate,$venueCode,$resultOddsType);
// print_r($basedata);

$activeMeetings = $basedata['data']['activeMeetings'];
    foreach ($activeMeetings as $meeting) {
        $allvenue[] = [
            'date' => $meeting['date'],
            'venueCode' => $meeting['venueCode'],
        ];
    }

// print_r($allvenue);
$cnt = 0;
foreach ($allvenue as $rsdata => $rsdetail) {
    $raceMeetings = $basedata['data']['raceMeetings'][$rsdata]['races'];
    
    $racingdate = $basedata['data']['raceMeetings'][$rsdata]['date'];
    $venue = $basedata['data']['raceMeetings'][$rsdata]['venueCode'];
    $update_RaceCard ="";
    // echo $racingdate.$venue."..<br>";
    foreach ($raceMeetings as $race) { 
        $raceno = $race['no'];
        $status = $race['status'];
        list($date, $timefull) = explode('T', $race['postTime']);
        // print_r($race['runners']);
        foreach ($race['runners'] as $racedetail) {
            $horseno[$racingdate][$venue][$raceno] = is_numeric($racedetail['no']) ? $racedetail['no'] * 1 : "";
            $last6run[$racingdate][$venue][$raceno] = $racedetail['last6run'];
            $color[$racingdate][$venue][$raceno] = $racedetail['color'];
            $name_ch[$racingdate][$venue][$raceno] = $racedetail['name_ch'];
            $BrandNo[$racingdate][$venue][$raceno] = $racedetail['horse']['code'];
            $handicapWeight[$racingdate][$venue][$raceno] = $racedetail['handicapWeight'];
            $jockey[$racingdate][$venue][$raceno] = $racedetail['jockey']['name_ch'];
            $OverWt[$racingdate][$venue][$raceno] = $racedetail['OverWt'];
            $Draw[$racingdate][$venue][$raceno] = $racedetail['barrierDrawNumber'];
            $Trainer[$racingdate][$venue][$raceno] = $racedetail['trainer']['name_ch'];
            $IntlRtg[$racingdate][$venue][$raceno] = $racedetail['internationalRating'];
            $Rtg[$racingdate][$venue][$raceno] = $racedetail['currentRating'];
            $Rtgdiff[$racingdate][$venue][$raceno] = $racedetail['Rtgdiff'];
            $HorseWt[$racingdate][$venue][$raceno] = $racedetail['currentWeight'];
            $HorseWtDiff[$racingdate][$venue][$raceno] = $racedetail['HorseWtDiff'];
            $BestTime[$racingdate][$venue][$raceno] = $racedetail['BestTime'];
            $Age[$racingdate][$venue][$raceno] = $racedetail['Age'];
            $WFA[$racingdate][$venue][$raceno] = $racedetail['WFA'];
            $Sex[$racingdate][$venue][$raceno] = $racedetail['Sex'];
            $SeasonStakes[$racingdate][$venue][$raceno] = $racedetail['SeasonStakes'];
            $Priority[$racingdate][$venue][$raceno] = $racedetail['priority'];
            $Gear[$racingdate][$venue][$raceno] = $racedetail['gearInfo'];
            $Owner[$racingdate][$venue][$raceno] = $racedetail['Owner'];
            $Sire[$racingdate][$venue][$raceno] = $racedetail['Sire'];
            $Dam[$racingdate][$venue][$raceno] = $racedetail['Dam'];
            $ImportCat[$racingdate][$venue][$raceno] = $racedetail['trumpCard'];

            $chk_saved = $racingdate.$venue.$raceno.$horseno[$racingdate][$venue][$raceno];
            $horsenochk = $horseno[$racingdate][$venue][$raceno];
            // echo $chk_saved."<br>";
            if(in_array($chk_saved, $racecard_recs)){
                // echo $chk_saved.$horseno[$racingdate][$venue][$raceno]."<br>";
            }else{
                if (is_numeric($horsenochk)) {
    				$update_RaceCard .= '(NULL, "'.$racingdate.'", "'.$venue.'", "'.$raceno.'", "'.
    					$horseno[$racingdate][$venue][$raceno].'","'.
    					$last6run[$racingdate][$venue][$raceno].'","'.
    					"-".'","'.
    					$name_ch[$racingdate][$venue][$raceno].'","'.
    					$BrandNo[$racingdate][$venue][$raceno].'","'.
    					$handicapWeight[$racingdate][$venue][$raceno].'","'.
    					$jockey[$racingdate][$venue][$raceno].'","'.
    					"-".'","'.
    					$Draw[$racingdate][$venue][$raceno].'","'.
    					$Trainer[$racingdate][$venue][$raceno].'","'.			
    					"-".'","'.
    					$Rtg[$racingdate][$venue][$raceno].'","'.
    					"-".'","'.
    					$HorseWt[$racingdate][$venue][$raceno].'","'.
    					"-".'","'.
    					"-".'","'.
    					$Age[$racingdate][$venue][$raceno].'","'.
    					"-".'","'.
    					$Sex[$racingdate][$venue][$raceno].'","'.
    					$SeasonStakes[$racingdate][$venue][$raceno].'","'.					
    					$Priority[$racingdate][$venue][$raceno].'","'.
    					$Gear[$racingdate][$venue][$raceno].'","'.
    					$Owner[$racingdate][$venue][$raceno].'","'.
    					$Sire[$racingdate][$venue][$raceno].'","'.
    					$Dam[$racingdate][$venue][$raceno].'","'.
    					$ImportCat[$racingdate][$venue][$raceno].'","'.
    					$result[$racingdate][$venue][$raceno].'"),'; 
    					$cnt++;
                }else{
                    // echo "nohorseno".$racingdate.$venue.$raceno."-".$horseno[$racingdate][$venue][$raceno]."<br>";
                }
            }
        }
    }
    
    if($cnt && $update_RaceCard){
    	$update_RaceCard = substr($update_RaceCard,0,strlen($update_RaceCard)-1).";";
        	$sql = "INSERT INTO `RaceCard` VALUES 
        	".$update_RaceCard;
        // 	$sql = "INSERT INTO `RaceCard` (`id`, `racingdate`, `venue`, `raceno`, `HorseNo`, `Last6Runs`, `Colour`, `horsename`, `BrandNo`, `Wt`, `Jockey`, `OverWt`, `Draw`, `Trainer`, `IntlRtg`, `Rtg`, `Rtgdiff`, `HorseWt`, `HorseWtDiff`, `BestTime`, `Age`, `WFA`, `Sex`, `SeasonStakes`, `Priority`, `Gear`, `Owner`, `Sire`, `Dam`, `ImportCat`, `result`) VALUES 
        // 	".$update_RaceCard;
    // 	echo $sql.$cnt;
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
                //--------------------------------------------------------------------
                $msg = 'ai-racecard2-'.$cnt;
                $insert = "	INSERT INTO logs
                VALUES(0,'$msg',now())";
                echo $insert;
                mysqli_query($mysqli, $insert);
                //--------------------------------------------------------------------	
    
    		}else{
    			echo "Query Failed: ".$error_message;
    		}
    		// mysqli_close($mysqli);
    	// }
    	// print_r($RaceCard_array);
    }
}




?>