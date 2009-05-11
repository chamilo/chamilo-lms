<?php // $Id: usermanager.lib.php,v 1.9.2.3 2005/10/28 14:31:45 bmol Exp $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	This library provides functions for user management.
*	Include/require it in your code to use its functionality.
*
*	@author Bart Mollet, main author
*	@package dokeos.library
==============================================================================
*/
class SurveyManager
{
	/**
	  * Creates a new survey for the platform
	  */
	function select_survey_list($seleced_surveyid='', $extra_script='')
	{
		$survey_table = Database :: get_course_table('survey');
		$sql = "SELECT * FROM $survey_table";// WHERE is_shared='1'";
		$sql_result = api_sql_query($sql,__FILE__,__LINE__);
		if(mysql_num_rows($sql_result)>0)
		{
			$str_survey_list = "";
			$str_survey_list .= "<select name=\"exiztingsurvey\" $extra_script>\n";
			$str_survey_list .= "<option value=\"\">--Select Survey--</option>";

			while($result=mysql_fetch_array($sql_result))
			{
			 $selected = ($seleced_surveyid==$result[survey_id])?"selected":"";
			 $str_survey_list .= "\n<option value=\"".$result[survey_id]."\" ".$selected.">".$result[title]."</option>";
			}

			$str_survey_list .= "</select>";
			return $str_survey_list;

		}
		else
		{
			return false;
		}

	}

	/*function getsurveyid($existing)
	{

		$survey_table = Database :: get_course_table(TABLE_MAIN_SURVEY);
		$sql = "SELECT survey_id FROM $survey_table WHERE title='$existing'" ;
		$result = api_sql_query($sql,__FILE__,__LINE__);
		$i=0;
		$survey_id=mysql_result($result,$i,'survey_id');
		echo "in getsurveyid".$survey_id;
		return(survey_id);
	}
*/


	function create_group($survey_id,$group_title,$introduction,$table_group)
	{		
		
		$sql_query = "SELECT * FROM $table_group WHERE groupname='".Database::escape_string($group_title)."' AND survey_id=".Database::escape_string($survey_id);
		$res = api_sql_query($sql_query, __FILE__, __LINE__);
		if(mysql_num_rows($res))
		{
			return false;

		}
		else
		 {

			$sql = 'SELECT MAX(sortby) FROM '.$table_group.' WHERE survey_id="'.Database::escape_string($survey_id).'"';
			$rs = api_sql_query($sql, __FILE__, __LINE__);
			list($sortby) = mysql_fetch_array($rs);
			$sortby++;
			$sql="INSERT INTO $table_group(group_id,survey_id,groupname,introduction, sortby) values('','$survey_id','$group_title','$introduction','$sortby')";
			$result=api_sql_query($sql);
			return mysql_insert_id();
		 }

	}


	function get_survey_author($authorid)
	{
			$user_table = Database :: get_main_table(TABLE_MAIN_USER);
			$authorid = Database::escape_string($authorid);
		    $sql_query = "SELECT * FROM $user_table WHERE user_id='$authorid'";
			$res = api_sql_query($sql_query, __FILE__, __LINE__);
			$firstname=@mysql_result($res,0,'firstname');
			return $firstname;
	}


	function get_author($db_name,$survey_id)
	{
	    //$table_survey = Database :: get_course_table('survey');
	    $survey_id = Database::escape_string($survey_id);
		$sql = "SELECT author FROM $db_name.survey WHERE survey_id='$survey_id'";
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$author=@mysql_result($res,0,'author');
		return $author;
	}

	function get_surveyid($db_name,$group_id)
	{
	    //$group_table = Database :: get_course_table('survey_group');
	    $group_id = Database::escape_string($group_id);
		$sql = "SELECT survey_id FROM $db_name.survey_group WHERE group_id='$group_id'";
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$surveyid=@mysql_result($res,0,'survey_id');
		return $surveyid;
	}

	/*function get_survey_code($table_survey,$survey_code)
	{
			$sql="SELECT code FROM $table_survey where code='$survey_code'";
			$result=api_sql_query($sql);
			$code=mysql_result($result,0,'code');
			return($code);
	}*/

	function get_groupname($db_name,$gid)
	{
		//$grouptable = Database :: get_course_table('survey_group');
		$gid = Database::escape_string($gid);
		$sql = "SELECT * FROM $db_name.survey_group WHERE group_id='$gid'";
		$res=api_sql_query($sql);
		$code=@mysql_result($res,0,'groupname');
		return($code);
	}


	function insert_into_group($survey_id,$group_title,$introduction,$tb)
	{				
		$survey_id = Database::escape_string($survey_id);
		$group_title = Database::escape_string($group_title);
		$introduction = Database::escape_string($introduction);	
		
		$sql="INSERT INTO $tb (group_id,survey_id,group_title,introduction) values('','$survey_id','$group_title','$introduction')";
		$result=api_sql_query($sql);
		return mysql_insert_id();
	}

	function get_survey_code($table_survey,$survey_code)
	{
		$survey_code = Database::escape_string($survey_code);
		$sql="SELECT code FROM $table_survey where code='$survey_code'";
		//echo $sql;
		//exit;
		$result=api_sql_query($sql);
		$code=@mysql_result($result,0,'code');
		//echo $code;exit;
		return($code);
	}

	function get_survey_list()
	{
		$survey_table = Database :: get_course_table('survey');
		$sql_query = "SELECT survey_id,title FROM $survey_table where title!='' ";
		$sql_result = api_sql_query($sql_query,__FILE__,__LINE__);
		echo "<select name=\"author\">";
		echo "<option value=\"\"><--Select Survey--></optional>";
		while ($result =@mysql_fetch_array($sql_result))
		{
			echo "\n<option value=\"".$result[survey_id]."\">".$result[title]."</option>";
		}
		echo "</select>";
	}

	function create_survey($surveycode,$surveytitle, $surveysubtitle, $author, $survey_language, $availablefrom, $availabletill,$isshare, $surveytemplate, $surveyintroduction, $surveythanks, $table_survey, $table_group)
		{
			//$table_survey = Database :: get_course_table('survey');
			$sql = "INSERT INTO $table_survey (code,title, subtitle, author,lang,avail_from,avail_till, is_shared,template,intro,surveythanks,creation_date) values('$surveycode','$surveytitle','$surveysubtitle','$author','$survey_language','$availablefrom','$availabletill','$isshare','$surveytemplate','$surveyintroduction','$surveythanks',curdate())";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			//$result = api_sql_query($sql);
			$survey_id = mysql_insert_id();
			$sql2 = "INSERT INTO $table_group(group_id,survey_id,groupname,introduction) values('','$survey_id','No Group','This is your Default Group')";
			$result = api_sql_query($sql2, __FILE__, __LINE__);
			return $survey_id;
		}

	function create_survey_in_another_language($id, $lang){

		global $_course;

		$original_survey = SurveyManager::get_all_datas($id);

		// copy the survey itself
		$sql = 'INSERT INTO '.$_course['dbName'].'.survey SET
					code = "'.$original_survey->code.'",
					title = "'.addslashes($original_survey->title).'",
					subtitle = "'.addslashes($original_survey->subtitle).'",
					author = "'.$original_survey->author.'",
					lang = "'.$lang.'",
					avail_from = "'.$original_survey->avail_from.'",
					avail_till = "'.$original_survey->avail_till.'",
					is_shared = "'.$original_survey->is_shared.'",
					template = "'.$original_survey->template.'",
					intro = "'.addslashes($original_survey->intro).'",
					surveythanks = "'.addslashes($original_survey->surveythanks).'",
					creation_date = "NOW()"';

		$result = api_sql_query($sql, __FILE__, __LINE__);
		$new_survey_id = mysql_insert_id();

		// copy the groups
		$groups = SurveyManager::listGroups($id);
		foreach($groups as $group)
		{
			SurveyManager::import_group($new_survey_id, $group['group_id'], $_course['dbName'], $_course['dbName']);
		}

	}

	function create_survey_attach($surveycode,$surveytitle, $surveysubtitle, $author, $survey_language, $availablefrom, $availabletill,$isshare, $surveytemplate, $surveyintroduction, $surveythanks, $table_survey, $table_group)
	{
			//$table_survey = Database :: get_course_table('survey');
			$sql = "INSERT INTO $table_survey (code,title, subtitle, author,lang,avail_from,avail_till, is_shared,template,intro,surveythanks,creation_date) values('$surveycode','$surveytitle','$surveysubtitle','$author','$survey_language','$availablefrom','$availabletill','$isshare','$surveytemplate','$surveyintroduction','$surveythanks',curdate())";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			$survey_id = mysql_insert_id();
			return $survey_id;
	}

	function update_survey($surveyid,$surveycode,$surveytitle, $surveysubtitle, $author, $survey_language, $availablefrom, $availabletill,$isshare, $surveytemplate, $surveyintroduction, $surveythanks, $cidReq,$table_course)
	{
          $sql_course = "SELECT * FROM $table_course WHERE code = '$cidReq'";
          $res_course = api_sql_query($sql_course,__FILE__,__LINE__);
          $obj_course=@mysql_fetch_object($res_course);
          $curr_dbname = $obj_course->db_name ;
          $surveyid = Database::escape_string($surveyid);
		  $sql = "UPDATE $curr_dbname.survey SET code='$surveycode', title='$surveytitle', subtitle='$surveysubtitle', lang='$survey_language',   avail_from='$availablefrom', avail_till='$availabletill', is_shared='$isshare', template='$surveytemplate', intro='$surveyintroduction',surveythanks='$surveythanks'
		  		  WHERE survey_id='$surveyid'";
		  api_sql_query($sql, __FILE__, __LINE__);
		  return $curr_dbname;
	}

