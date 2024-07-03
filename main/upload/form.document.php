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

$frmUpload = new FormValidator('form_div', 'post', 'upload.php');
$frmUpload->addElement('hidden', 'curdirpath', $path);
$frmUpload->addElement('hidden', 'tool', $my_tool);
$frmUpload->addElement('file', 'user_file', get_lang('FileToUpload'));
$frmUpload->addRule('user_file', get_lang('ThisFieldIsRequired'), 'required');
$frmUpload->addButtonUpload(get_lang('Upload'));

echo '
<div id="dynamic_div" style="display:block;margin-left:40%;margin-top:10px;height:50px;">
</div>
<div id="upload_form_div" name="form_div" style="display:block;">
';

$frmUpload->display();

echo '
</div>
<br/>
';

Display::display_footer();
