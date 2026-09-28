<?php

ini_set('max_execution_time', '3000000');
error_reporting(E_ALL);
// ini_set("allow_url_fopen", 1);
header('Content-Type: text/html; charset=utf-8');
// require_once('lib/mossql.php');	
require_once('lib/func.php');	
require_once("lib/func_mailer_gmail.php");
require_once('simplehtmldom_1_9_1/simple_html_dom.php');

require_once("lib/constants.php");
$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
//--------------------------------------------------------------------
// $horseid = "E079";
//--------------------------------------------------------------------
// $horseinfo_sql = "select * from horseinfo where 1";
// $horseinfo_array = mysqli_query($mysqli, $horseinfo_sql);
// $horesinfo_rec = array();
// while($row = mysqli_fetch_array($horseinfo_array)){
//     array_push($horesinfo_rec,$row['horseid'].$row['RaceIndex']);
// }
// print_r($horesinfo_rec);
//--------------------------------------------------------------------
$cnt=0;
//--------------------------------------------------------------------
$msg = 'ai-horseinfo-updatestart';
$insert = "	INSERT INTO logs
VALUES(0,$msg,now())";
echo $insert;
mysqli_query($mysqli, $insert);
//--------------------------------------------------------------------		

$racecard_sql = "select racingdate from RaceCard where 1 order by racingdate desc limit 1";
// echo $racecard_sql;
$racecard_array = mysqli_query($mysqli, $racecard_sql);
$row = mysqli_fetch_assoc($racecard_array);
// echo "--".$row['racingdate'];
$whr = " and racingdate='".$row['racingdate']."' ";//limit 2";

//--------------------------------------------------------------------
$sql = "select * from horseinfo where 1";   //horseid='".$horseno."' and RaceIndex='".$rowdetail['0']."' limit 1" ;
// echo $sql."<br>"; count(*) as cnt
$horseinfo_saved_array= mysqli_query($mysqli, $sql);
// $nofrec = mysqli_num_rows($horseinfo_saved);
while($row = mysqli_fetch_array($horseinfo_saved_array)){
    $horseinfo_saved[] = $row['horseid'].$row['RaceIndex'];
}
// print_r($horseinfo_saved);
// exit(1);
//--------------------------------------------------------------------
$horseinfo_array = array();
$racecard_sql = "select horsename,BrandNo from RaceCard where SUBSTRING(BrandNo,-1) REGEXP '^[0-9]+$' group by BrandNo";//.$whr;
// $racecard_sql = "select BrandNo from RaceCard where SUBSTRING(BrandNo,-1) REGEXP '^[0-9]+$' and racingdate>'2023-09-24' group by BrandNo";
// $racecard_array = mysqli_query($mysqli, $racecard_sql);
// echo ">".mysqli_num_rows($racecard_array)."<br>";

$sql = "SELECT rc.raceno, rc.horseno, rc.horsename, rc.BrandNo
        FROM RaceCard rc
        JOIN (
            SELECT MAX(racingdate) AS max_date
            FROM RaceCard
        ) t ON rc.racingdate = t.max_date
        WHERE SUBSTRING(BrandNo, -1) REGEXP '^[0-9]+$'
        GROUP BY rc.BrandNo";
