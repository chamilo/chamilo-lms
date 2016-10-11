<?php
/* For licensing terms, see /license.txt */
/**
 * ODF document editor script (maybe unused)
 * @package chamilo.document
 */

require_once '../inc/global.inc.php';
//exit;
$document_id = $_GET['id'];
$courseCode = api_get_course_id();

if ($document_id) {
    $document_data = DocumentManager::get_document_data_by_id($document_id, $courseCode);
    if (empty($document_data)) {
        api_not_allowed();
    }
} else {
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

if (in_array(strtolower($pathinfo['extension']), $web_odf_supported_files)) {
    $show_web_odf  = true;
}

$file_url_web = api_get_path(WEB_COURSE_PATH).$_course['path'].'/document'.$header_file;

if ($show_web_odf) {
    //$htmlHeadXtra[] = api_get_js('webodf/webodf.js');
    $htmlHeadXtra[] = api_get_js('wodotexteditor/wodotexteditor.js');
    $htmlHeadXtra[] = api_get_js('wodotexteditor/localfileeditor.js');
    $htmlHeadXtra[] = api_get_js('wodotexteditor/FileSaver.js');
    //$htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/webodf/webodf.css');
    /*$htmlHeadXtra[] = '
    <script type="text/javascript" charset="utf-8">
        function init() {
                var odfelement = document.getElementById("odf"),
                odfcanvas = new odf.OdfCanvas(odfelement);
                odfcanvas.load("'.$file_url_web.'");
                createEditor();
        }
        $(document).ready(function() {
            //createEditor();
            window.setTimeout(init, 0);
        });
  </script>';
    */
    $htmlHeadXtra[] = '
    <script type="text/javascript" charset="utf-8">
    $(document).ready(function() {
        createEditor("'.$file_url_web.'");
    });
    </script>';
}
/*
$interbreadcrumb[]=array("url"=>"./document.php?curdirpath=".urlencode($my_cur_dir_path).$req_gid, "name"=> get_lang('Documents'));

// Interbreadcrumb for the current directory root path
if (empty($document_data['parents'])) {
    $interbreadcrumb[] = array('url' => '#', 'name' => $document_data['title']);
} else {
    foreach($document_data['parents'] as $document_sub_data) {
        if ($document_data['title'] == $document_sub_data['title']) {
            continue;
        }
        $interbreadcrumb[] = array('url' => $document_sub_data['document_url'], 'name' => $document_sub_data['title']);
    }
}
*/
//
echo Display::display_header('');

echo '<div class="actions">';
echo '<a href="document.php?id='.$parent_id.'">'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('DocumentsOverview'),'',ICON_SIZE_MEDIUM).'</a>';
echo '<a href="edit_document.php?'.api_get_cidreq().'&id='.$document_id.$req_gid.'&origin=editodf">'.Display::return_icon('edit.png',get_lang('Rename').'/'.get_lang('Comments'),'',ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

// echo '<div id="odf"></div>';
echo '<div id="editorContainer" style="width:100%; height:600px; margin:0px; padding:0px"></div>';
Display::display_footer();
