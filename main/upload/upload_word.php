<?php
/* For licensing terms, see /license.txt */

/**
 * Action controller for the upload process. The display scripts (web forms)
 * redirect
 * the process here to do what needs to be done with each file.
 *
 * @package chamilo.upload
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

$form_style = '<style>
.row {
    width: 200px;
}
.convert_button{
    background: url("'.Display::returnIconPath('learnpath.png').'") 0px 0px no-repeat;
    padding: 2px 0px 2px 22px;
}
#dynamic_div_container{float:left;margin-right:10px;}
#dynamic_div_waiter_container{float:left;}
</style>';

$htmlHeadXtra[] = $form_style;

if (api_get_setting('search_enabled') == 'true') {
    $specific_fields = get_specific_field_list();
}

if (isset($_POST['convert'])) {
    $cwdir = getcwd();
    if (isset($_FILES['user_file'])) {
        $allowed_extensions = ['doc', 'docx', 'odt', 'txt', 'sxw', 'rtf'];
        if (in_array(strtolower(pathinfo($_FILES['user_file']['name'], PATHINFO_EXTENSION)), $allowed_extensions)) {
            require '../lp/lp_upload.php';
            if (isset($o_doc) && $first_item_id != 0) {
                // Search-related section
                if (api_get_setting('search_enabled') == 'true') {
                    require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';
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
                                        $o_doc->lp_id,
                                        $value
                                    );
                                }
                            }
                        }
                    }
                } //end of search-related section
                header('Location: ../lp/lp_controller.php?'.api_get_cidreq().'&lp_id='.$o_doc->lp_id.'&action=view_item&id='.$first_item_id);
            } else {
                if (!empty($o_doc->error)) {
                    $errorMessage = $o_doc->error;
                } else {
                    $errorMessage = get_lang('OogieUnknownError');
                }
            }
        } else {
            $errorMessage = get_lang('WoogieBadExtension');
        }
    }
}

Event::event_access_tool(TOOL_UPLOAD);
// check access permissions (edit permission is needed to add a document or a LP)
$is_allowed_to_edit = api_is_allowed_to_edit();
if (!$is_allowed_to_edit) {
    api_not_allowed(true);
}

$interbreadcrumb[] = ["url" => "../lp/lp_controller.php?action=list", "name" => get_lang("Doc")];
$nameTools = get_lang("WoogieConversionPowerPoint");
Display::display_header($nameTools);

echo '<span style="color: #5577af; font-size: 16px; font-family: Arial; margin-left: 10px;">'.
    get_lang("WelcomeWoogieSubtitle").'</span><br>';
$message = get_lang("WelcomeWoogieConverter");
echo '<br />';
$s_style = "border-width: 1px;
         border-style: solid;
         margin-left: 0;
         margin-top: 10px;
         margin-bottom: 0px;
         min-height: 30px;
         padding: 5px;
         position: relative;
         width: 500px;
         background-color: #E5EDF9;
         border-color: #4171B5;
         color: #000;";

$s_style_error = "border-width: 1px;
         border-style: solid;
         margin-left: 0;
         margin-top: 10px;
         margin-bottom: 10px;
         min-height: 30px;
         padding: 5px;
         position: relative;
         width: 500px;
         background-color: #FFD1D1;
         border-color: #FF0000;
         color: #000;";

echo '<div style="'.$s_style.'"><div style="float:left; margin-right:10px;">
<img src="'.Display::returnIconPath('message_normal.gif').'" alt="'.$alt_text.'" '.$attribute_list.'  /></div>
<div style="margin-left: 43px">'.$message.'</div></div>';

if (!empty($errorMessage)) {
    echo '<div style="'.$s_style_error.'"><div style="float:left; margin-right:10px;">
    <img src="'.Display::returnIconPath('message_error.gif').'" alt="'.$alt_text.'" '.$attribute_list.'  /></div>
    <div style="margin-left: 43px">'.$errorMessage.'</div></div>';
}

$form = new FormValidator('update_course', 'POST', '', '', 'style="margin: 0;"');

// build the form

$form->addElement('html', '<br>');

$div_upload_limit = '&nbsp;&nbsp;'.get_lang('UploadMaxSize').' : '.ini_get('post_max_size');

$renderer = $form->defaultRenderer();
// set template for user_file element
$user_file_template =
<<<EOT
<div class="row" style="margin-top:10px;width:100%">
        <!-- BEGIN required --><span class="form_required">*</span> <!-- END required -->{label}{element}$div_upload_limit
        <!-- BEGIN error --><br /><span class="form_error">{error}</span><!-- END error -->
</div>
EOT;
$renderer->setElementTemplate($user_file_template, 'user_file');

// set template for other elements
$user_file_template =
<<<EOT
<div class="row" style="margin-top:10px;width:100%">
        <!-- BEGIN required --><span class="form_required">*</span> <!-- END required -->{label}{element}
        <!-- BEGIN error --><br /><span class="form_error">{error}</span><!-- END error -->
</div>
EOT;
$renderer->setCustomElementTemplate($user_file_template);

$form->addElement('file', 'user_file', Display::return_icon('word_big.gif'));
if (api_get_setting('search_enabled') === 'true') {
    $form->addElement('checkbox', 'index_document', '', get_lang('SearchFeatureDoIndexDocument'));
    $form->addElement('html', '<br />');
    $form->addElement(
        'html',
        get_lang(
            'SearchFeatureDocumentLanguage'
        ).': &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.api_get_languages_combo()
    );
    $form->addElement('html', '<div class="sub-form">');
    foreach ($specific_fields as $specific_field) {
        $form->addElement('text', $specific_field['code'], $specific_field['name'].' : ');
    }
    $form->addElement('html', '</div>');
}

/*
 * commented because SplitStepsPerChapter is not stable at all
 * $form -> addElement ('radio', 'split_steps',null, get_lang('SplitStepsPerPage'),'per_page');
 * $form -> addElement ('radio', 'split_steps',null, get_lang('SplitStepsPerChapter'),'per_chapter');
 */
$form->addElement('hidden', 'split_steps', 'per_page');
$form->addElement('submit', 'convert', get_lang('ConvertToLP'), 'class="convert_button"');
$form->addElement('hidden', 'woogie', 'true');
$form->addProgress();
$defaults = ['split_steps' => 'per_page', 'index_document' => 'checked="checked"'];
$form->setDefaults($defaults);

// display the form
$form->display();
Display::display_footer();
