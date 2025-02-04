<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Language;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LanguageRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        parent::__construct($registry, Language::class);
    }

    public function getAllAvailable($excludeDefaultLocale = false): QueryBuilder
    {
        $qb = $this->createQueryBuilder('l');
        $qb
            ->where(
                $qb->expr()->eq('l.available', true)
            )
            /*->andWhere(
                $qb->expr()->isNull('l.parent')
            )*/
        ;

        if ($excludeDefaultLocale) {
            $qb
                ->andWhere($qb->expr()->neq('l.isocode', ':iso_en'))
                ->setParameter('iso_en', $this->parameterBag->get('locale'))
            ;
        }

        return $qb;
    }

    public function getAllAvailableToArray(bool $onlyActive = false): array
    {
        $queryBuilder = $this->getAllAvailable();

        if (!$onlyActive) {
            $queryBuilder->resetDQLPart('where');
        }

        $languages = $queryBuilder->getQuery()->getResult();

        $list = [];

        /** @var Language $language */
        foreach ($languages as $language) {
            $list[$language->getIsocode()] = $language->getOriginalName();
        }

        return $list;
    }

    /**
     * Get all the sub languages that are made available by the admin.
     *
     * @return Collection|Language[]
     */
    public function findAllSubLanguages()
    {
        $qb = $this->createQueryBuilder('l');
        $qb->select('l')
            ->where(
                $qb->expr()->eq('l.available', true)
            )
            ->andWhere(
                $qb->expr()->isNotNull('l.parent')
            )
        ;

        return $qb->getQuery()->getResult();
    }

    public function findByIsoCode(string $isoCode): ?Language
    {
        $qb = $this->createQueryBuilder('l');
        $qb->where('l.isocode = :isoCode')
            ->setParameter('isoCode', $isoCode)
            ->setMaxResults(1)
        ;

        return $qb->getQuery()->getSingleResult();
    }
}
