<?php

$hostName = "cei326-omada7";
$dbUser = "cei326omada7user";
$dbPassword = "DCXf!2OTgG!cYy52";
$dbName = "cei326omada7";
$conn = mysqli_connect($hostName, $dbUser, $dbPassword, $dbName);
 
if (!$conn) {
    die("Something went wrong;");
}

?>
