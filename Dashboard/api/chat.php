<?php
/**
 * POST /api/chat.php
 * OpenAI-powered agriculture chatbot.
 *
 * Body (JSON): { "message": "How to treat leaf blast?" }
 * Returns:     { "reply": "..." }
 */
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "POST required"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userMsg = trim($input['message'] ?? '');

if (!$userMsg) {
    http_response_code(400);
    echo json_encode(["error" => "message required"]);
    exit;
}

$cfg    = require __DIR__ . '/../config.php';
$apiKey = $cfg['openai']['api_key'] ?? '';
$model  = $cfg['openai']['model'] ?? 'gpt-4o';

$db = getDB();

// Save user message
$db->prepare("INSERT INTO chat_messages (role, message) VALUES ('user', :m)")->execute([':m' => $userMsg]);

// Build context from recent messages
$recent = $db->query("SELECT role, message FROM chat_messages ORDER BY id DESC LIMIT 20")->fetchAll();
$recent = array_reverse($recent);

// Get latest farm context
$sensorCtx = '';
$sensor = $db->query("SELECT * FROM sensor_readings ORDER BY id DESC LIMIT 1")->fetch();
if ($sensor) {
    $sensorCtx = "Current sensor: Temp={$sensor['temperature']}°C, Humidity={$sensor['humidity']}%, MQ7={$sensor['mq7']}, MQ3={$sensor['mq3']}, Rain=" . ($sensor['rain'] ? 'Yes' : 'No');
}

$diseaseCtx = '';
$disease = $db->query("SELECT * FROM disease_detections ORDER BY id DESC LIMIT 1")->fetch();
if ($disease) {
    $diseaseCtx = "Latest disease: {$disease['disease_name']} (Confidence: {$disease['confidence']}%, Severity: {$disease['severity']})";
}

$riskCtx = '';
$risk = $db->query("SELECT * FROM risk_logs ORDER BY id DESC LIMIT 1")->fetch();
if ($risk) {
    $riskCtx = "Risk: {$risk['risk_percentage']}%, Farm Health: {$risk['farm_health_score']}%";
}

$systemPrompt = <<<SYS
You are DrFarm AI Assistant — an expert Indian agriculture advisor.
You help farmers with crop disease management, treatment recommendations, weather-based farming advice, and smart farming practices.

Current Farm Data:
{$sensorCtx}
{$diseaseCtx}
{$riskCtx}

Guidelines:
- Give practical, actionable advice specific to Indian farming context
- Mention specific product names, dosages, and methods when relevant
- Keep answers clear, concise, and farmer-friendly
- Use Hindi/English mixed terms when helpful (e.g., "kharif season", "rabi crop")
- If asked about non-farming topics, politely redirect to agriculture
- Reference the current sensor/disease data when relevant
SYS;

$messages = [['role' => 'system', 'content' => $systemPrompt]];
foreach ($recent as $m) {
    $messages[] = ['role' => $m['role'], 'content' => $m['message']];
}

$reply = '';

if ($apiKey && $apiKey !== 'YOUR_OPENAI_API_KEY_HERE') {
    $payload = [
        'model'       => $model,
        'messages'    => $messages,
        'max_tokens'  => 800,
        'temperature' => 0.7,
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT    => 60,
    ]);

    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $resp) {
        $data  = json_decode($resp, true);
        $reply = $data['choices'][0]['message']['content'] ?? '';
    }
}

// Fallback demo responses
if (!$reply) {
    $fallbacks = [
        'leaf blast'    => "🌾 **Leaf Blast Treatment:**\n\nLeaf Blast (caused by *Pyricularia oryzae*) is common in high-humidity conditions.\n\n**Immediate Steps:**\n1. Spray **Tricyclazole 75% WP** at 0.6g per litre of water\n2. Reduce nitrogen fertilizer application\n3. Drain excess standing water\n4. Ensure proper spacing between plants\n\n**Prevention:** Use resistant varieties like Pusa Basmati-1, and apply preventive fungicide during tillering stage.\n\nYour current humidity is high — this increases blast risk. Monitor closely!",
        'brown spot'    => "🍂 **Brown Spot Management:**\n\nBrown Spot (*Bipolaris oryzae*) typically affects nutrient-deficient crops.\n\n**Treatment:**\n1. Spray **Mancozeb 75% WP** at 2.5g/L\n2. Apply balanced NPK fertilizer, especially potash\n3. Improve field drainage\n4. Apply **silicon** foliar spray for leaf resistance\n\n**Tip:** Seed treatment with Carbendazim before sowing prevents Brown Spot in next season.",
        'healthy'       => "✅ **Great news!** Your crops appear healthy.\n\n**Maintenance Tips:**\n1. Continue regular field monitoring\n2. Maintain consistent irrigation schedule\n3. Apply light preventive Mancozeb spray every 15 days during monsoon\n4. Upload leaf images weekly for AI tracking\n\nKeep up the good farming practices! 🌱",
        'default'       => "🌿 **DrFarm AI Assistant**\n\nI can help you with:\n- 🔬 Disease identification & treatment\n- 💧 Irrigation & water management\n- 🌱 Crop nutrition advice\n- 🌦️ Weather-based farming tips\n- 📊 Understanding your sensor data\n\nBased on your current farm data, your sensors are active and monitoring. Upload a leaf image on the AI Detection page for disease analysis!\n\nAsk me anything about your farm! 🚜"
    ];

    $lowerMsg = strtolower($userMsg);
    $reply = $fallbacks['default'];
    foreach ($fallbacks as $key => $val) {
        if ($key !== 'default' && strpos($lowerMsg, $key) !== false) {
            $reply = $val;
            break;
        }
    }
}

// Save assistant reply
$db->prepare("INSERT INTO chat_messages (role, message) VALUES ('assistant', :m)")->execute([':m' => $reply]);

echo json_encode(['reply' => $reply]);

