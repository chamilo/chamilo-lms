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
* 	@version $Id: survey_blank.php 10705 2007-01-12 22:40:01Z pcool $
*/

// name of the language file that needs to be included 
$language_file='admin';

// including the global dokeos file
require_once ('../inc/global.inc.php');

// including additional libraries
/** @todo check if these are all needed */
/** @todo check if the starting / is needed. api_get_path probably ends with an / */
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/groupmanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/usermanager.lib.php");

$surveyid = $_REQUEST['surveyid'];
$uid = $_REQUEST['uid'];
$uid1 = $_REQUEST['uid1'];

$temp = $_REQUEST['temp'];
$mail = $_REQUEST['mail'];
$username = $_REQUEST['username'];
$sql_sname = "select * from $db_name.survey where survey_id='$surveyid'";
$res_sname = api_sql_query($sql_sname,__FILE__,__LINE__);
$obj_sname = mysql_fetch_object($res_sname);
$surveyname = $obj_sname->title;
$sql_b = "SELECT * FROM $db_name.survey_report where user_id='$uid' AND survey_id='$surveyid'";
$parameters = array ();
$res_b = api_sql_query($sql_b,__FILE__,__LINE__);
$attempt=array();
while ($obj = mysql_fetch_object($res_b)){
$attempt[$obj->qid] = $obj->answer;
}
//print_r($attempt);


$surveyid = $_REQUEST['surveyid'];
$rs=mysql_query("SELECT * FROM $table_survey_question WHERE survey_id='$surveyid'");
$row=mysql_num_rows($rs);
$page=ceil($row/4);

if(isset($_GET[num])){
	$num=$_GET[num];
	/*if($num>$page){
		header("Location:test.php");
		exit;
	}*/
}else{
	$num = 1;
}
$lower = $num*4-4;

if(isset($_POST['Back'])){
	//echo $sql ="Insert into";
	$back=$num-2;
	header("location:".$_SERVER['PHP_SELF']."?num=$back");
	exit;
}

Display::display_header($tool_name);
api_display_tool_title("Survey Name : ".$surveyname);
api_display_tool_title($tool_name);	
?>
<script language="Javascript">
function printpage() {
window.print();  
}
</script>
<link href="../css/survey_white.css" rel="stylesheet" type="text/css">
<?php
$ques=$lower;
$sql = "SELECT * FROM $table_survey_question WHERE survey_id='$_REQUEST[surveyid]' ORDER BY gid, sortby limit $lower,4";
$parameters = array ();
$res = api_sql_query($sql,__FILE__,__LINE__);
?>
<table width="600" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="#F6F5F5">