	/*
	function create_question($gid,$type,$caption,$answers,$open_ans,$answerT,$answerD,$rating,$table_question)
	{

		for($i=0;$i<10;$i++)
		{
		   $x.= "'".$answers[$i]."',";
		}
		//echo "Hello".$x;

		for($j=0;$j<10;$j++)
		{
			if($j==9)
			{
				$y.= "'".$rating[$j]."'";
			}
			else
			{
				$y.= "'".$rating[$j]."',";
			}
		}

		$anst = implode(", " ,$answerT);
		$ansd = implode(", " ,$answerD);

		$table_question = Database :: get_course_table(TABLE_MAIN_SURVEYQUESTION);
		$sql = "INSERT INTO  $table_question (gid,type,caption,ans1,ans2,ans3,ans4,ans5,ans6,ans7,ans8,ans9,ans10,open_ans,anst,ansd,r1,r2,r3,r4,r5,r6,r7,r8,r9,r10) values('$gid','$type','$caption',$x'$open_ans','$anst','$ansd',$y)";
		$result = api_sql_query($sql);
		return mysql_insert_id();
	}

	function get_question($questionid)
	{
		$table_question = Database :: get_course_table(TABLE_MAIN_SURVEYQUESTION);
	  	$sql = "SELECT * FROM $table_question where qid='$questionid'";
		$res=api_sql_query($sql);
		$code=@mysql_result($res,0,'caption');
		return($code);
	}
	*/

	function create_question($gid,$surveyid,$qtype,$caption,$alignment,$answers,$open_ans,$answerT,$answerD,$rating,$curr_dbname)
	{
    	$sql_sort = "SELECT max(sortby) AS sortby FROM $curr_dbname.questions ";
		$res_sort=api_sql_query($sql_sort);
		$rs=mysql_fetch_object($res_sort);
		$sortby=$rs->sortby;
		if(empty($sortby))
		{
			$sortby=1;
		}
		else
		{
			$sortby=$sortby+1;
		}
		for($i=0;$i<10;$i++)
		{
		   $x.= "'".$answers[$i]."',";
		}
		for($j=0;$j<10;$j++)
		{
			if($j==9)
			{
				$y.= "'".$rating[$j]."'";
			}
			else
			{
				$y.= "'".$rating[$j]."',";
			}
		}
		/*if($qtype=='Multiple Choice (multiple answer)')
		{
			$anst = implode(", " ,$answerT);
			$ansd = implode(", " ,$answerD);
		}
		else
		{*/
			$anst = $answerT;
			$ansd = $answerD;
		//}
			$sql = "INSERT INTO $curr_dbname.questions (gid,survey_id,qtype,caption,alignment,sortby,a1,a2,a3,a4,a5,a6,a7,a8,a9,a10,at,ad,r1,r2,r3,r4,r5,r6,r7,r8,r9,r10) values('$gid','$surveyid','$qtype','$caption','$alignment','$sortby',$x'$anst','$ansd',$y)";
			$result = api_sql_query($sql);
			return mysql_insert_id();

		}


	function update_question($qid,$qtype,$caption,$alignment,$answers,$open_ans,$curr_dbname)
	{
      	for($i=0;$i<10;$i++)
		{
		   $k=$i+1;
		   $a.$k= $answers[$i];
		}
		$anst = $answerT;
		$ansd = $answerD;
		$sql = "UPDATE $curr_dbname.questions SET qtype='$qtype',caption='$caption',alignment='$alignment',a1='$answers[0]',a2='$answers[1]',a3='$answers[2]',a4='$answers[3]',a5='$answers[4]',a6='$answers[5]',a7='$answers[6]',a8='$answers[7]',a9='$answers[8]',a10='$answers[9]' WHERE qid='$qid'";
		$result = api_sql_query($sql);
		return mysql_insert_id();
	}


	 function get_question_type($questionid)
	 {
	  $table_question = Database :: get_course_table('questions');
	  $questionid = Database::escape_string($questionid);
	  $sql = "SELECT * FROM $table_question WHERE qid='$questionid'";
			$res=api_sql_query($sql);
			$code=@mysql_result($res,0,'type');
			return($code);
	 }


	 function no_of_question($db_name,$gid)
	 {
	  //$table_question = Database :: get_course_table('questions');
	  $gid = Database::escape_string($gid);
	  $sql = "SELECT * FROM $db_name.questions WHERE gid='$gid'";
			$res=api_sql_query($sql);
			$code=@mysql_num_rows($res);
			return($code);
	 }


	function get_question_data($qid,$curr_dbname)
	{
			$qid = Database::escape_string($qid);
	        $sql = "SELECT * FROM $curr_dbname.questions where qid='$qid'";
			$res=api_sql_query($sql);
			$rs=mysql_fetch_object($res);
			$properties = get_object_vars($rs);
			foreach ($properties as $property=>$val){
				$val = stripslashes($val);
				$rs->$property = $val;
			}
			return $rs;
	 }

	function get_data($id, $field)
	{
		global $_course;
		$sql='SELECT '.$field.' FROM '.$_course['dbName'].'.survey WHERE survey_id='.intval($id);
		$res=api_sql_query($sql);
		$code=@mysql_result($res,0);
		return($code);

	}

	function get_all_datas($id)
	{
		global $_course;
		$sql='SELECT * FROM '.$_course['dbName'].'.survey WHERE survey_id='.intval($id);
		$res=api_sql_query($sql);
		return mysql_fetch_object($res);
	}

	function get_surveyname($db_name,$sid)
	{
			//$surveytable=Database:: get_course_table('survey');
			$sid = Database::escape_string($sid);
			$sql="SELECT * FROM $db_name.survey WHERE survey_id=$sid";
			$res=api_sql_query($sql);
			$code=@mysql_result($res,0,'title');
			return($code);
	}
	
	function get_surveyname_display($sid)
	{
			$sid = Database::escape_string($sid);
			$surveytable=Database:: get_course_table('survey');
			$sql="SELECT * FROM $surveytable WHERE survey_id=$sid";
			$res=api_sql_query($sql);
			$code=@mysql_result($res,0,'title');
			return($code);
	}
	
	/*
	function join_survey($question_type)
		{
		  $table_survey = Database :: get_course_table(TABLE_MAIN_SURVEY);
		  $table_group =  Database :: get_course_table(TABLE_MAIN_GROUP);
		  $table_question = Database :: get_course_table(TABLE_MAIN_SURVEYQUESTION);
		  echo $sql="select t1.title as stitle, t3.type as type, t3.caption as caption, t2.groupname as groupname from $table_survey t1, $table_group t2, $table_question t3  where t1.survey_id=t2.survey_id  and t3.gid=t2.group_id and t3.type='$question_type'";
		  $sql_result = api_sql_query($sql,__FILE__,__LINE__);
		  $result = mysql_fetch_object($sql_result);
		  return ($result);
		  }
		  */

	function import_questions($import_type, $ids)
	{
			//$groupname=surveymanager::get_groupname($gid_arr[$index]);
			switch ($import_type) {
				case "survey":
				{

				}
				case "groups":
				{
				 foreach ($ids as $gid){
				 $sql="insert into $table_question SELECT ('',gid,qtype,caption,a1,a2,a3,a4,a5,a6,a7,a8,a9,a10,at,ad,r1,r2,r3,r4,r5,r6,r7,r8,r9,r10) FROM $table_question where gid=$gid";
				 }
				}
				case "questions":
				{

				}
			}
			$table_question = Database :: get_course_table('questions');
			if(isset($selected_group)){
			 if($selected_group!=''){
			  $sql = "SELECT $table_group('survey_id', 'groupname') values('$sid', '$groupname')";
				$res = api_sql_query($sql);
				$sql = "INSERT INTO $table_group('survey_id', 'groupname') values('$sid', '$groupname')";
				$res = api_sql_query($sql);
				$gid_arr[$index]+= mysql_insert_id();
				$groupids=implode(",",$gid_arr);
			  }
			}

			echo $groupids;
	}

	/**
	 * This function deletes a survey and all the groups and question belonging to it.
	 *
	 * @param unknown_type $survey_id
	 *
	 * @author unknown
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, cleanup and refactoring
	 *
	 */
	function delete_survey($survey_id)
	{
		$table_survey 	= Database :: get_course_table('survey');
		$table_group 	= Database :: get_course_table('survey_group');
		$table_question = Database :: get_course_table('questions');

		$survey_id = Database::escape_string($survey_id);
		
		// Deleting the survey
		$sql = "DELETE FROM $table_survey WHERE survey_id='".$survey_id."'";
		api_sql_query($sql,__FILE__,__LINE__);

		// Deleting all the questions of the survey
		$sql = "SELECT * FROM $table_group WHERE survey_id='".$survey_id."'";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		while($obj = mysql_fetch_object($res))
		{
			$sql = "DELETE FROM $table_question WHERE gid='".$obj->group_id."'";
			api_sql_query($sql,__FILE__,__LINE__);
		}

		// Deleting the groups of the survey
		$sql = "DELETE FROM $table_group WHERE survey_id='".$survey_id."'";
		api_sql_query($sql,__FILE__,__LINE__);
		return true;
	}

	/**
	 * This function deletes a
	 *
	 * @param unknown_type $group_id
	 * @param unknown_type $curr_dbname
	 *
	 * @author unknown
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, refactoring: using correct database calls and removing useless second parameter
	 */
	function delete_group($group_id)
	{
		// Database table definitions
		/** @todo use database constants for the survey tables */
		$table_question 	= Database :: get_course_table('questions');
		$table_survey_group = Database :: get_course_table('survey_group');

		$sql = "DELETE FROM $table_question WHERE gid='".$group_id."'";
		api_sql_query($sql,__FILE__,__LINE__);
		$sql = "DELETE FROM $table_survey_group WHERE group_id='".$group_id."'";
		api_sql_query($sql,__FILE__,__LINE__);
	}


	function ques_id_group_name($qid)
	{
		$ques_table=Database::get_course_table('questions');
		$sql="SELECT gid FROM $ques_table where qid=$qid";
		$res=api_sql_query($sql);
		$id=@mysql_result($res,0,'gid');
		$gname=surveymanager::get_groupname($id);
		return($gname);
	}

	function insert_questions($sid,$newgid,$gid,$table_group)
	{
		$sql_select = "SELECT * FROM $table_group WHERE group_id IN (".$gid.")";
		$res = api_sql_query($sql_select);
		$num = mysql_num_rows($res);
		$i=0;
		while($i<$num)
		{
			$sql_insert = "INSERT INTO $table_group(group_id, survey_id, groupname) values('', '$sid', 'Imported Group')";
			$result = api_sql_query($sql_insert);
			$i++;
		}
	}

