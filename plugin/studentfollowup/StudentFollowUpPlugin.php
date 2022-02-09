<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\PluginBundle\Entity\StudentFollowUp\CarePost;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Class StudentFollowUpPlugin.
 */
class StudentFollowUpPlugin extends Plugin
{
    public $hasEntity = true;

    /**
     * StudentFollowUpPlugin constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            '0.1',
            'Julio Montoya',
            [
                'tool_enable' => 'boolean',
            ]
        );
    }

    /**
     * @return StudentFollowUpPlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    public function install()
    {
        $em = Database::getManager();

        if ($em->getConnection()->getSchemaManager()->tablesExist(['sfu_post'])) {
            return;
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema(
            [
                $em->getClassMetadata(CarePost::class),
            ]
        );
    }

    public function uninstall()
    {
        $em = Database::getManager();

        if (!$em->getConnection()->getSchemaManager()->tablesExist(['sfu_post'])) {
            return;
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema(
            [
                $em->getClassMetadata(CarePost::class),
            ]
        );
    }

    /**
     * @param int $studentId
     * @param int $currentUserId
     *
     * @return array
     */
    public static function getPermissions($studentId, $currentUserId)
    {
        $installed = AppPlugin::getInstance()->isInstalled('studentfollowup');
        if ($installed === false) {
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
            $isDrhRelatedViaPost = false;
            $isCourseCoach = false;
            $isDrhRelatedToSession = false;

            // Only admins and DRH that follow the user.
            $isAdmin = api_is_platform_admin();

            // Check if user is care taker.
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
            }

            // Student sessions.
            $sessions = SessionManager::get_sessions_by_user($studentId, true, true);
            if (!empty($sessions)) {
                foreach ($sessions as $session) {
                    $sessionId = $session['session_id'];
                    // Check if the current user is following that session.
                    $sessionDrhInfo = SessionManager::getSessionFollowedByDrh(
                        $currentUserId,
                        $sessionId
                    );
                    if (!empty($sessionDrhInfo)) {
                        $isDrhRelatedToSession = true;
                        break;
                    }

                    // Check if teacher is coach between the date limits.
                    $visibility = api_get_session_visibility(
                        $sessionId,
                        null,
                        true,
                        $currentUserId
                    );

                    if (SESSION_AVAILABLE === $visibility && isset($session['courses']) && !empty($session['courses'])) {
                        foreach ($session['courses'] as $course) {
                            $coachList = SessionManager::getCoachesByCourseSession(
                                $sessionId,
                                $course['real_id']
                            );
                            if (!empty($coachList) && in_array($currentUserId, $coachList)) {
                                $isCourseCoach = true;
                                break 2;
                            }
                        }
                    }
                }
            }

            $isCareTaker = $isDrhRelatedViaPost && $isDrhRelatedToSession;
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
     * @param int    $currentUserId
     * @param int    $sessionId
     * @param int    $start
     * @param int    $limit
     *
     * @return array
     */
    public static function getUsers($status, $currentUserId, $sessionId, $start, $limit)
    {
        $sessions = [];
        $courses = [];
        $sessionsFull = [];

        switch ($status) {
            case COURSEMANAGER:
                $sessionsFull = SessionManager::getSessionsCoachedByUser($currentUserId);
                $sessions = array_column($sessionsFull, 'id');
                if (!empty($sessionId)) {
                    $sessions = [$sessionId];
                }
                // Get session courses where I'm coach
                $courseList = SessionManager::getCoursesListByCourseCoach($currentUserId);
                $courses = [];
                /** @var SessionRelCourseRelUser $courseItem */
                foreach ($courseList as $courseItem) {
                    $courses[] = $courseItem->getCourse()->getId();
                }
                break;
            case DRH:
                $sessionsFull = SessionManager::get_sessions_followed_by_drh($currentUserId);
                $sessions = array_column($sessionsFull, 'id');

                if (!empty($sessionId)) {
                    $sessions = [$sessionId];
                }
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

        return [
            'users' => $userList,
            'sessions' => $sessionsFull,
        ];
    }

    /**
     * @return int
     */
    public static function getPageSize()
    {
        return 20;
    }

    /**
     * @param int $userId
     */
    public function doWhenDeletingUser($userId)
    {
        $userId = (int) $userId;

        Database::query("DELETE FROM sfu_post WHERE user_id = $userId");
        Database::query("DELETE FROM sfu_post WHERE insert_user_id = $userId");
    }
}
