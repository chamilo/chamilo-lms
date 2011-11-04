<?php
/* For licensing terms, see /license.txt */
/**
*   This class provides methods for the notebook management.
*   Include/require it in your code to use its features.
*   @package chamilo.library
*/
/**
 * Code
 */

 /**
 * @package chamilo.library
 */

define ('SKILL_TYPE_REQUIREMENT',   'required');
define ('SKILL_TYPE_ACQUIRED',      'acquired');
define ('SKILL_TYPE_BOTH',          'both');

class SkillRelSkill extends Model {
    var $columns = array('skill_id', 'parent_id','relation_type', 'level');
    public function __construct() {
        $this->table                      = Database::get_main_table(TABLE_MAIN_SKILL_REL_SKILL);
    }
    
    /**
     * Gets an element
     */
    public function get_skill_info($id) {
        if (empty($id)) { return array(); }     
        $result = Database::select('*',$this->table, array('where'=>array('skill_id = ?'=>intval($id))));
        return $result;
    }
    
    public function get_skill_parents($skill_id, $add_child_info = true) {
        $skill_id = intval($skill_id);        
        $sql = 'SELECT child.* FROM '.$this->table.' child LEFT JOIN '.$this->table.' parent 
                ON child.parent_id = parent.skill_id
                WHERE child.skill_id = '.$skill_id.' ';
        $result = Database::query($sql);
        $skill  = Database::store_result($result,'ASSOC');
        $skill  = isset($skill[0]) ? $skill[0] : null; 
                  
        $parents = array();
        if (!empty($skill)) {        
            if ($skill['parent_id'] != null) {
                $parents = self::get_skill_parents($skill['parent_id']);
            }        
            if ($add_child_info) {
                $parents[] = $skill;
            }        
        }
        return $parents;
    }
    
    public function get_direct_parents($skill_id) {
        $skill_id = intval($skill_id);        
        $sql = 'SELECT parent_id as skill_id FROM '.$this->table.'
                WHERE skill_id = '.$skill_id.' ';
        $result = Database::query($sql);
        $skill  = Database::store_result($result,'ASSOC');
        $skill  = isset($skill[0]) ? $skill[0] : null;
        $parents = array();
        if (!empty($skill)) {
            $parents[] = $skill;            
        }        
        return $parents;
    }
    
    public function get_children($skill_id, $add_child_info = true) {
        $skills = $this->find('all', array('where'=> array('parent_id = ? '=> $skill_id)));
        $skill_obj = new Skill();
        if (!empty($skills)) {
            foreach ($skills as &$skill) {
              $skill['data'] = $skill_obj->get($skill['skill_id']);
            }
        }
        return $skills;
    }
}

 /**
 * @package chamilo.library
 */
class SkillRelGradebook extends Model {
    var $columns = array('id', 'gradebook_id','skill_id');
    
    public function __construct() {
        $this->table                      = Database::get_main_table(TABLE_MAIN_SKILL_REL_GRADEBOOK);
    }
    
    public function exists_gradebook_skill($gradebook_id, $skill_id) {
        $result = $this->find('all', array('where'=>array('gradebook_id = ? AND skill_id = ?' => array($gradebook_id, $skill_id))));
        if (!empty($result)) {
            return true;
        }
        return false;    
    }
    
    /**
     * Gets an element
     */
    public function get_skill_info($skill_id, $gradebook_id) {
        if (empty($skill_id)) { return array(); }     
        $result = Database::select('*',$this->table, array('where'=>array('skill_id = ? AND gradebook_id = ? '=>array($skill_id, $gradebook_id))),'first');
        return $result;
    }
    
}

 /**
 * @package chamilo.library
 */
class SkillRelUser extends Model {
    var $columns = array('id', 'user_id','skill_id','acquired_skill_at','assigned_by');
    public function __construct() {
        $this->table = Database::get_main_table(TABLE_MAIN_SKILL_REL_USER);
    }
}
   

