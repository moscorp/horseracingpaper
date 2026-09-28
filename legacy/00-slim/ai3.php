<?php
$time_start = microtime(true); 

// error_reporting(E_ALL);
error_reporting(E_ALL & ~E_NOTICE); //only in log
ini_set('display_errors', 0);   //hide from user
// ini_set("allow_url_fopen", 1);
header('Content-Type: text/html; charset=utf-8');
require_once('lib/mossql.php');	
require_once('lib/func.php');	
require_once('lib/func_grec.php');	
require_once("lib/func_mailer_gmail.php");
include_once('simplehtmldom_1_9_1/simple_html_dom.php');

require_once("lib/constants.php");
$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

function getNumericValue($text) {
    preg_match('/(\d+)/', $text, $matches);
    return isset($matches[1]) ? $matches[1] : null;
}

function multiExplodex($racingdate,$venue,$json_string) {
    $oddsarray = [];
    $data = explode('@@@', $json_string);

    foreach ($data as $key => $item) {
        $raceno = $key;
        $winpla_string = multiexplode(array("WIN;","#PLA;"),$item);
        $win_string = explode(';', $winpla_string[1]);
        $pla_string = explode(';', $winpla_string[2]);
    
        if($key>0){
            foreach ($win_string as $string) {
                $oddsdata = explode("=", $string);
                $horseno = $oddsdata[0];
                $odds = $oddsdata[1];
                $oddsarray[$racingdate][$venue][$raceno][$horseno]['win'] = ($odds=="---" || $odds=="999" || !$odds) ? 1 : $odds;
            }
            foreach ($pla_string as $string) {
                $oddsata = explode("=", $string);
                $horseno = $oddsata[0];
                $odds = $oddsata[1];
                $oddsarray[$racingdate][$venue][$raceno][$horseno]['pla'] = ($odds=="---" || $odds=="999" || !$odds) ? 1 : $odds;
            }       
        }
    }

    return $oddsarray;
}

function insert_prop_key($venue,&$data) {
    // Iterate through the outer array
    foreach ($data as &$date_data) {
        // Iterate through the 'HV' array
        foreach ($date_data[$venue] as &$hv_data) {
            // Iterate through the inner arrays
            foreach ($hv_data as &$inner_data) {
                // Check if 'win' and 'pla' keys exist
                // if (isset($inner_data['win']) && isset($inner_data['pla'])) {
                if (isset($inner_data['win']) && is_numeric($inner_data['win']) && isset($inner_data['pla']) && is_numeric($inner_data['pla']) && $inner_data['win'] <> 999 && $inner_data['pla'] <> 999) {
                    // Calculate the 'prop' value and insert it
                    $inner_data['prop'] = number_format(100 * $inner_data['pla'] / $inner_data['win'],0);
                }
            }
        }
    }
}

