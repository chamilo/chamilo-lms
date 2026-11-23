<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * Gives the console commands that send learner notifications (achievement certificates, final
 * items of learning paths) the public address their links must use. A CLI run has no request to
 * read it from, and on a multi-URL platform each learner may have followed the course on another
 * portal.
 */
readonly class LearnerPortalUrlHelper
{
    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * The portal where the learner followed the course, or the course inside its session: no
     * tracking table records it, so it is deduced from the portals the learner belongs to and
     * those of the session, or of the course outside a session. When several remain, the portal
     * where the learner was last seen online wins, then the first one.
     */
    public function resolve(int $courseId, int $sessionId, int $userId): ?string
    {
        $activeUrls = [];
        foreach ($this->connection->fetchAllAssociative('SELECT id, url FROM access_url WHERE active = 1 ORDER BY id') as $row) {
            if ('' !== trim((string) $row['url'])) {
                $activeUrls[(int) $row['id']] = (string) $row['url'];
            }
        }

        $userUrlIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT access_url_id FROM access_url_rel_user WHERE user_id = :userId',
            ['userId' => $userId]
        ));
        $contextUrlIds = array_map('intval', $sessionId > 0
            ? $this->connection->fetchFirstColumn(
                'SELECT access_url_id FROM access_url_rel_session WHERE session_id = :sessionId',
                ['sessionId' => $sessionId]
            )
            : $this->connection->fetchFirstColumn(
                'SELECT access_url_id FROM access_url_rel_course WHERE c_id = :courseId',
                ['courseId' => $courseId]
            ));

        $candidates = array_values(array_intersect($userUrlIds, $contextUrlIds)) ?: ($contextUrlIds ?: $userUrlIds);
        $candidates = array_values(array_filter(
            array_unique($candidates),
            static fn (int $urlId): bool => isset($activeUrls[$urlId])
        ));
        if ([] === $candidates) {
            return null;
        }

        if (\count($candidates) > 1) {
            $lastSeenUrlId = $this->connection->fetchOne(
                'SELECT access_url_id
                 FROM track_e_online
                 WHERE login_user_id = :userId
                   AND access_url_id IN (:urlIds)
                 ORDER BY login_date DESC
                 LIMIT 1',
                ['userId' => $userId, 'urlIds' => $candidates],
                ['urlIds' => ArrayParameterType::INTEGER]
            );
            if (false !== $lastSeenUrlId && null !== $lastSeenUrlId) {
                return $activeUrls[(int) $lastSeenUrlId];
            }

            sort($candidates);
        }

        return $activeUrls[$candidates[0]];
    }
}
