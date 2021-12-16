<?php

/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

// Setting the tabs
$this_section = SECTION_COURSES;
$htmlHeadXtra[] = api_get_jqgrid_js();

$filter_user = isset($_REQUEST['filter_by_user']) ? (int) $_REQUEST['filter_by_user'] : null;
$courseId = isset($_REQUEST['course_id']) ? (int) $_REQUEST['course_id'] : 0;
$exerciseId = isset($_REQUEST['exercise_id']) ? (int) $_REQUEST['exercise_id'] : 0;
$statusId = isset($_REQUEST['status']) ? (int) $_REQUEST['status'] : 0;
$exportXls = isset($_REQUEST['export_xls']) && !empty($_REQUEST['export_xls']) ? (int) $_REQUEST['export_xls'] : 0;
$action = $_REQUEST['a'] ?? null;

api_block_anonymous_users();

// Only teachers.
if (false === api_is_teacher()) {
    api_not_allowed(true);
}

switch ($action) {
    case 'get_exercise_by_course':
        $data = [];
        $results = ExerciseLib::get_all_exercises_for_course_id(
            null,
            0,
            $courseId,
            false
        );
        if (!empty($results)) {
            foreach ($results as $exercise) {
                $data[] = ['id' => $exercise['iid'], 'text' => html_entity_decode($exercise['title'])];
            }
        }

        echo json_encode($data);
        exit;
        break;
}

$userId = api_get_user_id();
$origin = api_get_origin();

$TBL_TRACK_EXERCISES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$TBL_TRACK_ATTEMPT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
$TBL_TRACK_ATTEMPT_RECORDING = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
$TBL_LP_ITEM_VIEW = Database::get_course_table(TABLE_LP_ITEM_VIEW);
$allowCoachFeedbackExercises = api_get_setting('allow_coach_feedback_exercises') === 'true';
$documentPath = null;

if (!empty($_GET['path'])) {
    $parameters['path'] = Security::remove_XSS($_GET['path']);
}

if (!empty($_REQUEST['export_report']) && $_REQUEST['export_report'] == '1') {
    if (api_is_platform_admin() || api_is_course_admin() ||
        api_is_course_tutor() || api_is_session_general_coach()
    ) {
        $loadExtraData = false;
        if (isset($_REQUEST['extra_data']) && $_REQUEST['extra_data'] == 1) {
            $loadExtraData = true;
        }

        $includeAllUsers = false;
        if (isset($_REQUEST['include_all_users']) &&
            $_REQUEST['include_all_users'] == 1
        ) {
            $includeAllUsers = true;
        }

        $onlyBestAttempts = false;
        if (isset($_REQUEST['only_best_attempts']) &&
            $_REQUEST['only_best_attempts'] == 1
        ) {
            $onlyBestAttempts = true;
        }

        require_once 'exercise_result.class.php';
        $export = new ExerciseResult();
        $export->setIncludeAllUsers($includeAllUsers);
        $export->setOnlyBestAttempts($onlyBestAttempts);

        switch ($_GET['export_format']) {
            case 'xls':
                $export->exportCompleteReportXLS(
                    $documentPath,
                    null,
                    $loadExtraData,
                    null,
                    $exerciseId
                );
                exit;
                break;
            case 'csv':
            default:
                $export->exportCompleteReportCSV(
                    $documentPath,
                    null,
                    $loadExtraData,
                    null,
                    $exerciseId
                );
                exit;
                break;
        }
    } else {
        api_not_allowed(true);
    }
}

