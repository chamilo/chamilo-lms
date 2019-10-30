<?php
/* For licensing terms, see /license.txt */

/**
 * Display part of the SCORM sub-process for upload. This script MUST BE included by upload/index.php
 * as it prepares most of the variables needed here.
 *
 * @package chamilo.upload
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

/**
 * Small function to list files in archive/.
 */
function get_zip_files_in_garbage()
{
    $list = [];
    $dh = opendir(api_get_path(SYS_ARCHIVE_PATH));
    if ($dh === false) {
        //ignore
    } else {
        while ($entry = readdir($dh)) {
            if (substr($entry, 0, 1) == '.') {
                /* ignore files starting with . */
            } else {
                if (preg_match('/^.*\.zip$/i', $entry)) {
                    $list[] = $entry;
                }
            }
        }
        natcasesort($list);
        closedir($dh);
    }

    return $list;
}

/**
 * Just display the form needed to upload a SCORM and give its settings.
 */
$nameTools = get_lang('File upload');
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('Learning path'),
];

Display::display_header($nameTools, 'Path');

require_once '../lp/content_makers.inc.php';
require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';

echo '<div class="actions">';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq().'">'.
    Display::return_icon('back.png', get_lang('Back to learning paths'), '', ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

$form = new FormValidator(
    '',
    'POST',
    api_get_path(WEB_CODE_PATH).'upload/upload.php?'.api_get_cidreq(),
    '',
    [
        'id' => 'upload_form',
        'enctype' => 'multipart/form-data',
    ]
);
$form->addHeader($nameTools);
$form->addLabel(null, Display::return_icon('scorm_logo.jpg', null, ['style' => 'width:230px;height:100px']));
$form->addElement('hidden', 'curdirpath', $path);
$form->addElement('hidden', 'tool', $my_tool);
$form->addElement('file', 'user_file', get_lang('SCORM or AICC file to upload'));
$form->addProgress();
$form->addRule('user_file', get_lang('Required field'), 'required');

unset($content_origins[0]);
unset($content_origins[1]);

if (api_get_setting('search_enabled') == 'true') {
    $form->addElement('checkbox', 'index_document', '', get_lang('Index document text?'));
    $specific_fields = get_specific_field_list();
    foreach ($specific_fields as $specific_field) {
        $form->addElement('text', $specific_field['code'], $specific_field['name'].' : ');
    }
}

if (api_is_platform_admin()) {
    $form->addElement('checkbox', 'use_max_score', null, get_lang('Use default maximum score of 100'));
}

if (api_get_configuration_value('allow_htaccess_import_from_scorm')) {
    $form->addElement('checkbox', 'allow_htaccess', null, get_lang('Allow htaccess in the SCORM import'));
}

$form->addButtonUpload(get_lang('Upload'));

// the default values for the form
$defaults = ['index_document' => 'checked="checked"', 'use_max_score' => 1];
$form->setDefaults($defaults);
echo Display::return_message(
    Display::tag('strong', get_lang('SCORM Authoring tools supported')).': '.implode(', ', $content_origins),
    'normal',
    false
);
$form->display();

Display::display_footer();
