<?php  
/* For licensing terms, see /license.txt */

/**
 * 
 * Files are saved in the path:
 * 
 *  courses/XXX/exercises/(session_id)/(exercise_id)/(question_id)/(user_id)/
 *
 * The file name is composed with 
 * 
 * (course_id)/(session_id)/(user_id)/(exercise_id)/(question_id)/(exe_id).wav|mp3|ogg
 *
 *
 */
class Nanogong {
	
	var $filename;
	var $store_filename;
	var $store_path;
	var $params;
	var $can_edit = false;
	
	/* Files allowed to upload */
	var $available_extensions = array('mp3', 'wav', 'ogg');
	
	public function __construct($params = array()) {
		$this->set_parameters($params);
	}
	
	function create_user_folder() {
				
		//COURSE123/exercises/session_id/exercise_id/question_id/user_id
		if (empty($this->store_path)) {
			return false;
		}
        
        //@todo use an array to create folders
		$folders_to_create = array();
        
        //Trying to create the courses/COURSE123/exercises/ dir just in case
        if (!is_dir($this->store_path)) {
			mkdir($this->store_path);
		}		
			
		if (!is_dir($this->store_path.$this->session_id)) {
			mkdir($this->store_path.$this->session_id);
		}
		
		if (!empty($this->exercise_id) && !is_dir($this->store_path.$this->session_id.'/'.$this->exercise_id)) {
			mkdir($this->store_path.$this->session_id.'/'.$this->exercise_id);
		}
		
		if (!empty($this->question_id) && !is_dir($this->store_path.$this->session_id.'/'.$this->exercise_id.'/'.$this->question_id)) {
			mkdir($this->store_path.$this->session_id.'/'.$this->exercise_id.'/'.$this->question_id);
		}
		
		if (!empty($this->user_id) && !is_dir($this->store_path.$this->session_id.'/'.$this->exercise_id.'/'.$this->question_id.'/'.$this->user_id)) {
			mkdir($this->store_path.$this->session_id.'/'.$this->exercise_id.'/'.$this->question_id.'/'.$this->user_id);
		}
	}
	
	/**
	 * Setting parameters: course id, session id, etc
	 * @param	array	
	 */
	function set_parameters($params = array()) {
		
		//Setting course id
		if (isset($params['course_id'])) {
			$this->course_id = intval($params['course_id']);
		} else {
			$this->course_id = $params['course_id'] = api_get_course_int_id();
		}
		
		//Setting course info
		if (isset($this->course_id)) {
			$this->course_info = api_get_course_info_by_id($this->course_id);
		}
		
		//Setting session id
		if (isset($params['session_id'])) {
			$this->session_id = intval($params['session_id']);
		} else {
			$this->session_id = $params['session_id'] = api_get_session_id();
		}
		
		//Setting user ids
		if (isset($params['user_id'])) {
			$this->user_id = intval($params['user_id']);
		} else {
			$this->user_id = $params['user_id'] = api_get_user_id();
		}
		
		//Setting user ids
		if (isset($params['exercise_id'])) {
			$this->exercise_id = intval($params['exercise_id']);
		} else {
			$this->exercise_id = 0;
		}
		
		//Setting user ids
		if (isset($params['question_id'])) {
			$this->question_id = intval($params['question_id']);
		} else {
			$this->question_id = 0;
		}
				
		$this->can_edit  = false;
		
		if (api_is_allowed_to_edit()) {
			$this->can_edit = true;			
		} else {
			if ($this->user_id ==  api_get_user_id()) {
				$this->can_edit = true;
			}
		}
		
		//Settings the params array
		$this->params 			= $params;
				
		$this->store_path 		= api_get_path(SYS_COURSE_PATH).$this->course_info['path'].'/exercises/';
		
		$this->create_user_folder();	
				
		$this->store_path 		= $this->store_path.implode('/', array($this->session_id, $this->exercise_id, $this->question_id, $this->user_id)).'/';
		$this->filename 		= $this->generate_filename();
		$this->store_filename 	= $this->store_path.$this->filename;
	}
	
