<?php
//Juan Carlos Raña export to pdf for Dokeos
 
include("../inc/global.inc.php");
api_block_anonymous_users();
require('../plugin/html2fpdf/html2fpdf.php');

$contentPDF=stripslashes(api_html_entity_decode($_POST['contentPDF'], ENT_QUOTES, $charset)); 
$titlePDF=stripslashes(api_html_entity_decode($_POST['titlePDF'], ENT_QUOTES, $charset)); 

//activate Output -Buffer:
ob_start();
////START-OF-PHP code
echo $contentPDF; //original
//END -OF- PHP code
//Output-Buffer in variable:
$htmlbuffer=ob_get_contents();
//// delete Output-Buffer:
ob_end_clean();
$pdf= new HTML2FPDF();
//$pdf->DisplayPreferences('FullScreen');
$pdf->AddPage();
$pdf->SetAuthor('Wiki Dokeos'); 
$pdf->SetTitle($titlePDF); 
$pdf->SetKeywords('Dokeos Wiki');
$pdf->WriteHTML($htmlbuffer); 
$pdf->Output();
?>
