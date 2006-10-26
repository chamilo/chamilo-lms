<?php //$id: $
/**
 * Display part of the SCORM sub-process for upload. This script MUST BE included by upload/index.php
 * as it prepares most of the variables needed here.
 * @package dokeos.upload
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Small function to list files in garbage/
 */
function get_zip_files_in_garbage(){
	$list = array();
	$dh = opendir(api_get_path(SYS_CODE_PATH).'garbage/');
	if($dh === false){
		//ignore
	}else{
		while($entry = readdir($dh)){
			if(substr($entry,0,1) == '.'){/*ignore files starting with . */}
			else
			{
				if(preg_match('/^.*\.zip$/i',$entry)){
					$list[] = $entry;
				}
			}
		}
		natcasesort($list);
		closedir($dh);
	}
	return $list;
}
/**
 * Just display the form needed to upload a SCORM and give its settings
 */
$nameTools = get_lang("FileUpload");
$interbreadcrumb[]= array ("url"=>"../newscorm/lp_controller.php?action=list", "name"=> get_lang(TOOL_LEARNPATH));
Display::display_header($nameTools,"Path");
//show the title
api_display_tool_title(get_lang("learnpath")." - ".$nameTools.$add_group_to_title);
?>

<div id="dynamic_div" style="display:block;margin-left:40%;margin-top:10px;height:50px;">
</div>
<div id="upload_form_div" name="form_div" style="display:block;">
	<table border="0">
	<form method="POST" action="upload.php" id="upload_form" enctype="multipart/form-data" onsubmit="myUpload.start('dynamic_div','../img/progress_bar.gif','<?php echo(get_lang('Uploading'));?>','upload_form_div');">
		<input type="hidden" name="curdirpath" value="<?php echo $path; ?>">
		<input type="hidden" name="tool" value="<?php echo $my_tool; ?>">
		<tr>
<?php
	echo '<td>'.get_lang('FileToUpload').'</td>'."\n";
	echo '<td><input type="file" name="user_file"></td>'."\n";
	echo '</tr><tr>'."\n";
	echo '<td>'.get_lang('ContentMaker').'</td>'."\n";
	include('../newscorm/content_makers.inc.php');
	echo	'<td><select name="content_maker">'."\n";
  	foreach($content_origins as $indx => $origin){
  		if($indx == 1){
			echo '			<option value="'.$origin.'" selected="selected">'.$origin.'</option>';
  		}else{
			echo '			<option value="'.$origin.'">'.$origin.'</option>';
  		}
	}
	echo 	"  </<select></td>\n";
	echo '</tr><tr>'."\n";
	echo '<td>'.get_lang('ContentProximity').'</td>'."\n";
	echo 	'  <td><select name="content_proximity">'."\n" .
			'    <option value="local" selected="selected">'.get_lang('Local').'</option>' .
			'    <option value="remote">'.get_lang('Remote').'</option>' .
			"  </select></td>\n" ;
	echo '</tr><tr>'."\n";
	echo '<td colspan="2" align="right"><input type="submit" name="submit" value="'.get_lang('Download').'"></td>';
	echo '</tr><tr>'."\n";
	echo '<td colspan="2">&nbsp;</td>'."\n";
	echo '</tr><tr>'."\n";
	echo '<td>'.get_lang('Or').' '.strtolower(get_lang('UploadLocalFileFromGarbageDir')).'</td>'."\n";
	$list = get_zip_files_in_garbage();
	if(count($list)>0){
		echo '<td><select name="file_name">'."\n";
		foreach($list as $file){
			echo '  <option value="'.$file.'">'.$file.'</option>'."\n";
		}
		echo '</select></td>'."\n";	
		echo '</tr><tr>'."\n";
		echo '<td colspan="2" align="right"><input type="submit" name="submit" value="'.get_lang('Download').'"></td>';
		echo '<td></td>'."\n";
	}else{
		echo '<td align="center">{'.get_lang('Empty').'}</td>';
	}
	echo '</tr>'."\n";
?>
	</form>
	</table>
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