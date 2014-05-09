<?php
/* For licensing terms, see /license.txt */

/**
 * This script is the Tickets plugin main entry point
 * @package chamilo.plugin.ticket
 */
/**
 * Initialization
 */
$language_file = array('messages', 'userInfo', 'admin');
$cidReset = true;
//needed in order to load the plugin lang variables
$course_plugin = 'ticket';
require_once '../config.php';

$plugin = TicketPlugin::create();
$tool_name = $plugin->get_lang('LastEdit');

api_block_anonymous_users();

$libPath = api_get_path(LIBRARY_PATH);
$webLibPath = api_get_path(WEB_LIBRARY_PATH);
require_once $libPath . 'formvalidator/FormValidator.class.php';
require_once $libPath . 'group_portal_manager.lib.php';
$htmlHeadXtra[] = '<script type="text/javascript">
function load_history_ticket (div_course,ticket_id) {
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
function clear_course_list (div_course) {
    $("div#"+div_course).html("&nbsp;");
    $("div#"+div_course).hide("");
}

$(function() {
    $( "#keyword_start_date_start" ).datepicker({ dateFormat: ' . "'dd/mm/yy'" . ' });
    $( "#keyword_start_date_end" ).datepicker({ dateFormat: ' . "'dd/mm/yy'" . ' });
});

$(document).ready(function() {
        $("#advanced_search_form").css("display","none");
});

function display_advanced_search_form () {
    if ($("#advanced_search_form").css("display") == "none") {
        $("#advanced_search_form").css("display","block");
        $("#img_plus_and_minus").html(\'&nbsp;' . Display::return_icon('div_hide.gif', get_lang('Hide'), array('style' => 'vertical-align:middle')) . '&nbsp;' . get_lang('AdvancedSearch') . '\');
    } else {
        $("#advanced_search_form").css("display","none");
        $("#img_plus_and_minus").html(\'&nbsp;' . Display::return_icon('div_show.gif', get_lang('Show'), array('style' => 'vertical-align:middle')) . '&nbsp;' . get_lang('AdvancedSearch') . '\');
    }
}
</script>
<style>
.label2 {
    float: left;
    text-align: left;
    width: 75px;
}

.label3 {
    margin-left: 20px;
    float: left;
    text-align: left;
    margin-top: 10px;
    width: 95px;
}

.label4 {
    float: left;
    text-align: left;
    margin-top: 10px;
    width: 75px;
}

.formw2 {
    float: left;
    margin-left: 4px;
    margin-top: 5px;
}

.blackboard_show {
    float: left;
    position: absolute;
    border: 1px solid black;
    width: 350px;
    background-color: white;
    z-index: 99;
    padding: 3px;
    display: inline;
}

.blackboard_hide {
    display: none;
}

.advanced-parameters {
    margin-top: 5px;
}

.remove-margin-top {
    margin-top: 0px;
}

.select-margin-top {
    margin-top: -5px;
}

.input-width {
    width: 170px;
}

.fleft {
    float: left;
}
</style>';

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

if (isset($_GET['action'])) {
    global $table;
    $action = $_GET['action'];
    switch ($action) {
        case 'assign':
            if ($isAdmin && isset($_GET['ticket_id']))
                TicketManager::assign_ticket_user($_GET['ticket_id'], $user_id);
            break;
        case 'unassign':
            if ($isAdmin && isset($_GET['ticket_id']))
                TicketManager::assign_ticket_user($_GET['ticket_id'], 0);
            break;
        case 'alert':
            if (!$isAdmin && isset($_GET['ticket_id']))
                TicketManager::send_alert($_GET['ticket_id'], $user_id);
            break;
        case 'export':
            $data = array(
                array(
                    $plugin->get_lang('TicketNum'),
                    $plugin->get_lang('Date'),
                    $plugin->get_lang('DateLastEdition'),
                    $plugin->get_lang('Category'),
                    $plugin->get_lang('User'),
                    $plugin->get_lang('Program'),
                    $plugin->get_lang('Responsible'),
                    $plugin->get_lang('Status'),
                    $plugin->get_lang('Description')
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
                    utf8_decode(strip_tags($ticket[7])),
                    utf8_decode(strip_tags(str_replace('&nbsp;', ' ', $ticket[9])))
                );
                $data[] = $ticket_rem;
            }
            Export::export_table_xls($data, $plugin->get_lang('Tickets'));
            exit;
            break;
        case 'close_tickets':
            TicketManager::close_old_tickets();
            break;
        default:
            break;
    }
}
//$nameTools = api_xml_http_response_encode($plugin->get_lang('MyTickets'));
$user_id = api_get_user_id();
$isAdmin = api_is_platform_admin();

Display::display_header($plugin->get_lang('MyTickets'));
if ($isAdmin) {
    $get_parameter = '&keyword=' . Security::remove_XSS($_GET['keyword']) . '&keyword_status=' . Security::remove_XSS($_GET['keyword_status']) . '&keyword_category=' .Security::remove_XSS($_GET['keyword_category']). '&keyword_request_user=' . Security::remove_XSS($_GET['keyword_request_user']);
    $get_parameter .= '&keyword_admin=' . Security::remove_XSS($_GET['keyword_admin']) . '&keyword_start_date=' . Security::remove_XSS($_GET['keyword_start_date']) . '&keyword_unread=' . Security::remove_XSS($_GET['keyword_unread']);
    $get_parameter2 = '&Tickets_per_page=' . Security::remove_XSS($_GET['Tickets_per_page']) . '&Tickets_column=' . Security::remove_XSS($_GET['Tickets_column']);
    if (isset($_GET['submit_advanced'])) {
        $get_parameter .= "&submit_advanced=";
    }
    if (isset($_GET['submit_simple'])) {
        $get_parameter .= "&submit_simple=";
    }
    //select categories
    $select_types .= '<select class="chzn-select" name = "keyword_category" id="keyword_category" ">';
    $select_types .= '<option value="">---' . get_lang('Select') . '---</option>';
    $types = TicketManager::get_all_tickets_categories();
    foreach ($types as $type) {
        $select_types.= "<option value = '" . $type['category_id'] . "'>" . $type['name'] . "</option>";
    }
    $select_types .= "</select>";
    //select admins
    $select_admins .= '<select  class ="chzn-select" name = "keyword_admin" id="keyword_admin" ">';
    $select_admins .= '<option value="">---' . get_lang('Select') . '---</option>';
    $select_admins .= '<option value = "0">' . $plugin->get_lang('Unassigned') . '</option>';
    $admins = UserManager::get_user_list_like(array("status" => "1"), array("username"), true);
    foreach ($admins as $admin) {
        $select_admins.= "<option value = '" . $admin['user_id'] . "'>" . $admin['lastname'] . " ," . $admin['firstname'] . "</option>";
    }
    $select_admins .= "</select>";
    //select status
    $select_status .= '<select  class ="chzn-select" name = "keyword_status" id="keyword_status" >';
    $select_status .= '<option value="">---' . get_lang('Select') . '---</option>';
    $status = TicketManager::get_all_tickets_status();
    foreach ($status as $stat) {
        $select_status.= "<option value = '" . $stat['status_id'] . "'>" . $stat['name'] . "</option>";
    }
    $select_status .= "</select>";
    //select priority
    $select_priority .= '<select  name = "keyword_priority" id="keyword_priority" >';
    $select_priority .= '<option value="">' . get_lang('All') . '</option>';
    $select_priority .= '<option value="NRM">' . $plugin->get_lang('PriorityNormal') . '</option>';
    $select_priority .= '<option value="HGH">' . $plugin->get_lang('PriorityHigh') . '</option>';
    $select_priority .= '<option value="LOW">' . $plugin->get_lang('PriorityLow') . '</option>';
    $select_priority .= "</select>";

    //select unread
    $select_unread = '<select  name = "keyword_unread" id="keyword_unread" >';
    $select_unread .= '<option value="">' . get_lang('All') . '</option>';
    $select_unread .= '<option value="yes">' . $plugin->get_lang('Unread') . '</option>';
    $select_unread .= '<option value="no">' . $plugin->get_lang('Read')  . '</option>';
    $select_unread .= "</select>";
    // Create a search-box
    $form = new FormValidator('search_simple', 'get', '', '', null, false);
    $renderer = & $form->defaultRenderer();
    $renderer->setElementTemplate('<span>{element}</span> ');
    $form->addElement('text', 'keyword', get_lang('keyword'), 'size="25"');
    $form->addElement('style_submit_button', 'submit_simple', get_lang('Search'), 'class="search"');
    $form->addElement('static', 'search_advanced_link', null,
            '<a href="javascript://" class = "advanced-parameters" onclick="display_advanced_search_form();">'
            . '<span id="img_plus_and_minus">&nbsp;'
            . Display::return_icon('div_show.gif', get_lang('Show')) . ' '
            . get_lang('AdvancedSearch') . '</span></a>');

    echo '<div class="actions" >';
    if (api_is_platform_admin()) {
        echo '<span class="fleft">' .
                '<a href="' . api_get_path(WEB_PLUGIN_PATH) . 'ticket/src/new_ticket.php">' .
                    Display::return_icon('add.png', $plugin->get_lang('TckNew'), '', '32') . '</a>' .
                '<a href="' . api_get_self() . '?action=export' . $get_parameter . $get_parameter2 . '">' .
                    Display::return_icon('export_excel.png', get_lang('Export'), '', '32') . '</a>' .
             '</span>';
    }
    $form->display();
    echo '</div>';
    echo '<form action="' . api_get_self() . '" method="get" name="advanced_search" id="advanced_search" display:"none">
            <div id="advanced_search_form" style="display: block;">
            <div>
               <div class="form_header">' . get_lang('AdvancedSearch') . '</div>
            </div>
            <table >
               <tbody>
                  <tr>
                     <td>
                        <div>
                           <div class="label2">' . get_lang('Category') . ': </div>
                           <div class="formw2" style="margin-top: -5px;">' . $select_types . '</div>
                        </div>
                     </td>
                     <td>
                        <div>
                           <div class="label3">' . get_lang('User') . ': </div>
                           <div class="formw2"><input class="input-width" id="keyword_request_user" name="keyword_request_user" type="text"></div>
                        </div>
                     </td>
                     <td>
                        <div>
                           <div class="label3">' . $plugin->get_lang('RegisterDate') . ': </div>
                           <div class="formw2"><input class="input-width" id="keyword_start_date_start" name="keyword_start_date_start" type="text"></div>
                        </div>
                     </td>
                     <td>
                        <div>
                           <div class="label3"><input type="checkbox" name="keyword_dates" value="1">' . $plugin->get_lang('Untill') . '</div>
                           <div class="formw2"><input class="input-width" id="keyword_start_date_end" name="keyword_start_date_end" type="text"></div>
                        </div>
                     </td>
                  </tr>
                  <tr >
                     <td>
                        <div>
                           <div class="label2">' . $plugin->get_lang('AssignedTo') . ': </div>
                           <div class="formw2 select-margin-top">' . $select_admins . '</div>
                        </div>
                     </td>
                     <td>
                        <div>
                           <div class="label3 remove-margin-top">' . get_lang('Status') . ':</div>
                           <div class="formw2 select-margin-top">' . $select_status . '</div>
                        </div>
                     </td>
                     <td>
                        <div>
                        <div>
                           <div class="label3">' . $plugin->get_lang('Priority') . ': </div>
                           <div class="formw2">' . $select_priority . '</div>
                        </div>
                     </td>
                     <td>
                        <div>
                           <div>
                              <div class="label3">' . get_lang('Status') . ': </div>
                              <div class="formw2">' . $select_unread . '</div>
                           </div>
                     </td>
                  </tr>
                  <tr>
                  <td>
                  <div >
                  <div class="label4">' . get_lang('Course') . ': </div>
                  <div class="formw2">
                  <input id="keyword_course" style="width: 170px;" name="keyword_course" type="text"></div>
                  </div>
                  </td>
                  <td colspan= "3">
                  <div>
                  <button  name="submit_advanced" type="submit">' . get_lang('AdvancedSearch') . '</button>
                  </div>
                  </td>
                  </tr>
               </tbody>
            </table>
            </div>
            <input name="_qf__advanced_search" type="hidden" value="">
            <div class="clear">&nbsp;</div>
         </form>';
} else {
    if ($plugin->get('allow_student_add') == 'true') {
        echo '<div class="actions" >';
        echo '<span style="float:right;">' .
                '<a href="' . api_get_path(WEB_PLUGIN_PATH) . 'ticket/src/new_ticket.php">' .
                    Display::return_icon('add.png', $plugin->get_lang('TckNew'), '', '32') .
                '</a>' .
              '</span>';
        echo '<span style="float:right;">' .
        '</span>';
        echo '</div>';
    }
}


if ($isAdmin) {
    $table->set_header(0, $plugin->get_lang('TicketNum'), true);
    $table->set_header(1, $plugin->get_lang('Date'), true);
    $table->set_header(2, $plugin->get_lang('DateLastEdition'), true);
    $table->set_header(3, $plugin->get_lang('Category'), true);
    $table->set_header(4, $plugin->get_lang('User'), true);
    $table->set_header(5, $plugin->get_lang('Responsible'), true);
    $table->set_header(6, $plugin->get_lang('Status'), true);
    $table->set_header(7, $plugin->get_lang('Message'), true);
    $table->set_header(8, get_lang('Actions'), true);
    $table->set_header(9, get_lang('Description'), true, array("style" => "width:200px"));
} else {
    echo '<center><h1>' . $plugin->get_lang('MyTickets') . '</h1></center>';
    echo '<center><p>' . $plugin->get_lang('MsgWelcome') . '</p></center>';
    if (isset($_GET['message'])) {
        Display::display_confirmation_message($plugin->get_lang('TckSuccessSave'));
    }
    $table->set_header(0, $plugin->get_lang('TicketNum'), true);
    $table->set_header(1, $plugin->get_lang('Date'), true);
    $table->set_header(2, $plugin->get_lang('DateLastEdition'), true);
    $table->set_header(3, $plugin->get_lang('Category'));
    $table->set_header(4, $plugin->get_lang('Status'), false);
    $table->set_header(5, get_lang('Actions'), false);
}

$table->display();
Display::display_footer();
