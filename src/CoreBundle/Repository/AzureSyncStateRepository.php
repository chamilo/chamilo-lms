<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AzureSyncState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AzureSyncState>
 */
class AzureSyncStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AzureSyncState::class);
    }

    public function save(string $title, string $value): void
    {
        $em = $this->getEntityManager();

        $state = $this->findOneBy(['title' => $title]);

        if (!$state) {
            $state = new AzureSyncState();
            $state->setTitle($title);

            $em->persist($state);
        }

        $state->setValue($value);

        $em->flush();
    }
}
