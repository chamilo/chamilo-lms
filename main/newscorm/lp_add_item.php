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
/** @var \learnpath $learnPath */
$learnPath = $_SESSION['oLP'];

$htmlHeadXtra[] = '
<script>'.$learnPath->get_js_dropdown_array() .

'function load_cbo(id){' ."\n" .
  'if (!id) {return false;}'.
  'var cbo = document.getElementById(\'previous\');' .
  'for(var i = cbo.length - 1; i > 0; i--) {' .
    'cbo.options[i] = null;' .
  '}' ."\n" .
  'var k=0;' .
  'for(var i = 1; i <= child_name[id].length; i++){' ."\n" .
  '  cbo.options[i] = new Option(child_name[id][i-1], child_value[id][i-1]);' ."\n" .
  '  k=i;' ."\n" .
  '}' ."\n" .
  //'if( typeof cbo != "undefined" ) {'."\n" .
  'cbo.options[k].selected = true;'."\n" .
   //'}'."\n" .
'}

$(function() {
    if ($(\'#previous\')) {
        if(\'parent is\'+$(\'#idParent\').val()) {
            load_cbo($(\'#idParent\').val());
        }
    }
    //Loads LP item tabs

    $("#resource_tab").tabs();
    $(\'.lp_resource_element\').click(function() {
        window.location.href = $(\'a\', this).attr(\'href\');
    });
});
</script>';

/* Constants and variables */

// Already set in lp_controller.php

$isStudentView  = isset($_REQUEST['isStudentView']) ? $_REQUEST['isStudentView'] : null;
$learnpath_id   = isset($_REQUEST['lp_id']) ? intval($_REQUEST['lp_id']) : null;
$submit			= isset($_POST['submit_button']) ? $_POST['submit_button'] : null;

$type = isset($_GET['type']) ? $_GET['type'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;

// Using the resource linker as a tool for adding resources to the learning path.
if ($action == 'add' && $type == 'learnpathitem') {
     $htmlHeadXtra[] = "<script type='text/javascript'> window.location=\"../resourcelinker/resourcelinker.php?source_id=5&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no\"; </script>";
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
$interbreadcrumb[] = array('url' => api_get_self()."?action=build&lp_id=$learnpath_id", 'name' => $learnPath->get_name());

switch ($type) {
    case 'chapter':
        $interbreadcrumb[]= array ('url' => 'lp_controller.php?action=add_item&type=step&lp_id='.$learnPath->get_id(), 'name' => get_lang('NewStep'));
        $interbreadcrumb[]= array ('url' => '#', 'name' => get_lang('NewChapter'));
        break;
    case 'document':
        $interbreadcrumb[]= array ('url' => 'lp_controller.php?action=add_item&type=step&lp_id='.$learnPath->get_id(), 'name' => get_lang('NewStep'));
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
$lp_theme_css = $learnPath->get_theme();

Display::display_header(null, 'Path');

$suredel = trim(get_lang('AreYouSureToDelete'));
//@todo move this somewhere else css/fix.css

$iconUrl = api_get_path(WEB_CODE_PATH).'img/';
?>
<style>
    #feedback { font-size: 1.4em; }
    #resExercise .ui-selecting { background: #FECA40; }
    #resExercise .ui-selected { background: #F39814; color: white; }
    #resExercise { list-style-type: none; margin: 0; padding: 0; width: 60%; }
    #resExercise li { margin: 3px; padding: 0.4em; font-size: 1.4em; height: 18px; }

    /* Fixes LP toolbar */
    #resource_tab li a {
        padding: 5px 4px;
    }
</style>

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

$(document).ready(function() {
    $("#hide_bar_template").toggle(
        function() {
            $("#lp_sidebar").hide();
            $(this).css({'background-image' : 'url("<?php echo $iconUrl; ?>hide2.png")'})
            $("#doc_form").removeClass("span8");
            $("#doc_form").addClass("span11");
        },
        function() {
            $("#lp_sidebar").show();
            $("#doc_form").removeClass("span11");
            $("#doc_form").addClass("span8");
            $(this).css('background-image', 'url("<?php echo $iconUrl; ?>hide0.png")');
        }
    );
});
</script>
<?php

/* DISPLAY SECTION */

echo $learnPath->build_action_menu();

echo '<div class="row" style="overflow:hidden">';
echo '<div id="lp_sidebar" class="col-md-4">';

echo $learnPath->return_new_tree(null, true);

$message = isset($_REQUEST['message']) ? $_REQUEST['message'] : null;

echo '</div>';

//hide bar div
if ($action == 'add_item' && $type == 'document' && !isset($_GET['file'])) {
    echo '<div id="hide_bar_template"></div>';
}

echo '<div id="doc_form" class="col-md-8">';

//@todo use session flash messages
if (in_array($message, array('ItemUpdated'))) {
    echo Display::return_message(get_lang($message));
}

if (isset($new_item_id) && is_numeric($new_item_id)) {
    switch ($type) {
        case 'chapter':
            echo $learnPath->display_manipulate($new_item_id, $_POST['type']);
            Display::display_confirmation_message(get_lang('NewChapterCreated'));
            break;
        case TOOL_LINK:
            echo $learnPath->display_manipulate($new_item_id, $type);
            Display::display_confirmation_message(get_lang('NewLinksCreated'));
            break;
        case TOOL_STUDENTPUBLICATION:
            echo $learnPath->display_manipulate($new_item_id, $type);
            Display::display_confirmation_message(get_lang('NewStudentPublicationCreated'));
            break;
        case 'module':
            echo $learnPath->display_manipulate($new_item_id, $type);
            Display::display_confirmation_message(get_lang('NewModuleCreated'));
            break;
        case TOOL_QUIZ:
            echo $learnPath->display_manipulate($new_item_id, $type);
            Display::display_confirmation_message(get_lang('NewExerciseCreated'));
            break;
        case TOOL_DOCUMENT:
            Display::display_confirmation_message(get_lang('NewDocumentCreated'));
            echo $learnPath->display_item($new_item_id);
            break;
        case TOOL_FORUM:
            echo $learnPath->display_manipulate($new_item_id, $type);
            Display::display_confirmation_message(get_lang('NewForumCreated'));
            break;
        case 'thread':
            echo $learnPath->display_manipulate($new_item_id, $type);
            Display::display_confirmation_message(get_lang('NewThreadCreated'));
            break;
    }
} else {
    switch ($type) {
        case 'chapter':
            echo $learnPath->display_item_form($type, get_lang('EnterDataNewChapter'));
            break;
        case 'module':
            echo $learnPath->display_item_form($type, get_lang('EnterDataNewModule'));
            break;
        case 'document':
            if (isset($_GET['file']) && is_numeric($_GET['file'])) {
                echo $learnPath->display_document_form('add', 0, $_GET['file']);
            } else {
                echo $learnPath->display_document_form('add', 0);
            }
            break;
        case 'hotpotatoes':
            echo $learnPath->display_hotpotatoes_form('add', 0, $_GET['file']);
            break;
        case 'quiz':
            echo Display::display_warning_message(get_lang('ExerciseCantBeEditedAfterAddingToTheLP'));
            echo $learnPath->display_quiz_form('add', 0, $_GET['file']);
            break;
        case 'forum':
            echo $learnPath->display_forum_form('add', 0, $_GET['forum_id']);
            break;
        case 'thread':
            echo $learnPath->display_thread_form('add', 0, $_GET['thread_id']);
            break;
        case 'link':
            echo $learnPath->display_link_form('add', 0, $_GET['file']);
            break;
        case 'student_publication':
            echo $learnPath->display_student_publication_form('add', 0, $_GET['file']);
            break;
        case 'step':
            $learnPath->display_resources();
            break;
    }
}
echo '</div>';
echo '</div>';

/* FOOTER */
Display::display_footer();
