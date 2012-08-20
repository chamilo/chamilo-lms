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
define ('SKILL_TYPE_REQUIREMENT',   'required');
define ('SKILL_TYPE_ACQUIRED',      'acquired');
define ('SKILL_TYPE_BOTH',          'both');

require_once api_get_path(LIBRARY_PATH).'model.lib.php';

class SkillProfile extends Model {
    var $columns = array('id', 'name','description');
    public function __construct() {
        $this->table = Database::get_main_table(TABLE_MAIN_SKILL_PROFILE);
        $this->table_rel_profile = Database::get_main_table(TABLE_MAIN_SKILL_REL_PROFILE);
    }

    public function get_profiles() {
        $sql = "SELECT * FROM $this->table p INNER JOIN $this->table_rel_profile sp ON(p.id = sp.profile_id) ";
        $result     = Database::query($sql);
        $profiles   = Database::store_result($result,'ASSOC');
        return $profiles;
    }

    public function save($params, $show_query = false) {
        if (!empty($params)) {
           $profile_id = parent::save($params, $show_query);
            if ($profile_id) {
                $skill_rel_profile = new SkillRelProfile();
                if (isset($params['skills'])) {
                    foreach($params['skills'] as $skill_id) {
                        $attributes = array('skill_id' => $skill_id, 'profile_id'=>$profile_id);
                        $skill_rel_profile->save($attributes);
                    }
                }
                return $profile_id;
            }
        }
        return false;
    }
}

class SkillRelProfile extends Model {
    var $columns = array('id', 'skill_id', 'profile_id');
    public function __construct() {
        $this->table = Database::get_main_table(TABLE_MAIN_SKILL_REL_PROFILE);
    }

    public function get_skills_by_profile($profile_id) {
        $skills =  $this->get_all(array('where'=>array('profile_id = ? ' => $profile_id)));
        $return_array = array();
        if (!empty($skills)) {
            foreach($skills as $skill_data) {
                $return_array[] = $skill_data['skill_id'];
            }
        }
        return $return_array;
    }
}

class SkillRelSkill extends Model {
    var $columns = array('skill_id', 'parent_id','relation_type', 'level');
    public function __construct() {
        $this->table = Database::get_main_table(TABLE_MAIN_SKILL_REL_SKILL);
    }

    /**
     * Gets an element
     */
    public function get_skill_info($id) {
        if (empty($id)) { return array(); }
        $result = Database::select('*',$this->table, array('where'=>array('skill_id = ?'=>intval($id))), 'first');
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
    

    public function get_children($skill_id, $load_user_data = false, $user_id = false) {
        $skills = $this->find('all', array('where'=> array('parent_id = ? '=> $skill_id)));
        $skill_obj = new Skill();
        $skill_rel_user = new SkillRelUser();

        if ($load_user_data) {
            $passed_skills = $skill_rel_user->get_user_skills($user_id);
            $done_skills = array();
            foreach ($passed_skills as $done_skill) {
                $done_skills[] = $done_skill['skill_id'];
            }
        }
        
        if (!empty($skills)) {
            foreach ($skills as &$skill) {
                $skill['data'] = $skill_obj->get($skill['skill_id']);
                if (isset($skill['data']) && !empty($skill['data'])) {
                    if (!empty($done_skills)) {
                        $skill['data']['passed'] =  0;
                        if (in_array($skill['skill_id'], $done_skills)) {
                            $skill['data']['passed'] =  1;
                        }
                    }
                } else {
                    $skill  = null;
                }
            }
        }
        return $skills;
    }

    function update_by_skill($params) {
        $result = Database::update($this->table, $params, array('skill_id = ? '=> $params['skill_id']));
        if ($result) {
            return true;
        }
        return false;
    }

    function relation_exists($skill_id, $parent_id) {
        $result = $this->find('all', array('where'=>array('skill_id = ? AND parent_id = ?' => array($skill_id, $parent_id))));
        if (!empty($result)) {
            return true;
        }
        return false;
    }
}

 /**
 * @package chamilo.library
 */
class SkillRelGradebook extends Model {
    var $columns = array('id', 'gradebook_id','skill_id');

