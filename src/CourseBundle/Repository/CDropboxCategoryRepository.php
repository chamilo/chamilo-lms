<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CDropboxCategory;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Custom queries for Dropbox categories.
 */
final class CDropboxCategoryRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CDropboxCategory::class);
    }

    /**
     * Find categories scoped by course, session, user and area (sent|received).
     */
    public function findByContextAndArea(int $cid, ?int $sid, int $uid, string $area): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.cId = :cid')->setParameter('cid', $cid)
            ->andWhere('c.sessionId = :sid')->setParameter('sid', $sid ?? 0)
            ->andWhere('c.userId = :uid')->setParameter('uid', $uid);

        if ($area === 'sent') {
            $qb->andWhere('c.sent = true');
        } elseif ($area === 'received') {
            $qb->andWhere('c.received = true');
        }

        return $qb->orderBy('c.title', 'ASC')->getQuery()->getResult();
    }

    /**
     * Create a category for the given user/context and mirror cat_id = iid
     * Uses two flushes to first get autoincrement iid, then align cat_id without raw SQL.
     */
    public function createForUser(int $cid, ?int $sid, int $uid, string $title, string $area): CDropboxCategory
    {
        $em = $this->getEntityManager();

        $cat = new CDropboxCategory();
        $cat->setCId($cid);
        $cat->setSessionId($sid ?? 0);
        $cat->setUserId($uid);
        $cat->setTitle($title);
        $cat->setReceived($area === 'received');
        $cat->setSent($area === 'sent');
        $cat->setCatId(0); // will be mirrored to iid after first flush

        // 1st flush: ensure iid is generated.
        $em->persist($cat);
        $em->flush();

        // Mirror cat_id = iid safely.
        $cat->setCatId((int) $cat->getIid());

        // 2nd flush: persist alignment.
        $em->flush();

        return $cat;
    }
}
