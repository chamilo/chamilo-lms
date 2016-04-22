<?php
/* For licensing terms, see /license.txt */

/**
 *
 * @package chamilo.plugin.ticket
 */
$cidReset = true;
require_once '../config.php';
$plugin = TicketPlugin::create();

api_block_anonymous_users();

$user_id = api_get_user_id();
$isAdmin = api_is_platform_admin();
$interbreadcrumb[] = array('url' => 'myticket.php', 'name' => $plugin->get_lang('MyTickets'));
$interbreadcrumb[] = array('url' => '#', 'name' => $plugin->get_lang('TicketDetail'));

$disableReponseButtons = "";
if ($isAdmin) {
    $disableReponseButtons = "$('#responseyes').attr('disabled', 'disabled');
                              $('#responseno').attr('disabled', 'disabled');";
}

$htmlHeadXtra[] = '
<script language="javascript">
$(document).ready(function(){
	$("#dialog-form").dialog({
		autoOpen: false,
		height: 450,
		width: 600,
		modal: true,
		buttons: {
            ' . get_lang('Accept') . ': function(){
                $("#frmResponsable").submit()
            },
            ' . ucfirst(get_lang('Close')) . ': function() {
                $(this).dialog("close");
            }
            }
        });

        $("a#assign").click(function () {
            $( "#dialog-form" ).dialog( "open" );
        });

        $("input#responseyes").click(function () {
            if(!confirm("' . $plugin->get_lang('AreYouSure') . ' : ' . strtoupper(get_lang('Yes')) . '. ' . $plugin->get_lang('IfYouAreSureTheTicketWillBeClosed') . '")){
                return false;
            }
        });

        $("input#responseno").click(function () {
            if(!confirm("' . $plugin->get_lang('AreYouSure') . ' : ' . strtoupper(get_lang('No')) . '")){
                return false;
            }
        });

        $("#unassign").click(function () {
            if (!confirm("' . $plugin->get_lang('AreYouSureYouWantToUnassignTheTicket') . '")) {
                return false;
            }
        });

        $("#close").click(function () {
            if (!confirm("' . $plugin->get_lang('AreYouSureYouWantToCloseTheTicket') . '")) {
                return false;
            }
        });

    ' . $disableReponseButtons . '
});

function validate() {
    fckEditor1val = CKEDITOR.instances["content"].getData();
    document.getElementById("content").value= fckEditor1val;
    if(fckEditor1val == ""){
        alert("' . $plugin->get_lang('YouMustWriteAMessage') . '");
        return false;
    }
}

var counter_image = 1;

function remove_image_form(element_id) {
    $("#" + element_id).remove();
    counter_image = counter_image - 1;
    $("#link-more-attach").css("display", "block");
}

function add_image_form() {
    // Multiple filepaths for image form
    var filepaths = $("#filepaths");
    var new_elem, input_file, link_remove, img_remove, new_filepath_id;

    if ($("#filepath_"+counter_image)) {
        counter_image = counter_image + 1;
    }  else {
        counter_image = counter_image;
    }

    new_elem = "filepath_"+counter_image;

    $("<div/>", {
        id: new_elem,
        class: "controls"
    }).appendTo(filepaths);

    input_file = $("<input/>", {
        type: "file",
        name: "attach_" + counter_image,
        size: 20
    });

    link_remove = $("<a/>", {
        onclick: "remove_image_form(\'" + new_elem + "\')",
        style: "cursor: pointer"
    });

    img_remove = $("<img/>", {
        src: "' . Display::returnIconPath('delete.png').'"
    });

    new_filepath_id = $("#filepath_" + counter_image);
    new_filepath_id.append(input_file, link_remove.append(img_remove));

    if (counter_image === 6) {
        var link_attach = $("#link-more-attach");
        if (link_attach) {
            $(link_attach).css("display", "none");
        }
    }
}
</script>';

