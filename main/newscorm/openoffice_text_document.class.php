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
    	
		// Tidy
		$tidy = new tidy;
		$config = array(
		       'indent'         => true,
		       'output-xhtml'   => true,
		       'wrap'           => 200,
		       'clean'           => true,
		       'bare'			=> true);
		$tidy->parseString($header.$content, $config, 'utf8');		
		$tidy->cleanRepair();
		$content = $tidy;
		
		// limit the width of the doc
		$max_width = '720px';
		$content = preg_replace("|<body[^>]*>|","\\0\r\n<div style=\"width:".$max_width."\">",$content);
		$content = str_replace('</body>','</div></body>',$content);
		
		// line break before and after picture
		$content = str_replace('p {','p {clear:both;',strtolower($content));
		
		// dokeos styles
		$my_style = api_get_setting('stylesheets');
		if(empty($my_style)){$my_style = 'default';}
		$style_to_import = '@import "'.api_get_path(WEB_CODE_PATH).'css/'.$my_style.'/default.css";'."\n";
		$style_to_import .= '@import "'.api_get_path(WEB_CODE_PATH).'css/'.$my_style.'/course.css";'."\n";
		
		$content = str_replace('/*<![cdata[*/','/*<![cdata[*/ '.$style_to_import,$content);
		
		$content = str_replace('absolute','relative',$content);
		
		/*
		// resize all the picture to the max_width-10
		preg_match_all("|<img src=\"([^\"]*)\"|",strtolower($content),$images);
		
		foreach ($images[1] as $image)
		{
			list($img_width, $img_height, $type) = getimagesize($path_to_folder.'/'.$image);
			
			$new_width = $max_width-10;
			if($img_width > $new_width)
			{
				$new_height = round($new_width/$img_width*$img_height);
				
				include_once (api_get_path(LIBRARY_PATH).'image.lib.php');
				$src = imagecreatefromgif($path_to_folder.'/'.$image);  
				$dstImg = imagecreatetruecolor($new_width, $new_height);  
				 
				$white = imagecolorallocate($dstImg, 255, 255, 255);  
				 
				imagefill($dstImg, 0, 0, $white);  
				imageColorTransparent($dstImg, $white);  
				imagecopyresampled($dstImg, $src, 0, 0, 0, 0, $new_width, $new_height, $img_width, $img_height);  
				 
				imagegif($dstImg,$path_to_folder.'/2'.$image);
			}
		}
		*/
		
    	return $content;
    	
    }
   
		
}
?>
