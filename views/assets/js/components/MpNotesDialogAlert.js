class MpNotesDialogAlert {
    static getAlertElement() {
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

        const templateNode = document.createElement("template");
        templateNode.id = "template-mpnote-dialog-alert";
        templateNode.innerHTML = template;

        const dialogFragment = templateNode.content.cloneNode(true);
        const dialogNode = dialogFragment.querySelector("dialog");

        return dialogNode;
    }

    static async show(title, message, type = "info") {
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

        cardBody.querySelector("p").textContent = message;
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
                { once: true }
            );
        });
    }
}
