<?php
// error_reporting(E_ERROR | E_PARSE);
// error_reporting(E_ALL);
// error_reporting(E_ALL & ~E_NOTICE);

include_once ("lib/func_aii.php");
include_once ("lib/func_basedata.php");
include_once ("lib/func_mailer_gmail.php");
include_once ("lib/constants.php");
include_once ("load_horseinfo.php");
include_once ("load_oddsdrop.php");
include_once ("load_prophist.php");
// include_once ("load_hkracingv2score.php");
include_once ("load_new.php");

require_once 'PropAnalyzer.php';
require_once 'PropPrediction.php';

$db = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['pass']
    );
            
$analyzer = new PropAnalyzer($db);
$predictor = new PropPrediction();
$venuePredictors = PropPrediction::createPerVenue($db);

// Train on ALL data (once — subsequent calls skip)
// if (!$predictor->isTrained()) {
//     $races = $analyzer->fetchRaces();
//     // $result = $analyzer->analyzeBatch($races);
//     // $predictor->train($result['features']);
//     $predictor->trainAll($db);
// }

$VenueCodeVN = [
    "ST" => "沙田",
    "HV" => "跑馬"
];

$oneyear = date("Y-9-1",strtotime("-1 year"));
$onemonth = date("Y-m-d",strtotime("-1 month"));
$oneweek = date("Y-m-d",strtotime("-1 week"));
$today = date("Y-m-d");
$ismm = (isset($_GET['mm']) && $_GET['mm']) ? 1 : 0;
$istext = (isset($_GET['text']) && $_GET['text']) ? 1 : 0;
$date = null; // Initialize $date
$venue = null; // Initialize $venue
$resultOddsType = null; // Initialize $resultOddsType


//------------------------------------------------------------------------------------------------------------------------------
$hint  = "<table>";
$hint .= "<tr><td>..<td>ST<td>HV<td>AU<td>JP";
$hint .= "<tr><td>31<td>o<td>x<td><td>";
$hint .= "<tr><td>19<td>x<td>b3<td><td>";
$hint .= "<tr><td>18<td>x<td>b2<td><td>";
$hint .= "<tr><td>3.2x<td>x<td>uW2ndP<td>2nd<td>";
$hint .= "<tr><td>L3w33<td>x<td>w3(L)<td><td>";
$hint .= "<tr><td>b3x<td><td>d&u<td><td>";
$hint .= "<tr><td>b3x inside 2x<td><td><td><td>3x";
$hint .= "<tr><td>b3.2x inside 2x<td><td><td><td>d9x";
$hint .= "<tr><td>3.1x<td><td><td><td>sd1x";
$hint .= "<tr><td>2.3x<td><td>outer2<td><td>";
// $hint = "if top=small3x then "."<br>";
$hint .= "</table>";
$hint .= "HV: if s4x=3,m4x; 3x under w4 both no, b3x above 2x pick, if B2x then pick upper any2x; b2x Top pick; middle 4 pick anybelow2;"."<br>";
$hint .= "HV: only 2.3x pick s3xd b3xu;"."<br>";
//------------------------------------------------------------------------------------------------------------------------------

$basedata = basedata($date,$venue,$resultOddsType);
$activeMeetings = $basedata['data']['activeMeetings'];
$raceMeetings = $basedata['data']['raceMeetings'];
// print_r($raceMeetings);

$racingdata = [];
$hkracing_v2_score = [];
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
    
    // echo $racingdatadate.$racingdatavenue."<br>";
    if (!empty($racingdatadate) && in_array($racingdatavenue, ['ST', 'HV'])) {
        $scores = load_hkracing_v2_score($racingdatadate, $racingdatavenue);
        // echo $racingdatadate.$racingdatavenue;
        
        if (!empty($scores)) {
            $hkracing_v2_score = $scores;
            // print_r($hkracing_v2_score);
        }
    }
    
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
$horseinfo = load_horseinfo($BrandNoarray,$horsenamearray,$racingdatadate);
$oddsdrop = load_oddsdrop($today,$BrandNoarray);
// print_r($BrandNoarray);
// print_r($horsenamearray);
// echo $racingdatadate."<br><br><br><br><br><br>";
// print_r($horseinfo);

// die();

// echo "<br><br><br><br>";
// print_r($basedata);
// echo "<br><br><br><br>";
// print_r($activeMeetings);
// echo "<br><br><br><br>";
// print_r($raceMeetings);

