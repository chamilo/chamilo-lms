<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Composer\ScriptHandler;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\FileHelper;

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
    if (Container::$container->get(FileHelper::class)->exists($file)) {
        Container::$container->get(FileHelper::class)->delete($file);
    }
    $dir = api_get_path(SYS_PUBLIC_PATH).'build';
    $files = scandir($dir);
    foreach ($files as $file) {
        if (preg_match('/main\..*\.js/', $file)) {
            Container::$container->get(FileHelper::class)->delete($dir.'/'.$file);
        }
    }

    $archive_path = api_get_path(SYS_ARCHIVE_PATH);
    $htaccess = Container::$container->get(FileHelper::class)->read($archive_path.'.htaccess');
    $result = rmdirr($archive_path, true, true);
    if (false === $result) {
        Display::addFlash(Display::return_message(get_lang('Cleanup of cache and temporary files'), 'error'));
    } else {
        Display::addFlash(
            Display::return_message(get_lang('The app/cache/ directory cleanup has been executed successfully.'))
        );
    }

    try {
        ScriptHandler::dumpCssFiles();
        Display::addFlash(
            Display::return_message(get_lang('The styles and assets in the web/ folder have been refreshed.'))
        );
    } catch (Exception $e) {
        Display::addFlash(
            Display::return_message(
                get_lang(
                    'The styles and assets in the web/ folder could not be refreshed, probably due to a permissions problem. Make sure the web/ folder is writeable by your web server.'
                ),
                'error'
            )
        );
        error_log($e->getMessage());
    }

    if (!empty($htaccess)) {
        Container::$container->get(FileHelper::class)->write($archive_path.'/.htaccess', $htaccess);
    }

    header('Location: '.api_get_self());
    exit;
}

Display::display_header(get_lang('Cleanup of cache and temporary files'));
echo Display::return_message(
    get_lang(
        'Chamilo keeps a copy of most of the temporary files it generates (for backups, exports, copies, etc) into its app/cache/ directory. After a while, this can add up to a very large amount of disk space being used for nothing. Click the button below to clean your archive directory up. This operation should be automated by a cron process, but if this is not possible, you can come to this page regularly to remove all temporary files from the directory.'
    ),
    'warning'
);
$form->display();
Display::display_footer();
