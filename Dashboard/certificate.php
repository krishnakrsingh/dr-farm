<?php
require_once __DIR__ . '/includes/lang.php';
$pageTitle   = __('crop_health_cert');
$currentPage = 'certificate';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/db.php';

$db = getDB();
$cert = null;

// Generate certificate on request
if (isset($_GET['generate'])) {
    $risk = $db->query("SELECT * FROM risk_logs ORDER BY id DESC LIMIT 1")->fetch();
    $disease = $db->query("SELECT * FROM disease_detections ORDER BY id DESC LIMIT 1")->fetch();
    $sensor  = $db->query("SELECT * FROM sensor_readings ORDER BY id DESC LIMIT 1")->fetch();

    $healthScore = $risk ? $risk['farm_health_score'] : 80;
    $riskPct     = $risk ? $risk['risk_percentage'] : 20;
    $diseaseSt   = $disease ? $disease['disease_name'] . ' (' . $disease['severity'] . ')' : 'Healthy';

    if ($healthScore >= 85)      $grade = 'A+';
    elseif ($healthScore >= 70)  $grade = 'A';
    elseif ($healthScore >= 55)  $grade = 'B';
    elseif ($healthScore >= 40)  $grade = 'C';
    else                         $grade = 'D';

    $riskLevel = $riskPct > 65 ? 'High' : ($riskPct > 40 ? 'Medium' : 'Low');

    $sensorSummary = $sensor
        ? "Temp: {$sensor['temperature']}°C, Humidity: {$sensor['humidity']}%, MQ7: {$sensor['mq7']}, Rain: " . ($sensor['rain'] ? 'Yes' : 'No')
        : 'No sensor data';

    $certCode = 'DF-' . strtoupper(substr(md5(uniqid()), 0, 8));
    $validUntil = date('Y-m-d H:i:s', strtotime('+30 days'));

    $stmt = $db->prepare("
        INSERT INTO certificates (cert_code, health_score, risk_level, disease_status, sensor_summary, grade, valid_until)
        VALUES (:code, :health, :risk, :disease, :sensor, :grade, :valid)
    ");
    $stmt->execute([
        ':code'    => $certCode,
        ':health'  => $healthScore,
        ':risk'    => $riskLevel,
        ':disease' => $diseaseSt,
        ':sensor'  => $sensorSummary,
        ':grade'   => $grade,
        ':valid'   => $validUntil,
    ]);

    header("Location: certificate.php?code={$certCode}");
    exit;
}

// Load certificate
if (isset($_GET['code'])) {
    $stmt = $db->prepare("SELECT * FROM certificates WHERE cert_code = :c");
    $stmt->execute([':c' => $_GET['code']]);
    $cert = $stmt->fetch();
}

if (!$cert) {
    $cert = $db->query("SELECT * FROM certificates ORDER BY id DESC LIMIT 1")->fetch();
}
?>

<!-- Generate Button -->
<div style="text-align:center;margin-bottom:1.5rem">
    <a href="certificate.php?generate=1" class="btn btn-primary btn-lg"><?= __('generate_cert') ?></a>
</div>

<?php if ($cert): ?>
<!-- ═══ Certificate Card ═══ -->
<div class="cert-card animate-in">
    <div class="cert-badge">🏅</div>
    <div class="cert-title"><?= __('cert_title') ?></div>
    <div class="cert-subtitle"><?= __('cert_subtitle') ?></div>

    <div style="margin:1.5rem 0">
        <div class="cert-grade"><?= $cert['grade'] ?></div>
        <div class="cert-grade-label"><?= __('overall_grade') ?></div>
    </div>

    <div class="cert-details">
        <div class="cert-detail-item">
            <div class="cert-detail-label"><?= __('cert_code') ?></div>
            <div class="cert-detail-val"><?= htmlspecialchars($cert['cert_code']) ?></div>
        </div>
        <div class="cert-detail-item">
            <div class="cert-detail-label"><?= __('farm_name') ?></div>
            <div class="cert-detail-val"><?= htmlspecialchars($cert['farm_name']) ?></div>
        </div>
        <div class="cert-detail-item">
            <div class="cert-detail-label"><?= __('health_score') ?></div>
            <div class="cert-detail-val" style="color:<?= $cert['health_score'] >= 70 ? 'var(--green)' : ($cert['health_score'] >= 40 ? 'var(--yellow)' : 'var(--red)') ?>">
                <?= $cert['health_score'] ?>%
            </div>
        </div>
        <div class="cert-detail-item">
            <div class="cert-detail-label"><?= __('risk_level') ?></div>
            <div class="cert-detail-val"><?= htmlspecialchars($cert['risk_level']) ?></div>
        </div>
        <div class="cert-detail-item">
            <div class="cert-detail-label"><?= __('disease_status') ?></div>
            <div class="cert-detail-val"><?= htmlspecialchars($cert['disease_status']) ?></div>
        </div>
        <div class="cert-detail-item">
            <div class="cert-detail-label"><?= __('sensor_summary') ?></div>
            <div class="cert-detail-val" style="font-size:0.85rem"><?= htmlspecialchars($cert['sensor_summary']) ?></div>
        </div>
        <div class="cert-detail-item">
            <div class="cert-detail-label"><?= __('issued_at') ?></div>
            <div class="cert-detail-val"><?= $cert['issued_at'] ?></div>
        </div>
        <div class="cert-detail-item">
            <div class="cert-detail-label"><?= __('valid_until') ?></div>
            <div class="cert-detail-val"><?= $cert['valid_until'] ?></div>
        </div>
    </div>

    <div class="cert-footer">
        <?= __('cert_footer') ?><br>
        Verification: <strong><?= htmlspecialchars($cert['cert_code']) ?></strong> &nbsp;|&nbsp;
        <?= $cert['grade'] === 'A+' || $cert['grade'] === 'A' ? __('msp_eligible') : __('msp_improve') ?>
    </div>
</div>

<!-- ═══ Actions ═══ -->
<div style="text-align:center;margin-top:1.5rem;display:flex;gap:0.75rem;justify-content:center;flex-wrap:wrap">
    <button class="btn btn-primary btn-sm" onclick="window.print()"><?= __('print_cert') ?></button>
    <button class="btn btn-outline btn-sm" onclick="shareCert()"><?= __('share_wa') ?></button>
    <a href="marketplace.php" class="btn btn-outline btn-sm"><?= __('use_marketplace') ?></a>
    <a href="loans.php" class="btn btn-outline btn-sm"><?= __('apply_loan') ?></a>
</div>

<?php else: ?>
<div class="panel">
    <div class="empty-state">
        <div class="es-icon">📜</div>
        <?= __('no_cert_yet') ?>
    </div>
</div>
<?php endif; ?>

<!-- ═══ Certificate History ═══ -->
<?php
$certs = $db->query("SELECT * FROM certificates ORDER BY id DESC LIMIT 10")->fetchAll();
if (!empty($certs)):
?>
<div class="panel animate-in" style="margin-top:1.5rem">
    <div class="panel-header">
        <div class="panel-title">📚 <?= __('cert_history') ?></div>
    </div>
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:0.82rem">
            <thead><tr style="border-bottom:2px solid var(--border);text-align:left">
                <th style="padding:0.5rem"><?= __('cert_code') ?></th>
                <th style="padding:0.5rem"><?= __('grade') ?></th>
                <th style="padding:0.5rem"><?= __('health') ?></th>
                <th style="padding:0.5rem"><?= __('risk') ?></th>
                <th style="padding:0.5rem"><?= __('latest_disease') ?></th>
                <th style="padding:0.5rem"><?= __('issued_at') ?></th>
                <th style="padding:0.5rem"><?= __('valid_until') ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($certs as $c): ?>
                <tr style="border-bottom:1px solid var(--border)">
                    <td style="padding:0.4rem 0.5rem"><a href="certificate.php?code=<?= urlencode($c['cert_code']) ?>" style="color:var(--accent);font-weight:600;text-decoration:none"><?= htmlspecialchars($c['cert_code']) ?></a></td>
                    <td style="padding:0.4rem 0.5rem;font-weight:700"><?= $c['grade'] ?></td>
                    <td style="padding:0.4rem 0.5rem"><?= $c['health_score'] ?>%</td>
                    <td style="padding:0.4rem 0.5rem"><?= htmlspecialchars($c['risk_level']) ?></td>
                    <td style="padding:0.4rem 0.5rem"><?= htmlspecialchars($c['disease_status']) ?></td>
                    <td style="padding:0.4rem 0.5rem"><?= $c['issued_at'] ?></td>
                    <td style="padding:0.4rem 0.5rem"><?= $c['valid_until'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php
$extraScripts = <<<'JS'
<script>
async function shareCert() {
    const code = document.querySelector('.cert-detail-val')?.textContent || '';
    const msg = `📜 DrFarm Crop Health Certificate\n\nCode: ${code}\nVerify at DrFarm Dashboard\n\nIssued by DrFarm IoT + AI Smart Farming System`;

    await fetch('api/whatsapp.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ message: msg })
    });
    toast('📱 WhatsApp ✓');
}
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>
