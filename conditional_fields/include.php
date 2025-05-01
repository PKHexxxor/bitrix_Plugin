<?php
/**
 * Include-Datei für das Modul
 * 
 * Registriert die Autoload-Klassen
 */

// Autoload-Funktion für die Klassen des Moduls
\Bitrix\Main\Loader::registerAutoLoadClasses(
    'conditional_fields',
    array(
        'ConditionalFields\\Handlers' => 'lib/handlers.php',
    )
);