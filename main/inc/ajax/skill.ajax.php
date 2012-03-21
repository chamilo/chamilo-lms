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
            $skill->edit($_REQUEST);    
        } else {
            $skill->add($_REQUEST);    
        }        
        break;      
    case 'find_skills':        
        $skills = $skill->find('all', array('where' => array('name LIKE %?% '=>$_REQUEST['tag'])));
        $return_skills = array();    
        foreach($skills as $skill) {
            $skill['caption'] = $skill['name'];
            $skill['value'] =  $skill['id'];
            $return_skills[] = $skill;
        }            
        echo json_encode($return_skills);
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
    case 'load_children':
        $id             = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
        $load_user_data = isset($_REQUEST['load_user_data']) ? $_REQUEST['load_user_data'] : null;
        $skills = $skill->get_children($id, $load_user_data);
        
        $return = array();
        foreach($skills as $skill) {
            if (isset($skill['data']) && !empty($skill['data'])) {
                $return[$skill['data']['id']] = array(
                                                    'id'    => $skill['data']['id'],
                                                    'name'  => $skill['data']['name'], 
                                                    'passed'=> $skill['data']['passed']);
            }
        }
        echo json_encode($return);
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
    case 'remove_skill':
        if (!empty($_REQUEST['skill_id']) && !empty($_REQUEST['gradebook_id'])) {            
            $skill_item = $skill_gradebook->get_skill_info($_REQUEST['skill_id'], $_REQUEST['gradebook_id']);
            if (!empty($skill_item)) {
                $skill_gradebook->delete($skill_item['id']);
                echo 1;
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
    default:
        echo '';
}
exit;