<?php

declare(strict_types=1);
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CLp;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;
api_protect_course_script();

$allow = api_is_allowed_to_edit(null, true);
$lpId = !empty($_GET['lp_id']) ? (int) $_GET['lp_id'] : 0;

if (!$allow || $lpId <= 0) {
    api_not_allowed(true);
}

$courseInfo = api_get_course_info();
$userId = api_get_user_id();

// Prevent open redirects: allow only absolute URLs under WEB_PATH or relative paths.
$sanitizeReturnTo = static function (?string $url): string {
    $url = trim((string) $url);
    if ('' === $url) {
        return '';
    }

    // Allow relative internal paths.
    if (str_starts_with($url, '/')) {
        return $url;
    }

    // Allow absolute URLs that start with the platform WEB_PATH.
    $webPath = api_get_path(WEB_PATH);
    if ($webPath && str_starts_with($url, $webPath)) {
        return $url;
    }

    return '';
};

$returnToRaw = $_GET['returnTo'] ?? '';
$returnTo = $sanitizeReturnTo($returnToRaw);

$lpRepo = Container::getLpRepository();

/** @var CLp|null $lpEntity */
$lpEntity = $lpRepo->find($lpId);

if (!$lpEntity || CLp::SCORM_TYPE !== (int) $lpEntity->getLpType()) {
    Display::addFlash(Display::return_message(get_lang('No learning path found'), 'error'));
    api_not_allowed(true);
}

// Breadcrumbs.
$interbreadcrumb[] = [
    'url' => 'lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('Learning paths'),
];
$interbreadcrumb[] = [
    'url' => api_get_self().'?'.api_get_cidreq().'&lp_id='.$lpId,
    'name' => Security::remove_XSS((string) $lpEntity->getTitle()),
];

$form = new FormValidator(
    '',
    'POST',
    api_get_self().'?'.api_get_cidreq().'&lp_id='.$lpId.($returnTo ? '&returnTo='.urlencode($returnTo) : ''),
    '',
    [
        'id' => 'upload_form',
        'enctype' => 'multipart/form-data',
    ]
);

$form->addHeader(get_lang('Update file'));
$form->addHtml(
    Display::return_message(
        get_lang('You must upload a zip file with the same name as the original SCORM file.')
    )
);

$form->addLabel(null, Display::return_icon('scorm_logo.jpg', null, ['style' => 'width:230px;height:100px']));
$form->addElement('hidden', 'curdirpath', '');
$form->addElement('file', 'user_file', get_lang('SCORM or AICC file to upload'));
$form->addRule('user_file', get_lang('Required field'), 'required');
$form->addButtonUpload(get_lang('Upload'));

/**
 * Map PHP upload error codes to readable messages.
 */
$uploadErrorToMessageKey = static function (int $code): string {
    return match ($code) {
        \UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds upload_max_filesize.',
        \UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive.',
        \UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
        \UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        \UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
        \UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        \UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
        default => 'Unknown upload error.',
    };
};

$isPost = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';

