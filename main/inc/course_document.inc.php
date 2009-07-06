<?php
$language_file = array('create_course', 'document');
include_once('global.inc.php');
/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included 

require_once api_get_path(INCLUDE_PATH).'lib/fckeditor/repository.php';

include(api_get_path(SYS_CODE_PATH).'document/document.inc.php');

//if(!$is_in_admin){
if(!api_is_platform_admin()){
	api_protect_course_script();
}

//session
if(isset($_GET['id_session']))
	$_SESSION['id_session'] = $_GET['id_session'];

$htmlHeadXtra[] =
"<script type=\"text/javascript\">
function confirmation (name)
{
	if (confirm(\" ". api_convert_encoding(get_lang('AreYouSureToDelete'), 'UTF-8', $charset) ." \"+ name + \" ?\"))
		{return true;}
	else
		{return false;}
}
</script>";

/*
-----------------------------------------------------------
	Variables
	- some need defining before inclusion of libraries
-----------------------------------------------------------
*/

$sType = isset($sType) ? $sType : '';

if($sType=="MP3") $sType="audio";

// Resource type
$sType = strtolower($sType);

// Choosing the repository to be used.
if (api_is_in_course())
{
	if (!api_is_in_group())
	{
		// 1. We are inside a course and not in a group.
		if (api_is_allowed_to_edit())
		{
			// 1.1. Teacher
			$base_work_dir = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/document/';
			$http_www = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/';
		}
		else
		{
			// 1.2. Student
			$base_work_dir = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/document/shared_folder/'.api_get_user_id().'/';
			$http_www = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/shared_folder/'.api_get_user_id().'/';
		}
	}
	else
	{
		// 2. Inside a course and inside a group.
		$base_work_dir = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/document'.$group_properties['directory'].'/';
		$http_www = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document'.$group_properties['directory'].'/';
	}
}
else
{
	if (api_is_platform_admin() && $_SESSION['this_section'] == 'platform_admin')
	{
		// 3. Platform administration activities.
		$base_work_dir = $_configuration['root_sys'].'home/default_platform_document/';
		$http_www = $_configuration['root_web'].'home/default_platform_document/';
	}
	else
	{
		// 4. The user is outside courses.
		$base_work_dir = $_configuration['root_sys'].'main/upload/users/'.api_get_user_id().'/my_files/';
		$http_www = $_configuration['root_web'].'main/upload/users/'.api_get_user_id().'/my_files/';
	}
}

// Set the upload path according to the resource type.
if ($sType == 'audio')
{
	check_and_create_resource_directory($base_work_dir, '/audio', get_lang('Audio'));
	$base_work_dir = $base_work_dir.'audio/';
	$http_www = $http_www.'audio/';
	$path = "/audio/";
}
elseif ($sType == 'flash')
{
	check_and_create_resource_directory($base_work_dir, '/flash', get_lang('Flash'));
	$base_work_dir = $base_work_dir.'flash/';
	$http_www = $http_www.'flash/';
	$path = "/flash/";
}
elseif ($sType == 'images')
{
	check_and_create_resource_directory($base_work_dir, '/images', get_lang('Images'));
	$base_work_dir = $base_work_dir.'images/';
	$http_www = $http_www.'images/';
	$path = "/images/";
}
elseif ($sType == 'video')
{
	check_and_create_resource_directory($base_work_dir, '/video', get_lang('Video'));
	$base_work_dir = $base_work_dir.'video/';
	$http_www = $http_www.'video/';
	$path = "/video/";
}
elseif ($sType == 'video/flv')
{
	check_and_create_resource_directory($base_work_dir, '/video', get_lang('Video'));
	check_and_create_resource_directory($base_work_dir, '/video/flv', 'flv');
	$base_work_dir = $base_work_dir.'video/flv/';
	$http_www = $http_www.'video/flv/';
	$path = "/video/flv/";
}

$course_dir   = $_course['path']."/document/".$sType;
$sys_course_path = api_get_path(SYS_COURSE_PATH);

$dbl_click_id = 0; // used to avoid double-click
$is_allowed_to_edit = api_is_allowed_to_edit();

$req_gid = '';

/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/
//libraries are included by default

include_once(api_get_path(LIBRARY_PATH) . 'fileDisplay.lib.php');
include_once(api_get_path(LIBRARY_PATH) . 'events.lib.inc.php');
include_once(api_get_path(LIBRARY_PATH) . 'document.lib.php');
include_once(api_get_path(LIBRARY_PATH) . 'tablesort.lib.php');

/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/

$course_quota = DocumentManager::get_course_quota();

/*
==============================================================================
		MAIN SECTION
==============================================================================
*/


/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/

$tool_name = get_lang("Doc"); // title of the page (should come from the language file)

?>
<style type="text/css" media="screen, projection">
/*<![CDATA[*/
@import "<?php echo api_get_path(WEB_CODE_PATH); ?>css/public_admin/default.css";
/*]]>*/
</style>

<?php
if(api_get_setting('stylesheets')<>'')
{
?>
<style type="text/css" media="screen, projection">
/*<![CDATA[*/
@import "<?php echo api_get_path(WEB_CODE_PATH); ?>css/<?php echo api_get_setting('stylesheets');?>/default.css";
/*]]>*/
</style>

<?php
}

$is_allowed_to_edit  = api_is_allowed_to_edit();

if($is_allowed_to_edit) // TEACHER ONLY
{

	/*======================================
			DELETE FILE OR DIRECTORY
	  ======================================*/

	if ( isset($_GET['delete']) )
	{	
		include_once(api_get_path(LIBRARY_PATH) . 'fileManage.lib.php');
		if(DocumentManager::delete_document($_course,$_GET['delete'],$base_work_dir))
		{
			Display::display_normal_message(api_convert_encoding(get_lang('DocDeleted'), 'UTF-8', $charset));
		}
		else 
		{
			Display::display_normal_message(api_convert_encoding(get_lang('DocDeleteError'), 'UTF-8', $charset));
		}
	}

	if( isset($_POST['action']))
	{
		switch($_POST['action'])
		{
			case 'delete':
				foreach($_POST['path'] as $index => $path)
				{
					DocumentManager::delete_document($_course,$path,$base_work_dir);
				}	
				Display::display_normal_message(api_convert_encoding(get_lang('DocDeleted'), 'UTF-8', $charset));
				break;
		}	
	}
}

/*
-----------------------------------------------------------
	GET ALL DOCUMENT DATA FOR CURDIRPATH
-----------------------------------------------------------
*/

$docs_and_folders = getlist ($base_work_dir.'/');

?>

<?php
if($docs_and_folders)
{
	//echo('<pre>');
	//print_r($docs_and_folders);
	//echo('</pre>');
	//*************************************************************************************************
	//do we need the title field for the document name or not?
	//we get the setting here, so we only have to do it once
	$use_document_title = api_get_setting('use_document_title');
	//create a sortable table with our data
	$sortable_data = array();
	while (list ($key, $id) = each($docs_and_folders))
	{
		// Skip directories.
		if ($id['filetype'] != 'file')
		{
			continue;
		}

		$row = array ();

		//if the item is invisible, wrap it in a span with class invisible
		$invisibility_span_open = ($id['visibility']==0)?'<span class="invisible">':'';
		$invisibility_span_close = ($id['visibility']==0)?'</span>':'';
		//size (or total size of a directory)
		$size = $id['filetype']=='folder' ? get_total_folder_size($id['path'],$is_allowed_to_edit) : $id[size];
		//get the title or the basename depending on what we're using
		if ($use_document_title=='true' AND $id['title']<>'')
		{
			 $document_name=$id['title'];
		}
		else 
		{
			$document_name=basename($id['path']);
		}
		//$row[] = $key; //testing
		//data for checkbox
		/*
		if ($is_allowed_to_edit AND count($docs_and_folders)>1)
		{
			$row[] = $id['path'];
		}
		*/
		// icons with hyperlinks
		$row[]= '<a href="#" onclick="javascript:OpenFile(\''.$http_www.'/'.$id['title'].'\', \''.$sType.'\');return false;">'.build_document_icon_tag($id['filetype'],$id['path']).'</a>';
		//document title with hyperlink
		$row[] = '<a href="#" onclick="javascript:OpenFile(\''.$http_www.'/'.$id['title'].'\', \''.$sType.'\');return false;">'.$id['title'].'</a>';
		//comments => display comment under the document name
		//$row[] = $invisibility_span_open.nl2br(htmlspecialchars($id['comment'])).$invisibility_span_close;
		$display_size = format_file_size($size);
		$row[] = '<span style="display:none;">'.$size.'</span>'.$invisibility_span_open.$display_size.$invisibility_span_close; 
		//last edit date
		$display_date = format_date(strtotime($id['lastedit_date']));
		$row[] = '<span style="display:none;">'.$id['lastedit_date'].'</span>'.$invisibility_span_open.$display_date.$invisibility_span_close; 
		
		$sortable_data[] = $row;
	}
	//*******************************************************************************************

}
else 
{
	$sortable_data=array();
	//$table_footer='<div style="text-align:center;"><strong>'.get_lang('NoDocsInFolder').'</strong></div>';
}

$table = new SortableTableFromArray($sortable_data,4,10);
$query_vars['curdirpath'] = $curdirpath;
if(isset($_SESSION['_gid']))
{
	$query_vars['gidReq'] = $_SESSION['_gid'];
}
$table->set_additional_parameters($query_vars);
$column = 0;
/*
if ($is_allowed_to_edit AND count($docs_and_folders)>1)
{
	$table->set_header($column++,'',false);
}
*/

$table->set_header($column++, api_htmlentities(get_lang('Type'), ENT_QUOTES, $charset));
$table->set_header($column++, api_htmlentities(get_lang('Title'), ENT_QUOTES, $charset));

//$column_header[] = array(get_lang('Comment'),true);  => display comment under the document name
$table->set_header($column++, api_htmlentities(get_lang('Size'), ENT_QUOTES, $charset));
$table->set_header($column++, api_htmlentities(get_lang('Date'), ENT_QUOTES, $charset));

//currently only delete action -> take only DELETE right into account
/*
if (count($docs_and_folders)>1)
{
	if ($is_allowed_to_edit)
	{
		$form_actions = array();
		$form_action['delete'] = get_lang('Delete');
		$table->set_form_actions($form_action,'path');
	}
}
*/

echo api_convert_encoding($table->get_table_html(), 'UTF-8', $charset);
echo api_convert_encoding($table_footer, 'UTF-8', $charset);

//////////  functions ////////////

function getlist ($directory) {
	//global $delim, $win;

	if ($d = @opendir($directory)) {

		while (($filename = @readdir($d)) !== false) {

			$path = $directory . $filename;

			if ($filename != '.' && $filename != '..' && $filename != '.svn')
			{
				$file = array(
					"lastedit_date" =>date ("Y-m-d H:i:s", filemtime($path)),
					"visibility" => 1,
					"path" => $path,
					"title" => basename($path),
					"filetype" => filetype($path),
					"size" => filesize ($path)
				);

				$files[] = $file;
			}
		}

		return $files;
	} 
	else
	{
		return false;
	}
}

function check_and_create_resource_directory($repository_path, $resource_directory, $resource_directory_name)
{
	global $permissions_for_new_directories;

	$resource_directory_full_path = substr($repository_path, 0, strlen($repository_path) - 1) . $resource_directory . '/';

	if (!is_dir($resource_directory_full_path))
	{
		if (@mkdir($resource_directory_full_path, $permissions_for_new_directories))
		{
			// While we are in a course: Registering the newly created folder in the course's database.
			if (api_is_in_course())
			{
				global $_course, $_user;
				global $group_properties, $to_group_id;
				$group_directory = !empty($group_properties['directory']) ? $group_properties['directory'] : '';

				$doc_id = add_document($_course, $group_directory.$resource_directory, 'folder', 0, $resource_directory_name);
				api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'FolderCreated', $_user['user_id'], $to_group_id);
			}
			return true;
		}
		return false;
	}
	return true;
}

?>
<script type="text/javascript">
<!--
function OpenFile( fileUrl, type )
{
	if(type=="audio")
	{
		ret = confirm('<?php echo api_convert_encoding(get_lang('AutostartMp3'), 'UTF-8', $charset); ?>');
		if(ret==true)
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
