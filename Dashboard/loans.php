<?php
require_once __DIR__ . '/includes/lang.php';
$pageTitle   = __('loans_lending');
$currentPage = 'loans';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/db.php';

$db = getDB();
$lenders = $db->query("SELECT * FROM loan_lenders ORDER BY interest_rate ASC")->fetchAll();

// Calculate creditworthiness from latest risk data
$creditScore = 0;
$creditGrade = 'N/A';
$healthAvg = 0;
$riskAvg = 0;

$riskData = $db->query("SELECT AVG(farm_health_score) as avg_health, AVG(risk_percentage) as avg_risk FROM (SELECT farm_health_score, risk_percentage FROM risk_logs ORDER BY id DESC LIMIT 30) sub")->fetch();
if ($riskData && $riskData['avg_health']) {
    $healthAvg = round($riskData['avg_health'], 1);
    $riskAvg   = round($riskData['avg_risk'], 1);
    $creditScore = round(300 + ($healthAvg / 100) * 600);
    $creditScore = min(900, max(300, $creditScore));
    if ($creditScore >= 750) $creditGrade = 'A+';
    elseif ($creditScore >= 650) $creditGrade = 'A';
    elseif ($creditScore >= 550) $creditGrade = 'B';
    elseif ($creditScore >= 450) $creditGrade = 'C';
    else $creditGrade = 'D';
}
?>

<!-- ═══ Creditworthiness Index ═══ -->
<div class="dash-grid" style="margin-bottom:1.25rem">
    <div class="panel animate-in">
        <div class="panel-header">
            <div class="panel-title">📊 <?= __('creditworthiness') ?></div>
            <span class="panel-badge purple"><?= __('ai_calculated') ?></span>
        </div>
        <div class="credit-card">
            <div style="font-size:0.78rem;opacity:0.7;text-transform:uppercase;letter-spacing:0.06em"><?= __('credit_score') ?></div>
            <div class="credit-score-value"><?= $creditScore ?: '—' ?></div>
            <div class="credit-grade" style="color:<?= $creditScore >= 650 ? 'var(--green)' : ($creditScore >= 450 ? 'var(--yellow)' : 'var(--red)') ?>">
                <?= __('grade') ?>: <?= $creditGrade ?>
            </div>
            <div class="credit-bars">
                <?php for ($i = 0; $i < 10; $i++): ?>
                    <div class="credit-bar <?= $i < ($creditScore / 100) ? 'filled' : '' ?>"></div>
                <?php endfor; ?>
            </div>
            <div style="display:flex;justify-content:space-around;margin-top:1rem;font-size:0.8rem">
                <div><div style="opacity:0.6"><?= __('avg_health') ?></div><div style="font-weight:700;font-size:1.1rem"><?= $healthAvg ?>%</div></div>
                <div><div style="opacity:0.6"><?= __('avg_risk') ?></div><div style="font-weight:700;font-size:1.1rem"><?= $riskAvg ?>%</div></div>
                <div><div style="opacity:0.6"><?= __('score_range') ?></div><div style="font-weight:700;font-size:1.1rem">300–900</div></div>
            </div>
        </div>
        <p style="font-size:0.78rem;color:var(--text-3);text-align:center;margin-top:0.75rem">
            <?= __('credit_note') ?>
        </p>
    </div>

    <!-- How it works -->
    <div class="panel animate-in">
        <div class="panel-header">
            <div class="panel-title">💡 <?= __('how_lending_works') ?></div>
        </div>
        <div style="padding:0.5rem 0">
            <div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:1rem">
                <div style="font-size:1.5rem">1️⃣</div>
                <div><strong style="font-size:0.9rem"><?= __('lending_step1') ?></strong><br><span style="font-size:0.82rem;color:var(--text-2)"><?= __('lending_step1_d') ?></span></div>
            </div>
            <div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:1rem">
                <div style="font-size:1.5rem">2️⃣</div>
                <div><strong style="font-size:0.9rem"><?= __('lending_step2') ?></strong><br><span style="font-size:0.82rem;color:var(--text-2)"><?= __('lending_step2_d') ?></span></div>
            </div>
            <div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:1rem">
                <div style="font-size:1.5rem">3️⃣</div>
                <div><strong style="font-size:0.9rem"><?= __('lending_step3') ?></strong><br><span style="font-size:0.82rem;color:var(--text-2)"><?= __('lending_step3_d') ?></span></div>
            </div>
            <div style="display:flex;gap:12px;align-items:flex-start">
                <div style="font-size:1.5rem">4️⃣</div>
                <div><strong style="font-size:0.9rem"><?= __('lending_step4') ?></strong><br><span style="font-size:0.82rem;color:var(--text-2)"><?= __('lending_step4_d') ?></span></div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ Lender Cards ═══ -->
