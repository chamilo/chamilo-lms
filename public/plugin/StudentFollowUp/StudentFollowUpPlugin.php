<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\PluginBundle\StudentFollowUp\Entity\CarePost;

/**
 * Class StudentFollowUpPlugin.
 */
class StudentFollowUpPlugin extends Plugin
{
    public const TABLE_POST = 'sfu_post';

    public $hasEntity = true;

    protected function __construct()
    {
        parent::__construct(
            '0.1',
            'Julio Montoya',
            []
        );
    }

    public static function create(): self
    {
        static $result = null;

        return $result ??= new self();
    }

    public function install(): void
    {
        $schema = Database::getManager()->getConnection()->createSchemaManager();

        if ($schema->tablesExist([self::TABLE_POST])) {
            return;
        }

        Database::query(
            "CREATE TABLE IF NOT EXISTS sfu_post (
                id INT AUTO_INCREMENT NOT NULL,
                insert_user_id INT NOT NULL,
                user_id INT NOT NULL,
                parent_id INT DEFAULT NULL,
                title VARCHAR(255) NOT NULL,
                content LONGTEXT DEFAULT NULL,
                external_care_id VARCHAR(255) DEFAULT NULL,
                created_at DATETIME DEFAULT NULL,
                updated_at DATETIME DEFAULT NULL,
                private TINYINT(1) NOT NULL,
                external_source TINYINT(1) NOT NULL,
                tags LONGTEXT NOT NULL COMMENT '(DC2Type:array)',
                attachment VARCHAR(255) NOT NULL,
                lft INT DEFAULT NULL,
                rgt INT DEFAULT NULL,
                lvl INT DEFAULT NULL,
                root INT DEFAULT NULL,
                INDEX IDX_35F9473C9C859CC3 (insert_user_id),
                INDEX IDX_35F9473CA76ED395 (user_id),
                INDEX IDX_35F9473C727ACA70 (parent_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;"
        );

        Database::query(
            'ALTER TABLE sfu_post ADD CONSTRAINT FK_35F9473C9C859CC3 FOREIGN KEY (insert_user_id) REFERENCES user (id);'
        );
        Database::query(
            'ALTER TABLE sfu_post ADD CONSTRAINT FK_35F9473CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id);'
        );
        Database::query(
            'ALTER TABLE sfu_post ADD CONSTRAINT FK_35F9473C727ACA70 FOREIGN KEY (parent_id) REFERENCES sfu_post (id) ON DELETE SET NULL;'
        );
    }

    public function uninstall(): void
    {
        Database::query('DROP TABLE IF EXISTS '.self::TABLE_POST);
    }

    public static function getPermissions(int $studentId, int $currentUserId): array
    {
        $installed = AppPlugin::getInstance()->isInstalled('StudentFollowUp') || AppPlugin::getInstance()->isInstalled('studentfollowup');
        if (false === $installed) {
            return [
                'is_allow' => false,
                'show_private' => false,
            ];
        }

        if ($studentId === $currentUserId) {
            return [
                'is_allow' => true,
                'show_private' => true,
            ];
        }

        $isDrh = api_is_drh();
        $isDrhRelatedViaPost = false;
        $isCourseCoach = false;
        $isDrhRelatedToSession = false;
        $isAdmin = api_is_platform_admin();

        if ($isDrh) {
            $criteria = [
                'user' => $studentId,
                'insertUser' => $currentUserId,
            ];
            $repo = Database::getManager()->getRepository(CarePost::class);
            $post = $repo->findOneBy($criteria);
            if ($post) {
                $isDrhRelatedViaPost = true;
            }
        }

        $sessions = SessionManager::get_sessions_by_user($studentId, false, true);
        if (!empty($sessions)) {
            foreach ($sessions as $session) {
                $sessionId = (int) $session['session_id'];
                $sessionDrhInfo = SessionManager::getSessionFollowedByDrh(
                    $currentUserId,
                    $sessionId
                );
                if (!empty($sessionDrhInfo)) {
                    $isDrhRelatedToSession = true;

                    break;
                }

                foreach ($session['courses'] as $course) {
                    $coachList = SessionManager::getCoachesByCourseSession(
                        $sessionId,
                        (int) $course['real_id']
                    );
                    if (!empty($coachList) && in_array($currentUserId, $coachList, true)) {
                        $isCourseCoach = true;

                        break 2;
                    }
                }
            }
        }

        $isCareTaker = $isDrhRelatedViaPost && $isDrhRelatedToSession;
        $isAllow = $isAdmin || $isCareTaker || $isDrhRelatedToSession || $isCourseCoach;
        $showPrivate = $isAdmin || $isCareTaker;

        return [
            'is_allow' => $isAllow,
            'show_private' => $showPrivate,
        ];
    }

    public static function getUsers(string $status, int $currentUserId, int $sessionId, int $start, int $limit): array
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

                $courseList = SessionManager::getCoursesListByCourseCoach($currentUserId);
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

                foreach ($sessions as $drhSessionId) {
                    $sessionDrhInfo = SessionManager::getSessionFollowedByDrh(
                        $currentUserId,
                        (int) $drhSessionId
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
            STUDENT,
            null,
            null
        );

        return [
            'users' => $userList,
            'sessions' => $sessionsFull,
        ];
    }


    public static function normalizeLegacyTags(): void
    {
        Database::query(
            "UPDATE sfu_post
             SET tags = 'a:0:{}'
             WHERE tags IS NULL
                OR tags = ''
                OR tags = '0'
                OR tags = 'b:0;'"
        );
    }

    public static function getPageSize(): int
    {
        return 20;
    }

    public function doWhenDeletingUser($userId): void
    {
        $userId = (int) $userId;

        Database::query('DELETE FROM sfu_post WHERE user_id = '.$userId);
        Database::query('DELETE FROM sfu_post WHERE insert_user_id = '.$userId);
    }
}
