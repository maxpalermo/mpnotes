class AdminBsTableWrapper {
    adminControllerUrl = null;
    tableId = null;
    toolbarId = null;
    uniqueId = null;
    table = null;

    constructor(adminControllerUrl, tableId = null, uniqueId = null, toolbarId = null) {
        this.adminControllerUrl = adminControllerUrl;
        this.tableId = tableId;
        this.toolbarId = toolbarId;
        this.uniqueId = uniqueId;
        this.table = document.getElementById(this.tableId);
        this.init();
    }

    init() {
        const self = this;
        $(self.table).bootstrapTable({
            url: self.adminControllerUrl,
            method: "post",
            contentType: "application/x-www-form-urlencoded",
            queryParams: function (params) {
                console.log("QueryParams", params);

                return {
                    ajax: 1,
                    action: "fetchAllNotes",
                    type: "all",
                    limit: params.limit,
                    offset: params.offset,
                    search: params.search,
                    sort: params.sort == undefined ? "a.id_mpnote" : params.sort,
                    order: params.order == undefined ? "asc" : params.order,
                    filter: params.filter == undefined ? "" : params.filter,
                };
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
            theadClasses: "thead-dark",
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
                window.bootstrapTableFixIcons();
                console.log(`MODULE: MPNOTES - Bootstrap ${self.tableId} Table initialized successfully`);
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
                    field: "type",
                    title: "Tipo",
                    align: "center",
                    sortable: true,
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
                        return `<span style="font-family:'monospace';">${value}</span>`;
                    },
                },
                {
                    field: "id_customer",
                    title: "Cliente",
                    align: "left",
                    sortable: true,
                    filterControl: "input",
                    formatter: function (value, row, index) {
                        return `<span style="font-family:'monospace';">${value}</span>`;
                    },
                },
                {
                    field: "id_employee",
                    title: "Operatore",
                    align: "left",
                    sortable: true,
                    formatter: function (value, row, index) {
                        return `${row.employee_firstname} ${row.employee_lastname}`;
                    },
                },
                {
                    field: "content",
                    title: "Messaggio",
                    align: "left",
                    sortable: true,
                    filterControl: "input",
                    formatter: function (value, row, index) {
                        return `<span style="font-family:'monospace';">${value}</span>`;
                    },
                },
                /*
                {
                    field: "gravity",
                    title: "Classe",
                    align: "center",
                    sortable: true,
                    filterControl: "input",
                    formatter: function (value, row, index) {
                        switch (value) {
                            case "info":
                                return `<span class="align-items-center" title="Info"><span class="material-icons text-info">info</span></span>`;
                            case "warning":
                                return `<span class="align-items-center" title="Avviso"><span class="material-icons text-warning">warning</span></span>`;
                            case "error":
                                return `<span class="align-items-center" title="Errore"><span class="material-icons text-danger">error</span></span>`;
                            case "success":
                                return `<span class="align-items-center" title="Successo"><span class="material-icons text-success">done</span></span>`;
                            default:
                                return `<span class="align-items-center" title="Info"><span class="material-icons text-info">info</span></span>`;
                        }
                    },
                },
                */
                {
                    field: "printable",
                    title: "Stampabile",
                    align: "center",
                    sortable: true,
                    filterControl: "input",
                    formatter: function (value, row, index) {
                        if (value == 1) {
                            return `<span class="material-icons text-success" title="Stampabile" onclick="window.togglePrintable(this)" data-id="${row.id_mpnote}">print</span>`;
                        } else {
                            return `<span class="material-icons text-danger" title="Non Stampabile" onclick="window.togglePrintable(this)" data-id="${row.id_mpnote}">print_disabled</span>`;
                        }
                    },
                },
                {
                    field: "chat",
                    title: "Chat",
                    align: "center",
                    sortable: true,
                    filterControl: "input",
                    formatter: function (value, row, index) {
                        if (value == 1) {
                            return `<span class="material-icons text-success" title="Chat" onclick="window.toggleChat(this)" data-id="${row.id_mpnote}">chat</span>`;
                        } else {
                            return `<span class="material-icons text-danger" title="Non Chat" onclick="window.toggleChat(this)" data-id="${row.id_mpnote}">chat_bubble_outline</span>`;
                        }
                    },
                },
                {
                    field: "attachments",
                    title: "Allegati",
                    align: "center",
                    sortable: true,
                    formatter: function (value, row, index) {
                        return self.previewAttachments(row.id_mpnote, row.type);
                    },
                },
                {
                    field: "date_add",
                    title: "Data Inserimento",
                    align: "center",
                    sortable: true,
                    formatter: function (value, row, index) {
                        return DateFormatter.toItalian(value);
                    },
                },
                {
                    field: "date_upd",
                    title: "Data Aggiornamento",
                    align: "center",
                    sortable: true,
                    formatter: function (value, row, index) {
                        return DateFormatter.toItalian(value);
                    },
                },
                {
                    field: "deleted",
                    title: "Cancellato",
                    align: "center",
                    sortable: true,
                    formatter: function (value, row, index) {
                        return value == 1 ? `<span class="badge badge-danger" onclick="window.toggleDeleted(this)" data-id="${row.id_mpnote}">Si</span>` : `<span class="badge badge-success" onclick="window.toggleDeleted(this)" data-id="${row.id_mpnote}">No</span>`;
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
                                <button type="button" class="btn btn-primary btn-sm" name="btn-edit-alias" title="Alias" data-id="${row.id_mpnote}">
                                    <span class="material-icons">edit</span>
                                </button>
                            </div>
                        `;
                    },
                },
            ],
        });
    }

    refresh() {
        $(this.table).bootstrapTable("refresh");
    }

    async previewAttachments(id_mpnote, type) {
        const self = this;
        const formData = new FormData();
        formData.append("action", "getAttachmentsPreview");
        formData.append("ajax", 1);
        formData.append("id_mpnote", id_mpnote);
        formData.append("type", type);

        const response = await fetch(self.adminControllerUrl, {
            method: "POST",
            body: formData,
        });

        const data = await response.json();

        return data.html;
    }
}
