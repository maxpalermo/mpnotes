class MpNoteAttachmentsDialog extends HTMLElement {
    static get observedAttributes() {
        return ["endpoint", "action", "table-id"];
    }

    constructor() {
        super();
        this._orderId = this.getAttribute("id-order") || "";
        this._endpoint = this.getAttribute("endpoint") || "";
        this._action = this.getAttribute("action") || "addAttachment";
        this._tableId = this.getAttribute("table-id") || "";

        this._dialog = null;
        this._form = null;
        this._btnSave = null;
        this._btnClose = null;
        this._fileInput = null;
        this._previews = null;

        this._bound = false;
        this._payload = {};
        this._files = [];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (oldValue === newValue) return;
        if (name === "endpoint") this._endpoint = newValue || "";
        if (name === "action") this._action = newValue || "addAttachment";
        if (name === "table-id") this._tableId = newValue || "";
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

    get action() {
        return this._action;
    }

    set action(value) {
        this._action = value || "addAttachment";
        this.setAttribute("action", this._action);
    }

    get tableId() {
        return this._tableId;
    }

    set tableId(value) {
        this._tableId = value || "";
        this.setAttribute("table-id", this._tableId);
    }

    _ensureDialog() {
        if (this._dialog) return;

        const dialog = document.createElement("dialog");
        dialog.className = "mpnote-attachments-dialog";
        dialog.innerHTML = `
            <style>
                dialog.mpnote-attachments-dialog {
                    border: none;
                    padding: 0;
                    width: min(920px, calc(100vw - 32px));
                    max-width: 920px;
                    border-radius: 6px;
                    z-index: 99999;
                }

                dialog.mpnote-attachments-dialog::backdrop {
                    background: rgba(0, 0, 0, 0.5);
                }

                .card {
                    border-radius: 6px;
                    overflow: hidden;
                }

                .header {
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

                .body {
                    padding: 16px;
                    background: #fff;
                }

                .meta {
                    display: grid;
                    grid-template-columns: 1fr 1fr 1fr;
                    gap: 16px;
                    margin-bottom: 12px;
                }

                .meta .form-control[readonly] {
                    background: #f5f5f5;
                }

                .previews {
                    display: grid;
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                    gap: 8px;
                    max-height: calc(84px * 3 + 8px * 2);
                    overflow-y: auto;
                    padding: 2px;
                    margin-top: 12px;
                }

                .tile {
                    position: relative;
                    height: 84px;
                    border: 1px solid rgba(0,0,0,0.15);
                    border-radius: 6px;
                    overflow: hidden;
                    background: #fff;
                }

                .thumb {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                    display: block;
                }

                .fileIcon {
                    width: 100%;
                    height: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial;
                    font-weight: 700;
                    letter-spacing: .4px;
                    color: #303030;
                    background: linear-gradient(180deg,#fafafa,#f0f0f0);
                }

                .badge {
                    padding: 6px 8px;
                    border-radius: 6px;
                    border: 1px solid rgba(0,0,0,0.12);
                    background: #fff;
                    color: #303030;
                    font-size: 12px;
                }

                .title {
                    position: absolute;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    padding: 6px 8px;
                    font-size: 12px;
                    color: #fff;
                    background: rgba(0,0,0,0.75);
                    transform: translateY(100%);
                    transition: transform 150ms ease;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .tile:hover .title { transform: translateY(0); }

                .remove {
                    position: absolute;
                    top: 6px;
                    right: 6px;
                    width: 22px;
                    height: 22px;
                    border-radius: 50%;
                    border: none;
                    background: rgba(0,0,0,.70);
                    color: #fff;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 18px;
                    line-height: 18px;
                    padding: 0;
                }

                .footer {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 14px 16px;
                    border-top: 1px solid #e5e5e5;
                    background: #fff;
                }

                .actions {
                    display: flex;
                    gap: 10px;
                }
            </style>

            <div class="card">
                <div class="header">
                    <span class="material-icons">attachment</span>
                    <span>Allegati</span>
                </div>

                <div class="body">
                    <form id="mpnote-attachments-form" method="post">
                        <input type="hidden" name="DialogMpNoteAttachment-Type" id="mpnote-attachments-type" value="">
                        <input type="hidden" name="DialogMpNoteAttachment-NoteId" id="mpnote-attachments-note-id" value="">
                        <input type="hidden" name="DialogMpNoteAttachment-OrderId" id="mpnote-attachments-order-id" value="">
                        <input type="hidden" name="DialogMpNoteAttachment-CustomerId" id="mpnote-attachments-customer-id" value="">

                        <div class="meta">
                            <div class="form-group">
                                <label for="mpnote-attachments-order-id-ro">Id Ordine</label>
                                <input type="text" class="form-control text-center" id="mpnote-attachments-order-id-ro" value="--" readonly>
                            </div>
                            <div class="form-group">
                                <label for="mpnote-attachments-note-id-ro">Id Nota</label>
                                <input type="text" class="form-control text-center" id="mpnote-attachments-note-id-ro" value="--" readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="mpnote-attachments-files">Seleziona file</label>
                            <input type="file" class="form-control" id="mpnote-attachments-files" name="attachments[]" multiple>
                        </div>

                        <div class="previews" id="mpnote-attachments-previews"></div>
                    </form>
                </div>

                <div class="footer">
                    <div class="actions">
                        <button type="button" class="btn btn-primary" id="mpnote-attachments-btn-save">
                            <i class="material-icons mr-2">cloud_upload</i>
                            <span>Carica</span>
                        </button>
                        <button type="button" class="btn btn-secondary" id="mpnote-attachments-btn-close">
                            <i class="material-icons mr-2">close</i>
                            <span>Chiudi</span>
                        </button>
                    </div>
                    <div></div>
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
        this._form = dialog.querySelector("#mpnote-attachments-form");
        this._btnSave = dialog.querySelector("#mpnote-attachments-btn-save");
        this._btnClose = dialog.querySelector("#mpnote-attachments-btn-close");
        this._fileInput = dialog.querySelector("#mpnote-attachments-files");
        this._previews = dialog.querySelector("#mpnote-attachments-previews");
    }

    _bindOnce() {
        if (this._bound) return;
        this._bound = true;

        this._btnClose.addEventListener("click", () => {
            this.close();
        });

        this._btnSave.addEventListener("click", async (e) => {
            e.preventDefault();
            await this.save();
        });

        this._fileInput.addEventListener("change", () => {
            const list = Array.from(this._fileInput.files || []);
            this._files = list;
            this._renderPreviews();
        });

        this._previews.addEventListener("click", (e) => {
            const btn = e.target.closest("button[data-action='remove']");
            if (!btn) return;
            const idx = Number(btn.dataset.index);
            if (!Number.isFinite(idx)) return;

            this._files = this._files.filter((_, i) => i !== idx);
            this._syncInputFiles();
            this._renderPreviews();
        });

        this._dialog.addEventListener("close", () => {
            this.dispatchEvent(
                new CustomEvent("mpnote-attachments:closed", {
                    bubbles: true,
                    composed: true,
                }),
            );
        });
    }

    open(payload = {}) {
        this._ensureDialog();
        this._payload = payload || {};

        const setValue = (selector, value) => {
            const el = this._dialog.querySelector(selector);
            if (!el) return;
            el.value = value;
        };

        setValue("#mpnote-attachments-type", payload.type ?? "");
        setValue("#mpnote-attachments-note-id", payload.noteId ?? 0);
        setValue("#mpnote-attachments-order-id", payload.orderId ?? 0);
        setValue("#mpnote-attachments-customer-id", payload.customerId ?? 0);

        setValue("#mpnote-attachments-order-id-ro", payload.orderId ?? "--");
        setValue("#mpnote-attachments-customer-name", payload.customerName ?? "--");
        setValue("#mpnote-attachments-note-id-ro", payload.noteId ?? "--");

        if (payload.endpoint) this._endpoint = payload.endpoint;
        if (payload.action) this._action = payload.action;
        if (payload.tableId) this._tableId = payload.tableId;

        this._files = [];
        if (this._fileInput) this._fileInput.value = "";
        this._renderPreviews();

        this._dialog.showModal();
        if (this._fileInput) this._fileInput.focus();
    }

    close() {
        if (this._dialog?.open) {
            this._dialog.close();
        }
    }

    async save() {
        if (!this._endpoint) {
            throw new Error("mpnote-attachments-dialog: endpoint is required");
        }

        if (!this._files.length) {
            this.dispatchEvent(
                new CustomEvent("mpnote-attachments:error", {
                    bubbles: true,
                    composed: true,
                    detail: { message: "Nessun file selezionato" },
                }),
            );
            return { success: false, message: "Nessun file selezionato" };
        }

        const formData = new FormData(this._form);
        formData.append("ajax", 1);
        formData.append("action", this._action);
        formData.append("id_order", this._orderId);

        this._btnSave.disabled = true;
        const prevHtml = this._btnSave.innerHTML;
        this._btnSave.innerHTML = '<i class="material-icons mr-2">hourglass_empty</i><span>Caricamento...</span>';

        try {
            const response = await fetch(this._endpoint, {
                method: "POST",
                body: formData,
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const contentType = response.headers.get("content-type") || "";
            const data = contentType.includes("application/json") ? await response.json() : await response.text();

            this.dispatchEvent(
                new CustomEvent("mpnote-attachments:saved", {
                    detail: data,
                    bubbles: true,
                    composed: true,
                }),
            );

            if (data?.success === false) {
                return data;
            }

            this._refreshTable();
            this.close();
            return data;
        } finally {
            this._btnSave.disabled = false;
            this._btnSave.innerHTML = prevHtml;
        }
    }

    _refreshTable() {
        const id = this._tableId || this._payload?.tableId;
        if (!id) return;
        const table = document.getElementById(id);
        if (!table) return;

        table.dispatchEvent(new CustomEvent("mpnote:refresh"));
        if (typeof window.$ === "function") {
            try {
                $(table).bootstrapTable("refresh", { silent: true });
            } catch {}
        }
    }

    _renderPreviews() {
        if (!this._previews) return;

        if (!this._files.length) {
            this._previews.innerHTML = "";
            return;
        }

        const html = this._files
            .map((file, idx) => {
                const name = file?.name || "";
                const ext = this._getExt(name);
                const isImage = (file?.type || "").startsWith("image/") || this._isImageExt(ext);
                const title = this._escapeHtml(name);

                let body = `
                    <div class="fileIcon" aria-hidden="true">
                        <span class="badge">${this._escapeHtml((ext || "file").toUpperCase())}</span>
                    </div>
                `;

                if (isImage) {
                    const url = URL.createObjectURL(file);
                    body = `<img class="thumb" alt="" src="${this._escapeAttr(url)}" />`;
                }

                return `
                    <div class="tile" role="group" aria-label="${title}">
                        ${body}
                        <span class="title">${title}</span>
                        <button class="remove" type="button" data-action="remove" data-index="${idx}" aria-label="Rimuovi ${title}" title="Rimuovi">&times;</button>
                    </div>
                `;
            })
            .join("");

        this._previews.innerHTML = html;
    }

    _syncInputFiles() {
        if (!this._fileInput) return;
        const dt = new DataTransfer();
        this._files.forEach((f) => dt.items.add(f));
        this._fileInput.files = dt.files;
    }

    _getExt(value) {
        if (!value) return "";
        const clean = value.split("?")[0].split("#")[0];
        const idx = clean.lastIndexOf(".");
        if (idx === -1) return "";
        return clean.slice(idx + 1).toLowerCase();
    }

    _isImageExt(ext) {
        return new Set(["png", "jpg", "jpeg", "gif", "webp", "bmp", "svg"]).has(ext);
    }

    _escapeHtml(str) {
        return String(str).replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;").replaceAll("'", "&#039;");
    }

    _escapeAttr(str) {
        return this._escapeHtml(str);
    }
}

if (!customElements.get("mpnote-attachments-dialog")) {
    customElements.define("mpnote-attachments-dialog", MpNoteAttachmentsDialog);
}
