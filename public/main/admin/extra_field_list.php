<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

api_protect_global_admin_script();

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];

$template = new Template(get_lang('Extra fields'));

$types = ExtraField::getValidExtraFieldTypes();
sort($types);

$url = api_get_path(WEB_CODE_PATH).'admin/extra_fields.php?type=';

$rowsHtml = '';
foreach ($types as $type) {
    $type = (string) $type;

    $listUrl = $url.$type;

    $action = Display::url(
        get_lang('List'),
        $listUrl,
        [
            'class' =>
                'inline-flex items-center justify-center rounded-md px-3 py-1.5 text-sm font-semibold '.
                'bg-primary text-white hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/40 '.
                'whitespace-nowrap',
        ]
    );

    $rowsHtml .= '
        <tr class="border-b border-gray-25 hover:bg-gray-15">
            <td class="px-4 py-3 text-body-2 text-gray-90 font-mono">'.$type.'</td>
            <td class="px-4 py-3 text-body-2 text-right">'.$action.'</td>
        </tr>
    ';
}

$content = '
<div class="w-full">
    <div class="flex items-center justify-between gap-3 mb-4">
        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-lg bg-support-2 flex items-center justify-center border border-gray-25">
                <span class="text-gray-90 font-bold">EF</span>
            </div>
            <div>
                <div class="text-lg font-semibold text-gray-90">'.get_lang('Extra fields').'</div>
                <div class="text-caption text-gray-50">'.get_lang('Type').': '.count($types).'</div>
            </div>
        </div>

        <a href="index.php"
           class="inline-flex items-center justify-center rounded-md px-3 py-1.5 text-sm font-semibold
                  bg-white border border-gray-25 text-gray-90 hover:bg-gray-15">
            '.get_lang('Back to').' '.get_lang('Administration').'
        </a>
    </div>

    <div class="w-full overflow-hidden rounded-xl border border-gray-25 bg-white shadow-xl">
        <table class="w-full">
            <thead class="bg-support-2">
                <tr class="border-b border-gray-25">
                    <th class="px-4 py-3 text-left text-tiny font-semibold text-gray-90 uppercase tracking-wider">
                        '.get_lang('Type').'
                    </th>
                    <th class="px-4 py-3 text-right text-tiny font-semibold text-gray-90 uppercase tracking-wider">
                        '.get_lang('Detail').'
                    </th>
                </tr>
            </thead>
            <tbody>
                '.$rowsHtml.'
            </tbody>
        </table>
    </div>
</div>
';

$template->assign('content', $content);
$template->display_one_col_template();
