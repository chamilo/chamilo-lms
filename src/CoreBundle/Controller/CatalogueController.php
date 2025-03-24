<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\CatalogueCourseRelAccessUrlRelUsergroup;
use Chamilo\CoreBundle\Entity\CatalogueSessionRelAccessUrlRelUsergroup;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\UsergroupRelUser;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\ServiceHelper\AccessUrlHelper;
use Chamilo\CoreBundle\ServiceHelper\UserHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/catalogue')]
class CatalogueController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserHelper $userHelper,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly CourseRepository $courseRepository,
        private readonly SessionRepository $sessionRepository
    ) {}

    #[Route('/courses-list', name: 'chamilo_core_catalogue_courses_list', methods: ['GET'])]
    public function listCourses(): JsonResponse
    {
        $user = $this->userHelper->getCurrent();
        $accessUrl = $this->accessUrlHelper->getCurrent();

        $relRepo = $this->em->getRepository(CatalogueCourseRelAccessUrlRelUsergroup::class);
        $userGroupRepo = $this->em->getRepository(UsergroupRelUser::class);

        $relations = $relRepo->findBy(['accessUrl' => $accessUrl]);

        if (empty($relations)) {
            $courses = $this->courseRepository->findAll();
        } else {
            $userGroups = $userGroupRepo->findBy(['user' => $user]);
            $userGroupIds = array_map(fn($ug) => $ug->getUsergroup()->getId(), $userGroups);

            $visibleCourses = [];

            foreach ($relations as $rel) {
                $course = $rel->getCourse();
                $usergroup = $rel->getUsergroup();

                if ($usergroup === null || in_array($usergroup->getId(), $userGroupIds)) {
                    $visibleCourses[$course->getId()] = $course;
                }
            }

            $courses = array_values($visibleCourses);
        }

        $data = array_map(function (Course $course) {
            return [
                'id' => $course->getId(),
                'code' => $course->getCode(),
                'title' => $course->getTitle(),
                'description' => $course->getDescription(),
                'visibility' => $course->getVisibility(),
            ];
        }, $courses);

        return $this->json($data);
    }

    #[Route('/sessions-list', name: 'chamilo_core_catalogue_sessions_list', methods: ['GET'])]
    public function listSessions(): JsonResponse
    {
        $user = $this->userHelper->getCurrent();
        $accessUrl = $this->accessUrlHelper->getCurrent();

        $relRepo = $this->em->getRepository(CatalogueSessionRelAccessUrlRelUsergroup::class);
        $userGroupRepo = $this->em->getRepository(UsergroupRelUser::class);

        $relations = $relRepo->findBy(['accessUrl' => $accessUrl]);

        if (empty($relations)) {
            $sessions = $this->sessionRepository->findAll();
        } else {
            $userGroups = $userGroupRepo->findBy(['user' => $user]);
            $userGroupIds = array_map(fn($ug) => $ug->getUsergroup()->getId(), $userGroups);

            $visibleSessions = [];

            foreach ($relations as $rel) {
                $session = $rel->getSession();
                $usergroup = $rel->getUsergroup();

                if ($usergroup === null || in_array($usergroup->getId(), $userGroupIds)) {
                    $visibleSessions[$session->getId()] = $session;
                }
            }

            $sessions = array_values($visibleSessions);
        }

        $data = array_map(function (Session $session) {
            return [
                'id' => $session->getId(),
                'title' => $session->getTitle(),
                'description' => $session->getDescription(),
                'imageUrl' => $session->getImageUrl(),
                'visibility' => $session->getVisibility(),
                'nbrUsers' => $session->getNbrUsers(),
                'nbrCourses' => $session->getNbrCourses(),
                'startDate' => $session->getAccessStartDate()?->format('Y-m-d'),
                'endDate' => $session->getAccessEndDate()?->format('Y-m-d'),
            ];
        }, $sessions);

        return $this->json($data);
    }
}
