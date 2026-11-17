<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

/**
 * Read-only scope for session-manager integrations. Never expands ROLE_ADMIN privileges.
 * Session managers can read data only in the URL serving the request and only for
 * their assigned sessions unless the existing "manage all sessions" setting is on.
 */
final readonly class SessionManagerApiScope
{
    public function __construct(
        private Connection $connection,
        private AccessUrlHelper $accessUrlHelper,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @return int[]
     */
    public function getSessionIds(User $actor): array
    {
        $urlId = $this->currentUrlIdForActor($actor);
        if (null === $urlId || null === $actor->getId()) {
            return [];
        }

        $sql = 'SELECT DISTINCT r.session_id FROM access_url_rel_session r';
        $params = ['urlId' => $urlId];
        $types = ['urlId' => Types::INTEGER];

        if (!$this->mayManageAllSessions()) {
            $sql .= ' INNER JOIN session_rel_user sr
                       ON sr.session_id = r.session_id
                      AND sr.user_id = :actorId
                      AND sr.relation_type = :adminRelation';
            $params['actorId'] = (int) $actor->getId();
            $params['adminRelation'] = Session::SESSION_ADMIN;
            $types['actorId'] = Types::INTEGER;
            $types['adminRelation'] = Types::INTEGER;
        }

        $sql .= ' WHERE r.access_url_id = :urlId';

        return array_map('intval', $this->connection->fetchFirstColumn($sql, $params, $types));
    }

    /**
     * @return int[]
     */
    public function getCourseIds(User $actor): array
    {
        $urlId = $this->currentUrlIdForActor($actor);
        if (null === $urlId || null === $actor->getId()) {
            return [];
        }

        $sql = 'SELECT DISTINCT sc.c_id
                  FROM session_rel_course sc
                  INNER JOIN access_url_rel_session rs
                          ON rs.session_id = sc.session_id AND rs.access_url_id = :urlId
                  INNER JOIN access_url_rel_course rc
                          ON rc.c_id = sc.c_id AND rc.access_url_id = :urlId';
        $params = ['urlId' => $urlId];
        $types = ['urlId' => Types::INTEGER];

        if (!$this->mayManageAllSessions()) {
            $sql .= ' INNER JOIN session_rel_user sr
                            ON sr.session_id = sc.session_id
                           AND sr.user_id = :actorId
                           AND sr.relation_type = :adminRelation';
            $params['actorId'] = (int) $actor->getId();
            $params['adminRelation'] = Session::SESSION_ADMIN;
            $types['actorId'] = Types::INTEGER;
            $types['adminRelation'] = Types::INTEGER;
        }

        return array_map('intval', $this->connection->fetchFirstColumn($sql, $params, $types));
    }

    public function canReadSession(User $actor, Session $session): bool
    {
        return null !== $session->getId()
            && \in_array((int) $session->getId(), $this->getSessionIds($actor), true);
    }

    public function canReadCourse(User $actor, Course $course): bool
    {
        return null !== $course->getId()
            && \in_array((int) $course->getId(), $this->getCourseIds($actor), true);
    }

    public function canReadExtraField(User $actor, ExtraField $field): bool
    {
        return match ($field->getItemType()) {
            ExtraField::COURSE_FIELD_TYPE => [] !== $this->getCourseIds($actor),
            ExtraField::SESSION_FIELD_TYPE => [] !== $this->getSessionIds($actor),
            default => false,
        };
    }

    public function canReadExtraFieldValue(User $actor, ExtraFieldValues $value): bool
    {
        $itemId = $value->getItemId();

        return match ($value->getField()->getItemType()) {
            ExtraField::COURSE_FIELD_TYPE => \in_array($itemId, $this->getCourseIds($actor), true),
            ExtraField::SESSION_FIELD_TYPE => \in_array($itemId, $this->getSessionIds($actor), true),
            default => false,
        };
    }

    private function currentUrlIdForActor(User $actor): ?int
    {
        $urlId = $this->accessUrlHelper->getCurrent()?->getId();
        $actorId = $actor->getId();
        if (null === $urlId || null === $actorId) {
            return null;
        }

        // A JWT or service token must not allow a manager from one portal to read
        // another portal merely because "manage all sessions" is enabled.
        $registered = $this->connection->fetchOne(
            'SELECT 1 FROM access_url_rel_user WHERE user_id = :actorId AND access_url_id = :urlId LIMIT 1',
            ['actorId' => (int) $actorId, 'urlId' => (int) $urlId],
            ['actorId' => Types::INTEGER, 'urlId' => Types::INTEGER],
        );

        return false === $registered ? null : (int) $urlId;
    }

    private function mayManageAllSessions(): bool
    {
        return 'true' === $this->settingsManager->getSetting('session.allow_session_admins_to_manage_all_sessions', true);
    }
}
