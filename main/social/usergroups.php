<?php
/* For licensing terms, see /chamilo_license.txt */
/**
 * @package chamilo.social
 * @author Julio Montoya <gugli100@gmail.com>
 */

$language_file = array('userInfo');
$cidReset=true;
require '../inc/global.inc.php';

require_once api_get_path(LIBRARY_PATH).'social.lib.php';
require_once api_get_path(LIBRARY_PATH).'usergroup.lib.php';

api_block_anonymous_users();

$this_section = SECTION_SOCIAL;

//jquery thickbox already called from main/inc/header.inc.php
$htmlHeadXtra[] = '<script type="text/javascript">
		
</script>';

$interbreadcrumb[]= array ('url' =>'profile.php','name' => get_lang('Social'));
$interbreadcrumb[]= array ('url' =>'#','name' => get_lang('Groups'));

Display :: display_header($tool_name, 'Groups');

echo '<div id="social-content">';

	echo '<div id="social-content-left">';	
		//this include the social menu div
		SocialManager::show_social_menu('friends');	
	echo '</div>';
	echo '<div id="social-content-right">';
	
$language_variable	= api_xml_http_response_encode(get_lang('Groups'));
$user_id	= api_get_user_id();

$list_path_friends	= array();
$user_id	= api_get_user_id();
$name_search= Security::remove_XSS($_POST['search_name_q']);
$number_friends = 0;

if (isset($name_search) && $name_search!='undefined') {
	$friends = SocialManager::get_friends($user_id,null,$name_search);
} else {
	$friends = SocialManager::get_friends($user_id);
}

$usergroup = new Usergroup();
$usergroup_list = $usergroup->get_usergroup_by_user(api_get_user_id());
if (!empty($usergroup_list)) {
    echo Display::tag('h2',get_lang('MyGroup'));
    foreach($usergroup_list as $group_id) {
    	$data = $usergroup->get($group_id);
        echo Display::tag('div',$data['name']);
    }
}
Display :: display_footer();