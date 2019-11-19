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

api_protect_admin_script(true);

$action = isset($_GET['action']) ? $_GET['action'] : '';

$plugin = MigrationMoodlePlugin::create();

if ('true' != $plugin->get('active')) {
    api_not_allowed(true);
}

$menu = [
    [
        'users',
        'Users',
        [],
    ],
    [
        'course_categories',
        'Course categories',
        [],
    ],
    [
        'courses',
        'Courses',
        [

            [
                'learning_paths',
                'Learning paths',
                [

                    [
                        'learning_path_chapters',
                        'Sections',
                        [],
                    ],
                    [
                        'learning_path_items',
                        'Items',
                        [

                            [
                                'learning_path_documents',
                                'Documents',
                                [
                                    [
                                        'learning_path_documents_files',
                                        'Document files',
                                        [],
                                    ],
                                ],
                            ],
                            [
                                'learning_path_quizzes',
                                'Quizzes',
                                [],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'quizzes',
                'Quizzes',
                [],
            ],
        ],
    ],
    [
        'course_users',
        'Subcribe users to courses',
        [],
    ],
];

Display::display_header($plugin->get_title());

echo '<div class="row">';
echo '<div class="col-sm-4">';
echo displayMenu($menu);
echo '</div>';
echo '<div class="col-sm-8">';

if (!empty($action)) {
    echo Display::page_subheader(
        getActionTitle($menu, $action)
    );

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
        case 'learning_path_chapters':
            $task = new LpDirsTask();
            break;
        case 'learning_path_items':
            $task = new LpItemsTask();
            break;
        case 'learning_path_documents':
            $task = new LpDocumentsTask();
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

echo '</div>';
echo '</div>';

Display::display_footer();

/**
 * @param array $menu
 *
 * @return string
 */
function displayMenu(array $menu)
{
    $baseUrl = api_get_self()."?action=";

    $html = '<ol>';

    foreach ($menu as $item) {
        list($action, $title, $subMenu) = $item;

        $html .= '<li>';
        $html .= Display::url(
            $title,
            $baseUrl.$action
        );

        if ($subMenu) {
            $html .= displayMenu($subMenu);
        }

        $html .= '</li>';
    }

    $html .= '</ol>';

    return $html;
}

/**
 * @param array  $menu
 * @param string $action
 *
 * @return string
 */
function getActionTitle(array $menu, $action)
{
    $flag = false;
    $title = '';

    array_walk_recursive(
        $menu,
        function ($value, $key) use ($action, &$flag, &$title) {
            if ($flag) {
                $title = $value;
            }

            $flag = false;

            if (0 == $key && $value == $action) {
                $flag = true;
            }
        }
    );

    return $title;
}
