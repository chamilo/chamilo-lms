<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CGlossaryCategory;
use Chamilo\CourseBundle\Repository\CGlossaryCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class GetGlossaryCategoryCollectionController
{
    public function __invoke(
        Request $request,
        CGlossaryCategoryRepository $repository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
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

        $categories = $repository
            ->getResourcesByCourse($course, $session, null, null, true, true)
            ->getQuery()
            ->getResult()
        ;

        $data = [];
        foreach ($categories as $category) {
            if (!$category instanceof CGlossaryCategory) {
                continue;
            }

            $link = $category->getFirstResourceLink();
            $data[] = [
                'iid' => (int) ($category->getIid() ?? 0),
                'id' => (int) ($category->getIid() ?? 0),
                'title' => $category->getTitle(),
                'sessionId' => $link?->getSession()?->getId(),
            ];
        }

        return new JsonResponse($data);
    }
}
