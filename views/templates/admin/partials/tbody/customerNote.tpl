<tbody>
    {foreach from=$note_list item=note}
        <tr class="pointer tr-note" data-id="{$note.id_mp_note_customer}" data-type="customer">
            <td style="width: 48px; text-align: center;">
                {if $note.type==1}
                    <span class="material-icons text-info" title="{l s='Informazione' mod='mpnotes'}">info</span>
                {elseif $note.type==2}
                    <span class="material-icons text-warning" title="{l s='Attenzione' mod='mpnotes'}">warning</span>
                {elseif $note.type==3}
                    <span class="material-icons text-danger" title="{l s='Errore' mod='mpnotes'}">error</span>
                {/if}
            </td>
            <td style="width: 6rem;">{$note.date_add|date_format:"%Y-%m-%d %H:%M:%S"}</td>
            <td>{$note.note|escape:'html':'UTF-8'}</td>
        </tr>
    {/foreach}
</tbody>