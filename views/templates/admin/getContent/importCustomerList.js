async function importCustomerList() {
    const confirm = await swalConfirm("Importare i messaggi cliente?");
    if (!confirm) {
        return;
    }

    const id_note_type = await swalInput("Inserisci l'id del tipo di nota");
    if (!id_note_type) {
        return;
    }

    console.log("TIPO NOTA", id_note_type);

    abortController = new AbortController();
    signal = abortController.signal;

    const formData = new FormData();
    formData.append("ajax", 1);
    formData.append("action", "getMessageList");
    formData.append("type", "customer");

    try {
        progressData.setTotal(100);
        progressData.start();
        progressData.setMessage("Ricerca Messaggi cliente");

        const response = await fetch(frontController, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
                "X-Requested-With": "XMLHttpRequest"
            },
            body: new URLSearchParams(formData),
            signal: signal
        });

        if (!response.ok) {
            swalError("Errore durante l'esecuzione dell'operazione");
            return;
        }

        progressData.end();

        const data = await response.json();
        if (data.list.length > 0) {
            progressData.setTotal(data.list.length);

            progressData.start();
            console.log("Importazione messaggi cliente", data.list.length);
            doImport(data.list, "customer", progressData, id_note_type);
        } else {
            Swal.fire({
                icon: "info",
                title: "Nessun messaggio cliente trovato",
                showConfirmButton: false,
                timer: 1500
            });
            return;
        }
    } catch (error) {
        if (progressData) {
            progressData.stop(error);
        }
        if (abortController) {
            abortController.abort();
        }
        if (statusText) {
            if (error.name === "AbortError") {
                statusText.textContent = "Operazione annullata";
            } else {
                statusText.textContent = "Errore durante l'esecuzione dell'operazione";
            }
        }
    }
}
