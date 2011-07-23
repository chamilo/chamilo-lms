<?php
/* For licensing terms, see /license.txt */

/**
 * Script opened in an iframe and containing the learning path's navigation and progress bar
 * @package chamilo.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 * @license	GNU/GPL
 */
/**
 * Code
 */
// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;

// Language files that needs to be included.
$language_file[] = 'scormdocument';
$language_file[] = 'scorm';
$language_file[] = 'learnpath';

require_once 'back_compat.inc.php';
require_once 'learnpath.class.php';
require_once 'scorm.class.php';
require_once 'aicc.class.php';

//error_log('New LP - Loaded lp_nav: '.$_SERVER['REQUEST_URI'], 0);
$htmlHeadXtra[] = '<script language="JavaScript" type="text/javascript">
      var dokeos_xajax_handler = window.parent.oxajax;
    </script>';

$progress_bar = '';
$navigation_bar = '';
$display_mode = '';
$autostart = 'true';

if (isset($_SESSION['lpobject'])) {
    //if($debug>0) //error_log('New LP - in lp_nav.php - SESSION[lpobject] is defined',0);
    $oLP = unserialize($_SESSION['lpobject']);
    if (is_object($oLP)) {
        $_SESSION['oLP'] = $oLP;
    } else {
        //error_log('New LP - in lp_nav.php - SESSION[lpobject] is not object - dying',0);
        die('Could not instanciate lp object');
    }
    $display_mode = $_SESSION['oLP']->mode;
    $scorm_css_header = true;
    $lp_theme_css = $_SESSION['oLP']->get_theme();
    //Setting up the CSS theme if exists
    include_once '../inc/reduced_header.inc.php';

    if (!empty($lp_theme_css) && !empty($mycourselptheme) && $mycourselptheme != -1 && $mycourselptheme == 1) {
        global $lp_theme_css;
    } else {
        $lp_theme_css = $my_style;
    }
    //$progress_bar = $_SESSION['oLP']->get_progress_bar();
    $progress_bar = $_SESSION['oLP']->get_progress_bar('', -1, '', true);
    $navigation_bar = $_SESSION['oLP']->get_navigation_bar();
    $mediaplayer = $_SESSION['oLP']->get_mediaplayer($autostart);
}
session_write_close();
?>
<span><?php echo (!empty($mediaplayer)) ? $mediaplayer : '&nbsp;' ?></span>
