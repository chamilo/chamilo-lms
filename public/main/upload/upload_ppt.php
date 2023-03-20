<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;

/**
 * Action controller for the upload process. The display scripts (web forms) redirect
 * the process here to do what needs to be done with each file.
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);
if (isset($_POST['convert'])) {
    $cwdir = getcwd();
    if (isset($_FILES['user_file'])) {
        $allowed_extensions = ['odp', 'sxi', 'ppt', 'pps', 'sxd', 'pptx'];
        if (in_array(
            strtolower(pathinfo($_FILES['user_file']['name'], PATHINFO_EXTENSION)),
            $allowed_extensions
        )) {
            require_once api_get_path(SYS_CODE_PATH).'lp/lp_upload.php';
            if (isset($o_ppt) && 0 != $first_item_id) {
                if ('true' == api_get_setting('search_enabled')) {
                    $specific_fields = get_specific_field_list();
                    foreach ($specific_fields as $specific_field) {
                        $values = explode(',', trim($_POST[$specific_field['code']]));
                        if (!empty($values)) {
                            foreach ($values as $value) {
                                $value = trim($value);
                                if (!empty($value)) {
                                    add_specific_field_value(
                                        $specific_field['id'],
                                        api_get_course_id(),
                                        TOOL_LEARNPATH,
                                        $o_ppt->lp_id,
                                        $value
                                    );
                                }
                            }
                        }
                    }
                }
                header('Location: ../lp/lp_controller.php?'.api_get_cidreq().'&lp_id='.$o_ppt->lp_id.'&action=view_item&id='.$first_item_id);
                exit;
            } else {
                if (!empty($o_ppt->error)) {
                    $errorMessage = $o_ppt->error;
                } else {
                    $errorMessage = get_lang('The conversion failed for an unknown reason.<br />Please contact your administrator to get more information.');
                }
            }
        } else {
            $errorMessage = get_lang('Please upload presentations only. Filename extension should be .ppt or .odp');
        }
    }
}

Event::event_access_tool(TOOL_UPLOAD);

// check access permissions (edit permission is needed to add a document or a LP)
$is_allowed_to_edit = api_is_allowed_to_edit();

if (!$is_allowed_to_edit) {
    api_not_allowed(true);
}

$interbreadcrumb[] = ["url" => "../lp/lp_controller.php?action=list", "name" => get_lang("Document")];

$nameTools = get_lang("Chamilo RAPID : PowerPoint conversion");
Display :: display_header($nameTools);
$message = get_lang('Welcome to Chamilo RAPID<ul type="1"><li>Browse your hard disk to find any .ppt or .odp file<li>Upload it to Oogie. It will convert it into a SCORM course.<li>You will then be allowed to add audio comments on each slide and insert test and activities between slides.');

if (!empty($errorMessage)) {
    echo Display::return_message($errorMessage, 'warning', false);
}

$div_upload_limit = get_lang('Upload max size').' : '.ini_get('post_max_size');

$form = new FormValidator('upload_ppt', 'POST', '?'.api_get_cidreq(), '');
$form->addElement('header', get_lang("A PowerPoint to SCORM Courses converter"));
$form->addElement('html', Display::return_message($message, 'info', false));
$form->addElement('file', 'user_file', [Display::return_icon('powerpoint_big.gif'), $div_upload_limit]);
$form->addElement('checkbox', 'take_slide_name', '', get_lang('Use the slides names as course learning object names'));
$options = ChamiloApi::getDocumentConversionSizes();
$form->addSelect('slide_size', get_lang('Size of the slides'), $options);
if ('true' === api_get_setting('search_enabled')) {
    $specific_fields = get_specific_field_list();
    $form->addElement('checkbox', 'index_document', '', get_lang('Index document text?ument'));
    $form->addSelectLanguage('language', get_lang('SearchFeatureDocumentumentLanguage'));
    foreach ($specific_fields as $specific_field) {
        $form->addElement('text', $specific_field['code'], $specific_field['name'].' : ');
    }
}

$form->addButtonUpload(get_lang('Convert to course'), 'convert');
$form->addElement('hidden', 'ppt2lp', 'true');
$form->addProgress();
$size = api_get_setting('service_ppt2lp', 'size');
$defaults = [
    'take_slide_name' => 'checked="checked"',
    'index_document' => 'checked="checked"',
    'slide_size' => $size,
];
$form->setDefaults($defaults);

$form->display();
Display::display_footer();
