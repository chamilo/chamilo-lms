<?php // $Id: session_import.php 20699 2009-05-15 16:32:03Z ivantcholakov $
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
*	@package dokeos.admin
==============================================================================
*/

$language_file = array('admin', 'registration');
$cidReset = true;

require '../inc/global.inc.php';

api_protect_admin_script(true);

require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require api_get_path(CONFIGURATION_PATH).'add_course.conf.php';
require_once api_get_path(LIBRARY_PATH).'add_course.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'sessionmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';

$form_sent = 0;
$error_msg = '';

$tbl_user					= Database::get_main_table(TABLE_MAIN_USER);
$tbl_course					= Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_course_user			= Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_session				= Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_user			= Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_course			= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_course_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

$tool_name = get_lang('ImportSessionListXMLCSV');

$interbreadcrumb[] = array('url' => 'index.php','name' => get_lang('AdministrationTools'));

set_time_limit(0);
$inserted_in_course = array();

if ($_POST['formSent']) {
	if (isset($_FILES['import_file']['tmp_name'])) {
		$form_sent = $_POST['formSent'];
		$file_type = $_POST['file_type'];
		$send_mail = $_POST['sendMail'] ? 1 : 0;
		//$updatesession = $_POST['updatesession'] ? 1 : 0;
		$updatesession = 0;
		$sessions = array();

		$session_counter = 0;

		if ($file_type == 'xml') {

			///////////////////////
			//XML/////////////////
			/////////////////////

			// SimpleXML for PHP5 deals with various encodings, but how many they are, what are version issues, do we need to waste time with configuration options?
			// For avoiding complications we go some sort of "PHP4 way" - we convert the input xml-file into UTF-8 before passing it to the parser.
			// Instead of:
			// $root = @simplexml_load_file($_FILES['import_file']['tmp_name']);
			// we may use the following construct:
			// $root = @simplexml_load_string(api_utf8_encode_xml(file_get_contents($_FILES['import_file']['tmp_name'])));
			// To ease debugging let us use:
			$content = file_get_contents($_FILES['import_file']['tmp_name']);
			$content = api_utf8_encode_xml($content);
			$root = @simplexml_load_string($content);
			unset($content);

			if (is_object($root)) {
				if (count($root->Users->User) > 0) {

					// Creating/updating users from <Sessions> <Users> base node.
					foreach ($root->Users->User as $node_user) {
						$username = api_utf8_decode($node_user->Username);
						$was_cut = false;
						if (UserManager::is_username_too_long($username)) {
							// The given username is too long.
							$user_name_dist = $username;
							$username = UserManager::cut_username($username);
							$was_cut = true;
						}
						$user_exists = UserManager::is_username_available($username);
						if ($user_exists) {
							if ($was_cut) {
								$error_msg .= get_lang('UsernameTooLongWasCut').' '.get_lang('From').' '.$user_name_dist.' '.get_lang('To').' '.$username.' <br />';
							}
							$lastname = api_utf8_decode($node_user->Lastname);
							$firstname = api_utf8_decode($node_user->Firstname);
							$password = api_utf8_decode($node_user->Password);
							if (empty($password)) {
								$password = base64_encode(rand(1000, 10000));
							}
							$email = api_utf8_decode($node_user->Email);
							$official_code = api_utf8_decode($node_user->OfficialCode);
							$phone = api_utf8_decode($node_user->Phone);
							$status = api_utf8_decode($node_user->Status);
							switch ($status) {
								case 'student' : $status = 5; break;
								case 'teacher' : $status = 1; break;
								default : $status = 5; $error_msg = get_lang('StudentStatusWasGivenTo').' : '.$username.'<br />';
							}

							// Adding the current user to the platform.
							$sql = "INSERT INTO $tbl_user SET
									username = '".Database::escape_string($username)."',
									lastname = '".Database::escape_string($lastname)."',
									firstname = '".Database::escape_string($firstname)."',
									password = '".(api_get_encrypted_password($password))."',
									email = '".Database::escape_string($email)."',
									official_code = '".Database::escape_string($official_code)."',
									phone = '".Database::escape_string($phone)."',
									status = '".Database::escape_string($status)."'";

							// When it is applicable, adding the access_url rel user relationship too.
							Database::query($sql, __FILE__, __LINE__);
							$return = Database::get_last_insert_id();
							global $_configuration;
							require_once api_get_path(LIBRARY_PATH).'urlmanager.lib.php';
							if ($_configuration['multiple_access_urls']) {
								if (api_get_current_access_url_id() != -1) {
									UrlManager::add_user_to_url($return, api_get_current_access_url_id());
								} else {
									UrlManager::add_user_to_url($return, 1);
								}
							} else {
								// We add by default in the access_url_user table with access_url_id = 1.
								UrlManager::add_user_to_url($return, 1);
							}
							// Sending email to the current user.
							if ($send_mail) {
								$recipient_name = api_get_person_name($firstname, $lastname, null, PERSON_NAME_EMAIL_ADDRESS);
								$emailsubject = '['.api_get_setting('siteName').'] '.get_lang('YourReg').' '.api_get_setting('siteName');
								$emailbody = "[NOTE:] ".get_lang('ThisIsAutomaticEmailNoReply').".\n\n".get_lang('langDear')." ".api_get_person_name($firstname, $lastname).",\n\n".get_lang('langYouAreReg')." ".api_get_setting('siteName') ." ".get_lang('langSettings')." $username\n". get_lang('langPass')." : $password\n\n".get_lang('langAddress') ." ". get_lang('langIs') ." ". $serverAddress ."\n\n".get_lang('YouWillSoonReceiveMailFromCoach')."\n\n". get_lang('langProblem'). "\n\n". get_lang('langFormula');
								$sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
								$email_admin = api_get_setting('emailAdministrator');
								@api_mail($recipient_name, $email, $emailsubject, $emailbody, $sender_name, $email_admin);
							}
						} else {
							$lastname = api_utf8_decode($node_user->Lastname);
							$firstname = api_utf8_decode($node_user->Firstname);
							$password = api_utf8_decode($node_user->Password);
							$email = api_utf8_decode($node_user->Email);
							$official_code = api_utf8_decode($node_user->OfficialCode);
							$phone = api_utf8_decode($node_user->Phone);
							$status = api_utf8_decode($node_user->Status);
							switch ($status) {
								case 'student' : $status = 5; break;
								case 'teacher' : $status = 1; break;
								default : $status = 5; $error_msg = get_lang('StudentStatusWasGivenTo').' : '.$username.'<br />';
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

							Database::query($sql, __FILE__, __LINE__);
						}
					}
				}

				// Creating  courses from <Sessions> <Courses> base node.
				if (count($root->Courses->Course) > 0) {
					foreach ($root->Courses->Course as $courseNode) {
						$course_code = api_utf8_decode($courseNode->CourseCode);
						$title = api_utf8_decode($courseNode->CourseTitle);
						$description = api_utf8_decode($courseNode->CourseDescription);
						$language = api_utf8_decode($courseNode->CourseLanguage);
						$username = api_utf8_decode($courseNode->CourseTeacher);

						// Looking up for the teacher.
						$sql = "SELECT user_id, lastname, firstname FROM $tbl_user WHERE username='$username'";
						$rs = Database::query($sql, __FILE__, __LINE__);
						list($user_id, $lastname, $firstname) = Database::fetch_array($rs);
						global $_configuration;
						$keys = define_course_keys($course_code, '', $_configuration['db_prefix']);

						if (sizeof($keys)) {
							$current_course_code = $keys['visual_code'];
							$current_course_id = $keys['currentCourseId'];
							if (empty($current_course_code)) {
								$current_course_code = $current_course_id;
							}
							$current_course_db_name = $keys['currentCourseDbName'];
							$current_course_repository = $keys['currentCourseRepository'];

							// Course creation.
							if ($current_course_id == api_strtoupper($course_code)) {
								if (empty ($title)) {
									$title = $keys['currentCourseCode'];
								}
								prepare_course_repository($current_course_repository, $current_course_id);


								update_Db_course($current_course_db_name);
								$pictures_array = fill_course_repository($current_course_repository);
								fill_Db_course($current_course_db_name, $current_course_repository, 'english', $pictures_array); // TODO: Hard-coded language id 'english'.
								register_course($current_course_id, $current_course_code, $current_course_repository, $current_course_db_name, "$lastname $firstname", $course['unit_code'], addslashes($course['FR']['title']), $language, $user_id);  // TODO: Hard-coded language 'FR'.
								$sql = "INSERT INTO ".$tbl_course." SET
										code = '".$current_course_id."',
										db_name = '".$current_course_db_name."',
										directory = '".$current_course_repository."',
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
										tutor_name = '".api_get_person_name($firstname, $lastname, null, null, $language)."',
										visual_code = '".$current_course_code."'";

								Database::query($sql, __FILE__, __LINE__);

								$sql = "INSERT INTO ".$tbl_course_user." SET
										course_code = '".$current_course_id."',
										user_id = '".$user_id."',
										status = '1',
										role = '".lang2db('Professor')."',
										tutor_id='1',
										sort='". ($sort + 1)."',
										user_course_cat='0'";

								Database::query($sql, __FILE__, __LINE__);
							}
						}
					}
				}

				// Creating sessions from <Sessions> base node.
				if (count($root->Session) > 0) {
					foreach ($root->Session as $node_session) {

						$course_counter = 0;
						$user_counter = 0;

						$session_name = api_utf8_decode($node_session->SessionName);
						$coach = api_utf8_decode($node_session->Coach);

						if (!empty($coach)) {
							$coach_id = UserManager::get_user_id_from_username($coach);
							if ($coach_id === false) {
								$error_msg .= get_lang('UserDoesNotExist').' : '.$coach.'<br />';
								// Forcing the coach id if user does not exist.
								$coach_id = api_get_user_id();
							}
						} else {
							// Forcing the coach id.
							$coach_id = api_get_user_id();
						}

						$date_start = $node_session->DateStart;

						if (!empty($date_start)) {
							list($year_start, $month_start, $day_start) = explode('-', $date_start);
							if(empty($year_start) || empty($month_start) || empty($day_start)) {
								$error_msg .= get_lang('WrongDate').' : '.$date_start.'<br />';
								break;
							} else {
								$time_start = mktime(0, 0, 0, $month_start, $day_start, $year_start);
							}

							$date_end = $node_session->DateEnd;
							if (!empty($date_start)) {
								list($year_end, $month_end, $day_end) = explode('-', $date_end);
								if (empty($year_end) || empty($month_end) || empty($day_end)) {
									$error_msg .= get_lang('WrongDate').' : '.$date_end.'<br />';
									break;
								} else {
									$time_end = mktime(0, 0, 0, $month_end, $day_end, $year_end);
								}
							}
							if ($time_end - $time_start < 0) {
								$error_msg .= get_lang('StartDateShouldBeBeforeEndDate').' : '.$date_end.'<br />';
							}
						}

						if (!$updatesession) {
							// Always create a session.
							$unique_name = false; // This MUST be initializead.
							$i = 0;
							// Change session name, verify that session doesn't exist.
							while (!$unique_name) {
								if ($i > 1) {
									$suffix = ' - '.$i;
								}
								$sql = 'SELECT 1 FROM '.$tbl_session.' WHERE name="'.Database::escape_string($session_name.$suffix).'"';
								$rs = Database::query($sql, __FILE__, __LINE__);
								if (Database::result($rs, 0, 0)) {
									$i++;
								} else {
									$unique_name = true;
									$session_name .= $suffix;
								}
							}
							// Creating the session.
							$sql_session = "INSERT IGNORE INTO $tbl_session SET
									name = '".Database::escape_string($session_name)."',
									id_coach = '$coach_id',
									date_start = '$date_start',
									date_end = '$date_end',
									session_admin_id=".intval($_user['user_id']);
							$rs_session = Database::query($sql_session, __FILE__, __LINE__);
							$session_id = Database::get_last_insert_id();
							$session_counter++;

						} else {
							// Update the session if it is needed.
							$my_session_result = SessionManager::get_session_by_name($session_name);
							if ($my_session_result == false) {
								// Creating the session.
								$sql_session = "INSERT IGNORE INTO $tbl_session SET
										name = '".Database::escape_string($session_name)."',
										id_coach = '$coach_id',
										date_start = '$date_start',
										date_end = '$date_end',
										session_admin_id=".intval($_user['user_id']);
								$rs_session = Database::query($sql_session, __FILE__, __LINE__);
								$session_id = Database::get_last_insert_id();
								$session_counter++;
							} else {
								// if the session already exists - update it.
								$sql_session = "UPDATE $tbl_session SET
										id_coach = '$coach_id',
										date_start = '$date_start',
										date_end = '$date_end'
									WHERE name = '$session_name'";
								$rs_session = Database::query($sql_session, __FILE__, __LINE__);
								$session_id = Database::query("SELECT id FROM $tbl_session WHERE name='$session_name'", __FILE__, __LINE__);
								list($session_id) = Database::fetch_array($session_id);
								Database::query("DELETE FROM $tbl_session_user WHERE id_session='$session_id'", __FILE__, __LINE__);
								Database::query("DELETE FROM $tbl_session_course WHERE id_session='$session_id'", __FILE__, __LINE__);
								Database::query("DELETE FROM $tbl_session_course_user WHERE id_session='$session_id'", __FILE__, __LINE__);
							}
						}

						// Associate the session with access_url.
						global $_configuration;
						require_once api_get_path(LIBRARY_PATH).'urlmanager.lib.php';
						if ($_configuration['multiple_access_urls']) {
							$tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
							$access_url_id = api_get_current_access_url_id();
							UrlManager::add_session_to_url($session_id, $access_url_id);
						} else {
							// We fill by default the access_url_rel_session table.
							UrlManager::add_session_to_url($session_id, 1);
						}

						// Adding users to the new session.
						foreach ($node_session->User as $node_user){
							$username = UserManager::cut_username(api_utf8_decode($node_user));
							$user_id = UserManager::get_user_id_from_username($username);
							if ($user_id !== false) {
								$sql = "INSERT IGNORE INTO $tbl_session_user SET
										id_user='$user_id',
										id_session = '$session_id'";
								$rs_user = Database::query($sql, __FILE__, __LINE__);
								$user_counter++;
							}
						}

						// Adding courses to a session.
						foreach ($node_session->Course as $node_course) {
							$course_code = Database::escape_string($node_course->CourseCode);
							// Verify that the course pointed by the course code node exists.
							if (CourseManager::course_exists($course_code)) {
								// If the course exists we continue.
								$course_info = CourseManager::get_course_information($course_code);
								// Searching the coach.
								$coach = UserManager::cut_username(api_utf8_decode($node_course->Coach));
								if (!empty($coach)) {
									$coach_id = UserManager::get_user_id_from_username($coach);
									if ($coach_id === false) {
										$error_msg .= get_lang('UserDoesNotExist').' : '.$coach.'<br />';
										$coach_id = '';
									}
								} else {
									$coach_id = '';
								}
								$session_course_relation = SessionManager::relation_session_course_exist($session_id, $course_code);
								if ($session_course_relation == false) {
									$sql_course = "INSERT INTO $tbl_session_course SET
											course_code = '$course_code',
											id_coach='$coach_id',
											id_session='$session_id'";
									$rs_course = Database::query($sql_course, __FILE__, __LINE__);
								}
								if (Database::affected_rows()) {
									$course_counter++;
									$users_in_course_counter = 0;
									foreach ($node_course->User as $node_user) {
										$username = UserManager::cut_username(api_utf8_decode($node_user));
										$user_id = UserManager::get_user_id_from_username($username);
										if ($user_id !== false) {
											// Adding to session_rel_user table.
											$sql = "INSERT IGNORE INTO $tbl_session_user SET
													id_user='$user_id',
													id_session = '$session_id'";
											$rs_user = Database::query($sql,__FILE__,__LINE__);
											$user_counter++;
											// Adding to session_rel_user_rel_course table.
											$sql = "INSERT IGNORE INTO $tbl_session_course_user SET
													id_user='$user_id',
													course_code='$course_code',
													id_session = '$session_id'";
											$rs_users = Database::query($sql, __FILE__, __LINE__);
											$users_in_course_counter++;
										} else {
											$error_msg .= get_lang('UserDoesNotExist').' : '.$username.'<br />';
										}
									}
									$update_session_course = "UPDATE $tbl_session_course SET nbr_users='$users_in_course_counter' WHERE course_code='$course_code'";
									Database::query($update_session_course, __FILE__, __LINE__);
									$inserted_in_course[$course_code] = $course_info['title'];
								}
							}

							if (CourseManager::course_exists($course_code, true)) {
								// If the course exists we continue.
								// Also subscribe to virtual courses through check on visual code.
								$list = CourseManager :: get_courses_info_from_visual_code($course_code);
								foreach ($list as $vcourse) {
									if ($vcourse['code'] == $course_code) {
										// Ignore, this has already been inserted.
									} else {
										$coach = UserManager::cut_username(api_utf8_decode($node_course->Coach));
										if (!empty($coach)) {
											$coach_id = UserManager::get_user_id_from_username($coach);
											if ($user_id === false) {
												$error_msg .= get_lang('UserDoesNotExist').' : '.$coach.'<br />';
												$coach_id = '';
											}
										} else {
											$coach_id = '';
										}

										$sql_course = "INSERT INTO $tbl_session_course SET
												course_code = '".$vcourse['code']."',
												id_coach='$coach_id',
												id_session='$session_id'";
										$rs_course = Database::query($sql_course, __FILE__, __LINE__);
										if (Database::affected_rows()) {
											$course_counter++;
											$users_in_course_counter = 0;
											foreach ($node_course->User as $node_user) {
												$username = UserManager::cut_username(api_utf8_decode($node_user));
												$user_id = UserManager::get_user_id_from_username($username);
												if ($user_id !== false) {
													$sql = "INSERT IGNORE INTO $tbl_session_user SET
															id_user='$user_id',
															id_session = '$session_id'";
													$rs_user = Database::query($sql, __FILE__, __LINE__);
													if (Database::affected_rows()) {
														$user_counter++;
													}
													$sql = "INSERT IGNORE INTO $tbl_session_course_user SET
															id_user='$user_id',
															course_code='".$vcourse['code']."',
															id_session = '$session_id'";
													$rs_users = Database::query($sql, __FILE__, __LINE__);
													if (Database::affected_rows()) {
														$users_in_course_counter++;
													}
												} else {
													$error_msg .= get_lang('UserDoesNotExist').' : '.$username.'<br />';
												}
											}
											Database::query("UPDATE $tbl_session_course SET nbr_users='$users_in_course_counter' WHERE course_code='$course_code'",__FILE__,__LINE__);
										}
									}
									$inserted_in_course[$vcourse['code']] = $vcourse['title'];
								}
							} else {
								// Tthe course does not exist.
								$error_msg .= get_lang('CourseDoesNotExist').' : '.$course_code.'<br />';
							}
						}
						Database::query("UPDATE $tbl_session SET nbr_users='$user_counter', nbr_courses='$course_counter' WHERE id='$session_id'", __FILE__, __LINE__);
					}

				}
				if (empty($root->Users->User) && empty($root->Courses->Course) && empty($root->Session)) {
					$error_msg = get_lang('NoNeededData');
				}
			} else {
				$error_msg .= get_lang('XMLNotValid');
			}
		} else {

			/////////////////////
			// CSV /////////////
			///////////////////

			$content = file($_FILES['import_file']['tmp_name']);
			if (!api_strstr($content[0], ';')) {
				$error_msg = get_lang('NotCSV');
			} else {
				$tag_names = array();

				foreach ($content as $key => $enreg) {
					$enreg = explode(';', trim($enreg));
					if ($key) {
						foreach ($tag_names as $tag_key => $tag_name) {
							$sessions[$key - 1][$tag_name] = $enreg[$tag_key];
						}
					} else {
						foreach ($enreg as $tag_name) {
							$tag_names[] = api_eregi_replace('[^a-z0-9_-]', '', $tag_name);
						}
						if (!in_array('SessionName', $tag_names) || !in_array('DateStart', $tag_names) || !in_array('DateEnd', $tag_names)) {
							$error_msg = get_lang('NoNeededData');
							break;
						}
					}
				}
				// looping the sessions
				foreach ($sessions as $enreg) {
					$user_counter = 0;
					$course_counter = 0;

					$session_name = $enreg['SessionName'];
					$date_start = $enreg['DateStart'];
					$date_end = $enreg['DateEnd'];

					// searching a coach
					if (!empty($enreg['Coach'])) {
						$coach_id = UserManager::get_user_id_from_username($enreg['Coach']);
						if ($coach_id === false) {
							// If the coach-user does not exist - I'm the coach.
							$coach_id = api_get_user_id();
						}
					} else {
						$coach_id = api_get_user_id();
					}

					if (!$updatesession) {
						// Always create a session.
						$unique_name = false; // This MUST be initializead.
						$i = 0;
						// Change session name, verify that session doesn't exist
						while (!$unique_name) {
							if ($i > 1) {
								$suffix = ' - '.$i;
							}
							$sql = 'SELECT 1 FROM '.$tbl_session.' WHERE name="'.Database::escape_string($session_name.$suffix).'"';
							$rs = Database::query($sql, __FILE__, __LINE__);

							if (Database::result($rs, 0, 0)) {
								$i++;
							} else {
								$unique_name = true;
								$session_name .= $suffix;
							}
						}

						// Creating the session.
						$sql_session = "INSERT IGNORE INTO $tbl_session SET
								name = '".Database::escape_string($session_name)."',
								id_coach = '$coach_id',
								date_start = '$date_start',
								date_end = '$date_end',
								session_admin_id=".intval($_user['user_id']);
						$rs_session = Database::query($sql_session, __FILE__, __LINE__);
						$session_id = Database::get_last_insert_id();
						$session_counter++;
					} else {
						$my_session_result = SessionManager::get_session_by_name($session_name);
						if ($my_session_result == false) {

							// Creating a session.
							$sql_session = "INSERT IGNORE INTO $tbl_session SET
									name = '$session_name',
									id_coach = '$coach_id',
									date_start = '$date_start',
									date_end = '$date_end'";
							$rs_session = Database::query($sql_session, __FILE__, __LINE__);
							// We get the last insert id.
							$my_session_result = SessionManager::get_session_by_name($session_name);
							$session_id = $my_session_result['id'];
							//echo '<br>';
						} else {
							// The session already exists, update it then.
							$sql_session = "UPDATE $tbl_session SET
									id_coach = '$coach_id',
									date_start = '$date_start',
									date_end = '$date_end'
								WHERE name = '$session_name'";
							$rs_session = Database::query($sql_session, __FILE__, __LINE__);
							$session_id = Database::query("SELECT id FROM $tbl_session WHERE name='$session_name'", __FILE__, __LINE__);
							list($session_id) = Database::fetch_array($session_id);
							Database::query("DELETE FROM $tbl_session_user WHERE id_session='$session_id'", __FILE__, __LINE__);
							Database::query("DELETE FROM $tbl_session_course WHERE id_session='$session_id'", __FILE__, __LINE__);
							Database::query("DELETE FROM $tbl_session_course_user WHERE id_session='$session_id'", __FILE__, __LINE__);
						}
						$session_counter++;
					}

					$users = explode('|', $enreg['Users']);

					// Adding the relationship "Session - User".
					if (is_array($users)) {
						foreach ($users as $user) {
							$user_id = UserManager::get_user_id_from_username($user);
							if ($user_id !== false) {
								// Insert new users.
								$sql = "INSERT IGNORE INTO $tbl_session_user SET
										id_user='$user_id',
										id_session = '$session_id'";
								$rs_user = Database::query($sql, __FILE__, __LINE__);
								$user_counter++;
							}
						}
					}

					$courses = explode('|', $enreg['Courses']);

					foreach ($courses as $course) {
						$course_code = api_strtoupper(api_substr($course, 0, api_strpos($course, '[')));
						if (CourseManager::course_exists($course_code)) {

							// If the course exists we continue
							$course_info = CourseManager::get_course_information($course_code);

							$coach = api_strstr($course, '[');
							$coach = api_substr($coach, 1, api_strpos($coach,']') - 1);

							if (!empty($coach)) {
								$coach_id = UserManager::get_user_id_from_username($coach);
								if ($coach_id === false) {
									$coach_id = '';
								}
							} else {
								$coach = '';
							}
							// Adding the course to a session
							$sql_course = "INSERT IGNORE INTO $tbl_session_course SET
									course_code = '$course_code',
									id_coach='$coach_id',
									id_session='$session_id'";
							$rs_course = Database::query($sql_course, __FILE__, __LINE__);
							$course_counter++;

							$users = api_substr($course, api_strpos($course, '[', 1) + 1 , api_strpos($course, ']', 1));
							$users = explode('|', $enreg['Users']);
							$users_in_course_counter = 0;

							// Adding the relationship "Session - Course - User".
							foreach ($users as $user) {
								$user_id = UserManager::get_user_id_from_username($user);
								if ($user_id !== false) {
									$sql = "INSERT IGNORE INTO $tbl_session_course_user SET
											id_user='$user_id',
											course_code='$course_code',
											id_session = '$session_id'";
									$rs_users = Database::query($sql, __FILE__, __LINE__);
									$users_in_course_counter++;
								} else {
									$error_msg .= get_lang('UserDoesNotExist').' : '.$user.'<br />';
								}
							}
							Database::query("UPDATE $tbl_session_course SET nbr_users='$users_in_course_counter' WHERE course_code='$course_code'", __FILE__, __LINE__);

							$course_info = CourseManager::get_course_information($course_code);
							$inserted_in_course[$course_code] = $course_info['title'];
						} else {
							// We should create the course as in the XML import.
							// TODO: ?
						}

						if (CourseManager::course_exists($course_code, true)) {
							$list = CourseManager :: get_courses_info_from_visual_code($course_code);
							foreach ($list as $vcourse) {
								if ($vcourse['code'] == $course_code) {
									// Ignore, this has already been inserted.
								} else {
									$coach = api_strstr($course, '[');
									$coach = api_substr($coach, 1, api_strpos($coach,']') - 1);

									if (!empty($coach)) {
										$sql_coach = "SELECT user_id FROM $tbl_user WHERE username='$coach'";
										$rs_coach = Database::query($sql_coach,__FILE__,__LINE__);
										list($coach) = (Database::fetch_array($rs_coach));
									} else {
										$coach = '';
									}
									// Adding the relationship "Session - Course".
									$sql_course = "INSERT IGNORE INTO $tbl_session_course SET
											course_code = '".$vcourse['code']."',
											id_coach='$coach',
											id_session='$session_id'";

									$rs_course = Database::query($sql_course, __FILE__, __LINE__);
									if (Database::affected_rows()){
										$course_counter++;
										$users = api_substr($course , api_strpos($course,'[', 1) + 1, api_strpos($course,']', 1));
										$users = explode('|', $enreg['Users']);
										$users_in_course_counter = 0;
										foreach ($users as $user) {
											$sqlUser = "SELECT user_id FROM $tbl_user WHERE username='".$user."'";
											$rs_user = Database::query($sqlUser);
											list($user_id) = (Database::fetch_array($rs_user));
											$sql = "INSERT INTO $tbl_session_course_user SET
													id_user='$user_id',
													course_code='".$vcourse['code']."',
													id_session = '$session_id'";
											$rs_users = Database::query($sql, __FILE__, __LINE__);
											if (Database::affected_rows()) {
												$users_in_course_counter++;
											}
										}
										Database::query("UPDATE $tbl_session_course SET nbr_users='$users_in_course_counter' WHERE course_code='".$vcourse['code']."'", __FILE__, __LINE__);
									}
								}
							}
							$inserted_in_course[$vcourse['code']] = $vcourse['title'];
						}
					}

					$sql_update_users = "UPDATE $tbl_session SET nbr_users='$user_counter', nbr_courses='$course_counter' WHERE id='$session_id'";
					Database::query($sql_update_users, __FILE__, __LINE__);
				}
			}
		}
		if (!empty($error_msg)) {
			$error_msg = get_lang('ButProblemsOccured').' :<br />'.$error_msg;
		}

		if (count($inserted_in_course) > 1) {
			$warn = get_lang('SeveralCoursesSubscribedToSessionBecauseOfSameVisualCode').': ';
			foreach ($inserted_in_course as $code => $title) {
				$warn .= ' '.$title.' ('.$code.'),';
			}
			$warn = substr($warn, 0, -1);
		}
		if ($session_counter == 1) {
			header('Location: resume_session.php?id_session='.$session_id.'&warn='.urlencode($warn));
			exit;
		} else {
			header('Location: session_list.php?action=show_message&message='.urlencode(get_lang('FileImported').' '.$error_msg).'&warn='.urlencode($warn));
			exit;
		}
	} else {
		$error_msg = get_lang('NoInputFile');
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
	$msg = substr($msg, 0, -1);
	Display::display_warning_message($msg);
}

/*

 update session by default is true
 <tr>
  <td nowrap="nowrap" valign="top"><?php echo get_lang('UpdateSession'); ?> :</td>
  <td>
	<input class="checkbox" type="checkbox" name="updatesession" id="updatesession" value="true" />
  </td>
</tr>


  */
?>

<form method="post" action="<?php echo api_get_self(); ?>" enctype="multipart/form-data" style="margin: 0px;">
<input type="hidden" name="formSent" value="1">
<div class="row"><div class="form_header"><?php echo $tool_name; ?></div></div>
<table border="0" cellpadding="5" cellspacing="0">

<?php
if (!empty($error_msg)) {
?>
<tr>
  <td colspan="2">
<?php
	Display::display_normal_message($error_msg, false);
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
	<input class="checkbox" type="radio" name="file_type" id="file_type_csv"  value="csv" <?php if ($form_sent && $file_type == 'csv') echo 'checked="checked"'; ?>> <label for="file_type_csv">CSV</label> (<a href="example_session.csv" target="_blank"><?php echo get_lang('ExampleCSVFile'); ?></a>)<br>
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
  <button class="save" type="submit" name="name" value="<?php echo get_lang('ImportSession'); ?>"><?php echo get_lang('ImportSession'); ?></button>
  </td>
</tr>
</table>
</form>

<font color="gray">
<p><?php echo get_lang('CSVMustLookLike').' ('.get_lang('MandatoryFields').')'; ?> :</p>

<blockquote>
<pre>
<b>SessionName</b>;Coach;<b>DateStart</b>;<b>DateEnd</b>;Users;Courses
<b>xxx1</b>;xxx;<b>xxx;xxx</b>;username1|username2;course1[coach1][username1,username2,...]|course2[coach1][username1,username2,...]
<b>xxx2</b>;xxx;<b>xxx;xxx</b>;username1|username2;course1[coach1][username1,username2,...]|course2[coach1][username1,username2,...]
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
