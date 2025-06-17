<?php
/* For licensing terms, see /license.txt */
/**
 * Strings to English L10n.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.plugin.azure_active_directory
 */
$strings['plugin_title'] = 'Azure Active Directory';
$strings['plugin_comment'] = 'Allow authentication with Microsoft\'s Azure Active Directory';

$strings['enable'] = 'Enable';
$strings['app_id'] = 'Application ID';
$strings['app_id_help'] = 'Enter the Application Id assigned to your app by the Azure portal, e.g. 580e250c-8f26-49d0-bee8-1c078add1609';
$strings['app_secret'] = 'Application secret';
$strings['force_logout'] = 'Force logout button';
$strings['force_logout_help'] = 'Show a button to force logout session from Azure.';
$strings['block_name'] = 'Block name';
$strings['management_login_enable'] = 'Management login';
$strings['management_login_enable_help'] = 'Disable the chamilo login and enable an alternative login page for admin users.<br>'
    .'You will need to copy the <code>/plugin/azure_active_directory/layout/login_form.tpl</code> file to <code>/main/template/overrides/layout/</code> directory.';
$strings['management_login_name'] = 'Name for the management login';
$strings['management_login_name_help'] = 'The default is "Management Login".';
$strings['existing_user_verification_order'] = 'Existing user verification order';
$strings['existing_user_verification_order_help'] = 'This value indicates the order in which the user will be searched in Chamilo to verify its existence. '
    .'By default is <code>1, 2, 3</code>.'
    .'<ol><li>EXTRA_FIELD_ORGANISATION_EMAIL (<code>mail</code>)</li><li>EXTRA_FIELD_AZURE_ID (<code>mailNickname</code>)</li><li>EXTRA_FIELD_AZURE_UID (<code>id</code> or <code>objectId</code>)</li></ol>';
$strings['OrganisationEmail'] = 'Organisation e-mail';
$strings['AzureId'] = 'Azure ID (mailNickname)';
$strings['AzureUid'] = 'Azure UID (internal ID)';
$strings['ManagementLogin'] = 'Management Login';
$strings['InvalidId'] = 'Login failed - incorrect login or password. Errocode: AZMNF';
$strings['provisioning'] = 'Automated provisioning';
$strings['provisioning_help'] = 'Automatically create new users (as students) from Azure when they are not in Chamilo.';
$strings['update_users'] = 'Update users';
$strings['update_users_help'] = 'Allow user data to be updated at the start of the session.';
$strings['group_id_admin'] = 'Group ID for platform admins';
$strings['group_id_admin_help'] = 'The group ID can be found in the user group details, looking similar to this: ae134eef-cbd4-4a32-ba99-49898a1314b6. If empty, no user will be automatically created as admin.';
$strings['group_id_session_admin'] = 'Group ID for session admins';
$strings['group_id_session_admin_help'] = 'The group ID for session admins. If empty, no user will be automatically created as session admin.';
$strings['group_id_teacher'] = 'Group ID for teachers';
$strings['group_id_teacher_help'] = 'The group ID for teachers. If empty, no user will be automatically created as teacher.';
$strings['additional_interaction_required'] = 'Some additional interaction is required to authenticate you. Please login directly through <a href="https://login.microsoftonline.com" target="_blank">your authentication system</a>, then come back to this page to login.';
$strings['tenant_id'] = 'Tenant ID';
$strings['tenant_id_help'] = 'Required to run scripts.';
$strings['deactivate_nonexisting_users'] = 'Deactivate non-existing users';
$strings['deactivate_nonexisting_users_help'] = 'Compare registered users in Chamilo with those in Azure and deactivate accounts in Chamilo that do not exist in Azure.';
$strings['script_users_delta'] = 'Delta query for users';
$strings['script_users_delta_help'] = 'Get newly created, updated, or deleted users without having to perform a full read of the entire user collection. By default, is <code>No</code>.';
$strings['script_usergroups_delta'] = 'Delta query for usergroups';
$strings['script_usergroups_delta_help'] = 'Get newly created, updated, or deleted groups, including group membership changes, without having to perform a full read of the entire group collection. By default, is <code>No</code>.';
$strings['group_filter_regex'] = 'Group filter RegEx';
$strings['group_filter_regex_help'] = 'Regular expression to filter groups (only matches will be synchronized), e.g. <code>.*-FIL-.*</code> <code>.*-PAR-.*</code> <code>.*(FIL|PAR).*</code> <code>^(FIL|PAR).*</code>';