	function select_group_list($survey_id, $seleced_groupid='', $extra_script='')
	{
		$group_table = Database :: get_course_table('survey_group');
		$sql = "SELECT * FROM $group_table WHERE survey_id='$survey_id'";
		$sql_result = api_sql_query($sql,__FILE__,__LINE__);
		if(mysql_num_rows($sql_result)>0)
		{
			$str_group_list = "";
			$str_group_list .= "<select name=\"exiztinggroup\" $extra_script>\n";

			while($result=mysql_fetch_array($sql_result))
			{
			 $selected = ($seleced_groupid==$result[group_id])?"selected":"";
			 $str_group_list .= "\n<option value=\"".$result[group_id]."\" ".$selected.">".$result[groupname]."</option>\n";
			}

			$str_group_list .= "</select>";
			return $str_group_list;

		}
		else
		{
			return false;
		}

	}



//For importing the groups to new survey
	function insert_groups($sid,$newgid,$gids,$table_group,$table_question)
		{
			$gid_arr = explode(",",$gids);
			$num = count($gid_arr);


				$queryone = "SELECT *  FROM $table_question WHERE gid = '$newgid'";
				$rs = api_sql_query($queryone);
				$numrs=mysql_num_rows($rs);

             for($k=0;$k<$numrs;$k++)
				{
                  $imp[]=mysql_result($rs,$k,"imported_group");
				}


			$imp=@array_unique($imp);
			for($n=0;$n<count($gid_arr);$n++)
				{
                  if(@in_array($gid_arr[$n],$imp))
					{
				     $gname=surveymanager::get_groupname($gid_arr[$n]);
					 $alr[]=$gname;
					 //$alr[]=$gid_arr[$n];
					 //$unique_arr = @array_unique($alr);
					// print_r($unique_arr);
					}
				}
			//$unique_arr = @array_unique($alr);
			//print_r($unique_arr);

		/*if($msg)
			{

		echo "You have already imported the following group(s)";
		echo "<br>".$msg;

		}*/

			for($index=0;$index<$num;$index++)
			{
				if(@!in_array($gid_arr[$index],$imp))
				{
				$temp_gid = $gid_arr[$index];

				$sql = "SELECT * FROM $table_question WHERE gid = '$temp_gid'";
				$res = api_sql_query($sql);
				$num_rows = mysql_num_rows($res);
				while($obj = mysql_fetch_object($res))
				{
					$temp_qtype = $obj->qtype;
					$temp_caption = $obj->caption;
					$y="";
					$x="";
					for($i=1;$i<=10;$i++)
						{
							$temp = "a".$i;
							$x.= "'".$obj->$temp."',"; /*this variable contains concatenated values and need to be refreshed each time 								before the loop starts!*/
						}

					for($j=1;$j<=10;$j++)
						{

							if($j==10)
								{
									$temps = "r".$j;
									$y.= "'".$obj->$temps."'";
								}
							else
								{
									$temps = "r".$j;
									$y.= "'".$obj->$temps."',";
								}
						}

					$sql_insert = "INSERT INTO  $table_question (qid,gid,qtype,caption,a1,a2,a3,a4,a5,a6,a7,a8,a9,a10,at,ad,r1,r2,r3,r4,r5,r6,r7,r8,r9,r10,imported_group) values('','$newgid','$temp_qtype','$temp_caption',$x'$anst','$ansd',$y,'$temp_gid')";
					$res2 = api_sql_query($sql_insert);
				}

			}

			}

		}




	function display_imported_group($sid,$table_group,$table_question)
	{

		 $sql = "SELECT group_id FROM $table_group WHERE survey_id='$sid'";
		$res = api_sql_query($sql);
		$num = @mysql_num_rows($res);
		//echo "ths is num".$num;
		$parameters = array();
		$displays = array();
		while($obj = @mysql_fetch_object($res))
		{

			$groupid = $obj->group_id;
			$query = "SELECT * FROM $table_question WHERE gid = '$groupid'";
			$result = api_sql_query($query);
		  while($object = @mysql_fetch_object($result))
			{
				$display = array();
				$display[] = '<input type="checkbox" name="course[]" value="'.$object->qid.'"/>';
				$display[] = $object->caption;
				$display[] = $object->qtype;
				$id = $object->gid;
				//echo "THIS IS GROUP NAME ID".$id;
				$gname = surveymanager::get_groupname($id);
				$display[] = $gname;
				$displays[] = $display;
			}
		}
			$table_header[] = array('', false);
			$table_header[] = array(get_lang('Question'), true);
			$table_header[] = array(get_lang('QuestionType'), true);
			$table_header[] = array(get_lang('Group'), true);
			Display :: display_sortable_table($table_header, $displays, array (), array (), $parameters);
	}


function attach_survey($surveyid,$newsurveyid,$db_name,$curr_dbname)
//For attaching the whole survey with its groups and questions
  {
	 $sql = "SELECT *  FROM $db_name.survey_group WHERE survey_id = '$surveyid'";
     $res = api_sql_query($sql,__FILE__,__LINE__);
	 while($obj=@mysql_fetch_object($res))
     {
		 $groupname=addslashes($obj->groupname);
		 $introduction=addslashes($obj->introduction);
	   $sql_insert = "INSERT INTO $curr_dbname.survey_group(group_id,survey_id,groupname,introduction) values('','$newsurveyid','$groupname','$introduction')";
	   $resnext = api_sql_query($sql_insert,__FILE__,__LINE__);
	   $groupid = mysql_insert_id();
	   $sql_q = "SELECT *  FROM $db_name.questions WHERE gid = '$obj->group_id'";
	   $res_q = api_sql_query($sql_q,__FILE__,__LINE__);
       while($obj_q = mysql_fetch_object($res_q))
	   {
		 $caption1=addslashes($obj_q->caption);
		      $a1=addslashes($obj_q->a1);
			  $a2=addslashes($obj_q->a2);
			  $a3=addslashes($obj_q->a3);
			  $a4=addslashes($obj_q->a4);
			  $a5=addslashes($obj_q->a5);
			  $a6=addslashes($obj_q->a6);
			  $a7=addslashes($obj_q->a7);
			  $a8=addslashes($obj_q->a8);
			  $a9=addslashes($obj_q->a9);
			  $a10=addslashes($obj_q->a10);
              $at=addslashes($obj_q->at);
			  $ad=addslashes($obj_q->ad);
			  $r1=addslashes($obj_q->r1);
			  $r2=addslashes($obj_q->r2);
			  $r3=addslashes($obj_q->r3);
			  $r4=addslashes($obj_q->r4);
			  $r5=addslashes($obj_q->r5);
			  $r6=addslashes($obj_q->r6);
			  $r7=addslashes($obj_q->r7);
			  $r8=addslashes($obj_q->r8);
			  $r9=addslashes($obj_q->r9);
			  $r10=addslashes($obj_q->r10);
		 $sql_sort = "SELECT max(sortby) AS sortby FROM $curr_dbname.questions ";
         $res_sort=api_sql_query($sql_sort);
         $rs=mysql_fetch_object($res_sort);
	     $sortby=$rs->sortby;
	     if(empty($sortby))
	     {$sortby=1;}
	     else{$sortby=$sortby+1;}
		 $sql_q_insert = "INSERT INTO $curr_dbname.questions (qid,gid,survey_id,qtype,caption,alignment,sortby,a1,a2,a3,a4,a5,a6,a7,a8,a9,a10,at,ad,r1,r2,r3,r4,r5,r6,r7,r8,r9,r10) values('','$groupid','$newsurveyid','$obj_q->qtype','$caption1','$obj_q->alignment','$sortby','$a1','$a2','$a3','$a4','$a5','$a6','$a7','$a8','$a9','$a10','$at','$ad','$r1','$r2','$r3','$r4','$r5','$r6','$r7','$r8','$r9','$r10')";
	    api_sql_query($sql_q_insert,__FILE__,__LINE__);
	   }
     }
   }


