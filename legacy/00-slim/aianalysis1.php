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
        $history[$venue][$raceno][$proporder] = [
            'proporder' => $proporder,
            'proppre' => $proppre,
            'finalPosition' => $finalPosition,
        ];

        // Initialize counts and max/min values
        foreach (['6', '5', '4', '3', '2', '1'] as $level) {
            if (!isset($history[$venue][$raceno]["{$level}xcnt"])) {
                $history[$venue][$raceno]["{$level}xcnt"] = 0;
                $history[$venue][$raceno]["{$level}xmax"] = null;
                $history[$venue][$raceno]["{$level}xmin"] = null;
            }
        }
        
        // Initialize biggest and smallest
        if (!isset($history[$venue][$raceno]['biggest'])) {
            $history[$venue][$raceno]['biggest'] = null;
        }
        if (!isset($history[$venue][$raceno]['smallest'])) {
            $history[$venue][$raceno]['smallest'] = null;
        }
        if (!isset($history[$venue][$raceno]['propTop'])) {
            $history[$venue][$raceno]['propTop'] = null;
        }
        if (!isset($history[$venue][$raceno]['propBottom'])) {
            $history[$venue][$raceno]['propBottom'] = null;
        }
        
        // Count occurrences based on proppre values
        if ($proppre >= 60) {
            $history[$venue][$raceno]['6xcnt']++;
            $history[$venue][$raceno]['6xmax'] = ($history[$venue][$raceno]['6xmax'] === null) ? $proppre : max($history[$venue][$raceno]['6xmax'], $proppre);
            $history[$venue][$raceno]['6xmin'] = ($history[$venue][$raceno]['6xmin'] === null) ? $proppre : min($history[$venue][$raceno]['6xmin'], $proppre);
        } elseif ($proppre >= 50) {
            $history[$venue][$raceno]['5xcnt']++;
            $history[$venue][$raceno]['5xmax'] = ($history[$venue][$raceno]['5xmax'] === null) ? $proppre : max($history[$venue][$raceno]['5xmax'], $proppre);
            $history[$venue][$raceno]['5xmin'] = ($history[$venue][$raceno]['5xmin'] === null) ? $proppre : min($history[$venue][$raceno]['5xmin'], $proppre);
        } elseif ($proppre >= 40) {
            $history[$venue][$raceno]['4xcnt']++;
            $history[$venue][$raceno]['4xmax'] = ($history[$venue][$raceno]['4xmax'] === null) ? $proppre : max($history[$venue][$raceno]['4xmax'], $proppre);
            $history[$venue][$raceno]['4xmin'] = ($history[$venue][$raceno]['4xmin'] === null) ? $proppre : min($history[$venue][$raceno]['4xmin'], $proppre);
        } elseif ($proppre >= 30) {
            $history[$venue][$raceno]['3xcnt']++;
            $history[$venue][$raceno]['3xmax'] = ($history[$venue][$raceno]['3xmax'] === null) ? $proppre : max($history[$venue][$raceno]['3xmax'], $proppre);
            $history[$venue][$raceno]['3xmin'] = ($history[$venue][$raceno]['3xmin'] === null) ? $proppre : min($history[$venue][$raceno]['3xmin'], $proppre);
        } elseif ($proppre >= 20) {
            $history[$venue][$raceno]['2xcnt']++;
            $history[$venue][$raceno]['2xmax'] = ($history[$venue][$raceno]['2xmax'] === null) ? $proppre : max($history[$venue][$raceno]['2xmax'], $proppre);
            $history[$venue][$raceno]['2xmin'] = ($history[$venue][$raceno]['2xmin'] === null) ? $proppre : min($history[$venue][$raceno]['2xmin'], $proppre);
        } elseif ($proppre >= 1) {
            $history[$venue][$raceno]['1xcnt']++;
            $history[$venue][$raceno]['1xmax'] = ($history[$venue][$raceno]['1xmax'] === null) ? $proppre : max($history[$venue][$raceno]['1xmax'], $proppre);
            $history[$venue][$raceno]['1xmin'] = ($history[$venue][$raceno]['1xmin'] === null) ? $proppre : min($history[$venue][$raceno]['1xmin'], $proppre);
        }
        
        // Update biggest and smallest values
        if ($history[$venue][$raceno]['biggest'] === null || $proppre > $history[$venue][$raceno]['biggest']) {
            $history[$venue][$raceno]['biggest'] = $proppre;
        }
        if ($history[$venue][$raceno]['smallest'] === null || $proppre < $history[$venue][$raceno]['smallest']) {
            $history[$venue][$raceno]['smallest'] = $proppre;
        }
        // Update propTop and propBottom
        if ($history[$venue][$raceno]['propTop'] === null) {
            $history[$venue][$raceno]['propTop'] = $proppre; // First proppre encountered
        }
        $history[$venue][$raceno]['propBottom'] = $proppre; // Last proppre encountered

        // Initialize propprecnt and increment
        if (!isset($history[$venue][$raceno]['propprecnt'])) {
            $history[$venue][$raceno]['propprecnt'] = [];
        }
        if (!isset($history[$venue][$raceno]['propprecnt'][$proppre])) {
            $history[$venue][$raceno]['propprecnt'][$proppre] = 0;
        }

        $history[$venue][$raceno]['propprecnt'][$proppre]++;
        
    }
}

