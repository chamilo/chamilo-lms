<?php
/**
	 * file listing
	 * @author Logan Cai (cailongqun [at] yahoo [dot] com [dot] cn)
	 * @link www.phpletter.com
	 * @since 22/April/2007
	 * 
	 * Modify for Chamilo
	 * @author Juan Carlos Raña
	 * @since 31/December/2008
	 */
	 
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "class.file.php");
class manager
{
	var $currentFolderPath;
	var $sessionAction = null; //object to session action
	var $flags = array('no'=>'noFlag', 'cut'=>'cutFlag', 'copy'=>'copyFlag');
	var $forceFolderOnTop = false; //forced to have folder shown on the top of the list
	var $currentFolderInfo = array(
	'name'=>'',
	'subdir'=>0,
	'file'=>0,
	'ctime'=>'',
	'mtime'=>'',
	'is_readable'=>'',
	'is_writable'=>'',
	'size'=>0,
	'path'=>'',
	'type'=>'folder',
	'flag'=>'noFlag',
	'friendly_path'=>'',
	);
	
	var $lastVisitedFolderPathIndex = 'ajax_last_visited_folder';
	var $folderPathIndex = "path";
	var $calculateSubdir = true;
	var $fileTypes = array(
			array(array("exe", "com"), "fileExe", SEARCH_TYPE_EXE, 0),
			array(array("gif", "jpg", "png", "bmp", "tif"), "filePicture", SEARCH_TYPE_IMG, 1),
			array(array("zip", "sit", "rar", "gz", "tar"), "fileZip", SEARCH_TYPE_ARCHIVE, 0),
			array(array("htm", "html", "php", "jsp", "asp", 'js', 'css'), "fileCode", SEARCH_TYPE_HTML, 1),
			array(array("mov", "ram", "rm", "asx", "dcr", "wmv"), "fileVideo", SEARCH_TYPE_VIDEO, 1),
			array(array("mpg", "avi", "asf", "mpeg", "flv", "mp4"), "fileVideo", SEARCH_TYPE_MOVIE, 1),
			array(array("aif", "aiff", "wav", "mp3", "wma","mid", "midi"), "fileMusic", SEARCH_TYPE_MUSIC, 1),
			array(array("swf", ), "fileFlash", SEARCH_TYPE_FLASH, 1),
			array(array("ppt", "pps", "pptx"), "filePPT", SEARCH_TYPE_PPT, 0),
			array(array("rtf"), "fileRTF", SEARCH_TYPE_DOC, 0),
			array(array("doc","docx"), "fileWord", SEARCH_TYPE_WORD, 0),
			array(array("pdf"), "fileAcrobat", SEARCH_TYPE_PDF, 0),
			array(array("xls", "xlsx", "csv"), "fileExcel", SEARCH_TYPE_EXCEL, 0),
			array(array("txt"), "fileText", SEARCH_TYPE_TEXT, 1),
			array(array("xml", "xsl", "dtd"), "fileXml", SEARCH_TYPE_XML, 1),
			array(array("svg","svgz"), "fileSVG", SEARCH_TYPE_SVG, 1),			
			array(array("ogg", "ogx", "ogv", "oga"), "fileOGG", SEARCH_TYPE_OGG, 1),			
			array(array("odt", "ott", "sxw", "stw"), "fileODT", SEARCH_TYPE_ODT, 1),			
			array(array("odp", "otp", "sxi", "sti"), "fileODP", SEARCH_TYPE_ODP, 1),
			array(array("ods", "ots", "sxc", "stc"), "fileODS", SEARCH_TYPE_ODS, 1),
			array(array("odg", "otg", "sxd", "std"), "fileODG", SEARCH_TYPE_ODG, 1)

	);// Added svg, svgz, pps, docx, pptx, xlsx, midi, ogg, ogv, oga,(odt,ods,odp,odg and extra) types to Chamilo, and change flv from flash to video
	
