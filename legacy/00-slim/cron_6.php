<?php
error_reporting(E_ALL & ~E_NOTICE); //only in log

require_once('lib/mossql.php');	
require_once("lib/func_mailer_gmail.php");
require_once("lib/constants.php");
$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$list = "<div>";

// function displaySpiralGrid($highlightNumbers, $highlight_estall, $highlight_est49, $highlight_max) {
//     $size = 7; // Grid size
//     $grid = array_fill(0, $size, array_fill(0, $size, 0)); // Create a 7x7 grid

//     $x = $y = $size / 2; // Start from the center
//     $directions = [[0, 1], [1, 0], [0, -1], [-1, 0]]; // Right, Down, Left, Up
//     $dir = 0; // Start with the first direction
//     $steps = 1; // Number of steps in the current direction
//     $num = 1; // Start number

//     while ($num <= 49) {
//         for ($i = 0; $i < 2; $i++) { // Increase steps after completing two directions
//             for ($j = 0; $j < $steps; $j++) {
//                 if ($num > 49) break; // Stop if number exceeds 49
//                 $grid[$y][$x] = $num++;
//                 $x += $directions[$dir][0]; // Move in the current direction
//                 $y += $directions[$dir][1];
//             }
//             $dir = ($dir + 1) % 4; // Change direction
//         }
//         $steps++; // Increase steps after completing two directions
//     }

//     // Check for row and column highlights
//     $rowCounts = array_fill(0, $size, 0);
//     $colCounts = array_fill(0, $size, 0);

//     foreach ($highlightNumbers as $number) {
//         foreach ($grid as $i => $row) {
//             foreach ($row as $j => $cell) {
//                 if ($cell == $number) {
//                     $rowCounts[$i]++;
//                     $colCounts[$j]++;
//                 }
//             }
//         }
//     }

//     // Collect the grid output
//     $output = '';
//     $output .= "<div class=\"container\">";
//     $output .= "<div class=\"grid\">";

//     foreach ($grid as $i => $row) {
//         foreach ($row as $j => $cell) {
//             $rowClass = $rowCounts[$i] >= 2 ? 'row-highlight' : '';
//             $colClass = $colCounts[$j] >= 2 ? 'col-highlight' : '';
//             $highlightClass = in_array($cell, $highlightNumbers) ? 'highlight' : '';
//             $underlineClass = in_array($cell, $highlight_estall) ? 'underline' : '';
//             $doubleUnderlineClass = ($cell == $highlight_est49) ? 'double-underline' : '';
//             $boldClass = ($cell == $highlight_max) ? 'bold' : '';
//             $fadedClass = ($rowClass || $colClass || $highlightClass || $underlineClass || $doubleUnderlineClass || $boldClass) ? '' : 'faded'; // Apply faded class if in highlighted row or column
            
//             $output .= "<div class='grid-item $rowClass $colClass $highlightClass $underlineClass $doubleUnderlineClass $boldClass $fadedClass'>$cell</div>";
//         }
//     }

//     $output .= "</div>"; // Close grid div
//     $output .= "</div>"; // Close container div
//     return $output; // Return the collected output
// }

function displaySpiralGrid($highlightNumbers, $highlight_estall, $highlight_est49, $highlight_max) {
    $size = 7; // Grid size
    $grid = array_fill(0, $size, array_fill(0, $size, 0)); // Create a 7x7 grid

    $x = $y = $size / 2; // Start from the center
    $directions = [[0, 1], [1, 0], [0, -1], [-1, 0]]; // Right, Down, Left, Up
    $directions = [[1, 0], [0, 1], [-1, 0], [0, -1]]; // Right, Down, Left, Up (clockwise)
    $dir = 0; // Start with the first direction
    $steps = 1; // Number of steps in the current direction
    $num = 1; // Start number

    while ($num <= 49) {
        for ($i = 0; $i < 2; $i++) { // Increase steps after completing two directions
            for ($j = 0; $j < $steps; $j++) {
                if ($num > 49) break; // Stop if number exceeds 49
                $grid[$y][$x] = $num++;
                $x += $directions[$dir][0]; // Move in the current direction
                $y += $directions[$dir][1];
            }
            $dir = ($dir + 1) % 4; // Change direction
        }
        $steps++; // Increase steps after completing two directions
    }

    // Check for row and column highlights
    $rowCounts = array_fill(0, $size, 0);
    $colCounts = array_fill(0, $size, 0);

    foreach ($highlightNumbers as $number) {
        foreach ($grid as $i => $row) {
            foreach ($row as $j => $cell) {
                if ($cell == $number) {
                    $rowCounts[$i]++;
                    $colCounts[$j]++;
                }
            }
        }
    }

    // Collect the grid output
    $output = '';
    $output .= "<div style='display: flex; justify-content: center; align-items: center; height: 42vh; background-color: #f5f5f5;'>";
    $output .= "<div style='display: grid; grid-template-columns: repeat(7, 50px); grid-gap: 5px;'>";

    foreach ($grid as $i => $row) {
        foreach ($row as $j => $cell) {
            $rowClass = $rowCounts[$i] >= 2 ? 'background-color: lightyellow;' : '';
            $colClass = $colCounts[$j] >= 2 ? 'background-color: lightyellow;' : '';   //lightgreen
            $highlightClass = in_array($cell, $highlightNumbers) ? 'color: red;' : '';
            $underlineClass = in_array($cell, $highlight_estall) ? 'text-decoration: underline;' : '';
            // $doubleUnderlineClass = ($cell == $highlight_est49) ? 'text-decoration: underline double; color: green; ' : '';
            $doubleUnderlineClass = in_array($cell, $highlight_est49) ? 'text-decoration: underline double;' : '';
            $boldClass = ($cell == $highlight_max) ? 'font-weight: bold;' : '';
            // $fadedClass = ($rowClass || $colClass) ? 'opacity: 0.5;' : ''; // Apply faded class if in highlighted row or column
            $fadedClass = ($rowClass || $colClass || $highlightClass || $underlineClass || $doubleUnderlineClass || $boldClass) ? '' : 'opacity: 0.5;'; // Apply faded class if in highlighted row or column
            
            $style = "width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; font-size: 20px; background-color: #e0e0e0; $rowClass $colClass $highlightClass $underlineClass $doubleUnderlineClass $boldClass $fadedClass";
            $output .= "<div style='$style'>$cell</div>";
        }
    }

    $output .= "</div>"; // Close grid div
    $output .= "</div>"; // Close container div
    return $output; // Return the collected output
}

        
function estimateNo($data, $nos) {
    // Check if the given nos exists in the data
    if (array_key_exists($nos, $data)) {
        return max(1, min(49, $data[$nos])); // Ensure it's between 1 and 49
    }

    // If nos is not found, estimate based on surrounding values
    $lower = $nos - 1;
    $upper = $nos + 1;

    $lowerValue = array_key_exists($lower, $data) ? $data[$lower] : null;
    $upperValue = array_key_exists($upper, $data) ? $data[$upper] : null;

    // Calculate the average of the surrounding values if they exist
    $values = [];
    if ($lowerValue !== null) {
        $values[] = $lowerValue;
    }
    if ($upperValue !== null) {
        $values[] = $upperValue;
    }

    if (empty($values)) {
        return null; // No surrounding values found
    }

    // Calculate the average and constrain it between 1 and 49
    $estimatedValue = array_sum($values) / count($values);
    return max(1, min(49, round($estimatedValue))); // Ensure it's between 1 and 49
}

