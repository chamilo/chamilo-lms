<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Repository\LegalRepository;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_set_more_memory_and_time_limits();
api_block_anonymous_users();

if (api_get_configuration_value('disable_gdpr')) {
    api_not_allowed(true);
}

$userId = api_get_user_id();
$userInfo = api_get_user_info($userId);

if (empty($userInfo)) {
    api_not_allowed(true);
}

$substitutionTerms = [
    'password' => get_lang('EncryptedData'),
    'salt' => get_lang('RandomData'),
    'empty' => get_lang('NoData'),
];

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$formToString = '';

if (api_get_setting('allow_terms_conditions') === 'true') {
    $form = new FormValidator('delete_term', 'post', api_get_self().'?action=delete_legal&user_id='.$userId);
    $form->addHtml(Display::return_message(get_lang('WhyYouWantToDeleteYourLegalAgreement'), 'normal', false));
    $form->addTextarea('explanation', [get_lang('DeleteLegal'), get_lang('ExplanationDeleteLegal')], [], true);
    $form->addHidden('action', 'delete_legal');
    $form->addButtonSave(get_lang('DeleteLegal'));
    $formToString = $form->returnForm();

    $formDelete = new FormValidator('delete_account', 'post', api_get_self().'?action=delete_account&user_id='.$userId);
    $formDelete->addTextarea(
        'explanation',
        [get_lang('DeleteAccount'), get_lang('ExplanationDeleteAccount')],
        [],
        true
    );
    $formDelete->addHidden('action', 'delete_account');
    $formDelete->addButtonDelete(get_lang('DeleteAccount'));
    $formToString .= $formDelete->returnForm();
}
switch ($action) {
    case 'send_legal':
        $language = api_get_interface_language();
        $language = api_get_language_id($language);
        $terms = LegalManager::get_last_condition($language);
        if (!$terms) {
            //look for the default language
            $language = api_get_setting('platformLanguage');
            $language = api_get_language_id($language);
            $terms = LegalManager::get_last_condition($language);
        }

        $legalAcceptType = $terms['version'].':'.$terms['language_id'].':'.time();

        Event::addEvent(
            LOG_TERM_CONDITION_ACCEPTED,
            LOG_USER_OBJECT,
            api_get_user_info($userId),
            api_get_utc_datetime()
        );

        LegalManager::sendEmailToUserBoss($userId, $legalAcceptType);

        Display::addFlash(Display::return_message(get_lang('Saved')));
        header('Location: '.api_get_self());
        exit;
        break;
    case 'delete_account':
        if ($formDelete->validate()) {
            $explanation = $formDelete->getSubmitValue('explanation');
            UserManager::createDataPrivacyExtraFields();

            UserManager::update_extra_field_value(
                $userId,
                'request_for_delete_account',
                1
            );
            UserManager::update_extra_field_value(
                $userId,
                'request_for_delete_account_justification',
                $explanation
            );

            Display::addFlash(Display::return_message(get_lang('Saved')));
            Event::addEvent(
                LOG_USER_DELETE_ACCOUNT_REQUEST,
                LOG_USER_OBJECT,
                $userInfo
            );

            $url = api_get_path(WEB_CODE_PATH).'admin/user_list_consent.php';
            $link = Display::url($url, $url);
            $subject = get_lang('RequestForAccountDeletion');
            $content = sprintf(
                get_lang('TheUserXAskedForAccountDeletionWithJustificationXGoHereX'),
                $userInfo['complete_name'],
                $explanation,
                $link
            );

            $email = api_get_configuration_value('data_protection_officer_email');
            if (!empty($email)) {
                api_mail_html('', $email, $subject, $content);
            } else {
                MessageManager::sendMessageToAllAdminUsers(api_get_user_id(), $subject, $content);
            }
            header('Location: '.api_get_self());
            exit;
        }
        break;
    case 'delete_legal':
        if ($form->validate()) {
            $explanation = $form->getSubmitValue('explanation');

            UserManager::createDataPrivacyExtraFields();
            UserManager::update_extra_field_value(
                $userId,
                'request_for_legal_agreement_consent_removal',
                1
            );

            UserManager::update_extra_field_value(
                $userId,
                'request_for_legal_agreement_consent_removal_justification',
                $explanation
            );

            Display::addFlash(Display::return_message(get_lang('Sent')));

            Event::addEvent(
                LOG_USER_REMOVED_LEGAL_ACCEPT,
                LOG_USER_OBJECT,
                $userInfo
            );

            $url = api_get_path(WEB_CODE_PATH).'admin/user_list_consent.php';
            $link = Display::url($url, $url);
            $subject = get_lang('RequestForLegalConsentWithdrawal');
            $content = sprintf(
                get_lang('TheUserXAskedLegalConsentWithdrawalWithJustificationXGoHereX'),
                $userInfo['complete_name'],
                $explanation,
                $link
            );

            $email = api_get_configuration_value('data_protection_officer_email');
            if (!empty($email)) {
                api_mail_html('', $email, $subject, $content);
            } else {
                MessageManager::sendMessageToAllAdminUsers(api_get_user_id(), $subject, $content);
            }
            header('Location: '.api_get_self());
            exit;
        }
        break;
}

