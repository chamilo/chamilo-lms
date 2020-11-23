<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

if (!api_is_platform_admin() && api_get_setting('ticket_allow_student_add') !== 'true') {
    header('Location:'.api_get_path(WEB_CODE_PATH).'ticket/tickets.php');
    exit;
}

api_block_anonymous_users();
$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();
$exerciseId = (isset($_GET['exerciseId']) && !empty($_GET['exerciseId'])) ? (int) $_GET['exerciseId'] : 0;
$lpId = (isset($_GET['lpId']) && !empty($_GET['lpId'])) ? (int) $_GET['lpId'] : 0;

$htmlHeadXtra[] = '<script>
function updateCourseList(sessionId) {
    var $selectCourse = $("select#course_id");
    $selectCourse.empty();

    $.get("'.api_get_path(WEB_AJAX_PATH).'session.ajax.php", {
        a: "get_courses_inside_session",
        session_id : sessionId
    }, function (courseList) {
        $("<option>", {
            value: 0,
            text: "'.get_lang('Select').'"
        }).appendTo($selectCourse);

        if (courseList.length > 0) {
            $.each(courseList, function (index, course) {
                $("<option>", {
                    value: course.id,
                    text: course.name
                }).appendTo($selectCourse);
            });

            $selectCourse.find("option[value=\''.$courseId.'\']").attr("selected",true);
            $selectCourse.selectpicker("refresh");
        }
    }, "json");
}

function updateExerciseList(courseId, sessionId) {
    var $selectExercise = $("select#exercise_id");
    $selectExercise.empty();

    $.get("'.api_get_path(WEB_AJAX_PATH).'exercise.ajax.php", {
        a: "get_exercise_by_course",
        course_id: courseId,
        session_id : sessionId
    }, function (exerciseList) {
        $("<option>", {
            value: 0,
            text: "'.get_lang('Select').'"
        }).appendTo($selectExercise);

        if (exerciseList.length > 0) {
            $.each(exerciseList, function (index, exercise) {
                $("<option>", {
                    value: exercise.id,
                    text: exercise.text
                }).appendTo($selectExercise);
            });

            $selectExercise.find("option[value=\''.$exerciseId.'\']").attr("selected",true);
        }

        $selectExercise.selectpicker("refresh");
    }, "json");
}

function updateLpList(courseId, sessionId) {
    var $selectLp = $("select#lp_id");
    $selectLp.empty();

    $.get("'.api_get_path(WEB_AJAX_PATH).'lp.ajax.php", {
        a: "get_lp_list_by_course",
        course_id: courseId,
        session_id: sessionId
    }, function (lpList) {
        $("<option>", {
            value: 0,
            text: "'.get_lang('Select').'"
        }).appendTo($selectLp);

        if (lpList.length > 0) {
            $.each(lpList, function (index, lp) {
                $("<option>", {
                    value: lp.id,
                    text: lp.text
                }).appendTo($selectLp);
            });
            $selectLp.find("option[value=\''.$lpId.'\']").attr("selected",true);
        }

        $selectLp.selectpicker("refresh");
    }, "json");
}

$(document).ready(function() {
    var $selectSession = $("select#session_id");
    var $selectCourse = $("select#course_id");

    $selectSession.on("change", function () {
        var sessionId = parseInt(this.value, 10);

        updateCourseList(sessionId);
        updateExerciseList(0, sessionId);
        updateLpList(0);
    });

    $selectCourse.on("change", function () {
        var sessionId = $selectSession.val();
        var courseId = parseInt(this.value, 10);

        updateExerciseList(courseId, sessionId);
        updateLpList(courseId);
    });

    var sessionId = $selectSession.val() ? $selectSession.val() : '.$sessionId.';
    var courseId = $selectCourse.val() ? $selectCourse.val() : '.$courseId.';

    updateCourseList(sessionId);
    updateExerciseList(courseId, sessionId);
    updateLpList(courseId, sessionId);
});

var counter_image = 1;

function remove_image_form(element_id) {
    $("#" + element_id).remove();
    counter_image = counter_image - 1;
    $("#link-more-attach").css("display", "block");
}