$htmlHeadXtra[] = '<script>
    $(function() {
        $("#export-xls").bind("click", function(e) {
            e.preventDefault();
            var input = $("<input>", {
                type: "hidden",
                name: "export_xls",
                value: "1"
            });
            $("#pending").append(input);
            $("#pending").submit();
        });
        $("#pending_pendingSubmit").bind("click", function(e) {
            e.preventDefault();
            if ($("input[name=\"export_xls\"]").length > 0) {
                $("input[name=\"export_xls\"]").remove();
            }
            $("#pending").submit();
        });

        $("select#course_id").on("change", function () {
            var courseId = parseInt(this.value, 10);
            updateExerciseList(courseId);
        });
    });
    function updateExerciseList(courseId) {
        if (courseId == 0) {
            return;
        }
        var $selectExercise = $("select#exercise_id");
        $selectExercise.empty();

        $.get("'.api_get_self().'", {
            a: "get_exercise_by_course",
            course_id: courseId,
        }, function (exerciseList) {
            $("<option>", {
                value: 0,
                text: "'.get_lang('All').'"
            }).appendTo($selectExercise);

            if (exerciseList.length > 0) {
                $.each(exerciseList, function (index, exercise) {
                    $("<option>", {
                        value: exercise.id,
                        text: exercise.text
                    }).appendTo($selectExercise);
                });
                $selectExercise.find("option[value=\''.$exerciseId.'\']").attr("selected",true);
            }
            $selectExercise.selectpicker("refresh");
        }, "json");
    }
</script>';

if ($exportXls) {
    ExerciseLib::exportPendingAttemptsToExcel($_REQUEST);
}

Display::display_header(get_lang('PendingAttempts'));
$actions = '';
$actions .= Display::url(
    Display::return_icon('excel.png', get_lang('ExportAsXLS'), [], ICON_SIZE_MEDIUM),
    '#',
    ['id' => 'export-xls']
);

echo Display::div($actions, ['class' => 'actions']);
$token = Security::get_token();
$extra = '<script>
    $(function() {
        $( "#dialog:ui-dialog" ).dialog( "destroy" );
        $( "#dialog-confirm" ).dialog({
                autoOpen: false,
                show: "blind",
                resizable: false,
                height:300,
                modal: true
         });

        $("#export_opener").click(function() {
            var targetUrl = $(this).attr("href");
            $( "#dialog-confirm" ).dialog({
                width:400,
                height:300,
                buttons: {
                    "'.addslashes(get_lang('Download')).'": function() {
                        var export_format = $("input[name=export_format]:checked").val();
                        var extra_data  = $("input[name=load_extra_data]:checked").val();
                        var includeAllUsers  = $("input[name=include_all_users]:checked").val();
                        var attempts = $("input[name=only_best_attempts]:checked").val();
                        location.href = targetUrl+"&export_format="+export_format+"&extra_data="+extra_data+"&include_all_users="+includeAllUsers+"&only_best_attempts="+attempts;
                        $( this ).dialog( "close" );
                    }
                }
            });
            $( "#dialog-confirm" ).dialog("open");
            return false;
        });
    });
    </script>';

$extra .= '<div id="dialog-confirm" title="'.get_lang('ConfirmYourChoice').'">';
$form = new FormValidator(
    'report',
    'post',
    null,
    null,
    ['class' => 'form-vertical']
);
$form->addElement(
    'radio',
    'export_format',
    null,
    get_lang('ExportAsCSV'),
    'csv',
    ['id' => 'export_format_csv_label']
);
$form->addElement(
    'radio',
    'export_format',
    null,
    get_lang('ExportAsXLS'),
    'xls',
    ['id' => 'export_format_xls_label']
);
$form->addElement(
    'checkbox',
    'load_extra_data',
    null,
    get_lang('LoadExtraData'),
    '0',
    ['id' => 'export_format_xls_label']
);
$form->addElement(
    'checkbox',
    'include_all_users',
    null,
    get_lang('IncludeAllUsers'),
    '0'
);
$form->addElement(
    'checkbox',
    'only_best_attempts',
    null,
    get_lang('OnlyBestAttempts'),
    '0'
);
$form->setDefaults(['export_format' => 'csv']);
$extra .= $form->returnForm();
$extra .= '</div>';

echo $extra;

$showAttemptsInSessions = api_get_configuration_value('show_exercise_attempts_in_all_user_sessions');
$courses = CourseManager::get_courses_list_by_user_id($userId, $showAttemptsInSessions, false, false);

