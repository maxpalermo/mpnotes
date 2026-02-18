<div id="attachmentsCarousel" class="card-body" data-id_note="{$note->id}">
    <div class="card-body">
        <!--Toolbar pusanti azione-->
        <div class="d-flex justify-content-end">
            <div class="btn-group" role="group">
                {if !$viewMode}
                    <button type="button" class="btn btn-primary" id="btn-new-attachment">
                        <i class="material-icons">add</i> Aggiungi
                    </button>
                {/if}
                
                <button type="button" class="btn btn-success" id="btnSaveAttachmentForm">
                    <i class="material-icons align-middle mr-1">save</i> Salva
                </button>
            </div>
        </div>

        <input type="hidden" name="id_note" id="id_note" value="{$note->id}">
        <!-- Upload Section (initially hidden in edit mode) -->
        {if !$viewMode}
            <div id="uploadSection" class="upload-section mb-4" style="display: none;">
                <div class="form-group">
                    <label for="fileUpload" class="form-control-label">Seleziona file</label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="fileUpload" name="attachments[]" multiple accept="image/*,.pdf">
                        <label class="custom-file-label" for="fileUpload">Scegli file...</label>
                    </div>
                    <small class="form-text text-muted">Formati supportati: immagini e PDF. Puoi selezionare più file contemporaneamente.</small>
                </div>
                <div class="upload-actions mt-3 d-flex justify-content-center">
                    <button type="button" id="btnUploadFiles" class="btn btn-primary mr-2">
                        <i class="material-icons">cloud_upload</i> Carica
                    </button>
                    <button type="button" id="btnCancelUpload" class="btn btn-outline-secondary ml-2">
                        <i class="material-icons">close</i> Chiudi
                    </button>
                </div>
            </div>
        {/if}

        <!-- Attachments Preview Section -->
        <div id="attachmentsContainer" class="attachments-container">
            {if isset($attachments) && count($attachments) > 0}
                <div class="attachment-carousel-wrapper">
                    <!-- Carousel Navigation -->
                    <div class="carousel-nav">
                        <button type="button" class="btn btn-sm btn-icon carousel-prev">
                            <i class="material-icons">chevron_left</i>
                        </button>
                        <button type="button" class="btn btn-sm btn-icon carousel-next">
                            <i class="material-icons">chevron_right</i>
                        </button>
                    </div>

                    <!-- Carousel Items -->
                    <div class="attachment-carousel">
                        {foreach from=$attachments item=attachment}
                            <div class="attachment-item" data-id="{$attachment.id_mpnote_attachment}">
                                <div class="attachment-preview">
                                    {if $attachment.file_ext|in_array:['jpg', 'jpeg', 'png', 'gif', 'webp']}
                                        <img src="{$attachment_url}{$attachment.filename}" alt="{$attachment.filetitle}" class="img-fluid attachment-preview-img">
                                    {else}
                                        <div class="pdf-placeholder">
                                            <i class="material-icons">picture_as_pdf</i>
                                            <span>Documento PDF</span>
                                        </div>
                                    {/if}
                                </div>
                                <div class="attachment-info">
                                    <div class="attachment-title">{$attachment.filetitle}</div>
                                    {if !$viewMode}
                                        <div class="attachment-actions">
                                            <button type="button" class="btn btn-sm btn-icon btn-outline-danger btn-delete-attachment" data-id="{$attachment.id_mpnote_attachment}">
                                                <i class="material-icons">delete</i>
                                            </button>
                                        </div>
                                    {/if}
                                </div>
                            </div>
                        {/foreach}
                    </div>
                </div>
            {else}
                <div class="alert alert-info">
                    <i class="material-icons">info</i>
                    Nessun allegato disponibile
                </div>
            {/if}
        </div>
    </div>
</div>