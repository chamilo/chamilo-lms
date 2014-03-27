<?php
/* For licensing terms, see /license.txt */
/**
 * List sessions in an efficient and usable way
 * @package chamilo.admin
 */
/**
 * Code
 */
$language_file = array('admin', 'courses');
$cidReset = true;

require_once '../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script(true);

//Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
$idChecked = isset($_REQUEST['idChecked']) ? $_REQUEST['idChecked'] : null;

if ($action == 'delete') {
	SessionManager::delete_session($idChecked);
	header('Location: session_list.php');
	exit();
} elseif ($action == 'copy') {
	SessionManager::copy_session($idChecked);
    header('Location: session_list.php');
    exit();
}

$interbreadcrumb[]=array("url" => "index.php","name" => get_lang('PlatformAdmin'));

$tool_name = get_lang('SessionList');
Display::display_header($tool_name);

$error_message = ''; // Avoid conflict with the global variable $error_msg (array type) in add_course.conf.php.
if (isset($_GET['action']) && $_GET['action'] == 'show_message') {
    $error_message = Security::remove_XSS($_GET['message']);
}

if (!empty($error_message)) {
    Display::display_normal_message($error_message, false);
}

$sessionFilter = new FormValidator('course_filter', 'get', '', '', array('class'=> 'form-search'), false);
$url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_course';
$courseList = array();
$courseId = isset($_GET['course_id']) ? $_GET['course_id'] : null;
if (!empty($courseId)) {
    require_once api_get_path(LIBRARY_PATH).'course_category.lib.php';
    $courseInfo = api_get_course_info_by_id($courseId);
    $parents = getParentsToString($courseInfo['categoryCode']);
    $courseList[] = array('id' => $courseInfo['id'], 'text' => $parents.$courseInfo['title']);
}
$sessionFilter->addElement('select_ajax', 'course_name', get_lang('SearchCourse'), null, array('url' => $url, 'defaults' => $courseList));
$url = api_get_self();
$actions = '
<script>
$(function() {
    $("#course_name").on("change", function() {
       var courseId = $(this).val();
       console.log(courseId);
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

//The order is important you need to check the the $column variable in the model.ajax.php file
$columns = array(
    get_lang('Name'),
    get_lang('NumberOfCourses'),
    get_lang('NumberOfUsers'),
    get_lang('SessionCategoryName'),
    get_lang('StartDate'),
    get_lang('EndDate'),
    get_lang('Coach'),
    get_lang('Status'),
    get_lang('Visibility'),
    get_lang('Actions')
);

//Column config
$column_model   = array(
    array('name'=>'name',           'index'=>'s.name',        'width'=>'160',  'align'=>'left', 'search' => 'true', 'wrap_cell' => "true"),
    array('name'=>'nbr_courses',    'index'=>'nbr_courses',   'width'=>'30',   'align'=>'left', 'search' => 'true'),
    array('name'=>'nbr_users',      'index'=>'nbr_users',     'width'=>'30',   'align'=>'left', 'search' => 'true'),
    array('name'=>'category_name',  'index'=>'sc.name', 'width'=>'70',   'align'=>'left', 'search' => 'true'),
    array('name'=>'date_start',     'index'=>'s.date_start',    'width'=>'40',   'align'=>'left', 'search' => 'true'),
    array('name'=>'date_end',       'index'=>'s.date_end',      'width'=>'40',   'align'=>'left', 'search' => 'true'),
    array('name'=>'coach_name',     'index'=>'coach_name',    'width'=>'80',   'align'=>'left', 'search' => 'false'),
    array('name'=>'status',         'index'=>'session_active','width'=>'50',   'align'=>'left', 'search' => 'true', 'stype'=>'select',
        //for the bottom bar
        'searchoptions' => array(
            'defaultValue'  => ':',
            'value'         => ':'.get_lang('All').';1:'.get_lang('Active').';0:'.get_lang('Inactive')
        ),
        //for the top bar
        'editoptions' => array(
            'value' => ':'.get_lang('All').';1:'.get_lang('Active').';0:'.get_lang('Inactive')
        )
    ),
    array('name'=>'visibility',     'index'=>'visibility',      'width'=>'40',   'align'=>'left', 'search' => 'false'),
    array('name'=>'actions',        'index'=>'actions',         'width'=>'100',  'align'=>'left','formatter'=>'action_formatter','sortable'=>'false', 'search' => 'false')
);
//Autowidth
$extra_params['autowidth'] = 'true';

//height auto
$extra_params['height'] = 'auto';

//With this function we can add actions to the jgrid (edit, delete, etc)
$action_links = 'function action_formatter(cellvalue, options, rowObject) {
     return \'<a href="session_edit.php?page=resume_session.php&id=\'+options.rowId+\'">'.Display::return_icon('edit.png',get_lang('Edit'),'',ICON_SIZE_SMALL).'</a>'.
     '&nbsp;<a href="add_users_to_session.php?page=session_list.php&id_session=\'+options.rowId+\'">'.Display::return_icon('user_subscribe_session.png',get_lang('SubscribeUsersToSession'),'',ICON_SIZE_SMALL).'</a>'.
     '&nbsp;<a href="add_courses_to_session.php?page=session_list.php&id_session=\'+options.rowId+\'">'.Display::return_icon('courses_to_session.png',get_lang('SubscribeCoursesToSession'),'',ICON_SIZE_SMALL).'</a>'.
     '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES))."\'".')) return false;"  href="session_list.php?action=copy&idChecked=\'+options.rowId+\'">'.Display::return_icon('copy.png',get_lang('Copy'),'',ICON_SIZE_SMALL).'</a>'.
     '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES))."\'".')) return false;"  href="session_list.php?action=delete&idChecked=\'+options.rowId+\'">'.Display::return_icon('delete.png',get_lang('Delete'),'',ICON_SIZE_SMALL).'</a>'.
     '\';
}';
?>
<script>

