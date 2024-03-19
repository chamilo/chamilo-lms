<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ColorTheme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ColorThemeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ColorTheme::class);
    }

    public function deactivateAll(): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->update(ColorTheme::class, 'ct')
            ->set('ct.active', ':inactive')
            ->where(
                $qb->expr()->eq('ct.active', ':active')
            )
            ->setParameters(['active' => true, 'inactive' => false])
            ->getQuery()
            ->execute()
        ;
    }

    public function getActiveOne(): ?ColorTheme
    {
        return $this->findOneBy(
            ['active' => true],
            ['createdAt' => 'DESC']
        );
    }
}
