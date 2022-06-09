<?php

/* For licensing terms, see /license.txt */

/**
 * Class ExerciseResult
 * which allows you to export exercises results in multiple presentation forms.
 *
 * @author Yannick Warnier
 */
class ExerciseResult
{
    public $includeAllUsers = false;
    public $onlyBestAttempts = false;
    private $results = [];

    /**
     * @param bool $includeAllUsers
     */
    public function setIncludeAllUsers($includeAllUsers)
    {
        $this->includeAllUsers = $includeAllUsers;
    }

    /**
     * @param bool $value
     */
    public function setOnlyBestAttempts($value)
    {
        $this->onlyBestAttempts = $value;
    }

    /**
     * Gets the results of all students (or just one student if access is limited).
     *
     * @param string $document_path The document path (for HotPotatoes retrieval)
     * @param int    $user_id       User ID. Optional. If no user ID is provided, we take all the results. Defauts to null
     * @param int    $filter
     * @param int    $exercise_id
     *
     * @return bool
     */
    public function getExercisesReporting(
        $document_path,
        $user_id = null,
        $filter = 0,
        $exercise_id = 0
    ) {
        $return = [];
        $TBL_EXERCISES = Database::get_course_table(TABLE_QUIZ_TEST);
        $TBL_TABLE_LP_MAIN = Database::get_course_table(TABLE_LP_MAIN);
        $TBL_USER = Database::get_main_table(TABLE_MAIN_USER);
        $TBL_TRACK_EXERCISES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $TBL_TRACK_ATTEMPT_RECORDING = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);

        $cid = api_get_course_id();
        $course_id = api_get_course_int_id();
        $user_id = (int) $user_id;
        $sessionId = api_get_session_id();
        $session_id_and = ' AND te.session_id = '.$sessionId.' ';
        $exercise_id = (int) $exercise_id;

        if (empty($sessionId) &&
            api_get_configuration_value('show_exercise_session_attempts_in_base_course')
        ) {
            $session_id_and = '';
        }

        if (!empty($exercise_id)) {
            $session_id_and .= " AND exe_exo_id = $exercise_id ";
        }

        if (empty($user_id)) {
            $user_id_and = null;
            $sql = "SELECT
                    firstname,
                    lastname,
                    official_code,
                    ce.title as extitle,
                    te.exe_result as exresult ,
                    te.exe_weighting as exweight,
                    te.exe_date as exdate,
                    te.exe_id as exid,
                    email as exemail,
                    te.start_date as exstart,
                    steps_counter as exstep,
                    exe_user_id as excruid,
                    te.exe_duration as duration,
                    te.orig_lp_id as orig_lp_id,
                    tlm.name as lp_name,
                    user.username,
                    te.status as exstatus
                FROM $TBL_EXERCISES  AS ce
                INNER JOIN $TBL_TRACK_EXERCISES AS te
                ON (te.exe_exo_id = ce.iid)
                INNER JOIN $TBL_USER AS user
                ON (user.user_id = exe_user_id)
                LEFT JOIN $TBL_TABLE_LP_MAIN AS tlm
                ON (tlm.id = te.orig_lp_id AND tlm.c_id = ce.c_id)
                WHERE
                    ce.c_id = $course_id AND
                    te.c_id = ce.c_id $user_id_and $session_id_and AND
                    ce.active <> -1";
        } else {
            $user_id_and = ' AND te.exe_user_id = '.api_get_user_id().' ';
            $orderBy = 'lastname';
            if (api_is_western_name_order()) {
                $orderBy = 'firstname';
            }
            // get only this user's results
            $sql = "SELECT
                        firstname,
                        lastname,
                        official_code,
                        ce.title as extitle,
                        te.exe_result as exresult,
                        te.exe_weighting as exweight,
                        te.exe_date as exdate,
                        te.exe_id as exid,
                        email as exemail,
                        te.start_date as exstart,
                        steps_counter as exstep,
                        exe_user_id as excruid,
                        te.exe_duration as duration,
                        ce.results_disabled as exdisabled,
                        te.orig_lp_id as orig_lp_id,
                        tlm.name as lp_name,
                        user.username,
                        te.status as exstatus
                    FROM $TBL_EXERCISES  AS ce
                    INNER JOIN $TBL_TRACK_EXERCISES AS te
                    ON (te.exe_exo_id = ce.iid)
                    INNER JOIN $TBL_USER AS user
                    ON (user.user_id = exe_user_id)
                    LEFT JOIN $TBL_TABLE_LP_MAIN AS tlm
                    ON (tlm.id = te.orig_lp_id AND tlm.c_id = ce.c_id)
                    WHERE
                        ce.c_id = $course_id AND
                        te.c_id = ce.c_id $user_id_and $session_id_and AND
                        ce.active <>-1 AND
                    ORDER BY $orderBy, te.c_id ASC, ce.title ASC, te.exe_date DESC";
        }

