<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors

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
	FILE UPLOAD LIBRARY

*	This is the file upload library for Dokeos.
*	Include/require it in your code to use its functionality.
*
*	@package dokeos.library
*	@todo test and reorganise
==============================================================================
*/

/*
==============================================================================
List of functions
function php2phps ($fileName)
function htaccess2txt($filename)
function disable_dangerous_file($filename)
function unique_name($path,$name)
function get_document_title($name)
function process_uploaded_file($uploaded_file)
function handle_uploaded_document($_course,$uploaded_file,$base_work_dir,$upload_path,$user_id,$to_group_id,$to_user_id,$maxFilledSpace,$unzip=0,$what_if_file_exists='')
function enough_size($fileSize, $dir, $maxDirSpace) //depreciated
function enough_space($file_size, $max_dir_space)
function dir_total_space($dirPath) //depreciated
function documents_total_space()
function add_ext_on_mime($fileName,$fileType)
function treat_uploaded_file($uploadedFile, $baseWorkDir, $uploadPath, $maxFilledSpace, $uncompress= '') //depreciated
function unzip_uploaded_file($uploaded_file, $upload_path, $base_work_dir, $max_filled_space)
function clean_up_files_in_zip($p_event, &$p_header)
function clean_up_path(&$path)
function add_document($_course,$path,$filetype,$filesize,$title)
function update_existing_document($_course,$document_id,$filesize)
function item_property_update_on_folder($_course,$path,$user_id)
function get_levels($filename)
function set_default_settings($upload_path,$filename,$filetype="file")
function search_img_from_html($htmlFile)
function create_unexisting_directory($_course,$user_id,$base_work_dir,$desired_dir_name)
function move_uploaded_file_collection_into_directory($_course, $uploaded_file_collection, $base_work_dir, $missing_files_dir,$user_id,$max_filled_space)
function replace_img_path_in_html_file($originalImgPath, $newImgPath, $htmlFile)
function create_link_file($filePath, $url)
function api_replace_links_in_html($upload_path, $full_file_name)
function api_replace_links_in_string($upload_path, $buffer)
function check_for_missing_files($file)
function build_missing_files_form($missing_files,$upload_path,$file_name)
	Still experimental:
function api_replace_parameter($upload_path, $buffer, $param_name="src")
==============================================================================
*/

/**
 * Replaces all accentuated characters by non-accentuated characters for filenames, as
 * well as special HTML characters by their HTML entity's first letter.
 *
 * Although this method is not absolute, it gives good results in general. It first
 * transforms the string to HTML entities (&ocirc;, @oslash;, etc) then removes the
 * HTML character part to result in simple characters (o, o, etc).
 * In the case of special characters (out of alphabetical value) like &nbsp; and &lt;,
 * it will still replace them by the first letter of the HTML entity (n, l, ...) but it
 * is still an acceptable method, knowing we're filtering filenames here...
 * @param	string	The accentuated string
 * @return	string	The escaped string, not absolutely correct but satisfying
 */
function replace_accents($string){
	global $charset;
	$string = api_htmlentities($string,ENT_QUOTES,$charset);
	$res = preg_replace("/&([a-z])[a-z]+;/i","$1",$string);
	return $res;
}

//------------------------------------------------------------------------------

/**
 * change the file name extension from .php to .phps
 * Useful to secure a site !!
 *
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param  - fileName (string) name of a file
 * @return - the filenam phps'ized
 */

function php2phps ($fileName)
{
	$fileName = preg_replace('/\.(php.?|phtml.?)(\.){0,1}.*$/i', '.phps', $fileName);
	return $fileName;
}

//------------------------------------------------------------------------------

/**
 * Renames .htaccess & .HTACCESS tot htaccess.txt
 *
 * @param string $filename
 * @return string
 */
function htaccess2txt($filename)
{
	$filename = str_replace('.htaccess', 'htaccess.txt', $filename);
	$filename = str_replace('.HTACCESS', 'htaccess.txt', $filename);
    return $filename;
}

//------------------------------------------------------------------------------


/**
 * this function executes our safety precautions
 * more functions can be added
 *
 * @param string $filename
 * @return string
 * @see php2phps()
 * @see htaccess2txt()
 */
function disable_dangerous_file($filename)
{
	$filename = php2phps($filename);
	$filename = htaccess2txt($filename);
	return $filename;
}

//------------------------------------------------------------------------------

/**
 * this function generates a unique name for a file on a given location
 * filenames are changed to name_#.ext
 *
 * @param string $path
 * @param string $name
 * @return new unique name
 */
function unique_name($path,$name)
{
	$ext = substr(strrchr($name, "."), 0);
	$name_no_ext = substr($name, 0, strlen($name) - strlen(strstr($name,$ext)));
	$n = 0;
	$unique = '';
	while(file_exists($path . $name_no_ext . $unique . $ext))
	{
		$unique = '_' . ++$n;
	}
	return $name_no_ext . $unique . $ext;
}

//------------------------------------------------------------------------------

/**
 * Returns the name without extension, used for the title
 *
 * @param string $name
 * @return name without the extension
 */
function get_document_title($name)
{
	//if they upload .htaccess...
	$name = disable_dangerous_file($name);
	$ext = substr(strrchr($name, "."), 0);
	$name_no_ext = substr($name, 0, strlen($name) - strlen(strstr($name,$ext)));
	$filename = addslashes($name_no_ext);
	return $filename;
}

//------------------------------------------------------------------------------

/**
 * This function checks if the upload succeeded
 *
 * @param array $uploaded_file ($_FILES)
 * @return true if upload succeeded
 */
function process_uploaded_file($uploaded_file) {
	// Checking the error code sent with the file upload.
	switch ($uploaded_file['error']) {
		case 1:
			// The uploaded file exceeds the upload_max_filesize directive in php.ini.
			Display::display_error_message(get_lang('UplExceedMaxServerUpload'). ini_get('upload_max_filesize'));
			return false;
		case 2:
			// The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.
			// Not used at the moment, but could be handy if we want to limit the size of an upload (e.g. image upload in html editor).
			Display::display_error_message(get_lang('UplExceedMaxPostSize'). round($_POST['MAX_FILE_SIZE']/1024) ." KB");
			return false;
		case 3:
			// The uploaded file was only partially uploaded.
			Display::display_error_message(get_lang('$UplPartialUpload')." ".get_lang('PleaseTryAgain'));
			return false;
		case 4:
			// No file was uploaded.
			Display::display_error_message(get_lang('UplNoFileUploaded')." ". get_lang('UplSelectFileFirst'));
			return false;
	}
	// case 0: default: We assume there is no error, the file uploaded with success.
	return true;
}

//------------------------------------------------------------------------------

/**
 * this function does the save-work for the documents.
 * it handles the uploaded file and adds the properties to the database
 * if unzip=1 and the file is a zipfile, it is extracted
 * if we decide to save ALL kinds of documents in one database,
 * we could extend this with a $type='document', 'scormdocument',...
 *
 * @param array $_course
 * @param array $uploaded_file ($_FILES)
 * @param string $base_work_dir
 * @param string $upload_path
 * @param int $user_id
 * @param int $to_group_id, 0 for everybody
 * @param int $to_user_id, NULL for everybody
 * @param int $maxFilledSpace
 * @param int $unzip 1/0
 * @param string $what_if_file_exists overwrite, rename or warn if exists (default)
 * @param boolean Optional output parameter. So far only use for unzip_uploaded_document function. If no output wanted on success, set to false.
 * @return path of the saved file
 */
