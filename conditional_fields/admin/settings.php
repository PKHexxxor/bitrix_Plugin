<?php
/**
 * Admin-Seite für Konfiguration
 */
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Json;
use ConditionalFields\Handlers;

// Zugriffsrechte prüfen
$MODULE_ID = "conditional_fields";
$MODULE_PERMISSION = $APPLICATION->GetGroupRight($MODULE_ID);
if ($MODULE_PERMISSION < "W")
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

// Modul laden
if (!Loader::includeModule($MODULE_ID)) {
    $APPLICATION->AuthForm(Loc::getMessage("MODULE_NOT_INSTALLED"));
}

// Titel setzen
$APPLICATION->SetTitle(Loc::getMessage("CONDITIONAL_FIELDS_ADMIN_TITLE"));

// Admin-Header
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

// Formular verarbeiten
if ($_SERVER["REQUEST_METHOD"] == "POST" && check_bitrix_sessid()) {
    if (isset($_POST["save"]) || isset($_POST["apply"])) {
        $entityTypeId = intval($_REQUEST["entity_type_id"]);
        
        // Regeln speichern
        if (isset($_POST["rules_json"]) && !empty($_POST["rules_json"])) {
            Handlers::saveConfig($entityTypeId, "rules", $_POST["rules_json"]);
        }
        
        // Layout speichern
        if (isset($_POST["layout_json"]) && !empty($_POST["layout_json"])) {
            Handlers::saveConfig($entityTypeId, "layout", $_POST["layout_json"]);
        }
        
        // Erfolgsmeldung
        CAdminMessage::ShowMessage(array(
            "MESSAGE" => Loc::getMessage("CONDITIONAL_FIELDS_SAVED"),
            "TYPE" => "OK",
        ));
        
        // Weiterleitung
        if (isset($_POST["save"])) {
            LocalRedirect("/bitrix/admin/settings.php?lang=".LANGUAGE_ID."&mid=".$MODULE_ID);
        }
    }
}

// Smart-Prozess-Typen laden
$entityTypes = [];

if (Loader::includeModule('crm')) {
    $typesIterator = \Bitrix\Crm\Model\Dynamic\TypeTable::getList([
        'select' => ['ID', 'NAME', 'TITLE'],
        'filter' => ['=IS_USE_IN_USERFIELD' => 'Y']
    ]);
    
    while($type = $typesIterator->fetch()) {
        $typeId = \CCrmOwnerType::SmartInvoice + (int)$type['ID'];
        $entityTypes[$typeId] = $type['TITLE'] ?: $type['NAME'];
    }
}

// Aktuelle Entity-ID
$entityTypeId = (int)$_REQUEST['entity_type_id'] ?: array_key_first($entityTypes);

// Felder des Entity-Typs laden
$fields = [];
if ($entityTypeId > 0 && Loader::includeModule('crm')) {
    $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
    if ($factory) {
        $entityFields = $factory->getFieldsCollection()->toArray();
        foreach ($entityFields as $field) {
            $fields[$field['ID']] = [
                'id' => $field['ID'],
                'title' => $field['TITLE'],
                'type' => $field['TYPE']
            ];
        }
    }
}

// Konfigurationen laden
$config = Handlers::getConfig($entityTypeId);
$rulesJson = $config['rules'];
$layoutJson = $config['layout'];

// CSS
?>
<style>
    .conditional-fields-form {
        padding: 15px;
        background: #fff;
        border: 1px solid #dce7ed;
        border-radius: 4px;
    }
    .conditional-fields-section {
        margin-bottom: 20px;
    }
    .conditional-fields-header {
        font-weight: bold;
        font-size: 16px;
        margin-bottom: 15px;
        padding-bottom: 5px;
        border-bottom: 1px solid #dce7ed;
    }
    .rule-row {
        padding: 10px;
        margin-bottom: 10px;
        background: #f5f9fa;
        border: 1px solid #dce7ed;
        border-radius: 3px;
    }
    .rule-title {
        font-weight: bold;
        margin-bottom: 10px;
    }
    .rule-field {
        margin-bottom: 10px;
    }
    .rule-field label {
        display: block;
        margin-bottom: 5px;
    }
    .rule-field select, .rule-field input {
        width: 100%;
        max-width: 400px;
    }
    .layout-table {
        width: 100%;
        border-collapse: collapse;
    }
    .layout-table th, .layout-table td {
        padding: 8px;
        border: 1px solid #dce7ed;
    }
    .layout-table th {
        background: #f5f9fa;
        text-align: left;
    }
    .add-button {
        margin-top: 10px;
    }
