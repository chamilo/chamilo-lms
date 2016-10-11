<?php
/* For licensing terms, see /license.txt */

require_once '../../global.inc.php';

use Chamilo\CoreBundle\Component\Editor\CkEditor\CkEditor;

$template = new Template();

$editor = new CkEditor();

$templates = $editor->simpleFormatTemplates();

$template->assign('templates', $templates);
header('Content-type: application/x-javascript');
$template->display('default/javascript/editor/ckeditor/templates.tpl');
