<?php
/* For license terms, see /license.txt */
/**
 * This script generates four session categories.
 *
 * @package chamilo.plugin.advanced_subscription
 */
require_once __DIR__.'/../../config.php';

api_protect_admin_script();

$categories = [
    'capacitaciones',
    'programas',
    'especializaciones',
    'cursos prácticos',
];
$tableSessionCategory = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
foreach ($categories as $category) {
    Database::query("INSERT INTO $tableSessionCategory (name) VALUES ('$category')");
}
