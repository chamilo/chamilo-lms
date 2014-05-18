<?php
/**
 * Show a list of images in a long horizontal table.
 * @author Wei Zhuo
 * @version $Id: images.php,v 1.2 2006/12/16 21:38:13 thierrybo Exp $
 * @package ImageManager
 */

require_once 'config.inc.php';
require_once 'Classes/ImageManager.php';

//default path is /
$relative = '/images/gallery/';

$manager = new ImageManager($IMConfig);

//process any file uploads
$manager->processUploads();
$manager->deleteFiles();

$refreshDir = false;
//process any directory functions
if ($manager->deleteDirs() || $manager->processNewDir())
	$refreshDir = true;

//check for any sub-directory request
//check that the requested sub-directory exists
//and valid
if (isset($_REQUEST['dir'])) {
	$path = rawurldecode($_REQUEST['dir']);
	if ($manager->validRelativePath($path)) {
		$relative = $path;
    }
}

$manager = new ImageManager($IMConfig);

//get the list of files and directories
$list = $manager->getFiles($relative);

/* ================= OUTPUT/DRAW FUNCTIONS ======================= */

/**
 * Draw the files in an table.
 */
function drawFiles($list, &$manager) {
	global $relative;
	global $IMConfig;

	// add filename with course code in it
	// here filename is images/gallery/COMES.jpg
	// it should be /chamilo1884url/courses/COURSTESTSIMSUURLAPP/document/
	global $_configuration;
	//var topDoc = window.top.document;

    $course_id = api_get_course_id();
    $in_course = $course_id != -1 ? true : false;

	foreach ($list as $entry => $file) {
		//$chamiloPath = '/'.$_configuration['url_append'].'/courses/'.api_get_course_path().'/document'.$file['relative'];
		?>
		<td><table width="100" cellpadding="0" cellspacing="0"><tr><td class="block">
		<!-- change <?php echo $file['relative'];?> with <?php echo $chamiloPath; ?>
		<a href="javascript: void(0);" onclick="selectImage('<?php echo $file['relative'];?>', '<?php echo $entry; ?>', <?php echo $file['image'][0];?>, <?php echo $file['image'][1]; ?>);"title="<?php echo $entry; ?> - <?php echo Files::formatSize($file['stat']['size']); ?>"><img src="<?php echo $manager->getThumbnail($file['relative']); ?>" alt="<?php echo $entry; ?> - <?php echo Files::formatSize($file['stat']['size']); ?>"/></a>
		-->
		<a href="javascript: void(0);" onclick="selectImage('<?php echo $file['relative']; ?>', '<?php echo $entry; ?>', <?php echo $file['image'][0];?>, <?php echo $file['image'][1]; ?>);"title="<?php echo $entry; ?> - <?php echo Files::formatSize($file['stat']['size']); ?>"><img src="<?php echo $manager->getThumbnail($file['relative']); ?>" alt="<?php echo $entry; ?> - <?php echo Files::formatSize($file['stat']['size']); ?>"/></a>
		</td></tr><tr><td class="edit" style="padding-top: 5px;">
		<?php if ($IMConfig['allow_delete']) { ?>
			<a href="images.php?dir=<?php echo $relative; ?>&amp;delf=<?php echo rawurlencode($file['relative']);?>" title="Trash" onclick="return confirmDeleteFile('<?php echo $entry; ?>');"><img src="img/edit_trash.gif" height="15" width="15" alt="Trash"/></a>
		<?php } ?>
		<?php if ($IMConfig['allow_edit']) { ?>
			<a href="javascript: void(0);" title="Edit" onclick="editImage('<?php echo rawurlencode($file['relative']);?>');"><img src="img/edit_pencil.gif" height="15" width="15" alt="Edit"/></a>
		<?php } ?>
		<?php if($file['image']){ echo $file['image'][0].'x'.$file['image'][1]; } else echo $entry;?>
		</td></tr></table></td>
	  <?php
	}//foreach
}//function drawFiles


/**
 * Draw the directory.
 */
