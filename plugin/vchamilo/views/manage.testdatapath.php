<?php
/* For licensing terms, see /license.txt */

/**
 * Tests presence of course directories.
 *
 * @package vchamilo
 * @category plugin
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

// Loading configuration.
require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_admin_script();

$plugin = VChamiloPlugin::create();

// Retrieve parameters for database connection test.
$dataroot = $_REQUEST['dataroot'];

$absalternatecourse = Virtual::getConfig('vchamilo', 'course_real_root');
if (!empty($absalternatecourse)) {
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
        echo '<div class="error">'.$plugin->get_lang('datapathnotavailable').'</div>';
    } else {
        echo '<div class="success">'.$plugin->get_lang('datapathavailable').'</div>';
    }
    echo stripslashes($coursedir);
} else {
    if (@mkdir($coursedir, 02777, true)) {
        echo '<div class="success">'.$plugin->get_lang('datapathcreated').'</div>';
    } else {
        echo '<div class="error">'.$plugin->get_lang('couldnotcreatedataroot').'</div>';
    }
    echo stripslashes($coursedir);
}

echo "</p>";

$closestr = $plugin->get_lang('closewindow');
echo "<center>";
echo "<input class='btn' type=\"button\" name=\"close\" value=\"$closestr\" onclick=\"self.close();\" />";
echo "</center>";
