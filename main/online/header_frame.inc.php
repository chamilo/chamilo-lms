<?php
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
*	Header of each frame of the Online conference tool
*
*	@author Olivier Brouckaert
*	@package dokeos.online
==============================================================================
*/

if(!defined('FRAME'))
{
	exit();
}

$bodyXtra='';

if(FRAME == 'hidden1')
{
	$bodyXtra='onload="javascript:'.($isMaster?'saveDocumentURL();':'getDocumentURL(); updateStreaming();').' updateChat(); updateConnected(); setTimeout(\'submitHiddenForm();\',5000);"';
}
elseif(FRAME == 'htmlarea')
{
	if($isMaster)
	{
		$bodyXtra='onload="javascript:setTimeout(\'saveHTMLareaContent();\',3000);"';
	}
}
elseif(FRAME == 'master' || FRAME == 'streaming')
{
	if($isMaster)
	{
		$bodyXtra='scroll="no"';
	}
}
elseif(FRAME == 'message')
{
	$bodyXtra='onload="javascript:eventMessage();"';
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<?php /* Encoding is directly set here, to be fixed. */ ?>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<title>Online Conference</title>

<?php
if(FRAME != 'htmlarea' || $isMaster)
{
?>

<link rel="stylesheet" type="text/css" href="../css/online.css">

<?php
}
?>

<script type="text/javascript">
/* <![CDATA[ */
function saveDocumentURL()
{
	document.formHidden.document.value=parent.online_working_area.location.href;
}

function getDocumentURL()
{
	currentDocument=parent.online_working_area.location.href;
	newDocument=document.formHidden.document.value;

	if(currentDocument != newDocument)
	{
		parent.online_working_area.location.href=newDocument;
	}
}

function updateChat()
{
	if('<?php echo $chat_size_old; ?>' != '<?php echo $chat_size_new; ?>')
	{
		parent.online_chat.location.href='online_chat.php?size=<?php echo $chat_size_new; ?>#bottom';
	}
}

function updateConnected()
{
	if('<?php echo $connected_old; ?>' != '<?php echo $connected_new; ?>')
	{
		parent.online_whoisonline.location.href='online_whoisonline.php?size=<?php echo $connected_new; ?>';
	}
}

function updateStreaming()
{
	if('<?php echo $streaming_old; ?>' != '<?php echo $streaming_new; ?>')
	{
		parent.online_master.location.href='online_master.php?md5=<?php echo $streaming_new; ?>';
	}
}

function submitHiddenForm()
{
	document.formHidden.submit();
}

function saveHTMLareaContent()
{
	document.formHTMLarea.onsubmit();

	document.formHTMLarea.submit();

	setTimeout('saveHTMLareaContent();',3000);
}

function eventMessage()
{
	<?php if($chat_size): ?>
	parent.online_hidden1.document.formHidden.chat_size_old.value='<?php echo $chat_size; ?>';
	parent.online_chat.location.href='online_chat.php?size=<?php echo $chat_size; ?>#bottom';
	<?php endif; ?>

	document.formMessage.message.focus();
}
/* ]]> */
</script>

</head>
<body <?php echo $bodyXtra; ?> >
