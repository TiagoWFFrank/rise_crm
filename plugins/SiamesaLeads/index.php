<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
Plugin Name: Leads SIAMESA
Description: Gestão de leads captados pelo Facebook Lead Ads da SIAMESA.
Version: 1.0.0
Requires at least: 2.8
*/

app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
    foreach (siamesa_leads_left_menu_native_items() as $key => $item) {
        $sidebar_menu[$key] = $item;
    }

    return $sidebar_menu;
});

app_hooks()->add_action('app_hook_role_permissions_extension', function () {
    echo view("SiamesaLeads\Views\permissions");
});

app_hooks()->add_filter('app_filter_role_permissions_save_data', function ($permissions) {
    $request = service("request");
    $permissions["siamesa_leads_view"] = $request->getPost("siamesa_leads_view") ? "1" : "";
    $permissions["siamesa_leads_edit"] = $request->getPost("siamesa_leads_edit") ? "1" : "";
    $permissions["siamesa_leads_export"] = $request->getPost("siamesa_leads_export") ? "1" : "";
    $permissions["siamesa_leads_sync"] = $request->getPost("siamesa_leads_sync") ? "1" : "";

    return $permissions;
});

if (function_exists("service")) {
    require __DIR__ . "/Config/Routes.php";
}

if (!function_exists("siamesa_leads_install_or_update")) {
    function siamesa_leads_left_menu_native_items()
    {
        return [
            "siamesa_leads" => [
                "name" => "Leads SIAMESA",
                "url" => get_uri("siamesa_leads"),
                "is_custom_menu_item" => true,
                "class" => "target",
                "position" => 16
            ]
        ];
    }

    function siamesa_leads_sync_left_menu_settings($db, $dbprefix)
    {
        $settings_table = $dbprefix . "settings";
        if (!$db->tableExists($settings_table)) {
            return;
        }

        $native_names = [];
        $native_items = [];
        foreach (siamesa_leads_left_menu_native_items() as $item) {
            $native_names[] = $item["name"];
            $native_items[] = ["name" => $item["name"]];
        }

        $legacy_names = ["Captação SIAMESA", "FacebookLeadsSiamesa", "SiamesaLeads"];
        $rows = $db->query("SELECT setting_name, setting_value FROM `" . $settings_table . "`
            WHERE deleted=0 AND (setting_name='default_left_menu' OR setting_name LIKE 'user\\_%\\_left_menu')")->getResult();

        foreach ($rows as $row) {
            $items = @unserialize($row->setting_value);
            if (!is_array($items) || !count($items)) {
                continue;
            }

            $changed = false;
            $has_native_item = false;
            $rebuilt = [];
            $insert_after = null;

            foreach ($items as $index => $item) {
                $name = $item["name"] ?? "";

                if (in_array($name, $native_names, true)) {
                    $has_native_item = true;
                    $rebuilt[] = $item;
                    continue;
                }

                if (in_array($name, $legacy_names, true)) {
                    $changed = true;
                    continue;
                }

                $rebuilt[] = $item;

                if ($name === "Captação" || $name === "SIAMESA Captação") {
                    $insert_after = count($rebuilt);
                }
            }

            if (!$has_native_item) {
                if ($insert_after === null) {
                    $insert_after = min(16, count($rebuilt));
                }
                array_splice($rebuilt, $insert_after, 0, $native_items);
                $changed = true;
            }

            if ($changed) {
                $db->query("UPDATE `" . $settings_table . "` SET setting_value=" . $db->escape(serialize($rebuilt)) . "
                    WHERE setting_name=" . $db->escape($row->setting_name));
            }
        }
    }

    function siamesa_leads_ensure_column($db, $table, $column, $definition)
    {
        $exists = $db->query("SHOW COLUMNS FROM `" . $table . "` LIKE " . $db->escape($column))->getRow();
        if (!$exists) {
            $db->query("ALTER TABLE `" . $table . "` ADD `" . $column . "` " . $definition);
        }
    }

    function siamesa_leads_ensure_index($db, $table, $index, $definition)
    {
        $exists = $db->query("SHOW INDEX FROM `" . $table . "` WHERE Key_name=" . $db->escape($index))->getRow();
        if (!$exists) {
            $db->query("ALTER TABLE `" . $table . "` ADD " . $definition);
        }
    }

    function siamesa_leads_install_or_update()
    {
        if (!function_exists("db_connect")) {
            return;
        }

        try {
            $db = db_connect();
            $dbprefix = $db->getPrefix();
            siamesa_leads_sync_left_menu_settings($db, $dbprefix);

            $leads_table = $dbprefix . "siamesa_facebook_leads";
            if (!$db->tableExists($leads_table)) {
                $db->query("CREATE TABLE IF NOT EXISTS `" . $leads_table . "` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `facebook_lead_id` varchar(80) NOT NULL,
                    `facebook_page_id` varchar(80) DEFAULT NULL,
                    `facebook_form_id` varchar(80) DEFAULT NULL,
                    `facebook_ad_id` varchar(80) DEFAULT NULL,
                    `facebook_campaign_id` varchar(80) DEFAULT NULL,
                    `facebook_adset_id` varchar(80) DEFAULT NULL,
                    `form_name` varchar(255) DEFAULT NULL,
                    `campaign_name` varchar(255) DEFAULT NULL,
                    `ad_name` varchar(255) DEFAULT NULL,
                    `responsible_name` varchar(255) DEFAULT NULL,
                    `phone_original` varchar(80) DEFAULT NULL,
                    `phone_normalized` varchar(40) DEFAULT NULL,
                    `email` varchar(255) DEFAULT NULL,
                    `child_name` varchar(255) DEFAULT NULL,
                    `child_age` decimal(5,2) DEFAULT NULL,
                    `city` varchar(255) DEFAULT NULL,
                    `neighborhood` varchar(255) DEFAULT NULL,
                    `status` varchar(80) DEFAULT 'NOVO',
                    `stage` varchar(80) DEFAULT 'captado',
                    `assigned_to` int(11) DEFAULT NULL,
                    `notes` text DEFAULT NULL,
                    `facebook_created_time` datetime DEFAULT NULL,
                    `imported_at` datetime DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    `raw_payload` longtext DEFAULT NULL,
                    `deleted` tinyint(1) DEFAULT 0,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `idx_facebook_lead_id_unique` (`facebook_lead_id`),
                    KEY `idx_facebook_lead_id` (`facebook_lead_id`),
                    KEY `idx_phone_normalized` (`phone_normalized`),
                    KEY `idx_facebook_form_id` (`facebook_form_id`),
                    KEY `idx_facebook_created_time` (`facebook_created_time`),
                    KEY `idx_status` (`status`),
                    KEY `idx_stage` (`stage`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            }

            siamesa_leads_ensure_column($db, $leads_table, "deleted", "tinyint(1) DEFAULT 0");
            $db->query("ALTER TABLE `" . $leads_table . "` MODIFY `status` varchar(80) DEFAULT 'NOVO'");
            siamesa_leads_ensure_index($db, $leads_table, "idx_phone_normalized", "KEY `idx_phone_normalized` (`phone_normalized`)");
            siamesa_leads_ensure_index($db, $leads_table, "idx_facebook_form_id", "KEY `idx_facebook_form_id` (`facebook_form_id`)");
            siamesa_leads_ensure_index($db, $leads_table, "idx_facebook_created_time", "KEY `idx_facebook_created_time` (`facebook_created_time`)");
            siamesa_leads_ensure_index($db, $leads_table, "idx_status", "KEY `idx_status` (`status`)");
            siamesa_leads_ensure_index($db, $leads_table, "idx_stage", "KEY `idx_stage` (`stage`)");

            $events_table = $dbprefix . "siamesa_facebook_lead_events";
            if (!$db->tableExists($events_table)) {
                $db->query("CREATE TABLE IF NOT EXISTS `" . $events_table . "` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `lead_id` int(11) NOT NULL,
                    `event_type` varchar(120) NOT NULL,
                    `description` text DEFAULT NULL,
                    `payload` longtext DEFAULT NULL,
                    `created_by` int(11) DEFAULT NULL,
                    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_lead_id` (`lead_id`),
                    KEY `idx_event_type` (`event_type`),
                    KEY `idx_created_at` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            }

            $sync_table = $dbprefix . "siamesa_facebook_sync_runs";
            if (!$db->tableExists($sync_table)) {
                $db->query("CREATE TABLE IF NOT EXISTS `" . $sync_table . "` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `started_at` datetime DEFAULT NULL,
                    `finished_at` datetime DEFAULT NULL,
                    `status` varchar(40) DEFAULT 'running',
                    `processed` int(11) DEFAULT 0,
                    `created` int(11) DEFAULT 0,
                    `updated` int(11) DEFAULT 0,
                    `duplicate_updates` int(11) DEFAULT 0,
                    `errors` int(11) DEFAULT 0,
                    `message` text DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_started_at` (`started_at`),
                    KEY `idx_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            }
        } catch (\Throwable $e) {
            log_message("error", "Erro ao instalar plugin Leads SIAMESA: " . $e->getMessage());
        }
    }
}
