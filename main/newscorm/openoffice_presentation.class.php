<?php //$id:$
/**
 * Defines the OpenOfficeDocument class, which is meant as a conversion
 * tool from Office presentations (.ppt, .sxi, .odp, .pptx) to
 * learning paths
 * @package dokeos.learnpath
 * @author  Eric Marguin <eric.marguin@dokeos.com>
 * @license GNU/GPL - See Dokeos license directory for details
 */
/**
 * Defines the "OpenofficePresentation" child of class "OpenofficeDocument"
 * @package dokeos.learnpath.openofficedocument
 */
require_once('openoffice_document.class.php');
require_once(api_get_path(LIBRARY_PATH).'search/DokeosIndexer.class.php');
require_once(api_get_path(LIBRARY_PATH).'search/IndexableChunk.class.php');

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
			
			list($slide_name,$file_name,$slide_body) = explode('||',$file); // '||' is used as separator between fields: slide name (with accents) || file name (without accents) || all slide text (to be indexed)
			
			//filename is utf8 encoded, but when we decode, some chars are not translated (like quote &rsquo;).
			//so we remove these chars by translating it in htmlentities and the reconvert it in want charset
			$slide_name = htmlentities($slide_name,ENT_COMPAT,$this->original_charset); 
			$slide_name = str_replace('&rsquo;','\'',$slide_name);
			$slide_name = mb_convert_encoding($slide_name, api_get_setting('platform_charset'), $this->original_charset);
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
			api_item_property_update($_course,TOOL_DOCUMENT,$document_id,'DocumentAdded',api_get_user_id(),0,0);

            // Generating the thumbnail
            $image = $this->base_work_dir.$this->created_dir .'/'. $file_name;
            // calculate thumbnail size
            list($width, $height) = getimagesize($image);
            $thumb_width = 200;
            $thumb_height = floor( $height * ($thumb_width / $width ) );
            // load
            $thumb = imagecreatetruecolor($thumb_width, $thumb_height);
            $source = imagecreatefrompng($image);
            // resize
            imagecopyresized($thumb, $source, 0, 0, 0, 0, $thumb_width, $thumb_height, $width, $height);
            // output
            $pattern = '/(\w+)\.png$/';
            $replacement = '${1}_thumb.png';
            $thumb_name = preg_replace($pattern, $replacement, $file_name);
            imagepng($thumb, $this->base_work_dir.$this->created_dir .'/'. $thumb_name);
            // adding the thumbnail to documents
            $document_id_thumb = add_document($_course, $this->created_dir.'/'.urlencode($thumb_name), 'file', filesize($this->base_work_dir.$this->created_dir.'/'.$thumb_name), $slide_name);
            api_item_property_update($_course, TOOL_THUMBNAIL, $document_id_thumb,'DocumentAdded',api_get_user_id(),0,0);
			
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
				api_item_property_update($_course,TOOL_DOCUMENT,$document_id,'DocumentAdded',api_get_user_id(),0,0);
				
				$previous = learnpath::add_item(0, $previous, 'document', $document_id, $slide_name, '');
				if($this->first_item == 0){
					$this->first_item = $previous;
				}
			}
            // code for text indexing
            if (isset($_POST['index_document']) && $_POST['index_document']) {
              //Display::display_normal_message(print_r($_POST));
              $di = new DokeosIndexer();
              isset($_POST['language'])? $lang=Database::escape_string($_POST['language']): $lang = 'english';
              $di->connectDb(NULL, NULL, $lang);
              $ic_slide = new IndexableChunk();
              $ic_slide->addValue("title", $slide_name);
              if (isset($_POST['terms'])) {
                foreach (explode(',', Database::escape_string($_POST['terms'])) as $term){
                  $ic_slide->addTerm(trim($term),'T');
                }
              }
              $ic_slide->addValue("content", $slide_body);
              /* FIXME:  cidReq:lp_id:doc_id al indexar  */
              //       add a comment to say terms separated by commas
              $courseid=api_get_course_id();
              $ic_slide->addTerm($courseid,'C');
              //TODO: add dokeos tool type instead of filetype
              $lp_id = $this->lp_id;
              // TODO: get "path" field
              $ic_slide->addValue('ids', $courseid .':'. $this->lp_id
              .':'.$document_id );
              $di->addChunk($ic_slide);
              //index and return search engine document id
              $did = $di->index();
              if ($did) {
                // save it to db
                $tbl_lp_item = Database::get_course_table('lp_item');
                $sql_update = "
                  UPDATE " . $tbl_lp_item . "
                  SET search_did = " . $did . "
                  WHERE id = " . $previous;
                api_sql_query($sql_update, __FILE__, __LINE__);
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
			$slide_name = htmlentities($slide_name,ENT_COMPAT,$this->original_charset); 
			$slide_name = str_replace('&rsquo;','\'',$slide_name);
			$slide_name = mb_convert_encoding($slide_name, api_get_setting('platform_charset'), $this->original_charset);
			$slide_name = html_entity_decode($slide_name);
			
			$did = add_document($_course, $this->created_dir.'/'.urlencode($file_name), 'file', filesize($this->base_work_dir.$this->created_dir.'/'.$file_name), $slide_name);
			if ($did)
				api_item_property_update($_course, TOOL_DOCUMENT, $did, 'DocumentAdded', $_SESSION['_uid'], 0, NULL);
		
		}
		
    }
	    
		
}
?>
