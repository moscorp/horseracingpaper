<?php
// set_time_limit(3000); // Set the time limit to 300 seconds (5 minutes)

require_once("lib/constants.php");
require_once('simplehtmldom_1_9_1/simple_html_dom.php');

$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$cnt = 0;
$msg = 'ai-horseinfo-updatestart';
$insert = "INSERT INTO logs VALUES(0, ?, now())";
$stmt = $mysqli->prepare($insert);
$stmt->bind_param("s", $msg);
$stmt->execute();
$stmt->close();

//--------------------------------------------------------------------
$horseinfo_saved = array();
$sql = "SELECT * FROM horseinfo";
$horseinfo_saved_array = mysqli_query($mysqli, $sql);
while ($row = mysqli_fetch_array($horseinfo_saved_array)) {
    $horseinfo_saved[] = $row['horseid'] . $row['RaceIndex'];
}

//--------------------------------------------------------------------
$horseinfo_array = array();
$racecard_sql = "SELECT horsename, BrandNo FROM RaceCard WHERE SUBSTRING(BrandNo,-1) REGEXP '^[0-9]+$' GROUP BY BrandNo";


$racecard_sql = "
        SELECT rc.horseno, rc.horsename, rc.BrandNo
        FROM RaceCard rc
        JOIN (
            SELECT MAX(racingdate) AS max_date
            FROM RaceCard
        ) t ON rc.racingdate = t.max_date ";
echo $racecard_sql;        
$racecard_array = mysqli_query($mysqli, $racecard_sql);       
while ($racecard = mysqli_fetch_assoc($racecard_array)) {
    $horseid = $racecard['BrandNo'];
    $horsename = $racecard['horsename'];

    // Save the HTML file
    $htmlFile = 'horsehtml/' . $horseid . '.html';
    // echo $htmlFile."<br>";
    $htmlContent = file_get_contents('https://racing.hkjc.com/racing/information/Chinese/Horse/Horse.aspx?HorseNo=' . $horseid);
    file_put_contents($htmlFile, $htmlContent);

    // Parse the HTML using Simple HTML DOM
    // require_once('simplehtmldom_1_9_1/simple_html_dom.php');
    $html = file_get_html($htmlFile);

    // Perform the data extraction
    $rowData = array();
    $tableRows = $html->find('table.bigborder tr');

    foreach ($tableRows as $row) {
        $rowCells = $row->find('td');
        $cellData = array();

        foreach ($rowCells as $cell) {
            $cellValue = '';

            if ($cell->find('a', 0)) {
                $cellValue = $cell->find('a', 0)->innertext;
            } else {
                $cellValue = trim(str_replace(['<span class="htable_eng_text">', '</span>'], '', $cell->plaintext));
            }
            $cellData[] = $cellValue;
        }
        $rowData[] = $cellData;
    }
    
    $insert_horseinfo = "";

    // Output the parsed data
    foreach ($rowData as $rowdetail) {
        // echo implode(', ', $row) . PHP_EOL;
            if (is_numeric($rowdetail['0'])) {
                // echo "<br>".$rowdetail['0'] . '-'. $rowdetail['1'] . '-'. $rowdetail['2']; 
                $dateconvert = date_parse_from_format("d/m/y", $rowdetail['2']);
                $date = $dateconvert['year']."-".$dateconvert['month']."-".$dateconvert['day'];
    
                if (!in_array($horseid . $rowdetail['0'], $horseinfo_saved) && $horseid) {
                    $horseid_raceindex = trim($horseid.$rowdetail['0']);
                    $insert_horseinfo .= "(0, 
                        '".$horseid."', 
                        '".$horsename."', 
                        '".(isset($rowdetail['0']) ? $rowdetail['0'] : '')."', 
                        '".(isset($rowdetail['1']) ? $rowdetail['1'] : '')."', 
                        '".$date."', 
                        '".(isset($rowdetail['3']) ? $rowdetail['3'] : '')."', 
                        '".(isset($rowdetail['4']) ? $rowdetail['4'] : '')."', 
                        '".(isset($rowdetail['5']) ? $rowdetail['5'] : '')."', 
                        '".(isset($rowdetail['6']) ? $rowdetail['6'] : '')."', 
                        '".(isset($rowdetail['7']) ? $rowdetail['7'] : '')."', 
                        '".(isset($rowdetail['8']) ? $rowdetail['8'] : '')."', 
                        '".(isset($rowdetail['9']) ? $rowdetail['9'] : '')."', 
                        '".(isset($rowdetail['10']) ? $rowdetail['10'] : '')."', 
                        '".(isset($rowdetail['11']) ? $rowdetail['11'] : '')."', 
                        '".(isset($rowdetail['12']) ? $rowdetail['12'] : '')."', 
                        '".(isset($rowdetail['13']) ? $rowdetail['13'] : '')."', 
                        '".(isset($rowdetail['14']) ? $rowdetail['14'] : '')."', 
                        '".(isset($rowdetail['15']) ? $rowdetail['15'] : '')."', 
                        '".(isset($rowdetail['16']) ? $rowdetail['16'] : '')."', 
                        '".(isset($rowdetail['17']) ? $rowdetail['17'] : '')."', 
                        now()),";
                }
            }
    }
    
    $insert_horseinfo = rtrim($insert_horseinfo, ",");
    if (!empty($insert_horseinfo)) {
        $sql = "INSERT INTO `horseinfo` (`id`, `horseid`, `horsename`, `RaceIndex`, `Pla`, `Date`, `RC_Track_Course`, `Dist`, `G`, `RaceClass`, `Dr`, `Rtg`, `Trainer`, `Jockey`, `LBW`, `Win_Odds`, `Act_Wt`, `Running_Position`, `Finish_Time`, `Declar_Wt`, `Gear`, `rectime`) 
            VALUES ".$insert_horseinfo; 
        echo $sql;
        mysqli_query($mysqli, $sql);
        $error_message = mysqli_error($mysqli);
        if($error_message == ""){
            echo "Done.<br>";
            $cnt++;
        }else{
            echo "<u>Query Failed: ".$error_message."<u><br>";
        }
    }
}

//--------------------------------------------------------------------
$msg = 'ai-horseinfo-'.$cnt;
$insert = "	INSERT INTO logs
VALUES(0,'$msg',now())";
echo $insert;
mysqli_query($mysqli, $insert);
//--------------------------------------------------------------------

?>