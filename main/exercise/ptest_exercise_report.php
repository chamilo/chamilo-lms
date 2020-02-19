<?php
/* For licensing terms, see /licence.txt */

/**
 * Personality test report: This script shows the attemp of test for administrators and students.
 *
 * @package chamilo.exercise
 *
 * @author Jose Angel Ruiz (NOSOLORED)
 *
 * @todo fix excel export
 */
require_once __DIR__.'/../inc/global.inc.php';

// Setting the tabs
$this_section = SECTION_COURSES;
$htmlHeadXtra[] = api_get_jqgrid_js();

$filterUser = isset($_REQUEST['filter_by_user']) ? (int) $_REQUEST['filter_by_user'] : null;
$isBossOfStudent = false;
if (api_is_student_boss() && !empty($filterUser)) {
    // Check if boss has access to user info.
    if (UserManager::userIsBossOfStudent(api_get_user_id(), $filterUser)) {
        $isBossOfStudent = true;
    } else {
        api_not_allowed(true);
    }
} else {
    api_protect_course_script(true, false, true);
}

$limitTeacherAccess = api_get_configuration_value('limit_exercise_teacher_access');

if ($limitTeacherAccess && !api_is_platform_admin()) {
    api_not_allowed(true);
}

$_course = api_get_course_info();

// document path
$documentPath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
$origin = api_get_origin();
$isAllowedToEdit = api_is_allowed_to_edit(null, true) ||
    api_is_drh() ||
    api_is_student_boss() ||
    api_is_session_admin();
$isTutor = api_is_allowed_to_edit(true);

$TBL_TRACK_EXERCISES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$TBL_TRACK_ATTEMPT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
$TBL_TRACK_ATTEMPT_RECORDING = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
$TBL_LP_ITEM_VIEW = Database::get_course_table(TABLE_LP_ITEM_VIEW);
$allowCoachFeedbackExercises = api_get_setting('allow_coach_feedback_exercises') === 'true';
$courseId = api_get_course_int_id();
$exerciseId = isset($_REQUEST['exerciseId']) ? (int) $_REQUEST['exerciseId'] : 0;
$locked = api_resource_is_locked_by_gradebook($exerciseId, LINK_EXERCISE);
$sessionId = api_get_session_id();

if (empty($exerciseId)) {
    api_not_allowed(true);
}

$blockPage = true;
if (empty($sessionId)) {
    if ($isAllowedToEdit) {
        $blockPage = false;
    }
} else {
    if ($allowCoachFeedbackExercises && api_is_coach($sessionId, $courseId)) {
        $blockPage = false;
    } else {
        if ($isAllowedToEdit) {
            $blockPage = false;
        }
    }
}

if ($blockPage) {
    api_not_allowed(true);
}

if (!empty($exerciseId)) {
    $parameters['exerciseId'] = $exerciseId;
}

if (!empty($_GET['path'])) {
    $parameters['path'] = Security::remove_XSS($_GET['path']);
}

$objExerciseTmp = new Exercise();
$exerciseExists = $objExerciseTmp->read($exerciseId);

$actions = null;
if ($isAllowedToEdit && $origin != 'learnpath') {
    // the form
    if (api_is_platform_admin() || api_is_course_admin() ||
        api_is_course_tutor() || api_is_session_general_coach()
    ) {
        $actions .= '<a href="exercise.php?'.api_get_cidreq().'">'.
            Display::return_icon('back.png', get_lang('GoBackToQuestionList'), '', ICON_SIZE_MEDIUM).'</a>';
        $actions .= '<a href="ptest_stats.php?'.api_get_cidreq().'&exerciseId='.$exerciseId.'">'.
            Display::return_icon('statistics.png', get_lang('ReportByQuestion'), '', ICON_SIZE_MEDIUM).'</a>';
        $actions .= '<a href="ptest_stats_graph.php?'.api_get_cidreq().'&exerciseId='.$exerciseId.'">'.
            Display::return_icon('survey_reporting_question.png', get_lang('ExerciseGraph'), '', ICON_SIZE_MEDIUM);
        $actions .= '</a>';
        // clean result before a selected date icon
        $actions .= Display::url(
            Display::return_icon(
                'clean_before_date.png',
                get_lang('CleanStudentsResultsBeforeDate'),
                '',
                ICON_SIZE_MEDIUM
            ),
            '#',
            ['onclick' => 'javascript:display_date_picker()']
        );
        // clean result before a selected date datepicker popup
        $actions .= Display::span(
            Display::input(
                'input',
                'datepicker_start',
                get_lang('SelectADateOnTheCalendar'),
                [
                    'onmouseover' => 'datepicker_input_mouseover()',
                    'id' => 'datepicker_start',
                    'onchange' => 'datepicker_input_changed()',
                    'readonly' => 'readonly',
                ]
            ).
            Display::button(
                'delete',
                get_lang('Delete'),
                ['onclick' => 'submit_datepicker()']
            ),
            ['style' => 'display:none', 'id' => 'datepicker_span']
        );
    }
} else {
    $actions .= '<a href="exercise.php">'.
        Display::return_icon(
            'back.png',
            get_lang('GoBackToQuestionList'),
            '',
            ICON_SIZE_MEDIUM
        ).
    '</a>';
}