        $results = [];
        $resx = Database::query($sql);
        $bestAttemptPerUser = [];
        while ($rowx = Database::fetch_array($resx, 'ASSOC')) {
            if ($this->onlyBestAttempts) {
                if (!isset($bestAttemptPerUser[$rowx['excruid']])) {
                    $bestAttemptPerUser[$rowx['excruid']] = $rowx;
                } else {
                    if ($rowx['exresult'] > $bestAttemptPerUser[$rowx['excruid']]['exresult']) {
                        $bestAttemptPerUser[$rowx['excruid']] = $rowx;
                    }
                }
            } else {
                $results[] = $rowx;
            }
        }

        if ($this->onlyBestAttempts) {
            // Remove userId indexes to avoid issues in output
            foreach ($bestAttemptPerUser as $attempt) {
                $results[] = $attempt;
            }
        }

        $filter_by_not_revised = false;
        $filter_by_revised = false;

        if ($filter) {
            switch ($filter) {
                case 1:
                    $filter_by_not_revised = true;
                    break;
                case 2:
                    $filter_by_revised = true;
                    break;
                default:
                    null;
            }
        }

        if (empty($sessionId)) {
            $students = CourseManager::get_user_list_from_course_code($cid);
        } else {
            $students = CourseManager::get_user_list_from_course_code($cid, $sessionId);
        }
        $studentsUserIdList = array_keys($students);

        // Print the results of tests
        $userWithResults = [];
        if (is_array($results)) {
            $i = 0;
            foreach ($results as $result) {
                $revised = 0;
                if ($result['exstatus'] === 'incomplete') {
                    $revised = -1;
                } else {
                    //revised or not
                    $sql_exe = "SELECT exe_id
                                FROM $TBL_TRACK_ATTEMPT_RECORDING
                                WHERE
                                    author != '' AND
                                    exe_id = ".intval($result['exid'])."
                                LIMIT 1";
                    $query = Database::query($sql_exe);

                    if (Database::num_rows($query) > 0) {
                        $revised = 1;
                    }
                }

                if ($filter_by_not_revised && $revised === 1) {
                    continue;
                }

                if ($filter_by_revised && $revised < 1) {
                    continue;
                }

                $return[$i] = [];
                if (empty($user_id)) {
                    $return[$i]['official_code'] = $result['official_code'];
                    $return[$i]['firstname'] = $results[$i]['firstname'];
                    $return[$i]['lastname'] = $results[$i]['lastname'];
                    $return[$i]['user_id'] = $results[$i]['excruid'];
                    $return[$i]['email'] = $results[$i]['exemail'];
                    $return[$i]['username'] = $results[$i]['username'];
                }
                $return[$i]['title'] = $result['extitle'];
                $return[$i]['start_date'] = api_get_local_time($result['exstart']);
                $return[$i]['end_date'] = api_get_local_time($result['exdate']);
                $return[$i]['duration'] = $result['duration'];
                $return[$i]['result'] = $result['exresult'];
                $return[$i]['max'] = $result['exweight'];
                // Revised: 1 = revised, 0 = not revised, -1 = not even finished by user
                $return[$i]['status'] = $revised === 1 ? get_lang('Validated') : ($revised === 0 ? get_lang('NotValidated') : get_lang('Unclosed'));
                $return[$i]['lp_id'] = $result['orig_lp_id'];
                $return[$i]['lp_name'] = $result['lp_name'];

                if (in_array($result['excruid'], $studentsUserIdList)) {
                    $return[$i]['is_user_subscribed'] = get_lang('Yes');
                } else {
                    $return[$i]['is_user_subscribed'] = get_lang('No');
                }

                $userWithResults[$result['excruid']] = 1;
                $i++;
            }
        }

