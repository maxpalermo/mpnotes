// Funzione per gestire l'azione personalizzata nella griglia degli ordini
async function handleCustomAction(orderId) {
    // Mostra un messaggio di conferma
    Swal.fire({
        title: "Azione personalizzata",
        text: `Stai eseguendo un'azione personalizzata per l'ordine #${orderId}`,
        icon: "info",
        showCancelButton: true,
        confirmButtonText: "Procedi",
        cancelButtonText: "Annulla"
    }).then((result) => {
        if (result.isConfirmed) {
            // Qui puoi implementare la tua logica personalizzata
            // Ad esempio, puoi fare una chiamata AJAX al server
            fetch(adminURL, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: JSON.stringify({
                    ajax: 1,
                    action: "customAction",
                    id_order: orderId
                })
            })
                .then((response) => response.json())
                .then((data) => {
                    Swal.fire({
                        title: "Completato",
                        text: "Azione personalizzata completata con successo!",
                        icon: "success"
                    });
                })
                .catch((error) => {
                    Swal.fire({
                        title: "Errore",
                        text: "Si è verificato un errore durante l'esecuzione dell'azione personalizzata.",
                        icon: "error"
                    });
                });
        }
    });
}

document.addEventListener("DOMContentLoaded", (e) => {
    // Aggiungi un event listener per il pulsante di azione personalizzata nella griglia degli ordini
    const orderGrid = document.querySelector(".js-order-grid-table");
    if (orderGrid) {
        orderGrid.addEventListener("click", (event) => {
            // Verifica se l'elemento cliccato è il pulsante di azione personalizzata
            const customActionBtn = event.target.closest(".btn-customAction");
            if (customActionBtn) {
                event.preventDefault();
                event.stopPropagation();

                // Ottieni l'ID dell'ordine dalla riga della tabella
                const row = customActionBtn.closest("tr");
                const orderId = row.dataset.orderId || row.querySelector("td[data-order-id]")?.dataset.orderId;

                // Esegui l'azione personalizzata
                if (orderId) {
                    handleCustomAction(orderId);
                }
            }
        });
    }

    document.querySelectorAll(".search-bar").forEach((el) => {
        el.addEventListener("input", (e) => {
            e.stopPropagation();
            e.stopImmediatePropagation();
            const text = e.target.value;
            const type = e.target.closest("div.panel").dataset.type;

            updateSearch(text, type);
        });
    });

    document.querySelectorAll(".tr-note").forEach((e) => {
        e.addEventListener("click", (e) => {
            e.stopPropagation();
            e.stopImmediatePropagation();

            const id = e.target.closest(".tr-note").dataset.id;
            const type = e.target.closest(".tr-note").dataset.type;

            getNote(type, id);
        });
    });

    //CLICK SU NUOVA NOTA
    document.querySelectorAll(".btn-new-note").forEach((btn) => {
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
            e.stopImmediatePropagation();

            const noteTableName = e.target.closest("div.panel").dataset.table;
            const noteType = e.target.closest("div.panel").dataset.type;
            const noteId = e.target.closest("div.panel").dataset.id;

            newNote(noteTableName, noteType, noteId);
        });
    });
});

async function updateSearch(text, type) {
    const response = await fetch(adminURL, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({
            ajax: 1,
            action: "updateSearch",
            text: text,
            type: type,
            id_customer: noteCustomerId,
            id_order: noteOrderId
        })
    });

    const data = await response.json();

    document.getElementById("tableNote-" + type).querySelector("tbody").innerHTML = data.tbody;
    rebindTable("tableNote-" + type);
}

function rebindTable(table) {
    document
        .getElementById(table)
        .querySelectorAll("tbody tr")
        .forEach((tr) => {
            tr.addEventListener("click", (e) => {
                e.stopPropagation();
                e.stopImmediatePropagation();

                const id = e.target.closest("tr").dataset.id;
                const tableName = "mp_note_" + e.target.closest("tr").dataset.type;
                noteType = e.target.closest("tr").dataset.type;
                console.log("GetNote", tableName, id, noteType);

                getNote(tableName, id);
            });
        });
}

