<?php

/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

ini_set('memory_limit', -1);
ini_set('max_execution_time', 0);

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];

$form = new FormValidator(
    'archive_cleanup_form',
    'post',
    '',
    '',
    [],
    FormValidator::LAYOUT_BOX
);
$form->addButtonSend(get_lang('ArchiveDirCleanupProceedButton'));

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
    $htaccess = <<<TEXT
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order deny,allow
    Deny from all
</IfModule>
# pChart generated files should be allowed
<FilesMatch "^[0-9a-f]+$">
    order allow,deny
    allow from all
</FilesMatch>
php_flag engine off
TEXT;

    $result = rmdirr($archive_path, true, true);
    if (false === $result) {
        Display::addFlash(Display::return_message(get_lang('ArchiveDirCleanupFailed'), 'error'));
    } else {
        Display::addFlash(Display::return_message(get_lang('ArchiveDirCleanupSucceeded')));
    }
    try {
        \Chamilo\CoreBundle\Composer\ScriptHandler::dumpCssFiles();
        Display::addFlash(Display::return_message(get_lang('WebFolderRefreshSucceeded')));
    } catch (Exception $e) {
        Display::addFlash(Display::return_message(get_lang('WebFolderRefreshFailed'), 'error'));
        error_log($e->getMessage());
    }

    if (!empty($htaccess)) {
        @file_put_contents($archive_path.'/.htaccess', $htaccess);
    }

    header('Location: '.api_get_self());
    exit;
}

Display::display_header(get_lang('ArchiveDirCleanup'));
echo Display::return_message(get_lang('ArchiveDirCleanupDescr'), 'warning');
$form->display();
Display::display_footer();
