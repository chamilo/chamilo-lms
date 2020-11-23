<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This script is the Tickets plugin main entry point.
 */
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$tool_name = get_lang('Ticket');

$webLibPath = api_get_path(WEB_LIBRARY_PATH);
$htmlHeadXtra[] = '<script>
function load_history_ticket(div_course, ticket_id) {
    $.ajax({
        contentType: "application/x-www-form-urlencoded",
        beforeSend: function(object) {
        $("div#"+div_course).html("<img src=\''.$webLibPath.'javascript/indicator.gif\' />"); },
        type: "POST",
        url: "ticket_assign_log.php",
        data: "ticket_id="+ticket_id,
        success: function(data) {
            $("div#div_"+ticket_id).html(data);
            $("div#div_"+ticket_id).attr("class","blackboard_show");
            $("div#div_"+ticket_id).attr("style","");
        }
    });
}
function clear_course_list(div_course) {
    $("div#"+div_course).html("&nbsp;");
    $("div#"+div_course).hide("");
}

$(function() {
    $("#advanced_search_form").css("display","none");
});

function display_advanced_search_form () {
    if ($("#advanced_search_form").css("display") == "none") {
        $("#advanced_search_form").css("display","block");
        $("#img_plus_and_minus").html(\'&nbsp;'.Display::returnFontAwesomeIcon('arrow-down').' '.get_lang('AdvancedSearch').'\');
    } else {
        $("#advanced_search_form").css("display","none");
        $("#img_plus_and_minus").html(\'&nbsp;'.Display::returnFontAwesomeIcon('arrow-right').' '.get_lang('AdvancedSearch').'\');
    }
}
</script>';

$this_section = 'tickets';
Session::erase('this_section');

$action = isset($_GET['action']) ? $_GET['action'] : '';
$projectId = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;

$table = new SortableTable(
    'Tickets',
    ['TicketManager', 'getTotalTicketsCurrentUser'],
    ['TicketManager', 'getTicketsByCurrentUser'],
    2,
    20,
    'DESC'
);

$table->set_additional_parameters(['project_id' => $projectId]);

if ($table->per_page == 0) {
    $table->per_page = 20;
}

switch ($action) {
    case 'alert':
        if (!$isAdmin && isset($_GET['ticket_id'])) {
            TicketManager::send_alert($_GET['ticket_id'], $user_id);
        }
        break;
    case 'export':
        $data = [
            [
                '#',
                get_lang('Date'),
                get_lang('LastUpdate'),
                get_lang('Category'),
                get_lang('User'),
                get_lang('Program'),
                get_lang('AssignedTo'),
                get_lang('Status'),
                get_lang('Description'),
            ],
        ];
        $datos = $table->get_clean_html();
        foreach ($datos as $ticket) {
            $ticket[0] = substr(strip_tags($ticket[0]), 0, 12);
            $ticket_rem = [
                utf8_decode(strip_tags($ticket[0])),
                utf8_decode(api_html_entity_decode($ticket[1])),
                utf8_decode(strip_tags($ticket[2])),
                utf8_decode(strip_tags($ticket[3])),
                utf8_decode(strip_tags($ticket[4])),
                utf8_decode(strip_tags($ticket[5])),
                utf8_decode(strip_tags($ticket[6])),
                utf8_decode(strip_tags($ticket[7])),
            ];
            $data[] = $ticket_rem;
        }
        Export::arrayToXls($data, get_lang('Tickets'));
        exit;
        break;
    case 'close_tickets':
        TicketManager::close_old_tickets();
        break;
    default:
        break;
}

if (empty($projectId)) {
    $projects = TicketManager::getProjectsSimple();
    if (!empty($projects) && isset($projects[0])) {
        $project = $projects[0];
        header('Location: '.api_get_self().'?project_id='.$project['id']);
        exit;
    }
}

$currentUrl = api_get_self().'?project_id='.$projectId;
$user_id = api_get_user_id();
$isAllow = TicketManager::userIsAllowInProject(api_get_user_info(), $projectId);
$isAdmin = api_is_platform_admin();
$actionRight = '';

Display::display_header(get_lang('MyTickets'));

if (!empty($projectId)) {
    $getParameters = [];
    if ($isAdmin) {
        $getParameters = [
            'keyword',
            'keyword_status',
            'keyword_category',
            'keyword_assigned_to',
            'keyword_start_date',
            'keyword_unread',
            'Tickets_per_page',
            'Tickets_column',
        ];
    }
    $get_parameter = '';
    foreach ($getParameters as $getParameter) {
        if (isset($_GET[$getParameter])) {
            $get_parameter .= "&$getParameter=".Security::remove_XSS($_GET[$getParameter]);
        }
    }

    $getParameters = [
        'Tickets_per_page',
        'Tickets_column',
    ];
    $get_parameter2 = '';
    foreach ($getParameters as $getParameter) {
        if (isset($_GET[$getParameter])) {
            $get_parameter2 .= "&$getParameter=".Security::remove_XSS($_GET[$getParameter]);
        }
    }

    if (isset($_GET['submit_advanced'])) {
        $get_parameter .= "&submit_advanced=";
    }
    if (isset($_GET['submit_simple'])) {
        $get_parameter .= "&submit_simple=";
    }

    // Select categories
    $selectTypes = [];
    $types = TicketManager::get_all_tickets_categories($projectId);
    foreach ($types as $type) {
        $selectTypes[$type['category_id']] = $type['name'];
    }

    $admins = UserManager::getUserListLike(
        ['status' => '1'],
        ['username'],
        true
    );
    $selectAdmins = [
        0 => get_lang('Unassigned'),
    ];
    foreach ($admins as $admin) {
        $selectAdmins[$admin['user_id']] = $admin['complete_name_with_username'];
    }
    $status = TicketManager::get_all_tickets_status();
    $selectStatus = [];
    foreach ($status as $stat) {
        $selectStatus[$stat['id']] = $stat['name'];
    }

    $selectPriority = TicketManager::getPriorityList();
    $selectStatusUnread = [
        '' => get_lang('StatusAll'),
        'yes' => get_lang('StatusUnread'),
        'no' => get_lang('StatusRead'),
    ];

    // Create a search-box
    $form = new FormValidator(
        'search_simple',
        'get',
        $currentUrl,
        null,
        null,
        'inline'
    );
    $form->addText('keyword', get_lang('Keyword'), false);
    $form->addButtonSearch(get_lang('Search'), 'submit_simple');
    $form->addHidden('project_id', $projectId);

    $advancedSearch = Display::url(
        '<span id="img_plus_and_minus">&nbsp;'.
        Display::returnFontAwesomeIcon('arrow-right').' '.get_lang('AdvancedSearch'),
        'javascript://',
        [
            'class' => 'btn btn-default advanced-parameters',
            'onclick' => 'display_advanced_search_form();',
        ]
    );

    // Add link
    if (api_get_setting('ticket_allow_student_add') == 'true' || api_is_platform_admin()) {
        $extraParams = '';

        if (isset($_GET['exerciseId']) && !empty($_GET['exerciseId'])) {
            $extraParams .= '&exerciseId='.(int) $_GET['exerciseId'];
        }

        if (isset($_GET['lpId']) && !empty($_GET['lpId'])) {
            $extraParams .= '&lpId='.(int) $_GET['lpId'];
        }

        $actionRight = Display::url(
            Display::return_icon(
                'add.png',
                get_lang('Add'),
                null,
                ICON_SIZE_MEDIUM
            ),
            api_get_path(WEB_CODE_PATH).'ticket/new_ticket.php?project_id='.$projectId.'&'.api_get_cidReq().$extraParams,
            ['title' => get_lang('Add')]
        );
    }

    if (api_is_platform_admin()) {
        $actionRight .= Display::url(
            Display::return_icon(
                'export_excel.png',
                get_lang('Export'),
                null,
                ICON_SIZE_MEDIUM
            ),
            api_get_self().'?action=export'.$get_parameter.$get_parameter2.'&project_id='.$projectId,
            ['title' => get_lang('Export')]
        );

        $actionRight .= Display::url(
            Display::return_icon(
                'settings.png',
                get_lang('Settings'),
                null,
                ICON_SIZE_MEDIUM
            ),
            api_get_path(WEB_CODE_PATH).'ticket/settings.php',
            ['title' => get_lang('Settings')]
        );
    }

    echo Display::toolbarAction(
        'toolbar-tickets',
        [
            $form->returnForm(),
            $advancedSearch,
            $actionRight,
        ]
    );

    $ticketLabel = get_lang('AllTickets');
    $url = api_get_path(WEB_CODE_PATH).'ticket/tickets.php?project_id='.$projectId;

    if (!isset($_GET['keyword_assigned_to'])) {
        $ticketLabel = get_lang('MyTickets');
        $url = api_get_path(WEB_CODE_PATH).'ticket/tickets.php?project_id='.$projectId.'&keyword_assigned_to='.api_get_user_id();
    }

    $options = '';
    $iconProject = Display::return_icon(
        'project.png',
        get_lang('Projects'),
        null,
        ICON_SIZE_MEDIUM
        );
    if ($isAdmin) {
        $options .= Display::url(
            $iconProject,
            api_get_path(WEB_CODE_PATH).'ticket/projects.php'
        );
    }
    $iconTicket = Display::return_icon(
        'tickets.png',
        $ticketLabel,
        null,
        ICON_SIZE_MEDIUM
        );
    $options .= Display::url(
        $iconTicket,
        $url
    );

    if ($isAllow) {
        echo Display::toolbarAction(
            'toolbar-options',
            [
                $options,
            ]
        );
    }

    $advancedSearchForm = new FormValidator(
        'advanced_search',
        'get',
        $currentUrl,
        null,
        ['style' => 'display:"none"', 'id' => 'advanced_search_form']
    );

    $advancedSearchForm->addHidden('project_id', $projectId);
    $advancedSearchForm->addHeader(get_lang('AdvancedSearch'));
    $advancedSearchForm->addSelect(
        'keyword_category',
        get_lang('Category'),
        $selectTypes,
        ['placeholder' => get_lang('Select')]
    );
    $advancedSearchForm->addDateTimePicker('keyword_start_date_start', get_lang('Created'));
    $advancedSearchForm->addDateTimePicker('keyword_start_date_end', get_lang('Until'));
    $advancedSearchForm->addSelect(
        'keyword_assigned_to',
        get_lang('AssignedTo'),
        $selectAdmins,
        ['placeholder' => get_lang('All')]
    );
    $advancedSearchForm->addSelect(
        'keyword_status',
        get_lang('Status'),
        $selectStatus,
        ['placeholder' => get_lang('Select')]
    );
    $advancedSearchForm->addSelect(
        'keyword_priority',
        get_lang('Priority'),
        $selectPriority,
        ['placeholder' => get_lang('All')]
    );
    $advancedSearchForm->addText('keyword_course', get_lang('Course'), false);
    $advancedSearchForm->addButtonSearch(get_lang('AdvancedSearch'), 'submit_advanced');
    $advancedSearchForm->display();
} else {
    if (api_get_setting('ticket_allow_student_add') === 'true') {
        echo '<div class="actions">';
        echo '<a href="'.api_get_path(WEB_CODE_PATH).'ticket/new_ticket.php?project_id='.$projectId.'">'.
                Display::return_icon('add.png', get_lang('Add'), '', '32').
             '</a>';
        echo '</div>';
    }
}

if ($isAdmin) {
    $table->set_header(0, '#', true);
    $table->set_header(1, get_lang('Status'), true);
    $table->set_header(2, get_lang('Date'), true);
    $table->set_header(3, get_lang('LastUpdate'), true);
    $table->set_header(4, get_lang('Category'), true);
    $table->set_header(5, get_lang('CreatedBy'), true);
    $table->set_header(6, get_lang('AssignedTo'), true);
    $table->set_header(7, get_lang('Message'), true);
} else {
    if ($isAllow == false) {
        echo Display::page_subheader(get_lang('MyTickets'));
        echo Display::return_message(get_lang('TicketMsgWelcome'));
    }
    $table->set_header(0, '#', true);
    $table->set_header(1, get_lang('Status'), false);
    $table->set_header(2, get_lang('Date'), true);
    $table->set_header(3, get_lang('LastUpdate'), true);
    $table->set_header(4, get_lang('Category'));
}

$table->display();
Display::display_footer();
