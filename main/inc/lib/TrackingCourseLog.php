<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField as EntityExtraField;
use ChamiloSession as Session;

class TrackingCourseLog
{
    /**
     * @return mixed
     */
    public static function count_item_resources()
    {
        $session_id = api_get_session_id();
        $course_id = api_get_course_int_id();

        $table_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $table_user = Database::get_main_table(TABLE_MAIN_USER);

        $sql = "SELECT count(tool) AS total_number_of_items
                FROM $table_item_property track_resource, $table_user user
                WHERE
                    track_resource.c_id = $course_id AND
                    track_resource.insert_user_id = user.user_id AND
                    session_id ".(empty($session_id) ? ' IS NULL ' : " = $session_id ");

        if (isset($_GET['keyword'])) {
            $keyword = Database::escape_string(trim($_GET['keyword']));
            $sql .= " AND (
                        user.username LIKE '%".$keyword."%' OR
                        lastedit_type LIKE '%".$keyword."%' OR
                        tool LIKE '%".$keyword."%'
                    )";
        }

        $sql .= " AND tool IN (
                    'document',
                    'learnpath',
                    'quiz',
                    'glossary',
                    'link',
                    'course_description',
                    'announcement',
                    'thematic',
                    'thematic_advance',
                    'thematic_plan'
                )";
        $res = Database::query($sql);
        $obj = Database::fetch_object($res);

        return $obj->total_number_of_items;
    }

    /**
     * @param $from
     * @param $number_of_items
     * @param $column
     * @param $direction
     *
     * @return array
     */
    public static function get_item_resources_data($from, $number_of_items, $column, $direction): array
    {
        $session_id = api_get_session_id();
        $course_id = api_get_course_int_id();

        $table_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $table_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $column = (int) $column;
        $direction = !in_array(strtolower(trim($direction)), ['asc', 'desc']) ? 'asc' : $direction;

        $sql = "SELECT
                    tool as col0,
                    lastedit_type as col1,
                    ref as ref,
                    user.username as col3,
                    insert_date as col6,
                    visibility as col7,
                    user.user_id as user_id
                FROM $table_item_property track_resource, $table_user user
                WHERE
                  track_resource.c_id = $course_id AND
                  track_resource.insert_user_id = user.user_id AND
                  session_id ".(empty($session_id) ? ' IS NULL ' : " = $session_id ");

        if (isset($_GET['keyword'])) {
            $keyword = Database::escape_string(trim($_GET['keyword']));
            $sql .= " AND (
                        user.username LIKE '%".$keyword."%' OR
                        lastedit_type LIKE '%".$keyword."%' OR
                        tool LIKE '%".$keyword."%'
                     ) ";
        }

        $sql .= " AND tool IN (
                    'document',
                    'learnpath',
                    'quiz',
                    'glossary',
                    'link',
                    'course_description',
                    'announcement',
                    'thematic',
                    'thematic_advance',
                    'thematic_plan'
                )";

        if ($column == 0) {
            $column = '0';
        }
        if ($column != '' && $direction != '') {
            if ($column != 2 && $column != 4) {
                $sql .= " ORDER BY col$column $direction";
            }
        } else {
            $sql .= " ORDER BY col6 DESC ";
        }

        $from = intval($from);
        if ($from) {
            $number_of_items = intval($number_of_items);
            $sql .= " LIMIT $from, $number_of_items ";
        }

        $res = Database::query($sql);
        $resources = [];
        $thematic_tools = ['thematic', 'thematic_advance', 'thematic_plan'];
        while ($row = Database::fetch_array($res)) {
            $ref = $row['ref'];
            $table_name = self::get_tool_name_table($row['col0']);
            $table_tool = Database::get_course_table($table_name['table_name']);

            $id = $table_name['id_tool'];
            $recorset = false;

            if (in_array($row['col0'], ['thematic_plan', 'thematic_advance'])) {
                $tbl_thematic = Database::get_course_table(TABLE_THEMATIC);
                $sql = "SELECT thematic_id FROM $table_tool
                        WHERE c_id = $course_id AND id = $ref";
                $rs_thematic = Database::query($sql);
                if (Database::num_rows($rs_thematic)) {
                    $row_thematic = Database::fetch_array($rs_thematic);
                    $thematic_id = $row_thematic['thematic_id'];

                    $sql = "SELECT session.id, session.name, user.username
                            FROM $tbl_thematic t, $table_session session, $table_user user
                            WHERE
                              t.c_id = $course_id AND
                              t.session_id = session.id AND
                              session.id_coach = user.user_id AND
                              t.id = $thematic_id";
                    $recorset = Database::query($sql);
                }
            } else {
                $sql = "SELECT session.id, session.name, user.username
                          FROM $table_tool tool, $table_session session, $table_user user
                          WHERE
                              tool.c_id = $course_id AND
                              tool.session_id = session.id AND
                              session.id_coach = user.user_id AND
                              tool.$id = $ref";
                $recorset = Database::query($sql);
            }

            if (!empty($recorset)) {
                $obj = Database::fetch_object($recorset);

                $name_session = '';
                $coach_name = '';
                if (!empty($obj)) {
                    $name_session = $obj->name;
                    $coach_name = $obj->username;
                }

                $url_tool = api_get_path(WEB_CODE_PATH).$table_name['link_tool'];
                $row[0] = '';
                if ($row['col6'] != 2) {
                    if (in_array($row['col0'], $thematic_tools)) {
                        $exp_thematic_tool = explode('_', $row['col0']);
                        $thematic_tool_title = '';
                        if (is_array($exp_thematic_tool)) {
                            foreach ($exp_thematic_tool as $exp) {
                                $thematic_tool_title .= api_ucfirst($exp);
                            }
                        } else {
                            $thematic_tool_title = api_ucfirst($row['col0']);
                        }

                        $row[0] = '<a href="'.$url_tool.'?'.api_get_cidreq().'&action=thematic_details">'.get_lang($thematic_tool_title).'</a>';
                    } else {
                        $row[0] = '<a href="'.$url_tool.'?'.api_get_cidreq().'">'.get_lang('Tool'.api_ucfirst($row['col0'])).'</a>';
                    }
                } else {
                    $row[0] = api_ucfirst($row['col0']);
                }
                $row[1] = get_lang($row[1]);
                $row[6] = api_convert_and_format_date($row['col6'], null, date_default_timezone_get());
                $row[5] = '';
                //@todo Improve this code please
                switch ($table_name['table_name']) {
                    case 'document':
                        $sql = "SELECT tool.title as title FROM $table_tool tool
                                WHERE c_id = $course_id AND id = $ref";
                        $rs_document = Database::query($sql);
                        $obj_document = Database::fetch_object($rs_document);
                        if ($obj_document) {
                            $row[5] = $obj_document->title;
                        }
                        break;
                    case 'announcement':
                        $sql = "SELECT title FROM $table_tool
                                WHERE c_id = $course_id AND id = $ref";
                        $rs_document = Database::query($sql);
                        $obj_document = Database::fetch_object($rs_document);
                        if ($obj_document) {
                            $row[5] = $obj_document->title;
                        }
                        break;
                    case 'glossary':
                        $sql = "SELECT name FROM $table_tool
                                WHERE c_id = $course_id AND glossary_id = $ref";
                        $rs_document = Database::query($sql);
                        $obj_document = Database::fetch_object($rs_document);
                        if ($obj_document) {
                            $row[5] = $obj_document->name;
                        }
                        break;
                    case 'lp':
                        $sql = "SELECT name
                                FROM $table_tool WHERE c_id = $course_id AND id = $ref";
                        $rs_document = Database::query($sql);
                        $obj_document = Database::fetch_object($rs_document);
                        $row[5] = $obj_document->name;
                        break;
                    case 'quiz':
                        $sql = "SELECT title FROM $table_tool
                                WHERE c_id = $course_id AND id = $ref";
                        $rs_document = Database::query($sql);
                        $obj_document = Database::fetch_object($rs_document);
                        if ($obj_document) {
                            $row[5] = $obj_document->title;
                        }
                        break;
                    case 'course_description':
                        $sql = "SELECT title FROM $table_tool
                                WHERE c_id = $course_id AND id = $ref";
                        $rs_document = Database::query($sql);
                        $obj_document = Database::fetch_object($rs_document);
                        if ($obj_document) {
                            $row[5] = $obj_document->title;
                        }
                        break;
                    case 'thematic':
                        $rs = Database::query("SELECT title FROM $table_tool WHERE c_id = $course_id AND id = $ref");
                        if (Database::num_rows($rs) > 0) {
                            $obj = Database::fetch_object($rs);
                            if ($obj) {
                                $row[5] = $obj->title;
                            }
                        }
                        break;
                    case 'thematic_advance':
                        $rs = Database::query("SELECT content FROM $table_tool WHERE c_id = $course_id AND id = $ref");
                        if (Database::num_rows($rs) > 0) {
                            $obj = Database::fetch_object($rs);
                            if ($obj) {
                                $row[5] = $obj->content;
                            }
                        }
                        break;
                    case 'thematic_plan':
                        $rs = Database::query("SELECT title FROM $table_tool WHERE c_id = $course_id AND id = $ref");
                        if (Database::num_rows($rs) > 0) {
                            $obj = Database::fetch_object($rs);
                            if ($obj) {
                                $row[5] = $obj->title;
                            }
                        }
                        break;
                    default:
                        break;
                }

                $row2 = $name_session;
                if (!empty($coach_name)) {
                    $row2 .= '<br />'.get_lang('Coach').': '.$coach_name;
                }
                $row[2] = $row2;
                if (!empty($row['col3'])) {
                    $userInfo = api_get_user_info($row['user_id']);
                    $row['col3'] = Display::url(
                        $row['col3'],
                        $userInfo['profile_url']
                    );
                    $row[3] = $row['col3'];

                    $ip = Tracking::get_ip_from_user_event(
                        $row['user_id'],
                        $row['col6'],
                        true
                    );
                    if (empty($ip)) {
                        $ip = get_lang('Unknown');
                    }
                    $row[4] = $ip;
                }

                $resources[] = $row;
            }
        }

        return $resources;
    }

    public static function get_tool_name_table(string $tool): array
    {
        $link_tool = '';
        $id_tool = '';

        switch ($tool) {
            case 'document':
                $table_name = TABLE_DOCUMENT;
                $link_tool = 'document/document.php';
                $id_tool = 'id';
                break;
            case 'learnpath':
                $table_name = TABLE_LP_MAIN;
                $link_tool = 'lp/lp_controller.php';
                $id_tool = 'id';
                break;
            case 'quiz':
                $table_name = TABLE_QUIZ_TEST;
                $link_tool = 'exercise/exercise.php';
                $id_tool = 'iid';
                break;
            case 'glossary':
                $table_name = TABLE_GLOSSARY;
                $link_tool = 'glossary/index.php';
                $id_tool = 'glossary_id';
                break;
            case 'link':
                $table_name = TABLE_LINK;
                $link_tool = 'link/link.php';
                $id_tool = 'id';
                break;
            case 'course_description':
                $table_name = TABLE_COURSE_DESCRIPTION;
                $link_tool = 'course_description/';
                $id_tool = 'id';
                break;
            case 'announcement':
                $table_name = TABLE_ANNOUNCEMENT;
                $link_tool = 'announcements/announcements.php';
                $id_tool = 'id';
                break;
            case 'thematic':
                $table_name = TABLE_THEMATIC;
                $link_tool = 'course_progress/index.php';
                $id_tool = 'id';
                break;
            case 'thematic_advance':
                $table_name = TABLE_THEMATIC_ADVANCE;
                $link_tool = 'course_progress/index.php';
                $id_tool = 'id';
                break;
            case 'thematic_plan':
                $table_name = TABLE_THEMATIC_PLAN;
                $link_tool = 'course_progress/index.php';
                $id_tool = 'id';
                break;
            default:
                $table_name = $tool;
                break;
        }

        return [
            'table_name' => $table_name,
            'link_tool' => $link_tool,
            'id_tool' => $id_tool,
        ];
    }

    public static function display_additional_profile_fields(array $exclude = []): string
    {
        // getting all the extra profile fields that are defined by the platform administrator
        $extra_fields = UserManager::get_extra_fields(0, 50);

        // creating the form
        $return = '<form action="courseLog.php" method="get" name="additional_profile_field_form" id="additional_profile_field_form">';

        // the select field with the additional user profile fields (= this is where we select the field of which we want to see
        // the information the users have entered or selected.
        $return .= '<select class="chzn-select" name="additional_profile_field[]" multiple>';
        $return .= '<option value="-">'.get_lang('SelectFieldToAdd').'</option>';
        $extra_fields_to_show = 0;
        foreach ($extra_fields as $field) {
            // exclude extra profile fields by id
            if (in_array($field[3], $exclude)) {
                continue;
            }
            // show only extra fields that are visible + and can be filtered, added by J.Montoya
            if ($field[6] == 1 && $field[8] == 1) {
                if (isset($_GET['additional_profile_field']) && $field[0] == $_GET['additional_profile_field']) {
                    $selected = 'selected="selected"';
                } else {
                    $selected = '';
                }
                $extra_fields_to_show++;
                $return .= '<option value="'.$field[0].'" '.$selected.'>'.$field[3].'</option>';
            }
        }
        $return .= '</select>';

        // the form elements for the $_GET parameters (because the form is passed through GET
        foreach ($_GET as $key => $value) {
            if ($key != 'additional_profile_field') {
                $return .= '<input type="hidden" name="'.Security::remove_XSS($key).'" value="'.Security::remove_XSS($value).'" />';
            }
        }
        // the submit button
        $return .= '<button class="save" type="submit">'.get_lang('AddAdditionalProfileField').'</button>';
        $return .= '</form>';
        if ($extra_fields_to_show > 0) {
            return $return;
        } else {
            return '';
        }
    }

    /**
     * This function gets all the information of a certrain ($field_id)
     * additional profile field for a specific list of users is more efficent
     * than get_addtional_profile_information_of_field() function
     * It gets the information of all the users so that it can be displayed
     * in the sortable table or in the csv or xls export.
     *
     * @author    Julio Montoya <gugli100@gmail.com>
     *
     * @param    int field id
     * @param    array list of user ids
     *
     * @since    Nov 2009
     *
     * @version    1.8.6.2
     */
    public static function getAdditionalProfileInformationOfFieldByUser($field_id, $users): array
    {
        // Database table definition
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $table_user_field_values = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $extraField = Database::get_main_table(TABLE_EXTRA_FIELD);
        $result_extra_field = UserManager::get_extra_field_information($field_id);
        $return = [];
        if (!empty($users)) {
            if ($result_extra_field['field_type'] == UserManager::USER_FIELD_TYPE_TAG) {
                foreach ($users as $user_id) {
                    $user_result = UserManager::get_user_tags($user_id, $field_id);
                    $tag_list = [];
                    foreach ($user_result as $item) {
                        $tag_list[] = $item['tag'];
                    }
                    $return[$user_id][] = implode(', ', $tag_list);
                }
            } else {
                $new_user_array = [];
                foreach ($users as $user_id) {
                    $new_user_array[] = "'".$user_id."'";
                }
                $users = implode(',', $new_user_array);
                $extraFieldType = EntityExtraField::USER_FIELD_TYPE;
                // Selecting only the necessary information NOT ALL the user list
                $sql = "SELECT user.user_id, v.value
                        FROM $table_user user
                        INNER JOIN $table_user_field_values v
                        ON (user.user_id = v.item_id)
                        INNER JOIN $extraField f
                        ON (f.id = v.field_id)
                        WHERE
                            f.extra_field_type = $extraFieldType AND
                            v.field_id=".intval($field_id)." AND
                            user.user_id IN ($users)";

                $result = Database::query($sql);
                while ($row = Database::fetch_array($result)) {
                    // get option value for field type double select by id
                    if (!empty($row['value'])) {
                        if ($result_extra_field['field_type'] ==
                            ExtraField::FIELD_TYPE_DOUBLE_SELECT
                        ) {
                            $id_double_select = explode(';', $row['value']);
                            if (is_array($id_double_select)) {
                                $value1 = $result_extra_field['options'][$id_double_select[0]]['option_value'];
                                $value2 = $result_extra_field['options'][$id_double_select[1]]['option_value'];
                                $row['value'] = ($value1.';'.$value2);
                            }
                        }

                        if ($result_extra_field['field_type'] == ExtraField::FIELD_TYPE_SELECT_WITH_TEXT_FIELD) {
                            $parsedValue = explode('::', $row['value']);

                            if ($parsedValue) {
                                $value1 = $result_extra_field['options'][$parsedValue[0]]['display_text'];
                                $value2 = $parsedValue[1];

                                $row['value'] = "$value1: $value2";
                            }
                        }

                        if ($result_extra_field['field_type'] == ExtraField::FIELD_TYPE_TRIPLE_SELECT) {
                            [$level1, $level2, $level3] = explode(';', $row['value']);

                            $row['value'] = $result_extra_field['options'][$level1]['display_text'].' / ';
                            $row['value'] .= $result_extra_field['options'][$level2]['display_text'].' / ';
                            $row['value'] .= $result_extra_field['options'][$level3]['display_text'];
                        }
                    }
                    // get other value from extra field
                    $return[$row['user_id']][] = $row['value'];
                }
            }
        }

        return $return;
    }

    /**
     * count the number of students in this course (used for SortableTable)
     * Deprecated.
     */
    public function count_student_in_course(): int
    {
        global $nbStudents;

        return $nbStudents;
    }

    public function sort_users($a, $b): int
    {
        $tracking = Session::read('tracking_column');

        return strcmp(
            trim(api_strtolower($a[$tracking])),
            trim(api_strtolower($b[$tracking]))
        );
    }

    public function sort_users_desc($a, $b): int
    {
        $tracking = Session::read('tracking_column');

        return strcmp(
            trim(api_strtolower($b[$tracking])),
            trim(api_strtolower($a[$tracking]))
        );
    }

    /**
     * Get number of users for sortable with pagination.
     */
    public static function get_number_of_users(array $conditions): array
    {
        $conditions['get_count'] = true;

        return self::get_user_data(0, 0, 0, '', $conditions);
    }

    /**
     * Get data for users list in sortable with pagination.
     */
    public static function get_user_data(
        $from,
        $number_of_items,
        $column,
        $direction,
        array $conditions = []
    ): array {
        global $user_ids, $course_code, $export_csv, $session_id;
        $includeInvitedUsers = $conditions['include_invited_users']; // include the invited users
        $getCount = $conditions['get_count'] ?? false;

        $csv_content = [];
        $course_code = $course_code ? Database::escape_string($course_code) : api_get_course_id();
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $access_url_id = api_get_current_access_url_id();

        // get all users data from a course for sortable with limit
        if (is_array($user_ids) && !empty($user_ids)) {
            $user_ids = array_map('intval', $user_ids);
            $condition_user = " WHERE user.id IN (".implode(',', $user_ids).") ";
        } else {
            $user_ids = (int) $user_ids;
            $condition_user = " WHERE user.id = $user_ids ";
        }

        if (!empty($_GET['user_keyword'])) {
            $keyword = trim(Database::escape_string($_GET['user_keyword']));
            $condition_user .= " AND (
                user.firstname LIKE '%".$keyword."%' OR
                user.lastname LIKE '%".$keyword."%'  OR
                user.username LIKE '%".$keyword."%'  OR
                user.email LIKE '%".$keyword."%'
             ) ";
        }

        $url_table = '';
        $url_condition = '';
        if (api_is_multiple_url_enabled()) {
            $url_table = " INNER JOIN $tbl_url_rel_user as url_users ON (user.id = url_users.user_id)";
            $url_condition = " AND access_url_id = '$access_url_id'";
        }

        $invitedUsersCondition = '';
        if (!$includeInvitedUsers) {
            $invitedUsersCondition = " AND user.status != ".INVITEE;
        }

        $select = '
                SELECT user.id as user_id,
                    user.official_code  as col0,
                    user.lastname       as col1,
                    user.firstname      as col2,
                    user.username       as col3,
                    user.email          as col4';
        if ($getCount) {
            $select = ' SELECT COUNT(distinct(user.id)) as count ';
        }

        $sqlInjectJoins = '';
        $where = 'AND 1 = 1 ';
        $sqlInjectWhere = '';
        if (!empty($conditions)) {
            if (isset($conditions['inject_joins'])) {
                $sqlInjectJoins = $conditions['inject_joins'];
            }
            if (isset($conditions['where'])) {
                $where = $conditions['where'];
            }
            if (isset($conditions['inject_where'])) {
                $sqlInjectWhere = $conditions['inject_where'];
            }
            $injectExtraFields = !empty($conditions['inject_extra_fields']) ? $conditions['inject_extra_fields'] : 1;
            $injectExtraFields = rtrim($injectExtraFields, ', ');
            if (false === $getCount) {
                $select .= " , $injectExtraFields";
            }
        }

        $sql = "$select
                FROM $tbl_user as user
                $url_table
                $sqlInjectJoins
                $condition_user
                $url_condition
                $invitedUsersCondition
                $where
                $sqlInjectWhere
                ";

        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }

        $column = $column <= 2 ? (int) $column : 0;
        $from = (int) $from;
        $number_of_items = (int) $number_of_items;

        if ($getCount) {
            $res = Database::query($sql);
            $row = Database::fetch_array($res);

            return $row['count'];
        }

        $sortByFirstName = api_sort_by_first_name();

        if ($sortByFirstName) {
            if ($column == 1) {
                $column = 2;
            } elseif ($column == 2) {
                $column = 1;
            }
        }

        $sql .= " ORDER BY col$column $direction ";
        $sql .= " LIMIT $from, $number_of_items";

        $res = Database::query($sql);
        $users = [];

        $courseInfo = api_get_course_info($course_code);
        $courseId = $courseInfo['real_id'];
        $courseCode = $courseInfo['code'];

        $total_surveys = 0;
        $total_exercises = ExerciseLib::get_all_exercises(
            $courseInfo,
            $session_id,
            false,
            null,
            false,
            3
        );

        if (empty($session_id)) {
            $survey_user_list = [];
            $surveyList = SurveyManager::get_surveys($course_code, $session_id);
            if ($surveyList) {
                $total_surveys = count($surveyList);
                foreach ($surveyList as $survey) {
                    $user_list = SurveyManager::get_people_who_filled_survey(
                        $survey['survey_id'],
                        false,
                        $courseId
                    );

                    foreach ($user_list as $user_id) {
                        isset($survey_user_list[$user_id]) ? $survey_user_list[$user_id]++ : $survey_user_list[$user_id] = 1;
                    }
                }
            }
        }

        $urlBase = api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?details=true&cidReq='.$courseCode.
            '&course='.$course_code.'&origin=tracking_course&id_session='.$session_id;

        Session::write('user_id_list', []);
        $userIdList = [];

        $addExerciseOption = api_get_configuration_value('add_exercise_best_attempt_in_report');
        $exerciseResultsToCheck = [];
        if (!empty($addExerciseOption) && isset($addExerciseOption['courses']) &&
            isset($addExerciseOption['courses'][$courseCode])
        ) {
            foreach ($addExerciseOption['courses'][$courseCode] as $exerciseId) {
                $exercise = new Exercise();
                $exercise->read($exerciseId);
                if ($exercise->iid) {
                    $exerciseResultsToCheck[] = $exercise;
                }
            }
        }

        $lpShowMaxProgress = api_get_configuration_value('lp_show_max_progress_instead_of_average');
        if (api_get_configuration_value('lp_show_max_progress_or_average_enable_course_level_redefinition')) {
            $lpShowProgressCourseSetting = api_get_course_setting('lp_show_max_or_average_progress', $courseInfo, true);
            if (in_array($lpShowProgressCourseSetting, ['max', 'average'])) {
                $lpShowMaxProgress = ('max' === $lpShowProgressCourseSetting);
            }
        }

        while ($user = Database::fetch_array($res, 'ASSOC')) {
            $userIdList[] = $user['user_id'];
            $user['official_code'] = $user['col0'];
            $user['username'] = $user['col3'];
            $user['time'] = api_time_to_hms(
                Tracking::get_time_spent_on_the_course(
                    $user['user_id'],
                    $courseId,
                    $session_id
                )
            );

            $avg_student_score = Tracking::get_avg_student_score(
                $user['user_id'],
                $course_code,
                [],
                $session_id
            );

            $averageBestScore = Tracking::get_avg_student_score(
                $user['user_id'],
                $course_code,
                [],
                $session_id,
                false,
                false,
                true
            );

            $avg_student_progress = Tracking::get_avg_student_progress(
                $user['user_id'],
                $course_code,
                [],
                $session_id,
                false,
                false,
                $lpShowMaxProgress
            );

            if (empty($avg_student_progress)) {
                $avg_student_progress = 0;
            }
            $user['average_progress'] = $avg_student_progress.'%';

            $total_user_exercise = Tracking::get_exercise_student_progress(
                $total_exercises,
                $user['user_id'],
                $courseId,
                $session_id
            );

            $user['exercise_progress'] = $total_user_exercise;

            $total_user_exercise = Tracking::get_exercise_student_average_best_attempt(
                $total_exercises,
                $user['user_id'],
                $courseId,
                $session_id
            );

            $user['exercise_average_best_attempt'] = $total_user_exercise;

            if (is_numeric($avg_student_score)) {
                $user['student_score'] = $avg_student_score.'%';
            } else {
                $user['student_score'] = $avg_student_score;
            }

            if (is_numeric($averageBestScore)) {
                $user['student_score_best'] = $averageBestScore.'%';
            } else {
                $user['student_score_best'] = $averageBestScore;
            }

            $exerciseResults = [];
            if (!empty($exerciseResultsToCheck)) {
                foreach ($exerciseResultsToCheck as $exercise) {
                    $bestExerciseResult = Event::get_best_attempt_exercise_results_per_user(
                        $user['user_id'],
                        $exercise->iid,
                        $courseId,
                        $session_id,
                        false
                    );

                    $best = null;
                    if ($bestExerciseResult) {
                        $best = $bestExerciseResult['exe_result'] / $bestExerciseResult['exe_weighting'];
                        $best = round($best, 2) * 100;
                        $best .= '%';
                    }
                    $exerciseResults['exercise_'.$exercise->iid] = $best;
                }
            }

            $user['count_assignments'] = Tracking::count_student_assignments(
                $user['user_id'],
                $course_code,
                $session_id
            );
            $user['count_messages'] = Tracking::count_student_messages(
                $user['user_id'],
                $course_code,
                $session_id
            );
            $user['first_connection'] = Tracking::get_first_connection_date_on_the_course(
                $user['user_id'],
                $courseId,
                $session_id,
                false === $export_csv
            );

            $user['last_connection'] = Tracking::get_last_connection_date_on_the_course(
                $user['user_id'],
                $courseInfo,
                $session_id,
                false === $export_csv
            );

            if ($export_csv) {
                if (!empty($user['first_connection'])) {
                    $user['first_connection'] = api_get_local_time($user['first_connection']);
                } else {
                    $user['first_connection'] = '-';
                }
                if (!empty($user['last_connection'])) {
                    $user['last_connection'] = api_get_local_time($user['last_connection']);
                } else {
                    $user['last_connection'] = '-';
                }
            }

            if (empty($session_id)) {
                $user['survey'] = (isset($survey_user_list[$user['user_id']]) ? $survey_user_list[$user['user_id']] : 0).' / '.$total_surveys;
            }

            $url = $urlBase.'&student='.$user['user_id'];

            $user['link'] = '<center><a href="'.$url.'">
                            '.Display::return_icon('2rightarrow.png', get_lang('Details')).'
                             </a></center>';

            // store columns in array $users
            $user_row = [];
            $user_row['official_code'] = $user['official_code']; //0
            if ($sortByFirstName) {
                $user_row['firstname'] = $user['col2'];
                $user_row['lastname'] = $user['col1'];
            } else {
                $user_row['lastname'] = $user['col1'];
                $user_row['firstname'] = $user['col2'];
            }
            $user_row['username'] = $user['username'];
            $user_row['time'] = $user['time'];
            $user_row['average_progress'] = $user['average_progress'];
            $user_row['exercise_progress'] = $user['exercise_progress'];
            $user_row['exercise_average_best_attempt'] = $user['exercise_average_best_attempt'];
            $user_row['student_score'] = $user['student_score'];
            $user_row['student_score_best'] = $user['student_score_best'];
            if (!empty($exerciseResults)) {
                foreach ($exerciseResults as $exerciseId => $bestResult) {
                    $user_row[$exerciseId] = $bestResult;
                }
            }

            $user_row['count_assignments'] = $user['count_assignments'];
            $user_row['count_messages'] = $user['count_messages'];

            $userGroupManager = new UserGroup();
            if ($export_csv) {
                $user_row['classes'] = implode(
                    ',',
                    $userGroupManager->getNameListByUser($user['user_id'], UserGroup::NORMAL_CLASS)
                );
            } else {
                $user_row['classes'] = $userGroupManager->getLabelsFromNameList(
                    $user['user_id'],
                    UserGroup::NORMAL_CLASS
                );
            }

            if (empty($session_id)) {
                $user_row['survey'] = $user['survey'];
            } else {
                $userSession = SessionManager::getUserSession($user['user_id'], $session_id);
                $user_row['registered_at'] = '';
                if ($userSession) {
                    $user_row['registered_at'] = api_get_local_time($userSession['registered_at']);
                }
            }

            $user_row['first_connection'] = $user['first_connection'];
            $user_row['last_connection'] = $user['last_connection'];

            // we need to display an additional profile field
            if (isset($_GET['additional_profile_field'])) {
                $data = Session::read('additional_user_profile_info');

                $extraFieldInfo = Session::read('extra_field_info');
                foreach ($_GET['additional_profile_field'] as $fieldId) {
                    if (isset($data[$fieldId]) && isset($data[$fieldId][$user['user_id']])) {
                        if (is_array($data[$fieldId][$user['user_id']])) {
                            $user_row[$extraFieldInfo[$fieldId]['variable']] = implode(
                                ', ',
                                $data[$fieldId][$user['user_id']]
                            );
                        } else {
                            $user_row[$extraFieldInfo[$fieldId]['variable']] = $data[$fieldId][$user['user_id']];
                        }
                    } else {
                        $user_row[$extraFieldInfo[$fieldId]['variable']] = '';
                    }
                }
            }

            $data = Session::read('default_additional_user_profile_info');
            $defaultExtraFieldInfo = Session::read('default_extra_field_info');
            if (isset($defaultExtraFieldInfo) && isset($data)) {
                foreach ($data as $key => $val) {
                    if (isset($val[$user['user_id']])) {
                        if (is_array($val[$user['user_id']])) {
                            $user_row[$defaultExtraFieldInfo[$key]['variable']] = implode(
                                ', ',
                                $val[$user['user_id']]
                            );
                        } else {
                            $user_row[$defaultExtraFieldInfo[$key]['variable']] = $val[$user['user_id']];
                        }
                    } else {
                        $user_row[$defaultExtraFieldInfo[$key]['variable']] = '';
                    }
                }
            }

            if (api_get_setting('show_email_addresses') === 'true') {
                $user_row['email'] = $user['col4'];
            }

            $user_row['link'] = $user['link'];

            if ($export_csv) {
                unset($user_row['link']);
                $csv_content[] = $user_row;
            }
            $users[] = array_values($user_row);
        }

        if ($export_csv) {
            Session::write('csv_content', $csv_content);
        }

        Session::erase('additional_user_profile_info');
        Session::erase('extra_field_info');
        Session::erase('default_additional_user_profile_info');
        Session::erase('default_extra_field_info');
        Session::write('user_id_list', $userIdList);

        return $users;
    }

    /**
     * Get data for users list in sortable with pagination.
     */
    public static function getTotalTimeReport(
        $from,
        $number_of_items,
        $column,
        $direction,
        bool $includeInvitedUsers = false
    ): array {
        global $user_ids, $course_code, $export_csv, $session_id;

        $course_code = Database::escape_string($course_code);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $access_url_id = api_get_current_access_url_id();

        // get all users data from a course for sortable with limit
        if (is_array($user_ids)) {
            $user_ids = array_map('intval', $user_ids);
            $condition_user = " WHERE user.user_id IN (".implode(',', $user_ids).") ";
        } else {
            $user_ids = intval($user_ids);
            $condition_user = " WHERE user.user_id = $user_ids ";
        }

        $url_table = null;
        $url_condition = null;
        if (api_is_multiple_url_enabled()) {
            $url_table = ", ".$tbl_url_rel_user." as url_users";
            $url_condition = " AND user.user_id = url_users.user_id AND access_url_id='$access_url_id'";
        }

        $invitedUsersCondition = '';
        if (!$includeInvitedUsers) {
            $invitedUsersCondition = " AND user.status != ".INVITEE;
        }

        $sql = "SELECT  user.user_id as user_id,
                    user.official_code  as col0,
                    user.lastname       as col1,
                    user.firstname      as col2,
                    user.username       as col3
                FROM $tbl_user as user $url_table
                $condition_user $url_condition $invitedUsersCondition";

        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }

        $column = (int) $column;
        $from = (int) $from;
        $number_of_items = (int) $number_of_items;

        $sql .= " ORDER BY col$column $direction ";
        $sql .= " LIMIT $from,$number_of_items";

        $res = Database::query($sql);
        $users = [];

        $sortByFirstName = api_sort_by_first_name();
        $courseInfo = api_get_course_info($course_code);
        $courseId = $courseInfo['real_id'];

        while ($user = Database::fetch_array($res, 'ASSOC')) {
            $user['official_code'] = $user['col0'];
            $user['lastname'] = $user['col1'];
            $user['firstname'] = $user['col2'];
            $user['username'] = $user['col3'];

            $totalCourseTime = Tracking::get_time_spent_on_the_course(
                $user['user_id'],
                $courseId,
                $session_id
            );

            $user['time'] = api_time_to_hms($totalCourseTime);
            $totalLpTime = Tracking::get_time_spent_in_lp(
                $user['user_id'],
                $course_code,
                [],
                $session_id
            );

            $warning = '';
            if ($totalLpTime > $totalCourseTime) {
                $warning = '&nbsp;'.Display::label(get_lang('TimeDifference'), 'danger');
            }

            $user['total_lp_time'] = api_time_to_hms($totalLpTime).$warning;

            $user['first_connection'] = Tracking::get_first_connection_date_on_the_course(
                $user['user_id'],
                $courseId,
                $session_id
            );
            $user['last_connection'] = Tracking::get_last_connection_date_on_the_course(
                $user['user_id'],
                $courseInfo,
                $session_id,
                $export_csv === false
            );

            $user['link'] = '<center>
                             <a href="../mySpace/myStudents.php?student='.$user['user_id'].'&details=true&course='.$course_code.'&origin=tracking_course&id_session='.$session_id.'">
                             '.Display::return_icon('2rightarrow.png', get_lang('Details')).'
                             </a>
                         </center>';

            // store columns in array $users
            $user_row = [];
            $user_row['official_code'] = $user['official_code']; //0
            if ($sortByFirstName) {
                $user_row['firstname'] = $user['firstname'];
                $user_row['lastname'] = $user['lastname'];
            } else {
                $user_row['lastname'] = $user['lastname'];
                $user_row['firstname'] = $user['firstname'];
            }
            $user_row['username'] = $user['username'];
            $user_row['time'] = $user['time'];
            $user_row['total_lp_time'] = $user['total_lp_time'];
            $user_row['first_connection'] = $user['first_connection'];
            $user_row['last_connection'] = $user['last_connection'];

            $user_row['link'] = $user['link'];
            $users[] = array_values($user_row);
        }

        return $users;
    }

    public static function actionsLeft($current, $sessionId = 0): string
    {
        $usersLink = Display::url(
            Display::return_icon('user.png', get_lang('StudentsTracking'), [], ICON_SIZE_MEDIUM),
            'courseLog.php?'.api_get_cidreq(true, false)
        );

        $groupsLink = Display::url(
            Display::return_icon('group.png', get_lang('GroupReporting'), [], ICON_SIZE_MEDIUM),
            'course_log_groups.php?'.api_get_cidreq()
        );

        $resourcesLink = Display::url(
            Display::return_icon('tools.png', get_lang('ResourcesTracking'), [], ICON_SIZE_MEDIUM),
            'course_log_resources.php?'.api_get_cidreq(true, false)
        );

        $courseLink = Display::url(
            Display::return_icon('course.png', get_lang('CourseTracking'), [], ICON_SIZE_MEDIUM),
            'course_log_tools.php?'.api_get_cidreq(true, false)
        );

        $examLink = Display::url(
            Display::return_icon('quiz.png', get_lang('ExamTracking'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'tracking/exams.php?'.api_get_cidreq()
        );

        $eventsLink = Display::url(
            Display::return_icon('security.png', get_lang('EventsReport'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'tracking/course_log_events.php?'.api_get_cidreq()
        );

        $lpLink = Display::url(
            Display::return_icon('scorms.png', get_lang('CourseLearningPathsGenericStats'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'tracking/lp_report.php?'.api_get_cidreq()
        );

        $attendanceLink = '';
        if (!empty($sessionId)) {
            $attendanceLink = Display::url(
                Display::return_icon('attendance_list.png', get_lang('Logins'), '', ICON_SIZE_MEDIUM),
                api_get_path(WEB_CODE_PATH).'attendance/index.php?'.api_get_cidreq().'&action=calendar_logins'
            );
        }

        switch ($current) {
            case 'users':
                $usersLink = Display::url(
                    Display::return_icon(
                        'user_na.png',
                        get_lang('StudentsTracking'),
                        [],
                        ICON_SIZE_MEDIUM
                    ),
                    '#'
                );
                break;
            case 'groups':
                $groupsLink = Display::url(
                    Display::return_icon('group_na.png', get_lang('GroupReporting'), [], ICON_SIZE_MEDIUM),
                    '#'
                );
                break;
            case 'courses':
                $courseLink = Display::url(
                    Display::return_icon('course_na.png', get_lang('CourseTracking'), [], ICON_SIZE_MEDIUM),
                    '#'
                );
                break;
            case 'resources':
                $resourcesLink = Display::url(
                    Display::return_icon(
                        'tools_na.png',
                        get_lang('ResourcesTracking'),
                        [],
                        ICON_SIZE_MEDIUM
                    ),
                    '#'
                );
                break;
            case 'exams':
                $examLink = Display::url(
                    Display::return_icon('quiz_na.png', get_lang('ExamTracking'), [], ICON_SIZE_MEDIUM),
                    '#'
                );
                break;
            case 'logs':
                $eventsLink = Display::url(
                    Display::return_icon('security_na.png', get_lang('EventsReport'), [], ICON_SIZE_MEDIUM),
                    '#'
                );
                break;
            case 'attendance':
                if (!empty($sessionId)) {
                    $attendanceLink = Display::url(
                        Display::return_icon('attendance_list.png', get_lang('Logins'), '', ICON_SIZE_MEDIUM),
                        '#'
                    );
                }
                break;
            case 'lp':
                $lpLink = Display::url(
                    Display::return_icon('scorms_na.png', get_lang('CourseLearningPathsGenericStats'), [], ICON_SIZE_MEDIUM),
                    '#'
                );
                break;
        }

        $items = [
            $usersLink,
            $groupsLink,
            $courseLink,
            $resourcesLink,
            $examLink,
            $eventsLink,
            $lpLink,
            $attendanceLink,
        ];

        return implode('', $items).'&nbsp;';
    }

    public static function getTeachersOrCoachesHtmlHeader(
        string $courseCode,
        int $cId,
        int $sessionId,
        bool $addLinkToPrfile
    ): string {
        $html = '';

        $teacherList = CourseManager::getTeacherListFromCourseCodeToString(
            $courseCode,
            ',',
            $addLinkToPrfile,
            true
        );

        if (!empty($teacherList)) {
            $html .= Display::page_subheader2(get_lang('Teachers'));
            $html .= $teacherList;
        }

        if (!empty($sessionId)) {
            $coaches = CourseManager::get_coachs_from_course_to_string(
                $sessionId,
                $cId,
                ',',
                $addLinkToPrfile,
                true
            );

            if (!empty($coaches)) {
                $html .= Display::page_subheader2(get_lang('Coaches'));
                $html .= $coaches;
            }
        }

        return $html;
    }

    /**
     * @return float|string
     */
    public static function calcBestScoreAverageNotInLP(
        array $exerciseList,
        array $usersInGroup,
        int $cId,
        int $sessionId = 0,
        bool $returnFormatted = false
    ) {
        if (empty($exerciseList) || empty($usersInGroup)) {
            return 0;
        }

        $bestScoreAverageNotInLP = 0;

        foreach ($exerciseList as $exerciseData) {
            foreach ($usersInGroup as $userId) {
                $results = Event::get_best_exercise_results_by_user(
                    $exerciseData['iid'],
                    $cId,
                    $sessionId,
                    $userId
                );

                $scores = array_map(
                    function (array $result) {
                        return empty($result['exe_weighting']) ? 0 : $result['exe_result'] / $result['exe_weighting'];
                    },
                    $results
                );

                $bestScoreAverageNotInLP += $scores ? max($scores) : 0;
            }
        }

        $rounded = round(
            $bestScoreAverageNotInLP / count($exerciseList) * 100 / count($usersInGroup),
            2
        );

        if ($returnFormatted) {
            return sprintf(get_lang('XPercent'), $rounded);
        }

        return $rounded;
    }
}
