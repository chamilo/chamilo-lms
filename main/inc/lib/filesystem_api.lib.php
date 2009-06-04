<?php
/**
 * ==============================================================================
 * File: filesystem_api.lib.php
 * Main API extension library for Dokeos 1.8.6.1+ LMS
 * A common purpose library for supporting filesystem functions
 * with encoding management.
 * License: GNU/GPL version 2 or later (Free Software Foundation)
 * @author: Ivan Tcholakov, ivantcholakov@gmail.com
 * June 2009
 * @package dokeos.library
 * ==============================================================================
 */

/**
 * The original PHP5 file system function do not convert file/folder
 * names into proper encoding. The encoding which site uses and the
 * encoding of the server's file system might be different.
 * 
 * The major difference in the following functions is that they work
 * with file/folder names that are encoded using the platform
 * character set.
 */

/**
 * Returns the MIME content type for a file as determined by using information from the magic.mime file.
 * @param string $filename		Path to the tested file.
 * @return 						Returns the content type in MIME format.
 * Note: As of PHP 5.3.0 this function is considered as deprecated. The PECL extension Fileinfo provides the same functionality.
 * @link http://php.net/mime_content_type
 */
function api_mime_content_type ($filename) {
	return mime_content_type (api_file_system_encode($filename));
}

?>