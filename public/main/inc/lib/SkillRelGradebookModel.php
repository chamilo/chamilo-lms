<?php

/* For licensing terms, see /license.txt */

class SkillRelGradebookModel extends Model
{
    public $columns = ['id', 'gradebook_id', 'skill_id'];

    public function __construct()
    {
        $this->table = Database::get_main_table(TABLE_MAIN_SKILL_REL_GRADEBOOK);
    }

    /**
     * @param int $gradebookId
     * @param int $skillId
     *
     * @return bool
     */
    public function existsGradeBookSkill($gradebookId, $skillId)
    {
        $result = $this->find(
            'all',
            [
                'where' => [
                    'gradebook_id = ? AND skill_id = ?' => [
                        $gradebookId,
                        $skillId,
                    ],
                ],
            ]
        );
        if (!empty($result)) {
            return true;
        }

        return false;
    }

    /**
     * Gets an element.
     */
    public function getSkillInfo($skill_id, $gradebookId)
    {
        if (empty($skill_id)) {
            return [];
        }
        $result = Database::select(
            '*',
            $this->table,
            [
                'where' => [
                    'skill_id = ? AND gradebook_id = ? ' => [
                        $skill_id,
                        $gradebookId,
                    ],
                ],
            ],
            'first'
        );

        return $result;
    }

    /**
     * @param int   $skill_id
     * @param array $gradebook_list
     */
    public function updateGradeBookListBySkill($skill_id, $gradebook_list)
    {
        $original_gradebook_list = $this->find(
            'all',
            ['where' => ['skill_id = ?' => [$skill_id]]]
        );
        $gradebooks_to_remove = [];
        $gradebooks_to_add = [];
        $original_gradebook_list_ids = [];

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
                $attributes = [
                    'skill_id' => $skill_id,
                    'gradebook_id' => $gradebook_id,
                ];
                $this->save($attributes);
            }
        }
    }

    /**
     * @param array $params
     *
     * @return bool|void
     */
    public function updateBySkill($params)
    {
        $skillInfo = $this->existsGradeBookSkill(
            $params['gradebook_id'],
            $params['skill_id']
        );

        if ($skillInfo) {
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