function estimateNox($data, $nos) {

    // Check if the given nos exists in the data
    if (array_key_exists($nos, $data)) {
        return max(1, min(49, $data[$nos])); // Ensure it's between 1 and 49
    }

    // Initialize lower and upper values
    $lower = null;
    $upper = null;

    // Find the nearest lower and upper values
    foreach ($data as $key => $value) {
        if ($key < $nos) {
            $lower = [$key, $value];
        } elseif ($key > $nos) {
            if ($upper === null) {
                $upper = [$key, $value];
            }
        }
    }

    // Check if both lower and upper values are available
    if ($lower && $upper) {
        list($lowerKey, $lowerValue) = $lower;
        list($upperKey, $upperValue) = $upper;

        // Linear interpolation formula
        $slope = ($upperValue - $lowerValue) / ($upperKey - $lowerKey);
        $estimatedValue = $lowerValue + $slope * ($nos - $lowerKey);

        // Ensure the estimated value is between 1 and 49
        return max(1, min(49, round($estimatedValue)));
    } elseif ($lower) {
        // If only lower is found, return its value
        return max(1, min(49, $lower[1]));
    } elseif ($upper) {
        // If only upper is found, return its value
        return max(1, min(49, $upper[1]));
    }

    return null; // No estimation available if no suitable values are found
}



/**
 * Get the next number in a circular array.
 *
 * @param array $numbers The array of numbers.
 * @param int $currentIndex The current index in the array.
 * @return int The next number in the circular array.
 */
function getNextInCircle(array $numbers, int $currentIndex) {
    if (empty($numbers)) {
        throw new InvalidArgumentException("The numbers array cannot be empty.");
    }

    // Calculate the next index
    $nextIndex = ($currentIndex + 1) % count($numbers);
    
    // Return the number at the next index
    return $numbers[$nextIndex];
}

// Example usage
$numbers = [1, 2, 3, 4, 5];
$currentIndex = 0;

// for ($i = 0; $i < 10; $i++) {
//     $currentNumber = $numbers[$currentIndex];
//     echo "Current Number: $currentNumber\n";
    
//     // Get the next number in the circle
//     $currentNumber = getNextInCircle($numbers, $currentIndex);
    
//     // Update the current index
//     $currentIndex = ($currentIndex + 1) % count($numbers); // Move to the next index
// }

function posestnext($x) {
    /**
     * Calculates the result based on the given inputs.
     * 
     * @param int $x The first input value.
     * @param int $y The second input value.
     * @return int The calculated result.
     */
	$y = $x;
    $sum = $x + $y;
    if ($sum > 49) {
        return abs(49 - $x);
    } else {
        return $sum;
    }
}

function excelTrend($knownY, $knownX, $newX){
    $n = count($knownY);
    
    // Calculate the sums of arrays
    $sumY = array_sum($knownY);
    $sumX = array_sum($knownX);
    $sumXY = 0;
    $sumX2 = 0;
    
    for ($i = 0; $i < $n; $i++) {
        $sumXY += $knownX[$i] * $knownY[$i];
        $sumX2 += pow($knownX[$i], 2);
    }
    
    // Calculate the slope and intercept
    $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - pow($sumX, 2));
    $intercept = ($sumY - $slope * $sumX) / $n;
    
    // Calculate the new Y values
    $newY = array();
    foreach ($newX as $x) {
        $newY[] = $intercept + $slope * $x;
    }
    
    return $newY;
}

function predictNextValue1($knownX, $knownY)
{
    // Calculate the slope and intercept using linear regression
    $slope = (count($knownX) * array_sum(array_map('product', $knownX, $knownY)) - array_sum($knownX) * array_sum($knownY))
        / (count($knownX) * array_sum(array_map('square', $knownX)) - pow(array_sum($knownX), 2));
    
    $intercept = (array_sum($knownY) - $slope * array_sum($knownX)) / count($knownX);
    
    // Predict the next value in the sequence
    $nextX = end($knownX) + 1;
    $nextY = $slope * $nextX + $intercept;
    
    return $nextY;
}

function product($a, $b)
{
    return $a * $b;
}

function square($a)
{
    return $a * $a;
}

// Test the function with the given arrays
// $knownY = [46, 31, 9, 38, 6, 41, 2, 3, 30, 5, 8, 20, 12, 32, 15, 7, 24, 26, 26, 27, 34, 2, 24, 41, 35, 11, 39, 32, 16, 24, 23, 30, 18, 20, 42, 2, 6, 42, 12, 13, 30, 9, 12, 17, 26, 7, 37, 3, 28];
// $knownX = [1, 2, 3, 5, 4, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 33, 32, 34, 35, 36, 37, 38, 40, 39, 41, 42, 43, 44, 45, 46, 47, 48];

// $nextValue = predictNextValue($knownX, $knownY);

// echo "The predicted next value is: " . round($nextValue, 2);

// function predictNextValue($arr) {
//     print_r($arr);
//     $x = range(0, count($arr) - 1);
//     $y = $arr;

//     $sumX = array_sum($x);
//     $sumY = array_sum($y);
//     $sumXY = 0;
//     $sumX2 = 0;

//     for ($i = 0; $i < count($arr); $i++) {
//         $sumXY += ($x[$i] * $y[$i]);
//         $sumX2 += ($x[$i] * $x[$i]);
//     }

//     $n = count($arr);
//     $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
//     $intercept = ($sumY - $slope * $sumX) / $n;

//     $nextX = count($arr);
//     $nextValue = $slope * $nextX + $intercept;

//     return $nextValue;
// }
function predictNextValue($arr) {
    // print_r($arr);
    
    $x = range(0, count($arr) - 1);
    $y = $arr;

    $sumX = array_sum($x);
    $sumY = array_sum($y);
    $sumXY = 0;
    $sumX2 = 0;

    for ($i = 0; $i < count($arr); $i++) {
        $sumXY += ($x[$i] * $y[$i]);
        $sumX2 += ($x[$i] * $x[$i]);
    }

    $n = count($arr);
    $denominator = ($n * $sumX2 - $sumX * $sumX);

    // Check for division by zero
    if ($denominator == 0) {
        // Handle the case where the denominator is zero
        return null; // Or some default value, or throw an exception
    }

    $slope = ($n * $sumXY - $sumX * $sumY) / $denominator;
    $intercept = ($sumY - $slope * $sumX) / $n;

    $nextX = count($arr);
    $nextValue = $slope * $nextX + $intercept;

    return $nextValue;
}
// Example usage
// $arr = [46, 31, 9, 38, 6, 41, 2, 3, 30, 5, 8, 20, 12, 32, 15, 7, 24, 26, 26, 27, 34, 2, 24, 41, 35, 11, 39, 32, 16, 24, 23, 30, 18, 20, 42, 2, 6, 42, 12, 13, 30, 9, 12, 17, 26, 7, 37, 3, 28];
// $nextValue = predictNextValue($arr);
// echo "Predicted next value: " . $nextValue;


