<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

$tpl = new Template();
$contentTemplate = $tpl->get_template('glossary/glossary.js.tpl');

$tpl->display($contentTemplate);
