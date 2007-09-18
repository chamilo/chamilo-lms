<?php // $Id: showinframes.php 13074 2007-09-18 16:06:35Z elixir_inter $ 
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Hugues Peeters
	Copyright (c) Roan Embrechts
	
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
/**
============================================================================== 
*	This file will show documents in a separate frame.
*	We don't like frames, but it was the best of two bad things.
*
*	display html files within Dokeos - html files have the Dokeos header.
*
*	--- advantages ---
*	users "feel" like they are in Dokeos,
*	and they can use the navigation context provided by the header.
*
*	--- design ---
*	a file gets a parameter (an html file)
*	and shows
*	- dokeos header
*	- html file from parameter
*	- (removed) dokeos footer
*
*	@version 0.6
*	@author Roan Embrechts (roan.embrechts@vub.ac.be)
*	@package dokeos.document
==============================================================================
*/
	
/*
============================================================================== 
	   DOKEOS INIT 
============================================================================== 
*/ 

include('../inc/global.inc.php');

$_SESSION['whereami'] = 'document/view';
	
$interbreadcrumb[]= array ("url"=>"./document.php", "name"=> get_lang("Documents"));
$nameTools = get_lang("Documents");

$file = $_GET['file'];

/*
============================================================================== 
		Main section
============================================================================== 
*/ 
header('Expires: Wed, 01 Jan 1990 00:00:00 GMT');
//header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Last-Modified: Wed, 01 Jan 2100 00:00:00 GMT');
		
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

$browser_display_title = "Dokeos Documents - " . $_GET['cidReq'] . " - " . $_GET['file'];

//only admins get to see the "no frames" link in pageheader.php, so students get a header that's not so high
$frameheight = 130;
if($is_courseAdmin)
{
	$frameheight = 150;
}

?>
<html>
<head>
<title><?php echo $browser_display_title;?></title>
</head>
	<frameset rows="<?php echo $frameheight; ?>,*" border="0" frameborder="no" >
		<frame name="top" scrolling="no" noresize target="contents" src="headerpage.php?file=<?php echo urlencode($_GET['file']); ?>">
		<frame name="main" src="<?php echo api_get_path('WEB_COURSE_PATH').$_course['path'].'/document'.str_replace('%2F', '/',urlencode($_GET['file'])).'?'.api_get_cidreq().'&rand='.mt_rand(1,10000); ?>">
	<noframes>
	<body>
		<p>This page uses frames, but your browser doesn't support them.<br/>
			We suggest you try Mozilla, Firefox, Safari, Opera, or other browsers updated this millenium.</p>
	</body>
	</noframes>
	</frameset>
</html>