// https://bet.hkjc.com/marksix/getJSON.aspx?sd=20190101&ed=20190331&sb=0
$fil = fopen ("http://www.smartmark6.com/Update.dat", "r");// xyz.dat file url

    while ($filedata = fgets($fil)) {
        $d = explode("+", $filedata);
        $year = substr($d[0], 0, 4);
        $nos = $d[1];
        $query = "SELECT * FROM 00m6 WHERE year='$year' AND nos='$nos'";
        $result = $mysqli->query($query);

        if ($result) {
            $row = $result->fetch_assoc();
            if ($row) {
                // The record already exists, skip the insert
                continue;
            } else {
                // The record does not exist, perform the insert
                $insert = "INSERT INTO 00m6 VALUES ('', '$year', '$nos', '$d[2]', '$d[3]', '$d[4]', '$d[5]', '$d[6]', '$d[7]', '$d[8]', '$d[9]')";
                if (!$mysqli->query($insert)) {
                    // Display the error if insert fails
                    echo "Insert Error: " . $mysqli->error . "\n";
                }
            }
        } else {
            // Display the error if the select query fails
            echo "Select Error: " . $mysqli->error . "\n";
        }
    }
fclose($fil);
//576 123
/*********************************************************************************************/
// $laterestq = $db->get_row("select (id) as aa,nos from 00m6 order by year desc, nos desc limit 1");
// $laterestq = $db->get_row("select (id) as aa,nos from 00m6 order by concat(year,nos) desc limit 1");
// $drawnos = $laterestq->nos + 1;
// $feq = $laterestq->aa;
// Query to get the latest record
$query = "SELECT id AS aa, nos FROM 00m6 ORDER BY CONCAT(year, nos) DESC LIMIT 1";
$result = $mysqli->query($query);

if ($result) {
    $laterestq = $result->fetch_object();
    if ($laterestq) {
        $drawnos = $laterestq->nos + 1;
        $feq = $laterestq->aa;
    } else {
        // Handle case where no rows are returned
        $drawnos = 1; // Or some default value
        $feq = null; // Or some default value
    }
} else {
    // Display the error if the select query fails
    echo "Select Error: " . $mysqli->error . "\n";
    // Handle error appropriately, maybe set default values or exit
    $drawnos = 1; // Or some default value
    $feq = null; // Or some default value
}
//***********************************************************************
//second-to-last row 
$sql = "SELECT *
        FROM 00m6 
        ORDER BY CONCAT(year, LPAD(nos, 3, '0')) DESC
        LIMIT 1 OFFSET 1";
$secondtolast = $db->get_row($sql);
//***********************************************************************
// $laterestquery = $db->get_row("select * from 00m6 order by id desc limit 1");
// //print_r($laterestquery);
// $la=array();
// array_push($la,
//     $laterestquery->no1,
//     $laterestquery->no2,
//     $laterestquery->no3,
//     $laterestquery->no4,
//     $laterestquery->no5,
//     $laterestquery->no6,
//     $laterestquery->no7
// );
// $query = "SELECT * FROM 00m6 ORDER BY CONCAT(year, nos) DESC LIMIT 1";
$query = "SELECT * FROM 00m6 ORDER BY year desc, nos DESC LIMIT 1";
$result = $mysqli->query($query);
$la=array();
if ($result) {
    $row = $result->fetch_assoc();
    if ($row) {
        $laterest_year = $row['year'];
        $laterest_nos = $row['nos'];
        $la = array(
            $row['no1'],
            $row['no2'],
            $row['no3'],
            $row['no4'],
            $row['no5'],
            $row['no6'],
            $row['no7']
        );

        // Now you can use the $la array as needed
        // print_r($la);
    } else {
        echo "No rows found.";
    }
} else {
    echo "Error executing query: " . $mysqli->error;
}

// print_r($la);
//echo $feq;
function islastdraw($nos,$q){
	if (in_array($nos, $q)) {
		// return "<p style=\"background-color: yellow; \"><strike>".$nos."</strike></p></b>";
		return "<font style1=\"background-color: yellow; \"><strike>".$nos."</strike></font>";
	}else{
		return $nos;
	}
}
$list .=  "Last draw : ".$laterest_year."[".$laterest_nos."] ".$la[0]."-".$la[1]."-".$la[2]."-".$la[3]."-".$la[4]."-".$la[5]."-".$la[6];
/*********************************************************************************************/
$sql = "select year,nos,no1,no2,no3,no4,no5,no6,no7 from 00m6 order by concat(year,nos) desc";
$sql = "SELECT year, nos, no1, no2, no3, no4, no5, no6, no7 
       FROM 00m6
       WHERE 1	/*nos BETWEEN '000' AND '999'*/
       ORDER BY CONCAT(year, LPAD(nos, 4, '0')) DESC";
$t = $db->get_results($sql);
//print_r($t);
$cnt49=0;
foreach($t as $draw) {
	$drawno[] = $draw->nos;
	$dig1[] = $draw->no1;
	$dig2[] = $draw->no2;
	$dig3[] = $draw->no3;
	$dig4[] = $draw->no4;
	$dig5[] = $draw->no5;
	$dig6[] = $draw->no6;
	$dig7[] = $draw->no7;
	
	$key = $draw->year.($draw->nos<10 ? "00" : ($draw->nos<100 ? "0" : "")).$draw->nos;
	$history1[$key]=$draw->no1;
	$history2[$key]=$draw->no2;
	$history3[$key]=$draw->no3;
	$history4[$key]=$draw->no4;
	$history5[$key]=$draw->no5;
	$history6[$key]=$draw->no6;
	$history7[$key]=$draw->no7;
	if($cnt49<49){
    	$history1_49[$key]=$draw->no1;
    	$history2_49[$key]=$draw->no2;
    	$history3_49[$key]=$draw->no3;
    	$history4_49[$key]=$draw->no4;
    	$history5_49[$key]=$draw->no5;
    	$history6_49[$key]=$draw->no6;
    	$history7_49[$key]=$draw->no7;
    	$cnt49++;
	}
	//----
	if($draw->year==date('Y')){
		$drawnocurryr[] = $draw->nos;
		$dig1curryr[] = $draw->no1;
		$dig2curryr[] = $draw->no2;
		$dig3curryr[] = $draw->no3;
		$dig4curryr[] = $draw->no4;
		$dig5curryr[] = $draw->no5;
		$dig6curryr[] = $draw->no6;
		$dig7curryr[] = $draw->no7;
	}
	$dig1count[$draw->no1]++ ;
	$dig2count[$draw->no2]++ ;
	$dig3count[$draw->no3]++ ;
	$dig4count[$draw->no4]++ ;
	$dig5count[$draw->no5]++ ;
	$dig6count[$draw->no6]++ ;
	$dig7count[$draw->no7]++ ;
	if($draw->year <> $laterestquery->year && $draw->nos <> $laterestquery->nos ){
		$dig1countnolast[$draw->no1]++ ;
		$dig2countnolast[$draw->no2]++ ;
		$dig3countnolast[$draw->no3]++ ;
		$dig4countnolast[$draw->no4]++ ;
		$dig5countnolast[$draw->no5]++ ;
		$dig6countnolast[$draw->no6]++ ;
		$dig7countnolast[$draw->no7]++ ;
	}

}
// print_r($history1);
$first_key = key($history1);
$first_key++;

$est1 = estimateNox($history1, $first_key);
$est2 = estimateNox($history2, $first_key);
$est3 = estimateNox($history3, $first_key);
$est4 = estimateNox($history4, $first_key);
$est5 = estimateNox($history5, $first_key);
$est6 = estimateNox($history6, $first_key);
$est7 = estimateNox($history7, $first_key);
$list .=  "<hr>";
$list .=  "estall: ".$est1."+".$est2."+".$est3."+".$est4."+".$est5."+".$est6."+".$est7;
$highlight_estall = [$est1, $est2, $est3, $est4, $est5, $est6, $est7];