	/**
		 * constructor
		 * @path the path to a folder
		 * @calculateSubdir force to get the subdirectories information
		 */		
	function __construct($path = null, $calculateSubdir=true)
	{

		$this->calculateSubdir = $calculateSubdir;
		if(defined('CONFIG_SYS_FOLDER_SHOWN_ON_TOP'))
		{
			$this->forceFolderOnTop = CONFIG_SYS_FOLDER_SHOWN_ON_TOP;
		}
		if(!is_null($path))
		{
			$this->currentFolderPath = $path;

		}elseif(isset($_GET[$this->folderPathIndex]) && file_exists($_GET[$this->folderPathIndex]) && !is_file($_GET[$this->folderPathIndex]) )
		{
			$this->currentFolderPath = api_htmlentities(Security::remove_XSS($_GET[$this->folderPathIndex]));
		}
		elseif(isset($_SESSION[$this->lastVisitedFolderPathIndex]) && file_exists($_SESSION[$this->lastVisitedFolderPathIndex]) && !is_file($_SESSION[$this->lastVisitedFolderPathIndex]))
		{
			$this->currentFolderPath = $_SESSION[$this->lastVisitedFolderPathIndex];
		}else
		{
			$this->currentFolderPath = CONFIG_SYS_DEFAULT_PATH;
		}
		
		$this->currentFolderPath = (isUnderRoot($this->currentFolderPath)?backslashToSlash((addTrailingSlash($this->currentFolderPath))):CONFIG_SYS_DEFAULT_PATH);
		
		if($this->calculateSubdir)
		{// keep track of this folder path in session 
			$_SESSION[$this->lastVisitedFolderPathIndex] = $this->currentFolderPath;
		}
		if(is_dir($this->currentFolderPath))
		{
						  
			$file = new file($this->currentFolderPath);
			$folderInfo = $file->getFileInfo();
			if(sizeof($folderInfo))
			{
				//for Chamilo in a name folder, replace num user by user names
				if(preg_match('/sf_user_/',basename($this->currentFolderPath)))
				{
					$userinfo=Database::get_user_info_from_id(substr(basename($this->currentFolderPath), 8));
					$this->currentFolderInfo['name']=api_get_person_name($userinfo['firstname'], $userinfo['lastname']);
				}
				else
				{
					$this->currentFolderInfo['name']=str_replace('_',' ',basename($this->currentFolderPath));//for Chamilo. Prevent long directory name
				}				
				if(preg_match('/shared_folder/', basename($this->currentFolderPath)))
				{
					$this->currentFolderInfo['name']=get_lang('UserFolders');
				}
				if(preg_match('/shared_folder_session_/',basename($this->currentFolderPath)))
				{
					$session = explode('_', basename($this->currentFolderPath));
					$session = strtolower($session[sizeof($session) - 1]);
					$this->currentFolderInfo['name']=get_lang('UserFolders').' ('.api_get_session_name($session).')*';
				}
			
				//end Chamilo
				$this->currentFolderInfo['subdir']=0;
				$this->currentFolderInfo['file']=0;
				$this->currentFolderInfo['ctime']=$folderInfo['ctime'];
				$this->currentFolderInfo['mtime']=$folderInfo['mtime'];
				$this->currentFolderInfo['is_readable']=$folderInfo['is_readable'];
				$this->currentFolderInfo['is_writable']=$folderInfo['is_writable'];	
				$this->currentFolderInfo['path']  = $this->currentFolderPath;
				$this->currentFolderInfo['friendly_path'] = transformFilePath($this->currentFolderPath);
				$this->currentFolderInfo['type'] = "folder";
				$this->currentFolderInfo['cssClass']='folder';
				
				//$this->currentFolderInfo['flag'] = $folderInfo['flag'];
			}			
		}
		if($calculateSubdir && !file_exists($this->currentFolderPath))
		{
			die(ERR_FOLDER_NOT_FOUND . $this->currentFolderPath);
		}			
	}
	
