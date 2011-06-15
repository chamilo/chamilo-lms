<?php
/* For licensing terms, see /license.txt */

/**
 * This file allows editing documents.
 *
 * Based on create_document, this file allows
 * - edit name
 * - edit comments
 * - edit metadata (requires a document table entry)
 * - edit html content (only for htm/html files)
 *
 * For all files
 * - show editable name field
 * - show editable comments field
 * Additionally, for html and text files
 * - show RTE
 *
 * Remember, all files and folders must always have an entry in the
 * database, regardless of wether they are visible/invisible, have
 * comments or not.
 *
 * @package chamilo.document
 * @todo improve script structure (FormValidator is used to display form, but
 * not for validation at the moment)
 */

// Name of the language file that needs to be included
$language_file = array('document', 'gradebook');

/*	Included libraries */

require_once '../inc/global.inc.php';

// Template's javascript
$htmlHeadXtra[] = '
<script type="text/javascript">

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

function FCKeditor_OnComplete( editorInstance) {
	document.getElementById(\'frmModel\').innerHTML = "<iframe style=\'height: 525px; width: 180px;\' scrolling=\'no\' frameborder=\'0\' src=\''.api_get_path(WEB_LIBRARY_PATH).'fckeditor/editor/fckdialogframe.html \'>";
}
</script>';

$_SESSION['whereami'] = 'document/create';
$this_section = SECTION_COURSES;
$lib_path = api_get_path(LIBRARY_PATH);

require_once $lib_path.'fileManage.lib.php';
require_once $lib_path.'fileUpload.lib.php';
require_once $lib_path.'document.lib.php';
require_once $lib_path.'groupmanager.lib.php';
require_once $lib_path.'formvalidator/FormValidator.class.php';
require_once api_get_path(SYS_CODE_PATH).'document/document.inc.php';
/* Constants & Variables */

if (api_is_in_group()) {
	$group_properties = GroupManager::get_group_properties($_SESSION['_gid']);
}

if (isset($_GET['id'])) {
    $document_data = DocumentManager::get_document_data_by_id($_GET['id'], api_get_course_id());    
    if (empty($document_data)) {
        api_not_allowed();
    }
    $document_id    = $document_data['id'];
    $file           = $document_data['path'];
    $parent_id      = DocumentManager::get_document_id(api_get_course_info(), dirname($file));    
    $dir            = dirname($document_data['path']);    
    $dir_original   =  $dir;
    
    $doc            = basename($file);
    $my_cur_dir_path = Security::remove_XSS($_GET['curdirpath']);    
    
} else {
    $dir = Security::remove_XSS($_GET['curdirpath']);    
    $dir_original = $dir;
    $file = $_GET['file'];
    $doc = basename($file);    
}

//I'm in the certification module?
$is_certificate_mode = DocumentManager::is_certificate_mode($dir);

//Call from
$call_from_tool = Security::remove_XSS($_GET['origin']);
$slide_id = Security::remove_XSS($_GET['origin_opt']);
$file_name = $doc;

$baseServDir = api_get_path(SYS_COURSE_PATH);
$courseDir   = $_course['path'].'/document';
$baseWorkDir = $baseServDir.$courseDir;
$group_document = false;
$current_session_id = api_get_session_id();
$doc_tree = explode('/', $file);
$count_dir = count($doc_tree) - 2; // "2" because at the begin and end there are 2 "/"

// Level correction for group documents.
if (!empty($group_properties['directory'])) {
	$count_dir = $count_dir > 0 ? $count_dir - 1 : 0;
}
$relative_url = '';
for ($i = 0; $i < ($count_dir); $i++) {
	$relative_url .= '../';
}

$html_editor_config = array(
	'ToolbarSet' => (api_is_allowed_to_edit(null, true) ? 'Documents' :'DocumentsStudent'),
	'Width' => '100%',
	'Height' => '600',
	'FullPage' => true,
	'InDocument' => true,
	'CreateDocumentDir' => $relative_url,
	'CreateDocumentWebDir' => (empty($group_properties['directory']))
		? api_get_path(WEB_COURSE_PATH).$_course['path'].'/document/'
		: api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document'.$group_properties['directory'].'/',
	'BaseHref' =>  api_get_path(WEB_COURSE_PATH).$_course['path'].'/document'.$dir
);

$is_allowed_to_edit = api_is_allowed_to_edit(null, true) || $_SESSION['group_member_with_upload_rights']|| is_my_shared_folder(api_get_user_id(), $dir, $current_session_id);

$use_document_title = api_get_setting('use_document_title') == 'true';
$noPHP_SELF = true;

/*	Other initialization code */

/* Please, do not modify this dirname formatting */

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

$filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document'.$dir;

if (!is_dir($filepath)) {
	$filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
	$dir = '/';
}

$dbTable = Database::get_course_table(TABLE_DOCUMENT);

if (!empty($_SESSION['_gid'])) {
	$req_gid = '&amp;gidReq='.$_SESSION['_gid'];
	$interbreadcrumb[] = array ('url' => '../group/group_space.php?gidReq='.$_SESSION['_gid'], 'name' => get_lang('GroupSpace'));
	$group_document = true;
	$noPHP_SELF = true;
}

if (!$is_certificate_mode)
	$interbreadcrumb[]=array("url"=>"./document.php?curdirpath=".urlencode($my_cur_dir_path).$req_gid, "name"=> get_lang('Documents'));
else
	$interbreadcrumb[]= array (	'url' => '../gradebook/'.$_SESSION['gradebook_dest'], 'name' => get_lang('Gradebook'));

if (!is_allowed_to_edit) {
	api_not_allowed(true);
}
$user_id = api_get_user_id();
event_access_tool(TOOL_DOCUMENT);

//TODO:check the below code and his funcionality
if (!is_allowed_to_edit()) {
	if (DocumentManager::check_readonly($_course, $user_id, $file)) {
		api_not_allowed();
	}
}

/* MAIN TOOL CODE */

/*	Code to change the comment
	Step 2. React on POST data
	(Step 1 see below) */

if (isset($_POST['comment'])) {
	// Fixing the path if it is wrong	
	$comment 	     = trim(Database::escape_string($_POST['comment']));
	$title 		     = trim(Database::escape_string($_POST['title'])); 
	$query = "UPDATE $dbTable SET comment='".$comment."', title='".$title."' WHERE id = ".$document_id;
	Database::query($query);	
	$comments_updated = get_lang('ComMod');
	$info_message     = get_lang('fileModified');
}

/*	Code to change the name
	Step 2. react on POST data - change the name
	(Step 1 see below) */

if (isset($_POST['renameTo'])) {
	$info_message = change_name($baseWorkDir, $_GET['sourceFile'], $_POST['renameTo'], $dir, $doc);
}

/*	Code to change the comment
	Step 1. Create dialog box. */

/** TODO: Check whether this code is still used **/
/* Search the old comment */  // RH: metadata: added 'id,'
$result = Database::query("SELECT id, comment, title FROM $dbTable WHERE id = ".$document_id);

/*
// Debug info - enable on temporary needs only.
$message = '<i>Debug info</i><br />directory = '.$dir.'<br />';
$message .= 'document = '.$file_name.'<br />';
$message .= 'comments file = '.$file.'<br />';
Display::display_normal_message($message);
*/

while ($row = Database::fetch_array($result, 'ASSOC')) {
	$oldComment = $row['comment'];
	$oldTitle   = $row['title'];
	$docId      = $row['id'];  // RH: metadata
}

/*	WYSIWYG HTML EDITOR - Program Logic */

if ($is_allowed_to_edit) {
	if ($_POST['formSent'] == 1) {
		if (isset($_POST['renameTo'])) {
			$_POST['filename'] = disable_dangerous_file($_POST['renameTo']);
			$extension = explode('.', $_POST['filename']);
			$extension = $extension[sizeof($extension) - 1];
			$_POST['filename'] = str_replace('.'.$extension, '', $_POST['filename']);
		}

		$filename = stripslashes($_POST['filename']);

		$content = trim(str_replace(array("\r", "\n"), '', stripslashes($_POST['content'])));
		$content = Security::remove_XSS($content, COURSEMANAGERLOWSECURITY);

		if (!strstr($content, '/css/frames.css')) {
			$content=str_replace('</title></head>', '</title><link rel="stylesheet" href="../css/frames.css" type="text/css" /></head>', $content);
		}
		/*
        if (!ctype_alnum($_POST['extension'])) {
            header('Location: document.php?msg=WeirdExtensionDeniedInPost');
            exit ();
	    }*/
        $extension = $_POST['extension'];
		$file = $dir.$filename.'.'.$extension;
		$read_only_flag = $_POST['readonly'];
		$read_only_flag = empty($read_only_flag) ? 0 : 1;
				
		$show_edit = $_SESSION['showedit'];
		api_session_unregister('showedit');

		if (empty($filename)) {
			$msgError = get_lang('NoFileName');
		} else {		    
		    if ($document_data['filetype'] == 'file') {
                $file_size = filesize($filepath.$filename.'.'.$extension);
		    } else {
		        $file_size = filesize($filepath.$filename);		        
		    }		    
			if ($read_only_flag == 0) {
				if (!empty($content)) {
					if ($fp = @fopen($filepath.$filename.'.'.$extension, 'w')) {
						$content = text_filter($content);
						// For flv player, change absolute paht temporarely to prevent from erasing it in the following lines
						$content = str_replace(array('flv=h', 'flv=/'), array('flv=h|', 'flv=/|'), $content);

						// Change the path of mp3 to absolute
						// The first regexp deals with ../../../ urls
						// Disabled by Ivan Tcholakov.
						//$content = preg_replace("|(flashvars=\"file=)(\.+/)+|","$1".api_get_path(REL_COURSE_PATH).$_course['path'].'/document/',$content);
						// The second regexp deals with audio/ urls
						// Disabled by Ivan Tcholakov.
						//$content = preg_replace("|(flashvars=\"file=)([^/]+)/|","$1".api_get_path(REL_COURSE_PATH).$_course['path'].'/document/$2/',$content);

 						fputs($fp, $content);
						fclose($fp);
						if (!is_dir($filepath.'css')) {
							mkdir($filepath.'css', api_get_permissions_for_new_directories());
							$doc_id = add_document($_course, $dir.'css', 'folder', 0, 'css');
							api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'FolderCreated', api_get_user_id(), null, null, null, null, $current_session_id);
							api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'invisible', api_get_user_id(), null, null, null, null, $current_session_id);
						}

						if (!is_file($filepath.'css/frames.css')) {
							$platform_theme = api_get_setting('stylesheets');
							if (file_exists(api_get_path(SYS_CODE_PATH).'css/'.$platform_theme.'/frames.css')) {
								copy(api_get_path(SYS_CODE_PATH).'css/'.$platform_theme.'/frames.css', $filepath.'css/frames.css');
								$doc_id = add_document($_course, $dir.'css/frames.css', 'file', filesize($filepath.'css/frames.css'), 'frames.css');
								api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', api_get_user_id(), null, null, null, null, $current_session_id);
								api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'invisible', api_get_user_id(), null, null, null, null, $current_session_id);
							}
						}

						// "WHAT'S NEW" notification: update table item_property
						$document_id = DocumentManager::get_document_id($_course, $file);
						if ($document_id) {							
							update_existing_document($_course, $document_id, $file_size, $read_only_flag);
							api_item_property_update($_course, TOOL_DOCUMENT, $document_id, 'DocumentUpdated', api_get_user_id(), null, null, null, null, $current_session_id);
							// Update parent folders
							item_property_update_on_folder($_course, $dir, api_get_user_id());
							$dir_modified = substr($dir, 0, -1);
							//header('Location: document.php?id='.urlencode($dir));
							
							$my_id = DocumentManager::get_document_id($_course, $dir_modified);
							header('Location: document.php?id='.$my_id);
							
							exit ();
						} else {
							//$msgError = get_lang('Impossible');
						}
					} else {
						$msgError = get_lang('Impossible');
					}
				} else {					
					if ($document_id) {
                        update_existing_document($_course, $document_id, $file_size, $read_only_flag);					
					}
				}
			} else {
                if ($document_id) {
                    update_existing_document($_course, $document_id, $file_size, $read_only_flag);
                }				
			}
		}
	}
}

