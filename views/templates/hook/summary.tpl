<div id="summaryNotes" class="row" style="border-bottom: 1px solid #ddd; padding-bottom: 1rem; margin-bottom: 1rem;">
    {include file="./notes.tpl"}
</div>

<script type="text/javascript">
    const adminURL = "{$adminURL}";
    const noteCustomerId = "{$id_customer}";
    const noteOrderId = "{$id_order}";
    const noteOrderUploadDir = "{$noteOrderUploadDir}";
    const noteEmbroideryUploadDir = "{$noteEmbroideryUploadDir}";
    const noteTypes = ["unknown", "customer", "embroidery", "order"];

    document.addEventListener("DOMContentLoaded", function() {
        //TODO: ?
    })
</script>