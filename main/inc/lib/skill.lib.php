<?php
/* For licensing terms, see /license.txt */

use Chamilo\UserBundle\Entity\User;

/**
 * Class SkillProfile
 * @package chamilo.library
 */
class SkillProfile extends Model
{
    public $columns = array('id', 'name', 'description');

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->table = Database::get_main_table(TABLE_MAIN_SKILL_PROFILE);
        $this->table_rel_profile = Database::get_main_table(TABLE_MAIN_SKILL_REL_PROFILE);
    }

    /**
     * @return array
     */
    public function get_profiles()
    {
        $sql = "SELECT * FROM $this->table p
                INNER JOIN $this->table_rel_profile sp
                ON(p.id = sp.profile_id) ";
        $result   = Database::query($sql);
        $profiles = Database::store_result($result, 'ASSOC');

        return $profiles;
    }

    /**
    * This function is for editing profile info from profile_id.
    * @param int    $profileId
    * @param string $name
    * @param string $description
    */
    public function updateProfileInfo($profileId, $name, $description)
    {
        $profileId = intval($profileId);
        $sql = "UPDATE $this->table SET
                    name = '$name',
                    description = '$description'
                WHERE id = $profileId ";
        $result = Database::query($sql);

        return $result;
    }

    /**
     * Call the save method of the parent class and the SkillRelProfile object
     * @param array Params
     * @param bool Whether to show the query in parent save() method
     * @return mixed Profile ID or false if incomplete params
     */
    public function save($params, $show_query = false)
    {
        if (!empty($params)) {
            $profile_id = parent::save($params, $show_query);
            if ($profile_id) {
                $skill_rel_profile = new SkillRelProfile();
                if (isset($params['skills'])) {
                    foreach ($params['skills'] as $skill_id) {
                        $attributes = array(
                            'skill_id' => $skill_id,
                            'profile_id' => $profile_id,
                        );
                        $skill_rel_profile->save($attributes);
                    }
                }
                return $profile_id;
            }
        }

        return false;
    }

    /**
     * Delete a skill profile
     * @param int $id The skill profile id
     * @return boolean Whether delete a skill profile
     */
    public function delete($id)
    {
        Database::delete(
            $this->table_rel_profile,
            array(
                'profile_id' => $id
            )
        );

        return parent::delete($id);
    }
}

/**
 * Class SkillRelProfile
 */
class SkillRelProfile extends Model
{
    public $columns = array('id', 'skill_id', 'profile_id');

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->table = Database::get_main_table(TABLE_MAIN_SKILL_REL_PROFILE);
        $this->tableProfile = Database::get_main_table(TABLE_MAIN_SKILL_PROFILE);
    }

    /**
     * @param int $profileId
     * @return array
     */
    public function get_skills_by_profile($profileId)
    {
        $profileId = intval($profileId);
        $skills = $this->get_all(array('where' => array('profile_id = ? ' => $profileId)));
        $return = array();
        if (!empty($skills)) {
            foreach ($skills as $skill_data) {
                $return[] = $skill_data['skill_id'];
            }
        }

        return $return;
    }

    /**
    * This function is for getting profile info from profile_id.
    * @param int $profileId
    */

    public function getProfileInfo($profileId)
    {
        $sql = "SELECT * FROM $this->table p
                INNER JOIN $this->tableProfile pr
                ON (pr.id = p.profile_id)
                WHERE p.profile_id = ".intval($profileId);
        $result = Database::query($sql);
        $profileData = Database::fetch_array($result, 'ASSOC');

        return $profileData;
    }
}

/**
 * Class SkillRelSkill
 */
