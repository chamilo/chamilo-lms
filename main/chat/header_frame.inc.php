<?php
/* For licensing terms, see /license.txt */

/**
 *	Header of each frame of the Chat tool
 *
 *	@author Olivier Brouckaert
 *  @modified by Denes Nagy
 *	@package chamilo.chat
 */

if (!defined('FRAME')) {
	exit();
}

$bodyXtra = 'dir="'.api_get_text_direction().'" ';

if (FRAME == 'hidden') {
	$bodyXtra .= 'onload="javascript: updateChat(); updateConnected(); setTimeout(\'submitHiddenForm();\', 5000);"';
    // Change timeout to change refresh time of the chat window
} elseif (FRAME == 'message') {
	$bodyXtra .= 'onload="javascript: eventMessage();"';
}

/*
 * Choose CSS style (platform's, user's, or course's)
 */

$platform_theme = api_get_setting('stylesheets'); 	// plataform's css
$my_style = $platform_theme;
if (api_get_setting('user_selected_theme') == 'true') {
	$useri = api_get_user_info();
	$user_theme = $useri['theme'];
	if (!empty($user_theme) && $user_theme != $my_style) {
		$my_style = $user_theme;					// user's css
	}
}

$mycourseid = api_get_course_id();

if (!empty($mycourseid) && $mycourseid != -1) {
	if (api_get_setting('allow_course_theme') == 'true') {
		$mycoursetheme = api_get_course_setting('course_theme');
		if (!empty($mycoursetheme) && $mycoursetheme != -1) {
			if (!empty($mycoursetheme) && $mycoursetheme != $my_style) {
				$my_style = $mycoursetheme;			// course's css
			}
		}
	}
}

if (empty($mycourseid)) {
	// If it is not set $mycourse id we reload the chat_message window in order to hide the textarea to submit a message.
	echo '<script type="text/javascript" language="javascript">';
	echo "parent.chat_message.location.href='chat_whoisonline.php?".api_get_cidreq()."';";
	echo '</script>';
}

?><!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo api_get_language_isocode(); ?>" lang="<?php echo api_get_language_isocode(); ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo api_get_system_encoding(); ?>">
<title>Chat</title>
<link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CSS_PATH).$my_style; ?>/default.css">
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
	if ('<?php echo $chat_size_old; ?>' != '<?php echo $chat_size_new; ?>')
	{
		parent.chat_chat.location.href='chat_chat.php?size=<?php echo $chat_size_new.'&cidReq='.$_GET['cidReq']; ?>#bottom';
	}
}

function updateConnected()
{
	if ('<?php echo $connected_old; ?>' != '<?php echo $connected_new; ?>')
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
	parent.chat_chat.location.href='chat_chat.php?size=<?php echo $chat_size.'&cidReq='.$_GET['cidReq']; ?>#bottom';
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