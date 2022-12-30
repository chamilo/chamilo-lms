<?php

/**
 *	This file holds the configuration constants and variables
 *	for the user profile tool.
 *
 *	@package chamilo.configuration
 */

// Autentication, password
define('CHECK_PASS_EASY_TO_FIND', true);

$profileIsEditable = true;

// User photos
define('PREFIX_IMAGE_FILENAME_WITH_UID', true); // If true, filename of images on server begin with uid of the user.
define('RESIZE_IMAGE_TO_THIS_HEIGTH', 180);
define('IMAGE_THUMBNAIL_WIDTH', 100);

// Replacing user photos
define('KEEP_THE_NAME_WHEN_CHANGE_IMAGE', true);
	// true  -> the new image have the name of previous.
	// false -> a new name is build for each upladed image.
define('KEEP_THE_OLD_IMAGE_AFTER_CHANGE', true);
	// true  -> if KEEP_THE_NAME_WHEN_CHANGE_IMAGE is true, the  previous image is rename before.
	// false -> only the last image still on server.

// Official code
// Don't forget to change name of offical code in your organization
// See $langOfficialCode within the language file 'registration'
define('CONFVAL_ASK_FOR_OFFICIAL_CODE', true); // not used but name fixed
define('CONFVAL_CHECK_OFFICIAL_CODE', false);
/* if CONFVAL_CHECK_OFFICIAL_CODE is true, build here the
function personal_check_official_code($code, $valueToReturnIfOk, $valueToReturnIfBad) {
	return $stateOfficialCode = true;
}
*/

// For stats
define('NB_LINE_OF_EVENTS', 15);

