<?php
	/**
	 * delete selected files
	 * @author Logan Cai (cailongqun [at] yahoo [dot] com [dot] cn)
	 * @link www.phpletter.com
	 * @since 22/April/2007
     *
	 * Modify for Dokeos
	 * @author Juan Carlos Raña
	 * @since 19/March/2009
	 */
	
	require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "inc" . DIRECTORY_SEPARATOR . "config.php");
	$error = "";
	
	if(CONFIG_SYS_VIEW_ONLY || !CONFIG_OPTIONS_DELETE)
	{
		$error = SYS_DISABLED;
	}
	elseif(!empty($_GET['delete']))
	{//delete the selected file from context menu
		if(!file_exists($_GET['delete']))
		{
			$error = ERR_FILE_NOT_AVAILABLE;
		}
		elseif(!isUnderRoot($_GET['delete']))
		{
			$error = ERR_FOLDER_PATH_NOT_ALLOWED;
		}else
		{
				include_once(CLASS_FILE);
				$file = new file();
				if(is_dir($_GET['delete'])
					 &&  isValidPattern(CONFIG_SYS_INC_DIR_PATTERN, getBaseName($_GET['delete'])) 
					 && !isInvalidPattern(CONFIG_SYS_EXC_DIR_PATTERN, getBaseName($_GET['delete'])))
					{
					/////////////bridge to Dokeos by Juan Carlos Raña Trabado
					//find path
					$mainPath='../../../../../../../courses/'.$_course['path'].'/document/';//get Dokeos					
					$fullPath = $_GET['delete']; //get Ajaxfilemanager	
					$dokeosPath = substr($fullPath, strlen($mainPath)-strlen($fullPath)-1,-1);
					//find base_work_dir
					$course_dir   = $_course['path']."/document";//get Dokeos
					$sys_course_path = api_get_path(SYS_COURSE_PATH);//get Dokeos
					$base_work_dir = $sys_course_path.$course_dir; // sample c:/xampp/htdocs/dokeos2009beta/courses/JUAN2009/document
					//delete directory
					
					   //check protect directories 
					   if ($dokeosPath!='/audio' && $dokeosPath!='/flash' && $dokeosPath!='/images' && $dokeosPath!='/shared_folder' && $dokeosPath!='/video')
					   {							   
						   if(! $is_allowed_to_edit && DocumentManager::check_readonly($_course,api_get_user_id(),$dokeosPath))
						   {		
								$error=get_lang('CantDeleteReadonlyFiles'); //From Dokeos to Ajaxfilemanager
						   }							   
						   else
						   {
								$deleted= DocumentManager::delete_document($_course,$dokeosPath,$base_work_dir); //deleted by Dokeos
								//$file->delete(addTrailingSlash(backslashToSlash($doc))); // disabled deleted by ajaxfilemanager
						   }
					   }
					   else
					   {
							$error=get_lang('ProtectFolder'); //From Dokeos to Ajaxfilemanager
					   }
					//////end bridge to Dokeos
						
						
					}elseif(is_file($_GET['delete']) 
					&& isValidPattern(CONFIG_SYS_INC_FILE_PATTERN, getBaseName($_GET['delete']))
					&& !isInvalidPattern(CONFIG_SYS_EXC_FILE_PATTERN, getBaseName($_GET['delete']))
					)
					{
					/////////////bridge to Dokeos by Juan Carlos Raña Trabado
					//find path
					$mainPath='../../../../../../../courses/'.$_course['path'].'/document/';//get Dokeos					
					$fullPath = $_GET['delete']; //get Ajaxfilemanager
					$dokeosPath = substr($fullPath, strlen($mainPath)-strlen($fullPath)-1);					
					//find base_work_dir
					$course_dir   = $_course['path']."/document";//get Dokeos
					$sys_course_path = api_get_path(SYS_COURSE_PATH);//get Dokeos
					$base_work_dir = $sys_course_path.$course_dir; // sample c:/xampp/htdocs/dokeos2009beta/courses/JUAN2009/document
					//delete file
												   
						   if(! $is_allowed_to_edit && DocumentManager::check_readonly($_course,api_get_user_id(),$dokeosPath))
						   {		
								$error=get_lang('CantDeleteReadonlyFiles'); //From Dokeos to Ajaxfilemanager
						   }							   
						   else
						   {
						   
								$deleted= DocumentManager::delete_document($_course,$dokeosPath,$base_work_dir); //deleted by Dokeos
								//$file->delete(($_GET['delete'])); // disabled deleted by ajaxfilemanager
								
						   }
					//////end bridge to Dokeos
					}			
		}
	}else 
	{
		if(!isset($_POST['selectedDoc']) || !is_array($_POST['selectedDoc']) || sizeof($_POST['selectedDoc']) < 1)
		{
			$error = ERR_NOT_FILE_SELECTED;
		}
		else 
		{

			include_once(CLASS_FILE);
			$file = new file();
			
			foreach($_POST['selectedDoc'] as $doc)
			{
				if(file_exists($doc) && isUnderRoot($doc))
				{
					if(is_dir($doc)
					 &&  isValidPattern(CONFIG_SYS_INC_DIR_PATTERN, $doc) 
					 && !isInvalidPattern(CONFIG_SYS_EXC_DIR_PATTERN, $doc))
					{
					/////////////bridge to Dokeos by Juan Carlos Raña Trabado
					//find path
					$mainPath='../../../../../../../courses/'.$_course['path'].'/document/';//get Dokeos					
					$fullPath = $doc; //get Ajaxfilemanager				
					$dokeosPath = substr($fullPath, strlen($mainPath)-strlen($fullPath)-1,-1);
					//find base_work_dir
					$course_dir   = $_course['path']."/document";//get Dokeos
					$sys_course_path = api_get_path(SYS_COURSE_PATH);//get Dokeos
					$base_work_dir = $sys_course_path.$course_dir; // sample c:/xampp/htdocs/dokeos2009beta/courses/JUAN2009/document
					//delete directory
					
					   //check protect directories 
					   if ($dokeosPath!='/audio' && $dokeosPath!='/flash' && $dokeosPath!='/images' && $dokeosPath!='/shared_folder' && $dokeosPath!='/video')
					   {							   
						   if(! $is_allowed_to_edit && DocumentManager::check_readonly($_course,api_get_user_id(),$dokeosPath))
						   {		
								$error=get_lang('CantDeleteReadonlyFiles'); //From Dokeos to Ajaxfilemanager
						   }							   
						   else
						   {
								$deleted= DocumentManager::delete_document($_course,$dokeosPath,$base_work_dir); //deleted by Dokeos
								//$file->delete(addTrailingSlash(backslashToSlash($doc))); // disabled deleted by ajaxfilemanager
						   }
					   }
					   else
					   {
							$error=get_lang('ProtectFolder'); //From Dokeos to Ajaxfilemanager
					   }
					//////end bridge to Dokeos
						
					}elseif(is_file($doc) 
					&& isValidPattern(CONFIG_SYS_INC_FILE_PATTERN, $doc)
					&& !isInvalidPattern(CONFIG_SYS_EXC_FILE_PATTERN, $doc)
					)
					{
					/////////////bridge to Dokeos by Juan Carlos Raña Trabado
					//find path
					$mainPath='../../../../../../../courses/'.$_course['path'].'/document/';//get Dokeos					
					$fullPath = $doc; //get Ajaxfilemanager	
					$dokeosPath = substr($fullPath, strlen($mainPath)-strlen($fullPath)-1);
					//find base_work_dir
					$course_dir   = $_course['path']."/document";//get Dokeos
					$sys_course_path = api_get_path(SYS_COURSE_PATH);//get Dokeos
					$base_work_dir = $sys_course_path.$course_dir; // sample c:/xampp/htdocs/dokeos2009beta/courses/JUAN2009/document
					//delete file
												   
						   if(! $is_allowed_to_edit && DocumentManager::check_readonly($_course,api_get_user_id(),$dokeosPath))
						   {		
								$error=get_lang('CantDeleteReadonlyFiles'); //From Dokeos to Ajaxfilemanager
						   }							   
						   else
						   {
						   
								$deleted= DocumentManager::delete_document($_course,$dokeosPath,$base_work_dir); //deleted by Dokeos
								//$file->delete($doc); // disabled deleted by ajaxfilemanager
								
						   }
					//////end bridge to Dokeos
					}					
				}

				
			}
		}		
	}

	echo "{error:'" . $error . "'}";
?>