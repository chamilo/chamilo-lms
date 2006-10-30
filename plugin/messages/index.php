<?php // $Id: index.php 9847 2006-10-30 12:52:12Z evie_em $
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
include_once('functions.inc.php');
$e= api_get_path(WEB_PLUGIN_PATH).'messages/inbox.php';	
$e2= api_get_path(WEB_PLUGIN_PATH).'messages/new_message.php';
$inb = get_lang("Inbox");
$newm = get_lang("ComposeMessage");
if(api_get_user_id())
{
	/*$query = "CREATE TABLE IF NOT EXISTS `".MESSAGES_DATABASE."` (".
		"`id` VARCHAR(150) NOT NULL,".
		"`id_rem` INT( 10 ) NOT NULL ,".
		"`id_reciv` INT( 10 ) NOT NULL ,".
		"`status` BOOL NOT NULL,".
		"`fecha` DATETIME NOT NULL ,".
		"`contenido` TEXT NOT NULL,".
		"INDEX ( `id`,`id_reciv` )". 
		") TYPE = MYISAM ;";*/
	$query = "CREATE TABLE IF NOT EXISTS `".MESSAGES_DATABASE."` (".
		"`id` VARCHAR(150) NOT NULL,".
		"`id_sender` INT( 10 ) NOT NULL ,".
		"`id_receiver` INT( 10 ) NOT NULL ,".
		"`status` BOOL NOT NULL,".
		"`date` DATETIME NOT NULL ,".
		"`title` VARCHAR(255) NOT NULL,".
		"`content` TEXT NOT NULL,".
		"INDEX ( `id`,`id_receiver` )". 
		") TYPE = MYISAM ;";
	@api_sql_query($query,__FILE__,__LINE__);
	?>
<style type="text/css">
		#box.aviso
			{
				
				position:absolute;
				width:200px;
				height:60px;
				z-index:3;				
				
				visibility:hidden;
				top:85px;
				left:10px;
				margin: 0px;
				padding: 0px;
			}
		#table.content
		{
		border: 1px solid #4171B5;
		height:60px;
		background-color:#DBEAF5;
		filter:alpha(opacity=90);
		opacity: 0.90;
		}
		#ref.content
		{
			text-align:center;
		}


	</style>
<?php

	echo '<script language="javascript" type="text/javascript" src="'.api_get_path(WEB_PLUGIN_PATH).'messages/cookies.js"> </script> ';
	echo '<script language="javascript" type="text/javascript">set_url("'.api_get_path(WEB_PLUGIN_PATH).'messages/notify.php") ; notificar()</script> ';
		
	echo "- ";
	echo "<a href=$e>".$inb."(<span id=\"nuevos\" style=\"none\">".get_new_messages()."</span>)</a>";
	echo " - ";
	echo "<a href=$e2>".$newm."</a>";
	?>
<div class="aviso" id="box"> 
  <table width="100%" height="100%" border="0" cellpadding="0" cellspacing="2" id="table" class="content"> 
    <tr> 
      <td width="28%" height="16" class="content" id="ref"><a style="color:red;font-size:10px" href="javascript:;" onclick="ocultar_aviso()">Close</a></td> 
      <td width="72%" rowspan="2" class="content" id="ref"><?php echo '<a href="'.$e.'" style="color:#000000" onclick="ocultar_aviso()">'.get_lang('YouHaveNewMessage').'</a>'; ?></td> 
    </tr> 
    <tr> 
      <td class="content" id="ref"><?php echo'<img src="'.api_get_path(WEB_PLUGIN_PATH).'messages/images/newmsg.gif" alt="new message" align="middle" class="images"></p>';?> </td> 
    </tr> 
  </table> 
</div> 
<?php
}
else
{
echo '<script language="javascript" type="text/javascript" src="'.api_get_path(WEB_PLUGIN_PATH).'messages/cookies.js"> </script>'; 
echo '<script language="javascript" type="text/javascript">Set_Cookie( "nuevos", 0, 0, "/","","")</script> ';
}
?>