<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * List sessions in an efficient and usable way.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

SessionManager::protectSession(null, false);

// Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
$idChecked = isset($_REQUEST['idChecked']) ? $_REQUEST['idChecked'] : null;
$idMultiple = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
$listType = isset($_REQUEST['list_type']) ? Security::remove_XSS($_REQUEST['list_type']) : SessionManager::getDefaultSessionTab();
$copySessionContent = isset($_REQUEST['copy_session_content']) ? true : false;
$addSessionContent = api_get_configuration_value('duplicate_specific_session_content_on_session_copy');
if (false === $addSessionContent) {
    $copySessionContent = false;
}

switch ($action) {
    case 'delete_multiple':
        $sessionList = explode(',', $idMultiple);
        foreach ($sessionList as $id) {
            $sessionInfo = api_get_session_info($id);
            if ($sessionInfo) {
                $response = SessionManager::delete($id);
                /*if ($response) {
                    Display::addFlash(
                        Display::return_message(get_lang('Deleted').': '.Security::remove_XSS($sessionInfo['name']))
                    );
                }*/
            }
        }
        echo 1;
        exit;
        break;
    case 'delete':
        $sessionInfo = api_get_session_info($idChecked);
        if ($sessionInfo) {
            $response = SessionManager::delete($idChecked);
            if ($response) {
                Display::addFlash(
                    Display::return_message(get_lang('Deleted').': '.Security::remove_XSS($sessionInfo['name']))
                );
            }
        }
        $url = 'session_list.php';
        if ('custom' !== $listType) {
            $url = 'session_list.php?list_type='.$listType;
        }
        header('Location: '.$url);
        exit();
        break;
    case 'copy':
        $result = SessionManager::copy(
            $idChecked,
            true,
            true,
            false,
            false,
            $copySessionContent
        );
        if ($result) {
            $sessionInfo = api_get_session_info($result);
            $url = Display::url(
                $sessionInfo['name'],
                api_get_path(WEB_CODE_PATH).'session/resume_session.php?id_session='.$result
            );
            Display::addFlash(
                Display::return_message(
                    get_lang('ItemCopied').' - '.$url,
                    'success',
                    false
                )
            );
        } else {
            Display::addFlash(Display::return_message(get_lang('ThereWasAnError'), 'error'));
        }
        $url = 'session_list.php';
        if ('custom' !== $listType) {
            $url = 'session_list.php?list_type='.$listType;
        }
        header('Location: '.$url);
        exit;
        break;
    case 'copy_multiple':
        $sessionList = explode(',', $idMultiple);
        foreach ($sessionList as $id) {
            $sessionIdCopied = SessionManager::copy($id);
            if ($sessionIdCopied) {
                $sessionInfo = api_get_session_info($sessionIdCopied);
                Display::addFlash(Display::return_message(get_lang('ItemCopied').' - '.$sessionInfo['name']));
            } else {
                Display::addFlash(Display::return_message(get_lang('ThereWasAnError'), 'error'));
            }
        }
        $url = 'session_list.php';
        if ('custom' !== $listType) {
            $url = 'session_list.php?list_type='.$listType;
        }
        header('Location: '.$url);
        exit;
    case 'export_csv':
        $selectedSessions = explode(',', $idMultiple);

        $csvHeaders = [];
        $csvHeaders[] = get_lang('SessionName');
        $csvHeaders[] = get_lang('SessionStartDate');
        $csvHeaders[] = get_lang('SessionEndDate');
        $csvHeaders[] = get_lang('CourseName');
        $csvHeaders[] = get_lang('OfficialCode');
        if (api_sort_by_first_name()) {
            $csvHeaders[] = get_lang('FirstName');
            $csvHeaders[] = get_lang('LastName');
        } else {
            $csvHeaders[] = get_lang('LastName');
            $csvHeaders[] = get_lang('FirstName');
        }
        $csvHeaders[] = get_lang('Login');
        $csvHeaders[] = get_lang('TrainingTime');
        $csvHeaders[] = get_lang('CourseProgress');
        $csvHeaders[] = get_lang('ExerciseProgress');
        $csvHeaders[] = get_lang('ExerciseAverage');
        $csvHeaders[] = get_lang('Score');
        $csvHeaders[] = get_lang('Score').' - '.get_lang('BestAttempt');
        $csvHeaders[] = get_lang('Student_publication');
        $csvHeaders[] = get_lang('Messages');
        $csvHeaders[] = get_lang('Classes');
        $csvHeaders[] = get_lang('RegistrationDate');
        $csvHeaders[] = get_lang('FirstLoginInCourse');
        $csvHeaders[] = get_lang('LatestLoginInCourse');
        $csvHeaders[] = get_lang('LpFinalizationDate');
        $csvHeaders[] = get_lang('QuizFinalizationDate');
        $csvData = [];
        $i = 0;
        foreach ($selectedSessions as $sessionId) {
            $courses = SessionManager::get_course_list_by_session_id($sessionId);
            if (!empty($courses)) {
                foreach ($courses as $course) {
                    $courseCode = $course['course_code'];
                    $studentList = CourseManager::get_student_list_from_course_code(
                        $courseCode,
                        true,
                        $sessionId
                    );

                    $nbStudents = count($studentList);
                    // Set global variables used by get_user_data()
                    $GLOBALS['user_ids'] = array_keys($studentList);
                    $GLOBALS['session_id'] = $sessionId;
                    $GLOBALS['course_code'] = $courseCode;
                    $GLOBALS['export_csv'] = true;
                    $csvContentInSession = TrackingCourseLog::getUserData(
                        null,
                        $nbStudents,
                        null,
                        null,
                        [],
                        false,
                        true
                    );

                    if (!empty($csvContentInSession)) {
                        $csvData = array_merge($csvData, $csvContentInSession);
                    }
                }
            }
        }

        if (!empty($csvData)) {
            array_unshift($csvData, $csvHeaders);
            $filename = 'export_session_courses_reports_complete_'.api_get_local_time();
            Export::arrayToCsv($csvData, $filename);
            exit;
        }

        break;
    case 'export_multiple':
        $sessionList = explode(',', $idMultiple);
        $tempZipFile = api_get_path(SYS_ARCHIVE_PATH).api_get_unique_id().'.zip';
        $zip = new PclZip($tempZipFile);
        $csvList = [];

        foreach ($sessionList as $sessionItemId) {
            $em = Database::getManager();
            $sessionRepository = $em->getRepository('ChamiloCoreBundle:Session');
            $session = $sessionRepository->find($sessionItemId);

            Session::write('id_session', $sessionItemId);

            if ($session->getNbrCourses() > 0) {
                $courses = $session->getCourses();
                $courseList = [];

                foreach ($courses as $sessionRelCourse) {
                    $courseList[] = $sessionRelCourse->getCourse();
                }

                foreach ($courseList as $course) {
                    $courseId = $course->getId();
                    $courseInfo = api_get_course_info_by_id($courseId);
                    $addExerciseOption = api_get_configuration_value('add_exercise_best_attempt_in_report');
                    $sortByFirstName = api_sort_by_first_name();
                    $bestScoreLabel = get_lang('Score').' - '.get_lang('BestAttempt');
                    $courseCode = $courseInfo['code'];

                    $csvHeaders = [];
                    $csvHeaders[] = get_lang('OfficialCode');

                    if ($sortByFirstName) {
                        $csvHeaders[] = get_lang('FirstName');
                        $csvHeaders[] = get_lang('LastName');
                    } else {
                        $csvHeaders[] = get_lang('LastName');
                        $csvHeaders[] = get_lang('FirstName');
                    }

                    $csvHeaders[] = get_lang('Login');
                    $csvHeaders[] = get_lang('TrainingTime');
                    $csvHeaders[] = get_lang('CourseProgress');
                    $csvHeaders[] = get_lang('ExerciseProgress');
                    $csvHeaders[] = get_lang('ExerciseAverage');
                    $csvHeaders[] = get_lang('Score');
                    $csvHeaders[] = $bestScoreLabel;
                    $exerciseResultHeaders = [];
                    if (!empty($addExerciseOption) && isset($addExerciseOption['courses']) &&
                        isset($addExerciseOption['courses'][$courseCode])
                    ) {
                        foreach ($addExerciseOption['courses'][$courseCode] as $exerciseId) {
                            $exercise = new Exercise();
                            $exercise->read($exerciseId);
                            if ($exercise->iid) {
                                $title = get_lang('Exercise').': '.$exercise->get_formated_title();
                                $table->set_header(
                                    $headerCounter++,
                                    $title,
                                    false
                                );
                                $exerciseResultHeaders[] = $title;
                                $headers['exercise_'.$exercise->iid] = $title;
                            }
                        }
                    }
                    $csvHeaders[] = get_lang('Student_publication');
                    $csvHeaders[] = get_lang('Messages');
                    $csvHeaders[] = get_lang('Classes');
                    $csvHeaders[] = get_lang('RegistrationDate');
                    $csvHeaders[] = get_lang('FirstLoginInCourse');
                    $csvHeaders[] = get_lang('LatestLoginInCourse');

                    $studentList = CourseManager::get_student_list_from_course_code(
                        $courseCode,
                        true,
                        $sessionItemId
                    );
                    $nbStudents = count($studentList);

                    // Set global variables used by get_user_data()
                    $user_ids = array_keys($studentList);
                    $session_id = $sessionItemId;
                    $course_code = $courseCode;
                    $export_csv = true;

                    $csvContentInSession = TrackingCourseLog::getUserData(
                        null,
                        $nbStudents,
                        null,
                        null,
                        []
                    );

                    array_unshift($csvContentInSession, $csvHeaders);

                    $sessionInfo = api_get_session_info($sessionItemId);
                    $sessionDates = SessionManager::parseSessionDates($sessionInfo);

                    array_unshift($csvContentInSession, [get_lang('Date'), $sessionDates['access']]);
                    array_unshift($csvContentInSession, [get_lang('SessionName'), Security::remove_XSS($sessionInfo['name'])]);

                    $csvList[] = [
                        'session_id' => $sessionItemId,
                        'session_name' => $session->getName(),
                        'course_id' => $courseId,
                        'course_name' => $courseInfo['name'],
                        'path' => Export::arrayToCsv($csvContentInSession, '', true),
                    ];
                }
            }
        }

        foreach ($csvList as $csv) {
            $newFileName = $csv['session_id'].'_'.$csv['session_name'];
            $newFileName .= '-'.$csv['course_id'].'_'.$csv['course_name'].'.csv';

            $zip->add(
                [
                    [
                        PCLZIP_ATT_FILE_NAME => $csv['path'],
                        PCLZIP_ATT_FILE_NEW_FULL_NAME => $newFileName,
                    ],
                ],
                'fixDocumentNameCallback'
            );
            unlink($csv['path']);
        }

        DocumentManager::file_send_for_download(
            $tempZipFile,
            true
        );
        unlink($tempZipFile);
        exit;
        break;
}