<div style="margin-bottom:1rem">
    <h3 style="font-size:1.05rem;font-weight:700;margin-bottom:0.5rem">🏦 <?= __('available_lenders') ?></h3>
    <div class="filter-bar">
        <button class="filter-chip active" onclick="filterLenders('all',this)"><?= __('all') ?></button>
        <button class="filter-chip" onclick="filterLenders('Public Bank',this)">🏛️ <?= __('public_banks') ?></button>
        <button class="filter-chip" onclick="filterLenders('Private Bank',this)">🏦 <?= __('private_banks') ?></button>
        <button class="filter-chip" onclick="filterLenders('Govt',this)">📜 <?= __('govt_schemes') ?></button>
        <button class="filter-chip" onclick="filterLenders('NBFC',this)">💼 <?= __('nbfcs') ?></button>
    </div>
</div>

<div class="loan-grid" id="loanGrid">
    <?php foreach ($lenders as $l):
        $stars = str_repeat('⭐', round($l['rating']));
    ?>
    <div class="loan-card animate-in" data-type="<?= htmlspecialchars($l['type']) ?>">
        <div class="loan-top">
            <div class="loan-logo"><?= $l['logo_emoji'] ?></div>
            <div>
                <div class="loan-name"><?= htmlspecialchars($l['lender_name']) ?></div>
                <div class="loan-type"><?= htmlspecialchars($l['type']) ?></div>
            </div>
        </div>
        <div class="loan-stats">
            <div class="loan-stat">
                <div class="loan-stat-val"><?= $l['interest_rate'] ?>%</div>
                <div class="loan-stat-label"><?= __('interest') ?></div>
            </div>
            <div class="loan-stat">
                <div class="loan-stat-val">₹<?= number_format($l['max_amount'] / 100000, 1) ?>L</div>
                <div class="loan-stat-label"><?= __('max_amount') ?></div>
            </div>
            <div class="loan-stat">
                <div class="loan-stat-val"><?= $l['tenure_months'] ?>mo</div>
                <div class="loan-stat-label"><?= __('tenure') ?></div>
            </div>
        </div>
        <div class="loan-desc"><?= htmlspecialchars($l['description']) ?></div>
        <div class="loan-reqs">📋 <strong><?= __('requirements') ?>:</strong> <?= htmlspecialchars($l['requirements']) ?></div>
        <div class="loan-rating" style="margin-top:0.5rem"><?= $stars ?> <?= $l['rating'] ?></div>
        <div style="margin-top:0.75rem;display:flex;gap:0.5rem">
            <button class="btn btn-primary btn-sm" style="flex:1" onclick="toast('✓ Demo')"><?= __('apply_now') ?></button>
            <button class="btn btn-outline btn-sm" onclick="toast('✓ Demo')">📄 <?= __('details') ?></button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($lenders)): ?>
<div class="empty-state"><div class="es-icon">🏦</div><?= __('no_lenders') ?></div>
<?php endif; ?>

<?php
$extraScripts = <<<'JS'
<script>
function filterLenders(type, btn) {
    $$('.filter-chip').forEach(c => c.classList.remove('active'));
    if (btn) btn.classList.add('active');
    $$('.loan-card').forEach(card => {
        if (type === 'all') { card.style.display = ''; return; }
        card.style.display = card.dataset.type.includes(type) ? '' : 'none';
    });
}
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>
