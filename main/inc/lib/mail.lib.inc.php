<?php
/* For licensing terms, see /license.txt */
/**
 *
 * @package chamilo.library
 */
/**
 * Code
 */
require_once api_get_path(LIBRARY_PATH).'phpmailer/class.phpmailer.php';


// A regular expression for testing against valid email addresses.
// It should actually be revised for using the complete RFC3696 description:
// http://tools.ietf.org/html/rfc3696#section-3
//$regexp_rfc3696 = "^[0-9a-z_\.+-]+@(([0-9]{1,3}\.){3}[0-9]{1,3}|([0-9a-z][0-9a-z-]*[0-9a-z]\.)+[a-z]{2,3})$"; // Deprecated, 13-OCT-2010.
