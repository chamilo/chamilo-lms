<?php
/* For licensing terms, see /license.txt */

/*		INIT SECTION */

$language_file = array('create_course', 'document');
require 'global.inc.php';

/*	Libraries */

require_once api_get_path(LIBRARY_PATH).'course_document.lib.php';
require_once api_get_path(LIBRARY_PATH).'fckeditor/repository.php';
require_once api_get_path(SYS_CODE_PATH).'document/document.inc.php';
require_once api_get_path(LIBRARY_PATH).'fileDisplay.lib.php';
require_once api_get_path(LIBRARY_PATH).'document.lib.php';
//require_once api_get_path(LIBRARY_PATH).'tablesort.lib.php'; moved to autoload
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';

//if(!$is_in_admin){
if (!api_is_platform_admin()){
	api_protect_course_script();
}

//session
if(isset($_GET['id_session'])) {
	$_SESSION['id_session'] = intval($_GET['id_session']);
}

$htmlHeadXtra[] =
"<script type=\"text/javascript\">
function confirmation (name)
{
	if (confirm(\" ". api_utf8_encode(get_lang('AreYouSureToDelete')) ." \"+ name + \" ?\"))
		{return true;}
	else
		{return false;}
}
</script>";

/*	Variables
	- some need defining before inclusion of libraries */

$sType = isset($sType) ? $sType : '';

if ($sType=="MP3") $sType="audio";

// Resource type
$sType = strtolower($sType);

// Choosing the repository to be used.
if (api_is_in_course()) {
	if (!api_is_in_group()) {
		// 1. We are inside a course and not in a group.
		if (api_is_allowed_to_edit()) {
			// 1.1. Teacher
			$base_work_dir = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/document/';
			$http_www = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/';
		} else {
			// 1.2. Student
			$base_work_dir = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/document/shared_folder/'.api_get_user_id().'/';
			$http_www = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/shared_folder/'.api_get_user_id().'/';
		}
	} else {
		// 2. Inside a course and inside a group.
		$base_work_dir = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/document'.$group_properties['directory'].'/';
		$http_www = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document'.$group_properties['directory'].'/';
	}
} else {
	if (api_is_platform_admin() && $_SESSION['this_section'] == 'platform_admin') {
		// 3. Platform administration activities.
		$base_work_dir = $_configuration['root_sys'].'home/default_platform_document/';
		$http_www = $_configuration['root_web'].'home/default_platform_document/';
	} else {
		// 4. The user is outside courses.
        $my_path = UserManager::get_user_picture_path_by_id(api_get_user_id(),'system');
		$base_work_dir = $my_path['dir'].'my_files/';
        $my_path = UserManager::get_user_picture_path_by_id(api_get_user_id(),'web');
		$http_www = $my_path['dir'].'my_files/';
	}
}

// Set the upload path according to the resource type.
if ($sType == 'audio') {
	check_and_create_resource_directory($base_work_dir, '/audio', get_lang('Audio'));
	$base_work_dir = $base_work_dir.'audio/';
	$http_www = $http_www.'audio/';
	$path = "/audio/";
} elseif ($sType == 'flash') {
	check_and_create_resource_directory($base_work_dir, '/flash', get_lang('Flash'));
	$base_work_dir = $base_work_dir.'flash/';
	$http_www = $http_www.'flash/';
	$path = "/flash/";
} elseif ($sType == 'images') {
	check_and_create_resource_directory($base_work_dir, '/images', get_lang('Images'));
	$base_work_dir = $base_work_dir.'images/';
	$http_www = $http_www.'images/';
	$path = "/images/";
} elseif ($sType == 'video') {
	check_and_create_resource_directory($base_work_dir, '/video', get_lang('Video'));
	$base_work_dir = $base_work_dir.'video/';
	$http_www = $http_www.'video/';
	$path = "/video/";
} elseif ($sType == 'video/flv') {
	check_and_create_resource_directory($base_work_dir, '/video', get_lang('Video'));
	check_and_create_resource_directory($base_work_dir, '/video/flv', 'flv');
	$base_work_dir = $base_work_dir.'video/flv/';
	$http_www = $http_www.'video/flv/';
	$path = "/video/flv/";
}

$course_dir   = $_course['path'].'/document/'.$sType;
$sys_course_path = api_get_path(SYS_COURSE_PATH);

$dbl_click_id = 0; // used to avoid double-click
$is_allowed_to_edit = api_is_allowed_to_edit();

$req_gid = '';

/*	Constants and variables */

$course_quota = DocumentManager::get_course_quota();

/*		MAIN SECTION */

/*	Header */

$tool_name = get_lang('Doc'); // Title of the page (should come from the language file)

?>
<style type="text/css" media="screen, projection">
/*<![CDATA[*/
@import "<?php echo api_get_path(WEB_CSS_PATH); ?>public_admin/default.css";
/*]]>*/
</style>

<?php
if(api_get_setting('stylesheets')<>'')
{
?>
<style type="text/css" media="screen, projection">
/*<![CDATA[*/
@import "<?php echo api_get_path(WEB_CSS_PATH), api_get_setting('stylesheets'); ?>/default.css";
/*]]>*/
</style>

<?php
}

$is_allowed_to_edit  = api_is_allowed_to_edit();

if ($is_allowed_to_edit) { // TEACHER ONLY

	/*	DELETE FILE OR DIRECTORY */

	if (isset($_GET['delete'])) {
		if (DocumentManager::delete_document($_course,$_GET['delete'], $base_work_dir)) {
			Display::display_normal_message(api_utf8_encode(get_lang('DocDeleted')));
		} else {
			Display::display_normal_message(api_utf8_encode(get_lang('DocDeleteError')));
		}
	}

	if (isset($_POST['action'])) {
		switch ($_POST['action']) {
			case 'delete':
				foreach ($_POST['path'] as $index => $path) {
					DocumentManager::delete_document($_course, $path, $base_work_dir);
				}
				Display::display_normal_message(api_utf8_encode(get_lang('DocDeleted')));
				break;
		}
	}
}

/*	GET ALL DOCUMENT DATA FOR CURDIRPATH */

$docs_and_folders = getlist ($base_work_dir.'/');

if ($docs_and_folders) {
	//do we need the title field for the document name or not?
	//we get the setting here, so we only have to do it once
	$use_document_title = api_get_setting('use_document_title');
	//create a sortable table with our data
	$sortable_data = array();
	while (list ($key, $id) = each($docs_and_folders)) {
		// Skip directories.
		if ($id['filetype'] != 'file') {
			continue;
		}

		$row = array ();

		//if the item is invisible, wrap it in a span with class invisible
		$invisibility_span_open = ($id['visibility'] == 0) ? '<span class="invisible">' : '';
		$invisibility_span_close = ($id['visibility'] == 0) ? '</span>' : '';
		//size (or total size of a directory)
		$size = $id['filetype'] == 'folder' ? get_total_folder_size($id['path'], $is_allowed_to_edit) : $id[size];
		//get the title or the basename depending on what we're using
		if ($use_document_title == 'true' AND $id['title'] != '') {
			 $document_name=$id['title'];
		} else {
			$document_name = basename($id['path']);
		}
		//$row[] = $key; //testing
		//data for checkbox
		/*
		if ($is_allowed_to_edit AND count($docs_and_folders) > 1) {
			$row[] = $id['path'];
		}
		*/
		// icons with hyperlinks
		$row[]= '<a href="#" onclick="javascript: OpenFile(\''.$http_www.'/'.$id['title'].'\', \''.$sType.'\');return false;">'.build_document_icon_tag($id['filetype'],$id['path']).'</a>';
		//document title with hyperlink
		$row[] = '<a href="#" onclick="javascript: OpenFile(\''.$http_www.'/'.$id['title'].'\', \''.$sType.'\');return false;">'.$id['title'].'</a>';
		//comments => display comment under the document name
		//$row[] = $invisibility_span_open.nl2br(htmlspecialchars($id['comment'])).$invisibility_span_close;
		$display_size = format_file_size($size);
		$row[] = '<span style="display:none;">'.$size.'</span>'.$invisibility_span_open.$display_size.$invisibility_span_close;
		//last edit date
		$display_date = format_date(strtotime($id['lastedit_date']));
		$row[] = '<span style="display:none;">'.$id['lastedit_date'].'</span>'.$invisibility_span_open.$display_date.$invisibility_span_close;

		$sortable_data[] = $row;
	}
} else {
	$sortable_data = array();
	//$table_footer='<div style="text-align:center;"><strong>'.get_lang('NoDocsInFolder').'</strong></div>';
}

$table = new SortableTableFromArray($sortable_data, 4, 10);
$query_vars['curdirpath'] = $curdirpath;
if (isset($_SESSION['_gid'])) {
	$query_vars['gidReq'] = $_SESSION['_gid'];
}
$table->set_additional_parameters($query_vars);
$column = 0;
/*
if ($is_allowed_to_edit AND count($docs_and_folders) > 1) {
	$table->set_header($column++, '', false);
}
*/

$table->set_header($column++, api_htmlentities(get_lang('Type'), ENT_QUOTES));
$table->set_header($column++, api_htmlentities(get_lang('Title'), ENT_QUOTES));

//$column_header[] = array(get_lang('Comment'),true);  => display comment under the document name
$table->set_header($column++, api_htmlentities(get_lang('Size'), ENT_QUOTES));
$table->set_header($column++, api_htmlentities(get_lang('Date'), ENT_QUOTES));

//currently only delete action -> take only DELETE right into account
/*
if (count($docs_and_folders) > 1) {
	if ($is_allowed_to_edit) {
		$form_actions = array();
		$form_action['delete'] = get_lang('Delete');
		$table->set_form_actions($form_action, 'path');
	}
}
*/

echo api_utf8_encode($table->get_table_html());
echo api_utf8_encode($table_footer);

// Functions

?>
<script type="text/javascript">
<!--
function OpenFile( fileUrl, type )
{
	if (type=="audio")
	{
		ret = confirm('<?php echo api_utf8_encode(get_lang('AutostartMp3')); ?>');
		if (ret)
		{
			GetE('autostart').checked = true;
		}
		else
		{
			GetE('autostart').checked = false;
		}
	}
	SetUrl( fileUrl ) ;
	//window.close() ;
}
//-->
</script>
