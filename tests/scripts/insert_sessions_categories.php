<?php

/* For licensing terms, see /license.txt */

/**
 * This script generates four session categories.
 */
exit;
require_once '../../main/inc/global.inc.php';
api_protect_admin_script();

$accessUrlId = api_get_current_access_url_id();

$categories = array(
    'capacitaciones',
    'programas',
    'especializaciones',
    'cursos prácticos'
);
$tableSessionCategory = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
foreach ($categories as $category) {
    Database::insert(
        $tableSessionCategory,
        [
            'name' => $category,
            'access_url_id' => $accessUrlId
        ]
    );
}
