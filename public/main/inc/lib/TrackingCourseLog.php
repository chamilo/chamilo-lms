<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField as EntityExtraField;
use Chamilo\CoreBundle\Enums\ToolIcon;
use ChamiloSession as Session;

class TrackingCourseLog
{
    /**
     * Counts course resources using resource_link / resource_node.
     */
    public static function countItemResources(): mixed
    {
        $sessionId = api_get_session_id();
        $courseId  = api_get_course_int_id();

        $tableUser         = Database::get_main_table(TABLE_MAIN_USER);
        $tableSession      = Database::get_main_table(TABLE_MAIN_SESSION);
        $tableResourceLink = Database::get_main_table('resource_link');
        $tableResourceNode = Database::get_main_table('resource_node');
        $tableResourceType = Database::get_main_table('resource_type');

        // Resource types we want to see in this report.
        $allowedTypes = [
            'files',
            'lps',
            'exercises',
            'glossaries',
            'links',
            'course_descriptions',
            'announcements',
            'thematics',
            'thematic_advance',
            'thematic_plan',
        ];
        $typesList = "'" . implode("','", $allowedTypes) . "'";

        $sql = "SELECT COUNT(*) AS total_number_of_items
            FROM $tableResourceLink rl
            INNER JOIN $tableResourceNode rn ON rn.id = rl.resource_node_id
            INNER JOIN $tableResourceType rt ON rt.id = rn.resource_type_id
            LEFT JOIN $tableUser u ON u.id = rn.creator_id
            LEFT JOIN $tableSession s ON s.id = rl.session_id
            WHERE rl.c_id = $courseId";

        if (empty($sessionId)) {
            $sql .= ' AND rl.session_id IS NULL';
        } else {
            $sessionId = (int) $sessionId;
            $sql .= " AND rl.session_id = $sessionId";
        }

        $sql .= " AND rt.title IN ($typesList)";

        if (!empty($_GET['keyword'])) {
            $keyword = Database::escape_string(trim((string) $_GET['keyword']));
            $sql .= " AND (
            u.username LIKE '%$keyword%' OR
            rn.title   LIKE '%$keyword%' OR
            rt.title   LIKE '%$keyword%'
        )";
        }

        $res = Database::query($sql);
        $obj = Database::fetch_object($res);

