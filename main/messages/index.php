<?php // $Id: index.php 20962 2009-05-25 03:15:53Z iflorespaz $
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
$language_file= 'messages';
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'message.lib.php';
api_block_anonymous_users();

if (api_get_setting('allow_message_tool')!='true'){
	api_not_allowed();
}

if(api_get_user_id()!=0) {
	echo '<script language="javascript" type="text/javascript" src="'.api_get_path(WEB_CODE_PATH).'messages/cookies.js"> </script> ';
	echo '<script language="javascript" type="text/javascript">set_url("'.api_get_path(WEB_CODE_PATH).'messages/notify.php") ; notificar()</script> ';
	$number_of_new_messages = get_new_messages();
	if(is_null($number_of_new_messages)) {
		$number_of_new_messages = 0;
	}
	echo "<a href=inbox.php>".get_lang('Inbox')."(<span id=\"nuevos\" style=\"none\">".$number_of_new_messages."</span>)</a>";
	echo " - ";
	echo "<a href=new_message.php>".get_lang('ComposeMessage')."</a>";
	if($number_of_new_messages > 0)
	{
	?>
		<div id="box" class="message-content-table">
		  <table width="100%" height="100%" border="0" cellpadding="0" cellspacing="2" id="table" class="content">
		    <tr>
		      <td width="28%" height="16" class="content" id="ref"><a style="color:red;font-size:10px" href="javascript:;" onclick="ocultar_aviso()"><?php echo get_lang('Close');?></a></td>
		      <td width="72%" rowspan="2" class="content" id="ref"><?php echo '<a href="'.$e.'" style="color:#000000" onclick="ocultar_aviso()">'.get_lang('YouHaveNewMessage').'</a>'; ?></td>
		    </tr>
		    <tr>
		      <td class="content" id="ref"><?php Display::return_icon('message_new.gif',get_lang('NewMessage'));?> </td>
		    </tr>
		  </table>
		</div>
	<?php
	}
} else {
	echo '<script language="javascript" type="text/javascript" src="'.api_get_path(WEB_CODE_PATH).'messages/cookies.js"> </script>';
	echo '<script language="javascript" type="text/javascript">Set_Cookie( "nuevos", 0, 0, "/","","")</script> ';
}
?>