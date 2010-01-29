<?php



$html = '
<h1>mPDF</h1>
<h2>Basic Example Using CSS Styles</h2>
<p class="breadcrumb">Chapter &raquo; Topic</p>
<h3>Heading 3</h3>
<h4>Heading 4</h4>
<h5>Heading 5</h5>
<p>Nulla felis erat, imperdiet eu, ullamcorper non, nonummy quis, elit. Suspendisse potenti. Ut a eros at ligula vehicula pretium. Maecenas feugiat pede vel risus. Nulla et lectus. Fusce eleifend neque sit amet erat. Integer consectetuer nulla non orci. Morbi feugiat pulvinar dolor. Cras odio. Donec mattis, nisi id euismod auctor, neque metus pellentesque risus, at eleifend lacus sapien et risus. Phasellus metus. Phasellus feugiat, lectus ac aliquam molestie, leo lacus tincidunt turpis, vel aliquam quam odio et sapien. Mauris ante pede, auctor ac, suscipit quis, malesuada sed, nulla. Integer sit amet odio sit amet lectus luctus euismod. Donec et nulla. Sed quis orci. </p>
<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Proin vel sem at odio varius pretium. Maecenas sed orci. Maecenas varius. Ut magna ipsum, tempus in, condimentum at, rutrum et, nisl. Vestibulum interdum luctus sapien. Quisque viverra. Etiam id libero at magna pellentesque aliquet. Nulla sit amet ipsum id enim tempus dictum. Maecenas consectetuer eros quis massa. Mauris semper velit vehicula purus. Duis lacus. Aenean pretium consectetuer mauris. Ut purus sem, consequat ut, fermentum sit amet, ornare sit amet, ipsum. Donec non nunc. Maecenas fringilla. Curabitur libero. In dui massa, malesuada sit amet, hendrerit vitae, viverra nec, tortor. Donec varius. Ut ut dolor et tellus adipiscing adipiscing. </p>
<p>Proin aliquet lorem id felis. Curabitur vel libero at mauris nonummy tincidunt. Donec imperdiet. Vestibulum sem sem, lacinia vel, molestie et, laoreet eget, urna. Curabitur viverra faucibus pede. Morbi lobortis. Donec dapibus. Donec tempus. Ut arcu enim, rhoncus ac, venenatis eu, porttitor mollis, dui. Sed vitae risus. In elementum sem placerat dui. Nam tristique eros in nisl. Nulla cursus sapien non quam porta porttitor. Quisque dictum ipsum ornare tortor. Fusce ornare tempus enim. </p>
';


//==============================================================
//==============================================================
//==============================================================

include("../mpdf.php");

$mpdf=new mPDF(); 

$mpdf->useOnlyCoreFonts = true;

$mpdf->SetDisplayMode('fullpage');

// LOAD a stylesheet
$stylesheet = file_get_contents('mpdfstyleA4.css');
$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text

$mpdf->WriteHTML($html,2);

$mpdf->Output();

exit;
//==============================================================
//==============================================================
//==============================================================

?>