console.log("summaryPanel.js loaded");

document.addEventListener("SummaryNoteLoaded", async () => {
    console.log("Captured SummaryNoteLoaded event");

    const trNotes = document.querySelectorAll(".tr-note");
    const trAttachments = document.querySelectorAll(".note-attachment");
    const btnNewNote = document.querySelectorAll(".btn-new-note");

    bindNoteAttachment();
    bindNoteFlags();
    bindSearchBar();

    trNotes.forEach((element) => {
        element.addEventListener("click", async (e) => {
            if (e.target.closest(".no-row-click")) {
                return;
            }
            const id_row = element.dataset.id.replace("row_", "");

            try {
                const response = await fetch(ajaxController, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest"
                    },
                    body: JSON.stringify({
                        ajax: 1,
                        action: "getNote",
                        id_row: id_row
                    })
                });
                const data = await response.json();
                if (data.success) {
                    const panelHTML = data.html;
                    await swalNote(panelHTML, "btnCancelNoteForm", notePanelLoaded);
                    bindNoteAttachment();
                    bindNoteFlags();
                    bindSearchBar();
                } else {
                    if (data.message) {
                        swalError(data.message);
                    } else {
                        swalError("Si è verificato un errore durante il recupero del record.");
                    }
                }
            } catch (error) {
                console.error(error);
                swalError("Si è verificato un errore durante il processo.");
            }
        });
    });

    trAttachments.forEach((element) => {
        element.addEventListener("click", async (e) => {
            if (e.target.closest(".no-row-click")) {
                return;
            }
            const id_note = element.dataset.id_note;
            try {
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
                if (data.success) {
                    const panelHTML = data.html;
                    await swalNote(panelHTML, "btnCancelNoteForm", notePanelLoaded);
                    bindNoteAttachment();
                    bindNoteFlags();
                    bindSearchBar();
                } else {
                    if (data.message) {
                        swalError(data.message);
                    } else {
                        swalError("Si è verificato un errore durante il recupero del record.");
                    }
                }
            } catch (error) {
                console.error(error);
                swalError("Si è verificato un errore durante il processo.");
            }
        });
    });

    btnNewNote.forEach((element) => {
        element.addEventListener("click", async (e) => {
            if (e.target.closest(".no-row-click")) {
                return;
            }
            const id_note_type = element.dataset.id_note_type;
            const id_order = element.dataset.id_order;
            const id_customer = element.dataset.id_customer;
            const id_employee = element.dataset.id_employee;
            try {
                const response = await fetch(ajaxController, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest"
                    },
                    body: JSON.stringify({
                        ajax: 1,
                        action: "getNote",
                        id_note_type: id_note_type,
                        id_order: id_order,
                        id_customer: id_customer,
                        id_employee: id_employee
                    })
                });
                const data = await response.json();
                if (data.success) {
                    const panelHTML = data.html;
                    await swalNote(panelHTML, "btnCancelNoteForm", notePanelLoaded);
                    bindNoteAttachment();
                    bindNoteFlags();
                    bindSearchBar();
                } else {
                    if (data.message) {
                        swalError(data.message);
                    } else {
                        swalError("Si è verificato un errore durante il recupero del record.");
                    }
                }
            } catch (error) {
                console.error(error);
                swalError("Si è verificato un errore durante il processo.");
            }
        });
    });
});