        if ($this->includeAllUsers) {
            $latestId = count($return);
            $userWithResults = array_keys($userWithResults);

            if (!empty($students)) {
                foreach ($students as $student) {
                    if (!in_array($student['user_id'], $userWithResults)) {
                        $i = $latestId;
                        $isWestern = api_is_western_name_order();

                        if (empty($user_id)) {
                            $return[$i]['official_code'] = $student['official_code'];
                            $return[$i]['firstname'] = $student['firstname'];
                            $return[$i]['lastname'] = $student['lastname'];
                            $return[$i]['user_id'] = $student['user_id'];
                            $return[$i]['email'] = $student['email'];
                            $return[$i]['username'] = $student['username'];
                        }
                        $return[$i]['title'] = null;
                        $return[$i]['start_date'] = null;
                        $return[$i]['end_date'] = null;
                        $return[$i]['duration'] = null;
                        $return[$i]['result'] = null;
                        $return[$i]['max'] = null;
                        $return[$i]['status'] = get_lang('NotAttempted');
                        $return[$i]['lp_id'] = null;
                        $return[$i]['lp_name'] = null;
                        $return[$i]['is_user_subscribed'] = get_lang('Yes');

                        $latestId++;
                    }
                }
            }
        }

        $this->results = $return;

        return true;
    }

    /**
     * Exports the complete report as a CSV file.
     *
     * @param string $document_path      Document path inside the document tool
     * @param int    $user_id            Optional user ID
     * @param bool   $export_user_fields Whether to include user fields or not
     * @param int    $export_filter
     * @param int    $exercise_id
     *
     * @return bool False on error
     */
    public function exportCompleteReportCSV(
        $document_path = '',
        $user_id = null,
        $export_user_fields = false,
        $export_filter = 0,
        $exercise_id = 0
    ) {
        global $charset;
        $this->getExercisesReporting(
            $document_path,
            $user_id,
            $export_filter,
            $exercise_id
        );
        $now = api_get_local_time();
        $filename = 'exercise_results_'.$now.'.csv';
        if (!empty($user_id)) {
            $filename = 'exercise_results_user_'.$user_id.'_'.$now.'.csv';
        }

        $filename = api_replace_dangerous_char($filename);
        $data = '';
        if (api_is_western_name_order()) {
            if (!empty($this->results[0]['firstname'])) {
                $data .= get_lang('FirstName').';';
            }
            if (!empty($this->results[0]['lastname'])) {
                $data .= get_lang('LastName').';';
            }
        } else {
            if (!empty($this->results[0]['lastname'])) {
                $data .= get_lang('LastName').';';
            }
            if (!empty($this->results[0]['firstname'])) {
                $data .= get_lang('FirstName').';';
            }
        }
        $officialCodeInList = api_get_setting('show_official_code_exercise_result_list');
        if ($officialCodeInList === 'true') {
            $data .= get_lang('OfficialCode').';';
        }

        $data .= get_lang('LoginName').';';
        $data .= get_lang('Email').';';
        $data .= get_lang('Groups').';';

        if ($export_user_fields) {
            //show user fields section with a big th colspan that spans over all fields
            $extra_user_fields = UserManager::get_extra_fields(
                0,
                1000,
                5,
                'ASC',
                false,
                1
            );
            if (!empty($extra_user_fields)) {
                foreach ($extra_user_fields as $field) {
                    $data .= '"'.str_replace("\r\n", '  ', api_html_entity_decode(strip_tags($field[3]), ENT_QUOTES, $charset)).'";';
                }
            }
        }

        $data .= get_lang('Title').';';
        $data .= get_lang('StartDate').';';
        $data .= get_lang('EndDate').';';
        $data .= get_lang('Duration').' ('.get_lang('MinMinutes').') ;';
        $data .= get_lang('Score').';';
        $data .= get_lang('Total').';';
        $data .= get_lang('Status').';';
        $data .= get_lang('ToolLearnpath').';';
        $data .= get_lang('UserIsCurrentlySubscribed').';';
        $data .= get_lang('CourseCode').';';
        $data .= "\n";

        //results
        foreach ($this->results as $row) {
            if (api_is_western_name_order()) {
                $data .= str_replace("\r\n", '  ', api_html_entity_decode(strip_tags($row['firstname']), ENT_QUOTES, $charset)).';';
                $data .= str_replace("\r\n", '  ', api_html_entity_decode(strip_tags($row['lastname']), ENT_QUOTES, $charset)).';';
            } else {
                $data .= str_replace("\r\n", '  ', api_html_entity_decode(strip_tags($row['lastname']), ENT_QUOTES, $charset)).';';
                $data .= str_replace("\r\n", '  ', api_html_entity_decode(strip_tags($row['firstname']), ENT_QUOTES, $charset)).';';
            }

            // Official code
            if ($officialCodeInList === 'true') {
                $data .= $row['official_code'].';';
            }

            // Email
            $data .= str_replace("\r\n", '  ', api_html_entity_decode(strip_tags($row['username']), ENT_QUOTES, $charset)).';';
            $data .= str_replace("\r\n", '  ', api_html_entity_decode(strip_tags($row['email']), ENT_QUOTES, $charset)).';';
            $data .= str_replace("\r\n", '  ', implode(", ", GroupManager::get_user_group_name($row['user_id']))).';';

            if ($export_user_fields) {
                //show user fields data, if any, for this user
                $user_fields_values = UserManager::get_extra_user_data(
                    $row['user_id'],
                    false,
                    false,
                    false,
                    true
                );
                if (!empty($user_fields_values)) {
                    foreach ($user_fields_values as $value) {
                        $data .= '"'.str_replace('"', '""', api_html_entity_decode(strip_tags($value), ENT_QUOTES, $charset)).'";';
                    }
                }
            }
            $duration = !empty($row['duration']) ? round($row['duration'] / 60) : 0;

            $data .= str_replace("\r\n", '  ', api_html_entity_decode(strip_tags($row['title']), ENT_QUOTES, $charset)).';';
            $data .= str_replace("\r\n", '  ', $row['start_date']).';';
            $data .= str_replace("\r\n", '  ', $row['end_date']).';';
            $data .= str_replace("\r\n", '  ', $duration).';';
            $data .= str_replace("\r\n", '  ', $row['result']).';';
            $data .= str_replace("\r\n", '  ', $row['max']).';';
            $data .= str_replace("\r\n", '  ', $row['status']).';';
            $data .= str_replace("\r\n", '  ', $row['lp_name']).';';
            $data .= str_replace("\r\n", '  ', $row['is_user_subscribed']).';';
            $data .= str_replace("\r\n", '  ', api_get_course_id()).';';

            $data .= "\n";
        }

        // output the results
        $len = strlen($data);
        header('Content-type: application/octet-stream');
        header('Content-Type: application/force-download');
        header('Content-length: '.$len);
        if (preg_match("/MSIE 5.5/", $_SERVER['HTTP_USER_AGENT'])) {
            header('Content-Disposition: filename= '.$filename);
        } else {
            header('Content-Disposition: attachment; filename= '.$filename);
        }
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
            header('Pragma: ');
            header('Cache-Control: ');
            header('Cache-Control: public'); // IE cannot download from sessions without a cache
        }
        header('Content-Description: '.$filename);
        header('Content-transfer-encoding: binary');
        echo $data;

        return true;
    }

    /**
     * Exports the complete report as an XLS file.
     *
     * @param string $document_path
     * @param null   $user_id
     * @param bool   $export_user_fields
     * @param int    $export_filter
     * @param int    $exercise_id
     * @param null   $hotpotato_name
     *
     * @return bool
     */
    public function exportCompleteReportXLS(
        $document_path = '',
        $user_id = null,
        $export_user_fields = false,
        $export_filter = 0,
        $exercise_id = 0,
        $hotpotato_name = null
    ) {
        global $charset;
        $this->getExercisesReporting(
            $document_path,
            $user_id,
            $export_filter,
            $exercise_id,
            $hotpotato_name
        );
        $now = api_get_local_time();
        $filename = 'exercise_results_'.$now.'.xlsx';
        if (!empty($user_id)) {
            $filename = 'exercise_results_user_'.$user_id.'_'.$now.'.xlsx';
        }

        $spreadsheet = new PHPExcel();
        $spreadsheet->setActiveSheetIndex(0);
        $worksheet = $spreadsheet->getActiveSheet();

        $line = 1; // Skip first line
        $column = 0; //skip the first column (row titles)

        // check if exists column 'user'
        $with_column_user = false;
        foreach ($this->results as $result) {
            if (!empty($result['lastname']) && !empty($result['firstname'])) {
                $with_column_user = true;
                break;
            }
        }

        $officialCodeInList = api_get_setting('show_official_code_exercise_result_list');

        if ($with_column_user) {
            if (api_is_western_name_order()) {
                $worksheet->setCellValueByColumnAndRow($column, $line, get_lang('FirstName'));
                $column++;
                $worksheet->setCellValueByColumnAndRow($column, $line, get_lang('LastName'));
                $column++;
            } else {
                $worksheet->setCellValueByColumnAndRow($column, $line, get_lang('LastName'));
                $column++;
                $worksheet->setCellValueByColumnAndRow($column, $line, get_lang('FirstName'));
                $column++;
            }

            if ($officialCodeInList === 'true') {
                $worksheet->setCellValueByColumnAndRow($column, $line, get_lang('OfficialCode'));
                $column++;
            }

            $worksheet->setCellValueByColumnAndRow($column++, $line, get_lang('LoginName'));
            $worksheet->setCellValueByColumnAndRow($column, $line, get_lang('Email'));
            $column++;
        }
        $worksheet->setCellValueByColumnAndRow($column, $line, get_lang('Groups'));
        $column++;

        if ($export_user_fields) {
            //show user fields section with a big th colspan that spans over all fields
            $extra_user_fields = UserManager::get_extra_fields(
                0,
                1000,
                5,
                'ASC',
                false,
                1
            );

            //show the fields names for user fields
            foreach ($extra_user_fields as $field) {
                $worksheet->setCellValueByColumnAndRow(
                    $column,
                    $line,
                    api_html_entity_decode(
                        strip_tags($field[3]),
                        ENT_QUOTES,
                        $charset
                    )
                );
                $column++;
            }
        }

        $worksheet->setCellValueByColumnAndRow($column, $line, get_lang('Title'));
        $column++;
        $worksheet->setCellValueByColumnAndRow($column, $line, get_lang('StartDate'));
        $column++;
        $worksheet->setCellValueByColumnAndRow($column, $line, get_lang('EndDate'));
        $column++;
        $worksheet->setCellValueByColumnAndRow($column, $line, get_lang('Duration').' ('.get_lang('MinMinutes').')');
        $column++;
        $worksheet->setCellValueByColumnAndRow($column, $line, get_lang('Score'));
        $column++;
        $worksheet->setCellValueByColumnAndRow($column, $line, get_lang('Total'));
        $column++;
        $worksheet->setCellValueByColumnAndRow($column, $line, get_lang('Status'));
        $column++;
        $worksheet->setCellValueByColumnAndRow($column, $line, get_lang('ToolLearnpath'));
        $column++;
        $worksheet->setCellValueByColumnAndRow($column, $line, get_lang('UserIsCurrentlySubscribed'));
        $column++;
        $worksheet->setCellValueByColumnAndRow($column, $line, get_lang('CourseCode'));
        $line++;

        foreach ($this->results as $row) {
            $column = 0;
            if ($with_column_user) {
                if (api_is_western_name_order()) {
                    $worksheet->setCellValueByColumnAndRow(
                        $column,
                        $line,
                        api_html_entity_decode(
                            strip_tags($row['firstname']),
                            ENT_QUOTES,
                            $charset
                        )
                    );
                    $column++;
                    $worksheet->setCellValueByColumnAndRow(
                        $column,
                        $line,
                        api_html_entity_decode(
                            strip_tags($row['lastname']),
                            ENT_QUOTES,
                            $charset
                        )
                    );
                    $column++;
                } else {
                    $worksheet->setCellValueByColumnAndRow(
                        $column,
                        $line,
                        api_html_entity_decode(
                            strip_tags($row['lastname']),
                            ENT_QUOTES,
                            $charset
                        )
                    );
                    $column++;
                    $worksheet->setCellValueByColumnAndRow(
                        $column,
                        $line,
                        api_html_entity_decode(
                            strip_tags($row['firstname']),
                            ENT_QUOTES,
                            $charset
                        )
                    );
                    $column++;
                }

                if ($officialCodeInList === 'true') {
                    $worksheet->setCellValueByColumnAndRow(
                        $column,
                        $line,
                        api_html_entity_decode(
                            strip_tags($row['official_code']),
                            ENT_QUOTES,
                            $charset
                        )
                    );
                    $column++;
                }

                $worksheet->setCellValueByColumnAndRow(
                    $column++,
                    $line,
                    api_html_entity_decode(
                        strip_tags($row['username']),
                        ENT_QUOTES,
                        $charset
                    )
                );
                $worksheet->setCellValueByColumnAndRow(
                    $column,
                    $line,
                    api_html_entity_decode(
                        strip_tags($row['email']),
                        ENT_QUOTES,
                        $charset
                    )
                );
                $column++;
            }

            $worksheet->setCellValueByColumnAndRow(
                $column,
                $line,
                api_html_entity_decode(
                    strip_tags(
                        implode(
                            ", ",
                            GroupManager::get_user_group_name($row['user_id'])
                        )
                    ),
                    ENT_QUOTES,
                    $charset
                )
            );
            $column++;

            if ($export_user_fields) {
                //show user fields data, if any, for this user
                $user_fields_values = UserManager::get_extra_user_data(
                    $row['user_id'],
                    false,
                    false,
                    false,
                    true
                );
                foreach ($user_fields_values as $value) {
                    $worksheet->setCellValueByColumnAndRow(
                        $column,
                        $line,
                        api_html_entity_decode(
                            strip_tags($value),
                            ENT_QUOTES,
                            $charset
                        )
                    );
                    $column++;
                }
            }

            $worksheet->setCellValueByColumnAndRow(
                $column,
                $line,
                api_html_entity_decode(
                    strip_tags($row['title']),
                    ENT_QUOTES,
                    $charset
                )
            );

            $column++;
            $worksheet->setCellValueByColumnAndRow($column, $line, $row['start_date']);
            $column++;
            $worksheet->setCellValueByColumnAndRow($column, $line, $row['end_date']);
            $column++;
            $duration = !empty($row['duration']) ? round($row['duration'] / 60) : 0;
            $worksheet->setCellValueByColumnAndRow($column, $line, $duration);
            $column++;
            $worksheet->setCellValueByColumnAndRow($column, $line, $row['result']);
            $column++;
            $worksheet->setCellValueByColumnAndRow($column, $line, $row['max']);
            $column++;
            $worksheet->setCellValueByColumnAndRow($column, $line, $row['status']);
            $column++;
            $worksheet->setCellValueByColumnAndRow($column, $line, $row['lp_name']);
            $column++;
            $worksheet->setCellValueByColumnAndRow($column, $line, $row['is_user_subscribed']);
            $column++;
            $worksheet->setCellValueByColumnAndRow($column, $line, api_get_course_id());
            $line++;
        }

        $file = api_get_path(SYS_ARCHIVE_PATH).api_replace_dangerous_char($filename);
        $writer = new PHPExcel_Writer_Excel2007($spreadsheet);
        $writer->save($file);
        DocumentManager::file_send_for_download($file, true, $filename);

        return true;
    }
}
