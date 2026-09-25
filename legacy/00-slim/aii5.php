<?php
error_reporting(E_ERROR | E_PARSE);

include_once ("lib/func_aii.php");
include_once ("lib/func_basedata.php");
include_once ("lib/func_mailer_gmail.php");
include_once ("lib/constants.php");

$mysqli = mysqli_connect(DB_HOSTwp, DB_USERwp, DB_PASSwp, DB_NAMEwp);
mysqli_set_charset($mysqli, "utf8mb4");

$oneyear = date("Y-m-d",strtotime("-2 year"));
$onemonth = date("Y-m-d",strtotime("-1 month"));
$oneweek = date("Y-m-d",strtotime("-1 week"));
$yesterday = date("Y-m-d",strtotime("-1 day"));

$ismm = (isset($_GET['mm']) && $_GET['mm']) ? 1 : 0;
$istext = (isset($_GET['text']) && $_GET['text']) ? 1 : 0;
// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
//---------------------------------------------------------------------------------------------------------------------------------------
// switch ($venue){
//     case "HV" : 
//     case "ST" : 
        // $hint .="4x top same > bottom; <br>";
        // $hint .="4x top biggerest > next 4x and top of same 2x; <br>";
        // $hint .="4x =2 biggerest > up1w if 4x, next big same 2x; <br>";
        // $hint .="4x >2 biggerest > up1w, next big same 2x and smaller w2x; <br>";
        
        // $hint .="3x middle biggerest > cool(small same 2x 3x; <br>";
        // $hint .="3x middle x3 > pre raise curr; <br>";
        // $hint .="2x middle same, big > top; <br>";
        // $hint .="2x middle same, 3more > top and top; <br>";
        // $hint .="28 middle same, pick both; <br>";
        // $hint .="white 2x middle, inside yellow, no 3*2x or 3x; <br>";
        // $hint .="19 > upper2.2x pick upper2x or both; <br>";
        // $hint .="19 > big3x;(if 2x, then pick bigger_raise 2x first) <br>";
        // $hint .="b19 > upper2x pick2x; <br>";
        // $hint .="18,19 > middle 17 pick 17 > bu first big?x; <br>";
        // $hint .="17,18 > pick 18 up1(w2); <br>";
        // $hint .="2.5x > pick both 5x; <br>";
        // $hint .="pre1 > big2up small2down & rem[16]; <br>";
        
        $hint .= "<font color=red>B5=top,B4=next, 2.2x=B2xup+Sm2Xdown, 2.3x=up3x</font><br>";  //*****
        $hint .= "<font color=red>XGap:2x=top, 3x=bottom;</font><br>";  //*****
        $hint .= "<font color=red>W3 next to W4orW5; if below half W4 then lower W2; B2u W3 next to B3; </font><br>";  //*****
        //-----------------------------------------------------------------------
        $hint .= "ST:  <br>";
        $hint .= "***start from smaller pre pla   <br>";
        $hint .= "(bottom of if 4x or top3x)<br>";
        $hint .= "(upper big2x)(bigW2,W3 inside 2x and lowerW2)(halfdown 36,24,26 then 24, 36,26,24 then 36, 36,26,25,24 then hot) <br>";
        $hint .= "<font color=red>(if top=B4 then upper bigX or WX)+(B4)+(both big3X)+(lower W inside x)+(mid3.2X)+(midsmall)+(big3X) </font><br>";  //*****
        $hint .= "<hr>";//---------------------------------------------------------------------------
        $hint .= "HV:  <br>";
        $hint .= "***select from BIGGER pre pla   <br>";
        $hint .= "<font color=red>(first single W3)(top inside X)(sm2 inside 3)(if top=B4 then upper x)(upofbottom2.2x)(b2u,nextofbig2W)</font><br>";  //*****
        $hint .= "<font color=blue>B4=next+bottominside3x+B3ifhigher&contain3x; B5=top</font><br>";  //*****
        $hint .= "halfup middle smallW; if 2.3x then B3xtopS3Xbottom; **31** <br>";
        $hint .= "(bigW inside 3x and bottomW)(**smallerW2 below middle4) <br>";
        $hint .= "pick bigW4 only 2 more W4, if 4x below W4 the upper 4x=pla <br>";
        $hint .= "<hr>";//---------------------------------------------------------------------------
        $hint .= "AU:  <br>";
        $hint .= "(nami then t2, boko then sm2;)(bottom 28)(2nd of 3.2x)  <br>";
        $hint .= "***select from bottomX **** (when bigW inside then no smallX)   <br>";
        $hint .= "JP:<br>";//---------------------------------------------------------------------------
        $hint .= "4x3xbigUsmallD; if3.2x mid2x<br>";
        $hint .= "Fr:<br>";//---------------------------------------------------------------------------
        $hint .= "T4=cool<br>";
//     break;
// }
    
$query = "select * from hrp_propanalysis order by venue,proptop desc, prop desc";
$data = mysqli_query($mysqli, $query);        
if(mysqli_num_rows($data)>0){
    echo "<table border=1>";
    while($prop = mysqli_fetch_assoc($data)){
        $propanalysis[$prop['venue']][$prop['proptop']][$prop['prop']][$prop['samecnt']][$prop['isbigger']][['issmaller']][$prop['pos']][$prop['same1x']][$prop['same2x']][$prop['same3x']] = $prop['pick'];
        // echo "<td>".$prop['venue'];
        // echo "<td>".$prop['proptop'];
        // echo "<td>".$prop['prop'];
        // echo "<td>".$prop['samecnt'];
        // echo "<td>".$prop['isbigger'];
        // echo "<td>".$prop['issmaller'];
        // echo "<td>".$prop['pos'];
        // echo "<td>".$prop['same1x'];
        // echo "<td>".$prop['same2x'];
        // echo "<td>".$prop['same3x'];
        // echo "<td>".$prop['predict'];
        // echo "<td>".$prop['pick'];
        // echo "<td>".$prop['nopick'];
    }
}
// print_r($propanalysis);

//-----------------------------------------------------------------------------------------------------------
function bettingflow($racingdate,$venue){
    $sql = "
        SELECT 
        r1.startTime, 
        r1.venue, 
        r1.race_no, 
        r1.horse_no, 
        r1.win_value, 
        r1.win_investment, 
        r1.place_value, 
        r1.place_investment, 
        r1.sourcetimefull,
        DATE_FORMAT(r1.sourcetimefull, '%Y%m%d%H%i') AS sourcetime 
    FROM 
        racedata_stheadline r1 
    JOIN (
        SELECT 
            venue, 
            race_no, 
            horse_no, 
            HOUR(sourcetimefull) AS hour_source, 
            MAX(sourcetimefull) AS max_source_time 
        FROM 
            racedata_stheadline 
        WHERE 
            startTime >= ('$racingdate' - INTERVAL 1 DAY)
        GROUP BY 
            venue, 
            race_no, 
            horse_no, 
            HOUR(sourcetimefull) 
    ) r2 ON 
        r1.venue = r2.venue 
        AND r1.race_no = r2.race_no 
        AND r1.horse_no = r2.horse_no 
        AND r1.sourcetimefull = r2.max_source_time 
    WHERE 
        (r1.race_date = '$racingdate' - INTERVAL 1 DAY OR r1.race_date = '$racingdate') 
        AND r1.startTime = '$racingdate' 
        AND r1.venue = '$venue' 
    ORDER BY 
        r1.venue, 
        r1.race_no, 
        r1.horse_no, 
        r1.sourcetimefull;
    ";
    return $sql;
}
//-----------------------------------------------------------------------------------------------------------
// SQL query to get the latest racing date and venue along with their details
$racecardsql = "SELECT * FROM RaceCard  
                 WHERE (racingdate, venue) = (
                     SELECT racingdate, venue FROM RaceCard  
                     WHERE venue IN ('HV', 'ST')
                     ORDER BY racingdate DESC LIMIT 1
                 )";
// echo $racecardsql;
// Execute the query
$racecarddata = $mysqli->query($racecardsql);

// Initialize the racecard array
$racecard = [];
$horsenamearray = [];

