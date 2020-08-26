<?php
// Check extra_field authors to lp and company to user
require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/../../main/inc/lib/extra_field.lib.php';
// public $isAdminPlugin = true;
// $_template['show_message'] = false;

if (api_is_anonymous()) {

    echo "Hola mundo";
}
