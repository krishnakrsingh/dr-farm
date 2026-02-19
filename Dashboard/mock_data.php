<?php
/**
 * DrFarm — Mock Data Generator
 *
 * Usage:
 *   php mock_data.php seed       — Insert 60 historical readings (1 per minute for the last hour) + register 1 node
 *   php mock_data.php once       — Insert 1 new reading right now
 *   php mock_data.php loop       — Insert 1 reading every 60 seconds (runs forever, Ctrl+C to stop)
 *   php mock_data.php loop 30    — Same but every 30 seconds
 *
 * Can also be called via browser:
 *   mock_data.php?action=seed
 *   mock_data.php?action=once
 */

require_once __DIR__ . '/db.php';

// ── Configuration ────────────────────────────
$NODE_ID = 'ESP32_NODE_1';

// Realistic Indian farm ranges
$TEMP_MIN  = 24.0;   $TEMP_MAX  = 38.0;   // °C
$HUM_MIN   = 45.0;   $HUM_MAX   = 88.0;   // %
$MQ7_MIN   = 80;     $MQ7_MAX   = 520;    // ppm (CO — normal air ~100, elevated near farm machinery/fire)
$MQ3_MIN   = 50;     $MQ3_MAX   = 380;    // ppm (alcohol/organic vapour — fermentation, compost)
$RAIN_PROB = 0.20;                         // 20% chance of rain per reading

// ── Helpers ──────────────────────────────────
function randFloat(float $min, float $max, int $decimals = 1): float {
    return round($min + mt_rand() / mt_getrandmax() * ($max - $min), $decimals);
}

function generateReading(
    string $nodeId,
    float $tempMin, float $tempMax,
    float $humMin, float $humMax,
    int $mq7Min, int $mq7Max,
    int $mq3Min, int $mq3Max,
    float $rainProb,
    ?float $prevTemp = null,
    ?float $prevHum = null,
    ?int $prevMq7 = null,
    ?int $prevMq3 = null
): array {
    // Smooth values: new value drifts ±15% from previous for realistic continuity
    if ($prevTemp !== null) {
        $drift = randFloat(-1.5, 1.5);
        $temp = max($tempMin, min($tempMax, round($prevTemp + $drift, 1)));
    } else {
        $temp = randFloat($tempMin, $tempMax);
    }

    if ($prevHum !== null) {
        $drift = randFloat(-3.0, 3.0);
        $hum = max($humMin, min($humMax, round($prevHum + $drift, 1)));
    } else {
        $hum = randFloat($humMin, $humMax);
    }

    if ($prevMq7 !== null) {
        $drift = mt_rand(-30, 30);
        $mq7 = max($mq7Min, min($mq7Max, $prevMq7 + $drift));
    } else {
        $mq7 = mt_rand($mq7Min, $mq7Max);
    }

    if ($prevMq3 !== null) {
        $drift = mt_rand(-20, 20);
        $mq3 = max($mq3Min, min($mq3Max, $prevMq3 + $drift));
    } else {
        $mq3 = mt_rand($mq3Min, $mq3Max);
    }

    // Higher humidity → more likely rain
    $effectiveRainProb = $hum > 75 ? $rainProb * 2.0 : $rainProb;
    $rain = (mt_rand(1, 100) / 100.0) <= $effectiveRainProb ? 1 : 0;

    return [
        'node_id'     => $nodeId,
        'temperature' => $temp,
        'humidity'    => $hum,
        'mq7'         => $mq7,
        'mq3'         => $mq3,
        'rain'        => $rain,
    ];
}

