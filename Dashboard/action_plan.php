<?php
/**
 * GET  /action_plan.php              → Get latest action plan
 * GET  /action_plan.php?generate=1   → Generate a new 7-day plan based on current risk + disease
 */
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// ── Disease-specific 7-day plans ──────
function getPlanTemplate(string $disease, string $riskLevel): array {
    $plans = [
        'Leaf Blast' => [
            ['🔬', 'Inspect & Isolate',          'Identify affected areas. Isolate severely infected plants. Mark zones for treatment.'],
            ['💧', 'Reduce Irrigation',           'Lower water frequency to reduce leaf wetness. Avoid overhead irrigation.'],
            ['🧪', 'Apply Tricyclazole Fungicide','Spray Tricyclazole 75% WP @ 0.6g/L on affected and surrounding areas.'],
            ['🌱', 'Nutrient Boost',              'Apply potassium-rich fertilizer to strengthen plant cell walls against fungal penetration.'],
            ['🔍', 'Monitor Progress',            'Check treated areas for new lesions. Document leaf condition with photos.'],
            ['🧪', 'Second Fungicide Round',      'If lesions persist, apply Isoprothiolane 40% EC as second-line treatment.'],
            ['📊', 'Evaluate & Report',           'Assess overall recovery. Update DrFarm dashboard. Plan next cycle actions.'],
        ],
        'Brown Spot' => [
            ['🔬', 'Field Scouting',              'Walk the field and identify brown spot lesion patterns. Note severity per zone.'],
            ['🧹', 'Remove Debris',               'Clear fallen leaves and crop residue to reduce fungal inoculum source.'],
            ['🧪', 'Apply Mancozeb Spray',        'Spray Mancozeb 75% WP @ 2.5g/L. Ensure full leaf coverage.'],
            ['🌾', 'Silicon Supplement',           'Apply silicon-based foliar spray to improve leaf resistance.'],
            ['💧', 'Optimize Water Management',    'Ensure proper drainage. Maintain intermittent irrigation, not continuous flooding.'],
            ['🔍', 'Re-inspect Fields',            'Check if lesion spread has stopped. Compare with Day 1 observations.'],
            ['📊', 'Health Assessment',            'Score plant recovery. Record farm health data and plan preventive schedule.'],
        ],
        'Leaf Rust' => [
            ['🚨', 'Emergency Assessment',        'Leaf Rust spreads fast. Identify all affected zones immediately.'],
            ['🧪', 'Apply Propiconazole',         'Spray Propiconazole 25% EC @ 1ml/L. Prioritize heavily infected areas.'],
            ['🌬️', 'Improve Air Circulation',     'Thin canopy if dense. Remove weeds for better airflow between rows.'],
            ['🌱', 'Resistant Variety Planning',   'Source rust-resistant seed varieties for next planting cycle.'],
            ['🔍', 'Monitor Spread',               'Check neighboring fields. Rust spores travel via wind — alert nearby farmers.'],
            ['🧪', 'Follow-up Spray',             'Apply Tebuconazole 25.9% EC if rust persists after first treatment.'],
            ['📊', 'Recovery Report',              'Document before/after. Update DrFarm risk assessment. Share learnings.'],
        ],
        'Powdery Mildew' => [
            ['🔬', 'Identify Infection Zones',    'Look for white powdery coating on leaf surfaces. Map affected zones.'],
            ['🌬️', 'Increase Ventilation',        'Space plants better. Remove excessive foliage for airflow.'],
            ['🧪', 'Apply Sulfur Spray',          'Spray wettable sulfur 80% WP @ 3g/L in early morning or evening.'],
            ['💧', 'Adjust Humidity',              'Reduce humidity around canopy. Use drip irrigation instead of overhead.'],
            ['🌱', 'Apply Neem Oil',               'Spray neem oil solution (2%) as organic preventive on unaffected plants.'],
            ['🔍', 'Progress Check',               'Inspect treated zones. Mildew should reduce within 72-96 hours of treatment.'],
            ['📊', 'Final Assessment',             'Evaluate recovery. Score farm health. Plan next preventive cycle.'],
        ],
        'Healthy' => [
            ['✅', 'Routine Monitoring',           'Crops are healthy. Continue regular field walks and visual inspection.'],
            ['💧', 'Maintain Irrigation Schedule', 'Keep consistent watering cycle. Ensure no waterlogging in any zone.'],
            ['🌱', 'Balanced Fertilization',       'Apply scheduled NPK dose. Avoid over-fertilization.'],
            ['🛡️', 'Preventive Spray',             'Apply light preventive fungicide (Mancozeb) as prophylactic measure.'],
            ['📸', 'Photo Documentation',          'Take weekly leaf photos for AI tracking and comparison.'],
            ['🌾', 'Soil Health Check',            'Test soil pH and moisture levels. Maintain optimal growing conditions.'],
            ['📊', 'Weekly Report',                'Update farm dashboard. Review sensor trends. Plan ahead.'],
        ],
    ];

    // Default generic plan
    $default = [
        ['🔬', 'Initial Assessment',       'Thoroughly inspect all fields. Document any abnormalities in crop health.'],
        ['🧹', 'Sanitation & Cleanup',     'Remove diseased material, weeds, and debris from the field.'],
        ['🧪', 'Apply Recommended Treatment','Based on diagnosis, apply appropriate fungicide or treatment spray.'],
        ['🌱', 'Nutritional Support',       'Supplement with balanced fertilizer to boost plant immunity.'],
        ['💧', 'Water Management',          'Optimize irrigation. Avoid excess moisture on leaf surfaces.'],
        ['🔍', 'Monitor & Reassess',        'Check treatment effectiveness. Look for new symptoms.'],
        ['📊', 'Review & Plan Ahead',       'Generate farm health report. Plan next week\'s preventive actions.'],
    ];

    return $plans[$disease] ?? $default;
}

