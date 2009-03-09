<?php // $Id: slideshow.php 18899 2009-03-09 21:37:00Z herodoto $

/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)

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
*	@author Patrick Cool
*	@package dokeos.document
============================================================================== 
*/
/*
==============================================================================
Developped by Patrick Cool
patrick.cool@UGent.be
Ghent University
Mai 2004
http://icto.UGent.be

==============================================================================
*/
/*
==============================================================================
Improve by Juan Carlos Raña Trabado
herodoto@telefonica.net
January 2008
==============================================================================
*/

// including the language file

// name of the language file that needs to be included 
$language_file = "slideshow";

include ('../inc/global.inc.php');

$noPHP_SELF = true;

$path = $_GET['curdirpath'];
$pathurl = urlencode($path);

$slide_id = $_GET['slide_id'];

if ($path and $path <> "")
{
	$folder = $path."/";
}
else
{
	$folder = "";
}
$sys_course_path = api_get_path(SYS_COURSE_PATH);

// including the functions for the slideshow
include ('slideshow.inc.php');

// breadcrumb navigation
$url = "document.php?curdirpath=".$pathurl;
$originaltoolname = get_lang('Documents');
$interbreadcrumb[] = array ("url" => $url, "name" => $originaltoolname);

// because $nametools uses $_SERVER['PHP_SELF'] for the breadcrumbs instead of $_SERVER['REQUEST_URI'], I had to 
// bypass the $nametools thing and use <b></b> tags in the $interbreadcrump array
$url = "slideshow.php?curdirpath=".$pathurl;
$originaltoolname = get_lang('SlideShow');
//$interbreadcrumb[]= array ("url"=>$url, "name"=>$originaltoolname );

Display :: display_header($originaltoolname, "Doc");

// loading the slides from the session
$image_files_only = $_SESSION["image_files_only"];

// calculating the current slide, next slide, previous slide and the number of slides
if ($slide_id <> "all")
{
	if ($slide_id)
	{
		$slide = $slide_id;
	}
	else
	{
		$slide = 0;
	}
	$previous_slide = $slide -1;
	$next_slide = $slide +1;
} // if ($slide_id<>"all")
$total_slides = count($image_files_only);
?>
<script language="JavaScript" type="text/JavaScript">
<!--
function MM_openBrWindow(theURL,winName,features) { //v2.0
  window.open(theURL,winName,features);
}
//-->
</script>


<div class="actions">
	<?php

	// exit the slideshow
	echo '<a href="document.php?action=exit_slideshow&curdirpath='.$pathurl.'"><img src="'.api_get_path(WEB_IMG_PATH).'folder_up.gif" alt="">'.get_lang('_exit_slideshow').'</a>&nbsp;';

	// show thumbnails
	if ($slide_id <> "all") {
		echo '<a href="slideshow.php?slide_id=all&curdirpath='.$pathurl.'"><img src="'.api_get_path(WEB_IMG_PATH).'thumbnails.png" alt="">'.get_lang('_show_thumbnails').'</a>&nbsp;';
	} else {
		echo '<img src="'.api_get_path(WEB_IMG_PATH).'thumbnails_na.png" alt="">'.get_lang('_show_thumbnails').'&nbsp;';
	}
	$image = $sys_course_path.$_course['path']."/document/".$folder.$image_files_only[$slide];
	
	// slideshow options
	echo '<a href="slideshowoptions.php?curdirpath='.$pathurl.'"><img src="'.api_get_path(WEB_IMG_PATH).'acces_tool.gif" alt="">'.get_lang('_set_slideshow_options').'</a> &nbsp;';
?>
</div>

<?php
echo '<br />';

// =======================================================================
//				TREATING THE POST DATA FROM SLIDESHOW OPTIONS
// =======================================================================
// if we come from slideshowoptions.php we sessionize (new word !!! ;-) the options
if (isset ($_POST['Submit'])) // we come from slideshowoptions.php
{
	$_SESSION["image_resizing"] = $_POST['radio_resizing'];
	if ($_POST['radio_resizing'] == "resizing" && $_POST['width'] != '' && $_POST['height'] != '')
	{
		//echo "resizing"; 
		$_SESSION["image_resizing_width"] = $_POST['width'];
		$_SESSION["image_resizing_height"] = $_POST['height'];
	}
	else
	{
		//echo "unsetting the session heighte and width"; 
		$_SESSION["image_resizing_width"] = null;
		$_SESSION["image_resizing_height"] = null;
	}
} // if ($submit)


// The target height and width depends if we choose resizing or no resizing
if ($_SESSION["image_resizing"] == "resizing")
{
	$target_width = $_SESSION["image_resizing_width"];
	$target_height = $_SESSION["image_resizing_height"];
}
else
{
	$image_width = $source_width;
	$image_height = $source_height;
}