function insertReading(PDO $db, array $r, ?string $createdAt = null): void {
    $sql = "INSERT INTO sensor_readings (node_id, temperature, humidity, mq7, mq3, rain, created_at)
            VALUES (:node_id, :temperature, :humidity, :mq7, :mq3, :rain, :created_at)";
    $db->prepare($sql)->execute([
        ':node_id'     => $r['node_id'],
        ':temperature' => $r['temperature'],
        ':humidity'    => $r['humidity'],
        ':mq7'         => $r['mq7'],
        ':mq3'         => $r['mq3'],
        ':rain'        => $r['rain'],
        ':created_at'  => $createdAt ?? date('Y-m-d H:i:s'),
    ]);
}

function upsertNode(PDO $db, string $nodeId): void {
    $db->prepare("
        INSERT INTO nodes (node_id, name, location, last_seen, status)
        VALUES (:nid, :name, :loc, NOW(), 'online')
        ON DUPLICATE KEY UPDATE last_seen = NOW(), status = 'online'
    ")->execute([
        ':nid'  => $nodeId,
        ':name' => 'Field Sensor Node 1',
        ':loc'  => 'Main Field — North Block',
    ]);
}

function getLastReading(PDO $db, string $nodeId): ?array {
    $stmt = $db->prepare("SELECT temperature, humidity, mq7, mq3 FROM sensor_readings WHERE node_id = :nid ORDER BY id DESC LIMIT 1");
    $stmt->execute([':nid' => $nodeId]);
    return $stmt->fetch() ?: null;
}

// ── Determine action ─────────────────────────
$isCLI = (php_sapi_name() === 'cli');
$action = $isCLI ? ($argv[1] ?? 'seed') : ($_GET['action'] ?? 'once');
$interval = $isCLI ? intval($argv[2] ?? 60) : 60;

if (!$isCLI) {
    header('Content-Type: application/json; charset=utf-8');
}

$db = getDB();

// ══════════════════════════════════════════════
// ACTION: seed — backfill 60 readings + risk logs
// ══════════════════════════════════════════════
if ($action === 'seed') {
    $count = 60;
    $now   = time();

    // Register node
    upsertNode($db, $NODE_ID);

    $prevTemp = null; $prevHum = null; $prevMq7 = null; $prevMq3 = null;

    for ($i = $count; $i >= 0; $i--) {
        $ts = date('Y-m-d H:i:s', $now - ($i * 60));

        $r = generateReading(
            $NODE_ID,
            $TEMP_MIN, $TEMP_MAX, $HUM_MIN, $HUM_MAX,
            $MQ7_MIN, $MQ7_MAX, $MQ3_MIN, $MQ3_MAX,
            $RAIN_PROB,
            $prevTemp, $prevHum, $prevMq7, $prevMq3
        );

        insertReading($db, $r, $ts);

        $prevTemp = $r['temperature'];
        $prevHum  = $r['humidity'];
        $prevMq7  = $r['mq7'];
        $prevMq3  = $r['mq3'];

        // Also generate a risk log every 5th reading
        if ($i % 5 === 0) {
            $envRisk = 0;
            if ($r['temperature'] > 35 || $r['temperature'] < 15) $envRisk += 15;
            if ($r['humidity'] > 75) $envRisk += 12;
            if ($r['mq7'] > 400) $envRisk += 10;
            if ($r['mq3'] > 350) $envRisk += 8;
            if ($r['rain']) $envRisk += 5;
            $envRisk = min(50, $envRisk);

            $diseaseRisk = mt_rand(0, 25);
            $riskPct = min(100, $envRisk + $diseaseRisk);
            $health  = max(0, 100 - $riskPct);
            $alert   = $riskPct > 65 ? 1 : 0;

            $db->prepare("
                INSERT INTO risk_logs (risk_percentage, farm_health_score, env_risk, disease_risk, alert_triggered, created_at)
                VALUES (:rp, :fh, :er, :dr, :at, :ca)
            ")->execute([
                ':rp' => $riskPct,
                ':fh' => $health,
                ':er' => $envRisk,
                ':dr' => $diseaseRisk,
                ':at' => $alert,
                ':ca' => $ts,
            ]);
        }
    }

    // Seed one disease detection for demo
    $db->prepare("
        INSERT INTO disease_detections (disease_name, confidence, severity, analysis, created_at)
        VALUES (:dn, :cf, :sv, :an, :ca)
    ")->execute([
        ':dn' => 'Leaf Blast (Magnaporthe oryzae)',
        ':cf' => 78.5,
        ':sv' => 'Medium',
        ':an' => 'Fungal infection detected on rice leaves. Diamond-shaped lesions with gray centers observed. Likely caused by high humidity and warm temperatures. Recommend fungicide application and field drainage.',
        ':ca' => date('Y-m-d H:i:s', $now - 1800),
    ]);

    $msg = "✅ Seeded " . ($count + 1) . " sensor readings, " . ceil($count / 5) . " risk logs, 1 disease detection, and registered node '{$NODE_ID}'.";

    if ($isCLI) {
        echo $msg . "\n";
    } else {
        echo json_encode(['success' => true, 'message' => $msg]);
    }
    exit;
}

