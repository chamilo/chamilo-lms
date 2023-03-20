<?php

/* For licensing terms, see /license.txt */

class SkillProfileModel extends Model
{
    public $columns = ['id', 'name', 'description'];

    public function __construct()
    {
        $this->table = Database::get_main_table(TABLE_MAIN_SKILL_PROFILE);
        $this->table_rel_profile = Database::get_main_table(TABLE_MAIN_SKILL_REL_PROFILE);
    }

    /**
     * @return array
     */
    public function getProfiles()
    {
        $sql = "SELECT * FROM $this->table p
                INNER JOIN $this->table_rel_profile sp
                ON (p.id = sp.profile_id) ";
        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    /**
     * This function is for editing profile info from profile_id.
     *
     * @param int    $profileId
     * @param string $name
     * @param string $description
     *
     * @return bool
     */
    public function updateProfileInfo($profileId, $name, $description)
    {
        $profileId = (int) $profileId;

        if (empty($profileId)) {
            return false;
        }

        $name = Database::escape_string($name);
        $description = Database::escape_string($description);

        $sql = "UPDATE $this->table SET
                    name = '$name',
                    description = '$description'
                WHERE id = $profileId ";
        Database::query($sql);

        return true;
    }

    /**
     * Call the save method of the parent class and the SkillRelProfile object.
     *
     * @param array $params
     * @param bool  $showQuery Whether to show the query in parent save() method
     *
     * @return mixed Profile ID or false if incomplete params
     */
    public function save($params, $showQuery = false)
    {
        if (!empty($params)) {
            $profile_id = parent::save($params, $showQuery);
            if ($profile_id) {
                $skill_rel_profile = new SkillRelProfileModel();
                if (isset($params['skills'])) {
                    foreach ($params['skills'] as $skill_id) {
                        $attributes = [
                            'skill_id' => $skill_id,
                            'profile_id' => $profile_id,
                        ];
                        $skill_rel_profile->save($attributes);
                    }
                }

                return $profile_id;
            }
        }

        return false;
    }

    /**
     * Delete a skill profile.
     *
     * @param int $id The skill profile id
     *
     * @return bool Whether delete a skill profile
     */
    public function delete($id)
    {
        Database::delete(
            $this->table_rel_profile,
            [
                'profile_id' => $id,
            ]
        );

        return parent::delete($id);
    }
}
