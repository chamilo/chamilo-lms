<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/functions.inc.php';

/** @var array $uData */
if ($uData['auth_source'] === 'azure') {
    $plugin = AzureActiveDirectory::create();

    if ('true' !== $plugin->get(AzureActiveDirectory::SETTING_ENABLE)) {
        api_not_allowed(true);
    }

    $azureIdField = new ExtraFieldValue('user');
    $azureIdValue = $azureIdField->get_values_by_handler_and_field_variable(
        $uData['user_id'],
        AzureActiveDirectory::EXTRA_FIELD_AZURE_ID
    );

    if (empty($azureIdValue) || empty($azureIdValue['value'])) {
        api_not_allowed(true);
    }

    $organsationEmailField = new ExtraFieldValue('user');
    $organsationEmailValue = $organsationEmailField->get_values_by_handler_and_field_variable(
        $uData['user_id'],
        AzureActiveDirectory::EXTRA_FIELD_ORGANISATION_EMAIL
    );

    if (empty($organsationEmailValue) || empty($organsationEmailValue['value'])) {
        api_not_allowed(true);
    }

    $provider = $plugin->getProvider();

    $authUrl = $provider->getAuthorizationUrl(['login_hint' => $organsationEmailValue['value']]);

    ChamiloSession::write('oauth2state', $provider->getState());

    // Redirect to Azure login.
    header('Location: '.$authUrl);
    // Avoid execution from here in local.inc.php script.
    exit;
}
