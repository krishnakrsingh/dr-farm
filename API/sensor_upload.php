<?php
include "db.php";

$node_id = $_POST['node_id'];
$temp = $_POST['temperature'];
$hum = $_POST['humidity'];
$mq7 = $_POST['mq7'];
$mq3 = $_POST['mq3'];
$rain = $_POST['rain'];

$sql = "INSERT INTO sensor_data 
(node_id, temperature, humidity, mq7, mq3, rain)
VALUES ('$node_id', '$temp', '$hum', '$mq7', '$mq3', '$rain')";

if($conn->query($sql)){
    echo "OK";
} else {
    echo "ERROR";
}
?>
