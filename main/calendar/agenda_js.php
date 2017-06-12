<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.calendar
 */

// use anonymous mode when accessing this course tool
$use_anonymous = true;
$typeList = array('personal', 'course', 'admin', 'platform');
// Calendar type
$type = isset($_REQUEST['type']) && in_array($_REQUEST['type'], $typeList) ? $_REQUEST['type'] : 'personal';
$userId = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : null;

if ($type == 'personal' || $type == 'admin') {
    $cidReset = true; // fixes #5162
}
require_once __DIR__.'/../inc/global.inc.php';

$current_course_tool = TOOL_CALENDAR_EVENT;
$this_section = SECTION_MYAGENDA;

$htmlHeadXtra[] = api_get_jquery_libraries_js(array('jquery-ui', 'jquery-ui-i18n'));
$htmlHeadXtra[] = api_get_asset('qtip2/jquery.qtip.min.js');
$htmlHeadXtra[] = api_get_asset('fullcalendar/dist/fullcalendar.js');
$htmlHeadXtra[] = api_get_asset('fullcalendar/dist/locale-all.js');
$htmlHeadXtra[] = api_get_asset('fullcalendar/dist/gcal.js');
$htmlHeadXtra[] = api_get_css_asset('fullcalendar/dist/fullcalendar.min.css');
$htmlHeadXtra[] = api_get_css_asset('qtip2/jquery.qtip.min.css');

if (api_is_platform_admin() && ($type == 'admin' || $type == 'platform')) {
    $type = 'admin';
}

if (isset($_REQUEST['cidReq']) && !empty($_REQUEST['cidReq'])) {
    if ($_REQUEST['cidReq'] == -1) {
        // When is out of the course tool (e.g My agenda)
        header('Location: '.api_get_self());
        exit;
    } else {
        $type = 'course';
        $this_section = SECTION_COURSES;
    }
}

api_protect_course_group(GroupManager::GROUP_TOOL_CALENDAR);

$agenda = new Agenda($type);

$is_group_tutor = false;
$session_id = api_get_session_id();
$group_id = api_get_group_id();
$courseId = api_get_course_int_id();

if (!empty($group_id)) {
    $group_properties = GroupManager::get_group_properties($group_id);
    $is_group_tutor = GroupManager::is_tutor_of_group(api_get_user_id(), $group_properties);
    $interbreadcrumb[] = array(
        "url" => api_get_path(WEB_CODE_PATH)."group/group.php?".api_get_cidreq(),
        "name" => get_lang('Groups')
    );
    $interbreadcrumb[] = array(
        "url" => api_get_path(WEB_CODE_PATH)."group/group_space.php?".api_get_cidreq(),
        "name" => get_lang('GroupSpace').' '.$group_properties['name']
    );
}

$tpl = new Template(get_lang('Agenda'));
$tpl->assign('use_google_calendar', 0);

$can_add_events = 0;

switch ($type) {
    case 'admin':
        api_protect_admin_script();
        $this_section = SECTION_PLATFORM_ADMIN;
        if (api_is_platform_admin()) {
            $can_add_events = 1;
        }
        break;
    case 'course':
        api_protect_course_script(true);
        $allowToEdit = $agenda->getIsAllowedToEdit();
        $this_section = SECTION_COURSES;
        if ($allowToEdit) {
            $can_add_events = 1;
        }
        break;
    case 'personal':
        if (api_is_anonymous()) {
            api_not_allowed(true);
        }
        $extra_field_data = UserManager::get_extra_user_data_by_field(
            api_get_user_id(),
            'google_calendar_url'
        );
        if (!empty($extra_field_data) &&
            isset($extra_field_data['google_calendar_url']) &&
            !empty($extra_field_data['google_calendar_url'])
        ) {
            $tpl->assign('use_google_calendar', 1);
            $tpl->assign('google_calendar_url', $extra_field_data['google_calendar_url']);
        }
        $this_section = SECTION_MYAGENDA;
        if (!api_is_anonymous()) {
            $can_add_events = 1;
        }
        break;
}

$tpl->assign('js_format_date', 'll');
$region_value = api_get_language_isocode();

