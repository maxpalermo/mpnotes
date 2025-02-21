document.addEventListener("DOMContentLoaded", (e) => {
    document.querySelectorAll(".search-bar").forEach((el) => {
        el.addEventListener("input", (e) => {
            e.stopPropagation();
            e.stopImmediatePropagation();
            const text = e.target.value;
            const type = e.target.dataset.type;
            const id = e.target.closest(".search-bar").dataset.id;

            updateSearch(id, text, type);
        });
    });

    document.querySelectorAll(".tr-note").forEach((e) => {
        e.addEventListener("click", (e) => {
            e.stopPropagation();
            e.stopImmediatePropagation();

            const id = e.target.closest(".tr-note").dataset.id;
            const tableName = "mp_note_" + e.target.closest(".tr-note").dataset.type;

            showNote(tableName, id);
        });
    });

    document.querySelectorAll(".btn-new-note").forEach((btn) => {
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
            e.stopImmediatePropagation();

            const tableName = e.target.closest("button").dataset.table;
            showNote(tableName, 0);
        });
    });
});

async function updateSearch(id, text, type) {
    const response = await fetch(adminURL, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({
            ajax: 1,
            action: "updateSearch",
            id: id,
            text: text,
            type: type
        })
    });

    const data = await response.json();

    switch (type) {
        case "customer":
            document.getElementById("tableNoteCustomer").querySelector("tbody").innerHTML = data.tbody;
            rebindTable("tableNoteCustomer");
            break;
        case "order":
            document.getElementById("tableNoteOrder").querySelector("tbody").innerHTML = data.tbody;
            rebindTable("tableNoteOrder");
            break;
        case "embroidery":
            document.getElementById("tableNoteEmbroidery").querySelector("tbody").innerHTML = data.tbody;
            rebindTable("tableNoteEmbroidery");
            break;
    }
}

