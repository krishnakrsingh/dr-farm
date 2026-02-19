<?php
/**
 * DrFarm — Multi-Language Helper
 *
 * Supported: en (English), hi (Hindi), mr (Marathi), pa (Punjabi), te (Telugu)
 *
 * Usage:
 *   <?= __('dashboard') ?>
 *   <?= __('farm_health_score') ?>
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Language switch via GET param
if (isset($_GET['lang'])) {
    $allowed = ['en', 'hi', 'mr', 'pa', 'te'];
    $req = strtolower(trim($_GET['lang']));
    if (in_array($req, $allowed)) {
        $_SESSION['drfarm_lang'] = $req;
    }
    // Redirect back (remove ?lang= from URL)
    $redir = strtok($_SERVER['REQUEST_URI'], '?');
    $params = $_GET;
    unset($params['lang']);
    if (!empty($params)) $redir .= '?' . http_build_query($params);
    header("Location: $redir");
    exit;
}

// Current language
$GLOBALS['drfarm_lang'] = $_SESSION['drfarm_lang'] ?? 'en';
$GLOBALS['drfarm_translations'] = [];

// Load language file
$langFile = __DIR__ . '/../lang/' . $GLOBALS['drfarm_lang'] . '.php';
if (file_exists($langFile)) {
    $GLOBALS['drfarm_translations'] = require $langFile;
}

// Fallback to English
$enFile = __DIR__ . '/../lang/en.php';
if ($GLOBALS['drfarm_lang'] !== 'en' && file_exists($enFile)) {
    $GLOBALS['drfarm_translations_en'] = require $enFile;
} else {
    $GLOBALS['drfarm_translations_en'] = $GLOBALS['drfarm_translations'];
}

/**
 * Translate a key. Falls back to English, then to the key itself.
 */
function __( string $key ): string {
    return $GLOBALS['drfarm_translations'][$key]
        ?? $GLOBALS['drfarm_translations_en'][$key]
        ?? $key;
}

/**
 * Get current language code.
 */
function currentLang(): string {
    return $GLOBALS['drfarm_lang'];
}

/**
 * Language metadata for the switcher.
 */
function availableLanguages(): array {
    return [
        'en' => ['name' => 'English',  'native' => 'English',  'flag' => '🇬🇧'],
        'hi' => ['name' => 'Hindi',    'native' => 'हिन्दी',    'flag' => '🇮🇳'],
        'mr' => ['name' => 'Marathi',  'native' => 'मराठी',     'flag' => '🇮🇳'],
        'pa' => ['name' => 'Punjabi',  'native' => 'ਪੰਜਾਬੀ',    'flag' => '🇮🇳'],
        'te' => ['name' => 'Telugu',   'native' => 'తెలుగు',    'flag' => '🇮🇳'],
    ];
}

