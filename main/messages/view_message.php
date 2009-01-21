<?php // $Id: view_message.php 17903 2009-01-21 19:50:57Z juliomontoya $
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
=========================================================================5====
*/

// name of the language file that needs to be included
$language_file= 'messages';
include_once('../inc/global.inc.php');
api_block_anonymous_users();
if (api_get_setting('allow_message_tool')!='true'){
	api_not_allowed();
}


require_once(api_get_path(LIBRARY_PATH).'message.lib.php');
$nameTools = get_lang('Messages');

$interbredcrump[]=array('url' => 'inbox.php','name' => get_lang('Inbox'));
Display::display_header($nameTools,get_lang('Messages'));

api_display_tool_title(get_lang('ReadMessage'));

$table_message = Database::get_course_table(TABLE_MESSAGE);

$query = "UPDATE $table_message SET msg_status = '0' WHERE user_receiver_id=".api_get_user_id()." AND id='".Database::escape_string($_GET['id'])."';";
$result = api_sql_query($query,__FILE__,__LINE__);

$query = "SELECT * FROM $table_message WHERE user_receiver_id=".api_get_user_id()." AND id='".Database::escape_string($_GET['id'])."';";
$result = api_sql_query($query,__FILE__,__LINE__);
$row = Database::fetch_array($result);

$user_con = users_connected_by_id();
$band=0;
$reply='';
for($i=0;$i<count($user_con);$i++)
	if($row[1]==$user_con[$i])
		$band=1;	
if($band==1)
	$reply = '<a href="new_message.php?re_id='.$_GET['id'].'">'.Display::return_icon('message_reply.png',get_lang('ReplyToMessage')).get_lang('ReplyToMessage').'</a>';
	
echo '<div class=actions>';
echo '<a href="inbox.php">&laquo;&nbsp;'.get_lang('BackToInbox').'</a>';
echo $reply; 
echo '<a href="inbox.php?action=deleteone&id='.$row[0].'"  onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang('ConfirmDeleteMessage')))."'".')) return false;">'.Display::return_icon('message_delete.png',get_lang('DeleteMessage')).''.get_lang('Delete').'</a>';
echo '</div><br />';

echo '
<table class="message_view_table" >
    <TR>
      <TD width=10>&nbsp; </TD>
      <TD vAlign=top width="100%">
      	<TABLE>      
            <TR>
              <TD width="100%">                              
                    <TR> <h1>'.$row[5].'</h1></TR>
              </TD>              		
              <TR>                       
              	<TD>'.get_lang('From').'&nbsp;<b>'.GetFullUserName($row[1],$mysqlMainDb).'</b> '.strtolower(get_lang('To')).'&nbsp;  <b>'.GetFullUserName($row[2],$mysqlMainDb).'</b> </TD>
              </TR>                    
              <TR>
              <TD >'.get_lang('Date').'&nbsp; '.$row[4].'</TD>                      
              </TR>              
            </TR>          
        </TABLE>	      		
        
        <br />
        <TABLE height=209 width="100%" bgColor=#ffffff>
          <TBODY>
            <TR>
              <TD vAlign=top>'.$row[6].'</TD>
            </TR>
          </TBODY>
        </TABLE>
        <DIV class=HT style="PADDING-BOTTOM: 5px"> </DIV></TD>
      <TD width=10>&nbsp;</TD>
    
    </TR>
</TABLE>';

 	
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>