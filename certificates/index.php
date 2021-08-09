<?php

/* For licensing terms, see /license.txt */

/**
 * Show specified user certificate.
 */
require_once '../main/inc/global.inc.php';

$action = isset($_GET['action']) ? $_GET['action'] : null;
$userId = isset($_GET['user_id']) ? $_GET['user_id'] : 0;
$certificateId = isset($_GET['id']) ? $_GET['id'] : 0;

$category = Category::findByCertificate($certificateId);

// Check if the certificate should use the course language
if (!empty($category) && !empty($category->get_course_code())) {
    $courseInfo = api_get_course_info($category->get_course_code());
    $language = $courseInfo['language'];
    $languageFilesToLoad = api_get_language_files_to_load($language);

    foreach ($languageFilesToLoad as $languageFile) {
        include $languageFile;
    }

    // Overwrite the interface language with the course language
    $language_interface = $language;
    $language_interface_initial_value = $language_interface;
}

$certificate = new Certificate($certificateId, $userId);
$certificateData = $certificate->get($certificateId);
if (empty($certificateData)) {
    api_not_allowed(false, Display::return_message(get_lang('NoCertificateAvailable'), 'warning'));
}

CustomCertificatePlugin::redirectCheck($certificate, $certificateId, $userId);

switch ($action) {
    case 'export':
        $hideExportLink = api_get_setting('hide_certificate_export_link');
        $hideExportLinkStudent = api_get_setting('hide_certificate_export_link_students');
        if ($hideExportLink === 'true' ||
            (api_is_student() && $hideExportLinkStudent === 'true')
        ) {
            api_not_allowed(true);
        }

        $certificate->generate(['hide_print_button' => true]);

        if ($certificate->isHtmlFileGenerated()) {
            $certificatePathList[] = $certificate->html_file;

            $pdfParams = [
                'top' => 0,
                'right' => 0,
                'bottom' => 0,
                'left' => 0,
            ];

            $orientation = api_get_configuration_value('certificate_pdf_orientation');
            $pdfParams['orientation'] = 'landscape';
            if (!empty($orientation)) {
                $pdfParams['orientation'] = $orientation;
            }

            $pageFormat = $pdfParams['orientation'] === 'landscape' ? 'A4-L' : 'A4';
            $userInfo = api_get_user_info($certificate->user_id);
            $pdfName = api_replace_dangerous_char(
                get_lang('Certificate').' '.$userInfo['username']
            );

            $pdf = new PDF($pageFormat, $pdfParams['orientation'], $pdfParams);

            if (api_get_configuration_value('add_certificate_pdf_footer')) {
                $pdf->setCertificateFooter();
            }

            $pdf->html_to_pdf(
                $certificatePathList,
                $pdfName,
                null,
                false,
                false
            );
        }
        break;
    default:
        // Special rules for anonymous users
        if (!$certificate->isVisible()) {
            api_not_allowed(false, Display::return_message(get_lang('CertificateExistsButNotPublic'), 'warning'));
            break;
        }

        if (!$certificate->isAvailable()) {
            api_not_allowed(false, Display::return_message(get_lang('NoCertificateAvailable'), 'warning'));
            break;
        }

        // Show certificate HTML
        $certificate->show();
        break;
}
