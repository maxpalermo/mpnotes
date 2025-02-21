<div class="modal fade" id="noteCustomerModal" tabindex="-1" role="dialog" aria-labelledby="noteCustomerModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="noteCustomerModalLabel">{l s='Nota Cliente' mod='mpnotes'}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="customerNoteForm">
                    <input type="hidden" name="noteId" id="noteId" value="{$note.id_mp_note_customer|intval}">
                    <div class="form-group">
                        <label for="noteText">{l s='Nota' mod='mpnotes'}</label>
                        <textarea class="form-control" id="noteText" rows="3" required>{$note.note}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="noteType">{l s='Tipo' mod='mpnotes'}</label>
                        <select class="form-control" id="noteType">
                            <option value="1" {if $note.type==1}selected{/if}>{l s='Informazione' mod='mpnotes'}</option>
                            <option value="2" {if $note.type==2}selected{/if}>{l s='Importante' mod='mpnotes'}</option>
                            <option value="3" {if $note.type==3}selected{/if}>{l s='Avviso' mod='mpnotes'}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="noteEmployee">{l s='Operatore' mod='mpnotes'}</label>
                        <input readonly type="text" class="form-control" id="noteEmployee" value="{$note.employee}">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{l s='Chiudi' mod='mpnotes'}</button>
                {if $note.id_mp_note_customer == 0}
                    <button type="button" class="btn btn-primary" id="saveNoteCustomer">{l s='Salva' mod='mpnotes'}</button>
                {/if}
            </div>
        </div>
    </div>
</div>