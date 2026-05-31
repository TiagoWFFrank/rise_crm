<?php
$phone = $lead->phone_original ?: $lead->phone_normalized;
$whatsapp_url = $lead->phone_normalized ? "https://wa.me/" . $lead->phone_normalized : "";
$status_options = [
    "NOVO" => "NOVO",
    "USADO" => "USADO",
    "RECUSADO" => "RECUSADO",
    "TELEMARKETING" => "TELEMARKETING"
];
?>

<style>
    .siamesa-leads-detail pre,
    .siamesa-leads-detail code {
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    @media (max-width: 767.98px) {
        .siamesa-leads-detail .page-title {
            padding: 14px 15px;
        }

        .siamesa-leads-detail .page-title h1 {
            font-size: 20px;
            line-height: 1.25;
            margin-bottom: 10px;
        }

        .siamesa-leads-detail .page-title h4 {
            font-size: 17px;
            line-height: 1.3;
        }

        .siamesa-leads-detail .title-button-group {
            clear: both;
            display: flex;
            flex-direction: column;
            float: none !important;
            gap: 8px;
            width: 100%;
        }

        .siamesa-leads-detail .title-button-group .btn,
        .siamesa-leads-detail .title-button-group a.btn {
            justify-content: center;
            margin-left: 0 !important;
            width: 100%;
        }

        .siamesa-leads-detail .p20 {
            padding: 15px !important;
        }

        .siamesa-leads-detail .row > [class*="col-"] {
            margin-bottom: 10px;
        }

        .siamesa-leads-detail .table-responsive td {
            overflow-wrap: anywhere;
            white-space: normal;
        }

        .siamesa-leads-detail #siamesa-lead-status-form .btn {
            width: 100%;
        }
    }
</style>

<div id="page-content" class="page-wrapper clearfix siamesa-leads-detail">
    <?php echo view("includes/back_button", ["button_url" => get_uri("siamesa_leads"), "button_text" => "Leads SIAMESA", "extra_class" => "float-start dark"]); ?>

    <div class="clearfix"></div>

    <div class="row mt15">
        <div class="col-md-8">
            <div class="card">
                <div class="page-title clearfix">
                    <h1><?php echo esc($lead->responsible_name ?: "Lead sem nome"); ?></h1>
                    <div class="title-button-group skip-dropdown-migration">
                        <?php if ($phone): ?>
                            <button class="btn btn-default" id="siamesa-copy-phone" data-phone="<?php echo esc($phone); ?>"><i data-feather="copy" class="icon-16"></i> Copiar telefone</button>
                        <?php endif; ?>
                        <?php if ($whatsapp_url): ?>
                            <?php echo anchor($whatsapp_url, "<i data-feather='message-circle' class='icon-16'></i> Abrir WhatsApp", ["class" => "btn btn-default", "target" => "_blank", "rel" => "noopener"]); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="p20">
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Responsável</h4>
                            <p><strong>Nome:</strong> <?php echo esc($lead->responsible_name ?: "-"); ?></p>
                            <p><strong>Telefone:</strong> <?php echo esc($phone ?: "-"); ?></p>
                            <p><strong>Email:</strong> <?php echo esc($lead->email ?: "-"); ?></p>
                            <p><strong>Cidade:</strong> <?php echo esc($lead->city ?: "-"); ?></p>
                            <p><strong>Bairro:</strong> <?php echo esc($lead->neighborhood ?: "-"); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h4>Criança</h4>
                            <p><strong>Nome:</strong> <?php echo esc($lead->child_name ?: "-"); ?></p>
                            <p><strong>Idade:</strong> <?php echo esc($lead->child_age !== "" ? $lead->child_age : "-"); ?></p>
                        </div>
                    </div>

                    <div class="row mt20">
                        <div class="col-md-6">
                            <h4>Meta</h4>
                            <p><strong>Lead ID:</strong> <code><?php echo esc($lead->facebook_lead_id); ?></code></p>
                            <p><strong>Page ID:</strong> <?php echo esc($lead->facebook_page_id ?: "-"); ?></p>
                            <p><strong>Formulário:</strong> <?php echo esc($lead->form_name ?: $lead->facebook_form_id ?: "-"); ?></p>
                            <p><strong>Data Meta:</strong> <?php echo format_to_datetime($lead->facebook_created_time); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h4>Campanha</h4>
                            <p><strong>Campanha:</strong> <?php echo esc($lead->campaign_name ?: "-"); ?></p>
                            <p><strong>Conjunto:</strong> <?php echo esc($lead->facebook_adset_id ?: "-"); ?></p>
                            <p><strong>Anúncio:</strong> <?php echo esc($lead->ad_name ?: $lead->facebook_ad_id ?: "-"); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt15">
                <div class="page-title clearfix"><h4>Histórico de eventos</h4></div>
                <div class="table-responsive">
                    <table class="table table-hover mb0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Evento</th>
                                <th>Descrição</th>
                                <th>Usuário</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td><?php echo format_to_datetime($event->created_at); ?></td>
                                    <td><?php echo esc($event->event_type); ?></td>
                                    <td><?php echo esc($event->description ?: "-"); ?></td>
                                    <td><?php echo esc($event->created_by_name ?: "-"); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mt15">
                <div class="page-title clearfix"><h4>Raw payload</h4></div>
                <div class="p20">
                    <details>
                        <summary>Ver payload bruto</summary>
                        <pre class="mt15 p15 bg-light" style="white-space: pre-wrap;"><?php echo esc($lead->raw_payload ?: "{}"); ?></pre>
                    </details>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="page-title clearfix"><h4>Gestão</h4></div>
                <div class="p20">
                    <?php echo form_open(get_uri("siamesa_leads/save_status"), ["id" => "siamesa-lead-status-form", "class" => "general-form", "role" => "form"]); ?>
                    <input type="hidden" name="id" value="<?php echo (int) $lead->id; ?>">

                    <div class="form-group">
                        <label>Status</label>
                        <?php echo form_dropdown("status", $status_options, strtoupper($lead->status ?: "NOVO"), ["class" => "form-control", "disabled" => !$can_edit]); ?>
                    </div>

                    <div class="form-group">
                        <label>Etapa</label>
                        <?php echo form_input(["name" => "stage", "value" => $lead->stage ?: "captado", "class" => "form-control", "readonly" => !$can_edit]); ?>
                    </div>

                    <div class="form-group">
                        <label>Observações</label>
                        <?php echo form_textarea(["name" => "notes", "value" => $lead->notes, "class" => "form-control", "rows" => 8, "readonly" => !$can_edit]); ?>
                    </div>

                    <?php if ($can_edit): ?>
                        <button type="submit" class="btn btn-primary"><i data-feather="check-circle" class="icon-16"></i> Salvar</button>
                    <?php endif; ?>
                    <?php echo form_close(); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#siamesa-lead-status-form").appForm({
            onSuccess: function (result) {
                if (result.success) {
                    appAlert.success(result.message);
                } else {
                    appAlert.error(result.message);
                }
            }
        });

        $("#siamesa-copy-phone").on("click", function () {
            var phone = $(this).data("phone");
            if (navigator.clipboard) {
                navigator.clipboard.writeText(phone);
                appAlert.success("Telefone copiado.");
            }
        });
    });
</script>
