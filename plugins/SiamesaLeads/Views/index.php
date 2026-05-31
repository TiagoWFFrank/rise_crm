<?php
$summary = $summary ?: (object) ["total" => 0, "today" => 0, "last_7_days" => 0, "last_30_days" => 0, "average_child_age" => 0];
$siamesa_leads_select_options = function ($json_options) {
    $items = json_decode($json_options, true) ?: [];
    $options = [];
    foreach ($items as $item) {
        $options[$item["id"]] = $item["text"];
    }

    return $options;
};
$phone_filter_options = [
    "" => "Telefone: todos",
    "with_phone" => "Com telefone",
    "without_phone" => "Sem telefone"
];
$duplicates_filter_options = [
    "" => "Duplicados: todos",
    "1" => "Somente duplicados"
];
?>

<style>
    #siamesa-leads-summary .table,
    #siamesa-leads-summary .table > :not(caption) > * > * {
        color: var(--siamesa-cream, var(--bs-body-color, #EAD4BA)) !important;
    }

    #siamesa-leads-summary .text-off {
        color: rgba(239, 228, 214, 0.72) !important;
        opacity: 1;
    }

    .siamesa-leads-mobile .dtr-details {
        width: 100%;
    }

    .siamesa-leads-mobile .dtr-details .dtr-title {
        display: block;
        font-weight: 600;
        margin-bottom: 2px;
    }

    .siamesa-leads-mobile .dtr-data,
    .siamesa-leads-mobile pre,
    .siamesa-leads-mobile code {
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .siamesa-leads-mobile .action-option,
    .siamesa-leads-mobile td.option a {
        align-items: center;
        display: inline-flex;
        justify-content: center;
        min-height: 36px;
        min-width: 36px;
    }

    .siamesa-leads-mobile-filter-panel {
        display: none;
    }

    @media (max-width: 767.98px) {
        .siamesa-leads-mobile .page-title {
            padding: 14px 15px;
        }

        .siamesa-leads-mobile .page-title h1 {
            font-size: 20px;
            line-height: 1.25;
            margin-bottom: 10px;
        }

        .siamesa-leads-mobile .page-title h4 {
            font-size: 17px;
            line-height: 1.3;
        }

        .siamesa-leads-mobile .title-button-group {
            clear: both;
            display: flex;
            flex-direction: column;
            float: none !important;
            gap: 8px;
            width: 100%;
        }

        .siamesa-leads-mobile .title-button-group .btn,
        .siamesa-leads-mobile .title-button-group a.btn {
            justify-content: center;
            margin-left: 0 !important;
            width: 100%;
        }

        .siamesa-leads-mobile .p20 {
            padding: 15px !important;
        }

        .siamesa-leads-mobile .row > [class*="col-"] {
            margin-bottom: 10px;
        }

        .siamesa-leads-mobile .dashboard-icon-widget .card-body {
            align-items: center;
            display: flex;
            min-height: auto;
        }

        .siamesa-leads-mobile .dashboard-icon-widget .widget-details h1 {
            font-size: 20px;
            line-height: 1.2;
        }

        .siamesa-leads-mobile .siamesa-leads-filters {
            display: none;
        }

        .siamesa-leads-mobile-filter-panel {
            background: rgba(127, 127, 127, 0.06);
            border-bottom: 1px solid rgba(127, 127, 127, 0.18);
            display: block;
        }

        .siamesa-leads-mobile-filter-panel .btn,
        .siamesa-leads-mobile-filter-panel .form-control {
            width: 100%;
        }

        .siamesa-leads-mobile-filter-actions,
        .siamesa-leads-mobile .siamesa-leads-filter-actions {
            display: grid;
            gap: 8px;
            grid-template-columns: 1fr;
            text-align: left !important;
        }

        .siamesa-leads-mobile .filter-section-flex-row,
        .siamesa-leads-mobile .filter-section-left,
        .siamesa-leads-mobile .filter-section-right {
            display: block !important;
            width: 100% !important;
        }

        .siamesa-leads-mobile .filter-item-box {
            margin: 0 0 8px !important;
            width: 100% !important;
        }

        .siamesa-leads-mobile .filter-item-box .btn,
        .siamesa-leads-mobile .filter-item-box .form-control,
        .siamesa-leads-mobile .filter-item-box .select2-container {
            width: 100% !important;
        }

        .siamesa-leads-mobile .dataTables_filter,
        .siamesa-leads-mobile .dt-search {
            text-align: left;
            width: 100%;
        }

        .siamesa-leads-mobile .dataTables_filter input,
        .siamesa-leads-mobile .dt-search input {
            margin-left: 0 !important;
            width: 100% !important;
        }

        .siamesa-leads-mobile .dataTables_info,
        .siamesa-leads-mobile .dataTables_length,
        .siamesa-leads-mobile .dataTables_paginate,
        .siamesa-leads-mobile .dt-buttons {
            float: none !important;
            margin-top: 8px;
            text-align: left;
            width: 100%;
        }

        .siamesa-leads-mobile table.dataTable > tbody > tr > td {
            overflow-wrap: anywhere;
            white-space: normal;
        }

        .siamesa-leads-mobile table.dataTable td.option {
            min-width: 44px;
            white-space: nowrap;
        }

        .siamesa-leads-mobile #siamesa-leads-table th.option,
        .siamesa-leads-mobile #siamesa-leads-table td.option {
            min-width: 76px;
            width: 76px !important;
        }

        #ajaxModal .modal-dialog {
            height: 100dvh;
            margin: 0;
            max-width: none;
            width: 100%;
        }

        #ajaxModal .modal-content {
            border-radius: 0;
            min-height: 100dvh;
        }

        #ajaxModal .modal-body {
            max-height: calc(100dvh - 120px);
            overflow-y: auto;
            padding: 15px;
        }

        #ajaxModal .modal-footer {
            background: var(--bs-body-bg, #fff);
            bottom: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            position: sticky;
        }

        #ajaxModal .modal-footer .btn {
            flex: 1 1 120px;
        }

        #ajaxModal .btn {
            white-space: normal;
        }
    }
