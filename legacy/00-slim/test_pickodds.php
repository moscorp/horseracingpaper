i7.php ( PHP script, UTF-8 Unicode text )
<?php
// error_reporting(E_ERROR | E_PARSE);
// error_reporting(E_ALL);
// error_reporting(E_ALL & ~E_NOTICE);

include_once ("lib/func_aii.php");
include_once ("lib/func_basedata.php");
include_once ("lib/func_mailer_gmail.php");
include_once ("lib/constants.php");
include_once ("load_horseinfo.php");
include_once ("load_oddsdrop.php");
include_once ("load_prophist.php");

$VenueCodeVN = [
    "ST" => "沙田",
    "HV" => "跑馬"
];


    $CurrPre = 'Pre';
    $venueCode = "ST";
    $date = "2026-05-09";
    $oddsPre = pickodds($oddsarray, $date, $venueCode, $raceNo, $CurrPre);
    print_r($oddsPre);