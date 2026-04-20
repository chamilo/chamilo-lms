<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;
$current_course_tool = TOOL_GROUP;

// Notice for unauthorized people.
api_protect_course_script(true);

if (!api_is_allowed_to_edit(false, true)) {
    api_not_allowed(true);
}

/**
 * Escape HTML output.
 */
function group_import_escape(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Build import result HTML.
 */
function group_import_build_result_html(array $result): string
{
    $statusStyles = [
        'created' => 'border-success bg-white text-gray-90',
        'updated' => 'border-info bg-support-2 text-gray-90',
        'deleted' => 'border-warning bg-white text-gray-90',
        'error' => 'border-danger bg-white text-gray-90',
    ];

    $html = '<div class="space-y-4">';

    foreach ($result as $status => $data) {
        if ('error' !== $status) {
            if (empty($data['category']) && empty($data['group'])) {
                continue;
            }
        }

        $style = $statusStyles[$status] ?? 'border-gray-20 bg-white text-gray-90';

        $html .= '<div class="rounded-lg border '.$style.' p-4">';
        $html .= '<h3 class="mb-3 text-base font-semibold text-gray-90">'.group_import_escape(get_lang(ucfirst($status))).'</h3>';

        if (!empty($data['category'])) {
            $html .= '<h4 class="mb-2 text-sm font-semibold text-gray-90">'.group_import_escape(get_lang('Categories')).'</h4>';
            $html .= '<ul class="mb-3 space-y-1 text-sm text-gray-90">';
            foreach ($data['category'] as $category) {
                $html .= '<li>• '.group_import_escape($category['category']).'</li>';
            }
            $html .= '</ul>';
        }

        if (!empty($data['group'])) {
            $html .= '<h4 class="mb-2 text-sm font-semibold text-gray-90">'.group_import_escape(get_lang('Groups')).'</h4>';
            $html .= '<ul class="mb-3 space-y-1 text-sm text-gray-90">';
            foreach ($data['group'] as $group) {
                $html .= '<li>• '.group_import_escape($group['group']).'</li>';
            }
            $html .= '</ul>';
        }

        if ('error' === $status && !empty($data)) {
            $html .= '<div class="space-y-1 text-sm text-gray-90">';
            foreach ($data as $message) {
                if (!empty($message)) {
                    $html .= '<div>• '.group_import_escape($message).'</div>';
                }
            }
            $html .= '</div>';
        }

        $html .= '</div>';
    }

    $html .= '</div>';

    return $html;
}

$nameTools = get_lang('Import');
$interbreadcrumb[] = [
    'url' => 'group.php?'.api_get_cidreq(),
    'name' => get_lang('Groups'),
];

$pageMessages = [];

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    if (
        !isset($_FILES['file']['tmp_name']) ||
        empty($_FILES['file']['tmp_name'])
    ) {
        $pageMessages[] = Display::return_message(get_lang('Required field'), 'warning');
    } else {
        $groupData = Import::csv_reader($_FILES['file']['tmp_name']);
        $deleteNotInArray = 1 == ($_POST['delete_not_in_file'] ?? 0);

        $result = GroupManager::importCategoriesAndGroupsFromArray(
            $groupData,
            $deleteNotInArray
        );

        if (!empty($result)) {
            Display::addFlash(
                Display::return_message(
                    group_import_build_result_html($result),
                    'information',
                    false
                )
            );

            header('Location: '.api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq());
            exit;
        }

        $pageMessages[] = Display::return_message(get_lang('NoResultsAvailable'), 'warning');
    }
}

Display::display_header($nameTools, 'Group');

