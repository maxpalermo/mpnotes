async function bindNoteFlags() {
    // Add click event listeners to flag checkboxes for better visual feedback
    const flagCheckboxes = document.querySelectorAll(".flag-checkbox");
    console.log("flagCheckboxes", flagCheckboxes);

    flagCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener("change", function () {
            // Add visual feedback with a brief animation
            console.log("Change", checkbox.checked);

            const label = this.nextElementSibling;
            if (this.checked) {
                label.style.transform = "scale(1.1)";
                setTimeout(() => {
                    label.style.transform = "translateY(-2px)";
                }, 150);
            } else {
                label.style.transform = "scale(0.95)";
                setTimeout(() => {
                    label.style.transform = "translateY(0)";
                }, 150);
            }
        });
    });
}
