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
$lp_item_id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$submit = isset($_POST['submit_button']) ? $_POST['submit_button'] : null;
$type = isset($_GET['type']) ? $_GET['type'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;
$courseInfo = api_get_course_info();

if (!$is_allowed_to_edit || $isStudentView) {
    header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
    exit;
}

if (empty($lp_item_id)) {
    api_not_allowed();
}

/** @var learnpath $lp */
$lp = Session::read('oLP');

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
    'url' => api_get_self()."?action=build&lp_id=$learnpath_id&".api_get_cidreq(),
    'name' => $lp->getNameNoTags(),
];

$audioPreview = DocumentManager::generateAudioJavascript([]);
$htmlHeadXtra[] = "<script>
    $(function() {
        $audioPreview
     });
</script>";

switch ($type) {
    case 'dir':
        $interbreadcrumb[] = [
            'url' => 'lp_controller.php?action=add_item&type=step&lp_id='.$lp->get_id().'&'.api_get_cidreq(),
            'name' => get_lang('NewStep'),
        ];
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('NewChapter')];
        break;
    case 'document':
        $interbreadcrumb[] = [
            'url' => 'lp_controller.php?action=add_item&type=step&lp_id='.$lp->get_id().'&'.api_get_cidreq(),
            'name' => get_lang('NewStep'),
        ];
        break;
    default:
        $interbreadcrumb[] = [
            'url' => api_get_self()."?action=add_item&type=step&lp_id=$learnpath_id&".api_get_cidreq(),
            'name' => get_lang('NewStep'),
        ];
        break;
}

if ($action === 'add_item' && $type === 'document') {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('NewDocumentCreated')];
}

// Theme calls.
$show_learn_path = true;
$lp_item = new learnpathItem($lp_item_id);
$form = new FormValidator(
    'add_audio',
    'post',
    api_get_self().'?action=add_audio&id='.$lp_item_id.'&'.api_get_cidreq().'&lp_id='.$learnpath_id,
    null,
    ['enctype' => 'multipart/form-data']
);
$suredel = trim(get_lang('AreYouSureToDeleteJS'));

$lpPathInfo = $lp->generate_lp_folder($courseInfo);
DocumentManager::createDefaultAudioFolder($courseInfo);
$currentDir = '/audio';
$audioFolderId = DocumentManager::get_document_id($courseInfo, $currentDir);

if (isset($_REQUEST['folder_id'])) {
    $folderIdFromRequest = isset($_REQUEST['folder_id']) ? (int) $_REQUEST['folder_id'] : 0;
    $documentData = DocumentManager::get_document_data_by_id($folderIdFromRequest, $courseInfo['code']);
    if ($documentData) {
        $audioFolderId = $folderIdFromRequest;
        $currentDir = $documentData['path'];
    } else {
        $currentDir = '/';
        $audioFolderId = false;
    }
}

$file = null;
if (!empty($lp_item->audio)) {
    $file = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document/'.$lp_item->audio;
    $urlFile = api_get_path(WEB_COURSE_PATH).$courseInfo['path'].'/document/'.$lp_item->audio.'?'.api_get_cidreq();
}

$page = $lp->build_action_menu(
    true,
    true,
    false,
    true,
    $action
);
$page .= '<div class="row" style="overflow:hidden">';
$page .= '<div id="lp_sidebar" class="col-md-4">';
$page .= $lp->return_new_tree(null, true);

// Show the template list.
$page .= '</div>';

$recordVoiceForm = '<h3 class="page-header">'.get_lang('RecordYourVoice').'</h3>';
$page .= '<div id="doc_form" class="col-md-8">';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'rtc/RecordRTC.js"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'wami-recorder/recorder.js"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'wami-recorder/gui.js"></script>';
$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'swfobject/swfobject.js"></script>';

$tpl = new Template(get_lang('Add'));
$tpl->assign('unique_file_id', api_get_unique_id());
$tpl->assign('course_code', api_get_course_id());
$tpl->assign('filename', $lp_item->get_title().'_nano.wav');
$tpl->assign('enable_record_audio', api_get_setting('enable_record_audio') === 'true');
$tpl->assign('cur_dir_path', '/audio');
$tpl->assign('lp_item_id', $lp_item_id);
$tpl->assign('lp_dir', api_remove_trailing_slash($lpPathInfo['dir']));
$template = $tpl->get_template('learnpath/record_voice.tpl');
$recordVoiceForm .= $tpl->fetch($template);
$form->addElement('header', '<small class="text-muted">'.get_lang('Or').'</small> '.get_lang('AudioFile'));

$audioLabel = '';
if (!empty($lp_item->audio)) {
    $audioLabel = '<br />'.get_lang('FileName').': <b>'.$lp_item->audio.'<b/>';
}

$form->addLabel(null, sprintf(get_lang('AudioFileForItemX'), $lp_item->get_title()).$audioLabel);

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
            get_lang('RemoveAudio'),
            $url,
            ['class' => 'btn btn-danger']
        )
    );
}

$form->addElement('file', 'file');
$form->addElement('hidden', 'id', $lp_item_id);
$form->addButtonSave(get_lang('SaveRecordedAudio'));

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
    $audioFolderId,
    true,
    true,
    ['mp3', 'ogg', 'wav']
);

$page .= $recordVoiceForm;
$page .= '<br>';
$page .= $form->returnForm();
$page .= '<h3 class="page-header">
            <small>'.get_lang('Or').'</small> '.get_lang('SelectAnAudioFileFromDocuments').'</h3>';

$folders = DocumentManager::get_all_document_folders(
    $courseInfo,
    null,
    true,
    false,
    $currentDir
);

$form = new FormValidator(
    'selector',
    'POST',
    api_get_self().'?view=build&id='.$lp_item_id.'&lp_id='.$learnpath_id.'&action=add_audio&'.api_get_cidreq()
);

$attributes = ['onchange' => 'javascript: document.selector.submit();'];
$selector = DocumentManager::build_directory_selector(
    $folders,
    $audioFolderId,
    null,
    false,
    $form,
    'folder_id',
    $attributes
);

$page .= $selector;
$page .= '<ul class="lp_resource">';
$page .= '<li class="doc_folder" style="margin-left: 36px;">'.get_lang('Audio').'</li>';
$page .= '<li class="doc_folder">';
$page .= '<ul class="lp_resource">'.$documentTree.'</ul>';
$page .= '</div>';
$page .= '</ul>';
$page .= '</div>';
$page .= '</div>';

$tpl->assign('content', $page);
$tpl->display_one_col_template();
