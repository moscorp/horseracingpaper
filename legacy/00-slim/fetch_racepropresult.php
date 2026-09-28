<?php
error_reporting(E_ERROR | E_PARSE);

include_once ("lib/func_aii.php");
include_once ("lib/func_basedata.php");
include_once ("lib/func_mailer_gmail.php");
include_once ("lib/constants.php");

$mysqli = mysqli_connect(DB_HOSTwp, DB_USERwp, DB_PASSwp, DB_NAMEwp);

function findMaxForMultiplier($array, $multiplier) {
    // Convert values to integers
    $array = array_map('intval', $array);

    // Determine the maximum value based on the multiplier
    switch ($multiplier) {
        case 1:
            return max(array_filter($array, function($value) {
                return $value >= 0 && $value <= 19;
            }));
        case 2:
            return max(array_filter($array, function($value) {
                return $value >= 20 && $value <= 29;
            }));
        case 3:
            return max(array_filter($array, function($value) {
                return $value >= 30 && $value <= 39;
            }));
        case 4:
            return max(array_filter($array, function($value) {
                return $value >= 40 && $value <= 49;
            }));
        case 5:
            return max(array_filter($array, function($value) {
                return $value >= 50 && $value <= 59;
            }));
        case 6:
            return max(array_filter($array, function($value) {
                return $value >= 60 && $value <= 69;
            }));            
        default:
            return null; // Handle invalid multipliers
    }
}


// Initialize variables
$ifxx = 1;
$if6x = 1;
$if5x = 1;
$if4x = 1;
$if3x = 1;
$if2x = 1;
$if1x = 1;
// $if3xx = 1;
$if35x = 1;
$if34x = 1;
$if33x = 1;
$if32x = 1;
$if31x = 1;
$whr = "";

$search_prop = $_GET['prop'];
$search_venue = $_GET['venue'];
$search_go_ch = $_GET['go_ch'];
$haveDigit = $_GET['haveDigit'];

switch ($search_venue){
    case "ST" :
    case "HV" :
        $whr = "and venue='".$search_venue."'";
        break;
    case "Sx" :
        $whr = "and venue like 'S%' and venue<>'ST'";
        break;
}

$whr .= $search_go_ch ? " and go_ch='".$search_go_ch."'" : "";

// $compareDigit  = "59";
// Check if 'multipliers' is set in the GET request
if (isset($_GET['multipliers'])) {
    // Retrieve the multipliers array
    $multipliers = $_GET['multipliers'];
    
    // Check if '3x' is in the multipliers array
    $is5x = (in_array('6x', $multipliers)) ? 1 : 0;
    $is5x = (in_array('5x', $multipliers)) ? 1 : 0;
    $is4x = (in_array('4x', $multipliers)) ? 1 : 0;
    $is3x = (in_array('3x', $multipliers)) ? 1 : 0;
    $is2x = (in_array('2x', $multipliers)) ? 1 : 0;
    $is1x = (in_array('1x', $multipliers)) ? 1 : 0;
    $isxx = (in_array('xx', $multipliers)) ? 1 : 0;
}

if (isset($_GET['multipliers2'])) {
    // Retrieve the multipliers array
    $multipliers2 = $_GET['multipliers2'];
    
    // Check if '3x' is in the multipliers array
    $is35x = (in_array('35x', $multipliers2)) ? 1 : 0;
    $is34x = (in_array('34x', $multipliers2)) ? 1 : 0;
    $is33x = (in_array('33x', $multipliers2)) ? 1 : 0;
    $is32x = (in_array('32x', $multipliers2)) ? 1 : 0;
    $is31x = (in_array('31x', $multipliers2)) ? 1 : 0;
    // $isxx = (in_array('3xx', $multipliers2)) ? 1 : 0;
}


// SQL query with ORDER BY clause to sort by prewin
$sql = "SELECT racingdate, venue, go_ch, horsecode, horsename, raceno, Distance, horseno, prewin, proppre, finalPosition 
        FROM racepropresult 
        where 1 $whr 
        ORDER BY prewin ASC"; // Order by prewin in ascending order
        // echo $sql;
$racepropdata = $mysqli->query($sql);

$data = []; // Initialize the data array

