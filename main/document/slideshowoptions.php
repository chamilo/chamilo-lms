<?php
/* For licensing terms, see /license.txt */
/**
 *	@author Patrick Cool
 *	@package chamilo.document
 * @author Patrick Cool, patrick.cool@UGent.be, Ghent University, May 2004, http://icto.UGent.be
 * Please bear in mind that this is only an beta release.
 * I wrote this quite quick and didn't think too much about it in advance.
 * It is not perfect at all but it is workable and usefull (I think)
 * Do not consider this as a powerpoint replacement, although it has
 * the same starting point.
 *	This is a plugin for the documents tool. It looks for .jpg, .jpeg, .gif, .png
 * 	files (since these are the files that can be viewed in a browser) and creates
 *	a slideshow with it by allowing to go to the next/previous image.
 *	You can also have a quick overview (thumbnail view) of all the images in
 *	that particular folder.
 *	Maybe it is important to notice that each slideshow is folder based. Only
 *	the images of the chosen folder are shown.
 *
 *	On this page the options of the slideshow can be set: maintain the original file
 *	or resize the file to a given width.
 */
/**
 * Code
 */

// Language files that need to be included
$language_file = array('slideshow', 'document');

require_once '../inc/global.inc.php';


api_protect_course_script();

$path = Security::remove_XSS($_GET['curdirpath']);
$pathurl = urlencode($path);

// Breadcrumb navigation
$url = 'document.php?curdirpath='.$pathurl;
$originaltoolname = get_lang('Documents');
$interbreadcrumb[] = array('url' => $url, 'name' => $originaltoolname);

$url = 'slideshow.php?curdirpath='.$pathurl;
$originaltoolname = get_lang('SlideShow');
$interbreadcrumb[] = array('url' => $url, 'name' => $originaltoolname);

// Because $nametools uses $_SERVER['PHP_SELF'] for the breadcrumbs instead of $_SERVER['REQUEST_URI'], I had to
// bypass the $nametools thing and use <b></b> tags in the $interbreadcrump array
$url = 'slideshowoptions.php?curdirpath='.$pathurl;
$originaltoolname = '<b>'.get_lang('SlideshowOptions').'</b>';
$interbreadcrumb[] = array('url' => $url, 'name' => $originaltoolname );

Display::display_header($originalToolName, 'Doc');

$image_resizing = isset($_SESSION['image_resizing']) ? $_SESSION['image_resizing'] : null;

?>
<style type="text/css">
<!--
.disabled_input {
	background-color: #cccccc;
}
.enabled_input {
	background-color: #ffffff;
}
-->
</style>

<script language="JavaScript" type="text/javascript">
<!--

function enableresizing() { //v2.0
	document.options.width.disabled=false;
	document.options.width.className='enabled_input';
	document.options.height.disabled=false;
	document.options.height.className='enabled_input';
}
function disableresizing() { //v2.0
	document.options.width.disabled=true;
	document.options.width.className='disabled_input';
	document.options.height.disabled=true;
	document.options.height.className='disabled_input';
}

window.onload = <?php echo $image_resizing == 'resizing' ? 'enableresizing' : 'disableresizing'; ?>;

//-->
</script>

<?php
echo '<div class="actions">';
echo '<a href="document.php?action=exit_slideshow&curdirpath='.$pathurl.'">'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('DocumentsOverview'),'',ICON_SIZE_MEDIUM).'</a>';

echo '<a href="slideshow.php?curdirpath='.$pathurl.'">'.Display::return_icon('slideshow.png',get_lang('BackTo').' '.get_lang('SlideShow'),'',ICON_SIZE_MEDIUM).'</a>';

echo '</div>';

?>

<form action="slideshow.php?curdirpath=<?php echo $pathurl; ?>" method="post" name="options" id="options">
	<legend><?php echo get_lang('SlideshowOptions') ?></legend>
	<div>
		<div class="label">
			<input class="checkbox" name="radio_resizing" type="radio" onClick="disableresizing()" value="noresizing" <?php
	if ($image_resizing == 'noresizing' || $image_resizing == '') {
		echo ' checked';
	}
			?>>
		<?php echo get_lang('NoResizing');?>
            
		</div>
		<div><?php echo get_lang('NoResizingComment');?>
		</div>
	</div>
    
    
    
   <div>
		<div class="label">
			<input class="checkbox" name="radio_resizing" type="radio" onClick="disableresizing()" value="autoresizing" <?php
	if ($image_resizing == 'resizing_auto' || $image_resizing == '') {
		echo ' checked';
	}
			?>>
		<?php echo get_lang('ResizingAuto');?>
            
		</div>
		<div><?php echo get_lang('ResizingAutoComment');?>
		</div>
	</div> 
 
    
	<div>
		<div class="label">
			<input class="checkbox" name="radio_resizing" type="radio" onClick="javascript: enableresizing();" value="resizing" <?php
	
	if ($image_resizing == 'resizing') {
		echo ' checked';
		$width = $_SESSION['image_resizing_width'];
		$height = $_SESSION['image_resizing_height'];
	}
			?>>
        <?php echo get_lang('Resizing'); ?>
		</div>
		<div>
		<?php echo get_lang('ResizingComment'); ?><br />
        <?php echo get_lang('Width'); ?>:
	    &nbsp;<input name="width" type="text" id="width" <?php
		if ($image_resizing == 'resizing') {
			echo ' value="'.$width.'"';
			echo ' class="enabled_input"';
	    } else {
            echo ' class="disabled_input"';
        }
		?> >
        <br />
        <?php echo get_lang('Height');?>:
        &nbsp;&nbsp;&nbsp;&nbsp;<input name="height" type="text" id="height" <?php
		if ($image_resizing == 'resizing') {
			echo ' value="'.$height.'"';
			echo ' class="enabled_input"';
		} else {
            echo ' class="disabled_input"';
        }
		?> >
        <br />
		</div>
	</div>
	<div>
		<div class="label">
		</div>
		<div>
			<br />
			<button type="submit" class="save" name="Submit" value="Save" ><?php echo get_lang('Save'); ?></button>
		</div>
	</div>
</form>
<?php

Display::display_footer();
