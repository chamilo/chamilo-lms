<?php
/* See license terms in /license.txt */
/**
*   @package chamilo.library
*/
/**
 * Code
 */
define('_MPDF_PATH', api_get_path(LIBRARY_PATH).'mpdf/');
require_once _MPDF_PATH.'mpdf.php';
/**
*   @package chamilo.library
*/
class PDF {
    
    var $pdf;    
    var $custom_header = '';
    var $custom_footer = '';    
    
    /**
     * Creates the mPDF object
     * @param   string  format A4 A4-L see  http://mpdf1.com/manual/index.php?tid=184&searchstring=format
     * @param   string  orientation "P" = Portrait "L" = Landscape
     */
    public function __construct($page_format ='A4', $orientation = 'P') {
        /* More info @ http://mpdf1.com/manual/index.php?tid=184&searchstring=mPDF
         * mPDF ([ string $mode [, mixed $format [, float $default_font_size [, string $default_font [, float $margin_left , float $margin_right , float $margin_top , float $margin_bottom , float $margin_header , float $margin_footer [, string $orientation ]]]]]])
         */         
        if(!in_array($orientation,array('P','L'))) {
            $orientation = 'P';
        }
        $this->pdf = $pdf = new mPDF('UTF-8', $page_format, '', '', 30, 20, 27, 25, 16, 13, $orientation); 
    } 
    
    /**
     * Converts an html file to PDF
     * @param   mixed   could be an html file path or an array with paths example: /var/www/myfile.html or array('/myfile.html','myotherfile.html') or even an indexed array with both 'title' and 'path' indexes for each element like array(0=>array('title'=>'Hello','path'=>'file.html'),1=>array('title'=>'Bye','path'=>'file2.html'));
     * @param   string  pdf name   
     * @param   string  course code (if you are using html that are located in the document tool you must provide this) 
     * @return  
     */    
    public function html_to_pdf($html_file_array, $pdf_name = '', $course_code = null, $print_title = false) {
        
        if (empty($html_file_array)) {
        	return false;
        }  
              
        if (is_array($html_file_array)) {
            if (count($html_file_array) == 0)
        	   return false;
        } else {
            if (!file_exists($html_file_array)) {
                return false;
            }
            //Converting the string into an array
            $html_file_array = array($html_file_array);
        }
        
        $course_data = array();
        if (!empty($course_code)) {
        	$course_data = api_get_course_info($course_code);
        }
        
        
        //clean styles and javascript document
        $clean_search = array (
            '@<script[^>]*?>.*?</script>@si',
            '@<style[^>]*?>.*?</style>@si'
            );
            
        //Formatting the pdf
        self::format_pdf($course_data);
               
        foreach ($html_file_array as $file) {
            $html_title = '';
            //if the array provided contained subarrays with 'title' entry,
            // then print the title in the PDF
            if (is_array($file) && isset($file['title'])) {
            	$html_title = $file['title'];
                $file  = $file['path'];
            } else {
                //we suppose we've only been sent a file path
            	$html_title = basename($file);
            }
            if (empty($file) && !empty($html_title)) {
            	//this is a chapter, print title & skip the rest
                if ($print_title) {
                    $this->pdf->WriteHTML('<html><body><h3>'.$html_title.'</h3></body></html>',2);
                }
                continue;
            } 
          
            if (!file_exists($file)) {
                //the file doesn't exist, skip
            	continue;
            }
       
            //it's not a chapter but the file exists, print its title
            if ($print_title) {
                $this->pdf->WriteHTML('<html><body><h3>'.$html_title.'</h3></body></html>',2);
            }
            
            $file_info = pathinfo($file);
            $extension = $file_info['extension'];
            
            if (in_array($extension, array('html', 'htm'))) {
            
                $dirname = str_replace("\\", '/', $file_info['dirname']);
                $filename = $file_info['basename'];
                $filename = str_replace('_',' ',$filename);
                
                if ($extension == 'html') {
                    $filename =basename($filename,'.html');
                } elseif($extension == 'htm'){
                    $filename =basename($filename,'.htm');
                }          
                
                $document_html = @file_get_contents($file);
                $document_html = preg_replace($clean_search, '', $document_html);   
                
                //absolute path for frames.css //TODO: necessary?
                $absolute_css_path = api_get_path(WEB_CODE_PATH).'css/'.api_get_setting('stylesheets').'/frames.css';
                $document_html = str_replace('href="./css/frames.css"',$absolute_css_path, $document_html);
                
                //$document_html=str_replace('<link rel="stylesheet" http://my.chamilo.net/main/css/chamilo/frames.css type="text/css" />','', $document_html);
                
                if (!empty($course_data['path'])) {
                    $document_html= str_replace('../','',$document_html);            
                    $document_path = api_get_path(WEB_COURSE_PATH).$course_data['path'].'/document/';
                               
                    $doc = new DOMDocument();           
                    $result = @$doc->loadHTML($document_html);
                                         
                    //Fixing only images @todo do the same thing with other elements
                    $elements = $doc->getElementsByTagName('img');
                    $replace_img_elements = array();
                    if (!empty($elements)) {
                        foreach($elements as $item) {                    
                            $old_src = $item->getAttribute('src');
                            //$old_src= str_replace('../','',$old_src);
                            if (strpos($old_src, 'http') === false) {
                                if (strpos($old_src, '/main/default_course_document') === false) {
                                    $document_html= str_replace($old_src, $document_path.$old_src, $document_html);                            
                                }                                                       	
                            }                                    
                        }
                    }
                }
                api_set_encoding_html($document_html, 'UTF-8'); // The library mPDF expects UTF-8 encoded input data.        
                $title = api_get_title_html($document_html, 'UTF-8', 'UTF-8');  // TODO: Maybe it is better idea the title to be passed through
                                                                                // $_GET[] too, as it is done with file name.
                                                                                // At the moment the title is retrieved from the html document itself.
                if (empty($title)) {
                    $title = $filename; // Here file name is expected to contain ASCII symbols only.
                }
                
                if (!empty($document_html)) {
                    $this->pdf->WriteHTML($document_html,2);
                }
            } elseif (in_array($extension, array('jpg','jpeg','png','gif'))) {
                //Images
                $image = Display::img($file);
                $this->pdf->WriteHTML('<html><body>'.$image.'</body></html>',2);
            } 
        }
        
        if (empty($pdf_name)) {
            $output_file = 'pdf_'.date('Y-m-d-his').'.pdf';
        } else {
            $pdf_name = replace_dangerous_char($pdf_name);
        	$output_file = $pdf_name.'.pdf';
        }
        $result = $this->pdf->Output($output_file, 'D');       /// F to save the pdf in a file              
        exit;
    }    
    
    
     
