<?php
/* For licensing terms, see /license.txt */
/**
 * This script contains the code to edit and send an e-mail to one of
 * the platform's users.
 * It can be called from the JavaScript library email_links.lib.php which
 * overtakes the mailto: links to use the internal interface instead.
 * @author	Yannick Warnier <ywarnier@beeznest.org>
 */

// name of the language file that needs to be included
$language_file = "index";

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';
if (empty($_user['user_id'])) {
	api_not_allowed(true);
}

//api_protect_course_script(); //not a course script, so no protection

if (empty($_SESSION['origin_url'])) {
	$origin_url = $_SERVER['HTTP_REFERER'];
	api_session_register('origin_url');
}

/* Process the form and redirect to origin */
if (!empty($_POST['submit_email']) && !empty($_POST['email_title']) && !empty($_POST['email_text'])) {
	$text = Security::remove_XSS($_POST['email_text'])."\n\n---\n".get_lang('EmailSentFromDokeos')." ".api_get_path(WEB_PATH);
	$email_administrator=Security::remove_XSS($_POST['dest']);
	$user_id=api_get_user_id();
	$title=Security::remove_XSS($_POST['email_title']);
	$content=Security::remove_XSS($_POST['email_text']);
	if (!empty($_user['mail'])) {
		api_mail('',$email_administrator,$title,$text,api_get_person_name($_user['firstname'],$_user['lastname']), $_user['mail']);
		UserManager::send_message_in_outbox ($email_administrator,$user_id,$title, $content);
	} else {
		api_mail('',$email_administrator,$title,$text,get_lang('Anonymous'));
	}
	$orig = $_SESSION['origin_url'];
	api_session_unregister('origin_url');
	header('location:'.$orig);
}

/* Header */
Display::display_header(get_lang('SendEmail'));

?>
<table border="0">
<form action="" method="POST">
<input type="hidden" name="dest" value="<?php echo Security::remove_XSS($_REQUEST['dest']);?>" />
	<tr>
		<td>
			<label for="email_address"><?php echo get_lang('EmailDestination');?></label>
		</td>
		<td>
			<span id="email_address"><?php echo Security::remove_XSS($_REQUEST['dest']); ?></span>
		</td>
	</tr>
	<tr>
		<td>
			<label for="email_title"><?php echo get_lang('EmailTitle');?></label>
		</td>
		<td>
			<input name="email_title" id="email_title" value="<?php echo Security::remove_XSS($_POST['email_title']);?>" size="60"></input>
		</td>
	</tr>
	<tr>
		<td valign="top">
			<label for="email_text"><?php echo get_lang('EmailText');?></label>
		</td>
		<td>
			<?php
			  echo '<textarea id="email_text" name="email_text" rows="10" cols="80">'.Security::remove_XSS($_POST['email_text']).'</textarea>';
			  //htmlarea is not used otherwise we have to deal with HTML e-mail and all the related probs
			  //api_disp_html_area('email_text',$_POST['email_text'],'250px');
			?>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<button class="save" type="submit" name="submit_email" value="<?php echo get_lang('SendMail');?>"><?php echo get_lang('SendMail');?></button>
		</td>
	</tr>
</form>
</table>

<?php

/* Footer */
Display::display_footer();
?>