function handle_uploaded_document($_course,$uploaded_file,$base_work_dir,$upload_path,$user_id,$to_group_id=0,$to_user_id=NULL,$maxFilledSpace='',$unzip=0,$what_if_file_exists='',$output=true)
{
	if(!$user_id) die("Not a valid user.");

	//strip slashes
	$uploaded_file['name']=stripslashes($uploaded_file['name']);
	//add extension to files without one (if possible)
	$uploaded_file['name']=add_ext_on_mime($uploaded_file['name'],$uploaded_file['type']);
	$current_session_id = api_get_session_id();
	//check if there is enough space to save the file
	if (!enough_space($uploaded_file['size'], $maxFilledSpace))
	{
		Display::display_error_message(get_lang('UplNotEnoughSpace'));
		return false;
	}

	//if the want to unzip, check if the file has a .zip (or ZIP,Zip,ZiP,...) extension
	if ($unzip == 1 && preg_match("/.zip$/", strtolower($uploaded_file['name'])) )
	{
		return unzip_uploaded_document($uploaded_file, $upload_path, $base_work_dir, $maxFilledSpace, $output, $to_group_id);
		//display_message("Unzipping file");
	}
	//we can only unzip ZIP files (no gz, tar,...)
	elseif ($unzip == 1 && !preg_match("/.zip$/", strtolower($uploaded_file['name'])) )
	{
		Display::display_error_message(get_lang('UplNotAZip')." ".get_lang('PleaseTryAgain'));
		return false;
	}
	else
	{
	//clean up the name and prevent dangerous files
	//remove strange characters
	$clean_name = replace_dangerous_char($uploaded_file['name']);
	$clean_name = replace_accents($clean_name);
	//no "dangerous" files
	$clean_name = disable_dangerous_file($clean_name);
	if(!filter_extension($clean_name))
	{
		Display::display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
		return false;
	}
	else
	{
		//extension is good
		//echo "<br/>clean name = ".$clean_name;
		//echo "<br/>upload_path = ".$upload_path;
		//if the upload path differs from / (= root) it will need a slash at the end
		if ($upload_path!='/')
			$upload_path = $upload_path.'/';
		//echo "<br/>upload_path = ".$upload_path;
		$file_path = $upload_path.$clean_name;
		//echo "<br/>file path = ".$file_path;
		//full path to where we want to store the file with trailing slash
		$where_to_save = $base_work_dir.$upload_path;
		//at least if the directory doesn't exist, tell so
		if(!is_dir($where_to_save)){
			Display::display_error_message(get_lang('DestDirectoryDoesntExist').' ('.$upload_path.')');
			return false;
		}
		//echo "<br/>where to save = ".$where_to_save;
		// full path of the destination
		$store_path = $where_to_save.$clean_name;
		//echo "<br/>store path = ".$store_path;
		//name of the document without the extension (for the title)
		$document_name = get_document_title($uploaded_file['name']);
		//size of the uploaded file (in bytes)
		$file_size = $uploaded_file['size'];

		$files_perm = api_get_setting('permissions_for_new_files');
		$files_perm = octdec(!empty($files_perm)?$files_perm:'0770');

			//what to do if the target file exists
			switch ($what_if_file_exists)
				{
				//overwrite the file if it exists
				case 'overwrite':

					//check if the target file exists, so we can give another message
					if (file_exists($store_path))
					{
						$file_exists = true;
					}
					else
					{
						$file_exists = false;
					}
					if (@move_uploaded_file($uploaded_file['tmp_name'], $store_path))
					{
						chmod($store_path,$files_perm);
						if($file_exists)
						{
							//UPDATE DATABASE!
							$document_id = DocumentManager::get_document_id($_course,$file_path);
							if ($document_id)
							{
								//update filesize
								update_existing_document($_course,$document_id,$uploaded_file['size']);
								//update document item_property
								api_item_property_update($_course,TOOL_DOCUMENT,$document_id,'DocumentUpdated',$user_id,$to_group_id,$to_user_id,null,null,$current_session_id);
							}
							//if the file is in a folder, we need to update all parent folders
							item_property_update_on_folder($_course,$upload_path,$user_id);
							//display success message with extra info to user
							if($output){
								Display::display_confirmation_message(get_lang('UplUploadSucceeded')."<br/>".$file_path .' '. get_lang('UplFileOverwritten'),false);
							}
							return $file_path;
						}
						else
						{
							//put the document data in the database
							$document_id = add_document($_course,$file_path,'file',$file_size,$document_name);
							if ($document_id)
							{
								//put the document in item_property update
								api_item_property_update($_course,TOOL_DOCUMENT,$document_id,'DocumentAdded',$user_id,$to_group_id,$to_user_id,null,null,$current_session_id);
							}
							//if the file is in a folder, we need to update all parent folders
							item_property_update_on_folder($_course,$upload_path,$user_id);
							//display success message to user
							Display::display_confirmation_message(get_lang('UplUploadSucceeded')."<br/>".$file_path,false);
							return $file_path;
						}
					}
					else
					{
						Display::display_error_message(get_lang('UplUnableToSaveFile'));
						return false;
					}
					break;

				//rename the file if it exists
				case 'rename':
					$new_name = unique_name($where_to_save, $clean_name);
					$store_path = $where_to_save.$new_name;
					$new_file_path = $upload_path.$new_name;

					if (@move_uploaded_file($uploaded_file['tmp_name'], $store_path))
					{
						chmod($store_path,$files_perm);

						//put the document data in the database
						$document_id = add_document($_course,$new_file_path,'file',$file_size,$document_name);
						if ($document_id)
						{
							//update document item_property
							api_item_property_update($_course,TOOL_DOCUMENT,$document_id,'DocumentAdded',$user_id,$to_group_id,$to_user_id,null,null,$current_session_id);
						}
						//if the file is in a folder, we need to update all parent folders
						item_property_update_on_folder($_course,$upload_path,$user_id);
						//display success message to user
						if($output){
							Display::display_confirmation_message(get_lang('UplUploadSucceeded'). "<br>" .get_lang('UplFileSavedAs') . $new_file_path,false);
						}
						return $new_file_path;
					}
					else
					{
						Display::display_error_message(get_lang('UplUnableToSaveFile'));
						return false;
					}
					break;

				//only save the file if it doesn't exist or warn user if it does exist
				default:
					if (file_exists($store_path))
					{
						Display::display_error_message($clean_name.' '.get_lang('UplAlreadyExists'));
					}
					else
					{
						if (@move_uploaded_file($uploaded_file['tmp_name'], $store_path))
						{
							chmod($store_path,$files_perm);

							//put the document data in the database
							$document_id = add_document($_course,$file_path,'file',$file_size,$document_name);
							if ($document_id)
							{
								//update document item_property
								api_item_property_update($_course,TOOL_DOCUMENT,$document_id,'DocumentAdded',$user_id,$to_group_id,$to_user_id,null,null,$current_session_id);
							}
							//if the file is in a folder, we need to update all parent folders
							item_property_update_on_folder($_course,$upload_path,$user_id);
							//display success message to user
							if($output){
								Display::display_confirmation_message(get_lang('UplUploadSucceeded')."<br/>".$file_path,false);
							}
							return $file_path;
						}
						else
						{
							Display::display_error_message(get_lang('UplUnableToSaveFile'));
							return false;
						}
					}
					break;
				}
		}
	}
}

//------------------------------------------------------------------------------

/**
 * Check if there is enough place to add a file on a directory
 * on the base of a maximum directory size allowed
 * @deprecated use enough_space instead!
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param  - fileSize (int) - size of the file in byte
 * @param  - dir (string) - Path of the directory
 *           whe the file should be added
 * @param  - maxDirSpace (int) - maximum size of the diretory in byte
 * @return - boolean true if there is enough space,
 *				boolean false otherwise
 *
 * @see    - enough_size() uses  dir_total_space() function
 */

function enough_size($fileSize, $dir, $maxDirSpace)
{
	if ($maxDirSpace)
	{
		$alreadyFilledSpace = dir_total_space($dir);

		if ( ($fileSize + $alreadyFilledSpace) > $maxDirSpace)
		{
			return false;
		}
	}

	return true;
}

//------------------------------------------------------------------------------

/**
 * Check if there is enough place to add a file on a directory
 * on the base of a maximum directory size allowed
 *
 * @author Bert Vanderkimpen
 * @param  int file_size size of the file in byte
 * @param array $_course
 * @param  int max_dir_space maximum size
 * @return boolean true if there is enough space, false otherwise
 *
 * @see enough_space() uses  documents_total_space() function
 */

