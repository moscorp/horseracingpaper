<?php
// error_reporting(E_ERROR | E_PARSE);
// error_reporting(E_ALL);
error_reporting(E_ALL & ~E_NOTICE);

include_once ("lib/func_aii.php");
include_once ("lib/func_basedata.php");
include_once ("lib/func_mailer_gmail.php");
include_once ("lib/constants.php");
include_once ("load_horseinfo.php");
include_once ("load_oddsdrop.php");

$oneyear = date("Y-m-d",strtotime("-1 year"));
$onemonth = date("Y-m-d",strtotime("-1 month"));
$oneweek = date("Y-m-d",strtotime("-1 week"));
$today = date("Y-m-d");
$ismm = (isset($_GET['mm']) && $_GET['mm']) ? 1 : 0;
$istext = (isset($_GET['text']) && $_GET['text']) ? 1 : 0;
$date = null; // Initialize $date
$venue = null; // Initialize $venue
$resultOddsType = null; // Initialize $resultOddsType

$basedata = basedata($date,$venue,$resultOddsType);
$activeMeetings = $basedata['data']['activeMeetings'];
$raceMeetings = $basedata['data']['raceMeetings'];
// print_r($raceMeetings);

$racingdata = [];
foreach ($raceMeetings as $meeting) {
    $racingdata['id'] = $meeting['id'];
    $racingdata['venueCode'] = $meeting['venueCode'];
    $racingdata['date'] = $meeting['date'];
    $racingdata['status'] = $meeting['status'];
    
    $allvenue[] = [
        'date' => $meeting['date'],
        'venueCode' => $meeting['venueCode'],
    ];
    
    $racingdatadate = $racingdata['date'];
    $racingdatavenue = $racingdata['venueCode'];
    foreach ($meeting['races'] as $race) {
        $raceno = $race['no'];
        $racingdata[$racingdatadate][$racingdatavenue][$raceno]['postTime'] = $race['postTime'];
        $racingdata[$racingdatadate][$racingdatavenue][$raceno]['raceName_ch'] = $race['raceName_ch'];
        $racingdata[$racingdatadate][$racingdatavenue][$raceno]['country_ch'] = $race['country_ch'];
        $racingdata[$racingdatadate][$racingdatavenue][$raceno]['distance'] = $race['distance'];
        $racingdata[$racingdatadate][$racingdatavenue][$raceno]['go_ch'] = $race['go_ch'];
        $racingdata[$racingdatadate][$racingdatavenue][$raceno]['ratingType'] = $race['ratingType'];
        $racingdata[$racingdatadate][$racingdatavenue][$raceno]['raceTrack'] = $race['raceTrack']['description_ch'];
        $racingdata[$racingdatadate][$racingdatavenue][$raceno]['wageringFieldSize'] = $race['wageringFieldSize'];
        $racingdata[$racingdatadate][$racingdatavenue][$raceno]['raceClass_ch'] = $race['raceClass_ch'];
        $racingdata[$racingdatadate][$racingdatavenue][$raceno]['raceCourse'] = $race['raceCourse']['displayCode'];
        
        foreach ($race['runners'] as $runners) {
            $horseno = $runners['no'];// ? $runners['no'] : '99';
            $jockey = $runners['jockey']['name_ch'];
            $trainer = $runners['trainer']['name_ch'];
            $jockey_trainer = $runners['jockey']['name_ch'].$runners['trainer']['name_ch'];
            
            $racingdata[$racingdatadate][$racingdatavenue][$raceno][$horseno]['horseno'] = $runners['no'];
            $racingdata[$racingdatadate][$racingdatavenue][$raceno][$horseno]['name_ch'] = $runners['name_ch'];
            $racingdata[$racingdatadate][$racingdatavenue][$raceno][$horseno]['code'] = $runners['horse']['code'];
            $racingdata[$racingdatadate][$racingdatavenue][$raceno][$horseno]['barrierDrawNumber'] = $runners['barrierDrawNumber'];
            $racingdata[$racingdatadate][$racingdatavenue][$raceno][$horseno]['handicapWeight'] = $runners['handicapWeight'];
            $racingdata[$racingdatadate][$racingdatavenue][$raceno][$horseno]['currentWeight'] = $runners['currentWeight'];
            $racingdata[$racingdatadate][$racingdatavenue][$raceno][$horseno]['currentRating'] = 1;//$runners['currentRating']*1;
            $racingdata[$racingdatadate][$racingdatavenue][$raceno][$horseno]['gearInfo'] = $runners['gearInfo'];
            $racingdata[$racingdatadate][$racingdatavenue][$raceno][$horseno]['trainerPreference'] = $runners['trainerPreference'];
            $racingdata[$racingdatadate][$racingdatavenue][$raceno][$horseno]['last6run'] = $runners['last6run'];
            $racingdata[$racingdatadate][$racingdatavenue][$raceno][$horseno]['trumpCard'] = $runners['trumpCard'];
            $racingdata[$racingdatadate][$racingdatavenue][$raceno][$horseno]['priority'] = $runners['priority'];
            $racingdata[$racingdatadate][$racingdatavenue][$raceno][$horseno]['finalPosition'] = $runners['finalPosition'];
            $racingdata[$racingdatadate][$racingdatavenue][$raceno][$horseno]['deadHeat'] = $runners['deadHeat'];
            $racingdata[$racingdatadate][$racingdatavenue][$raceno][$horseno]['winOdds'] = $runners['winOdds'];
            $racingdata[$racingdatadate][$racingdatavenue][$raceno][$horseno]['jockey'] = $runners['jockey']['name_ch'];
            $racingdata[$racingdatadate][$racingdatavenue][$raceno][$horseno]['trainer'] = $runners['trainer']['name_ch'];
            
            // $racingdata[$racingdatadate][$racingdatavenue]['BrandNo'][] = $runners['horse']['code'];
            $BrandNoarray[] = $runners['horse']['code'];
            $horsenamearray[] = $runners['name_ch'];
            $racingdata[$racingdatadate][$racingdatavenue]['horsename'][] = $runners['name_ch'];
            // $racingdata[$racingdatadate][$racingdatavenue]['jockeycnt'][$jockey]++;
            // Ensure the racing date and venue indices are set
            if (!isset($racingdata[$racingdatadate][$racingdatavenue])) {
                $racingdata[$racingdatadate][$racingdatavenue] = [
                    'jockeycnt' => [], // Initialize the jockey count array
                ];
            }
            
            // Ensure the jockey index is set
            if (!isset($racingdata[$racingdatadate][$racingdatavenue]['jockeycnt'][$jockey])) {
                $racingdata[$racingdatadate][$racingdatavenue]['jockeycnt'][$jockey] = 0; // Initialize the count
            }
            
            // Increment the jockey count
            $racingdata[$racingdatadate][$racingdatavenue]['jockeycnt'][$jockey]++;
            // $racingdata[$racingdatadate][$racingdatavenue]['trainercnt'][$trainer]++;
            // Ensure the racing date and venue indices are set
            if (!isset($racingdata[$racingdatadate][$racingdatavenue])) {
                $racingdata[$racingdatadate][$racingdatavenue] = [
                    'trainercnt' => [], // Initialize the trainer count array
                ];
            }
            
            // Ensure the trainer index is set
            if (!isset($racingdata[$racingdatadate][$racingdatavenue]['trainercnt'][$trainer])) {
                $racingdata[$racingdatadate][$racingdatavenue]['trainercnt'][$trainer] = 0; // Initialize the count
            }
            // Increment the trainer count
            $racingdata[$racingdatadate][$racingdatavenue]['trainercnt'][$trainer]++;
            // $racingdata[$racingdatadate][$racingdatavenue]['jockeytrainercnt'][$jockey_trainer]++;
            // Ensure the racing date and venue indices are set
            if (!isset($racingdata[$racingdatadate][$racingdatavenue])) {
                $racingdata[$racingdatadate][$racingdatavenue] = [
                    'jockeytrainercnt' => [], // Initialize the jockey-trainer count array
                ];
            }
            
            // Ensure the jockey-trainer index is set
            if (!isset($racingdata[$racingdatadate][$racingdatavenue]['jockeytrainercnt'][$jockey_trainer])) {
                $racingdata[$racingdatadate][$racingdatavenue]['jockeytrainercnt'][$jockey_trainer] = 0; // Initialize the count
            }
            
            // Increment the jockey-trainer count
            $racingdata[$racingdatadate][$racingdatavenue]['jockeytrainercnt'][$jockey_trainer]++;
            
        }
    }
    // $BrandNoarray = $racingdata[$racingdatadate][$racingdatavenue]['BrandNo'];
    //data for foPools
    foreach ($meeting['foPools'] as $foPools) {
        $oddsType = $foPools['oddsType']; // Get the odds type
        foreach ($foPools['selections'] as $selection) { // Use $selection instead of $selections
            $order = $selection['order']; // Get the order
            $name_ch = $selection['name_ch'];
            // Populate the $racingdata array correctly
            $racingdata[$racingdatadate][$racingdatavenue][$oddsType][$order]['name_ch'] = $selection['name_ch'];
            $racingdata[$racingdatadate][$racingdatavenue][$oddsType][$order]['scheduleRides'] = $selection['scheduleRides'];
            $racingdata[$racingdatadate][$racingdatavenue][$oddsType][$order]['remainingRides'] = $selection['remainingRides'];
            $racingdata[$racingdatadate][$racingdatavenue][$oddsType][$order]['openOdds'] = $selection['openOdds'];
            $racingdata[$racingdatadate][$racingdatavenue][$oddsType][$order]['currentOdds'] = $selection['currentOdds'];
            
            $racingdata[$racingdatadate][$racingdatavenue][$oddsType][$order][$name_ch]['order'] = $order;
            $racingdata[$racingdatadate][$racingdatavenue][$oddsType][$order][$name_ch]['openOdds'] = $selection['openOdds'];
            $racingdata[$racingdatadate][$racingdatavenue][$oddsType][$order][$name_ch]['currentOdds'] = $selection['currentOdds'];
        }
    }
    
    
}

