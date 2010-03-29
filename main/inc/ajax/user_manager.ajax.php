<?php
/* For licensing terms, see /chamilo_license.txt */
/**
 * Responses to AJAX calls 
 */
require_once '../global.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
$action = $_GET['a'];

switch ($action) {	
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
		
	default:
		echo '';
}
exit;
?>