function enough_space($file_size, $max_dir_space)
{
	if ($max_dir_space)
	{
		$already_filled_space = documents_total_space();
		if ( ($file_size + $already_filled_space) > $max_dir_space)
		{
			return false;
		}
	}

	return true;
}

//------------------------------------------------------------------------------

/**
 * Compute the size already occupied by a directory and is subdirectories
 *
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param  - dirPath (string) - size of the file in byte
 * @return - int - return the directory size in bytes
 */

function dir_total_space($dirPath)
{
	$save_dir = getcwd();
	chdir ($dirPath) ;
	$handle = opendir($dirPath);

	while ($element = readdir($handle) )
	{
		if ( $element == "." || $element == "..")
		{
			continue; // skip the current and parent directories
		}
		if ( is_file($element) )
		{
			$sumSize += filesize($element);
		}
		if ( is_dir($element) )
		{
			$dirList[] = $dirPath."/".$element;
		}
	}

	closedir($handle) ;

	if ( sizeof($dirList) > 0)
	{
		foreach($dirList as $j)
		{
			$sizeDir = dir_total_space($j);	// recursivity
			$sumSize += $sizeDir;
		}
	}
	chdir($save_dir);//return to initial position
	return $sumSize;
}

//------------------------------------------------------------------------------

/**
 * Calculate the total size of all documents in a course
 *
 * @author Bert vanderkimpen
 * @param  int $to_group_id (to calculate group document space)
 * @return int total size
 */

function documents_total_space($to_group_id='0')
{
	$TABLE_ITEMPROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);
	$TABLE_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT);

	$sql = "SELECT SUM(size)
	FROM  ".$TABLE_ITEMPROPERTY."  AS props, ".$TABLE_DOCUMENT."  AS docs
	WHERE docs.id = props.ref
	AND props.tool = '".TOOL_DOCUMENT."'
	AND props.to_group_id='".$to_group_id."'
	AND props.visibility <> 2";

	$result = Database::query($sql,__FILE__,__LINE__);

	if($result && mysql_num_rows($result)!=0)
	{
		$row = mysql_fetch_row($result);

		return $row[0];
	}
	else
	{
		return 0;
	}
}

//------------------------------------------------------------------------------

/**
 * Try to add an extension to files without extension
 * Some applications on Macintosh computers don't add an extension to the files.
 * This subroutine try to fix this on the basis of the MIME type sent
 * by the browser.
 *
 * Note : some browsers don't send the MIME Type (e.g. Netscape 4).
 *        We don't have solution for this kind of situation
 *
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @author - Bert Vanderkimpen
 * @param  - fileName (string) - Name of the file
 * @param  - fileType (string) - Type of the file
 * @return - fileName (string)
 *
 */

function add_ext_on_mime($fileName,$fileType)
{
	/*
	 * Check if the file has an extension AND if the browser has sent a MIME Type
	 */

	//if(!ereg("([[:alnum:]]|[[[:punct:]])+\.[[:alnum:]]+$", $fileName) // TODO: This fails sometimes on non-ASCII encoded file names. To be removed.
	//	&& $fileType)
	if(!preg_match('/^.*\.[a-zA-Z_0-9]+$/', $fileName) && $fileType)
	{
		/*
		 * Build a "MIME-types / extensions" connection table
		 */

		static $mimeType = array();

		$mimeType[] = "application/msword";             $extension[] =".doc";
		$mimeType[] = "application/rtf";                $extension[] =".rtf";
		$mimeType[] = "application/vnd.ms-powerpoint";  $extension[] =".ppt";
		$mimeType[] = "application/vnd.ms-excel";       $extension[] =".xls";
		$mimeType[] = "application/pdf";                $extension[] =".pdf";
		$mimeType[] = "application/postscript";         $extension[] =".ps";
		$mimeType[] = "application/mac-binhex40";       $extension[] =".hqx";
		$mimeType[] = "application/x-gzip";             $extension[] ="tar.gz";
		$mimeType[] = "application/x-shockwave-flash";  $extension[] =".swf";
		$mimeType[] = "application/x-stuffit";          $extension[] =".sit";
		$mimeType[] = "application/x-tar";              $extension[] =".tar";
		$mimeType[] = "application/zip";                $extension[] =".zip";
		$mimeType[] = "application/x-tar";              $extension[] =".tar";
		$mimeType[] = "text/html";                      $extension[] =".html";
		$mimeType[] = "text/plain";                     $extension[] =".txt";
		$mimeType[] = "text/rtf";                       $extension[] =".rtf";
		$mimeType[] = "img/gif";                        $extension[] =".gif";
		$mimeType[] = "img/jpeg";                       $extension[] =".jpg";
		$mimeType[] = "img/png";                        $extension[] =".png";
		$mimeType[] = "audio/midi";                     $extension[] =".mid";
		$mimeType[] = "audio/mpeg";                     $extension[] =".mp3";
		$mimeType[] = "audio/x-aiff";                   $extension[] =".aif";
		$mimeType[] = "audio/x-pn-realaudio";           $extension[] =".rm";
		$mimeType[] = "audio/x-pn-realaudio-plugin";    $extension[] =".rpm";
		$mimeType[] = "audio/x-wav";                    $extension[] =".wav";
		$mimeType[] = "video/mpeg";                     $extension[] =".mpg";
		$mimeType[] = "video/mpeg4-generic";            $extension[] =".mp4";
		$mimeType[] = "video/quicktime";                $extension[] =".mov";
		$mimeType[] = "video/x-msvideo";                $extension[] =".avi";

		$mimeType[] = "video/x-ms-wmv";                	$extension[] =".wmv";
		$mimeType[] = "video/x-flv";                    $extension[] =".flv";

		$mimeType[] = "application/vnd.ms-word.document.macroEnabled.12";							$extension[] =".docm";
		$mimeType[] = "application/vnd.openxmlformats-officedocument.wordprocessingml.document";	$extension[] =".docx";
		$mimeType[] = "application/vnd.ms-word.template.macroEnabled.12";							$extension[] =".dotm";
		$mimeType[] = "application/vnd.openxmlformats-officedocument.wordprocessingml.template";	$extension[] =".dotx";
		$mimeType[] = "application/vnd.ms-powerpoint.template.macroEnabled.12";						$extension[] =".potm";
		$mimeType[] = "application/vnd.openxmlformats-officedocument.presentationml.template";		$extension[] =".potx";
		$mimeType[] = "application/vnd.ms-powerpoint.addin.macroEnabled.12";						$extension[] =".ppam";
		$mimeType[] = "application/vnd.ms-powerpoint.slideshow.macroEnabled.12";					$extension[] =".ppsm";
		$mimeType[] = "application/vnd.openxmlformats-officedocument.presentationml.slideshow";		$extension[] =".ppsx";
		$mimeType[] = "application/vnd.ms-powerpoint.presentation.macroEnabled.12";					$extension[] =".pptm";
		$mimeType[] = "application/vnd.openxmlformats-officedocument.presentationml.presentation";	$extension[] =".pptx";
		$mimeType[] = "application/vnd.ms-excel.addin.macroEnabled.12";								$extension[] =".xlam";
		$mimeType[] = "application/vnd.ms-excel.sheet.binary.macroEnabled.12";						$extension[] =".xlsb";
		$mimeType[] = "application/vnd.ms-excel.sheet.macroEnabled.12";								$extension[] =".xlsm";
		$mimeType[] = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";			$extension[] =".xlsx";
		$mimeType[] = "application/vnd.ms-excel.template.macroEnabled.12";							$extension[] =".xltm";
		$mimeType[] = "application/vnd.openxmlformats-officedocument.spreadsheetml.template";		$extension[] =".xltx";

		//test on PC (files with no extension get application/octet-stream)
		//$mimeType[] = "application/octet-stream";      $extension[] =".ext";

		/*
		 * Check if the MIME type sent by the browser is in the table
		 */

		foreach($mimeType as $key=>$type)
		{
			if ($type == $fileType)
			{
				$fileName .=  $extension[$key];
				break;
			}
		}

		unset($mimeType, $extension, $type, $key); // Delete to eschew possible collisions
	}

	return $fileName;
}