// print_r($history1_49);
$est1 = estimateNox($history1_49, $first_key);
$est2 = estimateNox($history2_49, $first_key);
$est3 = estimateNox($history3_49, $first_key);
$est4 = estimateNox($history4_49, $first_key);
$est5 = estimateNox($history5_49, $first_key);
$est6 = estimateNox($history6_49, $first_key);
$est7 = estimateNox($history7_49, $first_key);
$list .=  "<br>";
$list .=  "est49: ".$est1."+".$est2."+".$est3."+".$est4."+".$est5."+".$est6."+".$est7;
$highlight_est49 = [$est1, $est2, $est3, $est4, $est5, $est6, $est7];

function sortFirstXvalues($array) {
	arsort($array);
	foreach ($array as $key => $value) {
		if ($counter < 30) {
			$firstXvalue[] = $key;
			$counter++;
        } else {
            break;
        }
	}
	return $firstXvalue;
}
$first30 = array_merge(sortFirstXvalues($dig1count),sortFirstXvalues($dig2count),sortFirstXvalues($dig3count),sortFirstXvalues($dig4count),sortFirstXvalues($dig5count),sortFirstXvalues($dig6count),sortFirstXvalues($dig7count)) ;
foreach ($first30 as $key => $value) {
	$first30max[$value]++;
}
arsort($first30max);
// print_r($first30max);
$counter = 0;
foreach ($first30max as $key => $value) {
	if ($counter < 7) {
		$first30max10[] = $key;
		$counter++;
	} else {
		break;
	}
}

$list .=  "<hr>first30max7: ";
foreach ($first30max10 as $value) {
	$list .= $value."; ";
	$first30max10arr[] .= $value;
}

function displayFirstTenValues($rowhead,$array,$lastdraw,$first30max10,$nolast,$lastposition,$posest,$secondtolast) {
	arsort($array);
    $list .= "<tr>";
	$list .= "<td bgcolor=lightgrey>".$rowhead;
    $counter = 0;
    foreach ($array as $key => $value) {
        if ($counter < 50) {
            $alert_secondtolast = ($nolast && $key == $secondtolast) ? "<b><font color=#66FF00>" : "";
			$alert_first30max10 = (in_array($key,$first30max10)) ? "<font color=red>" : "";
			$alert_lastnobyorder = ($nolast && $key == $lastdraw[$rowhead-1]) ? " bgcolor=yellow " : "";
			$alert_lastposition = (!$nolast && $lastposition[$rowhead] == $counter+1) ? " bgcolor=pink " : "";//$lastposition[1].$counter;
			$alert_posest = (!$nolast && $counter+1 == abs($posest)) ? " bgcolor=yellow " : "";
            $list .= "<td align=right nowrap $alert_lastnobyorder $alert_lastposition $alert_posest >" . $alert_secondtolast . $alert_first30max10.($nolast ? $key : islastdraw($key,$lastdraw)) . "</td>";
			if($nolast && $key == $lastdraw[$rowhead-1]){ 
				$lastpos[$rowhead] = $counter+1;
			}
            $counter++;
        } else {
            break;
        }
    }
	// return $list;
	return ['list' => $list, 'lastpos' => $lastpos];
}
$list .= "</div>";
//------------------------------------------------------------------------------------------------
        $highlightNumbers = [2, 8, 15, 19, 22, 34]; // Numbers to highlight in red
        $highlightNumbers = $la;
        // print_r($highlight_est49); 
        $highlight_max = $first30max10;
        $list .= displaySpiralGrid($highlightNumbers, $highlight_estall, $highlight_est49, $highlight_max);
//------------------------------------------------------------------------------------------------    
$list .= "<div>";
$list .= "<hr>(nolast)";
$list .= "<table border=1 bordercolor=gray cellpadding=\"5\" cellspacing=\"0\" style=\"border-collapse: collapse;vertical-align:top\">";
$list .= "<tr><td>";
for ($i = 1; $i <= 49; $i++) {
    $list .= "<td style=\"opacity: 0.5;\">".$i;
}
$result1 = displayFirstTenValues(1,$dig1countnolast,$la,$first30max10,1,0,0,$secondtolast->no1);
$list .= $result1['list'];
$result2 = displayFirstTenValues(2,$dig2countnolast,$la,$first30max10,1,0,0,$secondtolast->no2);
$list .= $result2['list'];
$result3 = displayFirstTenValues(3,$dig3countnolast,$la,$first30max10,1,0,0,$secondtolast->no3);
$list .= $result3['list'];
$result4 = displayFirstTenValues(4,$dig4countnolast,$la,$first30max10,1,0,0,$secondtolast->no4);
$list .= $result4['list'];
$result5 = displayFirstTenValues(5,$dig5countnolast,$la,$first30max10,1,0,0,$secondtolast->no5);
$list .= $result5['list'];
$result6 = displayFirstTenValues(6,$dig6countnolast,$la,$first30max10,1,0,0,$secondtolast->no6);
$list .= $result6['list'];
$result7 = displayFirstTenValues(7,$dig7countnolast,$la,$first30max10,1,0,0,$secondtolast->no7);
$list .= $result7['list'];
$list .= "</tr></table>";
// print_r($result2);
$list .= "pos:".$result1['lastpos'][1]."-".$result2['lastpos'][2]."-".$result3['lastpos'][3]."-".$result4['lastpos'][4]."-".$result5['lastpos'][5]."-".$result6['lastpos'][6]."-".$result7['lastpos'][7];
$list .= "<br>pos_est:".posestnext($result1['lastpos'][1])."-";
$list .= posestnext($result2['lastpos'][2])."-";
$list .= posestnext($result3['lastpos'][3])."-";
$list .= posestnext($result4['lastpos'][4])."-";
$list .= posestnext($result5['lastpos'][5])."-";
$list .= posestnext($result6['lastpos'][6])."-";
$list .= posestnext($result7['lastpos'][7]);

$list .= "<hr>est";
$list .= "<table border=1 bordercolor=gray cellpadding=\"5\" cellspacing=\"0\" style=\"border-collapse: collapse;vertical-align:top\">";
$list .= "<tr><td>";
for ($i = 1; $i <= 49; $i++) {
    $list .= "<td style=\"opacity: 0.5;\">".$i;
}
$result11 = displayFirstTenValues(1,$dig1count,$la,$first30max10,0,$result1['lastpos'],posestnext($result1['lastpos'][1]),$secondtolast->no1);
$list .= $result11['list'];
$result22 = displayFirstTenValues(2,$dig2count,$la,$first30max10,0,$result2['lastpos'],posestnext($result2['lastpos'][2]),$secondtolast->no2);
$list .= $result22['list'];
$result33 = displayFirstTenValues(3,$dig3count,$la,$first30max10,0,$result3['lastpos'],posestnext($result3['lastpos'][3]),$secondtolast->no3);
$list .= $result33['list'];
$result44 = displayFirstTenValues(4,$dig4count,$la,$first30max10,0,$result4['lastpos'],posestnext($result4['lastpos'][4]),$secondtolast->no4);
$list .= $result44['list'];
$result55 = displayFirstTenValues(5,$dig5count,$la,$first30max10,0,$result5['lastpos'],posestnext($result5['lastpos'][5]),$secondtolast->no5);
$list .= $result55['list'];
$result66 = displayFirstTenValues(6,$dig6count,$la,$first30max10,0,$result6['lastpos'],posestnext($result6['lastpos'][6]),$secondtolast->no6);
$list .= $result66['list'];
$result77 = displayFirstTenValues(7,$dig7count,$la,$first30max10,0,$result7['lastpos'],posestnext($result7['lastpos'][7]),$secondtolast->no7);
$list .= $result77['list'];
$list .= "</tr></table>";

