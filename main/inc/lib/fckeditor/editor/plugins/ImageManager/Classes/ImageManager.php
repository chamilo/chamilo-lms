<?php
/**
 * ImageManager, list images, directories, and thumbnails.
 * @author Wei Zhuo
 * @version $Id: ImageManager.php,v 1.4 2006/12/21 21:28:00 thierrybo Exp $
 * @package ImageManager
 */

require_once '../../../../../global.inc.php';
require_once '../../../../fileUpload.lib.php';

require_once('Files.php');
require_once('Transform.php');

/**
 * ImageManager Class.
 * @author Wei Zhuo
 * @version $Id: ImageManager.php,v 1.4 2006/12/21 21:28:00 thierrybo Exp $
 */
class ImageManager
{
	/**
	 * Configuration array.
	 */
	var $config;

	/**
	 * Array of directory information.
	 */
	var $dirs;

	/**
	 * Constructor. Create a new Image Manager instance.
	 * @param array $config configuration array, see config.inc.php
	 */
	function ImageManager($config)
	{
		$this->config = $config;
	}

	/**
	 * Get the base directory.
	 * @return string base dir, see config.inc.php
	 */
	function getBaseDir()
	{
		Return $this->config['base_dir'];
	}

	/**
	 * Get the base URL.
	 * @return string base url, see config.inc.php
	 */
	function getBaseURL()
	{
		Return $this->config['base_url'];
	}

	function isValidBase()
	{
		return is_dir($this->getBaseDir());
	}

	/**
	 * Get the tmp file prefix.
     * @return string tmp file prefix.
	 */
	function getTmpPrefix()
	{
		Return $this->config['tmp_prefix'];
	}

	/**
	 * Get the sub directories in the base dir.
	 * Each array element contain
	 * the relative path (relative to the base dir) as key and the
	 * full path as value.
	 * @return array of sub directries
	 * <code>array('path name' => 'full directory path', ...)</code>
	 */
	function getDirs()
	{
		if(is_null($this->dirs))
		{
			$dirs = $this->_dirs($this->getBaseDir(),'/');
			ksort($dirs);

			$this->dirs = $dirs;
		}
		return $this->dirs;
	}

	/**
	 * Recursively travese the directories to get a list
	 * of accessable directories.
	 * @param string $base the full path to the current directory
	 * @param string $path the relative path name
	 * @return array of accessiable sub-directories
	 * <code>array('path name' => 'full directory path', ...)</code>
	 */
	function _dirs($base, $path)
	{
		$base = Files::fixPath($base);
		$dirs = array();

		if (!$this->isValidBase())
			return $dirs;

		$d = @dir($base);

		$in_group = api_is_in_group();
		if ($in_group)
		{
			$group_properties = GroupManager::get_group_properties($_SESSION['_gid']);
			$group_directory = explode('/', $group_properties['directory']);
			$group_directory = $group_directory[count($group_directory) - 1];
		}

		$user_id = api_get_user_id();
		while (false !== ($entry = $d->read()))
		{
			//If it is a directory, and it doesn't start with
			// a dot, and if is it not the thumbnail directory
			if(is_dir($base.$entry)
				&& substr($entry,0,1) != '.'
				&& strpos($entry, '_DELETED_') === false
				&& strpos($entry, 'chat_files') === false
				&& strpos($entry, 'css') === false
				&& strpos($entry, 'HotPotatoes_files') === false
				&& ($in_group || (!$in_group && strpos($entry, '_groupdocs') === false))
				&& !$this->isThumbDir($entry))
			{
				$relative = Files::fixPath($path.$entry);
				$fullpath = Files::fixPath($base.$entry);

				if ($in_group && strpos($fullpath, '_groupdocs') !== false && strpos($fullpath, $group_directory) === false)
				{
					continue;
				}
				global $_course;
				if (isset($_course['dbName']) && $_course<>'-1') {
					$base_dir = substr($fullpath, 0, strpos($fullpath,'/document/')+9); //
					$new_dir  = substr($fullpath, strlen($base_dir),-1); //
					$doc_id = DocumentManager::get_document_id($_course, $new_dir );
					$visible_status= api_get_item_visibility($_course,TOOL_DOCUMENT,$doc_id);
				}

				//Teachers can access to hidden files and directories as they can in the tool documents
			    /*
				if ($visible_status=='0' || $visible_status=='-1') {
					continue;
				}
				*/

				/* if (strpos($fullpath, '/shared_folder/') !== false) {
					if (!preg_match('/.*\/shared_folder\/$/', $fullpath)) {
						//all students can see the shared_folder
						if (strpos($fullpath, '/shared_folder/sf_user_'.$user_id.'/') !== false) {
							continue;
						}
					}
				}
				*/
				$dirs[$relative] = $fullpath;
				$dirs = array_merge($dirs, $this->_dirs($fullpath, $relative));
			}
		}
		$d->close();

		Return $dirs;
	}

