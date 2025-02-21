<div class="row" style="border-bottom: 1px solid #ddd; padding-bottom: 1rem; margin-bottom: 1rem;">
    {foreach from=$notes item=note}
        <div class="col-md-12" style="margin-botton: 8px;">
            <div class="panel">
                <div class="panel-heading">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="material-icons">{$note.icon}</span>
                            <span>{$note.title}</span>
                            {if isset($note.note_list) && !$note.note_list}
                                <span class="badge-custom badge-custom-warning note-count-{$note.type}">
                                    0
                                </span>
                            {else}
                                <span class="badge-custom badge-custom-success note-count-{$note.type}">
                                    {$note.note_list|count}
                                </span>
                            {/if}
                        </div>

                        <div class="search-container" style="position: relative; width: 100%; flex-grow: 1; max-width: 60%">
                            <span class="material-icons" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #777;">search</span>
                            <input type="text" class="form-control pl-5 search-bar" data-type="{$note.type}" data-id="{$note.id}" placeholder="{l s='Cerca...' mod='mpnotes'}" style="padding-left: 40px; border-radius: 50%;">
                        </div>

                        <div>
                            <button type="button" class="btn btn-success btn-sm float-right btn-new-note" data-table="{$note.table}" style="margin-right: 10px;">
                                <span class="material-icons">add_circle_outline</span> {l s='Nuova nota' mod='mpnotes'}
                            </button>
                        </div>

                        <a class="btn btn-link" data-toggle="collapse" href="#{$note.type}NotesCollapse" role="button" aria-expanded="false" aria-controls="{$note.type}NotesCollapse">
                            <span class="collapse-text">{l s='Mostra/Nascondi' mod='mpnotes'}</span>
                            <span class="material-icons">expand_more</span>
                        </a>
                    </div>
                </div>
                <div class="panel-body overflow-y-scroll collapse" id="{$note.type}NotesCollapse">
                    {if isset($note.note_list) && $note.note_list}
                        <table class="table table-condensed table-striped" id="tableNote{$note.type|ucfirst}">
                            {include file="../admin/partials/tbody/{$note.type}Note.tpl" note_list=$note.note_list}
                        </table>
                    {/if}
                </div>
            </div>
        </div>
    {/foreach}
</div>

<script type="text/javascript">
    const adminURL = "{$adminURL}";
    const noteCustomerId = "{$id_customer}";
    const noteOrderId = "{$id_order}";
    const noteOrderUploadDir = "{$noteOrderUploadDir}";
    const noteEmbroideryUploadDir = "{$noteEmbroideryUploadDir}";
</script>