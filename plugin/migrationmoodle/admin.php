<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\MigrationMoodle\Task\CourseCategoriesTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\CoursesTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\CourseUsersTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\UsersTask;

require_once __DIR__.'/../../main/inc/global.inc.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

$selfUrl = api_get_self();

$actionNames = [
    'users' => 'Users',
    'course_categories' => 'Course categories',
    'courses' => 'Courses',
    'course_users' => 'Users in courses',
];

foreach ($actionNames as $actionName => $actionTitle) {
    echo '<p>';
    echo '<a href="'.$selfUrl.'?action='.$actionName.'">'.$actionTitle.'</a>';
    echo '</p>';
}

if (!empty($action)) {
    echo '<h3>'.$actionNames[$action].'</h3>';

    switch ($action) {
        case 'users':
            $usersMigration = new UsersTask();
            $usersMigration->execute();
            break;
        case 'course_categories':
            $courseCategoriesMigration = new CourseCategoriesTask();
            $courseCategoriesMigration->execute();
            break;
        case 'courses':
            $coursesMigration = new CoursesTask();
            $coursesMigration->execute();
            break;
        case 'course_users':
            $courseUsersMigration = new CourseUsersTask();
            $courseUsersMigration->execute();
            break;
    }
}



