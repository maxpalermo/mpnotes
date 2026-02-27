class GridAttachment extends HTMLElement {
    static observedAttributes = ["endpoint", "idnote", "files", "add-action", "delete-action", "open-mode"];
    static showDeleteButton = false;

    constructor() {
        super();
        this.attachShadow({ mode: "open" });
        this._endpoint = "";
        this._idNote = 0;
        this._files = [];
        this._addAction = "addAttachment";
        this._deleteAction = "deleteAttachment";
        this._openMode = "new-tab";
        this._onClick = this._onClick.bind(this);
        this._onKeyDown = this._onKeyDown.bind(this);
    }

    connectedCallback() {
        this._syncFromAttributes();
        this._render();
        this.shadowRoot.addEventListener("click", this._onClick);
        this.shadowRoot.addEventListener("keydown", this._onKeyDown);
    }

    disconnectedCallback() {
        this.shadowRoot?.removeEventListener("click", this._onClick);
        this.shadowRoot?.removeEventListener("keydown", this._onKeyDown);
    }

    attributeChangedCallback() {
        this._syncFromAttributes();
        this._render();
    }

    set files(value) {
        this._files = Array.isArray(value) ? value : [];
        this._render();
    }

    get files() {
        return this._files;
    }

    set endpoint(value) {
        this._endpoint = typeof value === "string" ? value : "";
        if (this.getAttribute("endpoint") !== this._endpoint) {
            this.setAttribute("endpoint", this._endpoint);
        }
        this._render();
    }

    get endpoint() {
        return this._endpoint;
    }

    _syncFromAttributes() {
        const endpoint = this.getAttribute("endpoint");
        if (endpoint != null) this._endpoint = endpoint;

        const idNote = this.getAttribute("idnote");
        if (idNote != null) this._idNote = parseInt(idNote, 10) || 0;

        const addAction = this.getAttribute("add-action");
        if (addAction != null) this._addAction = addAction;

        const deleteAction = this.getAttribute("delete-action");
        if (deleteAction != null) this._deleteAction = deleteAction;

        const openMode = this.getAttribute("open-mode");
        if (openMode != null) this._openMode = openMode;

        const filesAttr = this.getAttribute("files");
        if (filesAttr != null) {
            this._files = this._parseFiles(filesAttr);
            console.table(this._files);
        }
    }

    _triggerAdd() {
        const dlg = document.getElementById("mpnote-attachments-dialog");
        if (dlg && typeof dlg.open === "function") {
            dlg.open({
                endpoint: this._endpoint,
                action: this._addAction,
                noteId: this._idNote,
            });
        }

        this.dispatchEvent(
            new CustomEvent("grid-attachment:add", {
                bubbles: true,
                composed: true,
                detail: { noteId: this._idNote, endpoint: this._endpoint, action: this._addAction },
            }),
        );
    }

    _parseFiles(filesAttr) {
        if (!filesAttr) return [];
        try {
            const parsed = JSON.parse(filesAttr);
            return Array.isArray(parsed) ? parsed : [];
        } catch {
            try {
                const decoded = atob(filesAttr);
                const parsed = JSON.parse(decoded);
                return Array.isArray(parsed) ? parsed : [];
            } catch {
                return [];
            }
        }
    }

    _render() {
        if (!this.shadowRoot) return;

        const files = Array.isArray(this._files) ? this._files : [];
        const itemsHtml = files
            .map((f, idx) => {
                const id = f?.id ?? f?.id_attachment ?? f?.idAttachment ?? idx;
                const title = (f?.title ?? f?.name ?? f?.filename ?? "").toString();
                const url = (f?.url ?? f?.path ?? f?.src ?? "").toString();
                const ext = this._getExt(title || url);
                const kind = this._getKind(ext, f?.mime);
                const preview = this._renderPreview(kind, url, ext);

                const safeTitle = this._escapeHtml(title);

                if (this.showDeleteButton) {
                    return `
                    <div class="tile" role="group" aria-label="${safeTitle}">
                        <button class="open" type="button" data-action="open" data-id="${this._escapeAttr(id)}" data-url="${this._escapeAttr(url)}" data-title="${this._escapeAttr(title)}" data-ext="${this._escapeAttr(ext)}" aria-label="Apri allegato: ${safeTitle}">
                            ${preview}
                            <span class="title" title="${safeTitle}">${safeTitle}</span>
                        </button>
                        <button class="delete" type="button" data-action="delete" data-id="${this._escapeAttr(id)}" data-url="${this._escapeAttr(url)}" data-title="${this._escapeAttr(title)}" aria-label="Elimina allegato: ${safeTitle}" title="Elimina">&times;</button>
                    </div>
                `;
                } else {
                    return `
                    <div class="tile" role="group" aria-label="${safeTitle}">
                        <button class="open" type="button" data-action="open" data-id="${this._escapeAttr(id)}" data-url="${this._escapeAttr(url)}" data-title="${this._escapeAttr(title)}" data-ext="${this._escapeAttr(ext)}" aria-label="Apri allegato: ${safeTitle}">
                            ${preview}
                            <span class="title" title="${safeTitle}">${safeTitle}</span>
                        </button>
                    </div>
                `;
                }
            })
            .join("");

        this.shadowRoot.innerHTML = `
            <style>
                :host{display:block;}
                .wrap{width:100%;}
                .grid{
                    display:grid;
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                    grid-auto-rows: 84px;
                    gap: 8px;
                    max-height: calc(84px * 3 + 8px * 2);
                    overflow-y: auto;
                    padding: 2px;
                }
                .tile{position:relative;}
                .open{
                    width:100%;
                    height:100%;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    border:1px solid rgba(0,0,0,0.15);
                    border-radius:6px;
                    background:#fff;
                    padding:0;
                    cursor:pointer;
                    position:relative;
                    overflow:hidden;
                }
                .open:focus{outline:2px solid rgba(37,185,215,.5); outline-offset:2px;}
                .delete{
                    position:absolute;
                    top:6px;
                    right:6px;
                    width:22px;
                    height:22px;
                    border-radius:50%;
                    border:none;
                    background:rgba(0,0,0,.70);
                    color:#fff;
                    cursor:pointer;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    font-size:18px;
                    line-height:18px;
                    padding:0;
                }
                .delete[disabled]{opacity:.5; cursor:default;}
                .thumb{
                    width:100%;
                    height:100%;
                    object-fit:cover;
                    display:block;
                }
                .fileIcon{
                    width:100%;
                    height:100%;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial;
                    font-weight:700;
                    letter-spacing:.4px;
                    color:#303030;
                    background:linear-gradient(180deg,#fafafa,#f0f0f0);
                }
                .badge{
                    padding:6px 8px;
                    border-radius:6px;
                    border:1px solid rgba(0,0,0,0.12);
                    background:#fff;
                    font-size:12px;
                }
                .title{
                    position:absolute;
                    left:0;
                    right:0;
                    bottom:0;
                    padding:6px 8px;
                    font-size:12px;
                    color:#fff;
                    background:rgba(0,0,0,0.75);
                    transform:translateY(100%);
                    transition:transform 150ms ease;
                    text-align:left;
                    white-space:nowrap;
                    overflow:hidden;
                    text-overflow:ellipsis;
                }
                .tile:hover .title{transform:translateY(0);}
                .empty{
                    border:1px dashed rgba(0,0,0,0.2);
                    border-radius:6px;
                    padding:10px;
                    color:#666;
                    font-size:12px;
                    font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial;
                }
                .empty.add-attachment{
                    cursor:pointer;
                    background:#fff;
                }
                .empty.add-attachment:hover{
                    border-color: rgba(0,0,0,0.35);
                    color:#333;
                }
            </style>
            <div class="wrap">
                ${files.length ? `<div class="grid" part="grid">${itemsHtml}</div>` : `<button type="button" class="empty add-attachment" data-action="add">Nessun allegato</button>`}
            </div>
        `;
    }

    _renderPreview(kind, url, ext) {
        if (kind === "image" && url) {
            return `<img class="thumb" alt="" loading="lazy" src="${this._escapeAttr(url)}" />`;
        }

        const label = (ext || kind || "file").toUpperCase();
        return `
            <div class="fileIcon" aria-hidden="true">
                <span class="badge">${this._escapeHtml(label)}</span>
            </div>
        `;
    }

    _getExt(value) {
        if (!value) return "";
        const clean = value.split("?")[0].split("#")[0];
        const idx = clean.lastIndexOf(".");
        if (idx === -1) return "";
        return clean.slice(idx + 1).toLowerCase();
    }

    _getKind(ext, mime) {
        const m = (mime || "").toLowerCase();
        if (m.startsWith("image/")) return "image";

        const imageExt = new Set(["png", "jpg", "jpeg", "gif", "webp", "bmp", "svg"]);
        if (imageExt.has(ext)) return "image";
        return "file";
    }

    async _onClick(e) {
        const btn = e.target.closest("button[data-action]");
        if (!btn) {
            const grid = e.target.closest(".grid");
            if (grid && !e.target.closest(".tile")) {
                this._triggerAdd();
            }
            return;
        }

        const action = btn.dataset.action;
        const id = btn.dataset.id;
        const url = btn.dataset.url;
        const title = btn.dataset.title;

        if (action === "add") {
            this._triggerAdd();
            return;
        }

        if (action === "open") {
            if (!url) return;
            if (this._openMode === "new-tab") {
                window.open(url, "_blank", "noopener");
            } else {
                window.open(url, "_blank", "noopener");
            }
            this.dispatchEvent(
                new CustomEvent("grid-attachment:open", {
                    bubbles: true,
                    composed: true,
                    detail: { id, url, title },
                }),
            );
            return;
        }

        if (action === "delete") {
            if (!this._endpoint) {
                this.dispatchEvent(
                    new CustomEvent("grid-attachment:error", {
                        bubbles: true,
                        composed: true,
                        detail: { message: "endpoint mancante", id, url, title },
                    }),
                );
                return;
            }

            const ok = window.confirm(`Eliminare l'allegato "${title || ""}"?`);
            if (!ok) return;

            btn.disabled = true;
            try {
                const res = await this._request(this._deleteAction, {
                    id_attachment: id,
                    idAttachment: id,
                    id: id,
                    url: url,
                });

                const next = (Array.isArray(this._files) ? this._files : []).filter((f, idx) => {
                    const fid = f?.id ?? f?.id_attachment ?? f?.idAttachment ?? idx;
                    return String(fid) !== String(id);
                });
                this._files = next;
                this._render();

                this.dispatchEvent(
                    new CustomEvent("grid-attachment:deleted", {
                        bubbles: true,
                        composed: true,
                        detail: { id, url, title, response: res },
                    }),
                );
            } catch (err) {
                btn.disabled = false;
                this.dispatchEvent(
                    new CustomEvent("grid-attachment:error", {
                        bubbles: true,
                        composed: true,
                        detail: { message: err?.message || String(err), id, url, title },
                    }),
                );
            }
        }
    }

    _onKeyDown(e) {
        if (e.key !== "Enter" && e.key !== " ") return;
        const target = e.target;
        if (!(target instanceof HTMLElement)) return;
        if (target.matches("button[data-action='open']")) {
            e.preventDefault();
            target.click();
        }
    }

    async _request(action, data) {
        const formData = new FormData();
        formData.append("ajax", 1);
        formData.append("action", action);
        Object.entries(data || {}).forEach(([k, v]) => {
            if (v === undefined || v === null) return;
            formData.append(k, v);
        });

        const request = await fetch(this._endpoint, {
            method: "POST",
            body: formData,
        });

        if (!request.ok) {
            throw new Error("grid-attachment: Network response was not ok");
        }

        const contentType = request.headers.get("content-type") || "";
        if (contentType.includes("application/json")) {
            return request.json();
        }
        return request.text();
    }

    _escapeHtml(str) {
        return String(str).replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;").replaceAll("'", "&#039;");
    }

    _escapeAttr(str) {
        return this._escapeHtml(str);
    }
}

if (!customElements.get("grid-attachment")) {
    customElements.define("grid-attachment", GridAttachment);
}
