<?php 
$langFile = 'survey';

require_once ('../inc/global.inc.php');
//api_protect_admin_script();
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
$n=$_REQUEST['n'];
$interbredcrump[] = array ("url" => "survey_list.php?cidReq=$cidReq&n=$n", "name" => get_lang('Survey'));
$cidReq = $_REQUEST['cidReq'];
$curr_dbname = $_REQUEST['curr_dbname'];
$groupid=$_REQUEST['groupid'];
$surveyid=$_REQUEST['surveyid'];
$qid=$_REQUEST['qid'];
$qtype=$_REQUEST['qtype'];
$table_question = Database :: get_course_table('questions');
$Add = get_lang("updatequestiontype");
$Multi = get_lang("yesno");
$tool_name = $Add.$Multi;
$rs=SurveyManager::get_question_data($qid,$curr_dbname);
if(isset($_REQUEST['questtype']))
$add_question12=$_REQUEST['questtype'];
else
$add_question12=$rs->qtype;



   if(isset($_POST['update']))
	{
        $qid=$_POST['qid'];
		$alignment='';
		if(isset($_POST['enterquestion']))
		$enter_question=$_POST['enterquestion'];
		else
		$enter_question=$rs->caption;

		if(isset($_POST['mutlichkboxtext']))
		$answers=$_POST['mutlichkboxtext'];
		else
		{
			$answers=array();
			$i=1;
			while($rs)
			{
				$ans=a.$i;
				$answers[]=$rs->$ans;
				$i++;
			}
		}
		$open_ans="";
		$count=count($_POST['mutlichkboxtext']);		
		$noans=0;
		$nopoint=0;
		for($i=0;$i<$count;$i++)
		{		
			$answers[$i]=trim($answers[$i]);
			if(empty($answers[$i]))
				$noans++;
		}
		$enter_question=trim($enter_question);
		if(empty($enter_question)) 
		$error_message = get_lang('PleaseEnterAQuestion')."<br>";			
		if ($noans)
		$error_message = $error_message."<br>".get_lang('PleasFillAllAnswer');
		if(isset($error_message))
        {
			//Display::display_error_message($error_message);	
		}
		else
		{
		  //$groupid = $_POST['groupid'];	
		  //if(isset($_REQUEST['questtype']))		
		  //$questtype = $_REQUEST['questtype'];		
		  //else
		 $questtype=$rs->qtype; 
		 $curr_dbname = $_REQUEST['curr_dbname'];
		 $enter_question = addslashes($enter_question);
		 SurveyManager::update_question($qid,$questtype,$enter_question,$alignment,$answers,$open_ans,$curr_dbname);
		 $cidReq = $_GET['cidReq'];		 		 header("location:select_question_group.php?groupid=$groupid&surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
		 exit;
		}
	}
	elseif(isset($_POST['back']))
	{
	   $groupid = $_REQUEST['groupid'];
	   $surveyid = $_REQUEST['surveyid'];
	   $cidReq = $_GET['cidReq'];
	   $curr_dbname = $_REQUEST['curr_dbname'];		 	header("location:select_question_group.php?groupid=$groupid&surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
	   exit;
	}
	elseif(isset($_POST['saveandexit']))
	{
	  $groupid = $_REQUEST['groupid'];
	  $surveyid = $_REQUEST['surveyid'];
	  $questtype = $_REQUEST['questtype'];
	  $curr_dbname = $_REQUEST['curr_dbname'];
	  $enter_question = addslashes($enter_question); SurveyManager::create_question($groupid,$surveyid,$questtype,$enter_question,$alignment,$answers,$open_ans,$answerT,$answerD,$rating,$curr_dbname);
      $cidReq = $_GET['cidReq'];
	  header("location:survey_list.php?cidReq=$cidReq");
	  exit;
	}

?>
<?
Display::display_header($tool_name);
api_display_tool_title($tool_name);
if( isset($error_message) )
{
	Display::display_error_message($error_message);	
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<div id=content>
<form method="POST" name="yesno" id="yesno"  action="<?php echo $_SERVER['PHP_SELF'];?>?qid=<?=$qid?>&cidReq=<?=$cidReq?>&groupid=<?=$groupid?>&surveyid=<?=$surveyid?>&curr_dbname=<?=$curr_dbname?>">
<input type="hidden" name="qid" value="<?=$qid?>">
<input type="hidden" name="groupid" value="<?=$groupid?>">
<input type="hidden" name="surveyid" value="<?=$surveyid?>">
<input type="hidden" name="qid" value="<?=$qid?>">
<input type="hidden" name="questtype" value="<?=$add_question12?>">
<input type="hidden" name="cidReq" value="<?=$cidReq?>">
<input type="hidden" name="curr_dbname" value="<?=$curr_dbname?>">
<input type="hidden" name="action" value="addquestion" >
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="outerBorder_innertable">
<tr> 
<td class="pagedetails_heading"><a class="form_text_bold"><strong>Question</strong></a></td>
</tr>
</table>
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" class="outerBorder_innertable">
				<tr class="white_bg"> 
					<td height="30" class="form_text1"> 
						Enter the question.        
					</td>
					<td class="form_text1" align="right">&nbsp;
					</td>
				<tr class="form_bg"> 
					<td width="542" height="30" colspan="2" ><?php  api_disp_html_area('enterquestion',$rs->caption,'200px');?>
					</td>
					<!--<textarea name="enterquestion" id="enterquestion" cols="50" rows="6" class="text_field" style="width:75%;"><?
					if(isset($_POST['enterquestion']))
						echo $_POST['enterquestion'];
						else
						echo $rs->caption;
					?></textarea>-->
					</td>
				</tr>
			</table>
			<br>
			
			<table width="100%" border="0" cellspacing="0" cellpadding="0" class="outerBorder_innertable">
			<tr> 
				<td class="pagedetails_heading"><a class="form_text_bold"><strong>Answer</strong></a></td>
			</tr>
			</table>
			<table width="100%" border="0" cellspacing="0" cellpadding="0" class="outerBorder_innertable">
				<tr class="white_bg"> 
					<td height="30"><span class="form_text1">Enter the answers</span>.
					</td>
					<td>&nbsp;</td>
					<td width="192" align="right">&nbsp; </td>
				</tr>
			</table>										
			<table ID="tblFields" width="70%" border="0" cellpadding="0" cellspacing="0" class="outerBorder_innertable">
<?php	
	$start=1;$end=2;$upx=2;$upy=1;$dwnx=0;$dwny=1;$jd=0;$sn=1;
	$id="id";
	
	$tempmutlichkboxtext="jkjk";	
	$tempchkboxpoint="jkjk";	
	$up="up";
	$down="down";
	$flag=1;
	if(isset($_POST['mutlichkboxtext']))
	$end=count($_POST['mutlichkboxtext']);
	for($i=$start;$i<=$end;$i++)
	{	
		$id="id".$i."_x";
		
		if(isset($_POST[$id]))
		{
				$jd=$i;
				$flag=0;
				$end=count($_POST['mutlichkboxtext']);
				if($end<=3)
				{
					$end=3;
				}
				else
				$end-=1;
				break;
				
		}
	}
	
	

	for($i=$start;$i<=$end;$i++)
	{
		
		$up="up".$i."_x";
		$down="down".$i."_x";	
		$ans="a".$i;
		$score="r".$i;
		if(isset($_POST[$up])||isset($_POST[$down]))
		{			
			$flag=0;
			if(isset($_POST[$up]))
			{
				if(isset($_POST['mutlichkboxtext']))
				$tempmutlichkboxtext=$_POST['mutlichkboxtext'];
				else
				$tempmutlichkboxtext=$rs->$ans['mutlichkboxtext'];
				if(isset($_POST['chkboxpoint']))
				$tempchkboxpoint=$_POST['chkboxpoint'];
				else
				$tempchkboxpoint=$rs->$score;
				$tempm=	$tempmutlichkboxtext[$i-2];
				$tempchkboxp=$tempchkboxpoint[$i-2];
				$tempmutlichkboxtext[$i-2]=$tempmutlichkboxtext[$i-1];
				$tempchkboxpoint[$i-2]=$tempchkboxpoint[$i-1];
				$tempmutlichkboxtext[$i-1]=$tempm;
				$tempchkboxpoint[$i-1]=$tempchkboxp;

				if(isset($_POST['mutlichkboxtext']))
				$_POST['mutlichkboxtext']=$tempmutlichkboxtext;
				else
				$rs->$ans=$tempmutlichkboxtext;

				if(isset($_POST['chkboxpoint']))
				$_POST['chkboxpoint']=$tempchkboxpoint;
				else
					$rs->$score=$tempchkboxpoint;
			}

			if(isset($_POST[$down]))
			{
				if(isset($_POST['mutlichkboxtext']))
				$tempmutlichkboxtext=$_POST['mutlichkboxtext'];
				else
				$tempmutlichkboxtext=$rs->$ans['mutlichkboxtext'];
				if(isset($_POST['chkboxpoint']))
				$tempchkboxpoint=$_POST['chkboxpoint'];
				else
				$tempchkboxpoint=$rs->$score;
				$tempm=	$tempmutlichkboxtext[$i];
				$tempchkboxp=$tempchkboxpoint[$i];
				$tempmutlichkboxtext[$i]=$tempmutlichkboxtext[$i-1];
				$tempchkboxpoint[$i]=$tempchkboxpoint[$i-1];
				$tempmutlichkboxtext[$i-1]=$tempm;
				$tempchkboxpoint[$i-1]=$tempchkboxp;

				if(isset($_POST['mutlichkboxtext']))
				$_POST['mutlichkboxtext']=$tempmutlichkboxtext;
				else
				$rs->$ans=$tempmutlichkboxtext;

				if(isset($_POST['chkboxpoint']))
				$_POST['chkboxpoint']=$tempchkboxpoint;
				else
					$rs->$score=$tempchkboxpoint;
			}
			//echo ",while checking up/down end=".$end;
			$jd=0;
			break;		
		}
	}
	
	if($flag==1)
	{
		if(isset($_POST['addnewrows']))
		{								
				$end=count($_POST['mutlichkboxtext']);							
				if($end<10)
				{
					$end=$end+$_POST['addnewrows'];
					if($end>10)
						$end=10;
				}
				else
				$end=10;
			
		}
	}		
	
	for($i=$start;$i<=$end;$i++)
	{
		if($i==$jd)
		{
			$end++;
		}
		else
		{
			$k=$i-1;
			
			$val="a".$i;
			$sco="r".$i;

			if(isset($_POST['mutlichkboxtext']))
			{
				$post_text1=$_POST['mutlichkboxtext'];
				$post_text = $post_text1[$i-1];
			}
			else 
			$post_text=$rs->$val;
			
			if(isset($_POST['chkboxpoint']))
			{
				$post_point1=$_POST['chkboxpoint'];
				$post_point=$post_point1[$i-1];
			}
			else
			$post_point=$rs->$sco;
			
?>					
			<tr class="form_bg" id="0"> 					
					<td width="16" height="30" align="left" class="form_text"> 
					  <? echo $sn;?>
					</td>					
					<td class="form_bg"><textarea name="mutlichkboxtext[]" cols="50" rows="3" class="text_field" style="width:100%;"><?=$post_text?></textarea>
					</td>					
					<td width="10" class="form_text"><img src="../img/blank.gif" width="10" height="8">
					</td>
					<td width="10" class="form_text"><img src="../img/blank.gif" width="10" height="8">
					</td>					
					<td width="30" align="center" class="form_text">&nbsp; 
             		</td>
<?					if($i>$start)
					{
?>
					<td width="30" align="center" class="form_text1"> 
						<input type="image" src="../img/up.gif" width="24" height="24" border="0" onclick="this.form.submit();" name="<?echo "up".$i;?>" style="cursor:hand"> 
					</td>
<?					}
					else
					{
?>						<td width="30" align="center" class="form_text1"> 
						</td>
<?					}
					$sn++;
?>

<?					if($i<$end)
					{
?>
					<td width="30" align="center" class="form_text"> 
						<input type="image" src="../img/down.gif" width="24" height="24" border="0" onclick="this.form.submit();" name="<?echo "down".$i;?>" style="cursor:hand"> 
					</td>
<?					}
					else
					{
?>						<td width="30" align="center" class="form_text1"> 
						</td>
<?					}
?>
					<td width="30" align="center" class="form_text">					
			</tr>
<?		}	
	} 	
?>		
            </table>
            <br>
			<br>
			<div align="center">
			<input type="HIDDEN" name="end1" value="<?=$end?>">
<?			
            if(isset($_POST['add_question']))
			{
?>				<input type="hidden" name="add_question" value="<? echo $_POST['add_question'];?>" >
<?			}
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
						<input type="submit"  name="back" value="<?=get_lang("back");?>">
						<input type="button" value="<?php echo get_lang('preview');?>" onClick="preview('yesno','<?=$temp?>','<?=$Multi?>')">
						<input type="submit"  name="update" value="<?=get_lang("update");?>">
						<!--<input name="preview" value="<?=get_lang("preview");?>" type="submit" onClick="return changeAction()" > -->

						<!--<input type="submit"  name="next" value="<?=get_lang("next");?>"> -->
			</div>
<!--this partcular field helps in identify the item to be add at the itemadd.php-->			
</form>
</div>  
<div id=bottomnav align="center"></DIV>
</body>
</html>
<SCRIPT LANGUAGE="JavaScript">
function preview(form,temp,qtype)
{
	var ques = editor.getHTML();
	var id_str = "";
	
	for(i=0;i<eval("document."+form+"['mutlichkboxtext[]'].length");i++)
	{
		var box = (eval("document."+form+"['mutlichkboxtext[]']["+i+"]"));
			id_str += box.value+"|";
	}
	window.open(temp+'.php?temp=<?=$temp;?>&ques='+ques+'&ans='+id_str+'&qtype='+qtype, 'popup', 'width=800,height=600,scrollbars=yes,toolbar = no, status = no');
}
</script>
<?php
Display :: display_footer();
?>