//------------------------------------------------------------------------------

/**
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 *
 * @param  array $uploadedFile - follows the $_FILES Structure
 * @param  string $baseWorkDir - base working directory of the module
 * @param  string $uploadPath  - destination of the upload.
 *                               This path is to append to $baseWorkDir
 * @param  int $maxFilledSpace - amount of bytes to not exceed in the base
 *                               working directory
 *
 * @return boolean true if it succeds, false otherwise
 */
function treat_uploaded_file($uploadedFile, $baseWorkDir, $uploadPath, $maxFilledSpace, $uncompress= '')
{
	$uploadedFile['name']=stripslashes($uploadedFile['name']);

	if (!enough_size($uploadedFile['size'], $baseWorkDir, $maxFilledSpace))
	{
		return api_failure::set_failure('not_enough_space');
	}

	if ($uncompress == 'unzip' && preg_match("/.zip$/", strtolower($uploadedFile['name'])) )
	{
		return unzip_uploaded_file($uploadedFile, $uploadPath, $baseWorkDir, $maxFilledSpace);
	}
	else
	{
		$fileName = trim($uploadedFile['name']);

		// CHECK FOR NO DESIRED CHARACTERS
		$fileName = replace_dangerous_char($fileName);

		// TRY TO ADD AN EXTENSION TO FILES WITOUT EXTENSION
		$fileName = add_ext_on_mime($fileName,$uploadedFile['type']);

		// HANDLE PHP FILES
		$fileName = php2phps($fileName);

		// COPY THE FILE TO THE DESIRED DESTINATION
		if(move_uploaded_file($uploadedFile['tmp_name'], $baseWorkDir.$uploadPath."/".$fileName))
				set_default_settings($uploadPath,$fileName);

		return true;
	}
}
/**
 * Manages all the unzipping process of an uploaded file
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 *
 * @param  array  $uploadedFile - follows the $_FILES Structure
 * @param  string $uploadPath   - destination of the upload.
 *                                This path is to append to $baseWorkDir
 * @param  string $baseWorkDir  - base working directory of the module
 * @param  int $maxFilledSpace  - amount of bytes to not exceed in the base
 *                                working directory
 *
 * @return boolean true if it succeeds false otherwise
 */

function unzip_uploaded_file($uploadedFile, $uploadPath, $baseWorkDir, $maxFilledSpace)
{
	$zipFile = new pclZip($uploadedFile['tmp_name']);

	// Check the zip content (real size and file extension)

	$zipContentArray = $zipFile->listContent();

	$okScorm=false;

	foreach($zipContentArray as $thisContent)
	{
		if ( preg_match('~.(php.*|phtml)$~i', $thisContent['filename']) )
		{
			return api_failure::set_failure('php_file_in_zip_file');
		}
		elseif(stristr($thisContent['filename'],'imsmanifest.xml'))
		{
			$okScorm=true;
		}
		elseif(stristr($thisContent['filename'],'LMS'))
		{
			$okPlantynScorm1=true;
		}
		elseif(stristr($thisContent['filename'],'REF'))
		{
			$okPlantynScorm2=true;
		}
		elseif(stristr($thisContent['filename'],'SCO'))
		{
			$okPlantynScorm3=true;
		}
		elseif(stristr($thisContent['filename'],'AICC'))
		{
			$okAiccScorm=true;
		}

		$realFileSize += $thisContent['size'];
	}

	if ((($okPlantynScorm1==true) and ($okPlantynScorm2==true) and ($okPlantynScorm3==true)) or ($okAiccScorm==true))
	{
		$okScorm=true;
	}

	if(!$okScorm && defined('CHECK_FOR_SCORM') && CHECK_FOR_SCORM)
	{
		return api_failure::set_failure('not_scorm_content');
	}

	if (! enough_size($realFileSize, $baseWorkDir, $maxFilledSpace) )
	{
		return api_failure::set_failure('not_enough_space');
	}

	// it happens on Linux that $uploadPath sometimes doesn't start with '/'
	if($uploadPath[0] != '/')
	{
		$uploadPath='/'.$uploadPath;
	}

	if($uploadPath[strlen($uploadPath)-1] == '/')
	{
		$uploadPath=substr($uploadPath,0,-1);
	}

	/*
	--------------------------------------
		Uncompressing phase
	--------------------------------------
	*/
	/*
		The first version, using OS unzip, is not used anymore
		because it does not return enough information.
		We need to process each individual file in the zip archive to
		- add it to the database
		- parse & change relative html links
	*/
	if (PHP_OS == 'Linux' && ! get_cfg_var('safe_mode') && false)	// *** UGent, changed by OC ***
	{
		// Shell Method - if this is possible, it gains some speed
		exec("unzip -d \"".$baseWorkDir.$uploadPath."/\"".$uploadedFile['name']." "
			 .$uploadedFile['tmp_name']);
	}
	else
	{
		// PHP method - slower...
		$save_dir = getcwd();
		chdir($baseWorkDir.$uploadPath);
		$unzippingState = $zipFile->extract();
		for($j=0;$j<count($unzippingState);$j++)
		{
			$state=$unzippingState[$j];

			//fix relative links in html files
			$extension = strrchr($state["stored_filename"], ".");
		}

		if($dir=@opendir($baseWorkDir.$uploadPath))
		{
			while($file=readdir($dir))
			{
				if($file != '.' && $file != '..')
				{
					$filetype="file";

					if(is_dir($baseWorkDir.$uploadPath.'/'.$file)) $filetype="folder";

					$safe_file=replace_dangerous_char($file,'strict');

					@rename($baseWorkDir.$uploadPath.'/'.$file,$baseWorkDir.$uploadPath.'/'.$safe_file);

					set_default_settings($uploadPath,$safe_file,$filetype);
				}
			}

			closedir($dir);
		}
		chdir($save_dir); //back to previous dir position
	}

	return true;
}
//------------------------------------------------------------------------------

/**
 * Manages all the unzipping process of an uploaded document
 * This uses the item_property table for properties of documents
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @author Bert Vanderkimpen
 *
 * @param  array  $uploadedFile - follows the $_FILES Structure
 * @param  string $uploadPath   - destination of the upload.
 *                                This path is to append to $baseWorkDir
 * @param  string $baseWorkDir  - base working directory of the module
 * @param  int $maxFilledSpace  - amount of bytes to not exceed in the base
 *                                working directory
 * @param		boolean	Output switch. Optional. If no output not wanted on success, set to false.
 *
 * @return boolean true if it succeeds false otherwise
 */

