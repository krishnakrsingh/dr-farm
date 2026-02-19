<?php
/**
 * DrFarm — Database Connection Helper
 * Returns a PDO instance using config.php credentials.
 */

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $cfg = require __DIR__ . '/config.php';
    $d   = $cfg['db'];

    $dsn = "mysql:host={$d['host']};port={$d['port']};dbname={$d['database']};charset={$d['charset']}";
    $pdo = new PDO($dsn, $d['username'], $d['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}

