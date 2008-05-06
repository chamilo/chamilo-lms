<?php
/**
 * The main GUI for the ImageManager.
 * @author $Author: Wei Zhuo $
 * @version $Id: manager.php 26 2004-03-31 02:35:21Z Wei Zhuo $
 * @package ImageManager
 */

require_once('config.inc.php');
require_once('Classes/ImageManager.php');

$manager = new ImageManager($IMConfig);
$dirs = $manager->getDirs();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
<title><?php echo get_lang('InsertImage'); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="assets/manager.css" rel="stylesheet" type="text/css" />	

<script type="text/javascript">

	var thumbdir = "<?php echo $IMConfig['thumbnail_dir']; ?>";
	var base_url = "<?php echo $manager->getBaseURL(); ?>";
	var server_name = "<?php echo $IMConfig['server_name']; ?>";
	
	<?php
	//It's a teacher
		if(api_is_allowed_to_edit())
		{
			echo "window.resizeTo(600, 500);";
		}
		else{
			echo "window.resizeTo(600, 125);";
		}
	?>

	if(window.opener.ImageManager && window.opener.ImageManager.I18N)
	{
		I18N = window.opener.ImageManager.I18N;
	}

	// language object not found?
	if (!this.I18N)
	{
		// Read it now - copy in next script block
		document.write('<script type="text/javascript" src="lang/' + window.opener._editor_lang + '.js"><\/script>');
	}
	
	function showAdvancedSettings(){
		if(document.getElementById("advanced_settings").style.display=="none"){
			document.getElementById("advanced_settings").style.display="block";
		}
		else{
			document.getElementById("advanced_settings").style.display="none";
		}
	}
	
</script>

<script type="text/javascript">

	// now copy the language object of the included script - needed a seperate new script block to be able to do so
	if (!this.I18N)
	{
		I18N = this.ImageManager.I18N;
	}

</script>

<script type="text/javascript" src="assets/popup.js"></script>
<script type="text/javascript" src="assets/dialog.js"></script>
<script type="text/javascript" src="assets/manager.js"></script>

</head>

<body>
<div class="title"><?php echo get_lang('InsertImage'); ?></div>
<form action="images.php<?php if(isset($_GET['uploadPath']) && $_GET['uploadPath']!="") echo "?uploadPath=".$_GET['uploadPath']; ?>" id="uploadForm" method="post" enctype="multipart/form-data">

<fieldset <?php if(!api_is_allowed_to_edit()) echo "style='display: none;'"; ?>>
<legend><?php echo get_lang('ImageManager'); ?></legend>
<div class="dirs">
	<label for="dirPath"><?php echo ucwords(get_lang('directory')); ?></label>
	<select name="dir" class="dirWidth" id="dirPath" onchange="updateDir(this)">
	<option value="/">/</option>
		<?php foreach($dirs as $relative=>$fullpath) { ?>
				<option value="<?php echo rawurlencode($relative); ?>"><?php echo $relative; ?></option>
		<?php } ?>
			</select>
			<a href="#" onclick="javascript: goUpDir();" title="<?php echo get_lang('Up'); ?>">
			<img src="img/btnFolderUp.gif" height="15" width="15" alt="<?php echo get_lang('Up'); ?>" /></a>
		<?php if($IMConfig['safe_mode'] == false && $IMConfig['allow_new_dir']) { ?>
			<a href="#" onclick="newFolder();" title="New Folder">
				<img src="img/btnFolderNew.gif" height="15" width="15" alt="New Folder" />
			</a>
		<?php } ?>
	<div id="messages" style="display: none;">
	<span id="message"></span>
	<img src="img/dots.gif" width="22" height="12" alt="..." /></div>
	<iframe src="images.php<?php if(isset($_GET['uploadPath']) && $_GET['uploadPath']!="") echo "?uploadPath=".$_GET['uploadPath']; ?>" name="imgManager" id="imgManager" class="imageFrame" scrolling="auto" title="Image Selection" frameborder="0"></iframe>
</div>
</fieldset>


