<?php

/* For licensing terms, see /license.txt */

ob_start();
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$this_section = SECTION_TRACKING;
$export_csv = false;
if (isset($_REQUEST['export']) && $_REQUEST['export'] == 'csv') {
    $export_csv = true;
}

$id_coach = api_get_user_id();
if (isset($_REQUEST['id_coach']) && $_REQUEST['id_coach'] != '') {
    $id_coach = (int) $_REQUEST['id_coach'];
}

$allowToTrack = api_is_platform_admin(true, true) || api_is_teacher();

if (!$allowToTrack) {
    api_not_allowed(true);
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
    [], //exclude
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
            };
            grid.showCol('name');
            grid.showCol('display_start_date');
            grid.showCol('display_end_date');
            grid.showCol('course_title');
        }

        function show_cols(grid, added_cols) {
            grid.showCol('name').trigger('reloadGrid');
            for (key in added_cols) {
                grid.showCol(key);
            };
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