	/**
	 * Get all the files and directories of a relative path.
	 * @param string $path relative path to be base path.
	 * @return array of file and path information.
	 * <code>array(0=>array('relative'=>'fullpath',...), 1=>array('filename'=>fileinfo array(),...)</code>
	 * fileinfo array: <code>array('url'=>'full url',
	 *                       'relative'=>'relative to base',
	 *                        'fullpath'=>'full file path',
	 *                        'image'=>imageInfo array() false if not image,
	 *                        'stat' => filestat)</code>
	 */
	function getFiles($path)
	{
		$files = array();
		$dirs = array();

		if (!$this->isValidBase())
			return array($files,$dirs);

		$path = Files::fixPath($path);
		$base = Files::fixPath($this->getBaseDir());
		$fullpath = Files::makePath($base,$path);


		$d = @dir($fullpath);

		if(empty($d)) {
			$path = Files::fixPath('/');
			$base = Files::fixPath($this->getBaseDir());
			$fullpath = Files::makePath($base,$path);
			$d = @dir($fullpath);
		}

		$in_group = api_is_in_group();
		$user_id = api_get_user_id();

		// check templates files in bd
		$tbl_system_template = Database :: get_main_table(TABLE_MAIN_SYSTEM_TEMPLATE);

		$sql = "SELECT image FROM $tbl_system_template ";
		$res = Database::query($sql);

		$files_templates = array();

		while ($row = Database::fetch_row($res)) {
			$files_templates[] = $row[0];
		}

		while (false !== ($entry = $d->read()))
		{

			if (in_array($entry,$files_templates)) continue;

			if (substr($entry,0,1) != '.'          //not a dot file or directory
				&& strpos($entry, '_DELETED_') === false
				&& strpos($entry, 'chat_files') === false
				&& strpos($entry, 'css') === false
				&& strpos($entry, 'HotPotatoes_files') === false
				&& ($in_group || (!$in_group && strpos($entry, '_groupdocs') === false)))
			{
				$is_dir = is_dir($fullpath.$entry);

				if ($is_dir) {
					$dir_entry = Files::fixPath($fullpath.$entry);
					/*
					if (strpos($dir_entry, '/shared_folder/') !== false)
					{
						if (!preg_match('/.*\/shared_folder\/$/', $dir_entry))
						{
							//all students can see the shared_folder
							if (strpos($dir_entry, '/shared_folder/sf_user_'.$user_id.'/') === true)
							{
								continue;
							}
						}
					}
					*/
				}

				if ($is_dir && !$this->isThumbDir($entry)) {
				    global $_course;
					if (isset($_course['dbName']) && $_course<>'-1') {
						//checking visibility
						$base_dir = substr($dir_entry, 0, strpos($dir_entry,'/document/')+9);
						$new_dir  = substr($dir_entry, strlen($base_dir),-1); //
						$doc_id = DocumentManager::get_document_id($_course, $new_dir );
						$visible_status= api_get_item_visibility($_course,TOOL_DOCUMENT,$doc_id);
					}

					//Teachers can access to hidden files and directories as they can in the tool documents
					/*
					if ($visible_status=='0' || $visible_status=='-1') {
						continue;
					}
					*/
					$relative = Files::fixPath($path.$entry);
					$full = Files::fixPath($fullpath.$entry);
					$count = $this->countFiles($full);
					$dirs[$relative] = array('fullpath'=>$full,'entry'=>$entry,'count'=>$count);
				}
				else if(is_file($fullpath.$entry) && !$this->isThumb($entry) && !$this->isTmpFile($entry))
				{
					$img = $this->getImageInfo($fullpath.$entry);

					if(!(!is_array($img)&&$this->config['validate_images']))
					{
					    global $_course;
					    if (isset($_course['dbName']) && $_course<>'-1') {
							//checking visibility
							$base_dir = substr($fullpath.$entry, 0, strpos($fullpath.$entry,'/document/')+9);
							$new_dir  = substr($fullpath.$entry, strlen($base_dir));
							$doc_id = DocumentManager::get_document_id($_course, $new_dir );
							$visible_status= api_get_item_visibility($_course,TOOL_DOCUMENT,$doc_id);
						}

						//Teachers can access to hidden files and directories as they can in the tool documents
					    /*
						if ($visible_status=='0' || $visible_status=='-1') {
							continue;
						}
						*/

						$file['url'] = Files::makePath($this->config['base_url'],$path).$entry;
						$file['relative'] = $path.$entry;
						$file['fullpath'] = $fullpath.$entry;
						$file['image'] = $img;
						$file['stat'] = stat($fullpath.$entry);
						$files[$entry] = $file;
					}
				}
			}
		}
		$d->close();
		ksort($dirs);
		ksort($files);

		Return array($dirs, $files);
	}

