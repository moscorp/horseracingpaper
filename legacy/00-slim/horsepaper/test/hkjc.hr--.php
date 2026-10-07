<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);

require_once("../lib/class.phpmailer.php");
// probility=1/winx0.82
// hr=1000lb   ass<128lb

ini_set("allow_url_fopen", 1);
header('Content-Type: text/html; charset=utf-8');

$mail= $_GET['mail'] ? $_GET['mail'] : 0;
$raceno=1;

//---------------------------------------------------------------------------------------------
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
//---------------------------------------------------------------------------------------------

$infourl = "https://bet.hkjc.com/racing/script/rsdata.js?lang=ch&date=2020-02-23&venue=ST";
$infourl = "https://bet.hkjc.com/racing/script/rsdata.js?lang=ch&date=2020-02-26&venue=HV";
//$info = file_get_contents($infourl);
//var_dump($info);

//$results  = utf8_encode($info);
//print $results['mtgTotalRace'];
$type="winplaodds";	//"winplaoddspre";
$date="2020-03-04";
$venue="HV";
$start="1";
$end="8";

$url = "https://bet.hkjc.com/racing/getJSON.aspx?type=winplaodds&date=2020-02-23&venue=ST&start=1&end=10";
$url = "https://bet.hkjc.com/racing/getJSON.aspx?type=winplaodds&date=2020-02-26&venue=HV&start=1&end=9";
$url = "https://bet.hkjc.com/racing/getJSON.aspx?type=winplaodds&date=2020-03-01&venue=ST&start=1&end=10";
$url = "https://bet.hkjc.com/racing/getJSON.aspx?type=winplaodds&date=2020-03-04&venue=HV&start=1&end=8";

$url = "https://bet.hkjc.com/racing/getJSON.aspx?type=winplaodds&date=".$date."&venue=".$venue."&start=1&end=".$end;
$url_pre = "https://bet.hkjc.com/racing/getJSON.aspx?type=winplaoddspre&date=".$date."&venue=".$venue."&start=1&end=".$end;
//echo $url;
//echo 'file_get_contents: ', file_get_contents($url) ? 'Enabled' : 'Disabled';

//https://stackoverflow.com/questions/15617512/get-json-object-from-url
$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL, $url);
$result = curl_exec($ch);
curl_close($ch);

$ch_pre = curl_init();
curl_setopt($ch_pre, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch_pre, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_pre, CURLOPT_URL, $url_pre);
$result_pre = curl_exec($ch_pre);
curl_close($ch_pre);

$listr="c";

$obj = json_decode($result);
if(! isset($obj) ){
	$listr="f";
		$output = file_get_contents($url);
		$obj = json_decode(removeBOM($output));

		$output_pre = file_get_contents($url_pre);
		$obj_pre = json_decode(removeBOM($output_pre));

}
//}while( isset($obj->{'OUT'}) );
//echo $try;
//$obj = json_decode($json);
//if(empty($obj->{'OUT'})) exit;
//var_dump($obj->{'OUT'});
//print_r($obj->OUT);
//echo $obj['OUT'];


