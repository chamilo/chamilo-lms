<?php
/**
 * @package chamilo.plugin.vchamilo
 */

require_once api_get_path(LIBRARY_PATH) . 'plugin.class.php';
require_once dirname(__FILE__).'/lib/vchamilo_plugin.class.php';

api_protect_admin_script();

global $VCHAMILO;

$plugininstance = VChamiloPlugin::create();

// See also the share_user_info plugin

$_template['show_message'] = true;
$_template['title'] = $plugininstance->get_lang('hostlist');

$tablename = Database::get_main_table('vchamilo');
$sql = "
    SELECT 
        sitename,
        root_web
    FROM
        $tablename
    WHERE
        visible = 1
";

if ($VCHAMILO == '%'){
    $result = Database::query($sql);
    $_template['hosts'] = array();
    if ($result){
        while($vchamilo = Database::fetch_assoc($result)){
            $_template['hosts'][] = $vchamilo;
        }
    }
} else {
}
