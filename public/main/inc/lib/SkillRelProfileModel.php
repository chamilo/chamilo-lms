<?php

/* For licensing terms, see /license.txt */

class SkillRelProfileModel extends Model
{
    public $columns = ['id', 'skill_id', 'profile_id'];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->table = Database::get_main_table(TABLE_MAIN_SKILL_REL_PROFILE);
        $this->tableProfile = Database::get_main_table(TABLE_MAIN_SKILL_PROFILE);
    }

    /**
     * @param int $profileId
     *
     * @return array
     */
    public function getSkillsByProfile($profileId)
    {
        $profileId = (int) $profileId;
        $skills = $this->get_all(['where' => ['profile_id = ? ' => $profileId]]);
        $return = [];
        if (!empty($skills)) {
            foreach ($skills as $skill_data) {
                $return[] = $skill_data['skill_id'];
            }
        }

        return $return;
    }

    /**
     * This function is for getting profile info from profile_id.
     *
     * @param int $profileId
     *
     * @return array
     */
    public function getProfileInfo($profileId)
    {
        $profileId = (int) $profileId;
        $sql = "SELECT * FROM $this->table p
                INNER JOIN $this->tableProfile pr
                ON (pr.id = p.profile_id)
                WHERE p.profile_id = ".$profileId;
        $result = Database::query($sql);
        $profileData = Database::fetch_array($result, 'ASSOC');

        return $profileData;
    }
}
