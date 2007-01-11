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
* 	@version $Id: open_edit.php 10680 2007-01-11 21:26:23Z pcool $
*/

// name of the language file that needs to be included 
$language_file = 'survey';

// including the global dokeos file
require_once ('../inc/global.inc.php');

// including additional libraries
/** @todo check if these are all needed */
/** @todo check if the starting / is needed. api_get_path probably ends with an / */

require_once ("select_question.php");
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/usermanager.lib.php");

/** @todo replace this with the correct code */
/*
$status = surveymanager::get_status();
api_protect_course_script();
if($status==5)
{
	api_protect_admin_script();
}
*/
/** @todo this has to be moved to a more appropriate place (after the display_header of the code)*/
if (!api_is_allowed_to_edit())
{
	Display :: display_header();
	Display :: display_error_message(get_lang('NotAllowedHere'));
	Display :: display_footer();
	exit;
}

if(isset($_REQUEST['questtype']))
{
	$add_question12=$_REQUEST['questtype'];
}
else
{
	$add_question12=$_REQUEST['add_question'];
}
//if(!$add_question12)
//$add_question12=$_REQUEST['questtype'];
$interbreadcrumb[] = array ("url" => "survey_list.php?n=$n", "name" => get_lang('Survey'));
$table_survey 			= Database :: get_course_table(TABLE_SURVEY);
$table_group 			= Database :: get_course_table(TABLE_SURVEY_GROUP);
$table_survey_question 	= Database :: get_course_table(TABLE_SURVEY_QUESTION);
$Add = get_lang('UpdateQuestionType');
$Multi = get_lang('Open');
$tool_name = $Add.$Multi;
$groupid = $_REQUEST['groupid'];
$surveyid = $_REQUEST['surveyid'];
$rs=SurveyManager::get_question_data($qid,$curr_dbname);
$qid=$_REQUEST['qid'];
if(isset($_REQUEST['questtype']))
$add_question12=$_REQUEST['questtype'];
else
$add_question12=$rs->qtype;

if (isset($_POST['update']))
{
	  $enter_question=$_POST['enterquestion'];
	  
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
		 $qid = $_POST['qid'];		 
		 $questtype=$_POST['questtype'];
		 $answerD=$defaultext;
		 $enter_question = addslashes($enter_question);
		 SurveyManager::update_question($qid,$questtype,$enter_question,$alignment,$answers,$open_ans,$curr_dbname);
		 header("location:select_question_group.php?groupid=$groupid&surveyid=$surveyid");
		 exit;
		}
}

if(isset($_POST['back']))
{
   $groupid = $_REQUEST['groupid'];
   $surveyid = $_REQUEST['surveyid'];
	header("location:select_question_group.php?groupid=$groupid&surveyid=$surveyid");
   exit;
}
Display::display_header($tool_name);
api_display_tool_title($tool_name);

if( isset($error_message) )
{
	Display::display_error_message($error_message);	
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<BODY id=surveys>
<DIV id=content>
<FORM name="frmitemchkboxmulti" action="<?php echo $_SERVER['PHP_SELF'];?>" method=post>
<input type="hidden" name="groupid" value="<?php echo $groupid; ?>">
<input type="hidden" name="surveyid" value="<?php echo $surveyid; ?>">
<input type="hidden" name="questtype" value="<?php echo $add_question12; ?>">
<input type="hidden" name="action" value="addquestion" >
<input type="hidden" name="qid" value="<?php echo $qid; ?>">
<input type="hidden" name="curr_dbname" value="<?php echo $curr_dbname; ?>">
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" class="outerBorder_innertable">
				<tr class="white_bg"> 
					<td height="30" class="form_text1"> 
						Enter the question.        
					</td>
					<td class="form_text1" align="right">&nbsp;
					</td>
				</tr>
				<tr class="form_bg"> 
					<td width="542" height="30" colspan="2" >
					<?php

						require_once(api_get_path(LIBRARY_PATH) . "/fckeditor/fckeditor.php");
						$oFCKeditor = new FCKeditor('enterquestion') ;
						$oFCKeditor->BasePath	= api_get_path(WEB_PATH) . 'main/inc/lib/fckeditor/' ;
						$oFCKeditor->Height		= '300';
						$oFCKeditor->Width		= '400';
						$oFCKeditor->Value		= $rs->caption;
						$oFCKeditor->Config['CustomConfigurationsPath'] = api_get_path(REL_PATH)."main/inc/lib/fckeditor/myconfig.js";
						$oFCKeditor->ToolbarSet = "Survey";
						
						$TBL_LANGUAGES = Database::get_main_table(TABLE_MAIN_LANGUAGE);
						$sql="SELECT isocode FROM ".$TBL_LANGUAGES." WHERE english_name='".$_SESSION["_course"]["language"]."'";
						$result_sql=api_sql_query($sql);
						$isocode_language=mysql_result($result_sql,0,0);
						$oFCKeditor->Config['DefaultLanguage'] = $isocode_language;
						
						$return =	$oFCKeditor->CreateHtml();
		
						echo $return;
					?>
					</td>
				</tr>
</TABLE><BR>
<!-- <TABLE class=outerBorder_innertable cellSpacing=0 cellPadding=0 width="100%" 
border=0>
  <TBODY>
 <TR>
    <TD height=30>Default Text </TD></TR>
  <TR>
    <TD width=192 height=30><TEXTAREA style="WIDTH: 100%" name="defaultext" rows=3 cols=60>
	<?php
	if(isset($_POST['defaultext']))
	echo $_POST['defaultext'];
	else
	echo $rs->ad;
	?></TEXTAREA> 
    </TD></TR></TBODY></TABLE>--><BR>
		<?php
			$sql = "SELECT * FROM survey WHERE survey_id='$surveyid'";
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
	<input type="button" value="<?php echo get_lang('Preview');?>" onClick="preview('this.form','<?php echo $temp; ?>','<?php echo $Multi; ?>')">
	<input type="submit"  name="update" value="<?php echo get_lang('Update'); ?>">  
	
</DIV></FORM></DIV>
<DIV id=bottomnav align=center></DIV>
</BODY></HTML>
<SCRIPT LANGUAGE="JavaScript">
function preview(form,temp,qtype)
{
		var ques = editor.getHTML();

	//var ques = document.frmitemchkboxmulti.enterquestion.value;
	window.open(temp+'.php?ques='+ques+'&qtype='+qtype, 'popup', 'width=800,height=600,scrollbars=yes,toolbar = no, status = no');
}
</script>
<?php
Display :: display_footer();
?>