function sort_win_column_asc($venue, &$data, $sort_key = 'win'){
    // Iterate through the outer array
    foreach ($data as &$date_data) {
        // Iterate through the 'HV' array
        foreach ($date_data[$venue] as &$hv_data) {
            $pos = array();
            $sum_win = 0;
            $sum_pla = 0;
            
            // Sort the inner arrays based on the specified $sort_key in descending order
            uasort($hv_data, function($a, $b) use ($sort_key) {
                // Compare the values in the specified $sort_key in descending order
                $result = $a[$sort_key] <=> $b[$sort_key];

                // If the 'prop' values are the same, add additional sorting criteria
                if ($result == 0 && $sort_key != 'prop') {
                    $result = $a['pla'] <=> $b['pla'];
                    $result = $a['prop'] <=> $b['prop'];
                    $result = $result ?: $b['ismax'] <=> $a['ismax'];
                    $result = $result ?: $b['issamecount'] <=> $a['issamecount'];
                }

                return $result;
            });

            // Determine the 'issamtcount' and 'ismax' values
            $prop_counts = array_count_values(array_column($hv_data, 'prop'));
            // print_r($prop_counts);
            // $prop_samevalue = array_filter(array_count_values(array_column($hv_data, 'prop')), function($v) { return $v > 1; }); //===2
            $max_prop = max(array_column($hv_data, 'prop'));
            
            $filter4x = array_filter($hv_data,function($v) { return ($v['prop'] >= 40 && $v['prop'] < 50); });
                $isbig4x_max_prop = max( array_column( $filter4x, 'prop' ) );
            $filtercount4x = array_filter(array_count_values(array_column($filter4x, 'prop')), function($v) { return $v > 1; });
                $prop_counts4x = count($filtercount4x);
            $filter3x = array_filter($hv_data,function($v) { return ($v['prop'] >= 30 && $v['prop'] < 40); });
                $isbig3x_max_prop = max( array_column( $filter3x, 'prop' ) );
            $filtercount3x = array_filter(array_count_values(array_column($filter3x, 'prop')), function($v) { return $v > 1; });
                $prop_counts3x = count($filtercount3x);                
            $filter2x = array_filter($hv_data,function($v) { return ($v['prop'] >= 20 && $v['prop'] < 30); });
                $prop_values = array_column($filter2x, 'prop');
                $min_prop_value = min($prop_values);
                $ismin2x_min_prop = array_search($min_prop_value, $prop_values, true);
                $ismin2x_min_horseno = array_keys($filter2x)[$ismin2x_min_prop]; /////////////////////////////////////////////
                $ismin2x_min_prop = min( array_column( $filter2x, 'prop' ) );
                $isbig2x_max_prop = max( array_column( $filter2x, 'prop' ) );
            $filtercount2x = array_filter(array_count_values(array_column($filter2x, 'prop')), function($v) { return $v > 1; });
                $prop_counts2x = count($filtercount2x);
            $filter1x = array_filter($hv_data,function($v) { return ($v['prop'] >= 10 && $v['prop'] < 20); });
                $isbig1x_max_prop = max( array_column( $filter1x, 'prop' ) );
            $filtercount1x = array_filter(array_count_values(array_column($filter1x, 'prop')), function($v) { return $v > 1; });
                $prop_counts1x = count($filtercount1x);                

            $nofhorse = count($hv_data);
            $lastprop = end($hv_data);
            $lastprop2nd = prev($hv_data); //$hv_data[count($hv_data) - 2];

            $cnt=1;
            $is19=0;
            foreach ($hv_data as &$item) {
                $item['pos'] = $cnt;
                $item['nofhorse'] = $nofhorse;
                $item['issamecount'] = $prop_counts[$item['prop']] > 1 ? $prop_counts[$item['prop']] : 0;
                $item[$item['prop']]['issameXpos'] = $prop_counts[$item['prop']] > 1 ? $pos[$item['prop']][$item['prop']]++ : 0;
                $item['ismax'] = $item['prop'] == max(array_column($hv_data, 'prop')) ? 1 : 0;
                $item['ismin2x'] = ($ismin2x_min_prop == $item['prop']) ? 1 : 0;
                $item['isbig2x'] = ($isbig2x_max_prop == $item['prop']) ? 1 : 0;
                $item['isbig3x'] = ($isbig3x_max_prop == $item['prop']) ? 1 : 0;
                $item['same2last'] = ($lastprop['prop'] == $item['prop'] && $lastprop['win'] != $item['win']) ? 1 : 0;
                $item['lastbigger'] = ($lastprop['prop'] == $item['prop'] && $lastprop['prop'] > $lastprop2nd['prop'] ) ? 1 : 0;
                $is19 = ($item['prop'] == 19) ? $is19++ : $is19;
                $cnt++;
                $sum_win = $sum_win + (isset($item['win']) ? $item['win'] : 0);
                $sum_pla = $sum_pla + (isset($item['pla']) ? $item['pla'] : 0);
            }
            $hv_data['sum_win'] = $sum_win;
            $hv_data['sum_pla'] = $sum_pla;
            $hv_data['winpla_avg'] = number_format(100 * $sum_pla / $sum_win,0);
            $hv_data['count1x'] = $prop_counts1x;
            $hv_data['count2x'] = $prop_counts2x;
            $hv_data['count3x'] = $prop_counts3x;
            $hv_data['count4x'] = $prop_counts4x;
            $hv_data['min2xprop'] = $ismin2x_min_prop;
            $hv_data['min2xhorseno'] = $ismin2x_min_horseno;
            $hv_data['max_prop'] = $max_prop;
            $hv_data['nofhorse'] = $nofhorse;
            $hv_data['is19'] = $is19;
            
        }
    }
}
//--------------------------------------------------------------------------
$whr = isset($_GET['venue']) ? " and venue='".$_GET['venue']."'" : "";
$sql = "SELECT type,racingdate,venue,mtgTotalRace,odds	
        FROM oddshistory 
        where type = 'pre' $whr
        order by rectime desc
        limit 1
        ";
// echo $sql;
$latest_oddshistory = mysqli_query($mysqli, $sql);
$row = mysqli_fetch_assoc($latest_oddshistory);
$json_string = $row['odds'];
$venue = $row['venue'];
$racingdate = $row['racingdate'];
//--------------------------------------------------------------------------
$odds = multiExplodex($racingdate,$venue,$json_string);
insert_prop_key($venue,$odds);
sort_win_column_asc($venue,$odds);
print_r($odds);
$exectime = "[1]-".number_format(microtime(true) - $time_start, 2)."-";
//--------------------------------------------------------------------------
if(date("Y-m-d")==$racingdate){
    $url = "https://bet.hkjc.com/racing/getJSON.aspx?type=winplaodds&date=".$racingdate."&venue=".$venue."&start=1&end=14";//.$end;
    $json_string = postRequest($url,[]);
    // echo $url;
    // print_r($obj);
}else{
    $sql = "SELECT type,racingdate,venue,mtgTotalRace,odds	
            FROM oddshistory 
            where type = 'curr' and venue='$venue' and racingdate='$racingdate'
            order by rectime desc
            limit 1
            ";
    // echo $sql;        
    $latest_oddshistory = mysqli_query($mysqli, $sql);
    $row = mysqli_fetch_assoc($latest_oddshistory);
    $json_string = $row['odds'];
}
$odds_curr = multiExplodex($racingdate,$venue,$json_string);
insert_prop_key($venue,$odds_curr);
// sort_win_column_asc($venue,$odds_curr);
// print_r($odds_curr);
$exectime = "[2]-".number_format(microtime(true) - $time_start, 2)."-";
//--------------------------------------------------------------------------

$sql_rsdata = "SELECT *
        FROM rsdata 
        where mtgdate = '$racingdate' and mtgvenue = '$venue' limit 1";
$rsdata_array = mysqli_query($mysqli, $sql_rsdata);
$rsdata = mysqli_fetch_assoc($rsdata_array);
// print_r( $rsdata);
$raceHeaderInfo = json_decode($rsdata['raceHeaderInfo'], true);

