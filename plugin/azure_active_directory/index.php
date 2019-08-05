<?php
/* For licensing terms, see /license.txt */
/**
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.plugin.azure_active_directory
 */

/** @var AzureActiveDirectory $activeDirectoryPlugin */
$activeDirectoryPlugin = AzureActiveDirectory::create();

if ($activeDirectoryPlugin->get(AzureActiveDirectory::SETTING_ENABLE) === 'true') {
    $_template['block_title'] = $activeDirectoryPlugin->get(AzureActiveDirectory::SETTING_BLOCK_NAME);

    $_template['signin_url'] = $activeDirectoryPlugin->getUrl(AzureActiveDirectory::URL_TYPE_AUTHORIZE);

    if ('true' === $activeDirectoryPlugin->get(AzureActiveDirectory::SETTING_FORCE_LOGOUT_BUTTON)) {
        $_template['signout_url'] = $activeDirectoryPlugin->getUrl(AzureActiveDirectory::URL_TYPE_LOGOUT);
    }

    $managementLoginEnabled = 'true' === $activeDirectoryPlugin->get(AzureActiveDirectory::SETTING_MANAGEMENT_LOGIN_ENABLE);

    $_template['management_login_enabled'] = $managementLoginEnabled;

    if ($managementLoginEnabled) {
        $managementLoginName = $activeDirectoryPlugin->get(AzureActiveDirectory::SETTING_MANAGEMENT_LOGIN_NAME);

        if (empty($managementLoginName)) {
            $managementLoginName = $activeDirectoryPlugin->get_lang('ManagementLogin');
        }

        $_template['management_login_name'] = $managementLoginName;
    }
}
