<?php
/* For licensing terms, see /license.txt */
/**
 * ODF document editor script.
 *
 * @package chamilo.document
 */
require_once __DIR__.'/../inc/global.inc.php';

$documentId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$courseCode = api_get_course_id();

if (!$documentId) {
    api_not_allowed();
}

$documentInfo = DocumentManager::get_document_data_by_id(
    $documentId,
    $courseCode,
    true
);

if (empty($documentInfo)) {
    api_not_allowed();
}

//Check user visibility
$is_visible = DocumentManager::check_visibility_tree(
    $documentId,
    api_get_course_info(),
    api_get_session_id(),
    api_get_user_id(),
    api_get_group_id()
);

if (!api_is_allowed_to_edit() && !$is_visible) {
    api_not_allowed(true);
}

$headerFile = $documentInfo['path'];
$pathinfo = pathinfo($headerFile);
$showOdfEditor = false;
$webOdfSupportedFiles = DocumentManager::get_web_odf_extension_list();

if (in_array(strtolower($pathinfo['extension']), $webOdfSupportedFiles) &&
    api_get_configuration_value('enabled_support_odf') === true
) {
    $showOdfEditor = true;
}

$fileUrl = api_get_path(WEB_COURSE_PATH)
    .$_course['path'].'/document'.$headerFile;

if (!$showOdfEditor) {
    api_not_allowed(true);
}

$parentId = $documentInfo['parent_id'];

if (!$parentId) {
    $testParentId = 0;
    // Get parent id from current path
    if (!empty($documentInfo['path'])) {
        $testParentId = DocumentManager::get_document_id(
            api_get_course_info(),
            dirname($documentInfo['path']),
            0
        );
    }

    $parentId = !empty($testParentId) ? $testParentId : 0;
}

//$htmlHeadXtra[] = api_get_js('webodf/webodf.js');
$htmlHeadXtra[] = api_get_js('wodotexteditor/wodotexteditor.js');
$htmlHeadXtra[] = api_get_js('wodotexteditor/localfileeditor.js');
$htmlHeadXtra[] = api_get_js('wodotexteditor/FileSaver.js');
$htmlHeadXtra[] = '
<script>
    $(function() {
        createEditor(\''.$fileUrl.'\');
    });
</script>
';
$htmlHeadXtra[] = '
    <style>
        #editorContainer {
            width: 100%;
            height: 600px;
            margin: 0px;
            padding: 0px;
        }
    </style>
';

// Interbreadcrumb for the current directory root path
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'document/document.php',
    'name' => get_lang('Documents'),
];

if (!empty($documentInfo['parents'])) {
    foreach ($documentInfo['parents'] as $documentParent) {
        if ($documentInfo['title'] == $documentParent['title']) {
            continue;
        }

        $interbreadcrumb[] = [
            'url' => $documentParent['document_url'],
            'name' => $documentParent['title'],
        ];
    }
}

$actionBack = Display::url(
    Display::return_icon(
        'back.png',
        get_lang('BackTo').' '.get_lang('DocumentsOverview'),
        [],
        ICON_SIZE_MEDIUM
    ),
    'document.php?'.api_get_cidreq(true, true, 'editodf').'&id='.$parentId
);
$actionEdit = Display::url(
    Display::return_icon(
        'edit.png',
        get_lang('Rename').'/'.get_lang('Comments'),
        [],
        ICON_SIZE_MEDIUM
    ),
    'edit_document.php?'.api_get_cidreq(true, true, 'editodf')
        .'&id='.$documentId
);

$content = '<div id="editorContainer"></div>';

$view = new Template($documentInfo['title']);
$view->assign(
    'actions',
    Display::toolbarAction('actions', [$actionBack.$actionEdit])
);
$view->assign('header', $documentInfo['title']);
$view->assign('content', $content);
$view->display_one_col_template();
