<?php
/* For licensing terms, see /license.txt */
/**
 * Exercise list: This script shows the list of exercises for administrators and students.
 * @package chamilo.exercise
 * @author Julio Montoya <gugli100@gmail.com> jqgrid integration
 *   Modified by hubert.borderiou (question category)
 *
 * @todo fix excel export
 *
 */
/**
 * Code
 */
// name of the language file that needs to be included
$language_file = array('exercice');

api_protect_global_admin_script();

$urlMainExercise = api_get_path(WEB_CODE_PATH).'exercice/';

// Setting the tabs
$this_section = SECTION_COURSES;

$htmlHeadXtra[] = api_get_jqgrid_js();

// including additional libraries
require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.class.php';
require_once api_get_path(SYS_CODE_PATH).'exercice/question.class.php';
require_once api_get_path(SYS_CODE_PATH).'exercice/answer.class.php';

// need functions of statsutils lib to display previous exercices scores
require_once api_get_path(LIBRARY_PATH).'statsUtils.lib.inc.php';

// document path
$documentPath = api_get_path(SYS_COURSE_PATH).$_course['path']."/document";

/*	Constants and variables */
$is_tutor = api_is_allowed_to_edit(true);

$TBL_QUESTIONS = Database :: get_course_table(TABLE_QUIZ_QUESTION);
$TBL_TRACK_EXERCICES = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$TBL_TRACK_ATTEMPT = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
$TBL_TRACK_ATTEMPT_RECORDING = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
$TBL_LP_ITEM_VIEW = Database :: get_course_table(TABLE_LP_ITEM_VIEW);
$course_id = api_get_course_int_id();

if (!empty($_REQUEST['export_report']) && $_REQUEST['export_report'] == '1') {

    $load_extra_data = false;
    if (isset($_REQUEST['extra_data']) && $_REQUEST['extra_data'] == 1) {
        $load_extra_data = true;
    }
    require_once 'exercise_result.class.php';
    switch ($_GET['export_format']) {
        case 'xls':
            $export = new ExerciseResult();
            $export->exportCompleteReportXLS(
                $documentPath,
                null,
                $load_extra_data,
                null,
                $_GET['exerciseId'],
                $_GET['hotpotato_name']
            );
            exit;
            break;
        case 'csv':
        default:
            $export = new ExerciseResult();
            $export->exportCompleteReportCSV(
                $documentPath,
                null,
                $load_extra_data,
                null,
                $_GET['exerciseId'],
                $_GET['hotpotato_name']
            );
            exit;
            break;
    }

}

$nameTools = get_lang('StudentScore');

$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array("url" => "", "name" => get_lang('ExercicesReport'));

Display :: display_header($nameTools);

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
                        location.href = targetUrl+"&export_format="+export_format+"&extra_data="+extra_data;
                        $( this ).dialog( "close" );
                    },
                }
            });
            $( "#dialog-confirm" ).dialog("open");
            return false;
        });
    });
    </script>';

$extra .= '<div id="dialog-confirm" title="'.get_lang("ConfirmYourChoice").'">';
$form = new FormValidator('report', 'post', null, null, array('class' => 'form-vertical'));
$form->addElement(
    'radio',
    'export_format',
    null,
    get_lang('ExportAsCSV'),
    'csv',
    array('id' => 'export_format_csv_label')
);
$form->addElement(
    'radio',
    'export_format',
    null,
    get_lang('ExportAsXLS'),
    'xls',
    array('id' => 'export_format_xls_label')
);
$form->addElement(
    'checkbox',
    'load_extra_data',
    null,
    get_lang('LoadExtraData'),
    '0',
    array('id' => 'export_format_xls_label')
);
$form->setDefaults(array('export_format' => 'csv'));
$extra .= $form->return_form();
$extra .= '</div>';

echo $extra;

$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_admin_exercise_results';

$action_links = '';

//The order is important you need to check the the $column variable in the model.ajax.php file
$columns = array(
    get_lang('LoginName'),
    get_lang('FirstName'),
    get_lang('LastName'),
    get_lang('Score'),
    get_lang('Link'),
    get_lang('Session')
  //  get_lang('Actions')
);

//Column config
$column_model = array(
    array(
        'name' => 'login',
        'index' => 'username',
        'width' => '40',
        'align' => 'left',
        'search' => 'true'
    ),
    array('name' => 'firstname', 'index' => 'firstname', 'width' => '50', 'align' => 'left', 'search' => 'true'),
    array(
        'name' => 'lastname',
        'index' => 'lastname',
        'width' => '50',
        'align' => 'left',
        //'formatter' => 'action_formatter',
        'search' => 'true'
    ),
    array('name' => 'score', 'index' => 'exe_result', 'width' => '50', 'align' => 'left', 'search' => 'true'),
    array('name' => 'link', 'index' => 'link', 'width' => '50', 'align' => 'left', 'search' => 'false'),
    array('name' => 'session', 'index' => 'session_id', 'width' => '50', 'align' => 'left', 'search' => 'true'),
);

//Autowidth
$extra_params['autowidth'] = 'true';

//height auto
$extra_params['height'] = 'auto';

?>
<script>

    function setSearchSelect(columnName) {
        $("#results").jqGrid('setColProp', columnName, {
            searchoptions:{
                dataInit:function (el) {
                    $("option[value='1']", el).attr("selected", "selected");
                    setTimeout(function () {
                        $(el).trigger('change');
                    }, 1000);
                }
            }
        });
    }

    function exportExcel() {
        var mya = new Array();
        mya = $("#results").getDataIDs();  // Get All IDs
        var data = $("#results").getRowData(mya[0]);     // Get First row to get the labels
        var colNames = new Array();
        var ii = 0;
        for (var i in data) {
            colNames[ii++] = i;
        }    // capture col names
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
        form.target = '_blank';
        form.submit();
    }

    $(function () {
    <?php
        echo Display::grid_js('results', $url, $columns, $column_model, $extra_params, array(), $action_links, true);
    ?>
        //setSearchSelect("status");
        //
        //view:true, del:false, add:false, edit:false, excel:true}
        $("#results").jqGrid(
            'navGrid',
            '#results_pager',
            { view:true, edit:false, add:false, del:false, excel:false },
            { height:280, reloadAfterSubmit:false }, // view options
            { height:280, reloadAfterSubmit:false }, // edit options
            { height:280, reloadAfterSubmit:false }, // add options
            { reloadAfterSubmit : false }, // del options
            { width:500 } // search options
        );
        //Adding search options
        var options = {
            'stringResult': true,
            'autosearch' : true,
            'searchOnEnter':false
        }
        jQuery("#results").jqGrid('filterToolbar', options);
        var sgrid = $("#results")[0];
        sgrid.triggerToolbar();

    });
</script>
<form id="export_report_form" method="post" action="exercise_report.php">
    <input type="hidden" name="csvBuffer" id="csvBuffer" value=""/>
    <input type="hidden" name="export_report" id="export_report" value="1"/>
</form>
<?php

echo Display::grid_html('results');

Display :: display_footer();