// Replace relative paths by absolute web paths (e.g. './' => 'http://www.chamilo.org/courses/ABC/document/')
if (file_exists($filepath.$doc)) {
	$extension = explode('.', $doc);
	$extension = $extension[sizeof($extension) - 1];
	$filename = str_replace('.'.$extension, '', $doc);
	$extension = strtolower($extension);

	if (in_array($extension, array('html', 'htm'))) {
		$content = file($filepath.$doc);
		$content = implode('', $content);
		$path_to_append = api_get_path(WEB_COURSE_PATH).$_course['path'].'/document'.$dir;
		$content = str_replace('="./', '="'.$path_to_append, $content);
		$content = str_replace('mp3player.swf?son=.%2F', 'mp3player.swf?son='.urlencode($path_to_append), $content);
	}
}

/*	Display user interface */

// Display the header
$nameTools = get_lang('EditDocument') . ': '.$oldTitle;
Display::display_header($nameTools, 'Doc');

// Display the tool title
//api_display_tool_title($nameTools);

if (isset($msgError)) {
	Display::display_error_message($msgError);
}

if (isset($info_message)) {
	Display::display_confirmation_message($info_message);
	if (isset($_POST['origin'])) {
		$slide_id = $_POST['origin_opt'];	
		$call_from_tool = $_POST['origin'];
	}
}

