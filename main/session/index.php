<?php
// Language files that should be included.
$language_file = array('courses', 'index');
require_once '../inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'course.lib.php';
//require_once $libpath.'usermanager.lib.php';
require_once $libpath.'sessionmanager.lib.php';
require_once $libpath.'formvalidator/FormValidator.class.php';
require_once $libpath.'text.lib.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpathList.class.php';

api_block_anonymous_users(); // Only users who are logged in can proceed.


$this_section = SECTION_COURSES;

$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-ui/cupertino/jquery-ui-1.8.7.custom.css" type="text/css">';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-1.4.4.min.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-ui/cupertino/jquery-ui-1.8.7.custom.min.js" type="text/javascript" language="javascript"></script>';

$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jqgrid/css/ui.jqgrid.css" type="text/css">';

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jqgrid/js/i18n/grid.locale-en.js" type="text/javascript" language="javascript"></script>'; 
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jqgrid/js/jquery.jqGrid.min.js" type="text/javascript" language="javascript"></script>';

    
Display :: display_header($nameTools);



$session_id     = intval($_GET['session_id']);
$session_info   = SessionManager::fetch($session_id);
$session_list   = SessionManager::get_sessions_by_coach(api_get_user_id());
$course_list    = SessionManager::get_course_list_by_session_id($session_id);
$course_select = array();

$session_select = array();
foreach ($session_list as $item) {
    $session_select[$item['id']] =  $item['name'];
}
/*
foreach ($course_list as $course_item) {
	$course_select[$course_item['id']] =  $course_item['title'];
}*/
// Session list form
$form = new FormValidator('exercise_admin', 'get', api_get_self().'?session_id='.$session_id);
$form->addElement('select', 'session_id', get_lang('SessionList'), $session_select, 'onchange="javascript:change_session()"');
$defaults['session_id'] = $session_id;
$form->setDefaults($defaults);
$form->display();

echo Display::tag('h1', $session_info['name']);


//Listing LPs from all courses
$lps = array();

foreach ($course_list as $item) {    
    $list       = new LearnpathList(api_get_user_id(),$item['code']);
    $flat_list  = $list->get_flat_list();        
    $lps[$item['code']] = $flat_list;
    foreach ($flat_list as $item) {        
        //var_dump(get_week_from_day($item['publicated_on']));	
    }
    
}






if ($form->validate()) {
    
}

?>
<br />
<script>
    function change_session() {
            document.exercise_admin.submit();
    }
        
    
$(function() {
    $( "#tabs" ).tabs();
    $( "#sub_tab" ).tabs();
        
      
    $("#list_default").jqGrid({
        url:'<?php echo api_get_path(WEB_AJAX_PATH).'course_home.ajax.php?a=session_courses_lp_default&session_id='.$session_id; ?>',
        datatype: 'json',    
        colNames:['Date','Course', 'LP'],
        colModel :[       
          {name:'date',     index:'date',   width:120, align:'right'},
          {name:'course',   index:'course', width:150},  
          {name:'lp',       index:'lp',     width:250} 
        ],
        pager: '#pager1',
        rowNum:100,
        /* rowList:[10,20,30], */   
        sortname: 'date',
        sortorder: 'desc',
        viewrecords: true    
    });
      
    
    $("#list_course").jqGrid({
        url:'<?php echo api_get_path(WEB_AJAX_PATH).'course_home.ajax.php?a=session_courses_lp_by_course&session_id='.$session_id; ?>',
        datatype: 'json',    
        colNames:['Date','Course', 'LP'],
        colModel :[             
          {name:'date',     index:'date',   width:120},
          {name:'course',   index:'course', width:150},  
          {name:'lp',       index:'lp',     width:250} 
        ],
        pager: '#pager2',
        rowNum:100,
        /* rowList:[10,20,30], */  
        sortname: 'date',
        sortorder: 'desc',
        viewrecords: true,
        grouping:true, 
        groupingView : { 
            groupField : ['course'],
            groupColumnShow : [false],
            groupText : ['<b>Course {0} - {1} Item(s)</b>']
        } 
    });  
  
  
  
    $("#list_week").jqGrid({
        url:'<?php echo api_get_path(WEB_AJAX_PATH).'course_home.ajax.php?a=session_courses_lp_by_week&session_id='.$session_id; ?>',
        datatype: 'json',    
        colNames:['Week','Date','Course', 'LP'],
        colModel :[
          {name:'week',     index:'week',   width:120, align:'right'},       
          {name:'date',     index:'date',   width:120, align:'right'},
          {name:'course',   index:'course', width:150},  
          {name:'lp',       index:'lp',     width:250} 
        ],
        pager: '#pager3',
        rowNum:100,
        /* rowList:[10,20,30], */   
        sortname: 'date',
        sortorder: 'desc',
        viewrecords: true,
        grouping:true, 
        groupingView : { 
            groupField : ['week'],
            groupColumnShow : [false],
            groupText : ['<b>Week {0} - {1} Item(s)</b>']
        } 
    });  
  
});



</script>

<?php 
$lp_table1 = Display::tag('table','',array('id'=>'list_default'));
$lp_table1 .= Display::tag('div','',array('id'=>'pager1'));  

$lp_table2 = Display::tag('table','',array('id'=>'list_week'));
$lp_table2 .= Display::tag('div','',array('id'=>'pager2'));

$lp_table3 = Display::tag('table','',array('id'=>'list_course'));
$lp_table3 .= Display::tag('div','',array('id'=>'pager3'));


$headers = array(get_lang('LearningPaths'), get_lang('MyQCM'), get_lang('MyResults'));
$sub_header = array(get_lang('AllLearningPaths'), get_lang('PerWeek'), get_lang('ByCourse'));
$tabs =  Display::tabs($sub_header, array($lp_table1,$lp_table2, $lp_table3),'sub_tab');
echo Display::tabs($headers, array($tabs,'bbb','ccc'));

// Footer
Display :: display_footer();