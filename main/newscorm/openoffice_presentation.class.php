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
require_once('openoffice_document.class.php');

class OpenofficePresentation extends OpenofficeDocument {
	
	public $take_slide_name;
	
	function OpenofficePresentation($take_slide_name=false, $course_code=null, $resource_id=null,$user_id=null) {
		
		$this -> take_slide_name = $take_slide_name;
		parent::OpenofficeDocument($course_code, $resource_id, $user_id);
		
	}

    
    function make_lp() {
    
    	global $_course;
   
		$previous = 0;
		$i = 0;
		
		if(!is_dir($this->base_work_dir.$this->created_dir))
			return false;
		
		$files = scandir($this->base_work_dir.$this->created_dir);
		
		foreach($files as $file){
			
		
			if($file=='.' || $file=='..')
				continue;
				
			$i++;	
			$file = utf8_decode($file); //filename has been written in java, so unicode
			// add the png to documents
			$document_id = add_document($_course,$this->created_dir.'/'.urlencode($file),'file',filesize($this->base_work_dir.$this->created_dir.'/'.$file),$file);
			api_item_property_update($_course,TOOL_DOCUMENT,$document_id,'DocumentAdded',$_SESSION['_uid'],0,0);
			
			
			// create an html file
			$html_file = $file.'.html';
			$fp = fopen($this->base_work_dir.$this->created_dir.'/'.$html_file, 'w+');
			
			fwrite($fp,
					'<html>
					<head></head>
					<body>
						<img src="'.api_get_path(REL_COURSE_PATH).$_course['path'].'/document/'.$this->created_dir.'/'.utf8_encode($file).'" />
					</body>
					</html>');
			fclose($fp);
			$document_id = add_document($_course,$this->created_dir.'/'.urlencode($html_file),'file',filesize($this->base_work_dir.$this->created_dir.'/'.$html_file),$html_file);
			if ($document_id){	
							
				//put the document in item_property update
				api_item_property_update($_course,TOOL_DOCUMENT,$document_id,'DocumentAdded',$_SESSION['_uid'],0,0);
				
				$infos = pathinfo($file);
				if($this->take_slide_name === true)
				{
					$slide_name = substr($infos['basename'],0,strrpos($infos['basename'],'.'));
					$slide_name = str_replace('_',' ',$slide_name);
					$slide_name = ucfirst($slide_name);
				}
				else
				{
					$slide_name = 'slide'.str_repeat('0',2-strlen($i)).$i;
				}
				$previous = learnpath::add_item(0, $previous, 'document', $document_id, $slide_name, '');
				if($this->first_item == 0){
					$this->first_item = $previous;
				}
			}
		}
    }
    
    function add_command_parameters(){
    	
    	if(empty($this->slide_width) || empty($this->slide_height))
    		list($this->slide_width, $this->slide_height) = explode('x',api_get_setting('service_ppt2lp','size'));
    	return ' -w '.$this->slide_width.' -h '.$this->slide_height.' -d oogie "'.$this->base_work_dir.'/'.$this->file_path.'"  "'.$this->base_work_dir.$this->created_dir.'.html"';
    
    }
    
    function set_slide_size($width,$height)
    {
    	$this->slide_width = $width;
    	$this->slide_height = $height;
    }
    
    function add_docs_to_visio ($files=array()){
    	
    	global $_course;
    	/* Add Files */
    	
		$files = scandir($this->base_work_dir.$this->created_dir);
		
		foreach($files as $file){
			
			if($file=='.' || $file=='..')
				continue;
				
			$file = utf8_decode($file);
			
			$did = add_document($_course, $this->created_dir.'/'.urlencode($file), 'file', filesize($this->base_work_dir.$this->created_dir.'/'.$file), $file);
			if ($did)
				api_item_property_update($_course, TOOL_DOCUMENT, $did, 'DocumentAdded', $_SESSION['_uid'], 0, NULL);
		
		}
		
    }
	    
		
}
?>