   function update_group($groupid,$surveyid,$groupnamme,$introduction,$curr_dbname)
	{
		$sql = "UPDATE $curr_dbname.survey_group SET group_id='$groupid', survey_id='$surveyid', groupname='$groupnamme', introduction='$introduction' WHERE group_id='$groupid'";
		api_sql_query($sql, __FILE__, __LINE__);
	}

/*
function insert_old_groups($sid,$gids,$table_group,$table_question)
{
	$gid_arr = explode(",",$gids);
	$index = count($gid_arr);
	($gid_arr);
	for($p=0;$p<$index;$p++)
	{
		$sql = "SELECT * FROM $table_group WHERE group_id = '$gid_arr[$p]'";
		$res = api_sql_query($sql);
		$obj = mysql_fetch_object($res);
		$gname = $obj->groupname;
		if($gname=='Default')
		{
			$query = "SELECT * FROM $table_group WHERE survey_id = '$sid' AND groupname = 'Default'";
			$result = api_sql_query($query);
			$object = mysql_fetch_object($result);
			$gid = $object->group_id;
			$sql_def_check = "SELECT * FROM $table_question WHERE gid = '$gid'";
			$res_def_check = api_sql_query($sql_def_check);
			$count_def_check = mysql_num_rows($res_def_check);
			for($ctr=0;$ctr<$count_def_check;$ctr++)
			{
			 $imp[]=mysql_result($res_def_check,$ctr,"imported_group");
			}
			$imp = @array_unique($imp);
			if(!@in_array($gid_arr[$p],$imp))
			{
				$sql_ques = "SELECT * FROM $table_question WHERE gid= '$gid_arr[$p]'";
				$res_ques = api_sql_query($sql_ques);
				$num = mysql_num_rows($res_ques);
				while($obj_ques = mysql_fetch_object($res_ques))
				{
					$temp_qtype = $obj_ques->qtype;
					$temp_caption = $obj_ques->caption;
					$anst = $obj_ques->at;
					$ansd = $obj_ques->ad;
					$y="";
					$x="";
					for($i=1;$i<=10;$i++)
						{
							$temp = "a".$i;
							$x.= "'".$obj_ques->$temp."',";
						}

					for($j=1;$j<=10;$j++)
						{

							if($j==10)
								{
									$temps = "r".$j;
									$y.= "'".$obj_ques->$temps."'";
								}
							else
								{
									$temps = "r".$j;
									$y.= "'".$obj_ques->$temps."',";
								}
						}
					 $sql_ques_insert = "INSERT INTO  $table_question (qid,gid,qtype,caption,a1,a2,a3,a4,a5,a6,a7,a8,a9,a10,at,ad,r1,r2,r3,r4,r5,r6,r7,r8,r9,r10,imported_group) values('','$gid','$temp_qtype','$temp_caption',$x'$anst','$ansd',$y,'$gid_arr[$p]')";
					 $res_ques_insert = api_sql_query($sql_ques_insert);
				}
			}
			else
			{
				$flag = 1;
			}
		}
		else
		{
			$intro = $obj->introduction;
			$sql_check = "SELECT * FROM $table_group WHERE survey_id = '$sid'";
			$res_check = api_sql_query($sql_check);
			$num_check = mysql_num_rows($res_check);
			for($k=0;$k<$num_check;$k++)
				{
                  $imp[]=mysql_result($res_check,$k,"imported_group");
				}
			$imp = @array_unique($imp);
			if(!@in_array($gid_arr[$p],$imp))
			{
			$sql_insert = "INSERT INTO $table_group(group_id,survey_id,groupname,introduction,imported_group) values('','$sid','$gname','$intro','$gid_arr[$p]')";
			$res_insert = api_sql_query($sql_insert);
			$new_gid = mysql_insert_id();
			$sql_ques = "SELECT * FROM $table_question WHERE gid= '$gid_arr[$p]'";
			$res_ques = api_sql_query($sql_ques);
			$num = mysql_num_rows($res_ques);
			while($obj_ques = mysql_fetch_object($res_ques))
			{
				$temp_qtype = $obj_ques->qtype;
				$temp_caption = $obj_ques->caption;
				$anst = $obj_ques->at;
				$ansd = $obj_ques->ad;
				$y="";
				$x="";
				for($i=1;$i<=10;$i++)
					{
						$temp = "a".$i;
						$x.= "'".$obj_ques->$temp."',";
					}

				for($j=1;$j<=10;$j++)
					{

						if($j==10)
							{
								$temps = "r".$j;
								$y.= "'".$obj_ques->$temps."'";
							}
						else
							{
								$temps = "r".$j;
								$y.= "'".$obj_ques->$temps."',";
							}
					}
				 $sql_ques_insert = "INSERT INTO  $table_question (qid,gid,qtype,caption,a1,a2,a3,a4,a5,a6,a7,a8,a9,a10,at,ad,r1,r2,r3,r4,r5,r6,r7,r8,r9,r10,imported_group) values('','$new_gid','$temp_qtype','$temp_caption',$x'$anst','$ansd',$y,'$gid_arr[$p]')";
				 $res_ques_insert = api_sql_query($sql_ques_insert);
			}
		}
		else
		{
		$flag = 1;
		}
		}
	}
	return ($flag);
}
*/

function insert_old_groups($sid,$gids,$table_group,$table_question,$db_name,$cidReq)
{
	$table_course = Database :: get_main_table(TABLE_MAIN_COURSE);
	$sql = "SELECT * FROM $table_course WHERE code = '$cidReq'";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	$obj_name=@mysql_fetch_object($res);
	$current_db_name = $obj_name->db_name ;
	$gid_arr = explode(",",$gids);
	$index = count($gid_arr);
	($gid_arr);
	for($p=0;$p<$index;$p++)
	{
		$sql = "SELECT * FROM $db_name.survey_group WHERE group_id = '$gid_arr[$p]'";
		$res = api_sql_query($sql);
		$obj = mysql_fetch_object($res);
		$gname = $obj->groupname;
		if($gname=='No Group')
		{
			$query = "SELECT * FROM $db_name.survey_group WHERE survey_id = '$sid' AND groupname = 'No Group'";
			$result = api_sql_query($query);
			$object = mysql_fetch_object($result);
			$gid = $object->group_id;
			$sql_def_check = "SELECT * FROM $db_name.questions WHERE gid = '$gid'";
			$res_def_check = api_sql_query($sql_def_check);
			$count_def_check = mysql_num_rows($res_def_check);
			for($ctr=0;$ctr<$count_def_check;$ctr++)
			{
			 $imp[]=mysql_result($res_def_check,$ctr,"imported_group");
			}
			$imp = @array_unique($imp);
			$gid_arr[$p];
			if(!@in_array($gid_arr[$p],$imp))
			{
				$sql_ques = "SELECT * FROM $db_name.questions WHERE gid= '$gid_arr[$p]'";
				$res_ques = api_sql_query($sql_ques);
				$num = mysql_num_rows($res_ques);
				while($obj_ques = mysql_fetch_object($res_ques))
				{
					$temp_qtype = $obj_ques->qtype;
					$temp_caption = $obj_ques->caption;
					$anst = $obj_ques->at;
					$ansd = $obj_ques->ad;
					$y="";
					$x="";
					for($i=1;$i<=10;$i++)
						{
							$temp = "a".$i;
							$x.= "'".$obj_ques->$temp."',"; /*this variable contains concatenated values and need to be refreshed each time 									before the loop starts!*/
						}

					for($j=1;$j<=10;$j++)
						{

							if($j==10)
								{
									$temps = "r".$j;
									$y.= "'".$obj_ques->$temps."'";
								}
							else
								{
									$temps = "r".$j;
									$y.= "'".$obj_ques->$temps."',";
								}
						}
					$sql_ques_insert = "INSERT INTO  $current_db_name.questions (qid,gid,qtype,caption,a1,a2,a3,a4,a5,a6,a7,a8,a9,a10,at,ad,r1,r2,r3,r4,r5,r6,r7,r8,r9,r10,imported_group) values('','$gid','$temp_qtype','$temp_caption',$x'$anst','$ansd',$y,'$gid_arr[$p]')";
					 $res_ques_insert = api_sql_query($sql_ques_insert);
				}
			}
			else
			{
				$flag = 1;
			}
		}
		else
		{
			$intro = $obj->introduction;
			$sql_check = "SELECT * FROM $db_name.survey_group WHERE survey_id = '$sid'";
			$res_check = api_sql_query($sql_check);
			$num_check = mysql_num_rows($res_check);
			for($k=0;$k<$num_check;$k++)
				{
                  $imp[]=mysql_result($res_check,$k,"imported_group");
				}
			$imp = @array_unique($imp);
			if(!@in_array($gid_arr[$p],$imp))
			{
			$sql_insert = "INSERT INTO $current_db_name.survey_group(group_id,survey_id,groupname,introduction,imported_group) values('','$sid','$gname','$intro','$gid_arr[$p]')";
			$res_insert = api_sql_query($sql_insert);
			$new_gid = mysql_insert_id();
			$sql_ques = "SELECT * FROM $db_name.questions WHERE gid= '$gid_arr[$p]'";
			$res_ques = api_sql_query($sql_ques);
			$num = mysql_num_rows($res_ques);
			while($obj_ques = mysql_fetch_object($res_ques))
			{
				$temp_qtype = $obj_ques->qtype;
				$temp_caption = $obj_ques->caption;
				$anst = $obj_ques->at;
				$ansd = $obj_ques->ad;
				$y="";
				$x="";
				for($i=1;$i<=10;$i++)
					{
						$temp = "a".$i;
						$x.= "'".$obj_ques->$temp."',"; /*this variable contains concatenated values and need to be refreshed each time 									before the loop starts!*/
					}

				for($j=1;$j<=10;$j++)
					{

						if($j==10)
							{
								$temps = "r".$j;
								$y.= "'".$obj_ques->$temps."'";
							}
						else
							{
								$temps = "r".$j;
								$y.= "'".$obj_ques->$temps."',";
							}
					}
				 $sql_ques_insert = "INSERT INTO  $current_db_name.questions (qid,gid,qtype,caption,a1,a2,a3,a4,a5,a6,a7,a8,a9,a10,at,ad,r1,r2,r3,r4,r5,r6,r7,r8,r9,r10,imported_group) values('','$new_gid','$temp_qtype','$temp_caption',$x'$anst','$ansd',$y,'$gid_arr[$p]')";
				 $res_ques_insert = api_sql_query($sql_ques_insert);
			}
		}
		else
		{
		$flag = 1;
		}
		}
	}
	return ($flag);
}


function import_question($surveyid,$qids,$table_group,$table_question,$db_name,$cidReq,$yes)
 {
   $table_course = Database :: get_main_table(TABLE_MAIN_COURSE);
   $sql_course = "SELECT * FROM $table_course WHERE code = '$cidReq'";
   $res_course = api_sql_query($sql_course,__FILE__,__LINE__);
   $obj_name=@mysql_fetch_object($res_course);
   $current_db_name = $obj_name->db_name ;
   $qid=explode(",",$qids);
   $count = count($qid);
  for($i=0; $i<$count; $i++)
   {
	 $sql_q = "SELECT *  FROM $table_question WHERE qid = '$qid[$i]'";
     $res_q = api_sql_query($sql_q,__FILE__,__LINE__);
	 $obj=@mysql_fetch_object($res_q);
	 $oldgid=$obj->gid;
	 $sql = "SELECT *  FROM $table_group WHERE group_id = '$oldgid'";
	 $res = api_sql_query($sql,__FILE__,__LINE__);
	 $obj_gr = @mysql_fetch_object($res);
	 $gname = $obj_gr->groupname;
	 $gintro = $obj_gr->introduction;
     $sql_gid = "SELECT *  FROM $table_group WHERE survey_id = '$surveyid' AND groupname = '$gname'";
	 $res_gid = api_sql_query($sql_gid,__FILE__,__LINE__);
	 $num=mysql_num_rows($res_gid);
     $obj_gid=@mysql_fetch_object($res_gid);
	 $sql_quesid = "SELECT *  FROM $table_question WHERE gid = '$obj_gid->group_id' AND caption = '$obj->caption'";
     $res_quesid = api_sql_query($sql_quesid,__FILE__,__LINE__);
     $num_ques=mysql_num_rows($res_quesid);
 if($num_ques>0)
  {
	 $message=1;
     //echo "<div align=\"center\"><strong><font color=\"#FF0000\">Already Imported !</font></strong></div>" ;
  }
 else
    {
  if($num>0 && $yes=="yes")
  {
     $sql_q_insert = "INSERT INTO $current_db_name.questions (qid,gid,qtype,caption,a1,a2,a3,a4,a5,a6,a7,a8,a9,a10,at,ad,r1,r2,r3,r4,r5,r6,r7,r8,r9,r10) values('','$obj_gid->group_id','$obj->qtype','$obj->caption','$obj->a1','$obj->a2','$obj->a3','$obj->a4','$obj->a5','$obj->a6','$obj->a7','$obj->a8','$obj->a9','$obj->a10','$obj->at','$obj->ad','$obj->r1','$obj->r2','$obj->r3','$obj->r4','$obj->r5','$obj->r6','$obj->r7','$obj->r8','$obj->r9','$obj->r10')";
	 api_sql_query($sql_q_insert,__FILE__,__LINE__);
  }
  else
  {
	 $sql_ginsert="INSERT INTO $current_db_name.survey_group(group_id,survey_id,groupname,introduction) values('','$surveyid','$gname','$gintro')";
	 api_sql_query($sql_ginsert,__FILE__,__LINE__);
     $new_gid = mysql_insert_id();
	 $sql_q_insert = "INSERT INTO $current_db_name.questions (qid,gid,qtype,caption,a1,a2,a3,a4,a5,a6,a7,a8,a9,a10,at,ad,r1,r2,r3,r4,r5,r6,r7,r8,r9,r10) values('','$new_gid','$obj->qtype','$obj->caption','$obj->a1','$obj->a2','$obj->a3','$obj->a4','$obj->a5','$obj->a6','$obj->a7','$obj->a8','$obj->a9','$obj->a10','$obj->at','$obj->ad','$obj->r1','$obj->r2','$obj->r3','$obj->r4','$obj->r5','$obj->r6','$obj->r7','$obj->r8','$obj->r9','$obj->r10')";
	 api_sql_query($sql_q_insert,__FILE__,__LINE__);
    }
   }
  }
  return $message;
}

function create_course_survey_rel($cidReq,$survey_id,$table_course,$table_course_survey_rel)
{
 $sql = "SELECT * FROM $table_course WHERE code = '$cidReq'";
 $res = api_sql_query($sql,__FILE__,__LINE__);
 $obj=@mysql_fetch_object($res);
 $db_name = $obj->db_name ;
 $sql="INSERT INTO $table_course_survey_rel(id,course_code,db_name,survey_id) values('','$cidReq','$db_name','$survey_id')";

 api_sql_query($sql,__FILE__,__LINE__);
 return $db_name;
}

function import_existing_question($surveyid,$qids,$table_group,$table_question,$yes)
{
  $qid=explode(",",$qids);
  $count = count($qid);
  for($i=0; $i<$count; $i++)
  {
	 $sql_q = "SELECT *  FROM $table_question WHERE qid = '$qid[$i]'";
     $res_q = api_sql_query($sql_q,__FILE__,__LINE__);
	 $obj=@mysql_fetch_object($res_q);
	 $oldgid=$obj->gid;
	 $sql = "SELECT *  FROM $table_group WHERE group_id = '$oldgid'";
	 $res = api_sql_query($sql,__FILE__,__LINE__);
	 $obj_gr = @mysql_fetch_object($res);
	 $gname = $obj_gr->groupname;
	 $gintro = $obj_gr->introduction;
     $sql_gid = "SELECT *  FROM $table_group WHERE survey_id = '$surveyid' AND groupname = '$gname'";
	 $res_gid = api_sql_query($sql_gid,__FILE__,__LINE__);
	 $num=mysql_num_rows($res_gid);
     $obj_gid=@mysql_fetch_object($res_gid);
	 $sql_quesid = "SELECT *  FROM $table_question WHERE gid = '$obj_gid->group_id' AND caption = '$obj->caption'";
     $res_quesid = api_sql_query($sql_quesid,__FILE__,__LINE__);
     $num_ques=mysql_num_rows($res_quesid);
 if($num_ques>0)
  {
	 $message=1;
     //echo "<div align=\"center\"><strong><font color=\"#FF0000\">Already Imported !</font></strong></div>" ;
  }
 else
    {
  if($num>0 && $yes=="yes")
  {
     $sql_q_insert = "INSERT INTO $table_question (qid,gid,qtype,caption,a1,a2,a3,a4,a5,a6,a7,a8,a9,a10,at,ad,r1,r2,r3,r4,r5,r6,r7,r8,r9,r10) values('','$obj_gid->group_id','$obj->qtype','$obj->caption','$obj->a1','$obj->a2','$obj->a3','$obj->a4','$obj->a5','$obj->a6','$obj->a7','$obj->a8','$obj->a9','$obj->a10','$obj->at','$obj->ad','$obj->r1','$obj->r2','$obj->r3','$obj->r4','$obj->r5','$obj->r6','$obj->r7','$obj->r8','$obj->r9','$obj->r10')";
	 api_sql_query($sql_q_insert,__FILE__,__LINE__);
  }
  else
  {
	 $sql_ginsert="INSERT INTO $table_group(group_id,survey_id,groupname,introduction) values('','$surveyid','$gname','$gintro')";
	 api_sql_query($sql_ginsert,__FILE__,__LINE__);
     $new_gid = mysql_insert_id();
	 $sql_q_insert = "INSERT INTO $table_question (qid,gid,qtype,caption,a1,a2,a3,a4,a5,a6,a7,a8,a9,a10,at,ad,r1,r2,r3,r4,r5,r6,r7,r8,r9,r10) values('','$new_gid','$obj->qtype','$obj->caption','$obj->a1','$obj->a2','$obj->a3','$obj->a4','$obj->a5','$obj->a6','$obj->a7','$obj->a8','$obj->a9','$obj->a10','$obj->at','$obj->ad','$obj->r1','$obj->r2','$obj->r3','$obj->r4','$obj->r5','$obj->r6','$obj->r7','$obj->r8','$obj->r9','$obj->r10')";
	 api_sql_query($sql_q_insert,__FILE__,__LINE__);
    }
   }
  }
  return $message;
}

function insert_existing_groups($sid,$gids,$table_group,$table_question)
  {
	$gid_arr = explode(",",$gids);
	$index = count($gid_arr);
	($gid_arr);
	for($p=0;$p<$index;$p++)
	{
		$sql = "SELECT * FROM $table_group WHERE group_id = '$gid_arr[$p]'";
		$res = api_sql_query($sql);
		$obj = mysql_fetch_object($res);
		$gname = $obj->groupname;
		if($gname=='No Group')
		{
			$query = "SELECT * FROM $table_group WHERE survey_id = '$sid' AND groupname = 'No Group'";
			$result = api_sql_query($query);
			$object = mysql_fetch_object($result);
			$gid = $object->group_id;
			$sql_def_check = "SELECT * FROM $table_question WHERE gid = '$gid'";
			$res_def_check = api_sql_query($sql_def_check);
			$count_def_check = mysql_num_rows($res_def_check);
			for($ctr=0;$ctr<$count_def_check;$ctr++)
			{
			 $imp[]=mysql_result($res_def_check,$ctr,"imported_group");
			}
			$imp = @array_unique($imp);
			if(!@in_array($gid_arr[$p],$imp))
			{
				$sql_ques = "SELECT * FROM $table_question WHERE gid= '$gid_arr[$p]'";
				$res_ques = api_sql_query($sql_ques);
				$num = mysql_num_rows($res_ques);
				while($obj_ques = mysql_fetch_object($res_ques))
				{
					$temp_qtype = $obj_ques->qtype;
					$temp_caption = $obj_ques->caption;
					$anst = $obj_ques->at;
					$ansd = $obj_ques->ad;
					$y="";
					$x="";
					for($i=1;$i<=10;$i++)
						{
							$temp = "a".$i;
							$x.= "'".$obj_ques->$temp."',"; /*this variable contains concatenated values and need to be refreshed each time 									before the loop starts!*/
						}

					for($j=1;$j<=10;$j++)
						{

							if($j==10)
								{
									$temps = "r".$j;
									$y.= "'".$obj_ques->$temps."'";
								}
							else
								{
									$temps = "r".$j;
									$y.= "'".$obj_ques->$temps."',";
								}
						}
					 $sql_ques_insert = "INSERT INTO  $table_question (qid,gid,qtype,caption,a1,a2,a3,a4,a5,a6,a7,a8,a9,a10,at,ad,r1,r2,r3,r4,r5,r6,r7,r8,r9,r10,imported_group) values('','$gid','$temp_qtype','$temp_caption',$x'$anst','$ansd',$y,'$gid_arr[$p]')";
					 $res_ques_insert = api_sql_query($sql_ques_insert);
				}
			}
			else
			{
				$flag = 1;
			}
		}
		else
		{
			$intro = $obj->introduction;
			$sql_check = "SELECT * FROM $table_group WHERE survey_id = '$sid'";
			$res_check = api_sql_query($sql_check);
			$num_check = mysql_num_rows($res_check);
			for($k=0;$k<$num_check;$k++)
				{
                  $imp[]=mysql_result($res_check,$k,"imported_group");
				}
			$imp = @array_unique($imp);
			if(!@in_array($gid_arr[$p],$imp))
			{
			$sql_insert = "INSERT INTO $table_group(group_id,survey_id,groupname,introduction,imported_group) values('','$sid','$gname','$intro','$gid_arr[$p]')";
			$res_insert = api_sql_query($sql_insert);
			$new_gid = mysql_insert_id();
			$sql_ques = "SELECT * FROM $table_question WHERE gid= '$gid_arr[$p]'";
			$res_ques = api_sql_query($sql_ques);
			$num = mysql_num_rows($res_ques);
			while($obj_ques = mysql_fetch_object($res_ques))
			{
				$temp_qtype = $obj_ques->qtype;
				$temp_caption = $obj_ques->caption;
				$anst = $obj_ques->at;
				$ansd = $obj_ques->ad;
				$y="";
				$x="";
				for($i=1;$i<=10;$i++)
					{
						$temp = "a".$i;
						$x.= "'".$obj_ques->$temp."',"; /*this variable contains concatenated values and need to be refreshed each time 									before the loop starts!*/
					}

				for($j=1;$j<=10;$j++)
					{

						if($j==10)
							{
								$temps = "r".$j;
								$y.= "'".$obj_ques->$temps."'";
							}
						else
							{
								$temps = "r".$j;
								$y.= "'".$obj_ques->$temps."',";
							}
					}
				 $sql_ques_insert = "INSERT INTO  $table_question (qid,gid,qtype,caption,a1,a2,a3,a4,a5,a6,a7,a8,a9,a10,at,ad,r1,r2,r3,r4,r5,r6,r7,r8,r9,r10,imported_group) values('','$new_gid','$temp_qtype','$temp_caption',$x'$anst','$ansd',$y,'$gid_arr[$p]')";
				 $res_ques_insert = api_sql_query($sql_ques_insert);
			}
		}
		else
		{
		$flag = 1;
		}
		}
	}
	return ($flag);
 }

