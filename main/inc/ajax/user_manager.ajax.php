<?php
/* For licensing terms, see /chamilo_license.txt */
/**
 * Responses to AJAX calls 
 */
$language_file = array('admin', 'registration', 'userInfo');
require_once '../global.inc.php';
$action = $_GET['a'];

switch ($action) {	
    case 'get_user_popup':        
        
        $user_info = api_get_user_info($_REQUEST['user_id']);
        //var_dump($user_info);
        echo '<div class="well">';
            echo '<div class="row">';
            echo '<div class="span2">';
            echo '<img src="'.$user_info['avatar'].'" /> ';
            echo '</div>';
            echo '<div class="span2">';
            
            echo '<h3>'.$user_info['complete_name'].'</h3> '.$user_info['mail'].' '.$user_info['official_code'];
            echo '<br/><br/><a class="btn" href="'.api_get_path(WEB_CODE_PATH).'social/profile.php?u='.$user_info['user_id'].'">'.get_lang('ViewSharedProfile').'</a>';
            echo '</div>';
            echo '</div>';
        echo '</div>';
        break;
    case 'user_id_exists':
        if (api_is_anonymous()) {
            echo '';    
        } else {   
            if (UserManager::is_user_id_valid($_GET['user_id'])) {
                echo 1;
            } else {
                echo 0;
            }
        }
        break;        
	case 'search_tags':
        if (api_is_anonymous()) {
			echo '';	
		} else {		
			$field_id = intval($_GET['field_id']);
			$tag = $_GET['tag'];
			echo UserManager::get_tags($tag, $field_id,'json','10');
		}
        break;
	case 'generate_api_key':
		if (api_is_anonymous()) {
			echo '';
		} else {		
			$array_list_key = array();
			$user_id = api_get_user_id();
			$api_service = 'dokeos';
			$num = UserManager::update_api_key($user_id, $api_service);
			$array_list_key = UserManager::get_api_keys($user_id, $api_service);
			?>			
			<div class="row">
				<div class="label"><?php echo get_lang('MyApiKey'); ?></div>
				<div class="formw">
				<input type="text" name="api_key_generate" id="id_api_key_generate" size="40" value="<?php echo $array_list_key[$num]; ?>"/>
				</div>
			</div>
			<?php			
		}
        break;
	case 'active_user':
		if (api_is_platform_admin() && api_global_admin_can_edit_admin($_GET['user_id'])) {
            
			$user_id = intval($_GET['user_id']);
			$status  = intval($_GET['status']);
            
			if (!empty($user_id)) {
				$user_table = Database :: get_main_table(TABLE_MAIN_USER);
				$sql="UPDATE $user_table SET active='".$status."' WHERE user_id='".$user_id."'";
				$result = Database::query($sql);
                
				//Send and email if account is active
				if ($status == 1) {
					$user_info = api_get_user_info($user_id);					
					$recipient_name = api_get_person_name($user_info['firstname'], $user_info['lastname'], null, PERSON_NAME_EMAIL_ADDRESS);
					$emailsubject = '['.api_get_setting('siteName').'] '.get_lang('YourReg').' '.api_get_setting('siteName');
					$email_admin = api_get_setting('emailAdministrator');
					$sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
					$emailbody=get_lang('Dear')." ".stripslashes($recipient_name).",\n\n";					
					
					$emailbody.=sprintf(get_lang('YourAccountOnXHasJustBeenApprovedByOneOfOurAdministrators'), api_get_setting('siteName'))."\n";
					$emailbody.=sprintf(get_lang('YouCanNowLoginAtXUsingTheLoginAndThePasswordYouHaveProvided'), api_get_path(WEB_PATH)).",\n\n";
					$emailbody.=get_lang('HaveFun')."\n\n";
					//$emailbody.=get_lang('Problem'). "\n\n". get_lang('Formula');
					$emailbody.=api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))."\n". get_lang('Manager'). " ".api_get_setting('siteName')."\nT. ".api_get_setting('administratorTelephone')."\n" .get_lang('Email') ." : ".api_get_setting('emailAdministrator');
					$result = api_mail($recipient_name, $user_info['mail'], $emailsubject, $emailbody, $sender_name, $email_admin);					
				}
                echo $status;
            }				
		} else {
			echo '-1';
		}
        break;
	default:
		echo '';
}
exit;