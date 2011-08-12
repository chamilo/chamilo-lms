<?php
/**
* @package chamilo.library
*/
class Certificate extends Model {
    var $table;
    var $columns = array('id','cat_id','score_certificate','created_at','path_certificate');
    var $certificate_data;
    
    var $certification_user_path;    
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
	            
	        if (isset($this->certificate_data) && $this->certificate_data) {        
	        	if (empty($this->certificate_data['path_certificate'])) {
	        		$this->generate();	        		
	        	}        	
	        }
        }
	}
	
	/**
	 * Show an HTML file	 
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
	 * Checks the certificate user path directories
	 * Enter description here ...
	 */
	public function check_certificate_path() {
		$this->certification_user_path = null;
		
		//Setting certification path
		$path_info = UserManager::get_user_picture_path_by_id($this->user_id, 'system', true);
		
		if (isset($path_info['dir']) && !empty($path_info)) {
			
			$this->certification_user_path = $path_info['dir'].'certificate/';		
			
			if (!is_dir($path_info['dir'])) {
				mkdir($path_info['dir'],0777);
			}
					
			if (!is_dir($this->certification_user_path)) {
				mkdir($this->certification_user_path, 0777);
			}
		}
		
	}
	
	/** 
	 * 	Generates a certificate 
	 * */
	
	public function generate() {
		
		if (empty($this->certification_user_path)) {
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
				$name = $this->certificate_data['path_certificate'];		
				if (!empty($this->certificate_data)) {
					$new_content_html = get_user_certificate_content($this->user_id, $my_category[0]->get_course_code(), false);
										
					if ($cat_id = strval(intval($this->certificate_data['cat_id']))) {
						$my_path_certificate = $this->certification_user_path.$name;
						if (file_exists($my_path_certificate) && !empty($name)&& !is_dir($my_path_certificate) ) {
							//header('Content-Type: text/html; charset='. $charset);
							//echo $new_content_html;
							//Seems that the file was already generated
							return true;
						} else {
							$my_new_content_html = $new_content_html;
							$my_new_content_html = mb_convert_encoding($my_new_content_html,'UTF-8', api_get_system_encoding());
	
							//Creating new name
							$name    = md5($this->user_id.$this->certificate_data['cat_id']).'.html';
							$my_path_certificate = $this->certification_user_path.$name;
	
							$result = @file_put_contents($my_path_certificate, $my_new_content_html);						
							$path_certificate='/'.$name;
							//@todo move function in this class
							update_user_info_about_certificate($this->certificate_data['cat_id'], $this->user_id, $path_certificate);
							$this->certificate_data['path_certificate'] = $path_certificate;
							return $result;
						}						
					}
				}
			}
		}
		return false;
	}
}