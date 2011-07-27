<?php
/* For licensing terms, see /license.txt */
/**
 * Homepage script for the documents tool
 *
 * This script allows the user to manage files and directories on a remote http
 * server.
 * The user can : - navigate through files and directories.
 *				 - upload a file
 *				 - delete, copy a file or a directory
 *				 - edit properties & content (name, comments, html content)
 * The script is organised in four sections.
 *
 * 1) Execute the command called by the user
 *				Note: somme commands of this section are organised in two steps.
 *			    The script always begins with the second step,
 *			    so it allows to return more easily to the first step.
 *
 *				Note (March 2004) some editing functions (renaming, commenting)
 *				are moved to a separate page, edit_document.php. This is also
 *				where xml and other stuff should be added.
 * 2) Define the directory to display
 * 3) Read files and directories from the directory defined in part 2
 * 4) Display all of that on an HTML page
 *
 * @todo eliminate code duplication with document/document.php, scormdocument.php
 *
 * @package chamilo.document
 */

/**
 * Code
 */
/*	INIT SECTION */

// Language files that need to be included
$language_file = array('document', 'slideshow', 'gradebook', 'create_course');

require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;

require_once 'document.inc.php';
$lib_path = api_get_path(LIBRARY_PATH);

/* Libraries */
require_once $lib_path.'usermanager.lib.php';
require_once $lib_path.'document.lib.php';
require_once $lib_path.'fileUpload.lib.php';
require_once $lib_path.'sortabletable.class.php';
require_once $lib_path.'formvalidator/FormValidator.class.php';
require_once $lib_path.'fileDisplay.lib.php';
require_once $lib_path.'tablesort.lib.php';

api_protect_course_script(true);

//Removing sessions
unset($_SESSION['draw_dir']);
unset($_SESSION['paint_dir']);

// Create directory certificates
DocumentManager::create_directory_certificate_in_course(api_get_course_id());

//Hack in order to use document.php?id=X 
if (isset($_REQUEST['id'])) {
    $document_data = DocumentManager::get_document_data_by_id($_REQUEST['id'], api_get_course_id());
    //@todo replace all 
    $_GET['curdirpath'] = $document_data['path'];    
}

// What's the current path?
// We will verify this a bit further down
if (isset($_GET['curdirpath']) && $_GET['curdirpath'] != '') {
    $curdirpath = Security::remove_XSS($_GET['curdirpath']);
} elseif (isset($_POST['curdirpath']) && $_POST['curdirpath'] != '') {
    $curdirpath = Security::remove_XSS($_POST['curdirpath']);
} else {
    $curdirpath = '/';
}

$curdirpathurl = urlencode($curdirpath);

// Check the path
// If the path is not found (no document id), set the path to /
$document_id = DocumentManager::get_document_id($_course, $curdirpath);

if (!$document_id) {    
    $document_id = DocumentManager::get_document_id(api_get_course_info(), $curdirpath);
}

$document_data = DocumentManager::get_document_data_by_id($document_id, api_get_course_id());
$parent_id     = DocumentManager::get_document_id(api_get_course_info(), dirname($document_data['path']));

if (!$parent_id) {
    $parent_id = 0; 
}    

$current_folder_id = $document_id;


// Show preview
if (isset($_GET['curdirpath']) && $_GET['curdirpath'] == '/certificates' && isset($_GET['set_preview']) && $_GET['set_preview'] == strval(intval($_GET['set_preview']))) {
    if (isset($_GET['set_preview'])) {
        // Generate document HTML        
        $content_html = DocumentManager::replace_user_info_into_html(api_get_user_id(), api_get_course_id(), true);

        $new_content_html = $content_html;

        $path_image = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/images/gallery';
        $new_content_html = str_replace('../images/gallery', $path_image, $new_content_html);

        $path_image_in_default_course = api_get_path(WEB_CODE_PATH).'default_course_document';
        $new_content_html = str_replace('/main/default_course_document', $path_image_in_default_course, $new_content_html);

        $new_content_html = str_replace('/main/img/', api_get_path(WEB_IMG_PATH), $new_content_html);
        echo '<style media="print" type="text/css"> #print_div { visibility:hidden; } </style>';
        echo '<a href="javascript:window.print();" style="float:right; padding:4px;" id="print_div"><img src="../img/printmgr.gif" alt="' . get_lang('Print') . '" /> ' . get_lang('Print') . '</a>';
        print_r($new_content_html);
        exit;
    }
}


// Is the document tool visible?
// Check whether the tool is actually visible
$table_course_tool  = Database::get_course_table(TABLE_TOOL_LIST, $_course['dbName']);
$tool_sql           = 'SELECT visibility FROM ' . $table_course_tool . ' WHERE name = "'. TOOL_DOCUMENT .'" LIMIT 1';
$tool_result        = Database::query($tool_sql);
$tool_row           = Database::fetch_array($tool_result);
$tool_visibility    = $tool_row['visibility'];
if ($tool_visibility == '0' && $to_group_id == '0' && !($is_allowed_to_edit || $group_member_with_upload_rights)) {
    api_not_allowed(true);
}