// Readonly
$sql = 'SELECT id, readonly FROM '.$dbTable.' WHERE path LIKE BINARY "'.$dir.$doc.'"';
$rs = Database::query($sql);
$readonly = Database::result($rs, 0, 'readonly');
$doc_id = Database::result($rs, 0, 'id');

// Owner
$document_info  = api_get_item_property_info(api_get_course_int_id(),'document', $doc_id);
$owner_id       = $document_info['insert_user_id'];
$last_edit_date = $document_info['lastedit_date'];

if ($owner_id == api_get_user_id() || api_is_platform_admin() || $is_allowed_to_edit || GroupManager :: is_user_in_group(api_get_user_id(), api_get_group_id() )) {
	$get_cur_path = $dir;	
	$action = api_get_self().'?sourceFile='.urlencode($file_name).'&id='.$document_data['id'];
	$form = new FormValidator('formEdit', 'post', $action);

	// Form title
	$form->addElement('header', '', $nameTools);

	$renderer = $form->defaultRenderer();

	$form->addElement('hidden', 'filename');
	$form->addElement('hidden', 'extension');
	$form->addElement('hidden', 'file_path');
	$form->addElement('hidden', 'commentPath');
	$form->addElement('hidden', 'showedit');
	$form->addElement('hidden', 'origin');
	$form->addElement('hidden', 'origin_opt');

	if ($use_document_title) {
		$form->add_textfield('title', get_lang('Title'));
		$defaults['title'] = $oldTitle;
	} else {
		$form->addElement('hidden', 'renameTo');
	}

	$form->addElement('hidden', 'formSent');
	$defaults['formSent'] = 1;

	$read_only_flag = $_POST['readonly'];

	// Desactivation of IE proprietary commenting tags inside the text before loading it on the online editor.
	// This fix has been proposed by Hubert Borderiou, see Bug #573, http://support.chamilo.org/issues/573
	$defaults['content'] = str_replace('<!--[', '<!-- [', $content);

	//if ($extension == 'htm' || $extension == 'html')
	// HotPotatoes tests are html files, but they should not be edited in order their functionality to be preserved.
	if (($extension == 'htm' || $extension == 'html') && stripos($dir, '/HotPotatoes_files') === false) {
		if (empty($readonly) && $readonly == 0) {
			$_SESSION['showedit'] = 1;
			$renderer->setElementTemplate('<div class="row"><div class="label" id="frmModel" style="overflow: visible;"></div><div class="formw">{element}</div></div>', 'content');
			$form->add_html_editor('content', '', false, true, $html_editor_config);
		}
	}

	if (!$group_document && !is_my_shared_folder(api_get_user_id(), $my_cur_dir_path, $current_session_id)) {
		$metadata_link = '<a href="../metadata/index.php?eid='.urlencode('Document.'.$docId).'">'.get_lang('AddMetadata').'</a>';

		//Updated on field
		$last_edit_date = api_get_local_time($last_edit_date, null, date_default_timezone_get());
        $display_date = date_to_str_ago($last_edit_date).'<br /><span class="dropbox_date">'.api_format_date($last_edit_date).'</span>';        
		$form->addElement('static', null, get_lang('Metadata'), $metadata_link);
		$form->addElement('static', null, get_lang('UpdatedOn'), $display_date);
	}

	$form->addElement('textarea', 'comment', get_lang('Comment'), 'rows="3" style="width:300px;"');
	/*
	$renderer = $form->defaultRenderer();
	*/
	if ($owner_id == api_get_user_id() || api_is_platform_admin()) {
		$renderer->setElementTemplate('<div class="row"><div class="label"></div><div class="formw">{element}{label}</div></div>', 'readonly');
		$checked =& $form->addElement('checkbox', 'readonly', get_lang('ReadOnly'));
		if ($readonly == 1) {
			$checked->setChecked(true);
		}
	}
	
	if ($is_certificate_mode)
		$form->addElement('style_submit_button', 'submit', get_lang('SaveCertificate'), 'class="save"');
	else
		$form->addElement('style_submit_button','submit',get_lang('SaveDocument'), 'class="save"');

	$defaults['filename'] = $filename;
	$defaults['extension'] = $extension;
	$defaults['file_path'] = Security::remove_XSS($_GET['file']);
	$defaults['commentPath'] = $file;
	$defaults['renameTo'] = $file_name;
	$defaults['comment'] = $oldComment;
	$defaults['origin'] = Security::remove_XSS($_GET['origin']);
	$defaults['origin_opt'] = Security::remove_XSS($_GET['origin_opt']);

	$form->setDefaults($defaults);
	// Show templates
	/*
	$form->addElement('html', '<div id="frmModel" style="display:block; height:525px; width:240px; position:absolute; top:115px; left:1px;"></div>');
	*/
	if (isset($_REQUEST['curdirpath']) && $dir =='/certificates') {
		$all_information_by_create_certificate=DocumentManager::get_all_info_to_certificate(api_get_user_id());
		$str_info='';
		foreach ($all_information_by_create_certificate[0] as $info_value) {
			$str_info.=$info_value.'<br/>';
		}
		$create_certificate=get_lang('CreateCertificateWithTags');
		Display::display_normal_message($create_certificate.': <br /><br />'.$str_info,false);
	}	
	show_return($parent_id, $dir_original, $call_from_tool, $slide_id, $is_certificate_mode);
	if($extension=='svg' && !api_browser_support('svg') && api_get_setting('enabled_support_svg') == 'true'){
		Display::display_warning_message(get_lang('BrowserDontSupportsSVG'));
	}
	$form->display();
	//Display::display_error_message(get_lang('ReadOnlyFile'));
}

