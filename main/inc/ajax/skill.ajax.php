<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

require_once '../global.inc.php';

require_once api_get_path(LIBRARY_PATH).'skill.lib.php';
require_once api_get_path(LIBRARY_PATH).'gradebook.lib.php';

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

$skill           = new Skill();
$gradebook       = new Gradebook();
$skill_gradebook = new SkillRelGradebook();

switch ($action) {
    case 'add':  
        if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {            
            $skill_id = $skill->edit($_REQUEST);    
        } else {
            $skill_id = $skill->add($_REQUEST);    
        }        
        echo $skill_id;
        break;      
    case 'find_skills':        
        $skills = $skill->find('all', array('where' => array('name LIKE %?% '=>$_REQUEST['tag'])));
        $return_skills = array();    
        foreach ($skills as $skill) {
            $skill['caption'] = $skill['name'];
            $skill['value'] =  $skill['id'];
            $return_skills[] = $skill;
        }            
        echo json_encode($return_skills);
        break;
    case 'profile_matches':        
        $skill_rel_user  = new SkillRelUser();
        $skills = $_REQUEST['skills'];
        $users  = $skill_rel_user->get_user_by_skills($skills);
        
        $total_skills_to_search = array();
        
        if (!empty($users)) {
            foreach ($users as $user) {            
                $user_info = api_get_user_info($user['user_id']);
                $user_list[$user['user_id']]['user'] = $user_info;
                $my_user_skills = $skill_rel_user->get_user_skills($user['user_id']);
                $user_skills = array();
                $found_counts = 0 ;
                foreach($my_user_skills as $my_skill) {

                    $found = false;
                    if (in_array($my_skill['skill_id'], $skills)) {
                        $found = true;
                        $found_counts++;
                    }
                    $user_skills[] = array('skill_id' => $my_skill['skill_id'], 'found' => $found);
                    $total_skills_to_search[$my_skill['skill_id']] = $my_skill['skill_id']; 
                }
                $user_list[$user['user_id']]['skills'] = $user_skills;
                $user_list[$user['user_id']]['total_found_skills'] = $found_counts;
            }
            $ordered_user_list = array();
            foreach($user_list as $user_id => $user_data) {
                $ordered_user_list[$user_data['total_found_skills']][] = $user_data;
            }
            if (!empty($ordered_user_list)) {
                asort($ordered_user_list);
            }
        }
        
        break;
    case 'get_gradebooks':        
        $gradebooks = $gradebook_list = $gradebook->get_all();
        
        $gradebook_list = array();
        //Only course gradebook with certificate
        if (!empty($gradebooks)) {
            foreach ($gradebooks as $gradebook) {
                if ($gradebook['parent_id'] == 0 && !empty($gradebook['certif_min_score']) && !empty($gradebook['document_id'])) {
                    $gradebook_list[]  = $gradebook;
                    //$gradebook['name'] = $gradebook['name'];
                    //$gradebook_list[]  = $gradebook;
                } else {
                  //  $gradebook['name'] = $gradebook_list[$gradebook['parent_id']]['name'].' > '.$gradebook['name'];
                    //$gradebook_list[]  = $gradebook;
                }
               
            }
        }
        echo json_encode($gradebook_list);
        break;
    case 'find_gradebooks':        
        $gradebooks = $gradebook->find('all', array('where' => array('name LIKE %?% ' => $_REQUEST['tag'])));
        
        $return = array();    
        foreach ($gradebooks as $item) {
            $item['caption'] = $item['name'];
            $item['value'] =  $item['id'];
            $return[] = $item;
        }        
        echo json_encode($return);        
        break;          
    case 'gradebook_exists':
        $data = $gradebook->get($_REQUEST['gradebook_id']);        
        if (!empty($data)) {
            echo 1;
        } else {
            echo 0;
        }
        break;        
    case 'get_skills':
        $load_user_data = isset($_REQUEST['load_user_data']) ? $_REQUEST['load_user_data'] : null;
        //$parent_id = intval($_REQUEST['parent_id']);
        $id = intval($_REQUEST['id']);
        $skills = $skill->get_all($load_user_data, false, $id);                    
        echo json_encode($skills);
        break;
    case 'get_skill_info':
        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
        $skill_info = $skill->get_skill_info($id);                    
        echo json_encode($skill_info);
        break;     
    case 'get_gradebook_info':
        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
        $info = $gradebook->get($id);                    
        echo json_encode($info);
        break;  
    case 'load_children':
        $id             = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
        $load_user_data = isset($_REQUEST['load_user_data']) ? $_REQUEST['load_user_data'] : null;
        $skills = $skill->get_children($id, $load_user_data);
        
        $return = array();
        foreach ($skills as $skill) {
            if (isset($skill['data']) && !empty($skill['data'])) {
                $return[$skill['data']['id']] = array(
                                                    'id'    => $skill['data']['id'],
                                                    'name'  => $skill['data']['name'], 
                                                    'passed'=> $skill['data']['passed']);
            }
        }
        $success = true;
        if (empty($return)) {
            $success = false;
        }
        
        $result = array (
            'success' => $success,
            'data' => $return
        );
        echo json_encode($result);
        break;
    case 'load_direct_parents':
        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
        $skills = $skill->get_direct_parents($id);        
        $return = array();
        foreach($skills as $skill) {
            $return [$skill['data']['id']] = array (
                                                'id'        => $skill['data']['id'],
                                                'parent_id' => $skill['data']['parent_id'],
                                                'name'      => $skill['data']['name']
                                                );
        }
        echo json_encode($return);        
        break;        
    case 'delete_gradebook_from_skill':
    case 'remove_skill':
        if (!empty($_REQUEST['skill_id']) && !empty($_REQUEST['gradebook_id'])) {            
            $skill_item = $skill_gradebook->get_skill_info($_REQUEST['skill_id'], $_REQUEST['gradebook_id']);            
            if (!empty($skill_item)) {
                $skill_gradebook->delete($skill_item['id']);
                echo 1;
            } else {
                echo 0;    
            }
        } else {
            echo 0;
        }
        break; 
    case 'save_profile':
        $skill_profile = new SkillProfile();
        $params = $_REQUEST;
        $params['skills'] = isset($_SESSION['skills']) ? $_SESSION['skills'] : null; 
        $skill_data = $skill_profile->save($params);        
        if (!empty($skill_data)) {
            echo 1;
        } else {
            echo 0;
        }
        break;        
    case 'skill_exists':
        $skill_data = $skill->get($_REQUEST['skill_id']);        
        if (!empty($skill_data)) {
            echo 1;
        } else {
            echo 0;
        }
        break;   
    case 'get_skills_tree_json':        
        $user_id    = isset($_REQUEST['load_user']) && $_REQUEST['load_user'] == 1 ? api_get_user_id() : 0;
        $skill_id   = isset($_REQUEST['skill_id']) ? $_REQUEST['skill_id'] : 0;
        $depth      = isset($_REQUEST['main_depth']) ? $_REQUEST['main_depth'] : 2;        
        $all = $skill->get_skills_tree_json($user_id, $skill_id, false, $depth);
        echo $all;
        break;
    default:
        echo '';
}
exit;
