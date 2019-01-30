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

$extraField = new ExtraField('user');
$infoStage = $extraField->get_handler_field_info_by_field_variable('terms_villedustage');
$infoVille = $extraField->get_handler_field_info_by_field_variable('terms_ville');

$users = who_is_online(0, 500);

$data = [];
if (!empty($users)) {
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
                (u.id in ('".implode("','", $users)."') )
    ";
    //2643 or u.id = 2692 or u.id = 2656
    $result = Database::query($sql);
    $data = Database::store_result($result, 'ASSOC');
    foreach ($data as &$result) {
        $result['complete_name'] = addslashes(api_get_person_name($result['firstname'], $result['lastname']));
        $parts = explode('::', $result['ville']);
        $parts2 = explode(',', $parts[1]);
        $result['ville_lat'] = $parts2[0];
        $result['ville_long'] = $parts2[1];

        unset($result['ville']);

        $parts = explode('::', $result['stage']);
        $parts2 = explode(',', $parts[1]);
        $result['stage_lat'] = $parts2[0];
        $result['stage_long'] = $parts2[1];
        unset($result['stage']);
    }
}

$apiKey = api_get_configuration_value('google_api_key');
$htmlHeadXtra[] = '<script type="text/javascript" src="https://cdn.rawgit.com/googlemaps/js-marker-clusterer/gh-pages/src/markerclusterer.js"></script>';
$htmlHeadXtra[] = '<script type="text/javascript" src="https://jawj.github.com/OverlappingMarkerSpiderfier/bin/oms.min.js"></script>';

$tpl = new Template(null);
$tpl->assign('url', api_get_path(WEB_CODE_PATH).'social/profile.php');
$tpl->assign(
    'image_city',
    Display::return_icon(
        'accept.png',
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
        'delete.png',
        '',
        [],
        ICON_SIZE_SMALL,
        false,
        true
    )
);

$tpl->assign('places', json_encode($data));

$layout = $tpl->get_template('social/map.tpl');
$tpl->display($layout);