$htmlHeadXtra[] =
"<script type=\"text/javascript\">
function confirmation (name) {
    if (confirm(\" ". get_lang("AreYouSureToDelete") ." \"+ name + \" ?\"))
        {return true;}
    else
        {return false;}
}
</script>";

// I'm in the certification module?
$is_certificate_mode = DocumentManager::is_certificate_mode($curdirpath);

$course_dir      = $_course['path'].'/document';
$sys_course_path = api_get_path(SYS_COURSE_PATH);
$base_work_dir   = $sys_course_path.$course_dir;
$http_www        = api_get_path(WEB_COURSE_PATH).$_course['path'].'/document';
$dbl_click_id    = 0; // Used for avoiding double-click

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
$group_member_with_upload_rights = false;

// If the group id is set, we show them group documents
$group_properties = array();
$group_properties['directory'] = null;

if (api_get_group_id()) {
    // Needed for group related stuff
    require_once $lib_path.'groupmanager.lib.php';
    // Get group info
    $group_properties = GroupManager::get_group_properties(api_get_group_id());    
    $noPHP_SELF = true;
    // Let's assume the user cannot upload files for the group
    $group_member_with_upload_rights = false;

    if ($group_properties['doc_state'] == 2) { // Documents are private
        if ($is_allowed_to_edit || GroupManager :: is_user_in_group(api_get_user_id(), api_get_group_id())) { // Only courseadmin or group members (members + tutors) allowed
            $to_group_id = api_get_group_id();
            $req_gid = '&amp;gidReq='.api_get_group_id();
            $interbreadcrumb[] = array('url' => '../group/group.php', 'name' => get_lang('Groups'));
            $interbreadcrumb[] = array('url' => '../group/group_space.php?gidReq='.api_get_group_id(), 'name' => get_lang('GroupSpace').' '.$group_properties['name']);
            //they are allowed to upload
            $group_member_with_upload_rights = true;
        } else {
            $to_group_id = 0;
            $req_gid = '';
        }
    } elseif ($group_properties['doc_state'] == 1) {  // Documents are public
        $to_group_id = api_get_group_id();
        $req_gid = '&amp;gidReq='.api_get_group_id();
        $interbreadcrumb[] = array('url' => '../group/group.php', 'name' => get_lang('Groups'));
        $interbreadcrumb[] = array('url' => '../group/group_space.php?gidReq='.api_get_group_id(), 'name' => get_lang('GroupSpace').' '.$group_properties['name']);
        //allowed to upload?
        if ($is_allowed_to_edit || GroupManager::is_subscribed(api_get_user_id(), api_get_group_id())) { // Only courseadmin or group members can upload
            $group_member_with_upload_rights = true;
        }
    } else { // Documents not active for this group
        $to_group_id = 0;
        $req_gid = '';
    }
    $_SESSION['group_member_with_upload_rights'] = $group_member_with_upload_rights;
} else {
    $_SESSION['group_member_with_upload_rights'] = false;
    $to_group_id = 0;
    $req_gid = '';
}

// For sessions we should check the parameters of visibility
if (api_get_session_id() != 0) {
    $group_member_with_upload_rights = $group_member_with_upload_rights && api_is_allowed_to_session_edit(false, true);
}

// If they are looking at group documents they can't see the root
if ($to_group_id != 0 && $curdirpath == '/') {
    $curdirpath = $group_properties['directory'];
    $curdirpathurl = urlencode($group_properties['directory']);
}

// Check visibility of the current dir path. Don't show anything if not allowed
//@todo check this validation for coaches
//if (!$is_allowed_to_edit || api_is_coach()) { before

if (!$is_allowed_to_edit && api_is_coach()) {
    if ($curdirpath != '/' && !(DocumentManager::is_visible($curdirpath, $_course, api_get_session_id(),'folder'))) {   
        api_not_allowed(true);
    }
}

/*	Constants and variables */
$current_session_id = api_get_session_id();

/*	Create shared folders */
if ($current_session_id == 0) {
    //Create shared folder. Necessary for courses recycled. Allways session_id should be zero. Allway should be created from a base course, never from a session.
    if (!file_exists($base_work_dir.'/shared_folder')) {
        $usf_dir_title = get_lang('UserFolders');
        $usf_dir_name = '/shared_folder';
        $to_group_id = 0;
        $visibility = 0;
        create_unexisting_directory($_course, api_get_user_id(), api_get_session_id(), $to_group_id, $to_user_id, $base_work_dir, $usf_dir_name, $usf_dir_title, $visibility);
    }
    // Create dynamic user shared folder
    if (!file_exists($base_work_dir.'/shared_folder/sf_user_'.api_get_user_id())) {
            $usf_dir_title = api_get_person_name($_user['firstName'], $_user['lastName']);
            $usf_dir_name = '/shared_folder/sf_user_'.api_get_user_id();
            $to_group_id = 0;
            $visibility = 1;
            create_unexisting_directory($_course, api_get_user_id(), api_get_session_id(), $to_group_id, $to_user_id, $base_work_dir, $usf_dir_name, $usf_dir_title, $visibility);
    }
} else {
    //Create shared folder session
    if (!file_exists($base_work_dir.'/shared_folder_session_'.$current_session_id)) {
        $usf_dir_title = get_lang('UserFolders').' ('.api_get_session_name($current_session_id).')';
        $usf_dir_name = '/shared_folder_session_'.$current_session_id;
        $to_group_id = 0;
        $visibility = 0;
        create_unexisting_directory($_course, api_get_user_id(), api_get_session_id(), $to_group_id, $to_user_id, $base_work_dir, $usf_dir_name, $usf_dir_title, $visibility);
    }
    //Create dynamic user shared folder into a shared folder session
    if (!file_exists($base_work_dir.'/shared_folder_session_'.$current_session_id.'/sf_user_'.api_get_user_id())) {
        $usf_dir_title = api_get_person_name($_user['firstName'], $_user['lastName']).' ('.api_get_session_name($current_session_id).')';
        $usf_dir_name = '/shared_folder_session_'.$current_session_id.'/sf_user_'.api_get_user_id();
        $to_group_id = 0;
        $visibility = 1;
        create_unexisting_directory($_course, api_get_user_id(), api_get_session_id(), $to_group_id, $to_user_id, $base_work_dir, $usf_dir_name, $usf_dir_title, $visibility);
    }
}

/*	MAIN SECTION */

if (isset($_GET['action']) && $_GET['action'] == 'download') {
    $my_get_id = intval($_GET['id']);
    $document_data = DocumentManager::get_document_data_by_id($my_get_id, api_get_course_id());
    // Check whether the document is in the database
    if (empty($document_data)) {
        // File not found!
        header('HTTP/1.0 404 Not Found');
        $error404 = '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">';
        $error404 .= '<html><head>';
        $error404 .= '<title>404 Not Found</title>';
        $error404 .= '</head><body>';
        $error404 .= '<h1>Not Found</h1>';
        $error404 .= '<p>The requested URL was not found on this server.</p>';
        $error404 .= '<hr>';
        $error404 .= '</body></html>';
        echo $error404;
        exit;
    }
    // Launch event
    event_download($document_data['url']);
    // Check visibility of document and paths
    if (!($is_allowed_to_edit || $group_member_with_upload_rights) && !DocumentManager::is_visible_by_id($my_get_id, $_course, api_get_session_id(), 'file' )) {
        api_not_allowed(true);
    }
    $full_file_name = $base_work_dir.$document_data['path'];
    if (Security::check_abs_path($full_file_name, $base_work_dir.'/')) {
        DocumentManager::file_send_for_download($full_file_name, true);
    }
    exit;
}


// Download a folder
if (isset($_GET['action']) && $_GET['action'] == 'downloadfolder' && (api_get_setting('students_download_folders') == 'true' || api_is_allowed_to_edit() || api_is_platform_admin())) {
    //filter when I am into shared folder, I can donwload only my shared folder
    if(is_any_user_shared_folder($_GET['path'],$current_session_id)){
        if(is_my_shared_folder(api_get_user_id(), $_GET['path'], $current_session_id) || api_is_allowed_to_edit() || api_is_platform_admin()){
            require 'downloadfolder.inc.php';
        }
    } else {
        require 'downloadfolder.inc.php';
    }
}

// Export to PDF
if (isset($_GET['action']) && $_GET['action'] == 'export_to_pdf' && (api_get_setting('students_export2pdf') == 'true' || api_is_allowed_to_edit() || api_is_platform_admin())) {
    DocumentManager::export_to_pdf($_GET['id'],$course_code);
}

// Slideshow inititalisation
$_SESSION['image_files_only'] = '';
$image_files_only = '';

/*	Header */

if ($is_certificate_mode) {
    $interbreadcrumb[]= array('url' => '../gradebook/index.php', 'name' => get_lang('Gradebook'));
} else {
    if ((isset($_GET['id']) && $_GET['id'] != 0) || isset($_GET['curdirpath']) || isset($_GET['createdir'])) {
        $interbreadcrumb[]= array('url' => 'document.php', 'name' => get_lang('Documents'));
    } else {
        $interbreadcrumb[]= array('url' => '#', 'name' => get_lang('Documents'));
    }
}

// Interbreadcrumb for the current directory root path

$dir_array = explode('/', $curdirpath);
$array_len = count($dir_array);

$dir_acum = '';

for ($i = 0; $i < $array_len; $i++) {
    $url_dir = 'document.php?&amp;curdirpath='.$dir_acum.$dir_array[$i];
    //Max char 80
    $url_to_who = cut($dir_array[$i],80);
    if ($is_certificate_mode) {
        $interbreadcrumb[] = array('url' => $url_dir.'&amp;selectcat='.Security::remove_XSS($_GET['selectcat']), 'name' => $url_to_who);
    } else {
        if( $i == $array_len - 1 && !isset($_GET['createdir']) ) {
            $url_dir = '#';
        }
        if ($url_to_who == 'learning_path') {
            $url_to_who = get_lang('LearningPaths');
        }
        $interbreadcrumb[] = array('url' => $url_dir, 'name' => $url_to_who);
    }
    //does not repeat the name group in the url
    if (!empty($_SESSION['_gid'])) {
        unset($dir_array[1]);
    }
    $dir_acum .= $dir_array[$i].'/';
}

if (isset($_GET['createdir'])) {
    $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('CreateDir'));
}

$htmlHeadXtra[] = api_get_jquery_js();
$htmlHeadXtra[] = api_get_jquery_ui_js();

$htmlHeadXtra[] = api_get_js('yoxview/yoxview-init.js');
$js_path = api_get_path(WEB_LIBRARY_PATH).'javascript/';   
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.$js_path.'yoxview/yoxview.css" type="text/css">';
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.$js_path.'jquery-jplayer/skins/chamilo/jplayer.blue.monday.css" type="text/css">';
$htmlHeadXtra[] = '<script type="text/javascript" src="'.$js_path.'jquery-jplayer/jquery.jplayer.min.js"></script>';

$mediaplayer_path = api_get_path(WEB_LIBRARY_PATH).'mediaplayer/player.swf';

//automatic loading the course language for yoxview
$yoxview_code_translation_table = array('' => 'en', 'pt' => 'pt-Pt', 'sr' => 'sr_latn');
$lang_yoxview  = api_get_language_isocode();
$lang_yoxview = isset($yoxview_code_translation_table[$lang_yoxview]) ? $yoxview_code_translation_table[$lang_yoxview] : $lang_yoxview;

$docs_and_folders = DocumentManager::get_all_document_data($_course, $curdirpath, $to_group_id, null, $is_allowed_to_edit || $group_member_with_upload_rights, false);

$file_list = $format_list = '';
$count = 1;

if (!empty($docs_and_folders))
foreach ($docs_and_folders  as $file) {    
    if ($file['filetype'] == 'file') {
        $path_info = pathinfo($file['path']);  
        $extension = strtolower($path_info['extension']);
        //@todo use a js loop to autogenerate this code
        if (in_array($extension, array('ogg', 'mp3', 'wav'))) {
            $document_data = DocumentManager::get_document_data_by_id($file['id'], api_get_course_id());
            
            if ($extension == 'ogg') {
                $extension = 'oga';
            }            
            $jquery .= ' $("#jquery_jplayer_'.$count.'").jPlayer({                                
                                ready: function() {                    
                                    $(this).jPlayer("setMedia", {                                        
                                        '.$extension.' : "'.$document_data['direct_url'].'"                                                                                  
                                    });
                                },                                
                                swfPath: "'.$js_path.'jquery-jplayer",
                                supplied: "mp3, m4a, oga, ogv, wav",          
                                solution: "flash, html",  // Do not change this setting otherwise 
                                cssSelectorAncestor: "#jp_interface_'.$count.'", 
                            });'."\n\n";
            $count++;      
        }        
    }
}
 
