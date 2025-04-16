async function bindSearchBar() {
    // Add click event listeners to flag checkboxes for better visual feedback
    const searchBar = document.querySelectorAll(".search-bar");
    console.log("searchBar", searchBar);

    searchBar.forEach((searchBar) => {
        searchBar.addEventListener("input", function () {
            const textSearch = searchBar.value.toLowerCase();
            console.log("Change", textSearch);

            //cerco nella tabella la cella con data-cell="content"
            const cells = searchBar.closest(".card").querySelector("table").querySelectorAll("[data-cell='content']");

            cells.forEach((cell) => {
                const textContent = cell.textContent.trim().toLowerCase();
                if (textSearch.length == 0 || textContent.includes(textSearch)) {
                    cell.closest("tr").style.display = "";
                } else {
                    cell.closest("tr").style.display = "none";
                }
            });
        });
    });
}
