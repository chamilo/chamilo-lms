<?php

/* For licensing terms, see /license.txt */

ob_start();
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$this_section = SECTION_TRACKING;
$export_csv = false;
if (isset($_REQUEST['export']) && 'csv' == $_REQUEST['export']) {
    $export_csv = true;
}
$action = isset($_GET['action']) ? $_GET['action'] : '';

$id_coach = api_get_user_id();
if (isset($_REQUEST['id_coach']) && '' != $_REQUEST['id_coach']) {
    $id_coach = (int) $_REQUEST['id_coach'];
}

$allowToTrack = api_is_platform_admin(true, true) || api_is_teacher();

if (!$allowToTrack) {
    api_not_allowed(true);
}

$studentId = isset($_GET['student']) ? (int) $_GET['student'] : 0;
$sessionId = isset($_GET['sid']) ? (int) $_GET['sid'] : 0;
$hideConnectionTime = isset($_REQUEST['hide_connection_time']) ? (int) $_REQUEST['hide_connection_time'] : 0;
$currentUrl = api_get_self().'?student='.$studentId.'&sid='.$sessionId;

switch ($action) {
    case 'export_to_pdf':
        $allStudents = isset($_GET['all_students']) && 1 === (int) $_GET['all_students'] ? true : false;
        $sessionToExport = isset($_GET['session_to_export']) ? (int) $_GET['session_to_export'] : 0;
        $type = isset($_GET['type']) ? $_GET['type'] : 'attendance';
        $sessionInfo = api_get_session_info($sessionToExport);
        if (empty($sessionInfo)) {
            api_not_allowed(true);
        }
        $courses = Tracking::get_courses_list_from_session($sessionToExport);
        $studentList = [$studentId];
        if ($allStudents) {
            $users = SessionManager::get_users_by_session($sessionToExport, 0);
            $studentList = array_column($users, 'user_id');
        }

        $totalCourses = count($courses);
        $scoreDisplay = ScoreDisplay::instance();
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $pdfList = [];
        foreach ($studentList as $studentId) {
            $studentInfo = api_get_user_info($studentId);
            $timeSpent = 0;
            $numberVisits = 0;
            $progress = 0;
            $timeSpentPerCourse = [];
            $progressPerCourse = [];

            foreach ($courses as $course) {
                $courseId = $course['c_id'];
                $courseTimeSpent = Tracking::get_time_spent_on_the_course($studentId, $courseId, $sessionToExport);
                $timeSpentPerCourse[$courseId] = $courseTimeSpent;
                $timeSpent += $courseTimeSpent;
                $sql = "SELECT DISTINCT count(course_access_id) as count
                        FROM $table
                        WHERE
                            c_id = $courseId AND
                            session_id = $sessionToExport AND
                            user_id = $studentId";
                $result = Database::query($sql);
                $row = Database::fetch_array($result);
                $numberVisits += $row['count'];
                $courseProgress = Tracking::get_avg_student_progress(
                    $studentId,
                    $course['code'],
                    [],
                    $sessionToExport
                );
                $progressPerCourse[$courseId] = $courseProgress;
                $progress += $courseProgress;
            }

            $average = round($progress / $totalCourses, 1);
            $average = empty($average) ? '0%' : $average.'%';
            $first = Tracking::get_first_connection_date($studentId);
            $last = Tracking::get_last_connection_date($studentId);
            $timeSpentContent = '';
            $pdfTitle = get_lang('AttestationOfAttendance');
            if ('attendance' === $type) {
                $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
                $column = 0;
                $row = 0;
                $headers = [
                    get_lang('TimeSpent'),
                    get_lang('NumberOfVisits'),
                    get_lang('GlobalProgress'),
                    get_lang('FirstLogin'),
                    get_lang('LastConnexionDate'),
                ];
                foreach ($headers as $header) {
                    $table->setHeaderContents($row, $column, $header);
                    $column++;
                }
                $table->setCellContents(1, 0, api_time_to_hms($timeSpent));
                $table->setCellContents(1, 1, $numberVisits);
                $table->setCellContents(1, 2, $average);
                $table->setCellContents(1, 3, $first);
                $table->setCellContents(1, 4, $last);
                $timeSpentContent = $table->toHtml();
            } else {
                $pdfTitle = get_lang('CertificateOfAchievement');
            }

            $courseTable = '';
            if (!empty($courses)) {
                $courseTable .= '<table class="table table-hover table-striped data_table">';
                $courseTable .= '<thead>';
                $courseTable .= '<tr>
                    <th>'.get_lang('FormationUnit').'</th>';

                if (!$hideConnectionTime) {
                    $courseTable .= '<th>'.get_lang('ConnectionTime').'</th>';
                }

                $courseTable .= '<th>'.get_lang('Progress').'</th>';

                if ('attendance' === $type) {
                    $courseTable .= '<th>'.get_lang('Score').'</th>';
                }
                $courseTable .= '</tr>';
                $courseTable .= '</thead>';
                $courseTable .= '<tbody>';

                $totalCourseTime = 0;
                $totalAttendance = [0, 0];
                $totalScore = 0;
                $totalProgress = 0;
                $gradeBookTotal = [0, 0];
                $totalEvaluations = '0/0 (0%)';

                foreach ($courses as $course) {
                    $courseId = $course['c_id'];
                    $courseInfoItem = api_get_course_info_by_id($courseId);
                    $courseId = $courseInfoItem['real_id'];
                    $courseCodeItem = $courseInfoItem['code'];

                    $isSubscribed = CourseManager::is_user_subscribed_in_course(
                        $studentId,
                        $courseCodeItem,
                        true,
                        $sessionToExport
                    );

                    if ($isSubscribed) {
                        $timeInSeconds = $timeSpentPerCourse[$courseId];
                        $totalCourseTime += $timeInSeconds;
                        $time_spent_on_course = api_time_to_hms($timeInSeconds);
                        $progress = $progressPerCourse[$courseId];
                        $totalProgress += $progress;

                        $bestScore = Tracking::get_avg_student_score(
                            $studentId,
                            $courseCodeItem,
                            [],
                            $sessionToExport,
                            false,
                            false,
                            true
                        );

                        if (is_numeric($bestScore)) {
                            $totalScore += $bestScore;
                        }

                        $progress = empty($progress) ? '0%' : $progress.'%';
                        $score = empty($bestScore) ? '0%' : $bestScore.'%';

                        $courseTable .= '<tr>
                        <td>
                            <a href="'.$courseInfoItem['course_public_url'].'?id_session='.$sessionToExport.'">'.
                            $courseInfoItem['title'].'</a>
                        </td>';
                        if (!$hideConnectionTime) {
                            $courseTable .= '<td >'.$time_spent_on_course.'</td>';
                        }
                        $courseTable .= '<td >'.$progress.'</td>';
                        if ('attendance' === $type) {
                            $courseTable .= '<td >'.$score.'</td>';
                        }
                        $courseTable .= '</tr>';
                    }
                }

                $totalAttendanceFormatted = $scoreDisplay->display_score($totalAttendance);
                $totalScoreFormatted = $scoreDisplay->display_score([$totalScore / $totalCourses, 100], SCORE_AVERAGE);
                $totalProgressFormatted = $scoreDisplay->display_score(
                    [$totalProgress / $totalCourses, 100],
                    SCORE_AVERAGE
                );
                $totalEvaluations = $scoreDisplay->display_score($gradeBookTotal);
                $totalTimeFormatted = api_time_to_hms($totalCourseTime);
                $courseTable .= '
                    <tr>
                        <th>'.get_lang('Total').'</th>';
                if (!$hideConnectionTime) {
                    $courseTable .= '<th>'.$totalTimeFormatted.'</th>';
                }
                $courseTable .= '<th>'.$totalProgressFormatted.'</th>';
                if ('attendance' === $type) {
                    $courseTable .= '<th>'.$totalScoreFormatted.'</th>';
                }
                $courseTable .= '</tr>';
                $courseTable .= '</tbody></table>';
            }

            $tpl = new Template('', false, false, false, true, false, false);
            $tpl->assign('title', $pdfTitle);
            $tpl->assign('session_title', $sessionInfo['name']);
            $tpl->assign('session_info', $sessionInfo);
            $sessionCategoryTitle = '';
            if (isset($sessionInfo['session_category_id'])) {
                $sessionCategory = SessionManager::get_session_category($sessionInfo['session_category_id']);
                if ($sessionCategory) {
                    $sessionCategoryTitle = $sessionCategory['name'];
                }
            }
            $dateData = SessionManager::parseSessionDates($sessionInfo, false);
            $dateToString = $dateData['access'];
            $tpl->assign('session_display_dates', $dateToString);
            $tpl->assign('session_category_title', $sessionCategoryTitle);
            $tpl->assign('student', $studentInfo['complete_name']);
            $tpl->assign('student_info', $studentInfo);
            $tpl->assign('student_info_extra_fields', UserManager::get_extra_user_data($studentInfo['user_id']));
            $tpl->assign('table_progress', $timeSpentContent);
            $tpl->assign(
                'subtitle',
                sprintf(
                    get_lang('InSessionXYouHadTheFollowingResults'),
                    $sessionInfo['name']
                )
            );
            $tpl->assign('table_course', $courseTable);
            $template = 'pdf_export_student.tpl';
            if ('achievement' === $type) {
                $template = 'certificate_achievement.tpl';
            }
            $content = $tpl->fetch($tpl->get_template('my_space/'.$template));

            $params = [
                'pdf_title' => get_lang('Resume'),
                'session_info' => $sessionInfo,
                'course_info' => '',
                'pdf_date' => '',
                'student_info' => $studentInfo,
                'show_grade_generated_date' => true,
                'show_real_course_teachers' => false,
                'show_teacher_as_myself' => false,
                'orientation' => 'P',
            ];
            @$pdf = new PDF('A4', $params['orientation'], $params);
            $pdf->setBackground($tpl->theme);
            $mode = 'D';

            $pdfName = $sessionInfo['name'].'_'.$studentInfo['complete_name'];
            if ($allStudents) {
                $mode = 'F';
                $pdfName = $studentInfo['complete_name'];
            }
            $pdf->set_footer();
            $result = @$pdf->content_to_pdf(
                $content,
                '',
                $pdfName,
                null,
                $mode,
                false,
                null,
                false,
                true,
                false
            );
            $pdfList[] = $result;
        }

        if (empty($pdfList)) {
            api_not_allowed(true);
        }

        // Creating a ZIP file.
        $tempZipFile = api_get_path(SYS_ARCHIVE_PATH).uniqid('report_session_'.$sessionToExport, true).'.zip';

        $zip = new PclZip($tempZipFile);
        foreach ($pdfList as $file) {
            $zip->add(
                $file,
                PCLZIP_OPT_REMOVE_PATH,
                api_get_path(SYS_ARCHIVE_PATH)
            );
        }
        $name = $sessionInfo['name'].'_'.api_get_utc_datetime().'.zip';
        DocumentManager::file_send_for_download($tempZipFile, true, $name);
        exit;
        break;
}

