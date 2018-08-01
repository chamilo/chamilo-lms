<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Repository\LegalRepository;

/**
 * @package chamilo.messages
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();
if (!api_get_configuration_value('enable_gdpr')) {
    api_not_allowed(true);
}

$userId = api_get_user_id();

$substitutionTerms = [
    'password' => get_lang('EncryptedData'),
    'salt' => get_lang('RandomData'),
    'empty' => get_lang('NoData'),
];

$propertiesToJson = UserManager::getRepository()->getPersonalDataToJson($userId, $substitutionTerms);

if (!empty($_GET['export'])) {
    //$jsonProperties = json_encode($properties);
    $filename = md5(mt_rand(0, 1000000)).'.json';
    $path = api_get_path(SYS_ARCHIVE_PATH).$filename;
    $writeResult = file_put_contents($path, $jsonProperties);
    if ($writeResult !== false) {
        DocumentManager::file_send_for_download($path, true, $filename);
        exit;
    }
}

$allowSocial = api_get_setting('allow_social_tool') === 'true';

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

// MAIN CONTENT
$personalDataContent = '<ul>';
$properties = json_decode($propertiesToJson);

foreach ($properties as $key => $value) {
    if (is_array($value) || is_object($value)) {
        /*foreach ($value as $subValue) {
            foreach ($subValue as $subSubValue) {
                var_dump($subSubValue);
                //$personalDataContent .= '<li>'.$subSubValue.'</li>';
            }
        }*/
        //skip in some cases
        /*sif (!empty($value['date'])) {
            $personalDataContent .= '<li>'.$key.': '.$value['date'].'</li>';
        } else {
            $personalDataContent .= '<li>'.$key.': '.get_lang('ComplexDataNotShown').'</li>';
        }*/
    } else {
        $personalDataContent .= '<li>'.$key.': '.$value.'</li>';
    }
}
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

//Build the final array to pass to template
$personalData = [];
$personalData['data'] = $personalDataContent;
$icon = Display::return_icon('export_excel.png', get_lang('Export'), null, ICON_SIZE_MEDIUM);
$personalData['data_export_icon'] = $icon;
$personalData['permissions'] = $termsAndConditionsAcceptance;
//$personalData['responsible'] = api_get_setting('personal_data_responsible_org');

$em = Database::getManager();
/** @var LegalRepository $legalTermsRepo */
$legalTermsRepo = $em->getRepository('ChamiloCoreBundle:Legal');
// Get data about the treatment of data
$treatmentTypes = LegalManager::getTreatmentTypeList();

foreach ($treatmentTypes as $id => $item) {
    $personalData['treatment'][$item]['title'] = get_lang('PersonalData'.ucfirst($item).'Title');
    $legalTerm = $legalTermsRepo->findOneByTypeAndLanguage($id, api_get_language_id($user_language));
    $legalTermContent = '';
    if (!empty($legalTerm[0]) && is_array($legalTerm[0])) {
        $legalTermContent = $legalTerm[0]['content'];
    }
    $personalData['treatment'][$item]['content'] = $legalTermContent;
}
$officerName = api_get_configuration_value('data_protection_officer_name');
$officerRole = api_get_configuration_value('data_protection_officer_role');
$officerEmail = api_get_configuration_value('data_protection_officer_email');
if (!empty($officerName)) {
    $personalData['officer_name'] = $officerName;
    $personalData['officer_role'] = $officerRole;
    $personalData['officer_email'] = $officerEmail;
}

$tpl = new Template(null);
if ($actions) {
    $tpl->assign('actions', Display::toolbarAction('toolbar', [$actions]));
}

// Block Social Avatar
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'messages');
if (api_get_setting('allow_social_tool') === 'true') {
    $tpl->assign('social_menu_block', $socialMenuBlock);
    $tpl->assign('personal_data', $personalData);
} else {
    $tpl->assign('social_menu_block', '');
    $tpl->assign('personal_data_block', $personalDataContent);
}

$socialLayout = $tpl->get_template('social/personal_data.tpl');
$tpl->display($socialLayout);
