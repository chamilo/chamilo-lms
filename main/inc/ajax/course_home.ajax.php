<?php
/* For licensing terms, see /license.txt */
// @todo refactor this script, create a class that manage the jqgrid requests
/**
 * Responses to AJAX calls
*/
$action = $_GET['a'];
$now    = time();

switch ($action) {
	case 'set_visibility':
		require_once '../global.inc.php';
		if (api_is_allowed_to_edit(null,true)) {
			$tool_table = Database::get_course_table(TABLE_TOOL_LIST);
			$tool_id = Security::remove_XSS($_GET["id"]);
			$tool_info = api_get_tool_information($tool_id);
			$tool_visibility   = $tool_info['visibility'];
			$tool_image        = $tool_info['image'];
			if (api_get_setting('homepage_view') != 'activity_big') {			
                $new_image         = api_get_path(WEB_IMG_PATH).str_replace('.gif','_na.gif',$tool_image);
                $tool_image        = api_get_path(WEB_IMG_PATH).$tool_image;    
			} else {
			    $tool_image        = $tool_info['image'];
			    $tool_image        = (substr($tool_info['image'], 0, strpos($tool_info['image'], '.'))).'.png';
                $new_image         = str_replace('.png','_na.png',$tool_image);  
                $new_image         = api_get_path(WEB_IMG_PATH).'icons/64/'.$new_image;
                $tool_image        = api_get_path(WEB_IMG_PATH).'icons/64/'.$tool_image;            
			}
			
			$requested_image   = ($tool_visibility == 0 ) ? $tool_image : $new_image;
			$requested_clase   = ($tool_visibility == 0 ) ? 'visible' : 'invisible';
			$requested_message = ($tool_visibility == 0 ) ? 'is_active' : 'is_inactive';
		    $requested_view    = ($tool_visibility == 0 ) ? 'visible.gif' : 'invisible.gif';
		    $requested_visible = ($tool_visibility == 0 ) ? 1 : 0;

	    	$requested_view    = ($tool_visibility == 0 ) ? 'visible.gif' : 'invisible.gif';
	    	$requested_visible = ($tool_visibility == 0 ) ? 1 : 0;
			//HIDE AND REACTIVATE TOOL
			if ($_GET["id"]==strval(intval($_GET["id"]))) {

				/* -- session condition for visibility
				 if (!empty($session_id)) {
					$sql = "select session_id FROM $tool_table WHERE id='".$_GET["id"]."' AND session_id = '$session_id'";
					$rs = Database::query($sql);
					if (Database::num_rows($rs) > 0) {
			 			$sql="UPDATE $tool_table SET visibility=$requested_visible WHERE id='".$_GET["id"]."' AND session_id = '$session_id'";
					} else {
						$sql_select = "select * FROM $tool_table WHERE id='".$_GET["id"]."'";
						$res_select = Database::query($sql_select);
						$row_select = Database::fetch_array($res_select);
						$sql = "INSERT INTO $tool_table(name,link,image,visibility,admin,address,added_tool,target,category,session_id)
								VALUES('{$row_select['name']}','{$row_select['link']}','{$row_select['image']}','0','{$row_select['admin']}','{$row_select['address']}','{$row_select['added_tool']}','{$row_select['target']}','{$row_select['category']}','$session_id')";
					}
				} else $sql="UPDATE $tool_table SET visibility=$requested_visible WHERE id='".$_GET["id"]."'";
				*/

				$sql="UPDATE $tool_table SET visibility=$requested_visible WHERE id='".intval($_GET['id'])."'";
				Database::query($sql);
			}	
			$response_data = array(
				'image'   => $requested_image,
				'tclass'  => $requested_clase,
				'message' => $requested_message,
	      		'view'    => $requested_view
			);
			echo json_encode($response_data);
        }
        break;
	
	case 'show_course_information' :
	
		$language_file = array('course_description');
		require_once '../global.inc.php';			
        if (api_is_anonymous()) {
            exit;
        }
		require_once api_get_path(INCLUDE_PATH).'reduced_header.inc.php' ;
		
		// Get the name of the database course.
		$database_course        = CourseManager::get_name_database_course($_GET['code']);
		$tbl_course_description = Database::get_course_table(TABLE_COURSE_DESCRIPTION, $database_course);		
		
		$course_info = api_get_course_info($_GET['code']);
		
		echo Display::tag('h2', $course_info['name']);
		echo '<br />';
	
		$sql = "SELECT * FROM $tbl_course_description WHERE session_id = 0 ORDER BY id";
		$result = Database::query($sql);
		if (Database::num_rows($result) > 0 ) {		        
		    while ($description = Database::fetch_object($result)) {
			    $descriptions[$description->id] = $description;
		    }		    
            // Function that displays the details of the course description in html.
		    echo CourseManager::get_details_course_description_html($descriptions, api_get_system_encoding(), false);
		} else {
		    echo get_lang('NoDescription');
		}		
	    break;    
    /** 
     * @todo this functions need to belong to a class or a special wrapper to process the AJAX petitions from the jqgrid
     */
    case 'session_courses_lp_default':        
        
        require_once '../global.inc.php';
        
        $libpath = api_get_path(LIBRARY_PATH);
        require_once $libpath.'course.lib.php';
        require_once $libpath.'sessionmanager.lib.php';
        require_once $libpath.'usermanager.lib.php';
        require_once $libpath.'tracking.lib.php';                      
        require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpathList.class.php';
        
        
        $page  = intval($_REQUEST['page']);     //page
        $limit = intval($_REQUEST['rows']);     // quantity of rows
        $sidx  = $_REQUEST['sidx'];    //index to filter         
        $sord  = $_REQUEST['sord'];    //asc or desc
        if (!in_array($sord, array('asc','desc'))) {
        	$sord = 'desc';
        }        
        $session_id  = intval($_REQUEST['session_id']);
        $course_id   = intval($_REQUEST['course_id']);
        
        //Filter users that does not belong to the session
        if (!api_is_platform_admin()) {
            $new_session_list = UserManager::get_personal_session_course_list(api_get_user_id());
            $my_session_list  = array();
            foreach($new_session_list as $item) {
                if (!empty($item['id_session'])) 
                    $my_session_list[] = $item['id_session'];
            }        
            if (!in_array($session_id, $my_session_list)) {
            	break;
            }
        }        
                        
        if(!$sidx) $sidx = 1;
        
        $start = $limit*$page - $limit;         
        $course_list    = SessionManager::get_course_list_by_session_id($session_id);        
        $count = 0;
        
        foreach ($course_list as $item) {
            if (isset($course_id) && !empty($course_id)) {
                if ($course_id != $item['id']) {
                    continue;
                }
            }
        
            $list               = new LearnpathList(api_get_user_id(),$item['code'], $session_id);
            $flat_list          = $list->get_flat_list(); 
            $lps[$item['code']] = $flat_list;
            $course_url         = api_get_path(WEB_COURSE_PATH).$item['directory'].'/?id_session='.$session_id;
            $item['title']      = Display::url($item['title'], $course_url, array('target'=>SESSION_LINK_TARGET));
            foreach($flat_list as $lp_id => $lp_item) {                                                    
                $temp[$count]['id']= $lp_id;                
                $lp_url = api_get_path(WEB_CODE_PATH).'newscorm/lp_controller.php?cidReq='.$item['code'].'&id_session='.$session_id.'&lp_id='.$lp_id.'&action=view';
                $last_date = Tracking::get_last_connection_date_on_the_course(api_get_user_id(),$item['code'], $session_id, false);
                if ($lp_item['modified_on'] == '0000-00-00 00:00:00' || empty($lp_item['modified_on'])) {
                    $lp_date = api_get_local_time($lp_item['created_on'], null, date_default_timezone_get());
                    $image = 'new.gif';
                    $label      = get_lang('LearnpathAdded');
                } else {
                    $lp_date    = api_get_local_time($lp_item['modified_on'], null, date_default_timezone_get());
                    $image      = 'moderator_star.png';
                    $label      = get_lang('LearnpathUpdated');
                }
                if (strtotime($last_date) < strtotime($lp_date)) {
                    $icons = ' '.Display::return_icon($image, get_lang('_title_notification').': '.$label.' - '.$lp_date);                    
                }
                
                if (!empty($lp_item['publicated_on'])) {
                    $date = substr($lp_item['publicated_on'], 0, 10);
                } else {
                    $date = '-';
                }
                
                //Checking LP publicated and expired_on dates
                if (!empty($lp_item['publicated_on']) && $lp_item['publicated_on'] != '0000-00-00 00:00:00') {                        
                    if ($now < api_strtotime($lp_item['publicated_on'])) {                        
                        continue;
                    }
                }
                
                if (!empty($lp_item['expired_on']) && $lp_item['expired_on'] != '0000-00-00 00:00:00') {
                    if ($now > api_strtotime($lp_item['expired_on'])) {
                        continue;
                    }
                }
                
                $temp[$count]['cell']=array($date, $item['title'], Display::url($lp_item['lp_name'].$icons, $lp_url, array('target'=>SESSION_LINK_TARGET)));
                $count++;     
            }              
        } 
        
        $i =0;
        $response = new stdClass(); 
        foreach($temp as $key=>$row) {
            $row = $row['cell'];
            if (!empty($row)) {            
                if ($key >= $start  && $key < ($start + $limit)) {                 
                    $response->rows[$i]['id']= $key;
                    $response->rows[$i]['cell']=array($row[0], $row[1], $row[2]);
                    $i++;                
                }
            }
        }        
        
        if($count > 0 && $limit > 0) { 
            $total_pages = ceil($count/$limit); 
        } else { 
            $total_pages = 0; 
        }         
        $response->total    = $total_pages;         
        if ($page > $total_pages) { 
            $response->page= $total_pages;
        } else {
            $response->page = $page;	
        }        
        $response->records = $count;    
        echo json_encode($response);        
        break;
        
    case 'session_courses_lp_by_week':
        
        require_once '../global.inc.php';
    
        $libpath = api_get_path(LIBRARY_PATH);
        require_once $libpath.'course.lib.php';
        require_once $libpath.'usermanager.lib.php';
        require_once $libpath.'tracking.lib.php';   
        require_once $libpath.'sessionmanager.lib.php';        
        require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpathList.class.php';        
        
        $page  = intval($_REQUEST['page']);     //page
        $limit = intval($_REQUEST['rows']);     // quantity of rows
        $sidx  = intval($_REQUEST['sidx']);    //index to filter         
        $sord  = $_REQUEST['sord'];    //asc or desc
        if (!in_array($sord, array('asc','desc'))) {
            $sord = 'desc';
        }
                
        $session_id  = intval($_REQUEST['session_id']);
        $course_id   = intval($_REQUEST['course_id']);
        
        //Filter users that does not belong to the session
        if (!api_is_platform_admin()) {
            $new_session_list = UserManager::get_personal_session_course_list(api_get_user_id());
            $my_session_list  = array();
            foreach($new_session_list as $item) {
                if (!empty($item['id_session'])) 
                    $my_session_list[] = $item['id_session'];
            }        
            if (!in_array($session_id, $my_session_list)) {
                break;
            }
        }       
               
                
        if(!$sidx) $sidx =1;
        
        $start = $limit*$page - $limit; 
        
        $course_list    = SessionManager::get_course_list_by_session_id($session_id);
                        
        $count = 0;
        $temp = array();
        foreach ($course_list as $item) {    
            if (isset($course_id) && !empty($course_id)) {
                if ($course_id != $item['id']) {
                    continue;
                }
            }
            
            $list               = new LearnpathList(api_get_user_id(),$item['code'], $session_id, 'publicated_on DESC');
            $flat_list          = $list->get_flat_list();    
            $lps[$item['code']] = $flat_list;
            $item['title'] = Display::url($item['title'],api_get_path(WEB_COURSE_PATH).$item['directory'].'/?id_session='.$session_id,array('target'=>SESSION_LINK_TARGET));
            
            foreach($flat_list as $lp_id => $lp_item) {                                                    
                $temp[$count]['id']= $lp_id;
                $lp_url = api_get_path(WEB_CODE_PATH).'newscorm/lp_controller.php?cidReq='.$item['code'].'&id_session='.$session_id.'&lp_id='.$lp_id.'&action=view';
                
                $last_date = Tracking::get_last_connection_date_on_the_course(api_get_user_id(),$item['code'], $session_id, false);
                if ($lp_item['modified_on'] == '0000-00-00 00:00:00' || empty($lp_item['modified_on'])) {
                    $lp_date = api_get_local_time($lp_item['created_on'], null, date_default_timezone_get());
                    $image = 'new.gif';
                    $label      = get_lang('LearnpathAdded');
                } else {
                    $lp_date    = api_get_local_time($lp_item['modified_on'], null, date_default_timezone_get());
                    $image      = 'moderator_star.png';
                    $label      = get_lang('LearnpathUpdated');
                }
                if (strtotime($last_date) < strtotime($lp_date)) {
                    $icons = ' '.Display::return_icon($image, get_lang('_title_notification').': '.$label.' - '.$lp_date);                    
                }
                
                if (!empty($lp_item['publicated_on'])) {
                    $date = substr($lp_item['publicated_on'], 0, 10);
                } else {
                    $date = '-';
                }
                
                 //Checking LP publicated and expired_on dates
                
                if (!empty($lp_item['publicated_on']) && $lp_item['publicated_on'] != '0000-00-00 00:00:00') {
                    $week_data = date('Y', api_strtotime($lp_item['publicated_on'])).' - '.get_week_from_day($lp_item['publicated_on']);                   
                    if ($now < api_strtotime($lp_item['publicated_on'])) {                        
                        continue;
                    }
                } else {
                    $week_data = '';
                }
                
                if (!empty($lp_item['expired_on']) && $lp_item['expired_on'] != '0000-00-00 00:00:00') {
                    if ($now > api_strtotime($lp_item['expired_on'])) {
                        continue;
                    }
                }
            
                $temp[$count]['cell'] = array($week_data, $date, $item['title'], Display::url($lp_item['lp_name'].$icons, $lp_url, array('target'=>SESSION_LINK_TARGET)));
                $count++;     
            }              
        }     
           
        $response = new stdClass(); 
        $i =0;        
        foreach($temp as $key=>$row) {   
            $row = $row['cell'];     
            if (!empty($row)) {    
                if ($key >= $start  && $key < ($start + $limit)) {                 
                    $response->rows[$i]['id']= $key;
                    $response->rows[$i]['cell']=array($row[0], $row[1], $row[2],$row[3]);
                    $i++;                
                }
            }
        }
        
        if($count > 0 && $limit > 0) { 
            $total_pages = ceil($count/$limit); 
        } else { 
            $total_pages = 0; 
        }         
        $response->total    = $total_pages;         
        if ($page > $total_pages) { 
            $response->page = $total_pages;
        } else {
            $response->page = $page;    
        }        
        $response->records = $count;    
        echo json_encode($response); 
        break;
        

    case 'session_courses_lp_by_course':
        
        require_once '../global.inc.php';
    
        $libpath = api_get_path(LIBRARY_PATH);
        require_once $libpath.'course.lib.php';
        require_once $libpath.'usermanager.lib.php';
        require_once $libpath.'tracking.lib.php';   
        require_once $libpath.'sessionmanager.lib.php';        
        require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpathList.class.php';        
        
        $page  = intval($_REQUEST['page']);     //page
        $limit = intval($_REQUEST['rows']);     // quantity of rows
        $sidx  = intval($_REQUEST['sidx']);    //index to filter         
        $sord  = $_REQUEST['sord'];    //asc or desc
        if (!in_array($sord, array('asc','desc'))) {
            $sord = 'desc';
        }        
        $session_id  = intval($_REQUEST['session_id']);
        $course_id   = intval($_REQUEST['course_id']);
        
        //Filter users that does not belong to the session
        if (!api_is_platform_admin()) {
            $new_session_list = UserManager::get_personal_session_course_list(api_get_user_id());
            $my_session_list  = array();
            foreach($new_session_list as $item) {
                if (!empty($item['id_session'])) 
                    $my_session_list[] = $item['id_session'];
            }        
            if (!in_array($session_id, $my_session_list)) {
                break;
            }
        }
                
        if(!$sidx) $sidx =1;
        
        $start = $limit*$page - $limit; 
        
        $course_list = SessionManager::get_course_list_by_session_id($session_id);
                        
        $count = 0;
        
        foreach ($course_list as $item) {   
            if (isset($course_id) && !empty($course_id)) {
                if ($course_id != $item['id']) {
                    continue;
                }
            }
             
            $list               = new LearnpathList(api_get_user_id(),$item['code'],$session_id);
            $flat_list          = $list->get_flat_list();    
            $lps[$item['code']] = $flat_list;
            $item['title']      = Display::url($item['title'],api_get_path(WEB_COURSE_PATH).$item['directory'].'/?id_session='.$session_id, array('target'=>SESSION_LINK_TARGET));
            foreach($flat_list as $lp_id => $lp_item) {                                                    
                $temp[$count]['id']= $lp_id;
                $lp_url = api_get_path(WEB_CODE_PATH).'newscorm/lp_controller.php?cidReq='.$item['code'].'&id_session='.$session_id.'&lp_id='.$lp_id.'&action=view';
                $last_date = Tracking::get_last_connection_date_on_the_course(api_get_user_id(),$item['code'], $session_id, false);
                if ($lp_item['modified_on'] == '0000-00-00 00:00:00' || empty($lp_item['modified_on'])) {
                    $lp_date = api_get_local_time($lp_item['created_on'], null, date_default_timezone_get());
                    $image = 'new.gif';
                    $label      = get_lang('LearnpathAdded');
                } else {
                    $lp_date    = api_get_local_time($lp_item['modified_on'], null, date_default_timezone_get());
                    $image      = 'moderator_star.png';
                    $label      = get_lang('LearnpathUpdated');
                }
                if (strtotime($last_date) < strtotime($lp_date)) {
                    $icons = ' '.Display::return_icon($image, get_lang('_title_notification').': '.$label.' - '.$lp_date);                    
                }
                if (!empty($lp_item['publicated_on'])) {
                    $date = substr($lp_item['publicated_on'], 0, 10);
                } else {
                    $date = '-';
                }
                
                 //Checking LP publicated and expired_on dates
                if (!empty($lp_item['publicated_on']) && $lp_item['publicated_on'] != '0000-00-00 00:00:00') {                        
                    if ($now < api_strtotime($lp_item['publicated_on'])) {                        
                        continue;
                    }
                }                
                if (!empty($lp_item['expired_on']) && $lp_item['expired_on'] != '0000-00-00 00:00:00') {
                    if ($now > api_strtotime($lp_item['expired_on'])) {
                        continue;
                    }
                }                
                $temp[$count]['cell']=array($date, $item['title'], Display::url($lp_item['lp_name'].$icons, $lp_url, array('target'=>SESSION_LINK_TARGET)));
                $count++;     
            }              
        } 
        $response = new stdClass(); 
        $i =0;
        foreach($temp as $key=>$row) {   
            $row = $row['cell'];   
            if (!empty($row)) {           
                if ($key >= $start  && $key < ($start + $limit)) {                 
                    $response->rows[$i]['id']= $key;
                    $response->rows[$i]['cell']=array($row[0], $row[1], $row[2],$row[3]);
                    $i++;                
                }
            }
        }
        
        if($count > 0 && $limit > 0) { 
            $total_pages = ceil($count/$limit); 
        } else { 
            $total_pages = 0; 
        }         
        $response->total    = $total_pages;         
        if ($page > $total_pages) { 
            $response->page= $total_pages;
        } else {
            $response->page = $page;    
        }        
        $response->records = $count; 
        
        echo json_encode($response); 
        break;
	default:
		echo '';
}
exit;