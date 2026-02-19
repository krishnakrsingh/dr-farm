<?php
/**
 * GET /trigger_call.php          → Returns current alert state (0 or 1)
 * GET /trigger_call.php?set=1    → Activates alert
 * GET /trigger_call.php?set=0    → Resets alert
 */
require_once __DIR__ . '/db.php';

header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $db = getDB();

    // SET mode
    if (isset($_GET['set'])) {
        $val = intval($_GET['set']) ? 1 : 0;
        $db->prepare("UPDATE trigger_state SET state = :s WHERE id = 1")->execute([':s' => $val]);

        // Log alert if activating
        if ($val === 1) {
            $db->exec("INSERT INTO alerts (type, message) VALUES ('risk', 'High risk alert triggered by system')");
        }

        echo $val;
        exit;
    }

    // READ mode
    $row = $db->query("SELECT state FROM trigger_state WHERE id = 1")->fetch();
    echo $row ? $row['state'] : '0';

} catch (Exception $e) {
    http_response_code(500);
    echo '0';
}

