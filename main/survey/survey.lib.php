<?php
/* For licensing terms, see /license.txt */

/**
 * Class SurveyManager
 * @package chamilo.survey
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University:
 * cleanup, refactoring and rewriting large parts (if not all) of the code
 * @author Julio Montoya <gugli100@gmail.com>, Personality Test modification
 * and rewriting large parts of the code
 * @author cfasanando
 * @todo move this file to inc/lib
 * @todo use consistent naming for the functions (save vs store for instance)
 */
class SurveyManager
{
    /**
     * @param $code
     *
     * @return string
     */
    public static function generate_unique_code($code)
    {
        if (empty($code)) {
            return false;
        }
        $course_id = api_get_course_int_id();
        $table_survey = Database::get_course_table(TABLE_SURVEY);
        $code = Database::escape_string($code);
        $num = 0;
        $new_code = $code;
        while (true) {
            $sql = "SELECT * FROM $table_survey
                    WHERE code = '$new_code' AND c_id = $course_id";
            $result = Database::query($sql);
            if (Database::num_rows($result)) {
                $num++;
                $new_code = $code . $num;
            } else {
                break;
            }
        }
        return $code.$num;
    }

    /**
     * Deletes all survey invitations of a user
     * @param int $user_id
     *
     * @return boolean
     * @assert ('') === false
     */
    public static function delete_all_survey_invitations_by_user($user_id)
    {
        $user_id = intval($user_id);

        if (empty($user_id)) {
            return false;
        }
        $table_survey_invitation = Database :: get_course_table(TABLE_SURVEY_INVITATION);
        $table_survey = Database :: get_course_table(TABLE_SURVEY);

        $sql = "SELECT survey_invitation_id, survey_code
                FROM $table_survey_invitation WHERE user = '$user_id' AND c_id <> 0 ";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result ,'ASSOC')){
            $survey_invitation_id = $row['survey_invitation_id'];
            $survey_code = $row['survey_code'];
            $sql2 = "DELETE FROM $table_survey_invitation
                    WHERE survey_invitation_id = '$survey_invitation_id' AND c_id <> 0";
            if (Database::query($sql2)) {
                $sql3 = "UPDATE $table_survey SET
                            invited = invited-1
                        WHERE c_id <> 0 AND code ='$survey_code'";
                Database::query($sql3);
            }
        }
    }

    /**
     *
     * @param string $course_code
     * @param int $session_id
     * @return type
     * @assert ('') === false
     */
    public static function get_surveys($course_code, $session_id = 0)
    {
        $table_survey = Database :: get_course_table(TABLE_SURVEY);
        if (empty($course_code)) {
            return false;
        }
        $course_info = api_get_course_info($course_code);
        $session_condition = api_get_session_condition($session_id, true, true);

        $sql = "SELECT * FROM $table_survey
                WHERE c_id = {$course_info['real_id']} $session_condition ";
        $result = Database::query($sql);
        $result = Database::store_result($result, 'ASSOC');
        return $result;
    }

    /**
     * Retrieves all the survey information
     *
     * @param integer $survey_id the id of the survey
     * @param boolean $shared this parameter determines if
     * we have to get the information of a survey from the central (shared) database or from the
     * 		  course database
     * @param string course code optional
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version February 2007
     * @assert ('') === false
     *
     * @todo this is the same function as in create_new_survey.php
     */
    public static function get_survey($survey_id, $shared = 0, $course_code = '', $simple_return = false)
    {
        // Table definition
        if (!empty($course_code)) {
            $my_course_id = $course_code;
        } else if (isset($_GET['course'])) {
            $my_course_id = Security::remove_XSS($_GET['course']);
        } else {
            $my_course_id = api_get_course_id();
        }
        $my_course_info = api_get_course_info($my_course_id);
        $table_survey = Database :: get_course_table(TABLE_SURVEY);

        if ($shared != 0) {
            $table_survey	= Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION);
            $sql = "SELECT * FROM $table_survey
                    WHERE survey_id='".intval($survey_id)."' ";
        } else {
            $sql = "SELECT * FROM $table_survey
		            WHERE
		                survey_id='".intval($survey_id)."' AND
		                c_id = ".$my_course_info['real_id'];
        }

        $result = Database::query($sql);
        $return = array();

        if (Database::num_rows($result)> 0) {
            $return = Database::fetch_array($result,'ASSOC');
            if ($simple_return) {
                return $return;
            }
            // We do this (temporarily) to have the array match the quickform elements immediately
            // idealiter the fields in the db match the quickform fields
            $return['survey_code'] = $return['code'];
            $return['survey_title'] = $return['title'];
            $return['survey_subtitle'] = $return['subtitle'];
            $return['survey_language'] = $return['lang'];
            $return['start_date'] = $return['avail_from'];
            $return['end_date'] = $return['avail_till'];
            $return['survey_share'] = $return['is_shared'];
            $return['survey_introduction'] = $return['intro'];
            $return['survey_thanks'] = $return['surveythanks'];
            $return['survey_type'] = $return['survey_type'];
            $return['one_question_per_page'] = $return['one_question_per_page'];
            $return['show_form_profile'] = $return['show_form_profile'];
            $return['input_name_list'] = isset($return['input_name_list']) ? $return['input_name_list'] : null;
            $return['shuffle'] = $return['shuffle'];
            $return['parent_id'] = $return['parent_id'];
            $return['survey_version'] = $return['survey_version'];
            $return['anonymous'] = $return['anonymous'];
        }

        return $return;
    }

    /**
     * This function stores a survey in the database.
     *
     * @param array $values
     *
     * @return array $return the type of return message that has to be displayed and the message in it
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version February 2007
     */
    public static function store_survey($values)
    {
        $_user = api_get_user_info();
        $course_id = api_get_course_int_id();
        $session_id = api_get_session_id();
        $courseCode = api_get_course_id();
        $table_survey 	= Database :: get_course_table(TABLE_SURVEY);
        $shared_survey_id = 0;

        if (!isset($values['survey_id'])) {
            // Check if the code doesn't soon exists in this language
            $sql = 'SELECT 1 FROM '.$table_survey.'
			        WHERE
			            c_id = '.$course_id.' AND
			            code="'.Database::escape_string($values['survey_code']).'" AND
			            lang="'.Database::escape_string($values['survey_language']).'"';
            $rs = Database::query($sql);
            if (Database::num_rows($rs) > 0) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('ThisSurveyCodeSoonExistsInThisLanguage'),
                        'error'
                    )
                );
                $return['type'] = 'error';
                $return['id'] = isset($values['survey_id']) ? $values['survey_id'] : 0;

                return $return;
            }

            if (!isset($values['anonymous'])) {
                $values['anonymous'] = 0;
            }

            $values['anonymous'] = intval($values['anonymous']);
            $additional['columns'] = '';
            $extraParams = [];

            if ($values['anonymous'] == 0) {
                // Input_name_list
                $values['show_form_profile'] = isset($values['show_form_profile']) ? $values['show_form_profile'] : 0;
                $extraParams['show_form_profile'] = $values['show_form_profile'];

                if ($values['show_form_profile'] == 1) {
                    // Input_name_list
                    $fields = explode(',', $values['input_name_list']);
                    $field_values = '';
                    foreach ($fields as & $field) {
                        if ($field != '') {
                            if ($values[$field] == '') {
                                $values[$field] = 0;
                            }
                            $field_values.= $field.':'.$values[$field].'@';
                        }
                    }
                    $extraParams['form_fields'] = $field_values;
                } else {
                    $extraParams['form_fields'] = '';
                }
            } else {
                // Input_name_list
                $extraParams['show_form_profile'] = 0;
                $extraParams['form_fields'] = '';
            }

            if ($values['survey_type'] == 1) {
                $extraParams['survey_type'] = 1;
                $extraParams['shuffle'] = $values['shuffle'];
                $extraParams['one_question_per_page'] = $values['one_question_per_page'];
                $extraParams['parent_id'] = $values['parent_id'];

                // Logic for versioning surveys
                if (!empty($values['parent_id'])) {
                    $versionValue = '';
                    $sql = 'SELECT survey_version
                            FROM '.$table_survey.'
					        WHERE
					            c_id = '.$course_id.' AND
					            parent_id = '.intval($values['parent_id']).'
                            ORDER BY survey_version DESC
                            LIMIT 1';
                    $rs = Database::query($sql);
                    if (Database::num_rows($rs) === 0) {
                        $sql = 'SELECT survey_version FROM '.$table_survey.'
						        WHERE
						            c_id = '.$course_id.' AND
						            survey_id = '.intval($values['parent_id']);
                        $rs = Database::query($sql);
                        $getversion = Database::fetch_array($rs, 'ASSOC');
                        if (empty($getversion['survey_version'])) {
                            $versionValue = ++$getversion['survey_version'];
                        } else {
                            $versionValue = $getversion['survey_version'];
                        }
                    } else {
                        $row = Database::fetch_array($rs, 'ASSOC');
                        $pos = api_strpos($row['survey_version']);
                        if ($pos === false) {
                            $row['survey_version'] = $row['survey_version'] + 1;
                            $versionValue = $row['survey_version'];
                        } else {
                            $getlast = explode('\.', $row['survey_version']);
                            $lastversion = array_pop($getlast);
                            $lastversion = $lastversion + 1;
                            $add = implode('.', $getlast);
                            if ($add != '') {
                                $insertnewversion = $add.'.'.$lastversion;
                            } else {
                                $insertnewversion = $lastversion;
                            }
                            $versionValue = $insertnewversion;
                        }
                    }
                    $extraParams['survey_version'] = $versionValue;
                }
            }

            $params = [
                'c_id' => $course_id,
                'code' => strtolower(CourseManager::generate_course_code($values['survey_code'])),
                'title' => $values['survey_title'],
                'subtitle' => $values['survey_subtitle'],
                'author' => $_user['user_id'],
                'lang' => $values['survey_language'],
                'avail_from' => $values['start_date'],
                'avail_till' => $values['end_date'],
                'is_shared' => $shared_survey_id,
                'template' => 'template',
                'intro' => $values['survey_introduction'],
                'surveythanks' => $values['survey_thanks'],
                'creation_date' => api_get_utc_datetime(),
                'anonymous' => $values['anonymous'],
                'session_id' => api_get_session_id(),
                'visible_results' => $values['visible_results']
            ];

            $params = array_merge($params, $extraParams);
            $survey_id = Database::insert($table_survey, $params);
            if ($survey_id > 0) {

                $sql = "UPDATE $table_survey SET survey_id = $survey_id
                        WHERE iid = $survey_id";
                Database::query($sql);

                // Insert into item_property
                api_item_property_update(
                    api_get_course_info(),
                    TOOL_SURVEY,
                    $survey_id,
                    'SurveyAdded',
                    api_get_user_id()
                );
            }

            if ($values['survey_type'] == 1 && !empty($values['parent_id'])) {
                SurveyManager::copy_survey($values['parent_id'], $survey_id);
            }

            Display::addFlash(
                Display::return_message(
                    get_lang('SurveyCreatedSuccesfully'),
                    'success'
                )
            );
            $return['id'] = $survey_id;
        } else {
            // Check whether the code doesn't soon exists in this language
            $sql = 'SELECT 1 FROM '.$table_survey.'
			        WHERE
			            c_id = '.$course_id.' AND
			            code = "'.Database::escape_string($values['survey_code']).'" AND
			            lang = "'.Database::escape_string($values['survey_language']).'" AND
			            survey_id !='.intval($values['survey_id']);
            $rs = Database::query($sql);
            if (Database::num_rows($rs) > 0) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('ThisSurveyCodeSoonExistsInThisLanguage'),
                        'error'
                    )
                );
                $return['type'] = 'error';
                $return['id'] = isset($values['survey_id']) ? $values['survey_id'] : 0;
                return $return;
            }

            if (!isset($values['anonymous']) ||
                (isset($values['anonymous']) && $values['anonymous'] == '')
            ) {
                $values['anonymous'] = 0;
            }

            $values['shuffle'] = isset($values['shuffle']) ? $values['shuffle'] : null;
            $values['one_question_per_page'] = isset($values['one_question_per_page']) ? $values['one_question_per_page'] : null;
            $values['show_form_profile'] = isset($values['show_form_profile']) ? $values['show_form_profile'] : null;

            $extraParams = [];
            $extraParams['shuffle'] = $values['shuffle'];
            $extraParams['one_question_per_page'] = $values['one_question_per_page'];
            $extraParams['shuffle'] = $values['shuffle'];

            if ($values['anonymous'] == 0) {
                $extraParams['show_form_profile'] = $values['show_form_profile'];
                if ($values['show_form_profile'] == 1) {
                    $fields = explode(',',$values['input_name_list']);
                    $field_values = '';
                    foreach ($fields as &$field) {
                        if ($field != '') {
                            if (!isset($values[$field]) ||
                                (isset($values[$field]) && $values[$field] == '')
                            ) {
                                $values[$field] = 0;
                            }
                            $field_values.= $field.':'.$values[$field].'@';
                        }
                    }
                    $extraParams['form_fields'] = $field_values;
                } else {
                    $extraParams['form_fields'] = '';
                }
            } else {
                $extraParams['show_form_profile'] = 0;
                $extraParams['form_fields'] = '';
            }

            $params = [
                'title' => $values['survey_title'],
                'subtitle' => $values['survey_subtitle'],
                'author' => $_user['user_id'],
                'lang' => $values['survey_language'],
                'avail_from' => $values['start_date'],
                'avail_till' => $values['end_date'],
                'is_shared' => $shared_survey_id,
                'template' => 'template',
                'intro' => $values['survey_introduction'],
                'surveythanks' => $values['survey_thanks'],
                'anonymous' => $values['anonymous'],
                'session_id' => api_get_session_id(),
                'visible_results' => $values['visible_results'],
            ];

            $params = array_merge($params, $extraParams);
            Database::update(
                $table_survey,
                $params,
                [
                    'c_id = ? AND survey_id = ?' => [
                        $course_id,
                        $values['survey_id'],
                    ],
                ]
            );

            // Update into item_property (update)
            api_item_property_update(
                api_get_course_info(),
                TOOL_SURVEY,
                $values['survey_id'],
                'SurveyUpdated',
                api_get_user_id()
            );

            Display::addFlash(
                Display::return_message(
                    get_lang('SurveyUpdatedSuccesfully'),
                    'confirmation'
                )
            );

            $return['id'] = $values['survey_id'];
        }

        $survey_id = intval($return['id']);

        // Gradebook
        $gradebook_option = false;
        if (isset($values['survey_qualify_gradebook'])) {
            $gradebook_option = $values['survey_qualify_gradebook'] > 0;
        }

        $gradebook_link_type = 8;

        $link_info = GradebookUtils::isResourceInCourseGradebook(
            $courseCode,
            $gradebook_link_type,
            $survey_id,
            $session_id
        );

        $gradebook_link_id = isset($link_info['id']) ? $link_info['id'] : false;

        if ($gradebook_option) {
            if ($survey_id > 0) {
                $title_gradebook = ''; // Not needed here.
                $description_gradebook = ''; // Not needed here.
                $survey_weight = floatval($_POST['survey_weight']);
                $max_score = 1;

                if (!$gradebook_link_id) {
                    GradebookUtils::add_resource_to_course_gradebook(
                        $values['category_id'],
                        $courseCode,
                        $gradebook_link_type,
                        $survey_id,
                        $title_gradebook,
                        $survey_weight,
                        $max_score,
                        $description_gradebook,
                        1,
                        $session_id
                    );
                } else {
                    GradebookUtils::update_resource_from_course_gradebook(
                        $gradebook_link_id,
                        $courseCode,
                        $survey_weight
                    );
                }
            }
        } else {
            // Delete everything of the gradebook for this $linkId
            GradebookUtils::remove_resource_from_course_gradebook($gradebook_link_id);

            //comenting this line to correctly return the function msg
            //exit;
        }

        return $return;
    }

    /**
     * This function stores a shared survey in the central database.
     *
     * @param array $values
     * @return array $return the type of return message that has to be displayed and the message in it
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version February 2007
     */
    public function store_shared_survey($values)
    {
        $_user = api_get_user_info();
        $_course = api_get_course_info();

        // Table definitions
        $table_survey	= Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY);

        if (!$values['survey_id'] ||
            !is_numeric($values['survey_id']) ||
            $values['survey_share']['survey_share'] == 'true'
        ) {
            $sql = "INSERT INTO $table_survey (code, title, subtitle, author, lang, template, intro, surveythanks, creation_date, course_code) VALUES (
                    '".Database::escape_string($values['survey_code'])."',
                    '".Database::escape_string($values['survey_title'])."',
                    '".Database::escape_string($values['survey_subtitle'])."',
                    '".intval($_user['user_id'])."',
                    '".Database::escape_string($values['survey_language'])."',
                    '".Database::escape_string('template')."',
                    '".Database::escape_string($values['survey_introduction'])."',
                    '".Database::escape_string($values['survey_thanks'])."',
                    '".api_get_utc_datetime()."',
                    '".$_course['id']."')";
            Database::query($sql);
            $return	= Database::insert_id();

            $sql = "UPDATE $table_survey SET survey_id = $return WHERE iid = $return";
            Database::query($sql);

        } else {
            $sql = "UPDATE $table_survey SET
                        code 			= '".Database::escape_string($values['survey_code'])."',
                        title 			= '".Database::escape_string($values['survey_title'])."',
                        subtitle 		= '".Database::escape_string($values['survey_subtitle'])."',
                        author 			= '".intval($_user['user_id'])."',
                        lang 			= '".Database::escape_string($values['survey_language'])."',
                        template 		= '".Database::escape_string('template')."',
                        intro			= '".Database::escape_string($values['survey_introduction'])."',
                        surveythanks	= '".Database::escape_string($values['survey_thanks'])."'
					WHERE survey_id = '".Database::escape_string($values['survey_share']['survey_share'])."'";
            Database::query($sql);
            $return	= $values['survey_share']['survey_share'];
        }

        return $return;
    }

    /**
     * This function deletes a survey (and also all the question in that survey
     *
     * @param int $survey_id id of the survey that has to be deleted
     * @return true
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version January 2007
     */
    public static function delete_survey($survey_id, $shared = false, $course_id = '')
    {
        // Database table definitions
        if (empty($course_id)) {
            $course_id = api_get_course_int_id();
        }

        $survey_id = intval($survey_id);

        if (empty($survey_id)) {
            return false;
        }

        $course_info = api_get_course_info_by_id($course_id);
        $course_id   = $course_info['real_id'];

        $table_survey = Database :: get_course_table(TABLE_SURVEY);
        $table_survey_question_group = Database :: get_course_table(TABLE_SURVEY_QUESTION_GROUP);

        if ($shared) {
            $table_survey = Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY);
            // Deleting the survey
            $sql = "DELETE FROM $table_survey
                    WHERE survey_id='".$survey_id."'";
            Database::query($sql);
        } else {
            $sql = "DELETE FROM $table_survey
                    WHERE c_id = $course_id AND survey_id='".$survey_id."'";
            Database::query($sql);
        }

        // Deleting groups of this survey
        $sql = "DELETE FROM $table_survey_question_group
                WHERE c_id = $course_id AND survey_id='".$survey_id."'";
        Database::query($sql);

        // Deleting the questions of the survey
        SurveyManager::delete_all_survey_questions($survey_id, $shared);

        // Update into item_property (delete)
        api_item_property_update(
            $course_info,
            TOOL_SURVEY,
            $survey_id,
            'SurveyDeleted',
            api_get_user_id()
        );
        return true;
    }

    /**
     * @param int $survey_id
     * @param int $new_survey_id
     * @param int $targetCourseId
     *
     * @return bool
     */
    public static function copy_survey($survey_id, $new_survey_id = null, $targetCourseId = null)
    {
        $course_id = api_get_course_int_id();
        if (!$targetCourseId) {
            $targetCourseId = $course_id;
        }

        // Database table definitions
        $table_survey = Database::get_course_table(TABLE_SURVEY);
        $table_survey_question_group = Database::get_course_table(TABLE_SURVEY_QUESTION_GROUP);
        $table_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $table_survey_options = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
        $survey_id = intval($survey_id);

        // Get groups
        $survey_data = self::get_survey($survey_id, 0, null, true);
        if (empty($survey_data)) {
            return true;
        }

        if (empty($new_survey_id)) {
            $params = $survey_data;
            $params['code'] = self::generate_unique_code($params['code']);
            $params['c_id'] = $targetCourseId;
            unset($params['survey_id']);
            $params['session_id'] = api_get_session_id();
            $params['title'] = $params['title'] . ' ' . get_lang('Copy');
            unset($params['iid']);
            Database::insert($table_survey, $params);
            $new_survey_id = Database::insert_id();

            if ($new_survey_id) {
                $sql = "UPDATE $table_survey SET survey_id = $new_survey_id
                        WHERE iid = $new_survey_id";
                Database::query($sql);

                // Insert into item_property
                api_item_property_update(
                    api_get_course_info(),
                    TOOL_SURVEY,
                    $new_survey_id,
                    'SurveyAdded',
                    api_get_user_id()
                );
            }
        } else {
            $new_survey_id = intval($new_survey_id);
        }

        $sql = "SELECT * FROM $table_survey_question_group
                WHERE c_id = $course_id AND  survey_id='".$survey_id."'";
        $res = Database::query($sql);
        while($row = Database::fetch_array($res, 'ASSOC')) {
            $params = array(
                'c_id' =>  $targetCourseId,
                'name' => $row['name'],
                'description' => $row['description'],
                'survey_id' => $new_survey_id
            );
            $insertId = Database::insert($table_survey_question_group, $params);

            $sql = "UPDATE $table_survey_question_group SET id = iid
                    WHERE iid = $insertId";
            Database::query($sql);

            $group_id[$row['id']] = $insertId;
        }

        // Get questions
        $sql = "SELECT * FROM $table_survey_question
                WHERE c_id = $course_id AND survey_id='".$survey_id."'";
        $res = Database::query($sql);
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $params = array(
                'c_id' =>  $targetCourseId,
                'survey_id' => $new_survey_id,
                'survey_question' => $row['survey_question'],
                'survey_question_comment' => $row['survey_question_comment'],
                'type' => $row['type'],
                'display' => $row['display'],
                'sort' => $row['sort'],
                'shared_question_id' =>  $row['shared_question_id'],
                'max_value' =>  $row['max_value'],
                'survey_group_pri' =>  $row['survey_group_pri'],
                'survey_group_sec1' =>  $row['survey_group_sec1'],
                'survey_group_sec2' => $row['survey_group_sec2']
            );
            $insertId = Database::insert($table_survey_question, $params);
            $sql = "UPDATE $table_survey_question SET question_id = iid WHERE iid = $insertId";
            Database::query($sql);

            $question_id[$row['question_id']] = $insertId;
        }

        // Get questions options
        $sql = "SELECT * FROM $table_survey_options
                WHERE c_id = $course_id AND survey_id='".$survey_id."'";

        $res = Database::query($sql);
        while ($row = Database::fetch_array($res ,'ASSOC')) {
            $params = array(
                'c_id' =>  $targetCourseId,
                'question_id' => $question_id[$row['question_id']],
                'survey_id' => $new_survey_id,
                'option_text' => $row['option_text'],
                'sort' => $row['sort'],
                'value' => $row['value']
            );
            $insertId = Database::insert($table_survey_options, $params);

            $sql = "UPDATE $table_survey_options SET question_option_id = $insertId
                    WHERE iid = $insertId";
            Database::query($sql);
        }

        return $new_survey_id;
    }

    /**
     * This function duplicates a survey (and also all the question in that survey
     *
     * @param int $survey_id id of the survey that has to be duplicated
     * @param int $courseId id of the course which survey has to be duplicated
     * @return true
     *
     * @author Eric Marguin <e.marguin@elixir-interactive.com>, Elixir Interactive
     * @version October 2007
     */
    public static function empty_survey($survey_id, $courseId = null)
    {
        // Database table definitions
        $table_survey_invitation = Database :: get_course_table(TABLE_SURVEY_INVITATION);
        $table_survey_answer = Database :: get_course_table(TABLE_SURVEY_ANSWER);
        $table_survey = Database :: get_course_table(TABLE_SURVEY);

        $course_id = $courseId ? $courseId : api_get_course_int_id();

        $datas = SurveyManager::get_survey($survey_id);
        $session_where = '';
        if (api_get_session_id() != 0) {
            $session_where = ' AND session_id = "'.api_get_session_id().'" ';
        }

        $sql = 'DELETE FROM '.$table_survey_invitation.'
		        WHERE
		            c_id = '.$course_id.' AND
		            survey_code = "'.Database::escape_string($datas['code']).'" '.$session_where.' ';
        Database::query($sql);

        $sql = 'DELETE FROM '.$table_survey_answer.'
		        WHERE c_id = '.$course_id.' AND survey_id='.intval($survey_id);
        Database::query($sql);

        $sql = 'UPDATE '.$table_survey.' SET invited=0, answered=0
		        WHERE c_id = '.$course_id.' AND survey_id='.intval($survey_id);
        Database::query($sql);

        return true;
    }

    /**
     * This function recalculates the number of people who have taken the survey (=filled at least one question)
     *
     * @param int $survey_id the id of the survey somebody
     * @return true
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version February 2007
     */
    public static function update_survey_answered($survey_data, $user, $survey_code)
    {
        // Database table definitions
        $table_survey = Database :: get_course_table(TABLE_SURVEY);
        $table_survey_invitation = Database :: get_course_table(TABLE_SURVEY_INVITATION);

        $survey_id = $survey_data['survey_id'];
        $course_id = $survey_data['c_id'];
        $session_id = $survey_data['session_id'];

        // Getting a list with all the people who have filled the survey
        $people_filled = SurveyManager::get_people_who_filled_survey($survey_id, false, $course_id);

        $number = intval(count($people_filled));

        // Storing this value in the survey table
        $sql = "UPDATE $table_survey
		        SET answered = $number
		        WHERE
                    c_id = $course_id AND
		            survey_id = ".intval($survey_id);
        Database::query($sql);

        // Storing that the user has finished the survey.
        $sql = "UPDATE $table_survey_invitation SET answered='1'
                WHERE
                    c_id = $course_id AND
                    session_id='".$session_id."' AND
                    user='".Database::escape_string($user)."' AND
                    survey_code='".Database::escape_string($survey_code)."'";
        Database::query($sql);
    }

    /***
     * SURVEY QUESTION FUNCTIONS
     */

    /**
     * This function return the "icon" of the question type
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version February 2007
     */
    public static function icon_question($type)
    {
        // the possible question types
        $possible_types = array(
            'personality',
            'yesno',
            'multiplechoice',
            'multipleresponse',
            'open',
            'dropdown',
            'comment',
            'pagebreak',
            'percentage',
            'score',
        );

        // the images array
        $icon_question = array(
            'yesno' => 'yesno.png',
            'personality' => 'yesno.png',
            'multiplechoice' => 'mcua.png',
            'multipleresponse' => 'mcma.png',
            'open' => 'open_answer.png',
            'dropdown' => 'dropdown.png',
            'percentage' => 'percentagequestion.png',
            'score' => 'scorequestion.png',
            'comment' => 'commentquestion.png',
            'pagebreak' => 'page_end.png',
        );

        if (in_array($type, $possible_types)) {
            return $icon_question[$type];
        } else {
            return false;
        }
    }

    /**
     * This function retrieves all the information of a question
     *
     * @param integer $question_id the id of the question
     * @return array
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version January 2007
     *
     * @todo one sql call should do the trick
     */
    public static function get_question($question_id, $shared = false)
    {
        // Table definitions
        $tbl_survey_question = Database :: get_course_table(TABLE_SURVEY_QUESTION);
        $table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
        $course_id = api_get_course_int_id();

        $sql = "SELECT * FROM $tbl_survey_question
                WHERE c_id = $course_id AND question_id='".intval($question_id)."'
                ORDER BY `sort` ";

        $sqlOption = "  SELECT * FROM $table_survey_question_option
                        WHERE c_id = $course_id AND question_id='".intval($question_id)."'
                        ORDER BY `sort` ";

        if ($shared) {
            $tbl_survey_question = Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION);
            $table_survey_question_option = Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION_OPTION);

            $sql = "SELECT * FROM $tbl_survey_question
                    WHERE question_id='".intval($question_id)."'
                    ORDER BY `sort` ";
            $sqlOption = "SELECT * FROM $table_survey_question_option
                          WHERE question_id='".intval($question_id)."'
                          ORDER BY `sort` ";
        }

        // Getting the information of the question

        $result = Database::query($sql);
        $row = Database::fetch_array($result,'ASSOC');

        $return['survey_id'] = $row['survey_id'];
        $return['question_id'] = $row['question_id'];
        $return['type'] = $row['type'];
        $return['question'] = $row['survey_question'];
        $return['horizontalvertical'] = $row['display'];
        $return['shared_question_id'] = $row['shared_question_id'];
        $return['maximum_score'] = $row['max_value'];

        if ($row['survey_group_pri'] != 0) {
            $return['assigned'] = $row['survey_group_pri'];
            $return['choose'] = 1;
        } else {
            $return['assigned1'] = $row['survey_group_sec1'];
            $return['assigned2'] = $row['survey_group_sec2'];
            $return['choose'] = 2;
        }

        // Getting the information of the question options

        $result = Database::query($sqlOption);
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            /** @todo this should be renamed to options instead of answers */
            $return['answers'][] = $row['option_text'];
            $return['values'][] = $row['value'];

            /** @todo this can be done more elegantly (used in reporting) */
            $return['answersid'][] = $row['question_option_id'];
        }

        return $return;
    }

    /**
     * This function gets all the question of any given survey
     *
     * @param integer $survey_id the id of the survey
     * @return array containing all the questions of the survey
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version February 2007
     *
     * @todo one sql call should do the trick
     */
    public static function get_questions($survey_id, $course_id = '')
    {
        // Table definitions
        $tbl_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION);
        $table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);

        if (empty($course_id)) {
            $course_id = api_get_course_int_id();
        }

        $return = array();

        // Getting the information of the question
        $sql = "SELECT * FROM $tbl_survey_question
		        WHERE c_id = $course_id AND survey_id='".intval($survey_id)."'";
        $result = Database::query($sql);
        $return = array();
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $return[$row['question_id']]['survey_id'] = $row['survey_id'];
            $return[$row['question_id']]['question_id'] = $row['question_id'];
            $return[$row['question_id']]['type'] = $row['type'];
            $return[$row['question_id']]['question'] = $row['survey_question'];
            $return[$row['question_id']]['horizontalvertical'] = $row['display'];
            $return[$row['question_id']]['maximum_score'] = $row['max_value'];
            $return[$row['question_id']]['sort'] = $row['sort'];
        }

        // Getting the information of the question options
        $sql = "SELECT * FROM $table_survey_question_option
		        WHERE c_id = $course_id AND survey_id='".intval($survey_id)."'";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $return[$row['question_id']]['answers'][] = $row['option_text'];
        }

        return $return;
    }

    /**
     * This function saves a question in the database.
     * This can be either an update of an existing survey or storing a new survey
     * @param array $survey_data
     * @param array $form_content all the information of the form
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version January 2007
     */
    public static function save_question($survey_data, $form_content)
    {
        $return_message = '';

        if (strlen($form_content['question']) > 1) {
            // Checks length of the question
            $empty_answer = false;

            if ($survey_data['survey_type'] == 1) {
                if (empty($form_content['choose'])) {
                    $return_message = 'PleaseChooseACondition';
                    return $return_message;
                }

                if (($form_content['choose'] == 2) &&
                    ($form_content['assigned1'] == $form_content['assigned2'])
                ) {
                    $return_message = 'ChooseDifferentCategories';
                    return $return_message;
                }
            }

            if ($form_content['type'] != 'percentage') {
                if (isset($form_content['answers'])) {
                    for ($i = 0; $i < count($form_content['answers']); $i++) {
                        if (strlen($form_content['answers'][$i]) < 1) {
                            $empty_answer = true;
                            break;
                        }
                    }
                }
            }

            if ($form_content['type'] == 'score') {
                if (strlen($form_content['maximum_score']) < 1) {
                    $empty_answer = true;
                }
            }
            $additional = array();
            $course_id = api_get_course_int_id();

            if (!$empty_answer) {
                // Table definitions
                $tbl_survey_question = Database :: get_course_table(TABLE_SURVEY_QUESTION);

                // Getting all the information of the survey
                $survey_data = SurveyManager::get_survey($form_content['survey_id']);

                // Storing the question in the shared database
                if (is_numeric($survey_data['survey_share']) && $survey_data['survey_share'] != 0) {
                    $shared_question_id = SurveyManager::save_shared_question($form_content, $survey_data);
                    $form_content['shared_question_id'] = $shared_question_id;
                }

                // Storing a new question
                if ($form_content['question_id'] == '' || !is_numeric($form_content['question_id'])) {
                    // Finding the max sort order of the questions in the given survey
                    $sql = "SELECT max(sort) AS max_sort
					        FROM $tbl_survey_question
                            WHERE c_id = $course_id AND survey_id='".intval($form_content['survey_id'])."'";
                    $result = Database::query($sql);
                    $row = Database::fetch_array($result,'ASSOC');
                    $max_sort = $row['max_sort'];

                    // Some variables defined for survey-test type
                    $extraParams = [];
                    if (isset($_POST['choose'])) {
                        if ($_POST['choose'] == 1) {
                            $extraParams['survey_group_pri'] = $_POST['assigned'];
                        } elseif ($_POST['choose'] == 2) {
                            $extraParams['survey_group_sec1'] = $_POST['assigned1'];
                            $extraParams['survey_group_sec2'] = $_POST['assigned2'];
                        }
                    }

                    $questionComment = isset($form_content['question_comment']) ? $form_content['question_comment'] : '';
                    $maxScore = isset($form_content['maximum_score']) ? $form_content['maximum_score'] : '';
                    $display = isset($form_content['horizontalvertical']) ? $form_content['horizontalvertical'] : '';

                    $params = [
                        'c_id' => $course_id,
                        'survey_id' => $form_content['survey_id'],
                        'survey_question' => $form_content['question'],
                        'survey_question_comment' => $questionComment,
                        'type' => $form_content['type'],
                        'display' => $display,
                        'sort' => $max_sort + 1,
                        'shared_question_id' => $form_content['shared_question_id'],
                        'max_value' => $maxScore,
                    ];

                    $params = array_merge($params, $extraParams);
                    $question_id = Database::insert($tbl_survey_question, $params);
                    if ($question_id) {

                        $sql = "UPDATE $tbl_survey_question SET question_id = $question_id
                                WHERE iid = $question_id";
                        Database::query($sql);

                        $form_content['question_id'] = $question_id;
                        $return_message = 'QuestionAdded';
                    }
                } else {
                    // Updating an existing question

                    $extraParams = [];

                    if (isset($_POST['choose'])) {
                        if ($_POST['choose'] == 1) {
                            $extraParams['survey_group_pri'] = $_POST['assigned'];
                            $extraParams['survey_group_sec1'] = 0;
                            $extraParams['survey_group_sec2'] = 0;
                        } elseif ($_POST['choose'] == 2) {
                            $extraParams['survey_group_pri'] = 0;
                            $extraParams['survey_group_sec1'] = $_POST['assigned1'];
                            $extraParams['survey_group_sec2'] = $_POST['assigned2'];
                        }
                    }

                    $maxScore = isset($form_content['maximum_score']) ? $form_content['maximum_score'] : null;
                    $questionComment = isset($form_content['question_comment']) ? $form_content['question_comment'] : null;

                    // Adding the question to the survey_question table
                    $params = [
                        'survey_question' => $form_content['question'],
                        'survey_question_comment' => $questionComment,
                        'display' => $form_content['horizontalvertical'],
                    ];

                    $params = array_merge($params, $extraParams);

                    Database::update(
                        $tbl_survey_question,
                        $params,
                        [
                            'c_id = ? AND question_id = ?' => [
                                $course_id,
                                $form_content['question_id'],
                            ],
                        ]
                    );

                    $return_message = 'QuestionUpdated';
                }

                if (!empty($form_content['survey_id'])) {
                    //Updating survey
                    api_item_property_update(
                        api_get_course_info(),
                        TOOL_SURVEY,
                        $form_content['survey_id'],
                        'SurveyUpdated',
                        api_get_user_id()
                    );
                }

                // Storing the options of the question
                SurveyManager::save_question_options($form_content, $survey_data);
            } else {
                $return_message = 'PleasFillAllAnswer';
            }
        } else {
            $return_message = 'PleaseEnterAQuestion';
        }

        if (!empty($return_message)) {
            Display::addFlash(Display::return_message(get_lang($return_message)));
        }
        return $return_message;
    }

    /**
    * This function saves the question in the shared database
    *
    * @param array $form_content all the information of the form
    * @param array $survey_data all the information of the survey
    *
    * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
    * @version February 2007
    *
    * @todo editing of a shared question
    */
    public function save_shared_question($form_content, $survey_data)
    {
        $_course = api_get_course_info();

        // Table definitions
        $tbl_survey_question = Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION);

        // Storing a new question
        if ($form_content['shared_question_id'] == '' ||
            !is_numeric($form_content['shared_question_id'])
        ) {
            // Finding the max sort order of the questions in the given survey
            $sql = "SELECT max(sort) AS max_sort FROM $tbl_survey_question
                    WHERE survey_id='".intval($survey_data['survey_share'])."'
                    AND code='".Database::escape_string($_course['id'])."'";
            $result = Database::query($sql);
            $row = Database::fetch_array($result,'ASSOC');
            $max_sort = $row['max_sort'];

            // Adding the question to the survey_question table
            $sql = "INSERT INTO $tbl_survey_question (survey_id, survey_question, survey_question_comment, type, display, sort, code) VALUES (
                    '".Database::escape_string($survey_data['survey_share'])."',
                    '".Database::escape_string($form_content['question'])."',
                    '".Database::escape_string($form_content['question_comment'])."',
                    '".Database::escape_string($form_content['type'])."',
                    '".Database::escape_string($form_content['horizontalvertical'])."',
                    '".Database::escape_string($max_sort+1)."',
                    '".Database::escape_string($_course['id'])."')";
            Database::query($sql);
            $shared_question_id = Database::insert_id();
        }  else {
            // Updating an existing question
            // adding the question to the survey_question table
            $sql = "UPDATE $tbl_survey_question SET
                        survey_question = '".Database::escape_string($form_content['question'])."',
                        survey_question_comment = '".Database::escape_string($form_content['question_comment'])."',
                        display = '".Database::escape_string($form_content['horizontalvertical'])."'
                    WHERE
                        question_id = '".intval($form_content['shared_question_id'])."' AND
                        code = '".Database::escape_string($_course['id'])."'";
            Database::query($sql);
            $shared_question_id = $form_content['shared_question_id'];
        }

        return $shared_question_id;
    }

    /**
     * This functions moves a question of a survey up or down
     *
     * @param string $direction
     * @param integer $survey_question_id
     * @param integer $survey_id
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version January 2007
     */
    public static function move_survey_question($direction, $survey_question_id, $survey_id)
    {
        // Table definition
        $table_survey_question 	= Database :: get_course_table(TABLE_SURVEY_QUESTION);
        $course_id = api_get_course_int_id();

        if ($direction == 'moveup') {
            $sort = 'DESC';
        }
        if ($direction == 'movedown') {
            $sort = 'ASC';
        }

        // Finding the two questions that needs to be swapped
        $sql = "SELECT * FROM $table_survey_question
		        WHERE c_id = $course_id AND survey_id='".Database::escape_string($survey_id)."'
		        ORDER BY sort $sort";
        $result = Database::query($sql);
        $found = false;
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            if ($found) {
                $question_id_two = $row['question_id'];
                $question_sort_two = $row['sort'];
                $found = false;
            }
            if ($row['question_id'] == $survey_question_id) {
                $found = true;
                $question_id_one = $row['question_id'];
                $question_sort_one = $row['sort'];
            }
        }

        $sql1 = "UPDATE $table_survey_question SET sort = '".Database::escape_string($question_sort_two)."'
		        WHERE c_id = $course_id AND  question_id='".intval($question_id_one)."'";
        Database::query($sql1);
        $sql2 = "UPDATE $table_survey_question SET sort = '".Database::escape_string($question_sort_one)."'
		        WHERE c_id = $course_id AND question_id='".intval($question_id_two)."'";
        Database::query($sql2);
    }

    /**
     * This function deletes all the questions of a given survey
     * This function is normally only called when a survey is deleted
     *
     * @param int $survey_id the id of the survey that has to be deleted
     * @return true
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version January 2007
     */
    public static function delete_all_survey_questions($survey_id, $shared = false)
    {
        $course_id = api_get_course_int_id();

        // Table definitions
        $table_survey_question 	= Database :: get_course_table(TABLE_SURVEY_QUESTION);
        $course_condition = " c_id = $course_id AND ";
        if ($shared) {
            $course_condition = "";
            $table_survey_question 	= Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION);
        }

        $sql = "DELETE FROM $table_survey_question
		        WHERE $course_condition survey_id='".intval($survey_id)."'";

        // Deleting the survey questions

        Database::query($sql);

        // Deleting all the options of the questions of the survey
        SurveyManager::delete_all_survey_questions_options($survey_id, $shared);

        // Deleting all the answers on this survey
        SurveyManager::delete_all_survey_answers($survey_id);
    }

    /**
     * This function deletes a survey question and all its options
     *
     * @param integer $survey_id the id of the survey
     * @param integer $question_id the id of the question
     * @param integer $shared
     *
     * @todo also delete the answers to this question
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version March 2007
     */
    public static function delete_survey_question($survey_id, $question_id, $shared = false)
    {
        $course_id = api_get_course_int_id();
        // Table definitions
        $table_survey_question 	= Database :: get_course_table(TABLE_SURVEY_QUESTION);
        if ($shared) {
            SurveyManager::delete_shared_survey_question($survey_id, $question_id);
        }

        // Deleting the survey questions
        $sql = "DELETE FROM $table_survey_question
		        WHERE
		            c_id = $course_id AND
		            survey_id='".intval($survey_id)."' AND
		            question_id='".intval($question_id)."'";
        Database::query($sql);

        // Deleting the options of the question of the survey
        SurveyManager::delete_survey_question_option($survey_id, $question_id, $shared);
    }

    /**
     * This function deletes a shared survey question from the main database and all its options
     *
     * @param int $question_id the id of the question
     * @param int $shared
     *
     * @todo delete all the options of this question
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version March 2007
     */
    public static function delete_shared_survey_question($survey_id, $question_id)
    {
        // Table definitions
        $table_survey_question 	      = Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION);
        $table_survey_question_option = Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION_OPTION);

        // First we have to get the shared_question_id
        $question_data = SurveyManager::get_question($question_id);

        // Deleting the survey questions
        $sql = "DELETE FROM $table_survey_question
		        WHERE question_id='".intval($question_data['shared_question_id'])."'";
        Database::query($sql);

        // Deleting the options of the question of the survey question
        $sql = "DELETE FROM $table_survey_question_option
		        WHERE question_id='".intval($question_data['shared_question_id'])."'";
        Database::query($sql);
    }

    /**
     * This function stores the options of the questions in the table
     *
     * @param array $form_content
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version January 2007
     *
     * @todo writing the update statement when editing a question
     */
    public static function save_question_options($form_content, $survey_data)
    {
        $course_id = api_get_course_int_id();
        // A percentage question type has options 1 -> 100
        if ($form_content['type'] == 'percentage') {
            for($i = 1; $i < 101; $i++) {
                $form_content['answers'][] = $i;
            }
        }

        if (is_numeric($survey_data['survey_share']) && $survey_data['survey_share'] != 0) {
            SurveyManager::save_shared_question_options($form_content, $survey_data);
        }

        // Table definition
        $table_survey_question_option = Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);

        // We are editing a question so we first have to remove all the existing options from the database
        if (is_numeric($form_content['question_id'])) {
            $sql = "DELETE FROM $table_survey_question_option
			        WHERE c_id = $course_id AND question_id = '".intval($form_content['question_id'])."'";
            Database::query($sql);
        }

        $counter = 1;
        if (isset($form_content['answers']) && is_array($form_content['answers'])) {
            for ($i = 0; $i < count($form_content['answers']); $i++) {
                $values = isset($form_content['values']) ? $form_content['values'][$i] : '';

                $params = [
                    'c_id' => $course_id,
                    'question_id' => $form_content['question_id'],
                    'survey_id' => $form_content['survey_id'],
                    'option_text' => $form_content['answers'][$i],
                    'value' => $values,
                    'sort' => $counter,
                ];
                $insertId = Database::insert($table_survey_question_option, $params);
                if ($insertId) {

                    $sql = "UPDATE $table_survey_question_option
                            SET question_option_id = $insertId
                            WHERE iid = $insertId";
                    Database::query($sql);

                    $counter++;
                }
            }
        }
    }

    /**
     * This function stores the options of the questions in the shared table
     *
     * @param array $form_content
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version February 2007
     *
     * @todo writing the update statement when editing a question
     */
    public function save_shared_question_options($form_content, $survey_data)
    {
        if (is_array($form_content) && is_array($form_content['answers'])) {
            // Table definition
            $table = Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION_OPTION);

            // We are editing a question so we first have to remove all the existing options from the database
            $sql = "DELETE FROM $table
                    WHERE question_id = '".Database::escape_string($form_content['shared_question_id'])."'";
            Database::query($sql);

            $counter = 1;
            foreach ($form_content['answers'] as & $answer) {
                $params = [
                    'question_id' => $form_content['shared_question_id'],
                    'survey_id' => $survey_data['is_shared'],
                    'option_text' => $answer,
                    'sort' => $counter,
                ];
                Database::insert($table, $params);

                $counter++;
            }
        }
    }

    /**
     * This function deletes all the options of the questions of a given survey
     * This function is normally only called when a survey is deleted
     *
     * @param $survey_id the id of the survey that has to be deleted
     * @return true
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version January 2007
     */
    public static function delete_all_survey_questions_options($survey_id, $shared = false)
    {
        // Table definitions
        $table_survey_question_option = Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
        $course_id = api_get_course_int_id();
        $course_condition = " c_id = $course_id AND ";
        if ($shared) {
            $course_condition = "";
            $table_survey_question_option = Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION_OPTION);
        }

        $sql = "DELETE FROM $table_survey_question_option
                WHERE $course_condition survey_id='".intval($survey_id)."'";

        // Deleting the options of the survey questions
        Database::query($sql);

        return true;
    }

    /**
     * This function deletes the options of a given question
     *
     * @param int $survey_id
     * @param int $question_id
     * @param bool $shared
     *
     * @return bool
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @author Julio Montoya
     * @version March 2007
     */
    public static function delete_survey_question_option($survey_id, $question_id, $shared = false)
    {
        $course_id = api_get_course_int_id();
        $course_condition = " c_id = $course_id AND ";

        // Table definitions
        $table_survey_question_option = Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
        if ($shared) {
            $course_condition = "";
            $table_survey_question_option = Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION_OPTION);
        }

        // Deleting the options of the survey questions
        $sql = "DELETE from $table_survey_question_option
		        WHERE
		            $course_condition survey_id='".intval($survey_id)."' AND
		            question_id='".intval($question_id)."'";
        Database::query($sql);
        return true;
    }

    /**
     * SURVEY ANSWERS FUNCTIONS
     */

    /**
     * This function deletes all the answers anyone has given on this survey
     * This function is normally only called when a survey is deleted
     *
     * @param $survey_id the id of the survey that has to be deleted
     * @return true
     *
     * @todo write the function
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version January 2007,december 2008
     */
    public static function delete_all_survey_answers($survey_id)
    {
        $course_id = api_get_course_int_id();
        $table_survey_answer = Database :: get_course_table(TABLE_SURVEY_ANSWER);
        $survey_id = intval($survey_id);
        Database::query("DELETE FROM $table_survey_answer WHERE c_id = $course_id AND survey_id=$survey_id");
        return true;
    }

    /**
     * @param int $user_id
     * @param int $survey_id
     * @param int $course_id
     * @return bool
     */
    public static function is_user_filled_survey($user_id, $survey_id, $course_id)
    {
        $table_survey_answer = Database :: get_course_table(TABLE_SURVEY_ANSWER);

        $user_id	= intval($user_id);
        $course_id	= intval($course_id);
        $survey_id	= intval($survey_id);

        $sql = "SELECT DISTINCT user FROM $table_survey_answer
                WHERE
                    c_id		= $course_id AND
                    user		= $user_id AND
                    survey_id	= $survey_id";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            return true;
        }
        return false;
    }

    /**
     * This function gets all the persons who have filled the survey
     *
     * @param integer $survey_id
     * @return array
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version February 2007
     */
    public static function get_people_who_filled_survey($survey_id, $all_user_info = false, $course_id = null)
    {
        // Database table definition
        $table_survey_answer = Database:: get_course_table(TABLE_SURVEY_ANSWER);
        $table_user = Database:: get_main_table(TABLE_MAIN_USER);

        // Variable initialisation
        $return = array();

        if (empty($course_id)) {
            $course_id = api_get_course_int_id();
        } else {
            $course_id = intval($course_id);
        }

        if ($all_user_info) {
            $order_clause = api_sort_by_first_name() ? ' ORDER BY user.firstname, user.lastname' : ' ORDER BY user.lastname, user.firstname';
            $sql = "SELECT DISTINCT
			            answered_user.user as invited_user, user.firstname, user.lastname, user.user_id
                    FROM $table_survey_answer answered_user
                    LEFT JOIN $table_user as user ON answered_user.user = user.user_id
                    WHERE
                        answered_user.c_id = $course_id AND
                        survey_id= '".Database::escape_string($survey_id)."' ".
                $order_clause;
        } else {
            $sql = "SELECT DISTINCT user FROM $table_survey_answer
			        WHERE c_id = $course_id AND survey_id= '".Database::escape_string($survey_id)."'  ";
        }

        $res = Database::query($sql);
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            if ($all_user_info) {
                $return[] = $row;
            } else {
                $return[] = $row['user'];
            }
        }

        return $return;
    }

    /**
     * @return bool
     */
    public static function survey_generation_hash_available()
    {
        if (extension_loaded('mcrypt')) {
            return true;
        }
        return false;
    }

    /**
     * @param int $survey_id
     * @param int $course_id
     * @param int $session_id
     * @param int $group_id
     * @return string
     */
    public static function generate_survey_hash($survey_id, $course_id, $session_id, $group_id)
    {
        $hash = hash('sha512', api_get_security_key().'_'.$course_id.'_'.$session_id.'_'.$group_id.'_'.$survey_id);
        return $hash;
    }

    /**
     * @param int $survey_id
     * @param int $course_id
     * @param int $session_id
     * @param int $group_id
     * @param string $hash
     *
     * @return bool
     */
    public static function validate_survey_hash($survey_id, $course_id, $session_id, $group_id, $hash)
    {
        $survey_generated_hash = self::generate_survey_hash($survey_id, $course_id, $session_id, $group_id);
        if ($survey_generated_hash == $hash) {
            return true;
        }
        return false;
    }

    /**
     * @param int $survey_id
     * @param int $course_id
     * @param int $session_id
     * @param int $group_id
     *
     * @return string
     */
    public static function generate_survey_link($survey_id, $course_id, $session_id, $group_id)
    {
        $code = self::generate_survey_hash($survey_id, $course_id, $session_id, $group_id);
        return api_get_path(WEB_CODE_PATH).'survey/link.php?h='.$code.'&i='.$survey_id.'&c='.intval($course_id).'&s='.intval($session_id).'&g='.$group_id;
    }
}