	/**
	 * Count the number of files and directories in a given folder
	 * minus the thumbnail folders and thumbnails.
	 */
	function countFiles($path)
	{
		$total = 0;

		if(is_dir($path))
		{
			$d = @dir($path);

			while (false !== ($entry = $d->read()))
			{
				//echo $entry."<br />";
				if (substr($entry, 0, 1) != '.'
					&& !$this->isThumbDir($entry)
					&& !$this->isTmpFile($entry)
					&& !$this->isThumb($entry))
				{
					$total++;
				}
			}
			$d->close();
		}
		return $total;
	}

	/**
	 * Get image size information.
	 * @param string $file the image file
	 * @return array of getImageSize information,
	 *  false if the file is not an image.
	 */
	function getImageInfo($file)
	{
		Return @getImageSize($file);
	}

	/**
	 * Check if the file contains the thumbnail prefix.
	 * @param string $file filename to be checked
	 * @return true if the file contains the thumbnail prefix, false otherwise.
	 */
	function isThumb($file)
	{
		$len = strlen($this->config['thumbnail_prefix']);
		if(substr($file,0,$len)==$this->config['thumbnail_prefix'])
			Return true;
		else
			Return false;
	}

	/**
	 * Check if the given directory is a thumbnail directory.
	 * @param string $entry directory name
	 * @return true if it is a thumbnail directory, false otherwise
	 */
	function isThumbDir($entry)
	{
		if (!$this->config['thumbnail_dir']
			|| strlen(trim($this->config['thumbnail_dir'])) == 0)
			Return false;
		else
			Return ($entry == $this->config['thumbnail_dir']);
	}

	/**
	 * Check if the given file is a tmp file.
	 * @param string $file file name
	 * @return boolean true if it is a tmp file, false otherwise
	 */
	function isTmpFile($file)
	{
		$len = strlen($this->config['tmp_prefix']);
		if(substr($file,0,$len)==$this->config['tmp_prefix'])
			Return true;
		else
			Return false;
	}

