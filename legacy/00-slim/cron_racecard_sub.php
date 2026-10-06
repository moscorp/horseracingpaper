<?php

// error_reporting(E_ALL);
include_once ("lib/func_basedata.php");
include_once ("lib/func_mailer_gmail.php");
include_once ("lib/constants.php");
include_once ("lib/func_aii.php");

$oneyear = date("Y-m-d",strtotime("-2 year"));
$onemonth = date("Y-m-d",strtotime("-1 month"));
$oneweek = date("Y-m-d",strtotime("-1 week"));

$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$logs = "";
if (!$mysqli) {
    die('DATABASE ERROR: ' . mysqli_connect_errno());
}
//---------------------------------------------------------------------------------------------------------------------------------

// Fetch data from RaceCard
$racecard_sql = "SELECT racingdate, venue, raceno, HorseNo,horsename,BrandNo,Jockey,Trainer,Sire,BestTime,Wt FROM RaceCard where racingdate>'2025-09-24'";
$racecard_data = mysqli_query($mysqli, $racecard_sql);

// Fetch data from RaceCard_sub
$racecard_sub_sql = "SELECT racingdate, venue, raceno, HorseNo,horsename,BrandNo,Jockey,Trainer,Sire,BestTime,Wt FROM RaceCard_sub where racingdate>'2025-09-24'";
$racecard_sub_data = mysqli_query($mysqli, $racecard_sub_sql);

// Store RaceCard_sub data in an associative array for easy lookup
$racecard_sub_array = [];
while ($row = mysqli_fetch_assoc($racecard_sub_data)) {
    $key = "{$row['racingdate']}_{$row['venue']}_{$row['raceno']}_{$row['horseno']}_{$row['BrandNo']}";
    $racecard_sub_array[$key] = true; // Use the composite key as an indicator
}

// Prepare to process RaceCard data
while ($data = mysqli_fetch_assoc($racecard_data)) {
    $key = "{$data['racingdate']}_{$data['venue']}_{$data['raceno']}_{$data['horseno']}_{$data['BrandNo']}";
    
    // Check if the current record from RaceCard is not in RaceCard_sub
    if (!isset($racecard_sub_array[$key])) {
        $raceno = $data['raceno'];
        $horseno = $data['HorseNo'];
        $racingdate = $data['racingdate'];
        $venue = $data['venue'];
        $jockeyshort = explode(' (', $data['Jockey']);
        $besttime = $data['BestTime'];
        $trainer = $data['Trainer'];

        // Store the required data in the racecard array
        $racecard[$racingdate][$venue][$raceno][$horseno]['brandno'] = $data['BrandNo'];
        $racecard[$racingdate][$venue][$raceno][$horseno]['jockey'] = $jockeyshort[0];
        $racecard[$racingdate][$venue][$raceno][$horseno]['trainer'] = $data['Trainer'];
        
        $BrandNo[] = $data['BrandNo'];
        $horsenamearray[] = $data['horsename'];
        
        $sire[$racingdate][$venue][] = ['Sire' => $data['Sire'], 'raceno' => $raceno, 'horseno' => $horseno];
        
        $current_brandno = $row['brandno'];
        
        $jockeyshort = explode(' (', $row['jockey']);
        $jockeychange[$date][$venue][$raceno][$jockeyshort[0]] = $horseno;
        
        $rc_trainer[$date][$venue][$raceno][$horseno]['trainer'] = $row['trainer'];
        
        $best_time[$date][$venue][$raceno][$horseno] = $besttime;
        $Wt[$date][$venue][$raceno][$horseno] = $row['Wt'];
        
        if(!empty($besttime) && $besttime <> '-'){
            if (!isset($best_time_min[$date][$venue][$raceno]) || $besttime < $best_time_min[$date][$venue][$raceno]) {
                $best_time_min[$date][$venue][$raceno] = $besttime;
            }
        }
        // Count the number of trainers for each race
        // if (!isset($trainer_count[$date][$venue][$raceno][$trainer])) {
        //     $trainer_count[$date][$venue][$raceno][$trainer] = 1;
        // } else {
        //     $trainer_count[$date][$venue][$raceno][$trainer]++;
        // }
        // Count the ttl number of trainers for each race
        // $jockey_count_ttl[$date][$venue][$jockeyshort[0]]++;
        // $trainer_count_ttl[$date][$venue][$trainer]++;
        // $jt = $jockeyshort[0].$trainer;
        // $jockey_trainer_count[$date][$venue][$jt]++;
        // // echo $date.$venue.$jockey_trainer_count[$date][$venue][$jt]."<br>";
        
        // $brandno[$date][$venue][] = $brandno;
        // $racecard[$date][$venue]['brandno'][] = $current_brandno; // C
        
    }
}

$sireCounts = []; // Initialize an array to hold counts and associated data

