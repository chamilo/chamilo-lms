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
	SessionManager::copy_session($idChecked, true, false);
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

//Autowidth             
$extra_params['autowidth'] = 'true';
//Height auto 
$extra_params['height'] = 'auto';

$extra_params['rowList'] = array(10, 20 ,30);

$result = SessionManager::get_session_columns();
$columns = $result['columns'];
$column_model = $result['column_model'];

$extra_params['postData'] =array (
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
$url_select = api_get_path(WEB_AJAX_PATH).'extra_field.ajax.php?1=1';
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
                url: "<?php echo $url_select ?>&a=get_second_select_options", 
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
        width: 600,
        caption: '<?php echo addslashes(get_lang('Search')); ?>',
        formclass:'data_table',
        onSearch : function() {
            var postdata = grid.jqGrid('getGridParam', 'postData');
                        
            if (postdata && postdata.filters) {
                filters = jQuery.parseJSON(postdata.filters);
                clean_cols(grid, added_cols);
                added_cols = [];                
                $.each(filters, function(key, value){
                    if (key == 'rules') {
                        $.each(value, function(key, value) {
                            //if (added_cols[value.field] == undefined) {
                                added_cols[value.field] = value.field;
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
                  
    // create the searching dialog
    grid.searchGrid(prmSearch);
    
    //Fixes search table
    var searchDialogAll = $("#fbox_"+grid[0].id);
    searchDialogAll.addClass("table");
    
    var searchDialog = $("#searchmodfbox_"+grid[0].id);    
    searchDialog.addClass("ui-jqgrid ui-widget ui-widget-content ui-corner-all");
    searchDialog.css({position:"relative", "z-index":"auto", "float":"left"})    
    var gbox = $("#gbox_"+grid[0].id);
    gbox.before(searchDialog);
    gbox.css({clear:"left"});
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