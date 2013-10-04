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

$tpl = new Template(get_lang('Agenda'));
$agenda = new Agenda();
$agenda->type = 'course'; //course,admin or personal

$events = $agenda->get_events(null, null, api_get_course_int_id(), api_get_group_id(), null, 'array');
$tpl->assign('agenda_events', $events);

// Loading Agenda template
$content = $tpl->fetch('default/agenda/event_list.tpl');

$tpl->assign('content', $content);

// Loading main Chamilo 1 col template
$tpl->display_one_col_template();
