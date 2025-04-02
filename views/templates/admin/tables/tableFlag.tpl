<table class="table" id="flags-notes-table">
    <thead>
        <tr class="nodrag nodrop">
            <th>{l s='ID' mod='mpnotes'}</th>
            <th>{l s='Name' mod='mpnotes'}</th>
            <th>{l s='Color' mod='mpnotes'}</th>
            <th>{l s='Icon' mod='mpnotes'}</th>
            <th>{l s='Type' mod='mpnotes'}</th>
            <th>{l s='Allow Update' mod='mpnotes'}</th>
            <th>{l s='Allow Attachments' mod='mpnotes'}</th>
            <th>{l s='Actions' mod='mpnotes'}</th>
        </tr>
    </thead>
    <tbody>
        {foreach $rows as $row}
            <tr data-id="{$row.id}">
                <td>{$row.id}</td>
                <td>{$row.name}</td>
                <td style="width: 96px;">
                    <div class="material-icons" style="color: {$row.color};">format_color_fill</div>
                </td>
                <td style="width: 96px;">
                    <div class="material-icons">{$row.icon}</div>
                </td>
                <td>{$row.type}</td>
                <td style="width: 150px;">{if $row.allow_update}<span class="material-icons text-success">thumb_up</span>{else}<span class="material-icons text-danger">thumb_down</span>{/if}</td>
                <td style="width: 150px;">{if $row.allow_attachments}<span class="material-icons text-success">thumb_up</span>{else}<span class="material-icons text-danger">thumb_down</span>{/if}</td>
                <td style="width: 150px;">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#editFlagNoteModal" data-id="{$row.id}">
                        {l s='Edit' mod='mpnotes'}
                    </button>
                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteFlagNoteModal" data-id="{$row.id}">
                        {l s='Delete' mod='mpnotes'}
                    </button>
                </td>
            </tr>
        {/foreach}
    </tbody>
</table>