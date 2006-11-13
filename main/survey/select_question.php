<?php
if(isset($_POST['add_question']))
{
	$groupid=$_REQUEST['groupid'];
	$surveyid=$_REQUEST['surveyid'];
	$cidReq=$_REQUEST['cidReq'];
	$curr_dbname = $_REQUEST['curr_dbname'];
	$langFile = 'survey';
	require_once ('../inc/global.inc.php');
    $add_question=$_REQUEST['add_question'];
	switch ($_POST['add_question'])
	{
		case get_lang('yesno'):
		header("location:yesno.php?add_question=$add_question&groupid=$groupid&surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
		break;
		case get_lang('MultipleChoiceSingle'):
		header("location:mcsa.php?add_question=$add_question&groupid=$groupid&surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
		break;
		case get_lang('MultipleChoiceMulti'):
		header("location:mcma.php?add_question=$add_question&groupid=$groupid&surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
		break;
		case get_lang('Open'):
		header("location:open.php?add_question=$add_question&groupid=$groupid&surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
		break;
		case get_lang('numbered'):
		header("location:numbered.php?add_question=$add_question&groupid=$groupid&surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
		break;
		default :
		header("location:select_question_type.php?cidReq=$cidReq");
		break;
	}	
	exit;
}
function select_question_type($add_question12,$groupid,$surveyid,$cidReq,$curr_dbname)
{	
		//$group_table = Database :: get_course_table('survey_group');
		$sql = "SELECT groupname FROM $curr_dbname.survey_group WHERE group_id='$groupid'";
		$sql_result = api_sql_query($sql,__FILE__,__LINE__);
		$group_name = @mysql_result($sql_result,0,'groupname');
?>

<table>
<tr>
<td><?php api_display_tool_title('Group Name :'); ?></td>
<td><?php api_display_tool_title($group_name); ?></td>
</tr>
</table>
<?
if( isset($error_message) )
{
	Display::display_error_message($error_message);	
}
?>
<form name="question" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>?cidReq=<?=$cidReq?>">
<input type="hidden" name="groupid" value="<?php echo $groupid?>">
<input type="hidden" name="surveyid" value="<?php echo $surveyid?>">
<input type="hidden" name="curr_dbname" value="<?php echo $curr_dbname?>">
<!--<input type="hidden" name="cidReq" value="<?php echo $cidReq?>">-->
<table>
<tr>
<td>
<?php echo get_lang('Selectype');?>
</td>
<td>
<select name="add_question" onChange="javascript:this.form.submit();">
	<option value="0"><?php echo get_lang('Select');?></option>
	<option value="<?=get_lang('yesno')?>" <?php if($add_question12==get_lang('yesno'))echo "selected";?>><?php echo get_lang('yesno');?></option>
	<option value="<?=get_lang('MultipleChoiceSingle')?>" <?php if($add_question12==get_lang('MultipleChoiceSingle')) { echo " selected ";}?>><?php echo get_lang('MultipleChoiceSingle');?></option>
	<option value="<?=get_lang('MultipleChoiceMulti')?>" <?php if($add_question12==get_lang('MultipleChoiceMulti')) { echo " selected ";}?>><?php echo get_lang('MultipleChoiceMulti');?></option>
	<option value="<?=get_lang('Open')?>" <?php if($add_question12==get_lang('Open')) { echo "selected";}?>><?php echo get_lang('Open');?></option>
	<option value="<?=get_lang('numbered')?>" <?php if($add_question12==get_lang('numbered')) { echo "selected";}?>><?php echo get_lang('numbered');?></option>
</select>
</td>
</tr>
</table>
</form>
<?
}
?>