function rebindTable(table) {
    document
        .getElementById(table)
        .querySelector("tbody tr")
        .addEventListener("click", (e) => {
            e.stopPropagation();
            e.stopImmediatePropagation();

            const id = e.target.closest("tr").dataset.id;
            const tableName = "mp_note_" + e.target.closest("tr").dataset.type;
            showNote(tableName, id);
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

async function saveNoteCustomer(note) {
    $("#noteCustomerModal").modal("hide"); // Hide the modal
    showSwalLoading();

    const data = {
        ajax: 1,
        action: "saveNoteCustomer",
        note: note
    };

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
        document.getElementById("tableNoteCustomer").querySelector("tbody").innerHTML = result.tbody;
        const rows = document.getElementById("tableNoteCustomer").querySelector("tbody").rows.length;
        document.querySelector(".note-count-customer").innerHTML = rows;
        rebindTable("tableNoteCustomer");

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

async function saveNoteOrder(note) {
    $("#noteOrderModal").modal("hide"); // Hide the modal
    showSwalLoading();

    const data = {
        ajax: 1,
        action: "saveNoteOrder",
        note: note
    };

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
        document.getElementById("tableNoteOrder").querySelector("tbody").innerHTML = result.tbody;
        const rows = document.getElementById("tableNoteOrder").querySelector("tbody").rows.length;
        document.querySelector(".note-count-order").innerHTML = rows;
        rebindTable("tableNoteOrder");

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

async function saveNoteEmbroidery(note) {
    $("#noteEmbroideryModal").modal("hide"); // Hide the modal
    showSwalLoading();

    const data = {
        ajax: 1,
        action: "saveNoteEmbroidery",
        note: note
    };

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
        document.getElementById("tableNoteEmbroidery").querySelector("tbody").innerHTML = result.tbody;
        const rows = document.getElementById("tableNoteEmbroidery").querySelector("tbody").rows.length;
        document.querySelector(".note-count-embroidery").innerHTML = rows;
        rebindTable("tableNoteEmbroidery");

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

async function handleSaveNoteClick(tableName) {
    let modalId = "";
    switch (tableName) {
        case "mp_note_customer":
            modalId = "#noteCustomerModal";
            btnId = "saveNoteCustomer";
            document.getElementById(btnId).addEventListener("click", (e) => {
                const note = {
                    noteId: $("#noteId").val(),
                    noteCustomerId: noteCustomerId,
                    noteText: $("#noteText").val(),
                    noteType: $("#noteType").val()
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

                window[btnId](note);
            });

            break;
        case "mp_note_order":
            modalId = "#noteOrderModal";
            btnId = "saveNoteOrder";
            document.getElementById(btnId).addEventListener("click", (e) => {
                const note = {
                    noteId: $("#noteId").val(),
                    noteOrderId: noteOrderId,
                    noteCustomerId: noteCustomerId,
                    noteText: $("#noteText").val(),
                    noteType: $("#noteType").val(),
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

                window[btnId](note);
            });
            break;
        case "mp_note_embroidery":
            modalId = "#noteEmbroideryModal";
            btnId = "saveNoteEmbroidery";
            document.getElementById(btnId).addEventListener("click", (e) => {
                const note = {
                    noteId: $("#noteId").val(),
                    noteOrderId: noteOrderId,
                    noteCustomerId: noteCustomerId,
                    noteText: $("#noteText").val(),
                    noteType: $("#noteType").val(),
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

                saveNoteOrder(note);
            });
            break;
        default:
            break;
    }
}

async function showNote(tableName, id = 0) {
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
            id_order: noteOrderId,
            id_customer: noteCustomerId,
            id_row: id,
            noteOrderUploadDir: noteOrderUploadDir,
            noteEmbroideryUploadDir: noteEmbroideryUploadDir
        })
    });

    const data = await response.json();

    const modal = data.modal;
    //rimuove tutti i modali esistenti del tipo tablename
    switch (tableName) {
        case "mp_note_customer":
            $("#noteCustomerModal").remove();

            break;
        case "mp_note_order":
            $("#noteOrderModal").remove();

            break;
        case "mp_note_embroidery":
            $("#noteEmbroideryModal").remove();

            break;
    }

    function bindAttachments() {
        const noteId = parseInt(document.getElementById("noteId").value, 10);
        const attachmentsDiv = document.getElementById("attachments-div");

        console.log("NOTE ID", noteId);

        if (noteId != 0) {
            attachmentsDiv.style.display = "block";
        } else {
            attachmentsDiv.style.display = "none";
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

            if (type == "order") {
                $("#noteOrderModal").modal("hide");
                showSwalLoading();
            } else if (type == "embroidery") {
                $("#noteEmbroideryModal").modal("hide");
                showSwalLoading();
            }

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
                        if (type == "order") {
                            $("#noteOrderModal").remove();
                            document.getElementById("tableNoteOrder").querySelector("tbody").innerHTML = response.tbody;
                            document.querySelectorAll(".tr-note[data-type=order]").forEach((e) => {
                                e.addEventListener("click", (e) => {
                                    e.stopPropagation();
                                    e.stopImmediatePropagation();

                                    const id = e.target.closest(".tr-note").dataset.id;
                                    const tableName = "mp_note_" + e.target.closest(".tr-note").dataset.type;
                                    showNote(tableName, id);
                                });
                            });
                        } else if (type == "embroidery") {
                            $("#noteEmbroideryModal").remove();
                            document.getElementById("tableNoteEmbroidery").querySelector("tbody").innerHTML = response.tbody;
                            document.querySelectorAll(".tr-note[data-type=embroidery]").forEach((e) => {
                                e.addEventListener("click", (e) => {
                                    e.stopPropagation();
                                    e.stopImmediatePropagation();

                                    const id = e.target.closest(".tr-note").dataset.id;
                                    const tableName = "mp_note_" + e.target.closest(".tr-note").dataset.type;
                                    showNote(tableName, id);
                                });
                            });
                        }
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

    //aggiungo il modale al body
    document.body.insertAdjacentHTML("afterbegin", modal);

    switch (tableName) {
        case "mp_note_customer":
            $("#noteCustomerModal").modal("show");
            handleSaveNoteClick(tableName);

            break;
        case "mp_note_order":
            $("#noteOrderModal").modal("show");
            bindAttachments();
            handleSaveNoteClick(tableName);

            break;
        case "mp_note_embroidery":
            $("#noteEmbroideryModal").modal("show");
            bindAttachments();
            handleSaveNoteClick(tableName);

            break;
            break;
    }
}
