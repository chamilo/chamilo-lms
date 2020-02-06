<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\MigrationMoodle\Exceptions\Message as MigrationMoodleException;
use Chamilo\PluginBundle\MigrationMoodle\Task\BaseTask;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script(true);

$action = isset($_GET['action']) ? $_GET['action'] : '';

$plugin = MigrationMoodlePlugin::create();

if ('true' != $plugin->get('active')) {
    api_not_allowed(true);
}

$menu = [
    '_' => [
        //'users',
        'efc_users',
        'course_categories',
        //'courses',
        'efc_courses',
        'role_assignments',
        'efc_user_sessions',
    ],
    'efc_courses' => [
        'course_introductions',
        'course_sections',
        'course_modules_scorm',
    ],
    'course_sections' => [
        'files_for_course_sections',
        'course_modules_lesson',
        'course_modules_quiz',
        'course_modules_url',
        'c_quiz',
    ],
    'course_modules_lesson' => [
        'lesson_pages',
    ],
    'lesson_pages' => [
        'lesson_pages_document',
        'lesson_pages_quiz',
    ],
    'lesson_pages_document' => [
        'files_for_lesson_pages',
    ],
    'lesson_pages_quiz' => [
        'lesson_pages_quiz_question',
        'files_for_lesson_answers',
    ],
    'lesson_pages_quiz_question' => [
        'lesson_answers_true_false',
        'lesson_answers_multiple_choice',
        'lesson_answers_multiple_answer',
        'lesson_answers_matching',
        'lesson_answers_essay',
        'lesson_answers_short_answer',
    ],
    'course_modules_quiz' => [
        'quizzes',
    ],
    'quizzes' => [
        'files_for_quizzes',
        'question_categories',
    ],
    'question_categories' => [
        'questions',
    ],
    'questions' => [
        'question_multi_choice_single',
        'question_multi_choice_multiple',
        'questions_true_false',
    ],
    'course_modules_scorm' => [
        'scorm_scoes',
    ],
    'scorm_scoes' => [
        'files_for_scorm_scoes',
    ],
    'course_introductions' => [
        'files_for_course_introductions',
    ],
];

Display::display_header($plugin->get_title());

echo '<div class="row">';
echo '<div class="col-sm-6">';
echo displayMenu();
echo '</div>';
echo '<div class="col-sm-6">';

if (!empty($action) && isAllowedAction($action, $menu)) {
    $taskName = api_underscore_to_camel_case($action).'Task';

    echo Display::page_subheader(
        $plugin->get_lang($taskName)
    );

    $taskName = 'Chamilo\\PluginBundle\\MigrationMoodle\\Task\\'.$taskName;

    /** @var BaseTask $task */
    $task = new $taskName();

    echo '<pre>';
    $task->execute();
    echo '</pre>';
}

echo '</div>';
echo '</div>';

Display::display_footer();

/**
 * @param string $parent
 *
 * @return string
 */
function displayMenu($parent = '_') {
    $plugin = MigrationMoodlePlugin::create();
    $menu = $GLOBALS['menu'];

    $items = $menu[$parent];

    $baseUrl = api_get_self()."?action=";

    $html = '<ol>';

    foreach ($items as $item) {
        $title = api_underscore_to_camel_case($item);

        $html .= '<li>';
        $html .= Display::url(
            $plugin->get_lang($title.'Task'),
            $baseUrl.$item
        );

        if (isset($menu[$item])) {
            $html .= displayMenu($item);
        }

        $html .= '</li>';
    }

    $html .= '</ol>';

    return $html;
}

/**
 * @param string $action
 * @param array  $menu
 *
 * @return bool
 */
function isAllowedAction($action, array $menu) {
    foreach ($menu as $items) {
        if (in_array($action, $items)) {
            return true;
        }
    }

    return false;
}
