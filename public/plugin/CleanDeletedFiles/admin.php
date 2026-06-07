<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/config.php';

api_protect_admin_script();

/** @var CleanDeletedFilesPlugin $plugin */
$plugin = CleanDeletedFilesPlugin::create();

if (!$plugin->isEnabled() || !api_is_platform_admin()) {
    api_not_allowed(true);
}

$nameTools = $plugin->get_lang('FileList');
$pathFilter = isset($_REQUEST['path_filter']) ? $plugin->normalizePathFilter((string) $_REQUEST['path_filter']) : '';
$scanUrl = api_get_self().'?scan=1';
if ('' !== $pathFilter) {
    $scanUrl .= '&path_filter='.urlencode($pathFilter);
}
$scanRequested = isset($_GET['scan']) && '1' === (string) $_GET['scan'];
$operationMessage = '';
$operationMessageType = 'normal';

if ('POST' === strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? ''))) {
    $scanRequested = true;
    $postedAction = isset($_POST['clean_deleted_files_action']) ? (string) $_POST['clean_deleted_files_action'] : '';

    if (!Security::check_token('post')) {
        $operationMessage = $plugin->get_lang('ErrorInvalidToken');
        $operationMessageType = 'error';
    } elseif ('delete-selected' === $postedAction) {
        $deleteList = [];

        if (isset($_POST['delete_single_file']) && '' !== trim((string) $_POST['delete_single_file'])) {
            $deleteList[] = (string) $_POST['delete_single_file'];
        } elseif (isset($_POST['delete_files']) && is_array($_POST['delete_files'])) {
            foreach ($_POST['delete_files'] as $path) {
                if (is_string($path) && '' !== trim($path)) {
                    $deleteList[] = $path;
                }
            }
        }

        $deleteList = array_values(array_unique($deleteList));

        if ([] === $deleteList) {
            $operationMessage = $plugin->get_lang('NoSelection');
            $operationMessageType = 'warning';
        } else {
            $deleteResult = $plugin->deleteRelativePathList($deleteList);
            $operationMessage = 'Delete request received for '.count($deleteList).' file(s). '.$deleteResult['message'];
            $operationMessageType = $deleteResult['success'] ? 'confirmation' : 'warning';

            if (!empty($deleteResult['deleted_paths'])) {
                $operationMessage .= '<br><strong>Deleted:</strong><ul class="m-0 mt-2 list-disc pl-5">';
                foreach ($deleteResult['deleted_paths'] as $deletedPath) {
                    $operationMessage .= '<li><code>'.htmlspecialchars((string) $deletedPath, ENT_QUOTES).'</code></li>';
                }
                $operationMessage .= '</ul>';
            }

            if (!empty($deleteResult['errors'])) {
                $operationMessage .= '<br><strong>Skipped or failed:</strong><ul class="m-0 mt-2 list-disc pl-5">';
                foreach ($deleteResult['errors'] as $error) {
                    $operationMessage .= '<li>'.htmlspecialchars((string) $error, ENT_QUOTES).'</li>';
                }
                $operationMessage .= '</ul>';
            }
        }
    } else {
        $operationMessage = get_lang('InvalidAction');
        $operationMessageType = 'error';
    }
}

$token = Security::get_token();
$confirmDeleteFiles = json_encode($plugin->get_lang('ConfirmDeleteFiles'), JSON_HEX_APOS | JSON_HEX_QUOT);

$htmlHeadXtra[] = <<<JAVASCRIPT
<script>
$(function () {
    $(document).on('click', '.clean-deleted-files-select-all', function () {
        var group = $(this).data('group');
        $('.clean-deleted-files-checkbox[data-group="' + group + '"]:not(:disabled)').prop('checked', $(this).prop('checked'));
    });

    $(document).on('submit', '#clean-deleted-files-delete-form', function () {
        return confirm({$confirmDeleteFiles});
    });
});
</script>
JAVASCRIPT;

Display::display_header($nameTools);
echo Display::page_header($nameTools);

