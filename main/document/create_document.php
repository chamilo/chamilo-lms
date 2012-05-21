<?php
/* For licensing terms, see /license.txt */
/**
 *	This file allows creating new html documents with an online WYSIWYG html editor.
 *
 *	@package chamilo.document
 */
/**
 * Code
 */
/*	INIT SECTION */

// Name of the language file that needs to be included
$language_file = array('document', 'gradebook');

require_once '../inc/global.inc.php';

$_SESSION['whereami'] = 'document/create';
$this_section = SECTION_COURSES;

$htmlHeadXtra[] = '
<script>

var hide_bar = function() {    
    $("#main_content .span3").hide(); 
    $("#doc_form").removeClass("span9"); 
    $("#doc_form").addClass("span11");   
    $("#hide_bar_template").css({"background-image" : \'url("../img/hide2.png")\'})
}

$(document).ready(function() {    
    $("#hide_bar_template").toggle(
        function() { 
            $("#main_content .span3").hide(); 
            $("#doc_form").removeClass("span9"); 
            $("#doc_form").addClass("span11");             
            $(this).css({"background-image" : \'url("../img/hide2.png")\'})
        },
        function() { 
            $("#main_content .span3").show(); 
            $("#doc_form").removeClass("span11"); 
            $("#doc_form").addClass("span9"); 
            $(this).css("background-image", \'url("../img/hide0.png")\'); 
        }            
    );    
});

function InnerDialogLoaded() {
	/*
	var B=new window.frames[0].FCKToolbarButton(\'Templates\',window.frames[0].FCKLang.Templates);
	return B.ClickFrame();
	*/

	var isIE  = (navigator.appVersion.indexOf(\'MSIE\') != -1) ? true : false ;
	var EditorFrame = null ;

	if ( !isIE ) {
		EditorFrame = window.frames[0] ;
	} else {
		// For this dynamic page window.frames[0] enumerates frames in a different order in IE.
		// We need a sure method to locate the frame that contains the online editor.
		for ( var i = 0, n = window.frames.length ; i < n ; i++ ) {
			if ( window.frames[i].location.toString().indexOf(\'InstanceName=content\') != -1 ) {
				EditorFrame = window.frames[i] ;
			}
		}
	}

	if ( !EditorFrame ) {
		return null ;
	}

	var B = new EditorFrame.FCKToolbarButton(\'Templates\', EditorFrame.FCKLang.Templates);
	return B.ClickFrame();
};

	var temp=false;
	var temp2=false;	
	var load_default_template = '. ((isset($_POST['submit']) || empty($_SERVER['QUERY_STRING'])) ? 'false' : 'true' ) .';

	function FCKeditor_OnComplete( editorInstance ) {
		editorInstance.Events.AttachEvent( \'OnSelectionChange\', check_for_title ) ;
		document.getElementById(\'frmModel\').innerHTML = "<iframe style=\'height: 525px; width: 180px;\' scrolling=\'no\' frameborder=\'0\' src=\''.api_get_path(WEB_LIBRARY_PATH).'fckeditor/editor/fckdialogframe.html \'>";
	}

	function check_for_title() {
		if (temp) {
			// This functions shows that you can interact directly with the editor area
			// DOM. In this way you have the freedom to do anything you want with it.

			// Get the editor instance that we want to interact with.
			var oEditor = FCKeditorAPI.GetInstance(\'content\') ;

			// Get the Editor Area DOM (Document object).
			var oDOM = oEditor.EditorDocument ;

			var iLength ;
			var contentText ;
			var contentTextArray;
			var bestandsnaamNieuw = "";
			var bestandsnaamOud = "";

			// The are two diffent ways to get the text (without HTML markups).
			// It is browser specific.

			if( document.all )		// If Internet Explorer.
			{
				contentText = oDOM.body.innerText ;
			}
			else					// If Gecko.
			{
				var r = oDOM.createRange() ;
				r.selectNodeContents( oDOM.body ) ;
				contentText = r.toString() ;
			}

			var index=contentText.indexOf("/*<![CDATA");
			contentText=contentText.substr(0,index);

			// Compose title if there is none
			contentTextArray = contentText.split(\' \') ;
			var x=0;
			for(x=0; (x<5 && x<contentTextArray.length); x++)
			{
				if(x < 4)
				{
					bestandsnaamNieuw += contentTextArray[x] + \' \';
				}
				else
				{
					bestandsnaamNieuw += contentTextArray[x];
				}
			}
		}
		temp=true;
	}

	function trim(s) {
        while(s.substring(0,1) == \' \') {
            s = s.substring(1,s.length);
        }
        while(s.substring(s.length-1,s.length) == \' \') {
            s = s.substring(0,s.length-1);
        }
        return s;
	}
	function setFocus() {
	   $("#document_title").focus();
    }
    
	$(window).load(function () {
        setFocus();
    });
</script>';

require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(SYS_CODE_PATH).'document/document.inc.php';

//I'm in the certification module?
$is_certificate_mode = false;

if (isset($_REQUEST['certificate']) && $_REQUEST['certificate'] == 'true') {
	$is_certificate_mode = true;
}

if ($is_certificate_mode) {
	$nameTools = get_lang('CreateCertificate');
} else {
	$nameTools = get_lang('CreateDocument');
}

/*	Constants and variables */

$doc_table = Database::get_course_table(TABLE_DOCUMENT);
$course_id = api_get_course_int_id();

$document_data = DocumentManager::get_document_data_by_id($_REQUEST['id'], api_get_course_id(), true);    
if (empty($document_data)) {
    if (api_is_in_group()) {
        $group_properties   = GroupManager::get_group_properties(api_get_group_id());        
        $document_id        = DocumentManager::get_document_id(api_get_course_info(), $group_properties['directory']);
        $document_data      = DocumentManager::get_document_data_by_id($document_id, api_get_course_id());
        
        $dir                = $document_data['path'];
        $folder_id          = $document_data['id'];
    } else {
        $dir = '/';
        $folder_id = 0;
    }
} else {
    $folder_id = $document_data['id'];
    $dir       = $document_data['path'];
}

/*	MAIN CODE */

// Please, do not modify this dirname formatting
if (strstr($dir, '..')) {
	$dir = '/';
}

if ($dir[0] == '.') {
	$dir = substr($dir, 1);
}

if ($dir[0] != '/') {
	$dir = '/'.$dir;
}

if ($dir[strlen($dir) - 1] != '/') {
	$dir .= '/';
}

if ($is_certificate_mode) {
	$document_id 	= DocumentManager::get_document_id(api_get_course_info(), '/certificates');
	$document_data 	= DocumentManager::get_document_data_by_id($document_id, api_get_course_id(), true);
	$folder_id = $document_data['id'];
	$dir = '/certificates/';
}

// Configuration for the FCKEDITOR
$doc_tree  = explode('/', $dir);
$count_dir = count($doc_tree) -2; // "2" because at the begin and end there are 2 "/"

if (api_is_in_group()) {
    $group_properties = GroupManager::get_group_properties(api_get_group_id()); 
        
    // Level correction for group documents.
    if (!empty($group_properties['directory'])) {
    	$count_dir = $count_dir > 0 ? $count_dir - 1 : 0;
    }
}
$relative_url = '';
for ($i = 0; $i < ($count_dir); $i++) {
	$relative_url .= '../';
}

// We do this in order to avoid the condition in html_editor.php ==> if ($this -> fck_editor->Config['CreateDocumentWebDir']=='' || $this -> fck_editor->Config['CreateDocumentDir']== '')
if ($relative_url== '') {
	$relative_url = '/';
}

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

$html_editor_config = array(
	'ToolbarSet'           => ($is_allowed_to_edit ? 'Documents' :'DocumentsStudent'),
	'Width'                => '100%',
	'Height'               => '500',
	'FullPage'             => true,
	'InDocument'           => true,
	'CreateDocumentDir'    => $relative_url,
	'CreateDocumentWebDir' => (empty($group_properties['directory']))
                        		? api_get_path(WEB_COURSE_PATH).$_course['path'].'/document/'
                        		: api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document'.$group_properties['directory'].'/',
	'BaseHref'             => api_get_path(WEB_COURSE_PATH).$_course['path'].'/document'.$dir
);

if ($is_certificate_mode) {
    $html_editor_config['CreateDocumentDir']    = api_get_path(WEB_COURSE_PATH).$_course['path'].'/document/';
    $html_editor_config['CreateDocumentWebDir'] = api_get_path(WEB_COURSE_PATH).$_course['path'].'/document/';
    $html_editor_config['BaseHref']             = api_get_path(WEB_COURSE_PATH).$_course['path'].'/document'.$dir;
}

$filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document'.$dir;
        
if (!is_dir($filepath)) {
	$filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
	$dir = '/';
}

$to_group_id = 0;

if (!$is_certificate_mode) {
	if (api_is_in_group()) {
		$req_gid = '&amp;gidReq='.api_get_group_id();
		$interbreadcrumb[] = array ("url" => "../group/group_space.php?gidReq=".api_get_group_id(), "name" => get_lang('GroupSpace'));
		$noPHP_SELF = true;
		$to_group_id = api_get_group_id();		
		$path = explode('/', $dir);				
		if ('/'.$path[1] != $group_properties['directory']) {
			api_not_allowed(true);
		}
	}
	$interbreadcrumb[] = array ("url" => "./document.php?curdirpath=".urlencode($dir).$req_gid, "name" => get_lang('Documents'));
} else {
	$interbreadcrumb[]= array (	'url' => '../gradebook/'.$_SESSION['gradebook_dest'], 'name' => get_lang('Gradebook'));
}

if (!$is_allowed_in_course) {
	api_not_allowed(true);
}
if (!($is_allowed_to_edit || $_SESSION['group_member_with_upload_rights'] || is_my_shared_folder($_user['user_id'], Security::remove_XSS($dir),api_get_session_id()))) {
	api_not_allowed(true);
}

/*	Header */

event_access_tool(TOOL_DOCUMENT);
$display_dir = $dir;
if (isset ($group_properties)) {
	$display_dir = explode('/', $dir);
	unset ($display_dir[0]);
	unset ($display_dir[1]);
	$display_dir = implode('/', $display_dir);
}

// Create a new form
$form = new FormValidator('create_document','post',api_get_self().'?dir='.Security::remove_XSS(urlencode($dir)).'&selectcat='.Security::remove_XSS($_GET['selectcat']), null, array('class' =>'form-vertical' ));

// form title
$form->addElement('header', '', $nameTools);

if ($is_certificate_mode) {//added condition for certicate in gradebook
	$form->addElement('hidden','certificate','true',array('id'=>'certificate'));
	if (isset($_GET['selectcat']))
		$form->addElement('hidden','selectcat', intval($_GET['selectcat']));

}
// Hidden element with current directory
$form->addElement('hidden', 'id');
$defaults = array();
$defaults['id'] = $folder_id;

// Filename
$form->addElement('hidden', 'title_edited', 'false', 'id="title_edited"');

/**
 * Check if a document width the choosen filename allready exists
 */
function document_exists($filename) {
	global $filepath;
	$filename = addslashes(trim($filename));
	$filename = Security::remove_XSS($filename);
	$filename = replace_dangerous_char($filename);
	$filename = disable_dangerous_file($filename);
	return !file_exists($filepath.$filename.'.html');
}

// Add group to the form
if ($is_certificate_mode) {	
    $form->addElement('text', 'title', get_lang('CertificateName'), 'class="span4" id="document_title"');
} else {
	$form->addElement('text', 'title', get_lang('Title'), 'class="span4" id="document_title"');
}

// Show read-only box only in groups
if (!empty($_SESSION['_gid'])) {
	$group[]= $form->createElement('checkbox', 'readonly', '', get_lang('ReadOnly'));
}
$form->addRule('title', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('title', get_lang('FileExists'), 'callback', 'document_exists');

$current_session_id = api_get_session_id();
$form->add_html_editor('content','', false, false, $html_editor_config);

// Comment-field
$folders = DocumentManager::get_all_document_folders($_course, $to_group_id, $is_allowed_to_edit);

// If we are not in the certificates creation, display a folder chooser for the
// new document created 

if (!$is_certificate_mode && !is_my_shared_folder($_user['user_id'], $dir, $current_session_id)) {
	$folders = DocumentManager::get_all_document_folders($_course, $to_group_id, $is_allowed_to_edit);
	
	$parent_select = $form->addElement('select', 'curdirpath', array(null, get_lang('DestinationDirectory')));
	
	// Following two conditions copied from document.inc.php::build_directory_selector()
	$folder_titles = array();
	
    if (is_array($folders)) {		
        $escaped_folders = array();			
        foreach ($folders as $key => & $val) {
            //Hide some folders
            if ($val=='/HotPotatoes_files' || $val=='/certificates' || basename($val)=='css'){			
                continue;
            }
            //Admin setting for Hide/Show the folders of all users		
            if (api_get_setting('show_users_folders') == 'false' && (strstr($val, '/shared_folder') || strstr($val, 'shared_folder_session_'))){	
                continue;
            }
            //Admin setting for Hide/Show Default folders to all users
            if (api_get_setting('show_default_folders') == 'false' && ($val=='/images' || $val=='/flash' || $val=='/audio' || $val=='/video' || strstr($val, '/images/gallery') || $val=='/video/flv')){
                continue;
            }
            //Admin setting for Hide/Show chat history folder
            if (api_get_setting('show_chat_folder') == 'false' && $val=='/chat_files'){
                continue;
            }				

            $escaped_folders[$key] = Database::escape_string($val);
        }
        $folder_sql = implode("','", $escaped_folders);

        $sql = "SELECT * FROM $doc_table WHERE c_id = $course_id AND filetype='folder' AND path IN ('".$folder_sql."')";
        $res = Database::query($sql);
        $folder_titles = array();	
        while ($obj = Database::fetch_object($res)) {				
            $folder_titles[$obj->path] = $obj->title;
        }
    }	
	
	if (empty($group_dir)) {
		$parent_select -> addOption(get_lang('HomeDirectory'), '/');
		if (is_array($folders)) {
			foreach ($folders as & $folder) {
				//Hide some folders
				if ($folder=='/HotPotatoes_files' || $folder=='/certificates' || basename($folder)=='css'){			
				 continue;
				}
				//Admin setting for Hide/Show the folders of all users		
				if (api_get_setting('show_users_folders') == 'false' && (strstr($folder, '/shared_folder') || strstr($folder, 'shared_folder_session_'))){	
					continue;
				}
				//Admin setting for Hide/Show Default folders to all users
				if (api_get_setting('show_default_folders') == 'false' && ($folder=='/images' || $folder=='/flash' || $folder=='/audio' || $folder=='/video' || strstr($folder, '/images/gallery') || $folder=='/video/flv')){
					continue;
				}
				//Admin setting for Hide/Show chat history folder
				if (api_get_setting('show_chat_folder') == 'false' && $folder=='/chat_files'){
					continue;
				}			
				
				$selected = (substr($dir,0,-1) == $folder) ? ' selected="selected"' : '';
				$path_parts = explode('/', $folder);
				$folder_titles[$folder] = cut($folder_titles[$folder], 80);
				$label = str_repeat('&nbsp;&nbsp;&nbsp;', count($path_parts) - 2).' &mdash; '.$folder_titles[$folder];
				$parent_select -> addOption($label, $folder);
				if ($selected != '') {
					$parent_select->setSelected($folder);
				}
			}
		}
	} else {
		foreach ($folders as & $folder) {
			$selected = (substr($dir,0,-1)==$folder) ? ' selected="selected"' : '';
			$label = $folder_titles[$folder];
			if ($folder == $group_dir) {
				$label = '/ ('.get_lang('HomeDirectory').')';
			} else {
				$path_parts = explode('/', str_replace($group_dir, '', $folder));
				$label = cut($label, 80);
				$label = str_repeat('&nbsp;&nbsp;&nbsp;', count($path_parts) - 2).' &mdash; '.$label;
			}
			$parent_select -> addOption($label, $folder);
			if ($selected != '') {
				$parent_select->setSelected($folder);
			}
		}
	}
}

if ($is_certificate_mode)
	$form->addElement('style_submit_button', 'submit', get_lang('CreateCertificate'), 'class="save"');
else
	$form->addElement('style_submit_button', 'submit', get_lang('langCreateDoc'), 'class="save"');

$form->setDefaults($defaults);

// If form validates -> save the new document
if ($form->validate()) {
	$values = $form->exportValues();    
	$readonly = isset($values['readonly']) ? 1 : 0;
	
	$values['title'] = trim($values['title']);	
	
	if (!empty($values['curdirpath'])) {
		$dir = $values['curdirpath'];
	}
    
	if ($dir[strlen($dir) - 1] != '/') {
		$dir .= '/';
	}
    
    //Setting the filename
	$filename = $values['title'];    
	$filename = addslashes(trim($filename));
	$filename = Security::remove_XSS($filename);
	$filename = replace_dangerous_char($filename);
	$filename = disable_dangerous_file($filename);	
    
    //Setting the title
	$title 		= $values['title'];
    
    //Setting the extension
	$extension = 'html';

	$content = Security::remove_XSS($values['content'], COURSEMANAGERLOWSECURITY);

	if (strpos($content, '/css/frames.css') === false) {
		$content = str_replace('</head>', '<style> body{margin:10px;}</style> <link rel="stylesheet" href="./css/frames.css" type="text/css" /></head>', $content);
	}	
	if ($fp = @fopen($filepath.$filename.'.'.$extension, 'w')) {
		$content = str_replace(api_get_path(WEB_COURSE_PATH), $_configuration['url_append'].'/courses/', $content);
		
		// change the path of mp3 to absolute
		// first regexp deals with ../../../ urls
		// Disabled by Ivan Tcholakov.
		//$content = preg_replace("|(flashvars=\"file=)(\.+/)+|","$1".api_get_path(REL_COURSE_PATH).$_course['path'].'/document/',$content);
		//second regexp deals with audio/ urls
		// Disabled by Ivan Tcholakov.
		//$content = preg_replace("|(flashvars=\"file=)([^/]+)/|","$1".api_get_path(REL_COURSE_PATH).$_course['path'].'/document/$2/',$content);
		fputs($fp, $content);
		fclose($fp);
		chmod($filepath.$filename.'.'.$extension, api_get_permissions_for_new_files());
		if (!is_dir($filepath.'css')) {
			mkdir($filepath.'css', api_get_permissions_for_new_directories());
			$doc_id = add_document($_course, $dir.'css', 'folder', 0, 'css');
			api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'FolderCreated', $_user['user_id'], null, null, null, null, $current_session_id);
			api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'invisible', $_user['user_id'], null, null, null, null, $current_session_id);
		}

		if (!is_file($filepath.'css/frames.css')) {
			// Make a copy of the current css for the new document
			copy(api_get_path(SYS_CODE_PATH).'css/'.api_get_setting('stylesheets').'/frames.css', $filepath.'css/frames.css');
			$doc_id = add_document($_course, $dir.'css/frames.css', 'file', filesize($filepath.'css/frames.css'), 'frames.css');
			api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id'], null, null, null, null, $current_session_id);
			api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'invisible', $_user['user_id'], null, null, null, null, $current_session_id);
		}

		$file_size = filesize($filepath.$filename.'.'.$extension);
		$save_file_path = $dir.$filename.'.'.$extension;

		$document_id = add_document($_course, $save_file_path, 'file', $file_size, $title, null, $readonly);
		if ($document_id) {
			api_item_property_update($_course, TOOL_DOCUMENT, $document_id, 'DocumentAdded', $_user['user_id'], $to_group_id, null, null, null, $current_session_id);
			// Update parent folders
			item_property_update_on_folder($_course, $dir, $_user['user_id']);
			$new_comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
			$new_title = isset($_POST['title']) ? trim($_POST['title']) : '';
			if ($new_comment || $new_title) {
				$ct = '';
				if ($new_comment)
					$ct .= ", comment='$new_comment'";
				if ($new_title)
					$ct .= ", title='$new_title'";
				Database::query("UPDATE $doc_table SET".substr($ct, 1)." WHERE c_id = $course_id AND id = '$document_id'");
			}
			$dir= substr($dir,0,-1);
			$selectcat = '';
			if (isset($_REQUEST['selectcat']))
				$selectcat = "&selectcat=".Security::remove_XSS($_REQUEST['selectcat']);
			$certificate_condition = '';
			if ($is_certificate_mode) {
				$certificate_condition = '&certificate=true';
			}
			header('Location: document.php?id='.$folder_id.$selectcat.$certificate_condition);
			exit ();
		} else {
			Display :: display_header($nameTools, 'Doc');
			Display :: display_error_message(get_lang('Impossible'));
			Display :: display_footer();
		}
	} else {
		Display :: display_header($nameTools, 'Doc');
		Display :: display_error_message(get_lang('Impossible'));
		Display :: display_footer();
	}
} else {
	// Interbreadcrumb for the current directory root path
	// Copied from document.php
	$dir_array = explode('/', $dir);
	$array_len = count($dir_array);
	
	// Interbreadcrumb for the current directory root path
	if (empty($document_data['parents'])) {
		$interbreadcrumb[] = array('url' => '#', 'name' => $document_data['title']);
	} else {
		foreach($document_data['parents'] as $document_sub_data) {
			$interbreadcrumb[] = array('url' => $document_sub_data['document_url'], 'name' => $document_sub_data['title']);
		}
	}

	Display :: display_header($nameTools, "Doc");
	//api_display_tool_title($nameTools);
	// actions	
	echo '<div class="actions">';
	
	// link back to the documents overview
	if ($is_certificate_mode)
		echo '<a href="document.php?certificate=true&id='.$folder_id.'&selectcat=' . Security::remove_XSS($_GET['selectcat']).'">'.Display::return_icon('back.png',get_lang('Back').' '.get_lang('To').' '.get_lang('CertificateOverview'),'',ICON_SIZE_MEDIUM).'</a>';
	else
		echo '<a href="document.php?curdirpath='.Security::remove_XSS($dir).'">'.Display::return_icon('back.png',get_lang('Back').' '.get_lang('To').' '.get_lang('DocumentsOverview'),'',ICON_SIZE_MEDIUM).'</a>';
	echo '</div>';
	
	if ($is_certificate_mode) {
		$all_information_by_create_certificate = DocumentManager::get_all_info_to_certificate(api_get_user_id(), api_get_course_id());
	
		$str_info = '';
		foreach ($all_information_by_create_certificate[0] as $info_value) {
			$str_info.=$info_value.'<br/>';
		}
		$create_certificate = get_lang('CreateCertificateWithTags');
		Display::display_normal_message($create_certificate.': <br /><br/>'.$str_info,false);
	}    
    // HTML-editor
    echo '<div class="row-fluid" style="overflow:hidden">
            <div class="span3">
                    <div id="frmModel" style="overflow: visible;"></div>
            </div>
            <div id="hide_bar_template" class="span1"></div>
            <div id="doc_form" class="span9">
                    '.$form->return_form().'
            </div>
          </div>';
	Display :: display_footer();
}
