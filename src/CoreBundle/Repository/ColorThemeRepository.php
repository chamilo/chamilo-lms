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

    public function deactivateAllExcept(ColorTheme $colorTheme): void
    {
        $this->getEntityManager()
            ->createQuery('UPDATE Chamilo\CoreBundle\Entity\ColorTheme t SET t.active = FALSE WHERE t.id <> :id')
            ->execute(['id' => $colorTheme->getId()])
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