$htmlHeadXtra[] = '
<style>
div.row div.label2 {
	float:left;
	text-align: right;
	width:22%;
}
div.row div.formw2 {
    width:50%;
	margin-left: 2%;
	margin-right: 16%;
	float:left;
}
.messageuser, .messagesupport {
    border: 1px solid;
    margin: 10px 0px;
    padding:15px 10px 15px 50px;
    background-repeat: no-repeat;
    background-position: 10px center;
    width:50%;
	behavior: url(/pie/PIE.htc);
}
.messageuser {
    color: #00529B;
    -moz-border-radius: 15px 15px 15px 15px;
    -webkit-border-radius: 15px 15px 15px 15px;
    background-color: #BDE5F8;
    margin-left:20%;
    border-radius:15px;
    float: left;
}
.messagesupport {
    color: #4F8A10;
    -moz-border-radius: 15px 15px 15px 15px;
    -webkit-border-radius: 15px 15px 15px 15px;
    background-color: #DFF2BF;
    margin-right: 20%;
    float: right;
    border-radius:15px;
}
.attachment-link {
    margin: 12px;
}
#link-more-attach {
    color: white;
    cursor: pointer;
    width: 120px;
}
</style>';

$ticket_id = $_GET['ticket_id'];
$ticket = TicketManager::get_ticket_detail_by_id($ticket_id, $user_id);
if (!isset($ticket['ticket'])) {
    api_not_allowed();
}
if (!isset($_GET['ticket_id'])) {
    header('location:myticket.php');
    exit;
}
if (isset($_POST['response'])) {
    if ($user_id == $ticket['ticket']['request_user']) {
        $response = ($_POST['response'] == "1") ? true : ($_POST['response'] == "0" ? false : null);
        if ($response && $ticket['ticket']['status_id'] == 'XCF') {
            TicketManager::close_ticket($_GET['ticket_id'], $user_id);
            $ticket['ticket']['status_id'] = 'CLS';
            $ticket['ticket']['status'] = $plugin->get_lang('Closed');
        } else if (!is_null($response) && $ticket['ticket']['status_id'] == 'XCF') {
            TicketManager::update_ticket_status('PND', $_GET['ticket_id'], $user_id);
            $ticket['ticket']['status_id'] = 'PND';
            $ticket['ticket']['status'] = $plugin->get_lang('StatusPending');
        }
    }
}
if (isset($_REQUEST['action'])) {
    $action = $_REQUEST['action'];
    switch ($action) {
        case 'assign':
            if (api_is_platform_admin() && isset($_GET['ticket_id']))
                TicketManager::assign_ticket_user($_GET['ticket_id'], $_POST['admins']);
            $ticket['ticket']['assigned_last_user'] = $_POST['admins'];
            break;
        case 'unassign':
            if (api_is_platform_admin() && isset($_GET['ticket_id']))
                TicketManager::assign_ticket_user($_GET['ticket_id'], 0);
            $ticket['ticket']['assigned_last_user'] = 0;
            break;
        default:
            break;
    }
}

$titulo = 'Ticket #' . $ticket['ticket']['ticket_code'];
$firstMessage = is_array($ticket['messages']) ? $ticket['messages'][0] : '';
$subTitle = '';
if (!empty($firstMessage) && isset($firstMessage['subject'])) {
    $subTitle = $firstMessage['subject'];
}

