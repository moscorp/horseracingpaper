<?php
include_once ('lib/func_basedata.php');
include_once ("lib/func_mailer_gmail.php");
include_once ("lib/constants.php");
$mysqli = mysqli_connect(DB_HOSTwp, DB_USERwp, DB_PASSwp, DB_NAMEwp);

// $mysqli = mysqli_connect("localhost", "innomtzv_melvin", "YOUR_DB_PASSWORD", "innomtzv_moosay");
/**
 * Convert a number to its circled Unicode representation.
 *
 * @param int $number The number to convert.
 * @return string The circled representation of the number.
 */
function convertToCircledNumber(int $number): string {
    // Check if the number is within the valid range for circled numbers (1-20)
    if ($number < 1 || $number > 20) {
        throw new InvalidArgumentException("Number must be between 1 and 20 inclusive.");
    }

    // Unicode for circled numbers starts at 9312 for 0
    // Circled numbers for 1-20 are from 9313 to 9324
    return mb_chr(9312 + $number -1); // 9312 is the Unicode for '0', so 9313 corresponds to '1'
}

$basedata = basedata($raceingdate,$venueCode,$resultOddsType);
// print_r($data);

$activeMeetings = $basedata['data']['activeMeetings'];
$Meetings .= "<table>
    <thead>
        <tr>
            <th>Meeting ID</th>
            <th>Venue Code</th>
            <th>Date</th>
            <th>Status</th>
            <th>Races</th>
        </tr>
    </thead>
    <tbody>";
    
    foreach ($activeMeetings as $meeting) {
        $Meetings =  "<tr>";
        $Meetings .= "<td>{$meeting['id']}</td>";
        $Meetings .= "<td>{$meeting['venueCode']}</td>";
        $Meetings .= "<td>{$meeting['date']}</td>";
        $Meetings .= "<td>{$meeting['status']}</td>";
        $Meetings .= "<td><ul>";
        foreach ($meeting['races'] as $race) {
            $Meetings .= "<li>Race {$race['no']}: Post Time: {$race['postTime']} - Status: {$race['status']} (Wagering Field Size: {$race['wageringFieldSize']})</li>";
        }
        $Meetings .= "</ul></td>";
        $Meetings .= "</tr>";
        $allvenue[] = [
            'date' => $meeting['date'],
            'venueCode' => $meeting['venueCode'],
        ];
    }
$Meetings .= "</tbody>";
$Meetings .= "</table>";

// echo $Meetings;

