class BsTableNotes {
    tableId = "";
    endpoint = null;
    orderId = 0;
    customerId = 0;
    table = null;
    type = null;
    id = null;

    constructor(tableId, endpoint, orderId, customerId) {
        this.endpoint = endpoint;
        this.orderId = orderId;
        this.customerId = customerId;
        this.tableId = tableId;
        this.table = document.getElementById(this.tableId);
        this._onClick = this.onClick.bind(this);
        this.initBsTable();
    }

    setType(type) {
        this.type = type;
    }

    setId(id) {
        this.id = id;
    }

    refreshTable(type = null) {
        if (!type) {
            type = this.type;
        }
        $(this.table).bootstrapTable("refresh", { silent: true });
        this.bindButtons();
    }

    async initBsTable() {
        const self = this;

        $(self.table).bootstrapTable({
            url: self.endpoint,
            method: "post",
            contentType: "application/x-www-form-urlencoded",
            queryParams: function (params) {
                const searchParams = {
                    ajax: 1,
                    action: "fetchAllNotes",
                    id: self.id,
                    type: self.type,
                    limit: params.limit,
                    offset: params.offset,
                    search: params.search,
                    sort: params.sort == undefined ? "a.date_add" : params.sort,
                    order: params.order == undefined ? "desc" : params.order,
                    filter: params.filter == undefined ? "" : params.filter,
                    orderId: self.orderId,
                    customerId: self.customerId,
                };

                console.log("INIT TABLE");
                console.table(searchParams);

                return searchParams;
            },
            search: true,
            filterControl: false,
            filterControlVisible: false,
            filterControlSearchClear: false,
            showFilterControlSwitch: false,
            searchOnEnterKey: true,
            sortSelectOptions: true,
            serverSort: true,
            sidePagination: "server",
            pagination: true,
            showRefresh: true,
            showColumns: false,
            striped: true,
            condensed: true,
            pageSize: 25,
            pageList: [10, 25, 50, 100, 250, 500],
            locale: "it-IT",
            classes: "table table-bordered table-hover",
            theadClasses: "thead-light",
            showExport: false,
            toolbar: self.toolbarId,
            uniqueId: self.uniqueId,
            detailView: false, // Imposta a true per avere il dettaglio della riga
            detailFormatter: (_, row) => {
                return '<div id="detail-' + row.id_carrier_brt_localita + '">Caricamento...</div>';
            },
            onExpandRow: (_, row, $detail) => {
                //Per ora non serve, ma lasciamo il codice per futura implementazione
                //$details è il contenuto da visualizzare
            },
            iconsPrefix: "icon", // usa Font Awesome invece delle glyphicons
            icons: {
                detailOpen: "icon-plus icon-2x", // icona quando è chiuso
                detailClose: "icon-minus icon-2x", // icona quando è aperto
            },
            onPostBody: function () {
                console.log(`MODULE MPNOTES - Bootstrap ${self.tableId} Table pronta.`);
                self.fixDropDownPagination();
                self.setBootstrapTableIcons();
            },
            columns: [
                {
                    field: "id_mpnote",
                    title: "ID",
                    align: "left",
                    sortable: true,
                    uniqueId: true,
                    filterControl: "input",
                    formatter: function (value, row, index) {
                        return `<span style="font-family:'monospace';">${value}</span>`;
                    },
                },
                {
                    field: "id_order",
                    title: "Id Ordine",
                    align: "left",
                    sortable: true,
                    filterControl: "input",
                    formatter: function (value, row, index) {
                        console.log("ORDER ID: ", self.orderId, row.editOrderUrl);
                        const orderID = self.orderId;
                        if (row.id_order > 0) {
                            if (row.id_order == orderID) {
                                return `<span style="font-family:'monospace';" class="text-danger">${value}</span>`;
                            } else {
                                return `<a href="${row.editOrderUrl}" target="_blank"><span style="font-family:'monospace';">${value}</span></a>`;
                            }
                        }
                        return `<span style="font-family:'monospace';">--</span>`;
                    },
                },
                {
                    field: "gravity",
                    title: "tipo",
                    align: "center",
                    width: 38,
                    sortable: true,
                    filterControl: "input",
                    formatter: function (value, row, index) {
                        switch (value) {
                            case "info":
                                return `<span class="btn-toggle-gravity material-icons text-info d-center-center" title="Info" data-id-note="${row.id_mpnote}">info</span>`;
                            case "warning":
                                return `<span class="btn-toggle-gravity material-icons text-warning d-center-center" title="Avviso" data-id-note="${row.id_mpnote}">warning</span>`;
                            case "error":
                                return `<span class="btn-toggle-gravity material-icons text-danger d-center-center" title="Errore" data-id-note="${row.id_mpnote}">error</span>`;
                            case "success":
                                return `<span class="btn-toggle-gravity material-icons text-success d-center-center" title="Successo" data-id-note="${row.id_mpnote}">check_circle</span>`;
                            default:
                                return `<span class="btn-toggle-gravity material-icons text-info d-center-center" title="Info" data-id-note="${row.id_mpnote}">info</span>`;
                        }
                    },
                },
                {
                    field: "content",
                    title: "Contenuto",
                    align: "left",
                    sortable: true,
                    formatter: function (value, row, index) {
                        return `${row.content}`;
                    },
                },
                {
                    field: "employee_firstname",
                    title: "Operatore",
                    align: "left",
                    sortable: true,
                    formatter: function (value, row, index) {
                        return `${row.employee_firstname} ${row.employee_lastname}`;
                    },
                },
                {
                    field: "printable",
                    title: "Stampa",
                    align: "center",
                    width: "48px",
                    class: "text-center",
                    sortable: true,
                    formatter: function (value, row, index) {
                        let textColor = "text-success";

                        if (value == 0) {
                            textColor = "text-danger";
                        }

                        return `<span style="width: 24px;" class="material-icons ${textColor}" title="Stampabile" data-action="togglePrintable" data-id-note="${row.id_mpnote}" data-action="togglePrintable">printer</span>`;
                    },
                },
                {
                    field: "chat",
                    title: "Chat",
                    align: "center",
                    width: "48px",
                    sortable: true,
                    formatter: function (value, row, index) {
                        let textColor = "text-success";

                        if (value == 0) {
                            textColor = "text-danger";
                        }

                        return `<span class="material-icons ${textColor}" title="Chat" data-action="toggleChat" data-id-note="${row.id_mpnote}">chat</span>`;
                    },
                },
                {
                    field: "attachments",
                    title: "Allegati",
                    align: "center",
                    sortable: true,
                    formatter: self.formatAttachments,
                },
                {
                    field: "date_add",
                    title: "Data Inserimento",
                    align: "center",
                    sortable: true,
                    formatter: function (value, row, index) {
                        return row.date_add;
                    },
                },
                {
                    field: "date_upd",
                    title: "Data Aggiornamento",
                    align: "center",
                    sortable: true,
                    formatter: function (value, row, index) {
                        return row.date_upd;
                    },
                },
                {
                    field: "action",
                    title: "Azioni",
                    align: "center",
                    width: 50,
                    sortable: false,
                    formatter: function (value, row, index) {
                        return `
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                <button type="button" class="btn btn-primary btn-sm" data-action="editNote" title="Modifica" data-type="${row.type}" data-id-note="${row.id_mpnote}" data-id-order="${self.orderId}" data-id-customer="${self.customerId}">
                                    <span class="material-icons">edit</span>
                                </button>
                                <button type="button" class="btn btn-primary btn-sm" data-action="addAttachment" title="Aggiungi allegato" data-type="${row.type}" data-id-note="${row.id_mpnote}" data-id-order="${self.orderId}" data-id-customer="${self.customerId}">
                                    <span class="material-icons">attachment</span>
                                </button>
                            </div>
                        `;
                    },
                },
            ],
        });
    }

