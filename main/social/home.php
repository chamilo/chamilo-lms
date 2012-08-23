<?php
/* For licensing terms, see /chamilo_license.txt */
/**
 * @package chamilo.social
 * @author Julio Montoya <gugli100@gmail.com>
 */
/**
 * Initialization
 */
$language_file = array('userInfo');
$cidReset = true;

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';
require_once api_get_path(LIBRARY_PATH).'skill.lib.php';

$user_id = api_get_user_id();
$show_full_profile = true;
//social tab
$this_section = SECTION_SOCIAL;
unset($_SESSION['this_section']);//for hmtl editor repository

api_block_anonymous_users();

if (api_get_setting('allow_social_tool') !='true' ) {
    $url = api_get_path(WEB_CODE_PATH).'auth/profile.php';
    header('Location: '.$url);
    exit;
    
    api_not_allowed();
}

//fast upload image
if (api_get_setting('profile', 'picture') == 'true') {	
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

$user_info = UserManager :: get_user_info_by_id(api_get_user_id());

$social_left_content = SocialManager::show_social_menu('home');

$social_right_content .= '<div class="span5">';

$social_right_content .= '<div class="well_border">';
$social_right_content .= '<h3>'.get_lang('ContactInformation').'</h3>';

$list = array(
                array('title' => get_lang('Name'), 'content' => api_get_person_name($user_info['firstname'], $user_info['lastname'])),
                array('title' => get_lang('Email'), 'content' => $user_info['email']),
        );
// information current user		       
$social_right_content .= '<div>'.Display::description($list).'</div>';
$social_right_content .= '
    <div class="form-actions">
    <a class="btn" href="'.api_get_path(WEB_PATH).'main/auth/profile.php">
        '.get_lang('EditProfile').'
    </a>
    </div></div>';
    
    if (api_get_setting('allow_skills_tool') == 'true') {
        $social_right_content .= '<div class="well_border">';
        $skill = new Skill();
        $ranking =  $skill->get_user_skill_ranking(api_get_user_id());
        $url = api_get_path(WEB_CODE_PATH).'social/skills_ranking.php'; 
        $social_right_content .= Display::url(sprintf(get_lang('YourSkillRankingX'), $ranking), $url);
        
        $skills =  $skill->get_user_skills(api_get_user_id(), true);            

        $social_right_content .= '<h3>'.get_lang('Skills').'</h3>';
        $lis = '';
        if (!empty($skills)) {
            foreach($skills as $skill) {
                $lis .= Display::tag('li', Display::span($skill['name'], array('class'=>'label_tag skill')));
            }                
            $social_right_content .= Display::tag('ul', $lis);
        }
        $url = api_get_path(WEB_CODE_PATH).'social/skills_wheel.php';            
        $social_right_content .= Display::url(get_lang('ViewSkillsWheel'), $url);
            $social_right_content .= '</div>';
    }
    
    $social_right_content .= '</div>';
                 
            //Search box
			$social_right_content .= '<div class="span4">';
    			$social_right_content .= UserManager::get_search_form('');
    			$social_right_content .= '<br />';
    			
    			//Group box by age
    			$results = GroupPortalManager::get_groups_by_age(1,false);
    			
    			$groups_newest = array();
    			if (!empty($results)) {
        			foreach ($results as $result) {
        				$id = $result['id'];    				
        				$result['description'] = Security::remove_XSS($result['description'], STUDENT, true);
        				$result['name'] = Security::remove_XSS($result['name'], STUDENT, true);
        			    if ($result['count'] == 1 ) {
                            $result['count'] = '1 '.get_lang('Member');
                        } else {
                            $result['count'] = $result['count'].' '.get_lang('Members');
                        }
        				$group_url = "groups.php?id=$id";
        				$result['name'] = Display::url(api_ucwords(cut($result['name'],40,true)), $group_url).Display::span('<br />'.$result['count'],array('class'=>'box_description_group_member'));
        				$picture = GroupPortalManager::get_picture_group($id, $result['picture_uri'],80);
        				$result['picture_uri'] = '<img class="social-groups-image" src="'.$picture['file'].'" hspace="10" height="44" border="2" align="left" width="44" />';
        				$group_actions = '<div class="box_description_group_actions"><a href="groups.php?#tab_browse-2">'.get_lang('SeeMore').'</a></div>';
        				$groups_newest[]= array(Display::url($result['picture_uri'], $group_url), $result['name'], cut($result['description'],120,true).$group_actions);
        			}
    			}
    
    			$results = GroupPortalManager::get_groups_by_popularity(1,false);
    			
    			$groups_pop = array();
    			foreach ($results as $result) {
    				$result['description'] = Security::remove_XSS($result['description'], STUDENT, true);
    				$result['name'] = Security::remove_XSS($result['name'], STUDENT, true);
    				$id = $result['id'];
                    $group_url = "groups.php?id=$id";
    				
    				if ($result['count'] == 1 ) {
    					$result['count'] = '1 '.get_lang('Member');
    				} else {
    					$result['count'] = $result['count'].' '.get_lang('Members');
    				}				
    				$result['name'] = Display::url(api_ucwords(cut($result['name'],40,true)), $group_url).Display::span('<br />'.$result['count'],array('class'=>'box_description_group_member'));
    				$picture = GroupPortalManager::get_picture_group($id, $result['picture_uri'],80);
    				$result['picture_uri'] = '<img class="social-groups-image" src="'.$picture['file'].'" hspace="10" height="44" border="2" align="left" width="44" />';
    				$group_actions = '<div class="box_description_group_actions" ><a href="groups.php?#tab_browse-3">'.get_lang('SeeMore').'</a></div>';
    				$groups_pop[]= array(Display::url($result['picture_uri'], $group_url) , $result['name'], cut($result['description'],120,true).$group_actions);
    			}
                
    			if (count($groups_newest) > 0) {
    				$social_right_content .= '<div class="social-groups-home-title">'.get_lang('Newest').'</div>';
    				$social_right_content .= Display::return_sortable_grid('home_group', array(), $groups_newest, array('hide_navigation'=>true, 'per_page' => 100), array(), false, array(true, true, true,false));				
    			}
    
    			if (count($groups_pop) > 0) {
    				$social_right_content .= '<div class="social-groups-home-title">'.get_lang('Popular').'</div>';
    				$social_right_content .= Display::return_sortable_grid('home_group', array(), $groups_pop, array('hide_navigation'=>true, 'per_page' => 100), array(), false, array(true, true, true,true,true));
    			}
$social_right_content .= '</div>';            
            
$tpl = new Template(get_lang('SocialNetwork'));
$tpl->assign('social_left_content', $social_left_content);
$tpl->assign('social_left_menu', $social_left_menu);
$tpl->assign('social_right_content', $social_right_content);
$social_layout = $tpl->get_template('layout/social_layout.tpl');
$content = $tpl->fetch($social_layout);
$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