$tool_name = get_lang('SessionList');
Display::display_header($tool_name);
$courseId = isset($_GET['course_id']) ? $_GET['course_id'] : null;

$sessionFilter = new FormValidator(
    'course_filter',
    'get',
    '',
    '',
    [],
    FormValidator::LAYOUT_INLINE
);
$courseSelect = $sessionFilter->addElement(
    'select_ajax',
    'course_name',
    get_lang('SearchCourse'),
    null,
    ['url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_course']
);

if (!empty($courseId)) {
    $courseInfo = api_get_course_info_by_id($courseId);
    $parents = CourseCategory::getParentsToString($courseInfo['categoryCode']);
    $courseSelect->addOption($parents.$courseInfo['title'], $courseInfo['code'], ['selected' => 'selected']);
}

$url = api_get_self();
$actions = '
<script>
$(function() {
    $("#course_name").on("change", function() {
       var courseId = $(this).val();

       if (!courseId) {
            return;
       }

       window.location = "'.$url.'?course_id="+courseId;
    });
});
</script>';

// jqgrid will use this URL to do the selects
if (!empty($courseId)) {
    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_sessions&course_id='.$courseId;
} else {
    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_sessions';
}

if (isset($_REQUEST['keyword'])) {
    // Begin with see the searchOper param
    $filter = new stdClass();
    $filter->groupOp = 'OR';
    $rule = new stdClass();
    $rule->field = 'category_name';
    $rule->op = 'in';
    $rule->data = Security::remove_XSS($_REQUEST['keyword']);
    $filter->rules[] = $rule;
    $filter->groupOp = 'OR';
    $filter = json_encode($filter);
    $url = api_get_path(WEB_AJAX_PATH).
        'model.ajax.php?a=get_sessions&_force_search=true&rows=20&page=1&sidx=&sord=asc&filters='.$filter.'&searchField=s.name&searchString='.Security::remove_XSS($_REQUEST['keyword']).'&searchOper=in';
}

