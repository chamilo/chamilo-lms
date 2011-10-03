<?php 
require_once('language.php');
require_once('../inc/global.inc.php');
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(CONFIGURATION_PATH).'profile.conf.php';
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';
//Removes some unwanted elementend of the form object
$content['form']->removeElement('extra_mail_notify_invitation');
$content['form']->removeElement('extra_mail_notify_message');
$content['form']->removeElement('extra_mail_notify_group_message');
$content['form']->removeElement('official_code');
$content['form']->removeElement('phone');
$content['form']->removeElement('submit');
$content['form']->removeElement('status');
$content['form']->removeElement('status');

?>
<html>
<head>
	<title>Registration</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<!--[if !IE 6]><!-->
	<link rel="stylesheet" type="text/css" href="../../custompages/style.css" />
	<!--<![endif]-->
	<!--[if IE 6]>
	<link rel="stylesheet" type="text/css" href="../../custompages/style-ie6.css" />
	<![endif]-->
	<script type="text/javascript" src="../../custompages/jquery-1.5.1.min.js"></script>
</head>
<body>
	<div id="backgroundimage">
		<img src="/custompages/images/page-background.png" class="backgroundimage" />
	</div>
	<div id="wrapper">
		<div id="header">
			<img src="../../custompages/images/header.png" alt="Ambassador logo" />
		</div> <!-- #header -->
		<div id="registration-form-box" class="form-box">
		<?php if (isset($form_error) && !empty($form_error)) {
			echo '<div id="registration-form-error" class="form-error"><ul>'.$form_error.'</ul></div>';
		}?>
      <?php
      $content['form']->display();
      ?>
			<div id="registration-form-submit" class="form-submit" onclick="document.forms['registration'].submit();">
				<span><?php echo cblue_get_lang('Subscribe');?></span>
			</div> <!-- #form-submit -->
			<div id="links">
      <!--<a href="mailto: support@cblue.be"><?php echo cblue_get_lang('NeedContactAdmin')?></a><br />-->
			</div>
		</div> <!-- #form -->
		<div id="footer">
			<img src="../../custompages/images/footer.png" />
		</div> <!-- #footer -->
	</div> <!-- #wrapper -->
</body>
</html>