// $raceHeaderInfo_array = 
// print_r($raceHeaderInfo);
$start="1";	
$end=$rsdata['mtgTotalRace'];
$mtgTotalRace = $rsdata['mtgTotalRace'];
//--------------------------------------------------------------------------
$date = new DateTime($racingdate);

// Check the month of the $racingdate
$month = $date->format('n'); // 'n' format returns the month as an integer

if ($month < 9) {
    // If the month is less than 9, set the year to the previous year
    $date->setDate($date->format('Y') - 1, 9, 1);
} else {
    // If the month is 9 or greater, set the year to the current year
    $date->setDate($date->format('Y'), 1, 1);   //9,1
}

$RacingDateStart = $date->format('Y-m-d');
$sql = "SELECT rc.venue, rc.raceno, rc.horseno, rc.horsename, rc.brandno, rc.trainer, rc.jockey, rc.besttime
        FROM RaceCard rc
        where racingdate = '$racingdate'";
$racecard_array = mysqli_query($mysqli, $sql);

$jockeychange = array();
$besttime_min = array();
$rc_trainer = array();
$trainer_count = array();
while ($row = mysqli_fetch_assoc($racecard_array)) {
    $venue = $row['venue'];
    $raceno = $row['raceno'];
    $horseno = $row['horseno'];
    $brandno = $row['brandno'];
    $besttime = $row['besttime'];
    $jockeyshort = explode('(', $row['jockey']);
    $jockeychange[$venue][$raceno][$jockeyshort[0]] = $horseno;
    $trainer = $row['trainer'];
    $rc_trainer[$racingdate][$venue][$raceno][$horseno]['trainer'] = $row['trainer'];
    
    if(!empty($besttime)){
        if (!isset($besttime_min[$venue][$raceno]) || $besttime < $besttime_min[$venue][$raceno]) {
            $besttime_min[$venue][$raceno] = $besttime;
        }
    }
    // Count the number of trainers for each race
    if (!isset($trainer_count[$venue][$raceno][$trainer])) {
        $trainer_count[$venue][$raceno][$trainer] = 1;
    } else {
        $trainer_count[$venue][$raceno][$trainer]++;
    }
    // echo $trainer.$trainer_count[$venue][$raceno][$trainer]."<br>";
}
// Reset the result set pointer
mysqli_data_seek($racecard_array, 0);

