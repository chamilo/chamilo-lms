<?php
/**
 * This script generates four session categories.
 */
require_once '../../main/inc/global.inc.php';
api_protect_admin_script();
$categories = array(
    'capacitación',
    'programas',
    'especializaciones',
    'cursos prácticos'
);
$tableSessionCategory = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
foreach ($categories as $category) {
    Database::query("INSERT INTO $tableSessionCategory (name) VALUES ('$category')");
}
