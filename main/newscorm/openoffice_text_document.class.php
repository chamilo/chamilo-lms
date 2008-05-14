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

class OpenOfficeTextDocument extends OpenofficeDocument {
	

	public $split_steps;
	
	/**
	 * Class constructor. Calls the parent class and initialises the local attribute split_steps
	 * @param	boolean	Whether to split steps (true) or make one large page (false)
	 * @param	string	Course code
	 * @param	integer	Resource ID
	 * @param	integer Creator user id
	 * @return	void
	 */
	function OpenofficeTextDocument($split_steps=false, $course_code=null, $resource_id=null,$user_id=null)
	{
		
		$this -> split_steps = $split_steps;
		parent::OpenofficeDocument($course_code, $resource_id, $user_id);
		
	}
    
    /**
     * Gets html pages and compose them into a learning path
     * @param	array	The files that will compose the generated learning path. Unused so far.
     * @return	boolean	False if file does not exit. Nothing otherwise.
     */
    function make_lp($files=array())
    {
    	
		global $_course;
		// we get a content where ||page_break|| indicates where the page is broken
		if(!file_exists($this->base_work_dir.'/'.$this->created_dir.'/'.$this->file_name.'.html')){return false;}
		$content = file_get_contents($this->base_work_dir.'/'.$this->created_dir.'/'.$this->file_name.'.html');	
		
		
		unlink($this->base_work_dir.'/'.$this->file_path);
		unlink($this->base_work_dir.'/'.$this->created_dir.'/'.$this->file_name.'.html');
		
		// set the charset if necessary
		$charset = api_get_setting('platform_charset');
		if(strcasecmp($charset,'utf-8')!==0)
		{
			$content = utf8_decode($content);
			$content = str_replace('utf-8',$charset,$content);
		}	
		
		// set the path to pictures to absolute (so that it can be modified in fckeditor)
		$content = preg_replace("|src=\"([^\"]*)|i", "src=\"".api_get_path(REL_COURSE_PATH).$_course['path'].'/document'.$this->created_dir."/\\1", $content);
		
		list($header, $body) = explode('<BODY',$content);

		$body = '<BODY'.$body;		

		// remove font-family styles
		$header = preg_replace("|font\-family[^;]*;|i", "",$header);
		
		// dokeos styles
		$my_style = api_get_setting('stylesheets');
		if(empty($my_style)){$my_style = 'dokeos_classic';}
		$style_to_import = "<style type=\"text/css\">\r\n";
		$style_to_import .= '@import "'.api_get_path(WEB_CODE_PATH).'css/'.$my_style.'/default.css";'."\n";
		$style_to_import .= '@import "'.api_get_path(WEB_CODE_PATH).'css/'.$my_style.'/course.css";'."\n";
		$style_to_import .= "</style>\r\n";		
		$header = preg_replace("|</head>|i", "\r\n$style_to_import\r\n\\0",$header);
		
		// line break before and after picture
		$header = str_replace('p {','p {clear:both;',$header);
		
		$header = str_replace('absolute','relative',$header);
		
		switch($this->split_steps)
		{
			case 'per_page' : $this -> dealPerPage($header,$body); break;
			case 'per_chapter' : $this -> dealPerChapter($header,$body); break;
		}
		
    }
    
    /**
     * Manages chapter splitting
     * @param	string	Chapter header
     * @param	string	Content
     * @return	void
     */
    function dealPerChapter($header, $content)
    {
		
		global $_course;
		
		$content = str_replace('||page_break||','',$content);
		
		// get all the h1
		preg_match_all("|<h1[^>]*>([^(h1)+]*)</h1>|is",$content,$matches_temp);
		
		
		//empty the fake chapters
		$new_index = 0;
		for ($i=0 ; $i<count($matches_temp[0]) ; $i++) {

			if(trim($matches_temp[1][$i])!=='')
			{
				$matches[0][$new_index] = $matches_temp[0][$i];
				$matches[1][$new_index] = $matches_temp[1][$i];
				$new_index++;
			}
			
		}
		
		// add intro item
		$intro_content = substr($content, 0, strpos($content, $matches[0][0]));
		$items_to_create[get_lang('Introduction')] = $intro_content;

		
		for ($i=0 ; $i<count($matches[0]) ; $i++) {
			
			if(empty($matches[1][$i]))
				continue;
			
			$content = strstr($content,$matches[0][$i]);
			if($i+1!==count($matches[0]))
			{
				$chapter_content = substr($content, 0, strpos($content, $matches[0][$i+1]));
			}
			else
			{
				$chapter_content = $content;
			}
			$items_to_create[$matches[1][$i]] = $chapter_content;
			
		}
		
		$i = 0;
		foreach($items_to_create as $item_title=>$item_content)
		{
			$i++;
			$page_content = $this->format_page_content($header, $item_content);
			
			$html_file = $this->created_dir.'-'.$i.'.html';
			$handle = fopen($this->base_work_dir.$this->created_dir.'/'.$html_file,'w+');
			fwrite($handle, $page_content);
			fclose($handle);
			
			$document_id = add_document($_course,$this->created_dir.'/'.$html_file,'file',filesize($this->base_work_dir.$this->created_dir.'/'.$html_file),$html_file);
		
			if ($document_id){	
							
				//put the document in item_property update
				api_item_property_update($_course,TOOL_DOCUMENT,$document_id,'DocumentAdded',$_SESSION['_uid'],0,0);
				
				$infos = pathinfo($this->filepath);
				$slide_name = strip_tags(nl2br($item_title));
				$slide_name = str_replace(array("\r\n", "\r", "\n"), "", $slide_name);
				$slide_name = html_entity_decode($slide_name);
				$previous = learnpath::add_item(0, $previous, 'document', $document_id, $slide_name, '');
				if($this->first_item == 0){
					$this->first_item = $previous;
				}
			}
		}
		
	}
    
