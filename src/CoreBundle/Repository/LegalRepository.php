<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\Legal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

class LegalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Legal::class);
    }

    /**
     * Count the legal terms by language (only count one set of terms for each
     * language).
     *
     * @throws Exception
     *
     * @return int
     */
    public function countAllActiveLegalTerms()
    {
        $qb = $this->createQueryBuilder('l');
        $qb->select('l.languageId, COUNT(l.id)')
            ->groupBy('l.languageId')
        ;

        return \count($qb->getQuery()->getResult());
    }

    /**
     * Get the latest version of terms of the given type and language.
     *
     * @param int $typeId     The type of terms:
     *                        0 for general text,
     *                        1 for general HTML link,
     *                        101 for private data collection,
     *                        etc - see personal_data.php
     * @param int $languageId The Id of the language
     *
     * @return array The terms for those type and language
     */
    public function findOneByTypeAndLanguage(int $typeId, int $languageId)
    {
        $qb = $this->createQueryBuilder('l');
        $qb->select('l.content')
            ->where($qb->expr()->eq('l.type', $typeId))
            ->andWhere($qb->expr()->eq('l.languageId', $languageId))
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * Get type of terms and conditions.
     * Type 0 is HTML Text
     * Type 1 is a link to a different terms and conditions page.
     *
     * @return mixed The current type of terms and conditions (int) or false on error
     */
    public function getTypeOfTermsAndConditions(Legal $legal, Language $language)
    {
        $qb = $this->createQueryBuilder('l');
        $qb->select('l.type')
            ->where($qb->expr()->eq('l.id', $legal->getId()))
            ->andWhere($qb->expr()->eq('l.languageId', $language->getId()))
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * Gets the number of terms and conditions available.
     *
     * @return int
     */
    public function countTerms()
    {
        $qb = $this->createQueryBuilder('l');
        $qb->select('COUNT(l.id)');

        return \count($qb->getQuery()->getResult());
    }

    /**
     * Gets the last version of a Term and condition by language.
     *
     * @return bool | int the version or false if does not exist
     */
    public function getLastVersion(int $languageId)
    {
        $qb = $this->createQueryBuilder('l');

        $result = $qb
            ->select('l.version')
            ->where(
                $qb->expr()->eq('l.languageId', $languageId)
            )
            ->setMaxResults(1)
            ->orderBy('l.version', Criteria::DESC)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!empty($result['version'])) {
            $lastVersion = $result['version'];
            if (!is_numeric($lastVersion)) {
                $version = explode(':', $lastVersion);
                $lastVersion = (int) $version[0];
            }

            return $lastVersion;
        }

        return false;
    }
}
