<?php
/*
CREATE TABLE IF NOT EXISTS `00mpf` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `fund` varchar(50) NOT NULL,
  `price` decimal(5,2) NOT NULL,
  `rectime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `date_fund` (`date`,`fund`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;
*/
echo 1;
/*
require_once('../lib/mossql.php');	

$url = "http://www.hsbc.com.hk/1/2/mpf/fund/unitprice?pwscmd=cmd_init&amp;input.subTypeCode=HSBCTRUST";
$url = "http://www.hsbc.com.hk/1/2/mpf/fund/unitprice";
/*2017-01-26*/
$url = "https://bank.hangseng.com/1/2/e-services/e-mpf/fund-price-performance/price#01";
$date = date("Y-m-d");
echo $url

require('simple_html_dom.php');

$arr = array();
$html = file_get_html($url);
foreach($html->find('tr') as $row) {
	//echo $v."<br>";
	++$cnt;
	if($cnt==1){
		$day =  $row->find('option[selected]', 0)->value;
		$mon =  $row->find('option[selected]', 1)->value;
		$yr =  $row->find('option[selected]', 2)->value;
	}
    $fund = $row->find('td',0)->plaintext;
	$price = $row->find('td',1)->plaintext;

//list for select array nos.
echo $cnt."---".$fund."---";
echo $price."<br>";

	$rec_array = array(90,92);
	if (in_array($cnt, $rec_array, true)) {
		$sql = "insert into 00mpf value('','$date','$fund','$price','',now())";
		//echo $sql;
		$db->query($sql);
	}

    $arr[$cnt][1]=$fund;
	$arr[$cnt][2]=$price;
}
//$date= $yr."-".$mon."-".$day;
//$fund= trim($arr[8][1]);
//$price= $arr[8][2];

//$sql = "insert into 00mpf value('','$date','$fund','$price','',now())";
//$db->query($sql);

?>
