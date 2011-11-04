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
$skill_rel_skill = new SkillRelSkill();

switch ($action) {
    case 'add':     
        $skill->add($_REQUEST);
        break;      
    case 'find_skills':
        $tag    = Database::escape_string($_REQUEST['tag']);
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
        /*$gradebook_list = array();
        if (!empty($gradebooks)) {
            foreach($gradebooks as $gradebook) {
                if ($gradebook['parent_id'] == 0) {
                    $gradebook['name'] = $gradebook['name'];
                    $gradebook_list[]  = $gradebook;
                } else {
                  //  $gradebook['name'] = $gradebook_list[$gradebook['parent_id']]['name'].' > '.$gradebook['name'];
                    $gradebook_list[]  = $gradebook;
                }
               
            }
        }*/
        echo json_encode($gradebook_list);
        break;    
    case 'get_skills':
        $load_user_data = isset($_REQUEST['load_user_data']) ? $_REQUEST['load_user_data'] : null;
        $skills = $skill->get_all($load_user_data);                    
        echo json_encode($skills);
        break;   
        
    case 'load_children':
        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
        $skills = $skill->get_children($id);
        $return = array();
        foreach($skills as $skill) {
            $return [$skill['data']['id']] = array('name' => $skill['data']['name'], 'id'=>$skill['data']['id']);
        }
        echo json_encode($return);
        break;
    case 'load_direct_parents':
        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
        $skills = $skill->get_direct_parents($id);
        $return = array();
        foreach($skills as $skill) {
            $return [$skill['data']['id']] = array('name' => $skill['data']['name'], 'id'=>$skill['data']['id']);
        }
        echo json_encode($return);        
        break;    
    case 'skill_exists':
        $skill_data = $skill->get($_REQUEST['skill_id']);        
        if (!empty($skill_data)) {
            echo 1;
        } else {
            echo 0;
        }
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
    default:
        echo '';
}
exit;