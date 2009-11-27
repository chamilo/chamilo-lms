<?php // $Id: online_goto.php 21112 2009-05-30 17:57:24Z cfasanando $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	Shows a link in the right frame of the Online conference tool
*
*	@author Olivier Brouckaert
*	@package dokeos.online
==============================================================================
*/

header('Expires: Wed, 01 Jan 1990 00:00:00 GMT');
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

$url=$_GET['url'];

$url = str_ireplace('javascript','',$url);

?>

<html>
<head>
</head>
<body style="margin:0px; padding:0px;">
<iframe src="<?php echo htmlspecialchars($url); ?>" width="100%" height="100%" marginwidth="0" marginheight="0" frameborder="0"></iframe>
</body>
</html>