$htmlHeadXtra[] = '<script type="text/javascript">
$(document).ready( function() {  
	  
    $(".yoxview").yoxview({
        lang: "'.$lang_yoxview.'",
        flashVideoPlayerPath: "'.$mediaplayer_path.'",
        allowInternalLinks:true,
        defaultDimensions: { iframe: { width: 800}},
        titleAttribute : "alt"        
    });
        
    //Experimental changes to preview mp3, ogg files        
     '.$jquery.'              
    //Keep this down otherwise the jquery player will not work
    for (i=0;i<$(".actions").length;i++) {
        if ($(".actions:eq("+i+")").html()=="<table border=\"0\"></table>" || $(".actions:eq("+i+")").html()=="" || $(".actions:eq("+i+")").html()==null) {
            $(".actions:eq("+i+")").hide();
        }
    }
});
</script>';

Display::display_header('','Doc');

// Lib for event log, stats & tracking & record of the access
event_access_tool(TOOL_DOCUMENT);

/*	DISPLAY */
if ($to_group_id != 0) { // Add group name after for group documents
    $add_group_to_title = ' ('.$group_properties['name'].')';
}

/* Introduction section (editable by course admins) */

if (!empty($_SESSION['_gid'])) {
    Display::display_introduction_section(TOOL_DOCUMENT.$_SESSION['_gid']);
} else {
    Display::display_introduction_section(TOOL_DOCUMENT);
}

// ACTION MENU