$racecard_array = mysqli_query($mysqli, $sql);
$racecard = array();
while ($racecard = mysqli_fetch_assoc($racecard_array)) {
    // $racecard[] = $row;
    // echo $row['raceno'].$row['horsename'].$row['BrandNo']."<br>";
// }
// print_r($racecard);
// exit(2);

// while($racecard = mysqli_fetch_array($racecard_array)){
    $horseid = $racecard['BrandNo'];
    $horsename = $racecard['horsename'];
    // echo $horseid."<br>";
// }
// print_r($racecard);

    // $horseinfo_sql = "select * from horseinfo where horseid=".$horseid;
    // if($horseinfo_array = mysqli_query($mysqli, $horseinfo_sql)){
    //     $horesinfo_rec = array();
    //     while($row = mysqli_fetch_array($horseinfo_array)){
    //         array_push($horesinfo_rec,$row['horseid'].$row['RaceIndex']);
    //     }
    // }

    $horseinfo_url = "https://racing.hkjc.com/racing/information/Chinese/Horse/Horse.aspx?HorseNo=".$horseid;
    // $horseinfo_url = "https://racing.hkjc.com/racing/information/Chinese/Horse/Horse.aspx?HorseNo=G470";
    // echo $horseinfo_url."<br>";
// die(1);
    $html = postRequest($horseinfo_url,[]);
    $htmls = new simple_html_dom();
    $htmls->load($html);
    $cnt=0;
    // $rowData=array();
    // foreach($htmls->find('//table[class="bigborder"] tr') as $row){
    //     foreach($row->find('td') as $cell) {
    //             if(substr(trim($cell->innertext),0,2)=="<a"){                    
    //                 $rowData[$horseid][$cnt][] = $cell->find('a', 0)->innertext;
    //             }else{		
    //                 $str = str_replace('<span class="htable_eng_text">',"",$cell->plaintext);
    //                 $str = str_replace("</span>","",$str);
    //                 $rowData[$horseid][$cnt][] = trim($str);
    //             }
    //         }
    //         $cnt++;				
    // }
    $rowData = array();
    $tableRows = $htmls->find('table.bigborder tr');
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
    
        $rowData[$horseid][] = $cellData;
    }

    // print_r($rowData);
    // exit(1);
    //-----------------------------------------------------------------------------------------------------
    $insert_horseinfo="";
    $sql =""; 
    $cnt = 0;   
	foreach($rowData as $horseno => $data){
        $insert_horseinfo="";
        // echo "[".$horseid."]<br>";
		foreach($data as $row => $rowdetail){
            if (is_numeric($rowdetail['0'])) {
                $dateconvert = date_parse_from_format("d/m/y",$rowdetail['2']);
                $date = $dateconvert['year']."-".$dateconvert['month']."-".$dateconvert['day'];

                $horseinfo_array[$horseno][$rowdetail[0]]['RaceIndex'] = $rowdetail['0'];
                $horseinfo_array[$horseno][$rowdetail[0]]['Pla'] = $rowdetail['1'];
                $horseinfo_array[$horseno][$rowdetail[0]]['Date'] = $date;
                $horseinfo_array[$horseno][$rowdetail[0]]['RC_Track_Course'] = $rowdetail['3'];
                $horseinfo_array[$horseno][$rowdetail[0]]['Dist'] = $rowdetail['4'];
                $horseinfo_array[$horseno][$rowdetail[0]]['G'] = $rowdetail['5'];
                $horseinfo_array[$horseno][$rowdetail[0]]['RaceClass'] = $rowdetail['6'];
                $horseinfo_array[$horseno][$rowdetail[0]]['Dr'] = $rowdetail['7'];
                $horseinfo_array[$horseno][$rowdetail[0]]['Rtg'] = $rowdetail['8'];
                $horseinfo_array[$horseno][$rowdetail[0]]['Trainer'] = $rowdetail['9'];
                $horseinfo_array[$horseno][$rowdetail[0]]['Jockey'] = $rowdetail['10'];
                $horseinfo_array[$horseno][$rowdetail[0]]['LBW'] = $rowdetail['11'];
                $horseinfo_array[$horseno][$rowdetail[0]]['Win_Odds'] = $rowdetail['12'];
                $horseinfo_array[$horseno][$rowdetail[0]]['Act_Wt'] = $rowdetail['13'];
                $horseinfo_array[$horseno][$rowdetail[0]]['Running_Position'] = $rowdetail['14'];
                $horseinfo_array[$horseno][$rowdetail[0]]['Finish_Time'] = $rowdetail['15'];
                $horseinfo_array[$horseno][$rowdetail[0]]['Declar_Wt'] = $rowdetail['16'];
                $horseinfo_array[$horseno][$rowdetail[0]]['Gear'] = $rowdetail['17'];       

                //******************************************************** */

                // if(!in_array($horseno.$rowdetail['0'],$horseinfo_saved) && $horseid ){ //&& $horsename
                if (!empty($horseinfo_saved) && is_array($horseinfo_saved) && !in_array($horseno . $rowdetail['0'], $horseinfo_saved) && $horseid) {
                        $horseid_raceindex = trim($horseno.$rowdetail['0']);
                        $insert_horseinfo .= "(0, 
                                            '".$horseid."', 
                                            '".$horsename."', 
                                            '".$rowdetail['0']."', 
                                            '".$rowdetail['1']."', 
                                            '".$date."', 
                                            '".$rowdetail['3']."', 
                                            '".$rowdetail['4']."', 
                                            '".$rowdetail['5']."', 
                                            '".$rowdetail['6']."', 
                                            '".$rowdetail['7']."', 
                                            '".$rowdetail['8']."', 
                                            '".$rowdetail['9']."', 
                                            '".$rowdetail['10']."', 
                                            '".$rowdetail['11']."', 
                                            '".$rowdetail['12']."', 
                                            '".$rowdetail['13']."', 
                                            '".$rowdetail['14']."', 
                                            '".$rowdetail['15']."', 
                                            '".$rowdetail['16']."', 
                                            '".$rowdetail['17']."',now()),";
                        // $cnt++;
                    // }
                    //******************************************************** */
                    // }
                }
            }
        }       

               
    // }
        if(strlen($insert_horseinfo)>5){
            $insert_horseinfo = substr($insert_horseinfo,0,strlen($insert_horseinfo)-1).";";
            $sql = "INSERT INTO `horseinfo` (`id`, `horseid`, `horsename`, `RaceIndex`, `Pla`, `Date`, `RC_Track_Course`, `Dist`, `G`, `RaceClass`, `Dr`, `Rtg`, `Trainer`, `Jockey`, `LBW`, `Win_Odds`, `Act_Wt`, `Running_Position`, `Finish_Time`, `Declar_Wt`, `Gear`, `rectime`) 
                VALUES ".$insert_horseinfo; 
            echo $sql."<br>";
            // if($racingdate == date("Y-m-d")) {
                mysqli_query($mysqli, $sql);
                $error_message = mysqli_error($mysqli);
                if($error_message == ""){
                    echo "No error related to SQL query<br>";
                    $cnt++;
                }else{
                    echo "<u>Query Failed: ".$error_message."<u><br>";
                }
                // die(1);
        }     
        // print_r($horseinfo_array);
    }
    sleep(10);
}

//--------------------------------------------------------------------
$msg = 'ai-horseinfo-'.$cnt;
$insert = "	INSERT INTO logs
VALUES(0,'$msg',now())";
echo $insert;
mysqli_query($mysqli, $insert);
//--------------------------------------------------------------------		