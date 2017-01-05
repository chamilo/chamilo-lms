<?php
/* For licensing terms, see /license.txt */

/**
 * Show the map coordinates of all users geo extra field
 * @author José Loguercio Silva <jose.loguercio@beeznest.com>
 * @package chamilo.plugin.google_maps
 */


$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_admin_script();

$plugin = GoogleMapsPlugin::create();

$apiIsEnable = $plugin->get('enable_api') === 'true';
$extraFieldName = $plugin->get('extra_field_name');

if ($apiIsEnable) {
    $gmapsApiKey = $plugin->get('api_key');
    $htmlHeadXtra[] = '<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key='. $gmapsApiKey . '" ></script>';
}

$em = Database::getManager();

$extraField = $em->getRepository('ChamiloCoreBundle:ExtraField');
$extraField = $extraField->findOneBy(['variable' => $extraFieldName]);

if ($extraField) {
    $extraFieldValues = $em->getRepository('ChamiloCoreBundle:ExtraFieldValues');
    $extraFieldValues = $extraFieldValues->findBy(['field' => $extraField->getId()]);
}

$templateName = $plugin->get_lang('UsersCoordinatesMap');

$tpl = new Template($templateName);

$tpl->assign('extra_field_values', $extraFieldValues);

$content = $tpl->fetch('google_maps/view/map_coordinates.tpl');

$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();

