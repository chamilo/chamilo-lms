<?php
	/**
	 * create a folder
	 * @author Logan Cai (cailongqun [at] yahoo [dot] com [dot] cn)
	 * @link www.phpletter.com
	 * @since 22/May/2007
	 *
	 * Modify for Dokeos
	 * @author Juan Carlos Raña
	 * @since 18/January/2009
	 */

	require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "inc" . DIRECTORY_SEPARATOR . "config.php");	
	
	echo "{";
	$error = "";
	$info = "";	
/*	$_POST['new_folder'] = substr(md5(time()), 1, 5);
	$_POST['currentFolderPath'] = "../../uploaded/";*/
	$_POST['new_folder']=htmlentities($_POST['new_folder'],ENT_QUOTES);//Dokeos improve security 
	$_POST['new_folder']=str_replace(' ','_',$_POST['new_folder']);//Interaction with Dokeos. Because fix long names. See: ajaxfilemanager/inc/class.manager.php
	$_POST['currentFolderPath']=htmlentities($_POST['currentFolderPath'],ENT_QUOTES);//Dokeos improve security 
	
	if(CONFIG_SYS_VIEW_ONLY || !CONFIG_OPTIONS_NEWFOLDER)
	{
		$error = SYS_DISABLED;
	}
	elseif(empty($_POST['new_folder']))
	{
		$error  =  ERR_FOLDER_NAME_EMPTY;
	}elseif(!preg_match("/^[a-zA-Z0-9_\- ]+$/", $_POST['new_folder']))
	{
		$error  =  ERR_FOLDER_FORMAT;
	}else if(empty($_POST['currentFolderPath']) || !isUnderRoot($_POST['currentFolderPath']))
	{
		$error = ERR_FOLDER_PATH_NOT_ALLOWED;
	}
	elseif(file_exists(addTrailingSlash($_POST['currentFolderPath']) . $_POST['new_folder']))
	{
		$error = ERR_FOLDER_EXISTS;
	}else
	{
	include_once(CLASS_FILE);
		$file = new file();
		
		if($file->mkdir(addTrailingSlash($_POST['currentFolderPath']) . $_POST['new_folder'], 0775))
		{
					include_once(CLASS_MANAGER);
					$manager = new manager(addTrailingSlash($_POST['currentFolderPath']) . $_POST['new_folder'], false);
					$pathInfo = $manager->getFolderInfo(addTrailingSlash($_POST['currentFolderPath']) . $_POST['new_folder']);
					
					/////////////bridge to Dokeos by Juan Carlos Raña Trabado
					if(!empty($_course['path']))
					{
					//only inside courses				
						$mainPath='../../../../../../../courses/'.$_course['path'].'/document/';//get Dokeos
						$fullPath = $_POST['currentFolderPath'].$_POST['new_folder']; //get Ajaxfilemanager						
						$dokeosPath = substr($fullPath, strlen($mainPath)-strlen($fullPath)-1);
						$_POST['new_folder']=str_replace('_',' ',$_POST['new_folder']);//Restore for interaction with Dokeos. Because fix long names. See: ajaxfilemanager/inc/class.manager.php
						$dokeosFile = $_POST['new_folder']; //get Ajaxfilemanager
						
						$doc_id = add_document($_course, $dokeosPath,'folder', 0, $dokeosFile); //get Dokeos	
						api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'FolderCreated', api_get_user_id(),$to_group_id);//get Dokeos
						api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'invisible', api_get_user_id(),$to_group_id);//get Dokeos
					}
					// end bridge to Dokeos
					foreach($pathInfo as $k=>$v)
					{				
						switch ($k)
						{


							case "ctime";								
							case "mtime":
							case "atime":
								$v = date(DATE_TIME_FORMAT, $v);
								break;
							case 'name':
								$info .= sprintf(", %s:'%s'", 'short_name', shortenFileName($v));
								break;
							case 'cssClass':
								$v = 'folderEmpty';
								break;
						}							
						$info .= sprintf(", %s:'%s'", $k, $v);
					}
		}else 
		{
			$error = ERR_FOLDER_CREATION_FAILED;
		}
		//$error = "For security reason, folder creation function has been disabled.";
	}
	echo "error:'" . $error . "'";
	echo $info;
	echo "}";
?>