<?php
/* For licensing terms, see /license.txt */
/**
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.plugin.azure_active_directory
 */

/** @var AzureActiveDirectory $activeDirectoryPlugin */
$activeDirectoryPlugin = AzureActiveDirectory::create();

if ($activeDirectoryPlugin->get(AzureActiveDirectory::SETTING_ENABLE) === 'true') {
    $signUp = $activeDirectoryPlugin->get(AzureActiveDirectory::SETTING_SIGNIN_POLICY);
    $signIn = $activeDirectoryPlugin->get(AzureActiveDirectory::SETTING_SIGNUP_POLICY);
    $signUnified = $activeDirectoryPlugin->get(AzureActiveDirectory::SETTING_SIGNUNIFIED_POLICY);

    $_template['block_title'] = $activeDirectoryPlugin->get(AzureActiveDirectory::SETTING_BLOCK_NAME);

    if ($signUp) {
        $_template['signup_url'] = $activeDirectoryPlugin->getUrl(AzureActiveDirectory::URL_TYPE_SIGNUP);
    }

    if ($signIn) {
        $_template['signin_url'] = $activeDirectoryPlugin->getUrl(AzureActiveDirectory::URL_TYPE_SIGNIN);
    }

    if ($signUnified) {
        $_template['signunified_url'] = $activeDirectoryPlugin->getUrl(AzureActiveDirectory::URL_TYPE_SIGNUNIFIED);
    }

    $_template['signout_url'] = $activeDirectoryPlugin->getUrl(AzureActiveDirectory::URL_TYPE_SIGNOUT);
}
