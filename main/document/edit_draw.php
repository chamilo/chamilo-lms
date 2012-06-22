<?php
/* For licensing terms, see /license.txt */

/**
 *	This file allows creating new svg and png documents with an online editor.
 *
 *	@package chamilo.document
 *
 * @author Juan Carlos Ra�a Trabado
 * @since 25/september/2010
*/
/**
 * Code
 */

/*	INIT SECTION */

$language_file = array('document');

require_once '../inc/global.inc.php';

$_SESSION['whereami'] = 'document/editdraw';
$this_section = SECTION_COURSES;

require_once api_get_path(SYS_CODE_PATH).'document/document.inc.php';

api_protect_course_script();
api_block_anonymous_users();

$document_data = DocumentManager::get_document_data_by_id($_GET['id'], api_get_course_id(), true);

if (empty($document_data)) {
    api_not_allowed();
} else {    
    $document_id    = $document_data['id'];
    $file_path      = $document_data['path'];
    $dir            = dirname($document_data['path']);
    $parent_id      = DocumentManager::get_document_id(api_get_course_info(), $dir);
    $my_cur_dir_path = Security::remove_XSS($_GET['curdirpath']);
}


$dir= str_replace('\\', '/',$dir);//and urlencode each url $curdirpath (hack clean $curdirpath under Windows - Bug #3261)

/* Constants & Variables */
$current_session_id=api_get_session_id();
$group_id = api_get_group_id();

//path for svg-edit save
$_SESSION['draw_dir']=Security::remove_XSS($dir);
if ($_SESSION['draw_dir']=='/'){
	$_SESSION['draw_dir'] = '';
}
$_SESSION['draw_file']=basename(Security::remove_XSS($file_path));
$get_file = Security::remove_XSS($file_path);
$file = basename($get_file);
$temp_file = explode(".",$file);
$filename=$temp_file[0];
$nameTools = get_lang('EditDocument') . ': '.$filename;

$courseDir   = $_course['path'].'/document';
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

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

//groups //TODO:clean
if (!empty($group_id)) {
	$req_gid = '&amp;gidReq='.$group_id;
	$interbreadcrumb[] = array ('url' => '../group/group_space.php?gidReq='.$group_id, 'name' => get_lang('GroupSpace'));
	$group_document = true;
	$noPHP_SELF = true;
}

$is_certificate_mode = DocumentManager::is_certificate_mode($dir);

if (!$is_certificate_mode)
	$interbreadcrumb[]= array("url" => "./document.php?curdirpath=".urlencode($my_cur_dir_path).$req_gid, "name"=> get_lang('Documents'));
else
	$interbreadcrumb[]= array ('url' => '../gradebook/'.$_SESSION['gradebook_dest'], 'name' => get_lang('Gradebook'));

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

$is_allowedToEdit = api_is_allowed_to_edit(null, true) || $_SESSION['group_member_with_upload_rights'] || is_my_shared_folder(api_get_user_id(), $dir, $current_session_id);

if (!$is_allowedToEdit) {
	api_not_allowed(true);
}

event_access_tool(TOOL_DOCUMENT);

Display :: display_header($nameTools, 'Doc');
echo '<div class="actions">';
		echo '<a href="document.php?id='.$parent_id.'">'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('DocumentsOverview'),'',ICON_SIZE_MEDIUM).'</a>';		
		echo '<a href="edit_document.php?'.api_get_cidreq().'&id='.$document_id.$req_gid.'&origin=editdraw">'.Display::return_icon('edit.png',get_lang('Rename').'/'.get_lang('Comments'),'',ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

if (api_browser_support('svg')) {	
	//automatic loading the course language
	$svgedit_code_translation_table = array('' => 'en', 'pt' => 'pt-Pt', 'sr' => 'sr_latn');
	$langsvgedit  = api_get_language_isocode();
	$langsvgedit = isset($svgedit_code_translation_table[$langsvgedit]) ? $svgedit_code_translation_table[$langsvgedit] : $langsvgedit;
	$langsvgedit = file_exists(api_get_path(LIBRARY_PATH).'svg-edit/locale/lang.'.$langsvgedit.'.js') ? $langsvgedit : 'en';
	
	$svg_url= api_get_path(WEB_LIBRARY_PATH).'svg-edit/svg-editor.php?url=../../../../courses/'.$courseDir.$dir.$file.'&amp;lang='.$langsvgedit;
		
	?>

	<script type="text/javascript">
	
	document.write ('<iframe id="frame" frameborder="0" scrolling="no" src="<?php echo  $svg_url; ?>" width="100%" height="100%"><noframes><p>Sorry, your browser does not handle frames</p></noframes></iframe>');
	function resizeIframe() {
    	var height = window.innerHeight -50;
		//max lower size
		if (height<550) {
			height=550;
		}
    	document.getElementById('frame').style.height = height +"px";
	};
	document.getElementById('frame').onload = resizeIframe;
	window.onresize = resizeIframe;
	
	</script>

    <?php
    echo '<noscript>';
	echo '<iframe style="height: 550px; width: 100%;" scrolling="no" frameborder="0\' src="'.$svg_url.'"<noframes><p>Sorry, your browser does not handle frames</p></noframes></iframe>';
	echo '</noscript>';
} else {	
	Display::display_error_message(get_lang('BrowserDontSupportsSVG'));
}
Display::display_footer();
