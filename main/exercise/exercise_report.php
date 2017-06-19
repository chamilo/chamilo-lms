<?php
/* For licensing terms, see /license.txt */

/**
 * 	Exercise list: This script shows the list of exercises for administrators and students.
 * 	@package chamilo.exercise
 * 	@author Julio Montoya <gugli100@gmail.com> jqgrid integration
 *   Modified by hubert.borderiou (question category)
 *
 *  @todo fix excel export
 *
 */

require_once __DIR__.'/../inc/global.inc.php';

// Setting the tabs
$this_section = SECTION_COURSES;

$htmlHeadXtra[] = api_get_jqgrid_js();

$filter_user = isset($_REQUEST['filter_by_user']) ? intval($_REQUEST['filter_by_user']) : null;
$isBossOfStudent = false;
if (api_is_student_boss() && !empty($filter_user)) {
    // Check if boss has access to user info.
    if (UserManager::userIsBossOfStudent(api_get_user_id(), $filter_user)) {
        $isBossOfStudent = true;
    } else {
        api_not_allowed(true);
    }
} else {
    api_protect_course_script(true, false, true);
}

// including additional libraries
require_once 'hotpotatoes.lib.php';

$_course = api_get_course_info();

// document path
$documentPath = api_get_path(SYS_COURSE_PATH).$_course['path']."/document";
$origin = isset($origin) ? $origin : null;
$gradebook = isset($gradebook) ? $gradebook : null;
$path = isset($_GET['path']) ? Security::remove_XSS($_GET['path']) : null;

/* 	Constants and variables */
$is_allowedToEdit = api_is_allowed_to_edit(null, true) || api_is_drh() || api_is_student_boss();
$is_tutor = api_is_allowed_to_edit(true);

$TBL_TRACK_EXERCISES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$TBL_TRACK_ATTEMPT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
$TBL_TRACK_ATTEMPT_RECORDING = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
$TBL_LP_ITEM_VIEW = Database::get_course_table(TABLE_LP_ITEM_VIEW);
$allowCoachFeedbackExercises = api_get_setting('allow_coach_feedback_exercises') === 'true';

$course_id = api_get_course_int_id();
$exercise_id = isset($_REQUEST['exerciseId']) ? intval($_REQUEST['exerciseId']) : null;
$locked = api_resource_is_locked_by_gradebook($exercise_id, LINK_EXERCISE);

if (empty($exercise_id)) {
    api_not_allowed(true);
}

if (!$is_allowedToEdit && !$allowCoachFeedbackExercises) {
    api_not_allowed(true);
}

if (!empty($exercise_id)) {
    $parameters['exerciseId'] = $exercise_id;
}

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
                    $_GET['exerciseId']
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
                    $_GET['exerciseId']
                );
                exit;
                break;
        }
    } else {
        api_not_allowed(true);
    }
}

