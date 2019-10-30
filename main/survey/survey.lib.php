<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyInvitation;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use Chamilo\CourseBundle\Entity\CSurveyQuestionOption;

/**
 * Class SurveyManager.
 *
 * @package chamilo.survey
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University:
 * cleanup, refactoring and rewriting large parts (if not all) of the code
 * @author Julio Montoya <gugli100@gmail.com>, Personality Test modification
 * and rewriting large parts of the code
 * @author cfasanando
 *
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
        $table = Database::get_course_table(TABLE_SURVEY);
        $code = Database::escape_string($code);
        $num = 0;
        $new_code = $code;
        while (true) {
            $sql = "SELECT * FROM $table
                    WHERE code = '$new_code' AND c_id = $course_id";
            $result = Database::query($sql);
            if (Database::num_rows($result)) {
                $num++;
                $new_code = $code.$num;
            } else {
                break;
            }
        }

        return $code.$num;
    }

    /**
     * Deletes all survey invitations of a user.
     *
     * @param int $user_id
     *
     * @return bool
     * @assert ('') === false
     */
    public static function delete_all_survey_invitations_by_user($user_id)
    {
        $user_id = (int) $user_id;
        if (empty($user_id)) {
            return false;
        }
        $table_survey_invitation = Database::get_course_table(TABLE_SURVEY_INVITATION);
        $table_survey = Database::get_course_table(TABLE_SURVEY);

        $sql = "SELECT survey_invitation_id, survey_code
                FROM $table_survey_invitation WHERE user = '$user_id' AND c_id <> 0 ";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result, 'ASSOC')) {
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
     * @param string $course_code
     * @param int    $session_id
     *
     * @return array
     * @assert ('') === false
     */
    public static function get_surveys($course_code, $session_id = 0)
    {
        if (empty($course_code)) {
            return false;
        }
        $course_info = api_get_course_info($course_code);

        if (empty($course_info)) {
            return false;
        }

        $sessionCondition = api_get_session_condition($session_id, true, true);

        $table = Database::get_course_table(TABLE_SURVEY);
        $sql = "SELECT * FROM $table
                WHERE c_id = {$course_info['real_id']} $sessionCondition ";
        $result = Database::query($sql);
        $result = Database::store_result($result, 'ASSOC');

        return $result;
    }

    /**
     * Retrieves all the survey information.
     *
     * @param int  $survey_id the id of the survey
     * @param bool $shared    this parameter determines if
     *                        we have to get the information of a survey from the central (shared) database or from the
     *                        course database
     * @param string course code optional
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version February 2007
     * @assert ('') === false
     *
     * @return array
     *
     * @todo this is the same function as in create_new_survey.php
     */
    public static function get_survey(
        $survey_id,
        $shared = 0,
        $course_code = '',
        $simple_return = false
    ) {
        $my_course_id = api_get_course_id();

        // Table definition
        if (!empty($course_code)) {
            $my_course_id = $course_code;
        } elseif (isset($_GET['course'])) {
            $my_course_id = Security::remove_XSS($_GET['course']);
        }

        $courseInfo = api_get_course_info($my_course_id);
        $survey_id = (int) $survey_id;
        $table_survey = Database::get_course_table(TABLE_SURVEY);

        if ($shared != 0) {
            $table_survey = Database::get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION);
            $sql = "SELECT * FROM $table_survey
                    WHERE survey_id='".$survey_id."' ";
        } else {
            if (empty($courseInfo)) {
                return [];
            }
            $sql = "SELECT * FROM $table_survey
		            WHERE
		                survey_id='".$survey_id."' AND
		                c_id = ".$courseInfo['real_id'];
        }

        $result = Database::query($sql);
        $return = [];

        if (Database::num_rows($result) > 0) {
            $return = Database::fetch_array($result, 'ASSOC');
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
            $return['c_id'] = isset($return['c_id']) ? $return['c_id'] : 0;
            $return['session_id'] = isset($return['session_id']) ? $return['session_id'] : 0;
        }

        return $return;
    }

    /**
     * @param string $code
     *
     * @return string
     */
    public static function generateSurveyCode($code)
    {
        return strtolower(CourseManager::generate_course_code($code));
    }

    /**
     * This function stores a survey in the database.
     *
     * @param array $values
     *
     * @return array $return the type of return message that has to be displayed and the message in it
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version February 2007
     */
    public static function store_survey($values)
    {
        $allowSurveyAvailabilityDatetime = api_get_configuration_value('allow_survey_availability_datetime');
        $_user = api_get_user_info();
        $course_id = api_get_course_int_id();
        $session_id = api_get_session_id();
        $courseCode = api_get_course_id();
        $table_survey = Database::get_course_table(TABLE_SURVEY);
        $shared_survey_id = 0;

        if (!isset($values['survey_id'])) {
            // Check if the code doesn't soon exists in this language
            $sql = 'SELECT 1 FROM '.$table_survey.'
			        WHERE
			            c_id = '.$course_id.' AND
			            code = "'.Database::escape_string($values['survey_code']).'" AND
			            lang = "'.Database::escape_string($values['survey_language']).'"';
            $rs = Database::query($sql);
            if (Database::num_rows($rs) > 0) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('This survey code soon exists in this language'),
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

            $values['anonymous'] = (int) $values['anonymous'];

            $survey = new CSurvey();

            $extraParams = [];
            if ($values['anonymous'] == 0) {
                // Input_name_list
                $values['show_form_profile'] = isset($values['show_form_profile']) ? $values['show_form_profile'] : 0;
                $survey->setShowFormProfile($values['show_form_profile']);

                if ($values['show_form_profile'] == 1) {
                    // Input_name_list
                    $fields = explode(',', $values['input_name_list']);
                    $field_values = '';
                    foreach ($fields as &$field) {
                        if ($field != '') {
                            if ($values[$field] == '') {
                                $values[$field] = 0;
                            }
                            $field_values .= $field.':'.$values[$field].'@';
                        }
                    }
                    $extraParams['form_fields'] = $field_values;
                } else {
                    $extraParams['form_fields'] = '';
                }
                $survey->setFormFields($extraParams['form_fields']);
            } else {
                $survey->setShowFormProfile(0);
                $survey->setFormFields(0);
            }

            $extraParams['one_question_per_page'] = isset($values['one_question_per_page']) ? $values['one_question_per_page'] : 0;
            $extraParams['shuffle'] = isset($values['shuffle']) ? $values['shuffle'] : 0;

            if ($values['survey_type'] == 1) {
                $survey
                    ->setSurveyType(1)
                    ->setShuffle($values['shuffle'])
                    ->setOneQuestionPerPage($values['one_question_per_page'])
                    ->setParentId($values['parent_id'])
                ;
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
                    $survey->setSurveyVersion($versionValue);
                }
            }

            $from = api_get_utc_datetime($values['start_date'].'00:00:00', true, true);
            $until = api_get_utc_datetime($values['end_date'].'23:59:59', true, true);
            if ($allowSurveyAvailabilityDatetime) {
                $from = api_get_utc_datetime($values['start_date'].':00', true, true);
                $until = api_get_utc_datetime($values['end_date'].':59', true, true);
            }

            $survey
                ->setCId($course_id)
                ->setCode(self::generateSurveyCode($values['survey_code']))
                ->setTitle($values['survey_title'])
                ->setSubtitle($values['survey_title'])
                ->setAuthor($_user['user_id'])
                ->setLang($values['survey_language'])
                ->setAvailFrom($from)
                ->setAvailTill($until)
                ->setIsShared($shared_survey_id)
                ->setTemplate('template')
                ->setIntro($values['survey_introduction'])
                ->setSurveyThanks($values['survey_thanks'])
                ->setAnonymous($values['anonymous'])
                ->setSessionId(api_get_session_id())
                ->setVisibleResults($values['visible_results'])
            ;

            $em = Database::getManager();
            $em->persist($survey);
            $em->flush();

            $survey_id = $survey->getIid();
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
                self::copy_survey($values['parent_id'], $survey_id);
            }

            Display::addFlash(
                Display::return_message(
                    get_lang('The survey has been created succesfully'),
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
                        get_lang('This survey code soon exists in this language'),
                        'error'
                    )
                );
                $return['type'] = 'error';
                $return['id'] = isset($values['survey_id']) ? $values['survey_id'] : 0;

                return $return;
            }

            if (!isset($values['anonymous'])
                || (isset($values['anonymous']) && $values['anonymous'] == '')
            ) {
                $values['anonymous'] = 0;
            }

            $extraParams = [];
            $extraParams['one_question_per_page'] = isset($values['one_question_per_page']) ? $values['one_question_per_page'] : 0;
            $extraParams['shuffle'] = isset($values['shuffle']) ? $values['shuffle'] : 0;

            if ($values['anonymous'] == 0) {
                $extraParams['show_form_profile'] = isset($values['show_form_profile']) ? $values['show_form_profile'] : 0;
                if ($extraParams['show_form_profile'] == 1) {
                    $fields = explode(',', $values['input_name_list']);
                    $field_values = '';
                    foreach ($fields as &$field) {
                        if ($field != '') {
                            if (!isset($values[$field]) ||
                                (isset($values[$field]) && $values[$field] == '')
                            ) {
                                $values[$field] = 0;
                            }
                            $field_values .= $field.':'.$values[$field].'@';
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
                'avail_from' => $allowSurveyAvailabilityDatetime
                    ? api_get_utc_datetime($values['start_date'].':00')
                    : $values['start_date'],
                'avail_till' => $allowSurveyAvailabilityDatetime
                    ? api_get_utc_datetime($values['end_date'].':59')
                    : $values['end_date'],
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
                    get_lang('The survey has been updated succesfully'),
                    'confirmation'
                )
            );

            $return['id'] = $values['survey_id'];
        }

        $survey_id = (int) $return['id'];

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
                    GradebookUtils::updateResourceFromCourseGradebook(
                        $gradebook_link_id,
                        $courseCode,
                        $survey_weight
                    );
                }
            }
        } else {
            // Delete everything of the gradebook for this $linkId
            GradebookUtils::remove_resource_from_course_gradebook($gradebook_link_id);
        }

        return $return;
    }

    /**
     * This function stores a shared survey in the central database.
     *
     * @param array $values
     *
     * @return array $return the type of return message that has to be displayed and the message in it
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version February 2007
     */
    public function store_shared_survey($values)
    {
        $_user = api_get_user_info();
        $_course = api_get_course_info();

        // Table definitions
        $table_survey = Database::get_main_table(TABLE_MAIN_SHARED_SURVEY);

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
            $return = Database::insert_id();

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
            $return = $values['survey_share']['survey_share'];
        }

        return $return;
    }

    /**
     * This function deletes a survey (and also all the question in that survey.
     *
     * @param int  $survey_id id of the survey that has to be deleted
     * @param bool $shared
     * @param int  $course_id
     *
     * @return true
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version January 2007
     */
    public static function delete_survey($survey_id, $shared = false, $course_id = 0)
    {
        // Database table definitions
        if (empty($course_id)) {
            $course_id = api_get_course_int_id();
        }

        $survey_id = (int) $survey_id;

        if (empty($survey_id)) {
            return false;
        }

        $course_info = api_get_course_info_by_id($course_id);
        $course_id = $course_info['real_id'];

        $table_survey = Database::get_course_table(TABLE_SURVEY);
        $table_survey_question_group = Database::get_course_table(TABLE_SURVEY_QUESTION_GROUP);

        if ($shared) {
            $table_survey = Database::get_main_table(TABLE_MAIN_SHARED_SURVEY);
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
        self::delete_all_survey_questions($survey_id, $shared);

        // Update into item_property (delete)
        api_item_property_update(
            $course_info,
            TOOL_SURVEY,
            $survey_id,
            'SurveyDeleted',
            api_get_user_id()
        );

        Skill::deleteSkillsFromItem($survey_id, ITEM_TYPE_SURVEY);

        return true;
    }

    /**
     * @param int $survey_id
     * @param int $new_survey_id
     * @param int $targetCourseId
     *
     * @return bool
     */
    public static function copy_survey(
        $survey_id,
        $new_survey_id = null,
        $targetCourseId = null
    ) {
        $course_id = api_get_course_int_id();
        if (!$targetCourseId) {
            $targetCourseId = $course_id;
        }

        // Database table definitions
        $table_survey = Database::get_course_table(TABLE_SURVEY);
        $table_survey_question_group = Database::get_course_table(TABLE_SURVEY_QUESTION_GROUP);
        $table_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $table_survey_options = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
        $survey_id = (int) $survey_id;

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
            $params['title'] = $params['title'].' '.get_lang('Copy');
            unset($params['iid']);
            $new_survey_id = Database::insert($table_survey, $params);

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
            $new_survey_id = (int) $new_survey_id;
        }

        $sql = "SELECT * FROM $table_survey_question_group
                WHERE c_id = $course_id AND survey_id='".$survey_id."'";
        $res = Database::query($sql);
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $params = [
                'c_id' => $targetCourseId,
                'name' => $row['name'],
                'description' => $row['description'],
                'survey_id' => $new_survey_id,
            ];
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
            $params = [
                'c_id' => $targetCourseId,
                'survey_id' => $new_survey_id,
                'survey_question' => $row['survey_question'],
                'survey_question_comment' => $row['survey_question_comment'],
                'type' => $row['type'],
                'display' => $row['display'],
                'sort' => $row['sort'],
                'shared_question_id' => $row['shared_question_id'],
                'max_value' => $row['max_value'],
                'survey_group_pri' => $row['survey_group_pri'],
                'survey_group_sec1' => $row['survey_group_sec1'],
                'survey_group_sec2' => $row['survey_group_sec2'],
            ];

            if (api_get_configuration_value('allow_required_survey_questions')) {
                if (isset($row['is_required'])) {
                    $params['is_required'] = $row['is_required'];
                }
            }

            $insertId = Database::insert($table_survey_question, $params);
            if ($insertId) {
                $sql = "UPDATE $table_survey_question SET question_id = iid WHERE iid = $insertId";
                Database::query($sql);
                $question_id[$row['question_id']] = $insertId;
            }
        }

        // Get questions options
        $sql = "SELECT * FROM $table_survey_options
                WHERE c_id = $course_id AND survey_id='".$survey_id."'";

        $res = Database::query($sql);
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $params = [
                'c_id' => $targetCourseId,
                'question_id' => $question_id[$row['question_id']],
                'survey_id' => $new_survey_id,
                'option_text' => $row['option_text'],
                'sort' => $row['sort'],
                'value' => $row['value'],
            ];
            $insertId = Database::insert($table_survey_options, $params);
            if ($insertId) {
                $sql = "UPDATE $table_survey_options SET question_option_id = $insertId
                        WHERE iid = $insertId";
                Database::query($sql);
            }
        }

        return $new_survey_id;
    }

    /**
     * This function duplicates a survey (and also all the question in that survey.
     *
     * @param int $surveyId id of the survey that has to be duplicated
     * @param int $courseId id of the course which survey has to be duplicated
     *
     * @return true
     *
     * @author Eric Marguin <e.marguin@elixir-interactive.com>, Elixir Interactive
     *
     * @version October 2007
     */
    public static function empty_survey($surveyId, $courseId = 0)
    {
        // Database table definitions
        $table_survey_invitation = Database::get_course_table(TABLE_SURVEY_INVITATION);
        $table_survey_answer = Database::get_course_table(TABLE_SURVEY_ANSWER);
        $table_survey = Database::get_course_table(TABLE_SURVEY);

        $courseId = (int) $courseId;
        $courseId = empty($courseId) ? api_get_course_int_id() : $courseId;
        $surveyId = (int) $surveyId;

        $datas = self::get_survey($surveyId);
        $session_where = '';
        if (api_get_session_id() != 0) {
            $session_where = ' AND session_id = "'.api_get_session_id().'" ';
        }

        $sql = 'DELETE FROM '.$table_survey_invitation.'
		        WHERE
		            c_id = '.$courseId.' AND
		            survey_code = "'.Database::escape_string($datas['code']).'" '.$session_where.' ';
        Database::query($sql);

        $sql = 'DELETE FROM '.$table_survey_answer.'
		        WHERE c_id = '.$courseId.' AND survey_id='.$surveyId;
        Database::query($sql);

        $sql = 'UPDATE '.$table_survey.' SET invited=0, answered=0
		        WHERE c_id = '.$courseId.' AND survey_id='.$surveyId;
        Database::query($sql);

        return true;
    }

    /**
     * This function recalculates the number of people who have taken the survey (=filled at least one question).
     *
     * @param array  $survey_data
     * @param array  $user
     * @param string $survey_code
     *
     * @return bool
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version February 2007
     */
    public static function update_survey_answered($survey_data, $user, $survey_code)
    {
        if (empty($survey_data)) {
            return false;
        }

        // Database table definitions
        $table_survey = Database::get_course_table(TABLE_SURVEY);
        $table_survey_invitation = Database::get_course_table(TABLE_SURVEY_INVITATION);

        $survey_id = (int) $survey_data['survey_id'];
        $course_id = (int) $survey_data['c_id'];
        $session_id = $survey_data['session_id'];

        // Getting a list with all the people who have filled the survey
        /*$people_filled = self::get_people_who_filled_survey($survey_id, false, $course_id);
        $number = count($people_filled);*/

        // Storing this value in the survey table
        $sql = "UPDATE $table_survey
		        SET answered = answered + 1
		        WHERE
                    c_id = $course_id AND
		            survey_id = ".$survey_id;
        Database::query($sql);

        $allow = api_get_configuration_value('survey_answered_at_field');
        // Requires DB change:
        // ALTER TABLE c_survey_invitation ADD answered_at DATETIME DEFAULT NULL;
        $answeredAt = '';
        if ($allow) {
            $answeredAt = "answered_at = '".api_get_utc_datetime()."',";
        }

        // Storing that the user has finished the survey.
        $sql = "UPDATE $table_survey_invitation
                SET $answeredAt answered = 1
                WHERE
                    c_id = $course_id AND
                    session_id = $session_id AND
                    user ='".Database::escape_string($user)."' AND
                    survey_code='".Database::escape_string($survey_code)."'";
        Database::query($sql);
    }

    /**
     * This function return the "icon" of the question type.
     *
     * @param string $type
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version February 2007
     */
    public static function icon_question($type)
    {
        // the possible question types
        $possible_types = [
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
        ];

        // the images array
        $icon_question = [
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
        ];

        if (in_array($type, $possible_types)) {
            return $icon_question[$type];
        }

        return false;
    }

    /**
     * This function retrieves all the information of a question.
     *
     * @param int  $question_id the id of the question
     * @param bool $shared
     *
     * @return array
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version January 2007
     *
     * @todo one sql call should do the trick
     */
    public static function get_question($question_id, $shared = false)
    {
        // Table definitions
        $tbl_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
        $course_id = api_get_course_int_id();
        $question_id = (int) $question_id;

        $sql = "SELECT * FROM $tbl_survey_question
                WHERE c_id = $course_id AND question_id='".$question_id."'
                ORDER BY `sort` ";

        $sqlOption = "  SELECT * FROM $table_survey_question_option
                        WHERE c_id = $course_id AND question_id='".$question_id."'
                        ORDER BY `sort` ";

        if ($shared) {
            $tbl_survey_question = Database::get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION);
            $table_survey_question_option = Database::get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION_OPTION);

            $sql = "SELECT * FROM $tbl_survey_question
                    WHERE question_id='".$question_id."'
                    ORDER BY `sort` ";
            $sqlOption = "SELECT * FROM $table_survey_question_option
                          WHERE question_id='".$question_id."'
                          ORDER BY `sort` ";
        }

        // Getting the information of the question

        $result = Database::query($sql);
        $row = Database::fetch_array($result, 'ASSOC');

        $return['survey_id'] = $row['survey_id'];
        $return['question_id'] = $row['question_id'];
        $return['type'] = $row['type'];
        $return['question'] = $row['survey_question'];
        $return['horizontalvertical'] = $row['display'];
        $return['shared_question_id'] = $row['shared_question_id'];
        $return['maximum_score'] = $row['max_value'];
        $return['is_required'] = api_get_configuration_value('allow_required_survey_questions')
            ? $row['is_required']
            : false;

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
     * This function gets all the question of any given survey.
     *
     * @param int $surveyId the id of the survey
     * @param int $courseId
     *
     * @return array containing all the questions of the survey
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version February 2007
     *
     * @todo one sql call should do the trick
     */
    public static function get_questions($surveyId, $courseId = 0)
    {
        $tbl_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);

        $courseId = (int) $courseId;
        $surveyId = (int) $surveyId;

        if (empty($courseId)) {
            $courseId = api_get_course_int_id();
        }

        // Getting the information of the question
        $sql = "SELECT * FROM $tbl_survey_question
		        WHERE c_id = $courseId AND survey_id='".$surveyId."'";
        $result = Database::query($sql);
        $return = [];
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $return[$row['question_id']]['survey_id'] = $row['survey_id'];
            $return[$row['question_id']]['question_id'] = $row['question_id'];
            $return[$row['question_id']]['type'] = $row['type'];
            $return[$row['question_id']]['question'] = $row['survey_question'];
            $return[$row['question_id']]['horizontalvertical'] = $row['display'];
            $return[$row['question_id']]['maximum_score'] = $row['max_value'];
            $return[$row['question_id']]['sort'] = $row['sort'];
            $return[$row['question_id']]['survey_question_comment'] = $row['survey_question_comment'];
        }

        // Getting the information of the question options
        $sql = "SELECT * FROM $table_survey_question_option
		        WHERE c_id = $courseId AND survey_id='".$surveyId."'";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $return[$row['question_id']]['answers'][] = $row['option_text'];
        }

        return $return;
    }

    /**
     * This function saves a question in the database.
     * This can be either an update of an existing survey or storing a new survey.
     *
     * @param array $survey_data
     * @param array $form_content all the information of the form
     *
     * @return string
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
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
            $course_id = api_get_course_int_id();
            if (!$empty_answer) {
                // Table definitions
                $tbl_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);

                // Getting all the information of the survey
                $survey_data = self::get_survey($form_content['survey_id']);

                // Storing the question in the shared database
                if (is_numeric($survey_data['survey_share']) && $survey_data['survey_share'] != 0) {
                    $shared_question_id = self::save_shared_question($form_content, $survey_data);
                    $form_content['shared_question_id'] = $shared_question_id;
                }

                // Storing a new question
                if ($form_content['question_id'] == '' || !is_numeric($form_content['question_id'])) {
                    // Finding the max sort order of the questions in the given survey
                    $sql = "SELECT max(sort) AS max_sort
					        FROM $tbl_survey_question
                            WHERE c_id = $course_id AND survey_id='".intval($form_content['survey_id'])."'";
                    $result = Database::query($sql);
                    $row = Database::fetch_array($result, 'ASSOC');
                    $max_sort = $row['max_sort'];

                    $question = new CSurveyQuestion();

                    // Some variables defined for survey-test type
                    $extraParams = [];
                    if (isset($_POST['choose'])) {
                        if ($_POST['choose'] == 1) {
                            $question->setSurveyGroupPri($_POST['assigned']);
                        } elseif ($_POST['choose'] == 2) {
                            $question->setSurveyGroupSec1($_POST['assigned1']);
                            $question->setSurveyGroupSec2($_POST['assigned2']);
                        }
                    }

                    $question
                        ->setSurveyQuestionComment($form_content['question_comment'] ?? '')
                        ->setMaxValue($form_content['maximum_score'] ?? 0)
                        ->setDisplay($form_content['horizontalvertical'] ?? '')
                        ->setCId($course_id)
                        ->setSurveyId($form_content['survey_id'])
                        ->setSurveyQuestion($form_content['question'])
                        ->setType($form_content['type'])
                        ->setSort($max_sort + 1)
                        ->setSharedQuestionId($form_content['shared_question_id'])
                    ;

                    if (api_get_configuration_value('allow_required_survey_questions')) {
                        $question->setIsMandatory(isset($form_content['is_required']));
                    }

                    $em = Database::getManager();
                    $em->persist($question);
                    $em->flush();

                    $question_id = $question->getIid();
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
                    $questionComment = isset($form_content['question_comment'])
                        ? $form_content['question_comment']
                        : null;

                    // Adding the question to the survey_question table
                    $params = [
                        'survey_question' => $form_content['question'],
                        'survey_question_comment' => $questionComment,
                        'display' => $form_content['horizontalvertical'],
                    ];

                    if (api_get_configuration_value('allow_required_survey_questions')) {
                        $params['is_required'] = isset($form_content['is_required']);
                    }

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
                self::save_question_options($form_content, $survey_data);
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
     * This function saves the question in the shared database.
     *
     * @param array $form_content all the information of the form
     * @param array $survey_data  all the information of the survey
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version February 2007
     *
     * @return int
     *
     * @todo editing of a shared question
     */
    public function save_shared_question($form_content, $survey_data)
    {
        $_course = api_get_course_info();

        // Table definitions
        $tbl_survey_question = Database::get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION);

        // Storing a new question
        if ($form_content['shared_question_id'] == '' ||
            !is_numeric($form_content['shared_question_id'])
        ) {
            // Finding the max sort order of the questions in the given survey
            $sql = "SELECT max(sort) AS max_sort FROM $tbl_survey_question
                    WHERE survey_id='".intval($survey_data['survey_share'])."'
                    AND code='".Database::escape_string($_course['id'])."'";
            $result = Database::query($sql);
            $row = Database::fetch_array($result, 'ASSOC');
            $max_sort = $row['max_sort'];

            // Adding the question to the survey_question table
            $sql = "INSERT INTO $tbl_survey_question (survey_id, survey_question, survey_question_comment, type, display, sort, code) VALUES (
                    '".Database::escape_string($survey_data['survey_share'])."',
                    '".Database::escape_string($form_content['question'])."',
                    '".Database::escape_string($form_content['question_comment'])."',
                    '".Database::escape_string($form_content['type'])."',
                    '".Database::escape_string($form_content['horizontalvertical'])."',
                    '".Database::escape_string($max_sort + 1)."',
                    '".Database::escape_string($_course['id'])."')";
            Database::query($sql);
            $shared_question_id = Database::insert_id();
        } else {
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
     * This functions moves a question of a survey up or down.
     *
     * @param string $direction
     * @param int    $survey_question_id
     * @param int    $survey_id
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version January 2007
     */
    public static function move_survey_question(
        $direction,
        $survey_question_id,
        $survey_id
    ) {
        // Table definition
        $table_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $course_id = api_get_course_int_id();

        if ($direction == 'moveup') {
            $sort = 'DESC';
        }
        if ($direction == 'movedown') {
            $sort = 'ASC';
        }

        $survey_id = (int) $survey_id;

        // Finding the two questions that needs to be swapped
        $sql = "SELECT * FROM $table_survey_question
		        WHERE c_id = $course_id AND survey_id='".$survey_id."'
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

        $sql = "UPDATE $table_survey_question 
                SET sort = '".Database::escape_string($question_sort_two)."'
		        WHERE c_id = $course_id AND question_id='".intval($question_id_one)."'";
        Database::query($sql);

        $sql = "UPDATE $table_survey_question 
                SET sort = '".Database::escape_string($question_sort_one)."'
		        WHERE c_id = $course_id AND question_id='".intval($question_id_two)."'";
        Database::query($sql);
    }

    /**
     * This function deletes all the questions of a given survey
     * This function is normally only called when a survey is deleted.
     *
     * @param int $survey_id the id of the survey that has to be deleted
     *
     * @return bool
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version January 2007
     */
    public static function delete_all_survey_questions($survey_id, $shared = false)
    {
        $course_id = api_get_course_int_id();
        $survey_id = (int) $survey_id;

        // Table definitions
        $table_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $course_condition = " c_id = $course_id AND ";
        if ($shared) {
            $course_condition = '';
            $table_survey_question = Database::get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION);
        }

        $sql = "DELETE FROM $table_survey_question
		        WHERE $course_condition survey_id = '".$survey_id."'";

        // Deleting the survey questions
        Database::query($sql);

        // Deleting all the options of the questions of the survey
        self::delete_all_survey_questions_options($survey_id, $shared);

        // Deleting all the answers on this survey
        self::delete_all_survey_answers($survey_id);

        return true;
    }

    /**
     * This function deletes a survey question and all its options.
     *
     * @param int  $survey_id   the id of the survey
     * @param int  $question_id the id of the question
     * @param bool $shared
     *
     * @todo also delete the answers to this question
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version March 2007
     */
    public static function delete_survey_question($survey_id, $question_id, $shared = false)
    {
        $survey_id = (int) $survey_id;
        $question_id = (int) $question_id;
        $course_id = api_get_course_int_id();

        if ($shared) {
            self::delete_shared_survey_question($survey_id, $question_id);
        }

        // Table definitions
        $table = Database::get_course_table(TABLE_SURVEY_QUESTION);
        // Deleting the survey questions
        $sql = "DELETE FROM $table
		        WHERE
		            c_id = $course_id AND
		            survey_id='".$survey_id."' AND
		            question_id='".$question_id."'";
        Database::query($sql);

        // Deleting the options of the question of the survey
        self::delete_survey_question_option($survey_id, $question_id, $shared);
    }

    /**
     * This function deletes a shared survey question from the main database and all its options.
     *
     * @param int $question_id the id of the question
     *
     * @todo delete all the options of this question
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version March 2007
     */
    public static function delete_shared_survey_question($survey_id, $question_id)
    {
        // Table definitions
        $table_survey_question = Database::get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION);
        $table_survey_question_option = Database::get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION_OPTION);

        // First we have to get the shared_question_id
        $question_data = self::get_question($question_id);

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
     * This function stores the options of the questions in the table.
     *
     * @param array $form_content
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version January 2007
     *
     * @todo writing the update statement when editing a question
     */
    public static function save_question_options($form_content, $survey_data)
    {
        $course_id = api_get_course_int_id();
        // A percentage question type has options 1 -> 100
        if ($form_content['type'] === 'percentage') {
            for ($i = 1; $i < 101; $i++) {
                $form_content['answers'][] = $i;
            }
        }

        if (is_numeric($survey_data['survey_share']) && $survey_data['survey_share'] != 0) {
            self::save_shared_question_options($form_content, $survey_data);
        }

        // Table definition
        $table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);

        // We are editing a question so we first have to remove all the existing options from the database
        if (is_numeric($form_content['question_id'])) {
            $sql = "DELETE FROM $table_survey_question_option
			        WHERE c_id = $course_id AND question_id = '".intval($form_content['question_id'])."'";
            Database::query($sql);
        }

        $counter = 1;
        $em = Database::getManager();
        if (isset($form_content['answers']) && is_array($form_content['answers'])) {
            for ($i = 0; $i < count($form_content['answers']); $i++) {
                $values = isset($form_content['values']) ? $form_content['values'][$i] : 0;
                $option = new CSurveyQuestionOption();
                $option
                    ->setCId($course_id)
                    ->setQuestionId($form_content['question_id'])
                    ->setSurveyId($form_content['survey_id'])
                    ->setOptionText($form_content['answers'][$i])
                    ->setValue($values)
                    ->setSort($counter)
                ;

                $em->persist($option);
                $em->flush();

                $insertId = $option->getIid();
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
     * This function stores the options of the questions in the shared table.
     *
     * @param array $form_content
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version February 2007
     *
     * @todo writing the update statement when editing a question
     */
    public function save_shared_question_options($form_content, $survey_data)
    {
        if (is_array($form_content) && is_array($form_content['answers'])) {
            // Table definition
            $table = Database::get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION_OPTION);

            // We are editing a question so we first have to remove all the existing options from the database
            $sql = "DELETE FROM $table
                    WHERE question_id = '".Database::escape_string($form_content['shared_question_id'])."'";
            Database::query($sql);

            $counter = 1;
            foreach ($form_content['answers'] as &$answer) {
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
     * This function is normally only called when a survey is deleted.
     *
     * @param int $survey_id the id of the survey that has to be deleted
     *
     * @return true
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version January 2007
     */
    public static function delete_all_survey_questions_options($survey_id, $shared = false)
    {
        // Table definitions
        $table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
        $course_id = api_get_course_int_id();
        $course_condition = " c_id = $course_id AND ";
        if ($shared) {
            $course_condition = '';
            $table_survey_question_option = Database::get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION_OPTION);
        }

        $sql = "DELETE FROM $table_survey_question_option
                WHERE $course_condition survey_id='".intval($survey_id)."'";

        // Deleting the options of the survey questions
        Database::query($sql);

        return true;
    }

    /**
     * This function deletes the options of a given question.
     *
     * @param int  $survey_id
     * @param int  $question_id
     * @param bool $shared
     *
     * @return bool
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @author Julio Montoya
     *
     * @version March 2007
     */
    public static function delete_survey_question_option(
        $survey_id,
        $question_id,
        $shared = false
    ) {
        $course_id = api_get_course_int_id();
        $course_condition = " c_id = $course_id AND ";

        // Table definitions
        $table = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
        if ($shared) {
            $course_condition = '';
            $table = Database::get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION_OPTION);
        }

        // Deleting the options of the survey questions
        $sql = "DELETE FROM $table
		        WHERE
		            $course_condition survey_id='".intval($survey_id)."' AND
		            question_id='".intval($question_id)."'";
        Database::query($sql);

        return true;
    }

    /**
     * SURVEY ANSWERS FUNCTIONS.
     */

    /**
     * This function deletes all the answers anyone has given on this survey
     * This function is normally only called when a survey is deleted.
     *
     * @param $survey_id the id of the survey that has to be deleted
     *
     * @return true
     *
     * @todo write the function
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version January 2007,december 2008
     */
    public static function delete_all_survey_answers($survey_id)
    {
        $course_id = api_get_course_int_id();
        $table = Database::get_course_table(TABLE_SURVEY_ANSWER);
        $survey_id = (int) $survey_id;
        $sql = "DELETE FROM $table 
                WHERE c_id = $course_id AND survey_id = $survey_id";
        Database::query($sql);

        return true;
    }

    /**
     * @param int $user_id
     * @param int $survey_id
     * @param int $course_id
     *
     * @return bool
     */
    public static function is_user_filled_survey($user_id, $survey_id, $course_id)
    {
        $table = Database::get_course_table(TABLE_SURVEY_ANSWER);
        $user_id = (int) $user_id;
        $course_id = (int) $course_id;
        $survey_id = (int) $survey_id;

        $sql = "SELECT DISTINCT user 
                FROM $table
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
     * This function gets all the persons who have filled the survey.
     *
     * @param int $survey_id
     *
     * @return array
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version February 2007
     */
    public static function get_people_who_filled_survey(
        $survey_id,
        $all_user_info = false,
        $course_id = null
    ) {
        // Database table definition
        $table_survey_answer = Database::get_course_table(TABLE_SURVEY_ANSWER);
        $table_user = Database::get_main_table(TABLE_MAIN_USER);

        // Variable initialisation
        $return = [];

        if (empty($course_id)) {
            $course_id = api_get_course_int_id();
        } else {
            $course_id = (int) $course_id;
        }

        $survey_id = (int) $survey_id;

        if ($all_user_info) {
            $order_clause = api_sort_by_first_name()
                ? ' ORDER BY user.firstname, user.lastname'
                : ' ORDER BY user.lastname, user.firstname';
            $sql = "SELECT DISTINCT
			            answered_user.user as invited_user, 
			            user.firstname, 
			            user.lastname, 
			            user.user_id
                    FROM $table_survey_answer answered_user
                    LEFT JOIN $table_user as user ON answered_user.user = user.user_id
                    WHERE
                        answered_user.c_id = $course_id AND
                        survey_id= '".$survey_id."' ".
                $order_clause;
        } else {
            $sql = "SELECT DISTINCT user FROM $table_survey_answer
			        WHERE c_id = $course_id AND survey_id= '".$survey_id."'  ";

            if (api_get_configuration_value('survey_anonymous_show_answered')) {
                $tblInvitation = Database::get_course_table(TABLE_SURVEY_INVITATION);
                $tblSurvey = Database::get_course_table(TABLE_SURVEY);

                $sql = "SELECT i.user FROM $tblInvitation i
                    INNER JOIN $tblSurvey s 
                    ON i.survey_code = s.code
                        AND i.c_id = s.c_id
                        AND i.session_id = s.session_id
                    WHERE i.answered IS TRUE AND s.iid = $survey_id";
            }
        }

        $res = Database::query($sql);
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            if ($all_user_info) {
                $userInfo = api_get_user_info($row['user_id']);
                $row['user_info'] = $userInfo;
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
     *
     * @return string
     */
    public static function generate_survey_hash($survey_id, $course_id, $session_id, $group_id)
    {
        $hash = hash('sha512', api_get_security_key().'_'.$course_id.'_'.$session_id.'_'.$group_id.'_'.$survey_id);

        return $hash;
    }

    /**
     * @param int    $survey_id
     * @param int    $course_id
     * @param int    $session_id
     * @param int    $group_id
     * @param string $hash
     *
     * @return bool
     */
    public static function validate_survey_hash($survey_id, $course_id, $session_id, $group_id, $hash)
    {
        $generatedHash = self::generate_survey_hash($survey_id, $course_id, $session_id, $group_id);
        if ($generatedHash == $hash) {
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
    public static function generate_survey_link(
        $survey_id,
        $course_id,
        $session_id,
        $group_id
    ) {
        $code = self::generate_survey_hash(
            $survey_id,
            $course_id,
            $session_id,
            $group_id
        );

        return api_get_path(WEB_CODE_PATH).'survey/link.php?h='.$code.'&i='.$survey_id.'&c='.intval($course_id).'&s='
            .intval($session_id).'&g='.$group_id;
    }

    /**
     * Check if the current user has mandatory surveys no-answered
     * and redirect to fill the first found survey.
     */
    public static function protectByMandatory()
    {
        if (strpos($_SERVER['SCRIPT_NAME'], 'fillsurvey.php') !== false) {
            return;
        }

        $userId = api_get_user_id();
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();

        if (!$userId) {
            return;
        }

        if (!$courseId) {
            return;
        }

        try {
            /** @var CSurveyInvitation $invitation */
            $invitation = Database::getManager()
                ->createQuery("
                    SELECT i FROM ChamiloCourseBundle:CSurveyInvitation i
                    INNER JOIN ChamiloCourseBundle:CSurvey s
                        WITH (s.code = i.surveyCode AND s.cId = i.cId AND s.sessionId = i.sessionId)
                    INNER JOIN ChamiloCoreBundle:ExtraFieldValues efv WITH efv.itemId = s.iid
                    INNER JOIN ChamiloCoreBundle:ExtraField ef WITH efv.field = ef.id
                    WHERE 
                        i.answered = 0 AND 
                        i.cId = :course AND 
                        i.user = :user AND 
                        i.sessionId = :session AND 
                        :now BETWEEN s.availFrom AND s.availTill AND 
                        ef.variable = :variable AND 
                        efv.value = 1 AND 
                        s.surveyType != 3
                    ORDER BY s.availTill ASC
                ")
                ->setMaxResults(1)
                ->setParameters([
                    'course' => $courseId,
                    'user' => $userId,
                    'session' => $sessionId,
                    'now' => new DateTime('UTC', new DateTimeZone('UTC')),
                    'variable' => 'is_mandatory',
                ])
                ->getSingleResult();
        } catch (Exception $e) {
            $invitation = null;
        }

        if (!$invitation) {
            return;
        }

        Display::addFlash(
            Display::return_message(get_lang('A mandatory survey is waiting your answer. To enter the course, you must first complete the survey.'), 'warning')
        );

        $url = SurveyUtil::generateFillSurveyLink(
            $invitation->getInvitationCode(),
            api_get_course_info(),
            api_get_session_id()
        );

        header('Location: '.$url);
        exit;
    }

    /**
     * This function empty surveys (invitations and answers).
     *
     * @param int $surveyId id of the survey to empty
     *
     * @return bool
     */
    public static function emptySurveyFromId($surveyId)
    {
        // Database table definitions
        $surveyInvitationTable = Database:: get_course_table(TABLE_SURVEY_INVITATION);
        $surveyAnswerTable = Database:: get_course_table(TABLE_SURVEY_ANSWER);
        $surveyTable = Database:: get_course_table(TABLE_SURVEY);
        $surveyId = (int) $surveyId;
        $surveyData = self::get_survey($surveyId);
        if (empty($surveyData)) {
            return false;
        }

        $surveyCode = $surveyData['survey_code'];
        $courseId = (int) $surveyData['c_id'];
        $sessionId = (int) $surveyData['session_id'];

        $sql = "DELETE FROM $surveyInvitationTable 
                WHERE session_id = $sessionId AND c_id = $courseId AND survey_code = '$surveyCode' ";
        Database::query($sql);

        $sql = "DELETE FROM $surveyAnswerTable 
               WHERE survey_id = $surveyId AND c_id = $courseId ";
        Database::query($sql);

        $sql = "UPDATE $surveyTable 
                SET invited = 0, answered = 0 
                WHERE survey_id = $surveyId AND c_id = $courseId AND session_id = $sessionId ";
        Database::query($sql);

        return true;
    }

    /**
     * This function copy survey specifying course id and session id where will be copied.
     *
     * @param int $surveyId
     * @param int $targetCourseId  target course id
     * @param int $targetSessionId target session id
     *
     * @return bool|int when fails or return the new survey id
     */
    public static function copySurveySession($surveyId, $targetCourseId, $targetSessionId)
    {
        // Database table definitions
        $surveyTable = Database::get_course_table(TABLE_SURVEY);
        $surveyQuestionGroupTable = Database::get_course_table(TABLE_SURVEY_QUESTION_GROUP);
        $surveyQuestionTable = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $surveyOptionsTable = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
        $surveyId = (int) $surveyId;
        $targetCourseId = (int) $targetCourseId;
        $targetSessionId = (int) $targetSessionId;

        $surveyData = self::get_survey($surveyId, 0, '', true);
        if (empty($surveyData) || empty($targetCourseId)) {
            return false;
        }

        $originalCourseId = $surveyData['c_id'];
        $originalSessionId = $surveyData['session_id'];

        $surveyData['code'] = self::generate_unique_code($surveyData['code']);
        $surveyData['c_id'] = $targetCourseId;
        $surveyData['session_id'] = $targetSessionId;
        // Add a "Copy" suffix if copied inside the same course
        if ($targetCourseId == $originalCourseId) {
            $surveyData['title'] = $surveyData['title'].' '.get_lang('Copy');
        }
        unset($surveyData['iid']);
        unset($surveyData['id']);

        $newSurveyId = Database::insert($surveyTable, $surveyData);

        if ($newSurveyId) {
            $sql = "UPDATE $surveyTable SET survey_id = $newSurveyId 
                    WHERE iid = $newSurveyId";
            Database::query($sql);

            $sql = "SELECT * FROM $surveyQuestionGroupTable 
                    WHERE survey_id = $surveyId AND c_id = $originalCourseId ";
            $res = Database::query($sql);
            while ($row = Database::fetch_array($res, 'ASSOC')) {
                $params = [
                    'c_id' => $targetCourseId,
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'survey_id' => $newSurveyId,
                ];
                $insertId = Database::insert($surveyQuestionGroupTable, $params);
                if ($insertId) {
                    $sql = "UPDATE $surveyQuestionGroupTable SET id = iid WHERE iid = $insertId";
                    Database::query($sql);
                    $group_id[$row['id']] = $insertId;
                }
            }

            // Get questions
            $sql = "SELECT * FROM $surveyQuestionTable 
                    WHERE survey_id = $surveyId AND c_id = $originalCourseId";
            $res = Database::query($sql);
            while ($row = Database::fetch_array($res, 'ASSOC')) {
                $params = [
                    'c_id' => $targetCourseId,
                    'survey_id' => $newSurveyId,
                    'survey_question' => $row['survey_question'],
                    'survey_question_comment' => $row['survey_question_comment'],
                    'type' => $row['type'],
                    'display' => $row['display'],
                    'sort' => $row['sort'],
                    'shared_question_id' => $row['shared_question_id'],
                    'max_value' => $row['max_value'],
                    'survey_group_pri' => $row['survey_group_pri'],
                    'survey_group_sec1' => $row['survey_group_sec1'],
                    'survey_group_sec2' => $row['survey_group_sec2'],
                ];

                if (api_get_configuration_value('allow_required_survey_questions')) {
                    if (isset($row['is_required'])) {
                        $params['is_required'] = $row['is_required'];
                    }
                }

                $insertId = Database::insert($surveyQuestionTable, $params);
                if ($insertId) {
                    $sql = "UPDATE $surveyQuestionTable 
                            SET question_id = iid
                            WHERE iid = $insertId";
                    Database::query($sql);

                    $question_id[$row['question_id']] = $insertId;
                }
            }

            // Get questions options
            $sql = "SELECT * FROM $surveyOptionsTable 
                    WHERE survey_id = $surveyId AND c_id = $originalCourseId";

            $res = Database::query($sql);
            while ($row = Database::fetch_array($res, 'ASSOC')) {
                $params = [
                    'c_id' => $targetCourseId,
                    'question_id' => $question_id[$row['question_id']],
                    'survey_id' => $newSurveyId,
                    'option_text' => $row['option_text'],
                    'sort' => $row['sort'],
                    'value' => $row['value'],
                ];
                $insertId = Database::insert($surveyOptionsTable, $params);
                if ($insertId) {
                    $sql = "UPDATE $surveyOptionsTable SET question_option_id = $insertId WHERE iid = $insertId";
                    Database::query($sql);
                }
            }

            return $newSurveyId;
        }

        return false;
    }

    /**
     * @param array $surveyData
     *
     * @return bool
     */
    public static function removeMultiplicateQuestions($surveyData)
    {
        if (empty($surveyData)) {
            return false;
        }
        $surveyId = $surveyData['survey_id'];
        $courseId = $surveyData['c_id'];

        if (empty($surveyId) || empty($courseId)) {
            return false;
        }

        $questions = self::get_questions($surveyId);
        foreach ($questions as $question) {
            // Questions marked with "geneated" were created using the "multiplicate" feature.
            if ($question['survey_question_comment'] === 'generated') {
                self::delete_survey_question($surveyId, $question['question_id']);
            }
        }
    }

    /**
     * @param array $surveyData
     *
     * @return bool
     */
    public static function multiplicateQuestions($surveyData)
    {
        if (empty($surveyData)) {
            return false;
        }
        $surveyId = $surveyData['survey_id'];
        $courseId = $surveyData['c_id'];

        if (empty($surveyId) || empty($courseId)) {
            return false;
        }

        $questions = self::get_questions($surveyId);

        $obj = new UserGroup();

        $options['where'] = [' usergroup.course_id = ? ' => $courseId];
        $classList = $obj->getUserGroupInCourse($options);

        $classTag = '{{class_name}}';
        $studentTag = '{{student_full_name}}';
        $classCounter = 0;
        foreach ($classList as $class) {
            $className = $class['name'];
            foreach ($questions as $question) {
                $users = $obj->get_users_by_usergroup($class['id']);
                if (empty($users)) {
                    continue;
                }

                $text = $question['question'];
                if (strpos($text, $classTag) !== false) {
                    $replacedText = str_replace($classTag, $className, $text);
                    $values = [
                        'c_id' => $courseId,
                        'question_comment' => 'generated',
                        'type' => $question['type'],
                        'display' => $question['horizontalvertical'],
                        'question' => $replacedText,
                        'survey_id' => $surveyId,
                        'question_id' => 0,
                        'shared_question_id' => 0,
                    ];
                    self::save_question($surveyData, $values);
                    $classCounter++;
                    continue;
                }

                foreach ($users as $userId) {
                    $userInfo = api_get_user_info($userId);

                    if (strpos($text, $studentTag) !== false) {
                        $replacedText = str_replace($studentTag, $userInfo['complete_name'], $text);
                        $values = [
                            'c_id' => $courseId,
                            'question_comment' => 'generated',
                            'type' => $question['type'],
                            'display' => $question['horizontalvertical'],
                            'maximum_score' => $question['maximum_score'],
                            'question' => $replacedText,
                            'survey_id' => $surveyId,
                            'question_id' => 0,
                            'shared_question_id' => 0,
                        ];

                        $answers = [];
                        if (!empty($question['answers'])) {
                            foreach ($question['answers'] as $answer) {
                                $replacedText = str_replace($studentTag, $userInfo['complete_name'], $answer);
                                $answers[] = $replacedText;
                            }
                        }
                        $values['answers'] = $answers;
                        self::save_question($surveyData, $values);
                    }
                }

                if ($classCounter < count($classList)) {
                    // Add end page
                    $values = [
                        'c_id' => $courseId,
                        'question_comment' => 'generated',
                        'type' => 'pagebreak',
                        'display' => 'horizontal',
                        'question' => get_lang('Question for next class'),
                        'survey_id' => $surveyId,
                        'question_id' => 0,
                        'shared_question_id' => 0,
                    ];
                    self::save_question($surveyData, $values);
                }
            }
        }
    }

    /**
     * @param array $survey
     *
     * @return int
     */
    public static function getCountPages($survey)
    {
        if (empty($survey) || !isset($survey['iid'])) {
            return 0;
        }

        $courseId = $survey['c_id'];
        $surveyId = $survey['survey_id'];

        $table = Database::get_course_table(TABLE_SURVEY_QUESTION);

        // pagebreak
        $sql = "SELECT COUNT(iid) FROM $table
                WHERE
                    survey_question NOT LIKE '%{{%' AND
                    type = 'pagebreak' AND
                    c_id = $courseId AND
                    survey_id = $surveyId";
        $result = Database::query($sql);
        $numberPageBreaks = Database::result($result, 0, 0);

        // No pagebreak
        $sql = "SELECT COUNT(iid) FROM $table
                WHERE
                    survey_question NOT LIKE '%{{%' AND
                    type != 'pagebreak' AND
                    c_id = $courseId AND
                    survey_id = $surveyId";
        $result = Database::query($sql);
        $countOfQuestions = Database::result($result, 0, 0);

        if ($survey['one_question_per_page'] == 1) {
            if (!empty($countOfQuestions)) {
                return $countOfQuestions;
            }

            return 1;
        }

        if (empty($numberPageBreaks)) {
            return 1;
        }

        return $numberPageBreaks + 1;
    }

    /**
     * Check whether this survey has ended. If so, display message and exit rhis script.
     *
     * @param array $surveyData Survey data
     */
    public static function checkTimeAvailability($surveyData)
    {
        if (empty($surveyData)) {
            api_not_allowed(true);
        }

        $allowSurveyAvailabilityDatetime = api_get_configuration_value('allow_survey_availability_datetime');
        $utcZone = new DateTimeZone('UTC');
        $startDate = new DateTime($surveyData['start_date'], $utcZone);
        $endDate = new DateTime($surveyData['end_date'], $utcZone);
        $currentDate = new DateTime('now', $utcZone);
        if (!$allowSurveyAvailabilityDatetime) {
            $currentDate->modify('today');
        }
        if ($currentDate < $startDate) {
            api_not_allowed(
                true,
                Display:: return_message(
                    get_lang('This survey is not yet available. Please try again later. Thank you.'),
                    'warning',
                    false
                )
            );
        }

        if ($currentDate > $endDate) {
            api_not_allowed(
                true,
                Display:: return_message(
                    get_lang('Sorry, this survey is not available anymore. Thank you for trying.'),
                    'warning',
                    false
                )
            );
        }
    }

    /**
     * @param int    $userId
     * @param string $surveyCode
     * @param int    $courseId
     * @param int    $sessionId
     * @param int    $groupId
     *
     * @return array|CSurveyInvitation[]
     */
    public static function getUserInvitationsForSurveyInCourse(
        $userId,
        $surveyCode,
        $courseId,
        $sessionId = 0,
        $groupId = 0
    ) {
        $invitationRepo = Database::getManager()->getRepository('ChamiloCourseBundle:CSurveyInvitation');
        $invitations = $invitationRepo->findBy(
            [
                'user' => $userId,
                'cId' => $courseId,
                'sessionId' => $sessionId,
                'groupId' => $groupId,
                'surveyCode' => $surveyCode,
            ],
            ['invitationDate' => 'DESC']
        );

        return $invitations;
    }

    /**
     * @param array $userInfo
     * @param int   $answered (1 = answered 0 = not answered)
     *
     * @return string
     */
    public static function surveyReport($userInfo, $answered = 0)
    {
        $userId = isset($userInfo['user_id']) ? (int) $userInfo['user_id'] : 0;
        $answered = (int) $answered;

        if (empty($userId)) {
            return '';
        }

        $em = Database::getManager();
        $repo = $em->getRepository('ChamiloCourseBundle:CSurveyInvitation');
        $repoSurvey = $em->getRepository('ChamiloCourseBundle:CSurvey');
        $invitations = $repo->findBy(['user' => $userId, 'answered' => $answered]);
        $mainUrl = api_get_path(WEB_CODE_PATH).'survey/survey.php?';
        $content = '';

        if (empty($answered)) {
            $content .= Display::page_subheader(get_lang('Unanswered'));
        } else {
            $content .= Display::page_subheader(get_lang('Answered'));
        }

        if (!empty($invitations)) {
            $table = new HTML_Table(['class' => 'table']);
            $table->setHeaderContents(0, 0, get_lang('Survey name'));
            $table->setHeaderContents(0, 1, get_lang('Course'));

            if (empty($answered)) {
                $table->setHeaderContents(0, 2, get_lang('Survey').' - '.get_lang('End Date'));
            }

            // Not answered
            /** @var CSurveyInvitation $invitation */
            $row = 1;
            foreach ($invitations as $invitation) {
                $courseId = $invitation->getCId();
                $courseInfo = api_get_course_info_by_id($courseId);

                $courseCode = $courseInfo['code'];
                if (empty($courseInfo)) {
                    continue;
                }
                $sessionId = $invitation->getSessionId();

                if (!empty($answered)) {
                    // check if user is subscribed to the course/session
                    if (empty($sessionId)) {
                        $subscribe = CourseManager::is_user_subscribed_in_course($userId, $courseCode);
                    } else {
                        $subscribe = CourseManager::is_user_subscribed_in_course($userId, $courseCode, true, $sessionId);
                    }

                    // User is not subscribe skip!
                    if (empty($subscribe)) {
                        continue;
                    }
                }

                $surveyCode = $invitation->getSurveyCode();

                $survey = $repoSurvey->findOneBy([
                    'cId' => $courseId,
                    'sessionId' => $sessionId,
                    'code' => $surveyCode,
                ]);

                if (empty($survey)) {
                    continue;
                }

                $url = $mainUrl.'survey_id='.$survey->getSurveyId().'&cidReq='.$courseCode.'&id_session='.$sessionId;
                $title = $survey->getTitle();
                $title = Display::url($title, $url);

                if (!empty($sessionId)) {
                    $sessionInfo = api_get_session_info($sessionId);
                    $courseInfo['name'] .= ' ('.$sessionInfo['name'].')';
                }

                $surveyData = self::get_survey($survey->getSurveyId(), 0, $courseCode);
                $table->setCellContents($row, 0, $title);
                $table->setCellContents($row, 1, $courseInfo['name']);

                if (empty($answered)) {
                    $table->setHeaderContents(
                        $row,
                        2,
                        api_get_local_time(
                            $survey->getAvailTill(),
                            null,
                            null,
                            true,
                            false
                        )
                    );
                }

                if (!empty($answered) && $surveyData['anonymous'] == 0) {
                    $answers = SurveyUtil::displayCompleteReport(
                        $surveyData,
                        $userId,
                        false,
                        false,
                        false
                    );
                    $table->setCellContents(++$row, 0, $answers);
                    $table->setCellContents(++$row, 1, '');
                }

                $row++;
            }
            $content .= $table->toHtml();
        } else {
            $content .= Display::return_message(get_lang('No data available'));
        }

        return $content;
    }
}
