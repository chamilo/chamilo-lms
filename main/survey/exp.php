<?php 
/*
    DOKEOS - elearning and course management software

    For a full list of contributors, see documentation/credits.html
   
    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    See "documentation/licence.html" more details.
 
    Contact: 
		Dokeos
		Rue des Palais 44 Paleizenstraat
		B-1030 Brussels - Belgium
		Tel. +32 (2) 211 34 56
*/

/**
*	@package dokeos.survey
* 	@author 
* 	@version $Id: exp.php 10605 2007-01-06 17:55:20Z pcool $
*/

$filename = 'account_history_'.date('mdY').'.csv';
//$strExportList = array(0=>'a',1=>'b',2=>'c');
	//$strExportList=$_REQUEST['strExportList'];
//echo "Transaction Date, Particulars, Amount($)";
if($_REQUEST[qtype]=="1"){
$strRows .="Total No. of users attempted this survey =";
$strRows .=$_REQUEST['count_total_user'];
$strRows .="\n";
$strRows .=$_REQUEST['caption'];
$strRows .= ",";
$strRows .= "No. of Votes";
$strRows .= ",";
$strRows .= "Result (%)";
$strRows .="\n";
$strRows .=$_REQUEST['a1'];
$strRows .=",";
$strRows .=$_REQUEST[count_a1];
$strRows .=",";
$strRows .="30%";
$strRows .="\n";
$strRows .=$_REQUEST['a2'];
$strRows .=",";
$strRows .=$_REQUEST[count_a2];
$strRows .=",";
$strRows .="40%";
}
if($strExportList[0])
		{		
			$intCount = 0;
			//$strRows=''; 
			foreach($strExportList as $arrRow)
			 {
				$ItemCount = count($strExportList);

					$strRows .=strip_tags(str_replace("<BR>"," ", $arrRow[0])).",";
					$strRows .=str_pad($arrRow[1],255);
					
					  $strRows .=",".str_pad($arrRow[2],12);
				 			
					$strRows .=",";

				$strRows.= "\r\n";	
				$intCount++;					
			 }
		}

// Stream the file, will prompt user to open or save
header("Content-type: application/vnd.ms-excel");
header ("Content-Type: application/octet-stream");
header("Content-disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Expires: 0");
echo $strRows;
die();
?>