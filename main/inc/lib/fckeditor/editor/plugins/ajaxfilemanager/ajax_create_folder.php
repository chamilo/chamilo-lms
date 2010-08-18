<?php
	/**
	 * create a folder
	 * @author Logan Cai (cailongqun [at] yahoo [dot] com [dot] cn)
	 * @link www.phpletter.com
	 * @since 22/May/2007
	 *
	 * Modify for Chamilo
	 * @author Juan Carlos Ra�a
	 * @since 18/January/2009
	 */

	require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "inc" . DIRECTORY_SEPARATOR . "config.php");	
	//@ob_start(); //Disabled for integration with Chamilo
	//displayArray($_POST); //Disabled for integration with Chamilo
	//writeInfo(@ob_get_clean()); //Disabled for integration with Chamilo
	echo "{";
	$error = "";
	$info = "";	
/*	$_POST['new_folder'] = substr(md5(time()), 1, 5);
	$_POST['currentFolderPath'] = "../../uploaded/";*/
	$_POST['new_folder']=htmlentities($_POST['new_folder'],ENT_QUOTES);//Chamilo improve security
	$_POST['new_folder']=str_replace(' ','_',$_POST['new_folder']);//Interaction with Chamilo. Because fix long names. See: ajaxfilemanager/inc/class.manager.php
	$_POST['currentFolderPath']=htmlentities($_POST['currentFolderPath'],ENT_QUOTES);//Chamilo improve security

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
								
					//bridge to Chamilo
					if(!empty($_course['path']))
					{
					//only inside courses
						$mainPath='../../../../../../../courses/'.$_course['path'].'/document/';//get Chamilo
						$fullPath = $_POST['currentFolderPath'].$_POST['new_folder']; //get Ajaxfilemanager
						$chamiloPath = substr($fullPath, strlen($mainPath)-strlen($fullPath)-1);
						$_POST['new_folder']=str_replace('_',' ',$_POST['new_folder']);//Restore for interaction with Chamilo. Because fix long names. See: ajaxfilemanager/inc/class.manager.php
						$chamiloFile = $_POST['new_folder']; //get Ajaxfilemanager

						$doc_id = add_document($_course, $chamiloPath,'folder', 0, $chamiloFile); //get Chamilo
						$current_session_id = api_get_session_id();//get Chamilo
						api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'FolderCreated', api_get_user_id(),$to_group_id,null,null,null,$current_session_id);//get Chamilo
						api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'invisible', api_get_user_id(),$to_group_id,null,null,null,$current_session_id);//get Chamilo
					}
					// end bridge to Chamilo

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