    /**
     * Converts an html string to PDF
     * @param   string  valid html 
     * @param   string  pdf name   
     * @param   string  course code (if you are using html that are located in the document tool you must provide this) 
     * @return  
     */    
    public function content_to_pdf($document_html, $css = '', $pdf_name = '', $course_code = null) {
        
        if (empty($document_html)) {
            return false;
        }

        //clean styles and javascript document
        $clean_search = array (
            '@<script[^>]*?>.*?</script>@si',
            '@<style[^>]*?>.*?</style>@siU'
            );
            
        //Formatting the pdf
        self::format_pdf($course_code);  
        
        if (!empty($course_code)) {
            $course_data = api_get_course_info($course_code);
        }                    
        
        $document_html = preg_replace($clean_search, '', $document_html);   
        
        //absolute path for frames.css //TODO: necessary?
        $absolute_css_path=api_get_path(WEB_CODE_PATH).'css/'.api_get_setting('stylesheets').'/frames.css';
        $document_html=str_replace('href="./css/frames.css"',$absolute_css_path, $document_html);
        
        //$document_html=str_replace('<link rel="stylesheet" http://my.chamilo.net/main/css/chamilo/frames.css type="text/css" />','', $document_html);
    
        $document_html= str_replace('../../','',$document_html);
        $document_html= str_replace('../','',$document_html);        
        $document_html= str_replace('courses/'.$course_code.'/document/','',$document_html);
        
        if (!empty($course_data['path'])) {
            $document_path = api_get_path(WEB_COURSE_PATH).$course_data['path'].'/document/';
                       
            $doc = new DOMDocument();           
            $result = @$doc->loadHTML($document_html);
                                 
            //Fixing only images @todo do the same thing with other elements
            $elements = $doc->getElementsByTagName('img');
            $replace_img_elements = array();
            if (!empty($elements)) {
                foreach($elements as $item) {                    
                    $old_src = $item->getAttribute('src');
                    //$old_src= str_replace('../','',$old_src);
                    if (strpos($old_src, 'http') === false) {
                        if (strpos($old_src, '/main/default_course_document') === false) {
                            if (strpos($old_src, '/main/inc/lib/') === false) {                            
                                $document_html= str_replace($old_src, $document_path.$old_src, $document_html);         
                                //var_dump($old_src, $document_path.$old_src);     
                            }              
                        }                                                           
                    }                                    
                }
            }
        }
          
        //replace relative path by absolute path for resources
        //$document_html= str_replace('src="/chamilo/main/default_course_document/', 'temp_template_path', $document_html);// before save src templates not apply
        //$document_html= str_replace('src="/', 'temp_template_path', $document_html);// before save src templates not apply
        //$document_html= str_replace('src="/chamilo/main/default_course_document/', 'temp_template_path', $document_html);// before save src templates not apply
        
        //$src_http_www= 'src="'.api_get_path(WEB_COURSE_PATH).$course_data['path'].'/document/';
        //$document_html= str_replace('src="',$src_http_www, $document_html);
        //$document_html= str_replace('temp_template_path', 'src="/main/default_course_document/', $document_html);// restore src templates
        
        api_set_encoding_html($document_html, 'UTF-8'); // The library mPDF expects UTF-8 encoded input data.        
        $title = api_get_title_html($document_html, 'UTF-8', 'UTF-8');  // TODO: Maybe it is better idea the title to be passed through
                                                                        // $_GET[] too, as it is done with file name.
                                                                        // At the moment the title is retrieved from the html document itself.
       /* if (empty($title)) {
            $title = $filename; // Here file name is expected to contain ASCII symbols only.
        }*/      
                       
        if (!empty($css)) {
            $this->pdf->WriteHTML($css, 1);            
        } 
        $this->pdf->WriteHTML($document_html,2);    
        
        if (empty($pdf_name)) {
            $output_file = 'pdf_'.date('Y-m-d-his').'.pdf';
        } else {
            $pdf_name = replace_dangerous_char($pdf_name);
            $output_file = $pdf_name.'.pdf';
        }
        $result = $this->pdf->Output($output_file, 'D');       /// F to save the pdf in a file
                      
        exit;
    }
    
