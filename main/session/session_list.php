<?php
/* For licensing terms, see /license.txt */

/**
 * List sessions in an efficient and usable way
 * @package chamilo.admin
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

SessionManager::protectSession(null, false);

//Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
$idChecked = isset($_REQUEST['idChecked']) ? $_REQUEST['idChecked'] : null;
$list_type = isset($_REQUEST['list_type']) ? $_REQUEST['list_type'] : 'simple';

if ($action == 'delete') {
    $response = SessionManager::delete($idChecked);

    if ($response) {
        Display::addFlash(Display::return_message(get_lang('Deleted')));
    }
    header('Location: session_list.php');
    exit();
} elseif ($action == 'copy') {
    $result = SessionManager::copy($idChecked);
    if ($result) {
        Display::addFlash(Display::return_message(get_lang('ItemCopied')));
    } else {
        Display::addFlash(Display::return_message(get_lang('ThereWasAnError'), 'error'));
    }
    header('Location: session_list.php');
    exit();
}

$tool_name = get_lang('SessionList');
Display::display_header($tool_name);

$courseId = isset($_GET['course_id']) ? $_GET['course_id'] : null;

$sessionFilter = new FormValidator(
    'course_filter',
    'get',
    '',
    '',
    array(),
    FormValidator::LAYOUT_INLINE
);
$courseSelect = $sessionFilter->addElement(
    'select_ajax',
    'course_name',
    get_lang('SearchCourse'),
    null,
    array('url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_course')
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
    //Begin with see the searchOper param
    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_sessions&_force_search=true&rows=20&page=1&sidx=&sord=asc&filters=&searchField=s.name&searchString='.Security::remove_XSS($_REQUEST['keyword']).'&searchOper=bw';
}

if (isset($_REQUEST['id_category'])) {
    $sessionCategory = SessionManager::get_session_category($_REQUEST['id_category']);
    if (!empty($sessionCategory)) {
        //Begin with see the searchOper param
        $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_sessions&_force_search=true&rows=20&page=1&sidx=&sord=asc&filters=&searchField=sc.name&searchString='.Security::remove_XSS($sessionCategory['name']).'&searchOper=bw';
    }
}

$url .= '&list_type='.$list_type;

$result = SessionManager::getGridColumns($list_type);

$columns = $result['columns'];
$column_model = $result['column_model'];

// Autowidth
$extra_params['autowidth'] = 'true';

// height auto
$extra_params['height'] = 'auto';

$extra_params['postData'] = array(
    'filters' => array(
        "groupOp" => "AND",
        "rules" => $result['rules'],
        /*array(
            array( "field" => "display_start_date", "op" => "gt", "data" => ""),
            array( "field" => "display_end_date", "op" => "gt", "data" => "")
        ),*/
        //'groups' => $groups
    )
);

//With this function we can add actions to the jgrid (edit, delete, etc)
$action_links = 'function action_formatter(cellvalue, options, rowObject) {
     return \'<a href="session_edit.php?page=resume_session.php&id=\'+options.rowId+\'">'.Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a>'.
    '&nbsp;<a href="add_users_to_session.php?page=session_list.php&id_session=\'+options.rowId+\'">'.Display::return_icon('user_subscribe_session.png', get_lang('SubscribeUsersToSession'), '', ICON_SIZE_SMALL).'</a>'.
    '&nbsp;<a href="add_courses_to_session.php?page=session_list.php&id_session=\'+options.rowId+\'">'.Display::return_icon('courses_to_session.png', get_lang('SubscribeCoursesToSession'), '', ICON_SIZE_SMALL).'</a>'.
    '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES))."\'".')) return false;"  href="session_list.php?action=copy&idChecked=\'+options.rowId+\'">'.Display::return_icon('copy.png', get_lang('Copy'), '', ICON_SIZE_SMALL).'</a>'.
    '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES))."\'".')) return false;"  href="session_list.php?action=delete&idChecked=\'+options.rowId+\'">'.Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>'.
    '\';
}';

$urlAjaxExtraField = api_get_path(WEB_AJAX_PATH).'extra_field.ajax.php?1=1';

?>
    <script>
        function setSearchSelect(columnName) {
            $("#sessions").jqGrid('setColProp', columnName, {
                /*searchoptions:{
                 dataInit:function(el){
                 $("option[value='1']",el).attr("selected", "selected");
                 setTimeout(function(){
                 $(el).trigger('change');
                 }, 1000);
                 }
                 }*/
            });
        }
        var added_cols = [];
        var original_cols = [];

        function clean_cols(grid, added_cols) {
            //Cleaning
            for (key in added_cols) {
                //console.log('hide: ' + key);
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
                //console.log('show: ' + key);
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
            echo Display::grid_js('sessions', $url, $columns, $column_model, $extra_params, array(), $action_links, true);
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
                            $.each(filters, function(key, value){
                                //console.log('key: ' + key );
                                if (key == 'rules') {
                                    $.each(value, function(subkey, subvalue) {
                                        if (subvalue.data == undefined) {
                                        }

                                        //if (added_cols[value.field] == undefined) {
                                        added_cols[subvalue.field] = subvalue.field;
                                        //}
                                        //grid.showCol(value.field);
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

            // Create the searching dialog.
            grid.searchGrid(prmSearch);

            // Fixes search table.
            var searchDialogAll = $("#fbox_"+grid[0].id);
            searchDialogAll.addClass("table");
            var searchDialog = $("#searchmodfbox_"+grid[0].id);
            searchDialog.addClass("ui-jqgrid ui-widget ui-widget-content ui-corner-all");
            searchDialog.css({position:"adsolute", "z-index":"100", "float":"left", "top":"55%", "left" : "25%", "padding" : "5px", "border": "1px solid #CCC"})
            var gbox = $("#gbox_"+grid[0].id);
            gbox.before(searchDialog);
            gbox.css({clear:"left"});

            //Select first elements by default
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

if ($list_type == 'complete') {
    echo '<a href="'.api_get_self().'?list_type=simple">'.Display::return_icon('view_remove.png', get_lang('Simple'), '', ICON_SIZE_MEDIUM).'</a>';
} else {
    echo '<a href="'.api_get_self().'?list_type=complete">'.Display::return_icon('view_text.png', get_lang('Complete'), '', ICON_SIZE_MEDIUM).'</a>';
}

echo $actions;
if (api_is_platform_admin()) {
    echo '<div class="pull-right">';
    // Create a search-box
    $form = new FormValidator(
        'search_simple',
        'get',
        '',
        '',
        [],
        FormValidator::LAYOUT_INLINE
    );
    $form->addElement('text', 'keyword', null, array(
        'aria-label' => get_lang('Search')
    ));
    $form->addButtonSearch(get_lang('Search'));
    $form->display();
    echo '</div>';

    echo '<div class="pull-right">';
    echo $sessionFilter->returnForm();
    echo '</div>';
}
echo '</div>';
echo '<div id="session-table" class="table-responsive">';
echo Display::grid_html('sessions');
echo '</div>';

Display::display_footer();


