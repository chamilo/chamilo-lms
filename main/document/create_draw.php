<?php
/* For licensing terms, see /license.txt */

/**
 *	This file allows creating new svg and png documents with an online editor.
 *
 *	@package chamilo.document
 *
 * @author Juan Carlos Raña Trabado
 * @since 25/september/2010
*/
/**
 * Code
 */

/*	INIT SECTION */

// Name of the language file that needs to be included
$language_file = array('document');

require_once '../inc/global.inc.php';

$_SESSION['whereami'] = 'document/createdraw';
$this_section = SECTION_COURSES;

require_once api_get_path(SYS_CODE_PATH).'document/document.inc.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';

$nameTools = get_lang('Draw');

api_protect_course_script();
api_block_anonymous_users();

$document_data = DocumentManager::get_document_data_by_id($_GET['id'], api_get_course_id(), true);
if (empty($document_data)) {
    if (api_is_in_group()) {
        $group_properties   = GroupManager::get_group_properties(api_get_group_id());        
        $document_id        = DocumentManager::get_document_id(api_get_course_info(), $group_properties['directory']);
        $document_data      = DocumentManager::get_document_data_by_id($document_id, api_get_course_id());
    }
}

$document_id   = $document_data['id'];
$dir           = $document_data['path'];

/*	Constants and variables */

//path for svg-edit save
$_SESSION['draw_dir'] = Security::remove_XSS($dir);
if ($_SESSION['draw_dir']=='/'){
    $_SESSION['draw_dir']='';
}

$dir = isset($dir) ? Security::remove_XSS($dir) : Security::remove_XSS($_POST['dir']);
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

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

$filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document'.$dir;

if (!is_dir($filepath)) {
	$filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
	$dir = '/';
}

//groups //TODO: clean
if (isset ($_SESSION['_gid']) && $_SESSION['_gid'] != 0) {
	$req_gid = '&amp;gidReq='.$_SESSION['_gid'];
	$interbreadcrumb[] = array ("url" => "../group/group_space.php?gidReq=".$_SESSION['_gid'], "name" => get_lang('GroupSpace'));
	$noPHP_SELF = true;
	$to_group_id = $_SESSION['_gid'];
	$group = GroupManager :: get_group_properties($to_group_id);
	$path = explode('/', $dir);
	if ('/'.$path[1] != $group['directory']) {
		api_not_allowed(true);
	}
}

$interbreadcrumb[] = array ("url" => "./document.php?id=".$parent_id.$req_gid, "name" => get_lang('Documents'));

if (!$is_allowed_in_course) {
	api_not_allowed(true);
}

if (!($is_allowed_to_edit || $_SESSION['group_member_with_upload_rights'] || is_my_shared_folder(api_get_user_id(), Security::remove_XSS($dir), api_get_session_id()))) {
	api_not_allowed(true);
}


/*	Header */
event_access_tool(TOOL_DOCUMENT);
$display_dir = $dir;
if (isset ($group)) {
	$display_dir = explode('/', $dir);
	unset ($display_dir[0]);
	unset ($display_dir[1]);
	$display_dir = implode('/', $display_dir);
}

// Interbreadcrumb for the current directory root path
	// Copied from document.php
	$dir_array = explode('/', $dir);
	$array_len = count($dir_array);
	
	/*
	TODO:check and delete this code
	if (!$is_certificate_mode) {
		if ($array_len > 1) {
			if (empty($_SESSION['_gid'])) {
				$url_dir = 'document.php?&curdirpath=/';
				$interbreadcrumb[] = array('url' => $url_dir, 'name' => get_lang('HomeDirectory'));
			}
		}
	}
	*/
	// Interbreadcrumb for the current directory root path
	if (empty($document_data['parents'])) {
		$interbreadcrumb[] = array('url' => '#', 'name' => $document_data['title']);
	} else {
		foreach($document_data['parents'] as $document_sub_data) {
			$interbreadcrumb[] = array('url' => $document_sub_data['document_url'], 'name' => $document_sub_data['title']);
		}
	}
Display :: display_header($nameTools, 'Doc');

echo '<div class="actions">';
		echo '<a href="document.php?id='.$document_id.'">'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('DocumentsOverview'),'',ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

if (api_browser_support('svg')){
	
	//automatic loading the course language
	$svgedit_code_translation_table = array('' => 'en', 'pt' => 'pt-Pt', 'sr' => 'sr_latn');
	$langsvgedit  = api_get_language_isocode();
	$langsvgedit = isset($svgedit_code_translation_table[$langsvgedit]) ? $svgedit_code_translation_table[$langsvgedit] : $langsvgedit;
	$langsvgedit = file_exists(api_get_path(LIBRARY_PATH).'svg-edit/locale/lang.'.$langsvgedit.'.js') ? $langsvgedit : 'en';
	?>
	<script type="text/javascript">
	if (window.innerHeight){
   		height_iframe = window.innerHeight -50;
	}else{   
		height_iframe = 550;
 	}

	document.write ('<iframe frameborder="0" scrolling="no" src="<?php echo api_get_path(WEB_LIBRARY_PATH).'svg-edit/svg-editor.php?lang='.$langsvgedit; ?>" width="100%" height="' + height_iframe + '"></iframe>');
	</script>
    <?php
    echo '<noscript>';
	echo '<iframe style=\'height: 550px; width: 100%;\' scrolling=\'no\' frameborder=\'0\' src=\''.api_get_path(WEB_LIBRARY_PATH).'svg-edit/svg-editor.php?lang='.$langsvgedit.'\'></iframe>';
	echo '</noscript>';
} else {	
	Display::display_error_message(get_lang('BrowserDontSupportsSVG'));
}

Display :: display_footer();
