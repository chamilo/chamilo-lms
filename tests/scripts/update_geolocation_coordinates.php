<?php
/* For licensing terms, see /license.txt */
exit;
require __DIR__.'/../../main/inc/global.inc.php';

$extraField = new ExtraField('user');
$extraFieldValue = new ExtraFieldValue('user');

$infoStage = $extraField->get_handler_field_info_by_field_variable('terms_villedustage');
$infoVille = $extraField->get_handler_field_info_by_field_variable('terms_ville');

$tableUser = Database::get_main_table(TABLE_MAIN_USER);
$tableValues = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);

// Ville

echo '<h3>Ville:</h3>';
$sql = "SELECT u.id, ev.id ville_id, ev.value ville
        FROM $tableUser u 
        INNER JOIN extra_field_values ev
        ON ev.item_id = u.id
        WHERE 
            ev.field_id = ".$infoVille['id']." AND          
            u.active = 1 AND
            (ev.value <> '') AND 
            (ev.value NOT LIKE '%::%') 
";
//2643 or u.id = 2692 or u.id = 2656
$result = Database::query($sql);
$data = Database::store_result($result, 'ASSOC');
foreach ($data as $result) {
    if (!empty($result['ville'])) {
        $newAdress = getCoordinates($result['ville']);
        if ($newAdress) {
            $sql = "UPDATE $tableValues SET value = '".$newAdress."' WHERE id = ".$result['ville_id'];
            Database::query($sql);
            var_dump($result['ville']."-".$sql);
        } else {
            var_dump("nothing found for ville:  ".$result['ville']);
        }
    }
}

// stage

echo '<h3>Stage:</h3>';

$sql = "SELECT u.id, ev2.id stage_id, ev2.value stage
        FROM $tableUser u
        INNER JOIN extra_field_values ev2
        ON ev2.item_id = u.id
        WHERE             
            ev2.field_id = ".$infoStage['id']." AND
            u.active = 1 AND
            (ev2.value <> '') AND 
            (ev2.value NOT LIKE '%::%') 
";
//2643 or u.id = 2692 or u.id = 2656
$result = Database::query($sql);
$data = Database::store_result($result, 'ASSOC');
foreach ($data as $result) {
    if (!empty($result['stage'])) {
        $newAdress = getCoordinates($result['stage']);
        if ($newAdress) {
            $sql = "UPDATE $tableValues SET value = '".$newAdress."' WHERE id = ".$result['stage_id'];
            Database::query($sql);
            var_dump($result['stage']."-".$sql);
        } else {
            var_dump("nothing found for ".$result['stage']);
        }
    }
}


function getCoordinates($address)
{
    static $list;

    if (empty($address)) {
        return false;
    }

    if (isset($list[$address])) {
        return $list[$address];
    }

    $key = api_get_configuration_value('google_api_key');
    $prepAddr = str_replace(' ', '+', $address);
    $geocode = file_get_contents(
        'https://maps.google.com/maps/api/geocode/json?key='.$key.'&address='.$prepAddr.'&sensor=false'
    );
    $output = json_decode($geocode);
    $error = json_last_error();
    if ($error == JSON_ERROR_NONE && isset($output->results[0])) {
        $latitude = $output->results[0]->geometry->location->lat;
        $longitude = $output->results[0]->geometry->location->lng;
        if ($latitude != '' && $longitude != '') {
            $result = "$address::$latitude,$longitude";
            $list[$address] = $result;

            return $result;
        }
    }

    return false;
}
