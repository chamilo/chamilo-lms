<?php
/* For licensing terms, see /license.txt */

/**
 * Display part of the document sub-process for upload. This script MUST BE included by upload/index.php
 * as it prepares most of the variables needed here.
 *
 * @package chamilo.upload
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Just display the form needed to upload a SCORM and give its settings.
 */
$nameTools = get_lang('FileUpload');
$interbreadcrumb[] = ["url" => "../lp/lp_controller.php?action=list", "name" => get_lang(TOOL_DOCUMENT)];
Display::display_header($nameTools, "Doc");
// Show the title
api_display_tool_title($nameTools.$add_group_to_title);
?>

<div id="dynamic_div" style="display:block;margin-left:40%;margin-top:10px;height:50px;">
</div>
<div id="upload_form_div" name="form_div" style="display:block;">
    <form method="POST" action="upload.php" id="upload_form" enctype="multipart/form-data">
        <input type="hidden" name="curdirpath" value="<?php echo $path; ?>">
        <input type="hidden" name="tool" value="<?php echo $my_tool; ?>">
        <input type="file" name="user_file">
        <input type="submit" name="submit" value="Upload">
    </form>
</div>
<br/>
<?php

Display::display_footer();
