<?php
$permissions = isset($permissions) && is_array($permissions) ? $permissions : [];
if (!$permissions) {
    $role_id = service("uri")->getSegment(3);
    if ($role_id) {
        $db = db_connect();
        $roles_table = $db->prefixTable("roles");
        $role = $db->query("SELECT permissions FROM $roles_table WHERE id=" . (int) $role_id . " LIMIT 1")->getRow();
        $permissions = $role && $role->permissions ? @unserialize($role->permissions) : [];
        $permissions = is_array($permissions) ? $permissions : [];
    }
}
?>
<li>
    <span data-feather="target" class="icon-14 ml-20"></span>
    <h5>Leads SIAMESA:</h5>
    <div>
        <?php echo form_checkbox("siamesa_leads_view", "1", get_array_value($permissions, "siamesa_leads_view") ? true : false, "id='siamesa_leads_view' class='form-check-input'"); ?>
        <label for="siamesa_leads_view">Visualizar leads</label>
    </div>
    <div>
        <?php echo form_checkbox("siamesa_leads_edit", "1", get_array_value($permissions, "siamesa_leads_edit") ? true : false, "id='siamesa_leads_edit' class='form-check-input'"); ?>
        <label for="siamesa_leads_edit">Editar status e etapa</label>
    </div>
    <div>
        <?php echo form_checkbox("siamesa_leads_export", "1", get_array_value($permissions, "siamesa_leads_export") ? true : false, "id='siamesa_leads_export' class='form-check-input'"); ?>
        <label for="siamesa_leads_export">Exportar leads</label>
    </div>
    <div>
        <?php echo form_checkbox("siamesa_leads_sync", "1", get_array_value($permissions, "siamesa_leads_sync") ? true : false, "id='siamesa_leads_sync' class='form-check-input'"); ?>
        <label for="siamesa_leads_sync">Executar sincronização</label>
    </div>
</li>
