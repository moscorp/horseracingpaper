<?php
error_reporting(E_ERROR | E_PARSE);

include_once ("lib/func_aii.php");
include_once ("lib/func_basedata.php");
include_once ("lib/func_mailer_gmail.php");
include_once ("lib/constants.php");

$mysqli = mysqli_connect(DB_HOSTwp, DB_USERwp, DB_PASSwp, DB_NAMEwp);
// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
// $whr = "and racingdate='2025-06-08' and venue='ST' and raceno='10' ";
$whr = "and racingdate='2025-06-14' ";//and venue='ST' and raceno='10' ";
//---------------------------------------------------------------------------------------------------------------------------------------
$query = "SELECT * FROM racepropresult WHERE 1 $whr
          ORDER BY venue, raceno, prewin, prepla ASC";
// echo $query;          
$datas = mysqli_query($mysqli, $query);    

$history = [];
$propOrderCounter = []; // Initialize an array to keep track of prop order for each raceno
if (mysqli_num_rows($datas) > 0) {
    // First, gather the data into the history array
    while ($data = mysqli_fetch_assoc($datas)) {
        $racingdate = $data['racingdate'];
        $venue = $data['venue'];
        $raceno = $data['raceno'];
        $horseno = $data['horseno'];
        $proppre = $data['proppre'];
        $finalPosition = $data['finalPosition'];

        // Initialize or increment the prop order counter for the current raceno
        $propOrderCounter[$venue][$raceno] = ($propOrderCounter[$venue][$raceno] ?? 0) + 1;
        $proporder = $propOrderCounter[$venue][$raceno]; // Get the current prop order

        // Store the data in the history array
        $history[$racingdate][$venue][$raceno][$proporder] = [
            'proporder' => $proporder,
            'proppre' => $proppre,
            'finalPosition' => $finalPosition,
        ];

        // Initialize counts and max/min values
        foreach (['6', '5', '4', '3', '2', '1'] as $level) {
            if (!isset($history[$racingdate][$venue][$raceno]["{$level}xcnt"])) {
                $history[$racingdate][$venue][$raceno]["{$level}xcnt"] = 0;
                $history[$racingdate][$venue][$raceno]["{$level}xmax"] = null;
                $history[$racingdate][$venue][$raceno]["{$level}xmin"] = null;
            }
        }
        
        // Initialize biggest and smallest
        if (!isset($history[$racingdate][$venue][$raceno]['biggest'])) {
            $history[$racingdate][$venue][$raceno]['biggest'] = null;
        }
        if (!isset($history[$racingdate][$venue][$raceno]['smallest'])) {
            $history[$racingdate][$venue][$raceno]['smallest'] = null;
        }
        if (!isset($history[$racingdate][$venue][$raceno]['propTop'])) {
            $history[$racingdate][$venue][$raceno]['propTop'] = null;
        }
        if (!isset($history[$racingdate][$venue][$raceno]['propBottom'])) {
            $history[$racingdate][$venue][$raceno]['propBottom'] = null;
        }
        
        // Count occurrences based on proppre values
        if ($proppre >= 60) {
            $history[$racingdate][$venue][$raceno]['6xcnt']++;
            $history[$racingdate][$venue][$raceno]['6xmax'] = ($history[$racingdate][$venue][$raceno]['6xmax'] === null) ? $proppre : max($history[$racingdate][$venue][$raceno]['6xmax'], $proppre);
            $history[$racingdate][$venue][$raceno]['6xmin'] = ($history[$racingdate][$venue][$raceno]['6xmin'] === null) ? $proppre : min($history[$racingdate][$venue][$raceno]['6xmin'], $proppre);
        } elseif ($proppre >= 50) {
            $history[$racingdate][$venue][$raceno]['5xcnt']++;
            $history[$racingdate][$venue][$raceno]['5xmax'] = ($history[$racingdate][$venue][$raceno]['5xmax'] === null) ? $proppre : max($history[$racingdate][$venue][$raceno]['5xmax'], $proppre);
            $history[$racingdate][$venue][$raceno]['5xmin'] = ($history[$racingdate][$venue][$raceno]['5xmin'] === null) ? $proppre : min($history[$racingdate][$venue][$raceno]['5xmin'], $proppre);
        } elseif ($proppre >= 40) {
            $history[$racingdate][$venue][$raceno]['4xcnt']++;
            $history[$racingdate][$venue][$raceno]['4xmax'] = ($history[$racingdate][$venue][$raceno]['4xmax'] === null) ? $proppre : max($history[$racingdate][$venue][$raceno]['4xmax'], $proppre);
            $history[$racingdate][$venue][$raceno]['4xmin'] = ($history[$racingdate][$venue][$raceno]['4xmin'] === null) ? $proppre : min($history[$racingdate][$venue][$raceno]['4xmin'], $proppre);
        } elseif ($proppre >= 30) {
            $history[$racingdate][$venue][$raceno]['3xcnt']++;
            $history[$racingdate][$venue][$raceno]['3xmax'] = ($history[$racingdate][$venue][$raceno]['3xmax'] === null) ? $proppre : max($history[$racingdate][$venue][$raceno]['3xmax'], $proppre);
            $history[$racingdate][$venue][$raceno]['3xmin'] = ($history[$racingdate][$venue][$raceno]['3xmin'] === null) ? $proppre : min($history[$racingdate][$venue][$raceno]['3xmin'], $proppre);
        } elseif ($proppre >= 20) {
            $history[$racingdate][$venue][$raceno]['2xcnt']++;
            $history[$racingdate][$venue][$raceno]['2xmax'] = ($history[$racingdate][$venue][$raceno]['2xmax'] === null) ? $proppre : max($history[$racingdate][$venue][$raceno]['2xmax'], $proppre);
            $history[$racingdate][$venue][$raceno]['2xmin'] = ($history[$racingdate][$venue][$raceno]['2xmin'] === null) ? $proppre : min($history[$racingdate][$venue][$raceno]['2xmin'], $proppre);
        } elseif ($proppre >= 1) {
            $history[$racingdate][$venue][$raceno]['1xcnt']++;
            $history[$racingdate][$venue][$raceno]['1xmax'] = ($history[$racingdate][$venue][$raceno]['1xmax'] === null) ? $proppre : max($history[$racingdate][$venue][$raceno]['1xmax'], $proppre);
            $history[$racingdate][$venue][$raceno]['1xmin'] = ($history[$racingdate][$venue][$raceno]['1xmin'] === null) ? $proppre : min($history[$racingdate][$venue][$raceno]['1xmin'], $proppre);
        }
        
        // Update biggest and smallest values
        if ($history[$racingdate][$venue][$raceno]['biggest'] === null || $proppre > $history[$racingdate][$venue][$raceno]['biggest']) {
            $history[$racingdate][$venue][$raceno]['biggest'] = $proppre;
        }
        if ($history[$racingdate][$venue][$raceno]['smallest'] === null || $proppre < $history[$racingdate][$venue][$raceno]['smallest']) {
            $history[$racingdate][$venue][$raceno]['smallest'] = $proppre;
        }

        // Update propTop and propBottom
        if ($history[$racingdate][$venue][$raceno]['propTop'] === null) {
            $history[$racingdate][$venue][$raceno]['propTop'] = $proppre; // First proppre encountered
        }
        $history[$racingdate][$venue][$raceno]['propBottom'] = $proppre; // Last proppre encountered

        // Initialize propprecnt and increment
        if (!isset($history[$racingdate][$venue][$raceno]['propprecnt'])) {
            $history[$racingdate][$venue][$raceno]['propprecnt'] = [];
        }
        if (!isset($history[$racingdate][$venue][$raceno]['propprecnt'][$proppre])) {
            $history[$racingdate][$venue][$raceno]['propprecnt'][$proppre] = 0;
        }

        $history[$racingdate][$venue][$raceno]['propprecnt'][$proppre]++;
        
    }
}