// arsort($dig1count);
// print_r($dig1count);
// print_r($dig1curryr);
// print_r($drawnocurryr);
// print_r($dig2curryr);
// print_r($drawnocurryr);

if(!empty($drawnocurryr)){
    $list .= "The predicted next draw is: ";
    $list .= round(predictNextValue($dig1curryr),0);
    $list .= " + ". round(predictNextValue($dig2curryr),0);
    $list .= " + ". round(predictNextValue($dig3curryr),0);
    $list .= " + ". round(predictNextValue($dig4curryr),0);
    $list .= " + ". round(predictNextValue($dig5curryr),0);
    $list .= " + ". round(predictNextValue($dig6curryr),0);
    $list .= " + ". round(predictNextValue($dig7curryr),0);
}

foreach($t as $array) {
	$no1[$array->no1]++;
	$no2[$array->no2]++;
	$no3[$array->no3]++;
	$no4[$array->no4]++;
	$no5[$array->no5]++;
	$no6[$array->no6]++;
	$no7[$array->no7]++;
	foreach($array as $k=>$v) {
		$newArray[] = $v;
		$cnt[$v]++; 
	}
}
arsort ($no1);
foreach($no1 as $key => $value1){
  $sel1[] = $key;
}
//print_r ($no1);

arsort ($no2);
foreach($no2 as $key => $value2){
  $sel2[] = $key;
}
arsort ($no3);
foreach($no3 as $key => $value3){
  $sel3[] = $key;
}
arsort ($no4);
foreach($no4 as $key => $value4){
  $sel4[] = $key;
}
arsort ($no5);
foreach($no5 as $key => $value5){
  $sel5[] = $key;
}
arsort ($no6);
foreach($no6 as $key => $value6){
  $sel6[] = $key;
}
arsort ($no7);
foreach($no7 as $key => $value7){
  $sel7[] = $key;
}
/*********************************************************************************************/
$arr_keys = array_keys($cnt);
$arr_values = array_values($cnt);


arsort($cnt);
foreach($cnt as $key => $value){
  $sel[] = $key;
}

$m7=array();	//most 7 nos
for ($i = 0; $i <= 7;/*count($cnt) - 1;*/ $i++) {
	array_push($m7,$sel[$i]);
}

/*********************************************************************************************/
$last7 = "select no1,no2,no3,no4,no5,no6,no7 from 00m6 order by year desc, nos desc limit 7";//limit ".$feq;
$lquery = $db->get_results($last7);
$L7 = array(); // Initialize the main array

foreach ($lquery as $array) {
    // Ensure each sub-array is initialized before using array_push
    if (!isset($L7[1])) $L7[1] = array();
    if (!isset($L7[2])) $L7[2] = array();
    if (!isset($L7[3])) $L7[3] = array();
    if (!isset($L7[4])) $L7[4] = array();
    if (!isset($L7[5])) $L7[5] = array();
    if (!isset($L7[6])) $L7[6] = array();
    if (!isset($L7[7])) $L7[7] = array();

    array_push($L7[1], $array->no1);
    array_push($L7[2], $array->no2);
    array_push($L7[3], $array->no3);
    array_push($L7[4], $array->no4);
    array_push($L7[5], $array->no5);
    array_push($L7[6], $array->no6);
    array_push($L7[7], $array->no7);
}

// $counts = array_count_values($array);
// echo $counts['Ben'];

$mostnos=array();
function islt($nos,$q,$most,$last7){
	$rem ="";
	if (in_array($nos, $q)) {		//mostly per nos
		$rem = "<font color=red><b>";
	}
	if (in_array($nos, $most)) {	//mostly per all
		$rem .= "<u>";
	}else{
		$rem .= "</u>";
	}

	if (in_array($nos, $last7)) {	//last 7 draw
		$rem .= "<strike>";
	}else{
		$rem .= "</strike>";
	}
	if (!in_array($nos, $last7) && !in_array($nos, $most) && !in_array($nos, $q)) {
		$rem .= "<font color=green>";
		if (!isset($mostnos)) $mostnos = array();
		array_push($mostnos,$nos);
	}
	if (count(array_keys($last7, $nos))){
		// $rem .= "<p style=\"background-color:yellow;\">";
	}
	$frequence = count(array_keys($last7, $nos));
	return $rem.$nos.($frequence ? "</u></strike><br>(".$frequence.")" : "");
}


// print_r($sel);


/*
1	2	3	4	5	7
1	2	3	6	7	8
1	4	5	6	7	8
2	3	4	5	6	8
*/
$ran = substr($laterest,0,2);
//echo $ran;
$list .= "<hr>";
$list .= "<table border=1 bordercolor=gray cellpadding=\"5\" cellspacing=\"0\" style=\"border-collapse: collapse;vertical-align:top\">";
$list .= "<tr><td>timesrank<td>no1<td>no2<td>no3<td>no4<td>no5<td>no6<td>no7";
for ($i = 1; $i <= 49; $i++) {
	$list .= "<tr><td>[".$i."] ";
	$pos_last = ($i == $result1['lastpos'][1]) ? "bgcolor=pink" : "";
	$pos_est = ($i == posestnext($result1['lastpos'][1])) ? "bgcolor=yellow" : "";
	$list .= "<td $pos_last $pos_est>".islt($sel1[$i-1],$la,$m7,$L7[1]);

	$pos_last = ($i == $result2['lastpos'][2]) ? "bgcolor=pink" : "";
	$pos_est = ($i == posestnext($result2['lastpos'][2])) ? "bgcolor=yellow" : "";	
	$list .= "<td $pos_last $pos_est>".islt($sel2[$i-1],$la,$m7,$L7[2]);

	$pos_last = ($i == $result3['lastpos'][3]) ? "bgcolor=pink" : "";
	$pos_est = ($i == posestnext($result3['lastpos'][3])) ? "bgcolor=yellow" : "";
	$list .= "<td $pos_last $pos_est>".islt($sel3[$i-1],$la,$m7,$L7[3]);

	$pos_last = ($i == $result4['lastpos'][4]) ? "bgcolor=pink" : "";
	$pos_est = ($i == posestnext($result4['lastpos'][4])) ? "bgcolor=yellow" : "";
	$list .= "<td $pos_last $pos_est>".islt($sel4[$i-1],$la,$m7,$L7[4]);

	$pos_last = ($i == $result5['lastpos'][5]) ? "bgcolor=pink" : "";
	$pos_est = ($i == posestnext($result5['lastpos'][5])) ? "bgcolor=yellow" : "";
	$list .= "<td $pos_last $pos_est>".islt($sel5[$i-1],$la,$m7,$L7[5]);

	$pos_last = ($i == $result6['lastpos'][6]) ? "bgcolor=pink" : "";
	$pos_est = ($i == posestnext($result6['lastpos'][6])) ? "bgcolor=yellow" : "";
	$list .= "<td $pos_last $pos_est>".islt($sel6[$i-1],$la,$m7,$L7[6]);

	$pos_last = ($i == $result7['lastpos'][7]) ? "bgcolor=pink" : "";
	$pos_est = ($i == posestnext($result7['lastpos'][7])) ? "bgcolor=yellow" : "";
	$list .= "<td $pos_last $pos_est>".islt($sel7[$i-1],$la,$m7,$L7[7])."</td>";	
}	

