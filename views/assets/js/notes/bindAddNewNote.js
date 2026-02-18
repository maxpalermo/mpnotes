document.addEventListener("DOMContentLoaded", () => {
    const btnNewNoteCustomer = document.querySelector(".btnNewNoteCustomer");
    const btnNewNoteOrder = document.querySelector(".btnNewNoteOrder");
    const btnNewNoteEmbroidery = document.querySelector(".btnNewNoteEmbroidery");

    if (btnNewNoteCustomer) {
        btnNewNoteCustomer.addEventListener("click", () => {
            MpNotesDialogAlert.show("Nuova Nota Cliente", "customer", "info");
            //showDialogForm("Nuova Nota Cliente", "customer");
        });
    }

    if (btnNewNoteOrder) {
        btnNewNoteOrder.addEventListener("click", () => {
            MpNotesDialogAlert.show("Nuova Nota Ordini", "order", "confirm");
            //showDialogForm("Nuova Nota Ordini", "order");
        });
    }

    if (btnNewNoteEmbroidery) {
        btnNewNoteEmbroidery.addEventListener("click", () => {
            MpNotesDialogAlert.show("Nuova Nota Ricami", "embroidery", "error");
            //showDialogForm("Nuova Nota Ricami", "embroidery");
        });
    }
});
