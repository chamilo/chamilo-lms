<?php
/*
==============================================================================
	Dokeos - elearning and course management software
	
	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) Roan Embrechts, Vrije Universiteit Brussel
	
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
*	This is the document library for Dokeos.
*	It is / will be used to provide a service layer to all document-using tools.
*	and eliminate code duplication fro group documents, scorm documents, main documents.
*	Include/require it in your code to use its functionality.
*
*	@version 1.1, January 2005
*	@package dokeos.library
============================================================================== 
*/

/*
============================================================================== 
	DOCUMENTATION
	use the functions like this: DocumentManager::get_course_quota()
============================================================================== 
*/

/*
============================================================================== 
		CONSTANTS
============================================================================== 
*/

define("DISK_QUOTA_FIELD", "disk_quota"); //name of the database field
/** default quota for the course documents folder */
define("DEFAULT_DOCUMENT_QUOTA", get_setting('default_document_quotum'));

/*
============================================================================== 
		VARIABLES
============================================================================== 
*/

$sys_course_path = api_get_path(SYS_COURSE_PATH);
$baseServDir = api_get_path(SYS_PATH);
$baseServUrl = $_configuration['url_append']."/";
$baseWorkDir = $sys_course_path.(!empty($courseDir)?$courseDir:'');

/*
============================================================================== 
		DocumentManager CLASS
		the class and its functions
============================================================================== 
*/

/**
 *	@package dokeos.library
 */
class DocumentManager
{
	/**
	* @return the document folder quuta of the current course, in bytes
	* @todo eliminate globals
	*/
	function get_course_quota()
	{
		global $_course, $maxFilledSpace;
		$course_code = Database::escape_string($_course['sysCode']);
		$course_table = Database::get_main_table(TABLE_MAIN_COURSE);

		$sql_query = "SELECT ".DISK_QUOTA_FIELD." FROM $course_table WHERE code = '$course_code'";
		$sql_result = api_sql_query($sql_query, __FILE__, __LINE__);
		$result = Database::fetch_array($sql_result);
		$course_quota = $result[DISK_QUOTA_FIELD];

		if ($course_quota == NULL)
		{
			//course table entry for quota was null
			//use default value
			$course_quota = DEFAULT_DOCUMENT_QUOTA;
		}

		return $course_quota;
	}

