<?php
require_once __DIR__ . '/includes/lang.php';
$pageTitle   = __('alerts_notifications');
$currentPage = 'alerts';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ═══ Alert Controls ═══ -->
<div class="dash-grid" style="margin-bottom:1.25rem">
    <!-- Send WhatsApp Alert -->
    <div class="panel animate-in">
        <div class="panel-header">
            <div class="panel-title">📱 <?= __('send_whatsapp') ?></div>
        </div>
        <form id="waForm">
            <div class="form-group">
                <label class="form-label"><?= __('phone_number') ?></label>
                <input type="text" class="form-input" id="waPhone" placeholder="919999999999" value="">
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('message') ?></label>
                <textarea class="form-textarea" id="waMsg" rows="3" placeholder="🚨 DrFarm Alert: High risk detected on your farm. Check dashboard now."></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="background:linear-gradient(135deg,#25d366,#128c7e)">
                <?= __('send_wa_btn') ?>
            </button>
        </form>
    </div>

    <!-- Trigger GSM Call -->
    <div class="panel animate-in">
        <div class="panel-header">
            <div class="panel-title">📞 <?= __('gsm_call_alert') ?></div>
            <span class="panel-badge info">SIM900A</span>
        </div>
        <form id="gsmForm">
            <div class="form-group">
                <label class="form-label"><?= __('phone_number') ?></label>
                <input type="text" class="form-input" id="gsmPhone" placeholder="9999999999">
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('message') ?></label>
                <textarea class="form-textarea" id="gsmMsg" rows="3" placeholder="High risk alert — immediate attention required"></textarea>
            </div>
            <button type="submit" class="btn btn-danger">
                <?= __('trigger_gsm_btn') ?>
            </button>
        </form>
        <div style="margin-top:0.75rem;font-size:0.78rem;color:var(--text-3)">
            ℹ️ <?= __('gsm_info') ?>
        </div>
    </div>
</div>

<!-- ═══ Quick Alert Buttons ═══ -->
<div class="panel animate-in" style="margin-bottom:1.25rem">
    <div class="panel-header">
        <div class="panel-title">⚡ <?= __('quick_alerts') ?></div>
    </div>
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap">
        <button class="btn btn-primary btn-sm" onclick="quickAlert('whatsapp','🚨 HIGH RISK: Farm health critical. Immediate action required!')">📱 <?= __('wa_high_risk') ?></button>
        <button class="btn btn-primary btn-sm" onclick="quickAlert('whatsapp','🌧️ Rain detected + high humidity. Fungal disease risk elevated.')">📱 <?= __('wa_rain_alert') ?></button>
        <button class="btn btn-primary btn-sm" onclick="quickAlert('whatsapp','🔬 Disease detected on crops. Check DrFarm dashboard for details.')">📱 <?= __('wa_disease_alert') ?></button>
        <button class="btn btn-danger btn-sm" onclick="quickGSM('Emergency: High risk on farm, call triggered by DrFarm system')">📞 <?= __('gsm_emergency') ?></button>
        <button class="btn btn-outline btn-sm" onclick="resetTrigger()">🔄 <?= __('reset_trigger') ?></button>
    </div>
</div>

<!-- ═══ Alert Trigger Status ═══ -->
<div class="hero-grid" style="margin-bottom:1.25rem">
    <div class="hero-card blue animate-in">
        <div class="card-icon">🔔</div>
        <div class="card-label"><?= __('trigger_status') ?></div>
        <div class="card-value" id="triggerVal">—</div>
        <div class="card-sub" id="triggerSub"><?= __('checking') ?></div>
    </div>
    <div class="hero-card purple animate-in">
        <div class="card-icon">📊</div>
        <div class="card-label"><?= __('total_alerts') ?></div>
        <div class="card-value" id="totalAlerts">—</div>
        <div class="card-sub"><?= __('all_time') ?></div>
    </div>
</div>

<!-- ═══ Alert History ═══ -->
<div class="panel animate-in">
    <div class="panel-header">
        <div class="panel-title">🚨 <?= __('alert_history') ?></div>
        <div class="filter-bar" style="margin-bottom:0">
            <button class="filter-chip active" data-filter="all"><?= __('all') ?></button>
            <button class="filter-chip" data-filter="whatsapp">📱 <?= __('whatsapp') ?></button>
            <button class="filter-chip" data-filter="gsm">📞 <?= __('gsm') ?></button>
            <button class="filter-chip" data-filter="system">🖥 <?= __('system') ?></button>
        </div>
    </div>
    <ul class="alert-list" id="alertList" style="max-height:500px">
        <li class="empty-state"><div class="es-icon">✅</div><?= __('no_alerts_yet') ?></li>
    </ul>