foreach ($history as $racingdate => $venueData) {
    foreach ($venueData as $venueKey => $racenoData) {
        foreach ($racenoData as $racenoKey => $proporderData) {
            // Initialize summary counts
            $summaryCounts = [
                'propprecnt1x' => [],
                'propprecnt2x' => [],
                'propprecnt3x' => [],
                'propprecnt4x' => [],
                'propprecnt5x' => [],
                'propprecnt6x' => [],
            ];

            // Loop through propprecnt array
            if (isset($proporderData['propprecnt'])) {
                foreach ($proporderData['propprecnt'] as $proppre => $count) {
                    if ($count > 1) {
                        if ($proppre >= 10 && $proppre < 20) {
                            $summaryCounts['propprecnt1x'][] = $proppre;
                        } elseif ($proppre >= 20 && $proppre < 30) {
                            $summaryCounts['propprecnt2x'][] = $proppre;
                        } elseif ($proppre >= 30 && $proppre < 40) {
                            $summaryCounts['propprecnt3x'][] = $proppre;
                        } elseif ($proppre >= 40 && $proppre < 50) {
                            $summaryCounts['propprecnt4x'][] = $proppre;
                        } elseif ($proppre >= 50 && $proppre < 60) {
                            $summaryCounts['propprecnt5x'][] = $proppre;
                        } elseif ($proppre >= 60) {
                            $summaryCounts['propprecnt6x'][] = $proppre;
                        }
                    }
                }
            }

            // Store the summary in the history array
            $history[$racingdate][$venueKey][$racenoKey]['xCounts'] = $summaryCounts;

            // Loop through each horse's data
            foreach ($proporderData as $proporder => $data) {
                $proppre = $data['proppre'];

                // Initialize the count for the proppre if not already set
                if (!isset($history[$racingdate][$venueKey][$racenoKey]['propprecnt'][$proppre]) && is_numeric($proporder) && $proporder > 0) {
                    $history[$racingdate][$venueKey][$racenoKey]['propprecnt'][$proppre] = 0;
                }

                if (is_numeric($proporder) && $proporder > 0) {
                    // Set the same count for this proporder
                    $history[$racingdate][$venueKey][$racenoKey][$proporder]['samecnt'] = $history[$racingdate][$venueKey][$racenoKey]['propprecnt'][$proppre];

                    // Store the same position for this proporder
                    $position = $historytemp[$racingdate][$venueKey][$racenoKey]['postemp'][$proppre]++;
                    $history[$racingdate][$venueKey][$racenoKey][$proporder]['samepos'] = $position + 1; // Store the position (1st, 2nd, etc.)

                    $history[$racingdate][$venueKey][$racenoKey][$proporder]['min2inside3'] = 0;
                    if ($proporder > 2) {
                        $last1proporder = $proporder - 1;
                        $last2proporder = $proporder - 2;

                        // Ensure last orders are set and accessible
                        if (isset($history[$racingdate][$venueKey][$racenoKey][$last1proporder]) && isset($history[$racingdate][$venueKey][$racenoKey][$last2proporder])) {
                            $temp1 = $history[$racingdate][$venueKey][$racenoKey][$last1proporder]['proppre'];
                            $temp2 = $history[$racingdate][$venueKey][$racenoKey][$last2proporder]['proppre'];

                            $last1samecnt = $history[$racingdate][$venueKey][$racenoKey][$last1proporder]['samecnt'];
                            $last2samecnt = $history[$racingdate][$venueKey][$racenoKey][$last2proporder]['samecnt'];

                            // Check conditions for min2inside3
                            if ($temp2 >= 30 && $temp2 < 40 && 
                                $temp1 >= 10 && $temp1 < 30 &&
                                $proppre >= 30 && $proppre < 50) {
                                    $history[$racingdate][$venueKey][$racenoKey][$last1proporder]['min2inside3'] = 1;
                            } else {
                                $history[$racingdate][$venueKey][$racenoKey][$last1proporder]['min2inside3'] = 0;
                            }
                        }
                    } else {
                        // For proporders 1 and 2, set min2inside3 to 0
                        $history[$racingdate][$venueKey][$racenoKey][$proporder]['min2inside3'] = 0;
                    }
                }
                
                if (is_numeric($proporder)) {
                    $history[$racingdate][$venueKey][$racenoKey][$proporder]['propprecnts'] = $history[$racingdate][$venueKey][$racenoKey]['propprecnt'][$proppre];
                }
            }
        }
    }
}


print_r($history);


?>
