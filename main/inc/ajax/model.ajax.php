<?php
/* For licensing terms, see /license.txt */

//@todo this could be integrated in the inc/lib/model.lib.php + try to clean this file, is not very well tested yet!
require_once '../global.inc.php';

api_protect_admin_script(true);

$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'array.lib.php';

// 1. Setting variables needed by jqgrid
$action= $_GET['a'];
$page  = intval($_REQUEST['page']); //page
$limit = intval($_REQUEST['rows']); //quantity of rows
$sidx  = $_REQUEST['sidx'];         //index to filter         
$sord  = $_REQUEST['sord'];         //asc or desc
if (!in_array($sord, array('asc','desc'))) {
    $sord = 'desc'; 
}
// get index row - i.e. user click to sort $sord = $_GET['sord']; 
// get the direction 
if (!$sidx) $sidx = 1;
 
//2. Selecting the count FIRST
//@todo rework this
switch ($action) {
    case 'get_gradebooks':
        require_once $libpath.'gradebook.lib.php';
        $obj        = new Gradebook();
        $count      = $obj->get_count();
        break;
    case 'get_careers':        
        require_once $libpath.'career.lib.php';
        $obj        = new Career();
        $count      = $obj->get_count();
        break;
    case 'get_promotions':
       require_once $libpath.'promotion.lib.php';        
        $obj        = new Promotion();        
        $count      = $obj->get_count();   
        break;
    case 'get_usergroups':
        require_once $libpath.'usergroup.lib.php';        
        $obj        = new UserGroup();        
        $count      = $obj->get_count();   
        break;
    default:
        exit;   
}

//3. Calculating first, end, etc
        
$total_pages = 0;
if ($count > 0) { 
    if (!empty($limit)) {
        $total_pages = ceil($count/$limit);
    }
}
if ($page > $total_pages) { 
    $page = $total_pages;
}     

$start = $limit * $page - $limit;
if ($start < 0 ) {
	$start = 0;
}
 

//4. Deleting an element if the user wants to
if ($_REQUEST['oper'] == 'del') {
    $obj->delete($_REQUEST['id']);
}

//4. Querying the DB for the elements
$columns = array();
switch ($action) {
    case 'get_gradebooks': 
        $columns = array('name', 'skills', 'actions');                
        if (!in_array($sidx, $columns)) {
            $sidx = 'name';
        }
        $result     = Database::select('*', $obj->table, array('order'=>"$sidx $sord", 'LIMIT'=> "$start , $limit"));
        $new_result = array();
        foreach($result as $item) {
            if ($item['parent_id'] != 0) {
                continue;
            }
            $skills = $obj->get_skills_by_gradebook($item['id']);
            //Fixes bug when gradebook doesn't have names
            if (empty($item['name'])) {
                $item['name'] = $item['course_code'];                 
            }
            if (!empty($item['certif_min_score']) && !empty($item['document_id'])) {
                $item['name'] .= '* (with_certificate)'; 
            } else {
                $item['name'] .= ' (No certificate)';
            }
            $skills_string = '';
            if (!empty($skills)) {
                foreach($skills as $skill) {
                    $item['skills'] .= Display::span($skill['name'], array('class' => 'label_tag skill'));  
                }
            }          
            $new_result[] = $item;
        } 
        $result = $new_result;
        break;
    case 'get_careers': 
        $columns = array('name', 'description', 'actions');                
        if(!in_array($sidx, $columns)) {
        	$sidx = 'name';
        }
        $result     = Database::select('*', $obj->table, array('order'=>"$sidx $sord", 'LIMIT'=> "$start , $limit"));
        $new_result = array();
        foreach($result as $item) {
            if (!$item['status']) {
                $item['name'] = '<font style="color:#AAA">'.$item['name'].'</font>';
            }
            $new_result[] = $item;
        } 
        $result = $new_result;        
        break;
    case 'get_promotions':        
        $columns = array('name', 'career', 'description', 'actions');
        if(!in_array($sidx, $columns)) {
            $sidx = 'name';
        }                  
        $result     = Database::select('p.id,p.name, p.description, c.name as career, p.status', "$obj->table p LEFT JOIN ".Database::get_main_table(TABLE_CAREER)." c  ON c.id = p.career_id ", array('order' =>"$sidx $sord", 'LIMIT'=> "$start , $limit"));
        $new_result = array();
        foreach($result as $item) {
            if (!$item['status']) {
                $item['name'] = '<font style="color:#AAA">'.$item['name'].'</font>';
            }
            $new_result[] = $item;
        } 
        $result = $new_result;      
        
        break;
    case 'get_usergroups':
        $columns = array('name', 'users', 'courses','sessions','actions');
        $result     = Database::select('*', $obj->table, array('order'=>"name $sord", 'LIMIT'=> "$start , $limit"));
        $new_result = array();
        if (!empty($result)) {
            foreach ($result as $group) {            
                $group['sessions']   = count($obj->get_sessions_by_usergroup($group['id']));
                $group['courses']    = count($obj->get_courses_by_usergroup($group['id']));
                $group['users']      = count($obj->get_users_by_usergroup($group['id']));
                $new_result[]        = $group;
            }
            $result = $new_result;
        }        
        $columns = array('name', 'users', 'courses','sessions');                
        if(!in_array($sidx, $columns)) {
            $sidx = 'name';
        }
        //Multidimensional sort
        msort($result, $sidx);
        break;      
    default:    
        exit;            
}
//var_dump($result);

//5. Creating an obj to return a json
if (in_array($action, array('get_careers','get_promotions','get_usergroups','get_gradebooks'))) { 
    $response = new stdClass();           
    $response->page     = $page; 
    $response->total    = $total_pages; 
    $response->records  = $count; 
    $i=0;
    if (!empty($result)) {
        foreach($result as $row) {
             //print_r($row);
             $response->rows[$i]['id']=$row['id'];
             $array = array();
             foreach($columns as $col) {
             	$array[] = $row[$col];
             }                   
             $response->rows[$i]['cell']=$array;
             $i++; 
        }
    } 
    echo json_encode($response);       
}
exit;