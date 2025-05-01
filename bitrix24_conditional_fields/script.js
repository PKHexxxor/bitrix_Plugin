javascript/**
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
        // Event-Listener für Änderungen an Formularfeldern
        document.addEventListener('change', function(e) {
            // Prüfen, ob das Zielelement ein Formularfeld ist
            if (isFormField(e.target)) {
                // Regeln anwenden
                applyRules();
            }
        });
        
        // Bitrix24-eigene Events abfangen
        if (typeof BX !== 'undefined') {
            BX.addCustomEvent('BX.Crm.EntityEditorField:onChange', function() {
                applyRules();
            });
        }
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
        // Beispielregel: Wenn ein Feld mit ID "UF_CRM_xxx" den Wert "Sonstiges" hat,
        // dann zeige das Feld mit ID "UF_CRM_yyy" an
        var fields = document.querySelectorAll('.crm-entity-widget-content-block');
        
        // Hier würden die tatsächlichen Regeln implementiert werden
        console.log('Regeln werden angewendet...');
    }
})();
