<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
require_once 'work.lib.php';

$current_course_tool = TOOL_STUDENTPUBLICATION;

$workId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$docId = isset($_GET['document_id']) ? (int) $_GET['document_id'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;

if (empty($workId)) {
    api_not_allowed(true);
}

$blockAddDocuments = api_get_configuration_value('block_student_publication_add_documents');
if ($blockAddDocuments) {
    api_not_allowed(true);
}

$my_folder_data = get_work_data_by_id($workId);
if (empty($my_folder_data)) {
    api_not_allowed(true);
}

$work_data = get_work_assignment_by_id($workId);

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$courseInfo = api_get_course_info();

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
    'name' => get_lang('StudentPublications'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work_list_all.php?'.api_get_cidreq().'&id='.$workId,
    'name' => $my_folder_data['title'],
];
$interbreadcrumb[] = ['url' => '#', 'name' => get_lang('AddDocument')];

switch ($action) {
    case 'delete':
        if (!empty($workId) && !empty($docId)) {
            deleteDocumentToWork($docId, $workId, api_get_course_int_id());
            $url = api_get_path(WEB_CODE_PATH).'work/add_document.php?id='.$workId.'&'.api_get_cidreq();
            Display::addFlash(Display::return_message(get_lang('Deleted'), 'success'));
            header('Location: '.$url);
            exit;
        }
        break;
}

if (empty($docId)) {
    Display::display_header(null);
    $documents = getAllDocumentToWork($workId, api_get_course_int_id());
    if (!empty($documents)) {
        echo Display::page_subheader(get_lang('DocumentsAdded'));
        echo '<div class="well">';
        $urlDocument = api_get_path(WEB_CODE_PATH).'work/add_document.php';
        foreach ($documents as $doc) {
            $documentId = $doc['document_id'];
            $docData = DocumentManager::get_document_data_by_id($documentId, $courseInfo['code']);
            if ($docData) {
                $url = $urlDocument.'?action=delete&id='.$workId.'&document_id='.$documentId.'&'.api_get_cidreq();
                $link = Display::url(get_lang('Remove'), $url, ['class' => 'btn btn-danger']);
                echo $docData['title'].' '.$link.'<br />';
            }
        }
        echo '</div>';
    }

    $documentTree = DocumentManager::get_document_preview(
        $courseInfo,
        false,
        null,
        api_get_session_id(),
        false,
        '/',
        api_get_path(WEB_CODE_PATH).'work/add_document.php?id='.$workId.'&'.api_get_cidreq(),
        false,
        true,
        false,
        false
    );
    echo Display::page_subheader(get_lang('Documents'));
    echo $documentTree;
    echo '<hr /><div class="clear"></div>';
} else {
    $documentInfo = DocumentManager::get_document_data_by_id($docId, $courseInfo['code']);
    $url = api_get_path(WEB_CODE_PATH).'work/add_document.php?id='.$workId.'&document_id='.$docId.'&'.api_get_cidreq();
    $form = new FormValidator('add_doc', 'post', $url);
    $form->addElement('header', get_lang('AddDocument'));
    $form->addElement('hidden', 'add_doc', '1');
    $form->addElement('hidden', 'id', $workId);
    $form->addElement('hidden', 'document_id', $docId);
    $form->addElement('label', get_lang('File'), $documentInfo['title']);
    $form->addButtonCreate(get_lang('Add'));
    if ($form->validate()) {
        $values = $form->exportValues();
        $workId = $values['id'];
        $docId = $values['document_id'];
        $data = getDocumentToWork($docId, $workId, api_get_course_int_id());

        if (empty($data)) {
            addDocumentToWork($docId, $workId, api_get_course_int_id());
            Display::addFlash(Display::return_message(get_lang('Added'), 'success'));
        } else {
            Display::addFlash(Display::return_message(get_lang('DocumentAlreadyAdded'), 'warning'));
        }

        $url = api_get_path(WEB_CODE_PATH).'work/add_document.php?id='.$workId.'&'.api_get_cidreq();
        header('Location: '.$url);
        exit;
    }

    Display::display_header(null);
    $form->display();
}
