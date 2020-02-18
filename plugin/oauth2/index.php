<?php
/* For licensing terms, see /license.txt */
/**
 * @author Sébastien Ducoulombier <seb@ldd.fr>
 * inspired by AzureActiveDirectory plugin from Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.plugin.oauth2
 */

/** @var OAuth2 $oAuth2Plugin */
$oAuth2Plugin = OAuth2::create();

if ($oAuth2Plugin->get(OAuth2::SETTING_ENABLE) === 'true') {
    $_template['block_title'] = $oAuth2Plugin->get(OAuth2::SETTING_BLOCK_NAME);

    $_template['signin_url'] = $oAuth2Plugin->getSignInURL();

    $managementLoginEnabled = 'true' === $oAuth2Plugin->get(OAuth2::SETTING_MANAGEMENT_LOGIN_ENABLE);

    $_template['management_login_enabled'] = $managementLoginEnabled;

    if ($managementLoginEnabled) {
        $managementLoginName = $oAuth2Plugin->get(OAuth2::SETTING_MANAGEMENT_LOGIN_NAME);

        if (empty($managementLoginName)) {
            $managementLoginName = $oAuth2Plugin->get_lang('ManagementLogin');
        }

        $_template['management_login_name'] = $managementLoginName;
    }
}
