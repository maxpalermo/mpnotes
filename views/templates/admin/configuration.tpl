<div class="panel">
    <div class="panel-heading">
        <i class="material-icons">settings</i> {l s='Import Messages' mod='mpnotes'}
    </div>
    <div class="panel-body">
        <div class="import-container">
            <div class="import-buttons">
                <button type="button" id="import-customer-messages" class="btn btn-info">
                    <div class="material-icons">person</div>
                    <span>{l s='Importa messaggi cliente' mod='mpnotes'}</span>
                </button>
                <button type="button" id="import-order-messages" class="btn btn-warning">
                    <div class="material-icons">shopping_cart</div>
                    <span>{l s='Importa messaggi ordini' mod='mpnotes'}</span>
                </button>
                <button type="button" id="import-embroidery-messages" class="btn btn-success">
                    <div class="material-icons">local_offer</div>
                    <span>{l s='Importa messaggi ricami' mod='mpnotes'}</span>
                </button>
                <button type="button" id="truncate-tables" class="btn btn-danger">
                    <div class="material-icons">delete</div>
                    <span>{l s='Svuota tabelle' mod='mpnotes'}</span>
                </button>
            </div>

            <div class="progress-container">
                <div class="progress-header">
                    <h4>Operazione in corso</h4>
                    <span id="progressPercent">0%</span>
                </div>
                <progress id="progressBar" value="0" max="100"></progress>
                <p id="statusText" class="status-text">Pronto per iniziare...</p>
                <div class="progress-buttons d-flex justify-content-center">
                    <button id="cancelButton" class="btn btn-danger">Annulla</button>
                </div>
                <div class="d-flex justify-content-end">
                    <div class="material-icons hourglass-animation">hourglass_empty</div>
                </div>
                <div id="resultContainer" class="result-container" style="display:none;">
                    <h5>Risultato:</h5>
                    <pre id="resultOutput"></pre>
                </div>
            </div>
        </div>
    </div>
</div>


{include file="./getContent/flags.tpl"}

<script>
    let operation = null;
    let abortController = null;
    let signal = null;
    let progressData = null;
    const frontController = '{$frontController}';

    document.addEventListener('DOMContentLoaded', async () => {
        const progressBar = document.getElementById('progressBar');
        const progressPercent = document.getElementById('progressPercent');
        const statusText = document.getElementById('statusText');
        const resultContainer = document.getElementById('resultContainer');
        const truncateBtn = document.getElementById('truncate-tables');
        const customerBtn = document.getElementById('import-customer-messages');
        const orderBtn = document.getElementById('import-order-messages');
        const embroideryBtn = document.getElementById('import-embroidery-messages');
        const cancelButton = document.getElementById('cancelButton');
        const saveFlagNote = document.getElementById('save-flag-note');

        progressData = new ProgressData(100, progressBar, statusText, progressPercent);

        saveFlagNote.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            await saveFlagNote();
        });

        cancelButton.addEventListener("click", () => {
            if (operation) {
                operation.cancel();
            } else {
                console.warn('Nessuna operazione in corso da annullare');
            }
        });

        customerBtn.addEventListener('click', async () => { await importCustomerList(); });
        orderBtn.addEventListener('click', async () => { await importOrdersList(); });
        embroideryBtn.addEventListener('click', async () => { await importEmbroideryList(); });

        truncateBtn.addEventListener('click', async () => {
            await truncateTables();
        });
    });

    {include file="./getContent/truncateTable.js"}
    {include file="./getContent/importCustomerList.js"}
    {include file="./getContent/importOrdersList.js"}
    {include file="./getContent/importEmbroideryList.js"}
    {include file="./getContent/doImport.js"}



    async function saveFlagNote() {
        const confirm = await swalConfirm("Salvare il record?");
        if (!confirm) return;

        const formData = new FormData(document.getElementById('flag-note-form'));
        formData.append("ajax", 1);
        formData.append("action", "saveFlagNote");

        const response = await fetch(
            frontController, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams(formData)
            }
        );

        const data = await response.json();

        if (data.success) {
            swal.fire({
                icon: 'success',
                title: 'Successo',
                text: data.message,
                showConfirmButton: false,
                timer: 1500
            });

            updateTable();

        } else {
            swal.fire({
                icon: 'error',
                title: 'Errore',
                text: data.message,
                showConfirmButton: false,
                timer: 1500
            });
        }

        return false;
    }



    async function updateTable() {
        const response = await fetch(
            frontController, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    ajax: 1,
                    action: 'updateTableNote'
                })
            }
        );

        if (!response.ok) {
            progressData.end();
            throw new Error('Errore durante l\'esecuzione dell\'operazione');
        }

        const data = await response.json();
        const table = document.getElementById('flags-notes-table');
        table.closest("div").innerHTML = data.html;
    }
</script>