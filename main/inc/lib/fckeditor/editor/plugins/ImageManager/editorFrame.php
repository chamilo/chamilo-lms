<?php
	/**
	 * The frame that contains the image to be edited.
	 * @author Wei Zhuo
	 * @author Paul Moers <mail@saulmade.nl> - watermarking and replace code + several small enhancements <http://www.saulmade.nl/FCKeditor/FCKPlugins.php>
	 * @version $Id: editorFrame.php,v 1.7 2006/12/20 18:19:28 thierrybo Exp $
	 * @package ImageManager
	 */

	require_once('config.inc.php');
	require_once('Classes/ImageManager.php');
	require_once('Classes/ImageEditor.php');

	//default path is /
	$relative = '/';

	$manager = new ImageManager($IMConfig);
	$editor = new ImageEditor($manager);

	// process any uploaded files
	$imageRelative = $manager->processUploads();

	// get image info and process any action
	$imageInfo = $editor->processImage($imageRelative);

?>
<!--[if IE]>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<![endif]-->
<html>
<head>
<title></title>
<link href="assets/editorFrame.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="assets/wz_jsgraphics.js"></script>
<script type="text/javascript" src="assets/wz_dragdrop.js"></script>
<script type="text/javascript" src="assets/EditorContent.js"></script>
<script type="text/javascript" src="assets/editorFrame.js"></script>

<style>

	/* for centering the image vertically in IE */
	html, body
	{
		height: 100%;
	}

</style>

<script type="text/javascript">
// <![CDATA[

	var processedAction = "<?php echo (isset($_GET['action']) ? str_replace('"','',$_GET['action']) : ''); ?>";

	if (processedAction == 'replace' && parent.old)
	{
		// reallow the user to close the editor window now uploading is done
		parent.onbeforeunload = function () {parent.old();};
	}

	// set function for the wz_dragdrop script to use when dragging
	function my_DragFunc()
	{
		verifyBounds();
	}

	// keep the watermark within the background
	function verifyBounds()
	{
		var
			orig = dd.elements.background,
			floater = dd.elements.floater,
			newX = floater.x,
			newY = floater.y
			;

		if (floater.x < orig.x)
		{
			newX = orig.x;
		}
		else if (floater.x + floater.w > orig.x + orig.w)
		{
			newX = orig.x + (orig.w - floater.w);
		}
		if (floater.y < orig.y)
		{
			newY = orig.y;
		}
		else if (floater.y + floater.h > orig.y + orig.h)
		{
			newY = orig.y + (orig.h - floater.h);
		}
		if (newX != floater.x || newY != floater.y)
		{
			floater.moveTo(newX, newY);
		}
	}

// ]]>
</script>


<script type="text/javascript">

	var mode = "<?php echo $editor->getAction(); ?>" //crop, scale, measure
	var currentImageFile = "<?php if(count($imageInfo)>0) echo rawurlencode($imageInfo['file']); ?>";

	if(window.top)
	{
		I18N = window.top.I18N;
	}

	function i18n(str)
	{
		if(I18N)
			return (I18N[str] || str);
		else
			return str;
	};

</script>

<script type="text/javascript">
<?php
	if ($editor->isFileSaved() == 1)
	{
?>
	// Reload the images in the imageframe of the Manager :
	// Is there a "imgManager" frame.
	if ((popupWindow = parent.opener.parent) && (imgManager = parent.opener.parent.document.getElementById("imgManager")))
	{
		// refresh it - so it shows the edited image.
		imgManager.src = imgManager.src;
	}
	else
	{
		popupWindow = parent;
	}
	// Reload the images in the editor :
	// base system is the FCKeditor?
	if (popupWindow.opener.FCK)
	{
		editorFrame = popupWindow.opener.parent.document.getElementById(popupWindow.opener.FCK.Name + '___Frame');
		// get document
		if (editorDocument = popupWindow.opener.FCK.EditorDocument.body)
		{
			editorImages = editorDocument.getElementsByTagName("img");
			for (i = 0; i < editorImages.length; i++)
			{
				// reapply images their src to reload'm
				editorImages[i].src = editorImages[i].src;
			}
		}
	}
	// not found?
	if (!editorImages)
	{
		alert(i18n("Error: Couldn't update editor window.\nAny image that was edited and saved will still be visible as the old version!\nPlease save and reload the editor content."));
	}

	// save message
	var message = i18n('File saved.');
<?php
	if ($editor->forcedNewName != false)
	{
?>
	message += '\n' + i18n('File name was changed into ') +  '<?php echo $editor->forcedNewName; ?>';
<?php
	}
?>
	alert(message);
<?php
	}
	else if ($editor->isFileSaved() == -1)
	{
?>
	alert(i18n('File was not saved.'));
<?php
	}
