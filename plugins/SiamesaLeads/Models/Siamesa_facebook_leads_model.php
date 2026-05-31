<?php

namespace SiamesaLeads\Models;

use App\Models\Crud_model;

class Siamesa_facebook_leads_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = "siamesa_facebook_leads";
        parent::__construct($this->table);
    }

    public function get_details($options = [])
    {
        $leads_table = $this->db->prefixTable("siamesa_facebook_leads");
        $users_table = $this->db->prefixTable("users");
        $where = "WHERE $leads_table.deleted=0";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $leads_table.id=" . (int) $id;
        }

        foreach (["status", "stage", "facebook_form_id", "campaign_name"] as $field) {
            $value = $this->_get_clean_value($options, $field);
            if ($value !== "" && $value !== null) {
                $where .= " AND $leads_table.$field=" . $this->db->escape($value);
            }
        }

        $start_date = $this->_get_clean_value($options, "start_date");
        if ($start_date) {
            $where .= " AND DATE($leads_table.facebook_created_time)>=" . $this->db->escape($start_date);
        }

        $end_date = $this->_get_clean_value($options, "end_date");
        if ($end_date) {
            $where .= " AND DATE($leads_table.facebook_created_time)<=" . $this->db->escape($end_date);
        }

        $child_age = $this->_get_clean_value($options, "child_age");
        if ($child_age !== "" && $child_age !== null) {
            $where .= " AND FLOOR($leads_table.child_age)=" . (int) $child_age;
        }

        $phone_filter = $this->_get_clean_value($options, "phone_filter");
        if ($phone_filter === "with_phone") {
            $where .= " AND $leads_table.phone_normalized IS NOT NULL AND $leads_table.phone_normalized!=''";
        } else if ($phone_filter === "without_phone") {
            $where .= " AND ($leads_table.phone_normalized IS NULL OR $leads_table.phone_normalized='')";
        }

        $duplicates = $this->_get_clean_value($options, "duplicates");
        if ($duplicates) {
            $where .= " AND $leads_table.phone_normalized IN (
                SELECT phone_normalized
                FROM $leads_table
                WHERE deleted=0 AND phone_normalized IS NOT NULL AND phone_normalized!=''
                GROUP BY phone_normalized
                HAVING COUNT(*) > 1
            )";
        }

        $search = trim((string) $this->_get_clean_value($options, "search"));
        if ($search !== "") {
            $escaped = $this->db->escapeLikeString($search);
            $where .= " AND (
                $leads_table.responsible_name LIKE '%" . $escaped . "%' ESCAPE '!'
                OR $leads_table.child_name LIKE '%" . $escaped . "%' ESCAPE '!'
                OR $leads_table.phone_original LIKE '%" . $escaped . "%' ESCAPE '!'
                OR $leads_table.phone_normalized LIKE '%" . $escaped . "%' ESCAPE '!'
                OR $leads_table.email LIKE '%" . $escaped . "%' ESCAPE '!'
            )";
        }

        $sql = "SELECT $leads_table.*,
                CONCAT($users_table.first_name, ' ', $users_table.last_name) AS assigned_to_name
            FROM $leads_table
            LEFT JOIN $users_table ON $users_table.id=$leads_table.assigned_to
            $where
            ORDER BY COALESCE($leads_table.facebook_created_time, $leads_table.imported_at) DESC, $leads_table.id DESC";

        return $this->db->query($sql);
    }

    public function dashboard_summary()
    {
        $table = $this->db->prefixTable("siamesa_facebook_leads");
        return $this->db->query("SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN DATE(COALESCE(facebook_created_time, imported_at))=CURDATE() THEN 1 ELSE 0 END) AS today,
                SUM(CASE WHEN COALESCE(facebook_created_time, imported_at) >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS last_7_days,
                SUM(CASE WHEN COALESCE(facebook_created_time, imported_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS last_30_days,
                AVG(child_age) AS average_child_age
            FROM $table
            WHERE deleted=0")->getRow();
    }

    public function grouped_counts($field, $limit = 10)
    {
        $allowed = ["status", "stage", "facebook_form_id", "form_name", "campaign_name"];
        if (!in_array($field, $allowed, true)) {
            return [];
        }

        $table = $this->db->prefixTable("siamesa_facebook_leads");
        return $this->db->query("SELECT COALESCE(NULLIF($field, ''), 'Sem informação') AS label, COUNT(*) AS total
            FROM $table
            WHERE deleted=0
            GROUP BY label
            ORDER BY total DESC, label ASC
            LIMIT " . (int) $limit)->getResult();
    }

    public function dropdown_values($field)
    {
        $allowed = ["status", "stage", "facebook_form_id", "campaign_name"];
        if (!in_array($field, $allowed, true)) {
            return [];
        }

        $table = $this->db->prefixTable("siamesa_facebook_leads");
        return $this->db->query("SELECT DISTINCT $field AS value
            FROM $table
            WHERE deleted=0 AND $field IS NOT NULL AND $field!=''
            ORDER BY $field ASC")->getResult();
    }
}