// Check if any rows were returned
if ($racecarddata && mysqli_num_rows($racecarddata) > 0) {
    while ($data = mysqli_fetch_assoc($racecarddata)) {
        $raceno = $data['raceno'];
        $horseno = $data['HorseNo'];
        $racingdate = $data['racingdate'];
        $venue = $data['venue'];
        $jockeyshort = explode(' (', $data['Jockey']);

        // Store the required data in the racecard array
        $racecard[$racingdate][$venue][$raceno][$horseno]['brandno'] = $data['BrandNo'];
        $racecard[$racingdate][$venue][$raceno][$horseno]['jockey'] = $jockeyshort[0];
        $racecard[$racingdate][$venue][$raceno][$horseno]['trainer'] = $data['Trainer'];
        
        $BrandNo[] = $data['BrandNo'];
        $horsenamearray[] = $data['horsename'];
        
        $sire[$racingdate][$venue][] = ['Sire' => $data['Sire'], 'raceno' => $raceno, 'horseno' => $horseno];
    }
} else {
    echo "No racecard data found.";
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

    // echo $horseinfosql1;
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
// print_r($merged_position);



function racecardinfo($racingdate,$venue){
    global $mysqli; // Ensure that $mysqli is accessible in the function
    

    // Return the collected arrays
    return [
        'racecard' => $racecard,
        'jockeychange' => $jockeychange,
        'besttime_min' => $besttime_min,
        'rc_trainer' => $rc_trainer,
        'trainer_count' => $trainer_count,
        'jockey_count_ttl' => $jockey_count_ttl,
        'trainer_count_ttl' => $trainer_count_ttl,
        'jockey_trainer_count' => $jockey_trainer_count
    ];
}



$basedata = basedata($raceingdate,$venueCode,$resultOddsType);
// print_r($basedata);

$activeMeetings = $basedata['data']['activeMeetings'];
$Meetings .= "<table>
    <thead>
        <tr>
            <th>Meeting ID</th>
            <th>Venue Code</th>
            <th>Date</th>
            <th>Status</th>
            <th>Races</th>
        </tr>
    </thead>
    <tbody>";
    
    foreach ($activeMeetings as $meeting) {
        $Meetings =  "<tr>";
        $Meetings .= "<td>{$meeting['id']}</td>";
        $Meetings .= "<td>{$meeting['venueCode']}</td>";
        $Meetings .= "<td>{$meeting['date']}</td>";
        $Meetings .= "<td>{$meeting['status']}</td>";
        $Meetings .= "<td><ul>";
        foreach ($meeting['races'] as $race) {
            $Meetings .= "<li>Race {$race['no']}: Post Time: {$race['postTime']} - Status: {$race['status']} (Wagering Field Size: {$race['wageringFieldSize']})</li>";
        }
        $Meetings .= "</ul></td>";
        $Meetings .= "</tr>";
        $allvenue[] = [
            'date' => $meeting['date'],
            'venueCode' => $meeting['venueCode'],
        ];
    }
$Meetings .= "</tbody>";
$Meetings .= "</table>";

// echo $Meetings;

$oddsMerged = [];
$prophistcurr1st = []; // Ensure $prop is an array before use
foreach ($allvenue as $rsdata) {
    $odds = [];
    $date = $rsdata['date'];
    $venueCode = $rsdata['venueCode'];
    
    //--------------------------------------------------------------------------------------------------------------
    
    // $query = "
    //     SELECT venue, raceno, horseno, prop, prop_curr, rectime 
    //     FROM hrp_prophist 
    //     WHERE racingdate='$date' AND (raceno, horseno, rectime) IN (
    //         SELECT raceno, horseno, MIN(rectime) 
    //         FROM hrp_prophist 
    //         WHERE racingdate='$date' 
    //         GROUP BY raceno, horseno
    //     )
    //     ORDER BY raceno, horseno, rectime
    // ";
// $query = "
//     SELECT venue, raceno, horseno, 
//           MIN(prop_curr) AS min_prop_curr, 
//           MAX(prop_curr) AS max_prop_curr
//     FROM hrp_prophist 
//     WHERE racingdate = '$date' 
//     AND rectime IN (
//         SELECT MIN(rectime) 
//         FROM hrp_prophist 
//         WHERE racingdate = '$date' 
//         GROUP BY raceno, horseno
//     )
//     GROUP BY venue, raceno, horseno
//     ORDER BY raceno, horseno
// ";
// $query = "
//     SELECT hp.venue, hp.raceno, hp.horseno,
//           MIN(hp.prop_curr) AS min_prop_curr,
//           MAX(hp.prop_curr) AS max_prop_curr
//     FROM hrp_prophist hp
//     WHERE hp.racingdate = '$date' 
//       AND hp.rectime IN (
//           SELECT MIN(rectime)
//           FROM hrp_prophist
//           WHERE racingdate = '$date'
//           GROUP BY raceno, horseno
//       )
//     GROUP BY hp.venue, hp.raceno, hp.horseno
//     ORDER BY hp.raceno, hp.horseno
// ";
    // echo $query;
    // $data = mysqli_query($mysqli, $query);        
    // $data = mysqli_query($mysqli, $query);        
    // if (mysqli_num_rows($data) > 0) {
    //     while ($row = mysqli_fetch_assoc($data)) {
    //         $venue = $row['venue'];
    //         $raceno = $row['raceno'];
    //         $horseno = $row['horseno'];
    //         $min_prop_curr = $row['min_prop_curr'];
    //         $max_prop_curr = $row['max_prop_curr'];
    
    //         // Initialize the array if it doesn't exist
    //         if (!isset($prophistcurr1st[$venue][$raceno][$horseno])) {
    //             $prophistcurr1st[$venue][$raceno][$horseno] = [
    //                 'prop_curr_values' => [], // Store all prop_curr values
    //                 'min' => $min_prop_curr,  // Store min
    //                 'max' => $max_prop_curr   // Store max
    //             ];
    //         }
    
    //         // Store the current prop_curr value (if you have other queries to fetch prop_curr)
    //         $prophistcurr1st[$venue][$raceno][$horseno]['prop_curr_values'][] = $min_prop_curr; // Example usage
    //         // If you need to fetch prop_curr from another source, include that logic here
    //     }
    // }
    $query = "
        SELECT racingdate, venue, raceno, horseno, pre_WIN,curr_prop, rectime,tri20, ttt20, fft20, qtt20
        FROM hrp_prophist2
        WHERE racingdate = '$date' and venue='$venueCode' 
        ORDER BY raceno, horseno, rectime
    ";    

    $data = mysqli_query($mysqli, $query);
    if (mysqli_num_rows($data) > 0) {
        // $prophistcurr1st = [];
    
        // First, gather all records into an array
        while ($row = mysqli_fetch_assoc($data)) {
            $racingdate = $row['racingdate'];
            $venue = $row['venue'];
            $raceno = $row['raceno'];
            $horseno = $row['horseno'];
            $prop_curr = $row['curr_prop'];
            $rectime = $row['rectime'];
            $pre_WIN = $row['pre_WIN'];
            $tri20 = $row['tri20'];
            $ttt20 = $row['ttt20'];
            $fft20 = $row['fft20'];
            $qtt20 = $row['qtt20'];
            // echo $venue."-".$raceno."-".$horseno."-".$rectime."-".$tri20."<br>";

            // Initialize the array if it doesn't exist
            // if (isset($prophistcurr1st[$venue][$raceno][$horseno])) {
            if (isset($horseno)) {
                $prophistcurr1st[$venue][$raceno][$horseno] = [
                    'earliest_rectime' => $rectime,
                    'prop_curr_values' => [],
                    'min' => ($pre_WIN>0 || date('H:i', strtotime($rectime)) >= '17:00') ? ($prophistcurr1st[$venue][$raceno][$horseno]['min'] > $prop_curr ? $prop_curr : $prophistcurr1st[$venue][$raceno][$horseno]['min']) : $prop_curr,
                    'max' => ($pre_WIN>0 || date('H:i', strtotime($rectime)) >= '17:00') ? ($prophistcurr1st[$venue][$raceno][$horseno]['max'] < $prop_curr ? $prop_curr : $prophistcurr1st[$venue][$raceno][$horseno]['max']) : $prop_curr,
                    'maxpre' => ($pre_WIN*1==0 && date('H:i', strtotime($rectime)) >= '17:00') ? ($prophistcurr1st[$venue][$raceno][$horseno]['maxpre'] < $prop_curr ? $prop_curr : $prophistcurr1st[$venue][$raceno][$horseno]['maxpre']) : $prop_curr,
                    'tri20' => $tri20,
                    'ttt20' => $ttt20,
                    'fft20' => $fft20,
                    'qtt20' => $qtt20,
                    'tri20_prelast' => null,
                    'ttt20_prelast' => null,
                    'fft20_prelast' => null,
                    'qtt20_prelast' => null,
                    'tri20_10am' => null,
                    'ttt20_10am' => null,
                    'fft20_10am' => null,
                    'qtt20_10am' => null,
                ];
            }
            // echo $venue."-".$raceno."-".$horseno."-".$pre_WIN."-".$prop_curr."-".$prophistcurr1st[$venue][$raceno][$horseno]['min']."-".$prophistcurr1st[$venue][$raceno][$horseno]['max']."-".$prophistcurr1st[$venue][$raceno][$horseno]['maxpre']."<br>";
            
            // Setting values based on conditions
            $hour = date('H', strtotime($rectime)) * 1;
            $currentDate = date('Y-m-d', strtotime($rectime));
            
            if ($hour == 6 && $currentDate == $racingdate && $pre_WIN * 1 == 0) {
                $ttfq[$venue][$raceno][$horseno]['tri20_prelast'] = $tri20;
                $ttfq[$venue][$raceno][$horseno]['ttt20_prelast'] = $ttt20;
                $ttfq[$venue][$raceno][$horseno]['fft20_prelast'] = $fft20;
                $ttfq[$venue][$raceno][$horseno]['qtt20_prelast'] = $qtt20;
            }
            
            // Set 10am values
            if ($hour == 10 && $currentDate == $racingdate && $pre_WIN * 1 > 0) {
                $ttfq[$venue][$raceno][$horseno]['tri20_10am'] = $tri20;
                $ttfq[$venue][$raceno][$horseno]['ttt20_10am'] = $ttt20;
                $ttfq[$venue][$raceno][$horseno]['fft20_10am'] = $fft20;
                $ttfq[$venue][$raceno][$horseno]['qtt20_10am'] = $qtt20;
            }
            
            // Set 12am values
            if ($hour == 12 && $currentDate == $racingdate && $pre_WIN * 1 > 0) {
                $ttfq[$venue][$raceno][$horseno]['tri20_12am'] = $tri20;
                $ttfq[$venue][$raceno][$horseno]['ttt20_12am'] = $ttt20;
                $ttfq[$venue][$raceno][$horseno]['fft20_12am'] = $fft20;
                $ttfq[$venue][$raceno][$horseno]['qtt20_12am'] = $qtt20;
            }

            // Store the prop_curr value
            $prophistcurr1st[$venue][$raceno][$horseno]['prop_curr_values'][] = $prop_curr;
            if($pre_WIN>0){
                // $prophistcurr1st[$venue][$raceno][$horseno]['min'] = min($prophistcurr1st[$venue][$raceno][$horseno]['min'], $prop_curr);
                // $prophistcurr1st[$venue][$raceno][$horseno]['max'] = max($prophistcurr1st[$venue][$raceno][$horseno]['max'], $prop_curr);
                
                $prophistcurr1st[$venue][$raceno][$horseno]['tri20'] = max($prophistcurr1st[$venue][$raceno][$horseno]['tri20'], $tri20);
                $prophistcurr1st[$venue][$raceno][$horseno]['ttt20'] = max($prophistcurr1st[$venue][$raceno][$horseno]['ttt20'], $ttt20);
                $prophistcurr1st[$venue][$raceno][$horseno]['fft20'] = max($prophistcurr1st[$venue][$raceno][$horseno]['fft20'], $fft20);
                $prophistcurr1st[$venue][$raceno][$horseno]['qtt20'] = max($prophistcurr1st[$venue][$raceno][$horseno]['qtt20'], $qtt20);    
                
            }else{
                if(date('H:i', strtotime($rectime)) >= '17:00'){
                    // $prophistcurr1st[$venue][$raceno][$horseno]['maxpre'] = max($prophistcurr1st[$venue][$raceno][$horseno]['maxpre'], $prop_curr);
                }
            }

            if($raceno==1 && $horseno==7){
                // echo "---".$venue."-".$raceno."-".$horseno."-".$pre_WIN."-".$prop_curr."-".$prophistcurr1st[$venue][$raceno][$horseno]['min']."-".$prophistcurr1st[$venue][$raceno][$horseno]['max']."-".$prophistcurr1st[$venue][$raceno][$horseno]['maxpre']."<br>";
            }
        }
    }
    // After processing the data
    foreach ($prophistcurr1st as $venue => $races) {
        foreach ($races as $raceno => $horses) {
            foreach ($horses as $horseno => $data) {
                $prophistcurr1st[$venue][$raceno]['max3'][] = $data['max'];
                // Sort max3 and keep only the top 3 values
                if (count($prophistcurr1st[$venue][$raceno]['max3']) > 3) {
                    // Sort in descending order
                    sort($prophistcurr1st[$venue][$raceno]['max3']);
                    // Keep only the last 3 values (highest)
                    $prophistcurr1st[$venue][$raceno]['max3'] = array_slice($prophistcurr1st[$venue][$raceno]['max3'], -3);
                }
                $prophistcurr1st[$venue][$raceno]['max3pre'][] = $data['maxpre'];
                // Sort max3 and keep only the top 3 values
                if (count($prophistcurr1st[$venue][$raceno]['max3pre']) > 3) {
                    // Sort in descending order
                    sort($prophistcurr1st[$venue][$raceno]['max3pre']);
                    // Keep only the last 3 values (highest)
                    $prophistcurr1st[$venue][$raceno]['max3pre'] = array_slice($prophistcurr1st[$venue][$raceno]['max3pre'], -3);
                }
            }
        }
    }
    
    //--------------------------------------------------------------------------------------------------------------

    $CurrPre = 'Pre';
    $oddsPre = pickodds($oddsarray, $date, $venueCode, $raceNo, $CurrPre);
    // echo $oddsarray.$date.$venueCode.$raceNo.$CurrPre;
    // print_r($oddsPre);    
    $CurrPre = 'Curr';
    $oddsCurr = pickodds($oddsarray, $date, $venueCode, $raceNo, $CurrPre);
    // print_r($oddsCurr);

    // Merging Pre odds
    foreach ($oddsPre as $venue => $races) {
        foreach ($races as $raceno => $horses) {
            foreach ($horses as $horseno => $data) {
                // Copy Pre odds directly
                $oddsMerged[$venue][$raceno][$horseno]['Pre'] = $data['Pre']; 
            }
            // Merge additional odds types from horses
            foreach ($horses as $oddsType => $oddsValues) {
                if (!is_numeric($oddsType) && isset($oddsValues)) {
                    // Initialize if not set
                    if (!isset($oddsMerged[$venue][$raceno][$oddsType])) {
                        $oddsMerged[$venue][$raceno][$oddsType] = [];
                    }
                    // Set Pre value
                    $oddsMerged[$venue][$raceno][$oddsType] = $oddsValues;
                }
            }
        }
    }
    
    // Merging Current odds
    foreach ($oddsCurr as $venue => $races) {
        foreach ($races as $raceno => $horses) {
            foreach ($horses as $horseno => $data) {
                // Check if horse entry exists in merged data
                if (!isset($oddsMerged[$venue][$raceno][$horseno])) {
                    // If no Pre odds exist, set Curr odds
                    $oddsMerged[$venue][$raceno][$horseno] = [
                        'Pre' => null, // Default Pre to null
                        'Curr' => $data['Curr'] ?? null // Use null if Curr doesn't exist
                    ];
                } else {
                    // If Pre odds exist, just add Curr odds
                    $oddsMerged[$venue][$raceno][$horseno]['Curr'] = $data['Curr'] ?? null;
                }
            }
    
            // Also merge additional odds types
            foreach ($horses as $oddsType => $oddsValues) {
                if (!is_numeric($oddsType)) {
                    if (!isset($oddsMerged[$venue][$raceno][$oddsType])) {
                        $oddsMerged[$venue][$raceno][$oddsType] = []; // Ensure initialization exists
                    }
                    $oddsMerged[$venue][$raceno][$oddsType] = $oddsValues; // Set the Curr odds for additional odds types
                }
            }
        }
    }
    
    
    // Traverse the array and add 'preprop' and 'prop' keys
    foreach ($oddsMerged[$venue] as $race => $horses) {
        $tmp_plapre = "";
        foreach ($horses as $horseno => $details) {
            // Check if 'Pre' exists in the current horse's details
            if (isset($details['Pre'])) {
                $plapre = $details['Pre']['PLAPre'];
                $winpre = $details['Pre']['WINPre'];
    
                // Calculate preprop and add it to the Pre array
                if ($winpre > 0) { // Prevent division by zero
                    $preprop = number_format(100 * $plapre / $winpre, 0);
                    $oddsMerged[$venue][$race][$horseno]['Pre']['preprop'] = $preprop;  // ? $preprop : $prophistcurr1st[$venue][$raceno][$horseno]['prop_curr1st'];
                    $oddsMerged[$venue][$race]['maxpreprop'] = ($oddsMerged[$venue][$race]['maxpreprop']>$preprop) ? $oddsMerged[$venue][$race]['maxpreprop'] : $preprop;
                    
                    $preprop1 = number_format(100 * (1/$plapre + 1/$winpre), 0);
                    $oddsMerged[$venue][$race][$horseno]['Pre']['preprop1'] = $preprop1;
                    $oddsMerged[$venue][$race]['maxpreprop1'] = ($oddsMerged[$venue][$race]['maxpreprop1']>$preprop1) ? $oddsMerged[$venue][$race]['maxpreprop1'] : $preprop1;
                    
                    $oddsMerged[$venue][$race][$horseno]['Pre']['issmaller'] = ($tmp_plapre && $plapre && ($tmp_plapre > $plapre)) ? 1 : 0;
                    $tmp_plapre = $plapre;
                } else {
                    $oddsMerged[$venue][$race][$horseno]['Pre']['preprop'] = 0;//null; // Handle division by zero case
                    $oddsMerged[$venue][$race][$horseno]['Pre']['preprop1'] = 0;//null; // Handle division by zero case
                    $oddsMerged[$venue][$race][$horseno]['Pre']['issmaller'] = 0;//null;
                    $tmp_plapre = "";
                }
            }else{
                $oddsMerged[$venue][$race][$horseno]['Pre']['preprop'] = $prophistcurr1st[$venue][$race][$horseno]['prop_curr1st'];   //
            }
    
            // Check if 'Curr' exists in the current horse's details
            if (isset($details['Curr'])) {
                $pla = $details['Curr']['PLA'];
                $win = $details['Curr']['WIN'];
    
                // Calculate prop and add it to the Curr array
                if ($win > 0) { // Prevent division by zero
                    $prop = number_format(100 * $pla / $win, 0);
                    $oddsMerged[$venue][$race][$horseno]['Curr']['prop'] = $prop;
                    $oddsMerged[$venue][$race]['maxcurrprop'] = ($oddsMerged[$venue][$race]['maxcurrprop']>$prop) ? $oddsMerged[$venue][$race]['maxcurrprop'] : $prop;
                } else {
                    $oddsMerged[$venue][$race][$horseno]['Curr']['prop'] = 0;//null; // Handle division by zero case
                }
            }
        }
    }
    
}
// print_r($prophistcurr1st);
$odds = $oddsMerged;
// print_r($odds);

$col = ($ismm) ? 48 : 36; // Number of columns ------------------------------------------------------------------------------------------------
    if($ismm){
        echo "<hr>".$hint."<hr>";
    }
foreach ($allvenue as $rsdata) {
    // print_r($rsdata);
    $date = $rsdata['date'];
    $venue = $rsdata['venueCode'];
    $basedata = basedata($date,$venue,$resultOddsType);
    $raceMeetings = $basedata['data']['raceMeetings'][0]['races'];
    //-----------------------------------------------------------------------
    $pick = array();
    $hrp_pick2_query = "select * from hrp_pick2
            WHERE racingdate = '$date'
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
    //-----------------------------------------------------------------------
    $bettingsql = bettingflow($date,$venue);
    // echo $bettingsql;
    $bettingresult = mysqli_query($mysqli, $bettingsql);
    while($data = mysqli_fetch_assoc($bettingresult)){
            // HOUR(r1.source_time) as source_time
        $startTime = $data['startTime'];
        $venue = $data['venue'];
        $race_no = $data['race_no'];
        $horse_no = $data['horse_no'];
        $win_value = $data['win_value'];
        $win_investment = $data['win_investment'];
        $place_value = $data['place_value'];
        $place_investment = $data['place_investment'];
        
        $win = number_format(abs($win_investment/10000));
        $pla = number_format(abs($place_investment/10000));
        $bettinghistory[$startTime][$venue][$race_no][$horse_no]['win_investment'] .= $win.";";
        $bettinghistory[$startTime][$venue][$race_no][$horse_no]['pla_investment'] .= $pla.";";
        $bettinghistory[$startTime][$venue][$race_no][$horse_no]['win'][] = $win;
        $bettinghistory[$startTime][$venue][$race_no][$horse_no]['pla'][] = $pla;
        // Limit the win array to the last 5 entries
        if (count($bettinghistory[$startTime][$venue][$race_no][$horse_no]['win']) > 5) {
            $bettinghistory[$startTime][$venue][$race_no][$horse_no]['win'] = array_slice($bettinghistory[$startTime][$venue][$race_no][$horse_no]['win'], -5);
        }
        if (count($bettinghistory[$startTime][$venue][$race_no][$horse_no]['pla']) > 5) {
            $bettinghistory[$startTime][$venue][$race_no][$horse_no]['pla'] = array_slice($bettinghistory[$startTime][$venue][$race_no][$horse_no]['pla'], -5);
        }
    }
    // print_r($bettinghistory);
    //------------------------------.";"-----------------------------------------
    $sql = "SELECT rc.venue, rc.raceno, rc.horseno, rc.horsename, rc.brandno, rc.trainer, rc.jockey, rc.besttime, rc.wt
        FROM RaceCard rc
        where racingdate = '$date'";
        // die("Query failed: " . mysqli
    $racecard_array = mysqli_query($mysqli, $sql);
    // $racecard_array = mysqli_query($GLOBALS['mysqli'], $sql);
    // Check for errors in the query
    if (!$racecard_array) {
        // die("Query failed: " . mysqli_error($mysqli));
    }

    $jockeychange = array();
    $besttime_min = array();
    $rc_trainer = array();
    $trainer_count = array();
    while ($row = mysqli_fetch_assoc($racecard_array)) {
        // $venue = $row['venue'];
        $raceno = $row['raceno'];
        $horseno = $row['horseno'];
        $current_brandno = $row['brandno'];
        $besttime = $row['besttime'];
        $jockeyshort = explode(' (', $row['jockey']);
        $jockeychange[$date][$venue][$raceno][$jockeyshort[0]] = $horseno;
        $trainer = $row['trainer'];
        $rc_trainer[$date][$venue][$raceno][$horseno]['trainer'] = $row['trainer'];
        
        $best_time[$date][$venue][$raceno][$horseno] = $besttime;
        $Wt[$date][$venue][$raceno][$horseno] = $row['wt'];
        
        if(!empty($besttime) && $besttime <> '-'){
            if (!isset($best_time_min[$date][$venue][$raceno]) || $besttime < $best_time_min[$date][$venue][$raceno]) {
                $best_time_min[$date][$venue][$raceno] = $besttime;
            }
        }
        // Count the number of trainers for each race
        if (!isset($trainer_count[$date][$venue][$raceno][$trainer])) {
            $trainer_count[$date][$venue][$raceno][$trainer] = 1;
        } else {
            $trainer_count[$date][$venue][$raceno][$trainer]++;
        }
        // Count the ttl number of trainers for each race
        $jockey_count_ttl[$date][$venue][$jockeyshort[0]]++;
        $trainer_count_ttl[$date][$venue][$trainer]++;
        $jt = $jockeyshort[0].$trainer;
        $jockey_trainer_count[$date][$venue][$jt]++;
        // echo $date.$venue.$jockey_trainer_count[$date][$venue][$jt]."<br>";
        
        $brandno[$date][$venue][] = $brandno;
        $racecard[$date][$venue]['brandno'][] = $current_brandno; // C
        
        // echo $trainer.$trainer_count[$venue][$raceno][$trainer]."<br>";
    }
    //-----------------------------------------------------------------------
    $sql = "SELECT * FROM oddsDrop WHERE date<'$date' and finalPosition GROUP BY date, horsecode ORDER BY horsecode, date";
    $sql = "SELECT * FROM oddsDrop WHERE date>'$yesterday'  GROUP BY date, horsecode ORDER BY horsecode, date";
    $oddsDrop_array = mysqli_query($mysqli, $sql);
    
    // Initialize the array to avoid null warnings
    $oddsDrophists = []; // Ensure this is initialized
    $oddsDrop_laterest = []; // Ensure this is initialized
    
    foreach ($oddsDrop_array as $oddsDrophist) {
        $horseid = $oddsDrophist['horsecode'];
        // Concatenate date and horse code
        $oddsDrophists[] = $oddsDrophist['date'] . $horseid;
    
        // Debug output
        // echo $horseid . "-" . $oddsDrophist['date'] . "-" . $oddsDrophist['finalPosition'] . "<br>";
    
        // Only process if the date is less than the comparison date
        if ($date > $oddsDrophist['date']) {
            // Check if this horseid already has a record
            if (!isset($oddsDrop_laterest[$horseid]) || 
                strtotime($oddsDrop_laterest[$horseid]['date']) < strtotime($oddsDrophist['date'])) {
                // Update the latest record for this horse
                $oddsDrop_laterest[$horseid]['pre_win'] = number_format($oddsDrophist['pre_win'], 0);
                $oddsDrop_laterest[$horseid]['curr_win'] = number_format($oddsDrophist['curr_win'], 0);
                $oddsDrop_laterest[$horseid]['oddsDropValue'] = $oddsDrophist['oddsDropValue'];
                $oddsDrop_laterest[$horseid]['finalPosition'] = $oddsDrophist['finalPosition'];
                $oddsDrop_laterest[$horseid]['date'] = $oddsDrophist['date'];
            }
        }
    }
    foreach ($oddsDrop_laterest as $oddsDrop_horseid => $oddsDrop_value) {
        foreach ($horseinfo_datepla[$oddsDrop_horseid] as $hrinfodate => $plavalue) {
            //   echo $oddsDrop_horseid."-".$hrinfodate."-".$oddsDrop_value['date']."-".$plavalue."<br>";
            if ($hrinfodate > $oddsDrop_value['date']) {
                $oddsDrop_after[$oddsDrop_horseid] .= $plavalue . ";"; // Store the corresponding value
            }
        }
    }
    foreach ($raceMeetings as $race) {
        $raceno = $race['no'];
            // if($ismm){
            // Loop through each runner in the $race['runners'] array
            foreach ($race['runners'] as $index => $racedetail) {
                $horseno = $racedetail['no']; // Get the horse number from the runner
            
                // Check if there's corresponding data in the $odds array
                if (isset($odds[$venue][$raceno][$horseno]['Pre']['WINPre'])) {
                    // Add the WINPre value to the runner's details
                    $race['runners'][$index]['WINPre'] = $odds[$venue][$raceno][$horseno]['Pre']['WINPre'];
                    $race['runners'][$index]['PLAPre'] = $odds[$venue][$raceno][$horseno]['Pre']['PLAPre'];
                    $race['runners'][$index]['preprop'] = $odds[$venue][$raceno][$horseno]['Pre']['preprop'];
                    
                    // $race['runners'][$index]['cnt'][$odds[$venue][$raceno][$horseno]['Pre']['preprop']]++;
                    
                    // Get counts based on preprop value
                    $counts = setCountsBasedOnPreprop($race['runners'][$index]['preprop']);
                    
                    // Assign counts to the runner's details
                    foreach ($counts as $key => $value) {
                        $race['runners'][$index][$key] = $value;
                    }
                } else {
                    // If not found, you can set it to null or leave it out
                    $race['runners'][$index]['WINPre'] = null; // Optional
                    $race['runners'][$index]['PLAPre'] = null; // Optional
                    $race['runners'][$index]['preprop'] = null; // Optional
                    // Initialize count fields to zero if needed
                    $race['runners'][$index]['1xcnt'] = 0;
                    $race['runners'][$index]['2xcnt'] = 0;
                    $race['runners'][$index]['3xcnt'] = 0;
                    $race['runners'][$index]['4xcnt'] = 0;
                    $race['runners'][$index]['5xcnt'] = 0;
                }
            }
            
            countPrepropOccurrences($race['runners']);
            
            // // //Sort the $race['runners'] array by the 'WINPre' value
            usort($race['runners'], function($a, $b) {
                // Compare by WINPre first
                $winComparison = $a['WINPre'] <=> $b['WINPre'];
            
                // If WINPre is the same, compare by PLAPre
                if ($winComparison === 0) {
                    return $a['PLAPre'] <=> $b['PLAPre'];
                }
            
                return $winComparison;
            });
        // }

        //---------------------------------------------------------------------------------------
        $pos =1;
        foreach ($race['runners'] as $racedetail) { 
            $jockey = $racedetail['jockey']['name_ch'];
            $propposition[$venue][$raceno][$jockey]=$pos;
            $pos++;
        }
    }
    
    // print_r($oddsDrop_laterest);
    foreach ($raceMeetings as $race) { //---------------------------------------------------------------------------------------------------------------------------
        $raceno = $race['no'];
        $status = $race['status'];
        switch ($status) {
            case "RESULT":
                foreach ($race['runners'] as $racedetail) { 
                    $horseno = $racedetail['no'];
                    $horsecode = $racedetail['horse']['code'];
                    $horsename = $racedetail['name_ch'];
                    $finalPosition = $racedetail['finalPosition'];
                    $mix = $date . $horsecode;
                    $pre = $odds[$venue][$raceno][$horseno]['Pre']['preprop'];
                    $curr = $odds[$venue][$raceno][$horseno]['Curr']['prop'];
                    $prewin = $odds[$venue][$raceno][$horseno]['Pre']['WINPre']; 
                    $currwin = $odds[$venue][$raceno][$horseno]['Curr']['WIN'];
    
                    // Check if $oddsDrophists is not empty and perform in_array check
                    if (!in_array($mix, $oddsDrophists)) {
                        // Ensure the odds variable is set and contains the expected structure
                        if (isset($odds[$venue][$raceno][$horseno]['Curr']['oddsDropValue']) && 
                            $odds[$venue][$raceno][$horseno]['Curr']['oddsDropValue'] > 0) {
                            
                            $insert_sql = "INSERT INTO oddsDrop VALUES (''," .
                                "'" . $date . "'," . // Add quotes around string values
                                "'" . $venue . "'," .
                                "'" . $horsecode . "'," .
                                "'" . $horsename . "'," .
                                $odds[$venue][$raceno][$horseno]['Curr']['oddsDropValue'] . "," .
                                $pre . "," .
                                $curr . "," .
                                $prewin . "," .
                                $currwin . "," .
                                $finalPosition.")";
                            
                            // echo $insert_sql . "<br>";
                            $result = mysqli_query($mysqli, $insert_sql);
                        }
                    }
                }
                break;
            case "DECLARED":
                break;
        }
        // Split the date and time
        list($date, $timefull) = explode('T', $race['postTime']);
        list($time, $timezone) = explode('+', $timefull);

        // Race header
        $pageContent .= $venue."..Race: ".$raceno."<br>".$date."..".$time."..".$race['country_ch']."..".$race['raceClass_ch']."..".$race['distance']."m..".$race['go_ch']."..".$race['raceName_ch'];

        $pageContent .= "<table class='race-table' border=1>";
        $pageContent .= "<thead><tr>";
				for($i = 1; $i<$col; $i++ ){
					$pageContent .= "<th class='order'></th>";
				}
				$pageContent .= "</tr>";
				$pageContent .= "</thead>";
        $trainerCounts = [];
        
        // Loop through each race runner
        foreach ($race['runners'] as $racedetail) {
            // Get the trainer's name
            $trainerName = $racedetail['trainer']['name_ch'];
        
            // Count occurrences of each trainer's name
            if (isset($trainerCounts[$trainerName])) {
                $trainerCounts[$trainerName]++;
            } else {
                $trainerCounts[$trainerName] = 1;
            }
        }
        // print_r($race['runners']);
        foreach ($race['runners'] as $racedetail) { //---------------------------------------------------------------------------------------
            $horseno = $racedetail['no'];
            $samepropcount[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['preprop']]++;
            $sameprop1count[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['preprop1']]++;
            $samecurrpropcount[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Curr']['prop']]++;
            $sameprewincount[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['WINPre']]++;
            $PLAPre = is_numeric($odds[$venue][$raceno][$horseno]['Pre']['PLAPre']) ? number_format($odds[$venue][$raceno][$horseno]['Pre']['PLAPre'], 0) : 0;
            if (!isset($samepreplacount[$venue][$raceno][$PLAPre])) {
                $samepreplacount[$venue][$raceno][$PLAPre] = 0;
            }
            $samepreplacount[$venue][$raceno][$PLAPre]++;
            if (isset($odds[$venue][$raceno][$horseno]['Pre']['preprop']) && $odds[$venue][$raceno][$horseno]['Pre']['preprop'] > 20 && $odds[$venue][$raceno][$horseno]['Pre']['preprop'] < 30) {
                $max2x[$venue][$raceno] = max($max2x[$venue][$raceno], $odds[$venue][$raceno][$horseno]['Pre']['preprop']);
                $min2x[$venue][$raceno] = min($min2x[$venue][$raceno], $odds[$venue][$raceno][$horseno]['Pre']['preprop']);
            }
            if (isset($odds[$venue][$raceno][$horseno]['Pre']['preprop']) && $odds[$venue][$raceno][$horseno]['Pre']['preprop'] > 30 && $odds[$venue][$raceno][$horseno]['Pre']['preprop'] < 40) {
                $max3x[$venue][$raceno] = max($max3x[$venue][$raceno], $odds[$venue][$raceno][$horseno]['Pre']['preprop']);
                $min3x[$venue][$raceno] = min($min3x[$venue][$raceno], $odds[$venue][$raceno][$horseno]['Pre']['preprop']);
            }         
            if($horseno){
                $racestring[$raceno][] = " Horse no.:".$horseno." Horse name:".$racedetail['name_ch']." Jockey:".$racedetail['jockey']['name_ch']."; ";
            }
        }
        
        if($ismm){
            // Loop through each runner in the $race['runners'] array
            foreach ($race['runners'] as $index => $racedetail) {
                $horseno = $racedetail['no']; // Get the horse number from the runner
            
                // Check if there's corresponding data in the $odds array
                if (isset($odds[$venue][$raceno][$horseno]['Pre']['WINPre'])) {
                    // Add the WINPre value to the runner's details
                    $race['runners'][$index]['WINPre'] = $odds[$venue][$raceno][$horseno]['Pre']['WINPre'];
                    $race['runners'][$index]['PLAPre'] = $odds[$venue][$raceno][$horseno]['Pre']['PLAPre'];
                    $race['runners'][$index]['preprop'] = $odds[$venue][$raceno][$horseno]['Pre']['preprop'];
                    
                    // $race['runners'][$index]['cnt'][$odds[$venue][$raceno][$horseno]['Pre']['preprop']]++;
                    
                    // Get counts based on preprop value
                    $counts = setCountsBasedOnPreprop($race['runners'][$index]['preprop']);
                    
                    // Assign counts to the runner's details
                    foreach ($counts as $key => $value) {
                        $race['runners'][$index][$key] = $value;
                    }
                } else {
                    // If not found, you can set it to null or leave it out
                    $race['runners'][$index]['WINPre'] = null; // Optional
                    $race['runners'][$index]['PLAPre'] = null; // Optional
                    $race['runners'][$index]['preprop'] = null; // Optional
                    // Initialize count fields to zero if needed
                    $race['runners'][$index]['1xcnt'] = 0;
                    $race['runners'][$index]['2xcnt'] = 0;
                    $race['runners'][$index]['3xcnt'] = 0;
                    $race['runners'][$index]['4xcnt'] = 0;
                    $race['runners'][$index]['5xcnt'] = 0;
                }
            }
            
            countPrepropOccurrences($race['runners']);
            
            // // //Sort the $race['runners'] array by the 'WINPre' value
            usort($race['runners'], function($a, $b) {
                // Compare by WINPre first
                $winComparison = $a['WINPre'] <=> $b['WINPre'];
            
                // If WINPre is the same, compare by PLAPre
                if ($winComparison === 0) {
                    return $a['PLAPre'] <=> $b['PLAPre'];
                }
            
                return $winComparison;
            });
        }
        if($istext){
            echo "<br>R".$raceno." Distince:".$race['distance']."m; ";
            foreach ($racestring[$raceno] as $txt){
                echo $txt;
            }
        }
        $redbold = "<b><font color=#FF33FF>";
        $tmp_PLAPre = "";
        // print_r($race['runners']);
        // Collect runner rows
        //---------------------------------------------------------------------------------------
        // $pos =1;
        // foreach ($race['runners'] as $racedetail) { 
        //     $jockey = $racedetail['jockey']['name_ch'];
        //     $propposition[$venue][$raceno][$jockey]=$pos;
        //     $pos++;
        // }
        // print_r($propposition);
        //---------------------------------------------------------------------------------------
        $pos =1;
        foreach ($race['runners'] as $racedetail) { 
            $horseno = $racedetail['no'];
            $horsecode = $racedetail['horse']['code'];
            $horsename = $racedetail['name_ch'];
            $jockey = $racedetail['jockey']['name_ch'];

            $ispla = ($racedetail['finalPosition'] && $racedetail['finalPosition'] <= 4) ? "<b>" : "<font color=lightgrey>"; 
            $ismaxpreprop = ($odds[$venue][$raceno][$horseno]['Pre']['preprop']==$odds[$venue][$raceno]['maxpreprop']) ? $redbold : "";
            $ismaxpreprop1 = ($odds[$venue][$raceno][$horseno]['Pre']['preprop1']==$odds[$venue][$raceno]['maxpreprop1']) ? $redbold : "";
            $ismaxprop = ($odds[$venue][$raceno][$horseno]['Curr']['prop']==$odds[$venue][$raceno]['maxcurrprop']) ? $redbold : "";
            $istopQINTop = !empty($odds[$venue][$raceno]['QIN']['top']) && in_array($horseno, $odds[$venue][$raceno]['QIN']['top']) ? $redbold : "";
            $istopQPLTop = !empty($odds[$venue][$raceno]['QPL']['top']) && in_array($horseno, $odds[$venue][$raceno]['QPL']['top']) ? $redbold : "";
            $istopTRITop = !empty($odds[$venue][$raceno]['TRITop']['top']) && in_array($horseno, $odds[$venue][$raceno]['TRITop']['top']) ? $redbold : "";
            $istopTCETop = !empty($odds[$venue][$raceno]['TCETop']['top']) && in_array($horseno, $odds[$venue][$raceno]['TCETop']['top']) ? $redbold : "";
            $istopFFTop = !empty($odds[$venue][$raceno]['FFTop']['top']) && in_array($horseno, $odds[$venue][$raceno]['FFTop']['top']) ? $redbold : "";
            $istopQTTTop = !empty($odds[$venue][$raceno]['QTTTop']['top']) && in_array($horseno, $odds[$venue][$raceno]['QTTTop']['top']) ? $redbold : "";
            $oddsDropdate = (isset($oddsDrop_laterest[$horsecode]['oddsDropValue']) && $oddsDrop_laterest[$horsecode]['date'] >= $onemonth) 
                                ? ($oddsDrop_laterest[$horsecode]['date'] >= $oneweek ? "<font color=red>" : "<font color=blue>") : "<font style=\"opacity: 0.5;\">";
            $oddsDropValue = isset($oddsDrop_laterest[$horsecode]['oddsDropValue']) 
                                ? $oddsDropdate."".$oddsDrop_after[$horsecode].$oddsDrop_laterest[$horsecode]['pre_win'].".".$oddsDrop_laterest[$horsecode]['curr_win']."(".$oddsDrop_laterest[$horsecode]['finalPosition']."</font>" 
                                : "";
                                
            $laterestWin_Oddss = $laterestWin_Odds[$horsecode] ? number_format($laterestWin_Odds[$horsecode],0)."(".($laterestpla[$horsecode]>9 ? "-" : mapColorsHtml($laterestpla[$horsecode])) : "";
            $laterestplas = $laterestpla[$horsecode] ? ($laterestpla[$horsecode]<4 ? " style='text-align: right;' " : "") : "";
            $laterestplasmaygo = $laterestpla[$horsecode] ? (($laterestWin_Odds[$horsecode]>$odds[$venue][$raceno][$horseno]['Pre']['WINPre'] ) ? " bgcolor=#99FF99 " : "") : "";
            $oddsDropValue .= ($odds[$venue][$raceno][$horseno]['Curr']['oddsDropValue']>0) ? "_".number_format($odds[$venue][$raceno][$horseno]['Curr']['oddsDropValue'],0) : null;
            
            // $smallerprepla = ($tmp_PLAPre && $odds[$venue][$raceno][$horseno]['Pre']['PLAPre'] && $tmp_PLAPre > $odds[$venue][$raceno][$horseno]['Pre']['PLAPre']) ? $redbold : "";
            $smallerprepla = $odds[$venue][$raceno][$horseno]['Pre']['issmaller'] ? $redbold : "";
            $trainercnt = ($trainerCounts[$racedetail['trainer']['name_ch']]>1) ? (($trainerCounts[$racedetail['trainer']['name_ch']]>2) ? "<font color=red>" : "<font color=blue>") : null;
            // $issamepreprop1 = ($sameprop1count[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['preprop1']]>1) ? 
            //             ($sameprop1count[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['preprop1']]>2 ? "style=\"background-color:lightyellow;font-weight:bold;border-right: thick #66CDFF;\"" : "style=\"background-color:lightyellow;border-right: thick #66CDFF;\"") : "style=\"border-right: thick #66CDFF;\"";
            // $issamepreprop1 = ($sameprop1count[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['preprop1']]>1) ? 
            //             ($sameprop1count[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['preprop1']]>2 ? "style=\"background-color:lightyellow;font-weight:bold;border-right: thick #66CDFF;\"" : "style=\"background-color:lightyellow;border-right: thick #66CDFF;\"") : "style=\"border-right: thick #66CDFF;\"";
            $preprop1 = $sameprop1count[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['preprop1']];
            $issamepreprop1 = ($sameprop1count[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['preprop1']]>1) ? 
                        ($sameprop1count[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['preprop1']]>2 ? "style=\"background-color:lightyellow;font-weight:bold;border-right: 2px solid #66CDFF;\"" : "style=\"background-color:lightgreen;border-right: 2px solid #66CDFF;\"") 
                        : ($preprop1==16 ? "style=\"border-right: 2px solid #66CDFF;color:blue;\"" : "style=\"border-right: 2px solid #66CDFF;\"");
            $issamepreprop = ($samepropcount[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['preprop']]>1) ? 
                        ($samepropcount[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['preprop']]>2 ? "style=\"background-color:yellow;font-weight:bold\"" : "style=\"background-color:yellow;\"") : null;
            $issameCurrprop = ($samecurrpropcount[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Curr']['prop']]>1) ? 
                        ($samecurrpropcount[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Curr']['prop']]>2 ? "style=\"background-color:yellow;font-weight:bold;border-right: 2px solid #66CDFF;\"" : "style=\"background-color:yellow;border-right: 2px solid #66CDFF;\"") : "style=\"border-right: 2px solid #66CDFF;\"";                        
            $issameprewin = ($sameprewincount[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['WINPre']]>1) ? 
                        ($sameprewincount[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['WINPre']]>2 ? "style=\"background-color:yellow;text-align: right;\"" : "style=\"background-color:lightyellow;text-align: right;\"") : "style=\"text-align: right;\"";                                                
            $issamejockey = ($racedetail['jockey']['name_ch'] == $laterestjockey[$horsecode]) ? " style=\"font-weight: bold;\" " : " style=\"opacity: 0.5;\" ";
            $PLAPre = is_numeric($odds[$venue][$raceno][$horseno]['Pre']['PLAPre']) ? number_format($odds[$venue][$raceno][$horseno]['Pre']['PLAPre'], 0) : 0;
            $PLAPrecnt = $samepreplacount[$venue][$raceno][$PLAPre]*10;
            $colorBase = 240; // Adjust this value to change the base color (higher value = more yellow)
            $colorRange = 40; // Adjust this value to change the color range
            $PLAPreColor = "style=\"background-color: #ffff" . dechex(max(0, min($colorBase + $colorRange, $colorBase + ($PLAPrecnt % $colorRange)))) . "\"";
            $cntafterbarrier = ($horseinfo_cntafterbarrier[$horsename] ? "[".$horseinfo_cntafterbarrier[$horsename] : "");
            $barrierpla = substr($barrierinfo[$horsename]['RunningPosition'],-1);
            $barrierdayTimestamp = strtotime($barrierinfo[$horsename]['barrierday'] ?? '1970-01-01'); // Default to epoch if not set
            // Calculate interval
            $barrierdayinterval = $barrierinfo[$horsename]['barrierday'] ? (int)((strtotime($date) - $barrierdayTimestamp) / 86400).(is_numeric($barrierpla) ? "(".mapColorsHtml($barrierpla).$cntafterbarrier : "") : "-";
            
            
            $last6run = countValuesInPattern($racedetail['last6run']);
            if(isset($racedetail['last6run'])){
                $last6runalert = ($last6run['percentage']==100) ? "style=\"background-color:#FFCDFF;\"" : ($last6run['percentage']>=60 ? "style=\"background-color:#FFFFCD;\"" : null);
                $last6rundot = ($last6run['percentage']==100) ? "<u>" : "";
            }
            if(!empty($best_time[$date][$venue][$raceno][$horseno]) && $best_time[$date][$venue][$raceno][$horseno]==$best_time_min[$date][$venue][$raceno]){
                $besttime = "<font color=red>".$best_time[$date][$venue][$raceno][$horseno]."</font>"; 
                $besttimealert = "bgcolor=pink";
            }else{
                $besttime = $best_time[$date][$venue][$raceno][$horseno];
                $besttimealert = "";
            }
            //-----------------------------------------------------------------------------------------------------------------------------
            $jt = $racedetail['jockey']['name_ch'].$racedetail['trainer']['name_ch'];
            $jockeycountttl = $jockey_count_ttl[$date][$venue][$racedetail['jockey']['name_ch']];
            $trainercountttl = $trainer_count_ttl[$date][$venue][$racedetail['trainer']['name_ch']];
            $jockeytrainercount = $jockey_trainer_count[$date][$venue][$jt];
            
            $pla = ($racedetail['finalPosition'] > 9) ? "-" : $racedetail['finalPosition'];
            $jockeypla[$venue][$racedetail['jockey']['name_ch']] .= ($pla ? $pla : "");
            if($racedetail['finalPosition']) $jockeyplaarray[$venue][$racedetail['jockey']['name_ch']][] = $racedetail['finalPosition'];
            $trainerpla[$venue][$racedetail['trainer']['name_ch']] .= ($pla ? $pla : "");
            $jockey_trainer_pla[$venue][$jt] .= ($pla ? $pla : "");
            $barrierDrawNumberpla[$date][$venue][$racedetail['barrierDrawNumber']] .= ($pla ? $pla : "");
            
            $jockeyplacntalert = (isset($horseinfo_jockeyinpla_cntpla[$horsecode]) && $horseinfo_jockeyinpla_cntpla[$horsecode]/$horseinfo_jockeyinpla_cnt[$horsecode]>=0.5) ? 
                                    ((isset($horseinfo_jockeyinpla_cntpla[$horsecode]) && $horseinfo_jockeyinpla_cntpla[$horsecode]/$horseinfo_jockeyinpla_cnt[$horsecode]>=0.8) ? "style=\"background-color:#FF66FF;\"" : "style=\"background-color:#FFCDFF;\"") : "";
            $jockeyplacnt = ($horseinfo_jockeyinpla_cntpla[$horsecode] ?? "-")."/".$horseinfo_jockeyinpla_cnt[$horsecode];
            //-----------------------------------------------------------------------------------------------------------------------------
            preg_match('/\d+/', $racedetail['last6run'], $lastpla);
            $jockeyArray = isset($horseinfo_jockeyinpla[$horsecode]) ? $horseinfo_jockeyinpla[$horsecode] : [];
            $jockeyArraycnt = count($jockeyArray);
            $distchange = ($laterestdist[$horsecode]==$race['distance']) ? "=" : (($laterestdist[$horsecode]>$race['distance']) ? "-" : "+");
            
            if($laterestjockey[$horsecode]==$racedetail['jockey']['name_ch'] && in_array($racedetail['jockey']['name_ch'],$jockeyArray) && $lastpla[0]<4){
                $jockeygo = "PP";
            }elseif($laterestjockey[$horsecode]==$racedetail['jockey']['name_ch'] && in_array($racedetail['jockey']['name_ch'],$jockeyArray) && $lastpla[0]>=4){
                $jockeygo = "Px";
            }elseif($laterestjockey[$horsecode]==$racedetail['jockey']['name_ch'] && isset($jockeyArray) && !in_array($racedetail['jockey']['name_ch'],$jockeyArray) && $lastpla[0]<4){
                $jockeygo = "LP";
            }elseif($laterestjockey[$horsecode]==$racedetail['jockey']['name_ch'] && isset($jockeyArray) && !in_array($racedetail['jockey']['name_ch'],$jockeyArray) && $lastpla[0]>=4){
                $jockeygo = "Lx";
            }elseif($laterestjockey[$horsecode]<>$racedetail['jockey']['name_ch'] && isset($jockeyArray) && !in_array($racedetail['jockey']['name_ch'],$jockeyArray) && $lastpla[0]<4){
                $jockeygo = "?P";
            }elseif($laterestjockey[$horsecode]<>$racedetail['jockey']['name_ch'] && isset($jockeyArray) && !in_array($racedetail['jockey']['name_ch'],$jockeyArray) && $lastpla[0]>=4){
                $jockeygo = "?x";
            }elseif($laterestjockey[$horsecode]<>$racedetail['jockey']['name_ch'] && in_array($racedetail['jockey']['name_ch'],$jockeyArray) && $lastpla[0]<4){
                $jockeygo = "HP";
            }elseif($laterestjockey[$horsecode]<>$racedetail['jockey']['name_ch'] && in_array($racedetail['jockey']['name_ch'],$jockeyArray) && $lastpla[0]>=4){
                $jockeygo = "Hx";
            }elseif($laterestjockey[$horsecode]<>$racedetail['jockey']['name_ch'] && !isset($jockeyArray) && $lastpla[0]>=4){
                $jockeygo = "-x";
            }else{
                $jockeygo = "?";
            }
            $isdifferenttrainer = ($lateresttrainer[$horsecode] == $racedetail['trainer']['name_ch']) ? "" : "**";
            
            //-----------------------------------------------------------------------------------------------------------------------------
            // $distances = isset($horseinfo_distinpla[$horsecode]) ? $horseinfo_distinpla[$horsecode] : [];
            // $distCount = array_count_values($distances);
            // $distinpla = ""; // Default case
            // // Check if the specific distance exists in the count array and get its count
            // // echo $venue.$raceno.$horseno.$distCount[$race['distance']]."<br>";
            // if (isset($distCount[$race['distance']])) {
            //     $count = $distCount[$race['distance']]; // Get the count for the specific distance
                
            //     if ($count > 1) {
            //         if ($count > 9) {
            //             $distinpla = "+"; // Bold "D" and include count <font color=blue>D</font>
            //         }else{
            //             $distinpla = $count; // Bold "D" and include count <font color=blue>D</font>
            //         }
            //     } else {
            //         // If it exists but is not more than 2
            //         $distinpla = $count; // Include count without bold "d".
            //     }
            // }

            // $jockeys = isset($horseinfo_jockeyinpla[$horsecode]) ? $horseinfo_jockeyinpla[$horsecode] : [];
            // $jockeyCount = array_count_values($jockeys);
            // $jockeyinpla = ""; // Default case
            // // Check if the specific jockey name exists in the count array and get its count
            // if (isset($jockeyCount[$racedetail['jockey']['name_ch']])) {
            //     $count = $jockeyCount[$racedetail['jockey']['name_ch']];
                
            //     if ($count > 1) {
            //         if ($count > 9) {
            //             $jockeyinpla = "+"; // Bold "J" and include count   <font color=blue>J</font>
            //         }else{
            //             $jockeyinpla = $count; // Bold "J" and include count    <font color=blue>J</font>
            //         }
            //     } else {
            //         // If it exists but is not more than 2
            //         $jockeyinpla = $count;  //"J".
            //     }
            // }
            //-----------------------------------------------------------------------------------------------------------------------------
            $ishotFavourite = $odds[$venue][$raceno][$horseno]['Curr']['hotFavourite'] ? "checked" : "";
            $istrumpCard = $odds[$venue][$raceno][$horseno]['Curr']['trumpCard'] ? "checked" : "";
            $ismax2x = ($odds[$venue][$raceno][$horseno]['Pre']['preprop'] == $max2x[$venue][$raceno]) ? "<font color=red>" : null;
            $ismin2x = ($odds[$venue][$raceno][$horseno]['Pre']['preprop'] == $min2x[$venue][$raceno]) ? "<font color=green>" : null;
            $ismax3x = ($odds[$venue][$raceno][$horseno]['Pre']['preprop'] == $max3x[$venue][$raceno]) ? "<font color=red>" : null;
            //-----------------------------------------------------------------------------------------------------------------------------
            $pick1 = (isset($pick[$raceno]['pick1']) && in_array($horseno, explode(",", $pick[$raceno]['pick1']))) ? " bgcolor=#99FF99 " : "";
            $pick2 = (isset($pick[$raceno]['pick2']) && in_array($horseno, explode(",", $pick[$raceno]['pick2']))) ? " bgcolor=#99FF99 " : "";
            $pick3 = (isset($pick[$raceno]['pick3']) && in_array($horseno, explode(",", $pick[$raceno]['pick3']))) ? " bgcolor=#99FF99 " : "";
            $pick4 = (isset($pick[$raceno]['pick4']) && in_array($horseno, explode(",", $pick[$raceno]['pick4']))) ? " bgcolor=lightgrey " : "";
            //-----------------------------------------------------------------------------------------------------------------------------

            
            $pageContent .= "<tr class='runner-row' data-race='{$venue}-{$raceno}'>";
            if($ismm){
                $pageContent .= "<td $issamepreprop1 ><span style=\"opacity: 0.5;\">$ismaxpreprop1{$odds[$venue][$raceno][$horseno]['Pre']['preprop1']}</span></td>";
                $pageContent .= "<td $issamepreprop >$ismax2x$ismax3x$ismaxpreprop{$odds[$venue][$raceno][$horseno]['Pre']['preprop']}$ismin2x</td>";
                $pageContent .= "<td $issameCurrprop >$ismaxprop{$odds[$venue][$raceno][$horseno]['Curr']['prop']}</td>";
                
                $minpropcurr = ($prophistcurr1st[$venue][$raceno][$horseno]['min'] >= $odds[$venue][$raceno][$horseno]['Pre']['preprop']) ? (($prophistcurr1st[$venue][$raceno][$horseno]['min'] == $odds[$venue][$raceno][$horseno]['Pre']['preprop']) ? "" : "<b>") : "<span style=\"opacity: 0.5;\">";
                $maxpropcurr = ($prophistcurr1st[$venue][$raceno][$horseno]['max'] >= $odds[$venue][$raceno][$horseno]['Pre']['preprop']) ? "<b>" : "<span style=\"opacity: 0.5;\">";
                $maxpropcurrpre = ($prophistcurr1st[$venue][$raceno][$horseno]['maxpre'] >= $odds[$venue][$raceno][$horseno]['Pre']['preprop']) ? "" : "<span style=\"opacity: 0.5;\">";
                $maxtop3 = (in_array($prophistcurr1st[$venue][$raceno][$horseno]['max'], $prophistcurr1st[$venue][$raceno]['max3'])) ? "<font color=red>" : "";
                $maxtop3pre = (in_array($prophistcurr1st[$venue][$raceno][$horseno]['maxpre'], $prophistcurr1st[$venue][$raceno]['max3pre'])) ? "<font color=red>" : "";
                $isupmaxpre = ($prophistcurr1st[$venue][$raceno][$horseno]['maxpre'] > $odds[$venue][$raceno][$horseno]['Pre']['preprop']) ? " bgcolor=lightyellow " : "";
                $isupmax = ($prophistcurr1st[$venue][$raceno][$horseno]['max'] > $odds[$venue][$raceno][$horseno]['Pre']['preprop']) ? " bgcolor=lightyellow " : "";
                $pageContent .= "<td >$minpropcurr{$prophistcurr1st[$venue][$raceno][$horseno]['min']}</td>";
                $pageContent .= "<td $isupmaxpre>$maxpropcurrpre$maxtop3pre{$prophistcurr1st[$venue][$raceno][$horseno]['maxpre']}</td>";
                $pageContent .= "<td $isupmax style=\"border-right: 2px solid #66CDFF;\">$maxpropcurr$maxtop3{$prophistcurr1st[$venue][$raceno][$horseno]['max']}</td>";
            }
            $alertWINdrop = ($odds[$venue][$raceno][$horseno]['Curr']['WIN'] > $odds[$venue][$raceno][$horseno]['Pre']['WINPre']) ? "<span style=\"opacity: 0.5;\">" : "";
            $pageContent .= "<td style=\"opacity: 0.5;\" $laterestplas $laterestplasmaygo>{$laterestWin_Oddss}</td>";
            $pageContent .= "<td $issameprewin>{$odds[$venue][$raceno][$horseno]['Pre']['WINPre']}</td>";
            $pageContent .= "<td style='text-align: right;'><b>$alertWINdrop{$odds[$venue][$raceno][$horseno]['Curr']['WIN']}</td>";
            $pageContent .= "<td><b>{$odds[$venue][$raceno][$horseno]['Curr']['PLA']}</td>";
            $pageContent .= "<td $PLAPreColor>$smallerprepla{$odds[$venue][$raceno][$horseno]['Pre']['PLAPre']}</td>";
            
            $pageContent .= "<td style=\"background-color:lightyellow; border-right: 2px solid #66CDFF;\">{$oddsDropValue}</td>";
            if($ismm){
                $isuptri20 = ($ttfq[$venue][$raceno][$horseno]['tri20_prelast'] < $ttfq[$venue][$raceno][$horseno]['tri20_10am']) ? "<b>" : "<span style=\"opacity: 0.5;\">";
                $isupttt20 = ($ttfq[$venue][$raceno][$horseno]['ttt20_prelast'] < $ttfq[$venue][$raceno][$horseno]['ttt20_10am']) ? "<b>" : "<span style=\"opacity: 0.5;\">";
                $isupfft20 = ($ttfq[$venue][$raceno][$horseno]['fft20_prelast'] < $ttfq[$venue][$raceno][$horseno]['fft20_10am']) ? "<b>" : "<span style=\"opacity: 0.5;\">";
                $isupqtt20 = ($ttfq[$venue][$raceno][$horseno]['qtt20_prelast'] < $ttfq[$venue][$raceno][$horseno]['qtt20_10am']) ? "<b>" : "<span style=\"opacity: 0.5;\">";
                
                $isuptri20_12 = ($ttfq[$venue][$raceno][$horseno]['tri20_prelast'] < $ttfq[$venue][$raceno][$horseno]['tri20_12am']) ? " bgcolor=lightyellow " : "";
                $isupttt20_12 = ($ttfq[$venue][$raceno][$horseno]['ttt20_prelast'] < $ttfq[$venue][$raceno][$horseno]['ttt20_12am']) ? " bgcolor=lightyellow " : "";
                $isupfft20_12 = ($ttfq[$venue][$raceno][$horseno]['fft20_prelast'] < $ttfq[$venue][$raceno][$horseno]['fft20_12am']) ? " bgcolor=lightyellow " : "";
                $isupqtt20_12 = ($ttfq[$venue][$raceno][$horseno]['qtt20_prelast'] < $ttfq[$venue][$raceno][$horseno]['qtt20_12am']) ? " bgcolor=lightyellow " : "";
                
                $tri20prelast = ($ttfq[$venue][$raceno][$horseno]['tri20_prelast'] ? $ttfq[$venue][$raceno][$horseno]['tri20_prelast'] : '');
                $ttt20prelast = ($ttfq[$venue][$raceno][$horseno]['ttt20_prelast'] ? $ttfq[$venue][$raceno][$horseno]['ttt20_prelast'] : '');
                $fft20prelast = ($ttfq[$venue][$raceno][$horseno]['fft20_prelast'] ? $ttfq[$venue][$raceno][$horseno]['fft20_prelast'] : '');
                $qtt20prelast = ($ttfq[$venue][$raceno][$horseno]['qtt20_prelast'] ? $ttfq[$venue][$raceno][$horseno]['qtt20_prelast'] : '');
                $pageContent .= "<td $isuptri20_12 >$isuptri20{$tri20prelast}</td>";
                $pageContent .= "<td $isupttt20_12 >$isupttt20{$ttt20prelast}</td>";
                $pageContent .= "<td $isupfft20_12 >$isupfft20{$fft20prelast}</td>";
                $pageContent .= "<td $isupqtt20_12 style=\"border-right: 2px solid #66CDFF;\">$isupqtt20{$qtt20prelast}</td>";
                
                $isuptri20_curr = ($ttfq[$venue][$raceno][$horseno]['tri20_12am'] < $odds[$venue][$raceno]['TRITop']['countArray'][$horseno]) ? " bgcolor=lightyellow " : "";
                $isupttt20_curr = ($ttfq[$venue][$raceno][$horseno]['ttt20_12am'] < $odds[$venue][$raceno]['TCETop']['countArray'][$horseno]) ? " bgcolor=lightyellow " : "";
                $isupfft20_curr = ($ttfq[$venue][$raceno][$horseno]['fft20_12am'] < $odds[$venue][$raceno]['FFTop']['countArray'][$horseno]) ? " bgcolor=lightyellow " : "";
                $isupqtt20_curr = ($ttfq[$venue][$raceno][$horseno]['qtt20_12am'] < $odds[$venue][$raceno]['QTTTop']['countArray'][$horseno]) ? " bgcolor=lightyellow " : "";
            }
            
            $pageContent .= "<td $isuptri20_curr>$istopTRITop{$odds[$venue][$raceno]['TRITop']['countArray'][$horseno]}</td>";
            $pageContent .= "<td $isupttt20_curr>$istopTCETop{$odds[$venue][$raceno]['TCETop']['countArray'][$horseno]}</td>";
            $pageContent .= "<td $isupfft20_curr>$istopTCETop{$odds[$venue][$raceno]['FFTop']['countArray'][$horseno]}</td>";
            $pageContent .= "<td $isupqtt20_curr style=\"border-right: 2px solid #66CDFF;\">$istopQTTTop{$odds[$venue][$raceno]['QTTTop']['countArray'][$horseno]}</td>";
            
            $horsenocircle = (is_numeric($horseno) && $horseno<=20) ? convertToCircledNumber($horseno) : $horseno;
            $pageContent .= "<td style='text-align: left;' $besttimealert>".$istopQINTop.$istopQPLTop.$last6rundot.$horsenocircle."</td>";
            
            $pageContent .= "<td>$ispla{$racedetail['finalPosition']}</td>";
            $pageContent .= "<td $pick1><input type=checkbox></td>";
            $pageContent .= "<td $pick2><input type=checkbox $ishotFavourite></td>";
            $pageContent .= "<td $pick3><input type=checkbox $istrumpCard>".$Rtgtrend[$horsecode]."</td>";
            
            $nextraceno = $horseno ? $raceno+1 : "";
            $totalMarks = calculateTotalMarks($jockeyplaarray[$venue][$racedetail['jockey']['name_ch']]);
            $nextnresult = $totalMarks ? $propposition[$venue][$nextraceno][$jockey]."[".$totalMarks : $propposition[$venue][$nextraceno][$jockey];
            $pageContent .= "<td style=\"font-size:10px;\">{$nextnresult}</td>";
            
            $pageContent .= "<td nowrap>{$distchange}</td>";
            // $pageContent .= "<td nowrap>{$bettinghistory[$date][$venue][$raceno][$horseno]['win_investment']}</td>";
                for ($i = 0; $i < 5; $i++) {
                    // $pageContent .= "<td nowrap>{$bettinghistory[$date][$venue][$raceno][$horseno]['win'][$i]}</td>";
                }
            // $pageContent .= "<td nowrap>{$bettinghistory[$date][$venue][$raceno][$horseno]['pla_investment']}</td>";
                $betpla_tmp = 0;
                for ($i = 0; $i < 5; $i++) {
                    $betpla = $bettinghistory[$date][$venue][$raceno][$horseno]['pla'][$i];
                    $disp = ($i && $betpla-$betpla_tmp>=2) ? ($betpla-$betpla_tmp>=4 ? "<font color=red>" : "<font color=blue>") : "";
                    $pageContent .= "<td style='text-align: right;font-size:11px;width:auto;' bgcolor=lightyellow nowrap>$disp{$betpla}</td>";
                    $betpla_tmp = $bettinghistory[$date][$venue][$raceno][$horseno]['pla'][$i];
                }
            $pageContent .= "<td nowrap>{$jockeygo}</td>";

                $pageContent .= "<td nowrap $jockeyplacntalert>{$jockeyplacnt}</td>";
                $runningPositions = $Running_Position[$horsecode][$race['distance']]; 
                // $output = isset($runningPositions) ? implode(',', $runningPositions) : "";
                $output = running_pos_jockey($merged_position[$horsecode], $racedetail['jockey']['name_ch'], $race['distance']);
                // $output = running_pos_jockey($Running_Positions[$horsecode], $racedetail['jockey']['name_ch'], $race['distance']);
                // $output = str_replace(' <span','<span',$output);
                $strlen = strlen($output);
                $pageContent .= "<td nowrap>".substr($output,0,5)."</td>";
            // }
            $pageContent .= "<td nowrap>{$barrierdayinterval}</td>";
            $pageContent .= "<td nowrap>".$horsename.($plural_sire[$date][$venue][$raceno][$horseno] && $horseno ? "<font color=red>".$plural_sire[$date][$venue][$raceno][$horseno] : "")."</td>";
            $pageContent .= "<td style='text-align: left;' nowrap>(<b>".$racedetail['barrierDrawNumber']."</b>)".mapColorsHtml($barrierDrawNumberpla[$date][$venue][$racedetail['barrierDrawNumber']])."</td>";
            $pageContent .= "<td nowrap>{$Wt[$date][$venue][$raceno][$horseno]}</td>";
            
            $pageContent .= "<td nowrap>{".$jockeytrainercount."}".mapColorsHtml($jockey_trainer_pla[$venue][$jt])."</td>";
            $pageContent .= "<td nowrap>[".$trainercountttl."]".mapColorsHtml($trainerpla[$venue][$racedetail['trainer']['name_ch']])."</td>";
            $pageContent .= "<td nowrap>".$isdifferenttrainer.$trainercnt.$racedetail['trainer']['name_ch']."</td>";
            $pageContent .= "<td nowrap>[".$jockeycountttl."]".mapColorsHtml($jockeypla[$venue][$racedetail['jockey']['name_ch']])."</td>";
            $pageContent .= "<td nowrap>{$racedetail['jockey']['name_ch']}</td>";
            if($ismm){
                $jockeyinpla = "";
                $samecurrentjockey = "";
                if(isset($horseinfo_jockeyinpla[$horsecode])){
                    $jockeyinpla = implode(',', array_unique($horseinfo_jockeyinpla[$horsecode]));
                    $jockeyinpla = mapColors($racedetail['jockey']['name_ch'],$jockeyinpla);
                    $samecurrentjockey = in_array($racedetail['jockey']['name_ch'], $horseinfo_jockeyinpla[$horsecode]) ? "<font color=blue>" : "";
                } 
                $pageContent .= "<td nowrap>{$jockeyinpla}</td>";
                $pageContent .= "<td nowrap $issamejockey>{$laterestjockey[$horsecode]}</td>";
            }
            
            // $pageContent .= "<td nowrap>$last6runalert{$racedetail['last6run']}</td>";colorstr
            $pageContent .= "<td nowrap $last6runalert>".colorstr($racedetail['last6run'])."</td>";
            
            
            
            // $pageContent .= "<td nowrap>".$besttime."</td>";
            $pageContent .= "</tr>"; // Close the runner row
            
            $tmp_PLAPre = $odds[$venue][$raceno][$horseno]['Pre']['PLAPre'];
            
            $statistic[$venue][$raceno][$horseno]['pos'] = $pos;
            $statistic[$venue][$raceno][$horseno][$pos] = $horseno;
            $statistic[$venue][$raceno][$horseno]['maxpreprop'] = $odds[$venue][$raceno]['maxpreprop'];
            $statistic[$venue][$raceno][$horseno]['samepropcount'] = $samepropcount[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['preprop']];
            $pos++;
        }
        $pageContent .= "</tbody>";
        $pageContent .= "</table>";
        // Prepare page data
        // $pageData = [
        //     'post_title'   => "Race Meeting - {$venue} Race No. {$raceno}",
        //     'post_content' => $pageContent,
        //     'post_status'  => 'publish',
        //     'post_author'  => 1, // Change to the appropriate user ID
        //     'post_type'    => 'post', // Change to 'page' for a WordPress page
        //     'post_template' => 'template-custom.php' // Optional: specify a custom page template
        // ];

        // // Insert the page into the database
        // wp_insert_post($pageData);
    }
    // print_r($statistic);
}
        
echo $pageContent;
if($_GET['mail'] ){
    // $emailtitle = "ai2";
	$ipAddress = substr($_SERVER['SERVER_ADDR'],-3);
	$Subject = "[horsepaper] -".$emailtitle." [AI5] ".$ipAddress;	//$date;
	$Body    = $pageContent;
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
?>

    <style>
        body {
            font-family: Calibri,Tahoma,Arial, sans-serif;
            font-size:13px;
            margin: 20px;
        }
        table {
            width1: 100%;
            border-collapse: collapse;
            font-family: Calibri,Tahoma,Arial, sans-serif;
            font-size:13px;
            white-space:nowrap;
            border: 1px solid #ddd;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 1px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
    </style>


<script type="text/javascript" >
function table_sort() {
    const styleSheet = document.createElement('style');
    styleSheet.innerHTML = `
        .order-inactive span {
            visibility: hidden;
        }
        .order-inactive:hover span {
            visibility: visible;
        }
        .order-active span {
            visibility: visible;
        }
    `;
    document.head.appendChild(styleSheet);

    document.querySelectorAll('th.order').forEach(th_elem => {
        let asc = true;
        const span_elem = document.createElement('span');
        span_elem.style = "font-size:0.8rem; margin-left:0.5rem";
        span_elem.innerHTML = "▼";
        th_elem.appendChild(span_elem);
        th_elem.classList.add('order-inactive');

        const index = Array.from(th_elem.parentNode.children).indexOf(th_elem);
        th_elem.addEventListener('click', (e) => {
            document.querySelectorAll('th.order').forEach(elem => {
                elem.classList.remove('order-active');
                elem.classList.add('order-inactive');
            });
            th_elem.classList.remove('order-inactive');
            th_elem.classList.add('order-active');

            // Toggle the sort direction indicator
            if (!asc) {
                th_elem.querySelector('span').innerHTML = '▲';
            } else {
                th_elem.querySelector('span').innerHTML = '▼';
            }

            const arr = Array.from(th_elem.closest("table").querySelectorAll('tbody tr'));
            arr.sort((a, b) => {
                const a_val = a.children[index].innerText.trim();
                const b_val = b.children[index].innerText.trim();

                // Determine if the column is for circled numbers or regular numbers
                let a_num, b_num;

                // Check if the value is a circled number
                if (isCircledNumber(a_val) && isCircledNumber(b_val)) {
                    a_num = circledToNumber(a_val);
                    b_num = circledToNumber(b_val);
                } else {
                    // Parse regular numbers
                    a_num = parseFloat(a_val) || 0; // Default to 0 if NaN
                    b_num = parseFloat(b_val) || 0; // Default to 0 if NaN
                }

                return asc ? a_num - b_num : b_num - a_num; // sort by number
            });

            arr.forEach(elem => {
                th_elem.closest("table").querySelector("tbody").appendChild(elem);
            });

            asc = !asc;
        });
    });
}

// Function to convert circled number to its numeric value
function circledToNumber(circledChar) {
    const unicodeValue = circledChar.codePointAt(0);
    return unicodeValue - 9312; // 9312 is the Unicode for '0'
}

// Function to check if a string is a circled number
function isCircledNumber(value) {
    const circledNumbers = ['①', '②', '③', '④', '⑤', '⑥', '⑦', '⑧', '⑨', '⑩', '⑪', '⑫', '⑬', '⑭', '⑮', '⑯', '⑰', '⑱', '⑲', '⑳'];
    return circledNumbers.includes(value);
}

table_sort();
</script>