	/**
	 * Generates the filename with the next format:
	 * (course_id)/(session_id)/(user_id)/(exercise_id)/(question_id)/(exe_id)
	 *
	 * @return string
	 */
	function generate_filename() {
		if (!empty($this->params)) {
			//filename
			//course_id/session_id/user_id/exercise_id/question_id/exe_id
			$filename_array = array($this->params['course_id'], $this->params['session_id'], $this->params['user_id'], $this->params['exercise_id'], $this->params['question_id'], $this->params['exe_id']);			
			return implode('-', $filename_array);
		} else {
			return api_get_unique_id();
		}
	}
	
	/**
	 * Delete audio file
	 * @return number
	 */
	function delete_files() {
		$delete_found = 0;
		if ($this->can_edit) {			
			$file = $this->load_filename_if_exists();			
			
			$path_info = pathinfo($file);			
			foreach($this->available_extensions as $extension) {
				$file_to_delete = $path_info['dirname'].'/'.$path_info['filename'].'.'.$extension;
				if (is_file($file_to_delete)) {
					unlink($file_to_delete);
					$delete_found = 1;
				}
			}		
		}
		return $delete_found;
	}
	
	/**
	 * 
	 * Tricky stuff to deal with the feedback = 0 in exercises (all question per page)
	 * @param unknown_type $exe_id
	 */
	function replace_with_real_exe($exe_id) {
		$filename = null;
		//@ugly fix
		foreach($this->available_extensions as $extension) {			
			$items = explode('-', $this->filename);
			$items[5] = 'temp_exe';
			$filename = implode('-', $items);			
			if (is_file($this->store_path.$filename.'.'.$extension)) {				 
				$old_name = $this->store_path.$filename.'.'.$extension;				
				$items = explode('-', $this->filename);
				$items[5] = $exe_id;
				$filename = $filename = implode('-', $items);
				$new_name = $this->store_path.$filename.'.'.$extension;
				//var_dump($old_name, $new_name);
				rename($old_name, $new_name);				
				break;
			}		
		}
	}
	
	function load_filename_if_exists($load_from_database = false) {
		$filename = null;
		//@ugly fix
		foreach($this->available_extensions as $extension) {			
			if (is_file($this->store_path.$this->filename.'.'.$extension)) {				
				$filename = $this->filename.'.'.$extension;
				break;				
			}
		}		
		
		//temp_exe		
		if ($load_from_database) {
		
			//Load the real filename just if exists
			if (isset($this->params['exe_id']) && isset($this->params['user_id']) && isset($this->params['question_id']) && isset($this->params['session_id']) && isset($this->params['course_id'])) {
				$attempt_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
				$sql = "SELECT filename FROM $attempt_table
								WHERE 	exe_id 		= ".$this->params['exe_id']." AND
										user_id 	= ".$this->params['user_id']." AND 	
										question_id = ".$this->params['question_id']." AND
										session_id 	= ".$this->params['session_id']." AND 
										course_code = '".$this->course_info['code']."' LIMIT 1";
				$result = Database::query($sql);
				$result = Database::fetch_row($result,'ASSOC');
									
				if (isset($result) && isset($result[0]) && !empty($result[0])) {
					$filename = $result[0];				
				}
			}
		}
		
		if (is_file($this->store_path.$filename)) {
			return $this->store_path.$filename;
		}
		return null;
	}
	
	/**
	 * 
	 * Get the URL of the file
	 * path courses/XXX/exercises/(session_id)/(exercise_id)/(question_id)/(user_id)/
	 * 
	 * @return string
	 */
	function get_public_url($force_download = 0) {
		$params = $this->get_params(true);
		$url = api_get_path(WEB_AJAX_PATH).'nanogong.ajax.php?a=get_file&download='.$force_download.'&'.$params;	
		$params = $this->get_params();
		$filename = basename($this->load_filename_if_exists());		
		$url = api_get_path(WEB_COURSE_PATH).$this->course_info['path'].'/exercises/'.
		$params['session_id'].'/'.$params['exercise_id'].'/'.$params['question_id'].'/'.$params['user_id'].'/'.$filename;		
		return $url;
	}
	
