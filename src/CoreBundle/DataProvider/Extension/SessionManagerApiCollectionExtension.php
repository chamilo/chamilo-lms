<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\SessionManagerApiScope;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Applies only to the four collections explicitly opted in via metadata.
 * Other collections, including the public catalogue and personal subscriptions,
 * must keep their existing authorization and providers unchanged.
 */
final readonly class SessionManagerApiCollectionExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private Security $security,
        private SessionManagerApiScope $scope,
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if (true !== ($operation?->getExtraProperties()['sessionManagerScoped'] ?? false)
            || !$this->security->isGranted('ROLE_SESSION_MANAGER')
            || $this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $actor = $this->security->getUser();
        if (!$actor instanceof User) {
            throw new AccessDeniedException('An authenticated session manager is required.');
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $sessionIds = $this->scope->getSessionIds($actor);
        $courseIds = $this->scope->getCourseIds($actor);

        if (Session::class === $resourceClass) {
            $this->addIdScope($queryBuilder, $alias, $sessionIds, 'sessionManagerSessionIds');
        } elseif (Course::class === $resourceClass) {
            $this->addIdScope($queryBuilder, $alias, $courseIds, 'sessionManagerCourseIds');
        } elseif (ExtraField::class === $resourceClass) {
            $types = [];
            if ([] !== $courseIds) {
                $types[] = ExtraField::COURSE_FIELD_TYPE;
            }
            if ([] !== $sessionIds) {
                $types[] = ExtraField::SESSION_FIELD_TYPE;
            }
            $this->addIdScope($queryBuilder, $alias.'.itemType', $types, 'sessionManagerFieldTypes');
        } elseif (ExtraFieldValues::class === $resourceClass) {
            if ([] === $sessionIds && [] === $courseIds) {
                $queryBuilder->andWhere('1 = 0');

                return;
            }

            $queryBuilder->innerJoin($alias.'.field', 'session_manager_scope_field');
            $clauses = [];
            if ([] !== $courseIds) {
                $clauses[] = '(session_manager_scope_field.itemType = :sessionManagerCourseType AND '
                    .$alias.'.itemId IN (:sessionManagerCourseIds))';
                $queryBuilder->setParameter('sessionManagerCourseType', ExtraField::COURSE_FIELD_TYPE)
                    ->setParameter('sessionManagerCourseIds', $courseIds)
                ;
            }
            if ([] !== $sessionIds) {
                $clauses[] = '(session_manager_scope_field.itemType = :sessionManagerSessionType AND '
                    .$alias.'.itemId IN (:sessionManagerSessionIds))';
                $queryBuilder->setParameter('sessionManagerSessionType', ExtraField::SESSION_FIELD_TYPE)
                    ->setParameter('sessionManagerSessionIds', $sessionIds)
                ;
            }
            $queryBuilder->andWhere('('.implode(' OR ', $clauses).')');
        }
    }

    /**
     * @param int[] $ids
     */
    private function addIdScope(QueryBuilder $qb, string $field, array $ids, string $parameter): void
    {
        if ([] === $ids) {
            $qb->andWhere('1 = 0');

            return;
        }

        $qb->andWhere($field.' IN (:'.$parameter.')')->setParameter($parameter, $ids);
    }
}
