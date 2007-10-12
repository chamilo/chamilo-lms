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

class word_document extends learnpath {

	/**
	 * Class constructor. Based on the parent constructor.
	 * @param	string	Course code
	 * @param	integer	Learnpath ID in DB
	 * @param	integer	User ID
	 */
    function word_document($course_code=null,$resource_id=null,$user_id=null) {
    	if($this->debug>0){error_log('In word_document::word_document()',0);}
    	if(!empty($course_code) and !empty($resource_id) and !empty($user_id))
    	{
    		parent::learnpath($course_code, $resource_id, $user_id);
    	}else{
    		//do nothing but still build the presentation object
    	}
    }
    
    function convert_word_document($file){
    	
    	global $_course, $_user, $_configuration;
    
    	$file_name = (strrpos($file['name'],'.')>0 ? substr($file['name'], 0, strrpos($file['name'],'.')) : $file['name']);
    	$file_extension = (strrpos($file['name'],'.')>0 ? substr($file['name'], strrpos($file['name'],'.'),10) : '');
    	
    	
    	$file_name = remove_accents($file_name);
		$file_name = replace_dangerous_char($file_name,'strict');	
		$file_name = strtolower($file_name);
		
		$file['name'] = $file_name.$file_extension;
		
		
		$dir_name = '/'.$file_name;
		
		
		// get properties of doc file
		$document_datas = DocumentManager::get_all_document_data($_course, $file);
		$to_group_id = (empty($document_datas['to_group_id'])) ? 0 : $document_datas['to_group_id'];
		$to_user_id = (empty($document_datas['to_user_id'])) ? null : $document_datas['to_user_id'];
	
		//create the directory
		
		$base_work_dir = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
		$created_dir = create_unexisting_directory($_course,$_user['user_id'],$to_group_id,$to_user_id,$base_work_dir,$dir_name);

		
		move_uploaded_file($file['tmp_name'],$base_work_dir.'/'.$file['name']);
		$file = $base_work_dir.'/'.$file['name'];

		$perm = api_get_setting('permissions_for_new_files');
		
		
		$classpath = '-cp .:ridl.jar:js.jar:juh.jar:jurt.jar:jut.jar:java_uno.jar:java_uno_accessbridge.jar:edtftpj-1.5.2.jar:unoil.jar:commons-cli-1.0.jar:commons-io-1.3.1.jar:jodconverter-2.2.0.jar:jodconverter-cli-2.2.0.jar';
		if(strpos($_ENV['OS'],'Windows') !== false)
		{
			$classpath = str_replace(':',';',$classpath);
		}
		if(strpos($_ENV['OS'],'Windows') !== false)
		{
			$cmd = 'cd '.str_replace('/','\\',api_get_path(SYS_PATH).'main/inc/lib/ppt2png ').$classpath.' DokeosConverter -p 2002 -d woogie "'.$file.'" "'.$base_work_dir.$created_dir.'/'.$file_name.'.html"';
		}
		else
		{
			$cmd = 'cd '.api_get_path(SYS_PATH).'main/inc/lib/ppt2png && java '.$classpath.' DokeosConverter -p 2002 -d woogie "'.$file.'" "'.$base_work_dir.$created_dir.'/'.$file_name.'.html"';
		}
		
		// to allow openoffice to manipulate docs.
		chmod ($base_work_dir.$created_dir,0777);
		chmod($file,0777);

		$shell = exec($cmd, $files, $return);
		
		if($return != 0) { //if the java application returns an error code
		
			DocumentManager::delete_document($_course, $dir_name, $base_work_dir);	 
			return false;   	
	    }
	    else {
			// create lp
			$learnpath_name .= $file_name;
			
			$this->lp_id = learnpath::add_lp($_course['id'], $learnpath_name,'','guess','manual');
			$content = file_get_contents($base_work_dir.$created_dir.'/'.$file_name.'.html');
			
			
			// we get a content where ||page_break|| indicates where the page is broken

			list($header, $body) = explode('<BODY',$content);

			$body = '<BODY'.$body;
			
			
			$pages = explode('||page_break||',$body);
			
			$first_item = 0;
			
			foreach($pages as $key=>$page_content){ // for every pages, we create a new file
				
				$key +=1;
				
				$page_content = $this->format_page_content($header, $page_content);
				$html_file = $created_dir.'-'.$key.'.html';
				$handle = fopen($base_work_dir.$created_dir.'/'.$html_file,'w+');
				fwrite($handle, $page_content);
				fclose($handle);
				
				$document_id = add_document($_course,$created_dir.'/'.$html_file,'file',filesize($base_work_dir.$created_dir.'/'.$html_file),$html_file);
			
				if ($document_id){	
								
					//put the document in item_property update
					api_item_property_update($_course,TOOL_DOCUMENT,$document_id,'DocumentAdded',$_SESSION['_uid'],$to_group_id,$to_user_id);
					
					$infos = pathinfo($file);
					$slide_name = 'page'.str_repeat('0',2-strlen($key)).$key;
					$previous = learnpath::add_item(0, $previous, 'document', $document_id, $slide_name, '');
					if($first_item == 0){
						$first_item = $previous;
					}
				}
			}
			$perm = api_get_setting('permissions_for_new_files');
			$perm = octdec(!empty($perm)?$perm:0770);
			chmod($file,$perm);
			
	    }
	    $perm = api_get_setting('permissions_for_new_directories');
		$perm = octdec(!empty($perm)?$perm:0770);
		chmod ($base_work_dir.$created_dir,$perm);
	    return $first_item;   	
	    
    }
    
    
    function format_page_content($header, $content)
    {
    	
		// Tidy
		$tidy = new tidy;
		$config = array(
		       'indent'         => true,
		       'output-xhtml'   => true,
		       'wrap'           => 200);
		$tidy->parseString($header.$content, $config, 'utf8');		
		$tidy->cleanRepair();
		$content = $tidy;
		
		// limit the width of the doc
		$max_width = '720px';
		$content = str_replace('<body>','<body><div style="width:'.$max_width.'">',$content);
		$content = str_replace('</body>','</div></body>',$content);
		
		// line break before and after picture
		$content = str_replace('p {','p {clear:both;',strtolower($content));
		
		// dokeos styles
		$my_style = api_get_setting('stylesheets');
		if(empty($my_style)){$my_style = 'default';}
		$style_to_import = '@import "'.api_get_path(WEB_CODE_PATH).'css/'.$my_style.'/default.css";'."\n";
		$style_to_import .= '@import "'.api_get_path(WEB_CODE_PATH).'css/'.$my_style.'/course.css";'."\n";
		
		$content = str_replace('/*<![cdata[*/','/*<![cdata[*/ '.$style_to_import,$content);
		
		
    	return $content;
    	
    }
		
}
?>
