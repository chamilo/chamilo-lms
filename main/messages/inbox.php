<?php 
// $Id: inbox.php 10204 2006-11-26 20:46:53Z pcool $
/*
==============================================================================
	Dokeos - elearning and course management software
	
	Copyright (c) Facultad de Matematicas, UADY (México)
	Copyright (c) Evie, Free University of Brussels (Belgium)
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/*
==============================================================================
		INIT SECTION
==============================================================================
*/ 
// name of the language file that needs to be included 
$language_file= "messages";
$cidReset=true;
include('../../main/inc/global.inc.php');
include('./functions.inc.php');
api_block_anonymous_users();
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
Display::display_header($nameTools,"Inbox");
api_display_tool_title($nameTools);
echo $_SESSION['prueba'];
if(!isset($_GET[del_msg]))
	inbox_display();
else
{
	$num_msg = $_POST['total'];
	for ($i=0;$i<$num_msg;$i++)
	{
		if($_POST[$i])
		{
			$query = "DELETE FROM `".MESSAGES_DATABASE."` WHERE id_receiver=".$_SESSION['_uid']." AND id='".$_POST['_'.$i]."';";
			api_sql_query($query,__FILE__,__LINE__);
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