// print_r($racingdata[$racingdatadate][$racingdatavenue]['BrandNo']);
// print_r($BrandNoarray);
$horseinfo = load_horseinfo($BrandNoarray,$horsenamearray);
$oddsdrop = load_oddsdrop($today,$BrandNoarray);

// print_r($horseinfo);

// echo "<br><br><br><br>";
// print_r($basedata);
// echo "<br><br><br><br>";
// print_r($activeMeetings);
// echo "<br><br><br><br>";
// print_r($raceMeetings);

$oddsMerged = [];
$prophistcurr1st = []; // Ensure $prop is an array before use
// foreach ($racingdata as $rsdata) {
foreach ($allvenue as $rsdata) {    
    $odds = [];
    $venueCode = $rsdata['venueCode'];
    $date = $rsdata['date'];
    
    $CurrPre = 'Pre';
    $oddsPre = pickodds($oddsarray, $date, $venueCode, $raceNo, $CurrPre);
    // print_r($oddsPre);    
    $CurrPre = 'Curr';
    $oddsCurr = pickodds($oddsarray, $date, $venueCode, $raceNo, $CurrPre);
    // print_r($oddsCurr);
    //-----------------------------------------
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
            if(is_numeric($horseno) === true){
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
                        
                        // echo $venue."-".$race."-".$oddsMerged[$venue][$race]['maxpreprop'].":".$plapre."-".$winpre."-".$preprop."ccccccccccccccc<br>";
                        
                        $oddsMerged[$venue][$race][$horseno]['Pre']['issmaller'] = ($tmp_plapre && $plapre && ($tmp_plapre > $plapre)) ? 1 : 0;
                        $tmp_plapre = $plapre;
                    } else {
                        $oddsMerged[$venue][$race][$horseno]['Pre']['preprop'] = 0;//null; // Handle division by zero case
                        $oddsMerged[$venue][$race][$horseno]['Pre']['preprop1'] = 0;//null; // Handle division by zero case
                        $oddsMerged[$venue][$race][$horseno]['Pre']['issmaller'] = 0;//null;
                        // echo $oddsMerged[$venue][$race]['maxpreprop'].":".$plapre."-".$winpre."-".$oddsMerged[$venue][$race]['maxpreprop1']."ddddddddddd<br>";
                        $tmp_plapre = "";
                    }
                }else{
                    $oddsMerged[$venue][$race][$horseno]['Pre']['preprop'] = $prophistcurr1st[$venue][$race][$horseno]['prop_curr1st'];   //
                }
            }
            // echo $horseno."vvvvvvvvvvvvvvv<br>";
    
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
    
    foreach ($racingdata[$date][$venueCode] as $raceno => $racedetail) {
        if (is_numeric($raceno)) {
            foreach ($racedetail as $horseno => $horsedetail) {
                // Ensure initialization before using
                if (!isset($racingdata[$date][$venueCode][$raceno]['prop']['max2x'])) {
                    $racingdata[$date][$venueCode][$raceno]['prop']['max2x'] = 0; // or another initial value
                }
                
                if (!isset($racingdata[$date][$venueCode][$raceno]['prop']['min2x'])) {
                    $racingdata[$date][$venueCode][$raceno]['prop']['min2x'] = PHP_INT_MAX; // Initialize to maximum integer
                }
                if (!isset($racingdata[$date][$venueCode][$raceno]['prop']['max3x'])) {
                    $racingdata[$date][$venueCode][$raceno]['prop']['max3x'] = 0; // or another initial value
                }
                
                if (!isset($racingdata[$date][$venueCode][$raceno]['prop']['min3x'])) {
                    $racingdata[$date][$venueCode][$raceno]['prop']['min3x'] = PHP_INT_MAX; // Initialize to maximum integer
                }

                if (is_numeric($horseno)) {
                    $proppre = $oddsMerged[$venue][$raceno][$horseno]['Pre']['proppre'];
                    
                     $racingdata[$date][$venueCode][$raceno][$horseno]['Pre']['WINPre'] = $oddsMerged[$venue][$raceno][$horseno]['Pre']['WINPre'];
                     $racingdata[$date][$venueCode][$raceno][$horseno]['Pre']['PLAPre'] = $oddsMerged[$venue][$raceno][$horseno]['Pre']['PLAPre'];
                     $racingdata[$date][$venueCode][$raceno][$horseno]['Pre']['proppre'] = $proppre;
                     $racingdata[$date][$venueCode][$raceno][$horseno]['Curr']['WIN'] = $oddsMerged[$venue][$raceno][$horseno]['Curr']['WIN'];
                     $racingdata[$date][$venueCode][$raceno][$horseno]['Curr']['PLA'] = $oddsMerged[$venue][$raceno][$horseno]['Curr']['PLA'];
                     $racingdata[$date][$venueCode][$raceno][$horseno]['Curr']['prop'] = $oddsMerged[$venue][$raceno][$horseno]['Curr']['prop'];
                     $racingdata[$date][$venueCode][$raceno][$horseno]['Curr']['oddsDropValue'] = $oddsMerged[$venue][$raceno][$horseno]['Curr']['oddsDropValue'];
                     
                     $racingdata[$date][$venueCode][$raceno]['prop']['maxproppre'] = $oddsMerged[$venue][$raceno]['maxpreprop'];
                     $racingdata[$date][$venueCode][$raceno]['prop']['maxproppre1'] = $oddsMerged[$venue][$raceno]['maxpreprop1'];
                     $racingdata[$date][$venueCode][$raceno]['prop']['maxpropcurr'] = $oddsMerged[$venue][$raceno]['maxcurrprop'];
                     
                    // $samepreplacount[$venue][$raceno][$PLAPre]++;
                    if (isset($proppre) && $proppre >= 20 && $proppre < 30) {
                        $racingdata[$date][$venueCode][$raceno]['prop']['max2xpre'] = max($racingdata[$date][$venueCode][$raceno]['prop']['max2xpre'], $proppre);
                        $racingdata[$date][$venueCode][$raceno]['prop']['min2xpre'] = min($racingdata[$date][$venueCode][$raceno]['prop']['min2xpre'], $proppre);
                    }
                    if (isset($proppre) && $proppre >= 30 && $proppre < 40) {
                        $racingdata[$date][$venueCode][$raceno]['prop']['max3xpre'] = max($racingdata[$date][$venueCode][$raceno]['prop']['max3xpre'], $proppre);
                        $racingdata[$date][$venueCode][$raceno]['prop']['min3xpre'] = min($racingdata[$date][$venueCode][$raceno]['prop']['min3xpre'], $proppre);
                    }
                    
                    if (isset($propcurr) && $propcurr >= 20 && $propcurr < 30) {
                        $racingdata[$date][$venueCode][$raceno]['prop']['max2xcurr'] = max($racingdata[$date][$venueCode][$raceno]['prop']['max2xcurr'], $propcurr);
                        $racingdata[$date][$venueCode][$raceno]['prop']['min2xcurr'] = min($racingdata[$date][$venueCode][$raceno]['prop']['min2xcurr'], $propcurr);
                    }
                    if (isset($proppre) && $proppre >= 30 && $proppre < 40) {
                        $racingdata[$date][$venueCode][$raceno]['prop']['max3xcurr'] = max($racingdata[$date][$venueCode][$raceno]['prop']['max3xcurr'], $propcurr);
                        $racingdata[$date][$venueCode][$raceno]['prop']['min3xcurr'] = min($racingdata[$date][$venueCode][$raceno]['prop']['min3xcurr'], $propcurr);
                    }
                                 
                    if (isset($oddsMerged[$venueCode][$raceno][$horseno]['Pre']['WINPre'])) {
                        $proppre = $oddsMerged[$venueCode][$raceno][$horseno]['Pre']['proppre'];
                        
                        // Initialize the count for the proppre if not set
                        if (!isset($racingdata[$date][$venueCode][$raceno][$proppre])) {
                            $racingdata[$date][$venueCode][$raceno]['cnt_'.$proppre] = 0;
                        }
                        $racingdata[$date][$venueCode][$raceno]['cnt_'.$proppre]++;
                        // echo $venueCode.$raceno.$proppre.$racingdata[$date][$venueCode][$raceno][$proppre]."<br>";
                        // Determine counts based on proppre value
                        if ($proppre >= 30 && $proppre < 40) {
                            $racingdata[$date][$venueCode][$raceno]['cnt_3x']++;
                            $racingdata[$date][$venueCode][$raceno]['cnt_3-4']++;
                            $racingdata[$date][$venueCode][$raceno]['arr_3-4'][] = $proppre;
                        } elseif ($proppre >= 20 && $proppre < 30) {
                            $racingdata[$date][$venueCode][$raceno]['cnt_2x']++;
                            $racingdata[$date][$venueCode][$raceno]['cnt_2-3']++;
                            $racingdata[$date][$venueCode][$raceno]['arr_2-3'][] = $proppre;
                        } elseif ($proppre >= 10 && $proppre < 20) {
                            $racingdata[$date][$venueCode][$raceno]['cnt_1x']++;
                            $racingdata[$date][$venueCode][$raceno]['cnt_1-2']++;
                            $racingdata[$date][$venueCode][$raceno]['arr_1-2'][] = $proppre;
                        } elseif ($proppre >= 0 && $proppre < 10) {
                            $racingdata[$date][$venueCode][$raceno]['cnt_0x']++;
                            $racingdata[$date][$venueCode][$raceno]['cnt_0-1']++;
                            $racingdata[$date][$venueCode][$raceno]['arr_0-1'][] = $proppre;
                        } elseif ($proppre >= 40 && $proppre < 50) {
                            $racingdata[$date][$venueCode][$raceno]['cnt_4x']++;
                            $racingdata[$date][$venueCode][$raceno]['cnt_4-5']++;
                            $racingdata[$date][$venueCode][$raceno]['arr_4-5'][] = $proppre;
                        } elseif ($proppre >= 50 && $proppre < 60) {
                            $racingdata[$date][$venueCode][$raceno]['cnt_5x']++;
                            $racingdata[$date][$venueCode][$raceno]['cnt_5-6']++;
                            $racingdata[$date][$venueCode][$raceno]['arr_5-6'][] = $proppre;
                        } elseif ($proppre >= 60 ) {
                            $racingdata[$date][$venueCode][$raceno]['cnt_xx']++;
                            $racingdata[$date][$venueCode][$raceno]['cnt_6x']++;
                            $racingdata[$date][$venueCode][$raceno]['arr_6x'][] = $proppre;
                        }
                    } else {
                        // Initialize count fields to zero if needed
                        // if (!isset($racingdata[$date][$venueCode][$raceno]['cnt_1x'])) {
                            $racingdata[$date][$venueCode][$raceno]['cnt_0-1'] = 0;
                            $racingdata[$date][$venueCode][$raceno]['cnt_1-2'] = 0;
                            $racingdata[$date][$venueCode][$raceno]['cnt_2-3'] = 0;
                            $racingdata[$date][$venueCode][$raceno]['cnt_3-4'] = 0;
                            $racingdata[$date][$venueCode][$raceno]['cnt_4-5'] = 0;
                            $racingdata[$date][$venueCode][$raceno]['cnt_5-6'] = 0;
                            $racingdata[$date][$venueCode][$raceno]['arr_0-1'] = 0;
                            $racingdata[$date][$venueCode][$raceno]['arr_1-2'] = 0;
                            $racingdata[$date][$venueCode][$raceno]['arr_2-3'] = 0;
                            $racingdata[$date][$venueCode][$raceno]['arr_3-4'] = 0;
                            $racingdata[$date][$venueCode][$raceno]['arr_4-5'] = 0;
                            $racingdata[$date][$venueCode][$raceno]['arr_5-6'] = 0;
                            
                        // }
                    }
                }
            }
        }
    }
            
    // Initialize the sorted array
    $racingdata_sorted = $racingdata; // Start by copying the original array
    
    if (isset($racingdata[$date][$venueCode]) && is_array($racingdata[$date][$venueCode])) {
        foreach ($racingdata[$date][$venueCode] as $raceno => $racedetail) {
            // Check if $raceno is numeric and $racedetail is an array
            if (is_numeric($raceno) && is_array($racedetail)) {
                // Filter to get only horses with numeric horseno
                $filteredHorses = array_filter($racedetail, function($horse) {
                    return isset($horse['horseno']) && is_numeric($horse['horseno']);
                });
    
                // Sort horses based on WINPre and then PLAPre
                usort($filteredHorses, function($a, $b) {
                    // First compare WINPre
                    if (isset($a['Pre']['WINPre']) && isset($b['Pre']['WINPre'])) {
                        $winComparison = $a['Pre']['WINPre'] <=> $b['Pre']['WINPre'];
                        if ($winComparison !== 0) {
                            return $winComparison; // Return the result if WINPre is different
                        }
                    }
                    // If WINPre is the same, compare PLAPre
                    if (isset($a['Pre']['PLAPre']) && isset($b['Pre']['PLAPre'])) {
                        return $a['Pre']['PLAPre'] <=> $b['Pre']['PLAPre'];
                    }
                    return 0; // If neither WINPre nor PLAPre are set, do not change order
                });
    
                // Create an array to hold the complete race details
                $completeRaceDetail = [];
    
                // Include sorted horses and any other fields from the original $racedetail
                foreach ($filteredHorses as $horse) {
                    $horseno = $horse['horseno'];
                    $completeRaceDetail[$horseno] = $horse; // Retain horse details
                }
    
                // Add any additional fields from the original racedetail if needed
                foreach ($racedetail as $key => $value) {
                    // Only add fields that are not already included in completeRaceDetail
                    if (!isset($completeRaceDetail[$key])) {
                        $completeRaceDetail[$key] = $value;
                    }
                }
    
                // Reassign the complete race details to the sorted array
                $racingdata_sorted[$date][$venueCode][$raceno] = $completeRaceDetail;
            }
        }
    }
    
    foreach ($racingdata_sorted[$date][$venueCode] as $raceno => $racedetail) {
        $position = 1; // Initialize a position counter
    
        foreach ($racedetail as $key => $horsedetail) {
            // Check if the entry is an array and contains the 'horseno' key
            if (is_array($horsedetail) && isset($horsedetail['horseno']) && is_numeric($horsedetail['horseno'])) {
                $horseno = $horsedetail['horseno'];
                $jockey = "pos_".$horsedetail['jockey'];
                $proppre = "proppre_".$horsedetail['Pre']['proppre'];
                $propcurr = "propcurr_".$horsedetail['Curr']['prop'];
                
                // Ensure that the jockey position is recorded only once
                if (!isset($racingdata[$date][$venueCode][$raceno][$jockey])) {
                    $racingdata[$date][$venueCode][$raceno][$jockey] = $position; // Store jockey position
                }
                
                // Ensure that the proppre key is initialized as an array if it doesn't exist
                if (!isset($racingdata[$date][$venueCode][$raceno][$proppre])) {
                    $racingdata[$date][$venueCode][$raceno][$proppre] = []; // Initialize as an array
                    $racingdata[$date][$venueCode][$raceno][$propcurr] = []; // Initialize as an array
                    $racingdata[$date][$venueCode][$raceno][$horseno]['Pre'][$proppre] = [];
                    $racingdata[$date][$venueCode][$raceno][$horseno]['Curr'][$proppre] = [];
                }
                $racingdata[$date][$venueCode][$raceno][$proppre][] = $position; // Add current position to proppre array
                // $racingdata[$date][$venueCode][$raceno][$horseno]['Pre'][$proppre][] = $position; // Add current position to proppre array
                $racingdata[$date][$venueCode][$raceno][$horseno]['Pre']['propprepos'] = $position;
                
                // $racingdata[$date][$venueCode][$raceno][$horseno]['propprecnt'] = count($racingdata[$date][$venueCode][$raceno][$horseno]['Pre'][$proppre]);
                
                $position++; // Increment position for the next horse
            }
        }
        foreach ($racedetail as $key => $horsedetail) { //store into up one level //put postition array
            if (is_array($horsedetail) && isset($horsedetail['horseno']) && is_numeric($horsedetail['horseno'])) {
                $horseno = $horsedetail['horseno'];
                $jockey = "pos_".$horsedetail['jockey'];
                $proppre = "proppre_".$horsedetail['Pre']['proppre'];
                $propcurr = "propcurr_".$horsedetail['Curr']['prop'];
                
                $racingdata[$date][$venueCode][$raceno][$horseno]['Pre'][$proppre] = $racingdata[$date][$venueCode][$raceno][$proppre];
                $racingdata[$date][$venueCode][$raceno][$horseno]['Pre']['cnt'] = count($racingdata[$date][$venueCode][$raceno][$horseno]['Pre'][$proppre]);
                $racingdata[$date][$venueCode][$raceno][$horseno]['Curr'][$propcurr][] = $racingdata[$date][$venueCode][$raceno][$propcurr];
                $racingdata[$date][$venueCode][$raceno][$horseno]['Curr']['cnt'] = count($racingdata[$date][$venueCode][$raceno][$horseno]['Curr'][$propcurr]);
                
                // $racingdata[$date][$venueCode][$raceno][$horseno]['proppresamecnt'] = count($racingdata[$date][$venueCode][$raceno][$proppre]);
                // Ensure $propcurr is an array before counting
                // if (isset($proppre)) {
                //     $racingdata[$date][$venueCode][$raceno][$horseno]['Pre']['propprecnt'] = count($racingdata[$date][$venueCode][$raceno][$horseno]['Pre'][$proppre]);
                // } else {
                //     $racingdata[$date][$venueCode][$raceno][$horseno]['Pre']['propprecnt'] = 0; // Default to 0 if not countable
                // }
                // // Ensure $propcurr is an array before counting
                // if (isset($propcurr)) {
                //     $racingdata[$date][$venueCode][$raceno][$horseno]['Curr']['propcurrcnt'] = count($racingdata[$date][$venueCode][$raceno][$horseno]['Curr'][$proppre]);
                // } else {
                //     $racingdata[$date][$venueCode][$raceno][$horseno]['Curr']['propcurrcnt'] = 0; // Default to 0 if not countable
                // }
            }
        }
    }


} //$allvenue

            
// $odds = $oddsMerged;
// print_r($oddsMerged);

