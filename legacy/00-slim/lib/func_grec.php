<?php

error_reporting(E_ALL);
// ini_set("allow_url_fopen", 1);
// header('Content-Type: text/html; charset=utf-8');
// header('Content-type: application/json');
// require_once('lib/mossql.php');	

function Grec($horseid){
    require_once('lib/func.php');	
    require_once("lib/constants.php");
    $mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    mysqli_set_charset($mysqli, "utf8mb4");
    // Check connection
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        exit();
    }
    // $horseid = "G123";
    $horseinfo = "select Pla,G from horseinfo where horseid='$horseid'";
    $horseinfo_array = mysqli_query($mysqli, $horseinfo);
    $rowcount=mysqli_num_rows($horseinfo_array);

    while($row = mysqli_fetch_array($horseinfo_array)){
        $Pla= $row['Pla'];
        $G= $row['G'];
        // $Gs = explode("/", $G);
        // $Gs[0]  $Gs[1]
        // echo $Gs[0];
        if ($Pla <= 3) {
            $venue[$G]++;
            // if($G) $venue[$G]++;
            // $result[] = $venue . $venue[$Gs];
            $result[] = $G.$venue[$G];
        } else {
            $venue1[$G.'-']++;
            // if($G) $venue1[$G]++;
            // $result[] = $char . (-1 * $places[$venue]);
            $result[] = $G.(-1 * $venue1[$G]);
        }
    }

    // echo implode('', $venue);
    // echo implode('', $result);
    // print_r($venue);
    // print_r($venue1);
    $new = array_merge($venue, $venue1);
    // print_r($new);
    // Convert the counts back to the desired string format
    $output = '';
    foreach ($new as $key => $count) {
        if ($output !== '') {
            $output .= '.';
        }
        if ($count < 0) {
            $output .= $key . $count;
        } else {
            $output .= $key . $count;
        }
    }


    return "[".$rowcount."]".$output;
}

?>
