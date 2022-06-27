<?php

/* For licensing terms, see /license.txt */
/**
 * Frontend script for multiple access urls.
 *
 * @author Julio Montoya <gugli100@gmail.com>
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

//api_protect_admin_script();
api_protect_global_admin_script();

if (!api_get_multiple_access_url()) {
    header('Location: index.php');
    exit;
}

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$tool_name = get_lang('MultipleAccessURLs');
Display::display_header($tool_name);

$my_user_url_list = api_get_access_url_from_user(api_get_user_id());
$current_access_url_id = api_get_current_access_url_id();
$url_list = UrlManager::get_url_data();

// Actions
if (isset($_GET['action'])) {
    $url_id = empty($_GET['url_id']) ? 0 : (int) $_GET['url_id'];

    switch ($_GET['action']) {
        case 'delete_url':
            $result = UrlManager::delete($url_id);
            if ($result) {
                echo Display::return_message(get_lang('URLDeleted'), 'normal');
            } else {
                echo Display::return_message(get_lang('CannotDeleteURL'), 'error');
            }
            break;
        case 'lock':
            UrlManager::set_url_status('lock', $url_id);
            echo Display::return_message(get_lang('URLInactive'), 'normal');
            break;
        case 'unlock':
            UrlManager::set_url_status('unlock', $url_id);
            echo Display::return_message(get_lang('URLActive'), 'normal');
            break;
        case 'register':
            // we are going to register the admin
            if (api_is_platform_admin()) {
                if ($current_access_url_id != -1) {
                    $url_str = '';
                    foreach ($url_list as $my_url) {
                        if (!in_array($my_url['id'], $my_user_url_list)) {
                            UrlManager::add_user_to_url(api_get_user_id(), $my_url['id']);
                            $url_str .= $my_url['url'].' <br />';
                        }
                    }
                    echo Display::return_message(
                        get_lang('AdminUserRegisteredToThisURL').': '.$url_str.'<br />',
                        'normal',
                        false
                    );
                }
            }
            break;
    }
}

$parameters['sec_token'] = Security::get_token();

// Checking if the admin is registered in all sites
$url_string = '';
$my_user_url_list = api_get_access_url_from_user(api_get_user_id());
foreach ($url_list as $my_url) {
    if (!in_array($my_url['id'], $my_user_url_list)) {
        $url_string .= $my_url['url'].' <br />';
    }
}
if (!empty($url_string)) {
    echo Display::return_message(
        get_lang('AdminShouldBeRegisterInSite').'<br />'.$url_string,
        'warning',
        false
    );
}

// checking the current installation
if ($current_access_url_id == -1) {
    echo Display::return_message(
        get_lang('URLNotConfiguredPleaseChangedTo').': '.api_get_path(WEB_PATH),
        'warning'
    );
} elseif (api_is_platform_admin()) {
    $quant = UrlManager::relation_url_user_exist(
        api_get_user_id(),
        $current_access_url_id
    );
    if ($quant == 0) {
        echo Display::return_message(
            '<a href="'.api_get_self().'?action=register&sec_token='.$parameters['sec_token'].'">'.
            get_lang('ClickToRegisterAdmin').'</a>',
            'warning',
            false
        );
    }
}

// action menu
echo '<div class="actions">';
echo Display::url(
    Display::return_icon('new_link.png', get_lang('AddUrl'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_CODE_PATH).'admin/access_url_edit.php'
);
echo Display::url(
    Display::return_icon('user.png', get_lang('ManageUsers'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_CODE_PATH).'admin/access_url_edit_users_to_url.php'
);
echo Display::url(
    Display::return_icon('course.png', get_lang('ManageCourses'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_CODE_PATH).'admin/access_url_edit_courses_to_url.php'
);

$userGroup = new UserGroup();
if ($userGroup->getUseMultipleUrl()) {
    echo Display::url(
        Display::return_icon('class.png', get_lang('ManageUserGroup'), [], ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH).'admin/access_url_edit_usergroup_to_url.php'
    );
}

echo Display::url(
    Display::return_icon('folder.png', get_lang('ManageCourseCategories'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_CODE_PATH).'admin/access_url_edit_course_category_to_url.php'
);

echo '</div>';

$data = UrlManager::get_url_data();
$urls = [];
foreach ($data as $row) {
    // Title
    $url = Display::url($row['url'], $row['url'], ['target' => '_blank']);
    $description = $row['description'];
    $createdAt = api_get_local_time($row['tms']);

    //Status
    $active = $row['active'];
    $action = 'unlock';
    $image = 'wrong';
    if ($active == '1') {
        $action = 'lock';
        $image = 'right';
    }
    // you cannot lock the default
    if ($row['id'] == '1') {
        $status = Display::return_icon($image.'.gif', get_lang(ucfirst($action)));
    } else {
        $status = '<a href="access_urls.php?action='.$action.'&amp;url_id='.$row['id'].'">'.
            Display::return_icon($image.'.gif', get_lang(ucfirst($action))).'</a>';
    }
    // Actions
    $url_id = $row['id'];
    $actions = Display::url(
        Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL),
        "access_url_edit.php?url_id=$url_id"
    );
    if ($url_id != '1') {
        $actions .= '<a href="access_urls.php?action=delete_url&amp;url_id='.$url_id.'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES, $charset))."'".')) return false;">'.
            Display::return_icon('delete.png', get_lang('Delete'), [], ICON_SIZE_SMALL).'</a>';
    }
    $urls[] = [$url, $description, $status, $createdAt, $actions];
}

$table = new SortableTableFromArrayConfig($urls, 2, 50, 'urls');
$table->set_additional_parameters($parameters);
$table->set_header(0, 'URL');
$table->set_header(1, get_lang('Description'));
$table->set_header(2, get_lang('Active'));
$table->set_header(3, get_lang('CreatedAt'));
$table->set_header(4, get_lang('Modify'), false);
$table->display();

Display::display_footer();