// Copy a file to general my files user's
if (isset($_GET['action']) && $_GET['action'] == 'copytomyfiles' && api_get_setting('users_copy_files') == 'true' && api_get_user_id() != 0) {

    $clean_get_id = Security::remove_XSS($_GET['id']);
    $my_path = UserManager::get_user_picture_path_by_id(api_get_user_id(),'system');
    $user_folder  = $my_path['dir'].'my_files/';
    $my_path = null;
    if (!file_exists($user_folder)) {
        $perm = api_get_permissions_for_new_directories();
        @mkdir($user_folder, $perm, true);
    }

    $file = $sys_course_path.$_course['path'].'/document'.$clean_get_id;
    $copyfile = $user_folder.basename($clean_get_id);

    if (file_exists($copyfile)) {
        $message = get_lang('CopyAlreadyDone').'</p><p>'.'<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;curdirpath='.$curdirpath.'">'.get_lang("No").'</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;curdirpath='.$curdirpath.'&amp;action=copytomyfiles&amp;id='.$clean_get_id.'&amp;copy=yes">'.get_lang('Yes').'</a></p>';
        if (!isset($_GET['copy'])){
            Display::display_warning_message($message,false);
        }
        if (Security::remove_XSS($_GET['copy']) == 'yes'){
            if (!copy($file, $copyfile)) {
                Display::display_error_message(get_lang('CopyFailed'));
            } else {
                Display::display_confirmation_message(get_lang('OverwritenFile'));
            }
        }
    } else {
        if (!copy($file, $copyfile)) {
            Display::display_error_message(get_lang('CopyFailed'));
        } else {
            Display::display_confirmation_message(get_lang('CopyMade'));
        }
    }
}

/*	MOVE FILE OR DIRECTORY */
//Only teacher and all users into their group and each user into his/her shared folder
if ($is_allowed_to_edit || $group_member_with_upload_rights || is_my_shared_folder(api_get_user_id(), $curdirpath, $current_session_id) || is_my_shared_folder(api_get_user_id(), Security::remove_XSS($_POST['move_to']), $current_session_id)) {
    
    if (isset($_GET['move']) && $_GET['move'] != '') {
        $my_get_move = intval($_REQUEST['move']);

        if (api_is_coach()) {
            if (!DocumentManager::is_visible_by_id($my_get_move, api_get_course_info(), api_get_session_id())) {                    
                api_not_allowed();
            }
        }

        if (!$is_allowed_to_edit) {
            if (DocumentManager::check_readonly($_course, api_get_user_id(), $my_get_move)) {                
                api_not_allowed();
            }
        }
        $document_to_move = DocumentManager::get_document_data_by_id($my_get_move, api_get_course_id());
        $move_path = $document_to_move['path']; 
        
        if (!empty($document_to_move)) {
            $folders = DocumentManager::get_all_document_folders($_course, $to_group_id, $is_allowed_to_edit || $group_member_with_upload_rights);

            //filter if is my shared folder. TODO: move this code to build_move_to_selector function
            if (is_my_shared_folder(api_get_user_id(), $curdirpath, $current_session_id) && !$is_allowed_to_edit){
                $main_user_shared_folder_main = '/shared_folder/sf_user_'.api_get_user_id();//only main user shared folder
                $main_user_shared_folder_sub  = '/shared_folder\/sf_user_'.api_get_user_id().'\//';//all subfolders
                $user_shared_folders=array();

                foreach($folders as $fold){
                    if($main_user_shared_folder_main==$fold || preg_match($main_user_shared_folder_sub, $fold)){
                        $user_shared_folders[]=$fold;
                    }
                }
                echo '<div class="row"><div class="form_header">'.get_lang('Move').'</div></div>';                    
                echo build_move_to_selector($user_shared_folders, $move_path, $my_get_move, $group_properties['directory']);
            } else {
                                    
                echo '<div class="row"><div class="form_header">'.get_lang('Move').'</div></div>';
                echo build_move_to_selector($folders, $move_path, $my_get_move, $group_properties['directory']);
            }
        }
    }

    if (isset($_POST['move_to']) && isset($_POST['move_file'])) {
        
        if (!$is_allowed_to_edit) {
            if (DocumentManager::check_readonly($_course, api_get_user_id(), $_POST['move_file'])) {                    
                api_not_allowed();
            }
        }

        if (api_is_coach()) {               
            if (!DocumentManager::is_visible_by_id($_POST['move_file'], $_course, api_get_session_id())) {                    
                api_not_allowed();
            }
        }
        $document_to_move = DocumentManager::get_document_data_by_id($_POST['move_file'], api_get_course_id());            
        require_once $lib_path.'fileManage.lib.php';
        // This is needed for the update_db_info function
        //$dbTable = $_course['dbNameGlu'].'document';
        $dbTable = Database::get_course_table(TABLE_DOCUMENT);
        // Security fix: make sure they can't move files that are not in the document table
        if (!empty($document_to_move)) {
			
			$real_path_target = $base_work_dir.$_POST['move_to'].'/'.basename($document_to_move['path']);
			$fileExist=false;
			if(file_exists($real_path_target)){
				$fileExist=true;
			}			
			
            if (move($base_work_dir.$document_to_move['path'], $base_work_dir.$_POST['move_to'])) {
            //if (1) {
            //$contents = DocumentManager::replace_urls_inside_content_html_when_moving_file(basename($document_to_move['path']), $base_work_dir.dirname($document_to_move['path']), $base_work_dir.$_POST['move_to']);
            //exit;
                update_db_info('update', $document_to_move['path'], $_POST['move_to'].'/'.basename($document_to_move['path']));
               
			   //update database item property
			   $doc_id=$_POST['move_file'];
			   $current_session_id = api_get_session_id();			   

			   if(is_dir($real_path_target)){
					api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'FolderMoved', api_get_user_id(),$to_group_id,null,null,null,$current_session_id);
					Display::display_confirmation_message(get_lang('DirMv'));
			   }
			   elseif(is_file($real_path_target)){
				   api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentMoved', api_get_user_id(),$to_group_id,null,null,null,$current_session_id);
				   Display::display_confirmation_message(get_lang('DocMv'));
			   }		   

				// Set the current path
                $curdirpath = $_POST['move_to'];
                $curdirpathurl = urlencode($_POST['move_to']);
                
            } else {
				if($fileExist){
					if(is_dir($real_path_target)){
						Display::display_error_message(get_lang('DirExists'));
					}
					elseif(is_file($real_path_target)){
                		Display::display_error_message(get_lang('FileExists'));
					}
				}
				else{
					Display::display_error_message(get_lang('Impossible'));
				}
            }
        } else {
            Display::display_error_message(get_lang('Impossible'));
        }
    }
}

