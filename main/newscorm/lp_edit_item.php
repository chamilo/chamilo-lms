<?php
/* For licensing terms, see /license.txt */

/**
 * This is a learning path creation and player tool in Chamilo - previously learnpath_handler.php
 *
 * @author Patrick Cool
 * @author Denes Nagy
 * @author Roan Embrechts, refactoring and code cleaning
 * @author Yannick Warnier <ywarnier@beeznest.org> - cleaning and update for new SCORM tool
 * @author Julio Montoya  - Improving the list of templates
 * @package chamilo.learnpath
*/
/**
 * INIT SECTION 
 */

$this_section = SECTION_COURSES;

api_protect_course_script();

/* Libraries */

// The main_api.lib.php, database.lib.php and display.lib.php
// libraries are included by default.

include 'learnpath_functions.inc.php';
//include '../resourcelinker/resourcelinker.inc.php';
include 'resourcelinker.inc.php';
// Rewrite the language file, sadly overwritten by resourcelinker.inc.php.
// Name of the language file that needs to be included.
$language_file = 'learnpath';

/* Header and action code */

$htmlHeadXtra[] = '
<script type="text/javascript">

function FCKeditor_OnComplete( editorInstance ) {
    document.getElementById(\'frmModel\').innerHTML = "<iframe height=890px; width=230px; frameborder=0 src=\''.api_get_path(WEB_LIBRARY_PATH).'fckeditor/editor/fckdialogframe.html \'>";
}

function InnerDialogLoaded() {
    if (document.all) {
        // if is iexplorer
        var B=new window.frames.content_lp___Frame.FCKToolbarButton(\'Templates\',window.content_lp___Frame.FCKLang.Templates);
    } else {
        var B=new window.frames[0].FCKToolbarButton(\'Templates\',window.frames[0].FCKLang.Templates);
    }
    return B.ClickFrame();
$};'.$_SESSION['oLP']->get_js_dropdown_array().'</script>';

/* Constants and variables */

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

$tbl_lp = Database::get_course_table(TABLE_LP_MAIN);

$isStudentView  = (int) $_REQUEST['isStudentView'];
$learnpath_id   = (int) $_REQUEST['lp_id'];
$submit			= $_POST['submit_button'];

/* MAIN CODE */

// Using the resource linker as a tool for adding resources to the learning path.
if ($action == 'add' && $type == 'learnpathitem') {
     $htmlHeadXtra[] = "<script language='JavaScript' type='text/javascript'> window.location=\"../resourcelinker/resourcelinker.php?source_id=5&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no\"; </script>";
}
if ((!$is_allowed_to_edit) || ($isStudentView)) {
    error_log('New LP - User not authorized in lp_add_item.php');
    header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
}
// From here on, we are admin because of the previous condition, so don't check anymore.

$course_id = api_get_course_int_id();
$sql_query = "SELECT * FROM $tbl_lp WHERE c_id = $course_id AND id = $learnpath_id";
$result = Database::query($sql_query);
$therow = Database::fetch_array($result);

/*
    Course admin section
    - all the functions not available for students - always available in this case (page only shown to admin)
*/

/* SHOWING THE ADMIN TOOLS */

if (isset($_SESSION['gradebook'])) {
    $gradebook = $_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array (
            'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
            'name' => get_lang('ToolGradebook')
        );
}
$interbreadcrumb[] = array('url' => 'lp_controller.php?action=list', 'name' => get_lang('LearningPaths'));
$interbreadcrumb[] = array('url' => api_get_self()."?action=build&lp_id=$learnpath_id", 'name' => stripslashes("{$therow['name']}"));
$interbreadcrumb[] = array('url' => api_get_self()."?action=add_item&type=step&lp_id=$learnpath_id", 'name' => get_lang('NewStep'));

// Theme calls.
$show_learn_path = true;
$lp_theme_css = $_SESSION['oLP']->get_theme();

Display::display_header(get_lang('Edit'),'Path');
$suredel = trim(get_lang('AreYouSureToDelete'));

?>
<script type='text/javascript'>
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


echo '<div class="row-fluid">';
echo '<div class="span3">';

$path_item = isset($_GET['path_item']) ? $_GET['path_item'] : 0;
$path_item = Database::escape_string($path_item);
$tbl_doc = Database :: get_course_table(TABLE_DOCUMENT);
$sql_doc = "SELECT path FROM " . $tbl_doc . " WHERE c_id = $course_id AND id = '". $path_item."' ";
$res_doc = Database::query($sql_doc);
$path_file = Database::result($res_doc, 0, 0);
$path_parts = pathinfo($path_file);

if (Database::num_rows($res_doc) > 0 && $path_parts['extension'] == 'html') {
    echo $_SESSION['oLP']->return_new_tree();
    
    // Show the template list
    echo '<p style="border-bottom:1px solid #999999; margin:0; padding:2px;"></p>';
    echo '<br />';
    echo '<div id="frmModel" style="display:block; height:890px;width:100px; position:relative;"></div>';
} else {
    echo $_SESSION['oLP']->return_new_tree();    
}
        
echo '</div>';
echo '<div class="span9">';

if (isset($is_success) && $is_success === true) {
    $msg = '<div class="lp_message" style="margin-bottom:10px;">';
    $msg .= 'The item has been edited.';
    $msg .= '</div>';
    echo $_SESSION['oLP']->display_item($_GET['id'], $msg);
} else {
    echo $_SESSION['oLP']->display_edit_item($_GET['id']);
}

echo '</div>';
echo '</div>';

/* FOOTER */

Display::display_footer();
