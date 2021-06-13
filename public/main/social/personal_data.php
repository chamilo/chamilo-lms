<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Repository\LegalRepository;

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
    'password' => get_lang('Encrypted data'),
    'salt' => get_lang('Random data'),
    'empty' => get_lang('No data available'),
];

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$formToString = '';

if ('true' === api_get_setting('allow_terms_conditions')) {
    $form = new FormValidator('delete_term', 'post', api_get_self().'?action=delete_legal&user_id='.$userId);
    $form->addHtml(Display::return_message(get_lang('You can ask below for your legal agreement to be deleted or your account to be deleted.</br>In the case of the legal agreement, once deleted you will have to accept it again on your next login to be able to access the platform and recover your access, because we cannot reasonably at the same time give you a personal environment and not treat your personal data.</br>In the case of an account deletion, your account will be deleted along with all of your course subscriptions and all the information related to your account. Please select the corresponding option with care. In both cases, one of our administrators will review your request before it is effective, to avoid any misunderstanding and definitive loss of your data.'), 'normal', false));
    $form->addTextarea('explanation', [get_lang('Delete legal agreement'), get_lang('ExplanationDelete legal agreement')], [], true);
    $form->addHidden('action', 'delete_legal');
    $form->addButtonSave(get_lang('Delete legal agreement'));
    $formToString = $form->returnForm();

    $formDelete = new FormValidator('delete_account', 'post', api_get_self().'?action=delete_account&user_id='.$userId);
    $formDelete->addTextarea(
        'explanation',
        [get_lang('Delete account'), get_lang('ExplanationDelete account')],
        [],
        true
    );
    $formDelete->addHidden('action', 'delete_account');
    $formDelete->addButtonDelete(get_lang('Delete account'));
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
        UserManager::update_extra_field_value(
            $userId,
            'legal_accept',
            $legalAcceptType
        );

        Event::addEvent(
            LOG_TERM_CONDITION_ACCEPTED,
            LOG_USER_OBJECT,
            api_get_user_info($userId),
            api_get_utc_datetime()
        );

        $bossList = UserManager::getStudentBossList($userId);
        if (!empty($bossList)) {
            $bossList = array_column($bossList, 'boss_id');
            $currentUserInfo = api_get_user_info($userId);
            foreach ($bossList as $bossId) {
                $subjectEmail = sprintf(
                    get_lang('User %s signed the agreement.'),
                    $currentUserInfo['complete_name']
                );
                $contentEmail = sprintf(
                    get_lang('User %s signed the agreement.TheDateY'),
                    $currentUserInfo['complete_name'],
                    api_get_local_time()
                );

                MessageManager::send_message_simple(
                    $bossId,
                    $subjectEmail,
                    $contentEmail,
                    api_get_user_id()
                );
            }
        }
        Display::addFlash(Display::return_message(get_lang('Saved..')));
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

            Display::addFlash(Display::return_message(get_lang('Saved..')));
            Event::addEvent(
                LOG_USER_DELETE_ACCOUNT_REQUEST,
                LOG_USER_OBJECT,
                $userInfo
            );

            $url = api_get_path(WEB_CODE_PATH).'admin/user_list_consent.php';
            $link = Display::url($url, $url);
            $subject = get_lang('Request for account removal');
            $content = sprintf(
                get_lang('User %s asked for the deletion of his/her account, explaining that "%s". You can process the request here: %s'),
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
            $subject = get_lang('Request for consent withdrawal on legal terms');
            $content = sprintf(
                get_lang('User %s asked for the removal of his/her consent to our legal terms, explaining that "%s". You can process the request here: %s'),
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
    if (false !== $writeResult) {
        DocumentManager::file_send_for_download($path, true, $filename);
        exit;
    }
}

$allowSocial = 'true' === api_get_setting('allow_social_tool');

$nameTools = get_lang('Personal data');
$show_message = null;

if ($allowSocial) {
    $this_section = SECTION_SOCIAL;
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_PATH).'main/social/home.php',
        'name' => get_lang('Social network'),
    ];
} else {
    $this_section = SECTION_MYPROFILE;
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_PATH).'main/auth/profile.php',
        'name' => get_lang('Profile'),
    ];
}

$interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Personal data')];

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
                    if (0 == $category) {
                        $categoryName = 'Class';
                    }
                    $personalDataContent .= '<li class="advanced_options" id="personal-data-list-'.$category.'">';
                    $personalDataContent .= '<u>'.$categoryName.'</u> &gt;</li>';
                    $personalDataContent .= '<ul id="personal-data-list-'.$category.'_options" style="display:none;">';
                    if (empty($subValue)) {
                        $personalDataContent .= '<li>'.get_lang('No data available').'</li>';
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
                    $personalDataContent .= '<li>'.get_lang('No data available').'</li>';
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
                        $personalDataContent .= '<li>'.get_lang('No data available').'</li>';
                    } else {
                        if (1000 === count($subValue)) {
                            $showWarningMessage = true;
                        }
                        foreach ($subValue as $subSubValue) {
                            if ('DocumentsAdded' === $category) {
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
                    $personalDataContent .= '<li>'.get_lang('No data available').'</li>';
                } else {
                    foreach ($value as $subValue) {
                        $personalDataContent .= '<li>'.Security::remove_XSS($subValue).'</li>';
                    }
                }
                $personalDataContent .= '</ul>';
                break;
            case 'sessionRelCourseRelUsers':
                $personalDataContent .= '<li>'.$key.': </li><ul>';
                foreach ($value as $session => $courseList) {
                    $personalDataContent .= '<li>'.$session.'<ul>';
                    if (empty($courseList)) {
                        $personalDataContent .= '<li>'.get_lang('No data available').'</li>';
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
            $personalDataContent .= '<li>'.$key.': '.get_lang('Complex data (not shown)').'</li>';
        }*/
    } else {
        $personalDataContent .= '<li>'.$key.': '.Security::remove_XSS($value).'</li>';
    }
}
$personalDataContent .= '</ul>';

// Check terms acceptation
$permissionBlock = '';
if ('true' === api_get_setting('allow_terms_conditions')) {
    $extraFieldValue = new ExtraFieldValue('user');
    $value = $extraFieldValue->get_values_by_handler_and_field_variable(
        $userId,
        'legal_accept'
    );
    $permissionBlock .= Display::return_icon('accept_na.png', get_lang('Rejected'));
    if (isset($value['value']) && !empty($value['value'])) {
        list($legalId, $legalLanguageId, $legalTime) = explode(':', $value['value']);
        $permissionBlock = '<h4>'.get_lang('Current status').'</h4>'.
            get_lang('Legal agreement accepted').' '.Display::return_icon('accept.png', get_lang('Legal agreement accepted'), [], ICON_SIZE_TINY).
            '<br />';
        $permissionBlock .= get_lang('Date').': '.api_get_local_time($legalTime).'<br /><br />';
        $permissionBlock .= $formToString;

    /*$permissionBlock .= Display::url(
        get_lang('Delete legal agreement'),
        api_get_self().'?action=delete_legal&user_id='.$userId,
        ['class' => 'btn btn-danger btn-xs']
    );*/
    } else {
        // @TODO add action handling for button
        $permissionBlock .= Display::url(
            get_lang('Send legal agreement'),
            api_get_self().'?action=send_legal&user_id='.$userId,
            ['class' => 'btn btn-primary btn-xs']
        );
    }
} else {
    $permissionBlock .= get_lang('No terms and conditions available');
}

//Build the final array to pass to template
$personalData = [];
$personalData['data'] = $personalDataContent;
//$personalData['responsible'] = api_get_setting('personal_data_responsible_org');

$em = Database::getManager();
/** @var LegalRepository $legalTermsRepo */
$legalTermsRepo = $em->getRepository(\Chamilo\CoreBundle\Entity\Legal::class);
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
if ('true' === api_get_setting('allow_terms_conditions')) {
    $url = api_get_path(WEB_CODE_PATH).'social/terms.php';
    $termLink = Display::url(get_lang('Read the Terms and Conditions'), $url);
}

if ($showWarningMessage) {
    Display::addFlash(Display::return_message(get_lang('More data available in the database but trunked for efficiency reasons.')));
}

// Block Social Avatar
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'messages');
if ('true' === api_get_setting('allow_social_tool')) {
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
