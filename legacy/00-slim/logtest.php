<?php

error_reporting(E_ALL);
// ini_set("allow_url_fopen", 1);
header('Content-Type: text/html; charset=utf-8');
require_once('lib/mossql.php');	
require_once('lib/func.php');	
require_once("lib/func_mailer_gmail.php");
include_once('simplehtmldom_1_9_1/simple_html_dom.php');

require_once("lib/constants.php");
$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$logs = "";
//--------------------------------------------------------------------
$dt = new DateTime("now", new DateTimeZone('Asia/Hong_Kong'));

$addr = "-addr:".$_SERVER['SERVER_ADDR'];
$port = "-port:".$_SERVER['SERVER_PORT'];

$times = 'test-'.$dt->format('Y-m-d, H:i:s');
$type = $times.$addr.$port;

        $insert = "	INSERT INTO logs
                    VALUES('','$type',now())";
        // echo $insert;
		mysqli_query($mysqli, $insert);
//--------------------------------------------------------------------	