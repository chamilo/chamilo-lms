<?php
require_once('../../main/inc/global.inc.php'); 
require_once('language.php');
?>
<html>
<head>
	<title>Password recovery</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<!--[if !IE 6]><!-->
	<link rel="stylesheet" type="text/css" href="../../custompages/style.css" />
	<!--<![endif]-->
	<!--[if IE 6]>
	<link rel="stylesheet" type="text/css" href="../../custompages/style-ie6.css" />
	<![endif]-->
	<script type="text/javascript" src="../../custompages/jquery-1.5.1.min.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			// Handler pour la touche retour
			$('input').keyup(function(e) { 
				if (e.keyCode == 13) {
					$('#lostpassword-form').submit();
				}
			});
		});
	</script>
</head>
<body>
	<div id="backgroundimage">
		<img src="/custompages/images/page-background.png" class="backgroundimage" />
	</div>
	<div id="wrapper">
		<div id="header">
			<img src="../../custompages/images/header.png" alt="Ambassador logo" />
		</div> <!-- #header -->
      <div id="registration-form-info" class="form-info">
        <?php if(isset($content['error']) && !empty($content['error'])){
          echo $content['info'];
        } else {
          echo cblue_get_lang('lang_enter_email_and_well_send_you_password');
        }?>
      </div>
		<div id="lostpassword-form-box" class="form-box">
		<?php if (isset($content['error']) && !empty($content['error'])) {
			echo '<div id="registration-form-error" class="form-error"><ul>'.$content['error'].'</ul></div>';
		}?>
			<form id="lostpassword-form" class="form" action="lostPassword.php" method="post">
				<div>
        <label for="user">*<?php echo cblue_get_lang('UserName');?></label>
					<input name="user" type="text" /><br />
          <label for="email">*<?php echo cblue_get_lang('Email');?></label>
					<input name="email" type="text" /><br />
				</div>
			</form>
			<div id="lostpassword-form-submit" class="form-submit" onclick="document.forms['lostpassword-form'].submit();">
      <span><?php echo cblue_get_lang('langSend'); ?> </span>
			</div> <!-- #form-submit -->
		</div> <!-- #form -->
		<div id="footer">
			<img src="../../custompages/images/footer.png" />
		</div> <!-- #footer -->
	</div> <!-- #wrapper -->
</body>
</html>
