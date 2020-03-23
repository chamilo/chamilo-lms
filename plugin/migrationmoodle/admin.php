<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\MigrationMoodle\Task\BaseTask;

ini_set('memory_limit', -1);
ini_set('max_execution_time', 0);

$cidReset = true;

$outputBuffering = false;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script(true);

$action = isset($_GET['action']) ? $_GET['action'] : '';

$plugin = MigrationMoodlePlugin::create();

if ('true' != $plugin->get('active')) {
    api_not_allowed(true);
}

$menu = [
    '_' => [
        'course_categories',
        'courses',
        //'role_assignments',
        'users',
    ],
    'courses' => [
        'course_introductions',
        'course_sections',
        'course_modules_scorm',
    ],
    'course_sections' => [
        'files_for_course_sections',
        'course_modules_lesson',
        'course_modules_quiz',
        'course_modules_url',
        //'c_quiz',
        'sort_section_modules',
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
        'quizzes_scores',
    ],
    'quizzes' => [
        'files_for_quizzes',
        'question_categories',
        'questions',
    ],
    'questions' => [
        'question_multi_choice_single',
        'question_multi_choice_multiple',
        'questions_true_false',
        'question_short_answer',
        'question_gapselect',
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
    'course_modules_url' => [
        'urls',
    ],
    'users' => [
        'users_last_login',
        'track_login',
        'user_sessions',
    ],
    'user_sessions' => [
        'users_learn_paths',
        'users_scorms_view',
        'track_course_access',
    ],
    'users_learn_paths' => [
        'users_learn_paths_lesson_timer',
        'users_learn_paths_lesson_branch',
        'users_learn_paths_lesson_attempts',
        'users_learn_paths_quizzes',
    ],
    'users_learn_paths_quizzes' => [
        'users_quizzes_attempts',
        'user_question_attempts_shortanswer',
        'user_question_attempts_gapselect',
        'user_question_attempts_truefalse',
    ],
];

$htmlHeadXtra[] = '<style>.fa-ul {list-style-type: decimal; list-style-position: outside;}</style>';

Display::display_header($plugin->get_title());

echo '<div class="row">';
echo '<div class="col-sm-6 col-sm-push-6">';

if (!empty($action) && isAllowedAction($action, $menu) && !$plugin->isTaskDone($action)) {
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
echo '<div class="col-sm-6 col-sm-pull-6">';
echo displayMenu();
echo '</div>';
echo '</div>';

Display::display_footer();

/**
 * @param string $parent
 *
 * @return string
 */
function displayMenu($parent = '_')
{
    $plugin = MigrationMoodlePlugin::create();
    $menu = $GLOBALS['menu'];

    $items = $menu[$parent];

    $baseUrl = api_get_self()."?action=";

    $html = '<ol class="fa-ul">';

    foreach ($items as $item) {
        $title = api_underscore_to_camel_case($item);

        $html .= '<li>';

        if ($plugin->isTaskDone($item)) {
            $html .= Display::returnFontAwesomeIcon('check-square-o', '', true);
            $html .= $plugin->get_lang($title.'Task');
        } else {
            $html .= Display::returnFontAwesomeIcon('square-o', '', true);
            $html .= Display::url(
                $plugin->get_lang($title.'Task'),
                $baseUrl.$item
            );
        }

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
 *
 * @return bool
 */
function isAllowedAction($action, array $menu)
{
    foreach ($menu as $items) {
        if (in_array($action, $items)) {
            return true;
        }
    }

    return false;
}