	function setSessionAction(&$session)
	{
		$this->sessionAction = $session;	
	}
	/**
		 * constructor
		 */
	function manager($path = null, $calculateSubdir=true)
	{
		$this->__construct($path, $calculateSubdir);
	}
	/**
		 * get current folder path
		 * @return  string
		 */
	function getCurrentFolderPath()
	{
		return $this->currentFolderPath;
	}
	/**
		 * get the list of files and folders under this current fold
		 *	@return array
		 */
	function getFileList()
	{
		$outputs = array();
		$files = array();
		$folders = array();
		$tem = array();
		$dirHandler = @opendir($this->currentFolderPath);
		if($dirHandler)
		{
			while(false !== ($file = readdir($dirHandler)))
			{
				if($file != '.' && $file != '..')
				{
					$flag = $this->flags['no'];
				
					if($this->sessionAction->getFolder() == $this->currentFolderPath)
					{//check if any flag associated with this folder or file
						$folder = addTrailingSlash(backslashToSlash($this->currentFolderPath));
						if(in_array($folder . $file, $this->sessionAction->get()))
						{
							if($this->sessionAction->getAction() == "copy")
							{
								$flag = $this->flags['copy'];
							}else 
							{
								$flag = $this->flags['cut'];
							}
						}
					}					
					$path=$this->currentFolderPath.$file;
					if(is_dir($path) && isListingDocument($path) )
					{
						$this->currentFolderInfo['subdir']++;
						//fix count left folders for Chamilo
						$deleted_by_Chamilo_folder='_DELETED_';
						$css_folder_Chamilo='css';
						$hotpotatoes_folder_Chamilo='HotPotatoes_files';
						$chat_files_Chamilo='chat_files';
						$certificates_Chamilo='certificates';
						//show group's directory only if I'm member. Or if I'm a teacher. TODO: check groups not necessary because the student dont have access to main folder documents (only to document/group or document/shared_folder). Teachers can access to all groups ?
						$group_folder='_groupdocs';
						$hide_doc_group=false;
						if(ereg($group_folder, $path))
						{
							$hide_doc_group=true;
							if($is_user_in_group ||( $to_group_id!=0 && api_is_allowed_to_edit()))
							{
								$hide_doc_group=false;
							}

						}

						if(ereg($deleted_by_Chamilo_folder, $path)|| ereg($css_folder_Chamilo, $path) || ereg($hotpotatoes_folder_Chamilo, $path) || ereg($chat_files_Chamilo, $path) || ereg($certificates_Chamilo, $path) || $hide_doc_group || $file[0]=='.')
						{
							$this->currentFolderInfo['subdir']=$this->currentFolderInfo['subdir']-1;
						}
						//end fix for Chamilo						

						if(!$this->calculateSubdir)
						{			
						}else 
						{
							
								$folder = $this->getFolderInfo($path);
								$folder['flag'] = $flag;
								$folders[$file] = $folder;
								$outputs[$file] = $folders[$file];							
						}

						
					}elseif(is_file($path) && isListingDocument($path))
					{

							$obj = new file($path);
							$tem = $obj->getFileInfo();
							if(sizeof($tem))
							{
								$fileType = $this->getFileType($file);
								foreach($fileType as $k=>$v)
								{
									$tem[$k] = $v;
								}
								$this->currentFolderInfo['size'] += $tem['size'];
								$this->currentFolderInfo['file']++;
								//fix count left files for Chamilo
								$deleted_by_Chamilo_file=' DELETED '; // ' DELETED ' not '_DELETED_' because in $file['name'] _ is replaced with blank see class.manager.php
								if(ereg($deleted_by_Chamilo_file, $tem['name']) || $tem['name'][0]=='.')
								{
									$this->currentFolderInfo['file']=$this->currentFolderInfo['file']-1;
								}
								///end fix for Chamilo
								$tem['path'] = backslashToSlash($path);		
								$tem['type'] = "file";
								$tem['flag'] = $flag;
								$files[$file] = $tem;
								$outputs[$file] = $tem;
								$tem = array();
								$obj->close();
								
							}							

				
					}
					
				}
			}
			if($this->forceFolderOnTop)
			{
				uksort($folders, "strnatcasecmp");
				uksort($files, "strnatcasecmp");
				$outputs = array();
				foreach($folders as $v)
				{
					$outputs[] = $v;
				}
				foreach ($files as $v)
				{
					$outputs[] = $v;
				}
			}else 
			{
				uksort($outputs, "strnatcasecmp");
			}
			
			@closedir($dirHandler);
		}else
		{
			trigger_error('Unable to locate the folder ' . $this->currentFolderPath, E_NOTICE);
		}
		return $outputs;
	}


