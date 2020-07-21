<?php

/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
require_once 'work.lib.php';

if (false === api_get_configuration_value('allow_my_student_publication_page')) {
    api_not_allowed(true);
}

api_block_anonymous_users();

$htmlHeadXtra[] = api_get_jqgrid_js();

$tpl = new Template(get_lang('StudentPublications'));

$tpl->assign('intro_title', get_lang('MyStudentPublicationsTitle'));
$tpl->assign('intro_content', Display::return_message(get_lang('MyStudentPublicationsExplanation')));
$tpl->assign('table', showStudentAllWorkGrid(0));
$tpl->assign('table_with_results', showStudentAllWorkGrid(1));

$tpl->display($tpl->get_template('work/publications.tpl'));