// Detect the classic "post_max_size exceeded" case.
// When that happens, PHP gives you empty $_POST and empty $_FILES.
if ($isPost && empty($_POST) && empty($_FILES) && !empty($_SERVER['CONTENT_LENGTH'])) {
    $cl = (int) $_SERVER['CONTENT_LENGTH'];
    $pm = (string) ini_get('post_max_size');

    error_log("lp_update_scorm.php - POST body too large. content_length={$cl}, post_max_size={$pm}, lp_id={$lpId}, user_id={$userId}");

    Display::addFlash(
        Display::return_message(
            get_lang('Upload failed. The file is likely larger than the server limit (post_max_size).'),
            'error'
        )
    );
} elseif ($isPost) {
    // If submitted, try processing even if FormValidator validate() fails,
    // because file uploads can fail validation silently depending on server limits.
    $file = $_FILES['user_file'] ?? null;

    if (!is_array($file)) {
        error_log("lp_update_scorm.php - No user_file found in \$_FILES. lp_id={$lpId}, user_id={$userId}");
        Display::addFlash(Display::return_message(get_lang('Update failed. No file received by the server.'), 'error'));
    } else {
        $fileErr = (int) ($file['error'] ?? \UPLOAD_ERR_NO_FILE);
        if (\UPLOAD_ERR_OK !== $fileErr) {
            $msgKey = $uploadErrorToMessageKey($fileErr);
            $msgUi = get_lang($msgKey);

            error_log("lp_update_scorm.php - Upload error: {$msgKey} (code={$fileErr}) lp_id={$lpId}, user_id={$userId}");

            Display::addFlash(
                Display::return_message(
                    sprintf(get_lang('Update failed. %s'), $msgUi),
                    'error'
                )
            );
        } else {
            $uploadedName = (string) ($file['name'] ?? '');
            $tmpPath = (string) ($file['tmp_name'] ?? '');
            $size = (int) ($file['size'] ?? 0);

            if ('' === $tmpPath || !is_uploaded_file($tmpPath) || $size <= 0) {
                error_log("lp_update_scorm.php - Invalid upload payload. tmp='{$tmpPath}', size={$size} lp_id={$lpId}, user_id={$userId}");
                Display::addFlash(Display::return_message(get_lang('Update failed. Invalid uploaded file payload.'), 'error'));
            } else {
                // Enforce same base name rule to ensure replacement targets the same folder.
                $expectedPath = trim((string) $lpEntity->getPath());
                $expectedFirstDir = '' !== $expectedPath ? trim((string) strtok($expectedPath, '/')) : '';

                $pi = pathinfo($uploadedName);
                $uploadedBase = trim((string) ($pi['filename'] ?? ''));

                if ('' !== $expectedFirstDir && '' !== $uploadedBase) {
                    $expectedSafe = api_replace_dangerous_char($expectedFirstDir);
                    $uploadedSafe = api_replace_dangerous_char($uploadedBase);

                    if ('' !== $expectedSafe && '' !== $uploadedSafe && $expectedSafe !== $uploadedSafe) {
                        error_log(
                            "lp_update_scorm.php - Update rejected: zip base name mismatch. expected='{$expectedSafe}', got='{$uploadedSafe}' ".
                            "(lp_id={$lpId}, user_id={$userId})"
                        );

                        Display::addFlash(
                            Display::return_message(
                                get_lang('Update failed. The uploaded ZIP file name must match the original SCORM package name.'),
                                'error'
                            )
                        );

                        // Stop here (do not call import_package).
                        $content = $form->returnForm();
                        $tpl = new Template(null);
                        $tpl->assign('content', $content);
                        $tpl->display_one_col_template();

                        exit;
                    }
                }

                // Log current asset id (optional).
                $oldAssetId = null;
                if (method_exists($lpEntity, 'getAsset')) {
                    $oldAsset = $lpEntity->getAsset();
                    if ($oldAsset instanceof Asset && method_exists($oldAsset, 'getId')) {
                        $oldAssetId = (string) $oldAsset->getId(); // UUID-safe
                    }
                }

                error_log(
                    "lp_update_scorm.php - Starting SCORM replace. lp_id={$lpId}, user_id={$userId}, file='{$uploadedName}', size={$size}, old_asset_id=".
                    (null !== $oldAssetId ? $oldAssetId : 'null')
                );

                // Replace mode.
                $oScorm = new scorm($lpEntity, $courseInfo, $userId);

                try {
                    $ok = $oScorm->import_package(
                        $file,
                        '',
                        $courseInfo,
                        true,       // updateDirContents
                        $lpEntity   // lpToCheck (replace target)
                    );
                } catch (Throwable $e) {
                    error_log('lp_update_scorm.php - import_package exception: '.$e->getMessage());
                    $ok = false;
                }

                if ($ok) {
                    // Link asset if applicable in this branch.
                    try {
                        if (isset($oScorm->asset) && $oScorm->asset instanceof Asset && method_exists($lpEntity, 'setAsset')) {
                            $lpEntity->setAsset($oScorm->asset);

                            $em = Database::getManager();
                            $em->persist($lpEntity);
                            $em->flush();

                            $newAssetId = method_exists($oScorm->asset, 'getId') ? (string) $oScorm->asset->getId() : null;
                            error_log(
                                'lp_update_scorm.php - Replace succeeded. LP asset linked. new_asset_id='.
                                (is_string($newAssetId) ? $newAssetId : 'null')
                            );
                        } else {
                            error_log('lp_update_scorm.php - Replace succeeded. Asset linking not applicable.');
                        }
                    } catch (Throwable $e) {
                        error_log('lp_update_scorm.php - Asset linking skipped: '.$e->getMessage());
                    }

                    Display::addFlash(Display::return_message(get_lang('Update successful')));

                    if ('' !== $returnTo) {
                        header('Location: '.$returnTo);

                        exit;
                    }

                    header('Location: lp_controller.php?action=list&'.api_get_cidreq());

                    exit;
                }

                error_log("lp_update_scorm.php - Replace failed. lp_id={$lpId}, user_id={$userId}");
                Display::addFlash(Display::return_message(get_lang('Update failed'), 'error'));
            }
        }
    }
}

// Default: show the form.
$content = $form->returnForm();
$tpl = new Template(null);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
