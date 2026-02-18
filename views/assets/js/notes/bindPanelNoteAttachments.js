function bindPanelNoteAttachments() {
    const btnNewAttachment = document.getElementById("btn-new-attachment");
    const btnUploadAttachments = document.getElementById("btn-upload-attachments");
    const attachmentsCarousel = document.getElementById("attachmentsCarousel");
    const id_note = attachmentsCarousel.dataset.id_note;
    const attachmentCarouselPanel = attachmentsCarousel.querySelector(".attachment-carousel");

    // Inizializza i pulsanti di eliminazione
    document.querySelectorAll(".btn-delete-attachment").forEach((btn) => {
        btn.addEventListener("click", deleteAttachment);
    });

    if (btnNewAttachment) {
        // Apri modal per nuovo allegato
        btnNewAttachment.addEventListener("click", function () {
            $("#uploadAttachmentModal").modal("show");
        });
    }

    if (btnUploadAttachments) {
        // Gestisci upload allegati
        btnUploadAttachments.addEventListener("click", uploadAttachments);
    }
}

async function uploadAttachments() {
    const form = document.getElementById("attachmentUploadForm");
    const formData = new FormData(form);
    formData.append("id_note", id_note);
    formData.append("ajax", 1);
    formData.append("action", "uploadAttachments");

    try {
        const response = await fetch(MpNotes.AjaxController, {
            method: "POST",
            body: formData,
        });

        const data = await response.json();

        if (data.success) {
            showDialogAlert("Successo", data.message || "Allegati caricati con successo", "success");
            $("#uploadAttachmentModal").modal("hide");
            form.reset();
            // Aggiorna la visualizzazione degli allegati
            refreshAttachments();
        } else {
            showDialogAlert("Errore", data.message || "Si è verificato un errore durante il caricamento", "error");
        }
    } catch (error) {
        showDialogAlert("Errore", "Si è verificato un errore durante il caricamento", "error");
        console.error(error);
    }
}

async function deleteAttachment(e) {
    const id = e.currentTarget.dataset.id;
    console.warn("Richiesta eliminazione allegato", id);

    const confirmation = await showDialogAlert("Elimina allegato", "Sei sicuro di voler eliminare questo allegato?", "confirm");

    if (!confirmation) return;

    try {
        const response = await fetch(MpNotes.AjaxController, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
            body: JSON.stringify({
                ajax: 1,
                action: "deleteAttachment",
                id_attachment: id,
            }),
        });

        const data = await response.json();

        if (data.success) {
            showDialogAlert("Successo", data.message || "Allegato eliminato con successo", "success");
            // Rimuovi l'elemento dal DOM
            document.querySelector(`.attachment-item[data-id="${id}"]`).remove();
        } else {
            showDialogAlert("Errore", data.message || "Si è verificato un errore durante l'eliminazione", "error");
        }
    } catch (error) {
        showDialogAlert("Errore", "Si è verificato un errore durante l'eliminazione", "error");
        console.error(error);
    }
}

async function refreshAttachments() {
    try {
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
            // Aggiorna il contenuto del carousel con i nuovi allegati
            const carouselInner = attachmentsCarousel.querySelector(".carousel-inner");
            carouselInner.innerHTML = data.html || '<div class="alert alert-info w-100">Nessun allegato disponibile</div>';

            // Reinizializza i pulsanti di eliminazione
            document.querySelectorAll(".btn-delete-attachment").forEach((btn) => {
                btn.addEventListener("click", deleteAttachment);
            });
        }
    } catch (error) {
        console.error(error);
    }
}