if (isset($_REQUEST['id_category'])) {
    $sessionCategory = SessionManager::get_session_category($_REQUEST['id_category']);
    if (!empty($sessionCategory)) {
        // Begin with see the searchOper param
        $url = api_get_path(WEB_AJAX_PATH).
            'model.ajax.php?a=get_sessions&_force_search=true&rows=20&page=1&sidx=&sord=asc&filters=&searchField=sc.name&searchString='.Security::remove_XSS($sessionCategory['name']).'&searchOper=in';
    }
}

$url .= '&list_type='.$listType;
$result = SessionManager::getGridColumns($listType);
$columns = $result['columns'];
$column_model = $result['column_model'];
$extra_params['autowidth'] = 'true';
$extra_params['height'] = 'auto';

switch ($listType) {
    case 'custom':
        $extra_params['sortname'] = 'display_end_date';
        $extra_params['sortorder'] = 'desc';
        break;
}

if (!isset($_GET['keyword'])) {
    $extra_params['postData'] = [
        'filters' => [
            'groupOp' => 'AND',
            'rules' => $result['rules'],
        ],
    ];
}

$hideSearch = api_get_configuration_value('hide_search_form_in_session_list');
$copySessionContentLink = '';
if ($addSessionContent) {
    $copySessionContentLink = '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES))."\'".')) return false;" href="session_list.php?copy_session_content=1&list_type='.$listType.'&action=copy&idChecked=\'+options.rowId+\'">'.
    Display::return_icon('copy.png', get_lang('CopyWithSessionContent'), '', ICON_SIZE_SMALL).'</a>';
}

