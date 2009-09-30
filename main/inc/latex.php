<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>LaTeX Code</title>
</head>

<body>
<?php // $Id: latex.php,v 1.1 2006/04/05 07:18:46 pcool Exp $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2006 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) Patrick Cool <patrick.cool@UGent.be>, Ghent University

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/


/*
==============================================================================
		INIT SECTION
==============================================================================
*/
include("../inc/global.inc.php");

/*
==============================================================================
		FUNCTIONS
==============================================================================
*/

// put your functions here
// if the list gets large, divide them into different sections:
// display functions, tool logic functions, database functions
// try to place your functions into an API library or separate functions file - it helps reuse

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

$code = Security::remove_XSS($_GET['code']);

echo '<div id="latex_code">';
echo '<h3>'.get_lang('LatexCode').'</h3>';
echo stripslashes($code);
echo '</div>';


echo '<div id="latex_image">';
echo '<h3>'.get_lang('LatexFormula').'</h3>';
echo '<img src="'.api_get_path(WEB_COURSE_PATH).$_course['path'].'/temp/'.$code.'" alt="'.get_lang('LatexCode').'"/>';
echo '</div>';
/*
==============================================================================
		FOOTER
==============================================================================
*/
?>
</body>
</html>