/*	DELETE FILE OR DIRECTORY */
//Only teacher and all users into their group
if($is_allowed_to_edit || $group_member_with_upload_rights || is_my_shared_folder(api_get_user_id(), $curdirpath, $current_session_id)){
    if (isset($_GET['delete'])) {
        if (!$is_allowed_to_edit) {
            if (api_is_coach()) {                
                if (!DocumentManager::is_visible($_GET['delete'], $_course, api_get_session_id())) {                    
                    api_not_allowed();
                }
            }
            
            if (DocumentManager::check_readonly($_course, api_get_user_id(), $_GET['delete'], '', true)) {
                api_not_allowed();
            }
        }
        require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
        if (DocumentManager::delete_document($_course, $_GET['delete'], $base_work_dir)) {
            if ( isset($_GET['delete_certificate_id']) && $_GET['delete_certificate_id'] == strval(intval($_GET['delete_certificate_id']))) {                    
                $default_certificate_id = $_GET['delete_certificate_id'];
                DocumentManager::remove_attach_certificate(api_get_course_id(), $default_certificate_id);
            }
            Display::display_confirmation_message(get_lang('DocDeleted'));
        } else {
            Display::display_error_message(get_lang('DocDeleteError'));
        }
    }

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete':
                foreach ($_POST['path'] as $index => & $path) {
                    if (!$is_allowed_to_edit) {
                        if (DocumentManager::check_readonly($_course, api_get_user_id(), $path)) {
                            Display::display_error_message(get_lang('CantDeleteReadonlyFiles'));
                            break 2;
                        }
                    }
                }

                foreach ($_POST['path'] as $index => & $path) {
                    if (in_array($path, array('/audio', '/flash', '/images', '/shared_folder', '/video', '/chat_files', '/certificates'))) {
                        continue;
                    } else {
                        $delete_document = DocumentManager::delete_document($_course, $path, $base_work_dir);
                    }
                }
                if (!empty($delete_document)) {
                    Display::display_confirmation_message(get_lang('DocDeleted'));
                }
                break;
        }
    }
}

/*	CREATE DIRECTORY */
//Only teacher and all users into their group and any user into his/her shared folder
if ($is_allowed_to_edit || $group_member_with_upload_rights || is_my_shared_folder(api_get_user_id(), $curdirpath, $current_session_id)) {
    // Create directory with $_POST data
    if (isset($_POST['create_dir']) && $_POST['dirname'] != '') {
        // Needed for directory creation
        require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
        $post_dir_name = Security::remove_XSS($_POST['dirname']);

        if ($post_dir_name == '../' || $post_dir_name == '.' || $post_dir_name == '..') {
            Display::display_error_message(get_lang('CannotCreateDir'));
        } else {            
            if (!empty($_POST['dir_id'])) {
                $document_data = DocumentManager::get_document_data_by_id($_POST['dir_id'], api_get_course_id());
                $curdirpath = $document_data['path'];
            }            
            $added_slash = ($curdirpath == '/') ? '' : '/';                
            $dir_name = $curdirpath.$added_slash.replace_dangerous_char($post_dir_name);
            $dir_name = disable_dangerous_file($dir_name);
            $dir_name = str_replace('.', '_', $dir_name);
            $post_dir_name = str_replace('.', '_', $post_dir_name);
            
            $dir_check = $base_work_dir.$dir_name;
            
            if (!is_dir($dir_check)) {
                $created_dir = create_unexisting_directory($_course, api_get_user_id(), api_get_session_id(), $to_group_id, $to_user_id, $base_work_dir, $dir_name, $post_dir_name);
                if ($created_dir) {
                    Display::display_confirmation_message('<span title="'.$created_dir.'">'.get_lang('DirCr').'</span>', false);
                    // Uncomment if you want to enter the created dir
                    //$curdirpath = $created_dir;
                    //$curdirpathurl = urlencode($curdirpath);
                } else {
                    Display::display_error_message(get_lang('CannotCreateDir'));
                }
            } else {
                Display::display_error_message(get_lang('CannotCreateDir'));
            }
        }
    }

    // Show them the form for the directory name
    if (isset($_GET['createdir'])) {
        echo create_dir_form($document_id);
    }
}

/*	VISIBILITY COMMANDS */
//Only teacher
if ($is_allowed_to_edit) {
    if ((isset($_GET['set_invisible']) && !empty($_GET['set_invisible'])) || (isset($_GET['set_visible']) && !empty($_GET['set_visible'])) && $_GET['set_visible'] != '*' && $_GET['set_invisible'] != '*') {
        // Make visible or invisible?
        if (isset($_GET['set_visible'])) {
            $update_id = $_GET['set_visible'];
            $visibility_command = 'visible';
        } else {
            $update_id = $_GET['set_invisible'];
            $visibility_command = 'invisible';
        }
        
        if (!$is_allowed_to_edit) {                
            if (api_is_coach()) {                
                if (!DocumentManager::is_visible_by_id($update_id, $_course, api_get_session_id())) {                    
                    api_not_allowed();
                }
            }                
            if (DocumentManager::check_readonly($_course, api_get_user_id(), '', $update_id)) {
                api_not_allowed();
            }
        }

        // Update item_property to change visibility
        if (api_item_property_update($_course, TOOL_DOCUMENT, $update_id, $visibility_command, api_get_user_id(), null, null, null, null, $current_session_id)) {
            Display::display_confirmation_message(get_lang('VisibilityChanged'));//don't use ViMod because firt is load ViMdod (Gradebook). VisibilityChanged (trad4all)
        } else {
            Display::display_error_message(get_lang('ViModProb'));
        }
    }
}