// With this function we can add actions to the jgrid (edit, delete, etc)
$action_links = 'function action_formatter(cellvalue, options, rowObject) {
     return \'<a href="session_edit.php?page=resume_session.php&id=\'+options.rowId+\'">'.
    Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a>'.
    '&nbsp;<a href="add_users_to_session.php?page=session_list.php&id_session=\'+options.rowId+\'">'.
    Display::return_icon('user_subscribe_session.png', get_lang('SubscribeUsersToSession'), '', ICON_SIZE_SMALL).'</a>'.
    '&nbsp;<a href="add_courses_to_session.php?page=session_list.php&id_session=\'+options.rowId+\'">'.
    Display::return_icon('courses_to_session.png', get_lang('SubscribeCoursesToSession'), '', ICON_SIZE_SMALL).'</a>'.
    '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES))."\'".')) return false;" href="session_list.php?list_type='.$listType.'&action=copy&idChecked=\'+options.rowId+\'">'.
    Display::return_icon('copy_partial.png', get_lang('Copy'), '', ICON_SIZE_SMALL).'</a>'.
    $copySessionContentLink.
    '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES))."\'".')) return false;" href="session_list.php?list_type='.$listType.'&action=delete&idChecked=\'+options.rowId+\'">'.
    Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>'.
    '\';
}';

