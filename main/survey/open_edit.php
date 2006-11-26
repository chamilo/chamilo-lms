<?php
// name of the language file that needs to be included 
$language_file = 'survey';

require_once ('../inc/global.inc.php');
//api_protect_admin_script();
if(isset($_REQUEST['questtype']))
$add_question12=$_REQUEST['questtype'];
else
$add_question12=$_REQUEST['add_question'];
//if(!$add_question12)
//$add_question12=$_REQUEST['questtype'];
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
$Add = get_lang("updatequestiontype");
$Multi = get_lang("open");
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
		 $cidReq = $_GET['cidReq'];
		 $questtype=$_POST['questtype'];
		 $answerD=$defaultext;
		 $curr_dbname = $_REQUEST['curr_dbname'];
		 $enter_question = addslashes($enter_question);
		 SurveyManager::update_question($qid,$questtype,$enter_question,$alignment,$answers,$open_ans,$curr_dbname);
		 $cidReq = $_GET['cidReq'];		 		 header("location:select_question_group.php?groupid=$groupid&surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
		 exit;
		}
}

if(isset($_POST['back']))
{
   $groupid = $_REQUEST['groupid'];
   $surveyid = $_REQUEST['surveyid'];
   $cidReq = $_GET['cidReq'];
   $curr_dbname = $_REQUEST['curr_dbname'];		 	header("location:select_question_group.php?groupid=$groupid&surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
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
<FORM name="frmitemchkboxmulti" action="<?php echo $_SERVER['PHP_SELF'];?>?cidReq=<?php echo $cidReq; ?>" method=post>
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
					<td width="542" height="30" colspan="2" ><?php  api_disp_html_area('enterquestion',$rs->caption,'200px');?><!-- <textarea name="enterquestion" id="enterquestion" cols="50" rows="6" class="text_field" style="width:100%;" ><?
					if(isset($_POST['enterquestion']))
						echo $_POST['enterquestion'];
						else
						echo $rs->caption;
					?></textarea>-->
					</TD></TR>
</TABLE><BR>
<!-- <TABLE class=outerBorder_innertable cellSpacing=0 cellPadding=0 width="100%" 
border=0>
  <TBODY>
 <TR>
    <TD height=30>Default Text </TD></TR>
  <TR>
    <TD width=192 height=30><TEXTAREA style="WIDTH: 100%" name="defaultext" rows=3 cols=60>
	<?
	if(isset($_POST['defaultext']))
	echo $_POST['defaultext'];
	else
	echo $rs->ad;
	?></TEXTAREA> 
    </TD></TR></TBODY></TABLE>--><BR>
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
	<input type="button" value="<?php echo get_lang('preview');?>" onClick="preview('this.form','<?php echo $temp; ?>','<?php echo $Multi; ?>')">
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