function drawDirs($list, &$manager) {
	global $relative;
	foreach($list as $path => $dir) { ?>
		<td><table width="100" cellpadding="0" cellspacing="0"><tr><td class="block">
		<a href="images.php?dir=<?php echo rawurlencode($path); ?>" onclick="updateDir('<?php echo $path; ?>')" title="<?php echo $dir['entry']; ?>"><img src="img/folder.gif" height="80" width="80" alt="<?php echo $dir['entry']; ?>" /></a>
		</td></tr><tr>
		<td class="edit" style="padding-top: 5px;">
			<a href="images.php?dir=<?php echo $relative; ?>&amp;deld=<?php echo rawurlencode($path); ?>" title="Trash" onclick="return confirmDeleteDir('<?php echo $dir['entry']; ?>', <?php echo $dir['count']; ?>);"><img src="img/edit_trash.gif" style="width: 15px; height: 15px;" alt="Trash"/></a>
			<?php echo $dir['entry']; ?>
		</td>
		</tr></table></td>
	  <?php
	} //foreach
}//function drawDirs


/**
 * No directories and no files.
 */
function drawNoResults()
{
?>
<table width="100%">
  <tr>
    <td class="noResult">No Images Found</td>
  </tr>
</table>
<?php
}

/**
 * No directories and no files.
 */
function drawErrorBase(&$manager)
{
?>
<table width="100%">
  <tr>
    <td class="error">Invalid base directory: <?php echo $manager->config['base_dir']; ?></td>
  </tr>
</table>
<?php
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $IMConfig['language']; ?>" lang="<?php echo $IMConfig['language']; ?>">
<head>
	<title>Image List</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="assets/imagelist.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="assets/dialog.js"></script>
<script type="text/javascript">
/*<![CDATA[*/

	//if(window.top)
	//	I18N = window.top.I18N;
	I18N = window.parent.I18N;

	function hideMessage()
	{
		//var topDoc = window.top.document;
		var topDoc = window.parent.document;
		var messages = topDoc.getElementById('messages');
		if(messages)
			messages.style.display = "none";
	}

	init = function()
	{
		hideMessage();
		//var topDoc = window.top.document;
		var topDoc = window.parent.document;

<?php
	//we need to refesh the drop directory list
	//save the current dir, delete all select options
	//add the new list, re-select the saved dir.
	if($refreshDir)
	{
		$dirs = $manager->getDirs();

?>
		var selection = topDoc.getElementById('dirPath');
		var currentDir = selection.options[selection.selectedIndex].text;

		while(selection.length > 0)
		{	selection.remove(0); }

		selection.options[selection.length] = new Option("/","<?php echo rawurlencode('/'); ?>");
		<?php foreach($dirs as $relative=>$fullpath) { ?>
		selection.options[selection.length] = new Option("<?php echo $relative; ?>","<?php echo rawurlencode($relative); ?>");
		<?php } ?>

		for(var i = 0; i < selection.length; i++)
		{
			var thisDir = selection.options[i].text;
			if(thisDir == currentDir)
			{
				selection.selectedIndex = i;
				break;
			}
		}
<?php } ?>
	}

	function editImage(image)
	{
		var url = "<?php echo api_get_path(REL_CODE_PATH).'inc/lib/fckeditor/editor/plugins/ImageManager/'; ?>editor.php?img="+image;
		if ( window.parent.opener )
		{
			Dialog(url, function(param)
			{
				if (!param) // user must have pressed Cancel
					return false;
				else
				{
					return true;
				}
			}, null);
		}
		else if ( window.parent.oEditor )
		{
			window.parent.oEditor.OpenDialog( url, null, null, 'FCKDialog_ImageEditor', 'Edit image', 713, 596 );
		}
	}

/*]]>*/
</script>
<script type="text/javascript" src="assets/images.js"></script>
</head>

<body dir="<?php echo $IMConfig['text_direction']; ?>">
<?php if (!$manager->isValidBase()) { drawErrorBase($manager); }
	elseif(count($list[0]) > 0 || count($list[1]) > 0) { ?>
<table>
	<tr>
	<?php drawDirs($list[0], $manager); ?>
	<?php drawFiles($list[1], $manager); ?>
	</tr>
</table>
<?php } else { drawNoResults(); } ?>
</body>
</html>