if ('' !== $operationMessage) {
    echo Display::return_message($operationMessage, $operationMessageType, false);
}

$candidates = $scanRequested ? $plugin->findCleanableFileCandidates($pathFilter) : [];
$databaseSummary = $plugin->getDatabaseReferenceSummary();

$totalSize = 0;
$resourceCount = 0;
$assetCount = 0;
foreach ($candidates as $candidate) {
    $totalSize += (int) $candidate['size_bytes'];

    if ('resource_orphan' === $candidate['candidate_type']) {
        ++$resourceCount;
    } elseif ('asset_orphan' === $candidate['candidate_type']) {
        ++$assetCount;
    }
}

echo '<div class="q-card mb-6 p-6">';
echo '<div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">';
echo '<div class="max-w-4xl">';
echo '<h2 class="m-0 text-xl font-semibold text-gray-90">'.htmlspecialchars($plugin->get_lang('ScanSummary'), ENT_QUOTES).'</h2>';
echo '<p class="mt-2 text-body-2 text-gray-50">'.htmlspecialchars($plugin->get_lang('StorageNoticeShort'), ENT_QUOTES).'</p>';
echo '<p class="mt-2 text-body-2 text-gray-50">'.htmlspecialchars($plugin->get_lang('SafeNoticeShort'), ENT_QUOTES).'</p>';
echo '<form class="mt-4 flex flex-col gap-2 md:flex-row md:items-end" method="get" action="'.htmlspecialchars(api_get_self(), ENT_QUOTES).'">';
echo '<input type="hidden" name="scan" value="1">';
echo '<div class="flex-1">';
echo '<label class="block text-body-2 font-semibold text-gray-90" for="path_filter">'.htmlspecialchars($plugin->get_lang('PathFilter'), ENT_QUOTES).'</label>';
echo '<input class="form-control w-full rounded-lg border border-gray-25 px-3 py-2" type="text" id="path_filter" name="path_filter" value="'.htmlspecialchars($pathFilter, ENT_QUOTES).'" placeholder="clean-deleted-files-test">';
echo '<p class="m-0 mt-1 text-body-2 text-gray-50">'.htmlspecialchars($plugin->get_lang('PathFilterHelp'), ENT_QUOTES).'</p>';
echo '</div>';
echo '<button class="btn btn--primary" type="submit">'.htmlspecialchars($plugin->get_lang('RunLimitedScan'), ENT_QUOTES).'</button>';
echo '</form>';
echo '</div>';
echo '<div class="grid grid-cols-1 gap-2 text-body-2 md:grid-cols-4">';
echo '<div class="rounded-lg bg-support-1 px-4 py-3"><strong>'.count($candidates).'</strong><br>'.htmlspecialchars($plugin->get_lang('CleanableFiles'), ENT_QUOTES).'</div>';
echo '<div class="rounded-lg bg-support-1 px-4 py-3"><strong>'.$plugin->formatBytes($totalSize).'</strong><br>'.htmlspecialchars($plugin->get_lang('SizeTotalAllDir'), ENT_QUOTES).'</div>';
echo '<div class="rounded-lg bg-support-1 px-4 py-3"><strong>'.$databaseSummary['resource_file'].'</strong><br>resource_file</div>';
echo '<div class="rounded-lg bg-support-1 px-4 py-3"><strong>'.$databaseSummary['asset'].'</strong><br>asset</div>';
echo '</div>';
echo '</div>';
echo '</div>';

if ($scanRequested && '' !== $pathFilter) {
    echo Display::return_message(sprintf($plugin->get_lang('ActivePathFilter'), htmlspecialchars($pathFilter, ENT_QUOTES)), 'normal');
}

if ($scanRequested && $plugin->wasLastScanLimited()) {
    echo Display::return_message($plugin->get_lang('ScanLimitedWarning'), 'warning');
}

