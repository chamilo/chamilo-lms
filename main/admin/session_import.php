<?php // $Id: session_import.php 20197 2009-04-29 21:20:04Z juliomontoya $
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
*	@package dokeos.admin
==============================================================================
*/
$language_file = array('admin','registration');
$cidReset=true;
require('../inc/global.inc.php');
if (empty($charset)) {
	$charset = 'ISO-8859-15';
}
api_protect_admin_script(true);

require_once(api_get_path(LIBRARY_PATH).'fileManage.lib.php');
require_once(api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require(api_get_path(CONFIGURATION_PATH).'add_course.conf.php');
require_once(api_get_path(LIBRARY_PATH).'add_course.lib.inc.php');
require_once(api_get_path(INCLUDE_PATH).'lib/mail.lib.inc.php');
require_once(api_get_path(INCLUDE_PATH).'lib/course.lib.php');
$formSent=0;
$errorMsg='';

$tbl_user					= Database::get_main_table(TABLE_MAIN_USER);
$tbl_course					= Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_course_user			= Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_session				= Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_user			= Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_course			= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_course_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

$tool_name=get_lang('ImportSessionListXMLCSV');

$interbreadcrumb[]=array('url' => 'index.php','name' => get_lang('AdministrationTools'));

set_time_limit(0);
$inserted_in_course = array();

if ($_POST['formSent']) {
	if (isset($_FILES['import_file']['tmp_name'])) {
		$formSent=$_POST['formSent'];
		$file_type=$_POST['file_type'];
		$sendMail=$_POST['sendMail']?1:0;
		$sessions=array();

		///////////////////////
		//XML/////////////////
		/////////////////////
		
		$countSessions = 0;
		
		if ($file_type == 'xml') {

			$racine = @simplexml_load_file($_FILES['import_file']['tmp_name']);																		
			if (is_object($racine)) {																				
				if (count($racine->Users->User) > 0) {	
					//creating/updating users 
					foreach($racine->Users->User as $userNode) {
						$username = mb_convert_encoding($userNode->Username,$charset,'utf-8');
						$isCut = 0; // if the username given is too long
						if(strlen($username)>20) {
							$user_name_dist = $username;
							$username = substr($username,0,20);
							$isCut = 1;
						}						
						$user_exist = UserManager::is_username_available($username);
												
						if ($user_exist == true) {
							if ($isCut) {
								$errorMsg .= get_lang('UsernameTooLongWasCut').' '.get_lang('From').' '.$user_name_dist.' '.get_lang('To').' '.$username.' <br />';
							}
	
							$lastname = mb_convert_encoding($userNode->Lastname,$charset,'utf-8');
							$firstname = mb_convert_encoding($userNode->Firstname,$charset,'utf-8');
							$password = mb_convert_encoding($userNode->Password,$charset,'utf-8');
							if(empty($password)) {
								$password = base64_encode(rand(1000,10000));
	                        }
							$email = mb_convert_encoding($userNode->Email,$charset,'utf-8');
							$official_code = mb_convert_encoding($userNode->OfficialCode,$charset,'utf-8');
							$phone = mb_convert_encoding($userNode->Phone,$charset,'utf-8');
							$status = mb_convert_encoding($userNode->Status,$charset,'utf-8');
							switch ($status) {
								case 'student' : $status = 5; break;
								case 'teacher' : $status = 1; break;
								default : $status = 5; 
								$errorMsg = get_lang('StudentStatusWasGivenTo').' : '.$username.'<br />';
							}
							
							//adding user to the platform 
							$sql = "INSERT INTO $tbl_user SET
									username = '".Database::escape_string($username)."',
									lastname = '".Database::escape_string($lastname)."',
									firstname = '".Database::escape_string($firstname)."',
									password = '".(api_get_encrypted_password($password))."',
									email = '".Database::escape_string($email)."',
									official_code = '".Database::escape_string($official_code)."',
									phone = '".Database::escape_string($phone)."',
									status = '".Database::escape_string($status)."'";
							
							//if available adding also the access_url rel user relationship
							api_sql_query($sql, __FILE__, __LINE__);
							$return=Database::get_last_insert_id();						
							global $_configuration;
							require_once (api_get_path(LIBRARY_PATH).'urlmanager.lib.php');
							if ($_configuration['multiple_access_urls']==true) {										
								if (api_get_current_access_url_id()!=-1)
									UrlManager::add_user_to_url($return, api_get_current_access_url_id());
								else
									UrlManager::add_user_to_url($return, 1);
							} else {
								//we are adding by default the access_url_user table with access_url_id = 1
								UrlManager::add_user_to_url($return, 1);				
							}					
	
							if($sendMail) {														
								$recipient_name = $firstname.' '.$lastname;
								$emailsubject = '['.get_setting('siteName').'] '.get_lang('YourReg').' '.get_setting('siteName');			
								$emailbody="[NOTE:] ".get_lang('ThisIsAutomaticEmailNoReply').".\n\n".get_lang('langDear')." $firstname $lastname,\n\n".get_lang('langYouAreReg')." ". get_setting('siteName') ." ".get_lang('langSettings')." $username\n". get_lang('langPass')." : $password\n\n".get_lang('langAddress') ." ". get_lang('langIs') ." ". $serverAddress ."\n\n".get_lang('YouWillSoonReceiveMailFromCoach')."\n\n". get_lang('langProblem'). "\n\n". get_lang('langFormula');						
								$sender_name = get_setting('administratorName').' '.get_setting('administratorSurname');
							    $email_admin = get_setting('emailAdministrator');							
								@api_mail($recipient_name, $email, $emailsubject, $emailbody, $sender_name,$email_admin);
							}
						} else {
							$lastname = mb_convert_encoding($userNode->Lastname,$charset,'utf-8');
							$firstname = mb_convert_encoding($userNode->Firstname,$charset,'utf-8');
							$password = mb_convert_encoding($userNode->Password,$charset,'utf-8');
							$email = mb_convert_encoding($userNode->Email,$charset,'utf-8');
							$official_code = mb_convert_encoding($userNode->OfficialCode,$charset,'utf-8');
							$phone = mb_convert_encoding($userNode->Phone,$charset,'utf-8');
							$status = mb_convert_encoding($userNode->Status,$charset,'utf-8');
							switch($status) {
								case 'student' : $status = 5; break;
								case 'teacher' : $status = 1; break;
								default : $status = 5; $errorMsg = get_lang('StudentStatusWasGivenTo').' : '.$username.'<br />';
							}
							
							$sql = "UPDATE $tbl_user SET
										lastname = '".Database::escape_string($lastname)."',
										firstname = '".Database::escape_string($firstname)."',
										".(empty($password) ? "" : "password = '".(api_get_encrypted_password($password))."',")."
										email = '".Database::escape_string($email)."',
										official_code = '".Database::escape_string($official_code)."',
										phone = '".Database::escape_string($phone)."',
										status = '".Database::escape_string($status)."'
									WHERE username = '".Database::escape_string($username)."'";
	
							api_sql_query($sql, __FILE__, __LINE__);						
						}
					}
				}
				
				//Creating/editing courses
				if (count($racine->Courses->Course) > 0) {			
					foreach($racine->Courses->Course as $courseNode) {
						$course_code = mb_convert_encoding($courseNode->CourseCode,$charset,'utf-8');
						$title = mb_convert_encoding($courseNode->CourseTitle,$charset,'utf-8');
						$description = mb_convert_encoding($courseNode->CourseDescription,$charset,'utf-8');
						$language = mb_convert_encoding($courseNode->CourseLanguage,$charset,'utf-8');
						$username = mb_convert_encoding($courseNode->CourseTeacher,$charset,'utf-8');
						
						//looking for the teacher
						$sql = "SELECT user_id, lastname, firstname FROM $tbl_user WHERE username='$username'";
						$rs = api_sql_query($sql, __FILE__, __LINE__);
	
						list($user_id, $lastname, $firstname) = Database::fetch_array($rs);
						$keys = define_course_keys($course_code, "", $dbNamePrefix);
	
						if (sizeof($keys)) {
	
							$currentCourseCode = $keys['visual_code'];
							$currentCourseId = $keys['currentCourseId'];
							if(empty($currentCourseCode))
								$currentCourseCode = $currentCourseId;
							$currentCourseDbName = $keys['currentCourseDbName'];
							$currentCourseRepository = $keys['currentCourseRepository'];
							//creating a course
							
							if($currentCourseId == strtoupper($course_code)) {
								if (empty ($title)) {
									$title = $keys['currentCourseCode'];
								}													
																
								prepare_course_repository($currentCourseRepository, $currentCourseId);
								update_Db_course($currentCourseDbName);								
								$pictures_array=fill_course_repository($currentCourseRepository); 
								fill_Db_course($currentCourseDbName, $currentCourseRepository, 'english',$pictures_array);
								//register_course($currentCourseId, $currentCourseCode, $currentCourseRepository, $currentCourseDbName, "$lastname $firstname", $course['unit_code'], addslashes($course['FR']['title']), $language, $user_id);
								$sql = "INSERT INTO ".$tbl_course." SET
											code = '".$currentCourseId."',
											db_name = '".$currentCourseDbName."',
											directory = '".$currentCourseRepository."',
											course_language = '".$language."',
											title = '".$title."',
											description = '".lang2db($description)."',
											category_code = '',
											visibility = '".$defaultVisibilityForANewCourse."',
											show_score = '',
											disk_quota = NULL,
											creation_date = now(),
											expiration_date = NULL,
											last_edit = now(),
											last_visit = NULL,
											tutor_name = '".$lastname." ".$firstname."',
											visual_code = '".$currentCourseCode."'";
	
								api_sql_query($sql, __FILE__, __LINE__);
	
								$sql = "INSERT INTO ".$tbl_course_user." SET
											course_code = '".$currentCourseId."',
											user_id = '".$user_id."',
											status = '1',
											role = '".lang2db('Professor')."',
											tutor_id='1',
											sort='". ($sort +1)."',
											user_course_cat='0'";
	
								api_sql_query($sql, __FILE__, __LINE__);
							}
	
						}
					}                    
				}
				
				if (count($racine->Session) > 0) {
					foreach ($racine->Session as $sessionNode) { // foreach session
						
						$countCourses = 0;
						$countUsers = 0;
	
						$SessionName = mb_convert_encoding($sessionNode->SessionName,$charset,'utf-8');
						$Coach = mb_convert_encoding($sessionNode->Coach,$charset,'utf-8');
	
						if (!empty($Coach)) {													
							$CoachId = UserManager::get_user_id_from_username($Coach);
							if($CoachId === false) {
								$errorMsg .= get_lang('UserDoesNotExist').' : '.$Coach.'<br />';
								$CoachId = api_get_user_id();
							}
						}
						
						$DateStart = $sessionNode->DateStart;
						if(!empty($DateStart)) {
							list($YearStart,$MonthStart, $DayStart) = explode('-',$DateStart);
							if(empty($YearStart) || empty($MonthStart) || empty($DayStart)) {
								$errorMsg .= get_lang('WrongDate').' : '.$DateStart.'<br />';
								break;
							} else {
								$timeStart = mktime(0,0,0,$MonthStart,$DayStart,$YearStart);
							}
	
							$DateEnd = $sessionNode->DateEnd;
							if(!empty($DateStart)) {
								list($YearEnd,$MonthEnd, $DayEnd) = explode('-',$DateEnd);
								if(empty($YearEnd) || empty($MonthEnd) || empty($DayEnd)) {
									$errorMsg .= get_lang('WrongDate').' : '.$DateEnd.'<br />';
									break;
								} else {
									$timeEnd = mktime(0,0,0,$MonthEnd,$DayEnd,$YearEnd);
								}
							}
							if($timeEnd - $timeStart < 0) {
								$errorMsg .= get_lang('StartDateShouldBeBeforeEndDate').' : '.$DateEnd.'<br />';
							}
						}
					}

					// verify that session doesn't exist
					while(!$uniqueName) {
						if($i>1)
							$suffix = ' - '.$i;
						$sql = 'SELECT 1 FROM '.$tbl_session.' WHERE name="'.Database::escape_string($SessionName.$suffix).'"';
						$rs = api_sql_query($sql, __FILE__, __LINE__);

						if(Database::result($rs,0,0)) {
							$i++;
						} else {
							$uniqueName = true;
							$SessionName .= $suffix;
						}
					}
					// Creating the session	
					$sqlSession = "INSERT IGNORE INTO $tbl_session SET
									name = '".Database::escape_string($SessionName)."',
									id_coach = '$CoachId',
									date_start = '$DateStart',
									date_end = '$DateEnd',
									session_admin_id=".intval($_user['user_id']);
					$rsSession = api_sql_query($sqlSession, __FILE__, __LINE__);
					$session_id = Database::get_last_insert_id();					
					$countSessions++;
					
					//adding the session to an access_url 
					global $_configuration;	
                    require_once (api_get_path(LIBRARY_PATH).'urlmanager.lib.php');
					if ($_configuration['multiple_access_urls']==true) {			
						$tbl_user_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);	
						$access_url_id = api_get_current_access_url_id();			
						UrlManager::add_session_to_url($session_id,$access_url_id);			
					} else {
						// we are filling by default the access_url_rel_session table 
						UrlManager::add_session_to_url($session_id,1);
					}
					
					foreach ($sessionNode->User as $userNode){		
						$username = mb_convert_encoding(substr($userNode,0,20),$charset,'utf-8');
						$user_id = UserManager::get_user_id_from_username($username);					
						if($user_id!==false){						
							$sql = "INSERT IGNORE INTO $tbl_session_user SET
									id_user='$user_id',
									id_session = '$session_id'";
							$rsUser = api_sql_query($sql,__FILE__,__LINE__);							
							$countUsers++;							
						}
					}
					
					foreach ($sessionNode->Course as $courseNode){

						$CourseCode = Database::escape_string($courseNode->CourseCode);

						// Verify that the course pointed by the course code node exists
                        if (CourseManager::course_exists($CourseCode)) {
						    // If the course exists we continue
                            $c_info = CourseManager::get_course_information($CourseCode);
							$Coach = substr($courseNode->Coach,0,20);
							if(!empty($Coach)){
								$CoachId = UserManager::get_user_id_from_username($Coach);													
								if($CoachId===false) {
									$errorMsg .= get_lang('UserDoesNotExist').' : '.$Coach.'<br />';
									//$CoachId = api_get_user_id();
									$CoachId = '';
								}
							} else {
								$Coach = '';
							}

							$sqlCourse = "INSERT INTO $tbl_session_course SET
										  course_code = '$CourseCode',
										  id_coach='$CoachId',
										  id_session='$session_id'";
							$rsCourse = api_sql_query($sqlCourse,__FILE__,__LINE__);
                            
							if (Database::affected_rows()) {
								$countCourses++;
								$countUsersCourses = 0;								
								foreach ($courseNode->User as $userNode) {
									$username = substr($userNode,0,20);
									$user_id = UserManager::get_user_id_from_username($username);																		
									if ($user_id!==false) {
										// adding to session_rel_user table
										$sql = "INSERT IGNORE INTO $tbl_session_user SET
											id_user='$user_id',
											id_session = '$session_id'";										                                    
										$rsUser = api_sql_query($sql,__FILE__,__LINE__);
										$countUsers++;
										// adding to session_rel_user_rel_course table			
										$sql = "INSERT IGNORE INTO $tbl_session_course_user SET
												id_user='$user_id',
												course_code='$CourseCode',
												id_session = '$session_id'";
										$rsUsers = api_sql_query($sql,__FILE__,__LINE__);										
										$countUsersCourses++;                                       
									} else {
										$errorMsg .= get_lang('UserDoesNotExist').' : '.$username.'<br />';
									}
								}
								$update_session_course = "UPDATE $tbl_session_course SET nbr_users='$countUsersCourses' WHERE course_code='$CourseCode'";
								api_sql_query($update_session_course,__FILE__,__LINE__);
                                $inserted_in_course[$CourseCode] = $c_info['title'];
							}
						}
						
                        if (CourseManager::course_exists($CourseCode,true)) {
                            // if the course exists we continue
                            // also subscribe to virtual courses through check on visual code
                            $list = CourseManager :: get_courses_info_from_visual_code($CourseCode);
                            foreach ($list as $vcourse) {
                                if ($vcourse['code'] == $CourseCode) {
                                    //ignore, this has already been inserted
                                } else {
                                    $Coach = substr($courseNode->Coach,0,20);
                                    if(!empty($Coach)){
                                        $sqlCoach = "SELECT user_id FROM $tbl_user WHERE username='$Coach'";
                                        $rsCoach = api_sql_query($sqlCoach,__FILE__,__LINE__);
                                        list($CoachId) = (Database::fetch_array($rsCoach));
                                        if(empty($CoachId)) {
                                            $errorMsg .= get_lang('UserDoesNotExist').' : '.$Coach.'<br />';
                                        }
                                    } else {
                                        $Coach = '';
                                    }
        
                                    $sqlCourse = "INSERT INTO $tbl_session_course SET
                                                  course_code = '".$vcourse['code']."',
                                                  id_coach='$CoachId',
                                                  id_session='$session_id'";
                                    $rsCourse = api_sql_query($sqlCourse,__FILE__,__LINE__);
                                    if (Database::affected_rows()) {
                                        $countCourses++;
        
                                        $countUsersCourses = 0;
                                        foreach ($courseNode->User as $userNode) {
                                            $username = substr($userNode,0,20);
                                            $sqlUser = "SELECT user_id FROM $tbl_user WHERE username='".$username."'";
                                            $rsUser = api_sql_query($sqlUser);
                                            list($user_id) = (Database::fetch_array($rsUser));
                                            if (!empty($user_id)) {
                                                $sql = "INSERT IGNORE INTO $tbl_session_user SET
                                                    id_user='$user_id',
                                                    id_session = '$session_id'";
                                                $rsUser = api_sql_query($sql,__FILE__,__LINE__);
                                                if (Database::affected_rows()) {
                                                    $countUsers++;
                                                }
                                                
                                                $sql = "INSERT IGNORE INTO $tbl_session_course_user SET
                                                        id_user='$user_id',
                                                        course_code='".$vcourse['code']."',
                                                        id_session = '$session_id'";
                                                $rsUsers = api_sql_query($sql,__FILE__,__LINE__);
                                                if(Database::affected_rows())
                                                    $countUsersCourses++;
                                            } else {
                                                $errorMsg .= get_lang('UserDoesNotExist').' : '.$username.'<br />';
                                            }
                                        }
                                        api_sql_query("UPDATE $tbl_session_course SET nbr_users='$countUsersCourses' WHERE course_code='$CourseCode'",__FILE__,__LINE__);
                                    }
                                }
                                $inserted_in_course[$vcourse['code']] = $vcourse['title'];
                            }                            
                        } else { // if the course does not exists
							$errorMsg .= get_lang('CourseDoesNotExist').' : '.$CourseCode.'<br />';
						}
					}
					api_sql_query("UPDATE $tbl_session SET nbr_users='$countUsers', nbr_courses='$countCourses' WHERE id='$session_id'",__FILE__,__LINE__);

				}
				if(empty($racine->Users->User) && empty($racine->Courses->Course) && empty($racine->Session)) {
					$errorMsg=get_lang('NoNeededData');
				}
			} else {
				$errorMsg .= get_lang('XMLNotValid');
			}
		} else {
			/////////////////////
			// CSV /////////////
			///////////////////
						
			$content=file($_FILES['import_file']['tmp_name']);
			if(!strstr($content[0],';')) {
				$errorMsg=get_lang('NotCSV');
			} else {
				$tag_names=array();

				foreach($content as $key=>$enreg) {
					$enreg=explode(';',trim($enreg));
					if($key) {
						foreach($tag_names as $tag_key=>$tag_name) {
							$sessions[$key-1][$tag_name]=$enreg[$tag_key];
						}
					} else {
						foreach($enreg as $tag_name)
						{
							$tag_names[]=eregi_replace('[^a-z0-9_-]','',$tag_name);
						}

						if(!in_array('SessionName',$tag_names) || !in_array('DateStart',$tag_names) || !in_array('DateEnd',$tag_names))
						{
							$errorMsg=get_lang('NoNeededData');

							break;
						}
					}
				}
				// looping the sessions
				foreach($sessions as $enreg) {					
					$countUsers = 0;
					$countCourses = 0;
					
					$SessionName = $enreg['SessionName'];
					$DateStart = $enreg['DateStart'];
					$DateEnd = $enreg['DateEnd'];
					
					// searching a coach	
					if(!empty($enreg['Coach'])){
						$sqlCoach = "SELECT user_id FROM $tbl_user WHERE username='".$enreg['Coach']."'";
						$rsCoach = api_sql_query($sqlCoach);
						if (Database::num_rows($rsCoach)>1) {
							list($Coach) = (Database::fetch_array($rsCoach));
						} else {
							// if the user does not exist I'm the coach
							$Coach = api_get_user_id();	
						}
					} else {
						$Coach = api_get_user_id();
					}
					// creating a session
					$sqlSession = "INSERT IGNORE INTO $tbl_session SET
								name = '$SessionName',
								id_coach = '$Coach',
								date_start = '$DateStart',
								date_end = '$DateEnd'";
					$rsSession = api_sql_query($sqlSession, __FILE__, __LINE__);
					
                    $update = false;
					if($rSession === false){
						//if already exists we update the session 
						$update = true;
						$sqlSession = "UPDATE $tbl_session SET
										id_coach = '$Coach',
										date_start = '$DateStart',
										date_end = '$DateEnd'
										WHERE name = '$SessionName'";
						$rsSession = api_sql_query($sqlSession, __FILE__, __LINE__);

						$session_id = api_sql_query("SELECT id FROM $tbl_session WHERE name='$SessionName'",__FILE__,__LINE__);
						list($session_id) = Database::fetch_array($session_id);

						api_sql_query("DELETE FROM $tbl_session_user WHERE id_session='$session_id'",__FILE__,__LINE__);
						api_sql_query("DELETE FROM $tbl_session_course WHERE id_session='$session_id'",__FILE__,__LINE__);
						api_sql_query("DELETE FROM $tbl_session_course_user WHERE id_session='$session_id'",__FILE__,__LINE__);

					} else {
						// we get the last insert id
						$session_id = api_sql_query("SELECT id FROM $tbl_session WHERE name='$SessionName'",__FILE__,__LINE__);
						list($session_id) = Database::fetch_array($session_id);					
					}					
					  
					$countSessions++;
					
					$users = explode('|',$enreg['Users']);
					//var_dump($users );
					if (is_array($users)) {
						foreach ($users as $user) {									
							$user_id = UserManager::get_user_id_from_username($user);							
							if ($user_id!==false) {					
							// insert new users							
								$sql = "INSERT IGNORE INTO $tbl_session_user SET
									id_user='$user_id',
									id_session = '$session_id'";
								$rsUser = api_sql_query($sql,__FILE__,__LINE__);
								$countUsers++;
							}
						}
					}
					
					$courses = explode('|',$enreg['Courses']);

					foreach ($courses as $course) {
						
                        $CourseCode = strtoupper(substr($course,0,strpos($course,'[')));
                        
                        if (CourseManager::course_exists($CourseCode)) {             	
                            // If the course exists we continue
                            $c_info = CourseManager::get_course_information($CourseCode);
							
    						$Coach = strstr($course,'[');
    						$Coach = substr($Coach,1,strpos($Coach,']')-1);
    
    						if(!empty($Coach)) {
    							$sqlCoach = "SELECT user_id FROM $tbl_user WHERE username='$Coach'";
    							$rsCoach = api_sql_query($sqlCoach,__FILE__,__LINE__);
    							list($Coach) = (Database::fetch_array($rsCoach));
    						} else {
    							$Coach = '';
    						}    						
    						$sqlCourse = "INSERT IGNORE INTO $tbl_session_course SET
    									  course_code = '$CourseCode',
    									  id_coach='$Coach',
    									  id_session='$session_id'";    						
    						$rsCourse = api_sql_query($sqlCourse,__FILE__,__LINE__);
    						
    						$countCourses++;
    						$users = substr($course , strpos($course,'[',1)+1 , strpos($course,']',1));
    						$users = explode('|',$enreg['Users']);
    						$countUsersCourses = 0;
    						foreach ($users as $user) {    								
    							$user_id = UserManager::get_user_id_from_username($user);							
								if ($user_id!==false) {	    									    								
	    							$sql = "INSERT IGNORE INTO $tbl_session_course_user SET
	    									id_user='$user_id',
	    									course_code='$CourseCode',
	    									id_session = '$session_id'";
	    							$rsUsers = api_sql_query($sql,__FILE__,__LINE__);
	    							$countUsersCourses++;
    							}
    						}
    						api_sql_query("UPDATE $tbl_session_course SET nbr_users='$countUsersCourses' WHERE course_code='$CourseCode'",__FILE__,__LINE__);
    					
                            $c_info = CourseManager::get_course_information($CourseCode);
                            $inserted_in_course[$CourseCode] = $c_info['title'];                        
                     	}
                        
	                    if (CourseManager::course_exists($CourseCode,true)) {
	                        $list = CourseManager :: get_courses_info_from_visual_code($CourseCode);
	                        foreach ($list as $vcourse) {
	                            if ($vcourse['code'] == $CourseCode) {
	                                //ignore, this has already been inserted
	                            } else {
	                                $Coach = strstr($course,'[');
	                                $Coach = substr($Coach,1,strpos($Coach,']')-1);
	        
	                                if(!empty($Coach)){
	                                    $sqlCoach = "SELECT user_id FROM $tbl_user WHERE username='$Coach'";
	                                    $rsCoach = api_sql_query($sqlCoach,__FILE__,__LINE__);
	                                    list($Coach) = (Database::fetch_array($rsCoach));
	                                } else {
	                                    $Coach = '';
	                                }
	        
	                                $sqlCourse = "INSERT IGNORE INTO $tbl_session_course SET
	                                              course_code = '".$vcourse['code']."',
	                                              id_coach='$Coach',
	                                              id_session='$session_id'";
	        
	                                $rsCourse = api_sql_query($sqlCourse,__FILE__,__LINE__);
	                                if(Database::affected_rows()){
	                                    $countCourses++;
	                                    $users = substr($course , strpos($course,'[',1)+1 , strpos($course,']',1));
	                                    $users = explode('|',$enreg['Users']);
	                                    $countUsersCourses = 0;
	                                    foreach ($users as $user){
	                                        $sqlUser = "SELECT user_id FROM $tbl_user WHERE username='".$user."'";
	                                        $rsUser = api_sql_query($sqlUser);
	                                        list($user_id) = (Database::fetch_array($rsUser));
	                                        $sql = "INSERT INTO $tbl_session_course_user SET
	                                                id_user='$user_id',
	                                                course_code='".$vcourse['code']."',
	                                                id_session = '$session_id'";
	                                        $rsUsers = api_sql_query($sql,__FILE__,__LINE__);
	                                        if(Database::affected_rows())
	                                            $countUsersCourses++;
	                                    }
	                                    api_sql_query("UPDATE $tbl_session_course SET nbr_users='$countUsersCourses' WHERE course_code='".$vcourse['code']."'",__FILE__,__LINE__);
	                                }
	                            }
	                        }
                        	$inserted_in_course[$vcourse['code']] = $vcourse['title'];                        
                   		}
					}
					
					$sql_update_users = "UPDATE $tbl_session SET nbr_users='$countUsers', nbr_courses='$countCourses' WHERE id='$session_id'";
					api_sql_query($sql_update_users,__FILE__,__LINE__);
				}
			}
		}
		if(!empty($errorMsg)) {
			$errorMsg = get_lang('ButProblemsOccured').' :<br />'.$errorMsg;
		}

        if (count($inserted_in_course)>1) {
        	$warn = get_lang('SeveralCoursesSubscribedToSessionBecauseOfSameVisualCode').': ';
            foreach ($inserted_in_course as $code => $title) {
            	$warn .= ' '.$title.' ('.$code.'),';
            }
            $warn = substr($warn,0,-1);
        }
		if($countSessions == 1) {
			header('Location: resume_session.php?id_session='.$session_id.'&warn='.urlencode($warn));
			exit;
		} else {
			header('Location: session_list.php?action=show_message&message='.urlencode(get_lang('FileImported').' '.$errorMsg).'&warn='.urlencode($warn));
			exit;
		}
	} else {
		$errorMsg = get_lang('NoInputFile');
	}
}

