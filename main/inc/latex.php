<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>LaTeX Code</title>
</head>

<body>
<?php // $Id: latex.php,v 1.1 2006/04/05 07:18:46 pcool Exp $
/* For licensing terms, see /license.txt */

/*		INIT SECTION */

include '../inc/global.inc.php';

/*		FUNCTIONS */

// put your functions here
// if the list gets large, divide them into different sections:
// display functions, tool logic functions, database functions
// try to place your functions into an API library or separate functions file - it helps reuse

/*		MAIN CODE */

$code = Security::remove_XSS($_GET['code']);

echo '<div id="latex_code">';
echo '<h3>'.get_lang('LatexCode').'</h3>';
echo stripslashes($code);
echo '</div>';

echo '<div id="latex_image">';
echo '<h3>'.get_lang('LatexFormula').'</h3>';
echo '<img src="'.api_get_path(WEB_COURSE_PATH).$_course['path'].'/temp/'.$code.'" alt="'.get_lang('LatexCode').'"/>';
echo '</div>';

/*		FOOTER */

?>
</body>
</html>