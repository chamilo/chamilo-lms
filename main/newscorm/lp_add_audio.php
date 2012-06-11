<?php
/* For licensing terms, see /license.txt */
/**
 * This is a learning path creation and player tool in Chamilo - previously
 * learnpath_handler.php
 *
 * @author Patrick Cool
 * @author Denes Nagy
 * @author Roan Embrechts, refactoring and code cleaning
 * @author Yannick Warnier <ywarnier@beeznest.org> - cleaning and update
 * @author Julio Montoya  - Improving the list of templates
 * @package chamilo.learnpath
 */
/**
 * INIT SECTION
 */

$this_section = SECTION_COURSES;

api_protect_course_script();

include 'learnpath_functions.inc.php';
include 'resourcelinker.inc.php';

$language_file = 'learnpath';

/* Constants and variables */

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

$isStudentView  = (int) $_REQUEST['isStudentView'];
$learnpath_id   = (int) $_REQUEST['lp_id'];
$submit			= $_POST['submit_button'];


$type = isset($_GET['type']) ? $_GET['type'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;

// Using the resource linker as a tool for adding resources to the learning path.
if ($action == 'add' && $type == 'learnpathitem') {
     $htmlHeadXtra[] = "<script language='JavaScript' type='text/javascript'> window.location=\"../resourcelinker/resourcelinker.php?source_id=5&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no\"; </script>";
}
if ((!$is_allowed_to_edit) || ($isStudentView)) {
    error_log('New LP - User not authorized in lp_add_item.php');
    header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
    exit;
}
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
$interbreadcrumb[] = array('url' => api_get_self()."?action=build&lp_id=$learnpath_id", 'name' => $_SESSION['oLP']->get_name());

switch ($type) {
    case 'chapter':
        $interbreadcrumb[]= array ('url' => 'lp_controller.php?action=add_item&type=step&lp_id='.$_SESSION['oLP']->get_id(), 'name' => get_lang('NewStep'));
        $interbreadcrumb[]= array ('url' => '#', 'name' => get_lang('NewChapter'));
        break;
    case 'document':
        $interbreadcrumb[]= array ('url' => 'lp_controller.php?action=add_item&type=step&lp_id='.$_SESSION['oLP']->get_id(), 'name' => get_lang('NewStep'));
        break;
    default:
        $interbreadcrumb[]= array ('url' => '#', 'name' => get_lang('NewStep'));
        break;
}

if ($action == 'add_item' && $type == 'document' ) {
    $interbreadcrumb[]= array ('url' => '#', 'name' => get_lang('NewDocumentCreated'));
}

// Theme calls.
$show_learn_path = true;
$lp_item_id = isset($_GET['id']) ? intval($_GET['id']) : null;
if (empty($lp_item_id)) {
    api_not_allowed();
}

Display::display_header(null, 'Path');

$suredel = trim(get_lang('AreYouSureToDelete'));

/* DISPLAY SECTION */

echo $_SESSION['oLP']->build_action_menu();

echo '<div class="row-fluid" style="overflow:hidden">';
echo '<div id="lp_sidebar" class="span4">';
echo $_SESSION['oLP']->return_new_tree(null, true); 
// Show the template list.
echo '</div>';

echo '<div id="doc_form" class="span8">';

$form = new FormValidator('add_audio', 'post', api_get_self().'?action=add_audio&id='.$lp_item_id, null, array('enctype' => 'multipart/form-data'));
$form->addElement('header', get_lang('Upload'));
$form->addElement('file', 'file', get_lang('File'));
$form->addElement('hidden', 'id', $lp_item_id);

$lp_item = new learnpathItem($lp_item_id);

if (isset($lp_item->audio) && !empty($lp_item->audio))  {
    $form->addElement('checkbox', 'delete_file', null, get_lang('RemoveAudio'));
    
    $player = '<script type="text/javascript" src="../inc/lib/mediaplayer/swfobject.js"></script>';
    $player .= '<div id="preview"></div><script type="text/javascript">
                    var s1 = new SWFObject("../inc/lib/mediaplayer/player.swf","ply","250","20","9","#FFFFFF");
                    s1.addParam("allowscriptaccess","always");
                    s1.addParam("flashvars","file=../../courses/' . $_course['path'] . '/document/audio/' . $lp_item->audio . '");
                    s1.write("preview");
                </script>';
    
    $form->addElement('label', get_lang('Preview'), $player);
}
$form->addElement('button', 'submit', get_lang('Edit'));


//RemoveAudio
$form->display();
echo '</div>';

echo '</div>';

/* FOOTER */
Display::display_footer();