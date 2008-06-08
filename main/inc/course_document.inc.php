<?php
$language_file = 'document';
include_once('global.inc.php');
/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included 

include(api_get_path(SYS_CODE_PATH).'document/document.inc.php');

if(!$is_in_admin){
	api_protect_course_script();
}

//session
if(isset($_GET['id_session']))
	$_SESSION['id_session'] = $_GET['id_session'];

$htmlHeadXtra[] =
"<script type=\"text/javascript\">
function confirmation (name)
{
	if (confirm(\" ". get_lang("AreYouSureToDelete") ." \"+ name + \" ?\"))
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

//what's the current path?

$sType = isset($sType)?$sType:"Image";

if($sType=="MP3") $sType="audio";

$sType = strtolower($sType);

$course_dir   = $_course['path']."/document/".$sType;
$sys_course_path = api_get_path(SYS_COURSE_PATH);
$base_work_dir = $sys_course_path.$course_dir;
$http_www = api_get_path('WEB_COURSE_PATH').$_course['path'].'/document/'.$sType;
$dbl_click_id = 0; // used to avoid double-click
$is_allowed_to_edit = api_is_allowed_to_edit();


$to_group_id = 0;
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
			Display::display_normal_message(get_lang('DocDeleted'));
		}
		else 
		{
			Display::display_normal_message(get_lang('DocDeleteError'));
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
				Display::display_normal_message(get_lang('DocDeleted'));
				break;
		}	
	}


} // END is allowed to edit

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
	$use_document_title = get_setting('use_document_title');
	//create a sortable table with our data
	$sortable_data = array();
	while (list ($key, $id) = each($docs_and_folders))
	{
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
		//icons
		$row[]= build_document_icon_tag($id['filetype'],$id['path']);
		//document title with hyperlink
		$row[] = '<a href="#" onclick="OpenFile(\''.$http_www.'/'.$id['title'].'\', \''.$sType.'\');return false;">'.$id['title'].'</a>';
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
$table->set_header($column++,get_lang('Type'));
$table->set_header($column++,get_lang('Title'));

//$column_header[] = array(get_lang('Comment'),true);  => display comment under the document name
$table->set_header($column++,get_lang('Size'));
$table->set_header($column++,get_lang('Date'));



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

$table->display();
echo $table_footer;	

//////////  functions ////////////
function getlist ($directory) {
	//global $delim, $win;

	if ($d = @opendir($directory)) {

		while (($filename = @readdir($d)) !== false) {

			$path = $directory . $filename;

			if ($filename != '..')
			if ($filename != '.')
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
?>
<SCRIPT LANGUAGE="JavaScript">
<!--
function OpenFile( fileUrl, type )
{
	if(type=="audio")
	{
		ret = confirm('<?php echo get_lang('AutostartMp3') ?>');
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
</SCRIPT>