function add_image_form() {
    // Multiple filepaths for image form
    var filepaths = $("#filepaths");
    var new_elem, input_file, link_remove, img_remove, new_filepath_id;

    if ($("#filepath_"+counter_image)) {
        counter_image = counter_image + 1;
    }  else {
        counter_image = counter_image;
    }

    new_elem = "filepath_"+counter_image;

    $("<div/>", {
        id: new_elem,
        class: "controls"
    }).appendTo(filepaths);

    input_file = $("<input/>", {
        type: "file",
        name: "attach_" + counter_image,
        size: 20
    });

    link_remove = $("<a/>", {
        onclick: "remove_image_form(\'" + new_elem + "\')",
        style: "cursor: pointer"
    });

    img_remove = $("<img/>", {
        src: "'.Display::returnIconPath('delete.png').'"
    });

    new_filepath_id = $("#filepath_" + counter_image);
    new_filepath_id.append(input_file, link_remove.append(img_remove));

    if (counter_image === 6) {
        var link_attach = $("#link-more-attach");
        if (link_attach) {
            $(link_attach).css("display", "none");
        }
    }
}
</script>';

$projectId = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;

$types = TicketManager::get_all_tickets_categories($projectId, 'category.name ASC');
$htmlHeadXtra[] = '<script>
    var projects = '.js_array($types, 'projects', 'project_id').'
    var course_required = '.js_array($types, 'course_required', 'course_required').'
    var other_area = '.js_array($types, 'other_area', 'other_area').'
    var email = '.js_array($types, 'email', 'email').
'</script>';

/**
 * @param $array
 * @param $name
 * @param $key
 *
 * @return string
 */
function js_array($array, $name, $key)
{
    $return = "new Array(); ";
    foreach ($array as $value) {
        $return .= $name."['".$value['category_id']."'] ='".$value[$key]."'; ";
    }

    return $return;
}

function save_ticket()
{
    $content = $_POST['content'];
    if (!empty($_POST['phone'])) {
        $content .= '<p style="color:red">&nbsp;'.get_lang('Phone').': '.Security::remove_XSS($_POST['phone']).'</p>';
    }
    $course_id = isset($_POST['course_id']) ? (int) $_POST['course_id'] : '';
    $sessionId = isset($_POST['session_id']) ? (int) $_POST['session_id'] : '';
    $category_id = isset($_POST['category_id']) ? (int) $_POST['category_id'] : '';
    $exercise_id = isset($_POST['exercise_id']) ? (int) $_POST['exercise_id'] : null;
    $lp_id = isset($_POST['lp_id']) ? (int) $_POST['lp_id'] : null;

    $project_id = (int) $_POST['project_id'];
    $subject = $_POST['subject'];
    $other_area = (int) $_POST['other_area'];
    $personal_email = $_POST['personal_email'];
    $source = (int) $_POST['source_id'];
    $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
    $priority = isset($_POST['priority_id']) ? (int) $_POST['priority_id'] : '';
    $status = isset($_POST['status_id']) ? (int) $_POST['status_id'] : '';
    $file_attachments = $_FILES;

    if (TicketManager::add(
        $category_id,
        $course_id,
        $sessionId,
        $project_id,
        $other_area,
        $subject,
        $content,
        $personal_email,
        $file_attachments,
        $source,
        $priority,
        $status,
        $user_id,
        $exercise_id,
        $lp_id
    )) {
        header('Location:'.api_get_path(WEB_CODE_PATH).'ticket/tickets.php');
        exit;
    } else {
        Display::addFlash(Display::return_message(get_lang('ThereWasAnErrorRegisteringTheTicket')));
    }
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'ticket/tickets.php',
    'name' => get_lang('MyTickets'),
];

$userId = api_get_user_id();

// Category List
$categoryList = [];
foreach ($types as $type) {
    $categoryList[$type['category_id']] = $type['name'].': '.$type['description'];
}

// Status List
$statusAttributes = [
    'style' => 'display: none;',
    'id' => 'status_id',
    'for' => 'status_id',
];

$statusList = TicketManager::getStatusList();

// Source List
$sourceList = [];
$sourceAttributes = [
    'style' => 'display: none;',
    'id' => 'source_id',
    'for' => 'source_id',
];
$sourceList[TicketManager::SOURCE_PLATFORM] = get_lang('SrcPlatform');
if (api_is_platform_admin()) {
    $sourceAttributes = [
        'id' => 'source_id',
        'for' => 'source_id',
    ];
    $sourceList[TicketManager::SOURCE_EMAIL] = get_lang('SrcEmail');
    $sourceList[TicketManager::SOURCE_PHONE] = get_lang('SrcPhone');
    $sourceList[TicketManager::SOURCE_PRESENTIAL] = get_lang('SrcPresential');
}

// Priority List
$priorityList = TicketManager::getPriorityList();

$projectId = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;

$form = new FormValidator(
    'send_ticket',
    'POST',
    api_get_self().'?project_id='.$projectId,
    '',
    [
        'enctype' => 'multipart/form-data',
    ]
);

$form->addElement(
    'hidden',
    'user_id_request',
    '',
    [
        'id' => 'user_id_request',
    ]
);