while ($row = mysqli_fetch_assoc($racecard_array)) {
    $rcvenue = $row['venue'];
    $raceno = $row['raceno'];
    $horseno = $row['horseno'];
    $brandno = $row['brandno'];
    $besttime = $row['besttime'];
    $jockeyshort = explode('(', $row['jockey']);
    $min_besttime = $besttime_min[$rcvenue][$raceno];
    
    // $rsdata_track = substr($rsdata['raceHeaderInfo'][$raceno]['track'], 0, strpos($rsdata['raceHeaderInfo'][$raceno]['track'], '跑道'));
    $rsdata_venue = $raceHeaderInfo[$raceno]['venue'];
    // $mtgVenue = $rsdata['mtgVenue'];
    // $rsdata[$mtgVenue][$raceno]['time'] = $rsdata['raceHeaderInfo'][$raceno]['time'];
    $rsdata_track = (substr($raceHeaderInfo[$raceno]['track'], 0, strpos($raceHeaderInfo[$raceno]['track'], '跑道'))) ? substr($raceHeaderInfo[$raceno]['track'], 0, strpos($raceHeaderInfo[$raceno]['track'], '跑道')) : $raceHeaderInfo[$raceno]['track'];
    // echo $rsdata_track;
    
    $rsdata_dist = str_replace("m","",$rsdata['raceHeaderInfo'][$raceno]['dist']);
    
    $hist = "SELECT raceindex, horseid, horsename, max(date) as date, 
            GROUP_CONCAT(pla ORDER BY date DESC) AS plax, 
            GROUP_CONCAT(win_odds ORDER BY date DESC) AS win_oddsx, 
            GROUP_CONCAT(rc_track_course ORDER BY date DESC) as rc_track_course, 
            GROUP_CONCAT(dr ORDER BY date DESC) as dr,
            GROUP_CONCAT(jockey ORDER BY date DESC) as jockey,
            GROUP_CONCAT(dist ORDER BY date DESC) as dist,
            GROUP_CONCAT(finish_time ORDER BY date DESC) as finish_time
            FROM (
                SELECT DISTINCT raceindex, horseid, horsename, pla, win_odds, dr, date, SUBSTRING_INDEX(REPLACE(rc_track_course, '&quot;', '\"'), '\"', 1) AS rc_track_course, 
                jockey, dist, finish_time
                FROM horseinfo
                WHERE horseid = '".$brandno."'
                and date>='$RacingDateStart'
                and date<'$racingdate'
                ORDER BY date DESC
            ) t
            GROUP BY horseid
            ORDER BY date DESC
        ";                
    $hist_data = mysqli_query($mysqli, $hist);
    while ($hist_row = mysqli_fetch_assoc($hist_data)) {
        if($hist_row['date'] < $racingdate){
            $odds[$racingdate][$rcvenue][$raceno][$horseno]['raceindex'] = $hist_row['raceindex'];
            $odds[$racingdate][$rcvenue][$raceno][$horseno]['horseid'] = $hist_row['horseid'];
            $odds[$racingdate][$rcvenue][$raceno][$horseno]['horsename'] = $hist_row['horsename'] ? $hist_row['horsename'] : $row['horsename'];
            $odds[$racingdate][$rcvenue][$raceno][$horseno]['plax'] = $hist_row['plax'];
            $odds[$racingdate][$rcvenue][$raceno][$horseno]['win_oddsx'] = $hist_row['win_oddsx'];
            $odds[$racingdate][$rcvenue][$raceno][$horseno]['rc_track_course'] = $hist_row['rc_track_course'];
            $odds[$racingdate][$rcvenue][$raceno][$horseno]['dr'] = $hist_row['dr'];
            $odds[$racingdate][$rcvenue][$raceno][$horseno]['jockey'] = $row['jockey'];
            $odds[$racingdate][$rcvenue][$raceno][$horseno]['besttime'] = ($besttime == $min_besttime) ? "<font color=red>*</font>" : "";
            
        //---
            $course = $rsdata_venue.$rsdata_track;
            // echo $course;
            $hist_course = explode(",", $hist_row['rc_track_course']);
            $total_count = count($hist_course);
            $plaxhist = explode(",", $hist_row['plax']);
            $keys_course = array_keys(array_column($hist_course, null), $course);
            $result = array_intersect_key($plaxhist, array_flip($keys_course));
            $total_course_count = count($result);
            $count_course_less_than_4 = count(array_filter($result, function($value) {
                return $value < 4;
            }));
            $odds[$racingdate][$rcvenue][$raceno][$horseno]['trackpla'] = $count_course_less_than_4."/".$total_course_count."/".$total_count;
            $odds[$racingdate][$rcvenue][$raceno][$horseno]['trackpla80'] = ($count_course_less_than_4/$total_course_count >= 0.8) ? 1 : 0;
            $odds[$racingdate][$rcvenue][$raceno][$horseno]['trackpla50u'] = ($count_course_less_than_4/$total_course_count > 0.5) ? 1 : 0;
            $odds[$racingdate][$rcvenue][$raceno][$horseno]['trackpla50d'] = ($count_course_less_than_4/$total_course_count >= 0.4) ? 1 : 0;
        // ---
            $hist_jockey = explode(",", $hist_row['jockey']);
            $keys_jockey = array_keys(array_column($hist_jockey, null), $jockeyshort[0]);
            $result = array_intersect_key($plaxhist, array_flip($keys_jockey));
            $total_jockey_count = count($result);
            $count_jockey_less_than_4 = count(array_filter($result, function($value) {
                return $value <= 4;
            }));
            $odds[$racingdate][$rcvenue][$raceno][$horseno]['jockeypla'] = (($count_jockey_less_than_4 !== 0) || ($total_jockey_count !== 0)) ? $count_jockey_less_than_4."/".$total_jockey_count : "-";
            $odds[$racingdate][$rcvenue][$raceno][$horseno]['jockeypla80'] = ($count_jockey_less_than_4/$total_jockey_count >= 0.8) ? 1 : 0;
            $odds[$racingdate][$rcvenue][$raceno][$horseno]['jockeypla50u'] = ($count_jockey_less_than_4/$total_jockey_count > 0.5) ? 1 : 0;
            $odds[$racingdate][$rcvenue][$raceno][$horseno]['jockeypla50d'] = ($count_jockey_less_than_4/$total_jockey_count >= 0.4) ? 1 : 0;
            // echo $rcvenue.$raceno.$horseno.$hist_jockey[0].$jockeyshort[0]."<br>";
            $jockeychanges = $jockeychange[$rcvenue][$raceno][$hist_jockey[0]] ? $jockeychange[$rcvenue][$raceno][$hist_jockey[0]] : "";
            $odds[$racingdate][$rcvenue][$raceno][$horseno]['jockeysamelast'] = ($hist_jockey[0] == $jockeyshort[0]) ? "=" : $jockeychanges;
        // ---
            $hist_dist = explode(",", $hist_row['dist']);      
            $odds[$racingdate][$rcvenue][$raceno][$horseno]['dist'] = $rsdata_dist ? ($rsdata_dist>$hist_dist[0] ? "+" : ($rsdata_dist<$hist_dist[0] ? "-" : "")) : "";
        }
    }
}
// print_r($odds);
//--------------------------------------------------------------------------
    $result_url = "https://racing.hkjc.com/racing/information/Chinese/Racing/ResultsAll.aspx?RaceDate=".str_replace("-","/",$racingdate);
    // echo $result_url.$venue;
    $html = postRequest($result_url,[]);
    $htmls = new simple_html_dom();
    $htmls->load($html);
    // print_r($htmls);
    //die(1);
    
    $group=array();
    $race=array();
    $result = array(); // Initialize the $result array
    $resultx = array(); // Initialize the $result array
    $resultfull=array();
    $race_cnt=1;
    
    $html = str_get_html($htmls);

    $raceno = array();
    
    foreach ($html->find('div[class=race_result]') as $race_result) {
        $eachrace = $race_result->find('div.f_fs13.margin_top15');
        foreach ($eachrace as $key => $div) {
            $pla = 1; // Initialize the variable $pla
            $rank=1;
            $trcount=0;
            
            $venue = strtoupper($venue);
            if(substr($venue,0,2)=="S1" || $venue == "S2" || $venue == "S3" || $venue == "S4" || $venue == "S5" || $venue == "S6"){
                $racetitle = $div->find('div.color_w.f_fs13.font_wb', 0)->innertext;
                list($t['venuefull'], $t['racenofull']) = explode('-',$racetitle);
                $racetitlevenue = substr($t['venuefull'], strpos($t['venuefull'], "(") + 1, 2); //preg_replace('/\D/', '', $t['venuefull']); // Extract the numeric part
                $racetitleno = preg_replace('/\D/', '', $t['racenofull']); // Extract the numeric part
                
                foreach($div->find('table[class=f_fs13 f_tac dividends] tr') as $row) {
                    $rowData = array();
                    foreach($row->find('td') as $cell) {
                        $rowData[] = $cell->innertext;//substr($cell->innertext,1,2);
                        // echo $cell->innertext."<br>";
                    }
                    // echo $trcount."--".$rowData[0].$rowData[1].$rowData[2]."<br>";
                    
                    if($trcount==14 && $rowData[1]){    //tr14 = QQF
                        $ttt = $rowData[1];
                        $array = explode(',', $ttt);

                        $resultfull[$racetitlevenue][$racetitleno][14] = $ttt;
                        $resultfull[$racetitlevenue][$racetitleno][1] = getNumericValue($array[0]); 
                        $resultfull[$racetitlevenue][$racetitleno][2] = getNumericValue($array[1]);  
                        $resultfull[$racetitlevenue][$racetitleno][3] = getNumericValue($array[2]);  
                        $resultfull[$racetitlevenue][$racetitleno][4] = getNumericValue($array[3]);  
    
                        $resultx[$racetitlevenue][$racetitleno][$array[0]] = 1; 
                        $resultx[$racetitlevenue][$racetitleno][$array[1]] = 2; 
                        $resultx[$racetitlevenue][$racetitleno][$array[2]] = 3; 
                        $resultx[$racetitlevenue][$racetitleno][$array[3]] = 4; 
                        // print_r($resultx);
                        $pla++; // Increment the $pla variable
                    }
                    $trcount++;
                }
            }else{
                $racetitle = $div->find('div.color_w.f_fs13.font_wb', 0)->innertext;
                $racetitleno = preg_replace('/\D/', '', $racetitle); // Extract the numeric part
                foreach($div->find('table[class=f_fs13 f_tac result] tr') as $row) {
                    $rowData = array();
                    foreach($row->find('td') as $cell) {
                        $rowData[] = $cell->innertext;
                        // echo $cell->innertext."<br>";
                    }
                    // echo $venue.$racetitle.$racetitleno.$rowData[0].$rowData[1].$rowData[2]."<br>";
                    if($rowData[1]){
                        $rankpla = trim(substr($rowData[0],0,2));
                        $result[$racetitleno][$rankpla] = $rowData[1]; // Use $pla as the key for $result array
                        $resultx[$venue][$racetitleno][$rowData[1]] = $rankpla; // Use $pla as the key for $resultfull array
                        $resultfull[$racetitlevenue][$racetitleno][$rowData[1]] = $rankpla; // Use $pla as the key for $resultfull array
                        // echo $venue.$racetitleno.$rankpla.$rowData[1]."<br>";
                        
                        $pla++; // Increment the $pla variable
                    }
                }
            }
        }
    }

    // Output the raceno
    // print_r($resultx);
    // print_r($resultfull);
    
