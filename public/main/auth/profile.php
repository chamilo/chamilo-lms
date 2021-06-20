<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\User;
use ChamiloSession as Session;

/**
 * This file displays the user's profile,
 * optionally it allows users to modify their profile as well.
 *
 * See inc/conf/profile.conf.php to modify settings
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_MYPROFILE;
$allowSocialTool = 'true' == api_get_setting('allow_social_tool');
if ($allowSocialTool) {
    $this_section = SECTION_SOCIAL;
}

$logInfo = [
    'tool' => 'profile',
    'action' => $this_section,
];
Event::registerLog($logInfo);

$profileList = (array) api_get_setting('profile');

$_user = api_get_user_info();
$_SESSION['this_section'] = $this_section;

if (!(isset($_user['user_id']) && $_user['user_id']) || api_is_anonymous($_user['user_id'], true)) {
    api_not_allowed(true);
}

$htmlHeadXtra[] = api_get_password_checker_js('#username', '#password1');
//$htmlHeadXtra[] = api_get_css_asset('cropper/dist/cropper.min.css');
//$htmlHeadXtra[] = api_get_asset('cropper/dist/cropper.min.js');
$htmlHeadXtra[] = '<script>
$(function() {
    $("#id_generate_api_key").on("click", function (e) {
        e.preventDefault();

        $.ajax({
            contentType: "application/x-www-form-urlencoded",
            type: "POST",
            url: "'.api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=generate_api_key",
            data: "num_key_id="+"",
            success: function(datos) {
                $("#div_api_key").html(datos);
            }
        });
    });

});

function confirmation(name) {
    if (confirm("'.get_lang('Are you sure to delete?').' " + name + " ?")) {
            document.forms["profile"].submit();
    } else {
        return false;
    }
}
function show_image(image,width,height) {
    width = parseInt(width) + 20;
    height = parseInt(height) + 20;
    window_x = window.open(image,\'windowX\',\'width=\'+ width + \', height=\'+ height + \'\');
}
</script>';

$jquery_ready_content = '';
if ('true' === api_get_setting('allow_message_tool')) {
    $jquery_ready_content = <<<EOF
    $(".message-content .message-delete").click(function(){
        $(this).parents(".message-content").animate({ opacity: "hide" }, "slow");
        $(".message-view").animate({ opacity: "show" }, "slow");
    });
EOF;
}

$tool_name = 'true' === api_get_setting('profile.is_editable') ? get_lang('Edit Profile') : get_lang('View my e-portfolio');
$table_user = Database::get_main_table(TABLE_MAIN_USER);

/*
 * Get initial values for all fields.
 */
$user_data = api_get_user_info(
    api_get_user_id(),
    false,
    false,
    false,
    false,
    true,
    true
);
$array_list_key = UserManager::get_api_keys(api_get_user_id());
$id_temp_key = UserManager::get_api_key_id(api_get_user_id(), 'dokeos');
$value_array = [];
if (isset($array_list_key[$id_temp_key])) {
    $value_array = $array_list_key[$id_temp_key];
}
$user_data['api_key_generate'] = $value_array;

if (false !== $user_data) {
    if ('true' == api_get_setting('login_is_email')) {
        $user_data['username'] = $user_data['email'];
    }
    if (is_null($user_data['language'])) {
        $user_data['language'] = api_get_setting('platformLanguage');
    }
}

$form = new FormValidator('profile');

if (api_is_western_name_order()) {
    //    FIRST NAME and LAST NAME
    $form->addElement('text', 'firstname', get_lang('First name'), ['size' => 40]);
    $form->addElement('text', 'lastname', get_lang('Last name'), ['size' => 40]);
} else {
    //    LAST NAME and FIRST NAME
    $form->addElement('text', 'lastname', get_lang('Last name'), ['size' => 40]);
    $form->addElement('text', 'firstname', get_lang('First name'), ['size' => 40]);
}
if (!in_array('name', $profileList)) {
    $form->freeze(['lastname', 'firstname']);
}
$form->applyFilter(['lastname', 'firstname'], 'stripslashes');
$form->applyFilter(['lastname', 'firstname'], 'trim');
$form->applyFilter(['lastname', 'firstname'], 'html_filter');
$form->addRule('lastname', get_lang('Required field'), 'required');
$form->addRule('firstname', get_lang('Required field'), 'required');

