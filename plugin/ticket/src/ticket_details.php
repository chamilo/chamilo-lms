<?php

/* For licensing terms, see /license.txt */
/**
 *
 * @package chamilo.plugin.ticket
 */
/**
 *
 */
$language_file = array('messages', 'userInfo', 'admin');
$cidReset = true;
require_once '../config.php';
$plugin = TicketPlugin::create();

api_block_anonymous_users();
$interbreadcrumb[] = array('url' => 'myticket.php', 'name' => get_lang('MisTickets'));
$interbreadcrumb[] = array('url' => '#', 'name' => get_lang('TicketDetail'));
$htmlHeadXtra[] = '
<script src="/pie/PIE_IE678.js"></script>
<script language="javascript">
$(document).ready(function(){
	$( "#dialog-form" ).dialog({
		autoOpen: false,
		height: 450,
		width: 600,
		modal: true,
		buttons: {
			Asignar: function(){
				$("#genesis").submit()
				},
			 Close: function() {
                    $( this ).dialog( "close" );
             }
		}
		
	});
    	$("a#assign").click(function () {
		$( "#dialog-form" ).dialog( "open" );
        
    	});
		$("input#respuestasi").click(function () {
			if(!confirm("Confirma que su respuesta es : SI ?. Si est\u00e1 seguro el ticket ser\u00e1 cerrado")){
				return false;
			}
        
    	});
		$("input#respuestano").click(function () {
			if(!confirm("Confirma que su respuesta es : NO ?")){
				return false;
			}
        
    	});
	$("#unassign").click(function () {
        if (!confirm("Estas seguro de Desasignarte")) {
            return false		
		}
    });
	$("#close").click(function () {
        if (!confirm("Estas seguro de Cerrar el Ticket")) {
			return false		
		}
    });
});
function validate() {	
	fckEditor1val = FCKeditorAPI.__Instances["content"].GetHTML();	
	document.getElementById("content").value= fckEditor1val;
	if(fckEditor1val ==""){
		alert("Debe escribir un mensaje");
		return false;
	}
}
var counter_image = 1;
function remove_image_form(id_elem1) {
	var elem1 = document.getElementById(id_elem1);
	elem1.parentNode.removeChild(elem1);
	counter_image = counter_image - 1;
}
function add_image_form() {
	// Multiple filepaths for image form
	var filepaths = document.getElementById("filepaths");
	if (document.getElementById("filepath_"+counter_image)) {
		counter_image = counter_image + 1;
	}  else {
		counter_image = counter_image;
	}
	var elem1 = document.createElement("div");
	elem1.setAttribute("id","filepath_"+counter_image);
	filepaths.appendChild(elem1);
	id_elem1 = "filepath_"+counter_image;
	id_elem1 = "\'"+id_elem1+"\'";
	document.getElementById("filepath_"+counter_image).innerHTML = "<input type=\"file\" name=\"attach_"+counter_image+"\"  size=\"20\"  />&nbsp;<a href=\"javascript:remove_image_form("+id_elem1+")\"><img src=\"' . api_get_path(WEB_CODE_PATH) . 'img/delete.gif\"></a>";
	//document.getElementById("filepath_"+counter_image).innerHTML = "<input type=\"file\" name=\"attach_"+counter_image+"\"  size=\"20\" />&nbsp;<input type=\"text\" name=\"legend[]\" size=\"20\" />";
	if (filepaths.childNodes.length == 6) {
		var link_attach = document.getElementById("link-more-attach");
		if (link_attach) {
			link_attach.innerHTML="";
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

</style>';

$user_id = api_get_user_id();
$isAdmin = api_is_platform_admin();
$ticket_id = $_GET['ticket_id'];
$ticket = TicketManager::get_ticket_detail_by_id($ticket_id, $user_id);
if (!isset($ticket['ticket'])) {
    api_not_allowed();
}
if (!isset($_GET['ticket_id'])) {
    header('location:myticket.php');
}
if (isset($_POST['respuesta'])) {
    if ($user_id == $ticket['ticket']['request_user']) {
        $respuesta = ($_POST['respuesta'] == 'si') ? true : (($_POST['respuesta'] == 'no' ? false : null));
        if ($respuesta && $ticket['ticket']['status_id'] == 'XCF') {
            TicketManager::close_ticket($_GET['ticket_id'], $user_id);
            $ticket['ticket']['status_id'] = 'CLS';
            $ticket['ticket']['status'] = 'CERRADO';
        } else if (!is_null($respuesta) && $ticket['ticket']['status_id'] == 'XCF') {
            TicketManager::update_ticket_status('PND', $_GET['ticket_id'], $user_id);
            $ticket['ticket']['status_id'] = 'PND';
            $ticket['ticket']['status'] = 'PENDIENTE';
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
if (!isset($_POST['compose'])) {
    if (isset($_POST['close'])) {
        $_GET['ticket_id'] = $_POST['ticket_id'];
        TicketManager::close_ticket($_GET['ticket_id'], $user_id);
        $ticket['ticket']['status_id'] = 'CLS';
        $ticket['ticket']['status'] = 'CERRADO';
    }
    $ticket['ticket']['request_user'] = intval($ticket['ticket']['request_user']);
    if ($ticket['ticket']['request_user'] == $user_id || intval($ticket['ticket']['assigned_last_user']) == $user_id) {
        TicketManager::update_message_status($ticket_id, $ticket['ticket']['request_user']);
    }
    Display::display_header();
    $form_close_ticket = "";
    if ($ticket['ticket']['status_id'] != 'REE' AND $ticket['ticket']['status_id'] != 'CLS' AND $isAdmin) {
        if (intval($ticket['ticket']['assigned_last_user']) == $user_id) {
            if ($ticket['ticket']['status_id'] != 'CLS') {
                $form_close_ticket.= '<form enctype="multipart/form-data" action="' . api_get_self() . '?ticket_id=' . $ticket['ticket']['ticket_id'] . '" method="post" name="close_ticket" id="close_ticket" >';
                $form_close_ticket.= '<input type="hidden" name="ticket_id" value="' . $ticket['ticket']['ticket_id'] . '"/>
							<button class="minus" name="close" type="submit" id="close" >Cerrar</button>';
                $form_close_ticket.= '</form>';
            }
        }
    }
    $titulo = '<center><h1>Ticket #' . $ticket['ticket']['ticket_code'] . '</h1></center>';
    if ($isAdmin && $ticket['ticket']['status_id'] != 'CLS' && $ticket['ticket']['status_id'] != 'REE') {
        if ($ticket['ticket']['assigned_last_user'] != 0 && $ticket['ticket']['assigned_last_user'] == $user_id) {
            $img_assing = '<a href="' . api_get_self() . '?ticket_id=' . $ticket['ticket']['ticket_id'] . '&amp;action=unassign" id="unassign"><img src="../../../main/img/admin_star.png" border="0" title="Desasignarme" align="center"/></a>';
        } else {
            $img_assing .= '<a href="#" id="assign"><img src="../../../main/img/admin_star_na.png" border="0" title="Asignar" align="center"/></a>';
        }
    }
    $negrita = ($ticket['ticket']['status_id'] == 'CLS') ? 'style = "font-weight: bold;"' : '';
    $cadena = ($ticket['ticket']['status_id'] != 'CLS') ? "sas" : "";
    echo '<div style="margin-left:20%;margin-right:20%;">
			<table width="100%" >
				<tr>
	              <td colspan="3" style="width:65%">' . $titulo . '</td>
	              <td >' . $img_assing . '</td>
	              <td>' . $form_close_ticket . '</td>
	            </tr>
	         	<tr>
	              <td style="width:45%;" ><p>Enviado  : ' . $ticket['ticket']['start_date'] . '</p></td>
	              <td style="width:50px;"></td>
	              <td style="width:45%;" ><p>Ultima Respuesta  : ' . $ticket['ticket']['sys_lastedit_datetime'] . '</p></td>
	              <td colspan="2"></td>
	            </tr>
	            <tr>
	               <td><p>Asunto  : ' . $ticket['messages'][0]['subject'] . '</p></td>
	               <td></td>
	               <td><p ' . $negrita . '>Estado : ' . $ticket['ticket']['status'] . '</p></td>
	               <td colspan="2"></td>
	            </tr>
	            <tr>
	                <td><p>Categoria  : ' . $ticket['ticket']['name'] . '</p></td>
	                <td></td>
	                <td ><p>Prioridad :' . $ticket['ticket']['priority'] . '<p></td>
	                <td colspan="2"></td>
	            </tr>';
    if ($ticket['ticket']['course_url'] != null) {
        echo '<tr>
				<td><p>Curso:</p></td>
	            <td></td>
			    <td>' . $ticket['ticket']['course_url'] . '</td>
	            <td colspan="2"></td>
	          </tr>';
    }
    if ($isAdmin) {
        echo '<tr>
				<td><p>Usuario:</p></td>
	            <td></td>
			    <td>' . $user_info = $ticket['ticket']['user_url'] . ' (' . $ticket['usuario']['username'] . ')</td>
	            <td colspan="2"></td>
			 </tr>';
    }
    //select admins
    $select_admins .= '<select  class ="chzn-select" style="width: 350px; " name = "admins" id="admins" ">';

    $admins = UserManager::get_user_list_like(array("status" => "1"), array("username"), true);
    foreach ($admins as $admin) {
        $select_admins.= "<option value = '" . $admin['user_id'] . "' " . (($user_id == $admin['user_id']) ? ("selected='selected'") : "") . ">" . $admin['lastname'] . " ," . $admin['firstname'] . "</option>";
    }
    $select_admins .= "</select>";
    echo '<div id="dialog-form" title="Asignar Ticket" >';
    echo '<form id="genesis" method="POST" action="ticket_details.php?ticket_id=' . $ticket['ticket']['ticket_id'] . '">
			<input type="hidden" name ="action" id="action" value="assign"/>
			<div  class="row">
				<div class="label">Responsable:</div>
				<div class="formw">' . $select_admins . '</div>
			</div>			
		  </form>';
    echo '</div>';
    echo '</table></div>';
    $messages = $ticket['messages'];
    foreach ($messages as $message) {
        $class = "messageuser";
        if ($message['admin']) {
            $class = "messagesupport";
            if ($isAdmin)
                $message['message'].="<br/><b>Atendido por: " . $message['user_created'] . " - " . api_convert_and_format_date(api_get_local_time($message['sys_insert_datetime']), DATE_TIME_FORMAT_LONG, _api_get_timezone()) . "</b>";
        }else {
            $message['message'].="<b>Enviado: " . api_convert_and_format_date(api_get_local_time($message['sys_insert_datetime']), DATE_TIME_FORMAT_LONG, _api_get_timezone()) . "</b>";
        }
        echo '<div class="' . $class . '" ><b>Asunto: </b> ' . $message['subject'] . '<br/> <b> Mensaje:</b>' . $message['message'] . '<br/>';
        if (isset($message['atachments'])) {
            foreach ($message['atachments'] as $attach) {
                echo $attach['attachment_link'];
            }
        }
        echo '</div>';
    }
    $asunto = "RE: " . $message['subject'];
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
} else {
    $ticket_id = $_POST['ticket_id'];
    $content = $_POST['content'];
    $subject = $_POST['subject'];
    $mensajeconfirmacion = isset($_POST['confirmation']) ? true : false;
    $file_attachments = $_FILES;
    $user_id = api_get_user_id();
    TicketManager::insert_message($ticket_id, $subject, $content, $file_attachments, $user_id, 'NOL', $mensajeconfirmacion);
    header("location:" . api_get_self() . "?ticket_id=" . $ticket_id);
}

function show_form_send_message()
{
    global $isAdmin;
    global $ticket;
    global $asunto;
    echo '<form enctype="multipart/form-data" action="' . api_get_self() . '?ticket_id=' . $ticket['ticket']['ticket_id'] . '" method="post" name="send_ticket" id="send_ticket"
 	onsubmit="return validate()" style="width:100%">';
    echo '<div class="row" ><div class ="label">Asunto:</div>
       		<div class="formw"><input type = "text" id ="subject" name="subject" value="' . $asunto . '" required ="" style="width:60%"/></div>
		  </div>';
    echo '<div class="row">
		<div class="label2">mensaje
		</div>
		<div class="formw2">
			<input type="hidden" id="content" name="content" value="" style="display:none">
		<input type="hidden" id="content___Config" value="ToolbarSet=Messages&amp;Width=95%25&amp;Height=250&amp;ToolbarSets={ %22Messages%22: [  [ %22Bold%22,%22Italic%22,%22-%22,%22InsertOrderedList%22,%22InsertUnorderedList%22,%22Link%22,%22RemoveLink%22 ] ], %22MessagesMaximized%22: [  ] }&amp;LoadPlugin=[%22customizations%22]&amp;EditorAreaStyles=body { background: #ffffff; }&amp;ToolbarStartExpanded=false&amp;CustomConfigurationsPath=/main/inc/lib/fckeditor/myconfig.js&amp;EditorAreaCSS=/main/css/chamilo/default.css&amp;ToolbarComboPreviewCSS=/main/css/chamilo/default.css&amp;DefaultLanguage=es&amp;ContentLangDirection=ltr&amp;AdvancedFileManager=true&amp;BaseHref=' . api_get_path(WEB_PLUGIN_PATH) . PLUGIN_NAME . '/s/&amp;&amp;UserIsCourseAdmin=true&amp;UserIsPlatformAdmin=true" style="display:none">
		<iframe id="content___Frame" src="/main/inc/lib/fckeditor/editor/fckeditor.html?InstanceName=content&amp;Toolbar=Messages" width="95%" height="250" frameborder="0" scrolling="no" style="margin: 0px; padding: 0px; border: 0px; background-color: transparent; background-image: none; width: 95%; height: 250px;">
		</iframe>
		</div>
	</div>
';
    echo '<input type="hidden" id="ticket_id" name="ticket_id" value="' . $_GET['ticket_id'] . '">';
    echo '<div class="row">
		<div class="label">' . get_lang('FilesAttachment') . '</div>
		<div class="formw">
				<span id="filepaths">
				<div id="filepath_1">
					<input type="file" name="attach_1" id="attach_1"  size="20" style="width:59%;"/>
				</div></span>
		</div>
	</div>';
    echo '<div class="row">
		<div class="formw">
			<span id="link-more-attach">
				<a href="javascript://" onclick="return add_image_form()">' . get_lang('AddOneMoreFile') . '</a></span>&nbsp;
					(' . sprintf(get_lang('MaximunFileSizeX'), format_file_size(api_get_setting('message_max_upload_filesize'))) . ')
			</div>
		</div>';
    echo '<div class="row">
		<div class="label"></div>
		<div class="formw">	<button class="save" name="compose" type="submit">Enviar mensaje</button>' . ($isAdmin ? '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="confirmation"/>Solicitar confirmaci&oacute;n' : "") .
    '</div>
	</div>';
    echo '</form>';
}

Display::display_footer();
?>