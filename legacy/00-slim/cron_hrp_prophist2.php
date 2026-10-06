<?php
error_reporting(E_ERROR | E_PARSE);

include_once ("lib/func_aii.php");
include_once ("lib/func_basedata.php");
include_once ("lib/func_mailer_gmail.php");
include_once ("lib/constants.php");

$mysqli = mysqli_connect(DB_HOSTwp, DB_USERwp, DB_PASSwp, DB_NAMEwp);

$basedata = basedata($raceingdate,$venueCode,$resultOddsType);
// print_r($basedata);

$activeMeetings = $basedata['data']['activeMeetings'];
foreach ($activeMeetings as $meeting) {
    $venueInfo = [
        'date' => $meeting['date'],
        'venueCode' => $meeting['venueCode'],
        'races' => [] // Initialize races array
    ];

    $venueCode = $meeting['venueCode'];
    // Loop through races and store their details
    foreach ($meeting['races'] as $race) {
        // Split postTime into date and time
        $postTime = new DateTime($race['postTime']);
        $formattedDate = $postTime->format('Y-m-d');
        $formattedTime = $postTime->format('H:i:s');

        $venueInfo[$venueCode][$race['no']] = [
            'date' => $formattedDate,
            'time' => $formattedTime,
            'postTime' => $postTime->format('Y-m-d H:i:s'),
        ];
    }

    $allvenue[] = $venueInfo; // Add the venue info to allvenue
}
// print_r($allvenue);

$oddsMerged = [];
$prophistcurr1st = []; // Ensure $prop is an array before use
foreach ($allvenue as $rsdata) {
    $odds = [];
    $date = $rsdata['date'];
    $venueCode = $rsdata['venueCode'];
    // echo $date.$venueCode;
    
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

$odds = $oddsMerged;
// print_r($odds);

$sql = "INSERT INTO `hrp_prophist2` (`id`, `racingdate`, `venue`, `raceno`, `horseno`, `horsecode`, `postTime`, `pre_WIN`, `pre_PLA`, `pre_oddsDropValue`, `pre_hotFavourite`, `pre_prop`, `pre_prop1`, `pre_issmaller`, 
                                    `curr_WIN`, `curr_PLA`, `curr_oddsDropValue`, `curr_hotFavourite`, `curr_prop`, 
                                    `tri20`, `ttt20`, `fft20`, `qtt20`, `finalPosition`, `rectime`) VALUES ";

$values = []; // Array to hold the values for each row
// Set the timezone to Hong Kong
date_default_timezone_set('Asia/Hong_Kong');
$currentHongKongTime = new DateTime(); // Current time in Hong Kong

foreach ($allvenue as $rsdata) {
    $date = $rsdata['date'];
    // $venueCode = $rsdata['venueCode'];

    foreach ($odds as $venue => $races) {
        foreach ($races as $raceno => $horses) {
            foreach ($horses as $horseno => $data) {
                // $postTimeString = $date . ' ' . $rsdata['races'][$raceno]['time'];
                $postTimeString = $rsdata[$venue][$raceno]['postTime'];
                
                $postTime = new DateTime($postTimeString); // Create DateTime object
                // {$rsdata['races'][$raceno]['postTime']}', 
                // echo $postTime->format('Y-m-d H:i:s') . "_" . $currentHongKongTime->format('Y-m-d H:i:s') . "<br>";
                if ($postTimeString && is_numeric($horseno) && isset($data['Curr']['WIN']) && isset($data['Pre']['preprop'])) {
                    if($postTime > $currentHongKongTime){
                        // echo $venue."/".$raceno."/".$postTime->format('Y-m-d H:i:s')."/".$postTimeString."/".$currentHongKongTime->format('Y-m-d H:i:s')."<br>";
                        // Prepare the values for each horse
                        $values[] = "(
                            NULL, 
                            '$date', 
                            '$venue', 
                            '$raceno', 
                            '$horseno', 
                            '$horsecode',
                            '{$postTime->format('Y-m-d H:i:s')}',  
                            '{$data['Pre']['WINPre']}', 
                            '{$data['Pre']['PLAPre']}', 
                            '{$data['Pre']['oddsDropValue']}', 
                            '{$data['Pre']['hotFavourite']}', 
                            '{$data['Pre']['preprop']}', 
                            '{$data['Pre']['preprop1']}', 
                            '{$data['Pre']['issmaller']}', 
                            '{$data['Curr']['WIN']}', 
                            '{$data['Curr']['PLA']}', 
                            '{$data['Curr']['oddsDropValue']}', 
                            '{$data['Curr']['hotFavourite']}', 
                            '{$data['Curr']['prop']}', 
                            '{$odds[$venue][$raceno]['TRITop']['countArray'][$horseno]}', 
                            '{$odds[$venue][$raceno]['FFTop']['countArray'][$horseno]}', 
                            '{$odds[$venue][$raceno]['TCETop']['countArray'][$horseno]}', 
                            '{$odds[$venue][$raceno]['QTTTop']['countArray'][$horseno]}', 
                            '$finalPosition',
                            '{$currentHongKongTime}'
                        )";
                    }
                }
            }
        }
    }
}

//CONVERT_TZ(NOW(), 'SYSTEM', 'Asia/Hong_Kong')
// print_r($values);

// Join all values into the SQL statement
$sql .= implode(", ", $values) . ";";
$insert = mysqli_query($mysqli, $sql);
// echo $sql . "<br>";

$logs = "cron_hrp_prophist2_".$currentHongKongTime->format('Y-m-d H:i:s');
$logsql = "	INSERT INTO logs
VALUES('','$logs',now())";
// echo $insert;
mysqli_query($mysqli, $logsql);
	

// CREATE TABLE hrp_prophist2 (
//     id INT AUTO_INCREMENT PRIMARY KEY,
//     racingdate DATE NOT NULL,
//     venue VARCHAR(10) NOT NULL,
//     raceno INT NOT NULL,
//     horseno INT NOT NULL,
//     horsecode INT NOT NULL,
//     postTime TIME NOT NULL, 
//     pre_WIN DECIMAL(10, 2),
//     pre_PLA DECIMAL(10, 2),
//     pre_oddsDropValue INT,
//     pre_hotFavourite VARCHAR(1),
//     pre_prop INT,
//     pre_prop1 INT,
//     pre_issmaller INT,
//     curr_WIN DECIMAL(10, 2),
//     curr_PLA DECIMAL(10, 2),
//     curr_oddsDropValue INT,
//     curr_hotFavourite VARCHAR(1),
//     curr_prop INT,
//     tri20 INT,
//     ttt20 INT,
//     fft20 INT,
//     qtt20 INT,
//     finalPosition INT,
//     rectime TIME,  
//     KEY (racingdate, venue, raceno, horseno,horsecode )
// );   

?>