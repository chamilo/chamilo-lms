<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\GradebookCertificateExpiryNotification;
use Chamilo\CoreBundle\Enums\CertificateExpiryNotificationType;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GradebookCertificateExpiryNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GradebookCertificateExpiryNotification::class);
    }

    /**
     * Whether a reminder of this type has already been sent for the certificate's
     * CURRENT expiry date. If the expiry date has since changed (e.g. a teacher
     * edited it), a prior reminder no longer counts and this returns false.
     */
    public function hasBeenNotified(
        int $certificateId,
        CertificateExpiryNotificationType $type,
        DateTime $currentExpiryDate,
    ): bool {
        $count = (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.certificate = :certificateId')
            ->andWhere('n.notificationType = :type')
            ->andWhere('n.expiryDateAtSend = :expiryDate')
            ->setParameter('certificateId', $certificateId)
            ->setParameter('type', $type)
            ->setParameter('expiryDate', $currentExpiryDate)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $count > 0;
    }

    /**
     * Latest reminder of each type for a set of certificate ids, keyed by
     * "certificateId:notificationType". Used to populate the expirations list.
     *
     * @param list<int> $certificateIds
     *
     * @return array<string, GradebookCertificateExpiryNotification>
     */
    public function findLatestPerCertificate(array $certificateIds): array
    {
        if ([] === $certificateIds) {
            return [];
        }

        $rows = $this->createQueryBuilder('n')
            ->andWhere('n.certificate IN (:certificateIds)')
            ->setParameter('certificateIds', $certificateIds)
            ->orderBy('n.sentAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $latest = [];
        foreach ($rows as $row) {
            if (!$row instanceof GradebookCertificateExpiryNotification) {
                continue;
            }
            $key = $row->getCertificate()->getId().':'.$row->getNotificationType()->value;
            $latest[$key] = $row;
        }

        return $latest;
    }
}