 function pick_surveyname($sid)
		{
			$surveytable=Database:: get_course_table('survey');
			$sql="SELECT * FROM $surveytable WHERE survey_id=$sid";
			$res=api_sql_query($sql);
			$code=@mysql_result($res,0,'title');
			return($code);
		}

function pick_author($survey_id)
	{
	    $survey_table = Database :: get_course_table('survey');
		$sql = "SELECT author FROM $survey_table WHERE survey_id='$survey_id'";
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$author=@mysql_result($res,0,'author');
		return $author;
	}

function question_import($surveyid,$qids,$db_name,$curr_dbname)
 {
  $qid=explode(",",$qids);
  $count = count($qid);
  for($i=0; $i<$count; $i++)
   {
	 $sql_sort = "SELECT max(sortby) AS sortby FROM $curr_dbname.questions ";
     $res_sort=api_sql_query($sql_sort);
     $rs=mysql_fetch_object($res_sort);
	 $sortby=$rs->sortby;
	 if(empty($sortby))
	 {$sortby=1;}
	 else{$sortby=$sortby+1;}
	 $sql_q = "SELECT * FROM $db_name.questions WHERE qid = '$qid[$i]'";
     $res_q = api_sql_query($sql_q,__FILE__,__LINE__);
	 $obj=@mysql_fetch_object($res_q);
	 $oldgid=$obj->gid;
	 $caption1=addslashes($obj->caption);
	  $a1=addslashes($obj->a1);
			  $a2=addslashes($obj->a2);
			  $a3=addslashes($obj->a3);
			  $a4=addslashes($obj->a4);
			  $a5=addslashes($obj->a5);
			  $a6=addslashes($obj->a6);
			  $a7=addslashes($obj->a7);
			  $a8=addslashes($obj->a8);
			  $a9=addslashes($obj->a9);
			  $a10=addslashes($obj->a10);
              $at=addslashes($obj->at);
			  $ad=addslashes($obj->ad);
			  $r1=addslashes($obj->r1);
			  $r2=addslashes($obj->r2);
			  $r3=addslashes($obj->r3);
			  $r4=addslashes($obj->r4);
			  $r5=addslashes($obj->r5);
			  $r6=addslashes($obj->r6);
			  $r7=addslashes($obj->r7);
			  $r8=addslashes($obj->r8);
			  $r9=addslashes($obj->r9);
			  $r10=addslashes($obj_q->r10);
     //$sql_gr = "SELECT * FROM $db_name.survey_group WHERE group_id = '$oldgid'";
     //$res_gr = api_sql_query($sql_gr,__FILE__,__LINE__);
	 // $obj_gr=@mysql_fetch_object($res_gr);
	 //$groupname = $obj_gr->groupname
	 $sql_quesid = "SELECT *  FROM $curr_dbname.questions WHERE survey_id = '$surveyid' AND imported_question = '$qid[$i]' AND db_name = '$db_name'";
     $res_quesid = api_sql_query($sql_quesid,__FILE__,__LINE__);
     $num_ques=mysql_num_rows($res_quesid);
	if($num_ques>0)
     {
	  $message=1;
     }
	else
	 {
	  $sql_group = "SELECT * FROM $db_name.survey_group WHERE group_id = '$oldgid'";
	  $res_group = api_sql_query($sql_group,__FILE__,__LINE__);
	  $obj_group=@mysql_fetch_object($res_group);
	  $groupname = $obj_group->groupname;
	  $sql = "SELECT *  FROM $curr_dbname.survey_group WHERE groupname = '$groupname' AND survey_id = '$surveyid'";
	  $res = api_sql_query($sql,__FILE__,__LINE__);
      $obj_gro = mysql_fetch_object($res);
	   $num_group=mysql_num_rows($res);
	  if($num_group>0)
	   {
	   $sql_q_insert = "INSERT INTO $curr_dbname.questions (qid,gid,survey_id,qtype,caption,alignment,sortby,a1,a2,a3,a4,a5,a6,a7,a8,a9,a10,at,ad,r1,r2,r3,r4,r5,r6,r7,r8,r9,r10,imported_question,db_name) values('','$obj_gro->group_id','$surveyid','$obj->qtype','$caption1','$obj->alignment','$sortby','$a1','$a2','$a3','$a4','$a5','$a6','$a7','$a8','$a9','$a10','$at','$ad','$r1','$r2','$r3','$r4','$r5','$r6','$r7','$r8','$r9','$r10','$qid[$i]','$db_name')";
	    api_sql_query($sql_q_insert,__FILE__,__LINE__);
	   }
	  else
	   {
		 //$num_group;
      $sql_ginsert="INSERT INTO $curr_dbname.survey_group(group_id,survey_id,groupname,introduction,imported_group, db_name) values('','$surveyid','$groupname','$obj_group->introduction','$oldgid','$db_name')";
	    api_sql_query($sql_ginsert,__FILE__,__LINE__);
        $new_gid = mysql_insert_id();
      $sql_q_insert = "INSERT INTO $curr_dbname.questions (qid,gid,survey_id,qtype,caption,alignment,sortby,a1,a2,a3,a4,a5,a6,a7,a8,a9,a10,at,ad,r1,r2,r3,r4,r5,r6,r7,r8,r9,r10,imported_question,db_name) values('','$new_gid','$surveyid','$obj->qtype','$caption1','$obj->alignment','$sortby','$a1','$a2','$a3','$a4','$a5','$a6','$a7','$a8','$a9','$a10','$at','$ad','$r1','$r2','$r3','$r4','$r5','$r6','$r7','$r8','$r9','$r10','$qid[$i]','$db_name')";
	    api_sql_query($sql_q_insert,__FILE__,__LINE__);
       }
     }
  }
  return $message;
}

/*
function import_group($surveyid,$gids,$db_name,$curr_dbname)
  {
	$gid_arr = explode(",",$gids);
	$index = count($gid_arr);
	for($i=0;$i<$index;$i++)
	{
		$sql = "SELECT * FROM $db_name.survey_group WHERE group_id = '$gid_arr[$i]'";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$obj = mysql_fetch_object($res);
		$sql_ques = "SELECT * FROM $db_name.questions WHERE gid = '$gid_arr[$i]'";
		$res_ques = api_sql_query($sql_ques,__FILE__,__LINE__);
		$obj_ques = mysql_fetch_object($res_ques);
		$sql_check = "SELECT * FROM $curr_dbname.survey_group WHERE survey_id = '$surveyid' AND imported_group = '$gid_arr[$i]' AND db_name = '$db_name'";
		$res_check = api_sql_query($sql_check);
		$obj_check = mysql_fetch_object($res_check);
		$num = mysql_num_rows($res_check);
		if($num>0)
		{
			$sql_question = "SELECT * FROM $curr_dbname.questions WHERE survey_id='$surveyid' AND imported_question = '$obj_ques->qid' AND db_name = '$db_name'";
			$res_question = api_sql_query($sql_question,__FILE__,__LINE__);
			$num_ques = mysql_num_rows($res_question);
			if($num_ques>0)
			{
				$message=1;
			}
	      else
           {
			$sql_insert_ques =  "INSERT INTO $curr_dbname.questions (qid,gid,qtype,caption,a1,a2,a3,a4,a5,a6,a7,a8,a9,a10,at,ad,r1,r2,r3,r4,r5,r6,r7,r8,r9,r10,imported_question,db_name) values('','$obj_check->group_id','$surveyid','$obj_ques->qtype','$obj_ques->caption','$obj_ques->a1','$obj_ques->a2','$obj_ques->a3','$obj_ques->a4','$obj_ques->a5','$obj_ques->a6','$obj_ques->a7','$obj_ques->a8','$obj_ques->a9','$obj_ques->a10','$obj_ques->at','$obj_ques->ad','$obj_ques->r1','$obj_ques->r2','$obj_ques->r3','$obj_ques->r4','$obj_ques->r5','$obj_ques->r6','$obj_ques->r7','$obj_ques->r8','$obj_ques->r9','$obj_ques->r10','$obj_ques->qid','$db_name')";
			api_sql_query($sql_insert_ques);
		   }
		}
		else
		 {
			$insert_group = "INSERT INTO $curr_dbname.survey_group (group_id,survey_id,groupname,introduction,imported_group,db_name) values('','$surveyid','$obj->groupname','$obj->introduction','$obj->group_id','$db_name')";
			$res_insert_group=api_sql_query($insert_group);
            $new_gid = mysql_insert_id();
			$sql_insert_grp =  "INSERT INTO $curr_dbname.questions (qid,gid,qtype,caption,a1,a2,a3,a4,a5,a6,a7,a8,a9,a10,at,ad,r1,r2,r3,r4,r5,r6,r7,r8,r9,r10,imported_question,db_name) values('','$new_gid','$surveyid','$obj_ques->qtype','$obj_ques->caption','$obj_ques->a1','$obj_ques->a2','$obj_ques->a3','$obj_ques->a4','$obj_ques->a5','$obj_ques->a6','$obj_ques->a7','$obj_ques->a8','$obj_ques->a9','$obj_ques->a10','$obj_ques->at','$obj_ques->ad','$obj_ques->r1','$obj_ques->r2','$obj_ques->r3','$obj_ques->r4','$obj_ques->r5','$obj_ques->r6','$obj_ques->r7','$obj_ques->r8','$obj_ques->r9','$obj_ques->r10','$obj_ques->qid','$db_name')";
			api_sql_query($sql_insert_grp);
		 }
	  }
	return $message;
  }

*/


function import_group($sid,$gids,$db_name,$curr_dbname)
{
	$gid_arr = explode(",",$gids);
	$index = count($gid_arr);
	for($i=0;$i<$index;$i++)
	{
		$sql = "SELECT * FROM $db_name.survey_group WHERE group_id = '$gid_arr[$i]'";
		$res = api_sql_query($sql);
		$obj = mysql_fetch_object($res);
		$groupname=addslashes($obj->groupname);
		$introduction=addslashes($obj->introduction);
		$g_sortby = intval($obj->sortby);
		$sql_curr = "SELECT * FROM $curr_dbname.survey_group WHERE survey_id = '$sid' AND groupname = '$obj->groupname'";
		$res_curr = api_sql_query($sql_curr);
		$obj_curr = mysql_fetch_object($res_curr);
		$gid = $obj_curr->group_id;
		$num = mysql_num_rows($res_curr);

		if($num>0) //the group name exists and the questions will be imported in this group.
		{
			$sql_ques = "SELECT * FROM $curr_dbname.questions WHERE gid = '$gid'";
			$res_ques = api_sql_query($sql_ques);
			$obj_ques = mysql_fetch_object($res_ques);
			$count = mysql_num_rows($res_ques);
			for($j=0;$j<$count;$j++)
			{
				$check_qid[] = mysql_result($res_ques,$j,"imported_question");
				$check_db[] = mysql_result($res_ques,$j,"db_name");
			}
			$check_qid = @array_unique($check_qid);
			$check_db = @array_unique($check_db);
			$sql_old = "SELECT * FROM $db_name.questions WHERE gid = '$gid_arr[$i]'";
			$res_old = api_sql_query($sql_old);
			while($obj_old = mysql_fetch_object($res_old))
			{
			  $caption1=addslashes($obj_old->caption);
	          $a1=addslashes($obj_old->a1);
			  $a2=addslashes($obj_old->a2);
			  $a3=addslashes($obj_old->a3);
			  $a4=addslashes($obj_old->a4);
			  $a5=addslashes($obj_old->a5);
			  $a6=addslashes($obj_old->a6);
			  $a7=addslashes($obj_old->a7);
			  $a8=addslashes($obj_old->a8);
			  $a9=addslashes($obj_old->a9);
			  $a10=addslashes($obj_old->a10);
              $at=addslashes($obj_old->at);
			  $ad=addslashes($obj_old->ad);
			  $r1=addslashes($obj_old->r1);
			  $r2=addslashes($obj_old->r2);
			  $r3=addslashes($obj_old->r3);
			  $r4=addslashes($obj_old->r4);
			  $r5=addslashes($obj_old->r5);
			  $r6=addslashes($obj_old->r6);
			  $r7=addslashes($obj_old->r7);
			  $r8=addslashes($obj_old->r8);
			  $r9=addslashes($obj_old->r9);
			  $r10=addslashes($obj_old->r10);
				$sql_sort = "SELECT max(sortby) AS sortby FROM $curr_dbname.questions ";
                $res_sort=api_sql_query($sql_sort);
                $rs=mysql_fetch_object($res_sort);
	            $sortby=$rs->sortby;
	            if(empty($sortby))
	            {$sortby=1;}
	            else{$sortby=$sortby+1;}
				if(@in_array($obj_old->qid,$check_qid)&&@in_array($db_name,$check_db))  //the question has already been imported
				{
					$flag=1;
					continue;
				}
				else
				{
					$sql_insertq = "INSERT INTO $curr_dbname.questions (qid, gid, survey_id, qtype, caption, alignment, sortby, a1, a2, a3, a4, a5, a6, a7, a8, a9, a10, at, ad, alt_text, r1, r2, r3, r4, r5, r6, r7, r8, r9, r10, imported_question, db_name) VALUES('', '$gid', '$sid', '$obj_old->qtype', '$caption1', '$obj_old->alignment', '$sortby', '$a1', '$a2', '$a3', '$a4', '$a5', '$a6', '$a7', '$a8', '$a9', '$a10', '$at', '$ad', '$alt_text', '$r1', '$r2', '$r3', '$r4', '$r5', '$r6', '$r7', '$r8', '$r9', '$r10', '$obj_old->qid', '$db_name')";
					api_sql_query($sql_insertq);
				}
			}
		}
		else  //the groupname does not exist. Create group with this name and insert questions in this new group.
		{

			$sql_insertg = "INSERT INTO $curr_dbname.survey_group (group_id, survey_id, groupname, introduction, imported_group, db_name, sortby) VALUES ('', '$sid', '$groupname', '$introduction', '$obj->group_id', '$db_name', $g_sortby)";
			api_sql_query($sql_insertg);
			$group_id = mysql_insert_id();
			$sql_old = "SELECT * FROM $db_name.questions WHERE gid = '$gid_arr[$i]'";
			$res_old = api_sql_query($sql_old);
			while($obj_old = mysql_fetch_object($res_old))
			{
			  $caption1=addslashes($obj_old->caption);
	          $a1=addslashes($obj_old->a1);
			  $a2=addslashes($obj_old->a2);
			  $a3=addslashes($obj_old->a3);
			  $a4=addslashes($obj_old->a4);
			  $a5=addslashes($obj_old->a5);
			  $a6=addslashes($obj_old->a6);
			  $a7=addslashes($obj_old->a7);
			  $a8=addslashes($obj_old->a8);
			  $a9=addslashes($obj_old->a9);
			  $a10=addslashes($obj_old->a10);
              $at=addslashes($obj_old->at);
			  $ad=addslashes($obj_old->ad);
			  $r1=addslashes($obj_old->r1);
			  $r2=addslashes($obj_old->r2);
			  $r3=addslashes($obj_old->r3);
			  $r4=addslashes($obj_old->r4);
			  $r5=addslashes($obj_old->r5);
			  $r6=addslashes($obj_old->r6);
			  $r7=addslashes($obj_old->r7);
			  $r8=addslashes($obj_old->r8);
			  $r9=addslashes($obj_old->r9);
			  $r10=addslashes($obj_old->r10);
				$sql_sort = "SELECT max(sortby) AS sortby FROM $curr_dbname.questions ";
                $res_sort=api_sql_query($sql_sort);
                $rs=mysql_fetch_object($res_sort);
	            $sortby=$rs->sortby;
	            if(empty($sortby))
	            {$sortby=1;}
	            else{$sortby=$sortby+1;}
				$sql_insertq = "INSERT INTO $curr_dbname.questions (qid, gid, survey_id, qtype, caption, alignment, sortby, a1, a2, a3, a4, a5, a6, a7, a8, a9, a10, at, ad, alt_text, r1, r2, r3, r4, r5, r6, r7, r8, r9, r10, imported_question, db_name) VALUES('', '$group_id', '$sid', '$obj_old->qtype', '$caption1', '$obj_old->alignment', '$sortby', '$a1', '$a2', '$a3', '$a4', '$a5', '$a6', '$a7', '$a8', '$a9', '$a10', '$at', '$ad', '$obj_old->alt_text', '$r1', '$r2', '$r3', '$r4', '$r5', '$r6', '$r7', '$r8', '$r9', '$r10', '$obj_old->qid', '$db_name')";
				api_sql_query($sql_insertq);
			}
		}
	}

	return ($flag);
}



/**
 * Enter description here...
 *
 * @return unknown
 * @todo remove this function because this is of no use and should not be used.
 */
function get_status()
{
	global $_user;

	$table_user = Database::get_main_table(TABLE_MAIN_USER);
	$sqlm = "SELECT  status FROM  $table_user WHERE user_id = '".mysql_real_escape_string($_user['user_id'])."'";
	$resm = api_sql_query($sqlm,__FILE__,__LINE__);
	$objm=@mysql_fetch_object($resm);
	$ss = $objm->status ;
	return $ss;
}



function move_question($direction,$qid,$sort,$curr_dbname)
{

	$questions=SurveyManager::get_questions_move($curr_dbname);

	foreach ($questions as $key=>$value)
		{

		if ($qid==$value['qid'])
			{
			$source_course=$value;
			if ($direction=="up")
				{
				  $target_course=$questions[$key-1];
			    }
			else
				{$target_course=$questions[$key+1];}
			 }
		}
	$sql_update1="UPDATE $curr_dbname.questions SET sortby='".$target_course['sortby']."' WHERE qid='".$source_course['qid']."'";
	$sql_update2="UPDATE $curr_dbname.questions SET sortby='".$source_course['sortby']."' WHERE qid='".$target_course['qid']."'";
	mysql_query($sql_update2);
	mysql_query($sql_update1);
	//return ;
}

function get_questions_move($curr_dbname)
{
	$sql_select_questions="SELECT  * from $curr_dbname.questions order by `sortby` asc";
	$result=mysql_query($sql_select_questions);
	while ($row=mysql_fetch_array($result))
		{
		// we only need the database name of the course
		$question1[]=array("caption"=> $row['caption'], "qid" => $row['qid'],"sortby" => $row['sortby']);
		}
	return $question1;
}

function listGroups($id_survey, $fields = '*')
{
	$groups_table = Database :: get_course_table(TABLE_MAIN_GROUP);
	$sql = 'SELECT '.$fields.' FROM '.$groups_table.'
			WHERE survey_id='.$id_survey.' ORDER BY sortby';
	$rs = api_sql_query($sql, __FILE__, __LINE__);
	$groups = array();
	while($row = mysql_fetch_array($rs)){
		$groups[] = $row;
	}
	return $groups;
}

function listQuestions($id_survey, $fields = '*')
{

	$questions_table = Database :: get_course_table(TABLE_MAIN_SURVEYQUESTION);
	$groups_table = Database :: get_course_table('survey_group');

	$sql = 'SELECT '.$fields.'
			FROM '.$questions_table.' questions
			INNER JOIN '.$groups_table.' groups
				ON questions.gid = groups.group_id
			WHERE questions.survey_id='.$id_survey.'
			ORDER BY groups.sortby, questions.sortby';

	$rs = api_sql_query($sql, __FILE__, __LINE__);

	$questions = array();
	while($row = mysql_fetch_array($rs)){
		$questions[] = $row;
	}

	return $questions;

}

function listAnswers($qid){

	$answers_table = Database :: get_course_table('survey_report');

	$sql = 'SELECT DISTINCT answer FROM '.$answers_table.'
			WHERE qid='.$qid;

	$rs = api_sql_query($sql, __FILE__, __LINE__);

	$answers = array();
	while($row = mysql_fetch_array($rs)){
		$answers[] = $row;
	}

	return $answers;
}


function listUsers($survey_id, $dbname, $fields='id, user_id, firstname, lastname, email, organization') {

	$tbl_survey_users = Database :: get_main_table(TABLE_MAIN_SURVEY_USER);
	$sql = 'SELECT '.$fields.' FROM '.$tbl_survey_users.'
			WHERE survey_id='.$survey_id.'
			AND db_name="'.$dbname.'"
			ORDER BY lastname, firstname ';

	$rs = api_sql_query($sql, __FILE__, __LINE__);
	$users = array();
	while($row = mysql_fetch_array($rs))
		$users[] = $row;

	return $users;

}

function getUserAnswersDetails($id_userAnswers, $params=''){

	$table_answers = Database :: get_main_table(TABLE_MAIN_SURVEY_USER);
	$sql = 'SELECT * FROM '.$table_answers.' '.$where.' '.$order;
	$rs = api_sql_query($sql, __FILE__, __LINE__);
	$answers = array();
	while($row = mysql_fetch_array($rs))
		$answers[] = $row;

	return $answers;
}

}