<!-- image properties -->
<input type="file" name="upload" id="upload"/>&nbsp;<button type="submit" name="submit" onclick="doUpload();"/><?php echo get_lang('Send'); ?></button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		  <button type="button" class="buttons" onclick="return refresh();" style="display: none"><?php echo get_lang('Regresh'); ?></button>
          <button type="button" class="buttons" onclick="return onOK();"><?php echo get_lang('Ok'); ?></button>
          <button type="button" class="buttons" onclick="return onCancel();"><?php echo get_lang('Cancel'); ?></button>
		  <?php
		  
		  if(api_is_allowed_to_edit()){
		  ?>
		  	<span style="cursor: pointer" onclick="showAdvancedSettings();"><?php echo utf8_encode(get_lang('AdvancedSettings'));?></span>
		  	
		  <?php
		  }
		  ?>
		  
	<table class="inputTable" style="display: none" id="advanced_settings">
		<tr>
			<td align="right">			
				<label for="f_url"><?php echo get_lang('ImageFile'); ?></label></td>				
			<td>
				<input type="text" id="f_url" class="largelWidth" value="" /></td>
			<td rowspan="3" align="right">&nbsp;</td>
			<td align="right">
			
			<label for="f_width"><?php echo get_lang('Width'); ?></label></td>
			
			<td><input type="text" id="f_width" class="smallWidth" value="" onchange="javascript:checkConstrains('width');"/></td>
			<td rowspan="2" align="right"><img src="img/locked.gif" id="imgLock" width="25" height="32" alt="Constrained Proportions" /></td>
			<td rowspan="3" align="right">&nbsp;</td>			
			<td align="right">
			
			<label for="f_vert"><?php echo get_lang('VSpace'); ?></label></td>
			
			<td><input type="text" id="f_vert" class="smallWidth" value="" /></td>
		</tr>
		<tr>
			<td align="right">
			<label for="f_alt"><?php echo get_lang('Alt'); ?></label></td>
			
			<td><input type="text" id="f_alt" class="largelWidth" value="" /></td>
			<td align="right">
			<label for="f_height"><?php echo get_lang('Height'); ?></label></td>
			
			<td><input type="text" id="f_height" class="smallWidth" value="" onchange="javascript:checkConstrains('height');"/></td>
			<td align="right">
			<label for="f_horiz"><?php echo get_lang('HSpace'); ?></label></td>
			
			<td><input type="text" id="f_horiz" class="smallWidth" value="" /></td>
		</tr>
		<tr>
<?php if($IMConfig['allow_upload'] == true) { ?>
			
<?php } else { ?>
			<td colspan="2"></td>
<?php } ?>
			<td align="right"><label for="f_align"><?php echo get_lang('Align'); ?></label></td>
			<td colspan="2">
				<select size="1" id="f_align"  title="Positioning of this image">
				  <option value=""                             >Not Set</option>
				  <option value="left"                         >Left</option>
				  <option value="right"                        >Right</option>
				  <option value="texttop"                      >Texttop</option>
				  <option value="absmiddle"                    >Absmiddle</option>
				  <option value="baseline" selected="selected" >Baseline</option>
				  <option value="absbottom"                    >Absbottom</option>
				  <option value="bottom"                       >Bottom</option>
				  <option value="middle"                       >Middle</option>
				  <option value="top"                          >Top</option>
				</select>
			</td>
			<td align="right"><label for="f_border"><?php echo get_lang('Border'); ?></label></td>
			<td><input type="text" id="f_border" class="smallWidth" value="" /></td>
		</tr>
		<tr>
         <td colspan="4" align="right">
				<input type="hidden" id="orginal_width" />
				<input type="hidden" id="orginal_height" />
            <input type="checkbox" id="constrain_prop" checked="checked" onclick="javascript:toggleConstrains(this);" />
          </td>
          <td colspan="5"><label for="constrain_prop"><?php echo get_lang('ConstrainProportions'); ?></label></td>
      </tr>
	</table>

	<input type="hidden" id="f_file" name="f_file" />
</form>
</body>
</html>