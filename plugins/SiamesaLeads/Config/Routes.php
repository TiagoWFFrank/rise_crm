<?php

if (defined("SIAMESA_LEADS_ROUTES_LOADED")) {
    return;
}

define("SIAMESA_LEADS_ROUTES_LOADED", true);

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group("siamesa_leads", ["namespace" => "SiamesaLeads\Controllers"], function ($routes) {
    $routes->get("/", "SiamesaLeads::index");
    $routes->get("index", "SiamesaLeads::index");
    $routes->post("list_data", "SiamesaLeads::list_data");
    $routes->get("view/(:num)", "SiamesaLeads::view/$1");
    $routes->post("lead_modal_form", "SiamesaLeads::lead_modal_form");
    $routes->post("save_status", "SiamesaLeads::save_status");
    $routes->post("sync", "SiamesaLeads::sync");
    $routes->get("export_csv", "SiamesaLeads::export_csv");
});
