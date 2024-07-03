<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use Chamilo\CoreBundle\Entity\Skill as SkillEntity;
use Chamilo\CoreBundle\Entity\SkillRelUser as SkillRelUserEntity;
use Chamilo\SkillBundle\Entity\SkillRelCourse;
use Chamilo\SkillBundle\Entity\SkillRelItem;
use Chamilo\SkillBundle\Entity\SkillRelItemRelUser;
use Chamilo\UserBundle\Entity\User;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Vertex;

/**
 * Class SkillProfile.
 *
 * @todo break the file in different classes
 */
class SkillProfile extends Model
{
    public $columns = ['id', 'name', 'description'];

    /**
     * Constructor.
     */
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
        $profiles = Database::store_result($result, 'ASSOC');

        return $profiles;
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

        Database::update(
            $this->table,
            [
                'name' => html_filter($name),
                'description' => html_filter($description),
            ],
            ['id = ?' => $profileId]
        );

        return true;
    }

    /**
     * Call the save method of the parent class and the SkillRelProfile object.
     *
     * @param array $params
     * @param bool  $show_query Whether to show the query in parent save() method
     *
     * @return mixed Profile ID or false if incomplete params
     */
    public function save($params, $show_query = false)
    {
        if (!empty($params)) {
            $params['name'] = html_filter($params['name']);
            $params['description'] = html_filter($params['description']);

            $profile_id = parent::save($params, $show_query);
            if ($profile_id) {
                $skill_rel_profile = new SkillRelProfile();
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

/**
 * Class SkillRelProfile.
 */
class SkillRelProfile extends Model
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

/**
 * Class SkillRelSkill.
 */
class SkillRelSkill extends Model
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
            if ($skill['parent_id'] != null) {
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

        $skill_obj = new Skill();
        $skill_rel_user = new SkillRelUser();

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

/**
 * Class SkillRelGradebook.
 */
class SkillRelGradebook extends Model
{
    public $columns = ['id', 'gradebook_id', 'skill_id'];

    /**
     * SkillRelGradebook constructor.
     */
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

/**
 * Class SkillRelUser.
 */
class SkillRelUser extends Model
{
    public $columns = [
        'id',
        'user_id',
        'skill_id',
        'acquired_skill_at',
        'assigned_by',
        'course_id',
        'session_id',
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->table = Database::get_main_table(TABLE_MAIN_SKILL_REL_USER);
    }

    /**
     * @param array $skill_list
     *
     * @return array
     */
    public function getUserBySkills($skill_list)
    {
        $users = [];
        if (!empty($skill_list)) {
            $skill_list = array_map('intval', $skill_list);
            $skill_list = implode("', '", $skill_list);

            $sql = "SELECT user_id FROM {$this->table}
                    WHERE skill_id IN ('$skill_list') ";

            $result = Database::query($sql);
            $users = Database::store_result($result, 'ASSOC');
        }

        return $users;
    }

    /**
     * Get the achieved skills for the user.
     *
     * @param int $userId
     * @param int $courseId  Optional. The course id
     * @param int $sessionId Optional. The session id
     *
     * @return array The skill list. Otherwise return false
     */
    public function getUserSkills($userId, $courseId = 0, $sessionId = 0)
    {
        if (empty($userId)) {
            return [];
        }

        $courseId = (int) $courseId;
        $sessionId = $sessionId ? (int) $sessionId : null;
        $whereConditions = [
            'user_id = ? ' => (int) $userId,
        ];

        if ($sessionId > 0) {
            $whereConditions['AND course_id = ? '] = $courseId;
            $whereConditions['AND session_id = ? '] = $sessionId;
        } else {
            $whereConditions['AND course_id = ? AND session_id is NULL'] = $courseId;
        }

        $result = Database::select(
            'skill_id',
            $this->table,
            [
                'where' => $whereConditions,
            ],
            'all'
        );

        return $result;
    }

    /**
     * Get the relation data between user and skill.
     *
     * @param int $userId    The user id
     * @param int $skillId   The skill id
     * @param int $courseId  Optional. The course id
     * @param int $sessionId Optional. The session id
     *
     * @return array The relation data. Otherwise return false
     */
    public function getByUserAndSkill($userId, $skillId, $courseId = 0, $sessionId = 0)
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = %d AND skill_id = %d ";

        if ($courseId > 0) {
            $sql .= "AND course_id = %d ".api_get_session_condition($sessionId, true);
        }

        $sql = sprintf(
            $sql,
            $userId,
            $skillId,
            $courseId
        );

        $result = Database::query($sql);

        return Database::fetch_assoc($result);
    }

    /**
     * Delete a user skill by course.
     *
     * @param int $userId
     * @param int $courseId
     * @param int $sessionId
     */
    public function deleteUserSkill($userId, $courseId, $sessionId = 0)
    {
        $whereSession = ($sessionId ? " AND session_id = $sessionId" : " AND session_id IS NULL");
        $sql = "DELETE FROM {$this->table}
                WHERE
                      user_id = $userId AND
                      course_id = $courseId
                      $whereSession";

        Database::query($sql);
    }

    /**
     * Get the URL for the issue.
     *
     * @return string
     */
    public static function getIssueUrl(SkillRelUserEntity $skillIssue)
    {
        return api_get_path(WEB_PATH)."badge/{$skillIssue->getId()}";
    }

    /**
     * Get the URL for the All issues page.
     *
     * @return string
     */
    public static function getIssueUrlAll(SkillRelUserEntity $skillIssue)
    {
        return api_get_path(WEB_PATH)."skill/{$skillIssue->getSkill()->getId()}/user/{$skillIssue->getUser()->getId()}";
    }

    /**
     * Get the URL for the assertion.
     *
     * @return string
     */
    public static function getAssertionUrl(SkillRelUserEntity $skillIssue)
    {
        $url = api_get_path(WEB_CODE_PATH).'badge/assertion.php?';

        $url .= http_build_query([
            'user' => $skillIssue->getUser()->getId(),
            'skill' => $skillIssue->getSkill()->getId(),
            'course' => $skillIssue->getCourse() ? $skillIssue->getCourse()->getId() : 0,
            'session' => $skillIssue->getSession() ? $skillIssue->getSession()->getId() : 0,
        ]);

        return $url;
    }
}

/**
 * Class Skill.
 */
class Skill extends Model
{
    public $columns = [
        'id',
        'name',
        'description',
        'access_url_id',
        'short_code',
        'icon',
        'criteria',
    ];
    public $required = ['name'];

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
     * Gets an element.
     *
     * @param int $id
     *
     * @return array|mixed
     */
    public function get($id)
    {
        $result = parent::get($id);
        if (empty($result)) {
            return [];
        }

        $path = api_get_path(WEB_UPLOAD_PATH).'badges/';
        if (!empty($result['icon'])) {
            $iconSmall = sprintf(
                '%s-small.png',
                sha1($result['name'])
            );

            $iconBig = sprintf(
                '%s.png',
                sha1($result['name'])
            );

            $iconMini = $path.$iconSmall;
            $iconSmall = $path.$iconSmall;
            $iconBig = $path.$iconBig;
        } else {
            $iconMini = Display::returnIconPath('badges-default.png', ICON_SIZE_MEDIUM);
            $iconSmall = Display::returnIconPath('badges-default.png', ICON_SIZE_BIG);
            $iconBig = Display::returnIconPath('badges-default.png', ICON_SIZE_HUGE);
        }

        $result['icon_mini'] = $iconMini;
        $result['icon_small'] = $iconSmall;
        $result['icon_big'] = $iconBig;

        $result['img_mini'] = Display::img($iconBig, $result['name'], ['width' => ICON_SIZE_MEDIUM]);
        $result['img_big'] = Display::img($iconBig, $result['name']);
        $result['img_small'] = Display::img($iconSmall, $result['name']);
        $result['name'] = self::translateName($result['name']);
        $result['short_code'] = self::translateCode($result['short_code']);

        return $result;
    }

    /**
     * @param array  $skills
     * @param string $imageSize     mini|small|big
     * @param bool   $addDivWrapper
     *
     * @return string
     */
    public function processSkillList($skills, $imageSize = '', $addDivWrapper = true)
    {
        if (empty($skills)) {
            return '';
        }

        if (empty($imageSize)) {
            $imageSize = 'img_small';
        } else {
            $imageSize = "img_$imageSize";
        }

        $html = '';
        if ($addDivWrapper) {
            $html = '<div class="scrollbar-inner badges-sidebar">';
        }
        $html .= '<ul class="list-unstyled list-badges">';
        foreach ($skills as $skill) {
            if (isset($skill['data'])) {
                $skill = $skill['data'];
            }
            $html .= '<li class="thumbnail">';
            $item = $skill[$imageSize];
            $item .= '<div class="caption">
                        <p class="text-center">'.$skill['name'].'</p>
                      </div>';
            if (isset($skill['url'])) {
                $html .= Display::url($item, $skill['url'], ['target' => '_blank']);
            } else {
                $html .= $item;
            }
            $html .= '</li>';
        }
        $html .= '</ul>';

        if ($addDivWrapper) {
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * @param $skills
     * @param string $imageSize mini|small|big
     * @param string $style
     * @param bool   $showBadge
     * @param bool   $showTitle
     *
     * @return string
     */
    public function processSkillListSimple($skills, $imageSize = '', $style = '', $showBadge = true, $showTitle = true)
    {
        if (empty($skills)) {
            return '';
        }

        $isHierarchicalTable = api_get_configuration_value('table_of_hierarchical_skill_presentation');

        if (empty($imageSize)) {
            $imageSize = 'img_small';
        } else {
            $imageSize = "img_$imageSize";
        }

        $html = '';
        foreach ($skills as $skill) {
            if (isset($skill['data'])) {
                $skill = $skill['data'];
            }

            $item = '';
            if ($showBadge) {
                $item = '<div class="item">'.$skill[$imageSize].'</div>';
            }

            $name = '<div class="caption">'.$skill['name'].'</div>';
            if (!empty($skill['short_code'])) {
                $name = $skill['short_code'];
            }

            if (!$isHierarchicalTable) {
                //$item .= '<br />';
            }

            if ($showTitle) {
                $item .= $name;
            }

            if (isset($skill['url'])) {
                $html .= Display::url($item, $skill['url'], ['target' => '_blank', 'style' => $style]);
            } else {
                $html .= Display::url($item, '#', ['target' => '_blank', 'style' => $style]);
            }
        }

        return $html;
    }

    /**
     * @param int $id
     *
     * @return array
     */
    public function getSkillInfo($id)
    {
        $skillRelSkill = new SkillRelSkill();
        $skillInfo = $this->get($id);
        if (!empty($skillInfo)) {
            $skillInfo['extra'] = $skillRelSkill->getSkillInfo($id);
            $skillInfo['gradebooks'] = $this->getGradebooksBySkill($id);
        }

        return $skillInfo;
    }

    /**
     * @param array $skill_list
     *
     * @return array
     */
    public function getSkillsInfo($skill_list)
    {
        $skill_list = array_map('intval', $skill_list);
        $skill_list = implode("', '", $skill_list);

        $sql = "SELECT * FROM {$this->table}
                WHERE id IN ('$skill_list') ";

        $result = Database::query($sql);
        $skills = Database::store_result($result, 'ASSOC');

        foreach ($skills as &$skill) {
            if (!$skill['icon']) {
                continue;
            }

            $skill['icon_small'] = sprintf(
                'badges/%s-small.png',
                sha1($skill['name'])
            );
            $skill['name'] = self::translateName($skill['name']);
            $skill['short_code'] = self::translateCode($skill['short_code']);
        }

        return $skills;
    }

    /**
     * @param bool $load_user_data
     * @param bool $user_id
     * @param int  $id
     * @param int  $parent_id
     *
     * @return array
     */
    public function get_all(
        $load_user_data = false,
        $user_id = false,
        $id = null,
        $parent_id = null
    ) {
        $id_condition = '';
        if (!empty($id)) {
            $id = (int) $id;
            $id_condition = " WHERE s.id = $id";
        }

        if (!empty($parent_id)) {
            $parent_id = (int) $parent_id;
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
        $skills = [];
        $webPath = api_get_path(WEB_UPLOAD_PATH);
        if (Database::num_rows($result)) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $skillInfo = self::get($row['id']);

                $row['img_mini'] = $skillInfo['img_mini'];
                $row['img_big'] = $skillInfo['img_big'];
                $row['img_small'] = $skillInfo['img_small'];

                $row['name'] = self::translateName($row['name']);
                $row['short_code'] = self::translateCode($row['short_code']);
                $skillRelSkill = new SkillRelSkill();
                $parents = $skillRelSkill->getSkillParents($row['id']);
                $row['level'] = count($parents) - 1;
                $row['gradebooks'] = $this->getGradebooksBySkill($row['id']);
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
     *
     * @return array|resource
     */
    public function getGradebooksBySkill($skill_id)
    {
        $skill_id = (int) $skill_id;
        $sql = "SELECT g.* FROM {$this->table_gradebook} g
                INNER JOIN {$this->table_skill_rel_gradebook} sg
                ON g.id = sg.gradebook_id
                WHERE sg.skill_id = $skill_id";
        $result = Database::query($sql);
        $result = Database::store_result($result, 'ASSOC');

        return $result;
    }

    /**
     * Get one level children.
     *
     * @param int  $skill_id
     * @param bool $load_user_data
     *
     * @return array
     */
    public function getChildren($skill_id, $load_user_data = false)
    {
        $skillRelSkill = new SkillRelSkill();
        if ($load_user_data) {
            $user_id = api_get_user_id();
            $skills = $skillRelSkill->getChildren($skill_id, true, $user_id);
        } else {
            $skills = $skillRelSkill->getChildren($skill_id);
        }

        return $skills;
    }

    /**
     * Get all children of the current node (recursive).
     *
     * @param int $skillId
     *
     * @return array
     */
    public function getAllChildren($skillId)
    {
        $skillRelSkill = new SkillRelSkill();
        $children = $skillRelSkill->getChildren($skillId);
        foreach ($children as $child) {
            $subChildren = $this->getAllChildren($child['id']);
        }

        if (!empty($subChildren)) {
            $children = array_merge($children, $subChildren);
        }

        return $children;
    }

    /**
     * Gets all parents from from the wanted skill.
     */
    public function get_parents($skillId)
    {
        $skillRelSkill = new SkillRelSkill();
        $skills = $skillRelSkill->getSkillParents($skillId, true);
        foreach ($skills as &$skill) {
            $skill['data'] = $this->get($skill['skill_id']);
        }

        return $skills;
    }

    /**
     * All direct parents.
     *
     * @param int $skillId
     *
     * @return array
     */
    public function getDirectParents($skillId)
    {
        $skillRelSkill = new SkillRelSkill();
        $skills = $skillRelSkill->getDirectParents($skillId, true);
        if (!empty($skills)) {
            foreach ($skills as &$skill) {
                $skillData = $this->get($skill['skill_id']);
                if (empty($skillData)) {
                    continue;
                }
                $skill['data'] = $skillData;
                $skill_info2 = $skillRelSkill->getSkillInfo($skill['skill_id']);
                $parentId = isset($skill_info2['parent_id']) ? isset($skill_info2['parent_id']) : 0;
                $skill['data']['parent_id'] = $parentId;
            }

            return $skills;
        }

        return [];
    }

    /**
     * Adds a new skill.
     *
     * @param array $params
     *
     * @return bool|null
     */
    public function add($params)
    {
        if (!isset($params['parent_id'])) {
            $params['parent_id'] = 1;
        }

        if (!is_array($params['parent_id'])) {
            $params['parent_id'] = [$params['parent_id']];
        }

        $skillRelSkill = new SkillRelSkill();
        $skillRelGradebook = new SkillRelGradebook();

        // Saving name, description
        $skill_id = $this->save($params);
        if ($skill_id) {
            // Saving skill_rel_skill (parent_id, relation_type)
            foreach ($params['parent_id'] as $parent_id) {
                $relation_exists = $skillRelSkill->relationExists($skill_id, $parent_id);
                if (!$relation_exists) {
                    $attributes = [
                        'skill_id' => $skill_id,
                        'parent_id' => $parent_id,
                        'relation_type' => isset($params['relation_type']) ? $params['relation_type'] : 0,
                        //'level'         => $params['level'],
                    ];
                    $skillRelSkill->save($attributes);
                }
            }

            if (!empty($params['gradebook_id'])) {
                foreach ($params['gradebook_id'] as $gradebook_id) {
                    $attributes = [];
                    $attributes['gradebook_id'] = $gradebook_id;
                    $attributes['skill_id'] = $skill_id;
                    $skillRelGradebook->save($attributes);
                }
            }

            return $skill_id;
        }

        return null;
    }

    /**
     * @param int      $userId
     * @param Category $category
     * @param int      $courseId
     * @param int      $sessionId
     *
     * @return bool
     */
    public function addSkillToUser(
        $userId,
        $category,
        $courseId,
        $sessionId
    ) {
        $skill_gradebook = new SkillRelGradebook();
        $skill_rel_user = new SkillRelUser();

        if (empty($category)) {
            return false;
        }

        $enableGradeSubCategorySkills = (true === api_get_configuration_value('gradebook_enable_subcategory_skills_independant_assignement'));
        // Load subcategories
        if (empty($category->get_parent_id())) {
            $subCategories = $category->get_subcategories(
                $userId,
                $category->get_course_code(),
                $category->get_session_id()
            );
            $scoreSubCategories = $this->getSubCategoryResultScore($category, $userId);
            if (!empty($subCategories)) {
                /** @var Category $subCategory */
                foreach ($subCategories as $subCategory) {
                    $scoreChecked = true;
                    if (!empty($scoreSubCategories[$subCategory->get_id()])) {
                        $resultScore = $scoreSubCategories[$subCategory->get_id()];
                        $scoreChecked = ($resultScore['user_score'] >= $resultScore['min_score']);
                    }
                    if ($scoreChecked) {
                        $this->addSkillToUser($userId, $subCategory, $courseId, $sessionId);
                    }
                }
            }
        }

        $gradebookId = $category->get_id();
        $skill_gradebooks = $skill_gradebook->get_all(['where' => ['gradebook_id = ?' => $gradebookId]]);

        // It checks if gradebook is passed to add the skill
        if ($enableGradeSubCategorySkills) {
            $userFinished = Category::userFinishedCourse(
                $userId,
                $category,
                true
            );
            if (!$userFinished) {
                return false;
            }
        }

        if (!empty($skill_gradebooks)) {
            foreach ($skill_gradebooks as $skill_gradebook) {
                $hasSkill = $this->userHasSkill(
                    $userId,
                    $skill_gradebook['skill_id'],
                    $courseId,
                    $sessionId
                );

                if (!$hasSkill) {
                    $params = [
                        'user_id' => $userId,
                        'skill_id' => $skill_gradebook['skill_id'],
                        'acquired_skill_at' => api_get_utc_datetime(),
                        'course_id' => (int) $courseId,
                        'session_id' => $sessionId ? (int) $sessionId : null,
                    ];

                    $skill_rel_user->save($params);

                    // It sends notifications about user skills from gradebook
                    $badgeAssignationNotification = api_get_configuration_value('badge_assignation_notification');
                    if ($badgeAssignationNotification) {
                        $entityManager = Database::getManager();
                        $skillRepo = $entityManager->getRepository('ChamiloCoreBundle:Skill');
                        $skill = $skillRepo->find($skill_gradebook['skill_id']);
                        if ($skill) {
                            $user = api_get_user_entity($userId);
                            $url = api_get_path(WEB_PATH)."skill/{$skill_gradebook['skill_id']}/user/{$userId}";
                            $message = sprintf(
                                get_lang('YouXHaveAchievedTheSkillYToSeeFollowLinkZ'),
                                $user->getFirstname(),
                                $skill->getName(),
                                Display::url($url, $url, ['target' => '_blank'])
                            );
                            MessageManager::send_message(
                                $user->getId(),
                                get_lang('YouHaveAchievedANewSkill'),
                                $message
                            );
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Get the results of user in a subCategory.
     *
     * @param $category
     * @param $userId
     *
     * @return array
     */
    public function getSubCategoryResultScore($category, $userId)
    {
        $scoreSubCategories = [];
        if (true === api_get_configuration_value('gradebook_enable_subcategory_skills_independant_assignement')) {
            $subCategories = $category->get_subcategories(
                $userId,
                $category->get_course_code(),
                $category->get_session_id()
            );
            $alleval = $category->get_evaluations($userId, false, $category->get_course_code(),
                $category->get_session_id());
            $alllink = $category->get_links($userId, true, $category->get_course_code(), $category->get_session_id());
            $datagen = new GradebookDataGenerator($subCategories, $alleval, $alllink);
            $gradeResult = $datagen->get_data();
            foreach ($gradeResult as $data) {
                /** @var AbstractLink $item */
                $item = $data[0];
                if (Category::class === get_class($item)) {
                    $scoreSubCategories[$item->get_id()]['min_score'] = $item->getCertificateMinScore();
                    $scoreSubCategories[$item->get_id()]['user_score'] = round($data['result_score'][0]);
                }
            }
        }

        return $scoreSubCategories;
    }

    /* Deletes a skill */
    public function delete($skill_id)
    {
        /*$params = array('skill_id' => $skill_id);

        $skillRelSkill     = new SkillRelSkill();
        $skills = $skillRelSkill->get_all(array('where'=>array('skill_id = ?' =>$skill_id)));

        $skill_rel_profile     = new SkillRelProfile();
        $skillRelGradebook = new SkillRelGradebook();
        $skill_rel_user     = new SkillRelUser();

        $this->delete($skill_id);

        $skillRelGradebook->delete($params);*/
    }

    /**
     * @param array $params
     */
    public function edit($params)
    {
        if (!isset($params['parent_id'])) {
            $params['parent_id'] = 1;
        }

        $params['gradebook_id'] = isset($params['gradebook_id']) ? $params['gradebook_id'] : [];

        $skillRelSkill = new SkillRelSkill();
        $skillRelGradebook = new SkillRelGradebook();

        // Saving name, description
        $this->update($params);
        $skillId = $params['id'];

        if ($skillId) {
            // Saving skill_rel_skill (parent_id, relation_type)
            if (!is_array($params['parent_id'])) {
                $params['parent_id'] = [$params['parent_id']];
            }

            // Cannot change parent of root
            if ($skillId == 1) {
                $params['parent_id'] = 0;
            }

            foreach ($params['parent_id'] as $parent_id) {
                $relation_exists = $skillRelSkill->relationExists($skillId, $parent_id);
                if (!$relation_exists) {
                    $attributes = [
                        'skill_id' => $skillId,
                        'parent_id' => $parent_id,
                        'relation_type' => $params['relation_type'],
                        //'level'         => $params['level'],
                    ];
                    $skillRelSkill->updateBySkill($attributes);
                }
            }

            $skillRelGradebook->updateGradeBookListBySkill(
                $skillId,
                $params['gradebook_id']
            );

            return $skillId;
        }

        return null;
    }

    /**
     * Get user's skills.
     *
     * @param int  $userId
     * @param bool $getSkillData
     * @param int  $courseId
     * @param int  $sessionId
     *
     * @return array
     */
    public function getUserSkills($userId, $getSkillData = false, $courseId = 0, $sessionId = 0)
    {
        $userId = (int) $userId;
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;

        $courseCondition = '';
        if (!empty($courseId)) {
            $courseCondition = " AND course_id = $courseId ";
        }

        $sessionCondition = '';
        if (!empty($sessionId)) {
            $sessionCondition = " AND course_id = $sessionId ";
        }

        $sql = 'SELECT DISTINCT
                    s.id,
                    s.name,
                    s.icon,
                    u.id as issue,
                    u.acquired_skill_at,
                    u.course_id
                FROM '.$this->table_skill_rel_user.' u
                INNER JOIN '.$this->table.' s
                ON u.skill_id = s.id
                WHERE
                    user_id = '.$userId.' '.$sessionCondition.' '.$courseCondition;

        $result = Database::query($sql);
        $skills = Database::store_result($result, 'ASSOC');
        $skillList = [];
        if (!empty($skills)) {
            foreach ($skills as $skill) {
                if ($getSkillData) {
                    $skillData = $this->get($skill['id']);
                    $skillData['url'] = api_get_path(WEB_PATH).'badge/'.$skill['id'].'/user/'.$userId;
                    $skillList[$skill['id']] = array_merge($skill, $skillData);
                } else {
                    $skillList[$skill['id']] = $skill['id'];
                }
            }
        }

        return $skillList;
    }

    /**
     * @param array $skills
     * @param int   $level
     *
     * @return string
     */
    public function processVertex(Vertex $vertex, $skills = [], $level = 0)
    {
        $isHierarchicalTable = api_get_configuration_value('table_of_hierarchical_skill_presentation');
        $subTable = '';
        if ($vertex->getVerticesEdgeTo()->count() > 0) {
            if ($isHierarchicalTable) {
                $subTable .= '<ul>';
            }
            foreach ($vertex->getVerticesEdgeTo() as $subVertex) {
                $data = $subVertex->getAttribute('graphviz.data');
                $passed = in_array($data['id'], array_keys($skills));
                $transparency = '';
                if ($passed === false) {
                    // @todo use css class
                    $transparency = 'opacity: 0.4; filter: alpha(opacity=40);';
                }

                if ($isHierarchicalTable) {
                    $label = $this->processSkillListSimple([$data], 'mini', $transparency);
                    $subTable .= '<li>'.$label;
                    $subTable .= $this->processVertex($subVertex, $skills, $level + 1);
                    $subTable .= '</li>';
                } else {
                    $imageSize = 'mini';
                    if ($level == 2) {
                        $imageSize = 'small';
                    }
                    $showTitle = true;
                    if ($level > 2) {
                        $showTitle = false;
                    }

                    $label = $this->processSkillListSimple([$data], $imageSize, $transparency, true, $showTitle);
                    $subTable .= '<div class="thumbnail" style="float:left; margin-right:5px; ">';
                    $subTable .= '<div style="'.$transparency.'">';

                    $subTable .= '<div style="text-align: center">';
                    $subTable .= $label;
                    $subTable .= '</div>';

                    $subTable .= '</div>';
                    $subTable .= $this->processVertex($subVertex, $skills, $level + 1);
                    $subTable .= '</div>';
                }
            }

            if ($isHierarchicalTable) {
                $subTable .= '</ul>';
            }
        }

        return $subTable;
    }

    /**
     * @param int  $userId
     * @param int  $courseId
     * @param int  $sessionId
     * @param bool $addTitle
     *
     * @return array
     */
    public function getUserSkillsTable($userId, $courseId = 0, $sessionId = 0, $addTitle = true)
    {
        $skills = $this->getUserSkills($userId, true, $courseId, $sessionId);
        $courseTempList = [];
        $tableRows = [];
        $skillParents = [];
        foreach ($skills as $resultData) {
            $parents = $this->get_parents($resultData['id']);
            foreach ($parents as $parentData) {
                $parentData['passed'] = in_array($parentData['id'], array_keys($skills));
                if ($parentData['passed'] && isset($skills[$parentData['id']]['url'])) {
                    $parentData['data']['url'] = $skills[$parentData['id']]['url'];
                }
                $skillParents[$resultData['id']][$parentData['id']] = $parentData;
            }
        }

        foreach ($skills as $resultData) {
            $courseId = $resultData['course_id'];
            if (!empty($courseId)) {
                if (isset($courseTempList[$courseId])) {
                    $courseInfo = $courseTempList[$courseId];
                } else {
                    $courseInfo = api_get_course_info_by_id($courseId);
                    $courseTempList[$courseId] = $courseInfo;
                }
            } else {
                $courseInfo = [];
            }
            $tableRow = [
                'skill_badge' => $resultData['img_small'],
                'skill_name' => self::translateName($resultData['name']),
                'short_code' => $resultData['short_code'],
                'skill_url' => $resultData['url'],
                'achieved_at' => api_get_local_time($resultData['acquired_skill_at']),
                'course_image' => '',
                'course_name' => '',
            ];

            if (!empty($courseInfo)) {
                $tableRow['course_image'] = $courseInfo['course_image'];
                $tableRow['course_name'] = $courseInfo['title'];
            }
            $tableRows[] = $tableRow;
        }

        $isHierarchicalTable = api_get_configuration_value('table_of_hierarchical_skill_presentation');
        $allowLevels = api_get_configuration_value('skill_levels_names');

        $tableResult = '<div id="skillList">';
        if ($isHierarchicalTable) {
            $tableResult = '<div class="table-responsive">';
        }

        if ($addTitle) {
            $tableResult .= Display::page_subheader(get_lang('AchievedSkills'));
            $tableResult .= '<div class="skills-badges">';
        }

        if (!empty($skillParents)) {
            if (empty($allowLevels)) {
                $tableResult .= $this->processSkillListSimple($skills);
            } else {
                $graph = new Graph();
                $graph->setAttribute('graphviz.graph.rankdir', 'LR');
                foreach ($skillParents as $skillId => $parentList) {
                    $old = null;
                    foreach ($parentList as $parent) {
                        if ($graph->hasVertex($parent['id'])) {
                            $current = $graph->getVertex($parent['id']);
                        } else {
                            $current = $graph->createVertex($parent['id']);
                            $current->setAttribute('graphviz.data', $parent['data']);
                        }

                        if (!empty($old)) {
                            if ($graph->hasVertex($old['id'])) {
                                $nextVertex = $graph->getVertex($old['id']);
                            } else {
                                $nextVertex = $graph->createVertex($old['id']);
                                $nextVertex->setAttribute('graphviz.data', $old['data']);
                            }

                            if (!$nextVertex->hasEdgeTo($current)) {
                                $nextVertex->createEdgeTo($current);
                            }
                        }
                        $old = $parent;
                    }
                }

                if ($isHierarchicalTable) {
                    $table = '<table class ="table table-bordered">';
                    // Getting "root" vertex
                    $root = $graph->getVertex(1);
                    $table .= '<tr>';
                    /** @var Vertex $vertex */
                    foreach ($root->getVerticesEdgeTo() as $vertex) {
                        $data = $vertex->getAttribute('graphviz.data');

                        $passed = in_array($data['id'], array_keys($skills));
                        $transparency = '';
                        if ($passed === false) {
                            // @todo use a css class
                            $transparency = 'opacity: 0.4; filter: alpha(opacity=40);';
                        }

                        $label = $this->processSkillListSimple([$data], 'mini', $transparency);
                        $table .= '<td >';

                        $table .= '<div class="skills_chart"> <ul><li>'.$label;
                        $table .= $this->processVertex($vertex, $skills);
                        $table .= '</ul></li></div>';
                        $table .= '</td>';
                    }
                    $table .= '</tr></table>';
                } else {
                    // Getting "root" vertex
                    $root = $graph->getVertex(1);
                    $table = '';
                    /** @var Vertex $vertex */
                    foreach ($root->getVerticesEdgeTo() as $vertex) {
                        $data = $vertex->getAttribute('graphviz.data');

                        $passed = in_array($data['id'], array_keys($skills));
                        $transparency = '';
                        if ($passed === false) {
                            // @todo use a css class
                            $transparency = 'opacity: 0.4; filter: alpha(opacity=40);';
                        }

                        $label = $this->processSkillListSimple([$data], 'mini', $transparency, false);

                        $skillTable = $this->processVertex($vertex, $skills, 2);
                        $table .= "<h3>$label</h3>";

                        if (!empty($skillTable)) {
                            $table .= '<table class ="table table-bordered">';
                            $table .= '<tr>';
                            $table .= '<td>';
                            $table .= '<div>';
                            $table .= $skillTable;
                            $table .= '</div>';
                            $table .= '</td>';
                            $table .= '</tr></table>';
                        }
                    }
                }

                $tableResult .= $table;
            }
        } else {
            $tableResult .= get_lang('WithoutAchievedSkills');
        }

        if ($addTitle) {
            $tableResult .= '</div>';
        }
        $tableResult .= '</div>';

        return [
            'skills' => $tableRows,
            'table' => $tableResult,
        ];
    }

    /**
     * @param int  $user_id
     * @param int  $skill_id
     * @param bool $return_flat_array
     * @param bool $add_root
     *
     * @return array|null
     */
    public function getSkillsTree(
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
                    $skills[1] = [
                        'id' => '1',
                        'name' => get_lang('Root'),
                        'parent_id' => '0',
                        'status' => 1,
                    ];
                    $skillInfo = $this->getSkillInfo($skill_id);

                    // 2nd node
                    $skills[$skill_id] = $skillInfo;
                    // Uncomment code below to hide the searched skill
                    $skills[$skill_id]['data']['parent_id'] = $skillInfo['extra']['parent_id'];
                    $skills[$skill_id]['parent_id'] = 1;
                }
            }
        }

        $refs = [];
        $skills_tree = null;

        // Create references for all nodes
        $flat_array = [];
        $family = [];
        if (!empty($skills)) {
            foreach ($skills as &$skill) {
                if ($skill['parent_id'] == 0) {
                    $skill['parent_id'] = 'root';
                }

                // because except main keys (id, name, children) others keys
                // are not saved while in the space tree
                $skill['data'] = ['parent_id' => $skill['parent_id']];

                // If a short code was defined, send the short code to replace
                // skill name (to shorten the text in the wheel)
                if (!empty($skill['short_code']) &&
                    api_get_setting('show_full_skill_name_on_skill_wheel') === 'false'
                ) {
                    $skill['data']['short_code'] = $skill['short_code'];
                }

                $skill['data']['name'] = $skill['name'];
                $skill['data']['status'] = $skill['status'];

                // In order to paint all members of a family with the same color
                if (empty($skill_id)) {
                    if ($skill['parent_id'] == 1) {
                        $family[$skill['id']] = $this->getAllChildren($skill['id']);
                    }
                } else {
                    if ($skill['parent_id'] == $skill_id) {
                        $family[$skill['id']] = $this->getAllChildren($skill['id']);
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
                    $skill['data']['achieved'] = $this->userHasSkill(
                        $user_id,
                        $skill['id']
                    );
                }

                // Check if the skill has related gradebooks
                $skill['data']['skill_has_gradebook'] = false;
                if (isset($skill['gradebooks']) && !empty($skill['gradebooks'])) {
                    $skill['data']['skill_has_gradebook'] = true;
                }
                $refs[$skill['id']] = &$skill;
                $flat_array[$skill['id']] = &$skill;
            }

            // Checking family value

            $family_id = 1;
            $new_family_array = [];
            foreach ($family as $main_family_id => $family_items) {
                if (!empty($family_items)) {
                    foreach ($family_items as $item) {
                        $new_family_array[$item['id']] = $family_id;
                    }
                }
                $new_family_array[$main_family_id] = $family_id;
                $family_id++;
            }

            if (empty($original_skill)) {
                $refs['root']['children'][0] = $skills[1];
                $skills[$skill_id]['data']['family_id'] = 1;
                $refs['root']['children'][0]['children'][0] = $skills[$skill_id];
                $flat_array[$skill_id] = $skills[$skill_id];
            } else {
                // Moving node to the children index of their parents
                foreach ($skills as $my_skill_id => &$skill) {
                    if (isset($new_family_array[$skill['id']])) {
                        $skill['data']['family_id'] = $new_family_array[$skill['id']];
                    }
                    $refs[$skill['parent_id']]['children'][] = &$skill;
                    $flat_array[$my_skill_id] = $skill;
                }
            }

            $skills_tree = [
                'name' => get_lang('SkillRootName'),
                'id' => 'root',
                'children' => $refs['root']['children'],
                'data' => [],
            ];
        }

        if ($return_flat_array) {
            return $flat_array;
        }
        unset($skills);

        return $skills_tree;
    }

    /**
     * Get skills tree as a simplified JSON structure.
     *
     * @param int user id
     * @param int skill id
     * @param bool return a flat array or not
     * @param int depth of the skills
     *
     * @return string json
     */
    public function getSkillsTreeToJson(
        $user_id = null,
        $skill_id = null,
        $return_flat_array = false,
        $main_depth = 2
    ) {
        $tree = $this->getSkillsTree(
            $user_id,
            $skill_id,
            $return_flat_array,
            true
        );
        $simple_tree = [];
        if (!empty($tree['children'])) {
            foreach ($tree['children'] as $element) {
                $children = [];
                if (isset($element['children'])) {
                    $children = $this->getSkillToJson($element['children'], 1, $main_depth);
                }
                $simple_tree[] = [
                    'name' => $element['name'],
                    'children' => $children,
                ];
            }
        }

        return json_encode($simple_tree[0]['children']);
    }

    /**
     * Get JSON element.
     *
     * @param array $subtree
     * @param int   $depth
     * @param int   $max_depth
     *
     * @return array|null
     */
    public function getSkillToJson($subtree, $depth = 1, $max_depth = 2)
    {
        $simple_sub_tree = [];
        if (is_array($subtree)) {
            $counter = 1;
            foreach ($subtree as $elem) {
                $tmp = [];
                $tmp['name'] = $elem['name'];
                $tmp['id'] = $elem['id'];
                $tmp['isSearched'] = self::isSearched($elem['id']);

                if (isset($elem['children']) && is_array($elem['children'])) {
                    $tmp['children'] = $this->getSkillToJson(
                        $elem['children'],
                        $depth + 1,
                        $max_depth
                    );
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
     *
     * @return bool
     */
    public function getUserSkillRanking($user_id)
    {
        $user_id = (int) $user_id;
        $sql = "SELECT count(skill_id) count
                FROM {$this->table} s
                INNER JOIN {$this->table_skill_rel_user} su
                ON (s.id = su.skill_id)
                WHERE user_id = $user_id";
        $result = Database::query($sql);
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
     *
     * @return array
     */
    public function getUserListSkillRanking(
        $start,
        $limit,
        $sidx,
        $sord,
        $where_condition
    ) {
        $start = (int) $start;
        $limit = (int) $limit;

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

        return [];
    }

    /**
     * @return int
     */
    public function getUserListSkillRankingCount()
    {
        $sql = "SELECT count(*) FROM (
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
     *
     * @return int
     */
    public function getCountSkillsByCourse($courseCode)
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
     * @param int $skillId
     *
     * @return array
     */
    public function getCoursesBySkill($skillId)
    {
        $skillId = (int) $skillId;
        $sql = "SELECT c.title, c.code
                FROM {$this->table_gradebook} g
                INNER JOIN {$this->table_skill_rel_gradebook} sg
                ON g.id = sg.gradebook_id
                INNER JOIN {$this->table_course} c
                ON c.code = g.course_code
                WHERE sg.skill_id = $skillId
                AND (g.session_id IS NULL OR g.session_id = 0)";
        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    /**
     * Check if the user has the skill.
     *
     * @param int $userId    The user id
     * @param int $skillId   The skill id
     * @param int $courseId  Optional. The course id
     * @param int $sessionId Optional. The session id
     *
     * @return bool Whether the user has the skill return true. Otherwise return false
     */
    public function userHasSkill($userId, $skillId, $courseId = 0, $sessionId = 0)
    {
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;

        $whereConditions = [
            'user_id = ? ' => (int) $userId,
            'AND skill_id = ? ' => (int) $skillId,
        ];

        if ($courseId > 0) {
            if ($sessionId) {
                $whereConditions['AND course_id = ? '] = $courseId;
                $whereConditions['AND session_id = ? '] = $sessionId;
            } else {
                $whereConditions['AND course_id = ? AND session_id is NULL'] = $courseId;
            }
        }

        $result = Database::select(
            'COUNT(1) AS qty',
            $this->table_skill_rel_user,
            [
                'where' => $whereConditions,
            ],
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
     * Check if a skill is searched.
     *
     * @param int $id The skill id
     *
     * @return bool Whether el skill is searched return true. Otherwise return false
     */
    public static function isSearched($id)
    {
        $id = (int) $id;

        if (empty($id)) {
            return false;
        }

        $skillRelProfileTable = Database::get_main_table(TABLE_MAIN_SKILL_REL_PROFILE);

        $result = Database::select(
            'COUNT( DISTINCT `skill_id`) AS qty',
            $skillRelProfileTable,
            [
                'where' => [
                    'skill_id = ?' => $id,
                ],
            ],
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
     * Get the achieved skills by course.
     *
     * @param int $courseId The course id
     *
     * @return array The skills list
     */
    public function listAchievedByCourse($courseId)
    {
        $courseId = (int) $courseId;

        if ($courseId == 0) {
            return [];
        }

        $list = [];

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
            $row['skill_name'] = self::translateName($row['skill_name']);
            $list[] = $row;
        }

        return $list;
    }

    /**
     * Get the users list who achieved a skill.
     *
     * @param int $skillId The skill id
     *
     * @return array The users list
     */
    public function listUsersWhoAchieved($skillId)
    {
        $skillId = (int) $skillId;

        if ($skillId == 0) {
            return [];
        }

        $list = [];
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
            $row['skill_name'] = self::translateName($row['skill_name']);
            $list[] = $row;
        }

        return $list;
    }

    /**
     * Get the session list where the user can achieve a skill.
     *
     * @param int $skillId The skill id
     *
     * @return array
     */
    public function getSessionsBySkill($skillId)
    {
        $skillId = (int) $skillId;

        $sql = "SELECT s.id, s.name
                FROM {$this->table_gradebook} g
                INNER JOIN {$this->table_skill_rel_gradebook} sg
                ON g.id = sg.gradebook_id
                INNER JOIN {$this->sessionTable} s
                ON g.session_id = s.id
                WHERE sg.skill_id = $skillId
                AND g.session_id > 0";

        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    /**
     * Check if the $fromUser can comment the $toUser skill issue.
     *
     * @param User $fromUser
     * @param User $toUser
     *
     * @return bool
     */
    public static function userCanAddFeedbackToUser($fromUser, $toUser)
    {
        if (api_is_platform_admin()) {
            return true;
        }

        $userRepo = UserManager::getRepository();
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
                break;
            case DRH:
                return UserManager::is_user_followed_by_drh(
                    $toUser->getId(),
                    $fromUser->getId()
                );
        }

        return false;
    }

    /**
     * If $studentId is set then check if current user has the right to see
     * the page.
     *
     * @param int  $studentId check if current user has access to see $studentId
     * @param bool $blockPage raise a api_not_allowed()
     *
     * @return bool
     */
    public static function isAllowed($studentId = 0, $blockPage = true)
    {
        $allowHR = api_get_setting('allow_hr_skills_management') === 'true';

        if (self::isToolAvailable()) {
            if (api_is_platform_admin(false, $allowHR)) {
                return true;
            }

            if (!empty($studentId)) {
                $currentUserId = api_get_user_id();
                if ((int) $currentUserId === (int) $studentId) {
                    return true;
                }

                $haveAccess = self::hasAccessToUserSkill(
                    $currentUserId,
                    $studentId
                );

                if ($haveAccess) {
                    return true;
                }
            }
        }

        if ($blockPage) {
            api_not_allowed(true);
        }

        return false;
    }

    /**
     * @return bool
     */
    public static function isToolAvailable()
    {
        $allowTool = api_get_setting('allow_skills_tool');

        if ($allowTool === 'true') {
            return true;
        }

        return false;
    }

    /**
     * @param int $currentUserId
     * @param int $studentId
     *
     * @return bool
     */
    public static function hasAccessToUserSkill($currentUserId, $studentId)
    {
        if (self::isToolAvailable()) {
            if (api_is_platform_admin()) {
                return true;
            }

            $currentUserId = (int) $currentUserId;
            $studentId = (int) $studentId;

            if ($currentUserId === $studentId) {
                return true;
            }

            if (api_is_student_boss()) {
                $isBoss = UserManager::userIsBossOfStudent($currentUserId, $studentId);
                if ($isBoss) {
                    return true;
                }
            }

            $allow = api_get_configuration_value('allow_private_skills');
            if ($allow === true) {
                if (api_is_teacher()) {
                    return UserManager::isTeacherOfStudent(
                        $currentUserId,
                        $studentId
                    );
                }

                if (api_is_drh()) {
                    return UserManager::is_user_followed_by_drh(
                        $studentId,
                        $currentUserId
                    );
                }
            }
        }

        return false;
    }

    /**
     * Get skills.
     *
     * @param int $userId
     * @param int level
     *
     * @return array
     */
    public function getStudentSkills($userId, $level = 0)
    {
        $userId = (int) $userId;

        $sql = "SELECT s.id, s.name, sru.acquired_skill_at
                FROM {$this->table} s
                INNER JOIN {$this->table_skill_rel_user} sru
                ON s.id = sru.skill_id
                WHERE sru.user_id = $userId";

        $result = Database::query($sql);

        $skills = [];
        foreach ($result as $item) {
            if (empty($level)) {
                $skills[] = [
                    'name' => self::translateName($item['name']),
                    'acquired_skill_at' => $item['acquired_skill_at'],
                ];
            } else {
                $parents = self::get_parents($item['id']);
                // +2 because it takes into account the root
                if (count($parents) == $level + 1) {
                    $skills[] = [
                        'name' => self::translateName($item['name']),
                        'acquired_skill_at' => $item['acquired_skill_at'],
                    ];
                }
            }
        }

        return $skills;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public static function translateName($name)
    {
        $variable = ChamiloApi::getLanguageVar($name, 'Skill');

        return isset($GLOBALS[$variable]) ? $GLOBALS[$variable] : $name;
    }

    /**
     * @param string $code
     *
     * @return mixed|string
     */
    public static function translateCode($code)
    {
        if (empty($code)) {
            return '';
        }

        $variable = ChamiloApi::getLanguageVar($code, 'SkillCode');

        return isset($GLOBALS[$variable]) ? $GLOBALS[$variable] : $code;
    }

    /**
     * @param array $skillInfo
     *
     * @return array
     */
    public function setForm(FormValidator &$form, $skillInfo = [])
    {
        $allSkills = $this->get_all();
        $objGradebook = new Gradebook();

        $isAlreadyRootSkill = false;
        foreach ($allSkills as $checkedSkill) {
            if (intval($checkedSkill['parent_id']) > 0) {
                $isAlreadyRootSkill = true;
                break;
            }
        }

        $skillList = $isAlreadyRootSkill ? [] : [0 => get_lang('None')];

        foreach ($allSkills as $skill) {
            if (isset($skillInfo['id']) && $skill['id'] == $skillInfo['id']) {
                continue;
            }

            $skillList[$skill['id']] = $skill['name'];
        }

        $allGradeBooks = $objGradebook->find('all');

        // This procedure is for check if there is already a Skill with no Parent (Root by default)
        $gradeBookList = [];
        foreach ($allGradeBooks as $gradebook) {
            $gradeBookList[$gradebook['id']] = $gradebook['name'];
        }

        $translateUrl = api_get_path(WEB_CODE_PATH).'admin/skill_translate.php?';
        $translateNameButton = '';
        $translateCodeButton = '';
        $skillId = null;
        if (!empty($skillInfo)) {
            $skillId = $skillInfo['id'];
            $translateNameUrl = $translateUrl.http_build_query(['skill' => $skillId, 'action' => 'name']);
            $translateCodeUrl = $translateUrl.http_build_query(['skill' => $skillId, 'action' => 'code']);
            $translateNameButton = Display::toolbarButton(
                get_lang('TranslateThisTerm'),
                $translateNameUrl,
                'language',
                'link'
            );
            $translateCodeButton = Display::toolbarButton(
                get_lang('TranslateThisTerm'),
                $translateCodeUrl,
                'language',
                'link'
            );
        }

        $form->addText('name', [get_lang('Name'), $translateNameButton], true, ['id' => 'name']);
        $form->addText('short_code', [get_lang('ShortCode'), $translateCodeButton], false, ['id' => 'short_code']);

        // Cannot change parent of root
        if ($skillId != 1) {
            $form->addSelect('parent_id', get_lang('Parent'), $skillList, ['id' => 'parent_id']);
        }

        $form->addSelect(
            'gradebook_id',
            [get_lang('Gradebook'), get_lang('WithCertificate')],
            $gradeBookList,
            ['id' => 'gradebook_id', 'multiple' => 'multiple', 'size' => 10]
        );
        $form->addTextarea('description', get_lang('Description'), ['id' => 'description', 'rows' => 7]);
        $form->addTextarea('criteria', get_lang('CriteriaToEarnTheBadge'), ['id' => 'criteria', 'rows' => 7]);

        // EXTRA FIELDS
        $extraField = new ExtraField('skill');
        $returnParams = $extraField->addElements($form, $skillId);

        if (empty($skillInfo)) {
            $form->addButtonCreate(get_lang('Add'));
        } else {
            $form->addButtonUpdate(get_lang('Update'));
            $form->addHidden('id', $skillInfo['id']);
        }

        return $returnParams;
    }

    /**
     * @return string
     */
    public function getToolBar()
    {
        $toolbar = Display::url(
            Display::return_icon(
                'back.png',
                get_lang('ManageSkills'),
                null,
                ICON_SIZE_MEDIUM
            ),
            api_get_path(WEB_CODE_PATH).'admin/skill_list.php'
        );
        $actions = '<div class="actions">'.$toolbar.'</div>';

        return $actions;
    }

    /**
     * @param SkillRelItemRelUser $skillRelItemRelUser
     */
    public static function getUserSkillStatusLabel(SkillRelItem $skillRelItem, SkillRelItemRelUser $skillRelItemRelUser = null, bool $addHeader = true, int $userId = 0): string
    {
        if (empty($skillRelItem)) {
            return '';
        }
        $type = 'success';
        if (empty($skillRelItemRelUser)) {
            $type = '';
        }
        $label = '';
        $skill = $skillRelItem->getSkill();
        if ($addHeader) {
            $label .= '<span id="skill-'.$skill->getId().'-'.$userId.'" class="user_skill" style="cursor:pointer">';
        }
        $label .= Display::label($skill->getName(), $type);
        if ($addHeader) {
            $label .= '</span>&nbsp;';
        }

        return $label;
    }

    /**
     * Attach a list of skills (skill_rel_item) potentially assigned to a user to the given form.
     *
     * @param int  $typeId    see ITEM_TYPE_* constants
     * @param bool $addHeader Whether to show the 'Skills' title for this block
     */
    public static function addSkillsToUserForm(FormValidator $form, int $typeId, int $itemId, int $userId, int $resultId = 0, bool $addHeader = false): void
    {
        $allowSkillInTools = api_get_configuration_value('allow_skill_rel_items');
        if ($allowSkillInTools && !empty($typeId) && !empty($itemId) && !empty($userId)) {
            $em = Database::getManager();
            $items = $em->getRepository('ChamiloSkillBundle:SkillRelItem')->findBy(
                ['itemId' => $itemId, 'itemType' => $typeId]
            );

            $skillRelUser = new SkillRelUser();
            $skillUserList = $skillRelUser->getUserSkills($userId);
            if (!empty($skillUserList)) {
                $skillUserList = array_column($skillUserList, 'skill_id');
            }

            $skills = '';
            /** @var SkillRelItem $skillRelItem */
            foreach ($items as $skillRelItem) {
                $criteria = [
                    'user' => $userId,
                    'skillRelItem' => $skillRelItem,
                ];
                $skillRelItemRelUser = $em->getRepository('ChamiloSkillBundle:SkillRelItemRelUser')->findOneBy($criteria);
                $skills .= self::getUserSkillStatusLabel($skillRelItem, $skillRelItemRelUser, true, $userId);
            }

            if (!empty($skills)) {
                $url = api_get_path(WEB_AJAX_PATH).'skill.ajax.php?a=update_skill_rel_user&'.api_get_cidreq();
                $params = [
                    'item_id' => $itemId,
                    'type_id' => $typeId,
                    'user_id' => $userId,
                    'course_id' => api_get_course_int_id(),
                    'session_id' => api_get_session_id(),
                    'result_id' => $resultId,
                ];
                $params = json_encode($params);
                if ($addHeader) {
                    $form->addHtml(Display::page_subheader2(get_lang('Skills')));
                }

                $skillId = $skillRelItem->getSkill()->getId();
                $elementId = 'skill-'.$skillId.'-'.$userId;
                $html = '
                <script>
                    $(function() {
                        $("#'.$elementId.'").on("click", function() {
                            var params = '.$params.';
                            $.ajax({
                                type: "GET",
                                async: false,
                                data: params,
                                url: "'.$url.'&skill_id="+'.$skillId.',
                                success: function(result) {
                                    $("#'.$elementId.'.user_skill").html(result);
                                }
                            });
                        });
                    });
                </script>
                ';
                $form->addHtml($html);
                $form->addLabel(get_lang('Skills'), $skills);
                if ($addHeader) {
                    $form->addHtml('<br />');
                }
            }
        }
    }

    /**
     * Shows a list of skills (skill_rel_item) potentially assigned to a user
     * to the given form, with AJAX action on click to save the assignment.
     * Assigned skills appear in a different colour.
     *
     * @param int  $typeId    see ITEM_TYPE_* constants
     * @param bool $addHeader Whether to show the 'Skills' title for this block
     */
    public static function getAddSkillsToUserBlock(int $typeId, int $itemId, int $userId, int $resultId = 0, bool $addHeader = false): string
    {
        $block = '';
        $allowSkillInTools = api_get_configuration_value('allow_skill_rel_items');
        if ($allowSkillInTools && !empty($typeId) && !empty($itemId) && !empty($userId)) {
            $em = Database::getManager();
            $items = $em->getRepository('ChamiloSkillBundle:SkillRelItem')->findBy(
                ['itemId' => $itemId, 'itemType' => $typeId]
            );

            $skills = '';
            /** @var SkillRelItem $skillRelItem */
            foreach ($items as $skillRelItem) {
                $criteria = [
                    'user' => $userId,
                    'skillRelItem' => $skillRelItem,
                ];
                $skillRelItemRelUser = $em->getRepository('ChamiloSkillBundle:SkillRelItemRelUser')->findOneBy($criteria);
                $skills .= self::getUserSkillStatusLabel($skillRelItem, $skillRelItemRelUser, true, $userId);
            }
            $block .= $skills;

            if (!empty($skills)) {
                $url = api_get_path(WEB_AJAX_PATH).'skill.ajax.php?a=update_skill_rel_user&'.api_get_cidreq();
                $params = [
                    'item_id' => $itemId,
                    'type_id' => $typeId,
                    'user_id' => $userId,
                    'course_id' => api_get_course_int_id(),
                    'session_id' => api_get_session_id(),
                    'result_id' => $resultId,
                ];
                $params = json_encode($params);
                if ($addHeader) {
                    $block .= Display::page_subheader2(get_lang('Skills'));
                }

                $skillId = $skillRelItem->getSkill()->getId();
                $elementId = 'skill-'.$skillId.'-'.$userId;
                $html = '
                <script>
                    $(function() {
                        $("#'.$elementId.'").on("click", function() {
                            var params = '.$params.';
                            $.ajax({
                                type: "GET",
                                async: false,
                                data: params,
                                url: "'.$url.'&skill_id="+'.$skillId.',
                                success: function(result) {
                                    $("#'.$elementId.'.user_skill").html(result);
                                }
                            });
                        });
                    });
                </script>
                ';
                $block .= $html;
                //$block .= $form->addLabel(get_lang('Skills'), $skills);
                if ($addHeader) {
                    $block .= '<br />';
                }
            }
        }

        return $block;
    }

    /**
     * Add skills select ajax for an item (exercise, lp).
     *
     * @param int $courseId
     * @param int $sessionId
     * @param int $typeId    see ITEM_TYPE_* constants
     * @param int $itemId
     *
     * @throws Exception
     *
     * @return array
     */
    public static function addSkillsToForm(FormValidator $form, $courseId, $sessionId, $typeId, $itemId = 0)
    {
        $allowSkillInTools = api_get_configuration_value('allow_skill_rel_items');
        if (!$allowSkillInTools) {
            return [];
        }

        if (empty($sessionId)) {
            $sessionId = null;
        }

        $em = Database::getManager();
        $skillRelCourseRepo = $em->getRepository('ChamiloSkillBundle:SkillRelCourse');
        $items = $skillRelCourseRepo->findBy(['course' => $courseId, 'session' => $sessionId]);

        $skills = [];
        /** @var \Chamilo\SkillBundle\Entity\SkillRelCourse $skillRelCourse */
        foreach ($items as $skillRelCourse) {
            $skills[] = $skillRelCourse->getSkill();
        }

        $selectedSkills = [];
        if (!empty($itemId)) {
            $items = $em->getRepository('ChamiloSkillBundle:SkillRelItem')->findBy(
                ['itemId' => $itemId, 'itemType' => $typeId]
            );
            /** @var SkillRelItem $skillRelItem */
            foreach ($items as $skillRelItem) {
                $selectedSkills[] = $skillRelItem->getSkill()->getId();
            }
        }

        self::skillsToCheckbox($form, $skills, $courseId, $sessionId, $selectedSkills);

        return $skills;
    }

    /**
     * @param int $courseId
     * @param int $sessionId
     *
     * @return array
     */
    public static function getSkillRelItemsPerCourse($courseId, $sessionId = null)
    {
        $allowSkillInTools = api_get_configuration_value('allow_skill_rel_items');
        $skills = [];

        if (empty($sessionId)) {
            $sessionId = null;
        }

        if ($allowSkillInTools) {
            $em = Database::getManager();
            $skills = $em->getRepository('ChamiloSkillBundle:SkillRelItem')->findBy(
                ['courseId' => $courseId, 'sessionId' => $sessionId]
            );
        }

        return $skills;
    }

    /**
     * @param int $itemId
     * @param int $itemType
     *
     * @return array
     */
    public static function getItemInfo($itemId, $itemType)
    {
        $itemInfo = [];
        $itemId = (int) $itemId;
        $itemType = (int) $itemType;
        $em = Database::getManager();

        switch ($itemType) {
            case ITEM_TYPE_EXERCISE:
                /** @var \Chamilo\CourseBundle\Entity\CQuiz $item */
                $item = $em->getRepository('ChamiloCourseBundle:CQuiz')->find($itemId);
                if ($item) {
                    $itemInfo['name'] = $item->getTitle();
                }
                break;
            case ITEM_TYPE_HOTPOTATOES:
                break;
            case ITEM_TYPE_LINK:
                /** @var \Chamilo\CourseBundle\Entity\CLink $item */
                $item = $em->getRepository('ChamiloCourseBundle:CLink')->find($itemId);
                if ($item) {
                    $itemInfo['name'] = $item->getTitle();
                }
                break;
            case ITEM_TYPE_LEARNPATH:
                /** @var \Chamilo\CourseBundle\Entity\CLp $item */
                $item = $em->getRepository('ChamiloCourseBundle:CLp')->find($itemId);
                if ($item) {
                    $itemInfo['name'] = $item->getName();
                }
                break;
            case ITEM_TYPE_GRADEBOOK:
                break;
            case ITEM_TYPE_STUDENT_PUBLICATION:
                /** @var \Chamilo\CourseBundle\Entity\CStudentPublication $item */
                $item = $em->getRepository('ChamiloCourseBundle:CStudentPublication')->find($itemId);
                if ($item) {
                    $itemInfo['name'] = $item->getTitle();
                }
                break;
            //ITEM_TYPE_FORUM', 7);
            case ITEM_TYPE_ATTENDANCE:
                /** @var \Chamilo\CourseBundle\Entity\CAttendance $item */
                $item = $em->getRepository('ChamiloCourseBundle:CAttendance')->find($itemId);
                if ($item) {
                    $itemInfo['name'] = $item->getName();
                }
                break;
            case ITEM_TYPE_SURVEY:
                /** @var \Chamilo\CourseBundle\Entity\CSurvey $item */
                $item = $em->getRepository('ChamiloCourseBundle:CSurvey')->find($itemId);
                if ($item) {
                    $itemInfo['name'] = strip_tags($item->getTitle());
                }
                break;
            case ITEM_TYPE_FORUM_THREAD:
                /** @var \Chamilo\CourseBundle\Entity\CForumThread $item */
                $item = $em->getRepository('ChamiloCourseBundle:CForumThread')->find($itemId);
                if ($item) {
                    $itemInfo['name'] = $item->getThreadTitle();
                }
                break;
        }

        return $itemInfo;
    }

    /**
     * @param int $typeId
     * @param int $itemId
     *
     * @return array
     */
    public static function getSkillRelItems($typeId, $itemId)
    {
        $allowSkillInTools = api_get_configuration_value('allow_skill_rel_items');
        $skills = [];
        if ($allowSkillInTools) {
            $em = Database::getManager();
            $skills = $em->getRepository('ChamiloSkillBundle:SkillRelItem')->findBy(
                ['itemId' => $itemId, 'itemType' => $typeId]
            );
        }

        return $skills;
    }

    /**
     * @param int $typeId
     * @param int $itemId
     *
     * @return string
     */
    public static function getSkillRelItemsToString($typeId, $itemId)
    {
        $skills = self::getSkillRelItems($typeId, $itemId);
        $skillToString = '';
        if (!empty($skills)) {
            /** @var SkillRelItem $skillRelItem */
            $skillList = [];
            foreach ($skills as $skillRelItem) {
                $skillList[] = Display::label($skillRelItem->getSkill()->getName(), 'success');
            }
            $skillToString = '&nbsp;'.implode(' ', $skillList);
        }

        return $skillToString;
    }

    /**
     * @param int $itemId
     * @param int $typeId
     */
    public static function deleteSkillsFromItem($itemId, $typeId)
    {
        $allowSkillInTools = api_get_configuration_value('allow_skill_rel_items');
        if ($allowSkillInTools) {
            $itemId = (int) $itemId;
            $typeId = (int) $typeId;

            $em = Database::getManager();
            // Delete old ones
            $items = $em->getRepository('ChamiloSkillBundle:SkillRelItem')->findBy(
                ['itemId' => $itemId, 'itemType' => $typeId]
            );

            /** @var SkillRelItem $skillRelItem */
            foreach ($items as $skillRelItem) {
                $em->remove($skillRelItem);
            }
            $em->flush();
        }
    }

    /**
     * Builds a list of skills attributable to this course+session in a checkbox input list for FormValidator.
     *
     * @param     $courseId
     * @param int $sessionId
     *
     * @return array
     */
    public static function setSkillsToCourse(FormValidator $form, $courseId, $sessionId = 0)
    {
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;

        $form->addHidden('course_id', $courseId);
        $form->addHidden('session_id', $sessionId);

        if (empty($sessionId)) {
            $sessionId = null;
        }

        $em = Database::getManager();
        $skillRelCourseRepo = $em->getRepository('ChamiloSkillBundle:SkillRelCourse');
        $items = $skillRelCourseRepo->findBy(['course' => $courseId, 'session' => $sessionId]);

        $skillsIdList = [];
        $skills = [];
        /** @var \Chamilo\SkillBundle\Entity\SkillRelCourse $skillRelCourse */
        foreach ($items as $skillRelCourse) {
            $skillId = $skillRelCourse->getSkill()->getId();
            $skills[] = $skillRelCourse->getSkill();
            $skillsIdList[] = $skillId;
        }

        $group = self::skillsToCheckbox($form, $skills, $courseId, $sessionId, $skillsIdList);
        $group->freeze();

        return [];
    }

    /**
     * Show a list of skills attributable to this course+session in a checkbox input list for FormValidator.
     *
     * @param       $skills
     * @param       $courseId
     * @param       $sessionId
     * @param array $selectedSkills
     *
     * @return HTML_QuickForm_Element|HTML_QuickForm_group
     */
    public static function skillsToCheckbox(FormValidator $form, $skills, $courseId, $sessionId, $selectedSkills = [])
    {
        $em = Database::getManager();
        $skillRelItemRepo = $em->getRepository('ChamiloSkillBundle:SkillRelItem');
        $skillList = [];
        /** @var \Chamilo\CoreBundle\Entity\Skill $skill */
        foreach ($skills as $skill) {
            $skillList[$skill->getId()] = $skill->getName();
        }

        if (!empty($skillList)) {
            asort($skillList);
        }

        if (empty($sessionId)) {
            $sessionId = null;
        }

        $elements = [];
        foreach ($skillList as $skillId => $skill) {
            $countLabel = '';
            $skillRelItemCount = $skillRelItemRepo->count(
                ['skill' => $skillId, 'courseId' => $courseId, 'sessionId' => $sessionId]
            );
            if (!empty($skillRelItemCount)) {
                $countLabel = '&nbsp;'.Display::badge($skillRelItemCount, 'info');
            }

            $element = $form->createElement(
                'checkbox',
                "skills[$skillId]",
                null,
                $skill.$countLabel
            );

            if (in_array($skillId, $selectedSkills)) {
                $element->setValue(1);
            }

            $elements[] = $element;
        }

        return $form->addGroup($elements, '', get_lang('Skills'));
    }

    /**
     * Relate skill with an item (exercise, gradebook, lp, etc).
     *
     * @return bool
     */
    public static function saveSkillsToCourseFromForm(FormValidator $form)
    {
        $skills = (array) $form->getSubmitValue('skills');
        $courseId = (int) $form->getSubmitValue('course_id');
        $sessionId = (int) $form->getSubmitValue('session_id');

        if (!empty($skills)) {
            $skills = array_keys($skills);
        }

        return self::saveSkillsToCourse($skills, $courseId, $sessionId);
    }

    /**
     * @param array $skills
     * @param int   $courseId
     * @param int   $sessionId
     *
     * @return bool
     */
    public static function saveSkillsToCourse($skills, $courseId, $sessionId)
    {
        $allowSkillInTools = api_get_configuration_value('allow_skill_rel_items');
        if (!$allowSkillInTools) {
            return false;
        }

        $em = Database::getManager();
        $sessionId = empty($sessionId) ? null : (int) $sessionId;

        $course = api_get_course_entity($courseId);
        if (empty($course)) {
            return false;
        }

        $session = null;
        if (!empty($sessionId)) {
            $session = api_get_session_entity($sessionId);
            $courseExistsInSession = SessionManager::sessionHasCourse($sessionId, $course->getCode());
            if (!$courseExistsInSession) {
                return false;
            }
        }

        // Delete old ones
        $items = $em->getRepository('ChamiloSkillBundle:SkillRelCourse')->findBy(
            ['course' => $courseId, 'session' => $sessionId]
        );

        if (!empty($items)) {
            /** @var SkillRelCourse $item */
            foreach ($items as $item) {
                if (!in_array($item->getSkill()->getId(), $skills)) {
                    $em->remove($item);
                }
            }
            $em->flush();
        }

        // Add new one
        if (!empty($skills)) {
            foreach ($skills as $skillId) {
                $item = (new SkillRelCourse())
                    ->setCourse($course)
                    ->setSession($session)
                ;

                /** @var SkillEntity $skill */
                $skill = $em->getRepository('ChamiloCoreBundle:Skill')->find($skillId);
                if ($skill) {
                    if (!$skill->hasCourseAndSession($item)) {
                        $skill->addToCourse($item);
                        $em->persist($skill);
                    }
                }
            }
            $em->flush();
        }

        return true;
    }

    /**
     * Relate skill with an item (exercise, gradebook, lp, etc).
     *
     * @param FormValidator $form
     * @param int           $typeId
     * @param int           $itemId
     */
    public static function saveSkills($form, $typeId, $itemId)
    {
        $allowSkillInTools = api_get_configuration_value('allow_skill_rel_items');
        if ($allowSkillInTools) {
            $userId = api_get_user_id();
            $courseId = api_get_course_int_id();
            if (empty($courseId)) {
                $courseId = null;
            }
            $sessionId = api_get_session_id();
            if (empty($sessionId)) {
                $sessionId = null;
            }

            $em = Database::getManager();
            $skills = (array) $form->getSubmitValue('skills');

            $skillRelItemRelUserRepo = $em->getRepository('ChamiloSkillBundle:SkillRelItemRelUser');

            // Delete old ones
            $items = $em->getRepository('ChamiloSkillBundle:SkillRelItem')->findBy(
                ['itemId' => $itemId, 'itemType' => $typeId]
            );

            if (!empty($items)) {
                /** @var SkillRelItem $skillRelItem */
                foreach ($items as $skillRelItem) {
                    $skill = $skillRelItem->getSkill();
                    $skillId = $skill->getId();
                    $skillRelItemId = $skillRelItem->getId();
                    if (!in_array($skillId, $skills)) {
                        // Check if SkillRelItemRelUser is empty
                        /** @var SkillRelItem[] $skillRelItemList */
                        $skillRelItemRelUserList = $skillRelItemRelUserRepo->findBy(['skillRelItem' => $skillRelItemId]);
                        if (empty($skillRelItemRelUserList)) {
                            $em->remove($skillRelItem);
                        } else {
                            /** @var \Chamilo\SkillBundle\Entity\SkillRelItemRelUser $skillRelItemRelUser */
                            foreach ($skillRelItemRelUserList as $skillRelItemRelUser) {
                                Display::addFlash(
                                    Display::return_message(
                                        get_lang('CannotDeleteSkillBlockedByUser').'<br />'.
                                        get_lang('User').': '.UserManager::formatUserFullName($skillRelItemRelUser->getUser()).'<br />'.
                                        get_lang('Skill').': '.$skillRelItemRelUser->getSkillRelItem()->getSkill()->getName(),
                                        'warning',
                                        false
                                    )
                                );
                            }
                        }
                    }
                }
                $em->flush();
            }

            // Add new one
            if (!empty($skills)) {
                $skills = array_keys($skills);
                $skillRepo = $em->getRepository('ChamiloCoreBundle:Skill');

                foreach ($skills as $skillId) {
                    /** @var SkillEntity $skill */
                    $skill = $skillRepo->find($skillId);
                    if (null !== $skill) {
                        if (!$skill->hasItem($typeId, $itemId)) {
                            $skillRelItem = (new SkillRelItem())
                                ->setItemType($typeId)
                                ->setItemId($itemId)
                                ->setCourseId($courseId)
                                ->setSessionId($sessionId)
                                ->setCreatedBy($userId)
                                ->setUpdatedBy($userId)
                            ;
                            $skill->addItem($skillRelItem);
                            $em->persist($skill);
                            $em->persist($skillRelItem);
                            $em->flush();
                        }
                    }
                }
            }
        }
    }

    /**
     * Get the icon (badge image) URL.
     *
     * @param bool $getSmall Optional. Allow get the small image
     *
     * @return string
     */
    public static function getWebIconPath(SkillEntity $skill, $getSmall = false)
    {
        if ($getSmall) {
            if (empty($skill->getIcon())) {
                return \Display::return_icon('badges-default.png', null, null, ICON_SIZE_BIG, null, true);
            }

            return api_get_path(WEB_UPLOAD_PATH).'badges/'.sha1($skill->getName()).'-small.png';
        }

        if (empty($skill->getIcon())) {
            return \Display::return_icon('badges-default.png', null, null, ICON_SIZE_HUGE, null, true);
        }

        return api_get_path(WEB_UPLOAD_PATH)."badges/{$skill->getIcon()}";
    }

    /**
     * @param User                             $user
     * @param \Chamilo\CoreBundle\Entity\Skill $skill
     * @param int                              $levelId
     * @param string                           $argumentation
     * @param int                              $authorId
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return SkillRelUserEntity
     */
    public function addSkillToUserBadge($user, $skill, $levelId, $argumentation, $authorId)
    {
        $showLevels = false === api_get_configuration_value('hide_skill_levels');
        $badgeAssignationNotification = api_get_configuration_value('badge_assignation_notification');

        $entityManager = Database::getManager();

        $skillUserRepo = $entityManager->getRepository('ChamiloCoreBundle:SkillRelUser');

        $criteria = ['user' => $user, 'skill' => $skill];
        $result = $skillUserRepo->findOneBy($criteria);

        if (!empty($result)) {
            return false;
        }
        $skillLevelRepo = $entityManager->getRepository('ChamiloSkillBundle:Level');

        $skillUser = new SkillRelUserEntity();
        $skillUser->setUser($user);
        $skillUser->setSkill($skill);

        if ($showLevels && !empty($levelId)) {
            $level = $skillLevelRepo->find($levelId);
            $skillUser->setAcquiredLevel($level);
        }

        $skillUser->setArgumentation($argumentation);
        $skillUser->setArgumentationAuthorId($authorId);
        $skillUser->setAcquiredSkillAt(new DateTime());
        $skillUser->setAssignedBy(0);

        $entityManager->persist($skillUser);
        $entityManager->flush();

        if ($badgeAssignationNotification) {
            $url = SkillRelUser::getIssueUrlAll($skillUser);

            $message = sprintf(
                get_lang('YouXHaveAchievedTheSkillYToSeeFollowLinkZ'),
                $user->getFirstname(),
                $skill->getName(),
                Display::url($url, $url, ['target' => '_blank'])
            );

            MessageManager::send_message(
                $user->getId(),
                get_lang('YouHaveAchievedANewSkill'),
                $message
            );
        }

        return $skillUser;
    }
}
