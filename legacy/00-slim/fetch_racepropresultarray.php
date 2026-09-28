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

// Initialize variables
$whr = "";

$search_racingdate = $_GET['racingdate'] ?? null;
$search_venue = $_GET['venue'] ?? null;
$TopPredigit = $_GET['TopPredigit'] ?? null;


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

$whr .= "and racingdate <> '".$search_racingdate."' ";
// $whr .= $search_go_ch ? " AND go_ch='{$search_go_ch}'" : "";

// Initialize variables from GET parameters
$cnt1x = $_GET['if1x'] ?? null;
$cnt2x = $_GET['if2x'] ?? null;
$cnt3x = $_GET['if3x'] ?? null;
$cnt4x = $_GET['if4x'] ?? null;
$cnt5x = $_GET['if5x'] ?? null;

// SQL query with ORDER BY clause to sort by prewin
$sql = "SELECT racingdate, venue, go_ch, horsecode, horsename, raceno, Distance, horseno, prewin, proppre, finalPosition 
        FROM racepropresult 
        WHERE 1 $whr 
        ORDER BY racingdate DESC
        ";
$sql = "SELECT *
        FROM racepropresult 
        JOIN (
            SELECT DISTINCT racingdate as date
            FROM racepropresult
            ORDER BY racingdate DESC
            LIMIT 5
        ) AS recent_dates ON racingdate = recent_dates.date
        WHERE  1 $whr 
        ORDER BY racingdate, proppre DESC";
        
// echo $sql;
$racepropdata = $mysqli->query($sql);

$tempData = []; // Temporary array to store all racepropdata

$tempData = []; // Temporary array to store all racepropdata

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


$prophist = []; // Initialize the prophist array
$count = 0; // Counter for how many race keys we have added

// Step 3: Filter the data based on the tens digit of the first proppre
foreach ($tempData as $raceKey => $horses) {
    if ($count >= 5) {
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
}

// Initialize occurrence tracking in prophist
foreach ($prophist as $raceKey => $horses) {
    $occurrences = [];

    // Count the occurrences of each proppre
    foreach ($horses as $horseno => $data) {
        $proppreValue = $data['proppre']; // Assuming proppre is an array; modify as necessary to get its value

        if (!isset($occurrences[$proppreValue])) {
            $occurrences[$proppreValue] = 0; // Initialize if not set
        }
        $occurrences[$proppreValue]++; // Increment the count
    }

    // Store occurrences back in the prophist under 'occurrences'
    $prophist[$raceKey]['occurrences'] = $occurrences;
}

// If you want to see the final result with occurrences
print_r($prophist);



$mysqli->close();
header('Content-Type: application/json');
echo json_encode($prophist);