    public function __construct() {
        $this->table = Database::get_main_table(TABLE_MAIN_SKILL_REL_GRADEBOOK);
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

    public function update_gradebooks_by_skill($skill_id, $gradebook_list) {
        $original_gradebook_list = $this->find('all', array('where'=>array('skill_id = ?' => array($skill_id))));
        $gradebooks_to_remove = array();
        $gradebooks_to_add = array();
        $original_gradebook_list_ids = array();

        if (!empty($original_gradebook_list)) {
            foreach ($original_gradebook_list as $gradebook) {
                if (!in_array($gradebook['gradebook_id'], $gradebook_list)) {
                    $gradebooks_to_remove[] = $gradebook['id'];
                }
            }
            foreach($original_gradebook_list as $gradebook_item) {
                $original_gradebook_list_ids[] = $gradebook_item['gradebook_id'];
            }
        }

        if (!empty($gradebook_list))
        foreach($gradebook_list as $gradebook_id) {
            if (!in_array($gradebook_id, $original_gradebook_list_ids)) {
                $gradebooks_to_add[] = $gradebook_id;
            }
        }
        //var_dump($gradebooks_to_add, $gradebooks_to_remove);
        if (!empty($gradebooks_to_remove)) {
            foreach($gradebooks_to_remove as $id) {
               $this->delete($id);
            }
        }

        if (!empty($gradebooks_to_add)) {
            foreach($gradebooks_to_add as $gradebook_id) {
               $attributes = array('skill_id' => $skill_id, 'gradebook_id' => $gradebook_id);
               $this->save($attributes);
            }
        }
    }

    function update_by_skill($params) {
        $skill_info = $this->exists_gradebook_skill($params['gradebook_id'], $params['skill_id']);

        if ($skill_info) {
            return;
        } else {
            $result = $this->save($params);
        }
        if ($result) {
            return true;
        }
        return false;
    }
}

 /**
 * @package chamilo.library
 */
class SkillRelUser extends Model {
    var $columns = array('id', 'user_id','skill_id','acquired_skill_at','assigned_by');
    public function __construct() {
        $this->table        = Database::get_main_table(TABLE_MAIN_SKILL_REL_USER);
        //$this->table_user   = Database::get_main_table(TABLE_MAIN_USER);
    }

    public function get_user_by_skills($skill_list) {
        $users = array();
        if (!empty($skill_list)) {
            $skill_list = array_map('intval', $skill_list);
            $skill_list = implode("', '", $skill_list);

            $sql = "SELECT user_id FROM {$this->table}  WHERE skill_id IN ('$skill_list') ";

            $result = Database::query($sql);
            $users  = Database::store_result($result, 'ASSOC');
        }
        return $users;
    }

    public function get_user_skills($user_id) {
        if (empty($user_id)) { return array(); }
        $result = Database::select('skill_id',$this->table, array('where'=>array('user_id = ?'=>intval($user_id))), 'all');
        return $result;
    }
}


class Skill extends Model {
    var $columns  = array('id', 'name','description', 'access_url_id');
    var $required = array('name');
    /** Array of colours by depth, for the coffee wheel. Each depth has 4 col */
    var $colours = array(
      0 => array('#f9f0ab', '#ecc099', '#e098b0', '#ebe378'),
      1 => array('#d5dda1', '#4a5072', '#8dae43', '#72659d'),
      2 => array('#b28647', '#2e6093', '#393e64', '#1e8323'),
      3 => array('#9f6652', '#9f6652', '#9f6652', '#9f6652'),
      4 => array('#af643c', '#af643c', '#af643c', '#af643c'),
      5 => array('#72659d', '#72659d', '#72659d', '#72659d'),
      6 => array('#8a6e9e', '#8a6e9e', '#8a6e9e', '#8a6e9e'),
      7 => array('#92538c', '#92538c', '#92538c', '#92538c'),
      8 => array('#2e6093', '#2e6093', '#2e6093', '#2e6093'),
      9 => array('#3a5988', '#3a5988', '#3a5988', '#3a5988'),
     10 => array('#393e64', '#393e64', '#393e64', '#393e64'),
    );

