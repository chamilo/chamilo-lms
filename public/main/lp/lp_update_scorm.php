<?php
/* For licensing terms, see /license.txt */

/**
 * This is a learning path creation and player tool in Chamilo - previously.
 *
 * @author Julio Montoya  - Improving the list of templates
 */
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;

api_protect_course_script();

$allow = api_is_allowed_to_edit(null, true);
$lpId = !empty($_GET['lp_id']) ? (int) $_GET['lp_id'] : 0;

if (!$allow || empty($lpId)) {
    api_not_allowed(true);
}

$lp = new learnpath(api_get_course_id(), $lpId, api_get_user_id());

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Assessments'),
    ];
}

$interbreadcrumb[] = [
    'url' => 'lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('Learning paths'),
];
$interbreadcrumb[] = [
    'url' => api_get_self()."?action=add_item&lp_id=$lpId&".api_get_cidreq(),
    'name' => $lp->getNameNoTags(),
];

$form = new FormValidator(
    '',
    'POST',
    api_get_self().'?'.api_get_cidreq().'&lp_id='.$lpId,
    '',
    [
        'id' => 'upload_form',
        'enctype' => 'multipart/form-data',
    ]
);
$form->addHeader(get_lang('Update file'));
$form->addHtml(Display::return_message(get_lang('You must upload a zip file with the same name as the original SCORM file.')));
$form->addLabel(null, Display::return_icon('scorm_logo.jpg', null, ['style' => 'width:230px;height:100px']));
$form->addElement('hidden', 'curdirpath', '');
$form->addElement('file', 'user_file', get_lang('SCORM or AICC file to upload'));
$form->addRule('user_file', get_lang('Required field'), 'required');
$form->addButtonUpload(get_lang('Upload'));

if ($form->validate()) {
    $oScorm = new scorm();
    $manifest = $oScorm->import_package(
        $_FILES['user_file'],
        '',
        api_get_course_info(),
        true,
        $lp
    );
    if ($manifest) {
        Display::addFlash(Display::return_message(get_lang('Update successful')));
    }
    header('Location: '.api_get_path(WEB_CODE_PATH).'lp/lp_list.php?'.api_get_cidreq());
    exit;
}

$content = $form->returnForm();

$tpl = new Template(null);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
