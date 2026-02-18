async function showDialogForm(title, icon = "add") {
    const existsDialog = document.getElementById("dialog-note-form");
    if (existsDialog) {
        existsDialog.remove();
    }

    const dialogTemplate = document.getElementById("template-dialog-note-form");
    const dialogNode = dialogTemplate.content.cloneNode(true);
    document.body.appendChild(dialogNode);

    const dialog = document.getElementById("dialog-note-form");
    const cardHeader = dialog.querySelector(".card-header");
    const cardBody = dialog.querySelector(".card-body");
    const cardFooter = dialog.querySelector(".card-footer");

    const existingIcon = cardHeader.querySelector('slot[name="icon"]');
    const existingTitle = cardHeader.querySelector('slot[name="title"]');

    existingTitle.textContent = title;
    existingIcon.innerHTML = "";

    switch (icon) {
        case "add":
            existingIcon.innerHTML = `<i class="material-icons">add</i>`;
            break;
        case "edit":
            existingIcon.innerHTML = `<i class="material-icons">edit</i>`;
            break;
    }

    dialog.showModal();

    // Funzione helper per chiudere il dialog con animazione
    const closeDialog = (result) => {
        dialog.classList.add("closing");
        setTimeout(() => {
            dialog.close();
            dialog.remove();
        }, 200);
        return result;
    };

    // Restituisce una Promise che si risolve quando il dialog viene chiuso
    return new Promise((resolve) => {
        let resolved = false;

        dialog.querySelector(".card-footer button.no").addEventListener("click", () => {
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
