<?php
/**
 * DrFarm — Shared Header with Sidebar Navigation + Multi-Language
 * Include this at the top of every page after setting $pageTitle and $currentPage.
 */
require_once __DIR__ . '/lang.php';

if (!isset($pageTitle))  $pageTitle  = __('nav_dashboard');
if (!isset($currentPage)) $currentPage = 'dashboard';

$langs = availableLanguages();
$curLang = currentLang();
?>
<!DOCTYPE html>
<html lang="<?= $curLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — <?= __('brand_name') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <?php if (isset($extraHead)) echo $extraHead; ?>
    <style>
    /* Language Switcher Styles */
    .lang-switcher{position:relative;display:inline-flex;align-items:center;}
    .lang-btn{display:flex;align-items:center;gap:6px;padding:5px 12px;border-radius:8px;border:1px solid var(--border);background:var(--surface);color:var(--text-1);cursor:pointer;font-size:0.82rem;font-weight:600;transition:all 0.2s;}
    .lang-btn:hover{background:var(--bg);border-color:var(--accent);}
    .lang-btn .lang-flag{font-size:1rem;}
    .lang-btn .lang-arrow{font-size:0.65rem;opacity:0.5;margin-left:2px;}
    .lang-dropdown{position:absolute;top:calc(100% + 6px);right:0;min-width:170px;background:var(--card);border:1px solid var(--border);border-radius:10px;box-shadow:0 8px 32px rgba(0,0,0,0.12);z-index:1000;padding:6px;display:none;animation:langFadeIn 0.15s ease;}
    .lang-dropdown.show{display:block;}
    .lang-option{display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:6px;cursor:pointer;font-size:0.82rem;text-decoration:none;color:var(--text-1);transition:background 0.15s;}
    .lang-option:hover{background:var(--surface);}
    .lang-option.active{background:var(--accent);color:#fff;font-weight:600;}
    .lang-option .lo-flag{font-size:1rem;}
    .lang-option .lo-native{font-weight:600;}
    .lang-option .lo-name{opacity:0.6;font-size:0.75rem;margin-left:auto;}
    @keyframes langFadeIn{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:translateY(0)}}
    </style>
</head>
<body>

<!-- ═══ Sidebar ═══ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <span class="brand-icon">🌿</span>
        <div>
            <div class="brand-name"><?= __('brand_name') ?></div>
            <div class="brand-sub"><?= __('brand_sub') ?></div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-group-label"><?= __('nav_main') ?></div>
        <a href="index.php" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
            <span class="nav-icon">📊</span> <?= __('nav_dashboard') ?>
        </a>
        <a href="sensors.php" class="nav-item <?= $currentPage === 'sensors' ? 'active' : '' ?>">
            <span class="nav-icon">📡</span> <?= __('nav_sensors') ?>
        </a>
        <a href="detect.php" class="nav-item <?= $currentPage === 'detect' ? 'active' : '' ?>">
            <span class="nav-icon">🤖</span> <?= __('nav_ai_detection') ?>
        </a>
        <a href="plan.php" class="nav-item <?= $currentPage === 'plan' ? 'active' : '' ?>">
            <span class="nav-icon">📋</span> <?= __('nav_action_plan') ?>
        </a>

        <div class="nav-group-label"><?= __('nav_alerts_label') ?></div>
        <a href="alerts.php" class="nav-item <?= $currentPage === 'alerts' ? 'active' : '' ?>">
            <span class="nav-icon">🚨</span> <?= __('nav_alerts') ?>
            <span class="nav-badge" id="sidebarAlertBadge" style="display:none">0</span>
        </a>

        <div class="nav-group-label"><?= __('nav_services') ?></div>
        <a href="marketplace.php" class="nav-item <?= $currentPage === 'marketplace' ? 'active' : '' ?>">
            <span class="nav-icon">🛒</span> <?= __('nav_marketplace') ?>
        </a>
        <a href="loans.php" class="nav-item <?= $currentPage === 'loans' ? 'active' : '' ?>">
            <span class="nav-icon">🏦</span> <?= __('nav_loans') ?>
        </a>
        <a href="certificate.php" class="nav-item <?= $currentPage === 'certificate' ? 'active' : '' ?>">
            <span class="nav-icon">📜</span> <?= __('nav_certificate') ?>
        </a>

        <div class="nav-group-label"><?= __('nav_intelligence') ?></div>
        <a href="chatbot.php" class="nav-item <?= $currentPage === 'chatbot' ? 'active' : '' ?>">
            <span class="nav-icon">💬</span> <?= __('nav_chatbot') ?>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-status">
            <span class="status-dot" id="sysStatusDot"></span>
            <span id="sysStatusText"><?= __('connecting') ?></span>
        </div>
    </div>
