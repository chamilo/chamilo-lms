<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\Session;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class GradeBookCategoryRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, GradebookCategory::class);
        $this->entityManager = $entityManager;
    }

    /**
     * Retrieves gradebook categories for a specific course and optional session.
     *
     * @param int $courseId The ID of the course.
     * @param int|null $sessionId The ID of the session (optional).
     * @return GradebookCategory[] A list of gradebook categories.
     */
    public function getCategoriesForCourse(int $courseId, ?int $sessionId = null): array
    {
        $qb = $this->createQueryBuilder('gc')
            ->where('gc.course = :courseId')
            ->setParameter('courseId', $courseId);

        if ($sessionId !== null) {
            $qb->andWhere('gc.session = :sessionId')
                ->setParameter('sessionId', $sessionId);
        } else {
            $qb->andWhere('gc.session IS NULL');
        }

        $qb->orderBy('gc.title', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Creates a default gradebook category for a course if it doesn't already exist.
     *
     * @param int $courseId The ID of the course.
     * @param int|null $sessionId The ID of the session (optional).
     * @return GradebookCategory The default category.
     */
    public function createDefaultCategory(int $courseId, ?int $sessionId = null): GradebookCategory
    {
        $existingCategory = $this->findOneBy([
            'course' => $courseId,
            'session' => $sessionId,
            'parent' => null, // Root category
        ]);

        if ($existingCategory) {
            return $existingCategory; // Return existing category
        }

        $defaultCategory = new GradebookCategory();
        $defaultCategory->setTitle('Default');
        $defaultCategory->setCourse($this->entityManager->getReference(Course::class, $courseId));
        $defaultCategory->setSession($sessionId ? $this->entityManager->getReference(Session::class, $sessionId) : null);
        $defaultCategory->setWeight(1.0);
        $defaultCategory->setVisible(true);

        $this->entityManager->persist($defaultCategory);
        $this->entityManager->flush();

        return $defaultCategory;
    }
}