function showSwalLoading() {
    swal.fire({
        title: "Attendere",
        text: "Salvataggio in corso...",
        text: "Salvataggio in corso...",
        icon: "info",
        showConfirmButton: false,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

function ucFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

async function saveNote(note) {
    $("#panelNoteModal").modal("hide"); // Hide the modal
    showSwalLoading();

    const data = {
        ajax: 1,
        action: "saveNote",
        note: note
    };

    console.log("call save note", adminURL, data);

    const response = await fetch(adminURL, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify(data)
    });

    const result = await response.json();

    if (result.success) {
        if (note.type == 1) {
            document.getElementById("tableNoteCustomer").querySelector("tbody").innerHTML = result.tbody;
            rebindTable("tableNoteCustomer");
        } else if (note.type == 2) {
            document.getElementById("tableNoteEmbroidery").querySelector("tbody").innerHTML = result.tbody;
            rebindTable("tableNoteEmbroidery");
        } else if (note.type == 3) {
            document.getElementById("tableNoteOrder").querySelector("tbody").innerHTML = result.tbody;
            rebindTable("tableNoteOrder");
        }

        swal.hideLoading();
        swal.update({
            title: "Successo",
            text: result.message,
            icon: "success",
            showConfirmButton: true
        });
    } else {
        swal.hideLoading();
        swal.update({
            title: "Errore",
            text: result.message,
            icon: "error",
            showConfirmButton: true
        });
    }
}

async function doTest(id, type) {
    const response = await fetch(adminURL, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({
            ajax: 1,
            action: "doTest",
            id: id,
            type: type
        })
    });

    const data = await response.json();

    swal.fire({
        title: "Test Ajax",
        text: JSON.stringify(data),
        icon: "success"
    });
}

async function newNote(tableName, type, id) {
    const response = await fetch(adminURL, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({
            ajax: 1,
            action: "showNote",
            tableName: tableName,
            type: type,
            id: id,
            id_row: 0,
            new: 1
        })
    });

    const data = await response.json();

    const modal = data.modal;
    //rimuove tutti i modali esistenti del tipo tablename
    $("#panelNoteModal").remove();
    bindNote(modal);
}

async function getNote(type, id) {
    const response = await fetch(adminURL, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({
            ajax: 1,
            action: "showNote",
            type: type,
            id_order: noteOrderId,
            id_customer: noteCustomerId,
            id_row: id,
            noteOrderUploadDir: noteOrderUploadDir,
            noteEmbroideryUploadDir: noteEmbroideryUploadDir,
            new: 0
        })
    });

    const data = await response.json();

    const modal = data.modal;
    //rimuove tutti i modali esistenti del tipo tablename
    $("#panelNoteModal").remove();
    bindNote(modal);
}

async function bindNote(modal) {
    //aggiungo il modale al body
    document.body.insertAdjacentHTML("afterbegin", modal);
    $("#panelNoteModal").modal("show");

    try {
        console.log("Bind modal note");
        attachmentsDiv = document.getElementById("attachments-div");
        handleSaveNoteClick();
        bindAttachments(attachmentsDiv);
    } catch (error) {
        return;
    }
}

