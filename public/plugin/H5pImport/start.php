<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\H5pImport\Entity\H5pImport;
use Chamilo\PluginBundle\H5pImport\Entity\H5pImportResults;
use Chamilo\PluginBundle\H5pImport\H5pImporter\H5pPackageImporter;
use Chamilo\PluginBundle\H5pImport\H5pImporter\H5pPackageTools;

$course_plugin = 'h5pimport';
require_once __DIR__.'/config.php';

if (!function_exists('h5pimport_parse_ini_size')) {
    function h5pimport_parse_ini_size($value): int
    {
        $value = trim((string) $value);

        if ('' === $value) {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $bytes = (int) $value;

        switch ($unit) {
            case 'g':
                $bytes *= 1024;
            case 'm':
                $bytes *= 1024;
            case 'k':
                $bytes *= 1024;
                break;
        }

        return $bytes;
    }
}

if (!function_exists('h5pimport_get_max_upload_size_bytes')) {
    function h5pimport_get_max_upload_size_bytes(): int
    {
        $uploadMax = h5pimport_parse_ini_size(ini_get('upload_max_filesize'));
        $postMax = h5pimport_parse_ini_size(ini_get('post_max_size'));

        if ($uploadMax > 0 && $postMax > 0) {
            return min($uploadMax, $postMax);
        }

        return max($uploadMax, $postMax);
    }
}

if (!function_exists('h5pimport_build_url')) {
    function h5pimport_build_url(string $baseUrl, array $params = []): string
    {
        if (empty($params)) {
            return $baseUrl;
        }

        $separator = '' !== (string) parse_url($baseUrl, PHP_URL_QUERY) ? '&' : '?';

        return $baseUrl.$separator.http_build_query($params);
    }
}

if (!function_exists('h5pimport_get_open_url')) {
    function h5pimport_get_open_url(H5pImportPlugin $plugin, H5pImport $h5pImport): string
    {
        return h5pimport_build_url($plugin->getViewUrl($h5pImport), ['view' => 1]);
    }
}

if (!function_exists('h5pimport_format_description')) {
    function h5pimport_format_description(?string $description): string
    {
        $description = trim((string) $description);

        if ('' === $description) {
            return '—';
        }

        $plain = trim(strip_tags(html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        if ('' === $plain) {
            return '—';
        }

        if (function_exists('mb_strlen') && mb_strlen($plain) > 140) {
            $plain = mb_substr($plain, 0, 137).'...';
        } elseif (strlen($plain) > 140) {
            $plain = substr($plain, 0, 137).'...';
        }

        return Security::remove_XSS($plain);
    }
}

if (!function_exists('h5pimport_render_actions')) {
    function h5pimport_render_actions(
        H5pImportPlugin $plugin,
        H5pImport $h5pImport,
        string $pluginIndex,
        bool $isAllowedToEdit
    ): string {
        $openUrl = h5pimport_get_open_url($plugin, $h5pImport);

        $openButton = '<a href="'.htmlspecialchars($openUrl, ENT_QUOTES, 'UTF-8').'" class="btn btn-primary btn-sm">'.
            get_lang('Open').
            '</a>';

        if (!$isAllowedToEdit) {
            return $openButton;
        }

        $confirmMessage = addslashes(get_lang('Delete').' ?');

        $deleteForm = '
        <form
            method="post"
            action="'.htmlspecialchars($pluginIndex, ENT_QUOTES, 'UTF-8').'"
            style="display:inline-block; margin-left:8px;"
            onsubmit="return confirm(\''.$confirmMessage.'\');"
        >
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="'.(int) $h5pImport->getIid().'">
            <button type="submit" class="btn btn-danger btn-sm">
                '.get_lang('Delete').'
            </button>
        </form>
    ';

        return '<div style="white-space:nowrap;">'.$openButton.$deleteForm.'</div>';
    }
}

api_block_anonymous_users();
api_protect_course_script(true);

$plugin = H5pImportPlugin::create();

if (!h5pimport_is_plugin_active()) {
    Display::addFlash(
        Display::return_message(
            $plugin->get_lang('PluginDisabledFromAdminPanel'),
            'warning'
        )
    );

    header('Location: '.h5pimport_get_course_home_url());
    exit;
}

$cidReq = api_get_cidreq();
$pluginBaseUrl = api_get_path(WEB_PLUGIN_PATH).$plugin->get_name().'/start.php';
$pluginIndex = $pluginBaseUrl.('' !== $cidReq ? '?'.$cidReq : '');
$isAllowedToEdit = api_is_allowed_to_edit(true);
$action = $_REQUEST['action'] ?? null;

$em = Database::getManager();
$h5pRepo = $em->getRepository(H5pImport::class);
$h5pResultsRepo = $em->getRepository(H5pImportResults::class);

$course = api_get_course_entity(api_get_course_int_id());
$session = api_get_session_entity(api_get_session_id());
$user = api_get_user_entity(api_get_user_id());

$matchesContext = static function (H5pImport $h5pImport) use ($course, $session): bool {
    if ($course->getId() !== $h5pImport->getCourse()->getId()) {
        return false;
    }

    $itemSession = $h5pImport->getSession();

    if (null === $session) {
        return null === $itemSession;
    }

    if (null === $itemSession) {
        return false;
    }

    return $session->getId() === $itemSession->getId();
};

$view = new Template($plugin->getToolTitle());
$view->assign('is_allowed_to_edit', $isAllowedToEdit);

$header = $plugin->getToolTitle();
$actionsHtml = '';
$htmlContent = '';

switch ($action) {
    case 'add':
        if (!$isAllowedToEdit) {
            api_not_allowed(true);
        }

        $header = $plugin->get_lang('import_h5p_package');

        $actions = [
            Display::url(
                Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
                $pluginIndex
            ),
        ];

        $actionsHtml = Display::toolbarAction($plugin->get_name(), $actions);
        $addActionUrl = h5pimport_build_url($pluginIndex, ['action' => 'add']);
        $maxFileSize = h5pimport_get_max_upload_size_bytes();

        $form = new FormValidator('frm_edit', 'post', $addActionUrl);
        $form->addHtml(
            '<div style="
                background:#fff;
                border:1px solid #dcdcdc;
                border-radius:10px;
                padding:20px;
                margin-bottom:20px;
            ">
                <h4 style="margin-top:0; margin-bottom:10px;">'.$plugin->get_lang('import_h5p_package').'</h4>
                <p style="margin-bottom:16px; color:#555;">
                    Upload a valid <strong>.h5p</strong> package. Maximum allowed size: <strong>'.$maxFileSize.' bytes</strong>.
                </p>'
        );
        $form->addFile('file', $plugin->get_lang('h5p_package'), ['accept' => '.h5p']);
        $form->addRule('file', get_lang('ThisFieldIsRequired'), 'uploadedfile');
        $form->addTextarea('description', get_lang('Description'), ['rows' => 4]);
        $form->applyFilter('description', 'trim');
        $form->addHtml('</div>');
        $form->addButtonUpdate(get_lang('Add'));
        $form->addHidden('action', 'add');
        $form->protect();

        if ($form->validate()) {
            $values = $form->exportValues();
            $zipFileInfo = $_FILES['file'] ?? null;

            if (empty($zipFileInfo['tmp_name'])) {
                Display::addFlash(Display::return_message(get_lang('Error'), 'error'));
                header('Location: '.$pluginIndex);
                exit;
            }

            if (!empty($zipFileInfo['error']) && UPLOAD_ERR_OK !== (int) $zipFileInfo['error']) {
                Display::addFlash(Display::return_message(get_lang('Error'), 'error'));
                header('Location: '.$addActionUrl);
                exit;
            }

            if (
                $maxFileSize > 0 &&
                !empty($zipFileInfo['size']) &&
                (int) $zipFileInfo['size'] > $maxFileSize
            ) {
                Display::addFlash(
                    Display::return_message(
                        'The file size cannot exceed: '.$maxFileSize.' bytes',
                        'error'
                    )
                );
                header('Location: '.$addActionUrl);
                exit;
            }

            $originalName = $zipFileInfo['name'] ?? '';
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if ('h5p' !== $extension) {
                Display::addFlash(
                    Display::return_message(
                        $plugin->get_lang('h5p_package').' must be a .h5p file',
                        'error'
                    )
                );
                header('Location: '.$addActionUrl);
                exit;
            }

            try {
                $importer = H5pPackageImporter::create($zipFileInfo, $course);
                $packageFile = $importer->import();

                $h5pJson = H5pPackageTools::getJson($packageFile.'/h5p.json');
                $contentJson = H5pPackageTools::getJson($packageFile.'/content/content.json');

                if (false === $contentJson) {
                    $contentJson = H5pPackageTools::getJson($packageFile.'/content.json');
                }

                if (!$h5pJson) {
                    throw new \RuntimeException('Missing or invalid h5p.json after extraction.');
                }

                if (!$contentJson) {
                    throw new \RuntimeException('Missing or invalid content.json after extraction.');
                }

                if (!H5pPackageTools::checkPackageIntegrity($h5pJson, $packageFile)) {
                    throw new \RuntimeException('H5P package integrity check failed.');
                }

                H5pPackageTools::storeH5pPackage($packageFile, $h5pJson, $course, $session, $values);

                Display::addFlash(Display::return_message(get_lang('Added'), 'success'));
                header('Location: '.$pluginIndex);
                exit;
            } catch (Throwable $e) {
                error_log('[H5pImport][upload] '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
                error_log('[H5pImport][upload][trace] '.$e->getTraceAsString());

                Display::addFlash(
                    Display::return_message(
                        'Upload failed: '.$e->getMessage(),
                        'error'
                    )
                );

                header('Location: '.$addActionUrl);
                exit;
            }
        }

        $htmlContent = $form->returnForm();
        break;

    case 'delete':
        if (!$isAllowedToEdit) {
            api_not_allowed(true);
        }

        if ('POST' !== $_SERVER['REQUEST_METHOD']) {
            header('Location: '.$pluginIndex);
            exit;
        }

        $h5pImportId = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if (!$h5pImportId) {
            Display::addFlash(Display::return_message(get_lang('Error'), 'danger'));
            header('Location: '.$pluginIndex);
            exit;
        }

        $h5pImport = $h5pRepo->find($h5pImportId);

        if (!$h5pImport || !$matchesContext($h5pImport)) {
            Display::addFlash(Display::return_message($plugin->get_lang('ContentNotFound'), 'danger'));
            header('Location: '.$pluginIndex);
            exit;
        }

        if (H5pPackageTools::deleteH5pPackage($h5pImport)) {
            Display::addFlash(Display::return_message(get_lang('Deleted'), 'success'));
        } else {
            Display::addFlash(Display::return_message(get_lang('Error'), 'danger'));
        }

        header('Location: '.$pluginIndex);
        exit;

    default:
        /** @var H5pImport[] $h5pImports */
        $h5pImports = $h5pRepo->findBy([
            'course' => $course,
            'session' => $session,
        ]);

        $tableData = [];
        $deleteToken = Security::get_token('post');

        foreach ($h5pImports as $h5pImport) {
            $attemptCriteria = [
                'course' => $course,
                'session' => $session,
                'h5pImport' => $h5pImport,
            ];

            if (!$isAllowedToEdit) {
                $attemptCriteria['user'] = $user;
            }

            $attemptCount = (int) $h5pResultsRepo->count($attemptCriteria);
            $openUrl = h5pimport_get_open_url($plugin, $h5pImport);

            $row = [
                Display::url(Security::remove_XSS($h5pImport->getName()), $openUrl),
                h5pimport_format_description($h5pImport->getDescription()),
                (string) $attemptCount,
                $h5pImport,
            ];

            $tableData[] = $row;
        }

        if ($isAllowedToEdit) {
            $btnAdd = Display::toolbarButton(
                get_lang('Upload'),
                h5pimport_build_url($pluginIndex, ['action' => 'add']),
                'file-code-o',
                'primary'
            );

            $actionsHtml = Display::toolbarAction($plugin->get_name(), [$btnAdd]);
        }

        $table = new SortableTableFromArray($tableData, 0);
        $table->set_header(0, get_lang('Title'));
        $table->set_header(1, get_lang('Description'));
        $table->set_header(2, 'Launches');
        $table->set_header(3, get_lang('Actions'), false, 'th-header text-right', ['class' => 'text-right']);

        $table->set_column_filter(
            3,
            static function (H5pImport $value) use ($plugin, $pluginIndex, $isAllowedToEdit, $deleteToken) {
                return h5pimport_render_actions($plugin, $value, $pluginIndex, $isAllowedToEdit, $deleteToken);
            }
        );

        $htmlContent = $table->return_table();
        break;
}

$view->assign('header', $header);
$view->assign('actions', $actionsHtml);
$view->assign('content', $htmlContent);
$view->display_one_col_template();
