<?php

include("../mpdf.php");
include("../mpdfi/mpdfi.php");

$mpdf=new mPDFI('','','','',15,15,47,16,9,9); 

$mpdf->SetDocTemplate('sample_logoheader2.pdf',1);	// 1|0 to continue after end of document or not - used on matching page numbers

//===================================================
$mpdf->AddPage();
$mpdf->WriteHTML('Hallo World');
$mpdf->AddPage();
$mpdf->WriteHTML('Hallo World');
$mpdf->AddPage();
$mpdf->WriteHTML('Hallo World');
//===================================================

$mpdf->RestartDocTemplate();

//===================================================
$mpdf->AddPage();
$mpdf->WriteHTML('Hallo World');
$mpdf->AddPage();
$mpdf->WriteHTML('Hallo World');
$mpdf->AddPage();
$mpdf->WriteHTML('Hallo World');
//===================================================


$mpdf->Output();

exit;

?>