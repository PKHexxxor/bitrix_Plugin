<?php
// Bitrix24 App Handler für bedingte Felder
header('Content-Type: application/json');

// CORS-Header für Anfragen aus Bitrix24
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Bei OPTIONS-Anfragen sofort beenden (CORS-Preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Bitrix24 Installation
if (isset($_REQUEST['PLACEMENT']) && $_REQUEST['PLACEMENT'] === 'DEFAULT') {
    // App wurde installiert
    echo json_encode([
        'status' => 'success',
        'script' => 'https://raw.githubusercontent.com/yourusername/bitrix24_conditional_fields/main/script.js'
    ]);
    exit;
}

// Bitrix24 Anwendungslogik
$authData = $_REQUEST['AUTH'] ?? [];
$placement = $_REQUEST['PLACEMENT'] ?? '';

// Rückgabe der Konfiguration für die App
echo json_encode([
    'status' => 'success'
]);