// arsort($mostnos);
// print_r($mostnos);
// foreach($mostnos as $key => $value){
// 	$list .= $mostnos."-";
// }

// $list .= "<tr><td>[0]"."<td>".islt($sel1[0],$la,$m7,$L7)."<td>".islt($sel2[0],$la,$m7,$L7)."<td>".islt($sel3[0],$la,$m7,$L7)."<td>".islt($sel4[0],$la,$m7,$L7)."<td>".islt($sel5[0],$la,$m7,$L7)."<td>".islt($sel6[0],$la,$m7,$L7)."<td>".islt($sel7[0],$la,$m7,$L7)."</td>";
// $list .= "<tr><td>[1]"."<td>".islt($sel1[1],$la,$m7,$L7)."<td>".islt($sel2[1],$la,$m7,$L7)."<td>".islt($sel3[1],$la,$m7,$L7)."<td>".islt($sel4[1],$la,$m7,$L7)."<td>".islt($sel5[1],$la,$m7,$L7)."<td>".islt($sel6[1],$la,$m7,$L7)."<td>".islt($sel7[1],$la,$m7,$L7)."</td>";
// $list .= "<tr><td>[2]"."<td>".islt($sel1[2],$la,$m7,$L7)."<td>".islt($sel2[2],$la,$m7,$L7)."<td>".islt($sel3[2],$la,$m7,$L7)."<td>".islt($sel4[2],$la,$m7,$L7)."<td>".islt($sel5[2],$la,$m7,$L7)."<td>".islt($sel6[2],$la,$m7,$L7)."<td>".islt($sel7[2],$la,$m7,$L7)."</td>";
// $list .= "<tr><td>[3]"."<td>".islt($sel1[3],$la,$m7,$L7)."<td>".islt($sel2[3],$la,$m7,$L7)."<td>".islt($sel3[3],$la,$m7,$L7)."<td>".islt($sel4[3],$la,$m7,$L7)."<td>".islt($sel5[3],$la,$m7,$L7)."<td>".islt($sel6[3],$la,$m7,$L7)."<td>".islt($sel7[3],$la,$m7,$L7)."</td>";
// $list .= "<tr><td>[4]"."<td>".islt($sel1[4],$la,$m7,$L7)."<td>".islt($sel2[4],$la,$m7,$L7)."<td>".islt($sel3[4],$la,$m7,$L7)."<td>".islt($sel4[4],$la,$m7,$L7)."<td>".islt($sel5[4],$la,$m7,$L7)."<td>".islt($sel6[4],$la,$m7,$L7)."<td>".islt($sel7[4],$la,$m7,$L7)."</td>";
// $list .= "<tr><td>[5]"."<td>".islt($sel1[5],$la,$m7,$L7)."<td>".islt($sel2[5],$la,$m7,$L7)."<td>".islt($sel3[5],$la,$m7,$L7)."<td>".islt($sel4[5],$la,$m7,$L7)."<td>".islt($sel5[5],$la,$m7,$L7)."<td>".islt($sel6[5],$la,$m7,$L7)."<td>".islt($sel7[5],$la,$m7,$L7)."</td>";
// $list .= "<tr><td>[6]"."<td>".islt($sel1[6],$la,$m7,$L7)."<td>".islt($sel2[6],$la,$m7,$L7)."<td>".islt($sel3[6],$la,$m7,$L7)."<td>".islt($sel4[6],$la,$m7,$L7)."<td>".islt($sel5[6],$la,$m7,$L7)."<td>".islt($sel6[6],$la,$m7,$L7)."<td>".islt($sel7[6],$la,$m7,$L7)."</td>";
// $list .= "<tr><td>[7]"."<td>".islt($sel1[7],$la,$m7,$L7)."<td>".islt($sel2[7],$la,$m7,$L7)."<td>".islt($sel3[7],$la,$m7,$L7)."<td>".islt($sel4[7],$la,$m7,$L7)."<td>".islt($sel5[7],$la,$m7,$L7)."<td>".islt($sel6[7],$la,$m7,$L7)."<td>".islt($sel7[7],$la,$m7,$L7)."</td>";
// $list .= "<tr><td>[8]"."<td>".islt($sel1[8],$la,$m7,$L7)."<td>".islt($sel2[8],$la,$m7,$L7)."<td>".islt($sel3[8],$la,$m7,$L7)."<td>".islt($sel4[8],$la,$m7,$L7)."<td>".islt($sel5[8],$la,$m7,$L7)."<td>".islt($sel6[8],$la,$m7,$L7)."<td>".islt($sel7[8],$la,$m7,$L7)."</td>";
// $list .= "<tr><td>[9]"."<td>".islt($sel1[9],$la,$m7,$L7)."<td>".islt($sel2[9],$la,$m7,$L7)."<td>".islt($sel3[9],$la,$m7,$L7)."<td>".islt($sel4[9],$la,$m7,$L7)."<td>".islt($sel5[9],$la,$m7,$L7)."<td>".islt($sel6[9],$la,$m7,$L7)."<td>".islt($sel7[9],$la,$m7,$L7)."</td>";
// $list .= "<tr><td>[10]"."<td>".islt($sel1[10],$la,$m7,$L7)."<td>".islt($sel2[10],$la,$m7,$L7)."<td>".islt($sel3[10],$la,$m7,$L7)."<td>".islt($sel4[10],$la,$m7,$L7)."<td>".islt($sel5[10],$la,$m7,$L7)."<td>".islt($sel6[10],$la,$m7,$L7)."<td>".islt($sel7[10],$la,$m7,$L7)."</td>";
// $list .= "<tr><td>[11]"."<td>".islt($sel1[11],$la,$m7,$L7)."<td>".islt($sel2[11],$la,$m7,$L7)."<td>".islt($sel3[11],$la,$m7,$L7)."<td>".islt($sel4[11],$la,$m7,$L7)."<td>".islt($sel5[11],$la,$m7,$L7)."<td>".islt($sel6[11],$la,$m7,$L7)."<td>".islt($sel7[11],$la,$m7,$L7)."</td>";
// $list .= "<tr><td>[12]"."<td>".islt($sel1[12],$la,$m7,$L7)."<td>".islt($sel2[12],$la,$m7,$L7)."<td>".islt($sel3[12],$la,$m7,$L7)."<td>".islt($sel4[12],$la,$m7,$L7)."<td>".islt($sel5[12],$la,$m7,$L7)."<td>".islt($sel6[12],$la,$m7,$L7)."<td>".islt($sel7[12],$la,$m7,$L7)."</td>";
// $list .= "<tr><td>[13]"."<td>".islt($sel1[13],$la,$m7,$L7)."<td>".islt($sel2[13],$la,$m7,$L7)."<td>".islt($sel3[13],$la,$m7,$L7)."<td>".islt($sel4[13],$la,$m7,$L7)."<td>".islt($sel5[13],$la,$m7,$L7)."<td>".islt($sel6[13],$la,$m7,$L7)."<td>".islt($sel7[13],$la,$m7,$L7)."</td>";
// $list .= "<tr><td>[14]"."<td>".islt($sel1[14],$la,$m7,$L7)."<td>".islt($sel2[14],$la,$m7,$L7)."<td>".islt($sel3[14],$la,$m7,$L7)."<td>".islt($sel4[14],$la,$m7,$L7)."<td>".islt($sel5[14],$la,$m7,$L7)."<td>".islt($sel6[14],$la,$m7,$L7)."<td>".islt($sel7[14],$la,$m7,$L7)."</td>";
// $list .= "<tr><td>[15]"."<td>".islt($sel1[15],$la,$m7,$L7)."<td>".islt($sel2[15],$la,$m7,$L7)."<td>".islt($sel3[15],$la,$m7,$L7)."<td>".islt($sel4[15],$la,$m7,$L7)."<td>".islt($sel5[15],$la,$m7,$L7)."<td>".islt($sel6[15],$la,$m7,$L7)."<td>".islt($sel7[15],$la,$m7,$L7)."</td>";
// $list .= "<tr><td>[16]"."<td>".islt($sel1[16],$la,$m7,$L7)."<td>".islt($sel2[16],$la,$m7,$L7)."<td>".islt($sel3[16],$la,$m7,$L7)."<td>".islt($sel4[16],$la,$m7,$L7)."<td>".islt($sel5[16],$la,$m7,$L7)."<td>".islt($sel6[16],$la,$m7,$L7)."<td>".islt($sel7[16],$la,$m7,$L7)."</td>";
// $list .= "<tr><td>[17]"."<td>".islt($sel1[17],$la,$m7,$L7)."<td>".islt($sel2[17],$la,$m7,$L7)."<td>".islt($sel3[17],$la,$m7,$L7)."<td>".islt($sel4[17],$la,$m7,$L7)."<td>".islt($sel5[17],$la,$m7,$L7)."<td>".islt($sel6[17],$la,$m7,$L7)."<td>".islt($sel7[17],$la,$m7,$L7)."</td>";
// $list .= "<tr><td>[18]"."<td>".islt($sel1[18],$la,$m7,$L7)."<td>".islt($sel2[18],$la,$m7,$L7)."<td>".islt($sel3[18],$la,$m7,$L7)."<td>".islt($sel4[18],$la,$m7,$L7)."<td>".islt($sel5[18],$la,$m7,$L7)."<td>".islt($sel6[18],$la,$m7,$L7)."<td>".islt($sel7[18],$la,$m7,$L7)."</td>";