// Deleting an attempt
if (($isAllowedToEdit || $isTutor || api_is_coach()) &&
    isset($_GET['delete']) && $_GET['delete'] === 'delete' &&
    !empty($_GET['did']) && $locked == false
) {
    $exeId = (int) $_GET['did'];
    if (!empty($exeId)) {
        $sql = 'DELETE FROM '.$TBL_TRACK_EXERCISES.' WHERE exe_id = '.$exeId;
        Database::query($sql);
        $sql = 'DELETE FROM '.$TBL_TRACK_ATTEMPT.' WHERE exe_id = '.$exeId;
        Database::query($sql);

        Event::addEvent(
            LOG_EXERCISE_ATTEMPT_DELETE,
            LOG_EXERCISE_ATTEMPT,
            $exeId,
            api_get_utc_datetime()
        );
        header('Location: ptest_exercise_report.php?'.api_get_cidreq().'&exerciseId='.$exerciseId);
        exit;
    }
}

if ($isAllowedToEdit || $isTutor) {
    $interbreadcrumb[] = [
        'url' => 'exercise.php?'.api_get_cidreq(),
        'name' => get_lang('Exercises'),
    ];

    $nameTools = get_lang('Stats');
    if ($exerciseExists) {
        $interbreadcrumb[] = [
            'url' => '#',
            'name' => $objExerciseTmp->selectTitle(true),
        ];
    }
} else {
    $interbreadcrumb[] = [
        'url' => 'exercise.php?'.api_get_cidreq(),
        'name' => get_lang('Exercises'),
    ];
    if ($exerciseExists) {
        $nameTools = get_lang('Results').': '.$objExerciseTmp->selectTitle(true);
    }
}

if (($isAllowedToEdit || $isTutor || api_is_coach()) &&
    isset($_GET['a']) && $_GET['a'] === 'close' &&
    !empty($_GET['id']) && $locked == false
) {
    // Close the user attempt otherwise left pending
    $exeId = (int) $_GET['id'];
    $sql = "UPDATE $TBL_TRACK_EXERCISES SET status = '' 
            WHERE exe_id = $exeId AND status = 'incomplete'";
    Database::query($sql);
}

Display::display_header($nameTools);

// Clean all results for this test before the selected date
if (($isAllowedToEdit || $isTutor || api_is_coach()) &&
    isset($_GET['delete_before_date']) && $locked == false
) {
    // ask for the date
    $check = Security::check_token('get');
    if ($check) {
        $objExerciseTmp = new Exercise();
        if ($objExerciseTmp->read($exerciseId)) {
            $count = $objExerciseTmp->cleanResults(
                true,
                $_GET['delete_before_date'].' 23:59:59'
            );
            echo Display::return_message(
                sprintf(get_lang('XResultsCleaned'), $count),
                'confirm'
            );
        }
    }
}

// Security token to protect deletion
$token = Security::get_token();
$actions = Display::div($actions, ['class' => 'actions']);

echo $actions;
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?';
$url .= 'a=get_ptest_exercise_results&exerciseId='.$exerciseId.'&filter_by_user='.$filterUser.'&'.api_get_cidreq();
$actionLinks = '';
// Generating group list
$group_list = GroupManager::get_group_list();
$groupParameters = [
    'group_all:'.get_lang('All'),
    'group_none:'.get_lang('None'),
];

foreach ($group_list as $group) {
    $groupParameters[] = $group['id'].':'.$group['name'];
}
if (!empty($groupParameters)) {
    $groupParameters = implode(';', $groupParameters);
}

$officialCodeInList = api_get_setting('show_official_code_exercise_result_list');

