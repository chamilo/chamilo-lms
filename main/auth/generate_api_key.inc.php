<?php
require '../inc/global.inc.php';
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
$array_list_key=array();
$user_id=api_get_user_id();
$api_service='dokeos';
$num=UserManager::update_api_key($user_id,$api_service);
$array_list_key=UserManager::get_api_keys($user_id,$api_service);
?>
<div class="row">
	<div class="label"><?php echo get_lang('MyApiKeyGenerate') ?></div>
	<div class="formw">
	<input type="text" name="api_key_generate" id="id_api_key_generate" size="40" value="<?php echo $array_list_key[$num]?>"/>
	</div>
</div>