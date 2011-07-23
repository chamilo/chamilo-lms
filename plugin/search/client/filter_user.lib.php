<?php //$id:$
/**
 * This script declares a set of functions that will enable authorization check
 * for a user's access to a course directory, as well as course name
 * translations for search results display.
 * @package dokeos.search
 * @author Yannick Warnier <yannick.warnier@dokeos.com>
 * @uses The Dokeos database library, to access the tables using its facilities
 * @uses The Dokeos main api library to execute database queries
 */
/**
 * Checks if a user can access a given course
 *
 * The function gets the course code from the course directory, then
 * checks in the course_user table if the user has access to that course.
 * @param integer User ID (inside Dokeos)
 * @param string  Course directory
 * @return boolean True if user has access, false otherwise
 */
function get_boolean_user_access_to_course_dir($user_id,$course_dir){
  if(api_is_platform_admin()){return true;}
  $course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
  $course      = Database::get_main_table(TABLE_MAIN_COURSE);
  //Get the course code
  $sql = "SELECT code FROM $course WHERE directory = '$course_dir'";
  $res = Database::query($sql);
  if(Database::num_rows($res)>0){
    //Course found. Get the course code.
    $row = Database::fetch_array($res);
    $course_code = $row['code'];
    //Check user permissions
    $sql = "SELECT * FROM $course_user
    	WHERE course_code = '$course_code'
	AND user_id = '$user_id'";
    $res = Database::query($sql);
    if(Database::num_rows($res)>0){
      //User permission found, go further and check there is a status
      $row = Database::fetch_array($res);
      $rel = $row['status'];
      //if(!empty($rel)){
        //Status found (we may later check this further to refine permissions)
		//Sometimes for now it appears that the status can be 0, though.
      	return true;
      //}
      //Status not found, problem, return false.
      //return false;
    }else{
      //No course-user relation found, return false
      return false;
    }
  }else{
    //No course found, return false
    return false;
  }
}
/**
 * Check course URL to get a course code and check it against user permissions
 *
 * Make this function always return true when no check is to be done
 * @param	string	URL to check
 * @return	boolean	True on user having access to the course or course not found, false otherwise

 */
function access_check($url,$default=true){
  $matches = array();
  $match1 = preg_match('/courses\/([^\/]*)\//',$url,$matches);
  if(!$match1){
    $match2 = preg_match('/cidReq=([^&]*)/',$url,$matches);
  }
  if($match1 or $match2){
    $has_access = get_boolean_user_access_to_course_dir($_SESSION['_user']['user_id'],$matches[1]);
    if(!$has_access){
      //user has no access to this course, skip it
      return false;
    }//else grant access
    else
    {
      return true;
    }
  }
  return $default;
}
/**
 * Translates a course code into a course name into a string
 *
 * This function should only be used if needed by a funny course-name rule
 * @param	string	The string to transform
 * @return	string	The transformed string
 */
function subst_course_code($string){
  $matches = array();
  if(preg_match('/(PORTAL_[0-9]{1,4})/',$string,$matches)){
    $course      = Database::get_main_table(TABLE_MAIN_COURSE);
    //Get the course code
    $sql = "SELECT title FROM $course WHERE code = '".$matches[1]."'";
    $res = Database::query($sql);
    if(Database::num_rows($res)>0){
      $row = Database::fetch_array($res);
      $string = preg_replace('/(.*)\?cidReq=('.$matches[1].')(.*)/',' '.$row['title'].' - \1 \3',$string);
      $string = preg_replace('/'.$matches[1].'/',$row['title'],$string);
    }
  }
  return $string;
}
?>