<?php
if ($numb=mysql_num_rows($res) > 0)
	{	
		?>
		<table width="727" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="#F6F5F5">
		<tr>
		<td width="23" height="21">&nbsp;</td>
		<td height="21">&nbsp;</td>
		<td width="20" height="21">&nbsp;</td>
		</tr>
		<tr>
		<td>&nbsp;</td>
		<td valign="top">
		<form method="post" action="<?php echo $_SERVER['PHP_SELF']?>?num=<?php echo $num+1; ?>">
			  <input type="hidden" name="uid1" value="<?php echo $uid1;?>">
		      <input type="hidden" name="surveyid" value="<?php echo $surveyid;?>">
		      <input type="hidden" name="db_name" value="<?php echo $db_name;?>">		  
		      <input type="hidden" name="temp" value="<?php echo $temp;?>">
		      <input type="hidden" name="mail" value="<?php echo $mail;?>">
			  <input type="hidden" name="username" value="<?php echo $username;?>">
	   <table width="100%"  border="0" cellpadding="0" cellspacing="0">
		<?php
		$users = array ();
		$i=$lower+1;
		$group_array=array();
		while ($obj = mysql_fetch_object($res))
		{
			$ques++;			
			$user = array ();
			unset($sel1,$sel2,$m1,$m2,$m3,$m4,$m5,$m6,$m7,$m8,$m9,$m10,$n1,$n2,$n3,$n4,$n5,$n6,$n7,$n8,$n9,$n10);

			if($obj->qtype=="Yes/No")
			{
				if(!in_array($obj->gid,$group_array))
				{
					$group_array[]=$obj->gid;
					$group="SELECT * FROM $db_name.survey_group WHERE survey_id='$_REQUEST[surveyid]' AND group_id='$obj->gid'";
					$res_group=api_sql_query($group);
					$object=mysql_fetch_object($res_group);
					$user_ans = $attempt[$obj->qid];
					if($user_ans=='a1'){
					$sel1="checked";
				}else if($user_ans=='a2'){
					$sel2="checked";
				}
					if($object->groupname!='No Group')
					{
						echo "<tr><td><hr><br><table border='1'><tr><td align='center'>".$object->groupname."<br><br>".$object->introduction."</td></tr></table><br><br>";
					}
			echo "Q.".$ques."&nbsp;&nbsp;<b>".$obj->caption."</b><br>"."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a1' $sel1 name='q[".$obj->qid."]'>".$obj->a1.
			"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a2' $sel2 name='q[".$obj->qid."]'>".$obj->a2."<BR><BR></td></tr>";
			//$users[] = $user;
				}
				else
				{
					$user_ans = $attempt[$obj->qid];
					if($user_ans=='a1'){
					$sel1="checked";
				}else if($user_ans=='a2'){
					$sel2="checked";
				}
			echo "<tr><td>"."Q.".$ques."&nbsp;&nbsp;<b>".$obj->caption."</b><br>".
			"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a1' $sel1 name='q[".$obj->qid."]'>".$obj->a1.
			"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a2' $sel2 name='q[".$obj->qid."]'>".$obj->a2."<BR><BR></td></tr>";
			//$users[] = $user;
				}
			}
			if($obj->qtype=="Open Answer")
			{
				if(!in_array($obj->gid,$group_array))
				{
					$group_array[]=$obj->gid;
					$group="SELECT * FROM $db_name.survey_group WHERE survey_id='$_REQUEST[surveyid]' AND group_id='$obj->gid'";
					$res_group=api_sql_query($group);
					$object=mysql_fetch_object($res_group);
					$atmp=$attempt[$obj->qid];
					if($object->groupname!='No Group')
					{
						echo "<tr><td><hr><br><table border='1'><tr><td align='center'>".$object->groupname."<br><br>".$object->introduction."</td></tr></table><br><br>";
					}
					echo "Q.".$ques."&nbsp;&nbsp;<b>".$obj->caption."</b><br><br>"."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea cols='50' rows='6' name='q[".$obj->qid."]'>".$obj->a1.
					"$atmp</textarea><br><br></td></tr>";
					//$users[] = $user;
				}
				else
				{
					$atmp=$attempt[$obj->qid];
					//"<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=text name=".$i.">".$obj->a2
					echo "<tr><td>"."Q.".$ques."&nbsp;&nbsp;<b>".$obj->caption."</b><br>".
					"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea cols='50' rows='6' name='q[".$obj->qid."]'>".$obj->a1.
					"$atmp</textarea><br><br></td></tr>";
					//$users[] = $user;
				}
			}
			if($obj->qtype=="Multiple Choice (multiple answer)")
			{
				if(!in_array($obj->gid,$group_array))
				{
					$group_array[]=$obj->gid;
					$group="SELECT * FROM $db_name.survey_group WHERE survey_id='$_REQUEST[surveyid]' AND group_id='$obj->gid'";
					$res_group=api_sql_query($group);
					$object=mysql_fetch_object($res_group);
					$atmp1 = $attempt[$obj->qid];
					$atmp2=explode(',',$atmp1);
					if($obj->alignment=='vertical')
					{
						$break= "<br>";
					}
					foreach($atmp2 as $k => $v){
						if($v=='a1'){
							$m1="checked";
						}else if($v=='a2'){
							$m2="checked";
						}else if($v=='a3'){
							$m3="checked";
						}else if($v=='a4'){
							$m4="checked";
						}else if($v=='a5'){
							$m5="checked";
						}else if($v=='a6'){
							$m6="checked";
						}else if($v=='a7'){
							$m7="checked";
						}else if($v=='a8'){
							$m8="checked";
						}else if($v=='a9'){
							$m9="checked";
						}else if($v=='a10'){
							$m10="checked";
						}
					}
					if($object->groupname!='No Group')
					{
						echo "<tr><td><hr><br><table border='1'><tr><td align='center'>".$object->groupname."<br><br>".$object->introduction."</td></tr></table><br><br>";
					}
				echo "Q.".$ques."&nbsp;&nbsp;<b>".$obj->caption."</b><br>";
				if($obj->a1 != ""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input value='a1' $m1 type=checkbox name='q[".$obj->qid."][]'>".$obj->a1;
				}
				if($obj->a2!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input value='a2' $m2 type=checkbox name='q[".$obj->qid."][]'>".$obj->a2;
				}
				if($obj->a3!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input value='a3' $m3 type=checkbox name='q[".$obj->qid."][]'>".$obj->a3;
				}
				if($obj->a4!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input value='a4' $m4 type=checkbox name='q[".$obj->qid."][]'>".$obj->a4;
				}
				if($obj->a5!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input value='a5' $m5 type=checkbox name='q[".$obj->qid."][]'>".$obj->a5;
				}
				if($obj->a6!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input value='a6' $m6 type=checkbox name='q[".$obj->qid."][]'>".$obj->a6;
				}	
				if($obj->a7!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input value='a7' $m7 type=checkbox name='q[".$obj->qid."][]'>".$obj->a7;
				}
				if($obj->a8!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input value='a8' $m8 type=checkbox name='q[".$obj->qid."][]'>".$obj->a8;
				}
				if($obj->a9!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input value='a9' $m9 type=checkbox name='q[".$obj->qid."][]'>".$obj->a9;
				}
				if($obj->a10!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input value='a10' $m10 type=checkbox name='q[".$obj->qid."][]'>".$obj->a10;
				}	
				echo "<br><br></td></tr>";			
				//$user[]=$val."<BR><BR>";
				//$users[] = $user;
				}
				else
				{
					if($obj->alignment=='vertical')
					{
						$break= "<br>";
					}
					$atmp1 = $attempt[$obj->qid];
					$atmp2=explode(',',$atmp1);
					
					foreach($atmp2 as $k => $v){
						if($v=='a1'){
							$m1="checked";
						}else if($v=='a2'){
							$m2="checked";
						}else if($v=='a3'){
							$m3="checked";
						}else if($v=='a4'){
							$m4="checked";
						}else if($v=='a5'){
							$m5="checked";
						}else if($v=='a6'){
							$m6="checked";
						}else if($v=='a7'){
							$m7="checked";
						}else if($v=='a8'){
							$m8="checked";
						}else if($v=='a9'){
							$m9="checked";
						}else if($v=='a10'){
							$m10="checked";
						}
					}
				echo "<tr><td>"."Q.".$ques."&nbsp;&nbsp;<b>".$obj->caption."</b><br>";
				if($obj->a1 != ""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input value='a1' $m1 type=checkbox name='q[".$obj->qid."][]'>".$obj->a1;
				}
				if($obj->a2!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input value='a2' $m2 type=checkbox name='q[".$obj->qid."][]'>".$obj->a2;
				}
				if($obj->a3!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input value='a3' $m3 type=checkbox name='q[".$obj->qid."][]'>".$obj->a3;
				}
				if($obj->a4!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input value='a4' $m4 type=checkbox name='q[".$obj->qid."][]'>".$obj->a4;
				}
				if($obj->a5!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input value='a5' $m5 type=checkbox name='q[".$obj->qid."][]'>".$obj->a5;
				}
				if($obj->a6!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input value='a6' $m6 type=checkbox name='q[".$obj->qid."][]'>".$obj->a6;
				}	
				if($obj->a7!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input value='a7' $m7 type=checkbox name='q[".$obj->qid."][]'>".$obj->a7;
				}
				if($obj->a8!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input value='a8' $m8 type=checkbox name='q[".$obj->qid."][]'>".$obj->a8;
				}
				if($obj->a9!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input value='a9' $m9 type=checkbox name='q[".$obj->qid."][]'>".$obj->a9;
				}
				if($obj->a10!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input value='a10' $m10 type=checkbox name='q[".$obj->qid."][]'>".$obj->a10;
				}	
				echo "<br><br></td></tr>";			
				//$user[]=$val."<BR><BR>";
				//$users[] = $user;
				}
				
			}
			if($obj->qtype=="Multiple Choice (single answer)")
			{
				if(!in_array($obj->gid,$group_array))
				{
					$group_array[]=$obj->gid;
					$group="SELECT * FROM $db_name.survey_group WHERE survey_id='$_REQUEST[surveyid]' AND group_id='$obj->gid'";
					$res_group=api_sql_query($group);
					$object=mysql_fetch_object($res_group);
					unset($s1,$s2,$s3,$s4,$s5,$s6,$s7,$s8,$s9,$s10);
					if($obj->alignment=='vertical')
					{
						$break= "<br>";
					}
					 $user_ans = $attempt[$obj->qid];
					if($user_ans=='a1'){
						$s1="checked";
					}else if($user_ans=='a2'){
						$s2="checked";
					}else if($user_ans=='a3'){
						$s3="checked";
					}else if($user_ans=='a4'){
						$s4="checked";
					}else if($user_ans=='a5'){
						$s5="checked";
					}else if($user_ans=='a6'){
						$s6="checked";
					}else if($user_ans=='a7'){
						$s7="checked";
					}else if($user_ans=='a8'){
						$s8="checked";
					}else if($user_ans=='a9'){
						$s9="checked";
					}else if($user_ans=='a10'){
						$s10="checked";
					}
				if($object->groupname!='No Group')
				{
					echo "<tr><td><hr><br><table border='1'><tr><td align='center'>".$object->groupname."<br><br>".$object->introduction."</td></tr></table><br><br>";
				}
				echo "Q.".$ques."&nbsp;&nbsp;<b>".$obj->caption."</b><br>";
				if($obj->a1 != ""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a1' $s1 name='q[".$obj->qid."]'>".$obj->a1;
				}
				if($obj->a2!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a2' $s2 name='q[".$obj->qid."]'>".$obj->a2;
				}
				if($obj->a3!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a3' $s3 name='q[".$obj->qid."]'>".$obj->a3;
				}
				if($obj->a4!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a4' $s4 name='q[".$obj->qid."]'>".$obj->a4;
				}
				if($obj->a5!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a5' $s5 name='q[".$obj->qid."]'>".$obj->a5;
				}
				if($obj->a6!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a6' $s6 name='q[".$obj->qid."]'>".$obj->a6;
				}	
				if($obj->a7!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a7' $s7 name='q[".$obj->qid."]'>".$obj->a7;
				}
				if($obj->a8!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a8' $s8 name='q[".$obj->qid."]'>".$obj->a8;
				}
				if($obj->a9!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a9' $s9 name='q[".$obj->qid."]'>".$obj->a9;
				}
				if($obj->a10!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a10' $s10 name='q[".$obj->qid."]'>".$obj->a10;
				}	
				echo "<br><br></td></tr>";
				//$user[]=$val."<BR><BR>";;
				//$users[] = $user;
				}
				else
				{
					if($obj->alignment=='vertical')
					{
						$break= "<br>";
					}
					 $user_ans = $attempt[$obj->qid];
					if($user_ans=='a1'){
						$s1="checked";
					}else if($user_ans=='a2'){
						$s2="checked";
					}else if($user_ans=='a3'){
						$s3="checked";
					}else if($user_ans=='a4'){
						$s4="checked";
					}else if($user_ans=='a5'){
						$s5="checked";
					}else if($user_ans=='a6'){
						$s6="checked";
					}else if($user_ans=='a7'){
						$s7="checked";
					}else if($user_ans=='a8'){
						$s8="checked";
					}else if($user_ans=='a9'){
						$s9="checked";
					}else if($user_ans=='a10'){
						$s10="checked";
					}
				echo "<tr><td>"."Q.".$ques."&nbsp;&nbsp;<b>".$obj->caption."</b><br>";
				if($obj->a1 != ""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a1' $s1 name='q[".$obj->qid."]'>".$obj->a1;
				}
				if($obj->a2!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a2' $s2 name='q[".$obj->qid."]'>".$obj->a2;
				}
				if($obj->a3!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a3' $s3 name='q[".$obj->qid."]'>".$obj->a3;
				}
				if($obj->a4!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a4' $s4 name='q[".$obj->qid."]'>".$obj->a4;
				}
				if($obj->a5!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a5' $s5 name='q[".$obj->qid."]'>".$obj->a5;
				}
				if($obj->a6!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a6' $s6 name='q[".$obj->qid."]'>".$obj->a6;
				}	
				if($obj->a7!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a7' $s7 name='q[".$obj->qid."]'>".$obj->a7;
				}
				if($obj->a8!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a8' $s8 name='q[".$obj->qid."]'>".$obj->a8;
				}
				if($obj->a9!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a9' $s9 name='q[".$obj->qid."]'>".$obj->a9;
				}
				if($obj->a10!=""){
				echo $break."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a10' $s10 name='q[".$obj->qid."]'>".$obj->a10;
				}	
				echo "<br><br></td></tr>";
				//$user[]=$val."<BR><BR>";;
				//$users[] = $user;
				}
			}			
			if($obj->qtype=="Numbered")
			{
				if(!in_array($obj->gid,$group_array))
				{
					$group_array[]=$obj->gid;
					$group="SELECT * FROM $db_name.survey_group WHERE survey_id='$_REQUEST[surveyid]' AND group_id='$obj->gid'";
					$res_group=api_sql_query($group);
					$object=mysql_fetch_object($res_group);
					$atmp1 = $attempt[$obj->qid];
					$atmp2=explode(',',$atmp1);
					if($object->groupname!='No Group')
					{
					echo "<tr><td><hr><br><table border='1'><tr><td align='center'>".$object->groupname."<br><br>".$object->introduction."</td></tr></table><br><br>";
					}
					echo "Q.".$ques."&nbsp;&nbsp;<b>".$obj->caption."</b><br>";
					if($obj->a1 != ""){
					echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea rows='6' cols='50' disabled='true'>".$obj->a1."</textarea>"."&nbsp;&nbsp;&nbsp;<select name='q[".$obj->qid."][]'>";
					echo "<option value='0'>0</option>";
					for($i=1;$i<=10;$i++){
						$x="";
						if($atmp2[0]==$i){
							$x="selected";
						}
						echo "<option value=$i $x>$i</option>";
					}
					echo "</select><br>";
				}
				if($obj->a2 != ""){
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea rows='6' cols='50' disabled='true'>".$obj->a2."</textarea>"."&nbsp;&nbsp;&nbsp;<select name='q[".$obj->qid."][]'>";
					echo "<option value='0'>0</option>";
					for($i=1;$i<=10;$i++){
						$x="";
						if($atmp2[1]==$i){
							$x="selected";
						}
						echo "<option value=$i $x>$i</option>";
					}
					echo "</select><br>";
				}
				if($obj->a3 != ""){
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea rows='6' cols='50' disabled='true'>".$obj->a3."</textarea>"."&nbsp;&nbsp;&nbsp;<select name='q[".$obj->qid."][]'>";
					echo "<option value='0'>0</option>";
					for($i=1;$i<=10;$i++){
						$x="";
						if($atmp2[2]==$i){
							$x="selected";
						}
						echo "<option value=$i $x>$i</option>";
					}
					echo "</select><br>";
				}
				if($obj->a4 != ""){
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea rows='6' cols='50' disabled='true'>".$obj->a4."</textarea>"."&nbsp;&nbsp;&nbsp;<select name='q[".$obj->qid."][]'>";
					echo "<option value='0'>0</option>";
					for($i=1;$i<=10;$i++){
						$x="";
						if($atmp2[3]==$i){
							$x="selected";
						}
						echo "<option value=$i $x>$i</option>";
					}
					echo "</select><br>";
				}
				if($obj->a5 != ""){
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea rows='6' cols='50' disabled='true'>".$obj->a5."</textarea>"."&nbsp;&nbsp;&nbsp;<select name='q[".$obj->qid."][]'>";
					echo "<option value='0'>0</option>";
					for($i=1;$i<=10;$i++){
						$x="";
						if($atmp2[4]==$i){
							$x="selected";
						}
						echo "<option value=$i $x>$i</option>";
					}
					echo "</select><br>";
				}
				if($obj->a6 != ""){
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea rows='6' cols='50' disabled='true'>".$obj->a6."</textarea>"."&nbsp;&nbsp;&nbsp;<select name='q[".$obj->qid."][]'>";
					echo "<option value='0'>0</option>";
					for($i=1;$i<=10;$i++){
						$x="";
						if($atmp2[5]==$i){
							$x="selected";
						}
						echo "<option value=$i $x>$i</option>";
					}
					echo "</select><br>";
				}
				if($obj->a7 != ""){
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea rows='6' cols='50' disabled='true'>".$obj->a7."</textarea>"."&nbsp;&nbsp;&nbsp;<select name='q[".$obj->qid."][]'>";
					echo "<option value='0'>0</option>";
					for($i=1;$i<=10;$i++){
						$x="";
						if($atmp2[6]==$i){
							$x="selected";
						}
						echo "<option value=$i $x>$i</option>";
					}
					echo "</select><br>";
				}
				if($obj->a8 != ""){
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea rows='6' cols='50' disabled='true'>".$obj->a8."</textarea>"."&nbsp;&nbsp;&nbsp;<select name='q[".$obj->qid."][]'>";
					echo "<option value='0'>0</option>";
					for($i=1;$i<=10;$i++){
						$x="";
						if($atmp2[7]==$i){
							$x="selected";
						}
						echo "<option value=$i $x>$i</option>";
					}
					echo "</select><br>";
				}
				if($obj->a9 != ""){
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea rows='6' cols='50' disabled='true'>".$obj->a9."</textarea>"."&nbsp;&nbsp;&nbsp;<select name='q[".$obj->qid."][]'>";
					echo "<option value='0'>0</option>";
					for($i=1;$i<=10;$i++){
						$x="";
						if($atmp2[8]==$i){
							$x="selected";
						}
						echo "<option value=$i $x>$i</option>";
					}
					echo "</select><br>";
				}
				if($obj->a10 != ""){
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea rows='6' cols='50' disabled='true'>".$obj->a10."</textarea>"."&nbsp;&nbsp;&nbsp;<select name='q[".$obj->qid."][]'>";
					echo "<option value='0'>0</option>";
					for($i=1;$i<=10;$i++){
						$x="";
						if($atmp2[9]==$i){
							$x="selected";
						}
						echo "<option value=$i $x>$i</option>";
					}
					echo "</select><br>";
				}						
				echo "<br><br></td></tr>";
				//$user[]=$val."<BR><BR>";;
				//$users[] = $user;	
				}
				else
				{
					$atmp1 = $attempt[$obj->qid];
					$atmp2=explode(',',$atmp1);
					echo "<tr><td>"."Q.".$ques."&nbsp;&nbsp;<b>".$obj->caption."</b><br>";
					if($obj->a1 != ""){
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea rows='6' cols='50' disabled='true'>".$obj->a1."</textarea>"."&nbsp;&nbsp;&nbsp;<select name='q[".$obj->qid."][]'>";
					echo "<option value='0'>0</option>";
					for($i=1;$i<=10;$i++){
						$x="";
						if($atmp2[0]==$i){
							$x="selected";
						}
						echo "<option value=$i $x>$i</option>";
					}
					echo "</select><br>";
				}
				if($obj->a2 != ""){
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea rows='6' cols='50' disabled='true'>".$obj->a2."</textarea>"."&nbsp;&nbsp;&nbsp;<select name='q[".$obj->qid."][]'>";
					echo "<option value='0'>0</option>";
					for($i=1;$i<=10;$i++){
						$x="";
						if($atmp2[1]==$i){
							$x="selected";
						}
						echo "<option value=$i $x>$i</option>";
					}
					echo "</select><br>";
				}
				if($obj->a3 != ""){
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea rows='6' cols='50' disabled='true'>".$obj->a3."</textarea>"."&nbsp;&nbsp;&nbsp;<select name='q[".$obj->qid."][]'>";
					echo "<option value='0'>0</option>";
					for($i=1;$i<=10;$i++){
						$x="";
						if($atmp2[2]==$i){
							$x="selected";
						}
						echo "<option value=$i $x>$i</option>";
					}
					echo "</select><br>";
				}
				if($obj->a4 != ""){
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea rows='6' cols='50' disabled='true'>".$obj->a4."</textarea>"."&nbsp;&nbsp;&nbsp;<select name='q[".$obj->qid."][]'>";
					echo "<option value='0'>0</option>";
					for($i=1;$i<=10;$i++){
						$x="";
						if($atmp2[3]==$i){
							$x="selected";
						}
						echo "<option value=$i $x>$i</option>";
					}
					echo "</select><br>";
				}
				if($obj->a5 != ""){
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea rows='6' cols='50' disabled='true'>".$obj->a5."</textarea>"."&nbsp;&nbsp;&nbsp;<select name='q[".$obj->qid."][]'>";
					echo "<option value='0'>0</option>";
					for($i=1;$i<=10;$i++){
						$x="";
						if($atmp2[4]==$i){
							$x="selected";
						}
						echo "<option value=$i $x>$i</option>";
					}
					echo "</select><br>";
				}
				if($obj->a6 != ""){
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea rows='6' cols='50' disabled='true'>".$obj->a6."</textarea>"."&nbsp;&nbsp;&nbsp;<select name='q[".$obj->qid."][]'>";
					echo "<option value='0'>0</option>";
					for($i=1;$i<=10;$i++){
						$x="";
						if($atmp2[5]==$i){
							$x="selected";
						}
						echo "<option value=$i $x>$i</option>";
					}
					echo "</select><br>";
				}
				if($obj->a7 != ""){
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea rows='6' cols='50' disabled='true'>".$obj->a7."</textarea>"."&nbsp;&nbsp;&nbsp;<select name='q[".$obj->qid."][]'>";
					echo "<option value='0'>0</option>";
					for($i=1;$i<=10;$i++){
						$x="";
						if($atmp2[6]==$i){
							$x="selected";
						}
						echo "<option value=$i $x>$i</option>";
					}
					echo "</select><br>";
				}
				if($obj->a8 != ""){
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea rows='6' cols='50' disabled='true'>".$obj->a8."</textarea>"."&nbsp;&nbsp;&nbsp;<select name='q[".$obj->qid."][]'>";
					echo "<option value='0'>0</option>";
					for($i=1;$i<=10;$i++){
						$x="";
						if($atmp2[7]==$i){
							$x="selected";
						}
						echo "<option value=$i $x>$i</option>";
					}
					echo "</select><br>";
				}
				if($obj->a9 != ""){
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea rows='6' cols='50' disabled='true'>".$obj->a9."</textarea>"."&nbsp;&nbsp;&nbsp;<select name='q[".$obj->qid."][]'>";
					echo "<option value='0'>0</option>";
					for($i=1;$i<=10;$i++){
						$x="";
						if($atmp2[8]==$i){
							$x="selected";
						}
						echo "<option value=$i $x>$i</option>";
					}
					echo "</select><br>";
				}
				if($obj->a10 != ""){
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea rows='6' cols='50' disabled='true'>".$obj->a10."</textarea>"."&nbsp;&nbsp;&nbsp;<select name='q[".$obj->qid."][]'>";
					echo "<option value='0'>0</option>";
					for($i=1;$i<=10;$i++){
						$x="";
						if($atmp2[9]==$i){
							$x="selected";
						}
						echo "<option value=$i $x>$i</option>";
					}
					echo "</select><br>";
				}						
				echo "<br><br></td></tr>";
				//$user[]=$val."<BR><BR>";;
				//$users[] = $user;	
				}
			}
			$i++;
		 }
	   $table_header[] = array ($surveyname, false);
	   //Display :: display_sortable_table($table_header, $users, array (), array (), $parameters);
		
	}
	else
	{
		echo get_lang('NoSearchResults');
	}
