<?php
/* For licensing terms, see /license.txt */
/**
 * Strings to english L10n.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.plugin.azure_active_directory
 */
$strings['plugin_title'] = 'Azure Active Directory';
$strings['plugin_comment'] = 'Allow authentication with Microsoft\'s Azure Active Directory';

$strings['enable'] = 'Enable';
$strings['app_id'] = 'Application ID';
$strings['app_id_help'] = 'Enter the Application Id assinged to your app by the Azure portal, e.g. 580e250c-8f26-49d0-bee8-1c078add1609';
$strings['app_secret'] = 'Application secret';
$strings['force_logout'] = 'Force logout button';
$strings['force_logout_help'] = 'Show a button to force logout session from Azure.';
$strings['block_name'] = 'Block name';
$strings['management_login_enable'] = 'Management login';
$strings['management_login_enable_help'] = 'Disable the chamilo login and enable an alternative login page for users.<br>'
    .'You will need copy the <code>/plugin/azure_active_directory/layout/login_form.tpl</code> file to <code>/main/template/overrides/layout/</code> directory.';
$strings['management_login_name'] = 'Name for the management login';
$strings['management_login_name_help'] = 'By default is "Management Login".';
$strings['OrganisationEmail'] = 'Organisation e-mail';
$strings['AzureId'] = 'Azure ID (mailNickname)';
$strings['ManagementLogin'] = 'Management Login';
$strings['InvalidId'] = 'Login failed - incorrect login or password. Errocode: AZMNF';
