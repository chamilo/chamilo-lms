<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Main script for the documents tool.
 *
 * This script allows the user to manage files and directories on a remote http server.
 *
 * The user can : - upload a file
 *
 * The script respects the strategical split between process and display, so the first
 * part is only processing code (init, process, display preparation) and the second
 * part is only display (HTML)
 */
require_once __DIR__.'/../inc/global.inc.php';

$_course = api_get_course_info();

api_protect_course_script(true);

$htmlHeadXtra[] = "<script>
function check_unzip() {
	if (document.upload.unzip.checked) {
        document.upload.if_exists[0].disabled=true;
        document.upload.if_exists[1].checked=true;
        document.upload.if_exists[2].disabled=true;
	} else {
        document.upload.if_exists[0].checked=true;
        document.upload.if_exists[0].disabled=false;
        document.upload.if_exists[2].disabled=false;
	}
}
</script>";

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
if (!$is_allowed_to_edit) {
    api_not_allowed(true);
}

//what's the current path?
$path = '/';
if (isset($_REQUEST['curdirpath'])) {
    $path = $_REQUEST['curdirpath'];
}

$toolFromSession = Session::read('my_tool');

// set calling tool
if (isset($_REQUEST['tool'])) {
    $my_tool = $_REQUEST['tool'];
    Session::write('my_tool', $_REQUEST['tool']);
} elseif (!empty($toolFromSession)) {
    $my_tool = $toolFromSession;
} else {
    $my_tool = 'document';
    Session::write('my_tool', $my_tool);
}

Event::event_access_tool(TOOL_UPLOAD);

/**
 * Just display the form needed to upload a SCORM and give its settings.
 */
$nameTools = get_lang('File upload');
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('Learning path'),
];

Display::display_header($nameTools, 'Path');

$content_origins = [
    //'--'.get_lang('Generic SCORM').'--',
    //'--'.get_lang('Other').'--',
    'Accent',
    'Accenture',
    'ADLNet',
    'Articulate',
    'ATutor',
    'Blackboard',
    'Calfat',
    'Captivate',
    'Chamilo',
    'Chamilo 2',
    'Claroline',
    'Commest',
    'Coursebuilder',
    'Docent',
    'Dokeos',
    'Dreamweaver',
    'Easyquiz',
    'e-doceo',
    'ENI Editions',
    'Explio',
    'Flash',
    'HTML',
    'HotPotatoes',
    'Hyperoffice',
    'Ingenatic',
    'Instruxion',
    'iProgress',
    'Lectora',
    'Microsoft',
    'Onlineformapro',
    'Opikanoba',
    'Plantyn',
    'Saba',
    'Skillsoft',
    'Speechi',
    'Thomson-NETg',
    'U&I Learning',
    'Udutu',
    'WebCT',
];

echo Display::toolbarAction('lp', [
    '<a href="'.api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq().'">'.
    Display::return_icon('back.png', get_lang('Back to learning paths'), '', ICON_SIZE_MEDIUM).'</a>',
]);

$form = new FormValidator(
    'upload',
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

if ('true' == api_get_setting('search_enabled')) {
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
