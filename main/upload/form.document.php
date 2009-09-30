<?php //$id: $
/**
 * Display part of the document sub-process for upload. This script MUST BE included by upload/index.php
 * as it prepares most of the variables needed here.
 * @package dokeos.upload
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Just display the form needed to upload a SCORM and give its settings
 */
$noPHP_SELF = false;
$nameTools = get_lang("FileUpload");
$interbreadcrumb[]= array ("url"=>"../newscorm/lp_controller.php?action=list", "name"=> get_lang(TOOL_DOCUMENT));
Display::display_header($nameTools,"Doc");
//show the title
api_display_tool_title($nameTools.$add_group_to_title);
?>

<div id="dynamic_div" style="display:block;margin-left:40%;margin-top:10px;height:50px;">
</div>
<div id="upload_form_div" name="form_div" style="display:block;">
		<?php
	/*
	<div id="folderselector">
		//form to select directory
		//$folders = DocumentManager::get_all_document_folders($_course,$to_group_id,$is_allowed_to_edit);
		//include_once('../document/document.inc.php'); //this should be removed when the functions there are moved to inc/lib
		//echo(build_directory_selector($folders,$path,$group_properties['directory']));
	</div>
	*/
		?>

	<form method="POST" action="upload.php" id="upload_form" enctype="multipart/form-data" onsubmit="myUpload.start('dynamic_div','progressbar_green.gif','<?php echo(get_lang('Uploading'));?>','upload_form_div');">
		<input type="hidden" name="curdirpath" value="<?php echo $path; ?>">
		<input type="hidden" name="tool" value="<?php echo $my_tool; ?>">
		<input type="file" name="user_file">
		<input type="submit" name="submit" value="Upload">
	</form>
</div>
<br/>
<?php
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>