$form = new FormValidator('pending', 'GET');
$courses = array_column($courses, 'title', 'real_id');
$form->addSelect('course_id', get_lang('Course'), $courses, ['placeholder' => get_lang('All'), 'id' => 'course_id']);

$form->addSelect(
    'exercise_id',
    get_lang('Exercise'),
    [],
    [
        'placeholder' => get_lang('All'),
        'id' => 'exercise_id',
    ]
);

$status = [
    1 => get_lang('All'),
    2 => get_lang('Validated'),
    3 => get_lang('NotValidated'),
    4 => get_lang('Unclosed'),
    5 => get_lang('Ongoing'),
];

$form->addSelect('status', get_lang('Status'), $status);
$form->addButtonSearch(get_lang('Search'), 'pendingSubmit');
$content = $form->returnForm();

echo $content;

if (empty($statusId)) {
    Display::display_footer();
    exit;
}

$url = api_get_path(WEB_AJAX_PATH).
    'model.ajax.php?a=get_exercise_pending_results&filter_by_user='.$filter_user.
    '&course_id='.$courseId.'&exercise_id='.$exerciseId.'&status='.$statusId;
$action_links = '';

$officialCodeInList = api_get_setting('show_official_code_exercise_result_list');

// The order is important you need to check the the $column variable in the model.ajax.php file
$columns = [
    get_lang('Course'),
    get_lang('Exercise'),
    get_lang('FirstName'),
    get_lang('LastName'),
    get_lang('LoginName'),
    get_lang('Duration').' ('.get_lang('MinMinute').')',
    get_lang('StartDate'),
    get_lang('EndDate'),
    get_lang('Score'),
    get_lang('IP'),
    get_lang('Status'),
    get_lang('Corrector'),
    get_lang('CorrectionDate'),
    get_lang('Actions'),
];

if ($officialCodeInList === 'true') {
    $columns = array_merge([get_lang('OfficialCode')], $columns);
}

// Column config
$column_model = [
    ['name' => 'course', 'index' => 'course', 'width' => '50', 'align' => 'left', 'search' => 'false', 'sortable' => 'false'],
    ['name' => 'exercise', 'index' => 'exercise', 'width' => '50', 'align' => 'left', 'search' => 'false', 'sortable' => 'false'],
    ['name' => 'firstname', 'index' => 'firstname', 'width' => '50', 'align' => 'left', 'search' => 'true'],
    [
        'name' => 'lastname',
        'index' => 'lastname',
        'width' => '50',
        'align' => 'left',
        'formatter' => 'action_formatter',
        'search' => 'true',
    ],
    [
        'name' => 'login',
        'index' => 'username',
        'width' => '40',
        'align' => 'left',
        'search' => 'true',
        'hidden' => api_get_configuration_value('exercise_attempts_report_show_username') ? 'false' : 'true',
    ],
    ['name' => 'duration', 'index' => 'exe_duration', 'width' => '30', 'align' => 'left', 'search' => 'true'],
    ['name' => 'start_date', 'index' => 'start_date', 'width' => '60', 'align' => 'left', 'search' => 'true'],
    ['name' => 'exe_date', 'index' => 'exe_date', 'width' => '60', 'align' => 'left', 'search' => 'true'],
    ['name' => 'score', 'index' => 'exe_result', 'width' => '50', 'align' => 'center', 'search' => 'true'],
    ['name' => 'ip', 'index' => 'user_ip', 'width' => '40', 'align' => 'center', 'search' => 'true'],
    [
        'name' => 'status',
        'index' => 'revised',
        'width' => '40',
        'align' => 'left',
        'search' => 'false',
        'sortable' => 'false',
        //'stype' => 'select',
        //for the bottom bar
        /*'searchoptions' => [
            'defaultValue' => '',
            'value' => ':'.get_lang('All').';1:'.get_lang('Validated').';0:'.get_lang('NotValidated'),
        ],*/
        //for the top bar
        /*'editoptions' => [
            'value' => ':'.get_lang('All').';1:'.get_lang('Validated').';0:'.get_lang('NotValidated'),
        ],*/
    ],
    [
        'name' => 'qualificator_fullname',
        'index' => 'qualificator_fullname',
        'width' => '20',
        'align' => 'left',
        'search' => 'true',
    ],
    [
        'name' => 'date_of_qualification',
        'index' => 'date_of_qualification',
        'width' => '20',
        'align' => 'left',
        'search' => 'true',
    ],
    [
        'name' => 'actions',
        'index' => 'actions',
        'width' => '60',
        'align' => 'left',
        'search' => 'false',
        'sortable' => 'false',
    ],
];