$list .= "</table>";
$list .= "strike:lastdraw; redbold:most7npernos; ():frequence";
$list .= "<br>";
/*********************************************************************************************/
function nondrawed($nos,$q,$most,$last7){
	if (!in_array($nos, $last7) && !in_array($nos, $most) && !in_array($nos, $q)) {
		$rem .= $nos;
	}
	return $rem;
}
$recomment=array();	
for ($i = 0; $i <= 49; $i++) {
	array_push($recomment,nondrawed($sel1[$i],$la,$m7,$L7));
	array_push($recomment,nondrawed($sel2[$i],$la,$m7,$L7));
	array_push($recomment,nondrawed($sel3[$i],$la,$m7,$L7));
	array_push($recomment,nondrawed($sel4[$i],$la,$m7,$L7));
	array_push($recomment,nondrawed($sel5[$i],$la,$m7,$L7));
	array_push($recomment,nondrawed($sel6[$i],$la,$m7,$L7));
	array_push($recomment,nondrawed($sel7[$i],$la,$m7,$L7));
}
	foreach($recomment as $k=>$v) {
		//$newArray[] = $v;
		$recommenta[$v]++; 
	}
arsort($recommenta);
foreach($recommenta as $key => $value){
  $recomments[] = $key;
}
$list .= "recommend<br>";
$list .= "<table border=1 bordercolor=gray cellpadding=\"5\" cellspacing=\"0\" style=\"border-collapse: collapse\">";
$list .= "<tr>";
foreach($recomments as $array) {
	$list .= "<td><b>".$array;
}
$list .= "</table><br>";

/*********************************************************************************************/


//$list .= "[$ran]::::::".$sel1[$ran]."-".$sel2[$ran]."-".$sel3[$ran]."-".$sel4[$ran]."-".$sel5[$ran]."-".$sel6[$ran]."-".$sel7[$ran]."<br><br><br>";
$list .= "<table border=1 bordercolor=gray cellpadding=\"5\" cellspacing=\"0\" style=\"border-collapse: collapse\">";
$list .= "<tr><td>".$sel[0]."<td>".$sel[1]."<td>".$sel[2]."<td>".$sel[3]."<td>".$sel[4]."<td>".$sel[6];
$list .= "<tr><td>".$sel[0]."<td>".$sel[1]."<td>".$sel[2]."<td>".$sel[5]."<td>".$sel[6]."<td>".$sel[7];
$list .= "<tr><td>".$sel[0]."<td>".$sel[3]."<td>".$sel[4]."<td>".$sel[5]."<td>".$sel[6]."<td>".$sel[7];
$list .= "<tr><td>".$sel[1]."<td>".$sel[2]."<td>".$sel[3]."<td>".$sel[4]."<td>".$sel[5]."<td>".$sel[7];
$list .= "<tr><td colspan=6>&nbsp;<hr>";
$list .= "<tr><td>".$arr_keys[0]."<td>".$arr_keys[1]."<td>".$arr_keys[2]."<td>".$arr_keys[3]."<td>".$arr_keys[4]."<td>".$arr_keys[6];
$list .= "<tr><td>".$arr_keys[0]."<td>".$arr_keys[1]."<td>".$arr_keys[2]."<td>".$arr_keys[5]."<td>".$arr_keys[6]."<td>".$arr_keys[7];
$list .= "<tr><td>".$arr_keys[0]."<td>".$arr_keys[3]."<td>".$arr_keys[4]."<td>".$arr_keys[5]."<td>".$arr_keys[6]."<td>".$arr_keys[7];
$list .= "<tr><td>".$arr_keys[1]."<td>".$arr_keys[2]."<td>".$arr_keys[3]."<td>".$arr_keys[4]."<td>".$arr_keys[5]."<td>".$arr_keys[7];

$list .= "</table>";

//poe-------------------------------------------------------------------------
function calculateTrend($xValues, $yValues, $newX) {
    // $n = count($xValues);
    if (is_array($xValues) || $xValues instanceof Countable) {
        $n = count($xValues);
    } else {
        $n = 0; // or handle the case when $xValues is not countable
    }
    
    // Calculate the sum of x, y, x^2, xy
    // $sumX = array_sum($xValues);
    if (is_array($xValues)) {
        $sumX = array_sum($xValues);
    } else {
        $sumX = 0; // or handle the case when $xValues is not an array
    }
    $sumY = array_sum($yValues);
    $sumX2 = 0;
    $sumXY = 0;
    for ($i = 0; $i < $n; $i++) {
        $sumX2 += $xValues[$i] * $xValues[$i];
        $sumXY += $xValues[$i] * $yValues[$i];
    }
    
    // Calculate the slope (m) and intercept (b)
    // $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
    // $intercept = ($sumY - $slope * $sumX) / $n;
    if (($n * $sumX2 - $sumX * $sumX) != 0) {
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
    } else {
        $slope = 0; // Or handle this case as needed
    }
    
    if ($n != 0) {
        $intercept = ($sumY - $slope * $sumX) / $n;
    } else {
        $intercept = 0; // Or handle this case as needed
    }
    
    // Calculate the new y value based on the trend equation y = mx + b
    $newY = $slope * $newX + $intercept;
    $newY = number_format(fmod($newY,49),0);

    return $newY;
}
// function calculateTrend($xValues, $yValues, $newX) {
//     $n = count($xValues);

//     // Calculate the sum of x, y, x^2, xy
//     $sumX = array_sum($xValues);
//     $sumY = array_sum($yValues);
//     $sumX2 = 0;
//     $sumXY = 0;
//     for ($i = 0; $i < $n; $i++) {
//         $sumX2 += $xValues[$i] * $xValues[$i];
//         $sumXY += $xValues[$i] * $yValues[$i];
//     }

