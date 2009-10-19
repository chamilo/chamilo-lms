<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)

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
*	@todo convert comments to be understandable to phpDocumentor
==============================================================================
*/
/*
==============================================================================
Developped by Patrick Cool
patrick.cool@UGent.be
Ghent University
Mai 2004
http://icto.UGent.be

Please bear in mind that this is only an alpha release.
I wrote this quite quick and didn't think too much about it in advance.
It is not perfect at all but it is workable and usefull (I think)
Do not consider this as a powerpoint replacement, although it has
the same starting point.
==============================================================================
*/
/*
==============================================================================
Description:
	This is a plugin for the documents tool. It looks for .jpg, .jpeg, .gif, .png
	files (since these are the files that can be viewed in a browser) and creates
	a slideshow with it by allowing to go to the next/previous image.
	You can also have a quick overview (thumbnail view) of all the images in
	that particular folder.
	Maybe it is important to notice that each slideshow is folder based. Only
	the images of the chosen folder are shown.

	This file has two large sections.
	1. code that belongs in document.php, but to avoid clutter I put the code here
	2. the function resize_image that handles the image resizing
==============================================================================

*/



// ====================================================================================================
//				function resize_image($image, $target_width, $target_height, $slideshow=0)
// ====================================================================================================
// this functions calculates the resized width and resized heigt according to the source and target widths
// and heights, height so that no distortions occur
// parameters
// $image = the absolute path to the image
// $target_width = how large do you want your resized image
// $target_height = how large do you want your resized image
// $slideshow (default=0) = indicates weither we are generating images for a slideshow or not, t
//							this overrides the $_SESSION["image_resizing"] a bit so that a thumbnail
//							view is also possible when you choose not to resize the source images

function resize_image($image, $target_width, $target_height, $slideshow=0) {
/*  // Replaced fragment of code by Ivan Tcholakov, 04-MAY-2009.
	// 1. grabbing the image height and width of the original image
		$image_properties=getimagesize($image);
		$source_width=$image_properties["0"];
		$source_height=$image_properties["1"];
		//print_r($image_properties);

	// 2. calculate the resize factor
	if ($_SESSION["image_resizing"]=="resizing" or $slideshow==1)
		{
		$resize_factor_width=$target_width/$source_width;
		$resize_factor_height=$target_height/$source_height;
		//echo $resize_factor_width."//".$resize_factor_height."<br>";
		} // if ($_SESSION["image_resizing"]=="resizing")

	// 4. calculate the resulting heigt and width
	if ($_SESSION["image_resizing"]=="resizing" or $slideshow==1)
		{
		if ($resize_factor_width<=1 and $resize_factor_height<=1)
			{
			if ($resize_factor_width > $resize_factor_height)
				{
				$image_width=$target_width;
				$image_height=ceil($source_height*$resize_factor_width);
				}
			if ($resize_factor_width < $resize_factor_height)
				{
				$image_width=ceil($source_width*$resize_factor_height);
				$image_height=$target_height;
				}
			else // both resize factors are equal
				{
				$image_width=ceil($source_width*$resize_factor_width);
				$image_height=ceil($source_height*$resize_factor_height);
				}
			//echo "image width=".$image_width."<br>";
			//echo "image height=".$image_height;
		} //if ($resize_factor_width<=1 and $resize_factor_height<=1)
	else // no resizing required
		{
		$image_width=$source_width;
		$image_height=$source_height;
		}
} //if ($_SESSION["image_resizing"]=="resizing")

// storing the resulting height and width in an array and returning it
$image_height_width[]=$image_height;
$image_height_width[]=$image_width;
return $image_height_width;
*/
	$result = array();
	if ($_SESSION['image_resizing'] == 'resizing' or $slideshow==1) {
		$new_sizes = api_resize_image($image, $target_width, $target_height);
		$result[] = $new_sizes['height'];
		$result[] = $new_sizes['width'];
	} else {
		$result[] = $image_height;
		$result[] = $image_width;
	}
	return $result;
}
?>
