<?php
/**
* @package chamilo.library
*/
class Certificate extends Model {
    var $table;
    var $columns = array('id','cat_id','score_certificate','created_at','path_certificate');
    /**
     * Certification data 
     * */
    var $certificate_data = array();
    
    /**
     * Student's certification path
     * */    
    var $certification_user_path = null;  
    var $certification_web_user_path = null;  
    var $user_id;
    
	public function __construct($certificate_id = null) {
        $this->table 			=  Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $this->certificate_data = null;
        
        if (isset($certificate_id)) {
        	$this->certificate_data = $this->get($certificate_id);
        	$this->user_id = $this->certificate_data['user_id']; 
        } else {
        	//Try with the current user
        	$this->user_id = api_get_user_id();
        }
        
        if ($this->user_id) { 
        	
			//Need to be called before any operation
	        $this->check_certificate_path();
	        	    
			//To force certification generation
	        $this->generate();
	        
	        if (isset($this->certificate_data) && $this->certificate_data) {        
	        	if (empty($this->certificate_data['path_certificate'])) {
	        		$this->generate();	        		
	        	}        	
	        }
        }
	}
	
	/**
	 * Shows the student's certificate (HTML file)	 
	 */
	public function show() {		
		//Read file or preview file
		if (!empty($this->certificate_data['path_certificate'])) {
			$user_certificate = $this->certification_user_path.basename($this->certificate_data['path_certificate']);
			if (file_exists($user_certificate)) {
				header('Content-Type: text/html; charset='. api_get_system_encoding());
				echo @file_get_contents($user_certificate);				
			}
		} else {			
			Display :: display_reduced_header();
			Display :: display_warning_message(get_lang('NoCertificateAvailable'));			
		}
		exit;
	}
	
	/**
	 * Checks if the certificate user path directory is created
	 */
	public function check_certificate_path() {
		$this->certification_user_path = null;
		
		//Setting certification path
		$path_info = UserManager::get_user_picture_path_by_id($this->user_id, 'system', true);
		
		$web_path_info = UserManager::get_user_picture_path_by_id($this->user_id, 'web', true);
		
		if (!empty($path_info) && isset($path_info['dir'])) {
			
			$this->certification_user_path = $path_info['dir'].'certificate/';
			$this->certification_web_user_path = $web_path_info['dir'].'certificate/';
			
			
			if (!is_dir($path_info['dir'])) {
				mkdir($path_info['dir'],0777);
			}
					
			if (!is_dir($this->certification_user_path)) {
				mkdir($this->certification_user_path, 0777);
			}
		}
		
	}
	
	/** 
	 * 	Generates an HTML Certificate and fills the path_certificate field in the DB 
	 * */
	
