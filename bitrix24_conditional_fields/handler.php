<?php
/**
 * Haupthandler für die Bitrix24-App "Bedingte Felder"
 */

// Sicherheitsheader
header('Content-Type: application/json');

// CORS-Header für Anfragen aus Bitrix24
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Bei OPTIONS-Anfragen sofort beenden (CORS-Preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Hauptprogrammlogik
$event = $_REQUEST['event'] ?? '';
$authData = $_REQUEST['auth'] ?? [];
$placement = $_REQUEST['PLACEMENT'] ?? '';

// Verarbeite je nach Event
switch ($event) {
    case 'ONAPPINSTALL':
        // App wurde installiert
        // Weiterleitung zur Installationsseite
        header('Location: install.php');
        exit;
        
    case 'ONAPPUNINSTALL':
        // App wurde deinstalliert
        echo json_encode([
            'status' => 'success',
            'message' => 'App wurde erfolgreich deinstalliert'
        ]);
        exit;
        
    default:
        // Standard-Handler für normale App-Aufrufe
        // Prüfe, ob es ein Placement ist
        if (!empty($placement)) {
            // Platzierungs-Handler
            switch ($placement) {
                case 'DEFAULT':
                    // Standard-Platzierung (App-Öffnung)
                    header('Location: admin.html');
                    exit;
                    
                default:
                    // Andere Platzierungen
                    echo json_encode([
                        'status' => 'success',
                        'script' => 'script.js'
                    ]);
                    exit;
            }
        }
        
        // Standard-Antwort
        echo json_encode([
            'status' => 'success',
            'message' => 'Handler wurde erfolgreich aufgerufen'
        ]);
}
