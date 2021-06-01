<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\SkillRelUser;

class SkillRelUserModel extends Model
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

        if ($courseId > 0) {
            $whereConditions['AND course_id = ? '] = $courseId;
            $whereConditions['AND session_id = ?'] = $sessionId;
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
     * Get the URL for the issue.
     *
     * @return string
     */
    public static function getIssueUrl(SkillRelUser $skillIssue)
    {
        return api_get_path(WEB_PATH)."badge/{$skillIssue->getId()}";
    }

    /**
     * Get the URL for the All issues page.
     *
     * @return string
     */
    public static function getIssueUrlAll(SkillRelUser $skillIssue)
    {
        return api_get_path(WEB_PATH)."skill/{$skillIssue->getSkill()->getId()}/user/{$skillIssue->getUser()->getId()}";
    }

    /**
     * Get the URL for the assertion.
     *
     * @return string
     */
    public static function getAssertionUrl(SkillRelUser $skillIssue)
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