//    USERNAME
$form->addElement(
    'text',
    'username',
    get_lang('Username'),
    [
        'id' => 'username',
        'maxlength' => User::USERNAME_MAX_LENGTH,
        'size' => User::USERNAME_MAX_LENGTH,
    ]
);
if (!in_array('login', $profileList) || 'true' == api_get_setting('login_is_email')) {
    $form->freeze('username');
}
$form->applyFilter('username', 'stripslashes');
$form->applyFilter('username', 'trim');
$form->addRule('username', get_lang('Required field'), 'required');
$form->addRule('username', get_lang('Your login can only contain letters, numbers and _.-'), 'username');
$form->addRule('username', get_lang('This login is already in use'), 'username_available', $user_data['username']);

$form->addElement('text', 'official_code', get_lang('Code'), ['size' => 40]);
if (!in_array('officialcode', $profileList)) {
    $form->freeze('official_code');
}
$form->applyFilter('official_code', 'stripslashes');
$form->applyFilter('official_code', 'trim');
$form->applyFilter('official_code', 'html_filter');
if ('true' === api_get_setting('registration', 'officialcode') &&
    in_array('officialcode', $profileList)
) {
    $form->addRule('official_code', get_lang('Required field'), 'required');
}

//    EMAIL
$form->addElement('email', 'email', get_lang('e-mail'), ['size' => 40]);
if (!in_array('email', $profileList)) {
    $form->freeze('email');
}

if ('true' == api_get_setting('registration', 'email') && in_array('email', $profileList)
) {
    $form->applyFilter('email', 'stripslashes');
    $form->applyFilter('email', 'trim');
    $form->addRule('email', get_lang('Required field'), 'required');
    $form->addEmailRule('email');
}

//    PHONE
$form->addElement('text', 'phone', get_lang('Phone'), ['size' => 20]);
if (!in_array('phone', $profileList)) {
    $form->freeze('phone');
}
$form->applyFilter('phone', 'stripslashes');
$form->applyFilter('phone', 'trim');
$form->applyFilter('phone', 'html_filter');

//  PICTURE
if ('true' === api_get_setting('profile.is_editable') && in_array('picture', $profileList)) {
    $form->addFile(
        'picture',
        [
            '' != $user_data['picture_uri'] ? get_lang('Update Image') : get_lang('Add image'),
            get_lang('Only PNG, JPG or GIF images allowed'),
        ],
        [
            'id' => 'picture',
            'class' => 'picture-form',
            'crop_image' => true,
            'crop_ratio' => '1 / 1',
            'accept' => 'image/*',
        ]
    );

    $form->addProgress();
    if (!empty($user_data['picture_uri'])) {
        $form->addElement('checkbox', 'remove_picture', null, get_lang('Remove picture'));
    }
    $allowed_picture_types = api_get_supported_image_extensions(false);
    $form->addRule(
        'picture',
        get_lang('Only PNG, JPG or GIF images allowed').' ('.implode(', ', $allowed_picture_types).')',
        'filetype',
        $allowed_picture_types
    );
}

//    LANGUAGE
$form->addSelectLanguage('language', get_lang('Language'));
if (!in_array('language', $profileList)) {
    $form->freeze('language');
}

// THEME
if ('true' === api_get_setting('profile.is_editable') && 'true' === api_get_setting('user_selected_theme')) {
    $form->addElement('SelectTheme', 'theme', get_lang('Graphical theme'));
    if (!in_array('theme', $profileList)) {
        $form->freeze('theme');
    }
    $form->applyFilter('theme', 'trim');
}

//    EXTENDED PROFILE  this make the page very slow!
if ('true' === api_get_setting('extended_profile')) {
    $width_extended_profile = 500;
    //    MY COMPETENCES
    $form->addHtmlEditor(
        'competences',
        get_lang('My competences'),
        false,
        false,
        [
            'ToolbarSet' => 'Profile',
            'Width' => $width_extended_profile,
            'Height' => '130',
        ]
    );
    //    MY DIPLOMAS
    $form->addHtmlEditor(
        'diplomas',
        get_lang('My diplomas'),
        false,
        false,
        [
            'ToolbarSet' => 'Profile',
            'Width' => $width_extended_profile,
            'Height' => '130',
        ]
    );
    // WHAT I AM ABLE TO TEACH
    $form->addHtmlEditor(
        'teach',
        get_lang('What I am able to teach'),
        false,
        false,
        [
            'ToolbarSet' => 'Profile',
            'Width' => $width_extended_profile,
            'Height' => '130',
        ]
    );

    //    MY PRODUCTIONS
    $form->addElement('file', 'production', get_lang('My productions'));
    if ($production_list = UserManager::build_production_list(api_get_user_id(), '', true)) {
        $form->addElement('static', 'productions_list', null, $production_list);
    }
    //    MY PERSONAL OPEN AREA
    $form->addHtmlEditor(
        'openarea',
        get_lang('My personal open area'),
        false,
        false,
        [
            'ToolbarSet' => 'Profile',
            'Width' => $width_extended_profile,
            'Height' => '350',
        ]
    );
    // openarea is untrimmed for maximum openness
    $form->applyFilter(['competences', 'diplomas', 'teach', 'openarea'], 'stripslashes');
    $form->applyFilter(['competences', 'diplomas', 'teach'], 'trim');
}