// ══════════════════════════════════════════════
// ACTION: once — insert 1 new reading
// ══════════════════════════════════════════════
if ($action === 'once') {
    $prev = getLastReading($db, $NODE_ID);

    $r = generateReading(
        $NODE_ID,
        $TEMP_MIN, $TEMP_MAX, $HUM_MIN, $HUM_MAX,
        $MQ7_MIN, $MQ7_MAX, $MQ3_MIN, $MQ3_MAX,
        $RAIN_PROB,
        $prev['temperature'] ?? null,
        $prev['humidity'] ?? null,
        $prev['mq7'] ?? null,
        $prev['mq3'] ?? null
    );

    insertReading($db, $r);
    upsertNode($db, $NODE_ID);

    $msg = "📡 Inserted: Temp={$r['temperature']}°C, Hum={$r['humidity']}%, MQ7={$r['mq7']}, MQ3={$r['mq3']}, Rain={$r['rain']}";

    if ($isCLI) {
        echo $msg . "\n";
    } else {
        echo json_encode(['success' => true, 'reading' => $r, 'message' => $msg]);
    }
    exit;
}

// ══════════════════════════════════════════════
// ACTION: loop — continuous insertion every N sec
// ══════════════════════════════════════════════
if ($action === 'loop') {
    if (!$isCLI) {
        echo json_encode(['error' => 'loop mode is CLI-only. Use: php mock_data.php loop [seconds]']);
        exit;
    }

    $interval = max(10, $interval);
    echo "🔄 Mock data loop started — inserting every {$interval}s for node '{$NODE_ID}'. Press Ctrl+C to stop.\n\n";

    // Register node first
    upsertNode($db, $NODE_ID);

    while (true) {
        $prev = getLastReading($db, $NODE_ID);

        $r = generateReading(
            $NODE_ID,
            $TEMP_MIN, $TEMP_MAX, $HUM_MIN, $HUM_MAX,
            $MQ7_MIN, $MQ7_MAX, $MQ3_MIN, $MQ3_MAX,
            $RAIN_PROB,
            $prev['temperature'] ?? null,
            $prev['humidity'] ?? null,
            $prev['mq7'] ?? null,
            $prev['mq3'] ?? null
        );

        insertReading($db, $r);
        upsertNode($db, $NODE_ID);

        $ts = date('H:i:s');
        echo "[{$ts}] Temp={$r['temperature']}°C  Hum={$r['humidity']}%  MQ7={$r['mq7']}  MQ3={$r['mq3']}  Rain=" . ($r['rain'] ? '🌧 Yes' : '☀ No') . "\n";

        sleep($interval);
    }
}

// Unknown action
if ($isCLI) {
    echo "Usage: php mock_data.php [seed|once|loop] [interval_seconds]\n";
} else {
    echo json_encode(['error' => "Unknown action: {$action}. Use seed, once, or loop."]);
}