/**
 * 
 * Manage the "versioning" of a conditional survey
 * 
 * */
class SurveyTree
{
	var $surveylist;	 
	var $plainsurveylist;
	var $numbersurveys;
	
	/**
	 * Sets the surveylist and the plainsurveylist
	 */
	function __construct()
    {        
        // Database table definitions
		$table_survey 			= Database :: get_course_table(TABLE_SURVEY);
		$table_survey_question 	= Database :: get_course_table(TABLE_SURVEY_QUESTION);
		$table_user 			= Database :: get_main_table(TABLE_MAIN_USER);
	
		// searching
		$search_restriction = SurveyUtil::survey_search_restriction();
		if ($search_restriction) {
			$search_restriction = ' AND '.$search_restriction;
		}
		
		$sql = "SELECT survey.survey_id , survey.parent_id, survey_version, survey.code as name
		FROM $table_survey survey 
		LEFT JOIN  $table_survey_question  survey_question 
		ON survey.survey_id = survey_question.survey_id , $table_user user 
		WHERE survey.author = user.user_id 
		GROUP BY survey.survey_id";
		
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$surveys_parents = array ();			
		$refs = array();
		$list = array();
		$last=array();		
		$plain_array=array();
					
		while ($survey = Database::fetch_array($res,'ASSOC'))
		{
			$plain_array[$survey['survey_id']]=$survey;
			$surveys_parents[]=$survey['survey_version'];			
			$thisref = &$refs[ $survey['survey_id'] ];		
			$thisref['parent_id'] = $survey['parent_id'];
			$thisref['name'] = $survey['name'];
			$thisref['id'] = $survey['survey_id'];
			$thisref['survey_version'] = $survey['survey_version'];						
			if ($survey['parent_id'] == 0) 
			{
				$list[ $survey['survey_id'] ] = &$thisref;					
			} 
			else 
			{
				$refs[ $survey['parent_id'] ]['children'][ $survey['survey_id'] ] = &$thisref;				
			}			
		}			
        $this->surveylist = $list;
        $this->plainsurveylist = $plain_array;         
    }
    /* 
    function read_children($space=array(),$i=0)
	{			
		foreach ( $this->surveylist  as $key=>$node)
		{
			if (is_array($node['children']))
			{				
				if (($node['parent_id']==0))
				{					
					//echo '1<br>';		
				}
				else
				{
					$space[]='2>'; 
				}
				
				//if have children										
				for ($j=0;$j<count($space);$j++)
				{
					//echo $space[$j]; echo 'dd<br>';
				}
				//echo $node['name']; echo '3<br>';
				read_children($node['children'],$space,$i);										
			}
			else
			{				
				
				for ($j=0;$j<count($space);$j++)
				{
					echo $space[$j]; echo '4<br>';
				}				
				echo $node['name']; echo '5<br>';
				
			}
		}		
	}
	*/
	/*
	if (($key==$id))
			{
				echo $node['name']; echo '<br>';
				//$node['children']=array();
				return $node['children']; 
			}
			else
			{ 
				if (is_array($node['children']))
				{	
					return SurveyTree::get_children($node['children'],$id);
				}
				else
				{
					//return SurveyTree::get_children($node, $id);
				}
			}	
	 */

