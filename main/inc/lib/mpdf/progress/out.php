<?php

$tempfilename = $_REQUEST['filename'].'.pdf';
$opname = $_REQUEST['opname'];
$dest = $_REQUEST['dest'];
	// Modified by Ivan Tcholakov, 28-JUN-2010.
	//if ($tempfilename && file_exists('../tmp/'.$tempfilename)) {
	if ($tempfilename && file_exists(_MPDF_TEMP_PATH.$tempfilename)) {
	//
		header("Pragma: ");
		header("Cache-Control: private");
		header("Content-transfer-encoding: binary\n");
		if ($dest=='I') {
			header('Content-Type: application/pdf');
			header('Content-disposition: inline; filename='.$opname);
		}

		else if ($dest=='D') {
			if(isset($_SERVER['HTTP_USER_AGENT']) and strpos($_SERVER['HTTP_USER_AGENT'],'MSIE')) {
				if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') {
					header('HTTP/1.1 200 OK');
					header('Status: 200 OK');
					header('Pragma: anytextexeptno-cache', true);
					header("Cache-Control: public, must-revalidate");
				}
				else {
					header('Cache-Control: public, must-revalidate');
					header('Pragma: public');
				}
				header('Content-Type: application/force-download');
			}
			else {
				header('Content-Type: application/octet-stream');
			}
			header('Content-disposition: attachment; filename='.$opname);
		}
		// Modified by Ivan Tcholakov, 28-JUN-2010.
		//$filesize = filesize('../tmp/'.$tempfilename);
		$filesize = filesize(_MPDF_TEMP_PATH.$tempfilename);
		//
		header("Content-length:".$filesize);
		// Modified by Ivan Tcholakov, 28-JUN-2010.
		//$fd=fopen('../tmp/'.$tempfilename,'r');
		$fd=fopen(_MPDF_TEMP_PATH.$tempfilename,'r');
		//
		fpassthru($fd);
		fclose($fd);
		// Modified by Ivan Tcholakov, 28-JUN-2010.
		//unlink('../tmp/'.$tempfilename);
		unlink(_MPDF_TEMP_PATH.$tempfilename);
		//
		// ====================== DELETE OLD FILES FIRST - Housekeeping =========================================
		// Clear any files in directory that are >24 hrs old
		$interval = 86400;
		// Modified by Ivan Tcholakov, 28-JAN-2010.
		//if ($handle = opendir('../tmp')) {
		//   while (false !== ($file = readdir($handle))) {
		//	if (((filemtime('../tmp/'.$file)+$interval) < time()) && ($file != "..") && ($file != ".")) {
		//		unlink('../tmp/'.$file);
		//	}
		//   }
		//   closedir($handle);
		//}
		if ($handle = opendir(_MPDF_TEMP_PATH)) {
		   while (false !== ($file = readdir($handle))) {
			if (((filemtime(_MPDF_TEMP_PATH.$file)+$interval) < time()) && ($file != "..") && ($file != ".")) {
				unlink(_MPDF_TEMP_PATH.$file);
			}
		   }
		   closedir($handle);
		}
		//
		exit;
	}
?>