$oddsMerged = [];
foreach ($allvenue as $rsdata) {
    $odds = [];
    $date = $rsdata['date'];
    $venueCode = $rsdata['venueCode'];
    
    $CurrPre = 'Pre';
    $oddsPre = pickodds($oddsarray, $date, $venueCode, $raceNo, $CurrPre);
// print_r($oddsPre);    
    $CurrPre = 'Curr';
    $oddsCurr = pickodds($oddsarray, $date, $venueCode, $raceNo, $CurrPre);
// print_r($oddsCurr);

    // Merging Pre odds
    foreach ($oddsPre as $venue => $races) {
        foreach ($races as $raceno => $horses) {
            foreach ($horses as $horseno => $data) {
                // Copy Pre odds directly
                $oddsMerged[$venue][$raceno][$horseno]['Pre'] = $data['Pre'];
            }
            // Merge additional odds types from horses
            foreach ($horses as $oddsType => $oddsValues) {
                if (!is_numeric($oddsType) && isset($oddsValues)) {
                    // Initialize if not set
                    if (!isset($oddsMerged[$venue][$raceno][$oddsType])) {
                        $oddsMerged[$venue][$raceno][$oddsType] = [];
                    }
                    // Set Pre value
                    $oddsMerged[$venue][$raceno][$oddsType] = $oddsValues;
                }
            }
        }
    }
    
    // Merging Current odds
    foreach ($oddsCurr as $venue => $races) {
        foreach ($races as $raceno => $horses) {
            foreach ($horses as $horseno => $data) {
                // Check if horse entry exists in merged data
                if (!isset($oddsMerged[$venue][$raceno][$horseno])) {
                    // If no Pre odds exist, set Curr odds
                    $oddsMerged[$venue][$raceno][$horseno] = [
                        'Pre' => null, // Default Pre to null
                        'Curr' => $data['Curr'] ?? null // Use null if Curr doesn't exist
                    ];
                } else {
                    // If Pre odds exist, just add Curr odds
                    $oddsMerged[$venue][$raceno][$horseno]['Curr'] = $data['Curr'] ?? null;
                }
            }
    
            // Also merge additional odds types
            foreach ($horses as $oddsType => $oddsValues) {
                if (!is_numeric($oddsType)) {
                    if (!isset($oddsMerged[$venue][$raceno][$oddsType])) {
                        $oddsMerged[$venue][$raceno][$oddsType] = []; // Ensure initialization exists
                    }
                    $oddsMerged[$venue][$raceno][$oddsType] = $oddsValues; // Set the Curr odds for additional odds types
                }
            }
        }
    }
    
    
    // Traverse the array and add 'preprop' and 'prop' keys
    foreach ($oddsMerged[$venue] as $race => $horses) {
        foreach ($horses as $horseno => $details) {
            // Check if 'Pre' exists in the current horse's details
            if (isset($details['Pre'])) {
                $plapre = $details['Pre']['PLAPre'];
                $winpre = $details['Pre']['WINPre'];
    
                // Calculate preprop and add it to the Pre array
                if ($winpre > 0) { // Prevent division by zero
                    $preprop = number_format(100 * $plapre / $winpre, 0);
                    $oddsMerged[$venue][$race][$horseno]['Pre']['preprop'] = $preprop;
                    $oddsMerged[$venue][$race]['maxpreprop'] = ($oddsMerged[$venue][$race]['maxpreprop']>$preprop) ? $oddsMerged[$venue][$race]['maxpreprop'] : $preprop;
                    
                    $preprop1 = number_format(100 * (1/$plapre + 1/$winpre), 0);
                    $oddsMerged[$venue][$race][$horseno]['Pre']['preprop1'] = $preprop1;
                    $oddsMerged[$venue][$race]['maxpreprop1'] = ($oddsMerged[$venue][$race]['maxpreprop1']>$preprop1) ? $oddsMerged[$venue][$race]['maxpreprop1'] : $preprop1;
                } else {
                    $oddsMerged[$venue][$race][$horseno]['Pre']['preprop'] = 0;//null; // Handle division by zero case
                    $oddsMerged[$venue][$race][$horseno]['Pre']['preprop1'] = 0;//null; // Handle division by zero case
                }
            }
    
            // Check if 'Curr' exists in the current horse's details
            if (isset($details['Curr'])) {
                $pla = $details['Curr']['PLA'];
                $win = $details['Curr']['WIN'];
    
                // Calculate prop and add it to the Curr array
                if ($win > 0) { // Prevent division by zero
                    $prop = number_format(100 * $pla / $win, 0);
                    $oddsMerged[$venue][$race][$horseno]['Curr']['prop'] = $prop;
                    $oddsMerged[$venue][$race]['maxcurrprop'] = ($oddsMerged[$venue][$race]['maxcurrprop']>$prop) ? $oddsMerged[$venue][$race]['maxcurrprop'] : $prop;
                } else {
                    $oddsMerged[$venue][$race][$horseno]['Curr']['prop'] = 0;//null; // Handle division by zero case
                }
            }
        }
    }
    
}

$odds = $oddsMerged;
// print_r($odds);

$col = (isset($_GET['mm']) && $_GET['mm']) ? 23 : 20; // Number of columns

