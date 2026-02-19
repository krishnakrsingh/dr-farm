<?php
require_once __DIR__ . '/includes/lang.php';
$pageTitle   = __('ai_chatbot');
$currentPage = 'chatbot';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/db.php';

// Load recent chat history
$db = getDB();
$history = $db->query("SELECT role, message, created_at FROM chat_messages ORDER BY id DESC LIMIT 30")->fetchAll();
$history = array_reverse($history);
?>

<div class="panel animate-in" style="height:calc(100vh - var(--topbar-h) - 100px);display:flex;flex-direction:column">
    <div class="panel-header" style="flex-shrink:0">
        <div class="panel-title">💬 <?= __('ai_assistant') ?></div>
        <div style="display:flex;gap:0.5rem">
            <span class="panel-badge info"><?= __('gpt4o_powered') ?></span>
            <button class="btn btn-outline btn-sm" onclick="clearChat()"><?= __('clear_chat') ?></button>
        </div>
    </div>

    <!-- Chat Messages -->
    <div class="chat-messages" id="chatMsgs">
        <?php if (empty($history)): ?>
        <div class="chat-msg assistant">
            <div class="chat-avatar">🌿</div>
            <div class="chat-bubble">
                <strong><?= __('chat_welcome') ?></strong><br><br>
                <?= __('chat_intro') ?><br>
                <?= __('chat_help1') ?><br>
                <?= __('chat_help2') ?><br>
                <?= __('chat_help3') ?><br>
                <?= __('chat_help4') ?><br>
                <?= __('chat_help5') ?><br><br>
                <?= __('chat_ask') ?>
            </div>
        </div>
        <?php else: ?>
            <?php foreach ($history as $m): ?>
            <div class="chat-msg <?= $m['role'] ?>">
                <div class="chat-avatar"><?= $m['role'] === 'user' ? '👤' : '🌿' ?></div>
                <div class="chat-bubble"><?= nl2br(htmlspecialchars($m['message'])) ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Quick Suggestions -->
    <div style="display:flex;gap:0.35rem;flex-wrap:wrap;padding:0.5rem 0;flex-shrink:0" id="suggestions">
        <button class="filter-chip" onclick="askQuestion('What diseases are common in rice crops?')"><?= __('chip_rice') ?></button>
        <button class="filter-chip" onclick="askQuestion('How to treat Leaf Blast?')"><?= __('chip_blast') ?></button>
        <button class="filter-chip" onclick="askQuestion('Best practices for monsoon farming')"><?= __('chip_monsoon') ?></button>
        <button class="filter-chip" onclick="askQuestion('Explain my current sensor readings')"><?= __('chip_sensor') ?></button>
        <button class="filter-chip" onclick="askQuestion('How to improve my farm health score?')"><?= __('chip_health') ?></button>
    </div>

    <!-- Input -->
    <div class="chat-input-wrap" style="flex-shrink:0">
        <input type="text" class="form-input" id="chatInput" placeholder="<?= __('chat_placeholder') ?>" autocomplete="off">
        <button class="btn btn-primary" id="sendBtn" onclick="sendMessage()"><?= __('send') ?></button>
    </div>
</div>

<?php
$extraScripts = <<<'JS'
<script>
const chatMsgs  = $('#chatMsgs');
const chatInput  = $('#chatInput');

function scrollBottom() {
    chatMsgs.scrollTop = chatMsgs.scrollHeight;
}
scrollBottom();

function addMsg(role, text) {
    const div = document.createElement('div');
    div.className = 'chat-msg ' + role;
    div.innerHTML = `<div class="chat-avatar">${role==='user'?'👤':'🌿'}</div><div class="chat-bubble">${text.replace(/\n/g,'<br>')}</div>`;
    chatMsgs.appendChild(div);
    scrollBottom();
}

async function sendMessage() {
    const msg = chatInput.value.trim();
    if (!msg) return;

    chatInput.value = '';
    addMsg('user', msg);

    // Typing indicator
    const typing = document.createElement('div');
    typing.className = 'chat-msg assistant';
    typing.id = 'typing';
    typing.innerHTML = '<div class="chat-avatar">🌿</div><div class="chat-bubble"><div class="spinner" style="margin:0;width:18px;height:18px;border-width:2px"></div></div>';
    chatMsgs.appendChild(typing);
    scrollBottom();

    try {
        const r = await fetch('api/chat.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ message: msg })
        });
        const d = await r.json();
        typing.remove();

        if (d.error) { addMsg('assistant', '❌ ' + d.error); return; }
        addMsg('assistant', d.reply);
    } catch (e) {
        typing.remove();
        addMsg('assistant', '❌ ' + LANG.error);
    }
}

function askQuestion(q) {
    chatInput.value = q;
    sendMessage();
}

async function clearChat() {
    chatMsgs.innerHTML = `<div class="chat-msg assistant"><div class="chat-avatar">🌿</div><div class="chat-bubble"><strong>${LANG.chat_cleared}</strong> 🌱</div></div>`;
    toast('✓');
}

// Enter to send
chatInput.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});

// Focus input on load
chatInput.focus();
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>
