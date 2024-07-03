<?php
/* For licensing terms, see /license.txt */

use Symfony\Component\HttpFoundation\Request as HttpRequest;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script(false, true);
Skill::isAllowed();

$httpRequest = HttpRequest::createFromGlobals();

//Adds the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_js('d3/d3.v3.5.4.min.js');
$htmlHeadXtra[] = api_get_js('d3/colorbrewer.js');
$htmlHeadXtra[] = api_get_js('d3/jquery.xcolor.js');

$tpl = new Template(null, false, false);

$load_user = 0;
if (isset($_GET['load_user'])) {
    $load_user = 1;
}

$skill_condition = '';
if (isset($_GET['skill_id'])) {
    $skillId = $httpRequest->query->getInt('skill_id');
    $skill_condition = "&skill_id=$skillId";
    $tpl->assign('skill_id_to_load', $skillId);
}

$url = api_get_path(WEB_AJAX_PATH)."skill.ajax.php?a=get_skills_tree_json&load_user=$load_user";
$tpl->assign('wheel_url', $url);

$url = api_get_path(WEB_AJAX_PATH).'skill.ajax.php?1=1';
$tpl->assign('url', $url);
$tpl->assign('isAdministration', true);

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
    [get_lang('Gradebook'), get_lang('WithCertificate')],
    Display::tag('ul', null, ['id' => 'gradebook', 'class' => 'form-control-static list-unstyled'])
);
$dialogForm->addLabel(
    get_lang('Description'),
    Display::tag(
        'p',
        null,
        ['id' => 'description', 'class' => 'form-control-static']
    )
);

$tpl->assign('dialogForm', $dialogForm->returnForm());

$saveProfileForm = new FormValidator(
    'form',
    'post',
    null,
    null,
    ['id' => 'dialog-form-profile']
);
$saveProfileForm->addHidden('profile_id', null);
$saveProfileForm->addText(
    'name',
    get_lang('Name'),
    true,
    ['id' => 'name_profile']
);
$saveProfileForm->addTextarea(
    'description',
    get_lang('Description'),
    ['id' => 'description_profile', 'rows' => 6]
);
$tpl->assign('save_profile_form', $saveProfileForm->returnForm());
$templateName = $tpl->get_template('skill/skill_wheel.tpl');
$content = $tpl->fetch($templateName);

$tpl->assign('content', $content);
$tpl->display_no_layout_template();
