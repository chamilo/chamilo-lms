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
* 	@version $Id: blank.php 10223 2006-11-27 14:45:59Z pcool $
*/

// name of the language file that needs to be included 
$language_file = 'survey';

$temp = $_REQUEST['temp'];
require ('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
$status = surveymanager::get_status();
if($status==5)
{
api_protect_admin_script();
}
$cidReq = $_REQUEST['cidReq'];
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
$ques = $_REQUEST['ques'];
$ans = $_REQUEST['ans'];
$answers = explode("|",$ans);
$count = count($answers);
$qtype = $_REQUEST['qtype'];



?>


<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Blank template</title>
<style type="text/css">
<!--
body {
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
}
.style5 {font-size: 12px; font-weight: bold; font-family: Arial, Helvetica, sans-serif;}
-->
</style>
<link href="file:///D|/EasyPHP1-8/www/dokeosIFA/main/css/default.css" rel="stylesheet" type="text/css">
</head>

<body>
<table width="100%"  border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="69%" height="29" bgcolor="#264269"><font face="Verdana">&nbsp;&nbsp;<font color="#FFFFFF" size="2"><strong>My Portal - My Organisation</strong></font></font></td>
    <td width="31%" align="right" bgcolor="#264269" class="bg-1">&nbsp;&nbsp;</td>
  </tr>
  <tr>
    <td height="24" bgcolor="#90AFDD" class="bg-3">&nbsp;</td>
    <td height="24" align="right" bgcolor="#90AFDD" class="topBanner"><span class="text"></span>&nbsp;&nbsp;</td>
  </tr>
  <tr bordercolor="#6699FF">
    <td colspan="2" align="center" valign="top"><br><br>
      <table width="707" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <?
		switch ($qtype)
		{
		case "Yes/No":
		{?>
          <td width="100%" bgcolor="F6F5F5"><p><font face="Verdana"><strong><font size="2">Question:</font> </strong><br>
              <?php echo html_entity_decode($ques);?><br>
              <font size="2"><strong>Answers:</strong></font><br>
                     <textarea cols="50" rows="3" disabled='true'><?echo $answers[0];?></textarea>
                   &nbsp;
              <input name="radiobutton" type="radio" value="radiobutton">
</font></p>
            <p><font face="Verdana">              
              <textarea cols="50" rows="3" disabled='true'><?echo $answers[1];?></textarea>
              &nbsp;
              <input name="radiobutton" type="radio" value="radiobutton">
            </font></p></td>
          <td width="10" height="161" bgcolor="F6F5F5">&nbsp;</td>
          <?
		break;
		}
		case "Multiple Choice (multiple answer)":
		{?>
          <td width="100%" bgcolor="F6F5F5"><font face="Verdana"><strong><font size="2">Question:</font> </strong><br>
                <?php echo $ques;?><br>
                <br>
                <strong><font size="2">Answers: </font></strong><br>
           
              <?
		$i=0;
		for($p=1;$p<$count;$i++,$p++)
		{
		?>
              <textarea cols="50" rows="3" disabled='true'><?php echo $answers[$i]; ?></textarea>&nbsp;   
              <input type="checkbox" name="checkbox" value="checkbox"></font>
              <br>
              <?
		}
		?>
          </td>
          <td width="8" height="161" bgcolor="F6F5F5">&nbsp;</td> 
          <?
		break;
		}
		case "Multiple Choice (single answer)":
		{?>
          <td width="100%" bgcolor="F6F5F5"><font face="Verdana"><strong><font size="2">Question:</font> </strong><br>
                <?php echo $ques;?><br>
                <br>
                <strong><font size="2">Answers: </font></strong><br>
          
              <?
		$i=0;
		for($p=1;$p<$count;$i++,$p++)
		{
		?>
              <textarea cols="50" rows="3" disabled='true'><?php echo $answers[$i]; ?></textarea>
              <input name="radiobutton" type="radio" value="radiobutton">  </font>
              <br>
              <?
		}
		?>
          </td>
          <td width="8" height="161" bgcolor="F6F5F5">&nbsp;</td>
          <?
		break;
		}
		case "Open":
		{?>
          <td width="87" bgcolor="F6F5F5"><font face="Verdana"><strong><font size="2">Question:</font> </strong><br>
                <?php echo $ques;?><br>
                <br>
                <strong><font size="2">Answers: </font></strong><br></font>
            
              <textarea  style="WIDTH: 100%" name="defaultext" rows=3 cols=60>
        </textarea>
          </td>
          <?
		break;
		}
		case "Numbered":
		{?>
           <td width="144" bgcolor="F6F5F5"><font face="Verdana"><strong><font size="2">Question:</font> </strong><br>
                <?php echo $ques;?><br>
                <br>
                <strong><font size="2">Answers: </font></strong><br>
            
              <?
		$i=0;
		for($p=1;$p<$count;$i++,$p++)
		{
		?>
              <textarea cols="50" rows="3" disabled='true'><?php echo $answers[$i]; ?></textarea>
              <select>
                <option value="not applicable">Not Applicable</option>
				<option value="$i">1</option>
                <option value="$i">2</option>
                <option value="$i">3</option>
                <option value="$i">4</option>
                <option value="$i">5</option>
                <option value="$i">6</option>
                <option value="$i">7</option>
                <option value="$i">8</option>
                <option value="$i">9</option>
                <option value="$i">10</option></font>
              </select>
              <br>
              <?
		}
		?>
          </td>
          <?
		break;		
		}
		}
		
		?>
        </tr>
      </table>
      <p>&nbsp;</p></td>
  </tr>
  <tr align="right">
    <td height="31" align="left" bgcolor="#E5EDF9" class="bg-4"><font face="Verdana">&nbsp;&nbsp;<span class="text"><font size="2">Manager : <font color="#4171B5"><strong>user admin</strong></font></font></span> </font></td>
    <td height="31" bgcolor="#E5EDF9" class="bg-4"><span class="text"><font size="2" face="Verdana">Platform <font color="#4171B5"><strong>Dokeos 1.6.3</strong></font></font> <font size="2" face="Verdana">&copy; 2006</font></span>&nbsp;&nbsp;</td>
  </tr>
</table>
</body>
</html>
