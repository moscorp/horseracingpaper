<?php

ini_set('max_execution_time', '3000000');
error_reporting(E_ALL);
// error_reporting(E_ERROR | E_PARSE);

// include_once ("lib/func_aii.php");
// include_once ("lib/constants.php");

// $mysqli = mysqli_connect(DB_HOSTwp, DB_USERwp, DB_PASSwp, DB_NAMEwp);
// $oneyear = date("Y-m-d",strtotime("-2 year"));
// $onemonth = date("Y-m-d",strtotime("-1 month"));
// $oneweek = date("Y-m-d",strtotime("-1 week"));

// $BrandNo = ['H339','H339'];
// print_r($BrandNo);
echo 1;
// if (!empty($BrandNo)) {
//     // Prepare the BrandNo for the SQL query
//     // Use implode to create a comma-separated string
//     // $brandNoImploded = implode(',', array_map('intval', $BrandNo)); // Ensure all values are integers

//     $brandNoImploded = "'" . implode("','", $BrandNo) . "'";
//     $today = date("Y-m-d");
    
//     // Construct the SQL query safely
//     $horseinfosql = "SELECT DISTINCT CONCAT(horseid, RaceIndex) AS unique_horse_race,Date,
//                         RaceIndex,horseid,horsename, Pla,RC_Track_Course,Dist,G,RaceClass,Dr,Rtg,Trainer,Jockey,LBW,Win_Odds,Act_Wt,Running_Position,Finish_Time,Declar_Wt,Gear
//                         FROM horseinfo as a
//                         WHERE horseid IN ($brandNoImploded) 
//                         and date>'$oneyear'
//                         and date<>'$today' 
//                         order by date desc, horseid,RaceIndex";
//     $horseinfosql = "
//                 SELECT DISTINCT CONCAT(a.horseid, a.RaceIndex) AS unique_horse_race, a.Date,
//                                 a.RaceIndex, a.horseid, a.horsename, a.Pla, a.RC_Track_Course, a.Dist, a.G, a.RaceClass, a.Dr, a.Rtg, a.Trainer, a.Jockey, a.LBW, a.Win_Odds, a.Act_Wt, a.Running_Position, a.Finish_Time, a.Declar_Wt, a.Gear,
//                                 COUNT(b.horse) AS cntafterbarrier
//                 FROM horseinfo AS a
//                 LEFT JOIN barrierresult AS b ON b.horse = a.horsename AND b.barrierday > a.date
//                 WHERE a.horseid IN ($brandNoImploded) 
//                 AND a.date > '$oneyear'
//                 AND a.date <> '$today' 
//                 GROUP BY a.horseid, a.RaceIndex
//                 ORDER BY a.date DESC, a.horseid, a.RaceIndex";

//     // echo $horseinfosql1;
//     // Execute the horse info query
//     $horseinforesult = $mysqli->query($horseinfosql);

//     // Check if any horse info data was returned
//     if ($horseinforesult && mysqli_num_rows($horseinforesult) > 0) {
//         $Rtgcomparecnt = []; // Initialize the counter for comparisons
//         $Rtgtrend = []; // Array to hold Rtg trends
//         $previousValues = []; // Array to hold the previous values for comparison
//         while ($horseinfo = mysqli_fetch_assoc($horseinforesult)) {
//             $horseid = $horseinfo['horseid'];
//             $pla = $horseinfo['Pla'];
//             $hrinfodate = $horseinfo['Date'];
//             $jockey = $horseinfo['Jockey'];
//             $trainer = $horseinfo['Trainer'];
//             $dist = $horseinfo['Dist'];
//             $Win_Odds = $horseinfo['Win_Odds'];
//             $Rtg = $horseinfo['Rtg'];
//             $horseinfo_cntafterbarrier[$horseinfo['horsename']] = $horseinfo['cntafterbarrier'];
            