// for ($raceno = $start; $raceno <= $end; $raceno++) {
// 	$url = "https://bet.hkjc.com/racing/getJSON.aspx?type=tcetop&date=".$racingdate."&venue=".$venue."&raceno=".$raceno;
//     $json_data = file_get_contents("compress.zlib://".$url);
//     $data = json_decode($json_data, true);
    
//     $result = array(
//         'venue' => 'HV',
//         'raceno' => $raceno, //'1',
//         'bets' => array()
//     );
    
//     $bets = explode(';', $data['OUT']);
//     foreach ($bets as $bet) {
//         $parts = explode('=', $bet);
//         $tec = $parts[0];
//         $odds = $parts[1];
//         $result['bets'][] = array(
//             'tec' => $tec,
//             'odds' => $odds
//         );
//     }
    
//     print_r($result);
// }
$pick = [];
foreach ($odds[$racingdate][$venue] as $raceno => $race) {
    $maxbtw3x = ['key' => null, 'prop' => 0];
    $inside2x = ['key' => null, 'prop' => 0];
    $pos = [];
    $count18 = 0;
    $max6upos = 0;

    //-----------------------------------------------------------------------------------------------------------------
    // if ($race['count1x'] == 0 && $race['count2x'] == 0 && $race['count3x'] == 1 && $race['count4x'] == 0) {
    if ($race['count3x'] >= 1 ) {
        // Collect all non-empty positions
        foreach ($race as $horseno => $row) {
            if ($row['issamecount'] > 0 && $row['prop'] > 30 ) {
                $pos[] = $row['pos'];
            }
        }
        if (!empty($pos)) {
            $minPos = min($pos);
            $maxPos = max($pos);
            // Find the horse with the maximum prop value between the min and max positions
            foreach ($race as $horseno => $row) {
                if ($row['pos'] > $minPos && $row['pos'] < $maxPos && $row['prop'] > $maxbtw3x['prop']) {
                    $maxbtw3x['key'] = $horseno;
                    $maxbtw3x['prop'] = $row['prop'];
                }
            }
            $pick[$raceno] = $maxbtw3x['key'];
            $odds[$racingdate][$venue][$raceno]['pick1'][] = $maxbtw3x['key'];   //pick 1
        }
    }
    //-----------------------------------------------------------------------------------------------------------------
    $propArray = [];
    // $uponepos = PHP_INT_MAX;
    foreach ($race as $horseno => $row) {
        $horseno_tmp = $horseno;
        if (is_numeric($row['prop']) && is_numeric($horseno)) {
            $propArray[$horseno] = $row['prop'];
        }
        if($row['pos']>1 && $row['ismax']==1 && $row['prop']>40  && $row['prop']<50){
            if($race[$uponepos]['issamecount']){
                $odds[$racingdate][$venue][$raceno]['pick4'][] = $uponepos; //exclusive
            }else{
                $odds[$racingdate][$venue][$raceno]['pick1'][] = $uponepos;
            }
            // echo $raceno.$horseno.$uponepos."<br>";
            // $odds[$racingdate][$venue][$raceno]['pick3'][] = $race['min2xhorseno'] ;//$ismin2x_min_horseno;
        }
        if($row['pos']>1 && $row['prop']==18 && !$count18){
            $odds[$racingdate][$venue][$raceno]['pick1'][] = $uponepos;     //pick 1
            $count18++;
        }
        $maxPos = max(array_column($race, 'pos'));
        if($row['pos']==$maxPos && $row['prop']>=28){
            if($row['issamecount']){
                $odds[$racingdate][$venue][$raceno]['pick4'][] = $horseno;   //exclusive
            }else{
                $odds[$racingdate][$venue][$raceno]['pick2'][] = $horseno;   //pick 2
            }
        }
        if($race['max_prop']>=60 && $race['max_prop']==$row['prop']){
          $max6upos = $row['pos'];  
        } 
        if($max6upos && ($max6upos+1)==$row['pos'] && !$row['issamecount']){
            // $odds[$racingdate][$venue][$raceno]['pickbyprop'][] = $race['min2xprop'];   //pick 2
            $odds[$racingdate][$venue][$raceno]['pick2'][] = $race['min2xhorseno'];   //pick 2
        }

        if ($race['count2x'] == 2) {
            $maxPos = 0;
            $minProp = PHP_INT_MAX;
            $maxPosList = [];
        
            foreach ($race as $horseno => $horseData) {
                if ($horseData['issamecount'] && $horseData['prop'] >= 20 && $horseData['prop'] < 30 ) {
                    if ($horseData['prop'] < $minProp) {
                        $minProp = $horseData['prop'];
                    }
                }
            }
        
            foreach ($race as $horseno => $horseData) {
                if ($horseData['issamecount'] && $horseData['pos'] >= $maxPos && $horseData['prop'] == $minProp) {
                    if ($horseData['pos'] > $maxPos) {
                        $maxPos = $horseData['pos'];
                        $maxPosList = [$horseno];
                    } elseif ($horseData['pos'] == $maxPos) {
                        $maxPosList[] = $horseno;
                    }
                }
            }
        // echo $raceno.$maxPosList[0];
        // print_r($maxPosList);
            $odds[$racingdate][$venue][$raceno]['pick1'][] = $maxPosList[0];  // pick 3
        }        
        
        
        $uponepos = ($horseno_tmp);
    }
    if($race['count2x']){
        // Find the first occurrence of the most common value between 20 and 30
        $commonValues = array_filter($propArray, function($value) {
            return $value >= 20 && $value < 30;
        });
        $nexto2xhorseno = array_slice(array_keys($commonValues), 1, 1);
        $nexto2xprop = $commonValues[reset($nexto2xhorseno)];
        // echo $raceno."key=" . reset($nexto2xhorseno) . ", prop=" . $output;
        $odds[$racingdate][$venue][$raceno]['pick3'][] = reset($nexto2xhorseno);   //pick 3
    }
    if($race['count1x']){
        // Find the first occurrence of the most common value between 20 and 30
        $commonValues = array_filter($propArray, function($value) {
            return $value >= 10 && $value < 20;
        });
        $nexto2xhorseno = array_slice(array_keys($commonValues), 1, 1);
        $nexto2xprop = $commonValues[reset($nexto2xhorseno)];
        // echo $raceno."key=" . reset($nexto2xhorseno) . ", prop=" . $output;
        $odds[$racingdate][$venue][$raceno]['pick2'][] = reset($nexto2xhorseno);   //pick 3
    }
}
//--------------------------------------------------------------------------
//--------------------------------------------------------------------------
//--------------------------------------------------------------------------
//--------------------------------------------------------------------------

