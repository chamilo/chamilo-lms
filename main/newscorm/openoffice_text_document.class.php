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
	

    
    function make_lp($files=array()){
    	
		global $_course;
		
		$content = file_get_contents($this->base_work_dir.$this->created_dir.'/'.$this->file_name.'.html');
		
		
		// we get a content where ||page_break|| indicates where the page is broken

		list($header, $body) = explode('<BODY',$content);

		$body = '<BODY'.$body;
		
		
		$pages = explode('||page_break||',$body);
		
		$first_item = 0;
		
		foreach($pages as $key=>$page_content){ // for every pages, we create a new file
			
			$key +=1;
			
			$page_content = $this->format_page_content($header, $page_content, $this->base_work_dir.$this->created_dir);
			$html_file = $this->created_dir.'-'.$key.'.html';
			$handle = fopen($this->base_work_dir.$this->created_dir.'/'.$html_file,'w+');
			fwrite($handle, $page_content);
			fclose($handle);
			
			$document_id = add_document($_course,$this->created_dir.'/'.$html_file,'file',filesize($this->base_work_dir.$this->created_dir.'/'.$html_file),$html_file);
		
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
    
    
    function add_command_parameters(){
    	return ' -d woogie';
    }
    
    
    function format_page_content($header, $content, $path_to_folder)
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
		
		// set the charset if necessary
		$charset = api_get_setting('platform_charset');
		if(!strcasecmp($charset,'utf-8'))
		{
			$content = utf8_decode($content);
			$header = str_replace('utf-8','iso-8859-15',$header);
		}
		
		// add the headers
		$content = $header.$content;
		
		// line break before and after picture
		$content = str_replace('p {','p {clear:both;',$content);
		
		// dokeos styles
		$my_style = api_get_setting('stylesheets');
		if(empty($my_style)){$my_style = 'default';}
		$style_to_import = '@import "'.api_get_path(WEB_CODE_PATH).'css/'.$my_style.'/default.css";'."\n";
		$style_to_import .= '@import "'.api_get_path(WEB_CODE_PATH).'css/'.$my_style.'/course.css";'."\n";
		
		$content = str_replace('/*<![cdata[*/','/*<![cdata[*/ '.$style_to_import,$content);
		
		$content = str_replace('absolute','relative',$content);
		
		
		// resize all the picture to the max_width-10
		preg_match_all("|<img[^src]*src=\"([^\"]*)\"[^>]*>|i",$content,$images);
		
		foreach ($images[1] as $key => $image)
		{
			// check if the <img tag soon has a width attribute
			$defined_width = preg_match("|width=([^\s]*)|i",$images[0][$key], $img_width);
			$img_width = $img_width[1];
			if(!$defined_width)
			{
			
				list($img_width, $img_height, $type) = getimagesize($path_to_folder.'/'.$image);
				
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
   
		
}
?>