/*	TEMPLATE ACTION */
//Only teacher and all users into their group
if ($is_allowed_to_edit || $group_member_with_upload_rights || is_my_shared_folder(api_get_user_id(), $curdirpath, $current_session_id)){
    if (isset($_GET['add_as_template']) && !isset($_POST['create_template'])) {

        $document_id_for_template = intval($_GET['add_as_template']);

        // Create the form that asks for the directory name
        $template_text = '<form name="set_document_as_new_template" enctype="multipart/form-data" action="'.api_get_self().'?add_as_template='.$document_id_for_template.'" method="post">';
        $template_text .= '<input type="hidden" name="curdirpath" value="'.$curdirpath.'" />';
        $template_text .= '<table><tr><td>';
        $template_text .= get_lang('TemplateName').' : </td>';
        $template_text .= '<td><input type="text" name="template_title" /></td></tr>';
        //$template_text .= '<tr><td>'.get_lang('TemplateDescription').' : </td>';
        //$template_text .= '<td><textarea name="template_description"></textarea></td></tr>';
        $template_text .= '<tr><td>'.get_lang('TemplateImage').' : </td>';
        $template_text .= '<td><input type="file" name="template_image" id="template_image" /></td></tr>';
        $template_text .= '</table>';
        $template_text .= '<button type="submit" class="add" name="create_template">'.get_lang('CreateTemplate').'</button>';
        $template_text .= '</form>';
        // Show the form
        Display::display_normal_message($template_text, false);

    } elseif (isset($_GET['add_as_template']) && isset($_POST['create_template'])) {

        $document_id_for_template = intval(Database::escape_string($_GET['add_as_template']));

        $title = Security::remove_XSS($_POST['template_title']);
        //$description = Security::remove_XSS($_POST['template_description']);
        $course_code = api_get_course_id();
        $user_id = api_get_user_id();

        // Create the template_thumbnails folder in the upload folder (if needed)
        if (!is_dir(api_get_path(SYS_PATH).'courses/'.$_course['path'].'/upload/template_thumbnails/')) {
            @mkdir(api_get_path(SYS_PATH).'courses/'.$_course['path'].'/upload/template_thumbnails/', api_get_permissions_for_new_directories());
        }
        // Upload the file
        if (!empty($_FILES['template_image']['name'])) {

            require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
            $upload_ok = process_uploaded_file($_FILES['template_image']);

            if ($upload_ok) {
                // Try to add an extension to the file if it hasn't one
                $new_file_name = $_course['sysCode'].'-'.add_ext_on_mime(stripslashes($_FILES['template_image']['name']), $_FILES['template_image']['type']);

                // Upload dir
                $upload_dir = api_get_path(SYS_PATH).'courses/'.$_course['path'].'/upload/template_thumbnails/';

                // Resize image to max default and end upload
                $temp = new Image($_FILES['template_image']['tmp_name']);
                $picture_info = $temp->get_image_info();

                $max_width_for_picture = 100;

                if ($picture_info['width'] > $max_width_for_picture) {
                    $thumbwidth = $max_width_for_picture;
                    if (empty($thumbwidth) || $thumbwidth == 0) {
                        $thumbwidth = $max_width_for_picture;
                    }
                    $new_height = round(($thumbwidth/$picture_info['width'])*$picture_info['height']);
                    $temp->resize($thumbwidth, $new_height, 0);
                }
                $temp->send_image($upload_dir.$new_file_name);
            }
        }

        DocumentManager::set_document_as_template($title, $description, $document_id_for_template, $course_code, $user_id, $new_file_name);
        Display::display_confirmation_message(get_lang('DocumentSetAsTemplate'));
    }

    if (isset($_GET['remove_as_template'])) {
        $document_id_for_template = intval($_GET['remove_as_template']);
        $course_code = api_get_course_id();
        $user_id = api_get_user_id();
        DocumentManager::unset_document_as_template($document_id_for_template, $course_code, $user_id);
        Display::display_confirmation_message(get_lang('DocumentUnsetAsTemplate'));
    }
}

// END ACTION MENU

// Attach certificate in the gradebook
if (isset($_GET['curdirpath']) && $_GET['curdirpath'] == '/certificates' && isset($_GET['set_certificate']) && $_GET['set_certificate'] == strval(intval($_GET['set_certificate']))) {
    if (isset($_GET['cidReq'])) {
        $course_id      = Security::remove_XSS($_GET['cidReq']); // course id
        $document_id    = Security::remove_XSS($_GET['set_certificate']); // document id
        DocumentManager::attach_gradebook_certificate ($course_id,$document_id);
        Display::display_normal_message(get_lang('IsDefaultCertificate'));
    }
}

/*	GET ALL DOCUMENT DATA FOR CURDIRPATH */
if (isset($_GET['keyword']) && !empty($_GET['keyword'])) {
    $docs_and_folders = DocumentManager::get_all_document_data($_course, $curdirpath, $to_group_id, null, $is_allowed_to_edit || $group_member_with_upload_rights, true);    
} else {
    $docs_and_folders = DocumentManager::get_all_document_data($_course, $curdirpath, $to_group_id, null, $is_allowed_to_edit || $group_member_with_upload_rights, false);
}

$folders = DocumentManager::get_all_document_folders($_course, $to_group_id, $is_allowed_to_edit || $group_member_with_upload_rights);
if ($folders === false) {
    $folders = array();
}

echo '<div class="actions">';
if ($is_allowed_to_edit || $group_member_with_upload_rights){
/* BUILD SEARCH FORM */
    echo '<span style="display:inline-block;">';
    $form = new FormValidator('search_document', 'get', '', '', null, false);
    $renderer = & $form->defaultRenderer();
    $renderer->setElementTemplate('<span>{element}</span> ');
    $form->add_textfield('keyword', '', false);
    $form->addElement('style_submit_button', 'submit', get_lang('Search'), 'class="search"');
    $form->display();
    echo '</span>';
}

/* GO TO PARENT DIRECTORY */
if ($curdirpath!= '/' && $curdirpath != $group_properties['directory'] && !$is_certificate_mode) {
    echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&id='.$parent_id.'">';
    echo Display::display_icon('folder_up.png', get_lang('Up'),'','32');
    echo '</a>';
}

if ($is_certificate_mode && $curdirpath != '/certificates') {
?>
    <a href="<?php echo api_get_self(); ?>?<?php echo api_get_cidreq();?>&amp;curdirpath=<?php echo urlencode((dirname($curdirpath) == '\\') ? '/' : dirname($curdirpath)).$req_gid; ?>">
        <?php Display::display_icon('folder_up.png', get_lang('Up'),'','32'); ?></a>
<?php
}
$table_footer = '';
$total_size = 0;

