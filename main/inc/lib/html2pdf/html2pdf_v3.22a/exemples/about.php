<?php
/**
 * Logiciel : exemple d'utilisation de HTML2PDF
 * 
 * Convertisseur HTML => PDF, utilise fpdf de Olivier PLATHEY 
 * Distribué sous la licence LGPL. 
 *
 * @author		Laurent MINGUET <webmaster@spipu.net>
 */
	require_once(dirname(__FILE__).'/../html2pdf.class.php');

	// récupération de l'html
 	ob_start();
 	include(dirname('__FILE__').'/res/about.php');
	$content = ob_get_clean();

	// initialisation de HTML2PDF
	$html2pdf = new HTML2PDF('P','A4','fr', array(0, 0, 0, 0));
	
	// affichage de la page en entier
	$html2pdf->pdf->SetDisplayMode('fullpage');
	
	// conversion
	$html2pdf->WriteHTML($content, isset($_GET['vuehtml']));
	
	// ajout de l'index (obligatoirement en fin de document)
	$html2pdf->setNewPage();
	$html2pdf->pdf->CreateIndex('Index', 25, 12);
	
	// envoie du PDF
	$html2pdf->Output('about.pdf');
