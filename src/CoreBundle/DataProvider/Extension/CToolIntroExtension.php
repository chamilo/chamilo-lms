<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CourseBundle\Entity\CToolIntro;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

// use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;

final class CToolIntroExtension implements QueryCollectionExtensionInterface
{
    use CourseLinkExtensionTrait;

    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $requestStack
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (CToolIntro::class !== $resourceClass) {
            return;
        }

        if (null === $user = $this->security->getUser()) {
            throw new AccessDeniedException('Access Denied.');
        }

        $this->addCourseLinkWithVisibilityConditions($queryBuilder, true);
    }
}
