{if isset($flash) && $flash}
    <div class="alert alert-success alert-dismissible show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        <strong>
            {foreach $flash as $msg}
                <p>{$msg}</p>
            {/foreach}
        </strong> 
    </div>
{/if}
<div class="panel">
    <div class="panel-heading">
        <i class="material-icons">settings</i> {l s='Import Messages' mod='mpnotes'}
    </div>
    <div class="panel-body">
        <form method="post" id="form-notes">
            <div class="form-group">
                <label for="endpoint">URL</label>
                <input type="text" name="endpoint" class="form-control" value="{$endpoint}">
            </div>
            <div class="form-group">
                <label for="connector_token">Token</label>
                <input type="text" name="connector_token" class="form-control fixed-width-xxl" value="{$connector_token}">
            </div>
            <button class="btn btn-default" type="submit" name="submitButton" value="save">
                <i class="icon icon-save icon-2x"></i>
                <br>
                <span>Salva</span>
            </button>
            <button class="btn btn-default" type="submit" name="submitButton" value="truncate">
                <i class="icon icon-trash icon-2x" style="color: var(--danger);"></i>
                <br>
                <span>Tronca tabelle</span>
            </button>
        </form>
    </div>

    <div class="panel-body">
        <div class="row">
            {foreach $curl as $key=>$value}
            <div class="col-md-2" data-type="panelImport">
                <div class="panel panel-info" style="min-height: 200px;">
                    <div class="panel-heading">
                        <p>Tabella<br>{$key}</p>
                        <div class="badge" style="font-size: 0.75rem;">{$value.type}</div>
                    </div>
                    <div class="panel-body">
                        <p class="text-center" style="font-size: 2rem;">{if isset($value.remote[0].total)}{$value.remote[0].total}{else}0{/if}</p>
                        <p class="text-center text-info" style="font-size: 1rem;" data-id="displayImport">{if isset($value.recordCount)}{$value.recordCount}{else}0{/if}</p>
                    </div>
                    <div class="panel-footer">
                        <button class="btn btn-default pull-right" type="button" name="import-{$value.type}" data-table="{$key}" data-type="import" data-offset="0" data-limit="5000">
                            <i class="process-icon-download"></i>
                            <span>{l s='Importa' mod='mpnotes'}</span>
                        </button>
                    </div>
                </div>
            </div>
            {/foreach}
        </div>
    </div>
</div>

<script>
    let operation = null;
    let abortController = null;
    let signal = null;
    let progressData = null;
    const adminControllerUrl = "{$adminControllerUrl}";
    const endpoint = "{$endpoint}";

    async function getTablesCount(tablename)
    {
        const endpoint = document.getElementById('endpoint').value;
        const connector_token = document.getElementById('connector_token').value;
        const query = `SELECT COUNT(*) FROM ${ tablename }`;
        const formData = new FormData();

        formData.append('action', 'setQuery');
        formData.append('ajax', 1);
        formData.append('query', query);
        formData.append('connector_token', connector_token);

        const response = await fetch(endpoint, {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            alert("Errore nella chiamata API");
        }

        const data = await response.json();
        const countRows = data.count;

        return countRows;
    }

    function onSubmitForm(event) {
        if (!confirm("Procedere con l'operazione?")) {
            event.preventDefault();
            return false;
        }
    }

    async function onClickBtn(event)
    {
        const el = event.target.closest("button");
        if (el) {
            if (!confirm("Procedere all'importazione dei dati?")) {
                return false;
            }
            
            const displayImport = el.closest("div[data-type=panelImport]").querySelector("p[data-id=displayImport]");
            
            if (displayImport) {
                displayImport.textContent = "In esecuzione...";
                await new Promise(requestAnimationFrame);
            }
            
            await importTableData(el);
        }
    }

    async function importTableData(el)
    {
        const table = el.dataset.table;
            
        operation = "import";
        abortController = new AbortController();
        signal = abortController.signal;
        
        const offset = el.dataset.offset;
        const limit = el.dataset.limit;
        const formData = new FormData();
        formData.append("ajax", 1);
        formData.append("action", "importV16");
        formData.append("table", table);
        formData.append("offset", offset);
        formData.append("limit", limit);

        const response = await fetch(adminControllerUrl, {
            method: 'POST',
            body: formData,
            signal: signal
        });

        if (!response.ok) {
            alert("Errore nella chiamata API");
            return false;
        }

        const data = await response.json();
        
        const displayImport = el.closest("div[data-type=panelImport]").querySelector("p[data-id=displayImport]");
        if (displayImport) {
            displayImport.textContent = `Importati ${ data.offset } record`;
            await new Promise(requestAnimationFrame);
        }

        if (data.success && !data.done) {
            el.dataset.offset = data.offset;
            el.dataset.limit = data.limit;
            await importTableData(el);
        } else { 
            alert("Operazione eseguita");
        }
    }

    document.addEventListener('DOMContentLoaded', async () => {
        const btnsImport = document.querySelectorAll("button[data-type=import]")
        const form = document.querySelector("#form-notes");

        if (btnsImport) {
            btnsImport.forEach(btn => {
                btn.addEventListener('click', onClickBtn);
            });
        }

        if (form) {
            form.addEventListener('submit', onSubmitForm);
        }
    });
</script>