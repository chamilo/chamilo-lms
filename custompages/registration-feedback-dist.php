<?php
/* For licensing terms, see /license.txt */
/**
 * Quick display for user registration
 * @package chamilo.custompages
 */ 
/**
 * HTML output
 */
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
	<script type="text/javascript" src="../../main/inc/lib/javascript/jquery.min.js"></script>
</head>
<body>
	<div id="backgroundimage">
		<img src="/custompages/images/page-background.png" class="backgroundimage" />
	</div>
	<div id="wrapper">
		<div id="header">
			<img src="../../custompages/images/header.png" alt="Ambassador logo" />
		</div> <!-- #header -->
		<div id="registration-feedback-box">
      <?php
      echo $content['info'];
      ?>
		</div> <!-- #feedback -->
		<div id="footer">
			<img src="../../custompages/images/footer.png" />
		</div> <!-- #footer -->
	</div> <!-- #wrapper -->
</body>
</html>
