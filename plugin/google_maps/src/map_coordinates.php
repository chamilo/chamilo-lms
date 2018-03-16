<?php
/* For licensing terms, see /license.txt */

/**
 * Show the map coordinates of all users geo extra field.
 *
 * @author José Loguercio Silva <jose.loguercio@beeznest.com>
 *
 * @package chamilo.plugin.google_maps
 */
$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_admin_script();

$plugin = GoogleMapsPlugin::create();

$apiIsEnable = $plugin->get('enable_api') === 'true';
$extraFieldName = $plugin->get('extra_field_name');

$extraFieldName = array_map('trim', explode(',', $extraFieldName));

if ($apiIsEnable) {
    $gmapsApiKey = $plugin->get('api_key');
    $htmlHeadXtra[] = '<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key='.$gmapsApiKey.'" ></script>';
}

$em = Database::getManager();
$extraField = $em->getRepository('ChamiloCoreBundle:ExtraField');

$extraFieldNames = [];

foreach ($extraFieldName as $field) {
    $extraFieldNames[] = $extraField->findOneBy(['variable' => $field]);
}

$extraFieldValues = [];

foreach ($extraFieldNames as $index => $fieldName) {
    if ($fieldName) {
        $extraFieldRepo = $em->getRepository('ChamiloCoreBundle:ExtraFieldValues');
        $extraFieldValues[] = $extraFieldRepo->findBy(['field' => $fieldName->getId()]);
    }
}

$templateName = $plugin->get_lang('UsersCoordinatesMap');

$tpl = new Template($templateName);

$formattedExtraFieldValues = [];

foreach ($extraFieldValues as $index => $extra) {
    foreach ($extra as $yandex => $field) {
        $thisUserExtraField = api_get_user_info($field->getItemId());
        $formattedExtraFieldValues[$index][$yandex]['address'] = $field->getValue();
        $formattedExtraFieldValues[$index][$yandex]['user_complete_name'] = $thisUserExtraField['complete_name'];
    }
}

$tpl->assign('extra_field_values_formatted', $formattedExtraFieldValues);
$tpl->assign('extra_field_values', $extraFieldValues);

$content = $tpl->fetch('google_maps/view/map_coordinates.tpl');

$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
