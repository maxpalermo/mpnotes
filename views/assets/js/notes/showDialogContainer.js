function showDialogContainer(dialogTitle, dialogContent) {
    console.log("Dialog title: %s", dialogTitle);

    const existsDialog = document.getElementById("dialog-container");
    if (existsDialog) {
        existsDialog.remove();
    }
    const id = crypto.randomUUID();
    const dialogTemplate = document.getElementById("template-dialog-container");
    const dialogNode = dialogTemplate.content.firstElementChild.cloneNode(true);
    //const dialogNode = dialogTemplate.content.cloneNode(true);
    document.body.appendChild(dialogNode);

    dialogNode.id = id;
    const titleElement = dialogNode.querySelector('slot[name="title"]');
    const contentElement = dialogNode.querySelector('slot[name="content"]');

    titleElement.textContent = dialogTitle;
    contentElement.innerHTML = dialogContent;

    dialogNode.querySelector("#btn-close-dialog").addEventListener("click", function (e) {
        const dialog = e.target.closest("dialog");
        dialog.classList.add("closing");
        setTimeout(() => {
            dialog.close();
            dialog.remove();
        }, 200);
    });

    dialogNode.showModal();
}
