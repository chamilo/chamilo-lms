<?php
/**
 * Tests presence of course directories.
 *
 * @package vchamilo
 * @category plugin
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

// Loading configuration.
require_once '../../../main/inc/global.inc.php';
require_once api_get_path(SYS_PLUGIN_PATH).'vchamilo/lib/vchamilo_plugin.class.php';
require_once api_get_path(SYS_PLUGIN_PATH).'vchamilo/lib.php';

api_protect_admin_script();

$plugininstance = VChamiloPlugin::create();

// Retrieve parameters for database connection test.
$dataroot = $_REQUEST['dataroot'];

$absalternatecourse = vchamilo_get_config('vchamilo', 'course_real_root');
if (!empty($absalternatecourse)){
    // this is the relocated case
    $coursedir = str_replace('//', '/', $absalternatecourse.'/'.$dataroot);
} else {
    // this is the standard local case
    $coursedir = api_get_path(SYS_PATH).$dataroot;
}

if (is_dir($coursedir)) {
    $DIR = opendir($coursedir);
    $cpt = 0;
    $hasfiles = false;
    while (($file = readdir($DIR)) && !$hasfiles) {
        if (!preg_match("/^\\./", $file)) {
            $hasfiles = true;
        }
    }
    closedir($DIR);

    if ($hasfiles) {
        echo '<div class="error">'.$plugininstance->get_lang('datapathnotavailable').'</div>';
    } else {
        echo '<div class="success">'.$plugininstance->get_lang('datapathavailable').'</div>';
    }
    echo stripslashes($coursedir);
} else {
    if (@mkdir($coursedir, 02777, true)) {
        echo '<div class="success">'.$plugininstance->get_lang('datapathcreated').'</div>';
    } else {
        echo '<div class="error">'.$plugininstance->get_lang('couldnotcreatedataroot').'</div>';
    }
    echo stripslashes($coursedir);
}

echo "</p>";

$closestr = $plugininstance->get_lang('closewindow');
echo "<center>";
echo "<input class='btn' type=\"button\" name=\"close\" value=\"$closestr\" onclick=\"self.close();\" />";
echo "</center>";