    public function __construct() {
        $this->table                      = Database::get_main_table(TABLE_MAIN_SKILL);
        $this->table_skill_rel_gradebook  = Database::get_main_table(TABLE_MAIN_SKILL_REL_GRADEBOOK);
        $this->table_skill_rel_user       = Database::get_main_table(TABLE_MAIN_SKILL_REL_USER);
        $this->table_course               = Database::get_main_table(TABLE_MAIN_COURSE);
        $this->table_skill_rel_skill      = Database::get_main_table(TABLE_MAIN_SKILL_REL_SKILL);
        $this->table_gradebook            = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
    }

    function get_skill_info($id) {
        $skill_rel_skill = new SkillRelSkill();
        $skill_info = $this->get($id);
        if (!empty($skill_info)) {
            $skill_info['extra']        = $skill_rel_skill->get_skill_info($id);
            $skill_info['gradebooks']   = self::get_gradebooks_by_skill($id);
        }
        return $skill_info;
    }

    function get_skills_info($skill_list) {
        $skill_list = array_map('intval', $skill_list);
        $skill_list = implode("', '", $skill_list);

        $sql = "SELECT * FROM {$this->table}  WHERE id IN ('$skill_list') ";

        $result = Database::query($sql);
        $users  = Database::store_result($result, 'ASSOC');
        return $users;
    }

