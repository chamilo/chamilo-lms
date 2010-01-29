<?php

include("../mpdf.php");
include("../mpdfi/mpdfi.php");

$mpdf=new mPDFI(); 

$mpdf->Thumbnail('sample_orientation2.pdf', 4, 5);	// number per row	// spacing in mm

$mpdf->WriteHTML('<pagebreak /><div>Now with rotated pages</div>');

$mpdf->Thumbnail('sample_orientation3.pdf', 4);	// number per row	// spacing in mm


$mpdf->Output();

exit;


?>