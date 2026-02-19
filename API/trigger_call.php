<?php
include "db.php";

if(isset($_GET['set'])){
    $set = intval($_GET['set']);
    $conn->query("UPDATE call_control SET trigger_call=$set WHERE id=1");
}

$result = $conn->query("SELECT trigger_call FROM call_control WHERE id=1");
$row = $result->fetch_assoc();

echo $row['trigger_call'];
?>
