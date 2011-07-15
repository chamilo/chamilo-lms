<?php
/* For licensing terms, see /license.txt */

/**
 *	@author Patrick Cool
 *	@package ghamilo.document
 *	@todo convert comments to be understandable to phpDocumentor
 */

/*
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
*/

/*
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
*/



/**
 * This function calculates the resized width and resized heigt according to the source and target widths
 * and heights, height so that no distortions occur
 * parameters
 * $image = the absolute path to the image
 * $target_width = how large do you want your resized image
 * $target_height = how large do you want your resized image
 * $slideshow (default=0) = indicates weither we are generating images for a slideshow or not, t
 *							this overrides the $_SESSION["image_resizing"] a bit so that a thumbnail
 *							view is also possible when you choose not to resize the source images
 */
function resize_image($image, $target_width, $target_height, $slideshow = 0) {
	// Modifications by Ivan Tcholakov, 04-MAY-2009.
	$result = array();
	if ($_SESSION['image_resizing'] == 'resizing' or $slideshow == 1) {
		$new_sizes = api_resize_image($image, $target_width, $target_height);
		$result[] = $new_sizes['height'];
		$result[] = $new_sizes['width'];
	} else {
        $size = api_getimagesize($image);
		$result[] = $size[1];
		$result[] = $size[0];
	}
	return $result;
}
