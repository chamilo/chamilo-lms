<?php
/* For licensing terms, see /license.txt */

use Symfony\Component\Filesystem\Filesystem;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;

/**
 * Class StudentFollowUpPlugin
 */
class StudentFollowUpPlugin extends Plugin
{
    public $hasEntity = true;

    /**
     * @return StudentFollowUpPlugin
     */
    public static function create()
    {
        static $result = null;
        return $result ? $result : $result = new self();
    }

    /**
     * StudentFollowUpPlugin constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            '0.1',
            'Julio Montoya',
            array(
                'tool_enable' => 'boolean',
            )
        );
    }

    /**
     *
     */
    public function install()
    {
        $pluginEntityPath = $this->getEntityPath();
        if (!is_dir($pluginEntityPath)) {
            mkdir($pluginEntityPath, api_get_permissions_for_new_directories());
        }

        $fs = new Filesystem();
        $fs->mirror(__DIR__.'/Entity/', $pluginEntityPath, null, ['override']);

        $sql = "CREATE TABLE sfu_post (id INT AUTO_INCREMENT NOT NULL, insert_user_id INT NOT NULL, user_id INT NOT NULL, parent_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT DEFAULT NULL, external_care_id VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, private TINYINT(1) NOT NULL, external_source TINYINT(1) NOT NULL, tags LONGTEXT NOT NULL COMMENT '(DC2Type:array)', attachment VARCHAR(255) NOT NULL, lft INT DEFAULT NULL, rgt INT DEFAULT NULL, lvl INT DEFAULT NULL, root INT DEFAULT NULL, INDEX IDX_35F9473C9C859CC3 (insert_user_id), INDEX IDX_35F9473CA76ED395 (user_id), INDEX IDX_35F9473C727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;";
        Database::query($sql);
        $sql = "ALTER TABLE sfu_post ADD CONSTRAINT FK_35F9473C9C859CC3 FOREIGN KEY (insert_user_id) REFERENCES user (id);";
        Database::query($sql);
        $sql = "ALTER TABLE sfu_post ADD CONSTRAINT FK_35F9473CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id);";
        Database::query($sql);
        $sql = "ALTER TABLE sfu_post ADD CONSTRAINT FK_35F9473C727ACA70 FOREIGN KEY (parent_id) REFERENCES sfu_post (id) ON DELETE SET NULL;";
        Database::query($sql);
    }

    /**
     *
     */
    public function uninstall()
    {
        $pluginEntityPath = $this->getEntityPath();
        $fs = new Filesystem();
        if ($fs->exists($pluginEntityPath)) {
            $fs->remove($pluginEntityPath);
        }
        $table = Database::get_main_table('sfu_post');
        $sql = "DROP TABLE IF EXISTS $table";
        Database::query($sql);
    }

    /**
     * @return string
     */
    public function getEntityPath()
    {
        return api_get_path(SYS_PATH).'src/Chamilo/PluginBundle/Entity/'.$this->getCamelCaseName();
    }

    /**
     * @param int $studentId
     * @param int $currentUserId
     * @return array
     */
    public static function getPermissions($studentId, $currentUserId)
    {
        $params = ['variable = ? AND subkey = ?' => ['status', 'studentfollowup']];
        $result = api_get_settings_params_simple($params);
        $installed = false;
        if (!empty($result) && $result['selected_value'] === 'installed') {
            $installed = true;
        }

        if ($installed == false) {
            return [
                'is_allow' => false,
                'show_private' => false,
            ];
        }

        if ($studentId === $currentUserId) {
            $isAllow = true;
            $showPrivate = true;
        } else {
            $isDrh = api_is_drh();
            $isCareTaker = false;
            $isDrhRelatedViaPost = false;
            $isCourseCoach = false;
            $isDrhRelatedToSession = false;

            // Only admins and DRH that follow the user
            $isAdmin = api_is_platform_admin();

            // Check if user is care taker
            if ($isDrh) {
                $criteria = [
                    'user' => $studentId,
                    'insertUser' => $currentUserId,
                ];
                $repo = Database::getManager()->getRepository('ChamiloPluginBundle:StudentFollowUp\CarePost');
                $post = $repo->findOneBy($criteria);
                if ($post) {
                    $isDrhRelatedViaPost = true;
                }

                // Check if course session coach
                $sessions = SessionManager::get_sessions_by_user($studentId);

                if (!empty($sessions)) {
                    foreach ($sessions as $session) {
                        $sessionId = $session['session_id'];
                        $sessionDrhInfo = SessionManager::getSessionFollowedByDrh(
                            $currentUserId,
                            $sessionId
                        );
                        if (!empty($sessionDrhInfo)) {
                            $isDrhRelatedToSession = true;
                            break;
                        }
                        foreach ($session['courses'] as $course) {
                            //$isCourseCoach = api_is_coach($sessionId, $course['real_id']);
                            $coachList = SessionManager::getCoachesByCourseSession(
                                $sessionId,
                                $course['real_id']
                            );
                            if (!empty($coachList) && in_array($currentUserId, $coachList)) {
                                $isCourseCoach = true;
                                break(2);
                            }
                        }
                    }
                }

                $isCareTaker = $isDrhRelatedViaPost && $isDrhRelatedToSession;
            }

            $isAllow = $isAdmin || $isCareTaker || $isDrhRelatedToSession || $isCourseCoach;
            $showPrivate = $isAdmin || $isCareTaker;
        }

        return [
            'is_allow' => $isAllow,
            'show_private' => $showPrivate,
        ];
    }

    /**
     * @param string $status
     * @param int $currentUserId
     * @param int $start
     * @param int $limit
     *
     * @return array
     */
    public static function getUsers($status, $currentUserId, $start, $limit)
    {
        switch ($status) {
            case COURSEMANAGER:
                $sessions = SessionManager::get_sessions_by_user($currentUserId);
                $sessions = array_column($sessions, 'session_id');

                // Get session courses where I'm coach
                $courseList = SessionManager::getCoursesListByCourseCoach($currentUserId);
                $courses = [];
                /** @var SessionRelCourseRelUser $courseItem */
                foreach ($courseList as $courseItem) {
                    $courses[] = $courseItem->getCourse()->getId();
                }
                break;
            case DRH:
                $sessions = SessionManager::get_sessions_followed_by_drh($currentUserId);
                $sessions = array_column($sessions, 'id');
                $courses = [];
                foreach ($sessions as $sessionId) {
                    $sessionDrhInfo = SessionManager::getSessionFollowedByDrh(
                        $currentUserId,
                        $sessionId
                    );
                    if ($sessionDrhInfo && isset($sessionDrhInfo['course_list'])) {
                        $courses = array_merge($courses, array_column($sessionDrhInfo['course_list'], 'id'));
                    }
                }
                break;
        }

        $userList = SessionManager::getUsersByCourseAndSessionList(
            $sessions,
            $courses,
            $start,
            $limit
        );

        /*$userList = [];
        foreach ($sessions as $sessionId) {
            foreach ($courses as $courseId) {
                $courseInfo = ['real_id' => $courseId];
                $userFromSessionList = SessionManager::getUsersByCourseSession(
                    $sessionId,
                    $courseInfo
                );
                $userList = array_merge($userList, $userFromSessionList);
            }
            $userList = array_unique($userList);
        }*/

        return $userList;
    }

    /**
     * @return int
     */
    public static function getPageSize()
    {
        return 2;
    }
}
