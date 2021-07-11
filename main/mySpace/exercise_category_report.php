<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

$exportCSV = isset($_GET['export']) && $_GET['export'] === 'csv' ? true : false;

// the section (for the tabs)
$this_section = SECTION_TRACKING;

$csv_content = [];
$nameTools = get_lang('MySpace');

$is_platform_admin = api_is_platform_admin();
$is_drh = api_is_drh();
$is_session_admin = api_is_session_admin();

$currentUrl = api_get_self();
$courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
$defaults = [];
$defaults['start_date'] = isset($_GET['start_date']) ? Security::remove_XSS($_GET['start_date']) : '';
$defaults['course_id'] = $courseId;

$htmlHeadXtra[] = api_get_jqgrid_js();
$htmlHeadXtra[] = '<script>
$(function() {
    $("#exercise_course_id").on("change", function(e) {
        var data = $(this).select2(\'data\');
        var option = data[0];
        //Then I take the values like if I work with an array
        var value = option.id;
        var selectedDate = $("#start_date").datepicker({ dateFormat: \'dd,MM,yyyy\' }).val();
        window.location.replace("'.$currentUrl.'?start_date="+selectedDate+"&course_id="+value);
    });
});
</script>';

$form = new FormValidator('exercise', 'get');
$form->addDatePicker('start_date', get_lang('StartDate'));
if (empty($courseId)) {
    $form->addSelectAjax(
        'course_id',
        get_lang('Course'),
        null,
        [
            'url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_course',
        ]
    );
} else {
    $courseInfo = api_get_course_info_by_id($courseId);
    if (!empty($courseInfo)) {
        $form->addHidden('course_id', $courseId);
        $courseLabel = Display::url(
            $courseInfo['name'].' ('.$courseInfo['code'].')',
            $courseInfo['course_public_url'],
            ['target' => '_blank']
        );
        $form->addLabel(get_lang('Course'), $courseLabel);
        $exerciseList = ExerciseLib::get_all_exercises_for_course_id(
            $courseInfo,
            0,
            $courseId,
            true
        );

        if (!empty($exerciseList)) {
            $options = [];
            foreach ($exerciseList as $exercise) {
                $options[$exercise['iid']] = $exercise['title'];
            }
            $form->addSelect('exercise_id', get_lang('Exercises'), $options);
        } else {
            $form->addLabel(get_lang('Exercises'), Display::return_message(get_lang('NoExercises')));
        }
    } else {
        Display::addFlash(Display::return_message(get_lang('CourseDoesNotExist'), 'warning'));
    }
}

$form->setDefaults($defaults);
$form->addButtonSearch(get_lang('Search'));

Display::display_header($nameTools);
$form->display();

$extraFields = api_get_configuration_value('exercise_category_report_user_extra_fields');

if ($form->validate() && !empty($courseInfo)) {
    $values = $form->getSubmitValues();
    $exerciseId = isset($values['exercise_id']) ? $values['exercise_id'] : 0;
    $startDate = Security::remove_XSS($values['start_date']);
    $exportFilename = 'exercise_results_report_'.$exerciseId.'_'.$courseInfo['code'];
    $url = api_get_path(WEB_AJAX_PATH).
        'model.ajax.php?a=get_exercise_results_report&exercise_id='.$exerciseId.
        '&start_date='.$startDate.'&cidReq='.$courseInfo['code'].
        '&course_id='.$courseId.
        '&export_filename='.$exportFilename;

    $categoryList = TestCategory::getListOfCategoriesIDForTest($exerciseId, $courseId);
    $columns = [
        get_lang('FirstName'),
        get_lang('LastName'),
        get_lang('LoginName'),
    ];

    if (!empty($extraFields) && isset($extraFields['fields'])) {
        $extraField = new ExtraField('user');
        foreach ($extraFields['fields'] as $variable) {
            $info = $extraField->get_handler_field_info_by_field_variable($variable);
            if ($info) {
                $columns[] = $info['display_text'];
            }
        }
    }

    $columns[] = get_lang('Session');
    $columns[] = get_lang('SessionStartDate');
    $columns[] = get_lang('StartDate');
    $columns[] = get_lang('Score');

    if (!empty($categoryList)) {
        foreach ($categoryList as $categoryInfo) {
            $columns[] = $categoryInfo['title'];
        }
    }
    $columns[] = get_lang('Actions');

    $columnModel = [
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
            'hidden' => 'true',
        ],
    ];

    if (!empty($extraFields) && isset($extraFields['fields'])) {
        $extraField = new ExtraField('user');
        foreach ($extraFields['fields'] as $variable) {
            $columnModel[] = [
                'name' => $variable,
                'index' => $variable,
                'width' => '40',
                'align' => 'left',
                'search' => 'false',
            ];
        }
    }

    $columnModel[] = [
        'name' => 'session',
        'index' => 'session',
        'width' => '40',
        'align' => 'left',
        'search' => 'false',
    ];
    $columnModel[] = [
        'name' => 'session_access_start_date',
        'index' => 'session_access_start_date',
        'width' => '50',
        'align' => 'center',
        'search' => 'true',
    ];
    $columnModel[] = [
        'name' => 'exe_date',
        'index' => 'exe_date',
        'width' => '60',
        'align' => 'left',
        'search' => 'true',
    ];
    $columnModel[] = [
        'name' => 'score',
        'index' => 'exe_result',
        'width' => '50',
        'align' => 'center',
        'search' => 'true',
    ];

    if (!empty($categoryList)) {
        foreach ($categoryList as $categoryInfo) {
            $columnModel[] = [
                'name' => 'category_'.$categoryInfo['id'],
                'index' => 'category_'.$categoryInfo['id'],
                'width' => '50',
                'align' => 'center',
                'search' => 'true',
            ];
        }
    }

    $columnModel[] = [
        'name' => 'actions',
        'index' => 'actions',
        'width' => '60',
        'align' => 'left',
        'search' => 'false',
        'sortable' => 'false',
        'hidden' => 'true',
    ];

    $extra_params['autowidth'] = 'true';

    //height auto
    $extra_params['height'] = 'auto';
    $actionLinks = '
    // add username as title in lastname filed - ref 4226
    function action_formatter(cellvalue, options, rowObject) {
        // rowObject is firstname,lastname,login,... get the third word
        var loginx = "'.api_htmlentities(sprintf(get_lang("LoginX"), ":::"), ENT_QUOTES).'";
        var tabLoginx = loginx.split(/:::/);
        // tabLoginx[0] is before and tabLoginx[1] is after :::
        // may be empty string but is defined
        return "<span title=\""+tabLoginx[0]+rowObject[2]+tabLoginx[1]+"\">"+cellvalue+"</span>";
    }';
    $tableId = 'results'; ?>
    <script>
        $(function() {
            <?php
            echo Display::grid_js(
                'results',
                $url,
                $columns,
                $columnModel,
                $extra_params,
                [],
                $actionLinks,
                true
            ); ?>
        });
    </script>
    <?php

    echo '<script>
        $(function() {
            var myUrl = jQuery("#'.$tableId.'").jqGrid(\'getGridParam\', \'url\');
            myUrl += "&export_format=xls&oper=excel";
            var postData = jQuery("#'.$tableId.'").jqGrid(\'getGridParam\', \'postData\');
            $.each(postData, function(key, value) {
                myUrl += "&"+key+"="+encodeURIComponent(value);
            });

            $("#excel_export").attr("href", myUrl);

            jQuery("#'.$tableId.'").jqGrid(
                "navGrid",
                "#'.$tableId.'_pager",
                {
                    view:false, edit:false, add:false, del:false, search:false, excel:true
                }
            );

            jQuery("#'.$tableId.'").jqGrid("navButtonAdd","#'.$tableId.'_pager",{
               caption: "",
               title:"'.get_lang('ExportExcel').'",
               onClickButton : function() {
                   jQuery("#'.$tableId.'").jqGrid(
                    "excelExport",{
                        "url":"'.$url.'&export_format=xls"
                    }
                );
               }
            });
        });
    </script>';
    $items = [
        [
            'url' => '  ',
            'url_attributes' => ['id' => 'excel_export'],
            'content' => Display::return_icon('export_excel.png', get_lang('ExportExcel')),
        ],
    ];

    echo Display::actions($items);
    echo Display::grid_html('results');
}

Display::display_footer();
