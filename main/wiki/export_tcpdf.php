<?php
//Juan Carlos Raña export to pdf for Dokeos
 
include("../inc/global.inc.php");
api_block_anonymous_users();

require_once('../plugin/tcpdf/config/lang/eng.php');
require('../plugin/tcpdf/tcpdf.php');


$contentPDF=($_POST['contentPDF']); 
$titlePDF=($_POST['titlePDF']); 



// create new PDF document 
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, PDF_FONT_SIZE_MAIN, true); 

// set document information 
$pdf->SetCreator(PDF_CREATOR); 
$pdf->SetAuthor("Nicola Asuni"); 
$pdf->SetTitle("TCPDF Example 006"); 
$pdf->SetSubject("TCPDF Tutorial"); 
$pdf->SetKeywords("TCPDF, PDF, example, test, guide"); 

// set default header data 
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING); 

// set header and footer fonts 
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN)); 
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA)); 

//set margins 
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT); 
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER); 
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER); 

//set auto page breaks 
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM); 

//set image scale factor 
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO); 

//set some language-dependent strings 
$pdf->setLanguageArray($l); 

//initialize document 
$pdf->AliasNbPages(); 

// add a page 
$pdf->AddPage(); 
// output the HTML content 
$pdf->writeHTML($contentPDF, true, 0, true, 0); 

// reset pointer to the last page 
$pdf->lastPage();


////////////////////////////////////////////////////////////////Generar el documento pdf //////////////////////////////////////////////
$pdf->Output();


?>