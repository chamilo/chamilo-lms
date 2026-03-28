<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Doctrine\Orm\Extension\FilterExtension;
use ApiPlatform\Doctrine\Orm\Extension\OrderExtension;
use ApiPlatform\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\CourseRelUserRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @template-implements ProviderInterface<CourseRelUser>
 */
final class CourseRelUserCollectionStateProvider implements ProviderInterface
{
    private array $extensions;

    public function __construct(
        private readonly CollectionProvider $collectionProvider,
        private readonly CourseRelUserRepository $courseRelUserRepository,
        private readonly UserHelper $userHelper,
        private readonly Security $security,
        private readonly CourseRepository $courseRepo,
        FilterExtension $filterExtension,
        OrderExtension $orderExtension,
        PaginationExtension $paginationExtension,
    ) {
        $this->extensions = [$filterExtension, $orderExtension, $paginationExtension];
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        if (!$operation instanceof GetCollection) {
            return $this->collectionProvider->provide($operation, $uriVariables, $context);
        }

        // Privileged roles: return the full collection with all API Platform filters applied.
        if (
            $this->security->isGranted('ROLE_ADMIN')
            || $this->security->isGranted('ROLE_SUPER_ADMIN')
            || $this->security->isGranted('ROLE_GLOBAL_ADMIN')
        ) {
            return $this->collectionProvider->provide($operation, $uriVariables, $context);
        }

        // Students and other authenticated users: restrict to their own subscriptions.
        $currentUser = $this->userHelper->getCurrent();

        if (!$currentUser instanceof User) {
            throw new AccessDeniedException('User not authenticated.');
        }

        if ($context['filters']['course'] ?? null) {
            $course = $this->courseRepo->find($context['filters']['course']);

            if (!$this->security->isGranted(CourseVoter::VIEW, $course)) {
                throw new AccessDeniedException();
            }
        }

        $qb = $this->courseRelUserRepository->createQueryBuilder('cru');
        $qb
            ->andWhere(
                $qb->expr()->exists(
                    'SELECT 1 FROM '.CourseRelUser::class.' my_cru
                     WHERE my_cru.course = cru.course
                       AND my_cru.user = :currentUser'
                )
            )
            ->setParameter('currentUser', $currentUser)
        ;

        $queryNameGenerator = new QueryNameGenerator();
        $items = [];

        foreach ($this->extensions as $extension) {
            $extension->applyToCollection($qb, $queryNameGenerator, CourseRelUser::class, $operation, $context);

            if (
                $extension instanceof QueryResultCollectionExtensionInterface
                && $extension->supportsResult(CourseRelUser::class, $operation, $context)
            ) {
                $items = $extension->getResult($qb, CourseRelUser::class, $operation, $context);
            }
        }

        return [] !== $items ? $items : $qb->getQuery()->getResult();
    }
}
