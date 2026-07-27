<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\MobilePushInstallation;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MobilePushInstallationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MobilePushInstallation::class);
    }

    public function findOneByAccessUrlAndInstallationId(
        AccessUrl $accessUrl,
        string $installationId
    ): ?MobilePushInstallation {
        return $this->findOneBy([
            'accessUrl' => $accessUrl,
            'installationId' => $installationId,
        ]);
    }

    public function findOneByAccessUrlAndTokenHash(
        AccessUrl $accessUrl,
        string $tokenHash
    ): ?MobilePushInstallation {
        return $this->findOneBy([
            'accessUrl' => $accessUrl,
            'tokenHash' => $tokenHash,
        ]);
    }

    public function removeOwnedInstallation(
        User $user,
        AccessUrl $accessUrl,
        string $installationId
    ): int {
        return $this->createQueryBuilder('installation')
            ->delete()
            ->where('installation.user = :user')
            ->andWhere('installation.accessUrl = :accessUrl')
            ->andWhere('installation.installationId = :installationId')
            ->setParameter('user', $user)
            ->setParameter('accessUrl', $accessUrl)
            ->setParameter('installationId', $installationId)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @param User[] $recipients
     *
     * @return MobilePushInstallation[]
     */
    public function findForRecipientsAndAccessUrl(array $recipients, AccessUrl $accessUrl): array
    {
        if ([] === $recipients) {
            return [];
        }

        return $this->createQueryBuilder('installation')
            ->where('installation.user IN (:recipients)')
            ->andWhere('installation.accessUrl = :accessUrl')
            ->setParameter('recipients', $recipients)
            ->setParameter('accessUrl', $accessUrl)
            ->getQuery()
            ->getResult()
        ;
    }

    public function removeInvalidInstallation(MobilePushInstallation $installation): int
    {
        $id = $installation->getId();

        if (null === $id) {
            return 0;
        }

        return $this->createQueryBuilder('invalidInstallation')
            ->delete()
            ->where('invalidInstallation.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->execute()
        ;
    }
}