if ($isAllowedToEdit || $isTutor) {
    // The order is important you need to check the the $column variable in the model.ajax.php file
    $columns = [
        get_lang('FirstName'),
        get_lang('LastName'),
        get_lang('LoginName'),
        get_lang('Group'),
        get_lang('Duration').' ('.get_lang('MinMinute').')',
        get_lang('StartDate'),
        get_lang('EndDate'),
        get_lang('IP'),
        get_lang('ToolLearnpath'),
        get_lang('Actions'),
    ];

    if ($officialCodeInList === 'true') {
        $columns = array_merge([get_lang('OfficialCode')], $columns);
    }

    // Column config
    $columnModel = [
        ['name' => 'firstname', 'index' => 'firstname', 'width' => '50', 'align' => 'left', 'search' => 'true'],
        [
            'name' => 'lastname',
            'index' => 'lastname',
            'width' => '50',
            'align' => 'left',
            'formatter' => 'action_formatter',
            'search' => 'true'
        ],
        [
            'name' => 'login',
            'index' => 'username',
            'width' => '40',
            'align' => 'left',
            'search' => 'true',
            'hidden' => api_get_configuration_value('exercise_attempts_report_show_username') ? 'false' : 'true',
        ],
        [
            'name' => 'group_name',
            'index' => 'group_id',
            'width' => '40',
            'align' => 'left',
            'search' => 'true',
            'stype' => 'select',
            //for the bottom bar
            'searchoptions' => [
                'defaultValue' => 'group_all',
                'value' => $groupParameters,
            ],
            //for the top bar
            'editoptions' => ['value' => $groupParameters],
        ],
        ['name' => 'duration', 'index' => 'exe_duration', 'width' => '30', 'align' => 'left', 'search' => 'true'],
        ['name' => 'start_date', 'index' => 'start_date', 'width' => '60', 'align' => 'left', 'search' => 'true'],
        ['name' => 'exe_date', 'index' => 'exe_date', 'width' => '60', 'align' => 'left', 'search' => 'true'],
        ['name' => 'ip', 'index' => 'user_ip', 'width' => '40', 'align' => 'center', 'search' => 'true'],
        ['name' => 'lp', 'index' => 'orig_lp_id', 'width' => '60', 'align' => 'left', 'search' => 'false'],
        [
            'name' => 'actions',
            'index' => 'actions',
            'width' => '60',
            'align' => 'left',
            'search' => 'false',
            'sortable' => 'false'
        ],
    ];

    if ($officialCodeInList === 'true') {
        $officialCodeRow = [
            'name' => 'official_code',
            'index' => 'official_code',
            'width' => '50',
            'align' => 'left',
            'search' => 'true'
        ];
        $columnModel = array_merge([$officialCodeRow], $columnModel);
    }

    $actionLinks = '
    // add username as title in lastname filed - ref 4226
    function action_formatter(cellvalue, options, rowObject) {
        // rowObject is firstname,lastname,login,... get the third word
        var loginx = "'.api_htmlentities(sprintf(get_lang('LoginX'), ':::'), ENT_QUOTES).'";
        var tabLoginx = loginx.split(/:::/);
        // tabLoginx[0] is before and tabLoginx[1] is after :::
        // may be empty string but is defined
        return "<span title=\""+tabLoginx[0]+rowObject[2]+tabLoginx[1]+"\">"+cellvalue+"</span>";
    }';
}

$extraParams['autowidth'] = 'true';
$extraParams['height'] = 'auto';
$extraParams['gridComplete'] = "
    defaultGroupId = Cookies.get('default_group_".$exerciseId."');
    if (typeof defaultGroupId !== 'undefined') {
        $('#gs_group_name').val(defaultGroupId);
    }
";

$extraParams['beforeRequest'] = "
var defaultGroupId = $('#gs_group_name').val();

// Load from group menu
if (typeof defaultGroupId !== 'undefined') {
    Cookies.set('default_group_".$exerciseId."', defaultGroupId);
} else {
    // get from cookies
    defaultGroupId = Cookies.get('default_group_".$exerciseId."');
    $('#gs_group_name').val(defaultGroupId);    
}