</style>

<?php
// Entity-Type-Auswahl
?>
<form method="GET" action="">
    <div class="adm-info-message-wrap">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?= Loc::getMessage("CONDITIONAL_FIELDS_SELECT_ENTITY") ?></div>
            <div class="adm-info-message-field">
                <select name="entity_type_id" onchange="this.form.submit()">
                    <?php foreach ($entityTypes as $id => $name): ?>
                        <option value="<?= $id ?>" <?= $id == $entityTypeId ? 'selected' : '' ?>><?= htmlspecialcharsbx($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</form>

<?php
// Haupt-Formular
?>
<form method="POST" action="" name="conditional_fields_form">
    <?= bitrix_sessid_post() ?>
    <input type="hidden" name="entity_type_id" value="<?= $entityTypeId ?>">
    
    <div class="conditional-fields-form">
        <?php
        // Tabs
        $tabControl = new CAdminTabControl("tabControl", array(
            array("DIV" => "edit1", "TAB" => Loc::getMessage("CONDITIONAL_FIELDS_TAB_RULES"), "TITLE" => Loc::getMessage("CONDITIONAL_FIELDS_TAB_RULES_TITLE")),
            array("DIV" => "edit2", "TAB" => Loc::getMessage("CONDITIONAL_FIELDS_TAB_LAYOUT"), "TITLE" => Loc::getMessage("CONDITIONAL_FIELDS_TAB_LAYOUT_TITLE")),
        ));
        
        $tabControl->Begin();
        $tabControl->BeginNextTab();
        ?>
        
        <!-- Vereinfachte bedingte Regeln -->
        <tr>
            <td colspan="2">
                <div class="conditional-fields-section">
                    <div class="conditional-fields-header"><?= Loc::getMessage("CONDITIONAL_FIELDS_RULES") ?></div>
                    
                    <div id="rules-container">
                        <!-- Hier werden die Regeln dynamisch eingefügt -->
                    </div>
                    
                    <div class="add-button">
                        <input type="button" class="adm-btn adm-btn-save" id="add-rule-button" value="<?= Loc::getMessage("CONDITIONAL_FIELDS_ADD_RULE") ?>">
                    </div>
                    
                    <input type="hidden" name="rules_json" id="rules-json" value="<?= htmlspecialcharsbx($rulesJson) ?>">
                </div>
            </td>
        </tr>
        
        <?php $tabControl->BeginNextTab(); ?>
        
        <!-- Vereinfachtes Layout -->
        <tr>
            <td colspan="2">
                <div class="conditional-fields-section">
                    <div class="conditional-fields-header"><?= Loc::getMessage("CONDITIONAL_FIELDS_LAYOUT") ?></div>
                    
                    <table class="layout-table">
                        <thead>
                            <tr>
                                <th><?= Loc::getMessage("CONDITIONAL_FIELDS_FIELD") ?></th>
                                <th><?= Loc::getMessage("CONDITIONAL_FIELDS_COLUMN") ?></th>
                                <th><?= Loc::getMessage("CONDITIONAL_FIELDS_ORDER") ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fields as $fieldId => $field): ?>
                                <tr data-field-id="<?= $fieldId ?>">
                                    <td><?= htmlspecialcharsbx($field['title']) ?> [<?= $fieldId ?>]</td>
                                    <td>
                                        <select name="layout[<?= $fieldId ?>][column]" class="layout-column">
                                            <option value="1"><?= Loc::getMessage("CONDITIONAL_FIELDS_COLUMN_LEFT") ?></option>
                                            <option value="2"><?= Loc::getMessage("CONDITIONAL_FIELDS_COLUMN_RIGHT") ?></option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="layout[<?= $fieldId ?>][order]" class="layout-order" value="500">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <input type="hidden" name="layout_json" id="layout-json" value="<?= htmlspecialcharsbx($layoutJson) ?>">
                </div>
            </td>
        </tr>
        
        <?php
        $tabControl->Buttons(array(
            "btnSave" => true,
            "btnApply" => true,
        ));
        $tabControl->End();
        ?>
    </div>
</form>

