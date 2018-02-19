<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Editor\CkEditor\CkEditor;

require_once __DIR__.'/../../global.inc.php';

$template = new Template();

$editor = new CkEditor();

$templates = $editor->simpleFormatTemplates();

$template->assign('templates', $templates);
header('Content-type: application/x-javascript');
$template->display('default/javascript/editor/ckeditor/templates.tpl');
