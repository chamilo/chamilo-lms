<?php 
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2009 Dokeos SPRL
	Copyright (c) 2009 Julio Montoya Armas <gugli100@gmail.com>
	Copyright (c) Facultad de Matematicas, UADY (M�xico)
	Copyright (c) Evie, Free University of Brussels (Belgium)		

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
/*
==============================================================================
		INIT SECTION
==============================================================================
*/ 
// name of the language file that needs to be included 
$language_file = array('registration','messages','userInfo','admin');
$cidReset=true;
include_once ('../inc/global.inc.php');
require_once '../messages/message.class.php';
require_once (api_get_path(LIBRARY_PATH).'message.lib.php');
api_block_anonymous_users();

if (api_get_setting('allow_message_tool')!='true'){
	api_not_allowed();
}
$htmlHeadXtra[]='<script language="javascript">
<!--
function enviar(miforma) 
{ 
	if(confirm("'.get_lang("SureYouWantToDeleteSelectedMessages").'"))
		miforma.submit();
} 
function select_all(formita)
{ 
   for (i=0;i<formita.elements.length;i++) 
	{
      		if(formita.elements[i].type == "checkbox") 			
				formita.elements[i].checked=1			
	}
}
function deselect_all(formita)
{ 
   for (i=0;i<formita.elements.length;i++) 
	{
      		if(formita.elements[i].type == "checkbox") 			
				formita.elements[i].checked=0			
	}	
}
//-->
</script>';


/*
==============================================================================
		MAIN CODE
==============================================================================
*/

//$nameTools = get_lang('Messages');
$request=api_is_xml_http_request();
if ($request===false) {
	$interbreadcrumb[]= array (
		'url' => '#',
		'name' => get_lang('Messages')
	);
	$interbreadcrumb[]= array (
		'url' => 'inbox.php',
		'name' => get_lang('Inbox')
	);
	$interbreadcrumb[]= array (
		'url' => 'outbox.php',
		'name' => get_lang('Outbox')
	);
	Display::display_header('');
}
/**************************************************************/
$info_delete_outbox=array();
$info_delete_outbox=explode(',',$_GET['form_delete_outbox']);
$count_delete_outbox=(count($info_delete_outbox)-1);
/**************************************************************/
if( trim($info_delete_outbox[0])=='delete' ) {
	for ($i=1;$i<=$count_delete_outbox;$i++) {
		MessageManager::delete_message_by_user_sender(api_get_user_id(),$info_delete_outbox[$i]);	
	}
		$message_box=get_lang('SelectedMessagesDeleted').
			'&nbsp
			<br><a href="../social/index.php#remote-tab-3">'.
			get_lang('BackToOutbox').
			'</a>';
		Display::display_normal_message($message_box,false);
	    exit;	
}
/**************************************************************/
$table_message = Database::get_main_table(TABLE_MESSAGE);
echo '<div id="div_content_messages_sent">&nbsp;&nbsp;';
api_display_tool_title(mb_convert_encoding(get_lang('Outbox'),'UTF-8',$charset));
echo '<div class=actions>';
$language_variable=($request===true) ? mb_convert_encoding(get_lang('MessageOutboxComment'),'UTF-8',$charset) : get_lang('MessageOutboxComment');
echo $language_variable;	
echo '</div>';
echo '</div>';
$user_sender_id=api_get_user_id();
if ($_REQUEST['action']=='delete') {
	$delete_list_id=array();
	if (isset($_POST['out'])) {
		$delete_list_id=$_POST['out'];	
	}
	if (isset($_POST['id'])) {
		$delete_list_id=$_POST['id'];			
	}
	for ($i=0;$i<count($delete_list_id);$i++) {
		MessageManager::delete_message_by_user_sender(api_get_user_id(), $delete_list_id[$i]);		
	}
	$delete_list_id=array();
	outbox_display();		
} elseif ($_REQUEST['action']=='deleteone') {
	$delete_list_id=array();
	$id=Security::remove_XSS($_GET['id']);
	MessageManager::delete_message_by_user_sender(api_get_user_id(),$id);
	$delete_list_id=array();		
	outbox_display();	
}else {
	outbox_display();	
}
/*
==============================================================================
		FOOTER 
==============================================================================
*/
if ($request===false) {
	Display::display_footer();
}
?>