	/**
	 * For a given image file, get the respective thumbnail filename
	 * no file existence check is done.
	 * @param string $fullpathfile the full path to the image file
	 * @return string of the thumbnail file
	 */
	function getThumbName($fullpathfile)
	{
		$path_parts = pathinfo($fullpathfile);

		$thumbnail = $this->config['thumbnail_prefix'].$path_parts['basename'];

		if ($this->config['safe_mode']
			|| strlen(trim($this->config['thumbnail_dir'])) == 0)
		{
			Return Files::makeFile($path_parts['dirname'],$thumbnail);
		}
		else
		{
			if(strlen(trim($this->config['thumbnail_dir'])) > 0)
			{
				$path = Files::makePath($path_parts['dirname'],$this->config['thumbnail_dir']);
				if(!is_dir($path))
					Files::createFolder($path);
				Return Files::makeFile($path,$thumbnail);
			}
			else //should this ever happen?
			{
				//error_log('ImageManager: Error in creating thumbnail name');
			}
		}
	}

	/**
	 * Similar to getThumbName, but returns the URL, base on the
	 * given base_url in config.inc.php
	 * @param string $relative the relative image file name,
	 * relative to the base_dir path
	 * @return string the url of the thumbnail
	 */
	function getThumbURL($relative)
	{
		$path_parts = pathinfo($relative);
		$thumbnail = $this->config['thumbnail_prefix'].$path_parts['basename'];
		if($path_parts['dirname']=='\\') $path_parts['dirname']='/';

		if ($this->config['safe_mode']
			|| strlen(trim($this->config['thumbnail_dir'])) == 0)
		{
			$path = Files::fixPath($path_parts['dirname']);
			$url_path = Files::makePath($this->getBaseURL(), $path);
			Return Files::makeFile($url_path,$thumbnail);
		}
		else
		{
			if(strlen(trim($this->config['thumbnail_dir'])) > 0)
			{
				$path = Files::makePath($path_parts['dirname'],$this->config['thumbnail_dir']);
				$url_path = Files::makePath($this->getBaseURL(), $path);
				Return Files::makeFile($url_path,$thumbnail);
			}
			else //should this ever happen?
			{
				//error_log('ImageManager: Error in creating thumbnail url');
			}

		}
	}

	/**
	 * Check if the given path is part of the subdirectories
	 * under the base_dir.
	 * @param string $path the relative path to be checked
	 * @return boolean true if the path exists, false otherwise
	 */
	function validRelativePath($path)
	{
		$dirs = $this->getDirs();
		if($path == '/' || $path == '')
			Return true;
		//check the path given in the url against the
		//list of paths in the system.
		for($i = 0; $i < count($dirs); $i++)
		{
			$key = key($dirs);
			//we found the path
			if($key == $path)
				Return true;

			next($dirs);
		}
		Return false;
	}

	/**
	 * Process uploaded files, assumes the file is in
	 * $_FILES['upload'] and $_POST['dir'] is set.
	 * The dir must be relative to the base_dir and exists.
	 * If 'validate_images' is set to true, only file with
	 * image dimensions will be accepted.
	 * @return null
	 */
	function processUploads()
	{
		if (!$this->isValidBase())
			return;

		$relative = null;

		if(isset($_POST['dir']))
			$relative = rawurldecode($_POST['dir']);
		else
			return;

		//check for the file, and must have valid relative path
		if(isset($_FILES['upload']) && $this->validRelativePath($relative))
		{
			return $this->_processFiles($relative, $_FILES['upload']);
		}
	}