if (isset($docs_and_folders) && is_array($docs_and_folders)) {

    // Do we need the title field for the document name or not?
    // We get the setting here, so we only have to do it once
    $use_document_title = api_get_setting('use_document_title');
    // Create a sortable table with our data
    $sortable_data = array();

    $count = 1;
    foreach ($docs_and_folders as $key => $document_data) {
        $row = array();        
        $row['id']   = $document_data['id'];
        $row['type'] = $document_data['filetype'];        

        // If the item is invisible, wrap it in a span with class invisible
        $invisibility_span_open  = ($document_data['visibility'] == 0) ? '<span class="invisible">' : '';
        $invisibility_span_close = ($document_data['visibility'] == 0) ? '</span>' : '';
        // Size (or total size of a directory)
        $size = $document_data['filetype'] == 'folder' ? get_total_folder_size($document_data['path'], $is_allowed_to_edit) : $document_data['size'];
        
        // Get the title or the basename depending on what we're using
        if ($use_document_title == 'true' && $document_data['title'] != '') {
            $document_name = $document_data['title'];
        } else {
            $document_name = basename($document_data['path']);
        }
        $row['name'] = $document_name;
        // Data for checkbox
        if (($is_allowed_to_edit || $group_member_with_upload_rights) && count($docs_and_folders) > 1) {
            $row[] = $document_data['path'];
        }

        // Hide HotPotatoes Certificates and all css folders
        if ($document_data['path']=='/HotPotatoes_files' || $document_data['path']=='/certificates' || basename($document_data['path'])=='css'){
            continue;
        }

        //Admin setting for Hide/Show the folders of all users
        if (api_get_setting('show_users_folders') == 'false' && ($document_data['path']=='/shared_folder' || strstr($document_data['path'], 'shared_folder_session_'))){
            continue;
        }

        //Admin setting for Hide/Show Default folders to all users
        if (api_get_setting('show_default_folders') == 'false' && ($document_data['path']=='/images' || $document_data['path']=='/flash' || $document_data['path']=='/audio' || $document_data['path']=='/video')){
            continue;
        }

        //Admin setting for Hide/Show chat history folder
        if (api_get_setting('show_chat_folder') == 'false' && $document_data['path']=='/chat_files'){
            continue;
        }

        // Show the owner of the file only in groups
        $user_link = '';

        if (isset($_SESSION['_gid']) && $_SESSION['_gid'] != '') {
            if (!empty($document_data['insert_user_id'])) {
                $user_info = UserManager::get_user_info_by_id($document_data['insert_user_id']);
                $user_name = api_get_person_name($user_info['firstname'], $user_info['lastname']);
                $user_link = '<div class="document_owner">'.get_lang('Owner').': '.display_user_link_document($document_data['insert_user_id'], $user_name).'</div>';
            }
        }

        // Icons (clickable)
        $row[] = create_document_link($document_data,  true, $count);
        $path_info = pathinfo($document_data['path']);
                
        if (isset($path_info['extension']) && in_array($path_info['extension'], array('ogg', 'mp3','wav'))) {
            $count ++;
        }

        // Validacion when belongs to a session
        $session_img = api_get_session_image($document_data['session_id'], $_user['status']);
                
        // Document title with hyperlink        
        $row[] = create_document_link($document_data).$session_img.'<br />'.$invisibility_span_open.'<i>'.nl2br(htmlspecialchars($document_data['comment'],ENT_QUOTES,$charset)).'</i>'.$invisibility_span_close.$user_link;
    
        // Comments => display comment under the document name
        $display_size = format_file_size($size);
        $row[] = '<span style="display:none;">'.$size.'</span>'.$invisibility_span_open.$display_size.$invisibility_span_close;

        // Last edit date
        $last_edit_date = $document_data['lastedit_date'];
        $last_edit_date = api_get_local_time($last_edit_date, null, date_default_timezone_get());
        //$display_date = date_to_str_ago($last_edit_date).'<br /><span class="dropbox_date">'.api_format_date($last_edit_date).'</span>';
        $display_date = date_to_str_ago($last_edit_date);
        $row[] = $invisibility_span_open.$display_date.$invisibility_span_close;
        // Admins get an edit column
        
        if ($is_allowed_to_edit || $group_member_with_upload_rights || is_my_shared_folder(api_get_user_id(), $curdirpath, $current_session_id)) {
            $is_template = isset($document_data['is_template']) ? $document_data['is_template'] : false;            
            // If readonly, check if it the owner of the file or if the user is an admin            
            if ($document_data['insert_user_id'] == api_get_user_id() || api_is_platform_admin()) {                
                $edit_icons = build_edit_icons($document_data, $key, $is_template, 0);
            } else {          
                $edit_icons = build_edit_icons($document_data, $key, $is_template, $document_data['readonly']);
            }
            $row[] = $edit_icons;
        }
        $row[] = $last_edit_date;
        $row[] = $size;
        $row[] = $document_name;
        $total_size = $total_size + $size;

        if ((isset($_GET['keyword']) && search_keyword($document_name, $_GET['keyword'])) || !isset($_GET['keyword']) || empty($_GET['keyword'])) {            
            $sortable_data[] = $row;
        }
    }
} else {
    $sortable_data = '';
    $table_footer = get_lang('NoDocsInFolder');
}

$column_show = array();