//    PASSWORD, if auth_source is platform
if (PLATFORM_AUTH_SOURCE == $user_data['auth_source'] &&
    'true' === api_get_setting('profile.is_editable') &&
    in_array('password', $profileList)
) {
    $form->addElement('password', 'password0', [get_lang('Pass'), get_lang('Enter2passToChange')], ['size' => 40]);
    $form->addElement('password', 'password1', get_lang('New password'), ['id' => 'password1', 'size' => 40]);

    $form->addElement('password', 'password2', get_lang('Confirm password'), ['size' => 40]);
    //    user must enter identical password twice so we can prevent some user errors
    $form->addRule(['password1', 'password2'], get_lang('You have typed two different passwords'), 'compare');
    $form->addPasswordRule('password1');
}

$extraField = new ExtraField('user');
$return = $extraField->addElements(
    $form,
    api_get_user_id()
);

$jquery_ready_content = $return['jquery_ready_content'];

// the $jquery_ready_content variable collects all functions that
// will be load in the $(document).ready javascript function
$htmlHeadXtra[] = '<script>
$(function() {
    '.$jquery_ready_content.'
});
</script>';

if (in_array('apikeys', $profileList)) {
    $form->addElement('html', '<div id="div_api_key">');
    $form->addElement(
        'text',
        'api_key_generate',
        get_lang('My API key'),
        ['size' => 40, 'id' => 'id_api_key_generate']
    );
    $form->addElement('html', '</div>');
    $form->addButton(
        'generate_api_key',
        get_lang('Generate API key'),
        'cogs',
        'default',
        'default',
        null,
        ['id' => 'id_generate_api_key']
    );
}
//    SUBMIT
if ('true' === api_get_setting('profile.is_editable')) {
    $form->addButtonUpdate(get_lang('Save settings'), 'apply_change');
} else {
    $form->freeze();
}

// Student cannot modified their user conditions
$extraConditions = api_get_configuration_value('show_conditions_to_user');
if ($extraConditions && isset($extraConditions['conditions'])) {
    $extraConditions = $extraConditions['conditions'];
    foreach ($extraConditions as $condition) {
        $element = $form->getElement('extra_'.$condition['variable']);
        if ($element) {
            $element->freeze();
        }
    }
}

$form->setDefaults($user_data);

$filtered_extension = false;

