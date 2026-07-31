<?php

/* For licensing terms, see /license.txt */

/**
 * Show specified user certificate.
 */
require_once '../main/inc/global.inc.php';

$action = $_GET['action'] ?? null;
$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$certificateId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

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

// Access control has to be fully settled *before* instantiating Certificate: its constructor
// regenerates the certificate as a side effect (writes the HTML file, updates the gradebook
// row and assigns skills to the owner). Instantiating first would let an unauthenticated
// visitor trigger those writes for any certificate by iterating ?id=N, even though the
// checks below would then deny the response.
$certificateData = Certificate::getCertificateData($certificateId);
if (empty($certificateData)) {
    api_not_allowed(false, Display::return_message(get_lang('NoCertificateAvailable'), 'warning'));
}

// Only the owner, a platform admin, or a teacher of the certificate's course may view or
// export a certificate. Compare against the owner recorded in the database — not the $userId
// GET parameter, which an attacker can spoof to their own ID while supplying someone else's
// certificate $id.
// Anonymous visitors are only ever allowed to reach a certificate that was explicitly
// published for public verification, and that must hold for every action: the 'export' branch
// never calls isVisible(), so leaving the check to the 'default' branch alone let an
// unauthenticated visitor enumerate ?action=export&id=N and download the full PDF (name,
// course, score) of every certificate on the platform.
$currentUserId = api_get_user_id();
if (api_is_anonymous()) {
    if (!Certificate::isPubliclyVisible($certificateData['cat_id'])) {
        api_not_allowed(false, Display::return_message(get_lang('CertificateExistsButNotPublic'), 'warning'));
    }
} elseif ((int) $currentUserId !== (int) $certificateData['user_id']) {
    $isCourseTeacher = false;
    if (!empty($category) && !empty($category->get_course_code())) {
        $isCourseTeacher = CourseManager::is_course_teacher($currentUserId, $category->get_course_code());
    }
    if (!api_is_platform_admin() && !$isCourseTeacher) {
        api_not_allowed(true);
    }
}

$certificate = new Certificate($certificateId, $userId);

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