function unzip_uploaded_document($uploaded_file, $upload_path, $base_work_dir, $max_filled_space, $output = true, $to_group_id=0)
{
	global $_course;
	global $_user;
	global $to_user_id;
	global $to_group_id;

	$zip_file = new pclZip($uploaded_file['tmp_name']);

	// Check the zip content (real size and file extension)

	$zip_content_array = $zip_file->listContent();

	foreach((array) $zip_content_array as $this_content)
	{
		$real_filesize += $this_content['size'];
	}

	if (! enough_space($real_filesize, $max_filled_space) )
	{
		Display::display_error_message(get_lang('UplNotEnoughSpace'));
		return false;
	}

	// it happens on Linux that $uploadPath sometimes doesn't start with '/'
	if($upload_path[0] != '/')
	{
		$upload_path='/'.$upload_path;
	}
	/*
	--------------------------------------
		Uncompressing phase
	--------------------------------------
	*/
	//get into the right directory
	$save_dir = getcwd();
	chdir($base_work_dir.$upload_path);
	//we extract using a callback function that "cleans" the path
	$unzipping_state = $zip_file->extract(PCLZIP_CB_PRE_EXTRACT, 'clean_up_files_in_zip');
	// Add all documents in the unzipped folder to the database
	add_all_documents_in_folder_to_database($_course,$_user['user_id'],$base_work_dir,$upload_path == '/' ? '' : $upload_path, $to_group_id);
	//Display::display_normal_message(get_lang('UplZipExtractSuccess'));
	return true;
	/*
	if ($upload_path != '/')
		$upload_path = $upload_path.'/';
	if($unzipping_state!=0)
	{
		for($j=0;$j<count($unzipping_state);$j++)
		{
			$state=$unzipping_state[$j];
			$filename = $state['stored_filename'];
			//echo("<br>filename = ".$filename."<br>");
			$filename2 = $state['filename'];
			//echo("<br>filename2 = ".$filename2."<br>");
			$filetype="file";
			//if(is_dir($filename))
			if($state['folder']==1)
			{
				$filetype="folder";
				$endchar=substr($filename,strlen($filename)-1,1);
				if($endchar=="\\" || $endchar=="/")
					$filename=substr($filename,0,strlen($filename)-1);
			}

			//store document in database
			if($state['status']=="ok" || $state['status']=="already_a_directory")
			{
				//echo $base_work_dir.$upload_path.clean_up_path($state["stored_filename"])." (".$filetype.")<br/>";
				$cleaned_up_filename = clean_up_path($filename);
				$file_path = $upload_path.$cleaned_up_filename;
				echo("file path = ".$file_path."<br>");

				//this is a quick fix for zipfiles that have files in folders but the folder is not stored in the zipfile
				//if the path has folders, check if they already are in the database
				if(dirname('/'.$cleaned_up_filename)!='/' AND dirname('/'.$cleaned_up_filename)!='\\')
				{
					$folder_id=DocumentManager::get_document_id($_course,$upload_path.dirname($cleaned_up_filename));
					if(!$folder_id)
					{
						echo($upload_path.dirname($cleaned_up_filename).' not found in database!<br>');
						$folder_id = add_document($_course,$upload_path.dirname($cleaned_up_filename),'folder',0,basename(dirname($cleaned_up_filename)));
						if($folder_id)
						{
							api_item_property_update($_course,TOOL_DOCUMENT,$folder_id,'FolderAdded',$_user['user_id'],$to_group_id,$to_user_id);
							//echo('folder '.$upload_path.dirname($cleaned_up_filename)." added<br>\n");
						}
					}
				}

				$store_path = $base_work_dir.$file_path;
				//echo("store path = ".$store_path."<br>");
				$document_name = get_document_title(basename($filename));
				//echo("document_name = ".$document_name."<br><br>");
				//put the document data in the database
				//if the file/dir does not exist, just add it
				//if(!file_exists($store_path)) <- not working, as the file is already extracted
				//so we check if the document is already in the database
				$document_id = DocumentManager::get_document_id($_course,$file_path);
				if(!$document_id)
				{
				$document_id = add_document($_course,$file_path,$filetype,$state['size'],$document_name);
					if ($document_id)
					{
						$lastedit_type = ($filetype=='folder')?'FolderAdded':'DocumentAdded';
						//update item property for document
						api_item_property_update($_course,TOOL_DOCUMENT,$document_id,$lastedit_type,$_user['user_id'],$to_group_id,$to_user_id);
					}
				}
				//file/dir exists -> update
				else
				{
					$lastedit_type = ($filetype=='folder')?'FolderUpdated':'DocumentUpdated';
					//update the document in item_property
					api_item_property_update($_course,TOOL_DOCUMENT,$document_id,$lastedit_type,$_user['user_id'],$to_group_id,$to_user_id);
				}

			}
		}
	//print_r_pre($zip_content_array);
	//if the file is in a folder, we need to update all parent folders
	item_property_update_on_folder($_course,$upload_path,$_user['user_id']);
	//display success message to user
	chdir($save_dir); //return to previous dir position
	if($output){
		Display::display_normal_message(get_lang('UplZipExtractSuccess'));
	}
	return true;
	}
	else {
		//zip file could not be extracted -> corrupt file
		Display::display_error_message(get_lang('UplZipCorrupt'));
		return false;
	}
	*/
}

//------------------------------------------------------------------------------

/**
 * this function is a callback function that is used while extracting a zipfile
 * http://www.phpconcept.net/pclzip/man/en/index.php?options-pclzip_cb_pre_extract
 *
 * @param $p_event
 * @param $p_header
 * @return 1 (If the function returns 1, then the extraction is resumed)
 */
function clean_up_files_in_zip($p_event, &$p_header)
{
	$res = clean_up_path($p_header['filename']);
	return $res;
}

//------------------------------------------------------------------------------

/**
 * this function cleans up a given path
 * by eliminating dangerous file names and cleaning them
 *
 * @param string $path
 * @return $path
 * @see disable_dangerous_file()
 * @see replace_dangerous_char()
 */
function clean_up_path(&$path)
{
	//split the path in folders and files
    $path_array = explode('/',$path);
    //clean up every foler and filename in the path
    $val = '';
    foreach($path_array as $key => $val)
	{
		//we don't want to lose the dots in ././folder/file (cfr. zipfile)
		if($path_array[$key]!='.')
			$path_array[$key] = disable_dangerous_file( replace_dangerous_char($val) );
	}
	//join the "cleaned" path (modified in-place as passed by reference)
	$path = implode('/',$path_array);
	$res = filter_extension($path);
	return $res;
}

/**
 * Check if the file is dangerous, based on extension and/or mimetype.
 * The list of extensions accepted/rejected can be found from
 * api_get_setting('upload_extensions_exclude') and api_get_setting('upload_extensions_include')
 * @param	string 	filename passed by reference. The filename will be modified if filter rules say so! (you can include path but the filename should look like 'abc.html')
 * @return	int		0 to skip file, 1 to keep file
 */
function filter_extension(&$filename)
{
	if(substr($filename,-1)=='/'){return 1;} //authorize directories
	$blacklist = api_get_setting('upload_extensions_list_type');
	if($blacklist!='whitelist')//if = blacklist
	{
		$extensions = split(';',strtolower(api_get_setting('upload_extensions_blacklist')));
		$skip = api_get_setting('upload_extensions_skip');
		$ext = strrchr($filename, ".");
		$ext = substr($ext,1);
		if(empty($ext)){return 1;}//we're in blacklist mode, so accept empty extensions
		if(in_array(strtolower($ext),$extensions))
		{
			if($skip=='true')
			{
				return 0;
			}
			else
			{
				$new_ext = api_get_setting('upload_extensions_replace_by');
				$filename = str_replace(".".$ext,".".$new_ext,$filename);
				return 1;
			}
		}
		else
		{
			return 1;
		}
	}
	else
	{
		$extensions = split(';',strtolower(api_get_setting('upload_extensions_whitelist')));
		$skip = api_get_setting('upload_extensions_skip');
		$ext = strrchr($filename, ".");
		$ext = substr($ext,1);
		if(empty($ext)){return 1;}//accept empty extensions
		if(!in_array(strtolower($ext),$extensions))
		{
			if($skip=='true')
			{
				return 0;
			}
			else
			{
				$new_ext = api_get_setting('upload_extensions_replace_by');
				$filename = str_replace(".".$ext,".".$new_ext,$filename);
				return 1;
			}
		}
		else
		{
			return 1;
		}
	}
}

//------------------------------------------------------------------------------

/**
 * Adds a new document to the database
 *
 * @param array $_course
 * @param string $path
 * @param string $filetype
 * @param int $filesize
 * @param string $title
 * @return id if inserted document
 */