if ($form->validate()) {
    $wrong_current_password = false;
    $user_data = $form->getSubmitValues(1);
    $user = api_get_user_entity(api_get_user_id());

    // set password if a new one was provided
    $validPassword = false;
    $passwordWasChecked = false;

    if ($user &&
        (!empty($user_data['password0']) &&
        !empty($user_data['password1'])) ||
        (!empty($user_data['password0']) &&
            in_array('email', $profileList)
        )
    ) {
        $passwordWasChecked = true;
        $validPassword = UserManager::isPasswordValid(
            $user,
            $user_data['password0'],
        );

        if ($validPassword) {
            $password = $user_data['password1'];
        } else {
            Display::addFlash(
                Display:: return_message(
                    get_lang('The current password is incorrect'),
                    'warning',
                    false
                )
            );
        }
    }

    $allow_users_to_change_email_with_no_password = true;
    if (isset($user_data['auth_source']) && PLATFORM_AUTH_SOURCE == $user_data['auth_source'] &&
        'false' === api_get_setting('allow_users_to_change_email_with_no_password')
    ) {
        $allow_users_to_change_email_with_no_password = false;
    }

    // If user sending the email to be changed (input available and not frozen )
    if (in_array('email', $profileList)) {
        $userFromEmail = api_get_user_info_from_email($user_data['email']);
        if ($allow_users_to_change_email_with_no_password) {
            if (!empty($userFromEmail)) {
                $changeemail = $user_data['email'];
            }
        } else {
            // Normal behaviour
            if (!empty($userFromEmail) && $validPassword) {
                $changeemail = $user_data['email'];
            }

            if (!empty($userFromEmail) && empty($user_data['password0'])) {
                Display::addFlash(
                    Display:: return_message(
                        get_lang('ToChangeYoure-mailMustTypeYourPassword'),
                        'error',
                        false
                    )
                );
            }
        }
    }

    // Upload picture if a new one is provided
    if (isset($_FILES['picture']) && $_FILES['picture']['size']) {
        $new_picture = UserManager::update_user_picture(
            api_get_user_id(),
            $_FILES['picture']['name'],
            $_FILES['picture']['tmp_name'],
            $user_data['picture_crop_result']
        );

        if ($new_picture) {
            $user_data['picture_uri'] = $new_picture;

            Display::addFlash(
                Display:: return_message(
                    get_lang('Your picture has been uploaded'),
                    'normal',
                    false
                )
            );
        }
    } elseif (!empty($user_data['remove_picture'])) {
        // remove existing picture if asked
        UserManager::deleteUserPicture(api_get_user_id());
        $user_data['picture_uri'] = '';
    }

    // Remove production.
    if (isset($user_data['remove_production']) &&
        is_array($user_data['remove_production'])
    ) {
        foreach (array_keys($user_data['remove_production']) as $production) {
            UserManager::remove_user_production(api_get_user_id(), urldecode($production));
        }
        if ($production_list = UserManager::build_production_list(api_get_user_id(), true, true)) {
            $form->insertElementBefore(
                $form->createElement('static', null, null, $production_list),
                'productions_list'
            );
        }
        $form->removeElement('productions_list');
        Display::addFlash(
            Display:: return_message(get_lang('File deleted'), 'normal', false)
        );
    }

    // upload production if a new one is provided
    /*if (isset($_FILES['production']) && $_FILES['production']['size']) {
        $res = upload_user_production(api_get_user_id());
        if (!$res) {
            //it's a bit excessive to assume the extension is the reason why
            // upload_user_production() returned false, but it's true in most cases
            $filtered_extension = true;
        } else {
            Display::addFlash(
                Display:: return_message(
                    get_lang('Your production file has been uploaded'),
                    'normal',
                    false
                )
            );
        }
    }*/

    // remove values that shouldn't go in the database
    unset(
        $user_data['password0'],
        $user_data['password1'],
        $user_data['password2'],
        $user_data['MAX_FILE_SIZE'],
        $user_data['remove_picture'],
        $user_data['apply_change'],
        $user_data['email']
    );

    // Following RFC2396 (http://www.faqs.org/rfcs/rfc2396.html), a URI uses ':' as a reserved character
    // we can thus ensure the URL doesn't contain any scheme name by searching for ':' in the string
    $my_user_openid = isset($user_data['openid']) ? $user_data['openid'] : '';
    if (!preg_match('/^[^:]*:\/\/.*$/', $my_user_openid)) {
        //ensure there is at least a http:// scheme in the URI provided
        $user_data['openid'] = 'http://'.$my_user_openid;
    }
    $extras = [];

    //Checking the user language
    $languages = array_keys(api_get_languages());
    if (!in_array($user_data['language'], $languages)) {
        $user_data['language'] = api_get_setting('platformLanguage');
    }
    $_SESSION['_user']['language'] = $user_data['language'];

    //Only update values that are request by the "profile" setting
    //Adding missing variables

    $available_values_to_modify = [];
    foreach ($profileList as $key) {
        switch ($key) {
            case 'language':
                $available_values_to_modify[] = 'language';
                $available_values_to_modify[] = 'locale';
                $user_data['locale'] = $user_data['language'];
                break;
            case 'login':
                $available_values_to_modify[] = 'username';
                break;
            case 'name':
                $available_values_to_modify[] = 'firstname';
                $available_values_to_modify[] = 'lastname';
                break;
            case 'picture':
                $available_values_to_modify[] = 'picture_uri';
                break;
            default:
                $available_values_to_modify[] = $key;
                break;
        }
    }

    //Fixing missing variables
    $available_values_to_modify = array_merge(
        $available_values_to_modify,
        ['competences', 'diplomas', 'openarea', 'teach', 'openid', 'address']
    );

    // build SQL query
    $sql = "UPDATE $table_user SET";
    unset($user_data['api_key_generate']);

    foreach ($user_data as $key => $value) {
        if ('extra_' === substr($key, 0, 6)) { //an extra field
            continue;
        } elseif (false !== strpos($key, 'remove_extra_')) {
        } else {
            if (in_array($key, $available_values_to_modify)) {
                $sql .= " $key = '".Database::escape_string($value)."',";
            }
        }
    }

    $changePassword = false;
    // Change email
    if ($allow_users_to_change_email_with_no_password) {
        if (isset($changeemail) && in_array('email', $available_values_to_modify)) {
            $sql .= " email = '".Database::escape_string($changeemail)."' ";
        }
        if (isset($password) && in_array('password', $available_values_to_modify)) {
            $changePassword = true;
        }
    } else {
        if (isset($changeemail) && !isset($password) && in_array('email', $available_values_to_modify)) {
            $sql .= " email = '".Database::escape_string($changeemail)."'";
        } else {
            if (isset($password) && in_array('password', $available_values_to_modify)) {
                if (isset($changeemail) && in_array('email', $available_values_to_modify)) {
                    $sql .= " email = '".Database::escape_string($changeemail)."' ";
                }
                $changePassword = true;
            }
        }
    }

    $sql = rtrim($sql, ',');
    if ($changePassword && !empty($password)) {
        UserManager::updatePassword(api_get_user_id(), $password);
    }

    if (!in_array('officialcode', $profileList) &&
        isset($user_data['official_code'])
    ) {
        $sql .= ", official_code = '".Database::escape_string($user_data['official_code'])."'";
    }

    $sql .= " WHERE id  = '".api_get_user_id()."'";
    Database::query($sql);

    if (isset($user_data['language']) && !empty($user_data['language'])) {
        // _locale_user is set in the UserLocaleListener during login
        Session::write('_locale_user', $user_data['language']);
    }

    if (false == $passwordWasChecked) {
        Display::addFlash(
            Display:: return_message(get_lang('Your new profile has been saved'), 'normal', false)
        );
    } else {
        if ($validPassword) {
            Display::addFlash(
                Display:: return_message(get_lang('Your new profile has been saved'), 'normal', false)
            );
        }
    }

    $extraField = new ExtraFieldValue('user');
    $extraField->saveFieldValues($user_data);

    $userInfo = api_get_user_info(
        api_get_user_id(),
        false,
        false,
        false,
        false,
        true,
        true
    );
    Session::write('_user', $userInfo);

    /*if ($hook) {
        Database::getManager()->clear(User::class); // Avoid cache issue (user entity is used before)
        $user = api_get_user_entity(api_get_user_id()); // Get updated user info for hook event
        $hook->setEventData(['user' => $user]);
        $hook->notifyUpdateUser(HOOK_EVENT_TYPE_POST);
    }*/

    Session::erase('system_timezone');

    $url = api_get_self();
    header("Location: $url");
    exit;
}

