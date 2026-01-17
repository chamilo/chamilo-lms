<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Repository\ResourceWithLinkInterface;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\RouterInterface;
use Throwable;

final class CGlossaryRepository extends ResourceRepository implements ResourceWithLinkInterface
{
    private Connection $connection;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CGlossary::class);

        $this->connection = $this->getEntityManager()->getConnection();
    }

    /*public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroup $group = null): QueryBuilder
    {
        return $this->getResourcesByCourse($course, $session, $group, $parentNode);
    }*/

    public function getLink(ResourceInterface $resource, RouterInterface $router, array $extraParams = []): string
    {
        $params = [
            'glossary_id' => $resource->getResourceIdentifier(),
        ];
        if (!empty($extraParams)) {
            $params = array_merge($params, $extraParams);
        }

        return '/main/glossary/index.php?'.http_build_query($params);
    }

    /**
     * Best-effort extraction from Chamilo "course description" tool table.
     * Tries the session-specific record first (if sid>0), then session_id=0.
     *
     * Returns plain text (HTML stripped, whitespace normalized).
     */
    public function getGenericCourseDescription(int $cid, int $sid = 0): string
    {
        if ($cid <= 0) {
            return '';
        }

        $candidates = [];
        if ($sid > 0) {
            $candidates[] = $sid;
        }
        $candidates[] = 0;

        foreach ($candidates as $sessionId) {
            try {
                $row = $this->connection->fetchAssociative(
                    'SELECT content
                     FROM c_course_description
                     WHERE c_id = :cid
                       AND session_id = :sid
                     ORDER BY id DESC
                     LIMIT 1',
                    [
                        'cid' => $cid,
                        'sid' => (int) $sessionId,
                    ]
                );

                if (!empty($row['content'])) {
                    $txt = strip_tags((string) $row['content']);
                    $txt = preg_replace('/\s+/', ' ', $txt ?? '') ?? '';

                    return trim($txt);
                }
            } catch (Throwable) {
                // Some installs may not have this table or may differ; ignore.
                continue;
            }
        }

        return '';
    }
}
