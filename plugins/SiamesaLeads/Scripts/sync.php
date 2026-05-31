#!/usr/bin/env php
<?php

declare(strict_types=1);

function cli_args(array $argv): array
{
    $args = [];
    for ($i = 1; $i < count($argv); $i++) {
        $item = $argv[$i];
        if (strpos($item, "--") !== 0) {
            continue;
        }
        $key = substr($item, 2);
        $next = $argv[$i + 1] ?? null;
        if ($next === null || strpos($next, "--") === 0) {
            $args[$key] = true;
        } else {
            $args[$key] = $next;
            $i++;
        }
    }
    return $args;
}

function read_env_file(string $path): array
{
    if (!is_file($path)) {
        return [];
    }
    $env = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === "" || $line[0] === "#" || strpos($line, "=") === false) {
            continue;
        }
        [$key, $value] = explode("=", $line, 2);
        $value = trim($value);
        $value = trim($value, "\"'");
        $env[trim($key)] = $value;
    }
    return $env;
}

function env_value(array $primary, array $fallback, string $key, string $default = ""): string
{
    $value = getenv($key);
    if ($value !== false && $value !== "") {
        return (string) $value;
    }
    if (array_key_exists($key, $primary) && $primary[$key] !== "") {
        return (string) $primary[$key];
    }
    if (array_key_exists($key, $fallback) && $fallback[$key] !== "") {
        return (string) $fallback[$key];
    }
    return $default;
}

