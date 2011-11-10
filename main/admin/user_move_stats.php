<?php

$language_file = 'admin';
$cidReset = true;
require_once '../inc/global.inc.php';
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once '../newscorm/learnpathList.class.php';

$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
$debug = 0;

function compare_data($result_message) {
  foreach ($result_message as $table=>$data) {
        $title = $table;                                
        if ($table == 'TRACK_E_EXERCISES') {
            $title = get_lang('Exercises');
        } elseif ($table == 'TRACK_E_EXERCISES_IN_LP') {
            $title = get_lang('ExercisesInLp');
          } elseif ($table == 'LP_VIEW') {
            $title = get_lang('LearningPath');
        }                                
        echo '<br / ><h2>'.get_lang($title).' </h2><hr />';
        
        if (is_array($data)) {    
            foreach ($data as $id => $item) {
                
                if ($table == 'TRACK_E_EXERCISES' || $table == 'TRACK_E_EXERCISES_IN_LP' ) {
                    echo "<br /><h3>".get_lang('Attempt')." #$id</h3>";                                            
                    echo '<h3>';
                    echo get_lang('Exercise').' #'.$item['exe_exo_id'];
                    echo '</h3>';                                            
                    if (!empty($item['orig_lp_id'])) {
                        echo '<h3>';
                        echo get_lang('LearningPath').' #'.$item['orig_lp_id'];
                        echo '</h3>';
                    }                                            
                    //Process data
                    $array = array('exe_date' =>get_lang('Date'), 'exe_result' =>get_lang('Score'),'exe_weighting'=>get_lang('Weighting'));
                    foreach($item as $key=> $value) {
                        if (in_array($key,array_keys($array))) {
                            $key = $array[$key];
                            echo "$key =  $value <br />";
                        }   
                    }
                } else  {
                    echo "<br /><h3>".get_lang('Id')." #$id</h3>";                                            
                           //process data
                    foreach($item as $key=> $value) {
                        echo "$key =  $value <br />";   
                    }
                }                                     
            }                                    
        } else {
            echo get_lang('NoResults');
        }                           
    }
}
if (isset($_REQUEST['load_ajax'])) {
    //Checking the variable $_SESSION['combination'] that has all the information of the selected course (instead of using a lots of hidden variables ... )
    if (isset($_SESSION['combination']) && !empty($_SESSION['combination'])) {
    	$combinations = $_SESSION['combination'];        
        $combination_result = $combinations[$_REQUEST['unique_id']];
        if (empty($combination_result)) {
        	echo get_lang('ThereWasAnError');
        } else {
            $origin_course_code     = $combination_result['course_code'];            
            $origin_session_id      = intval($combination_result['session_id']);            
            $new_session_id         = intval($_REQUEST['session_id']);
            
            //if (!isset($_REQUEST['view_stat'])) {
                if ($origin_session_id == $new_session_id ) {
                    echo get_lang('CantMoveToTheSameSession');
                	exit;
                }
            //}
            $user_id                = intval($_REQUEST['user_id']);
     
            $new_course_list = SessionManager::get_course_list_by_session_id($new_session_id);
           
            $result_message         = array();            
            $result_message_compare = array();
            
            $view_stat = false;
            $update_database = true;
            if (isset($_REQUEST['view_stat']) && $_REQUEST['view_stat'] == 1 ) {                
                $update_database = false;
            }
           
            //Check if the same course exist in the session destination 
            if (isset($new_course_list[$origin_course_code])) {
              
                //Check if the user is registered in the session otherwise we will add it                
                $result = SessionManager::get_users_by_session($new_session_id);
                if (empty($result) || !in_array($user_id, array_keys($result))) {
                    if ($debug) echo 'User added to the session'; 
                    //Registering user to the new session
                    SessionManager::suscribe_users_to_session($new_session_id,array($user_id),false);                    
                }            
                           
                //Begin with the import process
                $course_info                = api_get_course_info($origin_course_code);
                $course_id 					= $course_info['real_id'];
                
                $TABLETRACK_EXERCICES       = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
                $TBL_TRACK_ATTEMPT          = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
                $TBL_TRACK_E_COURSE_ACCESS  = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
                $TBL_TRACK_E_LAST_ACCESS    = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
                                
                $TBL_LP_VIEW                = Database::get_course_table(TABLE_LP_VIEW);
                $TBL_NOTEBOOK               = Database::get_course_table(TABLE_NOTEBOOK);
                $TBL_STUDENT_PUBLICATION    = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
                $TBL_STUDENT_PUBLICATION_ASSIGNMENT    = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
                $TBL_ITEM_PROPERTY          = Database::get_course_table(TABLE_ITEM_PROPERTY);
                
                $TBL_DROPBOX_FILE           = Database::get_course_table(TABLE_DROPBOX_FILE);
                $TBL_DROPBOX_POST           = Database::get_course_table(TABLE_DROPBOX_POST);
                $TBL_AGENDA                 = Database::get_course_table(TABLE_AGENDA);
                              
                $course_code = Database::escape_string($course_code);
                
                //1. track_e_exercises
                
                //ORIGINAL COURSE
                
                $sql = "SELECT * FROM $TABLETRACK_EXERCICES WHERE exe_cours_id = '$origin_course_code' AND  session_id = $origin_session_id AND exe_user_id = $user_id ";                
                $res = Database::query($sql);
                $list = array();    
                while($row = Database::fetch_array($res,'ASSOC')) {
                    $list[$row['exe_id']]= $row;
                }   
               
                if (!empty($list))
                foreach ($list as $exe_id =>$data) {
                    if ($update_database) {
                        $sql = "UPDATE $TABLETRACK_EXERCICES SET session_id = '$new_session_id' WHERE exe_id = $exe_id";
                        $res = Database::query($sql);   
                        $result_message[$TABLETRACK_EXERCICES]++;
                    } else {
                        if(!empty($data['orig_lp_id']) &&  !empty($data['orig_lp_item_id'])) {
                    	   $result_message['TRACK_E_EXERCISES'][$exe_id] = $data;
                        } else {                              
                           $result_message['TRACK_E_EXERCISES_IN_LP'][$exe_id] = $data;
                        }
                    }                                    	
                } 
                
                //DESTINY COURSE
                
                if (!$update_database) {
                
                    $sql = "SELECT * FROM $TABLETRACK_EXERCICES WHERE exe_cours_id = '$origin_course_code' AND  session_id = $new_session_id AND exe_user_id = $user_id ";                
                    $res = Database::query($sql);
                    $list = array();    
                    while($row = Database::fetch_array($res,'ASSOC')) {
                        $list[$row['exe_id']]= $row;
                    }   
                   
                    if (!empty($list))
                    foreach ($list as $exe_id =>$data) {
                        if ($update_database) {
                            $sql = "UPDATE $TABLETRACK_EXERCICES SET session_id = '$new_session_id' WHERE exe_id = $exe_id";
                            $res = Database::query($sql);   
                            $result_message[$TABLETRACK_EXERCICES]++;
                        } else {
                            if(!empty($data['orig_lp_id']) &&  !empty($data['orig_lp_item_id'])) {
                               $result_message_compare['TRACK_E_EXERCISES'][$exe_id] = $data;
                            } else {                              
                               $result_message_compare['TRACK_E_EXERCISES_IN_LP'][$exe_id] = $data;
                            }
                        }                                       
                    }                
                }
                
                
                //2.track_e_attempt, track_e_attempt_recording, track_e_downloads
                //Nothing to do because there are not relationship with a session
                
                //3. track_e_course_access  
                              
                $sql = "SELECT * FROM $TBL_TRACK_E_COURSE_ACCESS WHERE course_code = '$origin_course_code' AND session_id = $origin_session_id  AND user_id = $user_id ";                
                $res = Database::query($sql);
                $list = array();    
                while($row = Database::fetch_array($res,'ASSOC')) {
                    $list[$row['course_access_id']] = $row;
                }   
          
                if (!empty($list))
                foreach ($list as $id => $data) {
                    if ($update_database) {
                        $sql = "UPDATE $TBL_TRACK_E_COURSE_ACCESS SET session_id = '$new_session_id' WHERE course_access_id = $id";
                        if ($debug) echo $sql;  
                        $res = Database::query($sql);  
                        if ($debug) var_dump($res);  
                        $result_message[$TBL_TRACK_E_COURSE_ACCESS]++;
                    } else {
                    	//$result_message[$TBL_TRACK_E_COURSE_ACCESS][$id] = $data;
                    }          
                }
                
                //4. track_e_lastaccess
                
                $sql = "SELECT access_id FROM $TBL_TRACK_E_LAST_ACCESS WHERE access_cours_code = '$origin_course_code' AND access_session_id = $origin_session_id  AND access_user_id = $user_id ";                
                $res = Database::query($sql);
                $list = array();    
                while($row = Database::fetch_array($res,'ASSOC')) {
                    $list[] = $row['access_id'];
                }  
                if (!empty($list))
                foreach ($list as $id) {
                    if ($update_database) {
                        $sql = "UPDATE $TBL_TRACK_E_LAST_ACCESS SET access_session_id = '$new_session_id' WHERE access_id = $id";
                        if ($debug) echo $sql; 
                        $res = Database::query($sql);
                        if ($debug) var_dump($res); 
                        $result_message[$TBL_TRACK_E_LAST_ACCESS]++;
                    }  
                }                
                
                //5. lp_item_view           
                 
               //CHECK ORIGIN
               
                $sql = "SELECT * FROM $TBL_LP_VIEW WHERE user_id = $user_id AND session_id = $origin_session_id AND c_id = $course_id ";
                $res = Database::query($sql);
                
                //Getting the list of LPs in the new session 
                $lp_list = new LearnpathList($user_id, $origin_course_code, $new_session_id);
                $flat_list = $lp_list->get_flat_list();
                
                $list = array();             
                while($row = Database::fetch_array($res,'ASSOC')) {
                    //Checking if the LP exist in the new session                    
                    if (in_array($row['lp_id'], array_keys($flat_list))) {                    
                        $list[$row['id']] = $row;
                    }                  
                }
                            
                if (!empty($list))
                foreach ($list as $id=>$data) {
                    if ($update_database) {
                        $sql = "UPDATE $TBL_LP_VIEW SET session_id = '$new_session_id' WHERE c_id = $course_id AND id = $id ";
                        if ($debug) var_dump($sql); 
                        $res = Database::query($sql);          
                        if ($debug) var_dump($res);   
                          $result_message[$TBL_LP_VIEW]++;
                    } else {
                          //Getting all information of that lp_item_id
                          $score    = Tracking::get_avg_student_score($user_id, $origin_course_code, array($data['lp_id']),$origin_session_id);
                          $progress = Tracking::get_avg_student_progress($user_id, $origin_course_code, array($data['lp_id']),$origin_session_id);                         
                    	  $result_message['LP_VIEW'][$data['lp_id']] = array('score' => $score, 'progress' =>$progress);                          
                    }        
                }
                
                
                //CHECk DESTINY
                if (!$update_database) {
                         $sql = "SELECT * FROM $TBL_LP_VIEW WHERE user_id = $user_id AND session_id = $new_session_id AND c_id = $course_id";
                        $res = Database::query($sql);
                        
                        //Getting the list of LPs in the new session 
                        $lp_list = new LearnpathList($user_id, $origin_course_code, $new_session_id);
                        $flat_list = $lp_list->get_flat_list();
                        
                        $list = array();             
                        while($row = Database::fetch_array($res,'ASSOC')) {
                            //Checking if the LP exist in the new session                    
                            if (in_array($row['lp_id'], array_keys($flat_list))) {                    
                                $list[$row['id']] = $row;
                            }                  
                        }                                    
                        if (!empty($list))
                        foreach ($list as $id=>$data) {                    
                              //Getting all information of that lp_item_id
                              $score    = Tracking::get_avg_student_score($user_id, $origin_course_code,        array($data['lp_id']),$new_session_id);
                              $progress = Tracking::get_avg_student_progress($user_id, $origin_course_code,     array($data['lp_id']),  $new_session_id);                         
                              $result_message_compare['LP_VIEW'][$data['lp_id']] = array('score' => $score, 'progress' =>$progress);                          
                                  
                        }
                }
                
                
                //6. Agenda
                
                //calendar_event_attachment no problems no session_id
                $sql = "SELECT ref FROM $TBL_ITEM_PROPERTY WHERE tool = 'calendar_event' AND insert_user_id = $user_id AND c_id = $course_id ";
                $res = Database::query($sql);
                while($row = Database::fetch_array($res,'ASSOC')) {
                     $id = $row['ref'];
                     if ($update_database) {                    
                        $sql = "UPDATE $TBL_AGENDA SET session_id = '$new_session_id' WHERE id = $id ";
                        if ($debug) var_dump($sql); 
                        $res_update = Database::query($sql);
                        if ($debug) var_dump($res_update);    
                        $result_message['agenda']++;
                    }                   
                }                                
                
                //7. Forum ?? So much problems when trying to import data
                
                //8. Student publication - Works
                
                //echo '<h1>Student publication</h1>';
                
                $sql = "SELECT ref FROM $TBL_ITEM_PROPERTY WHERE tool = 'work' AND insert_user_id = $user_id AND c_id = $course_id";
                if ($debug) echo $sql;
                $res = Database::query($sql);
                while($row = Database::fetch_array($res,'ASSOC')) {
                    $id = $row['ref'];
                    $sql = "SELECT * FROM $TBL_STUDENT_PUBLICATION WHERE id = $id AND session_id = $origin_session_id AND c_id = $course_id";
                    if ($debug) var_dump($sql); 
                    $sub_res = Database::query($sql);
                    if (Database::num_rows($sub_res) > 0 ) {
                        $data = Database::fetch_array($sub_res,'ASSOC');     
                        if ($debug) var_dump($data);                  
                        $parent_id = $data['parent_id'];
                        if (isset($data['parent_id']) && !empty($data['parent_id'])) {
                            $sql = "SELECT * FROM $TBL_STUDENT_PUBLICATION WHERE id = $parent_id AND c_id = $course_id";
                            $select_res     = Database::query($sql);
                            $parent_data    = Database::fetch_array($select_res,'ASSOC');
                            if ($debug)     var_dump($parent_data);
                            
                            $sys_course_path = api_get_path(SYS_COURSE_PATH);
                            $course_dir = $sys_course_path . $course_info['path'];
                            $base_work_dir = $course_dir . '/work';
                            require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';                            
                           
                            //Creating the parent folder in the session if does not exists already                            
                                
                            //@todo ugly fix
                            $search_this = "folder_moved_from_session_id_$origin_session_id";
                            $search_this2 = $parent_data['url'];
                            $sql = "SELECT * FROM $TBL_STUDENT_PUBLICATION 
                            		WHERE description like '%$search_this%' AND url LIKE '%$search_this2%' AND session_id = $new_session_id AND c_id = $course_id 
                            		ORDER BY id desc  LIMIT 1";
                            if ($debug) echo $sql;
                            $sub_res = Database::query($sql);
                            $num_rows = Database::num_rows($sub_res);
                   
                            if ($num_rows > 0 ) {                     
                                $new_result = Database::fetch_array($sub_res,'ASSOC');
                                $created_dir    = $new_result['url'];
                                $new_parent_id  = $new_result['id'];                                
                            } else {                            
                                
                                if ($update_database) {
                                    
	                                $dir_name = substr($parent_data['url'], 1);
	                                $created_dir = create_unexisting_work_directory($base_work_dir, $dir_name);
	                                $created_dir = '/'.$created_dir;                                
	                                $now = api_get_utc_datetime();
	                                //Creating directory                                
	                                $sql_add_publication = "INSERT INTO " . $TBL_STUDENT_PUBLICATION . " SET " .         
	                                       "url         = '".$created_dir."',
	                                       c_id        = '".$course_id."',
	                                       title        = '".$parent_data['title']."',
	                                       description  = '".$parent_data['description']." folder_moved_from_session_id_$origin_session_id ',
	                                       author       = '',
	                                       active       = '0',
	                                       accepted     = '1',
	                                       filetype     = 'folder',                                   
	                                       sent_date    = '".$now."',
	                                       qualification    = '".$parent_data['qualification'] ."',
	                                       parent_id    = '',
	                                       qualificator_id  = '',
	                                       date_of_qualification    = '0000-00-00 00:00:00',
	                                       session_id   = ".$new_session_id;                                       
	                                $rest_insert     = Database::query($sql_add_publication);
	                                if ($debug) echo ($sql_add_publication);
	                                // add the directory
	                                $id = Database::insert_id();
	                                 //Folder created
	                                api_item_property_update($course_info, 'work', $id, 'DirectoryCreated', api_get_user_id());      
	                                if ($debug) var_dump($rest_insert);
	                                $new_parent_id = $id;                                
	                                $result_message[$TBL_STUDENT_PUBLICATION.' - new folder created called: '.$created_dir]++;
                                }                                                        
                            }                            
                                   
                            //Creating student_publication_assignment if exists
                            $sql = "SELECT * FROM $TBL_STUDENT_PUBLICATION_ASSIGNMENT WHERE publication_id = $parent_id AND c_id = $course_id";
                            if ($debug) var_dump($sql);
                            $rest_select     = Database::query($sql);
                            if (Database::num_rows($rest_select) > 0 ) {
                                if ($update_database) {
                                    $assignment_data = Database::fetch_array($rest_select,'ASSOC');
                                    $sql_add_publication = "INSERT INTO " . $TBL_STUDENT_PUBLICATION_ASSIGNMENT . " SET 
                                    	c_id = '$course_id',          
                                       expires_on          = '".$assignment_data['expires_on']."',
                                       ends_on              = '".$assignment_data['ends_on']."',
                                       add_to_calendar      = '".$assignment_data['add_to_calendar']."',
                                       enable_qualification = '".$assignment_data['enable_qualification']."',
                                       publication_id       = '".$new_parent_id."'";
                                    if ($debug) echo $sql_add_publication; 
                                    $rest_select     = Database::query($sql_add_publication);
                                    $id = Database::insert_id();
                                    
                                    $sql_update = "UPDATE " . $TBL_STUDENT_PUBLICATION . " SET " . 
                                      "has_properties         = '".$id."',
                                       view_properties    = '1'
                                       WHERE id   = ".$new_parent_id;
                                      if ($debug) echo $sql_update; 
                                    $rest_update     = Database::query($sql_update);    
                                   
                                             
                                    if ($debug) var_dump($sql_update);
                                    $result_message[$TBL_STUDENT_PUBLICATION_ASSIGNMENT]++;                  
                                }                                                                                         
                            }                             
                            
                            $doc_url = $data['url'];
                            $new_url = str_replace($parent_data['url'], $created_dir, $doc_url);                            
                            
                            if ($update_database) {                                
                                //Creating a new work                                  
                                $sql_add_publication = "INSERT INTO " . $TBL_STUDENT_PUBLICATION . " SET " .
                                               "url         = '" . $new_url . "',
                                               c_id        = '".$course_id."',
                                               title       = '" . $data['title']. "',
                                               description = '" . $data['description'] . " file moved',
                                               author      = '" . $data['author'] . "',
                                               active       = '" . $data['active']. "',
                                               accepted     = '" . $data['accepted']. "',
                                               post_group_id = '" . $data['post_group_id'] . "',
                                               sent_date    =  '".$data['sent_date'] ."',
                                               parent_id    =  '".$new_parent_id ."' ,
                                               session_id = ".$new_session_id;
                                               
                                if ($debug) echo $sql_add_publication;   
                                $rest_insert     = Database::query($sql_add_publication);      
                                if ($debug) var_dump($rest_insert);
                                $id = Database::insert_id();
                                api_item_property_update($course_info, 'work', $id, 'DocumentAdded', $user_id);
                                $result_message[$TBL_STUDENT_PUBLICATION]++;                                                                                 
                                  
                                $full_file_name   = $course_dir.'/'.$doc_url;                                  
                                $new_file         = $course_dir.'/'.$new_url;
                                
                                if (file_exists($full_file_name)) {
                                    //deleting old assignment
                                    $result = copy($full_file_name, $new_file);                                  
                                    if ($result) {
                                        unlink($full_file_name);                                            
                                        $sql = "DELETE FROM $TBL_STUDENT_PUBLICATION WHERE id= ".$data['id'];
                                        if ($debug) var_dump($sql);
                                        $result_delete     = Database::query($sql);    
                                        api_item_property_update($course_info, 'work', $data['id'], 'DocumentDeleted', api_get_user_id());
                                    }
                                }
                            }
                        }
                        
                    }                    
                }                
                
                //9. Survey   Pending                 
                
                //10. Dropbox - not neccesary to move categories (no presence of session_id)
               
                $sql = "SELECT id FROM $TBL_DROPBOX_FILE WHERE uploader_id = $user_id AND session_id = $origin_session_id AND c_id = $course_id";
                if ($debug) var_dump($sql); 
                $res = Database::query($sql);
                while($row = Database::fetch_array($res,'ASSOC')) {
                    $id = $row['id'];
                    if ($update_database) {
                    $sql = "UPDATE $TBL_DROPBOX_FILE SET session_id = '$new_session_id' WHERE c_id = $course_id AND id = $id";
                    if ($debug) var_dump($sql); 
                    $res = Database::query($sql);
                    if ($debug) var_dump($res);
                    
                    $sql = "UPDATE $TBL_DROPBOX_POST SET session_id = '$new_session_id' WHERE file_id = $id";
                    if ($debug) var_dump($sql); 
                    $res = Database::query($sql);
                    if ($debug) var_dump($res); 
                    $result_message[$TBL_DROPBOX_FILE]++;
                    }                        
                }                
                
                //11. Notebook
                
                $sql = "SELECT notebook_id FROM $TBL_NOTEBOOK 
                        WHERE user_id = $user_id AND session_id = $origin_session_id AND course = '$origin_course_code' AND c_id = $course_id";
                if ($debug) var_dump($sql); 
                $res = Database::query($sql);
                while($row = Database::fetch_array($res,'ASSOC')) {
                    $id = $row['notebook_id'];        
                    if ($update_database) {            
                        $sql = "UPDATE $TBL_NOTEBOOK SET session_id = '$new_session_id' WHERE c_id = $course_id AND notebook_id = $id";                    
                        if ($debug) var_dump($sql); 
                            $res = Database::query($sql);
                        if ($debug) var_dump($res); 
                   }
                }
                
                if ($update_database) {
                    echo '<h2>'.get_lang('StatsMoved').'</h2>';
                        if (is_array($result_message))
                        foreach ($result_message as $table=>$times) {
                            echo 'Table '.$table.' - '.$times.' records updated <br />';
                        }    
                } else {
                	   echo '<h1>'.get_lang('UserInformationOfThisCourse').'</h1>';
                        //var_dump($result_message);                     
                        	
                        echo '<br />';
                        echo '<table width="100%">';
                        echo '<tr>';
                        echo '<td width="50%" valign="top">';
                        
                        if ($origin_session_id == 0 ) {
                            echo '<h1>'.get_lang('OriginCourse').'</h1>';
                        } else {
                        	echo '<h1>'.get_lang('OriginSession').' #'.$origin_session_id.'</h1>';
                        }
                        compare_data($result_message);
                        echo '</td>';
                        echo '<td width="50%" valign="top">';
                        if ($new_session_id == 0 ) {
                            echo '<h1>'.get_lang('DestinyCourse').'</h1>';                                
                        } else {
                        	echo '<h1>'.get_lang('DestinySession').' #'.$new_session_id.'</h1>';
                        }                        
                        compare_data($result_message_compare);
                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';                  
                 
                        
                }
            } else {
                echo get_lang('CourseDoesNotExistInThisSession');            	
            }
        }
    } else {
    	echo get_lang('ThereWasAnError');
    }
    exit;
}
$htmlHeadXtra[] = '<script type="text/javascript">

   function moveto (unique_id, user_id) {
        var session_id = document.getElementById(unique_id).options[document.getElementById(unique_id).selectedIndex].value;
         $.ajax({
            contentType: "application/x-www-form-urlencoded",
            beforeSend: function(objeto) {
            $("div#reponse_"+unique_id).html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
            type: "POST",
            url: "user_move_stats.php",
            data: "load_ajax=1"+"&unique_id="+unique_id+"&user_id="+user_id+"&session_id="+session_id,
            success: function(datos) {
             $("div#reponse_"+unique_id).html(datos);
            }
        });                
    }
    function view_stat (unique_id, user_id) {
        var session_id = document.getElementById(unique_id).options[document.getElementById(unique_id).selectedIndex].value;
        load_thick("user_move_stats.php?load_ajax=1&view_stat=1"+"&unique_id="+unique_id+"&user_id="+user_id+"&session_id="+session_id,"");
/*
         $.ajax({
            contentType: "application/x-www-form-urlencoded",
            beforeSend: function(objeto) {
            $("div#reponse_"+unique_id).html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
            type: "POST",
            url: "user_move_stats.php",
            data: "load_ajax=1&view_stat=1"+"&unique_id="+unique_id+"&user_id="+user_id+"&session_id="+session_id,
            success: function(datos) {
             $("div#reponse_"+unique_id).html(datos);
            }
        });*/                
    }

  
 </script>';

function get_courses_list_by_user_id_based_in_exercises($user_id) {
    $TABLETRACK_EXERCICES       = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
    $user_id = intval($user_id);
	 //$sql = "SELECT DISTINCT exe_user_id, exe_cours_id as code, session_id as id_session FROM $TABLETRACK_EXERCICES WHERE exe_user_id = $user_id GROUP BY exe_user_id, exe_cours_id ORDER by exe_user_id, exe_cours_id ASC";
    $sql = "SELECT DISTINCT exe_user_id, exe_cours_id as code, session_id as id_session FROM $TABLETRACK_EXERCICES WHERE exe_user_id = $user_id ORDER by exe_user_id, exe_cours_id ASC";
    
    $res = Database::query($sql);
    $course_list = array();
    while($row = Database::fetch_array($res,'ASSOC')) {
        $course_list []= $row;    
    }
    return $course_list;    
}


Display::display_header(get_lang('MoveUserStats'));
echo  '<div class="actions">';
echo '<a href="../admin/index.php">'.Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('PlatformAdmin'),'','32').'</a>';
echo '</div>';
echo Display::display_normal_message(get_lang('CompareUserResultsBetweenCoursesAndCoursesInASession'),false);


