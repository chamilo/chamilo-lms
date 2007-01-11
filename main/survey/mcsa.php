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
* 	@version $Id: mcsa.php 10680 2007-01-11 21:26:23Z pcool $
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
$add_question = $_REQUEST['add_question'];
$groupid = $_REQUEST['groupid'];
$surveyid = $_REQUEST['surveyid'];
$table_survey_question = Database :: get_course_table(TABLE_SURVEY_QUESTION);
$Add = get_lang('AddNewQuestionType');
$Multi = get_lang('MultipleChoiceSingle');
$groupid = $_REQUEST['groupid'];
$surveyid = $_REQUEST['surveyid'];
$interbreadcrumb[] = array ("url" => "survey_list.php?n=$n", "name" => get_lang('Survey'));

//$n=$_REQUEST['n'];
if ($_POST['action'] == 'addquestion')
{
    $enter_question=$_POST['enterquestion'];    
    if(isset($_POST['next']))
	{
		$enter_question=$_POST['enterquestion'];
		$answers=$_POST['mutlichkboxtext'];
		$rating=$_POST['chkboxpoint'];	
		$answerT=$_POST['radiotrue'];
		$answerD=$_POST['radiodefault'];
		$alignment=$_POST['alignment'];
		$open_ans="";
		$count=count($_POST['mutlichkboxtext']);		
		$noans=0;
		$nopoint=0;
		for($i=0;$i<$count;$i++)
		{	
			$answers[$i]=trim($answers[$i]);
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
		//if(empty($_POST['radiotrue']))
		//	$error_message=$error_message."<br>".get_lang('PleaseSelectOneTrue');
		//if(empty($_POST['radiodefault']))
			//$error_message=$error_message."<br>".get_lang('PleaseSelectOneDefault');
		if(isset($error_message));
		//Display::display_error_message($error_message);	
		else
		{
			 $groupid = $_POST['groupid'];
			 $questtype = $_REQUEST['questtype'];	 
			 $surveyid = $_REQUEST['surveyid'];	
			 $enter_question = addslashes($enter_question); SurveyManager::create_question($groupid,$surveyid,$questtype,$enter_question,$alignment,$answers,$open_ans,$answerT,$answerD,$rating,$curr_dbname);
			 header("location:select_question_group.php?groupid=$groupid&surveyid=$surveyid");
			 exit;
		}
	}
	elseif(isset($_POST['back']))
	{
	   $groupid = $_REQUEST['groupid'];
	   $surveyid = $_REQUEST['surveyid'];
	   header("location:addanother.php?groupid=$groupid&surveyid=$surveyid");
	   exit;
	}
	elseif(isset($_POST['saveandexit']))
	{
	    $enter_question=$_POST['enterquestion'];
		$answers=$_POST['mutlichkboxtext'];
		$rating=$_POST['chkboxpoint'];	
		$answerT=$_POST['radiotrue'];
		$answerD=$_POST['radiodefault'];
		$alignment=$_POST['alignment'];
		$open_ans="";
		$count=count($_POST['mutlichkboxtext']);		
		$noans=0;
		$nopoint=0;
		for($i=0;$i<$count;$i++)
		{			
			$answers[$i]=trim($answers[$i]);
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
		//if(empty($_POST['radiotrue']))
		//	$error_message=$error_message."<br>".get_lang('PleaseSelectOneTrue');
		//if(empty($_POST['radiodefault']))
			//$error_message=$error_message."<br>".get_lang('PleaseSelectOneDefault');
		if(isset($error_message));
		//Display::display_error_message($error_message);	
		else
		{
	     $groupid = $_REQUEST['groupid'];
	     $surveyid = $_REQUEST['surveyid'];
	     $questtype = $_REQUEST['questtype'];
		 $enter_question = addslashes($enter_question);		 SurveyManager::create_question($groupid,$surveyid,$questtype,$enter_question,$alignment,$answers,$open_ans,$answerT,$answerD,$rating,$curr_dbname);		
	     header("location:survey_list.php?n=$n");
	     exit;
		}
	}
}

$tool = get_lang('AddAnotherQuestion');
Display::display_header($tool);
?>

<SCRIPT LANGUAGE="JavaScript">
<!-- Begin
function displayHTML(form) {
//var inf = form.enterquestion.value;
//var abc=form.mutlichkboxtext.length;
var i;
alert(eval(form+"['mutlichkboxtext[]'].length"));
//for(i=0;i<eval("document."+form+"['mutlichkboxtext[]'].length");i++)
	//{
		//alert(i);
//	}

//win = window.open(", ", 'popup', 'toolbar = no, status = no');
//win.document.write("" + inf + "");
}
//  End -->
</script>
<?php
//select_question_type($add_question12,);
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
<form method="POST" name="mcsa" action="<?php echo $_SERVER['PHP_SELF'];?>?add_question=<?php echo $add_question; ?>&groupid=<?php echo $groupid; ?>&surveyid=<?php echo $surveyid; ?>&curr_dbname=<?php echo $curr_dbname; ?>">
<input type="hidden" name="action" value="addquestion">
<input type="hidden" name="groupid" value="<?php echo $groupid; ?>">
<input type="hidden" name="surveyid" value="<?php echo $surveyid; ?>">
<input type="hidden" name="questtype" value="<?php echo $add_question12; ?>">

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
					<td width="542" height="30" colspan="2" >
					<?php
						
						require_once(api_get_path(LIBRARY_PATH) . "/fckeditor/fckeditor.php");
						$oFCKeditor = new FCKeditor('enterquestion') ;
						$oFCKeditor->BasePath	= api_get_path(WEB_PATH) . 'main/inc/lib/fckeditor/' ;
						$oFCKeditor->Height		= '300';
						$oFCKeditor->Width		= '400';
						$oFCKeditor->Value		= stripslashes($enterquestion);
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
$start=1;$end=5;$upx=2;$upy=1;$dwnx=0;$dwny=1;$jd=0;$sn=1;
	$id="id";
	$tempmutlichkboxtext="jkjk";		
	if(isset($_POST['radiodefault']))
	$tempradiodefault=$_POST['radiodefault'];
	else
	$tempradiodefault=1;
	$tempchkboxpoint="jkjk";	
	if(isset($_POST['radiotrue']))
	$tempradiotrue=$_POST['radiotrue'];
	else
	$tempradiotrue=1;
	$up="up";
	$down="down";
	$flag=1;
	if(isset($_POST['mutlichkboxtext']))
	$end=count($_POST['mutlichkboxtext']);	
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
			$flag=0;
			if(isset($_POST[$up]))
			{
				$tempmutlichkboxtext=$_POST['mutlichkboxtext'];
				if($tempradiodefault==$i)
					$tempradiodefault--;
				elseif($tempradiodefault==$i-1)
					$tempradiodefault++;
				$tempchkboxpoint=$_POST['chkboxpoint'];
				if($tempradiotrue==$i)
					$tempradiotrue--;
				elseif($tempradiotrue==$i-1)
					$tempradiotrue++;										
				$tempm=	$tempmutlichkboxtext[$i-2];
				$tempchkboxp=$tempchkboxpoint[$i-2];			
				$tempmutlichkboxtext[$i-2]=$tempmutlichkboxtext[$i-1];
				$tempchkboxpoint[$i-2]=$tempchkboxpoint[$i-1];
				$tempmutlichkboxtext[$i-1]=$tempm;
				$tempchkboxpoint[$i-1]=$tempchkboxp;
				$_POST['mutlichkboxtext']=$tempmutlichkboxtext;
				$_POST['chkboxpoint']=$tempchkboxpoint;
			}
			if(isset($_POST[$down]))
			{
				$tempmutlichkboxtext=$_POST['mutlichkboxtext'];
				if($tempradiodefault==$i)
					$tempradiodefault++;
				elseif($tempradiodefault==$i+1)
					$tempradiodefault--;
				$tempchkboxpoint=$_POST['chkboxpoint'];
				if($tempradiotrue==$i)
					$tempradiotrue++;
				elseif($tempradiotrue==$i+1)
					$tempradiotrue--;
				$tempm=	$tempmutlichkboxtext[$i];
				$tempchkboxp=$tempchkboxpoint[$i];
				$tempmutlichkboxtext[$i]=$tempmutlichkboxtext[$i-1];
				$tempchkboxpoint[$i]=$tempchkboxpoint[$i-1];
				$tempmutlichkboxtext[$i-1]=$tempm;
				$tempchkboxpoint[$i-1]=$tempchkboxp;
				$_POST['mutlichkboxtext']=$tempmutlichkboxtext;
				$_POST['chkboxpoint']=$tempchkboxpoint;
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
				  $error_message = get_lang('YouCantAddMoreThanTen')."<br>";
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
			//$post_check=$_POST['radiodefault'];
			$post_point=$_POST['chkboxpoint'];
			//$post_true=$_POST['radiotrue'];	
	
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
<?php 				if($i>$start)
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
						<input type="image" src="../img/down.gif" width="24" height="24" border="0" onclick="this.form.submit();" name="<?echo "down".$i;?>" style="cursor:hand"> 
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

<?php			if(isset($_POST['add_question']))
			{
?>				<input type="hidden" name="add_question" value="<?php echo $_POST['add_question'];?>" >
<?php			}

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
						<input type="submit"  name="back" value="<?php echo get_lang('Back'); ?>">
						<input type="submit"  name="saveandexit" value="<?php echo get_lang('SaveAndExit');?>">
						<input type="button" value="<?php echo get_lang('Preview');?>" onClick="preview('mcsa','<?php echo $temp; ?>','<?php echo $Multi; ?>')">
						<input type="submit"  name="next" value="<?php echo get_lang('Next'); ?>"> 
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