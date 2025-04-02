<div class="card note-card" data-id_note="{$note->id}" data-id_order="{$note->id_order}" data-id_customer="{$note->id_customer}">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
            <i class="material-icons mr-2">{if $note->id > 0}edit_note{else}note_add{/if}</i>
            {if $note->id > 0}Modifica Nota #{$note->id}{else}Nuova Nota{/if}
        </h3>
        <div class="note-meta d-flex">
            {if $note && $note->id_order > 0}
                <div class="badge badge-info mr-2">
                    <i class="material-icons">shopping_cart</i> Ordine #{$note->id_order}
                </div>
            {/if}
            {if $note && $note->id_customer > 0}
                <div class="badge badge-info">
                    <i class="material-icons">person</i> Cliente #{$note->id_customer}
                </div>
            {/if}
        </div>
    </div>

    <div class="card-body">
        <form id="noteForm" class="form">
            <!-- Content Field -->
            <input type="hidden" name="noteTypeId" value="{$note->id_note_type|intval}">
            <input type="hidden" name="noteId" value="{$note->id|intval}">
            <input type="hidden" name="orderId" value="{$note->id_order|intval}">
            <input type="hidden" name="customerId" value="{$note->id_customer|intval}">
            <input type="hidden" name="employeeId" value="{$note->id_employee|intval}">
            <div class="form-group">
                <label for="note_content" class="form-control-label required">
                    <i class="material-icons align-middle mr-1">subject</i> Contenuto
                </label>
                <textarea id="note_content" name="content" class="form-control" rows="5" required>{if isset($note)}{$note->content}{/if}</textarea>
                <small class="form-text text-muted">Inserisci il contenuto della nota</small>
            </div>

            <!-- Gravity Field -->
            <div class="form-group">
                <label for="note_gravity" class="form-control-label">
                    <i class="material-icons align-middle mr-1">priority_high</i> Gravità
                </label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <i class="material-icons gravity-icon" id="gravityIcon" style="font-size: 1rem;">info</i>
                        </span>
                    </div>
                    {assign var=gravities value=['info', 'warning', 'error', 'success']}
                    <select id="note_gravity" name="gravity" class="form-control">
                        {foreach from=$gravities item=gravity}
                            <option value="{$gravity}" {if $note->gravity == $gravity}selected{/if}>
                                {$gravity|capitalize}
                            </option>
                        {/foreach}
                    </select>
                </div>
            </div>

            <!-- Flags Field -->
            <div class="form-group">
                <label class="form-control-label">
                    <i class="material-icons align-middle mr-1">flag</i> Flags
                </label>
                <div class="flags-container">
                    {foreach from=$note->flags item=flag}
                        <div class="row">
                            <div class="col-md-12">
                                <span>{$flag.name}</span>
                            </div>
                            <div class="col-md-12">
                                <div class="ps-switch ps-switch-sm ps-switch-nolabel ps-switch-center ps-togglable-row" data-toggle-url="/admin_shop/index.php/sell/customers/105414/toggle-status?_token=1t_-9r0bTOF6N59iwWC8DuErNDRvELbMtgq6T7NvkEA">
                                    <input type="radio" name="flag-note-{$flag.id}" id="input-false-flag-note-{$flag.id}" value="0" {if $flag.value == 0}checked{/if}>
                                    <label for="input-false-flag-note-{$flag.id}">Off</label>
                                    <input type="radio" name="flag-note-{$flag.id}" id="input-true-flag-note-{$flag.id}" value="1" {if $flag.value == 1}checked{/if}>
                                    <label for="input-true-flag-note-{$flag.id}">On</label>
                                    <span class="slide-button"></span>
                                </div>
                            </div>
                        </div>
                    {/foreach}
                </div>
            </div>
        </form>
    </div>

    <div class="card-footer d-flex justify-content-between">
        <button type="button" id="btnCancelNoteForm" class="btn btn-outline-secondary">
            <i class="material-icons align-middle mr-1">close</i> Annulla
        </button>
        {if $note->allowUpdate() || $note->id == 0}
            <button type="button" id="btnSaveNote" class="btn btn-primary">
                <i class="material-icons align-middle mr-1">save</i> Salva
            </button>
        {/if}
    </div>
</div>