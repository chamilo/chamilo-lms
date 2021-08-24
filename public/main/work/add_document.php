<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CDocument;

require_once __DIR__.'/../inc/global.inc.php';

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

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
    'name' => get_lang('Assignments'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work_list_all.php?'.api_get_cidreq().'&id='.$workId,
    'name' => $my_folder_data['title'],
];
$interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Add document')];

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
$docRepo = Container::getDocumentRepository();

if (empty($docId)) {
    Display::display_header(null);
    $documents = getAllDocumentToWork($workId, api_get_course_int_id());
    if (!empty($documents)) {
        echo Display::page_subheader(get_lang('Documents added'));
        echo '<div class="well">';
        $urlDocument = api_get_path(WEB_CODE_PATH).'work/add_document.php';
        foreach ($documents as $doc) {
            $documentId = $doc['document_id'];
            /** @var CDocument $docData */
            $docData = $docRepo->find($documentId);
            if ($docData) {
                $url = $urlDocument.'?action=delete&id='.$workId.'&document_id='.$documentId.'&'.api_get_cidreq();
                $link = Display::url(get_lang('Remove'), $url, ['class' => 'btn btn-danger']);
                echo $docData->getTitle().' '.$link.'<br />';
            }
        }
        echo '</div>';
    }

    $documentTree = DocumentManager::get_document_preview(
        api_get_course_entity(),
        false,
        null,
        api_get_session_id(),
        false,
        '/',
        api_get_path(WEB_CODE_PATH).'work/add_document.php?id='.$workId.'&'.api_get_cidreq(),
        false,
        false,
        false,
        false
    );
    echo Display::page_subheader(get_lang('Documents'));
    echo $documentTree;
    echo '<hr /><div class="clear"></div>';
    Display::display_footer();
} else {
    /** @var CDocument $documentInfo */
    $documentInfo = $docRepo->find($docId);
    $url = api_get_path(WEB_CODE_PATH).'work/add_document.php?id='.$workId.'&document_id='.$docId.'&'.api_get_cidreq();
    $form = new FormValidator('add_doc', 'post', $url);
    $form->addElement('header', get_lang('Add document'));
    $form->addElement('hidden', 'add_doc', '1');
    $form->addElement('hidden', 'id', $workId);
    $form->addElement('hidden', 'document_id', $docId);
    $form->addElement('label', get_lang('File'), $documentInfo->getTitle());
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
            Display::addFlash(Display::return_message(get_lang('Document already added'), 'warning'));
        }

        $url = api_get_path(WEB_CODE_PATH).'work/add_document.php?id='.$workId.'&'.api_get_cidreq();
        header('Location: '.$url);
        exit;
    }

    Display::display_header(null);
    $form->display();
    Display::display_footer();
}
