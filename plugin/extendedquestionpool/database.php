<?php
/* For license terms, see /license.txt */

use Doctrine\DBAL\Types\Type;

/**
 * Plugin database installation script. Can only be executed if included
 * inside another script loading global.inc.php.
 *
 * @package chamilo.plugin.extendedquestionpool
 */
/**
 * Check if script can be called.
 */
if (!function_exists('api_get_path')) {
    exit('This script must be loaded through the Chamilo plugin installer sequence');
}

$entityManager = Database::getManager();

$connection = $entityManager->getConnection();
$platform = $connection->getDatabasePlatform();
$sm = $connection->getSchemaManager();

$extraFieldTable = Database::get_main_table(TABLE_EXTRA_FIELD);

$categoryExtraField = Database::select(
    "*",
    $extraFieldTable,
    [
        'where' => ['variable = ?' => 'additional_question_category'],
    ],
    'first'
);

if (!$categoryExtraField) {
    Database::insert(
        $extraFieldTable,
        [
            'extra_field_type' => 4,
            'field_type' => 1,
            'variable' => 'additional_question_category',
            'display_text' => 'Categoría adicional',
            'default_value' => '',
            'field_order' => 0,
            'visible_to_self' => 1,
            'changeable' => 1,
            'filter' => 1,
            'created_at' => api_get_utc_datetime(),
        ]
    );
} else {
    $query = "UPDATE $extraFieldTable 
                SET visible_to_self = 1, 
                visible_to_others = 1, 
                changeable = 1, 
                filter = 1
                WHERE variable = 'additional_question_category'";
    Database::query($query);
}

$categoryExtraField = Database::select(
    "*",
    $extraFieldTable,
    [
        'where' => ['variable = ?' => 'question_data1'],
    ],
    'first'
);

if (!$categoryExtraField) {
    Database::insert(
        $extraFieldTable,
        [
            'extra_field_type' => 4,
            'field_type' => 1,
            'variable' => 'question_data1',
            'display_text' => 'Campo 1',
            'default_value' => '',
            'field_order' => 0,
            'visible_to_self' => 1,
            'changeable' => 1,
            'filter' => 1,
            'created_at' => api_get_utc_datetime(),
        ]
    );
} else {
    $query = "UPDATE $extraFieldTable 
                SET visible_to_self = 1, 
                visible_to_others = 1, 
                changeable = 1, 
                filter = 1 
                WHERE variable = 'question_data1'";
    Database::query($query);
}

$categoryExtraField = Database::select(
    "*",
    $extraFieldTable,
    [
        'where' => ['variable = ?' => 'question_data2'],
    ],
    'first'
);

if (!$categoryExtraField) {
    Database::insert(
        $extraFieldTable,
        [
            'extra_field_type' => 4,
            'field_type' => 1,
            'variable' => 'question_data2',
            'display_text' => 'Campo 2',
            'default_value' => '',
            'field_order' => 0,
            'visible_to_self' => 1,
            'changeable' => 1,
            'filter' => 1,
            'created_at' => api_get_utc_datetime(),
        ]
    );
} else {
    $query = "UPDATE $extraFieldTable 
                SET visible_to_self = 1, 
                visible_to_others = 1, 
                changeable = 1, 
                filter = 1 
                WHERE variable = 'question_data2'";
    Database::query($query);
}

$categoryExtraField = Database::select(
    "*",
    $extraFieldTable,
    [
        'where' => ['variable = ?' => 'question_data3'],
    ],
    'first'
);

if (!$categoryExtraField) {
    Database::insert(
        $extraFieldTable,
        [
            'extra_field_type' => 4,
            'field_type' => 1,
            'variable' => 'question_data3',
            'display_text' => 'Campo 3',
            'default_value' => '',
            'field_order' => 0,
            'visible_to_self' => 1,
            'changeable' => 1,
            'filter' => 1,
            'created_at' => api_get_utc_datetime(),
        ]
    );
} else {
    $query = "UPDATE $extraFieldTable 
                SET visible_to_self = 1, 
                visible_to_others = 1, 
                changeable = 1, 
                filter = 1 
                WHERE variable = 'question_data3'";
    Database::query($query);
}

$categoryExtraField = Database::select(
    "*",
    $extraFieldTable,
    [
        'where' => ['variable = ?' => 'question_extra_info'],
    ],
    'first'
);

if (!$categoryExtraField) {
    Database::insert(
        $extraFieldTable,
        [
            'extra_field_type' => 4,
            'field_type' => 2,
            'variable' => 'question_extra_info',
            'display_text' => 'Información adicional',
            'default_value' => '',
            'field_order' => 0,
            'visible_to_self' => 1,
            'changeable' => 1,
            'filter' => 1,
            'created_at' => api_get_utc_datetime(),
        ]
    );
} else {
    $query = "UPDATE $extraFieldTable 
                SET visible_to_self = 1, 
                visible_to_others = 1, 
                changeable = 1, 
                filter = 1 
                WHERE variable = 'question_extra_info'";
    Database::query($query);
}