    /**
     * Gets the watermark from the platform or a course
     * @param   string  course code (optional)
     * @param   mixed   web path of the watermark image, false if there is nothing to return
     */
    public function get_watermark($course_code = null) {
        $web_path = false;
        if (!empty($course_code) && api_get_setting('pdf_export_watermark_by_course') == 'true') {
            $course_info = api_get_course_info($course_code);
            $store_path = api_get_path(SYS_COURSE_PATH).$course_info['path'].'/'.api_get_current_access_url_id().'_pdf_watermark.png';   // course path
            if (file_exists($store_path)) {
                $web_path   = api_get_path(WEB_COURSE_PATH).$course_info['path'].'/'.api_get_current_access_url_id().'_pdf_watermark.png';                 
            }
        } else {
            $store_path = api_get_path(SYS_CODE_PATH).'default_course_document/images/'.api_get_current_access_url_id().'_pdf_watermark.png';   // course path
            if (file_exists($store_path))                   
                $web_path   = api_get_path(WEB_CODE_PATH).'default_course_document/images/'.api_get_current_access_url_id().'_pdf_watermark.png';
        }
        return $web_path;        
    }
    
    /**
     * Deletes the watermark from the Platform or Course
     * @param   string  course code (optional)
     * @param   mixed   web path of the watermark image, false if there is nothing to return
     */
     
