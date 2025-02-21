<link rel="stylesheet" href="{$moduleDir}views/css/toast.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css">
<script src="https://unpkg.com/htmx.org@1.9.4"></script>

{* Translations and configuration *}
<script type="text/javascript">
    window.adminURL = "{$adminURL}";
    window.stringTranslated = {
        title_success: "{l s='Operazione eseguita' mod='mpnotes'}",
        title_warning: "{l s='Attenzione' mod='mpnotes'}",
        title_error: "{l s='Errore' mod='mpnotes'}",
        select_csv_before_import: "{l s='Seleziona un file CSV prima di importare.' mod='mpnotes'}",
        import_done: "{l s='Importazione completata!' mod='mpnotes'}",
        importing: "{l s='Importazione in corso...' mod='mpnotes'}",
        select_file: "{l s='Scegli un file' mod='mpnotes'}"
    };
</script>

{* Import handler module *}
<script src="{$moduleDir}views/js/import-handler.js"></script>
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        ImportHandler.init();
    });
</script>
<div class="bootstrap">
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-cogs"></i> {l s='MP Notes - Gestione Importazioni' mod='mpnotes'}
        </div>
        <div class="panel-body">
            <div class="row">
                {foreach $importPanels as $panel}
                    <form id="importCustomerForm_{$panel.tablename}" class="form-horizontal" action="{$panel.action}" method="post" enctype="multipart/form-data">
                        <div class="col-md-4">
                            <div class="panel">
                                <div class="panel-heading toast-container">
                                    <i class="material-icons">{$panel.icon}</i> {$panel.title}
                                    <span class="toast toast-{if $panel.exists}success{else}danger{/if} toast-top-right show">
                                        {if $panel.exists}
                                            Totale record: {$panel.rows}
                                        {else}
                                            {l s='Tabella mancante' mod='mpnotes'}
                                        {/if}
                                    </span>
                                    {if !$panel.exists}
                                        <button type="button" class="btn btn-success btn-sm pull-right create-table" data-table="{$panel.tablename}" style="margin-right: 10px;">
                                            <i class="icon-plus"></i> {l s='Crea Tabella' mod='mpnotes'}
                                        </button>
                                    {/if}
                                </div>
                                <div class="panel-body">
                                    <form class="form-horizontal" action="{$panel.action}" method="post" enctype="multipart/form-data">
                                        <div class="form-group">
                                            <label class="control-label col-lg-3">{l s='File CSV' mod='mpnotes'}</label>
                                            <div class="col-lg-9">
                                                <div class="custom-file-upload">
                                                    <input type="file" name="import_csv_file" class="file-upload-input" accept=".csv" required>
                                                    <label class="file-upload-label">
                                                        <i class="icon-file-text-o"></i>
                                                        <span class="file-upload-text">{l s='Scegli un file' mod='mpnotes'}</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        {if $tablename != 'customer'}
                                            <div class="form-group">
                                                <label class="control-label col-lg-3">{l s='File CSV allegati' mod='mpnotes'}</label>
                                                <div class="col-lg-9">
                                                    <div class="custom-file-upload">
                                                        <input type="file" name="import_csv_file_att" class="file-upload-input" accept=".csv" required>
                                                        <label class="file-upload-label">
                                                            <i class="icon-file-text-o"></i>
                                                            <span class="file-upload-text">{l s='Scegli un file' mod='mpnotes'}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        {/if}
                                        <div class="form-group">
                                            <div class="col-lg-9 col-lg-offset-3">
                                                <button type="button" class="btn btn-primary btn-lg importBtn" data-type="{$panel.tablename}">
                                                    <i class="icon-upload"></i>{$panel.description}
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </form>
                {/foreach}
            </div>
        </div>
    </div>
</div>

<!-- Progress Modal -->
<div class="modal fade" id="importProgress" tabindex="-1" role="dialog" aria-labelledby="importProgressLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="importProgressLabel">{l s='Importazione in corso...' mod='mpnotes'}</h4>
            </div>
            <div class="modal-body">
                <div class="progress">
                    <div class="progress-bar progress-bar-striped active" role="progressbar" style="width: 0%">
                        <span>0%</span>
                    </div>
                </div>
                <p id="importStatus" class="text-center mt-2"></p>
            </div>
        </div>
    </div>
</div>