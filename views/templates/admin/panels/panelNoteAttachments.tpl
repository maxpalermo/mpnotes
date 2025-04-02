<div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="material-icons mr-2">attach_file</i> Allegati</h5>
        <button type="button" class="btn btn-primary btn-sm" id="btn-new-attachment">
            <i class="material-icons">add</i> Nuovo allegato
        </button>
    </div>
    <div class="card-body">
        <div id="attachments-carousel" class="carousel slide" data-ride="carousel" data-id_note="{$id_note}">
            <div class="carousel-inner d-flex flex-wrap">
                {if isset($attachments) && count($attachments) > 0}
                    {foreach $attachments as $index => $attachment}
                        <div class="attachment-item m-2" data-id="{$attachment.id}">
                            <div class="attachment-preview">
                                <img src="{$attachment.file_url}" alt="{$attachment.title}" class="img-thumbnail">
                            </div>
                            <div class="attachment-info text-center mt-1">
                                <p class="mb-0">{$attachment.title}</p>
                                <button class="btn btn-danger btn-sm mt-1 btn-delete-attachment" data-id="{$attachment.id}">
                                    <i class="material-icons">delete</i>
                                </button>
                            </div>
                        </div>
                    {/foreach}
                {else}
                    <div class="alert alert-info w-100">Nessun allegato disponibile</div>
                {/if}
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="uploadAttachmentModal" tabindex="-1" role="dialog" aria-labelledby="uploadAttachmentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadAttachmentModalLabel">Carica nuovi allegati</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="attachmentUploadForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="attachment-title">Titolo</label>
                        <input type="text" class="form-control" id="attachment-title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="attachment-description">Descrizione</label>
                        <textarea class="form-control" id="attachment-description" name="description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="attachment-files">File</label>
                        <input type="file" class="form-control-file" id="attachment-files" name="files[]" multiple accept="image/*">
                        <small class="form-text text-muted">Puoi selezionare più file contemporaneamente</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" id="btn-upload-attachments">Carica</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        const btnNewAttachment = document.getElementById('btn-new-attachment');
        const btnUploadAttachments = document.getElementById('btn-upload-attachments');
        const attachmentsCarousel = document.getElementById('attachments-carousel');
        const id_note = attachmentsCarousel.dataset.id_note;
        
        // Inizializza i pulsanti di eliminazione
        document.querySelectorAll('.btn-delete-attachment').forEach(btn => {
            btn.addEventListener('click', deleteAttachment);
        });
        
        // Apri modal per nuovo allegato
        btnNewAttachment.addEventListener('click', function() {
            $('#uploadAttachmentModal').modal('show');
        });
        
        // Gestisci upload allegati
        btnUploadAttachments.addEventListener('click', uploadAttachments);
        
        async function uploadAttachments() {
            const form = document.getElementById('attachmentUploadForm');
            const formData = new FormData(form);
            formData.append('id_note', id_note);
            formData.append('ajax', 1);
            formData.append('action', 'uploadAttachments');
            
            try {
                const response = await fetch("{$ajaxController}", {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    swalSuccess(data.message || 'Allegati caricati con successo');
                    $('#uploadAttachmentModal').modal('hide');
                    form.reset();
                    // Aggiorna la visualizzazione degli allegati
                    refreshAttachments();
                } else {
                    swalError(data.message || 'Si è verificato un errore durante il caricamento');
                }
            } catch (error) {
                swalError('Si è verificato un errore durante il caricamento');
                console.error(error);
            }
        }
        
        async function deleteAttachment(e) {
            const id = e.currentTarget.dataset.id;
            
            const confirmation = await swalConfirm('Sei sicuro di voler eliminare questo allegato?');
            if (!confirmation.isConfirmed) return;
            
            try {
                const response = await fetch("{$ajaxController}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        ajax: 1,
                        action: 'deleteAttachment',
                        id_attachment: id
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    swalSuccess(data.message || 'Allegato eliminato con successo');
                    // Rimuovi l'elemento dal DOM
                    document.querySelector(`.attachment-item[data-id="${id}"]`).remove();
                } else {
                    swalError(data.message || 'Si è verificato un errore durante l\'eliminazione');
                }
            } catch (error) {
                swalError('Si è verificato un errore durante l\'eliminazione');
                console.error(error);
            }
        }
        
        async function refreshAttachments() {
            try {
                const response = await fetch("{$ajaxController}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        ajax: 1,
                        action: 'getAttachments',
                        id_note: id_note
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Aggiorna il contenuto del carousel con i nuovi allegati
                    const carouselInner = attachmentsCarousel.querySelector('.carousel-inner');
                    carouselInner.innerHTML = data.html || '<div class="alert alert-info w-100">Nessun allegato disponibile</div>';
                    
                    // Reinizializza i pulsanti di eliminazione
                    document.querySelectorAll('.btn-delete-attachment').forEach(btn => {
                        btn.addEventListener('click', deleteAttachment);
                    });
                }
            } catch (error) {
                console.error(error);
            }
        }
    });
</script>