	/**
	 * This function gets all the children of a given survey id
	 *
	 * @param  array the list where we are going to search the children
	 * @param  id the id of the survey
	 * @return array the children of a given survey id
	 *
	 * @author Julio Montoya <gugli100@gmail.com>, Dokeos 
	 * @version September 2008
	 */
	function get_children($list,$id)
	{			
		$result=array();
		foreach ($list as $key=>$node)
		{
			if ($key==$id)
			{
				$result = $node['children'];						
				break;																							
			}			
			if (is_array($node['children']))
			{		
				//echo $key; echo '--<br>';					
				$re=SurveyTree::get_children($node['children'],$id);
				if (!empty($re))
				{
					$result=$re;
				}																									
			}
			else
			{		
				//echo $key; echo '-<br>';				
			}			
		}
		return $result;
	}
		
	/**
	 * This function gets the parent id of a survey
	 *
	 * @param  int survey id
	 * @return int survey parent id
	 *
	 * @author Julio Montoya <gugli100@gmail.com>, Dokeos 
	 * @version September 2008
	 */
	function getParentId($id)
	{	
		$node = $this->plainsurveylist[$id];
		if (is_array($node)&& !empty($node['parent_id']))
			return $node['parent_id'];
		else
			return -1;			
	}
	
	/**
	 * This function gets all the siblings of a given survey id
	 *
	 * @param  array the list where we are going to search the children
	 * @param  id the id of the survey
	 * @return array the children of a given survey id
	 *
	 * @author Julio Montoya <gugli100@gmail.com>, Dokeos 
	 * @version September 2008
	 */
	function nextSibling($id)
    {
		$result=array();
		$parent_id = SurveyTree::getParentId($id);			
		$siblings  = SurveyTree::get_children($this->surveylist ,$parent_id);
		//print_r($siblings);  
		if (count($siblings) > 1) 
		{			
			// $key> $id means that the siblings are order 1 2 3 and we suppose that you can't change that order 
			foreach ($siblings as $key=>$bro)
			{
				if ($key> $id && $key != $id)
				{
					$result[$key]=($bro);
				}					
			}				
		}
		return $result;		
    }
    
