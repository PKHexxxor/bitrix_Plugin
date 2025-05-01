/**
 * Conditional Fields für Bitrix24 Smart-Prozesse
 * Implementiert zweispaltiges Layout und bedingte Feldlogik
 */
(function() {
    // Konfiguration
    var config = {
        // Standardlayout (50/50)
        layout: {
            mode: 'equal', // 'equal', 'custom'
            ratio: '50/50', // Nur für Modus 'equal'
        },
        // Beispiel für bedingte Regeln
        rules: [
            // Beispiel: Wenn Feld X den Wert Y hat, zeige Feld Z
            /*{
                condition: {
                    fieldId: 'UF_CRM_FIELD_ID', 
                    operator: 'equal', 
                    value: 'Sonstiges'
                },
                action: {
                    fieldId: 'UF_CRM_OTHER_FIELD',
                    type: 'show'
                }
            }*/
        ]
    };

    // Initialisieren, wenn Bitrix24 SDK geladen ist
    BX24.ready(function() {
        // Prüfen, ob wir uns auf einer Smart-Prozess-Seite befinden
        if (window.location.href.indexOf('/crm/type/') !== -1) {
            console.log('Conditional Fields: Smart-Prozess-Seite erkannt');
            
            // CSS für das Layout einfügen
            addCSS();
            
            // Warten, bis das DOM geladen ist
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initConditionalFields);
            } else {
                initConditionalFields();
            }
        }
    });
    
    /**
     * Fügt das CSS für das Layout ein
     */
    function addCSS() {
        var style = document.createElement('style');
        style.textContent = `
            /* Container für das zweispaltige Layout */
            .conditional-fields-two-column-container {
                display: flex;
                flex-wrap: wrap;
                margin: 0 -10px;
            }
            
            /* Styling für die Spalten */
            .conditional-fields-column {
                flex: 1;
                min-width: 300px;
                padding: 0 10px;
                box-sizing: border-box;
            }
            
            /* Responsive Design für mobile Geräte */
            @media (max-width: 768px) {
                .conditional-fields-column {
                    flex: 100%;
                }
            }
            
            /* Klasse für versteckte Felder */
            .conditional-fields-hidden {
                display: none !important;
            }
        `;
        document.head.appendChild(style);
    }
    
    /**
     * Initialisiert die bedingten Felder
     */
    function initConditionalFields() {
        console.log('Conditional Fields: Initialisierung gestartet');
        
        // Smart-Prozess-Formular finden
        var formContainer = document.querySelector('.crm-entity-section');
        if (!formContainer) {
            console.log('Smart-Prozess-Formular nicht gefunden');
            return;
        }
        
        // Layout initialisieren
        initTwoColumnLayout(formContainer);
        
        // Event-Listener für Feldänderungen hinzufügen
        attachFieldChangeListeners();
        
        // Initiale Anwendung der Regeln
        applyRules();
        
        console.log('Conditional Fields: Initialisierung abgeschlossen');
    }
    
    /**
     * Initialisiert das zweispaltige Layout
     */
    function initTwoColumnLayout(formContainer) {
        console.log('Conditional Fields: Layout wird initialisiert');
        
        // Container für zweispaltiges Layout erstellen
        var twoColumnContainer = document.createElement('div');
        twoColumnContainer.className = 'conditional-fields-two-column-container';
        twoColumnContainer.id = 'conditional-fields-container';
        
        // Spalten erstellen
        var leftColumn = document.createElement('div');
        leftColumn.className = 'conditional-fields-column conditional-fields-column-left';
        
        var rightColumn = document.createElement('div');
        rightColumn.className = 'conditional-fields-column conditional-fields-column-right';
        
        // Spalten zum Container hinzufügen
        twoColumnContainer.appendChild(leftColumn);
        twoColumnContainer.appendChild(rightColumn);
        
        // Felder auf die Spalten verteilen
        var fields = formContainer.querySelectorAll('.crm-entity-widget-content-block');
        
        // Felder klonen und auf Spalten verteilen
        fields.forEach(function(field, index) {
            // Entscheide basierend auf dem Index, in welche Spalte das Feld kommt
            if (index % 2 === 0) {
                leftColumn.appendChild(field);
            } else {
                rightColumn.appendChild(field);
            }
        });
        
        // Alten Container durch neuen ersetzen
        formContainer.parentNode.replaceChild(twoColumnContainer, formContainer);
        
        console.log('Conditional Fields: Layout initialisiert');
    }
    
    /**
     * Fügt Event-Listener für Feldänderungen hinzu
     */
    function attachFieldChangeListeners() {
        console.log('Conditional Fields: Event-Listener werden angehängt');
        
        // Event-Listener für Änderungen an Formularfeldern
        document.addEventListener('change', function(e) {
            // Prüfen, ob das Zielelement ein Formularfeld ist
            if (isFormField(e.target)) {
                // Regeln anwenden
                applyRules();
            }
        });
        
        // Input-Events für Textfelder (für Echtzeit-Updates)
        document.addEventListener('input', function(e) {
            if (isFormField(e.target)) {
                // Verzögerung, um nicht bei jedem Tastenanschlag zu aktualisieren
                setTimeout(applyRules, 300);
            }
        });
        
        // Bitrix24-eigene Events abfangen
        if (typeof BX !== 'undefined') {
            BX.addCustomEvent('BX.Crm.EntityEditorField:onChange', function() {
                applyRules();
            });
        }
        
        console.log('Conditional Fields: Event-Listener angehängt');
    }
    
    /**
     * Prüft, ob ein Element ein Formularfeld ist
     */
    function isFormField(element) {
        var formFieldTypes = ['INPUT', 'SELECT', 'TEXTAREA'];
        return formFieldTypes.indexOf(element.tagName) !== -1;
    }
    
    /**
     * Wendet die Regeln auf die Felder an
     */
    function applyRules() {
        console.log('Conditional Fields: Regeln werden angewendet');
        
        // Formularwerte sammeln
        var formValues = collectFormValues();
        
        // Regeln durchlaufen und anwenden
        config.rules.forEach(function(rule) {
            // Bedingung auswerten
            var conditionMet = evaluateCondition(rule.condition, formValues);
            
            // Aktion ausführen
            executeAction(rule.action, conditionMet);
        });
    }
    
    /**
     * Sammelt alle Formularwerte
     */
    function collectFormValues() {
        var values = {};
        
        // Alle Formularfelder durchlaufen
        var fields = document.querySelectorAll('.crm-entity-widget-content-block');
        fields.forEach(function(field) {
            var fieldId = getFieldId(field);
            if (fieldId) {
                values[fieldId] = getFieldValue(field);
            }
        });
        
        return values;
    }
    
    /**
     * Ermittelt die ID eines Feldes
     */
    function getFieldId(field) {
        // Versuche, die ID aus dem data-cid-Attribut zu extrahieren
        var dataCid = field.getAttribute('data-cid');
        if (dataCid) {
            var match = dataCid.match(/field_(\w+)/);
            if (match && match[1]) {
                return match[1];
            }
        }
        
        return null;
    }
    
    /**
     * Ermittelt den Wert eines Feldes
     */
    function getFieldValue(field) {
        // Input-Felder
        var input = field.querySelector('input[type="text"], input[type="number"], input[type="email"]');
        if (input) {
            return input.value;
        }
        
        // Select-Felder
        var select = field.querySelector('select');
        if (select) {
            return select.value;
        }
        
        // Textarea-Felder
        var textarea = field.querySelector('textarea');
        if (textarea) {
            return textarea.value;
        }
        
        // Checkbox-Felder
        var checkbox = field.querySelector('input[type="checkbox"]');
        if (checkbox) {
            return checkbox.checked;
        }
        
        // Radio-Buttons
        var radioChecked = field.querySelector('input[type="radio"]:checked');
        if (radioChecked) {
            return radioChecked.value;
        }
        
        return null;
    }
    
    /**
     * Wertet eine Bedingung aus
     */
    function evaluateCondition(condition, formValues) {
        // Feldwert abrufen
        var fieldValue = formValues[condition.fieldId];
        
        // Wenn das Feld nicht existiert oder keinen Wert hat
        if (fieldValue === undefined || fieldValue === null) {
            return false;
        }
        
        // Je nach Operator auswerten
        switch (condition.operator) {
            case 'equal':
                return fieldValue == condition.value;
                
            case 'notEqual':
                return fieldValue != condition.value;
                
            case 'contains':
                return String(fieldValue).indexOf(condition.value) !== -1;
                
            case 'notContains':
                return String(fieldValue).indexOf(condition.value) === -1;
                
            case 'empty':
                return !fieldValue || fieldValue === '' || fieldValue.length === 0;
                
            case 'notEmpty':
                return fieldValue && fieldValue !== '' && fieldValue.length > 0;
                
            default:
                return false;
        }
    }
    
    /**
     * Führt eine Aktion aus
     */
    function executeAction(action, conditionMet) {
        // Feld finden
        var field = findField(action.fieldId);
        if (!field) {
            return;
        }
        
        // Aktion je nach Typ ausführen
        switch (action.type) {
            case 'show':
                toggleFieldVisibility(field, conditionMet);
                break;
                
            case 'hide':
                toggleFieldVisibility(field, !conditionMet);
                break;
                
            case 'require':
                toggleFieldRequired(field, conditionMet);
                break;
                
            case 'setValue':
                if (conditionMet) {
                    setFieldValue(field, action.value);
                }
                break;
        }
    }
    
    /**
     * Findet ein Feld anhand seiner ID
     */
    function findField(fieldId) {
        var fields = document.querySelectorAll('.crm-entity-widget-content-block');
        
        for (var i = 0; i < fields.length; i++) {
            var field = fields[i];
            var id = getFieldId(field);
            
            if (id === fieldId) {
                return field;
            }
        }
        
        return null;
    }
    
    /**
     * Ändert die Sichtbarkeit eines Feldes
     */
    function toggleFieldVisibility(field, visible) {
        if (visible) {
            field.style.display = '';
            field.classList.remove('conditional-fields-hidden');
        } else {
            field.style.display = 'none';
            field.classList.add('conditional-fields-hidden');
        }
    }
    
    /**
     * Ändert, ob ein Feld ein Pflichtfeld ist
     */
    function toggleFieldRequired(field, required) {
        // Title-Element finden
        var title = field.querySelector('.crm-entity-widget-content-block-title');
        if (!title) {
            return;
        }
        
        // Prüfen, ob bereits ein Pflichtfeld-Marker existiert
        var marker = title.querySelector('.crm-entity-widget-content-block-required');
        
        if (required) {
            // Marker hinzufügen, falls noch nicht vorhanden
            if (!marker) {
                marker = document.createElement('span');
                marker.className = 'crm-entity-widget-content-block-required';
                marker.textContent = '*';
                title.appendChild(marker);
            }
        } else {
            // Marker entfernen, falls vorhanden
            if (marker) {
                marker.remove();
            }
        }
    }
    
    /**
     * Setzt den Wert eines Feldes
     */
    function setFieldValue(field, value) {
        // Input-Felder
        var input = field.querySelector('input[type="text"], input[type="number"], input[type="email"]');
        if (input) {
            input.value = value;
            triggerEvent(input, 'change');
            return;
        }
        
        // Select-Felder
        var select = field.querySelector('select');
        if (select) {
            select.value = value;
            triggerEvent(select, 'change');
            return;
        }
        
        // Textarea-Felder
        var textarea = field.querySelector('textarea');
        if (textarea) {
            textarea.value = value;
            triggerEvent(textarea, 'change');
            return;
        }
        
        // Checkbox-Felder
        var checkbox = field.querySelector('input[type="checkbox"]');
        if (checkbox) {
            checkbox.checked = (value === true || value === 'Y' || value === '1');
            triggerEvent(checkbox, 'change');
            return;
        }
        
        // Radio-Buttons
        var radio = field.querySelector('input[type="radio"][value="' + value + '"]');
        if (radio) {
            radio.checked = true;
            triggerEvent(radio, 'change');
            return;
        }
    }
    
    /**
     * Löst ein Event auf einem Element aus
     */
    function triggerEvent(element, eventName) {
        var event;
        
        if (typeof Event === 'function') {
            event = new Event(eventName, { bubbles: true });
        } else {
            // Für ältere Browser
            event = document.createEvent('Event');
            event.initEvent(eventName, true, true);
        }
        
        element.dispatchEvent(event);
    }
})();
