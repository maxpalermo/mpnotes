console.log("NotePanelAttachment.js loaded");

async function notePanelAttachmentLoaded() {
    console.log("notePanelAttachmentLoaded");
    const idNote = document.getElementById("id_note").value;
    const attachmentCard = document.querySelector(".attachment-card");
    const attachmentsContainer = document.getElementById("attachmentsContainer");

    // Upload section toggle
    const btnToggleUpload = document.getElementById("btnToggleUpload");
    const uploadSection = document.getElementById("uploadSection");
    const btnCancelUpload = document.getElementById("btnCancelUpload");

    if (btnToggleUpload) {
        btnToggleUpload.addEventListener("click", function () {
            uploadSection.style.display = uploadSection.style.display === "none" ? "block" : "none";
        });
    }

    if (btnCancelUpload) {
        btnCancelUpload.addEventListener("click", function () {
            uploadSection.style.display = "none";
            document.getElementById("fileUpload").value = "";
            document.querySelector(".custom-file-label").textContent = "Scegli file...";
        });
    }

    // File input label update
    const fileUpload = document.getElementById("fileUpload");
    if (fileUpload) {
        fileUpload.addEventListener("change", function (e) {
            const fileName = e.target.files.length > 1 ? e.target.files.length + " file selezionati" : e.target.files[0]?.name || "Scegli file...";

            const nextSibling = e.target.nextElementSibling;
            nextSibling.innerText = fileName;
        });
    }

    // Upload files
    const btnUploadFiles = document.getElementById("btnUploadFiles");
    if (btnUploadFiles) {
        btnUploadFiles.addEventListener("click", async function () {
            const files = fileUpload.files;

            if (files.length === 0) {
                swalError("Seleziona almeno un file da caricare");
                return;
            }

            const formData = new FormData();
            formData.append("ajax", 1);
            formData.append("action", "uploadAttachments");
            formData.append("id_note", idNote);

            for (let i = 0; i < files.length; i++) {
                formData.append("attachments[]", files[i]);
            }

            try {
                btnUploadFiles.disabled = true;
                btnUploadFiles.innerHTML = '<i class="material-icons spin">refresh</i> Caricamento...';

                const response = await fetch(ajaxController, {
                    method: "POST",
                    body: formData,
                });

                const data = await response.json();

                if (data.success) {
                    swalSuccess(data.message || "Allegati caricati con successo").then(() => {
                        // Refresh attachments list
                        refreshAttachments();
                    });
                } else {
                    swalError(data.message || "Si è verificato un errore durante il caricamento degli allegati");
                }
            } catch (error) {
                swalError("Si è verificato un errore durante la comunicazione con il server");
                console.error(error);
            } finally {
                btnUploadFiles.disabled = false;
                btnUploadFiles.innerHTML = '<i class="material-icons">cloud_upload</i> Carica';
                uploadSection.style.display = "none";
                fileUpload.value = "";
                document.querySelector(".custom-file-label").textContent = "Scegli file...";
            }
        });
    }

    const imgFluid = document.querySelectorAll(".img-fluid");
    if (imgFluid) {
        imgFluid.forEach((img) => {
            img.addEventListener("click", async function (e) {
                const src = img.src;
                // Open image in a new window at full size
                const newWindow = window.open(src, "_blank");
                if (newWindow) {
                    newWindow.document.write(`
                        <html>
                            <head>
                                <title>Visualizzazione immagine</title>
                                <style>
                                    body {
                                        margin: 0;
                                        padding: 0;
                                        display: flex;
                                        justify-content: center;
                                        align-items: center;
                                        background-color: rgba(0, 0, 0, 0.8);
                                        height: 100vh;
                                    }
                                    img {
                                        max-width: 100%;
                                        max-height: 100vh;
                                        object-fit: contain;
                                    }
                                </style>
                            </head>
                            <body>
                                <img src="${src}" alt="Immagine a schermo intero">
                            </body>
                        </html>
                    `);
                    newWindow.document.close();
                }
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
            });
        });
    }

    // Carousel navigation
    const carousel = document.querySelector(".attachment-carousel");
    const prevBtn = document.querySelector(".carousel-prev");
    const nextBtn = document.querySelector(".carousel-next");

    if (carousel && prevBtn && nextBtn) {
        prevBtn.addEventListener("click", function () {
            carousel.scrollBy({ left: -200, behavior: "smooth" });
        });

        nextBtn.addEventListener("click", function () {
            carousel.scrollBy({ left: 200, behavior: "smooth" });
        });
    }

    // Helper function to refresh attachments
    async function refreshAttachments() {
        try {
            const formData = new FormData();
            formData.append("ajax", 1);
            formData.append("action", "getAttachments");
            formData.append("id_note", idNote);

            const response = await fetch(ajaxController, {
                method: "POST",
                body: formData,
            });

            const data = await response.json();

            if (data.success && data.html) {
                // Replace the entire card with the new HTML
                const tempDiv = document.createElement("div");
                tempDiv.innerHTML = data.html;
                const newCard = tempDiv.firstElementChild;

                if (newCard) {
                    attachmentCard.parentNode.replaceChild(newCard, attachmentCard);
                }
            }
        } catch (error) {
            console.error("Error refreshing attachments:", error);
        }
    }

    // Helper functions for SweetAlert
    function swalSuccess(message) {
        return Swal.fire({
            title: "Successo",
            text: message,
            icon: "success",
            confirmButtonText: "OK",
        });
    }

    function swalError(message) {
        return Swal.fire({
            title: "Errore",
            text: message,
            icon: "error",
            confirmButtonText: "OK",
        });
    }

    function swalInfo(message) {
        return Swal.fire({
            title: "Informazione",
            text: message,
            icon: "info",
            confirmButtonText: "OK",
        });
    }

    function swalConfirm(title, text) {
        return Swal.fire({
            title: title,
            text: text,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sì, procedi",
            cancelButtonText: "Annulla",
            reverseButtons: true,
        });
    }
}
