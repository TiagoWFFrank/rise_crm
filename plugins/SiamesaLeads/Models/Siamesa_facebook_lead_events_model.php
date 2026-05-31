<?php

namespace SiamesaLeads\Models;

use App\Models\Crud_model;

class Siamesa_facebook_lead_events_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = "siamesa_facebook_lead_events";
        parent::__construct($this->table);
    }

    public function get_details($options = [])
    {
        $events_table = $this->db->prefixTable("siamesa_facebook_lead_events");
        $users_table = $this->db->prefixTable("users");
        $where = "WHERE 1=1";

        $lead_id = $this->_get_clean_value($options, "lead_id");
        if ($lead_id) {
            $where .= " AND $events_table.lead_id=" . (int) $lead_id;
        }

        $sql = "SELECT $events_table.*,
                CONCAT($users_table.first_name, ' ', $users_table.last_name) AS created_by_name
            FROM $events_table
            LEFT JOIN $users_table ON $users_table.id=$events_table.created_by
            $where
            ORDER BY $events_table.created_at DESC, $events_table.id DESC";

        return $this->db->query($sql);
    }
}
