<?php
/* For licensing terms, see /license.txt */
/**
 *   @package chamilo.admin
 */
// resetting the course id
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

ini_set('memory_limit', -1);
ini_set('max_execution_time', 0);

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];

$form = new FormValidator(
    'archive_cleanup_form',
    'post',
    '',
    '',
    [],
    FormValidator::LAYOUT_BOX
);
$form->addButtonSend(get_lang('Proceed with cleanup'));

if ($form->validate()) {
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }

    $file = api_get_path(SYS_PUBLIC_PATH).'build/main.js';
    if (file_exists($file)) {
        unlink($file);
    }
    $dir = api_get_path(SYS_PUBLIC_PATH).'build';
    $files = scandir($dir);
    foreach ($files as $file) {
        if (preg_match('/main\..*\.js/', $file)) {
            unlink($dir.'/'.$file);
        }
    }

    $archive_path = api_get_path(SYS_ARCHIVE_PATH);
    $htaccess = @file_get_contents($archive_path.'.htaccess');
    $result = rmdirr($archive_path, true, true);
    if (false === $result) {
        Display::addFlash(Display::return_message(get_lang('Cleanup of cache and temporary filesFailed'), 'error'));
    } else {
        Display::addFlash(Display::return_message(get_lang('The app/cache/ directory cleanup has been executed successfully.')));
    }
    try {
        \Chamilo\CoreBundle\Composer\ScriptHandler::dumpCssFiles();
        Display::addFlash(Display::return_message(get_lang('The styles and assets in the web/ folder have been refreshed.')));
    } catch (Exception $e) {
        Display::addFlash(Display::return_message(get_lang('The styles and assets in the web/ folder could not be refreshed, probably due to a permissions problem. Make sure the web/ folder is writeable by your web server.'), 'error'));
        error_log($e->getMessage());
    }

    if (!empty($htaccess)) {
        @file_put_contents($archive_path.'/.htaccess', $htaccess);
    }

    header('Location: '.api_get_self());
    exit;
}

Display::display_header(get_lang('Cleanup of cache and temporary files'));
echo Display::return_message(get_lang('Cleanup of cache and temporary filesDescr'), 'warning');
$form->display();
Display::display_footer();