class SkillRelSkill extends Model
{
    public $columns = array('skill_id', 'parent_id', 'relation_type', 'level');

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->table = Database::get_main_table(TABLE_MAIN_SKILL_REL_SKILL);
    }

    /**
     * Gets an element
     * @param int $id
     * @return array
     */
    public function get_skill_info($id)
    {
        if (empty($id)) {
            return array();
        }
        $result = Database::select(
            '*',
            $this->table,
            array('where' => array('skill_id = ?' => intval($id))),
            'first'
        );

        return $result;
    }

    /**
     * @param int $skill_id
     * @param bool $add_child_info
     * @return array
     */
    public function get_skill_parents($skill_id, $add_child_info = true)
    {
        $skill_id = intval($skill_id);
        $sql = 'SELECT child.* FROM '.$this->table.' child
                LEFT JOIN '.$this->table.' parent
                ON child.parent_id = parent.skill_id
                WHERE child.skill_id = '.$skill_id.' ';
        $result = Database::query($sql);
        $skill = Database::store_result($result, 'ASSOC');
        $skill = isset($skill[0]) ? $skill[0] : null;

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

    /**
     * @param int $skill_id
     * @return array
     */
    public function get_direct_parents($skill_id)
    {
        $skill_id = intval($skill_id);
        $sql = 'SELECT parent_id as skill_id 
                FROM '.$this->table.'
                WHERE skill_id = '.$skill_id.' ';
        $result = Database::query($sql);
        $skill = Database::store_result($result, 'ASSOC');
        $skill = isset($skill[0]) ? $skill[0] : null;
        $parents = array();
        if (!empty($skill)) {
            $parents[] = $skill;
        }

        return $parents;
    }

    /**
     * @param int $skill_id
     * @param bool $load_user_data
     * @param bool $user_id
     * @return array
     */
    public function get_children($skill_id, $load_user_data = false, $user_id = false)
    {
        $skills = $this->find('all', array('where' => array('parent_id = ? ' => $skill_id)));
        $skill_obj = new Skill();
        $skill_rel_user = new SkillRelUser();

        if ($load_user_data) {
            $passed_skills = $skill_rel_user->get_user_skills($user_id);
            $done_skills   = array();
            foreach ($passed_skills as $done_skill) {
                $done_skills[] = $done_skill['skill_id'];
            }
        }

        if (!empty($skills)) {
            foreach ($skills as &$skill) {
                $skill['data'] = $skill_obj->get($skill['skill_id']);
                if (isset($skill['data']) && !empty($skill['data'])) {
                    if (!empty($done_skills)) {
                        $skill['data']['passed'] = 0;
                        if (in_array($skill['skill_id'], $done_skills)) {
                            $skill['data']['passed'] = 1;
                        }
                    }
                } else {
                    $skill = null;
                }
            }
        }

        return $skills;
    }

    /**
     * @param array $params
     * @return bool
     */
    public function update_by_skill($params)
    {
        $result = Database::update(
            $this->table,
            $params,
            array('skill_id = ? ' => $params['skill_id'])
        );
        if ($result) {
            return true;
        }

        return false;
    }

    /**
     * @param int $skill_id
     * @param int $parent_id
     * @return bool
     */
    public function relation_exists($skill_id, $parent_id)
    {
        $result = $this->find(
            'all',
            array(
                'where' => array(
                    'skill_id = ? AND parent_id = ?' => array(
                        $skill_id,
                        $parent_id
                    )
                )
            )
        );

        if (!empty($result)) {
            return true;
        }
        return false;
    }
}

/**
 * Class SkillRelGradebook
 */
class SkillRelGradebook extends Model
{
    public $columns = array('id', 'gradebook_id', 'skill_id');

    public function __construct()
    {
        $this->table = Database::get_main_table(TABLE_MAIN_SKILL_REL_GRADEBOOK);
    }

    /**
     * @param int $gradebook_id
     * @param int $skill_id
     * @return bool
     */
    public function exists_gradebook_skill($gradebook_id, $skill_id)
    {
        $result = $this->find(
            'all',
            array('where' => array('gradebook_id = ? AND skill_id = ?' => array($gradebook_id, $skill_id)))
        );
        if (!empty($result)) {
            return true;
        }
        return false;
    }

    /**
     * Gets an element
     */
    public function get_skill_info($skill_id, $gradebook_id)
    {
        if (empty($skill_id)) {
            return array();
        }
        $result = Database::select(
            '*',
            $this->table,
            array('where' => array('skill_id = ? AND gradebook_id = ? ' => array($skill_id, $gradebook_id))),
            'first'
        );
        return $result;
    }

    /**
     * @param int $skill_id
     * @param array $gradebook_list
     */
    public function update_gradebooks_by_skill($skill_id, $gradebook_list)
    {
        $original_gradebook_list = $this->find(
            'all',
            array('where' => array('skill_id = ?' => array($skill_id)))
        );
        $gradebooks_to_remove = array();
        $gradebooks_to_add = array();
        $original_gradebook_list_ids = array();

        if (!empty($original_gradebook_list)) {
            foreach ($original_gradebook_list as $gradebook) {
                if (!in_array($gradebook['gradebook_id'], $gradebook_list)) {
                    $gradebooks_to_remove[] = $gradebook['id'];
                }
            }
            foreach ($original_gradebook_list as $gradebook_item) {
                $original_gradebook_list_ids[] = $gradebook_item['gradebook_id'];
            }
        }

        if (!empty($gradebook_list)) {
            foreach ($gradebook_list as $gradebook_id) {
                if (!in_array($gradebook_id, $original_gradebook_list_ids)) {
                    $gradebooks_to_add[] = $gradebook_id;
                }
            }
        }

        if (!empty($gradebooks_to_remove)) {
            foreach ($gradebooks_to_remove as $id) {
                $this->delete($id);
            }
        }

        if (!empty($gradebooks_to_add)) {
            foreach ($gradebooks_to_add as $gradebook_id) {
                $attributes = array('skill_id' => $skill_id, 'gradebook_id' => $gradebook_id);
                $this->save($attributes);
            }
        }
    }

