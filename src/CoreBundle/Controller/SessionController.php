<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use BuyCoursesPlugin;
use Chamilo\CoreBundle\Entity\AccessUrlRelUser;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\SequenceResource;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\Tag;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\MessageHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SequenceRepository;
use Chamilo\CoreBundle\Repository\SequenceResourceRepository;
use Chamilo\CoreBundle\Repository\TagRepository;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use CourseDescription;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Essence\Essence;
use ExtraFieldValue;
use Graphp\GraphViz\GraphViz;
use SessionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use UserManager;

#[Route('/sessions')]
class SessionController extends AbstractController
{
    public function __construct(
        private readonly UserHelper $userHelper,
        private readonly TranslatorInterface $translator,
        private readonly RequestStack $requestStack
    ) {}

    #[Route(path: '/{sid}/about', name: 'chamilo_core_session_about')]
    public function about(
        Request $request,
        Session $session,
        IllustrationRepository $illustrationRepo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        $requestSession = $request->getSession();
        $htmlHeadXtra[] = api_get_asset('readmore-js/readmore.js');

        $sessionId = $session->getId();
        $courses = [];
        $sessionCourses = $session->getCourses();

        /** @var EntityRepository $fieldsRepo */
        $fieldsRepo = $em->getRepository(ExtraField::class);

        /** @var TagRepository $tagRepo */
        $tagRepo = $em->getRepository(Tag::class);

        /** @var SequenceRepository $sequenceResourceRepo */
        $sequenceResourceRepo = $em->getRepository(SequenceResource::class);

        /** @var ExtraField $tagField */
        $tagField = $fieldsRepo->findOneBy([
            'itemType' => ExtraField::COURSE_FIELD_TYPE,
            'variable' => 'tags',
        ]);

        $courseValues = new ExtraFieldValue('course');
        $userValues = new ExtraFieldValue('user');
        $sessionValues = new ExtraFieldValue('session');

        /** @var SessionRelCourse $sessionRelCourse */
        foreach ($sessionCourses as $sessionRelCourse) {
            $sessionCourse = $sessionRelCourse->getCourse();
            $courseTags = [];

            if (null !== $tagField) {
                $courseTags = $tagRepo->getTagsByItem($tagField, $sessionCourse->getId());
            }

            $courseCoaches = $userRepo->getCoachesForSessionCourse($session, $sessionCourse);
            $coachesData = [];

            /** @var User $courseCoach */
            foreach ($courseCoaches as $courseCoach) {
                $coachData = [
                    'complete_name' => UserManager::formatUserFullName($courseCoach),
                    'image' => $illustrationRepo->getIllustrationUrl($courseCoach),
                    'diploma' => $courseCoach->getDiplomas(),
                    'openarea' => $courseCoach->getOpenarea(),
                    'extra_fields' => $userValues->getAllValuesForAnItem(
                        $courseCoach->getId(),
                        null,
                        true
                    ),
                ];

                $coachesData[] = $coachData;
            }

            $cd = new CourseDescription();
            $cd->set_course_id($sessionCourse->getId());
            $cd->set_session_id($session->getId());
            $descriptionsData = $cd->get_description_data();

            $courseDescription = [];
            $courseObjectives = [];
            $courseTopics = [];
            $courseMethodology = [];
            $courseMaterial = [];
            $courseResources = [];
            $courseAssessment = [];
            $courseCustom = [];

            if (!empty($descriptionsData)) {
                foreach ($descriptionsData as $descriptionInfo) {
                    $type = $descriptionInfo->getDescriptionType();

                    switch ($type) {
                        case CCourseDescription::TYPE_DESCRIPTION:
                            $courseDescription[] = $descriptionInfo;

                            break;

                        case CCourseDescription::TYPE_OBJECTIVES:
                            $courseObjectives[] = $descriptionInfo;

                            break;

                        case CCourseDescription::TYPE_TOPICS:
                            $courseTopics[] = $descriptionInfo;

                            break;

                        case CCourseDescription::TYPE_METHODOLOGY:
                            $courseMethodology[] = $descriptionInfo;

                            break;

                        case CCourseDescription::TYPE_COURSE_MATERIAL:
                            $courseMaterial[] = $descriptionInfo;

                            break;

                        case CCourseDescription::TYPE_RESOURCES:
                            $courseResources[] = $descriptionInfo;

                            break;

                        case CCourseDescription::TYPE_ASSESSMENT:
                            $courseAssessment[] = $descriptionInfo;

                            break;

                        case CCourseDescription::TYPE_CUSTOM:
                            $courseCustom[] = $descriptionInfo;

                            break;
                    }
                }
            }

            $courses[] = [
                'course' => $sessionCourse,
                'description' => $courseDescription,
                'image' => Container::getIllustrationRepository()->getIllustrationUrl($sessionCourse),
                'tags' => $courseTags,
                'objectives' => $courseObjectives,
                'topics' => $courseTopics,
                'methodology' => $courseMethodology,
                'material' => $courseMaterial,
                'resources' => $courseResources,
                'assessment' => $courseAssessment,
                'custom' => array_reverse($courseCustom),
                'coaches' => $coachesData,
                'extra_fields' => $courseValues->getAllValuesForAnItem(
                    $sessionCourse->getId(),
                    null,
                    true
                ),
            ];
        }

        $sessionDates = SessionManager::parseSessionDates($session, true);

        $hasRequirements = false;

        /*$sessionRequirements = $sequenceResourceRepo->getRequirements(
         * $session->getId(),
         * SequenceResource::SESSION_TYPE
         * );
         * foreach ($sessionRequirements as $sequence) {
         * if (!empty($sequence['requirements'])) {
         * $hasRequirements = true;
         * break;
         * }
         * }*/
        $plugin = BuyCoursesPlugin::create();
        $checker = $plugin->isEnabled();
        $sessionIsPremium = null;
        if ($checker) {
            $sessionIsPremium = $plugin->getItemByProduct(
                $sessionId,
                BuyCoursesPlugin::PRODUCT_TYPE_SESSION
            );
            if ([] !== $sessionIsPremium) {
                $requestSession->set('SessionIsPremium', true);
                $requestSession->set('sessionId', $sessionId);
            }
        }

        $redirectToSession = ('true' === Container::getSettingsManager()->getSetting('session.allow_redirect_to_session_after_inscription_about'));
        $redirectToSession = $redirectToSession ? '?s='.$sessionId : false;

        $coursesInThisSession = SessionManager::get_course_list_by_session_id($sessionId);
        $coursesCount = \count($coursesInThisSession);
        $redirectToSession = 1 === $coursesCount && $redirectToSession
            ? ($redirectToSession.'&cr='.array_values($coursesInThisSession)[0]['directory'])
            : $redirectToSession;

        $essence = new Essence();

        $params = [
            'session' => $session,
            'redirect_to_session' => $redirectToSession,
            'courses' => $courses,
            'essence' => $essence,
            'session_extra_fields' => $sessionValues->getAllValuesForAnItem($session->getId(), null, true),
            'has_requirements' => $hasRequirements,
            // 'sequences' => $sessionRequirements,
            'is_premium' => $sessionIsPremium,
            'show_tutor' => 'true' === api_get_setting('show_session_coach'),
            'page_url' => api_get_path(WEB_PATH).\sprintf('sessions/%s/about/', $session->getId()),
            'session_date' => $sessionDates,
            'is_subscribed' => SessionManager::isUserSubscribedAsStudent(
                $session->getId(),
                api_get_user_id()
            ),
            'user_session_time' => SessionManager::getDayLeftInSession(
                [
                    'id' => $session->getId(),
                    'duration' => $session->getDuration(),
                ],
                api_get_user_id()
            ),
            'base_url' => $request->getSchemeAndHttpHost(),
        ];

        return $this->render('@ChamiloCore/Session/about.html.twig', $params);
    }

