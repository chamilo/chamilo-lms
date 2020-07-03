<?php

/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
require_once 'work.lib.php';

api_block_anonymous_users();

$htmlHeadXtra[] = api_get_jqgrid_js();

$tpl = new Template(get_lang('StudentPublications'));

$tpl->assign('introduction_message', Display::return_message(get_lang('StudentPublicationsIntro')));
$tpl->assign('table', showStudentAllWorkGrid(0));
$tpl->assign('table_with_results', showStudentAllWorkGrid(1));

$tpl->display($tpl->get_template('work/publications.tpl'));
