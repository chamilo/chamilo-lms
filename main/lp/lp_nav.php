<?php
/* For licensing terms, see /license.txt */

/**
 * Script opened in an iframe and containing the
 * learning path's navigation and progress bar.
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;
require_once __DIR__.'/../inc/global.inc.php';

$htmlHeadXtra[] = '<script>
    var chamilo_xajax_handler = window.parent.oxajax;
</script>';

$lpItemId = isset($_REQUEST['lp_item']) ? (int) $_REQUEST['lp_item'] : 0;
$lpId = isset($_REQUEST['lp_id']) ? (int) $_REQUEST['lp_id'] : 0;

if (!$lpItemId) {
    echo '';
    exit;
}

$progress_bar = '';
$navigation_bar = '';
$autostart = 'true';

$myLP = learnpath::getLpFromSession(api_get_course_id(), $lpId, api_get_user_id());

if ($myLP) {
    $lp_theme_css = $myLP->get_theme();
    $my_style = api_get_visual_theme();

    // Setting up the CSS theme if exists
    $myCourseLpTheme = null;
    if ('true' === api_get_setting('allow_course_theme')) {
        $myCourseLpTheme = api_get_course_setting('allow_learning_path_theme');
    }

    if (!empty($lp_theme_css) && !empty($myCourseLpTheme) && -1 != $myCourseLpTheme && 1 == $myCourseLpTheme) {
        global $lp_theme_css;
    } else {
        $lp_theme_css = $my_style;
    }
    $progress_bar = $myLP->getProgressBar();
    $navigation_bar = $myLP->get_navigation_bar();
    $mediaplayer = $myLP->get_mediaplayer($lpItemId, $autostart);

    if ($mediaplayer) {
        echo $mediaplayer;
        echo "<script>
            $(function() {
                jQuery('video:not(.skip), audio:not(.skip)').mediaelementplayer();
            });
        </script>";
    }
}
session_write_close();
