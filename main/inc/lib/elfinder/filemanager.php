<?php
/* For licensing terms, see /license.txt */

require_once '../../global.inc.php';
Chat::setDisableChat();
$template = new Template();
$template->display('default/javascript/editor/ckeditor/elfinder.tpl');
