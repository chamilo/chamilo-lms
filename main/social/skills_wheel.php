<?php
/* For licensing terms, see /license.txt */

/**
 *  @package chamilo.admin
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_SOCIAL;

api_block_anonymous_users();
Skill::isAllowed(api_get_user_id());

//Adds the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_js('d3/d3.v3.5.4.min.js');
$htmlHeadXtra[] = api_get_js('d3/colorbrewer.js');
$htmlHeadXtra[] = api_get_js('d3/jquery.xcolor.js');
$htmlHeadXtra[] = api_get_js('jquery.jsPlumb.all.js');
$htmlHeadXtra[] = api_get_js('jqueryui-touch-punch/jquery.ui.touch-punch.min.js');
$htmlHeadXtra[] = api_get_js('skills.js');

$tpl = new Template(null, false, false);

$userId = api_get_user_id();
$userInfo = api_get_user_info();

$skill = new Skill();
$ranking = $skill->getUserSkillRanking($userId);
$skills = $skill->getUserSkills($userId, true);

$dialogForm = new FormValidator('form', 'post', null, null, ['id' => 'add_item']);
$dialogForm->addLabel(
    get_lang('Name'),
    Display::tag('p', null, ['id' => 'name', 'class' => 'form-control-static'])
);
$dialogForm->addLabel(
    get_lang('ShortCode'),
    Display::tag('p', null, ['id' => 'short_code', 'class' => 'form-control-static'])
);
$dialogForm->addLabel(
    get_lang('Parent'),
    Display::tag('p', null, ['id' => 'parent', 'class' => 'form-control-static'])
);
$dialogForm->addLabel(
    [
        get_lang('Gradebook'),
        get_lang('WithCertificate'),
    ],
    Display::tag(
        'ul',
        null,
        ['id' => 'gradebook', 'class' => 'form-control-static list-unstyled']
    )
);
$dialogForm->addLabel(
    get_lang('Description'),
    Display::tag('p', null, ['id' => 'description', 'class' => 'form-control-static'])
);

$type = 'read';
$tree = $skill->getSkillsTree($userId, null, true);
$skill_visualizer = new SkillVisualizer($tree, $type);
$tpl->assign('skill_visualizer', $skill_visualizer);
$tpl->assign('dialogForm', $dialogForm->returnForm());

$url = api_get_path(WEB_AJAX_PATH)."skill.ajax.php?a=get_skills_tree_json&load_user=$userId";
$tpl->assign('wheel_url', $url);

$url = api_get_path(WEB_AJAX_PATH).'skill.ajax.php?1=1';
$tpl->assign('url', $url);

$tpl->assign('user_info', $userInfo);
$tpl->assign('ranking', $ranking);
$tpl->assign('skills', $skills);

$template = $tpl->get_template('skill/skill_wheel_student.tpl');
$content = $tpl->fetch($template);
$tpl->assign('content', $content);
$tpl->display_no_layout_template();
