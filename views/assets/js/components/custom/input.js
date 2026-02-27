class CustomInput extends HTMLElement {
    static get observedAttributes() {
        return ["disabled", "placeholder", "value", "data-*"];
    }

    constructor() {
        super();
        this.attachShadow({ mode: "open" });

        // Template del componente
        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    display: inline-block;
                    width: 100%;
                    max-width: 100%;
                    font-family: inherit;
                }
                
                .input-group {
                    display: flex;
                    width: 100%;
                    border: 1px solid #ced4da;
                    border-radius: 4px;
                    overflow: hidden;
                    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
                    background: white;
                }
                
                .input-group:focus-within {
                    border-color: #007bff;
                    box-shadow: 0 0 0 3px rgba(0,123,255,0.25);
                }
                
                .input-group.disabled {
                    background: #e9ecef;
                    opacity: 0.65;
                }
                
                .prepend, .append {
                    display: flex;
                    align-items: center;
                    padding: 0 10px;
                    background: #e9ecef;
                    border: none;
                    font-size: 14px;
                    color: #495057;
                    user-select: none;
                }
                
                .prepend {
                    border-right: 1px solid #ced4da;
                }
                
                .append {
                    border-left: 1px solid #ced4da;
                }
                
                ::slotted([slot="prepend"]),
                ::slotted([slot="append"]) {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    min-width: 30px;
                    height: 100%;
                    cursor: pointer;
                    transition: background-color 0.2s;
                }
                
                ::slotted([slot="prepend"]:hover),
                ::slotted([slot="append"]:hover) {
                    background-color: #dee2e6;
                }
                
                ::slotted([slot="prepend"]:active),
                ::slotted([slot="append"]:active) {
                    background-color: #ced4da;
                }
                
                input {
                    flex: 1;
                    padding: 8px 12px;
                    border: none;
                    outline: none;
                    font-size: 14px;
                    font-family: inherit;
                    min-width: 0;
                    background: transparent;
                }
                
                input:disabled {
                    background: transparent;
                    cursor: not-allowed;
                }
                
                /* Stili per i vari tipi di elementi prepend/append */
                .prepend-text, .append-text {
                    font-weight: bold;
                    background: #6c757d;
                    color: white;
                }
                
                .prepend-icon, .append-icon {
                    font-size: 16px;
                }
                
                .append-btn {
                    background: #007bff;
                    color: white;
                    border: none;
                    padding: 0 15px;
                    height: 100%;
                    cursor: pointer;
                    font-size: 14px;
                }
                
                .append-btn:hover {
                    background: #0056b3;
                }
            </style>
            
            <div class="input-group" part="input-group">
                <div class="prepend" part="prepend">
                    <slot name="prepend"></slot>
                </div>
                <input 
                    type="text" 
                    part="input"
                    id="mainInput"
                    autocomplete="off">
                <div class="append" part="append">
                    <slot name="append"></slot>
                </div>
            </div>
        `;

        // Riferimenti
        this.inputElement = this.shadowRoot.getElementById("mainInput");
        this.inputGroup = this.shadowRoot.querySelector(".input-group");

        // Binding metodi
        this.handleInput = this.handleInput.bind(this);
        this.handleFocus = this.handleFocus.bind(this);
        this.handleSlotClick = this.handleSlotClick.bind(this);
        this.handleKeyDown = this.handleKeyDown.bind(this);

        // Stato interno
        this._value = "";
    }

    connectedCallback() {
        // Setup iniziale
        this.updateFromAttributes();

        // Event listeners
        this.inputElement.addEventListener("input", this.handleInput);
        this.inputElement.addEventListener("focus", this.handleFocus);
        this.inputElement.addEventListener("keydown", this.handleKeyDown);

        // Listener per gli elementi negli slot
        this.shadowRoot.querySelector('slot[name="prepend"]').addEventListener("click", this.handleSlotClick);
        this.shadowRoot.querySelector('slot[name="append"]').addEventListener("click", this.handleSlotClick);

        // Gestione disabled
        if (this.hasAttribute("disabled")) {
            this.inputElement.disabled = true;
            this.inputGroup.classList.add("disabled");
        }
    }

    disconnectedCallback() {
        // Cleanup
        this.inputElement.removeEventListener("input", this.handleInput);
        this.inputElement.removeEventListener("focus", this.handleFocus);
        this.inputElement.removeEventListener("keydown", this.handleKeyDown);

        this.shadowRoot.querySelector('slot[name="prepend"]')?.removeEventListener("click", this.handleSlotClick);
        this.shadowRoot.querySelector('slot[name="append"]')?.removeEventListener("click", this.handleSlotClick);
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (oldValue === newValue) return;

        switch (name) {
            case "disabled":
                this.inputElement.disabled = newValue !== null;
                if (newValue !== null) {
                    this.inputGroup.classList.add("disabled");
                } else {
                    this.inputGroup.classList.remove("disabled");
                }
                break;

            case "placeholder":
                this.inputElement.placeholder = newValue || "";
                break;

            case "value":
                this._value = newValue || "";
                this.inputElement.value = this._value;
                break;
        }
    }

    updateFromAttributes() {
        // Imposta proprietà dagli attributi
        if (this.hasAttribute("placeholder")) {
            this.inputElement.placeholder = this.getAttribute("placeholder");
        }

        if (this.hasAttribute("value")) {
            this._value = this.getAttribute("value");
            this.inputElement.value = this._value;
        }

        if (this.hasAttribute("disabled")) {
            this.inputElement.disabled = true;
            this.inputGroup.classList.add("disabled");
        }
    }

    // Gestione input
    handleInput(event) {
        this._value = event.target.value;

        // Applica formattazione se specificata
        const format = this.dataset.format;
        if (format === "uppercase") {
            this._value = this._value.toUpperCase();
            this.inputElement.value = this._value;
        }

        // Emetti evento personalizzato
        this.dispatchEvent(
            new CustomEvent("input-change", {
                detail: {
                    value: this._value,
                    dataAttributes: this.getAllDataAttributes(),
                },
                bubbles: true,
                composed: true,
            }),
        );

        // Log per debug
        this.logEvent("input", this._value);
    }

    // Gestione focus - seleziona tutto il testo
    handleFocus(event) {
        event.target.select();

        this.dispatchEvent(
            new CustomEvent("input-focus", {
                detail: {
                    value: this._value,
                    dataAttributes: this.getAllDataAttributes(),
                },
                bubbles: true,
                composed: true,
            }),
        );

        this.logEvent("focus", "Input focus - testo selezionato");
    }

    // Gestione tasti
    handleKeyDown(event) {
        const maxLength = this.dataset.maxlength;
        if (maxLength && this._value.length >= parseInt(maxLength) && event.key !== "Backspace" && event.key !== "Delete") {
            event.preventDefault();
            this.logEvent("keydown", `Max length (${maxLength}) raggiunto`);
        }

        this.dispatchEvent(
            new CustomEvent("input-keydown", {
                detail: {
                    key: event.key,
                    value: this._value,
                    dataAttributes: this.getAllDataAttributes(),
                },
                bubbles: true,
                composed: true,
            }),
        );
    }

    // Gestione click su prepend/append
    handleSlotClick(event) {
        const target = event.target;
        const slotName = target.assignedSlot?.name;

        // Trova l'elemento cliccato (potrebbe essere il figlio dello slot)
        const clickedElement = event.composedPath()[0];

        // Estrai data-action se presente
        const action = clickedElement.dataset.action || "click";

        // Raccogli tutti i data attributes dell'elemento cliccato
        const elementData = {};
        for (let key in clickedElement.dataset) {
            elementData[key] = clickedElement.dataset[key];
        }

        // Emetti evento personalizzato
        this.dispatchEvent(
            new CustomEvent("input-group-action", {
                detail: {
                    action: action,
                    slot: slotName,
                    value: this._value,
                    elementData: elementData,
                    componentData: this.getAllDataAttributes(),
                    originalEvent: event.type,
                },
                bubbles: true,
                composed: true,
            }),
        );

        // Log dell'azione
        this.logEvent("action", `Slot: ${slotName}, Action: ${action}, Data: ${JSON.stringify(elementData)}`);

        // Azioni speciali basate su data-action
        switch (action) {
            case "clear":
                this._value = "";
                this.inputElement.value = "";
                this.inputElement.focus();
                break;

            case "copy":
                navigator.clipboard?.writeText(this._value);
                break;

            case "validate":
                this.validateInput();
                break;

            case "calculate":
                this.calculateValue();
                break;

            case "emoji":
                this._value += clickedElement.textContent;
                this.inputElement.value = this._value;
                this.inputElement.focus();
                break;
        }
    }

    // Utility: raccogli tutti i data attributes del componente
    getAllDataAttributes() {
        const data = {};
        for (let key in this.dataset) {
            data[key] = this.dataset[key];
        }
        return data;
    }

    // Validazione base
    validateInput() {
        const min = this.dataset.min ? parseFloat(this.dataset.min) : null;
        const max = this.dataset.max ? parseFloat(this.dataset.max) : null;
        const value = parseFloat(this._value);

        let isValid = true;
        let message = "✅ Input valido";

        if (this.dataset.currency && isNaN(value)) {
            isValid = false;
            message = "❌ Deve essere un numero valido";
        } else if (min !== null && value < min) {
            isValid = false;
            message = `❌ Valore minimo: ${min}`;
        } else if (max !== null && value > max) {
            isValid = false;
            message = `❌ Valore massimo: ${max}`;
        }

        this.dispatchEvent(
            new CustomEvent("input-validate", {
                detail: {
                    isValid: isValid,
                    message: message,
                    value: this._value,
                },
            }),
        );

        this.logEvent("validate", message);
    }

    // Calcolo per input monetario
    calculateValue() {
        if (this.dataset.currency) {
            const value = parseFloat(this._value) || 0;
            const tax = value * 0.22;
            this.logEvent("calculate", `Valore: ${value}${this.dataset.currency}, IVA 22%: ${tax.toFixed(2)}`);
        }
    }

    // Logging
    logEvent(type, message) {
        const logEvent = new CustomEvent("component-log", {
            detail: { type, message, timestamp: new Date().toLocaleTimeString() },
        });
        this.dispatchEvent(logEvent);
    }

    // Getter/Setter per value
    get value() {
        return this._value;
    }

    set value(newValue) {
        this._value = newValue;
        this.inputElement.value = newValue;
        this.setAttribute("value", newValue);
    }

    // Metodo pubblico per focus
    focus() {
        this.inputElement.focus();
    }

    // Metodo pubblico per select
    select() {
        this.inputElement.select();
    }
}

// Registrazione del componente
customElements.define("custom-input", CustomInput);
