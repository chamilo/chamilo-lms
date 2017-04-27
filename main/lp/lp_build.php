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

$_SESSION['whereami'] = 'lp/build';
$this_section = SECTION_COURSES;

api_protect_course_script();

/* Constants and variables */

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

$tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
$tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
$tbl_lp_view = Database::get_course_table(TABLE_LP_VIEW);

$isStudentView = (int)$_REQUEST['isStudentView'];
$learnpath_id = (int)$_REQUEST['lp_id'];
$submit = $_POST['submit_button'];

/* MAIN CODE */
if ((!$is_allowed_to_edit) || ($isStudentView)) {
    error_log('New LP - User not authorized in lp_build.php');
    header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
}
// From here on, we are admin because of the previous condition, so don't check anymore.

/* The learnpath has been just created, go get the last id. */
$is_new = false;
$course_id = api_get_course_int_id();

if ($learnpath_id == 0) {
    $is_new = true;
    $sql = "SELECT id FROM $tbl_lp
            WHERE c_id = $course_id 
            ORDER BY id DESC LIMIT 0, 1";
    $result = Database::query($sql);
    $row = Database::fetch_array($result);
    $learnpath_id = $row['id'];
}

$sql_query = "SELECT * FROM $tbl_lp WHERE c_id = $course_id AND id = $learnpath_id";

$result = Database::query($sql_query);
$therow = Database::fetch_array($result);

/* SHOWING THE ADMIN TOOLS */

if (!empty($_GET['gradebook']) && $_GET['gradebook'] == 'view') {
    $_SESSION['gradebook'] = Security::remove_XSS($_GET['gradebook']);
    $gradebook = $_SESSION['gradebook'];
} elseif (empty($_GET['gradebook'])) {
    unset($_SESSION['gradebook']);
    $gradebook = '';
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array (
        'url' => '../gradebook/' . $_SESSION['gradebook_dest'],
        'name' => get_lang('ToolGradebook')
    );
}
$interbreadcrumb[] = array('url' => 'lp_controller.php?action=list', 'name' => get_lang('LearningPaths'));
$interbreadcrumb[] = array('url' => '#', "name" => $therow['name']);

// Theme calls.
$lp_theme_css=$_SESSION['oLP']->get_theme();
$show_learn_path = true;
Display::display_header('', 'Path');
$suredel = trim(get_lang('AreYouSureToDeleteJS'));

?>
<script>
/* <![CDATA[ */
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

/* DISPLAY SECTION */

echo $_SESSION['oLP']->build_action_menu();

echo '<div class="row">';
echo '<div class="col-md-4">';
// Build the tree with the menu items in it.
echo $_SESSION['oLP']->return_new_tree();
echo '</div>';
echo '<div class="col-md-8">';

if (isset($is_success) && $is_success === true) {
    echo Display::return_message(get_lang('ItemRemoved'), 'confirmation');
} else {
    if ($is_new) {
        echo Display::return_message(get_lang('LearnpathAdded'), 'normal', false);
    }
    // Display::addFlash(Display::return_message(get_lang('LPCreatedAddChapterStep'), 'normal', false));
    $gradebook = isset($_GET['gradebook']) ? Security::remove_XSS($_GET['gradebook']) : null;

    echo Display::page_subheader(get_lang('LearnPathAddedTitle'));

    echo '<ul id="lp_overview" class="thumbnails">';
    echo show_block(
        'lp_controller.php?'.api_get_cidreq().'&gradebook='.$gradebook.'&action=add_item&type=step&lp_id='.$_SESSION['oLP']->lp_id,
        get_lang("NewStep"),
        get_lang('NewStepComment'),
        'tools.png'
    );
    echo show_block(
        'lp_controller.php?'.api_get_cidreq().'&gradebook='.$gradebook.'&action=view&lp_id='.$_SESSION['oLP']->lp_id,
        get_lang("Display"),
        get_lang('DisplayComment'),
        'view.png'
    );
    echo '</ul>';
}
echo '</div>';
echo '</div>';


function show_block($link, $title, $subtitle, $icon) {
    $html = '<li class="col-md-4">';
    $html .=  '<div class="thumbnail">';
    $html .=  '<a href="'.$link.'" title="'.$title.'">';
    $html .=  Display::return_icon($icon, $title, array(), ICON_SIZE_BIG);
    $html .=  '</a>';
    $html .=  '<div class="caption">';
    $html .=  '<strong>'.$title.'</strong></a> '.$subtitle;
    $html .=  '</div>';
    $html .=  '</div>';
    $html .=  '</li>';
    return $html;
}

/* FOOTER */

Display::display_footer();