// Iterate through the existing $sire array
foreach ($sire as $racingdate => $venues) {
    foreach ($venues as $venue => $entries) {
        foreach ($entries as $entry) {
            $sireName = $entry['Sire'];
            $raceno = $entry['raceno'];
            $horseno = $entry['horseno'];
            
            // Initialize if sire not already set
            if (!isset($sireCounts[$sireName])) {
                $sireCounts[$sireName] = [
                    'count' => 0, // To count occurrences
                    'details' => [] // To hold raceno and horseno
                ];
            }

            // Increment count for this sire
            $sireCounts[$sireName]['count']++;
            
            // Add raceno and horseno to the details array
            $sireCounts[$sireName]['details'][] = [
                'raceno' => $raceno,
                'horseno' => $horseno
            ];
        }
    }
}

// Filter to keep only those sires with a count greater than 1
$filteredSireCounts = array_filter($sireCounts, function($info) {
    return $info['count'] > 1;
});

// Iterate through the filtered results if needed
foreach ($filteredSireCounts as $sire => $info) {
    $firstChar = mb_substr($sire, 0, 1);
    // echo "Sire: $sire, Count: {$info['count']}\n";
    foreach ($info['details'] as $detail) {
        // echo "Race No: {$detail['raceno']}, Horse No: {$detail['horseno']}\n";
        $plural_sire[$racingdate][$venue][$detail['raceno']][$detail['horseno']] = "(".$firstChar.$info['count'];
    }
}

// print_r($plural_sire);
if (!empty($horsenamearray)) {
    // print_r($horsenamearray);
    // Sanitize and prepare the horse names for the SQL query
    $horsenamearray = array_map(function($name) use ($mysqli) {
        return "'" . mysqli_real_escape_string($mysqli, $name) . "'";
    }, $horsenamearray);
    
    // Remove duplicates if necessary
    $horsenamearray = array_unique($horsenamearray);
    
    // Create a comma-separated string
    $horsenamearray = implode(',', $horsenamearray);

    $barriersql = "SELECT 
            br.Horse,
            br.barrierday,
            br.Jockey,
            br.RunningPosition,
            br.Comment
        FROM 
            barrierresult br
        INNER JOIN (
            SELECT 
                Horse, 
                MAX(barrierday) AS latest_barrierday
            FROM 
                barrierresult
            WHERE 
                Horse IN ($horsenamearray)
            GROUP BY 
                Horse
        ) AS latest_results ON br.Horse = latest_results.Horse 
                             AND br.barrierday = latest_results.latest_barrierday";
    // echo $barriersql;
    $barrierresult = $mysqli->query($barriersql);

    // Check if any horse info data was returned
    if ($barrierresult && mysqli_num_rows($barrierresult) > 0) {
        while ($barrier = mysqli_fetch_assoc($barrierresult)) {
            $horsename = $barrier['Horse'];
            $barrierday = $barrier['barrierday'];
            $Comment = $barrier['Comment'];
            $RunningPosition = $barrier['RunningPosition'];
            $Jockey = $barrier['Jockey'];
            
            $barrierinfo[$horsename]['barrierday'] = $barrierday;
            $barrierinfo[$horsename]['Comment'] = $Comment;
            $barrierinfo[$horsename]['Jockey'] = $Jockey;
            $barrierinfo[$horsename]['RunningPosition'] = $RunningPosition;
        }
    }
}

