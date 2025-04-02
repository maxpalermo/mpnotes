<div class="card-body overflow-y-scroll collapse" id="panelNotesCollapse-{$id_type}">
    <table class="table table-condensed table-striped" id="noteTable_{$id_type}">
        <tbody>
            {if count($notes) > 0}
                {foreach $notes as $note}
                    <tr class="pointer tr-note" data-id="row_{$note.id}">
                        <td style="width: 48px; text-align: center;" data-cell="gravity">
                            <span class="material-icons text-{$note.gravity}">{$note.gravity_icon}</span>
                        </td>
                        <td style="width: 3rem;" data-cell="flags">
                            <div class="d-flex align-items-center justify-content-start">
                                {foreach $note.flags as $flag}
                                    {if $flag.value == 1}
                                        <span class="material-icons" style="color: {$flag.color}">{$flag.icon}</span>
                                    {/if}
                                {/foreach}
                            </div>
                        </td>
                        <td style="width: 6rem;" data-cell="id_order">{$note.id_order}</td>
                        <td style="width: 6rem;" data-cell="date_add">{$note.date_add|date_format:"%Y-%m-%d %H:%M:%S"}</td>
                        <td data-cell="content">{$note.content}</td>
                        <td style="width: 4rem;" class="no-row-click" data-cell="attachments">
                            <div class="d-flex align-items-center justify-content-end">
                                {if $note.attachments > 0 || $note.allow_attachments}
                                    <button type="button" class="btn btn-default btn-sm btn-attachment" data-id_note="{$note.id}" style="max-width: 3rem; min-width: 3rem;">
                                        {if $note.attachments > 0}
                                            <span class="badge badge-success">{$note.attachments}</span>
                                        {else}
                                            <span class="badge badge-warning"><span class="material-icons" style="font-size: 0.7rem;">attachment</span></span>
                                        {/if}
                                    </button>
                                {elseif !$note.allow_attachments}
                                    <span class="material-icons" data-id_note="{$note.id}" title="Allegati non permessi">lock</span>
                                {/if}
                            </div>
                        </td>
                        <td style="width: 2rem;" data-cell="view">
                            <div class="d-flex align-items-center justify-content-end">
                                <button type="button" class="btn btn-info btn-sm btn-view-note" data-id="{$note.id}">
                                    <span class="material-icons">preview</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                {/foreach}
            {else}
                <tr>
                    <td colspan="5" class="text-center">Nessuna nota trovata</td>
                </tr>
            {/if}
        </tbody>
    </table>
</div>