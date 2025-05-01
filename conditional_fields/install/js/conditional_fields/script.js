/**
 * ConditionalFields - JavaScript für bedingte Felder und zweispaltiges Layout
 */
(function(window) {
    'use strict';
    
    if (typeof BX === 'undefined') {
        return;
    }
    
    var ConditionalFields = {
        config: {
            entityTypeId: 0,
            rules: [],
            layout: {}
        },
        
        /**
         * Initialisierung
         */
        init: function(config) {
            // Konfiguration übernehmen
            this.config = Object.assign({}, this.config, config);
            
            // Prüfe, ob wir auf einer Smart-Prozess-Seite sind
            if (!this.isSmartProcessPage()) {
                return;
            }
            
            // Initialisiere zweispaltiges Layout
            this.initTwoColumnLayout();
            
            // Initialisiere bedingte Felder
            this.initConditionalFields();
            
            // Event-Listener für Feldänderungen
            this.attachFieldChangeListeners();
            
            console.log('ConditionalFields initialized', this.config);
        },
        
        /**
         * Prüft, ob wir auf einer Smart-Process-Seite sind
         */
        isSmartProcessPage: function() {
            return document.querySelector('.crm-entity-section-control-container') !== null;
        },
        
        /**
         * Initialisiert das zweispaltige Layout
         */
        initTwoColumnLayout: function() {
            var formContainer = document.querySelector('.crm-entity-section');
            if (!formContainer) {
                return;
            }
            
            // Erstelle Container für zweispaltiges Layout
            var twoColumnContainer = document.createElement('div');
            twoColumnContainer.className = 'conditional-fields-two-column-container';
            
            // Erstelle die beiden Spalten
            var leftColumn = document.createElement('div');
            leftColumn.className = 'conditional-fields-column conditional-fields-column-left';
            
            var rightColumn = document.createElement('div');
            rightColumn.className = 'conditional-fields-column conditional-fields-column-right';
            
            // Füge die Spalten zum Container hinzu
            twoColumnContainer.appendChild(leftColumn);
            twoColumnContainer.appendChild(rightColumn);
            
            // Füge CSS zu den Spalten hinzu
            this.addColumnStyles();
            
            // Organisiere die Felder in die Spalten
            this.organizeFieldsInColumns(formContainer, leftColumn, rightColumn);
            
            // Ersetze den originalen Container durch unseren Container
            formContainer.parentNode.replaceChild(twoColumnContainer, formContainer);
        },
        
        /**
         * Fügt CSS für die Spalten hinzu
         */
        addColumnStyles: function() {
            var style = document.createElement('style');
            style.textContent = `
                .conditional-fields-two-column-container {
                    display: flex;
                    flex-wrap: wrap;
                    margin: 0 -10px;
                }
                .conditional-fields-column {
                    flex: 1;
                    min-width: 300px;
                    padding: 0 10px;
                    box-sizing: border-box;
                }
                @media (max-width: 768px) {
                    .conditional-fields-column {
                        flex: 100%;
                    }
                }
            `;
            document.head.appendChild(style);
        },
        
        /**
         * Organisiert die Felder in die Spalten
         */
        organizeFieldsInColumns: function(formContainer, leftColumn, rightColumn) {
            var fields = formContainer.querySelectorAll('.crm-entity-widget-content-block');
            var leftFields = [];
            var rightFields = [];
            
            // Teile die Felder in linke und rechte Spalte
            Array.prototype.forEach.call(fields, function(field) {
                var fieldId = this.getFieldIdFromDOM(field);
                var column = 1; // Standard: linke Spalte
                
                // Prüfe, ob ein Layout für dieses Feld definiert ist
                if (this.config.layout[fieldId] && this.config.layout[fieldId].column) {
                    column = this.config.layout[fieldId].column;
                }
                
                // Füge Feld zur entsprechenden Spalte hinzu
                if (column == 2) {
                    rightFields.push({
                        element: field,
                        order: this.config.layout[fieldId] ? this.config.layout[fieldId].order : 500
                    });
                } else {
                    leftFields.push({
                        element: field,
                        order: this.config.layout[fieldId] ? this.config.layout[fieldId].order : 500
                    });
                }
            }, this);
            
            // Sortiere die Felder nach Order
            leftFields.sort(function(a, b) {
                return a.order - b.order;
            });
            
            rightFields.sort(function(a, b) {
                return a.order - b.order;
            });
            
            // Füge die Felder zu den Spalten hinzu
            leftFields.forEach(function(item) {
                leftColumn.appendChild(item.element);
            });
            
            rightFields.forEach(function(item) {
                rightColumn.appendChild(item.element);
            });
        },
        
        /**
         * Holt die ID eines Feldes aus dem DOM
         */
        getFieldIdFromDOM: function(field) {
            if (!field) {
                return '';
            }
            
            var dataName = field.getAttribute('data-cid');
            if (!dataName) {
                return '';
            }
            
            // Format ist normalerweise "crm-entity-widget-content-block-field_NAME"
            var match = dataName.match(/field_(\w+)$/);
            if (match && match[1]) {
                return match[1];
            }
            
            return '';
        },
        
        /**
         * Initialisiert die bedingten Felder
         */
        initConditionalFields: function() {
            this.applyAllRules();
        },
        
        /**
         * Fügt Event-Listener für Feldänderungen hinzu
         */
        attachFieldChangeListeners: function() {
            var self = this;
            var formContainer = document.querySelector('.crm-entity-section-control-container');
            if (!formContainer) {
                return;
            }
            
            // Delegierter Event-Listener für Änderungen an Formularfeldern
            formContainer.addEventListener('change', function(e) {
                setTimeout(function() {
                    self.applyAllRules();
                }, 100);
            });
            
            // Listener für "input" Events
            formContainer.addEventListener('input', function(e) {
                setTimeout(function() {
                    self.applyAllRules();
                }, 100);
            });
            
            // Listener für Bitrix-eigene change-Events
            BX.addCustomEvent('BX.Crm.EntityEditorField:onChange', function() {
                setTimeout(function() {
                    self.applyAllRules();
                }, 100);
            });
        },
        
        /**
         * Wendet alle Regeln an
         */
        applyAllRules: function() {
            var formData = this.collectFormData();
            
            // Durchlaufe alle Regeln
            for (var i = 0; i < this.config.rules.length; i++) {
                var rule = this.config.rules[i];
                
                // Prüfe, ob die Bedingung erfüllt ist
                var conditionMet = this.evaluateCondition(rule.condition, formData);
                
                // Wende die Aktionen an
                for (var j = 0; j < rule.actions.length; j++) {
                    var action = rule.actions[j];
                    this.applyAction(action, conditionMet, formData);
                }
            }
        },
        
        /**
         * Sammelt alle Formulardaten
         */
        collectFormData: function() {
            var formData = {};
            var fields = document.querySelectorAll('.crm-entity-widget-content-block');
            
            Array.prototype.forEach.call(fields, function(field) {
                var fieldId = this.getFieldIdFromDOM(field);
                var value = this.getFieldValue(field);
                
                if (fieldId) {
                    formData[fieldId] = value;
                }
            }, this);
            
            return formData;
        },
        
        /**
         * Holt den Wert eines Feldes
         */
        getFieldValue: function(field) {
            if (!field) {
                return null;
            }
            
            // Verschiedene Feldtypen abfragen
            var input = field.querySelector('input[type="text"], input[type="number"], input[type="email"], input[type="tel"]');
            if (input) {
                return input.value;
            }
            
            var textarea = field.querySelector('textarea');
            if (textarea) {
                return textarea.value;
            }
            
            var select = field.querySelector('select');
            if (select) {
                return select.value;
            }
            
            var checkbox = field.querySelector('input[type="checkbox"]');
            if (checkbox) {
                return checkbox.checked ? 'Y' : 'N';
            }
            
            var radioChecked = field.querySelector('input[type="radio"]:checked');
            if (radioChecked) {
                return radioChecked.value;
            }
            
            // Für komplexe Felder
            var dataValue = field.getAttribute('data-value');
            if (dataValue) {
                try {
                    return JSON.parse(dataValue);
                } catch (e) {
                    return dataValue;
                }
            }
            
            return null;
        },
        
        /**
         * Evaluiert eine Bedingung
         */
        evaluateCondition: function(condition, data) {
            var fieldId = condition.fieldId;
            var operator = condition.operator;
            var value = condition.value;
            
            if (typeof data[fieldId] === 'undefined') {
                return false;
            }
            
            var fieldValue = data[fieldId];
            
            switch (operator) {
                case 'equal':
                    return fieldValue == value;
                    
                case 'notEqual':
                    return fieldValue != value;
                    
                case 'greater':
                    return fieldValue > value;
                    
                case 'less':
                    return fieldValue < value;
                    
                case 'contains':
                    return typeof fieldValue === 'string' && fieldValue.indexOf(value) !== -1;
                    
                case 'notContains':
                    return typeof fieldValue === 'string' && fieldValue.indexOf(value) === -1;
                    
                case 'empty':
                    return !fieldValue || fieldValue === '' || 
                           (Array.isArray(fieldValue) && fieldValue.length === 0) ||
                           (typeof fieldValue === 'object' && Object.keys(fieldValue).length === 0);
                    
                case 'notEmpty':
                    return fieldValue && fieldValue !== '' && 
                           (!Array.isArray(fieldValue) || fieldValue.length > 0) &&
                           (typeof fieldValue !== 'object' || Object.keys(fieldValue).length > 0);
                    
                default:
                    return false;
            }
        },
        
        /**
         * Wendet eine Aktion an
         */
        applyAction: function(action, conditionMet, formData) {
            var fieldId = action.fieldId;
            var field = this.findFieldInDOM(fieldId);
            
            if (!field) {
                return;
            }
            
            switch (action.type) {
                case 'show':
                    this.toggleFieldVisibility(field, conditionMet);
                    break;
                    
                case 'hide':
                    this.toggleFieldVisibility(field, !conditionMet);
                    break;
                    
                case 'require':
                    this.toggleFieldRequired(field, conditionMet);
                    break;
                    
                case 'setValue':
                    if (conditionMet) {
                        this.setFieldValue(field, action.value);
                    }
                    break;
            }
        },
        
        /**
         * Findet ein Feld im DOM
         */
        findFieldInDOM: function(fieldId) {
            var fields = document.querySelectorAll('.crm-entity-widget-content-block');
            
            for (var i = 0; i < fields.length; i++) {
                var field = fields[i];
                var currentFieldId = this.getFieldIdFromDOM(field);
                
                if (currentFieldId === fieldId) {
                    return field;
                }
            }
            
            return null;
        },
        
        /**
         * Setzt die Sichtbarkeit eines Feldes
         */
        toggleFieldVisibility: function(field, visible) {
            if (!field) {
                return;
            }
            
            if (visible) {
                field.style.display = '';
                field.classList.remove('conditional-fields-hidden');
            } else {
                field.style.display = 'none';
                field.classList.add('conditional-fields-hidden');
            }
        },
        
        /**
         * Setzt ein Feld als erforderlich/optional
         */
        toggleFieldRequired: function(field, required) {
            if (!field) {
                return;
            }
            
            var label = field.querySelector('.crm-entity-widget-content-block-title');
            if (!label) {
                return;
            }
            
            var requiredMark = label.querySelector('.crm-entity-widget-content-block-required');
            
            if (required) {
                if (!requiredMark) {
                    requiredMark = document.createElement('span');
                    requiredMark.className = 'crm-entity-widget-content-block-required';
                    requiredMark.textContent = '*';
                    label.appendChild(requiredMark);
                }
                
                field.setAttribute('data-required', 'true');
            } else {
                if (requiredMark) {
                    requiredMark.remove();
                }
                
                field.removeAttribute('data-required');
            }
        },
        
        /**
         * Setzt den Wert eines Feldes
         */
        setFieldValue: function(field, value) {
            if (!field) {
                return;
            }
            
            // Text/Zahlen/E-Mail/Telefon-Felder
            var input = field.querySelector('input[type="text"], input[type="number"], input[type="email"], input[type="tel"]');
            if (input) {
                input.value = value;
                this.triggerEvent(input, 'change');
                return;
            }
            
            // Textbereiche
            var textarea = field.querySelector('textarea');
            if (textarea) {
                textarea.value = value;
                this.triggerEvent(textarea, 'change');
                return;
            }
            
            // Auswahllisten
            var select = field.querySelector('select');
            if (select) {
                select.value = value;
                this.triggerEvent(select, 'change');
                return;
            }
            
            // Checkboxen
            var checkbox = field.querySelector('input[type="checkbox"]');
            if (checkbox) {
                checkbox.checked = (value === 'Y' || value === true);
                this.triggerEvent(checkbox, 'change');
                return;
            }
            
            // Radio Buttons
            if (value) {
                var radio = field.querySelector('input[type="radio"][value="' + value + '"]');
                if (radio) {
                    radio.checked = true;
                    this.triggerEvent(radio, 'change');
                    return;
                }
            }
            
            // Für komplexe Bitrix-Felder
            try {
                // Event für Bitrix24 Entity Editor auslösen
                BX.onCustomEvent(field, 'BX.Crm.EntityEditorField:setValue', [value]);
            } catch (e) {
                console.error('Fehler beim Setzen des Werts für ein komplexes Feld', e);
            }
        },
        
        /**
         * Löst ein Event auf einem Element aus
         */
        triggerEvent: function(element, eventName) {
            var event;
            
            if (typeof(Event) === 'function') {
                event = new Event(eventName, { bubbles: true });
            } else {
                event = document.createEvent('Event');
                event.initEvent(eventName, true, true);
            }
            
            element.dispatchEvent(event);
        }
    };
    
    // Exportiere Bibliothek
    window.ConditionalFields = ConditionalFields;
    
})(window);