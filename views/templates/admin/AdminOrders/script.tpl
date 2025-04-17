<script type="text/javascript">
    const gravityIcons = {
        info: "info",
        warning: "warning",
        error: "error",
        success: "check_circle"
    };

    const gravityColors = {
        info: "#17a2b8",
        warning: "#ffc107",
        error: "#dc3545",
        success: "#28a745"
    };
    const ajaxController = '{$ajaxController}';
    const id_employee = {$id_employee};

    document.addEventListener('DOMContentLoaded', function() {
        const orderViewPage = document.getElementById("order-view-page");
        if (!orderViewPage) {
            return;
        }
        const summaryNotes = document.getElementById("summaryNotes");
        if (!summaryNotes) {
            return;
        }

        //Inserisco summarynotes all'inizio di orderViewPage
        //orderViewPage.insertBefore(summaryNotes, orderViewPage.firstChild);
        const event = new CustomEvent("SummaryNoteLoaded");
        document.dispatchEvent(event);
    });
</script>