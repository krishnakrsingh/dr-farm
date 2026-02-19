<?php
/**
 * GET /get_latest.php
 * Returns the latest sensor reading as JSON.
 * Optional: ?node_id=NODE_1  (filter by node)
 */
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $db = getDB();

    $nodeFilter = $_GET['node_id'] ?? '';

    if ($nodeFilter) {
        $stmt = $db->prepare("SELECT * FROM sensor_readings WHERE node_id = :nid ORDER BY id DESC LIMIT 1");
        $stmt->execute([':nid' => $nodeFilter]);
    } else {
        $stmt = $db->query("SELECT * FROM sensor_readings ORDER BY id DESC LIMIT 1");
    }

    $row = $stmt->fetch();

    if ($row) {
        echo json_encode($row, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(["error" => "No data available"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}

