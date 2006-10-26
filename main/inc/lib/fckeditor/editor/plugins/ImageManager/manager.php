<?
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
<title>Insert Image</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="assets/manager.css" rel="stylesheet" type="text/css" />	

<script type="text/javascript">
/*<![CDATA[*/

	var thumbdir = "<? echo $IMConfig['thumbnail_dir']; ?>";
	var base_url = "<? echo $manager->getBaseURL(); ?>";
	var server_name = "<? echo $IMConfig['server_name']; ?>";

	window.resizeTo(600, 460);

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

/*]]>*/
</script>

<script type="text/javascript">
/*<![CDATA[*/

	// now copy the language object of the included script - needed a seperate new script block to be able to do so
	if (!this.I18N)
	{
		I18N = this.ImageManager.I18N;
	}

/*]]>*/
</script>

<script type="text/javascript" src="assets/popup.js"></script>
<script type="text/javascript" src="assets/dialog.js"></script>
<script type="text/javascript" src="assets/manager.js"></script>

</head>

<body>
<div class="title">Insert Image</div>
<form action="images.php" id="uploadForm" method="post" enctype="multipart/form-data">
<fieldset><legend>Image Manager</legend>
<div class="dirs">
	<label for="dirPath">Directory</label>
	<select name="dir" class="dirWidth" id="dirPath" onchange="updateDir(this)">
	<option value="/">/</option>
<? foreach($dirs as $relative=>$fullpath) { ?>
		<option value="<? echo rawurlencode($relative); ?>"><? echo $relative; ?></option>
<? } ?>
	</select>
	<a href="#" onclick="javascript: goUpDir();" title="Directory Up"><img src="img/btnFolderUp.gif" height="15" width="15" alt="Directory Up" /></a>
<? if($IMConfig['safe_mode'] == false && $IMConfig['allow_new_dir']) { ?>
	<a href="#" onclick="newFolder();" title="New Folder"><img src="img/btnFolderNew.gif" height="15" width="15" alt="New Folder" /></a>
<? } ?>
	<div id="messages" style="display: none;"><span id="message"></span><img SRC="img/dots.gif" width="22" height="12" alt="..." /></div>
	<iframe src="images.php" name="imgManager" id="imgManager" class="imageFrame" scrolling="auto" title="Image Selection" frameborder="0"></iframe>
</div>
</fieldset>
<!-- image properties -->
	<table class="inputTable">
		<tr>
			<td align="right"><label for="f_url">Image File</label></td>
			<td><input type="text" id="f_url" class="largelWidth" value="" /></td>
			<td rowspan="3" align="right">&nbsp;</td>
			<td align="right"><label for="f_width">Width</label></td>
			<td><input type="text" id="f_width" class="smallWidth" value="" onchange="javascript:checkConstrains('width');"/></td>
			<td rowspan="2" align="right"><img src="img/locked.gif" id="imgLock" width="25" height="32" alt="Constrained Proportions" /></td>
			<td rowspan="3" align="right">&nbsp;</td>
			<td align="right"><label for="f_vert">V Space</label></td>
			<td><input type="text" id="f_vert" class="smallWidth" value="" /></td>
		</tr>		
		<tr>
			<td align="right"><label for="f_alt">Alt</label></td>
			<td><input type="text" id="f_alt" class="largelWidth" value="" /></td>
			<td align="right"><label for="f_height">Height</label></td>
			<td><input type="text" id="f_height" class="smallWidth" value="" onchange="javascript:checkConstrains('height');"/></td>
			<td align="right"><label for="f_horiz">H Space</label></td>
			<td><input type="text" id="f_horiz" class="smallWidth" value="" /></td>
		</tr>
		<tr>
<? if($IMConfig['allow_upload'] == true) { ?>
			<td align="right"><label for="upload">Upload</label></td>
			<td>
				<table cellpadding="0" cellspacing="0" border="0">
                  <tr>
                    <td><input type="file" name="upload" id="upload"/></td>
                    <td>&nbsp;<button type="submit" name="submit" onclick="doUpload();"/>Upload</button></td>
                  </tr>
                </table>
			</td>
<? } else { ?>
			<td colspan="2"></td>
<? } ?>
			<td align="right"><label for="f_align">Align</label></td>
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
			<td align="right"><label for="f_border">Border</label></td>
			<td><input type="text" id="f_border" class="smallWidth" value="" /></td>
		</tr>
		<tr> 
         <td colspan="4" align="right">
				<input type="hidden" id="orginal_width" />
				<input type="hidden" id="orginal_height" />
            <input type="checkbox" id="constrain_prop" checked="checked" onclick="javascript:toggleConstrains(this);" />
          </td>
          <td colspan="5"><label for="constrain_prop">Constrain Proportions</label></td>
      </tr>
	</table>
<!--// image properties -->	
	<div style="text-align: right;"> 
          <hr />
		  <button type="button" class="buttons" onclick="return refresh();">Refresh</button>
          <button type="button" class="buttons" onclick="return onOK();">OK</button>
          <button type="button" class="buttons" onclick="return onCancel();">Cancel</button>
    </div>
	<input type="hidden" id="f_file" name="f_file" />
</form>
</body>
</html>