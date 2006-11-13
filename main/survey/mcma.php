<?
$langFile = 'survey';

require_once ('../inc/global.inc.php');
//api_protect_admin_script();
require_once ("select_question.php");
if(isset($_REQUEST['questtype']))
$add_question12=$_REQUEST['questtype'];
else
$add_question12=$_REQUEST['add_question'];
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
$cidReq=$_GET['cidReq'];
$curr_dbname = $_REQUEST['curr_dbname'];
$add_question = $_REQUEST['add_question'];
$groupid = $_REQUEST['groupid'];
$surveyid = $_REQUEST['surveyid'];
$table_survey = Database :: get_course_table('survey');
$table_group =  Database :: get_course_table('survey_group');
$table_question = Database :: get_course_table('questions');
$Add = get_lang("addnewquestiontype");
$Multi = get_lang("MultipleChoiceMulti");
$interbredcrump[] = array ("url" => "survey_list.php?cidReq=$cidReq&n=$n", "name" => get_lang('Survey'));
//$interbredcrump[] = array ("url" => "survey.php?cidReq=$cidReq&n=$n", "name" => get_lang('CreateSurvey'));
/*
if($n=="n")
$interbredcrump[] = array ("url" => "create_new_survey.php?cidReq=$cidReq&n=$n", "name" => get_lang('New_survey'));
else
$interbredcrump[] = array ("url" => "create_from_existing.php?cidReq=$cidReq&n=$n", "name" => get_lang('New_survey'));
*/
//$n=$_REQUEST['n'];
if ($_POST['action'] == 'addquestion')
{
   $groupid = $_REQUEST['groupid'];
   $surveyid = $_REQUEST['surveyid'];
   $questtype = $_REQUEST['questtype'];   
   $enter_question=$_POST['enterquestion'];
    if(isset($_POST['next']))
	{
		$enter_question=$_POST['enterquestion'];
		$answers=$_POST['mutlichkboxtext'];
		$rating=$_POST['chkboxpoint'];	
		$answerT=$_POST['chkboxdefault1'];	
		$answerD=$_POST['chkboxdefault'];
		$alignment=$_POST['alignment'];
		$open_ans="";
		$count=count($_POST['mutlichkboxtext']);

		$default=0;
		$true=0;
		$noans=0;
		$nopoint=0;

		for($i=0;$i<$count;$i++)
		{
			$answers[$i]=trim($answers[$i]);

			if(!empty($answerT[$i]))
				$true++;
			if(!empty($answerD[$i]))
				$default++;
			if(empty($answers[$i]))
				$noans++;
			if(!is_numeric($rating[$i]))
				$number=1;
		}

		$enter_question=trim($enter_question);
		if(empty($enter_question))
		$error_message = get_lang('PleaseEnterAQuestion')."<br>";		
		if ($noans)
		$error_message = $error_message."<br>".get_lang('PleasFillAllAnswer');
		//if($number==1)
		//$error_message = $error_message."<br>".get_lang('PleaseFillNumber');
		//if($nopoint)
		//$error_message = $error_message."<br>".get_lang('PleaseFillAllPoints');		
		//if($true<1)
		//$error_message=$error_message."<br>".get_lang('PleaseSelectOneTrue');
		//if($default<1)
		//$error_message=$error_message."<br>".get_lang('PleaseSelectOneDefault');				
		if(isset($error_message));
		//Display::display_error_message($error_message);
		else
		{
		 $groupid = $_REQUEST['groupid'];
         $questtype = $_REQUEST['questtype'];
		 $curr_dbname = $_REQUEST['curr_dbname'];
		 $surveyid = $_REQUEST['surveyid'];
		 $enter_question = addslashes($enter_question); SurveyManager::create_question($groupid,$surveyid,$questtype,$enter_question,$alignment,$answers,$open_ans,$answerT,$answerD,$rating,$curr_dbname);
		 $cidReq = $_GET['cidReq'];		header("location:select_question_group.php?groupid=$groupid&surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
		 exit;
		}
	}
	elseif(isset($_POST['back']))
	{
	   $groupid = $_REQUEST['groupid'];
	   $surveyid = $_REQUEST['surveyid'];
	   $cidReq = $_GET['cidReq'];
	   $curr_dbname = $_REQUEST['curr_dbname'];
	   header("location:addanother.php?groupid=$groupid&surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
	   exit;
	}
	elseif(isset($_POST['saveandexit']))
	{
		$enter_question=$_POST['enterquestion'];
		$answers=$_POST['mutlichkboxtext'];
		$rating=$_POST['chkboxpoint'];	
		$answerT=$_POST['chkboxdefault1'];	
		$answerD=$_POST['chkboxdefault'];
		$alignment=$_POST['alignment'];
		$open_ans="";
		$count=count($_POST['mutlichkboxtext']);

		$default=0;
		$true=0;
		$noans=0;
		$nopoint=0;
		
		for($i=0;$i<$count;$i++)
		{
			$answers[$i]=trim($answers[$i]);

			if(!empty($answerT[$i]))
				$true++;
			if(!empty($answerD[$i]))
				$default++;
			if(empty($answers[$i]))
				$noans++;
			if(empty($rating[$i])&&($rating[$i]!='0'))
				$nopoint++;
		}

		$enter_question=trim($enter_question);
		if(empty($enter_question))
		$error_message = get_lang('PleaseEnterAQuestion')."<br>";		
		if ($noans)
		$error_message = $error_message."<br>".get_lang('PleasFillAllAnswer');
		//if($nopoint)
		//$error_message = $error_message."<br>".get_lang('PleaseFillAllPoints');		
		//if($true<1)
		//$error_message=$error_message."<br>".get_lang('PleaseSelectOneTrue');
		//if($default<1)
		//$error_message=$error_message."<br>".get_lang('PleaseSelectOneDefault');				
		if(isset($error_message));
		//Display::display_error_message($error_message);
		else
		{
	     $groupid = $_REQUEST['groupid'];
	     $cidReq = $_GET['cidReq'];
		 $curr_dbname = $_REQUEST['curr_dbname']; 
		 $surveyid = $_REQUEST['surveyid'];
		 $enter_question = addslashes($enter_question); SurveyManager::create_question($groupid,$surveyid,$questtype,$enter_question,$alignment,$answers,$open_ans,$answerT,$answerD,$rating,$curr_dbname);	  
	     header("location:survey_list.php?cidReq=$cidReq&n=$n");
	     exit;
		}
	}
}
?>
<?
$tool = get_lang('AddAnotherQuestion');
Display::display_header($tool);
?>
<script type="text/javascript">
function changeAction()
{
	var x=document.getElementById("myForm")
	x.action="preview_mcma.php"
	return true;
}
</script>
<?
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
<?php
if( isset($error_message) )
{
	Display::display_error_message($error_message);	
}
?>
<SCRIPT LANGUAGE="JAVASCRIPT">
function checkLength(form){
    if (form.description.value.length > 250){
        alert("Text too long. Must be 250 characters or less");
        return false;
    }
    return true;
}
</SCRIPT>
<form method="POST" name ="mcma" id="myForm" action="<?php echo $_SERVER['PHP_SELF'];?>?cidReq=<?php echo $cidReq; ?>&add_question=<?php echo $add_question; ?>&groupid=<?php echo $groupid; ?>&surveyid=<?php echo $surveyid; ?>&curr_dbname=<?php echo $curr_dbname; ?>">
<input type="hidden" name="groupid" value="<?php echo $groupid; ?>">
<input type="hidden" name="surveyid" value="<?php echo $surveyid; ?>">
<input type="hidden" name="questtype" value="<?php echo $add_question12; ?>">
<input type="hidden" name="curr_dbname" value="<?php echo $curr_dbname; ?>">
<!--<input type="hidden" name="cidReq" value="<?php echo $cidReq; ?>">-->
<input type="hidden" name="action" value="addquestion" >
	  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="outerBorder_innertable">
	  <tr><td>
	  <tr>
	  <td valign="top"><strong><?php echo get_lang('SelectDisplayType'); ?></strong>&nbsp;</td>
	  </tr>
	  <tr><td>
         <input type="radio" name="alignment" value="horizontal"  checked>Horizontal</td>
      </tr>
	  <tr><td>
		 <input type="radio" name="alignment" value="vertical">Vertical</td>
		 </tr>

</td></tr>
    <tr><td><br></td></tr>
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
				</tr>
				<tr class="form_bg"> 
					<td width="542" height="30" colspan="2" ><?php  api_disp_html_area('enterquestion',stripslashes($enterquestion),'200px');?><!--<textarea name="enterquestion" id="enterquestion" cols="50" rows="6" class="text_field" style="width:100%;" ><?if(isset($_POST['enterquestion']))echo $_POST['enterquestion'];?></textarea>-->
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
	
		
	$start=1;$end=5;$upx=2;$upy=1;$dwnx=0;$dwny=1;$jd=0;$sn=1;
	$id="id";
	$tempmutlichkboxtext="jkjk";
	$tempchkboxdefault="jkjk";
	$tempchkboxpoint="jkjk";
	$tempchkboxdefault1="jkjk";
	$up="up";
	$down="down";
	$flag=1;
	if(isset($_POST['mutlichkboxtext']))
	$end=count($_POST['mutlichkboxtext']);
	//echo ",before 1st loop end=".$end;
	for($i=$start;$i<=$end;$i++)
	{	
		$id="id".$i."_x";
		//echo ",".$id;
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
				//echo ",while checking id,end=".$end;

		}

	}
	
	for($i=$start;$i<=$end;$i++)
	{
		
		$up="up".$i."_x";
		$down="down".$i."_x";
		
		if(isset($_POST[$up])||isset($_POST[$down]))
		{
			//if(isset($_POST['up2_x']))	
			$flag=0;
			if(isset($_POST[$up]))
			{
				$tempmutlichkboxtext=$_POST['mutlichkboxtext'];
				$tempchkboxdefault=$_POST['chkboxdefault'];
				$tempchkboxpoint=$_POST['chkboxpoint'];
				$tempchkboxdefault1=$_POST['chkboxdefault1'];

										
				$tempm=	$tempmutlichkboxtext[$i-2];
				$tempchkboxd=$tempchkboxdefault[$i-2];
				$tempchkboxp=$tempchkboxpoint[$i-2];
				$tempchkboxd1=$tempchkboxdefault1[$i-2];

				$tempmutlichkboxtext[$i-2]=$tempmutlichkboxtext[$i-1];
				$tempchkboxdefault[$i-2]=$tempchkboxdefault[$i-1];
				$tempchkboxpoint[$i-2]=$tempchkboxpoint[$i-1];
				$tempchkboxdefault1[$i-2]=$tempchkboxdefault1[$i-1];

				$tempmutlichkboxtext[$i-1]=$tempm;
				$tempchkboxdefault[$i-1]=$tempchkboxd;
				$tempchkboxpoint[$i-1]=$tempchkboxp;
				$tempchkboxdefault1[$i-1]=$tempchkboxd1;

				$_POST['mutlichkboxtext']=$tempmutlichkboxtext;
				$_POST['chkboxdefault']=$tempchkboxdefault;
				$_POST['chkboxpoint']=$tempchkboxpoint;
				$_POST['chkboxdefault1']=$tempchkboxdefault1;
			}

			if(isset($_POST[$down]))
			{
				$tempmutlichkboxtext=$_POST['mutlichkboxtext'];
				$tempchkboxdefault=$_POST['chkboxdefault'];
				$tempchkboxpoint=$_POST['chkboxpoint'];
				$tempchkboxdefault1=$_POST['chkboxdefault1'];

										
				$tempm=	$tempmutlichkboxtext[$i];
				$tempchkboxd=$tempchkboxdefault[$i];
				$tempchkboxp=$tempchkboxpoint[$i];
				$tempchkboxd1=$tempchkboxdefault1[$i];

				$tempmutlichkboxtext[$i]=$tempmutlichkboxtext[$i-1];
				$tempchkboxdefault[$i]=$tempchkboxdefault[$i-1];
				$tempchkboxpoint[$i]=$tempchkboxpoint[$i-1];
				$tempchkboxdefault1[$i]=$tempchkboxdefault1[$i-1];

				$tempmutlichkboxtext[$i-1]=$tempm;
				$tempchkboxdefault[$i-1]=$tempchkboxd;
				$tempchkboxpoint[$i-1]=$tempchkboxp;
				$tempchkboxdefault1[$i-1]=$tempchkboxd1;

				$_POST['mutlichkboxtext']=$tempmutlichkboxtext;
				$_POST['chkboxdefault']=$tempchkboxdefault;
				$_POST['chkboxpoint']=$tempchkboxpoint;
				$_POST['chkboxdefault1']=$tempchkboxdefault1;
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
				 {
				  $end=10;
				  $error_message = get_lang('YouCanntAddmorethanTen')."<br>";
				if( isset($error_message) )
                  {
	                  Display::display_error_message($error_message);	
                  }
				}
			//echo ",while checking select end=".$end;
			/*else
			$end=$end+$_POST['addnewrows'];*/
		}
	}
	
		
	//echo ",after select end=".$end;
				
	
	for($i=$start;$i<=$end;$i++)
	{
		

		if($i==$jd)
		/*{
			if($end<=3);
			else;
			//continue;
		}*/
		{
			$end++;
		}
		else
		{
			$k=$i-1;
			$post_text = $_POST['mutlichkboxtext'];
			$post_check=$_POST['chkboxdefault'];
			$post_point=$_POST['chkboxpoint'];
			$post_true=$_POST['chkboxdefault1'];	
?>					
			<tr class="form_bg" id="0">					
					<td width="16" height="30" align="left" class="form_text"> 
					  <?php echo $sn;?>
					</td>					
					<td class="form_bg"><textarea name="mutlichkboxtext[]" cols="50" rows="3" class="text_field" style="width:100%;"><?php echo $post_text[$k]; ?></textarea>
					</td>					
					<td width="10" class="form_text"><img src="../img/blank.gif" width="10" height="8">
					</td>
					<td width="10" class="form_text"><img src="../img/blank.gif" width="10" height="8">
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

					
					
					<input type="image" src="../img/delete.gif" width="24" height="24" border="0" style="cursor:hand" name="<?php echo "id".$i;?>" value="<?php echo $end;?>" onclick="this.form.submit();">					
			</tr>
<?		}	
	}
	
?>   
		
			</table>											
			<table width="100%" border="0" cellspacing="0" cellpadding="0">
				<tr class="white_bg"> 
					  <td height="30"><span class="form_text1">Add&nbsp;&nbsp;</span>
							<select name="addnewrows" class="text_field_small" style="width:100px" onChange="this.form.submit();">
								
								<!--<option value="1" <?if(isset($_POST['addnewrows'])){if($_POST['addnewrows']=="1")echo "selected";}?>>1</option>
								<option value="2" <?if(isset($_POST['addnewrows'])){if($_POST['addnewrows']=="2")echo "selected";}?>>2</option>
								<option value="3" <?if(isset($_POST['addnewrows'])){if($_POST['addnewrows']=="3")echo "selected";}?>>3</option>
								<option value="4" <?if(isset($_POST['addnewrows'])){if($_POST['addnewrows']=="4")echo "selected";}?>>4</option>
								<option value="5" <?if(isset($_POST['addnewrows'])){if($_POST['addnewrows']=="5")echo "selected";}?>>5</option>-->
								
								<option value="0" >0</option>
								<option value="1" >1</option>
								<option value="2" >2</option>
								<option value="3" >3</option>
								<option value="4" >4</option>
								<option value="5" >5</option>
								
							</select>
						  <a class="form_text1">New Answer</a>
						  
						<span class="form_text"><span class="form_text1">
						
						
					</td>
				</tr>
			</table>

	        <br>
			<br>
			<div align="center">
						
			<!--
			<input type="hidden" name="type" value="mcma">-->
			<input type="HIDDEN" name="end1" value="<?php echo $end; ?>">


<?			if(isset($_POST['add_question']))
			{
?>				<input type="hidden" name="add_question" value="<?php echo $_POST['add_question'];?>" >
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
						<input type="submit"  name="back" value="<?php echo get_lang('Back'); ?>">
						<input type="submit"  name="saveandexit" value="<?php echo get_lang('SaveAndExit');?>">
						<input type="button" value="<?php echo get_lang('preview');?>" onClick="preview('mcma','<?php echo $temp; ?>','<?php echo $Multi; ?>')">
						<input type="submit"  name="next" value="<?php echo get_lang("Next"); ?>"> 
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
	//alert(ques);
	var id_str = "";
	for(i=0;i<eval("document."+form+"['mutlichkboxtext[]'].length");i++)
	{
		var box = (eval("document."+form+"['mutlichkboxtext[]']["+i+"]"));
			id_str += box.value+"|";
	}
	window.open(temp+'.php?ques='+ques+'&ans='+id_str+'&qtype='+qtype, 'popup', 'width=800,height=600,scrollbars=yes,toolbar = no, status = no');
}
</script>
<?php
Display :: display_footer();
?>