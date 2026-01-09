<?php

/* For licensing terms, see /license.txt */

/**
 * Frontend script for multiple access urls.
 *
 * @author Julio Montoya <gugli100@gmail.com>
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\StateIcon;
use Chamilo\CoreBundle\Framework\Container;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;
api_protect_global_admin_script();

$httpRequest = Container::getRequest();

$translator = Container::$container->get('translator');;

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$tool_name = get_lang('Multiple access URL / Branding');
Display :: display_header($tool_name);

$my_user_url_list = api_get_access_url_from_user(api_get_user_id());
$current_access_url_id = api_get_current_access_url_id();
/** @var array<int, AccessUrl> $url_list */
$url_list = Container::getAccessUrlRepository()->findAll();

// Actions
if ($httpRequest->query->has('action')) {
    $url_id = $httpRequest->query->getInt('url_id');

    switch ($httpRequest->query->get('action')) {
        case 'delete_url':
            $ok = UrlManager::delete($url_id);
            echo Display::return_message(
                $ok ? get_lang('URL deleted.') : get_lang('Cannot delete this URL.'),
                $ok ? 'normal' : 'error'
            );

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
            if (api_is_platform_admin() && -1 != $current_access_url_id) {
                $url_str = '';
                foreach ($url_list as $u) {
                    if (!in_array($u->getId(), $my_user_url_list)) {
                        UrlManager::add_user_to_url(api_get_user_id(), $u->getId());
                        $url_str .= $u->getUrl() . '<br />';
                    }
                }
                echo Display::return_message(
                    get_lang('Admin user assigned to this URL') . ': ' . $url_str,
                    'normal',
                    false
                );
            }

            break;
    }
}

$parameters['sec_token'] = Security::get_token();
echo '<script>window.SEC_TOKEN = ' . json_encode($parameters['sec_token']) . ';</script>';
// Checking if the admin is registered in all sites
if (!api_is_admin_in_all_active_urls()) {
    // Get the list of unregistered urls
    $url_string = '';
    foreach ($url_list as $u) {
        if (!in_array($u->getId(), $my_user_url_list)) {
            $url_string .= $u->getUrl() . '<br />';
        }
    }
    echo Display::return_message(
        get_lang('Admin user should be registered here') . '<br />' . $url_string,
        'warning',
        false
    );
}

// checking the current installation
if (-1 == $current_access_url_id) {
    echo Display::return_message(
        get_lang('URL not configured yet, please add this URL :') . ' ' . api_get_path(WEB_PATH),
        'warning'
    );
} elseif (api_is_platform_admin()) {
    $quant = UrlManager::relation_url_user_exist(api_get_user_id(), $current_access_url_id);
    if (0 == $quant) {
        echo Display::return_message(
            '<a href="' . api_get_self() . '?action=register&sec_token=' . $parameters['sec_token'] . '">' .
            get_lang('Click here to register the admin into all sites') .
            '</a>',
            'warning',
            false
        );
    }
}

// 1) Find the default URL (ID = 1)
$defaultUrl = AccessUrl::DEFAULT_ACCESS_URL;
foreach ($url_list as $u) {
    if ($u->getId() === 1) {
        $defaultUrl = trim($u->getUrl());
        break;
    }
}

// 2) Tooltip message (in English, per spec)
$tooltip     = 'Adding new URLs requires you to first set the first URL to a value different than localhost.';
$isLocalhost = ($defaultUrl === AccessUrl::DEFAULT_ACCESS_URL);

// 3) Decide link href and base attributes
$attributes = ['id' => 'add-url-button'];
if ($isLocalhost) {
    // Block the link and apply a “disabled” style
    $attributes['class'] = 'ch-disabled';
    $linkHref = '#';
} else {
    $linkHref = api_get_path(WEB_CODE_PATH) . 'admin/access_url_edit.php';
}

