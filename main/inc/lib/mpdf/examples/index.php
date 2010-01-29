<?php

$ff = scandir('./');
sort($ff);
$files = array();
foreach($ff AS $f) {
	if (preg_match('/example[0]{0,1}(\d+)_(.*?)\.php/',$f,$m)) {
		$num = intval($m[1]);
		$files[$num] = array(ucfirst(preg_replace('/_/',' ',$m[2])), $m[0]);
	}
}
echo '<html><body><h3>mPDF Example Files</h3>';

foreach($files AS $n=>$f) {
	echo '<p>'.$n.') '.$f[0].' &nbsp; <a href="'.$f[1].'">PDF</a> &nbsp;  <small><a href="show_code.php?filename='.$f[1].'">PHP</a></small></p>';
}

echo '</body></html>';
exit;

?>