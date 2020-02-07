<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This is a learning path creation and player tool in Chamilo - previously.
 *
 * @author Julio Montoya  - Improving the list of templates
 */
$this_section = SECTION_COURSES;
api_protect_course_script();
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
$isStudentView = api_is_student_view_active();
$learnpath_id = (int) $_REQUEST['lp_id'];
$submit = isset($_POST['submit_button']) ? $_POST['submit_button'] : null;
$type = isset($_GET['type']) ? $_GET['type'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;

if (!$is_allowed_to_edit || $isStudentView) {
    header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
    exit;
}

/** @var learnpath $lp */
$lp = Session::read('oLP');

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
    'url' => api_get_self()."?action=add_item&lp_id=$learnpath_id&".api_get_cidreq(),
    'name' => $lp->getNameNoTags(),
];

switch ($type) {
    case 'dir':
        $interbreadcrumb[] = [
            'url' => 'lp_controller.php?action=add_item&type=step&lp_id='.$lp->get_id().'&'.api_get_cidreq(),
            'name' => get_lang('Add learning object or activity'),
        ];
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Add section')];
        break;
    case 'document':
        $interbreadcrumb[] = [
            'url' => 'lp_controller.php?action=add_item&type=step&lp_id='.$lp->get_id().'&'.api_get_cidreq(),
            'name' => get_lang('Add learning object or activity'),
        ];
        break;
    default:
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Add learning object or activity')];
        break;
}

if ('add_item' == $action && 'document' == $type) {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('The rich media page/activity has been added to the course')];
}

// Theme calls.
$show_learn_path = true;
$lp_item_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (empty($lp_item_id)) {
    api_not_allowed();
}

$courseInfo = api_get_course_info();
$lp_item = new learnpathItem($lp_item_id);
$form = new FormValidator(
    'add_audio',
    'post',
    api_get_self().'?action=add_audio&id='.$lp_item_id.'&'.api_get_cidreq().'&lp_id='.$learnpath_id,
    null,
    ['enctype' => 'multipart/form-data']
);
$suredel = trim(get_lang('Are you sure to delete'));

$lpPathInfo = $lp->generate_lp_folder(api_get_course_info());

DocumentManager::createDefaultAudioFolder($courseInfo);

$audioFolderId = DocumentManager::get_document_id($courseInfo, '/audio');

$file = null;
if (isset($lp_item->audio) && !empty($lp_item->audio)) {
    //$file = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document/audio/'.$lp_item->audio;
    //$urlFile = api_get_path(WEB_COURSE_PATH).$courseInfo['path'].'/document/audio/'.$lp_item->audio.'?'.api_get_cidreq();
    /*if (!file_exists($file)) {
        $file = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document'.$lpPathInfo['dir'].'/'.$lp_item->audio;
        $urlFile = api_get_path(WEB_COURSE_PATH).$courseInfo['path'].'/document'.$lpPathInfo['dir'].'/'.$lp_item->audio.'?'.api_get_cidreq();
    }*/
}

$page = $lp->build_action_menu(true);
$page .= '<div class="row" style="overflow:hidden">';
$page .= '<div id="lp_sidebar" class="col-md-4">';
$page .= $lp->return_new_tree(null, true);

// Show the template list.
$page .= '</div>';

$recordVoiceForm = Display::page_subheader(get_lang('Record your voice'));

$page .= '<div id="doc_form" class="col-md-8">';

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'rtc/RecordRTC.js"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'wami-recorder/recorder.js"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'wami-recorder/gui.js"></script>';
$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'swfobject/swfobject.js"></script>';

$tpl = new Template(null);
$tpl->assign('unique_file_id', api_get_unique_id());
$tpl->assign('course_code', api_get_course_id());
$tpl->assign('filename', $lp_item->get_title().'_nano.wav');
$tpl->assign('enable_record_audio', 'true' === api_get_setting('enable_record_audio'));
$tpl->assign('cur_dir_path', '/audio');
$tpl->assign('lp_item_id', $lp_item_id);
$tpl->assign('lp_dir', api_remove_trailing_slash($lpPathInfo['dir']));
$form->addElement('header', get_lang('or'));
$form->addElement('header', get_lang('Audio file'));
$form->addLabel(null, sprintf(get_lang('Audio fileForItemX'), $lp_item->get_title()));

if (!empty($file)) {
    $audioPlayer = '<div id="preview">'.
        Display::getMediaPlayer($file, ['url' => $urlFile]).
        "</div>";
    $form->addElement('label', get_lang('Listen'), $audioPlayer);
    $url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?lp_id='.$lp->get_id().'&action=add_audio&id='.$lp_item_id.'&delete_file=1&'.api_get_cidreq();
    $form->addElement(
        'label',
        null,
        Display::url(
            get_lang('Remove audio'),
            $url,
            ['class' => 'btn btn-danger']
        )
    );
} else {
    $form->addElement('file', 'file');
    $form->addElement('hidden', 'id', $lp_item_id);
    $form->addButtonSave(get_lang('Save'));
}
$form->addElement('header', get_lang('or'));

$courseInfo = api_get_course_info();
$documentTree = DocumentManager::get_document_preview(
    $courseInfo,
    $lp->get_id(),
    null,
    api_get_session_id(),
    false,
    '',
    api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?action=add_audio&lp_id='.$lp->get_id().'&id='.$lp_item_id,
    false,
    true,
    $audioFolderId
);

$tpl->assign('pre_page', $page);

$tpl->assign('form', $form->returnForm());

$page = '<legend>'.get_lang('SelectAnAudio fileFromDocuments').'</legend>';
$page .= $documentTree;
$page .= '</div>';
$page .= '</div>';

$tpl->assign('post_page', $page);

$tpl->displayTemplate('@ChamiloTheme/LearnPath/add_audio.html.twig');