// =======================================================================
//						THUMBNAIL VIEW
// =======================================================================
// this is for viewing all the images in the slideshow as thumbnails. 
$image_tag = array ();
if ($slide_id == "all")
{
	$thumbnail_width = 100;
	$thumbnail_height = 100;	

	$row_items = 4;

	foreach ($image_files_only as $one_image_file)
	{
		$image = $sys_course_path.$_course['path']."/document/".$folder.$one_image_file;
		$image_height_width = resize_image($image, $thumbnail_width, $thumbnail_height, 1);
		
		$image_height = $image_height_width[0];
		$image_width = $image_height_width[1];		
		
		if ($path and $path !== "/")
		{
			$doc_url = $path."/".$one_image_file;
		}
		else
		{
			$doc_url = $path.$one_image_file;
		}

		$image_tag[] = "<img src='download.php?doc_url=".$doc_url."' border='0' width='".$image_width."' height='".$image_height."' title='".$one_image_file."'>";
	} // foreach ($image_files_only as $one_image_file)
} // if ($slide_id=="all")

// creating the table
echo "\n<table align='center' cellspacing='10'>";
$i = 0;
foreach ($image_tag as $image_tag_item)
{
	$link=$i;
	// starting new table row
	if ($i == 0)
	{
		echo "\n<tr>\n";
		$i ++;
					
	}
	$link=$i-1;
		echo "\t<td align='center' style='display:block; position:relative; top: -3px; left:-3px; padding:5px; background:#FFFFFF; border:1px solid; border-color: #CCCCCC #666666 #666666 #CCCCCC;'><a href='slideshow.php?slide_id=".$link."&curdirpath=".$pathurl."'>".$image_tag_item."</a></td>\n";
	
	if ($i % 6 == 0 and $i !== 0) // 6 cols
	{
		echo "</tr>\n<tr>\n";
	}
	$i ++;
}
echo "</table>\n\n";

// =======================================================================
//						ONE AT A TIME VIEW
// =======================================================================
// this is for viewing all the images in the slideshow one at a time. 
if ($slide_id !== "all")
{
	$image = $sys_course_path.$_course['path']."/document/".$folder.$image_files_only[$slide];
	$image_height_width = resize_image($image, $target_width, $target_height);

	$image_height = $image_height_width[0];
	$image_width = $image_height_width[1];

	if ($_SESSION["image_resizing"] == "resizing")
	{
		$height_width_tags = 'width="'.$image_width.'" height="'.$image_height.'"';
		
		//adjust proportions. Juan Carlos Raña Trabado TODO: replace resize_image function ?	
		$size = @ getimagesize($image);
		$height_width_tags = (($size[1] > $image_width) ? 'width="'.$image_width.'"' : '');
		$height_width_tags = (($size[1] > $image_height) ? 'height="'.$image_height.'"' : '');		
	}
	

	// showing the comment of the image, Patrick Cool, 8 april 2005
	// this is done really quickly and should be cleaned up a little bit using the API functions
	$tbl_documents = Database::get_course_table(TABLE_DOCUMENT);
	if ($path=='/')
	{
		$pathpart='/';
	}
	else 
	{
		$pathpart=$path.'/'; 
	}
	$sql = "SELECT * FROM $tbl_documents WHERE path='".$pathpart.$image_files_only[$slide]."'";
	$result = api_sql_query($sql,__FILE__,__LINE__);
	$row = mysql_fetch_array($result);

	echo '<table align="center" border="0">';
	echo '<tr>';
	echo '<td align="center">';
	echo "<img src='download.php?doc_url=$path/".$image_files_only[$slide]."' alt='".$image_files_only[$slide]."' border='0'".$height_width_tags.">";
	echo '</td>';
	echo '</tr>';
	echo '<tr>';
	echo '<td align="center">';
	$aux= explode(".", htmlspecialchars($image_files_only[$slide]));
    $ext= $aux[count($aux)-1];
	echo '<strong>'.basename(htmlspecialchars($image_files_only[$slide]), '.'.$ext).'</strong>';	
	echo '<br />'.$row['comment'].'<br />';	
	list($width, $high) = getimagesize($image);
	echo $width.' x '.$high.' '.round((filesize($image)/1024),2).' KB';	
    echo ' - '.$ext; 	
	echo '</td>';
	echo '</tr>';
	echo '<tr>';
	echo '<td align="center">';
	// previous slide
	if ($slide > 0)
	{
		echo '<a href="slideshow.php?slide_id='.$previous_slide.'&amp;curdirpath='.$pathurl.'">';
	}
	echo '&lt;&lt;'.get_lang('_previous_slide');
	if ($slide > 0)
	{
		echo "</a> ";
	}	
	// divider
	if ($slide_id <> "all")
	{
		echo ' [ '.$next_slide.'/'.$total_slides.' ] ';
	}		
	// next slide
	if ($slide < $total_slides -1 and $slide_id <> "all")
	{
		echo "<a href='slideshow.php?slide_id=".$next_slide."&curdirpath=$pathurl'>";
	}
	echo get_lang('_next_slide').'&gt;&gt;';
	if ($slide > 0)
	{
		echo '</a>';
	}
	echo '</td>';
	echo '</tr>';
	echo '</table>';
	
} // if ($slide_id!=="all")

Display :: display_footer();
?>