	/**
	*	Get the content type of a file by checking the extension
	*	We could use mime_content_type() with php-versions > 4.3,
	*	but this doesn't work as it should on Windows installations
	*
	*	@param string $filename or boolean TRUE to return complete array
	*	@author ? first version
	*	@author Bert Vanderkimpen
	*
	*/
	function file_get_mime_type($filename)
	{
		//all mime types in an array (from 1.6, this is the authorative source)
		//please keep this alphabetical if you add something to this list!!!
	    $mime_types=array(
			"ai" => "application/postscript",
			"aif" => "audio/x-aiff",
    		"aifc" => "audio/x-aiff",
   			"aiff" => "audio/x-aiff",
		    "asf" => "video/x-ms-asf",
		    "asc" => "text/plain",
		    "au" => "audio/basic",
		    "avi" => "video/x-msvideo",
		    "bcpio" => "application/x-bcpio",
		    "bin" => "application/octet-stream",
		    "bmp" => "image/bmp",
		    "cdf" => "application/x-netcdf",
		    "class" => "application/octet-stream",
		    "cpio" => "application/x-cpio",
		    "cpt" => "application/mac-compactpro",
		    "csh" => "application/x-csh",
		    "css" => "text/css",
		    "dcr" => "application/x-director",
		    "dir" => "application/x-director",
		    "djv" => "image/vnd.djvu",
		    "djvu" => "image/vnd.djvu",
		    "dll" => "application/octet-stream",
		    "dmg" => "application/x-diskcopy",
		    "dms" => "application/octet-stream",
		    "doc" => "application/msword",
		    "dvi" => "application/x-dvi",
		    "dwg" => "application/vnd.dwg",
		    "dxf" => "application/vnd.dxf",
		    "dxr" => "application/x-director",
		    "eps" => "application/postscript",
		    "etx" => "text/x-setext",
		    "exe" => "application/octet-stream",
		    "ez" => "application/andrew-inset",
		    "gif" => "image/gif",
		    "gtar" => "application/x-gtar",
		    "gz" => "application/x-gzip",    
		    "hdf" => "application/x-hdf",
		    "hqx" => "application/mac-binhex40",
		    "htm" => "text/html",
		    "html" => "text/html",
		    "ice" => "x-conference-xcooltalk",
		    "ief" => "image/ief",
		    "iges" => "model/iges",
		    "igs" => "model/iges",
		    "jar" => "application/java-archiver",
		    "jpe" => "image/jpeg",
		    "jpeg" => "image/jpeg",
		    "jpg" => "image/jpeg",
		    "js" => "application/x-javascript",
		    "kar" => "audio/midi",
		    "latex" => "application/x-latex",
		    "lha" => "application/octet-stream",
		    "lzh" => "application/octet-stream",
		    "m1a" => "audio/mpeg",
		    "m2a" => "audio/mpeg",
		    "m3u" => "audio/x-mpegurl",
		    "man" => "application/x-troff-man",
		    "me" => "application/x-troff-me",
		    "mesh" => "model/mesh",
		    "mid" => "audio/midi",
		    "midi" => "audio/midi",
		    "mov" => "video/quicktime",
		    "movie" => "video/x-sgi-movie",
		    "mp2" => "audio/mpeg",
		    "mp3" => "audio/mpeg",
		    "mp4" => "video/mpeg4-generic",
		    "mpa" => "audio/mpeg",
		    "mpe" => "video/mpeg",
		    "mpeg" => "video/mpeg",
		    "mpg" => "video/mpeg",
		    "mpga" => "audio/mpeg",
		    "ms" => "application/x-troff-ms",
		    "msh" => "model/mesh",
		    "mxu" => "video/vnd.mpegurl",
		    "nc" => "application/x-netcdf",
		    "oda" => "application/oda",
		    "pbm" => "image/x-portable-bitmap",
		    "pct" => "image/pict",
		    "pdb" => "chemical/x-pdb",
		    "pdf" => "application/pdf",
		    "pgm" => "image/x-portable-graymap",
		    "pgn" => "application/x-chess-pgn",
		    "pict" => "image/pict",
		    "png" => "image/png",
		    "pnm" => "image/x-portable-anymap",
		    "ppm" => "image/x-portable-pixmap",
		    "ppt" => "application/vnd.ms-powerpoint",
		    "pps" => "application/vnd.ms-powerpoint",
		    "ps" => "application/postscript",
		    "qt" => "video/quicktime",
		    "ra" => "audio/x-realaudio",
		    "ram" => "audio/x-pn-realaudio",
		    "rar" => "image/x-rar-compressed",
		    "ras" => "image/x-cmu-raster",
		    "rgb" => "image/x-rgb",
		    "rm" => "audio/x-pn-realaudio",
		    "roff" => "application/x-troff",
		    "rpm" => "audio/x-pn-realaudio-plugin",
		    "rtf" => "text/rtf",
		    "rtx" => "text/richtext",
		    "sgm" => "text/sgml",
		    "sgml" => "text/sgml",
		    "sh" => "application/x-sh",
		    "shar" => "application/x-shar",
		    "silo" => "model/mesh",
		    "sib" => "application/X-Sibelius-Score",
		    "sit" => "application/x-stuffit",
		    "skd" => "application/x-koan",
		    "skm" => "application/x-koan",
		    "skp" => "application/x-koan",
		    "skt" => "application/x-koan",
		    "smi" => "application/smil",
		    "smil" => "application/smil",
		    "snd" => "audio/basic",
		    "so" => "application/octet-stream",
		    "spl" => "application/x-futuresplash",
		    "src" => "application/x-wais-source",
		    "sv4cpio" => "application/x-sv4cpio",
		    "sv4crc" => "application/x-sv4crc",
		    "svf" => "application/vnd.svf",
		    "swf" => "application/x-shockwave-flash",
		    "sxc" => "application/vnd.sun.xml.calc",
		    "sxi" => "application/vnd.sun.xml.impress",
		    "sxw" => "application/vnd.sun.xml.writer",
		    "t" => "application/x-troff",
		    "tar" => "application/x-tar",
		    "tcl" => "application/x-tcl",
		    "tex" => "application/x-tex",
		    "texi" => "application/x-texinfo",
		    "texinfo" => "application/x-texinfo",
		    "tga" => "image/x-targa",
		    "tif" => "image/tif",
		    "tiff" => "image/tiff",
		    "tr" => "application/x-troff",
		    "tsv" => "text/tab-seperated-values",
		    "txt" => "text/plain",
		    "ustar" => "application/x-ustar",
		    "vcd" => "application/x-cdlink",
		    "vrml" => "model/vrml",
		    "wav" => "audio/x-wav",
		    "wbmp" => "image/vnd.wap.wbmp",
		    "wbxml" => "application/vnd.wap.wbxml",
		    "wml" => "text/vnd.wap.wml",
		    "wmlc" => "application/vnd.wap.wmlc",
		    "wmls" => "text/vnd.wap.wmlscript",
		    "wmlsc" => "application/vnd.wap.wmlscriptc",
		    "wma" => "video/x-ms-wma",   
		    "wmv" => "audio/x-ms-wmv",    
		    "wrl" => "model/vrml",
		    "xbm" => "image/x-xbitmap",
		    "xht" => "application/xhtml+xml",
		    "xhtml" => "application/xhtml+xml",
		    "xls" => "application/vnd.ms-excel",
		    "xml" => "text/xml",
		    "xpm" => "image/x-xpixmap",
		    "xsl" => "text/xml",
		    "xwd" => "image/x-windowdump",
		    "xyz" => "chemical/x-xyz",
		    "zip" => "application/zip"
		);
		
		if ($filename === TRUE) 
		{
			return $mime_types;
		}
		
		//get the extension of the file
		$extension = explode('.', $filename);

		//$filename will be an array if a . was found
		if (is_array($extension))
		{
			$extension = (strtolower($extension[sizeof($extension) - 1]));
		}
		//file without extension
		else
		{
			$extension = 'empty';
		}

		//if the extension is found, return the content type
		if (isset ($mime_types[$extension]))
			return $mime_types[$extension];
		//else return octet-stream
		return "application/octet-stream";
	}

