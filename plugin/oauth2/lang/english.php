<?php
/* For licensing terms, see /license.txt */
/**
 * Strings to english L10n.
 *
 * @author Sébastien Ducoulombier <seb@ldd.fr>
 * inspired by Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.plugin.oauth2
 */
$strings['plugin_title'] = 'OAuth2';
$strings['plugin_comment'] = 'Allow authentication with an <em>OAuth2</em> server';

$strings['enable'] = 'Enable';

$strings['force_redirect'] = 'Force redirect';
$strings['force_redirect_help'] = 'If set to yes, then if the user is not yet logged in it will be redirected automatically to the SSO server';
$strings['skip_force_redirect_in'] = 'Skip folders for force redirect';
$strings['skip_force_redirect_in_help'] = "If force redirect is set to yes, then all pages will redirect unlogged user to the SSO server except from the one defined here in a list separated by commas in the form /main/webservices,/plugin/oauth2";

$strings['client_id'] = 'Client ID';
$strings['client_id_help'] = '<strong>The <em>OAuth2</em> client identifier</strong>
the <em>OAuth2</em> server administrator assigned to this Chamilo instance.
<br/>Required.';

$strings['client_secret'] = 'Client Secret';
$strings['client_secret_help'] = '<strong>The secret code</strong> associated to the <em>OAuth2</em> client identifier.
<br/>Required.';

$strings['authorize_url'] = 'Authorize URL';
$strings['authorize_url_help'] = 'The <em>OAuth2</em> server URL to request authorization.
<br/>Required.';

$strings['scopes'] = 'Scopes';
$strings['scopes_help'] = 'Scope is a mechanism in <em>OAuth2</em> to limit an application\'s access to a user\'s account.
An application can request one or more scopes, this information is then presented to the user in the consent screen,
and the access token issued to the application will be limited to the scopes granted.
Multiple scopes should be set separeted with <code>,</code> or spaces.';

$strings['scope_separator'] = 'Scope separator';
$strings['scope_separator'] = 'The separator used in the scope param. Defaul is a space. For instance: <code>email profile</code>.';

$strings['access_token_url'] = 'Access Token URL';
$strings['access_token_url_help'] = 'The <em>OAuth2</em> server URL to request an access token.
<br/>Required.';

$strings['access_token_method'] = 'Access Token HTTP Method';
$strings['access_token_method_help'] = 'Default value: POST';

$strings['resource_owner_details_url'] = 'Resource Owner Details URL';
$strings['resource_owner_details_url_help'] = 'The <em>OAuth2</em> server URL
returning the identified user information as a <em>JSON</em> array.
Required.';

$strings['response_error'] = 'Response error key';
$strings['response_error_help'] = 'Default is <code>error</code>';

$strings['response_code'] = 'Response code key';
$strings['response_code_help'] = 'By default, an error code retrieval is not attempted';

$strings['response_resource_owner_id'] = 'Response Resource Owner Id key';
$strings['response_resource_owner_id_help'] = 'The array key to the user\'s <em>OAuth2</em> identifier value.
<br/>Default value: <code>id</code>.
<br/>If the identifier is in a subentry of the returned <em>JSON</em> array,
<br/>then please enter successive path keys separated by dots. For example,
<br/><code>data.0.id</code>
<br/>means the identifier is to be found at
<code>$jsonArray["data"][0]["id"]</code>';

$strings['update_user_info'] = 'Update user information';
$strings['create_new_users'] = 'Create new users';
$strings['response_resource_owner_firstname'] = 'Response Resource Owner firstname key';
$strings['response_resource_owner_firstname_help'] = 'Same syntax as for the <em>Response Resource Owner Id key</em>';
$strings['response_resource_owner_lastname'] = 'Response Resource Owner lastname key';
$strings['response_resource_owner_status'] = 'Response Resource Owner status key';
$strings['response_resource_owner_status_help'] = 'The value at this array key should be one of these integers:<dl>
 <dt>1</dt><dd>Course Manager / Teacher</dd>
 <dt>3</dt><dd>Session Administrator</dd>
 <dt>4</dt><dd>DRH</dd>
 <dt>5</dt><dd>Student</dd>
 <dt>6</dt><dd>Anonymous</dd>
