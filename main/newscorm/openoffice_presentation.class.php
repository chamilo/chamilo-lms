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

    
    function make_lp($files=array()) {
    
    	global $_course;
   
		$previous = 0;
		$i = 0;
		
		if(!is_dir($this->base_work_dir.$this->created_dir))
			return false;
		
		
		foreach($files as $file){
			
			list($slide_name,$file_name) = explode('||',$file); // '||' is used as separator between slide name (with accents) and file name (without accents)
			
			//filename is utf8 encoded, but when we decode, some chars are not translated (like quote &rsquo;).
			//so we remove these chars by translating it in htmlentities and the reconvert it in want charset
			$slide_name = htmlentities($slide_name,ENT_COMPAT,'utf-8'); 
			$slide_name = str_replace('&rsquo;','\'',$slide_name);
			$slide_name = mb_convert_encoding($slide_name, api_get_setting('platform_charset'), 'utf-8');
			$slide_name = html_entity_decode($slide_name);
			
			if($this->take_slide_name === true)
			{
				$slide_name = str_replace('_',' ',$slide_name);
				$slide_name = ucfirst($slide_name);
			}
			else
			{
				$slide_name = 'slide'.str_repeat('0',2-strlen($i)).$i;
			}
			
			$i++;	
			// add the png to documents
			$document_id = add_document($_course,$this->created_dir.'/'.urlencode($file_name),'file',filesize($this->base_work_dir.$this->created_dir.'/'.$file_name),$slide_name);
			api_item_property_update($_course,TOOL_DOCUMENT,$document_id,'DocumentAdded',$_SESSION['_uid'],0,0);
			
			
			// create an html file
			$html_file = $file_name.'.html';
			$fp = fopen($this->base_work_dir.$this->created_dir.'/'.$html_file, 'w+');
			
			fwrite($fp,
					'<html>
					<head></head>
					<body>
						<img src="'.api_get_path(REL_COURSE_PATH).$_course['path'].'/document/'.$this->created_dir.'/'.utf8_encode($file_name).'" />
					</body>
					</html>');
			fclose($fp);
			$document_id = add_document($_course,$this->created_dir.'/'.urlencode($html_file),'file',filesize($this->base_work_dir.$this->created_dir.'/'.$html_file),$slide_name);
			if ($document_id){	
							
				//put the document in item_property update
				api_item_property_update($_course,TOOL_DOCUMENT,$document_id,'DocumentAdded',$_SESSION['_uid'],0,0);
				
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
    	
		
		foreach($files as $file){
			
			list($slide_name,$file_name) = explode('||',$file); // '||' is used as separator between slide name (with accents) and file name (without accents)
			$slide_name = utf8_decode($slide_name); //filename has been written in java, so unicode
			
			$did = add_document($_course, $this->created_dir.'/'.urlencode($file_name), 'file', filesize($this->base_work_dir.$this->created_dir.'/'.$file_name), $slide_name);
			if ($did)
				api_item_property_update($_course, TOOL_DOCUMENT, $did, 'DocumentAdded', $_SESSION['_uid'], 0, NULL);
		
		}
		
    }
	    
		
}
?>