function bindAttachments(attachmentsDiv) {
    console.log("Bind attachments", attachmentsDiv);

    const noteId = parseInt(document.getElementById("noteId").value, 10);
    const type = noteType;

    if (noteId != 0) {
        console.log("Note id", noteId, "visible");
        attachmentsDiv.style.display = "block";
    } else {
        console.log("Note id", noteId, "hidden");
        attachmentsDiv.style.display = "none";
        return;
    }

    document.getElementById("newAttachment").addEventListener("change", function (e) {
        var file = e.target.files[0];
        var reader = new FileReader();
        var previewContainer = document.querySelector(".preview-container");
        var previewImage = document.getElementById("attachmentPreview");

        reader.onload = function (e) {
            previewImage.src = e.target.result;
            previewContainer.style.display = "block";
        };

        if (file) {
            reader.readAsDataURL(file);
        } else {
            previewContainer.style.display = "none";
        }
    });

    document.querySelectorAll(".thumbnail-img").forEach((img) => {
        img.addEventListener("click", function (e) {
            e.stopPropagation();
            e.stopImmediatePropagation();

            const image = e.target;

            console.log("CLICK", image.dataset.id);

            var fullscreen = document.createElement("div");
            fullscreen.style.cssText = "position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);display:flex;justify-content:center;align-items:center;z-index:9999;";
            var img = document.createElement("img");
            img.src = image.src;
            img.style.cssText = "max-width:90%;max-height:90%;object-fit:contain;";
            fullscreen.appendChild(img);
            document.body.appendChild(fullscreen);
            fullscreen.onclick = function () {
                document.body.removeChild(fullscreen);
            };
            document.addEventListener("keydown", function (e) {
                if (e.key === "Escape") {
                    document.body.removeChild(fullscreen);
                }
            });
        });
    });

    document.querySelectorAll(".delete-attachment").forEach((btn) => {
        btn.addEventListener("click", function (e) {
            e.preventDefault();

            const id = this.dataset.id;

            console.log("DELETE", id);
        });
    });

    document.getElementById("addAttachment").addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        const button = e.target;
        const type = button.dataset.type;
        const noteId = document.getElementById("noteId").value;
        const file = document.getElementById("newAttachment").files[0];
        const formData = new FormData();

        formData.append("ajax", 1);
        formData.append("action", "addAttachment");
        formData.append("type", type);
        formData.append("id_row", noteId);
        formData.append("file", file);
        formData.append("hasFile", 1);

        $("#panelNoteModal").modal("hide");
        showSwalLoading();

        $.ajax({
            url: adminURL,
            type: "post",
            dataType: "json",
            processData: false,
            contentType: false,
            data: formData,
            success: function (response) {
                console.log("AJAX SUCCESS:", response);

                if (response.success) {
                    swal.hideLoading();
                    swal.update({
                        title: "Successo",
                        text: response.message,
                        icon: "success",
                        showConfirmButton: true
                    });

                    let tableName = "";
                    let trType = "";

                    $("#panelNoteModal").remove();
                    if (type == 3) {
                        tableName = "tableNoteOrder";
                        trType = ".tr-note[data-type=order]";
                    } else if (type == 2) {
                        tableName = "tableNoteEmbroidery";
                        trType = ".tr-note[data-type=embroidery]";
                    } else {
                        return;
                    }

                    document.getElementById(tableName).querySelector("tbody").innerHTML = response.tbody;
                    document.querySelectorAll(trType).forEach((e) => {
                        e.addEventListener("click", (e) => {
                            e.stopPropagation();
                            e.stopImmediatePropagation();

                            const id = e.target.closest(".tr-note").dataset.id;
                            const tableName = "mp_note_" + e.target.closest(".tr-note").dataset.type;
                            getNote(tableName, id);
                        });
                    });
                } else {
                    swal.hideLoading();
                    swal.update({
                        title: "Errore",
                        text: response.message,
                        icon: "error",
                        showConfirmButton: true
                    });
                }
            },
            error: function (response) {
                console.error(response);
                if (type == "order") {
                    $("#noteOrderModal").remove();
                } else if (type == "embroidery") {
                    $("#noteEmbroideryModal").remove();
                }
                swal.update({
                    title: "Errore",
                    text: response.responseText,
                    icon: "error",
                    showConfirmButton: true
                });
            }
        });
    });
}

function handleSaveNoteClick() {
    const modalId = "#panelNoteModal";
    const btnId = "btnSaveNote";
    console.log("Handle save note click");

    try {
        document.getElementById(btnId).addEventListener("click", (e) => {
            const note = {
                noteId: $("#noteId").val(),
                noteType: noteType,
                noteCustomerId: noteCustomerId,
                noteOrderId: noteOrderId,
                noteText: $("#noteText").val(),
                noteAlert: $("#noteAlert").val(),
                notePrintable: $("input[name=notePrintable]:checked").val(),
                noteChat: $("input[name=noteChat]:checked").val()
            };

            if (note.noteText.trim() === "") {
                $(modalId).modal("hide"); // Hide the modal
                Swal.fire({
                    icon: "error",
                    title: "Errore",
                    text: "Il testo della nota non può essere vuoto",
                    confirmButtonText: "OK"
                }).then(() => {
                    $(modalId).modal("show"); // Show the modal
                });
                return false;
            }

            saveNote(note);
        });
        console.log("pulsante SALVA abilitato");
    } catch (error) {
        console.log("pulsante SALVA non abilitato");
    }
}
