<?php
		include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "inc" . DIRECTORY_SEPARATOR . "config.php");		
		$error = '';
		$fileMoved = array();
		$unmovedDocDueToSamePath = array();
	if(CONFIG_SYS_VIEW_ONLY || (!CONFIG_OPTIONS_CUT && !CONFIG_OPTIONS_COPY))
	{
		$error = SYS_DISABLED;
	}
	elseif(empty($_GET['current_folder_path']))
		{
			$error = ERR_NOT_DEST_FOLDER_SPECIFIED;
		}elseif(!file_exists($_GET['current_folder_path']) || !is_dir($_GET['current_folder_path']))
		{
			$error = ERR_DEST_FOLDER_NOT_FOUND;
		}elseif(!isUnderRoot($_GET['current_folder_path']))
		{
			$error = ERR_DEST_FOLDER_NOT_ALLOWED;
		}else 
		{
			
			include_once(CLASS_MANAGER);
			include_once(CLASS_SESSION_ACTION);
			$sessionAction = new SessionAction();
			include_once(DIR_AJAX_INC . "class.manager.php");	
			$manager = new manager();
			$manager->setSessionAction($sessionAction);
			$selectedDocuments = $sessionAction->get();
			
			$destFolderPath = addTrailingSlash(backslashToSlash($_GET['current_folder_path']));
			
			
			if(sizeof($selectedDocuments))
			{
				//get all files within the destination folder
				$allDocs = array();
				if(($fh = @opendir($_GET['current_folder_path'])))
				{
					while(($file = readdir($fh)) && $file != '.' && $file != '..')
					{
						$allDocs[] = getRealPath($destFolderPath . $file);
					}
				}

				include_once(CLASS_FILE);
				$file = new file();
				//check if all files are allowed to cut or copy

				foreach($selectedDocuments as $doc)
				{
					if(file_exists($doc) && isUnderRoot($doc) )
					{
						
						if( array_search(getRealPath($doc), $allDocs) === false || CONFIG_OVERWRITTEN)
						{
							if(CONFIG_OVERWRITTEN)
							{
								$file->delete($doc);
							}
							if($file->copyTo($doc, $_GET['current_folder_path']))
							{
								
								$finalPath = $destFolderPath . basename($doc);
								$objFile = new file($finalPath);
								$tem = $objFile->getFileInfo();
								$obj = new manager($finalPath, false);			
													
								$fileType = $obj->getFileType($finalPath, (is_dir($finalPath)?true:false));
								
								foreach($fileType as $k=>$v)
								{
									$tem[$k] = $v;
								}
								
/*								foreach ($folderInfo as $k=>$v)
								{
									$tem['i_' . $k] = $v;
								}
								if($folderInfo['type'] == 'folder' && empty($folderInfo['subdir']) &&  empty($folderInfo['file']))
								{
									$tem['cssClass'] = 'folderEmpty';
								}*/
								
								$tem['final_path'] = $finalPath;
								$tem['path'] = backslashToSlash($finalPath);		
								$tem['type'] = (is_dir($finalPath)?'folder':'file');
								$tem['size'] = @transformFileSize($tem['size']);
								$tem['ctime'] = date(DATE_TIME_FORMAT, $tem['ctime']);
								$tem['mtime'] = date(DATE_TIME_FORMAT, $tem['mtime']);
								$tem['flag'] = 'noFlag';
								$tem['url'] = getFileUrl($doc);

								/**
								* Bridge to Chamilo documents tool
								* @author Juan Carlos Raña Trabado
								*/

								if(!empty($_course['path']))
								{
									
									$mainPath= getParentFolderPath($folderInfo['path']);// from ajaxfilemanager sample ../../../../../../../courses/TEST/document/
									$fullPath=$tem['final_path'];// from ajaxfilemanager sample ../../../../../../../courses/TEST/document/icons/book_highlight.jpg									
									$chamiloFolder = substr($fullPath, strlen($mainPath)-strlen($fullPath)-1); // sample /icons/book_highlight.jpg or /icons
									$chamiloFile = $tem['name'];	//get ajaxmanager
									$chamiloFileSize = filesize($fullPath);//get ajaxmanager
									if(!empty($group_properties['directory'])){
										$chamiloFolder=$group_properties['directory'].$chamiloFolder;//get Chamilo
									}
									//cut and paste or copy and paste
									if($sessionAction->getAction() == "cut"){ //from ajaxmanager								
										$full_old_path=$doc;// get from ajaxfilemanager sample ../../../../../../../courses/TEST/document/book_highlight.jpg or if you select a folder: ../../../../../../../courses/TEST/document/trainer/	
										if(is_dir($full_old_path)){
											//update first folder
											$old_path = substr($full_old_path, strlen($mainPath)-strlen($full_old_path)-1,-1);
											if(!empty($group_properties['directory'])){											
												$old_path = $group_properties['directory'].$old_path;//get Chamilo
											}
											$new_path = $chamiloFolder; //sample /images
											$dbTable = Database::get_course_table(TABLE_DOCUMENT);//Chamilo
											update_db_info('update',$old_path,$new_path);//Chamilo	
											$curdirpath=$new_path;
											$doc_id = DocumentManager::get_document_id($_course, $curdirpath);//Chamilo
											$current_session_id = api_get_session_id();
											api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'FolderMoved', api_get_user_id(),$to_group_id,null,null,null,$current_session_id);										
											// update database: inside subdirectories and files										
											$course_dir   = $_course['path']."/document";//get Chamilo
											$sys_course_path = api_get_path(SYS_COURSE_PATH);//get Chamilo
											$base_work_dir = $sys_course_path.$course_dir; // sample c:/xampp/htdocs/chamilo/courses/TEST/document
											$source_dir=$base_work_dir.$chamiloFolder;
											
											//thanks to donovan
											$path = '';
											   $stack[] = $source_dir;
											   while ($stack) {
												   $thisdir = array_pop($stack);
												   if ($dircont = scandir($thisdir)) {
													   $i=0;
													   while (isset($dircont[$i])) {
														   if ($dircont[$i] !== '.' && $dircont[$i] !== '..') {
															   $current_file = "{$thisdir}/{$dircont[$i]}";
															   if (is_file($current_file)) {
																   $path[] = "{$thisdir}/{$dircont[$i]}";
															   } elseif (is_dir($current_file)) {
																	$path[] = "{$thisdir}/{$dircont[$i]}";
																   $stack[] = $current_file;
															   }
														   }
														   $i++;
													   }
												   }
											   }
	
											$invisibleFileNames='';//fill with file names that do not want cut											
											
											foreach ($path as $item){
												//Sample $item is C:/xampp/htdocs/chamilo/courses/TEST/document/books/book_highlight.jpg
												$file_orig=basename($item);
												if($file_orig[0]!='.' && !in_array($invisibleFileNames)){
													$source_item= substr($item, (strlen($base_work_dir)-strlen($item)));// sample /books/book_highlight.jpg or /books
													$target_item=$source_item;
													$chamiloFolder=$target_item;
													$chamiloFile=basename($target_item);
													//
													if (is_dir($item)){
														$old_path = substr($full_old_path, strlen($mainPath)-strlen($full_old_path)-1,-1);
														if(!empty($group_properties['directory'])){											
															$old_path = $group_properties['directory'].$old_path;//get Chamilo
														}
														$new_path = $chamiloFolder; //sample /images
														$dbTable = Database::get_course_table(TABLE_DOCUMENT);//Chamilo
														update_db_info('update',$old_path,$new_path);//Chamilo
														$curdirpath=$new_path;
														$doc_id = DocumentManager::get_document_id($_course, $curdirpath);//Chamilo
														$current_session_id = api_get_session_id();
														api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'FolderMoved', api_get_user_id(),$to_group_id,null,null,null,$current_session_id);
														
													}
													elseif(is_file($item)){
														$old_path = substr($full_old_path, strlen($mainPath)-strlen($full_old_path)-1);
														if(!empty($group_properties['directory'])){											
															$old_path = $group_properties['directory'].$old_path;//get Chamilo
														}
														$new_path = $chamiloFolder; //sample /images/book_highlight.jpg
														$dbTable = Database::get_course_table(TABLE_DOCUMENT);//Chamilo
														update_db_info('update',$old_path,$new_path);//Chamilo
														//update items
														$curdirpath=$new_path;
														$doc_id = DocumentManager::get_document_id($_course, $curdirpath);//Chamilo
														$current_session_id = api_get_session_id();
														api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentMoved', api_get_user_id(),$to_group_id,null,null,null,$current_session_id);
													}
												}
											}
										}
										elseif(is_file($full_old_path)){
											$old_path = substr($full_old_path, strlen($mainPath)-strlen($full_old_path)-1);
											if(!empty($group_properties['directory'])){											
												$old_path = $group_properties['directory'].$old_path;//get Chamilo
											}
											$new_path = $chamiloFolder; //sample /images/book_highlight.jpg						
											//update documents											
											$dbTable = Database::get_course_table(TABLE_DOCUMENT);//Chamilo
											update_db_info('update',$old_path,$new_path);//Chamilo
											//update items
											$curdirpath=$new_path;
											$doc_id = DocumentManager::get_document_id($_course, $curdirpath);
											$current_session_id = api_get_session_id();
											api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentMoved', api_get_user_id(),$to_group_id,null,null,null,$current_session_id);
										}										
									}
									else{
										$current_session_id = api_get_session_id();
										if ($tem['type']=="folder"){
											//add to database the first folder to target
											$doc_id = add_document($_course, $chamiloFolder,'folder', $chamiloFileSize , $chamiloFile); //get Chamilo							
											api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'FolderCreated', api_get_user_id(),$to_group_id,null,null,null,$current_session_id);//get Chamilo
											
											// add to database inside subdirectories and files
										
											$course_dir   = $_course['path']."/document";//get Chamilo
											$sys_course_path = api_get_path(SYS_COURSE_PATH);//get Chamilo
											$base_work_dir = $sys_course_path.$course_dir; // sample c:/xampp/htdocs/chamilo/courses/TEST/document
											$source_dir=$base_work_dir.$chamiloFolder;
											
											//thanks to donovan
											$path = '';
											   $stack[] = $source_dir;
											   while ($stack) {
												   $thisdir = array_pop($stack);
												   if ($dircont = scandir($thisdir)) {
													   $i=0;
													   while (isset($dircont[$i])) {
														   if ($dircont[$i] !== '.' && $dircont[$i] !== '..') {
															   $current_file = "{$thisdir}/{$dircont[$i]}";
															   if (is_file($current_file)) {
																   $path[] = "{$thisdir}/{$dircont[$i]}";
															   } elseif (is_dir($current_file)) {
																	$path[] = "{$thisdir}/{$dircont[$i]}";
																   $stack[] = $current_file;
															   }
														   }
														   $i++;
													   }
												   }
											   }
	
											$invisibleFileNames='';//fill with file names that do not want cut or copy											
											
											foreach ($path as $item){
												//Sample $item is C:/xampp/htdocs/chamilo/courses/TEST/document/books/book_highlight.jpg
												$file_orig=basename($item);
												if($file_orig[0]!='.' && !in_array($invisibleFileNames)){
													$source_item= substr($item, (strlen($base_work_dir)-strlen($item)));// sample /books/book_highlight.jpg or /books
													$target_item=$source_item;
													$chamiloFolder=$target_item;
													$chamiloFile=basename($target_item);
													//
													if (is_dir($item)){
														$doc_id = add_document($_course, $chamiloFolder,'folder', $chamiloFileSize , $chamiloFile); //get Chamilo							
														api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'FolderCreated', api_get_user_id(),$to_group_id,null,null,null,$current_session_id);//get Chamilo
													}
													elseif(is_file($item)){
														$chamiloFileSize=filesize($item);
														$doc_id = add_document($_course, $chamiloFolder,'file', $chamiloFileSize , $chamiloFile); //get Chamilo							
														api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', api_get_user_id(),$to_group_id,null,null,null,$current_session_id);//get Chamilo	
													}
												}
											}										
										}
										elseif ($tem['type']=="file"){
										$doc_id = add_document($_course, $chamiloFolder,'file', $chamiloFileSize , $chamiloFile); //get Chamilo							
										api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', api_get_user_id(),$to_group_id,null,null,null,$current_session_id);//get Chamilo		
										}
									}			

								}		
								//end bridge
								
								$manager = null;
								if($sessionAction->getAction() == "cut")
								{
									$file->delete($doc);
								}
								
								
								
								$fileMoved[sizeof($fileMoved)] = $tem;
								$tem = null;
							}							
						}else 
						{
							$unmovedDocDueToSamePath[] = $doc;
						}
							
					}
				}

				$sessionAction->set(array());
			}
			if(sizeof($unmovedDocDueToSamePath) == sizeof($selectedDocuments))
			{
				$error = ERR_DEST_FOLDER_NOT_ALLOWED;
			}elseif(sizeof($unmovedDocDueToSamePath)) 
			{
				foreach($unmovedDocDueToSamePath as $v)
				{
					$error .=  sprintf(ERR_UNABLE_TO_MOVE_TO_SAME_DEST, $v) . "\r\n";
				}
			}
		}
		
		echo "{'error':'" . $error . "', 'unmoved_files':" . sizeof($unmovedDocDueToSamePath) . ", 'files':{";
		foreach($fileMoved as  $i=>$file)
		{
			
			echo ($i>0?', ':' ') . $i . ": { ";
			$j = 0;
			foreach($file as $k=>$v)
			{
				echo ($j++ > 0? ", ":'') . "'" . $k . "':'" . $v . "'"; 
				
			}
			echo "} ";
		}
		echo "} }";
	
?>