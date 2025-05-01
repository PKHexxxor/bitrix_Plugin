<?php
/**
 * Installationsdatei für die Bitrix24-App "Bedingte Felder"
 * Diese Datei wird beim Installieren der App aufgerufen
 */

// Sicherheitsheader
header('Content-Type: text/html; charset=utf-8');

// CORS-Header für Anfragen aus Bitrix24
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// GitHub RAW-URLs
$baseRawUrl = 'https://raw.githubusercontent.com/PKHexxxor/bitrix_Plugin/main/bitrix24_conditional_fields/';
$scriptUrl = $baseRawUrl . 'script.js';

// Bitrix24 JS SDK einbinden
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bedingte Felder für Bitrix24 - Installation</title>
    <script src="//api.bitrix24.com/api/v1/"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px; }
        h1 { color: #2a3640; font-size: 24px; margin-bottom: 20px; }
        .success { color: green; padding: 10px; background: #e6ffe6; border-radius: 3px; }
        .error { color: red; padding: 10px; background: #ffe6e6; border-radius: 3px; }
        .info { margin: 15px 0; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Bedingte Felder für Bitrix24 - Installation</h1>
        
        <div class="info">
            <p>Diese App ermöglicht ein zweispaltiges Layout und bedingte Felder in Smart-Prozessen von Bitrix24.</p>
            <p>Die Installation wird nun abgeschlossen...</p>
        </div>
        
        <div id="status"></div>
        
        <script>
            // Bitrix24 API initialisieren
            BX24.init(function() {
                // App-Zugangsdaten erfassen
                var authData = BX24.getAuth();
                
                // Status anzeigen
                document.getElementById('status').innerHTML = 
                    '<div class="success">Die Anwendung wurde erfolgreich installiert!</div>' +
                    '<p>Die Anwendung ist jetzt einsatzbereit. Sie können die Smart-Prozesse öffnen, um das zweispaltige Layout zu sehen.</p>';
                
                // Script für bedingte Felder laden
                var script = document.createElement('script');
                script.src = '<?php echo $scriptUrl; ?>';
                document.head.appendChild(script);
                
                // Installation abschließen
                BX24.installFinish();
            });
        </script>
    </div>
</body>
</html>