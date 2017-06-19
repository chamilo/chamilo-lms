<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.calendar
 */

// use anonymous mode when accessing this course tool
$use_anonymous = true;
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_CALENDAR_EVENT;
$course_info = api_get_course_info();

if (!empty($course_info)) {
    api_protect_course_script(true);
}

$action = isset($_GET['action']) ? $_GET['action'] : null;

$this_section = SECTION_COURSES;
$url = null;
if (empty($action)) {
    if (!empty($course_info)) {
        $url = api_get_path(WEB_CODE_PATH).'calendar/agenda_js.php?type=course'.'&'.api_get_cidreq();
    } else {
        $url = api_get_path(WEB_CODE_PATH).'calendar/agenda_js.php?';
    }
    header("Location: $url");
    exit;
}

$group_id = api_get_group_id();
$groupInfo = GroupManager::get_group_properties($group_id);
$eventId = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
$type = $event_type = isset($_GET['type']) ? $_GET['type'] : null;

$htmlHeadXtra[] = "<script>
function plus_repeated_event() {
    if (document.getElementById('options2').style.display == 'none') {
        document.getElementById('options2').style.display = 'block';
    } else {
        document.getElementById('options2').style.display = 'none';
    }
}
    $(function() {
        var checked = $('input[name=repeat]').attr('checked');
        if (checked) {
            $('#options2').show();
        }
    });
</script>";


$htmlHeadXtra[] = '<script>
var counter_image = 1;
function add_image_form() {
	// Multiple filepaths for image form
	var filepaths = document.getElementById("filepaths");
	if (document.getElementById("filepath_"+counter_image)) {
		counter_image = counter_image + 1;
	}  else {
		counter_image = counter_image;
	}
	var elem1 = document.createElement("div");
	elem1.setAttribute("id","filepath_"+counter_image);
	filepaths.appendChild(elem1);
	id_elem1 = "filepath_"+counter_image;
	id_elem1 = "\'"+id_elem1+"\'";
	document.getElementById("filepath_"+counter_image).innerHTML = "<input type=\"file\" name=\"attach_"+counter_image+"\" />&nbsp; <br />'.get_lang('Description').'&nbsp;&nbsp;<input type=\"text\" name=\"legend[]\"  /><br /><br />";
	if (filepaths.childNodes.length == 6) {
		var link_attach = document.getElementById("link-more-attach");
		if (link_attach) {
			link_attach.innerHTML="";
		}
	}
}
</script>';

// setting the name of the tool
$nameTools = get_lang('Agenda');

Event::event_access_tool(TOOL_CALENDAR_EVENT);

if ($type === 'fromjs') {
    $id_list = explode('_', $eventId);
    $eventId = $id_list[1];
    $event_type = $id_list[0];
    $event_type = $event_type === 'platform' ? 'admin' : $event_type;
}

$agenda = new Agenda($event_type);
$allowToEdit = $agenda->getIsAllowedToEdit();
$actions = $agenda->displayActions('calendar');

if (!$allowToEdit && $event_type === 'course') {
    api_not_allowed(true);
}

if ($event_type === 'course') {
    $agendaUrl = api_get_path(WEB_CODE_PATH).'calendar/agenda_js.php?'.api_get_cidreq().'&type=course';
} else {
    $agendaUrl = api_get_path(WEB_CODE_PATH).'calendar/agenda_js.php?&type='.$event_type;
}
$course_info = api_get_course_info();