</aside>

<!-- ═══ Main Wrapper ═══ -->
<div class="main-wrap">
    <!-- Top Bar -->
    <header class="topbar">
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>
        <h1 class="topbar-title"><?= htmlspecialchars($pageTitle) ?></h1>
        <div class="topbar-right">
            <!-- Language Switcher -->
            <div class="lang-switcher">
                <button class="lang-btn" id="langBtn">
                    <span class="lang-flag"><?= $langs[$curLang]['flag'] ?></span>
                    <span><?= $langs[$curLang]['native'] ?></span>
                    <span class="lang-arrow">▼</span>
                </button>
                <div class="lang-dropdown" id="langDropdown">
                    <?php foreach ($langs as $code => $meta): ?>
                    <a href="?lang=<?= $code ?>" class="lang-option <?= $code === $curLang ? 'active' : '' ?>">
                        <span class="lo-flag"><?= $meta['flag'] ?></span>
                        <span class="lo-native"><?= $meta['native'] ?></span>
                        <span class="lo-name"><?= $meta['name'] ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <span class="topbar-time" id="topbarTime"></span>
            <span class="topbar-badge" id="topbarAlertBadge" title="Active alerts">🔔</span>
        </div>
    </header>

    <!-- Page Content -->
    <main class="page-content">

    <!-- Pass translation strings to JS for dynamic content -->
    <script>
    const LANG = <?= json_encode([
        'system_online'     => __('system_online'),
        'system_offline'    => __('system_offline'),
        'no_data'           => __('no_data'),
        'connecting'        => __('connecting'),
        'healthy_condition' => __('healthy_condition'),
        'needs_attention'   => __('needs_attention'),
        'critical'          => __('critical'),
        'low_risk'          => __('low_risk'),
        'moderate_risk'     => __('moderate_risk'),
        'high_risk'         => __('high_risk'),
        'no_active_alerts'  => __('no_active_alerts'),
        'alert_triggered'   => __('alert_triggered'),
        'active'            => __('active'),
        'clear'             => __('clear'),
        'low'               => __('low'),
        'moderate'          => __('moderate'),
        'live'              => __('live'),
        'waiting'           => __('waiting'),
        'detected'          => __('detected'),
        'dry'               => __('dry'),
        'online'            => __('online'),
        'yes'               => __('yes'),
        'no'                => __('no'),
        'no_nodes'          => __('no_nodes'),
        'all_clear'         => __('all_clear'),
        'loading'           => __('loading'),
        'temperature'       => __('temperature'),
        'humidity'          => __('humidity'),
        'health'            => __('health'),
        'risk'              => __('risk'),
        'nodes'             => __('nodes'),
        'no_alerts_yet'     => __('no_alerts_yet'),
        'no_detections'     => __('no_detections'),
        'analyzing'         => __('analyzing'),
        'analyze_btn'       => __('analyze_btn'),
        'confidence'        => __('confidence'),
        'severity'          => __('severity'),
        'day'               => __('day'),
        'no_plan_yet'       => __('no_plan_yet'),
        'generate_plan'     => __('generate_plan'),
        'no_disease'        => __('no_disease'),
        'sim900a_will_call' => __('sim900a_will_call'),
        'idle'              => __('idle'),
        'no_active_trigger' => __('no_active_trigger'),
        'chat_cleared'      => __('chat_cleared'),
        'error'             => __('error'),
        'success'           => __('success'),
        'env_risk'          => __('env_risk'),
        'disease_risk'      => __('disease_risk'),
        'node'              => __('node'),
        'rain'              => __('rain'),
        'temp_humidity_chart'=> __('temp_humidity_chart'),
        'risk_history'      => __('risk_history'),
        'in_stock'          => __('in_stock'),
        'out_of_stock'      => __('out_of_stock'),
    ], JSON_UNESCAPED_UNICODE) ?>;
    </script>
