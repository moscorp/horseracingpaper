<?php

// ini_set('max_execution_time', '3000000');
// error_reporting(E_ALL);
// error_reporting(E_ERROR | E_PARSE);

include_once ("lib/func_aii.php");
include_once ("lib/constants.php");

$mysqli = mysqli_connect(DB_HOSTwp, DB_USERwp, DB_PASSwp, DB_NAMEwp);
$oneyear = date("Y-m-d",strtotime("-1 year"));
$onemonth = date("Y-m-d",strtotime("-1 month"));
$oneweek = date("Y-m-d",strtotime("-1 week"));
$today = date("Y-m-d");
// $BrandNo = ['J218'];
 
function load_oddsdrop($today,$BrandNo){
    if (!empty($BrandNo)) {
        global $mysqli;

        $brandNoImploded = "'" . implode("','", $BrandNo) . "'";
       
        // Construct the SQL query safely
        $sql = "SELECT * FROM oddsDrop 
                WHERE date<'$today' and finalPosition 
                and horsecode IN ($brandNoImploded) 
                GROUP BY date, horsecode 
                ORDER BY horsecode, date DESC";
        // echo $sql;
        $oddsDrop_array = mysqli_query($mysqli, $sql);
        
        // Initialize the array to avoid null warnings
        $oddsDrophists = []; // Ensure this is initialized
        $oddsDrop_hist = []; // Initialize the result array
        $horseCount = []; // Initialize an array to keep track of counts for each horse ID
        
        foreach ($oddsDrop_array as $oddsDrophist) {
            $horseid = $oddsDrophist['horsecode'];
            $date = $oddsDrophist['date'];
            $oddsDropValue = $oddsDrophist['oddsDropValue'];
            $finalPosition = $oddsDrophist['finalPosition'];
        
            // Initialize the count for this horse ID if it doesn't exist
            if (!isset($horseCount[$horseid])) {
                $horseCount[$horseid] = 0;
                $oddsDrop_hist[$horseid] = [
                    'date' => $date,  // Store the date for this horse ID
                    'values' => ''    // Initialize an empty string for odds drop values
                ];
            }
        
            // Check if the count for this horse ID is less than 5
            if ($horseCount[$horseid] < 2) {
                $oddsDrop_hist[$horseid]['values'] .= $oddsDropValue . "." . ($finalPosition > 9 ? "-" : mapColorsHtml($finalPosition)) . ";";
                $horseCount[$horseid]++; // Increment the count for this horse ID
            }
        }
    }
    // print_r($oddsDrop_hist);
    // print_r($oddsDrop_after);
    // print_r($oddsDrophists);
    return $oddsDrop_hist;    
}



?>