	/**
	*	@return true if the user is allowed to see the document, false otherwise
	*	@author Sergio A Kessler, first version
	*	@author Roan Embrechts, bugfix
	*   @todo ??not only check if a file is visible, but also check if the user is allowed to see the file??
	*/
	function file_visible_to_user($this_course, $doc_url)
	{
		if (api_is_allowed_to_edit())
		{
			return true;
		}
		else
		{
			$tbl_document = Database::get_course_table(TABLE_DOCUMENT);
			$tbl_item_property = $this_course.'item_property';
			$doc_url = Database::escape_string($doc_url);
			//$doc_url = addslashes($doc_url);
			$query = "SELECT 1 FROM $tbl_document AS docs,$tbl_item_property AS props 
					  WHERE props.tool = 'document' AND docs.id=props.ref AND props.visibility <> '1' AND docs.path = '$doc_url'";
			//echo $query;
			$result = api_sql_query($query, __FILE__, __LINE__);

			return (Database::num_rows($result) == 0);
		}
	}

	/**
	* This function streams a file to the client
	*
	* @param string $full_file_name
	* @param boolean $forced
	* @param string $name
	* @return false if file doesn't exist, true if stream succeeded
	*/
	function file_send_for_download($full_file_name, $forced = false, $name = '')
	{
		if (!is_file($full_file_name))
		{
			return false;
		}
		$filename = ($name == '') ? basename($full_file_name) : $name;
		$len = filesize($full_file_name);

		if ($forced)
		{
			//force the browser to save the file instead of opening it

			header('Content-type: application/octet-stream');
			//header('Content-Type: application/force-download');
			header('Content-length: '.$len);
			if (preg_match("/MSIE 5.5/", $_SERVER['HTTP_USER_AGENT']))
			{
				header('Content-Disposition: filename= '.$filename);
			}
			else
			{
				header('Content-Disposition: attachment; filename= '.$filename);
			}
			if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE'))
			{
				header('Pragma: ');
				header('Cache-Control: ');
				header('Cache-Control: public'); // IE cannot download from sessions without a cache
			}
			header('Content-Description: '.$filename);
			header('Content-transfer-encoding: binary');

			$fp = fopen($full_file_name, 'r');
			fpassthru($fp);
			return true;
		}
		else
		{
			//no forced download, just let the browser decide what to do according to the mimetype

			$content_type = DocumentManager::file_get_mime_type($filename);
			header('Expires: Wed, 01 Jan 1990 00:00:00 GMT');
			header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
            // Commented to avoid double caching declaration when playing with IE and HTTPS
			//header('Cache-Control: no-cache, must-revalidate');
			//header('Pragma: no-cache');
			header('Content-type: '.$content_type);
			header('Content-Length: '.$len);
			$user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
			if (strpos($user_agent, 'msie'))
			{
				header('Content-Disposition: ; filename= '.$filename);
			}
			else
			{
				header('Content-Disposition: inline; filename= '.$filename);
			}
			readfile($full_file_name);
			return true;
		}
	}

	/**
	* This function streams a string to the client for download.
	* You have to ensure that the calling script then stops processing (exit();)
	* otherwise it may cause subsequent use of the page to want to download
	* other pages in php rather than interpreting them.
	*
	* @param string The string contents
	* @param boolean Whether "save" mode is forced (or opening directly authorized)
	* @param string The name of the file in the end (including extension)
	* @return false if file doesn't exist, true if stream succeeded
	*/
	function string_send_for_download($full_string, $forced = false, $name = '')
	{
		$filename = $name;
		$len = strlen($full_string);

		if ($forced)
		{
			//force the browser to save the file instead of opening it

			header('Content-type: application/octet-stream');
			//header('Content-Type: application/force-download');
			header('Content-length: '.$len);
			if (preg_match("/MSIE 5.5/", $_SERVER['HTTP_USER_AGENT']))
			{
				header('Content-Disposition: filename= '.$filename);
			}
			else
			{
				header('Content-Disposition: attachment; filename= '.$filename);
			}
			if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE'))
			{
				header('Pragma: ');
				header('Cache-Control: ');
				header('Cache-Control: public'); // IE cannot download from sessions without a cache
			}
			header('Content-Description: '.$filename);
			header('Content-transfer-encoding: binary');

			//$fp = fopen($full_string, 'r');
			//fpassthru($fp);
			echo $full_string;
			return true;
			//You have to ensure that the calling script then stops processing (exit();)
			//otherwise it may cause subsequent use of the page to want to download
			//other pages in php rather than interpreting them.
		}
		else
		{
			//no forced download, just let the browser decide what to do according to the mimetype

			$content_type = DocumentManager::file_get_mime_type($filename);
			header('Expires: Wed, 01 Jan 1990 00:00:00 GMT');
			header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
			header('Cache-Control: no-cache, must-revalidate');
			header('Pragma: no-cache');
			header('Content-type: '.$content_type);
			header('Content-Length: '.$len);
			$user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
			if (strpos($user_agent, 'msie'))
			{
				header('Content-Disposition: ; filename= '.$filename);
			}
			else
			{
				header('Content-Disposition: inline; filename= '.$filename);
			}
			echo($full_string);
			//You have to ensure that the calling script then stops processing (exit();)
			//otherwise it may cause subsequent use of the page to want to download
			//other pages in php rather than interpreting them.
			return true;
		}
	}