    function get_all($load_user_data = false, $user_id = false, $id = null, $parent_id = null) {
        $id_condition = '';
        
        if (isset($id) && !empty($id)) {
            $id = intval($id);
            $id_condition = " WHERE s.id = $id";
        }

        if (isset($parent_id) && !empty($parent_id)) {
            $parent_id = intval($parent_id);
            if (empty($id_condition)) {
                $id_condition = "WHERE ss.parent_id = $parent_id";
            } else {
                $id_condition = " AND ss.parent_id = $parent_id";
            }
        }

        $sql = "SELECT s.id, s.name, s.description, ss.parent_id, ss.relation_type 
                FROM {$this->table} s INNER JOIN {$this->table_skill_rel_skill} ss ON (s.id = ss.skill_id) $id_condition
                ORDER BY ss.id, ss.parent_id";

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

        if ($load_user_data && $user_id) {
            $passed_skills = $this->get_user_skills($user_id);
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

    function get_children($skill_id, $load_user_data = false) {
        $skill_rel_skill = new SkillRelSkill();
        if ($load_user_data) {
            $user_id = api_get_user_id();
            $skills = $skill_rel_skill->get_children($skill_id, true, $user_id);
        } else {
            $skills = $skill_rel_skill->get_children($skill_id);
        }
        return $skills;
    }
    
    function get_all_children($skill_id) {
        $skill_rel_skill = new SkillRelSkill();
        
        $children = $skill_rel_skill->get_children($skill_id);
       
        foreach ($children as $child) {             
            $sub_children = $this->get_all_children($child['skill_id']);            
        }
        if (!empty($sub_children)) {
            $children = array_merge($children, $sub_children);
        }        
        return $children;
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
            $skill_info2 = $skill_rel_skill->get_skill_info($skill['skill_id']);
            $skill['data']['parent_id'] = $skill_info2['parent_id'];
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
            return $skill_id;
        }
        return null;
    }

    public function edit($params) {
        if (!isset($params['parent_id'])) {
            $params['parent_id'] = 1;
        }
        $skill_rel_skill     = new SkillRelSkill();
        $skill_rel_gradebook = new SkillRelGradebook();

        //Saving name, description

        $this->update($params);
        $skill_id = $params['id'];

        if ($skill_id) {
            //Saving skill_rel_skill (parent_id, relation_type)
            $attributes = array(
                            'skill_id'      => $skill_id,
                            'parent_id'     => $params['parent_id'],
                            'relation_type' => $params['relation_type'],
                            //'level'         => $params['level'],
            );
            $skill_rel_skill->update_by_skill($attributes);

            $skill_rel_gradebook->update_gradebooks_by_skill($skill_id, $params['gradebook_id']);
            return $skill_id;
        }
        return null;
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

    /**
    * Get user's skills
    *
    * @param int $userId User's id
    */

    public function get_user_skills($user_id, $get_skill_data = false) {
        $user_id = intval($user_id);
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

    public function get_skills_tree($user_id = null, $skill_id = null, $return_flat_array = false, $add_root = false) {
        if (isset($user_id) && !empty($user_id)) {
            $skills = $this->get_all(true, $user_id, null, $skill_id);
        } else {
            $skills = $this->get_all(false, false, null, $skill_id);
        }
        
        //Show 1 item
        if ($add_root) {
            if (!empty($skill_id)) {
                $skills[1] = array('id' => '1', 'name' => get_lang('Root'), 'parent_id' => '0');
                $skill_info = $this->get_skill_info($skill_id);
                $skills[$skill_id] = $skill_info;
                $skills[$skill_id]['parent_id'] =  $skill_info['extra']['parent_id'];
            }
        }
                
        $refs = array();
        $skills_tree = null;

        // Create references for all nodes
        $flat_array = array();
        
        $css_attributes = array('fill' => 'red');
        
        $family = array();
        if (!empty($skills)) {
            foreach ($skills as &$skill) {
                
                if ($skill['parent_id'] == 0) {
                    $skill['parent_id'] = 'root';
                }
                
                if ($skill['parent_id'] == 1) {
                    $family[$skill['id']] = $this->get_all_children($skill['id']);        
                }
                
                $skill['data'] = array('parent_id' => $skill['parent_id']); // because except main keys (id, name, children) others keys are not saved while in the space tree
                
                $skill['data']['achieved'] = false;
                
                if ($user_id) {
                    $css_attributes = array('fill' => 'green');
                    $skill['data']['achieved'] = $this->user_has_skill($user_id, $skill['id']);                    
                }
                
                $skill['data']['skill_has_gradebook'] = false;
                
                if (isset($skill['gradebooks']) && !empty($skill['gradebooks'])) {
                    $skill['data']['skill_has_gradebook'] = true;
                }
                
                $skill['data']['css_attributes'] = $css_attributes;
                
                $refs[$skill['id']] = &$skill;
                $flat_array[$skill['id']] =  &$skill;
            }
            
            $family_id = 1;
            $new_family_array = array();
            foreach ($family as $main_family_id => $family_items) {                
                if (!empty($family_items)) {
                    foreach ($family_items as $item) {
                        $new_family_array[$item['skill_id']] = $family_id;
                    }
                }
                $new_family_array[$main_family_id] = $family_id;                
                $family_id++;
            }
                        
            // Moving node to the children index of their parents
            foreach ($skills as $skillInd => &$skill) {                
                $skill['data']['family_id'] = $new_family_array[$skill['id']];
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
        
        if ($return_flat_array) {
            return $flat_array;
        }
        unset($skills);
        return $skills_tree;
    }
    
    /**
     * Get skills tree as a simplified JSON structure
     * 
     */
    public function get_skills_tree_json($user_id = null, $skill_id = null, $return_flat_array = false, $main_depth = 2) {        
        $tree = $this->get_skills_tree($user_id, $skill_id, $return_flat_array, true);        
        
        $simple_tree = array();
        if (!empty($tree['children'])) {
            foreach ($tree['children'] as $element) {
                $simple_tree[] = array( 'name'      => $element['name'], 
                                        'children'  => $this->get_skill_json($element['children'], 1, $main_depth),                                        
                                        );
            }
        }        
        return json_encode($simple_tree[0]['children']);
    }
    
    /**
     * Get JSON element
     */
    public function get_skill_json($subtree, $depth = 1, $max_depth = 2) {
        $simple_sub_tree = array();
        if (is_array($subtree)) {
            $counter = 1;            
            foreach ($subtree as $elem) {
                $tmp = array();
                $tmp['name'] = $elem['name'];
                $tmp['id'] = $elem['id'];

                if (is_array($elem['children'])) {
                    $tmp['children'] = $this->get_skill_json($elem['children'], $depth+1, $max_depth);                                        
                } else {
                    $tmp['colour'] = $this->colours[$depth][rand(0,3)];
                }
                if ($depth > $max_depth) {
                    continue;
                }
                                
                $tmp['depth'] = $depth;
                $tmp['counter'] = $counter;
                $counter++;
                
                if (isset($elem['data']) && is_array($elem['data'])) {
                    foreach ($elem['data'] as $key => $item) {
                        $tmp[$key] = $item;
                    }
                }                
                $simple_sub_tree[] = $tmp;
            }
            return $simple_sub_tree;
        }
        return null;
    }
}
