<?php
/**
 * Plugin-Manifest für vereinfachtes Conditional Fields Plugin
 * 
 * Dieses Plugin ermöglicht bedingte Felder und zweispaltiges Layout in Bitrix24 Smart-Prozessen
 */

$arModuleVersion = array(
    "VERSION" => "1.0.0",
    "VERSION_DATE" => "2025-04-30 12:00:00"
);

$MESS["CONDITIONAL_FIELDS_MODULE_NAME"] = "Bedingte Felder für Smart-Prozesse";
$MESS["CONDITIONAL_FIELDS_MODULE_DESCRIPTION"] = "Erlaubt bedingte Felder und zweispaltiges Layout in Smart-Prozessen";
$MESS["CONDITIONAL_FIELDS_PARTNER_NAME"] = "Ihr Firmenname";
$MESS["CONDITIONAL_FIELDS_PARTNER_URI"] = "https://ihre-website.de";
$MESS["CONDITIONAL_FIELDS_INSTALL_TITLE"] = "Installation des Moduls Bedingte Felder";
$MESS["CONDITIONAL_FIELDS_UNINSTALL_TITLE"] = "Deinstallation des Moduls Bedingte Felder";

/**
 * Installationsklasse
 */
class conditional_fields extends CModule
{
    var $MODULE_ID = "conditional_fields";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $PARTNER_NAME;
    var $PARTNER_URI;
    
    public function __construct()
    {
        global $arModuleVersion;
        
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        
        $this->MODULE_NAME = GetMessage("CONDITIONAL_FIELDS_MODULE_NAME");
        $this->MODULE_DESCRIPTION = GetMessage("CONDITIONAL_FIELDS_MODULE_DESCRIPTION");
        $this->PARTNER_NAME = GetMessage("CONDITIONAL_FIELDS_PARTNER_NAME");
        $this->PARTNER_URI = GetMessage("CONDITIONAL_FIELDS_PARTNER_URI");
    }
    
    /**
     * Modul installieren
     */
    public function DoInstall()
    {
        global $APPLICATION;
        
        if (!IsModuleInstalled($this->MODULE_ID)) {
            $this->InstallFiles();
            $this->InstallDB();
            $this->InstallEvents();
            
            RegisterModule($this->MODULE_ID);
            
            $APPLICATION->IncludeAdminFile(
                GetMessage("CONDITIONAL_FIELDS_INSTALL_TITLE"), 
                $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/step.php"
            );
        }
    }
    
    /**
     * Modul deinstallieren
     */
    public function DoUninstall()
    {
        global $APPLICATION;
        
        if (IsModuleInstalled($this->MODULE_ID)) {
            $this->UnInstallEvents();
            $this->UnInstallDB();
            $this->UnInstallFiles();
            
            UnRegisterModule($this->MODULE_ID);
            
            $APPLICATION->IncludeAdminFile(
                GetMessage("CONDITIONAL_FIELDS_UNINSTALL_TITLE"), 
                $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/unstep.php"
            );
        }
    }
    
    /**
     * Dateien installieren
     */
    public function InstallFiles()
    {
        CopyDirFiles(
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/js",
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/js",
            true, true
        );
        
        CopyDirFiles(
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/css",
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/css",
            true, true
        );
        
        return true;
    }
    
    /**
     * Dateien deinstallieren
     */
    public function UnInstallFiles()
    {
        DeleteDirFilesEx("/bitrix/js/conditional_fields");
        DeleteDirFilesEx("/bitrix/css/conditional_fields");
        
        return true;
    }
    
    /**
     * Datenbank installieren
     */
    public function InstallDB()
    {
        global $DB;
        
        // Einfache Tabelle für Konfigurationen
        $DB->Query("
            CREATE TABLE IF NOT EXISTS conditional_fields_config (
                ID INT AUTO_INCREMENT PRIMARY KEY,
                ENTITY_TYPE_ID INT NOT NULL,
                CONFIG_TYPE VARCHAR(50) NOT NULL,
                CONFIG MEDIUMTEXT NOT NULL,
                CREATED_DATE DATETIME NOT NULL,
                MODIFIED_DATE DATETIME NOT NULL,
                UNIQUE KEY entity_config (ENTITY_TYPE_ID, CONFIG_TYPE)
            )
        ");
        
        return true;
    }
    
    /**
     * Datenbank deinstallieren
     */
    public function UnInstallDB()
    {
        global $DB;
        
        // Tabelle löschen
        $DB->Query("DROP TABLE IF EXISTS conditional_fields_config");
        
        // Optionen löschen
        COption::RemoveOption($this->MODULE_ID);
        
        return true;
    }
    
    /**
     * Events installieren
     */
    public function InstallEvents()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        
        // Events für UI
        $eventManager->registerEventHandler(
            'main',
            'OnEpilog',
            $this->MODULE_ID,
            '\ConditionalFields\Handlers',
            'onEpilog'
        );
        
        // Events für Smart-Prozesse
        $eventManager->registerEventHandler(
            'crm',
            'OnAfterCrmSmartEntityFieldsLoad',
            $this->MODULE_ID,
            '\ConditionalFields\Handlers',
            'onAfterFieldsLoad'
        );
        
        return true;
    }
    
    /**
     * Events deinstallieren
     */
    public function UnInstallEvents()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        
        // UI Events entfernen
        $eventManager->unRegisterEventHandler(
            'main',
            'OnEpilog',
            $this->MODULE_ID,
            '\ConditionalFields\Handlers',
            'onEpilog'
        );
        
        // Smart-Prozess Events entfernen
        $eventManager->unRegisterEventHandler(
            'crm',
            'OnAfterCrmSmartEntityFieldsLoad',
            $this->MODULE_ID,
            '\ConditionalFields\Handlers',
            'onAfterFieldsLoad'
        );
        
        return true;
    }
}