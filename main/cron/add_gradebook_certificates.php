<?php
require_once __DIR__.'/../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'database.lib.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';

/**
 * Adds gradebook certificates to gradebook_certificate table from users
 * who have achieved the requirements but have not reviewed them yet
 * @package chamilo.cron
 * @author Imanol Losada <imanol.losada@beeznest.com>
 */

/**
 * Get all categories and users ids from gradebook
 * @return array Categories and users ids
 */
function getAllCategoriesAndUsers() {
    $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
    $jointable = Database::get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION);
    $joinStatement = ' JOIN '.$jointable.' ON '.$table.'.evaluation_id = '.$jointable.'.id';
    return Database::select(
        'DISTINCT '.$jointable.'.category_id,'.$table.'.user_id',
        $table.$joinStatement
    );
}

if ($categoriesAndUsers = getAllCategoriesAndUsers()) {
    foreach ($categoriesAndUsers as $categoryAndUser) {
        Category::register_user_certificate(
            $categoryAndUser['category_id'],
            $categoryAndUser['user_id']
        );
    }
}
