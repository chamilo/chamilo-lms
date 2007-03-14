<?php // $Id: header_frame.inc.php,v 1.2 2005/05/01 11:49:16 darkden81 Exp $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	Header of each frame of the Chat tool
*
*	@author Olivier Brouckaert
*   @modified by Denes Nagy
*	@package dokeos.chat
==============================================================================
*/

if(!defined('FRAME'))
{
	exit();
}

$bodyXtra='';

if(FRAME == 'hidden')
{
	$bodyXtra='onload="javascript:updateChat(); updateConnected(); setTimeout(\'submitHiddenForm();\',5000);"';
}
elseif(FRAME == 'message')
{
	$bodyXtra='onload="javascript:eventMessage();"';
}

header('Content-Type: text/html; charset="UTF-8"');

?><!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>">
<title>Chat</title>

<link rel="stylesheet" type="text/css" href="../css/<?php if(api_get_setting('stylesheets')=="") echo 'default'; else echo api_get_setting('stylesheets'); ?>/default.css">

<style>
	a{
		font-size: 12px;
	}
	
	.background_submit{
		background: url(../img/chat_little.gif) 2px 2px no-repeat;
		padding: 2px 1px 1px 20px;
	}
	TH{
		font-size: 12px;
	}
</style>

<script type="text/javascript" language="javascript">
<!--
function updateChat()
{
	if('<?php echo $chat_size_old; ?>' != '<?php echo $chat_size_new; ?>')
	{
		parent.chat_chat.location.href='chat_chat.php?size=<?php echo $chat_size_new; ?>#bottom';
	}
}

function updateConnected()
{
	if('<?php echo $connected_old; ?>' != '<?php echo $connected_new; ?>')
	{
		parent.chat_whoisonline.location.href='chat_whoisonline.php?size=<?php echo $connected_new; ?>';
	}
}

function submitHiddenForm()
{
	document.formHidden.submit();
}

function eventMessage()
{
	<?php if($chat_size): ?>
	parent.chat_hidden.document.formHidden.chat_size_old.value='<?php echo $chat_size; ?>';
	parent.chat_chat.location.href='chat_chat.php?size=<?php echo $chat_size; ?>#bottom';
	<?php endif; ?>

	document.formMessage.message.focus();
}

function send_message(evenement){

    for (prop in evenement)
    {
    	if(prop == 'which') touche = evenement.which; else touche = evenement.keyCode;
    }
    
    if (touche == 13)
    {
    	document.formMessage.submit();
    }

}

//-->
</script>

</head>
<body <?php echo $bodyXtra; ?> >