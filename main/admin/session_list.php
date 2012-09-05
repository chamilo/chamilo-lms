<?php
/* For licensing terms, see /license.txt */

$language_file = 'admin';
$cidReset = true;

require_once '../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script(true);

//Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();
$htmlHeadXtra[] = api_get_js('json-js/json2.js');
$htmlHeadXtra[] = api_get_js('date/date.js');

$htmlHeadXtra = api_get_datetime_picker_js($htmlHeadXtra);

$action = $_REQUEST['action'];
$idChecked = $_REQUEST['idChecked'];

if ($action == 'delete') {
	SessionManager::delete_session($idChecked);
	header('Location: session_list.php');
	exit();
} elseif ($action == 'copy') {
	SessionManager::copy_session($idChecked);
    header('Location: session_list.php');
    exit();
}

$interbreadcrumb[] = array("url" => "index.php","name" => get_lang('PlatformAdmin'));

$tool_name = get_lang('SessionList');
Display::display_header($tool_name);

$error_message = ''; // Avoid conflict with the global variable $error_msg (array type) in add_course.conf.php.
if (isset($_GET['action']) && $_GET['action'] == 'show_message') {
    $error_message = Security::remove_XSS($_GET['message']);
}

if (!empty($error_message)) {
    Display::display_normal_message($error_message, false);
}

//jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_sessions';
if (isset($_REQUEST['keyword'])) {
    //Begin with see the searchOper param
    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_sessions&_search=true&rows=20&page=1&sidx=&sord=asc&filters=&searchField=name&searchString='.Security::remove_XSS($_REQUEST['keyword']).'&searchOper=bw';    
}

//The order is important you need to check the the $column variable in the model.ajax.php file 

$columns = array(
    get_lang('Name'),     
    get_lang('SessionDisplayStartDate'),
    get_lang('SessionDisplayEndDate'),
    get_lang('SessionCategoryName'),
    //get_lang('StartDate'), 
    //get_lang('EndDate'), 
    get_lang('Coach'),
    get_lang('Status'), 
    //get_lang('CourseCode'),
    get_lang('CourseTitle'),
    get_lang('Visibility'),    
    get_lang('Actions'));

//$activeurl = '?sidx=session_active';
//Column config
$operators = array('cn', 'nc');
$date_operators = array('gt', 'ge', 'lt', 'le');

$column_model = array (
                array('name'=>'name',                'index'=>'name',          'width'=>'120',  'align'=>'left', 'search' => 'true'),    
                array('name'=>'display_start_date',  'index'=>'display_start_date', 'width'=>'70',   'align'=>'left', 'search' => 'true', 'searchoptions' => array('dataInit' => 'date_pick_today', 'sopt' => $date_operators)),
                array('name'=>'display_end_date',    'index'=>'display_end_date', 'width'=>'70',   'align'=>'left', 'search' => 'true', 'searchoptions' => array('dataInit' => 'date_pick_one_month', 'sopt' => $date_operators)),
                array('name'=>'category_name',       'index'=>'category_name', 'hidden' => 'true', 'width'=>'70',   'align'=>'left', 'search' => 'true', 'searchoptions' => array('searchhidden' =>'true', 'sopt' => $operators)),
                //array('name'=>'access_start_date',     'index'=>'access_start_date',    'width'=>'60',   'align'=>'left', 'search' => 'true',  'searchoptions' => array('searchhidden' =>'true')),
                //array('name'=>'access_end_date',       'index'=>'access_end_date',      'width'=>'60',   'align'=>'left', 'search' => 'true', 'searchoptions' => array('searchhidden' =>'true')),
                array('name'=>'coach_name',     'index'=>'coach_name',    'width'=>'70',   'align'=>'left', 'search' => 'false', 'searchoptions' => array('sopt' => $operators)),                        
                array('name'=>'status',         'index'=>'session_active','width'=>'40',   'align'=>'left', 'search' => 'true', 'stype'=>'select',

                      //for the bottom bar
                      'searchoptions' => array(                                            
                                        'defaultValue'  => '1', 
                                        'value'         => '1:'.get_lang('Active').';0:'.get_lang('Inactive')),

                      //for the top bar                              
                      //'editoptions' => array('value' => '" ":'.get_lang('All').';1:'.get_lang('Active').';0:'.get_lang('Inactive'))
                ),   
                //array('name'=>'course_code',    'index'=>'course_code',    'width'=>'40', 'hidden' => 'true', 'search' => 'true', 'searchoptions' => array('searchhidden' =>'true','sopt' => $operators)),
                array('name'=>'course_title',    'index'=>'course_title',   'width'=>'40',  'hidden' => 'true', 'search' => 'true', 'searchoptions' => array('searchhidden' =>'true','sopt' => $operators)),
                array('name'=>'visibility',     'index'=>'visibility',      'width'=>'40',   'align'=>'left', 'search' => 'false'),                        
                array('name'=>'actions',        'index'=>'actions',         'width'=>'80',  'align'=>'left','formatter'=>'action_formatter','sortable'=>'false', 'search' => 'false')
); 

