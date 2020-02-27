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
$strings['response_resource_owner_firstname_help'] = 'Same syntax as for the Response Resource Owner Id key';
$strings['response_resource_owner_lastname'] = 'Response Resource Owner lastname key';
$strings['response_resource_owner_status'] = 'Response Resource Owner status key';
$strings['response_resource_owner_status_help'] = 'The value at this array key should be one of these integers:<dl>
 <dt>1</dt><dd>Course Manager / Teacher</dd>
 <dt>3</dt><dd>Session Administrator</dd>
 <dt>4</dt><dd>DRH</dd>
 <dt>5</dt><dd>Student</dd>
 <dt>6</dt><dd>Anonymous</dd>
</dl>';
$strings['response_resource_owner_email'] = 'Response Resource Owner email key';
$strings['response_resource_owner_username'] = 'Response Resource Owner username key';

$strings['logout_url'] = 'Logout URL';
$strings['logout_url_help'] = 'If this URL is set, the OAuth2 token will be POSTed to it at user logout.';

$strings['block_name'] = 'Block name';
$strings['block_name_help'] = 'The title shown above the <em>OAuth2</em> Login button';

$strings['management_login_enable'] = 'Management login';
$strings['management_login_enable_help'] = 'Disable the Chamilo login and enable an alternative login page for users.
<br>
You will need copy file <code>/plugin/oauth2/layout/login_form.tpl</code>
to directory <code>/main/template/overrides/layout/</code>.';
$strings['management_login_name'] = 'Name for the management login';
$strings['management_login_name_help'] = 'Default value is "Management Login".';

$strings['OAuth2Id'] = 'OAuth2 identifier';
$strings['ManagementLogin'] = 'Management Login';

$strings['invalid_json_received_from_provider'] = 'The OAuth2 provider did not provide a valid JSON document';
$strings['wrong_response_resource_owner_id'] = 'OAuth2 resource owner identifier value not found at the configured key';
$strings['no_user_has_this_oauth_code'] = 'No existing user has this OAuth2 code';
$strings['FailedUserCreation'] = 'User account creation failed';
$strings['internal_error_cannot_get_user_info'] = 'Internal error: could not get user information';
$strings['InvalidId'] = 'Login failed - the OAuth2 identifier was not recognized as an existing Chamilo user\'s.';

$strings['DefaultFirstname'] = 'OAuth2 User default firstname';
$strings['DefaultLastname'] = 'OAuth2 User default lastname';
