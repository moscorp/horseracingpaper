<?php
include_once ('lib/func_basedata.php');
include_once ("lib/func_mailer_gmail.php");
include_once ("lib/constants.php");

$mysqli = mysqli_connect(DB_HOSTwp, DB_USERwp, DB_PASSwp, DB_NAMEwp);

$ismm = (isset($_GET['mm']) && $_GET['mm']) ? 1 : 0;
// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// SQL query to get the latest racing date and venue along with their details
$racecardsql = "SELECT * FROM RaceCard  
                 WHERE (racingdate, venue) = (
                     SELECT racingdate, venue FROM RaceCard  
                     ORDER BY racingdate DESC LIMIT 1
                 )";

// Execute the query
$racecarddata = $mysqli->query($racecardsql);

// Initialize the racecard array
$racecard = [];

// Check if any rows were returned
if ($racecarddata && mysqli_num_rows($racecarddata) > 0) {
    while ($data = mysqli_fetch_assoc($racecarddata)) {
        $raceno = $data['raceno'];
        $horseno = $data['HorseNo'];
        $racingdate = $data['racingdate'];
        $venue = $data['venue'];
        $jockeyshort = explode(' (', $data['Jockey']);

        // Store the required data in the racecard array
        $racecard[$racingdate][$venue][$raceno][$horseno]['brandno'] = $data['BrandNo'];
        $racecard[$racingdate][$venue][$raceno][$horseno]['jockey'] = $jockeyshort[0];
        $racecard[$racingdate][$venue][$raceno][$horseno]['trainer'] = $data['Trainer'];
        
        $BrandNo[] = $data['BrandNo'];
    }
} else {
    echo "No racecard data found.";
}

if (!empty($BrandNo)) {
    // Prepare the BrandNo for the SQL query
    // Use implode to create a comma-separated string
    $brandNoImploded = implode(',', array_map('intval', $BrandNo)); // Ensure all values are integers

    // Construct the SQL query safely
    $horseinfosql = "SELECT DISTINCT horseid,Pla,RC_Track_Course,Dist,G,RaceClass,Dr,Rtg,Trainer,Jockey,LBW,Win_Odds,Act_Wt,Running_Position,Finish_Time,Declar_Wt,Gear
                        FROM horseinfo WHERE horseid IN ($brandNoImploded) order by date desc";

    // Execute the horse info query
    $horseinforesult = $mysqli->query($horseinfosql);

    // Check if any horse info data was returned
    if ($horseinforesult && mysqli_num_rows($horseinforesult) > 0) {
        while ($horseinfo = mysqli_fetch_assoc($horseinforesult)) {
            $horseid = $horseinfo['horseid'];
            $pla = $horseinfo['Pla'];
            $dist = $horseinfo['Dist'];
            $jockey = $horseinfo['Jockey'];
            $trainer = $horseinfo['Trainer'];
            
            $horseinfo_jockeyinpla[$horseid][] = ($pla<4) ? $jockey : "";
            $horseinfo_distinpla[$horseid][] = ($pla<4) ? $dist : "";
        }
    } else {
        echo "No horse info data found.";
    }
} else {
    echo "No BrandNo found, cannot query horse info.";
}


print_r($horseinfo_jockeyinpla);
         


?>         