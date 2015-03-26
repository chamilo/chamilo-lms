<?php
/* For licensing terms, see /license.txt */

/**
 * Script opened in an iframe and containing the
 * learning path's navigation and progress bar
 * @package chamilo.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;
require_once '../inc/global.inc.php';

$htmlHeadXtra[] = '<script>
    var chamilo_xajax_handler = window.parent.oxajax;
</script>';

$progress_bar = '';
$navigation_bar = '';
$display_mode = '';
$autostart = 'true';

if (isset($_SESSION['lpobject'])) {
    $oLP = unserialize($_SESSION['lpobject']);
    if (is_object($oLP)) {
        $_SESSION['oLP'] = $oLP;
    } else {
        die('Could not instanciate lp object');
    }
    $display_mode = $_SESSION['oLP']->mode;
    $scorm_css_header = true;
    $lp_theme_css = $_SESSION['oLP']->get_theme();

    $my_style = api_get_visual_theme();

    // Setting up the CSS theme if exists
    $mycourselptheme = null;
    if (api_get_setting('allow_course_theme') == 'true') {
        $mycourselptheme = api_get_course_setting('allow_learning_path_theme');
    }

    if (!empty($lp_theme_css) && !empty($mycourselptheme) && $mycourselptheme != -1 && $mycourselptheme == 1) {
        global $lp_theme_css;
    } else {
        $lp_theme_css = $my_style;
    }

    $progress_bar 	= $_SESSION['oLP']->getProgressBar();
    $navigation_bar = $_SESSION['oLP']->get_navigation_bar();
    $mediaplayer 	= $_SESSION['oLP']->get_mediaplayer($autostart);
}
session_write_close();
?>
<script type="text/javascript">
    $(document).ready(function() {
        jQuery('video:not(.skip), audio:not(.skip)').mediaelementplayer({
            success: function(player, node) {
            }
        });
    });
</script>
<span>
    <?php echo (!empty($mediaplayer)) ? $mediaplayer : '&nbsp;' ?>
</span>
