<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Admin;
use Chamilo\CoreBundle\Entity\CatalogueCourseRelAccessUrlRelUsergroup;
use Chamilo\CoreBundle\Entity\CatalogueSessionRelAccessUrlRelUsergroup;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UsergroupRelUser;
use Chamilo\CoreBundle\Entity\UserRelCourseVote;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\ServiceHelper\AccessUrlHelper;
use Chamilo\CoreBundle\ServiceHelper\UserHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
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
            $userGroupIds = array_map(fn ($ug) => $ug->getUsergroup()->getId(), $userGroups);

            $visibleCourses = [];

            foreach ($relations as $rel) {
                $course = $rel->getCourse();
                $usergroup = $rel->getUsergroup();

                if (null === $usergroup || \in_array($usergroup->getId(), $userGroupIds)) {
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
        $voteRepo = $this->em->getRepository(UserRelCourseVote::class);

        $relations = $relRepo->findBy(['accessUrl' => $accessUrl]);

        if (empty($relations)) {
            $sessions = $this->sessionRepository->findAll();
        } else {
            $userGroups = $userGroupRepo->findBy(['user' => $user]);
            $userGroupIds = array_map(fn ($ug) => $ug->getUsergroup()->getId(), $userGroups);

            $visibleSessions = [];

            foreach ($relations as $rel) {
                $session = $rel->getSession();
                $usergroup = $rel->getUsergroup();

                if (null === $usergroup || \in_array($usergroup->getId(), $userGroupIds)) {
                    $visibleSessions[$session->getId()] = $session;
                }
            }

            $sessions = array_values($visibleSessions);
        }

        $data = array_map(function (Session $session) use ($voteRepo, $user) {
            $courses = [];

            foreach ($session->getCourses() as $rel) {
                $course = $rel->getCourse();
                if (!$course) {
                    continue;
                }

                $teachers = [];
                foreach ($session->getGeneralCoachesSubscriptions() as $coachRel) {
                    $userObj = $coachRel->getUser();
                    if ($userObj) {
                        $teachers[] = [
                            'id' => $userObj->getId(),
                            'fullName' => $userObj->getFullname(),
                        ];
                    }
                }

                $courses[] = [
                    'id' => $course->getId(),
                    'title' => $course->getTitle(),
                    'duration' => $course->getDuration(),
                    'courseLanguage' => $course->getCourseLanguage(),
                    'teachers' => $teachers,
                ];
            }

            $voteCount = (int) $voteRepo->createQueryBuilder('v')
                ->select('COUNT(DISTINCT v.user)')
                ->where('v.session = :session')
                ->andWhere('v.course IS NULL')
                ->setParameter('session', $session->getId())
                ->getQuery()
                ->getSingleScalarResult()
            ;

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
                'courses' => $courses,
                'popularity' => $voteCount,
                'isSubscribed' => $session->hasUserInSession($user, Session::STUDENT),
            ];
        }, $sessions);

        return $this->json($data);
    }

    #[Route('/course-extra-fields', name: 'chamilo_core_catalogue_course_extra_fields', methods: ['GET'])]
    public function getCourseExtraFields(SettingsManager $settingsManager): JsonResponse
    {
        if ('true' !== $settingsManager->getSetting('course.allow_course_extra_field_in_catalog')) {
            return $this->json([]);
        }

        $extraField = new \ExtraField('course');
        $fields = $extraField->get_all(['filter' => 1]);

        $result = array_map(function ($field) {
            return [
                'variable' => $field['variable'],
                'display_text' => $field['display_text'],
                'value_type' => $field['value_type'],
            ];
        }, $fields);

        return $this->json($result);
    }

    #[Route('/auto-subscribe-course/{courseId}', name: 'chamilo_core_catalogue_auto_subscribe_course', methods: ['POST'])]
    public function autoSubscribeCourse(int $courseId, SettingsManager $settings): JsonResponse
    {
        $user = $this->userHelper->getCurrent();
        $course = $this->em->getRepository(Course::class)->find($courseId);

        if (!$user || !$course) {
            return $this->json(['error' => 'Course or user not found'], 400);
        }

        $useAutoSession = $settings->getSetting('session.catalog_course_subscription_in_user_s_session', true) === 'true';

        if ($useAutoSession) {
            $session = new Session();
            $timestamp = (new \DateTime())->format('Ymd_His');
            $sessionTitle = sprintf('%s %s - Session %s', $user->getFirstname(), $user->getLastname(), $timestamp);
            $session->setTitle($sessionTitle);

            $session->setAccessStartDate(new \DateTime());
            $session->setAccessEndDate(null);
            $session->setCoachAccessEndDate(null);
            $session->setDisplayEndDate(null);
            $session->setSendSubscriptionNotification(false);

            $adminIdSetting = $settings->getSetting('session.session_automatic_creation_user_id');
            $adminId = null;

            if (is_numeric($adminIdSetting) && (int) $adminIdSetting > 0) {
                $adminUser = $this->em->getRepository(User::class)->find((int) $adminIdSetting);
                if ($adminUser) {
                    $adminId = $adminUser->getId();
                }
            }

            if (!$adminId) {
                $adminEntity = $this->em->getRepository(Admin::class)->findOneBy([]);
                if ($adminEntity) {
                    $adminId = $adminEntity->getUser()->getId();
                }
            }

            if ($adminId) {
                $adminUser = $this->em->getRepository(User::class)->find($adminId);
                if ($adminUser) {
                    $session->addSessionAdmin($adminUser);
                }
            }

            $session->addUserInSession(Session::STUDENT, $user);
            $session->addAccessUrl($this->accessUrlHelper->getCurrent());
            $session->addCourse($course);
            $session->addUserInCourse(Session::STUDENT, $user, $course);

            $this->em->persist($session);
            $this->em->flush();
        }

        return $this->json([
            'message' => 'User subscribed successfully.',
            'sessionId' => $session?->getId(),
        ]);
    }
}
