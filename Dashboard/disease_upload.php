<?php
/**
 * POST /disease_upload.php
 * Accepts a crop leaf image, stores it, optionally proxies to Flask AI server,
 * and saves the detection result.
 *
 * Form fields:
 *   leaf_image   — file upload
 *   flask_url    — (optional) Flask AI endpoint, e.g. http://localhost:5000/predict
 *
 * If Flask is unreachable, a demo/mock prediction is returned.
 */
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "POST required"]);
    exit;
}

// ── Upload handling ───────────────────
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

if (!isset($_FILES['leaf_image']) || $_FILES['leaf_image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["error" => "leaf_image file required"]);
    exit;
}

$ext  = strtolower(pathinfo($_FILES['leaf_image']['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp'];
if (!in_array($ext, $allowed)) {
    http_response_code(400);
    echo json_encode(["error" => "Only JPG, PNG, WEBP allowed"]);
    exit;
}

$filename = 'leaf_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
$filepath = $uploadDir . $filename;
move_uploaded_file($_FILES['leaf_image']['tmp_name'], $filepath);

// ── AI Prediction ─────────────────────
$flaskUrl = $_POST['flask_url'] ?? '';
$prediction = null;

if ($flaskUrl) {
    // Proxy to Flask AI server
    $ch = curl_init();
    $cfile = new CURLFile($filepath, mime_content_type($filepath), $filename);
    curl_setopt_array($ch, [
        CURLOPT_URL            => $flaskUrl,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => ['file' => $cfile],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $resp) {
        $prediction = json_decode($resp, true);
    }
}

// Fallback: demo prediction
if (!$prediction || !isset($prediction['disease_name'])) {
    $demos = [
        ['disease_name' => 'Leaf Blast',     'confidence' => 87.3, 'severity' => 'High'],
        ['disease_name' => 'Brown Spot',     'confidence' => 72.1, 'severity' => 'Medium'],
        ['disease_name' => 'Leaf Rust',      'confidence' => 91.5, 'severity' => 'Critical'],
        ['disease_name' => 'Powdery Mildew', 'confidence' => 65.8, 'severity' => 'Medium'],
        ['disease_name' => 'Healthy',        'confidence' => 96.2, 'severity' => 'Low'],
    ];
    $prediction = $demos[array_rand($demos)];
}

$diseaseName = $prediction['disease_name'];
$confidence  = floatval($prediction['confidence']);
$severity    = $prediction['severity'] ?? 'Medium';

// ── Save to DB ────────────────────────
try {
    $db   = getDB();
    $stmt = $db->prepare("
        INSERT INTO disease_detections (image_path, disease_name, confidence, severity, created_at)
        VALUES (:img, :name, :conf, :sev, NOW())
    ");
    $stmt->execute([
        ':img'  => 'uploads/' . $filename,
        ':name' => $diseaseName,
        ':conf' => $confidence,
        ':sev'  => $severity,
    ]);

    echo json_encode([
        'success'       => true,
        'disease_name'  => $diseaseName,
        'confidence'    => $confidence,
        'severity'      => $severity,
        'image'         => 'uploads/' . $filename,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}

