<?php
/* For licensing terms, see /license.txt */

require_once '../../global.inc.php';

use Chamilo\CoreBundle\Component\Editor\CkEditor\CkEditor;

$template = new Template();

$table = Database::get_main_table(TABLE_MAIN_SYSTEM_TEMPLATE);
$sql = "SELECT * FROM $table";
$result = Database::query($sql);
$templates = Database::store_result($result, 'ASSOC');
$editor = new CkEditor();
$templates = $editor->simpleFormatTemplates($templates);
$template->assign('templates', $templates);
$template->display('default/javascript/editor/ckeditor/templates.tpl');
