class CustomSelect extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: "open" });

        this._selectedValues = [];
        this._open = false;
        this._searchTerm = "";
        this._options = [];

        this.render();
    }

    render() {
        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    display: block;
                    font-family: Arial, sans-serif;
                }
                
                .container {
                    position: relative;
                    width: 100%;
                }
                
                .display {
                    display: flex;
                    align-items: center;
                    width: 100%;
                    min-height: 38px;
                    border: 1px solid #ced4da;
                    border-radius: 4px;
                    background: white;
                    cursor: pointer;
                    box-sizing: border-box;
                }
                
                .display:hover {
                    border-color: #28a745;
                }
                
                .display.open {
                    border-color: #28a745;
                    box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.25);
                }
                
                .prepend, .append {
                    display: flex;
                    align-items: center;
                    padding: 0 8px;
                    height: 100%;
                    background: #f8f9fa;
                }
                
                .prepend {
                    border-right: 1px solid #ced4da;
                }
                
                .append {
                    border-left: 1px solid #ced4da;
                }
                
                .value {
                    flex: 1;
                    padding: 8px 12px;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
                
                .placeholder {
                    color: #6c757d;
                }
                
                .arrow {
                    padding: 0 10px;
                    color: #6c757d;
                }
                
                .dropdown {
                    position: absolute;
                    top: 100%;
                    left: 0;
                    right: 0;
                    margin-top: 2px;
                    background: white;
                    border: 1px solid #ced4da;
                    border-radius: 4px;
                    max-height: 300px;
                    overflow-y: auto;
                    z-index: 1000;
                    display: none;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                }
                
                .dropdown.open {
                    display: block;
                }
                
                .search-box {
                    padding: 8px;
                    border-bottom: 1px solid #ced4da;
                    position: sticky;
                    top: 0;
                    background: white;
                }
                
                .search-box input {
                    width: 100%;
                    padding: 6px 8px;
                    border: 1px solid #ced4da;
                    border-radius: 3px;
                    box-sizing: border-box;
                }
                
                .search-box input:focus {
                    outline: none;
                    border-color: #28a745;
                }
                
                .options {
                    padding: 4px 0;
                }
                
                .option {
                    padding: 8px 12px;
                    cursor: pointer;
                }
                
                .option:hover {
                    background: #28a745;
                    color: white;
                }
                
                .option.selected {
                    background: #d4edda;
                    color: #155724;
                }
                
                .option.selected:hover {
                    background: #28a745;
                    color: white;
                }
                
                .selected-tags {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 4px;
                    padding: 4px 8px;
                }
                
                .tag {
                    background: #28a745;
                    color: white;
                    padding: 2px 6px;
                    border-radius: 3px;
                    font-size: 12px;
                    display: inline-flex;
                    align-items: center;
                }
                
                .tag .remove {
                    margin-left: 4px;
                    cursor: pointer;
                    font-weight: bold;
                    padding: 0 2px;
                }
                
                .tag .remove:hover {
                    color: #dc3545;
                }
                
                .no-results {
                    padding: 12px;
                    text-align: center;
                    color: #6c757d;
                }
                
                ::slotted([slot="prepend"]),
                ::slotted([slot="append"]) {
                    cursor: pointer;
                }
            </style>
            
            <div class="container">
                <div class="display" id="display">
                    <div class="prepend">
                        <slot name="prepend"></slot>
                    </div>
                    <div class="value" id="valueDisplay">
                        <span class="placeholder">${this.getAttribute("placeholder") || "Seleziona..."}</span>
                    </div>
                    <div class="append">
                        <slot name="append"></slot>
                    </div>
                    <div class="arrow">▼</div>
                </div>
                
                <div class="dropdown" id="dropdown">
                    <div class="search-box" id="searchBox">
                        <input type="text" id="searchInput" placeholder="Cerca...">
                    </div>
                    <div class="options" id="options"></div>
                </div>
            </div>
        `;

        this.display = this.shadowRoot.getElementById("display");
        this.dropdown = this.shadowRoot.getElementById("dropdown");
        this.valueDisplay = this.shadowRoot.getElementById("valueDisplay");
        this.searchInput = this.shadowRoot.getElementById("searchInput");
        this.searchBox = this.shadowRoot.getElementById("searchBox");
        this.options = this.shadowRoot.getElementById("options");

        this.loadOptions();
        this.attachEvents();
    }

    attachEvents() {
        // Click sul display
        this.display.addEventListener("click", (e) => {
            e.stopPropagation();
            this.toggleDropdown();
        });

        // Click fuori per chiudere
        document.addEventListener("click", (e) => {
            if (!this.shadowRoot.contains(e.target)) {
                this.closeDropdown();
            }
        });

        // Click sugli slot prepend/append
        this.shadowRoot.querySelector('slot[name="prepend"]').addEventListener("click", (e) => {
            e.stopPropagation();
            const target = e.target;
            if (target.dataset && target.dataset.action) {
                this.handleAction(target.dataset.action, target);
            }
        });

        this.shadowRoot.querySelector('slot[name="append"]').addEventListener("click", (e) => {
            e.stopPropagation();
            const target = e.target;
            if (target.dataset && target.dataset.action) {
                this.handleAction(target.dataset.action, target);
            }
        });

        // Ricerca
        if (this.searchInput) {
            this.searchInput.addEventListener("input", (e) => {
                this._searchTerm = e.target.value.toLowerCase();
                this.renderOptions();
            });

            this.searchInput.addEventListener("click", (e) => {
                e.stopPropagation();
            });
        }
    }

    loadOptions() {
        this._options = [];
        const options = this.querySelectorAll("option");

        options.forEach((opt) => {
            if (opt.value) {
                this._options.push({
                    value: opt.value,
                    text: opt.textContent,
                    selected: opt.selected,
                    data: opt.dataset,
                });

                if (opt.selected) {
                    if (this.hasAttribute("multiple")) {
                        this._selectedValues.push(opt.value);
                    } else {
                        this._selectedValues = [opt.value];
                    }
                }
            }
        });

        this.updateDisplay();
        this.renderOptions();
    }

    renderOptions() {
        const filtered = this._options.filter((opt) => opt.text.toLowerCase().includes(this._searchTerm) || opt.value.toLowerCase().includes(this._searchTerm));

        if (filtered.length === 0) {
            this.options.innerHTML = '<div class="no-results">Nessun risultato</div>';
            return;
        }

        let html = "";
        filtered.forEach((opt) => {
            const isSelected = this._selectedValues.includes(opt.value);
            html += `
                        <div class="option ${isSelected ? "selected" : ""}" data-value="${opt.value}">
                            ${opt.text}
                        </div>
                    `;
        });

        this.options.innerHTML = html;

        // Event listeners per le opzioni
        this.options.querySelectorAll(".option").forEach((opt) => {
            opt.addEventListener("click", (e) => {
                e.stopPropagation();
                const value = opt.dataset.value;
                this.selectOption(value);
            });
        });
    }

    selectOption(value) {
        if (this.hasAttribute("multiple")) {
            const index = this._selectedValues.indexOf(value);
            if (index === -1) {
                this._selectedValues.push(value);
            } else {
                this._selectedValues.splice(index, 1);
            }
        } else {
            this._selectedValues = [value];
            this.closeDropdown();
        }

        this.updateDisplay();
        this.renderOptions();
        this.emitChange();
    }

    updateDisplay() {
        if (this._selectedValues.length === 0) {
            const placeholder = this.getAttribute("placeholder") || "Seleziona...";
            this.valueDisplay.innerHTML = `<span class="placeholder">${placeholder}</span>`;
            return;
        }

        if (this.hasAttribute("multiple")) {
            let tagsHtml = '<div class="selected-tags">';
            this._selectedValues.forEach((value) => {
                const option = this._options.find((o) => o.value === value);
                if (option) {
                    tagsHtml += `
                                <span class="tag">
                                    ${option.text}
                                    <span class="remove" data-value="${value}">✕</span>
                                </span>
                            `;
                }
            });
            tagsHtml += "</div>";
            this.valueDisplay.innerHTML = tagsHtml;

            // Event listeners per rimuovere tags
            this.valueDisplay.querySelectorAll(".remove").forEach((btn) => {
                btn.addEventListener("click", (e) => {
                    e.stopPropagation();
                    const value = btn.dataset.value;
                    this.removeValue(value);
                });
            });
        } else {
            const option = this._options.find((o) => o.value === this._selectedValues[0]);
            this.valueDisplay.textContent = option ? option.text : "";
        }
    }

    removeValue(value) {
        this._selectedValues = this._selectedValues.filter((v) => v !== value);
        this.updateDisplay();
        this.renderOptions();
        this.emitChange();
    }

    toggleDropdown() {
        if (this._open) {
            this.closeDropdown();
        } else {
            this.openDropdown();
        }
    }

    openDropdown() {
        this._open = true;
        this.dropdown.classList.add("open");
        this.display.classList.add("open");

        // Reset ricerca
        this._searchTerm = "";
        if (this.searchInput) {
            this.searchInput.value = "";
            setTimeout(() => this.searchInput.focus(), 50);
        }
        this.renderOptions();
    }

    closeDropdown() {
        this._open = false;
        this.dropdown.classList.remove("open");
        this.display.classList.remove("open");
    }

    handleAction(action, element) {
        this.dispatchEvent(
            new CustomEvent("select-action", {
                detail: {
                    action: action,
                    elementData: element.dataset,
                    selectedValues: this._selectedValues,
                },
                bubbles: true,
                composed: true,
            }),
        );

        switch (action) {
            case "info":
                const selected = this._options.find((o) => o.value === this._selectedValues[0]);
                alert(selected ? `Selezionato: ${selected.text}` : "Nessuna selezione");
                break;

            case "confirm":
                alert(`Confermato: ${this._selectedValues.join(", ") || "nessuna selezione"}`);
                break;

            case "stats":
                alert(`Elementi selezionati: ${this._selectedValues.length}`);
                break;
        }

        this.logEvent(`Action: ${action}`);
    }

    emitChange() {
        this.dispatchEvent(
            new CustomEvent("select-change", {
                detail: {
                    value: this.hasAttribute("multiple") ? this._selectedValues : this._selectedValues[0],
                    selectedValues: this._selectedValues,
                },
                bubbles: true,
                composed: true,
            }),
        );

        this.logEvent(`Selezionato: ${this._selectedValues.join(", ")}`);
    }

    logEvent(message) {
        this.dispatchEvent(
            new CustomEvent("select-log", {
                detail: { message },
            }),
        );
    }

    // Metodo pubblico per ottenere il valore
    getValue() {
        return this.hasAttribute("multiple") ? this._selectedValues : this._selectedValues[0];
    }
}

customElements.define("custom-select", CustomSelect);