    #[Route('/{id}/send-course-notification', name: 'chamilo_core_session_send_course_notification', methods: ['POST'])]
    public function sendCourseNotification(
        int $id,
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        MessageHelper $messageHelper,
        AccessUrlHelper $accessUrlHelper
    ): Response {
        $session = $em->getRepository(Session::class)->find($id);
        $currentUser = $this->userHelper->getCurrent();

        if (!$session) {
            return $this->json(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }

        $studentId = $request->request->get('studentId');
        if (!$studentId) {
            return $this->json(['error' => 'Missing studentId'], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepo->find($studentId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $email = $user->getEmail();
        if (empty($email)) {
            return $this->json(['error' => 'User has no email address.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$user->isActive()) {
            $user->setActive(User::ACTIVE);
            $em->persist($user);
        }

        $accessUrl = $accessUrlHelper->getCurrent();

        if ($accessUrl) {
            $hasAccess = $user->getPortals()->exists(
                fn ($k, $rel) => $rel->getUrl()?->getId() === $accessUrl->getId()
            );

            if (!$hasAccess) {
                $rel = new AccessUrlRelUser();
                $rel->setUser($user);
                $rel->setUrl($accessUrl);

                $em->persist($rel);
            }
        }

        $em->flush();

        $now = new DateTime();
        $relSessions = $em->getRepository(SessionRelUser::class)->findBy([
            'user' => $user,
            'relationType' => Session::STUDENT,
        ]);

        $activeSessions = array_filter($relSessions, function (SessionRelUser $rel) use ($now) {
            $s = $rel->getSession();

            return $s->getAccessStartDate() <= $now && $s->getAccessEndDate() >= $now;
        });

        $sessionListHtml = '';
        foreach ($activeSessions as $rel) {
            $s = $rel->getSession();
            $sessionListHtml .= '<li>'.htmlspecialchars($s->getTitle()).'</li>';
        }

        $request = $this->requestStack->getCurrentRequest();
        $baseUrl = $request->getSchemeAndHttpHost().$request->getBasePath();
        $sessionUrl = rtrim($baseUrl, '/').'/sessions';

        $subject = $this->translator->trans('You have been enrolled in a new course');

        $bodyTemplate = $this->translator->trans(
            'Hello %s,<br><br>'.
            'You have been enrolled in a new session: <strong>%s</strong>.<br>'.
            'You can access your courses from <a href="%s">here</a>.<br><br>'.
            'Your current active sessions are:<br><ul>%s</ul><br>'.
            'Best regards,<br>'.
            'Chamilo'
        );

        $body = \sprintf(
            $bodyTemplate,
            $user->getFullName(),
            $session->getTitle(),
            $sessionUrl,
            $sessionListHtml
        );

        $messageHelper->sendMessage(
            $user->getId(),
            $subject,
            $body,
            [],
            [],
            0,
            0,
            0,
            $currentUser->getId(),
            0,
            false,
            true
        );

        return $this->json(['success' => true]);
    }

    #[Route('/{sessionId}/next-session', name: 'chamilo_session_next_session')]
    public function getNextSession(
        int $sessionId,
        Request $request,
        SequenceResourceRepository $repo,
        Security $security
    ): JsonResponse {
        $user = $security->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $requirementAndDependencies = $repo->getRequirementAndDependencies(
            $sessionId,
            SequenceResource::SESSION_TYPE
        );

        $sequences = $repo->getRequirements($sessionId, SequenceResource::SESSION_TYPE);
        $requirementsStatus = $repo->checkRequirementsForUser(
            $sequences,
            SequenceResource::SESSION_TYPE,
            $user->getId()
        );
        $isUnlocked = $repo->checkSequenceAreCompleted($requirementsStatus);

        $requirements = [];
        foreach ($requirementAndDependencies['requirements'] ?? [] as $requirement) {
            $requirements[] = [
                'id' => $requirement['id'],
                'name' => $requirement['name'],
                'admin_link' => $requirement['admin_link'],
            ];
        }

        $dependents = $repo->getDependents($sessionId, SequenceResource::SESSION_TYPE);
        $dependentsStatus = $repo->checkDependentsForUser(
            $dependents,
            SequenceResource::SESSION_TYPE,
            $user->getId(),
            $sessionId
        );

        $dependencies = [];
        foreach ($dependentsStatus as $sequence) {
            foreach ($sequence['dependents'] as $id => $item) {
                $dependencies[] = [
                    'id' => $id,
                    'name' => $item['name'],
                    'admin_link' => $item['adminLink'] ?? null,
                    'unlocked' => (bool) $item['status'],
                ];
            }
        }

        $graphImage = null;
        $sequenceResource = $repo->findRequirementForResource($sessionId, SequenceResource::SESSION_TYPE);
        if ($sequenceResource && $sequenceResource->hasGraph()) {
            $graph = $sequenceResource->getSequence()->getUnSerializeGraph();
            if (null !== $graph) {
                $graph->setAttribute('graphviz.node.fontname', 'arial');
                $graphviz = new GraphViz();
                $graphImage = $graphviz->createImageSrc($graph);
            }
        }

        return new JsonResponse([
            'requirements' => $requirements,
            'dependencies' => $dependencies,
            'graph' => $graphImage,
            'unlocked' => $isUnlocked,
        ]);
    }
}
