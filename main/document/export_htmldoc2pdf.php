<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

$file = Security::remove_XSS($_GET['file']);
$file_info = pathinfo($file);
$filename = $file_info['basename'];
$extension = $file_info['extension'];

if (!($extension == 'html' || $extension == 'htm')) {
	exit;
}

define('_MPDF_PATH', api_get_path(LIBRARY_PATH).'mpdf/');
require_once _MPDF_PATH.'mpdf.php';

$filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';

$document_html = @file_get_contents($filepath.$file);
api_set_encoding_html($document_html, 'UTF-8');	// The library mPDF expects UTF-8 encoded input data.

$title = api_get_title_html($document_html, 'UTF-8', 'UTF-8');	// TODO: Maybe it is better idea the title to be passed through
																// $_GET[] too, as it is done with file name.
																// At the moment the title is retrieved from the html document itself.
if (empty($title)) {
	$title = $filename; // Here file name is expected to contain ASCII symbols only.
}

$pdf = new mPDF('UTF-8', 'A4', '', '', 30, 20, 27, 25, 16, 13, 'P');

$pdf->directionality = api_get_text_direction();

$pdf->useOnlyCoreFonts = true;

$pdf->mirrorMargins = 1;			// Use different Odd/Even headers and footers and mirror margins

$pdf->defaultheaderfontsize = 10;	// in pts
$pdf->defaultheaderfontstyle = B;	// blank, B, I, or BI
$pdf->defaultheaderline = 1;		// 1 to include line below header/above footer

$pdf->defaultfooterfontsize = 12;	// in pts
$pdf->defaultfooterfontstyle = B;	// blank, B, I, or BI
$pdf->defaultfooterline = 1;		// 1 to include line below header/above footer

$pdf->SetHeader('{DATE j-m-Y}|{PAGENO}/{nb}|'.$title);
$pdf->SetFooter('{PAGENO}');		// defines footer for Odd and Even Pages - placed at Outer margin

$pdf->SetAuthor('Documents Chamilo');
$pdf->SetTitle($title);
$pdf->SetSubject('Exported from Chamilo Documents');
$pdf->SetKeywords('Chamilo Documents');

$pdf->WriteHTML($document_html);

$pdf->Output(replace_dangerous_char($title.'.pdf'), 'D');