// print_r($racingdata);

$col = ($ismm) ? 48 : 36; // Number of columns ------------------------------------------------------------------------------------------------
    if($ismm){
        echo "<hr>".$hint."<hr>";
    }
$borderright = "style=\"border-right: 2px solid #66CDFF;\"";    

// foreach ($racingdata as $rsdata) {
foreach ($allvenue as $rsdata) {
    $venue = $rsdata['venueCode'];
    $date = $rsdata['date'];

    // Check if the current key is a date and contains race details
    if (isset($racingdata[$date]) && is_array($racingdata[$date])) {
        foreach ($racingdata[$date] as $emptyKey => $racedetail) {
            foreach ($racedetail as $raceno => $race) {
                if (is_numeric($raceno) && is_array($race)){
                    // Split the date and time
                    list($date, $timefull) = explode('T', $race['postTime']);
                    list($time, $timezone) = explode('+', $timefull);
                    $country_ch = $race['country_ch'];
                    $raceClass_ch = $race['raceClass_ch'];
                    $distance = $race['distance'];
                    $dist = intval($distance/100);
                    $go_ch = $race['go_ch'];
                    $raceName_ch = $race['raceName_ch'];
                    $raceTrack = $race['raceTrack'];
                    $raceCourse = $race['raceCourse'];
                
                    // Race header
                    $pageContent .= $venue."..Race: ".$raceno."<br>".$date."..".$time."..".$country_ch."..".$raceClass_ch."..".$distance."m..".$go_ch."..".$raceName_ch."..".$raceTrack."..".$raceCourse;
                    //-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                    $pageContent .= "<table class='race-table' border=1>";
                    $pageContent .= "<thead><tr>";
                			for($i = 1; $i<$col; $i++ ){
                			    switch ($i) {
                                    case 10:
                                        $disp = "J";
                                        break;
                                    case 11:
                                        $disp = "D";
                                        break;
                                    case 12:
                                        $disp = "C";
                                        break;
                                    case 13:
                                        $disp = "jt";
                                        break;
                                    case 14:
                                        $disp = "JT";
                                        break;
                                    default:
                                        $disp = "";
                                        break;
                                }
                				$pageContent .= "<th class='order' style='text-align: center; vertical-align: middle;'>$disp</th>";
                			}
                	$pageContent .= "</tr>";
                	$pageContent .= "</thead>";
                	//-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                    // Filter to get only horses with numeric horseno
                    $filteredHorses = array_filter($race, function($horse) {
                        return isset($horse['horseno']) && is_numeric($horse['horseno']);
                    });
        
                    // Sort horses based on WINPre and then PLAPre while maintaining original keys
                    uasort($filteredHorses, function($a, $b) {
                        // Compare WINPre first
                        $winComparison = ($a['Pre']['WINPre'] ?? PHP_INT_MAX) <=> ($b['Pre']['WINPre'] ?? PHP_INT_MAX);
                        if ($winComparison !== 0) {
                            return $winComparison; // Return result if WINPre differs
                        }
                        // If WINPre is the same, compare PLAPre
                        return ($a['Pre']['PLAPre'] ?? PHP_INT_MAX) <=> ($b['Pre']['PLAPre'] ?? PHP_INT_MAX);
                    });
                	
                	//-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                    // Iterate over the horses in the race
                    // foreach ($race as $horseno => $horsedetail) {
                    foreach ($filteredHorses as $horseno => $horsedetail) {
                        // Check if $horsedetail is an array and has the required keys
                        if (is_numeric($horseno) && is_array($horsedetail) && isset($horsedetail['name_ch'])) {
                            $horsename = $horsedetail['name_ch']; 
                            $code = $horsedetail['code']; 
                            $barrierDrawNumber = $horsedetail['barrierDrawNumber']; 
                            $handicapWeight = $horsedetail['handicapWeight']; 
                            $currentWeight = $horsedetail['currentWeight']; 
                            $gearInfo = $horsedetail['gearInfo']; 
                            $trainerPreference = $horsedetail['trainerPreference']; 
                            $last6run = $horsedetail['last6run']; 
                            $trumpCard = $horsedetail['trumpCard']; 
                            $finalPosition = $horsedetail['finalPosition']; 
                            $ispla = ($finalPosition && $finalPosition <= 4) ? "<b>" : "<font color=lightgrey>"; 
                            $winOdds = $horsedetail['winOdds']; 
                            $jockey = $horsedetail['jockey']; 
                            $trainer = $horsedetail['trainer'];
                            $jockey_trainer = $horsedetail['jockey'].$horsedetail['trainer'];
                            //-----------------------------------------------------------------------------------------------------------
                            $last_jocky = $horseinfo[$horsename]['jockey'];
                            $last_trainer = $horseinfo[$horsename]['trainer'];
                            $last_dist = $horseinfo[$horsename]['dist'];
                            $last_Win_Odds = $horseinfo[$horsename]['Win_Odds'];
                            $last_pla = $horseinfo[$horsename]['pla'];
                            $last_detail =  (isset($jockey) || isset($last_jocky) || isset($trainer) || isset($last_trainer) || $last_Win_Odds || $last_pla) ? (
                                            ($jockey==$last_jocky ? "" : "<font color=red>j</font>").
                                            ($trainer==$last_trainer ? "" : "<font color=red>t</font>").
                                            $last_Win_Odds.($last_Win_Odds ? "(" : "").
                                            ($last_pla>9 ? "-" : mapColorsHtml($last_pla)) ) : "/";
                                            
                            $Running_Position = $horseinfo[$horsename]['inpla']['Running_Position'][$dist];
                            //-----------------------------------------------------------------------------------------------------------
                            $Pre_WINPre = $oddsMerged[$venue][$raceno][$horseno]['Pre']['WINPre'];
                            $Pre_PLAPre = $oddsMerged[$venue][$raceno][$horseno]['Pre']['PLAPre'];
                            $Pre_proppre = $oddsMerged[$venue][$raceno][$horseno]['Pre']['proppre'];
                            $Curr_WIN = $oddsMerged[$venue][$raceno][$horseno]['Curr']['WIN'];
                            $Curr_PLA = $oddsMerged[$venue][$raceno][$horseno]['Curr']['PLA'];
                            $Curr_prop = $oddsMerged[$venue][$raceno][$horseno]['Curr']['prop'];
                            $Curr_oddsDropValue = number_format($oddsMerged[$venue][$raceno][$horseno]['Curr']['oddsDropValue'],0);
                            $TRITop = $oddsMerged[$venue][$raceno]['TRITop']['countArray'][$horseno];
                            $TCETop = $oddsMerged[$venue][$raceno]['TCETop']['countArray'][$horseno];
                            $FFTop = $oddsMerged[$venue][$raceno]['FFTop']['countArray'][$horseno];
                            $QTTTop = $oddsMerged[$venue][$raceno]['QTTTop']['countArray'][$horseno];
                            
                            $proppre_same = ($horsedetail['Pre']['cnt'] > 1) ? " style='background-color: yellow;' " : "";
                            $propcurr_same = ($horsedetail['Curr'][$Curr_prop]['cnt'] > 1) ? " style='background-color: yellow;' " : "";
                            $ismax_proppre = ($Pre_proppre == $racingdata[$date][$venue][$raceno]['prop']['maxproppre']) ? " style='color: red;' " : "";
                            $ismax_propcurr = ($Curr_prop == $racingdata[$date][$venue][$raceno]['prop']['maxpropcurr']) ? " style='color: red;' " : "";
                            $ismax2x3xpre = ($Pre_proppre == $racingdata[$date][$venue][$raceno]['prop']['max2xpre'] || $Pre_proppre == $racingdata[$date][$venue][$raceno]['prop']['max3xpre']) ? " style='font-weight:bold;' " : "";
                            $ismax2x3xcurr = ($Curr_prop == $racingdata[$date][$venue][$raceno]['prop']['max2xcurr'] || $Curr_prop == $racingdata[$date][$venue][$raceno]['prop']['max3xcurr']) ? " style='font-weight:bold;' " : "";
                            
                            $istopTRITop = !empty($oddsMerged[$venue][$raceno]['TRITop']['top']) && in_array($horseno, $oddsMerged[$venue][$raceno]['TRITop']['top']) ? " style='font-weight:bold;color:#FF33FF;' " : " style=\"opacity: 0.5;\" ";
                            $istopTCETop = !empty($oddsMerged[$venue][$raceno]['TCETop']['top']) && in_array($horseno, $oddsMerged[$venue][$raceno]['TCETop']['top']) ? " style='font-weight:bold;color:#FF33FF;' " : " style=\"opacity: 0.5;\" ";
                            $istopFFTop  = !empty($oddsMerged[$venue][$raceno]['FFTop']['top']) && in_array($horseno, $oddsMerged[$venue][$raceno]['FFTop']['top']) ? " style='font-weight:bold;color:#FF33FF;' " : " style=\"opacity: 0.5;\" ";
                            $istopQTTTop = !empty($oddsMerged[$venue][$raceno]['QTTTop']['top']) && in_array($horseno, $oddsMerged[$venue][$raceno]['QTTTop']['top']) ? " style='font-weight:bold;color:#FF33FF;' " : " style=\"opacity: 0.5;\" ";
                            
                            //-----------------------------------------------------------------------------------------------------------
                            // $jockey_inpla = in_array($jockey, $horseinfo[$horsename]['inpla']['jockey']) ? "bgcolor=#99FF99" : "";
                            $jockey_alert = (isset($horseinfo[$horsename]['inpla']['jockey']) && is_array($horseinfo[$horsename]['inpla']['jockey']) && in_array($jockey, $horseinfo[$horsename]['inpla']['jockey'])) 
                                ? "bgcolor=#99FF99" 
                                : " style=\"opacity: 0.5;\" ";
                            $jockey_inpla = $horseinfo[$horsename]['inpla']['cnt_jockey'][$jockey];
                            $jockey_notinpla = $horseinfo[$horsename]['notinpla']['cnt_jockey'][$jockey];
                            //-------------
                            $distanceArray = isset($horseinfo[$horsename]['inpla']['dist']) && is_array($horseinfo[$horsename]['inpla']['dist']) 
                                ? $horseinfo[$horsename]['inpla']['dist'] 
                                : [];
                            
                            $distValue = intval($distance / 100); // Convert distance to the required value
                            $dist_alert = in_array($distValue, $distanceArray) ? "bgcolor=#99FF99" : " style=\"opacity: 0.5;\" ";
                            $dist_inpla = $horseinfo[$horsename]['inpla']['cnt_dist'][$dist];
                            $dist_notinpla = $horseinfo[$horsename]['notinpla']['cnt_dist'][$dist];
                            //-------------
                            $trackCourseArray = isset($horseinfo[$horsename]['inpla']['RC_Track_Course']) && is_array($horseinfo[$horsename]['inpla']['RC_Track_Course']) 
                                ? $horseinfo[$horsename]['inpla']['RC_Track_Course'] 
                                : [];
                            $Course_alert = in_array($raceCourse, $trackCourseArray) ? "bgcolor=#99FF99" : " style=\"opacity: 0.5;\" ";
                            $Course_inpla = $horseinfo[$horsename]['inpla']['cnt_RC_Track_Course'][$raceCourse];
                            $Course_notinpla = $horseinfo[$horsename]['notinpla']['cnt_RC_Track_Course'][$raceCourse];
                            //-------------
                            // $jtArray = isset($horseinfo[$horsename]['inpla']['jt']) && is_array($horseinfo[$horsename]['inpla']['jt']) 
                            //     ? $horseinfo[$horsename]['inpla']['jt'] 
                            //     : [];
                            
                            // $jt_alert = isset($jtArray[$jockey_trainer]) && is_array($jtArray[$jockey_trainer]) && in_array($jockey_trainer, $jtArray[$jockey_trainer]) 
                            //     ? "bgcolor=#99FF99" 
                            //     : " style=\"opacity: 0.5;\" ";
                            $jt_inpla = $horseinfo[$horsename]['inpla']['cnt_jt'][$jockey_trainer];
                            $jt_notinpla = $horseinfo[$horsename]['notinpla']['cnt_jt'][$jockey_trainer];
                            // $JT_cnt = $horseinfo['inpla']['jt'][$jockey_trainer]."/".$horseinfo['notinpla']['jt'][$jockey_trainer];
                            // Initialize variables
                            $jtinplaCount = isset($horseinfo[$horsename]['inpla']['jt'][$jockey_trainer]) ? $horseinfo[$horsename]['inpla']['jt'][$jockey_trainer] : 0;
                            $jtnotInplaCount = isset($horseinfo[$horsename]['notinpla']['jt'][$jockey_trainer]) ? $horseinfo[$horsename]['notinpla']['jt'][$jockey_trainer] : 0;
                            // Calculate total count
                            $jttotalCount = $jtinplaCount + $jtnotInplaCount;
                            
                            // Initialize the result variable
                            $jt_alert = " style=\"opacity: 0.5;\" "; // Default to 3
                            
                            // Check if the total count is greater than 0 to avoid division by zero
                            if ($jttotalCount > 0) {
                                $jtpercentage = $jtinplaCount / $jttotalCount; // Calculate the percentage
                            
                                // Determine the result based on the percentage
                                if ($jtpercentage > 0.75) {
                                    $jt_alert = " style='background-color: #99FF99;' "; // More than 75%
                                } elseif ($jtpercentage > 0.40) {
                                    $jt_alert = " style='background-color: ##CDFFCD;' "; // More than 50%
                                }
                            }
                            //-------------
                            $JT_cnt = $horseinfo['inpla']['jt'][$jockey_trainer]."/".$horseinfo['notinpla']['jt'][$jockey_trainer];
                            // Initialize variables
                            $inplaCount = isset($horseinfo['inpla']['jt'][$jockey_trainer]) ? $horseinfo['inpla']['jt'][$jockey_trainer] : 0;
                            $notInplaCount = isset($horseinfo['notinpla']['jt'][$jockey_trainer]) ? $horseinfo['notinpla']['jt'][$jockey_trainer] : 0;
                            // Calculate total count
                            $totalCount = $inplaCount + $notInplaCount;
                            
                            // Initialize the result variable
                            $JT_cnt_alert = " style=\"opacity: 0.5;\" "; // Default to 3
                            
                            // Check if the total count is greater than 0 to avoid division by zero
                            if ($totalCount > 0) {
                                $percentage = $inplaCount / $totalCount; // Calculate the percentage
                            
                                // Determine the result based on the percentage
                                if ($percentage > 0.75) {
                                    $JT_cnt_alert = " style='background-color: #99FF99;' "; // More than 75%
                                } elseif ($percentage > 0.50) {
                                    $JT_cnt_alert = " style='background-color: ##CDFFCD;' "; // More than 50%
                                }
                            }
                            //-----------------------------------------------------------------------------------------------------------
                            $oddsDropdate = (isset($oddsdrop[$code]['date']) && $oddsdrop[$code]['date'] >= $onemonth) 
                                ? ($oddsdrop[$code]['date'] >= $oneweek ? " style='background-color: #FFCDFF;' " : " style='background-color: #CDFFFF;' ") : " style=\"opacity: 0.5;\" ";
                            //-----------------------------------------------------------------------------------------------------------

                            
                            $pageContent .= "<tr>";
                            $pageContent .= "<td $borderright>";
                            $pageContent .= "<td $proppre_same $ismax_proppre $ismax2x3xpre>".$Pre_proppre;
                            $pageContent .= "<td $propcurr_same $ismax_propcurr $borderright $ismax2x3xcurr>".$Curr_prop;
                            $pageContent .= "<td style='text-align: right;'>".$Pre_WINPre;
                            $pageContent .= "<td style='text-align: right;'>".$Curr_WIN;
                            $pageContent .= "<td>".$Curr_PLA;
                            $pageContent .= "<td $borderright>".$Pre_PLAPre;
                            //-----------
                            $pageContent .= "<td>".$last_detail;
                            $pageContent .= "<td $oddsDropdate>".$oddsdrop[$code]['values'];
                            $pageContent .= ($Curr_oddsDropValue ? "_<b>".$Curr_oddsDropValue."</b>" : "");
                            $pageContent .= "<td $jockey_alert>".($jockey_inpla."/".$jockey_notinpla);
                            $pageContent .= "<td $dist_alert>".($dist_inpla."/".$dist_notinpla);
                            $pageContent .= "<td $Course_alert>".($Course_inpla."/".$Course_notinpla);
                            $pageContent .= "<td $jt_alert>".($jt_inpla."/".$jt_notinpla);
                            $pageContent .= "<td $JT_cnt_alert>".$JT_cnt;
                            $pageContent .= "<td>".$Running_Position;
                            //-----------
                            $pageContent .= "<td $istopTRITop>".$TRITop;
                            $pageContent .= "<td $istopTCETop>".$TCETop;
                            $pageContent .= "<td $istopFFTop>".$FFTop;
                            $pageContent .= "<td $istopQTTTop>".$QTTTop;
                            //-----------
                            $pageContent .= "<td>".convertToCircledNumber($horseno);
                            $pageContent .= "<td>$ispla".($finalPosition ? $finalPosition : "");
                            $pageContent .= "<td $pick1><input type=checkbox></td>";
                            $pageContent .= "<td $pick2><input type=checkbox $ishotFavourite></td>";
                            $pageContent .= "<td $pick3><input type=checkbox $istrumpCard>".$Rtgtrend[$horsecode]."</td>";
                            $pageContent .= "<td nowrap>".$horsename;
                            $pageContent .= "<td nowrap>".$jockey;
                            $pageContent .= "<td nowrap>".$trainer;
                            
                            $tmp_PLAPre = $odds[$venue][$raceno][$horseno]['Pre']['PLAPre'];
                                 
                        }
                    }
                    
                    $pageContent .= "</tbody>";
                    $pageContent .= "</table>";
                }
            }
        }
    }//--------------------------------------------------------------------------------------------------------------
}
    
echo $pageContent;

if($_GET['mail'] ){
    // $emailtitle = "ai2";
	$ipAddress = substr($_SERVER['SERVER_ADDR'],-3);
	$Subject = "[horsepaper] -".$emailtitle." [AI6] ".$ipAddress;	//$date;
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
            /*text-wrap: nowrap;*/
            white-space: nowrap; /* Use this to prevent text wrapping */
        }
        th {
            background-color: #f2f2f2;
            text-align: center;
            vertical-align: middle; /* Center vertically */
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