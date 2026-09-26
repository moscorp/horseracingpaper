<?php
error_reporting(E_ERROR | E_PARSE);

include_once("lib/func_aii.php");
include_once("lib/func_basedata.php");
include_once("lib/func_mailer_gmail.php");
include_once("lib/constants.php");

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

function hasExactlyOccurrencesOf($occurrences, $checkvalue) {
    // Check if the array has exactly two elements
    if (count($occurrences) === 2) {
        // Check if all values are equal to checkvalue
        foreach ($occurrences as $value) {
            if ($value !== $checkvalue) {
                return false; // If any value is not equal to checkvalue, return false
            }
        }
        return true; // All values are equal to checkvalue, return true
    }
    return false; // Not exactly two elements
}

// Initialize variables
// $whr = "";

// $search_racingdate = $_GET['racingdate'] ?? null;
// $search_venue = $_GET['venue'] ?? null;
// $TopPredigit = $_GET['TopPredigit'] ?? null;


function load_prophist($search_racingdate,$search_venue,$horsecnt,$TopPredigit,$if1x,$if2x,$if3x,$if4x,$if5x,$if6x, $unique1x, $unique2x, $unique3x, $unique4x, $unique5x, $unique6x){
    global $mysqli;
    $racingdate = isset($search_racingdate) ? $search_racingdate : date("Y-m-d");
    // echo $racingdate."-".$search_venue."-".$TopPredigit."-".$if1x."-".$if2x."-".$if3x."-".$if4x."-".$if5x."-/-".$unique1x."-".$unique2x."-".$unique3x."-".$unique4x."-".$unique5x."<br>";
    
    // Construct the WHERE clause based on venue
    switch ($search_venue) {
        case "ST":
        case "HV":
            $whr = "AND venue='{$search_venue}'";
            break;
        case "Sx":
            $whr = "AND venue LIKE 'S%' AND venue<>'ST'";
            break;
    }
    
    $whr .= "and racingdate <> '".$racingdate."' ";
    // $whr .= $search_go_ch ? " AND go_ch='{$search_go_ch}'" : "";
    
    // Initialize variables from GET parameters
    $cnt1x = $unique1x ?? null;
    $cnt2x = $unique2x ?? null;
    $cnt3x = $unique3x ?? null;
    $cnt4x = $unique4x ?? null;
    $cnt5x = $unique5x ?? null;
    $cnt6x = $unique6x ?? null;
    $cnt7x = $unique7x ?? null;
    
    // SQL query with ORDER BY clause to sort by prewin
    $sql = "SELECT racingdate, venue, go_ch, horsecode, horsename, raceno, Distance, horseno, prewin, proppre, finalPosition 
            FROM racepropresult 
            WHERE 1 $whr 
            ORDER BY racingdate, proppre DESC
            ";
    // $sql = "SELECT *
    //         FROM racepropresult 
    //         JOIN (
    //             SELECT DISTINCT racingdate as date
    //             FROM racepropresult
    //             ORDER BY racingdate DESC
    //             LIMIT 1000
    //         ) AS recent_dates ON racingdate = recent_dates.date
    //         WHERE  1 $whr 
    //         ORDER BY racingdate, proppre DESC";
            
    // echo $sql."<br>---<br>";
    $racepropdata = $mysqli->query($sql);
    
    $tempData = []; // Temporary array to store all racepropdata
    $prophist = []; // Initialize the prophist array
    $count = 0; // Counter for how many race keys we have added
    
    // Step 1: Store the racepropdata into the temporary data array
    foreach ($racepropdata as $raceprop) {
        $racingdate = $raceprop['racingdate'];
        $venue = $raceprop['venue'];
        $raceno = $raceprop['raceno'];
        $horseno = $raceprop['horseno']; // Capture horseno
        $prewin = (float)$raceprop['prewin']; // Ensure prewin is treated as a number
        $proppre = $raceprop['proppre'];
        $finalPosition = $raceprop['finalPosition'];
    
        // Store in temporary data array
        $tempData[$racingdate.$raceno][$horseno] = [
            'prewin' => $prewin,
            'proppre' => $proppre,
            'finalPosition' => $finalPosition,
        ];
    }
    
    // Step 2: Sort each $racingdate.$raceno group by prewin
    foreach ($tempData as $raceKey => &$horses) {
        // Sort horses within the same $racingdate.$raceno group by prewin
        uasort($horses, function($a, $b) {
            return $a['prewin'] <=> $b['prewin']; // Sort in ascending order
        });
        
    }
    
    
    // Step 3: Filter the data based on the tens digit of the first proppre
    foreach ($tempData as $raceKey => $horses) {
        if ($count >= 100) {
            break; // Stop if we have already added 5 keys
        }
        // Get the first horse's proppre to check the tens digit
        $firstHorseKey = key($horses); // Get the first horses' key
        $firstHorseData = $horses[$firstHorseKey];
        $first_proppre = (int) $firstHorseData['proppre']; // Assuming proppre is an array
        $tens_digit = (int)(($first_proppre % 100) / 10); // Calculate the tens digit
    
        // Compare the tens digit with TopPredigit
        if ($tens_digit == $TopPredigit) {
            // Store the entire raceno group in prophist
            $prophist[$raceKey] = $horses;
            $count++; // Increment the counter
        }
        $prophist[$raceKey]['horsecnt'] = count($prophist[$raceKey]);
    }
    
    // Initialize occurrence tracking in prophist
    foreach ($prophist as $raceKey => $horses) {
        $occurrences = [];
        $occurrencesx2 = [];
        $occurrencesx3 = [];
        
        // Count the occurrences of each proppre
        foreach ($horses as $horseno => $data) {
            $proppreValue = $data['proppre']; 
            $tensDigit = (int)(($proppreValue % 100) / 10); // Calculate the tens digit
    
            if(is_numeric($horseno)){
                if (!isset($occurrences[$proppreValue])) {
                    $occurrences[$proppreValue] = 0; // Initialize if not set
                }
                $occurrences[$proppreValue]++; // Increment the count

                if ($occurrences[$proppreValue]>1) {
                    $occurrencesx2['tensDigit'][] = $tensDigit;
                    $occurrencesx2[$tensDigit."x"][] = $proppreValue;
                }
                // if ($occurrences[$proppreValue]>2) {
                //     $occurrencesx3['tensDigit'] = $tensDigit;
                //     $occurrencesx3['count'] = $occurrences[$proppreValue];
                // }
            }
            // $occurrences[$tensDigit . "x"]++; // Increment the count
        }
    
        // Store occurrences back in the prophist under 'occurrences'
        $prophist[$raceKey]['occurrences'] = $occurrences;
        $prophist[$raceKey]['occurrencesx2'] = $occurrencesx2;
        $prophist[$raceKey]['occurrencesx2']['tensDigit'] = array_unique($occurrencesx2['tensDigit']);
        $prophist[$raceKey]['occurrencesx3'] = $occurrencesx3;
    }
    
    // print_r($prophist);
    // die();
    
    $newProphist = []; // Initialize a new array for the modified structure
    $key1 = 1; // Start raceKey from 1
    
    
    foreach ($prophist as $raceKey => $horses) {
        $keepRace = false; // Flag to determine if we should keep this race
        $keepRaceunique = false; // Flag to determine if we should keep this race
        $uniquecount = [];
        $cnt2 = 0;
        $cnt3 = 0;
        
        // Store occurrences
        $occurrences = $horses['occurrences'] ?? []; // Retrieve occurrences if they exist
        $occurrencesx2 = $horses['occurrencesx2'] ?? []; // Retrieve occurrences if they exist
        $occurrencesx3 = $horses['occurrencesx3'] ?? []; // Retrieve occurrences if they exist
    
        if($prophist[$raceKey]['horsecnt'] <= $horsecnt){
        
            $keepRace = true; // Initialize the variable to true
            $keepRace1 = true; // Initialize the variable to true
            $keepRace2 = true; // Initialize the variable to true
            
            // Build an array to collect present digits with a count greater than 1
            $presentDigits = [];
            $tendigit_hist = "";
            $tendigit_condition = "";
            foreach ($occurrences as $proppre => $count) {
                $tensDigit = (int)(($proppre % 100) / 10); // Calculate the tens digit
                if (!in_array($tensDigit, $presentDigits) && $count > 1) {
                    $presentDigits[] = $tensDigit; // Store unique tens digits where count > 1
                }
            }
            sort($presentDigits);
            $tendigit_hist = implode('', $presentDigits);
            
            // Build the condition array based on count values
            $conditionXcnt = [];
            if ($cnt1x > 0) $conditionXcnt[] = 1;
            if ($cnt2x > 0) $conditionXcnt[] = 2;
            if ($cnt3x > 0) $conditionXcnt[] = 3;
            if ($cnt4x > 0) $conditionXcnt[] = 4;
            if ($cnt5x > 0) $conditionXcnt[] = 5;
            if ($cnt6x > 0) $conditionXcnt[] = 6;
            if ($cnt7x > 0) $conditionXcnt[] = 7;
            sort($conditionXcnt);
            $tendigit_condition = implode('', $conditionXcnt);
            
            // An array to hold counts for easy reference
            $counts = [$cnt1x, $cnt2x, $cnt3x, $cnt4x, $cnt5x, $cnt6x, $cnt7x];
            $targetX = 1;
            foreach ($counts as $cntX) {
                // Check if the count is 2 or already exists in occurrencesx2
                // echo "<br>"."--------------------------(".$cntX."-".(count($occurrencesx2[$targetX."x"]))."-".$occurrences[$proppre]."<br>";
                if ($cntX){
                    // if(!in_array($count, $occurrencesx2['tensDigit']) || !hasExactlyOccurrencesOf($occurrencesx2, $count) || ($count > $occurrencesx2['count'])) {
                    
                    if(($cntX <> count($occurrencesx2[$targetX."x"])) || !in_array($targetX, $occurrencesx2['tensDigit']) ) {   //|| $cntX <> $occurrences[$proppre]) {
                        // If $count is 2 and not in occurrencesx2 but hasExactlyOccurrencesOf returns true
                        $keepRace1 = false; 
                        break; // Exit the loop early
                    }
                    $cnt2++;
                }
                $targetX++;
            }

            
            // Check if every presentDigit has a corresponding count
            if (!empty($presentDigits) || !empty($conditionXcnt) && (count($presentDigits) == count($conditionXcnt))) {
                // echo $cnt1x."-".$cnt2x."-".$cnt3x."-".$cnt4x."-".$cnt5x."-".$tendigit_hist."-".$tendigit_condition."-".($tendigit_hist <> $tendigit_condition ? "xxxx" : "yyyy")."-".(hasExactlyOccurrencesOf($occurrencesx2, $cnt2x) ? 1 : 2)."<br>";
                // print_r($occurrences);
                // echo "<br>";
                // print_r(($occurrencesx2));
                // print_r(($occurrencesx2['tensDigit']));
                // echo "<br>";
                // print_r($occurrencesx3);
                // print_r(($occurrencesx3['tensDigit']));
                // echo "<br>";
                // print_r($presentDigits);
                // print_r($conditionXcnt);
                if($tendigit_hist <> $tendigit_condition){
                    $keepRace = false; 
                }
                // foreach ($presentDigits as $digit) {
                //     if (!in_array($digit, $conditionXcnt)) {
                //         $keepRace = false; // Set keepRace to false if any digit is not found
                //         break; // Exit the loop early if a mismatch is found
                //     }
                // }
                // foreach ($conditionXcnt as $digit) {
                //     if (!in_array($digit, $presentDigits)) {
                //         $keepRace = false; // Set keepRace to false if any digit is not found
                //         break; // Exit the loop early if a mismatch is found
                //     }
                // }
            }else{
                $keepRace = false; 
            }
            
            // Final outcome of keepRace
            // if($keepRace) {
            //     print_r($occurrences);
            //     print_r($presentDigits);
            //     print_r($conditionXcnt);
            //     echo "c".$prophist[$raceKey]['horsecnt'].$horsecnt."------------".$tendigit_hist."-".$tendigit_condition;
            //     echo "<br>";
            // }
    
            
            $count1x = 0;
            $count2x = 0;
            $count3x = 0;
            $count4x = 0;
            $count5x = 0;
            $count6x = 0;
            $count7x = 0;
            
            // Iterate over each key and count in the uniquecount array
            foreach ($uniquecount as $key => $count) {
                $tensDigit = (int)(($key % 100) / 10); // Calculate tens digit
            
                // Assign counts to respective unique variables
                switch ($tensDigit) {
                    case 1:
                        $count1x += $count; // Add to unique1x
                        break;
                    case 2:
                        $count2x += $count; // Add to unique2x
                        break;
                    case 3:
                        $count3x += $count; // Add to unique3x
                        break;
                    case 4:
                        $count4x += $count; // Add to unique4x
                        break;
                    case 5:
                        $count5x += $count; // Add to unique5x
                        break;
                    case 6:
                        $count6x += $count; // Add to unique5x
                        break; 
                    case 7:
                        $count7x += $count; // Add to unique5x
                        break; 
                    default:
                        break; // Do nothing for other cases
                }
            }
            
            if (($unique1x > 0 && $count1x > 0) ||
                ($unique2x > 0 && $count2x > 0) ||
                ($unique3x > 0 && $count3x > 0) ||
                ($unique4x > 0 && $count4x > 0) ||
                ($unique5x > 0 && $count5x > 0) ||
                ($unique6x > 0 && $count6x > 0) ||
                ($unique7x > 0 && $count7x > 0)) {
                
                $keepRaceunique = true; // Set this variable to indicate we should keep the race
            } else {
                $keepRaceunique = false; // Alternatively, you can set it to false if you want
            }
        
            // If we determined to keep the race based on occurrences
            if ($keepRace && $keepRace1 && $keepRace2){    //&& $keepRaceunique) {
                $newHorses = []; // Array to hold horses with new keys
                $key2 = 1; // Start key2 from 1 for horses
        
                // Re-add horses regardless of filtering
                foreach ($horses as $horseno => $data) {
                    if ($horseno === 'occurrences') {
                        continue; // Skip occurrences key to avoid duplication
                    }
                    $newHorses[$key2] = $data; // Assign each horse data to the new key
                    $key2++; // Increment key2
                }
        
                // Store the modified horses array with new raceKey in the newProphist array
                $newProphist[$key1] = $newHorses; // Use key1 as the new raceKey
                $newProphist[$key1]['occurrences'] = $occurrences; // Add occurrences array
                $newProphist[$key1]['uniquecount'] = $uniquecount; // Add occurrences array
                $key1++; // Increment key1 for the next race
            }
        }//if $horsecnt = 12;
    }

    foreach ($newProphist as $key => $value) {
        // Check if the 'occurrences' array is empty
        if (empty($value['occurrences'])) {
            // Remove the entry if 'occurrences' is empty
            unset($newProphist[$key]);
        }
    }

    // Optionally, reindex the array if needed
    $newProphist = array_values($newProphist);
    
    // Example of how to display or process the filtered $newProphist
    // print_r($newProphist);
    // echo "<br>";
    // die();
    
    return $newProphist;
}

// $mysqli->close();
// header('Content-Type: application/json');
// echo json_encode($prophist);

