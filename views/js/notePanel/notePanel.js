console.log("NotePanel.js loaded");

async function notePanelLoaded() {
    console.log("NotePanelLoaded Event");

    const noteCard = document.querySelector(".note-card");
    const noteForm = document.getElementById("noteForm");
    const btnSaveNote = document.getElementById("btnSaveNote");
    const btnCancelNoteForm = document.getElementById("btnCancelNoteForm");
    const gravitySelect = document.getElementById("note_gravity");
    const gravityIcon = document.getElementById("gravityIcon");

    //rebind attachment buttons
    bindNoteAttachment();

    //update Icon
    await updateGravityIcon();
    gravitySelect.addEventListener("click", updateGravityIcon);

    // Save note
    if (btnSaveNote) {
        btnSaveNote.addEventListener("click", async function () {
            if (!noteForm.checkValidity()) {
                noteForm.reportValidity();
                return;
            }

            // Show loading state
            btnSaveNote.disabled = true;
            btnSaveNote.innerHTML = '<i class="material-icons align-middle mr-1">hourglass_empty</i> Salvataggio...';

            const formData = new FormData(noteForm);
            const id_note = noteCard.dataset.id_note;
            const id_order = noteCard.dataset.id_order;
            const id_customer = noteCard.dataset.id_customer;

            formData.append("ajax", 1);
            formData.append("action", id_note > 0 ? "updateNote" : "createNote");
            formData.append("id_note", id_note);
            formData.append("id_order", id_order);
            formData.append("id_customer", id_customer);

            try {
                const response = await fetch(ajaxController, {
                    method: "POST",
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    await swalSuccess(data.message || "Nota salvata con successo").then(() => {
                        window.location.reload();
                    });
                } else {
                    await swalError(data.message || "Si è verificato un errore durante il salvataggio della nota");
                    // Reset button state
                    btnSaveNote.disabled = false;
                    btnSaveNote.innerHTML = '<i class="material-icons align-middle mr-1">save</i> Salva';
                }
            } catch (error) {
                console.error("Error saving note:", error);
                await swalError("Si è verificato un errore durante la comunicazione con il server");
                // Reset button state
                btnSaveNote.disabled = false;
                btnSaveNote.innerHTML = '<i class="material-icons align-middle mr-1">save</i> Salva';
            }
        });
    }

    // Cancel button
    if (btnCancelNoteForm) {
        btnCancelNoteForm.addEventListener("click", function () {
            Swal.close();
        });
    }
}

async function updateGravityIcon() {
    const gravitySelect = document.getElementById("note_gravity");
    const gravityIcon = document.getElementById("gravityIcon");

    const selectedOption = gravitySelect.options[gravitySelect.selectedIndex];
    const iconName = gravityIcons[selectedOption.value];
    gravityIcon.textContent = iconName;

    // Update icon color based on gravity
    const gravity = selectedOption.value;
    gravityIcon.style.color = gravityColors[gravity];
}