// display the header
Display::display_header($tool_name);

// display the tool title
// api_display_tool_title($tool_name);

if (count($inserted_in_course) > 1) {
	$msg = get_lang('SeveralCoursesSubscribedToSessionBecauseOfSameVisualCode').': ';
    foreach ($inserted_in_course as $code => $title) {
    	$msg .= ' '.$title.' ('.$title.'),';
    }
    $msg = substr($msg,0,-1);
    Display::display_warning_message($msg);
}
?>

<form method="post" action="<?php echo api_get_self(); ?>" enctype="multipart/form-data" style="margin:0px;">
<input type="hidden" name="formSent" value="1">
<div class="row"><div class="form_header"><?php echo $tool_name; ?></div></div>
<table border="0" cellpadding="5" cellspacing="0">

<?php
if(!empty($errorMsg)) {
?>
<tr>
  <td colspan="2">
<?php
	Display::display_normal_message($errorMsg,false); //main API
?>
  </td>
</tr>
<?php
}
?>
<tr>
  <td nowrap="nowrap"><?php echo get_lang('ImportFileLocation'); ?> :</td>
  <td><input type="file" name="import_file" size="30"></td>
</tr>
<tr>
  <td nowrap="nowrap" valign="top"><?php echo get_lang('FileType'); ?> :</td>
  <td>
	<input class="checkbox" type="radio" name="file_type" id="file_type_xml" value="xml" checked="checked" /> <label for="file_type_xml">XML</label> (<a href="example_session.xml" target="_blank"><?php echo get_lang('ExampleXMLFile'); ?></a>)<br>
	<input class="checkbox" type="radio" name="file_type" id="file_type_csv"  value="csv" <?php if($formSent && $file_type == 'csv') echo 'checked="checked"'; ?>> <label for="file_type_csv">CSV</label> (<a href="example_session.csv" target="_blank"><?php echo get_lang('ExampleCSVFile'); ?></a>)<br>
  </td>
