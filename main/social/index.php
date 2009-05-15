<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2009 Dokeos SPRL

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/

$cidReset = true;
$language_file = array('registration','messages','userInfo','admin');
require '../inc/global.inc.php';
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
$this_section = SECTION_MYPROFILE;
$_SESSION['this_section']=$this_section;
api_block_anonymous_users();
$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery-1.1.3.1.pack.js" type="text/javascript"></script>';
$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery.history_remote.pack.js" type="text/javascript"></script>';
$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery.tabs.pack.js" type="text/javascript"></script>';
$htmlHeadXtra[] = '<script src="../inc/lib/javascript/thickbox.js" type="text/javascript" language="javascript"></script>';
//$htmlHeadXtra[] = '<style rel="stylesheet" href="../inc/lib/javascript/thickbox.css" type="text/css" media="projection, screen">';
$htmlHeadXtra[]='<style type="text/css" media="all">@import "'.api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.css";</style>';
$htmlHeadXtra[] = '<script type="text/javascript">
$(function() {
	$("#container-9").tabs({ remote: true});	     
});
function register_friend(element_input) {
	name_button=$(element_input).attr("id");
	name_div_id="id_"+name_button.substring(13);
	user_id=name_div_id.split("_");
	user_friend_id=user_id[1];
	 $.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(objeto) {
		$("#id_response").html("'.get_lang('Loading', '').'"); },
		type: "POST",
		url: "../social/register_friend.php",
		data: "friend_id="+user_friend_id,
		success: function(datos) {
		 $("div#"+name_div_id).hide("slow");
		 $("#id_response").html(datos);
		}
	});		
}
function denied_friend (element_input) {
	name_button=$(element_input).attr("id");
	name_div_id="id_"+name_button.substring(13);
	user_id=name_div_id.split("_");
	friend_user_id=user_id[1];	
	 $.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(objeto) {
		$("#id_response").html("'.get_lang('Loading', '').'"); },
		type: "POST",
		url: "../social/register_friend.php",
		data: "denied_friend_id="+friend_user_id,
		success: function(datos) {
		 $("div#"+name_div_id).hide("slow");
		 $("#id_response").html(datos);
		}
	});
}
function delete_friend (element_div) {
	id_image=$(element_div).attr("id");
	user_id=id_image.split("_");
	if (confirm("'.get_lang('Delete', '').'")) {
		 $.ajax({
			contentType: "application/x-www-form-urlencoded",
			beforeSend: function(objeto) {
			/*$("#id_response").html("Cargando...");*/ },
			type: "POST",
			url: "../social/register_friend.php",
			data: "delete_friend_id="+user_id[1],
			success: function(datos) {
			//alert(datos);
			 $("div#"+"div_"+user_id[1]).hide("slow");
			 $("div#"+"div_"+user_id[1]).html("");
			 clear_form ();
			}
		});
	}
}
function qualify_friend(element_div) {
	name_user=$(element_div).attr("title");
	id_full=$(element_div).attr("id");
	$("div#div_info_user").html(name_user);
	user_qualify=id_full.split("_");
	user_qualify_id=user_qualify[1];
	$("#user_cod_qualify").attr("value",user_qualify_id);
	get_image_url=$(element_div).attr("src");
	img_html="<img width=100 height=120 src="+get_image_url+" />";
	$("div#div_qualify_image").html(img_html);
}
function set_qualify_friend() {
	user_id_friend = $("#user_cod_qualify").attr("value");
	type_friend    = $("input[@name=list_type_friend]:checked").val();
	if (user_id_friend==0) {
		alert("'.get_lang('SelectOneContact', '').'");
		return false;
	}
	if (type_friend==null) {
		alert("'.get_lang('SelectOneGroup', '').'");
		return false;		
	}	
	if (confirm("'.get_lang('AttachContactsPersonal', '').' ?")) {
		 $.ajax({
			contentType: "application/x-www-form-urlencoded",
			beforeSend: function(objeto) {
			/*$("#id_response").html("Cargando...");*/ },
			type: "POST",
			url: "../social/register_friend.php",
			data: "user_id_friend_q="+user_id_friend+"&type_friend_q="+type_friend,
			success: function(datos) {
			//alert(datos);
			tb_remove();
			//clear_form();
			}
		});
	}
}
function show_icon_delete(element_html) {
	elem_id=$(element_html).attr("id");
	id_elem=elem_id.split("_");
	ident="#img_"+id_elem[1];
	$(ident).attr("src","../img/delete.gif");
	$(ident).attr("alt","'.get_lang('Delete', '').'");
	$(ident).attr("title","'.get_lang('Delete', '').'");
}
function hide_icon_delete(element_html)  {
	elem_id=$(element_html).attr("id");
	id_elem=elem_id.split("_");
	ident="#img_"+id_elem[1];
	$(ident).attr("src","../img/blank.gif");
	$(ident).attr("alt","");
	$(ident).attr("title","");
}
function search_image_social(element_html)  {
	name_search=$(element_html).attr("value");
	 $.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(objeto) {
		$("#div_content_table").html("'.get_lang('Searching', '').'"); },
		type: "POST",
		url: "../social/show_search_image.inc.php",
		data: "search_name_q="+name_search,
		success: function(datos) {
			$("div#div_content_table").html(datos);
		}
	});
}
function clear_form () {
	$("input[@type=radio]").attr("checked", false);
	$("div#div_qualify_image").html("");
	$("div#div_info_user").html("");
}
function toogle_function (element_html){
	elem_id=$(element_html).attr("id");
	id_elem=elem_id.split("_");
	ident="div#div_group_"+id_elem[1];
	id_button="#btn_"+id_elem[1];
	elem_src=$(id_button).attr("src");
	image_show=elem_src.split("/");
	for (i=0;i<image_show.length;i++) {
		if (image_show[i]=="visible.gif" || image_show[i]=="invisible_na.gif") {
			my_image=image_show[i];
		}
	}
	if (my_image=="visible.gif") {
		$(ident).hide("slow");	
		$(id_button).attr("src","../img/invisible_na.gif");
	} else {
		$(ident).show("slow");	
		$(id_button).attr("src","../img/visible.gif");
	}
}
function selectall_cheks() {
	$("input[@type=checkbox]").attr("checked", true);
}
function unselectall_cheks() {
	$("input[@type=checkbox]").attr("checked", false);
}
function submit_form (path_submit) {
	if (path_submit=="inbox") {
		if (count_checkbox("inbox") > 0) {
		   document.getElementById(\'form_send\').action="../social/index.php?inbox=true#remote-tab-2";		
		   if (confirm("'.get_lang('ConfirmYourChoice', '').'")) {
		   		$("#form_send").submit();
		   }	
		} else {
			alert("'.get_lang('SelectTheCheckbox', '').'");
		}

	} else {
		if (count_checkbox("outbox") > 0) {
			document.getElementById(\'form_send_out\').action="../social/index.php?outbox=true#remote-tab-3";		
		   if (confirm("'.get_lang('ConfirmYourChoice', '').'")) {			
				$("#form_send_out").submit();
		   }
		} else {
			alert("'.get_lang('SelectedMoreOptions', '').'");
		}			
	}
}
function count_checkbox(status_type) {
	if (status_type=="inbox") {
		var checkboxes = document.getElementsByName("id[]");
		var cont = 0;
		for (var x=0; x < checkboxes.length; x++) {
		   if (checkboxes[x].checked) {
		       cont = cont + 1;
		    }
		}
		return cont;	
	} else {
		var checkboxes = document.getElementsByName("out[]");
		var cont = 0;
		for (var x=0; x < checkboxes.length; x++) {
		   if (checkboxes[x].checked) {
		       cont = cont + 1;
		    }
		}
		return cont;	
	}
}
function get_action_url_and_show_messages (name_rs,name_id) {
		 $.ajax({
			contentType: "application/x-www-form-urlencoded",
			beforeSend: function(objeto) {
			$("#id_response").html("'.get_lang('Loading', '').'"); },
			type: "GET",
			url: "../messages/view_message.php",
			data: "rs="+name_rs+"&id="+name_id,
			success: function(datos) {
			 $("div#div_content_messages").html(datos);
			 $("div#div_content_table_data").html(" ");	
			}
		});
}
function close_div_show (my_div) {
	$("div#"+my_div).html("");
		 $.ajax({
			contentType: "application/x-www-form-urlencoded",
			beforeSend: function(objeto) {
			$("#id_response").html("'.get_lang('Loading', '').'"); },
			type: "POST",
			url: "../messages/inbox.php",
			data:"",
			success: function(datos) {
			 $("div#div_content_table_data").html(datos);	
			}
		});
}
function reply_to_messages (my_action,name_rs,name_id) {
	if(my_action=="show") {
		$("div#div_content_messages").html("");
		$("div#div_content_table_data").html("");
		 $.ajax({
			contentType: "application/x-www-form-urlencoded",
			beforeSend: function(objeto) {
			$("#id_response").html("'.get_lang('Loading', '').'"); },
			type: "GET",
			url: "../messages/new_message.php",
			data:"re_id="+name_rs+"&id="+name_id,
			success: function(datos) {
			 $("div#div_content_messages").html(datos);	
			}
		});
	}
}
function compose_and_show_message (my_action,name_rs) {
	if(my_action=="show") {
		$("div#div_content_messages").html("");
		$("div#div_content_table_data").html("");
			$.ajax({
			contentType: "application/x-www-form-urlencoded",
			beforeSend: function(objeto) {
			$("#id_response").html("'.get_lang('Loading', '').'"); },
			type: "GET",
			url: "../messages/new_message.php",
			data:"rs="+name_rs,
			success: function(datos) {
			 $("div#div_content_messages").html(datos);	
			}
		});		
	}		
}
function send_request_and_search() {
	cont=0;
      //$("#id_text_name").bind("keyup", function(){
      	name=$("#id_text_name").get(0).value;
      	if (name.length==0) {
      		return false;
      	}
		$.ajax({
				contentType: "application/x-www-form-urlencoded",
				beforeSend: function(objeto) {
				//$("#id_div_search").html("Searching...");
					},
				type: "POST",
				url: "../social/select_options.php",
				data: "search="+name,
				success: function(datos){
					
					num_records=datos.split(\'</option>\');
					if (num_records.length==1) {
						$("#id_div_search").html("");					
						return false;
					}
	
					$("#id_div_search").html(datos)
						$("#id_search_name").bind("click", function() {
							name_option=$("select#id_search_name option:selected").text();
							code_option=$("select#id_search_name option:selected").val();
							 $("#user_list").attr("value", code_option);
							 $("#id_text_name").attr("value", name_option);
							 
							 $("#id_div_search").html("");
							 cont++;
						 });
				}
		});
      //});
}
function delete_one_message (num_id) {
		$("div#div_content_messages").html("");
		$("div#div_content_table_data").html("");	
			$.ajax({
				contentType: "application/x-www-form-urlencoded",
				beforeSend: function(objeto) {
				/*$("#id_div_search").html("Searching...");*/ },
				type: "GET",
				url: "../messages/inbox.php",
				data: "action="+"deleteone"+"&id="+num_id,
				success: function(datos){
				$("#div_content_table_data").html(datos)
				}
		});
}
function show_sent_message (id_sent) {
		$("div#div_content_messages_sent").html("");
		$("div#div_content_table_data_sent").html("");
			$.ajax({
				contentType: "application/x-www-form-urlencoded",
				beforeSend: function(objeto) {
				/*$("#id_div_search").html("Searching...");*/ },
				type: "GET",
				url: "../messages/view_message.php",
				data: "rs="+"1"+"&id_send="+id_sent,
				success: function(datos){
				$("#div_content_table_data_sent").html(datos)
				}
		});
}
function close_and_open_outbox() {
		$("div#div_content_messages_sent").html("");
		$("div#div_content_table_data_sent").html("");
		 $.ajax({
			contentType: "application/x-www-form-urlencoded",
			beforeSend: function(objeto) {
			$("#id_response").html("'.get_lang('Loading', '').'"); },
			type: "POST",
			url: "../messages/outbox.php",
			data:"",
			success: function(datos) {
			 $("div#div_content_table_data_sent").html(datos);	
			}
		});
}
function delete_one_message_outbox (num_id) {
		$("div#div_content_messages_sent").html("");
		$("div#div_content_table_data_sent").html("");	
			$.ajax({
				contentType: "application/x-www-form-urlencoded",
				beforeSend: function(objeto) {
				/*$("#id_div_search").html("Searching...");*/ },
				type: "GET",
				url: "../messages/outbox.php",
				data: "action="+"deleteone"+"&id="+num_id,
				success: function(datos){
				$("#div_content_table_data_sent").html(datos)
				}
		});
}
function list_search_hide () {
		setTimeout("hide_search_list()",1500);
}
function hide_search_list () {
	$("div#id_div_search").html("");
}
</script>';
if (api_get_setting('allow_message_tool')=='true') {
	$htmlHeadXtra[] ='<script type="text/javascript">
		function delete_message_js() {
			$(".message-content").animate({ opacity: "hide" }, "slow");
			$(".message-view").animate({ opacity: "show" }, "slow");			
		}
	</script>';	
}
$htmlHeadXtra[] = '<link rel="stylesheet" href="../inc/lib/javascript/jquery.tabs.css" type="text/css" media="print, projection, screen">';
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_CODE_PATH).'css/'.api_get_setting('stylesheets').'/jquery.tabs.css" type="text/css" media="print, projection, screen">';
$htmlHeadXtra[] = '
        <!-- Additional IE/Win specific style sheet (Conditional Comments) -->
        <!--[if lte IE 7]>
        <link rel="stylesheet" href="../inc/lib/javascript/jquery.tabs-ie.css" type="text/css" media="projection, screen">
        <![endif]-->
        ';
$htmlHeadXtra[] = '<style type="text/css" media="screen, projection">
/* Not required for Tabs, just to make this demo look better... */

* html body {
    font-size: 100%; /* @ IE */
}
h1 {
    margin: 1em 0 1.5em;
    font-size: 18px;
}
h2 {
    margin: 2em 0 1.5em;
    font-size: 16px;
}
p {
    margin: 0;
}
pre, pre+p, p+p {
    margin: 1em 0 0;
}
code {
    font-family: "Courier New", Courier, monospace;
}
</style>';
$_SESSION['social_exist']=true;
$_SESSION['social_dest'] = 'index.php';
$interbreadcrumb[]= array (
	'url' => '#',
	'name' => get_lang('ModifyProfile')
);
if ((api_get_setting('allow_social_tool')=='true' && api_get_setting('allow_message_tool')=='true') ||(api_get_setting('allow_social_tool')=='true') && api_get_user_id()<>2 && api_get_user_id()<>0) {
	$interbreadcrumb[]= array (
	'url' => 'index.php?#remote-tab-1',
	'name' => get_lang('SocialNetwork')
	);
} elseif ((api_get_setting('allow_social_tool')=='false' && api_get_setting('allow_message_tool')=='true')) {
	$interbreadcrumb[]= array (
	'url' => 'index.php?#remote-tab-1',
	'name' => get_lang('MessageTool')
	);	
}

Display :: display_header('');
if (isset($_GET['sendform'])) {
	$form_reply=array();
	$form_reply[]=urlencode($_POST['title']);
	$form_reply[]=urlencode($_POST['content']);
	$form_reply[]=$_POST['user_list'];
	$form_reply[]=$_POST['re_id'];
	$form_reply[]=urlencode($_POST['compose']);
	$form_reply[]=urlencode($_POST['id_text_name']);
	$form_reply[]=urlencode($_POST['save_form']);
	$form_info=implode(base64_encode('&%ff..x'),$form_reply);
	$form_send_data_message='?form_reply='.$form_info;
} elseif (isset($_GET['inbox'])) {
	$form_delete=array();
	$form_delete[]=$_POST['action'];
	for ($i=0;$i<count($_POST['id']);$i++) {
		$form_delete[]=$_POST['id'][$i];
	}
	$form_info=implode(',',$form_delete);
	$form_send_data_message='?form_delete='.($form_info);
} elseif (isset($_GET['outbox'])) {
	$form_delete_outbox=array();
	$form_delete_outbox[]=$_POST['action'];
	for ($i=0;$i<count($_POST['out']);$i++) {
		$form_delete_outbox[]=$_POST['out'][$i];
	}
	$form_info_outbox=implode(',',$form_delete_outbox);
	$form_send_data_message='?form_delete_outbox='.($form_info_outbox);	
}
$form_url_send=isset($form_send_data_message) ? $form_send_data_message :'';
?>

<div id="container-9">
    <ul>
        <li><a href="data_personal.inc.php"><span><?php Display :: display_icon('profile.png',get_lang('PersonalData')); echo '&nbsp;&nbsp;'.get_lang('PersonalData'); ?></span></a></li>
        <?php 
       	if (api_get_setting('allow_message_tool')=='true') { 
       	?>        
        <li><a href="../messages/inbox.php<?php echo $form_url_send; ?>"><span><?php Display :: display_icon('inbox.png',get_lang('Inbox')); echo '&nbsp;&nbsp;'.get_lang('Inbox');?></span></a></li>
        <li><a href="../messages/outbox.php<?php echo $form_url_send; ?>"><span><?php Display :: display_icon('outbox.png',get_lang('Outbox') ); echo '&nbsp;&nbsp;'.get_lang('Outbox');?></span></a></li>
        <?php }
  	 	if (api_get_setting('allow_social_tool')=='true') {      
        ?>
        <li><a href="select_friend_response.php"><span><?php Display :: display_icon('pending_invitation.png',get_lang('PendingInvitations'), array('height'=>'22px')); echo '&nbsp;&nbsp;'.get_lang('PendingInvitations'); ?></span></a></li>
        <li><a href="contacts.inc.php"><span><?php Display :: display_icon('contacts.png',get_lang('Contacts')); echo '&nbsp;&nbsp;'.get_lang('Contacts'); ?></span></a></li>
        <li><a href="group_contact.inc.php"><span><?php Display :: display_icon('group_contact.png',get_lang('ContactsGroups')); echo '&nbsp;&nbsp;'.get_lang('ContactsGroups'); ?></span></a></li>
        <?php
  	 	}
        ?>   
    
    </ul>
    <?php echo '<div id="show">&nbsp;</div>';?>
</div>
<?php
Display :: display_footer();
?>
