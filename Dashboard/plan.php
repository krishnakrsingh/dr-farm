<?php
require_once __DIR__ . '/includes/lang.php';
$pageTitle   = __('seven_day_plan');
$currentPage = 'plan';
require_once __DIR__ . '/includes/header.php';
?>

<div class="panel animate-in" style="margin-bottom:1.5rem">
    <div class="plan-header">
        <div class="panel-title" style="margin-bottom:0">📋 <?= __('semi_dynamic') ?></div>
        <div class="plan-meta">
            <span class="plan-tag disease" id="planDisease"><?= __('no_disease') ?></span>
            <span class="plan-tag risk" id="planRisk"><?= __('low_risk') ?></span>
            <button class="btn btn-primary btn-sm" onclick="generatePlan()"><?= __('generate_new') ?></button>
        </div>
    </div>
    <div id="planTimeline">
        <div class="empty-state">
            <div class="es-icon">📋</div>
            <?= __('no_plan_yet') ?><br>
            <button class="btn btn-outline btn-sm" style="margin-top:0.75rem" onclick="generatePlan()"><?= __('generate_plan') ?></button>
        </div>
    </div>
</div>

<!-- ═══ How It Works ═══ -->
<div class="panel animate-in">
    <div class="panel-header">
        <div class="panel-title">📚 <?= __('how_it_works') ?></div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
        <div style="text-align:center;padding:1rem">
            <div style="font-size:2rem;margin-bottom:0.5rem">🔬</div>
            <div style="font-weight:700;font-size:0.9rem;margin-bottom:0.25rem"><?= __('step1_title') ?></div>
            <div style="font-size:0.8rem;color:var(--text-2)"><?= __('step1_desc') ?></div>
        </div>
        <div style="text-align:center;padding:1rem">
            <div style="font-size:2rem;margin-bottom:0.5rem">📊</div>
            <div style="font-weight:700;font-size:0.9rem;margin-bottom:0.25rem"><?= __('step2_title') ?></div>
            <div style="font-size:0.8rem;color:var(--text-2)"><?= __('step2_desc') ?></div>
        </div>
        <div style="text-align:center;padding:1rem">
            <div style="font-size:2rem;margin-bottom:0.5rem">📋</div>
            <div style="font-weight:700;font-size:0.9rem;margin-bottom:0.25rem"><?= __('step3_title') ?></div>
            <div style="font-size:0.8rem;color:var(--text-2)"><?= __('step3_desc') ?></div>
        </div>
        <div style="text-align:center;padding:1rem">
            <div style="font-size:2rem;margin-bottom:0.5rem">🚜</div>
            <div style="font-weight:700;font-size:0.9rem;margin-bottom:0.25rem"><?= __('step4_title') ?></div>
            <div style="font-size:0.8rem;color:var(--text-2)"><?= __('step4_desc') ?></div>
        </div>
    </div>
</div>

<?php
$extraScripts = <<<'JS'
<script>
async function loadPlan() {
    try {
        const d = await api('action_plan.php');
        if (d.error || !d.days || !d.days.length) return;
        $('#planDisease').textContent = d.disease || 'Unknown';
        $('#planRisk').textContent = (d.risk_level || 'Low') + ' Risk';
        const tl = $('#planTimeline');
        tl.innerHTML = '<div class="timeline">' + d.days.map(day => `
            <div class="timeline-item animate-in">
                <div class="timeline-dot">${day.day}</div>
                <div class="timeline-content">
                    <div class="timeline-day">${LANG.day} ${day.day}</div>
                    <div class="timeline-title">${day.icon} ${day.title}</div>
                    <div class="timeline-desc">${day.description}</div>
                </div>
            </div>
        `).join('') + '</div>';
    } catch (e) { console.error(e); }
}

async function generatePlan() {
    toast('⏳ ...');
    const d = await api('action_plan.php?generate=1');
    if (d.error) { toast(d.error, 'error'); return; }
    toast('✅ ' + (d.disease || ''));
    loadPlan();
}

document.addEventListener('DOMContentLoaded', loadPlan);
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>