// Some pagination
$page = 1;
if (isset($_GET['page']) && !empty($_GET['page'])) {
    $page = intval($_GET['page']);
}
$default = 20;
$count          = UserManager::get_number_of_users();
$nro_pages      = round($count/$default) + 1;
$begin          = $default* ($page - 1);
$end            = $default*$page;

$navigation     = "$begin - $end  / $count<br />";

if ($page > 1) {
    $navigation .='<a href="'.api_get_self().'?page='.($page - 1).'">'.get_lang('Previous').'</a>';
} else {
	$navigation .= get_lang('Previous');
}
$navigation .= '&nbsp;';
$page ++;
if ($page < $nro_pages)
    $navigation .= '<a href="'.api_get_self().'?page='.$page.'">'.get_lang('Next').'</a>';
else 
    $navigation .= get_lang('Next');

echo $navigation;
$user_list      = UserManager::get_user_list(array(), array(), $begin, $default);
$session_list   = SessionManager::get_sessions_list(array(),array('name'));
$options = '';
$options .= '<option value="0">--'.get_lang('SelectASession').'--</option>';
foreach ($session_list as $session_data) {
	$my_session_list[$session_data['id']] =$session_data['name'];
    $options .= '<option value="'.$session_data['id'].'">'.$session_data['name'].'</option>';
}

