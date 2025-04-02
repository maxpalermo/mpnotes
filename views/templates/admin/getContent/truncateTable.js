async function truncateTables() {
    const confirm = await swalConfirm("Troncare le tabelle?");
    if (!confirm) {
        return;
    }

    abortController = new AbortController();
    signal = abortController.signal;

    const formData = new FormData();
    formData.append("ajax", 1);
    formData.append("action", "truncateTables");

    try {
        progressData = new ProgressData(100, progressBar, statusText, progressPercent);

        progressData.start();

        const response = await fetch(frontController, {
            method: "POST",
            body: formData,
            signal: signal
        });

        if (!response.ok) {
            throw new Error("Errore durante la troncatura delle tabelle");
        }

        const data = await response.json();
        if (data.success) {
            await swalSuccess("Tabelle svuotate con successo");
        } else {
            await swalError("Errore durante lo svuotamento delle tabelle");
        }
    } catch (error) {
        await swalError("Errore durante lo svuotamento delle tabelle");
    } finally {
        progressData.end();
    }
}
