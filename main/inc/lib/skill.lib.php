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
            foreach($passed_skills as $done_skill) {
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
        if (isset($id)) {
            $id = intval($id);
            $id_condition = " WHERE id = $id";
        }

        if (isset($parent_id)) {
            $parent_id = intval($parent_id);
            if (empty($id_condition)) {
                $id_condition = "WHERE parent_id = $parent_id";
            } else {
                $id_condition = " AND parent_id = $parent_id";
            }

        }

        $sql = "SELECT id, name, description, parent_id, relation_type
                FROM {$this->table} s INNER JOIN {$this->table_skill_rel_skill} ss ON (s.id = ss.skill_id)
                $id_condition";

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

    public function get_skills_tree($user_id = null, $return_flat_array = false) {
        if (isset($user_id) && !empty($user_id)) {
            $skills = $this->get_all(true, $user_id);
        } else {
            $skills = $this->get_all();
        }

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