	/**
	 * Process upload files. The file must be an
	 * uploaded file. If 'validate_images' is set to
	 * true, only images will be processed. Any duplicate
	 * file will be renamed. See Files::copyFile for details
	 * on renaming.
	 * @param string $relative the relative path where the file
	 * should be copied to.
	 * @param array $file the uploaded file from $_FILES
	 * @return boolean true if the file was processed successfully,
	 * false otherwise
	 */
	function _processFiles($relative, $file)
	{
		global $_course;
		if($file['error']!=0)
		{
			return false;
		}

		if(!is_file($file['tmp_name']))
		{
			return false;
		}

		if(!is_uploaded_file($file['tmp_name']))
		{
			Files::delFile($file['tmp_name']);
			return false;
		}

		$file['name'] = replace_dangerous_char($file['name'], 'strict');
		$file_name = $file['name'];
		$extension = explode('.', $file_name);
		$count = count($extension);
		if ($count == 1)
		{
			$extension = '';
		}
		else
		{
			$extension = strtolower($extension[$count - 1]);
		}

		// Checking for image by file extension first, using the configuration file.
		if (!in_array($extension, $this->config['accepted_extensions']))
		{
			Files::delFile($file['tmp_name']);
			return false;
		}

		// Second, filtering using a special function of the system.
		$result = filter_extension($file_name);
		if (($result == 0) || ($file_name != $file['name']))
		{
			Files::delFile($file['tmp_name']);
			return false;
		}

		// Checking for a valid image by reading binary file (partially in most cases).
		if ($this->config['validate_images'])
		{
			$imgInfo = @getImageSize($file['tmp_name']);
			if(!is_array($imgInfo))
			{
				Files::delFile($file['tmp_name']);
				return false;
			}
		}

		//now copy the file
		$path = Files::makePath($this->getBaseDir(),$relative);
		$result = Files::copyFile($file['tmp_name'], $path, $file['name']);

		//no copy error
		if (!is_int($result)) {

	   	    if (isset($_course['dbName']) && $_course<>'-1') {
				//adding the document to the DB
				global $to_group_id;

				// looking for the /document/ folder
				$document_path = substr($path, strpos($path,'/document/')+9, strlen($path)); //   /shared_folder/4/name
				$document_path.= $result;

				$chamiloFile = $file['name'];
				$chamiloFileSize = $file['size'];
				if(!empty($group_properties['directory'])) {
					$chamiloFolder=$group_properties['directory'].$chamiloFolder;
				}

				$doc_id = add_document($_course, $document_path,'file', $chamiloFileSize , $chamiloFile);
				$current_session_id = api_get_session_id();
				api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', api_get_user_id(),$to_group_id,null,null,null,$current_session_id);//get Dokeos
			}

			/*
			if (!(api_is_platform_admin() || api_is_course_admin())) {
				//setting invisible by default for the students
				api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'invisible', api_get_user_id());
			}
			*/
		   $dimensionsIndex = isset($_REQUEST['uploadSize']) ? $_REQUEST['uploadSize'] : 0;
		   // If maximum size is specified, constrain image to it.
           if ($this->config['maxWidth'][$dimensionsIndex] > 0 && $this->config['maxHeight'][$dimensionsIndex] > 0)
           {
			   $img = Image_Transform::factory(IMAGE_CLASS);
			   $img->load($path . $result);

			   // image larger than max dimensions?
			   if ($img->img_x > $this->config['maxWidth'][$dimensionsIndex] || $img->img_y > $this->config['maxHeight'][$dimensionsIndex])
			   {
				   $percentage = min($this->config['maxWidth'][$dimensionsIndex] / $img->img_x, $this->config['maxHeight'][$dimensionsIndex] / $img->img_y);
				   $img->scale($percentage);
			   }

			   $img->save($path . $result);
			   $img->free();
           }
	   }

		//delete tmp files.
		Files::delFile($file['tmp_name']);
		return false;
	}

	/**
	 * Get the URL of the relative file.
	 * basically appends the relative file to the
	 * base_url given in config.inc.php
	 * @param string $relative a file the relative to the base_dir
	 * @return string the URL of the relative file.
	 */
	function getFileURL($relative)
	{
		Return Files::makeFile($this->getBaseURL(),$relative);
	}

	/**
	 * Get the fullpath to a relative file.
	 * @param string $relative the relative file.
	 * @return string the full path, .ie. the base_dir + relative.
	 */
	function getFullPath($relative)
	{
		Return Files::makeFile($this->getBaseDir(),$relative);;
	}

