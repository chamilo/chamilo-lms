<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Transcript;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transcript>
 */
class TranscriptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transcript::class);
    }

    public function findOneByToken(string $token): ?Transcript
    {
        return $this->findOneBy(['token' => $token]);
    }

    /**
     * @return Transcript[]
     */
    public function findByUserInUrl(User $user, AccessUrl $url): array
    {
        return $this->findBy(['user' => $user, 'url' => $url], ['issuedAt' => 'DESC', 'id' => 'DESC']);
    }
}
