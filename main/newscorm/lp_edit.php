<?php //$id: $
/**
 * Script allowing simple edition of learnpath information (title, description, etc)
 * @package dokeos.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Script
 */
$langFile[] = "learnpath";
$langFile[] = "scormdocument";
$langFile[] = "scorm";

$show_description_field = false; //for now
$nameTools = get_lang("Doc");
event_access_tool(TOOL_LEARNPATH);
if (! $is_allowed_in_course) api_not_allowed();
/**
 * Display
 */
$interbreadcrumb[]= array ("url"=>"lp_controller.php?action=list", "name"=> get_lang("_learning_path"));
$interbreadcrumb[]= array ("url"=>$_SERVER['PHP_SELF']."?action=admin_view&lp_id=$learnpath_id", "name" => $_SESSION['oLP']->get_name());
Display::display_header(null,'Path');
api_display_tool_title($nameTools);
if($my_version=='1.8'){
	Display::display_introduction_section(TOOL_LEARNPATH);
}else{
	api_introductionsection(TOOL_LEARNPATH);
}
?>
<form name="form1" method="post" action="lp_controller.php">
  <h4>
	<?php echo get_lang('_edit_learnpath'); ?>
  </h4>
  <table width="400" border="0" cellspacing="2" cellpadding="0">
	<tr>
	  <td align="right"><?php echo get_lang('_title');?></td>
	  <td><input name="lp_name" type="text" value="<?php echo $_SESSION['oLP']->get_name();?>" size="50" /></td>
	</tr>
	<?php if($show_description_field){ ?>
	<tr>
	  <td align="right" valign="top"><?php echo get_lang('_description');?></td>
	  <td><textarea name='lp_description' cols='45'><?php echo $_SESSION['oLP']->get_description();?></textarea></td>
	</tr>
	<?php } ?>
	<tr>
	  <td align="right"><?php echo get_lang('Charset');?></td>
	  <td><select name="lp_encoding">
	  <?php
	  $encodings = array('UTF-8','ISO-8859-1','ISO-8859-15','cp1251','cp1252','KOI8-R','BIG5','GB2312','Shift_JIS','EUC-JP');
	  foreach($encodings as $encoding){
	  	if($encoding == $_SESSION['oLP']->encoding){
	  		echo "	<option value='$encoding' selected='selected'>$encoding</option>\n";
	  	}else{
	  		echo "	<option value='$encoding'>$encoding</option>\n";
	  	}
	  }
	  ?></select></td>
	</tr>
	<tr>
	  <td align="right"><?php echo get_lang('Origin');?></td>
	  <td><select name="lp_maker">
	  <?php
	  include('content_makers.inc.php');
	  foreach($content_origins as $indx => $origin){
			echo '    <option value="'.$origin.'">'.$origin.'</option>' ;
	  }
	  ?></select></td>
	</tr>
	<tr>
	  <td align="right"><?php echo get_lang('Location');?></td>
	  <td><select name="lp_proximity">
	  <?php
		echo '    <option value="local" selected="selected">'.get_lang('Local')."</option>" .
		'    <option value="remote">'.get_lang('Remote')."</option>";
	  ?></select></td>
	</tr>
	<tr>
      <td align="right">&nbsp;</td>
		<input type="hidden" name="action" value="update_lp" />
		<input type="hidden" name="lp_id" value="<?php echo $_SESSION['oLP']->get_id(); ?>" />
		<td><input type="submit" name="Submit" value="<?php echo get_lang('Ok'); ?>" /></td>
	</tr>
  </table>
</form>
<?php
Display::display_footer();
?>