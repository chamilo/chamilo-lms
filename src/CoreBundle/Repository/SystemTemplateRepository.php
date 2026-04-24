<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\SystemTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SystemTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SystemTemplate::class);
    }

    public function findForLanguageFilter(array $languageCandidates): array
    {
        $normalizedCandidates = [];

        foreach ($languageCandidates as $candidate) {
            $candidate = trim((string) $candidate);

            if ('' === $candidate) {
                continue;
            }

            $normalizedCandidates[] = mb_strtolower($candidate);
        }

        $normalizedCandidates = array_values(array_unique($normalizedCandidates));

        $qb = $this->createQueryBuilder('template')
            ->orderBy('template.title', 'ASC')
        ;

        if (empty($normalizedCandidates)) {
            $qb->andWhere('template.language IS NULL OR template.language = :emptyLanguage');
            $qb->setParameter('emptyLanguage', '');

            return $qb->getQuery()->getResult();
        }

        $qb
            ->andWhere(
                $qb->expr()->orX(
                    'template.language IS NULL',
                    'template.language = :emptyLanguage',
                    'LOWER(template.language) IN (:languages)'
                )
            )
            ->setParameter('emptyLanguage', '')
            ->setParameter('languages', $normalizedCandidates)
        ;

        return $qb->getQuery()->getResult();
    }
}
