<?php
/* For licensing terms, see /chamilo_license.txt */

/**
 * Export html to pdf
 * @author Juan Carlos Raña <herodoto@telefonica.net>, initial code, 2009
 * @author Ivan Tcholakov <ivantcholakov@gmail.com>, 2010
 * @deprecated now we use the pdf.lib.php library for all pdf export issues
 */

require '../inc/global.inc.php';

api_protect_course_script();
api_block_anonymous_users();

define('_MPDF_PATH', api_get_path(LIBRARY_PATH).'mpdf/');
require_once _MPDF_PATH.'mpdf.php';

$content_pdf = api_html_entity_decode($_POST['contentPDF'], ENT_QUOTES, api_get_system_encoding());
$title_pdf = api_html_entity_decode($_POST['titlePDF'], ENT_QUOTES, api_get_system_encoding());

$title_pdf = api_utf8_encode($title_pdf, api_get_system_encoding());
$content_pdf = api_utf8_encode($content_pdf, api_get_system_encoding());

$html='
<!-- defines the headers/footers - this must occur before the headers/footers are set -->

<!--mpdf
<pageheader name="odds" content-left="'.$title_pdf.'"  header-style-left="color: #880000; font-style: italic;"  line="1" />
<pagefooter name="odds" content-right="{PAGENO}/{nb}" line="1" />

<!-- set the headers/footers - they will occur from here on in the document -->
<!--mpdf
<setpageheader name="odds" page="odd" value="on" show-this-page="1" />
<setpagefooter name="odds" page="O" value="on" />

mpdf-->'.$content_pdf;


$css_file = api_get_path(TO_SYS, WEB_CSS_PATH).api_get_setting('stylesheets').'/print.css';
if (file_exists($css_file)) {
	$css = @file_get_contents($css_file);
} else {
	$css = '';
}

$pdf = new mPDF('UTF-8', 'A4', '', '', 30, 20, 27, 25, 16, 13, 'P');

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