$content = null;
if ($allowToEdit) {
    switch ($action) {
        case 'add':
            $actionName = get_lang('Add');
            $form = $agenda->getForm(array('action' => 'add'));

            if ($form->validate()) {
                $values = $form->getSubmitValues();

                $sendEmail = isset($values['add_announcement']) ? true : false;
                $allDay = isset($values['all_day']) ? 'true' : 'false';

                $sendAttachment = isset($_FILES) && !empty($_FILES) ? true : false;
                $attachmentList = $sendAttachment ? $_FILES : null;
                $attachmentCommentList = isset($values['legend']) ? $values['legend'] : null;
                $comment = isset($values['comment']) ? $values['comment'] : null;
                $usersToSend = isset($values['users_to_send']) ? $values['users_to_send'] : '';

                $startDate = $values['date_range_start'];
                $endDate = $values['date_range_end'];

                $eventId = $agenda->addEvent(
                    $startDate,
                    $endDate,
                    $allDay,
                    $values['title'],
                    $values['content'],
                    $usersToSend,
                    $sendEmail,
                    null,
                    $attachmentList,
                    $attachmentCommentList,
                    $comment
                );

                if (!empty($values['repeat']) && !empty($eventId)) {
                    // End date is always set as 23:59:59
                    $endDate = substr($values['repeat_end_day'], 0, 10).' 23:59:59';
                    $agenda->addRepeatedItem(
                        $eventId,
                        $values['repeat_type'],
                        $endDate,
                        $values['users_to_send']
                    );
                }
                $message = Display::return_message(get_lang('AddSuccess'), 'confirmation');
                if ($sendEmail) {
                    $message .= Display::return_message(
                        get_lang('AdditionalMailWasSentToSelectedUsers'),
                        'confirmation'
                    );
                }
                Display::addFlash($message);
                header("Location: $agendaUrl");
                exit;
            } else {
                $content = $form->return_form();
            }
            break;
        case 'edit':
            $actionName = get_lang('Edit');
            $event = $agenda->get_event($eventId);

            if (empty($event)) {
                api_not_allowed(true);
            }

            $event['action'] = 'edit';
            $event['id'] = $eventId;

            $form = $agenda->getForm($event);

            if ($form->validate()) {
                $values = $form->getSubmitValues();

                $allDay = isset($values['all_day']) ? 'true' : 'false';
                $sendEmail = isset($values['add_announcement']) ? true : false;
                $startDate = $values['date_range_start'];
                $endDate = $values['date_range_end'];

                $sendAttachment = isset($_FILES) && !empty($_FILES) ? true : false;
                $attachmentList = $sendAttachment ? $_FILES : null;
                $attachmentCommentList = isset($values['legend']) ? $values['legend'] : null;

                $comment = isset($values['comment']) ? $values['comment'] : null;

                // This is a sub event. Delete the current and create another BT#7803

                if (!empty($event['parent_event_id'])) {
                    $agenda->deleteEvent($eventId);

                    $eventId = $agenda->addEvent(
                        $startDate,
                        $endDate,
                        $allDay,
                        $values['title'],
                        $values['content'],
                        $values['users_to_send'],
                        false,
                        null,
                        $attachmentList,
                        $attachmentCommentList,
                        $comment
                    );

                    $message = Display::return_message(get_lang('Updated'), 'confirmation');
                    Display::addFlash($message);
                    header("Location: $agendaUrl");
                    exit;
                }

                $usersToSend = isset($values['users_to_send']) ? $values['users_to_send'] : '';

                // Editing normal event.
                $agenda->editEvent(
                    $eventId,
                    $startDate,
                    $endDate,
                    $allDay,
                    $values['title'],
                    $values['content'],
                    $usersToSend,
                    $attachmentList,
                    $attachmentCommentList,
                    $comment,
                    '',
                    $sendEmail
                );

                if (!empty($values['repeat']) && !empty($eventId)) {
                    // End date is always set as 23:59:59
                    $endDate = substr($values['repeat_end_day'], 0, 10).' 23:59:59';
                    $agenda->addRepeatedItem(
                        $eventId,
                        $values['repeat_type'],
                        $endDate,
                        $values['users_to_send']
                    );
                }

                $deleteAttachmentList = isset($values['delete_attachment']) ? $values['delete_attachment'] : array();

                if (!empty($deleteAttachmentList)) {
                    foreach ($deleteAttachmentList as $deleteAttachmentId => $value) {
                        $agenda->deleteAttachmentFile(
                            $deleteAttachmentId,
                            $agenda->course
                        );
                    }
                }

                $message = Display::return_message(get_lang('Updated'), 'confirmation');
                Display::addFlash($message);
                header("Location: $agendaUrl");
                exit;
            } else {
                $content = $form->returnForm();
            }
            break;
        case 'importical':
            $actionName = get_lang('Import');
            $form = $agenda->getImportCalendarForm();
            if ($form->validate()) {
                $ical_name = $_FILES['ical_import']['name'];
                $ical_type = $_FILES['ical_import']['type'];
                $ext = substr($ical_name, (strrpos($ical_name, ".") + 1));

                if (in_array($ext, ['ics', 'ical', 'icalendar', 'ifb'])) {
                    $content = $agenda->importEventFile($course_info, $_FILES['ical_import']);
                    $message = Display::return_message(get_lang('AddSuccess'));
                } else {
                    $message = Display::return_message(get_lang('IsNotiCalFormatFile'), 'error');
                }
                Display::addFlash($message);
                $url = api_get_self().'?action=importical&type='.$agenda->type;
                header("Location: $url");
                exit;
            }
            $content = $form->returnForm();
            break;
        case "delete":
            if (!(api_is_session_general_coach() && !api_is_element_in_the_session(TOOL_AGENDA, $eventId))) {
                // a coach can only delete an element belonging to his session
                $content = $agenda->deleteEvent($eventId);
            }
            break;
    }
}

if (!empty($group_id)) {
    $group_properties = GroupManager :: get_group_properties($group_id);
    $interbreadcrumb[] = array(
        "url" => api_get_path(WEB_CODE_PATH)."group/group.php?".api_get_cidreq(),
        "name" => get_lang('Groups')
    );
    $interbreadcrumb[] = array(
        "url" => api_get_path(WEB_CODE_PATH)."group/group_space.php?".api_get_cidreq(),
        "name" => get_lang('GroupSpace').' '.$group_properties['name']
    );
}
if (!empty($actionName)) {
    $interbreadcrumb[] = array(
        "url" => $url,
        "name" => get_lang('Agenda')
    );
}

// Tool introduction
$introduction = Display::return_introduction_section(TOOL_CALENDAR_EVENT);

$tpl = new Template($actionName);
$tpl->assign('content', $content);
$tpl->assign('actions', $actions);

// Loading main Chamilo 1 col template
$tpl->display_one_col_template();
