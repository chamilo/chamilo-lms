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
 * This function attempts to change the mode of the specified file.
 * @param string $filename		Path to the file.
 * @param int $mode				Access mode, usually given as octal value, examples: 0777, 0770, 0755, 0644.
 * @return bool					Returns TRUE on success or FALSE on failure.
 * @link http://php.net/chmod
 */
function api_chmod($filename, $mode) {
	return chmod(api_file_system_encode($filename), $mode);
}

/**
 * Makes a copy of the file $source to $destination.
 * @param string $source		Path to the source file.
 * @param string $destination	The destination path.
 * @param resource $context		A valid context resource created with stream_context_create().
 * @return bool					Returns TRUE on success or FALSE on failure.
 * Note: If the destination file already exists, it will be overwritten.
 * @link http://php.net/copy
 */
function api_copy($source, $destination, $context = null) {
	$source = api_is_system_path($source) ? api_file_system_encode($source) : $source;
	$destination = api_is_system_path($destination) ? api_file_system_encode($destination) : $destination;
	if (func_num_args() <= 2) {
		return copy($source, $destination);
	}
	return copy($source, $destination, $context);
}

/**
 * Checks whether a file or directory exists.
 * @param string $path	Path to the file or directory.
 * @return bool			Returns TRUE if the specified file or directory exists, FALSE otherwise.
 * @link http://php.net/manual/en/function.file-exists.php
 */
function api_file_exists($path) {
	return file_exists(api_is_system_path($path) ? api_file_system_encode($path) : $path);
}

/**
 * Gets the size for the given file.
 * @param string $filename		Path to the file.
 * @return int					Returns the size of the file in bytes, or FALSE in case of an error.
 * @link http://php.net/manual/en/function.filesize.php
 */
function api_filesize($filename) {
	return filesize(api_is_system_path($filename) ? api_file_system_encode($filename) : $filename);
}

/**
 * This function opens file or URL, i.e. it binds a named resource, specified by filename, to a stream.
 * @param string $filename			The named resource, file or URL.
 * @param string $mode				Specifies the type of access required to the stream.
 * @param bool $use_include_path	If '1' or TRUE the file will be searched in the include_path too.
 * @param resource $context			A valid context resource created with stream_context_create().
 * @return resource					Returns a file pointer resource on success, or FALSE on error.
 * @link http://php.net/manual/en/function.fopen.php
 */
function api_fopen($filename, $mode, $use_include_path = false, $context = null) {
	$filename = api_is_system_path($filename) ? api_file_system_encode($filename) : $filename;
	$count = func_num_args();
	if ($count <= 2) {
		return fopen($filename, $mode);
	}
	elseif ($count == 3) {
		return fopen($filename, $mode, $use_include_path);
	}
	return fopen($filename, $mode, $use_include_path, $context);
}

/**
 * Checks whether a given path represents a directory (folder).
 * @param string $path	The path to be checked.
 * @return bool			Returns TRUE if we have a directory, FALSE otherwise.
 * @link http://php.net/is_dir
 */
function api_is_dir($path) {
	return is_dir(api_is_system_path($path) ? api_file_system_encode($path) : $path);
}

/**
 * Checks whether a given path represents a file.
 * @param string $path	The path to be checked.
 * @return bool			Returns TRUE if we have a file, FALSE otherwise.
 * @link http://php.net/is_file
 */
function api_is_file($path) {
	return is_file(api_is_system_path($path) ? api_file_system_encode($path) : $path);
}

/**
 * Checks whether a given path represents a system (local) file or folder.
 * @param string $path	The path to be checked.
 * @return bool			Returns TRUE if we have a system path, FALSE otherwise.
 */
function api_is_system_path($path) {
	return preg_match('/^(https?|ftp):.*$/i', $path) ? false : true;
}

/**
 * Returns the MIME content type for a file as determined by using information from the magic.mime file.
 * @param string $filename		Path to the tested file.
 * @return string				Returns the content type in MIME format.
 * Note: As of PHP 5.3.0 this function is considered as deprecated. The PECL extension Fileinfo provides the same functionality.
 * @link http://php.net/mime_content_type
 */
function api_mime_content_type($filename) {
	return mime_content_type(api_file_system_encode($filename));
}

/**
 * This function moves an uploaded file to a new location.
 * @param string $filename		The filename of the uploaded file.
 * @param string $destination	The destination of the moved file.
 * @return bool					If the specified file is not a valid upload file or it cannot be moved for some reason, the function will return FALSE.
 */
function api_move_uploaded_file($filename, $destination) {
	return move_uploaded_file(api_file_system_encode($filename), api_file_system_encode($destination));
}

?>
