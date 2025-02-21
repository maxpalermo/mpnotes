<tbody>
    {foreach from=$note_list item=note}
        <tr class="pointer tr-note" data-id="{$note.id_mp_note_embroidery}" data-type="embroidery">
            <td style="width: 48px; text-align: center;">
                {if $note.type==1}
                    <span class="material-icons text-info" title="{l s='Informazione' mod='mpnotes'}">info</span>
                {elseif $note.type==2}
                    <span class="material-icons text-warning" title="{l s='Attenzione' mod='mpnotes'}">warning</span>
                {elseif $note.type==3}
                    <span class="material-icons text-danger" title="{l s='Errore' mod='mpnotes'}">error</span>
                {/if}
            </td>
            <td style="width: 6rem;">
                <a class="pointer" target="_blank" href="{$note.link}">{$note.id_order}</a>
            </td>
            <td style="width: 10rem;">{$note.date_add|date_format:"%Y-%m-%d %H:%M:%S"}</td>
            <td>
                {$note.note|escape:'html':'UTF-8'}
                <span class="badge bg-secondary float-right" title="{l s='Allegati' mod='mpnotes'}">{count($note.attachments)}</span>
            </td>
        </tr>
    {/foreach}
</tbody>