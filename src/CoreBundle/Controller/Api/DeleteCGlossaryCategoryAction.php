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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class DeleteCGlossaryCategoryAction
{
    public function __invoke(
        CGlossaryCategory $category,
        Request $request,
        CGlossaryCategoryRepository $repository,
        EntityManagerInterface $entityManager,
    ): Response {
        $courseId = $request->query->getInt('cid');
        $sessionId = max(0, $request->query->getInt('sid'));

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

        foreach ($category->getTerms() as $term) {
            $term->setCategory(null);
            $entityManager->persist($term);
        }

        $repository->delete($category);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
