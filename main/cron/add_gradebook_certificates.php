<?php

/* For licensing terms, see /license.txt */

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
    foreach ($categoriesAndUsers as $categoryAndUser) {
        Category::generateUserCertificate(
            $categoryAndUser['category_id'],
            $categoryAndUser['user_id'],
            false,
            true
        );
    }
}

// Optional set the url id
// $_configuration['access_url'] = 7;

$sql = "SELECT * FROM gradebook_category
        WHERE generate_certificates = 1 AND parent_id = 0";
$result = Database::query($sql);
$categories = Database::store_result($result);
$total = count($categories);
$counter = 1;
foreach ($categories as $category) {
    $courseCode = $category['course_code'];
    $sessionId = (int) $category['session_id'];
    $filter = STUDENT;
    if (!empty($sessionId)) {
        $filter = 0;
    }
    $users = CourseManager::get_user_list_from_course_code(
        $courseCode,
        $sessionId,
        null,
        null,
        $filter
    );

    $_SESSION['id_session'] = $sessionId;

    echo "Category: ".$category['id']." Course: ".$courseCode." Session: $sessionId - Processing: $counter/".$total.PHP_EOL;
    foreach ($users as $user) {
        echo "Generating certificate user #".$user['user_id'].PHP_EOL;
        Category::generateUserCertificate(
            $category['id'],
            $user['user_id'],
            false,
            true
        );
    }
    $counter++;
}
