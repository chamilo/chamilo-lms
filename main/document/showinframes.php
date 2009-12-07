<?php // $Id: showinframes.php 22177 2009-07-16 22:30:39Z iflorespaz $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos S.A.
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

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
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
$language_file[] = 'document';
require_once '../inc/global.inc.php';
require_once '../glossary/glossary.class.php';
if (!empty($_GET['nopages'])) {
	$nopages=Security::remove_XSS($_GET['nopages']);
	if ($nopages==1) {
		require_once api_get_path(INCLUDE_PATH).'reduced_header.inc.php';
		Display::display_error_message(get_lang('FileNotFound'));
	}
	exit;
}

$_SESSION['whereami'] = 'document/view';

$interbreadcrumb[]= array ('url'=>'./document.php', 'name'=> get_lang('Documents'));
$nameTools = get_lang('Documents');
$file = Security::remove_XSS(urldecode($_GET['file']));
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

$browser_display_title = "Dokeos Documents - " . Security::remove_XSS($_GET['cidReq']) . " - " . $file;

//only admins get to see the "no frames" link in pageheader.php, so students get a header that's not so high
$frameheight = 135;
if($is_courseAdmin) {
	$frameheight = 165;
}

$file_root=$_course['path'].'/document'.str_replace('%2F', '/',$file);
$file_url_sys=api_get_path(SYS_COURSE_PATH).$file_root;
$file_url_web=api_get_path(WEB_COURSE_PATH).$file_root;
$path_info= pathinfo($file_url_sys);
?>
<html>
<head>
<title>
<?php echo $browser_display_title;?>
</title>
<script language="javascript" src="<?= api_get_path(WEB_LIBRARY_PATH); ?>javascript/jquery.js"></script>
<script language="javascript" src="<?= api_get_path(WEB_LIBRARY_PATH); ?>javascript/jquery.frameready.js"></script>


<script type="text/javascript">
	<!--
	
	var updateContentHeight = function() {
		initialHeight = document.getElementById('headerFrame').offsetHeight; 
		docHeight = document.body.clientHeight;
		document.getElementById('mainFrame').style.height = (docHeight-initialHeight)+"px";						
	};
	
	// Fixes the content height of the frame			
	window.onload = function() {

		updateContentHeight();
				
		//loads the glossary library		
		<?php 	
			if (api_get_setting('show_glossary_in_documents') == 'ismanual') {  	  	 	
		  	 	?>		  	 	
		    $(document).ready(function() {   
		      $.frameReady(function(){   
		       //  $("<div>I am a div courses</div>").prependTo("body");		     
		      }, "top.mainFrame",   
		      { load: [   
		      		{type:"script", id:"_fr1", src:"<?= api_get_path(WEB_LIBRARY_PATH); ?>javascript/jquery.js"},
		            {type:"script", id:"_fr2", src:"<?= api_get_path(WEB_LIBRARY_PATH); ?>javascript/jquery.highlight.js"},
		            {type:"script", id:"_fr3", src:"<?= api_get_path(WEB_LIBRARY_PATH); ?>fckeditor/editor/plugins/glossary/fck_glossary_manual.js"}
		      	 ] 
		      }		   
		      );
		   });		   		  	 	
		<?php
		  	 } elseif(api_get_setting('show_glossary_in_documents') == 'isautomatic') {
		?>		
		    $(document).ready(function() {   
		      $.frameReady(function(){   
		       //  $("<div>I am a div courses</div>").prependTo("body");
		     
		      }, "top.mainFrame",   
		      { load: [   
		      		{type:"script", id:"_fr1", src:"<?= api_get_path(WEB_LIBRARY_PATH); ?>javascript/jquery.js"},
		            {type:"script", id:"_fr2", src:"<?= api_get_path(WEB_LIBRARY_PATH); ?>javascript/jquery.highlight.js"},
		            {type:"script", id:"_fr3", src:"<?= api_get_path(WEB_LIBRARY_PATH); ?>fckeditor/editor/plugins/glossary/fck_glossary_automatic.js"}
		      	 ] 
		      }
		   
		      );
		   });
		<?php
		  	 }		  
		?>
	}
	-->	
</script>
</head>
<body style="margin:0px;padding:0px" OnResize="updateContentHeight()">
	<iframe border="0" frameborder="0" scrolling="no" style="width:100%;" id="headerFrame" src="headerpage.php?file=<?php echo $file.'&amp;'.api_get_cidreq(); ?>"> </iframe>
	<?php
	if (file_exists($file_url_sys)) {
		//echo '<div style="overflow:scroll">aca';			
		echo '<iframe border="0" frameborder="0" scrolling="yes"  style="width:100%;"  id="mainFrame" name="mainFrame" src="'.$file_url_web.'?'.api_get_cidreq().'&rand='.mt_rand(1,10000).'"></iframe>';
		//echo '</div>';			
	} else {				
		echo '<frame name="mainFrame" id="mainFrame" src=showinframes.php?nopages=1 />';
	}
	?>
</body>
</html>