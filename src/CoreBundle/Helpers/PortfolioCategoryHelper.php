<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\PortfolioCategory;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\SecurityBundle\Security;

class PortfolioCategoryHelper
{
    private EntityRepository $categoryRepo;

    public function __construct(
        private readonly Security $security,
        EntityManagerInterface $entityManager,
    ) {
        $this->categoryRepo = $entityManager->getRepository(PortfolioCategory::class);
    }

    /**
     * @return array<int, PortfolioCategory>
     */
    public function getListForIndex(
        ?int $parentId = null,
        ?User $owner = null,
    ): array {
        $categoriesCriteria = [];

        if (!$this->security->isGranted('ROLE_ADMIN') && null !== $owner?->getId()) {
            $categoriesCriteria['isVisible'] = true;
        }

        if ($parentId) {
            $categoriesCriteria['parent'] = $parentId;
        }

        return $this->categoryRepo->findBy($categoriesCriteria);
    }
}
