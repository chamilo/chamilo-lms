<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : 'calendar_list';

$logInfo = [
    'tool' => TOOL_CALENDAR_EVENT,
    'action' => $action,
];
Event::registerLog($logInfo);

$typeList = ['personal', 'course', 'admin', 'platform'];
$type = isset($_REQUEST['type']) && in_array($_REQUEST['type'], $typeList, true) ? $_REQUEST['type'] : null;

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'calendar/agenda_js.php?type='.Security::remove_XSS($type),
    'name' => get_lang('Agenda'),
];

$currentCourseId = api_get_course_int_id();
$groupId = api_get_group_id();

if (!empty($groupId)) {
    $groupProperties = GroupManager::get_group_properties($groupId);
    $groupId = $groupProperties['iid'];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH)."group/group.php?".api_get_cidreq(),
        'name' => get_lang('Groups'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH)."group/group_space.php?".api_get_cidreq(),
        'name' => get_lang('GroupSpace').' '.$groupProperties['name'],
    ];
}

$agenda = new Agenda($type);
$events = $agenda->getEvents(
    null,
    null,
    $currentCourseId,
    $groupId,
    null,
    'array'
);

$this_section = SECTION_MYAGENDA;

if (!empty($currentCourseId) && $currentCourseId != -1) {
    // Agenda is inside a course tool
    $url = api_get_self().'?'.api_get_cidreq();
    $this_section = SECTION_COURSES;

    // Order by start date
    usort($events, function ($a, $b) {
        $t1 = strtotime($a['start']);
        $t2 = strtotime($b['start']);

        return $t1 > $t2;
    });
} else {
    // Agenda is out of the course tool (e.g personal agenda)

    // Little hack to sort the events by start date in personal agenda (Agenda events List view - See #8014)
    usort($events, function ($a, $b) {
        $t1 = strtotime($a['start']);
        $t2 = strtotime($b['start']);

        return $t1 - $t2;
    });

    $url = false;
    if (!empty($events)) {
        foreach ($events as &$event) {
            $courseId = isset($event['course_id']) ? $event['course_id'] : '';
            $event['url'] = api_get_self().'?cid='.$courseId.'&type='.$event['type'];
        }
    }
}

$actions = $agenda->displayActions('list');

$tpl = new Template(get_lang('Events'));
$tpl->assign('agenda_events', $events);
$tpl->assign('url', $url);
$tpl->assign('show_action', in_array($type, ['course', 'session']));
$tpl->assign('agenda_actions', $actions);
$tpl->assign('is_allowed_to_edit', api_is_allowed_to_edit());

if (api_is_allowed_to_edit()) {
    if ($action == 'change_visibility') {
        $courseInfo = api_get_course_info();
        $courseCondition = '';
        // This happens when list agenda is not inside a course
        if (($type == 'course' || $type == 'session' && !empty($courseInfo))) {
            // For course and session event types
            // Just needs course ID
            $agenda->changeVisibility($_GET['id'], $_GET['visibility'], $courseInfo);
        } else {
            $courseCondition = '&'.api_get_cidreq();
        }
        header('Location: '.api_get_self().'?type='.$agenda->type.$courseCondition);
        exit;
    }
}

$templateName = $tpl->get_template('agenda/event_list.tpl');
$content = $tpl->fetch($templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
