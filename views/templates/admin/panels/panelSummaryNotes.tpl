<div id="summaryNotes" class="row" style="border-bottom: 1px solid #ddd; padding-bottom: 1rem; margin-bottom: 1rem;">
    {$summaryContent}
</div>
<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {
        const url = window.location.href;
        const isViewPage = url.includes('/view');

        console.log("URL:", url, isViewPage);

        if (isViewPage == false) {
            console.log("isViewPage == false");
            const summaryNotes = document.getElementById("summaryNotes");
            if (summaryNotes) {
                summaryNotes.remove();
            }
        } else {
            console.log("Triggering SummaryNoteLoaded");
            const event = new CustomEvent("SummaryNoteLoaded");
            document.dispatchEvent(event);
        }
    });
</script>