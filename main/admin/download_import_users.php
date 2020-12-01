<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraFieldOptions;
use ChamiloSession as Session;
use Ddeboer\DataImport\Writer\CsvWriter;
use Symfony\Component\DomCrawler\Crawler;

/**
 * This tool allows platform admins to check history by csv file.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

// Set this option to true to enforce strict purification for usenames.
$purification_option_for_usernames = false;
$userId = api_get_user_id();

api_protect_admin_script(true, null);
api_protect_limit_for_session_admin();
set_time_limit(0);


function ReadAllElement($path = null, $parentFile = null, $userId = 0)
{
    $data = [];
    $basePath = api_get_configuration_value('root_sys').'app/cache/backup/import_users/'.api_get_user_id();
    if ($path == null) $path = $basePath;
    foreach (scandir($path) as $dir) {
        // exclude . .. and .htaccess
        if ($dir == '.') continue;
        if ($dir == '..') continue;
        if ($dir == '.htaccess') continue;
        $currentPath = $path.DIRECTORY_SEPARATOR.$dir;
        if (is_dir($currentPath)) {
            $data[$dir] = ReadAllElement($currentPath, $dir, $userId);
        } elseif (is_file($currentPath)) {
            $downloadItem = isset($_GET['download']) ? (int)$_GET['download'] : null;

            if (
                strpos($dir, '.csv') !== false
            ) {
                $data[$dir] = $currentPath;
                if (
                    $downloadItem !== null &&
                    $downloadItem == "$parentFile$dir"

                ) {
                    echo "";
                    DocumentManager::file_send_for_download($currentPath, true, $dir);
                }
            }
        }
    }

    return $data;
}

function printTable()
{
    $data = ReadAllElement(api_get_configuration_value('root_sys').'app/cache/backup/import_users/'.api_get_user_id());
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

    //foreach ($data as $userId => $items) {
    $userInfo = api_get_user_info($userId);
    foreach ($data as $date => $elements) {
        $dateTime = DateTime::createFromFormat('Ymdhis', $date)->format('Y-m-d H:i:s');
        $files = '';
        foreach ($elements as $fileName => $file) {
            $files .= "<a href='".api_get_self()."?download=$date$fileName'>".
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
    //}

    return $table->toHtml();
}

$this_section = SECTION_PLATFORM_ADMIN;
$defined_auth_sources[] = PLATFORM_AUTH_SOURCE;
if (isset($extAuthSource) && is_array($extAuthSource)) {
    $defined_auth_sources = array_merge($defined_auth_sources, array_keys($extAuthSource));
}

$tool_name = "<strong>".get_lang('History')."</strong> ".get_lang('ImportUserListXMLCSV');
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$reloadImport = (isset($_REQUEST['reload_import']) && (int)$_REQUEST['reload_import'] === 1);

$extra_fields = UserManager::get_extra_fields(0, 0, 5, 'ASC', true);
$printTable = printTable();
Display::display_header($tool_name);

$form = new FormValidator('user_import', 'post', api_get_self());
$form->addHeader($tool_name);
/*
$form->addElement('hidden', 'formSent');
*/
$defaults['formSent'] = 1;
$form->addHtml($printTable);
$form->display();
Display::display_footer();