</tr>
<tr>
  <td nowrap="nowrap" valign="top"><?php echo get_lang('SendMailToUsers'); ?> :</td>
  <td>
	<input class="checkbox" type="checkbox" name="sendMail" id="sendMail" value="true" />
  </td>
</tr>
<tr>
  <td>&nbsp;</td>
  <td>
  <button class="save" type="submit" name="name" value="<?php echo get_lang('ImportSession') ?>"><?php echo get_lang('ImportSession') ?></button>
  </td>
</tr>
</table>
</form>

<font color="gray">
<p><?php echo get_lang('CSVMustLookLike').' ('.get_lang('MandatoryFields').')'; ?> :</p>

<blockquote>
<pre>
<b>SessionName</b>;Coach;<b>DateStart</b>;<b>DateEnd</b>;Users;Courses
<b>xxx</b>;xxx;<b>xxx;xxx</b>;username1|username2;course1[coach1][username1,username2,...]|course2[coach1][username1,username2,...]
</pre>
</blockquote>

<p><?php echo get_lang('XMLMustLookLike').' ('.get_lang('MandatoryFields').')'; ?> :</p>

<blockquote>
<pre>
&lt;?xml version=&quot;1.0&quot; encoding=&quot;ISO-8859-1&quot;?&gt;
&lt;Sessions&gt;
    &lt;Users&gt;
        &lt;User&gt;
            &lt;Username&gt;<b>username1</b>&lt;/Username&gt;
            &lt;Lastname&gt;xxx&lt;/Lastname&gt;
            &lt;Firstname&gt;xxx&lt;/Firstname&gt;
            &lt;Password&gt;xxx&lt;/Password&gt;
            &lt;Email&gt;xxx@xx.xx&lt;/Email&gt;
            &lt;OfficialCode&gt;xxx&lt;/OfficialCode&gt;
            &lt;Phone&gt;xxx&lt;/Phone&gt;
            &lt;Status&gt;student|teacher&lt;/Status&gt;
        &lt;/User&gt;
    &lt;/Users&gt;
    &lt;Courses&gt;
        &lt;Course&gt;
            &lt;CourseCode&gt;<b>xxx</b>&lt;/CourseCode&gt;
            &lt;CourseTeacher&gt;xxx&lt;/CourseTeacher&gt;
            &lt;CourseLanguage&gt;xxx&lt;/CourseLanguage&gt;
            &lt;CourseTitle&gt;xxx&lt;/CourseTitle&gt;
            &lt;CourseDescription&gt;xxx&lt;/CourseDescription&gt;
        &lt;/Course&gt;
    &lt;/Courses&gt;
    &lt;Session&gt;
        <b>&lt;SessionName&gt;xxx&lt;/SessionName&gt;</b>
        &lt;Coach&gt;xxx&lt;/Coach&gt;
        <b>&lt;DateStart&gt;xxx&lt;/DateStart&gt;</b>
        <b>&lt;DateEnd&gt;xxx&lt;/DateEnd&gt;</b>
        &lt;User&gt;xxx&lt;/User&gt;
        &lt;User&gt;xxx&lt;/User&gt;
    	&lt;Course&gt;
    		&lt;CourseCode&gt;coursecode1&lt;/CourseCode&gt;
    		&lt;Coach&gt;coach1&lt;/Coach&gt;
		&lt;User&gt;username1&lt;/User&gt;
		&lt;User&gt;username2&lt;/User&gt;
    	&lt;/Course&gt;
    &lt;/Session&gt;
&lt;/Sessions&gt;
</pre>
</blockquote>
</font>
<?php
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>