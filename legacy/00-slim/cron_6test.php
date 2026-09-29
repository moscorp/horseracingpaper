<?php
require_once('../lib/mossql.php');	

$sql = "select no1,no2,no3,no4,no5,no6,no7 from 00m6";
$t = $db->get_results($sql);
$nos = $db->num_rows;

foreach($t as $array) {
	foreach($array as $k=>$v) {
		$newArray[] = $v;
		$cnt[$v]++; 
	}
}

arsort($cnt);
foreach($cnt as $key => $value){
  $sel[] = $key;
}

$sql = "select no1,no2,no3,no4,no5,no6,no7 from 00m6 order by id desc limit 1";
$t = $db->get_row($sql);
//echo $sql;

$list .= "test<table border=1 bordercolor=gray cellpadding=\"5\" cellspacing=\"0\" style=\"border-collapse: collapse\">";
for($i=1;$i<=$nos;$i++){
	$s=0;
	$sqlx = "select no1,no2,no3,no4,no5,no6,no7 from 00m6 order by id asc limit ".$i;
	//echo $sql."<br>";
	$tx = $db->get_results($sqlx);
	$nosx = $db->num_rows;


	foreach($tx as $array) {
		foreach($array as $k=>$v) {
			$newArray[] = $v;
			$cntx[$v]++; 
		}
	}

	arsort($cntx);
	foreach($cntx as $key => $value){
	  $selx[] = $key;
	}

	if($t->no1==$selx[0] || $t->no1==$selx[1] || $t->no1==$selx[2] || $t->no1==$selx[3] || $t->no1==$selx[4] || $t->no1==$selx[5] ||$t->no1==$selx[6] || $t->no1==$selx[7]) $s++;
	if($t->no2==$selx[0] || $t->no2==$selx[1] || $t->no2==$selx[2] || $t->no2==$selx[3] || $t->no2==$selx[4] || $t->no2==$selx[5] ||$t->no2==$selx[6] || $t->no2==$selx[7]) $s++;
	if($t->no3==$selx[0] || $t->no3==$selx[1] || $t->no3==$selx[2] || $t->no3==$selx[3] || $t->no3==$selx[4] || $t->no3==$selx[5] ||$t->no3==$selx[6] || $t->no3==$selx[7]) $s++;
	if($t->no4==$selx[0] || $t->no4==$selx[1] || $t->no4==$selx[2] || $t->no4==$selx[3] || $t->no4==$selx[4] || $t->no4==$selx[5] ||$t->no4==$selx[6] || $t->no4==$selx[7]) $s++;
	if($t->no5==$selx[0] || $t->no5==$selx[1] || $t->no5==$selx[2] || $t->no5==$selx[3] || $t->no5==$selx[4] || $t->no5==$selx[5] ||$t->no5==$selx[6] || $t->no5==$selx[7]) $s++;
	if($t->no6==$selx[0] || $t->no6==$selx[1] || $t->no6==$selx[2] || $t->no6==$selx[3] || $t->no6==$selx[4] || $t->no6==$selx[5] ||$t->no6==$selx[6] || $t->no6==$selx[7]) $s++;
	if($t->no7==$selx[0] || $t->no7==$selx[1] || $t->no7==$selx[2] || $t->no7==$selx[3] || $t->no7==$selx[4] || $t->no7==$selx[5] ||$t->no7==$selx[6] || $t->no7==$selx[7]) $s++;
	$list .= "<tr><td>".$i."-".$t->no1."-".$t->no2."-".$t->no3."-".$t->no4."-".$t->no5."-".$t->no6."-".$t->no7;
	$list .= "<td>".$nosx."<td>".$s."-".$selx[0]."-".$selx[1]."-".$selx[2]."-".$selx[3]."-".$selx[4]."-".$selx[5]."-".$selx[6]."-".$selx[7];
}
echo $list;
/*
1	2	3	4	5	7
1	2	3	6	7	8
1	4	5	6	7	8
2	3	4	5	6	8


$list .= "<table border=1 bordercolor=gray cellpadding=\"5\" cellspacing=\"0\" style=\"border-collapse: collapse\">";
$list .= "<tr><td>".$t->no1."<td>".$t->no2."<td>".$t->no3."<td>".$t->no4."<td>".$t->no5."<td>".$t->no7;
$list .= "<tr><td>".$t->no1."<td>".$t->no2."<td>".$t->no3."<td>".$t->no6."<td>".$t->no7."<td>".$sel[7];
$list .= "<tr><td>".$t->no1."<td>".$t->no4."<td>".$t->no5."<td>".$t->no6."<td>".$t->no7."<td>".$sel[7];
$list .= "<tr><td>".$t->no2."<td>".$t->no3."<td>".$t->no4."<td>".$t->no5."<td>".$t->no6."<td>".$sel[7];
$list .= "</table>";
*/
?>