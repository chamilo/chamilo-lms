<?php
/* For licensing terms, see /license.txt */

/**
 * Script opened in an iframe and containing the learning path's table of contents
 * @package chamilo.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 * @license	GNU/GPL
 */

// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;

require_once 'back_compat.inc.php';
require_once 'learnpath.class.php';
require_once 'scorm.class.php';
require_once 'aicc.class.php';

if (isset($_SESSION['lpobject'])) {
    $oLP = unserialize($_SESSION['lpobject']);
    if (is_object($oLP)) {
        $_SESSION['oLP'] = $oLP;
    } else {
        die('Could not instanciate lp object.');
    }
}

$lp_theme_css = $_SESSION['oLP']->get_theme();
$scorm_css_header = true;
require_once '../inc/reduced_header.inc.php';

echo '<body dir="'.api_get_text_direction().'">';

        echo '<div id="audiorecorder">	';
        $audio_recorder_studentview = 'true';
        $audio_recorder_item_id = $_SESSION['oLP']->current;
        if (api_get_setting('service_visio', 'active') == 'true') {
            include 'audiorecorder.inc.php';
        }
        echo '</div>';
        // end of audiorecorder include

echo '</body></html>';
