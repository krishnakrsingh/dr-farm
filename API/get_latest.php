<?php
include "db.php";

$result = $conn->query("SELECT * FROM sensor_data ORDER BY id DESC LIMIT 1");

if($result->num_rows > 0){
    $row = $result->fetch_assoc();
    echo json_encode($row);
} else {
    echo json_encode(["status"=>"no_data"]);
}
?>
