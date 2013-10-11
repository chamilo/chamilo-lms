<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.calendar
 */
/**
 * INIT SECTION
 */
// name of the language file that needs to be included
$language_file = array('agenda', 'group', 'announcements');

require_once '../inc/global.inc.php';
require_once 'agenda.lib.php';
require_once 'agenda.inc.php';

$interbreadcrumb[] = array("url" => api_get_path(WEB_CODE_PATH)."calendar/agenda_js.php?".api_get_cidreq(), "name" => get_lang('Agenda'));

$tpl = new Template(get_lang('Events'));

$agenda = new Agenda();
$agenda->type = 'course'; //course,admin or personal

$events = $agenda->get_events(null, null, api_get_course_int_id(), api_get_group_id(), null, 'array');
$url = api_get_path(WEB_CODE_PATH).'calendar/agenda_list.php?'.api_get_cidreq();
$tpl->assign('url', $url);
$tpl->assign('agenda_events', $events);
$actions = '<a href="'.api_get_path(WEB_CODE_PATH).'calendar/agenda_js.php?type=course&'.api_get_cidreq().'">'.
            Display::return_icon('calendar.png', get_lang('Agenda'), '', ICON_SIZE_MEDIUM)."</a>";
$tpl->assign('actions', $actions);
$tpl->assign('is_allowed_to_edit', api_is_allowed_to_edit());

if (api_is_allowed_to_edit()) {
    if (isset($_GET['action']) && $_GET['action'] == 'change_visibility') {
        $courseInfo = api_get_course_info();
        $agenda->changeVisibility($_GET['id'], $_GET['visibility'], $courseInfo);
        header('Location: '.$url);
        exit;
    }
}

// Loading Agenda template
$content = $tpl->fetch('default/agenda/event_list.tpl');

$tpl->assign('content', $content);

// Loading main Chamilo 1 col template
$tpl->display_one_col_template();
