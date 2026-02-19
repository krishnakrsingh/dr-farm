<?php
// DrFarm — Central Configuration

return [
    // ── MySQL Database ────────────────────
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'database' => 'drfarm',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],

    // ── OpenAI API ────────────────────────
    'openai' => [
        'api_key' => 'sk-proj-x',   // sk-...
        'model'   => 'gpt-4o',                      // chat model
        'vision_model' => 'gpt-4o',                 // vision model for disease detection
    ],

    // ── WhatsApp Alert (waclient.com) ─────
    'whatsapp' => [
        'api_url'      => 'https://waclient.com/api/send',
        'access_token' => 'x',
        'instance_id'  => 'x',
        'default_to' => 'x',  // default phone with country code
    ],

    // ── Risk Thresholds ───────────────────
    'risk' => [
        'alert_threshold' => 65,
        'humidity_high'   => 75,
        'temp_fungal_low' => 20,
        'temp_fungal_high'=> 30,
        'mq7_anomaly'     => 400,
        'mq3_anomaly'     => 350,
    ],
];
