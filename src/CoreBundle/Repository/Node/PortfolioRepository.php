<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldRelTag;
use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

class PortfolioRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Portfolio::class);
    }

    public function findItemsByUser(
        User $user,
        ?Course $course,
        ?Session $session,
        ?array $orderBy = null,
        array $visibility = []
    ): array {
        $criteria = [];
        $criteria['user'] = $user;

        if ($course) {
            $criteria['course'] = $course;
            $criteria['session'] = $session;
        }

        if ($visibility) {
            $criteria['visibility'] = $visibility;
        }

        return $this->findBy($criteria, $orderBy);
    }

    public function findTemplates(User $creator, ?Course $course, ?Session $session)
    {
        $qb = $this->getResourcesByCourse($course, $session);

        $this->addCreatorQueryBuilder($creator, $qb);

        return $qb
            ->andWhere($qb->expr()->eq('resource.isTemplate', true))
            ->getQuery()
            ->getResult()
        ;
    }

    public function getIndexCourseItems(
        User $currentUser,
        User $owner,
        Course $course,
        ?Session $session = null,
        bool $showBaseContentInSession = false,
        bool $listByUser = false,
        ?string $date = null,
        array $tags = [],
        ?string $searchText = null,
        array $searchCategories = [],
        array $searchNoInCategories = [],
        bool $advancedSharingEnabled = false
    ): array {
        $queryBuilder = $this->getResources();
        $this->addCourseQueryBuilder($course, $queryBuilder);

        if ($session) {
            if ($showBaseContentInSession) {
                $this->addSessionAndBaseContentQueryBuilder($session, $queryBuilder);
            } else {
                $this->addSessionOnlyQueryBuilder($session, $queryBuilder);
            }
        } else {
            $this->addSessionNullQueryBuilder($queryBuilder);
        }

        if ($date) {
            $queryBuilder
                ->andWhere('node.createdAt >= :date')
                ->setParameter(':date', $date)
            ;
        }

        if ($tags) {
            $queryBuilder
                ->innerJoin(ExtraFieldRelTag::class, 'efrt', Join::WITH, 'efrt.itemId = resource.id')
                ->innerJoin(ExtraField::class, 'ef', Join::WITH, 'ef.id = efrt.fieldId')
                ->andWhere('ef.extraFieldType = :efType')
                ->andWhere('ef.variable = :variable')
                ->andWhere('efrt.tagId IN (:tags)')
            ;

            $queryBuilder->setParameter('efType', ExtraField::PORTFOLIO_TYPE);
            $queryBuilder->setParameter('variable', 'tags');
            $queryBuilder->setParameter('tags', $tags);
        }

        if (!empty($searchText)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->like('resource.title', ':text'),
                    $queryBuilder->expr()->like('resource.content', ':text')
                )
            );

            $queryBuilder->setParameter('text', '%'.$searchText.'%');
        }

        if ($searchCategories) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in('resource.category', $searchCategories)
            );
        }

        if ($searchNoInCategories) {
            $queryBuilder->andWhere('resource.category NOT IN('.implode(',', $searchNoInCategories).')');
        }

        if ($listByUser) {
            $queryBuilder
                ->andWhere('node.creator = :user')
                ->setParameter('user', $owner)
            ;
        }

        if ($advancedSharingEnabled) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq('resource.visibility', Portfolio::VISIBILITY_VISIBLE),
                    $queryBuilder->expr()->eq('links.user', ':current_user')
                )
            );
        } else {
            $visibilityCriteria = [Portfolio::VISIBILITY_VISIBLE];

            if (api_is_allowed_to_edit()) {
                $visibilityCriteria[] = Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER;
            }

            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    'node.creator = :current_user',
                    $queryBuilder->expr()->andX(
                        'node.creator != :current_user',
                        $queryBuilder->expr()->in('resource.visibility', $visibilityCriteria)
                    )
                )
            );
        }

        $queryBuilder
            ->setParameter('current_user', $currentUser->getId())
            ->orderBy('node.createdAt', 'DESC')
        ;

        return $queryBuilder->getQuery()->getResult();
    }
}