$oddsMerged = [];
$prophistcurr1st = []; // Ensure $prop is an array before use
$pickcount = [];
$pickHV = [];
// foreach ($racingdata as $rsdata) {
foreach ($allvenue as $rsdata) {    
    $odds = [];
    $venueCode = $rsdata['venueCode'];
    $date = $rsdata['date'];
    // Initialize $oddsarray and $raceNo with default values
    if (!isset($oddsarray)) {
        $oddsarray = []; // Assign as an empty array or a suitable default value
    }
    
    if (!isset($raceNo)) {
        $raceNo = 0; // Assign a default value, such as 0 or another appropriate value
    }

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
                // $oddsMerged[$venue][$raceno][$horseno]['Pre'] = $data['Pre']; 
                // Ensure that 'Pre' key exists in the data array before assignment
                if (array_key_exists('Pre', $data)) {
                    $oddsMerged[$venue][$raceno][$horseno]['Pre'] = $data['Pre'];
                } else {
                    // Handle the case where the 'Pre' key is not found
                    error_log("Warning: 'Pre' key not found for venue '$venue', race number '$raceno', horse number '$horseno'.");
                    $oddsMerged[$venue][$raceno][$horseno]['Pre'] = null; // or set a default value as needed
                }
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
                if($oddsType=="DBL2"){
                    foreach ($oddsValues as $horseno1 => $nextracehorse) {
                        $oddsValue = $nextracehorse['oddsValue'];
                        $oddsDropValue = $nextracehorse['oddsDropValue'];
                        if($oddsDropValue){
                            $oddsMerged[$venue][$raceno]['1st'][] = $horseno1;
                            $oddsMerged[$venue][$raceno]['2nd'][] = $nextracehorse;
                        }
                    }
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
                        // $oddsMerged[$venue][$race][$horseno]['Pre']['preprop'] = $preprop; 
                        // Ensure that 'Pre' key is initialized in the oddsMerged array
                        if (!isset($oddsMerged[$venue][$race][$horseno]['Pre'])) {
                            $oddsMerged[$venue][$race][$horseno]['Pre'] = []; // Initialize as an empty array
                        }
                        
                        // // Now safely assign the preprop value
                        // $oddsMerged[$venue][$race][$horseno]['Pre']['preprop'] = $preprop; // Assign value
                        // $oddsMerged[$venue][$race]['maxpreprop'] = ($oddsMerged[$venue][$race]['maxpreprop']>$preprop) ? $oddsMerged[$venue][$race]['maxpreprop'] : $preprop;
                        
                        // $preprop1 = number_format(100 * (1/$plapre + 1/$winpre), 0);
                        // $oddsMerged[$venue][$race][$horseno]['Pre']['preprop1'] = $preprop1;
                        // $oddsMerged[$venue][$race]['maxpreprop1'] = ($oddsMerged[$venue][$race]['maxpreprop1']>$preprop1) ? $oddsMerged[$venue][$race]['maxpreprop1'] : $preprop1;
                        // Ensure that 'Pre' key is initialized in the oddsMerged array
                        if (!isset($oddsMerged[$venue][$race][$horseno]['Pre'])) {
                            $oddsMerged[$venue][$race][$horseno]['Pre'] = []; // Initialize as an empty array
                        }
                        
                        // Now safely assign the preprop value
                        $oddsMerged[$venue][$race][$horseno]['Pre']['preprop'] = $preprop; // Assign value
                        
                        // Initialize maxpreprop if not already set
                        if (!isset($oddsMerged[$venue][$race]['maxpreprop'])) {
                            $oddsMerged[$venue][$race]['maxpreprop'] = 0; // Set to a default value (0)
                        }
                        $oddsMerged[$venue][$race]['maxpreprop'] = max($oddsMerged[$venue][$race]['maxpreprop'], $preprop);
                        
                        // Calculate and assign preprop1 value
                        $preprop1 = number_format(100 * (1 / $plapre + 1 / $winpre), 0);
                        $oddsMerged[$venue][$race][$horseno]['Pre']['preprop1'] = $preprop1;
                        
                        // Initialize maxpreprop1 if not already set
                        if (!isset($oddsMerged[$venue][$race]['maxpreprop1'])) {
                            $oddsMerged[$venue][$race]['maxpreprop1'] = 0; // Set to a default value (0)
                        }
                        $oddsMerged[$venue][$race]['maxpreprop1'] = max($oddsMerged[$venue][$race]['maxpreprop1'], $preprop1);
                        
                        
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
                    // $proppre = $oddsMerged[$venue][$raceno][$horseno]['Pre']['proppre'];
                    // Ensure that the necessary keys exist in the $oddsMerged array
                    if (isset($oddsMerged[$venue]) && 
                        isset($oddsMerged[$venue][$raceno]) && 
                        isset($oddsMerged[$venue][$raceno][$horseno]) && 
                        isset($oddsMerged[$venue][$raceno][$horseno]['Pre']) && 
                        isset($oddsMerged[$venue][$raceno][$horseno]['Pre']['proppre'])) {
                        
                        // Access the proppre if everything is set
                        $proppre = $oddsMerged[$venue][$raceno][$horseno]['Pre']['proppre'];
                    } else {
                        // Handle the case where the keys don't exist
                        $proppre = null; // Or set to a default value as needed
                        // error_log("Warning: Missing keys in oddsMerged array for venue: $venue, race number: $raceno, horse number: $horseno.");
                    }
                    
                    //  $racingdata[$date][$venueCode][$raceno][$horseno]['Pre']['WINPre'] = $oddsMerged[$venue][$raceno][$horseno]['Pre']['WINPre'];
                    //  $racingdata[$date][$venueCode][$raceno][$horseno]['Pre']['PLAPre'] = $oddsMerged[$venue][$raceno][$horseno]['Pre']['PLAPre'];
                    //  $racingdata[$date][$venueCode][$raceno][$horseno]['Pre']['proppre'] = $proppre;
                    //  $racingdata[$date][$venueCode][$raceno][$horseno]['Curr']['WIN'] = $oddsMerged[$venue][$raceno][$horseno]['Curr']['WIN'];
                    //  $racingdata[$date][$venueCode][$raceno][$horseno]['Curr']['PLA'] = $oddsMerged[$venue][$raceno][$horseno]['Curr']['PLA'];
                    //  $racingdata[$date][$venueCode][$raceno][$horseno]['Curr']['prop'] = $oddsMerged[$venue][$raceno][$horseno]['Curr']['prop'];
                    //  $racingdata[$date][$venueCode][$raceno][$horseno]['Curr']['oddsDropValue'] = $oddsMerged[$venue][$raceno][$horseno]['Curr']['oddsDropValue'];
                     
                    //  $racingdata[$date][$venueCode][$raceno]['prop']['maxproppre'] = $oddsMerged[$venue][$raceno]['maxpreprop'];
                    //  $racingdata[$date][$venueCode][$raceno]['prop']['maxproppre1'] = $oddsMerged[$venue][$raceno]['maxpreprop1'];
                    //  $racingdata[$date][$venueCode][$raceno]['prop']['maxpropcurr'] = $oddsMerged[$venue][$raceno]['maxcurrprop'];
                    // Ensure that the necessary keys exist in the $oddsMerged array
                    if (isset($oddsMerged[$venue]) &&
                        isset($oddsMerged[$venue][$raceno]) &&
                        isset($oddsMerged[$venue][$raceno][$horseno]) &&
                        isset($oddsMerged[$venue][$raceno][$horseno]['Pre']) &&
                        isset($oddsMerged[$venue][$raceno][$horseno]['Curr'])) {
                        
                        $racingdata[$date][$venueCode][$raceno][$horseno]['Pre']['WINPre'] = 
                            $oddsMerged[$venue][$raceno][$horseno]['Pre']['WINPre'] ?? null;
                        $racingdata[$date][$venueCode][$raceno][$horseno]['Pre']['PLAPre'] = 
                            $oddsMerged[$venue][$raceno][$horseno]['Pre']['PLAPre'] ?? null;
                        $racingdata[$date][$venueCode][$raceno][$horseno]['Pre']['proppre'] = $proppre;
                    
                        $racingdata[$date][$venueCode][$raceno][$horseno]['Curr']['WIN'] = 
                            $oddsMerged[$venue][$raceno][$horseno]['Curr']['WIN'] ?? null;
                        $racingdata[$date][$venueCode][$raceno][$horseno]['Curr']['PLA'] = 
                            $oddsMerged[$venue][$raceno][$horseno]['Curr']['PLA'] ?? null;
                        $racingdata[$date][$venueCode][$raceno][$horseno]['Curr']['prop'] = 
                            $oddsMerged[$venue][$raceno][$horseno]['Curr']['prop'] ?? null;
                        $racingdata[$date][$venueCode][$raceno][$horseno]['Curr']['oddsDropValue'] = 
                            $oddsMerged[$venue][$raceno][$horseno]['Curr']['oddsDropValue'] ?? null;
                    
                        // For the 'prop' array
                        $racingdata[$date][$venueCode][$raceno]['prop']['maxproppre'] = 
                            $oddsMerged[$venue][$raceno]['maxpreprop'] ?? null;
                        $racingdata[$date][$venueCode][$raceno]['prop']['maxproppre1'] = 
                            $oddsMerged[$venue][$raceno]['maxpreprop1'] ?? null;
                        $racingdata[$date][$venueCode][$raceno]['prop']['maxpropcurr'] = 
                            $oddsMerged[$venue][$raceno]['maxcurrprop'] ?? null;
                    
                    } else {
                        // Handle the case where the necessary keys don't exist
                        // error_log("Warning: Missing keys in oddsMerged array for venue: $venue, race number: $raceno, horse number: $horseno.");
                    }
                     
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
                        $propprefirstdigit = getFirstDigit($proppre);
                        
                        $racingdata[$date][$venueCode][$raceno]['propprearray'][] = $proppre;
                        $racingdata[$date][$venueCode][$raceno]['WINPrearray'][] = $oddsMerged[$venueCode][$raceno][$horseno]['Pre']['WINPre'];
                        
                        $racingdata[$date][$venueCode][$raceno]['propprecnt'][$propprefirstdigit."_".$proppre]++;
                        if($racingdata[$date][$venueCode][$raceno]['propprecnt'][$propprefirstdigit."_".$proppre] > 1){
                            $racingdata[$date][$venueCode][$raceno]['proppremorethanone'][] = $proppre;
                            $racingdata[$date][$venueCode][$raceno]['proppremorethanone_init'][] = $propprefirstdigit;
                        };
                        // if($racingdata[$date][$venueCode][$raceno]['propprecnt'][$propprefirstdigit."_".$proppre] == 1){
                        //     $racingdata[$date][$venueCode][$raceno]['proppreonlyone'][] = $proppre;
                        //     $racingdata[$date][$venueCode][$raceno]['proppreonlyone_init'][] = $propprefirstdigit;
                        // };
                        if ($racingdata[$date][$venueCode][$raceno]['propprecnt'][$propprefirstdigit . "_" . $proppre] == 1) {
                            // If equal to 1, add to the 'proppreonlyone' and 'proppreonlyone_init' arrays
                            $racingdata[$date][$venueCode][$raceno]['proppreonlyone'][] = $proppre;
                            $racingdata[$date][$venueCode][$raceno]['proppreonlyone_init'][] = $propprefirstdigit;
                        } elseif ($racingdata[$date][$venueCode][$raceno]['propprecnt'][$propprefirstdigit . "_" . $proppre] > 1) {
                            // If greater than 1, remove the last added $proppre and $propprefirstdigit
                            $index = array_search($proppre, $racingdata[$date][$venueCode][$raceno]['proppreonlyone']);
                            if ($index !== false) {
                                unset($racingdata[$date][$venueCode][$raceno]['proppreonlyone'][$index]);
                            }
                        
                            $indexInit = array_search($propprefirstdigit, $racingdata[$date][$venueCode][$raceno]['proppreonlyone_init']);
                            if ($indexInit !== false) {
                                unset($racingdata[$date][$venueCode][$raceno]['proppreonlyone_init'][$indexInit]);
                            }
                        }
                        // Initialize the count for the proppre if not set
                        if (!isset($racingdata[$date][$venueCode][$raceno][$proppre])) {
                            $racingdata[$date][$venueCode][$raceno]['cnt_'.$proppre] = 0;
                        }
                        $racingdata[$date][$venueCode][$raceno]['cnt_'.$proppre]++;
                        // echo $venueCode.$raceno.$proppre.$racingdata[$date][$venueCode][$raceno][$proppre]."--".$racingdata[$date][$venueCode][$raceno][$horseno]['Pre']['cnt']."--".$oddsMerged[$venueCode][$raceno][$horseno]['Pre']['propprepos']."<br>";
                        // Determine counts based on proppre value
                        if ($proppre >= 30 && $proppre < 40) {
                            // if($oddsMerged[$venueCode][$raceno][$horseno]['Pre']['cnt']>1){
                            //     $racingdata[$date][$venueCode][$raceno]['cnt_3x']++;
                            // }
                            $racingdata[$date][$venueCode][$raceno]['cnt_3-4']++;
                            $racingdata[$date][$venueCode][$raceno]['arr_3-4'][] = $proppre;
                        } elseif ($proppre >= 20 && $proppre < 30) {
                            // if($oddsMerged[$venueCode][$raceno][$horseno]['Pre']['cnt']>1){
                            //     $racingdata[$date][$venueCode][$raceno]['cnt_2x']++;
                            // }
                            $racingdata[$date][$venueCode][$raceno]['cnt_2-3']++;
                            $racingdata[$date][$venueCode][$raceno]['arr_2-3'][] = $proppre;
                        } elseif ($proppre >= 10 && $proppre < 20) {
                            // if($oddsMerged[$venueCode][$raceno][$horseno]['Pre']['cnt']>1){
                            //     $racingdata[$date][$venueCode][$raceno]['cnt_1x']++;
                            // }
                            $racingdata[$date][$venueCode][$raceno]['cnt_1-2']++;
                            $racingdata[$date][$venueCode][$raceno]['arr_1-2'][] = $proppre;
                        } elseif ($proppre >= 0 && $proppre < 10) {
                            // if($oddsMerged[$venueCode][$raceno][$horseno]['Pre']['cnt']>1){
                            //     $racingdata[$date][$venueCode][$raceno]['cnt_0x']++;
                            // }
                            $racingdata[$date][$venueCode][$raceno]['cnt_0-1']++;
                            $racingdata[$date][$venueCode][$raceno]['arr_0-1'][] = $proppre;
                        } elseif ($proppre >= 40 && $proppre < 50) {
                            // if($oddsMerged[$venueCode][$raceno][$horseno]['Pre']['cnt']>1){
                            //     $racingdata[$date][$venueCode][$raceno]['cnt_4x']++;
                            // }
                            $racingdata[$date][$venueCode][$raceno]['cnt_4-5']++;
                            $racingdata[$date][$venueCode][$raceno]['arr_4-5'][] = $proppre;
                        } elseif ($proppre >= 50 && $proppre < 60) {
                            // if($oddsMerged[$venueCode][$raceno][$horseno]['Pre']['cnt']>1){
                            //     $racingdata[$date][$venueCode][$raceno]['cnt_5x']++;
                            // }
                            $racingdata[$date][$venueCode][$raceno]['cnt_5-6']++;
                            $racingdata[$date][$venueCode][$raceno]['arr_5-6'][] = $proppre;
                        } elseif ($proppre >= 60 ) {
                            // if($oddsMerged[$venueCode][$raceno][$horseno]['Pre']['cnt']>1){
                            //     $racingdata[$date][$venueCode][$raceno]['cnt_xx']++;
                            // }
                            $racingdata[$date][$venueCode][$raceno]['cnt_6-x']++;
                            $racingdata[$date][$venueCode][$raceno]['arr_6x'][] = $proppre;
                        }
                    } else {
                        // Initialize count fields to zero if needed
                        // if (!isset($racingdata[$date][$venueCode][$raceno]['cnt_1x'])) {
                            $racingdata[$date][$venueCode][$raceno]['cnt_xx'] = 0;
                            $racingdata[$date][$venueCode][$raceno]['cnt_5x'] = 0;
                            $racingdata[$date][$venueCode][$raceno]['cnt_4x'] = 0;
                            $racingdata[$date][$venueCode][$raceno]['cnt_3x'] = 0;
                            $racingdata[$date][$venueCode][$raceno]['cnt_2x'] = 0;
                            $racingdata[$date][$venueCode][$raceno]['cnt_1x'] = 0;
                            
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
                // $proppre = "proppre_".$horsedetail['Pre']['proppre'];
                // $propcurr = "propcurr_".$horsedetail['Curr']['prop'];
                // Initialize proppre and propcurr with default values
                $proppre = '';
                $propcurr = '';
                $proppreproppos = '';
                $propcurrproppos = '';
                
                // Ensure 'Pre' exists and has 'proppre'
                if (isset($horsedetail['Pre']) && isset($horsedetail['Pre']['proppre'])) {
                    $proppre = "proppre_" . $horsedetail['Pre']['proppre'];
                    $proppreproppos = "proppreproppos_" . $horsedetail['Pre']['proppre'];
                } else {
                    // Handle the case where 'Pre' or 'proppre' is missing
                    // Optionally log a warning
                    // error_log("Warning: 'Pre' or 'proppre' is missing in horsedetail array.");
                }
                
                // Ensure 'Curr' exists and has 'prop'
                if (isset($horsedetail['Curr']) && isset($horsedetail['Curr']['prop'])) {
                    $propcurr = "propcurr_" . $horsedetail['Curr']['prop'];
                    $propcurrproppos = "propcurrproppos_" . $horsedetail['Curr']['prop'];
                } else {
                    // Handle the case where 'Curr' or 'prop' is missing
                    // Optionally log a warning
                    // error_log("Warning: 'Curr' or 'prop' is missing in horsedetail array.");
                }
                
                // Ensure that the jockey position is recorded only once
                if (!isset($racingdata[$date][$venueCode][$raceno][$jockey])) {
                    $racingdata[$date][$venueCode][$raceno][$jockey] = $position; // Store jockey position
                }
                
                // Ensure that the proppre key is initialized as an array if it doesn't exist
                if (!isset($racingdata[$date][$venueCode][$raceno][$proppre])) {
                    $racingdata[$date][$venueCode][$raceno][$proppre] = []; // Initialize as an array
                    $racingdata[$date][$venueCode][$raceno][$horseno]['Pre'][$proppre] = [];
                }
                if (!isset($racingdata[$date][$venueCode][$raceno][$propcurr])) {
                    $racingdata[$date][$venueCode][$raceno][$propcurr] = []; // Initialize as an array
                    $racingdata[$date][$venueCode][$raceno][$horseno]['Curr'][$propcurr] = [];
                }                
                
                $racingdata[$date][$venueCode][$raceno][$proppre][] = $position; // Add current position to proppre array
                $racingdata[$date][$venueCode][$raceno][$propcurr][] = $position; // Add current position to proppre array
                // $racingdata[$date][$venueCode][$raceno][$horseno]['Pre'][$proppre][] = $position; // Add current position to proppre array
                $racingdata[$date][$venueCode][$raceno][$horseno]['Pre']['propprepos'] = $position;
                $racingdata[$date][$venueCode][$raceno][$horseno]['Curr']['propprepos'] = $position;
                
                $propprehorsepos = "propprehorsepos_".$horseno;
                $racingdata[$date][$venueCode][$raceno][$propprehorsepos][] = $position;
                $racingdata[$date][$venueCode][$raceno][$proppreproppos][] = $position; // Add current position to proppre array
                $racingdata[$date][$venueCode][$raceno][$propcurrproppos][] = $position; // Add current position to proppre array
                $propprehorseno = "propprehorseno_".$horsedetail['Pre']['proppre'];
                $racingdata[$date][$venueCode][$raceno][$propprehorseno] = $horseno;
                
                // $racingdata[$date][$venueCode][$raceno][$horseno]['propprecnt'] = count($racingdata[$date][$venueCode][$raceno][$horseno]['Pre'][$proppre]);
                
                $position++; // Increment position for the next horse
                
                $racingdata[$date][$venueCode][$raceno]['propprearraywhorseno']['proppre'][] = $horsedetail['Pre']['proppre'];
                $racingdata[$date][$venueCode][$raceno]['propprearraywhorseno']['horseno'][] = $horseno;

            }
        }
        if($ismm){
            // print_r($racingdata);
        }
        // die(1);
        $tmp_proppre = 0;
        $tmp_prex = [];
        $cnttemp2x = 0;
        foreach ($racedetail as $key => $horsedetail) { //store into up one level //put postition array
        // print_r($racingdata);
        // die();
            if (is_array($horsedetail) && isset($horsedetail['horseno']) && is_numeric($horsedetail['horseno'])) {
                $proppre = '';
                $propcurr = '';
                $horseno = $horsedetail['horseno'];
                $jockey = "pos_".$horsedetail['jockey'];
                $Curr_WIN = $horsedetail['Curr']['WIN'] ?? null;
                $Pre_proppre = $horsedetail['Pre']['proppre'] ?? null;
                $proppreproppos = "proppreproppos_".$horsedetail['Pre']['proppre'];
                // $racingdata[$date][$venueCode][$raceno][$horseno]['Pre'][$proppre] = $racingdata[$date][$venueCode][$raceno][$proppre];
                // $racingdata[$date][$venueCode][$raceno][$horseno]['Pre']['cnt'] = count($racingdata[$date][$venueCode][$raceno][$horseno]['Pre'][$proppre]);
                // $racingdata[$date][$venueCode][$raceno][$horseno]['Curr'][$propcurr][] = $racingdata[$date][$venueCode][$raceno][$propcurr];
                // Initialize variables

                
                // Check for 'Pre' and 'proppre' in $horsedetail
                if (isset($horsedetail['Pre']) && isset($horsedetail['Pre']['proppre'])) {
                    $proppre = "proppre_" . $horsedetail['Pre']['proppre'];
                } else {
                    // error_log("Warning: 'Pre' or 'proppre' is missing in horsedetail array.");
                }
                
                // Check for 'Curr' and 'prop' in $horsedetail
                if (isset($horsedetail['Curr']) && isset($horsedetail['Curr']['prop'])) {
                    $propcurr = "propcurr_" . $horsedetail['Curr']['prop'];
                } else {
                    // error_log("Warning: 'Curr' or 'prop' is missing in horsedetail array.");
                }
                
                // Initialize the Pre array if not set
                if (!isset($racingdata[$date][$venueCode][$raceno][$horseno]['Pre'])) {
                    $racingdata[$date][$venueCode][$raceno][$horseno]['Pre'] = [];
                }
                //---------------------------------
                // Check if $proppre is valid before assignment
                if (!empty($proppre)) {
                    $racingdata[$date][$venueCode][$raceno][$horseno]['Pre'][$proppre] = $racingdata[$date][$venueCode][$raceno][$proppre] ?? null;
                    $racingdata[$date][$venueCode][$raceno][$horseno]['Pre']['cnt'] = count($racingdata[$date][$venueCode][$raceno][$horseno]['Pre'][$proppre] ?? []);
                }
                
                // Initialize the Curr array if not set
                if (!isset($racingdata[$date][$venueCode][$raceno][$horseno]['Curr'])) {
                    $racingdata[$date][$venueCode][$raceno][$horseno]['Curr'] = [];
                }
                
                // Check if $propcurr is valid before assignment
                if (!empty($propcurr)) {
                    $racingdata[$date][$venueCode][$raceno][$horseno]['Curr'][$propcurr][] = $racingdata[$date][$venueCode][$raceno][$propcurr] ?? null;
                    $racingdata[$date][$venueCode][$raceno][$horseno]['Curr']['cnt'] = count($racingdata[$date][$venueCode][$raceno][$horseno]['Curr'][$propcurr][0]);
                }
                
                //---------------------------------------------------------------------------------------------------------------------------------
                //---------------------------------------------------------------------------------------------------------------------------------
                // $ispicked = (isset($racingdata[$date][$venueCode]['Pre'][$syspick[$venue][$raceno]['pos']][1]) && $horsedetail['Pre'][$syspick[$venue][$raceno]['pos']][1] == $racingdata[$date][$venueCode]['Pre']['propprepos'] ) ? "background-color: #FFCDFF;" : "";
                // $ispicked .= (isset($syspick[$venue][$raceno]['3x']) && $horsedetail['Pre']["proppre_".$syspick[$venue][$raceno]['3x']][0] == $horsedetail['Pre']['propprepos'] ) ? "background-color: #FFCDFF;" : "";
                if(is_numeric($Curr_WIN)){
                    $FirstDigit_proppre = getFirstDigit($Pre_proppre);
                    $FirstDigit_tmp_proppre = getFirstDigit($tmp_proppre);
                    
                    if(1){  //$venueCode=="HV"
                        if($FirstDigit_proppre == 2 &&  $FirstDigit_tmp_proppre == 3
                            && $Pre_proppre > 25
                            && $tmp_proppre <= 35
                            && !in_array($Pre_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone']) 
                            && !in_array($tmp_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone']) 
                            ) {
                                $pickHV[$date][$venueCode][$raceno][$horseno]++;
                            }
                        if($FirstDigit_proppre == 3 &&  $FirstDigit_tmp_proppre == 3
                            && !in_array($Pre_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone']) 
                            && !in_array($tmp_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone']) 
                            && $racingdata[$date][$venueCode][$raceno]['prop']['max3xpre'] == $tmp_proppre ) {
                                $pickHV[$date][$venueCode][$raceno][$horseno]++;
                            }                            
                        
                    }
                    
                    if(isset($tmp_proppre) && $FirstDigit_tmp_proppre == $FirstDigit_proppre && $tmp_proppre > $Pre_proppre 
                        && $racingdata[$date][$venueCode][$raceno]['propprecnt'][$FirstDigit_tmp_proppre."_".$tmp_proppre] ==1
                        && $racingdata[$date][$venueCode][$raceno]['propprecnt'][$FirstDigit_proppre."_".$Pre_proppre] ==1
                        && $Pre_proppre <40){
                        $ispicked[$date][$venueCode][$raceno][$horseno] .= "color: red;";
                        $pickcount[$date][$venueCode][$raceno][$horseno]++;
                    }
                    if(in_array("5", $racingdata[$date][$venueCode][$raceno]['proppremorethanone_init']) && !in_array($Pre_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone'])
                        && !in_array("4", $racingdata[$date][$venueCode][$raceno]['proppremorethanone_init']) ){
                        $pickcount[$date][$venueCode][$raceno][$horseno]++;    
                    }
                    if(!in_array("4", $racingdata[$date][$venueCode][$raceno]['proppremorethanone_init'])
                        && $tmp_pre[$date][$venueCode][$raceno]['count']==2 ){
                        $pickcount[$date][$venueCode][$raceno][$horseno]++;    
                    }
                    if( $FirstDigit_tmp_proppre == 2 && $FirstDigit_proppre == 5
                        && in_array($tmp_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone']) 
                        && !in_array($Pre_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone']) ){
                        $pickcount[$date][$venueCode][$raceno][$horseno] = $pickcount[$date][$venueCode][$raceno][$horseno] ? $pickcount[$date][$venueCode][$raceno][$horseno]-- : -1;    
                    }
                    if( $FirstDigit_tmp_proppre == 2 && $FirstDigit_proppre == 3
                        && !in_array($tmp_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone']) 
                        && !in_array($Pre_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone']) ){
                        $pickcount[$date][$venueCode][$raceno][$horseno]++;    
                    }else{
                        $pickcount[$date][$venueCode][$raceno][$tmp_horseno] = $pickcount[$date][$venueCode][$raceno][$tmp_horseno] ? $pickcount[$date][$venueCode][$raceno][$tmp_horseno]-- : '';    
                    }
                    if( $FirstDigit_tmp_proppre == 5 && $racingdata[$date][$venueCode][$raceno]['prop']['max3xpre'] == $Pre_proppre
                        && !in_array($Pre_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone']) ){
                        if($Pre_proppre>=35){
                            $pickcount[$date][$venueCode][$raceno][$horseno]++;
                        }
                        $pickcount[$date][$venueCode][$raceno][$tmp_horseno]++;
                    }
                    if( $FirstDigit_tmp_proppre == 3 && $FirstDigit_proppre == 3
                        && $tmp_proppre < $Pre_proppre 
                        && $racingdata[$date][$venueCode][$raceno]['prop']['max3xpre'] == $Pre_proppre ){
                        $pickcount[$date][$venueCode][$raceno][$horseno]++;    
                        // $pickcount[$date][$venueCode][$raceno][$tmp_horseno]--;
                    }
                    if($FirstDigit_proppre == 3 &&  $FirstDigit_tmp_proppre == 4
                        && !in_array($Pre_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone']) 
                        && !in_array($tmp_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone']) 
                        && $racingdata[$date][$venueCode][$raceno]['prop']['max3xpre'] == $Pre_proppre ) {
                            $pickHV[$date][$venueCode][$raceno][$horseno]++;
                            $pickcount[$date][$venueCode][$raceno][$horseno]++;
                    }
                    if( $FirstDigit_tmp_proppre == 3 && $FirstDigit_proppre == 1 
                        && !in_array($tmp_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone']) && !in_array($Pre_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone']) ){
                        $pickcount[$date][$venueCode][$raceno][$horseno]++;    
                    }
                    if( $FirstDigit_proppre == 3
                        && in_array($Pre_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone']) 
                        && in_array("19", $racingdata[$date][$venueCode][$raceno]['propprearray']) 
                        && $racingdata[$date][$venueCode][$raceno]['proppreproppos_'.$Pre_proppre][0]==1 ){
                        $pickcount[$date][$venueCode][$raceno][$horseno]++;   
                        $pickcount[$date][$venueCode][$raceno][$horseno]++;   
                    }
                    if( $FirstDigit_tmp_proppre == 4 && $FirstDigit_proppre == 4 
                        && $racingdata[$date][$venueCode][$raceno]['prop']['maxproppre'] <> $tmp_proppre
                        && !in_array($tmp_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone']) && !in_array($Pre_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone']) ){
                        $pickcount[$date][$venueCode][$raceno][$tmp_horseno]++;    
                    }
                    if( $FirstDigit_tmp_proppre == 4 && $FirstDigit_proppre == 2 
                        && !in_array($Pre_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone'])
                        && !in_array($tmp_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone'])){
                        $pickcount[$date][$venueCode][$raceno][$horseno] = $pickcount[$date][$venueCode][$raceno][$horseno] ? $pickcount[$date][$venueCode][$raceno][$horseno]-- : -1;    
                        if($racingdata[$date][$venueCode][$raceno]['prop']['max2xpre'] == $Pre_proppre ){
                            $pickcount[$date][$venueCode][$raceno][$horseno] = $pickcount[$date][$venueCode][$raceno][$horseno] ? $pickcount[$date][$venueCode][$raceno][$horseno]-- : -1;    
                        }
                        
                    }
                    if( $FirstDigit_proppre == 2 && $Pre_proppre >= 28 
                        && in_array($Pre_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone'])  ){
                        $pickcount[$date][$venueCode][$raceno][$horseno]++;    
                    }
                    if( $racingdata[$date][$venueCode][$raceno]['prop']['maxproppre'] == $tmp_proppre && $racingdata[$date][$venueCode][$raceno]['prop']['max3xpre'] == $Pre_proppre
                        && $Pre_proppre >= 36
                        && !in_array($Pre_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone'])  ){
                        $pickcount[$date][$venueCode][$raceno][$horseno]++;    
                    }
                    if( in_array("18", $racingdata[$date][$venueCode][$raceno]['propprearray']) && in_array("19", $racingdata[$date][$venueCode][$raceno]['propprearray'])
                        && $racingdata[$date][$venueCode][$raceno]['prop']['max3xpre'] >= 36 
                        && $racingdata[$date][$venueCode][$raceno]['prop']['max3xpre'] == $Pre_proppre){
                        $propprehorseno = "propprehorseno_".$racingdata[$date][$venueCode][$raceno]['prop']['max3xpre'];
                        $pickcount[$date][$venueCode][$raceno][$racingdata[$date][$venueCode][$raceno][$propprehorseno]]++;    
                    }
                    if( in_array("18", $racingdata[$date][$venueCode][$raceno]['propprearray']) 
                        && $racingdata[$date][$venueCode][$raceno]['prop']['max2xpre'] >= 26 
                        && $racingdata[$date][$venueCode][$raceno]['prop']['max2xpre'] == $Pre_proppre){
                        $propprehorseno = "propprehorseno_".$racingdata[$date][$venueCode][$raceno]['prop']['max2xpre'];
                        $pickcount[$date][$venueCode][$raceno][$racingdata[$date][$venueCode][$raceno][$propprehorseno]]++;    
                    }
                    if( in_array("19", $racingdata[$date][$venueCode][$raceno]['propprearray'])
                        && $racingdata[$date][$venueCode][$raceno]['prop']['max3xpre'] >= 36 
                        && $racingdata[$date][$venueCode][$raceno]['prop']['max3xpre'] == $Pre_proppre){
                        $propprehorseno = "propprehorseno_".$racingdata[$date][$venueCode][$raceno]['prop']['max3xpre'];
                        $pickcount[$date][$venueCode][$raceno][$racingdata[$date][$venueCode][$raceno][$propprehorseno]]++;    
                    }
                    if(count($racingdata[$date][$venueCode][$raceno]['arr_4-5'] ==2) 
                        && $FirstDigit_proppre == 2 
                        && !in_array($Pre_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone'])
                        && $cnttemp2x==1 ){
                        $pickcount[$date][$venueCode][$raceno][$tmp_prex[$date][$venueCode][$raceno]['horseno']]++;    
                    }
                    // echo $date.$venueCode.$raceno.$horseno."-".$tmp_proppre. $racingdata[$date][$venueCode][$raceno]['prop']['maxproppre'].$racingdata[$date][$venueCode][$raceno]['prop']['max3xpre']."<br>";
                    if($FirstDigit_tmp_proppre == 4 
                       && $tmp_proppre == $racingdata[$date][$venueCode][$raceno]['prop']['maxproppre'] 
                       && $FirstDigit_proppre == 3 
                       && $racingdata[$date][$venueCode][$raceno]['prop']['max3xpre'] == $Pre_proppre){
                        if($tmp_pre[$date][$venueCode][$raceno]['propprepos']==1){
                            $pickcount[$date][$venueCode][$raceno][$horseno]++;
                            $pickcount[$date][$venueCode][$raceno][$tmp_horseno]++;
                        }else{
                            $pickcount[$date][$venueCode][$raceno][$horseno] = $pickcount[$date][$venueCode][$raceno][$horseno] ? $pickcount[$date][$venueCode][$raceno][$horseno]-- : -1;
                            $pickcount[$date][$venueCode][$raceno][$tmp_horseno] = $pickcount[$date][$venueCode][$raceno][$tmp_horseno] ? $pickcount[$date][$venueCode][$raceno][$tmp_horseno]-- : -1;
                        }
                    }
                    if($tmp_proppre == $racingdata[$date][$venueCode][$raceno]['prop']['max2xpre'] 
                        && $FirstDigit_proppre == 1
                        && !in_array($Pre_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone']) ){
                        $pickcount[$date][$venueCode][$raceno][$horseno]++;
                        $pickcount[$date][$venueCode][$raceno][$tmp_horseno]--;// = $pickcount[$date][$venueCode][$raceno][$tmp_horseno]-10;
                    }
                    if($racingdata[$date][$venueCode][$raceno]['proppreproppos_'.$Pre_proppre][0] ==2 
                        && $racingdata[$date][$venueCode][$raceno]['proppreproppos_'.$tmp_proppre][0] == 1 && $FirstDigit_proppre == 3 
                        && in_array($Pre_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone']) ){
                        $pickcount[$date][$venueCode][$raceno][$tmp_horseno]++;
                    }
                    if($Pre_proppre == getMaxValueInRange5x($racingdata[$date][$venueCode][$raceno]['proppreonlyone']) ){
                        // print_r($racingdata[$date][$venueCode][$raceno]['proppreonlyone']);
                        $pickcount[$date][$venueCode][$raceno][$horseno]++;
                    }

                }
                //---------------------------------------------------------------------------------------------------------------------------------
                //---------------------------------------------------------------------------------------------------------------------------------
                
                $tmp_proppre = $horsedetail['Pre']['proppre'];
                $tmp_horseno = $horseno;
                $count_prex = count($racingdata[$date][$venueCode][$raceno]['proppre_'.$Pre_proppre]);
                if(!isset($tmp_prex[$date][$venueCode][$raceno]['horseno'])){
                    $tmp_pre[$date][$venueCode][$raceno] = [
                        "proppre" => $Pre_proppre,
                        "propprepos" => $proppreproppos,
                        "horseno" => $horseno,
                        "count" => count($racingdata[$date][$venueCode][$raceno]['proppre_'.$Pre_proppre])
                    ];
                    $cnttemp = $cnttemp.$FirstDigit_proppre."x" ;
                    $cnttemp++;
                }
                
                // if( $FirstDigit_proppre == 2 
                //     && !in_array($Pre_proppre, $racingdata[$date][$venueCode][$raceno]['proppremorethanone']) ) {
                //         if(!isset($tmp_2x[$date][$venueCode][$raceno]['horseno'])){
                //             $tmp_2x[$date][$venueCode][$raceno] = [
                //                 "proppre" => $Pre_proppre,
                //                 "propprepos" => $proppreproppos,
                //                 "horseno" => $horseno
                //             ];
                //         }
                //         $cnttemp2x++;
                // }
                            
            }
            // print_r($tmp_2x);
        }
    }
    $racingdata = setXCountsBasedOnPrecnt($racingdata, $date, $venueCode);


} //$allvenue

if($ismm){        
    // print_r($ispicked);
// $odds = $oddsMerged;
// print_r($oddsMerged);

// print_r($racingdata);
// exit;
}

$col = ($ismm) ? 50 : 33; // Number of columns ------------------------------------------------------------------------------------------------
$propresultcolumn = ($ismm) ? 6 : 0;
$othcol = ($ismm) ? 4 : -3;

    if($ismm){
        echo "<hr>".$hint."<hr>";
    }
$borderright = " border-right: 2px solid #66CDFF; ";    
$pageContent = "";


foreach ($racingdata as $key => $venuedetail) {
    if (isValidDate($key)) {
        $trainnerranking = [];
        $jockeyranking = [];
        $syspick = [];
        // $pickcount = [];
        foreach ($venuedetail as $venue => $racedetail) {

            foreach ($racedetail['JKC'] as $jockeydetail ) {
                if (isset($jockeydetail['name_ch'])) {
                    $jockeyname = $jockeydetail['name_ch'];
                    $jockeyranking[$jockeyname] = $jockeydetail[$jockeyname]['order'];
                }
            }
            foreach ($racedetail['TNC'] as $trainnerdetail ) {
                if (isset($trainnerdetail['name_ch'])) {
                    $trainnername = $trainnerdetail['name_ch'];
                    $trainnerranking[$trainnername] = $trainnerdetail[$trainnername]['order'];
                }
            }
            
            foreach ($racedetail as $raceno => $race) {
                if (is_numeric($raceno) && is_array($race)){
                    // print_r($race);
                    // die();
                    // print_r($race['propprearraywhorseno']);
                    // die();
                    //-----------------------------------------------------------------------------------------------------------------------------------------
                    $groupVenue = ($venue == "HV" || $venue == "ST") ? $venue : "Sx";
                    $allVenues = ['ST', 'HV', 'Sx'];
                    $predsByVenue = [];
                    $picksByVenue = [];
                    
                    foreach ($allVenues as $vkey) {
                        $predsByVenue[$vkey] = $venuePredictors[$vkey]->predictRawProps(
                            $vkey,
                            $race['propprearraywhorseno']['proppre'],
                            $race['WINPrearray']
                        );
                        $picksByVenue[$vkey] = $venuePredictors[$vkey]->finalpick3(
                            $predsByVenue[$vkey],
                            $race['propprearraywhorseno']['proppre']
                        );
                    }
                    
                    // Map each venue's preds to horseno
                    $prediction = [];
                    $prediction_ST = []; $prediction_HV = []; $prediction_Sx = [];
                    foreach ($race['propprearraywhorseno']['horseno'] as $i => $horseno) {
                        $prediction_ST[$horseno] = $predsByVenue['ST'][$i];
                        $prediction_HV[$horseno] = $predsByVenue['HV'][$i];
                        $prediction_Sx[$horseno] = $predsByVenue['Sx'][$i];
                        $prediction[$horseno] = $predsByVenue[$groupVenue][$i];
                    }
                    // $prediction = $prediction_ST; // keep original if needed elsewhere
                    // print_r($prediction_Sx );

                    //-----------------------------------------------------------------------------------------------------------------------------------------
                    
                    // print_r($prediction);
                    // die();
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
                    $wageringFieldSize = $race['wageringFieldSize'];
                    
                    $toprankscore = $hkracing_v2_score[$date][$venue][$raceno]['blog_priority_score_rank'][1]['blog_priority_score'];
                    $toprankscore .= " << ".$hkracing_v2_score[$date][$venue][$raceno]['blog_priority_score_rank'][1]['score_version_2'];
                
                    // Race header
                    $pageContent .= $venue."..Race: ".$raceno."<br>".$date."..".$time."..".$country_ch."..".$raceClass_ch."..".$distance."m..".$go_ch."..".$raceName_ch."..".$raceTrack."..".$raceCourse."       ";
                    if($ismm){
                        $pageContent .= "<br>---";
                        $pageContent .= ($toprankscore > 60) ? $toprankscore : "<font color=red><b>".$toprankscore."</b></font>";
                        $pageContent .= "---";
                    }
                    //-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                    $pageContent .= "<table class='race-table' border=1>";
                    $startcolumnno = 24 + $othcol + $propresultcolumn;
                    $pageContent .= "<thead><tr>";
                			for($i = 1; $i<$col; $i++ ){
                			    switch ($i) {
                                    case $startcolumnno:
                                        $disp = "Va";
                                        break;
                                    case $startcolumnno+1:
                                        $disp = "VxP";
                                        break;
                                    case $startcolumnno+2:
                                        $disp = "J";
                                        break;
                                    case $startcolumnno+3:
                                        $disp = "D";
                                        break;
                                    case $startcolumnno+4:
                                        $disp = "C";
                                        break;
                                    case $startcolumnno+5:
                                        $disp = "jt";
                                        break;
                                    case $startcolumnno+6:
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
                	$TopPredigit = isset($race['TopPrePos']) ? $race['TopPrePos'] : "";
                    // if($raceno==1){
                    //     $TopPredigit = 5;
                    //     $search_venue = "HV";
                    //     $cnt1x = 0;
                    //     $cnt2x = 1;
                    //     $cnt3x = 0;
                    //     $cnt4x = 0;
                    //     $cnt5x = 0;
                    //     $wageringFieldSize = 12;
                    // }
                    // if($raceno==2){
                    //     $TopPredigit = 4;
                    //     $search_venue = "HV";
                    //     $cnt1x = 0;
                    //     $cnt2x = 1;
                    //     $cnt3x = 1;
                    //     $cnt4x = 0;
                    //     $cnt5x = 0;
                    //     $wageringFieldSize = 12;
                    // }
                    if($TopPredigit){
                    //     // echo $TopPredigit;
                        $cnt1x = $race['1xcnt'];
                        $cnt2x = $race['2xcnt'];
                        $cnt3x = $race['3xcnt'];
                        $cnt4x = $race['4xcnt'];
                        $cnt5x = $race['5xcnt'];
                        $cnt6x = $race['6xcnt'];
                        $unique1x = $race['1xunique'];
                        $unique2x = $race['2xunique'];
                        $unique3x = $race['3xunique'];
                        $unique4x = $race['4xunique'];
                        $unique5x = $race['5xunique'];
                        $unique6x = $race['6xunique'];
                        $propprecnt = $race['propprecnt']; 
                        $proppremorethanone = $race['proppremorethanone']; 
                        
                        $racepropresult = [];
                        $racepropresult = load_prophist($search_racingdate, $search_venue, $wageringFieldSize, $TopPredigit, $propprecnt, $proppremorethanone, $cnt1x, $cnt2x, $cnt3x, $cnt4x, $cnt5x, $cnt6x, $unique1x, $unique2x, $unique3x, $unique4x, $unique5x, $unique6x);
                    }
                
                    //-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                    $TopPredigit2 = isset($race['TopPrePos2']) ? $race['TopPrePos2'] : "";
                    $xuniquearray = $TopPredigit."xuniquearray";
                    if($TopPredigit2 >= 40 && $TopPredigit2 < 45 && in_array($TopPredigit2, $race[$xuniquearray])){
                        $syspick[$venue][$raceno]['pos'] = "proppre_".$TopPredigit2;
                    }
                    if($race['3xunique'] == 2){
                        $bigger3x = max($race['3xuniquearray']);
                        $syspick[$venue][$raceno]['3x'] = max($race['3xuniquearray']);
                    }
                    // echo $raceno."-".$race['3xunique']."-".max($race['3xuniquearray'])."<br>";
                    // print_r($syspick);
                    //-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                    $bestime = [];
                    $bestTimesInSeconds = [];
                    
                    foreach ($filteredHorses as $horseno => $horsedetail) {
                        $horseid = $horsedetail['code'];
                        if (isset($horseinfo[$horseid]['Finish_Time'][$distance])) {
                            $finishTime = $horseinfo[$horseid]['Finish_Time'][$distance];
                    
                            $sec = array_reduce(explode('.', $finishTime), function($a, $b) { 
                                return $a * 60 + $b; 
                            }, 0);
                            $bestTimesInSeconds[$horseid] = $sec;
                        }
                    }
                    
                    $currentBestTimeInSeconds = !empty($bestTimesInSeconds) ? min($bestTimesInSeconds) : PHP_INT_MAX;
                    foreach ($filteredHorses as $horseno => $horsedetail) {
                        $horseid = $horsedetail['code'];
                        // echo $bestTimesInSeconds[$horseid] ."-". $currentBestTimeInSeconds."<br>";
                        if (isset($bestTimesInSeconds[$horseid])) {
                            $bestime[$horseid] = ($bestTimesInSeconds[$horseid] <= $currentBestTimeInSeconds) ? 1 : 0;
                        } else {
                            $bestime[$horseid] = 0;
                        }
                    }
                    // print_r($bestime);
                    //-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                    $tmp_PLAPre = null;
                    $rowcount = 1;
                    foreach ($filteredHorses as $horseno => $horsedetail) {
                        $venue_xp = '';
                        $venueall = '';
                        // Check if $horsedetail is an array and has the required keys
                        if (is_numeric($horseno) && is_array($horsedetail) && isset($horsedetail['name_ch'])) {
                            $horsename = $horsedetail['name_ch']; 
                            $horseid = $horsedetail['code']; 
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
                            $jockeycnt = $racingdata[$date][$venue]['jockeycnt'][$jockey];
                            $trainercnt = $racingdata[$date][$venue]['trainercnt'][$trainer];
                            $jockeytrainercnt = $racingdata[$date][$venue]['jockeytrainercnt'][$jockey.$trainer];
                            // $jockey_pla[$venue][$jockey] .= ($finalPosition ? $finalPosition : "");
                            $jockey_pla[$venue][$jockey] .= ($finalPosition ? ($jockey_pla[$venue][$jockey] ? ',' : '') . $finalPosition : "");
                            $trainer_pla[$venue][$trainer] .= ($finalPosition ? ($trainer_pla[$venue][$trainer] ? ',' : '') . $finalPosition : "");
                            $jockey_trainer_pla[$venue][$jockey_trainer] .= ($finalPosition ? ($jockey_trainer_pla[$venue][$jockey_trainer] ? ',' : '') . $finalPosition : "");
                            $barrierDrawNumber_pla[$venue][$barrierDrawNumber] .= ($finalPosition ? ($barrierDrawNumber_pla[$venue][$barrierDrawNumber] ? ',' : '') . $finalPosition : "");
                            
                            $Rtgtrend = trim($horseinfo[$horseid]['Rtgtrend']); // Initialize as an empty array to avoid access errors
                            //-----------------------------------------------------------------------------------------------------------
                            // $preds_picks = $venuePredictors[$vkey]->finalpick3(
                            //     $preds,
                            //     $race['propprearraywhorseno']['proppre']
                            // );
                            // $preds_picks_bg = '';
                            // if (isset($preds_picks[0]) && $horseno == $preds_picks[0]['horseno']) {
                            //     $preds_picks_bg = 'background-color: #FFB6C1;';
                            // } elseif (isset($preds_picks[1]) && $horseno == $preds_picks[1]['horseno']) {
                            //     $preds_picks_bg = 'background-color: #ADD8E6;';
                            // } elseif (isset($preds_picks[2]) && $horseno == $preds_picks[2]['horseno']) {
                            //     $preds_picks_bg = 'background-color: #90EE90;';
                            // }
                            // //-----------------------------
                            // $preds_picks_bg_ST = '';
                            // if (isset($preds_picks_ST[0]) && $horseno == $preds_picks_ST[0]['horseno']) {
                            //     $preds_picks_bg_ST = 'background-color: #FFB6C1;';
                            // } elseif (isset($preds_picks_ST[1]) && $horseno == $preds_picks_ST[1]['horseno']) {
                            //     $preds_picks_bg_ST = 'background-color: #ADD8E6;';
                            // } elseif (isset($preds_picks_ST[2]) && $horseno == $preds_picks_ST[2]['horseno']) {
                            //     $preds_picks_bg_ST = 'background-color: #90EE90;';
                            // }
                            // //-----------------------------
                            // $preds_picks_bg_HV = '';
                            // if (isset($preds_picks_HV[0]) && $horseno == $preds_picks_HV[0]['horseno']) {
                            //     $preds_picks_bg_HV = 'background-color: #FFB6C1;';
                            // } elseif (isset($preds_picks_HV[1]) && $horseno == $preds_picks_HV[1]['horseno']) {
                            //     $preds_picks_bg_HV = 'background-color: #ADD8E6;';
                            // } elseif (isset($preds_picks_HV[2]) && $horseno == $preds_picks_HV[2]['horseno']) {
                            //     $preds_picks_bg_HV = 'background-color: #90EE90;';
                            // }
                            $venues = ['ST', 'HV', 'Sx'];
                            foreach ($venues as $vkey) {
                                $predVar = 'preds_' . $vkey;
                                $pickVar = 'preds_picks_' . $vkey;
                                $bgVar   = 'preds_picks_bg_' . $vkey;
                            
                                $$predVar = $venuePredictors[$vkey]->predictRawProps(
                                    $vkey,
                                    $race['propprearraywhorseno']['proppre'],
                                    $race['WINPrearray']
                                );
                            
                                $$pickVar = $venuePredictors[$vkey]->finalpick3onlyrules(
                                    $$predVar,
                                    $race['propprearraywhorseno']['proppre']
                                );
                            
                                $$bgVar = '';
                                if (isset($$pickVar[0]) && $horseno == $$pickVar[0]['horseno']) {
                                    $$bgVar = 'background-color: #FFB6C1;';
                                } elseif (isset($$pickVar[1]) && $horseno == $$pickVar[1]['horseno']) {
                                    $$bgVar = 'background-color: #ADD8E6;';
                                } elseif (isset($$pickVar[2]) && $horseno == $$pickVar[2]['horseno']) {
                                    $$bgVar = 'background-color: #90EE90;';
                                }
                            }
                            
                            // echo $preds_picks_bg;
                            //-----------------------------------------------------------------------------------------------------------
                            // $last_jocky = $horseinfo[$horseid]['jockey'];
                            // $last_trainer = $horseinfo[$horseid]['trainer'];
                            // $last_dist = $horseinfo[$horseid]['dist'];
                            // $last_Win_Odds = $horseinfo[$horseid]['Win_Odds'];
                            // $last_pla = $horseinfo[$horseid]['pla'];
                            // Ensure the horse name exists in the horseinfo array
                            if (isset($horseinfo[$horseid])) {
                                // Initialize variables with default values if keys do not exist
                                $last_jocky = $horseinfo[$horseid]['jockey'] ?? null;
                                $last_trainer = $horseinfo[$horseid]['trainer'] ?? null;
                                $last_dist = $horseinfo[$horseid]['dist'] ?? null;
                                $last_Win_Odds = $horseinfo[$horseid]['Win_Odds'] ?? null;
                                $last_pla = $horseinfo[$horseid]['pla'] ?? null;
                                $last_venue_cn = $horseinfo[$horseid]['venue_cn'] ?? null;
                            } else {
                                // Handle the case where the horse name does not exist
                                // error_log("Warning: Horse name '$horsename' not found in horseinfo array.");
                                $last_jocky = null;
                                $last_trainer = null;
                                $last_dist = null;
                                $last_Win_Odds = null;
                                $last_pla = null;
                                $last_venue_cn = null;
                            }
                            $last_detail =  (isset($jockey) || isset($last_jocky) || isset($trainer) || isset($last_trainer) || $last_Win_Odds || $last_pla) ? (
                                            ($jockey==$last_jocky ? "" : "<font color=red>j</font>").
                                            ($trainer==$last_trainer ? "" : "<font color=red>t</font>").
                                            $last_Win_Odds.($last_Win_Odds ? "(" : "").
                                            ($last_pla>9 ? "-" : mapColorsHtml($last_pla)) ) : "/";
                            $isVenueX = $last_venue_cn ? ((isset($VenueCodeVN[$venue]) && isset($last_venue_cn) && $VenueCodeVN[$venue] == $last_venue_cn) ? " " : " background-color: #FFCDFF; ") : "";
                            //------
                            $last_vs_this =  ($last_Win_Odds || $last_pla) ? 
                                (($last_Win_Odds > $Pre_WINPre && $last_Win_Odds > $Curr_WIN) ? " background-color: #CDFFFF; " : ($last_Win_Odds > $Curr_WIN ? " background-color: #FFCDFF; " : "")) : "";

                            // echo $raceno.$horsen.$VenueCodeVN[$venue]."...".$last_venue_cn."<br>";
                            // $Running_Position = $horseinfo[$horseid]['inpla']['Running_Position'][$dist];
                            // Check if the horse name exists in the horseinfo array
                            if (isset($horseinfo[$horseid]['inpla']['Running_Position'][$dist])) {
                                $Running_Position = $horseinfo[$horseid]['inpla']['Running_Position'][$dist];
                            } else {
                                // Handle the case where the index does not exist
                                $Running_Position = null; // or any default value you prefer
                                // error_log("Warning: Running_Position for horse '$horsename' at distance '$dist' not found in horseinfo array.");
                            }
                            //-----------------------------------------------------------------------------------------------------------
                            // $Pre_WINPre = $oddsMerged[$venue][$raceno][$horseno]['Pre']['WINPre'];
                            // $Pre_PLAPre = $oddsMerged[$venue][$raceno][$horseno]['Pre']['PLAPre'];
                            // $Pre_proppre = $oddsMerged[$venue][$raceno][$horseno]['Pre']['proppre'];
                            // $Curr_WIN = $oddsMerged[$venue][$raceno][$horseno]['Curr']['WIN'];
                            // $Curr_PLA = $oddsMerged[$venue][$raceno][$horseno]['Curr']['PLA'];
                            // $Curr_prop = $oddsMerged[$venue][$raceno][$horseno]['Curr']['prop'];
                            // $Curr_oddsDropValue = number_format($oddsMerged[$venue][$raceno][$horseno]['Curr']['oddsDropValue'],0);
                            // $TRITop = $oddsMerged[$venue][$raceno]['TRITop']['countArray'][$horseno];
                            // $TCETop = $oddsMerged[$venue][$raceno]['TCETop']['countArray'][$horseno];
                            // $FFTop = $oddsMerged[$venue][$raceno]['FFTop']['countArray'][$horseno];
                            // $QTTTop = $oddsMerged[$venue][$raceno]['QTTTop']['countArray'][$horseno];
                            
                            // $proppre_same = ($horsedetail['Pre']['cnt'] > 1) ? " style='background-color: yellow;' " : "";
                            // $propcurr_same = ($horsedetail['Curr'][$Curr_prop]['cnt'] > 1) ? " style='background-color: yellow;' " : "";
                            // $ismax_proppre = ($Pre_proppre == $racingdata[$date][$venue][$raceno]['prop']['maxproppre']) ? " style='color: red;' " : "";
                            // $ismax_propcurr = ($Curr_prop == $racingdata[$date][$venue][$raceno]['prop']['maxpropcurr']) ? " style='color: red;' " : "";
                            // $ismax2x3xpre = ($Pre_proppre == $racingdata[$date][$venue][$raceno]['prop']['max2xpre'] || $Pre_proppre == $racingdata[$date][$venue][$raceno]['prop']['max3xpre']) ? " style='font-weight:bold;' " : "";
                            // $ismax2x3xcurr = ($Curr_prop == $racingdata[$date][$venue][$raceno]['prop']['max2xcurr'] || $Curr_prop == $racingdata[$date][$venue][$raceno]['prop']['max3xcurr']) ? " style='font-weight:bold;' " : "";
                            
                            // $istopTRITop = !empty($oddsMerged[$venue][$raceno]['TRITop']['top']) && in_array($horseno, $oddsMerged[$venue][$raceno]['TRITop']['top']) ? " style='font-weight:bold;color:#FF33FF;' " : " style=\"opacity: 0.5;\" ";
                            // $istopTCETop = !empty($oddsMerged[$venue][$raceno]['TCETop']['top']) && in_array($horseno, $oddsMerged[$venue][$raceno]['TCETop']['top']) ? " style='font-weight:bold;color:#FF33FF;' " : " style=\"opacity: 0.5;\" ";
                            // $istopFFTop  = !empty($oddsMerged[$venue][$raceno]['FFTop']['top']) && in_array($horseno, $oddsMerged[$venue][$raceno]['FFTop']['top']) ? " style='font-weight:bold;color:#FF33FF;' " : " style=\"opacity: 0.5;\" ";
                            // $istopQTTTop = !empty($oddsMerged[$venue][$raceno]['QTTTop']['top']) && in_array($horseno, $oddsMerged[$venue][$raceno]['QTTTop']['top']) ? " style='font-weight:bold;color:#FF33FF;' " : " style=\"opacity: 0.5;\" ";
                            // Check if necessary keys exist in the $oddsMerged array
                            $Pre_WINPre = $oddsMerged[$venue][$raceno][$horseno]['Pre']['WINPre'] ?? null;
                            $Pre_PLAPre = $oddsMerged[$venue][$raceno][$horseno]['Pre']['PLAPre'] ?? null;
                            $Pre_proppre = $oddsMerged[$venue][$raceno][$horseno]['Pre']['proppre'] ?? null;
                            $Curr_WIN = $oddsMerged[$venue][$raceno][$horseno]['Curr']['WIN'] ?? null;
                            $Curr_PLA = $oddsMerged[$venue][$raceno][$horseno]['Curr']['PLA'] ?? null;
                            $Curr_prop = $oddsMerged[$venue][$raceno][$horseno]['Curr']['prop'] ?? null;
                            $Curr_oddsDropValue = number_format($oddsMerged[$venue][$raceno][$horseno]['Curr']['oddsDropValue'] ?? 0, 0);
                            
                            // Check if TRITop, TCETop, FFTop, and QTTTop exist
                            $TRITop = $oddsMerged[$venue][$raceno]['TRITop']['countArray'][$horseno] ?? null;
                            $TCETop = $oddsMerged[$venue][$raceno]['TCETop']['countArray'][$horseno] ?? null;
                            $FFTop = $oddsMerged[$venue][$raceno]['FFTop']['countArray'][$horseno] ?? null;
                            $QTTTop = $oddsMerged[$venue][$raceno]['QTTTop']['countArray'][$horseno] ?? null;
                            
                            // Style checks
                            $proppre_same = (isset($horsedetail['Pre']['cnt']) && $Pre_proppre && $horsedetail['Pre']['cnt'] > 1 && $horsedetail['Pre']['cnt'] <=3) ? (($horsedetail['Pre']['cnt'] == 2) ? " background-color: yellow; " : " background-color: #CDFFCD; ") : "";
                            $proppre_same4x = (isset($horsedetail['Pre']['cnt']) && $Pre_proppre && $horsedetail['Pre']['cnt'] >= 4) ? " background-color: #CDFFCD; " : "";
                            $propcurr_same = (isset($horsedetail['Curr']['cnt']) && $Curr_prop && $horsedetail['Curr']['cnt'] > 1) ? (($horsedetail['Curr']['cnt'] == 2) ? " background-color: yellow; " : " background-color: #CDFFCD; ") : "";
                            $ismax2xpre = (isset($race['prop']['max2xpre']) && $Pre_proppre && $race['prop']['max2xpre'] == $Pre_proppre) ? " text-align: right; " : "";
                            $ismax3xpre = (isset($race['prop']['max3xpre']) && $Pre_proppre && $race['prop']['max3xpre'] == $Pre_proppre) ? " text-align: right; " : "";
                            $ismax2xcurr = (isset($race['prop']['max2xcurr']) && $Curr_prop && $race['prop']['max2xcurr'] == $Curr_prop) ? " text-align: right; " : "";
                            $ismax3xcurr = (isset($race['prop']['max3xcurr']) && $Curr_prop && $race['prop']['max3xcurr'] == $Curr_prop) ? " text-align: right; " : "";
                            
                            $precnt = (isset($horsedetail['Pre']['cnt']) && $horsedetail['Pre']['cnt'] >= 3) ? " <i> " : "";
                            $precnt1 = (isset($horsedetail['Pre']['cnt']) && $horsedetail['Pre']['cnt'] >= 3) ? " text-align: center; " : "";
                            $currcnt = (isset($horsedetail['Curr']['cnt']) && $horsedetail['Curr']['cnt'] >= 3) ? " <i> " : "";
                            $currcnt1 = (isset($horsedetail['Curr']['cnt']) && $horsedetail['Curr']['cnt'] >= 3) ? " text-align: center; " : "";
                            
                            $ismax_proppre = ($Pre_proppre == ($racingdata[$date][$venue][$raceno]['prop']['maxproppre'] ?? null)) ? " color: red; " : "";
                            $ismax_propcurr = ($Curr_prop == ($racingdata[$date][$venue][$raceno]['prop']['maxpropcurr'] ?? null)) ? " color: red; " : "";
                            $ismax2x3xpre = ($Pre_proppre == ($racingdata[$date][$venue][$raceno]['prop']['max2xpre'] ?? null) || $Pre_proppre == ($racingdata[$date][$venue][$raceno]['prop']['max3xpre'] ?? null)) ? " font-weight:bold; " : "";
                            $ismax2x3xcurr = ($Curr_prop == ($racingdata[$date][$venue][$raceno]['prop']['max2xcurr'] ?? null) || $Curr_prop == ($racingdata[$date][$venue][$raceno]['prop']['max3xcurr'] ?? null)) ? " font-weight:bold; " : "";
                            
                            // Top checks
                            $istopTRITop = !empty($oddsMerged[$venue][$raceno]['TRITop']['top']) && in_array($horseno, $oddsMerged[$venue][$raceno]['TRITop']['top']) ? " style='font-weight:bold;color:#FF33FF;' " : " style=\"opacity: 0.5;\" ";
                            $istopTCETop = !empty($oddsMerged[$venue][$raceno]['TCETop']['top']) && in_array($horseno, $oddsMerged[$venue][$raceno]['TCETop']['top']) ? " style='font-weight:bold;color:#FF33FF;' " : " style=\"opacity: 0.5;\" ";
                            $istopFFTop  = !empty($oddsMerged[$venue][$raceno]['FFTop']['top']) && in_array($horseno, $oddsMerged[$venue][$raceno]['FFTop']['top']) ? " style='font-weight:bold;color:#FF33FF;' " : " style=\"opacity: 0.5;\" ";
                            $istopQTTTop = !empty($oddsMerged[$venue][$raceno]['QTTTop']['top']) && in_array($horseno, $oddsMerged[$venue][$raceno]['QTTTop']['top']) ? " style='font-weight:bold;color:#FF33FF;' " : " style=\"opacity: 0.5;\" ";
                            
                        //-----------------------------------------------------------------------------------------------------------
                        //-----------------------------------------------------------------------------------------------------------
                        //-----------------------------------------------------------------------------------------------------------
                            // $ispicked[$date][$venueCode][$raceno][$horseno] .= (isset($horsedetail['Pre'][$syspick[$venue][$raceno]['pos']][1]) && $horsedetail['Pre'][$syspick[$venue][$raceno]['pos']][1] == $horsedetail['Pre']['propprepos'] ) ? "background-color: #FFCDFF;" : "";
                            // $ispicked[$date][$venueCode][$raceno][$horseno] .= (isset($syspick[$venue][$raceno]['3x']) && $horsedetail['Pre']["proppre_".$syspick[$venue][$raceno]['3x']][0] == $horsedetail['Pre']['propprepos'] ) ? "background-color: #FFCDFF;" : "";
                            // if(isset($tmp_proppre) && is_numeric($Curr_WIN) && getFirstDigit($tmp_proppre) == getFirstDigit($Pre_proppre) && $tmp_proppre > $Pre_proppre 
                            //     && $racingdata[$date][$venue][$raceno]['propprecnt'][getFirstDigit($tmp_proppre)."_".$tmp_proppre] ==1
                            //     && $racingdata[$date][$venue][$raceno]['propprecnt'][getFirstDigit($Pre_proppre)."_".$Pre_proppre] ==1
                            //     && $Pre_proppre <40){
                            //     $ispicked[$date][$venue][$raceno][$horseno] .= "color: red;";
                            //     $pickcount[$date][$venue][$raceno][$horseno]++;
                            // }
                            // if(is_numeric($Curr_WIN) && in_array("5", $racingdata[$date][$venue][$raceno]['proppremorethanone_init']) && !in_array($Pre_proppre, $racingdata[$date][$venue][$raceno]['proppremorethanone'])
                            //     && !in_array("4", $racingdata[$date][$venue][$raceno]['proppremorethanone_init']) ){
                            //     $pickcount[$date][$venue][$raceno][$horseno]++;    
                            // }
                            
                            
                        //-----------------------------------------------------------------------------------------------------------
                            $venueallarray = [];
                            
                            // Check if venuetotalpla is set
                            if (isset($horseinfo[$horseid]['venuetotalpla'])) {
                                foreach ($horseinfo[$horseid]['venuetotalpla'] as $venus2dig => $placnt) {
                                    // Check if the key is not empty and count is more than 0
                                    if (!empty($venus2dig) && $placnt > 0) {
                                        $v = mb_substr($venus2dig, 0, 1, 'UTF-8'); // Get the first character of the venue
                                        // Initialize or add count to the venue in the venueallarray
                                        if (!isset($venueallarray[$v])) {
                                            $venueallarray[$v] = [];
                                        }
                                        $venueallarray[$v][] = $placnt; // Store counts for each venue
                                    }
                                }
                            }
                            
                            // Check if venuetotalplax is set
                            if (isset($horseinfo[$horseid]['venuetotalplax'])) {
                                foreach ($horseinfo[$horseid]['venuetotalplax'] as $venus2dig => $placnt) {
                                    // Check if the key is not empty and count is more than 0
                                    if (!empty($venus2dig) && $placnt > 0) {
                                        $v = mb_substr($venus2dig, 0, 1, 'UTF-8'); // Get the first character of the venue
                                        // Initialize or add count to the venue in the venueallarray
                                        if (!isset($venueallarray[$v])) {
                                            $venueallarray[$v] = [];
                                        }
                                        $venueallarray[$v][] = -$placnt; // Store counts for each venue
                                    }
                                }
                            }
                            
                            // Create the formatted output
                            $infovenue = [];
                            foreach ($venueallarray as $venueinfo => $counts) {
                                $firstCount = array_shift($counts); // Get the first count
                                $infovenue[] = $venueinfo . $firstCount; // Start with the first count
                                if (!empty($counts)) {
                                    // Add remaining counts separated by a slash
                                    // $infovenue[] .= '/' . implode('/', $counts);
                                    $infovenue[] .= '' . implode('', $counts);
                                }
                            }
                            
                            // Join the results with '' to format as "跑1/4沙1/9"
                            $infovenues = implode('', $infovenue);
                            
                            // If there are no valid entries, assign "-"
                            if (empty($infovenues)) {
                                $infovenues = "-";
                            }
                            //-------------
                            // Check if venuechangeplacnt is set
                            if (isset($horseinfo[$horseid]['venuechangeplacnt'])) {
                                foreach ($horseinfo[$horseid]['venuechangeplacnt'] as $venus2dig => $placnt) {
                                    // Check if the key is not empty, not a numeric zero, and count is more than 0
                                    if (!empty($venus2dig) && $placnt > 0) {
                                        // Concatenate the first character of the venue with its count
                                        $venue_xp .= mb_substr($venus2dig, 0, 1, 'UTF-8') . $placnt;  
                                    }
                                }
                            }
                            
                            // If there are no valid entries, assign "-"
                            if (empty($venue_xp)) {
                                $venue_xp = "-";
                            }



                            //-------------
                            // $jockey_inpla = in_array($jockey, $horseinfo[$horseid]['inpla']['jockey']) ? "bgcolor=#99FF99" : "";
                            $jockey_alert = (isset($horseinfo[$horseid]['inpla']['jockey']) && is_array($horseinfo[$horseid]['inpla']['jockey']) && in_array($jockey, $horseinfo[$horseid]['inpla']['jockey'])) 
                                ? "bgcolor=#99FF99" 
                                : " style=\"opacity: 0.5;\" ";
                            // $jockey_inpla = $horseinfo[$horseid]['inpla']['cnt_jockey'][$jockey];
                            // $jockey_notinpla = $horseinfo[$horseid]['notinpla']['cnt_jockey'][$jockey];
                            // Initialize variables
                            $jockey_inpla = null;
                            $jockey_notinpla = null;
                            
                            // Check for inpla cnt_jockey
                            if (isset($horseinfo[$horseid]['inpla']['cnt_jockey'][$jockey])) {
                                $jockey_inpla = $horseinfo[$horseid]['inpla']['cnt_jockey'][$jockey];
                            } else {
                                // error_log("Warning: cnt_jockey for jockey '$jockey' in inpla not found for horse '$horsename'.");
                            }
                            
                            // Check for notinpla cnt_jockey
                            if (isset($horseinfo[$horseid]['notinpla']['cnt_jockey'][$jockey])) {
                                $jockey_notinpla = $horseinfo[$horseid]['notinpla']['cnt_jockey'][$jockey];
                            } else {
                                // error_log("Warning: cnt_jockey for jockey '$jockey' in notinpla not found for horse '$horsename'.");
                            }
                            $jockey_alert = textcolorAlert($jockey_inpla, $jockey_notinpla);
                            //-------------
                            $distanceArray = isset($horseinfo[$horseid]['inpla']['dist']) && is_array($horseinfo[$horseid]['inpla']['dist']) 
                                ? $horseinfo[$horseid]['inpla']['dist'] 
                                : [];
                            
                            $distValue = intval($distance / 100); // Convert distance to the required value
                            $dist_alert = in_array($distValue, $distanceArray) ? "bgcolor=#99FF99" : " style=\"opacity: 0.5;\" ";
                            // $dist_inpla = $horseinfo[$horseid]['inpla']['cnt_dist'][$dist];
                            // $dist_notinpla = $horseinfo[$horseid]['notinpla']['cnt_dist'][$dist];
                            // Initialize variables
                            $dist_inpla = null;
                            $dist_notinpla = null;
                            
                            // Check for inpla cnt_dist
                            if (isset($horseinfo[$horseid]['inpla']['cnt_dist'][$dist])) {
                                $dist_inpla = $horseinfo[$horseid]['inpla']['cnt_dist'][$dist];
                            } else {
                                // error_log("Warning: cnt_dist for distance '$dist' in inpla not found for horse '$horsename'.");
                            }
                            
                            // Check for notinpla cnt_dist
                            if (isset($horseinfo[$horseid]['notinpla']['cnt_dist'][$dist])) {
                                $dist_notinpla = $horseinfo[$horseid]['notinpla']['cnt_dist'][$dist];
                            } else {
                                // error_log("Warning: cnt_dist for distance '$dist' in notinpla not found for horse '$horsename'.");
                            }
                            $dist_alert = textcolorAlert($dist_inpla, $dist_notinpla);
                            //-------------
                            $trackCourseArray = isset($horseinfo[$horseid]['inpla']['RC_Track_Course']) && is_array($horseinfo[$horseid]['inpla']['RC_Track_Course']) 
                                ? $horseinfo[$horseid]['inpla']['RC_Track_Course'] 
                                : [];
                            $Course_alert = in_array($raceCourse, $trackCourseArray) ? "bgcolor=#99FF99" : " style=\"opacity: 0.5;\" ";
                            // $Course_inpla = $horseinfo[$horseid]['inpla']['cnt_RC_Track_Course'][$raceCourse];
                            // $Course_notinpla = $horseinfo[$horseid]['notinpla']['cnt_RC_Track_Course'][$raceCourse];
                            // Initialize variables
                            $Course_inpla = null;
                            $Course_notinpla = null;
                            
                            // Check for inpla cnt_RC_Track_Course
                            if (isset($horseinfo[$horseid]['inpla']['cnt_RC_Track_Course'][$raceCourse])) {
                                $Course_inpla = $horseinfo[$horseid]['inpla']['cnt_RC_Track_Course'][$raceCourse];
                            } else {
                                // error_log("Warning: cnt_RC_Track_Course for race course '$raceCourse' in inpla not found for horse '$horsename'.");
                            }
                            
                            // Check for notinpla cnt_RC_Track_Course
                            if (isset($horseinfo[$horseid]['notinpla']['cnt_RC_Track_Course'][$raceCourse])) {
                                $Course_notinpla = $horseinfo[$horseid]['notinpla']['cnt_RC_Track_Course'][$raceCourse];
                            } else {
                                // error_log("Warning: cnt_RC_Track_Course for race course '$raceCourse' in notinpla not found for horse '$horsename'.");
                            }
                            $Course_alert = textcolorAlert($Course_inpla, $Course_notinpla);
                            //-------------
                            // $jtArray = isset($horseinfo[$horseid]['inpla']['jt']) && is_array($horseinfo[$horseid]['inpla']['jt']) 
                            //     ? $horseinfo[$horseid]['inpla']['jt'] 
                            //     : [];
                            
                            // $jt_alert = isset($jtArray[$jockey_trainer]) && is_array($jtArray[$jockey_trainer]) && in_array($jockey_trainer, $jtArray[$jockey_trainer]) 
                            //     ? "bgcolor=#99FF99" 
                            //     : " style=\"opacity: 0.5;\" ";
                            // $jt_inpla = $horseinfo[$horseid]['inpla']['cnt_jt'][$jockey_trainer];
                            // $jt_notinpla = $horseinfo[$horseid]['notinpla']['cnt_jt'][$jockey_trainer];
                            // Initialize variables
                            $jt_inpla = null;
                            $jt_notinpla = null;
                            
                            // Check for inpla cnt_jt
                            if (isset($horseinfo[$horseid]['inpla']['cnt_jt'][$jockey_trainer])) {
                                $jt_inpla = $horseinfo[$horseid]['inpla']['cnt_jt'][$jockey_trainer];
                            } else {
                                // error_log("Warning: cnt_jt for jockey/trainer '$jockey_trainer' in inpla not found for horse '$horsename'.");
                            }
                            
                            // Check for notinpla cnt_jt
                            if (isset($horseinfo[$horseid]['notinpla']['cnt_jt'][$jockey_trainer])) {
                                $jt_notinpla = $horseinfo[$horseid]['notinpla']['cnt_jt'][$jockey_trainer];
                            } else {
                                // error_log("Warning: cnt_jt for jockey/trainer '$jockey_trainer' in notinpla not found for horse '$horsename'.");
                            }
                            $jt_alert = textcolorAlert($jt_inpla, $jt_notinpla);
                            // $JT_cnt = $horseinfo['inpla']['jt'][$jockey_trainer]."/".$horseinfo['notinpla']['jt'][$jockey_trainer];
                            // Initialize variables
                            // $jtinplaCount = isset($horseinfo[$horseid]['inpla']['cnt_jt'][$jockey_trainer]) ? $horseinfo[$horseid]['inpla']['cnt_jt'][$jockey_trainer] : 0;
                            // $jtnotInplaCount = isset($horseinfo[$horseid]['notinpla']['cnt_jt'][$jockey_trainer]) ? $horseinfo[$horseid]['notinpla']['cnt_jt'][$jockey_trainer] : 0;
                            // // Calculate total count
                            // $jttotalCount = $jtinplaCount + $jtnotInplaCount;
                            
                            // // Initialize the result variable
                            // $jt_alert = " style=\"opacity: 0.5;\" "; // Default to 3
                            
                            // // Check if the total count is greater than 0 to avoid division by zero
                            // if ($jttotalCount > 0) {
                            //     $jtpercentage = $jtinplaCount / $jttotalCount; // Calculate the percentage
                            
                            //     // Determine the result based on the percentage
                            //     if ($jtpercentage >= 1) {
                            //         $jt_alert = " style='background-color: #99FF99; color:red;' "; // More than 100%
                            //     } elseif ($jtpercentage > 0.75) {
                            //         $jt_alert = " style='background-color: #99FF99;' "; // More than 75%
                            //     } elseif ($jtpercentage > 0.40) {
                            //         $jt_alert = " style='background-color: #CDFFCD;' "; // More than 50%
                            //     }
                            // }
                            
                            //-------------
                            // $JT_cnt = $horseinfo['inpla']['jt'][$jockey_trainer]."/".$horseinfo['notinpla']['jt'][$jockey_trainer];
                            // Initialize variable
                            $JT_cnt = '';
                            
                            // Check if the required keys exist in the horseinfo array
                            if (isset($horseinfo['inpla']['jt'][$jockey_trainer]) && isset($horseinfo['notinpla']['jt'][$jockey_trainer])) {
                                $JT_cnt = $horseinfo['inpla']['jt'][$jockey_trainer] . "/" . $horseinfo['notinpla']['jt'][$jockey_trainer];
                            } else {
                                // Handle the case where the keys are not found
                                $inpla_jt = $horseinfo['inpla']['jt'][$jockey_trainer] ?? '0'; // Default to '0' if not set
                                $notinpla_jt = $horseinfo['notinpla']['jt'][$jockey_trainer] ?? '0'; // Default to '0' if not set
                                $JT_cnt = ($inpla_jt || $notinpla_jt ? ($inpla_jt ? $inpla_jt : "") . "/" . ($notinpla_jt ? $notinpla_jt : "") : "");
                                // error_log("Warning: cnt for jockey/trainer '$jockey_trainer' not found in horseinfo array.");
                            }
                            $JT_cnt_alert = textcolorAlert($horseinfo['inpla']['jt'][$jockey_trainer], $horseinfo['notinpla']['jt'][$jockey_trainer]);
                            
                            // Initialize variables
                            // $inplaCount = isset($horseinfo['inpla']['jt'][$jockey_trainer]) ? $horseinfo['inpla']['jt'][$jockey_trainer] : 0;
                            // $notInplaCount = isset($horseinfo['notinpla']['jt'][$jockey_trainer]) ? $horseinfo['notinpla']['jt'][$jockey_trainer] : 0;
                            // // Calculate total count
                            // $totalCount = $inplaCount + $notInplaCount;
                            
                            // // Initialize the result variable
                            // $JT_cnt_alert = " style=\"opacity: 0.5;\" "; // Default to 3
                            
                            // // Check if the total count is greater than 0 to avoid division by zero
                            // if ($totalCount > 0) {
                            //     $percentage = $inplaCount / $totalCount; // Calculate the percentage
                            
                            //     // Determine the result based on the percentage
                            //     if ($percentage >= 1) {
                            //         $JT_cnt_alert = " style='background-color: #99FF99; color:red;' "; // More than 75%
                            //     } elseif ($percentage > 0.75) {
                            //         $JT_cnt_alert = " style='background-color: #99FF99;' "; // More than 75%
                            //     } elseif ($percentage > 0.50) {
                            //         $JT_cnt_alert = " style='background-color: ##CDFFCD;' "; // More than 50%
                            //     }
                            // }
                            //-----------------------------------------------------------------------------------------------------------
                            $oddsDropdate = (isset($oddsdrop[$horseid]['date']) && $oddsdrop[$horseid]['date'] >= $onemonth) 
                                ? ($oddsdrop[$horseid]['date'] >= $oneweek ? " style='background-color: #FFCDFF;' " : " style='background-color: #CDFFFF;' ") : " style=\"opacity: 0.5;\" ";
                            // Initialize the value for oddsDropdate
                            $oddsDrophist = ''; // Default value
                            
                            // Check if the required key exists in the oddsdrop array
                            if (isset($oddsdrop[$horseid]['values'])) {
                                $oddsDrophist = $oddsdrop[$horseid]['values'];
                            } else {
                                // Handle the case where the key is not found
                                // error_log("Warning: 'values' for code '$horseid' not found in oddsdrop array.");
                                $oddsDrophist = ''; // Default value if not found
                            }                            
                            //-----------------------------------------------------------------------------------------------------------
                            $prepla_alert = ($tmp_PLAPre && $Pre_PLAPre < $tmp_PLAPre) ? " <font style=\"color:red;\"> " : "";
                            $prewin_alert = ($tmp_PLAPre && $Pre_PLAPre < $tmp_PLAPre && ($Pre_WINPre/$Curr_WIN > 2)) ? " <font style=\"color:red;\"> " : "";
                            //-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                            $isbestime = ($bestime[$horseid]==1) ? "background-color: #FFCDFF;" : "";
                            //-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                            // print_r($race);
                            // die();
                            $pageContent .= "<tr>";
                            //-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                            if(isset($ismm)){
                                if(isset($racepropresult)){
                                    // print_r($racepropresult);
                                    for ($i = 1; $i <= $propresultcolumn; $i++) {
                                        // Ensure the row count and proppre are valid
                                        // if (isset($racepropresult[$i][$rowcount])) {
                                            $racepropresult_proppre = $racepropresult[$i][$rowcount]['proppre'];
                                            $racepropresult_finalPosition = $racepropresult[$i][$rowcount]['finalPosition'];
                                    
                                            // Access occurrences correctly
                                            $racepropresult_occurrences = $racepropresult[$i]['occurrences'][$racepropresult_proppre] ?? 0; // Defaults to 0 if not found
                                            $racepropresult__ispla = (isset($racepropresult_finalPosition)) ? PlaColors($racepropresult_finalPosition) : '';
                                            $racepropresult__ispla1 = ($racepropresult_finalPosition <= 4) ? " text-decoration: underline;" : "";
                                            
                                            // Determine the background color
                                            $racepropresult__same = (isset($racepropresult_occurrences) && $racepropresult_occurrences > 1) ? 
                                                (($racepropresult_occurrences == 2) ? " background-color: yellow; " : " background-color: #CDFFCD; ") : "";
                                    
                                            // Build the output
                                            $pageContent .= "<td nowrap style=\"$racepropresult__same $racepropresult__ispla $racepropresult__ispla1\">".$racepropresult_proppre; //." (Occurrences: $racepropresult_occurrences)</td>";
                                        // }
                                    }
                                }else{
                                    for ($i = 1; $i <= $propresultcolumn; $i++) {
                                        $pageContent .= "<td>";
                                    }
                                }
                            }
                            
                            // Print the constructed URL for verification
                            // print_r($racepropresult);
                            // die();
                            //-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                            if($ismm){
                                $pageContent .= "<td nowrap style=\" $borderright \">";//.$TopPredigit."-".$cnt1x."-".$cnt2x."-".$cnt3x."-".$cnt4x."-".$cnt5x."-".$unique1x."-".$unique2x."-".$unique3x."-".$unique4x."-".$unique5x;
                                $pageContent .= "<td nowrap style=\" $proppre_same $ismax_proppre $ismax2x3xpre $ismax2xpre $ismax3xpre  $precnt1 $proppre_same4x\">$precnt".($Pre_proppre ? $Pre_proppre : "-");
                                $pageContent .= "<td nowrap style=\" $borderright $propcurr_same $ismax_propcurr $ismax2x3xcurr $ismax2xcurr $ismax3xcurr  $currcnt1\">$precnt".($Curr_prop ? $Curr_prop : "-");
                                $istoprank1 = ($hkracing_v2_score[$date][$venue][$raceno][$horseno]['score_version_1_rank'] && $hkracing_v2_score[$date][$venue][$raceno][$horseno]['score_version_1_rank'] <= 4) ? "<b>" : "<font color=lightgrey>"; 
                                $istoprank2 = ($hkracing_v2_score[$date][$venue][$raceno][$horseno]['score_version_2_rank'] && $hkracing_v2_score[$date][$venue][$raceno][$horseno]['score_version_2_rank'] <= 4) ? "<b>" : "<font color=lightgrey>"; 
                                $istoprankpriority = ($hkracing_v2_score[$date][$venue][$raceno][$horseno]['priority'] && $hkracing_v2_score[$date][$venue][$raceno][$horseno]['priority'] <= 4) ? "<b>" : "<font color=lightgrey>"; 
                                $pageContent .= "<td nowrap>".$istoprank1.($hkracing_v2_score[$date][$venue][$raceno][$horseno]['score_version_1_rank'] ? number_format($hkracing_v2_score[$date][$venue][$raceno][$horseno]['score_version_1_rank']) : '');
                                $pageContent .= "<td nowrap>".$istoprank2.($hkracing_v2_score[$date][$venue][$raceno][$horseno]['score_version_2_rank'] ? number_format($hkracing_v2_score[$date][$venue][$raceno][$horseno]['score_version_2_rank']) : '');
                                $pageContent .= "<td nowrap>".($hkracing_v2_score[$date][$venue][$raceno][$horseno]['barrier_trial_level'] ?? "");
                                $pageContent .= "<td nowrap>".$istoprankpriority.($hkracing_v2_score[$date][$venue][$raceno][$horseno]['priority'] ? number_format($hkracing_v2_score[$date][$venue][$raceno][$horseno]['priority']) : '');
                                
                                $pageContent .= "<td bgcolor=lightgrey nowrap>".number_format($prediction[$horseno]['probability']*100);
                                $pageContent .= "<td bgcolor=lightgrey nowrap>".number_format($prediction[$horseno]['rule_hits']);
                                $pageContent .= "<td bgcolor=lightgrey nowrap>".number_format($prediction[$horseno]['ruleProb']*100);
                                $pageContent .= "<td bgcolor=lightgrey nowrap>".number_format($prediction[$horseno]['baseProb']*100);
                            }
                            //-----------
                            $pageContent .= "<td nowrap style='text-align: right; opacity: 0.6;'>".$Pre_WINPre;
                            $pageContent .= "<td nowrap style='text-align: right; '>".($Curr_WIN <$Pre_WINPre ? "<b>" : "").$prewin_alert.$Curr_WIN;
                            $pageContent .= "<td nowrap >".($Curr_PLA < $Pre_PLAPre ? "<b>" : "").$Curr_PLA;
                            $pageContent .= "<td nowrap style=\"opacity: 0.6;\"  $borderright>".$prepla_alert.$Pre_PLAPre;
                            //-----------
                            $pageContent .= "<td nowrap style=\" $last_vs_this \">".$last_detail;
                            // $pageContent .= "<td $oddsDropdate>".$oddsdrop[$horseid]['values'];
                            
                            // Build the page content
                            $pageContent .= "<td nowrap $oddsDropdate>".$oddsDrophist; // Adjust as needed for the context
                            $pageContent .= "<td nowrap >".($Curr_oddsDropValue ? "<b><font color=red>".$Curr_oddsDropValue."</b>" : "");
                            //-----------
                            $pageContent .= "<td nowrap $istopTRITop>".$TRITop;
                            $pageContent .= "<td nowrap $istopTCETop>".$TCETop;
                            $pageContent .= "<td nowrap $istopFFTop>".$FFTop;
                            $pageContent .= "<td nowrap $istopQTTTop>".$QTTTop;
                            //-----------
                            // $pageContent .= "<td nowrap style=\"{$ispicked[$date][$venue][$raceno][$horseno]}\">".convertToCircledNumber($horseno).$chk;
                            $pageContent .= "<td nowrap style=\"{$preds_picks_bg}\">".convertToCircledNumber($horseno).$chk;
                            $pageContent .= "<td nowrap >$ispla".($finalPosition ? $finalPosition : "");

                            $pick1 = '';
                            $pick2 = '';
                            $ishotFavourite = '';
                            $pick3 = '';
                            $istrumpCard = '';
                            $horsecode = ''; // Initialize to a default value
                            
                            // Example conditions to set the variables
                            // Replace these with your actual logic
                            // if (/* condition for pick1 */) {
                            //     $pick1 = "style='color:red;'"; // Example style
                            // }
                            // if (/* condition for pick2 */) {
                            //     $pick2 = "style='color:blue;'"; // Example style
                            // }
                            // if (/* condition for ishotFavourite */) {
                            //     $ishotFavourite = "checked='checked'"; // Example checked attribute
                            // }
                            // if (/* condition for pick3 */) {
                            //     $pick3 = "style='color:green;'"; // Example style
                            // }
                            // if (/* condition for istrumpCard */) {
                                $istrumpCard = $trumpCard ? "background-color: #FFCDFF;" : ""; // Example checked attribute
                            // }
                            // if (/* condition for horsecode */) {
                            //     $horsecode = /* logic to assign horsecode */; // Set based on your logic
                            // }
                            
                            // Ensure Rtgtrend is populated before accessing it
                            // Example: $Rtgtrend = ['someHorseCode' => 'TrendValue']; // Populate as needed
                            
                            // Build the page content
                            $pageContent .= "<td $pick1 style=\"{$preds_picks_bg_ST}\">".$pickcount[$date][$venue][$raceno][$horseno]."<input type='checkbox'></td>";
                            $pageContent .= "<td $pick2 style=\"{$preds_picks_bg_HV}\">". $pickHV[$date][$venue][$raceno][$horseno] . "<input type='checkbox' $ishotFavourite >" . $trainnerranking[$trainer] . "</td>";
                            $pageContent .= "<td style=\"{$preds_picks_bg_Sx}\">" . "<input type='checkbox' class='checkbox' >" . ($Rtgtrend ?? '') . $jockeyranking[$jockey] . "</td>"; //style=\"{$istrumpCard}\" 
                            //------------------------------------------------------------------------------------------------------------------------------
                            $pageContent .= "<td nowrap style=\" $isbestime \">".$infovenues;  //."----".$bestime[$horseid]; //.$last_venue_cn;
                            $pageContent .= "<td nowrap style=\" $isVenueX \">".$venue_xp; //.$last_venue_cn;
                            $pageContent .= "<td nowrap $jockey_alert>".($jockey_inpla || $jockey_notinpla ? $jockey_inpla."/".$jockey_notinpla : "");
                            $pageContent .= "<td nowrap $dist_alert>".($dist_inpla || $dist_notinpla ? $dist_inpla."/".$dist_notinpla : "");
                            $pageContent .= "<td nowrap $Course_alert>".($Course_inpla || $Course_notinpla ? $Course_inpla."/".$Course_notinpla : "");
                            $pageContent .= "<td nowrap $jt_alert>".($jt_inpla || $jt_notinpla ? $jt_inpla."/".$jt_notinpla : "");
                            $pageContent .= "<td nowrap $JT_cnt_alert>".$JT_cnt;
                            $pageContent .= "<td nowrap>".$Running_Position;
                            //------------------------------------------------------------------------------------------------------------------------------
                            
                            $pageContent .= "<td nowrap>".$horsename;
                            
                            $pageContent .= "<td nowrap>(".$barrierDrawNumber.")".mapColorsHtml($barrierDrawNumber_pla[$venue][$barrierDrawNumber]);
                            $pageContent .= "<td nowrap>{".$jockeytrainercnt."}".mapColorsHtml($jockey_trainer_pla[$venue][$jockey_trainer]);
                            $pageContent .= "<td nowrap>".$trainer;
                            $pageContent .= "<td nowrap>[".$trainercnt."]".mapColorsHtml($trainer_pla[$venue][$trainer]);
                            $pageContent .= "<td nowrap>".$jockey;
                            $pageContent .= "<td nowrap>[".$jockeycnt."]".mapColorsHtml($jockey_pla[$venue][$jockey]);
                            $pageContent .= "<td nowrap>".colorstr($last6run);
                            
                            // $tmp_PLAPre = $odds[$venue][$raceno][$horseno]['Pre']['PLAPre'];
                            // Initialize the variable
                            
                            
                            // Check if the required keys exist in the odds array
                            if (isset($Pre_PLAPre)) {
                                $tmp_PLAPre = $Pre_PLAPre;
                            } else {
                                // Handle the case where the keys are not found
                                // error_log("Warning: PLAPre for venue '$venue', race number '$raceno', horse number '$horseno' not found.");
                                $tmp_PLAPre = 0; // Default value if not found
                            }
                            if (isset($Pre_proppre) ) {
                                $tmp_proppre = $Pre_proppre;
                            } else {
                                // Handle the case where the keys are not found
                                // error_log("Warning: PLAPre for venue '$venue', race number '$raceno', horse number '$horseno' not found.");
                                $tmp_proppre = 0; // Default value if not found
                            }
                            
                            // Continue with your logic using $tmp_PLAPre
                            $rowcount++;         
                        }
                    }
                    
                    $pageContent .= "</tbody>";
                    $pageContent .= "</table>";
                }else{
                    if(in_array($raceno, ['TNC','JKC'])){
                        $pageContent .= "<hr><table>";
                        foreach ($race as $oddtype => $detail) {
                            $txtstyle = "text-align:right; ";
                            $openOdds = $detail['openOdds'];
                            $currentOdds = $detail['currentOdds'];
                            $alert_currentOdds = ($openOdds > $currentOdds) ? " color:red; " : "";
                            $pageContent .= "<tr>";
                            $pageContent .= "<td nowrap>".$oddtype;
                            $pageContent .= "<td nowrap>".(isset($detail['name_ch']) ? $detail['name_ch'] : "");
                            $pageContent .= "<td nowrap>[".$detail['scheduleRides']."]";
                            $pageContent .= "<td nowrap style=\" $txtstyle  \">".$openOdds;
                            $pageContent .= "<td nowrap style=\" $txtstyle $alert_currentOdds \" >".$currentOdds;
                            if($raceno == "TNC"){
                                $trainer = $detail['name_ch'];
                                $pageContent .= "<td nowrap>".mapColorsHtml($trainer_pla[$venue][$trainer]);
                                $pageContent .= "<td nowrap>".calculateTotalMarks_maparray($trainer_pla[$venue][$trainer]);
                                
                            }else{
                                $jockey = $detail['name_ch'];
                                $pageContent .= "<td nowrap>".mapColorsHtml($jockey_pla[$venue][$jockey]);
                                $pageContent .= "<td nowrap>".calculateTotalMarks_maparray($jockey_pla[$venue][$jockey]);
                            }
                            
                        }
                        $pageContent .= "</table>";    
                    }
                }
            }
        }
    } //date key--------------------------------------------------------------------------------------------------------------
} //racingdata
    
echo $pageContent;

if($_GET['mail'] ){
    $emailtitle = "HRP";
	$ipAddress = substr($_SERVER['SERVER_ADDR'],-3);
	$Subject = "[horsepaper] -".$emailtitle." [AI7] ".$ipAddress;	//$date;
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
        tr.strikethrough {
            text-decoration: line-through;
        }
        .checkbox {
            margin-right: 10px; 
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
    
    document.querySelectorAll('.checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const row = this.closest('tr');
            if (this.checked) {
                row.classList.add('strikethrough');
            } else {
                row.classList.remove('strikethrough');
            }
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