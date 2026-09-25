<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once('simplehtmldom_1_9_1/simple_html_dom.php');
// DB connection
require_once("lib/constants.php");
$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$mysqli) {
    die('DATABASE ERROR: ' . mysqli_connect_errno());
}

        $insert = "	INSERT INTO logs
                    VALUES('','testing',now())";
        // echo $insert;
        mysqli_query($mysqli, $insert);

        // $cnt++;
    

$search = mysqli_query($mysqli, "select * from barrierresult ");
while($row = mysqli_fetch_array($search))
     {
        // print_r($row);
     } 

mysqli_close($mysqli);
// print_r($trailday);


?>