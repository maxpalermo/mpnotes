function getFormData(data) {
    const formData = new FormData();
    formData.append("ajax", 1);
    Object.entries(data).forEach(([key, value]) => {
        if (value === undefined || value === null) return;
        formData.append(key, String(value));
    });

    return formData;
}

async function request(endpoint, data) {
    const response = await fetch(endpoint, {
        method: "post",
        body: data,
    });

    if (!response.ok) {
        showErrorMessage("Errore nella chiamata API " + data.action);
        return false;
    }

    const json = await response.json();

    if (json.success != true) {
        showErrorMessage("Errore nella ricezione dei dati.");
        return false;
    }

    return json;
}

async function loadCustomerNotes() {
    const endpoint = mpnote_endpoint;
    const formData = getFormData({
        action: "getCustomerNotes",
        id_customer: mpnote_id_customer,
    });
    const data = await request(endpoint, formData);

    const card = document.getElementById("customer-notes-card");
    if (card) {
        const panelBody = card.querySelector(".card-body");
        panelBody.innerHTML = data.html;
    }
}

async function closeMpNotePanel() {
    await loadCustomerNotes();
}

async function showMpNotePanel(id_customer = 0, id_mpnote = 0) {
    const panel = document.getElementById("customer-notes-card");
    if (panel) {
        const formData = getFormData({
            action: "getCustomerNotePanel",
            id_customer: id_customer,
            id_mpnote: id_mpnote,
        });

        const data = await request(mpnote_endpoint, formData);

        panel.querySelector(".card-body").innerHTML = data.html;
        const content = panel.querySelector("textarea");
        if (content) {
            content.focus();
        }
    }
}

async function addNoteDetails(btn) {
    const form = btn.closest("div.form-control");
    const id_customer = form.dataset.idCustomer;
    const id_mpnote = form.dataset.idMpnote;
    const content = form.querySelector("textarea").value;
    const formData = getFormData({
        action: "addCustomerNote",
        id_customer: id_customer,
        id_mpnote: id_mpnote,
        content: content,
    });

    const response = await fetch(mpnote_endpoint, {
        method: "POST",
        body: formData,
    });

    if (!response.ok) {
        showErrorMessage("Errore durante la chiamata API");
        return false;
    }

    const data = await response.json();
    if (data.success != true) {
        showErrorMessage("Errore nella ricezione dei dati.");
        return false;
    }

    await loadCustomerNotes();

    showNoticeMessage("Nota aggiunta con successo.");
}

async function editNoteDetails(idMpNote) {
    showMpNotePanel(0, idMpNote);
}

async function deleteNote(idMpNote) {
    if (!confirm("Eliminare la nota selezionata?")) {
        return false;
    }

    const data = await request(
        mpnote_endpoint,
        getFormData({
            action: "deleteNote",
            id_mpnote: idMpNote,
        }),
    );

    if (data.success != true) {
        showErrorMessage(data.message);
        return false;
    }

    showNoticeMessage(data.message);
    await loadCustomerNotes();
}