$arr = '
{"OUT":"170318@@@WIN;1=219=0;2=5.7=3;3=2.8=1;4=18=0;5=6.8=2;6=7.3=0;7=115=0;8=22=0;9=63=0;10=24=0;11=16=0;12=7.5=0;13=73=0;14=40=0#PLA;1=47=0;2=1.7=2;3=1.3=1;4=4.4=0;5=2.9=0;6=2.3=0;7=23=0;8=4.5=0;9=13=0;10=6.8=0;11=4.3=0;12=2.5=0;13=17=0;14=9.0=0@@@WIN;1=12=2;2=16=0;3=180=0;4=11=0;5=90=0;6=7.0=0;7=5.8=0;8=21=0;9=10=2;10=11=2;11=12=2;12=100=0;13=214=0;14=3.1=1#PLA;1=4.8=0;2=4.3=0;3=47=0;4=2.6=0;5=24=0;6=2.0=0;7=2.3=0;8=4.7=0;9=3.2=2;10=4.5=0;11=4.0=0;12=27=0;13=42=0;14=1.3=1@@@WIN;1=87=0;2=204=0;3=6.6=0;4=37=0;5=49=0;6=17=0;7=6.8=0;8=4.7=2;9=145=0;10=518=0;11=12=0;12=49=0;13=55=0;14=2.2=1#PLA;1=13=0;2=32=0;3=1.5=3;4=8.9=0;5=9.1=0;6=3.3=0;7=2.3=0;8=1.7=2;9=29=0;10=57=0;11=2.5=0;12=7.6=0;13=10=0;14=1.4=1@@@WIN;1=86=0;2=8.0=0;3=41=0;4=43=0;5=2.4=1;6=108=0;7=79=0;8=4.8=0;9=13=2;10=31=0;11=7.2=0;12=32=0;13=43=0;14=10=2#PLA;1=16=0;2=2.3=0;3=8.9=0;4=10=0;5=1.5=0;6=26=0;7=20=0;8=1.5=1;9=2.8=2;10=7.3=0;11=2.3=0;12=6.7=0;13=10=0;14=2.7=2@@@WIN;1=64=0;2=9.2=0;3=2.4=1;4=3.6=0;5=255=0;6=179=0;7=228=0;8=43=0;9=4.2=0;10=102=0;11=99=0;12=92=0;13=10=2;14=164=0#PLA;1=9.2=0;2=2.2=2;3=1.3=0;4=1.3=1;5=39=0;6=23=0;7=35=0;8=7.0=0;9=1.7=0;10=14=0;11=15=0;12=13=0;13=2.1=2;14=21=0@@@WIN;1=14=0;2=9.7=3;3=190=0;4=11=0;5=5.4=0;6=20=0;7=50=0;8=107=0;9=2.2=1;10=348=0;11=250=0;12=9.1=2;13=58=2;14=10=0#PLA;1=2.8=0;2=2.2=2;3=32=0;4=2.8=0;5=2.2=0;6=6.0=0;7=8.8=0;8=21=0;9=1.2=1;10=71=0;11=46=0;12=2.9=0;13=8.4=2;14=3.1=0@@@WIN;1=2.2=1;2=13=2;3=2.8=0;4=7.3=0;5=35=0;6=122=0;7=16=0;8=17=2;9=135=0;10=45=0#PLA;1=1.4=0;2=2.5=2;3=1.0=1;4=2.1=0;5=6.5=0;6=15=2;7=2.8=0;8=3.3=0;9=19=0;10=6.7=0@@@WIN;1=241=0;2=4.9=0;3=7.2=0;4=22=2;5=15=2;6=2.4=1;7=4.8=2;8=144=0;9=SCR=0;10=11=3;11=199=0;12=21=3#PLA;1=39=0;2=1.7=0;3=2.3=0;4=3.7=2;5=3.4=0;6=1.2=1;7=1.7=0;8=22=0;9=SCR=0;10=3.1=2;11=25=0;12=5.1=2@@@WIN;1=64=0;2=9.4=3;3=5.2=2;4=15=0;5=1.9=1;6=84=0;7=155=0;8=224=0;9=10=0;10=14=2;11=150=0;12=11=0;13=34=0;14=256=0#PLA;1=14=0;2=2.7=2;3=1.7=0;4=3.1=0;5=1.2=1;6=15=0;7=23=0;8=32=0;9=2.5=0;10=3.2=2;11=24=0;12=2.5=0;13=6.2=2;14=46=0@@@WIN;1=3.3=1;2=37=0;3=19=0;4=5.8=0;5=290=0;6=6.4=2;7=125=0;8=10=2;9=18=0;10=4.1=0;11=258=0;12=13=0;13=39=0;14=375=0#PLA;1=1.5=1;2=8.6=0;3=4.7=0;4=1.6=0;5=58=0;6=2.5=0;7=24=0;8=2.8=2;9=4.6=0;10=1.7=0;11=41=0;12=3.7=0;13=7.4=0;14=69=0"}

