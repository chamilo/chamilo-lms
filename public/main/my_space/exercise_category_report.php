<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

// Section for tabs
$this_section = SECTION_TRACKING;

$exportCSV = isset($_GET['export']) && 'csv' === $_GET['export'];

$csv_content = [];
$nameTools = get_lang('Reporting');

$currentUrl = api_get_self();
$courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
$selectedExerciseId = isset($_GET['exercise_id']) ? (int) $_GET['exercise_id'] : 0;

$defaults = [];
$defaults['start_date'] = isset($_GET['start_date']) ? Security::remove_XSS($_GET['start_date']) : '';
$defaults['course_id'] = $courseId;
$defaults['exercise_id'] = $selectedExerciseId;

// Ensure variable exists even if no course found
$courseInfo = [];

// JS: reload on course change to keep URL filters in sync
$htmlHeadXtra[] = '<script>
$(function() {
    $("#exercise_course_id").on("change", function() {
        var data = $(this).select2("data");
        if (!data || !data.length) {
            return;
        }

        var option = data[0];
        var value = option.id;
        var selectedDate = $("#start_date").val() || "";

        window.location.replace(
            "'.$currentUrl.'?start_date=" + encodeURIComponent(selectedDate) + "&course_id=" + encodeURIComponent(value)
        );
    });
});
</script>';

// -----------------------------------------------------------------------------
// Filter form
// -----------------------------------------------------------------------------
$form = new FormValidator('exercise', 'get');
$form->addDatePicker('start_date', get_lang('Start Date'));

if (empty($courseId)) {
    $form->addSelectAjax(
        'course_id',
        get_lang('Course'),
        [],
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

        $exerciseList = ExerciseLib::get_all_exercises_for_course_id($courseId);

        if (!empty($exerciseList)) {
            $options = [];
            foreach ($exerciseList as $exercise) {
                $exerciseOptionId = (int) ($exercise['iid'] ?? $exercise['id'] ?? 0);
                if ($exerciseOptionId <= 0) {
                    continue;
                }

                $options[$exerciseOptionId] = $exercise['title'];
            }

            if (!empty($options)) {
                $form->addSelect('exercise_id', get_lang('Tests'), $options);
            } else {
                $form->addLabel(get_lang('Tests'), Display::return_message(get_lang('No tests')));
            }
        } else {
            $form->addLabel(get_lang('Tests'), Display::return_message(get_lang('No tests')));
        }
    } else {
        Display::addFlash(
            Display::return_message(get_lang("This course doesn't exist"), 'warning')
        );
    }
}

$form->setDefaults($defaults);
$form->addButtonSearch(get_lang('Search'));

// -----------------------------------------------------------------------------
// Toolbar + admin report cards layout (same helper as other admin reports)
// -----------------------------------------------------------------------------
Display::display_header($nameTools, get_lang('Test'));

// Local styles for admin cards + reporting cards
echo '<style>
    .admin-report-card-active {
        border-color: #0284c7 !important;
        background-color: #e0f2fe !important;
    }
    .reporting-admin-card {
        border-color: #e5e7eb !important;
        border-width: 1px !important;
    }
    .reporting-admin-card .panel,
    .reporting-admin-card fieldset {
        border-color: #e5e7eb !important;
    }
    .reporting-grid-wrapper {
        width: 100%;
        min-width: 100%;
    }
    .reporting-grid-wrapper .ui-jqgrid,
    .reporting-grid-wrapper .ui-jqgrid-view,
    .reporting-grid-wrapper .ui-jqgrid-hdiv,
    .reporting-grid-wrapper .ui-jqgrid-bdiv,
    .reporting-grid-wrapper .ui-jqgrid-pager {
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box;
    }
    .reporting-grid-wrapper .ui-jqgrid-btable,
    .reporting-grid-wrapper .ui-jqgrid-htable {
        width: 100% !important;
    }
</style>';

// Left side: MySpace admin menu (shared helper)
$actionsLeft = Display::mySpaceMenu('admin_view');

// Right side: print action
$actionsRight = Display::url(
    Display::getMdiIcon(
        ActionIcon::PRINT,
        'ch-tool-icon',
        null,
        ICON_SIZE_MEDIUM,
        get_lang('Print')
    ),
    'javascript: void(0);',
    ['onclick' => 'javascript: window.print();']
);

$toolbar = Display::toolbarAction('toolbar-admin', [$actionsLeft, $actionsRight]);

// Current script so the helper can highlight the right card
$currentScriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');

// -----------------------------------------------------------------------------
// Main container
// -----------------------------------------------------------------------------
echo '<div class="w-full px-4 md:px-8 pb-8 space-y-6">';

// Toolbar row (icons aligned to the left)
echo '  <div class="flex flex-wrap gap-2">';
echo        $toolbar;
echo '  </div>';

// Page title
echo '  <div class="space-y-1">';
echo        Display::page_subheader($nameTools);
echo '  </div>';

// Admin report cards (shared helper used across admin reports)
echo MySpace::renderAdminReportCardsSection(null, $currentScriptName, true);

// Filter form card
echo '  <section class="reporting-admin-card bg-white rounded-xl shadow-sm border border-gray-200 w-full">';
echo '      <div class="p-4 md:p-5">';
$form->display();
echo '      </div>';
echo '  </section>';

$extraFields = api_get_setting('exercise.exercise_category_report_user_extra_fields', true);