	/**
	 * 
	 * This function shows the last sibling from a given list of surveys
	 * @param  id of the survey
	 * @author Julio Montoya <gugli100@gmail.com>, Dokeos 
	 * @version September 2008
	 * 
	 */
	function lastSibling($id)
    {	
		$result=array();
		$parent_id = SurveyTree::getParentId($id);			
		$siblings  = SurveyTree::get_children($this->surveylist ,$parent_id);
		//print_r($siblings);  
		if (count($siblings) > 1) 
		{			
			// $key> $id means that the siblings are order 1 2 3 and we suppose that you can't change that order
			$i=0; 
			foreach ($siblings as $key=>$bro)
			{
				if ($key> $id && $key != $id&& $i==count($siblings)-1)
				{
					$result[$key]=($bro);
				}		
				$i++;			
			}				
		}
		return $result;		
    }
    
    
	/**
	 * 
	 * This function shows the last children of a branch 
	 * @param  list of nodes
	 * @return array of the lastest node
	 * @author Julio Montoya <gugli100@gmail.com>, Dokeos 
	 * @version September 2008
	 * 
	 */
	function get_last_children_from_branch($list)
	{	
		$result=array();		
		foreach ($list as $key=>$node)
		{			 
			//echo 'frist<br>'; echo $key;
			//print_r($node);
			if ($node['parent_id']!=0)
			{				
				$list_bros = SurveyTree::lastSibling($key);				
				//echo '   list_bro <br>';	
				//print_r($list_bros);
				if (is_array($list_bros) && !empty($list_bros))					
				{
					foreach	($list_bros as $bro)
					{					
						//echo '0';						
						if (is_array($bro['children']))
						{
							//print_r($bro['children']);							
							return $result[]=SurveyTree::get_last_children_from_branch($bro['children']);
						}
						else
						{	
							//echo 'esl';		
							$result=$bro;
							//print_r($bro);
							return $result;
						}
					}
				}
				else
				{
					//SurveyTree::get_last_children_from_branch($list_bros);
					//echo 'sss';
					//if if (is_array($node['children']))
					$children = SurveyTree::get_children($node,$key);
					//return $result[]=SurveyTree::get_last_children_from_branch($node);
					if (is_array($node['children']))
					{
						return $result[]=SurveyTree::get_last_children_from_branch($node['children']);
					}
					else											
					{					
						//return $result[]=SurveyTree::get_last_children_from_branch($node['children']);					
						return $result[]=$node;						
					}
		
				}
			}
			else
			{
				//print_r($key);print_r($node['children']);
				if (is_array($node['children']))
				{
					$result[]=SurveyTree::get_last_children_from_branch($node['children']);
				}
				else	
				{
					$result[]=$node;					
				}
			}	
		}
		return $result;
	}
	/*
*
	 * 
	 * This function show the last children of list 
	 * @param  id
	 * @param  array 
	 * @return array of the lastest node
	 * @author Julio Montoya <gugli100@gmail.com>, Dokeos 
	 * @version September 2008
	 * 

	 
	function get_last_children($id,$last)
	{		
		foreach ( $this->_list as $key=>$node)
		{
			if (($node['parent_id']==$id))
			{
				$last=$node['name']; echo '<br>';				
			}
			else
			{
				if (is_array($node['children']))
				{
					return $last = get_last_children($node['children'],$id);	
				}			
			}	
		}
		return $last;
	}
	*/
	/**
	 * This function creates a list of all surveys id 
	 * @param  list of nodes
	 * @return array with the structure survey_id => survey_name 
	 * @author Julio Montoya <gugli100@gmail.com>, Dokeos 
	 * @version September 2008
	 * 
	 */
	function createList($list)
	{
		$result=array();				
		foreach ($list as $key=>$node)
		{			
			if (is_array($node['children']))
			{		
				//echo $key; echo '--<br>';
				//print_r($node);
				//echo '<br>';
				$result[$key]= $node['name'];		
				$re=SurveyTree::createList($node['children']);
				if (!empty($re))
				{				
					if (is_array($re))
						foreach ($re as $key=>$r)
						{						
							$result[$key]=''.$r;
						}
					else				
					{
						$result[]=$re;
					}
					
				}																									
			}
			else
			{		
				//echo $key; echo '-<br>';
				$result[$key]=$node['name'];			
			}			
		}			
		return $result;
	}	
}
?>
