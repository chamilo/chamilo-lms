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
        'name' => get_lang('ToolGradebook'),
    ];
}

$interbreadcrumb[] = [
    'url' => 'lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('LearningPaths'),
];
$interbreadcrumb[] = [
    'url' => api_get_self()."?action=build&lp_id=$lpId&".api_get_cidreq(),
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
$firstPart = explode("/", $lp->path, 2);
$zipFileName = $firstPart[0].".zip";

$form->addHeader(get_lang('UpdateFile'));
$form->addHtml(Display::return_message(get_lang('TheScormPackageWillBeUpdatedYouMustUploadTheFileWithTheSameName')." ".get_lang('FileName')." : ".$zipFileName));
$form->addLabel(null, Display::return_icon('scorm_logo.jpg', null, ['style' => 'width:230px;height:100px']));
$form->addElement('hidden', 'curdirpath', '');
$form->addElement('file', 'user_file', get_lang('FileToUpload'));
$form->addRule('user_file', get_lang('ThisFieldIsRequired'), 'required');
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
        Display::addFlash(Display::return_message(get_lang('Updated')));
    }
    header('Location: '.api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq());
    exit;
}

$content = $form->returnForm();

$tpl = new Template(null);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
