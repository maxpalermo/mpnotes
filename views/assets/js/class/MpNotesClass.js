class MpNotes {
    params = [];
    ajaxController = "";
    orderId = 0;
    customerId = 0;
    customerName = "";
    employeeId = 0;
    employeeName = "";
    endpoint = "";

    constructor(params) {
        this.params = params.mpNotes;

        console.log("MODULE MPNOTES: Class mpNotes constructor params - ", params);

        if (!params.adminControllerUrl) {
            throw new Error("MODULE MPNOTES: class MpNotes constructor: adminControllerUrl is required");
        }

        this.ajaxController = params.adminControllerUrl;
        this.orderId = params.orderId || 0;
        this.customerId = params.customerId || 0;
        this.customerName = params.customerName || "";
        this.employeeId = params.employeeId || 0;
        this.employeeName = params.employeeName || "";
        this.endpoint = params.endpoint || "";
    }

    getAdminControllerUrl() {
        return this.ajaxController;
    }

    getOrderId() {
        return this.orderId;
    }

    getCustomerId() {
        return this.customerId;
    }

    async fetch(action, data) {
        const response = await fetch(this.ajaxController, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
                "X-Requested-With": "XMLHttpRequest",
            },
            body: new URLSearchParams({
                ajax: 1,
                action: action,
                data: JSON.stringify(data),
            }),
        });

        if (!response.ok) {
            throw new Error("MPNOTES: fetch: Network response was not ok");
        }

        const result = await response.json();

        return result;
    }

    createNodeElement(html) {
        const template = document.createElement("template");
        template.innerHTML = html;

        const fragment = template.content.cloneNode(true);
        const node = fragment.querySelector(fragment.firstElementChild.tagName);

        return node;
    }

    getTemplateAlert() {
        const template = `
            <dialog id="mpnote-dialog-alert">
                <div class="card card-alert">
                    <div class="card-header">
                        <h3>
                            <slot name="icon"></slot>
                            <slot name="title"></slot>
                        </h3>
                    </div>
                    <div class="card-body">
                        <p>
                            <slot name="message"></slot>
                        </p>
                    </div>
                    <div class="card-footer d-flex justify-content-center align-items-center gap-2">
                        <button type="button" class="btn btn-primary yes">
                            <i class="material-icons mr-2">check</i>
                            <span>Sì</span>
                        </button>
                        <button type="button" class="btn btn-secondary no">
                            <i class="material-icons mr-2">close</i>
                            <span>No</span>
                        </button>
                        <button type="button" class="btn btn-warning cancel">
                            <i class="material-icons mr-2">exit_to_app</i>
                            <span>Chiudi</span>
                        </button>
                    </div>
                </div>
            </dialog>
        `;

        return template;
    }

    getTemplateNoteForm(type, noteId, orderId, customerId, customerName, employeeId, employeeName, content = "", printable = 0, chat = 0) {
        const template = `
            <div class="card">
                <div class="card-body">
                    <form id="mpnote-form-new-note" method="post">
                        <input type="hidden" name="type" value="${type}">
                        <input type="hidden" name="id" value="${noteId}">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <div class="form-group fixed-width-md">
                                <label for="MpNoteOrderId">Id Ordine</label>
                                <input type="text" class="form-control text-center" name="MpNoteOrderId" placeholder="Id" value="${orderId}" readonly>
                            </div>
                            <div class="form-group">
                                <input type="hidden" name="MpNoteCustomerId" value="${customerId}">
                                <label for="MpNoteCustomerName">Cliente</label>
                                <input type="text" class="form-control text-center" name="MpNoteCustomerName" placeholder="Nome" value="${customerName}" readonly>
                            </div>
                            <div class="form-group">
                                <label for="MpNoteEmployee">Operatore</label>
                                <input type="hidden" name="MpNoteEmployeeId" value="${employeeId}">
                                <input type="text" class="form-control text-center" name="MpNoteEmployee" placeholder="Operatore" value="${employeeName}" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="MpNoteContent">Messaggio</label>
                            <textarea class="form-control" name="MpNoteContent" rows="3">${content}</textarea>
                        </div>
                        <div class="d-flex justify-content-end align-items-center gap-2">
                            <div class="ps-switch-container">
                                <label>Stampabile</label>
                                <div class="ps-switch ps-switch-sm ps-togglable-row">
                                    <input type="radio" name="MpNotePrintable" id="MpNotePrintable-0" value="0">
                                    <label for="MpNotePrintable-0"></label>
                                    <input type="radio" name="MpNotePrintable" id="MpNotePrintable-1" value="1" ${printable ? "checked" : ""}>
                                    <label for="MpNotePrintable-1"></label>
                                    <span class="slide-button"></span>
                                </div>
                            </div>
                            <div class="ps-switch-container">
                                <label>Chat</label>
                                <div class="ps-switch ps-switch-sm ps-togglable-row">
                                    <input type="radio" name="MpNoteChat" id="MpNoteChat-0" value="0">
                                    <label for="MpNoteChat-0"></label>
                                    <input type="radio" name="MpNoteChat" id="MpNoteChat-1" value="1" ${chat ? "checked" : ""}>
                                    <label for="MpNoteChat-1"></label>
                                    <span class="slide-button"></span>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary" id="MpNoteSubmitNote">
                                <i class="material-icons mr-2">save</i>
                                <span>Salva</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        `;

        return template;
    }

    getTemplateAttachmentCardContainer() {
        const template = `
            <div class="card">
                <div class="card-body">
                    <ul id="list-attachments" class="d-flex justify-content-start align-items-center gap-2" style="width: auto; overflow-x: auto;">
                        
                    </ul>
                </div>
            </div>
        `;

        return template;
    }

    getTemplateAttachmentListElement() {
        const template = `
            <li class="d-flex justify-content-between align-items-center">
                <div class="card d-flex justify-content-center align-items-center">
                    <img src="{url}" alt="test attachment" style="max-height: 200px;" onclick="javascript:mpNotes.magnifyImage(this);">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex justify-content-start align-items-center gap-2">
                                <i class="material-icons">attachment</i>
                                <span>{name}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </li>
        `;

        return template;
    }

    getTemplateAddAttachmentElement() {
        const self = this;
        const template = `
            <div class="card">
                <div class="card-body">
                    <form id="mpnote-form-add-attachment" enctype="multipart/form-data">
                        <file-upload id="file-add-attachment" endpoint="${self.endpoint}" multiple="true" data-sections="attachments" data-importance="high"></file-upload>
                    </form>
                </div>
            </div>
        `;

        return template;
    }

    getTemplateMagnifyImage() {
        const template = `
            <dialog id="dialog-magnify-image" class="d-flex justify-content-center align-items-center">
                <div class="card d-flex justify-content-center align-items-center">
                    <img src="{url}" alt="{image}" style="height: 80vh; max-height: 80vh;">
                </div>
            </dialog>
        `;

        return template;
    }

    getAlertElement() {
        const templateNode = this.createNodeElement(this.getTemplateAlert());

        return templateNode;
    }

    async showNewNote(title, type = "customer") {
        const existsElement = document.getElementById("mpnote-new-note");
        if (existsElement) {
            existsElement.remove();
        }

        const noteHtml = this.getTemplateNoteForm(type, 0, this.orderId, this.customerId, this.customerName, this.employeeId, this.employeeName, "", 0, 0);
        const noteNode = this.createNodeElement(noteHtml);

        this.showAlert(title, noteNode, "info");

        const textArea = document.getElementById("MpNoteContent");

        if (textArea) {
            textArea.focus();
        }

        this.bindSubmitNote();
    }

    async showViewNote(title, type = "customer", idNote = 0) {
        const response = await this.fetch("getNoteById", {
            idNote: idNote,
            idOrder: this.orderId,
            idEmployee: this.employeeId,
        });

        if (!response.success) {
            this.showAlert("Errore", response.message, "error");
            return false;
        }

        const note = response.note;

        const existsElement = document.getElementById("mpnote-view-note");
        if (existsElement) {
            existsElement.remove();
        }

        const noteHtml = this.getTemplateNoteForm(type, note.id, note.id_order, note.id_customer, note.customer_name, note.id_employee, note.employee_name, note.content, note.flags.printable, note.flags.chat);
        const noteNode = this.createNodeElement(noteHtml);

        if (type != "embroidery") {
            noteNode.querySelector("#MpNoteSubmitNote").style.display = "none";
        }

        this.showAlert(title, noteNode, "info");

        const textArea = document.getElementById("MpNoteContent");

        if (textArea) {
            textArea.focus();
        }

        this.bindSubmitNote();
    }

    imagePreview() {
        document.getElementById("MpNoteAttachment").addEventListener("change", function () {
            const file = this.files[0];
            const reader = new FileReader();
            reader.onload = function (e) {
                const img = document.querySelector("#imgMpNoteAttachment");
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    showAddAttachment(idNote, idCustomer, idOrder, typeNote) {
        const existsElement = document.getElementById("mpnote-add-attachment");
        if (existsElement) {
            existsElement.remove();
        }

        const attachmentNode = this.createNodeElement(this.getTemplateAddAttachmentElement());
        const attachmentSubmit = attachmentNode.querySelector("#btnSubmitAttachment");

        //attachmentSubmit.dataset.id_note = idNote;
        //attachmentSubmit.dataset.id_order = idOrder;
        //attachmentSubmit.dataset.id_customer = idCustomer;
        //attachmentSubmit.dataset.id_type = typeNote;

        this.showAlert("Aggiungi allegato", attachmentNode, "info");

        this.bindSubmitAddAttachmentButtons();
        this.bindBtnSubmitAttachment();
        this.imagePreview();
    }

    bindSubmitNote() {
        const btnSubmit = document.getElementById("MpNoteSubmitNote");
        if (btnSubmit) {
            btnSubmit.removeEventListener("click", function () {
                console.log("Evento rimosso");
            });
            btnSubmit.addEventListener("click", async (event) => {
                event.preventDefault();
                event.stopPropagation();

                const form = document.getElementById("mpnote-form-new-note");
                if (form) {
                    await this.submitNote(form);
                }
                return false;
            });
        }

        return false;
    }

    async submitNote(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        const result = await this.fetch("submitNote", data);

        if (result.success) {
            this.showAlert("Successo", result.message, "success");
        } else {
            this.showAlert("Errore", result.message, "error");
        }
    }

    async bindSubmitAddAttachmentButtons() {
        const btnSubmit = document.getElementById("MpNoteSubmitNote");
        if (btnSubmit) {
            btnSubmit.removeEventListener("click", function () {
                console.log("Evento rimosso");
            });
            btnSubmit.addEventListener("click", async (event) => {
                event.preventDefault();
                event.stopPropagation();

                const form = document.getElementById("mpnote-form-add-attachment");
                if (form) {
                    await this.submitAttachment(form);
                }
                return false;
            });
        }

        return false;
    }

    async submitAttachment(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        const result = await this.fetch("submitAttachment", data);

        if (result.success) {
            this.showAlert("Successo", result.message, "success");
        } else {
            this.showAlert("Errore", result.message, "error");
        }
    }

    async showAlert(title, message, type = "info") {
        const existsDialog = document.getElementById("mpnote-dialog-alert");
        if (existsDialog) {
            existsDialog.remove();
        }

        const dialogNode = this.getAlertElement();
        document.body.appendChild(dialogNode);

        const dialog = document.getElementById("mpnote-dialog-alert");
        const cardHeader = dialog.querySelector(".card-header");
        const cardBody = dialog.querySelector(".card-body");
        const cardFooter = dialog.querySelector(".card-footer");

        const existingIcon = cardHeader.querySelector('slot[name="icon"]');
        const existingTitle = cardHeader.querySelector('slot[name="title"]');

        existingTitle.textContent = title;
        existingIcon.innerHTML = "";

        if (typeof message === "string") {
            cardBody.textContent = message;
        } else {
            cardBody.innerHTML = "";
            cardBody.appendChild(message);
        }

        switch (type) {
            case "info":
                cardHeader.classList.add("card-info");
                cardHeader.style.color = "#FCFCFC";
                cardFooter.querySelector("button.yes").style.display = "none";
                cardFooter.querySelector("button.no").style.display = "none";
                cardFooter.querySelector("button.cancel").style.display = "block";
                cardFooter.querySelector("button.cancel").classList.add("btn-info");
                existingIcon.innerHTML = `<i class="material-icons">info</i>`;
                break;
            case "success":
                cardHeader.classList.add("card-success");
                cardHeader.style.color = "#FCFCFC";
                cardFooter.querySelector("button.yes").style.display = "none";
                cardFooter.querySelector("button.no").style.display = "none";
                cardFooter.querySelector("button.cancel").style.display = "block";
                cardFooter.querySelector("button.cancel").classList.add("btn-success");
                existingIcon.innerHTML = `<i class="material-icons">check</i>`;
                break;
            case "error":
                cardHeader.classList.add("card-danger");
                cardHeader.style.color = "#FCFCFC";
                cardFooter.querySelector("button.yes").style.display = "none";
                cardFooter.querySelector("button.no").style.display = "none";
                cardFooter.querySelector("button.cancel").style.display = "block";
                cardFooter.querySelector("button.cancel").classList.add("btn-danger");
                existingIcon.innerHTML = `<i class="material-icons">error</i>`;
                break;
            case "warning":
                cardHeader.classList.add("card-warning");
                cardHeader.style.color = "#FCFCFC";
                cardFooter.querySelector("button.yes").style.display = "none";
                cardFooter.querySelector("button.no").style.display = "none";
                cardFooter.querySelector("button.cancel").style.display = "block";
                cardFooter.querySelector("button.cancel").classList.add("btn-warning");
                existingIcon.innerHTML = `<i class="material-icons">warning</i>`;
                break;
            case "confirm":
                cardHeader.classList.add("card-danger");
                cardHeader.style.color = "#FCFCFC";
                cardFooter.querySelector("button.yes").style.display = "block";
                cardFooter.querySelector("button.no").style.display = "block";
                cardFooter.querySelector("button.cancel").style.display = "none";
                cardFooter.querySelector("button.yes").classList.add("btn-success");
                cardFooter.querySelector("button.no").classList.add("btn-danger");
                existingIcon.innerHTML = `<i class="material-icons">question_mark</i>`;
                break;
        }

        dialog.showModal();

        // Funzione helper per chiudere il dialog con animazione
        const closeDialog = (result) => {
            dialog.classList.add("closing");
            setTimeout(() => {
                dialog.close();
                dialog.remove();
            }, 200); // Durata dell'animazione popupOut
            return result;
        };

        // Restituisce una Promise che si risolve quando il dialog viene chiuso
        return new Promise((resolve) => {
            let resolved = false;

            dialog.querySelector(".card-footer button.cancel").addEventListener("click", () => {
                if (!resolved) {
                    resolved = true;
                    resolve(closeDialog(false));
                }
            });

            dialog.querySelector(".card-footer button.yes").addEventListener("click", () => {
                if (!resolved) {
                    resolved = true;
                    resolve(closeDialog(true));
                }
            });

            dialog.querySelector(".card-footer button.no").addEventListener("click", () => {
                if (!resolved) {
                    resolved = true;
                    resolve(closeDialog(false));
                }
            });

            // Gestisce anche la chiusura con ESC o altri metodi
            dialog.addEventListener(
                "close",
                () => {
                    if (!resolved) {
                        resolved = true;
                        resolve(false);
                    }
                },
                { once: true },
            );
        });
    }

    async showAttachments(idNote, typeNote) {
        const response = await this.fetch("getAttachments", { idNote: idNote, typeNote: typeNote });

        if (response.success) {
            const attachmentCardContainer = this.createNodeElement(this.getTemplateAttachmentCardContainer());
            const attachmentListNode = this.createNodeElement(this.getTemplateAttachmentListElement());
            attachmentCardContainer.querySelector("#list-attachments").innerHTML = "";

            console.log("Trovati %s elementi", response.data.length);

            response.data.forEach((attachment) => {
                const attachmentNode = attachmentListNode.cloneNode(true);
                attachmentNode.querySelector("img").src = attachment.url;
                attachmentNode.querySelector("span").textContent = attachment.filetitle;

                attachmentCardContainer.querySelector("#list-attachments").appendChild(attachmentNode);
            });

            this.showAlert("Allegati Note", attachmentCardContainer, "info");
        } else {
            this.showAlert("Errore", response.message, "error");
        }
    }

    bindBtnAttachment() {
        const btnAttachments = document.querySelectorAll(".btn-attachment");
        btnAttachments.forEach((btn) => {
            //Rimuovi il bind precedente
            btn.removeEventListener("click", () => {
                console.log("Rimuovo bind su %s", btn.dataset.id_note);
            });

            btn.addEventListener("click", () => {
                console.log("Aggiungo bind su %s", btn.dataset.id_note);
                const typeNote = btn.dataset.id_type;
                const idNote = btn.dataset.id_note;
                this.showAttachments(idNote, typeNote);
            });
        });
    }

    bindBtnNewNote() {}

    bindBtnViewNote() {
        const btnViewNotes = document.querySelectorAll(".btn-view-note");
        btnViewNotes.forEach((btn) => {
            btn.removeEventListener("click", () => {
                console.log("Rimuovo bind su %s", btn.dataset.id);
            });

            btn.addEventListener("click", () => {
                console.log("Aggiungo bind su %s", btn.dataset.id);
                const idNote = btn.dataset.id;
                const typeNote = btn.dataset.type;

                this.showViewNote("NOTA", typeNote, idNote);
            });
        });
    }

    bindBtnAddAttachment() {
        const btnAddAttachments = document.querySelectorAll(".btn-add-attachment");
        btnAddAttachments.forEach((btn) => {
            btn.removeEventListener("click", () => {
                console.log("Rimuovo bind su %s", btn.dataset.id);
            });

            btn.addEventListener("click", () => {
                console.log("Aggiungo bind su %s", btn.dataset.id);
                const idNote = btn.dataset.id;
                const idCustomer = btn.dataset.customer;
                const idOrder = btn.dataset.order;
                const idType = btn.dataset.type;

                this.showAddAttachment(idNote, idCustomer, idOrder, idType);
            });
        });
    }

    bindBtnSubmitAttachment() {
        const btnSubmitAttachment = document.getElementById("btnSubmitAttachment");

        if (!btnSubmitAttachment) {
            return;
        }

        btnSubmitAttachment.removeEventListener("click", () => {
            console.log("Rimuovo bind su %s", btnSubmitAttachment.dataset.id);
        });

        btnSubmitAttachment.addEventListener("click", async (e) => {
            e.preventDefault();
            const btn = e.currentTarget;

            console.log("Aggiungo bind su %s", btn.dataset.id_note);

            const idNote = btn.dataset.id_note;
            const idOrder = btn.dataset.id_order;
            const idCustomer = btn.dataset.id_customer;
            const idType = btn.dataset.id_type;
            const form = document.getElementById("mpnote-form-add-attachment");
            const formData = new FormData(form);
            formData.append("ajax", 1);
            formData.append("action", "addAttachment");
            formData.append("idNote", idNote);
            formData.append("idOrder", idOrder);
            formData.append("idCustomer", idCustomer);
            formData.append("idType", idType);

            const response = await fetch(this.ajaxController, {
                method: "POST",
                body: formData,
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert("Allegato aggiunto con successo", "", "success");
            } else {
                this.showAlert("Errore", data.message, "error");
            }

            return false;
        });
    }

    magnifyImage(image) {
        const dialog = this.createNodeElement(this.getTemplateMagnifyImage());
        const img = dialog.querySelector("img");
        img.src = image.src;
        img.alt = image.alt;

        dialog.addEventListener("close", () => {
            dialog.remove();
        });

        document.body.appendChild(dialog);
        dialog.showModal();
    }
}