function log_line(string $level, string $event, array $data = []): void
{
    echo json_encode(["timestamp" => gmdate("c"), "level" => $level, "event" => $event] + $data, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

function normalize_phone(?string $value): string
{
    $digits = preg_replace("/\D+/", "", (string) $value);
    if ($digits && strlen($digits) <= 11 && strpos($digits, "55") !== 0) {
        $digits = "55" . $digits;
    }
    return $digits ?: "";
}

function first_field(array $fields, array $names): string
{
    foreach ($names as $name) {
        foreach ($fields as $key => $value) {
            if (strtolower($key) === strtolower($name)) {
                return (string) $value;
            }
        }
    }
    return "";
}

function first_non_empty(array $values, string $default = ""): string
{
    foreach ($values as $value) {
        $value = trim((string) $value);
        if ($value !== "") {
            return $value;
        }
    }
    return $default;
}

function strip_integration_prefix(?string $value): string
{
    return preg_replace("/^[a-z]+:/i", "", trim((string) $value));
}

function lead_status_for_created_time(?string $createdTime): string
{
    if (!$createdTime) {
        return "NOVO";
    }

    return substr($createdTime, 0, 10) < "2026-05-20" ? "USADO" : "NOVO";
}

function graph_request(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_CONNECTTIMEOUT => 15
    ]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($body === false || $body === "") {
        throw new RuntimeException("Graph API sem resposta: " . $error);
    }

    $json = json_decode($body, true);
    if (!is_array($json)) {
        throw new RuntimeException("Graph API retornou JSON inválido.");
    }

    if ($status >= 400 || isset($json["error"])) {
        $message = $json["error"]["message"] ?? ("HTTP " . $status);
        throw new RuntimeException("Graph API: " . $message);
    }

    return $json;
}

function create_tables(mysqli $db, string $prefix): void
{
    $db->query("CREATE TABLE IF NOT EXISTS `{$prefix}siamesa_facebook_leads` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->query("ALTER TABLE `{$prefix}siamesa_facebook_leads` MODIFY `status` varchar(80) DEFAULT 'NOVO'");

    $db->query("CREATE TABLE IF NOT EXISTS `{$prefix}siamesa_facebook_lead_events` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->query("CREATE TABLE IF NOT EXISTS `{$prefix}siamesa_facebook_sync_runs` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function prepared_execute(mysqli $db, string $sql, string $types, array $values): mysqli_stmt
{
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException($db->error);
    }
    $stmt->bind_param($types, ...$values);
    if (!$stmt->execute()) {
        throw new RuntimeException($stmt->error);
    }
    return $stmt;
}

function field_map(array $lead): array
{
    $fields = [];
    foreach (($lead["field_data"] ?? []) as $field) {
        $name = (string) ($field["name"] ?? "");
        $values = $field["values"] ?? [];
        $fields[$name] = is_array($values) ? implode(", ", $values) : (string) $values;
    }
    return $fields;
}

function save_lead(mysqli $db, string $prefix, array $lead, string $formId, string $formName, bool $skipExisting = false): string
{
    $fields = field_map($lead);
    $facebookLeadId = strip_integration_prefix((string) ($lead["id"] ?? ""));
    if ($facebookLeadId === "") {
        throw new RuntimeException("Lead sem ID Facebook.");
    }

    $facebookPageId = first_non_empty([$lead["page_id"] ?? "", "440382895834530"]);
    $facebookFormId = strip_integration_prefix(first_non_empty([$lead["form_id"] ?? "", $formId]));
    $facebookAdId = strip_integration_prefix((string) ($lead["ad_id"] ?? ""));
    $facebookCampaignId = strip_integration_prefix((string) ($lead["campaign_id"] ?? ""));
    $facebookAdsetId = strip_integration_prefix((string) ($lead["adset_id"] ?? ""));
    $responsibleName = first_field($fields, ["full_name", "nome_completo", "nome_do_responsavel", "nome", "responsavel_nome"]);
    $phoneOriginal = first_field($fields, ["phone_number", "telefone", "telefone_whatsapp", "whatsapp", "celular"]);
    $phoneNormalized = normalize_phone($phoneOriginal);
    $email = first_field($fields, ["email", "e-mail"]);
    $childName = first_field($fields, ["nome_da_crianca", "nome_crianca", "aluno", "nome_do_aluno"]);
    $childAgeRaw = first_field($fields, ["idade_da_crianca", "idade", "idade_aluno"]);
    $childAge = is_numeric(str_replace(",", ".", $childAgeRaw)) ? (float) str_replace(",", ".", $childAgeRaw) : null;
    $city = first_field($fields, ["cidade", "city"]);
    $neighborhood = first_field($fields, ["bairro", "neighborhood"]);
    $createdTime = isset($lead["created_time"]) ? date("Y-m-d H:i:s", strtotime((string) $lead["created_time"])) : null;
    $leadStatus = lead_status_for_created_time($createdTime);
    $rawPayload = json_encode($lead, JSON_UNESCAPED_UNICODE);

    $table = $prefix . "siamesa_facebook_leads";
    $existing = prepared_execute($db, "SELECT id FROM `$table` WHERE facebook_lead_id=? LIMIT 1", "s", [$facebookLeadId])->get_result()->fetch_assoc();
    if ($existing && $skipExisting) {
        return "lead_skipped";
    }

    $action = $existing ? "lead_updated" : "lead_imported";

    $sql = "INSERT INTO `$table` (
            facebook_lead_id, facebook_page_id, facebook_form_id, facebook_ad_id,
            facebook_campaign_id, facebook_adset_id, form_name, campaign_name, ad_name,
            responsible_name, phone_original, phone_normalized, email, child_name, child_age,
            city, neighborhood, status, stage, facebook_created_time, raw_payload, imported_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'captado', ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            facebook_page_id=COALESCE(NULLIF(VALUES(facebook_page_id), ''), facebook_page_id),
            facebook_form_id=COALESCE(NULLIF(VALUES(facebook_form_id), ''), facebook_form_id),
            facebook_ad_id=COALESCE(NULLIF(VALUES(facebook_ad_id), ''), facebook_ad_id),
            facebook_campaign_id=COALESCE(NULLIF(VALUES(facebook_campaign_id), ''), facebook_campaign_id),
            facebook_adset_id=COALESCE(NULLIF(VALUES(facebook_adset_id), ''), facebook_adset_id),
            form_name=COALESCE(NULLIF(VALUES(form_name), ''), form_name),
            campaign_name=COALESCE(NULLIF(VALUES(campaign_name), ''), campaign_name),
            ad_name=COALESCE(NULLIF(VALUES(ad_name), ''), ad_name),
            responsible_name=COALESCE(NULLIF(VALUES(responsible_name), ''), responsible_name),
            phone_original=COALESCE(NULLIF(VALUES(phone_original), ''), phone_original),
            phone_normalized=COALESCE(NULLIF(VALUES(phone_normalized), ''), phone_normalized),
            email=COALESCE(NULLIF(VALUES(email), ''), email),
            child_name=COALESCE(NULLIF(VALUES(child_name), ''), child_name),
            child_age=COALESCE(VALUES(child_age), child_age),
            city=COALESCE(NULLIF(VALUES(city), ''), city),
            neighborhood=COALESCE(NULLIF(VALUES(neighborhood), ''), neighborhood),
            facebook_created_time=COALESCE(VALUES(facebook_created_time), facebook_created_time),
            raw_payload=VALUES(raw_payload),
            updated_at=NOW(),
            deleted=0";

    prepared_execute($db, $sql, "ssssssssssssssdsssss", [
        $facebookLeadId,
        $facebookPageId,
        $facebookFormId,
        $facebookAdId,
        $facebookCampaignId,
        $facebookAdsetId,
        $formName,
        (string) ($lead["campaign_name"] ?? ""),
        (string) ($lead["ad_name"] ?? ""),
        $responsibleName,
        $phoneOriginal,
        $phoneNormalized,
        $email,
        $childName,
        $childAge,
        $city,
        $neighborhood,
        $leadStatus,
        $createdTime,
        $rawPayload
    ]);

    $leadId = $existing["id"] ?? prepared_execute($db, "SELECT id FROM `$table` WHERE facebook_lead_id=? LIMIT 1", "s", [$facebookLeadId])->get_result()->fetch_assoc()["id"];
    $eventsTable = $prefix . "siamesa_facebook_lead_events";
    prepared_execute($db, "INSERT INTO `$eventsTable` (lead_id, event_type, description, payload, created_at) VALUES (?, ?, ?, ?, NOW())", "isss", [
        (int) $leadId,
        $action,
        $action === "lead_imported" ? "Lead importado da Meta." : "Lead atualizado por sincronização da Meta.",
        $rawPayload
    ]);

    return $action;
}

$args = cli_args($argv);
$deployEnv = read_env_file("/opt/apps/rise/shared/.deploy.env");
$metaEnv = read_env_file("/opt/apps/siamesa-leads/.env");

$lockPath = "/tmp/siamesa-rise-facebook-leads-sync.lock";
$lock = fopen($lockPath, "c");
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    log_line("info", "sync_skipped_locked");
    exit(0);
}

$host = env_value($deployEnv, [], "MYSQL_HOST", "127.0.0.1");
$port = (int) env_value($deployEnv, [], "MYSQL_PORT", "3306");
$database = env_value($deployEnv, [], "MYSQL_DB", "rise_prod");
$user = env_value($deployEnv, [], "MYSQL_USER", "rise_app");
$password = env_value($deployEnv, [], "MYSQL_PASSWORD");
$prefix = env_value($deployEnv, [], "DB_PREFIX", "rise_");
$token = env_value($metaEnv, [], "META_PAGE_ACCESS_TOKEN");
$graphVersion = env_value($metaEnv, [], "META_GRAPH_VERSION", "v25.0");
$formId = (string) ($args["form-id"] ?? env_value($metaEnv, [], "META_FORM_IDS", "2135130517298155"));
$formId = explode(",", $formId)[0];
$formName = (string) ($args["form-name"] ?? "SIAMESA - SBC");
$limit = max(1, (int) ($args["limit"] ?? 100));
$maxPages = max(1, (int) ($args["max-pages"] ?? 100));

if ($token === "") {
    log_line("error", "sync_failed", ["message" => "META_PAGE_ACCESS_TOKEN não encontrado."]);
    exit(1);
}

$db = new mysqli($host, $user, $password, $database, $port);
if ($db->connect_errno) {
    log_line("error", "sync_failed", ["message" => "Falha ao conectar MySQL."]);
    exit(1);
}
$db->set_charset("utf8mb4");
create_tables($db, $prefix);

if (!empty($args["migrate-only"])) {
    log_line("info", "sync_migration_finished", ["message" => "Rise SIAMESA lead tables are ready."]);
    exit(0);
}

$syncTable = $prefix . "siamesa_facebook_sync_runs";
prepared_execute($db, "INSERT INTO `$syncTable` (started_at, status, message) VALUES (NOW(), 'running', ?)", "s", ["sync started"]);
$syncRunId = $db->insert_id;

$processed = 0;
$created = 0;
$updated = 0;
$duplicateUpdates = 0;
$errors = 0;
$message = "";

try {
    log_line("info", "sync_started", ["formId" => $formId, "limit" => $limit, "maxPages" => $maxPages]);

    if (!empty($args["csv"])) {
        $path = (string) $args["csv"];
        if (!is_file($path)) {
            throw new RuntimeException("CSV não encontrado: " . $path);
        }
        $handle = fopen($path, "r");
        $headers = fgetcsv($handle, 0, ",");
        if (!$headers || count($headers) < 2) {
            rewind($handle);
            $headers = fgetcsv($handle, 0, ";");
        }
        while (($row = fgetcsv($handle, 0, ",")) !== false) {
            if (count($row) !== count($headers)) {
                continue;
            }
            $data = array_combine($headers, $row);
            $lead = [
                "id" => $data["facebook_lead_id"] ?? $data["id"] ?? "",
                "created_time" => $data["facebook_created_time"] ?? $data["created_time"] ?? "",
                "form_id" => first_non_empty([$data["facebook_form_id"] ?? "", $data["form_id"] ?? "", $formId]),
                "page_id" => $data["facebook_page_id"] ?? "",
                "ad_id" => first_non_empty([$data["facebook_ad_id"] ?? "", $data["ad_id"] ?? ""]),
                "ad_name" => $data["ad_name"] ?? "",
                "adset_id" => first_non_empty([$data["facebook_adset_id"] ?? "", $data["adset_id"] ?? ""]),
                "campaign_id" => first_non_empty([$data["facebook_campaign_id"] ?? "", $data["campaign_id"] ?? ""]),
                "campaign_name" => $data["campaign_name"] ?? "",
                "field_data" => [
                    ["name" => "full_name", "values" => [$data["responsible_name"] ?? $data["nome"] ?? ""]],
                    ["name" => "phone_number", "values" => [$data["phone_original"] ?? $data["telefone"] ?? ""]],
                    ["name" => "email", "values" => [$data["email"] ?? ""]],
                    ["name" => "nome_da_crianca", "values" => [$data["child_name"] ?? $data["crianca"] ?? ""]],
                    ["name" => "idade_da_crianca", "values" => [$data["child_age"] ?? $data["idade"] ?? ""]],
                    ["name" => "cidade", "values" => [$data["city"] ?? ""]],
                    ["name" => "bairro", "values" => [$data["neighborhood"] ?? ""]]
                ],
                "csv_row" => $data
            ];
            $action = save_lead($db, $prefix, $lead, $formId, $formName, !empty($args["skip-existing"]));
            $processed++;
            if ($action === "lead_imported") {
                $created++;
            } else if ($action === "lead_updated") {
                $updated++;
                $duplicateUpdates++;
            } else {
                $duplicateUpdates++;
            }
        }
        fclose($handle);
    } else {
        $params = [
            "access_token" => $token,
            "limit" => $limit,
            "fields" => "id,created_time,field_data,ad_id,ad_name,adset_id,adset_name,campaign_id,campaign_name,form_id,platform"
        ];

        if (empty($args["all"])) {
            if (!empty($args["since-hours"])) {
                $params["since"] = time() - ((int) $args["since-hours"] * 3600);
            } else {
                $params["since"] = time() - ((int) ($args["since-days"] ?? 30) * 86400);
            }
        }

        $url = "https://graph.facebook.com/" . rawurlencode($graphVersion) . "/" . rawurlencode($formId) . "/leads?" . http_build_query($params);
        $page = 0;
        while ($url && $page < $maxPages) {
            $page++;
            $response = graph_request($url);
            foreach (($response["data"] ?? []) as $lead) {
                try {
                    $action = save_lead($db, $prefix, $lead, $formId, $formName, !empty($args["skip-existing"]));
                    $processed++;
                    if ($action === "lead_imported") {
                        $created++;
                    } else if ($action === "lead_updated") {
                        $updated++;
                        $duplicateUpdates++;
                    } else {
                        $duplicateUpdates++;
                    }
                } catch (Throwable $e) {
                    $errors++;
                    log_line("error", "sync_lead_failed", ["message" => $e->getMessage(), "facebookLeadId" => $lead["id"] ?? null]);
                }
            }
            $url = $response["paging"]["next"] ?? "";
        }
    }

    $message = "processed=$processed created=$created updated=$updated duplicate_updates=$duplicateUpdates errors=$errors";
    prepared_execute($db, "UPDATE `$syncTable` SET finished_at=NOW(), status=?, processed=?, created=?, updated=?, duplicate_updates=?, errors=?, message=? WHERE id=?", "siiiiisi", [
        $errors ? "partial" : "success",
        $processed,
        $created,
        $updated,
        $duplicateUpdates,
        $errors,
        $message,
        $syncRunId
    ]);
    log_line("info", "sync_finished", compact("processed", "created", "updated", "duplicateUpdates", "errors"));
    exit($errors ? 1 : 0);
} catch (Throwable $e) {
    $message = $e->getMessage();
    prepared_execute($db, "UPDATE `$syncTable` SET finished_at=NOW(), status='failed', processed=?, created=?, updated=?, duplicate_updates=?, errors=?, message=? WHERE id=?", "iiiiisi", [
        $processed,
        $created,
        $updated,
        $duplicateUpdates,
        $errors + 1,
        $message,
        $syncRunId
    ]);
    log_line("error", "sync_failed", ["message" => $message]);
    exit(1);
}
