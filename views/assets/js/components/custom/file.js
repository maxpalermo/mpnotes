class FileUpload extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: "open" });

        this.files = [];
        this.uploading = false;

        this.render();
    }

    static get observedAttributes() {
        return ["accept", "multiple", "max-size", "disabled"];
    }

    render() {
        const accept = this.getAttribute("accept") || "*/*";
        const multiple = this.hasAttribute("multiple");
        const label = this.getAttribute("label") || "Trascina qui i file o clicca per selezionare";
        const maxSize = this.getAttribute("max-size") || "10"; // MB

        this.shadowRoot.innerHTML = `
                    <style>
                        :host {
                            display: block;
                            font-family: inherit;
                        }
                        
                        .upload-container {
                            border: 2px dashed #17a2b8;
                            border-radius: 8px;
                            padding: 20px;
                            text-align: center;
                            background: #f8f9fa;
                            transition: all 0.3s;
                            cursor: pointer;
                        }
                        
                        .upload-container.dragover {
                            background: #d1ecf1;
                            border-color: #0c7b8b;
                            transform: scale(1.02);
                        }
                        
                        .upload-container.disabled {
                            opacity: 0.5;
                            cursor: not-allowed;
                            pointer-events: none;
                            background: #e9ecef;
                        }
                        
                        .upload-icon {
                            font-size: 48px;
                            color: #17a2b8;
                            margin-bottom: 10px;
                        }
                        
                        .upload-label {
                            color: #495057;
                            margin-bottom: 5px;
                        }
                        
                        .upload-hint {
                            font-size: 12px;
                            color: #6c757d;
                        }
                        
                        .preview-grid {
                            display: grid;
                            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                            gap: 10px;
                            margin-top: 20px;
                        }
                        
                        .preview-item {
                            position: relative;
                            border: 1px solid #dee2e6;
                            border-radius: 4px;
                            overflow: hidden;
                            background: white;
                        }
                        
                        .preview-item.image {
                            aspect-ratio: 1;
                        }
                        
                        .preview-item.document {
                            padding: 15px 5px;
                            text-align: center;
                            background: #f8f9fa;
                        }
                        
                        .preview-item img {
                            width: 100%;
                            height: 100%;
                            object-fit: cover;
                        }
                        
                        .file-icon {
                            font-size: 32px;
                            color: #17a2b8;
                        }
                        
                        .file-name {
                            font-size: 11px;
                            padding: 5px;
                            word-break: break-word;
                            background: white;
                        }
                        
                        .file-size {
                            font-size: 10px;
                            color: #6c757d;
                            padding: 0 5px 5px;
                        }
                        
                        .remove-btn {
                            position: absolute;
                            top: 2px;
                            right: 2px;
                            width: 20px;
                            height: 20px;
                            background: rgba(220, 53, 69, 0.9);
                            color: white;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            cursor: pointer;
                            font-size: 14px;
                            font-weight: bold;
                            transition: background 0.2s;
                            z-index: 10;
                        }
                        
                        .remove-btn:hover {
                            background: #dc3545;
                        }
                        
                        .progress-bar {
                            height: 4px;
                            background: #e9ecef;
                            border-radius: 2px;
                            margin-top: 15px;
                            overflow: hidden;
                        }
                        
                        .progress-fill {
                            height: 100%;
                            background: #28a745;
                            width: 0%;
                            transition: width 0.3s;
                        }
                        
                        .file-info {
                            display: flex;
                            justify-content: space-between;
                            margin-top: 10px;
                            font-size: 12px;
                            color: #495057;
                        }
                        
                        input {
                            display: none;
                        }
                    </style>
                    
                    <input type="file" id="fileInput" ${multiple ? "multiple" : ""} accept="${accept}">
            
            <div class="upload-container" id="dropZone">
                <div class="upload-icon">📁</div>
                <div class="upload-label">${label}</div>
                <div class="upload-hint">Max ${maxSize}MB per file</div>
            </div>
            
            <div class="preview-grid" id="previewGrid"></div>
            
            <div class="progress-bar" id="progressBar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            
            <div class="file-info" id="fileInfo">
                <span>0 file selezionati</span>
                <span>0 MB totali</span>
            </div>
        `;

        this.fileInput = this.shadowRoot.getElementById("fileInput");
        this.dropZone = this.shadowRoot.getElementById("dropZone");
        this.previewGrid = this.shadowRoot.getElementById("previewGrid");
        this.progressFill = this.shadowRoot.getElementById("progressFill");
        this.fileInfo = this.shadowRoot.getElementById("fileInfo");

        this.attachEvents();
    }

    attachEvents() {
        // Click sulla drop zone
        this.dropZone.addEventListener("click", () => {
            if (!this.hasAttribute("disabled")) {
                this.fileInput.click();
            }
        });

        // Change dell'input file
        this.fileInput.addEventListener("change", (e) => {
            this.handleFiles(Array.from(e.target.files));
        });

        // Drag & drop events
        this.dropZone.addEventListener("dragover", (e) => {
            e.preventDefault();
            if (!this.hasAttribute("disabled")) {
                this.dropZone.classList.add("dragover");
            }
        });

        this.dropZone.addEventListener("dragleave", () => {
            this.dropZone.classList.remove("dragover");
        });

        this.dropZone.addEventListener("drop", (e) => {
            e.preventDefault();
            this.dropZone.classList.remove("dragover");

            if (!this.hasAttribute("disabled")) {
                const files = Array.from(e.dataTransfer.files);
                this.handleFiles(files);
            }
        });
    }

    handleFiles(newFiles) {
        const maxSize = parseFloat(this.getAttribute("max-size") || "10") * 1024 * 1024; // MB to bytes
        const accept = this.getAttribute("accept") || "*/*";

        // Filtra per tipo e dimensione
        const validFiles = newFiles.filter((file) => {
            // Controllo dimensione
            if (file.size > maxSize) {
                this.emitEvent("file-error", {
                    file: file.name,
                    error: `File troppo grande (max ${this.getAttribute("max-size")}MB)`,
                });
                return false;
            }

            // Controllo tipo (semplificato)
            if (accept !== "*/*") {
                const acceptedTypes = accept.split(",").map((t) => t.trim());
                const fileType = file.type || file.name.split(".").pop();

                const isValid = acceptedTypes.some((type) => {
                    if (type.startsWith(".")) {
                        return file.name.toLowerCase().endsWith(type.toLowerCase());
                    }
                    if (type.endsWith("/*")) {
                        const mainType = type.replace("/*", "");
                        return file.type.startsWith(mainType);
                    }
                    return file.type === type || file.name.endsWith(type);
                });

                if (!isValid) {
                    this.emitEvent("file-error", {
                        file: file.name,
                        error: `Tipo file non supportato`,
                    });
                    return false;
                }
            }

            return true;
        });

        if (this.hasAttribute("multiple")) {
            this.files = [...this.files, ...validFiles];
        } else {
            this.files = validFiles.slice(0, 1);
        }

        this.updatePreview();
        this.emitEvent("files-selected", { count: this.files.length });
    }

    updatePreview() {
        this.previewGrid.innerHTML = "";
        let totalSize = 0;

        this.files.forEach((file, index) => {
            totalSize += file.size;

            const previewItem = document.createElement("div");
            previewItem.className = "preview-item";

            if (file.type.startsWith("image/")) {
                previewItem.classList.add("image");
                const img = document.createElement("img");
                img.src = URL.createObjectURL(file);
                previewItem.appendChild(img);
            } else {
                previewItem.classList.add("document");
                previewItem.innerHTML = `
                            <div class="file-icon">📄</div>
                            <div class="file-name">${file.name}</div>
                            <div class="file-size">${(file.size / 1024).toFixed(1)} KB</div>
                        `;
            }

            // Pulsante rimuovi
            const removeBtn = document.createElement("div");
            removeBtn.className = "remove-btn";
            removeBtn.textContent = "×";
            removeBtn.onclick = (e) => {
                e.stopPropagation();
                this.removeFile(index);
            };

            previewItem.appendChild(removeBtn);
            this.previewGrid.appendChild(previewItem);
        });

        // Aggiorna info
        this.fileInfo.innerHTML = `
                    <span>${this.files.length} file selezionati</span>
                    <span>${(totalSize / 1024 / 1024).toFixed(2)} MB totali</span>
                `;

        // Emetti evento con le info
        this.emitEvent("preview-updated", {
            count: this.files.length,
            totalSize: totalSize,
        });
    }

    removeFile(index) {
        this.files.splice(index, 1);
        this.updatePreview();
        this.emitEvent("file-removed", { remainingCount: this.files.length });
    }

    clearFiles() {
        this.files = [];
        this.fileInput.value = "";
        this.updatePreview();
        this.emitEvent("files-cleared");
    }

    async upload(endpoint, extraParams = {}) {
        if (this.files.length === 0) {
            this.emitEvent("upload-error", { error: "Nessun file selezionato" });
            return;
        }

        this.uploading = true;
        this.emitEvent("upload-start", { fileCount: this.files.length });

        const formData = new FormData();

        // Aggiungi file
        this.files.forEach((file, index) => {
            formData.append(`file_${index}`, file);
        });

        // Aggiungi parametri extra
        Object.keys(extraParams).forEach((key) => {
            formData.append(key, extraParams[key]);
        });

        // Aggiungi data attributes come parametri
        for (let key in this.dataset) {
            formData.append(`data_${key}`, this.dataset[key]);
        }

        try {
            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener("progress", (e) => {
                if (e.lengthComputable) {
                    const percent = (e.loaded / e.total) * 100;
                    this.progressFill.style.width = percent + "%";
                    this.emitEvent("upload-progress", { percent, loaded: e.loaded, total: e.total });
                }
            });

            const response = await new Promise((resolve, reject) => {
                xhr.open("POST", endpoint || this.getAttribute("endpoint"));

                xhr.onload = () => {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            resolve(JSON.parse(xhr.responseText));
                        } catch {
                            resolve(xhr.responseText);
                        }
                    } else {
                        reject(new Error(`HTTP ${xhr.status}`));
                    }
                };

                xhr.onerror = () => reject(new Error("Network error"));
                xhr.send(formData);
            });

            this.emitEvent("upload-success", { response });

            // Opzionale: pulisci dopo upload
            if (this.hasAttribute("clear-after-upload")) {
                this.clearFiles();
            }

            return response;
        } catch (error) {
            this.emitEvent("upload-error", { error: error.message });
            throw error;
        } finally {
            this.uploading = false;
            setTimeout(() => {
                this.progressFill.style.width = "0%";
            }, 1000);
        }
    }

    emitEvent(type, detail) {
        this.dispatchEvent(
            new CustomEvent(type, {
                detail: { ...detail, timestamp: new Date().toISOString() },
                bubbles: true,
                composed: true,
            }),
        );

        // Log per debug
        this.logEvent(type, detail);
    }

    logEvent(type, data) {
        this.dispatchEvent(
            new CustomEvent("upload-log", {
                detail: { type, data, timestamp: new Date().toLocaleTimeString() },
            }),
        );
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (oldValue === newValue) return;

        if (name === "disabled") {
            if (newValue !== null) {
                this.dropZone.classList.add("disabled");
            } else {
                this.dropZone.classList.remove("disabled");
            }
        }
    }
}

customElements.define("file-upload", FileUpload);
