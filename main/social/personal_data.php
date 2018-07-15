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

$userId = api_get_user_id();

$entityManager = Database::getManager();
/** @var UserRepository $repository */
$repository = $entityManager->getRepository('ChamiloUserBundle:User');
/** @var User $user */
$user = $repository->find($userId);
$properties = $user->getPersonalData($entityManager);

if (!empty($_GET['export'])) {
    $jsonProperties = json_encode($properties);
    $filename = md5(rand(0,1000000)).'.json';
    $path = api_get_path(SYS_ARCHIVE_PATH).$filename;
    $writeResult = file_put_contents($path, $jsonProperties);
    if ($writeResult !== false) {
        DocumentManager::file_send_for_download($path, true, $filename);
        exit;
    }
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
$socialMenuBlock = '';
if ($allowSocial) {
    // Block Social Menu
    $socialMenuBlock = SocialManager::show_social_menu('personal-data');
}

//MAIN CONTENT
$personalDataContent = '<ul>';
foreach ($properties as $key => $value) {
    if (is_array($value) || is_object($value)) {
        //skip in some cases
        if (!empty($value['date'])) {
            $personalDataContent .= '<li>'.$key.': '.$value['date'].'</li>';
        } else {
            $personalDataContent .= '<li>'.$key.': '.get_lang('ComplexDataNotShown').'</li>';
        }
    } else {
        $personalDataContent .= '<li>'.$key.': '.$value.'</li>';
    }
}
$jsonProperties = json_encode($properties);
$personalDataContent .= '</ul>';

// Check terms acceptation
$termsAndConditionsAcceptance = [];
$termsAndConditionsAcceptance['accepted'] = false;
if (api_get_setting('allow_terms_conditions') === 'true') {
    $extraFieldValue = new ExtraFieldValue('user');
    $value = $extraFieldValue->get_values_by_handler_and_field_variable(
        $userId,
        'legal_accept'
    );
    $termsAndConditionsAcceptance['icon'] = Display::return_icon('accept_na.png', get_lang('NotAccepted'));
    $termsAndConditionsAcceptance['last_login'] = $user->getLastLogin();
    if (isset($value['value']) && !empty($value['value'])) {
        list($legalId, $legalLanguageId, $legalTime) = explode(':', $value['value']);
        $termsAndConditionsAcceptance['accepted'] = true;
        $termsAndConditionsAcceptance['icon'] = Display::return_icon('accept.png', get_lang('LegalAgreementAccepted'));
        $termsAndConditionsAcceptance['date'] = api_get_local_time($legalTime);
        // @TODO add action handling for button
        $termsAndConditionsAcceptance['button'] = Display::url(
            get_lang('DeleteLegal'),
            api_get_self().'?action=delete_legal&user_id='.$userId,
            ['class' => 'btn btn-danger btn-xs']
        );
    } else {
        // @TODO add action handling for button
        $termsAndConditionsAcceptance['button'] = Display::url(
            get_lang('SendLegal'),
            api_get_self().'?action=send_legal&user_id='.$userId,
            ['class' => 'btn btn-primary btn-xs']
        );
    }
    $termsAndConditionsAcceptance['label'] = get_lang('LegalAccepted');
} else {
    $termsAndConditionsAcceptance['label'] = get_lang('NoTermsAndConditionsAvailable');
}

$personalData = [];
$personalData['data'] = $personalDataContent;
$icon = Display::return_icon('export_excel.png', get_lang('Export'), null,ICON_SIZE_MEDIUM);
$personalData['data_export_icon'] = $icon;
$personalData['permissions'] = $termsAndConditionsAcceptance;
$tpl = new Template(null);

if ($actions) {
    $tpl->assign('actions', Display::toolbarAction('toolbar', [$actions]));
}
// Block Social Avatar
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'messages');
if (api_get_setting('allow_social_tool') == 'true') {
    $tpl->assign('social_menu_block', $socialMenuBlock);
    $tpl->assign('personal_data', $personalData);
    $social_layout = $tpl->get_template('social/personal_data.tpl');
    $tpl->display($social_layout);
} else {
    $tpl->assign('social_menu_block', '');
    $tpl->assign('personal_data_block', $personalDataContent);
    $social_layout = $tpl->get_template('social/personal_data.tpl');
    $tpl->display($social_layout);
}
