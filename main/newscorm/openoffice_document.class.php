<?php //$id:$
/**
 * Defines the OpenofficeDocument class, which is meant as a mother class
 * to help in the conversion of Office documents to learning paths
 * @package dokeos.learnpath
 * @author	Eric Marguin <eric.marguin@dokeos.com>
 * @license	GNU/GPL - See Dokeos license directory for details
 */
/**
 * Defines the "OpenofficeDocument" child of class "learnpath"
 * @package dokeos.learnpath.aicc
 */

abstract class OpenofficeDocument extends learnpath {
	

	public $first_item = 0;
    public $original_charset = 'utf-8';
    public $original_locale = 'en_US.UTF-8';

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

		$visio_dir = ($action_after_conversion=='add_docs_to_visio')?VIDEOCONF_UPLOAD_PATH:'';
		
		$this->file_path = $visio_dir.'/'.$this->file_name.'.'.pathinfo($file['name'],PATHINFO_EXTENSION);

		$dir_name = $visio_dir.'/'.$this->file_name;
		
	
		//create the directory		
		$this->base_work_dir = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
		
		
		$this->created_dir = create_unexisting_directory($_course,$_user['user_id'],0,0,$this->base_work_dir,$dir_name);

		move_uploaded_file($file['tmp_name'],$this->base_work_dir.'/'.$this->file_path);


		$perm = api_get_setting('permissions_for_new_files');
		
		/*
		$classpath = '-cp .:jodconverter-2.2.1.jar:jodconverter-cli-2.2.1.jar';
		if(isset($_ENV['OS']) && strpos($_ENV['OS'],'Windows') !== false)
		{
			$classpath = str_replace(':',';',$classpath);
		}
		if(isset($_ENV['OS']) && strpos($_ENV['OS'],'Windows') !== false)
		{
			$cmd = 'cd '.str_replace('/','\\',api_get_path(SYS_PATH).'main/inc/lib/ppt2png ').$classpath.' DokeosConverter';
		}
		else
		{
			$cmd = 'cd '.api_get_path(SYS_PATH).'main/inc/lib/ppt2png && java '.$classpath.' DokeosConverter';
		}
		$cmd .=  ' -p '.api_get_setting('service_ppt2lp','port');
		*/
		if (IS_WINDOWS_OS) // IS_WINDOWS_OS has been defined in main_api.lib.php
		{
			$converter_path = str_replace('/','\\',api_get_path(SYS_PATH).'main/inc/lib/ppt2png'); 
			$class_path = $converter_path.';'.$converter_path.'/jodconverter-2.2.1.jar;'.$converter_path.'/jodconverter-cli-2.2.1.jar'; 

			//$cmd = 'java -cp "'.$class_path.'" DokeosConverter';
			$cmd = 'java -Dfile.encoding=UTF-8 -cp "'.$class_path.'" DokeosConverter';
		}
		else
		{
			$converter_path = api_get_path(SYS_PATH).'main/inc/lib/ppt2png';

			//$class_path = '-cp .:jodconverter-2.2.1.jar:jodconverter-cli-2.2.1.jar';
			$class_path = ' -Dfile.encoding=UTF-8 -cp .:jodconverter-2.2.1.jar:jodconverter-cli-2.2.1.jar';

			$cmd = 'cd '.$converter_path.' && java '.$class_path.' DokeosConverter';
		}
		$cmd .=  ' -p '.api_get_setting('service_ppt2lp','port');		
		
		// call to the function implemented by child
		$cmd .= $this -> add_command_parameters();	

		// to allow openoffice to manipulate docs.
		@chmod ($this->base_work_dir.$this->created_dir,0777);
		@chmod ($this->base_work_dir.$this->file_path,0777);
		
		$locale = $this->original_locale; // TODO : improve it because we're not sure this locale is present everywhere
		putenv('LC_ALL='.$locale);
		$files = array(); $return = 0;
		$shell = exec($cmd, $files, $return);
		if($return != 0) { //if the java application returns an error code
			switch($return)
			{
				// can't connect to openoffice
				case 1 : $this->error = get_lang('CannotConnectToOpenOffice');break;
				
				// conversion failed in openoffice
				case 2 : $this->error = get_lang('OogieConversionFailed');break;
				
				// conversion can't be launch because command failed
				case 255 : $this->error = get_lang('OogieUnknownError');break;
			}
			
			DocumentManager::delete_document($_course, $dir_name, $this->base_work_dir);	
			return false;   
				
	    }
	    
		// create lp
		$this->lp_id = learnpath::add_lp($_course['id'], ucfirst(pathinfo($file['name'], PATHINFO_FILENAME)),'','guess','manual');
		
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