//     // Calculate the slope (m) and intercept (b)
//     $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
//     $intercept = ($sumY - $slope * $sumX) / $n;

//     // Calculate the new y value based on the trend equation y = mx + b
//     $newY = $slope * $newX + $intercept;

//     // Constrain the trend value within the range of 1 to 49
//     $trendValue = max(1, min(49, $newY));

//     return $trendValue;
// }
// $xValues = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
// $yValues = [43, 24, 2, 6, 22, 45, 22, 46, 11, 7];
// $newX = 10;

// $trendValue = calculateTrend($xValues, $yValues, $newX);
// echo "Trend value for x=$newX: $trendValue";
$newX = count($drawno)+1;
$xValues = $laterestquery->nos;//$drawno;
// print_r($xValues);
$trendValue1 = calculateTrend($xValues, $dig1, $newX);
$trendValue2 = calculateTrend($xValues, $dig2, $newX);
$trendValue3 = calculateTrend($xValues, $dig3, $newX);
$trendValue4 = calculateTrend($xValues, $dig4, $newX);
$trendValue5 = calculateTrend($xValues, $dig5, $newX);
$trendValue6 = calculateTrend($xValues, $dig6, $newX);
$trendValue7 = calculateTrend($xValues, $dig7, $newX);
$list .= "<hr>";
$list .=  $trendValue1."-".$trendValue2."-".$trendValue3."-".$trendValue4."-".$trendValue5."-".$trendValue6."-".$trendValue7;
$list .=  "<hr>";
//--------------------------------------------------------------------------------
if(!empty($drawnocurryr)){
    $newX = count($drawnocurryr)+1;
    $newX = max($drawnocurryr)+1;
    // $newX = 101;
    $xValues = $drawnocurryr;
    // print_r($xValues);
    $trendValue1curryr = calculateTrend($xValues, $dig1curryr, $newX);
    $trendValue2curryr = calculateTrend($xValues, $dig2curryr, $newX);
    $trendValue3curryr = calculateTrend($xValues, $dig3curryr, $newX);
    $trendValue4curryr = calculateTrend($xValues, $dig4curryr, $newX);
    $trendValue5curryr = calculateTrend($xValues, $dig5curryr, $newX);
    $trendValue6curryr = calculateTrend($xValues, $dig6curryr, $newX);
    $trendValue7curryr = calculateTrend($xValues, $dig7curryr, $newX);
    $list .= "<hr>";
    $list .=  $trendValue1curryr."-".$trendValue2curryr."-".$trendValue3curryr."-".$trendValue4curryr."-".$trendValue5curryr."-".$trendValue6curryr."-".$trendValue7curryr;
    $list .=  "<hr>";
}
//--------------------------------------------------------------------------------
// $newX = count($drawnos)+1;
// $newX = max($drawnos)+1;
$newX = $drawnos;
$xValues = $drawnocurryr;
// print_r($xValues);
if(!empty($drawnocurryr)){
    $trendValue1curryr = calculateTrend($xValues, $dig1curryr, $newX);
    $trendValue2curryr = calculateTrend($xValues, $dig2curryr, $newX);
    $trendValue3curryr = calculateTrend($xValues, $dig3curryr, $newX);
    $trendValue4curryr = calculateTrend($xValues, $dig4curryr, $newX);
    $trendValue5curryr = calculateTrend($xValues, $dig5curryr, $newX);
    $trendValue6curryr = calculateTrend($xValues, $dig6curryr, $newX);
    $trendValue7curryr = calculateTrend($xValues, $dig7curryr, $newX);
    $list .= "<hr>";
    $list .=  $trendValue1curryr."-".$trendValue2curryr."-".$trendValue3curryr."-".$trendValue4curryr."-".$trendValue5curryr."-".$trendValue6curryr."-".$trendValue7curryr;
    $list .=  "<hr>";
}
//--------------------------------------------------------------------------------
//echo "<pre>";
//print_r($newArray);
//print_r($sel);

$list .= "</div>";
echo $list;
//echo "<pre>";

if($_GET['mail'] ){
    $emailtitle = $laterest_nos;
	$ipAddress = substr($_SERVER['SERVER_ADDR'],-3);
	$Subject = "[666] - ".$emailtitle."  ".$ipAddress;	//$date;
	$Body    = $list;
	$AltBody = '';
	$recipients = '';
	$log = '';
	$recipients_BCC = array(
		'hkhorsepaper@gmail.com' => 's',
		'melvinmo@gmail.com' => 'm',
		'support@fengins.com' => 'fi',
		// ..
	 );
	 
	 $send = mailto($Subject,$Body,$AltBody,$recipients,$recipients_BCC,$log);

}

// 	$mail = new PHPMailer();

// 	$mail->IsSMTP();							// set mailer to use SMTP
// 	$mail->Host = "smtp.juraron.com.hk";		// specify main and backup server
// 	$mail->SMTPAuth = true;						// turn on SMTP authentication
// 	$mail->Username = "jhkdb@juraron.com.hk";	// SMTP username
// 	$mail->Password = "123x1*";					// SMTP password

// 	$mail->From = "jhkdb@juraron.com.hk";
// 	$mail->FromName = "mm";
// 	$mail->AddBCC("melvin@juraron.com.hk",".");
// 	$mail->AddBCC("melvinmo@gmail.com",".");
// 	//$mail->AddBCC("kmwong6688@gmail.com",".");

// 	$mail->WordWrap = 50;                                 // set word wrap to 50 characters
// 	$mail->IsHTML(true);                                  // set email format to HTML

// 	$mail->Subject = "[moosay] nos - ".$drawnos;
// 	//$mail->AddEmbeddedImage($string, 'mflow_c', 'mflow_c.png');
// 	$mail->Body = $list;	//"<img src='".$string."'>"; 

// 	if(!$mail->Send()){
// 		//echo "error";
// 	}else{
// 		//echo "done";
// 	}
// 	$mail->ClearAddresses();
// 	$mail->ClearAttachments(); 
	


    // <meta name="viewport" content="width=device-width, initial-scale=1.0">
    // <style>
    //     .container {
    //         display: flex;
    //         justify-content: center;
    //         align-items: center;
    //         height: 42vh;
    //         background-color: #f5f5f5;
    //     }
    //     .grid {
    //         display: grid;
    //         grid-template-columns: repeat(7, 50px);
    //         grid-gap: 5px;
    //     }
    //     .grid-item {
    //         width: 50px;
    //         height: 50px;
    //         display: flex;
    //         align-items: center;
    //         justify-content: center;
    //         border: 1px solid #ccc;
    //         font-size: 20px;
    //         background-color: #e0e0e0;
    //     }
    //     .highlight {
    //         color: red; /* Highlight color */
    //     }
    //     .row-highlight {
    //         background-color: lightyellow; /* Row highlight color */
    //     }
    //     .col-highlight {
    //         background-color: lightyellow; /* lightgreen Column highlight color */
    //     }
    //     .underline {
    //         text-decoration: underline; /* Underline style */
    //     }
    //             .double-underline {
    //         text-decoration: underline double; /* Double underline style */
    //     }
    //     .bold {
    //         font-weight: bold; /* Bold style */
    //     }
    //     .faded {
    //         opacity: 0.5; /* Reduced opacity */
    //     }
    // </style>
    
    
?>    