$actions = '';
if ($allowSocialTool) {
    if ('true' === api_get_setting('extended_profile')) {
        if ('true' === api_get_setting('allow_message_tool')) {
            $actions .= '<a href="'.api_get_path(WEB_PATH).'main/social/profile.php">'.
                Display::return_icon('shared_profile.png', get_lang('View shared profile')).'</a>';
            $actions .= '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.
                Display::return_icon('inbox.png', get_lang('Messages')).'</a>';
        }
        $show = isset($_GET['show']) ? '&amp;show='.Security::remove_XSS($_GET['show']) : '';

        if (isset($_GET['type']) && 'extended' === $_GET['type']) {
            $actions .= '<a href="profile.php?type=reduced'.$show.'">'.
                Display::return_icon('edit.png', get_lang('Edit normal profile'), '', 16).'</a>';
        } else {
            $actions .= '<a href="profile.php?type=extended'.$show.'">'.
                Display::return_icon('edit.png', get_lang('Edit extended profile'), '', 16).'</a>';
        }
    }
}

$show_delete_account_button = 'true' === api_get_setting('platform_unsubscribe_allowed') ? true : false;

$tpl = new Template(get_lang('Profile'));

if ($actions) {
    $tpl->assign(
        'actions',
        Display::toolbarAction('toolbar', [$actions])
    );
}

SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'messages');
$tabs = SocialManager::getHomeProfileTabs('profile');

if ($allowSocialTool) {
    SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'home');
    $menu = SocialManager::show_social_menu(
        'home',
        null,
        api_get_user_id(),
        false,
        $show_delete_account_button
    );

    $tpl->assign('social_menu_block', $menu);
    $tpl->assign('social_right_content', $form->returnForm());
    $social_layout = $tpl->get_template('social/edit_profile.html.twig');
    $tpl->display($social_layout);
} else {
    $bigImage = UserManager::getUserPicture(api_get_user_id(), USER_IMAGE_SIZE_BIG);
    $normalImage = UserManager::getUserPicture(api_get_user_id(), USER_IMAGE_SIZE_ORIGINAL);

    $imageToShow = '<div id="image-message-container">';
    $imageToShow .= '<a class="expand-image float-right" href="'.$bigImage.'" /><img src="'.$normalImage.'"></a>';
    $imageToShow .= '</div>';

    $content = $imageToShow.$form->returnForm();

    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
}
