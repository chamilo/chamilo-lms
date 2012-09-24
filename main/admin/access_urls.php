<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
*	@author Julio Montoya <gugli100@gmail.com>
*/

// name of the language file that needs to be included
$language_file = 'admin';
$cidReset = true;
require_once '../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

//api_protect_admin_script();
api_protect_global_admin_script();

if (!api_get_multiple_access_url()) {
	header('Location: index.php');
	exit;
}

$interbreadcrumb[] = array ("url" => 'index.php', 'name' => get_lang('PlatformAdmin'));
$tool_name = get_lang('MultipleAccessURLs');
Display :: display_header($tool_name);

$my_user_url_list = api_get_access_url_from_user(api_get_user_id());
$current_access_url_id = api_get_current_access_url_id();
$url_list = UrlManager::get_url_data();

// Actions
if (isset ($_GET['action'])) {
	if ($_GET['action'] == 'show_message')
		Display :: display_normal_message(Security::remove_XSS(stripslashes($_GET['message'])));

	$check = Security::check_token('get');
	if ($check) {
		$url_id=Database::escape_string($_GET['url_id']);

		switch ($_GET['action']) {
			case 'delete_url' :
				$result = UrlManager::delete($url_id);
				if ($result) {
					Display :: display_normal_message(get_lang('URLDeleted'));
				} else {
					Display :: display_error_message(get_lang('CannotDeleteURL'));
				}
				break;
			case 'lock' :
				UrlManager::set_url_status('lock',$url_id);
				Display :: display_normal_message(get_lang('URLInactive'));
				break;
			case 'unlock';
				UrlManager::set_url_status('unlock',$url_id);
				Display :: display_normal_message(get_lang('URLActive'));
				break;
			case 'register';
				// we are going to register the admin
				if(api_is_platform_admin()) {
					if($current_access_url_id!=-1) {
						$url_str = '';
						foreach($url_list as $my_url) {
							if (!in_array($my_url['id'],$my_user_url_list)){
								UrlManager::add_user_to_url(api_get_user_id(),$my_url['id']);
									$url_str.=$my_url['url'].' <br />';
							}
						}
						Display :: display_normal_message(get_lang('AdminUserRegisteredToThisURL').': '.$url_str.'<br />',false);
					}
				}
				break;
			}

		}
		Security::clear_token();
}

$parameters['sec_token'] = Security::get_token();

// checking if the admin is registered in all sites

$url_string='';
$my_user_url_list = api_get_access_url_from_user(api_get_user_id());
foreach($url_list as $my_url) {
	if (!in_array($my_url['id'],$my_user_url_list)){
		$url_string.=$my_url['url'].' <br />';
	}
}
if(!empty($url_string)) {
	Display :: display_warning_message(get_lang('AdminShouldBeRegisterInSite').'<br />'.$url_string,false);
}

// checking the current installation
if ($current_access_url_id==-1) {
	Display :: display_warning_message(get_lang('URLNotConfiguredPleaseChangedTo').': '.api_get_path(WEB_PATH));
} elseif(api_is_platform_admin()) {
	$quant= UrlManager::relation_url_user_exist(api_get_user_id(),$current_access_url_id);
	if ($quant==0) {
		Display :: display_warning_message('<a href="'.api_get_self().'?action=register&sec_token='.$parameters['sec_token'].'">'.get_lang('ClickToRegisterAdmin').'</a>',false);
	}
}

// action menu
echo '<div class="actions">';
echo Display::url(Display::return_icon('new_link.png',  get_lang('AddUrl'), array(), ICON_SIZE_MEDIUM),          api_get_path(WEB_CODE_PATH).'admin/access_url_edit.php');
echo Display::url(Display::return_icon('user.png',      get_lang('ManageUsers'), array(), ICON_SIZE_MEDIUM),     api_get_path(WEB_CODE_PATH).'admin/access_url_edit_users_to_url.php');
echo Display::url(Display::return_icon('course.png',    get_lang('ManageCourses'), array(), ICON_SIZE_MEDIUM),   api_get_path(WEB_CODE_PATH).'admin/access_url_edit_courses_to_url.php');
//echo Display::url(Display::return_icon('session.png',   get_lang('ManageSessions'), array(), ICON_SIZE_MEDIUM),          api_get_path(WEB_CODE_PATH).'admin/access_url_edit_sessions_to_url.php');
echo '</div>';

//$table = new SortableTable('urls', 'url_count_mask', 'get_url_data_mask',2);
$sortable_data = UrlManager::get_url_data();
$urls = array();
foreach($sortable_data as $row)  {
    //title
    $url = Display::url($row['url'], $row['url'], array('target'=>'_blank'));    
    $description = $row['description'];
    
    //Status
    $active = $row['active'];
    if ($active=='1') {
        $action='lock';
        $image='right';
    }
    if ($active=='0') {
        $action='unlock';
        $image='wrong';
    }
    // you cannot lock the default
    if ($row['id']=='1') {
        $status = Display::return_icon($image.'.gif', get_lang(ucfirst($action)));
    } else {
        $status = '<a href="access_urls.php?action='.$action.'&amp;url_id='.$row['id'].'&amp;sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon($image.'.gif', get_lang(ucfirst($action))).'</a>';
    }    
    //Actions
    $url_id = $row['id'];
    $actions = Display::url(Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL), "access_url_edit.php?url_id=$url_id");
    if ($url_id != '1') {
        $actions .= '<a href="access_urls.php?action=delete_url&amp;url_id='.$url_id.'&amp;sec_token='.$_SESSION['sec_token'].'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset))."'".')) return false;">'.Display::return_icon('delete.png', get_lang('Delete'), array(), ICON_SIZE_SMALL).'</a>';
    }    
    $urls[] = array($url, $description, $status, $actions);
}

$table = new SortableTableFromArrayConfig($urls, 2, 50, 'urls');
$table->set_additional_parameters($parameters);

//$table->set_header(0, '');
$table->set_header(0, 'URL');
$table->set_header(1, get_lang('Description'));
$table->set_header(2, get_lang('Active'));
$table->set_header(3, get_lang('Modify'), false);
$table->display();

/*		FOOTER	*/
Display :: display_footer();