    formatAttachments(value, row, index) {
        return value;
    }

    fixDropDownPagination() {
        $(".fixed-table-pagination .dropdown-toggle")
            .off("click")
            .on("click", function (e) {
                e.preventDefault();
                e.stopPropagation();
                const $btn = $(this);
                const $menu = $btn.closest(".btn-group").find(".dropdown-menu");

                $(".fixed-table-pagination .dropdown-menu").not($menu).removeClass("show");
                $menu.toggleClass("show");
            });

        // Normalizza il markup del dropdown page-size a Bootstrap 3
        $(".fixed-table-pagination .btn-group.dropdown").each(function () {
            var $group = $(this);
            var $menuDiv = $group.find("> .dropdown-menu");

            if ($menuDiv.length) {
                // Se non è già <ul>, converti
                if ($menuDiv.prop("tagName") !== "UL") {
                    var $ul = $('<ul class="dropdown-menu" role="menu"></ul>');

                    $menuDiv.find("a").each(function () {
                        var $a = $(this);
                        var $li = $("<li></li>");
                        $a.removeClass("dropdown-item"); // classe BS4/5 inutile qui
                        $li.append($a);
                        $ul.append($li);
                    });

                    $menuDiv.replaceWith($ul);
                }
            }

            // Assicura data-toggle (non data-bs-toggle) e inizializza il plugin
            var $btn = $group.find("> .dropdown-toggle");
            if ($btn.attr("data-bs-toggle") === "dropdown") {
                $btn.removeAttr("data-bs-toggle").attr("data-toggle", "dropdown");
            }
            if (typeof $.fn.dropdown === "function") {
                $btn.dropdown();
            }
        });

        $("button[name=filterControlSwitch]").html("<i class='material-icons'>filter_list</i>");

        $(document)
            .off("click.bs-table-page-size")
            .on("click.bs-table-page-size", function () {
                $(".fixed-table-pagination .dropdown-menu").removeClass("show");
            });
    }

