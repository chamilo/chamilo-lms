<?php 
//require_once('main/inc/global.inc.php'); 
require_once('language.php');
if (isset($_GET['loginFailed'])){
  if (isset($_GET['error'])) {
    switch ($_GET['error']) {
    case 'account_expired':
      $error_message = cblue_get_lang('AccountExpired');
      break;
    case 'account_inactive':
      $error_message = cblue_get_lang('AccountInactive');
      break;
    case 'user_password_incorrect':
      $error_message = cblue_get_lang('InvalidId');
      break;
    case 'access_url_inactive':
      $error_message = cblue_get_lang('AccountURLInactive');
      break;
    default : 
      $error_message = cblue_get_lang('InvalidId');
    }
  } else { 
    $error_message = get_lang('InvalidId');
  }
}

    $rootWeb = api_get_path('WEB_PATH');
?>
<html>
<head>
	<title>Custompage - login</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<!--[if !IE 6]><!-->
	<link rel="stylesheet" type="text/css" href="<?php echo $rootWeb ?>custompages/style.css" />
	<!--<![endif]-->
	<!--[if IE 6]>
	<link rel="stylesheet" type="text/css" href="/custompages/style-ie6.css" />
	<![endif]-->

	<script type="text/javascript" src="<?php echo $rootWeb ?>custompages/jquery-1.5.1.min.js"></script>
        
	<script type="text/javascript">
		$(document).ready(function() {
			if (top.location != location) 
				top.location.href = document.location.href ;

			// Handler pour la touche retour
			$('input').keyup(function(e) { 
				if (e.keyCode == 13) {
					$('#login-form').submit();
				}
			});
		});
	</script>
</head>
<body>
	<div id="backgroundimage">
		<img src="<?php echo api_get_path(WEB_PATH)?>/custompages/images/page-background.png" class="backgroundimage" />
	</div>
	<div id="wrapper">
		<div id="header">
			<img src="<?php echo api_get_path(WEB_PATH)?>/custompages/images/header.png" alt="Logo" />
		</div> <!-- #header -->
		<div id="login-form-box" class="form-box">
      <div id="login-form-info" class="form-info">
        <?php if (isset($content['info']) && !empty($content['info'] )) {
          echo $content['info'];
        }?>
      </div>
		<?php if (isset($error_message)) {
			echo '<div id="login-form-info" class="form-error">'.$error_message.'</div>';
		}?> 
			<form id="login-form" class="form" action="<?php echo api_get_path(WEB_PATH)?>index.php" method="post">
				<div>
        <label for="login">*<?php echo cblue_get_lang('User');?></label>
					<input name="login" type="text" /><br />
          <label for="password">*<?php echo cblue_get_lang('langPass');?></label>
					<input name="password" type="password" /><br />
				</div>
			</form>
			<div id="login-form-submit" class="form-submit" onclick="document.forms['login-form'].submit();">
      <span><?php echo cblue_get_lang('LoginEnter');?></span>
			</div> <!-- #form-submit -->
			<div id="links">
      <a href="<?php echo api_get_path(WEB_PATH)?>main/auth/inscription.php"><?php echo cblue_get_lang('langReg')?></a><br />
      <a href="<?php echo api_get_path(WEB_PATH)?>main/auth/lostPassword.php"><?php echo cblue_get_lang('langLostPassword')?></a>
			</div>
		</div> <!-- #form -->
		<div id="footer">
			<img src="<?php echo api_get_path(WEB_PATH)?>/custompages/images/footer.png" />
		</div> <!-- #footer -->
	</div> <!-- #wrapper -->
        
</body>
</html>
