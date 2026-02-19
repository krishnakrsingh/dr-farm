<?php
/**
 * POST /api/gsm_alert.php
 * Triggers a GSM call alert via trigger_call mechanism + logs it.
 *
 * Body (JSON): { "phone": "9999999999", "message": "High risk detected" }
 */
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$input   = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$phone   = $input['phone']   ?? '';
$message = $input['message'] ?? 'GSM call alert triggered by DrFarm';

$db = getDB();

// Activate trigger state for SIM900A master node
$db->exec("UPDATE trigger_state SET state = 1 WHERE id = 1");

// Log alert
$db->prepare("
    INSERT INTO alerts (type, channel, message, phone, resolved, created_at)
    VALUES ('gsm', 'gsm', :msg, :phone, 0, NOW())
")->execute([':msg' => $message, ':phone' => $phone]);

echo json_encode([
    'success' => true,
    'trigger' => 1,
    'phone'   => $phone,
    'message' => $message,
    'note'    => 'SIM900A trigger activated. Master node will detect and place call.',
]);

