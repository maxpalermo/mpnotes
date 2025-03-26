<div class="panel">
    <div class="panel-heading">
        <i class="material-icons">flag</i> Gestione Tipi di nota e Attributi
    </div>
    <div class="panel-body">
        <form id="flag-note-form" class="form-horizontal">
            <div class="form-group">
                <label class="control-label col-lg-3">Nome</label>
                <div class="col-lg-3 col-sm-5">
                    <input type="text" name="name" id="flag-name" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">Colore</label>
                <div class="col-lg-3 col-sm-5">
                    <input type="color" name="color" id="flag-color" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">Icona (Material Icons)</label>
                <div class="col-lg-3 col-sm-6">
                    <div class="d-flex justify-content-start">
                        <input type="text" name="icon" id="flag-icon" class="form-control chosen mr-2" required readonly>
                        <span class="icon-preview ml-2" style="border: 1px solid #ccc; padding: 5px; border-radius: 5px"><i id="icon-preview" class="material-icons">{if isset($icons[0])}{$icons[0]}{/if}</i></span>
                    </div>
                    <p class="help-block">Seleziona un'icona da utilizzare</p>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">Elenco Icone</label>
                <div class="col-lg-8">
                    <div class="icon-grid">
                        <div class="button-group-container">
                            {foreach $icons as $icon}
                            <div class="icon-button">
                                <input type="radio" name="icon_selection" id="icon_{$icon}" value="{$icon}" class="icon-radio" title="{$icon}">
                                <label for="icon_{$icon}" class="icon-label" title="{$icon}">
                                    <i class="material-icons">{$icon}</i>
                                </label>
                            </div>
                            {/foreach}
                        </div>
                    </div>
                    <p class="help-block">Seleziona un'icona da Material Icons da utilizzare</p>
                    <script type="text/javascript">
                        document.addEventListener('DOMContentLoaded', function() {
                            const iconRadio = document.querySelectorAll('.icon-radio');
                            iconRadio.forEach(radio => {
                                radio.addEventListener('change', function() {
                                    const selectedIcon = this.value;
                                    const color = document.getElementById('flag-color').value;
                                    document.getElementById('icon-preview').textContent = selectedIcon;
                                    document.getElementById('icon-preview').style.color = color;
                                    document.getElementById('flag-icon').value = selectedIcon;
                                });
                            });
                            const colorInput = document.getElementById('flag-color');
                            colorInput.addEventListener('change', function() {
                                const selectedIcon = document.getElementById('flag-icon').value;
                                const color = this.value;
                                document.getElementById('icon-preview').textContent = selectedIcon;
                                document.getElementById('icon-preview').style.color = color;
                            });
                        });
                    </script>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">Tipo</label>
                <div class="col-lg-3 col-sm-5">
                    <select name="type" id="flag-type" class="form-control">
                        <option value="NOTE">Nota</option>
                        <option value="FLAG">Attributo</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">Permetti aggiornamento</label>
                <div class="col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="allow_update" id="allow_update_on" value="1" checked>
                        <label for="allow_update_on">SI</label>
                        <input type="radio" name="allow_update" id="allow_update_off" value="0">
                        <label for="allow_update_off">NO</label>
                        <a class="slide-button btn"></a>
                    </span>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">Permetti allegati</label>
                <div class="col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="allow_attachments" id="allow_attachments_on" value="1" checked>
                        <label for="allow_attachments_on">SI</label>
                        <input type="radio" name="allow_attachments" id="allow_attachments_off" value="0">
                        <label for="allow_attachments_off">NO</label>
                        <a class="slide-button btn"></a>
                    </span>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">Attivo</label>
                <div class="col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="active" id="active_on" value="1" checked>
                        <label for="active_on">SI</label>
                        <input type="radio" name="active" id="active_off" value="0">
                        <label for="active_off">NO</label>
                        <a class="slide-button btn"></a>
                    </span>
                </div>
            </div>
            <div class="panel-footer">
                <button type="submit" class="btn btn-default pull-right" id="save-flag-note">
                    <i class="process-icon-save"></i> Salva
                </button>
            </div>
        </form>

        <div class="table-responsive-row clearfix">
            {$table}
        </div>
    </div>
</div>