<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CForumThreadFeedback;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CForumThreadFeedbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CForumThreadFeedback::class);
    }

    public function findOneByLegacyCommentId(int $legacyCommentId): ?CForumThreadFeedback
    {
        return $this->findOneBy(['legacyCommentId' => $legacyCommentId]);
    }

    /**
     * @return CForumThreadFeedback[]
     */
    public function findByThread(CForumThread $thread): array
    {
        return $this->findBy(['thread' => $thread], ['iid' => 'ASC']);
    }
}
