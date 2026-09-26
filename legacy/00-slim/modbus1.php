<?php

$fp = fsockopen("192.168.1.60", 502, $errno, $errstr, 3);
if(!$fp)
{
    printf("can\'t connect modbus tcp device\n");
    die();
}
else{
    printf("Link\n");
}   
?>