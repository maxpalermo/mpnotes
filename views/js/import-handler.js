const ImportHandler = (function () {
    let progressBar;
    let progressSpan;
    let importStatus;
    let importModal;

    function init() {
        setupEventListeners();
        initializeElements();
    }

    function initializeElements() {
        progressBar = document.querySelector("#importProgress .progress-bar");
        progressSpan = progressBar.querySelector("span");
        importStatus = document.getElementById("importStatus");
        importModal = $("#importProgress");
    }

    function setupEventListeners() {
        // Handle table creation
        document.querySelectorAll(".create-table").forEach((button) => {
            button.addEventListener("click", function () {
                const tableType = this.dataset.table;
                handleTableCreation(tableType);
            });
        });

        // Handle import CSV
        document.querySelectorAll(".importBtn").forEach((btn) => {
            btn.addEventListener("click", function (e) {
                e.stopPropagation();
                e.stopImmediatePropagation();
                handleImport(this);
            });
        });

        // Handle file input change
        document.querySelectorAll(".file-upload-input").forEach((input) => {
            input.addEventListener("change", function () {
                const fileName = this.files[0]?.name || stringTranslated["select_file"];
                this.parentElement.querySelector(".file-upload-text").textContent = fileName;
            });
        });
    }

    function handleTableCreation(type) {
        Swal.fire({
            title: "Creazione tabella...",
            html: "Creazione della tabella in corso...",
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch("", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            },
            body: JSON.stringify({
                ajax: 1,
                action: "createTable",
                tableName: type
            })
        })
            .then((response) => response.json())
            .then((result) => {
                if (result.success) {
                    Swal.fire({
                        title: "Successo",
                        text: "Tabella creata con successo!",
                        icon: "success",
                        confirmButtonText: "OK"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.reload();
                        }
                    });
                } else {
                    showError(result.message || "Errore durante la creazione della tabella");
                }
            })
            .catch((error) => {
                showError("Errore durante la creazione della tabella: " + error);
            });
    }

    async function handleImport(btn) {
        const fileInput = btn.closest(".panel-body").querySelectorAll("input[type=file]");
        const file = fileInput[0].files[0];
        const file_att = fileInput[1].files[0];
        const type = btn.dataset.type;

        if (!file) {
            Swal.fire({
                icon: "warning",
                title: stringTranslated["title_warning"],
                text: stringTranslated["select_csv_before_import"]
            });
            return;
        }

        //invio il file al server tramite fetch
        const formData = new FormData();
        formData.append("ajax", 1);
        formData.append("action", "parseCsv");
        formData.append("file", file);
        formData.append("file_att", file_att);

        //Visualizzo il messaggio di importazione del file
        Swal.fire({
            html: "Lettura file CSV in corso...",
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        readFileCSV(formData, type);
    }

    async function readFileCSV(formData, type) {
        const response = await fetch(adminURL, {
            method: "POST",
            body: formData
        });

        console.log(response);

        if (!response.ok) {
            Swal.fire({
                icon: "error",
                title: stringTranslated["title_error"],
                text: "Errore durante l'importazione del file"
            });
            return false;
        }

        const json = await response.json();

        if (json.success) {
            console.log("TABELLA ", type, "RIGHE", json.data.length);
            console.log("TABELLA ", type + "_attachment", "RIGHE", json.data_att.length);

            truncateTable(json.data, type, json.data.length);
            truncateTable(json.data_att, type + "_attachment", json.data_att.length);
        } else {
            showError(json.message);
        }
    }

    async function truncateTable(data, type, remains) {
        return readChunk(data, type, remains);

        console.log("Truncate table", type);

        const response = await fetch(adminURL, {
            method: "POST",
            body: JSON.stringify({
                ajax: 1,
                action: "truncateTable",
                tableName: type
            })
        });

        const json = await response.json();

        if (json.success) {
            console.log("Tabella troncata con successo");
            readChunk(data, type, remains);
        } else {
            showError(json.message);
        }
    }

    async function readChunk(data, type, remains) {
        console.log("readChunk", data.length, "Table: " + type, "Remains: " + remains);

        if (data.length == 0) {
            console.log("Fine importazione");
            Swal.update({
                icon: "success",
                title: stringTranslated["import_done"],
                html: "",
                showConfirmButton: true
            });
            Swal.hideLoading();
            return true;
        }

        const chunk = data.splice(0, 1000);

        if (chunk.length > 0) {
            // Aggiorno la finestra esistente con il nuovo progresso
            Swal.update({
                icon: "success",
                title: stringTranslated["title_success"],
                html: createProgress(remains)
            });
            Swal.showLoading();

            const response = await fetch(adminURL, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: JSON.stringify({
                    ajax: 1,
                    action: "importCsv",
                    chunk: JSON.stringify(chunk),
                    type: type
                })
            });

            if (!response.ok) {
                return;
            }

            const json = await response.json();

            if (json.success) {
                console.log("Lettura completata, proseguo con la prossima parte", data.length);
                readChunk(data, type, data.length);
            } else {
                showError(json.message);
            }
        }
    }

    function createProgress(remains) {
        return $("<div>").append(`<h3>Importazione in corso...</h3>`).append(`<span>Righe rimanenti: ${remains}</span>`).prop("outerHTML");
    }

    function updateProgressBar(progress) {
        if (!importModal.is(":visible")) {
            importModal.modal("show");
        }

        progressBar.style.width = progress + "%";
        progressSpan.textContent = Math.round(progress) + "%";
        importStatus.textContent = stringTranslated["importing"];

        if (progress >= 100) {
            importStatus.textContent = stringTranslated["import_done"];
            setTimeout(() => {
                importModal.modal("hide");
            }, 2000);
        }
    }

    function showError(message) {
        Swal.fire({
            title: stringTranslated["title_error"],
            text: message,
            icon: "error",
            confirmButtonText: "OK"
        });
    }

    return {
        init: init
    };
})();