?>

	// show action buttons and current action's controls - were hidden during processing
	if (processedAction != '')
	{
		if ('flip' == processedAction)
		{
			processedAction = 'rotate';
		}
		var tools = parent.document.getElementById('tools_' + processedAction);
		tools.style.display = 'block';
		var buttons = parent.document.getElementById('buttons');
		buttons.style.display = 'block';
	}


	// populating watermarks select box (excluding watermarks that are to big or became to big for the editted image)
	watermarkBox = parent.document.getElementById("watermark_file");
	imagesArray = new Array();
	// first clear all its options
	watermarkBox.options.length = 0;
<?php
	foreach($IMConfig['watermarks'] as $watermark)
	{
		$watermarkInfo = @getImageSize($IMConfig['base_dir'] . $watermark);
		// populate
		if ($watermarkInfo[0] < $imageInfo['width'] && $watermarkInfo[1] < $imageInfo['height'] && $watermarkInfo[0] != '')
		{
			$pos = strrpos(basename($watermark), ".");
			$filename = substr(basename($watermark), 0, $pos);

			echo "watermarkBox.options[watermarkBox.options.length] = new Option('" . basename($watermark) . "', '$filename');";
			echo "watermarkBox.options[watermarkBox.options.length - 1].setAttribute('fullPath', '" . $IMConfig['base_dir'] . $watermark . "');";
			echo "watermarkBox.options[watermarkBox.options.length - 1].setAttribute('x', $watermarkInfo[0]);";
			echo "watermarkBox.options[watermarkBox.options.length - 1].setAttribute('y', $watermarkInfo[1]);";

			echo $filename . "Preload = new Image(10, 10);";
			echo $filename . "Preload.src = '" . $IMConfig['base_url'] . $watermark . "';";
		}
		// first item unavailable?
		else if ($watermark == $IMConfig['watermarks'][0])
		{
			echo "var firstWatermarkItemUnavailable = true;";
		}

	}
?>
	// no watermarks found? Show message instead of watermarking controls.
	if (!watermarkBox.options[0])
	{
		parent.document.getElementById("watermarkControls").style.display = "none";
		parent.document.getElementById("watermarkMessage").style.display = "block";

		var watermarkingEnabled = false;
	}
	// else just show the controls
	else
	{
		parent.document.getElementById("watermarkControls").style.display = "block";
		parent.document.getElementById("watermarkMessage").style.display = "none";

		var watermarkingEnabled = true;
	}

</script>

</head>

<body>
<div id="status"></div>
<div id="ant" class="selection" style="visibility:hidden"><img src="img/spacer.gif" width="0" height="0" border="0" alt="" id="cropContent"></div>
<?php if ($editor->isGDEditable() == -1) { ?>
	<div style="text-align:center; padding:10px;"><span class="error">GIF format is not supported, image editing not supported.</span></div>
<?php } ?>
<table height="100%" width="100%">
	<tr>
		<td>
<?php if(count($imageInfo) > 0 && is_file($imageInfo['fullpath'])) { ?>

		<div id="background" name="background" style="margin: auto; width: <?php echo $imageInfo['width']; ?>px; height: <?php echo $imageInfo['height']; ?>px; background-image: url(<?php echo $imageInfo['src']; ?>);">
		<?php if (count($IMConfig['watermarks']) > 0) { ?>
			<img name="floater" id="floater" style="width: 150px; height: 150px; behavior: url('assets/pngbehavior.htc'); position: absolute" src="<?php echo $IMConfig['base_url'] . $IMConfig['watermarks'][0]; ?>" />
		<?php } ?>
		</div>

		<span id="imgCanvas" name="imgCanvas" class="crop" style="display: none;"><img src="<?php echo $imageInfo['src']; ?>" <?php echo $imageInfo['dimensions']; ?> alt="" id="theImage" name="theImage" /></span>

<?php } else { ?>
				<span class="error">No Image Available</span>
<?php } ?>
		</td>
	</tr>
</table>

<script type="text/javascript">
// <![CDATA[

	if (watermarkingEnabled == true)
	{
		SET_DHTML("background"+NO_DRAG, "floater"+CURSOR_MOVE);
		if (window.firstWatermarkItemUnavailable == true)
		{
			dd.elements.floater.swapImage(eval("window." + watermarkBox.options[0].value + "Preload.src"));
		}
		dd.elements.floater.resizeTo(watermarkBox.options[0].getAttribute("x"), watermarkBox.options[0].getAttribute("y"));
		dd.elements.floater.hide();
		verifyBounds();
		dd.elements.floater.nimg.style.behavior = "url('assets/pngbehavior.htc')";

<?php
	if (isset($_GET['action']))
	{
		if ($_GET['action'] == "watermark")
		{?>
				dd.elements.floater.show();
		<?php }
	}
?>
	}
	else
	{
		if (document.getElementById("floater"))
		{
			document.getElementById("floater").style.display = "none";
		}
	}

	// hiding parent processing message
	parent.window.hideMessage();

	// make sure the slider of the watermark's opacity if at max
	parent.window.updateSlider(100, 'watermark');

// ]]>
</script>
</body>
</html>
