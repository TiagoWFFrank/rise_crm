<?php

namespace SiamesaLeads\Controllers;

use App\Controllers\Security_Controller;

class SiamesaLeads extends Security_Controller
{
    public $Siamesa_facebook_leads_model;
    public $Siamesa_facebook_lead_events_model;
    public $Siamesa_facebook_sync_runs_model;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();

        if (function_exists("siamesa_leads_install_or_update")) {
            siamesa_leads_install_or_update();
        }

        $this->Siamesa_facebook_leads_model = model("SiamesaLeads\Models\Siamesa_facebook_leads_model");
        $this->Siamesa_facebook_lead_events_model = model("SiamesaLeads\Models\Siamesa_facebook_lead_events_model");
        $this->Siamesa_facebook_sync_runs_model = model("SiamesaLeads\Models\Siamesa_facebook_sync_runs_model");
    }

    public function index()
    {
        $this->_require_permission("view");

        $view_data["summary"] = $this->Siamesa_facebook_leads_model->dashboard_summary();
        $view_data["status_counts"] = $this->Siamesa_facebook_leads_model->grouped_counts("status");
        $view_data["campaign_counts"] = $this->Siamesa_facebook_leads_model->grouped_counts("campaign_name");
        $view_data["form_counts"] = $this->Siamesa_facebook_leads_model->grouped_counts("form_name");
        $view_data["sync_runs"] = $this->Siamesa_facebook_sync_runs_model->recent(6);
        $view_data["status_dropdown"] = $this->_dropdown_options("status", "Todos os status");
        $view_data["stage_dropdown"] = $this->_dropdown_options("stage", "Todas as etapas");
        $view_data["form_dropdown"] = $this->_dropdown_options("facebook_form_id", "Todos os formulários");
        $view_data["campaign_dropdown"] = $this->_dropdown_options("campaign_name", "Todas as campanhas");
        $view_data["can_edit"] = $this->_can("edit");
        $view_data["can_export"] = $this->_can("export");
        $view_data["can_sync"] = $this->_can("sync");

        return $this->template->render("SiamesaLeads\Views\index", $view_data);
    }

    public function list_data()
    {
        $this->_require_permission("view");

        $options = [
            "start_date" => $this->request->getPost("start_date"),
            "end_date" => $this->request->getPost("end_date"),
            "status" => $this->request->getPost("status"),
            "stage" => $this->request->getPost("stage"),
            "facebook_form_id" => $this->request->getPost("facebook_form_id"),
            "campaign_name" => $this->request->getPost("campaign_name"),
            "child_age" => $this->request->getPost("child_age"),
            "phone_filter" => $this->request->getPost("phone_filter"),
            "duplicates" => $this->request->getPost("duplicates"),
            "search" => $this->request->getPost("search")
        ];

        $list_data = $this->Siamesa_facebook_leads_model->get_details($options)->getResult();
        $result = [];

        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data);
        }

        echo json_encode(["data" => $result]);
    }

    public function view($id = 0)
    {
        $this->_require_permission("view");
        validate_numeric_value($id);

        $lead = $this->Siamesa_facebook_leads_model->get_details(["id" => $id])->getRow();
        if (!$lead) {
            app_redirect("siamesa_leads");
        }

        $view_data["lead"] = $lead;
        $view_data["events"] = $this->Siamesa_facebook_lead_events_model->get_details(["lead_id" => $id])->getResult();
        $view_data["can_edit"] = $this->_can("edit");

        return $this->template->render("SiamesaLeads\Views\detail", $view_data);
    }

    public function lead_modal_form()
    {
        $this->_require_permission("view");
        $id = (int) $this->request->getPost("id");
        validate_numeric_value($id);

        $lead = $this->Siamesa_facebook_leads_model->get_details(["id" => $id])->getRow();
        if (!$lead) {
            show_404();
        }

        $view_data["lead"] = $lead;
        $view_data["events"] = $this->Siamesa_facebook_lead_events_model->get_details(["lead_id" => $id])->getResult();
        $view_data["can_edit"] = $this->_can("edit");
        return $this->template->view("SiamesaLeads\Views\modal_lead", $view_data);
    }

    public function save_status()
    {
        $this->_require_permission("edit");
        $this->validate_submitted_data([
            "id" => "required|numeric"
        ]);

        $id = (int) $this->request->getPost("id");
        $lead = $this->Siamesa_facebook_leads_model->get_one($id);
        if (!$lead || !$lead->id) {
            echo json_encode(["success" => false, "message" => "Lead não encontrado."]);
            return;
        }

        $allowed_statuses = ["NOVO", "USADO", "RECUSADO", "TELEMARKETING"];
        $status = strtoupper(trim((string) $this->request->getPost("status")));
        if (!in_array($status, $allowed_statuses, true)) {
            $status = "NOVO";
        }

        $data = [
            "status" => $status,
            "stage" => trim((string) $this->request->getPost("stage")) ?: "captado",
            "assigned_to" => $this->request->getPost("assigned_to") ? (int) $this->request->getPost("assigned_to") : null,
            "notes" => $this->request->getPost("notes")
        ];

        $this->Siamesa_facebook_leads_model->ci_save($data, $id);
        $this->Siamesa_facebook_lead_events_model->ci_save([
            "lead_id" => $id,
            "event_type" => "lead_status_updated",
            "description" => "Status/etapa atualizados manualmente.",
            "payload" => json_encode($data, JSON_UNESCAPED_UNICODE),
            "created_by" => $this->login_user->id
        ]);

        $updated = $this->Siamesa_facebook_leads_model->get_details(["id" => $id])->getRow();
        echo json_encode([
            "success" => true,
            "id" => $id,
            "data" => $this->_make_row($updated),
            "message" => "Lead atualizado."
        ]);
    }

    public function sync()
    {
        $this->_require_permission("sync");
        $since_days = (int) ($this->request->getPost("since_days") ?: 30);
        $form_id = $this->request->getPost("form_id") ?: "2135130517298155";

        $cmd = "php " . escapeshellarg(PLUGINPATH . "SiamesaLeads/Scripts/sync.php")
            . " --form-id " . escapeshellarg($form_id)
            . " --since-days " . escapeshellarg((string) $since_days);
        $output = [];
        $exit_code = 0;
        exec($cmd . " 2>&1", $output, $exit_code);

        echo json_encode([
            "success" => $exit_code === 0,
            "message" => $exit_code === 0 ? "Sincronização concluída." : "Sincronização falhou.",
            "details" => implode("\n", array_slice($output, -8))
        ]);
    }

    public function export_csv()
    {
        $this->_require_permission("export");

        $options = [
            "start_date" => $this->request->getGet("start_date"),
            "end_date" => $this->request->getGet("end_date"),
            "status" => $this->request->getGet("status"),
            "stage" => $this->request->getGet("stage"),
            "facebook_form_id" => $this->request->getGet("facebook_form_id"),
            "campaign_name" => $this->request->getGet("campaign_name"),
            "child_age" => $this->request->getGet("child_age"),
            "phone_filter" => $this->request->getGet("phone_filter"),
            "duplicates" => $this->request->getGet("duplicates"),
            "search" => $this->request->getGet("search")
        ];

        $rows = $this->Siamesa_facebook_leads_model->get_details($options)->getResultArray();
        $filename = "leads_siamesa_" . date("Ymd_His") . ".csv";

        header("Content-Type: text/csv; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"$filename\"");

        $out = fopen("php://output", "w");
        fputcsv($out, [
            "Data do lead", "Responsável", "Telefone", "Email", "Criança", "Idade",
            "Formulário", "Campanha", "Anúncio", "Status", "Etapa", "Cidade", "Bairro",
            "Facebook Lead ID", "Última atualização"
        ], ";");

        foreach ($rows as $row) {
            fputcsv($out, [
                $row["facebook_created_time"],
                $row["responsible_name"],
                $row["phone_original"] ?: $row["phone_normalized"],
                $row["email"],
                $row["child_name"],
                $row["child_age"],
                $row["form_name"] ?: $row["facebook_form_id"],
                $row["campaign_name"],
                $row["ad_name"],
                $row["status"],
                $row["stage"],
                $row["city"],
                $row["neighborhood"],
                $row["facebook_lead_id"],
                $row["updated_at"]
            ], ";");
        }

        fclose($out);
        exit;
    }

    private function _dropdown_options($field, $empty_label)
    {
        $options = [["id" => "", "text" => $empty_label]];
        if ($field === "status") {
            foreach (["NOVO", "USADO", "RECUSADO", "TELEMARKETING"] as $status) {
                $options[] = ["id" => $status, "text" => $status];
            }

            return json_encode($options);
        }

        foreach ($this->Siamesa_facebook_leads_model->dropdown_values($field) as $row) {
            $options[] = ["id" => $row->value, "text" => $row->value];
        }

        return json_encode($options);
    }

    private function _make_row($data)
    {
        $phone = $data->phone_original ?: $data->phone_normalized;
        $whatsapp_url = $data->phone_normalized ? "https://wa.me/" . $data->phone_normalized : "";

        $actions = modal_anchor(get_uri("siamesa_leads/lead_modal_form"), "<i data-feather='eye' class='icon-16'></i>", ["class" => "action-option", "title" => "Detalhes", "data-post-id" => $data->id]);
        if ($whatsapp_url) {
            $actions .= anchor($whatsapp_url, "<i data-feather='message-circle' class='icon-16'></i>", ["class" => "action-option", "title" => "Abrir WhatsApp manualmente", "target" => "_blank", "rel" => "noopener"]);
        }

        return [
            format_to_datetime($data->facebook_created_time ?: $data->imported_at),
            modal_anchor(get_uri("siamesa_leads/lead_modal_form"), $data->responsible_name ?: "Sem nome", ["title" => "Detalhes", "data-post-id" => $data->id]),
            $phone ?: "-",
            $data->email ?: "-",
            $data->child_name ?: "-",
            $data->child_age !== null && $data->child_age !== "" ? (string) $data->child_age : "-",
            $data->form_name ?: $data->facebook_form_id,
            $data->campaign_name ?: "-",
            $data->ad_name ?: "-",
            "<span class='badge bg-info'>" . esc($data->status ?: "NOVO") . "</span>",
            "<span class='badge bg-secondary'>" . esc($data->stage ?: "captado") . "</span>",
            format_to_datetime($data->updated_at),
            $actions
        ];
    }

    private function _require_permission($permission)
    {
        if (!$this->_can($permission)) {
            app_redirect("forbidden");
        }
    }

    private function _can($permission)
    {
        if ($this->login_user->is_admin) {
            return true;
        }

        if ($permission === "view" && $this->login_user->user_type === "staff") {
            return true;
        }

        return (bool) get_array_value($this->login_user->permissions, "siamesa_leads_" . $permission);
    }
}