//Send student email @todo move this code in a class, library
if (isset($_REQUEST['comments']) &&
    $_REQUEST['comments'] == 'update' &&
    ($is_allowedToEdit || $is_tutor || $allowCoachFeedbackExercises)
) {
    // Filtered by post-condition
    $id = intval($_GET['exeid']);
    $track_exercise_info = ExerciseLib::get_exercise_track_exercise_info($id);

    if (empty($track_exercise_info)) {
        api_not_allowed();
    }
    $test = $track_exercise_info['title'];
    $student_id = $track_exercise_info['exe_user_id'];
    $session_id = $track_exercise_info['session_id'];
    $lp_id = $track_exercise_info['orig_lp_id'];
    $lp_item_view_id = $track_exercise_info['orig_lp_item_view_id'];
    $exerciseId = $track_exercise_info['exe_exo_id'];
    $course_info = api_get_course_info();
    $url = api_get_path(WEB_CODE_PATH).'exercise/result.php?id='.$track_exercise_info['exe_id'].'&'.api_get_cidreq().'&show_headers=1&id_session='.$session_id;

    $my_post_info = array();
    $post_content_id = array();
    $comments_exist = false;

    foreach ($_POST as $key_index => $key_value) {
        $my_post_info = explode('_', $key_index);

        $post_content_id[] = isset($my_post_info[1]) ? $my_post_info[1] : null;

        if ($my_post_info[0] == 'comments') {
            $comments_exist = true;
        }
    }

    $loop_in_track = $comments_exist === true ? (count($_POST) / 2) : count($_POST);
    $array_content_id_exe = array();

    if ($comments_exist === true) {
        $array_content_id_exe = array_slice($post_content_id, $loop_in_track);
    } else {
        $array_content_id_exe = $post_content_id;
    }

    for ($i = 0; $i < $loop_in_track; $i++) {
        $my_marks = isset($_POST['marks_'.$array_content_id_exe[$i]]) ? $_POST['marks_'.$array_content_id_exe[$i]] : '';
        $contain_comments = $_POST['comments_'.$array_content_id_exe[$i]];
        if (isset($contain_comments)) {
            $my_comments = $_POST['comments_'.$array_content_id_exe[$i]];
        } else {
            $my_comments = '';
        }
        $my_questionid = intval($array_content_id_exe[$i]);

        $params = [
            'marks' => $my_marks,
            'teacher_comment' => $my_comments
        ];
        Database::update(
            $TBL_TRACK_ATTEMPT,
            $params,
            ['question_id = ? AND exe_id = ?' => [$my_questionid, $id]]
        );

        $params = [
            'exe_id' => $id,
            'question_id' => $my_questionid,
            'marks' => $my_marks,
            'insert_date' => api_get_utc_datetime(),
            'author' => api_get_user_id(),
            'teacher_comment' => $my_comments
        ];
        Database::insert($TBL_TRACK_ATTEMPT_RECORDING, $params);
    }

    $qry = 'SELECT DISTINCT question_id, marks
            FROM '.$TBL_TRACK_ATTEMPT.' WHERE exe_id = '.$id.'
            GROUP BY question_id';
    $res = Database::query($qry);
    $tot = 0;
    while ($row = Database :: fetch_array($res, 'ASSOC')) {
        $tot += $row['marks'];
    }

    $sql = "UPDATE $TBL_TRACK_EXERCISES
            SET exe_result = '".floatval($tot)."'
            WHERE exe_id = ".$id;

    Database::query($sql);

    if (isset($_POST['send_notification'])) {
        //@todo move this somewhere else
        $subject = get_lang('ExamSheetVCC');
        $message = isset($_POST['notification_content']) ? $_POST['notification_content'] : '';

        MessageManager::send_message_simple(
            $student_id,
            $subject,
            $message,
            api_get_user_id()
        );

        if ($allowCoachFeedbackExercises) {
            Display::addFlash(
                Display::return_message(get_lang('MessageSent'))
            );
            header('Location: '.api_get_self().'?'.api_get_cidreq().'&exerciseId='.$exerciseId);
            exit;
        }
    }

    // Updating LP score here
    if (in_array($origin, array('tracking_course', 'user_course', 'correct_exercise_in_lp'))
    ) {
        $sql = "UPDATE $TBL_LP_ITEM_VIEW 
                SET score = '".floatval($tot)."'
                WHERE c_id = ".$course_id." AND id = ".$lp_item_view_id;
        Database::query($sql);
        if ($origin == 'tracking_course') {
            //Redirect to the course detail in lp
            header('location: '.api_get_path(WEB_CODE_PATH).'exercise/exercise.php?course='.Security::remove_XSS($_GET['course']));
            exit;
        } else {
            // Redirect to the reporting
            header('Location: '.api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?origin='.$origin.'&student='.$student_id.'&details=true&course='.$course_id.'&session_id='.$session_id);
            exit;
        }
    }
}

$actions = null;
if ($is_allowedToEdit && $origin != 'learnpath') {
    // the form
    if (api_is_platform_admin() || api_is_course_admin() ||
        api_is_course_tutor() || api_is_session_general_coach()
    ) {
        $actions .= '<a href="admin.php?exerciseId='.intval($_GET['exerciseId']).'">'.Display::return_icon('back.png', get_lang('GoBackToQuestionList'), '', ICON_SIZE_MEDIUM).'</a>';
        $actions .= '<a href="live_stats.php?'.api_get_cidreq().'&exerciseId='.$exercise_id.'">'.Display::return_icon('activity_monitor.png', get_lang('LiveResults'), '', ICON_SIZE_MEDIUM).'</a>';
        $actions .= '<a href="stats.php?'.api_get_cidreq().'&exerciseId='.$exercise_id.'">'.Display::return_icon('statistics.png', get_lang('ReportByQuestion'), '', ICON_SIZE_MEDIUM).'</a>';

        $actions .= '<a id="export_opener" href="'.api_get_self().'?export_report=1&exerciseId='.intval($_GET['exerciseId']).'" >'.
        Display::return_icon('save.png', get_lang('Export'), '', ICON_SIZE_MEDIUM).'</a>';
        // clean result before a selected date icon
        $actions .= Display::url(
            Display::return_icon('clean_before_date.png', get_lang('CleanStudentsResultsBeforeDate'), '', ICON_SIZE_MEDIUM),
            '#',
            array('onclick' => "javascript:display_date_picker()")
        );
        // clean result before a selected date datepicker popup
        $actions .= Display::span(
            Display::input('input', 'datepicker_start', get_lang('SelectADateOnTheCalendar'),
                array('onmouseover'=>'datepicker_input_mouseover()', 'id'=>'datepicker_start', 'onchange'=>'datepicker_input_changed()', 'readonly'=>'readonly')
            ).
            Display::button('delete', get_lang('Delete'),
                array('onclick'=>'submit_datepicker()')),
            array('style'=>'display:none', 'id'=>'datepicker_span')
        );
    }
} else {
    $actions .= '<a href="exercise.php">'.
        Display::return_icon('back.png', get_lang('GoBackToQuestionList'), '', ICON_SIZE_MEDIUM).
    '</a>';
}

//Deleting an attempt
if (($is_allowedToEdit || $is_tutor || api_is_coach()) &&
    isset($_GET['delete']) && $_GET['delete'] == 'delete' &&
    !empty($_GET['did']) && $locked == false
) {
    $exe_id = intval($_GET['did']);
    if (!empty($exe_id)) {
        $sql = 'DELETE FROM '.$TBL_TRACK_EXERCISES.' WHERE exe_id = '.$exe_id;
        Database::query($sql);
        $sql = 'DELETE FROM '.$TBL_TRACK_ATTEMPT.' WHERE exe_id = '.$exe_id;
        Database::query($sql);
        header('Location: exercise_report.php?'.api_get_cidreq().'&exerciseId='.$exercise_id);
        exit;
    }
}


if ($is_allowedToEdit || $is_tutor) {
    $interbreadcrumb[] = array("url" => "exercise.php?gradebook=$gradebook", "name" => get_lang('Exercises'));
    $objExerciseTmp = new Exercise();
    $nameTools = get_lang('StudentScore');
    if ($objExerciseTmp->read($exercise_id)) {
        $interbreadcrumb[] = array("url" => "admin.php?exerciseId=".$exercise_id, "name" => $objExerciseTmp->selectTitle(true));
    }
} else {
    $interbreadcrumb[] = array("url" => "exercise.php?gradebook=$gradebook", "name" => get_lang('Exercises'));
    $objExerciseTmp = new Exercise();
    if ($objExerciseTmp->read($exercise_id)) {
        $nameTools = get_lang('Results').': '.$objExerciseTmp->selectTitle(true);
    }
}

if (($is_allowedToEdit || $is_tutor || api_is_coach()) &&
    isset($_GET['a']) && $_GET['a'] == 'close' &&
    !empty($_GET['id']) && $locked == false
) {
    // Close the user attempt otherwise left pending
    $exe_id = intval($_GET['id']);
    $sql = "UPDATE $TBL_TRACK_EXERCISES SET status = '' WHERE exe_id = $exe_id AND status = 'incomplete'";
    Database::query($sql);
}

Display :: display_header($nameTools);

// Clean all results for this test before the selected date
if (($is_allowedToEdit || $is_tutor || api_is_coach()) &&
    isset($_GET['delete_before_date']) && $locked == false
) {
    // ask for the date
    $check = Security::check_token('get');
    if ($check) {
        $objExerciseTmp = new Exercise();
        if ($objExerciseTmp->read($exercise_id)) {
            $count = $objExerciseTmp->clean_results(
                true,
                $_GET['delete_before_date'].' 23:59:59'
            );
            echo Display::return_message(sprintf(get_lang('XResultsCleaned'), $count), 'confirm');
        }
    }
}

// Security token to protect deletion
$token = Security::get_token();
$actions = Display::div($actions, array('class' => 'actions'));

$extra = '<script>
    $(document).ready(function() {
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

$extra .= '<div id="dialog-confirm" title="'.get_lang("ConfirmYourChoice").'">';
$form = new FormValidator('report', 'post', null, null, array('class' => 'form-vertical'));
$form->addElement('radio', 'export_format', null, get_lang('ExportAsCSV'), 'csv', array('id' => 'export_format_csv_label'));
$form->addElement('radio', 'export_format', null, get_lang('ExportAsXLS'), 'xls', array('id' => 'export_format_xls_label'));
$form->addElement('checkbox', 'load_extra_data', null, get_lang('LoadExtraData'), '0', array('id' => 'export_format_xls_label'));
$form->addElement('checkbox', 'include_all_users', null, get_lang('IncludeAllUsers'), '0');
$form->addElement('checkbox', 'only_best_attempts', null, get_lang('OnlyBestAttempts'), '0');
$form->setDefaults(array('export_format' => 'csv'));
$extra .= $form->returnForm();
$extra .= '</div>';

if ($is_allowedToEdit) {
    echo $extra;
}

echo $actions;
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_exercise_results&exerciseId='.$exercise_id.'&filter_by_user='.$filter_user.'&'.api_get_cidreq();
$action_links = '';
// Generating group list
$group_list = GroupManager::get_group_list();
$group_parameters = array(
    'group_all:'.get_lang('All'),
    'group_none:'.get_lang('None'),
);

foreach ($group_list as $group) {
    $group_parameters[] = $group['id'].':'.$group['name'];
}
if (!empty($group_parameters)) {
    $group_parameters = implode(';', $group_parameters);
}

$officialCodeInList = api_get_setting('show_official_code_exercise_result_list');

if ($is_allowedToEdit || $is_tutor) {
    // The order is important you need to check the the $column variable in the model.ajax.php file
    $columns = array(
        get_lang('FirstName'),
        get_lang('LastName'),
        get_lang('LoginName'),
        get_lang('Group'),
        get_lang('Duration').' ('.get_lang('MinMinute').')',
        get_lang('StartDate'),
        get_lang('EndDate'),
        get_lang('Score'),
        get_lang('IP'),
        get_lang('Status'),
        get_lang('ToolLearnpath'),
        get_lang('Actions')
    );

    if ($officialCodeInList === 'true') {
        $columns = array_merge(array(get_lang('OfficialCode')), $columns);
    }

    //Column config
    $column_model = array(
        array('name' => 'firstname', 'index' => 'firstname', 'width' => '50', 'align' => 'left', 'search' => 'true'),
        array('name' => 'lastname', 'index' => 'lastname', 'width' => '50', 'align' => 'left', 'formatter' => 'action_formatter', 'search' => 'true'),
        array('name' => 'login', 'index' => 'username', 'width' => '40', 'align' => 'left', 'search' => 'true', 'hidden' => 'true'),
        array('name' => 'group_name', 'index' => 'group_id', 'width' => '40', 'align' => 'left', 'search' => 'true', 'stype' => 'select',
            //for the bottom bar
            'searchoptions' => array(
                'defaultValue' => 'group_all',
                'value' => $group_parameters),
            //for the top bar
            'editoptions' => array('value' => $group_parameters)),
        array('name' => 'duration', 'index' => 'exe_duration', 'width' => '30', 'align' => 'left', 'search' => 'true'),
        array('name' => 'start_date', 'index' => 'start_date', 'width' => '60', 'align' => 'left', 'search' => 'true'),
        array('name' => 'exe_date', 'index' => 'exe_date', 'width' => '60', 'align' => 'left', 'search' => 'true'),
        array('name' => 'score', 'index' => 'exe_result', 'width' => '50', 'align' => 'center', 'search' => 'true'),
        array('name' => 'ip', 'index' => 'user_ip', 'width' => '40', 'align' => 'center', 'search' => 'true'),
        array('name' => 'status', 'index' => 'revised', 'width' => '40', 'align' => 'left', 'search' => 'true', 'stype' => 'select',
            //for the bottom bar
            'searchoptions' => array(
                'defaultValue' => '',
                'value' => ':'.get_lang('All').';1:'.get_lang('Validated').';0:'.get_lang('NotValidated')),
            //for the top bar
            'editoptions' => array('value' => ':'.get_lang('All').';1:'.get_lang('Validated').';0:'.get_lang('NotValidated'))),
        array('name' => 'lp', 'index' => 'lp', 'width' => '60', 'align' => 'left', 'search' => 'false'),
        array('name' => 'actions', 'index' => 'actions', 'width' => '60', 'align' => 'left', 'search' => 'false')
    );

    if ($officialCodeInList == 'true') {
        $officialCodeRow = array('name' => 'official_code', 'index' => 'official_code', 'width' => '50', 'align' => 'left', 'search' => 'true');
        $column_model = array_merge(array($officialCodeRow), $column_model);
    }

    $action_links = '
    // add username as title in lastname filed - ref 4226
    function action_formatter(cellvalue, options, rowObject) {
        // rowObject is firstname,lastname,login,... get the third word
        var loginx = "'.api_htmlentities(sprintf(get_lang("LoginX"), ":::"), ENT_QUOTES).'";
        var tabLoginx = loginx.split(/:::/);
        // tabLoginx[0] is before and tabLoginx[1] is after :::
        // may be empty string but is defined
        return "<span title=\""+tabLoginx[0]+rowObject[2]+tabLoginx[1]+"\">"+cellvalue+"</span>";
    }';
}

//Autowidth
$extra_params['autowidth'] = 'true';

//height auto
$extra_params['height'] = 'auto';
?>
<script>
    function setSearchSelect(columnName) {
        $("#results").jqGrid(
            'setColProp',
            columnName, {
                searchoptions:{
                    dataInit:function(el) {
                        $("option[value='1']",el).attr("selected", "selected");
                        setTimeout(function(){
                            $(el).trigger('change');
                        },1000);
                    }
                }
            }
        );
    }

    function exportExcel() {
        var mya=new Array();
        mya=$("#results").getDataIDs();  // Get All IDs
        var data=$("#results").getRowData(mya[0]);     // Get First row to get the labels
        var colNames=new Array();
        var ii=0;
        for (var i in data){colNames[ii++]=i;}    // capture col names
        var html="";

        for(i=0;i<mya.length;i++) {
            data=$("#results").getRowData(mya[i]); // get each row
            for(j=0;j<colNames.length;j++) {
                html=html+data[colNames[j]]+","; // output each column as tab delimited
            }
            html=html+"\n";  // output each row with end of line
        }
        html = html+"\n";  // end of line at the end

        var form = $("#export_report_form");

        $("#csvBuffer").attr('value', html);
        form.target='_blank';
        form.submit();
    }

    $(function() {
        <?php
        echo Display::grid_js(
            'results',
            $url,
            $columns,
            $column_model,
            $extra_params,
            array(),
            $action_links,
            true
        );

        if ($is_allowedToEdit || $is_tutor) {
            ?>
                //setSearchSelect("status");
                //
                //view:true, del:false, add:false, edit:false, excel:true}
                $("#results").jqGrid('navGrid','#results_pager', {view:true, edit:false, add:false, del:false, excel:false},
                {height:280, reloadAfterSubmit:false}, // view options
                {height:280, reloadAfterSubmit:false}, // edit options
                {height:280, reloadAfterSubmit:false}, // add options
                {reloadAfterSubmit: false}, // del options
                {width:500} // search options
            );
                /*
            // add custom button to export the data to excel
            jQuery("#results").jqGrid('navButtonAdd','#results_pager',{
                caption:"",
                onClickButton : function () {
                     //exportExcel();
                }
            });*/

                /*
            jQuery('#sessions').jqGrid('navButtonAdd','#sessions_pager',{id:'pager_csv',caption:'',title:'Export To CSV',onClickButton : function(e)
            {
                try {
                    jQuery("#sessions").jqGrid('excelExport',{tag:'csv', url:'grid.php'});
                } catch (e) {
                    window.location= 'grid.php?oper=csv';
                }
            },buttonicon:'ui-icon-document'})
                 */

                //Adding search options
                var options = {
                    'stringResult': true,
                    'autosearch' : true,
                    'searchOnEnter':false
                }
                jQuery("#results").jqGrid('filterToolbar',options);
                var sgrid = $("#results")[0];
                sgrid.triggerToolbar();

                $('#results').on('click', 'a.exercise-recalculate', function (e) {
                    e.preventDefault();
                    if (!$(this).data('user') || !$(this).data('exercise') || !$(this).data('id')) {
                        return;
                    }
                    var url = '<?php echo api_get_path(WEB_CODE_PATH) ?>exercise/recalculate.php?<?php echo api_get_cidreq() ?>';
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
                if (confirm("<?php echo convert_double_quote_to_single(get_lang('AreYouSureDeleteTestResultBeforeDateD')); ?>" + selectedDate)) {
                    self.location.href = "exercise_report.php?<?php echo api_get_cidreq(); ?>&exerciseId=<?php echo $exercise_id; ?>&delete_before_date="+dateForBDD+"&sec_token=<?php echo $token; ?>";
                }
            }
        }

        /**
        * initiate datepicker
        */
        $(function() {
            $( "#datepicker_start" ).datepicker({
                defaultDate: "",
                changeMonth: false,
                numberOfMonths: 1
            });
        });
</script>
<form id="export_report_form" method="post" action="exercise_report.php?<?php echo api_get_cidreq(); ?>">
    <input type="hidden" name="csvBuffer" id="csvBuffer" value="" />
    <input type="hidden" name="export_report" id="export_report" value="1" />
    <input type="hidden" name="exerciseId" id="exerciseId" value="<?php echo $exercise_id ?>" />
</form>

<?php
echo Display::grid_html('results');
Display :: display_footer();
