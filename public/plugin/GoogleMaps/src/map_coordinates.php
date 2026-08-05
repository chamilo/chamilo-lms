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
$provider = $plugin->getMapProvider();

if ($plugin->isGoogleMapsProvider()) {
    if (!$plugin->isGoogleApiEnabled()) {
        $warnings[] = $plugin->get_lang('GoogleMapsApiDisabledWarning');
    }

    if (!$plugin->hasApiKey()) {
        $warnings[] = $plugin->get_lang('GoogleMapsApiKeyMissingWarning');
    }
}

if ($plugin->isOpenStreetMapProvider()) {
    $warnings[] = $plugin->get_lang('OpenStreetMapInfo');
}

if (empty($fieldNames)) {
    $warnings[] = $plugin->get_lang('ExtraFieldNameMissingWarning');
}

if ($plugin->isGoogleMapsProvider() && $plugin->isProviderConfigured()) {
    global $htmlHeadXtra;

    $htmlHeadXtra[] = '<script src="https://maps.googleapis.com/maps/api/js?key='.rawurlencode($plugin->getApiKey()).'" async defer></script>';
}

$em = Database::getManager();
$extraFieldRepository = $em->getRepository(ExtraFieldEntity::class);
$extraFieldValuesRepository = $em->getRepository(ExtraFieldValues::class);

$formattedExtraFieldValues = [];
$missingFields = [];

$parseLocationValue = static function (?string $raw): ?array {
    $raw = trim((string) $raw);

    if ('' === $raw) {
        return null;
    }

    $label = '';
    $address = $raw;
    $lat = null;
    $lng = null;

    if ('{' === substr($raw, 0, 1)) {
        $decoded = json_decode($raw, true);
        if (JSON_ERROR_NONE === json_last_error() && is_array($decoded)) {
            $label = trim((string) ($decoded['label'] ?? $decoded['name'] ?? ''));
            $address = trim((string) ($decoded['address'] ?? $decoded['formatted_address'] ?? $raw));
            $lat = $decoded['lat'] ?? $decoded['latitude'] ?? null;
            $lng = $decoded['lng'] ?? $decoded['long'] ?? $decoded['longitude'] ?? null;
        }
    } elseif (str_contains($raw, '::')) {
        [$label, $coordinates] = explode('::', $raw, 2);
        $label = trim($label);
        $parts = array_map('trim', explode(',', $coordinates, 2));
        if (2 === count($parts)) {
            $lat = $parts[0];
            $lng = $parts[1];
            $address = $label;
        }
    } elseif (str_contains($raw, ',')) {
        $parts = array_map('trim', explode(',', $raw, 2));
        if (2 === count($parts) && is_numeric($parts[0]) && is_numeric($parts[1])) {
            $lat = $parts[0];
            $lng = $parts[1];
            $address = $raw;
        }
    }

    if (null !== $lat && null !== $lng && is_numeric($lat) && is_numeric($lng)) {
        $lat = (float) $lat;
        $lng = (float) $lng;

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            $lat = null;
            $lng = null;
        }
    } else {
        $lat = null;
        $lng = null;
    }

    return [
        'address' => $address,
        'label' => '' !== $label ? $label : $address,
        'lat' => $lat,
        'lng' => $lng,
    ];
};

foreach ($fieldNames as $index => $fieldName) {
    $extraField = $extraFieldRepository->findOneBy([
        'variable' => $fieldName,
        'itemType' => ExtraFieldEntity::USER_FIELD_TYPE,
    ]);

    if (null === $extraField) {
        $missingFields[] = $fieldName;
        continue;
    }

    $values = $extraFieldValuesRepository->findBy(['field' => $extraField->getId()]);
    $markerGroup = [];

    foreach ($values as $value) {
        $location = $parseLocationValue($value->getFieldValue());

        if (null === $location) {
            continue;
        }

        $userInfo = api_get_user_info((int) $value->getItemId());

        if (empty($userInfo) || empty($userInfo['complete_name'])) {
            continue;
        }

        $markerGroup[] = [
            'address' => $location['address'],
            'label' => $location['label'],
            'lat' => $location['lat'],
            'lng' => $location['lng'],
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
$tpl->assign('api_ready', $plugin->isProviderConfigured());
$tpl->assign('map_provider', $provider);
$tpl->assign('api_key', $plugin->getApiKey());
$tpl->assign('gmap_api_key', $plugin->getApiKey());
$tpl->assign('default_latitude', $plugin->getDefaultLatitude());
$tpl->assign('default_longitude', $plugin->getDefaultLongitude());
$tpl->assign('default_zoom', $plugin->getDefaultZoom());
$tpl->assign('extra_field_values_formatted', array_values($formattedExtraFieldValues));
$tpl->assign('configured_fields', $fieldNames);

$content = $tpl->fetch('GoogleMaps/view/map_coordinates.tpl');

$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