// -----------------------------------------------------------------------------
// Results grid
// Render results whenever the required GET filters are present.
// This avoids blank pages after reloads on GET reports.
// -----------------------------------------------------------------------------
if (!empty($courseInfo) && !empty($selectedExerciseId)) {
    $exerciseId = $selectedExerciseId;
    $startDate = $defaults['start_date'];

    $exportFilename = 'exercise_results_report_'.$exerciseId.'_'.$courseInfo['code'];

    $url = api_get_path(WEB_AJAX_PATH).
        'model.ajax.php?a=get_exercise_results_report&exercise_id='.$exerciseId.
        '&start_date='.urlencode($startDate).
        '&cidReq='.$courseInfo['code'].
        '&course_id='.$courseId.
        '&export_filename='.$exportFilename;

    $categoryList = TestCategory::getListOfCategoriesIDForTest($exerciseId, $courseId);

    $columns = [
        get_lang('First name'),
        get_lang('Last name'),
        get_lang('Login'),
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
    $columns[] = get_lang('Session start date');
    $columns[] = get_lang('Start date');
    $columns[] = get_lang('Score');

    if (!empty($categoryList)) {
        foreach ($categoryList as $categoryInfo) {
            $columns[] = $categoryInfo['title'];
        }
    }

    $columns[] = get_lang('Detail');

    $columnModel = [
        [
            'name' => 'firstname',
            'index' => 'firstname',
            'width' => '160',
            'align' => 'left',
            'search' => 'true',
        ],
        [
            'name' => 'lastname',
            'index' => 'lastname',
            'width' => '160',
            'align' => 'left',
            'formatter' => 'action_formatter',
            'search' => 'true',
        ],
        [
            'name' => 'login',
            'index' => 'username',
            'width' => '140',
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
                'width' => '140',
                'align' => 'left',
                'search' => 'false',
            ];
        }
    }

    $columnModel[] = [
        'name' => 'session',
        'index' => 'session',
        'width' => '180',
        'align' => 'left',
        'search' => 'false',
    ];
    $columnModel[] = [
        'name' => 'session_access_start_date',
        'index' => 'session_access_start_date',
        'width' => '170',
        'align' => 'center',
        'search' => 'true',
    ];
    $columnModel[] = [
        'name' => 'exe_date',
        'index' => 'exe_date',
        'width' => '170',
        'align' => 'left',
        'search' => 'true',
    ];
    $columnModel[] = [
        'name' => 'score',
        'index' => 'score',
        'width' => '140',
        'align' => 'center',
        'search' => 'true',
    ];

    if (!empty($categoryList)) {
        foreach ($categoryList as $categoryInfo) {
            $columnModel[] = [
                'name' => 'category_'.$categoryInfo['id'],
                'index' => 'category_'.$categoryInfo['id'],
                'width' => '150',
                'align' => 'center',
                'search' => 'true',
            ];
        }
    }

    $columnModel[] = [
        'name' => 'actions',
        'index' => 'actions',
        'width' => '120',
        'align' => 'left',
        'search' => 'false',
        'sortable' => 'false',
        'hidden' => 'true',
    ];

    $extra_params = [];
    $extra_params['autowidth'] = 'true';
    $extra_params['shrinkToFit'] = 'true';
    $extra_params['height'] = 'auto';

    $actionLinks = '
    // Add username as title in lastname field - ref 4226
    function action_formatter(cellvalue, options, rowObject) {
        var loginx = "'.api_htmlentities(sprintf(get_lang("Login: %s"), ":::"), ENT_QUOTES).'";
        var tabLoginx = loginx.split(/:::/);

        return "<span title=\""+tabLoginx[0]+rowObject[2]+tabLoginx[1]+"\">"+cellvalue+"</span>";
    }';

    $tableId = 'results'; ?>
    <script>
        function resizeExerciseCategoryGrid() {
            var $grid = jQuery("#<?php echo $tableId; ?>");
            var $wrapper = jQuery("#results-grid-wrapper");

            if (!$grid.length || !$wrapper.length) {
                return;
            }

            var width = $wrapper.innerWidth();
            if (width && width > 0) {
                $grid.jqGrid("setGridWidth", width, true);
            }
        }

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

            setTimeout(resizeExerciseCategoryGrid, 0);
            setTimeout(resizeExerciseCategoryGrid, 200);
            jQuery(window).off("resize.exerciseCategoryGrid").on("resize.exerciseCategoryGrid", function() {
                resizeExerciseCategoryGrid();
            });
        });
    </script>
    <?php

    // Results card
    echo '  <section class="reporting-admin-card bg-white rounded-xl shadow-sm border border-gray-200 w-full">';
    echo '      <div id="results-grid-wrapper" class="reporting-grid-wrapper p-4 md:p-5 overflow-x-auto">';

    echo '<script>
        $(function() {
            var myUrl = jQuery("#'.$tableId.'").jqGrid("getGridParam", "url");
            myUrl += "&export_format=xls&oper=excel";
            var postData = jQuery("#'.$tableId.'").jqGrid("getGridParam", "postData");
            $.each(postData, function(key, value) {
                myUrl += "&" + key + "=" + encodeURIComponent(value);
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
               title:"'.get_lang('Excel export').'",
               onClickButton : function() {
                   jQuery("#'.$tableId.'").jqGrid(
                    "excelExport",{
                        "url":"'.$url.'&export_format=xls"
                    }
                );
               }
            });

            setTimeout(resizeExerciseCategoryGrid, 0);
            setTimeout(resizeExerciseCategoryGrid, 200);
        });
    </script>';

    $items = [
        [
            'url' => '  ',
            'url_attributes' => ['id' => 'excel_export'],
            'content' => Display::getMdiIcon(
                ActionIcon::EXPORT_SPREADSHEET,
                'ch-tool-icon',
                null,
                ICON_SIZE_SMALL,
                get_lang('Excel export')
            ),
        ],
    ];

    echo Display::actions($items);
    echo Display::grid_html('results');

    echo '      </div>';
    echo '  </section>';
}

echo '</div>';

Display::display_footer();
