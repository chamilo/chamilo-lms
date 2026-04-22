<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseCategory;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\CourseCatalogueHelper;
use Chamilo\CoreBundle\Repository\CourseCategoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly CourseCategoryRepository $courseCategoryRepository,
        private readonly CourseCatalogueHelper $courseCatalogueHelper,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/home-categories-data', name: 'home_categories_data', methods: ['GET'])]
    public function categories(): JsonResponse
    {
        $accessUrlId = $this->accessUrlHelper->getCurrent()?->getId() ?? 1;
        $categories = $this->courseCategoryRepository->findAllInAccessUrl($accessUrlId);

        $items = array_map(
            fn (CourseCategory $category): array => [
                'id' => $category->getId(),
                'iri' => '/api/course_categories/'.$category->getId(),
                'title' => $category->getTitle(),
                'code' => $category->getCode(),
                'description' => $category->getDescription(),
                'visibleCourseCount' => $this->getVisibleCourseCountByCategory($category, $accessUrlId),
            ],
            $categories,
        );

        return $this->json([
            'items' => $items,
        ]);
    }

    private function getVisibleCourseCountByCategory(CourseCategory $category, int $accessUrlId): int
    {
        $courseRepository = $this->entityManager->getRepository(Course::class);

        $qb = $courseRepository->createQueryBuilder('c');

        $qb
            ->select('COUNT(DISTINCT c.id)')
            ->innerJoin('c.categories', 'cat')
            ->andWhere('cat.id = :categoryId')
            ->setParameter('categoryId', $category->getId(), Types::INTEGER);

        if ($this->accessUrlHelper->isMultiple()) {
            $qb
                ->innerJoin(
                    'c.urls',
                    'aurc',
                    'WITH',
                    $qb->expr()->eq('aurc.url', ':accessUrl'),
                )
                ->setParameter('accessUrl', $accessUrlId, Types::INTEGER);
        }

        $this->courseCatalogueHelper->addAvoidedCoursesCondition($qb);
        $this->courseCatalogueHelper->addShowInCatalogueCondition($qb);
        $this->courseCatalogueHelper->addVisibilityCondition($qb, true);

        try {
            return (int) $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException) {
            return 0;
        } catch (NonUniqueResultException) {
            return 0;
        }
    }
}
