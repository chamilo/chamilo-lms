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
}