	/**
	 * Uploads the nanogong wav file 
	 */	
	public function upload_file($is_nano = false) {
		require_once api_get_path(LIBRARY_PATH).'fileDisplay.lib.php';
		require_once api_get_path(LIBRARY_PATH).'document.lib.php';
		require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
				
		if (!empty($_FILES)) {		
			$upload_ok = process_uploaded_file($_FILES['file'], false);			
			
			if (!is_uploaded_file($_FILES['file']['tmp_name'])) {
				return 0;
			}
					
			if ($upload_ok) {
				// Check if there is enough space to save the file
				if (!DocumentManager::enough_space($_FILES['file']['size'], DocumentManager::get_course_quota())) {
					return 0;				
				}		
				
				//first we delete everything before uploading the file
				$this->delete_files();
								
				//Reload the filename variable
				$file_name = add_ext_on_mime($_FILES['file']['name'], $_FILES['file']['type']);				
				$file_name = strtolower($file_name);
				$file_info = pathinfo($file_name);				
				
				if ($is_nano == true) {
					$file_info['extension'] = 'wav';					
				}
				
				$file_name = $this->filename.'.'.$file_info['extension'];
			
				if (in_array($file_info['extension'], $this->available_extensions)) {				
					if (move_uploaded_file($_FILES['file']['tmp_name'], $this->store_path.$file_name)) {
						$this->store_filename = $this->store_path.$file_name;						
						//error_log('saved');
						return 1;
					}
				}
			}			
		}
		return 0;
	}
	