function add_document($_course,$path,$filetype,$filesize,$title,$comment=NULL, $readonly=0)
{
	global $charset;
	$session_id = api_get_session_id();
	$table_document = Database::get_course_table(TABLE_DOCUMENT,$_course['dbName']);
	$sql="INSERT INTO $table_document
	(`path`, `filetype`, `size`, `title`, `comment`, `readonly`, `session_id`)
	VALUES ('$path','$filetype','$filesize','".
	Database::escape_string(htmlspecialchars($title, ENT_QUOTES, $charset))."', '$comment', $readonly, $session_id)";
	if(Database::query($sql,__FILE__,__LINE__))
	{
		//display_message("Added to database (id ".mysql_insert_id().")!");
		return Database::insert_id();
	}
	else
	{
		//display_error("The uploaded file could not be added to the database (".mysql_error().")!");
		return false;
	}
}

//------------------------------------------------------------------------------

/*
function get_document_id() moved to document.lib.php
*/

//------------------------------------------------------------------------------

/**
 * Update an existing document in the database
 * as the file exists, we only need to change the size
 *
 * @param array $_course
 * @param int $document_id
 * @param int $filesize
 * @param int $readonly
 * @return boolean true /false
 */
function update_existing_document($_course,$document_id,$filesize,$readonly=0)
{
	$document_table = Database::get_course_table(TABLE_DOCUMENT,$_course['dbName']);
	$sql="UPDATE $document_table SET size = '$filesize' , readonly = '$readonly' WHERE id='$document_id'";
	if(Database::query($sql,__FILE__,__LINE__))
	{
		return true;
	}
	else
	{
		return false;
	}
}


/**
 * this function updates the last_edit_date, last edit user id on all folders in a given path
 *
 * @param array $_course
 * @param string $path
 * @param int $user_id
 */
function item_property_update_on_folder($_course,$path,$user_id)
{
	//display_message("Start update_lastedit_on_folder");
	//if we are in the root, just return... no need to update anything
	if ($path=='/')
		return;

	//if the given path ends with a / we remove it
	$endchar=substr($path,strlen($path)-1,1);
	if($endchar=='/')
	$path=substr($path,0,strlen($path)-1);
	$TABLE_ITEMPROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY,$_course['dbName']);

	//get the time
	$time = date("Y-m-d H:i:s", time());

	//get all paths in the given path
	// /folder/subfolder/subsubfolder/file
	// if file is updated, subsubfolder, subfolder and folder are updated

	$exploded_path = explode('/',$path);

	foreach ($exploded_path as $key => $value) {
		//we don't want a slash before our first slash
		if($key!=0){
			$newpath .= "/".$value;

			//echo "path= ".$newpath."<br>";
			//select ID of given folder
			$folder_id = DocumentManager::get_document_id($_course,$newpath);

			if($folder_id)
			{
				$sql = "UPDATE $TABLE_ITEMPROPERTY SET `lastedit_date`='$time',`lastedit_type`='DocumentInFolderUpdated', `lastedit_user_id`='$user_id' WHERE tool='".TOOL_DOCUMENT."' AND ref='$folder_id'";
				Database::query($sql,__FILE__,__LINE__);
			}
		}
	}
}
//------------------------------------------------------------------------------

/**
 * Returns the directory depth of the file.
 *
 * @author	Olivier Cauberghe <olivier.cauberghe@ugent.be>
 * @param	path+filename eg: /main/document/document.php
 * @return	The directory depth
 */
function get_levels($filename)
{
	$levels=explode("/",$filename);
	if(empty($levels[count($levels)-1])) unset($levels[count($levels)-1]);
	return count($levels);
}

/*
	function file_set_default_settings

	moved to fileManage.lib.php,
	class FileManager
*/

//------------------------------------------------------------------------------

/**
 * Adds file to document table in database
 * @deprecated, use file_set_default_settings instead
 *
 * @author	Olivier Cauberghe <olivier.cauberghe@ugent.be>
 * @param	path,filename
 * @action	Adds an entry to the document table with the default settings.
 */
function set_default_settings($upload_path,$filename,$filetype="file")
{
	global $dbTable,$_configuration;
	global $default_visibility;

	if (!$default_visibility)
		$default_visibility="v";

	$upload_path=str_replace('\\','/',$upload_path);
	$upload_path=str_replace("//","/",$upload_path);

	if($upload_path == '/')
	{
		$upload_path='';
	}
	elseif(!empty($upload_path) && $upload_path[0] != '/')
	{
		$upload_path="/$upload_path";
	}

	$endchar=substr($filename,strlen($filename)-1,1);

	if($endchar == '/')
	{
		$filename=substr($filename,0,-1);
	}

	//$dbTable already has `backticks`!
	//$query="select count(*) as bestaat from `$dbTable` where path='$upload_path/$filename'";
	$query="select count(*) as bestaat from $dbTable where path='$upload_path/$filename'";
	$result=Database::query($query,__FILE__,__LINE__);
	$row=mysql_fetch_array($result);
	if($row["bestaat"]>0)
		//$query="update `$dbTable` set path='$upload_path/$filename',visibility='$default_visibility', filetype='$filetype' where path='$upload_path/$filename'";
		$query="update $dbTable set path='$upload_path/$filename',visibility='$default_visibility', filetype='$filetype' where path='$upload_path/$filename'";
	else //$query="INSERT INTO `$dbTable` (path,visibility,filetype) VALUES('$upload_path/$filename','$default_visibility','$filetype')";
		$query="INSERT INTO $dbTable (path,visibility,filetype) VALUES('$upload_path/$filename','$default_visibility','$filetype')";
	Database::query($query,__FILE__,__LINE__);
}

//------------------------------------------------------------------------------

/**
 * retrieve the image path list in a html file
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @param  string $htmlFile
 * @return array -  images path list
 */

function search_img_from_html($htmlFile)
{
	$imgFilePath = array();

	$fp = fopen($htmlFile, "r") or die('<center>can not open file</center>');

	// search and store occurences of the <IMG> tag in an array
	$size_file=(filesize($htmlFile)===0) ? 1 : filesize($htmlFile);
	if (isset($fp) && !($fp===false)) {
		$buffer = fread( $fp, $size_file );
		if (strlen($buffer)>=0 && !($buffer===false)) {
			//
		} else {
			die('<center>Can not read file.</center>');
		}
	} else {
		die('<center>Can not read file.</center>');
	}
	$matches = array();
	if ( preg_match_all('~<[[:space:]]*img[^>]*>~i', $buffer, $matches) )
	{
		$imgTagList = $matches[0];
	}

	fclose ($fp); unset($buffer);

	// Search the image file path from all the <IMG> tag detected

	if ( sizeof($imgTagList)  > 0)
	{
		foreach($imgTagList as $thisImgTag)
		{
			if ( preg_match('~src[[:space:]]*=[[:space:]]*[\"]{1}([^\"]+)[\"]{1}~i',
							$thisImgTag, $matches) )
			{
				$imgPathList[] = $matches[1];
			}
		}

		$imgPathList = array_unique($imgPathList);		// remove duplicate entries
	}

	return $imgPathList;

}

//------------------------------------------------------------------------------

/**
 * creates a new directory trying to find a directory name
 * that doesn't already exist
 * (we could use unique_name() here...)
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @author Bert Vanderkimpen
 * @param array $_course current course information
 * @param int $user_id current user id
 * @param string $desiredDirName complete path of the desired name
 * @return string actual directory name if it succeeds,
 *         boolean false otherwise
 */

function create_unexisting_directory($_course,$user_id,$to_group_id,$to_user_id,$base_work_dir,$desired_dir_name, $title = null)
{
	$nb = '';
	while ( file_exists($base_work_dir.$desired_dir_name.$nb) )
	{
		$nb += 1;
	}
	if( $title == null)
	{
		$title = basename($desired_dir_name);
	}
	if ( mkdir($base_work_dir.$desired_dir_name.$nb))
	{
		$perm = api_get_setting('permissions_for_new_directories');
		$perm = octdec(!empty($perm)?$perm:'0770');
		chmod($base_work_dir.$desired_dir_name.$nb,$perm);
		$document_id = add_document($_course, $desired_dir_name.$nb,'folder',0,$title);
		if ($document_id)
		{
		//update document item_property
		$current_session_id = api_get_session_id();
		api_item_property_update($_course,TOOL_DOCUMENT,$document_id,'FolderCreated',$user_id,$to_group_id,$to_user_id,null,null,$current_session_id);
		return $desired_dir_name.$nb;
		}
	}
	else
	{
	return false;
	}
}

//------------------------------------------------------------------------------

/**
 * Handles uploaded missing images
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @author Bert Vanderkimpen
 * @param array $_course
 * @param array $uploaded_file_collection - follows the $_FILES Structure
 * @param string $base_work_dir
 * @param string $missing_files_dir
 * @param int $user_id
 * @param int $max_filled_space
 */