	/**
	 * get current or the specified dir information
	 *
	 * @param string $path
	 * @return array
	 */
	function getFolderInfo($path=null)
	{
		if(is_null($path))
		{
			return $this->currentFolderInfo;
		}else 
		{
			$obj = new manager($path, false);
			$obj->setSessionAction($this->sessionAction);
			$obj->getFileList();
			return $obj->getFolderInfo();			
		}

	}

		/**
		 * return the file type of a file.
		 *
		 * @param string file name
		 * @return array
		 */
		function getFileType($fileName, $checkIfDir = false) 
		{
			
			$ext = strtolower($this->_getExtension($fileName, $checkIfDir));
			
			foreach ($this->fileTypes as $fileType) 
			{
				if(in_array($ext, $fileType[0]))
				{
					return array("cssClass" => $fileType[1], "fileType" => $fileType[2], "preview" => $fileType[3], 'test'=>5);
				}
			}
			if(!empty($fileName))
			{//this is folder
				if(empty($ext))
				{
					if(is_dir($fileName))
					{

						return array("cssClass" => ($checkIfDir && $this->isDirEmpty($fileName)?'folderEmpty':"folder") , "fileType" => "Folder", "preview" => 0, 'test'=>1);
					}else 
					{
						return array("cssClass" => "fileUnknown", "fileType" => SEARCH_TYPE_UNKNOWN, "preview" => 0, 'test'=>2);
					}
				}else 
				{
					return array("cssClass" => "fileUnknown", "fileType" => SEARCH_TYPE_UNKNOWN, "preview" => 0, 'test'=>3, 'ext'=>$ext , 'filename'=>$fileName);
				}
				
			}else
			{//this is unknown file
				return array("cssClass" => "fileUnknown", "fileType" => SEARCH_TYPE_UNKNOWN, "preview" => 0, 'test'=>4);
			}
		
		
		}

	/**
		 * return the predefined file types
		 *
		 * @return arrray
		 */
	function getFileTypes()
	{
		return $this->fileTypes;
	}
	/**
		 * print out the file types
		 *
		 */
	function printFileTypes()
	{
		foreach($fileTypes as $fileType)
		{
			if(isset($fileType[0]) && is_array($fileType[0]))
			{
				foreach($fileType[0] as $type)
				{
					echo $type. ",";
				}
			}
		}
	}

    /**
	 * Get the extension of a file name
	 * 
	 * @param  string $file
 	 * @return string
     * @copyright this function originally come from Andy's php 
	 */
    function _getExtension($file, $checkIfDir = false)
    {
    	if($checkIfDir && file_exists($file) && is_dir($file))
    	{
    		return '';
    	}else 
    	{
    		return @substr(@strrchr($file, "."), 1);
    	}
    	
    	
    }	

	function isDirEmpty($path)
	{
		$dirHandler = @opendir($path);
		if($dirHandler)
		{
			while(false !== ($file = readdir($dirHandler)))
			{
				if($file != '.' && $file != '..')
				{
					@closedir($dirHandler);
					return false;
					
				}
			}
			
			@closedir($dirHandler);
				
		}	
		return true;	
	}
}
?>