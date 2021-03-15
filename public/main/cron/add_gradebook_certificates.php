<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

/**
 * Adds gradebook certificates to gradebook_certificate table from users
 * who have achieved the requirements but have not reviewed them yet.
 *
 * @author Imanol Losada <imanol.losada@beeznest.com>
 */

require_once __DIR__.'/../inc/global.inc.php';

/**
 * Get all categories and users ids from gradebook.
 *
 * @return array Categories and users ids
 */
function getAllCategoriesAndUsers()
{
    $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
    $jointable = Database::get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION);
    $joinStatement = ' JOIN '.$jointable.' ON '.$table.'.evaluation_id = '.$jointable.'.id';

    return Database::select(
        'DISTINCT '.$jointable.'.category_id,'.$table.'.user_id',
        $table.$joinStatement
    );
}

if ($categoriesAndUsers = getAllCategoriesAndUsers()) {
    $repo = Container::getGradeBookCategoryRepository();
    foreach ($categoriesAndUsers as $categoryAndUser) {
        $category = $repo->find($categoryAndUser['category_id']);
        Category::generateUserCertificate($category, $categoryAndUser['user_id']);
    }
}