function move_uploaded_file_collection_into_directory($_course, $uploaded_file_collection, $base_work_dir, $missing_files_dir,$user_id,$to_group_id,$to_user_id,$max_filled_space)
{
	$number_of_uploaded_images = count($uploaded_file_collection['name']);
	for ($i=0; $i < $number_of_uploaded_images; $i++)
		{
		$missing_file['name'] = $uploaded_file_collection['name'][$i];
		$missing_file['type'] = $uploaded_file_collection['type'][$i];
		$missing_file['tmp_name'] = $uploaded_file_collection['tmp_name'][$i];
		$missing_file['error'] = $uploaded_file_collection['error'][$i];
		$missing_file['size'] = $uploaded_file_collection['size'][$i];

		$upload_ok = process_uploaded_file($missing_file);
			if($upload_ok)
			{
			$new_file_list[] = handle_uploaded_document($_course,$missing_file,$base_work_dir,$missing_files_dir,$user_id,$to_group_id,$to_user_id,$max_filled_space,0,'overwrite');
			}
		unset($missing_file);
		}
	return $new_file_list;
}

//------------------------------------------------------------------------------

//------------------------------------------------------------------------------

/*
 * Open the old html file and replace the src path into the img tag
 * This also works for files in subdirectories.
 * @param $originalImgPath is an array
 * @param $newImgPath is an array
 */
function replace_img_path_in_html_file($originalImgPath, $newImgPath, $htmlFile)
{
	global $_course;

	/*
	 * Open the file
	 */
	$fp = fopen($htmlFile, "r");
	$buffer = fread ($fp, filesize ($htmlFile));

	/*
	 * Fix the image tags
	 */
	for ($i = 0, $fileNb = count($originalImgPath); $i < $fileNb ; $i++)
	{
		$replace_what = $originalImgPath[$i];
		/*
		we only need the directory and the filename
		/path/to/file_html_files/missing_file.gif -> file_html_files/missing_file.gif
		*/
		$exploded_file_path = explode('/',$newImgPath[$i]);
		$replace_by = $exploded_file_path[count($exploded_file_path)-2].'/'.$exploded_file_path[count($exploded_file_path)-1];
		//$message .= "Element [$i] <b>" . $replace_what . "</b> replaced by <b>" . $replace_by . "</b><br>"; //debug
		//api_display_debug_info($message);

		$buffer = str_replace( $replace_what, $replace_by, $buffer);
	}

	$new_html_content .= $buffer;

	fclose ($fp) or die ('<center>cannot close file</center>');;

	/*
	 * Write the resulted new file
	 */
	$fp = fopen($htmlFile, 'w')      or die('<center>cannot open file</center>');
	fwrite($fp, $new_html_content)   or die('<center>cannot write in file</center>');
}

//------------------------------------------------------------------------------

/**
 * Creates a file containing an html redirection to a given url
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @param string $filePath
 * @param string $url
 * @return void
 */

function create_link_file($filePath, $url)
{
	$fileContent = '<html>'
				  .'<head>'
				  .'<meta http-equiv="refresh" content="1;url='.$url.'">'
				  .'</head>'
				  .'<body>'
				  .'</body>'
				  .'</html>';

	 $fp = fopen ($filePath, 'w') or die ('can not create file');
	 fwrite($fp, $fileContent);
}

//------------------------------------------------------------------------------

/**
	Open html file $full_file_name;
	Parse the hyperlinks; and
	Write the result back in the html file.

	@author Roan Embrechts
	@version 0.1
 */
function api_replace_links_in_html($upload_path, $full_file_name)
{
	//Open the file
	$fp = fopen($full_file_name, "r");
	$buffer = fread ($fp, filesize ($full_file_name));

	//Parse the contents
	$new_html_content = api_replace_links_in_string($upload_path, $buffer);

	//Write the result
	$fp = fopen($full_file_name, "w");
	fwrite($fp, $new_html_content);
}

//------------------------------------------------------------------------------

/**
	@deprecated, use api_replace_parameter instead

	Parse the buffer string provided as parameter
	Replace the a href tags so they are displayed correctly.
	- works for files in root and subdirectories
	- replace relative hyperlinks to use showinframes.php?file= ...
	- add target="_self" to all absolute hyperlinks
	- leave local anchors untouched (e.g. #CHAPTER1)
	- leave links with download.php and showinframes.php untouched

	@author Roan Embrechts
	@version 0.6
*/
function api_replace_links_in_string($upload_path, $buffer)
{
	// Search for hyperlinks
	$matches = array();
	if ( preg_match_all("/<a[\s]*href[^<]*>/i", $buffer, $matches) )
	{
		$tag_list = $matches[0];
	}

	// Search the filepath of all detected <a href> tags
	if (sizeof($tag_list)  > 0)
	{
		$file_path_list=array();
		$href_list=array();

		foreach($tag_list as $this_tag)
		{
			/* Match case insensitive, the stuff between the two ~ :
				a href = <exactly one quote><one or more non-quotes><exactly one ">
				e.g. a href="www.google.be", A HREF =   "info.html"
				to match ["] escape the " or else PHP interprets it
				[\"]{1} --> matches exactly one "
				+	1 or more (like * is 0 or more)
				[\s]* matches whitespace
				$matches contains captured subpatterns
				the only one here is ([^\"]+) --> matches[1]
			*/
			if ( preg_match("~a href[\s]*=[\s]*[\"]{1}([^\"]+)[\"]{1}~i",
				   		 $this_tag, $matches) )
			{
				$file_path_list[] = $matches[1];//older
				$href_list[] = $matches[0];//to also add target="_self"
			}
		}


	}

	// replace the original hyperlinks
	// by the correct ones
	for ($count = 0; $count < sizeof($href_list); $count++)
	{
		$replaceWhat[$count] = $href_list[$count];

		$is_absolute_hyperlink = strpos($replaceWhat[$count], "http");
		$is_local_anchor = strpos($replaceWhat[$count], "#");
		if ($is_absolute_hyperlink == false && $is_local_anchor == false )
		{
			//this is a relative hyperlink
			if (
				(strpos($replaceWhat[$count], "showinframes.php") == false) &&
				(strpos($replaceWhat[$count], "download.php") == false)
				)
			{
				//fix the link to use showinframes.php
				$replaceBy[$count] = "a href = \"showinframes.php?file=" . $upload_path."/".$file_path_list[$count]."\" target=\"_self\"";
			}
			else
			{
				//url already fixed, leave as is
				$replaceBy[$count] = $replaceWhat[$count];
			}
		}
		else if ($is_absolute_hyperlink)
		{
			$replaceBy[$count] = "a href=\"" . $file_path_list[$count] . "\" target =\"_self\"";
		}
		else
		{
			//don't change anything
			$replaceBy[$count] = $replaceWhat[$count];
		}
		//Display::display_normal_message("link replaced by " . $replaceBy[$count]); //debug
	}

	$buffer = str_replace($replaceWhat, $replaceBy, $buffer);
	return $buffer;
}

//------------------------------------------------------------------------------

