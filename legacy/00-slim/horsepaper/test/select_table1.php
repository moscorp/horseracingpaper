<?php
$url="https://racing.hkjc.com/racing/information/chinese/Horse/BTResult.aspx?Date=2020/03/19";
$html = file_get_contents($url);

$DOM = new DOMDocument();
$DOM->loadHTML($html);

$aDataTableDetailHTML=array();
$aTempData=array();

$Header = $DOM->getElementsByTagName('tr');
$Detail = $DOM->getElementsByTagName('td');

print_r($Header);
print_r($Detail);
//#Get header name of the table
foreach($Header as $NodeHeader) 
{
    $aDataTableHeaderHTML[] = trim($NodeHeader->textContent);
}
//print_r($aDataTableHeaderHTML); die();

//#Get row data/detail table without header name as key
$i = 0;
$j = 0;
foreach($Detail as $sNodeDetail) 
{
    $aDataTableDetailHTML[$j][] = trim($sNodeDetail->textContent);
    $i = $i + 1;
    $j = $i % count($aDataTableHeaderHTML) == 0 ? $j + 1 : $j;
}
// print_r($aDataTableDetailHTML); die();

//#Get row data/detail table with header name as key and outer array index as row number
for($i = 0; $i < count($aDataTableDetailHTML); $i++)
{
    for($j = 0; $j < count($aDataTableHeaderHTML); $j++)
    {
        $aTempData[$i][$aDataTableHeaderHTML[$j]] = $aDataTableDetailHTML[$i][$j];
    }
}
$aDataTableDetailHTML = $aTempData; unset($aTempData);
print_r($aDataTableDetailHTML); die();
?>