Display::display_footer();


/* General functions */

/*
	Workhorse functions

	These do the actual work that is expected from of this tool, other functions
	are only there to support these ones.
*/

/**
	This function changes the name of a certain file.
	It needs no global variables, it takes all info from parameters.
	It returns nothing.
*/
function change_name($base_work_dir, $source_file, $rename_to, $dir, $doc) {
	$file_name_for_change = $base_work_dir.$dir.$source_file;
	//api_display_debug_info("call my_rename: params $file_name_for_change, $rename_to");
    $rename_to = disable_dangerous_file($rename_to); // Avoid renaming to .htaccess file
	$rename_to = my_rename($file_name_for_change, stripslashes($rename_to)); // fileManage API

	if ($rename_to) {
		if (isset($dir) && $dir != '') {
			$source_file = $dir.$source_file;
			$new_full_file_name = dirname($source_file).'/'.$rename_to;
		} else {
			$source_file = '/'.$source_file;
			$new_full_file_name = '/'.$rename_to;
		}

		update_db_info('update', $source_file, $new_full_file_name); // fileManage API
		$name_changed = get_lang('ElRen');
		$info_message = get_lang('fileModified');

		$GLOBALS['file_name'] = $rename_to;
		$GLOBALS['doc'] = $rename_to;

		return $info_message;
	} else {
		$dialogBox = get_lang('FileExists'); // TODO: This variable is not used.

		/* Return to step 1 */
		$rename = $source_file;
		unset($source_file);
	}
}