$propertiesToJson = UserManager::getRepository()->getPersonalDataToJson($userId, $substitutionTerms);

if (!empty($_GET['export'])) {
    $filename = md5(mt_rand(0, 1000000)).'.json';
    $path = api_get_path(SYS_ARCHIVE_PATH).$filename;
    $writeResult = file_put_contents($path, $propertiesToJson);
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

// LEFT CONTENT
$socialMenuBlock = '';
if ($allowSocial) {
    // Block Social Menu
    $socialMenuBlock = SocialManager::show_social_menu('personal-data');
}

// MAIN CONTENT
$personalDataContent = '<ul>';
$properties = json_decode($propertiesToJson);
$webCoursePath = api_get_path(WEB_COURSE_PATH);
$showWarningMessage = false;
foreach ($properties as $key => $value) {
    if (is_array($value) || is_object($value)) {
        switch ($key) {
            case 'classes':
                foreach ($value as $category => $subValue) {
                    $categoryName = 'Social group';
                    if ($category == 0) {
                        $categoryName = 'Class';
                    }
                    $personalDataContent .= '<li class="advanced_options" id="personal-data-list-'.$category.'">';
                    $personalDataContent .= '<u>'.$categoryName.'</u> &gt;</li>';
                    $personalDataContent .= '<ul id="personal-data-list-'.$category.'_options" style="display:none;">';
                    if (empty($subValue)) {
                        $personalDataContent .= '<li>'.get_lang('NoData').'</li>';
                    } else {
                        foreach ($subValue as $subSubValue) {
                            $personalDataContent .= '<li>'.Security::remove_XSS($subSubValue).'</li>';
                        }
                    }
                    $personalDataContent .= '</ul>';
                }
                break;
            case 'extraFields':
                $personalDataContent .= '<li>'.$key.': </li><ul>';
                if (empty($value)) {
                    $personalDataContent .= '<li>'.get_lang('NoData').'</li>';
                } else {
                    foreach ($value as $subValue) {
                        if (is_array($subValue->value)) {
                            // tags fields can be stored as arrays
                            $val = json_encode(Security::remove_XSS($subValue->value));
                        } else {
                            $val = Security::remove_XSS($subValue->value);
                        }
                        $personalDataContent .= '<li>'.$subValue->variable.': '.$val.'</li>';
                    }
                }
                $personalDataContent .= '</ul>';
                break;
            case 'dropBoxSentFiles':
                foreach ($value as $category => $subValue) {
                    $personalDataContent .= '<li class="advanced_options" id="personal-data-list-'.$category.'">';
                    $personalDataContent .= '<u>'.get_lang($category).'</u> &gt;</li>';
                    $personalDataContent .= '<ul id="personal-data-list-'.$category.'_options" style="display:none;">';
                    if (empty($subValue)) {
                        $personalDataContent .= '<li>'.get_lang('NoData').'</li>';
                    } else {
                        if (count($subValue) === 1000) {
                            $showWarningMessage = true;
                        }
                        foreach ($subValue as $subSubValue) {
                            if ($category === 'DocumentsAdded') {
                                $documentLink = Display::url(
                                    $subSubValue->code_path,
                                    $webCoursePath.$subSubValue->directory.'/document'.$subSubValue->path
                                );
                                $personalDataContent .= '<li>'.$documentLink.'</li>';
                            } else {
                                $personalDataContent .= '<li>'.Security::remove_XSS($subSubValue).'</li>';
                            }
                        }
                    }
                    $personalDataContent .= '</ul>';
                }

                break;
            case 'portals':
            case 'roles':
            case 'achievedSkills':
            case 'sessionAsGeneralCoach':
            case 'courses':
            case 'groupNames':
            case 'groups':
                $personalDataContent .= '<li>'.$key.': </li><ul>';
                if (empty($subValue)) {
                    $personalDataContent .= '<li>'.get_lang('NoData').'</li>';
                } else {
                    foreach ($value as $subValue) {
                        $personalDataContent .= '<li>'.Security::remove_XSS($subValue).'</li>';
                    }
                }
                $personalDataContent .= '</ul>';
                break;
            case 'sessionCourseSubscriptions':
                $personalDataContent .= '<li>'.$key.': </li><ul>';
                foreach ($value as $session => $courseList) {
                    $personalDataContent .= '<li>'.$session.'<ul>';
                    if (empty($courseList)) {
                        $personalDataContent .= '<li>'.get_lang('NoData').'</li>';
                    } else {
                        foreach ($courseList as $course) {
                            $personalDataContent .= '<li>'.$course.'</li>';
                        }
                    }
                    $personalDataContent .= '</ul>';
                }
                $personalDataContent .= '</ul>';
                break;
            default:
                //var_dump($key);
                break;
        }

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
        $personalDataContent .= '<li>'.$key.': '.Security::remove_XSS($value).'</li>';
    }
}
$personalDataContent .= '</ul>';

// Check terms acceptation
$permissionBlock = '';
if (api_get_setting('allow_terms_conditions') === 'true') {
    $extraFieldValue = new ExtraFieldValue('user');
    $value = $extraFieldValue->get_values_by_handler_and_field_variable(
        $userId,
        'legal_accept'
    );
    $permissionBlock .= Display::return_icon('accept_na.png', get_lang('NotAccepted'));
    if (isset($value['value']) && !empty($value['value'])) {
        list($legalId, $legalLanguageId, $legalTime) = explode(':', $value['value']);
        $permissionBlock = '<h4>'.get_lang('CurrentStatus').'</h4>'.
            get_lang('LegalAgreementAccepted').' '.Display::return_icon('accept.png', get_lang('LegalAgreementAccepted'), [], ICON_SIZE_TINY).
            '<br />';
        $permissionBlock .= get_lang('Date').': '.api_get_local_time($legalTime).'<br /><br />';
        $permissionBlock .= $formToString;

    /*$permissionBlock .= Display::url(
        get_lang('DeleteLegal'),
        api_get_self().'?action=delete_legal&user_id='.$userId,
        ['class' => 'btn btn-danger btn-xs']
    );*/
    } else {
        // @TODO add action handling for button
        $permissionBlock .= Display::url(
            get_lang('SendLegal'),
            api_get_self().'?action=send_legal&user_id='.$userId,
            ['class' => 'btn btn-primary btn-xs']
        );
    }
} else {
    $permissionBlock .= get_lang('NoTermsAndConditionsAvailable');
}

//Build the final array to pass to template
$personalData = [];
$personalData['data'] = $personalDataContent;
//$personalData['responsible'] = api_get_setting('personal_data_responsible_org');

$em = Database::getManager();
/** @var LegalRepository $legalTermsRepo */
$legalTermsRepo = $em->getRepository('ChamiloCoreBundle:Legal');
// Get data about the treatment of data
$treatmentTypes = LegalManager::getTreatmentTypeList();

/*foreach ($treatmentTypes as $id => $item) {
    $personalData['treatment'][$item]['title'] = get_lang('PersonalData'.ucfirst($item).'Title');
    $legalTerm = $legalTermsRepo->findOneByTypeAndLanguage($id, api_get_language_id($user_language));
    $legalTermContent = '';
    if (!empty($legalTerm[0]) && is_array($legalTerm[0])) {
        $legalTermContent = $legalTerm[0]['content'];
    }
    $personalData['treatment'][$item]['content'] = $legalTermContent;
}*/

$officerName = api_get_configuration_value('data_protection_officer_name');
$officerRole = api_get_configuration_value('data_protection_officer_role');
$officerEmail = api_get_configuration_value('data_protection_officer_email');
if (!empty($officerName)) {
    $personalData['officer_name'] = $officerName;
    $personalData['officer_role'] = $officerRole;
    $personalData['officer_email'] = $officerEmail;
}

$tpl = new Template(null);

$actions = Display::url(
    Display::return_icon('excel.png', get_lang('Export'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_CODE_PATH).'social/personal_data.php?export=1'
);

$tpl->assign('actions', Display::toolbarAction('toolbar', [$actions]));

$termLink = '';
if (api_get_setting('allow_terms_conditions') === 'true') {
    $url = api_get_path(WEB_CODE_PATH).'social/terms.php';
    $termLink = Display::url(get_lang('ReadTermsAndConditions'), $url);
}

if ($showWarningMessage) {
    Display::addFlash(
        Display::return_message(get_lang('MoreDataAvailableInTheDatabaseButTrunkedForEfficiencyReasons'))
    );
}

// Block Social Avatar
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'messages');
if (api_get_setting('allow_social_tool') === 'true') {
    $tpl->assign('social_menu_block', $socialMenuBlock);
} else {
    $tpl->assign('social_menu_block', '');
    $tpl->assign('personal_data_block', $personalDataContent);
}

$tpl->assign('personal_data', $personalData);
$tpl->assign('permission', $permissionBlock);
$tpl->assign('term_link', $termLink);
$socialLayout = $tpl->get_template('social/personal_data.tpl');
$tpl->display($socialLayout);
