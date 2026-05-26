<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField as ExtraFieldEntity;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;

$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';
require_once __DIR__.'/../config.php';

api_protect_admin_script();

$plugin = GoogleMapsPlugin::create();

if (!$plugin->isEnabled()) {
    api_not_allowed(true);
}

$templateName = $plugin->get_lang('UsersCoordinatesMap');
$tpl = new Template($templateName);

$warnings = [];
$fieldNames = $plugin->getConfiguredExtraFieldNames();

if (!$plugin->isGoogleApiEnabled()) {
    $warnings[] = $plugin->get_lang('GoogleMapsApiDisabledWarning');
}

if (!$plugin->hasApiKey()) {
    $warnings[] = $plugin->get_lang('GoogleMapsApiKeyMissingWarning');
}

if (empty($fieldNames)) {
    $warnings[] = $plugin->get_lang('ExtraFieldNameMissingWarning');
}

if ($plugin->isGoogleApiEnabled() && $plugin->hasApiKey()) {
    global $htmlHeadXtra;

    $htmlHeadXtra[] = '<script src="https://maps.googleapis.com/maps/api/js?key='.rawurlencode($plugin->getApiKey()).'" async defer></script>';
}

$em = Database::getManager();
$extraFieldRepository = $em->getRepository(ExtraFieldEntity::class);
$extraFieldValuesRepository = $em->getRepository(ExtraFieldValues::class);

$formattedExtraFieldValues = [];
$missingFields = [];

foreach ($fieldNames as $index => $fieldName) {
    $extraField = $extraFieldRepository->findOneBy(['variable' => $fieldName]);

    if (null === $extraField) {
        $missingFields[] = $fieldName;
        continue;
    }

    $values = $extraFieldValuesRepository->findBy(['field' => $extraField->getId()]);
    $markerGroup = [];

    foreach ($values as $value) {
        $address = trim((string) $value->getFieldValue());

        if ('' === $address) {
            continue;
        }

        $userInfo = api_get_user_info((int) $value->getItemId());

        if (empty($userInfo) || empty($userInfo['complete_name'])) {
            continue;
        }

        $markerGroup[] = [
            'address' => $address,
            'user_complete_name' => (string) $userInfo['complete_name'],
        ];
    }

    $formattedExtraFieldValues[$index] = $markerGroup;
}

if (!empty($missingFields)) {
    $warnings[] = sprintf(
        $plugin->get_lang('ExtraFieldsNotFoundWarning'),
        htmlspecialchars(implode(', ', $missingFields))
    );
}

$tpl->assign('admin_url', $plugin->getAdminUrl());
$tpl->assign('plugin_title', $plugin->get_lang('plugin_title'));
$tpl->assign('users_coordinates_map', $plugin->get_lang('UsersCoordinatesMap'));
$tpl->assign('users_coordinates_map_help', $plugin->get_lang('UsersCoordinatesMapHelp'));
$tpl->assign('no_user_coordinates_found', $plugin->get_lang('NoUserCoordinatesFound'));
$tpl->assign('configure_google_maps_first', $plugin->get_lang('ConfigureGoogleMapsFirst'));
$tpl->assign('warnings', $warnings);
$tpl->assign('api_ready', $plugin->isGoogleApiEnabled() && $plugin->hasApiKey());
$tpl->assign('extra_field_values_formatted', array_values($formattedExtraFieldValues));
$tpl->assign('configured_fields', $fieldNames);

$content = $tpl->fetch('GoogleMaps/view/map_coordinates.tpl');

$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