foreach ($allvenue as $rsdata) {
    $date = $rsdata['date'];
    $venue = $rsdata['venueCode'];
    $basedata = basedata($date,$venue,$resultOddsType);
    $raceMeetings = $basedata['data']['raceMeetings'][0]['races'];
    // print_r($raceMeetings);
    //-----------------------------------------------------------------------
    $pick = array();
    $hrp_pick2_query = "select * from hrp_pick2
            WHERE racingdate = '$date'
            AND venue = '$venue' ";
    // echo $horseinfo_hints_query;        
    $hrp_pick2_result = mysqli_query($mysqli, $hrp_pick2_query);        
    if(mysqli_num_rows($hrp_pick2_result)>0){
        while($data = mysqli_fetch_assoc($hrp_pick2_result)){
            $pick[$data['raceno']]['pick1'] = $data['pick1'];
            $pick[$data['raceno']]['pick2'] = $data['pick2'];
            $pick[$data['raceno']]['pick3'] = $data['pick3'];
            $pick[$data['raceno']]['pick4'] = $data['pick4'];
        }
    }
    //-----------------------------------------------------------------------
    // print_r($raceMeetings);
    // die();
    foreach ($raceMeetings as $race) { //---------------------------------------------------------------------------------------------------------------------------
        $raceno = $race['no'];
        // Split the date and time
        list($date, $timefull) = explode('T', $race['postTime']);
        list($time, $timezone) = explode('+', $timefull);

        // Race header
        $pageContent .= $venue."..Race:".$raceno."..".$date."..".$time."..".$race['country_ch']."..".$race['distance']."m..".$race['go_ch']."..".$race['raceName_ch'];

        $pageContent .= "<table class='race-table'>";
        $pageContent .= "<thead><tr>";
				for($i = 1; $i<$col; $i++ ){
					$pageContent .= "<th class='order'></th>";
				}
				$pageContent .= "</tr>";
				$pageContent .= "</thead>";
        $trainerCounts = [];
        
        // Loop through each race runner
        foreach ($race['runners'] as $racedetail) {
            // Get the trainer's name
            $trainerName = $racedetail['trainer']['name_ch'];
        
            // Count occurrences of each trainer's name
            if (isset($trainerCounts[$trainerName])) {
                $trainerCounts[$trainerName]++;
            } else {
                $trainerCounts[$trainerName] = 1;
            }
        }

        foreach ($race['runners'] as $racedetail) { //---------------------------------------------------------------------------------------
            $horseno = $racedetail['no'];
            $samepropcount[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['preprop']]++;
            $sameprop1count[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['preprop1']]++;
            $samecurrpropcount[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Curr']['prop']]++;
            $sameprewincount[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['WINPre']]++;
            $PLAPre = number_format($odds[$venue][$raceno][$horseno]['Pre']['PLAPre'], 0);
            if (!isset($samepreplacount[$venue][$raceno][$PLAPre])) {
                $samepreplacount[$venue][$raceno][$PLAPre] = 0;
            }
            $samepreplacount[$venue][$raceno][$PLAPre]++;
            if (isset($odds[$venue][$raceno][$horseno]['Pre']['preprop']) && $odds[$venue][$raceno][$horseno]['Pre']['preprop'] > 20 && $odds[$venue][$raceno][$horseno]['Pre']['preprop'] < 30) {
                $max2x[$venue][$raceno] = max($max2x[$venue][$raceno], $odds[$venue][$raceno][$horseno]['Pre']['preprop']);
                $min2x[$venue][$raceno] = min($min2x[$venue][$raceno], $odds[$venue][$raceno][$horseno]['Pre']['preprop']);
            }
            if (isset($odds[$venue][$raceno][$horseno]['Pre']['preprop']) && $odds[$venue][$raceno][$horseno]['Pre']['preprop'] > 30 && $odds[$venue][$raceno][$horseno]['Pre']['preprop'] < 40) {
                $max3x[$venue][$raceno] = max($max3x[$venue][$raceno], $odds[$venue][$raceno][$horseno]['Pre']['preprop']);
                $min3x[$venue][$raceno] = min($min3x[$venue][$raceno], $odds[$venue][$raceno][$horseno]['Pre']['preprop']);
            }            
        }
        
        if($_GET['mm'] ){
            // Loop through each runner in the $race['runners'] array
            foreach ($race['runners'] as $index => $racedetail) {
                $horseno = $racedetail['no']; // Get the horse number from the runner
            
                // Check if there's corresponding data in the $odds array
                if (isset($odds[$venue][$raceno][$horseno]['Pre']['WINPre'])) {
                    // Add the WINPre value to the runner's details
                    $race['runners'][$index]['WINPre'] = $odds[$venue][$raceno][$horseno]['Pre']['WINPre'];
                } else {
                    // If not found, you can set it to null or leave it out
                    $race['runners'][$index]['WINPre'] = null; // Optional
                }
            }
            
            // //Sort the $race['runners'] array by the 'WINPre' value
            usort($race['runners'], function($a, $b) {
                return $a['WINPre'] <=> $b['WINPre'];
            });
        }

        $redbold = "<b><font color=#FF33FF>";
        // Collect runner rows
        //---------------------------------------------------------------------------------------
        foreach ($race['runners'] as $racedetail) { 
            $horseno = $racedetail['no'];
            $ispla = ($racedetail['finalPosition'] && $racedetail['finalPosition'] <= 4) ? "<b>" : "<font color=lightgrey>"; 
            $ismaxpreprop = ($odds[$venue][$raceno][$horseno]['Pre']['preprop']==$odds[$venue][$raceno]['maxpreprop']) ? $redbold : "";
            $ismaxpreprop1 = ($odds[$venue][$raceno][$horseno]['Pre']['preprop1']==$odds[$venue][$raceno]['maxpreprop1']) ? $redbold : "";
            $ismaxprop = ($odds[$venue][$raceno][$horseno]['Curr']['prop']==$odds[$venue][$raceno]['maxcurrprop']) ? $redbold : "";
            $istopQINTop = !empty($odds[$venue][$raceno]['QIN']['top']) && in_array($horseno, $odds[$venue][$raceno]['QIN']['top']) ? $redbold : "";
            $istopQPLTop = !empty($odds[$venue][$raceno]['QPL']['top']) && in_array($horseno, $odds[$venue][$raceno]['QPL']['top']) ? $redbold : "";
            $istopTRITop = !empty($odds[$venue][$raceno]['TRITop']['top']) && in_array($horseno, $odds[$venue][$raceno]['TRITop']['top']) ? $redbold : "";
            $istopTCETop = !empty($odds[$venue][$raceno]['TCETop']['top']) && in_array($horseno, $odds[$venue][$raceno]['TCETop']['top']) ? $redbold : "";
            $istopFFTop = !empty($odds[$venue][$raceno]['FFTop']['top']) && in_array($horseno, $odds[$venue][$raceno]['FFTop']['top']) ? $redbold : "";
            $istopQTTTop = !empty($odds[$venue][$raceno]['QTTTop']['top']) && in_array($horseno, $odds[$venue][$raceno]['QTTTop']['top']) ? $redbold : "";
            $oddsDropValue = ($odds[$venue][$raceno][$horseno]['Curr']['oddsDropValue']>0) ? number_format($odds[$venue][$raceno][$horseno]['Curr']['oddsDropValue'],0) : null;
            $trainercnt = ($trainerCounts[$racedetail['trainer']['name_ch']]>1) ? (($trainerCounts[$racedetail['trainer']['name_ch']]>2) ? "<font color=red>" : "<font color=blue>") : null;
            // $issamepreprop1 = ($sameprop1count[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['preprop1']]>1) ? 
            //             ($sameprop1count[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['preprop1']]>2 ? "style=\"background-color:lightyellow;font-weight:bold;border-right: thick #66CDFF;\"" : "style=\"background-color:lightyellow;border-right: thick #66CDFF;\"") : "style=\"border-right: thick #66CDFF;\"";
            // $issamepreprop1 = ($sameprop1count[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['preprop1']]>1) ? 
            //             ($sameprop1count[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['preprop1']]>2 ? "style=\"background-color:lightyellow;font-weight:bold;border-right: thick #66CDFF;\"" : "style=\"background-color:lightyellow;border-right: thick #66CDFF;\"") : "style=\"border-right: thick #66CDFF;\"";
            $issamepreprop1 = ($sameprop1count[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['preprop1']]>1) ? 
                        ($sameprop1count[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['preprop1']]>2 ? "style=\"background-color:lightyellow;font-weight:bold;border-right: 2px solid #66CDFF;\"" : "style=\"background-color:lightyellow;border-right: 2px solid #66CDFF;\"") : "style=\"border-right: 2px solid #66CDFF;\"";
            $issamepreprop = ($samepropcount[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['preprop']]>1) ? 
                        ($samepropcount[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['preprop']]>2 ? "style=\"background-color:yellow;font-weight:bold\"" : "style=\"background-color:yellow;\"") : null;
            $issameCurrprop = ($samecurrpropcount[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Curr']['prop']]>1) ? 
                        ($samecurrpropcount[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Curr']['prop']]>2 ? "style=\"background-color:yellow;font-weight:bold;border-right: 2px solid #66CDFF;\"" : "style=\"background-color:yellow;border-right: 2px solid #66CDFF;\"") : "style=\"border-right: 2px solid #66CDFF;\"";                        
            $issameprewin = ($sameprewincount[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['WINPre']]>1) ? 
                        ($sameprewincount[$venue][$raceno][$odds[$venue][$raceno][$horseno]['Pre']['WINPre']]>2 ? "style=\"background-color:yellow;text-align: right;\"" : "style=\"background-color:lightyellow;text-align: right;\"") : "style=\"text-align: right;\"";                                                
            $PLAPre = number_format($odds[$venue][$raceno][$horseno]['Pre']['PLAPre'], 0);
            $PLAPrecnt = $samepreplacount[$venue][$raceno][$PLAPre]*10;
            $colorBase = 240; // Adjust this value to change the base color (higher value = more yellow)
            $colorRange = 40; // Adjust this value to change the color range
            $PLAPreColor = "style=\"background-color: #ffff" . dechex(max(0, min($colorBase + $colorRange, $colorBase + ($PLAPrecnt % $colorRange)))) . "\"";
             
            $ishotFavourite = $odds[$venue][$raceno][$horseno]['Curr']['hotFavourite'] ? "checked" : "";
            $istrumpCard = $odds[$venue][$raceno][$horseno]['Curr']['trumpCard'] ? "checked" : "";
            $ismax2x = ($odds[$venue][$raceno][$horseno]['Pre']['preprop'] == $max2x[$venue][$raceno]) ? "*" : null;
            $ismin2x = ($odds[$venue][$raceno][$horseno]['Pre']['preprop'] == $min2x[$venue][$raceno]) ? "*" : null;
            $ismax3x = ($odds[$venue][$raceno][$horseno]['Pre']['preprop'] == $max3x[$venue][$raceno]) ? "*" : null;
            //-----------------------------------------------------------------------------------------------------------------------------
            $pick1 = in_array($horseno, explode(",", $pick[$raceno]['pick1'])) ? " bgcolor=#99FF99 " : "";
            $pick2 = in_array($horseno, explode(",", $pick[$raceno]['pick2'])) ? " bgcolor=#99FF99 " : "";
            $pick3 = in_array($horseno, explode(",", $pick[$raceno]['pick3'])) ? " bgcolor=#99FF99 " : "";
            $pick4 = in_array($horseno, explode(",", $pick[$raceno]['pick4'])) ? " bgcolor=lightgrey " : "";
            //-----------------------------------------------------------------------------------------------------------------------------

            
            $pageContent .= "<tr class='runner-row' data-race='{$venue}-{$raceno}'>";
            if(isset($_GET['mm']) && $_GET['mm']){
                $pageContent .= "<td $issamepreprop1 >$ismaxpreprop1{$odds[$venue][$raceno][$horseno]['Pre']['preprop1']}</td>";
                $pageContent .= "<td $issamepreprop >$ismax2x$ismax3x$ismaxpreprop{$odds[$venue][$raceno][$horseno]['Pre']['preprop']}$ismin2x</td>";
                $pageContent .= "<td $issameCurrprop >$ismaxprop{$odds[$venue][$raceno][$horseno]['Curr']['prop']}</td>";
            }
            $pageContent .= "<td $issameprewin>{$odds[$venue][$raceno][$horseno]['Pre']['WINPre']}</td>";
            $pageContent .= "<td style='text-align: right;'><b>{$odds[$venue][$raceno][$horseno]['Curr']['WIN']}</td>";
            $pageContent .= "<td><b>{$odds[$venue][$raceno][$horseno]['Curr']['PLA']}</td>";
            $pageContent .= "<td $PLAPreColor>{$odds[$venue][$raceno][$horseno]['Pre']['PLAPre']}</td>";
            $pageContent .= "<td>{$oddsDropValue}</td>";
            $pageContent .= "<td>$istopTRITop{$odds[$venue][$raceno]['TRITop']['countArray'][$horseno]}</td>";
            $pageContent .= "<td>$istopTCETop{$odds[$venue][$raceno]['TCETop']['countArray'][$horseno]}</td>";
            $pageContent .= "<td>$istopTCETop{$odds[$venue][$raceno]['FFTop']['countArray'][$horseno]}</td>";
            $pageContent .= "<td>$istopQTTTop{$odds[$venue][$raceno]['QTTTop']['countArray'][$horseno]}</td>";
            
            $pageContent .= "<td style='text-align: center;'>".$istopQINTop.$istopQPLTop.convertToCircledNumber($horseno)."</td>";
            $pageContent .= "<td>$ispla{$racedetail['finalPosition']}</td>";
            $pageContent .= "<td $pick1><input type=checkbox></td>";
            $pageContent .= "<td $pick2><input type=checkbox $ishotFavourite></td>";
            $pageContent .= "<td $pick3><input type=checkbox $istrumpCard></td>";
            
            $pageContent .= "<td nowrap>{$racedetail['name_ch']}</td>";
            $pageContent .= "<td nowrap>{$racedetail['barrierDrawNumber']}</td>";
            $pageContent .= "<td nowrap>$trainercnt{$racedetail['trainer']['name_ch']}</td>";
            $pageContent .= "<td nowrap>{$racedetail['jockey']['name_ch']}</td>";
            $pageContent .= "<td nowrap>{$racedetail['last6run']}</td>";
            $pageContent .= "</tr>"; // Close the runner row
        }
        $pageContent .= "</tbody>";
        $pageContent .= "</table>";
        // Prepare page data
        // $pageData = [
        //     'post_title'   => "Race Meeting - {$venue} Race No. {$raceno}",
        //     'post_content' => $pageContent,
        //     'post_status'  => 'publish',
        //     'post_author'  => 1, // Change to the appropriate user ID
        //     'post_type'    => 'post', // Change to 'page' for a WordPress page
        //     'post_template' => 'template-custom.php' // Optional: specify a custom page template
        // ];

        // // Insert the page into the database
        // wp_insert_post($pageData);
    }
}
        
echo $pageContent;
if($_GET['mail'] ){
    // $emailtitle = "ai2";
	$ipAddress = substr($_SERVER['SERVER_ADDR'],-3);
	$Subject = "[horsepaper] -".$emailtitle." [AI5] ".$ipAddress;	//$date;
	$Body    = $pageContent;
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
?>

    <style>
        body {
            font-family: Calibri,Tahoma,Arial, sans-serif;
            font-size:13px;
            margin: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-family: Calibri,Tahoma,Arial, sans-serif;
            font-size:13px;
            white-space:nowrap;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 1px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
    </style>


<script type="text/javascript" >

// function table_sort() {
//   const styleSheet = document.createElement('style')
//   styleSheet.innerHTML = `
//         .order-inactive span {
//             visibility:hidden;
//         }
//         .order-inactive:hover span {
//             visibility:visible;
//         }
//         .order-active span {
//             visibility: visible;
//         }
//     `
//   document.head.appendChild(styleSheet)

//   document.querySelectorAll('th.order').forEach(th_elem => {
//     let asc = true
//     const span_elem = document.createElement('span')
//     span_elem.style = "font-size:0.8rem; margin-left:0.5rem"
//     span_elem.innerHTML = "▼"
//     th_elem.appendChild(span_elem)
//     th_elem.classList.add('order-inactive')

//     const index = Array.from(th_elem.parentNode.children).indexOf(th_elem)
//     th_elem.addEventListener('click', (e) => {
//       document.querySelectorAll('th.order').forEach(elem => {
//         elem.classList.remove('order-active')
//         elem.classList.add('order-inactive')
//       })
//       th_elem.classList.remove('order-inactive')
//       th_elem.classList.add('order-active')

//       if (!asc) {
//         th_elem.querySelector('span').innerHTML = '▲'
//       } else {
//         th_elem.querySelector('span').innerHTML = '▼'
//       }
//       const arr = Array.from(th_elem.closest("table").querySelectorAll('tbody tr'))
// 	//   const arr = Array.from(th_elem.closest("table").querySelectorAll('tbody tr')).slice(0)
//       arr.sort((a, b) => {
//         const a_val = (a.children[index].innerText)
//         const b_val = (b.children[index].innerText)
// 		if (asc) {
// 			return parseFloat(a_val) - parseFloat(b_val); // sort by number
// 		}else{
// 			return parseFloat(b_val) - parseFloat(a_val); // sort by number
// 		}
		
// 		// return a_val > b_val ? 1 : -1; // sort by string
//         // return (asc) ? a_val.localeCompare(b_val) : b_val.localeCompare(a_val)
//       })
//       arr.forEach(elem => {
//         th_elem.closest("table").querySelector("tbody").appendChild(elem)
//       })
//       asc = !asc
//     })
//   })
// }

function table_sort() {
    const styleSheet = document.createElement('style');
    styleSheet.innerHTML = `
        .order-inactive span {
            visibility: hidden;
        }
        .order-inactive:hover span {
            visibility: visible;
        }
        .order-active span {
            visibility: visible;
        }
    `;
    document.head.appendChild(styleSheet);

    document.querySelectorAll('th.order').forEach(th_elem => {
        let asc = true;
        const span_elem = document.createElement('span');
        span_elem.style = "font-size:0.8rem; margin-left:0.5rem";
        span_elem.innerHTML = "▼";
        th_elem.appendChild(span_elem);
        th_elem.classList.add('order-inactive');

        const index = Array.from(th_elem.parentNode.children).indexOf(th_elem);
        th_elem.addEventListener('click', (e) => {
            document.querySelectorAll('th.order').forEach(elem => {
                elem.classList.remove('order-active');
                elem.classList.add('order-inactive');
            });
            th_elem.classList.remove('order-inactive');
            th_elem.classList.add('order-active');

            // Toggle the sort direction indicator
            if (!asc) {
                th_elem.querySelector('span').innerHTML = '▲';
            } else {
                th_elem.querySelector('span').innerHTML = '▼';
            }

            const arr = Array.from(th_elem.closest("table").querySelectorAll('tbody tr'));
            arr.sort((a, b) => {
                const a_val = a.children[index].innerText.trim();
                const b_val = b.children[index].innerText.trim();

                // Determine if the column is for circled numbers or regular numbers
                let a_num, b_num;

                // Check if the value is a circled number
                if (isCircledNumber(a_val) && isCircledNumber(b_val)) {
                    a_num = circledToNumber(a_val);
                    b_num = circledToNumber(b_val);
                } else {
                    // Parse regular numbers
                    a_num = parseFloat(a_val) || 0; // Default to 0 if NaN
                    b_num = parseFloat(b_val) || 0; // Default to 0 if NaN
                }

                return asc ? a_num - b_num : b_num - a_num; // sort by number
            });

            arr.forEach(elem => {
                th_elem.closest("table").querySelector("tbody").appendChild(elem);
            });

            asc = !asc;
        });
    });
}

// Function to convert circled number to its numeric value
function circledToNumber(circledChar) {
    const unicodeValue = circledChar.codePointAt(0);
    return unicodeValue - 9312; // 9312 is the Unicode for '0'
}

// Function to check if a string is a circled number
function isCircledNumber(value) {
    const circledNumbers = ['①', '②', '③', '④', '⑤', '⑥', '⑦', '⑧', '⑨', '⑩', '⑪', '⑫', '⑬', '⑭', '⑮', '⑯', '⑰', '⑱', '⑲', '⑳'];
    return circledNumbers.includes(value);
}

table_sort();
</script>