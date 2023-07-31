<?php

/* For licensing terms, see /license.txt */

// use anonymous mode when accessing this course tool
$use_anonymous = true;
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_CALENDAR_EVENT;
$course_info = api_get_course_info();

if (!empty($course_info)) {
    api_protect_course_script(true);
}

$action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : null;

$group_id = api_get_group_id();

$url = null;
if (empty($action)) {
    if (!empty($course_info)) {
        if (!empty($group_id)) {
            $url = api_get_path(WEB_CODE_PATH).'calendar/agenda_js.php?type=course&'.api_get_cidreq().'&user_id=GROUP:'.$group_id;
        } else {
            $url = api_get_path(WEB_CODE_PATH).'calendar/agenda_js.php?type=course&'.api_get_cidreq();
        }
    } else {
        $url = api_get_path(WEB_CODE_PATH).'calendar/agenda_js.php?';
    }
    header("Location: $url");
    exit;
}

$logInfo = [
    'tool' => TOOL_CALENDAR_EVENT,
    'action' => $action,
];
Event::registerLog($logInfo);

$groupInfo = GroupManager::get_group_properties($group_id);
$eventId = $_REQUEST['id'] ?? null;
$type = $event_type = $_GET['type'] ?? null;
$messageId = (int) ($_REQUEST['m'] ?? 0);
$messageInfo = [];

$currentUserId = api_get_user_id();

if ($messageId) {
    $event_type = 'personal';

    $messageInfo = MessageManager::get_message_by_id($messageId);

    if (!in_array($currentUserId, [$messageInfo['user_receiver_id'], $messageInfo['user_sender_id']])) {
        api_not_allowed(true);
    }
}