    public function delete_watermark($course_code = null) {        
        if (!empty($course_code) && api_get_setting('pdf_export_watermark_by_course') == 'true') {
            $course_info = api_get_course_info($course_code);
            $store_path  = api_get_path(SYS_COURSE_PATH).$course_info['path'].'/'.api_get_current_access_url_id().'_pdf_watermark.png';   // course path            
        } else {
            $store_path = api_get_path(SYS_CODE_PATH).'default_course_document/'.api_get_current_access_url_id().'_pdf_watermark.png';   // course path              
        }        
        if (file_exists($store_path)) {
            @unlink($store_path);
            return true;
        }
    	return false;
    }
    
    /**
     * Uploads the pdf watermark in the main/default_course_document directory or in the course directory
     * @param	string	filename
     * @param	string	path of the file
     * @param	string	course code
     * @return 	mixed	web path of the file if sucess, false otherwise
     */
    public function upload_watermark($filename, $source_file, $course_code = null) {        
        if (!empty($course_code) && api_get_setting('pdf_export_watermark_by_course') == 'true') {
            $course_info = api_get_course_info($course_code);            
            $store_path = api_get_path(SYS_COURSE_PATH).$course_info['path'];   // course path
            $web_path   = api_get_path(WEB_COURSE_PATH).$course_info['path'].'/pdf_watermark.png';
        } else {
            $store_path = api_get_path(SYS_CODE_PATH).'default_course_document/images';   // course path	
            $web_path   = api_get_path(WEB_CODE_PATH).'default_course_document/images/'.api_get_current_access_url_id().'_pdf_watermark.png';
        }        
        $course_image = $store_path.'/'.api_get_current_access_url_id().'_pdf_watermark.png';
        $extension = strtolower(substr(strrchr($filename, '.'), 1));
        $result = false;
        
        if (file_exists($course_image)) {
            @unlink($course_image);
        }
        $my_image = new Image($source_file);
        $result = $my_image->send_image($course_image, -1, 'png');        
        if ($result) {
        	$result = $web_path;
        }
        return $result;
    }
    
    /**
     * Returns the default header
     */
    public function get_header($course_code = null) {
        /*$header = api_get_setting('pdf_export_watermark_text');        
    	if (!empty($course_code) && api_get_setting('pdf_export_watermark_by_course') == 'true') {
            $header = api_get_course_setting('pdf_export_watermark_text');                        
        }
        return $header;*/        
    }
    
    public function set_footer() {    	 
        $this->pdf->defaultfooterfontsize = 12;   // in pts
        $this->pdf->defaultfooterfontstyle = B;   // blank, B, I, or BI
        $this->pdf->defaultfooterline = 1;        // 1 to include line below header/above footer
        $platform_name = api_get_setting('Institution');
        $left_content    = $platform_name;
        $center_content  = '';
        $right_content   = '{PAGENO}';
        
        //@todo remove this and use a simpler way
        $footer = array (
              'odd' => array (
                'L' => array (
                  'content' => $left_content,  'font-size' => 10,'font-style' => 'B','font-family' => 'serif','color'=>'#000000'),
                'C' => array (
                  'content' => $center_content,'font-size' => 10,'font-style' => 'B','font-family' => 'serif','color'=>'#000000'),
                'R' => array (
                  'content' => $right_content, 'font-size' => 10,'font-style' => 'B','font-family' => 'serif','color'=>'#000000'),
                'line' => 1,
              ),
             'even' => array (
                'L' => array (
                  'content' => $left_content,  'font-size' => 10,'font-style' => 'B','font-family' => 'serif','color'=>'#000000'),
                'C' => array (
                  'content' => $center_content,'font-size' => 10,'font-style' => 'B','font-family' => 'serif','color'=>'#000000'),
                'R' => array (
                  'content' => $right_content, 'font-size' => 10,'font-style' => 'B','font-family' => 'serif','color'=>'#000000'),
                'line' => 1,
              ),
            );       
        $this->pdf->SetFooter($footer);      // defines footer for Odd and Even Pages - placed at Outer margin http://mpdf1.com/manual/index.php?tid=151&searchstring=setfooter
    }
    
    
    public function set_header($course_data) {
    	// $pdf->SetBasePath($basehref); 
                
        $this->pdf->defaultheaderfontsize = 10;   // in pts
        $this->pdf->defaultheaderfontstyle = BI;   // blank, B, I, or BI
        $this->pdf->defaultheaderline = 1;        // 1 to include line below header/above footer              
        
        if (!empty($course_data['code'])) {
            $teacher_list = CourseManager::get_teacher_list_from_course_code($course_data['code']);
            $teachers = '';
            if (!empty($teacher_list)) {                
                foreach($teacher_list as $teacher) {
                    //$teachers[]= api_get_person_name($teacher['firstname'], $teacher['lastname']);
                    $teachers[]= $teacher['firstname'].' '.$teacher['lastname'];
                }
                if (count($teachers) > 1) {
                    $teachers = get_lang('Teachers').': '.implode(', ', $teachers);
                } else {
                    $teachers = get_lang('Teacher').': '.implode('', $teachers);
                }
            }
           
            $left_content    = '';
            $center_content  = '';
            $right_content   = $teachers;
        
            $header = array (
              'odd' => array (
                'L' => array (
                  'content' => $left_content,  'font-size' => 10,'font-style' => 'B','font-family' => 'serif','color'=>'#000000'),
                'C' => array (
                  'content' => $center_content,'font-size' => 10,'font-style' => 'B','font-family' => 'serif','color'=>'#000000'),
                'R' => array (
                  'content' => $right_content, 'font-size' => 10,'font-style' => 'B','font-family' => 'serif','color'=>'#000000'),
                'line' => 1,
              ),
             'even' => array (
                'L' => array (
                  'content' => $left_content,  'font-size' => 10,'font-style' => 'B','font-family' => 'serif','color'=>'#000000'),
                'C' => array (
                  'content' => $center_content,'font-size' => 10,'font-style' => 'B','font-family' => 'serif','color'=>'#000000'),
                'R' => array (
                  'content' => $right_content, 'font-size' => 10,'font-style' => 'B','font-family' => 'serif','color'=>'#000000'),
                'line' => 1,
              ),
            );
            $this->pdf->SetHeader($header);// ('{DATE j-m-Y}|{PAGENO}/{nb}|'.$title);       
        }               
    }
    
