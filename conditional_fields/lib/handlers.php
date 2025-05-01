<?php
/**
 * Event-Handler für Smart-Prozesse
 */
namespace ConditionalFields;

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Json;

/**
 * Hauptklasse für Event-Handling
 */
class Handlers
{
    /**
     * OnEpilog-Handler zum Einbinden von JS/CSS
     */
    public static function onEpilog()
    {
        global $APPLICATION;
        
        // Prüfe, ob wir uns in einem Smart-Prozess befinden
        $curPage = $APPLICATION->GetCurPage();
        if (strpos($curPage, '/crm/type/') === false) {
            return;
        }
        
        // Lade benötigte JS/CSS
        $APPLICATION->AddHeadScript('/bitrix/js/conditional_fields/script.js');
        $APPLICATION->SetAdditionalCSS('/bitrix/css/conditional_fields/style.css');
        
        // Füge Initialisierungscode hinzu
        $entityTypeId = self::getEntityTypeIdFromUrl($curPage);
        if ($entityTypeId > 0) {
            // Hole Konfigurationen aus der DB
            $config = self::getConfig($entityTypeId);
            
            $APPLICATION->AddHeadString('<script>
                BX.ready(function() {
                    if (typeof ConditionalFields !== "undefined") {
                        ConditionalFields.init({
                            entityTypeId: ' . $entityTypeId . ',
                            rules: ' . $config['rules'] . ',
                            layout: ' . $config['layout'] . '
                        });
                    }
                });
            </script>');
        }
    }
    
    /**
     * Handler für AfterFieldsLoad Event
     * 
     * @param \Bitrix\Main\Event $event
     * @return \Bitrix\Main\EventResult|null
     */
    public static function onAfterFieldsLoad(\Bitrix\Main\Event $event)
    {
        $entityTypeId = $event->getParameter('entityTypeId');
        $fields = $event->getParameter('fields');
        
        // Hole Konfigurationen aus der DB
        $config = self::getConfig($entityTypeId);
        $rules = [];
        $layout = [];
        
        try {
            $rules = Json::decode($config['rules']);
            $layout = Json::decode($config['layout']);
        } catch (\Exception $e) {
            return null;
        }
        
        // Füge zweispaltige Layout-Informationen hinzu
        $fields = self::addTwoColumnLayout($fields, $layout);
        
        // Füge bedingte Feld-Attribute hinzu
        $fields = self::addConditionalAttributes($fields, $rules);
        
        return new \Bitrix\Main\EventResult(
            \Bitrix\Main\EventResult::SUCCESS,
            ['fields' => $fields]
        );
    }
    
    /**
     * Fügt Informationen für zweispaltiges Layout hinzu
     * 
     * @param array $fields
     * @param array $layout
     * @return array
     */
    private static function addTwoColumnLayout($fields, $layout)
    {
        // Gehe durch alle Felder und füge Layout-Attribute hinzu
        foreach ($fields as $fieldId => &$field) {
            if (isset($layout[$fieldId])) {
                $field['COLUMN'] = $layout[$fieldId]['column'];
                $field['ORDER'] = $layout[$fieldId]['order'];
            } else {
                // Standard: alle Felder in der ersten Spalte
                $field['COLUMN'] = 1;
                $field['ORDER'] = isset($field['SORT']) ? $field['SORT'] : 500;
            }
        }
        
        return $fields;
    }
    
    /**
     * Fügt bedingte Feld-Attribute hinzu
     * 
     * @param array $fields
     * @param array $rules
     * @return array
     */
    private static function addConditionalAttributes($fields, $rules)
    {
        // Gehe durch alle Felder und füge bedingte Attribute hinzu
        foreach ($fields as $fieldId => &$field) {
            // Sammle alle Regeln, die dieses Feld betreffen
            $fieldRules = [];
            foreach ($rules as $rule) {
                foreach ($rule['actions'] as $action) {
                    if ($action['fieldId'] == $fieldId) {
                        $fieldRules[] = [
                            'condition' => $rule['condition'],
                            'action' => $action['type'],
                            'value' => isset($action['value']) ? $action['value'] : null
                        ];
                    }
                }
            }
            
            if (!empty($fieldRules)) {
                $field['CONDITIONAL_RULES'] = Json::encode($fieldRules);
            }
        }
        
        return $fields;
    }
    
    /**
     * Holt die Konfiguration für einen Entity-Typ
     * 
     * @param int $entityTypeId
     * @return array
     */
    private static function getConfig($entityTypeId)
    {
        $result = [
            'rules' => '[]',
            'layout' => '{}'
        ];
        
        if (!Loader::includeModule('conditional_fields')) {
            return $result;
        }
        
        $connection = Application::getConnection();
        
        // Hole Regeln
        $query = $connection->query("
            SELECT CONFIG FROM conditional_fields_config 
            WHERE ENTITY_TYPE_ID = " . intval($entityTypeId) . " 
            AND CONFIG_TYPE = 'rules'
        ");
        
        if ($row = $query->fetch()) {
            $result['rules'] = $row['CONFIG'];
        }
        
        // Hole Layout
        $query = $connection->query("
            SELECT CONFIG FROM conditional_fields_config 
            WHERE ENTITY_TYPE_ID = " . intval($entityTypeId) . " 
            AND CONFIG_TYPE = 'layout'
        ");
        
        if ($row = $query->fetch()) {
            $result['layout'] = $row['CONFIG'];
        }
        
        return $result;
    }
    
    /**
     * Speichert Konfiguration für einen Entity-Typ
     * 
     * @param int $entityTypeId
     * @param string $configType
     * @param string $config
     * @return bool
     */
    public static function saveConfig($entityTypeId, $configType, $config)
    {
        if (!Loader::includeModule('conditional_fields')) {
            return false;
        }
        
        $connection = Application::getConnection();
        $now = new \Bitrix\Main\Type\DateTime();
        
        // Prüfe, ob Konfiguration bereits existiert
        $query = $connection->query("
            SELECT ID FROM conditional_fields_config 
            WHERE ENTITY_TYPE_ID = " . intval($entityTypeId) . " 
            AND CONFIG_TYPE = '" . $connection->getSqlHelper()->forSql($configType) . "'
        ");
        
        if ($row = $query->fetch()) {
            // Update
            $connection->query("
                UPDATE conditional_fields_config SET
                CONFIG = '" . $connection->getSqlHelper()->forSql($config) . "',
                MODIFIED_DATE = '" . $now->format("Y-m-d H:i:s") . "'
                WHERE ID = " . intval($row['ID'])
            );
        } else {
            // Insert
            $connection->query("
                INSERT INTO conditional_fields_config 
                (ENTITY_TYPE_ID, CONFIG_TYPE, CONFIG, CREATED_DATE, MODIFIED_DATE)
                VALUES
                (" . intval($entityTypeId) . ",
                '" . $connection->getSqlHelper()->forSql($configType) . "',
                '" . $connection->getSqlHelper()->forSql($config) . "',
                '" . $now->format("Y-m-d H:i:s") . "',
                '" . $now->format("Y-m-d H:i:s") . "')
            ");
        }
        
        return true;
    }
    
    /**
     * Ermittelt die EntityTypeId aus der URL
     * 
     * @param string $url
     * @return int
     */
    private static function getEntityTypeIdFromUrl($url)
    {
        $matches = [];
        if (preg_match('/\/crm\/type\/(\d+)\//', $url, $matches)) {
            return (int)$matches[1];
        }
        
        return 0;
    }
}