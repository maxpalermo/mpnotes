<tbody>
    {foreach from=$note_list item=note}
        <tr class="pointer tr-note" data-id="{$note.id_mp_note_order}" data-type="order">
            <td style="width: 48px; text-align: center;">
                {if $note.type==1}
                    <span class="material-icons text-info" title="{l s='Informazione' mod='mpnotes'}">info</span>
                {elseif $note.type==2}
                    <span class="material-icons text-warning" title="{l s='Attenzione' mod='mpnotes'}">warning</span>
                {elseif $note.type==3}
                    <span class="material-icons text-danger" title="{l s='Errore' mod='mpnotes'}">error</span>
                {/if}
            </td>
            <td style="width: 3rem;">
                <div class="d-flex align-items-center justify-content-start">
                    {if $note.printable}
                        <span class="material-icons text-success" title="{l s='Nota stampabile' mod='mpnotes'}">print</span>
                    {/if}

                    {if $note.chat}
                        <span class="material-icons text-success" title="{l s='Chat' mod='mpnotes'}">headset_mic</span>
                    {/if}
                </div>
            </td>
            <td style="width: 10rem;">{$note.date_add|date_format:"%Y-%m-%d %H:%M:%S"}</td>
            <td>
                {$note.note|escape:'html':'UTF-8'}
                <span class="badge bg-secondary float-right" title="{l s='Allegati' mod='mpnotes'}">{count($note.attachments)}</span>
            </td>
        </tr>
    {/foreach}
</tbody>