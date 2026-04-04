<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\H5pImport\Entity\H5pImport;
use Chamilo\PluginBundle\H5pImport\Entity\H5pImportResults;
use Chamilo\PluginBundle\H5pImport\H5pImporter\H5pPackageImporter;
use Chamilo\PluginBundle\H5pImport\H5pImporter\H5pPackageTools;

$course_plugin = 'h5pimport';
require_once __DIR__.'/config.php';

if (!function_exists('h5pimport_get_delete_token')) {
    function h5pimport_get_delete_token(): string
    {
        if (empty($_SESSION['h5pimport_delete_token'])) {
            $_SESSION['h5pimport_delete_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['h5pimport_delete_token'];
    }
}

if (!function_exists('h5pimport_validate_delete_token')) {
    function h5pimport_validate_delete_token(?string $submittedToken): bool
    {
        $submittedToken = (string) $submittedToken;
        $expectedToken = (string) ($_SESSION['h5pimport_delete_token'] ?? '');

        return '' !== $submittedToken
            && '' !== $expectedToken
            && hash_equals($expectedToken, $submittedToken);
    }
}

if (!function_exists('h5pimport_rotate_delete_token')) {
    function h5pimport_rotate_delete_token(): void
    {
        $_SESSION['h5pimport_delete_token'] = bin2hex(random_bytes(32));
    }
}

if (!function_exists('h5pimport_escape')) {
    function h5pimport_escape($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('h5pimport_get_add_token')) {
    function h5pimport_get_add_token(): string
    {
        if (empty($_SESSION['h5pimport_add_token'])) {
            $_SESSION['h5pimport_add_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['h5pimport_add_token'];
    }
}

if (!function_exists('h5pimport_validate_add_token')) {
    function h5pimport_validate_add_token(?string $submittedToken): bool
    {
        $submittedToken = (string) $submittedToken;
        $expectedToken = (string) ($_SESSION['h5pimport_add_token'] ?? '');

        return '' !== $submittedToken
            && '' !== $expectedToken
            && hash_equals($expectedToken, $submittedToken);
    }
}

if (!function_exists('h5pimport_rotate_add_token')) {
    function h5pimport_rotate_add_token(): void
    {
        $_SESSION['h5pimport_add_token'] = bin2hex(random_bytes(32));
    }
}

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

if (!function_exists('h5pimport_format_bytes')) {
    function h5pimport_format_bytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return number_format($value, $power > 0 ? 2 : 0).' '.$units[$power];
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

if (!function_exists('h5pimport_validate_manual_token')) {
    function h5pimport_validate_manual_token(string $tokenKey, ?string $submittedToken): bool
    {
        $expectedToken = (string) Security::get_token($tokenKey);
        $submittedToken = (string) $submittedToken;

        return '' !== $submittedToken && hash_equals($expectedToken, $submittedToken);
    }
}

if (!function_exists('h5pimport_render_actions')) {
    function h5pimport_render_actions(
        H5pImportPlugin $plugin,
        H5pImport $h5pImport,
        string $pluginIndex,
        bool $isAllowedToEdit,
        string $deleteToken
    ): string {
        $openUrl = h5pimport_get_open_url($plugin, $h5pImport);
        $confirmMessage = json_encode(
            get_lang('Delete').'?',
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        );

        $openButton = '<a
            href="'.h5pimport_escape($openUrl).'"
            class="inline-flex items-center justify-center rounded-xl border border-gray-25 bg-white px-4 py-2 text-body-2 font-semibold text-gray-90 transition hover:bg-gray-10"
        >'.h5pimport_escape(get_lang('Open')).'</a>';

        if (!$isAllowedToEdit) {
            return '<div class="flex justify-end">'.$openButton.'</div>';
        }

        $deleteForm = '
            <form
                method="post"
                action="'.h5pimport_escape($pluginIndex).'"
                class="inline-flex"
                onsubmit="return confirm('.$confirmMessage.');"
            >
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="'.(int) $h5pImport->getIid().'">
                <input type="hidden" name="sec_token" value="'.h5pimport_escape($deleteToken).'">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-xl border border-danger/20 bg-danger px-4 py-2 text-body-2 font-semibold text-white transition hover:opacity-90"
                >
                    '.h5pimport_escape(get_lang('Delete')).'
                </button>
            </form>
        ';

        return '
            <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                '.$openButton.'
                '.$deleteForm.'
            </div>
        ';
    }
}

if (!function_exists('h5pimport_render_add_form')) {
    function h5pimport_render_add_form(
        H5pImportPlugin $plugin,
        string $actionUrl,
        string $cancelUrl,
        string $token,
        int $maxFileSize,
        string $description = ''
    ): string {
        $title = h5pimport_escape($plugin->get_lang('import_h5p_package'));
        $fileLabel = h5pimport_escape($plugin->get_lang('h5p_package'));
        $descriptionLabel = h5pimport_escape(get_lang('Description'));
        $addLabel = h5pimport_escape(get_lang('Add'));
        $cancelLabel = h5pimport_escape(get_lang('Back'));
        $helpText = 'Upload a valid .h5p package. Maximum allowed size: '.h5pimport_format_bytes($maxFileSize).' ('.$maxFileSize.' bytes).';

        return '
            <div class="mx-auto max-w-5xl">
                <div class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
                    <div class="mb-6">
                        <h2 class="text-xl font-semibold text-gray-90">'.$title.'</h2>
                        <p class="mt-2 text-body-2 text-gray-50">'.h5pimport_escape($helpText).'</p>
                    </div>

                    <form
                        method="post"
                        action="'.h5pimport_escape($actionUrl).'"
                        enctype="multipart/form-data"
                        class="space-y-6"
                    >
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="sec_token" value="'.h5pimport_escape($token).'">

                        <div class="space-y-2">
                            <label class="block text-body-2 font-semibold text-gray-90">
                                <span class="text-danger">*</span> '.$fileLabel.'
                            </label>
                            <input
                                type="file"
                                name="file"
                                accept=".h5p"
                                required
                                class="block w-full rounded-xl border border-gray-25 bg-white text-body-2 text-gray-90 file:mr-4 file:rounded-xl file:border-0 file:bg-primary file:px-4 file:py-2 file:font-semibold file:text-primary-button-text focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                            >
                        </div>

                        <div class="space-y-2">
                            <label for="h5pimport_description" class="block text-body-2 font-semibold text-gray-90">
                                '.$descriptionLabel.'
                            </label>
                            <textarea
                                id="h5pimport_description"
                                name="description"
                                rows="5"
                                class="block w-full rounded-xl border border-gray-25 bg-white text-body-2 text-gray-90 placeholder-gray-50 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                placeholder="Optional description"
                            >'.h5pimport_escape($description).'</textarea>
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-20 pt-4">
                            <a
                                href="'.h5pimport_escape($cancelUrl).'"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-25 bg-white px-5 py-2.5 text-body-2 font-semibold text-gray-90 transition hover:bg-gray-10"
                            >
                                '.$cancelLabel.'
                            </a>
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-body-2 font-semibold text-white shadow-sm transition hover:opacity-90"
                            >
                                '.$addLabel.'
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        ';
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

$addTokenKey = 'h5pimport_add';
$deleteTokenKey = 'h5pimport_delete';

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
        $addActionUrl = h5pimport_build_url($pluginIndex, ['action' => 'add']);
        $maxFileSize = h5pimport_get_max_upload_size_bytes();

        $actionsHtml = '
            <div class="mb-6 flex items-center justify-between">
                <div></div>
                <a
                    href="'.h5pimport_escape($pluginIndex).'"
                    class="inline-flex items-center justify-center rounded-xl border border-gray-25 bg-white px-4 py-2 text-body-2 font-semibold text-gray-90 shadow-sm transition hover:bg-gray-10"
                >
                    '.h5pimport_escape(get_lang('Back')).'
                </a>
            </div>
        ';

        if ('POST' === $_SERVER['REQUEST_METHOD']) {
            if (!h5pimport_validate_add_token($_POST['sec_token'] ?? null)) {
                error_log('[H5pImport][upload] Invalid CSRF token.');
                Display::addFlash(Display::return_message(get_lang('Error'), 'error'));
                header('Location: '.$addActionUrl);
                exit;
            }

            $description = Security::remove_XSS(trim((string) ($_POST['description'] ?? '')));
            $zipFileInfo = $_FILES['file'] ?? null;

            if (empty($zipFileInfo) || !is_array($zipFileInfo)) {
                error_log('[H5pImport][upload] Missing file payload in $_FILES.');
                Display::addFlash(Display::return_message(get_lang('Error'), 'error'));
                header('Location: '.$addActionUrl);
                exit;
            }

            if (empty($zipFileInfo['tmp_name'])) {
                $uploadErrorCode = isset($zipFileInfo['error']) ? (int) $zipFileInfo['error'] : -1;
                error_log('[H5pImport][upload] Empty tmp_name. Upload error code: '.$uploadErrorCode);
                Display::addFlash(Display::return_message(get_lang('Error'), 'error'));
                header('Location: '.$addActionUrl);
                exit;
            }

            if (!empty($zipFileInfo['error']) && UPLOAD_ERR_OK !== (int) $zipFileInfo['error']) {
                error_log('[H5pImport][upload] PHP upload error code: '.(int) $zipFileInfo['error']);
                Display::addFlash(Display::return_message(get_lang('Error'), 'error'));
                header('Location: '.$addActionUrl);
                exit;
            }

            if (
                $maxFileSize > 0 &&
                !empty($zipFileInfo['size']) &&
                (int) $zipFileInfo['size'] > $maxFileSize
            ) {
                error_log('[H5pImport][upload] File exceeds max allowed size. Size: '.(int) $zipFileInfo['size'].' bytes.');
                Display::addFlash(
                    Display::return_message(
                        'The file size cannot exceed: '.$maxFileSize.' bytes',
                        'error'
                    )
                );
                header('Location: '.$addActionUrl);
                exit;
            }

            $originalName = (string) ($zipFileInfo['name'] ?? '');
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if ('h5p' !== $extension) {
                error_log('[H5pImport][upload] Invalid file extension: '.$extension.' for file '.$originalName);
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

                H5pPackageTools::storeH5pPackage(
                    $packageFile,
                    $h5pJson,
                    $course,
                    $session,
                    ['description' => $description]
                );

                h5pimport_rotate_add_token();

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

        $htmlContent = h5pimport_render_add_form(
            $plugin,
            $addActionUrl,
            $pluginIndex,
            h5pimport_get_add_token(),
            $maxFileSize
        );
        break;

    case 'delete':
        if (!$isAllowedToEdit) {
            api_not_allowed(true);
        }

        if ('POST' !== $_SERVER['REQUEST_METHOD']) {
            header('Location: '.$pluginIndex);
            exit;
        }

        if (!h5pimport_validate_delete_token($_POST['sec_token'] ?? null)) {
            error_log('[H5pImport][delete] Invalid CSRF token.');
            Display::addFlash(Display::return_message(get_lang('Error'), 'error'));
            header('Location: '.$pluginIndex);
            exit;
        }

        $h5pImportId = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if (!$h5pImportId) {
            Display::addFlash(Display::return_message(get_lang('Error'), 'error'));
            header('Location: '.$pluginIndex);
            exit;
        }

        $h5pImport = $h5pRepo->find($h5pImportId);

        if (!$h5pImport || !$matchesContext($h5pImport)) {
            Display::addFlash(Display::return_message($plugin->get_lang('ContentNotFound'), 'error'));
            header('Location: '.$pluginIndex);
            exit;
        }

        if (H5pPackageTools::deleteH5pPackage($h5pImport)) {
            Display::addFlash(Display::return_message(get_lang('Deleted'), 'success'));
        } else {
            Display::addFlash(Display::return_message(get_lang('Error'), 'error'));
        }

        h5pimport_rotate_delete_token();

        header('Location: '.$pluginIndex);
        exit;

    default:
        /** @var H5pImport[] $h5pImports */
        $h5pImports = $h5pRepo->findBy([
            'course' => $course,
            'session' => $session,
        ]);

        usort(
            $h5pImports,
            static function (H5pImport $a, H5pImport $b): int {
                return strcasecmp((string) $a->getName(), (string) $b->getName());
            }
        );

        if ($isAllowedToEdit) {
            $actionsHtml = '
                <div class="mb-6 flex items-center justify-end">
                    <a
                        href="'.h5pimport_escape(h5pimport_build_url($pluginIndex, ['action' => 'add'])).'"
                        class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-body-2 font-semibold text-white shadow-sm transition hover:opacity-90"
                    >
                        '.h5pimport_escape(get_lang('Upload')).'
                    </a>
                </div>
            ';
        }

        if (empty($h5pImports)) {
            $emptyMessage = $isAllowedToEdit
                ? 'No H5P packages yet. Upload your first package to get started.'
                : 'No H5P packages available in this context.';

            $htmlContent = '
                <div class="rounded-2xl border border-gray-25 bg-white p-10 text-center shadow-sm">
                    <h2 class="text-xl font-semibold text-gray-90">'.$plugin->getToolTitle().'</h2>
                    <p class="mt-3 text-body-2 text-gray-50">'.h5pimport_escape($emptyMessage).'</p>
                </div>
            ';
            break;
        }

        $deleteToken = h5pimport_get_delete_token();
        $rowsHtml = '';

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
            $title = Security::remove_XSS((string) $h5pImport->getName());
            $description = h5pimport_format_description($h5pImport->getDescription());

            $rowsHtml .= '
                <tr class="border-t border-gray-20 transition hover:bg-gray-10/50">
                    <td class="px-5 py-4 align-middle">
                        <a
                            href="'.h5pimport_escape($openUrl).'"
                            class="font-semibold text-primary hover:underline"
                        >
                            '.h5pimport_escape($title).'
                        </a>
                    </td>
                    <td class="px-5 py-4 align-middle text-body-2 text-gray-90">
                        '.h5pimport_escape($description).'
                    </td>
                    <td class="px-5 py-4 align-middle">
                        <span class="inline-flex min-w-[3rem] items-center justify-center rounded-full bg-support-1 px-3 py-1 text-body-2 font-semibold text-support-4">
                            '.$attemptCount.'
                        </span>
                    </td>
                    <td class="px-5 py-4 align-middle text-right">
                        '.h5pimport_render_actions($plugin, $h5pImport, $pluginIndex, $isAllowedToEdit, $deleteToken).'
                    </td>
                </tr>
            ';
        }

        $htmlContent = '
            <div class="overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-sm">
                <div class="border-b border-gray-20 bg-gray-15 px-5 py-4">
                    <h2 class="text-lg font-semibold text-gray-90">'.$plugin->getToolTitle().'</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-20">
                        <thead class="bg-gray-15">
                            <tr>
                                <th class="px-5 py-4 text-left text-body-2 font-semibold text-gray-90">'.h5pimport_escape(get_lang('Title')).'</th>
                                <th class="px-5 py-4 text-left text-body-2 font-semibold text-gray-90">'.h5pimport_escape(get_lang('Description')).'</th>
                                <th class="px-5 py-4 text-left text-body-2 font-semibold text-gray-90">Launches</th>
                                <th class="px-5 py-4 text-right text-body-2 font-semibold text-gray-90">'.h5pimport_escape(get_lang('Actions')).'</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            '.$rowsHtml.'
                        </tbody>
                    </table>
                </div>
            </div>
        ';
        break;
}

$view->assign('header', $header);
$view->assign('actions', $actionsHtml);
$view->assign('content', $htmlContent);
$view->display_one_col_template();
