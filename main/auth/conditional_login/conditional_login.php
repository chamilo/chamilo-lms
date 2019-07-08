<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/*
 This script is included by local.inc.php to redirect users to some url if some conditions are satisfied.
 * Please populate the $login_conditions array with a conditional function and an url.
 * If the conditional function returns true the user will be redirected to URL at login.
 * This array must be filled for this module to work.
 * This is an example asking the user to enter his phone number if it is empty.
 * Note you can enter more than one condition in the array. They will be checked in the array order.
*/
/**
 * Please implements the functions of the $login_conditions array.
 * Each of these function will take a user array
 * (user_id, username, password (crypted), auth_source, active, expiration_date).
 */
$login_conditions = [];

$conditionalLoginHook = HookConditionalLogin::create();

if (!empty($conditionalLoginHook)) {
    foreach ($conditionalLoginHook->notifyConditionalLogin() as $condition) {
        $login_conditions[] = $condition;
    }
}

// "Terms and conditions" condition
array_push(
    $login_conditions,
    [
        'conditional_function' => 'check_platform_legal_conditions',
        'url' => api_get_path(WEB_CODE_PATH).'auth/inscription.php',
    ]
);

//array_push($login_conditions, array(
//  'conditional_function' => 'dc_check_phone_number',
//  'url' => api_get_path(WEB_PATH).'main/auth/conditional_login/complete_phone_number.php'
//));

function dc_check_phone_number($user)
{
    $uInfo = api_get_user_info($user['user_id']);
    if (empty($uInfo['phone'])) {
        return false;
    }

    return true;
}

/**
 * Checks if the user accepted or not the legal conditions.
 *
 * @param array $user
 *
 * @return bool true if user pass, false otherwise
 */
function check_platform_legal_conditions($user)
{
    if (api_get_setting('allow_terms_conditions') === 'true' &&
        api_get_setting('load_term_conditions_section') === 'login'
    ) {
        $termAndConditionStatus = api_check_term_condition($user['user_id']);

        // @todo not sure why we need the login password and update_term_status
        if ($termAndConditionStatus === false) {
            Session::write('term_and_condition', ['user_id' => $user['user_id']]);

            return false;
        } else {
            Session::erase('term_and_condition');

            return true;
        }
    } else {
        // No validation user can pass
        return true;
    }
}
