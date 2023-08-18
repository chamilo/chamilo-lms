<?php

/* For licensing terms, see /license.txt */

// use anonymous mode when accessing this course tool
use Chamilo\CoreBundle\Entity\AgendaEventSubscription;

$use_anonymous = true;
$typeList = ['personal', 'course', 'admin', 'platform'];
// Calendar type
$type = isset($_REQUEST['type']) && in_array($_REQUEST['type'], $typeList) ? $_REQUEST['type'] : 'personal';
$userId = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : null;

if ('personal' == $type || 'admin' == $type) {
    $cidReset = true; // fixes #5162
}
require_once __DIR__.'/../inc/global.inc.php';
api_block_inactive_user();

$current_course_tool = TOOL_CALENDAR_EVENT;
$this_section = SECTION_MYAGENDA;

$htmlHeadXtra[] = api_get_jquery_libraries_js(['jquery-ui', 'jquery-ui-i18n']);
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
    $is_group_tutor = GroupManager::is_tutor_of_group(
        api_get_user_id(),
        $group_properties,
        $courseId
    );
    $interbreadcrumb[] = [
        "url" => api_get_path(WEB_CODE_PATH)."group/group.php?".api_get_cidreq(),
        "name" => get_lang('Groups'),
    ];
    $interbreadcrumb[] = [
        "url" => api_get_path(WEB_CODE_PATH)."group/group_space.php?".api_get_cidreq(),
        "name" => get_lang('GroupSpace').' '.$group_properties['name'],
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
        $googleCalendarUrl = Agenda::returnGoogleCalendarUrl(api_get_user_id());

        if (!empty($googleCalendarUrl)) {
            $tpl->assign('use_google_calendar', 1);
            $tpl->assign('google_calendar_url', $googleCalendarUrl);
        }
        $this_section = SECTION_MYAGENDA;
        if (!api_is_anonymous() && ('true' === api_get_setting('allow_personal_agenda'))) {
            $can_add_events = 1;
        }
        break;
}

$tpl->assign('js_format_date', 'll');
$region_value = api_get_language_isocode();

if ('en' == $region_value) {
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
    api_get_configuration_value('agenda_colors') ?: []
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
$form->addHeader(get_lang('Events'));

$form->addHtml('<span id="calendar_course_info"></span><div id="visible_to_input">');

$sendTo = $agenda->parseAgendaFilter($userId);
$addOnlyItemsInSendTo = true;

if ($sendTo['everyone']) {
    $addOnlyItemsInSendTo = false;
}

$agenda->showToForm($form, $sendTo, [], $addOnlyItemsInSendTo);
$form->addHtml('</div>');

$form->addHtml('<div id="visible_to_read_only" style="display: none">');
$form->addElement('label', get_lang('To'), '<p id="visible_to_read_only_users" class="form-control-static"></p>');
$form->addHtml('</div>');

$form->addElement('label', get_lang('Agenda'), '<p class="form-control-static"><span id ="color_calendar"></span></p>');
$form->addElement(
    'label',
    get_lang('Date'), '<p class="form-control-static"><span id="start_date"></span><span id="end_date"></span></p>'
);
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
    $form->addElement('checkbox', 'add_as_annonuncement', null, get_lang('AddAsAnnouncement'));
    $form->addHtml('</div>');
    $form->addElement('textarea', 'comment', get_lang('Comment'), ['id' => 'comment']);
}

$allowCollectiveInvitations = api_get_configuration_value('agenda_collective_invitations')
    && 'personal' === $agenda->type;
$allowEventSubscriptions = api_is_platform_admin()
    && api_get_configuration_value('agenda_event_subscriptions')
    && 'personal' === $agenda->type;

if ($allowCollectiveInvitations && $allowEventSubscriptions) {
    $form->addRadio(
        'invitation_type',
        get_lang('Allowed'),
        [
            'invitations' => get_lang('Invitations'),
            'subscriptions' => get_lang('Subscriptions'),
        ],
        [
            'onchange' => "$('#invitations-block, #subscriptions-block').hide(); $('#' + this.value + '-block').show();",
        ]
    );
}

