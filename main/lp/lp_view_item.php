<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This is a learning path creation and player tool in Chamilo - previously learnpath_handler.php.
 *
 * @author Patrick Cool
 * @author Denes Nagy
 * @author Roan Embrechts, refactoring and code cleaning
 * @author Yannick Warnier <ywarnier@beeznest.org> - cleaning and update for new SCORM tool
 */

// Prevents FF 3.6 + Adobe Reader 9 bug see BT#794 when calling a pdf file in a LP
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script();

/** @var learnpath $lp */
$lp = Session::read('oLP');

if (isset($_GET['lp_item_id'])) {
    // Get parameter only came from lp_view.php.
    $lp_item_id = (int) $_GET['lp_item_id'];
    if (is_object($lp)) {
        $src = $lp->get_link('http', $lp_item_id);
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

if (empty($lp)) {
    api_not_allowed();
}

$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : 'fullpage';
if (isset($_SESSION['oLP']) && isset($_GET['id'])) {
    $_SESSION['oLP']->current = (int) $_GET['id'];
}
$this_section = SECTION_COURSES;
/* Header and action code */
/* Constants and variables */
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
$isStudentView = (empty($_REQUEST['isStudentView']) ? 0 : (int) $_REQUEST['isStudentView']);
$learnpath_id = (int) $_REQUEST['lp_id'];

if (!$is_allowed_to_edit || $isStudentView) {
    header('Location: '.api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?action=view&lp_id='.$learnpath_id.'&'.api_get_cidreq());
    exit;
}
// From here on, we are admin because of the previous condition, so don't check anymore.
$course_id = api_get_course_int_id();
/* SHOWING THE ADMIN TOOLS	*/
if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'gradebook/index.php?'.api_get_cidreq(),
        'name' => get_lang('ToolGradebook'),
    ];
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('LearningPaths'),
];
$interbreadcrumb[] = [
    'url' => api_get_self()."?action=build&lp_id=$learnpath_id&".api_get_cidreq(),
    'name' => $lp->getNameNoTags(),
];
$interbreadcrumb[] = [
    'url' => api_get_self()."?action=add_item&type=step&lp_id=$learnpath_id&".api_get_cidreq(),
    'name' => get_lang('NewStep'),
];

// Theme calls
$show_learn_path = true;
$lp_theme_css = $lp->get_theme();

if ('fullpage' === $mode) {
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
if (is_object($lp)) {
    switch ($mode) {
        case 'fullpage':
            echo $lp->build_action_menu();
            echo '<div class="row">';
            echo '<div class="col-md-3">';
            echo $lp->return_new_tree();
            echo '</div>';
            echo '<div class="col-md-9">';
            echo $lp->display_item($id);
            echo '</div>';
            echo '</div>';
            Display::display_footer();
            break;
        case 'preview_document':
            echo $lp->display_item($id, null, false);
            break;
    }
}
