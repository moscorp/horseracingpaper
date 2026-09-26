<?php
echo 1;
$mysqli = mysqli_connect('192.168.1.146', 'melvin_any', 'mopass', 'moosay');
if (!$mysqli)
{
    die("connection error: " . mysqli_connect_error());
}
$mm = mysqli_query($mysqli, "select * from barriercomment ");

$mysqli = mysqli_connect('localhost', 'melvin_melvin', 'mopass', 'moosay');
foreach ($mm as $bc) {
    $insert = "insert into barriercomment (comment, rank)
									values('".$bc['comment']."','".$bc['rank']."')";
				echo $insert."\n";
				mysqli_query($mysqli, $insert);
}

?>