echo '<div class="q-card mb-6 p-4">';
echo '<div class="grid grid-cols-1 gap-3 md:grid-cols-2">';
echo '<div class="rounded-lg border border-gray-25 p-4"><strong>'.$resourceCount.'</strong><br>'.htmlspecialchars($plugin->get_lang('OrphanResourceFiles'), ENT_QUOTES).'</div>';
echo '<div class="rounded-lg border border-gray-25 p-4"><strong>'.$assetCount.'</strong><br>'.htmlspecialchars($plugin->get_lang('OrphanAssetFiles'), ENT_QUOTES).'</div>';
echo '</div>';
echo '</div>';

$roots = $plugin->getScanRoots();

if ([] === $candidates) {
    echo '<div class="q-card mb-6 p-6">';
    echo '<div class="flex flex-col gap-3 md:flex-row md:items-start">';
    echo '<span class="mdi mdi-check-circle ch-tool-icon text-3xl" aria-hidden="true"></span>';
    echo '<div>';
    if ($scanRequested) {
        echo '<h3 class="m-0 text-lg font-semibold text-gray-90">'.htmlspecialchars($plugin->get_lang('NoCleanableFilesFound'), ENT_QUOTES).'</h3>';
        echo '<p class="m-0 mt-2 text-body-2 text-gray-50">'.htmlspecialchars($plugin->get_lang('NoCleanableFilesFoundHelp'), ENT_QUOTES).'</p>';
    } else {
        echo '<h3 class="m-0 text-lg font-semibold text-gray-90">'.htmlspecialchars($plugin->get_lang('ScanNotRun'), ENT_QUOTES).'</h3>';
        echo '<p class="m-0 mt-2 text-body-2 text-gray-50">'.htmlspecialchars($plugin->get_lang('ScanNotRunHelp'), ENT_QUOTES).'</p>';
    }
    echo '</div>';
    echo '</div>';
    echo '</div>';

    echo '<section class="q-card mb-6 overflow-hidden">';
    echo '<div class="border-b border-gray-25 bg-support-2 px-6 py-4">';
    echo '<h3 class="m-0 text-lg font-semibold text-gray-90">'.htmlspecialchars($plugin->get_lang('CheckedLocations'), ENT_QUOTES).'</h3>';
    echo '</div>';
    echo '<div class="p-4">';
    echo '<table class="table table-hover table-striped table-bordered data_table">';
    echo '<thead><tr>';
    echo '<th>'.htmlspecialchars($plugin->get_lang('StorageType'), ENT_QUOTES).'</th>';
    echo '<th>'.htmlspecialchars($plugin->get_lang('path_dir'), ENT_QUOTES).'</th>';
    echo '<th>'.htmlspecialchars($plugin->get_lang('DetectionRule'), ENT_QUOTES).'</th>';
    echo '</tr></thead><tbody>';
    foreach ($roots as $root) {
        echo '<tr>';
        echo '<td>'.htmlspecialchars($root['label'], ENT_QUOTES).'</td>';
        echo '<td><code>'.htmlspecialchars($root['path'], ENT_QUOTES).'</code></td>';
        echo '<td>'.htmlspecialchars($root['description'], ENT_QUOTES).'</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
    echo '</section>';

    Display::display_footer();

    exit;
}

echo '<form id="clean-deleted-files-delete-form" method="post" action="'.htmlspecialchars($scanUrl, ENT_QUOTES).'">';
echo '<input type="hidden" name="sec_token" value="'.htmlspecialchars($token, ENT_QUOTES).'">';
echo '<input type="hidden" name="clean_deleted_files_action" value="delete-selected">';
echo '<input type="hidden" name="path_filter" value="'.htmlspecialchars($pathFilter, ENT_QUOTES).'">';

$index = 0;

foreach ($roots as $root) {
    $rootCandidates = array_values(array_filter(
        $candidates,
        static fn (array $candidate): bool => $candidate['root'] === $root['path']
    ));

    if ([] === $rootCandidates) {
        continue;
    }

    $rootSize = array_sum(array_map(static fn (array $candidate): int => (int) $candidate['size_bytes'], $rootCandidates));
    $group = 'root_'.$index;

    echo '<section class="q-card mb-6 overflow-hidden">';
    echo '<div class="border-b border-gray-25 bg-support-2 px-6 py-4">';
    echo '<div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">';
    echo '<div>';
    echo '<h3 class="m-0 text-lg font-semibold text-gray-90">'.htmlspecialchars($root['label'], ENT_QUOTES).'</h3>';
    echo '<p class="m-0 mt-1 text-body-2 text-gray-50">'.htmlspecialchars($plugin->get_lang('path_dir'), ENT_QUOTES).': '.htmlspecialchars($root['path'], ENT_QUOTES).'</p>';
    echo '<p class="m-0 mt-1 text-body-2 text-gray-50">'.htmlspecialchars($root['description'], ENT_QUOTES).'</p>';
    echo '</div>';
    echo '<div class="text-body-2 text-gray-50">';
    echo htmlspecialchars($plugin->get_lang('CleanableFiles'), ENT_QUOTES).': <strong>'.count($rootCandidates).'</strong> · ';
    echo htmlspecialchars($plugin->get_lang('FileDirSize'), ENT_QUOTES).': <strong>'.$plugin->formatBytes((int) $rootSize).'</strong>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    echo '<div class="p-4 overflow-x-auto">';
    echo '<table class="table table-hover table-striped table-bordered data_table">';
    echo '<thead><tr>';
    echo '<th class="text-center" style="width:40px;"><input type="checkbox" class="clean-deleted-files-select-all" data-group="'.htmlspecialchars($group, ENT_QUOTES).'"></th>';
    echo '<th>'.htmlspecialchars($plugin->get_lang('RelativePath'), ENT_QUOTES).'</th>';
    echo '<th>'.htmlspecialchars($plugin->get_lang('StorageType'), ENT_QUOTES).'</th>';
    echo '<th style="min-width:85px">'.htmlspecialchars($plugin->get_lang('size'), ENT_QUOTES).'</th>';
    echo '<th>'.htmlspecialchars($plugin->get_lang('Reason'), ENT_QUOTES).'</th>';
    echo '<th>'.htmlspecialchars(get_lang('Actions'), ENT_QUOTES).'</th>';
    echo '</tr></thead><tbody>';

    foreach ($rootCandidates as $candidate) {
        $relativePath = (string) $candidate['relative_path'];

        echo '<tr>';
        echo '<td class="text-center"><input type="checkbox" name="delete_files[]" class="clean-deleted-files-checkbox" data-group="'.htmlspecialchars($group, ENT_QUOTES).'" value="'.htmlspecialchars($relativePath, ENT_QUOTES).'"></td>';
        echo '<td><code>'.htmlspecialchars($relativePath, ENT_QUOTES).'</code></td>';
        echo '<td>'.htmlspecialchars((string) $candidate['storage_type'], ENT_QUOTES).'</td>';
        echo '<td>'.htmlspecialchars((string) $candidate['size'], ENT_QUOTES).'</td>';
        echo '<td>'.htmlspecialchars((string) $candidate['reason'], ENT_QUOTES).'</td>';
        echo '<td>';
        echo '<button type="submit" name="delete_single_file" value="'.htmlspecialchars($relativePath, ENT_QUOTES).'" class="btn btn--danger btn--sm">';
        echo '<span class="mdi mdi-delete" aria-hidden="true"></span> '.htmlspecialchars($plugin->get_lang('DeleteSingle'), ENT_QUOTES);
        echo '</button>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
    echo '</section>';

    ++$index;
}

echo '<div class="q-card mb-6 p-4">';
echo '<div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">';
echo '<p class="m-0 text-body-2 text-gray-50">'.htmlspecialchars($plugin->get_lang('ReferencedFileWarning'), ENT_QUOTES).'</p>';
echo '<button type="submit" id="delete-selected-files" class="btn btn--danger">'.
    htmlspecialchars($plugin->get_lang('DeleteSelectedFiles'), ENT_QUOTES).
    '</button>';
echo '</div>';
echo '</div>';

echo '</form>';

Display::display_footer();
