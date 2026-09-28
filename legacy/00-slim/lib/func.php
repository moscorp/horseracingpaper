<?php


function multiexplode2 ($delimiters,$string) {
    $ary = explode($delimiters[0],$string);
    array_shift($delimiters);
    if($delimiters != NULL) {
        foreach($ary as $key => $val) {
             $ary[$key] = multiexplode2($delimiters, $val);
        }
    }
    return  $ary;
}

function postRequest($url, $data, $refer = "", $timeout = 10, $header = [])
{
    $curlObj = curl_init();
    $ssl = stripos($url,'https://') === 0 ? true : false;
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_AUTOREFERER => 1,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)',
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_0,
        CURLOPT_HTTPHEADER => ['Expect:'],
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_REFERER => $refer
    ];
    if (!empty($header)) {
        $options[CURLOPT_HTTPHEADER] = $header;
    }
    if ($refer) {
        $options[CURLOPT_REFERER] = $refer;
    }
    if ($ssl) {
        //support https
        $options[CURLOPT_SSL_VERIFYHOST] = false;
        $options[CURLOPT_SSL_VERIFYPEER] = false;
    }
    curl_setopt_array($curlObj, $options);
    $returnData = curl_exec($curlObj);
    if (curl_errno($curlObj)) {
        //error message
        $returnData = curl_error($curlObj);
    }
    curl_close($curlObj);
    return $returnData;
}
function removeBOM($data) {
    if (0 === strpos(bin2hex($data), 'efbbbf')) {
       return substr($data, 3);
    }
    return $data;
}
function count_filtered_array ($array,$value){
	$cnt=0;
	$len=count($array);
	for($i=0;$i<$len;$i++)
		 if($array[$i]==$value) { $cnt++; }
	return $cnt;
}
function multiexplode ($delimiters,$string) {
    $ready = str_replace($delimiters, $delimiters[0], $string);
    $launch = explode($delimiters[0], $ready);
    return  $launch;
}
function array_icount_values($arr,$value) {
	$cnt=0;
    foreach ($arr as $values) {
        if ($values == $value) {
            $cnt++;
        }
    }
    return $cnt;
}
    // function to calculate the standard deviation
    // of array elements
function Stand_Deviation($arr){
	$num_of_elements = count($arr);
	$variance = 0.0;
		// calculating mean using array_sum() method
	$average = array_sum($arr)/$num_of_elements;
	foreach($arr as $i){
		// sum of squares of differences between 
		// all numbers and means.
		$variance += pow(($i - $average), 2);
	}
	return (float)sqrt($variance/$num_of_elements);
}

function in_array_all($needles, $haystack) {
    return empty(array_diff($needles, $haystack));
 }
 

function output_wp_matrix($raceno,$input_string, $winpla, $platop4) {
    $max_key = count($winpla);
  
    // Create the first table
    $table1 = array();
    $input_array = explode(';', $input_string);
    foreach ($input_array as $input) {
      if (!empty($input)) {
        $row_data = explode('=', $input);
        $row_key = explode('-', $row_data[0]);
        $table1[$row_key[0]][$row_key[1]] = $row_data[1];
        $table1[$row_key[1]][$row_key[0]] = $row_data[1];
        // echo $row_key[0]."-".$row_key[1]."-".$row_data[1]."<br>";
      }
    }

      // Create the second table
    $table2 = array();
    for ($i = 1; $i <= $max_key; $i++) {
        for ($j = 1; $j <= $max_key; $j++) {
            if ($j == $i) {
            // Do nothing
            } else {
                $table2[$i][$j] = $winpla[$i] * $winpla[$j];
                $table2[$j][$i] = $winpla[$i] * $winpla[$j];
                // echo $i."-".$j."-".$values[$i]."-".$values[$j]."<br>";
            }
        }
    }

    // Create the third table by dividing each value of the first table by the corresponding value of the second table
    $table3 = array();
    for ($i = 1; $i <= $max_key; $i++) {
        for ($j = 1; $j <= $max_key; $j++) {
            if (isset($table2[$i][$j]) && isset($table1[$i][$j])) {
                $table3[$i][$j] = round(floatval($table1[$i][$j]) / floatval($table2[$i][$j]), 2);
            }
        }
    }

    // Output the tables
    // echo "Table 1:\n";
    // for ($i = 1; $i <= $max_key; $i++) {
    // for ($j = 1; $j <= $max_key; $j++) {
    //     if (isset($table1[$i][$j])) {
    //     echo $table1[$i][$j] . "\t";
    //     } else {
    //     echo "-\t";
    //     }
    // }
    // echo "\n";
    // }

    // echo "\nTable 2:\n";
    // for ($i = 1; $i <= $max_key; $i++) {
    // for ($j = 1; $j <= $max_key; $j++) {
    //     if (isset($table2[$i][$j])) {
    //     echo $table2[$i][$j] . "\t";
    //     } else {
    //     echo "-\t";
    //     }
    // }
    // echo "\n";
    // }

    // $sort_values = usort($values);

    // echo "\nTable 3:\n";
    //     echo "<table border='1'>";
    //     echo "<tr><th></th><th></th><th></th>";
    //     for ($i = 2; $i <= $max_key; $i++) {
    //     echo "<th>$i<br>$values[$i]</th>";
    //     }  
    //     echo "</tr>";
        
        
    // for ($i = 1; $i <= $max_key; $i++) {
    // echo "<tr><th>".$i."<th>".$values[$i]."</th>";
    // // echo "<td></td>";
    // for ($j = 1; $j <= $max_key; $j++) {
    //     if (isset($table3[$i][$j])) {
    //     echo "<td>".$table3[$i][$j] . "\t";
    //     } else {
    //     // echo "-\t";
    //     echo "<td></td>";
    //     }
    // }
    // echo "</tr>";
    // // echo "\n";
    // }
    // echo "</table>";

// print_r($platop4);
    // echo "\nTable 4:\n";
    // asort($values);
    $list .= "<table border='1'  border-collapse= collapse; style=\"border-collapse: collapse;\">";
    $list .=  "<tr><th>$raceno</th><th></th>";
    foreach($winpla as $i => $odds){
        $list .=  "<th bgcolor=lightgrey>$i</th>";
    }  
    $list .=  "</tr>";
    $list .=  "<tr><th>N</th><th>O</th>";
    foreach($winpla as $i => $odds){
        $list .=  "<th bgcolor=lightgrey>$winpla[$i]</th>";
    }  
    $list .=  "</tr>";

    
    foreach($winpla as $i => $odds){
        $list .=  "<tr><th bgcolor=lightgrey>".$i."<th bgcolor=lightgrey>".$winpla[$i]."</th>";
    $tmp=0;
    foreach($winpla as $j => $odds){    
        if (isset($table3[$i][$j])) {
            // $is_smaller = ($tmp && $tmp > $table3[$i][$j]) ? "<b><font color=brown>" : "";
            $is_pla = ($platop4 && in_array_all([$i, $j], $platop4)) ? "bgcolor=yellow" : "";
            $list .=  "<td $is_pla>".$is_smaller.$table3[$i][$j] . "\t";
            $tmp = $table3[$i][$j];
        } else {
            $list .=  "<td></td>";
        }
    }
    $list .=  "</tr>";
    }
    $list .=  "</table>";

    return ($raceno ? $list : "");

}


function getFileFTW($url)
{
    $fuse = 10;//maximum attempts
    $pause = 1;//time between 2 attempts
    do {
        if($fuse < 10)
            sleep($pause);
        $s = @file_get_contents($url);
    }
    while($s===false && $fuse--);
    return $s;
}
?>