//return button back to
function show_return($document_id, $path, $call_from_tool='', $slide_id=0, $is_certificate_mode=false) {
    global $parent_id;	
	$pathurl = urlencode($path);
	echo '<div class="actions">';
	
	if ($is_certificate_mode) {
		echo '<a href="document.php?curdirpath='.Security::remove_XSS($_GET['curdirpath']).'&selectcat=' . Security::remove_XSS($_GET['selectcat']).'">'.Display::return_icon('back.png',get_lang('Back').' '.get_lang('To').' '.get_lang('CertificateOverview'),'','32').'</a>';
	} elseif($call_from_tool=='slideshow') {
		echo '<a href="'.api_get_path(WEB_PATH).'main/document/slideshow.php?slide_id='.$slide_id.'&curdirpath='.Security::remove_XSS(urlencode($_GET['curdirpath'])).'">'.Display::return_icon('slideshow.png', get_lang('BackTo').' '.get_lang('ViewSlideshow'),'','32').'</a>';		
	} elseif($call_from_tool=='editdraw') {
		echo '<a href="document.php?action=exit_slideshow&id='.$parent_id.'">'.Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('DocumentsOverview'),'','32').'</a>';
		echo '<a href="javascript:history.back(1)">'.Display::return_icon('draw.png', get_lang('BackTo').' '.get_lang('Draw'), array(), 32).'</a>';
	} elseif($call_from_tool=='editpaint'){
		echo '<a href="document.php?action=exit_slideshow&id='.$parent_id.'">'.Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('DocumentsOverview'), array(), '32').'</a>';
		echo '<a href="javascript:history.back(1)">'.Display::return_icon('paint.png', get_lang('BackTo').' '.get_lang('Paint'), array(), 32).'</a>';		
	} else {
		echo '<a href="document.php?action=exit_slideshow&id='.$parent_id.'">'.Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('DocumentsOverview'),'','32').'</a>';
	}
	echo '</div>';
}