$combinations = array();

if (!empty($user_list)) {
    foreach ($user_list  as $user) {
        
        $user_id = $user['user_id'];
        //if ($user_id != 78 ) continue;
        $name = $user['firstname'].' '.$user['lastname'];
        $course_list_registered = CourseManager::get_courses_list_by_user_id($user_id, true, false);
 
        $new_course_list = array();
        foreach ($course_list_registered as $course_reg) {
            if (empty($course_reg['id_session'])) {
            	$course_reg['id_session'] = 0;
            }
        	$new_course_list[] = $course_reg['code'].'_'.$course_reg['id_session'];
        }
              
        $course_list = get_courses_list_by_user_id_based_in_exercises($user_id);
        
        if (is_array($course_list) && !empty($course_list)) {
            foreach ($course_list as $my_course) {
                $key = $my_course['code'].'_'.$my_course['id_session'];
            	if(!in_array($key,$new_course_list)) {
                    $my_course['not_registered'] = 1;
            		$course_list_registered[] = $my_course;
            	}
            }
        }

        $course_list  = $course_list_registered;
               
        echo '<div>';
        
        echo '<table class="data_table">';
            echo '<tr>';    
            echo '<th style="text-align:left;" colspan="'.count($course_list).'">';
               echo "<h3> $name #$user_id </h3>  ";
            echo '</th>';   
            echo '</tr>';     
        
        if (!empty($course_list)) {
            echo '<tr>';            
            
            foreach ($course_list as $course) {                
                echo '<td>';
                if (isset($course['id_session']) && !empty($course['id_session'])) {
                	echo '<b>'.get_lang('SessionName').'</b> '.$my_session_list[$course['id_session']].'<br />';
                }
                echo $course['title'];
                echo ' ('.$course['code'].') ';
                if (isset($course['not_registered']) && !empty($course['not_registered'])) {
                	echo ' <i>'.get_lang('UserNotRegistered').'</i>';
                }
                echo '</td>';               
            }
            echo '</tr>';
            echo '<tr>';
            
            foreach ($course_list as $course) {
                $course_code = $course['code'];
                if (empty($course['id_session'])) {
                	$session_id = 0;
                } else {
                	$session_id = $course['id_session'];
                }
                echo '<td>';
                echo get_lang('MoveTo');    echo '<br />';
                $unique_id = uniqid();
                $combinations[$unique_id] = array('course_code' =>$course_code, 'session_id'=>$session_id);
                 
                echo '<select id="'.$unique_id.'" name="'.$unique_id.'">';
                echo $options;
                echo '</select>';
                echo '<br />';
                //KK(\''.$unique_id.'\', \''.$user_id.'\')
                //echo '<input type="submit" class="thickbox"  value="'.get_lang('ViewStats').'" alt="user_move_stats.php?height=520&width=500&load_ajax=1&view_stat=1&unique_id='.$unique_id.'&user_id='.$user_id.'" >';
                //echo '<input type="submit" class="thickbox"  value="'.get_lang('ViewStats').'" alt="user_move_stats.php?height=520&width=500&load_ajax=1&view_stat=1&unique_id='.$unique_id.'&user_id='.$user_id.'" >';
                echo '<button type="submit" class="save" onclick="view_stat(\''.$unique_id.'\', \''.$user_id.'\');"> '.get_lang('CompareStats').'</button>';
                
                echo '<button type="submit" class="save" onclick="moveto(\''.$unique_id.'\', \''.$user_id.'\');"> '.get_lang('Move').'</button>';
                
                
                
                echo '<div id ="reponse_'.$unique_id.'"></div>';                                 
                echo '</td>';
            }
            echo '</tr>';
        } else {
            echo '<td>';
        	echo get_lang('NoCoursesForThisUser');
            echo '</td>';
            
        }
        echo '</table>';
        echo '</div>';
    }
}
echo $navigation;
$_SESSION['combination'] = $combinations;


