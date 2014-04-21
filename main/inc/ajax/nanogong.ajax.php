<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

$language_file = array('exercice','document');

require_once '../global.inc.php';

//@todo for some reason nanogong doesn't like this variables 
//api_protect_course_script(true);

$action = $_REQUEST['a'];
require_once api_get_path(LIBRARY_PATH).'nanogong.lib.php';

unset($_REQUEST['a']);
$js_path 		= api_get_path(WEB_LIBRARY_PATH).'javascript/';

//Fix in order to add the exe_id 

if (isset($_REQUEST['from_htaccess'])) {
	if (isset($_REQUEST['file'])) {
		$fileinfo = pathinfo($_REQUEST['file']);
		$items = explode('-', $fileinfo['filename']);
		$_REQUEST['exe_id'] = $items[5];	
	}
}
$nano = new Nanogong($_REQUEST);
$is_nano = false;

if (isset($_REQUEST['is_nano'])) {
	$is_nano = true;
}

switch ($action) { 
	case 'get_file':		
		if ($nano->get_param_value('user_id') == api_get_user_id() || api_is_allowed_to_edit()) {
			$file_path = $nano->load_filename_if_exists();
			//$file_path = '/var/www/rocio/courses/GREAT123/exercises/0/2/5/1/1-0-1-2-5-38.mp3';						
			if ($file_path) {				
				$info = pathinfo($file_path);				
				$user_info = api_get_user_info($nano->params['user_id']);
				$name = get_lang('Quiz').'-'.$user_info['firstname'].'-'.$user_info['lastname'].'.'.$info['extension'];
				$download = true;
				if (isset($_REQUEST['download'])  && $_REQUEST['download'] == 0) {
					$download = false;
				}				
				DocumentManager::file_send_for_download($file_path, $download);
				exit;
			}
		}		
		break;
	case 'show_audio':		
		if (!$is_nano) {
			echo $nano->return_js($_REQUEST);
		}
		echo $nano->show_audio_file($is_nano);		
		break;
	case 'delete':
		$return = $nano->delete_files();
		if ($return == 1) {
			//cant' do this because the post that nano send doesnt take into account the session
			Display::display_confirmation_message(get_lang('FileDeleted'));
		} else {
			Display::display_confirmation_message(get_lang('FileNotFound'));
		}
		break;
	case 'show_form':
		api_protect_course_script(true);
		Display::display_reduced_header();		
		echo $nano->return_js($_REQUEST);		
		echo $nano->return_form();
		break;		
    case 'save_file':    	
    	//User access same as upload.php
     	
    	$return = $nano->upload_file($is_nano);
    	
    	if ($is_nano) {
    		//nano looks for numbers
	    	if ($return == 1) {
	    		//cant' do this because the post that nano send doesnt take into account the session
	    		echo 1; //Display::display_confirmation_message(get_lang('UplUploadSucceeded'));
	    	} else {
	    		echo 0;
	    		//Display::display_warning_message(get_lang('UplUnableToSaveFileFilteredExtension'));
	    	}
    	} else {
    		Display::display_reduced_header();    	
    		echo $nano->return_js($_REQUEST);
    		//normal form
    		if ($return == 1) {
    			//cant' do this because the post that nano send doesnt take into account the session
    			$message = Display::return_message(get_lang('UplUploadSucceeded'), 'confirm');
    		} else {
    			$message = Display::return_message(get_lang('UplUnableToSaveFileFilteredExtension'), 'warning');
    		}    		
    		echo $nano->return_form($message);
    	}
        break;
    default:
        echo '';
}
exit;