$containerClass = 'mx-auto w-full px-4 sm:px-6 lg:px-8';
$cardClass = 'rounded-lg border border-gray-20 bg-white p-6 shadow-sm';
$inputClass = 'mt-1 block w-full rounded-md border-gray-25 bg-white text-gray-90 shadow-sm focus:border-primary focus:ring-primary';
$checkboxClass = 'h-4 w-4 rounded border-gray-25 text-white focus:ring-primary';
$primaryButtonClass = 'inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90';
$secondaryButtonClass = 'inline-flex items-center justify-center rounded-md border border-gray-25 bg-white px-4 py-2 text-sm font-medium text-gray-90 shadow-sm transition hover:bg-gray-15';

echo '<div class="'.$containerClass.' space-y-6">';
echo '<div>';
echo '<h1 class="text-2xl font-semibold text-gray-90">'.group_import_escape(get_lang('Import groups')).'</h1>';
echo '<p class="mt-1 text-sm text-gray-50">'.group_import_escape(get_lang('Import groups from a CSV file and optionally remove items that are not present in the file.')).'</p>';
echo '</div>';

if (!empty($pageMessages)) {
    foreach ($pageMessages as $message) {
        echo $message;
    }
}

echo '<div class="'.$cardClass.'">';
echo '<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">';
echo '<div class="lg:col-span-2">';
echo '<form method="post" action="'.group_import_escape(api_get_self().'?'.api_get_cidreq()).'" enctype="multipart/form-data" class="space-y-6">';

echo '<div>';
echo '<label for="file" class="block text-sm font-medium text-gray-90">';
echo group_import_escape(get_lang('CSV file import location'));
echo ' <span class="text-danger">*</span>';
echo '</label>';
echo '<input id="file" name="file" type="file" accept=".csv,text/csv" class="'.$inputClass.'">';
echo '<p class="mt-2 text-xs text-gray-50">'.group_import_escape(get_lang('Required field')).'</p>';
echo '</div>';

echo '<div class="rounded-lg border border-gray-20 bg-gray-10 p-4">';
echo '<label class="flex cursor-pointer items-start gap-3 text-sm text-gray-90">';
echo '<input class="'.$checkboxClass.' mt-0.5" type="checkbox" id="delete_not_in_file" name="delete_not_in_file" value="1">';
echo '<span>'.group_import_escape(get_lang('Delete items not in file')).'</span>';
echo '</label>';
echo '<p class="mt-2 text-xs text-gray-50">'.group_import_escape(get_lang('Use this option carefully because items missing from the CSV can be removed during the import process.')).'</p>';
echo '</div>';

echo '<div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-20 pt-4">';
echo '<a class="'.$secondaryButtonClass.'" href="'.group_import_escape(api_get_path(WEB_CODE_PATH).'group/example.csv').'">';
echo group_import_escape(get_lang('Example CSV file'));
echo '</a>';

echo '<button type="submit" class="'.$primaryButtonClass.'">';
echo group_import_escape(get_lang('Import'));
echo '</button>';
echo '</div>';

echo '</form>';
echo '</div>';

echo '<div class="space-y-4">';
echo '<div class="rounded-lg border border-info bg-support-2 p-4">';
echo '<h2 class="mb-2 text-sm font-semibold text-gray-90">'.group_import_escape(get_lang('Information')).'</h2>';
echo '<ul class="space-y-2 text-sm text-gray-90">';
echo '<li>• '.group_import_escape(get_lang('Upload a CSV file with the group data you want to import.')).'</li>';
echo '<li>• '.group_import_escape(get_lang('You can download the example file to use the expected format.')).'</li>';
echo '<li>• '.group_import_escape(get_lang('If you enable deletion, items not present in the file may be removed.')).'</li>';
echo '</ul>';
echo '</div>';

echo '<div class="rounded-lg border border-gray-20 bg-gray-10 p-4">';
echo '<h2 class="mb-2 text-sm font-semibold text-gray-90">'.group_import_escape(get_lang('Required field')).'</h2>';
echo '<p class="text-sm text-gray-50">'.group_import_escape(get_lang('The CSV file is mandatory to start the import.')).'</p>';
echo '</div>';
echo '</div>';

echo '</div>';
echo '</div>';
echo '</div>';

Display::display_footer();
