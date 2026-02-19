    </main><!-- /page-content -->

    <footer class="main-footer">
        <?= __('footer_text') ?>
    </footer>
</div><!-- /main-wrap -->

<!-- Toast -->
<div class="toast" id="toast"></div>

<!-- Shared JS -->
<script>
// ── Helpers ──────────────────────────
const $ = s => document.querySelector(s);
const $$ = s => document.querySelectorAll(s);

function toast(msg, type = 'success') {
    const t = $('#toast');
    t.textContent = msg;
    t.className = 'toast show ' + type;
    setTimeout(() => t.classList.remove('show'), 3500);
}

async function api(url, opts = {}) {
    const r = await fetch(url, opts);
    return r.json();
}

// ── Sidebar toggle ──────────────────
const sidebar = $('#sidebar');
const toggle  = $('#sidebarToggle');
toggle.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    document.body.classList.toggle('sidebar-open');
});

// Click outside to close on mobile
document.addEventListener('click', e => {
    if (window.innerWidth < 1024 && sidebar.classList.contains('open') &&
        !sidebar.contains(e.target) && !toggle.contains(e.target)) {
        sidebar.classList.remove('open');
        document.body.classList.remove('sidebar-open');
    }
});

// ── Language Switcher Toggle ────────
const langBtn = document.getElementById('langBtn');
const langDrop = document.getElementById('langDropdown');
if (langBtn && langDrop) {
    langBtn.addEventListener('click', e => {
        e.stopPropagation();
        langDrop.classList.toggle('show');
    });
    document.addEventListener('click', e => {
        if (!langDrop.contains(e.target) && !langBtn.contains(e.target)) {
            langDrop.classList.remove('show');
        }
    });
}

// ── Live clock ──────────────────────
function updateClock() {
    const now = new Date();
    const el = $('#topbarTime');
    if (el) el.textContent = now.toLocaleString('en-IN', {
        day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit'
    });
}
updateClock();
setInterval(updateClock, 30000);

// ── System status check ─────────────
async function checkSystemStatus() {
    try {
        const r = await fetch('get_latest.php');
        const d = await r.json();
        const dot  = $('#sysStatusDot');
        const text = $('#sysStatusText');
        if (d && !d.error) {
            dot.className  = 'status-dot online';
            text.textContent = LANG.system_online;
        } else {
            dot.className  = 'status-dot offline';
            text.textContent = LANG.no_data;
        }
    } catch {
        const dot  = $('#sysStatusDot');
        const text = $('#sysStatusText');
        if (dot) dot.className = 'status-dot offline';
        if (text) text.textContent = LANG.system_offline;
    }
}
checkSystemStatus();
setInterval(checkSystemStatus, 30000);

// ── Alert badge ─────────────────────
async function updateAlertBadge() {
    try {
        const r = await fetch('trigger_call.php');
        const state = (await r.text()).trim();
        const badge = $('#topbarAlertBadge');
        const sBadge = $('#sidebarAlertBadge');
        if (state === '1') {
            if (badge) badge.classList.add('alert-active');
            if (sBadge) { sBadge.style.display = 'inline-flex'; sBadge.textContent = '!'; }
        } else {
            if (badge) badge.classList.remove('alert-active');
            if (sBadge) sBadge.style.display = 'none';
        }
    } catch {}
}
updateAlertBadge();
setInterval(updateAlertBadge, 15000);
</script>

<?php if (isset($extraScripts)) echo $extraScripts; ?>

</body>
</html>
