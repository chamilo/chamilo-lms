<?php //$id: $
/* For licensing terms, see /chamilo_license.txt */
/**
 * @package dokeos.social
 * @author Julio Montoya <gugli100@gmail.com>
 */

$language_file = array('userInfo');
$cidReset = true;

require '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
require_once api_get_path(LIBRARY_PATH).'array.lib.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

$user_id = api_get_user_id();
$show_full_profile = true;
//social tab
$this_section = SECTION_SOCIAL;
unset($_SESSION['this_section']);//for hmtl editor repository
$interbreadcrumb[]= array ('url' => 'home.php','name' => get_lang('Social'));

api_block_anonymous_users();
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.css" type="text/css" media="projection, screen">';
$htmlHeadXtra[] = '<script type="text/javascript">

function show_icon_edit(element_html) {
	ident="#edit_image";
	$(ident).show();
}

function hide_icon_edit(element_html)  {
	ident="#edit_image";
	$(ident).hide();
}

</script>';
//fast upload image
if (api_get_setting('profile', 'picture') == 'true') {
	require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
	$form = new FormValidator('profile', 'post', 'home.php', null, array());

	//	PICTURE
	$form->addElement('file', 'picture', get_lang('AddImage'));
	$form->add_progress_bar();
	if (!empty($user_data['picture_uri'])) {
		$form->addElement('checkbox', 'remove_picture', null, get_lang('DelImage'));
	}
	$allowed_picture_types = array ('jpg', 'jpeg', 'png', 'gif');
	$form->addRule('picture', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);
	$form->addElement('style_submit_button', 'apply_change', get_lang('SaveSettings'), 'class="save"');

	if ($form->validate()) {
		$user_data = $form->getSubmitValues();
		// upload picture if a new one is provided
		if ($_FILES['picture']['size']) {
			if ($new_picture = UserManager::update_user_picture(api_get_user_id(), $_FILES['picture']['name'], $_FILES['picture']['tmp_name'])) {
				$table_user = Database :: get_main_table(TABLE_MAIN_USER);
				$sql = "UPDATE $table_user SET picture_uri = '$new_picture' WHERE user_id =  ".api_get_user_id();
				$result = Database::query($sql);
			}
		}
	}
}

Display :: display_header(get_lang('Home'));
$user_info = UserManager :: get_user_info_by_id(api_get_user_id());
$user_online_list = who_is_online(api_get_setting('time_limit_whosonline'),true);
$user_online_count = count($user_online_list);

echo '<div id="social-content">';

	echo '<div id="social-content-left">';
	//this include the social menu div
	SocialManager::show_social_menu('home');
	echo '</div>';
	echo '<div id="social-content-right">';
		echo '<div class="social-box-main1">';
			echo '<div class="social-box-left">';


			// information current user
			echo	'<div class="social-box-container1">
                    	<div>'.Display::return_icon('boxmygroups.jpg').'</div>
                        <div class="social-box-content1">';

                   echo	'<div>'.$image.'</div>';

                       echo '<div><p><strong>'.get_lang('Name').'</strong><br /><span class="social-groups-text4">'.api_get_person_name($user_info['firstname'], $user_info['lastname']).'</span></p></div>
                            <div><p><strong>'.get_lang('Email').'</strong><br /><span class="social-groups-text4">'.($user_info['email']?$user_info['email']:'').'</span></p></div>

                            <div class="box_description_group_actions" ><a href="'.api_get_path(WEB_PATH).'main/auth/profile.php">'.Display::return_icon('profile_edit.png', get_lang('EditProfile'), array('hspace'=>'6')).get_lang('EditProfile').$url_close.'</a></div>
                        </div>
					</div>';





			if (count($user_online_list) > 0) {

			echo '<div class="social-box-container1">
                    	<div>'.Display::return_icon('boxmygroups.jpg').'</div>
                        <div class="social-box-content1">
                          <div><p class="groupTex3"><strong>'.get_lang('UsersOnline').'</strong> </p></div>
                          <div>';
            echo '<center>'.SocialManager::display_user_list($user_online_list).'</center>';
            echo '</div>
                      </div>
                   </div>';
			}

			echo '</div>';

			echo '<div class="social-box-right">';
			echo '<br />';
			echo UserManager::get_search_form($query);
			echo '<br />';

			$results = GroupPortalManager::get_groups_by_age(1,false);

			$groups_newest = array();
			foreach ($results as $result) {
				$id = $result['id'];
				$url_open  = '<a href="groups.php?id='.$id.'"><span class="social-groups-text1">';
				$url_close = '</span></a>';
				$count_users_group = count(GroupPortalManager::get_users_by_group($id, false, array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_READER)));

				if ($count_users_group == 1 ) {
					$count_users_group = $count_users_group.' '.get_lang('Member');
				} else {
					$count_users_group = $count_users_group.' '.get_lang('Members');
				}

				$result['name'] = $url_open.api_ucwords(cut($result['name'],40,true)).' ('.$count_users_group.') '.$url_close.Display::return_icon('linegroups.jpg','').'<div>'.get_lang('DescriptionGroup').'</div>';
				$picture = GroupPortalManager::get_picture_group($id, $result['picture_uri'],80);
				$result['picture_uri'] = '<img class="social-groups-image" src="'.$picture['file'].'" hspace="10" height="44" border="2" align="left" width="44" />';
				$actions = '<div class="box_description_group_actions" ><a href="groups.php?view=newest">'.get_lang('SeeMore').$url_close.'</div>';
				$groups_newest[]= array($url_open.$result['picture_uri'].$url_close, $result['name'], cut($result['description'],120,true).$actions);
			}

			$results = GroupPortalManager::get_groups_by_popularity(1,false);
			$groups_pop = array();
			foreach ($results as $result) {
				$id = $result['id'];

				$url_open  = '<a href="groups.php?id='.$id.'"><span class="social-groups-text1">';
				$url_close = '</span></a>';

				if ($result['count'] == 1 ) {
					$result['count'] = $result['count'].' '.get_lang('Member');
				} else {
					$result['count'] = $result['count'].' '.get_lang('Members');
				}
				$result['name'] = $url_open.api_ucwords(cut($result['name'],40,true)).' ('.$result['count'].') '.$url_close.Display::return_icon('linegroups.jpg').'<div>'.get_lang('DescriptionGroup').'</div>';
				$picture = GroupPortalManager::get_picture_group($id, $result['picture_uri'],80);
				$result['picture_uri'] = '<img class="social-groups-image" src="'.$picture['file'].'" hspace="10" height="44" border="2" align="left" width="44" />';
				$actions = '<div class="box_description_group_actions" ><a href="groups.php?view=pop">'.get_lang('SeeMore').$url_close.'</div>';
				$groups_pop[]= array($url_open.$result['picture_uri'].$url_close, $result['name'], cut($result['description'],120,true).$actions);
			}

			if (count($groups_newest) > 0) {
				echo '<div class="social-groups-home-title">'.api_strtoupper(get_lang('Newest')).'</div>';
				Display::display_sortable_grid('home_group', array(), $groups_newest, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,false));
				echo '<br />';
			}

			if (count($groups_pop) > 0) {
				echo '<div class="social-groups-home-title">'.api_strtoupper(get_lang('Popular')).'</div>';
				Display::display_sortable_grid('home_group', array(), $groups_pop, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,true,true));
			}

			echo '</div>';
		echo '</div>';
	echo '</div>';
echo '</div>';

Display :: display_footer();
