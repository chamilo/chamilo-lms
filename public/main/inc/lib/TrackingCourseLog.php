<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField as EntityExtraField;
use ChamiloSession as Session;

class TrackingCourseLog
{
    /**
     * Counts the item resources and returns the result as a mixed type.
     */
    public static function countItemResources(): mixed
    {
        $sessionId = api_get_session_id();
        $courseId = api_get_course_int_id();

        $tableItemProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $tableUser = Database::get_main_table(TABLE_MAIN_USER);

        $sql = "SELECT count(tool) AS total_number_of_items
                FROM $tableItemProperty track_resource, $tableUser user
                WHERE
                    track_resource.c_id = $courseId AND
                    track_resource.insert_user_id = user.user_id AND
                    session_id ".(empty($sessionId) ? ' IS NULL ' : " = $sessionId ");

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
     * Retrieves item resources data with pagination and sorting options.
     */
    public static function getItemResourcesData($from, $numberOfItems, $column, $direction): array
    {
        $sessionId = api_get_session_id();
        $courseId = api_get_course_int_id();

        $tableItemProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $tableUser = Database::get_main_table(TABLE_MAIN_USER);
        $tableSession = Database::get_main_table(TABLE_MAIN_SESSION);
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
                FROM $tableItemProperty track_resource, $tableUser user
                WHERE
                  track_resource.c_id = $courseId AND
                  track_resource.insert_user_id = user.user_id AND
                  session_id ".(empty($sessionId) ? ' IS NULL ' : " = $sessionId ");

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

        if (0 == $column) {
            $column = '0';
        }
        if ('' != $column && '' != $direction) {
            if (2 != $column && 4 != $column) {
                $sql .= " ORDER BY col$column $direction";
            }
        } else {
            $sql .= " ORDER BY col6 DESC ";
        }

        $from = intval($from);
        if ($from) {
            $numberOfItems = intval($numberOfItems);
            $sql .= " LIMIT $from, $numberOfItems ";
        }

        $res = Database::query($sql);
        $resources = [];
        $thematicTools = ['thematic', 'thematic_advance', 'thematic_plan'];
        while ($row = Database::fetch_array($res)) {
            $ref = $row['ref'];
            $tableName = self::getToolNameTable($row['col0']);
            $tableTool = Database::get_course_table($tableName['table_name']);

            $id = $tableName['id_tool'];
            $recorset = false;

            if (in_array($row['col0'], ['thematic_plan', 'thematic_advance'])) {
                $tblThematic = Database::get_course_table(TABLE_THEMATIC);
                $sql = "SELECT thematic_id FROM $tableTool
                        WHERE c_id = $courseId AND id = $ref";
                $rsThematic = Database::query($sql);
                if (Database::num_rows($rsThematic)) {
                    $rowThematic = Database::fetch_array($rsThematic);
                    $thematicId = $rowThematic['thematic_id'];

                    $sql = "SELECT session.id, session.name, user.username
                            FROM $tblThematic t, $tableSession session, $tableUser user
                            WHERE
                              t.c_id = $courseId AND
                              t.session_id = session.id AND
                              session.id_coach = user.user_id AND
                              t.id = $thematicId";
                    $recorset = Database::query($sql);
                }
            } else {
                $sql = "SELECT session.id, session.name, user.username
                          FROM $tableTool tool, $tableSession session, $tableUser user
                          WHERE
                              tool.c_id = $courseId AND
                              tool.session_id = session.id AND
                              session.id_coach = user.user_id AND
                              tool.$id = $ref";
                $recorset = Database::query($sql);
            }

            if (!empty($recorset)) {
                $obj = Database::fetch_object($recorset);

                $nameSession = '';
                $coachName = '';
                if (!empty($obj)) {
                    $nameSession = $obj->name;
                    $coachName = $obj->username;
                }

                $urlTool = api_get_path(WEB_CODE_PATH).$tableName['link_tool'];

                if ($row['col6'] != 2) {
                    if (in_array($row['col0'], $thematicTools)) {
                        $expThematicTool = explode('_', $row['col0']);
                        $thematicTooltitle = '';
                        if (is_array($expThematicTool)) {
                            foreach ($expThematicTool as $exp) {
                                $thematicTooltitle .= api_ucfirst($exp);
                            }
                        } else {
                            $thematicTooltitle = api_ucfirst($row['col0']);
                        }

                        $row[0] = '<a href="'.$urlTool.'?'.api_get_cidreq().'&action=thematic_details">'.get_lang(
                                $thematicTooltitle
                            ).'</a>';
                    } else {
                        $row[0] = '<a href="'.$urlTool.'?'.api_get_cidreq().'">'.get_lang(
                                'Tool'.api_ucfirst($row['col0'])
                            ).'</a>';
                    }
                } else {
                    $row[0] = api_ucfirst($row['col0']);
                }
                $row[1] = get_lang($row[1]);
                $row[6] = api_convert_and_format_date($row['col6'], null, date_default_timezone_get());
                $row[5] = '';
                //@todo Improve this code please
                switch ($tableName['table_name']) {
                    case 'document':
                        $sql = "SELECT tool.title as title FROM $tableTool tool
                                WHERE c_id = $courseId AND id = $ref";
                        $rsDocument = Database::query($sql);
                        $objDocument = Database::fetch_object($rsDocument);
                        if ($objDocument) {
                            $row[5] = $objDocument->title;
                        }
                        break;
                    case 'quiz':
                    case 'course_description':
                    case 'announcement':
                        $sql = "SELECT title FROM $tableTool
                                WHERE c_id = $courseId AND id = $ref";
                        $rsDocument = Database::query($sql);
                        $objDocument = Database::fetch_object($rsDocument);
                        if ($objDocument) {
                            $row[5] = $objDocument->title;
                        }
                        break;
                    case 'glossary':
                        $sql = "SELECT name FROM $tableTool
                                WHERE c_id = $courseId AND glossary_id = $ref";
                        $rsDocument = Database::query($sql);
                        $objDocument = Database::fetch_object($rsDocument);
                        if ($objDocument) {
                            $row[5] = $objDocument->name;
                        }
                        break;
                    case 'lp':
                        $sql = "SELECT name
                                FROM $tableTool WHERE c_id = $courseId AND id = $ref";
                        $rsDocument = Database::query($sql);
                        $objDocument = Database::fetch_object($rsDocument);
                        $row[5] = $objDocument->name;
                        break;
                    case 'thematic_plan':
                    case 'thematic':
                        $rs = Database::query("SELECT title FROM $tableTool WHERE c_id = $courseId AND id = $ref");
                        if (Database::num_rows($rs) > 0) {
                            $obj = Database::fetch_object($rs);
                            if ($obj) {
                                $row[5] = $obj->title;
                            }
                        }
                        break;
                    case 'thematic_advance':
                        $rs = Database::query("SELECT content FROM $tableTool WHERE c_id = $courseId AND id = $ref");
                        if (Database::num_rows($rs) > 0) {
                            $obj = Database::fetch_object($rs);
                            if ($obj) {
                                $row[5] = $obj->content;
                            }
                        }
                        break;
                    case 'thematic_plan':
                        $rs = Database::query("SELECT title FROM $tableTool WHERE c_id = $courseId AND id = $ref");
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

                $row2 = $nameSession;
                if (!empty($coachName)) {
                    $row2 .= '<br />'.get_lang('Coach').': '.$coachName;
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

    /**
     * Retrieves the name and associated table for a given tool.
     */
    public static function getToolNameTable(string $tool): array
    {
        $linkTool = '';
        $idTool = '';

        switch ($tool) {
            case 'document':
                $tableName = TABLE_DOCUMENT;
                $linkTool = 'document/document.php';
                $idTool = 'id';
                break;
            case 'learnpath':
                $tableName = TABLE_LP_MAIN;
                $linkTool = 'lp/lp_controller.php';
                $idTool = 'id';
                break;
            case 'quiz':
                $tableName = TABLE_QUIZ_TEST;
                $linkTool = 'exercise/exercise.php';
                $idTool = 'iid';
                break;
            case 'glossary':
                $tableName = TABLE_GLOSSARY;
                $linkTool = 'glossary/index.php';
                $idTool = 'glossary_id';
                break;
            case 'link':
                $tableName = TABLE_LINK;
                $linkTool = 'link/link.php';
                $idTool = 'id';
                break;
            case 'course_description':
                $tableName = TABLE_COURSE_DESCRIPTION;
                $linkTool = 'course_description/';
                $idTool = 'id';
                break;
            case 'announcement':
                $tableName = TABLE_ANNOUNCEMENT;
                $linkTool = 'announcements/announcements.php';
                $idTool = 'id';
                break;
            case 'thematic':
                $tableName = TABLE_THEMATIC;
                $linkTool = 'course_progress/index.php';
                $idTool = 'id';
                break;
            case 'thematic_advance':
                $tableName = TABLE_THEMATIC_ADVANCE;
                $linkTool = 'course_progress/index.php';
                $idTool = 'id';
                break;
            case 'thematic_plan':
                $tableName = TABLE_THEMATIC_PLAN;
                $linkTool = 'course_progress/index.php';
                $idTool = 'id';
                break;
            default:
                $tableName = $tool;
                break;
        }

        return [
            'table_name' => $tableName,
            'link_tool' => $linkTool,
            'id_tool' => $idTool,
        ];
    }

    /**
     * Displays additional profile fields, excluding specific fields if provided.
     */
    public static function displayAdditionalProfileFields(array $exclude = [], $formAction = null): string
    {
        $formAction = $formAction ?: 'courseLog.php';

        // getting all the extra profile fields that are defined by the platform administrator
        $extraFields = UserManager::get_extra_fields(0, 50);

        // creating the form
        $return = '<form action="'.$formAction.'" method="get" name="additional_profile_field_form"
            id="additional_profile_field_form">';
        // the select field with the additional user profile fields, this is where we select the field of which we want to see
        // the information the users have entered or selected.
        $return .= '<div class="form-group">';
        $return .= '<select class="chzn-select" name="additional_profile_field[]" multiple>';
        $return .= '<option value="-">'.get_lang('SelectFieldToAdd').'</option>';
        $extraFieldsToShow = 0;
        foreach ($extraFields as $field) {
            // exclude extra profile fields by id
            if (in_array($field[3], $exclude)) {
                continue;
            }
            // show only extra fields that are visible + and can be filtered, added by J.Montoya
            if ($field[6] == 1 && $field[8] == 1) {
                if (isset($_GET['additional_profile_field']) && in_array($field[0], $_GET['additional_profile_field'])) {
                    $selected = 'selected="selected"';
                } else {
                    $selected = '';
                }
                $extraFieldsToShow++;
                $return .= '<option value="'.$field[0].'" '.$selected.'>'.$field[3].'</option>';
            }
        }
        $return .= '</select>';
        $return .= '</div>';

        // the form elements for the $_GET parameters (because the form is passed through GET
        foreach ($_GET as $key => $value) {
            if ($key != 'additional_profile_field') {
                $return .= '<input type="hidden" name="'.Security::remove_XSS($key).'" value="'.Security::remove_XSS(
                        $value
                    ).'" />';
            }
        }
        // the submit button
        $return .= '<div class="form-group">';
        $return .= '<button class="save btn btn-primary" type="submit">'
            .get_lang('AddAdditionalProfileField').'</button>';
        $return .= '</div>';
        $return .= '</form>';

        return $extraFieldsToShow > 0 ? $return : '';
    }

    /**
     * This function gets all the information of a certrain ($field_id)
     * additional profile field for a specific list of users is more efficent
     * than get_addtional_profile_information_of_field() function
     * It gets the information of all the users so that it can be displayed
     * in the sortable table or in the csv or xls export.
     *
     * @param int $fieldId field id
     * @param array $users list of user ids
     *
     * @author     Julio Montoya <gugli100@gmail.com>
     *
     * @since      Nov 2009
     *
     * @version    1.8.6.2
     */
    public static function getAdditionalProfileInformationOfFieldByUser($fieldId, $users): array
    {
        // Database table definition
        $tableUser = Database::get_main_table(TABLE_MAIN_USER);
        $tableUserFieldValues = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $extraField = Database::get_main_table(TABLE_EXTRA_FIELD);
        $resultExtraField = UserManager::get_extra_field_information($fieldId);
        $return = [];
        if (!empty($users)) {
            if ($resultExtraField['field_type'] == UserManager::USER_FIELD_TYPE_TAG) {
                foreach ($users as $user_id) {
                    $userResult = UserManager::get_user_tags($user_id, $fieldId);
                    $tagList = [];
                    foreach ($userResult as $item) {
                        $tagList[] = $item['tag'];
                    }
                    $return[$user_id][] = implode(', ', $tagList);
                }
            } else {
                $newUserArray = [];
                foreach ($users as $user_id) {
                    $newUserArray[] = "'".$user_id."'";
                }
                $users = implode(',', $newUserArray);
                $extraFieldType = EntityExtraField::USER_FIELD_TYPE;
                // Selecting only the necessary information NOT ALL the user list
                $sql = "SELECT user.user_id, v.value
                        FROM $tableUser user
                        INNER JOIN $tableUserFieldValues v
                        ON (user.user_id = v.item_id)
                        INNER JOIN $extraField f
                        ON (f.id = v.field_id)
                        WHERE
                            f.extra_field_type = $extraFieldType AND
                            v.field_id=".intval($fieldId)." AND
                            user.user_id IN ($users)";

                $result = Database::query($sql);
                while ($row = Database::fetch_array($result)) {
                    // get option value for field type double select by id
                    if (!empty($row['value'])) {
                        if ($resultExtraField['field_type'] ==
                            ExtraField::FIELD_TYPE_DOUBLE_SELECT
                        ) {
                            $idDoubleSelect = explode(';', $row['value']);
                            if (is_array($idDoubleSelect)) {
                                $value1 = $resultExtraField['options'][$idDoubleSelect[0]]['option_value'];
                                $value2 = $resultExtraField['options'][$idDoubleSelect[1]]['option_value'];
                                $row['value'] = ($value1.';'.$value2);
                            }
                        }

                        if ($resultExtraField['field_type'] == ExtraField::FIELD_TYPE_SELECT_WITH_TEXT_FIELD) {
                            $parsedValue = explode('::', $row['value']);

                            if ($parsedValue) {
                                $value1 = $resultExtraField['options'][$parsedValue[0]]['display_text'];
                                $value2 = $parsedValue[1];

                                $row['value'] = "$value1: $value2";
                            }
                        }

                        if ($resultExtraField['field_type'] == ExtraField::FIELD_TYPE_TRIPLE_SELECT) {
                            [$level1, $level2, $level3] = explode(';', $row['value']);

                            $row['value'] = $resultExtraField['options'][$level1]['display_text'].' / ';
                            $row['value'] .= $resultExtraField['options'][$level2]['display_text'].' / ';
                            $row['value'] .= $resultExtraField['options'][$level3]['display_text'];
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
     * Get number of users for sortable with pagination.
     */
    public static function getNumberOfUsers(array $conditions): int
    {
        $conditions['get_count'] = true;

        return self::getUserData(0, 0, 0, '', $conditions);
    }

    /**
     * Get data for users list in sortable with pagination.
     */
    public static function getUserData(
        $from,
        $numberOfItems,
        $column,
        $direction,
        array $conditions = [],
        bool $exerciseToCheckConfig = true,
        bool $displaySessionInfo = false,
        ?string $courseCode = null,
        ?int $sessionId = null,
        bool $exportCsv = false,
        array $userIds = []
    ) {
        $includeInvitedUsers = $conditions['include_invited_users'] ?? false;
        $getCount = $conditions['get_count'] ?? false;

        $csvContent = [];
        $tblUser = Database::get_main_table(TABLE_MAIN_USER);
        $tblUrlRelUser = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $accessUrlId = api_get_current_access_url_id();

        if (!empty($userIds) && is_array($userIds)) {
            $userIds = array_map('intval', $userIds);
            $conditionUser = " WHERE user.id IN (".implode(',', $userIds).") ";
        } else {
            $conditionUser = " WHERE user.id = " . (int) $userIds;
        }

        if (!empty($_GET['user_keyword'])) {
            $keyword = trim(Database::escape_string($_GET['user_keyword']));
            $conditionUser .= " AND (
            user.firstname LIKE '%".$keyword."%' OR
            user.lastname LIKE '%".$keyword."%'  OR
            user.username LIKE '%".$keyword."%'  OR
            user.email LIKE '%".$keyword."%'
         ) ";
        }

        $urlTable = '';
        $urlCondition = '';
        if (api_is_multiple_url_enabled()) {
            $urlTable = " INNER JOIN $tblUrlRelUser as url_users ON (user.id = url_users.user_id)";
            $urlCondition = " AND access_url_id = '$accessUrlId'";
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
            FROM $tblUser as user
            $urlTable
            $sqlInjectJoins
            $conditionUser
            $urlCondition
            $invitedUsersCondition
            $where
            $sqlInjectWhere
            ";

        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }

        $column = $column <= 2 ? (int) $column : 0;
        $from = (int) $from;
        $numberOfItems = (int) $numberOfItems;

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
        $sql .= " LIMIT $from, $numberOfItems";

        $res = Database::query($sql);
        $users = [];

        $courseInfo = api_get_course_info($courseCode);
        $courseId = $courseInfo['real_id'];

        $totalSurveys = 0;
        $totalExercises = ExerciseLib::get_all_exercises(
            $courseInfo,
            $sessionId
        );

        if (empty($sessionId)) {
            $surveyUserList = [];
            $surveyList = SurveyManager::get_surveys($courseCode);
            if ($surveyList) {
                $totalSurveys = count($surveyList);
                foreach ($surveyList as $survey) {
                    $userList = SurveyManager::get_people_who_filled_survey(
                        $survey['survey_id'],
                        false,
                        $courseId
                    );

                    foreach ($userList as $user_id) {
                        isset($surveyUserList[$user_id]) ? $surveyUserList[$user_id]++ : $surveyUserList[$user_id] = 1;
                    }
                }
            }
        }

        $urlBase = api_get_path(WEB_CODE_PATH).'my_space/myStudents.php?details=true&cid='.$courseId.
            '&course='.$courseCode.'&origin=tracking_course&sid='.$sessionId;

        Session::write('user_id_list', []);
        $userIdList = [];

        if ($exerciseToCheckConfig) {
            $addExerciseOption = api_get_setting('exercise.add_exercise_best_attempt_in_report', true);
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
        }

        $lpShowMaxProgress = 'true' === api_get_setting('lp.lp_show_max_progress_instead_of_average');
        if ('true' === api_get_setting('lp.lp_show_max_progress_or_average_enable_course_level_redefinition')) {
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
                    $sessionId
                )
            );

            $avgStudentScore = Tracking::get_avg_student_score(
                $user['user_id'],
                api_get_course_entity($courseId),
                [],
                api_get_session_entity($sessionId)
            );

            $averageBestScore = Tracking::get_avg_student_score(
                $user['user_id'],
                api_get_course_entity($courseId),
                [],
                api_get_session_entity($sessionId),
                false,
                false,
                true
            );

            $avgStudentProgress = Tracking::get_avg_student_progress(
                $user['user_id'],
                api_get_course_entity($courseId),
                [],
                api_get_session_entity($sessionId)
            );

            if (empty($avgStudentProgress)) {
                $avgStudentProgress = 0;
            }
            $user['average_progress'] = $avgStudentProgress.'%';

            $totalUserExercise = Tracking::get_exercise_student_progress(
                $totalExercises,
                $user['user_id'],
                $courseId,
                $sessionId
            );

            $user['exercise_progress'] = $totalUserExercise;

            $totalUserExercise = Tracking::get_exercise_student_average_best_attempt(
                $totalExercises,
                $user['user_id'],
                $courseId,
                $sessionId
            );

            $user['exercise_average_best_attempt'] = $totalUserExercise;

            if (is_numeric($avgStudentScore)) {
                $user['student_score'] = $avgStudentScore.'%';
            } else {
                $user['student_score'] = $avgStudentScore;
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
                        $sessionId,
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

            $user['first_connection'] = Tracking::get_first_connection_date_on_the_course(
                $user['user_id'],
                $courseId,
                $sessionId,
                !$exportCsv
            );

            $user['last_connection'] = Tracking::get_last_connection_date_on_the_course(
                $user['user_id'],
                $courseInfo,
                $sessionId,
                !$exportCsv
            );


            $user['count_assignments'] = Tracking::countStudentPublications(
                $courseId,
                $sessionId
            );

            $user['count_messages'] = Tracking::countStudentMessages(
                $courseId,
                $sessionId
            );


            $user['lp_finalization_date'] = Tracking::getCourseLpFinalizationDate(
                $user['user_id'],
                $courseId,
                $sessionId,
                !$exportCsv
            );

            $user['quiz_finalization_date'] = Tracking::getCourseQuizLastFinalizationDate(
                $user['user_id'],
                $courseId,
                $sessionId,
                !$exportCsv
            );

            if ($exportCsv) {
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
                if (!empty($user['lp_finalization_date'])) {
                    $user['lp_finalization_date'] = api_get_local_time($user['lp_finalization_date']);
                } else {
                    $user['lp_finalization_date'] = '-';
                }
                if (!empty($user['quiz_finalization_date'])) {
                    $user['quiz_finalization_date'] = api_get_local_time($user['quiz_finalization_date']);
                } else {
                    $user['quiz_finalization_date'] = '-';
                }
            }

            if (empty($sessionId)) {
                $user['survey'] = ($surveyUserList[$user['user_id']] ?? 0).' / '.$totalSurveys;
            }

            $url = $urlBase.'&student='.$user['user_id'];

            $user['link'] = '<a href="'.$url.'">
                        '.Display::return_icon('2rightarrow.png', get_lang('Details')).'
                         </a>';

            // store columns in array $users
            $userRow = [];
            if ($displaySessionInfo && !empty($sessionId)) {
                $sessionInfo = api_get_session_info($sessionId);
                $userRow['session_name'] = $sessionInfo['name'];
                $userRow['session_startdate'] = $sessionInfo['access_start_date'];
                $userRow['session_enddate'] = $sessionInfo['access_end_date'];
                $userRow['course_name'] = $courseInfo['name'];
            }
            $userRow['official_code'] = $user['official_code'];
            if ($sortByFirstName) {
                $userRow['firstname'] = $user['col2'];
                $userRow['lastname'] = $user['col1'];
            } else {
                $userRow['lastname'] = $user['col1'];
                $userRow['firstname'] = $user['col2'];
            }
            $userRow['username'] = $user['username'];
            $userRow['time'] = $user['time'];
            $userRow['average_progress'] = $user['average_progress'];
            $userRow['exercise_progress'] = $user['exercise_progress'];
            $userRow['exercise_average_best_attempt'] = $user['exercise_average_best_attempt'];
            $userRow['student_score'] = $user['student_score'];
            $userRow['student_score_best'] = $user['student_score_best'];
            if (!empty($exerciseResults)) {
                foreach ($exerciseResults as $exerciseId => $bestResult) {
                    $userRow[$exerciseId] = $bestResult;
                }
            }

            $userRow['count_assignments'] = $user['count_assignments'];
            $userRow['count_messages'] = $user['count_messages'];

            $userGroupManager = new UserGroupModel();
            if ($exportCsv) {
                $userRow['classes'] = implode(
                    ',',
                    $userGroupManager->getNameListByUser($user['user_id'], UserGroupModel::NORMAL_CLASS)
                );
            } else {
                $userRow['classes'] = $userGroupManager->getLabelsFromNameList(
                    $user['user_id'],
                    UserGroupModel::NORMAL_CLASS
                );
            }

            if (empty($sessionId)) {
                $userRow['survey'] = $user['survey'];
            } else {
                $userSession = SessionManager::getUserSession($user['user_id'], $sessionId);
                $userRow['registered_at'] = '';
                if ($userSession) {
                    $userRow['registered_at'] = api_get_local_time($userSession['registered_at']);
                }
            }

            $userRow['first_connection'] = $user['first_connection'];
            $userRow['last_connection'] = $user['last_connection'];

            $userRow['lp_finalization_date'] = $user['lp_finalization_date'];
            $userRow['quiz_finalization_date'] = $user['quiz_finalization_date'];

            // we need to display an additional profile field
            if (isset($_GET['additional_profile_field'])) {
                $data = Session::read('additional_user_profile_info');

                $extraFieldInfo = Session::read('extra_field_info');
                foreach ($_GET['additional_profile_field'] as $fieldId) {
                    if (isset($data[$fieldId]) && isset($data[$fieldId][$user['user_id']])) {
                        if (is_array($data[$fieldId][$user['user_id']])) {
                            $userRow[$extraFieldInfo[$fieldId]['variable']] = implode(
                                ', ',
                                $data[$fieldId][$user['user_id']]
                            );
                        } else {
                            $userRow[$extraFieldInfo[$fieldId]['variable']] = $data[$fieldId][$user['user_id']];
                        }
                    } else {
                        $userRow[$extraFieldInfo[$fieldId]['variable']] = '';
                    }
                }
            }

            $data = Session::read('default_additional_user_profile_info');
            $defaultExtraFieldInfo = Session::read('default_extra_field_info');
            if (isset($defaultExtraFieldInfo) && isset($data)) {
                foreach ($data as $key => $val) {
                    if (isset($val[$user['user_id']])) {
                        if (is_array($val[$user['user_id']])) {
                            $userRow[$defaultExtraFieldInfo[$key]['variable']] = implode(
                                ', ',
                                $val[$user['user_id']]
                            );
                        } else {
                            $userRow[$defaultExtraFieldInfo[$key]['variable']] = $val[$user['user_id']];
                        }
                    } else {
                        $userRow[$defaultExtraFieldInfo[$key]['variable']] = '';
                    }
                }
            }

            if (api_get_setting('show_email_addresses') === 'true') {
                $userRow['email'] = $user['col4'];
            }

            $userRow['link'] = $user['link'];

            if ($exportCsv) {
                unset($userRow['link']);
                $csvContent[] = $userRow;
            }
            $users[] = array_values($userRow);
        }

        if ($exportCsv) {
            Session::write('csv_content', $csvContent);
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
        $numberOfItems,
        $column,
        $direction,
        bool $includeInvitedUsers = false
    ): array {
        global $user_ids, $course_code, $export_csv, $session_id;

        $course_code = Database::escape_string($course_code);
        $tblUser = Database::get_main_table(TABLE_MAIN_USER);
        $tblUrlRelUser = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $accessUrlId = api_get_current_access_url_id();

        // get all users data from a course for sortable with limit
        if (is_array($user_ids)) {
            $user_ids = array_map('intval', $user_ids);
            $conditionUser = " WHERE user.user_id IN (".implode(',', $user_ids).") ";
        } else {
            $user_ids = intval($user_ids);
            $conditionUser = " WHERE user.user_id = $user_ids ";
        }

        $urlTable = null;
        $urlCondition = null;
        if (api_is_multiple_url_enabled()) {
            $urlTable = ", ".$tblUrlRelUser." as url_users";
            $urlCondition = " AND user.user_id = url_users.user_id AND access_url_id='$accessUrlId'";
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
                FROM $tblUser as user $urlTable
                $conditionUser $urlCondition $invitedUsersCondition";

        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }

        $column = (int) $column;
        $from = (int) $from;
        $numberOfItems = (int) $numberOfItems;

        $sql .= " ORDER BY col$column $direction ";
        $sql .= " LIMIT $from,$numberOfItems";

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
                api_get_course_entity($courseId),
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
                             <a href="../my_space/myStudents.php?student='.$user['user_id'].'&details=true&cid='.$courseId.'&sid='.$session_id.'&course='.$course_code.'&origin=tracking_course&id_session='.$session_id.'">
                             '.Display::return_icon('2rightarrow.png', get_lang('Details')).'
                             </a>
                         </center>';

            // store columns in array $users
            $userRow = [];
            $userRow['official_code'] = $user['official_code']; //0
            if ($sortByFirstName) {
                $userRow['firstname'] = $user['firstname'];
                $userRow['lastname'] = $user['lastname'];
            } else {
                $userRow['lastname'] = $user['lastname'];
                $userRow['firstname'] = $user['firstname'];
            }
            $userRow['username'] = $user['username'];
            $userRow['time'] = $user['time'];
            $userRow['total_lp_time'] = $user['total_lp_time'];
            $userRow['first_connection'] = $user['first_connection'];
            $userRow['last_connection'] = $user['last_connection'];

            $userRow['link'] = $user['link'];
            $users[] = array_values($userRow);
        }

        return $users;
    }

    /**
     * Determines the remaining actions for a session and returns a string with the results.
     */
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
                Display::return_icon('attendance_list.png', get_lang('Logins'), [], ICON_SIZE_MEDIUM),
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
                        Display::return_icon('attendance_list.png', get_lang('Logins'), [], ICON_SIZE_MEDIUM),
                        '#'
                    );
                }
                break;
            case 'lp':
                $lpLink = Display::url(
                    Display::return_icon(
                        'scorms_na.png',
                        get_lang('CourseLearningPathsGenericStats'),
                        [],
                        ICON_SIZE_MEDIUM
                    ),
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
