<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\CatalogueCourseRelAccessUrlRelUsergroup;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use CourseManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class CourseListActionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AccessUrlHelper $accessUrlHelper,
    ) {}

    #[Route('/admin/course-list-action', name: 'admin_course_list_action', methods: ['POST'])]
    public function handleAction(Request $request): Response
    {
        $action = (string) $request->request->get('action');
        $token = (string) $request->request->get('_token');

        if (!$this->isCsrfTokenValid('admin_course_list', $token)) {
            if ('toggle_catalogue' === $action) {
                return $this->json(['error' => 'Invalid CSRF token.'], 403);
            }
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirect('/admin/course-list');
        }

        return match ($action) {
            'delete_course' => $this->deleteSingle($request),
            'delete_courses' => $this->deleteBulk($request),
            'toggle_catalogue' => $this->toggleCatalogue($request),
            default => $this->redirect('/admin/course-list'),
        };
    }

    private function deleteSingle(Request $request): RedirectResponse
    {
        $courseId = (int) $request->request->get('course_id');
        $course = $this->em->getRepository(Course::class)->find($courseId);

        if ($course) {
            CourseManager::delete_course($course->getCode());
            $this->addFlash('success', 'The course has been deleted.');
        } else {
            $this->addFlash('error', 'Course not found.');
        }

        return $this->redirect('/admin/course-list');
    }

    private function deleteBulk(Request $request): RedirectResponse
    {
        $courseIds = $request->request->all('course_ids');

        if (empty($courseIds)) {
            $this->addFlash('error', 'No courses selected.');

            return $this->redirect('/admin/course-list');
        }

        $deleted = 0;
        foreach ($courseIds as $courseId) {
            $course = $this->em->getRepository(Course::class)->find((int) $courseId);
            if ($course) {
                CourseManager::delete_course($course->getCode());
                $deleted++;
            }
        }

        $this->addFlash('success', $deleted.' course(s) deleted.');

        return $this->redirect('/admin/course-list');
    }

    private function toggleCatalogue(Request $request): JsonResponse
    {
        $courseId = (int) $request->request->get('course_id');
        $course = $this->em->getRepository(Course::class)->find($courseId);

        if (!$course) {
            return $this->json(['error' => 'Course not found.'], 404);
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();

        if (!$accessUrl) {
            return $this->json(['error' => 'No access URL found.'], 500);
        }

        $repo = $this->em->getRepository(CatalogueCourseRelAccessUrlRelUsergroup::class);
        $existing = $repo->findOneBy([
            'course' => $course,
            'accessUrl' => $accessUrl,
        ]);

        if ($existing) {
            $this->em->remove($existing);
            $this->em->flush();

            return $this->json(['inCatalogue' => false]);
        }

        $entry = new CatalogueCourseRelAccessUrlRelUsergroup();
        $entry->setCourse($course);
        $entry->setAccessUrl($accessUrl);
        $this->em->persist($entry);
        $this->em->flush();

        return $this->json(['inCatalogue' => true]);
    }
}