	/**
	 * Get the default thumbnail.
	 * @return string default thumbnail, empty string if
	 * the thumbnail doesn't exist.
	 */
	function getDefaultThumb()
	{
		if(is_file($this->config['default_thumbnail']))
			Return $this->config['default_thumbnail'];
		else
			Return '';
	}


	/**
	 * Get the thumbnail url to be displayed.
	 * If the thumbnail exists, and it is up-to-date
	 * the thumbnail url will be returns. If the
	 * file is not an image, a default image will be returned.
	 * If it is an image file, and no thumbnail exists or
	 * the thumbnail is out-of-date (i.e. the thumbnail
	 * modified time is less than the original file)
	 * then a thumbs.php?img=filename.jpg is returned.
	 * The thumbs.php url will generate a new thumbnail
	 * on the fly. If the image is less than the dimensions
	 * of the thumbnails, the image will be display instead.
	 * @param string $relative the relative image file.
	 * @return string the url of the thumbnail, be it
	 * actually thumbnail or a script to generate the
	 * thumbnail on the fly.
	 */
	function getThumbnail($relative)
	{
		$fullpath = Files::makeFile($this->getBaseDir(),$relative);

		//not a file???
		if(!is_file($fullpath))
			Return $this->getDefaultThumb();

		$imgInfo = @getImageSize($fullpath);

		//not an image
		if(!is_array($imgInfo))
			Return $this->getDefaultThumb();

		//the original image is smaller than thumbnails,
		//so just return the url to the original image.
		if ($imgInfo[0] <= $this->config['thumbnail_width']
		 && $imgInfo[1] <= $this->config['thumbnail_height'])
			Return $this->getFileURL($relative);

		$thumbnail = $this->getThumbName($fullpath);

		//check for thumbnails, if exists and
		// it is up-to-date, return the thumbnail url
		if(is_file($thumbnail))
		{
			if(filemtime($thumbnail) >= filemtime($fullpath))
				Return $this->getThumbURL($relative);
		}

		//well, no thumbnail was found, so ask the thumbs.php
		//to generate the thumbnail on the fly.
		Return 'thumbs.php?img='.rawurlencode($relative);
	}

	/**
	 * Delete and specified files.
	 * @return boolean true if delete, false otherwise
	 */
	function deleteFiles()
	{
		if(isset($_GET['delf']))
			$this->_delFile(rawurldecode($_GET['delf']));
	}

	/**
	 * Delete and specified directories.
	 * @return boolean true if delete, false otherwise
	 */
	function deleteDirs()
	{
		 if(isset($_GET['deld']))
			return $this->_delDir(rawurldecode($_GET['deld']));
		 else
			 Return false;
	}

	/**
	 * Delete the relative file, and any thumbnails.
	 * @param string $relative the relative file.
	 * @return boolean true if deleted, false otherwise.
	 */
	function _delFile($relative)
	{
		$fullpath = Files::makeFile($this->getBaseDir(),$relative);
		//check that the file is an image
		if ($this->config['validate_images'])
		{
			if(!is_array($this->getImageInfo($fullpath)))
				return false; //hmmm not an Image!!???
		}

		$thumbnail = $this->getThumbName($fullpath);

		if(Files::delFile($fullpath)){
			//deleting from the DB
			global $_course;
			if (isset($_course['dbName']) && $_course<>'-1') {
				$document_path = substr($fullpath, strpos($fullpath,'/document/')+9, strlen($fullpath)); //   /shared_folder/4/name
				DocumentManager::delete_document($_course,$document_path,$fullpath);
			}
			Return Files::delFile($thumbnail);
		}
		else
			Return false;
	}

