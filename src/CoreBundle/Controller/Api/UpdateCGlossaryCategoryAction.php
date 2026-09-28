<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CGlossaryCategory;
use Chamilo\CourseBundle\Repository\CGlossaryCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class UpdateCGlossaryCategoryAction
{
    public function __invoke(
        CGlossaryCategory $category,
        Request $request,
        CGlossaryCategoryRepository $repository,
        EntityManagerInterface $entityManager,
    ): CGlossaryCategory {
        $data = json_decode((string) $request->getContent(), true) ?: [];
        $title = trim(strip_tags((string) ($data['title'] ?? '')));
        $courseId = $request->query->getInt('cid', (int) ($data['cid'] ?? 0));
        $sessionId = max(0, $request->query->getInt('sid', (int) ($data['sid'] ?? 0)));

        if ('' === $title) {
            throw new BadRequestHttpException('Category title is required.');
        }

        $course = $courseId > 0 ? $entityManager->find(Course::class, $courseId) : null;
        $session = $sessionId > 0 ? $entityManager->find(Session::class, $sessionId) : null;

        if (!$course instanceof Course) {
            throw new BadRequestHttpException('Course not found.');
        }
        if ($sessionId > 0 && !$session instanceof Session) {
            throw new BadRequestHttpException('Session not found.');
        }

        $owned = $repository->findInExactCourseContext((int) $category->getIid(), $course, $session);
        if (!$owned instanceof CGlossaryCategory) {
            throw new AccessDeniedHttpException('The category does not belong to the current course/session context.');
        }

        $duplicate = $repository->getResourcesByCourse($course, $session)
            ->andWhere('resource.title = :title')
            ->andWhere('resource.iid != :categoryId')
            ->setParameter('title', $title)
            ->setParameter('categoryId', (int) $category->getIid())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
        if ($duplicate instanceof CGlossaryCategory) {
            throw new BadRequestHttpException('The glossary category already exists.');
        }

        $category->setTitle($title);
        $entityManager->persist($category);
        $entityManager->flush();

        return $category;
    }
}
