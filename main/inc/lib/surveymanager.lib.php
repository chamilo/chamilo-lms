<?php
/* For licensing terms, see /license.txt */
/**
*	This library provides functions for user management.
*	Include/require it in your code to use its functionality.
*
*	@author Bart Mollet, main author
*	@package chamilo.library

*/
//@todo This class is deprecated
/*
class SurveyManager {
	private function __construct() {

	}
	//Possible  deprecated method
	function get_survey_author($authorid)
	{
			$user_table = Database :: get_main_table(TABLE_MAIN_USER);
			$authorid = Database::escape_string($authorid);
		    $sql_query = "SELECT * FROM $user_table WHERE user_id='$authorid'";
			$res = Database::query($sql_query);
			$firstname=@Database::result($res,0,'firstname');
			return $firstname;
	}

	//Possible  deprecated method
	function get_author($db_name,$survey_id)
	{
	    //$table_survey = Database :: get_course_table(TABLE_SURVEY);
	    $survey_id = Database::escape_string($survey_id);
		$sql = "SELECT author FROM $db_name.survey WHERE survey_id='$survey_id'";
		$res = Database::query($sql);
		$author=@Database::result($res,0,'author');
		return $author;
	}
	//Possible  deprecated method
	function get_surveyid($db_name,$group_id)
	{
	    //$group_table = Database :: get_course_table(TABLE_SURVEY_QUESTION_GROUP);
	    $group_id = Database::escape_string($group_id);
		$sql = "SELECT survey_id FROM $db_name.survey_group WHERE group_id='$group_id'";
		$res = Database::query($sql);
		$surveyid=@Database::result($res,0,'survey_id');
		return $surveyid;
	}

	public static function get_groupname ($db_name,$gid) {
		//$grouptable = Database :: get_course_table(TABLE_SURVEY_QUESTION_GROUP);
		$gid = Database::escape_string($gid);
		$sql = "SELECT * FROM $db_name.survey_group WHERE group_id='$gid'";
		$res=Database::query($sql);
		$code=@Database::result($res,0,'groupname');
		return($code);
	}

	//Possible  deprecated method
	function insert_into_group ($survey_id,$group_title,$introduction,$tb) {
		$survey_id = Database::escape_string($survey_id);
		$group_title = Database::escape_string($group_title);
		$introduction = Database::escape_string($introduction);

		$sql="INSERT INTO $tb (group_id,survey_id,group_title,introduction) values('','$survey_id','$group_title','$introduction')";
		$result=Database::query($sql);
		return Database::insert_id();
	}
	//Possible  deprecated method
	function get_survey_code ($table_survey,$survey_code)
	{
		$survey_code = Database::escape_string($survey_code);
		$sql="SELECT code FROM $table_survey where code='$survey_code'";
		//echo $sql;
		//exit;
		$result=Database::query($sql);
		$code=@Database::result($result,0,'code');
		//echo $code;exit;
		return($code);
	}
	//Possible  deprecated method
	function get_survey_list()
	{
		$survey_table = Database :: get_course_table(TABLE_SURVEY);
		$sql_query = "SELECT survey_id,title FROM $survey_table where title!='' ";
		$sql_result = Database::query($sql_query);
		echo "<select name=\"author\">";
		echo "<option value=\"\"><--Select Survey--></optional>";
		while ($result =@Database::fetch_array($sql_result))
		{
			echo "\n<option value=\"".$result[survey_id]."\">".$result[title]."</option>";
		}
		echo "</select>";
	}
	//Possible  deprecated method
	function create_survey ($surveycode,$surveytitle, $surveysubtitle, $author, $survey_language, $availablefrom, $availabletill,$isshare, $surveytemplate, $surveyintroduction, $surveythanks, $table_survey, $table_group)
		{
			//$table_survey = Database :: get_course_table(TABLE_SURVEY);
			$sql = "INSERT INTO $table_survey (code,title, subtitle, author,lang,avail_from,avail_till, is_shared,template,intro,surveythanks,creation_date) values('$surveycode','$surveytitle','$surveysubtitle','$author','$survey_language','$availablefrom','$availabletill','$isshare','$surveytemplate','$surveyintroduction','$surveythanks',curdate())";
			$result = Database::query($sql);
			//$result = Database::query($sql);
			$survey_id = Database::insert_id();
			$sql2 = "INSERT INTO $table_group(group_id,survey_id,groupname,introduction) values('','$survey_id','No Group','This is your Default Group')";
			$result = Database::query($sql2);
			return $survey_id;
		}
	//Possible  deprecated method
	//Possible  deprecated method
	function create_survey_attach($surveycode,$surveytitle, $surveysubtitle, $author, $survey_language, $availablefrom, $availabletill,$isshare, $surveytemplate, $surveyintroduction, $surveythanks, $table_survey, $table_group)
	{
			//$table_survey = Database :: get_course_table(TABLE_SURVEY);
			$sql = "INSERT INTO $table_survey (code,title, subtitle, author,lang,avail_from,avail_till, is_shared,template,intro,surveythanks,creation_date) values('$surveycode','$surveytitle','$surveysubtitle','$author','$survey_language','$availablefrom','$availabletill','$isshare','$surveytemplate','$surveyintroduction','$surveythanks',curdate())";
			$result = Database::query($sql);
			$survey_id = Database::insert_id();
			return $survey_id;
	}
	
	//Possible  deprecated method

	function update_survey($surveyid,$surveycode,$surveytitle, $surveysubtitle, $author, $survey_language, $availablefrom, $availabletill,$isshare, $surveytemplate, $surveyintroduction, $surveythanks, $cidReq,$table_course) {	  
          
          $sql_course = "SELECT * FROM $table_course WHERE code = '$cidReq'";
          $res_course = Database::query($sql_course);
          $obj_course=@Database::fetch_object($res_course);
          $curr_dbname = $obj_course->db_name ;         
          
		  $sql = "UPDATE $curr_dbname.survey SET code='$surveycode', title='$surveytitle', subtitle='$surveysubtitle', lang='$survey_language',   avail_from='$availablefrom', avail_till='$availabletill', is_shared='$isshare', template='$surveytemplate', intro='$surveyintroduction',surveythanks='$surveythanks'
		  		  WHERE survey_id='$surveyid'";
		  Database::query($sql);
		  return $curr_dbname;
	}

	
	// Possible  deprecated method
	 
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
		$result = Database::query($sql);
		return Database::insert_id();
	}
	
	
	//Possible  deprecated method
	 
	 function get_question_type($questionid)
	 {
	  $table_question = Database :: get_course_table(TABLE_MAIN_SURVEYQUESTION);
	  $questionid = Database::escape_string($questionid);
	  $sql = "SELECT * FROM $table_question WHERE qid='$questionid'";
			$res=Database::query($sql);
			$code=@Database::result($res,0,'type');
			return($code);
	 }

	//Possible  deprecated method
	 
	 function no_of_question($db_name,$gid)
	 {
	  //$table_question = Database :: get_course_table(TABLE_MAIN_SURVEYQUESTION);
	  $gid = Database::escape_string($gid);
	  $sql = "SELECT * FROM $db_name.questions WHERE gid='$gid'";
			$res=Database::query($sql);
			$code=@Database::num_rows($res);
			return($code);
	 }

	
	 //Possible  deprecated method
	 
	function get_data($id, $field)
	{
		global $_course;
		$sql='SELECT '.$field.' FROM '.$_course['dbName'].'.survey WHERE survey_id='.intval($id);
		$res=Database::query($sql);
		$code=@Database::result($res,0);
		return($code);

	}
	
	//Possible  deprecated method
	 
	function get_all_datas($id)
	{
		global $_course;
		$sql='SELECT * FROM '.$_course['dbName'].'.survey WHERE survey_id='.intval($id);
		$res=Database::query($sql);
		return Database::fetch_object($res);
	}
	//Possible  deprecated method
	function get_surveyname($db_name,$sid)
	{
			//$surveytable=Database:: get_course_table(TABLE_SURVEY);
			$sid = Database::escape_string($sid);
			$sql="SELECT * FROM $db_name.survey WHERE survey_id=$sid";
			$res=Database::query($sql);
			$code=@Database::result($res,0,'title');
			return($code);
	}
	//Possible  deprecated method
	function get_surveyname_display($sid)
	{
			$sid = Database::escape_string($sid);
			$surveytable=Database:: get_course_table(TABLE_SURVEY);
			$sql="SELECT * FROM $surveytable WHERE survey_id=$sid";
			$res=Database::query($sql);
			$code=@Database::result($res,0,'title');
			return($code);
	}

	//Possible  deprecated method
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
			$table_question = Database :: get_course_table(TABLE_MAIN_SURVEYQUESTION);
			if(isset($selected_group)){
			 if($selected_group!=''){
			  $sql = "SELECT $table_group('survey_id', 'groupname') values('$sid', '$groupname')";
				$res = Database::query($sql);
				$sql = "INSERT INTO $table_group('survey_id', 'groupname') values('$sid', '$groupname')";
				$res = Database::query($sql);
				$gid_arr[$index]+= Database::insert_id();
				$groupids=implode(",",$gid_arr);
			  }
			}

			echo $groupids;
	}

	
	 // This function deletes a survey and all the groups and question belonging to it.
	 //* @param unknown_type $survey_id
	 
	 // @author unknown
	 // @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, cleanup and refactoring
	 
	 
	public static function delete_survey($survey_id)
	{
		$table_survey 	= Database :: get_course_table(TABLE_SURVEY);
		$table_group 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_GROUP);
		$table_question = Database :: get_course_table(TABLE_MAIN_SURVEYQUESTION);

		$survey_id = Database::escape_string($survey_id);

		// Deleting the survey
		$sql = "DELETE FROM $table_survey WHERE survey_id='".$survey_id."'";
		Database::query($sql);

		// Deleting all the questions of the survey
		$sql = "SELECT * FROM $table_group WHERE survey_id='".$survey_id."'";
		$res = Database::query($sql);
		while($obj = Database::fetch_object($res))
		{
			$sql = "DELETE FROM $table_question WHERE gid='".$obj->group_id."'";
			Database::query($sql);
		}

		// Deleting the groups of the survey
		$sql = "DELETE FROM $table_group WHERE survey_id='".$survey_id."'";
		Database::query($sql);
		return true;
	}


	function delete_group($group_id)
	{
		// Database table definitions
		// @todo use database constants for the survey tables 
		$table_question 	= Database :: get_course_table(TABLE_MAIN_SURVEYQUESTION);
		$table_survey_group = Database :: get_course_table(TABLE_SURVEY_QUESTION_GROUP);

		$sql = "DELETE FROM $table_question WHERE gid='".$group_id."'";
		Database::query($sql);
		$sql = "DELETE FROM $table_survey_group WHERE group_id='".$group_id."'";
		Database::query($sql);
	}
	
	 // Possible  deprecated method

	function select_group_list($survey_id, $seleced_groupid='', $extra_script='')
	{
		$group_table = Database :: get_course_table(TABLE_SURVEY_QUESTION_GROUP);
		$sql = "SELECT * FROM $group_table WHERE survey_id='$survey_id'";
		$sql_result = Database::query($sql);
		if(Database::num_rows($sql_result)>0)
		{
			$str_group_list = "";
			$str_group_list .= "<select name=\"exiztinggroup\" $extra_script>\n";

			while($result=Database::fetch_array($sql_result))
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


	 //Possible  deprecated method

	function display_imported_group($sid,$table_group,$table_question)
	{

		 $sql = "SELECT group_id FROM $table_group WHERE survey_id='$sid'";
		$res = Database::query($sql);
		$num = @Database::num_rows($res);
		//echo "ths is num".$num;
		$parameters = array();
		$displays = array();
		while($obj = @Database::fetch_object($res))
		{

			$groupid = $obj->group_id;
			$query = "SELECT * FROM $table_question WHERE gid = '$groupid'";
			$result = Database::query($query);
		  while($object = @Database::fetch_object($result))
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

	
	//   Possible  deprecated method
	 
	function attach_survey($surveyid,$newsurveyid,$db_name,$curr_dbname)
	//For attaching the whole survey with its groups and questions
	  {
	 $sql = "SELECT *  FROM $db_name.survey_group WHERE survey_id = '$surveyid'";
     $res = Database::query($sql);
	 while($obj=@Database::fetch_object($res))
     {
		 $groupname=addslashes($obj->groupname);
		 $introduction=addslashes($obj->introduction);
	   $sql_insert = "INSERT INTO $curr_dbname.survey_group(group_id,survey_id,groupname,introduction) values('','$newsurveyid','$groupname','$introduction')";
	   $resnext = Database::query($sql_insert);
	   $groupid = Database::insert_id();
	   $sql_q = "SELECT *  FROM $db_name.questions WHERE gid = '$obj->group_id'";
	   $res_q = Database::query($sql_q);
       while($obj_q = Database::fetch_object($res_q))
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
         $res_sort=Database::query($sql_sort);
         $rs=Database::fetch_object($res_sort);
	     $sortby=$rs->sortby;
	     if(empty($sortby))
	     {$sortby=1;}
	     else{$sortby=$sortby+1;}
		 $sql_q_insert = "INSERT INTO $curr_dbname.questions (qid,gid,survey_id,qtype,caption,alignment,sortby,a1,a2,a3,a4,a5,a6,a7,a8,a9,a10,at,ad,r1,r2,r3,r4,r5,r6,r7,r8,r9,r10) values('','$groupid','$newsurveyid','$obj_q->qtype','$caption1','$obj_q->alignment','$sortby','$a1','$a2','$a3','$a4','$a5','$a6','$a7','$a8','$a9','$a10','$at','$ad','$r1','$r2','$r3','$r4','$r5','$r6','$r7','$r8','$r9','$r10')";
	    Database::query($sql_q_insert);
	   }
     }
   }

	
	// Possible  deprecated method
	 
   function update_group($groupid,$surveyid,$groupnamme,$introduction,$curr_dbname)
	{
		$sql = "UPDATE $curr_dbname.survey_group SET group_id='$groupid', survey_id='$surveyid', groupname='$groupnamme', introduction='$introduction' WHERE group_id='$groupid'";
		Database::query($sql);
	}

	
	 //  Possible  deprecated method
	 
	function create_course_survey_rel($cidReq,$survey_id,$table_course,$table_course_survey_rel)
	{
	 $sql = "SELECT * FROM $table_course WHERE code = '$cidReq'";
	 $res = Database::query($sql);
	 $obj=@Database::fetch_object($res);
	 $db_name = $obj->db_name ;
	 $sql="INSERT INTO $table_course_survey_rel(id,course_code,db_name,survey_id) values('','$cidReq','$db_name','$survey_id')";

	 Database::query($sql);
	 return $db_name;
	}

	
	//  Possible  deprecated method
	 
	function pick_surveyname($sid)
	{
		$surveytable=Database:: get_course_table(TABLE_SURVEY);
		$sql="SELECT * FROM $surveytable WHERE survey_id=$sid";
		$res=Database::query($sql);
		$code=@Database::result($res,0,'title');
		return($code);
	}

	 // Possible  deprecated method
	 
	function pick_author($survey_id)
	{
	    $survey_table = Database :: get_course_table(TABLE_SURVEY);
		$sql = "SELECT author FROM $survey_table WHERE survey_id='$survey_id'";
		$res = Database::query($sql);
		$author=@Database::result($res,0,'author');
		return $author;
	}

	function get_status()
	{
		global $_user;

		$table_user = Database::get_main_table(TABLE_MAIN_USER);
		$sqlm = "SELECT  status FROM  $table_user WHERE user_id = '".Database::escape_string($_user['user_id'])."'";
		$resm = Database::query($sqlm);
		$objm=@Database::fetch_object($resm);
		$ss = $objm->status ;
		return $ss;
	}
}
*/

