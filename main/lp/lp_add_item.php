<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This is a learning path creation and player tool in Chamilo - previously
 * learnpath_handler.php.
 *
 * @author Patrick Cool
 * @author Denes Nagy
 * @author Roan Embrechts, refactoring and code cleaning
 * @author Yannick Warnier <ywarnier@beeznest.org> - cleaning and update
 * @author Julio Montoya  - Improving the list of templates
 *
 * @package chamilo.learnpath
 */
$this_section = SECTION_COURSES;

api_protect_course_script();

$isStudentView = isset($_REQUEST['isStudentView']) ? $_REQUEST['isStudentView'] : null;
$lpId = isset($_REQUEST['lp_id']) ? (int) $_REQUEST['lp_id'] : 0;
$submit = isset($_POST['submit_button']) ? $_POST['submit_button'] : null;
$type = isset($_GET['type']) ? $_GET['type'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;
$is_allowed_to_edit = api_is_allowed_to_edit(null, false);

$listUrl = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?action=view&lp_id='.$lpId.'&'.api_get_cidreq().'&isStudentView=true';
if (!$is_allowed_to_edit) {
    header("Location: $listUrl");
    exit;
}

/** @var learnpath $learnPath */
$learnPath = Session::read('oLP');

if (empty($learnPath)) {
    api_not_allowed();
}

if ($learnPath->get_lp_session_id() != api_get_session_id()) {
    // You cannot edit an LP from a base course.
    header("Location: $listUrl");
    exit;
}

$htmlHeadXtra[] = '<script>'.$learnPath->get_js_dropdown_array()."
function load_cbo(id, previousId) {
    if (!id) {
        return false;
    }
    
    previousId = previousId || 'previous';

    var cbo = document.getElementById(previousId);
    for (var i = cbo.length - 1; i > 0; i--) {
        cbo.options[i] = null;
    }

    var k=0;
    for (var i = 1; i <= child_name[id].length; i++){
        var option = new Option(child_name[id][i - 1], child_value[id][i - 1]);
        option.style.paddingLeft = '40px';
        cbo.options[i] = option;
        k = i;
    }

    cbo.options[k].selected = true;
    $('#' + previousId).selectpicker('refresh');
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

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Assessments'),
    ];
}

$htmlHeadXtra[] = api_get_jquery_libraries_js(['jquery-ui', 'jquery-upload']);
$interbreadcrumb[] = [
    'url' => 'lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('Learning paths'),
];
$interbreadcrumb[] = [
    'url' => api_get_self()."?action=build&lp_id=$lpId&".api_get_cidreq(),
    'name' => $learnPath->getNameNoTags(),
];

switch ($type) {
    case 'dir':
        $interbreadcrumb[] = [
            'url' => 'lp_controller.php?action=add_item&type=step&lp_id='.$learnPath->get_id().'&'.api_get_cidreq(),
            'name' => get_lang('Add learning object or activity'),
        ];
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Add section')];
        break;
    case 'document':
        $interbreadcrumb[] = [
            'url' => 'lp_controller.php?action=add_item&type=step&lp_id='.$learnPath->get_id().'&'.api_get_cidreq(),
            'name' => get_lang('Add learning object or activity'),
        ];
        break;
    default:
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Add learning object or activity')];
        break;
}

if ($action === 'add_item' && $type === 'document') {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('The rich media page/activity has been added to the course')];
}

// Theme calls.
$show_learn_path = true;
$lp_theme_css = $learnPath->get_theme();

Display::display_header(null, 'Path');

$suredel = trim(get_lang('Are you sure to delete'));
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

$(function() {
    jQuery('.scrollbar-inner').scrollbar();

    $('#subtab ').on('click', 'a:first', function() {
        window.location.reload();
    });
    expandColumnToogle('#hide_bar_template', {
        selector: '#lp_sidebar'
    }, {
        selector: '#doc_form'
    });

    $('.lp-btn-associate-forum').on('click', function (e) {
        var associate = confirm('<?php echo get_lang('This action will associate a forum thread to this learning path item. Do you want to proceed?'); ?>');

        if (!associate) {
            e.preventDefault();
        }
    });

    $('.lp-btn-dissociate-forum').on('click', function (e) {
        var dissociate = confirm('<?php echo get_lang('This action will dissociate the forum thread of this learning path item. Do you want to proceed?'); ?>');

        if (!dissociate) {
            e.preventDefault();
        }
    });

    // hide the current template list for new documment until it tab clicked
    $('#frmModel').hide();
});

// document template for new document tab handler
$(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function (e) {
    var id = e.target.id;
    if (id == 'subtab2') {
        $('#frmModel').show();
    } else {
        $('#frmModel').hide();
    }
})
</script>
<?php

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
if (in_array($message, ['ItemUpdated'])) {
    echo Display::return_message(get_lang($message));
}

if (isset($new_item_id) && is_numeric($new_item_id)) {
    switch ($type) {
        case 'dir':
            echo $learnPath->display_manipulate($new_item_id, $_POST['type']);
            echo Display::return_message(
                get_lang('Add sectionCreated'),
                'confirmation'
            );
            break;
        case TOOL_LINK:
            echo $learnPath->display_manipulate($new_item_id, $type);
            echo Display::return_message(
                get_lang('The new link has been created'),
                'confirmation'
            );
            break;
        case TOOL_STUDENTPUBLICATION:
            echo $learnPath->display_manipulate($new_item_id, $type);
            echo Display::return_message(
                get_lang('The new assignment has been created'),
                'confirmation'
            );
            break;
        case TOOL_QUIZ:
            echo $learnPath->display_manipulate($new_item_id, $type);
            echo Display::return_message(
                get_lang('The test has been added to the course'),
                'confirmation'
            );
            break;
        case TOOL_DOCUMENT:
            echo Display::return_message(
                get_lang('The rich media page/activity has been added to the course'),
                'confirmation'
            );
            echo $learnPath->display_item($new_item_id);
            break;
        case TOOL_FORUM:
            echo $learnPath->display_manipulate($new_item_id, $type);
            echo Display::return_message(
                get_lang('A new forum has now been created'),
                'confirmation'
            );
            break;
        case 'thread':
            echo $learnPath->display_manipulate($new_item_id, $type);
            echo Display::return_message(
                get_lang('A new forum thread has now been created'),
                'confirmation'
            );
            break;
    }
} else {
    switch ($type) {
        case 'dir':
            echo $learnPath->display_item_form(
                $type,
                get_lang('EnterDataAdd section')
            );
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
            echo Display::return_message(
                get_lang('Exercise can\'t be edited after being added to the Learning Path'),
                'warning'
            );
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
