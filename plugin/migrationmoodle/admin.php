<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\MigrationMoodle\Task\BaseTask;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script(true);

$action = isset($_GET['action']) ? $_GET['action'] : '';

$plugin = MigrationMoodlePlugin::create();

if ('true' != $plugin->get('active')) {
    api_not_allowed(true);
}

$menu = [
    1 => [
        'action' => 'users',
        'parent' => 0,
    ],
    2 => [
        'action' => 'course_categories',
        'parent' => 0,
    ],
    3 => [
        'action' => 'courses',
        'parent' => 0,
    ],
    4 => [
        'action' => 'course_sections',
        'parent' => 3,
    ],
    5 => [
        'action' => 'course_modules_lesson',
        'parent' => 4,
    ],
    6 => [
        'action' => 'course_modules_quiz',
        'parent' => 4,
    ],
    7 => [
        'action' => 'lesson_pages',
        'parent' => 5,
    ],
    8 => [
        'action' => 'lesson_pages_document',
        'parent' => 7,
    ],
    9 => [
        'action' => 'files_for_lesson_pages',
        'parent' => 8,
    ],
    10 => [
        'action' => 'lesson_question_pages_quiz',
        'parent' => 7,
    ],
    11 => [
        'action' => 'lesson_question_pages_question',
        'parent' => 10,
    ],
    12 => [
        'action' => 'lesson_answers_true_false',
        'parent' => 11,
    ],
    13 => [
        'action' => 'lesson_answers_multiple_choice',
        'parent' => 11,
    ],
    14 => [
        'action' => 'lesson_answers_multiple_answer',
        'parent' => 11,
    ],
    15 => [
        'action' => 'lesson_answers_matching',
        'parent' => 11,
    ],
    16 => [
        'action' => 'lesson_answers_essay',
        'parent' => 11,
    ],
    17 => [
        'action' => 'lesson_answers_short_answer',
        'parent' => 11,
    ],
    18 => [
        'action' => 'files_for_lesson_answers',
        'parent' => 10,
    ],
    19 => [
        'action' => 'c_quiz',
        'parent' => 4,
    ],
    20 => [
        'action' => 'role_assignments',
        'parent' => 0,
    ],
];

Display::display_header($plugin->get_title());

echo '<div class="row">';
echo '<div class="col-sm-6">';
echo displayMenu($menu);
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
    $task->execute();
}

echo '</div>';
echo '</div>';

Display::display_footer();

/**
 * @param array $menu
 * @param int   $parent
 *
 * @return string
 */
function displayMenu(array $menu, $parent = 0) {
    /** @var MigrationMoodlePlugin $plugin */
    $plugin = $GLOBALS['plugin'];

    $items = array_filter(
        $menu,
        function ($item) use ($parent) {
            return $item['parent'] == $parent;
        }
    );

    $baseUrl = api_get_self()."?action=";

    $html = '<ol>';

    foreach ($items as $key => $item) {
        $title = api_underscore_to_camel_case($item['action']);

        $html .= '<li>';
        $html .= Display::url(
            $plugin->get_lang($title.'Task'),
            $baseUrl.$item['action']
        );

        $html .= displayMenu($menu, $key);

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
    foreach ($menu as $item) {
        if ($item['action'] == $action) {
            return true;
        }
    }

    return false;
}
