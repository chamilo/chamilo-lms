<?php
/* For licensing terms, see /license.txt */
/**
 * Script that displays the header frame for lp_view.php
 * @package chamilo.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Code
 */
// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;

// name of the language file that needs to be included
$language_file[] = 'scormdocument';

require_once 'back_compat.inc.php';
require_once 'learnpath.class.php';
require_once 'scorm.class.php';
require_once 'aicc.class.php';

if (isset($_SESSION['lpobject'])) {
    $temp = $_SESSION['lpobject'];
    $_SESSION['oLP'] = unserialize($temp);
}

$path_name = $_SESSION['oLP']->get_name();
$path_id = $_SESSION['oLP']->get_id();
// Use the flag set in lp_view.php to check if this script has been loaded
// as a frame of lp_view.php. Otherwise, redirect to lp_controller.
if (!$_SESSION['loaded_lp_view']) {
    header('location: lp_controller.php?'.api_get_cidreq().'&action=view&item_id='.$path_id);
}
// Unset the flag as it has been used already.
$_SESSION['loaded_lp_view'] = false;

// Check if the learnpaths list should be accessible to the user.
$show_link = true;
if (!api_is_allowed_to_edit()) { // If the user has no edit permission (simple user).
    $course_tool_table = Database::get_course_table(TABLE_TOOL_LIST);
    $result = Database::query("SELECT * FROM $course_tool_table WHERE name='learnpath'");
    if (Database::num_rows($result) > 0) {
        $row = Database::fetch_array($result);
        if ($row['visibility'] == '0') { // If the tool is *not* visible.
            $show_link = false;
        }
    } else {
        $show_link = false;
    }
}

if (isset($_SESSION['gradebook'])){
    $gradebook = $_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array (
            'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
            'name' => get_lang('ToolGradebook')
        );
}
if ($show_link) {
    $interbreadcrumb[] = array('url' => './lp_controller.php?action=list', 'name' => get_lang(ucfirst(TOOL_LEARNPATH)));
}
// Else we don't display get_lang(ucfirst(TOOL_LEARNPATH)) in the breadcrumb since the learner accessed it directly from the course homepage.
$interbreadcrumb[] = array('url' => './lp_controller.php?action=view&lp_id='.$path_id, 'name' => $path_name);

$noPHP_SELF = true;
$lp_theme_css = $_SESSION['oLP']->get_theme();
include '../inc/reduced_header.inc.php';

echo '<div style="font-size:14px;padding-left: 17px;">';
echo '<table ><tr><td>';
echo '<a href="./lp_controller.php?action=return_to_course_homepage" target="_self" onclick="javascript: window.parent.API.save_asset();">';
echo '<img src="../img/lp_arrow.gif">';
echo '<a>';
echo '</td><td>';
echo '<a class="link" href="./lp_controller.php?action=return_to_course_homepage" target="_self" onclick="javascript: window.parent.API.save_asset();">'.get_lang('CourseHomepageLink').'</a>';
echo '</td></tr><table>';
echo '</div>';
?>
</body>
</html>
