<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

api_protect_global_admin_script();

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];

$template = new Template(get_lang('ExtraFields'));

$types = ExtraField::getValidExtraFieldTypes();

$table = new HTML_Table(['class' => 'table']);
$table->setHeaderContents(0, 0, get_lang('Type'));
$table->setHeaderContents(0, 1, get_lang('Actions'));
$url = api_get_path(WEB_CODE_PATH).'admin/extra_fields.php?type=';
$row = 1;
foreach ($types as $key => $label) {
    $table->setCellContents($row, 0, $label);
    $table->setCellContents(
        $row,
        1,
        Display::url(
            get_lang('List'),
            $url.''.$label,
            ['class' => 'btn btn-default']
        )
    );
    $row++;
}

$content = $table->toHtml();
$template->assign('content', $content);
$template->display_one_col_template();