// 4) Build the “Add URL” action
$actions = Display::url(
    Display::getMdiIcon(
        'web-plus',
        'ch-tool-icon',
        null,
        ICON_SIZE_MEDIUM,
        get_lang('Add URL')
    ),
    $linkHref,
    $attributes
);

// 5) Append the other “Manage” actions as before
if (api_get_multiple_access_url()) {
    $actions .= Display::url(
        Display::getMdiIcon('account', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Manage users')),
        api_get_path(WEB_CODE_PATH).'admin/access_url_edit_users_to_url.php'
    );
    $actions .= Display::url(
        Display::getMdiIcon('book-open-page-variant', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Manage courses')),
        api_get_path(WEB_CODE_PATH).'admin/access_url_edit_courses_to_url.php'
    );

    $userGroup = new UserGroupModel();
    if ($userGroup->getUseMultipleUrl()) {
        $actions .= Display::url(
            Display::getMdiIcon('account-group', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Manage user groups')),
            api_get_path(WEB_CODE_PATH).'admin/access_url_edit_usergroup_to_url.php'
        );
    }
    $actions .= Display::url(
        Display::getMdiIcon('file-tree-outline', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Manage course categories')),
        api_get_path(WEB_CODE_PATH).'admin/access_url_edit_course_category_to_url.php'
    );
    $actions .= Display::url(
        Display::getMdiIcon('clipboard-account', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Assign authentication sources to users')),
        "/access-url/auth-sources"
    );
}

// 6) If still localhost, show the tooltip inline next to the button
$toolbarItems = [$actions];
if ($isLocalhost) {
    $toolbarItems[] = '<span style="
        margin-left: 8px;
        font-size: 0.9em;
        color: #666;
    ">'.$tooltip.'</span>';
}

// 7) Render the toolbar
echo Display::toolbarAction('urls', $toolbarItems);

$rows = [];
foreach ($url_list as $u) {
    $link   = Display::url($u->getUrl(), $u->getUrl(), ['target' => '_blank']);
    $desc   = $u->getDescription();
    $ts     = api_get_local_time($u->getTms());
    $active = ($u->getActive() === 1);

    $iconAction = $active ? 'lock' : 'unlock';
    $stateIcon  = $active ? StateIcon::ACTIVE : StateIcon::INACTIVE;

    if ($u->getId() === 1) {
        $status = Display::getMdiIcon($stateIcon, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang(ucfirst($iconAction)));
    } else {
        $status = '<a href="access_urls.php?action=' . $iconAction . '&url_id=' . $u->getId() . '">' .
            Display::getMdiIcon($stateIcon, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang(ucfirst($iconAction))) .
            '</a>';
    }

    $rowActions = Display::url(
        Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')),
        "access_url_edit.php?url_id={$u->getId()}"
    );

    if ($u->getId() !== 1) {
        // build a link to the Vue route that will open DeleteAccessUrl.vue
        $vueHref = Container::getRouter()->generate(
            'access_url_delete',
            [
                'id' => $u->getId(),
                'url' => $u->getUrl(),
                'sec_token' => $parameters['sec_token'],
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $rowActions .= '<a href="'.$vueHref.'">' .
            Display::getMdiIcon('delete', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete')) .
            '</a>';
    }

    $rows[] = [
        $link,
        $desc,
        $status,
        $u->isLoginOnly() ? $translator->trans('Yes') : $translator->trans('No'),
        $ts,
        $rowActions,
    ];
}

$table = new SortableTableFromArrayConfig($rows, 2, 50, 'urls');
$table->set_additional_parameters($parameters);
$table->set_header(0, 'URL');
$table->set_header(1, get_lang('Description'));
$table->set_header(2, get_lang('Active'));
$table->set_header(3, get_lang('Login-only URL'));
$table->set_header(4, get_lang('Created at'));
$table->set_header(5, get_lang('Edit'), false);
$table->display();

Display::display_footer();