class Skill extends Model {    
    var $columns = array('id', 'name','description', 'access_url_id');
    
    public function __construct() {
        $this->table                      = Database::get_main_table(TABLE_MAIN_SKILL);
        $this->table_skill_rel_gradebook  = Database::get_main_table(TABLE_MAIN_SKILL_REL_GRADEBOOK);
        $this->table_skill_rel_user       = Database::get_main_table(TABLE_MAIN_SKILL_REL_USER);
        $this->table_course               = Database::get_main_table(TABLE_MAIN_COURSE);
        $this->table_skill_rel_skill      = Database::get_main_table(TABLE_MAIN_SKILL_REL_SKILL);
        $this->table_gradebook            = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
    }
    
    public function skill_exists($skill_id) {
        
    }
     
    function get_all($load_user_data = false) {
        $sql = "SELECT id, name, description, parent_id, relation_type 
                    FROM {$this->table} skill INNER JOIN {$this->table_skill_rel_skill} skill_rel_skill
                    ON skill.id = skill_rel_skill.skill_id ";
        $result = Database::query($sql);
        $skills = array();        
        
        if (Database::num_rows($result)) {        
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $skill_rel_skill = new SkillRelSkill();
                $a = $skill_rel_skill->get_skill_parents($row['id']);                              
                $row['level'] = count($a)-1;                
                $row['gradebooks'] = self::get_gradebooks_by_skill($row['id']);                    
                $skills[$row['id']] = $row;            
            }        
        }
        
        if ($load_user_data) {
            $passed_skills = $this->get_user_skills(api_get_user_id());      
                  
            foreach ($skills as &$skill) {
                $skill['done_by_user'] = 0;
                if (in_array($skill['id'], $passed_skills)) {
                    $skill['done_by_user'] = 1;
                }                
            }
        }
        
