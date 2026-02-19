<?php
/**
 * POST /sensor_upload.php
 * Receives sensor data from ESP32 nodes.
 * Content-Type: application/x-www-form-urlencoded
 */
require_once __DIR__ . '/db.php';

header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$node_id     = $_POST['node_id']     ?? '';
$temperature = floatval($_POST['temperature'] ?? 0);
$humidity    = floatval($_POST['humidity']    ?? 0);
$mq7         = intval($_POST['mq7']          ?? 0);
$mq3         = intval($_POST['mq3']          ?? 0);
$rain        = intval($_POST['rain']         ?? 0);

if (empty($node_id)) {
    http_response_code(400);
    echo 'ERROR: node_id required';
    exit;
}

try {
    $db = getDB();

    // Insert sensor reading
    $stmt = $db->prepare("
        INSERT INTO sensor_readings (node_id, temperature, humidity, mq7, mq3, rain, created_at)
        VALUES (:node_id, :temperature, :humidity, :mq7, :mq3, :rain, NOW())
    ");
    $stmt->execute([
        ':node_id'     => $node_id,
        ':temperature' => $temperature,
        ':humidity'    => $humidity,
        ':mq7'         => $mq7,
        ':mq3'         => $mq3,
        ':rain'        => $rain,
    ]);

    // Upsert node registry
    $stmt2 = $db->prepare("
        INSERT INTO nodes (node_id, last_seen, status)
        VALUES (:node_id, NOW(), 'online')
        ON DUPLICATE KEY UPDATE last_seen = NOW(), status = 'online'
    ");
    $stmt2->execute([':node_id' => $node_id]);

    echo 'OK';
} catch (Exception $e) {
    http_response_code(500);
    echo 'ERROR: ' . $e->getMessage();
}

