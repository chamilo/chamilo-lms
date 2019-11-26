<?php
/* See license terms in /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$categoryId = isset($_GET['selectcat']) ? intval($_GET['selectcat']) : false;

if (empty($categoryId)) {
    api_not_allowed(false);
}
$userId = api_get_user_id();

$cats = Category::load($categoryId);
if (isset($cats[0])) {
    $cat = $cats[0];
}

$allcat = $cats[0]->get_subcategories($userId, api_get_course_id(), api_get_session_id());
$alleval = $cats[0]->get_evaluations($userId);
$alllink = $cats[0]->get_links($userId);

$gradebooktable = new GradebookTable(
    $cat,
    $allcat,
    $alleval,
    $alllink,
    [],
    false
);
$gradebooktable->userId = $userId;
$table = $gradebooktable->return_table();

echo $gradebooktable->getGraph();
