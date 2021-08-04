<?php
/* For licensing terms, see /license.txt */

/**
 * List sessions in an efficient and usable way.
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_teacher_script(true);

// Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();
$tool_name = get_lang('SessionList');
$allowTutors = api_get_setting('allow_tutors_to_assign_students_to_session');

if ($allowTutors !== 'true') {
    api_not_allowed(true);
}

Display::display_header($tool_name);

// jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_sessions&from_course_session=1';
if (isset($_REQUEST['keyword'])) {
    //Begin with see the searchOper param
    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_sessions&from_course_session=1&_search=true&rows=20&page=1&sidx=&sord=asc&filters=&searchField=name&searchString='.Security::remove_XSS($_REQUEST['keyword']).'&searchOper=bw';
}

//The order is important you need to check the the $column variable in the model.ajax.php file
$columns = [
    get_lang('Name'),
    get_lang('NumberOfCourses'),
    get_lang('NumberOfUsers'),
    get_lang('SessionCategoryName'),
    get_lang('StartDate'),
    get_lang('EndDate'),
    get_lang('Coach'),
    get_lang('Status'),
    get_lang('Visibility'),
    get_lang('Actions'),
];

//Column config
$column_model = [
    ['name' => 'name', 'index' => 'name', 'width' => '160', 'align' => 'left', 'search' => 'true', 'wrap_cell' => "true"],
    ['name' => 'nbr_courses', 'index' => 'nbr_courses', 'width' => '30', 'align' => 'left', 'search' => 'true'],
    ['name' => 'nbr_users', 'index' => 'nbr_users', 'width' => '30', 'align' => 'left', 'search' => 'true'],
    ['name' => 'category_name', 'index' => 'category_name', 'width' => '70', 'align' => 'left', 'search' => 'true'],
    ['name' => 'access_start_date', 'index' => 'access_start_date', 'width' => '40', 'align' => 'left', 'search' => 'true'],
    ['name' => 'access_end_date', 'index' => 'access_end_date', 'width' => '40', 'align' => 'left', 'search' => 'true'],
    ['name' => 'coach_name', 'index' => 'coach_name', 'width' => '80', 'align' => 'left', 'search' => 'false'],
    ['name' => 'status', 'index' => 'session_active', 'width' => '40', 'align' => 'left', 'search' => 'true', 'stype' => 'select',
      //for the bottom bar
        'searchoptions' => [
            'defaultValue' => '1',
            'value' => '1:'.get_lang('Active').';0:'.get_lang('Inactive'),
        ],
      //for the top bar
      'editoptions' => ['value' => ':'.get_lang('All').';1:'.get_lang('Active').';0:'.get_lang('Inactive')], ],
    ['name' => 'visibility', 'index' => 'visibility', 'width' => '40', 'align' => 'left', 'search' => 'false'],
    ['name' => 'actions', 'index' => 'actions', 'width' => '100', 'align' => 'left', 'formatter' => 'action_formatter', 'sortable' => 'false', 'search' => 'false'],
];
//Autowidth
$extra_params['autowidth'] = 'true';

//height auto
$extra_params['height'] = 'auto';
//$extra_params['excel'] = 'excel';
//$extra_params['rowList'] = array(10, 20 ,30);

//With this function we can add actions to the jgrid (edit, delete, etc)
$action_links = 'function action_formatter(cellvalue, options, rowObject) {
     return \'&nbsp;<a href="add_users_to_session.php?page=session_list.php&id_session=\'+options.rowId+\'">'.Display::return_icon('user_subscribe_session.png', get_lang('SubscribeUsersToSession'), '', ICON_SIZE_SMALL).'</a>'.
     '\';
}';
?>
<script>
    function setSearchSelect(columnName) {
    $("#sessions").jqGrid('setColProp', columnName,
    {
       searchoptions:{
            dataInit:function(el){
                $("option[value='2']",el).attr("selected", "selected");
                setTimeout(function(){
                    $(el).trigger('change');
                },1000);
            }
        }
    });
}

$(function() {
    <?php
        echo Display::grid_js('sessions', $url, $columns, $column_model, $extra_params, [], $action_links, true);
    ?>

    setSearchSelect("status");
    $("#sessions").jqGrid('navGrid','#sessions_pager', {edit:false,add:false,del:false},
        {height:280,reloadAfterSubmit:false}, // edit options
        {height:280,reloadAfterSubmit:false}, // add options
        {reloadAfterSubmit:false}, // del options
        {width:500} // search options
    );
    /*
    // add custom button to export the data to excel
    jQuery("#sessions").jqGrid('navButtonAdd','#sessions_pager',{
           caption:"",
           onClickButton : function () {
               jQuery("#sessions").excelExport();
           }
    });

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
    jQuery("#sessions").jqGrid('filterToolbar',options);
    var sgrid = $("#sessions")[0];
    sgrid.triggerToolbar();
});
</script>
<?php

if (api_is_platform_admin()) {
    echo '<div class="actions">';
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'session/session_add.php">'.
        Display::return_icon('new_session.png', get_lang('AddSession'), '', ICON_SIZE_MEDIUM).'</a>';
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'session/add_many_session_to_category.php">'.
        Display::return_icon('session_to_category.png', get_lang('AddSessionsInCategories'), '', ICON_SIZE_MEDIUM).'</a>';
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'session/session_category_list.php">'.
        Display::return_icon('folder.png', get_lang('ListSessionCategory'), '', ICON_SIZE_MEDIUM).'</a>';
    echo '</div>';
}
echo Display::grid_html('sessions');
Display::display_footer();
