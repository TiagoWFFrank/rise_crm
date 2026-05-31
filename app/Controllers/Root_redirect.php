<?php

namespace App\Controllers;

class Root_redirect extends App_Controller {

    public function index() {
        app_redirect("siamesa_gerencial_plugin");
    }
}
