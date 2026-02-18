async function bindNoteAttachment() {
    const btnAttachment = document.querySelectorAll(".btn-attachment");

    if (btnAttachment) {
        console.log("btnAttachment", btnAttachment.length);
        btnAttachment.forEach((btn) => {
            btn.addEventListener("click", async function (e) {
                console.log("btnAttachment click", btn.dataset.id_note);
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                const id_note = btn.dataset.id_note;
                // visualizzo il pannello allegati scaricandolo via ajax e inserendolo in SWAL
                const response = await fetch(MpNotes.AjaxController, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    body: JSON.stringify({
                        ajax: 1,
                        action: "getAttachments",
                        id_note: id_note,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    showDialogContainer("Allegati", data.html);
                    bindPanelNoteAttachments();
                    bindImgsAttachment();
                } else {
                    const error = `
                        <div class="alert alert-danger">
                            <strong>Errore!</strong> ${data.message || "Si è verificato un errore durante il caricamento degli allegati"}
                        </div>
                    `;
                    showDialogAlert("Errore", error, "error");
                }
            });
        });
    }

    async function bindImgsAttachment() {
        const imgAttachments = document.querySelectorAll(".attachment-preview-img");

        if (imgAttachments) {
            console.warn("TROVATE %s IMMAGINI", imgAttachments.length);

            imgAttachments.forEach((el) => {
                console.log("Attachment preview", el);

                el.addEventListener("click", async function (e) {
                    console.log("imgAttachment click", el.dataset.id_note);
                    e.preventDefault();
                    e.stopPropagation();

                    //Visualizzo l'immagine ingrandita
                    const imgSrc = el.src;
                    const imgElement = `
                    <img src="${imgSrc}" alt="Allegato" style="width: 100%; height: auto; object-fit: contain; object-position: center; object-fit: contain; object-position: center; max-width: 100%; max-height: 60vh;">
                `;
                    showDialogContainer("Allegati", imgElement);
                });
            });
        }
    }
}
