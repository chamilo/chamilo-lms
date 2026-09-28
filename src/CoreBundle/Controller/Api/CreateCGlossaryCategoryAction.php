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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class CreateCGlossaryCategoryAction
{
    public function __invoke(
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

        $existing = $repository->getResourcesByCourse($course, $session)
            ->andWhere('resource.title = :title')
            ->setParameter('title', $title)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
        if ($existing instanceof CGlossaryCategory) {
            throw new BadRequestHttpException('The glossary category already exists.');
        }

        $category = (new CGlossaryCategory())
            ->setTitle($title)
            ->setParent($course)
            ->addCourseLink($course, $session)
        ;

        $entityManager->persist($category);
        $entityManager->flush();

        return $category;
    }
}
