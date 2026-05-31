<?php

namespace SiamesaLeads\Models;

use App\Models\Crud_model;

class Siamesa_facebook_sync_runs_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = "siamesa_facebook_sync_runs";
        parent::__construct($this->table);
    }

    public function recent($limit = 10)
    {
        $table = $this->db->prefixTable("siamesa_facebook_sync_runs");
        return $this->db->query("SELECT *
            FROM $table
            ORDER BY started_at DESC, id DESC
            LIMIT " . (int) $limit)->getResult();
    }
}
