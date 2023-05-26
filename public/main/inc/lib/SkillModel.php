<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\Level;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\SkillRelCourse;
use Chamilo\CoreBundle\Entity\SkillRelGradebook;
use Chamilo\CoreBundle\Entity\SkillRelItem;
use Chamilo\CoreBundle\Entity\SkillRelItemRelUser;
use Chamilo\CoreBundle\Entity\SkillRelSkill;
use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CSurvey;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Vertex;

class SkillModel extends Model
{
    public $columns = [
        'id',
        'name',
        'description',
        'access_url_id',
        'updated_at',
        'short_code',
        'icon',
        'criteria',
    ];
    public array $required = ['name'];

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

        // @todo fix badges icons
        //$path = api_get_path(WEB_UPLOAD_PATH).'badges/';
        $path = '';
        /*if (!empty($result['icon'])) {
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
        }*/

        /*$result['icon_mini'] = $iconMini;
        $result['icon_small'] = $iconSmall;
        $result['icon_big'] = $iconBig;

        $result['img_mini'] = Display::img($iconBig, $result['name'], ['width' => ICON_SIZE_MEDIUM]);
        $result['img_big'] = Display::img($iconBig, $result['name']);
        $result['img_small'] = Display::img($iconSmall, $result['name']);*/
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
    public function processSkillListSimple($skills, $imageSize = 'mini', $style = '', $showBadge = true, $showTitle = true)
    {
        if (empty($skills)) {
            return '';
        }

        $imageParams = '';
        switch ($imageSize) {
            case 'mini':
                $imageParams = '?w='.ICON_SIZE_MEDIUM;
                break;
            case 'small':
                $imageParams = '?w='.ICON_SIZE_BIG;
                break;
            case 'big':
                $imageParams = '?w='.ICON_SIZE_HUGE;
                break;
        }

        $isHierarchicalTable = ('true' === api_get_setting('skill.table_of_hierarchical_skill_presentation'));
        $skillRepo = Container::getSkillRepository();
        $html = '';
        foreach ($skills as $skill) {
            if (isset($skill['data'])) {
                $skill = $skill['data'];
            }

            $item = '';
            if ($showBadge) {
                $skillEntity = $skillRepo->find($skill['id']);
                $url = $this->getWebIconPath($skillEntity);

                $item = '<div class="item"><img src="'.$url.$imageParams.'" /></div>';
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
        $skillRelSkill = new SkillRelSkillModel();
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
    public function getAllSkills(
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

        $skillRepo = Container::getSkillRepository();
        $assetRepo = Container::getAssetRepository();

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
        if (Database::num_rows($result)) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $skillId = $row['id'];
                $skill = $skillRepo->find($skillId);

                $row['asset'] = '';
                if ($skill->getAsset()) {
                    $row['asset'] = $assetRepo->getAssetUrl($skill->getAsset());
                }

                $row['name'] = self::translateName($skill->getName());
                $row['short_code'] = self::translateCode($skill->getShortCode());
                $skillRelSkill = new SkillRelSkillModel();
                $parents = $skillRelSkill->getSkillParents($skillId);
                $row['level'] = count($parents) - 1;
                $row['gradebooks'] = $this->getGradebooksBySkill($skillId);
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
        $skillRelSkill = new SkillRelSkillModel();
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
        $skillRelSkill = new SkillRelSkillModel();
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
        $skillRelSkill = new SkillRelSkillModel();
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
        $skillRelSkill = new SkillRelSkillModel();
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
    public function add(array $params)
    {
        if (empty($params['parent_id'])) {
            $params['parent_id'] = 1;
        }

        if (!is_array($params['parent_id'])) {
            $params['parent_id'] = [$params['parent_id']];
        }

        $skillRelSkill = new SkillRelSkillModel();

        // Saving name, description
        $params['access_url_id'] = api_get_current_access_url_id();
        $params['icon'] = '';

        $skill_id = $this->save($params);
        $em = Database::getManager();
        $repo = $em->getRepository(Skill::class);
        $repoGradebook = $em->getRepository(GradebookCategory::class);

        if ($skill_id) {
            $skill = $repo->find($skill_id);
            // Saving skill_rel_skill (parent_id, relation_type)
            foreach ($params['parent_id'] as $parent_id) {
                $relation_exists = $skillRelSkill->relationExists($skill_id, $parent_id);
                if (!$relation_exists) {
                    $skillRelSkill =
                        (new SkillRelSkill())
                            ->setSkill($skill)
                            ->setParent($repo->find($parent_id))
                            ->setLevel($params['level'] ?? 0)
                            ->setRelationType($params['relation_type'] ?? 0)
                    ;
                    $em->persist($skillRelSkill);
                    $em->flush();
                }
            }

            if (!empty($params['gradebook_id'])) {
                foreach ($params['gradebook_id'] as $gradebook_id) {
                    $skillRelGradebook = (new SkillRelGradebook())
                        ->setGradeBookCategory($repoGradebook->find($gradebook_id))
                        ->setSkill($skill)
                    ;
                    $em->persist($skillRelGradebook);
                    $em->flush();
                }
            }

            return $skill_id;
        }

        return null;
    }

    /**
     * @param int $userId
     * @param int $courseId
     * @param int $sessionId
     *
     * @return bool
     */
    public function addSkillToUser(
        $userId,
        GradebookCategory $category,
        $courseId,
        $sessionId
    ) {
        $skill_rel_user = new SkillRelUserModel();

        // Load subcategories
        if ($category->hasSubCategories()) {
            $subCategories = $category->getSubCategories();
            if (!empty($subCategories)) {
                foreach ($subCategories as $subCategory) {
                    $this->addSkillToUser($userId, $subCategory, $courseId, $sessionId);
                }
            }
        }

        $skills = $category->getSkills();
        if (!empty($skills)) {
            foreach ($skills as $skill) {
                $skillId = $skill->getSkill()->getId();
                $hasSkill = $this->userHasSkill(
                    $userId,
                    $skillId,
                    $courseId,
                    $sessionId
                );

                if (!$hasSkill) {
                    $params = [
                        'user_id' => $userId,
                        'skill_id' => $skillId,
                        'acquired_skill_at' => api_get_utc_datetime(),
                        'course_id' => (int) $courseId,
                        'session_id' => $sessionId ? (int) $sessionId : null,
                    ];
                    $skill_rel_user->save($params);
                }
            }
        }

        return true;
    }

    /* Deletes a skill */
    public function delete($id)
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

    public function edit(array $params)
    {
        if (empty($params['parent_id'])) {
            $params['parent_id'] = 1;
        }

        $params['gradebook_id'] = $params['gradebook_id'] ?? [];

        $skillRelSkill = new SkillRelSkillModel();
        $skillRelGradebook = new SkillRelGradebookModel();

        // Saving name, description
        $this->update($params);
        $skillId = $params['id'];

        if ($skillId) {
            // Saving skill_rel_skill (parent_id, relation_type)
            if (!is_array($params['parent_id'])) {
                $params['parent_id'] = [$params['parent_id']];
            }

            // Cannot change parent of root
            if (1 == $skillId) {
                $params['parent_id'] = 0;
            }

            foreach ($params['parent_id'] as $parent_id) {
                $relation_exists = $skillRelSkill->relationExists($skillId, $parent_id);
                if (!$relation_exists) {
                    $attributes = [
                        'skill_id' => $skillId,
                        'parent_id' => $parent_id,
                        'relation_type' => $params['relation_type'] ?? 0,
                        //'level'         => $params['level'],
                    ];
                    $skillRelSkill->updateBySkill($attributes);
                }
            }

            $skillRelGradebook->updateGradeBookListBySkill($skillId, $params['gradebook_id']);

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
                    s.asset_id,
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
        $isHierarchicalTable = ('true' === api_get_setting('skill.table_of_hierarchical_skill_presentation'));
        $subTable = '';
        if ($vertex->getVerticesEdgeTo()->count() > 0) {
            if ($isHierarchicalTable) {
                $subTable .= '<ul>';
            }
            foreach ($vertex->getVerticesEdgeTo() as $subVertex) {
                $data = $subVertex->getAttribute('graphviz.data');
                $passed = in_array($data['id'], array_keys($skills));
                $transparency = '';
                if (false === $passed) {
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
                    if (2 == $level) {
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
                'skill_id' => $resultData['id'],
                'asset_id' => $resultData['asset_id'],
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

        $isHierarchicalTable = ('true' === api_get_setting('skill.table_of_hierarchical_skill_presentation'));
        $allowLevels = api_get_setting('skill.skill_levels_names', true);

        $tableResult = '<div id="skillList">';
        if ($isHierarchicalTable) {
            $tableResult = '<div class="table-responsive">';
        }

        if ($addTitle) {
            $tableResult .= Display::page_subheader(get_lang('Achieved skills'));
            $tableResult .= '<div class="skills-badges">';
        }

        if (!empty($skillParents)) {
            if (empty($allowLevels)) {
                $tableResult .= $this->processSkillListSimple($skills);
            } else {
                $graph = new Graph();
                $graph->setAttribute('graphviz.graph.rankdir', 'LR');
                foreach ($skillParents as $parentList) {
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
                        if (false === $passed) {
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
                        if (false === $passed) {
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
            $tableResult .= get_lang('Without achieved skills');
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
        if (1 == $skill_id) {
            $skill_id = 0;
        }
        if (isset($user_id) && !empty($user_id)) {
            $skills = $this->getAllSkills(true, $user_id, null, $skill_id);
        } else {
            $skills = $this->getAllSkills(false, false, null, $skill_id);
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
                        'parent_id' => 0,
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
                if (0 == $skill['parent_id']) {
                    $skill['parent_id'] = 1;
                }

                // because except main keys (id, name, children) others keys
                // are not saved while in the space tree
                $skill['data'] = ['parent_id' => $skill['parent_id']];

                // If a short code was defined, send the short code to replace
                // skill name (to shorten the text in the wheel)
                if (!empty($skill['short_code']) &&
                    'false' === api_get_setting('show_full_skill_name_on_skill_wheel')
                ) {
                    $skill['data']['short_code'] = $skill['short_code'];
                }

                $skill['data']['name'] = $skill['name'];
                $skill['data']['status'] = $skill['status'];

                // In order to paint all members of a family with the same color
                if (empty($skill_id)) {
                    if (1 == $skill['parent_id']) {
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
                $refs[1]['children'][0] = $skills[1];
                $skills[$skill_id]['data']['family_id'] = 1;
                $refs[1]['children'][0]['children'][0] = $skills[$skill_id];
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
                'name' => get_lang('Absolute skill'),
                'id' => 1,
                'children' => $refs[1]['children'],
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

        return json_encode($simple_tree);
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
                    SELECT u.id as user_id, firstname, lastname, count(username) skills_acquired
                    FROM {$this->table} s INNER JOIN {$this->table_skill_rel_user} su
                    ON (s.id = su.skill_id)
                    INNER JOIN {$this->table_user} u
                    ON u.id = su.user_id, (SELECT @rownum:=0) r
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
                    ON u.id = su.user_id
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
            $whereConditions['AND course_id = ? '] = $courseId;
            $whereConditions['AND session_id = ? '] = $sessionId ? $sessionId : null;
        }

        $result = Database::select(
            'COUNT(1) AS qty',
            $this->table_skill_rel_user,
            [
                'where' => $whereConditions,
            ],
            'first'
        );

        if (false != $result) {
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

        if (empty($result)) {
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

        if (0 == $courseId) {
            return [];
        }

        $list = [];

        $sql = "SELECT
                    course.id c_id,
                    course.title c_name,
                    course.directory c_directory,
                    user.id as user_id,
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
                ON sru.user_id = user.id
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

        if (0 == $skillId) {
            return [];
        }

        $list = [];
        $sql = "SELECT
                    course.id c_id,
                    course.title c_name,
                    course.directory c_directory,
                    user.id as user_id,
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
                ON sru.user_id = user.id
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
                if ('true' === api_get_setting('allow_session_admins_to_manage_all_sessions')) {
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
                $studentBosses = $toUser->getFriendsByRelationType(UserRelUser::USER_RELATION_TYPE_BOSS);
                //$studentBosses = $userRepo->getStudentBosses($toUser);
                foreach ($studentBosses as $studentBoss) {
                    if ($studentBoss->getFriend()->getId() !== $fromUser->getId()) {
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
        $allowHR = 'true' === api_get_setting('allow_hr_skills_management');

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

        if ('true' === $allowTool) {
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

            $allow = ('true' === api_get_setting('skill.allow_private_skills'));
            if (true === $allow) {
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
        $allSkills = $this->getAllSkills();
        $objGradebook = new Gradebook();

        $skillList = [0 => get_lang('None')];

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

        $translateUrl = api_get_path(WEB_CODE_PATH).'skills/skill_translate.php?';
        $translateNameButton = '';
        $translateCodeButton = '';
        $skillId = null;
        if (!empty($skillInfo)) {
            $skillId = $skillInfo['id'];
            $translateNameUrl = $translateUrl.http_build_query(['skill' => $skillId, 'action' => 'name']);
            $translateCodeUrl = $translateUrl.http_build_query(['skill' => $skillId, 'action' => 'code']);
            $translateNameButton = Display::toolbarButton(
                get_lang('Translate this term'),
                $translateNameUrl,
                'language',
                'link'
            );
            $translateCodeButton = Display::toolbarButton(
                get_lang('Translate this term'),
                $translateCodeUrl,
                'language',
                'link'
            );
        }

        $form->addText('name', [get_lang('Name'), $translateNameButton], true, ['id' => 'name']);
        $form->addText('short_code', [get_lang('Short code'), $translateCodeButton], false, ['id' => 'short_code']);

        // Cannot change parent of root
        if (1 != $skillId) {
            $form->addSelect('parent_id', get_lang('Parent'), $skillList, ['id' => 'parent_id']);
        }

        $form->addSelect(
            'gradebook_id',
            [get_lang('Assessments'), get_lang('With Certificate')],
            $gradeBookList,
            ['id' => 'gradebook_id', 'multiple' => 'multiple', 'size' => 10]
        );
        $form->addTextarea('description', get_lang('Description'), ['id' => 'description', 'rows' => 7]);
        $form->addTextarea('criteria', get_lang('Criteria to earn the badge'), ['id' => 'criteria', 'rows' => 7]);

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
                get_lang('Manage skills'),
                null,
                ICON_SIZE_MEDIUM
            ),
            api_get_path(WEB_CODE_PATH).'skills/skill_list.php'
        );

        return Display::toolbarAction('skills_toolbar', [$toolbar]);
    }

    /**
     * @param SkillRelItem        $skillRelItem
     * @param SkillRelItemRelUser $skillRelItemRelUser
     * @param bool                $addHeader
     *
     * @return string
     */
    public static function getUserSkillStatusLabel($skillRelItem, $skillRelItemRelUser, $addHeader = true)
    {
        if (empty($skillRelItem)) {
            return '';
        }
        $type = 'success';
        if (empty($skillRelItemRelUser)) {
            $type = 'danger';
        }
        $label = '';
        $skill = $skillRelItem->getSkill();
        if ($addHeader) {
            $label .= '<span id="'.$skill->getId().'" class="user_skill" style="cursor:pointer">';
        }
        $label .= Display::label($skill->getName(), $type);
        if ($addHeader) {
            $label .= '</span>&nbsp;';
        }

        return $label;
    }

    /**
     * Assign a user with a SkilRelItem object.
     *
     * @param int $typeId see ITEM_TYPE_* constants
     * @param int $itemId
     * @param int $userId
     */
    public static function addSkillsToUserForm(
        FormValidator $form,
        $typeId,
        $itemId,
        $userId,
        $resultId = 0,
        $addHeader = false
    ) {
        $allowSkillInTools = ('true' === api_get_setting('skill.allow_skill_rel_items'));
        if ($allowSkillInTools && !empty($typeId) && !empty($itemId) && !empty($userId)) {
            $em = Database::getManager();
            $items = $em->getRepository(SkillRelItem::class)->findBy(
                ['itemId' => $itemId, 'itemType' => $typeId]
            );

            $skillRelUser = new SkillRelUserModel();
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
                $skillRelItemRelUser = $em->getRepository(SkillRelItemRelUser::class)->findOneBy($criteria);
                $skills .= self::getUserSkillStatusLabel($skillRelItem, $skillRelItemRelUser);
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
                $html = '
                <script>
                    $(function() {
                        $(".user_skill").on("click", function() {
                            var skillId = this.id;
                            var params = '.$params.';
                            $.ajax({
                                type: "GET",
                                async: false,
                                data: params,
                                url: "'.$url.'&skill_id="+skillId,
                                success: function(result) {
                                    $("#" +skillId+ ".user_skill").html(result);
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
     * Add skills select ajax for an item (exercise, lp).
     *
     * @param int $typeId see ITEM_TYPE_* constants
     * @param int $itemId
     *
     * @throws Exception
     *
     * @return array
     */
    public static function addSkillsToForm(FormValidator $form, $typeId, $itemId = 0)
    {
        $allowSkillInTools = ('true' === api_get_setting('skill.allow_skill_rel_items'));
        if (!$allowSkillInTools) {
            return [];
        }

        $skillList = [];
        if (!empty($itemId)) {
            $em = Database::getManager();
            $items = $em->getRepository(SkillRelItem::class)->findBy(
                ['itemId' => $itemId, 'itemType' => $typeId]
            );
            /** @var SkillRelItem $skillRelItem */
            foreach ($items as $skillRelItem) {
                $skillList[$skillRelItem->getSkill()->getId()] = $skillRelItem->getSkill()->getName();
            }
        }

        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();

        $url = api_get_path(WEB_AJAX_PATH).
            'skill.ajax.php?a=search_skills_in_course&course_id='.$courseId.'&session_id='.$sessionId;
        $form->addSelectAjax(
            'skills',
            get_lang('Skills'),
            $skillList,
            [
                'url' => $url,
                'multiple' => 'multiple',
            ]
        );

        return $skillList;
    }

    /**
     * @param int $courseId
     * @param int $sessionId
     *
     * @return array
     */
    public static function getSkillRelItemsPerCourse($courseId, $sessionId = null)
    {
        $allowSkillInTools = ('true' === api_get_setting('skill.allow_skill_rel_items'));
        $skills = [];

        if (empty($sessionId)) {
            $sessionId = null;
        }

        if ($allowSkillInTools) {
            $em = Database::getManager();
            $skills = $em->getRepository(SkillRelItem::class)->findBy(
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
                /** @var CQuiz $item */
                $item = $em->getRepository(CQuiz::class)->find($itemId);
                if ($item) {
                    $itemInfo['name'] = $item->getTitle();
                }
                break;
            case ITEM_TYPE_HOTPOTATOES:
                break;
            case ITEM_TYPE_LINK:
                /** @var CLink $item */
                $item = $em->getRepository(CLink::class)->find($itemId);
                if ($item) {
                    $itemInfo['name'] = $item->getTitle();
                }
                break;
            case ITEM_TYPE_LEARNPATH:
                /** @var CLp $item */
                $item = $em->getRepository(CLp::class)->find($itemId);
                if ($item) {
                    $itemInfo['name'] = $item->getName();
                }
                break;
            case ITEM_TYPE_GRADEBOOK:
                break;
            case ITEM_TYPE_STUDENT_PUBLICATION:
                /** @var CStudentPublication $item */
                $item = $em->getRepository(CStudentPublication::class)->find($itemId);
                if ($item) {
                    $itemInfo['name'] = $item->getTitle();
                }
                break;
            //ITEM_TYPE_FORUM', 7);
            case ITEM_TYPE_ATTENDANCE:
                /** @var CAttendance $item */
                $item = $em->getRepository(CAttendance::class)->find($itemId);
                if ($item) {
                    $itemInfo['name'] = $item->getName();
                }
                break;
            case ITEM_TYPE_SURVEY:
                /** @var CSurvey $item */
                $item = $em->getRepository(CSurvey::class)->find($itemId);
                if ($item) {
                    $itemInfo['name'] = strip_tags($item->getTitle());
                }
                break;
            case ITEM_TYPE_FORUM_THREAD:
                /** @var CForumThread $item */
                $item = $em->getRepository(CForumThread::class)->find($itemId);
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
        $allowSkillInTools = ('true' === api_get_setting('skill.allow_skill_rel_items'));
        $skills = [];
        if ($allowSkillInTools) {
            $em = Database::getManager();
            $skills = $em->getRepository(SkillRelItem::class)->findBy(
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
        $allowSkillInTools = ('true' === api_get_setting('skill.allow_skill_rel_items'));
        if ($allowSkillInTools) {
            $itemId = (int) $itemId;
            $typeId = (int) $typeId;

            $em = Database::getManager();
            // Delete old ones
            $items = $em->getRepository(SkillRelItem::class)->findBy(
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
     * Relate skill with an item (exercise, gradebook, lp, etc).
     *
     * @param FormValidator $form
     * @param int           $typeId
     * @param int           $itemId
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public static function saveSkills($form, $typeId, $itemId)
    {
        $allowSkillInTools = ('true' === api_get_setting('skill.allow_skill_rel_items'));
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

            // Delete old ones
            $items = $em->getRepository(SkillRelItem::class)->findBy(
                ['itemId' => $itemId, 'itemType' => $typeId]
            );
            if (!empty($items)) {
                /** @var SkillRelItem $skillRelItem */
                foreach ($items as $skillRelItem) {
                    if (!in_array($skillRelItem->getSkill()->getId(), $skills)) {
                        $em->remove($skillRelItem);
                    }
                }
                $em->flush();
            }

            // Add new one
            if (!empty($skills)) {
                foreach ($skills as $skillId) {
                    /** @var Skill $skill */
                    $skill = $em->getRepository(Skill::class)->find($skillId);
                    if ($skill) {
                        if (!$skill->hasItem($typeId, $itemId)) {
                            $skillRelItem = new SkillRelItem();
                            $skillRelItem
                                ->setItemType($typeId)
                                ->setItemId($itemId)
                                ->setCourseId($courseId)
                                ->setSessionId($sessionId)
                                ->setCreatedBy($userId)
                                ->setUpdatedBy($userId)
                            ;
                            $skill->addItem($skillRelItem);
                            $em->persist($skill);
                            $em->flush();
                        }
                    }
                }
            }
        }
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
        $sessionId = $form->getSubmitValue('session_id');

        return self::saveSkillsToCourse($skills, $courseId, $sessionId);
    }

    /**
     * @param array $skills
     * @param int   $courseId
     * @param int   $sessionId
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return bool
     */
    public static function saveSkillsToCourse($skills, $courseId, $sessionId)
    {
        $allowSkillInTools = ('true' === api_get_setting('skill.allow_skill_rel_items'));
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
        $items = $em->getRepository(SkillRelCourse::class)->findBy(
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
                $item = new SkillRelCourse();
                $item->setCourse($course);
                $item->setSession($session);

                /** @var Skill $skill */
                $skill = $em->getRepository(Skill::class)->find($skillId);
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
     * Get the icon (badge image) URL.
     */
    public static function getWebIconPath(?Skill $skill): string
    {
        $default = \Display::return_icon('badges-default.png', null, null, ICON_SIZE_HUGE, null, true);

        if (null === $skill) {
            return $default;
        }

        if (!$skill->hasAsset()) {
            return $default;
        }

        return Container::getAssetRepository()->getAssetUrl($skill->getAsset());
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addSkillToUserBadge(
        User $user,
        Skill $skill,
        int $levelId,
        string $argumentation,
        int $authorId
    ): ?SkillRelUser {
        $showLevels = ('false' === api_get_setting('skill.hide_skill_levels'));

        $entityManager = Database::getManager();

        $skillUserRepo = $entityManager->getRepository(SkillRelUser::class);

        $criteria = ['user' => $user, 'skill' => $skill];
        $result = $skillUserRepo->findOneBy($criteria);

        if (null !== $result) {
            return null;
        }

        $skillLevelRepo = $entityManager->getRepository(Level::class);

        $skillUser = (new SkillRelUser())
            ->setUser($user)
            ->setSkill($skill)
            ->setArgumentation($argumentation)
            ->setArgumentationAuthorId($authorId)
            ->setAssignedBy(0)
        ;

        if ($showLevels && !empty($levelId)) {
            $level = $skillLevelRepo->find($levelId);
            $skillUser->setAcquiredLevel($level);
        }

        $entityManager->persist($skillUser);
        $entityManager->flush();

        return $skillUser;
    }

    public static function setBackPackJs(&$htmlHeadXtra)
    {
        $backpack = 'https://backpack.openbadges.org/';
        $configBackpack = api_get_setting('openbadges_backpack');

        if (0 !== strcmp($backpack, $configBackpack)) {
            $backpack = $configBackpack;
            if ('/' !== substr($backpack, -1)) {
                $backpack .= '/';
            }
        }
        $htmlHeadXtra[] = '<script src="'.$backpack.'issuer.js"></script>';
    }

    public static function exportBadge(Skill $skill, SkillRelUser $skillRelUser, string $urlToRedirect)
    {
        if ($skill->hasAsset()) {
            $assetRepo = Container::getAssetRepository();
            $imageContent = $assetRepo->getAssetContent($skill->getAsset());
        } else {
            $defaultBadge = api_get_path(SYS_PUBLIC_PATH).'img/icons/128/badges-default.png';
            $imageContent = file_get_contents($defaultBadge);
        }

        $png = new PNGImageBaker($imageContent);

        if ($png->checkChunks('tEXt', 'openbadges')) {
            $result = $png->addChunk('tEXt', 'openbadges', SkillRelUserModel::getAssertionUrl($skillRelUser));
            $verifyBadge = $png->extractBadgeInfo($result);

            $error = true;
            if (is_array($verifyBadge)) {
                $error = false;
            }

            if (!$error) {
                //header('Content-type: image/png');
                header('Content-type: application/octet-stream');
                header('Content-Type: application/force-download');
                header('Content-Disposition: attachment; filename= '.api_get_unique_id());

                echo $result;
                exit;
            }
        }

        Display::addFlash(
            Display::return_message(
                get_lang(
                    'There was a problem embedding the badge assertion info into the badge image, but you can still use this page as a valid proof.'
                ),
                'warning'
            )
        );
        api_location($urlToRedirect);
    }

}
