<?php

/* For licensing terms, see /license.txt. */

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/lib/PENSPlugin.php';

api_protect_admin_script();

$plugin = PENSPlugin::create();
$table = Database::get_main_table(PENSPlugin::TABLE_PENS);

$tool_name = $plugin->get_lang('PensAdminTitle');
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'admin/settings.php?category=Plugins',
    'name' => get_lang('Plugins'),
];

Display::display_header($tool_name);

echo '<div class="mb-6">';
echo '<h1 class="text-2xl font-semibold mb-2">'.htmlspecialchars($plugin->get_lang('PensAdminTitle'), ENT_QUOTES, 'UTF-8').'</h1>';
echo '<p class="text-gray-600">';
echo htmlspecialchars($plugin->get_lang('PensAdminIntro'), ENT_QUOTES, 'UTF-8');
echo '</p>';
echo '</div>';

$sql = "SELECT
            id,
            pens_version,
            package_type,
            package_type_version,
            package_format,
            package_id,
            client,
            vendor_data,
            package_name,
            created_at,
            updated_at
        FROM $table
        ORDER BY id DESC";

$result = Database::query($sql);

echo '<div class="mb-4 flex flex-wrap gap-3">';
echo Display::url(
    $plugin->get_lang('PensBackToPlugins'),
    api_get_path(WEB_CODE_PATH).'admin/settings.php?category=Plugins',
    ['class' => 'btn btn--plain']
);
echo '</div>';

echo '<div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">';
echo '<table class="w-full text-sm">';
echo '<thead class="bg-gray-20">';
echo '<tr class="text-left">';
echo '<th class="p-3 border-b border-gray-200">'.htmlspecialchars($plugin->get_lang('PensId'), ENT_QUOTES, 'UTF-8').'</th>';
echo '<th class="p-3 border-b border-gray-200">'.htmlspecialchars($plugin->get_lang('PensPackageId'), ENT_QUOTES, 'UTF-8').'</th>';
echo '<th class="p-3 border-b border-gray-200">'.htmlspecialchars($plugin->get_lang('PensClient'), ENT_QUOTES, 'UTF-8').'</th>';
echo '<th class="p-3 border-b border-gray-200">'.htmlspecialchars($plugin->get_lang('PensType'), ENT_QUOTES, 'UTF-8').'</th>';
echo '<th class="p-3 border-b border-gray-200">'.htmlspecialchars($plugin->get_lang('PensFormat'), ENT_QUOTES, 'UTF-8').'</th>';
echo '<th class="p-3 border-b border-gray-200">'.htmlspecialchars($plugin->get_lang('PensStoredFile'), ENT_QUOTES, 'UTF-8').'</th>';
echo '<th class="p-3 border-b border-gray-200">'.htmlspecialchars($plugin->get_lang('PensCreated'), ENT_QUOTES, 'UTF-8').'</th>';
echo '<th class="p-3 border-b border-gray-200">'.htmlspecialchars($plugin->get_lang('PensUpdated'), ENT_QUOTES, 'UTF-8').'</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

if (0 === Database::num_rows($result)) {
    echo '<tr>';
    echo '<td colspan="8" class="p-6 text-center text-gray-500">';
    echo htmlspecialchars($plugin->get_lang('PensNoPackages'), ENT_QUOTES, 'UTF-8');
    echo '</td>';
    echo '</tr>';
} else {
    while ($row = Database::fetch_assoc($result)) {
        $id = (int) $row['id'];
        $packageId = htmlspecialchars((string) $row['package_id'], ENT_QUOTES, 'UTF-8');
        $client = htmlspecialchars((string) $row['client'], ENT_QUOTES, 'UTF-8');
        $type = htmlspecialchars((string) $row['package_type'], ENT_QUOTES, 'UTF-8');
        $typeVersion = htmlspecialchars((string) $row['package_type_version'], ENT_QUOTES, 'UTF-8');
        $format = htmlspecialchars((string) $row['package_format'], ENT_QUOTES, 'UTF-8');
        $packageName = htmlspecialchars((string) $row['package_name'], ENT_QUOTES, 'UTF-8');
        $createdAt = htmlspecialchars((string) $row['created_at'], ENT_QUOTES, 'UTF-8');
        $updatedAt = htmlspecialchars((string) ($row['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8');
        $vendorData = trim((string) ($row['vendor_data'] ?? ''));

        echo '<tr class="hover:bg-gray-20 align-top">';
        echo '<td class="p-3 border-b border-gray-100 font-medium">'.$id.'</td>';

        echo '<td class="p-3 border-b border-gray-100">';
        echo '<div class="max-w-[420px] break-all">'.$packageId.'</div>';

        if ('' !== $vendorData) {
            echo '<div class="mt-2 text-xs text-gray-500">';
            echo '<strong>'.htmlspecialchars($plugin->get_lang('PensVendorData'), ENT_QUOTES, 'UTF-8').':</strong> '
                .htmlspecialchars($vendorData, ENT_QUOTES, 'UTF-8');
            echo '</div>';
        }

        echo '</td>';
        echo '<td class="p-3 border-b border-gray-100">'.$client.'</td>';
        echo '<td class="p-3 border-b border-gray-100">'.$type.' <span class="text-gray-500">('.$typeVersion.')</span></td>';
        echo '<td class="p-3 border-b border-gray-100">'.$format.'</td>';
        echo '<td class="p-3 border-b border-gray-100 break-all">'.$packageName.'</td>';
        echo '<td class="p-3 border-b border-gray-100 whitespace-nowrap">'.$createdAt.'</td>';
        echo '<td class="p-3 border-b border-gray-100 whitespace-nowrap">'.('' !== $updatedAt ? $updatedAt : '-').'</td>';
        echo '</tr>';
    }
}

echo '</tbody>';
echo '</table>';
echo '</div>';

echo '<div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">';
echo '<div class="font-semibold mb-2">'.htmlspecialchars($plugin->get_lang('PensCurrentBehavior'), ENT_QUOTES, 'UTF-8').'</div>';
echo '<ul class="list-disc pl-5 space-y-1">';
echo '<li>'.htmlspecialchars($plugin->get_lang('PensBehaviorReceive'), ENT_QUOTES, 'UTF-8').'</li>';
echo '<li>'.htmlspecialchars($plugin->get_lang('PensBehaviorDownload'), ENT_QUOTES, 'UTF-8').'</li>';
echo '<li>'.htmlspecialchars($plugin->get_lang('PensBehaviorStore'), ENT_QUOTES, 'UTF-8').'</li>';
echo '<li>'.htmlspecialchars($plugin->get_lang('PensBehaviorPersist'), ENT_QUOTES, 'UTF-8').'</li>';
echo '<li>'.htmlspecialchars($plugin->get_lang('PensBehaviorCallbacks'), ENT_QUOTES, 'UTF-8').'</li>';
echo '<li>'.htmlspecialchars($plugin->get_lang('PensBehaviorNoImport'), ENT_QUOTES, 'UTF-8').'</li>';
echo '</ul>';
echo '</div>';

Display::display_footer();
