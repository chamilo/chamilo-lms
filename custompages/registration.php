<?php 
require_once('../inc/global.inc.php');
require_once('../inc/lib/group_portal_manager.lib.php');
require_once('language.php');
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
			<form id="registration-form" class="form" action="inscription.php" method="post">
				<div>
        <label for="email"><?php echo get_lang('langEmail');?>*</label>
					<input name="email" type="text"  value="<?php echo $values['email']?>" /><br />
					<label for="username"><?php echo get_lang('Username');?>*</label>
					<input name="username" type="text" value="<?php echo $values['username']?>" /><br />
          <p class="forminfo"><?php echo get_lang('UsernameWrong')?></p>
					<label for="pass1"><?php echo get_lang('Pass');?>*</label>
					<input name="pass1" type="password" value="<?php echo $values['pass1']?>" /><br />
					<label for="pass2"><?php echo get_lang('Confirmation');?>*</label>
					<input name="pass2" type="password"  value="<?php echo $values['pass2']?>" /><br />
					<!--
					<label for="phone">*Phone number</label>
					<input name="phone" type="text" /><br />
					-->
          <input name="language" type="hidden" value="<?php echo $_SESSION['user_language_choice']?>" />
					<input name="status" type="hidden" value="5" /> <!-- learner -->
				</div>
			</form>
			<div id="registration-form-submit" class="form-submit" onclick="document.forms['registration-form'].submit();">
				<span><?php echo get_lang('Subscribe');?></span>
			</div> <!-- #form-submit -->
			<div id="links">
      <!--<a href="mailto: <?php echo api_get_setting('emailAdministrator'); ?>"><?php echo get_lang('NeedContactAdmin')?></a><br />-->
			</div>
		</div> <!-- #form -->
		<div id="footer">
			<img src="../../custompages/images/footer.png" />
		</div> <!-- #footer -->
	</div> <!-- #wrapper -->
</body>
</html>