if ($is_allowed_to_edit || $group_member_with_upload_rights || is_my_shared_folder(api_get_user_id(), $curdirpath, $current_session_id)) {

    // TODO:check enable more options for shared folders
    /* CREATE NEW DOCUMENT OR NEW DIRECTORY / GO TO UPLOAD / DOWNLOAD ZIPPED FOLDER */

    // Create new document
    if (!$is_certificate_mode) {
        ?>
        <a href="create_document.php?<?php echo api_get_cidreq(); ?>&id=<?php echo $document_id.$req_gid; ?>">
            <?php Display::display_icon('new_document.png', get_lang('CreateDoc'),'','32'); ?></a>
        <?php

        // Create new draw
        if (api_get_setting('enabled_support_svg') == 'true') {
            if (api_browser_support('svg')) {
            ?>
                <a href="create_draw.php?<?php echo api_get_cidreq(); ?>&id=<?php echo $document_id.$req_gid; ?>">
            <?php Display::display_icon('new_draw.png', get_lang('Draw'),'','32'); ?></a>&nbsp;
            <?php
            } else {
                Display::display_icon('new_draw_na.png', get_lang('BrowserDontSupportsSVG'),'','32');
            }
        }
		
		// Create new paint
		if (api_get_setting('enabled_support_pixlr') == 'true'){
		?>
			<a href="create_paint.php?<?php echo api_get_cidreq(); ?>&id=<?php echo $document_id.$req_gid; ?>">
		   <?php Display::display_icon('new_paint.png', get_lang('PhotoRetouching'),'','32'); ?></a>
		<?php
		}		
		
		// Record new audio
		if (api_get_setting('enable_nanogong') == 'true') {
		?>
			<a href="record_audio.php?<?php echo api_get_cidreq(); ?>&id=<?php echo $document_id.$req_gid; ?>">
		   	<?php Display::display_icon('new_recording.png', get_lang('RecordMyVoice'),'',32); ?></a>
		<?php
		}		
		
		// Create new audio
		if (api_get_setting('enabled_text2audio') == 'true'){
		?>
			<a href="create_audio.php?<?php echo api_get_cidreq(); ?>&id=<?php echo $document_id.$req_gid; ?>">
		   <?php Display::display_icon('new_sound.png', get_lang('CreateAudio'),'','32'); ?></a>
		<?php
		}		
    }    

    // Create new certificate
    if ($is_certificate_mode) {
?>
    <a href="create_document.php?<?php echo api_get_cidreq(); ?>&id=<?php echo $document_id.$req_gid; ?>&certificate=true&<?php echo 'selectcat='.Security::remove_XSS($_GET['selectcat']); ?>">
        <?php Display::display_icon('new_certificate.png', get_lang('CreateCertificate'),'','32'); ?></a>
<?php
    }
    // File upload link
	if ($is_certificate_mode) {
        echo '<a href="upload.php?'.api_get_cidreq().'&id='.$current_folder_id.$req_gid.'">';
        echo Display::display_icon('upload_certificate.png', get_lang('UploadCertificate'),'','32').'</a>';
	} else {
        echo '<a href="upload.php?'.api_get_cidreq().'&id='.$current_folder_id.$req_gid.'">';
        echo Display::display_icon('upload_file.png', get_lang('UplUploadDocument'),'','32').'</a>';
	}
    // Create directory
    if (!$is_certificate_mode) {
    ?>
    <a href="<?php echo api_get_self(); ?>?<?php echo api_get_cidreq(); ?>&id=<?php echo $document_id.$req_gid; ?>&createdir=1">
        <?php Display::display_icon('new_folder.png', get_lang('CreateDir'),'','32'); ?></a>
<?php
    }
}

if (!is_null($docs_and_folders)) {

    // Show download zipped folder icon
    global $total_size;
    if (!$is_certificate_mode && $total_size != 0 && (api_get_setting('students_download_folders') == 'true' || api_is_allowed_to_edit() || api_is_platform_admin())) {

        //for student does not show icon into other shared folder, and does not show into main path (root)
        if (is_my_shared_folder(api_get_user_id(), $curdirpath, $current_session_id) && $curdirpath!='/' || api_is_allowed_to_edit() || api_is_platform_admin()) {
            echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;action=downloadfolder&amp;id='.$document_id.'">'.Display::return_icon('save_pack.png', get_lang('Save').' (ZIP)','','32').'</a>';
        }
    }
}

// Slideshow by Patrick Cool, May 2004
require 'document_slideshow.inc.php';
if ($image_present && !isset($_GET['keyword'])  ) {
    echo '<a href="slideshow.php?'.api_get_cidreq().'&amp;curdirpath='.$curdirpathurl.'">'.Display::return_icon('slideshow.png', get_lang('ViewSlideshow'),'','32').'</a>';
}
echo '</div>';

if (!$is_certificate_mode) {
    echo build_directory_selector($folders, $curdirpath, (isset($group_properties['directory']) ? $group_properties['directory'] : array()), true);
}

if (($is_allowed_to_edit || $group_member_with_upload_rights) && count($docs_and_folders) > 1) {
    $column_show[] = 1;
}

$column_show[] = 1;
$column_show[] = 1;
$column_show[] = 1;
$column_show[] = 1;

if ($is_allowed_to_edit || $group_member_with_upload_rights || is_my_shared_folder(api_get_user_id(), $curdirpath, $current_session_id)) {
    $column_show[] = 1;
}
$column_show[] = 0;
$column_show[] = 0;

$column_order = array();

if (count($row) == 12) {
    //teacher
    $column_order[2] = 8; //name
    $column_order[3] = 7;
    $column_order[4] = 6;
} elseif (count($row) == 10) {
    //student
    $column_order[1] = 6;
    $column_order[2] = 5;
    $column_order[3] = 4;
}

$default_column = $is_allowed_to_edit ? 2 : 1;
$tablename = $is_allowed_to_edit ? 'teacher_table' : 'student_table';

//var_dump($column_show,$column_order);
$table = new SortableTableFromArrayConfig($sortable_data, $default_column, 20, $tablename, $column_show, $column_order, 'ASC', true);

if(isset($_GET['keyword'])) {
    $query_vars['keyword'] = Security::remove_XSS($_GET['keyword']);
}else{
    $query_vars['curdirpath'] = $curdirpath;
}

if (api_get_group_id()) {
    $query_vars['gidReq'] = api_get_group_id();
}
$query_vars['cidReq'] = api_get_course_id();
$table->set_additional_parameters($query_vars);

$column = 0;

if (($is_allowed_to_edit || $group_member_with_upload_rights) && count($docs_and_folders) > 1) {
    $table->set_header($column++, '', false, array('style' => 'width:12px;'));
}
$table->set_header($column++, get_lang('Type'),true, array('style' => 'width:30px;'));
$table->set_header($column++, get_lang('Name'));
$table->set_header($column++, get_lang('Size'),true, array('style' => 'width:50px;'));
$table->set_header($column++, get_lang('Date'),true, array('style' => 'width:105px;'));
// Admins get an edit column
if ($is_allowed_to_edit || $group_member_with_upload_rights || is_my_shared_folder(api_get_user_id(), $curdirpath, $current_session_id)) {
    $table->set_header($column++, get_lang('Actions'), false, array ('style' => 'width:160px;'));
}

// Actions on multiple selected documents
// TODO: Currently only delete action -> take only DELETE right into account

if (count($docs_and_folders) > 1) {
    if ($is_allowed_to_edit || $group_member_with_upload_rights) {
        $form_actions = array();
        $form_action['delete'] = get_lang('Delete');
        $table->set_form_actions($form_action, 'path');        
    }    
}
$table->display();

if (count($docs_and_folders) > 1) {
    if ($is_allowed_to_edit || $group_member_with_upload_rights) {
        
        // Getting the course quota
        $course_quota = DocumentManager::get_course_quota();
                
        // Calculating the total space
        $already_consumed_space = DocumentManager::documents_total_space($_course);
        
        // Displaying the quota        
        DocumentManager::display_simple_quota($course_quota, $already_consumed_space);       
    }
}
if (!empty($table_footer)) {
    Display::display_warning_message($table_footer);
}

// Footer
Display::display_footer();
