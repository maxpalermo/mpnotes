<div class="card-header">
    <div class="d-flex align-items-center justify-content-between">
        <div style="width: 12rem;" class="d-flex align-items-center justify-content-between">
            <span class="material-icons" style="color: {$color}; width: 32px;">{$icon}</span>
            <span style=" font-size: 1.2rem;">{$title}</span>
            <span class="badge badge-success note-count-1" style="width: 24px; font-size: 1rem; border-radius: 0; text-align: center">
                {$noteCount}
            </span>
        </div>

        <div class="search-container" style="position: relative; width: 100%; flex-grow: 1; max-width: 60%">
            <span class="material-icons" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #777;">search</span>
            <input type="text" class="form-control pl-5 search-bar" placeholder="Cerca..." style="padding-left: 40px; border-radius: 5px;">
        </div>

        <div>
            <button type="button" class="btn btn-success btn-sm float-right btn-new-note" style="margin-right: 10px;" data-id_note_type="{$id}" data-id_order="{$id_order}" data-id_customer="{$id_customer}" data-id_employee="{$id_employee}">
                <span class="material-icons">add_circle_outline</span> Nuova nota
            </button>
        </div>

        <a class="btn btn-link" data-toggle="collapse" href="#panelNotesCollapse-{$id}" role="button" aria-expanded="false" aria-controls="{$id}NotesCollapse">
            <span class="collapse-text">Mostra/Nascondi</span>
            <span class="material-icons">expand_more</span>
        </a>
    </div>
</div>