	/**
	 * Show the audio file + a button to download
	 *
	 */
	public function show_audio_file($show_delete_button = false) {
		$html = '';
		$file_path = $this->load_filename_if_exists();
		
		if (!empty($file_path)) {
			$url = $this->get_public_url(true);
			$actions = Display::url(Display::return_icon('save.png', get_lang('Download'), array(), ICON_SIZE_SMALL), $url, array('target'=>'_blank'));
			$download_button = Display::url(get_lang('Download'), $url, array('class' =>'btn'));
			
			if ($show_delete_button) {				
				$actions .= ' '.Display::url(Display::return_icon('delete.png', get_lang('Delete'), array(), ICON_SIZE_SMALL), "#", array('onclick'=>'delete_file();'));
			}
							
			$basename = basename($file_path);
			$path_info = pathinfo($basename);
			
			if ($path_info['extension'] == 'wav') {
				
				$html .= '<script>
				$(document).ready( function() {
					var java_enabled = navigator.javaEnabled();
											
					if (java_enabled) {				
						$("#nanogong_warning").hide();
						$("#nanogong_player_id").show();
					} else {
						$("#nanogong_warning").show();
						$("#nanogong_player_id").hide();
					}
				});
				</script>';
												
				$html .= '<div id="nanogong_player_id" class="nanogong_player_container">';
					$html .= '<div class="action_player">'.$actions.'</div>';				
					$html .= '<div class="nanogong_player">';	
						$html .= '<applet id="nanogong_player" archive="'.api_get_path(WEB_LIBRARY_PATH).'nanogong/nanogong.jar" code="gong.NanoGong" width="250" height="40" ALIGN="middle">';
						
						$html .= '<param name="ShowRecordButton" value="false" />'; // default true
						$html .= '<param name="ShowSaveButton" value="false" />'; //you can save in local computer | (default true)
						//echo '<param name="ShowSpeedButton" value="false" />'; // default true
						//echo '<param name="ShowAudioLevel" value="false" />'; //  it displays the audiometer | (default true)
						$html .= '<param name="ShowTime" value="true" />'; // default false
						$html .= '<param name="Color" value="#FFFFFF" />';
						//echo '<param name="StartTime" value="10.5" />';
						//echo '<param name="EndTime" value="65" />';
						$html .= '<param name="AudioFormat" value="ImaADPCM" />';// ImaADPCM (more speed), Speex (more compression)|(default Speex)
						//$html .= '<param name="AudioFormat" value="Speex" />';// ImaADPCM (more speed), Speex (more compression)|(default Speex)
						
						//Quality for ImaADPCM (low 8000, medium 11025, normal 22050, hight 44100) OR Quality for Speex (low 8000, medium 16000, normal 32000, hight 44100) | (default 44100)						
						//echo '<param name="SamplingRate" value="32000" />';
						//echo '<param name="MaxDuration" value="60" />';
						$html .=  '<param name="SoundFileURL" value="'.$url.'" />';//load a file |(default "")
						$html .= '</applet>';						
					$html .= '</div>';
				$html .= '</div>';
						
				$html .= '<div id="nanogong_warning">'.Display::return_message(get_lang('BrowserNotSupportNanogongListen'),'warning').$download_button.'</div>';			
				
			} elseif(in_array($path_info['extension'],array('mp3', 'ogg','wav'))) {
				$js_path 		= api_get_path(WEB_LIBRARY_PATH).'javascript/';
				
				$html .= '<link rel="stylesheet" href="'.$js_path.'jquery-jplayer/skins/blue/jplayer.blue.monday.css" type="text/css">';
                //$html .= '<link rel="stylesheet" href="' . $js_path . 'jquery-jplayer/skins/chamilo/jplayer.blue.monday.css" type="text/css">';
				$html .= '<script type="text/javascript" src="'.$js_path.'jquery-jplayer/jquery.jplayer.min.js"></script>';
				
				$html .= '<div class="nanogong_player"></div>';
				$html .= '<br /><div class="action_player">'.$actions.'</div><br /><br /><br />';
				
                $params = array('url' => $url,
                                'extension' =>$path_info['extension'],
                                'count'=> 1                                 
                 );
                $jquery = DocumentManager::generate_jplayer_jquery($params);
                
				
				$html .= '<script>
				$(document).ready( function() {        
				    //Experimental changes to preview mp3, ogg files        
				     '.$jquery.'                 
				});
				</script>';				
				$html .= DocumentManager::generate_media_preview(1, 'advanced');
			}
		}
        return $html;
	}
	
	
	/*
	var filename = document.getElementById("audio_title").value+".wav";
	var filename = filename.replace(/\s/g, "_");//replace spaces by _
	var filename = encodeURIComponent(filename);
	var filepath="'.urlencode($filepath).'";
	var dir="'.urlencode($dir).'";
	var course_code="'.urlencode($course_code).'";
	var urlnanogong="'.$url.'?filename="+filename+"&filepath="+filepath+"&dir="+dir+"&course_code="+course_code;
	*/
	
