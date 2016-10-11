<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

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

$this_section = SECTION_COURSES;

api_protect_course_script();

/** @var learnpath $learnPath */
$learnPath = Session::read('oLP');

$htmlHeadXtra[] = '<script>'.
$learnPath->get_js_dropdown_array()."
function load_cbo(id) {
    if (!id) {
        return false;
    }

    var cbo = document.getElementById('previous');
    for(var i = cbo.length - 1; i > 0; i--) {
        cbo.options[i] = null;
    }

    var k=0;
    for(var i = 1; i <= child_name[id].length; i++){
        var option = new Option(child_name[id][i - 1], child_value[id][i - 1]);
        option.style.paddingLeft = '40px';
        cbo.options[i] = option;
        k = i;
    }

    cbo.options[k].selected = true;
    $('#previous').selectpicker('refresh');
}

$(function() {
    if ($('#previous')) {
        if('parent is'+$('#idParent').val()) {
            load_cbo($('#idParent').val());
        }
    }
    $('.lp_resource_element').click(function() {
        window.location.href = $('a', this).attr('href');
    });
    
     CKEDITOR.on('instanceReady', function (e) {
        showTemplates('content_lp');
    });    
});
</script>";

/* Constants and variables */

$isStudentView = isset($_REQUEST['isStudentView']) ? $_REQUEST['isStudentView'] : null;
$learnpath_id = isset($_REQUEST['lp_id']) ? intval($_REQUEST['lp_id']) : null;
$submit = isset($_POST['submit_button']) ? $_POST['submit_button'] : null;

$type = isset($_GET['type']) ? $_GET['type'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;

if (!$is_allowed_to_edit) {
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

$htmlHeadXtra[] = api_get_jquery_libraries_js(array('jquery-ui', 'jquery-upload'));
$interbreadcrumb[] = array(
    'url' => 'lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('LearningPaths'),
);
$interbreadcrumb[] = array(
    'url' => api_get_self()."?action=build&lp_id=$learnpath_id&".api_get_cidreq(),
    'name' => $learnPath->get_name(),
);

switch ($type) {
    case 'dir':
        $interbreadcrumb[] = array(
            'url' => 'lp_controller.php?action=add_item&type=step&lp_id='.$learnPath->get_id().'&'.api_get_cidreq(),
            'name' => get_lang('NewStep'),
        );
        $interbreadcrumb[]= array('url' => '#', 'name' => get_lang('NewChapter'));
        break;
    case 'document':
        $interbreadcrumb[] = array(
            'url' => 'lp_controller.php?action=add_item&type=step&lp_id='.$learnPath->get_id().'&'.api_get_cidreq(),
            'name' => get_lang('NewStep'),
        );
        break;
    default:
        $interbreadcrumb[]= array('url' => '#', 'name' => get_lang('NewStep'));
        break;
}

if ($action == 'add_item' && $type == 'document') {
    $interbreadcrumb[]= array ('url' => '#', 'name' => get_lang('NewDocumentCreated'));
}

// Theme calls.
$show_learn_path = true;
$lp_theme_css = $learnPath->get_theme();

Display::display_header(null, 'Path');

$suredel = trim(get_lang('AreYouSureToDeleteJS'));
//@todo move this somewhere else css/fix.css
?>
<style>
    #feedback { font-size: 1.4em; }
    #resExercise .ui-selecting { background: #FECA40; }
    #resExercise .ui-selected { background: #F39814; color: white; }
    #resExercise { list-style-type: none; margin: 0; padding: 0; width: 60%; }
    #resExercise li { margin: 3px; padding: 0.4em; font-size: 1.4em; height: 18px; }
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

jQuery(document).ready(function(){
    jQuery('.scrollbar-inner').scrollbar();

    $('#subtab ').on('click', 'a:first', function() {
        window.location.reload();
    });
});

$(document).ready(function() {
    expandColumnToogle('#hide_bar_template', {
        selector: '#lp_sidebar'
    }, {
        selector: '#doc_form'
    });

    $('.lp-btn-associate-forum').on('click', function (e) {
        var associate = confirm('<?php echo get_lang('ConfirmAssociateForumToLPItem') ?>');

        if (!associate) {
            e.preventDefault();
        }
    });

    $('.lp-btn-dissociate-forum').on('click', function (e) {
        var dissociate = confirm('<?php echo get_lang('ConfirmDissociateForumToLPItem') ?>');

        if (!dissociate) {
            e.preventDefault();
        }
    });

    // hide the current template list for new documment until it tab clicked
    $('#frmModel').hide();

});

// document template for new document tab handler
$(document).on( 'shown.bs.tab', 'a[data-toggle="tab"]', function (e) {

    var id = e.target.id;

    if (id == 'subtab2') {
        $('#frmModel').show();
    } else {
        $('#frmModel').hide();
    }
})
</script>
<?php

/* DISPLAY SECTION */

echo $learnPath->build_action_menu();
echo '<div class="row">';
echo '<div id="lp_sidebar" class="col-md-4">';
echo $learnPath->return_new_tree(null, true);

$message = isset($_REQUEST['message']) ? $_REQUEST['message'] : null;

// Show the template list.
if (($type == 'document' || $type == 'step') && !isset($_GET['file'])) {
    // Show the template list.
    echo '<div id="frmModel" class="scrollbar-inner lp-add-item">';
    echo '</div>';
}

echo '</div>';

echo '<div id="doc_form" class="col-md-8">';

//@todo use session flash messages
if (in_array($message, array('ItemUpdated'))) {
    echo Display::return_message(get_lang($message));
}

if (isset($new_item_id) && is_numeric($new_item_id)) {
    switch ($type) {
        case 'dir':
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
        case 'dir':
            echo $learnPath->display_item_form($type, get_lang('EnterDataNewChapter'));
            break;
        case TOOL_DOCUMENT:
            if (isset($_GET['file']) && is_numeric($_GET['file'])) {
                echo $learnPath->display_document_form('add', 0, $_GET['file']);
            } else {
                echo $learnPath->display_document_form('add', 0);
            }
            break;
        case 'hotpotatoes':
            echo $learnPath->display_hotpotatoes_form('add', 0, $_GET['file']);
            break;
        case TOOL_QUIZ:
            echo Display::display_warning_message(get_lang('ExerciseCantBeEditedAfterAddingToTheLP'));
            echo $learnPath->display_quiz_form('add', 0, $_GET['file']);
            break;
        case TOOL_FORUM:
            echo $learnPath->display_forum_form('add', 0, $_GET['forum_id']);
            break;
        case 'thread':
            echo $learnPath->display_thread_form('add', 0, $_GET['thread_id']);
            break;
        case TOOL_LINK:
            echo $learnPath->display_link_form('add', 0, $_GET['file']);
            break;
        case TOOL_STUDENTPUBLICATION:
            $extra = isset($_GET['file']) ? $_GET['file'] : null;
            echo $learnPath->display_student_publication_form('add', 0, $extra);
            break;
        case 'step':
            $learnPath->display_resources();
            break;
    }
}
echo '</div>';
echo '</div>';

Display::display_footer();
