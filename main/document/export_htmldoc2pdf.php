<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

api_protect_course_script();
api_block_anonymous_users();

$file = Security::remove_XSS($_GET['file']);
$file_info = pathinfo($file);
$dirname = str_replace("\\", '/', $file_info['dirname']);
$filename = $file_info['basename'];
$filename =str_replace('_','',$filename);
$extension = $file_info['extension'];

if (!($extension == 'html' || $extension == 'htm')) {
	exit;
}

if($extension == 'html'){
	$filename =basename($filename,'.html');
}elseif($extension == 'htm'){
	$filename =basename($filename,'.htm');
}

define('_MPDF_PATH', api_get_path(LIBRARY_PATH).'mpdf/');
require_once _MPDF_PATH.'mpdf.php';

$filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';

$document_html = @file_get_contents($filepath.$file);

//clean styles and javascript document
$clean_search = array(
	'@<script[^>]*?>.*?</script>@si',
	'@<style[^>]*?>.*?</style>@siU'
	);
$document_html = preg_replace($clean_search, '', $document_html);

//absolute path for frames.css //TODO: necessary?
$absolute_css_path=api_get_path(WEB_CODE_PATH).'css/'.api_get_setting('stylesheets').'/frames.css';
$document_html=str_replace('href="./css/frames.css"',$absolute_css_path,$document_html);

//replace relative path by absolute path for resources
$document_html= str_replace('src="/chamilo/main/default_course_document/', 'temp_template_path', $document_html);// before save src templates not apply
$document_html= str_replace('../','',$document_html);
$src_http_www= 'src="'.api_get_path(WEB_COURSE_PATH).$_course['path'].'/document/';
$document_html= str_replace('src="',$src_http_www,$document_html);
$document_html= str_replace('temp_template_path', 'src="/chamilo/main/default_course_document/', $document_html);// restore src templates

//
api_set_encoding_html($document_html, 'UTF-8');	// The library mPDF expects UTF-8 encoded input data.

$title = api_get_title_html($document_html, 'UTF-8', 'UTF-8');	// TODO: Maybe it is better idea the title to be passed through
																// $_GET[] too, as it is done with file name.
																// At the moment the title is retrieved from the html document itself.
if (empty($title)) {
	$title = $filename; // Here file name is expected to contain ASCII symbols only.
}

$pdf = new mPDF('UTF-8', 'A4', '', '', 30, 20, 27, 25, 16, 13, 'P');

$pdf->SetBasePath($basehref);

$pdf->directionality = api_get_text_direction(); // TODO: To be read from the html document.

$pdf->useOnlyCoreFonts = true;

$pdf->mirrorMargins = 1;			// Use different Odd/Even headers and footers and mirror margins

$pdf->defaultheaderfontsize = 10;	// in pts
$pdf->defaultheaderfontstyle = B;	// blank, B, I, or BI
$pdf->defaultheaderline = 1;		// 1 to include line below header/above footer

$pdf->defaultfooterfontsize = 12;	// in pts
$pdf->defaultfooterfontstyle = B;	// blank, B, I, or BI
$pdf->defaultfooterline = 1;		// 1 to include line below header/above footer

$pdf->SetHeader($filename.'|||');// ('{DATE j-m-Y}|{PAGENO}/{nb}|'.$title);
$pdf->SetFooter('||{PAGENO}');		// defines footer for Odd and Even Pages - placed at Outer margin

$pdf->SetAuthor('Documents Chamilo');
$pdf->SetTitle($title);
$pdf->SetSubject('Exported from Chamilo Documents');
$pdf->SetKeywords('Chamilo Documents');

$pdf->WriteHTML($document_html,2);

$pdf->Output(replace_dangerous_char($title.'.pdf'), 'D');