	/**
	 * Delete directories recursively.
	 * @param string $relative the relative path to be deleted.
	 * @return boolean true if deleted, false otherwise.
	 */
	function _delDir($relative)
	{
		$fullpath = Files::makePath($this->getBaseDir(),$relative);
		//we can delete recursively	even if there are images in the dir

		//if($this->countFiles($fullpath) <= 0) {
		// now we use the default delete_document function
		//return Files::delFolder($fullpath,true); //delete recursively.
		global $_course;
		if (isset($_course['dbName']) && $_course<>'-1') {
			$path_dir = substr($fullpath, strpos($fullpath,'/document/')+9,-1); //
			$base_dir  = substr($fullpath, 0, strlen($fullpath) - strlen($path_dir)); //
			return DocumentManager::delete_document($_course,$path_dir,$base_dir);
		}
		else
		{
		    if($this->countFiles($fullpath) <= 0) {
				return Files::delFolder($fullpath,true);
		    }
			else
			{
				Return false;
			}

		}
		/*
		}
		else
			Return false;
		*/
	}

	/**
	 * Create new directories.
	 * If in safe_mode, nothing happens.
	 * @return boolean true if created, false otherwise.
	 */
	function processNewDir()
	{
		if ($this->config['safe_mode'])
			Return false;

		if(isset($_GET['newDir']) && isset($_GET['dir']))
		{
			$newDir = rawurldecode($_GET['newDir']);
			$dir = rawurldecode($_GET['dir']);
			$path = Files::makePath($this->getBaseDir(),$dir);
			$fullpath = Files::makePath($path, Files::escape($newDir));
			if(is_dir($fullpath)) {
				Return false;
			} else {
				//adding to the DB
				// now the create_unexisting_directory will create the folder
				//$result = Files::createFolder($fullpath);

					global $_course;
					if (isset($_course['dbName']) && $_course<>'-1') {
					//@todo make this str to functions
					$base_dir = substr($path, 0, strpos($path,'/document/')+9); //
					$new_dir  = substr($fullpath, strlen($base_dir),-1); //
					$created_dir = create_unexisting_directory($_course, api_get_user_id(), api_get_session_id(), 0,0, $base_dir, $new_dir,$newDir);
					$doc_id = DocumentManager::get_document_id($_course, $new_dir );
					$current_session_id = api_get_session_id();
					api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'invisible', api_get_user_id(),null,null,null,null,$current_session_id);
				}
				else
				{
				 	Return Files::createFolder($fullpath);
				}
				return true;
			}
		}
	}

	/**
	 * Do some graphic library method checkings
	 * @param string $library the graphics library, GD, NetPBM, or IM.
	 * @param string $method the method to check.
	 * @return boolean true if able, false otherwise.
	 */
	function validGraphicMethods($library,$method)
	{
		switch ($library)
		{
			case 'GD':
				return $this->_checkGDLibrary($method);
				break;
			case 'NetPBM':
				return $this->_checkNetPBMLibrary($method);
				break;
			case 'IM':
				return $this->_checkIMLibrary($method);
		}
		return false;
	}

	function _checkIMLibrary($method)
	{
		//ImageMagick goes throught 1 single executable
		if(is_file(Files::fixPath(IMAGE_TRANSFORM_LIB_PATH).'convert'))
			return true;
		else
			return false;
	}

	/**
	 * Check the GD library functionality.
	 * @param string $library the graphics library, GD, NetPBM, or IM.
	 * @return boolean true if able, false otherwise.
	 */
	function _checkGDLibrary($method)
	{
		$errors = array();
		switch($method)
		{
			case 'create':
				$errors['createjpeg'] = function_exists('imagecreatefromjpeg');
				$errors['creategif'] = function_exists('imagecreatefromgif');
				$errors['createpng'] = function_exists('imagecreatefrompng');
				break;
			case 'modify':
				$errors['create'] = function_exists('ImageCreateTrueColor') || function_exists('ImageCreate');
				$errors['copy'] = function_exists('ImageCopyResampled') || function_exists('ImageCopyResized');
				break;
			case 'save':
				$errors['savejpeg'] = function_exists('imagejpeg');
				$errors['savegif'] = function_exists('imagegif');
				$errors['savepng'] = function_exists('imagepng');
				break;
		}

		return $errors;
	}
}

?>