function setSearchSelect(columnName) {
    $("#sessions").jqGrid(
        'setColProp',
        columnName,
        {
            searchoptions: {
                dataInit:function(el) {
                    $("option[value='2']",el).attr("selected", "selected");
                    setTimeout(function() {
                        $(el).trigger('change');
                    }, 1000);
                }
            }
        }
    );
}

$(function() {
    <?php
        echo Display::grid_js('sessions', $url, $columns, $column_model, $extra_params, array(), $action_links, true);
    ?>

    setSearchSelect("status");

    $("#sessions").jqGrid(
        'navGrid',
        '#sessions_pager',
        { edit: false, add: false, del: false},
        { height:280, reloadAfterSubmit:false }, // edit options
        { height:280, reloadAfterSubmit:false }, // add options
        { reloadAfterSubmit:false }, // del options
        { width:500 } // search options
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

    // Adding search options
    var options = {
        'stringResult': true,
        'autosearch' : true,
        'searchOnEnter':false
    }

    jQuery("#sessions").jqGrid('filterToolbar', options);
    var sgrid = $("#sessions")[0];
    sgrid.triggerToolbar();
});
</script>
<div class="actions">
<?php

echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/session_add.php">'.Display::return_icon('new_session.png',get_lang('AddSession'),'',ICON_SIZE_MEDIUM).'</a>';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/add_many_session_to_category.php">'.Display::return_icon('session_to_category.png',get_lang('AddSessionsInCategories'),'',ICON_SIZE_MEDIUM).'</a>';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/session_category_list.php">'.Display::return_icon('folder.png',get_lang('ListSessionCategory'),'',ICON_SIZE_MEDIUM).'</a>';

echo $actions;
echo '<div class="pull-right">';
echo $sessionFilter->return_form();
echo '</div>';
echo '</div>';
echo Display::grid_html('sessions');
Display::display_footer();


