<?php
/* For licensing terms, see /license.txt */

/**
 * This is a learning path creation and player tool in Chamilo - previously
 * @author Julio Montoya  - Improving the list of templates
 * @package chamilo.learnpath
 */

$this_section = SECTION_COURSES;

api_protect_course_script();

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

$isStudentView = isset($_REQUEST['isStudentView']) ? (int)$_REQUEST['isStudentView'] : null;
$learnpath_id = (int)$_REQUEST['lp_id'];
$submit = isset($_POST['submit_button']) ? $_POST['submit_button'] : null;

$type = isset($_GET['type']) ? $_GET['type'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;

if ((!$is_allowed_to_edit) || ($isStudentView)) {
    error_log('New LP - User not authorized in lp_add_item.php');
    header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
    exit;
}
/* SHOWING THE ADMIN TOOLS */

if (isset($_SESSION['gradebook'])) {
    $gradebook = $_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array (
        'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
        'name' => get_lang('ToolGradebook')
    );
}

$interbreadcrumb[] = array('url' => 'lp_controller.php?action=list', 'name' => get_lang('LearningPaths'));
$interbreadcrumb[] = array('url' => api_get_self()."?action=build&lp_id=$learnpath_id", 'name' => $_SESSION['oLP']->get_name());

switch ($type) {
    case 'dir':
        $interbreadcrumb[] = array(
            'url' => 'lp_controller.php?action=add_item&type=step&lp_id='.$_SESSION['oLP']->get_id().'&'.api_get_cidreq(),
            'name' => get_lang('NewStep'),
        );
        $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('NewChapter'));
        break;
    case 'document':
        $interbreadcrumb[] = array(
            'url' => 'lp_controller.php?action=add_item&type=step&lp_id='.$_SESSION['oLP']->get_id().'&'.api_get_cidreq(),
            'name' => get_lang('NewStep'),
        );
        break;
    default:
        $interbreadcrumb[]= array('url' => '#', 'name' => get_lang('NewStep'));
        break;
}

if ($action == 'add_item' && $type == 'document') {
    $interbreadcrumb[]= array ('url' => '#', 'name' => get_lang('NewDocumentCreated'));
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
    api_get_self() . '?action=add_audio&id=' . $lp_item_id . '&' . api_get_cidreq().'&lp_id='.$learnpath_id,
    null,
    array('enctype' => 'multipart/form-data')
);
$suredel = trim(get_lang('AreYouSureToDeleteJS'));

$lpPathInfo = $_SESSION['oLP']->generate_lp_folder(api_get_course_info());

$file = null;
if (isset($lp_item->audio) && !empty($lp_item->audio)) {
    $file = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document/audio/'.$lp_item->audio;
    $urlFile = api_get_path(WEB_COURSE_PATH).$courseInfo['path'].'/document/audio/'.$lp_item->audio.'?'.api_get_cidreq();

    if (!file_exists($file)) {
        $file = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document'.$lpPathInfo['dir'] . '/' . $lp_item->audio;
        $urlFile = api_get_path(WEB_COURSE_PATH).$courseInfo['path'].'/document'.$lpPathInfo['dir'] . '/' . $lp_item->audio.'?'.api_get_cidreq();
    }
}

$page = $_SESSION['oLP']->build_action_menu(true);
$page .= '<div class="row" style="overflow:hidden">';
$page .= '<div id="lp_sidebar" class="col-md-4">';
$page .= $_SESSION['oLP']->return_new_tree(null, true);

// Show the template list.
$page .= '</div>';

$recordVoiceForm = Display::page_subheader(get_lang('RecordYourVoice'));

$page .= '<div id="doc_form" class="col-md-8">';

$htmlHeadXtra[] = '<script src="' . api_get_path(WEB_LIBRARY_JS_PATH) . 'rtc/RecordRTC.js"></script>';
$htmlHeadXtra[] = '<script src="' . api_get_path(WEB_LIBRARY_PATH) . 'wami-recorder/recorder.js"></script>';
$htmlHeadXtra[] = '<script src="' . api_get_path(WEB_LIBRARY_PATH) . 'wami-recorder/gui.js"></script>';
$htmlHeadXtra[] = '<script type="text/javascript" src="' . api_get_path(WEB_LIBRARY_PATH) . 'swfobject/swfobject.js"></script>';

$tpl = new Template(null);
$tpl->assign('unique_file_id', api_get_unique_id());
$tpl->assign('course_code', api_get_course_id());
$tpl->assign('php_session_id', session_id());
$tpl->assign('filename', $lp_item->get_title().'_nano.wav');
$tpl->assign('enable_record_audio', api_get_setting('enable_record_audio') === 'true');
$tpl->assign('cur_dir_path', '/audio');
$tpl->assign('lp_item_id', $lp_item_id);
$tpl->assign('lp_dir', api_remove_trailing_slash($lpPathInfo['dir']));
$recordVoiceForm .= $tpl->fetch('default/learnpath/record_voice.tpl');
$form->addElement('header', get_lang('Or'));
$form->addElement('header', get_lang('AudioFile'));
$form->addLabel(null, sprintf(get_lang('AudioFileForItemX'), $lp_item->get_title()));

if (!empty($file)) {
    $audioPlayer = '<div id="preview">'.
        Display::getMediaPlayer($file, array('url' => $urlFile)).
        "</div>";
    $form->addElement('label', get_lang('Listen'), $audioPlayer);
    $url = api_get_path(WEB_CODE_PATH) . 'lp/lp_controller.php?lp_id='.$_SESSION['oLP']->get_id().'&action=add_audio&id='.$lp_item_id.'&delete_file=1&'.api_get_cidreq();
    $form->addElement('label', null, Display::url(get_lang('RemoveAudio'), $url, array('class' => 'btn btn-danger')));
} else {
    $form->addElement('file', 'file');
    $form->addElement('hidden', 'id', $lp_item_id);
    $form->addButtonSave(get_lang('Save'));
}
$form->addElement('header', get_lang('Or'));

$courseInfo = api_get_course_info();
$documentTree = DocumentManager::get_document_preview(
    $courseInfo,
    false,
    null,
    api_get_session_id(),
    false,
    '',
    urlencode('lp_controller.php?action=add_audio&lp_id='.$_SESSION['oLP']->get_id().'&id='.$lp_item_id),
    false,
    true
    //$folderId = false
);

$page .= $recordVoiceForm;
$page .= $form->returnForm();
$page .= '<legend>'.get_lang('SelectAnAudioFileFromDocuments').'</legend>';
$page .= $documentTree;
$page .= '</div>';
$page .= '</div>';

$tpl->assign('content', $page);
$content = $tpl->fetch('default/learnpath/lp_upload_audio.tpl');
$tpl->display_one_col_template();
