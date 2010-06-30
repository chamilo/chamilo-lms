<?php
/* For licensing terms, see /chamilo_license.txt */

/**
 * Export html to pdf
 * @author Juan Carlos Raña <herodoto@telefonica.net>, initial code, 2009
 * @author Ivan Tcholakov <ivantcholakov@gmail.com>, 2010
 */

require '../inc/global.inc.php';

api_protect_course_script();
api_block_anonymous_users();

define('_MPDF_PATH', api_get_path(LIBRARY_PATH).'mpdf/');
require_once _MPDF_PATH.'mpdf.php';

$content_pdf = api_html_entity_decode($_POST['contentPDF'], ENT_QUOTES, api_get_system_encoding());
$title_pdf = api_html_entity_decode($_POST['titlePDF'], ENT_QUOTES, api_get_system_encoding());

$html = '
<page backtop="10mm" backbottom="10mm" footer="page">
      <page_header>
{TITLE_PDF}<br/><hr/>
      </page_header>
{CONTENT_PDF}
      <page_footer>
            <hr/>
      </page_footer>
 </page>
';

$html = api_utf8_encode(str_replace(array('{TITLE_PDF}', '{CONTENT_PDF}'), array($title_pdf, $content_pdf), $html), api_get_system_encoding());
$title_pdf = api_utf8_encode($title_pdf, api_get_system_encoding());

$css_file = api_get_path(TO_SYS, WEB_CSS_PATH).api_get_setting('stylesheets').'/print.css';
if (file_exists($css_file)) {
	$css = @file_get_contents($css_file);
} else {
	$css = '';
}

$pdf = new mPDF('UTF-8', 'A4', '', '', 32, 25, 27, 25, 16, 13, 'P');

$pdf->directionality = api_get_text_direction();

$pdf->useOnlyCoreFonts = true;

$pdf->SetAuthor('Wiki Chamilo');
$pdf->SetTitle($title_pdf);
$pdf->SetSubject('Exported from Chamilo Wiki');
$pdf->SetKeywords('Chamilo Wiki');

if (!empty($css)) {
	$pdf->WriteHTML($css, 1);
	$pdf->WriteHTML($html, 2);
} else {
	$pdf->WriteHTML($html);
}

if (empty($title_pdf)) {
	$title_pdf = 'Exported from Chamilo Wiki';
}
$pdf->Output(replace_dangerous_char($title_pdf.'.pdf'), 'D');