try {
    $db = getDB();

    // ── Generate new plan ─────────────
    if (isset($_GET['generate'])) {
        // Get latest disease detection
        $disease = $db->query("SELECT * FROM disease_detections ORDER BY id DESC LIMIT 1")->fetch();
        $diseaseName = $disease ? $disease['disease_name'] : 'Healthy';
        $severity    = $disease ? $disease['severity'] : 'Low';

        // Get latest risk
        $risk = $db->query("SELECT * FROM risk_logs ORDER BY id DESC LIMIT 1")->fetch();
        $riskLevel = 'Low';
        if ($risk) {
            $rp = $risk['risk_percentage'];
            if ($rp > 65) $riskLevel = 'Critical';
            elseif ($rp > 45) $riskLevel = 'High';
            elseif ($rp > 25) $riskLevel = 'Medium';
        }

        $groupId = 'PLAN_' . date('Ymd_His');
        $template = getPlanTemplate($diseaseName, $riskLevel);

        $stmt = $db->prepare("
            INSERT INTO action_plans (plan_group_id, disease_name, risk_level, day_number, action_title, action_desc, icon)
            VALUES (:gid, :disease, :risk, :day, :title, :desc, :icon)
        ");

        $days = [];
        foreach ($template as $i => $item) {
            $dayNum = $i + 1;
            $stmt->execute([
                ':gid'     => $groupId,
                ':disease' => $diseaseName,
                ':risk'    => $riskLevel,
                ':day'     => $dayNum,
                ':title'   => $item[1],
                ':desc'    => $item[2],
                ':icon'    => $item[0],
            ]);
            $days[] = [
                'day'    => $dayNum,
                'icon'   => $item[0],
                'title'  => $item[1],
                'description' => $item[2],
            ];
        }

        echo json_encode([
            'plan_group_id' => $groupId,
            'disease'       => $diseaseName,
            'risk_level'    => $riskLevel,
            'days'          => $days,
        ]);
        exit;
    }

    // ── Get latest plan ───────────────
    $latest = $db->query("SELECT plan_group_id FROM action_plans ORDER BY id DESC LIMIT 1")->fetch();

    if (!$latest) {
        echo json_encode(["error" => "No action plan generated yet", "days" => []]);
        exit;
    }

    $gid  = $latest['plan_group_id'];
    $rows = $db->prepare("SELECT * FROM action_plans WHERE plan_group_id = :gid ORDER BY day_number ASC");
    $rows->execute([':gid' => $gid]);
    $plans = $rows->fetchAll();

    $days = [];
    $diseaseName = '';
    $riskLevel   = '';
    foreach ($plans as $p) {
        $diseaseName = $p['disease_name'];
        $riskLevel   = $p['risk_level'];
        $days[] = [
            'day'         => (int) $p['day_number'],
            'icon'        => $p['icon'],
            'title'       => $p['action_title'],
            'description' => $p['action_desc'],
        ];
    }

    echo json_encode([
        'plan_group_id' => $gid,
        'disease'       => $diseaseName,
        'risk_level'    => $riskLevel,
        'days'          => $days,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}

