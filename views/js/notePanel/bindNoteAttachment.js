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
                const response = await fetch(ajaxController, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest"
                    },
                    body: JSON.stringify({
                        ajax: 1,
                        action: "getAttachments",
                        id_note: id_note
                    })
                });

                const data = await response.json();
                console.log("data", data);
                if (data.success) {
                    await swalNote(data.html, "btnCancelAttachmentForm", notePanelAttachmentLoaded);
                } else {
                    await swalError(data.message || "Si è verificato un errore durante il caricamento degli allegati");
                }
            });
        });
    }
}