$form->addElement(
    'hidden',
    'project_id',
    $projectId
);

$form->addElement(
    'hidden',
    'other_area',
    '',
    [
        'id' => 'other_area',
    ]
);

$form->addElement(
    'hidden',
    'email',
    '',
    [
        'id' => 'email',
    ]
);

$form->addSelect(
    'category_id',
    get_lang('Category'),
    $categoryList,
    [
        'id' => 'category_id',
        'for' => 'category_id',
        'style' => 'width: 562px;',
    ]
);

$form->addElement(
    'text',
    'subject',
    get_lang('Subject'),
    [
        'id' => 'subject',
    ]
);

$form->addHtmlEditor(
    'content',
    get_lang('Message'),
    false,
    false,
    [
        'ToolbarSet' => 'Profile',
        'Height' => '250',
    ]
);

if (api_is_platform_admin()) {
    $form->addSelectAjax(
        'user_id',
        get_lang('Assign'),
        null,
        ['url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=get_user_like']
    );
}

$form->addElement(
    'text',
    'personal_email',
    get_lang('PersonalEmail'),
    [
        'id' => 'personal_email',
    ]
);

$form->addLabel(
    '',
    Display::div(
        '',
        [
            'id' => 'user_request',
        ]
    )
);

$form->addElement(
    'select',
    'status_id',
    get_lang('Status'),
    $statusList,
    $statusAttributes
);

$form->addElement(
    'select',
    'priority_id',
    get_lang('Priority'),
    $priorityList,
    [
        'id' => 'priority_id',
        'for' => 'priority_id',
    ]
);

$form->addElement(
    'select',
    'source_id',
    get_lang('Source'),
    $sourceList,
    $sourceAttributes
);

$form->addElement(
    'text',
    'phone',
    get_lang('Phone').' ('.get_lang('Optional').')',
    [
        'id' => 'phone',
    ]
);

$sessionList = SessionManager::get_sessions_by_user($userId);

if (api_is_platform_admin() || !empty($sessionList)) {
    $sessionListToSelect = [get_lang('Select')];
    // Course List
    foreach ($sessionList as $sessionInfo) {
        $sessionListToSelect[$sessionInfo['session_id']] = $sessionInfo['session_name'];
    }

    $form->addSelect('session_id', get_lang('Session'), $sessionListToSelect, ['id' => 'session_id']);
} else {
    $form->addHidden('session_id', 0);
}

$form->addSelect('course_id', get_lang('Course'), [], ['id' => 'course_id']);

if (api_get_configuration_value('ticket_lp_quiz_info_add')) {
    $form->addSelect('exercise_id', get_lang('Exercise'), [], ['id' => 'exercise_id']);

    $form->addSelect('lp_id', get_lang('LearningPath'), [], ['id' => 'lp_id']);
}

$courseInfo = api_get_course_info();
$params = [];

if (!empty($courseInfo)) {
    $params = [
        'course_id' => $courseInfo['real_id'],
    ];

    $sessionInfo = api_get_session_info(api_get_session_id());

    if (!empty($sessionInfo)) {
        $params['session_id'] = $sessionInfo['id'];
    }

    if (api_get_configuration_value('ticket_lp_quiz_info_add')) {
        if ($exerciseId !== '') {
            $params['exercise_id'] = $exerciseId;
        }

        if ($lpId !== '') {
            $params['lp_id'] = $lpId;
        }
    }
}

$form->setDefaults($params);

$form->addElement('file', 'attach_1', get_lang('FilesAttachment'));
$form->addLabel('', '<span id="filepaths"><div id="filepath_1"></div></span>');

$form->addLabel(
    '',
    '<span id="link-more-attach">
         <span class="btn btn-success" onclick="return add_image_form()">'.get_lang('AddOneMoreFile').'</span>
         </span>
         ('.sprintf(get_lang('MaximunFileSizeX'), format_file_size(api_get_setting('message_max_upload_filesize'))).')
    '
);

$form->addElement('html', '<br/>');
$form->addElement(
    'button',
    'compose',
    get_lang('SendMessage'),
    null,
    null,
    null,
    'btn btn-primary',
    [
        'id' => 'btnsubmit',
    ]
);

$form->addRule('content', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('category_id', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('subject', get_lang('ThisFieldIsRequired'), 'required');

if ($form->validate()) {
    save_ticket();
}

Display::display_header(get_lang('ComposeMessage'));

echo '<div class="actions">';
echo Display::url(
    Display::return_icon('back.png', get_lang('Tickets'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_CODE_PATH).'ticket/tickets.php'
);
echo '</div>';

$form->display();
Display::display_footer();
