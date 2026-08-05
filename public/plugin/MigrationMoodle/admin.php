<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\MigrationMoodle\Script\BaseScript;
use Chamilo\PluginBundle\MigrationMoodle\Task\BaseTask;

ini_set('memory_limit', -1);
ini_set('max_execution_time', 0);

$cidReset = true;
$outputBuffering = false;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script(true);

$plugin = MigrationMoodlePlugin::create();

if (!$plugin->isEnabled()) {
    api_not_allowed(true);
}

$action = isset($_GET['action']) ? Security::remove_XSS((string) $_GET['action']) : '';
$menuTasks = $plugin->getAdminTaskMenu();
$menuScripts = $plugin->getAdminScriptMenu();
$messages = [];
$executionTitle = '';
$executionWasRequested = false;

if ('' !== $action) {
    $executionWasRequested = true;

    if (!$plugin->hasRequiredDatabaseConfiguration()) {
        $messages[] = Display::return_message(
            $plugin->get_lang('MissingRequiredDatabaseConfiguration'),
            'warning'
        );
        $action = '';
    }

    if ('' !== $action) {
        $isTaskAction = isAllowedAction($action, $menuTasks);
        $isScriptAction = isAllowedAction($action, $menuScripts);

        if (!$isTaskAction && !$isScriptAction) {
            $messages[] = Display::return_message(
                $plugin->get_lang('InvalidMigrationAction'),
                'error'
            );
            $action = '';
        }

        if ('' !== $action && !Security::check_token('get')) {
            $messages[] = Display::return_message(
                $plugin->get_lang('InvalidSecurityToken'),
                'error'
            );
            $action = '';
        }

        Security::clear_token();
    }
}

$token = Security::get_token();

$executionOutput = '';

if ($executionWasRequested && '' !== $action) {
    ob_start();

    $isTaskAction = isAllowedAction($action, $menuTasks);
    $isScriptAction = isAllowedAction($action, $menuScripts);

    if ($isTaskAction && !$plugin->isTaskDone($action)) {
        $taskName = api_underscore_to_camel_case($action).'Task';
        $executionTitle = $plugin->get_lang($taskName);

        echo $executionTitle.PHP_EOL.PHP_EOL;

        executeTask($taskName);
    } elseif ($isScriptAction && !$plugin->isTaskDone($action)) {
        $scriptName = api_underscore_to_camel_case($action).'Script';
        $executionTitle = $plugin->get_lang($scriptName);

        echo $executionTitle.PHP_EOL.PHP_EOL;

        executeScript($scriptName);
    } else {
        echo $plugin->get_lang('TaskAlreadyExecuted').PHP_EOL;
    }

    $executionOutput = (string) ob_get_clean();
} else {
    $executionOutput = $plugin->get_lang('SelectTaskToRun').PHP_EOL;
}

