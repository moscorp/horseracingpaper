<?php

// ini_set('max_execution_time', '3000000');
// error_reporting(E_ALL);
// error_reporting(E_ERROR | E_PARSE);

include_once ("lib/func_aii.php");
include_once ("lib/constants.php");

$mysqli = mysqli_connect(DB_HOSTwp, DB_USERwp, DB_PASSwp, DB_NAMEwp);
mysqli_set_charset($mysqli, "utf8mb4");

// $BrandNo = ['G272'];
// $racingdate = "2025-11-30";
// $test = load_horseinfo($BrandNo,$horsenamearray,$racingdate);
// print_r($test);

function load_horseinfo($BrandNo,$horsenamearray,$racingdate){
    if (!empty($BrandNo)) {
        global $mysqli;
        mysqli_set_charset($mysqli, "utf8mb4");
        $tmp_unique_horse_race = "";
        $oneyear = date("Y-9-1",strtotime("-1 year"));
        $onemonth = date("Y-m-d",strtotime("-1 month"));
        $oneweek = date("Y-m-d",strtotime("-1 week"));
        $racingdate = isset($racingdate) ? $racingdate : date("Y-m-d");
        $venue_cn_last = "";
        $pla_last = "";
        
        $brandNoImploded = "'" . implode("','", $BrandNo) . "'";
        $brandNoImploded_horsename = isset($horsenamearray) ? "'" . implode("','", $horsenamearray) . "'" : "''";
       
        // Construct the SQL query safely
        $horseinfosql = "
                    SELECT DISTINCT CONCAT(a.horseid, a.RaceIndex) AS unique_horse_race, a.Date,
                                    a.RaceIndex, a.horseid, a.horsename, a.Pla, a.RC_Track_Course, a.Dist, a.G, a.RaceClass, a.Dr, a.Rtg, a.Trainer, a.Jockey, a.LBW, a.Win_Odds, a.Act_Wt, a.Running_Position, a.Finish_Time, a.Declar_Wt, a.Gear,
                                    COUNT(b.horse) AS cntafterbarrier
                    FROM horseinfo AS a
                    LEFT JOIN barrierresult AS b ON a.horsename = b.horse AND b.barrierday > a.date
                    WHERE 
                    (a.horseid IN ($brandNoImploded) OR a.horsename IN ($brandNoImploded_horsename))
                    AND a.Date >= '$oneyear'
                    AND a.Date <> '$racingdate' 
                    GROUP BY a.horseid, a.RaceIndex
                    ORDER BY a.horseid, a.Date DESC, a.RaceIndex";
        // $horseinfosql = "
        //             SELECT DISTINCT CONCAT(a.horseid, a.RaceIndex) AS unique_horse_race, a.Date,
        //                             a.RaceIndex, a.horseid, CONVERT(a.horsename USING utf8mb4) AS horsename, a.Pla, a.RC_Track_Course, a.Dist, a.G, a.RaceClass, a.Dr, a.Rtg, a.Trainer, a.Jockey, a.LBW, a.Win_Odds, a.Act_Wt, a.Running_Position, a.Finish_Time, a.Declar_Wt, a.Gear,
        //                             '?' AS cntafterbarrier
        //             FROM horseinfo AS a
                
        //             WHERE 
        //             (a.horseid IN ($brandNoImploded) OR a.horsename IN ($brandNoImploded_horsename))
        //             AND a.Date >= '$oneyear'
        //             AND a.Date <> '$today' 
        //             GROUP BY a.horseid, a.RaceIndex
        //             ORDER BY a.Date DESC, a.horseid, a.RaceIndex";
                    
        // echo $horseinfosql;
        // Execute the horse info query
        $horseinforesult = $mysqli->query($horseinfosql);
        
        // Check if any horse info data was returned
        if ($horseinforesult && mysqli_num_rows($horseinforesult) > 0) {
            $Rtgcomparecnt = []; // Initialize the counter for comparisons
            $Rtgtrend = []; // Array to hold Rtg trends
            $previousValues = []; // Array to hold the previous values for comparison
            $horsehist = [];
            while ($horseinfo = mysqli_fetch_assoc($horseinforesult)) {
                // print_r($horseinfo);
                $horseid = $horseinfo['horseid'];
                $horsename = $horseinfo['horsename']; //htmlspecialchars($horseinfo['horsename'], ENT_QUOTES, 'UTF-8');
                $pla = $horseinfo['Pla'];
                $hrinfodate = $horseinfo['Date'];
                $jockey = htmlspecialchars($horseinfo['Jockey'], ENT_QUOTES, 'UTF-8');
                $trainer = htmlspecialchars($horseinfo['Trainer'], ENT_QUOTES, 'UTF-8');
                $jockey_trainer = $jockey.$trainer;
                $dist = intval($horseinfo['Dist']/100); // Converts to 10, 12, 14
                $distance = $horseinfo['Dist'];
                $Win_Odds = $horseinfo['Win_Odds'];
                $Rtg = $horseinfo['Rtg'];
                $go_ch = $horseinfo['G'];
                $Finish_Time = $horseinfo['Finish_Time'];
                $horseinfo_cntafterbarrier[$horseinfo['horsename']] = $horseinfo['cntafterbarrier'];
                $RC_Track_Course = trim(str_replace(['&quot;', '"'], '', $horseinfo['RC_Track_Course']));
                $venue_cn = mb_substr($horseinfo['RC_Track_Course'], 0, 2, 'UTF-8');
                
                preg_match('/["&quot;]([^"&quot;]+)["&quot;]/', $horseinfo['RC_Track_Course'], $matches);
                $RC_Track_Course = $matches[1] ?? null; // Extracted value
                    
                if (empty($horsehist[$horseid]['jockey'])) {
                    $horsehist[$horseid] = [
                        'horsename' => $horsename,
                        'jockey' => $jockey, 
                        'trainer' => $trainer,
                        'dist' => $dist,
                        'Win_Odds' => $Win_Odds,
                        'pla' => $pla,
                        'venue_cn' => $venue_cn
                    ];
                }
                
                // Initialize $Rtgcomparecnt for each horse if not already set
                if (!isset($horsehist[$horseid]['Rtgcomparecnt'])) {
                    $horsehist[$horseid]['Rtgcomparecnt'] = 0;
                }
    
                // Store values for comparison
                if (!isset($horsehist[$horseid]['Rtgcomparecnt']) || $horsehist[$horseid]['Rtgcomparecnt'] < 3) {
                    // Store current row's values for later comparison
                    $previousValues[$horseid][$Rtgcomparecnt[$horseid]] = ['Rtg' => $Rtg, 'Pla' => $pla];
                    $horsehist[$horseid]['Rtgtrend'] = (
                        $previousValues[$horseid][0]['Rtg'] < $previousValues[$horseid][1]['Rtg'] && 
                        $previousValues[$horseid][1]['Rtg'] < $previousValues[$horseid][2]['Rtg'] &&  
                        $previousValues[$horseid][0]['Pla'] < $previousValues[$horseid][1]['Pla'] && 
                        $previousValues[$horseid][1]['Pla'] < $previousValues[$horseid][2]['Pla']
                    ) ? $previousValues[$horseid][0]['Pla'] : ""; // Store 1st row's Pla or reset
                
                    // echo $horseid."-".$Rtgcomparecnt[$horseid]."-0".$previousValues[$horseid][0]['Rtg']."-1".$previousValues[$horseid][1]['Rtg']."-2".$previousValues[$horseid][2]['Rtg']."<br>";
                }
                
                $horsehist[$horseid]['Rtgcomparecnt']++;
            
                // echo $horseinfo['unique_horse_race']."-".$tmp_unique_horse_race."--".$jockey."-".$trainer."-".$dist."-".$go_ch."<br>";
                if ($horseinfo['unique_horse_race'] <> $tmp_unique_horse_race) {
                    // $horsehist[$horseid]['venue'][] = ['venue_cn' => $venue_cn, 'pla' => $pla];
                    // Check the condition
                    
                    if ($venue_cn !== $venue_cn_last && $pla_last < 4 && $pla>0 && $pla_last>0) {
                        $venue2dig = $venue_cn_last;
                        $tmpcnt[$horseid][$venue2dig]++;
                    } else {
                        $venue2dig = 0;
                    }
                    // echo $horseid."=".$venue_cn."=".$venue_cn_last."=".$pla."=".$pla_last."=".$tmpcnt[$horseid][$venue2dig]."<br>";
                    //--------------------
                    // Append the result to the venuechangepla array
                    $horsehist[$horseid]['venuechangepla'][] = $venue2dig;
                    $horsehist[$horseid]['venuechangeplacnt'][$venue2dig] = $tmpcnt[$horseid][$venue2dig];
                    // $horsehist[$horseid]['Finish_Time'][$dist][] = $Finish_Time;
                    $venue_cn_last = $venue_cn;
                    $pla_last = $pla;
                    
                    $currentTimeSec = array_reduce(explode('.', $Finish_Time), function($a, $b) { 
                        return $a * 60 + $b; 
                    }, 0);
                    
                    $existingTime = $horsehist[$horseid]['Finish_Time'][$distance] ?? null;
                    $existingTimeSec = $existingTime ? array_reduce(explode('.', $existingTime), function($a, $b) { 
                        return $a * 60 + $b; 
                    }, 0) : INF;
                    
                    if ($currentTimeSec < $existingTimeSec) {
                        $horsehist[$horseid]['Finish_Time'][$distance] = $Finish_Time;
                    }

                    
                    if ($pla <= 4) {
                        if (!isset($horsehist[$horseid]['venuetotalpla'])) {
                            $horsehist[$horseid]['venuetotalpla'][$venue_cn]=0;
                        }
                        $horsehist[$horseid]['venuetotalpla'][$venue_cn]++;
                        
                        // Initialize the 'inpla' array if it doesn't exist
                        if (!isset($horsehist[$horseid]['inpla'])) {
                            $horsehist[$horseid]['inpla'] = [];
                        }
                
                        // Initialize necessary keys in 'inpla'
                        $horsehist[$horseid]['inpla']['jockey'][] = $jockey;
                        $horsehist[$horseid]['inpla']['trainer'][] = $trainer;
                        $horsehist[$horseid]['inpla']['dist'][] = $dist;
                        $horsehist[$horseid]['inpla']['go_ch'][] = $go_ch;
                        $horsehist[$horseid]['inpla']['RC_Track_Course'][] = $RC_Track_Course;
                
                        // Initialize cnt_dist
                        if (!isset($horsehist[$horseid]['inpla']['cnt_dist'][$dist])) {
                            $horsehist[$horseid]['inpla']['cnt_dist'][$dist] = 0;
                        }
                        $horsehist[$horseid]['inpla']['cnt_dist'][$dist]++;
                
                        // Initialize cnt_jockey
                        if (!isset($horsehist[$horseid]['inpla']['cnt_jockey'][$jockey])) {
                            $horsehist[$horseid]['inpla']['cnt_jockey'][$jockey] = 0;
                        }
                        $horsehist[$horseid]['inpla']['cnt_jockey'][$jockey]++;
                
                        // Initialize cnt_trainer
                        if (!isset($horsehist[$horseid]['inpla']['cnt_trainer'][$trainer])) {
                            $horsehist[$horseid]['inpla']['cnt_trainer'][$trainer] = 0;
                        }
                        $horsehist[$horseid]['inpla']['cnt_trainer'][$trainer]++;
                
                        // Running Position
                        // Ensure the 'Running_Position' array is initialized
                        if (!isset($horsehist[$horseid]['inpla']['Running_Position'])) {
                            $horsehist[$horseid]['inpla']['Running_Position'] = [];
                        }
                        
                        // Ensure the specific index for $dist is initialized
                        if (!isset($horsehist[$horseid]['inpla']['Running_Position'][$dist])) {
                            $horsehist[$horseid]['inpla']['Running_Position'][$dist] = ''; // Initialize as an empty string
                        }
                        
                        // Append the result of determinePattern to the Running_Position for the specific dist
                        $horsehist[$horseid]['inpla']['Running_Position'][$dist] .= determinePattern($horseinfo['Running_Position']);
                
                        // Initialize cnt_jt for horse
                        if (!isset($horsehist[$horseid]['inpla']['cnt_jt'][$jockey_trainer])) {
                            $horsehist[$horseid]['inpla']['cnt_jt'][$jockey_trainer] = 0;
                        }
                        $horsehist[$horseid]['inpla']['cnt_jt'][$jockey_trainer]++;
                
                        // Initialize jt for all
                        if (!isset($horsehist['inpla']['jt'])) {
                            $horsehist['inpla']['jt'] = [];
                        }
                        if (!isset($horsehist['inpla']['jt'][$jockey_trainer])) {
                            $horsehist['inpla']['jt'][$jockey_trainer] = 0;
                        }
                        $horsehist['inpla']['jt'][$jockey_trainer]++;
                    } else {
                        if (!isset($horsehist[$horseid]['venuetotalplax'])) {
                            $horsehist[$horseid]['venuetotalplax'][$venue_cn]=0;
                        }
                        $horsehist[$horseid]['venuetotalplax'][$venue_cn]++;
                        
                        // Initialize the 'notinpla' array if it doesn't exist
                        if (!isset($horsehist[$horseid]['notinpla'])) {
                            $horsehist[$horseid]['notinpla'] = [];
                        }
                
                        // Initialize necessary keys in 'notinpla'
                        $horsehist[$horseid]['notinpla']['jockey'][] = $jockey;
                        $horsehist[$horseid]['notinpla']['trainer'][] = $trainer;
                
                        // Initialize cnt_jockey
                        if (!isset($horsehist[$horseid]['notinpla']['cnt_jockey'][$jockey])) {
                            $horsehist[$horseid]['notinpla']['cnt_jockey'][$jockey] = 0;
                        }
                        $horsehist[$horseid]['notinpla']['cnt_jockey'][$jockey]++;
                
                        // Initialize cnt_dist
                        if (!isset($horsehist[$horseid]['notinpla']['cnt_dist'][$dist])) {
                            $horsehist[$horseid]['notinpla']['cnt_dist'][$dist] = 0;
                        }
                        $horsehist[$horseid]['notinpla']['cnt_dist'][$dist]++;
                
                        // Initialize cnt_jt
                        if (!isset($horsehist[$horseid]['notinpla']['cnt_jt'][$jockey_trainer])) {
                            $horsehist[$horseid]['notinpla']['cnt_jt'][$jockey_trainer] = 0;
                        }
                        $horsehist[$horseid]['notinpla']['cnt_jt'][$jockey_trainer]++;
                
                        // Initialize cnt_RC_Track_Course
                        if (!isset($horsehist[$horseid]['notinpla']['cnt_RC_Track_Course'][$RC_Track_Course])) {
                            $horsehist[$horseid]['notinpla']['cnt_RC_Track_Course'][$RC_Track_Course] = 0;
                        }
                        $horsehist[$horseid]['notinpla']['cnt_RC_Track_Course'][$RC_Track_Course]++;
                
                        // Initialize jt
                        if (!isset($horsehist['notinpla']['jt'])) {
                            $horsehist['notinpla']['jt'] = [];
                        }
                        if (!isset($horsehist['notinpla']['jt'][$jockey_trainer])) {
                            $horsehist['notinpla']['jt'][$jockey_trainer] = 0;
                        }
                        $horsehist['notinpla']['jt'][$jockey_trainer]++;
                    }
                }
                $tmp_unique_horse_race = $horseinfo['unique_horse_race'];
            }
        } else {
            // echo "E179";    //"No horse info data found.";
        }
    } else {
        // echo "E182";    //"No BrandNo found, cannot query horse info.";
    }
    // print_r($horsehist);
    return $horsehist;    
}



?>
