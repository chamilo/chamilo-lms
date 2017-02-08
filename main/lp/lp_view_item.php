<?php
/* For licensing terms, see /license.txt */

/**
 * This is a learning path creation and player tool in Chamilo - previously learnpath_handler.php
 *
 * @author Patrick Cool
 * @author Denes Nagy
 * @author Roan Embrechts, refactoring and code cleaning
 * @author Yannick Warnier <ywarnier@beeznest.org> - cleaning and update for new SCORM tool
 * @package chamilo.learnpath
 */

// Prevents FF 3.6 + Adobe Reader 9 bug see BT#794 when calling a pdf file in a LP

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script();

if (isset($_GET['lp_item_id'])) {
    // Get parameter only came from lp_view.php.
    $lp_item_id  = intval($_GET['lp_item_id']);
    if (isset($_SESSION['lpobject'])) {
        $oLP = unserialize($_SESSION['lpobject']);
    }
    if (is_object($oLP)) {
        $src = $oLP->get_link('http', $lp_item_id);
    }

    $url_info = parse_url($src);
    $real_url_info = parse_url(api_get_path(WEB_PATH));

    // The host must be the same.
    if ($url_info['host'] == $real_url_info['host']) {
        $url = Security::remove_XSS($src);
        header("Location: ".$url);
        exit;
    } else {
        header("Location: blank.php?error=document_not_found");
        exit;
    }
}

$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : 'fullpage';

$_SESSION['whereami'] = 'lp/build';
if (isset($_SESSION['oLP']) && isset($_GET['id'])) {
    $_SESSION['oLP'] -> current = intval($_GET['id']);
}
$this_section = SECTION_COURSES;
/* Header and action code */
/* Constants and variables */
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

$tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
$tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
$tbl_lp_view = Database::get_course_table(TABLE_LP_VIEW);

$isStudentView  = (empty($_REQUEST['isStudentView']) ? 0 : (int) $_REQUEST['isStudentView']);
$learnpath_id   = (int) $_REQUEST['lp_id'];

if ((!$is_allowed_to_edit) || $isStudentView) {
    error_log('New LP - User not authorized in lp_view_item.php');
    header('Location: '.api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?action=view&lp_id='.$learnpath_id.'&'.api_get_cidreq());
    exit;
}
// From here on, we are admin because of the previous condition, so don't check anymore.

$course_id = api_get_course_int_id();
$sql = "SELECT * FROM $tbl_lp 
        WHERE c_id = $course_id AND id = $learnpath_id";
$result=Database::query($sql);
$therow = Database::fetch_array($result);

/* SHOWING THE ADMIN TOOLS	*/
if (api_is_in_gradebook()) {
    $interbreadcrumb[] = array (
        'url' => api_get_path(WEB_CODE_PATH).'gradebook/index.php?'.api_get_cidreq(),
        'name' => get_lang('ToolGradebook')
    );
}

$interbreadcrumb[] = array(
    'url' => api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('LearningPaths')
);
$interbreadcrumb[] = array(
    'url' => api_get_self()."?action=build&lp_id=$learnpath_id&".api_get_cidreq(),
    'name' => $therow['name']
);
$interbreadcrumb[] = array(
    'url' => api_get_self()."?action=add_item&type=step&lp_id=$learnpath_id&".api_get_cidreq(),
    'name' => get_lang('NewStep')
);

// Theme calls
$show_learn_path = true;
if (isset($_SESSION['oLP']) && is_object($_SESSION['oLP'])) {
	$lp_theme_css = $_SESSION['oLP']->get_theme();
}

if ($mode == 'fullpage') {
    Display::display_header(get_lang('Item'), 'Path');
}

$suredel = trim(get_lang('AreYouSureToDeleteJS'));
?>
<script>
function stripslashes(str) {
    str=str.replace(/\\'/g,'\'');
    str=str.replace(/\\"/g,'"');
    str=str.replace(/\\\\/g,'\\');
    str=str.replace(/\\0/g,'\0');
    return str;
}
function confirmation(name) {
    name=stripslashes(name);
    if (confirm("<?php echo $suredel; ?> " + name + " ?")) {
        return true;
    } else {
        return false;
    }
}
</script>
<?php

$id = isset($new_item_id) ? $new_item_id : $_GET['id'];
if (is_object($_SESSION['oLP'])) {
    switch ($mode) {
        case 'fullpage':
            echo $_SESSION['oLP']->build_action_menu();
            echo '<div class="row">';
            echo '<div class="col-md-3">';
            echo $_SESSION['oLP']->return_new_tree();
            echo '</div>';
            echo '<div class="col-md-9">';
            echo $_SESSION['oLP']->display_item($id);
            echo '</div>';
            echo '</div>';
            Display::display_footer();
            break;
        case 'preview_document':
            echo $_SESSION['oLP']->display_item($id, null, false);
            break;
    }
}