        return $obj ? (int) $obj->total_number_of_items : 0;
    }

    /**
     * Retrieves resource log data using resource_link/resource_node.
     */
    public static function getItemResourcesData($from, $numberOfItems, $column, $direction): array
    {
        $sessionId = api_get_session_id();
        $courseId  = api_get_course_int_id();

        $tableUser         = Database::get_main_table(TABLE_MAIN_USER);
        $tableSession      = Database::get_main_table(TABLE_MAIN_SESSION);
        $tableSessionUser  = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $tableResourceLink = Database::get_main_table('resource_link');
        $tableResourceNode = Database::get_main_table('resource_node');
        $tableResourceType = Database::get_main_table('resource_type');

        $column    = (int) $column;
        $direction = strtolower(trim((string) $direction));
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'asc';

        // Resource types we want to see in this report.
        $allowedTypes = [
            'files',
            'lps',
            'exercises',
            'glossaries',
            'links',
            'course_descriptions',
            'announcements',
            'thematics',
            'thematic_advance',
            'thematic_plan',
        ];
        $typesList = "'" . implode("','", $allowedTypes) . "'";

        $sql = "SELECT
                rl.id            AS ref,
                rl.created_at    AS col6,
                rl.visibility    AS col7,
                rn.title         AS document_title,
                rt.title         AS resource_type_title,
                creator.id       AS user_id,
                creator.username AS col3,
                s.title           AS session_name,
                coach.username   AS coach_username
            FROM $tableResourceLink rl
            INNER JOIN $tableResourceNode rn ON rn.id = rl.resource_node_id
            INNER JOIN $tableResourceType rt ON rt.id = rn.resource_type_id
            LEFT JOIN $tableUser creator ON creator.id = rn.creator_id
            LEFT JOIN $tableSession s ON s.id = rl.session_id
            LEFT JOIN $tableSessionUser su ON su.session_id = rl.session_id
            LEFT JOIN $tableUser coach ON coach.id = su.user_id
            WHERE rl.c_id = $courseId";

        if (empty($sessionId)) {
            $sql .= ' AND rl.session_id IS NULL';
        } else {
            $sessionId = (int) $sessionId;
            $sql .= " AND rl.session_id = $sessionId";
        }

        $sql .= " AND rt.title IN ($typesList)";

        if (!empty($_GET['keyword'])) {
            $keyword = Database::escape_string(trim((string) $_GET['keyword']));
            $sql .= " AND (
                creator.username LIKE '%$keyword%' OR
                rn.title         LIKE '%$keyword%' OR
                rt.title         LIKE '%$keyword%'
            )";
        }

        // Decide ORDER BY based on requested column.
        switch ($column) {
            case 0: // Tool
                $orderBy = 'rt.title';
                break;
            case 2: // Session
                $orderBy = 's.title';
                break;
            case 3: // Username
                $orderBy = 'creator.username';
                break;
            case 5: // Document
                $orderBy = 'rn.title';
                break;
            case 6: // Date
            default:
                $orderBy = 'rl.created_at';
                break;
        }

        $sql .= " ORDER BY $orderBy $direction";

        $from = (int) $from;
        if ($from) {
            $numberOfItems = (int) $numberOfItems;
            $sql .= " LIMIT $from, $numberOfItems";
        }

        $res       = Database::query($sql);
        $resources = [];

        while ($row = Database::fetch_array($res)) {
            $legacyTool = self::mapResourceTypeTitleToLegacyTool((string) $row['resource_type_title']);

            if (null === $legacyTool) {
                // Ignore resource types that do not map to a legacy tool.
                continue;
            }

            // Build a clean row so SortableTable only sees columns 0..6.
            $displayRow = [];

            // Internal legacy columns used by CSV/XLS export.
            $displayRow['ref']                 = (int) $row['ref'];
            $displayRow['col6']                = $row['col6']; // created_at
            $displayRow['col7']                = (int) $row['col7']; // visibility
            $displayRow['user_id']             = isset($row['user_id']) ? (int) $row['user_id'] : 0;
            $displayRow['col3']                = $row['col3']; // username
            $displayRow['document_title']      = $row['document_title'] ?? '';
            $displayRow['resource_type_title'] = $row['resource_type_title'];
            $displayRow['session_name']        = $row['session_name'] ?? '';
            $displayRow['coach_username']      = $row['coach_username'] ?? '';
            $displayRow['col0']                = $legacyTool;
            $displayRow['col1']                = 'Created';

            // Column 0: Tool.
            $displayRow[0] = get_lang('Tool'.api_ucfirst($legacyTool));

            // Column 1: Event type.
            $displayRow[1] = get_lang($displayRow['col1']);

            // Column 2: Session + coach.
            $sessionText = '';
            if (!empty($displayRow['session_name'])) {
                $sessionText = $displayRow['session_name'];
                if (!empty($displayRow['coach_username'])) {
                    $sessionText .= '<br />'.get_lang('Coach').': '.$displayRow['coach_username'];
                }
            }
            $displayRow[2] = $sessionText;

            // Column 3: Username (linked to profile).
            $displayRow[3] = '';
            if (!empty($displayRow['col3']) && !empty($displayRow['user_id'])) {
                $userInfo          = api_get_user_info($displayRow['user_id']);
                $displayRow['col3'] = Display::url($displayRow['col3'], $userInfo['profile_url']);
                $displayRow[3]      = $displayRow['col3'];
            }

            // Column 4: IP address.
            $ip = '';
            if (!empty($displayRow['user_id']) && !empty($displayRow['col6'])) {
                $ip = Tracking::get_ip_from_user_event(
                    (int) $displayRow['user_id'],
                    $displayRow['col6'],
                    true
                );
            }
            if (empty($ip)) {
                $ip = get_lang('Unknown');
            }
            $displayRow[4] = $ip;

            // Column 5: Document title.
            $displayRow[5] = $displayRow['document_title'];

            // Column 6: Date.
            $displayRow[6] = api_convert_and_format_date(
                $displayRow['col6'],
                null,
                date_default_timezone_get()
            );

            $resources[] = $displayRow;
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
        $return .= '<option value="-">'.get_lang('Select user profile field to add').'</option>';
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
            .get_lang('Add user profile field').'</button>';
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
     *
     * @param int         $from
     * @param int         $numberOfItems
     * @param int         $column
     * @param string      $direction
     * @param array       $conditions
     * @param bool        $exerciseToCheckConfig
     * @param bool        $displaySessionInfo
     * @param string|null $courseCode
     * @param int|null    $sessionId
     * @param bool        $exportCsv
     * @param array       $userIds
     *
     * @return array
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
        $getCount            = $conditions['get_count'] ?? false;

        $csvContent     = [];
        $tblUser        = Database::get_main_table(TABLE_MAIN_USER);
        $tblUrlRelUser  = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $accessUrlId    = api_get_current_access_url_id();

        // ---------------------------------------------------------------------
        // Resolve course / session context if not explicitly provided
        // ---------------------------------------------------------------------
        if ($sessionId === null) {
            $sessionId = (int) api_get_session_id();
        }

        if (empty($courseCode)) {
            $courseInfo = api_get_course_info(); // current course
        } else {
            $courseInfo = api_get_course_info($courseCode);
        }

        if (empty($courseInfo)) {
            // Failsafe: no course context, nothing to show
            return [];
        }

        $courseId   = (int) $courseInfo['real_id'];
        $courseCode = $courseInfo['code'] ?? $courseCode;

        // ---------------------------------------------------------------------
        // Build user filter (single user vs list of users)
        // ---------------------------------------------------------------------
        if (!empty($userIds) && is_array($userIds)) {
            $userIds      = array_map('intval', $userIds);
            $conditionUser = ' WHERE user.id IN ('.implode(',', $userIds).') ';
        } else {
            $conditionUser = ' WHERE user.id = '.(int) $userIds.' ';
        }

        // Simple keyword filter
        if (!empty($_GET['user_keyword'])) {
            $keyword       = trim(Database::escape_string($_GET['user_keyword']));
            $conditionUser .= " AND (
                user.firstname LIKE '%".$keyword."%' OR
                user.lastname  LIKE '%".$keyword."%'  OR
                user.username  LIKE '%".$keyword."%'  OR
                user.email     LIKE '%".$keyword."%'
            ) ";
        }

        // Multiple URL restriction
        $urlTable     = '';
        $urlCondition = '';
        if (api_is_multiple_url_enabled()) {
            $urlTable     = " INNER JOIN $tblUrlRelUser AS url_users ON (user.id = url_users.user_id)";
            $urlCondition = " AND access_url_id = '$accessUrlId'";
        }

        // Exclude invited users if needed
        $invitedUsersCondition = '';
        if (!$includeInvitedUsers) {
            $invitedUsersCondition = ' AND user.status != '.INVITEE;
        }

        // ---------------------------------------------------------------------
        // Base SELECT
        // ---------------------------------------------------------------------
        $select = '
            SELECT user.id          AS user_id,
                   user.official_code AS col0,
                   user.lastname      AS col1,
                   user.firstname     AS col2,
                   user.username      AS col3,
                   user.email         AS col4';

        if ($getCount) {
            $select = 'SELECT COUNT(DISTINCT(user.id)) AS count';
        }

        // Extra joins / where from conditions (classes, extra fields, etc.)
        $sqlInjectJoins = '';
        $where          = 'AND 1 = 1 ';
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

            $injectExtraFields = $conditions['inject_extra_fields'] ?? 1;
            $injectExtraFields = rtrim($injectExtraFields, ', ');
            if (false === $getCount) {
                $select .= " , $injectExtraFields";
            }
        }

        $sql = "$select
            FROM $tblUser AS user
            $urlTable
            $sqlInjectJoins
            $conditionUser
            $urlCondition
            $invitedUsersCondition
            $where
            $sqlInjectWhere
        ";

        // ---------------------------------------------------------------------
        // Sorting / limits
        // ---------------------------------------------------------------------
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            $direction = 'ASC';
        }

        $column        = (int) $column;
        $from          = (int) $from;
        $numberOfItems = (int) $numberOfItems;

        if ($getCount) {
            $res = Database::query($sql);
            $row = Database::fetch_array($res);

            return (int) $row['count'];
        }

        $sortByFirstName = api_sort_by_first_name();
        if ($sortByFirstName) {
            // Invert columns 1/2 if we sort by firstname
            if (1 === $column) {
                $column = 2;
            } elseif (2 === $column) {
                $column = 1;
            }
        }

        $sql .= " ORDER BY col$column $direction ";
        $sql .= " LIMIT $from, $numberOfItems";

        $res   = Database::query($sql);
        $users = [];

        // ---------------------------------------------------------------------
        // Course data required for progress / scores
        // ---------------------------------------------------------------------
        $totalSurveys  = 0;
        $totalExercises = ExerciseLib::get_all_exercises(
            $courseInfo,
            $sessionId
        );

        // Preload survey info only when we are outside a session
        $surveyUserList = [];
        if (empty($sessionId)) {
            $courseCodeForSurvey = $courseCode;
            $surveyList          = [];

            if (!empty($courseCodeForSurvey)) {
                $surveyList = SurveyManager::get_surveys($courseCodeForSurvey);
            }

            if (!empty($surveyList)) {
                $totalSurveys = count($surveyList);

                foreach ($surveyList as $survey) {
                    if (!is_array($survey)) {
                        continue;
                    }

                    // Support both "survey_id" and "id"
                    $surveyId = $survey['survey_id'] ?? ($survey['id'] ?? null);
                    $surveyId = (int) $surveyId;

                    if ($surveyId <= 0) {
                        continue;
                    }

                    $userList = SurveyManager::get_people_who_filled_survey(
                        $surveyId,
                        false,
                        $courseId
                    );

                    foreach ($userList as $userId) {
                        if (isset($surveyUserList[$userId])) {
                            $surveyUserList[$userId]++;
                        } else {
                            $surveyUserList[$userId] = 1;
                        }
                    }
                }
            }
        }

        $urlBase = api_get_path(WEB_CODE_PATH).'my_space/myStudents.php?details=true'
            .'&cid='.$courseId
            .'&course='.$courseCode
            .'&origin=tracking_course'
            .'&sid='.$sessionId;

        Session::write('user_id_list', []);
        $userIdList = [];

        // Exercises to show as extra columns (best attempt)
        $exerciseResultsToCheck = [];
        if ($exerciseToCheckConfig) {
            $addExerciseOption = api_get_setting('exercise.add_exercise_best_attempt_in_report', true);
            if (!empty($addExerciseOption)
                && isset($addExerciseOption['courses'], $addExerciseOption['courses'][$courseCode])
            ) {
                foreach ($addExerciseOption['courses'][$courseCode] as $exerciseId) {
                    $exercise = new Exercise();
                    $exercise->read($exerciseId);
                    if (!empty($exercise->iid)) {
                        $exerciseResultsToCheck[] = $exercise;
                    }
                }
            }
        }

        $lpShowMaxProgress = 'true' === api_get_setting('lp.lp_show_max_progress_instead_of_average');
        if ('true' === api_get_setting('lp.lp_show_max_progress_or_average_enable_course_level_redefinition')) {
            $lpShowProgressCourseSetting = api_get_course_setting(
                'lp_show_max_or_average_progress',
                $courseInfo,
                true
            );
            if (in_array($lpShowProgressCourseSetting, ['max', 'average'], true)) {
                $lpShowMaxProgress = ('max' === $lpShowProgressCourseSetting);
            }
        }

        // ---------------------------------------------------------------------
        // Main per-user loop
        // ---------------------------------------------------------------------
        while ($user = Database::fetch_array($res, 'ASSOC')) {
            $userIdList[]          = $user['user_id'];
            $user['official_code'] = $user['col0'];
            $user['username']      = $user['col3'];

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

            $user['student_score'] = is_numeric($avgStudentScore)
                ? $avgStudentScore.'%'
                : $avgStudentScore;

            $user['student_score_best'] = is_numeric($averageBestScore)
                ? $averageBestScore.'%'
                : $averageBestScore;

            // Extra specific exercises as columns
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
                $user['first_connection']       = !empty($user['first_connection'])
                    ? api_get_local_time($user['first_connection'])
                    : '-';
                $user['last_connection']        = !empty($user['last_connection'])
                    ? api_get_local_time($user['last_connection'])
                    : '-';
                $user['lp_finalization_date']   = !empty($user['lp_finalization_date'])
                    ? api_get_local_time($user['lp_finalization_date'])
                    : '-';
                $user['quiz_finalization_date'] = !empty($user['quiz_finalization_date'])
                    ? api_get_local_time($user['quiz_finalization_date'])
                    : '-';
            }

            if (empty($sessionId)) {
                $filled = $surveyUserList[$user['user_id']] ?? 0;
                $user['survey'] = $totalSurveys > 0
                    ? $filled.' / '.$totalSurveys
                    : '0 / 0';
            }

            $url        = $urlBase.'&student='.$user['user_id'];
            $user['link'] = '<a href="'.$url.'">
                    '.Display::return_icon('2rightarrow.png', get_lang('Details')).'
                 </a>';

            // -------------------------------------------------------------
            // Build final row
            // -------------------------------------------------------------
            $userRow = [];
            if ($displaySessionInfo && !empty($sessionId)) {
                $sessionInfo                   = api_get_session_info($sessionId);
                $userRow['session_name']       = $sessionInfo['name'];
                $userRow['session_startdate']  = $sessionInfo['access_start_date'];
                $userRow['session_enddate']    = $sessionInfo['access_end_date'];
                $userRow['course_name']        = $courseInfo['name'];
            }

            $userRow['official_code'] = $user['official_code'];
            if ($sortByFirstName) {
                $userRow['firstname'] = $user['col2'];
                $userRow['lastname']  = $user['col1'];
            } else {
                $userRow['lastname']  = $user['col1'];
                $userRow['firstname'] = $user['col2'];
            }
            $userRow['username']                     = $user['username'];
            $userRow['time']                         = $user['time'];
            $userRow['average_progress']             = $user['average_progress'];
            $userRow['exercise_progress']            = $user['exercise_progress'];
            $userRow['exercise_average_best_attempt']= $user['exercise_average_best_attempt'];
            $userRow['student_score']                = $user['student_score'];
            $userRow['student_score_best']           = $user['student_score_best'];

            if (!empty($exerciseResults)) {
                foreach ($exerciseResults as $exerciseId => $bestResult) {
                    $userRow[$exerciseId] = $bestResult;
                }
            }

            $userRow['count_assignments'] = $user['count_assignments'];
            $userRow['count_messages']    = $user['count_messages'];

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
                $userSession              = SessionManager::getUserSession($user['user_id'], $sessionId);
                $userRow['registered_at'] = '';
                if ($userSession) {
                    $userRow['registered_at'] = api_get_local_time($userSession['registered_at']);
                }
            }

            $userRow['first_connection']      = $user['first_connection'];
            $userRow['last_connection']       = $user['last_connection'];
            $userRow['lp_finalization_date']  = $user['lp_finalization_date'];
            $userRow['quiz_finalization_date']= $user['quiz_finalization_date'];

            // Extra profile fields selected by the teacher
            if (isset($_GET['additional_profile_field'])) {
                $data          = Session::read('additional_user_profile_info');
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

            $data                  = Session::read('default_additional_user_profile_info');
            $defaultExtraFieldInfo = Session::read('default_extra_field_info');
            if (!empty($defaultExtraFieldInfo) && !empty($data)) {
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
                $warning = '&nbsp;'.Display::label(get_lang('Time difference'), 'danger');
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
    public static function actionsLeft(
        string $current,
        int $sessionId = 0,
        bool $showExtended = false
    ): string {
        // Keep all course/session params consistent across tabs
        $cidReq      = api_get_cidreq(true, false);
        $cidQuery    = $cidReq ? ('?'.$cidReq) : '';
        $webCodePath = api_get_path(WEB_CODE_PATH);

        $items = [
            'users' => [
                'icon'  => ToolIcon::MEMBER,
                'label' => get_lang('Report on learners'),
                'url'   => 'courseLog.php'.$cidQuery,
            ],
            'groups' => [
                'icon'  => ToolIcon::GROUP,
                'label' => get_lang('Group reporting'),
                'url'   => 'course_log_groups.php'.$cidQuery,
            ],
            'resources' => [
                'icon'  => ToolIcon::DOCUMENT,
                'label' => get_lang('Report on resources'),
                'url'   => 'course_log_resources.php'.$cidQuery,
            ],
            'courses' => [
                'icon'  => ToolIcon::COURSE,
                'label' => get_lang('Course report'),
                'url'   => 'course_log_tools.php'.$cidQuery,
            ],
            'exams' => [
                'icon'  => ToolIcon::QUIZ,
                'label' => get_lang('Exam tracking'),
                'url'   => $webCodePath.'tracking/exams.php'.$cidQuery,
            ],
            'logs' => [
                'icon'  => ToolIcon::SECURITY,
                'label' => get_lang('Audit report'),
                'url'   => $webCodePath.'tracking/course_log_events.php'.$cidQuery,
            ],
            'lp' => [
                'icon'  => ToolIcon::LP,
                'label' => get_lang('Learning paths generic stats'),
                'url'   => $webCodePath.'tracking/lp_report.php'.$cidQuery,
            ],
        ];

        if (!empty($sessionId)) {
            $items['attendance'] = [
                'icon'  => ToolIcon::ATTENDANCE,
                'label' => get_lang('Logins'),
                'url'   => $webCodePath.'attendance/index.php'.$cidQuery.'&action=calendar_logins',
            ];
        }

        // ---------------------------------------------------------------------
        // Hide tabs that require a single course context when there is no course
        // or when we are in "global" reporting mode (showExtended = true).
        // This avoids linking to courseLog.php which will block with api_not_allowed().
        // ---------------------------------------------------------------------
        $hasCourse = null !== api_get_course_entity();
        $isGlobalContext = $showExtended || !$hasCourse;

        if ($isGlobalContext) {
            unset($items['users'], $items['lp']);
        }

        $links = [];

        foreach ($items as $key => $config) {
            $isCurrent = ($key === $current);

            // Icon inside the pill
            $iconHtml = Display::getMdiIcon(
                $config['icon'],
                'ch-tool-icon course-log-tab-icon',
                null,
                ICON_SIZE_SMALL,
                $config['label']
            );

            // Base classes for tab look & feel
            $tabClass = 'course-log-tab inline-flex items-center gap-2 px-3 py-1 rounded-full transition ';

            if ($isCurrent) {
                // Active pill
                $tabClass .= 'admin-report-card-active text-gray-90 shadow-sm';
            } else {
                // Inactive pill
                $tabClass .= 'text-gray-60 hover:bg-gray-15 hover:text-gray-90';
            }

            $attrs = [
                'class' => $tabClass,
                'title' => $config['label'],
            ];

            $href = $isCurrent ? '#' : $config['url'];

            $links[] = Display::url(
                $iconHtml.'<span>'.Security::remove_XSS($config['label']).'</span>',
                $href,
                $attrs
            );
        }

        // Horizontal pill container (tabs)
        return
            '<nav class="course-log-nav inline-flex flex-wrap items-center gap-1 '.
            'rounded-full bg-gray-10 border border-gray-25 px-1 py-1 text-body-2">'.
            implode('', $links).
            '</nav>';
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
            return sprintf(get_lang('%s %%'), $rounded);
        }

        return $rounded;
    }

    /**
     * Map resource_type.title to legacy "tool" identifier used in old tracking code.
     */
    private static function mapResourceTypeTitleToLegacyTool(string $title): ?string
    {
        static $map = [
            'files'               => 'document',
            'lps'                 => 'learnpath',
            'exercises'           => 'quiz',
            'glossaries'          => 'glossary',
            'links'               => 'link',
            'course_descriptions' => 'course_description',
            'announcements'       => 'announcement',
            'thematics'           => 'thematic',
            'thematic_advance'    => 'thematic_advance',
            'thematic_plan'       => 'thematic_plan',
        ];

        $title = trim($title);

        return $map[$title] ?? null;
    }

    public static function getAdditionalProfileExtraFields(): array
    {
        $additionalProfileField = $_GET['additional_profile_field'] ?? [];

        $additionalExtraFieldsInfo = [];

        $objExtraField = new ExtraField('user');

        foreach ($additionalProfileField as $fieldId) {
            $additionalExtraFieldsInfo[$fieldId] = $objExtraField->getFieldInfoByFieldId($fieldId);
        }

        return $additionalExtraFieldsInfo;
    }
}