/**
 * This class offers a series of general utility functions for survey querying and display
 * @package chamilo.survey
 */
class SurveyUtil
{
    /**
     * Checks whether the given survey has a pagebreak question as the first or the last question.
     * If so, break the current process, displaying an error message
     * @param	integer	Survey ID (database ID)
     * @param	boolean	Optional. Whether to continue the current process or exit when breaking condition found. Defaults to true (do not break).
     * @return	void
     */
    public static function check_first_last_question($survey_id, $continue = true)
    {
        // Table definitions
        $tbl_survey_question = Database :: get_course_table(TABLE_SURVEY_QUESTION);
        $course_id = api_get_course_int_id();

        // Getting the information of the question
        $sql = "SELECT * FROM $tbl_survey_question
                WHERE c_id = $course_id AND survey_id='".Database::escape_string($survey_id)."'
                ORDER BY sort ASC";
        $result = Database::query($sql);
        $total = Database::num_rows($result);
        $counter = 1;
        $error = false;
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            if ($counter == 1 && $row['type'] == 'pagebreak') {

                Display::display_error_message(get_lang('PagebreakNotFirst'), false);
                $error = true;
            }
            if ($counter == $total && $row['type'] == 'pagebreak') {
                Display::display_error_message(get_lang('PagebreakNotLast'), false);
                $error = true;
            }
            $counter++;
        }

