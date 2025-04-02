<div id="summaryNotes" class="row" style="border-bottom: 1px solid #ddd; padding-bottom: 1rem; margin-bottom: 1rem;">
    {$summaryContent}
</div>
<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {
        console.log("Trigger SummaryNoteLoaded");

        const event = new CustomEvent("SummaryNoteLoaded");
        document.dispatchEvent(event);
    });
</script>