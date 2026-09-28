<?

include('../simple_html_dom.php');

$url = "https://www.hangseng.com/en-hk/e-services/e-mpf/fund-price-performance/price/";
$html = file_get_html($url);

print($html);

?>