    /**
     * Manages page splitting
     * @param	string	Page header
     * @param	string	Page body
     * @return	void
     */
    function dealPerPage($header,$body)
    {
		global $_course;
		// split document to pages
		$pages = explode('||page_break||',$body);		
		
		$first_item = 0;
		
		foreach($pages as $key=>$page_content)
		{ // for every pages, we create a new file
			
			$key +=1;
			
			$page_content = $this->format_page_content($header, $page_content, $this->base_work_dir.$this->created_dir);
			$html_file = $this->created_dir.'-'.$key.'.html';
			$handle = fopen($this->base_work_dir.$this->created_dir.'/'.$html_file,'w+');
			fwrite($handle, $page_content);
			fclose($handle);
			
			$document_id = add_document($_course,$this->created_dir.$html_file,'file',filesize($this->base_work_dir.$this->created_dir.$html_file),$html_file);
		
			if ($document_id){	
							
				//put the document in item_property update
				api_item_property_update($_course,TOOL_DOCUMENT,$document_id,'DocumentAdded',$_SESSION['_uid'],0,0);
				
				$infos = pathinfo($this->filepath);
				$slide_name = 'Page '.str_repeat('0',2-strlen($key)).$key;
				$previous = learnpath::add_item(0, $previous, 'document', $document_id, $slide_name, '');
				if($this->first_item == 0){
					$this->first_item = $previous;
				}
			}
		}
			
    }
    
    /**
     * Returns additional Java command parameters
     * @return	string	The additional parameters to be used in the Java call
     */
    function add_command_parameters(){
    	return ' -d woogie "'.$this->base_work_dir.'/'.$this->file_path.'"  "'.$this->base_work_dir.$this->created_dir.'/'.$this->file_name.'.html"';
    }
    
    /**
     * Formats a page content by reorganising the HTML code a little
     * @param	string	Page header
     * @param	string	Page content
     * @return	string	Formatted page content 
     */
    function format_page_content($header, $content)
    {
    	
		
		// limit the width of the doc
		list($max_width, $max_height) = explode('x',api_get_setting('service_ppt2lp','size'));
		
		$content = preg_replace("|<body[^>]*>|i","\\0\r\n<div style=\"width:".$max_width."\">",$content, -1,$count);
		if($count < 1)
		{
			$content = '<body><div style="width:'.$max_width.'">'.$content;
		}
		
		$content = preg_replace('|</body>|i','</div>\\0',$content, -1, $count);
		if($count < 1)
		{
			$content = $content.'</div></body>';
		}
		
		// add the headers
		$content = $header.$content;
		
		
		// resize all the picture to the max_width-10
		preg_match_all("|<img[^src]*src=\"([^\"]*)\"[^>]*>|i",$content,$images);
		
		foreach ($images[1] as $key => $image)
		{
			// check if the <img tag soon has a width attribute
			$defined_width = preg_match("|width=([^\s]*)|i",$images[0][$key], $img_width);
			$img_width = $img_width[1];
			if(!$defined_width)
			{
			
				list($img_width, $img_height, $type) = getimagesize($this->base_work_dir.$this->created_dir.'/'.$image);
				
				$new_width = $max_width-10;
				if($img_width > $new_width)
				{
					$picture_resized = str_ireplace('<img','<img width="'.$new_width.'" ',$images[0][$key]);
					$content = str_replace($images[0][$key],$picture_resized,$content);
				}
				
			}
			else if($img_width > $max_width-10)
			{
				$picture_resized = str_ireplace('width='.$img_width,'width="'.($max_width-10).'"',$images[0][$key]);
				$content = str_replace($images[0][$key],$picture_resized,$content);
			}
		}
		
		
    	return $content;
    	
    }
    /**
     * Add documents to the visioconference (to be implemented)
     */
    function add_docs_to_visio (){
    	  	
    }
}
?>