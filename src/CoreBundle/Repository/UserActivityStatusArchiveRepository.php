<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserActivityStatusArchive;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserActivityStatusArchiveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserActivityStatusArchive::class);
    }

    public function findOneByLegacyTrackingId(int $legacyTrackingId): ?UserActivityStatusArchive
    {
        return $this->findOneBy(['legacyTrackingId' => $legacyTrackingId]);
    }

    /**
     * @return UserActivityStatusArchive[]
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['sessionTimeAt' => 'DESC', 'iid' => 'DESC']);
    }
}