	/**
	* Fetches all document data for the given user/group
	*
	* @param array $_course
	* @param string $path
	* @param int $to_group_id
	* @param int $to_user_id
	* @param boolean $can_see_invisible
	* @return array with all document data 
	*/
	function get_all_document_data($_course, $path = '/', $to_group_id = 0, $to_user_id = NULL, $can_see_invisible = false)
	{
		$TABLE_ITEMPROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY, $_course['dbName']);
		$TABLE_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT, $_course['dbName']);

		//if to_user_id = NULL -> change query (IS NULL)
		//$to_user_id = (is_null($to_user_id))?'IS NULL':'= '.$to_user_id;
		if (!is_null($to_user_id))
		{
			$to_field = 'last.to_user_id';
			$to_value = $to_user_id;
		}
		else
		{
			$to_field = 'last.to_group_id';
			$to_value = $to_group_id;
		}

		//escape underscores in the path so they don't act as a wildcard
		$path 		= Database::escape_string(str_replace('_', '\_', $path));
		$to_user_id = Database::escape_string($to_user_id);
		$to_value	= Database::escape_string($to_value);

		//if they can't see invisible files, they can only see files with visibility 1
		$visibility_bit = ' = 1';
		//if they can see invisible files, only deleted files (visibility 2) are filtered out
		if ($can_see_invisible)
		{
			$visibility_bit = ' <> 2';
		}

		//the given path will not end with a slash, unless it's the root '/'
		//so no root -> add slash
		$added_slash = ($path == '/') ? '' : '/';

		$sql = "SELECT *
						FROM  ".$TABLE_ITEMPROPERTY."  AS last, ".$TABLE_DOCUMENT."  AS docs
						WHERE docs.id = last.ref
						AND docs.path LIKE '".$path.$added_slash."%'
						AND docs.path NOT LIKE '".$path.$added_slash."%/%'
						AND last.tool = '".TOOL_DOCUMENT."' 
						AND ".$to_field." = ".$to_value."
						AND last.visibility".$visibility_bit;

		//echo $sql;

		$result = api_sql_query($sql);

		if ($result && Database::num_rows($result) != 0)
		{
			while ($row = Database::fetch_array($result,'ASSOC'))
				//while ($row = Database::fetch_array($result,MYSQL_NUM))
			{
				
				if($row['filetype']=='file' && pathinfo($row['path'],PATHINFO_EXTENSION)=='html'){
					
					//Templates management
					$table_template = Database::get_main_table(TABLE_MAIN_TEMPLATES);
					$sql_is_template = "SELECT id FROM $table_template 
										WHERE course_code='".$_course['id']."' 
										AND user_id='".api_get_user_id()."'
										AND ref_doc='".$row['id']."'";
					$template_result = api_sql_query($sql_is_template);
					if(Database::num_rows($template_result)>0){
						$row['is_template'] = 1;
					}
					else{
						$row['is_template'] = 0;
					}
				}
				
				$document_data[$row['id']] = $row;
				//$document_data[] = $row;
			}
			return $document_data;
		}
		else
		{
			//display_error("Error getting document info from database (".mysql_error().")!");
			return false;
		}
	}

	/**
	 * Gets the paths of all folders in a course
	 * can show all folders (exept for the deleted ones) or only visible ones
	 * @param array $_course
	 * @param boolean $can_see_invisible
	 * @param int $to_group_id
	 * @return array with paths
	 */
	function get_all_document_folders($_course, $to_group_id = '0', $can_see_invisible = false)
	{
		$TABLE_ITEMPROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY, $_course['dbName']);
		$TABLE_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT, $_course['dbName']);
		if(empty($doc_url)){
			$to_group_id = '0';
		} else {
			$to_group_id = Database::escape_string($to_group_id);
		}
		
		if ($can_see_invisible)
		{
			$sql = "SELECT path
								FROM  ".$TABLE_ITEMPROPERTY."  AS last, ".$TABLE_DOCUMENT."  AS docs
								WHERE docs.id = last.ref
								AND docs.filetype = 'folder' 
								AND last.tool = '".TOOL_DOCUMENT."' 
								AND last.to_group_id = ".$to_group_id." 
								AND last.visibility <> 2";

			$result = api_sql_query($sql, __FILE__, __LINE__);

			if ($result && Database::num_rows($result) != 0)
			{
				while ($row = Database::fetch_array($result,'ASSOC'))
				{
					$document_folders[] = $row['path'];
				}
				sort($document_folders);
				//return results
				return $document_folders;
			}
			else
			{
				return false;
			}
		}
		//no invisible folders
		else
		{
			//get visible folders
			$visible_sql = "SELECT path
						FROM  ".$TABLE_ITEMPROPERTY."  AS last, ".$TABLE_DOCUMENT."  AS docs
						WHERE docs.id = last.ref
						AND docs.filetype = 'folder' 
						AND last.tool = '".TOOL_DOCUMENT."' 
						AND last.to_group_id = ".$to_group_id." 
						AND last.visibility = 1";
			$visibleresult = api_sql_query($visible_sql, __FILE__, __LINE__);
			while ($all_visible_folders = Database::fetch_array($visibleresult,'ASSOC'))
			{
				$visiblefolders[] = $all_visible_folders['path'];
				//echo "visible folders: ".$all_visible_folders['path']."<br>";
			}
			//get invisible folders
			$invisible_sql = "SELECT path 
						FROM  ".$TABLE_ITEMPROPERTY."  AS last, ".$TABLE_DOCUMENT."  AS docs
						WHERE docs.id = last.ref
						AND docs.filetype = 'folder' 
						AND last.tool = '".TOOL_DOCUMENT."' 
						AND last.to_group_id = ".$to_group_id." 
						AND last.visibility = 0";
			$invisibleresult = api_sql_query($invisible_sql, __FILE__, __LINE__);
			while ($invisible_folders = Database::fetch_array($invisibleresult,'ASSOC'))
			{
				//get visible folders in the invisible ones -> they are invisible too
				//echo "invisible folders: ".$invisible_folders['path']."<br>";
				$folder_in_invisible_sql = "SELECT path 
								FROM  ".$TABLE_ITEMPROPERTY."  AS last, ".$TABLE_DOCUMENT."  AS docs
								WHERE docs.id = last.ref
								AND docs.path LIKE '".Database::escape_string($invisible_folders['path'])."/%' 
								AND docs.filetype = 'folder' 
								AND last.tool = '".TOOL_DOCUMENT."' 
								AND last.to_group_id = ".$to_group_id." 
								AND last.visibility = 1";
				$folder_in_invisible_result = api_sql_query($folder_in_invisible_sql, __FILE__, __LINE__);
				while ($folders_in_invisible_folder = Database::fetch_array($folder_in_invisible_result,'ASSOC'))
				{
					$invisiblefolders[] = $folders_in_invisible_folder['path'];
					//echo "<br>folders in invisible folders: ".$folders_in_invisible_folder['path']."<br><br><br>";
				}
			}
			//if both results are arrays -> //calculate the difference between the 2 arrays -> only visible folders are left :)
			if (is_array($visiblefolders) && is_array($invisiblefolders))
			{
				$document_folders = array_diff($visiblefolders, $invisiblefolders);
				sort($document_folders);
				return $document_folders;
			}
			//only visible folders found
			elseif (is_array($visiblefolders))
			{
				sort($visiblefolders);
				return $visiblefolders;
			}
			//no visible folders found
			else
			{
				return false;
			}
		}
	}	
	/**
	 * This check if a document has the readonly property checked, then see if the user
	 * is the owner of this file, if all this is true then return true.
	 * 
	 * @param array  $_course
	 * @param int    $user_id id of the current user
	 * @param string $file path stored in the database
	 * @param int    $document_id in case you dont have the file path ,insert the id of the file here and leave $file in blank ''
	 * @return boolean true/false	 
	 **/
	function check_readonly($_course,$user_id,$file,$document_id='',$to_delete=false)
	{
		if(!(!empty($document_id) && is_numeric($document_id)))
		{			
			$document_id = DocumentManager::get_document_id($_course, $file);
		}		
		
		$TABLE_PROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY, $_course['dbName']);
		$TABLE_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT, $_course['dbName']);
		
		if ($to_delete)
		{
			if (DocumentManager::is_folder($_course, $document_id))
			{
				if (!empty($file))
				{
					$path = Database::escape_string($file);
					$what_to_check_sql = "SELECT td.id, readonly, tp.insert_user_id FROM ".$TABLE_DOCUMENT." td , $TABLE_PROPERTY tp 
									WHERE tp.ref= td.id and (path='".$path."' OR path LIKE BINARY '".$path."/%' ) ";
					//get all id's of documents that are deleted
					$what_to_check_result = api_sql_query($what_to_check_sql, __FILE__, __LINE__);
					
					if ($what_to_check_result && Database::num_rows($what_to_check_result) != 0)
					{
						// file with readonly set to 1 exist?
						$readonly_set=false;
						while ($row = Database::fetch_array($what_to_check_result))
						{
							//query to delete from item_property table		
							//echo $row['id']; echo "<br>";
							if ($row['readonly']==1)					
							{					
								if (!($row['insert_user_id'] == $user_id))
								{			 					
									$readonly_set=true;
									break;
								}	
								
							}
						}
				
						if ($readonly_set)
						{
							return true;
						}
					}
				} 
				return false;			
			}
		}
				
				
		
		if (!empty($document_id))
		{	
			$sql= 'SELECT a.insert_user_id, b.readonly FROM '.$TABLE_PROPERTY.' a,'.$TABLE_DOCUMENT.' b
				   WHERE a.ref = b.id and a.ref='.$document_id.' LIMIT 1';
			$resultans   =  api_sql_query($sql, __FILE__, __LINE__);
			$doc_details =  Database ::fetch_array($resultans,'ASSOC');
		
			if($doc_details['readonly']==1)
			{							
				if ( $doc_details['insert_user_id'] == $user_id || api_is_platform_admin() ) 
				{		
						return false;						
				}
				else
				{	
					return true;				
				}			
			}		
		}
		return false;
	}
	
	/**
	 * This check if a document is a folder or not	 
	 * @param array  $_course
	 * @param int    $document_id of the item
	 * @return boolean true/false	 
	 **/
	function is_folder($_course, $document_id)
	{	
		$TABLE_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT, $_course['dbName']);
		//if (!empty($document_id))
		$document_id = Database::escape_string($document_id);
		$resultans   =  api_sql_query('SELECT filetype FROM '.$TABLE_DOCUMENT.' WHERE id='.$document_id.'', __FILE__, __LINE__);		
		$result=  Database::fetch_array($resultans,'ASSOC');
		if ($result['filetype']=='folder') {
			return true;			
		} else {
			return false;
		}		
	}
	
	/**
	 * This deletes a document by changing visibility to 2, renaming it to filename_DELETED_#id
	 * Files/folders that are inside a deleted folder get visibility 2
	 *
	 * @param array $_course
	 * @param string $path, path stored in the database
	 * @param string ,$base_work_dir, path to the documents folder
	 * @return boolean true/false
	 * @todo now only files/folders in a folder get visibility 2, we should rename them too.
	 */
	function delete_document($_course, $path, $base_work_dir)
	{
		$TABLE_DOCUMENT = Database :: get_course_table(TABLE_DOCUMENT, $_course['dbName']);
		$TABLE_ITEMPROPERTY = Database :: get_course_table(TABLE_ITEM_PROPERTY, $_course['dbName']);
		//first, delete the actual document...
		$document_id = DocumentManager :: get_document_id($_course, $path);
		$new_path = $path.'_DELETED_'.$document_id;
		if ($document_id)
		{
			if (get_setting('permanently_remove_deleted_files') == 'true') //deleted files are *really* deleted
			{
				$what_to_delete_sql = "SELECT id FROM ".$TABLE_DOCUMENT." WHERE path='".$path."' OR path LIKE BINARY '".$path."/%'";
				//get all id's of documents that are deleted
				$what_to_delete_result = api_sql_query($what_to_delete_sql, __FILE__, __LINE__);
							
				if ($what_to_delete_result && Database::num_rows($what_to_delete_result) != 0)
				{
					//needed to deleted medadata
					require_once (api_get_path(SYS_CODE_PATH).'metadata/md_funcs.php');
					require_once(api_get_path(LIBRARY_PATH).'fileManage.lib.php');
					$mdStore = new mdstore(TRUE);

					//delete all item_property entries
					while ($row = Database::fetch_array($what_to_delete_result))
					{
						//query to delete from item_property table
						//avoid wrong behavior
												
						//$remove_from_item_property_sql = "DELETE FROM ".$TABLE_ITEMPROPERTY." WHERE ref = ".$row['id']." AND tool='".TOOL_DOCUMENT."'";
						api_item_property_update($_course, TOOL_DOCUMENT, $row['id'], 'delete', api_get_user_id());
																								
						//query to delete from document table
						$remove_from_document_sql = "DELETE FROM ".$TABLE_DOCUMENT." WHERE id = ".$row['id']."";
 						DocumentManager::unset_document_as_template($row['id'],$_course, api_get_user_id());
						//echo($remove_from_item_property_sql.'<br>');
						//api_sql_query($remove_from_item_property_sql, __FILE__, __LINE__);
						//echo($remove_from_document_sql.'<br>');
						api_sql_query($remove_from_document_sql, __FILE__, __LINE__);

						//delete metadata
						$eid = 'Document'.'.'.$row['id'];
						$mdStore->mds_delete($eid);
						$mdStore->mds_delete_offspring($eid);

					}
					DocumentManager::delete_document_from_search_engine(api_get_course_id(), $document_id);
					//delete documents, do it like this so metadata get's deleted too
					//update_db_info('delete', $path);
					//throw it away
					my_delete($base_work_dir.$path);

					return true;
				}
				else
				{
					return false;
				}
			}
			else //set visibility to 2 and rename file/folder to qsdqsd_DELETED_#id
			{
				if (api_item_property_update($_course, TOOL_DOCUMENT, $document_id, 'delete', api_get_user_id()))
				{	
					//echo('item_property_update OK');
					if (is_file($base_work_dir.$path) || is_dir($base_work_dir.$path) )
                    {
                        if(rename($base_work_dir.$path, $base_work_dir.$new_path))
    					{
    						 DocumentManager::unset_document_as_template($document_id, api_get_course_id(), api_get_user_id());	
    						 $sql = "UPDATE $TABLE_DOCUMENT set path='".$new_path."' WHERE id='".$document_id."'";
    						if (api_sql_query($sql, __FILE__, __LINE__))
    						{
    							//if it is a folder it can contain files
    							$sql = "SELECT id,path FROM ".$TABLE_DOCUMENT." WHERE path LIKE BINARY '".$path."/%'";
    							$result = api_sql_query($sql, __FILE__, __LINE__);
    							if ($result && Database::num_rows($result) > 0)
    							{
    								while ($deleted_items = Database::fetch_array($result,'ASSOC'))
    								{
    									//echo('to delete also: id '.$deleted_items['id']);
    									api_item_property_update($_course, TOOL_DOCUMENT, $deleted_items['id'], 'delete', api_get_user_id());
    									//Change path of subfolders and documents in database
    									$old_item_path = $deleted_items['path'];    									
    									$new_item_path = $new_path.substr($old_item_path, strlen($path));
    									/*/
    									 * trying to fix this bug FS#2681
    									echo $base_work_dir.$old_item_path;
    									echo "<br>";
    									echo $base_work_dir.$new_item_path;
    									echo "<br>";echo "<br>";
										rename($base_work_dir.$old_item_path, $base_work_dir.$new_item_path);
										*/
    									DocumentManager::unset_document_as_template($deleted_items['id'], api_get_course_id(), api_get_user_id());
    									$sql = "UPDATE $TABLE_DOCUMENT set path = '".$new_item_path."' WHERE id = ".$deleted_items['id'];
	
    									api_sql_query($sql, __FILE__, __LINE__);  									
    								}
    							}
    							
                                DocumentManager::delete_document_from_search_engine(api_get_course_id(), $document_id);
    							return true;
    						}
    					}
                        else
                        {
                        	//Couldn't rename - file permissions problem?                        
                            error_log(__FILE__.' '.__LINE__.': Error renaming '.$base_work_dir.$path.' to '.$base_work_dir.$new_path.'. This is probably due to file permissions',0);
                        }
                    }
                    else
                    {	//echo $base_work_dir.$path;
                    	//The file or directory isn't there anymore (on the filesystem)
                        // This means it has been removed externally. To prevent a
                        // blocking error from happening, we drop the related items from the
                        // item_property and the document table.
                        error_log(__FILE__.' '.__LINE__.': System inconsistency detected. The file or directory '.$base_work_dir.$path.' seems to have been removed from the filesystem independently from the web platform. To restore consistency, the elements using the same path will be removed from the database',0);
                        $sql = "SELECT id FROM $TABLE_DOCUMENT WHERE path='".$path."' OR path LIKE BINARY '".$path."/%'";
                        $res = Database::query($sql,__FILE__,__LINE__);

                        DocumentManager::delete_document_from_search_engine(api_get_course_id(), $document_id);

                        while ( $row = Database::fetch_array($res) ) 
                        {
                        	$sqlipd = "DELETE FROM $TABLE_ITEMPROPERTY WHERE ref = ".$row['id']." AND tool='".TOOL_DOCUMENT."'";
                            $resipd = Database::query($sqlipd,__FILE__,__LINE__);
                            DocumentManager::unset_document_as_template($row['id'],api_get_course_id(), api_get_user_id());
                            $sqldd = "DELETE FROM $TABLE_DOCUMENT WHERE id = ".$row['id'];
                            $resdd = Database::query($sqldd,__FILE__,__LINE__); 
                        }
                    }
				}
			}

		}

        return false;
	}

	/**
	 * Removes documents from search engine database
	 *
	 * @param string $course_id Course code
	 * @param int $document_id Document id to delete
	 */
	function delete_document_from_search_engine($course_id, $document_id) {
		// remove from search engine if enabled
		if (api_get_setting('search_enabled') == 'true') {
			$tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
			$sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s LIMIT 1';
			$sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_DOCUMENT, $document_id);
			$res = api_sql_query($sql, __FILE__, __LINE__);
			if (Database::num_rows($res) > 0) {
				$row2 = Database::fetch_array($res);
				require_once(api_get_path(LIBRARY_PATH) .'search/DokeosIndexer.class.php');
				$di = new DokeosIndexer();
				$di->remove_document((int)$row2['search_did']);
			}     						
			$sql = 'DELETE FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s LIMIT 1';
			$sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_DOCUMENT, $document_id);
			api_sql_query($sql, __FILE__, __LINE__);

			// remove terms from db
			require_once(api_get_path(LIBRARY_PATH) .'specific_fields_manager.lib.php');
			delete_all_values_for_item($course_id, TOOL_DOCUMENT, $document_id);
		}
	}

	/**
	 * Gets the id of a document with a given path
	 *
	 * @param array $_course
	 * @param string $path
	 * @return int id of document / false if no doc found
	 */
	function get_document_id($_course, $path)
	{
		$TABLE_DOCUMENT = Database :: get_course_table(TABLE_DOCUMENT, $_course['dbName']);
		$path = Database::escape_string($path);		
		$sql = "SELECT id FROM $TABLE_DOCUMENT WHERE path LIKE BINARY '$path'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		if ($result && Database::num_rows($result) == 1) {
			$row = Database::fetch_array($result);
			return $row[0];
		} else {
			return false;
		}
	}
	
	
	/**
	 * Allow to set a specific document as a new template for FCKEditor for a particular user in a particular course
	 *
	 * @param string $title
	 * @param string $description
	 * @param int $document_id_for_template the document id
	 * @param string $couse_code
	 * @param int $user_id
	 */
	function set_document_as_template($title, $description, $document_id_for_template, $couse_code, $user_id, $image)
	{
		// Database table definition
		$table_template = Database::get_main_table(TABLE_MAIN_TEMPLATES);
		
		// creating the sql statement	
		$sql = "INSERT INTO ".$table_template."
					(title, description, course_code, user_id, ref_doc, image)
				VALUES (
					'".Database::escape_string($title)."',
					'".Database::escape_string($description)."',
					'".Database::escape_string($couse_code)."',
					'".Database::escape_string($user_id)."',
					'".Database::escape_string($document_id_for_template)."',
					'".Database::escape_string($image)."')";		
		api_sql_query($sql);
		
		return true;
	}
	
	
	/**
	 * Unset a document as template
	 *
	 * @param int $document_id
	 * @param string $couse_code
	 * @param int $user_id
	 */
	function unset_document_as_template($document_id, $course_code, $user_id){
		
		$table_template = Database::get_main_table(TABLE_MAIN_TEMPLATES);
		$course_code = Database::escape_string($course_code);
		$user_id = Database::escape_string($user_id);
		$document_id = Database::escape_string($document_id);
		
		$sql = 'SELECT id FROM '.$table_template.' WHERE course_code="'.$course_code.'" AND user_id="'.$user_id.'" AND ref_doc="'.$document_id.'"';
		$result = api_sql_query($sql);
		$template_id = Database::result($result,0,0);
		
		include_once(api_get_path(LIBRARY_PATH) . 'fileManage.lib.php');
		my_delete(api_get_path(SYS_CODE_PATH).'upload/template_thumbnails/'.$template_id.'.jpg');
		
		$sql = 'DELETE FROM '.$table_template.' WHERE course_code="'.$course_code.'" AND user_id="'.$user_id.'" AND ref_doc="'.$document_id.'"';
		
		api_sql_query($sql);
		
	}
	/**
	 * return true if the documentpath have visibility=1 as item_property
	 *
	 * @param string $document_path the relative complete path of the document
     * @param array  $course the _course array info of the document's course
	 */
	function is_visible($doc_path, $course){
       	$docTable  = Database::get_course_table(TABLE_DOCUMENT, $course['dbName']);
		$propTable = Database::get_course_table(TABLE_ITEM_PROPERTY, $course['dbName']);
        //note the extra / at the end of doc_path to match every path in the
        // document table that is part of the document path
        $doc_path = Database::escape_string($doc_path);
        
        $sql = "SELECT path FROM $docTable d, $propTable ip " .
                "where d.id=ip.ref AND ip.tool='".TOOL_DOCUMENT."' AND d.filetype='file' AND visibility=0 AND ".
                "locate(concat(path,'/'),'".$doc_path."/')=1";
        $result = api_sql_query($sql,__FILE__,__LINE__);
        if (Database::num_rows($result) > 0){
            $row = Database::fetch_array($result);
            //echo "$row[0] not visible";
            return false;
        }
		
		//improved protection of documents viewable directly through the url: incorporates the same protections of the course at the url of documents:	access allowed for the whole world Open, access allowed for users registered on the platform Private access, document accessible only to course members (see the Users list), Completely closed; the document is only accessible to the course admin and teaching assistants.
		if ($_SESSION ['is_allowed_in_course'] || api_is_platform_admin())
        {	
        	return true; // ok, document is visible
		}
		else
		{
			return false;
		}
    }

}
//end class DocumentManager
?>