if ('true' === $officialCodeInList) {
    $officialCodeRow = [
        'name' => 'official_code',
        'index' => 'official_code',
        'width' => '50',
        'align' => 'left',
        'search' => 'true',
    ];
    $column_model = array_merge([$officialCodeRow], $column_model);
}

$action_links = '
// add username as title in lastname filed - ref 4226
function action_formatter(cellvalue, options, rowObject) {
    // rowObject is firstname,lastname,login,... get the third word
    var loginx = "'.api_htmlentities(sprintf(get_lang('LoginX'), ':::'), ENT_QUOTES).'";
    var tabLoginx = loginx.split(/:::/);
    // tabLoginx[0] is before and tabLoginx[1] is after :::
    // may be empty string but is defined
    return "<span title=\""+tabLoginx[0]+rowObject[2]+tabLoginx[1]+"\">"+cellvalue+"</span>";
}';

$extra_params['autowidth'] = 'true';
$extra_params['height'] = 'auto';
$gridJs = Display::grid_js(
    'results',
    $url,
    $columns,
    $column_model,
    $extra_params,
    [],
    $action_links,
    true
);

?>
    <script>
    function exportExcel()
    {
        var mya = $("#results").getDataIDs();  // Get All IDs
        var data = $("#results").getRowData(mya[0]);     // Get First row to get the labels
        var colNames = new Array();
        var ii = 0;
        for (var i in data) {
            colNames[ii++] = i;
        }
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

        var $selectCourse = $("select#course_id");

        $selectCourse.on("change", function () {
            var courseId = parseInt(this.value, 10);
            updateExerciseList(courseId);
        });

        var courseId = $selectCourse.val() ? $selectCourse.val() : 0;
        updateExerciseList(courseId);
        <?php
            echo $gridJs;
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

        // Adding search options
        var options = {
            'stringResult': true,
            'autosearch' : true,
            'searchOnEnter': false,
        }
        jQuery("#results").jqGrid('filterToolbar', options);
        sgrid.triggerToolbar();
        $('#results').on('click', 'a.exercise-recalculate', function (e) {
            e.preventDefault();
            if (!$(this).data('user') || !$(this).data('exercise') || !$(this).data('id')) {
                return;
            }
            var url = '<?php echo api_get_path(WEB_CODE_PATH); ?>exercise/recalculate.php?<?php echo api_get_cidreq(); ?>';
            var recalculateXhr = $.post(url, $(this).data());
            $.when(recalculateXhr).done(function (response) {
                $('#results').trigger('reloadGrid');
            });
        });
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
            if (confirm("<?php echo convert_double_quote_to_single(get_lang('AreYouSureDeleteTestResultBeforeDateD')).' '; ?>" + selectedDate)) {
                self.location.href = "exercise_report.php?<?php echo api_get_cidreq(); ?>&exerciseId=<?php echo $exerciseId; ?>&delete_before_date="+dateForBDD+"&sec_token=<?php echo $token; ?>";
            }
        }
    }
    </script>
    <form id="export_report_form" method="post" action="exercise_report.php?<?php echo api_get_cidreq(); ?>">
        <input type="hidden" name="csvBuffer" id="csvBuffer" value="" />
        <input type="hidden" name="export_report" id="export_report" value="1" />
        <input type="hidden" name="exerciseId" id="exerciseId" value="<?php echo $exerciseId; ?>" />
    </form>
<?php

echo Display::grid_html('results');
Display::display_footer();
