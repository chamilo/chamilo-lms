<?php
/**
 * @package chamilo.plugin.vchamilo
 */
api_protect_admin_script();

global $virtualChamilo;

$plugin = VChamiloPlugin::create();

// See also the share_user_info plugin

$_template['show_message'] = true;
$_template['title'] = $plugin->get_lang('hostlist');

$table = Database::get_main_table('vchamilo');
$sql = "SELECT sitename, root_web FROM $table WHERE visible = 1";

if ($virtualChamilo == '%') {
    $result = Database::query($sql);
    $_template['hosts'] = [];
    if ($result) {
        while ($vchamilo = Database::fetch_assoc($result)) {
            $_template['hosts'][] = $vchamilo;
        }
    }
}
