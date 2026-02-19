<?php
/**
 * GET /risk_engine.php
 * Calculates farm risk based on latest sensor data + latest disease detection.
 *
 * Returns JSON:
 * {
 *   "risk_percentage": 62.5,
 *   "farm_health_score": 37.5,
 *   "env_risk": 45.0,
 *   "disease_risk": 80.0,
 *   "factors": { ... },
 *   "alert_triggered": true
 * }
 */
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// ── Thresholds ──────────────────────────
define('HUMIDITY_HIGH',       75);    // %
define('TEMP_FUNGAL_LOW',     20);    // °C
define('TEMP_FUNGAL_HIGH',    30);    // °C
define('MQ7_ANOMALY',         400);
define('MQ3_ANOMALY',         350);
define('RISK_ALERT_THRESHOLD', 65);   // trigger call if risk > this

try {
    $db = getDB();

    // ── 1. Latest sensor data ────────────
    $sensor = $db->query("SELECT * FROM sensor_readings ORDER BY id DESC LIMIT 1")->fetch();
    if (!$sensor) {
        echo json_encode(["error" => "No sensor data"]);
        exit;
    }

    $temp     = (float) $sensor['temperature'];
    $humidity = (float) $sensor['humidity'];
    $mq7      = (int)   $sensor['mq7'];
    $mq3      = (int)   $sensor['mq3'];
    $rain     = (int)   $sensor['rain'];

    // ── 2. Environmental risk (0–100) ────
    $envFactors = [];
    $envScore   = 0;

    // Humidity factor (0–30 pts)
    if ($humidity >= HUMIDITY_HIGH) {
        $humidityPts = min(30, (($humidity - HUMIDITY_HIGH) / 25) * 30);
        $envScore += $humidityPts;
        $envFactors['high_humidity'] = round($humidityPts, 1);
    }

    // Temperature in fungal zone (0–25 pts)
    if ($temp >= TEMP_FUNGAL_LOW && $temp <= TEMP_FUNGAL_HIGH) {
        $mid = (TEMP_FUNGAL_LOW + TEMP_FUNGAL_HIGH) / 2;
        $dist = abs($temp - $mid);
        $maxDist = (TEMP_FUNGAL_HIGH - TEMP_FUNGAL_LOW) / 2;
        $tempPts = (1 - $dist / $maxDist) * 25;
        $envScore += $tempPts;
        $envFactors['fungal_temp_zone'] = round($tempPts, 1);
    }

    // Rain presence (0–20 pts)
    if ($rain > 0) {
        $envScore += 20;
        $envFactors['rain_detected'] = 20;
    }

    // MQ7 gas anomaly (0–15 pts)
    if ($mq7 >= MQ7_ANOMALY) {
        $gasPts = min(15, (($mq7 - MQ7_ANOMALY) / 300) * 15);
        $envScore += $gasPts;
        $envFactors['mq7_anomaly'] = round($gasPts, 1);
    }

    // MQ3 anomaly (0–10 pts)
    if ($mq3 >= MQ3_ANOMALY) {
        $mq3Pts = min(10, (($mq3 - MQ3_ANOMALY) / 250) * 10);
        $envScore += $mq3Pts;
        $envFactors['mq3_anomaly'] = round($mq3Pts, 1);
    }

    $envRisk = min(100, $envScore);

    // ── 3. Disease risk (0–100) ──────────
    $diseaseRisk = 0;
    $diseaseName = 'None';
    $diseaseConf = 0;
    $diseaseSev  = 'None';

    $disease = $db->query("SELECT * FROM disease_detections ORDER BY id DESC LIMIT 1")->fetch();
    if ($disease) {
        $diseaseConf = (float) $disease['confidence'];
        $diseaseName = $disease['disease_name'];
        $diseaseSev  = $disease['severity'];

        // Severity multiplier
        $sevMul = match (strtolower($diseaseSev)) {
            'critical' => 1.0,
            'high'     => 0.85,
            'medium'   => 0.6,
            'low'      => 0.35,
            default    => 0.3,
        };

        $diseaseRisk = min(100, $diseaseConf * $sevMul);
    }

    // ── 4. Combined risk ─────────────────
    // 50% env + 50% disease
    $riskPct = ($envRisk * 0.5) + ($diseaseRisk * 0.5);
    $riskPct = round(min(100, max(0, $riskPct)), 1);
    $healthScore = round(100 - $riskPct, 1);

    // ── 5. Alert trigger ─────────────────
    $alertTriggered = $riskPct > RISK_ALERT_THRESHOLD;
    if ($alertTriggered) {
        // Activate GSM trigger
        $db->exec("UPDATE trigger_state SET state = 1 WHERE id = 1");
        $msg = "🚨 Risk {$riskPct}% — Env: {$envRisk}%, Disease: {$diseaseName} ({$diseaseConf}%)";
        $db->prepare("INSERT INTO alerts (type, channel, message) VALUES ('risk', 'system', :msg)")->execute([':msg' => $msg]);

        // Auto-send WhatsApp alert via waclient.com Send Text API
        // Docs: https://waclient.com/docs/whatsapp-web-api
        $cfg = require __DIR__ . '/config.php';
        $wa  = $cfg['whatsapp'] ?? [];
        $accessToken = $wa['access_token'] ?? '';
        $instanceId  = $wa['instance_id']  ?? '';

        if ($accessToken && $accessToken !== 'YOUR_WACLIENT_ACCESS_TOKEN' && $instanceId && $instanceId !== 'YOUR_WACLIENT_INSTANCE_ID') {
            $waPayload = json_encode([
                'number'       => $wa['default_to'] ?? '',
                'type'         => 'text',
                'message'      => "🚨 DrFarm Alert!\n\nRisk: {$riskPct}%\nFarm Health: {$healthScore}%\nDisease: {$diseaseName}\nEnv Risk: {$envRisk}%\n\nCheck dashboard now!",
                'instance_id'  => $instanceId,
                'access_token' => $accessToken,
            ]);
            $ch = curl_init($wa['api_url'] ?? 'https://waclient.com/api/send');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $waPayload,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT        => 10,
            ]);
            curl_exec($ch);
            curl_close($ch);
            $db->prepare("INSERT INTO alerts (type, channel, message, phone) VALUES ('whatsapp', 'whatsapp', :msg, :phone)")
               ->execute([':msg' => $msg, ':phone' => $wa['default_to'] ?? '']);
        }
    }

    // ── 6. Log risk ──────────────────────
    $stmt = $db->prepare("
        INSERT INTO risk_logs (risk_percentage, farm_health_score, env_risk, disease_risk, factors_json, alert_triggered)
        VALUES (:risk, :health, :env, :dis, :factors, :alert)
    ");
    $stmt->execute([
        ':risk'    => $riskPct,
        ':health'  => $healthScore,
        ':env'     => round($envRisk, 1),
        ':dis'     => round($diseaseRisk, 1),
        ':factors' => json_encode($envFactors),
        ':alert'   => $alertTriggered ? 1 : 0,
    ]);

    // ── 7. Response ──────────────────────
    echo json_encode([
        'risk_percentage'   => $riskPct,
        'farm_health_score' => $healthScore,
        'env_risk'          => round($envRisk, 1),
        'disease_risk'      => round($diseaseRisk, 1),
        'disease_name'      => $diseaseName,
        'disease_confidence' => $diseaseConf,
        'disease_severity'  => $diseaseSev,
        'factors'           => $envFactors,
        'alert_triggered'   => $alertTriggered,
        'sensor'            => $sensor,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}

