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

abstract class OpenofficeDocument extends learnpath {
	

	public $first_item = 0;

	/**
	 * Class constructor. Based on the parent constructor.
	 * @param	string	Course code
	 * @param	integer	Learnpath ID in DB
	 * @param	integer	User ID
	 */
    function OpenofficeDocument($course_code=null,$resource_id=null,$user_id=null) {
    	if($this->debug>0){error_log('In OpenofficeDocument::OpenofficeDocument()',0);}
    	if(!empty($course_code) and !empty($resource_id) and !empty($user_id))
    	{
    		parent::learnpath($course_code, $resource_id, $user_id);
    	}else{
    		//do nothing but still build the presentation object
    	}
    }
    
    function convert_document($file, $action_after_conversion='make_lp'){
    	
    	global $_course, $_user, $_configuration;
    
    	$this->file_name = (strrpos($file['name'],'.')>0 ? substr($file['name'], 0, strrpos($file['name'],'.')) : $file['name']);
    	$this->file_name = remove_accents($this->file_name);
		$this->file_name = replace_dangerous_char($this->file_name,'strict');	
		$this->file_name = strtolower($this->file_name);
		
		$this->file_path = $this->file_name.'.'.pathinfo($file['name'],PATHINFO_EXTENSION);
		
		
		
		$dir_name = '/'.$this->file_name;
		
	
		//create the directory
		$this->base_work_dir = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
		
		$visio_dir = ($action_after_conversion=='add_docs_to_visio')?VIDEOCONF_UPLOAD_PATH:'';
		
		$this->created_dir = create_unexisting_directory($_course,$_user['user_id'],0,0,$this->base_work_dir,$visio_dir.$dir_name);

		
		move_uploaded_file($file['tmp_name'],$this->base_work_dir.'/'.$this->file_path);

		$perm = api_get_setting('permissions_for_new_files');
		
		
		$classpath = '-cp .:jodconverter-2.2.1.jar:jodconverter-cli-2.2.1.jar';
		if(strpos($_ENV['OS'],'Windows') !== false)
		{
			$classpath = str_replace(':',';',$classpath);
		}
		if(strpos($_ENV['OS'],'Windows') !== false)
		{
			$cmd = 'cd '.str_replace('/','\\',api_get_path(SYS_PATH).'main/inc/lib/ppt2png ').$classpath.' DokeosConverter';
		}
		else
		{
			$cmd = 'cd '.api_get_path(SYS_PATH).'main/inc/lib/ppt2png && java '.$classpath.' DokeosConverter';
		}
		$cmd .=  ' -p '.api_get_setting('service_ppt2lp','port');		
		
		// call to the function implemented by child
		$cmd .= $this -> add_command_parameters();	

		// to allow openoffice to manipulate docs.
		chmod ($this->base_work_dir.$this->created_dir,0777);
		chmod($this->base_work_dir.'/'.$this->file_path,0777);
		
		$locale = 'fr_FR.UTF-8';
		putenv('LC_ALL='.$locale);
		$shell = exec($cmd, $files, $return);
		
		if($return != 0) { //if the java application returns an error code
			DocumentManager::delete_document($_course, $dir_name, $this->base_work_dir);	 
			return false;   
				
	    }
	    
		// create lp
		$this->lp_id = learnpath::add_lp($_course['id'], $this->file_name,'','guess','manual');
		
		// call to the function implemented by child following action_after_conversion parameter
		switch ($action_after_conversion)
		{
			case 'make_lp':$this -> make_lp($files);	
			break;		
			case 'add_docs_to_visio':$this -> add_docs_to_visio($files);	
			break;	
		}
				
	    $perm = api_get_setting('permissions_for_new_directories');
		$perm = octdec(!empty($perm)?$perm:0770);
		chmod ($this->base_work_dir.$this->created_dir,$perm);
	    return $this->first_item;   	
	    
    }

    
    abstract function make_lp();
    abstract function add_docs_to_visio();
    abstract function add_command_parameters();
   
		
}
?>