if ($region_value == 'en') {
    $region_value = 'en-GB';
}
$tpl->assign('region_value', $region_value);

$export_icon = Display::return_icon(
    'export.png',
    null,
    null,
    null,
    null,
    true,
    false
);
$export_icon_low = Display::return_icon(
    'export_low_fade.png',
    null,
    null,
    null,
    null,
    true,
    false
);
$export_icon_high = Display::return_icon(
    'export_high_fade.png',
    null,
    null,
    null,
    null,
    true,
    false
);

$tpl->assign(
    'export_ical_confidential_icon',
    Display::return_icon($export_icon_high, get_lang('ExportiCalConfidential'))
);

$actions = $agenda->displayActions('calendar', $userId);

$tpl->assign('toolbar', $actions);

// Calendar Type : course, admin, personal
$tpl->assign('type', $type);

$type_event_class = $type.'_event';
$type_label = get_lang(ucfirst($type).'Calendar');
if ($type == 'course' && !empty($group_id)) {
    $type_event_class = 'group_event';
    $type_label = get_lang('GroupCalendar');
}

$defaultView = api_get_setting('default_calendar_view');

if (empty($defaultView)) {
    $defaultView = 'month';
}

/* month, basicWeek, agendaWeek, agendaDay */
$tpl->assign('default_view', $defaultView);

if ($type == 'course' && !empty($session_id)) {
    $type_event_class = 'session_event';
    $type_label = get_lang('SessionCalendar');
}

$tpl->assign('type_label', $type_label);
$tpl->assign('type_event_class', $type_event_class);

// Current user can add event?
$tpl->assign('can_add_events', $can_add_events);

// Setting AJAX caller
if (!empty($userId)) {
    $agenda_ajax_url = api_get_path(WEB_AJAX_PATH).'agenda.ajax.php?user_id='.$userId.'&type='.$type;
} else {
    $agenda_ajax_url = api_get_path(WEB_AJAX_PATH).'agenda.ajax.php?type='.$type;
}

if ($type == 'course' && !empty($courseId)) {
    $agenda_ajax_url .= '&'.api_get_cidreq();
}

if (isset($_GET['session_id'])) {
    $agenda_ajax_url .= '&session_id='.intval($_GET['session_id']);
}

$tpl->assign('web_agenda_ajax_url', $agenda_ajax_url);

$form = new FormValidator(
    'form',
    'get',
    api_get_self().'?'.api_get_cidreq(),
    null,
    array('id' => 'add_event_form')
);

$form->addHtml('<span id="calendar_course_info"></span><div id="visible_to_input">');

$sendTo = $agenda->parseAgendaFilter($userId);
$addOnlyItemsInSendTo = true;

if ($sendTo['everyone']) {
    $addOnlyItemsInSendTo = false;
}

$agenda->showToForm($form, $sendTo, array(), $addOnlyItemsInSendTo);
$form->addHtml('</div>');

$form->addHtml('<div id="visible_to_read_only" style="display: none">');
$form->addElement('label', get_lang('To'), '<div id="visible_to_read_only_users"></div>');
$form->addHtml('</div>');

$form->addElement('label', get_lang('Agenda'), '<div id ="color_calendar"></div>');
$form->addElement('label', get_lang('Date'), '<span id="start_date"></span><span id="end_date"></span>');
$form->addElement('text', 'title', get_lang('Title'), array('id' => 'title'));
$form->addHtmlEditor(
    'content',
    get_lang('Description'),
    false,
    false,
    [
        'ToolbarSet' => 'TestProposedAnswer',
        'Height' => '120'
    ]
);

if ($agenda->type === 'course') {
    $form->addHtml('<div id="add_as_announcement_div" style="display: none">');
    $form->addElement('checkbox', 'add_as_annonuncement', null, get_lang('AddAsAnnouncement'));
    $form->addHtml('</div>');
    $form->addElement('textarea', 'comment', get_lang('Comment'), array('id' => 'comment'));
}

$tpl->assign('form_add', $form->returnForm());
$tpl->assign('legend_list', api_get_configuration_value('agenda_legend'));
$templateName = $tpl->get_template('agenda/month.tpl');
$content = $tpl->fetch($templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