if (!isset($_POST['compose'])) {
    if (isset($_REQUEST['close'])) {
        TicketManager::close_ticket($_REQUEST['ticket_id'], $user_id);
        $ticket['ticket']['status_id'] = 'CLS';
        $ticket['ticket']['status'] = $plugin->get_lang('Closed');
    }
    $ticket['ticket']['request_user'] = intval($ticket['ticket']['request_user']);
    if ($ticket['ticket']['request_user'] == $user_id || intval($ticket['ticket']['assigned_last_user']) == $user_id) {
        TicketManager::update_message_status($ticket_id, $ticket['ticket']['request_user']);
    }
    Display::display_header();
    $form_close_ticket = "";
    if ($ticket['ticket']['status_id'] != 'REE' && $ticket['ticket']['status_id'] != 'CLS' && $isAdmin) {
        if (intval($ticket['ticket']['assigned_last_user']) == $user_id) {
            if ($ticket['ticket']['status_id'] != 'CLS') {
                $form_close_ticket.= '<a href="' . api_get_self() . '?close=1&ticket_id=' . $ticket['ticket']['ticket_id'] . '" id="close" class="btn btn-danger" >';
                $form_close_ticket.= get_lang('Close') . '</a>';
            }
        }
    }

    $img_assing = '';
    if ($isAdmin && $ticket['ticket']['status_id'] != 'CLS' && $ticket['ticket']['status_id'] != 'REE') {
        if ($ticket['ticket']['assigned_last_user'] != 0 && $ticket['ticket']['assigned_last_user'] == $user_id) {
            $img_assing = '<a class="btn btn-warning" href="' . api_get_self() . '?ticket_id=' . $ticket['ticket']['ticket_id'] . '&amp;action=unassign" id="unassign">
                           '.get_lang('Unassign').'
                           </a>';
        } else {
            $img_assing = '<a href="#" id="assign" class="btn btn-success">'.get_lang('Assign').'</a>';

        }
    }
    $bold = '';

    if ($ticket['ticket']['status_id'] == 'CLS') {
        $bold = 'style = "font-weight: bold;"';
        echo "<style>
                #confirmticket {
                    display: none;
                }
              </style>";
    }
    if ($isAdmin) {
        $senderData = get_lang('AddedBy') . ' ' . $ticket['ticket']['user_url'] . ' (' . $ticket['usuario']['username'] . ').';
    } else {
        $senderData = get_lang('AddedBy') . ' ' . $ticket['usuario']['complete_name'] . ' (' . $ticket['usuario']['username'] . ').';
    }

    echo '
			<table width="100%" >
				<tr>
	              <td colspan="3" style="width:65%">
	              <h1>' . $titulo . ' '.$form_close_ticket.' '.$img_assing.' </h1>
	              <h2>'.$subTitle.'</h2>
	              <p>
	                '.$senderData.' ' .
                    get_lang('Created') . ' '.
                    Display::url(
                        date_to_str_ago($ticket['ticket']['start_date_from_db']),
                        '#',
                        ['title' => $ticket['ticket']['start_date'], 'class' => 'boot-tooltip']
                    ).'. '.
                    $plugin->get_lang('TicketUpdated').' '.
                    Display::url(
                        date_to_str_ago($ticket['ticket']['sys_lastedit_datetime_from_db']),
                        '#',
                        ['title' => $ticket['ticket']['sys_lastedit_datetime'], 'class' => 'boot-tooltip']
                    ).'
	              </p>
	              </td>
	            </tr>
	            <tr>
	               <td><p><b>' . get_lang('Category') . ': </b>' . $ticket['ticket']['name'] . '</p></td>
	            </tr>
	            <tr>
	               <td><p ' . $bold . '><b>' . get_lang('Status') . ':</b> ' . $ticket['ticket']['status'] . '</p></td>
	            </tr>
	            <tr>
	                <td><p><b>' . $plugin->get_lang('Priority') . ': </b>' . $ticket['ticket']['priority'] . '<p></td>
	            </tr>';
    if ($ticket['ticket']['course_url'] != null) {
        echo '<tr>
				<td><p>' . get_lang('Course') . ':</p></td>
	            <td></td>
			    <td>' . $ticket['ticket']['course_url'] . '</td>
	            <td colspan="2"></td>
	          </tr>';
    }
    echo '<tr>
            <td><b>' . get_lang('Description') . ':</b> <br />
            '.$firstMessage['message'].'</td>
         </tr>
        ';

    // select admins
    $select_admins = '<select  class ="chzn-select" style="width: 350px; " name = "admins" id="admins" ">';

    $admins = UserManager::get_user_list_like(array("status" => "1"), array("username"), true);
    foreach ($admins as $admin) {
        $select_admins.= "<option value = '" . $admin['user_id'] . "' " . (($user_id == $admin['user_id']) ? ("selected='selected'") : "") . ">" . $admin['lastname'] . ", " . $admin['firstname'] . "</option>";
    }
    $select_admins .= "</select>";
    echo '<div id="dialog-form" title="' . $plugin->get_lang('AssignTicket') . '" >';
    echo '<form id="frmResponsable" method="POST" action="ticket_details.php?ticket_id=' . $ticket['ticket']['ticket_id'] . '">
			<input type="hidden" name ="action" id="action" value="assign"/>
			<div>
				<div class="label">' . get_lang('Responsable') . ':</div>
				<div class="formw">' . $select_admins . '</div>
			</div>
		  </form>';
    echo '</div>';
    echo '</table>';
    $messages = $ticket['messages'];

    unset($messages[0]);
    foreach ($messages as $message) {
        $type = "success";

        if ($message['admin']) {
            $type = "normal";
            if ($isAdmin) {
                $message['message'] .= '<br/><b>' . $plugin->get_lang('AttendedBy') . ': ' . $message['user_created'] . " - " . api_convert_and_format_date(api_get_local_time($message['sys_insert_datetime']), DATE_TIME_FORMAT_LONG, _api_get_timezone()) . "</b>";
            }
        }else {
            $message['message'] .= '<br /><b>' . get_lang('Sent') . ':</b> ' . api_convert_and_format_date(api_get_local_time($message['sys_insert_datetime']), DATE_TIME_FORMAT_LONG, _api_get_timezone());
        }

        $receivedMessage = '<b>' . get_lang('Subject') . ': </b> ' . $message['subject'] . '<br/>
                            <b>' . get_lang('Message') . ':</b><br/>' . $message['message'] . '<br/>';
        $attachementLinks = "";

        if (isset($message['atachments'])) {
            $attributeClass = array(
                'class' => 'attachment-link'
            );
            foreach ($message['atachments'] as $attach) {
                $attachementLinks .= Display::tag('div', $attach['attachment_link'], $attributeClass);
            }
        }

        $entireMessage = $receivedMessage . $attachementLinks;
        echo Display::return_message($entireMessage, $type, false);
    }

    $subject = get_lang('ReplyShort') .": " . $firstMessage['subject'];
    $user_admin = api_is_platform_admin();
    if ($ticket['ticket']['status_id'] != 'REE' AND $ticket['ticket']['status_id'] != 'CLS') {
        if (!$isAdmin && $ticket['ticket']['status_id'] != 'XCF') {
            show_form_send_message();
        } else {
            if (intval($ticket['ticket']['assigned_last_user']) == $user_id) {
                show_form_send_message();
                $cheked = "";
            }
        }
    }

    Display::display_footer();

} else {
    $ticket_id = $_POST['ticket_id'];
    $content = $_POST['content'];
    $subject = $_POST['subject'];
    $mensajeconfirmacion = isset($_POST['confirmation']) ? true : false;
    $file_attachments = $_FILES;
    $user_id = api_get_user_id();
    TicketManager::insert_message($ticket_id, $subject, $content, $file_attachments, $user_id, 'NOL', $mensajeconfirmacion);
    header("location:" . api_get_self() . "?ticket_id=" . $ticket_id);
    exit;
}