/**
	EXPERIMENTAL - function seems to work, needs more testing

	@param $upload_path is the path where the document is stored, like "/archive/"
	if it is the root level, the function expects "/"
	otherwise "/path/"

	This function parses all tags with $param_name parameters.
	so the tags are displayed correctly.

	--------------
	Algorithm v1.0
	--------------
		given a string and a parameter,
		* OK find all tags in that string with the specified parameter (like href or src)
		* OK for every one of these tags, find the src|href|... part to edit it
		* OK change the src|href|... part to use download.php (or showinframes.php)
		* OK do some special stuff for hyperlinks

		Exceptions
		* OK if download.php or showinframes.php is already in the tag, leave it alone
		* OK if mailto is in the tag, leave it alone
		* OK if the src|href param contains http://, it's absolute --> leave it alone

		Special for hyperlinks (a href...)
		* OK add target="_self"
		* OK use showinframes.php instead of download.php

	@author Roan Embrechts
	@version 1.1
*/
function api_replace_parameter($upload_path, $buffer, $param_name="src")
{
	/*
	 *	Search for tags with $param_name as a parameter
	 */
	/*
	// [\s]*	matches whitespace
	// [\"=a-z] matches ", = and a-z
	// ([\s]*[a-z]*)*	matches all whitespace and normal alphabet
	//					characters a-z combinations but seems too slow
	//	perhaps ([\s]*[a-z]*) a maximum number of times ?
	// [\s]*[a-z]*[\s]*	matches many tags
	// the ending "i" means to match case insensitive (a matches a and A)
	*/
	$matches = array();
	if ( preg_match_all("/<[a-z]+[^<]*".$param_name."[^<]*>/i", $buffer, $matches) )
	{
		$tag_list = $matches[0];
	}

	/*
	 *	Search the filepath of parameter $param_name in all detected tags
	 */
	if (sizeof($tag_list) > 0)
	{
		$file_path_list=array();
		$href_list=array();

		foreach($tag_list as $this_tag)
		{
			//Display::display_normal_message(htmlentities($this_tag)); //debug
			if ( preg_match("~".$param_name."[\s]*=[\s]*[\"]{1}([^\"]+)[\"]{1}~i",
						$this_tag, $matches) )

			{
				$file_path_list[] = $matches[1];//older
				$href_list[] = $matches[0];//to also add target="_self"
			}
		}
	}

	/*
	 *	Replace the original tags by the correct ones
	 */
	for ($count = 0; $count < sizeof($href_list); $count++)
	{
		$replaceWhat[$count] = $href_list[$count];

		$is_absolute_hyperlink = strpos($replaceWhat[$count], 'http');
		$is_local_anchor = strpos($replaceWhat[$count], '#');
		if ($is_absolute_hyperlink == false && $is_local_anchor == false )
		{
			if (
				(strpos($replaceWhat[$count], 'showinframes.php') == false) &&
				(strpos($replaceWhat[$count], 'download.php') == false) &&
				(strpos($replaceWhat[$count], 'mailto') == false)
				)
			{
				//fix the link to use download.php or showinframes.php
				if ( preg_match("/<a([\s]*[\"\/:'=a-z0-9]*){5}href[^<]*>/i", $tag_list[$count]) )
				{
					$replaceBy[$count] = " $param_name =\"showinframes.php?file=" . $upload_path.$file_path_list[$count]."\" target=\"_self\" ";
				}
				else
				{
					$replaceBy[$count] = " $param_name =\"download.php?doc_url=" . $upload_path.$file_path_list[$count]."\" ";
				}
			}
			else
			{
				//"mailto" or url already fixed, leave as is
				//$message .= "Already fixed or contains mailto: ";
				$replaceBy[$count] = $replaceWhat[$count];
			}
		}
		else if ($is_absolute_hyperlink)
		{
			//$message .= "Absolute hyperlink, don't change, add target=_self: ";
			$replaceBy[$count] = " $param_name=\"" . $file_path_list[$count] . "\" target =\"_self\"";
		}
		else
		{
			//don't change anything
			//$message .= "Local anchor, don't change: ";
			$replaceBy[$count] = $replaceWhat[$count];
		}
		//$message .= "In tag $count, <b>" . htmlentities($tag_list[$count])
		//	. "</b>, parameter <b>" . $replaceWhat[$count] . "</b> replaced by <b>" . $replaceBy[$count] . "</b><br>"; //debug
	}
	//if (isset($message) && $message == true) api_display_debug_info($message); //debug
	$buffer = str_replace($replaceWhat, $replaceBy, $buffer);
	return $buffer;
}

//------------------------------------------------------------------------------

/**
 * Checks the extension of a file, if it's .htm or .html
 * we use search_img_from_html to get all image paths in the file
 *
 * @param string $file
 * @return array paths
 * @see check_for_missing_files() uses search_img_from_html()
 */
function check_for_missing_files($file)
{
	if (strrchr($file, '.') == '.htm' || strrchr($file, '.') == '.html')
	{
		$img_file_path = search_img_from_html($file);
		return $img_file_path;
	}
	return false;
}

//------------------------------------------------------------------------------

/**
 * This builds a form that asks for the missing images in a html file
 * maybe we should do this another way?
 *
 * @param array $missing_files
 * @param string $upload_path
 * @param string $file_name
 * @return string the form
 */
function build_missing_files_form($missing_files,$upload_path,$file_name)
{
		//do we need a / or not?
		$added_slash = ($upload_path=='/')?'':'/';
		//build the form
		$form .= "<p><strong>".get_lang('MissingImagesDetected')."</strong></p>\n"
				."<form method=\"post\" action=\"".api_get_self()."\" enctype=\"multipart/form-data\">\n"
				//related_file is the path to the file that has missing images
				."<input type=\"hidden\" name=\"related_file\" value=\"".$upload_path.$added_slash.$file_name."\" />\n"
				."<input type=\"hidden\" name=\"upload_path\" value=\"".$upload_path."\" />\n"
				."<table border=\"0\">\n";
				foreach($missing_files as $this_img_file_path )
				{
				$form .= "<tr>\n"
					   ."<td>".basename($this_img_file_path)." : </td>\n"
					   ."<td>"
					   ."<input type=\"file\" name=\"img_file[]\"/>"
					   ."<input type=\"hidden\" name=\"img_file_path[]\" value=\"".$this_img_file_path."\" />"
					   ."</td>\n"
					   ."</tr>\n";
				}
				$form .= "</table>\n"
						."<button type='submit' name=\"cancel_submit_image\" value=\"".get_lang('Cancel')."\" class=\"cancel\">".get_lang('Cancel')."</button>"
						."<button type='submit' name=\"submit_image\" value=\"".get_lang('Ok')."\" class=\"save\">".get_lang('Ok')."</button>"
						."</form>\n";
				return $form;
}

//------------------------------------------------------------------------------

/**
 * This recursive function can be used during the upgrade process form older versions of Dokeos
 * It crawls the given directory, checks if the file is in the DB and adds it if it's not
 *
 * @param string $base_work_dir
 * @param string $current_path, needed for recursivity
 */
function add_all_documents_in_folder_to_database($_course,$user_id,$base_work_dir,$current_path='',$to_group_id=0)
{
$current_session_id = api_get_session_id();
$path = $base_work_dir.$current_path;
//open dir
$handle=opendir($path);
	//run trough
	while($file=readdir($handle))
	{
	   if ($file=='.' || $file=='..') continue;

	   $completepath="$path/$file";
	   //directory?

	   if (is_dir($completepath))
	   {
	   	$title=get_document_title($file);
	   	$safe_file=replace_dangerous_char($file);
		@rename($path.'/'.$file, $path.'/'.$safe_file);
		//if we can't find the file, add it
		if(!DocumentManager::get_document_id($_course, $current_path.'/'.$safe_file))
		{
			$document_id=add_document($_course,$current_path.'/'.$safe_file,'folder',0,$title);
			api_item_property_update($_course,TOOL_DOCUMENT,$document_id,'DocumentAdded',$user_id, $to_group_id,null,null,null,$current_session_id);
			//echo $current_path.'/'.$safe_file." added!<br/>";

		}
		//recursive
		add_all_documents_in_folder_to_database($_course,$user_id,$base_work_dir,$current_path.'/'.$safe_file, $to_group_id);
	    }
	    //file!
	    else
		{
			//rename
			$safe_file=disable_dangerous_file(replace_dangerous_char($file));
			@rename($base_work_dir.$current_path.'/'.$file,$base_work_dir.$current_path.'/'.$safe_file);

			if(!DocumentManager::get_document_id($_course, $current_path.'/'.$safe_file))
			{
			$title=get_document_title($file);
			$size = filesize($base_work_dir.$current_path.'/'.$safe_file);
			$document_id = add_document($_course,$current_path.'/'.$safe_file,'file',$size,$title);
			api_item_property_update($_course,TOOL_DOCUMENT,$document_id,'DocumentAdded',$user_id,$to_group_id,null,null,null,$current_session_id);
			//echo $current_path.'/'.$safe_file." added!<br/>";
			}
	    }
	}
}

// could be usefull in some cases...
function remove_accents($string){
	$string = strtr ( $string, "�����������������������������������������������������", "AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn");
	return $string;
}
?>