</dl>';
$strings['response_resource_owner_teacher_status'] = 'Response Resource Owner status value for Course Manager / Teacher';
$strings['response_resource_owner_teacher_status_help'] = 'If this value matches the value obtained from the <i>Response Resource Owner status key</i>, the user will have the role of Course Manager / Teacher';
$strings['response_resource_owner_sessadmin_status'] = 'Response Resource Owner status value for Session Administrator';
$strings['response_resource_owner_sessadmin_status_help'] = 'If this value matches the value obtained from the <i>Response Resource Owner status key</i>, the user will have the role of Session Administrator';
$strings['response_resource_owner_drh_status'] = 'Response Resource Owner status value for HRM';
$strings['response_resource_owner_drh_status_help'] = 'If this value matches the value obtained from the <i>Response Resource Owner status key</i>, the user will have the role of HRM';
$strings['response_resource_owner_student_status'] = 'Response Resource Owner status value for Student';
$strings['response_resource_owner_student_status_help'] = 'If this value matches the value obtained from the <i>Response Resource Owner status key</i>, the user will have the role of Student';
$strings['response_resource_owner_anon_status'] = 'Response Resource Owner status value for Anonymous';
$strings['response_resource_owner_anon_status_help'] = 'If this value matches the value obtained from the <i>Response Resource Owner status key</i>, the user will have the role of Anonymous';
$strings['response_resource_owner_email'] = 'Response Resource Owner email key';
$strings['response_resource_owner_username'] = 'Response Resource Owner username key';

$strings['response_resource_owner_urls'] = 'Response Resource Owner Access URL key';
$strings['response_resource_owner_urls_help'] = 'Similar syntax as for the <em>Response Resource Owner Id key</em>,
except there can be more than one value returned: <code>*</code> can be used as a placeholder for an integer.
<code>*</code> will be replaced by <code>0</code>, then <code>1</code>, then <code>2</code> and so on while it matches.
There can be more than one <code>*</code> in this key expression.
<br/>This option is used when $_configuration[\'multiple_access_urls\'] is set in app/config/configuration.php.
<br/>The fetched values should be found in table <code>access_url</code> columns <code>id</code> or <code>url</code>.
<br/>Example:
<br/><code>data.0.domaines.*.url</code>
<br/>means the URLs would be found at
<ul>
<li><code>$jsonArray["data"]["domaines"][0]["url"]</code></li>
<li><code>$jsonArray["data"]["domaines"][1]["url"]</code></li>
<li><code>$jsonArray["data"]["domaines"][2]["url"]</code></li>
<li>...</li>
</ul>';

$strings['logout_url'] = 'Logout URL';
$strings['logout_url_help'] = 'If set, the user agent will be redirected to this URL at logout.';

$strings['block_name'] = 'Block name';
$strings['block_name_help'] = 'The title shown above the <em>OAuth2</em> Login button';

$strings['management_login_enable'] = 'Management login';
$strings['management_login_enable_help'] = 'Disable the Chamilo login and enable an alternative login page for users.
<br>
You will need copy file <code>/plugin/oauth2/layout/login_form.tpl</code>
to directory <code>/main/template/overrides/layout/</code>.';
$strings['management_login_name'] = 'Name for the management login';
$strings['management_login_name_help'] = 'Default value is "Management Login".';

$strings['allow_third_party_login'] = 'Allow third party login';

// please keep these below alphabetically sorted
$strings['AccountInactive'] = "Account inactive";
$strings['DefaultFirstname'] = 'OAuth2 User default firstname';
$strings['DefaultLastname'] = 'OAuth2 User default lastname';
$strings['FailedUserCreation'] = 'User account creation failed';
$strings['InternalErrorCannotGetUserInfo'] = 'Internal error: could not get user information';
$strings['InvalidJsonReceivedFromProvider'] = 'The OAuth2 provider did not provide a valid JSON document';
$strings['ManagementLogin'] = 'Management Login';
$strings['NoUserAccountAndUserCreationNotAllowed'] = 'This user doesn\'t have an account yet and auto-provisioning is not enabled. Please contact this portal administration team at %s to request access.';
$strings['OAuth2Id'] = 'OAuth2 identifier';
$strings['UserNotAllowedOnThisPortal'] = 'This user account is not enabled on this portal';
$strings['WrongResponseResourceOwnerId'] = 'OAuth2 resource owner identifier value not found at the configured key';
$strings['IssuerNotFound'] = 'Issuer not found';
$strings['AuthorizeUrlNotAllowed'] = 'Authorize URL not allowed';

$strings['MessageInfoAboutRedirectToProvider'] = 'You are getting redirected to the common authentication system. Your credentials there are the ones that you typically use for other applications of your organisation. These might be different from the ones you used here previously.';