//             // Initialize $Rtgcomparecnt for each horse if not already set
//             if (!isset($Rtgcomparecnt[$horseid])) {
//                 $Rtgcomparecnt[$horseid] = 0;
//             }

//             // Store values for comparison
//             if (!isset($Rtgcomparecnt[$horseid]) || $Rtgcomparecnt[$horseid] < 3) {
//                 // Store current row's values for later comparison
//                 $previousValues[$horseid][$Rtgcomparecnt[$horseid]] = ['Rtg' => $Rtg, 'Pla' => $pla];
//                 $Rtgtrend[$horseid] = (
//                     $previousValues[$horseid][0]['Rtg'] < $previousValues[$horseid][1]['Rtg'] && 
//                     $previousValues[$horseid][1]['Rtg'] < $previousValues[$horseid][2]['Rtg'] &&  
//                     $previousValues[$horseid][0]['Pla'] < $previousValues[$horseid][1]['Pla'] && 
//                     $previousValues[$horseid][1]['Pla'] < $previousValues[$horseid][2]['Pla']
//                 ) ? $previousValues[$horseid][0]['Pla'] : ""; // Store 1st row's Pla or reset
            
//                 // echo $horseid."-".$Rtgcomparecnt[$horseid]."-0".$previousValues[$horseid][0]['Rtg']."-1".$previousValues[$horseid][1]['Rtg']."-2".$previousValues[$horseid][2]['Rtg']."<br>";
//             }
            
//             $Rtgcomparecnt[$horseid]++;
            
//             // $Rtgcomparecnt++; // Increment the counter
                
//             if (empty($laterestjockey[$horseid])) {
//                 $laterestjockey[$horseid] = $jockey;
//                 $lateresttrainer[$horseid] = $trainer;
//                 $laterestdist[$horseid] = $dist;
//                 $laterestWin_Odds[$horseid] = $Win_Odds;
//                 $laterestpla[$horseid] = $pla;
//             }
            
//             if($horseinfo['unique_horse_race'] <> $tmp_unique_horse_race){
//                 // $horseinfo_datepla[$horseid]['date'] = $hrinfodate;
//                 $horseinfo_datepla[$horseid][$hrinfodate] = $pla;
                
//                 $horseinfo_jockeyinpla[$horseid][] = ($pla<4) ? $jockey : "";
//                 $horseinfo_distinpla[$horseid][] = ($pla<4) ? $dist : "";
//                 // if($horseid == 'H034' && $dist =='1600'){
//                 //     echo $horseid.$dist.$jockey.$horseinfo['Running_Position']."-".determinePattern($horseinfo['Running_Position'])."<br>";
//                 // }
//                 $Running_Position[$horseid][$dist] .= determinePattern($horseinfo['Running_Position']);
//                 $Running_Position_jockey[$horseid][$dist] .= $jockey.";";
                
//                 $Running_Positions[$horseid][$dist]['position'] .= determinePattern($horseinfo['Running_Position']);
//                 $Running_Positions[$horseid][$dist]['jockeys'] .= $jockey.";";
//                 $Running_Positions[$horseid][$dist]['pla'] .= $pla.";";
                
//                 //---------------------------
//                 $horseinfo_jockeyinpla_cnt[$horseid]++;
//                 $horseinfo_jockeyinpla_cntpla[$horseid] = ($pla<4) ? $horseinfo_jockeyinpla_cntpla[$horseid]+1 : $horseinfo_jockeyinpla_cntpla[$horseid];
                
//             }
//             $tmp_unique_horse_race = $horseinfo['unique_horse_race'];
//         }
//     } else {
//         // echo "E179";    //"No horse info data found.";
//     }
// } else {
//     // echo "E182";    //"No BrandNo found, cannot query horse info.";
// }
// print_r($Rtgtrend);
// print_r($Running_Position);
// print_r($Running_Position_jockey);
// print_r($horseinfo_jockeyinpla_cnt);
// print_r($horseinfo_jockeyinpla_cntpla);



?>
