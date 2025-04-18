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
        const cardCustomer = document.querySelector('.card.customer-private-note-card');
        const summaryNotes = document.querySelector('#summaryNotes');
        const url = window.location.href;
        const isViewPage = url.includes('/view');

        console.log("URL:", url, isViewPage);

        if (isViewPage == false) {
            console.log("isViewPage == false");
            return;
        } else {
            console.log("isViewPage == true");
            if (cardCustomer) {
                const nextSibling = cardCustomer.nextElementSibling;
                cardCustomer.remove();
                if (summaryNotes) {
                    //inserisco prima di nextsibling il pannello summaryNotes
                    nextSibling.insertAdjacentHTML('beforebegin', summaryNotes.outerHTML);
                    summaryNotes.remove();

                    const event = new CustomEvent("SummaryNoteLoaded");
                    console.log("SummaryNoteLoaded");
                    document.dispatchEvent(event);
                }
            }
        }
    });
</script>