';

$exploded = (isset($obj)) ? multiexplode(array("@@@WIN;","#PLA;"),$obj->{'OUT'}) : "";
$exploded_pre = (isset($obj_pre)) ? multiexplode(array("@@@WIN;","#PLA;"),$obj_pre->{'OUT'}) : "";

//$exploded = multiexplode(array("@@@WIN;","#PLA;"),$arr);

//print_r($exploded);
echo "<br><br>";
$arraycount = (isset($obj)) ? count($exploded) : 0;
$arraycount_pre = (isset($obj_pre)) ? count($exploded_pre) : 0;

//echo $arraycount;

$winstr = ($arraycount>1) ? $exploded[1] : 0;
$plastr = ($arraycount>1) ? $exploded[2] : 0;

$winstr_pre = ($arraycount_pre>1) ? $exploded_pre[1] : 0;
$plastr_pre = ($arraycount_pre>1) ? $exploded_pre[2] : 0;

$odds = array();
$odds_pre = array();
$cnt=0;
$raceno=0;
/*
    [0] =&gt; 113243
    [1] =&gt; 1=158=0;2=13=0;3=3.1=1;4=12=0;5=10=0;6=6.1=0;7=83=0;8=14=0;9=54=0;10=19=0;11=13=0;12=5.6=0;13=45=0;14=23=0
    [2] =&gt; 1=32=0;2=3.4=0;3=1.5=1;4=4.0=0;5=3.3=0;6=2.2=0;7=17=0;8=3.8=0;9=11=0;10=5.5=0;11=3.7=0;12=2.0=0;13=9.1=0;14=4.6=0
*/
if(isset($obj_pre)){
	foreach($exploded_pre as $key=>$value){
		if($cnt){
			$racearray = explode(';', $value);
			foreach($racearray as $key=>$value){
				$arr = explode('=', $value);
				$horseno = $arr[0];
				$wp = $arr[1];
				if($cnt % 2 == 0){
					$odds_pre[$raceno][$horseno]["pla"] = $wp;
				}else{
					$odds_pre[$raceno][$horseno]["win"] = $wp;
				}
			}
		}
		if($cnt % 2 == 0){
			$raceno++;
		}
		$cnt++;
	}
}
var_dump($odds_pre);
$odds_prex = array();
foreach ($odds_pre as $key1 => $value){
	foreach($value as $key2 => $title){
		$odds_prex[$key1][$key2]["win"] = $title["win"];
		$odds_prex[$key1][$key2]["pla"] = $title["pla"];
	}
}
var_dump($odds_prex);
$cnt=0;
$raceno=0;
foreach($exploded as $key=>$value){
	if($cnt){
		$racearray = explode(';', $value);
//		print_r($racearray);
/*
    [0] =&gt; 1=158=0
    [1] =&gt; 2=13=0
    [2] =&gt; 3=3.1=1
*/
		foreach($racearray as $key=>$value){
			$arr = explode('=', $value);
			$horseno = $arr[0];
			$wp = $arr[1];
			if($cnt % 2 == 0){
				$odds[$raceno][$horseno]["pla"] = $wp;
			}else{
				$odds[$raceno][$horseno]["win"] = $wp;
				
			}
			//echo $odds[1][$horseno]["win"]."<br>";
		}
	}
	if($cnt % 2 == 0){
		$raceno++;
	}
	$cnt++;
}
//var_dump($odds);
$listr .= "<table bgcolor=lightgrey>";
     foreach ($odds as $key1 => $value):
	 	$listr .= "<tr><td style=\"text-align: left;\">".$key1;
		$listr .= "<table border=1 style=\"background-color: white;\">";

		asort($value);
		$cnt=0;
		$temp_win=array();
		$temp_pla=array();
		$temp_pla_array=array();
        foreach($value as $key => $title):
			$temp_win[$cnt]= $title['win'];
			$temp_pla[$cnt]= $title['pla'];
			array_push($temp_pla_array,$title['pla']);
			$cnt++;
        endforeach;

		$cnt=1;
		$temp_w=0;
		
        foreach($value as $key2 => $title):
			$sel_pla="";
			$hl_samepla="";
			$listr .= "<tr><td>".$key2;	//horse no