// print_r($odds);
$col = 16;
$listr .=$racingdate."[".$venue."]";
$listr .="<table border=1 cellpadding='3' cellspacing='3' border-collapse= collapse; style=\"border-collapse: collapse;\">";
$listr .= "<tr>";
// $listr .= "<th>raceno</th>";
// $listr .= "<th>pos</th>";
$listr .= "<th>prop</th>";
$listr .= "<th>proc</th>";
$listr .= "<th>win</th>";
$listr .= "<th>curW</th>";
$listr .= "<th>curP</th>";
$listr .= "<th>pla</th>";
$listr .= "<th>nos</th>";
// $listr .= "<th>name</th>";
// $listr .= "<th>jockey</th>";
$listr .= "<th>result</th>";
$listr .= "<th>sel</th>";
$listr .= "<th>sel</th>";
$listr .= "<th>sel</th>";
$listr .= "<th>trackpla</th>";
$listr .= "<th>jcpla</th>";
$listr .= "<th>jcex</th>";
$listr .= "<th>d</th>";
$listr .= "<th>tcnt</th>";
$listr .= "<th>issameXpos</th>";
$listr .= "<th>lastbigger</th>";

$listr .= "</tr>";
foreach ($odds[$racingdate][$venue] as $raceno => $race) {
    $temppla =0;
    $listr .= "<tr><td colspan=$col>[".$raceno."] ";
    $listr .= " . ".$raceHeaderInfo[$raceno]['time'];
    $listr .= " . ".$raceHeaderInfo[$raceno]['class'];
    $listr .= " . ".$raceHeaderInfo[$raceno]['track'];
    $listr .= " . ".$raceHeaderInfo[$raceno]['dist'];
    $listr .= " . ".$raceHeaderInfo[$raceno]['going'];
    foreach ($race as $horseno => $row) {
        if(is_numeric($horseno)){
            $pre_win = $row['win'];
            $pre_pla = $row['pla'];
            $pre_prop = $row['prop'];
            $curr_win = $odds_curr[$racingdate][$venue][$raceno][$horseno]['win'];
            $curr_pla = $odds_curr[$racingdate][$venue][$raceno][$horseno]['pla'];
            $curr_prop = $odds_curr[$racingdate][$venue][$raceno][$horseno]['prop'];
            $trainer = $rc_trainer[$racingdate][$venue][$raceno][$horseno]['trainer'];
            //-------------------------------------------------------------------------------------
            $issamecount = $row['issamecount'] ? "bgcolor=yellow" : "";
            $same2last = $row['same2last'] ? "*" : "";
            $smallpla = ($temppla && $temppla > $row['pla']) ? "<font color=red>" : "";
            $alert = $row['ismax'] ? "<font color=red>" : "";
            $alert .= $row['isbig2x'] ? "*" : "";
            $alert .= $row['isbig3x'] ? "*" : "";
            $isbesttime = $row['besttime'];
            $isdrop15_win = ($curr_win/$pre_win < 0.8) ? "<font color=#FF9900>" : "";
            $isdrop15_pla = ($curr_pla/$pre_pla < 0.8) ? "<font color=#FF9900>" : "";
            $curr_prop_up = ($curr_prop>$pre_prop) ? "<b>" : "";
            //-------------------------------------------------------------------------------------
            $pick1 = in_array($horseno,$odds[$racingdate][$venue][$raceno]['pick1']) ? " bgcolor=green " : "";
            $pick2 = in_array($horseno,$odds[$racingdate][$venue][$raceno]['pick2']) ? " bgcolor=green " : "";
            $pick3 = in_array($horseno,$odds[$racingdate][$venue][$raceno]['pick3']) ? " bgcolor=green " : "";
            $pick4 = in_array($horseno,$odds[$racingdate][$venue][$raceno]['pick4']) ? " bgcolor=lightgrey " : "";
            // echo $raceno.$odds[$racingdate][$venue][$raceno]['pick2']."<br>";
            //-------------------------------------------------------------------------------------
            
            // print_r($odds[$racingdate][$venue][$raceno]['pick']);
            
            $listr .= "<tr>";
            $listr .= "<td $issamecount>".$alert.$pre_prop.$same2last."</td>";
            $listr .= "<td>".$curr_prop_up.$curr_prop."</td>";
            $listr .= "<td style=\"border-left: 2px solid #000; font-weight1: bold;\" align=\"right\">{$pre_win}</td>";
            $listr .= "<td align=right><b>".$isdrop15_win.$curr_win."</td>";
            $listr .= "<td><b>".$isdrop15_pla.$curr_pla."</td>";
            $listr .= "<td style=\"border-right: 2px solid #000; font-weight1: bold;\" align=\"right\">$smallpla{$pre_pla}</td>";
            $listr .= "<td align=right>".$isbesttime.$horseno."</td>";
            $listr .= "<td align=center>{$resultx[$venue][$raceno][$horseno]}</td>";
            
            $listr .= "<td $pick1 $pick4>".((is_numeric($pre_win) && is_numeric($curr_win)) ? "<input type=\"checkbox\" name=\"test\" value=\"value1\" $pick1>" : "");
            $listr .= "<td $pick2>".((is_numeric($pre_win) && is_numeric($curr_win)) ? "<input type=\"checkbox\" name=\"test\" value=\"value1\" $pick2>" : "");
            $listr .= "<td $pick3>".((is_numeric($pre_win) && is_numeric($curr_win)) ? "<input type=\"checkbox\" name=\"test\" value=\"value1\" $pick3>" : "");
            
            $trackplaCheck = ($row['trackpla80']) ? "<font color=red>" : (($row['trackpla50u']) ? "<font color=blue>" : (($row['trackpla50d']) ? "<font color=green>" : "<font color=lightgrey>"));
            $listr .= "<td>".$trackplaCheck . ($row['trackpla'] !== 0 ? $row['trackpla'] : "") . "</td>";
            
            $jockeyplaCheck = ($row['jockeypla80']) ? "<font color=red>" : (($row['jockeypla50u']) ? "<font color=blue>" : (($row['jockeypla50d']) ? "<font color=green>" : "<font color=lightgrey>"));
            // $jockeysamelast = $row['jockeysamelast'];
            $listr .= "<td>".$jockeyplaCheck . ($row['jockeypla'] !== 0 ? $row['jockeypla'] : "") . "</td>";//"</font>".$jockeysamelast."</td>";
            $listr .= "<td>" . ($row['jockeysamelast'] !== 0 ? $row['jockeysamelast'] : "") . "</td>";
            
            
            $listr .= "<td>" . ($row['dist'] !== 0 ? $row['dist'] : "") . "</td>";
            $traincount = ($trainer_count[$venue][$raceno][$trainer]>1) ? $trainer_count[$venue][$raceno][$trainer] : "";
            $listr .= "<td align=right>$traincount</td>";   //".$venue.$raceno.$trainer;//
            
            $listr .= "<td>" . ($row[$pre_prop]['issameXpos'] !== 0 ? $row[$pre_prop]['issameXpos'] : "") . "</td>";
            $listr .= "<td>" . ($row['lastbigger'] !== 0 ? $row['lastbigger'] : "") . "</td>";

            $temppla = $row['pla'];
            $listr .= "</tr>";
        }
    }
}

