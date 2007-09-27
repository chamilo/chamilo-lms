<?php //$id:$
/**
 * Defines the AICC class, which is meant to contain the aicc items (nuclear elements)
 * @package dokeos.learnpath.aicc
 * @author	Yannick Warnier <ywarnier@beeznest.org>
 * @license	GNU/GPL - See Dokeos license directory for details
 */
/**
 * Defines the "aicc" child of class "learnpath"
 * @package dokeos.learnpath.aicc
 */

class presentation extends learnpath {

	/**
	 * Class constructor. Based on the parent constructor.
	 * @param	string	Course code
	 * @param	integer	Learnpath ID in DB
	 * @param	integer	User ID
	 */
    function presentation($course_code=null,$resource_id=null,$user_id=null) {
    	if($this->debug>0){error_log('In presentation::presentation()',0);}
    	if(!empty($course_code) and !empty($resource_id) and !empty($user_id))
    	{
    		parent::learnpath($course_code, $resource_id, $user_id);
    	}else{
    		//do nothing but still build the presentation object
    	}
    }
    
    function convert_presentation($file){
    	
    	global $_course, $_user, $_configuration;
    
    	$file_name = (strrpos($file['name'],'.')>0 ? substr($file['name'], 0, strrpos($file['name'],'.')) : $file['name']);
    	$file_extension = (strrpos($file['name'],'.')>0 ? substr($file['name'], strrpos($file['name'],'.'),10) : '');
    	
    	
    	$file_name = remove_accents($file_name);
		$file_name = replace_dangerous_char($file_name,'strict');	
		$file_name = strtolower($file_name);
		
		$file['name'] = $file_name.$file_extension;
		
		
		$dir_name = '/'.$file_name.'_dir';
		
		
		// get properties of ppt file
		$document_datas = DocumentManager::get_all_document_data($_course, $file);
		$to_group_id = (empty($document_datas['to_group_id'])) ? 0 : $document_datas['to_group_id'];
		$to_user_id = (empty($document_datas['to_user_id'])) ? null : $document_datas['to_user_id'];
	
		//create the directory
		
		$base_work_dir = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
		$created_dir = create_unexisting_directory($_course,$_user['user_id'],$to_group_id,$to_user_id,$base_work_dir,$dir_name);
		
		
		move_uploaded_file($file['tmp_name'],$base_work_dir.'/'.$file['name']);
		$file = $base_work_dir.'/'.$file['name'];
		$perm = api_get_setting('permissions_for_new_files');
		$perm = octdec(!empty($perm)?$perm:'0660');
		chmod($file,$perm);
		
		
		/*
		 * exec java application
		 * the parameters of the program are :
		 * - javacommand on this server ;
		 * - host where openoffice is running;
		 * - port with which openoffice is listening
		 * - file to convert
		 * - folder where put the slides
		 * - ftppassword if required
		 * The program fills $files with the list of slides created
		 */
		$classpath = '-cp .:ridl.jar:js.jar:juh.jar:jurt.jar:jut.jar:java_uno.jar:java_uno_accessbridge.jar:edtftpj-1.5.2.jar:unoil.jar';
		if(strpos($_ENV['OS'],'Windows') !== false)
		{
			$classpath = str_replace(':',';',$classpath);
		}
		list($slide_width, $slide_height) = explode('x',api_get_setting('service_ppt2lp','size'));
		
		if(strpos($_ENV['OS'],'Windows') !== false)
		{
			$cmd = 'cd '.str_replace('/','\\',api_get_path(SYS_PATH)).'main/inc/lib/ppt2png && java '.$classpath.' DocumentConverter '.api_get_setting('service_ppt2lp','host').' 2002'.' "'.$file.'" "'.$base_work_dir.$created_dir.'"'.' '.$slide_width.' '.$slide_height.' '.api_get_setting('service_ppt2lp','user').' '.api_get_setting('service_ppt2lp','ftp_password');
		}
		else
		{
			$cmd = 'cd '.api_get_path(SYS_PATH).'main/inc/lib/ppt2png && java '.$classpath.' DocumentConverter '.api_get_setting('service_ppt2lp','host').' 2002'.' "'.$file.'" "'.$base_work_dir.$created_dir.'"'.' '.$slide_width.' '.$slide_height.' '.api_get_setting('service_ppt2lp','user').' '.api_get_setting('service_ppt2lp','ftp_password');
		}
		chmod ($base_work_dir.$created_dir,0777);
		$shell = exec($cmd, $files, $return);
		
		chmod ($base_work_dir.$created_dir,0755);
		if($return != 0) { //if the java application returns an error code
			DocumentManager::delete_document($_course, $dir_name, $base_work_dir);	 
			return false;   	
	    }
	    else {
			// create lp
			$learnpath_name .= $file_name;
			
			$this->lp_id = learnpath::add_lp($_course['id'], $learnpath_name,'','guess','manual');
			$previous = 0;
			$i = 0;
			$first_item = 0;
			foreach($files as $file){
				$i++;
				
				
				// add the png to documents
				$document_id = add_document($_course,$created_dir.'/'.$file,'file',filesize($base_work_dir.$created_dir.'/'.$file),$file);
				api_item_property_update($_course,TOOL_DOCUMENT,$document_id,'DocumentAdded',$_SESSION['_uid'],$to_group_id,$to_user_id);
				
				
				// create an html file
				$html_file = $file.'.html';
				$fp = fopen($base_work_dir.$created_dir.'/'.$html_file, 'w+');
				
				fwrite($fp,
						'<html>
						<head></head>
						<body>
							<img src="'.$_configuration['url_append'].'/courses/'.$_course['path'].'/document'.$created_dir.'/'.$file.'" />
						</body>
						</html>');
				fclose($fp);
				$document_id = add_document($_course,$created_dir.'/'.$html_file,'file',filesize($base_work_dir.$created_dir.'/'.$html_file),$html_file);
				if ($document_id){	
								
					//put the document in item_property update
					api_item_property_update($_course,TOOL_DOCUMENT,$document_id,'DocumentAdded',$_SESSION['_uid'],$to_group_id,$to_user_id);
					
					$infos = pathinfo($file);
					$slide_name = 'slide'.str_repeat('0',2-strlen($i)).$i;
					$previous = learnpath::add_item(0, $previous, 'document', $document_id, $slide_name, '');
					if($first_item == 0){
						$first_item = $previous;
					}
				}
			}
	    }
	    return $first_item;   	
	    
    }
		
}
?>