/**
 *
 * Manage the "versioning" of a conditional survey
 *
 * */
class SurveyTree {
	public $surveylist;
	public $plainsurveylist;
	public $numbersurveys;

	/**
	 * Sets the surveylist and the plainsurveylist
	 */
	public function __construct() {
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

		$res = Database::query($sql);
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
	/**
	 * This function gets the parent id of a survey
	 *
	 * @param  int survey id
	 * @return int survey parent id
	 *
	 * @author Julio Montoya <gugli100@gmail.com>, Dokeos
	 * @version September 2008
	 */
	public function getParentId ($id) {
		$node = $this->plainsurveylist[$id];
		if (is_array($node)&& !empty($node['parent_id']))
			return $node['parent_id'];
		else
			return -1;
	}
	/**
	 * This function creates a list of all surveys id
	 * @param  list of nodes
	 * @return array with the structure survey_id => survey_name
	 * @author Julio Montoya <gugli100@gmail.com>, Dokeos
	 * @version September 2008
	 *
	 */
	public function createList ($list) {
		$result=array();
		if(is_array($list)) {
			foreach ($list as $key=>$node) {
				if (is_array($node['children']))
				{
					//echo $key; echo '--<br>';
					//print_r($node);
					//echo '<br>';
					$result[$key]= $node['name'];
					$re=self::createList($node['children']);
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
		}
		return $result;
	}
}
?>