	/**
	 * Returns the nanogong javascript code
	 * @return string
	 */
	function return_js() {
		$params = $this->get_params(true);
		$url = api_get_path(WEB_AJAX_PATH).'nanogong.ajax.php?a=save_file&'.$params.'&is_nano=1';
		$url_load_file = api_get_path(WEB_AJAX_PATH).'nanogong.ajax.php?a=show_audio&'.$params;
		
		$url_delete = api_get_path(WEB_AJAX_PATH).'nanogong.ajax.php?a=delete&'.$params;
		
		$js =  '<script language="javascript">

			//lang vars
			
			var lang_no_applet				= "'.get_lang('NanogongNoApplet').'";
			var lang_record_before_save		= "'.get_lang('NanogongRecordBeforeSave').'";
			var lang_give_a_title			= "'.get_lang('NanogongGiveTitle').'";
			var lang_failed_to_submit		= "'.get_lang('NanogongFailledToSubmit').'";
			var lang_submitted				= "'.get_lang('NanogongSubmitted').'";
			var lang_deleted				= "'.get_lang('Deleted').'";

			var is_nano = 0;
			
			function check_gong() {
				//var record = document.getElementById("nanogong");
				var recorder;
				
				var java_enabled = navigator.javaEnabled()
				return java_enabled;				
			}
            

            function show_simple_upload_form() {
                $("#no_nanogong_div").show();
                $("#nanogong_div").hide();
                $("#preview").hide();                
            }
		
			$(document).ready( function() {	
				$("#no_nanogong_div").hide();
				$("#nanogong_div").hide();
				
				var check_js = check_gong();
				
				if (check_js == true) {
					$("#nanogong_div").show();
					$("#no_nanogong_div").hide();
					is_nano = 1;
					$(".nanogong_player").show(); 
				} else {
					$("#no_nanogong_div").show();
					$("#nanogong_div").hide();
					$(".nanogong_player").hide();
				}
					
				//show always the mp3/ogg upload form (for dev purposes)
				
				//$("#no_nanogong_div").show();
				//$("#nanogong_div").hide();					
			});
			
			function delete_file() {
				$.ajax({
					url: "'.$url_delete.'",
					success:function(data) {
						$("#status_warning").hide();
						$("#status_ok").hide();
			
						$("#messages").html(data);
						$("#messages").show();
						$("#preview").hide();	
					}
				});
			}
			
			function upload_file() {
				$("#form_nanogong_simple").submit();
			}			
			
			function send_voice() {
				$("#status_warning").hide();
				$("#status_ok").hide();
				$("#messages").hide();
				
				var check_js = check_gong();				
				var recorder = document.getElementById("nanogong");
				
				if (!recorder || !check_js) {
					//alert(lang_no_applet)
					$("#status_warning").html(lang_no_applet);
					$("#status_warning").show();
                    
                    //Show form
                    $("#no_nanogong_div").show();                    
                    $("#nanogong_div").hide();           
					return false;
				}
				
				var duration = parseInt(recorder.sendGongRequest("GetMediaDuration", "audio")) || 0;
				
				if (duration <= 0) {
					$("#status_warning").html(lang_record_before_save);
					$("#status_warning").show();
					return false;				
				}		
				
				var applet = document.getElementById("nanogong");
				
				var ret = applet.sendGongRequest("PostToForm", "'.$url.'", "file", "", "temp"); // PostToForm, postURL, inputname, cookie, filename
				
				if (ret == 1)  {				
					$("#status_ok").html(lang_submitted);
					$("#status_ok").show();					
					$.ajax({
						url:"'.$url_load_file.'&is_nano="+is_nano,
						success: function(data){													
							$("#preview").html(data);
							$("#preview").show();					
						}					
					});					
				} else {
					//alert(lang_submitted+"\n"+ret);
					$("#status_warning").html(lang_failed_to_submit);
					$("#status_warning").show();
				}
				return false;
			}
			</script>';
		return $js;
	}
	
