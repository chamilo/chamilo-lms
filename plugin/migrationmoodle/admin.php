<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\MigrationMoodle\Task\BaseTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\CourseCategoriesTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\CoursesTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\CourseUsersTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\CQuizTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\LearningPathsTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\LpDirsTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\LpDocumentsFilesTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\LpDocumentsTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\LpItemsTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\LpQuizzesTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\UsersTask;

require_once __DIR__.'/../../main/inc/global.inc.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

$selfUrl = api_get_self();

$actionNames = [
    'users' => 'Users',
    'course_categories' => 'Course categories',
    'courses' => 'Courses',
    'course_users' => 'Users in courses',
    'quizzes' => 'Quizzes',
    'learning_paths' => 'Learning Paths',
    'learning_path_chatpers' => 'Learning Paths: Chapters',
    'learning_path_items' => 'Learning Paths: Items',
    'learning_path_documents' => 'Learning Paths Items: Documents',
    'learning_path_documents_files' => 'Learning Paths Items: Documents files',
    'learning_path_quizzes' => 'Learning Paths Items: Quizzes',
];

foreach ($actionNames as $actionName => $actionTitle) {
    echo '<p>';
    echo '<a href="'.$selfUrl.'?action='.$actionName.'">'.$actionTitle.'</a>';
    echo '</p>';
}

if (!empty($action)) {
    echo '<h3>'.$actionNames[$action].'</h3>';

    /** @var BaseTask|null $task */
    $task = null;

    switch ($action) {
        case 'users':
            $task = new UsersTask();
            break;
        case 'course_categories':
            $task = new CourseCategoriesTask();
            break;
        case 'courses':
            $task = new CoursesTask();
            break;
        case 'course_users':
            $task = new CourseUsersTask();
            break;
        case 'quizzes':
            $task = new CQuizTask();
            break;
        case 'learning_paths':
            $task = new LearningPathsTask();
            break;
        case 'learning_path_chatpers':
            $task = new LpDirsTask();
            break;
        case 'learning_path_items':
            $task = new LpItemsTask();
            break;
        case 'learning_path_documents':
            $task =  new LpDocumentsTask();
            break;
        case 'learning_path_documents_files':
            $task = new LpDocumentsFilesTask();
            break;
        case 'learning_path_quizzes':
            $task = new LpQuizzesTask();
            break;
    }

    if ($task) {
        $task->execute();
    }
}