</div>

<?php
$extraScripts = <<<'JS'
<script>
// ── WhatsApp form ─────
$('#waForm').addEventListener('submit', async e => {
    e.preventDefault();
    const phone = $('#waPhone').value.trim();
    const msg   = $('#waMsg').value.trim();
    if (!msg) { toast(LANG.error, 'error'); return; }

    const r = await fetch('api/whatsapp.php', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ phone, message: msg })
    });
    const d = await r.json();
    toast(d.sent ? 'WhatsApp ✓' : '✓');
    loadAlerts();
});

// ── GSM form ──────────
$('#gsmForm').addEventListener('submit', async e => {
    e.preventDefault();
    const phone = $('#gsmPhone').value.trim();
    const msg   = $('#gsmMsg').value.trim() || 'GSM alert from DrFarm';

    const r = await fetch('api/gsm_alert.php', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ phone, message: msg })
    });
    await r.json();
    toast('GSM ✓');
    loadAlerts(); checkTrigger();
});

// ── Quick alerts ──────
async function quickAlert(channel, msg) {
    await fetch('api/whatsapp.php', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ message: msg })
    });
    toast('WhatsApp ✓');
    loadAlerts();
}

async function quickGSM(msg) {
    await fetch('api/gsm_alert.php', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ message: msg })
    });
    toast('GSM ✓');
    loadAlerts(); checkTrigger();
}

async function resetTrigger() {
    await fetch('trigger_call.php?set=0');
    toast('✓');
    checkTrigger();
}

// ── Check trigger state ─
async function checkTrigger() {
    try {
        const r = await fetch('trigger_call.php');
        const s = (await r.text()).trim();
        const v = $('#triggerVal');
        if (s === '1') {
            v.textContent = '🔴 ' + LANG.active;
            v.style.color = 'var(--red)';
            $('#triggerSub').textContent = LANG.sim900a_will_call;
        } else {
            v.textContent = '🟢 ' + LANG.idle;
            v.style.color = 'var(--green)';
            $('#triggerSub').textContent = LANG.no_active_trigger;
        }
    } catch (e) { console.error(e); }
}

// ── Load alerts ─────
let allAlerts = [];
async function loadAlerts() {
    try {
        const d = await api('api_history.php?type=alerts&limit=50');
        allAlerts = Array.isArray(d) ? d : [];
        $('#totalAlerts').textContent = allAlerts.length;
        renderAlerts('all');
    } catch (e) { console.error(e); }
}

function renderAlerts(filter) {
    const l = $('#alertList');
    let data = allAlerts;
    if (filter !== 'all') {
        data = allAlerts.filter(a => (a.channel || 'system') === filter);
    }
    if (!data.length) { l.innerHTML = '<li class="empty-state"><div class="es-icon">✅</div>'+LANG.no_alerts_yet+'</li>'; return; }
    l.innerHTML = data.map(a => {
        const ch = a.channel || 'system';
        const cls = ch === 'whatsapp' ? 'wa' : ch === 'gsm' ? 'gsm' : 'sys';
        const icon = ch === 'whatsapp' ? '📱' : ch === 'gsm' ? '📞' : '🖥';
        return `<li class="alert-item">
            <span class="alert-dot ${cls}"></span>
            <div style="flex:1">
                <div class="alert-msg">${a.message || 'Alert'}</div>
                <div class="alert-time">
                    ${a.created_at || ''}
                    ${a.phone ? ' • ' + a.phone : ''}
                    <span class="alert-channel ${cls}">${icon} ${ch.toUpperCase()}</span>
                </div>
            </div>
        </li>`;
    }).join('');
}

// ── Filter chips ──────
$$('.filter-chip').forEach(chip => {
    chip.addEventListener('click', () => {
        $$('.filter-chip').forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        renderAlerts(chip.dataset.filter);
    });
});

document.addEventListener('DOMContentLoaded', () => {
    checkTrigger();
    loadAlerts();
    setInterval(checkTrigger, 10000);
});
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>
