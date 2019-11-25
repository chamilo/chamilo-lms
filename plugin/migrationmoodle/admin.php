<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\MigrationMoodle\Task\BaseTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\CourseCategoriesTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\CoursesTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\RoleAssignmentsTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\CQuizTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\FilesForLessonAnswersTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\CourseSectionsTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\LessonAnswersEssayTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\LessonAnswersMatchingTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\LessonAnswersMultipleAnswerTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\LessonAnswersMultipleChoiceTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\LessonAnswersShortAnswerTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\LessonAnswersTrueFalseTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\CourseModulesLessonTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\FilesForLessonPagesTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\LessonPagesDocumentTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\LessonPagesTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\LessonQuestionPagesQuestionTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\LessonQuestionPagesQuizTask;
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
                                [
                                    [
                                        'learnin_path_quiz_questions',
                                        'Questions',
                                        [
                                            [
                                                'learnin_path_quiz_answers_true_false',
                                                'Answers for True-False questions',
                                                []
                                            ],
                                            [
                                                'learnin_path_quiz_answers_multiple_choice',
                                                'Answers for Multiple Choice questions',
                                                []
                                            ],
                                            [
                                                'learnin_path_quiz_answers_multiple_answer',
                                                'Answers for Multiple Answers questions',
                                                []
                                            ],
                                            [
                                                'learnin_path_quiz_answers_matching',
                                                'Answers for Matching questions',
                                                []
                                            ],
                                            [
                                                'learnin_path_quiz_answers_essay',
                                                'Answers for Essay questions',
                                                []
                                            ],
                                            [
                                                'learnin_path_quiz_answers_short_answer',
                                                'Answers for Short-Answer and Numerical questions',
                                                []
                                            ]
                                        ],
                                    ],
                                    [
                                        'files_for_lesson_answers',
                                        'Files for answers',
                                        [],
                                    ],
                                ],
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
            $task = new RoleAssignmentsTask();
            break;
        case 'quizzes':
            $task = new CQuizTask();
            break;
        case 'learning_paths':
            $task = new CourseSectionsTask();
            break;
        case 'learning_path_chapters':
            $task = new CourseModulesLessonTask();
            break;
        case 'learning_path_items':
            $task = new LessonPagesTask();
            break;
        case 'learning_path_documents':
            $task = new LessonPagesDocumentTask();
            break;
        case 'learning_path_documents_files':
            $task = new FilesForLessonPagesTask();
            break;
        case 'learning_path_quizzes':
            $task = new LessonQuestionPagesQuizTask();
            break;
        case 'learnin_path_quiz_questions':
            $task = new LessonQuestionPagesQuestionTask();
            break;
        case 'learnin_path_quiz_answers_true_false':
            $task = new LessonAnswersTrueFalseTask();
            break;
        case 'learnin_path_quiz_answers_multiple_choice':
            $task = new LessonAnswersMultipleChoiceTask();
            break;
        case 'learnin_path_quiz_answers_multiple_answer':
            $task = new LessonAnswersMultipleAnswerTask();
            break;
        case 'learnin_path_quiz_answers_matching':
            $task = new LessonAnswersMatchingTask();
            break;
        case 'learnin_path_quiz_answers_essay':
            $task = new LessonAnswersEssayTask();
            break;
        case 'learnin_path_quiz_answers_short_answer':
            $task = new LessonAnswersShortAnswerTask();
            break;
        case 'files_for_lesson_answers':
            $task = new FilesForLessonAnswersTask();
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
