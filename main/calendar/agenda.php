<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.calendar
 */

/**
 * INIT SECTION
 */
use \ChamiloSession as Session;

// name of the language file that needs to be included
$language_file = array('agenda', 'group');

// use anonymous mode when accessing this course tool
$use_anonymous = true;
require_once '../inc/global.inc.php';
$current_course_tool = TOOL_CALENDAR_EVENT;
$course_info = api_get_course_info();

if (!empty($course_info)) {
    api_protect_course_script(true);
}

$action = isset($_GET['action']) ? $_GET['action'] : null;
$origin = isset($_GET['origin']) ? $_GET['origin'] : null;

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

/* 	Resource linker */
$_SESSION['source_type'] = 'Agenda';
require_once '../resourcelinker/resourcelinker.inc.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
$group_id = api_get_group_id();
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
</script>
";

// setting the name of the tool
$nameTools = get_lang('Agenda');

event_access_tool(TOOL_CALENDAR_EVENT);

// permission stuff - also used by loading from global in agenda.inc.php
$is_allowed_to_edit = api_is_allowed_to_edit(false, true) OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous());
$agenda = new Agenda();
$agenda->type = $type;
$actions = $agenda->displayActions('calendar');

if ($type == 'fromjs') {
    $id_list = explode('_', $eventId);
    $eventId = $id_list[1];
    $event_type = $id_list[0];
}

if (!api_is_allowed_to_edit(null, true) && $event_type == 'course') {
    api_not_allowed(true);
}
if ($event_type == 'course') {
    $agendaUrl = api_get_path(WEB_CODE_PATH).'calendar/agenda_js.php?'.api_get_cidreq().'&type=course';
} else {
    $agendaUrl = api_get_path(WEB_CODE_PATH).'calendar/agenda_js.php?&type='.$event_type;
}
$course_info = api_get_course_info();
$agenda->type = $event_type;

$message = null;
$content = null;

if (api_is_allowed_to_edit(false, true) OR
    (api_get_course_setting('allow_user_edit_agenda') &&
    !api_is_anonymous() &&
    api_is_allowed_to_session_edit(false, true)) OR
    GroupManager::user_has_access(api_get_user_id(), $group_id,  GroupManager::GROUP_TOOL_CALENDAR) &&
    GroupManager::is_tutor_of_group(api_get_user_id(), $group_id)
) {
    switch ($action) {
        case 'add':
            $actionName = get_lang('Add');
            $form = $agenda->getForm(array('action' => 'add'));

            if ($form->validate()) {
                $values = $form->getSubmitValues();

                $sendEmail = isset($values['add_announcement']) ? true : false;
                $allDay = isset($values['all_day']) ? 'true' : 'false';

                $sendAttachment = isset($_FILES['user_upload']) ? true : false;
                $attachment = $sendAttachment ? $_FILES['user_upload'] : null;
                $attachmentComment = isset($values['file_comment']) ? $values['file_comment'] : null;

                $startDate = $values['date_range_start'];
                $endDate = $values['date_range_end'];

                $eventId = $agenda->add_event(
                    $startDate,
                    $endDate,
                    $allDay,
                    $values['title'],
                    $values['content'],
                    $values['users_to_send'],
                    $sendEmail,
                    null,
                    $attachment,
                    $attachmentComment
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
                    $message .= Display::return_message(get_lang('AdditionalMailWasSentToSelectedUsers'), 'confirmation');
                }
                Session::write('message', $message);
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
                $startDate = $values['date_range_start'];
                $endDate = $values['date_range_end'];

                $sendAttachment = isset($_FILES['user_upload']) ? true : false;
                $attachment = $sendAttachment ? $_FILES['user_upload'] : null;
                $attachmentComment = isset($values['file_comment']) ? $values['file_comment'] : null;

                // This is a sub event. Delete the current and create another BT#7803

                if (!empty($event['parent_event_id'])) {
                    $agenda->delete_event($eventId);

                    $eventId = $agenda->add_event(
                        $startDate,
                        $endDate,
                        $allDay,
                        $values['title'],
                        $values['content'],
                        $values['users_to_send'],
                        false,
                        null,
                        $attachment,
                        $attachmentComment
                    );

                    $message = Display::return_message(get_lang('Updated'), 'confirmation');
                    Session::write('message', $message);
                    header("Location: $agendaUrl");
                    exit;
                }

                // Editing normal event.

                $agenda->edit_event(
                    $eventId,
                    $startDate,
                    $endDate,
                    $allDay,
                    $values['title'],
                    $values['content'],
                    $values['users_to_send'],
                    $attachment,
                    $attachmentComment
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

                $deleteAttachment = isset($values['delete_attachment']) ? true : false;

                if ($deleteAttachment && isset($event['attachment']) && !empty($event['attachment'])) {
                    $agenda->deleteAttachmentFile(
                        $event['attachment']['id'],
                        $agenda->course
                    );
                }

                $message = Display::return_message(get_lang('Updated'), 'confirmation');
                Session::write('message', $message);
                header("Location: $agendaUrl");
                exit;
            } else {
                $content = $form->return_form();

            }
            break;
        case 'importical':
            $form = $agenda->getImportCalendarForm();
            $content = $form->return_form();

            if ($form->validate()) {
                $ical_name = $_FILES['ical_import']['name'];
                $ical_type = $_FILES['ical_import']['type'];
                $ext = substr($ical_name, (strrpos($ical_name, ".") + 1));

                if ($ext === 'ics' || $ext === 'ical' || $ext === 'icalendar' || $ext === 'ifb') {
                    $result = $agenda->importEventFile($course_info, $_FILES['ical_import']);
                    $is_ical = true;
                } else {
                    $is_ical = false;
                }

                if (!$is_ical) {
                    $message = Display::return_message(get_lang('IsNotiCalFormatFile'), 'error');
                    $form = $agenda->getImportCalendarForm();
                    $content = $form->return_form();
                    break;
                } else {
                    $message = Display::return_message(get_lang('AddSuccess'), 'error');
                    $content = $result;
                }
                Session::write('message', $message);
            }
            break;
        case "delete":
            if (!(api_is_course_coach() && !api_is_element_in_the_session(TOOL_AGENDA, $eventId) )) {
                // a coach can only delete an element belonging to his session
                $content = $agenda->delete_event($eventId);
            }
            break;
    }
}

if (!empty($group_id)) {
    $group_properties = GroupManager :: get_group_properties($group_id);
    $interbreadcrumb[] = array(
        "url" => api_get_path(WEB_CODE_PATH)."group/group.php",
        "name" => get_lang('Groups')
    );
    $interbreadcrumb[] = array(
        "url" => api_get_path(WEB_CODE_PATH)."group/group_space.php?gidReq=".$group_id,
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

$message = Session::read('message');
Session::erase('message');

$tpl = new Template($actionName);
$tpl->assign('content', $content);
$tpl->assign('actions', $actions);

// Loading main Chamilo 1 col template
$tpl->display_one_col_template();