    setBootstrapTableIcons() {
        document.querySelectorAll("button[name=refresh] i").forEach((i) => {
            i.setAttribute("class", "material-icons");
            i.innerHTML = "refresh";
        });

        document.querySelectorAll("button[name=clearSearch] i").forEach((i) => {
            i.setAttribute("class", "material-icons");
            i.innerHTML = "clear";
        });
    }

    bindButtons() {
        this.table.removeEventListener("click", this._onClick);
        this.table.addEventListener("click", this._onClick);
    }

    async onClick(e) {
        console.log("Click on ", e);
        const btn = e.target.closest("button");
        const toggle = e.target.closest("span.material-icons");

        if (btn) {
            const action = btn.dataset.action;
            const idNote = btn.dataset.idNote;
            const idOrder = btn.dataset.idOrder;
            const idCustomer = btn.dataset.idCustomer;
            const type = btn.dataset.type;

            if (action === "addAttachment") {
                //nothing
            }

            if (action === "editNote") {
                //nothing
            }

            showNoticeMessage(
                `
                    <p>Click on</p>
                    <ul>
                        <li>id: ${idNote}</li>
                        <li>order id: ${idOrder}</li>
                        <li>customer id: ${idCustomer}</li>
                        <li>type: ${type}</li>
                        <li>action: ${action}</li>
                    </ul>    
                `,
            );
        }

        if (toggle) {
            const action = toggle.dataset.action;
            const idNote = toggle.dataset.idNote;
            const data = await this.request("toggleAction", {
                toggleAction: action,
                idNote: idNote,
            });

            showNoticeMessage(data?.message || "Operazione non riuscita.");
            this.refreshTable();
        }
    }

    async request(action, data) {
        const formData = new FormData();
        formData.append("ajax", 1);
        formData.append("action", action);
        Object.entries(data).forEach(([key, value]) => {
            formData.append(key, value);
        });

        const request = await fetch(this.endpoint, {
            method: "POST",
            body: formData,
        });

        if (!request.ok) {
            throw new Error("MPNOTES: fetch: Network response was not ok");
        }

        const response = await request.json();

        return response;
    }
}
