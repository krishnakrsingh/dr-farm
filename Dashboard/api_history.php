<?php
/**
 * GET /api_history.php?type=sensor&limit=50
 * GET /api_history.php?type=risk&limit=20
 * GET /api_history.php?type=alerts&limit=20
 * GET /api_history.php?type=nodes
 * GET /api_history.php?type=disease&limit=10
 */
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$type  = $_GET['type']  ?? 'sensor';
$limit = min(200, max(1, intval($_GET['limit'] ?? 50)));

try {
    $db = getDB();

    switch ($type) {
        case 'sensor':
            $stmt = $db->prepare("SELECT * FROM sensor_readings ORDER BY id DESC LIMIT :lim");
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll();
            // Reverse so oldest first (for charts)
            echo json_encode(array_reverse($data));
            break;

        case 'risk':
            $stmt = $db->prepare("SELECT * FROM risk_logs ORDER BY id DESC LIMIT :lim");
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode(array_reverse($stmt->fetchAll()));
            break;

        case 'alerts':
            $stmt = $db->prepare("SELECT * FROM alerts ORDER BY id DESC LIMIT :lim");
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode($stmt->fetchAll());
            break;

        case 'nodes':
            // Mark nodes offline if not seen in 5 minutes
            $db->exec("UPDATE nodes SET status = 'offline' WHERE last_seen < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
            $data = $db->query("SELECT * FROM nodes ORDER BY last_seen DESC")->fetchAll();
            echo json_encode($data);
            break;

        case 'disease':
            $stmt = $db->prepare("SELECT * FROM disease_detections ORDER BY id DESC LIMIT :lim");
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode($stmt->fetchAll());
            break;

        default:
            echo json_encode(["error" => "Unknown type"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}