//Inject extra session fields
$session_field = new SessionField();
$session_field_option = new SessionFieldOption();
$fields = $session_field->get_all(); 

$rules = array();
$now = new DateTime();
$now->add(new DateInterval('P30D'));
$one_month = $now->format('Y-m-d h:m:s');

//$rules[] = array( "field" => "name", "op" => "cn", "data" => "");
//$rules[] = array( "field" => "category_name", "op" => "cn", "data" => "");


$rules[] = array( "field" => "display_start_date", "op" => "ge", "data" => api_get_local_time());
$rules[] = array( "field" => "display_end_date", "op" => "le", "data" => api_get_local_time($one_month));
//$rules[] = array( "field" => "course_code", "op" => "cn", "data" => '');
$rules[] = array( "field" => "course_title", "op" => "cn", "data" => '');

if (!empty($fields)) {    
    foreach ($fields as $field) {        
        $search_options = array();
        
        $type = 'text';
        if ($field['field_type'] == UserManager::USER_FIELD_TYPE_SELECT) {
            $type = 'select';
            $search_options['sopt'] = array('eq', 'ne'); //equal not equal
        } else {
            $search_options['sopt'] = array('cn', 'nc');//contains not contains
        }
        
        $search_options['searchhidden'] = 'true';
        $search_options['defaultValue'] = $search_options['field_default_value'];
        $search_options['value'] = $session_field_option->get_field_options_to_string($field['id']);
        //$search_options['dataInit'] = 'datePick';
              
        $column_model[] = array(
            'name' => $field['field_variable'],
            'index' => 'extra_'.$field['field_variable'],
            'width' => '100',
            'hidden' => 'true',
            'search' => 'true',
            'stype' => $type,
            'searchoptions' => $search_options
        );        
        $columns[] = $field['field_display_text'];
        $rules[] = array('field' => 'extra_'.$field['field_variable'], 'op' => 'eq');        
    }
    
    /*$groups = array(
        'groupOp'=> 'OR', 
        'rules' => $rules
    );*/
}

                       
//Autowidth             
$extra_params['autowidth'] = 'true';

//height auto 
$extra_params['height'] = 'auto';
//$extra_params['excel'] = 'excel';

$extra_params['rowList'] = array(10, 20 ,30);

$extra_params['postData'] =array (
                    'filters' => array(                                        
                                        "groupOp" => "OR",                                         
                                        "rules" => $rules, 
                                        /*array(
                                            array( "field" => "display_start_date", "op" => "gt", "data" => ""),
                                            array( "field" => "display_end_date", "op" => "gt", "data" => "")
                                        ),*/ 
                                        //'groups' => $groups
                                ), 
                                
);
/*
     $filters = array('filters' => array( "groupOp" => "AND", 
                                        "rules" => $rules));
    
     $filters = json_encode($filters);
 var_dump($filters);*/

