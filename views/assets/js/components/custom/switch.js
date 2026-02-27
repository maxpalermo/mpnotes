class CustomSwitch extends HTMLElement {
    static get observedAttributes() {
        return ["checked", "disabled", "size", "color"];
    }

    constructor() {
        super();
        this.attachShadow({ mode: "open" });

        this._checked = this.hasAttribute("checked");
        this._disabled = this.hasAttribute("disabled");

        this.render();
    }

    getSize() {
        const size = this.getAttribute("size") || "md";
        const sizes = {
            xs: { track: "16px", thumb: "12px", fontSize: "10px", gap: "4px" },
            sm: { track: "20px", thumb: "16px", fontSize: "11px", gap: "5px" },
            md: { track: "24px", thumb: "20px", fontSize: "12px", gap: "6px" },
            lg: { track: "28px", thumb: "24px", fontSize: "13px", gap: "7px" },
            xl: { track: "32px", thumb: "28px", fontSize: "14px", gap: "8px" },
            xxl: { track: "36px", thumb: "32px", fontSize: "15px", gap: "9px" },
        };
        return sizes[size] || sizes["md"];
    }

    getColor() {
        const color = this.getAttribute("color") || "primary";
        const colors = {
            primary: { checked: "#007bff", unchecked: "#6c757d" },
            secondary: { checked: "#6c757d", unchecked: "#adb5bd" },
            success: { checked: "#28a745", unchecked: "#6c757d" },
            danger: { checked: "#dc3545", unchecked: "#6c757d" },
            warning: { checked: "#ffc107", unchecked: "#6c757d" },
            info: { checked: "#17a2b8", unchecked: "#6c757d" },
            dark: { checked: "#343a40", unchecked: "#6c757d" },
        };
        return colors[color] || colors["primary"];
    }

    render() {
        const size = this.getSize();
        const colors = this.getColor();

        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    display: inline-block;
                }
                
                .switch-container {
                    display: flex;
                    align-items: center;
                    gap: ${size.gap};
                    cursor: ${this._disabled ? "not-allowed" : "pointer"};
                    opacity: ${this._disabled ? "0.65" : "1"};
                }
                
                .switch-track {
                    position: relative;
                    width: calc(${size.track} * 1.8);
                    height: ${size.track};
                    background-color: ${this._checked ? colors.checked : colors.unchecked};
                    border-radius: calc(${size.track} / 2);
                    transition: background-color 0.2s;
                    box-shadow: inset 0 1px 3px rgba(0,0,0,0.2);
                }
                
                .switch-thumb {
                    position: absolute;
                    top: 2px;
                    left: ${this._checked ? `calc(100% - ${size.thumb} - 2px)` : "2px"};
                    width: ${size.thumb};
                    height: ${size.thumb};
                    background-color: white;
                    border-radius: 50%;
                    transition: left 0.2s;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.3);
                }
                
                .label {
                    font-size: ${size.fontSize};
                    user-select: none;
                }
                
                .label ::slotted(*) {
                    display: inline-block;
                }
                
                input {
                    position: absolute;
                    opacity: 0;
                    width: 0;
                    height: 0;
                }
            </style>
            
            <div class="switch-container" id="container">
                <div class="switch-track" id="track">
                    <div class="switch-thumb" id="thumb"></div>
                </div>
                <div class="label" id="label">
                    ${this._checked ? this.getAttribute("label-on") || "ON" : this.getAttribute("label-off") || "OFF"}
                </div>
            </div>
        `;

        this.container = this.shadowRoot.getElementById("container");
        this.track = this.shadowRoot.getElementById("track");
        this.label = this.shadowRoot.getElementById("label");

        this.attachEvents();
    }

    attachEvents() {
        this.container.addEventListener("click", (e) => {
            e.stopPropagation();
            if (!this._disabled) {
                this.toggle();
            }
        });
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (oldValue === newValue) return;

        switch (name) {
            case "checked":
                this._checked = newValue !== null;
                this.updateVisualState();
                break;

            case "disabled":
                this._disabled = newValue !== null;
                this.render();
                break;

            case "size":
            case "color":
                this.render();
                break;
        }
    }

    updateVisualState() {
        const size = this.getSize();
        const colors = this.getColor();

        if (this.track) {
            this.track.style.backgroundColor = this._checked ? colors.checked : colors.unchecked;

            const thumb = this.shadowRoot.getElementById("thumb");
            if (thumb) {
                thumb.style.left = this._checked ? `calc(100% - ${size.thumb} - 2px)` : "2px";
            }
        }

        if (this.label) {
            this.label.innerHTML = this._checked ? this.getAttribute("label-on") || "ON" : this.getAttribute("label-off") || "OFF";
        }

        this.emitChange();
    }

    toggle() {
        this._checked = !this._checked;
        this.updateVisualState();

        if (this._checked) {
            this.setAttribute("checked", "");
        } else {
            this.removeAttribute("checked");
        }
    }

    setChecked(value) {
        this._checked = value;
        this.updateVisualState();

        if (this._checked) {
            this.setAttribute("checked", "");
        } else {
            this.removeAttribute("checked");
        }
    }

    emitChange() {
        // Raccogli tutti i data attributes
        const dataAttributes = {};
        for (let key in this.dataset) {
            dataAttributes[key] = this.dataset[key];
        }

        const eventData = {
            checked: this._checked,
            value: this._checked ? this.getAttribute("value-on") : this.getAttribute("value-off"),
            dataAttributes: dataAttributes,
            timestamp: new Date().toISOString(),
        };

        // Evento personalizzato
        this.dispatchEvent(
            new CustomEvent("switch-change", {
                detail: eventData,
                bubbles: true,
                composed: true,
            }),
        );

        // Callback via attributo
        const callback = this.getAttribute("onchange-callback");
        if (callback && window[callback]) {
            window[callback](eventData);
        }

        // Log per debug
        this.logEvent(`Switch ${this._checked ? "ON" : "OFF"}`, dataAttributes);
    }

    logEvent(message, data = {}) {
        this.dispatchEvent(
            new CustomEvent("switch-log", {
                detail: { message, data, timestamp: new Date().toLocaleTimeString() },
            }),
        );
    }

    // Metodi pubblici
    get checked() {
        return this._checked;
    }

    set checked(value) {
        this.setChecked(value);
    }

    get value() {
        return this._checked ? this.getAttribute("value-on") || "on" : this.getAttribute("value-off") || "off";
    }

    set value(val) {
        // Valuta se il valore corrisponde a on o off
        const onValue = this.getAttribute("value-on") || "on";
        const offValue = this.getAttribute("value-off") || "off";

        if (val === onValue) {
            this.setChecked(true);
        } else if (val === offValue) {
            this.setChecked(false);
        }
    }
}

customElements.define("custom-switch", CustomSwitch);
