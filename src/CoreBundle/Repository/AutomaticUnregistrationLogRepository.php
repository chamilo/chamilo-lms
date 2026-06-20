<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AutomaticUnregistrationLog;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AutomaticUnregistrationLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AutomaticUnregistrationLog::class);
    }

    public function findOneByLegacyId(int $legacyId): ?AutomaticUnregistrationLog
    {
        return $this->findOneBy(['legacyId' => $legacyId]);
    }

    /**
     * @return AutomaticUnregistrationLog[]
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['deletedAt' => 'DESC', 'iid' => 'DESC']);
    }

    /**
     * @return AutomaticUnregistrationLog[]
     */
    public function findByCourse(Course $course): array
    {
        return $this->findBy(['course' => $course], ['deletedAt' => 'DESC', 'iid' => 'DESC']);
    }
}