	public function generate() {
		$always_generate = false; // For test purposes
		
		//The user directory should be set
		if (empty($this->certification_user_path) && $always_generate == false) {
			return false;
		}		
		require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be.inc.php';
		require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';
		require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/scoredisplay.class.php';
		
		$my_category = Category :: load($this->certificate_data['cat_id']);		
		if ($my_category[0]->is_certificate_available($this->user_id)) {
						
			$user         = api_get_user_info($this->user_id);
			$scoredisplay = ScoreDisplay :: instance();
			$scorecourse  = $my_category[0]->calc_score($this->user_id);
	
			$scorecourse_display = (isset($scorecourse) ? $scoredisplay->display_score($scorecourse,SCORE_AVERAGE) : get_lang('NoResultsAvailable'));
			$cattotal = Category :: load($this->certificate_data['cat_id']);
			$scoretotal= $cattotal[0]->calc_score($this->user_id);
			$scoretotal_display = (isset($scoretotal) ? $scoredisplay->display_score($scoretotal,SCORE_PERCENT) : get_lang('NoResultsAvailable'));
	
			//Prepare all necessary variables:
			$organization_name 	= api_get_setting('Institution');
			$portal_name 		= api_get_setting('siteName');
			$stud_fn 			= $user['firstname'];
			$stud_ln 			= $user['lastname'];
				
			//@todo this code is not needed
			$certif_text 		= sprintf(get_lang('CertificateWCertifiesStudentXFinishedCourseYWithGradeZ'), $organization_name, $stud_fn.' '.$stud_ln, $my_category[0]->get_name(), $scorecourse_display);
			$certif_text 		= str_replace("\\n","\n", $certif_text);
	
			$date = date('d/m/Y', time());
	
			if (is_dir($this->certification_user_path)) {
				if (!empty($this->certificate_data)) {	
					$new_content_html = get_user_certificate_content($this->user_id, $my_category[0]->get_course_code(), false);
				
					if ($cat_id = strval(intval($this->certificate_data['cat_id']))) {
						$name = $this->certificate_data['path_certificate'];
						$my_path_certificate = $this->certification_user_path.basename($name);
						if (file_exists($my_path_certificate) && !empty($name) && !is_dir($my_path_certificate) && $always_generate == false) {
							//Seems that the file was already generated							
							return true;
						} else {
							//Creating new name
							$name    = md5($this->user_id.$this->certificate_data['cat_id']).'.html';
							$my_path_certificate = $this->certification_user_path.$name;							
							$path_certificate    ='/'.$name;
							
							//Getting QR filename
							$file_info = pathinfo($path_certificate);
							$qr_code_filename = $this->certification_user_path.$file_info['filename'].'_qr.png';							
							$new_content_html['content'] = str_replace('((certificate_barcode))', Display::img($this->certification_web_user_path.$file_info['filename'].'_qr.png', 'QR'), $new_content_html['content']);
							
							$my_new_content_html = $new_content_html['content'];
						
							$my_new_content_html = mb_convert_encoding($my_new_content_html,'UTF-8', api_get_system_encoding());
							
							$result = @file_put_contents($my_path_certificate, $my_new_content_html);
							if ($result) {						
								
								//@todo move function in this class
								update_user_info_about_certificate($this->certificate_data['cat_id'], $this->user_id, $path_certificate);
								$this->certificate_data['path_certificate'] = $path_certificate;
								
								if ($this->html_file_is_generated()) {
									if (!empty($file_info)) {										
										$text = $this->parse_certificate_variables($new_content_html['variables']);										
										$this->generate_qr($text, $qr_code_filename);
									}
								}
								
							}
							return $result;
						}						
					}
				}
			}
		}
		return false;
	}
	
	/**
	 * 
	 * Check if the file was generated
	 * 
	 * @return boolean
	 */
	function html_file_is_generated() {
		if (empty($this->certification_user_path)) {
			return false;
		}
		if (!empty($this->certificate_data) && isset($this->certificate_data['path_certificate']) && !empty($this->certificate_data['path_certificate'])) {
			return true;			
		}
		return false;
	} 
	
	public function generate_qr($text, $path) {		
		//Make sure HTML certificate is generated
		if (!empty($text) && !empty($path)) {
			require_once api_get_path(LIBRARY_PATH).'phpqrcode/qrlib.php';			
			$return = QRcode::png($text, $path, 'L', 4, 2);						
		}
	}
	
	private function parse_certificate_variables($array) {
		$text = '';		
		$headers = $array[0];
		$content = $array[1];
		$final_content = array();
		
		foreach($content as $key => $value) {
			$my_header = $headers[$key];
			$my_header = str_replace(array('((', '))') , '', $my_header);
			$final_content[$my_header] = $value;
		}
		
		/*
		 * 
		  0 => string '((user_firstname))' (length=18)
          1 => string '((user_lastname))' (length=17)
          2 => string '((gradebook_institution))' (length=25)
          3 => string '((gradebook_sitename))' (length=22)
          4 => string '((teacher_firstname))' (length=21)
          5 => string '((teacher_lastname))' (length=20)
          6 => string '((official_code))' (length=17)
          7 => string '((date_certificate))' (length=20)
          8 => string '((course_code))' (length=15)
          9 => string '((course_title))' (length=16)
          10 => string '((gradebook_grade))' (length=19)
          11 => string '((certificate_link))' (length=20)
          12 => string '((certificate_link_html))' (length=25)
          13 => string '((certificate_barcode))' (length=23)
          
		 */
		
		$break_space = " \n\r ";
		
		$text = $final_content['gradebook_institution'].' - '.$final_content['gradebook_sitename'].' - '.get_lang('Certification').$break_space.
				get_lang('Student'). ': '.$final_content['user_firstname'].' '.$final_content['user_lastname'].$break_space.
				//get_lang('Portal'). ': '.$final_content['gradebook_sitename'].$break_space.
				get_lang('Teacher'). ': '.$final_content['teacher_firstname'].' '.$final_content['teacher_lastname'].$break_space.
				get_lang('Date'). ': '.$final_content['date_certificate'].$break_space.
				get_lang('Score'). ': '.$final_content['gradebook_grade'].$break_space.
				'URL'. ': '.$final_content['certificate_link'];		
		return $text;
		                                                                                                    
		
	}
	
}