$urlAjaxExtraField = api_get_path(WEB_AJAX_PATH).'extra_field.ajax.php?1=1';
$allowOrder = api_get_configuration_value('session_list_order');
$orderUrl = api_get_path(WEB_AJAX_PATH).'session.ajax.php?a=order';
$deleteUrl = api_get_self().'?list_type='.$listType.'&action=delete_multiple';
$copyUrl = api_get_self().'?list_type='.$listType.'&action=copy_multiple';
$exportUrl = api_get_self().'?list_type='.$listType.'&action=export_multiple';
$exportCsvUrl = api_get_self().'?list_type='.$listType.'&action=export_csv';
$extra_params['multiselect'] = true;

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

            //Great hack
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
                $column_model,
                $extra_params,
                [],
                $action_links,
                true
            );
            ?>

            setSearchSelect("status");
            var grid = $("#sessions");
            var prmSearch = {
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
                                    if (subvalue.data == undefined) {
                                    }
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

            <?php if ($allowOrder) {
                ?>
            options = {
                update: function (e, ui) {
                    var rowNum = jQuery("#sessions").getGridParam('rowNum');
                    var page = jQuery("#sessions").getGridParam('page');
                    page = page - 1;
                    var start = rowNum * page;
                    var list = jQuery('#sessions').jqGrid('getRowData');
                    var orderList = [];
                    $(list).each(function(index, e) {
                        index = index + start;
                        orderList.push({'order':index, 'id': e.id});
                    });
                    orderList = JSON.stringify(orderList);
                    $.get("<?php echo $orderUrl; ?>", "order="+orderList, function (result) {
                    });
                }
            };

            // Sortable rows
            grid.jqGrid('sortableRows', options);
            <?php
            } ?>

            grid.jqGrid('navGrid','#sessions_pager',
                {edit:false,add:false,del:true},
                {height:280,reloadAfterSubmit:false}, // edit options
                {height:280,reloadAfterSubmit:false}, // add options
                {reloadAfterSubmit:true, url: '<?php echo $deleteUrl; ?>' }, // del options
                prmSearch
            ).navButtonAdd('#sessions_pager',{
                caption:"<?php echo addslashes(Display::return_icon('copy.png', get_lang('Copy'), '', ICON_SIZE_SMALL)); ?>",
                buttonicon:"ui-icon ui-icon-plus",
                onClickButton: function(a) {
                    var list = $("#sessions").jqGrid('getGridParam', 'selarrrow');
                    if (list.length) {
                        window.location.replace('<?php echo $copyUrl; ?>&id='+list.join(','));
                    } else {
                        alert("<?php echo addslashes(get_lang('SelectAnOption')); ?>");
                    }
                }
            }).navButtonAdd('#sessions_pager',{
                caption:"<?php echo addslashes(Display::return_icon('save_pack.png', get_lang('ExportCoursesReports'), '', ICON_SIZE_SMALL)); ?>",
                buttonicon:"ui-icon ui-icon-plus",
                onClickButton: function(a) {
                    var list = $("#sessions").jqGrid('getGridParam', 'selarrrow');
                    if (list.length) {
                        window.location.replace('<?php echo $exportUrl; ?>&id='+list.join(','));
                    } else {
                        alert("<?php echo addslashes(get_lang('SelectAnOption')); ?>");
                    }
                },
                position:"last"
            }).navButtonAdd('#sessions_pager',{
                caption:"<?php echo addslashes(Display::return_icon('export_csv.png', get_lang('ExportCoursesReportsComplete'), '', ICON_SIZE_SMALL)); ?>",
                buttonicon:"ui-icon ui-icon-plus",
                onClickButton: function(a) {
                    var list = $("#sessions").jqGrid('getGridParam', 'selarrrow');
                    if (list.length) {
                        window.location.replace('<?php echo $exportCsvUrl; ?>&id='+list.join(','));
                    } else {
                        alert("<?php echo addslashes(get_lang('SelectAnOption')); ?>");
                    }
                },
                position:"last"
            });

            <?php
            // Create the searching dialog.
            if ($hideSearch !== true) {
                echo 'grid.searchGrid(prmSearch);';
            }
            ?>

            // Fixes search table.
            var searchDialogAll = $("#fbox_"+grid[0].id);
            searchDialogAll.addClass("table");
            var searchDialog = $("#searchmodfbox_"+grid[0].id);
            searchDialog.addClass("ui-jqgrid ui-widget ui-widget-content ui-corner-all");
            searchDialog.css({
                position: "absolute",
                "z-index": "100",
                "float": "left",
                "top": "55%",
                "left": "25%",
                "padding": "5px",
                "border": "1px solid #CCC"
            })
            var gbox = $("#gbox_"+grid[0].id);
            gbox.before(searchDialog);
            gbox.css({clear:"left"});

            // Select first elements by default
            $('.input-elm').each(function(){
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
    <div class="actions">
<?php

echo '<a href="'.api_get_path(WEB_CODE_PATH).'session/session_add.php">'.
    Display::return_icon('new_session.png', get_lang('AddSession'), '', ICON_SIZE_MEDIUM).'</a>';
if (api_is_platform_admin()) {
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'session/add_many_session_to_category.php">'.
        Display::return_icon('session_to_category.png', get_lang('AddSessionsInCategories'), '', ICON_SIZE_MEDIUM).'</a>';
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'session/session_category_list.php">'.
        Display::return_icon('folder.png', get_lang('ListSessionCategory'), '', ICON_SIZE_MEDIUM).'</a>';
}

echo $actions;
if (api_is_platform_admin()) {
    echo '<div class="pull-right">';
    // Create a search-box
    $form = new FormValidator(
        'search_simple',
        'get',
        api_get_self().'?list_type='.$listType,
        '',
        [],
        FormValidator::LAYOUT_INLINE
    );
    $form->addElement('text', 'keyword', null, ['aria-label' => get_lang('Search')]);
    $form->addHidden('list_type', $listType);
    $form->addButtonSearch(get_lang('Search'));
    $form->display();
    echo '</div>';

    echo '<div class="pull-right">';
    echo $sessionFilter->returnForm();
    echo '</div>';
}
echo '</div>';

echo SessionManager::getSessionListTabs($listType);

echo '<div id="session-table" class="table-responsive">';
echo Display::grid_html('sessions');
echo '</div>';

Display::display_footer();