        if (!$continue && $error) {
            Display::display_footer();
            exit;
        }
    }

    /**
     * This function removes an (or multiple) answer(s) of a user on a question of a survey
     *
     * @param mixed   The user id or email of the person who fills the survey
     * @param integer The survey id
     * @param integer The question id
     * @param integer The option id
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version January 2007
     */
    public static function remove_answer($user, $survey_id, $question_id, $course_id) {
        $course_id = intval($course_id);
        // table definition
        $table_survey_answer 		= Database :: get_course_table(TABLE_SURVEY_ANSWER);
        $sql = "DELETE FROM $table_survey_answer
				WHERE
				    c_id = $course_id AND
                    user = '".Database::escape_string($user)."' AND
                    survey_id = '".intval($survey_id)."' AND
                    question_id = '".intval($question_id)."'";
        Database::query($sql);
    }

    /**
     * This function stores an answer of a user on a question of a survey
     *
     * @param mixed   The user id or email of the person who fills the survey
     * @param integer Survey id
     * @param integer Question id
     * @param integer Option id
     * @param string  Option value
     * @param array	  Survey data settings
     * @return bool False if insufficient data, true otherwise
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version January 2007
     */
    public static function store_answer($user, $survey_id, $question_id, $option_id, $option_value, $survey_data)
    {
        // If the question_id is empty, don't store an answer
        if (empty($question_id)) {
            return false;
        }
        // Table definition
        $table_survey_answer = Database :: get_course_table(TABLE_SURVEY_ANSWER);

        // Make the survey anonymous
        if ($survey_data['anonymous'] == 1) {
            if (!isset($_SESSION['surveyuser'])) {
                $user = md5($user.time());
                $_SESSION['surveyuser'] = $user;
            } else {
                $user = $_SESSION['surveyuser'];
            }
        }

        $course_id = $survey_data['c_id'];

        $sql = "INSERT INTO $table_survey_answer (c_id, user, survey_id, question_id, option_id, value) VALUES (
				$course_id,
				'".Database::escape_string($user)."',
				'".Database::escape_string($survey_id)."',
				'".Database::escape_string($question_id)."',
				'".Database::escape_string($option_id)."',
				'".Database::escape_string($option_value)."'
				)";
        Database::query($sql);
        $insertId = Database::insert_id();

        $sql = "UPDATE $table_survey_answer SET answer_id = $insertId WHERE iid = $insertId";
        Database::query($sql);
        return true;
    }

    /**
     * This function checks the parameters that are used in this page
     *
     * @return 	string 	The header, an error and the footer if any parameter fails, else it returns true
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version February 2007
     */
    public static function check_parameters($people_filled)
    {
        $error = false;

        // Getting the survey data
        $survey_data = SurveyManager::get_survey($_GET['survey_id']);

        // $_GET['survey_id'] has to be numeric
        if (!is_numeric($_GET['survey_id'])) {
            $error = get_lang('IllegalSurveyId');
        }

        // $_GET['action']
        $allowed_actions = array(
            'overview',
            'questionreport',
            'userreport',
            'comparativereport',
            'completereport',
            'deleteuserreport'
        );
        if (isset($_GET['action']) && !in_array($_GET['action'], $allowed_actions)) {
            $error = get_lang('ActionNotAllowed');
        }

        // User report
        if (isset($_GET['action']) && $_GET['action'] == 'userreport') {
            if ($survey_data['anonymous'] == 0) {
                foreach ($people_filled as $key => & $value) {
                    $people_filled_userids[] = $value['invited_user'];
                }
            } else {
                $people_filled_userids = $people_filled;
            }

            if (isset($_GET['user']) && !in_array($_GET['user'], $people_filled_userids)) {
                $error = get_lang('UnknowUser');
            }
        }

        // Question report
        if (isset($_GET['action']) && $_GET['action'] == 'questionreport') {
            if (isset($_GET['question']) && !is_numeric($_GET['question'])) {
                $error = get_lang('UnknowQuestion');
            }
        }

        if ($error) {
            $tool_name = get_lang('Reporting');
            Display::display_header($tool_name);
            Display::display_error_message(get_lang('Error').': '.$error, false);
            Display::display_footer();
            exit;
        } else {
            return true;
        }
    }

    /**
     * This function deals with the action handling
     * @return	void
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version February 2007
     */
    public static function handle_reporting_actions($survey_data, $people_filled)
    {
        $action = isset($_GET['action']) ? $_GET['action'] : null;

        // Getting the number of question
        $temp_questions_data = SurveyManager::get_questions($_GET['survey_id']);

        // Sorting like they should be displayed and removing the non-answer question types (comment and pagebreak)
        $my_temp_questions_data = $temp_questions_data == null ? array() : $temp_questions_data;
        $questions_data = array();

        foreach ($my_temp_questions_data as $key => & $value) {
            if ($value['type'] != 'comment' && $value['type'] != 'pagebreak') {
                $questions_data[$value['sort']] = $value;
            }
        }

        // Counting the number of questions that are relevant for the reporting
        $survey_data['number_of_questions'] = count($questions_data);

        if ($action == 'questionreport') {
            SurveyUtil::display_question_report($survey_data);
        }
        if ($action == 'userreport') {
            SurveyUtil::display_user_report($people_filled, $survey_data);
        }
        if ($action == 'comparativereport') {
            SurveyUtil::display_comparative_report();
        }
        if ($action == 'completereport') {
            SurveyUtil::display_complete_report($survey_data);
        }
        if ($action == 'deleteuserreport') {
            SurveyUtil::delete_user_report($_GET['survey_id'], $_GET['user']);
        }
    }

    /**
     * This function deletes the report of an user who wants to retake the survey
     * @param integer $survey_id
     * @param integer $user_id
     * @return void
     * @author Christian Fasanando Flores <christian.fasanando@dokeos.com>
     * @version November 2008
     */
    public static function delete_user_report($survey_id, $user_id)
    {
        $table_survey_answer = Database:: get_course_table(TABLE_SURVEY_ANSWER);
        $table_survey_invitation = Database:: get_course_table(TABLE_SURVEY_INVITATION);
        $table_survey = Database:: get_course_table(TABLE_SURVEY);

        $course_id = api_get_course_int_id();

        if (!empty($survey_id) && !empty($user_id)) {
            // delete data from survey_answer by user_id and survey_id
            $sql = "DELETE FROM $table_survey_answer
			        WHERE c_id = $course_id AND survey_id = '".(int)$survey_id."' AND user = '".(int)$user_id."'";
            Database::query($sql);
            // update field answered from survey_invitation by user_id and survey_id
            $sql = "UPDATE $table_survey_invitation SET answered = '0'
			        WHERE
			            c_id = $course_id AND
			            survey_code = (
                            SELECT code FROM $table_survey
                            WHERE
                                c_id = $course_id AND
                                survey_id = '".(int)$survey_id."'
                        ) AND
			            user = '".(int)$user_id."'";
            $result = Database::query($sql);
        }

        if ($result !== false) {
            $message = get_lang('SurveyUserAnswersHaveBeenRemovedSuccessfully').'<br />
					<a href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?action=userreport&survey_id='.intval($survey_id).'">'.get_lang('GoBack').'</a>';
            Display::display_confirmation_message($message, false);
        }
    }

    /**
     * This function displays the user report which is basically nothing more
     * than a one-page display of all the questions
     * of the survey that is filled with the answers of the person who filled the survey.
     *
     * @return 	string	html code of the one-page survey with the answers of the selected user
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version February 2007 - Updated March 2008
     */
    public static function display_user_report($people_filled, $survey_data)
    {
        // Database table definitions
        $table_survey_question = Database :: get_course_table(TABLE_SURVEY_QUESTION);
        $table_survey_question_option = Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
        $table_survey_answer = Database :: get_course_table(TABLE_SURVEY_ANSWER);

        $surveyId = isset($_GET['survey_id']) ? (int) $_GET['survey_id'] : 0;

        // Actions bar
        echo '<div class="actions">';
        echo '<a href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?survey_id='.$surveyId.'&'.api_get_cidreq().'">'.
            Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('ReportingOverview'),'',ICON_SIZE_MEDIUM).'</a>';
        if (isset($_GET['user'])) {
            if (api_is_allowed_to_edit()) {
                // The delete link
                echo '<a href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?action=deleteuserreport&survey_id='.$surveyId.'&'.api_get_cidreq().'&user='.Security::remove_XSS($_GET['user']).'" >'.
                    Display::return_icon('delete.png', get_lang('Delete'),'',ICON_SIZE_MEDIUM).'</a>';
            }

            // Export the user report
            echo '<a href="javascript: void(0);" onclick="document.form1a.submit();">'.
                Display::return_icon('export_csv.png', get_lang('ExportAsCSV'),'',ICON_SIZE_MEDIUM).'</a> ';
            echo '<a href="javascript: void(0);" onclick="document.form1b.submit();">'.
                Display::return_icon('export_excel.png', get_lang('ExportAsXLS'),'',ICON_SIZE_MEDIUM).'</a> ';
            echo '<form id="form1a" name="form1a" method="post" action="'.api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&survey_id='.$surveyId.'&'.api_get_cidreq().'&user_id='.Security::remove_XSS($_GET['user']).'">';
            echo '<input type="hidden" name="export_report" value="export_report">';
            echo '<input type="hidden" name="export_format" value="csv">';
            echo '</form>';
            echo '<form id="form1b" name="form1b" method="post" action="'.api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&survey_id='.$surveyId.'&'.api_get_cidreq().'&user_id='.Security::remove_XSS($_GET['user']).'">';
            echo '<input type="hidden" name="export_report" value="export_report">';
            echo '<input type="hidden" name="export_format" value="xls">';
            echo '</form>';
            echo '<form id="form2" name="form2" method="post" action="'.api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&survey_id='.$surveyId.'&'.api_get_cidreq().'">';
        }
        echo '</div>';

        // Step 1: selection of the user
        echo "<script>
        function jumpMenu(targ,selObj,restore) {
            eval(targ+\".location='\"+selObj.options[selObj.selectedIndex].value+\"'\");
            if (restore) selObj.selectedIndex=0;
        }
		</script>";
        echo get_lang('SelectUserWhoFilledSurvey').'<br />';
        echo '<select name="user" onchange="jumpMenu(\'parent\',this,0)">';
        echo '<option value="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?action='.Security::remove_XSS($_GET['action']).'&survey_id='.Security::remove_XSS($_GET['survey_id']).'">'.get_lang('SelectUser').'</option>';

        foreach ($people_filled as $key => & $person) {
            if ($survey_data['anonymous'] == 0) {
                $name = api_get_person_name($person['firstname'], $person['lastname']);
                $id = $person['user_id'];
                if ($id == '') {
                    $id = $person['invited_user'];
                    $name = $person['invited_user'];
                }
            } else {
                $name  = get_lang('Anonymous') . ' ' . ($key + 1);
                $id = $person;
            }
            echo '<option value="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?action='.Security::remove_XSS($_GET['action']).'&survey_id='.Security::remove_XSS($_GET['survey_id']).'&user='.Security::remove_XSS($id).'" ';
            if (isset($_GET['user']) && $_GET['user'] == $id) {
                echo 'selected="selected"';
            }
            echo '>'.$name.'</option>';
        }
        echo '</select>';

        $course_id = api_get_course_int_id();
        // Step 2: displaying the survey and the answer of the selected users
        if (isset($_GET['user'])) {
            Display::display_normal_message(
                get_lang('AllQuestionsOnOnePage'),
                false
            );

            // Getting all the questions and options
            $sql = "SELECT
			            survey_question.question_id,
			            survey_question.survey_id,
			            survey_question.survey_question,
			            survey_question.display,
			            survey_question.max_value,
			            survey_question.sort,
			            survey_question.type,
                        survey_question_option.question_option_id,
                        survey_question_option.option_text,
                        survey_question_option.sort as option_sort
					FROM $table_survey_question survey_question
					LEFT JOIN $table_survey_question_option survey_question_option
					ON
					    survey_question.question_id = survey_question_option.question_id AND
					    survey_question_option.c_id = $course_id
					WHERE
					    survey_question.survey_id = '".Database::escape_string(
                    $_GET['survey_id']
                )."' AND
                        survey_question.c_id = $course_id
					ORDER BY survey_question.sort, survey_question_option.sort ASC";
            $result = Database::query($sql);
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                if ($row['type'] != 'pagebreak') {
                    $questions[$row['sort']]['question_id'] = $row['question_id'];
                    $questions[$row['sort']]['survey_id'] = $row['survey_id'];
                    $questions[$row['sort']]['survey_question'] = $row['survey_question'];
                    $questions[$row['sort']]['display'] = $row['display'];
                    $questions[$row['sort']]['type'] = $row['type'];
                    $questions[$row['sort']]['maximum_score'] = $row['max_value'];
                    $questions[$row['sort']]['options'][$row['question_option_id']] = $row['option_text'];
                }
            }

            // Getting all the answers of the user
            $sql = "SELECT * FROM $table_survey_answer
			        WHERE
                        c_id = $course_id AND
                        survey_id = '".intval($_GET['survey_id'])."' AND
                        user = '".Database::escape_string($_GET['user'])."'";
            $result = Database::query($sql);
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $answers[$row['question_id']][] = $row['option_id'];
                $all_answers[$row['question_id']][] = $row;
            }

            // Displaying all the questions

            foreach ($questions as & $question) {
                // If the question type is a scoring then we have to format the answers differently
                switch ($question['type']) {
                    case 'score':
                        $finalAnswer = array();
                        if (is_array($question) && is_array($all_answers)) {
                            foreach ($all_answers[$question['question_id']] as $key => & $answer_array) {
                                $finalAnswer[$answer_array['option_id']] = $answer_array['value'];
                            }
                        }
                        break;
                    case 'multipleresponse':
                        $finalAnswer = isset($answers[$question['question_id']]) ? $answers[$question['question_id']] : '';
                        break;
                    default:
                        $finalAnswer = '';
                        if (isset($all_answers[$question['question_id']])) {
                            $finalAnswer = $all_answers[$question['question_id']][0]['option_id'];
                        }
                        break;
                }

                $ch_type = 'ch_'.$question['type'];
                /** @var survey_question $display */
                $display = new $ch_type;

                $url = api_get_self();
                $form = new FormValidator('question', 'post', $url);
                $form->addHtml('<div class="survey_question_wrapper"><div class="survey_question">');
                $form->addHtml($question['survey_question']);
                $display->render($form, $question, $finalAnswer);
                $form->addHtml('</div></div>');
                $form->display();
            }
        }
    }

    /**
     * This function displays the report by question.
     *
     * It displays a table with all the options of the question and the number of users who have answered positively on the option.
     * The number of users who answered positive on a given option is expressed in an absolute number, in a percentage of the total
     * and graphically using bars
     * By clicking on the absolute number you get a list with the persons who have answered this.
     * You can then click on the name of the person and you will then go to the report by user where you see all the
     * answers of that user.
     *
     * @param 	array 	All the survey data
     * @return 	string	html code that displays the report by question
     * @todo allow switching between horizontal and vertical.
     * @todo multiple response: percentage are probably not OK
     * @todo the question and option text have to be shortened and should expand when the user clicks on it.
     * @todo the pagebreak and comment question types should not be shown => removed from $survey_data before
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version February 2007 - Updated March 2008
     */
    public static function display_question_report($survey_data)
    {
        $singlePage = isset($_GET['single_page']) ? intval($_GET['single_page']) : 0;
        $course_id = api_get_course_int_id();
        // Database table definitions
        $table_survey_question = Database:: get_course_table(TABLE_SURVEY_QUESTION);
        $table_survey_question_option = Database:: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
        $table_survey_answer = Database:: get_course_table(TABLE_SURVEY_ANSWER);

        // Determining the offset of the sql statement (the n-th question of the survey)
        $offset = !isset($_GET['question']) ? 0 : intval($_GET['question']);
        $currentQuestion = isset($_GET['question']) ? intval($_GET['question']) : 0;
        $questions = array();
        $surveyId = intval($_GET['survey_id']);
        $action = Security::remove_XSS($_GET['action']);

        echo '<div class="actions">';
        echo '<a href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?survey_id='.$surveyId.'">'.
            Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('ReportingOverview'),'',ICON_SIZE_MEDIUM).'</a>';
        echo '</div>';

        if ($survey_data['number_of_questions'] > 0) {
            $limitStatement = null;
            if (!$singlePage) {
                echo '<div id="question_report_questionnumbers" class="pagination">';
                if ($currentQuestion != 0) {
                    echo '<li><a href="' . api_get_path(WEB_CODE_PATH) . 'survey/reporting.php?action=' . $action . '&' . api_get_cidreq() . '&survey_id=' . $surveyId . '&question=' . ($offset - 1) . '">' . get_lang('PreviousQuestion') . '</a></li>';
                }

                for ($i = 1; $i <= $survey_data['number_of_questions']; $i++) {
                    if ($offset != $i - 1) {
                        echo '<li><a href="' . api_get_path(WEB_CODE_PATH) . 'survey/reporting.php?action=' . $action . '&' . api_get_cidreq() . '&survey_id=' . $surveyId . '&question=' . ($i - 1) . '">' . $i . '</a></li>';
                    } else {
                        echo '<li class="disabled"s><a href="#">' . $i . '</a></li>';
                    }
                }
                if ($currentQuestion < ($survey_data['number_of_questions'] - 1)) {
                    echo '<li><a href="' . api_get_path(WEB_CODE_PATH) . 'survey/reporting.php?action=' . $action . '&' . api_get_cidreq() . '&survey_id=' . $surveyId . '&question=' . ($offset + 1) . '">' . get_lang('NextQuestion') . '</li></a>';
                }
                echo '</ul>';
                echo '</div>';
                $limitStatement = " LIMIT $offset, 1";
            }

            // Getting the question information
            $sql = "SELECT * FROM $table_survey_question
			        WHERE
			            c_id = $course_id AND
                        survey_id='".Database::escape_string($_GET['survey_id'])."' AND
                        type<>'pagebreak' AND type<>'comment'
                    ORDER BY sort ASC
                    $limitStatement";
            $result = Database::query($sql);
            //$question = Database::fetch_array($result);

            while ($row = Database::fetch_array($result)) {
                $questions[$row['question_id']] = $row;
            }
        }

        foreach ($questions as $question) {
            $chartData = array();
            $options = array();
            echo '<div class="title-question">';
            echo strip_tags(isset($question['survey_question']) ? $question['survey_question'] : null);
            echo '</div>';

            if ($question['type'] == 'score') {
                /** @todo This function should return the options as this is needed further in the code */
                $options = SurveyUtil::display_question_report_score($survey_data, $question, $offset);
            } elseif ($question['type'] == 'open') {
                /** @todo Also get the user who has answered this */
                $sql = "SELECT * FROM $table_survey_answer
                        WHERE
                            c_id = $course_id AND
                            survey_id='" . intval($_GET['survey_id']) . "' AND
                            question_id = '" . intval($question['question_id']) . "'";
                $result = Database::query($sql);
                while ($row = Database::fetch_array($result, 'ASSOC')) {
                    echo $row['option_id'] . '<hr noshade="noshade" size="1" />';
                }
            } else {
                // Getting the options ORDER BY sort ASC
                $sql = "SELECT * FROM $table_survey_question_option
                        WHERE
                            c_id = $course_id AND
                            survey_id='" . intval($_GET['survey_id']) . "'
                            AND question_id = '" . intval($question['question_id']) . "'
                        ORDER BY sort ASC";
                $result = Database::query($sql);
                while ($row = Database::fetch_array($result, 'ASSOC')) {
                    $options[$row['question_option_id']] = $row;
                }
                // Getting the answers
                $sql = "SELECT *, count(answer_id) as total FROM $table_survey_answer
                        WHERE
                            c_id = $course_id AND
                            survey_id='" . intval($_GET['survey_id']) . "'
                            AND question_id = '" . intval($question['question_id']) . "'
                        GROUP BY option_id, value";
                $result = Database::query($sql);
                $number_of_answers = array();
                $data = array();
                while ($row = Database::fetch_array($result, 'ASSOC')) {
                    if (!isset($number_of_answers[$row['question_id']])) {
                        $number_of_answers[$row['question_id']] = 0;
                    }
                    $number_of_answers[$row['question_id']] += $row['total'];
                    $data[$row['option_id']] = $row;
                }

                foreach ($options as $option) {
                    $optionText = strip_tags($option['option_text']);
                    $optionText = html_entity_decode($optionText);
                    $votes = isset($data[$option['question_option_id']]['total']) ?
                        $data[$option['question_option_id']]['total'] :
                        '0';
                    array_push($chartData, array('option' => $optionText, 'votes' => $votes));
                }
                $chartContainerId = 'chartContainer'.$question['question_id'];
                echo '<div id="'.$chartContainerId.'" class="col-md-12">';
                echo self::drawChart($chartData, false, $chartContainerId);

                // displaying the table: headers

                echo '<table class="display-survey table">';
                echo '	<tr>';
                echo '		<th>&nbsp;</th>';
                echo '		<th>' . get_lang('AbsoluteTotal') . '</th>';
                echo '		<th>' . get_lang('Percentage') . '</th>';
                echo '		<th>' . get_lang('VisualRepresentation') . '</th>';
                echo '	<tr>';

                // Displaying the table: the content
                if (is_array($options)) {
                    foreach ($options as $key => & $value) {
                        $absolute_number = null;
                        if (isset($data[$value['question_option_id']])) {
                            $absolute_number = $data[$value['question_option_id']]['total'];
                        }
                        if ($question['type'] == 'percentage' && empty($absolute_number)) {
                            continue;
                        }
                        $number_of_answers[$option['question_id']] = isset($number_of_answers[$option['question_id']]) ? $number_of_answers[$option['question_id']] : 0;
                        if ($number_of_answers[$option['question_id']] == 0) {
                            $answers_number = 0;
                        } else {
                            $answers_number = $absolute_number / $number_of_answers[$option['question_id']] * 100;
                        }
                        echo '	<tr>';
                        echo '		<td class="center">' . $value['option_text'] . '</td>';
                        echo '		<td class="center">';
                        if ($absolute_number != 0) {
                            echo '<a href="' . api_get_path(WEB_CODE_PATH) . 'survey/reporting.php?action=' . $action . '&survey_id=' . $surveyId . '&question=' . $offset . '&viewoption=' . $value['question_option_id'] . '">' . $absolute_number . '</a>';
                        } else {
                            echo '0';
                        }

                        echo '      </td>';
                        echo '		<td class="center">' . round($answers_number, 2) . ' %</td>';
                        echo '		<td class="center">';
                        $size = $answers_number * 2;
                        if ($size > 0) {
                            echo '<div style="border:1px solid #264269; background-color:#aecaf4; height:10px; width:' . $size . 'px">&nbsp;</div>';
                        } else {
                            echo '<div style="text-align: left;">' . get_lang("NoDataAvailable") . '</div>';
                        }
                        echo ' </td>';
                        echo ' </tr>';
                    }
                }
                // displaying the table: footer (totals)
                echo '	<tr>';
                echo '		<td class="total"><b>' . get_lang('Total') . '</b></td>';
                echo '		<td class="total"><b>' . ($number_of_answers[$option['question_id']] == 0 ? '0' : $number_of_answers[$option['question_id']]) . '</b></td>';
                echo '		<td class="total">&nbsp;</td>';
                echo '		<td class="total">&nbsp;</td>';
                echo '	</tr>';

                echo '</table>';

                echo '</div>';
            }
        }
        if (isset($_GET['viewoption'])) {
            echo '<div class="answered-people">';

            echo '<h4>'.get_lang('PeopleWhoAnswered').': '.strip_tags($options[Security::remove_XSS($_GET['viewoption'])]['option_text']).'</h4>';

            if (is_numeric($_GET['value'])) {
                $sql_restriction = "AND value='".Database::escape_string($_GET['value'])."'";
            }

            $sql = "SELECT user FROM $table_survey_answer
                    WHERE
                        c_id = $course_id AND
                        option_id = '".Database::escape_string($_GET['viewoption'])."'
                        $sql_restriction";
            $result = Database::query($sql);
            echo '<ul>';
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $user_info = api_get_user_info($row['user']);
                echo '<li><a href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?action=userreport&survey_id='.$surveyId.'&user='.$row['user'].'">'.$user_info['complete_name'].'</a></li>';
            }
            echo '</ul>';
            echo '</div>';
        }
    }

    /**
     * Display score data about a survey question
     * @param	array	Question info
     * @param	integer	The offset of results shown
     * @return	void 	(direct output)
     */
    public static function display_question_report_score($survey_data, $question, $offset)
    {
        // Database table definitions
        $table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
        $table_survey_answer 			= Database :: get_course_table(TABLE_SURVEY_ANSWER);

        $course_id = api_get_course_int_id();

        // Getting the options
        $sql = "SELECT * FROM $table_survey_question_option
                WHERE
                    c_id = $course_id AND
                    survey_id='".Database::escape_string($_GET['survey_id'])."' AND
                    question_id = '".Database::escape_string($question['question_id'])."'
                ORDER BY sort ASC";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            $options[$row['question_option_id']] = $row;
        }

        // Getting the answers
        $sql = "SELECT *, count(answer_id) as total FROM $table_survey_answer
                WHERE
                   c_id = $course_id AND
                   survey_id='".Database::escape_string($_GET['survey_id'])."' AND
                   question_id = '".Database::escape_string($question['question_id'])."'
                GROUP BY option_id, value";
        $result = Database::query($sql);
        $number_of_answers = 0;
        while ($row = Database::fetch_array($result)) {
            $number_of_answers += $row['total'];
            $data[$row['option_id']][$row['value']] = $row;
        }

        $chartData = array();
        foreach ($options as $option) {
            $optionText = strip_tags($option['option_text']);
            $optionText = html_entity_decode($optionText);
            for ($i = 1; $i <= $question['max_value']; $i++) {
                $votes = $data[$option['question_option_id']][$i]['total'];
                if (empty($votes)) {
                    $votes = '0';
                }
                array_push(
                    $chartData,
                    array(
                        'serie' => $optionText,
                        'option' => $i,
                        'votes' => $votes
                    )
                );
            }
        }
        echo '<div id="chartContainer" class="col-md-12">';
        echo self::drawChart($chartData, true);
        echo '</div>';

        // Displaying the table: headers
        echo '<table class="data_table">';
        echo '	<tr>';
        echo '		<th>&nbsp;</th>';
        echo '		<th>'.get_lang('Score').'</th>';
        echo '		<th>'.get_lang('AbsoluteTotal').'</th>';
        echo '		<th>'.get_lang('Percentage').'</th>';
        echo '		<th>'.get_lang('VisualRepresentation').'</th>';
        echo '	<tr>';
        // Displaying the table: the content
        foreach ($options as $key => & $value) {
            for ($i = 1; $i <= $question['max_value']; $i++) {
                $absolute_number = $data[$value['question_option_id']][$i]['total'];
                echo '	<tr>';
                echo '		<td>'.$value['option_text'].'</td>';
                echo '		<td>'.$i.'</td>';
                echo '		<td><a href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?action='.$action.'&survey_id='.Security::remove_XSS($_GET['survey_id']).'&question='.Security::remove_XSS($offset).'&viewoption='.$value['question_option_id'].'&value='.$i.'">'.$absolute_number.'</a></td>';
                echo '		<td>'.round($absolute_number/$number_of_answers*100, 2).' %</td>';
                echo '		<td>';
                $size = ($absolute_number/$number_of_answers*100*2);
                if ($size > 0) {
                    echo '			<div style="border:1px solid #264269; background-color:#aecaf4; height:10px; width:'.$size.'px">&nbsp;</div>';
                }
                echo '		</td>';
                echo '	</tr>';
            }
        }
        // Displaying the table: footer (totals)
        echo '	<tr>';
        echo '		<td style="border-top:1px solid black"><b>'.get_lang('Total').'</b></td>';
        echo '		<td style="border-top:1px solid black">&nbsp;</td>';
        echo '		<td style="border-top:1px solid black"><b>'.$number_of_answers.'</b></td>';
        echo '		<td style="border-top:1px solid black">&nbsp;</td>';
        echo '		<td style="border-top:1px solid black">&nbsp;</td>';
        echo '	</tr>';

        echo '</table>';
    }

    /**
     * This functions displays the complete reporting
     * @return	string	HTML code
     * @todo open questions are not in the complete report yet.
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version February 2007
     */
    public static function display_complete_report($survey_data)
    {
        // Database table definitions
        $table_survey_question = Database :: get_course_table(TABLE_SURVEY_QUESTION);
        $table_survey_question_option = Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
        $table_survey_answer = Database :: get_course_table(TABLE_SURVEY_ANSWER);

        $surveyId = isset($_GET['survey_id']) ? intval($_GET['survey_id']) : 0;
        $action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : '';

        // Actions bar
        echo '<div class="actions">';
        echo '<a href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?survey_id='.Security::remove_XSS($_GET['survey_id']).'">
		'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('ReportingOverview'),'',ICON_SIZE_MEDIUM).'</a>';
        echo '<a class="survey_export_link" href="javascript: void(0);" onclick="document.form1a.submit();">
		'.Display::return_icon('export_csv.png',get_lang('ExportAsCSV'),'',ICON_SIZE_MEDIUM).'</a>';
        echo '<a class="survey_export_link" href="javascript: void(0);" onclick="document.form1b.submit();">
		'.Display::return_icon('export_excel.png',get_lang('ExportAsXLS'),'',ICON_SIZE_MEDIUM).'</a>';
        echo '</div>';

        // The form
        echo '<form id="form1a" name="form1a" method="post" action="'.api_get_self().'?action='.$action.'&survey_id='.$surveyId.'&'.api_get_cidreq().'">';
        echo '<input type="hidden" name="export_report" value="export_report">';
        echo '<input type="hidden" name="export_format" value="csv">';
        echo '</form>';
        echo '<form id="form1b" name="form1b" method="post" action="'.api_get_self().'?action='.$action.'&survey_id='.$surveyId.'&'.api_get_cidreq().'">';
        echo '<input type="hidden" name="export_report" value="export_report">';
        echo '<input type="hidden" name="export_format" value="xls">';
        echo '</form>';

        echo '<form id="form2" name="form2" method="post" action="'.api_get_self().'?action='.$action.'&survey_id='.$surveyId.'&'.api_get_cidreq().'">';

        // The table
        echo '<br /><table class="data_table" border="1">';
        // Getting the number of options per question
        echo '	<tr>';
        echo '		<th>';
        if (
            (isset($_POST['submit_question_filter']) && $_POST['submit_question_filter']) ||
            (isset($_POST['export_report']) && $_POST['export_report'])
        ) {
            echo '<button class="cancel" type="submit" name="reset_question_filter" value="'.get_lang('ResetQuestionFilter').'">'.get_lang('ResetQuestionFilter').'</button>';
        }
        echo '<button class="save" type="submit" name="submit_question_filter" value="'.get_lang('SubmitQuestionFilter').'">'.get_lang('SubmitQuestionFilter').'</button>';
        echo '</th>';

        $display_extra_user_fields = false;
        if (!(isset($_POST['submit_question_filter']) && $_POST['submit_question_filter'] ||
                isset($_POST['export_report']) && $_POST['export_report']) || !empty($_POST['fields_filter'])) {
            // Show user fields section with a big th colspan that spans over all fields
            $extra_user_fields = UserManager::get_extra_fields(0, 0, 5, 'ASC', false, true);
            $num = count($extra_user_fields);
            if ($num > 0 ) {
                echo '<th '.($num>0?' colspan="'.$num.'"':'').'>';
                echo '<label><input type="checkbox" name="fields_filter" value="1" checked="checked"/> ';
                echo get_lang('UserFields');
                echo '</label>';
                echo '</th>';
                $display_extra_user_fields = true;
            }
        }

        $course_id = api_get_course_int_id();
        $sql = "SELECT q.question_id, q.type, q.survey_question, count(o.question_option_id) as number_of_options
				FROM $table_survey_question q 
				LEFT JOIN $table_survey_question_option o
				ON q.question_id = o.question_id
				WHERE 
				    q.survey_id = '".$surveyId."' AND
				    q.c_id = $course_id AND
				    o.c_id = $course_id
				GROUP BY q.question_id
				ORDER BY q.sort ASC";
        $result = Database::query($sql);
        $questions = [];
        while ($row = Database::fetch_array($result)) {
            // We show the questions if
            // 1. there is no question filter and the export button has not been clicked
            // 2. there is a quesiton filter but the question is selected for display
            if (!(isset($_POST['submit_question_filter']) && $_POST['submit_question_filter']) ||
                (is_array($_POST['questions_filter']) && in_array($row['question_id'], $_POST['questions_filter']))
            ) {
                // We do not show comment and pagebreak question types
                if ($row['type'] != 'comment' && $row['type'] != 'pagebreak') {
                    echo ' <th';
                    // <hub> modified tst to include percentage
                    if ($row['number_of_options'] > 0 && $row['type'] != 'percentage') {
                        // </hub>
                        echo ' colspan="'.$row['number_of_options'].'"';
                    }
                    echo '>';

                    echo '<label><input type="checkbox" name="questions_filter[]" value="'.$row['question_id'].'" checked="checked"/> ';
                    echo $row['survey_question'];
                    echo '</label>';
                    echo '</th>';
                }
                // No column at all if it's not a question
            }
            $questions[$row['question_id']] = $row;
        }
        echo '	</tr>';
        // Getting all the questions and options
        echo '	<tr>';
        echo '		<th>&nbsp;</th>'; // the user column

        if (!(isset($_POST['submit_question_filter']) && $_POST['submit_question_filter'] ||
                isset($_POST['export_report']) && $_POST['export_report']) || !empty($_POST['fields_filter'])) {
            //show the fields names for user fields
            foreach($extra_user_fields as & $field) {
                echo '<th>'.$field[3].'</th>';
            }
        }

        // cells with option (none for open question)
        $sql = "SELECT 	
                    sq.question_id, sq.survey_id,
                    sq.survey_question, sq.display,
                    sq.sort, sq.type, sqo.question_option_id,
                    sqo.option_text, sqo.sort as option_sort
				FROM $table_survey_question sq
				LEFT JOIN $table_survey_question_option sqo
				ON sq.question_id = sqo.question_id
				WHERE
				    sq.survey_id = '".$surveyId."' AND
                    sq.c_id = $course_id AND
                    sqo.c_id = $course_id
				ORDER BY sq.sort ASC, sqo.sort ASC";
        $result = Database::query($sql);

        $display_percentage_header = 1;
        $possible_answers = [];
        // in order to display only once the cell option (and not 100 times)
        while ($row = Database::fetch_array($result)) {
            // We show the options if
            // 1. there is no question filter and the export button has not been clicked
            // 2. there is a question filter but the question is selected for display
            //if (!($_POST['submit_question_filter'] || $_POST['export_report']) || in_array($row['question_id'], $_POST['questions_filter'])) {
            if (!(isset($_POST['submit_question_filter']) && $_POST['submit_question_filter']) ||
                (is_array($_POST['questions_filter']) && in_array($row['question_id'], $_POST['questions_filter']))
            ) {
                // <hub> modif 05-05-2010
                // we do not show comment and pagebreak question types
                if ($row['type'] == 'open') {
                    echo '<th>&nbsp;-&nbsp;</th>';
                    $possible_answers[$row['question_id']][$row['question_option_id']] = $row['question_option_id'];
                    $display_percentage_header = 1;
                } else if ($row['type'] == 'percentage' && $display_percentage_header) {
                    echo '<th>&nbsp;%&nbsp;</th>';
                    $possible_answers[$row['question_id']][$row['question_option_id']] = $row['question_option_id'];
                    $display_percentage_header = 0;
                } else if ($row['type'] == 'percentage') {
                    $possible_answers[$row['question_id']][$row['question_option_id']] = $row['question_option_id'];
                } else if ($row['type'] <> 'comment' && $row['type'] <> 'pagebreak' && $row['type'] <> 'percentage') {
                    echo '<th>';
                    echo $row['option_text'];
                    echo '</th>';
                    $possible_answers[$row['question_id']][$row['question_option_id']] = $row['question_option_id'];
                    $display_percentage_header = 1;
                }
                //no column at all if the question was not a question
                // </hub>
            }
        }

        echo '	</tr>';

        // Getting all the answers of the users
        $old_user = '';
        $answers_of_user = array();
        $sql = "SELECT * FROM $table_survey_answer
                WHERE
                    c_id = $course_id AND
                    survey_id='".$surveyId."'
                ORDER BY user ASC";
        $result = Database::query($sql);
        $i = 1;
        while ($row = Database::fetch_array($result)) {
            if ($old_user != $row['user'] && $old_user != '') {
                $userParam = $old_user;
                if ($survey_data['anonymous'] != 0) {
                    $userParam = $i;
                    $i++;
                }
                SurveyUtil::display_complete_report_row(
                    $survey_data,
                    $possible_answers,
                    $answers_of_user,
                    $userParam,
                    $questions,
                    $display_extra_user_fields
                );
                $answers_of_user=array();
            }
            if (isset($questions[$row['question_id']]) && $questions[$row['question_id']]['type'] != 'open') {
                $answers_of_user[$row['question_id']][$row['option_id']] = $row;
            } else {
                $answers_of_user[$row['question_id']][0] = $row;
            }
            $old_user = $row['user'];
        }
        $userParam = $old_user;
        if ($survey_data['anonymous'] != 0) {
            $userParam = $i;
            $i++;
        }
        SurveyUtil::display_complete_report_row(
            $survey_data,
            $possible_answers,
            $answers_of_user,
            $userParam,
            $questions,
            $display_extra_user_fields
        );
        // This is to display the last user
        echo '</table>';
        echo '</form>';
    }

    /**
     * This function displays a row (= a user and his/her answers) in the table of the complete report.
     *
     * @param array $survey_data
     * @param 	array	Possible options
     * @param 	array 	User answers
     * @param	mixed	User ID or user details string
     * @param	boolean	Whether to show extra user fields or not
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version February 2007 - Updated March 2008
     */
    public static function display_complete_report_row(
        $survey_data,
        $possible_options,
        $answers_of_user,
        $user,
        $questions,
        $display_extra_user_fields = false
    ) {
        $user = Security::remove_XSS($user);
        echo '<tr>';
        if ($survey_data['anonymous'] == 0) {
            if (intval($user) !== 0) {
                $userInfo = api_get_user_info($user);
                if (!empty($userInfo)) {
                    $user_displayed = $userInfo['complete_name'];
                } else {
                    $user_displayed = '-';
                }
                echo '<th><a href="'.api_get_self().'?action=userreport&survey_id='.Security::remove_XSS($_GET['survey_id']).'&user='.$user.'">'.
                    $user_displayed.'</a></th>'; // the user column
            } else {
                echo '<th>'.$user.'</th>'; // the user column
            }
        } else {
            echo '<th>' . get_lang('Anonymous') . ' ' . $user . '</th>';
        }

        if ($display_extra_user_fields) {
            // Show user fields data, if any, for this user
            $user_fields_values = UserManager::get_extra_user_data(intval($user), false, false, false, true);
            foreach ($user_fields_values as & $value) {
                echo '<td align="center">'.$value.'</td>';
            }
        }
        if (is_array($possible_options)) {
            // <hub> modified to display open answers and percentage
            foreach ($possible_options as $question_id => & $possible_option) {
                if ($questions[$question_id]['type'] == 'open') {
                    echo '<td align="center">';
                    echo $answers_of_user[$question_id]['0']['option_id'];
                    echo '</td>';
                } else {
                    foreach ($possible_option as $option_id => & $value) {
                        if ($questions[$question_id]['type'] == 'percentage') {
                            if (!empty($answers_of_user[$question_id][$option_id])) {
                                echo "<td align='center'>";
                                echo $answers_of_user[$question_id][$option_id]['value'];
                                echo "</td>";
                            }
                        }
                        else {
                            echo '<td align="center">';
                            if (!empty($answers_of_user[$question_id][$option_id])) {
                                if ($answers_of_user[$question_id][$option_id]['value'] != 0) {
                                    echo $answers_of_user[$question_id][$option_id]['value'];
                                }
                                else {
                                    echo 'v';
                                }
                            }
                        } // </hub>
                    }
                }
            }
        }
        echo '</tr>';
    }

    /**
     * Quite similar to display_complete_report(), returns an HTML string
     * that can be used in a csv file
     * @todo consider merging this function with display_complete_report
     * @return	string	The contents of a csv file
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version February 2007
     */
    public static function export_complete_report($survey_data, $user_id = 0)
    {
        // Database table definitions
        $table_survey_question = Database :: get_course_table(TABLE_SURVEY_QUESTION);
        $table_survey_question_option = Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
        $table_survey_answer = Database :: get_course_table(TABLE_SURVEY_ANSWER);

        // The first column
        $return = ';';

        // Show extra fields blank space (enough for extra fields on next line)
        $extra_user_fields = UserManager::get_extra_fields(0, 0, 5, 'ASC', false, true);

        $num = count($extra_user_fields);
        $return .= str_repeat(';', $num);

        $course_id = api_get_course_int_id();

        $sql = "SELECT
                    questions.question_id,
                    questions.type,
                    questions.survey_question,
                    count(options.question_option_id) as number_of_options
				FROM $table_survey_question questions
                LEFT JOIN $table_survey_question_option options
				ON questions.question_id = options.question_id  AND options.c_id = $course_id
				WHERE
				    questions.survey_id = '".intval($_GET['survey_id'])."' AND
                    questions.c_id = $course_id
				GROUP BY questions.question_id
				ORDER BY questions.sort ASC";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            // We show the questions if
            // 1. there is no question filter and the export button has not been clicked
            // 2. there is a quesiton filter but the question is selected for display
            if (!(isset($_POST['submit_question_filter'])) ||
                (isset($_POST['submit_question_filter']) &&
                    is_array($_POST['questions_filter']) &&
                    in_array($row['question_id'], $_POST['questions_filter']))
            ) {
                // We do not show comment and pagebreak question types
                if ($row['type'] != 'comment' && $row['type'] != 'pagebreak') {
                    if ($row['number_of_options'] == 0 && $row['type'] == 'open') {
                        $return .= str_replace("\r\n",'  ', api_html_entity_decode(strip_tags($row['survey_question']), ENT_QUOTES)).';';
                    } else {
                        for ($ii = 0; $ii < $row['number_of_options']; $ii++) {
                            $return .= str_replace("\r\n",'  ', api_html_entity_decode(strip_tags($row['survey_question']), ENT_QUOTES)).';';
                        }
                    }
                }
            }
        }
        $return .= "\n";

        // Getting all the questions and options
        $return .= ';';

        // Show the fields names for user fields
        if (!empty($extra_user_fields)) {
            foreach ($extra_user_fields as & $field) {
                $return .= '"'.str_replace("\r\n",'  ',api_html_entity_decode(strip_tags($field[3]), ENT_QUOTES)).'";';
            }
        }

        $sql = "SELECT
		            survey_question.question_id,
		            survey_question.survey_id,
		            survey_question.survey_question,
		            survey_question.display,
		            survey_question.sort,
		            survey_question.type,
                    survey_question_option.question_option_id,
                    survey_question_option.option_text,
                    survey_question_option.sort as option_sort
				FROM $table_survey_question survey_question
				LEFT JOIN $table_survey_question_option survey_question_option
				ON
				    survey_question.question_id = survey_question_option.question_id AND
				    survey_question_option.c_id = $course_id
				WHERE
				    survey_question.survey_id = '".intval($_GET['survey_id'])."' AND
				    survey_question.c_id = $course_id
				ORDER BY survey_question.sort ASC, survey_question_option.sort ASC";
        $result = Database::query($sql);
        $possible_answers = array();
        $possible_answers_type = array();
        while ($row = Database::fetch_array($result)) {
            // We show the options if
            // 1. there is no question filter and the export button has not been clicked
            // 2. there is a quesiton filter but the question is selected for display
            if (!(isset($_POST['submit_question_filter'])) || (
                is_array($_POST['questions_filter']) &&
                in_array($row['question_id'], $_POST['questions_filter']))
            ) {
                // We do not show comment and pagebreak question types
                if ($row['type'] != 'comment' && $row['type'] != 'pagebreak') {
                    $row['option_text'] = str_replace(array("\r","\n"),array('',''),$row['option_text']);
                    $return .= api_html_entity_decode(strip_tags($row['option_text']), ENT_QUOTES).';';
                    $possible_answers[$row['question_id']][$row['question_option_id']] = $row['question_option_id'];
                    $possible_answers_type[$row['question_id']] = $row['type'];
                }
            }
        }
        $return .= "\n";

        // Getting all the answers of the users
        $old_user = '';
        $answers_of_user = array();
        $sql = "SELECT * FROM $table_survey_answer
		        WHERE c_id = $course_id AND survey_id='".Database::escape_string($_GET['survey_id'])."'";
        if ($user_id != 0) {
            $sql .= "AND user='".Database::escape_string($user_id)."' ";
        }
        $sql .= "ORDER BY user ASC";

        $open_question_iterator = 1;
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            if ($old_user != $row['user'] && $old_user != '') {
                $return .= SurveyUtil::export_complete_report_row(
                    $survey_data,
                    $possible_answers,
                    $answers_of_user,
                    $old_user,
                    true
                );
                $answers_of_user=array();
            }
            if($possible_answers_type[$row['question_id']] == 'open') {
                $temp_id = 'open'.$open_question_iterator;
                $answers_of_user[$row['question_id']][$temp_id] = $row;
                $open_question_iterator++;
            } else {
                $answers_of_user[$row['question_id']][$row['option_id']] = $row;
            }
            $old_user = $row['user'];
        }
        // This is to display the last user
        $return .= SurveyUtil::export_complete_report_row(
            $survey_data,
            $possible_answers,
            $answers_of_user,
            $old_user,
            true
        );

        return $return;
    }

    /**
     * Add a line to the csv file
     *
     * @param	array	Possible answers
     * @param	array	User's answers
     * @param 	mixed	User ID or user details as string - Used as a string in the result string
     * @param	boolean	Whether to display user fields or not
     * @return	string	One line of the csv file
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version February 2007
     */
    public static function export_complete_report_row(
        $survey_data,
        $possible_options,
        $answers_of_user,
        $user,
        $display_extra_user_fields = false
    ) {
        $return = '';
        if ($survey_data['anonymous'] == 0) {
            if (intval($user) !== 0) {
                $userInfo = api_get_user_info($user);

                if (!empty($userInfo)) {
                    $user_displayed = $userInfo['complete_name'];
                } else {
                    $user_displayed = '-';
                }
                $return .= $user_displayed.';';
            } else {
                $return .= $user.';';
            }
        } else {
            $return .= '-;'; // The user column
        }

        if ($display_extra_user_fields) {
            // Show user fields data, if any, for this user
            $user_fields_values = UserManager::get_extra_user_data($user,false,false, false, true);
            foreach ($user_fields_values as & $value) {
                $return .= '"'.str_replace('"', '""', api_html_entity_decode(strip_tags($value), ENT_QUOTES)).'";';
            }
        }

        if (is_array($possible_options)) {
            foreach ($possible_options as $question_id => $possible_option) {
                if (is_array($possible_option) && count($possible_option) > 0) {
                    foreach ($possible_option as $option_id => & $value) {
                        $my_answer_of_user = !isset($answers_of_user[$question_id]) || isset($answers_of_user[$question_id]) && $answers_of_user[$question_id] == null ? array() : $answers_of_user[$question_id];
                        $key = array_keys($my_answer_of_user);
                        if (isset($key[0]) && substr($key[0], 0, 4) == 'open') {
                            $return .= '"'.
                                str_replace(
                                    '"',
                                    '""',
                                    api_html_entity_decode(strip_tags($answers_of_user[$question_id][$key[0]]['option_id']), ENT_QUOTES)
                                ).
                                '"';
                        } elseif (!empty($answers_of_user[$question_id][$option_id])) {
                            //$return .= 'v';
                            if ($answers_of_user[$question_id][$option_id]['value'] != 0) {
                                $return .= $answers_of_user[$question_id][$option_id]['value'];
                            } else {
                                $return .= 'v';
                            }
                        }
                        $return .= ';';
                    }
                }
            }
        }
        $return .= "\n";
        return $return;
    }

    /**
     * Quite similar to display_complete_report(), returns an HTML string
     * that can be used in a csv file
     * @todo consider merging this function with display_complete_report
     * @return	string	The contents of a csv file
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version February 2007
     */
    public static function export_complete_report_xls($survey_data, $filename, $user_id = 0)
    {
        $course_id = api_get_course_int_id();
        $surveyId = isset($_GET['survey_id']) ? (int) $_GET['survey_id'] : 0;

        if (empty($course_id) || empty($surveyId)) {

            return false;
        }

        $spreadsheet = new PHPExcel();
        $spreadsheet->setActiveSheetIndex(0);
        $worksheet = $spreadsheet->getActiveSheet();
        $line = 1;
        $column = 1; // Skip the first column (row titles)

        // Show extra fields blank space (enough for extra fields on next line)
        // Show user fields section with a big th colspan that spans over all fields
        $extra_user_fields = UserManager::get_extra_fields(0, 0, 5, 'ASC', false, true);
        $num = count($extra_user_fields);
        for ($i = 0; $i < $num; $i++) {
            $worksheet->setCellValueByColumnAndRow($column, $line, '');
            $column++;
        }

        $display_extra_user_fields = true;

        // Database table definitions
        $table_survey_question = Database :: get_course_table(TABLE_SURVEY_QUESTION);
        $table_survey_question_option = Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
        $table_survey_answer = Database :: get_course_table(TABLE_SURVEY_ANSWER);

        // First line (questions)
        $sql = "SELECT
                    questions.question_id,
                    questions.type,
                    questions.survey_question,
                    count(options.question_option_id) as number_of_options
				FROM $table_survey_question questions
				LEFT JOIN $table_survey_question_option options
                ON questions.question_id = options.question_id AND options.c_id = $course_id
				WHERE
				    questions.survey_id = $surveyId AND
				    questions.c_id = $course_id
				GROUP BY questions.question_id
				ORDER BY questions.sort ASC";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            // We show the questions if
            // 1. there is no question filter and the export button has not been clicked
            // 2. there is a quesiton filter but the question is selected for display
            if (!(isset($_POST['submit_question_filter'])) ||
                (isset($_POST['submit_question_filter']) && is_array($_POST['questions_filter']) &&
                in_array($row['question_id'], $_POST['questions_filter']))
            ) {
                // We do not show comment and pagebreak question types
                if ($row['type'] != 'comment' && $row['type'] != 'pagebreak') {
                    if ($row['number_of_options'] == 0 && $row['type'] == 'open') {
                        $worksheet->setCellValueByColumnAndRow(
                            $column,
                            $line,
                            api_html_entity_decode(
                                strip_tags($row['survey_question']),
                                ENT_QUOTES
                            )
                        );
                        $column++;
                    } else {
                        for ($ii = 0; $ii < $row['number_of_options']; $ii ++) {
                            $worksheet->setCellValueByColumnAndRow(
                                $column,
                                $line,
                                api_html_entity_decode(
                                    strip_tags($row['survey_question']),
                                    ENT_QUOTES
                                )
                            );
                            $column++;
                        }
                    }
                }
            }
        }

        $line++;
        $column = 1;

        // Show extra field values
        if ($display_extra_user_fields) {
            // Show the fields names for user fields
            foreach ($extra_user_fields as & $field) {
                $worksheet->setCellValueByColumnAndRow(
                    $column,
                    $line,
                    api_html_entity_decode(strip_tags($field[3]), ENT_QUOTES)
                );
                $column++;
            }
        }

        // Getting all the questions and options (second line)
        $sql = "SELECT
                    survey_question.question_id, 
                    survey_question.survey_id, 
                    survey_question.survey_question, 
                    survey_question.display, 
                    survey_question.sort, 
                    survey_question.type,
                    survey_question_option.question_option_id, 
                    survey_question_option.option_text, 
                    survey_question_option.sort as option_sort
				FROM $table_survey_question survey_question
				LEFT JOIN $table_survey_question_option survey_question_option
				ON 
				    survey_question.question_id = survey_question_option.question_id AND 
				    survey_question_option.c_id = $course_id
				WHERE 
				    survey_question.survey_id = $surveyId AND
				    survey_question.c_id = $course_id
				ORDER BY survey_question.sort ASC, survey_question_option.sort ASC";
        $result = Database::query($sql);
        $possible_answers = array();
        $possible_answers_type = array();
        while ($row = Database::fetch_array($result)) {
            // We show the options if
            // 1. there is no question filter and the export button has not been clicked
            // 2. there is a quesiton filter but the question is selected for display
            if (!isset($_POST['submit_question_filter']) ||
                (isset($_POST['questions_filter']) && is_array($_POST['questions_filter']) &&
                in_array($row['question_id'], $_POST['questions_filter']))
            ) {
                // We do not show comment and pagebreak question types
                if ($row['type'] != 'comment' && $row['type'] != 'pagebreak') {
                    $worksheet->setCellValueByColumnAndRow(
                        $column,
                        $line,
                        api_html_entity_decode(
                            strip_tags($row['option_text']),
                            ENT_QUOTES
                        )
                    );
                    $possible_answers[$row['question_id']][$row['question_option_id']] = $row['question_option_id'];
                    $possible_answers_type[$row['question_id']] = $row['type'];
                    $column++;
                }
            }
        }

        // Getting all the answers of the users
        $line ++;
        $column = 0;
        $old_user = '';
        $answers_of_user = array();
        $sql = "SELECT * FROM $table_survey_answer
                WHERE c_id = $course_id AND survey_id = $surveyId";
        if ($user_id != 0) {
            $sql .= " AND user='".intval($user_id)."' ";
        }
        $sql .=	" ORDER BY user ASC";

        $open_question_iterator = 1;
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            if ($old_user != $row['user'] && $old_user != '') {
                $return = SurveyUtil::export_complete_report_row_xls(
                    $survey_data,
                    $possible_answers,
                    $answers_of_user,
                    $old_user,
                    true
                );
                foreach ($return as $elem) {
                    $worksheet->setCellValueByColumnAndRow($column, $line, $elem);
                    $column++;
                }
                $answers_of_user = array();
                $line++;
                $column = 0;
            }
            if ($possible_answers_type[$row['question_id']] == 'open') {
                $temp_id = 'open'.$open_question_iterator;
                $answers_of_user[$row['question_id']][$temp_id] = $row;
                $open_question_iterator++;
            } else {
                $answers_of_user[$row['question_id']][$row['option_id']] = $row;
            }
            $old_user = $row['user'];
        }

        $return = SurveyUtil::export_complete_report_row_xls(
            $survey_data,
            $possible_answers,
            $answers_of_user,
            $old_user,
            true
        );

        // this is to display the last user
        foreach ($return as $elem) {
            $worksheet->setCellValueByColumnAndRow($column, $line, $elem);
            $column++;
        }

        $file = api_get_path(SYS_ARCHIVE_PATH).api_replace_dangerous_char($filename);
        $writer = new PHPExcel_Writer_Excel2007($spreadsheet);
        $writer->save($file);
        DocumentManager::file_send_for_download($file, true, $filename);

        return null;
    }

    /**
     * Add a line to the csv file
     *
     * @param	array	Possible answers
     * @param	array	User's answers
     * @param 	mixed	User ID or user details as string - Used as a string in the result string
     * @param	boolean	Whether to display user fields or not
     * @return	string	One line of the csv file
     */
    public static function export_complete_report_row_xls(
        $survey_data,
        $possible_options,
        $answers_of_user,
        $user,
        $display_extra_user_fields = false
    ) {
        $return = array();
        if ($survey_data['anonymous'] == 0) {
            if (intval($user) !== 0) {
                $sql = 'SELECT firstname, lastname
                        FROM '.Database::get_main_table(TABLE_MAIN_USER).'
                        WHERE user_id='.intval($user);
                $rs = Database::query($sql);
                if($row = Database::fetch_array($rs)) {
                    $user_displayed = api_get_person_name($row['firstname'], $row['lastname']);
                } else {
                    $user_displayed = '-';
                }
                $return[] = $user_displayed;
            } else {
                $return[] = $user;
            }
        } else {
            $return[] = '-'; // The user column
        }

        if ($display_extra_user_fields) {
            //show user fields data, if any, for this user
            $user_fields_values = UserManager::get_extra_user_data(intval($user),false,false, false, true);
            foreach ($user_fields_values as $value) {
                $return[] = api_html_entity_decode(strip_tags($value), ENT_QUOTES);
            }
        }

        if (is_array($possible_options)) {
            foreach ($possible_options as $question_id => & $possible_option) {
                if (is_array($possible_option) && count($possible_option) > 0) {
                    foreach ($possible_option as $option_id => & $value) {
                        $my_answers_of_user = isset($answers_of_user[$question_id]) ? $answers_of_user[$question_id] : [];
                        $key = array_keys($my_answers_of_user);
                        if (isset($key[0]) && substr($key[0], 0, 4) == 'open') {
                            $return[] = api_html_entity_decode(strip_tags($answers_of_user[$question_id][$key[0]]['option_id']), ENT_QUOTES);
                        } elseif (!empty($answers_of_user[$question_id][$option_id])) {
                            if ($answers_of_user[$question_id][$option_id]['value'] != 0) {
                                $return[] = $answers_of_user[$question_id][$option_id]['value'];
                            } else {
                                $return[] = 'v';
                            }
                        } else {
                            $return[] = '';
                        }
                    }
                }
            }
        }

        return $return;
    }

    /**
     * This function displays the comparative report which allows you to compare two questions
     * A comparative report creates a table where one question is on the x axis and a second question is on the y axis.
     * In the intersection is the number of people who have answerd positive on both options.
     *
     * @return	string	HTML code
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version February 2007
     */
    public static function display_comparative_report()
    {
        // Allowed question types for comparative report
        $allowed_question_types = array(
            'yesno',
            'multiplechoice',
            'multipleresponse',
            'dropdown',
            'percentage',
            'score'
        );

        $surveyId = isset($_GET['survey_id']) ? (int) $_GET['survey_id'] : 0;

        // Getting all the questions
        $questions = SurveyManager::get_questions($surveyId);

        // Actions bar
        echo '<div class="actions">';
        echo '<a href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?survey_id='.$surveyId.'&'.api_get_cidreq().'">'.
                Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('ReportingOverview'),'',ICON_SIZE_MEDIUM).'</a>';
        echo '</div>';

        // Displaying an information message that only the questions with predefined answers can be used in a comparative report
        Display::display_normal_message(get_lang('OnlyQuestionsWithPredefinedAnswers'), false);

        $xAxis = isset($_GET['xaxis']) ? Security::remove_XSS($_GET['xaxis']) : '';
        $yAxis = isset($_GET['yaxis']) ? Security::remove_XSS($_GET['yaxis']) : '';

        $url = api_get_self().'?'.api_get_cidreq().'&action='.Security::remove_XSS($_GET['action']).'&survey_id='.$surveyId.'&xaxis='.$xAxis.'&y='.$yAxis;

        $form = new FormValidator('compare', 'get', $url);
        $form->addHidden('action', Security::remove_XSS($_GET['action']));
        $form->addHidden('survey_id', $surveyId);
        $optionsX = ['----'];
        $optionsY = ['----'];
        $defaults = [];
        foreach ($questions as $key => & $question) {
            if (is_array($allowed_question_types)) {
                if (in_array($question['type'], $allowed_question_types)) {
                    //echo '<option value="'.$question['question_id'].'"';
                    if (isset($_GET['xaxis']) && $_GET['xaxis'] == $question['question_id']) {
                        $defaults['xaxis'] = $question['question_id'];
                    }

                    if (isset($_GET['yaxis']) && $_GET['yaxis'] == $question['question_id']) {
                        $defaults['yaxis'] = $question['question_id'];
                    }

                    $optionsX[$question['question_id']] = api_substr(strip_tags($question['question']), 0, 50);
                    $optionsY[$question['question_id']] = api_substr(strip_tags($question['question']), 0, 50);
                }
            }
        }

        $form->addSelect('xaxis', get_lang('SelectXAxis'), $optionsX);
        $form->addSelect('yaxis', get_lang('SelectYAxis'), $optionsY);

        $form->addButtonSearch(get_lang('CompareQuestions'));
        $form->setDefaults($defaults);
        $form->display();

        // Getting all the information of the x axis
        if (is_numeric($xAxis)) {
            $question_x = SurveyManager::get_question($xAxis);
        }

        // Getting all the information of the y axis
        if (is_numeric($yAxis)) {
            $question_y = SurveyManager::get_question($yAxis);
        }

        if (is_numeric($xAxis) && is_numeric($yAxis)) {
            // Getting the answers of the two questions
            $answers_x = SurveyUtil::get_answers_of_question_by_user($surveyId, $xAxis);
            $answers_y = SurveyUtil::get_answers_of_question_by_user($surveyId, $yAxis);

            // Displaying the table
            $tableHtml = '<table border="1" class="data_table">';

            $xOptions = array();
            // The header
            $tableHtml .= '<tr>';
            for ($ii = 0; $ii <= count($question_x['answers']); $ii++) {
                if ($ii == 0) {
                    $tableHtml .=  '<th>&nbsp;</th>';
                } else {
                    if ($question_x['type'] == 'score') {
                        for ($x = 1; $x <= $question_x['maximum_score']; $x++) {
                            $tableHtml .= '<th>'.$question_x['answers'][($ii-1)].'<br />'.$x.'</th>';
                        }
                        $x = '';
                    } else {
                        $tableHtml .= '<th>'.$question_x['answers'][($ii-1)].'</th>';
                    }
                    $optionText = strip_tags($question_x['answers'][$ii-1]);
                    $optionText = html_entity_decode($optionText);
                    array_push($xOptions, trim($optionText));
                }
            }
            $tableHtml .= '</tr>';
            $chartData = array();

            // The main part
            for ($ij = 0; $ij < count($question_y['answers']); $ij++) {
                $currentYQuestion = strip_tags($question_y['answers'][$ij]);
                $currentYQuestion = html_entity_decode($currentYQuestion);
                // The Y axis is a scoring question type so we have more rows than the options (actually options * maximum score)
                if ($question_y['type'] == 'score') {
                    for ($y = 1; $y <= $question_y['maximum_score']; $y++) {
                        $tableHtml .=  '<tr>';
                        for ($ii = 0; $ii <= count($question_x['answers']); $ii++) {
                            if ($question_x['type'] == 'score') {
                                for ($x = 1; $x <= $question_x['maximum_score']; $x++) {
                                    if ($ii == 0) {
                                        $tableHtml .=  '<th>'.$question_y['answers'][($ij)].' '.$y.'</th>';
                                        break;
                                    } else {
                                        $tableHtml .=  '<td align="center">';
                                        $votes = SurveyUtil::comparative_check(
                                            $answers_x,
                                            $answers_y,
                                            $question_x['answersid'][($ii - 1)],
                                            $question_y['answersid'][($ij)],
                                            $x,
                                            $y
                                        );
                                        $tableHtml .=  $votes;
                                        array_push(
                                            $chartData,
                                            array(
                                                'serie' => array($currentYQuestion, $xOptions[$ii-1]),
                                                'option' => $x,
                                                'votes' => $votes
                                            )
                                        );
                                        $tableHtml .=  '</td>';
                                    }
                                }
                            } else {
                                if ($ii == 0) {
                                    $tableHtml .= '<th>'.$question_y['answers'][$ij].' '.$y.'</th>';
                                } else {
                                    $tableHtml .= '<td align="center">';
                                    $votes = SurveyUtil::comparative_check(
                                        $answers_x,
                                        $answers_y,
                                        $question_x['answersid'][($ii - 1)],
                                        $question_y['answersid'][($ij)],
                                        0,
                                        $y
                                    );
                                    $tableHtml .= $votes;
                                    array_push(
                                        $chartData,
                                        array(
                                            'serie' => array($currentYQuestion, $xOptions[$ii-1]),
                                            'option' => $y,
                                            'votes' => $votes
                                        )
                                    );
                                    $tableHtml .=  '</td>';
                                }
                            }
                        }
                        $tableHtml .=  '</tr>';
                    }
                }
                // The Y axis is NOT a score question type so the number of rows = the number of options
                else {
                    $tableHtml .=  '<tr>';
                    for ($ii = 0; $ii <= count($question_x['answers']); $ii++) {
                        if ($question_x['type'] == 'score') {
                            for ($x = 1; $x <= $question_x['maximum_score']; $x++) {
                                if ($ii == 0) {
                                    $tableHtml .=  '<th>'.$question_y['answers'][$ij].'</th>';
                                    break;
                                } else {
                                    $tableHtml .=  '<td align="center">';
                                    $votes =  SurveyUtil::comparative_check(
                                        $answers_x,
                                        $answers_y,
                                        $question_x['answersid'][($ii-1)],
                                        $question_y['answersid'][($ij)],
                                        $x,
                                        0
                                    );
                                    $tableHtml .= $votes;
                                    array_push(
                                        $chartData,
                                        array(
                                            'serie' => array($currentYQuestion, $xOptions[$ii-1]),
                                            'option' => $x,
                                            'votes' => $votes
                                        )
                                    );
                                    $tableHtml .=  '</td>';
                                }
                            }
                        } else {
                            if ($ii == 0) {
                                $tableHtml .=  '<th>'.$question_y['answers'][($ij)].'</th>';
                            } else {
                                $tableHtml .=  '<td align="center">';
                                $votes = SurveyUtil::comparative_check($answers_x, $answers_y, $question_x['answersid'][($ii-1)], $question_y['answersid'][($ij)]);
                                $tableHtml .= $votes;
                                array_push(
                                    $chartData,
                                    array(
                                        'serie' => $xOptions[$ii-1],
                                        'option' => $currentYQuestion,
                                        'votes' => $votes
                                    )
                                );
                                $tableHtml .= '</td>';
                            }
                        }
                    }
                    $tableHtml .= '</tr>';
                }
            }
            $tableHtml .=  '</table>';
            echo '<div id="chartContainer" class="col-md-12">';
            echo self::drawChart($chartData, true);
            echo '</div>';
            echo $tableHtml;
        }
    }

    /**
     * Get all the answers of a question grouped by user
     *
     * @param	integer	Survey ID
     * @param	integer	Question ID
     * @return 	Array	Array containing all answers of all users, grouped by user
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version February 2007 - Updated March 2008
     */
    public static function get_answers_of_question_by_user($survey_id, $question_id)
    {
        $course_id = api_get_course_int_id();
        $table_survey_answer = Database :: get_course_table(TABLE_SURVEY_ANSWER);

        $sql = "SELECT * FROM $table_survey_answer
                WHERE c_id = $course_id AND survey_id='".intval($survey_id)."'
                AND question_id='".intval($question_id)."'
                ORDER BY USER ASC";
        $result = Database::query($sql);
        $return = [];
        while ($row = Database::fetch_array($result)) {
            if ($row['value'] == 0) {
                $return[$row['user']][] = $row['option_id'];
            } else {
                $return[$row['user']][] = $row['option_id'].'*'.$row['value'];
            }
        }

        return $return;
    }

    /**
     * Count the number of users who answer positively on both options
     *
     * @param	array	All answers of the x axis
     * @param	array	All answers of the y axis
     * @param	integer x axis value (= the option_id of the first question)
     * @param	integer y axis value (= the option_id of the second question)
     * @return	integer Number of users who have answered positively to both options
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version February 2007
     */
    public static function comparative_check($answers_x, $answers_y, $option_x, $option_y, $value_x = 0, $value_y = 0)
    {
        if ($value_x == 0) {
            $check_x = $option_x;
        } else {
            $check_x = $option_x.'*'.$value_x;
        }
        if ($value_y == 0) {
            $check_y = $option_y;
        } else {
            $check_y = $option_y.'*'.$value_y;
        }

        $counter = 0;
        if (is_array($answers_x)) {
            foreach ($answers_x as $user => & $answers) {
                // Check if the user has given $option_x as answer
                if (in_array($check_x, $answers)) {
                    // Check if the user has given $option_y as an answer
                    if (!is_null($answers_y[$user]) && in_array($check_y, $answers_y[$user])) {
                        $counter++;
                    }
                }
            }
        }

        return $counter;
    }

    /**
     * Get all the information about the invitations of a certain survey
     *
     * @return	array	Lines of invitation [user, code, date, empty element]
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version January 2007
     *
     * @todo use survey_id parameter instead of $_GET
     */
    public static function get_survey_invitations_data()
    {
        $course_id = api_get_course_int_id();
        // Database table definition
        $table_survey_invitation = Database :: get_course_table(TABLE_SURVEY_INVITATION);
        $table_user = Database :: get_main_table(TABLE_MAIN_USER);

        $sql = "SELECT
					survey_invitation.user as col1,
					survey_invitation.invitation_code as col2,
					survey_invitation.invitation_date as col3,
					'' as col4
					FROM $table_survey_invitation survey_invitation
                LEFT JOIN $table_user user
                ON survey_invitation.user = user.user_id
                WHERE
                    survey_invitation.c_id = $course_id AND
                    survey_invitation.survey_id = '".intval($_GET['survey_id'])."' AND
                    session_id='".api_get_session_id()."'  ";
        $res = Database::query($sql);
        $data = [];
        while ($row = Database::fetch_array($res)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     * Get the total number of survey invitations for a given survey (through $_GET['survey_id'])
     *
     * @return	integer	Total number of survey invitations
     *
     * @todo use survey_id parameter instead of $_GET
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version January 2007
     */
    public static function get_number_of_survey_invitations()
    {
        $course_id = api_get_course_int_id();

        // Database table definition
        $table_survey_invitation = Database :: get_course_table(TABLE_SURVEY_INVITATION);

        $sql = "SELECT count(user) AS total
		        FROM $table_survey_invitation
		        WHERE
                    c_id = $course_id AND
                    survey_id='".intval($_GET['survey_id'])."' AND
                    session_id='".api_get_session_id()."' ";
        $res = Database::query($sql);
        $row = Database::fetch_array($res,'ASSOC');

        return $row['total'];
    }

    /**
     * Save the invitation mail
     *
     * @param string 	Text of the e-mail
     * @param integer	Whether the mail contents are for invite mail (0, default) or reminder mail (1)
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version January 2007
     */
    public static function save_invite_mail($mailtext, $mail_subject, $reminder = 0)
    {
        $course_id = api_get_course_int_id();
        // Database table definition
        $table_survey = Database :: get_course_table(TABLE_SURVEY);

        // Reminder or not
        if ($reminder == 0) {
            $mail_field = 'invite_mail';
        } else {
            $mail_field = 'reminder_mail';
        }

        $sql = "UPDATE $table_survey SET
		        mail_subject='".Database::escape_string($mail_subject)."',
		        $mail_field = '".Database::escape_string($mailtext)."'
		        WHERE c_id = $course_id AND survey_id = '".intval($_GET['survey_id'])."'";
        Database::query($sql);
    }

    /**
     * This function saves all the invitations of course users and additional users in the database
     * and sends the invitations by email
     *
     * @param	array	Users array can be both a list of course uids AND a list of additional emailaddresses
     * @param 	string	Title of the invitation, used as the title of the mail
     * @param 	string	Text of the invitation, used as the text of the mail.
     * 				 The text has to contain a **link** string or this will automatically be added to the end
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @author Julio Montoya - Adding auto-generated link support
     * @version January 2007
     *
     */
    public static function saveInvitations(
        $users_array,
        $invitation_title,
        $invitation_text,
        $reminder = 0,
        $sendmail = 0,
        $remindUnAnswered = 0
    ) {
        if (!is_array($users_array)) {
            // Should not happen

            return 0;
        }

        // Getting the survey information
        $survey_data = SurveyManager::get_survey($_GET['survey_id']);
        $survey_invitations = SurveyUtil::get_invitations($survey_data['survey_code']);
        $already_invited = SurveyUtil::get_invited_users($survey_data['code']);

        // Remind unanswered is a special version of remind all reminder
        $exclude_users = array();
        if ($remindUnAnswered == 1) { // Remind only unanswered users
            $reminder = 1;
            $exclude_users = SurveyManager::get_people_who_filled_survey($_GET['survey_id']);
        }

        $counter = 0;  // Nr of invitations "sent" (if sendmail option)
        $course_id = api_get_course_int_id();
        $session_id = api_get_session_id();

        $result = CourseManager::separateUsersGroups($users_array);

        $groupList = $result['groups'];
        $users_array = $result['users'];

        foreach ($groupList as $groupId) {
            $userGroupList = GroupManager::getStudents($groupId);
            $userGroupIdList = array_column($userGroupList, 'user_id');
            $users_array = array_merge($users_array, $userGroupIdList);

            $params = array(
                'c_id' => $course_id,
                'session_id' => $session_id,
                'group_id' => $groupId,
                'survey_code' => $survey_data['code']
            );

            $invitationExists = self::invitationExists(
                $course_id,
                $session_id,
                $groupId,
                $survey_data['code']
            );
            if (empty($invitationExists)) {
                self::save_invitation($params);
            }
        }

        $users_array = array_unique($users_array);

        foreach ($users_array as $key => $value) {
            if (!isset($value) || $value == '') {
                continue;
            }

            // Skip user if reminding only unanswered people
            if (in_array($value, $exclude_users)) {
                continue;
            }

            // Get the unique invitation code if we already have it
            if ($reminder == 1 && array_key_exists($value, $survey_invitations)) {
                $invitation_code = $survey_invitations[$value]['invitation_code'];
            } else {
                $invitation_code = md5($value.microtime());
            }
            $new_user = false; // User not already invited
            // Store the invitation if user_id not in $already_invited['course_users'] OR email is not in $already_invited['additional_users']
            $addit_users_array = isset($already_invited['additional_users']) && !empty($already_invited['additional_users']) ? explode(';', $already_invited['additional_users']) : array();

            $my_alredy_invited = $already_invited['course_users'] == null ? array() : $already_invited['course_users'];
            if ((is_numeric($value) && !in_array($value, $my_alredy_invited)) ||
                (!is_numeric($value) && !in_array($value, $addit_users_array))
            ) {
                $new_user = true;
                if (!array_key_exists($value, $survey_invitations)) {
                    $params = array(
                        'c_id' => $course_id,
                        'session_id' => $session_id,
                        'user' => $value,
                        'survey_code' => $survey_data['code'],
                        'invitation_code' => $invitation_code,
                        'invitation_date' => api_get_utc_datetime()
                    );
                    self::save_invitation($params);
                }
            }

            // Send the email if checkboxed
            if (($new_user || $reminder == 1) && $sendmail != 0) {
                // Make a change for absolute url
                if (isset($invitation_text)) {
                    $invitation_text = api_html_entity_decode($invitation_text, ENT_QUOTES);
                    $invitation_text = str_replace('src="../../', 'src="'.api_get_path(WEB_PATH), $invitation_text);
                    $invitation_text = trim(stripslashes($invitation_text));
                }
                SurveyUtil::send_invitation_mail($value, $invitation_code, $invitation_title, $invitation_text);
                $counter++;
            }
        }

        return $counter; // Number of invitations sent
    }

    /**
     * @param $params
     * @return bool|int
     */
    public static function save_invitation($params)
    {
        // Database table to store the invitations data
        $table = Database::get_course_table(TABLE_SURVEY_INVITATION);
        if (!empty($params['c_id']) &&
            (!empty($params['user']) || !empty($params['group_id'])) &&
            !empty($params['survey_code'])
        ) {
            $insertId = Database::insert($table, $params);
            if ($insertId) {

                $sql = "UPDATE $table SET survey_invitation_id = $insertId
                        WHERE iid = $insertId";
                Database::query($sql);
            }
            return $insertId;
        }
        return false;
    }

    /**
     * @param int $courseId
     * @param int $sessionId
     * @param int $groupId
     * @param string $surveyCode
     * @return int
     */
    public static function invitationExists($courseId, $sessionId, $groupId, $surveyCode)
    {
        $table = Database::get_course_table(TABLE_SURVEY_INVITATION);
        $courseId = intval($courseId);
        $sessionId = intval($sessionId);
        $groupId = intval($groupId);
        $surveyCode = Database::escape_string($surveyCode);

        $sql = "SELECT survey_invitation_id FROM $table
                WHERE
                    c_id = $courseId AND
                    session_id = $sessionId AND
                    group_id = $groupId AND
                    survey_code = '$surveyCode'
                ";
        $result = Database::query($sql);

        return Database::num_rows($result);
    }

    /**
     * Send the invitation by mail.
     *
     * @param int invitedUser - the userId (course user) or emailaddress of additional user
     * $param string $invitation_code - the unique invitation code for the URL
     * @return	void
     */
    public static function send_invitation_mail($invitedUser, $invitation_code, $invitation_title, $invitation_text)
    {
        $_user = api_get_user_info();
        $_course = api_get_course_info();

        // Replacing the **link** part with a valid link for the user
        $survey_link = api_get_path(WEB_CODE_PATH).'survey/fillsurvey.php?course='.$_course['code'].'&invitationcode='.$invitation_code;
        $text_link = '<a href="'.$survey_link.'">'.get_lang('ClickHereToAnswerTheSurvey')."</a><br />\r\n<br />\r\n".get_lang('OrCopyPasteTheFollowingUrl')." <br />\r\n ".$survey_link;

        $replace_count = 0;
        $full_invitation_text = api_str_ireplace('**link**', $text_link ,$invitation_text, $replace_count);
        if ($replace_count < 1) {
            $full_invitation_text = $full_invitation_text."<br />\r\n<br />\r\n".$text_link;
        }

        // Sending the mail
        $sender_name  = api_get_person_name($_user['firstName'], $_user['lastName'], null, PERSON_NAME_EMAIL_ADDRESS);
        $sender_email = $_user['mail'];
        $sender_user_id = api_get_user_id();

        $replyto = array();
        if (api_get_setting('survey_email_sender_noreply') == 'noreply') {
            $noreply = api_get_setting('noreply_email_address');
            if (!empty($noreply)) {
                $replyto['Reply-to'] = $noreply;
                $sender_name = $noreply;
                $sender_email = $noreply;
                $sender_user_id = null;
            }
        }

        // Optionally: finding the e-mail of the course user
        if (is_numeric($invitedUser)) {
            $table_user = Database :: get_main_table(TABLE_MAIN_USER);
            $sql = "SELECT firstname, lastname, email FROM $table_user
                    WHERE user_id='".Database::escape_string($invitedUser)."'";
            $result = Database::query($sql);
            $row = Database::fetch_array($result);
            $recipient_email = $row['email'];
            $recipient_name = api_get_person_name($row['firstname'], $row['lastname'], null, PERSON_NAME_EMAIL_ADDRESS);

            MessageManager::send_message(
                $invitedUser,
                $invitation_title,
                $full_invitation_text,
                [],
                [],
                null,
                null,
                null,
                null,
                $sender_user_id
            );

        } else {
            /** @todo check if the address is a valid email	 */
            $recipient_email = $invitedUser;
            @api_mail_html(
                $recipient_name,
                $recipient_email,
                $invitation_title,
                $full_invitation_text,
                $sender_name,
                $sender_email,
                $replyto
            );
        }
    }

    /**
     * This function recalculates the number of users who have been invited and updates the survey table with this value.
     *
     * @param	string	Survey code
     * @return	void
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version January 2007
     */
    public static function update_count_invited($survey_code)
    {
        $course_id = api_get_course_int_id();

        // Database table definition
        $table_survey_invitation 	= Database :: get_course_table(TABLE_SURVEY_INVITATION);
        $table_survey 				= Database :: get_course_table(TABLE_SURVEY);

        // Counting the number of people that are invited
        $sql = "SELECT count(user) as total
                FROM $table_survey_invitation
		        WHERE
		            c_id = $course_id AND
		            survey_code = '".Database::escape_string($survey_code)."' AND
		            user <> ''
                ";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $total_invited = $row['total'];

        // Updating the field in the survey table
        $sql = "UPDATE $table_survey
		        SET invited = '".Database::escape_string($total_invited)."'
		        WHERE
		            c_id = $course_id AND
		            code = '".Database::escape_string($survey_code)."'
                ";
        Database::query($sql);
    }

    /**
     * This function gets all the invited users for a given survey code.
     *
     * @param	string	Survey code
     * @param	string	optional - course database
     * @return 	array	Array containing the course users and additional users (non course users)
     *
     * @todo consider making $defaults['additional_users'] also an array
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @author Julio Montoya, adding c_id fixes - Dec 2012
     * @version January 2007
     */
    public static function get_invited_users($survey_code, $course_code = '', $session_id = 0)
    {
        if (!empty($course_code)) {
            $course_info = api_get_course_info($course_code);
            $course_id = $course_info['real_id'];
        } else {
            $course_id = api_get_course_int_id();
        }

        if (empty($session_id)) {
            $session_id = api_get_session_id();
        }

        $table_survey_invitation = Database :: get_course_table(TABLE_SURVEY_INVITATION);
        $table_user = Database :: get_main_table(TABLE_MAIN_USER);

        // Selecting all the invitations of this survey AND the additional emailaddresses (the left join)
        $order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname' : ' ORDER BY lastname, firstname';
        $sql = "SELECT user, group_id
				FROM $table_survey_invitation as table_invitation
				WHERE
				    table_invitation.c_id = $course_id AND
                    survey_code='".Database::escape_string($survey_code)."' AND
                    session_id = $session_id
                ";

        $defaults = array();
        $defaults['course_users'] = array();
        $defaults['additional_users'] = array(); // Textarea
        $defaults['users'] = array(); // user and groups

        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            if (is_numeric($row['user'])) {
                $defaults['course_users'][] = $row['user'];
                $defaults['users'][] = 'USER:'.$row['user'];
            } else {
                if (!empty($row['user'])) {
                    $defaults['additional_users'][] = $row['user'];
                }
            }

            if (isset($row['group_id']) && !empty($row['group_id'])) {
                $defaults['users'][] = 'GROUP:'.$row['group_id'];
            }
        }

        if (!empty($defaults['course_users'])) {
            $user_ids = implode("','", $defaults['course_users']);
            $sql = "SELECT user_id FROM $table_user WHERE user_id IN ('$user_ids') $order_clause";
            $result = Database::query($sql);
            $fixed_users = array();
            while ($row = Database::fetch_array($result)) {
                $fixed_users[] = $row['user_id'];
            }
            $defaults['course_users'] = $fixed_users;
        }

        if (!empty($defaults['additional_users'])) {
            $defaults['additional_users'] = implode(';', $defaults['additional_users']);
        }
        return $defaults;
    }

    /**
     * Get all the invitations
     *
     * @param	string	Survey code
     * @return	array	Database rows matching the survey code
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version September 2007
     */
    public static function get_invitations($survey_code)
    {
        $course_id = api_get_course_int_id();
        // Database table definition
        $table_survey_invitation 	= Database :: get_course_table(TABLE_SURVEY_INVITATION);

        $sql = "SELECT * FROM $table_survey_invitation
		        WHERE
		            c_id = $course_id AND
		            survey_code = '".Database::escape_string($survey_code)."'";
        $result = Database::query($sql);
        $return = array();
        while ($row = Database::fetch_array($result)) {
            $return[$row['user']] = $row;
        }
        return $return;
    }

    /**
     * This function displays the form for searching a survey
     *
     * @return	void	(direct output)
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version January 2007
     *
     * @todo use quickforms
     * @todo consider moving this to surveymanager.inc.lib.php
     */
    public static function display_survey_search_form()
    {
        $url = api_get_path(WEB_CODE_PATH).'survey/survey_list.php?search=advanced&'.api_get_cidreq();
        $form = new FormValidator('search', 'get', $url);
        $form->addHeader(get_lang('SearchASurvey'));
        $form->addText('keyword_title', get_lang('Title'));
        $form->addText('keyword_code', get_lang('Code'));
        $form->addSelectLanguage('keyword_language', get_lang('Language'));
        $form->addHidden('cidReq', api_get_course_id());
        $form->addButtonSearch(get_lang('Search'), 'do_search');
        $form->display();
    }

    /**
     * Show table only visible by DRH users
     */
    public static function displaySurveyListForDrh()
    {
        $parameters = array();
        $parameters['cidReq'] = api_get_course_id();

        // Create a sortable table with survey-data
        $table = new SortableTable('surveys', 'get_number_of_surveys', 'get_survey_data_drh', 2);
        $table->set_additional_parameters($parameters);
        $table->set_header(0, '', false);
        $table->set_header(1, get_lang('SurveyName'));
        $table->set_header(2, get_lang('SurveyCode'));
        $table->set_header(3, get_lang('NumberOfQuestions'));
        $table->set_header(4, get_lang('Author'));
        $table->set_header(5, get_lang('AvailableFrom'));
        $table->set_header(6, get_lang('AvailableUntil'));
        $table->set_header(7, get_lang('Invite'));
        $table->set_header(8, get_lang('Anonymous'));
        $table->set_header(9, get_lang('Modify'), false, 'width="150"');
        $table->set_column_filter(8, 'anonymous_filter');
        $table->set_column_filter(9, 'modify_filter_drh');
        $table->display();
    }

    /**
     * This function displays the sortable table with all the surveys
     *
     * @return	void	(direct output)
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version January 2007
     */
    public static function display_survey_list()
    {
        $parameters = array();
        $parameters['cidReq'] = api_get_course_id();
        if (isset($_GET['do_search']) && $_GET['do_search']) {
            $message = get_lang('DisplaySearchResults').'<br />';
            $message .= '<a href="'.api_get_self().'?'.api_get_cidreq().'">'.get_lang('DisplayAll').'</a>';
            Display::display_normal_message($message, false);
        }

        // Create a sortable table with survey-data
        $table = new SortableTable('surveys', 'get_number_of_surveys', 'get_survey_data', 2);
        $table->set_additional_parameters($parameters);
        $table->set_header(0, '', false);
        $table->set_header(1, get_lang('SurveyName'));
        $table->set_header(2, get_lang('SurveyCode'));
        $table->set_header(3, get_lang('NumberOfQuestions'));
        $table->set_header(4, get_lang('Author'));
        //$table->set_header(5, get_lang('Language'));
        //$table->set_header(6, get_lang('Shared'));
        $table->set_header(5, get_lang('AvailableFrom'));
        $table->set_header(6, get_lang('AvailableUntil'));
        $table->set_header(7, get_lang('Invite'));
        $table->set_header(8, get_lang('Anonymous'));
        $table->set_header(9, get_lang('Modify'), false, 'width="150"');
        $table->set_column_filter(8, 'anonymous_filter');
        $table->set_column_filter(9, 'modify_filter');
        $table->set_form_actions(array('delete' => get_lang('DeleteSurvey')));
        $table->display();
    }

    /**
     * Survey list for coach
     */
    public static function display_survey_list_for_coach()
    {
        $parameters = array();
        $parameters['cidReq']=api_get_course_id();
        if (isset($_GET['do_search'])) {
            $message = get_lang('DisplaySearchResults').'<br />';
            $message .= '<a href="'.api_get_self().'?'.api_get_cidreq().'">'.get_lang('DisplayAll').'</a>';
            Display::display_normal_message($message, false);
        }

        // Create a sortable table with survey-data
        $table = new SortableTable('surveys_coach', 'get_number_of_surveys_for_coach', 'get_survey_data_for_coach', 2);
        $table->set_additional_parameters($parameters);
        $table->set_header(0, '', false);
        $table->set_header(1, get_lang('SurveyName'));
        $table->set_header(2, get_lang('SurveyCode'));
        $table->set_header(3, get_lang('NumberOfQuestions'));
        $table->set_header(4, get_lang('Author'));
        //$table->set_header(5, get_lang('Language'));
        //$table->set_header(6, get_lang('Shared'));
        $table->set_header(5, get_lang('AvailableFrom'));
        $table->set_header(6, get_lang('AvailableUntil'));
        $table->set_header(7, get_lang('Invite'));
        $table->set_header(8, get_lang('Anonymous'));
        $table->set_header(9, get_lang('Modify'), false, 'width="130"');
        $table->set_column_filter(8, 'anonymous_filter');
        $table->set_column_filter(9, 'modify_filter_for_coach');
        $table->display();
    }

    /**
     * This function changes the modify column of the sortable table
     *
     * @param integer $survey_id the id of the survey
     * @param bool $drh
     * @return string html code that are the actions that can be performed on any survey
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version January 2007
     */
    public static function modify_filter($survey_id, $drh = false)
    {
        $survey_id = Security::remove_XSS($survey_id);
        $return = '';

        if ($drh) {
            return '<a href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?'.api_get_cidreq().'&survey_id='.$survey_id.'">'.
            Display::return_icon('stats.png', get_lang('Reporting'),'',ICON_SIZE_SMALL).'</a>';
        }

        // Coach can see that only if the survey is in his session
        if (api_is_allowed_to_edit() ||
            api_is_element_in_the_session(TOOL_SURVEY, $survey_id)
        ) {
            $return .= '<a href="'.api_get_path(WEB_CODE_PATH).'survey/create_new_survey.php?'.api_get_cidreq().'&action=edit&survey_id='.$survey_id.'">'.Display::return_icon('edit.png', get_lang('Edit'),'',ICON_SIZE_SMALL).'</a>';
            if (SurveyManager::survey_generation_hash_available()) {
                $return .=  Display::url(
                    Display::return_icon('new_link.png', get_lang('GenerateSurveyAccessLink'),'',ICON_SIZE_SMALL),
                    api_get_path(WEB_CODE_PATH).'survey/generate_link.php?survey_id='.$survey_id.'&'.api_get_cidreq()
                );
            }
            $return .= Display::url(
                Display::return_icon('copy.png', get_lang('DuplicateSurvey'), '', ICON_SIZE_SMALL),
                'survey_list.php?action=copy_survey&survey_id='.$survey_id.'&'.api_get_cidreq()
            );

            $return .= ' <a href="'.api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq().'&action=empty&survey_id='.$survey_id.'" onclick="javascript: if(!confirm(\''.addslashes(api_htmlentities(get_lang("EmptySurvey").'?')).'\')) return false;">'.
                Display::return_icon('clean.png', get_lang('EmptySurvey'),'',ICON_SIZE_SMALL).'</a>&nbsp;';
        }
        $return .= '<a href="'.api_get_path(WEB_CODE_PATH).'survey/preview.php?'.api_get_cidreq().'&survey_id='.$survey_id.'">'.
            Display::return_icon('preview_view.png', get_lang('Preview'),'',ICON_SIZE_SMALL).'</a>&nbsp;';
        $return .= '<a href="'.api_get_path(WEB_CODE_PATH).'survey/survey_invite.php?'.api_get_cidreq().'&survey_id='.$survey_id.'">'.
            Display::return_icon('mail_send.png', get_lang('Publish'),'',ICON_SIZE_SMALL).'</a>&nbsp;';
        $return .= '<a href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?'.api_get_cidreq().'&survey_id='.$survey_id.'">'.
            Display::return_icon('stats.png', get_lang('Reporting'),'',ICON_SIZE_SMALL).'</a>';

        if (api_is_allowed_to_edit() ||
            api_is_element_in_the_session(TOOL_SURVEY, $survey_id)
        ) {
            $return .= '<a href="'.api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq().'&action=delete&survey_id='.$survey_id.'" onclick="javascript: if(!confirm(\''.addslashes(api_htmlentities(get_lang("DeleteSurvey").'?', ENT_QUOTES)).'\')) return false;">'.
                Display::return_icon('delete.png', get_lang('Delete'),'',ICON_SIZE_SMALL).'</a>&nbsp;';
        }

        return $return;
    }

    public static function modify_filter_for_coach($survey_id)
    {
        $survey_id = Security::remove_XSS($survey_id);
        //$return = '<a href="create_new_survey.php?'.api_get_cidreq().'&action=edit&survey_id='.$survey_id.'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
        //$return .= '<a href="survey_list.php?'.api_get_cidreq().'&action=delete&survey_id='.$survey_id.'" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang("DeleteSurvey").'?', ENT_QUOTES)).'\')) return false;">'.Display::return_icon('delete.gif', get_lang('Delete')).'</a>';
        //$return .= '<a href="create_survey_in_another_language.php?id_survey='.$survey_id.'">'.Display::return_icon('copy.gif', get_lang('Copy')).'</a>';
        //$return .= '<a href="survey.php?survey_id='.$survey_id.'">'.Display::return_icon('add.gif', get_lang('Add')).'</a>';
        $return = '<a href="'.api_get_path(WEB_CODE_PATH).'survey/preview.php?'.api_get_cidreq().'&survey_id='.$survey_id.'">'.Display::return_icon('preview_view.png', get_lang('Preview'),'',ICON_SIZE_SMALL).'</a>&nbsp;';
        $return .= '<a href="'.api_get_path(WEB_CODE_PATH).'survey/survey_invite.php?'.api_get_cidreq().'&survey_id='.$survey_id.'">'.Display::return_icon('mail_send.png', get_lang('Publish'),'',ICON_SIZE_SMALL).'</a>&nbsp;';
        $return .= '<a href="'.api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq().'&action=empty&survey_id='.$survey_id.'" onclick="javascript: if(!confirm(\''.addslashes(api_htmlentities(get_lang("EmptySurvey").'?', ENT_QUOTES)).'\')) return false;">'.Display::return_icon('clean.png', get_lang('EmptySurvey'),'',ICON_SIZE_SMALL).'</a>&nbsp;';

        return $return;
    }

    /**
     * Returns "yes" when given parameter is one, "no" for any other value
     * @param	integer	Whether anonymous or not
     * @return	string	"Yes" or "No" in the current language
     */
    public static function anonymous_filter($anonymous)
    {
        if ($anonymous == 1) {
            return get_lang('Yes');
        } else {
            return get_lang('No');
        }
    }

    /**
     * This function handles the search restriction for the SQL statements
     *
     * @return	string	Part of a SQL statement or false on error
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version January 2007
     */
    public static function survey_search_restriction()
    {
        if (isset($_GET['do_search'])) {
            if ($_GET['keyword_title'] != '') {
                $search_term[] = 'title like "%" \''.Database::escape_string($_GET['keyword_title']).'\' "%"';
            }
            if ($_GET['keyword_code'] != '') {
                $search_term[] = 'code =\''.Database::escape_string($_GET['keyword_code']).'\'';
            }
            if ($_GET['keyword_language'] != '%') {
                $search_term[] = 'lang =\''.Database::escape_string($_GET['keyword_language']).'\'';
            }
            $my_search_term = ($search_term == null) ? array() : $search_term;
            $search_restriction = implode(' AND ', $my_search_term);
            return $search_restriction;
        } else {
            return false;
        }
    }

    /**
     * This function calculates the total number of surveys
     *
     * @return	integer	Total number of surveys
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version January 2007
     */
    public static function get_number_of_surveys()
    {
        $table_survey = Database :: get_course_table(TABLE_SURVEY);
        $course_id = api_get_course_int_id();

        $search_restriction = SurveyUtil::survey_search_restriction();
        if ($search_restriction) {
            $search_restriction = 'WHERE c_id = '.$course_id.' AND '.$search_restriction;
        } else {
            $search_restriction = "WHERE c_id = $course_id";
        }
        $sql = "SELECT count(survey_id) AS total_number_of_items
		        FROM ".$table_survey.' '.$search_restriction;
        $res = Database::query($sql);
        $obj = Database::fetch_object($res);
        return $obj->total_number_of_items;
    }

    public static function get_number_of_surveys_for_coach()
    {
        $survey_tree = new SurveyTree();
        return count($survey_tree->surveylist);
    }

    /**
     * This function gets all the survey data that is to be displayed in the sortable table
     *
     * @param int $from
     * @param int $number_of_items
     * @param int $column
     * @param string $direction
     * @param bool $isDrh
     * @return unknown
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @author Julio Montoya <gugli100@gmail.com>, Beeznest - Adding intvals
     * @version January 2007
     */
    public static function get_survey_data($from, $number_of_items, $column, $direction, $isDrh = false)
    {
        $table_survey = Database :: get_course_table(TABLE_SURVEY);
        $table_user = Database :: get_main_table(TABLE_MAIN_USER);
        $table_survey_question = Database :: get_course_table(TABLE_SURVEY_QUESTION);
        $_user = api_get_user_info();

        // Searching
        $search_restriction = SurveyUtil::survey_search_restriction();
        if ($search_restriction) {
            $search_restriction = ' AND '.$search_restriction;
        }
        $from = intval($from);
        $number_of_items = intval($number_of_items);
        $column = intval($column);
        if (!in_array(strtolower($direction), array('asc', 'desc'))) {
            $direction = 'asc';
        }

        // Condition for the session
        $session_id = api_get_session_id();
        $condition_session = api_get_session_condition($session_id);
        $course_id = api_get_course_int_id();

        $sql = "SELECT
					survey.survey_id AS col0,
					survey.title AS col1,
					survey.code AS col2,
					count(survey_question.question_id) AS col3,
					".(api_is_western_name_order() ? "CONCAT(user.firstname, ' ', user.lastname)" : "CONCAT(user.lastname, ' ', user.firstname)")."	AS col4,
					survey.avail_from AS col5,
					survey.avail_till AS col6,
					survey.invited AS col7,
					survey.anonymous AS col8,
					survey.survey_id AS col9,
					survey.session_id AS session_id,
					survey.answered,
					survey.invited
                FROM $table_survey survey
                LEFT JOIN $table_survey_question survey_question
                ON (survey.survey_id = survey_question.survey_id AND survey_question.c_id = $course_id)
                LEFT JOIN $table_user user
                ON (survey.author = user.user_id)
                WHERE survey.c_id = $course_id
                $search_restriction
                $condition_session ";
        $sql .= " GROUP BY survey.survey_id";
        $sql .= " ORDER BY col$column $direction ";
        $sql .= " LIMIT $from,$number_of_items";

        $res = Database::query($sql);
        $surveys = array();
        $array = array();
        while ($survey = Database::fetch_array($res)) {
            $array[0] = $survey[0];
            $array[1] = Display::url(
                $survey[1],
                api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.$survey[0].'&'.api_get_cidreq()
            );

            // Validation when belonging to a session
            $session_img = api_get_session_image($survey['session_id'], $_user['status']);
            $array[2] = $survey[2] . $session_img;
            $array[3] = $survey[3];
            $array[4] = $survey[4];
            $array[5] = $survey[5];
            $array[6] = $survey[6];
            $array[7] =
                Display::url(
                    $survey['answered'],
                    api_get_path(WEB_CODE_PATH).'survey/survey_invitation.php?view=answered&survey_id='.$survey[0].'&'.api_get_cidreq()
                ).' / '.
                Display::url(
                    $survey['invited'],
                    api_get_path(WEB_CODE_PATH).'survey/survey_invitation.php?view=invited&survey_id='.$survey[0].'&'.api_get_cidreq()
                );

            $array[8] = $survey[8];
            $array[9] = $survey[9];

            if ($isDrh) {
                $array[1] = $survey[1];
                $array[7] = strip_tags($array[7]);
            }

            $surveys[] = $array;
        }
        return $surveys;
    }

    /**
     * @param $from
     * @param $number_of_items
     * @param $column
     * @param $direction
     * @return array
     */
    public static function get_survey_data_for_coach($from, $number_of_items, $column, $direction)
    {
        $survey_tree = new SurveyTree();
        //$last_version_surveys = $survey_tree->get_last_children_from_branch($survey_tree->surveylist);
        $last_version_surveys = $survey_tree->surveylist;
        $list = array();
        foreach ($last_version_surveys as & $survey) {
            $list[]=$survey['id'];
        }
        if (count($list) > 0) {
            $list_condition = " AND survey.survey_id IN (".implode(',',$list).") ";
        } else {
            $list_condition = '';
        }

        $from = intval($from);
        $number_of_items = intval($number_of_items);
        $column = intval($column);
        if (!in_array(strtolower($direction), array('asc', 'desc'))) {
            $direction = 'asc';
        }

        $table_survey = Database:: get_course_table(TABLE_SURVEY);
        $table_survey_question = Database:: get_course_table(TABLE_SURVEY_QUESTION);
        $table_user = Database:: get_main_table(TABLE_MAIN_USER);

        $course_id = api_get_course_int_id();

        $sql = "SELECT 
            survey.survey_id							AS col0, 
            survey.title AS col1, 
            survey.code AS col2, 
            count(survey_question.question_id)			AS col3, 
            ".(api_is_western_name_order() ? "CONCAT(user.firstname, ' ', user.lastname)" : "CONCAT(user.lastname, ' ', user.firstname)")."	AS col4,
            survey.avail_from AS col5,
            survey.avail_till AS col6,
            CONCAT('<a href=\"survey_invitation.php?view=answered&survey_id=',survey.survey_id,'\">',survey.answered,'</a> / <a href=\"survey_invitation.php?view=invited&survey_id=',survey.survey_id,'\">',survey.invited, '</a>') AS col7,
            survey.anonymous AS col8,
            survey.survey_id AS col9
            FROM $table_survey survey
            LEFT JOIN $table_survey_question survey_question
            ON (survey.survey_id = survey_question.survey_id AND survey.c_id = survey_question.c_id),
            $table_user user
            WHERE survey.author = user.user_id AND survey.c_id = $course_id $list_condition ";
        $sql .= " GROUP BY survey.survey_id";
        $sql .= " ORDER BY col$column $direction ";
        $sql .= " LIMIT $from,$number_of_items";

        $res = Database::query($sql);
        $surveys = array();
        while ($survey = Database::fetch_array($res)) {
            $surveys[] = $survey;
        }

        return $surveys;
    }

    /**
     * Display all the active surveys for the given course user
     *
     * @param int $user_id
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @version April 2007
     */
    public static function getSurveyList($user_id)
    {
        $_course = api_get_course_info();
        $course_id = $_course['real_id'];
        $user_id = intval($user_id);
        $sessionId = api_get_session_id();

        // Database table definitions
        $table_survey_question = Database :: get_course_table(TABLE_SURVEY_QUESTION);
        $table_survey_invitation = Database :: get_course_table(TABLE_SURVEY_INVITATION);
        $table_survey_answer = Database :: get_course_table(TABLE_SURVEY_ANSWER);
        $table_survey = Database:: get_course_table(TABLE_SURVEY);

        $sql = "SELECT question_id
                FROM $table_survey_question
                WHERE c_id = $course_id";
        $result = Database::query($sql);

        $all_question_id = array();
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $all_question_id[] = $row;
        }

        $count = 0;
        for ($i = 0; $i < count($all_question_id); $i++) {
            $sql = 'SELECT COUNT(*) as count
			        FROM '.$table_survey_answer.'
					WHERE
					    c_id = '.$course_id.' AND
					    question_id='.intval($all_question_id[$i]['question_id']).' AND
					    user = '.$user_id;
            $result = Database::query($sql);
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                if ($row['count'] == 0) {
                    $count++;
                    break;
                }
            }
            if ($count > 0) {
                $link_add = true;
                break;
            }
        }

        echo '<table id="list-survey" class="table ">';
        echo '<tr>';
        echo '	<th>'.get_lang('SurveyName').'</th>';
        echo '	<th>'.get_lang('Anonymous').'</th>';
        echo '</tr>';

        $now = api_get_utc_datetime();

        $sql = "SELECT *
                FROM $table_survey survey INNER JOIN
                $table_survey_invitation survey_invitation
                ON (
                    survey.code = survey_invitation.survey_code AND
                    survey.c_id = survey_invitation.c_id
                )
				WHERE
                    survey_invitation.user = $user_id AND                    
                    survey.avail_from <= '".$now."' AND
                    survey.avail_till >= '".$now."' AND
                    survey.c_id = $course_id AND
                    survey.session_id = $sessionId AND
                    survey_invitation.c_id = $course_id
				";
        $result = Database::query($sql);

        while ($row = Database::fetch_array($result, 'ASSOC')) {
            echo '<tr>';
            if ($row['answered'] == 0) {
                echo '<td>';
                echo Display::return_icon('statistics.png', get_lang('CreateNewSurvey'), array(),ICON_SIZE_TINY);
                echo '<a href="'.api_get_path(WEB_CODE_PATH).'survey/fillsurvey.php?course='.$_course['sysCode'].'&invitationcode='.$row['invitation_code'].'&cidReq='.$_course['sysCode'].'">'.$row['title'].'</a></td>';
            } else {
                echo '<td>';
                echo Display::return_icon('statistics_na.png', get_lang('CreateNewSurvey'), array(),ICON_SIZE_TINY);
                echo '<a href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?action=questionreport&cidReq='.$_course['sysCode'].'&id_session='.$row['session_id'].'&gidReq=0&origin=&survey_id='.$row['survey_id'].'">'.$row['title'].'</a></td>';
            }
            echo '<td class="center">';
            echo ($row['anonymous'] == 1) ? get_lang('Yes') : get_lang('No');
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }

    /**
     * Creates a multi array with the user fields that we can show. We look the visibility with the api_get_setting function
     * The username is always NOT able to change it.
     * @author Julio Montoya Armas <gugli100@gmail.com>, Chamilo: Personality Test modification
     * @return array[value_name][name]
     * 		   array[value_name][visibilty]
     */
    public static function make_field_list()
    {
        //	LAST NAME and FIRST NAME
        $field_list_array = array();
        $field_list_array['lastname']['name'] = get_lang('LastName');
        $field_list_array['firstname']['name'] = get_lang('FirstName');

        if (api_get_setting('profile', 'name') != 'true') {
            $field_list_array['firstname']['visibility'] = 0;
            $field_list_array['lastname']['visibility'] = 0;
        } else {
            $field_list_array['firstname']['visibility'] = 1;
            $field_list_array['lastname']['visibility'] = 1;
        }

        $field_list_array['username']['name'] = get_lang('Username');
        $field_list_array['username']['visibility'] = 0;

        //	OFFICIAL CODE
        $field_list_array['official_code']['name'] = get_lang('OfficialCode');

        if (api_get_setting('profile', 'officialcode') != 'true') {
            $field_list_array['official_code']['visibility'] = 1;
        } else {
            $field_list_array['official_code']['visibility'] = 0;
        }

        // EMAIL
        $field_list_array['email']['name'] = get_lang('Email');
        if (api_get_setting('profile', 'email') != 'true') {
            $field_list_array['email']['visibility'] = 1;
        } else {
            $field_list_array['email']['visibility'] = 0;
        }

        // PHONE
        $field_list_array['phone']['name'] = get_lang('Phone');
        if (api_get_setting('profile', 'phone') != 'true') {
            $field_list_array['phone']['visibility'] = 0;
        } else {
            $field_list_array['phone']['visibility'] = 1;
        }
        //	LANGUAGE
        $field_list_array['language']['name'] = get_lang('Language');
        if (api_get_setting('profile', 'language') != 'true') {
            $field_list_array['language']['visibility'] = 0;
        } else {
            $field_list_array['language']['visibility'] = 1;
        }

        // EXTRA FIELDS
        $extra = UserManager::get_extra_fields(0, 50, 5, 'ASC');

        foreach ($extra as $id => $field_details) {
            if ($field_details[6] == 0) {
                continue;
            }
            switch ($field_details[2]) {
                case UserManager::USER_FIELD_TYPE_TEXT:
                    $field_list_array['extra_'.$field_details[1]]['name'] = $field_details[3];
                    if ($field_details[7] == 0) {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 0;
                    } else {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 1;
                    }
                    break;
                case UserManager::USER_FIELD_TYPE_TEXTAREA:
                    $field_list_array['extra_'.$field_details[1]]['name'] = $field_details[3];
                    if ($field_details[7] == 0) {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 0;
                    } else {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 1;
                    }
                    break;
                case UserManager::USER_FIELD_TYPE_RADIO:
                    $field_list_array['extra_'.$field_details[1]]['name'] = $field_details[3];
                    if ($field_details[7] == 0) {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 0;
                    } else {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 1;
                    }
                    break;
                case UserManager::USER_FIELD_TYPE_SELECT:
                    $get_lang_variables = false;
                    if (in_array($field_details[1], array('mail_notify_message', 'mail_notify_invitation', 'mail_notify_group_message'))) {
                        $get_lang_variables = true;
                    }

                    if ($get_lang_variables) {
                        $field_list_array['extra_'.$field_details[1]]['name'] = get_lang($field_details[3]);
                    } else {
                        $field_list_array['extra_'.$field_details[1]]['name'] = $field_details[3];
                    }

                    if ($field_details[7] == 0) {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 0;
                    } else {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 1;
                    }
                    break;
                case UserManager::USER_FIELD_TYPE_SELECT_MULTIPLE:
                    $field_list_array['extra_'.$field_details[1]]['name'] = $field_details[3];
                    if ($field_details[7] == 0) {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 0;
                    } else {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 1;
                    }
                    break;
                case UserManager::USER_FIELD_TYPE_DATE:
                    $field_list_array['extra_'.$field_details[1]]['name'] = $field_details[3];
                    if ($field_details[7] == 0) {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 0;
                    } else {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 1;
                    }
                    break;
                case UserManager::USER_FIELD_TYPE_DATETIME:
                    $field_list_array['extra_'.$field_details[1]]['name'] = $field_details[3];
                    if ($field_details[7] == 0) {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 0;
                    } else {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 1;
                    }
                    break;
                case UserManager::USER_FIELD_TYPE_DOUBLE_SELECT:
                    $field_list_array['extra_'.$field_details[1]]['name'] = $field_details[3];
                    if ($field_details[7] == 0) {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 0;
                    } else {
                        $field_list_array['extra_'.$field_details[1]]['visibility']=1;
                    }
                    break;
                case UserManager::USER_FIELD_TYPE_DIVIDER:
                    //$form->addElement('static',$field_details[1], '<br /><strong>'.$field_details[3].'</strong>');
                    break;
            }
        }
        return $field_list_array;
    }

    /**
     * @author Isaac Flores Paz <florespaz@bidsoftperu.com>
     * @param int $user_id - User ID
     * @param string $survey_code
     * @param int $user_id_answer - User in survey answer table (user id or anonymus)
     *
     * @return boolean
     */
    public static function show_link_available($user_id, $survey_code, $user_answer)
    {
        $table_survey = Database:: get_course_table(TABLE_SURVEY);
        $table_survey_invitation = Database:: get_course_table(TABLE_SURVEY_INVITATION);
        $table_survey_answer = Database:: get_course_table(TABLE_SURVEY_ANSWER);
        $table_survey_question = Database:: get_course_table(TABLE_SURVEY_QUESTION);

        $survey_code = Database::escape_string($survey_code);
        $user_id = intval($user_id);
        $user_answer = Database::escape_string($user_answer);

        $course_id = api_get_course_int_id();

        $sql = 'SELECT COUNT(*) as count
                FROM '.$table_survey_invitation.'
		        WHERE
		            user='.$user_id.' AND
		            survey_code="'.$survey_code.'" AND 
		            answered="1" AND 
		            c_id = '.$course_id;

        $sql2 = 'SELECT COUNT(*) as count FROM '.$table_survey.' s 
                 INNER JOIN '.$table_survey_question.' q 
                 ON s.survey_id=q.survey_id
				 WHERE 
				    s.code="'.$survey_code.'" AND 
				    q.type NOT IN("pagebreak","comment") AND s.c_id = '.$course_id.' AND q.c_id = '.$course_id.' ';

        $sql3 = 'SELECT COUNT(DISTINCT question_id) as count 
                 FROM '.$table_survey_answer.'
				 WHERE survey_id=(
				    SELECT survey_id FROM '.$table_survey.'
				    WHERE 
				        code = "'.$survey_code.'" AND 
				        c_id = '.$course_id.' 
                    )  AND 
                user="'.$user_answer.'" AND 
                c_id = '.$course_id;

        $result  = Database::query($sql);
        $result2 = Database::query($sql2);
        $result3 = Database::query($sql3);

        $row  = Database::fetch_array($result, 'ASSOC');
        $row2 = Database::fetch_array($result2, 'ASSOC');
        $row3 = Database::fetch_array($result3, 'ASSOC');

        if ($row['count'] == 1 && $row3['count'] != $row2['count']) {

            return true;
        } else {
            return false;
        }
    }

    /**
     * Display survey question chart
     * @param   array	$chartData
     * @param   boolean	$hasSerie Tells if the chart has a serie. False by default
     * @param   string $chartContainerId
     * @return	string 	(direct output)
     */
    public static function drawChart($chartData, $hasSerie = false, $chartContainerId = 'chartContainer')
    {
        $htmlChart = '';
        if (api_browser_support("svg")) {
            $htmlChart .= api_get_js("d3/d3.v3.5.4.min.js");
            $htmlChart .= api_get_js("dimple.v2.1.2.min.js") . '
            <script type="text/javascript">
            var svg = dimple.newSvg("#'.$chartContainerId.'", "100%", 400);
            var data = [';
            $serie = array();
            $order = array();
            foreach ($chartData as $chartDataElement) {
                $htmlChart .= '{"';
                if (!$hasSerie) {
                    $htmlChart .= get_lang("Option") . '":"' . $chartDataElement['option'] . '", "';
                    array_push($order, $chartDataElement['option']);
                } else {
                    if (!is_array($chartDataElement['serie'])) {
                        $htmlChart .= get_lang("Option") . '":"' . $chartDataElement['serie'] . '", "' .
                            get_lang("Score") . '":"' . $chartDataElement['option'] . '", "';
                        array_push($serie, $chartDataElement['serie']);
                    } else {
                        $htmlChart .= get_lang("Serie") . '":"' . $chartDataElement['serie'][0] . '", "' .
                            get_lang("Option") . '":"' . $chartDataElement['serie'][1] . '", "' .
                            get_lang("Score") . '":"' . $chartDataElement['option'] . '", "';
                    }
                }
                $htmlChart .= get_lang("Votes") . '":"' . $chartDataElement['votes'] .
                    '"},';
            }
            rtrim($htmlChart, ",");
            $htmlChart .= '];
                var myChart = new dimple.chart(svg, data);
                myChart.addMeasureAxis("y", "' . get_lang("Votes") . '");';
            if (!$hasSerie) {
                $htmlChart .= 'var xAxisCategory = myChart.addCategoryAxis("x", "' . get_lang("Option") . '");
                    xAxisCategory.addOrderRule(' . json_encode($order) . ');
                    myChart.addSeries("' . get_lang("Option") . '", dimple.plot.bar);';
            } else {
                if (!is_array($chartDataElement['serie'])) {
                    $serie = array_values(array_unique($serie));
                    $htmlChart .= 'var xAxisCategory = myChart.addCategoryAxis("x", ["' . get_lang("Option") . '","' . get_lang("Score") . '"]);
                        xAxisCategory.addOrderRule(' . json_encode($serie) . ');
                        xAxisCategory.addGroupOrderRule("' . get_lang("Score") . '");
                        myChart.addSeries("' . get_lang("Option") . '", dimple.plot.bar);';
                } else {
                    $htmlChart .= 'myChart.addCategoryAxis("x", ["' . get_lang("Option") . '","' . get_lang("Score") . '"]);
                        myChart.addSeries("' . get_lang("Serie") . '", dimple.plot.bar);';
                }
            }
            $htmlChart .= 'myChart.draw();
                </script>';
        }

        return $htmlChart;
    }

    /**
     * Set a flag to the current survey as answered by the current user
     * @param string $surveyCode The survey code
     * @param int $courseId The course ID
     */
    public static function flagSurveyAsAnswered($surveyCode, $courseId)
    {
        $currenUserId = api_get_user_id();
        $flag = sprintf("%s-%s-%d", $courseId, $surveyCode, $currenUserId);

        if (!isset($_SESSION['filled_surveys'])) {
            $_SESSION['filled_surveys'] = array();
        }

        $_SESSION['filled_surveys'][] = $flag;
    }

    /**
     * Check whether a survey was answered by the current user
     * @param string $surveyCode The survey code
     * @param int $courseId The course ID
     * @return boolean
     */
    public static function isSurveyAnsweredFlagged($surveyCode, $courseId)
    {
        $currenUserId = api_get_user_id();
        $flagToCheck = sprintf("%s-%s-%d", $courseId, $surveyCode, $currenUserId);

        if (!isset($_SESSION['filled_surveys'])) {
            return false;
        }

        if (!is_array($_SESSION['filled_surveys'])) {
            return false;
        }

        foreach ($_SESSION['filled_surveys'] as $flag) {
            if ($flagToCheck != $flag) {
                continue;
            }

            return true;
        }

        return false;
    }
}
