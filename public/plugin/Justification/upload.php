<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users();

$plugin = Justification::create();
$tool = 'justification';
$userId = api_get_user_id();

if ($userId <= 0) {
    api_not_allowed(true);
}

$documents = $plugin->getList();

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    if (!Security::check_token('post')) {
        api_not_allowed(true);
    }

    Security::clear_token();

    $documentId = isset($_POST['justification_document_id']) ? (int) $_POST['justification_document_id'] : 0;
    $manualDate = isset($_POST['date_validity']) ? trim((string) $_POST['date_validity']) : null;
    $file = $_FILES['justification_file'] ?? [];

    if ($plugin->saveUploadedJustification($userId, $documentId, $file, $manualDate)) {
        Display::addFlash(Display::return_message($plugin->get_lang('JustificationSaved')));
    } else {
        Display::addFlash(Display::return_message($plugin->get_lang('UploadFailed'), 'warning'));
    }

    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'Justification/upload.php');
    exit;
}

$existingRows = $plugin->getUserJustificationList($userId);
$existingByDocument = [];
foreach ($existingRows as $row) {
    $existingByDocument[(int) $row['justification_document_id']] = $row;
}

$token = Security::get_token();
$content = '<section class="w-full space-y-6">';
$content .= '
    <div class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-gray-90">'.$plugin->get_lang('MyJustifications').'</h2>
                <p class="text-sm text-gray-50">'.$plugin->get_lang('MyJustificationsHelp').'</p>
            </div>
        </div>
    </div>';

if (empty($documents)) {
    $content .= '
    <div class="rounded-2xl border border-gray-25 bg-gray-15/60 p-10 text-center">
        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-white text-primary shadow-sm">
            <span class="mdi mdi-file-document-alert-outline text-2xl"></span>
        </div>
        <p class="text-base font-semibold text-gray-90">'.$plugin->get_lang('NoRequiredJustificationDocuments').'</p>
        <p class="text-sm text-gray-50">'.$plugin->get_lang('NoRequiredJustificationDocumentsHelp').'</p>
    </div>';
} else {
    $content .= '<div class="grid gap-4">';
    foreach ($documents as $document) {
        $documentId = (int) $document['id'];
        $existing = $existingByDocument[$documentId] ?? null;
        $documentName = Security::remove_XSS((string) $document['name']);
        $documentCode = Security::remove_XSS((string) $document['code']);
        $documentComment = Security::remove_XSS((string) $document['comment']);
        $hasManualDate = !empty($document['date_manual_on']);
        $validityDate = $existing['date_validity'] ?? '';
        $fileLabel = '';

        if ($existing) {
            $fileLabel = basename((string) $existing['file_path']);
        }

        $content .= '
        <div class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <span class="mdi mdi-file-document-outline text-xl"></span>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-90">'.$documentName.'</h3>
                            <p class="text-xs uppercase tracking-wide text-gray-50">'.$documentCode.'</p>
                        </div>
                    </div>
                    '.($documentComment ? '<p class="text-sm text-gray-60">'.$documentComment.'</p>' : '').'
                    '.($existing ? '
                        <div class="inline-flex flex-wrap items-center gap-2 rounded-full bg-success/10 px-3 py-1 text-sm text-success">
                            <span class="mdi mdi-check-circle-outline"></span>
                            <span>'.$plugin->get_lang('Uploaded').'</span>
                            <span class="text-gray-50">'.Security::remove_XSS($fileLabel).'</span>
                            '.($validityDate ? '<span class="text-gray-50">'.$plugin->get_lang('ValidUntil').' '.Security::remove_XSS($validityDate).'</span>' : '').'
                        </div>
                    ' : '
                        <div class="inline-flex items-center gap-2 rounded-full bg-warning/10 px-3 py-1 text-sm text-warning">
                            <span class="mdi mdi-alert-circle-outline"></span>
                            <span>'.$plugin->get_lang('PendingUpload').'</span>
                        </div>
                    ').'
                </div>

                <form class="w-full space-y-3 lg:max-w-md" method="post" enctype="multipart/form-data" action="'.api_get_self().'">
                    <input type="hidden" name="sec_token" value="'.$token.'">
                    <input type="hidden" name="justification_document_id" value="'.$documentId.'">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-70" for="justification_file_'.$documentId.'">'.$plugin->get_lang('SelectFile').'</label>
                        <input
                            id="justification_file_'.$documentId.'"
                            class="w-full rounded-xl border border-gray-25 bg-white px-3 py-2 text-sm text-gray-90"
                            type="file"
                            name="justification_file"
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                            required
                        >
                    </div>
                    '.($hasManualDate ? '
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-70" for="date_validity_'.$documentId.'">'.$plugin->get_lang('ValidityDate').'</label>
                        <input
                            id="date_validity_'.$documentId.'"
                            class="w-full rounded-xl border border-gray-25 bg-white px-3 py-2 text-sm text-gray-90"
                            type="date"
                            name="date_validity"
                            value="'.Security::remove_XSS($validityDate).'"
                        >
                    </div>
                    ' : '').'
                    <button class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90" type="submit">
                        <span class="mdi mdi-upload"></span>
                        <span>'.$plugin->get_lang('UploadJustification').'</span>
                    </button>
                </form>
            </div>
        </div>';
    }
    $content .= '</div>';
}

$content .= '</section>';

$tpl = new Template($tool);
$actionLinks = '';

if (api_is_platform_admin() || ($plugin->canSessionAdminsManageUsers() && api_is_session_admin())) {
    $actionLinks .= Display::toolbarButton(
        $plugin->get_lang('AdminList'),
        api_get_path(WEB_PLUGIN_PATH).'Justification/list.php',
        'arrow-left',
        'primary'
    );
}

$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$actionLinks])
);

$tpl->assign('content', $content);
$tpl->display_one_col_template();