        return $skills;        
    }
    
    function get_gradebooks_by_skill($skill_id) {
        $skill_id = intval($skill_id);
        $sql = "SELECT g.* FROM {$this->table_gradebook} g INNER JOIN {$this->table_skill_rel_gradebook} sg
                    ON g.id = sg.gradebook_id 
                 WHERE sg.skill_id = $skill_id";
        $result = Database::query($sql);         
        $result = Database::store_result($result,'ASSOC');
        return $result;
    }
    
    function get_children($skill_id) {
        $skill_rel_skill = new SkillRelSkill();
        $skills = $skill_rel_skill->get_children($skill_id, true);
        return $skills;
    }    
    
    /**
     * All parents from root to n
     */
    function get_parents($skill_id) {
        $skill_rel_skill = new SkillRelSkill();
        $skills = $skill_rel_skill->get_skill_parents($skill_id, true);        
        foreach($skills as &$skill) {
            $skill['data'] = self::get($skill['skill_id']);             
        }
        return $skills;
    }
    
    /**
     * All direct parents
     */
    function get_direct_parents($skill_id) {
        $skill_rel_skill = new SkillRelSkill();
        $skills = $skill_rel_skill->get_direct_parents($skill_id, true);        
        foreach($skills as &$skill) {
            $skill['data'] = self::get($skill['skill_id']);             
        }
        return $skills;
    }
       
    public function add($params) {
        if (!isset($params['parent_id'])) {
            $params['parent_id'] = 1;
        }
        $skill_rel_skill     = new SkillRelSkill();
        $skill_rel_gradebook = new SkillRelGradebook();
        
        //Saving name, description
        $skill_id = $this->save($params);
        if ($skill_id) {
            //Saving skill_rel_skill (parent_id, relation_type)
            $attributes = array(
                            'skill_id'      => $skill_id,
                            'parent_id'     => $params['parent_id'],
                            'relation_type' => $params['relation_type'],
                            //'level'         => $params['level'],
            );            
            $skill_rel_skill->save($attributes);            
            
            if (!empty($params['gradebook_id'])) {
                foreach ($params['gradebook_id'] as $gradebook_id) {
                    $attributes = array();
                    $attributes['gradebook_id'] = $gradebook_id;
                    $attributes['skill_id']     = $skill_id;                    
                    $skill_rel_gradebook->save($attributes);
                }            
            }                                 
        }
    }
    
    /**
    * Return true if the user has the skill
    * 
    * @param int $userId User's id
    * @param int $skillId Skill's id
    * @param int $checkInParents if true, function will search also in parents of the given skill id
    * 
    * @return bool
    */
    public function user_has_skill($user_id, $skill_id) {
        $skills = $this->get_user_skills($user_id);                    
        foreach($skills as $my_skill_id) {
            if ($my_skill_id == $skill_id) {
                return true;
            }
        }
        return false;
    }
    
    public function add_skill_to_user($user_id, $gradebook_id) {
        $skill_gradebook = new SkillRelGradebook();
        $skill_rel_user  = new SkillRelUser();
        
        $skill_gradebooks = $skill_gradebook->get_all(array('where'=>array('gradebook_id = ?' =>$gradebook_id)));
        if (!empty($skill_gradebooks)) {        
            foreach ($skill_gradebooks as $skill_gradebook) {
                $user_has_skill = $this->user_has_skill($user_id, $skill_gradebook['skill_id']);            
                if (!$user_has_skill) {
                    $params = array(    'user_id'   => $user_id,
                                        'skill_id'  => $skill_gradebook['skill_id'],
                                        'acquired_skill_at'  => api_get_utc_datetime(),
                                   );                                   
                    $skill_rel_user->save($params);
                }
            }
        }
    }
    
    public function remove_skill_to_user($user_id) {
    }
    

    
    /**
    * Get user's skills
    * 
    * @param int $userId User's id


    */
    public function get_user_skills($user_id, $get_skill_data = false) {
        $user_id = intval($user_id);        
        //$sql = 'SELECT skill.*, user.* FROM '.$this->table_skill_rel_user.' user INNER JOIN '.$this->table_skill.' skill
        
        $sql = 'SELECT DISTINCT s.id, s.name FROM '.$this->table_skill_rel_user.' u INNER JOIN '.$this->table.' s 
                ON u.skill_id = s.id
                WHERE user_id = '.$user_id;
        $result = Database::query($sql);
        $skills = Database::store_result($result, 'ASSOC');
        $clean_skill = array();        
        if (!empty($skills)) {
            foreach ($skills as $skill) {
                if ($get_skill_data) {
                    $clean_skill[$skill['id']] = $skill;    
                } else {
                    $clean_skill[$skill['id']] = $skill['id'];
                }                
            }
        }
        return $clean_skill;
    }

        
    public function get_skills_tree($user_id = null, $return_flat_array = false) {
        $skills = $this->get_all();    
        $refs = array();        
        $skills_tree = null;
        
        // Create references for all nodes
        $flat_array = array();
        if (!empty($skills)) {
            foreach($skills as &$skill) {
                if ($skill['parent_id'] == 0) {
                    $skill['parent_id'] = 'root';
                }
                
                $skill['data'] = array('parent_id' => $skill['parent_id']); // because except main keys (id, name, children) others keys are not saved while in the space tree
                
                if ($user_id) {
                    $skill['data']['achieved'] = $this->user_has_skill($user_id, $skill['id']);
                }                
                $refs[$skill['id']] = &$skill;
                $flat_array[$skill['id']] =  &$skill;
            }
        
            // Moving node to the children index of their parents
            foreach($skills as $skillInd => &$skill) {
                $refs[$skill['parent_id']]['children'][] = &$skill;
                $flat_array[$skillInd] =  $skill;                                
            }
            
            $skills_tree = array(
                'name'      => get_lang('SkillRootName'),
                'id'        => 'root',
                'children'  => $refs['root']['children'],
                'data'      => array()
            );
        }    
//var_dump($flat_array);exit;    
        if ($return_flat_array) {
            return $flat_array;
        }
        unset($skills);        
        return $skills_tree;
    }    
}