/*same win-------------------*/
			if(!empty($temp_win[$cnt]) && !empty($title['win']) && $temp_win[$cnt]==$title['win']){
				$hl_samewin = "style=\"background-color:#FFFF99\"";
				if($temp_pla[$cnt]>$title['pla']){ $sel_pla = "<b>"; }
			}else{
				$hl_samewin = "";
			}
			if(!empty($title['win']) && $temp_w==$title['win']){
				$hl_samewin1 = "style=\"background-color:#FFFF99\"";
			}else{
				$hl_samewin1 = "";
			}
/*---------------------------*/
			if(!empty($title['win']) && count_filtered_array( $temp_pla_array, $title['pla'] )>1  ){ 
				$hl_samepla = "<font color=red><b>"; 
			}

			$listr .= "<td >&nbsp;";
			$listr .= "<td >&nbsp;";
			$listr .= "<td >".($title['win']>0 ? number_format(1/($title['win']+$title['pla'])*0.82,2) : "&nbsp;");
			$listr .= "<td $hl_samewin $hl_samewin1 >".($odds_prex[$key1][$key2]["win"] ? $odds_prex[$key1][$key2]["win"] : "&nbsp;");
			$listr .= "<td $hl_samewin $hl_samewin1 >".$title['win'];
			$listr .= "<td >".$hl_samepla.$sel_pla.$title['pla'];
			$listr .= "<td >".(is_numeric($title['pla']) ? number_format(100*$title['pla']/$title['win'],0) : "&nbsp;");
			$cnt++;
			$temp_w = $title['win'];
			$temp_p = $title['pla'];
        endforeach;
		$listr .= "</table>";
     endforeach; 
$listr .= "</table>";

echo $listr;

if($mail && $arraycount>1){
	$mail = new PHPMailer();

	$mail->IsSMTP();							// set mailer to use SMTP
	$mail->Host = "smtp.juraron.com.hk";		// specify main and backup server
	$mail->SMTPAuth = true;						// turn on SMTP authentication
	$mail->Username = "jhkdb@juraron.com.hk";	// SMTP username
	$mail->Password = "123x1*";					// SMTP password

	$mail->From = "jhkdb@juraron.com.hk";
	$mail->FromName = "mm";
	$mail->AddBCC("melvin@juraron.com.hk",".");
	$mail->AddBCC("melvinmo@gmail.com",".");
	//$mail->AddBCC("kmwong6688@gmail.com",".");

	$mail->WordWrap = 50;                                 // set word wrap to 50 characters
	$mail->IsHTML(true);                                  // set email format to HTML

	$mail->Subject = "[moosay] hr -".$date;
	//$mail->AddEmbeddedImage($string, 'mflow_c', 'mflow_c.png');
	$mail->Body = $listr;	//"<img src='".$string."'>"; 

	if(!$mail->Send()){
		//echo "error";
	}else{
		//echo "done";
	}
	$mail->ClearAddresses();
	$mail->ClearAttachments(); 
}

?>
<style>
table {
	border : 1px;
	border-collapse: collapse;
	vertical-align: top;
	#background-color: white;
	background: #76b852; /* fallback for old browsers */
    background: -webkit-linear-gradient(right, #76b852, #8DC26F);
    background: -moz-linear-gradient(right, #76b852, #8DC26F);
    background: -o-linear-gradient(right, #76b852, #8DC26F);
    background: linear-gradient(to left, #76b852, #8DC26F);
    font-family: "Roboto", sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;      
}
th, td {
  padding: 5px;
  text-align: right;
  vertical-align: top;
  font-size: 9px;
}
</style>
