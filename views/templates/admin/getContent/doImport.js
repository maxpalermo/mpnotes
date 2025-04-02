async function doImport(list, type, progressData, id_flag) {
    if (list.length == 0) {
        progressData.end();

        swalSuccess("Importazione completata.");
        return;
    }

    abortController = new AbortController();
    signal = abortController.signal;
    const chunk = list.splice(0, 1000);

    try {
        progressData.setMessage("Importazione in corso...");
        const response = await fetch(frontController, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
                "X-Requested-With": "XMLHttpRequest"
            },
            body: new URLSearchParams({
                ajax: 1,
                action: "importNotes",
                type: type,
                id_flag: id_flag,
                list: JSON.stringify(chunk)
            }),
            signal: signal
        });

        if (!response.ok) {
            progressData.end();
            swalError("Errore durante l'esecuzione dell'operazione");
            return;
        }

        const data = await response.json();
        if (data.success) {
            progressData.update(chunk.length);
            if (list.length > 0) {
                doImport(list, type, progressData, id_flag);
            } else {
                swalSuccess("Importazione completata.");
            }
        }
    } catch (error) {
        if (!(error.name === "AbortError")) {
            progressData.end();

            swalError(error.message);
            return;
        }

        console.log("Operazione annullata");
        progressData.end();
        return;
    }
}
