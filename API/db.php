<?php
$conn = new mysqli("localhost", "smartsip_farm", "smartsip_farm", "smartsip_farm");

if ($conn->connect_error) {
    die("Database Connection Failed");
}
?>