if (typeof defaultGroupId !== 'undefined') {
    var posted_data = $(\"#results\").jqGrid('getGridParam', 'postData');
    var extraFilter = ',{\"field\":\"group_id\",\"op\":\"eq\",\"data\":\"'+ defaultGroupId +'\"}]}';
    var filters = posted_data.filters;
    var stringObj = new String(filters);
    stringObj.replace(']}', extraFilter);

    posted_data['group_id_in_toolbar'] = defaultGroupId;
    $(this).jqGrid('setGridParam', 'postData', posted_data);
}
";

$gridJs = Display::grid_js(
    'results',
    $url,
    $columns,
    $columnModel,
    $extraParams,
    [],
    $actionLinks,
    true
);

?>
<script>
    function exportExcel()
    {
        var mya = $("#results").getDataIDs(); // Get All IDs
        var data = $("#results").getRowData(mya[0]); // Get First row to get the labels
        var colNames = new Array();
        var ii = 0;
        for (var i in data) {
            colNames[ii++] = i;
        }
        // capture col names
        var html = "";
        for (i = 0; i < mya.length; i++) {
            data = $("#results").getRowData(mya[i]); // get each row
            for (j = 0; j < colNames.length; j++) {
                html = html + data[colNames[j]] + ","; // output each column as tab delimited
            }
            html = html + "\n";  // output each row with end of line
        }
        html = html + "\n";  // end of line at the end
        var form = $("#export_report_form");
        $("#csvBuffer").attr('value', html);
        form.target='_blank';
        form.submit();
    }

    $(function() {
        $("#datepicker_start").datepicker({
            defaultDate: "",
            changeMonth: false,
            numberOfMonths: 1
        });
        <?php
        echo $gridJs;

        if ($isAllowedToEdit || $isTutor) {
            ?>
            $("#results").jqGrid(
                'navGrid',
                '#results_pager', {
                    view:true, edit:false, add:false, del:false, excel:false
                },
                {height:280, reloadAfterSubmit:false}, // view options
                {height:280, reloadAfterSubmit:false}, // edit options
                {height:280, reloadAfterSubmit:false}, // add options
                {reloadAfterSubmit: false}, // del options
                {width:500}, // search options
            );

            var sgrid = $("#results")[0];

            // Update group
            var defaultGroupId = Cookies.get('default_group_<?php echo $exerciseId; ?>');
            $('#gs_group_name').val(defaultGroupId);
            // Adding search options
            var options = {
                'stringResult': true,
                'autosearch' : true,
                'searchOnEnter': false,
                afterSearch: function () {
                    $('#gs_group_name').on('change', function() {
                        var defaultGroupId = $('#gs_group_name').val();
                        // Save default group id
                        Cookies.set('default_group_<?php echo $exerciseId; ?>', defaultGroupId);
                    });
                }
            }
            jQuery("#results").jqGrid('filterToolbar', options);
            sgrid.triggerToolbar();
            $('#results').on('click', 'a.exercise-recalculate', function (e) {
                e.preventDefault();
                if (!$(this).data('user') || !$(this).data('exercise') || !$(this).data('id')) {
                    return;
                }
                var url = '<?php echo api_get_path(WEB_CODE_PATH); ?>exercise/recalculate.php?'+
                    '<?php echo api_get_cidreq(); ?>';
                var recalculateXhr = $.post(url, $(this).data());
                $.when(recalculateXhr).done(function (response) {
                    $('#results').trigger('reloadGrid');
                });
            });
        <?php } ?>
        });
    // datepicker functions
    var datapickerInputModified = false;
    /**
     * return true if the datepicker input has been modified
     */
    function datepicker_input_changed() {
        datapickerInputModified = true;
    }

    /**
    * disply the datepicker calendar on mouse over the input
    */
    function datepicker_input_mouseover() {
        $('#datepicker_start').datepicker( "show" );
    }

    /**
    * display or hide the datepicker input, calendar and button
    */
    function display_date_picker() {
        if (!$('#datepicker_span').is(":visible")) {
            $('#datepicker_span').show();
            $('#datepicker_start').datepicker( "show" );
        } else {
            $('#datepicker_start').datepicker( "hide" );
            $('#datepicker_span').hide();
        }
    }

    /**
    * confirm deletion
    */
    function submit_datepicker() {
        if (datapickerInputModified) {
            var dateTypeVar = $('#datepicker_start').datepicker('getDate');
            var dateForBDD = $.datepicker.formatDate('yy-mm-dd', dateTypeVar);
            // Format the date for confirm box
            var dateFormat = $( "#datepicker_start" ).datepicker( "option", "dateFormat" );
            var selectedDate = $.datepicker.formatDate(dateFormat, dateTypeVar);
            if (confirm("
                <?php echo convert_double_quote_to_single(get_lang('AreYouSureDeleteTestResultBeforeDateD')).' '; ?>"
                 + selectedDate)
            ) {
                self.location.href = "ptest_exercise_report.php?<?php echo api_get_cidreq(); ?>" +
                "&exerciseId=<?php echo $exerciseId; ?>" +
                "&delete_before_date="+dateForBDD+"&sec_token=<?php echo $token; ?>";
            }
        }
    }
</script>
<form id="export_report_form" method="post" action="ptest_exercise_report.php?<?php echo api_get_cidreq(); ?>">
    <input type="hidden" name="csvBuffer" id="csvBuffer" value="" />
    <input type="hidden" name="export_report" id="export_report" value="1" />
    <input type="hidden" name="exerciseId" id="exerciseId" value="<?php echo $exerciseId; ?>" />
</form>

<?php
echo Display::grid_html('results');
Display::display_footer();