<!-- JavaScript für dynamische Formularelemente -->
<script>
    BX.ready(function() {
        // Initialisierung
        var rulesContainer = document.getElementById('rules-container');
        var rulesJson = document.getElementById('rules-json');
        var layoutJson = document.getElementById('layout-json');
        var addRuleButton = document.getElementById('add-rule-button');
        
        // Layout-Daten
        var layoutData = {};
        try {
            layoutData = JSON.parse(layoutJson.value) || {};
        } catch (e) {
            layoutData = {};
        }
        
        // Layout-Felder initialisieren
        initLayoutFields();
        
        // Regeln laden
        var rules = [];
        try {
            rules = JSON.parse(rulesJson.value) || [];
        } catch (e) {
            rules = [];
        }
        
        // Regeln rendern
        renderRules();
        
        // Event-Listener für "Regel hinzufügen"
        addRuleButton.addEventListener('click', function() {
            rules.push({
                condition: {
                    fieldId: '',
                    operator: 'equal',
                    value: ''
                },
                actions: [{
                    type: 'show',
                    fieldId: '',
                    value: ''
                }]
            });
            
            renderRules();
            updateRulesJson();
        });
        
        // Layout-Änderungen überwachen
        var layoutColumns = document.querySelectorAll('.layout-column');
        var layoutOrders = document.querySelectorAll('.layout-order');
        
        layoutColumns.forEach(function(select) {
            select.addEventListener('change', updateLayoutJson);
        });
        
        layoutOrders.forEach(function(input) {
            input.addEventListener('change', updateLayoutJson);
        });
        
        /**
         * Initialisiert die Layout-Felder
         */
        function initLayoutFields() {
            var layoutRows = document.querySelectorAll('.layout-table tbody tr');
            
            layoutRows.forEach(function(row) {
                var fieldId = row.getAttribute('data-field-id');
                var columnSelect = row.querySelector('.layout-column');
                var orderInput = row.querySelector('.layout-order');
                
                if (layoutData[fieldId]) {
                    if (layoutData[fieldId].column) {
                        columnSelect.value = layoutData[fieldId].column;
                    }
                    
                    if (layoutData[fieldId].order) {
                        orderInput.value = layoutData[fieldId].order;
                    }
                }
            });
        }
        
        /**
         * Rendert alle Regeln
         */
        function renderRules() {
            rulesContainer.innerHTML = '';
            
            if (rules.length === 0) {
                rulesContainer.innerHTML = '<div class="adm-info-message-wrap"><div class="adm-info-message">' + 
                                         '<?= Loc::getMessage("CONDITIONAL_FIELDS_NO_RULES") ?>' + 
                                         '</div></div>';
                return;
            }
            
            rules.forEach(function(rule, index) {
                rulesContainer.appendChild(createRuleElement(rule, index));
            });
        }
        
        /**
         * Erstellt ein Element für eine Regel
         */
        function createRuleElement(rule, index) {
            var ruleDiv = document.createElement('div');
            ruleDiv.className = 'rule-row';
            ruleDiv.setAttribute('data-rule-index', index);
            
            var titleDiv = document.createElement('div');
            titleDiv.className = 'rule-title';
            titleDiv.innerHTML = '<?= Loc::getMessage("CONDITIONAL_FIELDS_RULE") ?> #' + (index + 1) + 
                               ' <a href="javascript:void(0)" class="delete-rule" data-index="' + index + '">[X]</a>';
            ruleDiv.appendChild(titleDiv);
            
            // Bedingung
            ruleDiv.appendChild(createConditionElement(rule.condition, index));
            
            // Aktionen
            var actionsDiv = document.createElement('div');
            actionsDiv.className = 'rule-actions';
            actionsDiv.innerHTML = '<div class="rule-subtitle"><?= Loc::getMessage("CONDITIONAL_FIELDS_ACTIONS") ?></div>';
            
            var actionsList = document.createElement('div');
            actionsList.className = 'actions-list';
            
            rule.actions.forEach(function(action, actionIndex) {
                actionsList.appendChild(createActionElement(action, index, actionIndex));
            });
            
            actionsDiv.appendChild(actionsList);
            
            // Button zum Hinzufügen einer Aktion
            var addActionButton = document.createElement('button');
            addActionButton.className = 'adm-btn add-action-button';
            addActionButton.setAttribute('type', 'button');
            addActionButton.setAttribute('data-rule-index', index);
            addActionButton.textContent = '<?= Loc::getMessage("CONDITIONAL_FIELDS_ADD_ACTION") ?>';
            addActionButton.addEventListener('click', function() {
                rule.actions.push({
                    type: 'show',
                    fieldId: '',
                    value: ''
                });
                
                renderRules();
                updateRulesJson();
            });
            
            actionsDiv.appendChild(addActionButton);
            ruleDiv.appendChild(actionsDiv);
            
            // Event-Listener für Regel löschen
            var deleteRuleLink = ruleDiv.querySelector('.delete-rule');
            deleteRuleLink.addEventListener('click', function() {
                rules.splice(index, 1);
                renderRules();
                updateRulesJson();
            });
            
            return ruleDiv;
        }
        
        /**
         * Erstellt ein Element für eine Bedingung
         */
        function createConditionElement(condition, ruleIndex) {
            var conditionDiv = document.createElement('div');
            conditionDiv.className = 'rule-condition';
            conditionDiv.innerHTML = '<div class="rule-subtitle"><?= Loc::getMessage("CONDITIONAL_FIELDS_CONDITION") ?></div>';
            
            // Feld
            var fieldDiv = document.createElement('div');
            fieldDiv.className = 'rule-field';
            
            var fieldLabel = document.createElement('label');
            fieldLabel.textContent = '<?= Loc::getMessage("CONDITIONAL_FIELDS_FIELD") ?>';
            fieldDiv.appendChild(fieldLabel);
            
            var fieldSelect = document.createElement('select');
            fieldSelect.name = 'rule[' + ruleIndex + '][condition][fieldId]';
            fieldSelect.className = 'condition-field';
            
            // Leere Option
            var emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = '<?= Loc::getMessage("CONDITIONAL_FIELDS_SELECT_FIELD") ?>';
            fieldSelect.appendChild(emptyOption);
            
            // Feld-Optionen
            <?php foreach ($fields as $fieldId => $field): ?>
                var option = document.createElement('option');
                option.value = '<?= $fieldId ?>';
                option.textContent = '<?= htmlspecialcharsbx($field['title']) ?> [<?= $fieldId ?>]';
                
                if (condition.fieldId === '<?= $fieldId ?>') {
                    option.selected = true;
                }
                
                fieldSelect.appendChild(option);
            <?php endforeach; ?>
            
            fieldDiv.appendChild(fieldSelect);
            conditionDiv.appendChild(fieldDiv);
            
            // Operator
            var operatorDiv = document.createElement('div');
            operatorDiv.className = 'rule-field';
            
            var operatorLabel = document.createElement('label');
            operatorLabel.textContent = '<?= Loc::getMessage("CONDITIONAL_FIELDS_OPERATOR") ?>';
            operatorDiv.appendChild(operatorLabel);
            
            var operatorSelect = document.createElement('select');
            operatorSelect.name = 'rule[' + ruleIndex + '][condition][operator]';
            operatorSelect.className = 'condition-operator';
            
            var operators = {
                'equal': '<?= Loc::getMessage("CONDITIONAL_FIELDS_OPERATOR_EQUAL") ?>',
                'notEqual': '<?= Loc::getMessage("CONDITIONAL_FIELDS_OPERATOR_NOT_EQUAL") ?>',
                'greater': '<?= Loc::getMessage("CONDITIONAL_FIELDS_OPERATOR_GREATER") ?>',
                'less': '<?= Loc::getMessage("CONDITIONAL_FIELDS_OPERATOR_LESS") ?>',
                'contains': '<?= Loc::getMessage("CONDITIONAL_FIELDS_OPERATOR_CONTAINS") ?>',
                'notContains': '<?= Loc::getMessage("CONDITIONAL_FIELDS_OPERATOR_NOT_CONTAINS") ?>',
                'empty': '<?= Loc::getMessage("CONDITIONAL_FIELDS_OPERATOR_EMPTY") ?>',
                'notEmpty': '<?= Loc::getMessage("CONDITIONAL_FIELDS_OPERATOR_NOT_EMPTY") ?>'
            };
            
            for (var key in operators) {
                var option = document.createElement('option');
                option.value = key;
                option.textContent = operators[key];
                
                if (condition.operator === key) {
                    option.selected = true;
                }
                
                operatorSelect.appendChild(option);
            }
            
            operatorDiv.appendChild(operatorSelect);
            conditionDiv.appendChild(operatorDiv);
            
            // Wert (nur für bestimmte Operatoren)
            var valueDiv = document.createElement('div');
            valueDiv.className = 'rule-field condition-value';
            
            if (condition.operator === 'empty' || condition.operator === 'notEmpty') {
                valueDiv.style.display = 'none';
            }
            
            var valueLabel = document.createElement('label');
            valueLabel.textContent = '<?= Loc::getMessage("CONDITIONAL_FIELDS_VALUE") ?>';
            valueDiv.appendChild(valueLabel);
            
            var valueInput = document.createElement('input');
            valueInput.type = 'text';
            valueInput.name = 'rule[' + ruleIndex + '][condition][value]';
            valueInput.value = condition.value || '';
            valueDiv.appendChild(valueInput);
            
            conditionDiv.appendChild(valueDiv);
            
            // Event-Listener für Operator-Änderung
            operatorSelect.addEventListener('change', function() {
                if (this.value === 'empty' || this.value === 'notEmpty') {
                    valueDiv.style.display = 'none';
                } else {
                    valueDiv.style.display = '';
                }
                
                updateRulesJson();
            });
            
            // Event-Listener für Feld-Änderung
            fieldSelect.addEventListener('change', function() {
                updateRulesJson();
            });
            
            // Event-Listener für Wert-Änderung
            valueInput.addEventListener('input', function() {
                updateRulesJson();
            });
            
            return conditionDiv;
        }
        
        /**
         * Erstellt ein Element für eine Aktion
         */
        function createActionElement(action, ruleIndex, actionIndex) {
            var actionDiv = document.createElement('div');
            actionDiv.className = 'rule-action';
            actionDiv.setAttribute('data-rule-index', ruleIndex);
            actionDiv.setAttribute('data-action-index', actionIndex);
            
            // Typ
            var typeDiv = document.createElement('div');
            typeDiv.className = 'rule-field';
            
            var typeLabel = document.createElement('label');
            typeLabel.textContent = '<?= Loc::getMessage("CONDITIONAL_FIELDS_ACTION_TYPE") ?>';
            typeDiv.appendChild(typeLabel);
            
            var typeSelect = document.createElement('select');
            typeSelect.name = 'rule[' + ruleIndex + '][actions][' + actionIndex + '][type]';
            typeSelect.className = 'action-type';
            
            var actionTypes = {
                'show': '<?= Loc::getMessage("CONDITIONAL_FIELDS_ACTION_SHOW") ?>',
                'hide': '<?= Loc::getMessage("CONDITIONAL_FIELDS_ACTION_HIDE") ?>',
                'require': '<?= Loc::getMessage("CONDITIONAL_FIELDS_ACTION_REQUIRE") ?>',
                'setValue': '<?= Loc::getMessage("CONDITIONAL_FIELDS_ACTION_SET_VALUE") ?>'
            };
            
            for (var key in actionTypes) {
                var option = document.createElement('option');
                option.value = key;
                option.textContent = actionTypes[key];
                
                if (action.type === key) {
                    option.selected = true;
                }
                
                typeSelect.appendChild(option);
            }
            
            typeDiv.appendChild(typeSelect);
            actionDiv.appendChild(typeDiv);
            
            // Feld
            var fieldDiv = document.createElement('div');
            fieldDiv.className = 'rule-field';
            
            var fieldLabel = document.createElement('label');
            fieldLabel.textContent = '<?= Loc::getMessage("CONDITIONAL_FIELDS_FIELD") ?>';
            fieldDiv.appendChild(fieldLabel);
            
            var fieldSelect = document.createElement('select');
            fieldSelect.name = 'rule[' + ruleIndex + '][actions][' + actionIndex + '][fieldId]';
            fieldSelect.className = 'action-field';
            
            // Leere Option
            var emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = '<?= Loc::getMessage("CONDITIONAL_FIELDS_SELECT_FIELD") ?>';
            fieldSelect.appendChild(emptyOption);
            
            // Feld-Optionen
            <?php foreach ($fields as $fieldId => $field): ?>
                var option = document.createElement('option');
                option.value = '<?= $fieldId ?>';
                option.textContent = '<?= htmlspecialcharsbx($field['title']) ?> [<?= $fieldId ?>]';
                
                if (action.fieldId === '<?= $fieldId ?>') {
                    option.selected = true;
                }
                
                fieldSelect.appendChild(option);
            <?php endforeach; ?>
            
            fieldDiv.appendChild(fieldSelect);
            actionDiv.appendChild(fieldDiv);
            
            // Wert (nur für setValue)
            var valueDiv = document.createElement('div');
            valueDiv.className = 'rule-field action-value';
            
            if (action.type !== 'setValue') {
                valueDiv.style.display = 'none';
            }
            
            var valueLabel = document.createElement('label');
            valueLabel.textContent = '<?= Loc::getMessage("CONDITIONAL_FIELDS_VALUE") ?>';
            valueDiv.appendChild(valueLabel);
            
            var valueInput = document.createElement('input');
            valueInput.type = 'text';
            valueInput.name = 'rule[' + ruleIndex + '][actions][' + actionIndex + '][value]';
            valueInput.value = action.value || '';
            valueDiv.appendChild(valueInput);
            
            actionDiv.appendChild(valueDiv);
            
            // Löschen-Button
            var deleteDiv = document.createElement('div');
            deleteDiv.className = 'rule-field';
            
            var deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.className = 'adm-btn adm-btn-delete delete-action';
            deleteButton.textContent = 'X';
            deleteButton.title = '<?= Loc::getMessage("CONDITIONAL_FIELDS_DELETE_ACTION") ?>';
            
            deleteButton.addEventListener('click', function() {
                rules[ruleIndex].actions.splice(actionIndex, 1);
                renderRules();
                updateRulesJson();
            });
            
            deleteDiv.appendChild(deleteButton);
            actionDiv.appendChild(deleteDiv);
            
            // Event-Listener für Typ-Änderung
            typeSelect.addEventListener('change', function() {
                if (this.value === 'setValue') {
                    valueDiv.style.display = '';
                } else {
                    valueDiv.style.display = 'none';
                }
                
                updateRulesJson();
            });
            
            // Event-Listener für Feld-Änderung
            fieldSelect.addEventListener('change', function() {
                updateRulesJson();
            });
            
            // Event-Listener für Wert-Änderung
            valueInput.addEventListener('input', function() {
                updateRulesJson();
            });
            
            return actionDiv;
        }
        
        /**
         * Aktualisiert das Rules-JSON
         */
        function updateRulesJson() {
            // Sammle die Daten aus dem Formular
            var updatedRules = [];
            
            var ruleRows = document.querySelectorAll('.rule-row');
            ruleRows.forEach(function(ruleRow, ruleIndex) {
                var conditionFieldSelect = ruleRow.querySelector('.condition-field');
                var conditionOperatorSelect = ruleRow.querySelector('.condition-operator');
                var conditionValueInput = ruleRow.querySelector('.condition-value input');
                
                var rule = {
                    condition: {
                        fieldId: conditionFieldSelect.value,
                        operator: conditionOperatorSelect.value,
                        value: conditionValueInput.value
                    },
                    actions: []
                };
                
                var actionDivs = ruleRow.querySelectorAll('.rule-action');
                actionDivs.forEach(function(actionDiv) {
                    var actionTypeSelect = actionDiv.querySelector('.action-type');
                    var actionFieldSelect = actionDiv.querySelector('.action-field');
                    var actionValueInput = actionDiv.querySelector('.action-value input');
                    
                    var action = {
                        type: actionTypeSelect.value,
                        fieldId: actionFieldSelect.value
                    };
                    
                    if (actionTypeSelect.value === 'setValue') {
                        action.value = actionValueInput.value;
                    }
                    
                    rule.actions.push(action);
                });
                
                updatedRules.push(rule);
            });
            
            // Aktualisiere die Regeln
            rules = updatedRules;
            
            // Aktualisiere das versteckte Feld
            rulesJson.value = JSON.stringify(rules);
        }
        
        /**
         * Aktualisiert das Layout-JSON
         */
        function updateLayoutJson() {
            var layoutData = {};
            
            var layoutRows = document.querySelectorAll('.layout-table tbody tr');
            layoutRows.forEach(function(row) {
                var fieldId = row.getAttribute('data-field-id');
                var columnSelect = row.querySelector('.layout-column');
                var orderInput = row.querySelector('.layout-order');
                
                layoutData[fieldId] = {
                    column: columnSelect.value,
                    order: orderInput.value
                };
            });
            
            layoutJson.value = JSON.stringify(layoutData);
        }
    });
</script>

<?php
// Admin-Footer
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>