function show_form_send_message()
{
    global $isAdmin;
    global $ticket;
    global $subject;
    global $plugin;

    Display::div('', array('span2'));

    $form = new FormValidator(
        'send_ticket',
        'POST',
        api_get_self() . '?ticket_id=' . $ticket['ticket']['ticket_id'],
        '',
        array(
            'enctype' => 'multipart/form-data',
            'onsubmit' => 'return validate()',
            'class' => 'span9 offset1 form-horizontal'
        )
    );

    $form->addElement(
        'text',
        'subject',
        get_lang('Subject'),
        array(
            'for' => 'subject',
            'value' => $subject,
            'style' => 'width: 540px;'
        )
    );

    $form->addElement('hidden', 'ticket_id', $_GET['ticket_id']);

    $form->addHtmlEditor(
        'content',
        get_lang('Message'),
        false,
        false,
        array(
            'ToolbarSet' => 'Profile',
            'Width' => '550',
            'Height' => '250'
        )
    );

    if ($isAdmin) {
        $form->addElement(
            'checkbox',
            'confirmation',
             null,
            $plugin->get_lang('RequestConfirmation')
        );
    }

    $form->addElement('file', 'attach_1', get_lang('FilesAttachment'));
    $form->addLabel('', '<span id="filepaths"><div id="filepath_1"></div></span>');
    $form->addLabel('',
        '<span id="link-more-attach">
         <span class="btn btn-success" onclick="return add_image_form()">' . get_lang('AddOneMoreFile') . '</span>
         </span>
         ('.sprintf(get_lang('MaximunFileSizeX'), format_file_size(api_get_setting('message_max_upload_filesize'))).')
    ');

    $form->addElement('html', '<br/>');
    $form->addElement(
        'button',
        'compose',
        get_lang('SendMessage'),
        null,
        null,
        null,
        'btn btn-primary'
    );

    $form->display();
}
