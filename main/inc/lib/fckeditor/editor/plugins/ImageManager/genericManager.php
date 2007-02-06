<?php
/**
 * The main GUI for the ImageManager.
 * @author $Author: Wei Zhuo $
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


	var thumbdir = "<?php echo $IMConfig['thumbnail_dir']; ?>";
	var base_url = "<?php echo $manager->getBaseURL(); ?>";
	var server_name = "<?php echo $IMConfig['server_name']; ?>";
	
	<?php
	//It's a teacher
		if(api_is_allowed_to_edit()){
			echo "window.resizeTo(600, 430);";
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
	
</script>

<script type="text/javascript">

	// now copy the language object of the included script - needed a seperate new script block to be able to do so
	if (!this.I18N)
	{
		I18N = this.ImageManager.I18N;
	}
	
	// Generic Manager function
	
	function onLoad() {
		var imageFileParent = window.opener.document.getElementsByName("imagefile")[0];
		var imageFile = window.document.getElementById("f_url");
		imageFile.value = imageFileParent.value; 
		var imageLabelParent = window.opener.document.getElementsByName("imageLabel")[0];
		var imageLabel = window.document.getElementById("f_alt");
		imageLabel.value = imageLabelParent.value;
		document.getElementById('advanced_settings').style.display='block';
	}
	
	 function onOKLocal() {
		var imageFileParent = window.opener.document.getElementsByName("imagefile")[0];
		var imageFile = window.document.getElementById("f_url");
		if(imageFile.value.indexOf('://') < 0 ) {
			imageFileParent.value = makeURL( base_url, imageFile.value );		
		} else {
			imageFileParent.value = imageFile.value;
		}
		
		var imageLabelParent = window.opener.document.getElementsByName("imageLabel")[0];
		var imageLabel = window.document.getElementById("f_alt");
		imageLabelParent.value = imageLabel.value;
		
		window.close();
	 }
	 
	 function onCancelLocal() {
	 	window.close();
	 }
	 
	 //similar to the Files::makeFile() in Files.php
	function makeURL(pathA, pathB) 
	{
		if(pathA.substring(pathA.length-1) != '/')
			pathA += '/';

		if(pathB.charAt(0) == '/');	
			pathB = pathB.substring(1);

		return pathA+pathB;
	}


</script>

<script type="text/javascript" src="assets/popup.js"></script>
<script type="text/javascript" src="assets/dialog.js"></script>
<script type="text/javascript" src="assets/manager.js"></script>

</head>

<body onload="onLoad();">
<div class="title">Insert Image</div>
<form action="images.php<?php if(isset($_GET['uploadPath']) && $_GET['uploadPath']!="") echo "?uploadPath=".$_GET['uploadPath']; ?>" id="uploadForm" method="post" enctype="multipart/form-data">

<fieldset <?php if(!api_is_allowed_to_edit()) echo "style='display: none;'"; ?>><legend>Image Manager</legend>
<div class="dirs">
	<label for="dirPath">Directory</label>
	<select name="dir" class="dirWidth" id="dirPath" onchange="updateDir(this)">
	<option value="/">/</option>
<?php foreach($dirs as $relative=>$fullpath) { ?>
		<option value="<?php echo rawurlencode($relative); ?>"><?php echo $relative; ?></option>
<?php } ?>
	</select>
	<a href="#" onclick="javascript: goUpDir();" title="Directory Up"><img src="img/btnFolderUp.gif" height="15" width="15" alt="Directory Up" /></a>
<?php if($IMConfig['safe_mode'] == false && $IMConfig['allow_new_dir']) { ?>
	<a href="#" onclick="newFolder();" title="New Folder"><img src="img/btnFolderNew.gif" height="15" width="15" alt="New Folder" /></a>
<?php } ?>
	<div id="messages" style="display: none;"><span id="message"></span><img SRC="img/dots.gif" width="22" height="12" alt="..." /></div>
	<iframe src="images.php<?php if(isset($_GET['uploadPath']) && $_GET['uploadPath']!="") echo "?uploadPath=".$_GET['uploadPath']; ?>" name="imgManager" id="imgManager" class="imageFrame" scrolling="auto" title="Image Selection" frameborder="0"></iframe>
</div>
</fieldset>


<!-- image properties -->
<input type="file" name="upload" id="upload"/>&nbsp;<button type="submit" name="submit" onclick="doUpload();"/>Upload</button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		  <button type="button" class="buttons" onclick="return refresh();" style="display: none">Refresh</button>
          <button type="button" class="buttons" onclick="return onOKLocal();">OK</button>
          <button type="button" class="buttons" onclick="return onCancelLocal();">Cancel</button>
	<table class="inputTable" style="display: none" id="advanced_settings">
		<tr>
			<td align="right"><label for="f_url">Image File</label></td>
			<td><input type="text" id="f_url" class="largelWidth" value="" /></td>
			<td rowspan="3" align="right">&nbsp;</td>
		</tr>
		<tr>
			<td align="right"><label for="f_alt">Alt</label></td>
			<td><input type="text" id="f_alt" class="largelWidth" value="" /></td>
		</tr>

	</table>

	<input type="hidden" id="f_file" name="f_file" />
</form>
</body>
</html>