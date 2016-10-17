<?php
/* For licensing terms, see /license.txt */

/**
 * This script is the Tickets plugin main entry point
 * @package chamilo.plugin.ticket
 */

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$tool_name = get_lang('LastEdit');

$libPath = api_get_path(LIBRARY_PATH);
$webLibPath = api_get_path(WEB_LIBRARY_PATH);
$htmlHeadXtra[] = '<script>
function load_history_ticket(div_course, ticket_id) {
    $.ajax({
        contentType: "application/x-www-form-urlencoded",
        beforeSend: function(object) {
        $("div#"+div_course).html("<img src=\'' . $webLibPath . 'javascript/indicator.gif\' />"); },
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

$(document).ready(function() {
    $("#advanced_search_form").css("display","none");
});

function display_advanced_search_form() {
    if ($("#advanced_search_form").css("display") == "none") {
        $("#advanced_search_form").css("display","block");
        $("#img_plus_and_minus").html(\'&nbsp;' . Display::return_icon('div_hide.gif', get_lang('Hide'), array('style' => 'vertical-align:middle')) . '&nbsp;' . get_lang('AdvancedSearch') . '\');
    } else {
        $("#advanced_search_form").css("display","none");
        $("#img_plus_and_minus").html(\'&nbsp;' . Display::return_icon('div_show.gif', get_lang('Show'), array('style' => 'vertical-align:middle')) . '&nbsp;' . get_lang('AdvancedSearch') . '\');
    }
}
</script>';

$this_section = 'tickets';
unset($_SESSION['this_section']);

$table = new SortableTable(
    'Tickets',
    array('TicketManager', 'get_total_tickets_by_user_id'),
    array('TicketManager', 'get_tickets_by_user_id'),
    2,
    20,
    'DESC'
);

if ($table->per_page == 0) {
    $table->per_page = 20;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$projectId = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;

switch ($action) {
    case 'assign':
        if ($isAdmin && isset($_GET['ticket_id'])) {
            TicketManager::assign_ticket_user($_GET['ticket_id'], $user_id);
        }
        break;
    case 'unassign':
        if ($isAdmin && isset($_GET['ticket_id'])) {
            TicketManager::assign_ticket_user($_GET['ticket_id'], 0);
        }
        break;
    case 'alert':
        if (!$isAdmin && isset($_GET['ticket_id'])) {
            TicketManager::send_alert($_GET['ticket_id'], $user_id);
        }
        break;
    case 'export':
        $data = array(
            array(
                '#',
                get_lang('Date'),
                get_lang('LastUpdate'),
                get_lang('Category'),
                get_lang('User'),
                get_lang('Program'),
                get_lang('AssignedTo'),
                get_lang('Status'),
                get_lang('Description')
            )
        );
        $datos = $table->get_clean_html();
        foreach ($datos as $ticket) {
            $ticket[0] = substr(strip_tags($ticket[0]), 0, 12);
            $ticket_rem = array(
                utf8_decode(strip_tags($ticket[0])),
                utf8_decode(api_html_entity_decode($ticket[1])),
                utf8_decode(strip_tags($ticket[2])),
                utf8_decode(strip_tags($ticket[3])),
                utf8_decode(strip_tags($ticket[4])),
                utf8_decode(strip_tags($ticket[5])),
                utf8_decode(strip_tags($ticket[6])),
                utf8_decode(strip_tags($ticket[7]))
            );
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

$user_id = api_get_user_id();
$isAdmin = api_is_platform_admin();

Display::display_header(get_lang('MyTickets'));
if (!empty($projectId))
if ($isAdmin) {
    $getParameters = [
        'keyword',
        'keyword_status',
        'keyword_category',
        'keyword_request_user',
        'keyword_admin',
        'keyword_start_date',
        'keyword_unread',
        'Tickets_per_page',
        'Tickets_column'
    ];
    $get_parameter = '';
    foreach ($getParameters as $getParameter) {
        if (isset($_GET[$getParameter])) {
            $get_parameter .= "&$getParameter=".Security::remove_XSS($_GET[$getParameter]);
        }
    }

    $getParameters = [
        'Tickets_per_page',
        'Tickets_column'
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

    $admins = UserManager::get_user_list_like(array('status' => '1'), array('username'), true);
    $selectAdmins = [
        0 => get_lang('Unassigned')
    ];
    foreach ($admins as $admin) {
        $selectAdmins[$admin['user_id']] = $admin['complete_name'];
    }
    $status = TicketManager::get_all_tickets_status();
    $selectStatus = [];
    foreach ($status as $stat) {
        $selectStatus[$stat['id']] = $stat['name'];
    }

    $selectPriority = [
        '' => get_lang('All'),
        TicketManager::PRIORITY_NORMAL => get_lang('PriorityNormal'),
        TicketManager::PRIORITY_HIGH => get_lang('PriorityHigh'),
        TicketManager::PRIORITY_LOW => get_lang('PriorityLow')
    ];

    $selectStatusUnread = [
        '' => get_lang('StatusAll'),
        'yes' => get_lang('StatusUnread'),
        'no' => get_lang('StatusRead')
    ];

    // Create a search-box
    $form = new FormValidator('search_simple', 'get', '', '', array(), FormValidator::LAYOUT_INLINE);
    $form->addText('keyword', get_lang('Keyword'), 'size="25"');
    $form->addButtonSearch(get_lang('Search'), 'submit_simple');
    $form->addElement(
        'static',
        'search_advanced_link',
        null,
        '<a href="javascript://" class = "advanced-parameters" onclick="display_advanced_search_form();">'
        . '<span id="img_plus_and_minus">&nbsp;'
        . Display::return_icon('div_show.gif', get_lang('Show')) . ' '
        . get_lang('AdvancedSearch') . '</span></a>'
    );

    echo '<div class="actions" >';
    if (api_is_platform_admin()) {
        echo '<span class="left">' .
                '<a href="' . api_get_path(WEB_CODE_PATH) . 'ticket/new_ticket.php?project_id='.$projectId.'">' .
                    Display::return_icon('add.png', get_lang('Add'), '', ICON_SIZE_MEDIUM) . '</a>' .
                '<a href="' . api_get_self() . '?action=export' . $get_parameter . $get_parameter2 . '&project_id='.$projectId.'">' .
                    Display::return_icon('export_excel.png', get_lang('Export'), '', ICON_SIZE_MEDIUM) . '</a>';

        echo Display::url(
            Display::return_icon('settings.png', get_lang('Categories')),
            api_get_path(WEB_CODE_PATH) . 'ticket/settings.php'
        );
        echo '</span>';
    }
    $form->display();
    echo '</div>';

    $advancedSearchForm = new FormValidator(
        'advanced_search',
        'get',
        api_get_self().'?project_id='.$projectId,
        null,
        ['style' => 'display:"none"', 'id' => 'advanced_search_form']
    );
    $advancedSearchForm->addHeader(get_lang('AdvancedSearch'));
    $advancedSearchForm->addSelect(
        'keyword_category',
        get_lang('Category'),
        $selectTypes,
        ['placeholder' => get_lang('Select')]
    );
    //$advancedSearchForm->addText('keyword_request_user', get_lang('User'), false);
    $advancedSearchForm->addDateTimePicker('keyword_start_date_start', get_lang('Created'));
    $advancedSearchForm->addDateTimePicker('keyword_start_date_end', get_lang('Until'));
    $advancedSearchForm->addSelect(
        'keyword_admin',
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
    $advancedSearchForm->addSelect(
        'keyword_unread',
        get_lang('Status'),
        $selectStatusUnread,
        ['placeholder' => get_lang('All')]
    );
    $advancedSearchForm->addText('keyword_course', get_lang('Course'), false);
    $advancedSearchForm->addButtonSearch(get_lang('AdvancedSearch'), 'submit_advanced');
    $advancedSearchForm->display();
} else {
    if (api_get_setting('ticket_allow_student_add') === 'true') {
        echo '<div class="actions" >';
        echo '<a href="' . api_get_path(WEB_CODE_PATH) . 'ticket/new_ticket.php?project_id='.$projectId.'">' .
                Display::return_icon('add.png', get_lang('Add'), '', '32') .
             '</a>';
        echo '</div>';
    }
}

if (empty($projectId)) {
    $table = new SortableTable(
        'TicketProject',
        array('TicketManager', 'getProjectsCount'),
        array('TicketManager', 'getProjectsSimple'),
        1
    );

    $table->set_header(0, '', false);
    $table->set_header(1, get_lang('Title'), false);
    $table->set_header(2, get_lang('Description'), true, array("style" => "width:200px"));

    $table->display();
    Display::display_footer();
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
    echo '<center><h1>' . get_lang('MyTickets') . '</h1></center>';
    echo '<center><p>' . get_lang('TicketMsgWelcome') . '</p></center>';
    $table->set_header(0, '#', true);
    $table->set_header(1, get_lang('Status'), false);
    $table->set_header(2, get_lang('Date'), true);
    $table->set_header(3, get_lang('LastUpdate'), true);
    $table->set_header(4, get_lang('Category'));
}

$table->display();
Display::display_footer();