$htmlHeadXtra[] = "<script>
function plus_repeated_event() {
    if (document.getElementById('options2').style.display === 'none') {
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
	document.getElementById("filepath_"+counter_image).innerHTML = "<input type=\"file\" name=\"attach_"+counter_image+"\" /><label>'.get_lang('Description').'</label><input class=\"form-control\" type=\"text\" name=\"legend[]\"  />";
	if (filepaths.childNodes.length == 6) {
		var link_attach = document.getElementById("link-more-attach");
		if (link_attach) {
			link_attach.innerHTML="";
		}
	}
}
</script>';

$agendaRemindersEnabled = api_get_configuration_value('agenda_reminders');

if ($agendaRemindersEnabled) {
    $htmlHeadXtra[] = '<script>$(function () {'
        .Agenda::getJsForReminders('#add_event_add_notification')
        .'});</script>'
    ;
}

// setting the name of the tool
$nameTools = get_lang('Agenda');

Event::event_access_tool(TOOL_CALENDAR_EVENT);

if ('fromjs' === $type) {
    // split the "id" parameter only if string and there are _ separators
    if (preg_match('/_/', $eventId)) {
        $id_list = explode('_', $eventId);
    } else {
        $id_list = $eventId;
    }
    $eventId = $id_list[1];
    $event_type = $id_list[0];
    $event_type = 'platform' === $event_type ? 'admin' : $event_type;
}

$agenda = new Agenda($event_type);
$allowToEdit = $agenda->getIsAllowedToEdit();
$actions = $agenda->displayActions('calendar');

if (!$allowToEdit && 'course' === $event_type) {
    api_not_allowed(true);
}

if ('course' === $event_type) {
    $agendaUrl = api_get_path(WEB_CODE_PATH).'calendar/agenda_js.php?'.api_get_cidreq().'&type=course';
} else {
    $agendaUrl = api_get_path(WEB_CODE_PATH).'calendar/agenda_js.php?&type='.$event_type;

    if ($messageInfo) {
        $agendaUrl = api_get_path(WEB_CODE_PATH).'messages/view_message.php?'
            .http_build_query(
                [
                    'type' => $messageInfo['msg_status'] === MESSAGE_STATUS_OUTBOX ? 2 : 1,
                    'id' => $messageInfo['id'],
                ]
            );
    }
}
$course_info = api_get_course_info();

$this_section = $course_info ? SECTION_COURSES : SECTION_MYAGENDA;

$em = Database::getManager();

$content = null;
if ($allowToEdit) {
    switch ($action) {
        case 'add':
            $actionName = get_lang('Add');
            $form = $agenda->getForm(['action' => 'add']);

            if ($messageInfo) {
                $form->addHidden('m', $messageInfo['id']);
            }

            if ($form->validate()) {
                $values = $form->getSubmitValues();

                $addAsAnnouncement = isset($values['add_announcement']);
                $allDay = isset($values['all_day']) ? 'true' : 'false';
                $sendAttachment = isset($_FILES) && !empty($_FILES);
                $attachmentList = $sendAttachment ? $_FILES : null;
                $attachmentCommentList = $values['legend'] ?? null;
                $comment = $values['comment'] ?? null;
                $usersToSend = $values['users_to_send'] ?? '';
                $startDate = $values['date_range_start'];
                $endDate = $values['date_range_end'];
                $notificationCount = $_REQUEST['notification_count'] ?? [];
                $notificationPeriod = $_REQUEST['notification_period'] ?? [];
                $careerId = $_REQUEST['career_id'] ?? 0;
                $promotionId = $_REQUEST['promotion_id'] ?? 0;
                $subscriptionVisibility = (int) ($_REQUEST['subscription_visibility'] ?? 0);
                $subscriptionItemId = isset($_REQUEST['subscription_item']) ? (int) $_REQUEST['subscription_item'] : null;
                $maxSubscriptions = (int) ($_REQUEST['max_subscriptions'] ?? 0);

                $reminders = $notificationCount ? array_map(null, $notificationCount, $notificationPeriod) : [];

                $eventId = $agenda->addEvent(
                    $startDate,
                    $endDate,
                    $allDay,
                    $values['title'],
                    $values['content'],
                    $usersToSend,
                    $addAsAnnouncement,
                    null,
                    $attachmentList,
                    $attachmentCommentList,
                    $comment,
                    '',
                    $values['invitees'] ?? [],
                    $values['collective'] ?? false,
                    $reminders,
                    (int) $careerId,
                    (int) $promotionId,
                    $subscriptionVisibility,
                    $subscriptionItemId,
                    $maxSubscriptions
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
                if ($addAsAnnouncement) {
                    $message .= Display::return_message(
                        get_lang('AdditionalMailWasSentToSelectedUsers'),
                        'confirmation'
                    );
                }
                Display::addFlash($message);
                header("Location: $agendaUrl");
                exit;
            } else {
                if (!empty($messageInfo)) {
                    MessageManager::setDefaultValuesInFormFromMessageInfo($messageInfo, $form);
                }

                $content = $form->returnForm();
            }
            break;
        case 'edit':
            $actionName = get_lang('Edit');
            $event = $agenda->get_event((int) $eventId);

            if (empty($event)) {
                api_not_allowed(true);
            }

            $event['action'] = 'edit';
            $event['id'] = $eventId;

            $form = $agenda->getForm($event);

            if ($form->validate()) {
                $values = $form->getSubmitValues();

                $allDay = isset($values['all_day']) ? 'true' : 'false';
                $addAsAnnouncement = isset($values['add_announcement']);
                $startDate = $values['date_range_start'];
                $endDate = $values['date_range_end'];

                $sendAttachment = isset($_FILES) && !empty($_FILES);
                $attachmentList = $sendAttachment ? $_FILES : [];
                $attachmentCommentList = $values['legend'] ?? '';
                $comment = $values['comment'] ?? '';
                $notificationCount = $_REQUEST['notification_count'] ?? [];
                $notificationPeriod = $_REQUEST['notification_period'] ?? [];
                $careerId = $_REQUEST['career_id'] ?? 0;
                $promotionId = $_REQUEST['promotion_id'] ?? 0;
                $subscriptionVisibility = (int) ($_REQUEST['subscription_visibility'] ?? 0);
                $subscriptionItemId = isset($_REQUEST['subscription_item']) ? (int) $_REQUEST['subscription_item'] : null;
                $maxSubscriptions = (int) ($_REQUEST['max_subscriptions'] ?? 0);
                $subscribers = $_REQUEST['subscribers'] ?? [];

                $reminders = $notificationCount ? array_map(null, $notificationCount, $notificationPeriod) : [];

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
                        $comment,
                        '',
                        $values['invitees'] ?? [],
                        $values['collective'] ?? false,
                        $reminders
                    );

                    $message = Display::return_message(get_lang('Updated'), 'confirmation');
                    Display::addFlash($message);
                    header("Location: $agendaUrl");
                    exit;
                }

                $usersToSend = $values['users_to_send'] ?? '';

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
                    $addAsAnnouncement,
                    true,
                    0,
                    $values['invitees'] ?? [],
                    $values['collective'] ?? false,
                    $reminders,
                    (int) $careerId,
                    (int) $promotionId,
                    $subscriptionVisibility,
                    $subscriptionItemId,
                    $maxSubscriptions,
                    $subscribers
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

                $deleteAttachmentList = $values['delete_attachment'] ?? [];

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
        case 'delete':
            if (!(api_is_session_general_coach() &&
                !api_is_element_in_the_session(TOOL_AGENDA, $eventId))
            ) {
                // a coach can only delete an element belonging to his session
                $content = $agenda->deleteEvent($eventId);
            }
            break;
        case 'import_course_agenda_reminders':
            if (!empty($course_info)) {
                header('Location: '.api_get_path(WEB_CODE_PATH)
                    .'admin/import_course_agenda_reminders.php?'.api_get_cidreq().'&type=course'
                );

                exit();
            }
    }
}

if (!empty($group_id)) {
    $group_properties = GroupManager::get_group_properties($group_id);
    $interbreadcrumb[] = [
        "url" => api_get_path(WEB_CODE_PATH)."group/group.php?".api_get_cidreq(),
        "name" => get_lang('Groups'),
    ];
    $interbreadcrumb[] = [
        "url" => api_get_path(WEB_CODE_PATH)."group/group_space.php?".api_get_cidreq(),
        "name" => get_lang('GroupSpace').' '.$group_properties['name'],
    ];
}
if (!empty($actionName)) {
    $interbreadcrumb[] = [
        "url" => $url,
        "name" => get_lang('Agenda'),
    ];
} else {
    $actionName = '';
}

// Tool introduction
$introduction = Display::return_introduction_section(TOOL_CALENDAR_EVENT);

$tpl = new Template($actionName);
$tpl->assign('content', $content);
$tpl->assign('actions', $actions);

$tpl->display_one_col_template();