if ($allowCollectiveInvitations) {
    $form->addHtml(
        '<div id="invitations-block" style="display:'.($allowEventSubscriptions ? 'none;' : 'block;').'">'
    );
    $form->addHeader(get_lang('Invitations'));
    $form->addSelectAjax(
        'invitees',
        get_lang('Invitees'),
        [],
        [
            'multiple' => 'multiple',
            'url' => api_get_path(WEB_AJAX_PATH).'message.ajax.php?a=find_users',
        ]
    );
    $form->addCheckBox('collective', '', get_lang('IsItEditableByTheInvitees'));
    $form->addHtml('</div>');
}

if ($allowEventSubscriptions) {
    $form->addHtml(
        '<div id="subscriptions-block" style="display:'.($allowCollectiveInvitations ? 'none;' : 'block;').'">'
    );
    $form->addHeader(get_lang('Subscriptions'));
    $form->addHtml('<div id="form_subscriptions_container" style="position: relative;">');
    $form->addSelect(
        'subscription_visibility',
        get_lang('AllowSubscriptions'),
        [
            AgendaEventSubscription::SUBSCRIPTION_NO => get_lang('No'),
            AgendaEventSubscription::SUBSCRIPTION_ALL => get_lang('AllUsersOfThePlatform'),
            AgendaEventSubscription::SUBSCRIPTION_CLASS => get_lang('UsersInsideClass'),
        ],
        [
            'onchange' => 'document.getElementById(\'max_subscriptions\').disabled = this.value == 0; document.getElementById(\'form_subscription_item\').disabled = this.value != 2',
        ]
    );
    $form->addSelectAjax(
        'subscription_item',
        get_lang('SocialGroup').' / '.get_lang('Class'),
        [],
        [
            'url' => api_get_path(WEB_AJAX_PATH).'usergroup.ajax.php?a=get_class_by_keyword',
            'disabled' => 'disabled',
            'dropdownParent' => '#form_subscriptions_container',
        ]
    );
    $form->addNumeric(
        'max_subscriptions',
        ['', get_lang('MaxSubscriptionsLeaveEmptyToNotLimit')],
        [
            'disabled' => 'disabled',
            'step' => 1,
            'min' => 0,
            'value' => 0,
        ]
    );
    $form->addHtml('</div>');
    $form->addHtml('<div id="form_subscriptions_edit" style="display: none;"></div>');
    $form->addHtml('</div>');
}

if (api_get_configuration_value('agenda_reminders')) {
    $tpl->assign(
        'agenda_reminders_js',
        Agenda::getJsForReminders('#form_add_notification')
    );

    $form->addHtml('<hr><div id="notification_list"></div>');
    $form->addButton('add_notification', get_lang('AddNotification'), 'bell-o')->setType('button');
    $form->addHtml('<hr>');
}

if (api_get_configuration_value('allow_careers_in_global_agenda') && 'admin' === $agenda->type) {
    Career::addCareerFieldsToForm($form);
    $form->addHtml('<hr>');
}

$form->addHtml('<div id="attachment_block" style="display: none">');
$form->addHeader(get_lang('FilesAttachment'));
$form->addLabel(get_lang('Attachment'), '<div id="attachment_text" style="display: none"></div>');
$form->addHtml('</div>');

$tpl->assign('form_add', $form->returnForm());
$tpl->assign('legend_list', api_get_configuration_value('agenda_legend'));

$onHoverInfo = Agenda::returnOnHoverInfo();
$tpl->assign('on_hover_info', $onHoverInfo);

$extraSettings = Agenda::returnFullCalendarExtraSettings();

$tpl->assign('fullcalendar_settings', $extraSettings);

$tpl->assign('group_id', (!empty($group_id) ? $group_id : 0));

$templateName = $tpl->get_template('agenda/month.tpl');
$content = $tpl->fetch($templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
