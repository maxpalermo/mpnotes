class BsTableNotes {
    tableId = "";
    endpoint = null;
    orderId = 0;
    customerId = 0;
    table = null;
    type = null;
    id = null;
    _searchBound = false;

    constructor(tableId, endpoint, orderId, customerId) {
        this.endpoint = endpoint;
        this.orderId = orderId;
        this.customerId = customerId;
        this.tableId = tableId;
        this.table = document.getElementById(this.tableId);
        this._onClick = this.onClick.bind(this);
        this._onNewNoteClick = this.onNewNoteClick.bind(this);
        this.initBsTable();
    }

    setType(type) {
        this.type = type;
        this.updateColumnVisibility();
    }

    setId(id) {
        this.id = id;
    }

    refreshTable(type = null) {
        if (!type) {
            type = this.type;
        }
        $(this.table).bootstrapTable("refresh", { silent: true });
        this.updateColumnVisibility();
        this.bindButtons();
    }

    getToolBar() {
        const html = `
            <div class="btn-group" id="tblNoteToolbar">
                <button class="btn btn-default" type="button" id="tblNotesNewNote">
                    <span class="material-icons">add</span>
                    <span>Nuova nota</span>
                </button>
            </div>
        `;

        const template = document.createElement("template");
        template.innerHTML = html;

        const toolbar = template.content.cloneNode(true).querySelector("div.btn-group");

        return toolbar;
    }

    async initBsTable() {
        const self = this;

        const container = self.table.parentNode;
        if (!document.getElementById("tblNoteToolbar")) {
            const tbar = self.getToolBar();
            container.insertBefore(tbar, self.table);
        }

        $(self.table).bootstrapTable({
            url: self.endpoint,
            method: "post",
            contentType: "application/x-www-form-urlencoded",
            queryParams: function (params) {
                const $search = $(self.table).closest(".bootstrap-table").find(".search input");
                const liveSearch = $search.length ? $search.val() : params.search;
                const searchParams = {
                    ajax: 1,
                    action: "fetchAllNotes",
                    id: self.id,
                    type: self.type,
                    limit: params.limit,
                    offset: params.offset,
                    search: liveSearch == null ? "" : String(liveSearch),
                    sort: params.sort == undefined ? "a.date_add" : params.sort,
                    order: params.order == undefined ? "desc" : params.order,
                    filter: params.filter == undefined ? "" : params.filter,
                    orderId: self.orderId,
                    customerId: self.customerId,
                };

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
            toolbar: "#tblNoteToolbar",
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
                self.bindNewNote();
                self.bindSearchClear();
                self.updateColumnVisibility();
                self.hideOldCustomerNotePanel();
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
                        return self.unescapeQuotes(row.content);
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
                    formatter: (value, row, index) => self.formatAttachments(value, row, index),
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

    unescapeQuotes(str) {
        return String(str).replace(/\\(['"])/g, "$1");
    }

    updateColumnVisibility() {
        if (!this.table) {
            return;
        }

        const $table = $(this.table);
        if (!$table || typeof $table.bootstrapTable !== "function") {
            return;
        }

        switch (this.type) {
            case "customer":
                $table.bootstrapTable("showColumn", "id_mpnote");
                $table.bootstrapTable("hideColumn", "id_order");
                $table.bootstrapTable("hideColumn", "gravity");
                $table.bootstrapTable("showColumn", "content");
                $table.bootstrapTable("showColumn", "employee_firstname");
                $table.bootstrapTable("showColumn", "employee_lastname");
                $table.bootstrapTable("hideColumn", "printable");
                $table.bootstrapTable("hideColumn", "chat");
                $table.bootstrapTable("hideColumn", "attachments");
                $table.bootstrapTable("showColumn", "date_add");
                $table.bootstrapTable("hideColumn", "date_upd");
                $table.bootstrapTable("hideColumn", "action");
                break;
            case "order":
                $table.bootstrapTable("showColumn", "id_mpnote");
                $table.bootstrapTable("hideColumn", "id_order");
                $table.bootstrapTable("hideColumn", "gravity");
                $table.bootstrapTable("showColumn", "content");
                $table.bootstrapTable("showColumn", "employee_firstname");
                $table.bootstrapTable("showColumn", "employee_lastname");
                $table.bootstrapTable("showColumn", "printable");
                $table.bootstrapTable("showColumn", "chat");
                $table.bootstrapTable("showColumn", "attachments");
                $table.bootstrapTable("showColumn", "date_add");
                $table.bootstrapTable("hideColumn", "date_upd");
                $table.bootstrapTable("hideColumn", "action");
                break;
            case "embroidery":
                $table.bootstrapTable("showColumn", "id_mpnote");
                $table.bootstrapTable("showColumn", "id_order");
                $table.bootstrapTable("hideColumn", "gravity");
                $table.bootstrapTable("showColumn", "content");
                $table.bootstrapTable("showColumn", "employee_firstname");
                $table.bootstrapTable("showColumn", "employee_lastname");
                $table.bootstrapTable("hideColumn", "printable");
                $table.bootstrapTable("hideColumn", "chat");
                $table.bootstrapTable("showColumn", "attachments");
                $table.bootstrapTable("showColumn", "date_add");
                $table.bootstrapTable("hideColumn", "date_upd");
                $table.bootstrapTable("showColumn", "action");
                break;
            default:
                break;
        }
    }

    formatAttachments(value, row, index) {
        const component = `
            <grid-attachment
                endpoint="${this.endpoint}"
                idNote="${row.id_mpnote}"
                add-action="addAttachment"
                delete-action="deleteAttachment"
                files="${value}">
            </grid-attachment>
        `;

        return component;
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

    bindSearchClear() {
        if (this._searchBound) {
            return;
        }

        const $search = $(this.table).closest(".bootstrap-table").find(".search input");
        if (!$search.length) {
            return;
        }

        this._searchBound = true;
        $search.off("input.mpnotesSearchClear");
        $search.on("input.mpnotesSearchClear", (e) => {
            if (String(e.target.value || "") === "") {
                $(this.table).bootstrapTable("resetSearch", "");
            }
        });
    }

    async onClick(e) {
        const self = this;
        const btn = e.target.closest("button");
        const toggle = e.target.closest("span.material-icons");

        if (btn) {
            const action = btn.dataset.action;
            const idNote = btn.dataset.idNote;
            const idOrder = btn.dataset.idOrder;
            const idCustomer = btn.dataset.idCustomer;
            const type = btn.dataset.type;

            if (action === "addAttachment") {
                const dlg = document.getElementById("mpnote-attachments-dialog");
                dlg.open({
                    type,
                    noteId: idNote,
                    orderId: idOrder,
                    customerId: idCustomer,
                    customerName: "",
                    tableId: "bsTblNotes",
                });
                return;
            }

            if (action === "editNote") {
                const response = await self.getNote(idNote);
                let params = null;

                if (response.success) {
                    params = {
                        /*
                        'id' => $this->id,
                        'id_history' => $this->id_history,
                        'type' => $this->type,
                        'reference' => $this->reference,
                        'id_customer' => $this->id_customer,
                        'customer_firstname' => $this->customer_firstname,
                        'customer_lastname' => $this->customer_lastname,
                        'id_order' => $this->id_order,
                        'id_employee' => $this->id_employee,
                        'employee_firstname' => $this->employee_firstname,
                        'employee_lastname' => $this->employee_lastname,
                        'gravity' => $this->gravity,
                        'content' => $this->content,
                        'printable' => (int) $this->printable,
                        'chat' => (int) $this->chat,
                        'deleted' => $this->deleted,
                        'date_add' => $this->date_add,
                        'date_upd' => $this->date_upd,
                        */
                        type: response.data.type,
                        noteId: response.data.id,
                        orderId: response.data.id_order,
                        customerId: response.data.id_customer,
                        customerName: response.data.customer_firstname + " " + response.data.customer_lastname,
                        employeeId: response.data.id_employee,
                        employeeName: response.data.employee_firstname + " " + response.data.employee_lastname,
                        content: response.data.content,
                        printable: response.data.printable,
                        chat: response.data.chat,
                    };
                } else {
                    showErrorMessage("Caricamento nota non riuscito.");
                    return false;
                }

                self.showEditNoteModal(params);
            }

            return;
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

            return;
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

    async getNote(id) {
        const response = await this.request("getNoteDetails", {
            idNote: id,
        });

        return response;
    }

    showEditNoteModal(data) {
        /*
        data:
            type: type,
            noteId: idNote,
            orderId: idOrder,
            customerId: idCustomer,
            customerName: response.data.customer_name,
            employeeId: response.data.id_employee,
            employeeName: response.data.employee_firstname + " " + response.data.employee_lastname,
            content: response.data.content,
            printable: response.data.printable,
            chat: response.data.chat,
        */

        console.log(data);

        const dlg = document.getElementById("mpnote-dialog");

        dlg.open({
            type: data.type,
            noteId: data.noteId,
            orderId: data.orderId,
            customerId: data.customerId,
            customerName: data.customerName,
            employeeId: data.employeeId,
            employeeName: data.employeeName,
            content: data.content,
            printable: data.printable,
            chat: data.chat,
        });
    }

    bindNewNote() {
        const idNewNote = "tblNotesNewNote";
        const btn = document.getElementById(idNewNote);
        if (btn) {
            btn.removeEventListener("click", this._onNewNoteClick);
            btn.addEventListener("click", this._onNewNoteClick);
        }
    }

    onNewNoteClick(e) {
        e.preventDefault();

        const dlg = document.getElementById("mpnote-dialog");
        if (!dlg) {
            throw new Error("mpnote-dialog element not found");
        }

        dlg.open({
            type: this.type,
            noteId: 0,
            orderId: this.orderId,
            customerId: this.customerId,
            customerName: "",
            employeeId: 0,
            employeeName: "",
            content: "",
            printable: 0,
            chat: 0,
        });
    }

    hideOldCustomerNotePanel() {
        const panel = document.getElementById("privateNote");
        if (panel) {
            panel.style.display = "none";
        }
    }
}
