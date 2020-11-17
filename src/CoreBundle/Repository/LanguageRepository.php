<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Language;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class LanguageRepository.
 */
class LanguageRepository extends ServiceEntityRepository
{
    /**
     * LanguageRepository constructor.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Language::class);
    }

    /**
     * Get all the sub languages that are made available by the admin.
     *
     * @return array
     */
    public function findAllPlatformSubLanguages()
    {
        $qb = $this->createQueryBuilder('l');
        $qb->select('l')
            ->where(
                $qb->expr()->eq('l.available', true)
            )
            ->andWhere(
                $qb->expr()->isNotNull('l.parent')
            );

        return $qb->getQuery()->getResult();
    }
}
