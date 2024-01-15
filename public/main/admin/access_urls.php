<?php

/* For licensing terms, see /license.txt */

/**
 * Frontend script for multiple access urls.
 *
 * @author Julio Montoya <gugli100@gmail.com>
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */

use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Chamilo\CoreBundle\Component\Utils\ActionIcon;
use Chamilo\CoreBundle\Component\Utils\StateIcon;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

//api_protect_admin_script();
api_protect_global_admin_script();

if (!api_get_multiple_access_url()) {
    header('Location: index.php');
    exit;
}

$httpRequest = HttpRequest::createFromGlobals();

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$tool_name = get_lang('Multiple access URL / Branding');
Display :: display_header($tool_name);

$my_user_url_list = api_get_access_url_from_user(api_get_user_id());
$current_access_url_id = api_get_current_access_url_id();
$url_list = UrlManager::get_url_data();

// Actions
if ($httpRequest->query->has('action')) {
    $url_id = $httpRequest->query->getInt('url_id');

    switch ($httpRequest->query->get('action')) {
        case 'delete_url':
            $result = UrlManager::delete($url_id);
            if ($result) {
                echo Display::return_message(get_lang('URL deleted.'), 'normal');
            } else {
                echo Display::return_message(get_lang('Cannot delete this URL.'), 'error');
            }

            break;
        case 'lock':
            UrlManager::set_url_status('lock', $url_id);
            echo Display::return_message(get_lang('The URL has been disabled'), 'normal');

            break;
        case 'unlock':
            UrlManager::set_url_status('unlock', $url_id);
            echo Display::return_message(get_lang('The URL has been enabled'), 'normal');

            break;
        case 'register':
            // we are going to register the admin
            if (api_is_platform_admin()) {
                if (-1 != $current_access_url_id) {
                    $url_str = '';
                    foreach ($url_list as $my_url) {
                        if (!in_array($my_url['id'], $my_user_url_list)) {
                            UrlManager::add_user_to_url(api_get_user_id(), $my_url['id']);
                            $url_str .= $my_url['url'].' <br />';
                        }
                    }
                    echo Display::return_message(
                        get_lang('Admin user assigned to this URL').': '.$url_str.'<br />',
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
        get_lang('Admin user should be registered here').'<br />'.$url_string,
        'warning',
        false
    );
}

// checking the current installation
if (-1 == $current_access_url_id) {
    echo Display::return_message(
        get_lang('URL not configured yet, please add this URL :').': '.api_get_path(WEB_PATH),
        'warning'
    );
} elseif (api_is_platform_admin()) {
    $quant = UrlManager::relation_url_user_exist(
        api_get_user_id(),
        $current_access_url_id
    );
    if (0 == $quant) {
        echo Display::return_message(
            '<a href="'.api_get_self().'?action=register&sec_token='.$parameters['sec_token'].'">'.
            get_lang('Click here to register the admin into all sites').'</a>',
            'warning',
            false
        );
    }
}

$actions = Display::url(
    Display::getMdiIcon('new_link', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add URL')),
    api_get_path(WEB_CODE_PATH).'admin/access_url_edit.php'
);
$actions .= Display::url(
    Display::getMdiIcon('user', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Manage users')),
    api_get_path(WEB_CODE_PATH).'admin/access_url_edit_users_to_url.php'
);
$actions .= Display::url(
    Display::getMdiIcon('course', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Manage courses')),
    api_get_path(WEB_CODE_PATH).'admin/access_url_edit_courses_to_url.php'
);

$userGroup = new UserGroupModel();
if ($userGroup->getUseMultipleUrl()) {
    $actions .= Display::url(
        Display::getMdiIcon('class', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Manage user groups')),
        api_get_path(WEB_CODE_PATH).'admin/access_url_edit_usergroup_to_url.php'
    );
}

$actions .= Display::url(
    Display::getMdiIcon('folder', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Manage course categories')),
    api_get_path(WEB_CODE_PATH).'admin/access_url_edit_course_category_to_url.php'
);

echo Display::toolbarAction('urls', [$actions]);

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
    $image = StateIcon::ACTIVE;
    if ('1' == $active) {
        $action = 'lock';
        $image = StateIcon::ACTIVE;
    }
    // you cannot lock the default
    if ('1' == $row['id']) {
        $status = Display::getMdiIcon($image, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang(ucfirst($action)));
    } else {
        $status = '<a href="access_urls.php?action='.$action.'&amp;url_id='.$row['id'].'">'.
            Display::getMdiIcon($image, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang(ucfirst($action))).'</a>';
    }
    // Actions
    $url_id = $row['id'];
    $actions = Display::url(
        Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')),
        "access_url_edit.php?url_id=$url_id"
    );
    if ('1' != $url_id) {
        $actions .= '<a href="access_urls.php?action=delete_url&amp;url_id='.$url_id.'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('Please confirm your choice'), ENT_QUOTES))."'".')) return false;">'.
            Display::getMdiIcon('delete', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete')).'</a>';
    }
    $urls[] = [$url, $description, $status, $createdAt, $actions];
}

$table = new SortableTableFromArrayConfig($urls, 2, 50, 'urls');
$table->set_additional_parameters($parameters);
$table->set_header(0, 'URL');
$table->set_header(1, get_lang('Description'));
$table->set_header(2, get_lang('active'));
$table->set_header(3, get_lang('Created at'));
$table->set_header(4, get_lang('Edit'), false);
$table->display();

Display :: display_footer();