foreach ($racepropdata as $raceprop) {
    $racingdate = $raceprop['racingdate'];
    $venue = $raceprop['venue'];
    $go_ch = $raceprop['go_ch'];
    $raceno = $raceprop['raceno'];
    $prewin = $raceprop['prewin'];
    $proppre = $raceprop['proppre'];
    $finalPosition = $raceprop['finalPosition'];

    // Organize data into a structured array
    $data[$racingdate][$venue][$raceno]['prewin'][] = $prewin;
    $data[$racingdate][$venue][$raceno]['proppre'][] = $proppre;
    $data[$racingdate][$venue][$raceno]['finalPosition'][] = $finalPosition;
}

$output = []; // Initialize the output array
$occurrences = 1;

foreach ($data as $racingdate => $venues) {
    foreach ($venues as $venue => $races) {
        foreach ($races as $raceno => $racepropoutput) {
            // Check if the proppre contains the compare digit
            $firstvalue = reset($racepropoutput['proppre']);
            $firstdigit = (int)(($firstvalue % 100) / 10); // Extract the ten's digit
            $ifhaveDigit = 1; // Default to true
            if ($haveDigit) {
                $ifhaveDigit = in_array($haveDigit, $racepropoutput['proppre']);
            }
            $iffirstvalue = 1; // Default to true
            if ($search_prop){
                $iffirstvalue = ($firstdigit == $search_prop || $firstvalue == $search_prop) ? 1 : 0;
            }
            if($ifhaveDigit && $iffirstvalue){
                foreach ($racepropoutput['proppre'] as $proppreValue) {
                    // Check if the whole value matches or if the ten's digit matches
                    $tensDigit = (int)(($proppreValue % 100) / 10); // Extract the ten's digit
    
                    // Check for 5x multiplier (values in range 50-59)
                    $ifxx = ($isxx) ? 
                        (count(array_filter(array_count_values(array_filter($racepropoutput['proppre'], function($value) {
                            return $value >= 0 && $value <= 100; // Filter values in the range 0-100
                        })), function($count) {
                            return $count > 1; // Check if any value appears more than once
                        })) === 0 // Ensure no value appears more than once
                        ) : 1; // Default to 1 if $isxx is not true
                    // Check for 5x multiplier (values in range 50-59)
                    $if6x = ($is6x) ? 
                        (count(array_filter(array_count_values(array_filter($racepropoutput['proppre'], function($value) {
                            return $value >= 60 && $value <= 69; // Filter values in the range 50-59
                        })), function($count) {
                            return $count > 1; // Check if any value appears more than once
                        })) > 0 // Ensure there is at least one count greater than 0
                        ) : 1; // Default to 1 if $is5x is not true
                    // Check for 5x multiplier (values in range 50-59)
                    $if5x = ($is5x) ? 
                        (count(array_filter(array_count_values(array_filter($racepropoutput['proppre'], function($value) {
                            return $value >= 50 && $value <= 59; // Filter values in the range 50-59
                        })), function($count) {
                            return $count > 1; // Check if any value appears more than once
                        })) > 0 // Ensure there is at least one count greater than 0
                        ) : 1; // Default to 1 if $is5x is not true
                                        
                    // Check for 4x multiplier (values in range 40-49)
                    $if4x = ($is4x) ? 
                        (count(array_filter(array_count_values(array_filter($racepropoutput['proppre'], function($value) {
                            return $value >= 40 && $value <= 49; // Filter values in the range 40-49
                        })), function($count) {
                            return $count > 1; // Check if any value appears more than once
                        })) > 0 // Ensure there is at least one count greater than 0
                        ) : 1; // Default to 1 if $is4x is not true
                    
                    // Check for 2x multiplier (values in range 20-29)
                    $if2x = ($is2x) ? 
                        (count(array_filter(array_count_values(array_filter($racepropoutput['proppre'], function($value) {
                            return $value >= 20 && $value <= 29; // Filter values in the range 20-29
                        })), function($count) {
                            return $count > 1; // Check if any value appears more than once
                        })) > 0 // Ensure there is at least one count greater than 0
                        ) : 1; // Default to 1 if $is2x is not true
        
                    $if3x = ($is3x) ? 
                            (count(array_filter(array_count_values(array_filter($racepropoutput['proppre'], function($value) {
                                return $value >= 30 && $value <= 39; // Filter values in the range 30-39
                            })), function($count) {
                                return $count > 1; // Check if any value appears more than once
                            })) > 0 // Ensure we check if there is any count greater than 0
                            ) : 1; // Default to 1 if $is3x is not true    
                            
                    $if1x = ($is1x) ? 
                            (count(array_filter(array_count_values(array_filter($racepropoutput['proppre'], function($value) {
                                return $value >= 10 && $value <= 19; // Filter values in the range 30-39
                            })), function($count) {
                                return $count > 1; // Check if any value appears more than once
                            })) > 0 // Ensure we check if there is any count greater than 0
                            ) : 1; // Default to 1 if $is3x is not true   
                    //---------------------------------------------------------------------------------------------------------------------
                    $if35x = ($is35x) ? 
                        (count(array_filter(array_count_values(array_filter($racepropoutput['proppre'], function($value) {
                            return $value >= 50 && $value <= 59; // Filter values in the range 50-59
                        })), function($count) {
                            return $count > 2; // Check if any value appears more than once
                        })) > 0 // Ensure there is at least one count greater than 0
                        ) : 1; // Default to 1 if $is5x is not true
                    
                    // Check for 4x multiplier (values in range 40-49)
                    $if34x = ($is34x) ? 
                        (count(array_filter(array_count_values(array_filter($racepropoutput['proppre'], function($value) {
                            return $value >= 40 && $value <= 49; // Filter values in the range 40-49
                        })), function($count) {
                            return $count > 2; // Check if any value appears more than once
                        })) > 0 // Ensure there is at least one count greater than 0
                        ) : 1; // Default to 1 if $is4x is not true
                    
                    // Check for 2x multiplier (values in range 20-29)
                    $if32x = ($is32x) ? 
                        (count(array_filter(array_count_values(array_filter($racepropoutput['proppre'], function($value) {
                            return $value >= 20 && $value <= 29; // Filter values in the range 20-29
                        })), function($count) {
                            return $count > 2; // Check if any value appears more than once
                        })) > 0 // Ensure there is at least one count greater than 0
                        ) : 1; // Default to 1 if $is2x is not true
        
                    $if33x = ($is33x) ? 
                            (count(array_filter(array_count_values(array_filter($racepropoutput['proppre'], function($value) {
                                return $value >= 30 && $value <= 39; // Filter values in the range 30-39
                            })), function($count) {
                                return $count > 2; // Check if any value appears more than once
                            })) > 0 // Ensure we check if there is any count greater than 0
                            ) : 1; // Default to 1 if $is3x is not true    
                            
                    $if31x = ($is31x) ? 
                            (count(array_filter(array_count_values(array_filter($racepropoutput['proppre'], function($value) {
                                return $value >= 10 && $value <= 19; // Filter values in the range 30-39
                            })), function($count) {
                                return $count > 2; // Check if any value appears more than once
                            })) > 0 // Ensure we check if there is any count greater than 0
                            ) : 1; // Default to 1 if $is3x is not true   
                    //---------------------------------------------------------------------------------------------------------------------  
                            
                    $chk_samerace = 1; // Assume true initially
                    foreach ($output as $entry) {
                        if ($entry['racingdate'] == $racingdate && 
                            $entry['venue'] == $venue && 
                            $entry['raceno'] == $raceno) {
                            $chk_samerace = 0; // Found a match, set to false
                            break; // No need to continue checking
                        }
                    }
                    // echo $ifhaveDigit."-".$search_prop;
                    // if ($proppreValue == $search_prop || $tensDigit == $search_prop ) {
                        // echo $raceno.$occurrences.$proppreValue."<br>";
                        if($chk_samerace && $if5x && $if5x && $if4x && $if3x && $if2x && $if1x && $ifxx && $if35x && $if34x && $if33x && $if32x && $if31x ){
                            $output[$occurrences] = [
                                'racingdate' => $racingdate,
                                'venue' => $venue,
                                'raceno' => $raceno,
                                'prewin' => $racepropoutput['prewin'],
                                'proppre' => $racepropoutput['proppre'],
                                'finalPosition' => $racepropoutput['finalPosition'],
                                'max2x' => findMaxForMultiplier($racepropoutput['proppre'], 2),
                                'max3x' => findMaxForMultiplier($racepropoutput['proppre'], 3),
                                'max4x' => findMaxForMultiplier($racepropoutput['proppre'], 4)
                            ];
                            $occurrences++;
                        }
                    // }
                }
            }
        }
    }
}



$mysqli->close();
header('Content-Type: application/json');
echo json_encode($output);