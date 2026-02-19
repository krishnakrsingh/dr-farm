<?php
require_once __DIR__ . '/includes/lang.php';
$pageTitle   = __('ai_disease_detection');
$currentPage = 'detect';
require_once __DIR__ . '/includes/header.php';
?>

<div class="dash-grid">
    <!-- Upload Panel -->
    <div class="panel animate-in">
        <div class="panel-header">
            <div class="panel-title">🤖 <?= __('openai_vision') ?></div>
            <span class="panel-badge info"><?= __('gpt4o_powered') ?></span>
        </div>
        <form id="detectForm" enctype="multipart/form-data">
            <div class="upload-area" id="uploadArea">
                <input type="file" name="leaf_image" id="leafInput" accept="image/*">
                <div class="up-icon">📸</div>
                <div class="up-text"><?= __('upload_leaf') ?></div>
                <div class="up-hint"><?= __('upload_hint') ?></div>
            </div>
            <div id="imgPreview" style="margin-top:1rem;text-align:center;display:none">
                <img id="prevImg" style="max-height:200px;border-radius:var(--radius-sm);border:2px solid var(--border)">
            </div>
            <div style="text-align:center;margin-top:1rem">
                <button type="submit" class="btn btn-primary btn-lg" id="detectBtn" disabled>
                    <?= __('analyze_btn') ?>
                </button>
            </div>
        </form>

        <!-- Result -->
        <div class="disease-result" id="dResult">
            <div class="dr-name" id="drName"></div>
            <div class="dr-row"><span class="dr-label"><?= __('confidence') ?></span><span class="dr-val" id="drConf"></span></div>
            <div class="dr-row"><span class="dr-label"><?= __('severity') ?></span><span class="dr-val" id="drSev"></span></div>
            <div id="drAnalysis" style="margin-top:0.75rem;font-size:0.85rem;color:var(--text-2);line-height:1.55;background:var(--surface);padding:0.75rem;border-radius:var(--radius-sm);border:1px solid var(--border)"></div>
            <div style="display:flex;gap:0.5rem;margin-top:1rem;flex-wrap:wrap;justify-content:center">
                <button class="btn btn-outline btn-sm" onclick="genPlan()"><?= __('generate_plan_btn') ?></button>
                <button class="btn btn-outline btn-sm" onclick="recalcRisk()"><?= __('recalc_risk_btn') ?></button>
                <button class="btn btn-outline btn-sm" onclick="sendWaAlert()"><?= __('send_wa_alert_btn') ?></button>
            </div>
        </div>
    </div>

    <!-- History -->
    <div class="panel animate-in">
        <div class="panel-header">
            <div class="panel-title">🧬 <?= __('detection_history') ?></div>
        </div>
        <ul class="alert-list" id="histList">
            <li class="empty-state"><div class="es-icon">🔬</div><?= __('no_detections') ?></li>
        </ul>
    </div>
</div>

<?php
$extraScripts = <<<'JS'
<script>
const leafInput = $('#leafInput');
const detectForm = $('#detectForm');

leafInput.addEventListener('change', () => {
    if (!leafInput.files[0]) return;
    $('#detectBtn').disabled = false;
    const reader = new FileReader();
    reader.onload = e => { $('#prevImg').src = e.target.result; $('#imgPreview').style.display = 'block'; };
    reader.readAsDataURL(leafInput.files[0]);
});

detectForm.addEventListener('submit', async e => {
    e.preventDefault();
    const btn = $('#detectBtn');
    btn.disabled = true; btn.textContent = LANG.analyzing;

    try {
        const fd = new FormData(detectForm);
        const r = await fetch('api/detect_ai.php', { method: 'POST', body: fd });
        const d = await r.json();
        if (d.error) { toast(d.error, 'error'); btn.disabled = false; btn.textContent = LANG.analyze_btn; return; }

        const sev = {'Critical':'var(--red)','High':'#ea580c','Medium':'var(--yellow)','Low':'var(--green)'}[d.severity] || 'var(--text-2)';
        $('#drName').textContent = d.disease_name;
        $('#drConf').textContent = d.confidence + '%';
        $('#drSev').textContent = d.severity; $('#drSev').style.color = sev;
        $('#drAnalysis').textContent = d.analysis || '';
        $('#dResult').classList.add('active');

        toast(d.disease_name + ' — ' + d.confidence + '%');
        loadHistory();
    } catch (err) { toast(LANG.error, 'error'); }

    btn.disabled = false; btn.textContent = LANG.analyze_btn;
});

async function loadHistory() {
    try {
        const d = await api('api_history.php?type=disease&limit=15');
        const l = $('#histList');
        if (!Array.isArray(d) || !d.length) { l.innerHTML = '<li class="empty-state"><div class="es-icon">🔬</div>'+LANG.no_detections+'</li>'; return; }
        l.innerHTML = d.map(r => {
            const sc = {'Critical':'var(--red)','High':'#ea580c','Medium':'var(--yellow)','Low':'var(--green)'}[r.severity] || 'var(--text-2)';
            return `<li class="alert-item"><span class="alert-dot" style="background:${sc}"></span><div style="flex:1"><div class="alert-msg"><strong>${r.disease_name}</strong> — ${r.confidence}%</div><div class="alert-time">${LANG.severity}: <strong style="color:${sc}">${r.severity}</strong> | ${r.created_at||''}</div>${r.analysis?`<div style="font-size:0.78rem;color:var(--text-3);margin-top:2px">${r.analysis.substring(0,100)}…</div>`:''}</div></li>`;
        }).join('');
    } catch (e) { console.error(e); }
}

async function genPlan() {
    toast('⏳ ...');
    await api('action_plan.php?generate=1');
    toast('✅');
}
async function recalcRisk() {
    toast('⏳ ...');
    await api('risk_engine.php');
    toast('✅');
}
async function sendWaAlert() {
    const name = $('#drName').textContent;
    const conf = $('#drConf').textContent;
    await fetch('api/whatsapp.php', { method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ message: `🚨 DrFarm Alert: ${name} (${conf})` })
    });
    toast('📱 WhatsApp ✓');
}

document.addEventListener('DOMContentLoaded', loadHistory);
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>
