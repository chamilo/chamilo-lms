<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\AccessUrlRelCourse;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class CourseRelUserExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private Security $security,
        private AccessUrlHelper $accessUrlHelper,
        private EntityManagerInterface $entityManager
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (CourseRelUser::class !== $resourceClass) {
            return;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        // Need to be login to access the list.
        if (null === $user = $this->security->getUser()) {
            throw new AccessDeniedException('Access Denied.');
        }

        if ($this->accessUrlHelper->isMultiple()) {
            $accessUrl = $this->accessUrlHelper->getCurrent();
            $rootAlias = $queryBuilder->getRootAliases()[0];
            if (isset($context['filters']['sticky']) && $context['filters']['sticky']) {
                $queryBuilder
                    ->innerJoin(
                        AccessUrlRelCourse::class,
                        'url_rel',
                        'WITH',
                        'url_rel.course = '.$rootAlias
                    )
                    ->andWhere('url_rel.url = :access_url_id')
                    ->setParameter('access_url_id', $accessUrl->getId())
                ;
            } else {
                $metaData = $this->entityManager->getClassMetadata($resourceClass);
                if ($metaData->hasAssociation('course')) {
                    $queryBuilder
                        ->innerJoin("$rootAlias.course", 'co')
                        ->innerJoin('co.urls', 'url_rel')
                        ->andWhere('url_rel.url = :access_url_id')
                        ->setParameter('access_url_id', $accessUrl->getId())
                    ;
                }
            }
        }

        if ('collection_query' === $operation?->getName()) {
            $rootAlias = $queryBuilder->getRootAliases()[0];
            $queryBuilder->leftJoin("$rootAlias.course", 'c');
            $queryBuilder
                ->orderBy('c.title', 'ASC')
                ->addOrderBy("$rootAlias.sort", 'ASC')
                ->addOrderBy("$rootAlias.userCourseCat", 'ASC')
            ;

            if (!$this->security->isGranted('ROLE_ADMIN')) {
                /** @var User|null $user */
                if (null === $user = $this->security->getUser()) {
                    throw new AccessDeniedException('Access Denied.');
                }

                $queryBuilder->andWhere(\sprintf('%s.user = :current_user', $rootAlias));
                $queryBuilder->setParameter('current_user', $user->getId());
            }
        }
    }
}
