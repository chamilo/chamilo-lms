<?php

/* For licensing terms, see /license.txt */

class SkillRelSkillModel extends Model
{
    public $columns = ['skill_id', 'parent_id', 'relation_type', 'level'];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->table = Database::get_main_table(TABLE_MAIN_SKILL_REL_SKILL);
        $this->tableSkill = Database::get_main_table(TABLE_MAIN_SKILL);
    }

    /**
     * Gets an element.
     *
     * @param int $id
     *
     * @return array
     */
    public function getSkillInfo($id)
    {
        $id = (int) $id;

        if (empty($id)) {
            return [];
        }

        $result = Database::select(
            '*',
            $this->table,
            ['where' => ['skill_id = ?' => $id]],
            'first'
        );

        return $result;
    }

    /**
     * @param int  $skillId
     * @param bool $add_child_info
     *
     * @return array
     */
    public function getSkillParents($skillId, $add_child_info = true)
    {
        $skillId = (int) $skillId;
        $sql = 'SELECT child.* FROM '.$this->table.' child
                LEFT JOIN '.$this->table.' parent
                ON child.parent_id = parent.skill_id
                WHERE child.skill_id = '.$skillId.' ';
        $result = Database::query($sql);
        $skill = Database::store_result($result, 'ASSOC');
        $skill = isset($skill[0]) ? $skill[0] : null;

        $parents = [];
        if (!empty($skill)) {
            if (null != $skill['parent_id']) {
                $parents = self::getSkillParents($skill['parent_id']);
            }
            if ($add_child_info) {
                $parents[] = $skill;
            }
        }

        return $parents;
    }

    /**
     * @param int $skillId
     *
     * @return array
     */
    public function getDirectParents($skillId)
    {
        $skillId = (int) $skillId;
        $sql = 'SELECT parent_id as skill_id
                FROM '.$this->table.'
                WHERE skill_id = '.$skillId;
        $result = Database::query($sql);
        $skill = Database::store_result($result, 'ASSOC');
        $skill = isset($skill[0]) ? $skill[0] : null;
        $parents = [];
        if (!empty($skill)) {
            $parents[] = $skill;
        }

        return $parents;
    }

    /**
     * @param int  $skill_id
     * @param bool $load_user_data
     * @param bool $user_id
     *
     * @return array
     */
    public function getChildren(
        $skill_id,
        $load_user_data = false,
        $user_id = false,
        $order = ''
    ) {
        $skill_id = (int) $skill_id;
        $sql = 'SELECT parent.* FROM '.$this->tableSkill.' skill
                INNER JOIN '.$this->table.' parent
                ON parent.id = skill.id
                WHERE parent_id = '.$skill_id.'
                ORDER BY skill.name ASC';
        $result = Database::query($sql);
        $skills = Database::store_result($result, 'ASSOC');

        $skill_obj = new SkillModel();
        $skill_rel_user = new SkillRelUserModel();

        if ($load_user_data) {
            $passed_skills = $skill_rel_user->getUserSkills($user_id);
            $done_skills = [];
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
     *
     * @return bool
     */
    public function updateBySkill($params)
    {
        $result = Database::update(
            $this->table,
            $params,
            ['skill_id = ? ' => $params['skill_id']]
        );
        if ($result) {
            return true;
        }

        return false;
    }

    /**
     * @param int $skill_id
     * @param int $parent_id
     *
     * @return bool
     */
    public function relationExists($skill_id, $parent_id)
    {
        $result = $this->find(
            'all',
            [
                'where' => [
                    'skill_id = ? AND parent_id = ?' => [
                        $skill_id,
                        $parent_id,
                    ],
                ],
            ]
        );

        if (!empty($result)) {
            return true;
        }

        return false;
    }
}
