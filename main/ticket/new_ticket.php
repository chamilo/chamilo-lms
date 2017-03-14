<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.plugin.ticket
 */

require_once __DIR__.'/../inc/global.inc.php';

if (!api_is_platform_admin() && api_get_setting('ticket_allow_student_add') != 'true') {
    header('location:' . api_get_path(WEB_CODE_PATH).'ticket/tickets.php');
    exit;
}

api_block_anonymous_users();
$courseId = api_get_course_int_id();

$htmlHeadXtra[] = '<script>

function updateCourseList(sessionId) {    
    $selectCourse = $("select#course_id");
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
            $("select#course_id option[value=\''.$courseId.'\']").attr("selected",true);
            $("select#course_id").selectpicker("refresh");
        }        
    }, "json");    
}

$(document).on("ready", function () {    
    $("select#session_id").on("change", function () {        
        var sessionId = parseInt(this.value, 10);
        updateCourseList(sessionId);
    });    
            
    var sessionId = $("select#session_id").val();
    updateCourseList(sessionId);
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
        src: "' . Display::returnIconPath('delete.png') . '"
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
</script>
';
$projectId = isset($_GET['project_id']) ? (int) $_GET['project_id'] : '';

$types = TicketManager::get_all_tickets_categories($projectId, 'category.name ASC');
$htmlHeadXtra[] = '<script language="javascript">
    var projects = ' . js_array($types, 'projects', 'project_id') . '
    var course_required = ' . js_array($types, 'course_required', 'course_required') . '
    var other_area = ' . js_array($types, 'other_area', 'other_area') . '
    var email = ' . js_array($types, 'email', 'email') .
'</script>';

/**
 * @param $s
 * @return string
 */
function js_str($s)
{
    return '"' . addcslashes($s, "\0..\37\"\\") . '"';
}

/**
 * @param $array
 * @param $name
 * @param $key
 * @return string
 */
function js_array($array, $name, $key)
{
    $return = "new Array(); ";
    foreach ($array as $value) {
        $return .= $name . "['" . $value['category_id'] . "'] ='" . $value[$key] . "'; ";
    }

    return $return;
}

/**
 *
 */
function save_ticket()
{
    $content = $_POST['content'];
    if ($_POST['phone'] != '') {
        $content .= '<p style="color:red">&nbsp;' . get_lang('Phone') . ': ' . $_POST['phone']. '</p>';
    }
    $course_id = isset($_POST['course_id']) ? $_POST['course_id'] : '';
    $sessionId = isset($_POST['session_id']) ? $_POST['session_id'] : '';
    $category_id = isset($_POST['category_id']) ? $_POST['category_id'] : '';

    $project_id = $_POST['project_id'];
    $subject = $_POST['subject'];
    $other_area = (int) $_POST['other_area'];
    $personal_email = $_POST['personal_email'];
    $source = $_POST['source_id'];
    $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : 0;
    $priority = isset($_POST['priority_id']) ? $_POST['priority_id'] : '';
    $status = isset($_POST['status_id']) ? $_POST['status_id'] : '';
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
        $user_id
    )) {
        header('Location:' . api_get_path(WEB_CODE_PATH).'ticket/tickets.php');
        exit;
    } else {
        Display::addFlash(Display::return_message(get_lang('ThereWasAnErrorRegisteringTheTicket')));
    }
}

$interbreadcrumb[] = array(
    'url' => api_get_path(WEB_CODE_PATH).'ticket/tickets.php',
    'name' => get_lang('MyTickets')
);

$userId = api_get_user_id();

// Category List
$categoryList = array();
foreach ($types as $type) {
    $categoryList[$type['category_id']] = $type['name'].': '.$type['description'];
}

// Status List
$statusAttributes = array(
    'style' => 'display: none;',
    'id' => 'status_id',
    'for' => 'status_id'
);

$statusList = TicketManager::getStatusList();

// Source List
$sourceList = array();
$sourceAttributes = array(
    'style' => 'display: none;',
    'id' => 'source_id',
    'for' => 'source_id'
);
$sourceList[TicketManager::SOURCE_PLATFORM] = get_lang('SrcPlatform');
if (api_is_platform_admin()) {
    $sourceAttributes = array(
        'id' => 'source_id',
        'for' => 'source_id'
    );
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
    array(
        'enctype' => 'multipart/form-data',
    )
);

$form->addElement(
    'hidden',
    'user_id_request',
    '',
    array(
        'id' => 'user_id_request'
    )
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
    array(
        'id' => 'other_area'
    )
);

$form->addElement(
    'hidden',
    'email',
    '',
    array(
        'id' => 'email'
    )
);

$form->addSelect(
    'category_id',
    get_lang('Category'),
    $categoryList,
    array(
        'id' => 'category_id',
        'for' => 'category_id',
        'style' => 'width: 562px;'
    )
);

$form->addElement(
    'text',
    'subject',
    get_lang('Subject'),
    array(
        'id' => 'subject'
    )
);

$form->addHtmlEditor(
    'content',
    get_lang('Message'),
    false,
    false,
    array(
        'ToolbarSet' => 'Profile',
        'Height' => '250'
    )
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
    array(
        'id' => 'personal_email'
    )
);

$form->addLabel(
    '',
    Display::div(
        '',
        array(
            'id' => 'user_request'
        )
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
    array(
        'id' => 'priority_id',
        'for' => 'priority_id'
    )
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
    get_lang('Phone') . ' (' . get_lang('Optional') . ')',
    array(
        'id' => 'phone'
    )
);

$sessionList = SessionManager::get_sessions_by_user($userId);
$sessionListToSelect = array(get_lang('Select'));
//Course List
foreach ($sessionList as $sessionInfo) {
    $sessionListToSelect[$sessionInfo['session_id']] = $sessionInfo['session_name'];
}

$form->addSelect('session_id', get_lang('Session'), $sessionListToSelect, ['id' => 'session_id']);
$form->addSelect('course_id', get_lang('Course'), [], ['id' => 'course_id']);

$courseInfo = api_get_course_info();
$params = [];

if (!empty($courseInfo)) {
    $params = [
        'course_id' => $courseInfo['real_id']
    ];

    $sessionInfo = api_get_session_info(api_get_session_id());
    if (!empty($sessionInfo)) {
        $params['session_id'] = $sessionInfo['id'];
    }
}

$form->setDefaults($params);

$form->addElement('file', 'attach_1', get_lang('FilesAttachment'));
$form->addLabel('', '<span id="filepaths"><div id="filepath_1"></div></span>');

$form->addLabel(
    '',
    '<span id="link-more-attach">
         <span class="btn btn-success" onclick="return add_image_form()">' . get_lang('AddOneMoreFile') . '</span>
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
    array(
        'id' => 'btnsubmit'
    )
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
    api_get_path(WEB_CODE_PATH) . 'ticket/tickets.php'
);
echo '</div>';

$form->display();
Display::display_footer();