foreach ($history as $venueKey => $venueData) {
    foreach ($venueData as $racenoKey => $proporderData) {
        //------------------------------------------------------------------------------------
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
        $history[$venueKey][$racenoKey]['xCounts'] = $summaryCounts;
        //------------------------------------------------------------------------------------
        // Loop through each horse's data
        foreach ($proporderData as $proporder => $data) {
            $proppre = $data['proppre'];

            // Initialize the count for the proppre if not already set
            if (!isset($history[$venueKey][$racenoKey]['propprecnt'][$proppre]) && is_numeric($proporder) && $proporder > 0) {
                $history[$venueKey][$racenoKey]['propprecnt'][$proppre] = 0;
            }

            if (is_numeric($proporder) && $proporder > 0) {
                //------------------------------------------------------------------------------------
                // Set the same count for this proporder
                $history[$venueKey][$racenoKey][$proporder]['samecnt'] = $history[$venueKey][$racenoKey]['propprecnt'][$proppre];
                //------------------------------------------------------------------------------------
                // Store the same position for this proporder
                $position = $historytemp[$venueKey][$racenoKey]['postemp'][$proppre]++;
                $history[$venueKey][$racenoKey][$proporder]['samepos'] = $position + 1; // Store the position (1st, 2nd, etc.)
                //------------------------------------------------------------------------------------
                $history[$venueKey][$racenoKey][$proporder]['min2inside3'] = 0;
                if ($proporder > 2) {
                    $last1proporder = $proporder - 1;
                    $last2proporder = $proporder - 2;

                    // Ensure last orders are set and accessible
                    if (isset($history[$venueKey][$racenoKey][$last1proporder]) && isset($history[$venueKey][$racenoKey][$last2proporder])) {
                        $temp1 = $history[$venueKey][$racenoKey][$last1proporder]['proppre'];
                        $temp2 = $history[$venueKey][$racenoKey][$last2proporder]['proppre'];

                        $last1samecnt = $history[$venueKey][$racenoKey][$last1proporder]['samecnt'];
                        $last2samecnt = $history[$venueKey][$racenoKey][$last2proporder]['samecnt'];

                        // Check conditions for ismin2
                        if ($temp2 >= 30 && $temp2 < 40 && 
                            $temp1 >= 10 && $temp1 < 30 &&
                            $proppre >= 30 && $proppre < 50) {
                                $history[$venueKey][$racenoKey][$last1proporder]['min2inside3'] = 1;
                        } else {
                            $history[$venueKey][$racenoKey][$last1proporder]['min2inside3'] = 0;//$temp2.$temp1.$proppre;
                        }
                    }
                } else {
                    // For proporders 1 and 2, set ismin2 to 0
                    // if (isset($history[$venueKey][$racenoKey][$last1proporder])) {
                        $history[$venueKey][$racenoKey][$proporder]['min2inside3'] = 0;
                    // }
                }
                //------------------------------------------------------------------------------------
                
            }
            if (is_numeric($proporder)) {
                $history[$venueKey][$racenoKey][$proporder]['propprecnts'] = $history[$venueKey][$racenoKey]['propprecnt'][$proppre];
            }
        }
    }
}


print_r($history);


?>
