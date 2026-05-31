#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/opt/apps/rise/current}"
LEADS_DIR="${LEADS_DIR:-/opt/apps/siamesa-leads}"
FORM_ID="${FORM_ID:-2135130517298155}"
FORM_NAME="${FORM_NAME:-SIAMESA - SBC}"
SINCE_HOURS="${SINCE_HOURS:-48}"
LOCK_FILE="${LOCK_FILE:-/tmp/siamesa-iara-to-rise-leads.lock}"
LOG_FILE="${LOG_FILE:-/opt/apps/rise/current/writable/logs/siamesa-leads-iara-sync.log}"
TMP_CSV="$(mktemp /tmp/siamesa_iara_to_rise_XXXXXX.csv)"

cleanup() {
  rm -f "$TMP_CSV"
}
trap cleanup EXIT

mkdir -p "$(dirname "$LOG_FILE")"
exec >> "$LOG_FILE" 2>&1

log_line() {
  printf '{"timestamp":"%s","level":"%s","event":"%s","message":"%s"}\n' "$(date -Is)" "$1" "$2" "$3"
}

exec 9>"$LOCK_FILE"
if ! flock -n 9; then
  log_line info iara_to_rise_sync_skipped "locked"
  exit 0
fi

log_line info iara_to_rise_sync_started "form_id=$FORM_ID since_hours=$SINCE_HOURS"

SINCE_HOURS="$SINCE_HOURS" FORM_ID="$FORM_ID" docker compose -f "$LEADS_DIR/docker-compose.yml" exec -T web node - > "$TMP_CSV" <<'NODE'
const { Client } = require("pg");
const client = new Client({
  host: process.env.DB_HOST || process.env.IARA_POSTGRES_HOST,
  port: Number(process.env.DB_PORT || process.env.IARA_POSTGRES_PORT || 5432),
  database: process.env.DB_NAME || process.env.IARA_POSTGRES_DB,
  user: process.env.DB_USER || process.env.IARA_POSTGRES_USER,
  password: process.env.DB_PASSWORD || process.env.IARA_POSTGRES_PASSWORD,
  ssl: false
});
const out = value => `"${String(value ?? "").replace(/"/g, '""')}"`;
(async () => {
  await client.connect();
  const sinceHours = Number(process.env.SINCE_HOURS || 48);
  const formId = process.env.FORM_ID || "2135130517298155";
  const result = await client.query(`
    SELECT facebook_lead_id, page_id AS facebook_page_id, form_id AS facebook_form_id, ad_id AS facebook_ad_id,
           created_time AS facebook_created_time, responsible_name, phone_original, phone_normalized,
           email, child_name, child_age, graph_payload, normalized_payload
    FROM iara_integration.facebook_lead_ads_leads
    WHERE form_id = $1
      AND COALESCE(last_received_at, created_at, first_received_at, created_time) >= now() - ($2::int * interval '1 hour')
    ORDER BY COALESCE(created_time, created_at) ASC
  `, [formId, sinceHours]);
  const headers = ["facebook_lead_id","facebook_page_id","facebook_form_id","facebook_ad_id","facebook_campaign_id","facebook_adset_id","form_name","campaign_name","ad_name","responsible_name","phone_original","phone_normalized","email","child_name","child_age","city","neighborhood","facebook_created_time"];
  console.log(headers.map(out).join(","));
  for (const row of result.rows) {
    const graph = row.graph_payload || {};
    const normalized = row.normalized_payload || {};
    const values = {
      facebook_lead_id: row.facebook_lead_id,
      facebook_page_id: row.facebook_page_id,
      facebook_form_id: row.facebook_form_id,
      facebook_ad_id: row.facebook_ad_id,
      facebook_campaign_id: graph.campaign_id || normalized.campaign_id || "",
      facebook_adset_id: graph.adset_id || normalized.adset_id || "",
      form_name: "SIAMESA - SBC",
      campaign_name: graph.campaign_name || normalized.campaign_name || "",
      ad_name: graph.ad_name || normalized.ad_name || "",
      responsible_name: row.responsible_name,
      phone_original: row.phone_original,
      phone_normalized: row.phone_normalized,
      email: row.email,
      child_name: row.child_name,
      child_age: row.child_age,
      city: normalized.city || "",
      neighborhood: normalized.neighborhood || "",
      facebook_created_time: row.facebook_created_time ? row.facebook_created_time.toISOString() : ""
    };
    console.log(headers.map(h => out(values[h])).join(","));
  }
  await client.end();
})().catch(async error => {
  console.error(error.message);
  try { await client.end(); } catch {}
  process.exit(1);
});
NODE

php "$APP_DIR/plugins/SiamesaLeads/Scripts/sync.php" \
  --csv "$TMP_CSV" \
  --form-id "$FORM_ID" \
  --form-name "$FORM_NAME" \
  --skip-existing

log_line info iara_to_rise_sync_finished "ok"