    /**
     * @param array $params
     * @return bool|void
     */
    public function update_by_skill($params)
    {
        $skill_info = $this->exists_gradebook_skill(
            $params['gradebook_id'],
            $params['skill_id']
        );

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
 * Class SkillRelUser
 */
class SkillRelUser extends Model
{
    public $columns = array(
        'id',
        'user_id',
        'skill_id',
        'acquired_skill_at',
        'assigned_by',
        'course_id',
        'session_id',
    );

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->table = Database::get_main_table(TABLE_MAIN_SKILL_REL_USER);
    }

    /**
     * @param array $skill_list
     * @return array
     */
    public function get_user_by_skills($skill_list)
    {
        $users = array();
        if (!empty($skill_list)) {
            $skill_list = array_map('intval', $skill_list);
            $skill_list = implode("', '", $skill_list);

            $sql = "SELECT user_id FROM {$this->table}
                    WHERE skill_id IN ('$skill_list') ";

            $result = Database::query($sql);
            $users  = Database::store_result($result, 'ASSOC');
        }
        return $users;
    }

    /**
     * Get the achieved skills for the user
     * @param int $userId
     * @param int $courseId Optional. The course id
     * @param int $sessionId Optional. The session id
     * @return array The skill list. Otherwise return false
     */
    public function get_user_skills($userId, $courseId = 0, $sessionId = 0)
    {
        if (empty($userId)) {
            return array();
        }

        $courseId = intval($courseId);
        $sessionId = $sessionId ? intval($sessionId) : null;

        $whereConditions = array(
            'user_id = ? ' => intval($userId)
        );

        if ($courseId > 0) {
            $whereConditions['AND course_id = ? '] = $courseId;
            $whereConditions['AND session_id = ?'] = $sessionId;
        }

        $result = Database::select(
            'skill_id',
            $this->table,
            array(
                'where' => $whereConditions
            ),
            'all'
        );
        return $result;
    }

    /**
     * Get the relation data between user and skill
     * @param int $userId The user id
     * @param int $skillId The skill id
     * @param int $courseId The course id
     * @param int $sessionId Optional. The session id
     * @return array The relation data. Otherwise return false
     */
    public function getByUserAndSkill($userId, $skillId, $courseId, $sessionId = 0)
    {
        $where = array(
            'user_id = ? AND skill_id = ? AND course_id = ? AND session_id = ?' => array(
                intval($userId),
                intval($skillId),
                intval($courseId),
                $sessionId ? intval($sessionId) : null
            )
        );

        return Database::select('*', $this->table, array(
            'where' => $where
        ), 'first');
    }

}

/**
 * Class Skill
 */
class Skill extends Model
{
    public $columns = array('id', 'name', 'description', 'access_url_id', 'short_code', 'icon', 'criteria');
    public $required = array('name');

    /** Array of colours by depth, for the coffee wheel. Each depth has 4 col */
    /*var $colours = array(
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
    );*/

    public function __construct()
    {
        $this->table = Database::get_main_table(TABLE_MAIN_SKILL);
        $this->table_user = Database::get_main_table(TABLE_MAIN_USER);
        $this->table_skill_rel_gradebook = Database::get_main_table(TABLE_MAIN_SKILL_REL_GRADEBOOK);
        $this->table_skill_rel_user = Database::get_main_table(TABLE_MAIN_SKILL_REL_USER);
        $this->table_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $this->table_skill_rel_skill = Database::get_main_table(TABLE_MAIN_SKILL_REL_SKILL);
        $this->table_gradebook = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $this->sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);
    }

    /**
     * Gets an element
     * @param int $id
     *
     * @return array|mixed
     */
    public function get($id)
    {
        $result = parent::get($id);
        $result['web_icon_path'] = api_get_path(WEB_UPLOAD_PATH).'badges/'.$result['icon'];

        return $result;
    }

    /**
     * @param int $id
     * @return array
     */
    public function get_skill_info($id)
    {
        $skill_rel_skill = new SkillRelSkill();
        $skill_info = $this->get($id);
        if (!empty($skill_info)) {
            $skill_info['extra']      = $skill_rel_skill->get_skill_info($id);
            $skill_info['gradebooks'] = self::get_gradebooks_by_skill($id);
        }
        return $skill_info;
    }

    /**
     * @param array $skill_list
     * @return array
     */
    public function get_skills_info($skill_list)
    {
        $skill_list = array_map('intval', $skill_list);
        $skill_list = implode("', '", $skill_list);

        $sql = "SELECT * FROM {$this->table} WHERE id IN ('$skill_list') ";

        $result = Database::query($sql);
        $users  = Database::store_result($result, 'ASSOC');

        foreach ($users as &$user) {
            if (!$user['icon']) {
                continue;
            }

            $user['icon_small'] = sprintf("badges/%s-small.png", sha1($user['name']));
        }

        return $users;
    }