// print_r($BrandNo);
if (!empty($BrandNo)) {
    // Prepare the BrandNo for the SQL query
    // Use implode to create a comma-separated string
    // $brandNoImploded = implode(',', array_map('intval', $BrandNo)); // Ensure all values are integers

    $brandNoImploded = "'" . implode("','", $BrandNo) . "'";
// /*(select count(*) from barrierresult where horse=a.horsename and barrierday>a.date) as cntafterbarrier*/
    $today = date("Y-m-d");
    // Construct the SQL query safely
    $horseinfosql = "SELECT DISTINCT CONCAT(horseid, RaceIndex) AS unique_horse_race,Date,
                        RaceIndex,horseid,horsename, Pla,RC_Track_Course,Dist,G,RaceClass,Dr,Rtg,Trainer,Jockey,LBW,Win_Odds,Act_Wt,Running_Position,Finish_Time,Declar_Wt,Gear
                        FROM horseinfo as a
                        WHERE horseid IN ($brandNoImploded) 
                        and date>'$oneyear'
                        and date<>'$today' 
                        order by date desc, horseid,RaceIndex";
    $horseinfosql = "
                SELECT DISTINCT CONCAT(a.horseid, a.RaceIndex) AS unique_horse_race, a.Date,
                                a.RaceIndex, a.horseid, a.horsename, a.Pla, a.RC_Track_Course, a.Dist, a.G, a.RaceClass, a.Dr, a.Rtg, a.Trainer, a.Jockey, a.LBW, a.Win_Odds, a.Act_Wt, a.Running_Position, a.Finish_Time, a.Declar_Wt, a.Gear,
                                COUNT(b.horse) AS cntafterbarrier
                FROM horseinfo AS a
                LEFT JOIN barrierresult AS b ON b.horse = a.horsename AND b.barrierday > a.date
                WHERE a.horseid IN ($brandNoImploded) 
                AND a.date > '$oneyear'
                AND a.date <> '$today' 
                GROUP BY a.horseid, a.RaceIndex
                ORDER BY a.date DESC, a.horseid, a.RaceIndex";

    // echo $horseinfosql;
    // Execute the horse info query
    $horseinforesult = $mysqli->query($horseinfosql);

    // Check if any horse info data was returned
    if ($horseinforesult && mysqli_num_rows($horseinforesult) > 0) {
        $Rtgcomparecnt = []; // Initialize the counter for comparisons
        $Rtgtrend = []; // Array to hold Rtg trends
        $previousValues = []; // Array to hold the previous values for comparison
        while ($horseinfo = mysqli_fetch_assoc($horseinforesult)) {
            $horseid = $horseinfo['horseid'];
            $pla = $horseinfo['Pla'];
            $hrinfodate = $horseinfo['Date'];
            $jockey = $horseinfo['Jockey'];
            $trainer = $horseinfo['Trainer'];
            $dist = $horseinfo['Dist'];
            $Win_Odds = $horseinfo['Win_Odds'];
            $Rtg = $horseinfo['Rtg'];
            $horseinfo_cntafterbarrier[$horseinfo['horsename']] = $horseinfo['cntafterbarrier'];
            
            // Initialize $Rtgcomparecnt for each horse if not already set
            if (!isset($Rtgcomparecnt[$horseid])) {
                $Rtgcomparecnt[$horseid] = 0;
            }

            // Store values for comparison
            if (!isset($Rtgcomparecnt[$horseid]) || $Rtgcomparecnt[$horseid] < 3) {
                // Store current row's values for later comparison
                $previousValues[$horseid][$Rtgcomparecnt[$horseid]] = ['Rtg' => $Rtg, 'Pla' => $pla];
                $Rtgtrend[$horseid] = (
                    $previousValues[$horseid][0]['Rtg'] < $previousValues[$horseid][1]['Rtg'] && 
                    $previousValues[$horseid][1]['Rtg'] < $previousValues[$horseid][2]['Rtg'] &&  
                    $previousValues[$horseid][0]['Pla'] < $previousValues[$horseid][1]['Pla'] && 
                    $previousValues[$horseid][1]['Pla'] < $previousValues[$horseid][2]['Pla']
                ) ? $previousValues[$horseid][0]['Pla'] : ""; // Store 1st row's Pla or reset
            
                // echo $horseid."-".$Rtgcomparecnt[$horseid]."-0".$previousValues[$horseid][0]['Rtg']."-1".$previousValues[$horseid][1]['Rtg']."-2".$previousValues[$horseid][2]['Rtg']."<br>";
            }
            
            $Rtgcomparecnt[$horseid]++;
            
            // $Rtgcomparecnt++; // Increment the counter
                
            if (empty($laterestjockey[$horseid])) {
                $laterestjockey[$horseid] = $jockey;
                $lateresttrainer[$horseid] = $trainer;
                $laterestdist[$horseid] = $dist;
                $laterestWin_Odds[$horseid] = $Win_Odds;
                $laterestpla[$horseid] = $pla;
            }
            
            if($horseinfo['unique_horse_race'] <> $tmp_unique_horse_race){
                // $horseinfo_datepla[$horseid]['date'] = $hrinfodate;
                $horseinfo_datepla[$horseid][$hrinfodate] = $pla;
                
                $horseinfo_jockeyinpla[$horseid][] = ($pla<4) ? $jockey : "";
                $horseinfo_distinpla[$horseid][] = ($pla<4) ? $dist : "";
                // if($horseid == 'H034' && $dist =='1600'){
                //     echo $horseid.$dist.$jockey.$horseinfo['Running_Position']."-".determinePattern($horseinfo['Running_Position'])."<br>";
                // }
                $Running_Position[$horseid][$dist] .= determinePattern($horseinfo['Running_Position']);
                $Running_Position_jockey[$horseid][$dist] .= $jockey.";";
                
                $Running_Positions[$horseid][$dist]['position'] .= determinePattern($horseinfo['Running_Position']);
                $Running_Positions[$horseid][$dist]['jockeys'] .= $jockey.";";
                $Running_Positions[$horseid][$dist]['pla'] .= $pla.";";
                
                //---------------------------
                $horseinfo_jockeyinpla_cnt[$horseid]++;
                $horseinfo_jockeyinpla_cntpla[$horseid] = ($pla<4) ? $horseinfo_jockeyinpla_cntpla[$horseid]+1 : $horseinfo_jockeyinpla_cntpla[$horseid];
                
            }
            $tmp_unique_horse_race = $horseinfo['unique_horse_race'];
        }
    } else {
        // echo "E179";    //"No horse info data found.";
    }
} else {
    // echo "E182";    //"No BrandNo found, cannot query horse info.";
}
// print_r($Rtgtrend);
// print_r($Running_Position_jockey);
$merged_position = mergeRunningPositions($Running_Position, $Running_Position_jockey);
print_r($horseinfo_jockeyinpla);


























if($cnt && $update_RaceCardxx){
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


?>