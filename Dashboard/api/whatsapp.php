<?php
/**
 * POST /api/whatsapp.php
 * Sends WhatsApp message via waclient.com API (Send Text endpoint).
 * Docs: https://waclient.com/docs/whatsapp-web-api
 *
 * Body (JSON):
 * {
 *   "phone": "919999999999",      // optional, uses config default
 *   "message": "High risk alert!"
 * }
 *
 * waclient.com Send Text API:
 *   POST https://waclient.com/api/send
 *   {
 *     "number":       "919999999999",
 *     "type":         "text",
 *     "message":      "Hello!",
 *     "instance_id":  "609ACF283XXXX",
 *     "access_token": "EMCUH3NQQK8YXXXX"
 *   }
 */
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$cfg = require __DIR__ . '/../config.php';
$wa  = $cfg['whatsapp'] ?? [];

// Accept both JSON body and form POST
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$phone   = $input['phone']   ?? $wa['default_to'] ?? '';
$message = $input['message'] ?? '';

if (!$message) {
    http_response_code(400);
    echo json_encode(["error" => "message required"]);
    exit;
}

if (!$phone) {
    http_response_code(400);
    echo json_encode(["error" => "phone number required"]);
    exit;
}

$db = getDB();

// ── Send via waclient.com Send Text API ─────
$apiUrl      = $wa['api_url']      ?? 'https://waclient.com/api/send';
$accessToken = $wa['access_token'] ?? '';
$instanceId  = $wa['instance_id']  ?? '';

$sent    = false;
$apiResp = '';

if ($accessToken && $accessToken !== 'YOUR_WACLIENT_ACCESS_TOKEN' && $instanceId && $instanceId !== 'YOUR_WACLIENT_INSTANCE_ID') {
    // Build payload per waclient.com docs
    $postData = [
        'number'       => $phone,
        'type'         => 'text',
        'message'      => $message,
        'instance_id'  => $instanceId,
        'access_token' => $accessToken,
    ];

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($postData),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 30,
    ]);

    $apiResp  = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // waclient returns {"status":"success",...} on success
    if ($httpCode >= 200 && $httpCode < 300 && $apiResp) {
        $respData = json_decode($apiResp, true);
        $sent = (($respData['status'] ?? '') === 'success');
    }
}

// ── Log alert in DB ─────────────────────
$db->prepare("
    INSERT INTO alerts (type, channel, message, phone, resolved, created_at)
    VALUES ('whatsapp', 'whatsapp', :msg, :phone, 0, NOW())
")->execute([':msg' => $message, ':phone' => $phone]);

echo json_encode([
    'success'      => true,
    'sent'         => $sent,
    'phone'        => $phone,
    'message'      => $message,
    'api_response' => $sent ? 'Delivered via waclient.com' : 'Demo mode — configure access_token & instance_id in config.php',
]);