echo $listr;
//&& $racingdate == date("Y-m-d")
if($_GET['mail'] ){
    // $emailtitle = "ai2";
	$ipAddress = substr($_SERVER['SERVER_ADDR'],-3);
	$Subject = "[horsepaper] -".$emailtitle." [AI2] ".$ipAddress;	//$date;
	$Body    = $listr;
	$AltBody = '';
	$recipients = '';
	$log = '';
	$recipients_BCC = array(
		'hkhorsepaper@gmail.com' => 's',
		'support@fengins.com' => 'fi',
		// ..
	 );
	 
	 $send = mailto($Subject,$Body,$AltBody,$recipients,$recipients_BCC,$log);

}
$exectime .= "[9]-".number_format(microtime(true) - $time_start, 2)."-"; 
if($mm) { echo $exectime; }

$horseinfo_hints_check = "
    SELECT 1 FROM horseinfo_hints
    WHERE racingdate = '$racingdate'
      AND venue = '$venue'
";
$result = mysqli_query($mysqli, $horseinfo_hints_check);

if ($result === false) {
    // Query failed
    // echo "Error executing SQL query: " . mysqli_error($mysqli);
} else {
    // Query successful
    $affected_rows = mysqli_num_rows($result);
    if ($affected_rows > 0) {
        // Duplicate data found, no new record inserted
        // echo "Duplicate data, no new record inserted.";
    } else {
        $values = [];
        foreach ($odds[$racingdate][$venue] as $raceno => $race) {
            $ai2pos = 1;
            foreach ($race as $horseno => $row) {
                $trackpla = $row['trackpla'];
                $jcpla = $row['jockeypla'];
                $jcex = $row['jockeysamelast'];
                $dist = $row['dist'];
                $values[] = "('$racingdate', '$venue', '$mtgTotalRace', '$raceno', '$horseno', '$ai2pos', '$trackpla', '$jcpla', '$jcex', '$dist', NOW())";
                $ai2pos++;
            }
        }
        
        $insert_sql = "INSERT INTO horseinfo_hints (racingdate, venue, mtgTotalRace, raceno, horseno, ai2pos, trackpla, jcpla, jcex, dist, rectime) VALUES " . implode(", ", $values);
        $result = mysqli_query($mysqli, $insert_sql);
        
        if ($result === false) {
            // echo "Error executing SQL query: " . mysqli_error($mysqli);
        } else {
            $affected_rows = mysqli_affected_rows($mysqli);
            echo "Inserted $affected_rows new record(s).";
        }
    }

    // Assuming you have a database connection established
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    // print_r($odds);
    // Iterate through the $odds array
    foreach ($odds as $racingdate => $venueData) {
        foreach ($venueData as $venue => $raceData) {
            // Check if the combination of racingdate and venue already exists
            $sql = "SELECT COUNT(*) as count FROM hrp_pick2 WHERE racingdate = ? AND venue = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $racingdate, $venue);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $count = $row['count'];
            $stmt->close();
    
            if ($count == 0) {
                // Prepare the SQL query to insert the data
                $sql = "INSERT INTO hrp_pick2 (racingdate, venue, raceno, pick1, pick2, pick3, pick4, rectime)
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
    
                foreach ($raceData as $raceno => $pickData) {
                    $pick1 = implode(",", $pickData['pick1']);
                    $pick2 = implode(",", $pickData['pick2']);
                    $pick3 = implode(",", $pickData['pick3']);
                    $pick4 = implode(",", $pickData['pick4']);
                
                    $sql = "INSERT INTO hrp_pick2 (racingdate, venue, raceno, pick1, pick2, pick3, pick4, rectime)
                            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssissss", $racingdate, $venue, $raceno, $pick1, $pick2, $pick3, $pick4);
                
                    if ($stmt->execute()) {
                        // echo "Data saved successfully for racingdate: $racingdate, venue: $venue, raceno: $raceno\n";
                    } else {
                        // echo "Error saving data for racingdate: $racingdate, venue: $venue, raceno: $raceno: " . $stmt->error . "\n";
                    }
                
                    // $stmt->close();
                }
    
                $stmt->close();
            } else {
                // echo "Skipping data for racingdate: $racingdate, venue: $venue, as it already exists in the database.\n";
            }
        }
    }
    
    $conn->close();
    
}

$exectime .= "[10]-".number_format(microtime(true) - $time_start, 2)."-"; 
if($mm) { echo $exectime; }
?>

<STYLE type="text/css" media="all">
BODY, FIELDSET, MARQUEE, TEXTAREA, TD, TR, SELECT, input {
	font-family: Calibri,Tahoma, Verdana, sans-serif;
	font-size: 15px;
	font-weight: normal;
	text-decoration: none;
}
img { border: 0 solid; }


a
{
    text-decoration: none;
}    

.qpmatrix { display: none;}
</STYLE>