</style>

<div id="page-content" class="page-wrapper clearfix siamesa-leads-mobile">
    <div class="card">
        <div class="page-title clearfix">
            <h1>Leads SIAMESA</h1>
            <div class="title-button-group skip-dropdown-migration">
                <?php if ($can_sync): ?>
                    <?php echo js_anchor("<i data-feather='refresh-cw' class='icon-16'></i> Sincronizar", ["id" => "siamesa-leads-sync-btn", "class" => "btn btn-default"]); ?>
                <?php endif; ?>
                <?php if ($can_export): ?>
                    <?php echo anchor(get_uri("siamesa_leads/export_csv"), "<i data-feather='download' class='icon-16'></i> Exportar CSV", ["id" => "siamesa-leads-export-btn", "class" => "btn btn-default"]); ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="p20">
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="card dashboard-icon-widget">
                        <div class="card-body">
                            <div class="widget-icon bg-primary"><i data-feather="target" class="icon"></i></div>
                            <div class="widget-details"><h1><?php echo (int) $summary->total; ?></h1><span>Total de leads</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card dashboard-icon-widget">
                        <div class="card-body">
                            <div class="widget-icon bg-success"><i data-feather="calendar" class="icon"></i></div>
                            <div class="widget-details"><h1><?php echo (int) $summary->today; ?></h1><span>Hoje</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card dashboard-icon-widget">
                        <div class="card-body">
                            <div class="widget-icon bg-info"><i data-feather="activity" class="icon"></i></div>
                            <div class="widget-details"><h1><?php echo (int) $summary->last_7_days; ?></h1><span>Últimos 7 dias</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card dashboard-icon-widget">
                        <div class="card-body">
                            <div class="widget-icon bg-warning"><i data-feather="bar-chart-2" class="icon"></i></div>
                            <div class="widget-details"><h1><?php echo round((float) $summary->average_child_age, 1); ?></h1><span>Média de idade</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card dashboard-icon-widget">
                        <div class="card-body">
                            <div class="widget-icon bg-secondary"><i data-feather="calendar" class="icon"></i></div>
                            <div class="widget-details"><h1><?php echo (int) $summary->last_30_days; ?></h1><span>Últimos 30 dias</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt15" id="siamesa-leads-summary">
                <div class="col-md-4">
                    <div class="card">
                        <div class="page-title clearfix"><h4>Status</h4></div>
                        <div class="table-responsive">
                            <table class="table table-hover mb0">
                                <?php foreach ($status_counts as $row): ?>
                                    <tr><td><?php echo esc($row->label); ?></td><td class="text-end"><?php echo (int) $row->total; ?></td></tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="page-title clearfix"><h4>Campanhas</h4></div>
                        <div class="table-responsive">
                            <table class="table table-hover mb0">
                                <?php foreach ($campaign_counts as $row): ?>
                                    <tr><td><?php echo esc($row->label); ?></td><td class="text-end"><?php echo (int) $row->total; ?></td></tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="page-title clearfix"><h4>Sincronizações</h4></div>
                        <div class="table-responsive">
                            <table class="table table-hover mb0">
                                <?php foreach ($sync_runs as $run): ?>
                                    <tr>
                                        <td><?php echo format_to_datetime($run->started_at); ?><br><span class="text-off"><?php echo esc($run->status); ?></span></td>
                                        <td class="text-end"><?php echo (int) $run->processed; ?> / <?php echo (int) $run->errors; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="siamesa-leads-mobile-filter-panel p15 border-top">
            <div class="row">
                <div class="col-sm-6">
                    <label for="siamesa-leads-mobile-start-date">Data inicial</label>
                    <?php echo form_input(["id" => "siamesa-leads-mobile-start-date", "class" => "form-control", "type" => "date"]); ?>
                </div>
                <div class="col-sm-6">
                    <label for="siamesa-leads-mobile-end-date">Data final</label>
                    <?php echo form_input(["id" => "siamesa-leads-mobile-end-date", "class" => "form-control", "type" => "date"]); ?>
                </div>
                <div class="col-sm-6">
                    <label for="siamesa-leads-mobile-status">Status</label>
                    <?php echo form_dropdown("siamesa_leads_mobile_status", $siamesa_leads_select_options($status_dropdown), "", ["id" => "siamesa-leads-mobile-status", "class" => "form-control"]); ?>
                </div>
                <div class="col-sm-6">
                    <label for="siamesa-leads-mobile-stage">Etapa</label>
                    <?php echo form_dropdown("siamesa_leads_mobile_stage", $siamesa_leads_select_options($stage_dropdown), "", ["id" => "siamesa-leads-mobile-stage", "class" => "form-control"]); ?>
                </div>
                <div class="col-sm-6">
                    <label for="siamesa-leads-mobile-form">Formulário</label>
                    <?php echo form_dropdown("siamesa_leads_mobile_form", $siamesa_leads_select_options($form_dropdown), "", ["id" => "siamesa-leads-mobile-form", "class" => "form-control"]); ?>
                </div>
                <div class="col-sm-6">
                    <label for="siamesa-leads-mobile-campaign">Campanha</label>
                    <?php echo form_dropdown("siamesa_leads_mobile_campaign", $siamesa_leads_select_options($campaign_dropdown), "", ["id" => "siamesa-leads-mobile-campaign", "class" => "form-control"]); ?>
                </div>
                <div class="col-sm-6">
                    <label for="siamesa-leads-mobile-phone">Telefone</label>
                    <?php echo form_dropdown("siamesa_leads_mobile_phone", $phone_filter_options, "", ["id" => "siamesa-leads-mobile-phone", "class" => "form-control"]); ?>
                </div>
                <div class="col-sm-6">
                    <label for="siamesa-leads-mobile-duplicates">Duplicados</label>
                    <?php echo form_dropdown("siamesa_leads_mobile_duplicates", $duplicates_filter_options, "", ["id" => "siamesa-leads-mobile-duplicates", "class" => "form-control"]); ?>
                </div>
                <div class="col-sm-4">
                    <label for="siamesa-leads-mobile-age">Idade</label>
                    <?php echo form_input(["id" => "siamesa-leads-mobile-age", "class" => "form-control", "placeholder" => "Idade"]); ?>
                </div>
                <div class="col-sm-8">
                    <label for="siamesa-leads-mobile-search">Busca</label>
                    <?php echo form_input(["id" => "siamesa-leads-mobile-search", "class" => "form-control", "placeholder" => "Nome, telefone ou email"]); ?>
                </div>
                <div class="col-sm-12 siamesa-leads-mobile-filter-actions">
                    <button type="button" id="siamesa-leads-mobile-filter-apply" class="btn btn-primary"><i data-feather="filter" class="icon-16"></i> Filtrar</button>
                    <button type="button" id="siamesa-leads-mobile-filter-clear" class="btn btn-default"><i data-feather="x" class="icon-16"></i> Limpar</button>
                </div>
            </div>
        </div>

        <div class="p20 border-top">
            <div class="row siamesa-leads-filters">
                <div class="col-md-2"><?php echo form_input(["id" => "siamesa-filter-age", "class" => "form-control", "placeholder" => "Idade"]); ?></div>
                <div class="col-md-5"><?php echo form_input(["id" => "siamesa-filter-search", "class" => "form-control", "placeholder" => "Nome, telefone ou email"]); ?></div>
                <div class="col-md-5 text-end siamesa-leads-filter-actions">
                    <button id="siamesa-filter-apply" class="btn btn-primary"><i data-feather="filter" class="icon-16"></i> Filtrar</button>
                    <button id="siamesa-filter-clear" class="btn btn-default"><i data-feather="x" class="icon-16"></i> Limpar</button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="siamesa-leads-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        var statusOptions = <?php echo $status_dropdown; ?>,
            stageOptions = <?php echo $stage_dropdown; ?>,
            formOptions = <?php echo $form_dropdown; ?>,
            campaignOptions = <?php echo $campaign_dropdown; ?>;

        var recalcSiamesaLeadsTables = function () {
            if (!$.fn.dataTable) {
                return;
            }

            var visibleTables = $.fn.dataTable.tables({visible: true, api: true});
            visibleTables.columns.adjust();
            if (visibleTables.responsive && visibleTables.responsive.recalc) {
                visibleTables.responsive.recalc();
            }
        };

        $("#siamesa-leads-table").on("draw.dt", function () {
            setTimeout(recalcSiamesaLeadsTables, 0);
        });

        $("#siamesa-leads-table").appTable({
            source: "<?php echo_uri("siamesa_leads/list_data"); ?>",
            order: [[0, "desc"]],
            stateSave: false,
            tableRefreshButton: true,
            rangeDatepicker: [{startDate: {name: "start_date", value: ""}, endDate: {name: "end_date", value: ""}, showClearButton: true, label: "Data do lead", ranges: ["today", "last_7_days", "last_30_days", "this_month", "last_month"]}],
            filterDropdown: [
                {name: "status", class: "w160", options: statusOptions},
                {name: "stage", class: "w160", options: stageOptions},
                {name: "facebook_form_id", class: "w180", options: formOptions},
                {name: "campaign_name", class: "w200", options: campaignOptions},
                {name: "phone_filter", class: "w160", options: [{id: "", text: "Telefone: todos"}, {id: "with_phone", text: "Com telefone"}, {id: "without_phone", text: "Sem telefone"}]},
                {name: "duplicates", class: "w140", options: [{id: "", text: "Duplicados: todos"}, {id: "1", text: "Somente duplicados"}]}
            ],
            filterParams: {
                child_age: "",
                search: ""
            },
            columns: [
                {title: "Data do lead", "class": "w150", order_by: "facebook_created_time"},
                {title: "Responsável", "class": "all"},
                {title: "Telefone", "class": "all w130"},
                {title: "Email", "class": "w180"},
                {title: "Criança"},
                {title: "Idade", "class": "text-center w80"},
                {title: "Formulário", "class": "w160"},
                {title: "Campanha", "class": "w160"},
                {title: "Anúncio", "class": "w160"},
                {title: "Status", "class": "text-center w120"},
                {title: "Etapa", "class": "text-center w120"},
                {title: "Última atualização", "class": "w150"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "all text-center option w100"}
            ],
            printColumns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
            xlsColumns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]
        });

        function tableSettings() {
            return window.InstanceCollection ? window.InstanceCollection["siamesa-leads-table"] : null;
        }

        var applySiamesaLeadsMobileFilters = function () {
            var settings = tableSettings();
            if (settings) {
                settings.filterParams.start_date = $("#siamesa-leads-mobile-start-date").val();
                settings.filterParams.end_date = $("#siamesa-leads-mobile-end-date").val();
                settings.filterParams.status = $("#siamesa-leads-mobile-status").val();
                settings.filterParams.stage = $("#siamesa-leads-mobile-stage").val();
                settings.filterParams.facebook_form_id = $("#siamesa-leads-mobile-form").val();
                settings.filterParams.campaign_name = $("#siamesa-leads-mobile-campaign").val();
                settings.filterParams.phone_filter = $("#siamesa-leads-mobile-phone").val();
                settings.filterParams.duplicates = $("#siamesa-leads-mobile-duplicates").val();
                settings.filterParams.child_age = $("#siamesa-leads-mobile-age").val();
                settings.filterParams.search = $("#siamesa-leads-mobile-search").val();
            }

            $("#siamesa-filter-age").val($("#siamesa-leads-mobile-age").val());
            $("#siamesa-filter-search").val($("#siamesa-leads-mobile-search").val());
            $("#siamesa-leads-table").appTable({reload: true});
        };

        $("#siamesa-leads-mobile-filter-apply").on("click", function () {
            applySiamesaLeadsMobileFilters();
        });

        $("#siamesa-leads-mobile-filter-clear").on("click", function () {
            $("#siamesa-leads-mobile-start-date,#siamesa-leads-mobile-end-date,#siamesa-leads-mobile-status,#siamesa-leads-mobile-stage,#siamesa-leads-mobile-form,#siamesa-leads-mobile-campaign,#siamesa-leads-mobile-phone,#siamesa-leads-mobile-duplicates,#siamesa-leads-mobile-age,#siamesa-leads-mobile-search").val("");
            applySiamesaLeadsMobileFilters();
        });

        $("#siamesa-filter-apply").on("click", function () {
            var settings = tableSettings();
            if (settings) {
                settings.filterParams.child_age = $("#siamesa-filter-age").val();
                settings.filterParams.search = $("#siamesa-filter-search").val();
            }
            $("#siamesa-leads-table").appTable({reload: true});
        });

        $("#siamesa-filter-clear").on("click", function () {
            $("#siamesa-filter-age,#siamesa-filter-search").val("");
            var settings = tableSettings();
            if (settings) {
                settings.filterParams.child_age = "";
                settings.filterParams.search = "";
            }
            $("#siamesa-leads-table").appTable({reload: true});
        });

        $("#siamesa-leads-export-btn").on("click", function () {
            var settings = tableSettings(),
                params = settings ? $.param(settings.filterParams || {}) : "";
            this.href = "<?php echo get_uri("siamesa_leads/export_csv"); ?>" + (params ? "?" + params : "");
        });

        $("#siamesa-leads-sync-btn").on("click", function () {
            appLoader.show();
            appAjaxRequest({
                url: "<?php echo_uri("siamesa_leads/sync"); ?>",
                type: "POST",
                dataType: "json",
                data: {since_days: 30, form_id: "2135130517298155"},
                success: function (result) {
                    appLoader.hide();
                    if (result.success) {
                        appAlert.success(result.message);
                        $("#siamesa-leads-table").appTable({reload: true});
                    } else {
                        appAlert.error(result.message + (result.details ? "\n" + result.details : ""));
                    }
                }
            });
        });
    });
</script>
