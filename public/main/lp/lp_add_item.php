<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * @author Patrick Cool
 * @author Denes Nagy
 * @author Roan Embrechts, refactoring and code cleaning
 * @author Yannick Warnier <ywarnier@beeznest.org> - cleaning and update
 * @author Julio Montoya  - Improving the list of templates
 */
$this_section = SECTION_COURSES;

api_protect_course_script();

$isStudentView = $_REQUEST['isStudentView'] ?? null;
$lpId = isset($_REQUEST['lp_id']) ? (int) $_REQUEST['lp_id'] : 0;
$submit = $_POST['submit_button'] ?? null;
$type = isset($_GET['type']) ? $_GET['type'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;
$is_allowed_to_edit = api_is_allowed_to_edit(null, false);

$listUrl = api_get_path(WEB_CODE_PATH).
    'lp/lp_controller.php?action=view&lp_id='.$lpId.'&'.api_get_cidreq().'&isStudentView=true';
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
    //$('#' + previousId).selectpicker('refresh');
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

if ('add_item' === $action && 'document' === $type) {
    $interbreadcrumb[] = [
        'url' => '#',
        'name' => get_lang('The rich media page/activity has been added to the course'),
    ];
}

$show_learn_path = true;
$lp_theme_css = $learnPath->get_theme();
Display::display_header(null, 'Path');
echo $learnPath->build_action_menu();
echo '<div class="row">';
echo $learnPath->showBuildSideBar(null, true, $type);
echo '<div id="doc_form" class="col-md-8">';
$learnPath->displayResources();

/*
switch ($type) {
    case 'dir':
        echo $learnPath->display_item_form(
            $type
        );
        break;
    case TOOL_DOCUMENT:
        if (isset($_GET['file']) && is_numeric($_GET['file'])) {
            echo $learnPath->display_document_form('add', 0, $_GET['file']);
        } else {
            echo $learnPath->display_document_form('add');
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
        echo $learnPath->display_link_form('add');
        break;
    case TOOL_STUDENTPUBLICATION:
        echo $learnPath->display_student_publication_form('add', 0, $extra);
        break;
    case 'step':
        break;
}*/
echo '</div>';
echo '</div>';

Display::display_footer();