    public function set_custom_header($header) {
        $this->custom_header = $header;
    }
    
    public function set_custom_footer($footer) {
        $this->custom_footer = $footer;
    }
    
    public function format_pdf($course_data) {
        $course_code = null;
        if (!empty( $course_data)) {    
            $course_code = $course_data['code'];   
        }
        
        /*$pdf->SetAuthor('Documents Chamilo');
        $pdf->SetTitle('title');
        $pdf->SetSubject('Exported from Chamilo Documents');
        $pdf->SetKeywords('Chamilo Documents');
        */                      
        $this->pdf->directionality = api_get_text_direction(); // TODO: To be read from the html document.        
        $this->pdf->useOnlyCoreFonts = true;        
        $this->pdf->mirrorMargins = 1;            // Use different Odd/Even headers and footers and mirror margins       
        
        //Adding watermark
        if (api_get_setting('pdf_export_watermark_enable') == 'true') {            
            $watermark_file = self::get_watermark($course_code);      
                  
            if ($watermark_file) {                
                //http://mpdf1.com/manual/index.php?tid=269&searchstring=watermark                
                $this->pdf->SetWatermarkImage($watermark_file);
                $this->pdf->showWatermarkImage = true;
            } else {
                $watermark_file = self::get_watermark(null);      
                if ($watermark_file) {   
                    $this->pdf->SetWatermarkImage($watermark_file);
                    $this->pdf->showWatermarkImage = true;
                }
            }
            if ($course_code) {
                $watermark_text = api_get_course_setting('pdf_export_watermark_text');
                if (empty($watermark_text)) {
                    $watermark_text = api_get_setting('pdf_export_watermark_text');    
                }
            } else {
                $watermark_text = api_get_setting('pdf_export_watermark_text');
            }
            if (!empty($watermark_text)) {
                $this->pdf->SetWatermarkText(strcode2utf($watermark_text),0.1);
                $this->pdf->showWatermarkText = true;
            }
        }        
        if (empty($this->custom_header)) {
            self::set_header($course_data);   
        } else {
            $this->pdf->SetHTMLHeader($this->custom_header);	
        }
        
        if (empty($this->custom_footer)) {
            self::set_footer();    
        } else {
            $this->pdf->SetHTMLFooter($this->custom_footer);	
        }        
    }
}
