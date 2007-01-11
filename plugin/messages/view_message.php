<?php // $Id: view_message.php 10675 2007-01-11 13:03:10Z bmol $
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
=========================================================================5====
*/

// name of the language file that needs to be included
$language_file= "messages";
include('../../main/inc/global.inc.php');
api_block_anonymous_users();
include('./functions.inc.php');
$nameTools = get_lang("Messages");
$interbredcrump[]=array("url" => "inbox.php","name" => get_lang("Inbox"));
Display::display_header($nameTools,"messages");

$query = "UPDATE `".MESSAGES_DATABASE."` SET `status` = '0' WHERE `id_receiver`=".api_get_user_id()." AND `id`='".mysql_real_escape_string($_GET['id'])."';";
$result = api_sql_query($query,__FILE__,__LINE__);

$query = "SELECT * FROM `".MESSAGES_DATABASE."` WHERE id_receiver=".api_get_user_id()." AND id='".mysql_real_escape_string($_GET['id'])."';";
$result = api_sql_query($query,__FILE__,__LINE__);
$row = mysql_fetch_array($result);

echo '
<TABLE cellSpacing=0 cellPadding=0 width="100%" bgColor=#dbeaf5 border=0>
  <TBODY>
    <TR>
      <TD width=10>&nbsp; </TD>
      <TD vAlign=top width="100%">
        <TABLE cellSpacing=0 cellPadding=0 width="100%" border=0>
          <TBODY>
            <TR>
              <TD class=HT vAlign=top width="100%">
                <TABLE class=TH>
                  <TBODY>
                    <TR>
                      <TD noWrap>'.get_lang("From").':&nbsp;</TD>
                      <TD>'.GetFullUserName($row[1],$mysqlMainDb).'</TD>
                    </TR>
                    <TR>
                      <TD noWrap>'.get_lang("Date").'&nbsp;</TD>
                      <TD>'.$row[4].'</TD>
                    </TR>
                    <TR>
                      <TD noWrap>'.get_lang("To").':&nbsp; </TD>
                      <TD>'.GetFullUserName($row[2],$mysqlMainDb).'</TD>
                    </TR>
                    <TR>
                      <TD noWrap>'.get_lang("Title").':&nbsp; </TD>
                      <TD>'.$row[5].'</TD>
                    </TR>
                    <TR>
                      <TD style="PADDING-BOTTOM: 0px"></TD>
                      <TD style="PADDING-BOTTOM: 0px"
            width="100%"></TD>
                    </TR>
                  </TBODY>
              </TABLE></TD>
              <TD class=HT vAlign=top align=right>&nbsp; </TD>
            </TR>
          </TBODY>
        </TABLE>
        <TABLE cellSpacing=0 cellPadding=0 width="100%" border=0>
          <TBODY>
            <TR>
              <TD style="PADDING-BOTTOM: 5px" width="100%"><HR border="1"></TD>
            </TR>
          </TBODY>
        </TABLE>
        <TABLE height=209 width="100%" bgColor=#ffffff>
          <TBODY>
            <TR>
              <TD vAlign=top>'.$row[6].'</TD>
            </TR>
          </TBODY>
        </TABLE>
        <DIV class=HT style="PADDING-BOTTOM: 5px"> </DIV></TD>
      <TD width=10>&nbsp;</TD>
      <TD vAlign=top width=160></TD>
    </TR>
  </TBODY>
</TABLE>
<p><a href="inbox.php">'.get_lang("BackToInbox").'</a>
';
$user_con = users_connected_by_id();
$band=0;
for($i=0;$i<count($user_con);$i++)
if($row[1]==$user_con[$i])
$band=1;
if($band==1)
 echo '- <a href="new_message.php?re_id='.$_GET['id'].'">'.get_lang("ReplyToMessage").'</a>  </p>';
 else
 echo "</a>  </p>";
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>