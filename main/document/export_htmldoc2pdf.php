<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

define('_MPDF_PATH', api_get_path(LIBRARY_PATH).'mpdf/');
require_once _MPDF_PATH.'mpdf.php';

$file = Security::remove_XSS($_GET['file']);
$filename =basename($file,'.html');
$filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';

$title_pdf = $filename;
$content_pdf = @file_get_contents($filepath.$file);

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

$pdf->SetAuthor('Documents Chamilo');
$pdf->SetTitle($title_pdf);
$pdf->SetSubject('Exported from Chamilo Documents');
$pdf->SetKeywords('Chamilo Documents');

if (!empty($css)) {
	$pdf->WriteHTML($css, 1);
	$pdf->WriteHTML($html, 2);
} else {
	$pdf->WriteHTML($html);
}

if (empty($title_pdf)) {
	$title_pdf = 'Exported from Chamilo Documents';
}
$pdf->Output(replace_dangerous_char($title_pdf.'.pdf'), 'D');

?>