$htmlHeadXtra[] = api_get_jqgrid_js();
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('MySpace')];
Display::display_header(get_lang('Sessions'));

if (api_is_platform_admin(true, true)) {
    $a_sessions = SessionManager::get_sessions_followed_by_drh(api_get_user_id());

    if (!api_is_session_admin()) {
        $menu_items[] = Display::url(
            Display::return_icon('statistics.png', get_lang('MyStats'), '', ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'auth/my_progress.php'
        );
        $menu_items[] = Display::url(
            Display::return_icon('user.png', get_lang('Students'), [], ICON_SIZE_MEDIUM),
            'index.php?view=drh_students&amp;display=yourstudents'
        );
        $menu_items[] = Display::url(
            Display::return_icon('teacher.png', get_lang('Trainers'), [], ICON_SIZE_MEDIUM),
            'teachers.php'
        );
        $menu_items[] = Display::url(
            Display::return_icon('course.png', get_lang('Courses'), [], ICON_SIZE_MEDIUM),
            'course.php'
        );
        $menu_items[] = Display::url(
            Display::return_icon('session_na.png', get_lang('Sessions'), [], ICON_SIZE_MEDIUM),
            '#'
        );
    } else {
        $menu_items[] = Display::url(
            Display::return_icon('teacher.png', get_lang('Trainers'), [], ICON_SIZE_MEDIUM),
            'session_admin_teachers.php'
        );
    }

    $menu_items[] = Display::url(
        Display::return_icon('works.png', get_lang('WorksReport'), [], ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH).'mySpace/works_in_session_report.php'
    );

    $menu_items[] = Display::url(
        Display::return_icon('attendance_list.png', get_lang('ProgressInSessionReport'), [], ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH).'mySpace/progress_in_session_report.php'
    );

    $menu_items[] = Display::url(
        Display::return_icon('clock.png', get_lang('TeacherTimeReportBySession'), [], ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH).'admin/teachers_time_by_session_report.php'
    );

    if (!api_is_session_admin()) {
        $menu_items[] = Display::url(
            Display::return_icon('1day.png', get_lang('SessionsPlanCalendar'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH)."calendar/planification.php"
        );
    }

    if (api_is_drh()) {
        $menu_items[] = Display::url(
            Display::return_icon('session.png', get_lang('SessionFilterReport'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'mySpace/session_filter.php'
        );
    }

    $actionsLeft = '';
    $nb_menu_items = count($menu_items);
    if ($nb_menu_items > 1) {
        foreach ($menu_items as $key => $item) {
            $actionsLeft .= $item;
        }
    }
    $actionsRight = '';
    if (count($a_sessions) > 0) {
        $actionsRight = Display::url(
            Display::return_icon('printer.png', get_lang('Print'), [], 32),
            'javascript: void(0);',
            ['onclick' => 'javascript: window.print();']
        );
        $actionsRight .= Display::url(
            Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), [], 32),
            api_get_self().'?export=csv'
        );
    }

    $toolbar = Display::toolbarAction(
        'toolbar-session',
        [$actionsLeft, $actionsRight]
    );
    echo $toolbar;

    echo Display::page_header(get_lang('YourSessionsList'));
} elseif (api_is_teacher()) {
    $actionsRight = Display::url(
        Display::return_icon('clock.png', get_lang('TeacherTimeReportBySession'), [], ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH).'admin/teachers_time_by_session_report.php'
    );

    $toolbar = Display::toolbarAction(
        'toolbar-session',
        ['', $actionsRight]
    );
    echo $toolbar;

    echo Display::page_header(get_lang('YourSessionsList'));
} else {
    $a_sessions = Tracking::get_sessions_coached_by_user($id_coach);
}

$form = new FormValidator(
    'search_course',
    'post',
    api_get_path(WEB_CODE_PATH).'mySpace/session.php'
);
$form->addElement('text', 'keyword', get_lang('Keyword'));

$extraFieldSession = new ExtraField('session');
$extraFieldSession->addElements(
    $form,
    null,
    [],
    true
);

$form->addButtonSearch(get_lang('Search'));
$keyword = '';
$result = SessionManager::getGridColumns('my_space');

$columns = $result['columns'];
$columnModel = $result['column_model'];

$filterToString = '';
if ($form->validate()) {
    $values = $form->getSubmitValues();
    $keyword = Security::remove_XSS($form->getSubmitValue('keyword'));
    $extraField = new ExtraField('session');
    $extraFields = $extraField->get_all(null, 'option_order');
    $extraFields = array_column($extraFields, 'variable');
    $filter = new stdClass();

    foreach ($columnModel as $col) {
        if (isset($values[$col['index']]) && !empty($values[$col['index']]) &&
            in_array(str_replace('extra_', '', $col['index']), $extraFields)
        ) {
            $rule = new stdClass();
            $index = $col['index'];
            $rule->field = $index;
            $rule->op = 'in';
            $data = $values[$index];
            if (is_array($data) && array_key_exists($index, $data)) {
                $data = $data[$index];
            }
            $rule->data = Security::remove_XSS($data);
            $filter->rules[] = $rule;
            $filter->groupOp = 'AND';
        }
    }
    $filterToString = json_encode($filter);
}
$form->setDefaults(['keyword' => $keyword]);

$url = api_get_path(WEB_AJAX_PATH).
    'model.ajax.php?a=get_sessions_tracking&_search=true&_force_search=true&filters='.$filterToString.'&keyword='.$keyword;

// Column config
$extraParams = [
    'autowidth' => 'true',
    'height' => 'auto',
];

/*$extraParams['postData'] = [
    'filters' => [
        'groupOp' => 'AND',
        'rules' => $result['rules'],
    ],
];*/

$urlAjaxExtraField = api_get_path(WEB_AJAX_PATH).'extra_field.ajax.php?1=1';
$allowOrder = api_get_configuration_value('session_list_order');
$orderUrl = api_get_path(WEB_AJAX_PATH).'session.ajax.php?a=order';

?>
    <script>
        function setSearchSelect(columnName) {
            $("#sessions").jqGrid('setColProp', columnName, {
            });
        }
        var added_cols = [];
        var original_cols = [];
        function clean_cols(grid, added_cols) {
            // Cleaning
            for (key in added_cols) {
                grid.hideCol(key);
            }
            grid.showCol('name');
            grid.showCol('display_start_date');
            grid.showCol('display_end_date');
            grid.showCol('course_title');
        }

        function show_cols(grid, added_cols) {
            grid.showCol('name').trigger('reloadGrid');
            for (key in added_cols) {
                grid.showCol(key);
            }
        }

        var second_filters = [];

        $(function() {
            date_pick_today = function(elem) {
                $(elem).datetimepicker({dateFormat: "yy-mm-dd"});
                $(elem).datetimepicker('setDate', (new Date()));
            }
            date_pick_one_month = function(elem) {
                $(elem).datetimepicker({dateFormat: "yy-mm-dd"});
                next_month = Date.today().next().month();
                $(elem).datetimepicker('setDate', next_month);
            }

            // Great hack
            register_second_select = function(elem) {
                second_filters[$(elem).val()] = $(elem);
            }

            fill_second_select = function(elem) {
                $(elem).on("change", function() {
                    composed_id = $(this).val();
                    field_id = composed_id.split("#")[0];
                    id = composed_id.split("#")[1];
                    $.ajax({
                        url: "<?php echo $urlAjaxExtraField; ?>&a=get_second_select_options",
                        dataType: "json",
                        data: "type=session&field_id="+field_id+"&option_value_id="+id,
                        success: function(data) {
                            my_select = second_filters[field_id];
                            my_select.empty();
                            $.each(data, function(index, value) {
                                my_select.append($("<option/>", {
                                    value: index,
                                    text: value
                                }));
                            });
                        }
                    });
                });
            }

            <?php
            echo Display::grid_js(
                'sessions',
                $url,
                $columns,
                $columnModel,
                $extraParams,
                [],
                null,
                true
            );
            ?>

            setSearchSelect("status");

            var grid = $("#sessions"),
                prmSearch = {
                    multipleSearch : true,
                    overlay : false,
                    width: 'auto',
                    caption: '<?php echo addslashes(get_lang('Search')); ?>',
                    formclass:'data_table',
                    onSearch : function() {
                        var postdata = grid.jqGrid('getGridParam', 'postData');
                        if (postdata && postdata.filters) {
                            filters = jQuery.parseJSON(postdata.filters);
                            clean_cols(grid, added_cols);
                            added_cols = [];
                            $.each(filters, function(key, value) {
                                if (key == 'rules') {
                                    $.each(value, function(subkey, subvalue) {
                                        added_cols[subvalue.field] = subvalue.field;
                                    });
                                }
                            });
                            show_cols(grid, added_cols);
                        }
                    },
                    onReset: function() {
                        clean_cols(grid, added_cols);
                    }
                };

            original_cols = grid.jqGrid('getGridParam', 'colModel');

            grid.jqGrid('navGrid','#sessions_pager',
                {edit:false,add:false,del:false},
                {height:280,reloadAfterSubmit:false}, // edit options
                {height:280,reloadAfterSubmit:false}, // add options
                {reloadAfterSubmit:false},// del options
                prmSearch
            );

            <?php
            // Create the searching dialog.
            //echo 'grid.searchGrid(prmSearch);';
            ?>
            // Fixes search table.
            var searchDialogAll = $("#fbox_"+grid[0].id);
            searchDialogAll.addClass("table");
            var searchDialog = $("#searchmodfbox_"+grid[0].id);
            searchDialog.addClass("ui-jqgrid ui-widget ui-widget-content ui-corner-all");
            searchDialog.css({position:"adsolute", "z-index":"100", "float":"left", "top":"55%", "left" : "25%", "padding" : "5px", "border": "1px solid #CCC"})
            var gbox = $("#gbox_"+grid[0].id);
            gbox.before(searchDialog);
            gbox.css({clear:"left"});

            // Select first elements by default
            $('.input-elm').each(function() {
                $(this).find('option:first').attr('selected', 'selected');
            });

            $('.delete-rule').each(function(){
                $(this).click(function(){
                    $('.input-elm').each(function(){
                        $(this).find('option:first').attr('selected', 'selected');
                    });
                });
            });
        });
    </script>
<?php

$form->display();
echo Display::grid_html('sessions');
Display::display_footer();
