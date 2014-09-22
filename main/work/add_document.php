<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

$language_file = array('exercice', 'work', 'document', 'admin', 'gradebook');

//require_once '../inc/global.inc.php';
// Including necessary files
require_once 'work.lib.php';

if (ADD_DOCUMENT_TO_WORK == false) {
    exit;
}

$current_course_tool  = TOOL_STUDENTPUBLICATION;

$workId = isset($_GET['id']) ? intval($_GET['id']) : null;
$docId = isset($_GET['document_id']) ? intval($_GET['document_id']) : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;

if (empty($workId)) {
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

$interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(), 'name' => get_lang('StudentPublications'));
$interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'work/work_list_all.php?'.api_get_cidreq().'&id='.$workId, 'name' => $my_folder_data['title']);
$interbreadcrumb[] = array('url' => '#', 'name' => get_lang('AddDocument'));

$error_message = null;

switch ($action) {
    case 'delete':
        if (!empty($workId) && !empty($docId)) {
            deleteDocumentToWork($docId, $workId, api_get_course_int_id());
            $url = api_get_path(WEB_CODE_PATH).'work/add_document.php?id='.$workId;
            header('Location: '.$url);
            exit;
        }
        break;
}

if (empty($docId)) {

    Display :: display_header(null);
    $documents = getAllDocumentToWork($workId, api_get_course_int_id());
    if (!empty($documents)) {
        echo Display::page_subheader(get_lang('DocumentsAdded'));
        echo '<div class="well">';
        foreach ($documents as $doc) {
            $documentId = $doc['document_id'];
            $docData = DocumentManager::get_document_data_by_id($documentId, $courseInfo['code']);
            if ($docData) {
                $url = api_get_path(WEB_CODE_PATH).'work/add_document.php?action=delete&id='.$workId.'&document_id='.$documentId;
                $link = Display::url(get_lang('Delete'), $url);
                echo $docData['title'].' '.$link.'<br />';
            }

        }
        echo '</div>';
    }

    $document_tree = DocumentManager::get_document_preview($courseInfo, null, null, 0, false, '/', api_get_path(WEB_CODE_PATH).'work/add_document.php?id='.$workId);
    echo Display::page_subheader(get_lang('Documents'));
    echo $document_tree;
    echo '<hr /><div class="clear"></div>';
} else {
    $message = null;

    $documentInfo = DocumentManager::get_document_data_by_id($docId, $courseInfo['code']);
    $form = new FormValidator('add_doc', 'post', api_get_path(WEB_CODE_PATH).'work/add_document.php?id='.$workId.'&document_id='.$docId);
    $form->addElement('header', get_lang('AddDocument'));
    $form->addElement('hidden', 'add_doc', '1');
    $form->addElement('hidden', 'id', $workId);
    $form->addElement('hidden', 'document_id', $docId);
    $form->addElement('label', get_lang('File'), $documentInfo['title']);
    $form->addElement('style_submit_button', 'submit', get_lang('Add'));
    if ($form->validate()) {
        $values = $form->exportValues();
        $workId = $values['id'];
        $docId = $values['document_id'];
        $data = getDocumentToWork($docId, $workId, api_get_course_int_id());

        if (empty($data)) {
            addDocumentToWork($docId, $workId, api_get_course_int_id());
            $url = api_get_path(WEB_CODE_PATH).'work/add_document.php?id='.$workId;
            header('Location: '.$url);
            exit;
        } else {
            $message = Display::return_message(get_lang('DocumentAlreadyAdded'), 'warning');
        }
    }

    Display :: display_header(null);
    echo $message;
    $form->display();
}

/*
 * DB changes needed
 *
CREATE TABLE IF NOT EXISTS c_student_publication_rel_document (
    id  INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    work_id INT NOT NULL,
    document_id INT NOT NULL,
    c_id INT NOT NULL
);

CREATE TABLE IF NOT EXISTS c_student_publication_rel_user (
    id  INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    work_id INT NOT NULL,
    user_id INT NOT NULL,
    c_id INT NOT NULL
);



*/


