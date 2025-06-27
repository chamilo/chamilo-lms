<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRelCourseRelUserRepository;
use Chamilo\CoreBundle\ServiceHelper\AccessUrlHelper;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SESSION_MANAGER')")]
#[Route('/admin/sessionadmin')]
class SessionAdminController extends BaseController
{
    public function __construct(
        private readonly CourseRepository $courseRepository,
        private readonly AccessUrlHelper $accessUrlHelper
    ) {}

    #[Route('/courses', name: 'chamilo_core_admin_sessionadmin_courses', methods: ['GET'])]
    public function listCourses(): JsonResponse
    {
        $url     = $this->accessUrlHelper->getCurrent();
        $courses = $this->courseRepository->getCoursesByAccessUrl($url);

        $data = array_map(static function (Course $course) {
            return [
                'id'          => $course->getId(),
                'title'       => $course->getTitle(),
                'code' => $course->getCode(),
                'description' => $course->getDescription(),
                'visibility'  => $course->getVisibility(),
                'illustrationUrl' => method_exists($course, 'getIllustrationUrl')
                    ? $course->getIllustrationUrl()
                    : null,
            ];
        }, $courses);

        return $this->json($data);
    }

    #[Route('/courses/completed', name: 'chamilo_core_admin_sessionadmin_courses_completed', methods: ['GET'])]
    public function listCompleted(
        Request $request,
        GradebookCertificateRepository $repo,
        AccessUrlHelper $accessUrlHelper,
    ): JsonResponse {
        // Extract and validate pagination parameters from the query
        $offset = max(0, (int) $request->query->get('offset', 0));
        $limit  = max(1, (int) $request->query->get('limit', 50));

        // Determine current access URL context
        $url = $accessUrlHelper->getCurrent();

        // Retrieve certificates with associated session and course context
        $certs = $repo->findCertificatesWithContext($url->getId(), $offset, $limit);

        // Transform the certificate entities into a frontend-friendly structure
        $mapCertificate = function (GradebookCertificate $gc) {
            $sessionRel = $gc->getCategory()->getCourse()->getSessions()[0] ?? null;
            $session = $sessionRel?->getSession();
            $hash = pathinfo($gc->getPathCertificate(), PATHINFO_FILENAME);

            return [
                'id'        => $gc->getId(),
                'issuedAt'  => $gc->getCreatedAt()->format('Y-m-d H:i:s'),
                'user'      => [
                    'id'   => $gc->getUser()->getId(),
                    'name' => $gc->getUser()->getFullName(),
                ],
                'course'    => [
                    'id'    => $gc->getCategory()->getCourse()->getId(),
                    'title' => $gc->getCategory()->getCourse()->getTitle(),
                ],
                'session'   => $session ? [
                    'id'    => $session->getId(),
                    'title' => $session->getTitle(),
                ] : null,
                'downloadUrl' => '/certificates/'.$hash.'.pdf',
            ];
        };

        $items = array_map($mapCertificate, $certs);

        // Return JSON response with pagination metadata and certificate list
        return $this->json([
            'items'  => $items,
            'offset' => $offset,
            'limit'  => $limit,
            'count'  => \count($items),
        ]);
    }

    #[Route('/courses/incomplete', name: 'chamilo_core_admin_sessionadmin_courses_incomplete', methods: ['GET'])]
    public function listIncomplete(
        GradebookCertificateRepository $repo,
        AccessUrlHelper $accessUrlHelper
    ): JsonResponse {
        $url = $accessUrlHelper->getCurrent();
        $results = $repo->findIncompleteCertificates($url->getId());

        $items = array_map(function (SessionRelUser $sru) {
            $user = $sru->getUser();
            $session = $sru->getSession();

            $courses = $session->getCourses();
            $courseItems = [];

            foreach ($courses as $src) {
                $course = $src->getCourse();

                $courseItems[] = [
                    'user' => [
                        'id'   => $user->getId(),
                        'name' => $user->getFullName(),
                    ],
                    'course' => [
                        'id'    => $course->getId(),
                        'title' => $course->getTitle(),
                    ],
                    'session' => [
                        'id'    => $session->getId(),
                        'title' => $session->getTitle(),
                        'startDate' => $session->getAccessStartDate()?->format('Y-m-d'),
                        'endDate'   => $session->getAccessEndDate()?->format('Y-m-d'),
                    ],
                ];
            }

            return $courseItems;
        }, $results);

        $flatItems = array_merge(...$items);

        return $this->json([
            'items' => $flatItems,
            'count' => count($flatItems),
        ]);
    }

