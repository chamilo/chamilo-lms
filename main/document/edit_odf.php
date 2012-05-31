<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';
exit;
$document_id = $_GET['id'];

if ($document_id) {
    $document_data = DocumentManager::get_document_data_by_id($document_id);
    if (empty($document_data)) {
        api_not_allowed();    
    }
} else {
    api_not_allowed();
}

//Check user visibility
//$is_visible = DocumentManager::is_visible_by_id($document_id, $course_info, api_get_session_id(), api_get_user_id());
$is_visible = DocumentManager::check_visibility_tree($document_id, api_get_course_id(), api_get_session_id(), api_get_user_id());

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

$file_url_web = api_get_path(WEB_COURSE_PATH).$_course['path'].'/document'.$header_file.'?'.api_get_cidreq();

if ($show_web_odf) {
    $htmlHeadXtra[] = api_get_js('webodf/webodf.js');
    $htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/webodf/webodf.css');
    $htmlHeadXtra[] = '
    <script type="text/javascript" charset="utf-8">        
        function init() {
                var odfelement = document.getElementById("odf"),
                odfcanvas = new odf.OdfCanvas(odfelement);
                odfcanvas.load("'.$file_url_web.'");
        }
        $(document).ready(function() {        
            window.setTimeout(init, 0);
        });        
  </script>';  
}

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
Display::display_header('');
echo '<div id="odf"></div>';
Display::display_footer();