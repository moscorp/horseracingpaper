<?
ini_set('display_errors', 'On');

$url = "https://www.hangseng.com/en-hk/e-services/e-mpf/fund-price-performance/price/";
$date = date("Y-m-d");
echo $url
include('simple_html_dom.php');

$html = file_get_html("http://www.google.com");

print($html);

?>