<?php
	/**
	 * processing the uploaded files
	 * @author Logan Cai (cailongqun [at] yahoo [dot] com [dot] cn)
	 * @link www.phpletter.com
	 * @since 22/May/2007
	 *
	 */	
	sleep(3);
	require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "inc" . DIRECTORY_SEPARATOR . "config.php");
	echo "{";
	$error = "";
	$info = "";
	
	include_once(CLASS_UPLOAD);
	$upload = new Upload();
								
	$upload->setInvalidFileExt(explode(",", CONFIG_UPLOAD_INVALID_EXTS));
	if(CONFIG_SYS_VIEW_ONLY || !CONFIG_OPTIONS_UPLOAD)
	{
		$error = SYS_DISABLED;
	}
	elseif(empty($_GET['folder']) || !isUnderRoot($_GET['folder']))
	{
		$error = ERR_FOLDER_PATH_NOT_ALLOWED;
	}else	if(!$upload->isFileUploaded('file'))
	{
		$error = ERR_FILE_NOT_UPLOADED;
	}else if(!$upload->moveUploadedFile($_GET['folder']))
	{
		$error = ERR_FILE_MOVE_FAILED;
	}	
	elseif(!$upload->isPermittedFileExt(explode(",", CONFIG_UPLOAD_VALID_EXTS)))
	{		
		$error = ERR_FILE_TYPE_NOT_ALLOWED;
	}elseif(defined('CONFIG_UPLOAD_MAXSIZE') && CONFIG_UPLOAD_MAXSIZE && $upload->isSizeTooBig(CONFIG_UPLOAD_MAXSIZE))
	{		
		 $error = sprintf(ERROR_FILE_TOO_BID, transformFileSize(CONFIG_UPLOAD_MAXSIZE));
	}else
	{
							include_once(CLASS_FILE);
							$path = $upload->getFilePath();
							$obj = new file($path);
							$tem = $obj->getFileInfo();							
							if(sizeof($tem))
							{	
								include_once(CLASS_MANAGER);
							
								$manager = new manager($upload->getFilePath(), false);			
															
								$fileType = $manager->getFileType($upload->getFileName());

								foreach($fileType as $k=>$v)
								{
									$tem[$k] = $v;
								}
								
								$tem['path'] = backslashToSlash($path);		
								$tem['type'] = "file";
								$tem['size'] = transformFileSize($tem['size']);
								$tem['ctime'] = date(DATE_TIME_FORMAT, $tem['ctime']);
								$tem['mtime'] = date(DATE_TIME_FORMAT, $tem['mtime']);
								$tem['short_name'] = shortenFileName($tem['name']);						
								$tem['flag'] = 'noFlag';
								
								/**
								* Bridge to Chamilo documents tool
								* @author Juan Carlos Raña Trabado
								*/

								if(!empty($_course['path']))
								{
									//only inside courses									
									$fullPath= $upload->getFilePath();		//get	ajaxmanager. Sample ../../../../../../../courses/TEST/document/Grupo_1_groupdocs/image.jpg
									$folderInfo = $manager->getFolderInfo(); //get	ajaxmanager
									$mainPath= getParentFolderPath($folderInfo['path']);//get	ajaxmanager. Sample ../../../../../../../courses/TEST/document/Grupo_1_groupdocs/
									$chamiloFolder = substr($fullPath, strlen($mainPath)-strlen($fullPath)-1);
									$chamiloFile = $tem['name'];	//get	ajaxmanager
									$chamiloFileSize = filesize($fullPath); //get ajaxmanager
									if(!empty($group_properties['directory'])) //get Chamilo
									{
										$chamiloFolder=$group_properties['directory'].$chamiloFolder;//get Chamilo
									}
									else
									{
										if(!api_is_allowed_to_edit())
										{
											$current_session_id = api_get_session_id();
											if($current_session_id==0)
											{											
												$chamiloFolder='/shared_folder/sf_user_'.api_get_user_id().$chamiloFolder;
											}
											else
											{
												$chamiloFolder='/shared_folder_session_'.$current_session_id.'/sf_user_'.api_get_user_id().$chamiloFolder;
											}
										}
									}

									$doc_id = add_document($_course, $chamiloFolder,'file', $chamiloFileSize , $chamiloFile); //get Chamilo
									$current_session_id = api_get_session_id();
									api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', api_get_user_id(),$to_group_id,null,null,null,$current_session_id);//get Chamilo
									
								}
								// end bridge								
								$obj->close();
								foreach($tem as $k=>$v)
								{
										$info .= sprintf(", %s:'%s'", $k, $v);									
								}

								$info .= sprintf(", url:'%s'",  getFileUrl($path));
								$info .= sprintf(", tipedit:'%s'",  TIP_DOC_RENAME);		

																				
							}else 
							{
								$error = ERR_FILE_NOT_AVAILABLE;
							}


	}
	echo "error:'" . $error . "'";
	echo $info;
	echo "}";
	
?>