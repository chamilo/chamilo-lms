<?php
/* For licensing terms, see /license.txt */
/**
 * Quick form to ask for password reminder.
 * @package chamilo.custompages
 */

require_once api_get_path(SYS_PATH).'main/inc/global.inc.php';
require_once __DIR__.'/language.php';

$rootWeb = api_get_path('WEB_PATH');
?>
<html>
<head>
	<title><?php echo custompages_get_lang('LostPassword'); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<!--[if !IE 6]><!-->
	<link rel="stylesheet" type="text/css" href="../../custompages/style.css" />
	<!--<![endif]-->
	<!--[if IE 6]>
	<link rel="stylesheet" type="text/css" href="../../custompages/style-ie6.css" />
	<![endif]-->
	<script type="text/javascript" src="<?php echo $rootWeb ?>web/assets/jquery/jquery.min.js"></script>

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
		<div id="lostpassword-form-box" class="form-box">
            <?php
            if (isset($content['info']) && !empty($content['info'])) {
                echo '<div id="registration-form-error" class="form-error"><ul>'.$content['info'].'</ul></div>';
            }

            echo isset($content['form']) ? $content['form'] : ''
            ?>
		</div> <!-- #form -->
		<div id="footer">
			<img src="../../custompages/images/footer.png" />
		</div> <!-- #footer -->
	</div> <!-- #wrapper -->
</body>
</html>