$htmlHeadXtra[] = '<style>
.migration-moodle-layout { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1rem; }
@media (min-width: 1200px) { .migration-moodle-layout { grid-template-columns: minmax(0, 520px) minmax(0, 1fr); } }
.migration-moodle-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 1rem; box-shadow: 0 1px 3px rgba(15, 23, 42, .08); }
.migration-moodle-card__header { padding: 1rem 1.25rem; border-bottom: 1px solid #eef2f7; }
.migration-moodle-card__body { padding: 1rem 1.25rem; }
.migration-moodle-summary { display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); gap: .75rem; }
@media (min-width: 768px) { .migration-moodle-summary { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
.migration-moodle-summary__item { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: .75rem; padding: .75rem; }
.migration-moodle-summary__label { color: #64748b; font-size: .75rem; font-weight: 700; text-transform: uppercase; }
.migration-moodle-summary__value { color: #0f172a; font-size: 1.125rem; font-weight: 700; }
.migration-moodle-tree, .migration-moodle-tree ol { list-style: none; margin: 0; padding-left: 1.25rem; }
.migration-moodle-tree > li { margin-bottom: .35rem; }
.migration-moodle-task { align-items: center; display: flex; gap: .5rem; min-height: 2rem; }
.migration-moodle-task__icon { align-items: center; border-radius: 999px; display: inline-flex; height: 1.5rem; justify-content: center; width: 1.5rem; }
.migration-moodle-task__icon--done { background: #dcfce7; color: #15803d; }
.migration-moodle-task__icon--ready { background: #dbeafe; color: #1d4ed8; }
.migration-moodle-task__icon--locked { background: #f1f5f9; color: #94a3b8; }
.migration-moodle-task__link { color: #1d4ed8; font-weight: 600; text-decoration: none; }
.migration-moodle-task__link:hover { text-decoration: underline; }
.migration-moodle-task__locked { color: #64748b; }
.migration-moodle-log { background: #0f172a; border-radius: .75rem; color: #e5e7eb; font-size: .8125rem; margin: 0; max-height: 760px; min-height: 220px; overflow: auto; padding: 1rem; white-space: pre-wrap; }
.migration-moodle-help { color: #64748b; font-size: .875rem; line-height: 1.45; }
</style>';

Display::display_header($plugin->get_title());

$configUrl = api_get_path(WEB_CODE_PATH).'admin/configure_plugin.php?plugin=MigrationMoodle';

echo '<section class="migration-moodle-card" style="margin-bottom: 1rem;">';
echo '<div class="migration-moodle-card__header">';
echo '<h2 style="margin: 0;">'.Security::remove_XSS($plugin->get_title()).'</h2>';
echo '<p class="migration-moodle-help" style="margin: .5rem 0 0;">'.$plugin->get_lang('AdminIntro').'</p>';
echo '</div>';
echo '<div class="migration-moodle-card__body">';
echo '<div class="migration-moodle-summary">';
echo '<div class="migration-moodle-summary__item">';
echo '<div class="migration-moodle-summary__label">'.$plugin->get_lang('MoodleDatabase').'</div>';
echo '<div class="migration-moodle-summary__value">'.($plugin->hasRequiredDatabaseConfiguration() ? $plugin->get_lang('Configured') : $plugin->get_lang('NotConfigured')).'</div>';
echo '</div>';
echo '<div class="migration-moodle-summary__item">';
echo '<div class="migration-moodle-summary__label">'.$plugin->get_lang('MoodledataPath').'</div>';
echo '<div class="migration-moodle-summary__value">'.('' !== $plugin->getMoodledataPath() ? $plugin->get_lang('Configured') : $plugin->get_lang('NotConfigured')).'</div>';
echo '</div>';
echo '<div class="migration-moodle-summary__item">';
echo '<div class="migration-moodle-summary__label">'.$plugin->get_lang('AccessUrlId').'</div>';
echo '<div class="migration-moodle-summary__value">'.(int) $plugin->getAccessUrlId().'</div>';
echo '</div>';
echo '</div>';
echo '<p class="migration-moodle-help" style="margin: 1rem 0 0;">';
echo Display::url(
    Display::returnFontAwesomeIcon('cog', '', true).' '.$plugin->get_lang('ConfigurePlugin'),
    $configUrl,
    ['class' => 'btn btn--plain']
);
echo '</p>';
echo '</div>';
echo '</section>';

if (!$plugin->hasRequiredDatabaseConfiguration()) {
    $messages[] = Display::return_message(
        $plugin->get_lang('MissingRequiredDatabaseConfiguration'),
        'warning'
    );
}

if ('' === $plugin->getMoodledataPath()) {
    $messages[] = Display::return_message(
        $plugin->get_lang('MissingMoodledataPathWarning'),
        'warning'
    );
}

foreach ($messages as $message) {
    echo $message;
}

echo '<div class="migration-moodle-layout">';

echo '<div>';
echo '<section class="migration-moodle-card" style="margin-bottom: 1rem;">';
echo '<div class="migration-moodle-card__header"><h3 style="margin: 0;">'.$plugin->get_lang('MigrationTasks').'</h3></div>';
echo '<div class="migration-moodle-card__body">';
echo displayMenu($menuTasks, 'Task', '_', $token, $plugin->hasRequiredDatabaseConfiguration());
echo '</div>';
echo '</section>';

echo '<section class="migration-moodle-card">';
echo '<div class="migration-moodle-card__header"><h3 style="margin: 0;">'.$plugin->get_lang('MaintenanceScripts').'</h3></div>';
echo '<div class="migration-moodle-card__body">';
echo displayMenu($menuScripts, 'Script', '_', $token, $plugin->hasRequiredDatabaseConfiguration());
echo '</div>';
echo '</section>';
echo '</div>';

echo '<section class="migration-moodle-card">';
echo '<div class="migration-moodle-card__header"><h3 style="margin: 0;">'.$plugin->get_lang('ExecutionOutput').'</h3></div>';
echo '<div class="migration-moodle-card__body">';

echo '<pre class="migration-moodle-log">';
echo htmlspecialchars($executionOutput, ENT_QUOTES, 'UTF-8');
echo '</pre>';

echo '</div>';
echo '</section>';

echo '</div>';

Display::display_footer();

function executeTask(string $taskName): void
{
    $taskClass = 'Chamilo\\PluginBundle\\MigrationMoodle\\Task\\'.$taskName;

    if (!class_exists($taskClass)) {
        echo 'Task class not found: '.$taskClass.PHP_EOL;

        return;
    }

    try {
        /** @var BaseTask $task */
        $task = new $taskClass();
        $task->execute();
    } catch (Throwable $throwable) {
        echo 'Task failed: '.$throwable->getMessage().PHP_EOL;
    }
}

function executeScript(string $scriptName): void
{
    $scriptClass = 'Chamilo\\PluginBundle\\MigrationMoodle\\Script\\'.$scriptName;

    if (!class_exists($scriptClass)) {
        echo 'Script class not found: '.$scriptClass.PHP_EOL;

        return;
    }

    try {
        /** @var BaseScript $script */
        $script = new $scriptClass();
        $script->run();
    } catch (Throwable $throwable) {
        echo 'Script failed: '.$throwable->getMessage().PHP_EOL;
    }
}

function displayMenu(array $menu, string $type = 'Task', string $parent = '_', string $token = '', bool $canRun = true): string
{
    $plugin = MigrationMoodlePlugin::create();

    if (!isset($menu[$parent])) {
        return '';
    }

    $items = $menu[$parent];
    $isParentDone = '_' === $parent || $plugin->isTaskDone($parent);
    $baseUrl = api_get_self();

    $html = '<ol class="migration-moodle-tree">';

    foreach ($items as $item) {
        $title = api_underscore_to_camel_case($item);
        $label = $plugin->get_lang($title.$type);
        $isDone = $plugin->isTaskDone($item);
        $isReady = $canRun && $isParentDone && !$isDone;

        $html .= '<li>';
        $html .= '<div class="migration-moodle-task">';

        if ($isDone) {
            $html .= '<span class="migration-moodle-task__icon migration-moodle-task__icon--done">';
            $html .= Display::returnFontAwesomeIcon('check', '', true);
            $html .= '</span>';
            $html .= '<span>'.Security::remove_XSS($label).'</span>';
        } elseif ($isReady) {
            $url = $baseUrl.'?'.http_build_query([
                'action' => $item,
                'sec_token' => $token,
            ]);
            $html .= '<span class="migration-moodle-task__icon migration-moodle-task__icon--ready">';
            $html .= Display::returnFontAwesomeIcon('play', '', true);
            $html .= '</span>';
            $html .= Display::url(
                Security::remove_XSS($label),
                $url,
                [
                    'class' => 'migration-moodle-task__link',
                    'onclick' => "return confirm('Run this migration step now?');",
                ]
            );
        } else {
            $html .= '<span class="migration-moodle-task__icon migration-moodle-task__icon--locked">';
            $html .= Display::returnFontAwesomeIcon('lock', '', true);
            $html .= '</span>';
            $html .= '<span class="migration-moodle-task__locked">'.Security::remove_XSS($label).'</span>';
        }

        $html .= '</div>';

        if (isset($menu[$item])) {
            $html .= displayMenu($menu, $type, $item, $token, $canRun);
        }

        $html .= '</li>';
    }

    $html .= '</ol>';

    return $html;
}

function isAllowedAction(string $action, array $menu): bool
{
    foreach ($menu as $items) {
        if (in_array($action, $items, true)) {
            return true;
        }
    }

    return false;
}
