<?php 
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2009 Dokeos SPRL
	Copyright (c) 2009 Julio Montoya Armas <gugli100@gmail.com>
	Copyright (c) Facultad de Matematicas, UADY (México)
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
$language_file= 'messages';
$cidReset=true;
include_once ('../inc/global.inc.php');
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

$nameTools = get_lang('Messages');
Display::display_header($nameTools,get_lang('Inbox'));
api_display_tool_title(get_lang('Inbox'));

$table_message = Database::get_course_table(TABLE_MESSAGE);

echo '<div class=actions>';
echo '<a href="new_message.php">'.Display::return_icon('message_new.png',get_lang('ComposeMessage')).get_lang('ComposeMessage').'</a>';
echo '</div>';

if(!isset($_GET[del_msg])) {
	inbox_display();
} else {
	$num_msg = $_POST['total'];
	for ($i=0;$i<$num_msg;$i++) {
		if($_POST[$i]) {
			//the user_id was necesarry to delete a message??
			delete_message_by_user_receiver(api_get_user_id(), $_POST['_'.$i]);			
		}
	}
	inbox_display();
}

/*
==============================================================================
		FOOTER 
==============================================================================
*/ 
Display::display_footer();
?>