//With this function we can add actions to the jgrid (edit, delete, etc)
$action_links = 'function action_formatter(cellvalue, options, rowObject) {
                         return \'<a href="session_add.php?page=resume_session.php&id=\'+options.rowId+\'">'.Display::return_icon('edit.png',get_lang('Edit'),'',ICON_SIZE_SMALL).'</a>'.
                         '&nbsp;<a href="add_users_to_session.php?page=session_list.php&id_session=\'+options.rowId+\'">'.Display::return_icon('user_subscribe_session.png',get_lang('SubscribeUsersToSession'),'',ICON_SIZE_SMALL).'</a>'.
                         '&nbsp;<a href="add_courses_to_session.php?page=session_list.php&id_session=\'+options.rowId+\'">'.Display::return_icon('courses_to_session.png',get_lang('SubscribeCoursesToSession'),'',ICON_SIZE_SMALL).'</a>'.
                         '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES))."\'".')) return false;"  href="session_list.php?action=copy&idChecked=\'+options.rowId+\'">'.Display::return_icon('copy.png',get_lang('Copy'),'',ICON_SIZE_SMALL).'</a>'.
                         '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES))."\'".')) return false;"  href="session_list.php?action=delete&idChecked=\'+options.rowId+\'">'.Display::return_icon('delete.png',get_lang('Delete'),'',ICON_SIZE_SMALL).'</a>'.
                         '\'; 
                 }';
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

    <?php 
        echo Display::grid_js('sessions', $url, $columns, $column_model, $extra_params, array(), $action_links,true);      
    ?>   
    
    setSearchSelect("status");
    
    var grid = $("#sessions"),
    prmSearch = { 
        multipleSearch : true, 
        overlay : false, 
        width:600,
        onSearch : function(){
            var postdata = grid.jqGrid('getGridParam', 'postData');            
            if (postdata && postdata.filters) {
                filters = jQuery.parseJSON(postdata.filters);
                $.each(filters, function(key, value){  
                    if (key == 'rules') {
                        $.each(value, function(key, value){  
                            console.log(value.field);
                            grid.showCol(value.field);
                        });
                    }                    
                });
            }
       },
       onReset: function() {            
       }
    };
    
    grid.jqGrid('navGrid','#sessions_pager', 
        {edit:false,add:false,del:false},
        {height:280,reloadAfterSubmit:false}, // edit options 
        {height:280,reloadAfterSubmit:false}, // add options 
        {reloadAfterSubmit:false},// del options 
        prmSearch
    );
                  
    // create the searching dialog
    grid.searchGrid(prmSearch);

    //var searchDialog = $("#fbox_"+grid[0].id);
    var searchDialog = $("#searchmodfbox_"+grid[0].id);    
    searchDialog.addClass("ui-jqgrid ui-widget ui-widget-content ui-corner-all");
    searchDialog.css({position:"relative", "z-index":"auto", "float":"left"})    
    var gbox = $("#gbox_"+grid[0].id);
    gbox.before(searchDialog);
    gbox.css({clear:"left"});
    
    

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
    /*
    grid.jqGrid('filterToolbar', options);    
    var sgrid = $("#sessions")[0];
    sgrid.triggerToolbar();*/
    
    /*
    $('form').submit(function() {
       var start = $("#start_date_start").datetimepicker("getDate");
       //var params = $(this).serialize();      
    
        var filter = {
            "groupOp":"OR"
        }
        var rules = [];
        
        $.each($('form').serializeArray(), function(i, field) {            
             rule = {'field': field.name,"op":"eq","data":field.value};
             rules.push(rule);
        });
        
        filter['rules'] = rules;
                
        filter = JSON.stringify(filter);
        console.log(filter);       
         
       jQuery("#sessions").jqGrid('setGridParam',{
           url:"<?php echo $url ?>&search_form=1&filters="+filter,
           page:1
       }).trigger("reloadGrid");
       
       console.log(start);
       return false;       
    });*/
    
    
    
});
</script>
<div class="actions">
<?php 
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/session_add.php">'.Display::return_icon('new_session.png',get_lang('AddSession'),'',ICON_SIZE_MEDIUM).'</a>';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/add_many_session_to_category.php">'.Display::return_icon('session_to_category.png',get_lang('AddSessionsInCategories'),'',ICON_SIZE_MEDIUM).'</a>';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/session_category_list.php">'.Display::return_icon('folder.png',get_lang('ListSessionCategory'),'',ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

$form = new FormValidator('search');

$form->addElement('header', get_lang('Filter'));

$form->addElement('text', 'start_date_start', get_lang('Between'), array('id' =>'start_date_start'));
$form->addElement('text', 'start_date_end', get_lang('And'), array('id' =>'start_date_end'));
$renderer = $form->defaultRenderer();

$renderer->setElementTemplate(get_lang('StartDate').' {label} {element}', 'start_date_start');  
$renderer->setElementTemplate('{label} {element}', 'start_date_end');  

$form->addElement('html', '<div class="clear"></div>');

$form->addElement('text', 'end_date_start', get_lang('Between'), array('id' =>'end_date_start'));
$form->addElement('text', 'end_date_end', get_lang('And'), array('id' =>'end_date_end'));

$renderer->setElementTemplate(get_lang('EndDate').' {label} {element}', 'end_date_start');  
$renderer->setElementTemplate('{label} {element}', 'end_date_end');  
           
$options = CourseManager::get_course_list_of_user_as_course_admin(api_get_user_id());
$form->addElement('select', 'course', get_lang('Course'), $options);


//$session_field->add_elements($form);

$form->addElement('button', 'submit', get_lang('Search'), array('id' => 'search_button'));

//$form->display();

echo Display::grid_html('sessions');
Display::display_footer();