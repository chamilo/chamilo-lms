<?php
/* For licensing terms, see /license.txt */
/**
 * Show specified user certificate.
 *
 * @package chamilo.certificate
 */
require_once '../main/inc/global.inc.php';

$action = isset($_GET['action']) ? $_GET['action'] : null;
$userId = isset($_GET['user_id']) ? $_GET['user_id'] : 0;

$certificate = new Certificate($_GET['id'], $userId);

CustomCertificatePlugin::redirectCheck($certificate, $_GET['id'], $userId);

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
            Display::display_reduced_header();
            echo Display::return_message(
                get_lang('CertificateExistsButNotPublic'),
                'warning'
            );
            Display::display_reduced_footer();
            break;
        }

        if (!$certificate->isAvailable()) {
            Display::display_reduced_header();
            echo Display::return_message(
                get_lang('NoCertificateAvailable'),
                'error'
            );
            Display::display_reduced_footer();
            break;
        }

        // Show certificate HTML
        $certificate->show();
        break;
}