?><table width="600" border="0" align="center" cellpadding="0" cellspacing="0">

<table width="727" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td width="23" height="21" class="form-top-1">&nbsp;</td>
    <td height="21" class="form-top-2">&nbsp;</td>
    <td width="20" height="21" class="form-top-3">&nbsp;</td>
  </tr>
  <tr>
    <td class="form-left">&nbsp;</td>
    <td valign="top">
	<form method="post" action="<?php echo $_SERVER['PHP_SELF']?>?num=<?php echo $num+1; ?>">
			  <input type="hidden" name="uid1" value="<?php echo $uid1;?>">
		      <input type="hidden" name="surveyid" value="<?php echo $surveyid;?>">
		      <input type="hidden" name="temp" value="<?php echo $temp;?>">
		      <input type="hidden" name="mail" value="<?php echo $mail;?>">
			  <input type="hidden" name="username" value="<?php echo $username;?>">
			  <table width="100%"  border="0" cellpadding="0" cellspacing="0">

				  <tr>
				 <td align="center">
					<?php if($num > "1"){
								
							   echo "<input type=\"button\" name=\"Back\" value=\"Back\" onClick=\"location.href('".$_SERVER['PHP_SELF']."?temp=$temp&&surveyid=$surveyid&num=".($num-1)."');\">";
					} else{
							echo "<input type=\"button\" name=\"Back\" value=\"Back\" onClick=\"location.href('survey_list.php?uid1=$uid1&mail=$mail&sid=$surveyid');\">";
					}
					if($num >= $page) {echo "";
					?>
					<input type="button" value="Print" onClick="printpage()">
					<?php }
					else{ $sub_name = "Next";
					?>			
					<input type="submit" name="submit" value="<?php echo $sub_name; ?>">&nbsp;<input type="button"
value="Print" onClick="printpage()"></td>
					<?php }?>
				  </tr>
		</table>
	  </form>
	</td>
    <td class="form-right">&nbsp;</td>
  </tr>
  <tr>
    <td class="form-bottom-1">&nbsp;</td>
    <td class="form-bottom-2">&nbsp;</td>
    <td class="form-bottom-3">&nbsp;</td>
  </tr>
</table>
<?php
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>
