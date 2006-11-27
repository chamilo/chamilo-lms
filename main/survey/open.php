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
* 	@version $Id: open.php 10223 2006-11-27 14:45:59Z pcool $
*/

// name of the language file that needs to be included 
$language_file = 'survey';

require_once ('../inc/global.inc.php');
//api_protect_admin_script();
if(isset($_REQUEST['questtype']))
$add_question12=$_REQUEST['questtype'];
else
$add_question12=$_REQUEST['add_question'];
$n=$_REQUEST['n'];
require_once ("select_question.php");
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
$status = surveymanager::get_status();
if($status==5)
{
api_protect_admin_script();
}
require_once (api_get_path(LIBRARY_PATH)."/usermanager.lib.php");
$interbredcrump[] = array ("url" => "survey_list.php?cidReq=$cidReq&n=$n", "name" => get_lang('Survey'));
$cidReq=$_GET['cidReq'];
$curr_dbname = $_REQUEST['curr_dbname'];
$table_survey = Database :: get_course_table('survey');
$table_group =  Database :: get_course_table('survey_group');
$table_question = Database :: get_course_table('questions');
$Add = get_lang("addnewquestiontype");
$Multi = get_lang("open");
$groupid = $_REQUEST['groupid'];
$surveyid = $_REQUEST['surveyid'];
if ($_POST['action'] == 'addquestion')
{
	  $enter_question=$_POST['enterquestion'];
	  
     if(isset($_POST['next']))
	{
        $questtype = $_REQUEST['questtype'];
		$enter_question=$_POST['enterquestion'];
		$defaultext=$_POST['defaultext'];
		$alignment='';
		$open_ans="";
		
		$enter_question=trim($enter_question);
		if(empty($enter_question))
		$error_message = get_lang('PleaseEnterAQuestion')."<br>";		  
		
		//if(empty($defaultext))
		//$error_message = $error_message."<br>".get_lang('PleaseFillDefaultText');
		
		if(isset($error_message));
		//Display::display_error_message($error_message);	
		else
		{
		 $groupid = $_POST['groupid'];		 
		 $cidReq = $_GET['cidReq']; 
		 $curr_dbname = $_REQUEST['curr_dbname'];
		 $surveyid = $_REQUEST['surveyid'];			 SurveyManager::create_question($groupid,$surveyid,$questtype,$enter_question,$alignment,$answers,$open_ans,$answerT,$answerD,$rating,$curr_dbname);	  header("location:select_question_group.php?groupid=$groupid&surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
		 exit;
		}
	}
	elseif(isset($_POST['back']))
	{
	   $groupid = $_REQUEST['groupid'];
	   $surveyid = $_REQUEST['surveyid'];
	   $cidReq = $_GET['cidReq'];
	   $curr_dbname = $_REQUEST['curr_dbname'];	   header("location:addanother.php?groupid=$groupid&surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
	   exit;
	}
	elseif(isset($_POST['saveandexit']))
	{
	  $questtype = $_REQUEST['questtype'];
		$enter_question=$_POST['enterquestion'];
		$defaultext=$_POST['defaultext'];
	
		$alignment='';
		$open_ans="";
		
		$enter_question=trim($enter_question);
		if(empty($enter_question))
		$error_message = get_lang('PleaseEnterAQuestion')."<br>";		  
		
		//if(empty($defaultext))
		//$error_message = $error_message."<br>".get_lang('PleaseFillDefaultText');
		
		if(isset($error_message));
		//Display::display_error_message($error_message);	
		else
		{
	     $groupid = $_REQUEST['groupid'];
	     $questtype = $_REQUEST['questtype'];
		 $curr_dbname = $_REQUEST['curr_dbname'];
		 $surveyid = $_REQUEST['surveyid'];			 SurveyManager::create_question($groupid,$surveyid,$questtype,$enter_question,$alignment,$answers,$open_ans,$answerT,$answerD,$rating,$curr_dbname);
	     $cidReq = $_GET['cidReq'];
	     header("location:survey_list.php?cidReq=$cidReq&n=$n");
	     exit;
		}
	}
}
?>
<?
$tool = get_lang('AddAnotherQuestion');
Display::display_header($tool);
if( isset($error_message) )
{
	Display::display_error_message($error_message);	
}

select_question_type($add_question12,$groupid,$surveyid,$cidReq,$curr_dbname);
?>
<table>
<tr>
<td>
<?php api_display_tool_title($Add);?>
</td>
<td>
<?php api_display_tool_title($Multi);?>

</td>
</tr>
</table>
<BODY id=surveys>
<SCRIPT LANGUAGE="JAVASCRIPT">
function checkLength(form){
    if (form.description.value.length > 250){
        alert("Text too long. Must be 250 characters or less");
        return false;
    }
    return true;
}
</SCRIPT>
<DIV id=content>
<FORM name="frmitemchkboxmulti" action="<?php echo $_SERVER['PHP_SELF'];?>?cidReq=<?php echo $cidReq; ?>"  method="POST">
<input type="hidden" name="groupid" value="<?php echo $groupid; ?>">
<input type="hidden" name="surveyid" value="<?php echo $surveyid; ?>">
<input type="hidden" name="questtype" value="<?php echo $add_question12; ?>">
<input type="hidden" name="curr_dbname" value="<?php echo $curr_dbname; ?>">
<input type="hidden" name="action" value="addquestion" >
  <BR>
<TABLE class=outerBorder_innertable cellSpacing=0 cellPadding=0 width="100%" 
border=0>
  <TBODY>
  <TR>
    <TD class=pagedetails_heading>&nbsp;</TD>
  </TR></TBODY></TABLE>
<TABLE class=outerBorder_innertable cellSpacing=0 cellPadding=0 width="100%" 
align=center border=0>
  <TBODY>
  <TR class=white_bg>
    <TD height=30>Enter the question. </TD>
  </TR>
  <TR class=form_bg>
    <TD width=542 height=30><?php  api_disp_html_area('enterquestion','','200px');?><!-- <textarea name="enterquestion" id="enterquestion" cols="50" rows="6" class="text_field" style="width:100%;" ><?
					if(isset($_POST['enterquestion']))
						echo $_POST['enterquestion'];
					?></textarea>--></TD></TR>
 </TBODY></TABLE><BR>
<TABLE cellSpacing=0 cellPadding=0 width="100%" border=0>
  <TBODY>
  <TR class=white_bg>
    <TD class=pagedetails_heading>&nbsp;</TD>
  </TR></TBODY></TABLE>
<!-- <TABLE class=outerBorder_innertable cellSpacing=0 cellPadding=0 width="100%" 
border=0>
  <TBODY>
 <TR>
    <TD height=30>Default Text </TD></TR>
  <TR>
    <TD width=192 height=30><TEXTAREA onkeyup="this.value = this.value.slice(0, 200)" style="WIDTH: 100%" name="defaultext" rows=3 cols=60>
	<?if(isset($_POST['defaultext']))echo $_POST['defaultext'];?></TEXTAREA> 
    </TD></TR></TBODY></TABLE><BR>-->
	<?
	$sql = "SELECT * FROM $curr_dbname.survey WHERE survey_id='$surveyid'";
			$res=api_sql_query($sql);
			$obj=mysql_fetch_object($res);
			switch($obj->template)
			{
				case "template1":
					$temp = 'white';
					break;
				case "template2":
					$temp = 'bluebreeze';
					break;
				case "template3":
					$temp = 'brown';
					break;
				case "template4":
					$temp = 'grey';
					break;	
				case "template5":
					$temp = 'blank';
					break;
			}
		
	?>


<BR>
<DIV align=center> 
	<input type="submit"  name="back" value="<?php echo get_lang('Back');?>">
	<input type="submit"  name="saveandexit" value="<?php echo get_lang("saveandexit"); ?>">
	<input type="button" value="<?php echo get_lang('preview');?>" onClick="preview('this.form','<?php echo $temp; ?>','<?php echo $Multi; ?>')">
	<input type="submit"  name="next" value="<?php echo get_lang('Next'); ?>">  
</DIV></FORM></DIV>
<DIV id=bottomnav align=center></DIV>
</BODY></HTML>
<SCRIPT LANGUAGE="JavaScript">
function preview(form,temp,qtype)
{
	//var ques = document.frmitemchkboxmulti.enterquestion.value;
	var ques = editor.getHTML();
	window.open(temp+'.php?ques='+ques+'&qtype='+qtype, 'popup', 'width=600,height=600,toolbar = no, status = no');
}
</script>
<?php
Display :: display_footer();
?>