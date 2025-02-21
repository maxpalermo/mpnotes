<div class="modal fade" id="noteOrderModal" tabindex="-1" role="dialog" aria-labelledby="noteOrderModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <style>
        .carousel {
            display: flex;
            justify-content: start;
            overflow-x: auto;
            overflow-y: hidden;
            height: 260px;
            width: 100%;
        }

        .carousel-item {
            display: block;
            margin-right: 1rem;
            width: 160px;
            height: 240px;
            max-width: 160px;
            max-height: 240px;
        }

        .carousel-item .carousel-image {
            border: 1px solid #ddd;
            overflow-x: hidden;
            white-space: nowrap;
            cursor: pointer;
            padding: 2px;
            display: flex;
            align-items: center;
            justify-content: column;
            width: 160px;
            height: 240px;
            max-width: 160px;
            max-height: 240px;
        }

        .carousel-item .carousel-image img {
            max-width: 160px;
            max-height: 240px;
            object-fit: contain;
        }

        .carousel-caption {
            display: block;
            position: absolute;
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            text-align: center;
            padding: 10px;
            left: 0;
            bottom: 0;
            width: 100%;
        }
    </style>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title" id="noteOrderModalLabel">
                    <p>{l s='Nota Ordine del' mod='mpnotes'} {$note.date_add|date_format:"%d/%m/%Y %H:%M:%S"}</p>
                    <p>Operatore: {$note.employee}</p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="orderNoteForm">
                    <input type="hidden" name="noteId" id="noteId" value="{$note.id_mp_note_order|intval}">
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
            <div class="modal-body" id="attachments-div" style="display: none;">
                <div class="form-group mb-2">
                    <div class="row" style="display: flex; justify-content: space-between;">
                        <div class="mt-3 col-md-4">
                            <input type="file" id="newAttachment" name="newAttachment" class="form-control-file">
                            <button type="button" class="btn btn-primary btn-sm mt-2" id="addAttachment" data-type="order">
                                {l s='Aggiungi allegato' mod='mpnotes'}
                            </button>
                        </div>
                        <div class="col-md-8">
                            <div class="preview-container mt-2" style="display: none;">
                                <p>{l s='Anteprima:' mod='mpnotes'}</p>
                                <img id="attachmentPreview" src="" alt="Anteprima" style="max-width: 100%; max-height: 200px;">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        {if isset($note.attachments) && $note.attachments}
                            <div class="carousel">
                                {foreach from=$note.attachments item=attachment name=attachments}
                                    <div class="carousel-item">
                                        <div class="carousel-image">
                                            {if $attachment.filename|regex_replace:"/.*\.(\w+)$/":"$1"|in_array:['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']}
                                                <img data-id="{$attachment.id_mp_note_order_file|intval}" class="thumbnail-img" src="{$uploadDir}{$attachment.filename}" alt="{$attachment.filename}">
                                            {else}
                                                <span>{$attachment.filename}</span>
                                            {/if}
                                        </div>
                                        <div class="carousel-caption">
                                            <button type="button" class="btn btn-danger btn-sm delete-attachment" data-id="{$attachment.id_mp_note_order_file}" data-type="order">
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
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{l s='Chiudi' mod='mpnotes'}</button>
                {if $note.id_mp_note_order == 0}
                    <button type="button" class="btn btn-primary" id="saveNoteOrder">{l s='Salva' mod='mpnotes'}</button>
                {/if}
            </div>
        </div>
    </div>
</div>