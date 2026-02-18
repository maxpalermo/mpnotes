class MpNoteDialog extends HTMLElement {
    static get observedAttributes() {
        return ["endpoint"];
    }

    constructor() {
        super();
        this._endpoint = this.getAttribute("endpoint") || "";
        this._dialog = null;
        this._form = null;
        this._btnSave = null;
        this._btnClose = null;
        this._bound = false;
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (oldValue === newValue) return;
        if (name === "endpoint") {
            this._endpoint = newValue || "";
        }
    }

    connectedCallback() {
        this._ensureDialog();
        this._bindOnce();
    }

    get endpoint() {
        return this._endpoint;
    }

    set endpoint(value) {
        this._endpoint = value || "";
        this.setAttribute("endpoint", this._endpoint);
    }

    _ensureDialog() {
        if (this._dialog) {
            return;
        }

        const dialog = document.createElement("dialog");
        dialog.className = "mpnote-dialog";

        dialog.innerHTML = `
            <style>
                dialog.mpnote-dialog {
                    border: none;
                    padding: 0;
                    width: min(920px, calc(100vw - 32px));
                    max-width: 920px;
                    border-radius: 6px;
                    z-index: 99999;
                }

                dialog.mpnote-dialog::backdrop {
                    background: rgba(0, 0, 0, 0.5);
                }

                .mpnote-card {
                    border-radius: 6px;
                    overflow: hidden;
                }

                .mpnote-header {
                    background: #25b9d7;
                    color: #fff;
                    padding: 14px 16px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 20px;
                    font-weight: 600;
                    gap: 10px;
                }

                .mpnote-body {
                    padding: 16px;
                    background: #fff;
                }

                .mpnote-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr 1fr;
                    gap: 16px;
                }

                .mpnote-grid .form-control[readonly] {
                    background: #f5f5f5;
                }

                .mpnote-message {
                    margin-top: 12px;
                }

                .mpnote-footer {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 14px 16px;
                    border-top: 1px solid #e5e5e5;
                    background: #fff;
                }

                .mpnote-actions {
                    display: flex;
                    gap: 10px;
                }

                .mpnote-switches {
                    display: flex;
                    gap: 36px;
                    align-items: center;
                }

                .mpnote-switches .ps-switch-container {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    gap: 6px;
                }
            </style>

            <div class="mpnote-card">
                <div class="mpnote-header">
                    <span class="material-icons">note</span>
                    <span>Nota</span>
                </div>

                <div class="mpnote-body">
                    <form id="mpnote-dialog-form" method="post">
                        <input type="hidden" name="DialogMpNote-Type" id="mpnote-type" value="">
                        <input type="hidden" name="DialogMpNote-Id" id="mpnote-id" value="">
                        <input type="hidden" name="DialogMpNote-CustomerId" id="mpnote-customer-id" value="">
                        <input type="hidden" name="DialogMpNote-EmployeeId" id="mpnote-employee-id" value="">

                        <div class="mpnote-grid">
                            <div class="form-group">
                                <label for="mpnote-order-id">Id Ordine</label>
                                <input type="text" class="form-control text-center" name="DialogMpNote-OrderId" id="mpnote-order-id" value="--" readonly>
                            </div>

                            <div class="form-group">
                                <label for="mpnote-customer-name">Cliente</label>
                                <input type="text" class="form-control text-center" name="DialogMpNote-CustomerName" id="mpnote-customer-name" value="--" readonly>
                            </div>

                            <div class="form-group">
                                <label for="mpnote-employee-name">Operatore</label>
                                <input type="text" class="form-control text-center" name="DialogMpNote-EmployeeName" id="mpnote-employee-name" value="--" readonly>
                            </div>
                        </div>

                        <div class="form-group mpnote-message">
                            <label for="mpnote-content">Messaggio</label>
                            <textarea class="form-control" name="DialogMpNote-Content" id="mpnote-content" rows="4"></textarea>
                        </div>

                        <input type="hidden" name="DialogMpNote-Gravity" id="mpnote-gravity" value="info">

                        <div class="mpnote-footer">
                            <div class="mpnote-actions">
                                <button type="button" class="btn btn-primary" id="mpnote-btn-save">
                                    <i class="material-icons mr-2">save</i>
                                    <span>Salva</span>
                                </button>
                                <button type="button" class="btn btn-secondary" id="mpnote-btn-close">
                                    <i class="material-icons mr-2">close</i>
                                    <span>Chiudi</span>
                                </button>
                            </div>

                            <div class="mpnote-switches">
                                <div class="ps-switch-container">
                                    <label>Stampabile</label>
                                    <div class="ps-switch ps-switch-sm ps-togglable-row">
                                        <input type="radio" name="DialogMpNote-Printable" id="mpnote-printable-0" value="0" checked>
                                        <label for="mpnote-printable-0"></label>
                                        <input type="radio" name="DialogMpNote-Printable" id="mpnote-printable-1" value="1">
                                        <label for="mpnote-printable-1"></label>
                                        <span class="slide-button"></span>
                                    </div>
                                </div>
                                <div class="ps-switch-container">
                                    <label>Chat</label>
                                    <div class="ps-switch ps-switch-sm ps-togglable-row">
                                        <input type="radio" name="DialogMpNote-Chat" id="mpnote-chat-0" value="0" checked>
                                        <label for="mpnote-chat-0"></label>
                                        <input type="radio" name="DialogMpNote-Chat" id="mpnote-chat-1" value="1">
                                        <label for="mpnote-chat-1"></label>
                                        <span class="slide-button"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        `;

        dialog.addEventListener("cancel", (e) => {
            e.preventDefault();
            dialog.close();
        });

        dialog.addEventListener("click", (e) => {
            if (e.target === dialog) {
                dialog.close();
            }
        });

        document.body.appendChild(dialog);

        this._dialog = dialog;
        this._form = dialog.querySelector("#mpnote-dialog-form");
        this._btnSave = dialog.querySelector("#mpnote-btn-save");
        this._btnClose = dialog.querySelector("#mpnote-btn-close");
    }

    _bindOnce() {
        if (this._bound) {
            return;
        }
        this._bound = true;

        this._btnClose.addEventListener("click", () => {
            this.close();
        });

        this._btnSave.addEventListener("click", async (e) => {
            e.preventDefault();
            await this.save();
        });

        this._dialog.addEventListener("close", () => {
            this.dispatchEvent(
                new CustomEvent("mpnote:closed", {
                    bubbles: true,
                    composed: true,
                }),
            );
        });
    }

    open(payload = {}) {
        this._ensureDialog();

        const setValue = (selector, value) => {
            const el = this._dialog.querySelector(selector);
            if (!el) return;
            el.value = value;
        };

        setValue("#mpnote-type", payload.type ?? "");
        setValue("#mpnote-id", payload.noteId ?? 0);
        setValue("#mpnote-order-id", payload.orderId ?? "--");
        setValue("#mpnote-customer-id", payload.customerId ?? 0);
        setValue("#mpnote-customer-name", payload.customerName ?? "--");
        setValue("#mpnote-employee-id", payload.employeeId ?? 0);
        setValue("#mpnote-employee-name", payload.employeeName ?? "--");
        setValue("#mpnote-content", payload.content ?? "");

        const printable = Number(payload.printable ?? 0) === 1;
        const chat = Number(payload.chat ?? 0) === 1;

        const printable0 = this._dialog.querySelector("#mpnote-printable-0");
        const printable1 = this._dialog.querySelector("#mpnote-printable-1");
        if (printable0 && printable1) {
            printable1.checked = printable;
            printable0.checked = !printable;
        }

        const chat0 = this._dialog.querySelector("#mpnote-chat-0");
        const chat1 = this._dialog.querySelector("#mpnote-chat-1");
        if (chat0 && chat1) {
            chat1.checked = chat;
            chat0.checked = !chat;
        }

        this._dialog.showModal();

        const content = this._dialog.querySelector("#mpnote-content");
        if (content) {
            content.focus();
        }
    }

    close() {
        if (this._dialog?.open) {
            this._dialog.close();
        }
    }

    async save() {
        if (!this._endpoint) {
            throw new Error("mpnote-dialog: endpoint is required");
        }

        const formData = new FormData(this._form);
        formData.append("ajax", 1);
        formData.append("action", "updateNote");

        this._btnSave.disabled = true;
        const prevHtml = this._btnSave.innerHTML;
        this._btnSave.innerHTML = '<i class="material-icons mr-2">hourglass_empty</i><span>Salvataggio...</span>';

        try {
            const response = await fetch(this._endpoint, {
                method: "POST",
                body: formData,
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            this.dispatchEvent(
                new CustomEvent("mpnote:saved", {
                    detail: data,
                    bubbles: true,
                    composed: true,
                }),
            );

            if (data?.success) {
                this.close();
            }

            return data;
        } finally {
            this._btnSave.disabled = false;
            this._btnSave.innerHTML = prevHtml;
        }
    }
}

if (!customElements.get("mpnote-dialog")) {
    customElements.define("mpnote-dialog", MpNoteDialog);
}
