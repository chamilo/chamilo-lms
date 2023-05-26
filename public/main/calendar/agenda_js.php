<?php

/* For licensing terms, see /license.txt */

// use anonymous mode when accessing this course tool
$use_anonymous = true;
$typeList = ['personal', 'course', 'admin', 'platform'];
// Calendar type
$type = isset($_REQUEST['type']) && in_array($_REQUEST['type'], $typeList) ? $_REQUEST['type'] : 'personal';
$userId = $_REQUEST['user_id'] ?? null;

if ('personal' === $type || 'admin' === $type) {
    $cidReset = true; // fixes #5162
}
require_once __DIR__.'/../inc/global.inc.php';
api_block_inactive_user();

$current_course_tool = TOOL_CALENDAR_EVENT;
$this_section = SECTION_MYAGENDA;

$htmlHeadXtra[] = api_get_css_asset('fullcalendar/main.css');
$htmlHeadXtra[] = api_get_asset('fullcalendar/main.js');

if (api_is_platform_admin() && ('admin' === $type || 'platform' === $type)) {
    $type = 'admin';
}

if (isset($_REQUEST['cidReq']) && !empty($_REQUEST['cidReq'])) {
    if (-1 == $_REQUEST['cidReq']) {
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

$session_id = api_get_session_id();
$group_id = api_get_group_id();
$courseId = api_get_course_int_id();

if (!empty($group_id)) {
    $group_properties = GroupManager::get_group_properties($group_id);
    $interbreadcrumb[] = [
        "url" => api_get_path(WEB_CODE_PATH)."group/group.php?".api_get_cidreq(),
        "name" => get_lang('Groups'),
    ];
    $interbreadcrumb[] = [
        "url" => api_get_path(WEB_CODE_PATH)."group/group_space.php?".api_get_cidreq(),
        "name" => get_lang('Group area').' '.$group_properties['name'],
    ];
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

if ('en' === $region_value) {
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
    Display::return_icon($export_icon_high, get_lang('Export in iCal format as confidential event'))
);

$actions = $agenda->displayActions('calendar', $userId);

$tpl->assign('actions', $actions);

// Calendar Type : course, admin, personal
$tpl->assign('type', $type);

$type_event_class = $type.'_event';
$type_label = get_lang(ucfirst($type).'Calendar');
if ('course' === $type && !empty($group_id)) {
    $type_event_class = 'group_event';
    $type_label = get_lang('Agenda');
}

$defaultView = api_get_setting('default_calendar_view');

if (empty($defaultView)) {
    $defaultView = 'dayGridMonth';
}

if ('month' === $defaultView) {
    $defaultView = 'dayGridMonth';
}

/* month, basicWeek, agendaWeek, agendaDay */
$tpl->assign('default_view', $defaultView);

if ('course' === $type && !empty($session_id)) {
    $type_event_class = 'session_event';
    $type_label = get_lang('Session calendar');
}

$agendaColors = array_merge(
    [
        'platform' => 'red', //red
        'course' => '#458B00', //green
        'group' => '#A0522D', //siena
        'session' => '#00496D', // kind of green
        'other_session' => '#999', // kind of green
        'personal' => 'steel blue', //steel blue
        'student_publication' => '#FF8C00', //DarkOrange
    ],
    api_get_setting('agenda.agenda_colors', true) ?: []
);

switch ($type_event_class) {
    case 'admin_event':
        $tpl->assign('type_event_color', $agendaColors['platform']);
        break;
    case 'course_event':
        $tpl->assign('type_event_color', $agendaColors['course']);
        break;
    case 'group_event':
        $tpl->assign('type_event_color', $agendaColors['group']);
        break;
    case 'session_event':
        $tpl->assign('type_event_color', $agendaColors['session']);
        break;
    case 'personal_event':
        $tpl->assign('type_event_color', $agendaColors['personal']);
        break;
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

if ('course' === $type && !empty($courseId)) {
    $agenda_ajax_url .= '&'.api_get_cidreq();
}

if (isset($_GET['session_id'])) {
    $agenda_ajax_url .= '&session_id='.intval($_GET['session_id']);
}

$agenda_ajax_url .= '&sec_token='.Security::get_token();

$tpl->assign('web_agenda_ajax_url', $agenda_ajax_url);

$form = new FormValidator(
    'form',
    'get',
    api_get_self().'?'.api_get_cidreq(),
    null,
    ['id' => 'add_event_form']
);

$form->addHtml('<span id="calendar_course_info"></span><div id="visible_to_input">');

$sendTo = $agenda->parseAgendaFilter($userId);
$addOnlyItemsInSendTo = true;

if ($sendTo['everyone']) {
    $addOnlyItemsInSendTo = false;
}

$agenda->showToForm($form, $sendTo, [], $addOnlyItemsInSendTo);
$form->addHtml('</div>');

$form->addHtml('<div id="visible_to_read_only" style="display: none">');
$form->addElement('label', get_lang('To'), '<div id="visible_to_read_only_users"></div>');
$form->addHtml('</div>');

$form->addElement('label', get_lang('Agenda'), '<div id ="color_calendar"></div>');
$form->addElement('label', get_lang('Date'), '<span id="start_date"></span><span id="end_date"></span>');
$form->addElement('text', 'title', get_lang('Title'), ['id' => 'title']);
$form->addHtmlEditor(
    'content',
    get_lang('Description'),
    false,
    false,
    [
        'ToolbarSet' => 'TestProposedAnswer',
        'Height' => '120',
        'id' => 'content',
    ]
);

if ('course' === $agenda->type) {
    $form->addHtml('<div id="add_as_announcement_div" style="display: none">');
    $form->addElement('checkbox', 'add_as_annonuncement', null, get_lang('Add as an announcement'));
    $form->addHtml('</div>');
    $form->addElement('textarea', 'comment', get_lang('Comment'), ['id' => 'comment']);
}

$form->addHtml('<div id="attachment_block" style="display: none">');
$form->addLabel(get_lang('Attachment'), '<div id="attachment_text" style="display: none"></div>');
$form->addHtml('</div>');

$tpl->assign('form_add', $form->returnForm());
$tpl->assign('legend_list', api_get_setting('agenda.agenda_legend', true));

$onHoverInfo = api_get_setting('agenda.agenda_on_hover_info', true);
if (!empty($onHoverInfo)) {
    $options = $onHoverInfo['options'];
} else {
    $options = [
        'comment' => true,
        'description' => true,
    ];
}
$tpl->assign('on_hover_info', $options);

$templateName = $tpl->get_template('agenda/month.tpl');
$content = $tpl->fetch($templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
