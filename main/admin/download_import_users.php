<?php
/* For licensing terms, see /license.txt */

/**
 * This tool allows platform admins to check history by csv file.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$userId = api_get_user_id();
api_protect_admin_script(true, null);
api_protect_limit_for_session_admin();
set_time_limit(0);

/**
 * Read all the archive files previously placed in app/cache/backup/import_users/[user]/
 * when users were imported through CSV (one can only see the users imported by
 * oneself).
 *
 * @return array Array of archives found in the app/cache/backup
 */
function readImportedUsersArchives(string $path = '', string $parentFile = null, int $userId = 0): array
{
    $data = [];
    if (empty($path)) {
        $path = api_get_path(SYS_ARCHIVE_PATH).'backup/import_users/'.api_get_user_id();
    }
    foreach (scandir($path) as $dir) {
        // exclude ".", ".." and ".htaccess"
        if (in_array($dir, ['.', '..', '.htaccess'])) {
            continue;
        }
        $currentPath = $path.DIRECTORY_SEPARATOR.$dir;
        if (is_dir($currentPath)) {
            $data[$dir] = readImportedUsersArchives($currentPath, $dir, $userId);
        } elseif (is_file($currentPath)) {
            if (strpos($dir, '.csv') !== false) {
                $data[$dir] = $currentPath;
                if (empty($_GET['download'])) {
                    continue;
                }
                $filename = substr($_GET['download'], -strlen($dir));
                $time = (string) (int) substr($_GET['download'], 0, -strlen($dir));
                // Clean against hacks
                if ($filename == $dir) {
                    if (!Security::check_abs_path($path.DIRECTORY_SEPARATOR.$filename, $path)) {
                        continue;
                    }
                    DocumentManager::file_send_for_download($currentPath, true, $time.'_'.$filename);
                }
            }
        }
    }

    krsort($data);

    return $data;
}

/**
 * Print an HTML table of archives of imported users.
 *
 * @return string HTML table or empty string if no results
 */
function getImportedUsersArchivesTable(): string
{
    $data = readImportedUsersArchives();
    if (empty($data)) {
        return '';
    }
    $table = new HTML_Table(['class' => 'table table-responsive']);
    $headers = [
        get_lang('SelectUser'),
        get_lang('FieldTypeDatetime'),
        get_lang('Files'),
    ];
    $row = 0;
    $column = 0;
    foreach ($headers as $header) {
        $table->setHeaderContents($row, $column, $header);
        $column++;
    }

    $row++;
    $userId = api_get_user_id();

    $userInfo = api_get_user_info($userId);
    foreach ($data as $date => $elements) {
        $dateTime = DateTime::createFromFormat('YmdHis', $date)->format('Y-m-d H:i:s');
        $files = '';
        foreach ($elements as $fileName => $file) {
            $files .= "<a href='".api_get_self().'?download='.$date.'_'.$fileName."'>".
                Display::return_icon('down.png', get_lang('Down'), '', ICON_SIZE_SMALL).
                " $fileName </a> <br>";
        }
        if (!empty($files)) {
            $table->setCellContents($row, 0, $userInfo['complete_name']);
            $table->setCellContents($row, 1, $dateTime);
            $table->setCellContents($row, 2, $files);
            $row++;
        }
    }

    return $table->toHtml();
}

$this_section = SECTION_PLATFORM_ADMIN;
$defined_auth_sources[] = PLATFORM_AUTH_SOURCE;
if (isset($extAuthSource) && is_array($extAuthSource)) {
    $defined_auth_sources = array_merge($defined_auth_sources, array_keys($extAuthSource));
}

$tool_name = get_lang('History');
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ['url' => 'user_import.php', 'name' => get_lang('ImportUserListXMLCSV')];
$reloadImport = (isset($_REQUEST['reload_import']) && (int) $_REQUEST['reload_import'] === 1);

$extra_fields = UserManager::get_extra_fields(0, 0, 5, 'ASC', true);
$printTable = getImportedUsersArchivesTable();
Display::display_header($tool_name);

$form = new FormValidator('user_import', 'post', api_get_self());
$form->addHeader($tool_name);
$defaults['formSent'] = 1;
$form->addHtml($printTable);
$form->display();
Display::display_footer();