    /**
     * @param bool $load_user_data
     * @param bool $user_id
     * @param int $id
     * @param int $parent_id
     * @return array
     */
    public function get_all($load_user_data = false, $user_id = false, $id = null, $parent_id = null)
    {
        $id_condition = '';
        if (!empty($id)) {
            $id = intval($id);
            $id_condition = " WHERE s.id = $id";
        }

        if (!empty($parent_id)) {
            $parent_id = intval($parent_id);
            if (empty($id_condition)) {
                $id_condition = " WHERE ss.parent_id = $parent_id";
            } else {
                $id_condition = " AND ss.parent_id = $parent_id";
            }
        }

        $sql = "SELECT
                    s.id,
                    s.name,
                    s.description,
                    ss.parent_id,
                    ss.relation_type,
                    s.icon,
                    s.short_code,
                    s.status
                FROM {$this->table} s
                INNER JOIN {$this->table_skill_rel_skill} ss
                ON (s.id = ss.skill_id) $id_condition
                ORDER BY ss.id, ss.parent_id";

        $result = Database::query($sql);
        $skills = array();
        $webPath = api_get_path(WEB_UPLOAD_PATH);
        if (Database::num_rows($result)) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $skill_rel_skill = new SkillRelSkill();
                $parents = $skill_rel_skill->get_skill_parents($row['id']);
                $row['level'] = count($parents) - 1;
                $row['gradebooks'] = self::get_gradebooks_by_skill($row['id']);
                $row['web_icon_path'] = $webPath.'badges/'.$row['icon'];
                $skills[$row['id']] = $row;
            }
        }

        // Load all children of the parent_id
        if (!empty($skills) && !empty($parent_id)) {
            foreach ($skills as $skill) {
                $children = self::get_all($load_user_data, $user_id, $id, $skill['id']);
                if (!empty($children)) {
                    //$skills = array_merge($skills, $children);
                    $skills = $skills + $children;
                }
            }
        }
        return $skills;
    }

    /**
     * @param int $skill_id
     * @return array|resource
     */
    public function get_gradebooks_by_skill($skill_id)
    {
        $skill_id = intval($skill_id);
        $sql = "SELECT g.* FROM {$this->table_gradebook} g
                INNER JOIN {$this->table_skill_rel_gradebook} sg
                ON g.id = sg.gradebook_id
                WHERE sg.skill_id = $skill_id";
        $result = Database::query($sql);
        $result = Database::store_result($result, 'ASSOC');
        return $result;
    }

    /**
     * Get one level childrens
     *
     * @param int $skill_id
     * @param bool $load_user_data
     * @return array
     */
    public function get_children($skill_id, $load_user_data = false)
    {
        $skill_rel_skill = new SkillRelSkill();
        if ($load_user_data) {
            $user_id = api_get_user_id();
            $skills  = $skill_rel_skill->get_children($skill_id, true, $user_id);
        } else {
            $skills = $skill_rel_skill->get_children($skill_id);
        }
        return $skills;
    }

    /**
     * Get all children of the current node (recursive)
     * @param int $skillId
     * @return array
     */
    public function get_all_children($skillId)
    {
        $skill_rel_skill = new SkillRelSkill();
        $children = $skill_rel_skill->get_children($skillId);
        foreach ($children as $child) {
            $subChildren = $this->get_all_children($child['skill_id']);
        }

        if (!empty($subChildren)) {
            $children = array_merge($children, $subChildren);
        }

        return $children;
    }

    /**
     * Gets all parents from from the wanted skill
     */
    public function get_parents($skill_id)
    {
        $skill_rel_skill = new SkillRelSkill();
        $skills = $skill_rel_skill->get_skill_parents($skill_id, true);
        foreach ($skills as &$skill) {
            $skill['data'] = self::get($skill['skill_id']);
        }
        return $skills;
    }

    /**
     * All direct parents
     */
    public function get_direct_parents($skill_id)
    {
        $skill_rel_skill = new SkillRelSkill();
        $skills = $skill_rel_skill->get_direct_parents($skill_id, true);
        foreach ($skills as &$skill) {
            $skill['data'] = self::get($skill['skill_id']);
            $skill_info2 = $skill_rel_skill->get_skill_info($skill['skill_id']);
            $skill['data']['parent_id'] = $skill_info2['parent_id'];
        }
        return $skills;
    }

    /**
     * Adds a new skill
     * @param array $params
     * @return bool|null
     */
    public function add($params)
    {
        if (!isset($params['parent_id'])) {
            $params['parent_id'] = 1;
        }

        if (!is_array($params['parent_id'])) {
            $params['parent_id'] = array($params['parent_id']);
        }

        $skill_rel_skill = new SkillRelSkill();
        $skill_rel_gradebook = new SkillRelGradebook();

        // Saving name, description
        $skill_id = $this->save($params);
        if ($skill_id) {
            //Saving skill_rel_skill (parent_id, relation_type)
            foreach ($params['parent_id'] as $parent_id) {
                $relation_exists = $skill_rel_skill->relation_exists($skill_id, $parent_id);
                if (!$relation_exists) {
                    $attributes = array(
                        'skill_id'      => $skill_id,
                        'parent_id'     => $parent_id,
                        'relation_type' => (isset($params['relation_type'])?$params['relation_type']:0),
                        //'level'         => $params['level'],
                    );
                    $skill_rel_skill->save($attributes);
                }
            }

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

    /**
     * @param int $user_id
     * @param int $gradebook_id
     * @param int $courseId
     * @param int $sessionId
     */
    public function add_skill_to_user($user_id, $gradebook_id, $courseId = 0, $sessionId = 0)
    {
        $skill_gradebook = new SkillRelGradebook();
        $skill_rel_user  = new SkillRelUser();

        $skill_gradebooks = $skill_gradebook->get_all(
            array('where' => array('gradebook_id = ?' => $gradebook_id))
        );
        if (!empty($skill_gradebooks)) {
            foreach ($skill_gradebooks as $skill_gradebook) {
                $user_has_skill = $this->user_has_skill(
                    $user_id,
                    $skill_gradebook['skill_id'],
                    $courseId,
                    $sessionId
                );
                if (!$user_has_skill) {
                    $params = array(
                        'user_id' => $user_id,
                        'skill_id' => $skill_gradebook['skill_id'],
                        'acquired_skill_at' => api_get_utc_datetime(),
                        'course_id' => intval($courseId),
                        'session_id' => $sessionId ? intval($sessionId) : null
                    );

                    $skill_rel_user->save($params);
                }
            }
        }
    }

    /* Deletes a skill */
    public function delete($skill_id)
    {
        /*$params = array('skill_id' => $skill_id);

        $skill_rel_skill     = new SkillRelSkill();
        $skills = $skill_rel_skill->get_all(array('where'=>array('skill_id = ?' =>$skill_id)));

        $skill_rel_profile     = new SkillRelProfile();
        $skill_rel_gradebook = new SkillRelGradebook();
        $skill_rel_user     = new SkillRelUser();

        $this->delete($skill_id);

        $skill_rel_gradebook->delete($params);*/
    }

    /**
     * @param array $params
     * @return null
     */
    public function edit($params)
    {
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

            if (!is_array($params['parent_id'])) {
                $params['parent_id'] = array($params['parent_id']);
            }

            foreach ($params['parent_id'] as $parent_id) {
                $relation_exists = $skill_rel_skill->relation_exists($skill_id, $parent_id);
                if (!$relation_exists) {
                    $attributes = array(
                        'skill_id'      => $skill_id,
                        'parent_id'     => $parent_id,
                        'relation_type' => $params['relation_type'],
                        //'level'         => $params['level'],
                    );
                    $skill_rel_skill->update_by_skill($attributes);
                }
            }

            $skill_rel_gradebook->update_gradebooks_by_skill($skill_id, $params['gradebook_id']);
            return $skill_id;
        }
        return null;
    }

    /**
     * Get user's skills
     *
     * @param int $userId User's id
     * @param bool $get_skill_data
     */
    public function get_user_skills($user_id, $get_skill_data = false)
    {
        $user_id = intval($user_id);
        $sql = 'SELECT DISTINCT s.id, s.name, s.icon, u.id as issue
                FROM '.$this->table_skill_rel_user.' u
                INNER JOIN '.$this->table.' s
                ON u.skill_id = s.id
                WHERE user_id = '.$user_id;

        $result = Database::query($sql);
        $skills = Database::store_result($result, 'ASSOC');
        $uploadPath = api_get_path(WEB_UPLOAD_PATH);
        $clean_skill = array();
        if (!empty($skills)) {
            foreach ($skills as $skill) {
                if ($get_skill_data) {
                    $iconThumb = null;
                    $iconPath = null;

                    if (!empty($skill['icon'])) {
                        $iconThumb = sprintf(
                            "badges/%s-small.png",
                            sha1($skill['name'])
                        );

                        $iconPath = sprintf(
                            "badges/%s.png",
                            sha1($skill['name'])
                        );
                    }
                    $clean_skill[$skill['id']] = array_merge(
                        $skill,
                        array(
                            'web_icon_thumb_path' => $uploadPath.$iconThumb,
                            'web_icon_path' => $uploadPath.$iconPath
                        )
                    );
                } else {
                    $clean_skill[$skill['id']] = $skill['id'];
                }
            }
        }

        return $clean_skill;
    }

    /**
     * @param int $user_id
     * @param int $skill_id
     * @param bool $return_flat_array
     * @param bool $add_root
     * @return array|null
     */
    public function get_skills_tree(
        $user_id = null,
        $skill_id = null,
        $return_flat_array = false,
        $add_root = false
    ) {
        if ($skill_id == 1) {
            $skill_id = 0;
        }
        if (isset($user_id) && !empty($user_id)) {
            $skills = $this->get_all(true, $user_id, null, $skill_id);
        } else {
            $skills = $this->get_all(false, false, null, $skill_id);
        }

        $original_skill = $this->list = $skills;

        // Show 1 item
        if (!empty($skill_id)) {
            if ($add_root) {
                if (!empty($skill_id)) {
                    // Default root node
                    $skills[1] = array(
                        'id' => '1',
                        'name' => get_lang('Root'),
                        'parent_id' => '0'
                    );
                    $skill_info = $this->get_skill_info($skill_id);

                    // 2nd node
                    $skills[$skill_id] = $skill_info;
                    // Uncomment code below to hide the searched skill
                    $skills[$skill_id]['data']['parent_id'] = $skill_info['extra']['parent_id'];
                    $skills[$skill_id]['parent_id'] =  1;
                }
            }
        }

        $refs = array();
        $skills_tree = null;

        // Create references for all nodes
        $flat_array = array();
        $family = array();
        if (!empty($skills)) {
            foreach ($skills as &$skill) {
                if ($skill['parent_id'] == 0) {
                    $skill['parent_id'] = 'root';
                }

                // because except main keys (id, name, children) others keys
                // are not saved while in the space tree
                $skill['data'] = array('parent_id' => $skill['parent_id']);

                // If a short code was defined, send the short code to replace
                // skill name (to shorten the text in the wheel)
                if (
                    !empty($skill['short_code']) &&
                    api_get_setting('show_full_skill_name_on_skill_wheel') === 'false'
                ) {
                    $skill['data']['short_code'] = $skill['short_code'];
                }

                $skill['data']['name'] = $skill['name'];

                $skill['data']['status'] = $skill['status'];

                // In order to paint all members of a family with the same color
                if (empty($skill_id)) {
                    if ($skill['parent_id'] == 1) {
                        $family[$skill['id']] = $this->get_all_children($skill['id']);
                    }
                } else {
                    if ($skill['parent_id'] == $skill_id) {
                        $family[$skill['id']] = $this->get_all_children($skill['id']);
                    }

                    /*if ($skill_id == $skill['id']) {
                        $skill['parent_id'] = 1;
                    }*/
                }

                if (!isset($skill['data']['real_parent_id'])) {
                    $skill['data']['real_parent_id'] = $skill['parent_id'];
                }

                // User achieved the skill (depends in the gradebook with certification)
                $skill['data']['achieved'] = false;
                if ($user_id) {
                    $skill['data']['achieved'] = $this->user_has_skill($user_id, $skill['id']);
                }

                // Check if the skill has related gradebooks
                $skill['data']['skill_has_gradebook'] = false;
                if (isset($skill['gradebooks']) && !empty($skill['gradebooks'])) {
                    $skill['data']['skill_has_gradebook'] = true;
                }
                $refs[$skill['id']] = &$skill;
                $flat_array[$skill['id']] =  &$skill;
            }

            // Checking family value

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

            if (empty($original_skill)) {
                $refs['root']['children'][0] = $skills[1];
                $skills[$skill_id]['data']['family_id'] = 1;
                $refs['root']['children'][0]['children'][0] = $skills[$skill_id];
                $flat_array[$skill_id] =  $skills[$skill_id];
            } else {
                // Moving node to the children index of their parents

                foreach ($skills as $my_skill_id => &$skill) {
                    if (isset($new_family_array[$skill['id']])) {
                        $skill['data']['family_id'] = $new_family_array[$skill['id']];
                    }
                    $refs[$skill['parent_id']]['children'][] = &$skill;
                    $flat_array[$my_skill_id] =  $skill;
                }
            }

            $skills_tree = array(
                'name' => get_lang('SkillRootName'),
                'id' => 'root',
                'children' => $refs['root']['children'],
                'data' => array()
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
     * @param int user id
     * @param int skill id
     * @param bool return a flat array or not
     * @param int depth of the skills
     * @return json
     */
    public function get_skills_tree_json(
        $user_id = null,
        $skill_id = null,
        $return_flat_array = false,
        $main_depth = 2
    ) {
        $tree = $this->get_skills_tree(
            $user_id,
            $skill_id,
            $return_flat_array,
            true
        );
        $simple_tree = array();
        if (!empty($tree['children'])) {
            foreach ($tree['children'] as $element) {
                $children = [];
                if (isset($element['children'])) {
                    $children = $this->get_skill_json($element['children'], 1, $main_depth);
                }
                $simple_tree[] = array(
                    'name' => $element['name'],
                    'children' => $children
                );
            }
        }

        return json_encode($simple_tree[0]['children']);
    }

    /**
     * Get JSON element
     * @param array $subtree
     * @param int $depth
     * @param int $max_depth
     * @return array|null
     */
    public function get_skill_json($subtree, $depth = 1, $max_depth = 2)
    {
        $simple_sub_tree = array();
        if (is_array($subtree)) {
            $counter = 1;
            foreach ($subtree as $elem) {
                $tmp = array();
                $tmp['name'] = $elem['name'];
                $tmp['id'] = $elem['id'];
                $tmp['isSearched'] = self::isSearched($elem['id']);

                if (isset($elem['children']) && is_array($elem['children'])) {
                    $tmp['children'] = $this->get_skill_json($elem['children'], $depth + 1, $max_depth);
                } else {
                    //$tmp['colour'] = $this->colours[$depth][rand(0,3)];
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

    /**
     * @param int $user_id
     * @return bool
     */
    public function get_user_skill_ranking($user_id)
    {
        $user_id = intval($user_id);
        $sql = "SELECT count(skill_id) count FROM {$this->table} s
                INNER JOIN {$this->table_skill_rel_user} su
                ON (s.id = su.skill_id)
                WHERE user_id = $user_id";
        $result  = Database::query($sql);
        if (Database::num_rows($result)) {
            $result = Database::fetch_row($result);
            return $result[0];
        }
        return false;
    }

    /**
     * @param $start
     * @param $limit
     * @param $sidx
     * @param $sord
     * @param $where_condition
     * @return array
     */
    public function get_user_list_skill_ranking($start, $limit, $sidx, $sord, $where_condition)
    {
        $start = intval($start);
        $limit = intval($limit);
        /*  ORDER BY $sidx $sord */
        $sql = "SELECT *, @rownum:=@rownum+1 rank FROM (
                    SELECT u.user_id, firstname, lastname, count(username) skills_acquired
                    FROM {$this->table} s INNER JOIN {$this->table_skill_rel_user} su ON (s.id = su.skill_id)
                    INNER JOIN {$this->table_user} u ON u.user_id = su.user_id, (SELECT @rownum:=0) r
                    WHERE 1=1 $where_condition
                    GROUP BY username
                    ORDER BY skills_acquired desc
                    LIMIT $start , $limit)  AS T1, (SELECT @rownum:=0) r";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            return Database::store_result($result, 'ASSOC');
        }

        return array();
    }

    /**
     * @return int
     */
    public function get_user_list_skill_ranking_count()
    {
        $sql    = "SELECT count(*) FROM (
                        SELECT count(distinct 1)
                        FROM {$this->table} s
                        INNER JOIN {$this->table_skill_rel_user} su
                        ON (s.id = su.skill_id)
                        INNER JOIN {$this->table_user} u
                        ON u.user_id = su.user_id
                        GROUP BY username
                     ) as T1";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $result = Database::fetch_row($result);

            return $result[0];
        }

        return 0;
    }

    /**
     * @param string $courseCode
     * @return int
     */
    public function get_count_skills_by_course($courseCode)
    {
        $courseCode = Database::escape_string($courseCode);
        $sql = "SELECT count(skill_id) as count
                FROM {$this->table_gradebook} g
                INNER JOIN {$this->table_skill_rel_gradebook} sg
                ON g.id = sg.gradebook_id
                WHERE course_code = '$courseCode'";

        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $result = Database::fetch_row($result);
            return $result[0];
        }
        return 0;
    }

    /**
     * @param int $skill_id
     * @return array
     */
    public function get_courses_by_skill($skill_id)
    {
        $skill_id = intval($skill_id);
        $sql = "SELECT c.title, c.code
                FROM {$this->table_gradebook} g
                INNER JOIN {$this->table_skill_rel_gradebook} sg
                ON g.id = sg.gradebook_id
                INNER JOIN {$this->table_course} c
                ON c.code = g.course_code
                WHERE sg.skill_id = $skill_id
                AND (g.session_id IS NULL OR g.session_id = 0)";
        $result   = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    /**
     * Check if the user has the skill
     * @param int $userId The user id
     * @param int $skillId The skill id
     * @param int $courseId Optional. The course id
     * @param int $sessionId Optional. The session id
     * @return boolean Whether the user has the skill return true. Otherwise return false
     */
    public function user_has_skill($userId, $skillId, $courseId = 0, $sessionId = 0)
    {
        $courseId = intval($courseId);
        $sessionId = intval($sessionId);

        $whereConditions = array(
            'user_id = ? ' => intval($userId),
            'AND skill_id = ? ' => intval($skillId)
        );

        if ($courseId > 0) {
            $whereConditions['AND course_id = ? '] = $courseId;
            $whereConditions['AND session_id = ? '] = $sessionId ? $sessionId : null;
        }

        $result = Database::select(
            'COUNT(1) AS qty',
            $this->table_skill_rel_user,
            array(
                'where' => $whereConditions
            ),
            'first'
        );

        if ($result != false) {
            if ($result['qty'] > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a skill is searched
     * @param int $id The skill id
     * @return boolean Whether el skill is searched return true. Otherwise return false
     */
    public static function isSearched($id)
    {
        $id = intval($id);

        if (empty($id)) {
            return false;
        }

        $skillRelProfileTable = Database::get_main_table(TABLE_MAIN_SKILL_REL_PROFILE);

        $result = Database::select(
            'COUNT( DISTINCT `skill_id`) AS qty',
            $skillRelProfileTable,
            array(
                'where' => array(
                    'skill_id = ?' => $id
                )
            ),
            'first'
        );

        if ($result === false) {
            return false;
        }

        if ($result['qty'] > 0) {
            return true;
        }

        return false;
    }

    /**
     * Get the achieved skills by course
     * @param int $courseId The course id
     * @return array The skills list
     */
    public function listAchievedByCourse($courseId)
    {
        $courseId = intval($courseId);

        if ($courseId == 0) {
            return array();
        }

        $list = array();

        $sql = "SELECT
                    course.id c_id,
                    course.title c_name,
                    course.directory c_directory,
                    user.user_id,
                    user.lastname,
                    user.firstname,
                    user.username,
                    skill.id skill_id,
                    skill.name skill_name,
                    sru.acquired_skill_at
                FROM {$this->table_skill_rel_user} AS sru
                INNER JOIN {$this->table_course}
                ON sru.course_id = course.id
                INNER JOIN {$this->table_user}
                ON sru.user_id = user.user_id
                INNER JOIN {$this->table}
                ON sru.skill_id = skill.id
                WHERE course.id = $courseId";

        $result = Database::query($sql);

        while ($row = Database::fetch_assoc($result)) {
            $list[] = $row;
        }

        return $list;
    }

    /**
     * Get the users list who achieved a skill
     * @param int $skillId The skill id
     *
     * @return array The users list
     */
    public function listUsersWhoAchieved($skillId)
    {
        $skillId = intval($skillId);

        if ($skillId == 0) {
            return array();
        }

        $list = array();

        $sql = "SELECT
                    course.id c_id,
                    course.title c_name,
                    course.directory c_directory,
                    user.user_id,
                    user.lastname,
                    user.firstname,
                    user.username,
                    skill.id skill_id,
                    skill.name skill_name,
                    sru.acquired_skill_at
                FROM {$this->table_skill_rel_user} AS sru
                INNER JOIN {$this->table_course}
                ON sru.course_id = course.id
                INNER JOIN {$this->table_user}
                ON sru.user_id = user.user_id
                INNER JOIN {$this->table}
                ON sru.skill_id = skill.id
                WHERE skill.id = $skillId ";

        $result = Database::query($sql);
        while ($row = Database::fetch_assoc($result)) {
            $list[] = $row;
        }

        return $list;
    }

    /**
     * Get the session list where the user can achieve a skill
     * @param int $skillId The skill id
     * @return array
     */
    public function getSessionsBySkill($skillId)
    {
        $skillId = intval($skillId);

        $sql = "SELECT s.id, s.name
                FROM {$this->table_gradebook} g
                INNER JOIN {$this->table_skill_rel_gradebook} sg 
                ON g.id = sg.gradebook_id
                INNER JOIN {$this->sessionTable} s 
                ON g.session_id = s.id
                WHERE sg.skill_id = $skillId
                AND g.session_id > 0";

        $result   = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    /**
     * Check if the $fromUser can comment the $toUser skill issue
     * @param User $fromUser
     * @param User $toUser
     * @return boolean
     */
    public static function userCanAddFeedbackToUser($fromUser, $toUser)
    {
        if (api_is_platform_admin()) {
            return true;
        }

        $entityManager = Database::getManager();
        $userRepo = $entityManager->getRepository('ChamiloUserBundle:User');
        $fromUserStatus = $fromUser->getStatus();

        switch ($fromUserStatus) {
            case SESSIONADMIN:
                if (api_get_setting('allow_session_admins_to_manage_all_sessions') === 'true') {
                    if ($toUser->getCreatorId() === $fromUser->getId()) {
                        return true;
                    }
                }

                $sessionAdmins = $userRepo->getSessionAdmins($toUser);

                foreach ($sessionAdmins as $sessionAdmin) {
                    if ($sessionAdmin->getId() !== $fromUser->getId()) {
                        continue;
                    }

                    return true;
                }
                break;
            case STUDENT_BOSS:
                $studentBosses = $userRepo->getStudentBosses($toUser);

                foreach ($studentBosses as $studentBoss) {
                    if ($studentBoss->getId() !== $fromUser->getId()) {
                        continue;
                    }

                    return true;
                }
            case DRH:
                return UserManager::is_user_followed_by_drh($toUser->getId(), $fromUser->getId());
        }

        return false;
    }

}