	/**
	 * Returns the HTML form to upload a nano file or upload a file	 
	 */
	function return_form($message = null) {
	
		$params = $this->get_params(true);
		$url = api_get_path(WEB_AJAX_PATH).'nanogong.ajax.php?a=save_file&'.$params;
			
		//check browser support and load form
		$array_browser = api_browser_support('check_browser');
	
		$preview_file = $this->show_audio_file(true, true);
        
        
		$preview_file = Display::div($preview_file, array('id' => 'preview', 'style' => 'text-align:center; padding-left: 25px;'));
	
		$html .= '<center>';
	
		//Use normal upload file
		$html .= Display::return_icon('microphone.png', get_lang('PressRecordButton'),'', ICON_SIZE_BIG);
		$html .='<br />';
        
        
		$html .= '<div id="no_nanogong_div">';
		//$html .= Display::return_message(get_lang('BrowserNotSupportNanogongSend'), 'warning');
	
		$html .= '<form id="form_nanogong_simple" class="form-search" action="'.$url.'" name="form_nanogong" method="POST" enctype="multipart/form-data">';
		$html .= '<input type="file" name="file">';
		$html .= '<a href="#" class="btn"  onclick="upload_file()" />'.get_lang('UploadFile').'</a>';
		$html .= '</form>';
        
        $html .= '</div>';	
	
		$html .= '<div id="nanogong_div">';
	
		$html .= '<applet id="nanogong" archive="'.api_get_path(WEB_LIBRARY_PATH).'nanogong/nanogong.jar" code="gong.NanoGong" width="250" height="40" align="middle">';
		//echo '<param name="ShowRecordButton" value="false" />'; // default true
		// echo '<param name="ShowSaveButton" value="false" />'; //you can save in local computer | (default true)
		//echo '<param name="ShowSpeedButton" value="false" />'; // default true
		//echo '<param name="ShowAudioLevel" value="false" />'; //  it displays the audiometer | (default true)
		$html .= '<param name="ShowTime" value="true" />'; // default false
		$html .= '<param name="Color" value="#FFFFFF" />'; // default #FFFFFF
		//echo '<param name="StartTime" value="10.5" />';
		//echo '<param name="EndTime" value="65" />';
		$html .= '<param name="AudioFormat" value="ImaADPCM" />';// ImaADPCM (more speed), Speex (more compression)|(default Speex)
		//$html .= '<param name="AudioFormat" value="Speex" />';// ImaADPCM (more speed), Speex (more compression)|(default Speex)
	
		//echo '<param name="SamplingRate" value="32000" />';//Quality for ImaADPCM (low 8000, medium 11025, normal 22050, hight 44100) OR Quality for Speex (low 8000, medium 16000, normal 32000, hight 44100) | (default 44100)
		//echo '<param name="MaxDuration" value="60" />';
		//echo '<param name="SoundFileURL" value="http://somewhere.com/mysoundfile.wav" />';//load a file |(default "")
		$html .= '</applet>';
	
		$html .= '<br /><br /><br />';        
        
        $html .= '<form name="form_nanogong_advanced">';
		$html .= '<input type="hidden" name="is_nano" value="1">';
		$html .= '<a href="#" class="btn"  onclick="send_voice()" />'.get_lang('SendRecord').'</a>';
		$html .= '</form></div>';	
        
        $html .= Display::url(get_lang('ProblemsRecordingUploadYourOwnAudioFile'), 'javascript:void(0)', array('onclick' => 'show_simple_upload_form();'));
	
		$html .= '</center>';
        
		
		$html .= '<div style="display:none" id="status_ok" class="confirmation-message"></div><div style="display:none" id="status_warning" class="warning-message"></div>';
		
		$html .= '<div id="messages">'.$message.'</div>';
		
		$html .= $preview_file;
			
		
		return $html;
	}
	
	function get_params($return_as_query = false) {
		if (empty($this->params)) {
			return false;
		}		
		if ($return_as_query) {
			return http_build_query($this->params);
		}
		return $this->params;		
	}
	
	function get_param_value($attribute) {
		if (isset($this->params[$attribute])) {
			return $this->params[$attribute];
		}
	}
	
	/**
	 * Show a button to load the form
	 * @return string
	 */
	function show_button() {		
		$params_string = $this->get_params(true);		
		$html .= '<br />'.Display::url(get_lang('RecordAnswer'),api_get_path(WEB_AJAX_PATH).'nanogong.ajax.php?a=show_form&'.$params_string.'&TB_iframe=true&height=400&width=500', 
						array('class'=>'btn thickbox'));
		$html .= '<br /><br />'.Display::return_message(get_lang('UseTheMessageBelowToAddSomeComments'));				
		return $html;
	}	
} 