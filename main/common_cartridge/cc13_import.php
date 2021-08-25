<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
require_once api_get_path(SYS_CODE_PATH).'common_cartridge/import/src/lib/validateurlsyntax.php';
require_once api_get_path(SYS_CODE_PATH).'common_cartridge/import/src/inc/constants.php';

$current_course_tool = TOOL_COURSE_MAINTENANCE;
api_protect_course_script(true);

// Check access rights (only teachers are allowed here)
if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

api_set_more_memory_and_time_limits();

// Section for the tabs
$this_section = SECTION_COURSES;

// Breadcrumbs
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'course_info/maintenance.php?'.api_get_cidreq(),
    'name' => get_lang('Maintenance'),
];

$form = new FormValidator('cc_import', 'post', api_get_self().'?'.api_get_cidreq());
$form->addFile('cc_file', get_lang('IMSCCFile'));
$form->addButtonImport(get_lang('Import'));

if ($form->validate()) {
    $file = $_FILES['cc_file'];

    if (empty($file['tmp_name'])) {
        $errorMessage = get_lang('UplUploadFailed');
        echo Display::return_message($errorMessage, 'error', false);
    } else {
        $allowedFileMimetype = ['imscc'];

        $extImportFile = substr($file['name'], (strrpos($file['name'], '.') + 1));

        if (!in_array($extImportFile, $allowedFileMimetype)) {
            echo Display::return_message(get_lang('YouMustImportAFileAccordingToSelectedOption'), 'error');
        } else {
            $baseDir = api_get_path(SYS_ARCHIVE_PATH);
            $uploadPath = 'imsccImport/';
            $errors = [];
            if (!is_dir($baseDir.$uploadPath)) {
                @mkdir($baseDir.$uploadPath);
            }

            $filepath = $baseDir.$uploadPath;

            if (!Imscc13Import::unzip($file['tmp_name'], $filepath)) {
                return false;
            }

            // We detect if it is cc v1.3
            $detected = Imscc13Import::detectFormat($filepath);
            if ($detected) {
                Imscc13Import::execute($filepath);
                Display::addFlash(Display::return_message(get_lang('IMSCCFileImported'), 'normal', false));
            }
        }
    }
}

$template = new Template(get_lang('ImportCcVersion13'));
Display::addFlash(Display::return_message(get_lang('IMSCCImportInstructions'), 'normal', false));
$template->assign('form', $form->returnForm());
$templateName = $template->get_template('common_cartridge/import_cc.tpl');
$content = $template->fetch($templateName);
$template->assign('header', get_lang('ImportCcVersion13'));
$template->assign('content', $content);
$template->display_one_col_template();
