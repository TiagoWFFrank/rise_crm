<?php
$phone = $lead->phone_original ?: $lead->phone_normalized;
$whatsapp_url = $lead->phone_normalized ? "https://wa.me/" . $lead->phone_normalized : "";
$status_options = [
    "NOVO" => "NOVO",
    "USADO" => "USADO",
    "RECUSADO" => "RECUSADO",
    "TELEMARKETING" => "TELEMARKETING"
];
$stage_options = [
    "captado" => "Captado",
    "contato" => "Contato",
    "visita" => "Visita",
    "negociacao" => "Negociação",
    "matricula" => "Matrícula",
    "encerrado" => "Encerrado"
];
if (!isset($stage_options[$lead->stage])) {
    $stage_options[$lead->stage ?: "captado"] = $lead->stage ?: "Captado";
}
?>

<?php echo form_open(get_uri("siamesa_leads/save_status"), ["id" => "siamesa-lead-form", "class" => "general-form", "role" => "form"]); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo (int) $lead->id; ?>" />

        <div class="row">
            <div class="col-md-6">
                <h4><?php echo esc($lead->responsible_name ?: "Lead sem nome"); ?></h4>
                <p><strong>Telefone:</strong> <span id="siamesa-modal-phone"><?php echo esc($phone ?: "-"); ?></span></p>
                <p><strong>Email:</strong> <?php echo esc($lead->email ?: "-"); ?></p>
                <p><strong>Cidade:</strong> <?php echo esc($lead->city ?: "-"); ?></p>
                <p><strong>Bairro:</strong> <?php echo esc($lead->neighborhood ?: "-"); ?></p>
            </div>
            <div class="col-md-6">
                <h4>Criança</h4>
                <p><strong>Nome:</strong> <?php echo esc($lead->child_name ?: "-"); ?></p>
                <p><strong>Idade:</strong> <?php echo esc($lead->child_age !== "" ? $lead->child_age : "-"); ?></p>
                <p><strong>Data Meta:</strong> <?php echo format_to_datetime($lead->facebook_created_time); ?></p>
                <p><strong>Facebook Lead ID:</strong> <code><?php echo esc($lead->facebook_lead_id); ?></code></p>
            </div>
        </div>

        <div class="row mt15">
            <div class="col-md-6">
                <h4>Origem</h4>
                <p><strong>Formulário:</strong> <?php echo esc($lead->form_name ?: $lead->facebook_form_id ?: "-"); ?></p>
                <p><strong>Campanha:</strong> <?php echo esc($lead->campaign_name ?: "-"); ?></p>
                <p><strong>Anúncio:</strong> <?php echo esc($lead->ad_name ?: $lead->facebook_ad_id ?: "-"); ?></p>
            </div>
            <div class="col-md-6">
                <h4>Ações manuais</h4>
                <?php if ($phone): ?>
                    <button type="button" class="btn btn-default mb10" id="siamesa-copy-phone" data-phone="<?php echo esc($phone); ?>"><i data-feather="copy" class="icon-16"></i> Copiar telefone</button>
                <?php endif; ?>
                <?php if ($whatsapp_url): ?>
                    <?php echo anchor($whatsapp_url, "<i data-feather='message-circle' class='icon-16'></i> Abrir WhatsApp", ["class" => "btn btn-default mb10", "target" => "_blank", "rel" => "noopener"]); ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group mt15">
            <div class="row">
                <label class="col-md-2">Status</label>
                <div class="col-md-4">
                    <?php echo form_dropdown("status", $status_options, strtoupper($lead->status ?: "NOVO"), ["class" => "form-control", "disabled" => !$can_edit]); ?>
                </div>
                <label class="col-md-2">Etapa</label>
                <div class="col-md-4">
                    <?php echo form_dropdown("stage", $stage_options, $lead->stage ?: "captado", ["class" => "form-control", "disabled" => !$can_edit]); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-2">Observações</label>
                <div class="col-md-10">
                    <?php echo form_textarea(["name" => "notes", "value" => $lead->notes, "class" => "form-control", "rows" => 3, "readonly" => !$can_edit]); ?>
                </div>
            </div>
        </div>

        <h4>Histórico</h4>
        <div class="table-responsive mb15">
            <table class="table table-hover">
                <tbody>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td class="w160"><?php echo format_to_datetime($event->created_at); ?></td>
                            <td class="w160"><?php echo esc($event->event_type); ?></td>
                            <td><?php echo esc($event->description ?: "-"); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <details>
            <summary>Raw payload</summary>
            <pre class="mt10 p10 bg-light" style="white-space: pre-wrap; max-height: 260px; overflow: auto;"><?php echo esc($lead->raw_payload ?: "{}"); ?></pre>
        </details>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang("close"); ?></button>
    <?php if ($can_edit): ?>
        <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang("save"); ?></button>
    <?php endif; ?>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#siamesa-lead-form").appForm({
            onSuccess: function (result) {
                if (result.success) {
                    $("#siamesa-leads-table").appTable({newData: result.data, dataId: result.id});
                    appAlert.success(result.message);
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
