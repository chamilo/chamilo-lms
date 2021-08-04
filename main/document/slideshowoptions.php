<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * @author Patrick Cool, patrick.cool@UGent.be, Ghent University, May 2004, http://icto.UGent.be
 * Please bear in mind that this is only an beta release.
 * I wrote this quite quick and didn't think too much about it in advance.
 * It is not perfect at all but it is workable and usefull (I think)
 * Do not consider this as a powerpoint replacement, although it has
 * the same starting point.
 * This is a plugin for the documents tool. It looks for .jpg, .jpeg, .gif, .png
 * files (since these are the files that can be viewed in a browser) and creates
 * a slideshow with it by allowing to go to the next/previous image.
 * You can also have a quick overview (thumbnail view) of all the images in
 * that particular folder.
 * Maybe it is important to notice that each slideshow is folder based. Only
 * the images of the chosen folder are shown.
 * On this page the options of the slideshow can be set: maintain the original file
 * or resize the file to a given width.
 */
require_once __DIR__.'/../inc/global.inc.php';
api_protect_course_script();

if (api_get_configuration_value('disable_slideshow_documents')) {
    api_not_allowed(true);
}

$path = Security::remove_XSS($_GET['curdirpath']);
$pathurl = urlencode($path);

// Breadcrumb navigation
$url = 'document.php?curdirpath='.$pathurl;
$originaltoolname = get_lang('Documents');
$interbreadcrumb[] = ['url' => $url, 'name' => $originaltoolname];

$url = 'slideshow.php?curdirpath='.$pathurl;
$originaltoolname = get_lang('SlideShow');
$interbreadcrumb[] = ['url' => $url, 'name' => $originaltoolname];

// Because $nametools uses $_SERVER['PHP_SELF'] for the breadcrumbs instead of $_SERVER['REQUEST_URI'], I had to
// bypass the $nametools thing and use <b></b> tags in the $interbreadcrump array
$url = 'slideshowoptions.php?curdirpath='.$pathurl;
$originaltoolname = '<b>'.get_lang('SlideshowOptions').'</b>';
$interbreadcrumb[] = ['url' => $url, 'name' => $originaltoolname];

Display::display_header($originaltoolname, 'Doc');
$image_resizing = Session::read('image_resizing');
?>
<script>
function enableresizing() { //v2.0
    document.options.width.disabled=false;
    //document.options.width.className='enabled_input';
    document.options.height.disabled=false;
    //document.options.height.className='enabled_input';
}
function disableresizing() { //v2.0
    document.options.width.disabled=true;
    //document.options.width.className='disabled_input';
    document.options.height.disabled=true;
    //document.options.height.className='disabled_input';
}
window.onload = <?php echo $image_resizing == 'resizing' ? 'enableresizing' : 'disableresizing'; ?>;
</script>

<?php
$actions = '<a href="document.php?action=exit_slideshow&curdirpath='.$pathurl.'">'.Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('DocumentsOverview'), '', ICON_SIZE_MEDIUM).'</a>';
$actions .= '<a href="slideshow.php?curdirpath='.$pathurl.'">'.Display::return_icon('slideshow.png', get_lang('BackTo').' '.get_lang('SlideShow'), '', ICON_SIZE_MEDIUM).'</a>';
echo Display::toolbarAction('toolbar-slideshow', [$actions]);
?>
<div class="panel panel-default">
    <div class="panel-body">
    <form action="slideshow.php?curdirpath=<?php echo $pathurl; ?>" method="post" name="options" id="options" class="form-horizontal">
	<legend><?php echo get_lang('SlideshowOptions'); ?></legend>
        <div class="radio">
            <label>
                <input name="radio_resizing" type="radio" onClick="disableresizing()" value="noresizing" <?php
                    if ($image_resizing == 'noresizing' || $image_resizing == '') {
                        echo ' checked';
                    }
        ?>>
            </label>
            <?php echo '<b>'.get_lang('NoResizing').'</b>, '.get_lang('NoResizingComment'); ?>
        </div>
        <div class="radio">
            <label>
                <input name="radio_resizing" type="radio" onClick="disableresizing()" value="autoresizing" <?php
                if ($image_resizing == 'resizing_auto' || $image_resizing == '') {
                    echo ' checked';
                }
        ?>>
            </label>
            <?php echo '<b>'.get_lang('ResizingAuto').'</b>, '.get_lang('ResizingAutoComment'); ?>
	</div>
	<div class="radio">
            <label>
                <input class="checkbox" name="radio_resizing" type="radio" onClick="javascript: enableresizing();" value="resizing" <?php
                if ($image_resizing == 'resizing') {
                    echo ' checked';
                    $width = Session::read('image_resizing_width');
                    $height = Session::read('image_resizing_height');
                }
        ?>>
            </label>
            <?php echo '<b>'.get_lang('Resizing').'</b>, '.get_lang('ResizingComment'); ?>
	</div>
        <div class="form-group">
            <label class="col-sm-1 control-label"><?php echo get_lang('Width'); ?></label>
            <div class="col-sm-3">
                <input class="form-control" name="width" type="text" id="width" <?php
        if ($image_resizing == 'resizing') {
            echo ' value="'.$width.'"';
            echo ' class="enabled_input"';
        } else {
            echo ' class="disabled_input"';
        }
        ?> >
            </div>
            <div class="col-sm-8"></div>
        </div>
        <div class="form-group">
            <label class="col-sm-1 control-label"><?php echo get_lang('Height'); ?></label>
            <div class="col-sm-3">
                <input class="form-control" name="height" type="text" id="height" <?php
        if ($image_resizing == 'resizing') {
            echo ' value="'.$height.'"';
            echo ' class="enabled_input"';
        } else {
            echo ' class="disabled_input"';
        }
        ?> >
            </div>
            <div class="col-sm-8"></div>
        </div>
        <div class="form-group">
            <div class="col-sm-12">
                <button type="submit" class="btn btn-default" name="Submit" value="Save" ><?php echo get_lang('Save'); ?></button>
            </div>
        </div>
</form>
    </div>
</div>
<?php

Display::display_footer();
