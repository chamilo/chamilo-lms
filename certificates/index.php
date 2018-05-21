<?php
/* For licensing terms, see /license.txt */
/**
 * Show specified user certificate.
 *
 * @package chamilo.certificate
 */
require_once '../main/inc/global.inc.php';

$action = isset($_GET['action']) ? $_GET['action'] : null;
$certificate = new Certificate($_GET['id']);

if (api_get_plugin_setting('customcertificate', 'enable_plugin_customcertificate') == 'true') {
    $infoCertificate = CustomCertificatePlugin::getCertificateData(intval($_GET['id']));
    if (!empty($infoCertificate)) {
        if ($certificate->user_id == api_get_user_id() && !empty($certificate->certificate_data)) {
            $certificateId = $certificate->certificate_data['id'];
            $extraFieldValue = new ExtraFieldValue('user_certificate');
            $value = $extraFieldValue->get_values_by_handler_and_field_variable(
                $certificateId,
                'downloaded_at'
            );
            if (empty($value)) {
                $params = [
                        'item_id' => $certificate->certificate_data['id'],
                        'extra_downloaded_at' => api_get_utc_datetime(),
                ];
                $extraFieldValue->saveFieldValues($params);
            }
        }

        $url = api_get_path(WEB_PLUGIN_PATH).'customcertificate/src/print_certificate.php'.
                '?student_id='.$infoCertificate['user_id'].
                '&course_code='.$infoCertificate['course_code'].
                '&session_id='.$infoCertificate['session_id'];
        header('Location: '.$url);
        exit;
    }
}

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
