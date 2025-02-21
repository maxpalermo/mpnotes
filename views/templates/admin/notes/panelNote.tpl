<div class="modal fade" id="panelNoteModal" tabindex="-1" role="dialog" aria-labelledby="panelNoteModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="panelNoteModalLabel">{$note.title}</h5>
                    <p>Operatore: {$note.employee}</p>
                    {if $note.deleted}
                        <p class="text-danger">{l s='Nota Eliminata' mod='mpnotes'}</p>
                    {/if}
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="panelNoteForm">
                    <input type="hidden" name="noteType" id="noteType" value="{$note.type|intval}">
                    <input type="hidden" name="noteId" id="noteId" value="{$note.id_mp_note|intval}">
                    <div class="form-group">
                        <label for="noteText">{l s='Nota' mod='mpnotes'}</label>
                        <textarea class="form-control" id="noteText" rows="3" required>{$note.note}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="noteAlert">{l s='Tipo' mod='mpnotes'}</label>
                        <select class="form-control" id="noteAlert">
                            <option value="1" {if $note.alert==1}selected{/if}>{l s='Informazione' mod='mpnotes'}</option>
                            <option value="2" {if $note.alert==2}selected{/if}>{l s='Importante' mod='mpnotes'}</option>
                            <option value="3" {if $note.alert==3}selected{/if}>{l s='Avviso' mod='mpnotes'}</option>
                        </select>
                    </div>
                    <div class="d-flex justify-content-center">
                        <div class="col-md-3">
                            <label>{l s='Nota stampabile' mod='mpnotes'}</label>
                            <div class="ps-switch ps-switch-sm ps-togglable-row">
                                <input type="radio" name="notePrintable" id="notePrintableFalse" value="0" {if $note.printable==0}checked{/if}>
                                <label for="notePrintableFalse"></label>
                                <input type="radio" name="notePrintable" id="notePrintableTrue" value="1" {if $note.printable==1}checked{/if}>
                                <label for="notePrintableTrue"></label>
                                <span class="slide-button"></span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>{l s='Nota della chat' mod='mpnotes'}</label>
                            <div class="ps-switch ps-switch-sm ps-togglable-row">
                                <input type="radio" name="noteChat" id="noteChatFalse" value="0" {if $note.chat==0}checked{/if}>
                                <label for="noteChatFalse"></label>
                                <input type="radio" name="noteChat" id="noteChatTrue" value="1" {if $note.chat==1}checked{/if}>
                                <label for="noteChatTrue"></label>
                                <span class="slide-button"></span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            {if isset($note.attachments) && $note.attachments}
                <div class="modal-body" id="attachments-div">
                    <div class="form-group mb-2">
                        <div class="row" style="display: flex; justify-content: space-between;">
                            <div class="col">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="newAttachment" name="newAttachment">
                                    <label class="custom-file-label" for="newAttachment">{l s='Scegli file' mod='mpnotes'}</label>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm mt-2" id="addAttachment" data-type="embroidery">
                                    {l s='Aggiungi allegato' mod='mpnotes'}
                                </button>
                            </div>
                            <div class="col text-center">
                                <div class="preview-container" style="display: none;">
                                    <img id="attachmentPreview" src="" alt="Anteprima" style="max-width: 100%; max-height: 200px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class=" col-md-12">
                            {if isset($note.attachments) && $note.attachments}
                                <div class="carousel">
                                    {foreach from=$note.attachments item=attachment name=attachments}
                                        <div class="carousel-item">
                                            <div class="carousel-image">
                                                {if $attachment.filename|regex_replace:"/.*\.(\w+)$/":"$1"|in_array:['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']}
                                                    <img data-id="{$attachment.id_mp_note_attachment|intval}" class="thumbnail-img" src="{$uploadDir}{$attachment.filename|basename}" alt="{$attachment.filetitle}">
                                                {else}
                                                    <span>{$attachment.filename|basename}</span>
                                                {/if}
                                            </div>
                                            <div class="carousel-caption">
                                                <button type="button" class="btn btn-danger btn-sm delete-attachment" data-id="{$attachment.id_mp_note_attachment}" data-type="attachment">
                                                    {l s='Elimina' mod='mpnotes'}
                                                </button>
                                            </div>
                                        </div>
                                    {/foreach}
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>
            {/if}
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{l s='Chiudi' mod='mpnotes'}</button>
                {if $note.id_mp_note == 0}
                    <button type="button" class="btn btn-primary" id="btnSaveNote">{l s='Salva' mod='mpnotes'}</button>
                {/if}
            </div>
        </div>
    </div>
</div>