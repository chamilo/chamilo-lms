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
* 	@version $Id: mcsa_edit.php 10605 2007-01-06 17:55:20Z pcool $
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

$n=$_REQUEST['n'];
$interbreadcrumb[] = array ("url" => "survey_list.php?cidReq=$cidReq&n=$n", "name" => get_lang('Survey'));
$cidReq = $_REQUEST['cidReq'];
$curr_dbname = $_REQUEST['curr_dbname'];
$groupid=$_REQUEST['groupid'];
$surveyid=$_REQUEST['surveyid'];
$qid=$_REQUEST['qid'];
$qtype=$_REQUEST['qtype'];
$table_question = Database :: get_course_table('questions');
$Add = get_lang('UpdateQuestionType');
$Multi = get_lang('MultipleChoiceSingle');
$tool_name = $Add.$Multi;
$rs=SurveyManager::get_question_data($qid,$curr_dbname);
$sql = "SELECT * FROM $curr_dbname.questions WHERE qid = '$qid'";
$res = api_sql_query($sql);
$obj = mysql_fetch_object($res);
for($i=0,$check=0;$i<10;$i++)
{
$temp = a.$i;
if($obj->$temp)
  $check++;
}
if(isset($_REQUEST['questtype']))
$add_question12=$_REQUEST['questtype'];
else
$add_question12=$rs->qtype;
if (isset($_POST['update']))
{
	$qid=$_POST['qid'];
	$alignment=$_POST['alignment'];
	
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
		if(isset($error_message));
		//Display::display_error_message($error_message);	
		else
		{
			
		 $questtype=$rs->qtype; 
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
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<div id=content>
<form method="POST" name="mcsa" action="<?php echo $_SERVER['PHP_SELF'];?>?qid=<?php echo $qid; ?>&cidReq=<?php echo $cidReq; ?>&groupid=<?php echo $groupid; ?>&surveyid=<?php echo $surveyid; ?>&curr_dbname=<?php echo $curr_dbname; ?>" name="frmitemchkboxmulti">
<input type="hidden" name="action" value="addquestion">
<input type="hidden" name="qid" value="<?php echo $qid; ?>">
<input type="hidden" name="groupid" value="<?php echo $groupid; ?>">
<input type="hidden" name="surveyid" value="<?php echo $surveyid; ?>">
<input type="hidden" name="curr_dbname" value="<?php echo $curr_dbname; ?>">
<!--<input type="hidden" name="cidReq" value="<?php echo $cidReq; ?>">-->
<input type="hidden" name="questtype" value="<?php echo $add_question12; ?>">

	  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="outerBorder_innertable">
	 <tr><td>
	  <tr>
	  <td valign="top"><strong><?php echo get_lang('SelectDisplayType'); ?></strong>&nbsp;</td>
	  </tr>
	  <tr><td>
         <input type="radio" name="alignment" value="horizontal" <?php if($rs->alignment=='horizontal' || $_POST['alignment']=='horizontal'){?>checked<?php }?>>Horizontal</td>
      </tr>
	  <tr><td>
		 <input type="radio" name="alignment" value="vertical" <?php if($rs->alignment=='vertical' || $_POST['alignment']=='vertical'){?>checked<?php }?>>Vertical</td>
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
					<td width="542" height="30" colspan="2" >
					<?php
					 require_once(api_get_path(LIBRARY_PATH) . "/fckeditor/fckeditor.php");
					$oFCKeditor = new FCKeditor('enterquestion') ;
					$oFCKeditor->BasePath	= api_get_path(WEB_PATH) . 'main/inc/lib/fckeditor/' ;
					$oFCKeditor->Height		= '300';
					$oFCKeditor->Width		= '600';
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
				  <!--table for adding the multiple answers-->						  
						<!--<a name="tbl">-->										
			<table ID="tblFields" width="70%" border="0" cellpadding="0" cellspacing="0" class="outerBorder_innertable">
<?php	
$start=1;$end=$check;$upx=2;$upy=1;$dwnx=0;$dwny=1;$jd=0;$sn=1;
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
		if(isset($_POST[$up])||isset($_POST[$down]))
		{			
			$flag=0;
			if(isset($_POST[$up]))
			{
				$tempmutlichkboxtext=$_POST['mutlichkboxtext'];
				$tempm=	$tempmutlichkboxtext[$i-2];
				$tempmutlichkboxtext[$i-2]=$tempmutlichkboxtext[$i-1];
				$tempmutlichkboxtext[$i-1]=$tempm;
				$_POST['mutlichkboxtext']=$tempmutlichkboxtext;
			}
			if(isset($_POST[$down]))
			{
				$tempmutlichkboxtext=$_POST['mutlichkboxtext'];
				$tempm=	$tempmutlichkboxtext[$i];
				$tempmutlichkboxtext[$i]=$tempmutlichkboxtext[$i-1];
				$tempmutlichkboxtext[$i-1]=$tempm;
				$_POST['mutlichkboxtext']=$tempmutlichkboxtext;
			}
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
				  $error_message = get_lang('YouCantAddMoreThanTen')."<br>";
				if( isset($error_message) )
                  {
	                  Display::display_error_message($error_message);	
                  }
				}
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
				$post_text = stripslashes($post_text1[$i-1]);
			}
			else 
			$post_text=stripslashes($rs->$val);
?>				
			<tr class="form_bg" id="0"> 					
					<td width="16" height="30" align="left" class="form_text"> 
					  <?php echo $sn;?>
					</td>					
					<td class="form_bg"><textarea name="mutlichkboxtext[]" cols="50" rows="3" class="text_field" style="width:100%;"><?php echo $post_text; ?></textarea>
					</td>
					
					<td width="10" class="form_text"><img src="../img/blank.gif" width="10" height="8">
					</td>
					<td width="10" class="form_text"><img src="../img/blank.gif" width="10" height="8">
					</td>					
<?php				if($i>$start)
					{
?>
					<td width="30" align="center" class="form_text1"> 
						<input type="image" src="../img/up.gif" width="24" height="24" border="0" onclick="this.form.submit();" name="<?echo "up".$i;?>" style="cursor:hand"> 
					</td>
<?php				}
					else
					{
?>						<td width="30" align="center" class="form_text1"> 
						</td>
<?php				}
					$sn++;
?>

<?php				if($i<$end)
					{
?>
					<td width="30" align="center" class="form_text"> 
						<input type="image" src="../img/down.gif" width="24" height="24" border="0" onclick="this.form.submit();" name="<?php echo "down".$i;?>" style="cursor:hand"> 
					</td>
<?php				}
					else
					{
?>						<td width="30" align="center" class="form_text1"> 
						</td>
<?php				}
?>
					<td width="30" align="center" class="form_text">					
					<input type="image" src="../img/delete.gif" width="24" height="24" border="0" style="cursor:hand" name="<?php echo "id".$i;?>" value="<?php echo $end; ?>" onclick="this.form.submit();">	
			</tr>
<?php	}	
	}
	
?>		
			</table>											
			<table width="100%" border="0" cellspacing="0" cellpadding="0">
				<tr class="white_bg"> 
					  <td height="30"><span class="form_text1">Add&nbsp;&nbsp;</span>
							<select name="addnewrows" class="text_field_small" style="width:100px" onChange="this.form.submit();">							
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

			<input type="HIDDEN" name="end1" value="<?php echo $end; ?>">

<?php		if(isset($_POST['add_question']))
			{
?>				<input type="hidden" name="add_question" value="<?php echo $_POST['add_question'];?>" >
<?php		}
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
						<input type="button" value="<?php echo get_lang('Preview');?>" onClick="preview('mcsa','<?php echo $temp; ?>','<?php echo $Multi; ?>')">
						<input type="submit"  name="update" value="<?php echo get_lang('Update'); ?>"> 
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