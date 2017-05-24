<?php
/* For licensing terms, see /license.txt */
/**
 * ODF document editor script (maybe unused)
 * @package chamilo.document
 */

require_once __DIR__.'/../inc/global.inc.php';
//exit;
$document_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$courseCode = api_get_course_id();

if (!$document_id) {
    api_not_allowed();
}

$document_data = DocumentManager::get_document_data_by_id($document_id, $courseCode, true);

if (empty($document_data)) {
    api_not_allowed();
}

//Check user visibility
$is_visible = DocumentManager::check_visibility_tree(
    $document_id,
    api_get_course_id(),
    api_get_session_id(),
    api_get_user_id(),
    api_get_group_id()
);

if (!api_is_allowed_to_edit() && !$is_visible) {
    api_not_allowed(true);
}

$header_file  = $document_data['path'];
$pathinfo = pathinfo($header_file);
$show_web_odf = false;
$web_odf_supported_files = DocumentManager::get_web_odf_extension_list();

if (
    in_array(strtolower($pathinfo['extension']), $web_odf_supported_files) &&
    api_get_configuration_value('enabled_support_odf') === true
) {
    $show_web_odf = true;
}

$file_url_web = api_get_path(WEB_COURSE_PATH).$_course['path'].'/document'.$header_file;

if (!$show_web_odf) {
    api_not_allowed(true);
}

$parent_id = $document_data['parent_id'];

if (!$parent_id) {
    $testParentId = 0;
    // Get parent id from current path
    if (!empty($document_data['path'])) {
        $testParentId = DocumentManager::get_document_id(
            api_get_course_info(),
            dirname($document_data['path']),
            0
        );
    }

    $parent_id = !empty($testParentId) ? $testParentId : 0;
}

//$htmlHeadXtra[] = api_get_js('webodf/webodf.js');
$htmlHeadXtra[] = api_get_js('wodotexteditor/wodotexteditor.js');
$htmlHeadXtra[] = api_get_js('wodotexteditor/localfileeditor.js');
$htmlHeadXtra[] = api_get_js('wodotexteditor/FileSaver.js');
$htmlHeadXtra[] = '
    <script type="text/javascript" charset="utf-8">
        $(document).on(\'ready\', function() {
            createEditor(\''.$file_url_web.'\');
        });
    </script>
';
$htmlHeadXtra[] = '
    <style>
        #editorContainer {
            width:100%;
            height:100%;
            margin:0px;
            padding:0px;
        }
    </style>
';

// Interbreadcrumb for the current directory root path
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'document/document.php',
    'name' => get_lang('Documents')
];

if (!empty($document_data['parents'])) {
    foreach($document_data['parents'] as $documentParent) {
        if ($document_data['title'] == $documentParent['title']) {
            continue;
        }

        $interbreadcrumb[] = [
            'url' => $documentParent['document_url'],
            'name' => $documentParent['title']
        ];
    }
}

$actionBack = Display::url(
    Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('DocumentsOverview'), [], ICON_SIZE_MEDIUM),
    'document.php?'.api_get_cidreq(true, true, 'editodf').'&id='.$parent_id
);
$actionEdit = Display::url(
    Display::return_icon('edit.png', get_lang('Rename').'/'.get_lang('Comments'), [], ICON_SIZE_MEDIUM),
    'edit_document.php?'.api_get_cidreq(true, true, 'editodf').'&id='.$document_id
);

$content = '<div id="editorContainer"></div>';

$view = new Template($document_data['title']);
$view->assign(
    'actions',
    Display::toolbarAction('actions', [$actionBack.$actionEdit])
);
$view->assign('header', $document_data['title']);
$view->assign('content', $content);
$view->display_one_col_template();