    #[Route('/courses/restartable', name: 'chamilo_core_admin_sessionadmin_courses_restartable', methods: ['GET'])]
    public function listRestartables(
        Request $request,
        GradebookCertificateRepository $repo,
        AccessUrlHelper $accessUrlHelper
    ): JsonResponse {
        $offset = max(0,  (int) $request->query->get('offset', 0));
        $limit  = max(1,  (int) $request->query->get('limit', 10));

        $urlId  = $accessUrlHelper->getCurrent()->getId();

        /** @var SessionRelCourseRelUser[] $rows */
        $rows = $repo->findRestartableSessions($urlId, $offset, $limit);

        $items = array_map(static function (SessionRelCourseRelUser $srcu) {
            $session = $srcu->getSession();
            $course  = $srcu->getCourse();
            $user    = $srcu->getUser();

            return [
                'user'    => ['id' => $user->getId(),   'name' => $user->getFullName()],
                'course'  => ['id' => $course->getId(), 'title'=> $course->getTitle()],
                'session' => [
                    'id'      => $session->getId(),
                    'title'   => $session->getTitle(),
                    'endDate' => $session->getAccessEndDate()?->format('Y-m-d'),
                ],
            ];
        }, $rows);

        return $this->json([
            'items'  => $items,
            'offset' => $offset,
            'limit'  => $limit,
            'count'  => \count($items),
        ]);
    }

    #[Route('/courses/extend_week', name: 'chamilo_core_admin_sessionadmin_session_extend_one_week', methods: ['POST'])]
    public function extendSessionByWeek(Request $request, SessionRelCourseRelUserRepository $sessionRelCourseRelUserRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $sessionId = (int) ($data['sessionId'] ?? 0);
        $userId    = (int) ($data['userId'] ?? 0);
        $courseId  = (int) ($data['courseId'] ?? 0);

        if (!$sessionId || !$userId || !$courseId) {
            return $this->json(['error' => 'Missing data'], Response::HTTP_BAD_REQUEST);
        }

        $rel = $sessionRelCourseRelUserRepository->findOneBy([
            'session' => $sessionId,
            'user' => $userId,
            'course' => $courseId,
        ]);

        if (!$rel) {
            return $this->json(['error' => 'Relation not found'], Response::HTTP_NOT_FOUND);
        }

        $session = $rel->getSession();

        $now = new DateTime('now');
        $currentEndDate = $session->getAccessEndDate();

        $baseDate = $now > $currentEndDate ? $now : $currentEndDate;

        $newEndDate = clone $baseDate;
        $newEndDate->modify('+1 week');

        $session->setAccessEndDate($newEndDate);
        $entityManager->flush();

        return $this->json(['success' => true, 'message' => 'Session extended by one week.', 'newEndDate' => $newEndDate->format('Y-m-d')]);
    }

    #[Route('/courses/{id}', name: 'chamilo_core_admin_sessionadmin_course_view', methods: ['GET'])]
    public function getCourseForSessionAdmin(int $id): JsonResponse
    {
        $course = $this->courseRepository->find($id);

        if (!$course) {
            return $this->json(['error' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $course->getId(),
            'title' => $course->getTitle(),
            'code' => $course->getCode(),
            'description' => $course->getDescription(),
            'illustrationUrl' => method_exists($course, 'getIllustrationUrl') ? $course->getIllustrationUrl() : null,
        ]);
    }
}
