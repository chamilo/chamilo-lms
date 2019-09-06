<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.social
 *
 * @author Julio Montoya <gugli100@gmail.com>
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$fields = api_get_configuration_value('allow_social_map_fields');

if (!$fields) {
    api_not_allowed(true);
}

$fields = isset($fields['fields']) ? $fields['fields'] : '';

if (empty($fields)) {
    api_not_allowed(true);
}

$extraField = new ExtraField('user');
$infoStage = $extraField->get_handler_field_info_by_field_variable($fields['0']);
$infoVille = $extraField->get_handler_field_info_by_field_variable($fields['1']);

if (empty($infoStage) || empty($infoVille)) {
    api_not_allowed(true);
}

$gMapsPlugin = GoogleMapsPlugin::create();
$localization = $gMapsPlugin->get('enable_api') === 'true';

if ($localization) {
    $apiKey = $gMapsPlugin->get('api_key');
    if (empty($apiKey)) {
        api_not_allowed(true);
    }
} else {
    api_not_allowed(true);
}

$tableUser = Database::get_main_table(TABLE_MAIN_USER);
$sql = "SELECT u.id, firstname, lastname, ev.value ville, ev2.value stage
        FROM $tableUser u 
        INNER JOIN extra_field_values ev
        ON ev.item_id = u.id
        INNER JOIN extra_field_values ev2
        ON ev2.item_id = u.id
        WHERE 
            ev.field_id = ".$infoStage['id']." AND 
            ev2.field_id = ".$infoVille['id']." AND 
            u.status = ".STUDENT." AND
            u.active = 1 AND
            (ev.value <> '' OR ev2.value <> '') AND 
            (ev.value LIKE '%::%' OR ev2.value LIKE '%::%')            
";

$cacheDriver = new \Doctrine\Common\Cache\ApcuCache();
$keyDate = 'map_cache_date';
$keyData = 'map_cache_data';

$now = time();

// Refresh cache every day
//$tomorrow = strtotime('+1 day', $now);
$tomorrow = strtotime('+5 minute', $now);

$loadFromDatabase = true;
if ($cacheDriver->contains($keyData) && $cacheDriver->contains($keyDate)) {
    $savedDate = $cacheDriver->fetch($keyDate);
    $loadFromDatabase = false;
    if ($savedDate < $now) {
        $loadFromDatabase = true;
    }
}

$loadFromDatabase = true;
if ($loadFromDatabase) {
    $result = Database::query($sql);
    $data = Database::store_result($result, 'ASSOC');

    $cacheDriver->save($keyData, $data);
    $cacheDriver->save($keyDate, $tomorrow);
} else {
    $data = $cacheDriver->fetch($keyData);
}

foreach ($data as &$result) {
    // Clean process is made in twig with escape('js')
    $result['complete_name'] = $result['firstname'].' '.$result['lastname'];
    $result['lastname'] = '';
    $result['firstname'] = '';
    $parts = explode('::', $result['ville']);
    if (isset($parts[1]) && !empty($parts[1])) {
        $parts2 = explode(',', $parts[1]);
        $result['ville_lat'] = $parts2[0];
        $result['ville_long'] = $parts2[1];

        unset($result['ville']);
    }

    $parts = explode('::', $result['stage']);
    if (isset($parts[1]) && !empty($parts[1])) {
        $parts2 = explode(',', $parts[1]);
        $result['stage_lat'] = $parts2[0];
        $result['stage_long'] = $parts2[1];
        unset($result['stage']);
    }
}

$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_JS_PATH).'map/markerclusterer.js"></script>';
$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_JS_PATH).'map/oms.min.js"></script>';

$tpl = new Template(null);
$tpl->assign('url', api_get_path(WEB_CODE_PATH).'social/profile.php');
$tpl->assign(
    'image_city',
    Display::return_icon(
        'red-dot.png',
        '',
        [],
        ICON_SIZE_SMALL,
        false,
        true
    )
);
$tpl->assign(
    'image_stage',
    Display::return_icon(
        'blue-dot.png',
        '',
        [],
        ICON_SIZE_SMALL,
        false,
        true
    )
);

$tpl->assign('places', json_encode($data));
$tpl->assign('api_key', $apiKey);

$tpl->assign('field_1', $infoStage['display_text']);
$tpl->assign('field_2', $infoVille['display_text']);

$layout = $tpl->get_template('social/map.tpl');
$tpl->display($layout);
