<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.messages
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
use Chamilo\UserBundle\Entity\User;
use Chamilo\UserBundle\Repository\UserRepository;

api_block_anonymous_users();
if (!api_get_configuration_value('enable_gdpr')) {
    api_not_allowed(true);
}

$allowSocial = api_get_setting('allow_social_tool') == 'true';

$nameTools = get_lang('PersonalDataReport');
$show_message = null;

if ($allowSocial) {
    $this_section = SECTION_SOCIAL;
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_PATH).'main/social/home.php',
        'name' => get_lang('SocialNetwork'),
    ];
} else {
    $this_section = SECTION_MYPROFILE;
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_PATH).'main/auth/profile.php',
        'name' => get_lang('Profile'),
    ];
}

$interbreadcrumb[] = ['url' => '#', 'name' => get_lang('PersonalDataReport')];

$actions = '';

// LEFT CONTENT
$social_menu_block = '';
if ($allowSocial) {
    // Block Social Menu
    $social_menu_block = SocialManager::show_social_menu('personal-data');
}

//MAIN CONTENT
$social_right_content = '';
$social_right_content = '<ul>';
$entityManager = Database::getManager();
/** @var UserRepository $repository */
$repository = $entityManager->getRepository('ChamiloUserBundle:User');
/** @var User $user */
$user = $repository->find(api_get_user_id());

$properties = $user->getPersonalData($entityManager);
foreach ($properties as $key => $value) {
    if (is_array($value) || is_object($value)) {
        //skip
    } else {
        $social_right_content .= '<li>'.$key.': '.$value.'</li>';
    }
}
$social_right_content .= '</ul>';

$personal_data = [];
$personal_data['data'] = $social_right_content;

$tpl = new Template(null);

if ($actions) {
    $tpl->assign('actions', Display::toolbarAction('toolbar', [$actions]));
}
// Block Social Avatar
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'messages');
if (api_get_setting('allow_social_tool') == 'true') {
    $tpl->assign('social_menu_block', $social_menu_block);
    $tpl->assign('personal_data', $personal_data);
    $social_layout = $tpl->get_template('social/personal_data.tpl');
    $tpl->display($social_layout);
} else {
    $tpl->assign('social_menu_block', '');
    $tpl->assign('personal_data_block', $social_right_content);
    $social_layout = $tpl->get_template('social/personal_data.tpl');
    $tpl->display($social_layout);
}
